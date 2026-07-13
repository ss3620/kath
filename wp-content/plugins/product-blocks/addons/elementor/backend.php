<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'wopb_addons_config', 'wopb_elementor_config' );
function wopb_elementor_config( $config ) {
	$configuration = array(
		'name'     => __( 'Elementor', 'product-blocks' ),
		'desc'     => __( 'It allows you to use WowStore blocks, patterns, and templates while building pages with the Elementor.', 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/',
		// 'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-page-builder-integrations/?utm_source=db-wstore-addons&utm_medium=builder_integration-demo&utm_campaign=wstore-dashboard',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/elementor-addon/',
		'type'     => 'integration',
		'priority' => 10,
	);
	// live link missing
	$config['wopb_elementor'] = $configuration;
	return $config;
}
