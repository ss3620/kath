<?php
defined( 'ABSPATH' ) || exit;

/**
 * Animated Add to Cart Addons Initial Configuration
 *
 * @since v.4.0.0
 */
add_filter( 'wopb_addons_config', 'wopb_animated_cart_config' );
function wopb_animated_cart_config( $config ) {
	$configuration                = array(
		'name'     => __( 'Animated Add to Cart', 'product-blocks' ),
		'desc'     => __( 'Grab customers attention by animating the Add to Cart button on hover or in the loop.', 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-animated-add-to-cart-button/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/animated-add-to-cart/',
		'type'     => 'checkout_cart',
		'priority' => 40,
	);
	$config['wopb_animated_cart'] = $configuration;
	return $config;
}

/**
 * Animated Add to Cart Addons Default Settings
 *
 * @since v.4.0.0
 * @param ARRAY | Default Congiguration
 * @return ARRAY
 */
add_filter( 'wopb_settings', 'get_animated_cart_settings', 10, 1 );
function get_animated_cart_settings( $config ) {
	$arr = array(
		'wopb_animated_cart' => array(
			'label' => __( 'Animated Add to Cart Settings', 'product-blocks' ),
			'attr'  => array(
				'container_1' => array(
					'type' => 'container',
					'attr' => array(
						'wopb_animated_cart'      => array(
							'type'  => 'toggle',
							'value' => 'true',
							'label' => __( 'Enable Animated Add to Cart', 'product-blocks' ),
							'desc'  => __( 'Enable animated add to cart on your website', 'product-blocks' ),
						),
						'animated_cart_animation' => array(
							'type'    => 'select',
							'label'   => __( 'Animation', 'product-blocks' ),
							'options' => array(
								'click'  => __( 'Click', 'product-blocks' ),
								'zoom'   => __( 'Zoom In', 'product-blocks' ),
								'shake'  => __( 'Shake', 'product-blocks' ),
								'bounce' => __( 'Bounce', 'product-blocks' ),
								'wobble' => __( 'Wobble', 'product-blocks' ),
								'swing'  => __( 'Swing', 'product-blocks' ),
							),
							'default' => 'click',
						),
						'animated_cart_apply'     => array(
							'type'    => 'checkbox',
							'label'   => __( 'Applied on', 'product-blocks' ),
							'options' => array(
								'archive' => __( 'Shop & Archive Page', 'product-blocks' ),
								'single'  => __( 'Single Product Page', 'product-blocks' ),
							),
							'default' => array( 'single' ),
						),
						'animated_cart_interval'  => array(
							'type'    => 'select',
							'label'   => __( 'Animation Interval', 'product-blocks' ),
							'options' => array(
								'1'  => __( '1 Second', 'product-blocks' ),
								'2'  => __( '2 Second', 'product-blocks' ),
								'3'  => __( '3 Second', 'product-blocks' ),
								'4'  => __( '4 Second', 'product-blocks' ),
								'5'  => __( '5 Second', 'product-blocks' ),
								'no' => __( 'No Interval', 'product-blocks' ),
							),
							'default' => 'no',
						),
						'animated_cart_showcase'  => array(
							'type'    => 'select',
							'label'   => __( 'Animation Showcase', 'product-blocks' ),
							'options' => array(
								'loop'  => __( 'All Time (Loop)', 'product-blocks' ),
								'hover' => __( 'On Hover', 'product-blocks' ),
							),
							'default' => 'loop',
						),
					),
				),
			),
		),
	);
	return array_merge( $config, $arr );
}
