<?php
defined( 'ABSPATH' ) || exit;

/**
 * Product Video Addons Initial Configuration
 *
 * @since v.3.2.0
 */
add_filter( 'wopb_addons_config', 'wopb_product_video_init' );
function wopb_product_video_init( $config ) {
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
 * Product Video Addons Default Settings
 *
 * @since v.3.2.0
 * @param ARRAY | Default Congiguration
 * @return ARRAY
 */
add_filter( 'wopb_settings', 'get_product_video_opt', 10, 1 );
function get_product_video_opt( $config ) {
	$is_active = wopb_function()->get_setting( 'is_lc_active' );
	$arr       = array(
		'wopb_product_video' => array(
			'label' => __( 'Product Video Settings', 'product-blocks' ),
			'attr'  => array(
				'container_1' => array(
					'type' => 'container',
					'attr' => array(
						'wopb_product_video'    => array(
							'type'  => 'toggle',
							'value' => false,
							'label' => __( 'Enable Product Video', 'product-blocks' ),
							'desc'  => __( 'Enable product video on your website', 'product-blocks' ),
						),
						'product_video_archive' => array(
							'type'    => 'toggle',
							'label'   => __( 'Show on Shop & Archive', 'product-blocks' ),
							'default' => 'yes',
							'desc'    => __( 'Show Video on Shop & Archive Page', 'product-blocks' ),
							'pro'     => 'true',
						),
						'product_video_single'  => array(
							'type'    => 'toggle',
							'label'   => __( 'Show on Single Product Page', 'product-blocks' ),
							'default' => 'yes',
							'desc'    => __( 'Show video on Product Single Page', 'product-blocks' ),
						),
						'product_video_display' => array(
							'type'    => 'radio',
							'display' => 'inline-box',
							'label'   => __( 'Display', 'product-blocks' ),
							'options' => array(
								'video' => __( 'Direct Video Embedded', 'product-blocks' ),
								'icon'  => __( 'Icon On Image', 'product-blocks' ),
							),
							'default' => 'icon',
							'desc'    => __( 'Select Display Type', 'product-blocks' ),
						),
						'product_video_icon'    => array(
							'type'    => 'radio',
							'label'   => __( 'Icon', 'product-blocks' ),
							'display' => 'inline-box',
							'options' => array(
								'play_1' => wopb_function()->svg_icon( 'play_1' ),
								'play_2' => wopb_function()->svg_icon( 'play_2' ),
								'play_3' => wopb_function()->svg_icon( 'play_3' ),
								'play_4' => wopb_function()->svg_icon( 'play_4' ),
							),
							'default' => 'play_2',
							'desc'    => __( 'Play Icon on Feature Image', 'product-blocks' ),
							'depends' => array(
								'key'       => 'product_video_display',
								'condition' => '==',
								'value'     => 'icon',
							),
						),
					),
				),
			),
		),
	);
	return array_merge( $config, $arr );
}
