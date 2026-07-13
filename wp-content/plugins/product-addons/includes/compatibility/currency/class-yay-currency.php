<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility\Currency;

defined( 'ABSPATH' ) || exit;

/**
 *  Yay Currency Switcher
 */
class YayCurrency {

	/**
	 * Convert price to the active currency using Yay Currency.
	 *
	 * Uses the 'yay_currency_convert_price' filter to convert the price from the
	 * base currency to the currently active currency.
	 *
	 * @param float $price The price in base currency to convert.
	 * @return float The converted price in the active currency.
	 */
	public function convert( $price ) {
		return apply_filters( 'yay_currency_convert_price', $price, '' );// phpcs:ignore
	}

	/**
	 * Revert price from the active currency back to the base currency.
	 *
	 * Uses the 'yay_currency_revert_price' filter to revert the price from the
	 * currently active currency back to the base currency.
	 *
	 * @param float $price The price in active currency to revert.
	 * @return float The reverted price in the base currency.
	 */
	public function revert( $price ) {
		return apply_filters( 'yay_currency_revert_price', $price, '' );// phpcs:ignore
	}
}
