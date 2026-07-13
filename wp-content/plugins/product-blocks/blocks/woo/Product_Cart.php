<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Product_Cart {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'template_redirect', array( $this, 'buy_now_submit' ) );
	}

	public function get_attributes() {
		return array(
			'showQuantity'        => true,
			'quantityBtnPosition' => 'right',
			'showQuantityBtn'     => true,
			'currentPostId'       => '',
			'showBuyNow'          => false,
			'removeProduct'       => true,
			'btnBuyText'          => 'Buy Now',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/product-cart',
			array(
				'editor_script'   => 'wopb-blocks-builder-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr ) {
		global $product;
		$product       = wc_get_product();
		$block_name    = 'product-cart';
		$wraper_before = $wraper_after = $content = '';
		$attr          = wp_parse_args( $attr, $this->get_attributes() );

		if ( ! empty( $product ) ) {
			global $productx_cart;
			$cart_class = '';

			if ( ! empty( $attr['showBuyNow'] ) ) {
				add_filter(
					'wopb_bottom_add_to_cart',
					function ( $content ) use ( $attr, $product ) {

						if ( ! empty( $attr['removeProduct'] ) ) {
							$content .= '<input type="hidden" name="wopb-remove-product" value="true">';
						}

						$content .= '<button ';
						$content .= 'type="submit" ';
						$content .= 'name="wopb-buy-now" ';
						$content .= 'class="wopb-buy-button ' . ( $product->is_type( 'variable' ) ? 'wc-variation-selection-needed button disabled' : '' ) . '" ';
						$content .= 'value="' . $product->get_id() . '" ';
						$content .= 'data-product_id="' . $product->get_id() . '" ';
						$content .= '>';
						$content .= ! empty( $attr['btnBuyText'] ) ? $attr['btnBuyText'] : esc_html__( 'Buy Now', 'product-blocks' );
						$content .= '</button>';
						return $content;
					}
				);
			}

			if ( wopb_function()->isPro() ) {
				$methods = get_class_methods( wopb_pro_function() );
				if ( in_array( 'is_simple_preorder', $methods ) ) {
					if ( wopb_function()->is_simple_preorder() && wopb_function()->get_setting( 'preorder_add_to_cart_button_text' ) ) {
						$productx_cart = wopb_function()->get_setting( 'preorder_add_to_cart_button_text' );
					}
				}
				if ( in_array( 'is_simple_backorder', $methods ) ) {
					if ( wopb_function()->is_simple_backorder() && wopb_function()->get_setting( 'backorder_add_to_cart_button_text' ) ) {
						$productx_cart = wopb_function()->get_setting( 'backorder_add_to_cart_button_text' );
					}
				}
			}

			$cart_text         = function () {
				global $productx_cart;
				return $productx_cart;
			};
			$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';

			$wraper_before .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . '">';

			if ( $productx_cart ) {
				add_filter( 'woocommerce_product_single_add_to_cart_text', $cart_text );
			}

			if (
				! $attr['showQuantity'] ||
				( $product->get_manage_stock() && $product->get_stock_quantity() < 2 )
			) {
				$cart_class .= ' wopb-hide-qty';
				add_filter( 'woocommerce_is_sold_individually', array( $this, 'remove_quantity_fields' ), 10, 2 );
			}

			ob_start();
            echo '<div class="wopb-product-wrapper wopb-builder-cart' . esc_attr($cart_class) . '" data-type="'. ( $attr['showQuantity'] && $attr['showQuantityBtn'] ? esc_attr($attr['quantityBtnPosition']) : '' ).'">'; // phpcs:ignores
			$this->remove_qty_element();
			woocommerce_template_single_add_to_cart();
			do_action( 'wopb_after_builder_add_cart_button' );
			echo '</div>';
			$form     = ob_get_clean();
			$content .= str_replace( 'single_add_to_cart_button', 'single_add_to_cart_button wopb-cart-button', $form );

			if ( $productx_cart ) {
				remove_filter( 'woocommerce_product_single_add_to_cart_text', $cart_text );
			}

			$wraper_after .= '</div>';
		}

		return $wraper_before . $content . $wraper_after;
	}

	public function remove_qty_element() {
		remove_action( 'woocommerce_before_quantity_input_field', 'botiga_woocommerce_before_quantity_input_field' );
		remove_action( 'woocommerce_before_quantity_input_field', 'baseket_minus_btn' );
		remove_action( 'woocommerce_before_quantity_input_field', 'plant_minus_btn' );

		remove_action( 'woocommerce_before_add_to_cart_quantity', 'big_store_display_quantity_minus' );

		remove_action( 'woocommerce_after_quantity_input_field', 'botiga_woocommerce_after_quantity_input_field' );
		remove_action( 'woocommerce_after_quantity_input_field', 'baseket_plus_btn' );
		remove_action( 'woocommerce_after_quantity_input_field', 'plant_plus_btn' );

		remove_action( 'woocommerce_after_add_to_cart_quantity', 'big_store_display_quantity_plus' );
	}

	public function remove_quantity_fields( $return, $product ) {
		return true;
	}

	/**
	 * Click Buy Now and Redirect to Checkout Page
	 *
	 * @return null
	 * @since v.4.1.4
	 */
	public function buy_now_submit() {
		$sanitized_request = wopb_function()->rest_sanitize_params( $_REQUEST );
		if ( ! empty( $sanitized_request['wopb-buy-now'] ) ) {
			$product_id        = $sanitized_request['wopb-buy-now'];
			$qty               = floatval( ! empty( $sanitized_request['quantity'] ) ? $sanitized_request['quantity'] : 1 );
			$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $qty );
			if ( $passed_validation ) {
				$redirect = false;
				if ( ! empty( $sanitized_request['wopb-remove-product'] ) ) {
					WC()->cart->empty_cart();
					if ( ! empty( $sanitized_request['variation_id'] ) ) {
						$variation = array();
						foreach ( $sanitized_request as $name => $value ) {
							if ( str_starts_with( $name, 'attribute_' ) ) {
								$variation[ $name ] = $value;
							}
						}
						WC()->cart->add_to_cart( $product_id, $qty, $sanitized_request['variation_id'], $variation );
					}
				}

				if ( ! isset( $sanitized_request['variation_id'] ) ) {
					WC()->cart->add_to_cart( $product_id, $qty );
					$redirect = true;
				} elseif ( ! empty( $sanitized_request['variation_id'] ) ) {
					$redirect = true;
				}
				if ( $redirect ) {
					wp_safe_redirect( wc_get_checkout_url() );
					exit;
				} else {
					return;
				}
			}
		}
	}
}
