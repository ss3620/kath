<?php

/**
 * GermanizedSupport Action.
 *
 * Provides flexible integration with WooCommerce Germanized plugin.
 *
 * @package WOPB\Notice
 * @since v4.0.0
 */

namespace WOPB\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Class GermanizedSupport
 */
class GermanizedSupport {
	/**
	 * Constructor.
	 *
	 * Registers shortcodes.
	 */
	public function __construct() {
		// Shortcode that returns content.
		add_shortcode( 'wopb_germanized_contents', array( $this, 'shortcode_return' ) );

		// Shortcode that echoes content immediately.
		add_shortcode( 'wopb_germanized_contents_echo', array( $this, 'shortcode_echo' ) );
	}

	/**
	 * Get Germanized content as a string.
	 *
	 * Can be used in PHP templates:
	 * echo GermanizedSupport::get_content();
	 *
	 * @return string
	 */
	public static function get_content() {
		global $product;

		if ( ! $product ) {
			return '';
		}

		$germanized_wc_calllbacks = array(
			'unit_price'                 => 'woocommerce_gzd_template_single_price_unit',
			'legal'                      => 'woocommerce_gzd_template_single_legal_info',
			'delivery_time'              => 'woocommerce_gzd_template_single_delivery_time_info',
			// 'units'                      => 'woocommerce_gzd_template_single_product_units',
			'manufacturer'               => 'woocommerce_gzd_template_single_manufacturer',
			'product_safety_attachments' => 'woocommerce_gzd_template_single_product_safety_attachments',
			'safety_instructions'        => 'woocommerce_gzd_template_single_safety_instructions',
			'defect_description'         => 'woocommerce_gzd_template_single_defect_description',
			'power_supply'               => 'woocommerce_gzd_template_single_product_power_supply',
			'deposit'                    => 'woocommerce_gzd_template_single_deposit',
			'deposit_packaging_type'     => 'woocommerce_gzd_template_single_deposit_packaging_type',
			'nutri_score'                => 'woocommerce_gzd_template_single_nutri_score',
		);

		ob_start();
		echo '<div class="wopb-germanized-info">';

		/**
		 * Fires before Germanized content is output.
		 * Useful for adding custom HTML or actions.
		 *
		 * @param \WC_Product $product The current WooCommerce product.
		 */
		do_action( 'wopb_germanized_before', $product );

		foreach ( $germanized_wc_calllbacks as $func ) {
			if ( function_exists( $func ) ) {
				ob_start();
				$func();
				$output = ob_get_clean();
				if ( trim( $output ) !== '' ) {
					echo $output; //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
		}

		/**
		 * Fires after Germanized content is output.
		 *
		 * @param \WC_Product $product The current WooCommerce product.
		 */
		do_action( 'wopb_germanized_after', $product );

		echo '</div>';

		$content = ob_get_clean();

		/**
		 * Filter Germanized output before returning.
		 *
		 * Example usage:
		 * add_filter('wopb_germanized_contents_output', function($content, $product){
		 *     return '<div class="custom-wrapper">' . $content . '</div>';
		 * }, 10, 2);
		 *
		 * @param string      $content Output HTML
		 * @param \WC_Product $product Current product
		 */
		return apply_filters( 'wopb_germanized_contents_output', wopb_function()->wp_kses_safe( $content ), $product );
	}

	/**
	 * Shortcode that returns content (for editor usage)
	 *
	 * Usage: [wopb_germanized_contents]
	 *
	 * @return string
	 */
	public function shortcode_return() {
		return wopb_function()->wp_kses_safe( self::get_content() );
	}

	/**
	 * Shortcode that echoes content directly (rarely needed)
	 *
	 * Usage: [wopb_germanized_contents_echo]
	 */
	public function shortcode_echo() {
		echo wopb_function()->wp_kses_safe( self::get_content() ); //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
