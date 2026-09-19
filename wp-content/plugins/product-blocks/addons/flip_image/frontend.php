<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.1.1.0
 */
add_action( 'wp_loaded', 'wopb_flipimage_init' );
function wopb_flipimage_init() {
	if ( wopb_function()->get_setting( 'wopb_flipimage' ) == 'true' ) {
		require_once WOPB_PATH . '/addons/flip_image/FlipImage.php';
		new \WOPB\FlipImage();
	}
}
