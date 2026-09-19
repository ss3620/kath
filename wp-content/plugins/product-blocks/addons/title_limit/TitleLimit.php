<?php
/**
 * Product Title Limit Addons Core.
 *
 * @package WOPB\TitleLimit
 * @since v.4.0.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * TitleLimit class.
 */
class TitleLimit {

	/**
	 * Setup class.
	 *
	 * @since v.4.0.0
	 */
	public function __construct() {
		add_filter( 'body_class', array( $this, 'title_limit_body_class' ), 10, 1 );
		add_action( 'wopb_save_settings', array( $this, 'generate_css' ), 10, 1 ); // CSS Generator
	}

	/**
	 * Add Body Class for Checking
	 *
	 * @return NULL
	 * @since v.4.0.0
	 */
	public function title_limit_body_class( $classes ) {
		if ( wopb_function()->get_setting( 'title_limit_single' ) == 'yes' && is_product() ) {
			$classes[] = 'wopb-title-limit-single';
		} elseif ( wopb_function()->get_setting( 'title_limit_archive' ) == 'yes' && ( is_shop() || is_product_taxonomy() ) ) {
			$classes[] = 'wopb-title-limit-archive';
		}
		return $classes;
	}

	/**
	 * CSS Generator
	 *
	 * @return NULL
	 * @since v.4.0.0
	 */
	public function generate_css( $key ) {
		if ( $key == 'wopb_title_limit' ) {
			$css_class1 = wopb_function()->get_setting( 'title_limit_archive' ) == 'yes' ? '.woocommerce-loop-product__title, .wopb-title-limit-archive .wp-block-post-title a' : '';
			$css_class2 = wopb_function()->get_setting( 'title_limit_single' ) == 'yes' ? '.product_title.entry-title, .wp-block-column-is-layout-flow > .wp-block-post-title' : '';
			$css_class  = ( $css_class1 && $css_class2 ) ? ( $css_class1 . ',' . $css_class2 ) : ( $css_class1 ? $css_class1 : $css_class2 );
			if ( $css_class ) {
				$css = $css_class . '{ overflow: hidden; text-overflow: ellipsis; display: -webkit-box;-webkit-line-clamp: 1;-webkit-box-orient: vertical; }';
			}
			wopb_function()->update_css( $key, 'add', $css );
		}
	}
}
