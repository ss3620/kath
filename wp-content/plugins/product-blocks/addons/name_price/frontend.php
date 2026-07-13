<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.4.0.0
 */
add_action( 'wp_loaded', 'wopb_name_price_init' );
function wopb_name_price_init() {
	if ( wopb_function()->get_setting( 'wopb_name_price' ) == 'true' ) {
		require_once WOPB_PATH . '/addons/name_price/NamePrice.php';
		new \WOPB\NamePrice();
	}
}
