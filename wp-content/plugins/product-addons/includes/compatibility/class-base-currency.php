<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility;

use PRAD\Includes\Compatibility\Currency\AeliaCurrencySwitcher;
use PRAD\Includes\Compatibility\Currency\FoxCurrencySwitcher;
use PRAD\Includes\Compatibility\Currency\MudraCurrencySwitcher;
use PRAD\Includes\Compatibility\Currency\WcMultiCurrencySwitcher;
use PRAD\Includes\Compatibility\Currency\WcpayMulticurrency;
use PRAD\Includes\Compatibility\Currency\WCProductBaseOnCountry;
use PRAD\Includes\Compatibility\Currency\WoocommerceCurrencySwitcher;
use PRAD\Includes\Compatibility\Currency\WoocommerceMultilingualMulticurrency;
use PRAD\Includes\Compatibility\Currency\Woomulticurrency;
use PRAD\Includes\Compatibility\Currency\WowstoreSwitcher;
use PRAD\Includes\Compatibility\Currency\WpwhamCurrencySwitcher;
use PRAD\Includes\Compatibility\Currency\XCurrency;
use PRAD\Includes\Compatibility\Currency\YayCurrency;
use PRAD\Includes\Compatibility\Currency\YithCurrencySwitcher;

defined( 'ABSPATH' ) || exit;

/**
 * Base currency compatibility layer.
 *
 * Detects and manages currency conversion across multiple WooCommerce currency plugins.
 * Provides a unified interface for converting prices to and from different currencies.
 */
class BaseCurrency {

	/**
	 * The currency converter instance.
	 *
	 * @var object|null Currency converter object or null if none detected.
	 */
	private static $converter;

	/**
	 * Resolve and return the appropriate currency converter.
	 *
	 * Detects which currency plugin is active and returns the corresponding
	 * converter instance. The converter is cached in the static property to
	 * avoid repeated detection checks.
	 *
	 * @return object|null The currency converter instance or null if no compatible plugin is detected.
	 */
	public static function resolve() {
		if ( self::$converter ) {
			return self::$converter;
		}

		if ( defined( 'WOPB_VER' ) && defined( 'WOPB_PRO_VER' ) && class_exists( 'WOPB_PRO\Currency_Switcher_Action' ) ) {
			$current_currency_code = wopb_function()->get_setting( 'wopb_current_currency' );
			$default_currency      = wopb_function()->get_setting( 'wopb_default_currency' );
			$current_currency      = \WOPB_PRO\Currency_Switcher_Action::get_currency( $current_currency_code );
			if ( ! $current_currency ) {
				$current_currency = $default_currency;
			}

			if ( $current_currency_code !== $default_currency ) {
				self::$converter = new WowstoreSwitcher( $current_currency );
				return self::$converter;
			}
		}

		if ( defined( 'WCCS_VERSION' ) ) {
			self::$converter = new WoocommerceCurrencySwitcher();
			return self::$converter;
		}

		if ( function_exists( 'wmc_get_price' ) ) {
			self::$converter = new Woomulticurrency();
			return self::$converter;
		}

		if ( defined( 'YAY_CURRENCY_VERSION' ) ) {
			self::$converter = new YayCurrency();
			return self::$converter;
		}

		if ( defined( 'WOOCS_VERSION' ) ) {
			self::$converter = new FoxCurrencySwitcher();
			return self::$converter;
		}

		if ( function_exists( 'alg_convert_price' ) && function_exists( 'alg_get_current_currency_code' ) ) {
			self::$converter = new WpwhamCurrencySwitcher();
			return self::$converter;
		}

		if ( function_exists( 'yith_wcmcs_convert_price' ) ) {
			self::$converter = new YithCurrencySwitcher();
			return self::$converter;
		}

		if ( class_exists( 'WC_Aelia_CurrencySwitcher' ) ) {
			self::$converter = new AeliaCurrencySwitcher();
			return self::$converter;
		}

		if ( class_exists( '\WCPay\MultiCurrency\MultiCurrency' ) ) {
			self::$converter = new WcpayMulticurrency();
			return self::$converter;
		}

		if ( class_exists( 'XCurrency' ) ) {
			self::$converter = new XCurrency();
			return self::$converter;
		}

		if (
			class_exists( 'WC_Product_Price_Based_Country' ) &&
			function_exists( 'wcpbc_the_zone' )
		) {
			self::$converter = new WCProductBaseOnCountry();
			return self::$converter;
		}

		if ( function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on() ) {
			self::$converter = new WoocommerceMultilingualMulticurrency();
			return self::$converter;
		}

		if ( class_exists( 'WOOER\Currency_Manager' ) ) {
			self::$converter = new MudraCurrencySwitcher();
			return self::$converter;
		}

		if ( class_exists( '\APBDWMC_general' ) ) {
			self::$converter = new WcMultiCurrencySwitcher();
			return self::$converter;
		}

		return null;
	}

	/**
	 * Convert price to the active currency.
	 *
	 * Uses the detected currency converter to convert the price from the base
	 * currency to the currently active currency.
	 *
	 * @param float $price The price in base currency to convert.
	 * @return float The converted price in the active currency, or original price if no converter is available.
	 */
	public static function convert( $price ) {
		$converter = self::resolve();
		if ( $converter ) {
			return $converter->convert( $price );
		}
		return $price;
	}

	/**
	 * Revert price from the active currency back to the base currency.
	 *
	 * Uses the detected currency converter to revert the price from the currently
	 * active currency back to the base currency.
	 *
	 * @param float $price The price in active currency to revert.
	 * @return float The reverted price in the base currency, or original price if no converter is available.
	 */
	public static function revert( $price ) {
		$converter = self::resolve();
		if ( $converter ) {
			return $converter->revert( $price );
		}
		return $price;
	}
}
