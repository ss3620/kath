<?php
/**
 * Animated Add to Cart Addons Core.
 *
 * @package WOPB\AnimatedCart
 * @since v.4.0.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * AnimatedCart class.
 */
class AnimatedCart {

	/**
	 * Setup class.
	 *
	 * @since v.4.0.0
	 */
	public function __construct() {
		add_filter( 'body_class', array( $this, 'anim_body_classes' ) );
		add_filter( 'woocommerce_loop_add_to_cart_args', array( $this, 'loop_add_to_cart_args' ), 100, 2 );
	}

	/**
	 * Add Body Class
	 *
	 * @param $classes
	 * @return array
	 * @since v.4.0.0
	 */
	public function anim_body_classes( $classes ) {
		$type = wopb_function()->get_setting( 'animated_cart_apply' );
		if ( is_array( $type ) ) {
			if ( ( in_array( 'single', $type ) && is_product() ) || ( in_array( 'archive', $type ) && ( is_product_tag() || is_product_category() || is_shop() ) ) ) {
				$classes[] = 'wopb-animation-cart';
				$classes[] = 'wopb_anim_show-' . wopb_function()->get_setting( 'animated_cart_showcase' );
				$classes[] = 'wopb_anim_name-' . wopb_function()->get_setting( 'animated_cart_animation' );
				$classes[] = 'wopb_anim_interval-' . wopb_function()->get_setting( 'animated_cart_interval' );
			}
		}
		return $classes;
	}

	/**
	 * Add Class to Add To Cart Button
	 *
	 * @param $args
	 * @param $product
	 * @return array
	 * @since v.4.0.0
	 */
	public function loop_add_to_cart_args( $args, $product ) {
		if ( ! empty( $args['class'] ) ) {
			$args['class'] .= ' wopb-anim-cart-btn';
		} else {
			$args['class'] = ' wopb-anim-cart-btn';
		}
		return $args;
	}
}
