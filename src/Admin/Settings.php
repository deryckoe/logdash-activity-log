<?php

namespace LogDash\Admin;

class Settings {

	private static ?Settings $instance = null;

	public static function instance(): ?Settings {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( 'admin_menu', [ $this, 'sub_menu' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'init', [ $this, 'set_defaults' ] );
	}

	public function sub_menu() {
		add_submenu_page(
			'logdash_activity_log',
			__( 'LogDash Settings', LOGDASH_DOMAIN ),
			__( 'Settings', LOGDASH_DOMAIN ),
			'manage_options',
			'logdash_settings',
			[ $this, 'settings_page' ]
		);
	}

	public function settings_page() {
		settings_errors( 'logdash_messages' );

		include LOGDASH_TEMPLATES . '/admin/settings.template.php';
	}

	public function set_defaults() {

		$options = ! empty( $this->get_options() ) ? $this->get_options() : [];

		if ( ! isset( $_POST['action'] ) && ! isset( $options['logs_lifespan'] ) ) {
			$options['logs_lifespan'] = 60;
			$this->set_options( $options );
		}

	}

	public function register_settings() {

		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// add error/update messages

		// check if the user have submitted the settings
		// WordPress will add the "settings-updated" $_GET parameter to the url
		if ( isset( $_GET['settings-updated'] ) ) {
			// add settings saved message with the class of "updated"
			add_settings_error( 'logdash_messages', 'logdash_message', __( 'Settings Saved', LOGDASH_DOMAIN ), 'updated' );
		}

		register_setting( 'logdash', 'logdash_options' );
		add_settings_section(
			'logdash_section_developers',
			__( 'Basic Settings', 'wporg' ),
			__return_empty_string(),
			'logdash'
		);

		$deletion_stats = get_option( 'logdash_deleted_events' );

		if ( ! empty( $deletion_stats['rows'] ) && ! empty( $deletion_stats['date'] ) ) {

			$format         = 'Y-m-d H:i:s';
			$display_format = 'M d, Y h:mA';
			$gmt_date       = gmdate( $format, (int) $deletion_stats['date'] );
			$date           = get_date_from_gmt( $gmt_date, $display_format );

			$extra_description = ' ' . sprintf( __( '%s records were deleted on %s', LOGDASH_DOMAIN ), $deletion_stats['rows'], $date );

			if ( ! empty( $deletion_stats['execution_time'] ) ) {
				$extra_description .= ' ' . sprintf( __( 'and the execution time was %s', LOGDASH_DOMAIN ), $deletion_stats['execution_time'] );
			}
		} else {
			$extra_description = '';
		}

		add_settings_field(
			'logs_lifespan',
			__( 'Store logs for', LOGDASH_DOMAIN ),
			[ $this, 'input_callback' ],
			'logdash',
			'logdash_section_developers',
			[
				'input_type'        => InputTypes::NUMBER,
				'input_min'         => '1',
				'input_name'        => 'logs_lifespan',
				'input_class'       => 'small-text',
				'label_for'         => 'logs_lifespan',
				'label_suffix'      => __( 'days', LOGDASH_DOMAIN ),
				'label_description' => __( 'Specify the length of time you want to retain the activity log. If left blank, the activity log will be kept indefinitely, although this is not recommended.', LOGDASH_DOMAIN ) . $extra_description
			]
		);


		$admin_url  = admin_url( 'admin-ajax.php' );
		$action_url = add_query_arg( [ 'action' => 'logdash_reset_log' ], $admin_url );
		$ajax_url   = wp_nonce_url( $action_url, 'logdash_reset_log' );

		add_settings_field(
			'reset_log',
			__( 'Delete Log Activities', LOGDASH_DOMAIN ),
			function ( $attr ) {
				echo '<a href="' . esc_url( $attr['ajax_url'] ) . '" id="' . esc_attr( $attr['id'] ) . '" data-confirm-message="' . esc_attr( $attr['confirm_message'] ) . '">Reset Database</a><p class="description">' . esc_attr( $attr['label_description'] ) . '</p><div id="logdash_message" class="notice" style="display: none;"><p>...</p></div>';
			},
			'logdash',
			'logdash_section_developers',
			[
				'id'                => 'logdash_reset_log',
				'ajax_url'          => $ajax_url,
				'label_description' => __( 'Warning: Clicking this will delete all events from the database. Tables and structure will be maintained.', LOGDASH_DOMAIN ),
				'confirm_message'   => __( 'All data will be deleted. Are you sure do you want to continue?', LOGDASH_DOMAIN )
			]
		);

	}

	public function input_callback( $args ) {
		if ( empty( $args['input_type'] ) ) {
			return '';
		}

		$options = $this->get_options();

		$input_type        = esc_attr( $args['input_type'] );
		$input_class       = isset( $args['input_class'] ) ? $args['input_class'] : 'regular-text';
		$input_name        = isset( $args['input_name'] ) ? $args['input_name'] : '';
		$label_suffix      = isset( $args['label_suffix'] ) ? $args['label_suffix'] : '';
		$label_description = $args['label_description'] ?? '';

		$input_value = $options[ $input_name ] ?? '';
		$input_field = '';

		switch ( $args['input_type'] ) {
			case InputTypes::SELECT:

				$input_options = '';
				foreach ( $args['input_options'] as $key => $value ) {
					$selected      = selected( esc_attr( $key ), esc_attr( $input_value ), false );
					$input_options .= '<option value="' . esc_attr( $key ) . '" ' . $selected . '>' . esc_html( $value ) . '</option>';
				};
				$input_field = '<select class="' . esc_attr( $input_class ) . '" name="logdash_options[' . esc_attr( $input_name ) . ']">' . esc_html( $input_options ) . '</select>';
				break;

			case InputTypes::TEXTAREA:
				$input_rows  = isset( $args['input_rows'] ) ? $args['input_rows'] : '10';
				$input_cols  = isset( $args['input_cols'] ) ? $args['input_cols'] : '50';
				$input_field = '<textarea class="' . esc_attr( $input_class ) . '" name="logdash_options[' . esc_attr( $input_name ) . ']" rows="' . esc_attr( $input_rows ) . '" cols="' . esc_attr( $input_cols ) . '">'
				               . esc_html( $input_value )
				               . '</textarea>';
				break;


			default:

				if ( isset( $args['input_min'] ) ) {
					$min = 'min="' . esc_attr( $args['input_min'] ) . '"';
				} else {
					$min = '';
				}

				$input_field = '<input class="' . esc_attr( $input_class ) . '" name="logdash_options[' . esc_attr( $input_name ) . ']" type="' . esc_attr( $input_type ) . '" value="' . esc_attr( $input_value ) . '" ' . $min . ' />';
				break;
		}

		echo $input_field . ' ' . esc_textarea( $label_suffix );

		if ( $label_description ) {
			echo '<p class="description">' . esc_textarea( $label_description ) . '</p>';
		}

		return true;

	}

	private function get_options() {
		return get_option( 'logdash_options' );
	}

	private function set_options( $value ) {
		update_option( 'logdash_options', $value );
	}

}