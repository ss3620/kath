<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility\Currency;

defined( 'ABSPATH' ) || exit;

/**
 * WooCommerce Multilingual and Multicurrency for WooCommerce (WCML) by OnTheGoSystems
 */
class WoocommerceMultilingualMulticurrency {

	/**
	 * The WCML prices instance.
	 *
	 * @var object|null
	 */
	private $prices;

	/**
	 * Constructor.
	 *
	 * Initializes the WCML prices instance to avoid redundant
	 * global variable access in conversion methods.
	 */
	public function __construct() {
		global $woocommerce_wpml;
		if (
			isset( $woocommerce_wpml->multi_currency->prices ) &&
			is_object( $woocommerce_wpml->multi_currency->prices )
		) {
			$this->prices = $woocommerce_wpml->multi_currency->prices;
		}
	}

	/**
	 * Convert price to the active currency using WCML.
	 *
	 * Uses the cached WCML prices instance to convert the price
	 * from the base currency to the active currency.
	 *
	 * @param float $price The price in base currency to convert.
	 * @return float The converted price in the active currency, or original price if conversion is unavailable.
	 */
	public function convert( $price ) {
		if ( $this->prices && method_exists( $this->prices, 'convert_price_amount' ) ) {
			return $this->prices->convert_price_amount( $price );
		}
		return $price;
	}

	/**
	 * Revert price from the active currency back to the base currency.
	 *
	 * Uses the cached WCML prices instance to revert the price
	 * from the active currency back to the base currency.
	 *
	 * @param float $price The price in active currency to revert.
	 * @return float The reverted price in the base currency, or original price if conversion is unavailable.
	 */
	public function revert( $price ) {
		if ( $this->prices && method_exists( $this->prices, 'unconvert_price_amount' ) ) {
			return $this->prices->unconvert_price_amount( $price );
		}
		return $price;
	}
}
