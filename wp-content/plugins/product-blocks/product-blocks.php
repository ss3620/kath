<?php
/**
 * Plugin Name: WowStore – Store Builder & Product Blocks for WooCommerce
 * Description: <a href="https://www.wpxpo.com/product/wowstore/?utm_source=wowstore_org&utm_medium=wpxpo&utm_campaign=wstore-dashboard">WowStore</a> is an all-in-one solution for creating visually stunning and conversion-focused WooCommerce stores. The main and attractive features are WooCommerce Builder, Variation Swatches, Wishlist, Comparison, etc.
 * Version:     4.4.22
 * Author:      WowStore Team
 * Author URI:  https://www.wpxpo.com/product/wowstore/?utm_source=db-wstore-plugin&utm_medium=wpxpo&utm_campaign=wstore-dashboard
 * Text Domain: product-blocks
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Requires Plugins: woocommerce
 *
 * @package     WowStore
 **/

defined( 'ABSPATH' ) || exit;

// Define Constants.
define( 'WOPB_VER', '4.4.22' );
define( 'WOPB_URL', plugin_dir_url( __FILE__ ) );
define( 'WOPB_BASE', plugin_basename( __FILE__ ) );
define( 'WOPB_PATH', plugin_dir_path( __FILE__ ) );

// Language and Template Load.
add_action( 'init', 'wopb_language_n_template_load' );

/**
 * Loads the language files and templates for the Product Blocks plugin.
 *
 * This function is responsible for initializing the localization and template loading
 * mechanisms required by the plugin to support multiple languages and custom templates.
 *
 * @return void
 */
function wopb_language_n_template_load() {
	// Template Load .
	if ( class_exists( 'woocommerce' ) ) {
		require_once WOPB_PATH . 'classes/Templates.php';
		new \WOPB\Templates();
	}
}

// Common Functions.
if ( ! function_exists( 'wopb_function' ) ) {
	/**
	 * Retrieves the instance of the Functions class.
	 *
	 * This function ensures that the Functions class is loaded and returns its singleton instance.
	 *
	 * @return \WOPB\Functions
	 */
	function wopb_function() {
		require_once WOPB_PATH . 'classes/Functions.php';
		return \WOPB\Functions::get_instance();
	}
}

// Plugin Initialization and others.
// Include Wow Shipping Promotion.
if ( ! class_exists( '\WOPB\WowShippingPromotion', false ) ) {
	require_once WOPB_PATH . 'classes/class-wow-shipping-promotion.php';
}
new \WOPB\WowShippingPromotion();

require_once WOPB_PATH . 'classes/Initialization.php';
new \WOPB\Initialization();
