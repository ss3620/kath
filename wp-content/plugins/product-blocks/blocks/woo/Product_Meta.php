<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Product_Meta {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'metaSku'       => true,
			'metaCategory'  => true,
			'metaTag'       => true,
			'metaLabelShow' => true,
			'labelSku'      => 'sku : ',
			'labelCat'      => 'category :  ',
			'labelTag'      => 'tag : ',
			'currentPostId' => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/product-meta',
			array(
				'editor_script'   => 'wopb-blocks-builder-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function list_items( $terms, $type ) {
		$inc     = 1;
		$content = '';
		foreach ( $terms as $term_id ) {
			$term = get_term_by( 'id', $term_id, $type );
			if ( $inc > 1 ) {
				$content .= ', ';
			}
			$content .= '<a class="wopb-meta-list-sku meta-block__value" href="' . get_term_link( $term->slug, $type ) . '">' . esc_html( $term->name ) . '</a>';
			++$inc;
		}
		return $content;
	}

	public function content( $attr ) {
		global $product;
		$product       = wc_get_product();
		$block_name    = 'product-meta';
		$wraper_before = $wraper_after = $content = '';
		$attr          = wp_parse_args( $attr, $this->get_attributes() );

		if ( ! empty( $product ) ) {
			$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';

			$wraper_before .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . '">';
				$content   .= '<div class="wopb-product-wrapper">';
					ob_start();
						do_action( 'woocommerce_product_meta_start' );
					$content .= ob_get_clean();
			if ( $attr['metaSku'] ) {
				$content .= '<div class="wopb-meta-sku"><div class="product_meta" style="display: contents !important;">';
				if ( $attr['metaLabelShow'] ) {
						$content .= '<span class="wopb-meta-label-sku meta-block__label">' . esc_attr( $attr['labelSku'] ) . '</span>';
				}
					$content     .= '<span class="wopb-meta-list-sku meta-block__value sku">' . esc_html( $product->get_sku() ) . '</span>';
						$content .= '</div></div>';
			}
			if ( $attr['metaCategory'] ) {
				$terms = $product->get_category_ids();
				if ( count( $terms ) ) {
					$content .= '<div class="wopb-meta-cat">';
					if ( $attr['metaLabelShow'] ) {
							$content .= '<div class="wopb-meta-label-cat meta-block__label">' . esc_html( $attr['labelCat'] ) . '</div>';
					}
						$content     .= '<div class="wopb-meta-list-cat meta-block__value">';
							$content .= $this->list_items( $terms, 'product_cat' );
						$content     .= '</div>';
							$content .= '</div>';
				}
			}
			if ( $attr['metaTag'] ) {
				$tag_terms = $product->get_tag_ids();
				if ( count( $tag_terms ) ) {
					$content .= '<div class="wopb-meta-tag">';
					if ( $attr['metaLabelShow'] ) {
							$content .= '<span class="wopb-meta-label-tag meta-block__label">' . esc_html( $attr['labelTag'] ) . '</span>';
					}
						$content     .= '<div class="wopb-meta-list-tag">';
							$content .= $this->list_items( $tag_terms, 'product_tag' );
						$content     .= '</div>';
							$content .= '</div>';
				}
			}
					$content .= '<div class="wopb-product-meta-end">';
						ob_start();
							do_action( 'woocommerce_product_meta_end' );
							do_action( 'wopb_product_meta_end' );
						$content .= ob_get_clean();
				$content         .= '</div>';
				$content         .= '</div>';
			$wraper_after        .= '</div>';
		}

		return $wraper_before . $content . $wraper_after;
	}
}
