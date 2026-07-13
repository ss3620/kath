<?php
defined( 'ABSPATH' ) || exit;

/**
 * Stock Progress Bar Addons Initial Configuration
 *
 * @since v.1.0.5
 */
if ( ! function_exists( 'wopb_stock_progress_bar_init' ) ) {
	add_filter( 'wopb_addons_config', 'wopb_stock_progress_bar_init' );
	/**
	 * Initialize Stock Progress Bar configuration
	 *
	 * @param array $config Configuration array.
	 * @return array Modified configuration array.
	 * @since v.1.0.5
	 */
	function wopb_stock_progress_bar_init( $config ) {
		$configuration                     = array(
			'name'     => __( 'Stock Progress Bar', 'product-blocks' ),
			'desc'     => __( 'Visually highlight the total and remaining stocks of products to encourage shoppers to create FOMO.', 'product-blocks' ),
			'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-stock-progress-bar/',
			'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/stock-progress-bar-addon/',
			'is_pro'   => true,
			'type'     => 'sales',
			'priority' => 80,
		);
		$config['wopb_stock_progress_bar'] = $configuration;
		return $config;
	}
}

/**
 * Stock Progress Bar Addons Default Settings Param
 *
 * @since v.1.0.5
 * @param ARRAY | Default Filter Congiguration
 * @return ARRAY
 */
add_filter( 'wopb_settings', 'get_stock_progress_opt', 10, 1 );
function get_stock_progress_opt( $config ) {
	$is_active = wopb_function()->get_setting( 'is_lc_active' );
	$arr       = array(
		'wopb_stock_progress_bar' => array(
			'label' => __( 'Stock Progress Bar', 'product-blocks' ),
			'attr'  => array(
				'stock_progress_bar_heading' => array(
					'type'  => 'heading',
					'label' => __( 'Stock Progress Bar Settings', 'product-blocks' ),
				),
				'tab'                        => (object) array(
					'type'    => 'tab',
					'options' => array(
						'settings' => (object) array(
							'label' => __( 'Settings', 'product-blocks' ),
							'attr'  => array(
								'container_1' => array(
									'type' => 'container',
									'attr' => array(
										'wopb_stock_progress_bar' => array(
											'type'    => 'toggle',
											'value'   => $is_active ? 'true' : 'false',
											'label'   => __( 'Enable Progress bar', 'product-blocks' ),
											'default' => '',
											'pro'     => 'true',
											'desc'    => __( 'Enable stock progress bar on your website', 'product-blocks' ),
										),
										'total_sell_count_text' => array(
											'type'    => 'text',
											'label'   => __( 'Total Sell Count Text', 'product-blocks' ),
											'default' => __( 'Total Sold', 'product-blocks' ),
										),
										'available_item_count_text' => array(
											'type'    => 'text',
											'label'   => __( 'Available Item Count Text', 'product-blocks' ),
											'default' => __( 'Available Item', 'product-blocks' ),
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
										'stock_progress_label_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Label Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 16,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#656565',
												'hover_color' => '',
											),
										),
										'stock_progress_count_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Count Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 16,
												'bold'   => true,
												'italic' => false,
												'underline' => false,
												'color'  => '#000000',
												'hover_color' => '',
											),
										),
										'stock_progress_bg' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Progress Bar Color', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '#e2e2e2',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Color', 'product-blocks' ),
										),
										'stock_progress_active_bg' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Progress Bar Active Color', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '#25b064',
												'hover_bg' => '',
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
