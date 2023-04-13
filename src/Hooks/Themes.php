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

class Themes extends HooksBase {

	private static string $object_type = 'theme';

	private array $before_delete_theme;

	private array $old_themes;

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'switch_theme', [ $this, 'switch_theme' ], 10, 3 );
		add_action( 'delete_theme', [ $this, 'before_delete_theme' ], 10, 2 );
		add_action( 'deleted_theme', [ $this, 'deleted_plugin' ], 10, 2 );
		add_action( 'upgrader_process_complete', [ $this, 'upgraded_theme' ], 10, 2 );
		add_action( 'shutdown', [ $this, 'theme_actions' ] );
		add_filter( 'logdash_manage_columns-' . self::$object_type . '-content_event_meta', [
			$this,
			'event_meta_info'
		], 10, 3 );
//		add_filter( 'logdash_event-' . self::$object_type . '-row_actions', [ $this, 'event_row_actions' ], 10, 3 );
	}

	public function admin_init() {
		$this->old_themes = wp_get_themes();
	}

	public function switch_theme( $name, \WP_Theme $new_theme, \WP_Theme $old_theme ) {

		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::ACTIVATED, EventCodes::THEME_ACTIVATED, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'themePath', $new_theme->get_file_path() ),
				new EventMeta( 'themeName', $new_theme->get( 'Name' ) ),
				new EventMeta( 'themeVersion', $new_theme->get( 'Version' ) ),
			] );

	}

	public function before_delete_theme( $slug ) {
		$theme = wp_get_theme( $slug );

		$this->before_delete_theme = [
			'themePath'    => $theme->get_file_path(),
			'themeName'    => $theme->get( 'Name' ),
			'themeVersion' => $theme->get( 'Version' ),
		];
	}

	public function deleted_plugin( $slug, $deleted ) {

		if ( false === $deleted ) {
			return;
		}

		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::DELETED, EventCodes::THEME_UNINSTALLED, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'themePath', $this->before_delete_theme['themePath'] ),
				new EventMeta( 'themeName', $this->before_delete_theme['themeName'] ),
				new EventMeta( 'themeVersion', $this->before_delete_theme['themeVersion'] ),
			] );
	}

	public function upgraded_theme( $upgrader, $options ) {

		$current_user = wp_get_current_user();

		if ( $options['action'] === 'update' && $options['type'] === 'theme' ) {
			foreach ( $options['themes'] as $theme_slug ) {

				$theme = wp_get_theme( $theme_slug );

				$this
					->event
					->insert( EventTypes::UPGRADED, EventCodes::THEME_UPGRADED, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
					->attachMany( [
						new EventMeta( 'themePath', $theme->get_file_path() ),
						new EventMeta( 'themeName', $theme->get( 'Name' ) ),
						new EventMeta( 'themeVersion', $theme->get( 'Version' ) ),
					] );
			}
		}
	}

	public function theme_actions() {

		$input_get  = filter_input_array( INPUT_GET ) ?? [];
		$input_post = filter_input_array( INPUT_POST ) ?? [];
		$requests   = array_merge( $input_get, $input_post );


		if ( ! isset( $requests['action'] ) ) {
			return;
		}

		$action       = $requests['action'];
		$current_user = wp_get_current_user();

		// update-theme
		// 'do-theme-upgrade', 'update-selected-themes'

		if ( in_array( $action, [ 'install-theme', 'upload-theme' ] ) ) {

			$diff_themes = array_diff( array_keys( wp_get_themes() ), array_keys( $this->old_themes ) );

			foreach ( $diff_themes as $slug ) {

				$theme = wp_get_theme( $slug );

				$this
					->event
					->insert( EventTypes::INSTALLED, EventCodes::THEME_INSTALLED, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
					->attachMany( [
						new EventMeta( 'themePath', $theme->get_file_path() ),
						new EventMeta( 'themeName', $theme->get( 'Name' ) ),
						new EventMeta( 'themeVersion', $theme->get( 'Version' ) ),
					] );
			}
		}

	}

	public function event_meta_info( $output, $event_data, $meta_data ) {

		$message = EventCodes::desc( $event_data['event_code'] );

		$slug    = basename( $meta_data['themePath'] );
		$details = 'https://wordpress.org/themes/' . $slug;
		$actions = [ [ 'href' => $details, 'target' => '_blank', 'label' => __( 'Details', LOGDASH_DOMAIN ) ] ];

		$details = [
			new Specification( __( 'Path', LOGDASH_DOMAIN ), $meta_data['themePath'] ),
			new Specification( __( 'Name', LOGDASH_DOMAIN ), $meta_data['themeName'] ),
			new Specification( __( 'Version', LOGDASH_DOMAIN ), $meta_data['themeVersion'] ),
		];

		$view = new View();

		switch ( $event_data['event_code'] ) {

			case EventCodes::THEME_UPGRADED :
			case EventCodes::THEME_INSTALLED:
			case EventCodes::THEME_UNINSTALLED:
			case EventCodes::THEME_ACTIVATED:
			case EventCodes::THEME_DEACTIVATED:

				$data = [
					new Label( $meta_data['themeName'] ),
					new Label( $meta_data['themeVersion'] ),
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

	public function event_row_actions( $actions, $event_data, $meta_data ): array {

		$slug    = basename( $meta_data['themePath'] );
		$details = 'https://wordpress.org/themes/' . $slug;

		return array_merge( $actions, [
			sprintf( '<a href="%s" target="_blank">%s</a>', $details, __( 'Details', LOGDASH_DOMAIN ) )
		] );
	}

}