<?php

/**
 * Compatibility - WooCommerce Subscription Plugin.
 *
 * Compatibility last checked version of 5.0.1
 *
 * @link https://woocommerce.com/products/woocommerce-subscriptions/
 *
 * @since 9.8.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_WooCommerce_Subscription_Compatibility')) {

	/**
	 * Class.
	 *
	 * @since 9.8.0
	 */
	class FGF_WooCommerce_Subscription_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 *
		 * @since 9.8.0
		 */
		public function __construct() {
			$this->id = 'woocommerce_subscription';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 *
		 * @since 9.8.0
		 * @return boolean
		 * */
		public function is_plugin_enabled() {
			return class_exists('WC_Subscriptions');
		}

		/**
		 * Front end action.
		 *
		 * @since 9.8.0
		 */
		public function frontend_action() {
			// Set the extra properties for the subscription product as free gifts.
			add_action('woocommerce_get_cart_item_from_session', array( __CLASS__, 'set_subscription_product_property' ), 10, 3);
			// Alert the recurring subscription product price as free gift product which is used to set the next renewal price.
			add_filter('fgf_gift_product_price', array( __CLASS__, 'alter_recurring_subscription_product_price' ), 10, 3);
			// Alert the subscription product price as free gift product.
			add_filter('woocommerce_subscriptions_cart_get_price', array( __CLASS__, 'alter_subscription_product_price' ), 10, 3);
			// Remove the renewal order free gift product line item data.
			add_filter('woocommerce_order_again_cart_item_data', array( __CLASS__, 'remove_renewal_order_free_gift_line_item_data' ), 10, 3);
		}

		/**
		 * Set the extra properties for the subscription product as free gifts.
		 *
		 * @since 9.8.0
		 * @param array $session_data
		 * @param array $values
		 * @param string $key
		 * @return array
		 */
		public static function set_subscription_product_property( $session_data, $values, $key ) {
			// Don't consider if the product is not a free gift product.
			if (!isset($session_data['fgf_gift_product']) || !is_object($session_data['data'])) {
				return $session_data;
			}

			// Don't consider if the product is not a subscription product.
			if (!WC_Subscriptions_Product::is_subscription($session_data['data'])) {
				return $session_data;
			}

			$session_data['data']->add_meta_data('fgf_subscription_product', true);

			return $session_data;
		}

		/**
		 * Alert the recurring subscription product price as free gift product which is used to set the next renewal price.
		 *
		 * @since 9.8.0
		 * @param float $price
		 * @param string $cart_item_key
		 * @param array $cart_item
		 * @return float
		 */
		public static function alter_recurring_subscription_product_price( $price, $cart_item_key, $cart_item ) {
			// Don't consider if the calculation type is not recurring calculation.
			if ('recurring_total' != WC_Subscriptions_Cart::get_calculation_type()) {
				return $price;
			}

			// Don't consider if the product is not a subscription product.
			if (!WC_Subscriptions_Product::is_subscription($cart_item['data'])) {
				return $price;
			}

			// Set the original product price for renewal total.
			return wc_get_product($cart_item['data']->get_id())->get_price();
		}

		/**
		 * Alert the subscription product price as free gift product which will omit the price in the cart total.
		 *
		 * @since 9.8.0
		 * @param float $price
		 * @param string $product
		 * @return float
		 */
		public static function alter_subscription_product_price( $price, $product ) {
			// Don't consider if the calculation type is recurring calculation.
			if ('recurring_total' === WC_Subscriptions_Cart::get_calculation_type()) {
				return $price;
			}

			// Don't consider if the product is not a subscription product.
			if (!WC_Subscriptions_Product::is_subscription($product)) {
				return $price;
			}

			// Don't consider the product is not a free gift product.
			if (!$product->get_meta('fgf_subscription_product')) {
				return $price;
			}

			// Set the price as 0.
			return 0;
		}

		/**
		 * Remove the free gift product line item data when renewal the products.
		 *
		 * @since 9.8.0
		 * @param array $cart_item_data
		 * @param array $line_item
		 * @param object $subscription
		 * @return array
		 */
		public static function remove_renewal_order_free_gift_line_item_data( $cart_item_data, $line_item, $subscription ) {
			// Don't consider if the line item is not a free gift product.
			if (!isset($cart_item_data['subscription_renewal']['custom_line_item_meta']['_fgf_gift_product'])) {
				return $cart_item_data;
			}

			// Remove the parent order line item for the free gifts products.
			$cart_item_data['subscription_renewal']['custom_line_item_meta'] = array();

			return $cart_item_data;
		}
	}

}
