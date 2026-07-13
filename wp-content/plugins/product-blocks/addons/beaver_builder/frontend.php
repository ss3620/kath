<?php
defined( 'ABSPATH' ) || exit;

function wopb_productx_beaver_builder() {
	if ( wopb_function()->get_setting( 'wopb_beaver_builder' ) == 'true' ) {
		if ( class_exists( 'FLBuilder' ) ) {
			require_once WOPB_PATH . '/addons/beaver_builder/beaverbuilder.php';
		}
	}
}
add_action( 'init', 'wopb_productx_beaver_builder' );
