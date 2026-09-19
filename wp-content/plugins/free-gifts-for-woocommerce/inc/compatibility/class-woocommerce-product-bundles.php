<?php

/**
 * Compatibility - Product Bundles for WooCommerce Plugin.
 *
 * Compatibility last checked version of 8.1.1
 *
 * @link https://woocommerce.com/products/product-bundles/
 *
 * @since 11.6.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Product_Bundles_Compatibility')) {

	/**
	 * Class.
	 *
	 * @since 11.6.0
	 */
	class FGF_Product_Bundles_Compatibility extends FGF_Compatibility {

		/**
		 * Child item prices.
		 * 
		 * @since 11.6.0
		 */
		public static $child_item_prices=array();

		/**
		 * Class Constructor.
		 *
		 * @since 11.6.0
		 */
		public function __construct() {
			$this->id = 'product_bundles';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 *
		 * @since 11.6.0
		 * @return boolean
		 * */
		public function is_plugin_enabled() {
			return class_exists('WC_Bundles');
		}

		/**
		 * Front end action.
		 *
		 * @since 11.6.0
		 */
		public function frontend_action() {
			// Don't consider bundle child products as buy products.
			add_filter('fgf_is_valid_buy_product', array( __CLASS__, 'skip_bundle_child_products_as_buy_product' ), 10, 3);
			// Skip product validation for bundle child products.
			add_filter('fgf_skip_product_validation', array( __CLASS__, 'skip_bundle_child_products_validation' ), 10, 3);
			// Alter gift bundle products original cart price.
			add_filter('fgf_gift_product_original_cart_price', array( __CLASS__, 'alter_gift_bundle_products_original_cart_price' ), 10, 3);
			// Filter cart item price.
			add_filter( 'woocommerce_cart_item_price', array( $this, 'cart_item_price' ), 10, 3 );
			// Filter cart item subtotals.
			add_filter( 'woocommerce_cart_item_subtotal', array( $this, 'cart_item_subtotal' ), 10, 3 );
			// Set the price for gift bundle child item products as 0.
			add_action('woocommerce_before_calculate_totals', array( __CLASS__, 'set_price' ), 99999, 1);
		}

		/**
		 * Don't consider bundle child products as buy products.
		 *
		 * @since 11.6.0
		 * @param boolean $bool
		 * @param object $rule
		 * @param array $cart_item
		 * @return boolean
		 */
		public static function skip_bundle_child_products_as_buy_product( $bool, $rule, $cart_item ) {
			// Don't consider if the cart item is not a child item of bundle product.
			if (!wc_pb_maybe_is_bundled_cart_item($cart_item)) {
				return $bool;
			}

			return false;
		}

		/**
		 * Skip product validation for bundle child products.
		 *
		 * @since 11.6.0
		 * @param boolean $bool
		 * @param object $rule
		 * @param array $cart_item
		 * @return boolean
		 */
		public static function skip_bundle_child_products_validation( $bool, $rule, $cart_item ) {
			// Don't consider if the cart item is not a child item of bundle product.
			if (!wc_pb_maybe_is_bundled_cart_item($cart_item)) {
				return $bool;
			}

			return true;
		}

		/**
		 * Alter the gift bundle products original cart price.
		 *
		 * @since 11.6.0
		 * @param float $price
		 * @param array $cart_item
		 * @return float
		 */
		public static function alter_gift_bundle_products_original_cart_price( $price, $cart_item ) {
			// Don't consider if the cart item is not a bundle product.
			if (!wc_pb_is_bundle_container_cart_item($cart_item)) {
				return $price;
			}

			$aggregate_prices = WC_Product_Bundle::group_mode_has( $cart_item['data']->get_group_mode(), 'aggregated_prices' );

			if ( $aggregate_prices ) {
				$price = WC_PB_Display::instance()->get_container_cart_item_price_amount( $cart_item, 'price' ) ;
				$price=$price+wc_get_product($cart_item['product_id'])->get_price();
			} elseif ( empty( $cart_item['line_subtotal'] ) ) {
				$hide_container_zero_price = WC_Product_Bundle::group_mode_has( $cart_item['data']->get_group_mode(), 'component_multiselect' );
				$price                     = $hide_container_zero_price ? '' : $price;
			}

			return $price;
		}

		/**
		 * Modify the front-end price of bundled items and container items depending on their pricing setup.
		 *
		 * @since 11.6.0
		 * @param  double $price The price.
		 * @param  array  $cart_item The cart item.
		 * @param  string $cart_item_key The cart item key.
		 * @return string
		 */
		public function cart_item_price( $price, $cart_item, $cart_item_key ) {
			$bundle_container_item = wc_pb_get_bundled_cart_item_container( $cart_item );
			if ( !fgf_check_is_array($bundle_container_item) || !isset($bundle_container_item['fgf_gift_product'])) {
				return $price;
			}

			if (!self::$child_item_prices[$cart_item_key]) {
				return $price;
			}
			
			$formatted_price= '<del>' . fgf_price(self::$child_item_prices[$cart_item_key], false) . '</del> <ins>' . fgf_price(0, false) . '</ins>';
			if (false===stripos($price, '<span class="bundled_')) {
				return $formatted_price;
			} else {
				return '<span class="bundled_' . ( $this->is_cart_widget() ? 'mini_cart' : 'table' ) . '_item_subtotal">' . $formatted_price . '</span>';
			}
		}

		/**
		 * Modifies line item subtotals in the 'cart.php' & 'review-order.php' templates.
		 *
		 * @since 11.6.0
		 * @param  string $subtotal The subtotal.
		 * @param  array  $cart_item The cart item.
		 * @param  string $cart_item_key The cart item key.
		 * @return string
		 */
		public function cart_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {
			$bundle_container_item = wc_pb_get_bundled_cart_item_container( $cart_item );
			if ( !fgf_check_is_array($bundle_container_item) || !isset($bundle_container_item['fgf_gift_product'])) {
				return $subtotal;
			}

			if (!self::$child_item_prices[$cart_item_key]) {
				return $subtotal;
			}

			$subtotal_price= self::$child_item_prices[$cart_item_key]* $cart_item['quantity'];
			$formatted_subtotal ='<del>' . fgf_price($subtotal_price, false) . '</del> <ins>' . fgf_price(0, false) . '</ins>';
			if (false===stripos($subtotal, '<span class="bundled_')) {
				return $formatted_subtotal;
			} else {
				return '<span class="bundled_' . ( $this->is_cart_widget() ? 'mini_cart' : 'table' ) . '_item_subtotal">' . $formatted_subtotal . '</span>';
			}
		}

		/**
		 * Rendering cart widget?
		 *
		 * @since  11.6.0
		 * @return boolean
		 */
		protected function is_cart_widget() {
			return did_action( 'woocommerce_before_mini_cart' ) > did_action( 'woocommerce_after_mini_cart' );
		}

		/**
		 * Set the price for gift bundle child item products as 0.
		 *
		 * @since 11.6.0
		 * @param object $cart_object
		 */
		public static function set_price( $cart_object ) {
			// Return if cart object is not initialized.
			if (!is_object($cart_object)) {
				return;
			}

			foreach ($cart_object->cart_contents as $cart_item_key=>$cart_item) {
				// Don't consider if the cart item is not a child item of bundle product.
				if (!wc_pb_maybe_is_bundled_cart_item($cart_item)) {
					continue;
				}

				$container_item = wc_pb_get_bundled_cart_item_container( $cart_item );
				if (!fgf_check_is_array($container_item)) {
					continue;
				}

				if (!isset($container_item['fgf_gift_product'])) {
					continue;
				}

				self::$child_item_prices[$cart_item_key] = $cart_item['data']->get_price();

				$cart_item['data']->set_price(0);
			}
		}
	}
}
