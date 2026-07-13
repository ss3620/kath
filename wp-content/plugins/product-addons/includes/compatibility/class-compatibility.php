<?php //phpcs:ignore
/**
 * Compatibility Action.
 *
 * @package PRAD\Compatibility
 * @since v.1.0.4
 */
namespace PRAD\Includes\Compatibility;

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility class.
 */
class Compatibility {
	/**
	 * Constructor
	 */
	public function __construct() {

		// WPC Compatibility.
		add_filter( 'woosb_cart_item_subtotal', array( $this, 'handle_woosb_cart_item_subtotal' ), 99, 3 );
		add_filter( 'woosb_cart_item_price', array( $this, 'handle_woosb_cart_item_price' ), 99, 3 );
		add_filter( 'woosb_bundles_price', array( $this, 'handle_woosb_bundles_price' ), 99, 3 );

		// PRAD single product page price.
		add_filter( 'prad_single_product_page_price', array( $this, 'handle_prad_single_product_page_price' ), 99, 1 );

		// PRAD cart/checkout page price.
		add_filter( 'prad_cart_checkout_page_price', array( $this, 'handle_prad_cart_checkout_page_price' ), 99, 1 );
		add_filter( 'prad_cart_checkout_page_percentage_price', array( $this, 'handle_prad_cart_checkout_page_percentage_price' ), 99, 1 );

		add_filter( 'prad_percentage_based_price_raw', array( $this, 'handle_prad_percentage_based_price_raw' ), 99, 2 );

		// Currency Reverted Price.
		add_filter( 'prad_get_currency_reverted_price', array( $this, 'handle_prad_get_currency_reverted_price' ), 99, 1 );

		// WP Rocket Cache.
		add_action( 'prad_handle_cache_on_save', array( $this, 'handle_cache_on_save' ) );

		// add body class in front end.
		add_filter( 'body_class', array( $this, 'add_body_class' ) );
	}

	/**
	 * Add Class to body
	 *
	 * @param array $classes Array of body classes.
	 * @return array
	 */
	public function add_body_class( $classes ) {

		$classes[] = 'prad-page';
		return $classes;
	}

	/**
	 * Handle PRAD Type Percentage price
	 *
	 * Retrieves the product price.
	 *
	 * @since 1.0.6
	 *
	 * @param string $product_id  Product Id.
	 * @param string $type        Type of price handling (default: 'revert').
	 *
	 * @return string Price.
	 */
	public function handle_prad_percentage_based_price_raw( $product_id, $type = 'revert' ) {
		$product      = wc_get_product( $product_id );
		$sale_price   = $product->get_sale_price();
		$return_price = $sale_price || '' !== $sale_price ? $sale_price : $product->get_regular_price();
		if ( 'revert' === $type ) {
			$return_price = apply_filters(
				'prad_get_currency_reverted_price',
				$return_price
			);
		}
		return $return_price;
	}

	/**
	 * Handle PRAD Cart/Checkout page Percentage price
	 *
	 * Retrieves the product price.
	 *
	 * @since 1.0.6
	 *
	 * @param string $product_id  Product Id.
	 *
	 * @return string Price.
	 */
	public function handle_prad_cart_checkout_page_percentage_price( $product_id ) {
		return $this->handle_prad_percentage_based_price_raw( $product_id, 'revert' );
	}
	/**
	 * Handle PRADCart/Checkout page price
	 *
	 * Retrieves the product price.
	 *
	 * @since 1.0.6
	 *
	 * @param string $product_id  Product Id.
	 *
	 * @return string Price.
	 */
	public function handle_prad_cart_checkout_page_price( $product_id ) {
		$product      = wc_get_product( $product_id );
		$sale_price   = $product->get_sale_price();
		$return_price = $sale_price || '' !== $sale_price ? $sale_price : $product->get_regular_price();
		$return_price = apply_filters(
			'prad_get_currency_reverted_price',
			$return_price
		);
		return $return_price;
	}

	/**
	 * Reverts the given price from the converted currency back to the original currency.
	 *
	 * This method is used to calculate the original price before currency conversion,
	 * based on the current conversion rate and any additional conversion charges.
	 *
	 * @param float|string $price The converted price to revert.
	 * @return float The original price before conversion. Returns 0 if conversion rate is invalid.
	 */
	public function handle_prad_get_currency_reverted_price( $price ) {
		return BaseCurrency::revert( floatval( $price ) );
	}

