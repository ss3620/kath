<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility\Currency;

use PRAD\Includes\Common\Functions;

defined( 'ABSPATH' ) || exit;

/**
 *  Yith Currency Switcher
 */
class YithCurrencySwitcher {

	/**
	 * Convert price to the active currency using Yith Currency Switcher.
	 *
	 * Uses the 'yith_wcmcs_convert_price' filter to convert the price from the
	 * base currency to the currently active currency.
	 *
	 * @param float $price The price in base currency to convert.
	 * @return float The converted price in the active currency.
	 */
	public function convert( $price ) {
		return apply_filters( 'yith_wcmcs_convert_price', $price, '' ); // phpcs:ignore
	}

	/**
	 * Revert price from the active currency back to the base currency.
	 *
	 * Uses manual currency reversion since Yith doesn't provide a direct
	 * revert filter.
	 *
	 * @param float $price The price in active currency to revert.
	 * @return float The reverted price in the base currency.
	 */
	public function revert( $price ) {
		return product_addons()->manual_currency_reverted_price( $price );
	}
}
