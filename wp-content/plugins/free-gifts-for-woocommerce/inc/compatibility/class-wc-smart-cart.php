<?php

/**
 * Compatibility - WooCommerce Smart Cart Premium.
 * 
 * @since 11.5.0
 * @tested up to 1.8.0
 * @pluginauthor WP1
 * @pluginurl https://woocommerce.com/products/woocommerce-smart-cart/
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_WC_Smart_Cart_Compatibility')) {

	/**
	 * Class.
	 * 
	 * @since 11.5.0
	 */
	class FGF_WC_Smart_Cart_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 * 
		 * @since 11.5.0
		 */
		public function __construct() {
			$this->id = 'wc_smart_cart';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 * @since 11.5.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return function_exists('run_woocommerce_smart_cart');
		}

		/**
		 * Frontend Action.
		 * 
		 * @since 11.5.0
		 */
		public function frontend_action() {

			// Add or remove the free gifts based on cart contents.
			add_action('woocommerce_ajax_added_to_cart', array( $this, 'handle_free_gifts' ), 999);
			add_action('woocommerce_cart_item_removed', array( $this, 'handle_free_gifts' ), 999);
			add_action('woocommerce_cart_item_set_quantity', array( $this, 'handle_free_gifts' ), 999);
		}

		/**
		 * Handle free gifts after products added in the cart.
		 * 
		 * @since 11.5.0
		 */
		public function handle_free_gifts() {
			// Reset the rules cache data.
			self::reset_rules_cached_data();

			FGF_Gift_Products_Handler::automatic_gift_product(false);
			FGF_Gift_Products_Handler::bogo_gift_product(false);
			FGF_Gift_Products_Handler::coupon_gift_product(false);
			FGF_Gift_Products_Handler::subtotal_gift_product(false);
			FGF_Gift_Products_Handler::remove_gift_products();
		}

		/**
		 * Reset the rules cached data.
		 * 
		 * Which will solve the automatic gifts not adding or removing issues in the smart cart.
		 * 
		 * @since 11.5.0
		 */
		public function reset_rules_cached_data() {
			// Reset to add automatic gifts in the cart.
			FGF_Gift_Products_Handler::$automatic_gifts_added = false;
			// Reset to remove automatic gifts in the cart.
			FGF_Gift_Products_Handler::$automatic_gifts_removed = false;
			// Reset cached rule data.
			FGF_Rule_Handler::reset();
		}
	}

}
