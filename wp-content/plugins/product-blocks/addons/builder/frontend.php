<?php
defined( 'ABSPATH' ) || exit;

add_action( 'init', 'wopb_builder_init' );
function wopb_builder_init() {
	if ( wopb_function()->get_setting( 'wopb_builder' ) == 'true' ) {
		require_once WOPB_PATH . '/addons/builder/Builder.php';
		require_once WOPB_PATH . '/addons/builder/Condition.php';
		require_once WOPB_PATH . '/addons/builder/RequestAPI.php';
		new \WOPB\Builder();
		new \WOPB\Condition();
		new \WOPB\RequestAPI();
	}
}

if ( wopb_function()->get_setting( 'wopb_builder' ) == 'true' ) {
	add_action( 'after_setup_theme', 'wopb_gallery_image_support' );
	function wopb_gallery_image_support() {
		if ( ! class_exists( 'Flatsome_Default' ) ) {
			add_theme_support( 'wc-product-gallery-slider' );
		}
	}
}
