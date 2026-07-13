<?php
/**
 * Add to Cart Text Addons Core.
 *
 * @package WOPB\CartText
 * @since v.4.0.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * CartText class.
 */
class CartText {

	/**
	 * Setup class.
	 *
	 * @since v.4.0.0
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'shop_archive_text_callback' ), 10, 2 );
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'shop_archive_text_callback' ), 10, 2 );
	}

	/**
	 * Add to Cart Text for Shop & Archive Page
	 *
	 * @return string
	 * @since v.4.0.0
	 */
	public function shop_archive_text_callback( $add_to_cart_text, $product ) {
		if ( $product->get_stock_status() != 'outofstock' ) {
			$p_type = $product->get_type();
			if ( in_array( $p_type, array( 'simple', 'grouped', 'external', 'variable' ) ) ) {
				$temp = wopb_function()->get_setting( 'cart_text_' . ( is_product() ? 'single' : 'archive' ) . '_' . $p_type );
				if ( $temp ) {
					$add_to_cart_text = $temp;
				}
			}
		}
		return $add_to_cart_text;
	}
}
