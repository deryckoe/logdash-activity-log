<?php

function tally_activate_plugin() {
	global $wpdb;

	$tables[] = "CREATE TABLE `{$wpdb->prefix}logdash_activity_log` (
				`ID` bigint NOT NULL AUTO_INCREMENT,
				`site_id` bigint NOT NULL,
				`event_type` varchar(255) NOT NULL,
				`event_code` int NOT NULL DEFAULT '0',
				`object_type` varchar(255) NOT NULL,
				`object_subtype` varchar(255) DEFAULT NULL,
				`object_id` bigint DEFAULT NULL,
				`user_id` bigint DEFAULT NULL,
				`user_caps` varchar(70) DEFAULT '',
				`user_ip` varchar(55) NOT NULL DEFAULT '127.0.0.1',
				`user_agent` longtext,
				`created` int NOT NULL DEFAULT '0',
				PRIMARY KEY (`ID`)
				) CHARSET={$wpdb->charset} COLLATE={$wpdb->collate};";

	$tables[] = "CREATE TABLE `{$wpdb->prefix}logdash_activity_meta` (
				`ID` bigint NOT NULL AUTO_INCREMENT,
				`event_id` bigint NOT NULL DEFAULT '0',
				`name` varchar(255) NOT NULL,
				`value` longtext,
				  PRIMARY KEY (`ID`)
				) CHARSET={$wpdb->charset} COLLATE={$wpdb->collate};";

	$tables[] = "CREATE TABLE `{$wpdb->prefix}logdash_ip_info` (
  				`ID` int NOT NULL AUTO_INCREMENT,
  				`ip` varchar(255) DEFAULT NULL,
  				`info` longtext,
  					PRIMARY KEY (`ID`)
				) CHARSET={$wpdb->charset} COLLATE={$wpdb->collate};";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

	foreach ( $tables as $table ) {
		dbDelta( $table );

		if ( $wpdb->result === false ) {
			wp_die( "Plugin activation failed. <br>" . $wpdb->last_error );
		}
	}

}

register_activation_hook( LOGDASH_PLUGIN_FILE, 'tally_activate_plugin' );


/**
 * Register a custom post type called "book".
 *
 * @see get_post_type_labels() for label keys.
 */
function wpdocs_codex_book_init() {
	$labels = array(
		'name'                  => _x( 'Books', 'Post type general name', 'textdomain' ),
		'singular_name'         => _x( 'Book', 'Post type singular name', 'textdomain' ),
		'menu_name'             => _x( 'Books', 'Admin Menu text', 'textdomain' ),
		'name_admin_bar'        => _x( 'Book', 'Add New on Toolbar', 'textdomain' ),
		'add_new'               => __( 'Add New', 'textdomain' ),
		'add_new_item'          => __( 'Add New Book', 'textdomain' ),
		'new_item'              => __( 'New Book', 'textdomain' ),
		'edit_item'             => __( 'Edit Book', 'textdomain' ),
		'view_item'             => __( 'View Book', 'textdomain' ),
		'all_items'             => __( 'All Books', 'textdomain' ),
		'search_items'          => __( 'Search Books', 'textdomain' ),
		'parent_item_colon'     => __( 'Parent Books:', 'textdomain' ),
		'not_found'             => __( 'No books found.', 'textdomain' ),
		'not_found_in_trash'    => __( 'No books found in Trash.', 'textdomain' ),
		'featured_image'        => _x( 'Book Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'textdomain' ),
		'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
		'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
		'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'textdomain' ),
		'archives'              => _x( 'Book archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'textdomain' ),
		'insert_into_item'      => _x( 'Insert into book', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'textdomain' ),
		'uploaded_to_this_item' => _x( 'Uploaded to this book', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'textdomain' ),
		'filter_items_list'     => _x( 'Filter books list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'textdomain' ),
		'items_list_navigation' => _x( 'Books list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'textdomain' ),
		'items_list'            => _x( 'Books list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'textdomain' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => 'book' ),
		'capability_type'    => 'post',
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'supports'           => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments', 'page-attributes' ),
	);

	register_post_type( 'book', $args );

	$labels = array(
		'name'              => 'Groups',
		'singular_name'     => 'Group',
	);

	register_taxonomy( 'group', ['book', 'post'], array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'show_in_rest'      => true,
		 'rewrite'      => array( 'slug' => 'group' )
	) );

	$labels = array(
		'name'              => 'Specs',
		'singular_name'     => 'Spec',
	);

	register_taxonomy( 'specs', ['book', 'post'], array(
		'hierarchical'      => false,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'show_in_rest'      => true,
		'rewrite'      => array( 'slug' => 'specs' )
	) );
}

add_action( 'init', 'wpdocs_codex_book_init' );

