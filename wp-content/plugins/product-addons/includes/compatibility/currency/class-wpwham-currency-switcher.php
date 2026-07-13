<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility\Currency;

defined( 'ABSPATH' ) || exit;

/**
 * Currency Switcher for WooCommerce by wpwham.
 */
class WpwhamCurrencySwitcher {

	/**
	 * Whether currency conversion is enabled.
	 *
	 * @var bool
	 */
	private $enabled = false;

	/**
	 * The default currency code.
	 *
	 * @var string
	 */
	private $default_currency;

	/**
	 * The current currency code.
	 *
	 * @var string
	 */
	private $current_currency_code;

	/**
	 * Constructor.
	 *
	 * Initializes the currency codes and checks if conversion is needed
	 * to avoid redundant function calls in conversion methods.
	 */
	public function __construct() {
		$this->default_currency      = get_option( 'woocommerce_currency' );
		$this->current_currency_code = alg_get_current_currency_code();
		if ( $this->current_currency_code !== $this->default_currency ) {
			$this->enabled = true;
		}
	}
	/**
	 * Convert price to the active currency using wpwham Currency Switcher.
	 *
	 * Uses the alg_convert_price() function to convert the price from the
	 * default currency to the currently active currency.
	 *
	 * @param float $price The price in base currency to convert.
	 * @return float The converted price in the active currency, or original price if conversion is unavailable.
	 */
	public function convert( $price ) {
		if ( $this->enabled ) {
			return alg_convert_price(
				array(
					'price'         => $price,
					'currency'      => $this->current_currency_code,
					'currency_from' => $this->default_currency,
					'format_price'  => 'no',
				)
			);
		}
		return $price;
	}

	/**
	 * Revert price from the active currency back to the base currency.
	 *
	 * Uses manual currency reversion since wpwham doesn't provide a direct
	 * revert function.
	 *
	 * @param float $price The price in active currency to revert.
	 * @return float The reverted price in the base currency, or original price if conversion is unavailable.
	 */
	public function revert( $price ) {
		// Dont have any filter to revert the price.
		return product_addons()->manual_currency_reverted_price( $price );
	}
}
