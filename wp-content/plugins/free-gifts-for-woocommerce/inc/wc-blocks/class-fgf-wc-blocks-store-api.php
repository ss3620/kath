<?php

/**
 * WooCommerce Blocks Store API.
 *
 * @since 11.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema;
use Automattic\WooCommerce\StoreApi\Schemas\V1\CartItemSchema;

/**
 * Class for extend store API of cart/checkout.
 *
 * @since 11.0.0
 */
class FGF_WC_Blocks_Store_API {

	/**
	 * Plugin Identifier, unique to each plugin.
	 *
	 * @since 11.0.0
	 * @var string
	 */
	const IDENTIFIER = 'fgf-free-gifts';

	/**
	 * Bootstrap.
	 * 
	 * @since 11.0.0
	 */
	public static function init() {
		// Extend StoreAPI.
		self::extend_store();

		// Filter topup cart item quantity.
		add_filter('woocommerce_store_api_product_quantity_minimum', array( __CLASS__, 'filter_cart_item_qty' ), 10, 3);
		add_filter('woocommerce_store_api_product_quantity_maximum', array( __CLASS__, 'filter_cart_item_qty' ), 10, 3);

		// Handles the free gifts products adding and removing from the cart by automatic.
		add_action('woocommerce_check_cart_items', array( __CLASS__, 'handles_free_gifts' ));
	}

	/**
	 * Register extensibility points.
	 * 
	 * @since 11.0.0
	 */
	protected static function extend_store() {
		if (function_exists('woocommerce_store_api_register_endpoint_data')) {
			woocommerce_store_api_register_endpoint_data(
					array(
						'endpoint' => CartSchema::IDENTIFIER,
						'namespace' => self::IDENTIFIER,
						'data_callback' => array( 'FGF_WC_Blocks_Store_API', 'extend_cart_data' ),
						'schema_callback' => array( 'FGF_WC_Blocks_Store_API', 'extend_cart_schema' ),
						'schema_type' => ARRAY_A,
					)
			);

			woocommerce_store_api_register_endpoint_data(
					array(
						'endpoint' => CartItemSchema::IDENTIFIER,
						'namespace' => self::IDENTIFIER,
						'data_callback' => array( 'FGF_WC_Blocks_Store_API', 'extend_cart_item_data' ),
						'schema_callback' => array( 'FGF_WC_Blocks_Store_API', 'extend_cart_schema' ),
						'schema_type' => ARRAY_A,
					)
			);
		}

		if (function_exists('woocommerce_store_api_register_update_callback')) {
			woocommerce_store_api_register_update_callback(
					array(
						'namespace' => self::IDENTIFIER,
						'callback' => array( 'FGF_WC_Blocks_Store_API', 'rest_handle_endpoint' ),
					)
			);
		}
	}

	/**
	 * Register free gifts schema in the cart schema.
	 * 
	 * @since 11.0.0
	 * @return array
	 */
	public static function extend_cart_schema() {
		return array();
	}

	/**
	 * Register free gifts data in the cart API.
	 * 
	 * @since 11.0.0
	 * @return array
	 */
	public static function extend_cart_data() {
		/**
		 * This hook is used to alter the extend cart data.
		 * 
		 * @since 11.0.0
		 */
		return apply_filters('fgf_extend_cart_data', array(
			'cart_notices' => fgf_get_store_api_cart_notices(),
			'checkout_notices' => fgf_get_store_api_checkout_notices(),
			'snackbar_notices' => fgf_get_store_api_snackbar_notices(),
			'cart_gifts_html' => fgf_get_cart_free_gifts_html(),
			'checkout_gifts_html' => fgf_get_checkout_free_gifts_html(),
			'cart_progress_bar_html' => fgf_get_cart_progress_bar_html(),
			'checkout_progress_bar_html' => fgf_get_checkout_progress_bar_html(),
		));
	}

