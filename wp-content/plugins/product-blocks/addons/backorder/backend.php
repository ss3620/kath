<?php
defined( 'ABSPATH' ) || exit;

/**
 * Backorder Addons Initial Configuration
 *
 * @since v.1.0.7
 */
add_filter( 'wopb_addons_config', 'wopb_backorder_setup' );
function wopb_backorder_setup( $config ) {
	$configuration            = array(
		'name'     => __( 'Backorder', 'product-blocks' ),
		'desc'     => __( 'Keep getting orders for the products that are currently out of stock and will be restocked soon.', 'product-blocks' ),
		'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-backorder/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/back-order-addon/',
		'is_pro'   => false,
		'type'     => 'sales',
		'priority' => 40,
	);
	$config['wopb_backorder'] = $configuration;
	return $config;
}

/**
 * Backorder Addons Default Settings Parameters
 *
 * @param ARRAY | Default Settings Congiguration
 * @return ARRAY
 * @since v.1.0.7
 */
add_filter( 'wopb_settings', 'get_backorder_opt', 10, 1 );
function get_backorder_opt( $config ) {
	$arr = array(
		'wopb_backorder' => array(
			'label' => __( 'Backorder', 'product-blocks' ),
			'attr'  => array(
				'backorder_heading' => array(
					'type'  => 'heading',
					'label' => __( 'Backorder Settings', 'product-blocks' ),
				),
				'tab'               => (object) array(
					'type'    => 'tab',
					'options' => array(
						'settings' => (object) array(
							'label' => __( 'Settings', 'product-blocks' ),
							'attr'  => array(
								'container_1' => array(
									'type' => 'container',
									'attr' => array(
										'wopb_backorder' => array(
											'type'  => 'toggle',
											'value' => 'false',
											'label' => __( 'Enable Backorder', 'product-blocks' ),
											'desc'  => __( 'Enable backorder on your website', 'product-blocks' ),
										),
										'backorder_button_text' => array(
											'type'    => 'text',
											'label'   => __( 'Backorder Label/Text', 'product-blocks' ),
											'default' => __( 'Backorder', 'product-blocks' ),
										),
										'backorder_add_to_cart_button_text' => array(
											'type'    => 'text',
											'label'   => __( 'Backorder Add to Cart Text', 'product-blocks' ),
											'default' => __( 'Backorder Now', 'product-blocks' ),
										),
										'backorder_message_text' => array(
											'type'    => 'text',
											'label'   => __( 'Availability Message', 'product-blocks' ),
											'default' => __( 'Available On', 'product-blocks' ),
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
										'backorder_available_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Available Label Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 16,
												'bold'   => true,
												'italic' => true,
												'underline' => false,
												'color'  => '#398e29',
												'hover_color' => '',
											),
										),
										'backorder_duration_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Duration Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 16,
												'bold'   => true,
												'italic' => true,
												'underline' => false,
												'color'  => '#398e29',
												'hover_color' => '',
											),
										),
										'backorder_remain_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Remaining Label Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 16,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#000000',
												'hover_color' => '',
											),
										),
										'backorder_count_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Remaining Count Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 16,
												'bold'   => true,
												'italic' => false,
												'underline' => false,
												'color'  => '#000000',
												'hover_color' => '',
											),
										),
										'backorder_badge_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Badge Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 12,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#ffffff',
												'hover_color' => '',
											),
										),
										'backorder_badge_bg' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Badge Background', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '#007cba',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Background Color', 'product-blocks' ),
										),
										'backorder_badge_padding' => array(
											'type'    => 'dimension',
											'label'   => __( 'Badge Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 5,
												'bottom' => 5,
												'left'   => 5,
												'right'  => 5,
											),
										),
										'backorder_badge_border' => array(
											'type'    => 'border',
											'label'   => __( 'Badge Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 0,
												'color'  => '',
											),
										),
										'backorder_badge_radius' => array(
											'type'    => 'number',
											'label'   => __( 'Badge Border Radius', 'product-blocks' ),
											'default' => '',
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
