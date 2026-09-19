<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Cart_Table {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'init', array( $this, 'woocommerce_clear_cart_url' ) );
	}

	public function get_attributes() {
		return array(
			'showCoupon'             => true,
			'showUpdate'             => true,
			'showEmpty'              => true,
			'showContinue'           => false,
			'showCrossSell'          => false,
			'productHead'            => 'Product',
			'priceHead'              => 'Price',
			'qtyHead'                => 'Quantity',
			'subTotalHead'           => 'Subtotal',
			'removeBtnPosition'      => 'left',
			'couponInputPlaceholder' => 'Enter Coupon Code.....',
			'couponBtnText'          => 'Apply Coupon',
			'continueShoppingText'   => ' Continue Shopping',
			'emptyText'              => 'Empty Cart',
			'updateText'             => 'Update Cart',
			'crossSellPosition'      => 'bottom',
			'currentPostId'          => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/cart-table',
			array(
				'editor_script'   => 'wopb-blocks-builder-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	function woocommerce_clear_cart_url() {
		global $woocommerce;
        if ( isset( $_GET['empty-cart'] ) ) { // phpcs:ignore
			$woocommerce->cart->empty_cart();
			header( 'Location: ' . wc_get_cart_url() );
			exit();
		}
	}

	public function content( $attr, $noAjax = false ) {
		if ( function_exists( 'WC' ) && ! is_admin() && isset( WC()->customer ) ) {
			do_action( 'woocommerce_check_cart_items' );
			$block_name    = 'cart-table';
			$wraper_before = $wraper_after = $content = '';
			$attr          = wp_parse_args( $attr, $this->get_attributes() );

			$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
			$attr['align']     = ! empty( $attr['align'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';

			$wraper_before     .= '<div id="' . ( isset( $attr['advanceId'] ) ? sanitize_html_class( $attr['advanceId'] ) : '' ) . '"' . ' class="wp-block-product-blocks-' . $block_name . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . $attr['align'] . '">';
				$wraper_before .= '<div class="wopb-product-wrapper wopb-cart-table-wrapper">';

					ob_start();
			if ( WC()->cart->is_empty() ) {
				wc_get_template( 'cart/cart-empty.php' );
			} else {
				require_once WOPB_PATH . 'blocks/woo/cart_table/Template.php';
			}
					$content .= ob_get_clean();

				$wraper_after .= '</div>';
			$wraper_after     .= '</div>';

			return $wraper_before . $content . $wraper_after;
		}
	}
}
