<?php

/**
 * Compatibility Instances Class.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Compatibility_Instances')) {

	/**
	 * Class FGF_Compatibility_Instances
	 */
	class FGF_Compatibility_Instances {

		/**
		 * Compatibilities.
		 * 
		 * @var array
		 * */
		private static $compatibilities;

		/**
		 * Get Compatibilities.
		 * 
		 * @var array
		 */
		public static function instance() {
			if (is_null(self::$compatibilities)) {
				self::$compatibilities = self::load_compatibilities();
			}

			return self::$compatibilities;
		}

		/**
		 * Load all Compatibilities.
		 */
		public static function load_compatibilities() {
			if (!class_exists('FGF_Compatibility')) {
				include FGF_PLUGIN_PATH . '/inc/abstracts/abstract-fgf-compatibility.php';
			}

			$default_compatibility_classes = array(
				'wpml' => 'FGF_WPML_Compatibility',
				'paypal-payments' => 'FGF_Paypal_Payments_Compatibility',
				'woo-discount-rules' => 'FGF_WOO_Discount_Rules_Compatibility',
				'wc-product-price-based-country' => 'FGF_WC_Product_Price_Based_Country_Compatibility',
				'wc-multi-currency' => 'FGF_WC_Multi_Currency_Compatibility',
				'polylang' => 'FGF_Polylang_Compatibility',
				'wc-brands' => 'FGF_WC_Brands_Compatibility',
				'checkout-wc' => 'FGF_Checkout_WC_Compatibility',
				'woocommerce-b2b' => 'FGF_WooCommerce_B2B_Compatibility',
				'avada-fusion-builder' => 'FGF_Avada_Fusion_Builder_Compatibility',
				'wc-points-rewards' => 'FGF_WC_Points_Rewards_Compatibility',
				'wc-side-cart' => 'FGF_WC_Side_Cart_Compatibility',
				'funnelkit-cart' => 'FGF_Funnelkit_Cart_Compatibility',
				'funnelkit-builder' => 'FGF_Funnelkit_Builder_Compatibility',
				'wc-alia-currency-switcher' => 'FGF_WC_Alia_Currency_Switcher_Compatibility',
				'wc-smart-cart' => 'FGF_WC_Smart_Cart_Compatibility',
				'woocommerce-product-bundles' => 'FGF_Product_Bundles_Compatibility',
			);

			foreach ($default_compatibility_classes as $file_name => $compatibility_class) {

				// Include file.
				include 'class-' . $file_name . '.php';

				// Add compatibility.
				self::add_compatibility(new $compatibility_class());
			}
		}

		/**
		 * Add a Compatibility.
		 */
		public static function add_compatibility( $compatibility ) {
			self::$compatibilities[$compatibility->get_id()] = $compatibility;

			return new self();
		}

		/**
		 * Get compatibility by id.
		 * 
		 * @var Object
		 */
		public static function get_compatibility_by_id( $module_id ) {
			$compatibilities = self::instance();

			return isset($compatibilities[$compatibility_id]) ? $compatibilities[$compatibility_id] : false;
		}
	}

}
