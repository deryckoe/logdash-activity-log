<?php


// @see https://supporthost.com/wp-list-table-tutorial/

use LogDash\EventTypes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}


/**
 * Class for displaying registered WordPress Users
 * in a WordPress-like Admin Table with row actions to
 * perform user meta opeations
 */
class User_List_Table extends WP_List_Table {

	public string $plugin_text_domain = 'tally';
	/**
	 * @var array|object|stdClass[]|null
	 */
	private $table_data;

	private $table_data_count;

	public function get_columns(): array {

		return [
//			'cb'          => '<input type="checkbox" />', // to display the checkbox.
			'id'          => __( 'ID', $this->plugin_text_domain ),
			'created'     => __( 'Date', $this->plugin_text_domain ),
			'user_id'     => __( 'User', $this->plugin_text_domain ),
			'user_ip'     => __( 'IP', $this->plugin_text_domain ),
//			'event_code'  => __( 'Code', $this->plugin_text_domain ),
			'object_type' => __( 'Context', $this->plugin_text_domain ),
			'event_type'  => __( 'Action', $this->plugin_text_domain ),
			'event_meta'  => __( 'Meta ', $this->plugin_text_domain ),
		];
	}

	public function no_items() {

		if ( isset( $_GET['s'] ) ) {
			_e( 'No events have been found that meet your search criteria.', $this->plugin_text_domain );
		} else {
			_e( 'No events have been logged yet.', $this->plugin_text_domain );
		}
	}

	public function prepare_items() {

		//used by WordPress to build and fetch the _column_headers property
		$primary               = 'ID';
		$hidden                = [];
		$this->_column_headers = [ $this->get_columns(), $hidden, $this->get_sortable_columns(), $primary ];

		$this->table_data_count = $this->fetch_table_data_count();

		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$total_items  = $this->table_data_count;

		$this->set_pagination_args( array(
			'total_items' => $total_items, // total number of items
			'per_page'    => $per_page, // items to show on a page
			'total_pages' => ceil( $total_items / $per_page ) // use ceil to round up
		) );

		$this->table_data = $this->fetch_table_data_paginated();

		$this->items = $this->table_data;

	}

	private function apply_where_filter() {

		global $wpdb;

		$where = ' WHERE 1=1 ';

		if ( ! empty( $_GET['s'] ) ) {
			$s = trim( $_GET['s'] );

			$where = " WHERE event_type LIKE '%$s%' 
                        OR event_code LIKE '%$s%' 
                        OR object_type LIKE '%$s%'  
                        OR object_subtype LIKE '%$s%'  
                        OR object_id LIKE '%$s%' 
                        OR user_id LIKE '%$s%'  
                        OR user_ip LIKE '%$s%' ";

			$ids        = "SELECT event_id FROM {$wpdb->prefix}logdash_activity_meta WHERE value LIKE '%$s%' GROUP BY event_id;";
			$ids_query  = $wpdb->get_col( $ids );
			$ids_string = implode( ',', $ids_query );

			if ( ! empty( $ids_query ) ) {
				$where .= " OR ID IN($ids_string) ";
			}
		}

		if ( ! empty( $_GET['dateshow'] ) ) {
			if ( $_GET['dateshow'] === 'today' ) {
				$where .= " AND FROM_UNIXTIME(created, '%Y-%m-%d') = CURRENT_DATE ";
			}
			if ( $_GET['dateshow'] === 'yesterday' ) {
				$where .= " AND FROM_UNIXTIME(created, '%Y-%m-%d') = DATE_SUB(CURRENT_DATE, INTERVAL 1 DAY) ";
			}
			if ( $_GET['dateshow'] === 'week' ) {
				$where .= " AND FROM_UNIXTIME(created, '%Y-%m-%d') >= DATE_SUB(CURRENT_DATE, INTERVAL 1 WEEK) ";
			}
			if ( $_GET['dateshow'] === 'month' ) {
				$where .= " AND FROM_UNIXTIME(created, '%Y-%m-%d') >= DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH) ";
			}

		}

		if ( ! empty( $_GET['capshow'] ) ) {
			$where .= " AND user_caps = '" . $_GET['capshow'] . "' ";
		}

		if ( ! empty( $_GET['usershow'] ) ) {
			$where .= " AND user_id = " . $_GET['usershow'] . " ";
		}

		if ( ! empty( $_GET['subtypeshow'] ) ) {
			$where .= " AND object_subtype = '" . $_GET['subtypeshow'] . "' ";
		}

		if ( ! empty( $_GET['actionshow'] ) ) {
			$where .= " AND event_type = '" . $_GET['actionshow'] . "' ";
		}

		return $where;
	}

