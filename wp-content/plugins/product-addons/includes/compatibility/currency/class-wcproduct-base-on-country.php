<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility\Currency;

defined( 'ABSPATH' ) || exit;

/**
 *  WooCommerce Product Price Based on Countries ( WCPBC ) by Oscar Gare
 */
class WCProductBaseOnCountry {
	/**
	 * The pricing zone instance.
	 *
	 * @var object|null
	 */
	private $zone;

	/**
	 * Constructor.
	 *
	 * Initializes the pricing zone for the current user's country.
	 */
	public function __construct() {
		$this->zone = wcpbc_the_zone();
	}

	/**
	 * Convert price to the zone's currency.
	 *
	 * Uses the zone's exchange rate to convert the price from the base
	 * currency to the currency configured for the user's country/zone.
	 *
	 * @param float $price The price in base currency to convert.
	 * @return float The converted price in the zone's currency, or original price if zone is unavailable.
	 */
	public function convert( $price ) {
		if ( $this->zone && method_exists( $this->zone, 'get_exchange_rate_price' ) ) {
			return $this->zone->get_exchange_rate_price( $price );
		}
		return $price;
	}

	/**
	 * Revert price from the zone's currency back to the base currency.
	 *
	 * Uses the zone's exchange rate to convert the price from the
	 * zone's currency back to the base currency.
	 *
	 * @param float $price The price in zone's currency to revert.
	 * @return float The reverted price in the base currency, or original price if zone is unavailable.
	 */
	public function revert( $price ) {
		if ( $this->zone && method_exists( $this->zone, 'get_base_currency_amount' ) ) {
			return $this->zone->get_base_currency_amount( $price );
		}

		return $price;
	}
}