	/**
	 * Register free gifts data in the cart item API.
	 * 
	 * @since 11.0.0
	 * @param array $cart_item
	 * @return array
	 */
	public static function extend_cart_item_data( $cart_item ) {
		// Omit it if the cart item is not free gift.
		if (!isset($cart_item['fgf_gift_product'])) {
			return array();
		}

		$extend_cart_item = array(
			'item_price' => self::get_cart_item_price($cart_item),
			'show_remove_link' => self::can_show_cart_item_link($cart_item),
		);

		/**
		 * This hook is used to alter the extend cart item data.
		 * 
		 * @since 11.0.0
		 */
		return apply_filters('fgf_extend_cart_item_data', $extend_cart_item, $cart_item);
	}

	/**
	 * Handles free gifts endpoints.
	 * 
	 * @since 11.0.0
	 * @param array $args
	 */
	public static function rest_handle_endpoint( $args ) {
	}

	/**
	 * Get the cart item price.
	 * 
	 * @since 11.0.0
	 * @param array $cart_item
	 * @return string
	 */
	private static function get_cart_item_price( $cart_item ) {
		// Don't alter the cart item price when free products having price.
		if (fgf_get_free_gift_product_price()) {
			return null;
		}

		return array(
			'type' => '2' !== get_option('fgf_settings_gift_product_price_display_type') ? 'label' : 'price',
			'discounted_price' => fgf_store_api_format_price($cart_item['fgf_gift_product']['price']),
			'label' => __('Free', 'free-gifts-for-woocommerce'),
		);
	}

	/**
	 * Can show cart item remove link?
	 * 
	 * @since 11.0.0
	 * @param array $cart_item
	 * @return bool
	 */
	private static function can_show_cart_item_link( $cart_item ) {
		if (fgf_show_automatic_free_gift_product_cart_item_remove_link()) {
			return true;
		}

		/**
		 * This hook is used to validate the cart item remove link.
		 *
		 * @since 1.0
		 */
		if (apply_filters('fgf_validate_cart_item_remove_link', false, $cart_item['key'], $cart_item)) {
			return true;
		}

		// Show remove link for manual and some rule types.
		if (in_array($cart_item['fgf_gift_product']['mode'], fgf_get_manual_rule_types())) {
			return true;
		}

		return false;
	}

	/**
	 * Filter the free products cart item quantity.
	 * 
	 * @since 11.0.0
	 * @param int $value
	 * @param object $product
	 * @param array $cart_item
	 * @return int
	 */
	public static function filter_cart_item_qty( $value, $product, $cart_item ) {
		// Check if the current product is a free product.
		if (!isset($cart_item['fgf_gift_product'])) {
			return $value;
		}

		return $cart_item['quantity'];
	}

	/**
	 * Handles the free gifts products adding and removing from the cart by automatic based on cart contents.
	 * 
	 * @since 11.0.0
	 */
	public static function handles_free_gifts() {
		// Return if the request does not call vai cart/checkout block Store API.
		if (!isset($GLOBALS['wp']->query_vars['rest_route']) || false === strpos($GLOBALS['wp']->query_vars['rest_route'], '/wc/store/v1')) {
			return;
		}

		self::maybe_add_gift_products_to_cart_automatically();
		FGF_Gift_Products_Handler::remove_gift_products();
	}

	/**
	 * May be add valid gift products to the cart by automatically based on cart contents.
	 * 
	 * @since 11.0.0
	 */
	private static function maybe_add_gift_products_to_cart_automatically() {
		// Don't add automatic gift products when it is already executed.
		if (FGF_Gift_Products_Handler::$automatic_gifts_added) {
			return;
		}

		FGF_Gift_Products_Handler::automatic_gift_product(false);
		FGF_Gift_Products_Handler::bogo_gift_product(false);
		FGF_Gift_Products_Handler::coupon_gift_product(false);
		FGF_Gift_Products_Handler::subtotal_gift_product(false);
		FGF_Gift_Products_Handler::bulk_pricing_gift_product(false);
		FGF_Gift_Products_Handler::bulk_quantity_gift_product(false);
		FGF_Gift_Products_Handler::cheapest_gift_product(false);

		FGF_Gift_Products_Handler::$automatic_gifts_added = true;
	}
}
