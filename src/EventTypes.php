<?php

namespace LogDash;

class EventTypes {

	const CREATED = 'created';
	const MODIFIED = 'modified';
	const LOGIN = 'login';
	const LOGOUT = 'logout';
	const FAILED_LOGIN = 'failed-login';
	const TRASHED = 'trashed';
	const RESTORED = 'restored';
	const DELETED = 'deleted';
	const ACTIVATED = 'activated';
	const DEACTIVATED = 'deactivated';
	const UPGRADED = 'upgraded';
	const INSTALLED = 'installed';
	const UPLOADED = 'uploaded';
	const OPENED = 'opened';
	const COMPLETED = 'completed';
	const UNCOMPLETED = 'uncompleted';
	const ENROLLED = 'enrolled';
	const UNENROLLED = 'unenrolled';
	const ADDED = 'added';
	const REMOVED = 'removed';

	public static function label( $code ) {
		$labels = [
			self::CREATED      => __( 'Created', LOGDASH_DOMAIN ),
			self::MODIFIED     => __( 'Modified', LOGDASH_DOMAIN ),
			self::LOGIN        => __( 'Login', LOGDASH_DOMAIN ),
			self::LOGOUT       => __( 'Logout', LOGDASH_DOMAIN ),
			self::FAILED_LOGIN => __( 'Failed Login', LOGDASH_DOMAIN ),
			self::TRASHED      => __( 'Trashed', LOGDASH_DOMAIN ),
			self::DELETED      => __( 'Deleted', LOGDASH_DOMAIN ),
			self::RESTORED     => __( 'Restored', LOGDASH_DOMAIN ),
			self::ACTIVATED    => __( 'Activated', LOGDASH_DOMAIN ),
			self::DEACTIVATED  => __( 'Deactivated', LOGDASH_DOMAIN ),
			self::UPGRADED     => __( 'Upgraded', LOGDASH_DOMAIN ),
			self::INSTALLED    => __( 'Installed', LOGDASH_DOMAIN ),
			self::UPLOADED     => __( 'Uploaded', LOGDASH_DOMAIN ),
			self::OPENED       => __( 'Opened', LOGDASH_DOMAIN ),
			self::COMPLETED    => __( 'Completed', LOGDASH_DOMAIN ),
			self::UNCOMPLETED  => __( 'Uncompleted', LOGDASH_DOMAIN ),
			self::ENROLLED     => __( 'Enrolled', LOGDASH_DOMAIN ),
			self::UNENROLLED   => __( 'Unenrolled', LOGDASH_DOMAIN ),
			self::ADDED        => __( 'Added', LOGDASH_DOMAIN ),
			self::REMOVED      => __( 'Removed', LOGDASH_DOMAIN ),
		];

		return $labels[ $code ];
	}

}