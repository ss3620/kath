<?php
/**
 * Pre-order Frontend Configuration
 *
 * This file handles the pre-order functionality configuration for the frontend.
 *
 * @package ProductBlocks
 * @since v.1.0.4
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'wopb_preorder_config' ) ) {
	/**
	 * Configure pre-order settings and load required files.
	 *
	 * @since v.1.0.4
	 * @return void
	 */
	function wopb_preorder_config() {
		$settings = wopb_function()->get_setting();
		if ( isset( $settings['wopb_preorder'] ) && 'true' === $settings['wopb_preorder'] ) {
			require_once WOPB_PATH . '/addons/preorder/Preorder.php';
			$obj = new \WOPB\Preorder();
			if ( ! isset( $settings['preorder_badge_radius'] ) ) {
				$obj->initial_setup();
			}
		}
	}
	add_action( 'wp_loaded', 'wopb_preorder_config' );
}
