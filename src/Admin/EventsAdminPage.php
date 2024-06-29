<?php

namespace LogDash\Admin;

class EventsAdminPage {

	private static ?EventsAdminPage $instance = null;

	public static function instance(): ?EventsAdminPage {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function init() {
		$this->actions();
	}

	public function actions() {
		add_action( 'admin_menu', [ $this, 'menu_item' ] );
		add_action( 'admin_menu', [ $this, 'submenu_item_replacement' ], 90 );
	}

	public function menu_item() {

		$icon_url = LOGDASH_URL . '/assets/img/icon-logdash-white.svg';

		add_menu_page(
			__( 'LogDash Activity Log Viewer', LOGDASH_DOMAIN ),
			__( 'LogDash', LOGDASH_DOMAIN ),
			'manage_options',
			'logdash_activity_log',
			[ $this, 'display_events_page' ],
			$icon_url,
			3
		);
	}

	public function submenu_item_replacement() {
		global $submenu;

		if ( isset( $submenu['logdash_activity_log'] ) ) {
			$submenu['logdash_activity_log'][0][0] = __( 'Activity Log', LOGDASH_DOMAIN );
		}

	}

	function display_events_page() {
		$arguments = array(
			'label'   => __( 'Users Per Page', 'tally' ),
			'default' => 5,
			'option'  => 'users_per_page'
		);
		add_screen_option( 'per_page', $arguments );

		$user_list_table = new EventsListTable();
		$user_list_table->prepare_items();

		?>
		<div class="wrap">
			<h1><?php echo get_admin_page_title(); ?></h1>
			<div id="nds-wp-list-table-demo">
				<div id="nds-post-body">
					<form method="GET">
						<?php $user_list_table->search_box( __( 'Search' ), 's' ); ?>
						<input type="hidden" name="page" value="<?php echo esc_attr( $_GET['page'] ); ?>">
						<?php $user_list_table->display(); ?>
					</form>
				</div>
			</div>
		</div>
		<?php
	}

}