	public function fetch_table_data_count() {
		global $wpdb;
		$wpdb_table = $wpdb->prefix . 'logdash_activity_log';

		$query = "SELECT COUNT(*) as AGGREGATE FROM $wpdb_table {$this->apply_where_filter()}";

		return $wpdb->get_var( $query );

	}

	public function fetch_table_data_paginated() {

		global $wpdb;
		$wpdb_table = $wpdb->prefix . 'logdash_activity_log';
		$orderby    = ( isset( $_GET['orderby'] ) ) ? esc_sql( $_GET['orderby'] ) : 'created';
		$order      = ( isset( $_GET['order'] ) ) ? esc_sql( $_GET['order'] ) : 'DESC';
		$where      = $this->apply_where_filter();

		$per_page     = $this->get_pagination_arg( 'per_page' );
		$current_page = ( $this->get_pagenum() - 1 ) * $per_page;

		$event_query = "SELECT 
                            ID, event_type, event_code, object_type, object_subtype, object_id, user_id, user_caps, user_ip, user_agent, created
                        FROM 
                            $wpdb_table {$this->apply_where_filter()} 
                        ORDER BY $orderby $order 
                        LIMIT $current_page, $per_page";

		// query output_type will be an associative array with ARRAY_A.
		$query_results = $wpdb->get_results( $event_query, ARRAY_A );

		if ( empty( $query_results ) ) {
			return [];
		}

		$ids = implode( ',', array_column( $query_results, 'ID' ) );

		$events_meta = "SELECT
	                        ID, event_id, name, value
                        FROM
	                        {$wpdb->prefix}logdash_activity_meta
                        WHERE event_id IN ($ids)";

		$meta = $wpdb->get_results( $events_meta, ARRAY_A );

		return array_map( function ( $log_row ) use ( $meta ) {

			$log_row['event_meta'] = [];

			$row_meta = array_filter( $meta, function ( $meta_row ) use ( $log_row ) {
				return $meta_row['event_id'] === $log_row['ID'];
			} );

			foreach ( $row_meta as $meta ) {
				$log_row['event_meta'][ $meta['name'] ] = $meta['value'];
			}

			return apply_filters( 'logdash_add_columns_content_event_meta', $log_row, $meta );

		}, $query_results );

	}


	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'id':
                return $item['ID'];
			case 'event_type':
				return EventTypes::label( $item[ $column_name ] );
			case 'event_code':
				return $item[ $column_name ];

			case 'object_type':
				$output = ucfirst( $item[ $column_name ] );

				if ( ! empty( $item['object_subtype'] ) && $item['object_subtype'] !== $item[$column_name] ) {
					$output.= ' (' . ucfirst($item['object_subtype']) . ')';
				}

				return $output;

			case 'user_ip':
				if ( $item[ $column_name ] === '127.0.0.1' ) {
					$ip = '<span class="ip-info">' . $item[ $column_name ] . '</span>';
				} else {
					$ip = '<a class="ip-info" href="https://whatismyipaddress.com/ip/' . $item[ $column_name ] . '" data-ip="' . $item[ $column_name ] . '">' . $item[ $column_name ] . '</a>';
				}

				return '<div class="ip-info-wrapper">' . $ip . '</div>';

			case 'user_id':
				$user = get_user_by( 'ID', $item[ $column_name ] );

