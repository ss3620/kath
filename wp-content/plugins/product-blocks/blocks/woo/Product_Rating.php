<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Product_Rating {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'blockId'       => '',
			'currentPostId' => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/product-rating',
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
		$block_name    = 'product-rating';
		$wraper_before = $wraper_after = $content = '';
		$attr          = wp_parse_args( $attr, $this->get_attributes() );

		if ( ! empty( $product ) ) {
			$rating_count          = $product->get_rating_count();
			$review_count          = $product->get_review_count();
			$average               = $product->get_average_rating();
				$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';

				$wraper_before           .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . '">';
					$content             .= '<div class="wopb-product-wrapper">';
						$content         .= '<div class="woocommerce-product-rating">';
							$content     .= '<div class="star-rating">';
								$content .= wc_get_star_rating_html( $average, $rating_count );
							$content     .= '</div>';
							$content     .= '<a href="#reviews" class="woocommerce-review-link" rel="nofollow">';
								$content .= '(<span class="count">' . $review_count . '</span> ' . esc_html__( 'customer review', 'product-blocks' ) . ')';
							$content     .= '</a>';
						$content         .= '</div>';
						ob_start();
						$content .= ob_get_clean();
					$content     .= '</div>';
				$wraper_after    .= '</div>';
			// }
		}

		return $wraper_before . $content . $wraper_after;
	}
}
