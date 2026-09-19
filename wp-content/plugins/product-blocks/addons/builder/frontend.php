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
	function wopb_should_enable_wc_gallery_slider() {
		if ( class_exists( 'Flatsome_Default' ) ) {
			return false;
		}

		$theme = wp_get_theme();
		if ( $theme && $theme->get_template() === 'bridge' ) {
			return false;
		}

		return true;
	}

	function wopb_gallery_image_support() {
		if ( wopb_should_enable_wc_gallery_slider() ) {
			add_theme_support( 'wc-product-gallery-slider' );
		}
	}
}
