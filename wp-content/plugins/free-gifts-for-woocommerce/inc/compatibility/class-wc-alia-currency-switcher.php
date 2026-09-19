<?php

/**
 * Compatibility - WooCommerce Alia Currency Switcher plugin.
 * 
 * Plugin tested up to : 5.1.4.240307
 * Plugin author : realmag777
 * Plugin URL : https://aelia.co/shop/currency-switcher-woocommerce/
 * 
 * @since 11.1.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_WC_Alia_Currency_Switcher_Compatibility')) {

	/**
	 * Class.
	 * 
	 * @since 11.1.0
	 */
	class FGF_WC_Alia_Currency_Switcher_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 * 
		 * @since 11.1.0
		 */
		public function __construct() {
			$this->id = 'wc_alia_currency_switcher';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 * @since 11.1.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists('Aelia\WC\CurrencySwitcher\WC_Aelia_CurrencySwitcher');
		}

		/**
		 * Front end action.
		 * 
		 * @since 11.1.0
		 */
		public function frontend_action() {
			// Convert the price based on current currency. 
			add_filter('fgf_convert_price_to_base_currency', array( $this, 'convert_price' ), 10, 2);
		}

		/**
		 * Convert the rule maximum cart subtotal based on current currency.
		 * 
		 * @since 11.1.0
		 * @return float
		 */
		public static function convert_price( $price, $rule ) {
			if (!$price) {
				return $price;
			}

			global $GLOBALS;
			if (!isset($GLOBALS[Aelia\WC\CurrencySwitcher\WC_Aelia_CurrencySwitcher::$plugin_slug])) {
				return $price;
			}

			$alia_currency_switcher = $GLOBALS[Aelia\WC\CurrencySwitcher\WC_Aelia_CurrencySwitcher::$plugin_slug];
			if (!is_object($alia_currency_switcher)) {
				return $price;
			}

			return $alia_currency_switcher->convert($price, $alia_currency_switcher->base_currency(), $alia_currency_switcher->get_selected_currency());
		}
	}

}
