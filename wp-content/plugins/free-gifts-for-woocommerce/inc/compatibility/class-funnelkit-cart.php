<?php

/**
 * Compatibility - FunnelKit Cart for WooCommerce
 * 
 * @since 10.7.0
 * @tested up to 1.2.0
 * @pluginauthor Funnel Kit
 * @authorurl https://funnelkit.com
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Funnelkit_Cart_Compatibility')) {

	/**
	 * Class.
	 * 
	 * @since 10.7.0
	 */
	class FGF_Funnelkit_Cart_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 * 
		 * @since 10.7.0
		 */
		public function __construct() {
			$this->id = 'funnelkit_cart';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 * @since 10.7.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists('FKCart\Plugin');
		}

		/**
		 * Frontend Action.
		 * 
		 * @since 10.7.0
		 */
		public function frontend_action() {
			// Add or remove the free gifts based on cart contents.
			add_action('woocommerce_ajax_added_to_cart', array( $this, 'handle_free_gifts' ), 999);
			// Render the free gifts products in the funnel kit slider cart.
			add_action('fkcart_after_header', array( 'FGF_Gift_Products_Handler', 'render_gift_products_cart_page' ), 10);
			//Add custom CSS for gift products in the slider cart.
			add_action('fgf_custom_css', array( $this, 'add_custom_css' ));
		}

		/**
		 * Handle free gifts after products added in the cart.
		 * 
		 * @since 10.7.0
		 */
		public function handle_free_gifts() {
			// Reset the rules cache data.
			self::reset_rules_cached_data();

			FGF_Gift_Products_Handler::automatic_gift_product(false);
			FGF_Gift_Products_Handler::bogo_gift_product(false);
			FGF_Gift_Products_Handler::coupon_gift_product(false);
			FGF_Gift_Products_Handler::subtotal_gift_product(false);
			FGF_Gift_Products_Handler::bulk_pricing_gift_product(false);
			FGF_Gift_Products_Handler::bulk_quantity_gift_product(false);
			FGF_Gift_Products_Handler::cheapest_gift_product(false);
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

		/**
		 * Add custom CSS for gift products in the slider cart.
		 * 
		 * @since 11.3.0
		 * @param string $custom_css
		 * @return string
		 */
		public static function add_custom_css( $custom_css ) {
			$custom_css = '.fkcart-modal-container .fgf_gift_products_wrapper h3{'
					. 'font-size:18px !important;'
					. 'font-weight:600 !important;'
					. 'margin-left:12px !important;'
					. 'margin-top:12px !important;}'
					. '.fkcart-modal-container .fgf-gift-products-content{'
					. 'margin-left:12px !important;'
					. 'width:95% !important;}';

			return $custom_css;
		}
	}

}
