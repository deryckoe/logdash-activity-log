<?php

/** @noinspection PhpMultipleClassDeclarationsInspection */

namespace LogDash\Admin;

use LogDash\API\DB;
use LogDash\EventTypes;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class EventsListTable extends \WP_List_Table {

	/**
	 * @var array|object|null
	 */
	private $table_data;

	private int $table_data_count;

	public function get_columns(): array {

		return [
			'event_code'  => __( 'Code', LOGDASH_DOMAIN ),
			'created'     => __( 'Date', LOGDASH_DOMAIN ),
			'user_id'     => __( 'User', LOGDASH_DOMAIN ),
			'user_ip'     => __( 'IP', LOGDASH_DOMAIN ),
			'object_type' => __( 'Context', LOGDASH_DOMAIN ),
			'event_type'  => __( 'Action', LOGDASH_DOMAIN ),
			'event_meta'  => __( 'Meta ', LOGDASH_DOMAIN ),
		];
	}

	public function no_items() {
		if ( isset( $_GET['s'] ) ) {
			_e( 'No events have been found that meet your search criteria.', LOGDASH_DOMAIN );
		} else {
			_e( 'No events have been logged yet.', LOGDASH_DOMAIN );
		}
	}

	public function prepare_items() {

		//used by WordPress to build and fetch the _column_headers property
		$primary               = 'ID';
		$hidden                = [];
		$this->_column_headers = [ $this->get_columns(), $hidden, $this->get_sortable_columns(), $primary ];

		$this->table_data_count = $this->fetch_table_data_count();

		$per_page    = 20;
		$total_items = $this->table_data_count;

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
			$s = trim( sanitize_text_field( $_GET['s'] ) );


			$s_param = array_fill( 0, 7, '%' . $wpdb->esc_like( $s ) . '%' );
			$where   = $wpdb->prepare( "WHERE event_type LIKE %s 
                        OR event_code LIKE %s 
                        OR object_type LIKE %s  
                        OR object_subtype LIKE %s  
                        OR object_id LIKE %s 
                        OR user_id LIKE %s  
                        OR user_ip LIKE %s ", $s_param );

			$meta_table = DB::meta_table();

			$ids        = $wpdb->prepare(
				"SELECT event_id FROM {$meta_table} WHERE value LIKE %s GROUP BY event_id;",
				'%' . $wpdb->esc_like( $s ) . '%'
			);
			$ids_query  = $wpdb->get_col( $ids );
			$ids_string = implode( ',', $ids_query );

			if ( ! empty( $ids_query ) ) {
				$where .= $wpdb->prepare( " OR ID IN(%d) ", $ids_string );
			}
		}

		if ( ! empty( $_GET['dateshow'] ) ) {

			$date_show         = sanitize_text_field( $_GET['dateshow'] );
			$current_timestamp = time();

			if ( $date_show === 'today' ) {
				$start_timestamp = strtotime( 'today' );
			}
			if ( $date_show === 'yesterday' ) {
				$start_timestamp   = strtotime( 'yesterday' );
				$current_timestamp = strtotime( 'today' );
			}
			if ( $date_show === 'week' ) {
				$start_timestamp = strtotime( '-1 week' );
			}
			if ( $date_show === 'month' ) {
				$start_timestamp = strtotime( '-1 month' );
			}

			if ( ! empty( $start_timestamp ) ) {
				$where .= " AND created BETWEEN $start_timestamp AND $current_timestamp";
			}

		}

		if ( ! empty( $_GET['capshow'] ) ) {
			$cap_show = sanitize_text_field( $_GET['capshow'] );
			$where    .= $wpdb->prepare( " AND user_caps = %s ", $cap_show );
		}

		if ( ! empty( $_GET['usershow'] ) ) {
			$user_show = sanitize_text_field( $_GET['usershow'] );
			$where     .= $wpdb->prepare( " AND user_id = %d ", $user_show );
		}

		if ( ! empty( $_GET['subtypeshow'] ) ) {
			$subtype_show = sanitize_text_field( $_GET['subtypeshow'] );
			$where        .= $wpdb->prepare( " AND object_subtype = %s ", $subtype_show );
		}

		if ( ! empty( $_GET['actionshow'] ) ) {
			$action_show = sanitize_text_field( $_GET['actionshow'] );
			$where       .= $wpdb->prepare( " AND event_type = %s ", $action_show );
		}

		$site_id = get_current_blog_id();
		$where   .= $wpdb->prepare( " AND site_id = %d", $site_id );

		return $where;
	}

	public function fetch_table_data_count() {
		global $wpdb;

		$where_condition = $this->apply_where_filter();
		$query           = $wpdb->prepare( "SELECT COUNT(*) as AGGREGATE FROM %i {$where_condition}", DB::log_table() );

		return (int) $wpdb->get_var( $query );
	}


	public function fetch_table_data_paginated() {

		global $wpdb;
		$log_table  = DB::log_table();
		$meta_table = DB::meta_table();

		$orderby = ( isset( $_GET['orderby'] ) ) ? sanitize_text_field( $_GET['orderby'] ) : 'ID';
		$order   = ( isset( $_GET['order'] ) ) ? sanitize_text_field( $_GET['order'] ) : 'DESC';

		$per_page     = $this->get_pagination_arg( 'per_page' );
		$current_page = ( $this->get_pagenum() - 1 ) * $per_page;

		$where_filter    = $this->apply_where_filter();
		$order_by_string = sanitize_sql_orderby( $orderby . ' ' . $order );

		$event_query = $wpdb->prepare( "SELECT 
                            ID, event_type, event_code, object_type, object_subtype, object_id, user_id, user_caps, user_ip, user_agent, created
                            FROM %i $where_filter  
                            ORDER BY $order_by_string
                            LIMIT %d, %d",
			$log_table,
			$current_page,
			$per_page
		);

		// query output_type will be an associative array with ARRAY_A.
		$query_results = $wpdb->get_results( $event_query, ARRAY_A );

		if ( empty( $query_results ) ) {
			return [];
		}

		$ids = implode( ',', array_column( $query_results, 'ID' ) );

		$events_meta = $wpdb->prepare( "SELECT
	                        ID, event_id, name, value
                        FROM
	                        %i
                        WHERE event_id IN ($ids)", [ $meta_table ] );

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

				if ( ! empty( $item['object_subtype'] ) && $item['object_subtype'] !== $item[ $column_name ] ) {
					$output .= ' (' . ucfirst( $item['object_subtype'] ) . ')';
				}

				return $output;

			case 'user_ip':
				if ( $item[ $column_name ] === '127.0.0.1' ) {
					$ip = '<span class="ip-info">' . $item[ $column_name ] . '</span>';
				} else {
					$ip = '<a class="ip-info" target="_blank" href="https://whatismyipaddress.com/ip/' . $item[ $column_name ] . '" data-ip="' . $item[ $column_name ] . '">' . $item[ $column_name ] . '</a>';
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
                        <span><a href="$profile_link" title="$user->user_email">$user->user_login</a></span>
                        <span style="font-size: 1em; margin: 0;">$role</span>
                    </div>
                </div>
HTML;

			case 'created':

				$timestamp = $item[ $column_name ];
				$format    = 'Y-m-d H:i:s';
				$gmt_date  = date( $format, (int) $timestamp );
				$date      = get_date_from_gmt( $gmt_date, $format );

				$time_diff            = human_time_diff( strtotime( $date ), current_time( 'U' ) );
				$translated_time_diff = __( sprintf( '%s ago', $time_diff ) );

				return date_i18n( 'F d, Y', $date ) . '<br>' .
				       date( 'h:i:s a', $timestamp ) . '<br>' .
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

	private function _actions( $item, $column_name ): string {

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
		$item_id = esc_attr( $item['ID'] );

		return sprintf(
			'<label class="screen-reader-text" for="event_' . esc_attr( $item['ID'] ) . '">' . sprintf( esc_attr__( 'Select %s' ), $item['ID'] ) . '</label>'
			. "<input type='checkbox' name='events[]' id='event_{$item_id}' value='{$item_id}' />"
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

		$log_table  = DB::log_table();
		$meta_table = DB::meta_table();
		$site_id    = get_current_blog_id();

		if ( $which === 'top' ) :

			$filters = [ 'dateshow', 'capshow', 'usershow', 'subtypeshow', 'actionshow' ];


			$date_show = [
				''          => __( 'All time', LOGDASH_DOMAIN ),
				'today'     => __( 'Today', LOGDASH_DOMAIN ),
				'yesterday' => __( 'Yesterday', LOGDASH_DOMAIN ),
				'week'      => __( 'Last Week', LOGDASH_DOMAIN ),
				'month'     => __( 'Last Month', LOGDASH_DOMAIN ),
			];

			$selected_date_show = isset( $_GET['dateshow'] ) ? sanitize_text_field( $_GET['dateshow'] ) : '';

			?>
            <select class="ld-select" name="dateshow" id="temp-1">
				<?php foreach ( $date_show as $value => $label ) : ?>
                    <option
                            value="<?php echo esc_attr( $value ) ?>" <?php selected( esc_attr( $selected_date_show ), esc_attr( $value ) ) ?>><?php echo esc_html( $label ) ?></option>
				<?php endforeach; ?>
            </select>

			<?php

			$caps_query = "SELECT user_caps 
                FROM {$log_table}
                WHERE user_caps <> '' AND site_id = {$site_id} 
                GROUP BY user_caps ORDER BY user_caps ASC;";

			$caps_results = $wpdb->get_results( $caps_query );

			$selected_cap = isset( $_GET['capshow'] ) ? sanitize_text_field( $_GET['capshow'] ) : '';

			?>

            <select class="ld-select" name="capshow" id="temp-2">
                <option value=""><?php esc_attr_e( 'All Roles', LOGDASH_DOMAIN ) ?></option>
				<?php foreach ( $caps_results as $cap ) : ?>
                    <option
                            value="<?php echo esc_attr( $cap->user_caps ) ?>" <?php selected( esc_attr( $selected_cap ), esc_attr( $cap->user_caps ) ) ?>><?php echo translate_user_role( ucfirst( esc_html( $cap->user_caps ) ) ) ?></option>
				<?php endforeach; ?>
            </select>

			<?php

			$users_query = "SELECT user_id FROM {$log_table} WHERE user_id > 0 AND site_id = {$site_id} GROUP BY user_id ORDER BY user_id ASC;";

			$users_result = $wpdb->get_results( $users_query );

			$selected_user = isset( $_GET['usershow'] ) ? sanitize_text_field( $_GET['usershow'] ) : '';

			?>
            <select class="ld-select" name="usershow" id="temp-3">
                <option value=""><?php _e( 'All Users', LOGDASH_DOMAIN ); ?></option>
				<?php foreach ( $users_result as $user ) : ?>
					<?php $user_data = get_user_by( 'ID', $user->user_id ); ?>
                    <option
                            value="<?php echo esc_attr( $user->user_id ) ?>" <?php selected( esc_attr( $selected_user ), esc_attr( $user->user_id ) ) ?>><?php echo esc_html( $user_data->user_login ) ?></option>
				<?php endforeach; ?>
            </select>
			<?php

			$type_query = "SELECT
                                object_subtype
                            FROM
                                {$log_table}
                            WHERE 
                                site_id = {$site_id}
                            GROUP BY
                                object_subtype
                            ORDER BY
                                object_subtype ASC;";

			$type_result = $wpdb->get_results( $type_query );

			$selected_type = isset( $_GET['subtypeshow'] ) ? sanitize_text_field( $_GET['subtypeshow'] ) : '';

			?>
            <select class="ld-select" name="subtypeshow" id="temp-4">
                <option value=""><?php _e( 'All Contexts', LOGDASH_DOMAIN ) ?></option>
				<?php foreach ( $type_result as $type ) :
					?>
                    <option value="<?php echo esc_attr( $type->object_subtype )
					?>" <?php selected( esc_attr( $selected_type ), esc_attr( $type->object_subtype ) )
					?>><?php echo ucfirst( esc_html( $type->object_subtype ) )
						?></option>
				<?php endforeach;
				?>
            </select>

			<?php

			$action_query = "SELECT
                                event_type
                            FROM
                                {$log_table}
                            WHERE
                                site_id = {$site_id}
                            GROUP BY
                                event_type
                            ORDER BY
                                event_type ASC;";

			$action_result = $wpdb->get_results( $action_query );

			$selected_type = isset( $_GET['actionshow'] ) ? sanitize_text_field( $_GET['actionshow'] ) : '';

			?>
            <select class="ld-select" name="actionshow" id="temp-5">
                <option value=""><?php _e( 'All Actions', LOGDASH_DOMAIN ) ?></option>
				<?php foreach ( $action_result as $action ) : ?>
                    <option
                            value="<?php echo( esc_attr( $action->event_type ) ) ?>" <?php selected( esc_attr( $selected_type ), esc_attr( $action->event_type ) ) ?>><?php echo ucfirst( esc_html( $action->event_type ) ) ?></option>
				<?php endforeach; ?>
            </select>

            <input type="submit" name="filter" id="submit-13ert" class="button" value="Filter">
			<?php

			foreach ( $filters as $filter ) {
				if ( ! empty( $_GET[ $filter ] ) ) {
					$page = sanitize_text_field( $_GET['page'] );
					?> <a href="?page=<?php echo esc_html( $page ) ?>"
                          style="margin-left: 5px;"><?php _e( 'Reset filter', LOGDASH_DOMAIN ) ?></a> <?php
					break;
				}
			}

		endif;
	}

}