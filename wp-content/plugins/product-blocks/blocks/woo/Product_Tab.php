<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Product_Tab {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'showDescription'     => true,
			'showAddInfo'         => true,
			'showReview'          => true,
			'reviewHeading'       => true,
			'headingText'         => 'Product Tab',
			'currentPostId'       => '',
			'descriptionLabel'    => '',
			'additionalInfoLabel' => '',
			'reviewLabel'         => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/product-tab',
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
		$block_name    = 'product-tab';
		$wraper_before = $wraper_after = $content = '';
		$attr          = wp_parse_args( $attr, $this->get_attributes() );

		if ( ! empty( $product ) ) {
			global $productx_tab;
			$productx_tab['desc']   = $attr['showDescription'];
			$productx_tab['info']   = $attr['showAddInfo'];
			$productx_tab['review'] = $attr['showReview'];
			$hide_description       = function ( $tabs ) {
				global $productx_tab;
				if ( ! $productx_tab['desc'] ) {
					unset( $tabs['description'] );
				}
				if ( ! $productx_tab['info'] ) {
					unset( $tabs['additional_information'] );
				}
				if ( ! $productx_tab['review'] ) {
					unset( $tabs['reviews'] );
				}
				return $tabs;
			};

			$hide_heading = function () {
				return '';
			};

			$product_tabs = apply_filters( 'woocommerce_product_tabs', array() );

			if ( ! ( $attr['showDescription'] ) && isset( $product_tabs['description'] ) ) {
				unset( $product_tabs['description'] );
			}
			if ( ! ( $attr['showAddInfo'] && isset( $product_tabs['additional_information'] ) ) ) {
				unset( $product_tabs['additional_information'] );
			}
			if ( ! ( $attr['showReview'] && isset( $product_tabs['reviews'] ) ) ) {
				unset( $product_tabs['reviews'] );
			}

			$productx_tab['add_text'] = esc_html( $attr['headingText'] );

			$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
			$attr['align']     = ! empty( $attr['align'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
			$reviewHeading     = ! ( isset( $attr['reviewHeading'] ) && $attr['reviewHeading'] == true ) ? ' wopb_hide_r_head' : '';

			$wraper_before .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . $attr['align'] . $reviewHeading . '">';
			$wraper_before .= '<div class="wopb-product-wrapper">';
			$wraper_before .= '<div class="product">';

			add_filter( 'woocommerce_product_tabs', $hide_description );
			add_filter( 'woocommerce_product_additional_information_heading', $hide_heading );
			add_filter( 'woocommerce_product_description_heading', $hide_heading );

			if ( ! empty( $product_tabs ) ) {
				$content .= '<div class="woocommerce-tabs wc-tabs-wrapper">';
				$content .= '<ul class="tabs wc-tabs" role="tablist">';
				foreach ( $product_tabs as $key => $product_tab ) {
					if ( ! empty( $product_tab['title'] ) ) {
						// Determine the tab title - use custom label if available.
						$tab_title = $product_tab['title'];
						if ( 'description' === $key && ! empty( $attr['descriptionLabel'] ) ) {
							$tab_title = $attr['descriptionLabel'];
						} elseif ( 'additional_information' === $key && ! empty( $attr['additionalInfoLabel'] ) ) {
							$tab_title = $attr['additionalInfoLabel'];
						} elseif ( 'reviews' === $key && ! empty( $attr['reviewLabel'] ) ) {
							$tab_title = $attr['reviewLabel'];
						}

						$content .= '<li class="' . esc_attr( $key ) . '_tab" id="tab-title-' . esc_attr( $key ) . '" role="tab" aria-controls="tab-' . esc_attr( $key ) . '">';
						$content .= '<a href="#tab-' . esc_attr( $key ) . '">';
						ob_start();
						echo wp_kses_post( apply_filters( 'woocommerce_product_' . $key . '_tab_title', $tab_title, $key ) );
						$content .= ob_get_clean();
						$content .= '</a>';
						$content .= '</li>';
					}
				}
				$content .= '</ul>';
				foreach ( $product_tabs as $key => $product_tab ) {
					$content .= '<div class="woocommerce-Tabs-panel woocommerce-Tabs-panel--' . esc_attr( $key ) . ' panel entry-content wc-tab" id="tab-' . esc_attr( $key ) . '" role="tabpanel" aria-labelledby="tab-title-' . esc_attr( $key ) . '">';
					if ( isset( $product_tab['callback'] ) ) {
						if ( 'description' === $key ) {
							if ( $description = $product->get_description() ) {
								$content .= wpautop( $description );
							}
						} else {
							ob_start();
							call_user_func( $product_tab['callback'], $key, $product_tab );
							$content .= ob_get_clean();
						}
					}
					$content .= '</div>';
				}

				ob_start();
				do_action( 'woocommerce_product_after_tabs' );
				$content .= ob_get_clean();
				$content .= '</div>';
			}

			remove_filter( 'woocommerce_product_tabs', $hide_description );

			remove_filter( 'woocommerce_product_additional_information_heading', $hide_heading );
			remove_filter( 'woocommerce_product_description_heading', $hide_heading );

			$wraper_after .= '</div>';
			$wraper_after .= '</div>';
			$wraper_after .= '</div>';
		}

		return $wraper_before . $content . $wraper_after;
	}
}
