<?php

/**
 * Compatibility - WooCommerce Multi Currency.
 * 
 * @since 8.5
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_WC_Multi_Currency_Compatibility')) {

	/**
	 * Class.
	 */
	class FGF_WC_Multi_Currency_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id = 'wc_multi_currency';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 *  @return bool
		 * */
		public function is_plugin_enabled() {
			return function_exists('wmc_get_price');
		}

		/**
		 * Frontend Action.
		 */
		public function frontend_action() {
			// Convert the price based on current currency. 
			add_filter('fgf_convert_price_to_base_currency', array( $this, 'convert_price' ), 10, 2);
		}

		/**
		 * Convert the price based on current currency.
		 * 
		 * @return float
		 */
		public static function convert_price( $price, $rule ) {
			if (!$price) {
				return $price;
			}

			return wmc_get_price($price);
		}
	}

}
