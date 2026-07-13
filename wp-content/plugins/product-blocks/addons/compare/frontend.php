<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.1.1.0
 */
add_action( 'wp_loaded', 'wopb_compare_init' );
function wopb_compare_init() {
	$settings = wopb_function()->get_setting();
	if ( isset( $settings['wopb_compare'] ) && $settings['wopb_compare'] == 'true' ) {
		require_once WOPB_PATH . '/addons/compare/Compare.php';
		$obj = new \WOPB\Compare();
		if ( ! isset( $settings['compare_column_border'] ) ) {
			$obj->initial_setup();
		}
	}
}
