<?php

/**
 * Plugin Name: Free Gifts for WooCommerce
 * Description: Offer Free Gifts to your customers for making purchases in your WooCommerce Shop.
 * Version: 12.1.0
 * Author: Flintop
 * Author URI: https://woo.com/vendor/flintop/
 * Text Domain: free-gifts-for-woocommerce
 * Domain Path: /languages
 * Woo: 4891274:b2b37ad71ffc9fa2a8bdc2eb14df650f
 * Requires Plugins: woocommerce
 * Tested up to: 6.8.1
 * WC tested up to: 9.8.5
 * WC requires at least: 3.0.0
 * Copyright: © 2019 Flintop
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/* Include once will help to avoid fatal error by load the files when you call init hook */
include_once ABSPATH . 'wp-admin/includes/plugin.php';

// Include main class file.
if (!class_exists('FP_Free_Gift')) {
	include_once 'inc/class-free-gift.php';
}

if (!function_exists('fgf_is_plugin_active')) {

	/**
	 * Is plugin active?
	 *
	 * @return bool
	 */
	function fgf_is_plugin_active() {
		if (fgf_is_valid_wordpress_version() && fgf_is_woocommerce_active() && fgf_is_valid_woocommerce_version()) {
			return true;
		}

		add_action('admin_notices', 'fgf_display_warning_message');

		return false;
	}

}

if (!function_exists('fgf_is_woocommerce_active')) {

	/**
	 * Function to check whether WooCommerce is active or not.
	 *
	 * @return bool
	 */
	function fgf_is_woocommerce_active() {
		$return = true;
		// This condition is for multi site installation.
		if (is_multisite() && !is_plugin_active_for_network('woocommerce/woocommerce.php') && !is_plugin_active('woocommerce/woocommerce.php')) {
			$return = false;
			// This condition is for single site installation.
		} elseif (!is_plugin_active('woocommerce/woocommerce.php')) {
			$return = false;
		}

		return $return;
	}

}

if (!function_exists('fgf_is_valid_wordpress_version')) {

	/**
	 * Is valid WordPress version?
	 *
	 * @return bool
	 */
	function fgf_is_valid_wordpress_version() {
		if (version_compare(get_bloginfo('version'), FP_Free_Gift::$wp_minimum_version, '<')) {
			return false;
		}

		return true;
	}

}

if (!function_exists('fgf_is_valid_woocommerce_version')) {

	/**
	 * Is valid WooCommerce version?
	 *
	 * @return bool
	 */
	function fgf_is_valid_woocommerce_version() {
		if (version_compare(get_option('woocommerce_version'), FP_Free_Gift::$wc_minimum_version, '<')) {
			return false;
		}

		return true;
	}

}

if (!function_exists('fgf_display_warning_message')) {

	/**
	 * Display the WooCommere is not active warning message.
	 */
	function fgf_display_warning_message() {
		$notice = '';

		if (!fgf_is_valid_wordpress_version()) {
			$notice = sprintf('This version of Free Gifts for WooCommerce requires WordPress %1s or newer.', FP_Free_Gift::$wp_minimum_version);
		} elseif (!fgf_is_woocommerce_active()) {
			$notice = 'Free Gifts for WooCommerce Plugin will not work until WooCommerce Plugin is Activated. Please Activate the WooCommerce Plugin.';
		} elseif (!fgf_is_valid_woocommerce_version()) {
			$notice = sprintf('This version of Free Gifts for WooCommerce requires WooCommerce %1s or newer.', FP_Free_Gift::$wc_minimum_version);
		}

		if ($notice) {
			echo '<div class="error">';
			echo '<p>' . wp_kses_post($notice) . '</p>';
			echo '</div>';
		}
	}

}

// Return if the plugin is not active.
if (!fgf_is_plugin_active()) {
	return;
}

// Define constant.
if (!defined('FGF_PLUGIN_FILE')) {
	define('FGF_PLUGIN_FILE', __FILE__);
}

// Return initiated free gifts main class object.
if (!function_exists('FGF')) {

	function FGF() {
		return FP_Free_Gift::instance();
	}

}

// Initialize the plugin.
FGF();
