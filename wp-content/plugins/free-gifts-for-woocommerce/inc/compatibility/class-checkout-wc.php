<?php

/**
 * Checkout WC Compatibility.
 * 
 * @since 8.7
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Checkout_WC_Compatibility')) {

	/**
	 * Class.
	 */
	class FGF_Checkout_WC_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id = 'checkout_wc';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 *  @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists('Objectiv\Plugins\Checkout\Main');
		}

		/**
		 * Frontend Action
		 */
		public function frontend_action() {
			// Alter the gift products display position in the checkout page.
			add_filter('fgf_gift_display_checkout_page_position', array( $this, 'alter_checkou_gift_products_display_position' ), 10, 1);
		}

		/**
		 * Alter the gift products display position in the checkout page.
		 * 
		 * @return array
		 */
		public function alter_checkou_gift_products_display_position( $hooks ) {
			$hooks = array(
				'1' => array(
					'hook' => 'cfw_checkout_before_main_container',
					'priority' => 10,
				),
			);

			return $hooks;
		}
	}

}
