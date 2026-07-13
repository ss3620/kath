<?php
defined( 'ABSPATH' ) || exit;

/**
 * Variation Swatches Initial Configuration
 *
 * @since v.2.2.7
 */
add_filter( 'wopb_addons_config', 'wopb_variation_swatches_config' );
function wopb_variation_swatches_config( $config ) {
	$configuration                     = array(
		'name'     => __( 'Variation Swatches', 'product-blocks' ),
		'desc'     => __( 'Convert product attributes into beautiful swatches to ensure effortless shopping experiences.', 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-variation-swatches/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/variation-swatches-addon/',
		'type'     => 'build',
		'priority' => 20,
	);
	$config['wopb_variation_swatches'] = $configuration;
	return $config;
}

/**
 * Variation Swatches Addons Default Settings Param
 *
 * @param ARRAY | Default Filter Configuration
 * @return ARRAY
 * @since v.2.2.7
 */
add_filter( 'wopb_settings', 'get_variation_swatches_settings', 10, 1 );
function get_variation_swatches_settings( $config ) {
	$arr = array(
		'wopb_variation_swatches' => array(
			'label' => __( 'Variation Swatches', 'product-blocks' ),
			'attr'  => array(
				'variation_switch_heading' => array(
					'type'  => 'heading',
					'label' => __( 'Variation Swatches Settings', 'product-blocks' ),
				),
				'tab'                      => (object) array(
					'type'    => 'tab',
					'options' => array(
						'settings' => (object) array(
							'label' => __( 'Settings', 'product-blocks' ),
							'attr'  => array(
								'wopb_variation_swatches' => array(
									'type'  => 'toggle',
									'value' => 'true',
									'label' => __( 'Enable Variation Swatches', 'product-blocks' ),
									'desc'  => __( 'Enable variation swatches on your website', 'product-blocks' ),
								),
								'container_1'             => array(
									'type' => 'container',
									'attr' => array(
										'variation_switch_tooltip_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Enable / Disable Tooltip', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => 'Click if you want to show tooltip when hover',
										),
										'variation_switch_shape_style' => array(
											'type'    => 'radio',
											'label'   => __( 'Shape Style (Color, Image)', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => array(
												'square' => __( 'Square', 'product-blocks' ),
												'circle' => __( 'Circle', 'product-blocks' ),
											),
											'default' => 'square',
										),
										'variation_switch_label_is_background' => array(
											'type'    => 'toggle',
											'label'   => __( 'Shape(Label) Background', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Click if you want to show/hide background', 'product-blocks' ),
										),
										'variation_switch_dropdown_to_button' => array(
											'type'    => 'toggle',
											'label'   => __( 'Dropdown to Button', 'product-blocks' ),
											'default' => '',
											'desc'    => 'Convert default dropdowns to button type',
										),
										'variation_switch_shop_page_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Enable / Disable', 'product-blocks' ),
											'default' => '',
											'desc'    => 'Show swatches in default shop/archive pages',
										),
										'variation_switch_position' => array(
											'type'    => 'select',
											'label'   => __( 'Swatches Position(Default Shop/Archive)', 'product-blocks' ),
											'options' => array(
												'before_title' => 'Before Title',
												'after_title' => 'After Title',
												'before_price' => 'Before Price',
												'after_price' => 'After Price',
												'before_cart' => 'Before Cart',
												'after_cart' => 'After Cart',
											),
											'default' => 'before_cart',
											'product-blocks',
											'desc'    => 'Choose where to insert swatches in shop/listing',
											'product-blocks',
										),
										'product_image_in_variation_switch' => array(
											'type'    => 'toggle',
											'label'   => __( 'Product Image in Swatch', 'product-blocks' ),
											'default' => '',
											'desc'    => 'Show image in variation switcher from product',
											'pro'     => true,
										),
									),
								),
							),
						),
						'design'   => (object) array(
							'label' => __( 'Design', 'product-blocks' ),
							'attr'  => array(
								'container_2' => array(
									'type' => 'container',
									'attr' => array(
										'variation_label_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Label Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 15,
												'bold'   => 500,
												'italic' => false,
												'underline' => false,
												'color'  => 'rgba(7, 7, 7, 1)',
											),
										),
										'variation_switch_width' => array(
											'type'    => 'number',
											'label'   => __( 'Width (PX)', 'product-blocks' ),
											'default' => __( '16', 'product-blocks' ),
										),
										'variation_switch_height' => array(
											'type'    => 'number',
											'label'   => __( 'Height (PX)', 'product-blocks' ),
											'default' => __( '16', 'product-blocks' ),
										),
										'variation_image_width' => array(
											'type'    => 'number',
											'label'   => __( 'Image Width (PX)', 'product-blocks' ),
											'default' => __( '28', 'product-blocks' ),
										),
										'variation_image_height' => array(
											'type'    => 'number',
											'label'   => __( 'Image Height (PX)', 'product-blocks' ),
											'default' => __( '28', 'product-blocks' ),
										),
										'variation_align_shop' => array(
											'type'      => 'radio',
											'label'     => __( 'Alignment in Shop/Archive Page', 'product-blocks' ),
											'display'   => 'inline-box',
											'alignment' => 'right',
											'default'   => '',
											'options'   => array(
												'flex-start' => __( 'Left', 'product-blocks' ),
												'center'   => __( 'Center', 'product-blocks' ),
												'flex-end' => __( 'Right', 'product-blocks' ),
											),
										),
									),
								),
							),
						),
					),
				),
			),
		),
	);

	return array_merge( $config, $arr );
}
