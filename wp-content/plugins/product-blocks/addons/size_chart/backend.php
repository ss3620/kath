<?php
defined( 'ABSPATH' ) || exit;

/**
 * Size Chart Addons Initial Configuration
 *
 * @since v.3.2.0
 */
add_filter( 'wopb_addons_config', 'wopb_size_chart_init' );
function wopb_size_chart_init( $config ) {
	$configuration             = array(
		'name'     => __( 'Size Chart', 'product-blocks' ),
		'desc'     => __( 'Create & display size charts to help the potential buyers make better buying decisions.', 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-size-chart/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/size-chart/',
		'type'     => 'exclusive',
		'priority' => 50,
	);
	$config['wopb_size_chart'] = $configuration;
	return $config;
}

/**
 * Size Chart Addons Default Settings
 *
 * @since v.3.2.0
 * @param ARRAY | Default Configuration
 * @return ARRAY
 */
add_filter( 'wopb_settings', 'get_size_chart_opt', 10, 1 );
function get_size_chart_opt( $config ) {
	$is_active = wopb_function()->get_setting( 'is_lc_active' );
	$arr       = array(
		'wopb_size_chart' => array(
			'label' => __( 'Size Chart Settings', 'product-blocks' ),
			'attr'  => array(
				'tab' => array(
					'type'    => 'tab',
					'options' => array(
						'settings' => array(
							'label' => __( 'Settings', 'product-blocks' ),
							'attr'  => array(
								'container_1' => array(
									'type' => 'container',
									'attr' => array(
										'wopb_size_chart' => array(
											'type'  => 'toggle',
											'value' => $is_active ? 'true' : 'false',
											'label' => __( 'Enable Size Chart', 'product-blocks' ),
											'desc'  => __( 'Enable size chart on your website', 'product-blocks' ),
										),
										'size_chart_display' => array(
											'type'    => 'radio',
											'label'   => __( 'Show Size Chart As', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => array(
												'popup' => __( 'Pop Up', 'product-blocks' ),
											),
											'pro'     => array(
												'additional_tab' => __( 'Additional Tab', 'product-blocks' ),
											),
											'default' => 'popup',
										),
										'size_chart_position' => array(
											'type'    => 'radio',
											'label'   => __( 'Button Position', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => array(
												'before_add_cart' => __( 'Before Add to Cart', 'product-blocks' ),
												'after_add_cart' => __( 'After Add to Cart', 'product-blocks' ),
												'before_meta' => __( 'Before Meta', 'product-blocks' ),
												'after_meta' => __( 'After Meta', 'product-blocks' ),
											),
											'default' => 'before_add_cart',
											'depends' => array(
												'key'   => 'size_chart_display',
												'condition' => '==',
												'value' => 'popup',
											),
										),
										'size_chart_btn_text' => array(
											'type'    => 'text',
											'label'   => __( 'Button Text', 'product-blocks' ),
											'default' => 'Size Chart',
											'depends' => array(
												'key'   => 'size_chart_display',
												'condition' => '==',
												'value' => 'popup',
											),
										),
										'size_chart_tab_text' => array(
											'type'    => 'text',
											'label'   => __( 'Tab Label Text', 'product-blocks' ),
											'default' => 'Size Chart',
											'depends' => array(
												'key'   => 'size_chart_display',
												'condition' => '==',
												'value' => 'additional_tab',
											),
										),
										'size_chart_btn_icon' => array(
											'type'    => 'radio',
											'label'   => __( 'Choose Icon', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => array(
												'chart_1' => wopb_function()->svg_icon( 'chart_1' ),
												'chart_2' => wopb_function()->svg_icon( 'chart_2' ),
												'chart_3' => wopb_function()->svg_icon( 'chart_3' ),
												'chart_4' => wopb_function()->svg_icon( 'chart_4' ),
											),
											'default' => 'chart_1',
											'depends' => array(
												'key'   => 'size_chart_display',
												'condition' => '==',
												'value' => 'popup',
											),
										),
									),
								),
							),
						),
						'design'   => array(
							'label' => __( 'Design', 'product-blocks' ),
							'attr'  => array(
								'button_style_section' => array(
									'type'  => 'section',
									'label' => __( 'Size Chart Button Style', 'product-blocks' ),
									'attr'  => array(
										'size_chart_btn_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Button Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 15,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#000000',
												'hover_color' => '',
											),
										),
										'size_chart_btn_icon_size' => array(
											'type'    => 'number',
											'label'   => __( 'Icon Size', 'product-blocks' ),
											'default' => 16,
										),
										'size_chart_btn_bg' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Button Background', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Button Background', 'product-blocks' ),
										),
										'size_chart_btn_padding' => array(
											'type'    => 'dimension',
											'label'   => __( 'Button Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 8,
												'bottom' => 8,
												'left'   => 10,
												'right'  => 10,
											),
										),
										'size_chart_btn_border' => array(
											'type'    => 'border',
											'label'   => __( 'Button Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 1.5,
												'color'  => '#070707',
											),
										),
										'size_chart_btn_radius' => array(
											'type'    => 'number',
											'label'   => __( 'Border Radius', 'product-blocks' ),
											'default' => 0,
										),
									),
								),
								'table_style_section'  => array(
									'type'  => 'section',
									'label' => __( 'Size Chart Table Style', 'product-blocks' ),
									'attr'  => array(
										'size_chart_table_border' => array(
											'type'    => 'border',
											'label'   => __( 'Table Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 1,
												'color'  => '#646464',
											),
										),
										'size_chart_heading_bg' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Heading Background', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '#070707',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Background Color', 'product-blocks' ),
										),
										'size_chart_heading_color' => array(
											'type'    => 'color2',
											'field1'  => 'color',
											'field2'  => 'hover_color',
											'label'   => __( 'Heading Color', 'product-blocks' ),
											'default' => (object) array(
												'color' => '#FFFFFF',
												'hover_color' => '',
											),
											'tooltip' => __( 'Color', 'product-blocks' ),
										),
										'size_chart_even_row_bg' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Row(Even) Background', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Background Color', 'product-blocks' ),
										),
										'size_chart_even_row_color' => array(
											'type'    => 'color2',
											'field1'  => 'color',
											'field2'  => 'hover_color',
											'label'   => __( 'Row(Even) Color', 'product-blocks' ),
											'default' => (object) array(
												'color' => '',
												'hover_color' => '',
											),
											'tooltip' => __( 'Color', 'product-blocks' ),
										),
										'size_chart_odd_row_bg' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Row(Odd) Background', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Background Color', 'product-blocks' ),
										),
										'size_chart_odd_row_color' => array(
											'type'    => 'color2',
											'field1'  => 'color',
											'field2'  => 'hover_color',
											'label'   => __( 'Row(Odd) Color', 'product-blocks' ),
											'default' => (object) array(
												'color' => '',
												'hover_color' => '',
											),
											'tooltip' => __( 'Color', 'product-blocks' ),
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