				if ( ! $user ) {

					return <<<HTML
                <div style="display: flex; gap: 10px; align-items: center; ">
                    <div style="clip-path: circle(50%); overflow: hidden; display: flex; min-width: 40px; min-height: 40px;"><span style="font-size: 40px;" class="dashicons dashicons-wordpress"></span></div>
                    <div class="text" style="display: flex; flex-direction: column;">
                        <span>System</span>
                    </div>
                </div>
HTML;

				}

				$avatar       = get_avatar( $user->ID, 40 );
				$profile_link = get_edit_user_link( $user->ID );
				$role         = ucfirst( $user->roles[0] );

				return <<<HTML
                <div style="display: flex; gap: 10px">
                    <div style="clip-path: circle(50%); overflow: hidden; display: flex; min-width: 40px;">$avatar</div>
                    <div class="text" style="display: flex; flex-direction: column;">
                        <span><a href="$profile_link">$user->user_login</a></span>
                        <span style="font-size: 1em; margin: 0;">$role</span>
                    </div>
                </div>
HTML;

			case 'created':

                $format = 'Y-m-d H:i:s';
                $gmt_date =  date( $format, (int) $item[ $column_name ] );
				$date                 = get_date_from_gmt( $gmt_date, $format );
				$time_diff            = human_time_diff( strtotime( $date ), current_time( 'U' ) );
				$translated_time_diff = __( sprintf( '%s ago', $time_diff ) );

				return date_i18n( 'F d, Y', $date ) . '<br>' .
				       date_i18n( 'h:m:s a', $date ) . '<br>' .
				       $translated_time_diff;

			case 'event_meta':

				$output = apply_filters( 'logdash_manage_columns_content_event_meta', '', $item, $item[ $column_name ] );

				$object_type = $item['object_type'];
				if ( ! empty( $object_type ) ) {
					$output .= apply_filters( "logdash_manage_columns-$object_type-content_event_meta", '', $item, $item[ $column_name ] );
				}

				$output .= $this->_actions( $item, $column_name );

