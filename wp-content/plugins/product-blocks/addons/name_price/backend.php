<?php
defined( 'ABSPATH' ) || exit;

/**
 * Name Your Price Addons Initial Configuration
 *
 * @since v.4.0.0
 */
add_filter( 'wopb_addons_config', 'wopb_name_price_config' );
function wopb_name_price_config( $config ) {
	$configuration             = array(
		'name'     => __( 'Name Your Price', 'product-blocks' ),
		'desc'     => __( 'Let customers purchase products at their desired prices. Add Min & Max restrictions if required.', 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-name-your-price/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/name-your-price/',
		'type'     => 'sales',
		'priority' => 20,
	);
	$config['wopb_name_price'] = $configuration;
	return $config;
}

/**
 * Name Your Price Addons Default Settings
 *
 * @since v.4.0.0
 * @param ARRAY | Default Congiguration
 * @return ARRAY
 */
add_filter( 'wopb_settings', 'get_name_price_settings', 10, 1 );
function get_name_price_settings( $config ) {
	$arr = array(
		'wopb_name_price' => array(
			'label' => __( 'Name Your Price Settings', 'product-blocks' ),
			'attr'  => array(
				'tab' => array(
					'type'    => 'tab',
					'options' => array(
						'settings' => array(
							'label' => __( 'Settings', 'product-blocks' ),
							'attr'  => array(
								'wopb_name_price' => array(
									'type'  => 'toggle',
									'value' => 'true',
									'label' => __( 'Enable Name Price', 'product-blocks' ),
									'desc'  => __( 'Enable name price on your website', 'product-blocks' ),
								),
								'container_1'     => array(
									'type' => 'container',
									'attr' => array(
										'name_price_label' => array(
											'type'    => 'text',
											'label'   => __( 'Price Label', 'product-blocks' ),
											'default' => 'Price: ',
										),
										'name_price_min_show' => array(
											'type'    => 'toggle',
											'label'   => __( 'Show Minimum Price Label on the Frontend', 'product-blocks' ),
											'default' => '',
											'desc'    => __( 'Show Minimum Price Text Label on the Frontend', 'product-blocks' ),
										),
										'name_price_min'   => array(
											'type'    => 'text',
											'label'   => __( 'Minimum Price Label', 'product-blocks' ),
											'default' => 'Minimum Price: ',
											'depends' => array(
												'key'   => 'name_price_min_show',
												'condition' => '==',
												'value' => 'yes',
											),
										),
										'name_price_max_show' => array(
											'type'    => 'toggle',
											'label'   => __( 'Show Maximum Price Label on the Frontend', 'product-blocks' ),
											'default' => '',
											'desc'    => __( 'Show Maximum Price Label on the Frontend', 'product-blocks' ),
										),
										'name_price_max'   => array(
											'type'    => 'text',
											'label'   => __( 'Maximum Price Label', 'product-blocks' ),
											'default' => 'Maximum Price: ',
											'depends' => array(
												'key'   => 'name_price_max_show',
												'condition' => '==',
												'value' => 'yes',
											),
										),
										'name_price_single' => array(
											'type'    => 'text',
											'label'   => __( 'Add to Cart Button Text for Single Product', 'product-blocks' ),
											'default' => 'Add to Cart',
										),
										'name_price_archive' => array(
											'type'    => 'text',
											'label'   => __( 'Add to Cart Button Text for Shop & Archive', 'product-blocks' ),
											'default' => 'Add to Cart',
										),
									),
								),

							),
						),
						'design'   => array(
							'label' => __( 'Design', 'product-blocks' ),
							'attr'  => array(
								'container_2' => array(
									'type' => 'container',
									'attr' => array(
										'name_price_lvl_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Price Label Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 16,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#000000',
												'hover_color' => '',
											),
										),
										'name_price_split_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Suggested Price Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 14,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#000000',
												'hover_color' => '',
											),
										),
										'name_price_split_bg' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Suggested Price Background', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '#cccccc',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Background Color', 'product-blocks' ),
										),
										'name_price_split_padding' => array(
											'type'    => 'dimension',
											'label'   => __( 'Suggested Price Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 3,
												'bottom' => 3,
												'left'   => 15,
												'right'  => 15,
											),
										),
										'name_price_split_border' => array(
											'type'    => 'border',
											'label'   => __( 'Suggested Price Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 0,
												'color'  => '',
											),
										),
										'name_price_split_radius' => array(
											'type'    => 'number',
											'label'   => __( 'Suggested Price  Border Radius', 'product-blocks' ),
											'default' => 4,
										),
										'name_price_range_lvl_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Min/Max Label Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 14,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#000000',
												'hover_color' => '',
											),
										),
										'name_price_range_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Min/Max Price Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 14,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#000000',
												'hover_color' => '',
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
