<?php

namespace LogDash\Hooks;

use LogDash\API\EventMeta;
use LogDash\EventCodes;
use LogDash\EventTypes;
use LogDash\Template\Meta\After;
use LogDash\Template\Meta\Before;
use LogDash\Template\Meta\Label;
use LogDash\Template\Meta\Specification;
use LogDash\Template\Meta\View;

class Settings extends HooksBase {

	private static string $object_type = 'setting';
	private array $setting_excluded;
	private array $setting_labels;
	private string $setting_to_be_deleted;

	public function init() {

		$this->setting_excluded = [
			'cron',
			'theme_switched',
			'active_plugins',
			'recently_activated',
			'recovery_keys',
			'recovery_mode_email_last_sent',
			'sticky_posts',
			'wp_calendar_block_has_published_posts',
			'category_children',
			'type_children',
			'action_scheduler_lock_async-request-runner',
		];

		$this->setting_labels = [
			'siteurl'             => __( 'WordPress Address (URL)', LOGDASH_DOMAIN ),
			'home'                => __( 'Site Address (URL)', LOGDASH_DOMAIN ),
			'blogname'            => __( 'Site Title', LOGDASH_DOMAIN ),
			'blogdescription'     => __( 'Tagline', LOGDASH_DOMAIN ),
			'users_can_register'  => __( 'Anyone can register', LOGDASH_DOMAIN ),
			'admin_email'         => __( 'Administration Email Address', LOGDASH_DOMAIN ),
			'default_role'        => __( 'New User Default Role', LOGDASH_DOMAIN ),
			'WPLANG'              => __( 'Site Language', LOGDASH_DOMAIN ),
			'timezone_string'     => __( 'Timezone', LOGDASH_DOMAIN ),
			'date_format'         => __( 'Date Format', LOGDASH_DOMAIN ),
			'time_format'         => __( 'Time Format', LOGDASH_DOMAIN ),
			'start_of_week'       => __( 'Week Starts On', LOGDASH_DOMAIN ),
			'front-static-pages'  => __( 'Your homepage displays', LOGDASH_DOMAIN ),
			'page_on_front'       => __( 'Homepage:', LOGDASH_DOMAIN ),
			'page_for_posts'      => __( 'Posts page:', LOGDASH_DOMAIN ),
			'posts_per_page'      => __( 'Blog pages show at most', LOGDASH_DOMAIN ),
			'posts_per_rss'       => __( 'Syndication feeds show the most recent', LOGDASH_DOMAIN ),
			'rss_use_excerpt'     => __( 'For each post in a feed, include', LOGDASH_DOMAIN ),
			'blog_public'         => __( 'Search engine visibility', LOGDASH_DOMAIN ),
			'permalink_structure' => __( 'Permalink structure', LOGDASH_DOMAIN ),
		];

		$this->actions();

	}

	public function actions() {
		add_action( 'added_option', [ $this, 'added_option' ], 10, 2 );
		add_action( 'delete_option', [ $this, 'before_delete_option' ] );
		add_action( 'deleted_option', [ $this, 'deleted_option' ] );
		add_action( 'updated_option', [ $this, 'updated_option' ], 10, 3 );
		add_filter( 'logdash_manage_columns-' . self::$object_type . '-content_event_meta', [
			$this,
			'event_meta_info'
		], 10, 3 );
	}

	public function added_option( $option, $value ) {

		if ( $this->_is_system() ) {
			return;
		}

		if ( $this->_is_excluded( $option ) ) {
			return;
		}


		if ( $this->_is_transient( $option ) ) {
			return;
		}

		// it is not a user, it's the system
		if ( ! isset( $_POST['action'] ) ) {
			return;
		}

		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::CREATED, EventCodes::SETTING_CREATED, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'fieldName', $option ),
				new EventMeta( 'newValue', $value ),
			] );
	}

	public function before_delete_option( $option ) {

		if ( $this->_is_system() ) {
			return;
		}

		if ( $this->_is_excluded( $option ) ) {
			return;
		}

		if ( $this->_is_transient( $option ) ) {
			return;
		}

		// it is not a user, it's the system
		if ( ! isset( $_POST['action'] ) ) {
			return;
		}

		$option_value = get_option( $option );

		if ( empty( $option_value ) || is_array( $option_value ) || is_object( $option_value ) ) {
			$option_value = serialize( $option_value );
		}

		$this->setting_to_be_deleted = $option_value;
	}

	public function deleted_option( $option ) {

		if ( $this->_is_system() ) {
			return;
		}

		if ( $this->_is_excluded( $option ) ) {
			return;
		}

		if ( $this->_is_transient( $option ) ) {
			return;
		}

		// it is not a user, it's the system
		if ( ! isset( $_POST['action'] ) ) {
			return;
		}

		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::DELETED, EventCodes::SETTING_DELETED, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'fieldName', $option ),
				new EventMeta( 'oldValue', $this->setting_to_be_deleted ),
			] );

	}

	public function updated_option( $option, $old_value, $value ) {

		if ( $this->_is_excluded( $option ) ) {
			return;
		}

		if ( $this->_is_transient( $option ) ) {
			return;
		}

		$current_user = wp_get_current_user();


		// it is not a user, it's the system
		if ( ! isset( $_POST['action'] ) ) {
			return;
		}

		$this
			->event
			->insert( EventTypes::MODIFIED, EventCodes::SETTING_UPDATED, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'fieldName', $option ),
				new EventMeta( 'oldValue', $old_value ),
				new EventMeta( 'newValue', $value ),
			] );
	}

	public function event_meta_info( $output, $event_data, $meta_data ) {

		$message = EventCodes::desc( $event_data['event_code'] );


		$actions = [];
		$details = [];

		$view = new View();

		switch ( $event_data['event_code'] ) {
			case EventCodes::SETTING_CREATED:

				$data = [
					new Label( $meta_data['fieldName'] ),
				];

				$details = [
					new Specification( __( 'New value', LOGDASH_DOMAIN ), $meta_data['newValue'] ),
				];
				break;

			case EventCodes::SETTING_DELETED:

				$data = [
					new Label( $meta_data['fieldName'] ),
				];

				$details = [
					new Specification( __( 'Old value', LOGDASH_DOMAIN ), $meta_data['oldValue'] ),
				];
				break;

			case EventCodes::SETTING_UPDATED:

				$data = [
					new Label( $meta_data['fieldName'] ),
					new Before( $meta_data['oldValue'] ),
					new After( $meta_data['newValue'] ),
				];

				$details = [
					new Specification( __( 'Field name', LOGDASH_DOMAIN ), $meta_data['fieldName'] ),
					new Specification( __( 'Old value', LOGDASH_DOMAIN ), new Before( $meta_data['oldValue'] ) ),
					new Specification( __( 'New value', LOGDASH_DOMAIN ), new After( $meta_data['newValue'] ) ),
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

	private function _is_excluded( $option ): bool {

		if ( in_array( $option, $this->setting_excluded ) ) {
			return true;
		}

		return false;
	}

	private function _is_transient( $option ): bool {

		if ( substr( $option, 0, 10 ) === '_transient' ) {
			return true;
		}

		if ( substr( $option, 0, 15 ) === '_site_transient' ) {
			return true;
		}

		return false;
	}

	private function _is_system(): bool {
		return ! is_user_logged_in();
	}

}