				return ! empty( $output ) ? $output : null;
		}
	}

	private function _actions( $item, $column_name ) {

		$output  = '<div class="actions">';
		$actions = apply_filters( 'logdash_event_row_actions', [], $item, $item[ $column_name ] );

		$object_type = $item['object_type'];
		if ( ! empty ( $object_type ) ) {
			$actions = apply_filters( "logdash_event-{$object_type}-row_actions", $actions, $item, $item[ $column_name ] );
		}

		if ( ! empty( $actions ) ) {
			foreach ( $actions as $action ) {
				$output .= '<span class="action">' . $action . '</span>';
			}
		}
		$output .= '</div>';

		return $output;
	}

	protected function column_cb( $item ) {
		return sprintf(
			'<label class="screen-reader-text" for="event_' . $item['ID'] . '">' . sprintf( __( 'Select %s' ), $item['ID'] ) . '</label>'
			. "<input type='checkbox' name='events[]' id='event_{$item['ID']}' value='{$item['ID']}' />"
		);
	}

	protected function get_sortable_columns() {
		/*
		 * actual sorting still needs to be done by prepare_items.
		 * specify which columns should have the sort icon.
		 */
		$sortable_columns = array(
			'ID'        => array( 'ID', true ),
			'user_ip'   => [ 'IP', false ],
			'user_id'   => [ 'user_id', false ],
			'object_id' => [ 'object_id', false ],
			'created'   => [ 'created', false ]
		);

		return $sortable_columns;
	}

	protected function extra_tablenav( $which ) {
		global $wpdb;

		if ( $which === 'top' ) :

			$filters = [ 'dateshow', 'capshow', 'usershow', 'typeshow', 'actionshow' ];


			$date_show = [
				''          => 'All time',
				'today'     => 'Today',
				'yesterday' => 'Yesterday',
				'week'      => 'Last Week',
				'month'     => 'Last Month',
			];

			$selected_date_show = isset( $_GET['dateshow'] ) ? $_GET['dateshow'] : '';

			?>
            <select name="dateshow" id="temp-1">
				<?php foreach ( $date_show as $value => $label ) : ?>
                    <option value="<?php echo $value ?>" <?php selected( $selected_date_show, $value ) ?>><?php echo $label ?></option>
				<?php endforeach; ?>
            </select>

			<?php

			$caps_query = "SELECT user_caps 
                FROM {$wpdb->prefix}logdash_activity_log
                WHERE user_caps <> ''
                GROUP BY user_caps ORDER BY user_caps ASC;";

			$caps_results = $wpdb->get_results( $caps_query );

			$selected_cap = isset( $_GET['capshow'] ) ? $_GET['capshow'] : '';

			?>

            <select name="capshow" id="temp-2">
                <option value=""><?php _e( 'All Roles' ) ?></option>
				<?php foreach ( $caps_results as $cap ) : ?>
                    <option value="<?php echo $cap->user_caps ?>" <?php selected( $selected_cap, $cap->user_caps ) ?>><?php echo translate_user_role( ucfirst( $cap->user_caps ) ) ?></option>
				<?php endforeach; ?>
            </select>

			<?php

			$users_query = "SELECT user_id FROM {$wpdb->prefix}logdash_activity_log WHERE user_id > 0 GROUP BY user_id ORDER BY user_id ASC;";

			$users_result = $wpdb->get_results( $users_query );

			$selected_user = isset( $_GET['usershow'] ) ? $_GET['usershow'] : '';

			?>
            <select name="usershow" id="temp-3">
                <option value="">All Users</option>
				<?php foreach ( $users_result as $user ) : ?>
					<?php $user_data = get_user_by( 'ID', $user->user_id ); ?>
                    <option value="<?php echo $user->user_id ?>" <?php selected( $selected_user, $user->user_id ) ?>><?php echo $user_data->user_login ?></option>
				<?php endforeach; ?>
            </select>
			<?php

			$type_query = "SELECT
                                object_subtype
                            FROM
                                {$wpdb->prefix}logdash_activity_log
                            GROUP BY
                                object_subtype
                            ORDER BY
                                object_subtype ASC;";

			$type_result = $wpdb->get_results( $type_query );

			$selected_type = isset( $_GET['subtypeshow'] ) ? $_GET['subtypeshow'] : '';

			?>
            <select name="subtypeshow" id="temp-4">
                <option value="">All Contexts</option>
				<?php  foreach ( $type_result as $type ) :
				?>
                <option value="<?php  echo $type->object_subtype
				?>" <?php  selected( $selected_type, $type->object_subtype )
				?>><?php  echo ucfirst( $type->object_subtype )
					?></option>
				<?php  endforeach;
				?>
            </select>

			<?php

			$action_query = "SELECT
                                event_type
                            FROM
                                {$wpdb->prefix}logdash_activity_log
                            GROUP BY
                                event_type
                            ORDER BY
                                event_type ASC;";

			$action_result = $wpdb->get_results( $action_query );

			$selected_type = isset( $_GET['actionshow'] ) ? $_GET['actionshow'] : '';

			?>
            <select name="actionshow" id="temp-5">
                <option value="">All Actions</option>
				<?php foreach ( $action_result as $action ) : ?>
                    <option value="<?php echo $action->event_type ?>" <?php selected( $selected_type, $action->event_type ) ?>><?php echo ucfirst( $action->event_type ) ?></option>
				<?php endforeach; ?>
            </select>

            <input type="submit" name="filter" id="submit-13ert" class="button" value="Filter">
			<?php

			foreach ( $filters as $filter ) {
				if ( ! empty( $_GET[ $filter ] ) ) {
					?> <a href="?page=<?php echo $_GET['page'] ?>" style="margin-left: 5px;">Reset filter</a> <?php
					break;
				}
			}

		endif;
	}

}


