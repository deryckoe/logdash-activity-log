<?php

namespace LogDash\Hooks;

use LogDash\API\EventMeta;
use LogDash\EventCodes;
use LogDash\EventTypes;
use LogDash\Template\Meta\Label;
use LogDash\Template\Meta\Before;
use LogDash\Template\Meta\After;
use LogDash\Template\Meta\Specification;
use LogDash\Template\Meta\View;

class Core extends HooksBase {

	private static string $object_type = 'core';

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( '_core_updated_successfully', [ $this, 'core_updated_successfully' ] );
		add_filter( 'logdash_manage_columns-' . self::$object_type . '-content_event_meta', [
			$this,
			'event_meta_info'
		], 10, 3 );
	}

	public function core_updated_successfully( $version ) {

		$old_version = $GLOBALS['wp_version'] ?? __( 'Unknown', LOGDASH_DOMAIN );
		$event_code  = EventCodes::CORE_UPGRADED;

		if ( version_compare( $old_version, $version, 'eq' ) ) {
			$event_code = EventCodes::CORE_REINSTALLED;
		}

		if ( version_compare( $old_version, $version, '>' ) ) {
			$event_code = EventCodes::CORE_DOWNGRADED;
		}

		$current_user = wp_get_current_user();

		$this
			->event
			->insert( EventTypes::UPGRADED, $event_code, self::$object_type, self::$object_type, 0, $current_user->ID, $current_user->roles[0] )
			->attachMany( [
				new EventMeta( 'oldVersion', $old_version ),
				new EventMeta( 'newVersion', $version )
			] );
	}

	public function event_meta_info( $output, $event_data, $meta_data ) {

		$message = EventCodes::desc( $event_data['event_code'] );

		$version = str_replace( '.', '-', $meta_data['newVersion'] );
		$release = 'https://wordpress.org/documentation/wordpress-version/version-' . $version;
		$actions = [
			[ 'href' => get_admin_url() . 'about.php', 'target' => '_self', 'label' => __( 'About', LOGDASH_DOMAIN ) ],
			[ 'href' => $release, 'target' => '_self', 'label' => __( 'Release notes', LOGDASH_DOMAIN ) ],
		];

		switch ( $event_data['event_code'] ) {
			case EventCodes::CORE_REINSTALLED:
				$data    = [
					new Label( $meta_data['newVersion'] ),
				];
				$details = [
					new Specification( __( 'Version', LOGDASH_DOMAIN ), $meta_data['newVersion'] ),
				];
				break;

			default:
				$data    = [
					new Before( $meta_data['oldVersion'] ),
					new After( $meta_data['newVersion'] ),
				];
				$details = [
					new Specification( __( 'Old version', LOGDASH_DOMAIN ), $meta_data['oldVersion'] ),
					new Specification( __( 'New version', LOGDASH_DOMAIN ), $meta_data['newVersion'] ),
				];
		}

		$details = array_merge( $details, [
			new Specification( __( 'User Agent', LOGDASH_DOMAIN ), $event_data['user_agent'] ),
		] );

		$view = new View();
		$view
			->message( $message, $data )
			->actions( $actions )
			->details( $details );

		return $view->get();
	}

}