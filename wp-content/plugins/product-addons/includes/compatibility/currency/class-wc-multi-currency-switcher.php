<?php //phpcs:ignore

namespace PRAD\Includes\Compatibility\Currency;

defined( 'ABSPATH' ) || exit;

/**
 *  WC Multi Currency Switcher (by palscode)
 */
class WcMultiCurrencySwitcher {

	/**
	 * The currency conversion rate.
	 *
	 * @var float
	 */
	private $rate = 1;

	/**
	 * Constructor.
	 *
	 * Initializes the currency rate from the APBDWMC module instance
	 * to avoid redundant calls in conversion methods.
	 */
	public function __construct() {
		$module = \APBDWMC_general::GetModuleInstance();
		if ( $module && ! empty( $module->active_currency ) ) {
			$rate = $module->active_currency->rate;
			if ( $rate ) {
				$this->rate = $rate;
			}
		}
	}

	/**
	 * Convert price to the active currency using WC Multi Currency Switcher.
	 *
	 * Multiplies the price by the cached currency rate to convert
	 * to the active currency.
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
	 * Divides the price by the cached currency rate to revert
	 * to the base currency.
	 *
	 * @param float $price The price in active currency to revert.
	 * @return float The reverted price in the base currency.
	 */
	public function revert( $price ) {
		return $this->rate > 0 ? $price / $this->rate : $price;
	}
}
