<?php

namespace LogDash\API;

class DB {

	static function prefix(): string {
		global $wpdb;

		$main_site = get_main_site_id();

		return $wpdb->get_blog_prefix( $main_site );
	}

	static function log_table(): string {
		return self::prefix() . 'logdash_activity_log';
	}

	static function meta_table(): string {
		return self::prefix() . 'logdash_activity_meta';
	}

	static function ip_table(): string {
		return self::prefix() . 'logdash_ip_info';
	}

}