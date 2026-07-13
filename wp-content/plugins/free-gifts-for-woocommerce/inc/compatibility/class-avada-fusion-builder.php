<?php

/**
 * Compatibility - Avada Fusion Builder.
 * 
 * @since 9.4.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Avada_Fusion_Builder_Compatibility')) {

	/**
	 * Class.
	 */
	class FGF_Avada_Fusion_Builder_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id = 'fusion_builder';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 *  @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists('FusionBuilder');
		}

		/**
		 * Frontend Action.
		 */
		public function frontend_action() {
			// May be alter the cart criteria total based on selection.
			add_action('wp_head', array( $this, 'maybe_show_cart_notices' ));
			add_action('fgf_gift_display_cart_page_position', array( $this, 'alter_cart_page_display_position' ));
		}

		/**
		 * May be show the cart notices.
		 * 
		 * @since 9.4.0
		 */
		public function maybe_show_cart_notices() {
			if (!is_cart()) {
				return;
			}

			FGF_Notices_Handler::maybe_show_cart_notices();
			remove_action('woocommerce_before_cart', array( 'FGF_Notices_Handler', 'maybe_show_cart_notices' ), 5);
		}

		/**
		 * Alter the cart page display position.
		 * 
		 * @since 9.4.0
		 * @param array $positions
		 * @return array
		 */
		public function alter_cart_page_display_position( $positions ) {
			return array( '1000' => array( 'hook' => 'woocommerce_before_cart_totals', 'priority' => 5 ) );
		}
	}

}
