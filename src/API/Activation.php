<?php

namespace LogDash\API;

class Activation {
	private static ?Activation $instance = null;

	public static function instance(): ?Activation {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function init() {
		$this->actions();
	}

	public function actions(  ) {
		register_activation_hook( LOGDASH_PLUGIN_FILE, [ $this, 'create_tables'] );
	}

	public function create_tables() {

		global $wpdb;
		$main_site_id = get_main_site_id();
		$main_prefix = $wpdb->get_blog_prefix($main_site_id);

		$tables[] = "CREATE TABLE `{$main_prefix}logdash_activity_log` (
				`ID` bigint NOT NULL AUTO_INCREMENT,
				`site_id` bigint NOT NULL,
				`event_type` varchar(255) NOT NULL,
				`event_code` int NOT NULL DEFAULT '0',
				`object_type` varchar(255) NOT NULL,
				`object_subtype` varchar(255) NOT NULL,
				`object_id` bigint DEFAULT NULL,
				`user_id` bigint DEFAULT NULL,
				`user_caps` varchar(70) DEFAULT '',
				`user_ip` varchar(55) NOT NULL DEFAULT '127.0.0.1',
				`user_agent` longtext,
				`created` int NOT NULL DEFAULT '0',
				PRIMARY KEY (`ID`)
				) CHARSET={$wpdb->charset} COLLATE={$wpdb->collate};";

		$tables[] = "CREATE TABLE `{$main_prefix}logdash_activity_meta` (
				`ID` bigint NOT NULL AUTO_INCREMENT,
				`event_id` bigint NOT NULL DEFAULT '0',
				`name` varchar(255) NOT NULL,
				`value` longtext,
				  PRIMARY KEY (`ID`)
				) CHARSET={$wpdb->charset} COLLATE={$wpdb->collate};";

		$tables[] = "CREATE TABLE `{$main_prefix}logdash_ip_info` (
  				`ID` int NOT NULL AUTO_INCREMENT,
  				`ip` varchar(255) DEFAULT NULL,
				`city` text,
				`country_name` text,
				`country_code` text,
				`lat` text,
				`lon` text,
				`isp` text,
  					PRIMARY KEY (`ID`)
				) CHARSET={$wpdb->charset} COLLATE={$wpdb->collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		foreach ( $tables as $table ) {
			dbDelta( $table );

			if ( $wpdb->result === false ) {
				wp_die(
					__( sprintf( 'Plugin activation failed (%s)', $wpdb->last_error ), LOGDASH_DOMAIN )
				);
			}
		}

	}

}