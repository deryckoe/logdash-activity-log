<?php

namespace LogDash\API;

class RestEndpoints {

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( 'rest_api_init', [ $this, 'registerCanLoginField' ] );
	}


	function registerCanLoginField() {
		register_rest_route(
			'logdash/v1',
			'ip/(?P<ip>\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})',
			[
				'methods' => \WP_REST_Server::READABLE,
				'permission_callback' => function( $request ) {
					return true;
					return current_user_can('manage_options');
				},
				'callback' => function( \WP_REST_Request $data ) {

					global $wpdb;

					$table = $wpdb->prefix . 'logdash_ip_info';
					$ip = $data->get_param('ip') ?? '0';

					$query = $wpdb->prepare("SELECT * FROM $table WHERE ip = '%s';", [ $ip ] );
					$result = $wpdb->get_results( $query );

					if ( ! empty( $result[0]->info ) ) {
						return json_decode($result[0]->info);
					}

				}
			]
		);
	}

}