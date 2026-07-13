<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.1.1.0
 */
add_action( 'init', 'wopb_templates_init' );
function wopb_templates_init() {
	if ( wopb_function()->get_setting( 'wopb_templates' ) == 'true' ) {
		require_once WOPB_PATH . '/addons/templates/Saved_Templates.php';
		require_once WOPB_PATH . '/addons/templates/Shortcode.php';
		new \WOPB\Saved_Templates();
		new \WOPB\Shortcode();
	}
}

/**
 * Function to load css in elementor for using shortcode
 */
add_action( 'elementor/frontend/after_enqueue_styles', 'wopb_add_css_in_elementor_page' );
function wopb_add_css_in_elementor_page() {
	wp_register_style( 'wopb-style', WOPB_URL . 'assets/css/blocks.style.css', array(), WOPB_VER );
	wp_enqueue_style( 'wopb-style' );
}
