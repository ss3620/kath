<?php
/**
 * Preorder Addons Core.
 *
 * @package WOPB\Preorder
 * @since v.1.0.4
 */

namespace WOPB;

use WC_Order_Item_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Preorder class.
 */
class Preorder {


	/**
	 * Setup class.
	 *
	 * @since v.1.0.4
	 */
	public function __construct() {
		// Custom Option in Variable Product
		add_action( 'woocommerce_variation_options', array( $this, 'wopb_add_preorder_variable_checkbox' ), 10, 3 );
		add_filter( 'product_type_options', array( $this, 'wopb_add_custom_product_type' ), 5 );

		// custom field in variable product
		add_action( 'woocommerce_product_after_variable_attributes', array( $this, 'wopb_pre_order_custom_field_variation' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'wopb_pre_order_save_variation_data' ), 10, 2 );

		// Pre-order field save in woocomerce product page in admin panel
		add_action( 'woocommerce_process_product_meta', array( $this, 'pre_order_woocommerce_fields_save' ), 10, 2 );

		// woocommerce add column to order table in admin panel
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_column_in_order_listing_page' ), 10, 1 );
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'set_order_type_column_value' ), 10, 2 );

		add_filter( 'woocommerce_available_variation', array( $this, 'wopb_show_pre_order_variation_content_in_single_product' ), 10, 3 );

		// check product Pre-order end or not
		add_action( 'woocommerce_is_purchasable', array( $this, 'is_purchasable_products' ), 1, 2 );

		// validate pre order item when add to cart
		add_filter( 'woocommerce_add_to_cart', array( $this, 'is_validate_order_item' ), 10, 6 );

		// WooCommerce cart item name
		add_filter( 'woocommerce_cart_item_name', array( $this, 'wopb_woocommerce_cart_item_name' ), 10, 4 );

		// WooCommerce order item meta data
		add_filter( 'woocommerce_order_item_get_formatted_meta_data', array( $this, 'woocommerce_re_format_order_item_meta' ), 10, 4 );

		// WooCommerce checkout order line meta modify
		add_filter( 'woocommerce_checkout_create_order_line_item', array( $this, 'wopb_add_pre_order_meta_to_item' ), 10, 4 );

		// Filter hook for change add to cart button text in single product
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'add_to_cart_button_text' ), 10, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'add_to_cart_button_text' ), 10, 2 );

		// WowStore tab in woocomerce product page in admin panel
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'wopb_wowstore_tab_data' ), 10, 1 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'wopb_wowstore_custom_field_simple' ) );

		// Add Script for Addons
		add_action( 'wp_enqueue_scripts', array( $this, 'add_preorder_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'add_preorder_scripts' ) );

		// Before Single Product
		add_action( 'woocommerce_before_single_product', array( $this, 'before_single_product' ) );
		add_action( 'woocommerce_before_single_product', array( $this, 'before_single_product' ) );

		add_action( 'wopb_save_settings', array( $this, 'generate_css' ), 10, 1 ); // CSS Generator
	}

	/**
	 * Auto Convert Product Pre-order to Simple
	 *
	 * @since v.1.0.4
	 * @return NULL
	 */
	public function before_single_product( $admin_product = null ) {
		global $product;
		$this->autoconvert_product();
		if (
			! $product->is_type( 'grouped' ) &&
			$product->is_purchasable()
		) {
			if ( ! wopb_function()->is_builder() ) {
				add_filter(
					'woocommerce_get_price_html',
					function ( $price, $product ) {
						return $this->pre_order_content( $price, $product );
					},
					50,
					2
				);
			} else {
				add_filter(
					'wopb_after_single_product_price',
					function ( $price, $product ) {
						return $this->pre_order_content( $price, $product );
					},
					50,
					2
				);
			}
		}
	}

	public function autoconvert_product( $admin_product = null ) {
		global $product;
		if ( $admin_product ) {
			$product = $admin_product;
		}
		$type = $product->get_type();
		if ( $this->is_simple_preorder( $product ) && $type == 'simple' && $this->is_auto_convert_available( $product ) && wopb_function()->is_preorder_closed( $product ) ) {
			$product->update_meta_data( '_wopb_preorder_simple', '' );
			$product->save();
		} elseif ( $type == 'variable' ) {
			foreach ( $product->get_available_variations() as $variation ) {
				$variation = wc_get_product( $variation['variation_id'] );
				if ( $this->is_variable_preorder( $variation ) && $this->is_auto_convert_available( $variation ) && wopb_function()->is_preorder_closed( $variation ) ) {
					$variation->update_meta_data( '_wopb_preorder_variable', '' );
					$variation->save();
				}
			}
		}
	}

	/**
	 * Pre-Order JS & CSS Script
	 *
	 * @since v.1.0.4
	 * @return NULL
	 */
	public function add_preorder_scripts() {
		wp_enqueue_style( 'wopb-preorder-style', WOPB_URL . 'addons/preorder/css/preorder.css', array(), WOPB_VER );
		wp_enqueue_script( 'wopb-preorder-script', WOPB_URL . 'addons/preorder/js/preorder.js', array( 'jquery' ), WOPB_VER, true );
	}

	/**
	 * Pre-Order Custom Field
	 *
	 * @since v.1.0.4
	 * @return NULL
	 */
	public function wopb_wowstore_custom_field_simple() {
		$html = '';
		global $post;
		$html                 .= '<div class="panel woocommerce_options_panel hidden" id="productx_tab_data">';
			$html             .= '<div class="wopb-productx-options-tab-wrap">';
				$html         .= '<div class="wopb-woocommerce-preorder-field-group" id="wopb-woocommerce-preorder-field-group">';
					$html     .= $this->generate_field( $post->ID, '' );
				$html         .= '</div>';
				$html         .= '<div id="wopb-preorder-select-instruction">';
					$html     .= '<h2 class="wopb-preorder-select-instruction-title">';
						$html .= '<span class="dashicons dashicons-warning"></span>';
						$html .= __( 'For giving pre-order information please select Pre-Order Checkbox', 'product-blocks' );
					$html     .= '</h2>';
				$html         .= '</div>';
			$html             .= '</div>';
		$html                 .= '</div>';

		echo $html;
	}

	/**
	 * Productx tab in woocomerce product page in admin panel
	 *
	 * @return ARRAY
	 * @since v.1.0.4
	 */
	public function wopb_wowstore_tab_data( $product_data_tabs ) {
		$product_data_tabs['productx'] = array(
			'label'    => __( 'Preorder', 'product-blocks' ),
			'class'    => array( 'show_if_simple', 'hidden', 'wopb_product_tab' ),
			'target'   => 'productx_tab_data',
			'priority' => 15.1,
		);
		return $product_data_tabs;
	}

	/**
	 * Pre Order Option Added
	 *
	 * @return ARRAY
	 * @since v.1.0.4
	 */
	public function wopb_add_custom_product_type( $product_type_options ) {
		$product_type_options['wopb_preorder_simple'] = array(
			'id'            => '_wopb_preorder_simple',
			'wrapper_class' => 'show_if_simple wopb_preorder_simple',
			'label'         => __( 'Pre-Order', 'product-blocks' ),
			'description'   => __( 'If you want to set pre-order click here', 'product-blocks' ),
			'default'       => 'no',
		);
		return $product_type_options;
	}

	/**
	 * Change Add to Cart Button Text
	 *
	 * @return NULL
	 * @since v.1.0.4
	 */
	public function add_to_cart_button_text( $text, $product ) {
		if (
			$this->is_simple_preorder( $product ) &&
			! wopb_function()->is_preorder_closed( $product ) &&
			wopb_function()->get_setting( 'preorder_add_to_cart_button_text' ) &&
			$product->is_purchasable()
		) {
			return wopb_function()->get_setting( 'preorder_add_to_cart_button_text' );
		} else {
			return $text;
		}
	}


	/**
	 * Preorder Addons Intitial Setup Action
	 *
	 * @return NULL
	 * @since v.1.0.4
	 */
	public function initial_setup() {
		$settings     = wopb_function()->get_setting();
		$initial_data = array(
			'preorder_heading'                 => 'yes',
			'preorder_counter_disable'         => '',
			'preorder_button_text'             => __( 'Pre-order', 'product-blocks' ),
			'preorder_add_to_cart_button_text' => __( 'Pre-order Now', 'product-blocks' ),
			'preorder_message_text'            => 'Available On',
			'preorder_coming_soon_text'        => 'Coming Soon',

			'preorder_available_typo'          => array(
				'size'        => 16,
				'bold'        => true,
				'italic'      => true,
				'underline'   => false,
				'color'       => '#398e29',
				'hover_color' => '',
			),
			'preorder_duration_typo'           => array(
				'size'        => 16,
				'bold'        => true,
				'italic'      => true,
				'underline'   => false,
				'color'       => '#398e29',
				'hover_color' => '',
			),
			'preorder_remain_typo'             => array(
				'size'        => 16,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => '',
				'hover_color' => '',
			),
			'preorder_count_typo'              => array(
				'size'        => 16,
				'bold'        => true,
				'italic'      => false,
				'underline'   => false,
				'color'       => '#333333',
				'hover_color' => '',
			),
			'preorder_timer_bg'                => array(
				'bg'       => '#191924',
				'hover_bg' => '',
			),
			'preorder_time_typo'               => array(
				'size'        => 20,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => '#ffffff',
				'hover_color' => '',
			),
			'preorder_time_label_typo'         => array(
				'size'        => 12,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => '#ffffff',
				'hover_color' => '',
			),
			'preorder_time_separator_typo'     => array(
				'size'        => 24,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => '#ffffff',
				'hover_color' => '',
			),
			'preorder_badge_typo'              => array(
				'size'        => 12,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => '#ffffff',
				'hover_color' => '',
			),
			'preorder_badge_bg'                => array(
				'bg'       => '#007cba',
				'hover_bg' => '',
			),
			'preorder_badge_padding'           => array(
				'top'    => 5,
				'bottom' => 5,
				'left'   => 5,
				'right'  => 5,
			),
			'preorder_badge_border'            => array(
				'border' => 0,
				'color'  => '',
			),
			'preorder_badge_radius'            => 0,
		);
		foreach ( $initial_data as $key => $val ) {
			if ( ! isset( $settings[ $key ] ) ) {
				wopb_function()->set_setting( $key, $val );
			}
		}
		$this->generate_css( 'wopb_preorder' );
	}


	/**
	 * Save input field when product save in admin panel
	 *
	 * @param INT | Product ID
	 * @return NULL
	 * @since v.1.0.4
	 */
	public function pre_order_woocommerce_fields_save( $post_id ) {
		$product = wc_get_product( $post_id );

		if ( isset( $_POST['_wopb_preorder_simple'] ) ) {
			$product->update_meta_data( '_wopb_preorder_simple', 'yes' );
			$product->update_meta_data( '_wopb_preorder_date', isset( $_POST['_wopb_preorder_date'] ) ? sanitize_text_field( $_POST['_wopb_preorder_date'] ) : '' );
			$product->update_meta_data( '_wopb_max_preorder', isset( $_POST['_wopb_max_preorder'] ) ? sanitize_text_field( $_POST['_wopb_max_preorder'] ) : '' );
			$product->update_meta_data( '_wopb_preorder_message', isset( $_POST['_wopb_preorder_message'] ) ? sanitize_text_field( $_POST['_wopb_preorder_message'] ) : '' );
			$product->update_meta_data( '_wopb_preorder_coming_soon', isset( $_POST['_wopb_preorder_coming_soon'] ) ? sanitize_text_field( $_POST['_wopb_preorder_coming_soon'] ) : '' );

			$product->update_meta_data( '_wopb_preorder_auto_convert', isset( $_POST['_wopb_preorder_auto_convert'] ) ? sanitize_text_field( $_POST['_wopb_preorder_auto_convert'] ) : '' );
			$product->update_meta_data( '_wopb_preorder_price_manage', isset( $_POST['_wopb_preorder_price_manage'] ) ? sanitize_text_field( $_POST['_wopb_preorder_price_manage'] ) : '' );

			$product->update_meta_data( '_wopb_preorder_price_type', isset( $_POST['_wopb_preorder_price_type'] ) ? sanitize_text_field( $_POST['_wopb_preorder_price_type'] ) : '' );
			$product->update_meta_data( '_wopb_preorder_price', isset( $_POST['_wopb_preorder_price'] ) ? sanitize_text_field( $_POST['_wopb_preorder_price'] ) : '' );
			if ( $_POST['_wopb_preorder_price_manage'] && isset( $_POST['_wopb_preorder_price_type'] ) ) {
				$regular_price = $product->get_regular_price();
				if ( $_POST['_wopb_preorder_price_type'] == 'fixed' && $_POST['_wopb_preorder_price'] ) {
					$product->set_sale_price( sanitize_text_field( $_POST['_wopb_preorder_price'] ) );
				} elseif ( $_POST['_wopb_preorder_price_type'] == 'percentage' && $_POST['_wopb_preorder_price'] && $regular_price ) {
					$product->set_sale_price( $regular_price - ( ( $regular_price * sanitize_text_field( $_POST['_wopb_preorder_price'] ) ) / 100 ) );
				}
			}
		} else {
			$product->update_meta_data( '_wopb_preorder_simple', '' );
		}
		$product->save();
	}

	/**
	 * Preorder Menu Item in WooCommerce
	 *
	 * @return NULL
	 * @since v.1.0.4
	 */
	public function wopb_add_preorder_variable_checkbox( $loop, $variation_data, $variation ) {
		$variable_product = wc_get_product( $variation->ID );
		if ( wopb_function()->get_setting( 'is_lc_active' ) ) {
			$is_variable_preorder = $variable_product->get_meta( '_wopb_preorder_variable' );
			echo '<label>' . esc_html__( 'Pre-Order', 'product-blocks' );
			echo '<input type="checkbox" id="_wopb_preorder_variable[' . esc_attr( $loop ) . ']" class="_wopb_preorder_variable" name="_wopb_preorder_variable[' . esc_attr( $loop ) . ']"' . ( $is_variable_preorder ? 'checked' : '' ) . '>';
			echo wc_help_tip( esc_html__( 'Enable pre-order for giving pre-order information', 'product-blocks' ) );
			echo '</label>';
		}
	}

	/**
	 * Pre Order Custom Field in Variation
	 *
	 * @return NULL
	 * @since v.1.0.4
	 */
	public function generate_field( $post_id, $loop = '' ) {
		$html    = '';
		$loop    = $loop !== '' ? '[' . $loop . ']' : '';
		$product = wc_get_product( $post_id );
		$this->autoconvert_product( $product );

		$preorder_message            = $product->get_meta( '_wopb_preorder_message' );
		$default_preorder_message    = wopb_function()->get_setting( 'preorder_message_text' );
		$coming_soon_message         = $product->get_meta( '_wopb_preorder_coming_soon' );
		$default_coming_soon_message = wopb_function()->get_setting( 'preorder_coming_soon_text' );
		$is_active                   = wopb_function()->get_setting( 'is_lc_active' );
		$html                       .= '<h4 class="wopb-woocommerce-preorder-title">' . __( 'WowStore Pre-order information', 'product-blocks' ) . '</h4>';
		ob_start();
		woocommerce_wp_text_input(
			array(
				'id'          => '_wopb_max_preorder' . $loop,
				'class'       => 'wopb_required',
				'label'       => __( 'Available Quantity', 'product-blocks' ),
				'type'        => 'number',
				'value'       => $product->get_meta( '_wopb_max_preorder' ),
				'desc_tip'    => true,
				'description' => __( 'Enter the maximum amount of products available for pre-order', 'product-blocks' ),
			)
		);

		if ( $is_active ) {
			woocommerce_wp_text_input(
				array(
					'id'          => '_wopb_preorder_date' . $loop,
					'class'       => 'wopb_preorder_duration',
					'label'       => __( 'Availability Date', 'product-blocks' ),
					'type'        => 'datetime-local',
					'value'       => $product->get_meta( '_wopb_preorder_date' ),
					'desc_tip'    => true,
					'description' => __( 'Message indicating date and time of the pre-order product availability', 'product-blocks' ),
				)
			);
		} else {
			echo '<p class="form-field">';
			echo '<label>' . esc_html__( 'Availability Date', 'product-blocks' ) . '</label>';
			echo '<span class="description" style="display:block;">';
			echo esc_html__( 'This feature is available in the Pro version.', 'product-blocks' );
			echo ' <a href="' . esc_url( wopb_function()->get_premium_link( 'https://www.wpxpo.com/wowstore', 'preorder' ) ) . '" target="_blank">';
			echo esc_html__( 'Upgrade to Pro', 'product-blocks' );
			echo '</a>';
			echo '</span>';
			echo '</p>';
		}

		woocommerce_wp_text_input(
			array(
				'id'          => '_wopb_preorder_message' . $loop,
				'class'       => 'wopb_preorder_duration',
				'label'       => __( 'Availability Message', 'product-blocks' ),
				'type'        => 'text',
				'value'       => $preorder_message ? $preorder_message : $default_preorder_message,
				'desc_tip'    => true,
				'description' => __( 'Message indicating date and time of the pre-order product availability', 'product-blocks' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => '_wopb_preorder_coming_soon' . $loop,
				'class'             => 'wopb_required',
				'label'             => __( 'Pre-Release Message', 'product-blocks' ),
				'type'              => 'text',
				'value'             => $coming_soon_message ? $coming_soon_message : $default_coming_soon_message,
				'desc_tip'          => true,
				'description'       => __( 'Enter a message if the pre-ordered product availability date and time are not given', 'product-blocks' ),
				'custom_attributes' => array(
					'required' => 'required',
				),
			)
		);

		if ( $is_active ) {
			woocommerce_wp_checkbox(
				array(
					'id'          => '_wopb_preorder_auto_convert' . $loop,
					'label'       => __( 'Auto Convert', 'product-blocks' ),
					'type'        => 'checkbox',
					'value'       => $product->get_meta( '_wopb_preorder_auto_convert' ),
					'description' => __( 'Enable conversion to default product after the pre-Order date and time is Over', 'product-blocks' ),
				)
			);
		} else {
			echo '<p class="form-field">';
			echo '<label>' . esc_html__( 'Auto Convert', 'product-blocks' ) . '</label>';
			echo '<span class="description" style="display:block;">';
			echo esc_html__( 'This feature is available in the Pro version.', 'product-blocks' );
			echo ' <a href="' . esc_url( wopb_function()->get_premium_link( 'https://www.wpxpo.com/wowstore', 'preorder' ) ) . '" target="_blank">';
			echo esc_html__( 'Upgrade to Pro', 'product-blocks' );
			echo '</a>';
			echo '</span>';
			echo '</p>';
		}

		if ( $is_active ) {
			woocommerce_wp_checkbox(
				array(
					'id'          => '_wopb_preorder_price_manage' . $loop,
					'class'       => '_wopb_preorder_price_manage',
					'label'       => __( 'Manage Discount', 'product-blocks' ),
					'type'        => 'checkbox',
					'value'       => $product->get_meta( '_wopb_preorder_price_manage' ),
					'description' => __( 'Allow discounted prices for pre-order items' ),
				)
			);
		} else {
			echo '<p class="form-field">';
			echo '<label>' . esc_html__( 'Manage Discount', 'product-blocks' ) . '</label>';
			echo '<span class="description" style="display:block;">';
			echo esc_html__( 'This feature is available in the Pro version.', 'product-blocks' );
			echo ' <a href="' . esc_url( wopb_function()->get_premium_link( 'https://www.wpxpo.com/wowstore', 'preorder' ) ) . '" target="_blank">';
			echo esc_html__( 'Upgrade to Pro', 'product-blocks' );
			echo '</a>';
			echo '</span>';
			echo '</p>';
		}

		if ( $is_active ) {
			woocommerce_wp_select(
				array(
					'id'          => '_wopb_preorder_price_type' . $loop,
					'class'       => 'wopb_preorder_manage_price_depend_required w-50',
					'label'       => __( 'Discount Type', 'product-blocks' ),
					'options'     => array(
						''           => __( 'Select Type', 'product-blocks' ),
						'fixed'      => __( 'Fixed', 'product-blocks' ),
						'percentage' => __( 'Percentage', 'product-blocks' ),
					),
					'value'       => $product->get_meta( '_wopb_preorder_price_type' ),
					'desc_tip'    => true,
					'description' => __( 'Set the discount pricing type', 'product-blocks' ),
				)
			);
		}

		if ( $is_active ) {
			woocommerce_wp_text_input(
				array(
					'id'          => '_wopb_preorder_price' . $loop,
					'class'       => 'wopb_preorder_manage_price_depend_required',
					'label'       => __( 'Discounted Price/Percentage', 'product-blocks' ),
					'type'        => 'number',
					'value'       => $product->get_meta( '_wopb_preorder_price' ),
					'desc_tip'    => true,
					'description' => __( 'Set the discounted price for the pre-order products', 'product-blocks' ),
				)
			);
		}

		$html .= ob_get_clean();

		return $html;
	}

	/**
	 * Show Pre-Order Variation Fields
	 *
	 * @return NULL
	 * @since v.1.0.4
	 */
	public function wopb_pre_order_custom_field_variation( $loop, $variation_data, $variation ) {
		if ( wopb_function()->get_setting( 'is_lc_active' ) ) {
			$html      = '<div class="wopb-woocommerce-variable-preorder-field-group">';
				$html .= $this->generate_field( $variation->ID, $loop );
			$html     .= '</div>';
			echo $html;
		}
	}


	/**
	 * Show Pre-Order Variation Save
	 *
	 * @return NULL
	 * @since v.1.0.4
	 */
	public function wopb_pre_order_save_variation_data( $variation_id, $a ) {
		$product = wc_get_product( $variation_id );

		if ( isset( $_POST['_wopb_preorder_variable'][ $a ] ) ) {
			$product->update_meta_data( '_wopb_preorder_variable', 'yes' );
			$product->update_meta_data( '_wopb_max_preorder', isset( $_POST['_wopb_max_preorder'][ $a ] ) ? sanitize_text_field( $_POST['_wopb_max_preorder'][ $a ] ) : '' );
			$product->update_meta_data( '_wopb_preorder_date', isset( $_POST['_wopb_preorder_date'][ $a ] ) ? sanitize_text_field( $_POST['_wopb_preorder_date'][ $a ] ) : '' );
			$product->update_meta_data( '_wopb_preorder_message', isset( $_POST['_wopb_preorder_message'][ $a ] ) ? sanitize_text_field( $_POST['_wopb_preorder_message'][ $a ] ) : '' );
			$product->update_meta_data( '_wopb_preorder_coming_soon', isset( $_POST['_wopb_preorder_coming_soon'][ $a ] ) ? sanitize_text_field( $_POST['_wopb_preorder_coming_soon'][ $a ] ) : '' );

			$product->update_meta_data( '_wopb_preorder_auto_convert', isset( $_POST['_wopb_preorder_auto_convert'][ $a ] ) ? sanitize_text_field( $_POST['_wopb_preorder_auto_convert'][ $a ] ) : '' );
			$product->update_meta_data( '_wopb_preorder_price_manage', isset( $_POST['_wopb_preorder_price_manage'][ $a ] ) ? sanitize_text_field( $_POST['_wopb_preorder_price_manage'][ $a ] ) : '' );

			$product->update_meta_data( '_wopb_preorder_price_type', isset( $_POST['_wopb_preorder_price_type'][ $a ] ) ? sanitize_text_field( $_POST['_wopb_preorder_price_type'][ $a ] ) : '' );
			$product->update_meta_data( '_wopb_preorder_price', isset( $_POST['_wopb_preorder_price'][ $a ] ) ? sanitize_text_field( $_POST['_wopb_preorder_price'][ $a ] ) : '' );
			if ( $_POST['_wopb_preorder_price_manage'][ $a ] && isset( $_POST['_wopb_preorder_price_type'][ $a ] ) ) {
				if ( $_POST['_wopb_preorder_price_type'][ $a ] == 'fixed' && $_POST['_wopb_preorder_price'][ $a ] ) {
					$product->set_sale_price( sanitize_text_field( $_POST['_wopb_preorder_price'][ $a ] ) );
				} elseif ( $_POST['_wopb_preorder_price_type'][ $a ] == 'percentage' && $_POST['_wopb_preorder_price'][ $a ] ) {
					$product->set_sale_price( $product->get_regular_price() - ( ( $product->get_regular_price() * sanitize_text_field( $_POST['_wopb_preorder_price'][ $a ] ) ) / 100 ) );
				}
			}
		} else {
			$product->update_meta_data( '_wopb_preorder_variable', '' );
		}
		$product->save();
	}


	/**
	 * Show Pre-Order Single Content in Single Product
	 *
	 * @return STRING
	 * @since v.1.0.4
	 */
	public function pre_order_content( $price, $product ) {
		if ( ! is_product() ) {
			return $price;
		}
		if ( ! $product ) {
			return $price;
		}
		if ( ! $this->is_simple_preorder( $product ) ) {
			return $price;
		}

		$html = '';
		if ( wopb_function()->is_preorder_closed( $product ) && ! $this->is_auto_convert_available( $product ) && $price ) {
			$html = '<h3 class="wopb-preorder-closed">' . __( 'Pre-Order Closed', 'product-blocks' ) . '</h3>';
		} else {
			// pre order message
			$preorder_available_date = $product->get_meta( '_wopb_preorder_date' );
			$html                    = $this->preorder_message( $preorder_available_date, $product, $html );

			// remaining qty
			$html = $this->remaining_item( $product, $html );

			if ( $product->get_meta( '_wopb_preorder_date' ) ) {
				// countdown for available date and time
				$html = $this->countdown( $preorder_available_date, $html );
			}
		}
		return "<span class='wopb-single-product-preorder'>{$price}{$html}</span>";

		return $price;
	}


	/**
	 * Show Pre-Order Vaiation Content in Single Product
	 *
	 * @return STRING
	 * @since v.1.0.4
	 */
	public function wopb_show_pre_order_variation_content_in_single_product( $data, $product, $variation ) {
		if ( $product->is_type( 'variable' ) ) {
			$variation_id = $variation->get_id();
			$html         = '';

			if ( $this->is_variable_preorder( $variation ) && ! wopb_function()->is_preorder_closed( $variation ) ) {
				// pre order message
				$preorder_available_date = $variation->get_meta( '_wopb_preorder_date' );
				$html                    = $this->preorder_message( $preorder_available_date, $variation, $html );

				// remaining qty
				$html = $this->remaining_item( $product, $html, $variation );

				if ( $variation->get_meta( '_wopb_preorder_date' ) ) {
					// countdown for available date and time
					$html = $this->countdown( $preorder_available_date, $html );
				}

				$html                          .= '<input type="hidden" class="wopb-single-variation-pre-order-text" value="' . wopb_function()->get_setting( 'preorder_add_to_cart_button_text' ) . '">';
				$data['variation_description'] .= $html . '<br>';
			} elseif ( $this->is_variable_preorder( $variation ) && wopb_function()->is_preorder_closed( $variation ) && ! $this->is_auto_convert_available( $variation ) ) {
				$html                           = '<h3 class="wopb-preorder-closed">' . __( 'Pre-Order Closed', 'product-blocks' ) . '</h3>';
				$data['variation_description'] .= $html . '<br>';
			}

			return $data;
		}
	}


	/**
	 * Show Pre-Order Message
	 *
	 * @return STRING
	 * @since v.1.0.4
	 */
	private function preorder_message( $preorder_available_date, $product, $html ) {
		$html                                   = '';
		$preorder_message                       = $product->get_meta( '_wopb_preorder_message' ) . ': ';
		$preorder_available_date_time_formatted = date( 'd M Y', strtotime( $preorder_available_date ) ) . ' at ' . date( 'h:i a', strtotime( $preorder_available_date ) );

		if ( $preorder_message && $preorder_available_date ) {
			$html     .= '<span class="wopb-singlepage-preorder-message">';
				$html .= '<span class="wopb-preorder-message">' . $preorder_message . '</span>';
				$html .= '<span class="wopb-preorder-duration">' . $preorder_available_date_time_formatted . '</span>';
			$html     .= '</span>';
		} else {
			$html     .= '<span class="wopb-singlepage-no-date">';
				$html .= '<span class="wopb-no-date-message">' . $product->get_meta( '_wopb_preorder_coming_soon' ) . '</span>';
			$html     .= '</span>';
		}
		return $html;
	}


	/**
	 * Show pre order remining item in single product  details
	 *
	 * @return STRING
	 * @since v.1.0.4
	 */
	private function remaining_item( $product, $html, $variation = null ) {
		$remaining_items = $this->remaining_item_count( $product, $variation );
		if ( $remaining_items ) {
			$html     .= '<span class="wopb-singlepage-preorder-remaining-item">';
				$html .= '<span class="wopb-preorder-remaining-label">' . __( 'Remaining Item only: ', 'product-blocks' ) . '</span>';
				$html .= '<span class="wopb-preorder-remaining-count">' . $remaining_items . '</span>';
			$html     .= '</span>';
		}
		return $html;
	}


	/**
	 * Show Pre-Order Remaining Item Count
	 *
	 * @return STRING
	 * @since v.1.0.4
	 */
	public function remaining_item_count( $product, $variation = null ) {
		if ( $variation ) {
			$allow_max_preorder = $variation->get_meta( '_wopb_max_preorder' );
		} else {
			$allow_max_preorder = $product->get_meta( '_wopb_max_preorder' );
		}

		$booked_order_count = $this->get_total_product_order_by_meta( $product->get_Id(), $variation );
		$remaining_items    = intval( $allow_max_preorder ) - intval( $booked_order_count );
		return $remaining_items;
	}


	/**
	 * Show Pre-Order Countdown in Single Product Details
	 *
	 * @return STRING
	 * @since v.1.0.4
	 */
	private function countdown( $preorder_available_date, string $html ) {
		if ( wopb_function()->get_setting( 'preorder_counter_disable' ) == 'yes' ) {
			return $html;
		}
		$preorder_available_duration = date( 'Y-m-d H:i:s', strtotime( $preorder_available_date ) );
		$current_date                = new \DateTime( date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) );
		$duration                    = $current_date->diff( new \DateTime( $preorder_available_duration ) );

		$html             .= '<span class="wopb-singlepage-preorder-countdown" data-pre-order-date="' . $preorder_available_date . '">';
			$html         .= '<span class="wopb-countdown-block">';
				$html     .= '<span class="wopb-countdown-block-item">';
					$html .= '<span class="wopb-preorder-countdown-number wopb-count-days"> ' . $duration->format( '%D' ) . '</span>';
					$html .= '<span class="wopb-preorder-countdown-text">' . __( 'Days', 'product-blocks' ) . '</span> ';
				$html     .= '</span>';
				$html     .= '<span class="wopb-preorder-countdown-separator">:</span>';
			$html         .= '</span>';
			$html         .= '<span class="wopb-countdown-block">';
				$html     .= '<span class="wopb-countdown-block-item">';
					$html .= '<span class="wopb-preorder-countdown-number wopb-count-hours"> ' . $duration->format( '%H' ) . '</span>';
					$html .= '<span class="wopb-preorder-countdown-text">' . __( 'Hours', 'product-blocks' ) . '</span>';
				$html     .= '</span>';
				$html     .= '<span class="wopb-preorder-countdown-separator">:</span>';
			$html         .= '</span>';
			$html         .= '<span class="wopb-countdown-block">';
				$html     .= '<span class="wopb-countdown-block-item">';
					$html .= '<span class="wopb-preorder-countdown-number wopb-count-minutes"> ' . $duration->format( '%I' ) . '</span>';
					$html .= '<span class="wopb-preorder-countdown-text">' . __( 'Minutes', 'product-blocks' ) . '</span>';
				$html     .= '</span>';
				$html     .= '<span class="wopb-preorder-countdown-separator">:</span>';
			$html         .= '</span>';
			$html         .= '<span class="wopb-countdown-block">';
				$html     .= '<span class="wopb-countdown-block-item">';
					$html .= '<span class="wopb-preorder-countdown-number wopb-count-seconds"> ' . $duration->format( '%S' ) . '</span>';
					$html .= '<span class="wopb-preorder-countdown-text">' . __( 'Seconds', 'product-blocks' ) . '</span>';
				$html     .= '</span>';
			$html         .= '</span>';
		$html             .= '</span>';

		return $html;
	}


	/**
	 * Show Pre-order Label in Cart
	 *
	 * @return STRING
	 * @since v.1.0.4
	 */
	public function wopb_woocommerce_cart_item_name( $name, $cart_item, $cart_item_key ) {
		$product = $cart_item['data'];
		if ( $cart_item['variation_id'] ) {
			$product = wc_get_product( $cart_item['variation_id'] );
		}
		if ( ( $this->is_variable_preorder( $product ) || $this->is_simple_preorder( $product ) ) && ! wopb_function()->is_preorder_closed( $product ) ) {
			$preorder_available_date                = $product->get_meta( '_wopb_preorder_date' );
			$preorder_message                       = $product->get_meta( '_wopb_preorder_message' ) . ': ';
			$preorder_available_date_time_formatted = date( 'd M Y h:i a', strtotime( $preorder_available_date ) );

			$pre_order_content = '<span class="wopb-cart-preorder-badge">' . wopb_function()->get_setting( 'preorder_button_text' ) . '</span>';
			if ( $preorder_available_date ) {
				$pre_order_content .= '<div class="wopb-cart-preorder-message">';
				$pre_order_content .= '<span class="wopb-preorder-message">' . $preorder_message . '</span>';
				$pre_order_content .= '<span class="wopb-preorder-duration">' . $preorder_available_date_time_formatted . '</span>';
				$pre_order_content .= '</div>';
			}
			return $name . $pre_order_content;
		}
		return $name;
	}


	/**
	 * Add Meta When Checkout Order for Pre-Order
	 *
	 * @return STRING
	 * @since v.1.0.4
	 */
	public function wopb_add_pre_order_meta_to_item( WC_Order_Item_Product $item, $cart_item_key, $values, $order ) {
		$cart_item = WC()->cart->get_cart()[ $cart_item_key ];
		$product   = wc_get_product( $item['product_id'] );
		if ( $cart_item['variation_id'] ) {
			$product = wc_get_product( $cart_item['variation_id'] );
		}
		if ( ( $this->is_variable_preorder( $product ) || $this->is_simple_preorder( $product ) ) && ! wopb_function()->is_preorder_closed( $product ) ) {
			$item->update_meta_data( 'wopb_pre_order_item', 'yes' );
		}
	}


	/**
	 * Cart Page Pre Order Label Add
	 *
	 * @return HTML
	 * @since v.1.0.4
	 */
	public function woocommerce_re_format_order_item_meta( $formatted_meta, $item ) {
		foreach ( $formatted_meta as $key => $meta ) {
			if ( $meta->key == 'wopb_pre_order_item' ) {
				$meta->display_key = '<span class="wopb-cart-preorder-badge">' . wopb_function()->get_setting( 'preorder_button_text' ) . '</span>';
			}
		}
		return $formatted_meta;
	}


	/**
	 * Inserting "Order Type" Column Before Last Elements
	 *
	 * @return ARRAY
	 * @since v.1.0.4
	 */
	public function add_column_in_order_listing_page( $columns ) {
		$reordered_columns = array();
		foreach ( $columns as $key => $column ) {
			$reordered_columns[ $key ] = $column;
			if ( $key == 'order_status' ) {
				$reordered_columns['wopb_order_page_order_type'] = __( 'Order Type', 'product-blocks' );
			}
		}
		return $reordered_columns;
	}


	/**
	 * Show Pre-Order Label to Order Table Column
	 *
	 * @return STRING
	 * @since v.1.0.4
	 */
	public function set_order_type_column_value( $column ) {
		global $the_order;
		if ( $column == 'wopb_order_page_order_type' ) {
			$has_item = false;
			$items    = $the_order->get_items();
			foreach ( $the_order->get_items() as $item ) {
				if ( $item->get_meta( 'wopb_pre_order_item' ) == 'yes' ) {
					$has_item = true;
				}
			}
			if ( $has_item ) {
				echo '<span class="wopb-cart-preorder-badge">' . esc_html( wopb_function()->get_setting( 'preorder_button_text' ) ) . '</span>';
			}
		}
	}


	/**
	 * Get Total Order Number
	 *
	 * @return STRING
	 * @since v.1.0.4
	 */
	public function get_total_product_order_by_meta( $product_id, $variation = null ) {
		global $wpdb;
		$variation_statement = $variation ? ' AND order_product.variation_id = ' . intval( $variation->get_Id() ) : '';
		$result              = $wpdb->get_results(
			"
            SELECT sum(order_product.product_qty) as total_order
            FROM {$wpdb->prefix}wc_order_product_lookup as order_product
            INNER JOIN {$wpdb->prefix}wc_order_stats AS order_stat
                ON order_product.order_id = order_stat.order_id
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta as order_item_meta
                ON order_product.order_item_id = order_item_meta.order_item_id AND order_item_meta.meta_key = 'wopb_pre_order_item'
            WHERE order_product.product_id = {intval($product_id)} {$variation_statement}
                AND order_stat.status NOT IN ('wc-cancelled', 'wc-refunded')
        "
		);
		return $result[0]->total_order;
	}


	/**
	 * Check the Product Purchasable or Not
	 *
	 * @return STRING
	 * @since v.1.0.4
	 */
	public function is_purchasable_products( $status, $product ) {
		if ( $this->is_simple_preorder( $product ) && wopb_function()->is_preorder_closed( $product ) && ! $this->is_auto_convert_available( $product ) ) {
			return false;
		} elseif ( $this->is_simple_preorder( $product ) && wopb_function()->is_preorder_closed( $product ) && $this->is_auto_convert_available( $product ) ) {
			$product->update_meta_data( '_wopb_preorder_simple', '' );
			$product->save();
		}
		return true;
	}

	public function is_simple_preorder( $product ) {
		if ( $product && $product->get_meta( '_wopb_preorder_simple' ) == 'yes' ) {
			return true;
		} else {
			return false;
		}
	}

	public function is_variable_preorder( $product ) {
		if ( $product && $product->get_meta( '_wopb_preorder_variable' ) == 'yes' ) {
			return true;
		} else {
			return false;
		}
	}

	public function is_auto_convert_available( $product ) {
		if ( $product->get_meta( '_wopb_preorder_auto_convert' ) == 'yes' && $product->get_meta( '_wopb_preorder_date' ) ) {
			return true;
		} else {
			return false;
		}
	}

	public function is_validate_order_item( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		$cart_item = WC()->cart->get_cart()[ $cart_item_key ];
		$product   = wc_get_product( $product_id );
		if ( $variation_id ) {
			$product = wc_get_product( $variation_id );
		}

		if ( ( $product->get_meta( '_wopb_max_preorder' ) && $cart_item['quantity'] > $this->remaining_item_count( $product ) ) && ( $this->is_simple_preorder( $product ) || $this->is_variable_preorder( $product ) && ! wopb_function()->is_preorder_closed( $product ) ) ) {
			throw new \Exception( esc_html__( 'Quantity Exceeded for Pre-Order Item.', 'product-blocks' ) );
		}
	}

	/**
	 * Dynamic CSS
	 *
	 * @param $key
	 * @return void
	 * @since v.4.0.0
	 */
	public function generate_css( $key ) {
		if ( $key == 'wopb_preorder' && method_exists( wopb_function(), 'convert_css' ) ) {
			$settings    = wopb_function()->get_setting();
			$badge_style = array_merge( $settings['preorder_badge_typo'], $settings['preorder_badge_bg'] );

			$css      = '.wopb-preorder-message, .wopb-single-product-preorder .wopb-no-date-message{';
				$css .= wopb_function()->convert_css( 'general', $settings['preorder_available_typo'] );
			$css     .= '}';
			$css     .= '.wopb-preorder-message:hover, .wopb-single-product-preorder .wopb-no-date-message:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['preorder_available_typo'] );
			$css     .= '}';

			$css     .= '.wopb-preorder-duration{';
				$css .= wopb_function()->convert_css( 'general', $settings['preorder_duration_typo'] );
			$css     .= '}';
			$css     .= '.wopb-preorder-duration:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['preorder_duration_typo'] );
			$css     .= '}';

			$css     .= '.wopb-preorder-remaining-label{';
				$css .= wopb_function()->convert_css( 'general', $settings['preorder_remain_typo'] );
			$css     .= '}';
			$css     .= '.wopb-preorder-remaining-label:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['preorder_remain_typo'] );
			$css     .= '}';

			$css     .= '.wopb-preorder-remaining-count{';
				$css .= wopb_function()->convert_css( 'general', $settings['preorder_count_typo'] );
			$css     .= '}';
			$css     .= '.wopb-preorder-remaining-count:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['preorder_count_typo'] );
			$css     .= '}';

			$css     .= '.wopb-singlepage-preorder-countdown{';
				$css .= isset( $settings['preorder_timer_bg']['bg'] ) ? ( 'background-color: ' . $settings['preorder_timer_bg']['bg'] . ';' ) : '';
			$css     .= '}';
			$css     .= '.wopb-singlepage-preorder-countdown:hover{';
				$css .= isset( $settings['preorder_timer_bg']['hover_bg'] ) ? ( 'background-color: ' . $settings['preorder_timer_bg']['hover_bg'] . ';' ) : '';
			$css     .= '}';

			$css     .= '.wopb-preorder-countdown-number{';
				$css .= wopb_function()->convert_css( 'general', $settings['preorder_time_typo'] );
			$css     .= '}';
			$css     .= '.wopb-preorder-countdown-number:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['preorder_time_typo'] );
			$css     .= '}';

			$css     .= '.wopb-preorder-countdown-text{';
				$css .= wopb_function()->convert_css( 'general', $settings['preorder_time_label_typo'] );
			$css     .= '}';
			$css     .= '.wopb-preorder-countdown-text:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['preorder_time_label_typo'] );
			$css     .= '}';

			$css     .= '.wopb-preorder-countdown-separator{';
				$css .= wopb_function()->convert_css( 'general', $settings['preorder_time_separator_typo'] );
			$css     .= '}';
			$css     .= '.wopb-preorder-countdown-separator:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['preorder_time_separator_typo'] );
			$css     .= '}';

			$css     .= '.wopb-cart-preorder-badge{';
				$css .= wopb_function()->convert_css( 'general', $badge_style );
				$css .= wopb_function()->convert_css( 'border', $settings['preorder_badge_border'] );
				$css .= wopb_function()->convert_css( 'radius', $settings['preorder_badge_radius'] );
				$css .= 'padding: ' . wopb_function()->convert_css( 'dimension', $settings['preorder_badge_padding'] ) . ';';
			$css     .= '}';
			$css     .= '.wopb-cart-preorder-badge:hover{';
				$css .= wopb_function()->convert_css( 'hover', $badge_style );
			$css     .= '}';

			wopb_function()->update_css( $key, 'add', $css );
		}
	}
}
