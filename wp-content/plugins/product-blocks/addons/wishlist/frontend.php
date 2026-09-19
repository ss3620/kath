<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.1.1.0
 */
add_action( 'wp_loaded', 'wopb_wishlist_init' );
function wopb_wishlist_init() {
	$settings = wopb_function()->get_setting();
	if ( isset( $settings['wopb_wishlist'] ) && $settings['wopb_wishlist'] == 'true' ) {
		require_once WOPB_PATH . '/addons/wishlist/Wishlist.php';
		$obj = new \WOPB\Wishlist();
		if ( ! isset( $settings['wishlist_column_border'] ) ) {
			$obj->initial_setup();
		}
	}
}
