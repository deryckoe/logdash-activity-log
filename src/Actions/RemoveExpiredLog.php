<?php

namespace LogDash\Actions;

use LogDash\Admin\Settings;
use LogDash\API\DB;

class RemoveExpiredLog {
	private static ?RemoveExpiredLog $instance = null;
	private $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	public static function instance(): ?RemoveExpiredLog {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function init() {
		$this->actions();
	}

	public function actions() {

//		add_action( 'init', [ $this, 'delete_expired_log' ] );
		add_action( 'init', [ $this, 'register_expired_log_job' ] );
		add_action( 'delete_expired_log', [ $this, 'delete_expired_log' ] );
	}

	public function register_expired_log_job() {
		if ( ! wp_next_scheduled( 'delete_expired_log' ) ) {
			wp_schedule_single_event( time(), 'delete_expired_log' );
		}
	}

	public function delete_expired_log() {
		$options = $this->get_options();

		if ( empty( $options['logs_lifespan'] ) ) {
			return;
		}

		$days          = $options['logs_lifespan'];
		$activity_log  = DB::log_table();
		$activity_meta = DB::meta_table();
		$site_id       = get_current_blog_id();

		$cutoff_date = strtotime( '-' . $days . ' days' );

		$start_time = microtime( true );

		$sql_log = "
        DELETE FROM %i
        WHERE created < %d AND site_id = %d";
		$this->wpdb->query($this->wpdb->prepare($sql_log, $activity_log, $cutoff_date, $site_id));

		$sql_meta = "
        DELETE FROM %i
        WHERE event_id NOT IN (
            SELECT ID FROM %i
        )";
		$this->wpdb->query($this->wpdb->prepare($sql_meta, $activity_meta, $activity_log));

		$end_time                = microtime( true );
		$execution_time          = $end_time - $start_time;
		$execution_time_readable = number_format( $execution_time, 3 ) . ' ' . __('seconds', LOGDASH_DOMAIN );

		$rows_affected = $this->wpdb->rows_affected;

		if ( empty( $rows_affected ) ) {
			return;
		}

		update_option( 'logdash_deleted_events', [
			'rows'           => $rows_affected,
			'date'           => time(),
			'execution_time' => $execution_time_readable
		] );


	}

	private function get_options() {
		return get_option( 'logdash_options' );
	}

}