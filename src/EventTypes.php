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

	private static array $labels = [
		self::CREATED      => 'Created',
		self::MODIFIED     => 'Modified',
		self::LOGIN        => 'Login',
		self::LOGOUT       => 'Logout',
		self::FAILED_LOGIN => 'Failed Login',
		self::TRASHED      => 'Trashed',
		self::DELETED      => 'Deleted',
		self::RESTORED     => 'Restored',
		self::ACTIVATED    => 'Activated',
		self::DEACTIVATED  => 'Deactivated',
		self::UPGRADED     => 'Upgraded',
		self::INSTALLED    => 'Installed',
		self::UPLOADED     => 'Uploaded',
		self::OPENED       => 'Opened',
	];

	public static function label( $code ) {
		return self::$labels[ $code ];
	}

}