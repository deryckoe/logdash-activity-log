<?php

namespace LogDash\Hooks;

use LogDash\API\DB;
use LogDash\API\EventMeta;
use LogDash\EventCodes;
use LogDash\EventTypes;
use LogDash\Template;
use LogDash\Template\Meta\After;
use LogDash\Template\Meta\Before;
use LogDash\Template\Meta\Label;
use LogDash\Template\Meta\MetaView;
use LogDash\Template\Meta\Specification;

class Users extends HooksBase {

	private static string $object_type = 'user';
	private array $old_meta = [];

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( 'user_register', [ $this, 'user_register' ], 10, 2 );
		add_action( 'deleted_user', [ $this, 'deleted_user' ], 10, 3 );
		add_action( 'profile_update', [ $this, 'profile_update' ], 10, 3 );
		add_action( 'wp_login', [ $this, 'login' ], 10, 2 );
		add_action( 'wp_logout', [ $this, 'logout' ] );
		add_action( 'wp_login_failed', [ $this, 'login_failed' ], 10, 2 );
//		add_action( 'update_user_meta', [ $this, 'before_update_user_meta' ], 10, 4 );
//		add_action( 'updated_user_meta', [ $this, 'updated_user_meta' ], 10, 4 );
		add_action( 'admin_init', array( $this, 'extra_actions' ) );
		add_filter( 'logdash_manage_columns-' . self::$object_type . '-content_event_meta', [
			$this,
			'event_meta_info'
		], 10, 3 );
	}

	public function extra_actions() {

		if ( ! isset( $_POST['action'] ) || ! isset( $_POST['user_id'] ) ) {
			return;
		}

		$action       = sanitize_text_field( $_POST['action'] );
		$user_id      = intval( $_POST['user_id'] );
		$user         = get_user_by( 'ID', $user_id );
		$current_user = wp_get_current_user();

		if ( $action === 'destroy-sessions' ) {
			$this
				->event
				->insert( EventTypes::LOGOUT, EventCodes::USER_DESTROYED_SESSIONS, self::$object_type, self::$object_type, $user_id, $current_user->ID, $current_user->roles[0] )
				->attachMany( [
					new EventMeta( 'userLogin', $user->user_login )
				] );
		}

	}

	public function user_register( $user_id, $user_data ) {

		$registered_user = get_user_by( 'ID', $user_id );
		unset( $registered_user->data->user_pass );

		$current_user = wp_get_current_user();

		$this->event
			->insert( EventTypes::CREATED, EventCodes::USER_CREATED, self::$object_type, self::$object_type, $registered_user->ID, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'userLogin', $registered_user->user_login ),
				new EventMeta( 'firstName', $registered_user->first_name ),
				new EventMeta( 'lastName', $registered_user->last_name ),
				new EventMeta( 'roles', $registered_user->roles ),
			] );
	}

	public function deleted_user( $user_id, $reassign, $user_data ) {

		// TODO: Is not saving first and last name

		$current_user = wp_get_current_user();

		$reassign_posts = $reassign ? '1' : '0';

		$this
			->event
			->insert( EventTypes::DELETED, EventCodes::USER_DELETED, self::$object_type, self::$object_type, $user_id, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'reassignPosts', $reassign_posts ),
				new EventMeta( 'userLogin', $user_data->user_login ),
				new EventMeta( 'firstName', $user_data->first_name ),
				new EventMeta( 'lastName', $user_data->last_name ),
				new EventMeta( 'roles', $user_data->roles ),
			] );
	}

	public function profile_update( $user_id, $user_old_data, $user_data ) {

		$user_new_data = get_user_by( 'ID', $user_id );

		$current_user = wp_get_current_user();

		$fields_sensor = [
			'user_login'    => EventCodes::USER_UPDATED_LOGIN,
			'user_pass'     => EventCodes::USER_UPDATE_PASSWORD,
			'user_nicename' => EventCodes::USER_UPDATED_NICENAME,
			'user_email'    => EventCodes::USER_UPDATED_EMAIL,
			'user_url'      => EventCodes::USER_UPDATED_URL,
			'user_status'   => EventCodes::USER_UPDATED_STATUS,
			'display_name'  => EventCodes::USER_UPDATED_DISPLAYNAME,
			'roles'         => EventCodes::USER_UPDATED_ROLE,
		];

		foreach ( $fields_sensor as $field => $code ) {
			if ( $user_old_data->$field !== $user_new_data->$field ) {

				$meta = [
					new EventMeta( 'userLogin', $user_new_data->user_login ),
					new EventMeta( 'firstName', $user_new_data->first_name ),
					new EventMeta( 'lastName', $user_new_data->last_name ),
					new EventMeta( 'roles', $user_new_data->roles ),
				];

				if ( $field !== 'user_pass' ) {
					$meta = array_merge( [
						new EventMeta( 'fieldName', $field ),
						new EventMeta( 'oldValue', $user_old_data->$field ),
						new EventMeta( 'newValue', $user_new_data->$field ),
					], $meta );
				}

				$this
					->event
					->insert( EventTypes::MODIFIED, $code, self::$object_type, self::$object_type, $user_new_data->ID, $current_user->ID, $current_user->roles[0] )
					->attachMany( $meta );
				$updated = true;
			}
		}
	}

	public function login( $user_login, $user ) {
		$user         = get_user_by( 'login', $user_login );
		$current_user = wp_get_current_user();
		if ( $current_user->ID === 0 ) {
			$current_user = $user;
		}

		$this->event
			->insert( EventTypes::LOGIN, EventCodes::USER_LOGIN, self::$object_type, self::$object_type, $user->ID, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'userLogin', $user->user_login )
			] );

	}

	public function logout( $user_id ) {
		$user         = get_user_by( 'ID', $user_id );
		$current_user = wp_get_current_user();
		if ( $current_user->ID === 0 ) {
			$current_user = $user;
		}

		$this
			->event
			->insert( EventTypes::LOGOUT, EventCodes::USER_LOGOUT, self::$object_type, self::$object_type, $user_id, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'userLogin', $user->user_login )
			] );
	}

	public function login_failed( $user_login, $error ) {

		$user              = get_user_by( 'login', $user_login );
		$errors            = current( array_keys( get_object_vars( $error )['errors'] ) );
		$attempt_last_date = (string) time();
		$failed_login      = EventTypes::FAILED_LOGIN;

		global $wpdb;

		$log_table  = DB::log_table();
		$meta_table = DB::meta_table();

		$current_date = date( 'Y-m-d' );

		$user_query = $wpdb->prepare( "SELECT log.ID FROM {$log_table} AS log
                             LEFT JOIN {$meta_table} AS meta ON meta.event_id = log.ID
                             WHERE log.event_type = %s
                             AND meta.name = 'userLogin'
                             AND meta.value = %s
                             AND FROM_UNIXTIME(created, '%%Y-%%m-%%d') = %s
                             ORDER BY log.ID DESC LIMIT 1;",
			$failed_login,
			$user_login,
			$current_date );

		$event_id = $wpdb->get_var( $user_query );


		if ( $event_id ) {

			$attempts = $wpdb->get_var( $wpdb->prepare( "SELECT value FROM %i WHERE event_id = %d and name = 'attempts';", [
				$meta_table,
				$event_id
			] ) );

			if ( ! is_null( $attempts ) ) {

				$result = $wpdb->update( $meta_table, [
					'value' => (int) $attempts + 1,
				], [ 'event_id' => $event_id, 'name' => 'attempts' ] );

				$result = $wpdb->update( $meta_table, [
					'value' => $attempt_last_date,
				], [ 'event_id' => $event_id, 'name' => 'attemptsLastDate' ] );

				$result = $wpdb->update( $meta_table, [
					'value' => $errors,
				], [ 'event_id' => $event_id, 'name' => 'attemptsLastError' ] );
			}

		} else {

			if ( false === $user ) {
				$this->event
					->insert( EventTypes::FAILED_LOGIN, EventCodes::USER_LOGIN_FAIL, self::$object_type, self::$object_type, 0, 0, null )
					->attachMany( [
						new EventMeta( 'userLogin', $user_login ),
						new EventMeta( 'attempts', '1' ),
						new EventMeta( 'attemptsLastDate', $attempt_last_date ),
						new EventMeta( 'attemptsLastError', $errors ),
					] );

			} else {

				$this->event
					->insert( EventTypes::FAILED_LOGIN, EventCodes::USER_LOGIN_FAIL, self::$object_type, self::$object_type, $user->ID, $user->ID, $user->roles[0] )
					->attachMany( [
						new EventMeta( 'userLogin', $user_login ),
						new EventMeta( 'attempts', '1' ),
						new EventMeta( 'attemptsLastDate', $attempt_last_date ),
						new EventMeta( 'attemptsLastError', $errors ),
					] );

			}

		}
	}

	public function before_update_user_meta( $meta_id, $user_id, $meta_key, $meta_value ) {
		$this->old_meta[ $meta_key ] = get_user_meta( $user_id, $meta_key, true );
	}

	public function updated_user_meta( $meta_id, $user_id, $meta_key, $meta_value ) {

		$event_user        = get_user_by( 'ID', $user_id );
		$current_user      = wp_get_current_user();
		$current_user_role = $current_user->roles[0] ?? null;

		if ( $this->old_meta[ $meta_key ] === $meta_value ) {
			return;
		}

		if ( in_array( $meta_key, [ 'session_tokens', 'activation_key', 'wp_user_level', 'wp_capabilities' ] ) ) {
			return;
		}

		if ( is_int( $meta_value ) ) {
			$meta_value = (string) $meta_value;
		}

		$this->event
			->insert( EventTypes::MODIFIED, EventCodes::USER_UPDATED_META, self::$object_type, self::$object_type, $user_id, $current_user->ID, $current_user_role )
			->attachMany( [
				new EventMeta( 'fieldName', $meta_key ),
				new EventMeta( 'oldValue', $this->old_meta[ $meta_key ] ),
				new EventMeta( 'newValue', $meta_value ),
				new EventMeta( 'userLogin', $event_user->user_login ),
				new EventMeta( 'firstName', $event_user->first_name ),
				new EventMeta( 'lastName', $event_user->last_name ),
				new EventMeta( 'roles', $event_user->roles ),
			] );

	}

	public function event_meta_info( $output, $event_data, $meta_data ): string {

		if ( $event_data['object_subtype'] !== 'user' ) {
			return $output;
		}

		$user = ( ! empty( $event_data['object_id'] ) )
			? get_user_by( 'ID', $event_data['object_id'] )
			: null;


		$message = EventCodes::desc( $event_data['event_code'] );

		if ( isset( $user->ID ) ) {

			$actions = [
				[
					'href'   => get_edit_user_link( $user->ID ),
					'target' => '_self' . $user->ID,
					'label'  => ( $user->ID === get_current_user_id() ) ? __( 'View profile' ) : __( 'Edit user' )
				]
			];
		} else {
			$actions = [];
		}

		$details = [];

		$view = new Template\Meta\View();

		switch ( $event_data['event_code'] ) {
			case EventCodes::USER_UPDATED_META:

				$data = [
					new Label( $meta_data['fieldName'] ),
					new Before( $meta_data['oldValue'] ),
					new After( $meta_data['newValue'] ),
					new Label( $meta_data['userLogin'] ),
				];

				$details = [
					new Specification( __( 'User', LOGDASH_DOMAIN ), $meta_data['userLogin'] ),
					new Specification( __( 'Roles', LOGDASH_DOMAIN ), $this->_roles( $meta_data['roles'] ) ),
					new Specification( __( 'Updated field', LOGDASH_DOMAIN ), $meta_data['fieldName'] ),
					new Specification( __( 'Previous value', LOGDASH_DOMAIN ), new Before( $meta_data['oldValue'] ) ),
					new Specification( __( 'New value', LOGDASH_DOMAIN ), new After( $meta_data['newValue'] ) ),
				];
				break;

			case EventCodes::USER_CREATED:
			case EventCodes::USER_DELETED:

				$data = [
					new Label( $meta_data['userLogin'] ),
				];

				$details = [
					new Specification( __( 'User login', LOGDASH_DOMAIN ), $meta_data['userLogin'] ),
					new Specification( __( 'Roles', LOGDASH_DOMAIN ), $this->_roles( $meta_data['roles'] ) ),
					new Specification( __( 'First name', LOGDASH_DOMAIN ), $meta_data['firstName'] ),
					new Specification( __( 'Last name', LOGDASH_DOMAIN ), $meta_data['lastName'] ),
				];

				if ( EventCodes::equal( $event_data['event_code'], EventCodes::USER_DELETED ) ) {
					$details[] = new Specification( __( 'Reassign posts', LOGDASH_DOMAIN ), $meta_data['reassignPosts'] );
				}

				break;

			case EventCodes::USER_LOGIN:
			case EventCodes::USER_LOGOUT:
			case EventCodes::USER_DESTROYED_SESSIONS:

				$data = [
					new Label( $meta_data['userLogin'] ),
				];

				break;

			case EventCodes::USER_LOGIN_FAIL:

				$data = [
					new Label( $meta_data['userLogin'] ),
				];

				$format               = 'Y-m-d H:i:s';
				$gmt_date             = gmdate( $format, (int) $meta_data['attemptsLastDate'] );
				$date                 = get_date_from_gmt( $gmt_date, $format );
				$time_diff            = human_time_diff( strtotime( $date ), current_time( 'U' ) );
				$translated_time_diff = __( sprintf( '%s ago', $time_diff ) );

				$details = [
					new Specification( __( 'User', LOGDASH_DOMAIN ), $meta_data['userLogin'] ),
					new Specification( __( 'Attempts', LOGDASH_DOMAIN ), $meta_data['attempts'] ),
					new Specification( __( 'Last attempt', LOGDASH_DOMAIN ), $translated_time_diff ),
					new Specification( __( 'Last Error', LOGDASH_DOMAIN ), $meta_data['attemptsLastError'] ),
				];

				break;

			case EventCodes::USER_UPDATED_EMAIL:
			case EventCodes::USER_UPDATED_URL:
			case EventCodes::USER_UPDATED_NICENAME:
			case EventCodes::USER_UPDATED_STATUS:
			case EventCodes::USER_UPDATED_DISPLAYNAME:
			case EventCodes::USER_UPDATED_ROLE:


				$data = [
					new Before( $meta_data['oldValue'] ),
					new After( $meta_data['newValue'] ),
					new Label( $meta_data['userLogin'] ),
				];

				$details = [
					new Specification( __( 'User field', LOGDASH_DOMAIN ), $meta_data['fieldName'] ),
					new Specification( __( 'Old value', LOGDASH_DOMAIN ), new Before( $meta_data['oldValue'] ) ),
					new Specification( __( 'New new', LOGDASH_DOMAIN ), new After( $meta_data['newValue'] ) ),
				];

				break;

			case EventCodes::USER_UPDATE_PASSWORD:

				$data = [
					new Label( $meta_data['userLogin'] ),
				];

				$details = [
					new Specification( __( 'User login', LOGDASH_DOMAIN ), $meta_data['userLogin'] ),
					new Specification( __( 'Roles', LOGDASH_DOMAIN ), $this->_roles( $meta_data['roles'] ) ),
					new Specification( __( 'First Name', LOGDASH_DOMAIN ), $meta_data['firstName'] ),
					new Specification( __( 'Last Name', LOGDASH_DOMAIN ), $meta_data['lastName'] ),
				];

				break;

			default:
				$data = [];


		}

		$details = array_merge( $details, [
			new Specification( __( 'User Agent', LOGDASH_DOMAIN ), $event_data['user_agent'] ),
		] );

		$view
			->message( $message, $data )
			->actions( $actions )
			->details( $details );


		return $view->get();

	}

	private function _roles( $value ) {
		if ( ! empty( $value ) ) {
			return implode( ', ', unserialize( $value ) );
		}

		return '';

	}

}