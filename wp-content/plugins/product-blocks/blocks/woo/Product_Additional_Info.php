<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Product_Additional_Info {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {
		register_block_type(
			'product-blocks/product-additional-info',
			array(
				'editor_script'   => 'wopb-blocks-builder-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
				'currentPostId'   => '',
			)
		);
	}

	public function content( $attr ) {
		global $product;
		$product       = wc_get_product();
		$block_name    = 'product-additional-info';
		$wraper_before = $wraper_after = $content = '';

		if ( ! empty( $product ) ) {
			if ( $product->has_attributes() || $product->has_dimensions() || $product->has_weight() ) {
				$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';

				$wraper_before .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . '">';
				$wraper_before .= '<div class="wopb-product-wrapper">';

				ob_start();
				woocommerce_product_additional_information_tab();
				$content .= ob_get_clean();

				$wraper_after .= '</div>';
				$wraper_after .= '</div>';
			}
		}

		return $wraper_before . $content . $wraper_after;
	}
}
