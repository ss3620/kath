<?php
defined( 'ABSPATH' ) || exit;

/**
 * Custom Font Addons Feature
 *
 * @since v.4.0.0
 */
add_action( 'init', 'wopb_custom_font_init' );
function wopb_custom_font_init() {
	if ( wopb_function()->get_setting( 'wopb_custom_font' ) == 'true' ) {
		require_once WOPB_PATH . '/addons/custom_font/Custom_Font.php';
		new \WOPB\Custom_Font();
	}
}
