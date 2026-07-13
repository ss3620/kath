<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Product_Stock {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {
		register_block_type(
			'product-blocks/product-stock',
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
		$block_name    = 'product-stock';
		$wraper_before = $wraper_after = $content = '';

		$allowed_html_tags = wopb_function()->allowed_html_tags();

		if ( ! empty( $product ) ) {
			$availability = $product->get_availability();
			$stock_status = $product->get_stock_status();

			// Use custom labels if available, fallback to default availability text
			$labels = array(
				'instock'     => isset( $attr['stockInLabel'] ) && $attr['stockInLabel'] ? wp_kses( $attr['stockInLabel'], $allowed_html_tags ) : $availability['availability'],
				'outofstock'  => isset( $attr['stockOutLabel'] ) && $attr['stockOutLabel'] ? wp_kses( $attr['stockOutLabel'], $allowed_html_tags ) : $availability['availability'],
				'onbackorder' => isset( $attr['stockBackLabel'] ) && $attr['stockBackLabel'] ? wp_kses( $attr['stockBackLabel'], $allowed_html_tags ) : $availability['availability'],
			);

			if ( isset( $labels[ $stock_status ] ) && $labels[ $stock_status ] ) {
				$availability['availability'] = $labels[ $stock_status ];
			} else {
				return;
			}

			$stock_html = '';
			if ( ! empty( $availability['availability'] ) ) {
				$stock_html = '<p class="stock ' . esc_attr( $availability['class'] ) . '">' . wp_kses_post( $availability['availability'] ) . '</p>';
			}

			if ( has_filter( 'woocommerce_stock_html' ) ) {
				wc_deprecated_function( 'The woocommerce_stock_html filter', '', 'woocommerce_get_stock_html' );
				$stock_html = apply_filters( 'woocommerce_stock_html', $stock_html, $availability['availability'], $product );
			}
			$stock_html = apply_filters( 'woocommerce_get_stock_html', $stock_html, $product );

			// if ( $product->get_manage_stock() ) {
				$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';

				$wraper_before     .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . '">';
					$wraper_before .= '<div class="wopb-product-wrapper">';

			// $content .= wc_get_stock_html( $product );
			if ( ! empty( $stock_html ) ) {
				$content .= $stock_html;
			}

					$wraper_after .= '</div>';
				$wraper_after     .= '</div>';
			// }
		}

		return $wraper_before . $content . $wraper_after;
	}
}
