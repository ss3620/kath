<?php
defined( 'ABSPATH' ) || exit;

/**
 * Quickview Addons Initial Configuration
 *
 * @since v.1.1.0
 */
add_filter( 'wopb_addons_config', 'wopb_quickview_config' );
function wopb_quickview_config( $config ) {
	$configuration            = array(
		'name'         => __( 'Quick View', 'product-blocks' ),
		'desc'         => __( 'It allows the shoppers to check out the product details in a pop-up instead of visiting the product pages.', 'product-blocks' ),
		'is_pro'       => false,
		'live'         => 'https://www.wpxpo.com/product/wowstore/features/quick-view/',
		'docs'         => 'https://wpxpo.com/docs/wowstore/add-ons/quickview-settings/',
		'live_preview' => true,
		'type'         => 'build',
		'priority'     => 40,
	);
	$config['wopb_quickview'] = $configuration;
	return $config;
}

/**
 * Quickview Addons Default Settings Param
 *
 * @param ARRAY | Default Filter Congiguration
 * @return ARRAY
 * @since v.1.1.0
 */
add_filter( 'wopb_settings', 'wopb_get_quickview_settings', 10, 1 );
function wopb_get_quickview_settings( $config ) {
	$default_contents = array(
		array(
			'key'   => 'image',
			'label' => __( 'Image', 'product-blocks' ),
		),
		array(
			'key'   => 'title',
			'label' => __( 'Title', 'product-blocks' ),
		),
		array(
			'key'   => 'rating',
			'label' => __( 'Rating', 'product-blocks' ),
		),
		array(
			'key'   => 'price',
			'label' => __( 'Price', 'product-blocks' ),
		),
		array(
			'key'   => 'description',
			'label' => __( 'Description', 'product-blocks' ),
		),
		array(
			'key'   => 'stock_status',
			'label' => __( 'Stock Status', 'product-blocks' ),
		),
		array(
			'key'   => 'add_to_cart',
			'label' => __( 'Add To Cart', 'product-blocks' ),
		),
		array(
			'key'   => 'meta',
			'label' => __( 'Meta', 'product-blocks' ),
		),
		array(
			'key'   => 'view_details',
			'label' => __( 'View Details', 'product-blocks' ),
		),
		array(
			'key'   => 'social_share',
			'label' => __( 'Social Share', 'product-blocks' ),
		),
	);
	$contents         = array(
		array(
			'key'   => '',
			'label' => __( 'Select Column', 'product-blocks' ),
		),
		...$default_contents,
	);
	$arr              = array(
		'wopb_quickview' => array(
			'label' => __( 'QuickView', 'product-blocks' ),
			'attr'  => array(
				'quickview_heading' => array(
					'type'  => 'heading',
					'label' => __( 'Quick View Settings', 'product-blocks' ),
				),
				'tab'               => (object) array(
					'type'    => 'tab',
					'options' => array(
						'settings' => (object) array(
							'label' => __( 'Settings', 'product-blocks' ),
							'attr'  => array(
								'wopb_quickview'   => array(
									'type'  => 'toggle',
									'value' => 'true',
									'label' => __( 'Enable Quickview', 'product-blocks' ),
									'desc'  => __( 'Enable quickview on your website', 'product-blocks' ),
								),
								'general_settings' => array(
									'type'  => 'section',
									'label' => __( 'General Settings', 'product-blocks' ),
									'attr'  => array(
										'quick_view_mobile_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Enable Quick View on Mobile Devices', 'product-blocks' ),
											'default' => '',
											'desc'    => __( 'Enable if you want to show quick view on mobile devices', 'product-blocks' ),
										),
										'quick_view_shop_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Show in Shop/Archive Page', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable if you want to show quick view on shop/archive page', 'product-blocks' ),
										),
										'quick_view_click_action' => array(
											'type'       => 'radio',
											'label'      => __( 'Select option for what will happens when you click quick view', 'product-blocks' ),
											'display'    => 'inline-box',
											'options'    => array(
												'popup' => 'Popup',
												'right_sidebar' => 'Right Sidebar',
												'left_sidebar' => 'Left Sidebar',
											),
											'default'    => 'popup',
											'variations' => array(
												'popup' => array(
													'quick_view_layout' => 1,
												),
												'right_sidebar' => array(
													'quick_view_layout' => 4,
												),
												'left_sidebar' => array(
													'quick_view_layout' => 5,
												),
											),
										),
										'quick_view_loader' => array(
											'type'    => 'radio',
											'label'   => __( 'Quick View Display Loading', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => wopb_function()->modal_loaders(),
											'default' => 'loader_1',
											'desc'    => __( 'Select loading icon to show before open quick view.', 'product-blocks' ),
										),
										'quick_view_open_animation' => array(
											'type'    => 'select',
											'label'   => __( 'Quick View Opening Animation', 'product-blocks' ),
											'options' => array(
												''        => __( 'Select Animation', 'product-blocks' ),
												'zoom_in' => __( 'Zoom In', 'product-blocks' ),
												'shrink_in' => __( 'Shrink In', 'product-blocks' ),
												'fade_in' => __( 'Fade In', 'product-blocks' ),
												'flip_in' => __( 'Flip In', 'product-blocks' ),
												'slide_up_in' => __( 'Slide Up', 'product-blocks' ),
												'slide_down_in' => __( 'Slide Down', 'product-blocks' ),
												'slide_left_in' => __( 'Slide Left', 'product-blocks' ),
												'slide_right_in' => __( 'Slide Right', 'product-blocks' ),
												'unfold'  => __( 'Unfolding', 'product-blocks' ),
												'blow_up' => __( 'Blow Up', 'product-blocks' ),
											),
											'default' => 'zoom_in',
										),
										'quick_view_close_animation' => array(
											'type'    => 'select',
											'label'   => __( 'Quick View Closing Animation', 'product-blocks' ),
											'options' => array(
												''         => __( 'Select Animation', 'product-blocks' ),
												'zoom_out' => __( 'Zoom Out', 'product-blocks' ),
												'shrink_out' => __( 'Shrink Out', 'product-blocks' ),
												'fade_out' => __( 'Fade Out', 'product-blocks' ),
												'flip_out' => __( 'Flip Out', 'product-blocks' ),
												'slide_up_out' => __( 'Slide Up', 'product-blocks' ),
												'slide_down_out' => __( 'Slide Down', 'product-blocks' ),
												'slide_left_out' => __( 'Slide Left', 'product-blocks' ),
												'slide_right_out' => __( 'Slide Right', 'product-blocks' ),
												'fold'     => __( 'Folding', 'product-blocks' ),
												'blow_down' => __( 'Blow Down', 'product-blocks' ),
											),
											'default' => 'zoom_out',
										),
										'quick_view_product_navigation' => array(
											'type'    => 'toggle',
											'label'   => __( 'Product Navigation(Next/Previous)', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable navigation to quick view next/previous product', 'product-blocks' ),
										),
									),
								),
								'quick_view_button_settings' => array(
									'type'  => 'section',
									'label' => __( 'Quick View Button Settings', 'product-blocks' ),
									'attr'  => array(
										'quick_view_text' => array(
											'type'    => 'text',
											'label'   => __( 'Button Text', 'product-blocks' ),
											'default' => '',
											'desc'    => __( 'Write your preferable text to show  on quick view button', 'product-blocks' ),
										),
										'quick_view_position' => array(
											'type'    => 'select',
											'label'   => __( 'Button Position', 'product-blocks' ),
											'desc'    => __( 'Choose where will place quick view button.', 'product-blocks' ),
											'options' => array(
												'after_cart' => __( 'After Add to Cart', 'product-blocks' ),
												'bottom_cart' => __( 'Bottom Add to Cart', 'product-blocks' ),
												'top_cart' => __( 'Top Add to Cart', 'product-blocks' ),
												'before_cart' => __( 'Before Add to Cart', 'product-blocks' ),
												'above_image' => __( 'Above Image', 'product-blocks' ),
												'shortcode' => __( 'Use Shortcode', 'product-blocks' ),
											),
											'default' => 'bottom_cart',
											'note'    => (object) array(
												'text'    => __( 'Use this shortcode [wopb_quick_view_button] where you want to show quick view button.', 'product-blocks' ),
												'depends' => array(
													'key' => 'quick_view_position',
													'condition' => '==',
													'value' => 'shortcode',
												),
											),
										),
										'quick_view_button_icon_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Button Icon', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable button icon to display icon on quick view button.', 'product-blocks' ),
										),
										'quick_view_button_icon' => array(
											'type'    => 'radio',
											'label'   => __( 'Choose Icon', 'product-blocks' ),
											'display' => 'inline-box',
											'depends' => array(
												'key'   => 'quick_view_button_icon_enable',
												'condition' => '==',
												'value' => 'yes',
											),
											'options' => array(
												'quick_view_1' => wopb_function()->svg_icon( 'quick_view_1' ),
												'quick_view_2' => wopb_function()->svg_icon( 'quick_view_2' ),
												'quick_view_3' => wopb_function()->svg_icon( 'quick_view_3' ),
												'quick_view_4' => wopb_function()->svg_icon( 'quick_view_4' ),
												'quick_view_5' => wopb_function()->svg_icon( 'quick_view_5' ),
												'quick_view_6' => wopb_function()->svg_icon( 'quick_view_6' ),
											),
											'default' => 'quick_view_3',
											'desc'    => __( 'Choose icon display on quick view button.', 'product-blocks' ),
										),
										'quick_view_button_icon_position' => array(
											'type'    => 'radio',
											'label'   => __( 'Icon Position', 'product-blocks' ),
											'display' => 'inline-box',
											'depends' => array(
												array(
													'key' => 'quick_view_button_icon_enable',
													'condition' => '==',
													'value' => 'yes',
												),
											),
											'options' => array(
												'before_text' => __( 'Before Text', 'product-blocks' ),
												'after_text' => __( 'After Text', 'product-blocks' ),
											),
											'default' => 'before_text',
										),
									),
								),
								'quick_view_modal_settings' => array(
									'type'  => 'section',
									'label' => __( 'Quick View Modal Box Settings', 'product-blocks' ),
									'attr'  => array(
										'quick_view_contents' => array(
											'type'    => 'select_item',
											'label'   => __( 'Select Content to Show on Quick View Modal Box', 'product-blocks' ),
											'desc'    => __( 'Select the content you want to display in the quick view modal box. To rearrange these fields, you can also drag and drop. You can also customize the label name according to your preferences.', 'product-blocks' ),
											'default' => $default_contents,
											'options' => $contents,
										),
										'quick_view_image_type' => array(
											'type'    => 'radio',
											'label'   => __( 'Product Thumbnail', 'product-blocks' ),
											'desc'    => __( 'Select option for what you want to show on modal box', 'product-blocks' ),
											'default' => 'image_with_gallery',
											'display' => 'inline-box',
											'options' => array(
												'image_with_gallery' => 'Product Image & Gallery Images',
												'image_with_pagination' => 'Product Gallery Images',
												'image_only' => 'Product Image Only',
											),
										),
										'quick_view_image_gallery' => array(
											'type'    => 'layout',
											'label'   => __( 'Thumbnail Style', 'product-blocks' ),
											'default' => 'bottom',
											'depends' => array(
												'key'   => 'quick_view_image_type',
												'condition' => '==',
												'value' => 'image_with_gallery',
											),
											'options' => array(
												(object) array(
													'key' => 'bottom',
													'image' => WOPB_URL . 'assets/img/addons/quick_view/quick_gallery_bottom.svg',
												),
												(object) array(
													'key' => 'right',
													'image' => WOPB_URL . 'assets/img/addons/quick_view/quick_gallery_right.svg',
												),
												(object) array(
													'key' => 'left',
													'image' => WOPB_URL . 'assets/img/addons/quick_view/quick_gallery_left.svg',
												),
											),
										),
										'quick_view_image_pagination' => array(
											'type'    => 'layout',
											'label'   => __( 'Thumbnail Style', 'product-blocks' ),
											'default' => 'line',
											'depends' => array(
												'key'   => 'quick_view_image_type',
												'condition' => '==',
												'value' => 'image_with_pagination',
											),
											'options' => array(
												(object) array(
													'key' => 'line',
													'image' => WOPB_URL . 'assets/img/addons/quick_view/quick_slide_line.svg',
												),
												(object) array(
													'key' => 'dot',
													'image' => WOPB_URL . 'assets/img/addons/quick_view/quick_slide_dot.svg',
												),
												(object) array(
													'key' => 'right_arrow',
													'image' => WOPB_URL . 'assets/img/addons/quick_view/quick_slide_arrow_right.svg',
												),
											),
										),
										'quick_view_image_effect' => array(
											'type'    => 'toggle',
											'label'   => __( 'Product Image Effect', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable lightbox on click image', 'product-blocks' ),
										),
										'quick_view_image_effect_type' => array(
											'type'    => 'radio',
											'label'   => __( 'Effect Type', 'product-blocks' ),
											'default' => 'zoom',
											'display' => 'inline-box',
											'depends' => array(
												'key'   => 'quick_view_image_effect',
												'condition' => '==',
												'value' => 'yes',
											),
											'options' => array(
												'zoom'  => 'Zoom',
												'popup' => 'Click to Popup',
											),
										),
										'quick_view_image_hover_icon' => array(
											'type'    => 'radio',
											'label'   => __( 'Hover Effect Icon', 'product-blocks' ),
											'default' => 'zoom_1',
											'display' => 'inline-box',
											'depends' => array(
												'key'   => 'quick_view_image_effect',
												'condition' => '==',
												'value' => 'yes',
											),
											'options' => array(
												'zoom_1' => wopb_function()->svg_icon( 'cursor_zoom_1' ),
												'zoom_2' => wopb_function()->svg_icon( 'cursor_zoom_2' ),
											),
										),
										'quick_view_buy_now' => array(
											'type'    => 'toggle',
											'label'   => __( 'Enable Buy Now Button', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable to display buy now button on modal box', 'product-blocks' ),
										),
										'quick_view_thumbnail_freeze' => array(
											'type'    => 'toggle',
											'label'   => __( 'Freeze Product Thumbnail', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable freeze the product thumbnail when scrolling', 'product-blocks' ),
										),
										'quick_view_close_button' => array(
											'type'    => 'toggle',
											'label'   => __( 'Close Button on Quick View Modal', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable close button at top right corner on quick view modal', 'product-blocks' ),
										),
										'quick_view_close_add_to_cart' => array(
											'type'    => 'toggle',
											'label'   => __( 'Close Modal Box After Add To Cart', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Auto close the modal box after adding a product to the cart', 'product-blocks' ),
										),
									),
								),
							),
						),
						'design'   => (object) array(
							'label' => __( 'Design', 'product-blocks' ),
							'attr'  => array(
								'quick_view_layout' => array(
									'type'       => 'layout',
									'label'      => __( 'Choose Quick View Modal Layout', 'product-blocks' ),
									'default'    => 1,
									'options'    => array(
										(object) array(
											'key'   => 1,
											'image' => WOPB_URL . 'assets/img/addons/quick_view/layout_1.png',
											'pro'   => false,
										),
										(object) array(
											'key'   => 2,
											'image' => WOPB_URL . 'assets/img/addons/quick_view/layout_2.png',
											'pro'   => false,
										),
										(object) array(
											'key'   => 3,
											'image' => WOPB_URL . 'assets/img/addons/quick_view/layout_3.png',
											'pro'   => false,
										),
										(object) array(
											'key'   => 4,
											'image' => WOPB_URL . 'assets/img/addons/quick_view/layout_4.png',
											'pro'   => false,
										),
										(object) array(
											'key'   => 5,
											'image' => WOPB_URL . 'assets/img/addons/quick_view/layout_5.png',
											'pro'   => false,
										),
									),
									'preview'    => true,
									'variations' => array(
										1 => array(
											'quick_view_click_action' => 'popup',
											'quick_view_image_type' => 'image_with_gallery',
											'quick_view_image_gallery' => 'bottom',
										),
										2 => array(
											'quick_view_click_action' => 'popup',
											'quick_view_image_type' => 'image_with_gallery',
											'quick_view_image_gallery' => 'bottom',
										),
										3 => array(
											'quick_view_click_action' => 'popup',
											'quick_view_image_type' => 'image_with_pagination',
											'quick_view_image_pagination' => 'line',
										),
										4 => array(
											'quick_view_click_action' => 'right_sidebar',
											'quick_view_image_type' => 'image_with_gallery',
											'quick_view_image_gallery' => 'bottom',
										),
										5 => array(
											'quick_view_click_action' => 'left_sidebar',
											'quick_view_image_type' => 'image_with_gallery',
											'quick_view_image_gallery' => 'bottom',
										),
									),
								),
								'quick_view_preset' => array(
									'type'       => 'preset_color',
									'label'      => __( 'Browse Presets', 'product-blocks' ),
									'default'    => '1',
									'options'    => array(
										'1' => array( '#ff176b', '#070C1A', '#E5E5E5', '#FFFFFF' ),
										'2' => array( '#4558FF', '#1B233A', '#E5E5E5', '#FFFFFF' ),
										'3' => array( '#FF9B26', '#070C1A', '#E5E5E5', '#FFFFFF' ),
										'4' => array( '#FF4319', '#383838', '#E5E5E5', '#FFFFFF' ),
										'5' => array( '#2AAB6F', '#101010', '#E5E5E5', '#FFFFFF' ),
									),
									'variations' => array(
										0 => array(
											'quick_view_btn_bg' => 'bg',
										),
										1 => array(
											'quick_view_title_typo' => 'color',
										),
										2 => array(
											'quick_view_btn_border' => 'color',
										),
										3 => 'quick_view_modal_bg',
									),
								),
								'shop_style'        => array(
									'type'  => 'section',
									'label' => __( 'Shop/Archive Page Button Style', 'product-blocks' ),
									'attr'  => array(
										'quick_view_btn_typo' => array(
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
										'quick_view_btn_bg' => array(
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
										'quick_view_btn_padding' => array(
											'type'    => 'dimension',
											'label'   => __( 'Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 0,
												'bottom' => 0,
												'left'   => 0,
												'right'  => 0,
											),
										),
										'quick_view_btn_border' => array(
											'type'    => 'border',
											'label'   => __( 'Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 0,
												'color'  => '',
											),
										),
										'quick_view_btn_radius' => array(
											'type'    => 'number',
											'label'   => __( 'Border Radius', 'product-blocks' ),
											'default' => 0,
										),
										'quick_view_icon_size' => array(
											'type'    => 'number',
											'label'   => __( 'Icon Size', 'product-blocks' ),
											'default' => 16,
										),
										'quick_view_btn_align' => array(
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
								'modal_style'       => array(
									'type'  => 'section',
									'label' => __( 'Modal Style', 'product-blocks' ),
									'attr'  => array(
										'quick_view_title_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Title Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 32,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#070707',
												'hover_color' => '',
											),
										),
										'quick_view_modal_btn_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Button Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 14,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#ffffff',
												'hover_color' => '#ff176b',
											),
										),
										'quick_view_modal_btn_border' => array(
											'type'    => 'border',
											'label'   => __( 'Button Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 1,
												'color'  => '#ff176b',
											),
										),
										'quick_view_modal_btn_bg' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Button Background', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '#ff176b',
												'hover_bg' => '#ffffff',
											),
											'tooltip' => __( 'Background Color', 'product-blocks' ),
										),
										'quick_view_modal_bg' => array(
											'type'    => 'color',
											'label'   => __( 'Modal Background', 'product-blocks' ),
											'default' => '#FFFFFF',
											'tooltip' => __( 'Color', 'product-blocks' ),
										),
										'quick_view_content_inner_gap' => array(
											'type'       => 'number',
											'plus_minus' => true,
											'label'      => __( 'Inner Content Gap', 'product-blocks' ),
											'default'    => 15,
										),
										'quick_view_thumbnail_ratio' => array(
											'type'    => 'select',
											'label'   => __( 'Thumbnail Ratio', 'product-blocks' ),
											'options' => array(
												'default' => __( 'Default', 'product-blocks' ),
												'custom'  => __( 'Custom', 'product-blocks' ),
											),
											'default' => 'default',
										),
										'quick_view_thumbnail_height' => array(
											'type'    => 'number',
											'label'   => __( 'Thumbnail Height', 'product-blocks' ),
											'default' => '350',
											'depends' => array(
												'key'   => 'quick_view_thumbnail_ratio',
												'condition' => '==',
												'value' => 'custom',
											),
										),
										'quick_view_thumbnail_width' => array(
											'type'    => 'number',
											'label'   => __( 'Thumbnail Width', 'product-blocks' ),
											'default' => '450',
											'depends' => array(
												'key'   => 'quick_view_thumbnail_ratio',
												'condition' => '==',
												'value' => 'custom',
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
