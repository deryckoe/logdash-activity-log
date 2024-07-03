<?php

namespace LogDash;

use LogDash\Actions\RemoveExpiredLog;
use LogDash\Actions\ResetLog;
use LogDash\Admin\EventsAdminPage;
use LogDash\Admin\Settings;
use LogDash\API\Activation;
use LogDash\API\RestEndpoints;
use LogDash\Hooks\Core;
use LogDash\Hooks\Files;
use LogDash\Hooks\LearnDash;
use LogDash\Hooks\Meta;
use LogDash\Hooks\Plugins;
use LogDash\Hooks\Posts;
use LogDash\Hooks\Taxonomies;
use LogDash\Hooks\Themes;
use LogDash\Hooks\Users;

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
			EventsAdminPage::class,
			Settings::class,
			ResetLog::class,
			RemoveExpiredLog::class,
			RestEndpoints::class,

			Core::class,
			Plugins::class,
			Themes::class,
			Users::class,
			Files::class,
			Taxonomies::class,
			Posts::class,
			LearnDash::class,
		];

		$integrations = apply_filters( 'logdash_integrations', [] );

		foreach ( $dependencies as $dependency ) {
			( new $dependency )->init();
		}

		foreach ( $integrations as $integration ) {
			( new $integration )->init();
		}
	}

	function adminAssets() {
		$js_dependencies = [ 'wp-api', 'wp-element' ];
		wp_enqueue_script( 'logdash-activity-log', LOGDASH_URL . 'assets/build/index.js', $js_dependencies, LOGDASH_VERSION, true );
		wp_enqueue_style( 'logdash-activity-log', LOGDASH_URL . 'assets/build/index.css', [], LOGDASH_VERSION );
		wp_enqueue_script( 'logdash-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js', [ 'jquery' ] );
		wp_enqueue_style( 'logdash-select2', 'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css' );
	}

	function assets() {
		wp_enqueue_style( 'logdash-activity-log', LOGDASH_URL . 'assets/build/index.css', [], LOGDASH_VERSION );
	}

}