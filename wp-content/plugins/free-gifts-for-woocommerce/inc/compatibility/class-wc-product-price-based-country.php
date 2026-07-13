<?php

/**
 * Compatibility - WooCommerce Price Based on Country.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_WC_Product_Price_Based_Country_Compatibility')) {

	/**
	 * Class.
	 */
	class FGF_WC_Product_Price_Based_Country_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id = 'wc_product_price_based_country';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 *  @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists('WC_Product_Price_Based_Country');
		}

		/**
		 * Frontend Action.
		 */
		public function frontend_action() {
			// Convert the price based on current currency. 
			add_filter('fgf_convert_price_to_base_currency', array( $this, 'convert_price' ), 10, 2);
		}

		/**
		 * Convert the price based on country.
		 * 
		 * @return float
		 */
		public static function convert_price( $price, $rule ) {
			if (!$price) {
				return $price;
			}

			if (!is_object(wcpbc_the_zone())) {
				return $price;
			}

			return wcpbc_the_zone()->get_exchange_rate_price($price);
		}
	}

}
