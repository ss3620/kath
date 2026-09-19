<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility\Currency;

defined( 'ABSPATH' ) || exit;

/**
 *  Aelia Currency Switcher
 */
class AeliaCurrencySwitcher {

	/**
	 * The currently active currency code.
	 *
	 * @var string
	 */
	private $active_currency;

	/**
	 * The base currency code.
	 *
	 * @var string
	 */
	private $base_currency;

	/**
	 * Constructor.
	 *
	 * Initializes the active and base currency codes for use in conversion methods.
	 */
	public function __construct() {
		$this->active_currency = get_woocommerce_currency();
		$this->base_currency   = apply_filters( 'wc_aelia_cs_base_currency', '' );// phpcs:ignore
	}

	/**
	 * Convert price to the active currency using Aelia Currency Switcher.
	 *
	 * Uses the 'wc_aelia_cs_convert' filter to convert the price from the
	 * base currency to the currently active currency.
	 *
	 * @param float $price The price in base currency to convert.
	 * @return float The converted price in the active currency.
	 */
	public function convert( $price ) {
		return apply_filters( 'wc_aelia_cs_convert', $price, $this->base_currency, $this->active_currency );// phpcs:ignore
	}

	/**
	 * Revert price from the active currency back to the base currency.
	 *
	 * Uses the 'wc_aelia_cs_convert' filter to revert the price from the
	 * currently active currency back to the base currency.
	 *
	 * @param float $price The price in active currency to revert.
	 * @return float The reverted price in the base currency.
	 */
	public function revert( $price ) {
		return apply_filters( 'wc_aelia_cs_convert', $price, $this->active_currency, $this->base_currency );// phpcs:ignore
	}
}
