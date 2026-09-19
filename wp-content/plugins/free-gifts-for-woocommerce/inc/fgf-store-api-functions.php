<?php

/**
 * Store API functions.
 * 
 * @since 11.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!function_exists('fgf_store_api_format_price')) {

	/**
	 * Format the price by money formatter.
	 * 
	 * @since 11.0.0
	 * @param float $price
	 * @return float
	 */
	function fgf_store_api_format_price( $price ) {
		return woocommerce_store_api_get_formatter('money')->format($price);
	}

}

if (!function_exists('fgf_get_store_api_cart_notices')) {

	/**
	 * Get the store API cart notices.
	 * 
	 * @since 11.0.0
	 * @return array
	 */
	function fgf_get_store_api_cart_notices() {
		/**
		 * This hook is used to alter the store API cart notices.
		 * 
		 * @since 11.0.0
		 */
		$notices = apply_filters('fgf_store_api_cart_notices', array_merge((array) FGF_Notices_Handler::get_cart_gift_notice(), FGF_Notices_Handler::get_cart_gift_eligible_notices()));

		return array_filter($notices);
	}

}

if (!function_exists('fgf_get_store_api_checkout_notices')) {

	/**
	 * Get the store API checkout notices.
	 * 
	 * @since 11.0.0
	 * @return array
	 */
	function fgf_get_store_api_checkout_notices() {
		/**
		 * This hook is used to alter the store API checkout notices.
		 * 
		 * @since 11.0.0
		 */
		$notices = apply_filters('fgf_store_api_checkout_notices', array_merge(FGF_Notices_Handler::get_checkout_gift_notices(), FGF_Notices_Handler::get_checkout_gift_eligible_notices()));

		return array_filter($notices);
	}

}

if (!function_exists('fgf_get_store_api_snackbar_notices')) {

	/**
	 * Get the store API snack bar notices.
	 * 
	 * @since 11.0.0
	 * @return array
	 */
	function fgf_get_store_api_snackbar_notices() {
		/**
		 * This hook is used to alter the store API snack bar notices.
		 * 
		 * @since 11.0.0
		 */
		$notices = apply_filters('fgf_store_api_snackbar_notices', FGF_Gift_Products_Handler::add_notices());

		return array_filter($notices);
	}

}

if (!function_exists('fgf_is_block_cart')) {

	/**
	 * Is a block cart page?.
	 *
	 * @since 11.0.0
	 * @return boolean
	 */
	function fgf_is_block_cart() {
		static $is_block_cart;
		if (isset($is_block_cart)) {
			return $is_block_cart;
		}

		global $post;
		$is_singular = true;
		if (!is_a($post, 'WP_Post')) {
			$is_singular = false;
		}

		// Consider as block cart while the request call via Store API.
		if (isset($GLOBALS['wp']->query_vars['rest_route']) && false !== strpos($GLOBALS['wp']->query_vars['rest_route'], '/wc/store/v1')) {
			return true;
		}

		$is_block_cart = $is_singular && has_block('woocommerce/cart', $post);

		return $is_block_cart;
	}

}

if (!function_exists('fgf_is_block_checkout')) {

	/**
	 * Is a block checkout page?.
	 *
	 * @since 11.0.0
	 * @return boolean
	 */
	function fgf_is_block_checkout() {
		static $is_block_checkout;
		if (isset($is_block_checkout)) {
			return $is_block_checkout;
		}

		global $post;
		$is_singular = true;
		if (!is_a($post, 'WP_Post')) {
			$is_singular = false;
		}

		// Consider as block checkout while the request call via Store API.
		if (isset($GLOBALS['wp']->query_vars['rest_route']) && false !== strpos($GLOBALS['wp']->query_vars['rest_route'], '/wc/store/v1/cart')) {
			return true;
		}

		$is_block_checkout = $is_singular && has_block('woocommerce/checkout', $post);

		return $is_block_checkout;
	}

}
