<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.2.2.7
 */
add_action( 'wp_loaded', 'wopb_variation_swatches_init' );
function wopb_variation_swatches_init() {
	$settings = wopb_function()->get_setting();
	if ( isset( $settings['wopb_variation_swatches'] ) && $settings['wopb_variation_swatches'] == 'true' ) {
		require_once WOPB_PATH . '/addons/variation_swatches/VariationSwatches.php';
		$obj = new \WOPB\VariationSwatches();
		if ( ! isset( $settings['variation_label_typo'] ) ) {
			$obj->initial_setup();
		}
	}
}
