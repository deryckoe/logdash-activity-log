<?php
/**
 *
 * @link              https://deryckoe.com/logdash
 * @package           Activity_Log
 *
 * @wordpress-plugin
 * Plugin Name:       LogDash Activity Log
 * Plugin URI:        https://deryckoe.com/logdash
 * Description:       The ultimate tool for tracking and analyzing the behavior of your website's users.
 * Version:           1.0
 * Author:            Deryck Oñate
 * Author URI:        http://deryckoe.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       logdash
 * Domain Path:       /languages
 */

// If this file is called directly, abort.

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 */
define( 'LOGDASH_VERSION', '1.0' );

/**
 * Plugin paths
 */
define( 'LOGDASH_PLUGIN_FILE', __FILE__ );
define( 'LOGDASH_DIR', plugin_dir_path( __FILE__ ) );
define( 'LOGDASH_URL', plugins_url( '/', __FILE__ ) );
define( 'LOGDASH_LANGUAGES_DIR', basename( dirname( __FILE__ ) ) );
define( 'LOGDASH_DATE_FORMAT', 'd/m/Y' );
define( 'LOGDASH_TIME_FORMAT', 'g:i a' );
define( 'LOGDASH_TEMPLATES', LOGDASH_DIR . '/views' );
define( 'LOGDASH_DOMAIN', 'logdash' );

load_plugin_textdomain( LOGDASH_DOMAIN, false, LOGDASH_LANGUAGES_DIR . '/languages' );

// Composer autoload
require __DIR__ . '/vendor/autoload.php';

LogDash\ActivityLog::instance()->init();