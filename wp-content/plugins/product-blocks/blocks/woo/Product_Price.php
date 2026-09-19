<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Product_Price {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'blockId'        => '',
			'salesLabel'     => false,
			'salesBadge'     => false,
			'salesTextLabel' => 'Price: ',
			'badgeLabel'     => 'OFF',
			'advanceId'      => '',
			'currentPostId'  => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/product-price',
			array(
				'editor_script'   => 'wopb-blocks-builder-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr ) {
		$product = wc_get_product();
		if ( $product && $product->get_price() ) {
			$block_name    = 'product-price';
			$wraper_before = $wraper_after = $content = '';
			$attr          = wp_parse_args( $attr, $this->get_attributes() );

			if ( ! empty( $product ) ) {
				$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';

				$wraper_before .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . '">';
				$wraper_before .= '<div class="wopb-product-wrapper">';

				if ( $attr['salesLabel'] ) {
					$content .= '<span class="wopb-builder-price-label"><bdi>' . esc_html( $attr['salesTextLabel'] ) . '</bdi></span>';
				}

				ob_start();
				woocommerce_template_single_price();
				$content .= ob_get_clean();

				if ( $product->get_sale_price() && $attr['salesBadge'] ) {
					$percentage = 100 - ( $product->get_sale_price() / $product->get_regular_price() * 100 );
					$content   .= '<div class="woocommerce-discount-badge">' . round( $percentage, 2 ) . '% ' . esc_html( $attr['badgeLabel'] ) . '</div>';
				}
				$content = apply_filters( 'wopb_after_single_product_price', $content, $product );

				$wraper_after .= '</div>';
				$wraper_after .= '</div>';
			}

			return $wraper_before . $content . $wraper_after;
		}
	}
}
