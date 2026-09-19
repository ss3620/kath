<?php
/**
 * Support multi-currency with Aelia Currency Switcher for WooCommerce.
 *
 * @package affiliate-for-woocommerce/includes/integration/aelia-currency-switcher
 * @since   8.61.0
 * @version 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Aelia\WC\CurrencySwitcher\WC_Aelia_CurrencySwitcher;

if ( ! class_exists( 'AFWC_Aelia_Compatibility' ) ) {

	/**
	 * Main class to handle Aelia Currency Switcher for WooCommerce compatibility.
	 */
	class AFWC_Aelia_Compatibility {

		/**
		 * Instance of this class.
		 *
		 * @var AFWC_Aelia_Compatibility|null
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return AFWC_Aelia_Compatibility Single instance of this class.
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
		 * Convert amount from one currency to another using Aelia Currency Switcher
		 *
		 * @param float  $amount         Amount to convert.
		 * @param string $from_currency  Source currency code.
		 * @param string $to_currency    Target currency code.
		 * @return float Converted amount.
		 */
		public function convert_to_base_currency( $amount = 0, $from_currency = '', $to_currency = '' ) {

			if ( empty( $amount ) || empty( $from_currency ) || empty( $to_currency ) || ! class_exists( WC_Aelia_CurrencySwitcher::class ) ) {
				return $amount;
			}

			// No conversion needed if same currency.
			if ( $from_currency === $to_currency ) {
				return $amount;
			}

			try {
				$currency_switcher = is_callable( array( WC_Aelia_CurrencySwitcher::class, 'instance' ) ) ? WC_Aelia_CurrencySwitcher::instance() : null;

				if ( ! is_callable( array( $currency_switcher, 'convert' ) ) ) {
					return $amount;
				}

				$converted_amount = $currency_switcher->convert( $amount, $from_currency, $to_currency );
				return is_numeric( $converted_amount ) ? floatval( $converted_amount ) : $amount;
			} catch ( Throwable $e ) {
				Affiliate_For_WooCommerce::log_error( __METHOD__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			}

			return $amount;
		}
	}
}

AFWC_Aelia_Compatibility::get_instance();
