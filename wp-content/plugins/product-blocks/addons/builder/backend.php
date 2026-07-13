<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'wopb_addons_config', 'wopb_builder_config' );
function wopb_builder_config( $config ) {
	$configuration          = array(
		'name'     => __( 'Woo Builder', 'product-blocks' ),
		'desc'     => __( 'Design all pages of your WooCommerce store from scratch or save time by importing premade templates.', 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-builder/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/woo-builder/',
		'video'    => 'https://www.youtube.com/watch?v=fu3DH7mEZ8U&ab_channel=WPXPO',
		'type'     => 'build',
		'priority' => 10,
	);
	$config['wopb_builder'] = $configuration;
	return $config;
}
