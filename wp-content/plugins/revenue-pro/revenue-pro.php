<?php
/**
 * Plugin Name: WowRevenue Pro
 * Plugin URI: https://wordpress.org/plugins/revenue
 * Description: The Pro Version of WowRevenue - The most advanced WooCommerce plugin. Build powerful sales campaigns and deploy them on your online stores without limits.
 * Version: 2.1.4
 * Author: WowRevenue
 * Author URI: https://wowrevenue.com/
 * License: GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: revenue-pro
 * Domain Path: /languages
 */

// If the file is called directly, abort it.

defined( 'ABSPATH' ) || exit;


if ( ! defined( 'REVENUE_PRO_FILE' ) ) {
	define( 'REVENUE_PRO_FILE', __FILE__ );
}

if ( ! defined( 'REVENUE_PRO_PATH' ) ) {
	define( 'REVENUE_PRO_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'REVENUE_PRO_URL' ) ) {
	define( 'REVENUE_PRO_URL', plugin_dir_url( __FILE__ ) );
}

// Include the main Revenue class.
if ( ! class_exists( 'RevenuePro', false ) ) {
	include_once REVENUE_PRO_PATH . '/includes/class-revenue-pro.php';
}
if ( ! class_exists( '\RevenuePro\Revenue_Pro_Install', false ) ) {
	require_once REVENUE_PRO_PATH . 'includes/class-revenue-pro-install.php';
}

// Include Revenue Functions.
if ( ! class_exists( '\RevenuePro\Revenue_Pro_Functions', false ) ) {
	require_once REVENUE_PRO_PATH . '/includes/class-revenue-pro-functions.php';
}

if ( ! function_exists( 'revenue_pro' ) ) {
	function revenue_pro() {
		if ( ! isset( $GLOBALS['revenue_functions'] ) ) {
			$GLOBALS['revenue_functions'] = new \RevenuePro\Revenue_Pro_Functions(); // Using runtime cache
		}
		return $GLOBALS['revenue_functions'];
	}
}


/**
 * Loads Revenue
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'revenue_pro_run' ) ) {
	function revenue_pro_run() {
		return Revenue_Pro::init();
	}
}

// Kick off.
revenue_pro_run();
