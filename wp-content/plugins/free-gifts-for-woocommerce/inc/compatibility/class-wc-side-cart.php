<?php

/**
 * Compatibility - WooCommerce Side Cart Premium.
 * 
 * @since 10.7.0
 * @tested up to 3.1.0
 * @pluginauthor XootiX
 * @authorurl http://xootix.com
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_WC_Side_Cart_Compatibility')) {

	/**
	 * Class.
	 * 
	 * @since 10.7.0
	 */
	class FGF_WC_Side_Cart_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 * 
		 * @since 10.7.0
		 */
		public function __construct() {
			$this->id = 'wc_side_cart';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 * @since 10.7.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return function_exists('xoo_wsc');
		}

		/**
		 * Frontend Action.
		 * 
		 * @since 10.7.0
		 */
		public function frontend_action() {
			// Reset the rules cached data.
			add_action('woocommerce_before_mini_cart', array( $this, 'reset_rules_cached_data' ), 5);
		}

		/**
		 * Reset the rules cached data.
		 * 
		 * Which will solve the automatic gifts not adding or removing issues in the side cart.
		 * 
		 * @since 10.7.0
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
