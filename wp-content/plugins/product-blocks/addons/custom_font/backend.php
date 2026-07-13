<?php
defined( 'ABSPATH' ) || exit;

/**
 * Custom Font Addons Initial Configuration
 *
 * @since v.4.0.0
 */
add_filter( 'wopb_addons_config', 'wopb_custom_font_config' );
function wopb_custom_font_config( $config ) {
	$configuration = array(
		'name'     => __( 'Custom Font', 'product-blocks' ),
		'desc'     => __( 'It allows you to upload custom fonts and use them on any WowStore blocks with all typographical options.', 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-custom-font/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/custom-fonts/',
		'type'     => 'build',
		'priority' => 50,
	);
	// live link missing
	$arr['wopb_custom_font'] = $configuration;
	return $arr + $config;
}
