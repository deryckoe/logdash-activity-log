<?php

namespace LogDash;

use LogDash\Admin\Settings;
use LogDash\API\Activation;
use LogDash\Hooks\Meta;
use LogDash\Hooks\Taxonomies;
use LogDash\Hooks\Core;
use LogDash\Hooks\Plugins;
use LogDash\Hooks\Posts;
use LogDash\Hooks\Themes;
use LogDash\Hooks\Users;
use LogDash\Hooks\Files;
use LogDash\Actions\RemoveExpiredLog;
use LogDash\Actions\ResetLog;
use LogDash\Admin\EventsPage;

class ActivityLog {

	private static $instance = null;

	public static function instance(): self {
		return self::$instance = self::$instance ?? new self();
	}

	public function init() {
		$this->hooks();
		$this->dependencies();
	}

	public function hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'adminAssets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'assets' ] );
	}

	public function dependencies() {

		$dependencies = [
			Activation::class,
			EventsPage::class,
			Settings::class,
			ResetLog::class,
			RemoveExpiredLog::class,

			Core::class,
			Plugins::class,
			Themes::class,
			Users::class,
			Files::class,
			Taxonomies::class,
			Posts::class,
			Meta::class,
			RestEndpoints::class
		];

		foreach ( $dependencies as $dependency ) {
			( new $dependency )->init();
		}
	}

	function adminAssets() {
		$js_dependencies = [ 'wp-api', 'wp-element' ];
		wp_enqueue_script( 'logdash-activity-log', LOGDASH_URL . 'assets/build/index.js', $js_dependencies, LOGDASH_VERSION, true );
		wp_enqueue_style( 'logdash-activity-log', LOGDASH_URL . 'assets/build/index.css', [], LOGDASH_VERSION );
	}

	function assets() {
		wp_enqueue_style( 'logdash-activity-log', LOGDASH_URL . 'assets/build/index.css', [], LOGDASH_VERSION );
	}

}