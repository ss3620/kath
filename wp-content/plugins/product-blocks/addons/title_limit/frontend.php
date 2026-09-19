<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.4.0.0
 */
add_action( 'wp_loaded', 'wopb_title_limit_init' );
function wopb_title_limit_init() {
	if ( wopb_function()->get_setting( 'wopb_title_limit' ) == 'true' ) {
		require_once WOPB_PATH . '/addons/title_limit/TitleLimit.php';
		new \WOPB\TitleLimit();
	}
}
