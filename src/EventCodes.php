<?php

namespace LogDash;

class EventCodes {

	const DEFAULT = 1001;

	const CORE_UPGRADED = 1010;
	const CORE_REINSTALLED = 1011;
	const CORE_DOWNGRADED = 1012;

	const PLUGIN_UPLOADED = 1110;
	const PLUGIN_DOWNLOADED = 1111;
	const PLUGIN_INSTALLED = 1112;
	const PLUGIN_UNINSTALLED = 1113;
	const PLUGIN_ACTIVATED = 1114;
	const PLUGIN_DEACTIVATED = 1115;
	const PLUGIN_UPGRADED = 1116;

	const THEME_UPLOADED = 1210;
	const THEME_DOWNLOADED = 1211;
	const THEME_INSTALLED = 1212;
	const THEME_UNINSTALLED = 1213;
	const THEME_ACTIVATED = 1214;
	const THEME_DEACTIVATED = 1215;
	const THEME_UPGRADED = 1216;

	const USER_LOGIN = 1310;
	const USER_LOGOUT = 1311;
	const USER_LOGIN_FAIL = 1312;
	const USER_DESTROYED_SESSIONS = 1313;
	const USER_CREATED = 1314;
	const USER_DELETED = 1315;
	const USER_UPDATED_PROFILE = 1316;
	const USER_UPDATED_META = 1317;
	const USER_UPDATED_EMAIL = 1318;
	const USER_UPDATED_LOGIN = 1319;
	const USER_UPDATE_PASSWORD = 1320;
	const USER_UPDATED_NICENAME = 1321;
	const USER_UPDATED_URL = 1322;
	const USER_UPDATED_STATUS = 1323;
	const USER_UPDATED_DISPLAYNAME = 1324;
	const USER_UPDATED_ROLE = 1325;

	const SETTING_CREATED = 1410;
	const SETTING_UPDATED = 1411;
	const SETTING_DELETED = 1412;

	const FILE_UPLOADED = 1510;
	const FILE_DELETED = 1511;
	const FILE_UPDATED = 1512;
	const FILE_UPDATED_PLUGIN = 1513;
	const FILE_UPDATED_THEME = 1514;

	const POST_OPENED = 1610;
	const POST_CREATED = 1611;
	const POST_UPDATED = 1612;
	const POST_UPDATED_STATUS = 1613;
	const POST_UPDATED_TITLE = 1614;
	const POST_UPDATED_CONTENT = 1615;
	const POST_UPDATED_CATEGORY = 1616;
	const POST_UPDATED_TAG = 1617;
	const POST_UPDATED_TERM = 1618;

	const POST_UPDATED_AUTHOR = 1619;
	const POST_UPDATED_PUBLISH_DATE = 1620;
	const POST_UPDATED_SLUG = 1621;
	const POST_UPDATED_EXCERPT = 1622;

	const POST_UPDATED_PARENT = 1623;

	const POST_UPDATED_MENU_ORDER = 1624;
	const POST_MOVED_TRASH = 1625;
	const POST_DELETED = 1626;
	const POST_RESTORED = 1627;
	const POST_PUBLISHED = 1628;
	const POST_SCHEDULED = 1629;
	const POST_UPDATED_THUMBNAIL = 1630;
	const POST_THUMBNAIL_ADDED = 1631;
	const POST_THUMBNAIL_REMOVED = 1632;
	const POST_TEMPLATE_UPDATED = 1633;
	const POST_VISIBILITY_UPDATED = 1634;
	const POST_STUCK = 1635;
	const POST_UNSTUCK = 1636;
	const POST_COMMENTS_ENABLED = 1637;
	const POST_COMMENTS_DISABLED = 1638;

	const POST_PINGS_ENABLED = 1639;
	const POST_PINGS_DISABLED = 1640;
	const POST_UNLOCKED = 1641;

	const POST_CREATED_META = 1642;
	const POST_UPDATED_META = 1643;
	const POST_DELETED_META = 1644;


