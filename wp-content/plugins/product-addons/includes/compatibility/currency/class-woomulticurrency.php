<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility\Currency;

defined( 'ABSPATH' ) || exit;

/**
 *  Woo Multicurrency
 */
class Woomulticurrency {

	/**
	 * Whether multi-currency is enabled.
	 *
	 * @var bool
	 */
	private $enabled = false;

	/**
	 * Constructor.
	 *
	 * Initializes the currency instance and checks if multi-currency
	 * is enabled to avoid redundant checks in conversion methods.
	 */
	public function __construct() {
		$curcy = null;

		if ( defined( 'WOOMULTI_CURRENCY_VERSION' ) && class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
			$curcy = \WOOMULTI_CURRENCY_Data::get_ins();
		} elseif ( defined( 'WOOMULTI_CURRENCY_F_VERSION' ) && class_exists( 'WOOMULTI_CURRENCY_F_Data' ) ) {
			$curcy = \WOOMULTI_CURRENCY_F_Data::get_ins();
		}

		if ( $curcy && $curcy->get_enable() ) {
			$this->enabled = true;
		}
	}

	/**
	 * Convert price to the active currency using Woo Multicurrency.
	 *
	 * Uses the wmc_get_price() function to convert the price from
	 * the base currency to the active currency.
	 *
	 * @param float $price The price in base currency to convert.
	 * @return float The converted price in the active currency.
	 */
	public function convert( $price ) {
		if ( $this->enabled ) {
			return wmc_get_price( $price );
		}
		return $price;
	}

	/**
	 * Revert price from the active currency back to the base currency.
	 *
	 * Uses the wmc_revert_price() function to revert the price from
	 * the active currency back to the base currency.
	 *
	 * @param float $price The price in active currency to revert.
	 * @return float The reverted price in the base currency.
	 */
	public function revert( $price ) {
		if ( $this->enabled ) {
			return wmc_revert_price( $price );
		}
		return $price;
	}
}
