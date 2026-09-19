<?php
/**
 * Support multi-currency with WPML Multilingual & Multicurrency for WooCommerce.
 *
 * @package affiliate-for-woocommerce/includes/integration/wcml
 * @since   8.61.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_WCML_Compatibility' ) ) {

	/**
	 * Main class to handle WPML Multilingual & Multicurrency for WooCommerce compatibility.
	 */
	class AFWC_WCML_Compatibility {

		/**
		 * Instance of this class.
		 *
		 * @var AFWC_WCML_Compatibility|null
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class.
		 *
		 * @return AFWC_WCML_Compatibility Single instance of this class.
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			// Hook to provide conversion support.
			add_filter( 'afwc_convert_to_base_currency', array( $this, 'convert_to_base_currency' ), 10, 3 );
		}

		/**
		 * Convert amount from one currency to another using WCML
		 *
		 * @param float  $amount         Amount to convert.
		 * @param string $from_currency  Source currency code.
		 * @param string $to_currency    Target currency code.
		 * @return float Converted amount.
		 */
		public function convert_to_base_currency( $amount = 0, $from_currency = '', $to_currency = '' ) {

			if ( empty( $amount ) || empty( $from_currency ) || empty( $to_currency ) || ! class_exists( 'woocommerce_wpml' ) ) {
				return $amount;
			}

			// No conversion needed if same currency.
			if ( $from_currency === $to_currency ) {
				return $amount;
			}

			global $woocommerce_wpml;

			try {
				if ( empty( $woocommerce_wpml ) || empty( $woocommerce_wpml->multi_currency ) || empty( $woocommerce_wpml->multi_currency->prices ) ) {
					return $amount;
				}

				$prices = $woocommerce_wpml->multi_currency->prices;

				if ( ! is_callable( array( $prices, 'convert_price_amount_by_currencies' ) ) ) {
					return $amount;
				}

				$converted_amount = $prices->convert_price_amount_by_currencies( $amount, $from_currency, $to_currency );
				return is_numeric( $converted_amount ) ? floatval( $converted_amount ) : $amount;
			} catch ( Throwable $e ) {
				Affiliate_For_WooCommerce::log_error( __METHOD__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			}

			return $amount;
		}
	}
}

AFWC_WCML_Compatibility::get_instance();
