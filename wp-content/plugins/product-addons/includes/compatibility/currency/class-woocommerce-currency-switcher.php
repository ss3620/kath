<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility\Currency;

use PRAD\Includes\Common\Functions;

defined( 'ABSPATH' ) || exit;

/**
 *  WooCommerce Currency Switcher by WPExperts
 */
class WoocommerceCurrencySwitcher {
	/**
	 * Convert price to the active currency using WooCommerce Currency Switcher.
	 *
	 * @param float $price The price to convert.
	 * @return float The converted price in the active currency.
	 */
	public function convert( $price ) {
		if ( defined( 'WCCS_VERSION' ) ) {
			return apply_filters( 'woocommerce_product_addons_option_price_raw', $price, '' );// phpcs:ignore
		}
		return $price;
	}

	/**
	 * Revert price from the active currency back to the base currency.
	 *
	 * @param float $price The price in the active currency to revert.
	 * @return float The price reverted to the base currency.
	 */
	public function revert( $price ) {
		if ( defined( 'WCCS_VERSION' ) ) {
			// Dont have any filter to revert the price.
			return product_addons()->manual_currency_reverted_price( $price );
		}
		return $price;
	}
}
