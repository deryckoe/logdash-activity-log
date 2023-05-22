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

					$ip_table = DB::ip_table();
					$ip = $data->get_param('ip') ?? '0';

					$query = $wpdb->prepare("SELECT * FROM $ip_table WHERE ip = %s;", [ $ip ] );
					$result = $wpdb->get_results( $query );

					if ( empty( $result ) ) {
						echo json_encode( [
							'code' => '400',
							'data' => [
								'message' => 'No information available for ' . $ip
							],
						] );
						exit;
					}

					if ( ! empty( $result[0] ) ) {
						echo json_encode([
							'code' => '200',
							'data' => $result[0]
						]);
						exit;
					}

				}
			]
		);
	}

}