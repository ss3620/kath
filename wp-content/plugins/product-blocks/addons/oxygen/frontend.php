<?php
defined( 'ABSPATH' ) || exit;

add_action( 'wp_loaded', 'wopb_oxygen_builder' );
function wopb_oxygen_builder() {
	if ( wopb_function()->get_setting( 'wopb_oxygen' ) == 'true' ) {
		if ( class_exists( 'OxygenElement' ) ) {
			require_once WOPB_PATH . '/addons/oxygen/oxygen.php';
		}
	}
}
