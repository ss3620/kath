<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://revenuehunt.com/
 * @since             1.0.0
 * @package           Product_Recommendation_Quiz_For_Ecommerce
 *
 * @wordpress-plugin
 * Plugin Name:       Product Recommendation Quiz for eCommerce
 * Plugin URI:        https://revenuehunt.com/product-recommendation-quiz-woocommerce/
 * Description:       Advise and delight your customers by engaging them with a personal shopper experience on your store, guiding your customers from start to cart and helping them find the products that best match their needs.
 * Version:           2.5.6
 * Author:            RevenueHunt
 * Author URI:        https://revenuehunt.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       product-recommendation-quiz-for-ecommerce
 * Domain Path:       /languages
 * Requires at least: 6.0
 * Tested up to:      7.0
 * Requires PHP:      7.4
 */
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PRQ_PLUGIN_VERSION', '2.5.6' );

/**
 * Option keys used by the plugin.
 * Centralized here to ensure DRY - activator, deactivator, and uninstall all use these.
 *
 * @since 2.2.15
 */
define( 'PRQ_OPTION_SHOP_HASHID', 'rh_shop_hashid' );
define( 'PRQ_OPTION_API_KEY', 'rh_api_key' );
define( 'PRQ_OPTION_DOMAIN', 'rh_domain' );
define( 'PRQ_OPTION_TOKEN', 'rh_token' );

/**
 * Self-contained class autoloader (replaces the manual require_once chains).
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-product-recommendation-quiz-for-ecommerce-autoloader.php';
Product_Recommendation_Quiz_For_Ecommerce_Autoloader::register();

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-product-recommendation-quiz-for-ecommerce-activator.php
 */
function product_recommendation_quiz_for_ecommerce_activate() {
	Product_Recommendation_Quiz_For_Ecommerce_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-product-recommendation-quiz-for-ecommerce-deactivator.php
 */
function product_recommendation_quiz_for_ecommerce_deactivate() {
	Product_Recommendation_Quiz_For_Ecommerce_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'product_recommendation_quiz_for_ecommerce_activate' );
register_deactivation_hook( __FILE__, 'product_recommendation_quiz_for_ecommerce_deactivate' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function product_recommendation_quiz_for_ecommerce_run() {

	$plugin = new Product_Recommendation_Quiz_For_Ecommerce();
	$plugin->run();
}

add_action(
	'rest_api_init',
	function () {
		$controller = new Product_Recommendation_Quiz_For_Ecommerce_Rest_Set_Token_Controller();
		$controller->register_routes();
	}
);

/**
 * Declare compatibility with WooCommerce feature flags.
 *
 * Hooked on before_woocommerce_init. Declares HPOS (custom_order_tables) and the
 * Cart/Checkout Blocks so WooCommerce does not flag the plugin as incompatible
 * on the Features screen. Both are accurate: the plugin stores no order data and
 * adds nothing to the cart/checkout flow. No-ops on WooCommerce versions without
 * the FeaturesUtil API.
 *
 * @since 2.4.0
 * @return void
 */
function product_recommendation_quiz_for_ecommerce_declare_woo_compatibility() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
	}
}
add_action( 'before_woocommerce_init', 'product_recommendation_quiz_for_ecommerce_declare_woo_compatibility' );

// Guard allows the unit-test harness to load this file (function/constant
// definitions) without booting the full plugin. PRQ_SKIP_BOOTSTRAP is never
// defined in production, so the plugin runs exactly as before.
if ( ! defined( 'PRQ_SKIP_BOOTSTRAP' ) ) {
	product_recommendation_quiz_for_ecommerce_run();
}
