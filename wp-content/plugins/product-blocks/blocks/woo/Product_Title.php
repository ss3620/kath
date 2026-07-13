<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Product_Title {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function register() {
		register_block_type(
			'product-blocks/product-title',
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
		$block_name    = 'product-title';
		$wraper_before = $wraper_after = $content = '';

		$product = wc_get_product();

		if ( ! empty( $product ) ) {
			$title_tag         = isset( $attr['titleTag'] ) ? $attr['titleTag'] : 'h1';
			$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';

			$wraper_before     .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . '">';
				$wraper_before .= '<div class="wopb-product-wrapper">';
					$content   .= '<' . $title_tag . ' class="product_title entry-title wopb-builder-product-title">' . wp_kses_post( $product->get_title() ) . '</' . $title_tag . '>';
					ob_start();
						do_action( 'wopb_after_single_product_title' );
					$content  .= ob_get_clean();
				$wraper_after .= '</div>';
			$wraper_after     .= '</div>';
		}

		return $wraper_before . $content . $wraper_after;
	}
}
