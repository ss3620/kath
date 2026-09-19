<?php //phpcs:ignore

namespace PRAD\Includes\Compatibility\Currency;

defined( 'ABSPATH' ) || exit;

/**
 *  Mudra (Woo Exchange Rate) Currency Switcher by Codeixer
 */
class MudraCurrencySwitcher {

	/**
	 * The currency conversion rate.
	 *
	 * @var float
	 */
	private $rate = 1;

	/**
	 * Constructor.
	 *
	 * Initializes the currency rate from the WOOER Currency_Manager
	 * to avoid redundant calls in conversion methods.
	 */
	public function __construct() {
		$this->rate = \WOOER\Currency_Manager::get_currency_rate();
	}

	/**
	 * Convert price to the active currency using Mudra Currency Switcher.
	 *
	 * Multiplies the price by the currency rate obtained from the
	 * WOOER Currency_Manager to convert to the active currency.
	 *
	 * @param float $price The price in base currency to convert.
	 * @return float The converted price in the active currency, or original price if rate is unavailable.
	 */
	public function convert( $price ) {
		return $price * $this->rate;
	}

	/**
	 * Revert price from the active currency back to the base currency.
	 *
	 * Divides the price by the currency rate obtained from the
	 * WOOER Currency_Manager to revert to the base currency.
	 *
	 * @param float $price The price in active currency to revert.
	 * @return float The reverted price in the base currency, or original price if rate is unavailable.
	 */
	public function revert( $price ) {
		return $this->rate > 0 ? $price / $this->rate : $price;
	}
}
