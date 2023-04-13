<?php

namespace LogDash\Actions;

use LogDash\Admin\Settings;

class ResetLog {

	private static ?ResetLog $instance = null;

	private $wpdb;

	private array $tables;

	public function __construct() {
		global $wpdb;
		$this->wpdb   = $wpdb;
		$this->tables = [
			'logdash_activity_log',
			'logdash_activity_meta',
		];
	}

	public static function instance(): ?ResetLog {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( 'wp_ajax_logdash_reset_log', [ $this, 'logdash_reset_log_action' ] );
	}

	public function logdash_reset_log_action() {


		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'logdash_reset_log' ) ) {
			$output = [
				'status'  => 'error',
				'message' => __( 'You are not authorized to perform this action.', LOGDASH_DOMAIN )
			];
			$this->send_result( $output );
		}

		foreach ( $this->tables as $table ) {
			$table_name = $this->wpdb->prefix . $table;
			$this->wpdb->query( "TRUNCATE TABLE $table_name" );
			if ( '' !== $this->wpdb->last_error ) {
				$output = [
					'status'  => 'error',
					'message' => sprintf( __( 'Reset failed: (%s)', LOGDASH_DOMAIN ), $this->wpdb->last_error )
				];
				$this->send_result( $output );
			}
		}

		$output = [
			'status'  => 'success',
			'message' => __( 'All events were deleted.', LOGDASH_DOMAIN ),
		];

		$this->send_result( $output );
	}

	private function send_result( $response ) {
		echo json_encode( $response );
		wp_die();
	}


}