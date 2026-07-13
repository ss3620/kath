<?php
defined( 'ABSPATH' ) || exit;

/**
 * Title Limit Addons Initial Configuration
 *
 * @since v.4.0.0
 */
add_filter( 'wopb_addons_config', 'wopb_title_limit_config' );
function wopb_title_limit_config( $config ) {
	$configuration = array(
		'name'     => __( 'Product Title Limit', 'product-blocks' ),
		'desc'     => __( 'Shorten the product title on the shop, archive, and product pages to keep your store organized.', 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/',
		// 'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-product-title-limit/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/product-title-limit/',
		'type'     => 'exclusive',
		'priority' => 10,
	);
	// live link missing
	$config['wopb_title_limit'] = $configuration;
	return $config;
}

/**
 * Title Limit Addons Default Settings
 *
 * @since v.4.0.0
 * @param ARRAY | Default Congiguration
 * @return ARRAY
 */
add_filter( 'wopb_settings', 'get_title_limit_settings', 10, 1 );
function get_title_limit_settings( $config ) {
	$arr = array(
		'wopb_title_limit' => array(
			'label' => __( 'Product Title Limit Settings', 'product-blocks' ),
			'attr'  => array(
				'container_1' => array(
					'type' => 'container',
					'attr' => array(
						'wopb_title_limit'    => array(
							'type'  => 'toggle',
							'value' => 'true',
							'label' => __( 'Enable Title Limit', 'product-blocks' ),
							'desc'  => __( 'Enable product title limit on your website', 'product-blocks' ),
						),
						'title_limit_archive' => array(
							'type'    => 'toggle',
							'label'   => __( 'Shop & Product Archive Page', 'product-blocks' ),
							'default' => 'yes',
							'desc'    => __( 'Show Short Title in Default Shop & Product Archive Page', 'product-blocks' ),
						),
						'title_limit_single'  => array(
							'type'    => 'toggle',
							'label'   => __( 'Single Product Page', 'product-blocks' ),
							'default' => 'yes',
							'desc'    => __( 'Show Short Title in Product Single Page', 'product-blocks' ),
						),
					),
				),
			),
		),
	);
	return array_merge( $config, $arr );
}
