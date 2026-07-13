<?php
defined( 'ABSPATH' ) || exit;

add_action( 'plugins_loaded', 'wopb_elementor_init' );
function wopb_elementor_init() {
	if ( wopb_function()->get_setting( 'wopb_elementor' ) == 'true' ) {
		if ( did_action( 'elementor/loaded' ) ) {
			require_once WOPB_PATH . '/addons/elementor/Elementor.php';
			Elementor_WOPB_Extension::instance();
		}
	}
}
