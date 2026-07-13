<?php

/**
 * Compatibility - WOO Discount Rules.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_WOO_Discount_Rules_Compatibility')) {

	/**
	 * Class FGF_WOO_Discount_Rules_Compatibility.
	 */
	class FGF_WOO_Discount_Rules_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id = 'woo_discount_rules';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 *  @return bool
		 * */
		public function is_plugin_enabled() {

			return class_exists('Wdr\App\Controllers\ManageDiscount');
		}

		/**
		 * Frontend Action.
		 */
		public function frontend_action() {
			// Add the custom hook to discount rules.
			add_action('wp_loaded', array( $this, 'add_hooks' ), 100);
		}

		/**
		 * Add the custom hooks to discount rules.
		 */
		public function add_hooks() {
			FGF_Cart_Handler::init();
		}
	}

}
