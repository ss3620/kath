<?php
/**
 * Plugin Name: WowAddons – Product Addons and Product Options With Custom Fields
 * Description: The ultimate WooCommerce product addons plugin to add extra product options, including radio buttons, checkboxes, file uploads, text areas, and more!
 * Version:     1.8.4
 * Author:      WPXPO
 * Author URI:  https://www.wpxpo.com/about
 * Text Domain: product-addons
 * Requires at least: 6.8
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * License:     GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package WowAddons
 */

use PRAD\Includes\Analytics;
use PRAD\Includes\Blocks\Blocks_Bootstrap;
use PRAD\Includes\Common\Functions;
use PRAD\Includes\Initialization;

defined( 'ABSPATH' ) || exit;

// WordPress refuses to activate the plugin below "Requires PHP", but an active site can
// still be moved to an older PHP. Stop here, before any class file is parsed.
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %s: current PHP version */
						__( 'WowAddons requires PHP 7.4 or newer, and this site runs PHP %s. Ask your host to upgrade PHP; WowAddons stays inactive until then.', 'product-addons' ),
						PHP_VERSION
					)
				)
			);
		}
	);
	return;
}

// Define Vars.
define( 'PRAD_VER', '1.8.4' );
define( 'PRAD_URL', plugin_dir_url( __FILE__ ) );
define( 'PRAD_BASE', plugin_basename( __FILE__ ) );
define( 'PRAD_PATH', plugin_dir_path( __FILE__ ) );

spl_autoload_register( 'prad_autoloader' );

if ( ! function_exists( 'product_addons' ) ) {
	/**
	 * Returns an instance of the Functions class for product addons.
	 *
	 * @return Functions
	 */
	function product_addons() { //phpcs:ignore
		return new Functions();
	}
}

new Analytics();
add_action( 'plugins_loaded', 'prad_init', 10 );

register_activation_hook( __FILE__, 'prad_activate' );
register_deactivation_hook( __FILE__, 'prad_clear_cleanup_cron' );

/**
 * Initializes the plugin by creating Initialization instance and bootstrapping blocks.
 */
function prad_init() {
	// "Requires Plugins" stops activation without WooCommerce, but WooCommerce can still
	// go missing later (deleted over FTP, network-deactivated). Explain instead of failing.
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'prad_woocommerce_missing_notice' );
		return;
	}

	new Initialization();
	$bootstrap = Blocks_Bootstrap::get_instance();
	$bootstrap->init();
}

/**
 * Shows an error notice while WooCommerce is not active.
 */
function prad_woocommerce_missing_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	printf(
		'<div class="notice notice-error"><p>%s</p></div>',
		esc_html__( 'WowAddons needs WooCommerce. Install and activate WooCommerce to use it.', 'product-addons' )
	);
}

/**
 * Runs on activation: creates the stats tables and schedules the cleanup cron.
 */
function prad_activate() {
	Analytics::create_tables();
	prad_schedule_cleanup_cron();
}

/**
 * Schedules the daily upload-cleanup cron event on activation.
 */
function prad_schedule_cleanup_cron() {
	if ( ! wp_next_scheduled( 'prad_cleanup_upload_files' ) ) {
		wp_schedule_event( time(), 'daily', 'prad_cleanup_upload_files' );
	}
}

/**
 * Clears the daily upload-cleanup cron event on deactivation.
 */
function prad_clear_cleanup_cron() {
	wp_clear_scheduled_hook( 'prad_cleanup_upload_files' );
}

/**
 * Autoloader for PRAD namespace classes.
 *
 * @param string $class_name The fully-qualified class name.
 */
function prad_autoloader( $class_name ) {
	$namespace = 'PRAD\\';
	$base_dir  = trailingslashit( PRAD_PATH );

	$len = strlen( $namespace );
	if ( strncmp( $namespace, $class_name, $len ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class_name, $len );
	$segments       = explode( '\\', $relative_class );

	$file_name = array_pop( $segments );
	$subfolder = strtolower(
		implode(
			'/',
			array_map(
				function ( $segment ) {
					return str_replace( '_', '-', $segment );
				},
				$segments
			)
		)
	);

	$prefix    = ( strpos( $subfolder, 'traits' ) !== false ) ? 'trait-' : 'class-';
	$file_name = strtolower(
		preg_replace(
			'/([a-z])([A-Z])/',
			'$1-$2',
			str_replace( '_', '-', $file_name )
		)
	);

	$file = rtrim( $base_dir . $subfolder, '/' ) . '/' . $prefix . $file_name . '.php';

	if ( file_exists( $file ) ) {
		require_once $file;
	}
}
