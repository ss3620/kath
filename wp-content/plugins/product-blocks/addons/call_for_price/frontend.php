<?php
/**
 * Call for Price Frontend Configuration
 *
 * This file initializes the Call for Price addon functionality.
 *
 * @package WOPB
 * @since v.1.0.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.1.0.6
 */
if ( ! function_exists( 'wopb_call_for_price_config' ) ) {
	add_action( 'wp_loaded', 'wopb_call_for_price_config' );

	/**
	 * Initialize Call for Price configuration
	 *
	 * Loads the Call for Price addon if enabled in settings.
	 *
	 * @since v.1.0.6
	 * @return void
	 */
	function wopb_call_for_price_config() {
		$settings = wopb_function()->get_setting();
		if ( isset( $settings['wopb_call_for_price'] ) && 'true' === $settings['wopb_call_for_price'] ) {
			require_once WOPB_PATH . '/addons/call_for_price/CallForPrice.php';
			$obj = new \WOPB\CallForPrices();
			if ( ! isset( $settings['call_btn_radius_shop'] ) ) {
				$obj->initial_setup();
			}
		}
	}
}
