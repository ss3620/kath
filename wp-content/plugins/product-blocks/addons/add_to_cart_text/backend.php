<?php
defined( 'ABSPATH' ) || exit;

/**
 * Add to Cart Text Addons Initial Configuration
 *
 * @since v.4.0.0
 */
add_filter( 'wopb_addons_config', 'wopb_add_to_cart_text_config' );
function wopb_add_to_cart_text_config( $config ) {
	$configuration            = array(
		'name'     => __( 'Add to Cart Text', 'product-blocks' ),
		'desc'     => __( "Change any product type's default Add to Cart Button text in the Shop, Archive, and Product pages.", 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-change-add-to-cart-text/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/add-to-cart-text/',
		'type'     => 'checkout_cart',
		'priority' => 30,
	);
	$config['wopb_cart_text'] = $configuration;
	return $config;
}


/**
 * Add to Cart Text Addons Default Settings
 *
 * @since v.4.0.0
 * @param ARRAY | Default Congiguration
 * @return ARRAY
 */
add_filter( 'wopb_settings', 'get_add_to_cart_text_settings', 10, 1 );
function get_add_to_cart_text_settings( $config ) {
	$arr = array(
		'wopb_cart_text' => array(
			'label' => __( 'Add to Cart Text Settings', 'product-blocks' ),
			'attr'  => array(
				'wopb_cart_text'                  => array(
					'type'  => 'toggle',
					'value' => 'true',
					'label' => __( 'Enable Add to Cart text', 'product-blocks' ),
					'desc'  => __( 'Enable add to cart text on your website', 'product-blocks' ),
				),
				'cart_text_shop_archive_settings' => array(
					'type'  => 'section',
					'label' => __( 'Shop & Archive Page', 'product-blocks' ),
					'attr'  => array(
						'cart_text_archive_simple'   => array(
							'type'    => 'text',
							'label'   => __( 'Simple Product', 'product-blocks' ),
							'default' => 'Add to Cart',
						),
						'cart_text_archive_grouped'  => array(
							'type'    => 'text',
							'label'   => __( 'Grouped Product', 'product-blocks' ),
							'default' => 'View Products',
						),
						'cart_text_archive_external' => array(
							'type'    => 'text',
							'label'   => __( 'External/Affiliate Product', 'product-blocks' ),
							'default' => 'Buy Product',
						),
						'cart_text_archive_variable' => array(
							'type'    => 'text',
							'label'   => __( 'Variable Product', 'product-blocks' ),
							'default' => 'Select Options',
						),
					),
				),
				'cart_text_single_settings'       => array(
					'type'  => 'section',
					'label' => __( 'Single Product Page', 'product-blocks' ),
					'attr'  => array(
						'cart_text_single_simple'   => array(
							'type'    => 'text',
							'label'   => __( 'Simple Product', 'product-blocks' ),
							'default' => 'Add to Cart',
						),
						'cart_text_single_grouped'  => array(
							'type'    => 'text',
							'label'   => __( 'Grouped Product', 'product-blocks' ),
							'default' => 'Add to Cart',
						),
						'cart_text_single_external' => array(
							'type'    => 'text',
							'label'   => __( 'External/Affiliate Product', 'product-blocks' ),
							'default' => 'Buy Product',
						),
						'cart_text_single_variable' => array(
							'type'    => 'text',
							'label'   => __( 'Variable Product', 'product-blocks' ),
							'default' => 'Add to Cart',
						),
					),
				),
			),
		),
	);
	return array_merge( $config, $arr );
}
