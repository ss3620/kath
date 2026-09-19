<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility\Currency;

defined( 'ABSPATH' ) || exit;

/**
 *  X-Currency by DoatKolom
 */
class XCurrency {
	/**
	 * Convert price to the active currency using X-Currency.
	 *
	 * Uses the x_currency_exchange() function to convert the price from the
	 * base currency to the currently active currency.
	 *
	 * @param float $price The price in base currency to convert.
	 * @return float The converted price in the active currency, or original price if function is unavailable.
	 */
	public function convert( $price ) {
		if ( function_exists( 'x_currency_exchange' ) ) {
			return x_currency_exchange( $price );
		}
		return $price;
	}

	/**
	 * Revert price from the active currency back to the base currency.
	 *
	 * Uses the x_currency_exchange_revert() function to revert the price from the
	 * currently active currency back to the base currency.
	 *
	 * @param float $price The price in active currency to revert.
	 * @return float The reverted price in the base currency, or original price if function is unavailable.
	 */
	public function revert( $price ) {
		if ( function_exists( 'x_currency_exchange_revert' ) ) {
			return x_currency_exchange_revert( $price );
		}
		return $price;
	}
}
