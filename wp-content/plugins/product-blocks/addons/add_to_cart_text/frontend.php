<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.4.0.0
 */
add_action( 'wp_loaded', 'wopb_add_to_cart_text_init' );
function wopb_add_to_cart_text_init() {
	if ( wopb_function()->get_setting( 'wopb_cart_text' ) == 'true' ) {
		require_once WOPB_PATH . '/addons/add_to_cart_text/CartText.php';
		new \WOPB\CartText();
	}
}
