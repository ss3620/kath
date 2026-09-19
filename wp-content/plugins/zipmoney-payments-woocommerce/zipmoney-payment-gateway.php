<?php

/**
 * Plugin Name:       Zip - WooCommerce
 * Plugin URI:        https://wordpress.org/plugins/zipmoney-woocommerce-plugin/
 * Description:       Sell more online & in-store with Zip.
 * Give your customers the power to pay later, interest free and watch your sales grow.
 * Take advantage of our fast-growing customer base, proven revenue uplift, fast and simple integration.
 * Version:           2.3.34
 * Author:            Zip
 * Author URI:        https://www.zip.co/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Github URI:        https://github.com/zipMoney/woocommerce/
 * WC tested up to:   10.8
 *
 * @version  2.3.34
 * @package  Zip
 * @author   Zip
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}
register_activation_hook( __FILE__, 'zip_plugin_activation' );
require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
define( 'WOOCOMMERCE_GATEWAY_ZIPMONEY_VERSION', '2.3.34' );
define( 'WOOCOMMERCE_GATEWAY_ZIPMONEY_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WOOCOMMERCE_GATEWAY_ZIPMONEY_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
/**
 * Add zipMoney gateway class to hook
 *
 * @param $methods
 * @return array
 */
function add_zipmoney_gateway_class( $methods ) {
	$methods[] = 'WC_Zipmoney_Payment_Gateway';
	return $methods;
}

/**
 * install tokenisation table
 */
function zip_plugin_activation() {
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();
	$table_name      = $wpdb->prefix . 'zip_tokenisation';
	$customer_table  = $wpdb->prefix . 'users';
	$sql             = "CREATE TABLE `$table_name` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `customer_id` bigint(20) unsigned NOT NULL,
    `token` varchar(220) DEFAULT NULL,
    PRIMARY KEY(id),
    INDEX customer_id(customer_id),
    FOREIGN KEY(customer_id) REFERENCES $customer_table(id) ON UPDATE CASCADE ON DELETE CASCADE
    ) $charset_collate ;
    ";
	if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) != $table_name ) {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}


/**
 * Instantiates the Zipmoney Payment Gateway class and then
 * calls its run method officially starting up the plugin.
 */
function run_zipmoney_payment_gateway() {
	// Include the vendor repositories
	require_once plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';

	/**
	 * Include the core class responsible for loading all necessary components of the plugin.
	 */

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) {
		// if the woocommerce payment gateway is not defined, then we won't activate the zipmoney payment gateway
		return;
	}

	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-zipmoney-payment-gateway.php';

	$wc_zipmoney_payment_gateway = new WC_Zipmoney_Payment_Gateway();
	$wc_zipmoney_payment_gateway->run();

	// After the class is initialized, we put the class to wc payment options
	add_filter( 'woocommerce_payment_gateways', 'add_zipmoney_gateway_class' );
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'zipmoney_plugin_action_links' );
}

/**
 * Adds plugin action links.
 *
 * @since 1.0.0
 * @version 4.0.0
 */
function zipmoney_plugin_action_links( $links ) {
	$plugin_links = array(
		'<a href="admin.php?page=wc-settings&tab=checkout&section=zipmoney">' . esc_html__( 'Settings', 'zipmoney-payment-gateway' ) . '</a>',
		'<a href="https://help.zipmoney.com.au/hc/en-us/categories/200306615-merchants" target="_blank">' . esc_html__( 'Support', 'zipmoney-payment-gateway' ) . '</a>',
		'<a href="https://zip-woocomerce.api-docs.io/v1/integration-steps" target="_blank">' . esc_html__( 'Integration Doc', 'zipmoney-payment-gateway' ) . '</a>',
	);

	return array_merge( $plugin_links, $links );
}

// load language file
function load_language() {
	load_plugin_textdomain( 'zippayment', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}

// Call the above function to begin execution of the plugin.
add_action( 'plugins_loaded', 'run_zipmoney_payment_gateway' );
add_action( 'plugins_loaded', 'load_language' );

// Hook in Blocks integration. This action is called in a callback on plugins loaded
add_action( 'woocommerce_blocks_loaded', 'woocommerce_gateway_zipmoney_woocommerce_block_support' );

function woocommerce_gateway_zipmoney_woocommerce_block_support() {
	if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
		require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-zipmoney-payment-gateway-blocks-support.php';
		add_action(
			'woocommerce_blocks_payment_method_type_registration',
			function ( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
				$payment_method_registry->register( new WC_Zipmoney_Payment_Gateway_Blocks_Support() );
			}
		);
	}
}

add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
    }
} );
