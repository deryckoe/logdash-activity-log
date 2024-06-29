<?php

namespace LogDash\API;

class Event {
	private static $_instance = null;
	private int $event_id;

	public function __construct() {
	}

	public function insert( $event_type, $event_code, $object_type, $object_subtype = '', $object_id = '', $user_id = '', $user_caps = '' ) {


		global $wpdb;

		$site_id   = get_current_blog_id();
		$user_ip   = $this->user_ip();
		$log_table = DB::log_table();


		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$user_agent = 'WP-CLI/' . WP_CLI_VERSION . ' ' . php_uname( 's' ) . ' ' . php_uname( 'v' );
		} else {
			$user_agent = sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ) ?? '';
		}

		$wpdb->insert( $log_table,
			[
				'site_id'        => $site_id,
				'event_type'     => $event_type,
				'event_code'     => $event_code ?? 0,
				'object_type'    => $object_type,
				'object_subtype' => $object_subtype,
				'object_id'      => $object_id,
				'user_id'        => $user_id,
				'user_caps'      => $user_caps,
				'user_ip'        => $user_ip,
				'user_agent'     => $user_agent,
				'created'        => time(),
			], [ '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d' ]
		);

		$this->event_id = $wpdb->insert_id ?? null;

		return $this;

	}

	public function attachMany( array $events ) {

		global $wpdb;

		$meta_table = DB::meta_table();
		$query_ids  = [];

		foreach ( $events as $event ) {
			if ( ! $event instanceof EventMeta ) {
				trigger_error( 'Invalid type of meta', E_USER_WARNING );
				continue;
			}

			if ( $event->name === '' ) {
				trigger_error( 'Name is required', E_USER_WARNING );
				continue;
			}

			if ( is_object( $event->value ) || is_array( $event->value ) ) {
				$value = serialize( $event->value );
			} else {
				$value = $event->value;
			}

			$id = $wpdb->insert( $meta_table,
				[
					'event_id' => $this->event_id,
					'name'     => $event->name,
					'value'    => $value,
				], [ '%d', '%s', '%s' ]
			);

			if ( $id ) {
				$query_ids[] = $id;
			}

		}

		return $query_ids;

	}

	public function attach( $name, $value ) {

		global $wpdb;

		$meta_table = DB::meta_table();

		if ( is_object( $value ) || is_array( $value ) ) {
			$value = wp_json_encode( $value );
		}

		$wpdb->insert( $meta_table,
			[
				'event_id' => $this->event_id,
				'name'     => $name,
				'value'    => $value,
			], [ '%d', '%s', '%s' ]
		);

		return $this;

	}

	public function is_last_event( int $event_code, int $object_id = 0 ): bool {
		global $wpdb;

		$log_table = DB::log_table();

		$result = $wpdb->get_results(
			$wpdb->prepare( "SELECT ID, event_code, object_id FROM %i ORDER BY created DESC LIMIT 1", $log_table ),
			ARRAY_A
		);

		return ( (int) $result[0]['event_code'] === $event_code && (int) $result[0]['object_id'] === $object_id );
	}

	private function user_ip(): string {

		$ip = '';

		if ( defined( 'LOGDASH_TEST_IPS' ) && is_array( LOGDASH_TEST_IPS ) ) {
			$ip = LOGDASH_TEST_IPS[ array_rand( LOGDASH_TEST_IPS ) ];
		}

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( $_SERVER['HTTP_X_FORWARDED_FOR'] );
		} else {
			$ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
		}

		$pattern = '/\b(?:\d{1,3}\.){3}\d{1,3}\b/';
		preg_match( $pattern, $ip, $matches );

		if ( ! empty( $matches[0] ) ) {
			$ip = $matches[0];
		}

		return $ip;
	}

	public static function instance(): ?Event {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

}