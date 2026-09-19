<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.3.2.0
 */
add_action( 'wp_loaded', 'wopb_cart_reserved_config' );
function wopb_cart_reserved_config() {
	if ( wopb_function()->get_setting( 'wopb_cart_reserved' ) == 'true' ) {
		require_once WOPB_PATH . '/addons/cart_reserved/CartReserved.php';
		new \WOPB\CartReserved();
	}
}
