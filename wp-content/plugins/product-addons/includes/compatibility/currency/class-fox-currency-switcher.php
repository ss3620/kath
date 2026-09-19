<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility\Currency;

defined( 'ABSPATH' ) || exit;

/**
 *  Fox Currency Switcher
 */
class FoxCurrencySwitcher {

	/**
	 * Convert price to the active currency using Fox Currency Switcher.
	 *
	 * Uses the 'woocs_convert_price' filter to convert the price from the
	 * base currency to the currently active currency.
	 *
	 * @param float $price The price in base currency to convert.
	 * @return float The converted price in the active currency.
	 */
	public function convert( $price ) {
		return apply_filters( 'woocs_convert_price', $price, '' ); // phpcs:ignore
	}

	/**
	 * Revert price from the active currency back to the base currency.
	 *
	 * Uses the 'woocs_back_convert_price' filter to revert the price from the
	 * currently active currency back to the base currency.
	 *
	 * @param float $price The price in active currency to revert.
	 * @return float The reverted price in the base currency.
	 */
	public function revert( $price ) {
		return apply_filters( 'woocs_back_convert_price', $price, '' );// phpcs:ignore
	}
}
