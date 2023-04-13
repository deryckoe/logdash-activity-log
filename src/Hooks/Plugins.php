<?php

namespace LogDash\Hooks;

use LogDash\API\EventMeta;
use LogDash\EventCodes;
use LogDash\EventTypes;
use LogDash\Template\Meta\Label;
use LogDash\Template\Meta\Specification;
use LogDash\Template\Meta\View;

class Plugins extends HooksBase {

	private static string $object_type = 'plugin';

	private array $before_delete_plugin;

	private array $old_plugins;

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( 'admin_init', [ $this, 'admin_init' ] );
		add_action( 'activated_plugin', [ $this, 'activated_plugin' ], 10, 2 );
		add_action( 'deactivated_plugin', [ $this, 'deactivated_plugin' ], 10, 2 );
		add_action( 'delete_plugin', [ $this, 'before_delete_plugin' ] );
		add_action( 'deleted_plugin', [ $this, 'deleted_plugin' ], 10, 2 );
		add_action( 'upgrader_process_complete', [ $this, 'upgraded_plugin' ], 10, 2 );
		add_action( 'shutdown', [ $this, 'plugin_actions' ] );
		add_filter( 'logdash_manage_columns-' . self::$object_type . '-content_event_meta', [
			$this,
			'event_meta_info'
		], 10, 3 );
	}

	public function admin_init() {
		$this->old_plugins = get_plugins();
	}


	public function activated_plugin( $plugin, $network_wide ) {

		$current_user = wp_get_current_user();
		$plugin_data  = $this->plugin_data( $plugin );

		$this
			->event
			->insert( EventTypes::ACTIVATED, EventCodes::PLUGIN_ACTIVATED, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'pluginPath', $this->plugin_absolute_path( $plugin ) ),
				new EventMeta( 'pluginName', $plugin_data['Name'] ),
				new EventMeta( 'pluginVersion', $plugin_data['Version'] ),
			] );
	}

	public function deactivated_plugin( $plugin, $network_wide ) {

		$current_user = wp_get_current_user();
		$plugin_data  = $this->plugin_data( $plugin );

		$this
			->event
			->insert( EventTypes::DEACTIVATED, EventCodes::PLUGIN_DEACTIVATED, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'pluginPath', $this->plugin_absolute_path( $plugin ) ),
				new EventMeta( 'pluginName', $plugin_data['Name'] ),
				new EventMeta( 'pluginVersion', $plugin_data['Version'] ),
			] );
	}

	public function before_delete_plugin( $plugin ) {
		$plugin_data = $this->plugin_data( $plugin );

		$this->before_delete_plugin = [
			'pluginPath'    => $this->plugin_absolute_path( $plugin ),
			'pluginName'    => $plugin_data['Name'],
			'pluginVersion' => $plugin_data['Version'],
		];
	}

	public function deleted_plugin( $plugin_file, $deleted ) {

		if ( false === $deleted ) {
			return;
		}

		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::DELETED, EventCodes::PLUGIN_UNINSTALLED, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'pluginPath', $this->before_delete_plugin['pluginPath'] ),
				new EventMeta( 'pluginName', $this->before_delete_plugin['pluginName'] ),
				new EventMeta( 'pluginVersion', $this->before_delete_plugin['pluginVersion'] ),
			] );
	}

	public function upgraded_plugin( $upgrader, $options ) {

		$current_user = wp_get_current_user();

		if ( $options['action'] === 'update' && $options['type'] === 'plugin' ) {
			foreach ( $options['plugins'] as $plugin ) {

				$plugin_data = $this->plugin_data( $plugin );

				$this
					->event
					->insert( EventTypes::UPGRADED, EventCodes::PLUGIN_UPGRADED, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
					->attachMany( [
						new EventMeta( 'pluginPath', $this->plugin_absolute_path( $plugin ) ),
						new EventMeta( 'pluginName', $plugin_data['Name'] ),
						new EventMeta( 'pluginVersion', $plugin_data['Version'] ),
					] );
			}
		}
	}

	public function plugin_actions() {

		$input_get  = filter_input_array( INPUT_GET ) ?? [];
		$input_post = filter_input_array( INPUT_POST ) ?? [];
		$requests   = array_merge( $input_get, $input_post );


		if ( ! isset( $requests['action'] ) ) {
			return;
		}

		$action       = $requests['action'];
		$current_user = wp_get_current_user();

		if ( in_array( $action, [ 'install-plugin', 'upload-plugin' ] ) ) {

			$diff_plugins = array_diff( array_keys( get_plugins() ), array_keys( $this->old_plugins ) );

			foreach ( $diff_plugins as $slug ) {

				$plugin_data = $this->plugin_data( $slug );

				$this
					->event
					->insert( EventTypes::INSTALLED, EventCodes::PLUGIN_INSTALLED, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
					->attachMany( [
						new EventMeta( 'pluginPath', $this->plugin_absolute_path( $slug ) ),
						new EventMeta( 'pluginName', $plugin_data['Name'] ),
						new EventMeta( 'pluginVersion', $plugin_data['Version'] ),
					] );
			}
		}

	}

	public function event_meta_info( $output, $event_data, $meta_data ) {

		$user = ( ! empty( $event_data['object_id'] ) )
			? get_user_by( 'ID', $event_data['object_id'] )
			: null;


		$message = EventCodes::desc( $event_data['event_code'] );

		$path_info = pathinfo( $meta_data['pluginPath'] );
		$slug      = basename( $path_info['dirname'] );
		$details   = 'https://wordpress.org/plugins/' . $slug;

		$actions = [
			[ 'href' => $details, 'target' => '_blank', 'label' => __( 'Details' ) ],
			[ 'href' => $details . '#developers', 'target' => '_blank', 'label' => __( 'Changelog' ) ],
		];

		$details = [
			new Specification( __( 'Name', LOGDASH_DOMAIN ), $meta_data['pluginName'] ),
			new Specification( __( 'Version', LOGDASH_DOMAIN ), $meta_data['pluginVersion'] ),
			new Specification( __( 'Path', LOGDASH_DOMAIN ), $meta_data['pluginPath'] ),
		];

		$view = new View();

		switch ( $event_data['event_code'] ) {
			case EventCodes::PLUGIN_ACTIVATED:
			case EventCodes::PLUGIN_DEACTIVATED:
			case EventCodes::PLUGIN_UPGRADED:

				$data = [
					new Label( $meta_data['pluginName'] ),
					new Label( $meta_data['pluginVersion'] ),
				];

				break;

			case EventCodes::PLUGIN_INSTALLED:
			case EventCodes::PLUGIN_UNINSTALLED:

				$data = [
					new Label( $meta_data['pluginName'] ),
					new Label( $meta_data['pluginVersion'] ),
					new Label( $meta_data['pluginPath'] ),
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

	private function plugin_absolute_path( $plugin ): string {
		return path_join( WP_PLUGIN_DIR, $plugin );
	}

	private function plugin_data( $plugin ): array {
		$path = $this->plugin_absolute_path( $plugin );

		return get_plugin_data( $path );
	}

}