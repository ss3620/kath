<?php
/*
Plugin Name: Interparcel
Plugin URI: https://www.interparcel.com
Version: 1.5
Description: Automate shipping from WooCommerce with Interparcel's quoting and fulfilment solution.
Author: Interparcel
Author URI: https://www.interparcel.com

WC tested up to: 8.3
*/

if (!defined('WPINC')) {
    die;
}


if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return false;
}


register_activation_hook( __FILE__, 'interparcel_activation' );


function interparcel_activation() {
	if (!is_plugin_active('woocommerce/woocommerce.php')) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		$message = sprintf( '<br /><br />%s', __( 'Install and activate the WooCommerce plugin first.', 'interparcel_wc') );
		$message = __( 'Sorry! In order to use the freight for WooCommerce plugin you need to do the following:', 'interparcel_wc' ) . $message;
		wp_die( $message, 'Interparcel', array( 'back_link' => true ) );
	}
}

if (!defined('INTERPARCEL_PLUGIN_DIR')) {
	define('INTERPARCEL_PLUGIN_DIR', untrailingslashit(dirname(__FILE__)));
}


require_once (INTERPARCEL_PLUGIN_DIR.'/quote.php');


function add_interparcel_method($methods) {
  $methods["interparcel"] = 'WC_IP';
  return $methods;
}

//Settings link on plugins page 
function ip_settings_link($links) {
  $links[] = '<a href="admin.php?page=wc-settings&tab=shipping">Settings</a>';
  return $links;
}


add_action( 'before_woocommerce_init', function() {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
	}
} );

add_action('woocommerce_shipping_init', 'add_interparcel_zone');
add_filter('woocommerce_shipping_methods', 'add_interparcel_method' );
add_filter('woocommerce_shipping_calculator_enable_city','__return_true');
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'ip_settings_link');