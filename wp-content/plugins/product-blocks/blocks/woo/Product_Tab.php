<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- template partial included directly into a method scope; these are local render-time variables/functions, not plugin globals.

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

			// WooCommerce's own tabs/upsell/related-products callbacks are removed because the
			// tabs are already rendered manually below; only third-party callbacks registered on
			// this hook (e.g. extensions injecting content around the tabs) should fire here.
			remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
			remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
			remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

			// woocommerce_after_single_product_summary is a single WordPress action - WooCommerce
			// itself distinguishes "before tabs" from "after tabs" only by priority (tabs render
			// at priority 10). Since tabs are drawn manually here rather than via that hook, the
			// action is split into two firings so priority <10 callbacks land before the tabs and
			// priority >=10 callbacks land after, instead of all firing together in one spot.
			$content .= $this->fire_hook_by_priority( 'woocommerce_after_single_product_summary', true );

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

			$content .= $this->fire_hook_by_priority( 'woocommerce_after_single_product_summary', false );

			remove_filter( 'woocommerce_product_tabs', $hide_description );

			remove_filter( 'woocommerce_product_additional_information_heading', $hide_heading );
			remove_filter( 'woocommerce_product_description_heading', $hide_heading );

			$wraper_after .= '</div>';
			$wraper_after .= '</div>';
			$wraper_after .= '</div>';
		}

		return $wraper_before . $content . $wraper_after;
	}

	/**
	 * Fire a WordPress hook but only run the callbacks on one side of priority 10,
	 * so a single hook (e.g. woocommerce_after_single_product_summary) can be split
	 * into a "before" and an "after" insertion point around content rendered manually
	 * in between, without any callback firing twice.
	 *
	 * @param string $hook       Hook name.
	 * @param bool   $before_ten True to run only priority < 10 callbacks, false for >= 10.
	 * @return string Captured output.
	 */
	private function fire_hook_by_priority( $hook, $before_ten ) {
		global $wp_filter;

		// Callbacks are hidden/restored through remove_filter()/add_filter() rather than by
		// mutating $wp_filter[$hook]->callbacks directly, because WP_Hook keeps a separate
		// internal priorities cache that only stays in sync when going through that API.
		$hidden = array();
		if ( ! empty( $wp_filter[ $hook ] ) ) {
			foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
				$keep = $before_ten ? ( $priority < 10 ) : ( $priority >= 10 );
				if ( $keep ) {
					continue;
				}
				foreach ( $callbacks as $registered ) {
					$hidden[] = array(
						'priority'      => $priority,
						'function'      => $registered['function'],
						'accepted_args' => $registered['accepted_args'],
					);
					remove_filter( $hook, $registered['function'], $priority );
				}
			}
		}

		ob_start();
		do_action( $hook );
		$output = ob_get_clean();

		foreach ( $hidden as $registered ) {
			add_filter( $hook, $registered['function'], $registered['priority'], $registered['accepted_args'] );
		}

		return $output;
	}
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
