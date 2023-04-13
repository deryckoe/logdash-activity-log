<?php

namespace LogDash\Hooks;

use LogDash\API\EventMeta;
use LogDash\EventCodes;
use LogDash\EventTypes;
use LogDash\Template\Meta\Label;
use LogDash\Template\Meta\Specification;
use LogDash\Template\Meta\View;

class Files extends HooksBase {

	private static string $object_type = 'file';

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( 'add_attachment', array( $this, 'event_file_uploaded' ) );
		add_action( 'delete_attachment', array( $this, 'event_file_uploaded_deleted' ) );
		add_action( 'admin_init', array( $this, 'extra_file_hooks' ) );

		add_filter( 'logdash_manage_columns-' . self::$object_type . '-content_event_meta', [
			$this,
			'event_meta_info'
		], 10, 3 );
	}

	public function event_file_uploaded( $attachment_id ) {

		$request = filter_input_array( INPUT_POST );

		$action = isset( $request['action'] ) ? $request['action'] : '';
		if ( 'upload-theme' !== $action && 'upload-plugin' !== $action ) {

			$file = get_attached_file( $attachment_id );

			$current_user = wp_get_current_user();

			$this
				->event
				->insert( EventTypes::UPLOADED, EventCodes::FILE_UPLOADED, self::$object_type, self::$object_type, $attachment_id, $current_user->ID, $current_user->roles[0] )
				->attachMany( [
					new EventMeta( 'fileName', basename( $file ) ),
					new EventMeta( 'filePath', dirname( $file ) ),
				] );
		}
	}

	/**
	 * Deleted file from uploads directory.
	 *
	 * @param integer $attachment_id - Attachment ID.
	 */
	public function event_file_uploaded_deleted( $attachment_id ) {

		$file = get_attached_file( $attachment_id );

		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::DELETED, EventCodes::FILE_DELETED, self::$object_type, self::$object_type, $attachment_id, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'fileName', basename( $file ) ),
				new EventMeta( 'filePath', dirname( $file ) ),
			] );
	}

	public function extra_file_hooks() {
		$nonce   = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : false;
		$file    = isset( $_POST['file'] ) ? sanitize_text_field( wp_unslash( $_POST['file'] ) ) : false;
		$action  = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : false;
		$referer = isset( $_POST['_wp_http_referer'] ) ? sanitize_text_field( wp_unslash( $_POST['_wp_http_referer'] ) ) : false;
		$referer = remove_query_arg( array( 'file', 'theme', 'plugin' ), $referer );
		$referer = basename( $referer, '.php' );

		$current_user = wp_get_current_user();

		if ( 'edit-theme-plugin-file' === $action ) {
			if ( 'plugin-editor' === $referer && wp_verify_nonce( $nonce, 'edit-plugin_' . $file ) ) {
				$plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : false;

				$plugin_path = path_join( WP_PLUGIN_DIR, $plugin );
				$plugin_info = get_plugin_data( $plugin_path );
				$plugin_name = $plugin_info['Name'];

				$this
					->event
					->insert( EventTypes::MODIFIED, EventCodes::FILE_UPDATED_PLUGIN, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
					->attachMany( [
						new EventMeta( 'pluginName', $plugin_name ),
						new EventMeta( 'pluginFile', $file ),
						new EventMeta( 'pluginPath', path_join( WP_PLUGIN_DIR, $file ) ),
					] );

			} elseif ( 'theme-editor' === $referer ) {
				$stylesheet = isset( $_POST['theme'] ) ? sanitize_text_field( wp_unslash( $_POST['theme'] ) ) : false;

				if ( ! wp_verify_nonce( $nonce, 'edit-theme_' . $stylesheet . '_' . $file ) ) {
					return;
				}

				$theme_info = wp_get_theme( $stylesheet );

				$this
					->event
					->insert( EventTypes::MODIFIED, EventCodes::FILE_UPDATED_THEME, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
					->attachMany( [
						new EventMeta( 'themeName', $theme_info->get( 'Name' ) ),
						new EventMeta( 'themeFile', $file ),
						new EventMeta( 'themePath', $theme_info->get_file_path( $stylesheet ) ),
					] );
			}
		}
	}


	public function event_meta_info( $output, $event_data, $meta_data ) {

		$message = EventCodes::desc( $event_data['event_code'] );
		$actions = [];
		$details = [];

		$view = new View();

		switch ( $event_data['event_code'] ) {
			case EventCodes::FILE_UPLOADED:
			case EventCodes::FILE_DELETED:

				$data = [
					new Label( $meta_data['fileName'] ),
					new Label( $meta_data['filePath'] ),
				];

				if ( EventCodes::equal( $event_data['event_code'], EventCodes::FILE_UPLOADED ) ) {
					$view_link = get_permalink( $event_data['object_id'] );
					$edit_link = get_edit_post_link( $event_data['object_id'] );

					$actions = [
						[ 'href' => $view_link, 'target' => '_self', 'label' => __( 'View', LOGDASH_DOMAIN ) ],
						[ 'href' => $edit_link, 'target' => '_self', 'label' => __( 'Edit', LOGDASH_DOMAIN ) ],
					];
				}

				$details = [
					new Specification( __( 'File name', LOGDASH_DOMAIN ), new Label( $meta_data['fileName'] ) ),
					new Specification( __( 'File path', LOGDASH_DOMAIN ), new Label( $meta_data['filePath'] ) ),
				];

				break;

			case EventCodes::FILE_UPDATED_PLUGIN:

				$data = [
					new Label( basename( $meta_data['pluginFile'] ) )
				];

				$details = [
					new Specification( __( 'Plugin name' ), new Label( $meta_data['pluginName'] ) ),
					new Specification( __( 'Plugin file' ), new Label( $meta_data['pluginFile'] ) ),
					new Specification( __( 'Plugin path' ), new Label( $meta_data['pluginPath'] ) ),
				];

				break;

			case EventCodes::FILE_UPDATED_THEME:

				$data = [
					new Label( $meta_data['themeFile'] )
				];

				$details = [
					new Specification( __( 'Theme name' ), new Label( $meta_data['themeName'] ) ),
					new Specification( __( 'Theme file' ), new Label( $meta_data['themeFile'] ) ),
					new Specification( __( 'Theme path' ), new Label( $meta_data['themePath'] ) ),
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
}