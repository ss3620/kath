<?php
/**
 * WooHooks Action.
 *
 * @package WOPB\Notice
 * @since v.4.0.0
 */
namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * WooHooks class.
 */
class WooHooks {

	public function __construct() {
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'add_to_cart_filter' ), 100, 3 );
		add_action( 'woocommerce_before_shop_loop_item_title', array( $this, 'before_shop_loop_item' ), 100 );
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'before_add_to_cart_button' ), 100 );
		add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'after_add_to_cart_button' ), 100 );
		add_filter( 'wopb_after_loop_item', array( $this, 'after_loop_item' ), 100 );
	}

	/**
	 * Add To Cart Button Filter for Loop Product
	 *
	 * @since v.3.1.5
	 * @param $add_to_cart_html
	 * @param $product
	 * @param $products
	 *
	 * @return html
	 */
	// $args = array()
	public function add_to_cart_filter( $add_to_cart_html, $product, $args = array() ) {
		$before        = $after = '';
		$top_filter    = apply_filters( 'wopb_top_add_to_cart_loop', $content = '', $product, $args );
		$before_filter = apply_filters( 'wopb_before_add_to_cart_loop', $content = '', $product, $args );
		$after_filter  = apply_filters( 'wopb_after_add_to_cart_loop', $content = '', $product, $args );
		$bottom_filter = apply_filters( 'wopb_bottom_add_to_cart_loop', $content = '', $product, $args );

		if ( $top_filter ) {
			$before .= '<div class="wopb-cart-top">';
			$before .= wopb_function()->core_esc_wp( $top_filter );
			$before .= '</div>';
		}
		if ( $before_filter ) {
			$before .= '<span class="wopb-cart-before">';
			$before .= wopb_function()->core_esc_wp( $before_filter );
			$before .= '</span>';
		}
		if ( $after_filter ) {
			$after .= '<span class="wopb-cart-after">';
			$after .= wopb_function()->core_esc_wp( $after_filter );
			$after .= '</span>';
		}
		if ( $bottom_filter ) {
			$after .= '<div class="wopb-cart-bottom">';
			$after .= wopb_function()->core_esc_wp( $bottom_filter );
			$after .= '</div>';
		}

		return $before . $add_to_cart_html . $after;
	}

	/**
	 * Various Addons Before Shop Loop Title
	 *
	 * @since v.3.1.5
	 * @return null
	 */
	public function before_shop_loop_item() {
		$args         = array();
		$before_title = apply_filters( 'wopb_before_shop_loop_title', $content = '', $args );
		if ( $before_title ) {
			$content      = '<div class="wopb-loop-image-top">';
			$content     .= wopb_function()->core_esc_wp( $before_title );
			$content     .= '</div>';
			$content_safe = wopb_function()->wp_kses_safe( $content );
            echo $content_safe; // phpcs:ignore
		}
	}

	/**
	 * Button In Single Product Page Before Cart Button
	 *
	 * @since v.3.1.5
	 * @return null
	 */
	public function before_add_to_cart_button() {
		$content       = '';
		$top_filter    = apply_filters( 'wopb_top_add_to_cart', $content = '' );
		$before_filter = apply_filters( 'wopb_before_add_to_cart', $content = '' );
		if ( $top_filter ) {
			$content .= '<div class="wopb-cart-top">';
			$content .= wopb_function()->core_esc_wp( $top_filter );
			$content .= '</div>';
		}
		if ( $before_filter ) {
			$content .= '<span class="wopb-cart-before">';
			$content .= wopb_function()->core_esc_wp( $before_filter );
			$content .= '</span>';
		}
		$content_safe = wopb_function()->wp_kses_safe( $content );
        echo $content_safe; // phpcs:ignore
	}

	/**
	 * Button In Single Product Page After Cart Button
	 *
	 * @since v.3.1.5
	 * @return null
	 */
	public function after_add_to_cart_button() {
		$after_filter  = apply_filters( 'wopb_after_add_to_cart', $content = '' );
		$bottom_filter = apply_filters( 'wopb_bottom_add_to_cart', $content = '' );
		$content       = '';
		if ( $after_filter ) {
			$content .= '<span class="wopb-cart-after">';
			$content .= wopb_function()->core_esc_wp( $after_filter );
			$content .= '</span>';
		}
		if ( $bottom_filter ) {
			$content .= '<div class="wopb-cart-bottom">';
			$content .= wopb_function()->core_esc_wp( $bottom_filter );
			$content .= '</div>';
		}

		$content_safe = wopb_function()->wp_kses_safe( $content );
        echo $content_safe; // phpcs:ignore
	}

	/**
	 * Filter Applied After Loop Item
	 *
	 * @param $content
	 * @return string
	 * @since v.4.0.6
	 */
	public function after_loop_item( $content ) {
		$germanized_wc = array( // Germanized woocommerce function for after loop item
			'unit_price'             => 'woocommerce_gzd_template_loop_price_unit',
			'tax'                    => 'woocommerce_gzd_template_loop_tax_info',
			'shipping_costs'         => 'woocommerce_gzd_template_loop_shipping_costs_info',
			'delivery_time'          => 'woocommerce_gzd_template_loop_delivery_time_info',
			'units'                  => 'woocommerce_gzd_template_loop_product_units',
			'deposit'                => 'woocommerce_gzd_template_loop_deposit',
			'deposit_packaging_type' => 'woocommerce_gzd_template_loop_deposit_packaging_type',
			'nutri_score'            => 'woocommerce_gzd_template_loop_nutri_score',
		);
		ob_start();
		foreach ( $germanized_wc as $func ) {
			if ( function_exists( $func ) ) {
				$func();
			}
		}
		$content .= ob_get_clean();

		return $content; //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
