<?php
defined( 'ABSPATH' ) || exit;

/**
 * Product Video Addons Initial Configuration
 *
 * @since v.3.2.0
 */
add_filter( 'wopb_addons_config', 'wopb_product_video_setup' );
function wopb_product_video_setup( $config ) {
	$configuration                = array(
		'name'     => __( 'Product Video', 'product-blocks' ),
		'desc'     => __( "Display product-featured videos instead of featured images and grab users' attention to specific products.", 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-product-video/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/product-video/',
		'type'     => 'exclusive',
		'priority' => 60,
	);
	$config['wopb_product_video'] = $configuration;
	return $config;
}

/**
 * Require Main File
 *
 * @since v.3.2.0
 */
add_action( 'wp_loaded', 'wopb_product_video_load' );
function wopb_product_video_load() {
	if ( wopb_function()->get_setting( 'wopb_product_video' ) == 'true' ) {
		require_once WOPB_PATH . '/addons/product_video/ProductVideo.php';
		new \WOPB\ProductVideo();
	}
}
