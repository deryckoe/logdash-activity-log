<?php

namespace LogDash\Actions;

use LogDash\API\DB;

class ResetLog {

	private static ?ResetLog $instance = null;

	private $wpdb;

	private array $tables;

	public function __construct() {
		global $wpdb;
		$this->wpdb   = $wpdb;
		$this->tables = [
			'log'  => DB::log_table(),
			'meta' => DB::meta_table(),
			'ip'   => DB::ip_table(),
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

		if ( ! isset( $_REQUEST['_wpnonce'], $_REQUEST['action'] ) ) {
			$output = [
				'status'  => 'fail',
				'message' => __( 'There is an issue with reset request.', LOGDASH_DOMAIN )
			];
			$this->send_result( $output );
		}

		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'logdash_reset_log' ) ) {
			$output = [
				'status'  => 'error',
				'message' => __( 'You are not authorized to perform this action.', LOGDASH_DOMAIN )
			];
			$this->send_result( $output );
		}

		$site_id = get_current_blog_id();
		$log_table = $this->tables['log'];
		$meta_table = $this->tables['meta'];

		$queue = [
			'meta' => $this->wpdb->prepare( "
				DELETE meta 
			    FROM %i AS meta 
			    LEFT JOIN %i AS log ON meta.event_id = log.ID 
			    WHERE log.site_id = %d;", [$meta_table, $log_table, $site_id] ),
			'log' => $this->wpdb->prepare("DELETE FROM %i WHERE site_id = %d;", [ $log_table, $site_id]),
		];

		foreach ( $queue as $task ) {
			$this->wpdb->query($task);
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
		echo wp_json_encode( $response );
		wp_die();
	}


}