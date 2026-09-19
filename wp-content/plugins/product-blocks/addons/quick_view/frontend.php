<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.1.1.0
 */
add_action( 'wp_loaded', 'wopb_quickview_init' );
function wopb_quickview_init() {
	$settings = wopb_function()->get_setting();
	$mobile   = wp_is_mobile();
	if ( isset( $settings['wopb_quickview'] ) && $settings['wopb_quickview'] == 'true' ) {
		if (
			( $mobile && isset( $settings['quick_view_mobile_enable'] ) && $settings['quick_view_mobile_enable'] == 'yes' ) ||
			! $mobile
		) {
			require_once WOPB_PATH . '/addons/quick_view/Quickview.php';
			$obj = new \WOPB\Quickview();
			if ( ! isset( $settings['quick_view_modal_btn_bg'] ) ) {
				$obj->initial_setup();
			}
		}
	}
}
