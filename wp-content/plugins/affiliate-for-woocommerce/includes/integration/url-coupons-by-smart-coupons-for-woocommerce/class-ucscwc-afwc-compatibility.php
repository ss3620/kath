<?php
/**
 * Main class for URL Coupons Compatibility
 *
 * @package  affiliate-for-woocommerce/includes/integration/url-coupons-by-smart-coupons-for-woocommerce/
 * @since    9.7.0
 * @version  1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UCSCWC_AFWC_Compatibility' ) ) {

	/**
	 * Compatibility class for URL Coupons by Smart Coupons for WooCommerce.
	 */
	class UCSCWC_AFWC_Compatibility {

		/**
		 * Variable to hold instance of UCSCWC_AFWC_Compatibility
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		private function __construct() {
			// Filter to generate affiliate coupon URL.
			add_filter( 'afwc_coupon_url', array( $this, 'generate_coupon_url' ), 10, 2 );

			add_filter( 'afwc_coupon_shareable_link_is_available', '__return_true' );
		}

		/**
		 * Get single instance of UCSCWC_AFWC_Compatibility
		 *
		 * @return UCSCWC_AFWC_Compatibility Singleton object of UCSCWC_AFWC_Compatibility
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Generate the coupon URL.
		 *
		 * @param string $link The existing link.
		 * @param string $code The coupon code.
		 *
		 * @return string The generated coupon URL.
		 */
		public function generate_coupon_url( $link = '', $code = '' ) {
			if ( empty( $code ) ) {
				return $link;
			}

			if ( ! function_exists( 'ucscwc_generate_coupon_url' ) ) {
				return $link;
			}

			return ucscwc_generate_coupon_url( $code, home_url( '/' ) );
		}
	}
}

UCSCWC_AFWC_Compatibility::get_instance();
