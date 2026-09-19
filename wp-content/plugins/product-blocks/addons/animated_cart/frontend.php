<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.4.0.0
 */
add_action( 'wp_loaded', 'wopb_animated_cart_init' );
function wopb_animated_cart_init() {
	if ( wopb_function()->get_setting( 'wopb_animated_cart' ) == 'true' ) {
		require_once WOPB_PATH . '/addons/animated_cart/AnimatedCart.php';
		new \WOPB\AnimatedCart();
	}
}