	/**
	 * Handle PRAD single product page price
	 *
	 * Retrieves the product price.
	 *
	 * @since 1.0.6
	 *
	 * @param string $product_id  Product Id.
	 *
	 * @return string Price.
	 */
	public function handle_prad_single_product_page_price( $product_id ) {
		$product    = wc_get_product( $product_id );
		$sale_price = $product->get_sale_price();
		$to_return  = $sale_price || '' !== $sale_price ? $sale_price : $product->get_regular_price();
		$to_return  = apply_filters(
			'prad_raw_tax_compitable_price',
			array(
				'product_id' => $product_id,
				'price'      => $to_return,
				'source'     => 'product_page',
			)
		);
		return $to_return;
	}

	/**
	 * Handles Total Price of WPC Product Bundles for WooCommerce cart subtotal.
	 * via the 'prad_selection' data in the cart item.
	 *
	 * This function checks if a custom price exists in the `prad_selection` array within the cart item
	 * and whether the `woosb_price` flag is set. If both conditions are met, it adds the custom price
	 * to the current bundles price.
	 *
	 * @param float $bundles_price The current calculated price of the bundle.
	 * @param array $cart_item     The cart item data, which may contain custom pricing.
	 *
	 * @return float The adjusted bundle price.
	 */
	public function handle_woosb_bundles_price( $bundles_price, $cart_item ) {
		if ( isset( $cart_item['prad_selection']['price'] ) && isset( $cart_item['woosb_price'] ) && $cart_item['woosb_price'] ) {
			$bundles_price = $bundles_price + floatval( $cart_item['prad_selection']['price'] );
		}
		return $bundles_price;
	}

	/**
	 * Handles item Price of WPC Product Bundles for WooCommerce cart item.
	 *
	 * This function allows customization of the displayed subtotal for bundled items.
	 *
	 * @param string $woosb_price The modified price to be returned.
	 * @param string $price       The original calculated price.
	 * @param array  $cart_item   The cart item array containing product and bundle details.
	 *
	 * @return string The filtered price value.
	 */
	public function handle_woosb_cart_item_price( $woosb_price, $price, $cart_item ) {
		if ( isset( $cart_item['prad_selection']['price'] ) && isset( $cart_item['woosb_ids'], $cart_item['woosb_price'], $cart_item['woosb_fixed_price'] ) && ! $cart_item['woosb_fixed_price'] ) {
			$woosb_price = wc_price( $cart_item['woosb_price'] + floatval( $cart_item['prad_selection']['price'] ) );
		}
		return $woosb_price;
	}
	/**
	 * Handles subtotal of WPC Product Bundles for WooCommerce cart item.
	 *
	 * This function allows customization of the displayed subtotal for bundled items.
	 *
	 * @param string $_subtotal The modified subtotal to be returned.
	 * @param string $subtotal  The original calculated subtotal.
	 * @param array  $cart_item The cart item array containing product and bundle details.
	 *
	 * @return string The filtered subtotal value.
	 */
	public function handle_woosb_cart_item_subtotal( $_subtotal, $subtotal, $cart_item ) {
		if ( isset( $cart_item['prad_selection']['price'] ) && isset( $cart_item['woosb_ids'], $cart_item['woosb_price'], $cart_item['woosb_fixed_price'] ) && ! $cart_item['woosb_fixed_price'] ) {
			$_subtotal = wc_price( ( $cart_item['woosb_price'] + floatval( $cart_item['prad_selection']['price'] ) ) * $cart_item['quantity'] );

			if ( wc_tax_enabled() && WC()->cart->display_prices_including_tax() && ! wc_prices_include_tax() ) {
				$_subtotal .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
			}
		}

		return $_subtotal;
	}

	/**
	 * Clears the LSCache and WP Rocket cache for the current domain.
	 *
	 * This method checks if the WP Rocket `rocket_clean_domain()` function exists,
	 * and if so, calls it to purge all cached content.
	 *
	 * Useful when dynamic content updates are not reflected for logged-out users
	 * due to caching.
	 *
	 * @return void
	 */
	public function handle_cache_on_save() {
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		} elseif ( class_exists( '\LiteSpeed\Purge' ) &&
			method_exists( '\LiteSpeed\Purge', 'purge_all_lscache' )
		) {
			\LiteSpeed\Purge::purge_all_lscache();
		}
	}
}
