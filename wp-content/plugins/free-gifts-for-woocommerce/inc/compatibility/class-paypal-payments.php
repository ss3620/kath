<?php

/**
 * Paypal Payments Compatibility.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'FGF_Paypal_Payments_Compatibility' ) ) {

	/**
	 * Class.
	 */
	class FGF_Paypal_Payments_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id = 'paypal_payments' ;

			parent::__construct() ;
		}

		/**
		 * Is plugin enabled?.
		 * 
		 *  @return bool
		 * */
		public function is_plugin_enabled() {

			return class_exists( 'WooCommerce\PayPalCommerce\PluginModule' ) ;
		}

		/**
		 * Frontend Action
		 */
		public function frontend_action() {

			// Check if the redirection is valid after gift products are added.
			add_filter( 'fgf_is_valid_redirection', array( $this, 'is_valid_redirection' ), 10, 2 ) ;
		}

		/**
		 * Check if the redirection is valid after gift products are added.
		 * 
		 * @return bool
		 */
		public function is_valid_redirection( $bool, $action ) {
			// Return if the PayPal payment is not made a payment on the single product page.
			if ( ! isset( $_REQUEST[ 'wc-ajax' ] ) || 'ppc-create-order' != wc_clean( wp_unslash( $_REQUEST[ 'wc-ajax' ] ) ) ) {
				return $bool ;
			}

			return false ;
		}
	}

}
