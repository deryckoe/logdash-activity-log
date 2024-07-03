<?php

namespace LogDash;

class EventInfo {

	public static array $codes = [];

	public static function init() {
		self::$codes = [
			'default'                   => [
				'code'        => 1001,
				'description' => __( 'Default event.', LOGDASH_DOMAIN )
			],
			'core_upgraded'             => [
				'code'        => 1010,
				'description' => __( 'Updated WordPress from %s to %s', LOGDASH_DOMAIN )
			],
			'core_reinstalled'          => [
				'code'        => 1011,
				'description' => __( 'Reinstalled WordPress %s', LOGDASH_DOMAIN )
			],
			'core_downgraded'           => [
				'code'        => 1012,
				'description' => __( 'Downgraded WordPress from %s to %s', LOGDASH_DOMAIN )
			],
			'plugin_uploaded'           => [
				'code'        => 1110,
				'description' => __( 'Plugin uploaded', LOGDASH_DOMAIN )
			],
			'plugin_downloaded'         => [
				'code'        => 1111,
				'description' => __( 'Plugin downloaded', LOGDASH_DOMAIN )
			],
			'plugin_installed'          => [
				'code'        => 1112,
				'description' => __( 'Installed %s %s plugin at %s', LOGDASH_DOMAIN )
			],
			'plugin_uninstalled'        => [
				'code'        => 1113,
				'description' => __( 'Uninstalled %s %s plugin at %s', LOGDASH_DOMAIN )
			],
			'plugin_activated'          => [
				'code'        => 1114,
				'description' => __( 'Activated the plugin %s %s', LOGDASH_DOMAIN )
			],
			'plugin_deactivated'        => [
				'code'        => 1115,
				'description' => __( 'Deactivated the plugin %s %s', LOGDASH_DOMAIN )
			],
			'plugin_upgraded'           => [
				'code'        => 1116,
				'description' => __( 'Upgraded plugin %s to version %s', LOGDASH_DOMAIN )
			],
			'theme_uploaded'            => [
				'code'        => 1210,
				'description' => __( 'Uploaded theme %s %s', LOGDASH_DOMAIN )
			],
			'theme_downloaded'          => [
				'code'        => 1211,
				'description' => __( 'Downloaded theme %s %s', LOGDASH_DOMAIN )
			],
			'theme_installed'           => [
				'code'        => 1212,
				'description' => __( 'Installed theme %s %s', LOGDASH_DOMAIN )
			],
			'theme_uninstalled'         => [
				'code'        => 1213,
				'description' => __( 'Uninstalled theme %s %s', LOGDASH_DOMAIN )
			],
			'theme_activated'           => [
				'code'        => 1214,
				'description' => __( 'Activated theme %s %s', LOGDASH_DOMAIN )
			],
			'theme_deactivated'         => [
				'code'        => 1215,
				'description' => __( 'Deactivated theme %s %s', LOGDASH_DOMAIN )
			],
			'theme_upgraded'            => [
				'code'        => 1216,
				'description' => __( 'Upgraded theme %s to version %s', LOGDASH_DOMAIN )
			],
			'user_login'                => [
				'code'        => 1310,
				'description' => __( 'User %s logged in.', LOGDASH_DOMAIN )
			],
			'user_logout'               => [
				'code'        => 1311,
				'description' => __( 'User %s logged out.', LOGDASH_DOMAIN )
			],
			'user_login_fail'           => [
				'code'        => 1312,
				'description' => __( 'Failed login attempt for %s.', LOGDASH_DOMAIN )
			],
			'user_destroyed_sessions'   => [
				'code'        => 1313,
				'description' => __( 'All %s user sessions were closed.', LOGDASH_DOMAIN )
			],
			'user_created'              => [
				'code'        => 1314,
				'description' => __( 'User %s created.', LOGDASH_DOMAIN )
			],
			'user_deleted'              => [
				'code'        => 1315,
				'description' => __( 'User %s deleted.', LOGDASH_DOMAIN )
			],
			'user_updated_profile'      => [
				'code'        => 1316,
				'description' => __( 'User %s profile updated.', LOGDASH_DOMAIN )
			],
			'user_updated_meta'         => [
				'code'        => 1317,
				'description' => __( 'Meta field %s updated from %s to %s for %s.', LOGDASH_DOMAIN )
			],
			'user_updated_email'        => [
				'code'        => 1318,
				'description' => __( 'Email updated from %s to %s for user %s', LOGDASH_DOMAIN )
			],
			'user_updated_login'        => [
				'code'        => 1319,
				'description' => __( 'User login updated.', LOGDASH_DOMAIN )
			],
			'user_update_password'      => [
				'code'        => 1320,
				'description' => __( 'Password updated for %s.', LOGDASH_DOMAIN )
			],
			'user_updated_nicename'     => [
				'code'        => 1321,
				'description' => __( 'User %s nice name updated.', LOGDASH_DOMAIN )
			],
			'user_updated_url'          => [
				'code'        => 1322,
				'description' => __( 'User url updated from %s to %s for %s.', LOGDASH_DOMAIN )
			],
			'user_updated_status'       => [
				'code'        => 1323,
				'description' => __( 'User %s status updated.', LOGDASH_DOMAIN )
			],
			'user_updated_displayname'  => [
				'code'        => 1324,
				'description' => __( 'Changed display name from %s to %s for %s.', LOGDASH_DOMAIN )
			],
			'user_updated_role'         => [
				'code'        => 1325,
				'description' => __( 'User %s role updated.', LOGDASH_DOMAIN )
			],
			'setting_created'           => [
				'code'        => 1410,
				'description' => __( 'Setting %s created.', LOGDASH_DOMAIN )
			],
			'setting_updated'           => [
				'code'        => 1411,
				'description' => __( 'Setting %s updated from %s to %s.', LOGDASH_DOMAIN )
			],
			'setting_deleted'           => [
				'code'        => 1412,
				'description' => __( 'Setting %s deleted.', LOGDASH_DOMAIN )
			],
			'file_uploaded'             => [
				'code'        => 1510,
				'description' => __( 'Uploaded file %s to %s.', LOGDASH_DOMAIN )
			],
			'file_deleted'              => [
				'code'        => 1511,
				'description' => __( 'Deleted file %s from %s.', LOGDASH_DOMAIN )
			],
			'file_updated'              => [
				'code'        => 1512,
				'description' => __( 'Attachment updated.', LOGDASH_DOMAIN )
			],
			'file_updated_plugin'       => [
				'code'        => 1513,
				'description' => __( 'Modified the file %s with plugin editor.', LOGDASH_DOMAIN )
			],
			'file_updated_theme'        => [
				'code'        => 1514,
				'description' => __( 'Modified the file %s with theme editor.', LOGDASH_DOMAIN )
			],
			'post_opened'               => [
				'code'        => 1610,
				'description' => __( 'Opened %s with title %s in the editor.', LOGDASH_DOMAIN )
			],
			'post_created'              => [
				'code'        => 1611,
				'description' => __( 'Created post %s.', LOGDASH_DOMAIN )
			],
			'post_updated'              => [
				'code'        => 1612,
				'description' => __( 'Post updated.', LOGDASH_DOMAIN )
			],
			'post_updated_status'       => [
				'code'        => 1613,
				'description' => __( 'Updated the status of %s post to %s.', LOGDASH_DOMAIN )
			],
			'post_updated_title'        => [
				'code'        => 1614,
				'description' => __( 'Updated title of post %s from %s to %s.', LOGDASH_DOMAIN )
			],
			'post_updated_content'      => [
				'code'        => 1615,
				'description' => __( 'Updated content for post %s.', LOGDASH_DOMAIN )
			],
			'post_updated_category'     => [
				'code'        => 1616,
				'description' => __( 'Categories updated from %s to %s in post %s.', LOGDASH_DOMAIN )
			],
			'post_updated_tag'          => [
				'code'        => 1617,
				'description' => __( 'Tags updated from %s to %s in post %s.', LOGDASH_DOMAIN )
			],
			'post_updated_term'         => [
				'code'        => 1618,
				'description' => __( 'Taxonomies updated from %s to %s in post %s.', LOGDASH_DOMAIN )
			],
			'post_updated_author'       => [
				'code'        => 1619,
				'description' => __( 'Author updated from %s to %s in post %s', LOGDASH_DOMAIN )
			],
			'post_updated_publish_date' => [
				'code'        => 1620,
				'description' => __( 'Updated publish date from %s to %s for post %s', LOGDASH_DOMAIN )
			],
			'post_updated_slug'         => [
				'code'        => 1621,
				'description' => __( 'URL updated from %s to %s in post %s', LOGDASH_DOMAIN )
			],
			'post_updated_excerpt'      => [
				'code'        => 1622,
				'description' => __( 'Excerpt updated from %s to %s in post %s', LOGDASH_DOMAIN )
			],
			'post_updated_parent'       => [
				'code'        => 1623,
				'description' => __( 'Parent assigned to %s for post %s', LOGDASH_DOMAIN )
			],
			'post_updated_menu_order'   => [
				'code'        => 1624,
				'description' => __( 'Updated menu order from %s to %s in Post %s', LOGDASH_DOMAIN )
			],
			'post_moved_trash'          => [
				'code'        => 1625,
				'description' => __( 'Moved post %s to trash.', LOGDASH_DOMAIN )
			],
			'post_deleted'              => [
				'code'        => 1626,
				'description' => __( 'Permanently Deleted post %s.', LOGDASH_DOMAIN )
			],
			'post_restored'             => [
				'code'        => 1627,
				'description' => __( 'Restored post %s from trash.', LOGDASH_DOMAIN )
			],
			'post_published'            => [
				'code'        => 1628,
				'description' => __( 'Published post %s.', LOGDASH_DOMAIN )
			],
			'post_scheduled'            => [
				'code'        => 1629,
				'description' => __( 'Post %s set as scheduled for %s.', LOGDASH_DOMAIN )
			],
			'post_updated_thumbnail'    => [
				'code'        => 1630,
				'description' => __( 'Updated featured thumbnail to %s for %s.', LOGDASH_DOMAIN )
			],
			'post_thumbnail_added'      => [
				'code'        => 1631,
				'description' => __( 'Added featured thumbnail %s for post %s.', LOGDASH_DOMAIN )
			],
			'post_thumbnail_removed'    => [
				'code'        => 1632,
				'description' => __( 'Removed featured post thumbnail %s for post %s.', LOGDASH_DOMAIN )
			],
			'post_template_updated'     => [
				'code'        => 1633,
				'description' => __( 'Post %s template set as %s.', LOGDASH_DOMAIN )
			],
			'post_visibility_updated'   => [
				'code'        => 1634,
				'description' => __( 'Modified post %s visibility from %s to %s.', LOGDASH_DOMAIN )
			],
			'post_stuck'                => [
				'code'        => 1635,
				'description' => __( 'Set the post %s as sticky.', LOGDASH_DOMAIN )
			],
			'post_unstuck'              => [
				'code'        => 1636,
				'description' => __( 'Removed the post %s from sticky', LOGDASH_DOMAIN )
			],
			'post_comments_enabled'     => [
				'code'        => 1637,
				'description' => __( 'Enabled comments for post %s.', LOGDASH_DOMAIN )
			],
			'post_comments_disabled'    => [
				'code'        => 1638,
				'description' => __( 'Disabled comments for post %s.', LOGDASH_DOMAIN )
			],
			'post_pings_enabled'        => [
				'code'        => 1639,
				'description' => __( 'Enabled pings and trackbacks for post %s.', LOGDASH_DOMAIN )
			],
			'post_pings_disabled'       => [
				'code'        => 1640,
				'description' => __( 'Disabled pings and trackbacks for post %s.', LOGDASH_DOMAIN )
			],
			'post_unlocked'             => [
				'code'        => 1641,
				'description' => __( 'Post %s has taken over.', LOGDASH_DOMAIN )
			],
			'post_created_meta'         => [
				'code'        => 1642,
				'description' => __( 'Created meta field %s with value %s in %s.', LOGDASH_DOMAIN )
			],
			'post_updated_meta'         => [
				'code'        => 1643,
				'description' => __( 'Updated meta field %s from %s to %s in %s.', LOGDASH_DOMAIN )
			],
			'post_deleted_meta'         => [
				'code'        => 1644,
				'description' => __( 'Post meta field has been deleted.', LOGDASH_DOMAIN )
			],
			'category_created'          => [
				'code'        => 1710,
				'description' => __( 'Created category %s.', LOGDASH_DOMAIN )
			],
			'category_deleted'          => [
				'code'        => 1711,
				'description' => __( 'Deleted category %s.', LOGDASH_DOMAIN )
			],
			'category_updated'          => [
				'code'        => 1712,
				'description' => __( 'Updated %s from %s (%s) to %s (%s).', LOGDASH_DOMAIN )
			],
			'tag_created'               => [
				'code'        => 1810,
				'description' => __( 'Created term %s in %s.', LOGDASH_DOMAIN )
			],
			'tag_deleted'               => [
				'code'        => 1811,
				'description' => __( 'Delete term %s in %s.', LOGDASH_DOMAIN )
			],
			'tag_updated'               => [
				'code'        => 1812,
				'description' => __( 'Updated tag %s from %s (%s) to %s (%s).', LOGDASH_DOMAIN )
			],
			'term_created'              => [
				'code'        => 1910,
				'description' => __( 'Created term %s in taxonomy %s.', LOGDASH_DOMAIN )
			],
			'term_deleted'              => [
				'code'        => 1911,
				'description' => __( 'Deleted term %s in taxonomy %s.', LOGDASH_DOMAIN )
			],
			'term_updated'              => [
				'code'        => 1912,
				'description' => __( 'Updated taxonomy %s from %s (%s) to %s (%s).', LOGDASH_DOMAIN )
			]
		];

		self::$codes = apply_filters( 'logdash_event_info_codes', self::$codes );
	}


	public static function get_code( string $key ): ?int {
		return self::$codes[ $key ]['code'] ?? null;
	}

	public static function get_description( string $key ): ?string {
		return self::$codes[ $key ]['description'] ?? null;
	}
}

// Initialize the event codes
EventInfo::init();
