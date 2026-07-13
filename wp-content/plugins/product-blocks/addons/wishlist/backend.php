<?php
defined( 'ABSPATH' ) || exit;

/**
 * Wishlist Addons Initial Configuration
 *
 * @since v.1.1.0
 */
add_filter( 'wopb_addons_config', 'wopb_wishlist_config' );
function wopb_wishlist_config( $config ) {
	$configuration           = array(
		'name'     => __( 'Wishlist', 'product-blocks' ),
		'desc'     => __( 'It allows your shoppers to add their desired product to a list that they are willing to purchase later from the wishlist page.', 'product-blocks' ),
		'img'      => WOPB_URL . '/assets/img/addons/wishlist.svg',
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-wishlist/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/wishlist-settings/',
		'type'     => 'build',
		'priority' => 10,
	);
	$config['wopb_wishlist'] = $configuration;
	return $config;
}

/**
 * Wishlist Addons Default Settings Param
 *
 * @since v.1.1.0
 * @param ARRAY | Default Filter Congiguration
 * @return ARRAY
 */
add_filter( 'wopb_settings', 'get_wishlist_settings', 10, 1 );
function get_wishlist_settings( $config ) {
	$arr = array(
		'wopb_wishlist' => array(
			'label' => __( 'Wishlist', 'product-blocks' ),
			'attr'  => array(
				'tab' => array(
					'type'    => 'tab',
					'options' => array(
						'settings' => array(
							'label' => __( 'Settings', 'product-blocks' ),
							'attr'  => array(
								'wishlist_heading'        => array(
									'type'  => 'heading',
									'label' => __( 'Wishlist Settings', 'product-blocks' ),
								),
								'wopb_wishlist'           => array(
									'type'  => 'toggle',
									'value' => 'true',
									'label' => __( 'Enable Wishlist', 'product-blocks' ),
									'desc'  => __( 'Enable wishlist on your website', 'product-blocks' ),
								),
								'general_settings'        => array(
									'type'  => 'section',
									'label' => __( 'General Settings', 'product-blocks' ),
									'attr'  => array(
										'wishlist_page'  => array(
											'type'    => 'select',
											'label'   => __( 'Wishlist Page', 'product-blocks' ),
											'options' => wopb_function()->all_pages( true ),
											'desc'    => '[wopb_wishlist] ' . __( 'Use shortcode inside wishlist page.', 'product-blocks' ),
										),
										'wishlist_require_login' => array(
											'type'    => 'toggle',
											'label'   => __( 'Enable / Disable', 'product-blocks' ),
											'default' => '',
											'pro'     => true,
											'desc'    => __( 'Require Login for Wishlist.', 'product-blocks' ),
										),
										'wishlist_empty' => array(
											'type'    => 'toggle',
											'label'   => __( 'Enable / Disable', 'product-blocks' ),
											'default' => '',
											'pro'     => true,
											'desc'    => __( 'Empty Wishlist After All Add To Cart.', 'product-blocks' ),
										),
										'wishlist_redirect_cart' => array(
											'type'    => 'toggle',
											'label'   => __( 'Enable / Disable', 'product-blocks' ),
											'default' => '',
											'desc'    => __( 'Redirect to Cart After All Add to Cart.', 'product-blocks' ),
										),
										'wishlist_action_added' => array(
											'type'    => 'radio',
											'label'   => __( 'Action after Added', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => array(
												'popup'    => __( 'Popup', 'product-blocks' ),
												'redirect' => __( 'Redirect to Page', 'product-blocks' ),
											),
											'default' => 'popup',
										),
									),
								),
								'shop_settings'           => array(
									'type'  => 'section',
									'label' => __( 'Wishlist in Shop/Archive Page', 'product-blocks' ),
									'attr'  => array(
										'wishlist_shop_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Enable / Disable', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable Wishlist in default Shop Page.', 'product-blocks' ),
										),
										'wishlist_position' => array(
											'type'    => 'radio',
											'label'   => __( 'Position on Shop Page', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => array(
												'before_cart' => __( 'Before Cart', 'product-blocks' ),
												'after_cart' => __( 'After Cart', 'product-blocks' ),
											),
											'default' => 'after_cart',
										),
										'wishlist_button' => array(
											'type'    => 'text',
											'label'   => __( 'Button Text', 'product-blocks' ),
											'default' => 'Add To Wishlist',
										),
										'wishlist_browse' => array(
											'type'    => 'text',
											'label'   => __( 'Browse Wishlist Text', 'product-blocks' ),
											'default' => 'Browse Wishlist',
										),
									),
								),
								'single_product_settings' => array(
									'type'  => 'section',
									'label' => __( 'Wishlist in Single Page', 'product-blocks' ),
									'attr'  => array(
										'wishlist_single_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Enable / Disable', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable Wishlist in Single Page.', 'product-blocks' ),
										),
										'wishlist_position_single' => array(
											'type'    => 'radio',
											'label'   => __( 'Position on Single Page', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => array(
												'before_cart' => __( 'Before Cart', 'product-blocks' ),
												'after_cart' => __( 'After Cart', 'product-blocks' ),
											),
											'default' => 'after_cart',
										),
										'wishlist_button_single' => array(
											'type'    => 'text',
											'label'   => __( 'Button Text', 'product-blocks' ),
											'default' => __( 'Add to Wishlist', 'product-blocks' ),
										),
										'wishlist_browse_single' => array(
											'type'    => 'text',
											'label'   => __( 'Browse Wishlist Text', 'product-blocks' ),
											'default' => __( 'Browse Wishlist', 'product-blocks' ),
										),
									),
								),
								'nav_menu_settings'       => array(
									'type'  => 'section',
									'label' => __( 'Wishlist Menu in Navbar', 'product-blocks' ),
									'attr'  => array(
										'wishlist_nav_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Add Wishlist Menu In Navbar', 'product-blocks' ),
											'default' => '',
											'desc'    => __( 'Enable Wishlist menu in navbar.', 'product-blocks' ),
										),
										'wishlist_nav_location' => array(
											'type'    => 'select',
											'label'   => __( 'Nav Menu Location', 'product-blocks' ),
											'options' => wopb_function()->wopb_nav_menu_location(),
											'default' => '',
										),
										'wishlist_nav_text' => array(
											'type'    => 'text',
											'label'   => __( 'Wishlist Text', 'product-blocks' ),
											'default' => '',
											'desc'    => __( 'Write your preferable text to show  on wishlist button', 'product-blocks' ),
										),
										'wishlist_nav_icon_position' => array(
											'type'    => 'radio',
											'label'   => __( 'Icon Position', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => array(
												'before_text' => 'Before Text',
												'after_text' => 'After Text',
												'top_text' => 'Top of Text',
											),
											'default' => 'before_text',
										),
										'wishlist_nav_shortcode' => array(
											'type'    => 'toggle',
											'label'   => __( 'Wishlist Menu Custom Position', 'product-blocks' ),
											'default' => '',
											'desc'    => __( 'Enable shortcode for custom position.', 'product-blocks' ),
											'note'    => (object) array(
												'text'    => __( 'Use this shortcode [wopb_wishlist_nav] for custom position of comparison menu on navbar.', 'product-blocks' ),
												'depends' => array(
													'key' => 'wishlist_nav_shortcode',
													'condition' => '==',
													'value' => 'yes',
												),
											),
										),
									),
								),

							),
						),

						'design'   => array(
							'label' => __( 'Design', 'product-blocks' ),
							'attr'  => array(
								'shop_style'           => array(
									'type'  => 'section',
									'label' => __( 'Shop/Archive Page Button Style', 'product-blocks' ),
									'attr'  => array(
										'wishlist_btn_typo_shop' => array(
											'type'    => 'typography',
											'label'   => __( 'Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 14,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => 'rgba(7, 7, 7, 1)',
												'hover_color' => 'rgba(255, 23, 107, 1)',
											),
										),
										'wishlist_btn_bg_shop' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Background Color', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Background Color', 'product-blocks' ),
										),
										'wishlist_btn_padding_shop' => array(
											'type'    => 'dimension',
											'label'   => __( 'Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 0,
												'bottom' => 0,
												'left'   => 0,
												'right'  => 0,
											),
										),
										'wishlist_btn_border_shop' => array(
											'type'    => 'border',
											'label'   => __( 'Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 0,
												'color'  => '',
											),
										),
										'wishlist_btn_radius_shop' => array(
											'type'    => 'number',
											'label'   => __( 'Border Radius', 'product-blocks' ),
											'default' => 0,
										),
										'wishlist_icon_size_shop' => array(
											'type'    => 'number',
											'label'   => __( 'Icon Size', 'product-blocks' ),
											'default' => 16,
										),
										'wishlist_align_shop' => array(
											'type'      => 'radio',
											'label'     => __( 'Button Alignment', 'product-blocks' ),
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
								'single_style'         => array(
									'type'  => 'section',
									'label' => __( 'Product Single Page Button Style', 'product-blocks' ),
									'attr'  => array(
										'wishlist_btn_typo_single' => array(
											'type'    => 'typography',
											'label'   => __( 'Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 14,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => 'rgba(7, 7, 7, 1)',
												'hover_color' => 'rgba(255, 23, 107, 1)',
											),
										),
										'wishlist_btn_bg_single' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Background Color', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Background Color', 'product-blocks' ),
										),
										'wishlist_btn_padding_single' => array(
											'type'    => 'dimension',
											'label'   => __( 'Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 0,
												'bottom' => 0,
												'left'   => 0,
												'right'  => 0,
											),
										),
										'wishlist_btn_border_single' => array(
											'type'    => 'border',
											'label'   => __( 'Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 0,
												'color'  => '',
											),
										),
										'wishlist_btn_radius_single' => array(
											'type'    => 'number',
											'label'   => __( 'Border Radius', 'product-blocks' ),
											'default' => 0,
										),
										'wishlist_icon_size_single' => array(
											'type'    => 'number',
											'label'   => __( 'Icon Size', 'product-blocks' ),
											'default' => 16,
										),
									),
								),
								'wishlist_nav_style'   => array(
									'type'  => 'section',
									'label' => __( 'Navbar Button Style', 'product-blocks' ),
									'attr'  => array(
										'wishlist_btn_typo_nav' => array(
											'type'    => 'typography',
											'label'   => __( 'Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => '',
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '',
												'hover_color' => '',
											),
										),
										'wishlist_btn_bg_nav' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Background Color', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Background Color', 'product-blocks' ),
										),
										'wishlist_btn_padding_nav' => array(
											'type'    => 'dimension',
											'label'   => __( 'Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 0,
												'bottom' => 0,
												'left'   => 0,
												'right'  => 0,
											),
										),
										'wishlist_btn_border_nav' => array(
											'type'    => 'border',
											'label'   => __( 'Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 0,
												'color'  => '',
											),
										),
										'wishlist_btn_radius_nav' => array(
											'type'    => 'number',
											'label'   => __( 'Border Radius', 'product-blocks' ),
											'default' => 0,
										),
										'wishlist_icon_size_nav' => array(
											'type'    => 'number',
											'label'   => __( 'Icon Size', 'product-blocks' ),
											'default' => 18,
										),
									),
								),
								'wishlist_table_style' => array(
									'type'  => 'section',
									'label' => __( 'Table Column Style', 'product-blocks' ),
									'attr'  => array(
										'wishlist_heading_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Heading Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 16,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#000000',
												'hover_color' => '',
											),
										),
										'wishlist_heading_bg' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Heading Background', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '#ededed',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Background Color', 'product-blocks' ),
										),
										'wishlist_body_text_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Body Text Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 14,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#000000',
												'hover_color' => '',
											),
										),
										'wishlist_column_space' => array(
											'type'    => 'number',
											'label'   => __( 'Column Spacing', 'product-blocks' ),
											'default' => 0,
										),
										'wishlist_column_border' => array(
											'type'    => 'border',
											'label'   => __( 'Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 1,
												'color'  => 'rgba(0, 0, 0, .08)',
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
