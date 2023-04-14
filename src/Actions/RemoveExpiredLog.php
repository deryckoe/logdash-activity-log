<?php

namespace LogDash\Actions;

use LogDash\Admin\Settings;

class RemoveExpiredLog {
	private static ?RemoveExpiredLog $instance = null;
	private $wpdb;

	public function __construct() {
		global $wpdb;
		$this->wpdb   = $wpdb;
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
		add_action( 'init', [ $this, 'delete_expired_log' ] );
	}

	public function delete_expired_log() {

		$options = $this->get_options();

		if ( empty( $options['logs_lifespan'] ) ) {
			return;
		}

		$days          = $options['logs_lifespan'];
		$activity_log  = $this->wpdb->prefix . 'logdash_activity_log';
		$activity_meta = $this->wpdb->prefix . 'logdash_activity_meta';

		$this->wpdb->query( "DELETE l FROM $activity_log l
						       LEFT JOIN $activity_meta m
						         ON l.ID = m.event_id
						       WHERE FROM_UNIXTIME(l.created, '%Y-%m-%d') < DATE_SUB(CURRENT_DATE, INTERVAL $days DAY);" );

		$rows_affected = $this->wpdb->rows_affected;

		if ( empty( $rows_affected ) ) {
			return;
		}

		update_option('logdash_deleted_events', [
			'rows' => $rows_affected,
			'date' => time(),
		] );


	}

	private function get_options() {
		return get_option( 'logdash_options' );
	}

}