add_action( 'admin_menu', function () {

	$icon_svg = 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAiIGhlaWdodD0iMjAiIHZpZXdCb3g9IjAgMCAyMCAyMCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KPHBhdGggZD0iTTEgOS4wMzU5MUMxIDguNDQwNjQgMS40ODI1NyA3Ljk1ODA3IDIuMDc3ODQgNy45NTgwN0gxMy4xNzk2QzEzLjc3NDkgNy45NTgwNyAxNC4yNTc1IDguNDQwNjQgMTQuMjU3NSA5LjAzNTkxVjEwLjIyMTVDMTQuMjU3NSAxMC44MTY4IDEzLjc3NDkgMTEuMjk5NCAxMy4xNzk2IDExLjI5OTRIMi4wNzc4NEMxLjQ4MjU3IDExLjI5OTQgMSAxMC44MTY4IDEgMTAuMjIxNVY5LjAzNTkxWiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTEgNC4wNzc4NEMxIDMuNDgyNTcgMS40ODI1NyAzIDIuMDc3ODQgM0gxMy4xNzk2QzEzLjc3NDkgMyAxNC4yNTc1IDMuNDgyNTcgMTQuMjU3NSA0LjA3Nzg0VjUuMjYzNDdDMTQuMjU3NSA1Ljg1ODc1IDEzLjc3NDkgNi4zNDEzMiAxMy4xNzk2IDYuMzQxMzJIMi4wNzc4NEMxLjQ4MjU3IDYuMzQxMzIgMSA1Ljg1ODc1IDEgNS4yNjM0N1Y0LjA3Nzg0WiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTE1LjY1ODcgOS4wMzU5MUMxNS42NTg3IDguNDQwNjQgMTYuMTQxMyA3Ljk1ODA3IDE2LjczNjUgNy45NTgwN0gxNy45MjIyQzE4LjUxNzQgNy45NTgwNyAxOSA4LjQ0MDY0IDE5IDkuMDM1OTFWMTAuMjIxNUMxOSAxMC44MTY4IDE4LjUxNzQgMTEuMjk5NCAxNy45MjIyIDExLjI5OTRIMTYuNzM2NUMxNi4xNDEzIDExLjI5OTQgMTUuNjU4NyAxMC44MTY4IDE1LjY1ODcgMTAuMjIxNVY5LjAzNTkxWiIgZmlsbD0id2hpdGUiLz4KPHBhdGggZD0iTTEgMTMuOTk0QzEgMTMuMzk4NyAxLjQ4MjU3IDEyLjkxNjIgMi4wNzc4NCAxMi45MTYySDE3LjkyMjJDMTguNTE3NCAxMi45MTYyIDE5IDEzLjM5ODcgMTkgMTMuOTk0VjE1LjE3OTZDMTkgMTUuNzc0OSAxOC41MTc0IDE2LjI1NzUgMTcuOTIyMiAxNi4yNTc1SDIuMDc3ODRDMS40ODI1NyAxNi4yNTc1IDEgMTUuNzc0OSAxIDE1LjE3OTZWMTMuOTk0WiIgZmlsbD0id2hpdGUiLz4KPC9zdmc+Cg==';
	$icon_url = LOGDASH_URL . '/assets/img/icon-logdash-white.svg';

	add_menu_page( 'LogDash Activity',
		'LogDash',
		'manage_options',
		'logdash_activity_log',
		'logdash_load_user_list_table_screen_options',
		$icon_url,
		3 );

} );

function logdash_load_user_list_table_screen_options() {
	$arguments = array(
		'label'   => __( 'Users Per Page', 'tally' ),
		'default' => 5,
		'option'  => 'users_per_page'
	);
	add_screen_option( 'per_page', $arguments );

	$user_list_table = new User_List_Table( 'tally' );
	$user_list_table->prepare_items();
	?>
    <div class="wrap">

        <div id="my-react-app"></div>

        <h1 style="display: flex; align-items: center; gap: 5px;">
			<?php _e( 'LogDash Activity', 'tally' ); ?>
        </h1>
        <div id="nds-wp-list-table-demo">
            <div id="nds-post-body">
                <form method="GET">

					<?php $user_list_table->search_box( __( 'Search' ), 's' ); ?>

                    <input type="hidden" name="page" value="<?php echo $_GET['page']; ?>">
					<?php $user_list_table->display(); ?>
                </form>
            </div>
        </div>
    </div>
	<?php
}