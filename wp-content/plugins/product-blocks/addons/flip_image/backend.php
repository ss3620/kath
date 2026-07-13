<?php
defined( 'ABSPATH' ) || exit;

/**
 * Flip Image Addons Initial Configuration
 *
 * @since v.1.1.0
 */
add_filter( 'wopb_addons_config', 'wopb_flipimage_config' );
function wopb_flipimage_config( $config ) {
	$configuration = array(
		'name'     => __( 'Product Image Flipper', 'product-blocks' ),
		'desc'     => __( 'It allows you to display a different image of products when the shoppers of your store hover over a product.', 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/features/product-image-flipper/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/flip-image-settings/',
		'type'     => 'exclusive',
		'priority' => 30,
	);
	// live link missing
	$config['wopb_flipimage'] = $configuration;
	return $config;
}


/**
 * FlipImage Addons Default Settings Param
 *
 * @since v.1.1.0
 * @param ARRAY | Default Filter Congiguration
 * @return ARRAY
 */
add_filter( 'wopb_settings', 'wopb_get_flip_image_settings', 10, 1 );
function wopb_get_flip_image_settings( $config ) {
	$arr = array(
		'wopb_flipimage' => array(
			'label' => __( 'Flip Image', 'product-blocks' ),
			'attr'  => array(
				'flipimage_heading'         => array(
					'type'  => 'heading',
					'label' => __( 'Flip Image Settings', 'product-blocks' ),
				),
				'flip_image_basic_settings' => array(
					'type' => 'container',
					'attr' => array(
						'wopb_flipimage'              => array(
							'type'  => 'toggle',
							'value' => 'true',
							'label' => __( 'Enable Image Flipper', 'product-blocks' ),
							'desc'  => __( 'Enable product image flipper on your website', 'product-blocks' ),
						),
						'flip_image_source'           => array(
							'type'    => 'radio',
							'label'   => __( 'Flip Image Source', 'product-blocks' ),
							'display' => 'inline-box',
							'options' => array(
								'gallery' => __( 'First Image From Gallery', 'product-blocks' ),
								'feature' => __( 'Specific Flip Image Selection Option', 'product-blocks' ),
							),
							'default' => 'gallery',
						),

						'flip_animation_type'         => array(
							'type'    => 'radio',
							'label'   => __( 'Product Flip Animation Type', 'product-blocks' ),
							'display' => 'inline-box',
							'options' => array(
								'none'               => __( 'None', 'product-blocks' ),
								'fade_in'            => __( 'Fade In', 'product-blocks' ),
								'zoom_in'            => __( 'Zoom In', 'product-blocks' ),
								'fade_in_zoom_in'    => __( 'Fade In & Zoom In', 'product-blocks' ),
								'slide_right_in'     => __( 'Slide From Right', 'product-blocks' ),
								'flip_right_to_left' => __( 'Flip Right to Left', 'product-blocks' ),
							),
							'default' => 'fade_in',
							'desc'    => __( 'Select Option For What You Want to Show on Product After Mouse Hover.', 'product-blocks' ),
						),

						'flip_group_variable_disable' => array(
							'type'    => 'toggle',
							'label'   => __( 'Disable For Group & Variable Product', 'product-blocks' ),
							'default' => '',
							'desc'    => __( 'This option will disable image flipper to group & variable products.', 'product-blocks' ),
						),
						'flip_mobile_device_disable'  => array(
							'type'    => 'toggle',
							'label'   => __( 'Disable For Mobile Device', 'product-blocks' ),
							'default' => '',
							'desc'    => __( 'This option will disable image flipper to mobile devices.', 'product-blocks' ),
						),
					),
				),
			),
		),
	);

	return array_merge( $config, $arr );
}
