<?php
defined( 'ABSPATH' ) || exit;

add_filter( 'wopb_addons_config', 'wopb_oxygen_config' );
function wopb_oxygen_config( $config ) {
	$configuration = array(
		'name'     => __( 'Oxygen Builder', 'product-blocks' ),
		'desc'     => __( 'Create custom designs and save them as saved templates to use with the Oxygen Builder.', 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/',
		// 'live'     => 'https://www.wpxpo.com/product/wowstore/woocommerce-page-builder-integrations/?utm_source=db-wstore-addons&utm_medium=builder_integration-demo&utm_campaign=wstore-dashboard',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/oxygen-builder-addon/',
		'type'     => 'integration',
		'priority' => 30,
	);
	// live link missing
	$config['wopb_oxygen'] = $configuration;
	return $config;
}
