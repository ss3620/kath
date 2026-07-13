<?php
defined( 'ABSPATH' ) || exit;

/**
 * Compare Addons Initial Configuration
 *
 * @since v.1.1.0
 */
add_filter( 'wopb_addons_config', 'wopb_compare_config' );
function wopb_compare_config( $config ) {
	$configuration          = array(
		'name'     => __( 'Product Compare', 'product-blocks' ),
		'desc'     => __( 'Let your shoppers compare multiple products by displaying a pop-up or redirecting to a compare page.', 'product-blocks' ),
		'is_pro'   => false,
		'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-product-comparison/',
		'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/compare-settings/',
		'type'     => 'exclusive',
		'priority' => 40,
	);
	$config['wopb_compare'] = $configuration;
	return $config;
}

/**
 * Compare Addons Default Settings Param
 *
 * @since v.1.1.0
 * @param ARRAY | Default Filter Congiguration
 * @return ARRAY
 */
add_filter( 'wopb_settings', 'wopb_get_compare_settings', 10, 1 );
function wopb_get_compare_settings( $config ) {
	$compare_icons   = array(
		'compare_1' => wopb_function()->svg_icon( 'compare_1' ),
		'compare_2' => wopb_function()->svg_icon( 'compare_2' ),
		'compare_3' => wopb_function()->svg_icon( 'compare_3' ),
		'compare_4' => wopb_function()->svg_icon( 'compare_4' ),
		'compare_5' => wopb_function()->svg_icon( 'compare_5' ),
		'compare_6' => wopb_function()->svg_icon( 'compare_6' ),
		'compare_7' => wopb_function()->svg_icon( 'compare_7' ),
		'compare_8' => wopb_function()->svg_icon( 'compare_8' ),
	);
	$default_columns = array(
		array(
			'key'   => 'image',
			'label' => __( 'Image', 'product-blocks' ),
		),
		array(
			'key'   => 'title',
			'label' => __( 'Title', 'product-blocks' ),
		),
		array(
			'key'   => 'price',
			'label' => __( 'Price', 'product-blocks' ),
		),
		array(
			'key'   => 'stock_status',
			'label' => __( 'Stock Status', 'product-blocks' ),
		),
		array(
			'key'   => 'quantity',
			'label' => __( 'Quantity', 'product-blocks' ),
		),
		array(
			'key'   => 'add_to_cart',
			'label' => __( 'Add To Cart', 'product-blocks' ),
		),
		array(
			'key'   => 'review',
			'label' => __( 'Review', 'product-blocks' ),
		),
	);
	$all_columns     = array(
		array(
			'key'   => '',
			'label' => __( 'Select Column', 'product-blocks' ),
		),
		...$default_columns,
		array(
			'key'   => 'additional',
			'label' => __( 'Additional', 'product-blocks' ),
		),
		array(
			'key'   => 'description',
			'label' => __( 'Description', 'product-blocks' ),
		),
		array(
			'key'   => 'weight',
			'label' => __( 'Weight', 'product-blocks' ),
		),
		array(
			'key'   => 'dimensions',
			'label' => __( 'Dimensions', 'product-blocks' ),
		),
		array(
			'key'   => 'sku',
			'label' => __( 'SKU', 'product-blocks' ),
		),
	);
	$arr             = array(
		'wopb_compare' => array(
			'label' => __( 'Compare', 'product-blocks' ),
			'attr'  => array(
				'compare_heading' => array(
					'type'  => 'heading',
					'label' => __( 'Compare Settings', 'product-blocks' ),
				),
				'tab'             => (object) array(
					'type'    => 'tab',
					'options' => array(
						'settings' => (object) array(
							'label' => __( 'Settings', 'product-blocks' ),
							'attr'  => array(
								'wopb_compare'             => array(
									'type'  => 'toggle',
									'value' => 'true',
									'label' => __( 'Enable Compare', 'product-blocks' ),
									'desc'  => __( 'Enable compare on your website', 'product-blocks' ),
								),
								'compare_general_settings' => array(
									'type'  => 'section',
									'label' => __( 'General Settings', 'product-blocks' ),
									'attr'  => array(
										'compare_page' => array(
											'type'    => 'select',
											'label'   => __( 'Select Compare Page', 'product-blocks' ),
											'options' => wopb_function()->all_pages( true ),
											'desc'    => __( 'Select the page containing the ', 'product-blocks' ) . '[wopb_compare] ' . __( 'shortcode.', 'product-blocks' ),
										),
										'compare_my_account_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Add Compare Page To My Account', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable compare page to my account.', 'product-blocks' ),
										),
										'compare_action_added' => array(
											'type'    => 'radio',
											'label'   => __( 'Action After Click to Compare', 'product-blocks' ),
											'display' => 'inline-box',
											'options' => array(
												'popup'    => __( 'Popup', 'product-blocks' ),
												'redirect' => __( 'Redirect to Page', 'product-blocks' ),
												'sidebar'  => __( 'Sidebar', 'product-blocks' ),
												'message'  => __( 'Show Message', 'product-blocks' ),
											),
											'default' => 'popup',
											'desc'    => __( 'Select option for what will happen after clicking on the compare button.', 'product-blocks' ),
										),
										'compare_modal_loading' => array(
											'type'    => 'radio',
											'label'   => __( 'Compare Modal Loading', 'product-blocks' ),
											'display' => 'inline-box',
											'depends' => array(
												'key'   => 'compare_action_added',
												'condition' => '==',
												'value' => array( 'popup', 'sidebar' ),
											),
											'options' => wopb_function()->modal_loaders(),
											'default' => 'loader_1',
											'desc'    => __( 'Select loading icon to show before open compare modal.', 'product-blocks' ),
										),
										'compare_modal_open_animation' => array(
											'type'    => 'select',
											'label'   => __( 'Modal Opening Animation', 'product-blocks' ),
											'depends' => array(
												'key'   => 'compare_action_added',
												'condition' => '==',
												'value' => array( 'popup', 'sidebar' ),
											),
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
										'compare_modal_close_animation' => array(
											'type'    => 'select',
											'label'   => __( 'Modal Closing Animation', 'product-blocks' ),
											'depends' => array(
												'key'   => 'compare_action_added',
												'condition' => '==',
												'value' => array( 'popup', 'sidebar' ),
											),
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
										'compare_hide_empty_table' => array(
											'type'    => 'toggle',
											'label'   => __( 'Hide Compare Table If Empty', 'product-blocks' ),
											'default' => '',
											'desc'    => __( "Hide compare table if haven't any product.", 'product-blocks' ),
										),
									),
								),
								'compare_shop_settings'    => array(
									'type'  => 'section',
									'label' => __( 'Compare in Shop/Archive Page', 'product-blocks' ),
									'attr'  => array(
										'compare_shop_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Show Compare In Shop Page', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable compare on your shop page.', 'product-blocks' ),
										),
										'compare_position_shop_page' => array(
											'type'    => 'select',
											'label'   => __( 'Button Position on Shop Page', 'product-blocks' ),
											'desc'    => __( 'Choose where will place compare button on shop page.', 'product-blocks' ),
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
												'text'    => __( 'Use this shortcode [wopb_compare_button] where you want to show compare button.', 'product-blocks' ),
												'depends' => array(
													'key' => 'compare_position_shop_page',
													'condition' => '==',
													'value' => 'shortcode',
												),
											),
										),
										'compare_text' => array(
											'type'    => 'text',
											'label'   => __( 'Compare Button Text', 'product-blocks' ),
											'default' => __( 'Add to Compare', 'product-blocks' ),
										),
										'compare_added_text' => array(
											'type'    => 'text',
											'label'   => __( 'Button Text After Add To Compare', 'product-blocks' ),
											'default' => __( 'Added', 'product-blocks' ),
										),
										'compare_button_icon_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Button Icon', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable button icon to display icon on compare button.', 'product-blocks' ),
										),
										'compare_button_icon' => array(
											'type'    => 'radio',
											'label'   => __( 'Choose Icon', 'product-blocks' ),
											'display' => 'inline-box',
											'depends' => array(
												'key'   => 'compare_button_icon_enable',
												'condition' => '==',
												'value' => 'yes',
											),
											'options' => $compare_icons,
											'default' => 'compare_1',
											'desc'    => __( 'Choose icon display on compare button.', 'product-blocks' ),
										),
										'compare_button_icon_position' => array(
											'type'    => 'radio',
											'label'   => __( 'Icon Position', 'product-blocks' ),
											'display' => 'inline-box',
											'depends' => array(
												array(
													'key' => 'compare_button_icon_enable',
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
								'compare_single_settings'  => array(
									'type'  => 'section',
									'label' => __( 'Compare in Single Page', 'product-blocks' ),
									'attr'  => array(
										'compare_single_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Show Compare In Single Product Page', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable compare on your single product page.', 'product-blocks' ),
										),
										'compare_position' => array(
											'type'    => 'select',
											'label'   => __( 'Button Position on Single Page', 'product-blocks' ),
											'desc'    => __( 'Choose where will place compare button on single page.', 'product-blocks' ),
											'options' => array(
												'after_cart' => __( 'After Add to Cart', 'product-blocks' ),
												'bottom_cart' => __( 'Bottom Add to Cart', 'product-blocks' ),
												'top_cart' => __( 'Top Add to Cart', 'product-blocks' ),
												'before_cart' => __( 'Before Add to Cart', 'product-blocks' ),
												'shortcode' => __( 'Use Shortcode', 'product-blocks' ),
											),
											'default' => 'bottom_cart',
											'note'    => (object) array(
												'text'    => __( 'Use this shortcode [wopb_compare_button] where you want to show compare button.', 'product-blocks' ),
												'depends' => array(
													'key' => 'compare_position',
													'condition' => '==',
													'value' => 'shortcode',
												),
											),
										),
										'compare_text_single' => array(
											'type'    => 'text',
											'label'   => __( 'Compare Button Text', 'product-blocks' ),
											'default' => __( 'Add to Compare', 'product-blocks' ),
											'desc'    => __( 'Write your preferable text to show  on compare button', 'product-blocks' ),
										),
										'compare_added_text_single' => array(
											'type'    => 'text',
											'label'   => __( 'Button Text After Add To Compare', 'product-blocks' ),
											'default' => __( 'Added', 'product-blocks' ),
											'desc'    => __( 'Write your preferable text after clicking add to compare', 'product-blocks' ),
										),
										'compare_icon_enable_single' => array(
											'type'    => 'toggle',
											'label'   => __( 'Button Icon', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable button icon to display icon on compare button.', 'product-blocks' ),
										),
										'compare_icon_single' => array(
											'type'    => 'radio',
											'label'   => __( 'Choose Icon', 'product-blocks' ),
											'display' => 'inline-box',
											'depends' => array(
												'key'   => 'compare_icon_enable_single',
												'condition' => '==',
												'value' => 'yes',
											),
											'options' => $compare_icons,
											'default' => 'compare_1',
											'desc'    => __( 'Choose icon display on compare button.', 'product-blocks' ),
										),
										'compare_icon_position_single' => array(
											'type'    => 'radio',
											'label'   => __( 'Icon Position', 'product-blocks' ),
											'display' => 'inline-box',
											'depends' => array(
												array(
													'key' => 'compare_icon_enable_single',
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
								'compare_nav_menu_settings' => array(
									'type'  => 'section',
									'label' => __( 'Compare Menu in Navbar', 'product-blocks' ),
									'attr'  => array(
										'compare_nav_menu_enable' => array(
											'type'    => 'toggle',
											'label'   => __( 'Add Compare Menu In Navbar', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable compare menu in navbar.', 'product-blocks' ),
										),
										'compare_nav_menu_location' => array(
											'type'    => 'select',
											'label'   => __( 'Nav Menu Location', 'product-blocks' ),
											'options' => wopb_function()->wopb_nav_menu_location(),
											'default' => '',
											'depends' => array(
												'key'   => 'compare_nav_menu_enable',
												'condition' => '==',
												'value' => 'yes',
											),
										),
										'compare_nav_text' => array(
											'type'    => 'text',
											'label'   => __( 'Compare Button Text', 'product-blocks' ),
											'default' => '',
											'depends' => array(
												'key'   => 'compare_nav_menu_enable',
												'condition' => '==',
												'value' => 'yes',
											),
											'desc'    => __( 'Write your preferable text to show  on compare button', 'product-blocks' ),
										),
										'compare_nav_icon' => array(
											'type'    => 'radio',
											'label'   => __( 'Choose Icon', 'product-blocks' ),
											'display' => 'inline-box',
											'depends' => array(
												'key'   => 'compare_nav_menu_enable',
												'condition' => '==',
												'value' => 'yes',
											),
											'options' => $compare_icons,
											'default' => 'compare_1',
										),
										'compare_nav_icon_position' => array(
											'type'    => 'radio',
											'label'   => __( 'Icon Position', 'product-blocks' ),
											'display' => 'inline-box',
											'depends' => array(
												array(
													'key' => 'compare_nav_menu_enable',
													'condition' => '==',
													'value' => 'yes',
												),
											),
											'options' => array(
												'before_text' => 'Before Text',
												'after_text' => 'After Text',
												'top_text' => 'Top of Text',
											),
											'default' => 'before_text',
										),
										'compare_nav_click_action' => array(
											'type'    => 'radio',
											'label'   => __( 'Action After Click Menu', 'product-blocks' ),
											'display' => 'inline-box',
											'depends' => array(
												'key'   => 'compare_nav_menu_enable',
												'condition' => '==',
												'value' => 'yes',
											),
											'options' => array(
												'popup'    => 'Popup',
												'redirect' => 'Redirect',
											),
											'default' => 'popup',
										),
										'compare_nav_menu_shortcode' => array(
											'type'    => 'toggle',
											'label'   => __( 'Compare Menu Custom Position', 'product-blocks' ),
											'default' => '',
											'desc'    => __( 'Enable shortcode for custom position.', 'product-blocks' ),
											'depends' => array(
												'key'   => 'compare_nav_menu_enable',
												'condition' => '==',
												'value' => 'yes',
											),
											'note'    => (object) array(
												'text'    => __( 'Use this shortcode [wopb_compare_nav] for custom position of comparison menu on navbar.', 'product-blocks' ),
												'depends' => array(
													'key' => 'compare_nav_menu_shortcode',
													'condition' => '==',
													'value' => 'yes',
												),
											),
										),
									),
								),
								'compare_table_settings'   => array(
									'type'  => 'section',
									'label' => __( 'Compare Table Settings', 'product-blocks' ),
									'attr'  => array(
										'compare_table_columns' => array(
											'type'    => 'select_item',
											'label'   => __( 'Select Fields To Show In Comparison Table', 'product-blocks' ),
											'desc'    => __( 'Select the fields you want to include in the comparison table. To rearrange these fields, you can also drag and drop. You can also customize the label name according to your preferences.', 'product-blocks' ),
											'default' => $default_columns,
											'options' => $all_columns,
										),
										'compare_add_product_button' => array(
											'type'     => 'toggle',
											'label'    => __( 'Add New Product Button on Table', 'product-blocks' ),
											'default'  => 'yes',
											'desc'     => __( 'Enable add new product button on compare table.', 'product-blocks' ),
											'hr_line2' => true,
										),
										'compare_clear' => array(
											'type'    => 'toggle',
											'label'   => __( 'Clear Compare Product List', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable clear all button at bottom right corner on compare table.', 'product-blocks' ),
										),
										'compare_first_column_sticky' => array(
											'type'    => 'toggle',
											'label'   => __( 'Freeze Table First Column', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable freeze the compare table first column when scrolling horizontally.', 'product-blocks' ),
										),
										'compare_first_row_sticky' => array(
											'type'    => 'toggle',
											'label'   => __( 'Freeze Table First Row', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable freeze the compare table first row when scrolling vertically.', 'product-blocks' ),
										),
										'compare_close_button' => array(
											'type'    => 'toggle',
											'label'   => __( 'Close Button on Table', 'product-blocks' ),
											'default' => 'yes',
											'desc'    => __( 'Enable close button at top right corner on compare table.', 'product-blocks' ),
										),
									),
								),
							),
						),
						'design'   => (object) array(
							'label' => __( 'Design', 'product-blocks' ),
							'attr'  => array(
								'compare_layout'       => array(
									'type'       => 'layout',
									'label'      => __( 'Choose Comparison Table Layout', 'product-blocks' ),
									'default'    => 1,
									'options'    => array(
										(object) array(
											'key'   => 1,
											'image' => WOPB_URL . 'assets/img/addons/compare/layout_1.png',
											'pro'   => false,
										),
										(object) array(
											'key'   => 2,
											'image' => WOPB_URL . 'assets/img/addons/compare/layout_2.png',
											'pro'   => false,
										),
										(object) array(
											'key'   => 3,
											'image' => WOPB_URL . 'assets/img/addons/compare/layout_3.png',
											'pro'   => false,
										),
										(object) array(
											'key'   => 4,
											'image' => WOPB_URL . 'assets/img/addons/compare/layout_4.png',
											'pro'   => false,
										),
										(object) array(
											'key'   => 5,
											'image' => WOPB_URL . 'assets/img/addons/compare/layout_5.png',
											'pro'   => false,
										),
									),
									'preview'    => true,
									'variations' => array(
										1 => array(
											'compare_odd_column_background' => '',
											'compare_even_column_background' => '',
										),
										2 => array(
											'compare_odd_column_background' => '',
											'compare_even_column_background' => '',
										),
										3 => array(
											'compare_odd_column_background' => '',
											'compare_even_column_background' => '',
										),
										4 => array(
											'compare_odd_column_background' => '',
											'compare_even_column_background' => '',
										),
										5 => array(
											'compare_odd_column_background' => '',
											'compare_even_column_background' => '',
										),
									),
								),
								'compare_preset'       => array(
									'type'       => 'preset_color',
									'label'      => __( 'Browse Presets', 'product-blocks' ),
									'default'    => '1',
									'options'    => array(
										'1' => array( '#ff176b', '#070C1A', '#5A5A5A', '#E5E5E5', '#FFFFFF' ),
										'2' => array( '#4558FF', '#1B233A', '#5A5A5A', '#E5E5E5', '#FFFFFF' ),
										'3' => array( '#FF9B26', '#070C1A', '#5A5A5A', '#E5E5E5', '#FFFFFF' ),
										'4' => array( '#FF4319', '#383838', '#5A5A5A', '#E5E5E5', '#FFFFFF' ),
										'5' => array( '#2AAB6F', '#101010', '#5A5A5A', '#E5E5E5', '#FFFFFF' ),
									),
									'variations' => array(
										0 => array(
											'compare_btn_bg_shop' => 'bg',
											'compare_btn_bg_single' => 'bg',
											'compare_btn_bg_nav' => 'bg',
										),
										1 => array(
											'compare_heading_typo' => 'color',
										),
										2 => array(
											'compare_btn_typo_shop' => 'color',
											'compare_btn_typo_single' => 'color',
											'compare_btn_typo_nav' => 'color',
											'compare_body_text_typo' => 'color',
										),
										3 => array(
											'compare_btn_border_shop' => 'color',
											'compare_btn_border_single' => 'color',
											'compare_btn_border_nav' => 'color',
											'compare_column_border' => 'color',
										),
									),
								),
								'compare_shop_style'   => array(
									'type'  => 'section',
									'label' => __( 'Shop/Archive Page Button Style', 'product-blocks' ),
									'attr'  => array(
										'compare_btn_typo_shop' => array(
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
										'compare_btn_bg_shop' => array(
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
										'compare_btn_padding_shop' => array(
											'type'    => 'dimension',
											'label'   => __( 'Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 0,
												'bottom' => 0,
												'left'   => 0,
												'right'  => 0,
											),
										),
										'compare_btn_border_shop' => array(
											'type'    => 'border',
											'label'   => __( 'Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 0,
												'color'  => '',
											),
										),
										'compare_btn_radius_shop' => array(
											'type'    => 'number',
											'label'   => __( 'Border Radius', 'product-blocks' ),
											'default' => 0,
										),
										'compare_icon_size_shop' => array(
											'type'    => 'number',
											'label'   => __( 'Icon Size', 'product-blocks' ),
											'default' => 16,
										),
										'compare_align_shop' => array(
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
								'compare_single_style' => array(
									'type'  => 'section',
									'label' => __( 'Product Single Page Button Style', 'product-blocks' ),
									'attr'  => array(
										'compare_btn_typo_single' => array(
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
										'compare_btn_bg_single' => array(
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
										'compare_btn_padding_single' => array(
											'type'    => 'dimension',
											'label'   => __( 'Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 0,
												'bottom' => 0,
												'left'   => 0,
												'right'  => 0,
											),
										),
										'compare_btn_border_single' => array(
											'type'    => 'border',
											'label'   => __( 'Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 0,
												'color'  => '',
											),
										),
										'compare_btn_radius_single' => array(
											'type'    => 'number',
											'label'   => __( 'Border Radius', 'product-blocks' ),
											'default' => 0,
										),
										'compare_icon_size_single' => array(
											'type'    => 'number',
											'label'   => __( 'Icon Size', 'product-blocks' ),
											'default' => 16,
										),
									),
								),
								'compare_nav_style'    => array(
									'type'  => 'section',
									'label' => __( 'Navbar Button Style', 'product-blocks' ),
									'attr'  => array(
										'compare_btn_typo_nav' => array(
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
										'compare_btn_bg_nav' => array(
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
										'compare_btn_padding_nav' => array(
											'type'    => 'dimension',
											'label'   => __( 'Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 0,
												'bottom' => 0,
												'left'   => 0,
												'right'  => 0,
											),
										),
										'compare_btn_border_nav' => array(
											'type'    => 'border',
											'label'   => __( 'Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 0,
												'color'  => '',
											),
										),
										'compare_btn_radius_nav' => array(
											'type'    => 'number',
											'label'   => __( 'Border Radius', 'product-blocks' ),
											'default' => 0,
										),
										'compare_icon_size_nav' => array(
											'type'    => 'number',
											'label'   => __( 'Icon Size', 'product-blocks' ),
											'default' => 18,
										),
									),
								),
								'compare_table_style'  => array(
									'type'  => 'section',
									'label' => __( 'Table Column Style', 'product-blocks' ),
									'attr'  => array(
										'compare_tbl_cart_btn_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Cart Button Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 16,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#ffffff',
												'hover_color' => '',
											),
										),
										'compare_tbl_cart_btn_bg' => array(
											'type'    => 'color2',
											'field1'  => 'bg',
											'field2'  => 'hover_bg',
											'label'   => __( 'Cart Button Background', 'product-blocks' ),
											'default' => (object) array(
												'bg'       => '#ff176b',
												'hover_bg' => '',
											),
											'tooltip' => __( 'Background Color', 'product-blocks' ),
										),
										'compare_tbl_cart_btn_padding' => array(
											'type'    => 'dimension',
											'label'   => __( 'Cart Button Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 10,
												'bottom' => 10,
												'left'   => 20,
												'right'  => 20,
											),
										),
										'compare_tbl_cart_btn_border' => array(
											'type'    => 'border',
											'label'   => __( 'Cart Button Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 0,
												'color'  => '',
											),
										),
										'compare_tbl_cart_btn_radius' => array(
											'type'    => 'number',
											'label'   => __( 'Cart Button Border Radius', 'product-blocks' ),
											'default' => 4,
										),
										'compare_heading_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Heading Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 16,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#070C1A',
												'hover_color' => '',
											),
										),
										'compare_body_text_typo' => array(
											'type'    => 'typography',
											'label'   => __( 'Body Text Typography', 'product-blocks' ),
											'default' => (object) array(
												'size'   => 14,
												'bold'   => false,
												'italic' => false,
												'underline' => false,
												'color'  => '#5A5A5A',
												'hover_color' => '',
											),
										),
										'compare_column_padding' => array(
											'type'    => 'dimension',
											'label'   => __( 'Column Padding', 'product-blocks' ),
											'default' => (object) array(
												'top'    => 12,
												'bottom' => 12,
												'left'   => 12,
												'right'  => 12,
											),
										),
										'compare_column_space' => array(
											'type'    => 'number',
											'label'   => __( 'Column Spacing', 'product-blocks' ),
											'default' => 0,
										),
										'compare_column_border' => array(
											'type'    => 'border',
											'label'   => __( 'Border', 'product-blocks' ),
											'default' => (object) array(
												'border' => 1,
												'color'  => '#E5E5E5',
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