	const CATEGORY_CREATED = 1710;
	const CATEGORY_DELETED = 1711;
	const CATEGORY_UPDATED = 1712;

	const TAG_CREATED = 1810;
	const TAG_DELETED = 1811;
	const TAG_UPDATED = 1812;

	const TERM_CREATED = 1910;
	const TERM_DELETED = 1911;
	const TERM_UPDATED = 1912;

	const COMPLETED = 2010;
	const UNCOMPLETED = 2011;
	const ENROLLED = 2012;
	const UNENROLLED = 2013;
	const ADDED = 2014;
	const REMOVED = 2015;

	private static array $description = [];

	public static function desc( $code ) {

		self::$description = [
			self::DEFAULT => __( 'Default event.', LOGDASH_DOMAIN ),

			self::CORE_UPGRADED    => __( 'Updated WordPress from %s to %s', LOGDASH_DOMAIN ),
			self::CORE_DOWNGRADED  => __( 'Downgraded WordPress from %s to %s', LOGDASH_DOMAIN ),
			self::CORE_REINSTALLED => __( 'Reinstalled WordPress %s', LOGDASH_DOMAIN ),

			self::PLUGIN_UPLOADED    => __( 'Plugin uploaded', LOGDASH_DOMAIN ),
			self::PLUGIN_DOWNLOADED  => __( 'Plugin downloaded', LOGDASH_DOMAIN ),
			self::PLUGIN_INSTALLED   => __( 'Installed %s %s plugin at %s', LOGDASH_DOMAIN ),
			self::PLUGIN_UNINSTALLED => __( 'Uninstalled %s %s plugin at %s', LOGDASH_DOMAIN ),
			self::PLUGIN_ACTIVATED   => __( 'Activated the plugin %s %s', LOGDASH_DOMAIN ),
			self::PLUGIN_DEACTIVATED => __( 'Deactivated de plugin %s %s deactivated', LOGDASH_DOMAIN ),
			self::PLUGIN_UPGRADED    => __( 'Upgraded plugin %s to version %s', LOGDASH_DOMAIN ),

			self::THEME_UPLOADED    => __( 'Uploaded theme %s %s', LOGDASH_DOMAIN ),
			self::THEME_DOWNLOADED  => __( 'Downloaded theme %s %s', LOGDASH_DOMAIN ),
			self::THEME_INSTALLED   => __( 'Installed theme %s %s', LOGDASH_DOMAIN ),
			self::THEME_UNINSTALLED => __( 'Uninstalled theme %s %s', LOGDASH_DOMAIN ),
			self::THEME_ACTIVATED   => __( 'Activated theme %s %s', LOGDASH_DOMAIN ),
			self::THEME_DEACTIVATED => __( 'Deactivated theme %s %s', LOGDASH_DOMAIN ),
			self::THEME_UPGRADED    => __( 'Upgraded theme %s to version %s', LOGDASH_DOMAIN ),

			self::USER_LOGIN               => __( 'User %s logged in.', LOGDASH_DOMAIN ),
			self::USER_LOGOUT              => __( 'User %s logged out.', LOGDASH_DOMAIN ),
			self::USER_LOGIN_FAIL          => __( 'Failed login attempt for %s.', LOGDASH_DOMAIN ),
			self::USER_DESTROYED_SESSIONS  => __( 'All %s user sessions were closed.', LOGDASH_DOMAIN ),
			self::USER_CREATED             => __( 'User %s created.', LOGDASH_DOMAIN ),
			self::USER_DELETED             => __( 'User %s deleted.', LOGDASH_DOMAIN ),
			self::USER_UPDATED_PROFILE     => __( 'User %s profile updated.', LOGDASH_DOMAIN ),
			self::USER_UPDATED_META        => __( 'Meta field %s updated from %s to %s for %s.', LOGDASH_DOMAIN ),
			self::USER_UPDATED_EMAIL       => __( 'Email updated from %s to %s for user %s', LOGDASH_DOMAIN ),
			self::USER_UPDATED_LOGIN       => __( 'User login updated.', LOGDASH_DOMAIN ),
			self::USER_UPDATE_PASSWORD     => __( 'Password updated for %s.', LOGDASH_DOMAIN ),
			self::USER_UPDATED_NICENAME    => __( 'User %s nice name updated.', LOGDASH_DOMAIN ),
			self::USER_UPDATED_URL         => __( 'User url updated from %s to %s for %s.', LOGDASH_DOMAIN ),
			self::USER_UPDATED_STATUS      => __( 'User %s status updated.', LOGDASH_DOMAIN ),
			self::USER_UPDATED_DISPLAYNAME => __( 'Changed display name from %s to %s for %s.', LOGDASH_DOMAIN ),
			self::USER_UPDATED_ROLE        => __( 'User %s role updated.', LOGDASH_DOMAIN ),

			self::SETTING_CREATED => __( 'Setting %s created.', LOGDASH_DOMAIN ),
			self::SETTING_UPDATED => __( 'Setting %s updated from %s to %s.', LOGDASH_DOMAIN ),
			self::SETTING_DELETED => __( 'Setting %s deleted.', LOGDASH_DOMAIN ),

			self::FILE_UPLOADED       => __( 'Uploaded file %s to %s.', LOGDASH_DOMAIN ),
			self::FILE_DELETED        => __( 'Deleted file %s from %s.', LOGDASH_DOMAIN ),
			self::FILE_UPDATED        => __( 'Attachment updated.', LOGDASH_DOMAIN ),
			self::FILE_UPDATED_PLUGIN => __( 'Modified the file %s with plugin editor.', LOGDASH_DOMAIN ),
			self::FILE_UPDATED_THEME  => __( 'Modified the file %s with theme editor.', LOGDASH_DOMAIN ),

			self::POST_OPENED               => __( 'Opened %s with title %s in the editor.', LOGDASH_DOMAIN ),
			self::POST_CREATED              => __( 'Created post %s.', LOGDASH_DOMAIN ),
			self::POST_UPDATED              => __( 'Post updated.', LOGDASH_DOMAIN ),
			self::POST_UPDATED_STATUS       => __( 'Updated the status of %s post to %s.', LOGDASH_DOMAIN ),
			self::POST_UPDATED_TITLE        => __( 'Updated title of post %s from %s to %s.', LOGDASH_DOMAIN ),
			self::POST_UPDATED_CONTENT      => __( 'Updated content for post %s.', LOGDASH_DOMAIN ),
			self::POST_UPDATED_CATEGORY     => __( 'Categories updated from %s to %s in post %s.', LOGDASH_DOMAIN ),
			self::POST_UPDATED_TAG          => __( 'Tags updated from %s to %s in post %s.', LOGDASH_DOMAIN ),
			self::POST_UPDATED_TERM         => __( 'Taxonomies updated from %s to %s in post %s.', LOGDASH_DOMAIN ),
			self::POST_UPDATED_AUTHOR       => __( 'Author updated from %s to %s in post %s', LOGDASH_DOMAIN ),
			self::POST_UPDATED_PUBLISH_DATE => __( 'Updated publish date from %s to %s for post %s', LOGDASH_DOMAIN ),
			self::POST_UPDATED_SLUG         => __( 'URL updated from %s to %s in post %s', LOGDASH_DOMAIN ),
			self::POST_UPDATED_EXCERPT      => __( 'Excerpt updated from %s to %s in post %s', LOGDASH_DOMAIN ),
			self::POST_UPDATED_PARENT       => __( 'Parent assigned to %s for post %s', LOGDASH_DOMAIN ),
			self::POST_UPDATED_MENU_ORDER   => __( 'Updated menu order from %s to %s in Post %s', LOGDASH_DOMAIN ),
			self::POST_UPDATED_THUMBNAIL    => __( 'Updated featured thumbnail to %s for %s.', LOGDASH_DOMAIN ),
			self::POST_THUMBNAIL_ADDED      => __( 'Added featured thumbnail %s for post %s.', LOGDASH_DOMAIN ),
			self::POST_THUMBNAIL_REMOVED    => __( 'Removed featured post thumbnail %s for post %s.', LOGDASH_DOMAIN ),
			self::POST_DELETED              => __( 'Permanently Deleted post %s.', LOGDASH_DOMAIN ),
			self::POST_MOVED_TRASH          => __( 'Moved post %s to trash.', LOGDASH_DOMAIN ),
			self::POST_RESTORED             => __( 'Restored post %s from trash.', LOGDASH_DOMAIN ),
			self::POST_PUBLISHED            => __( 'Published post %s.', LOGDASH_DOMAIN ),
			self::POST_SCHEDULED            => __( 'Post %s set as scheduled for %s.', LOGDASH_DOMAIN ),
			self::POST_TEMPLATE_UPDATED     => __( 'Post %s template set as %s.', LOGDASH_DOMAIN ),
			self::POST_VISIBILITY_UPDATED   => __( 'Modified post %s visibility from %s to %s.', LOGDASH_DOMAIN ),
			self::POST_STUCK                => __( 'Set the post %s as sticky.', LOGDASH_DOMAIN ),
			self::POST_UNSTUCK              => __( 'Removed the post %s from sticky', LOGDASH_DOMAIN ),
			self::POST_COMMENTS_ENABLED     => __( 'Enabled comments for post %s.', LOGDASH_DOMAIN ),
			self::POST_COMMENTS_DISABLED    => __( 'Disabled comments for post %s.', LOGDASH_DOMAIN ),
			self::POST_PINGS_ENABLED        => __( 'Enabled pings and trackbacks for post %s.', LOGDASH_DOMAIN ),
			self::POST_PINGS_DISABLED       => __( 'Disabled pings and trackbacks for post %s.', LOGDASH_DOMAIN ),
			self::POST_UNLOCKED             => __( 'Post %s has taken over.', LOGDASH_DOMAIN ),
			self::POST_CREATED_META         => __( 'Created meta field %s with value %s in %s.', LOGDASH_DOMAIN ),
			self::POST_UPDATED_META         => __( 'Updated meta field %s from %s to %s in %s.', LOGDASH_DOMAIN ),
			self::POST_DELETED_META         => __( 'Post meta field has been deleted.', LOGDASH_DOMAIN ),

			self::CATEGORY_CREATED => __( 'Created category %s.', LOGDASH_DOMAIN ),
			self::CATEGORY_DELETED => __( 'Deleted category %s.', LOGDASH_DOMAIN ),
			self::CATEGORY_UPDATED => __( 'Updated %s from %s (%s) to %s (%s).', LOGDASH_DOMAIN ),

			self::TAG_CREATED => __( 'Created term %s in %s.', LOGDASH_DOMAIN ),
			self::TAG_DELETED => __( 'Delete term %s in %s.', LOGDASH_DOMAIN ),
			self::TAG_UPDATED => __( 'Updated tag %s from %s (%s) to %s (%s).', LOGDASH_DOMAIN ),

			self::TERM_CREATED => __( 'Created term %s in taxonomy %s.', LOGDASH_DOMAIN ),
			self::TERM_DELETED => __( 'Deleted term %s in taxonomy %s.', LOGDASH_DOMAIN ),
			self::TERM_UPDATED => __( 'Updated taxonomy %s from %s (%s) to %s (%s).', LOGDASH_DOMAIN ),

			self::COMPLETED => __( 'Completed %s.', LOGDASH_DOMAIN ),
			self::UNCOMPLETED => __( '% changed to uncompleted.', LOGDASH_DOMAIN ),
			self::ENROLLED => __( 'Enrolled to %s', LOGDASH_DOMAIN ),
			self::UNENROLLED => __( 'Unenrolled to %s', LOGDASH_DOMAIN ),
			self::ADDED => __( 'Added to %s', LOGDASH_DOMAIN ),
			self::REMOVED => __( 'Removed from %s', LOGDASH_DOMAIN ),
		];

		return self::$description[ $code ];
	}

	public static function equal( $code_a, $code_b ): bool {
		return (int) $code_a === (int) $code_b;
	}
}