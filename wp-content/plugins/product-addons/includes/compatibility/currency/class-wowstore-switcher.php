<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility\Currency;

defined( 'ABSPATH' ) || exit;

/**
 * WowStore Switcher.
 */
class WowstoreSwitcher {

	/**
	 * The total currency conversion rate (base rate + exchange fee).
	 *
	 * @var float
	 */
	private $rate = 1;

	/**
	 * Constructor.
	 *
	 * Calculates and caches the total conversion rate by combining
	 * the base currency rate and exchange fee from the current currency settings.
	 *
	 * @param array $current_currency The current currency configuration array.
	 */
	public function __construct( $current_currency ) {
		$wopb_current_currency_rate = floatval( ( isset( $current_currency['wopb_currency_rate'] ) && $current_currency['wopb_currency_rate'] > 0 && ! ( '' === $current_currency['wopb_currency_rate'] ) ) ? $current_currency['wopb_currency_rate'] : 1 );
		$wopb_current_exchange_fee  = floatval( ( isset( $current_currency['wopb_currency_exchange_fee'] ) && $current_currency['wopb_currency_exchange_fee'] >= 0 && ! ( '' === $current_currency['wopb_currency_exchange_fee'] ) ) ? $current_currency['wopb_currency_exchange_fee'] : 0 );
		$total_rate                 = ( $wopb_current_currency_rate + $wopb_current_exchange_fee );
		$this->rate                 = $total_rate;
	}

	/**
	 * Convert price to the active currency using WowStore Switcher.
	 *
	 * Multiplies the price by the cached conversion rate (base rate + exchange fee)
	 * to convert to the active currency.
	 *
	 * @param float $price The price in base currency to convert.
	 * @return float The converted price in the active currency.
	 */
	public function convert( $price ) {
		return $price * $this->rate;
	}

	/**
	 * Revert price from the active currency back to the base currency.
	 *
	 * Divides the price by the cached conversion rate to revert
	 * to the base currency.
	 *
	 * @param float $price The price in active currency to revert.
	 * @return float The reverted price in the base currency.
	 */
	public function revert( $price ) {
		return $this->rate > 0 ? $price / $this->rate : $price;
	}
}
