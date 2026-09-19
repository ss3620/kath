<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.1.0.7
 */
if ( ! function_exists( 'wopb_backorder_config' ) ) {
	add_action( 'wp_loaded', 'wopb_backorder_config' );
	/**
	 * Initialize backorder configuration.
	 *
	 * @since v.1.0.7
	 * @return void
	 */
	function wopb_backorder_config() {
		$settings = wopb_function()->get_setting();
		if ( isset( $settings['wopb_backorder'] ) && $settings['wopb_backorder'] == 'true' ) {
			require_once WOPB_PATH . '/addons/backorder/Backorder.php';
			$obj = new \WOPB\Backorder();
			if ( ! isset( $settings['backorder_badge_radius'] ) ) {
				$obj->initial_setup();
			}
		}
	}
}
