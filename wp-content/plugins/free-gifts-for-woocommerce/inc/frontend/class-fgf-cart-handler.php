<?php

/**
 *  Handles the cart.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
if (!class_exists('FGF_Cart_Handler')) {

	/**
	 * Class
	 */
	class FGF_Cart_Handler {

		/**
		 * Class Initialization.
		 */
		public static function init() {
			// May be validate the gift products add to cart.
			add_filter('woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_gift_products_add_to_cart' ), 10, 3);
			// May be add the custom cart item data.
			add_action('woocommerce_get_item_data', array( __CLASS__, 'maybe_add_custom_item_data' ), 10, 2);
			// Remove the shipping for gift product.
			add_filter('woocommerce_cart_shipping_packages', array( __CLASS__, 'alter_shipping_packages' ), 10, 1);
			// Set the price for gift product as 0.
			add_action('woocommerce_before_calculate_totals', array( __CLASS__, 'set_price' ), 9999, 1);
			// Alter the cart contents order.
			add_filter('woocommerce_cart_contents_changed', array( __CLASS__, 'alter_cart_contents_order' ), 10, 1);
			// Handles the cart item remove link.
			add_filter('woocommerce_cart_item_remove_link', array( __CLASS__, 'handles_cart_item_remove_link' ), 10, 2);
			// Set the cart item price html.
			add_filter('woocommerce_cart_item_price', array( __CLASS__, 'set_cart_item_price' ), 9999, 3);
			// Make the cart quantity html kis non editable.
			add_filter('woocommerce_cart_item_quantity', array( __CLASS__, 'set_cart_item_quantity' ), 9999, 2);
			// Set the cart item subtotal.
			add_filter('woocommerce_cart_item_subtotal', array( __CLASS__, 'set_cart_item_subtotal' ), 9999, 3);
			// Remove the gift products from the cart when cart is empty.
			add_action('woocommerce_cart_item_removed', array( __CLASS__, 'remove_gift_product_cart_empty' ), 10, 2);
			// Validating the free gifts coupons when applying in the cart.
			add_filter('woocommerce_coupon_is_valid', array( __CLASS__, 'validate_applying_coupon' ), 10, 3);
			// Consider automatic gift product cart removed item
			add_action('woocommerce_remove_cart_item', array( __CLASS__, 'consider_automatic_free_gift_product_before_cart_remove_item' ), 10, 2);
		}

		/**
		 * May be validate the gift products when adding to the cart.
		 *
		 * @since 9.2
		 * @param boolean $passed
		 * @param int $product_id
		 * @param int $qty
		 * @return boolean
		 */
		public static function validate_gift_products_add_to_cart( $passed, $product_id, $qty ) {
			$product = wc_get_product($product_id);
			if (!is_object($product)) {
				return $passed;
			}

			if ('yes' !== get_option('fgf_settings_restrict_gift_product_display')) {
				return $passed;
			}

			$free_products = fgf_get_rule_valid_gift_products();
			if (!fgf_check_is_array($free_products)) {
				return $passed;
			}

			if (!in_array($product_id, $free_products)) {
				return $passed;
			}

			return false;
		}

		/**
		 *  May be add the custom cart item data.
		 *
		 * @return array
		 */
		public static function maybe_add_custom_item_data( $item_data, $cart_item ) {
			if (!isset($cart_item['fgf_gift_product']) || !fgf_check_is_array($cart_item['fgf_gift_product'])) {
				return $item_data;
			}

			$type_label = get_option('fgf_settings_free_gift_cart_item_type_localization', __('Type', 'free-gifts-for-woocommerce'));
			$display_label = get_option('fgf_settings_free_gift_cart_item_type_value_localization', __('Free Product', 'free-gifts-for-woocommerce'));

			if (empty($type_label) && empty($display_label)) {
				return $item_data;
			}

			$item_data[] = array(
				'name' => $type_label,
				'display' => $display_label,
			);

			return $item_data;
		}

		/**
		 * Filter items needing shipping callback.
		 *
		 * @return bool
		 */
		public static function filter_items_needing_shipping( $item ) {
			// Return true,if the cart item is gift product.
			if (!isset($item['fgf_gift_product'])) {
				return true;
			}

			return false;
		}

		/**
		 * Get only items that need shipping.
		 *
		 * @return array
		 */
		public static function get_items_needing_shipping( $contents ) {
			return array_filter($contents, array( __CLASS__, 'filter_items_needing_shipping' ));
		}

		/**
		 * Remove the shipping for Gift product.
		 *
		 * @return array
		 */
		public static function alter_shipping_packages( $packages ) {
			// Return if the shipping is not allowed.
			if ('yes' == get_option('fgf_settings_allow_shipping_free_gift')) {
				return $packages;
			}

			// Return if the cart packages is empty.
			if (!fgf_check_is_array($packages)) {
				return $packages;
			}

			foreach ($packages as $package_key => $package) {
				if (!isset($package['contents']) || !isset($package['contents_cost'])) {
					continue;
				}

				// Get items needing shipping.
				$items_needing_shipping = self::get_items_needing_shipping($packages[$package_key]['contents']);

				// Alter shipping package.
				$packages[$package_key]['contents'] = $items_needing_shipping;
				$packages[$package_key]['contents_cost'] = array_sum(wp_list_pluck($items_needing_shipping, 'line_total'));
			}

			return $packages;
		}

		/**
		 * Set the custom price for gift product.
		 *
		 * @return void
		 */
		public static function set_price( $cart_object ) {
			// Return if cart object is not initialized.
			if (!is_object($cart_object)) {
				return;
			}

			foreach ($cart_object->cart_contents as $key => $value) {
				if (!isset($value['fgf_gift_product'])) {
					continue;
				}
				/**
				 * This hook is used to alter the gift product price.
				 *
				 * @since 1.0
				 */
				$price = apply_filters('fgf_gift_product_price', $value['fgf_gift_product']['price'], $key, $value);

				$value['data']->set_price($price);
			}
		}

		/**
		 * Alter the cart contents order.
		 *
		 * @return array
		 * */
		public static function alter_cart_contents_order( $cart_contents ) {
			// Return the same cart content if contents is empty.
			if (!fgf_check_is_array($cart_contents)) {
				return $cart_contents;
			}

			// Return the same cart content if display cart order is disabled.
			if ('2' != get_option('fgf_settings_gift_product_cart_display_order')) {
				return $cart_contents;
			}

			$other_cart_contents = array();
			$free_gift_cart_contents = array();

			foreach ($cart_contents as $key => $values) {
				if (isset($values['fgf_gift_product'])) {
					$free_gift_cart_contents[$key] = $values;
				} else {
					$other_cart_contents[$key] = $values;
				}
			}

			return array_merge($other_cart_contents, $free_gift_cart_contents);
		}

		/**
		 * Handles the cart item remove link.
		 *
		 * @return string
		 */
		public static function handles_cart_item_remove_link( $remove_link, $cart_item_key ) {
			if (fgf_show_automatic_free_gift_product_cart_item_remove_link()) {
				return $remove_link;
			}

			// Return if cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return $remove_link;
			}

			$cart_items = WC()->cart->get_cart();

			// Check if the product is a gift product.
			if (!isset($cart_items[$cart_item_key]['fgf_gift_product']['mode'])) {
				return $remove_link;
			}

			/**
			 * This hook is used to validate the cart item remove link.
			 *
			 * @since 1.0
			 */
			if (apply_filters('fgf_validate_cart_item_remove_link', false, $cart_item_key, $cart_items)) {
				return $remove_link;
			}

			// Return link if the product is a manual gift product.
			if (in_array($cart_items[$cart_item_key]['fgf_gift_product']['mode'], fgf_get_manual_rule_types())) {
				return $remove_link;
			}

			return '';
		}

		/**
		 * Set the cart item price html.
		 *
		 * @return mixed
		 */
		public static function set_cart_item_price( $price, $cart_item, $cart_item_key ) {

			// check if product is a gift product
			if (!isset($cart_item['fgf_gift_product'])) {
				return $price;
			}

			return self::get_gift_product_price($price, $cart_item);
		}

		/**
		 * Make the cart quantity as non editable in the cart page.
		 *
		 * @return string
		 */
		public static function set_cart_item_quantity( $quantity, $cart_item_key ) {
			// Return if cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return $quantity;
			}

			$cart_items = WC()->cart->get_cart();

			// check if product is a gift product
			if (!isset($cart_items[$cart_item_key]['fgf_gift_product'])) {
				return $quantity;
			}

			return $cart_items[$cart_item_key]['quantity'];
		}

		/**
		 * Set the cart item subtotal.
		 *
		 * @return string
		 */
		public static function set_cart_item_subtotal( $price, $cart_item, $cart_item_key ) {

			// Check if the product is a gift product.
			if (!isset($cart_item['fgf_gift_product'])) {
				return $price;
			}

			return self::get_gift_product_price($price, $cart_item, true);
		}

		/**
		 * Get the gift product price.
		 *
		 * @return string
		 * */
		public static function get_gift_product_price( $price, $cart_item, $multiply_qty = false ) {

			// Check if the cart item is a gift product.
			if (!isset($cart_item['fgf_gift_product'])) {
				return $price;
			}

			$product_id = !empty($cart_item['variation_id']) ? $cart_item['variation_id'] : $cart_item['product_id'];
			$product = wc_get_product($product_id);
			if (!is_object($product)) {
				return $price;
			}
			
			$price_display_type = get_option('fgf_settings_gift_product_price_display_type');
			if (fgf_get_free_gift_product_price() || '2' == $price_display_type) {
				$product_price = ( $multiply_qty ) ? (float) $cart_item['quantity'] * (float) $product->get_price() : $product->get_price();
				$current_product_price=( $multiply_qty ) ? (float) $cart_item['quantity'] * (float) $cart_item['fgf_gift_product']['price'] : $cart_item['fgf_gift_product']['price'];
				/**
				 * This hook is used to alter the gift product original cart price.
				 * 
				 * @since 11.6.0
				 */
				$product_price=apply_filters('fgf_gift_product_original_cart_price', $product_price, $cart_item);
				$display_price = '<del>' . fgf_price($product_price, false) . '</del> <ins>' . fgf_price($current_product_price, false) . '</ins>';
			} else {
				$display_price = __('Free', 'free-gifts-for-woocommerce');
			}

			return $display_price;
		}

		/**
		 * Remove the gift products from cart when cart is empty.
		 *
		 * @return void
		 * */
		public static function remove_gift_product_cart_empty( $removed_cart_item_key, $cart ) {
			// Return if the cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return;
			}

			// Return if the cart is empty.
			if (WC()->cart->get_cart_contents_count() == 0) {
				return;
			}

			$free_products_count = fgf_get_free_gift_products_count_in_cart();
			$cart_items_count = WC()->cart->get_cart_contents_count() - $free_products_count;

			// Return if the gift products is exists.
			if ($cart_items_count) {
				return;
			}

			// Remove all gift products from the cart.
			WC()->cart->empty_cart();

			// Error Notice.
			fgf_add_wc_notice(get_option('fgf_settings_free_gift_error_message'), 'notice');
		}

		/**
		 * Validating the free gift coupons when applying in the cart.
		 *
		 * @return bool
		 */
		public static function validate_applying_coupon( $bool, $coupon, $discount ) {
			if (!is_object(WC()->cart)) {
				return $bool;
			}

			// Return If the current coupon is not free gifts coupon.
			if ('fgf_free_gift' != $coupon->get_discount_type()) {
				return $bool;
			}

			$applied_coupons = WC()->cart->get_applied_coupons();
			// Return if the applied coupon in the cart is current coupon.
			if (!empty($applied_coupons) && in_array($coupon->get_code(), $applied_coupons)) {
				return $bool;
			}

			// Restrict the adding gift product if the gift products per order count exists.
			if (!FGF_Rule_Handler::check_per_order_count_exists()) {
				return $bool;
			}

			throw new Exception(esc_html(get_option('fgf_settings_free_gift_coupon_restriction_error_message')));
		}

		/**
		 * Consider automatic free gift product before cart remove item
		 *
		 * @since 10.1.0
		 * @param string $cart_item_key
		 * @param object $cart
		 * @return void
		 */
		public static function consider_automatic_free_gift_product_before_cart_remove_item( $cart_item_key, $cart ) {
			if (!is_object(WC()->cart)) {
				return;
			}

			if (!fgf_show_automatic_free_gift_product_cart_item_remove_link() || FGF_Gift_Products_Handler::$free_gift_automatic_removed_cart_item) {
				return;
			}

			$remove_item = WC()->cart->get_cart_item($cart_item_key);
			if (!isset($remove_item['fgf_gift_product'])) {
				return;
			}

			$rule_id = $remove_item['fgf_gift_product']['rule_id'];
			$session_gift_products = fgf_get_removed_automatic_free_gift_products_from_session();

			if (isset($session_gift_products[$rule_id]) && fgf_check_is_array($session_gift_products[$rule_id])) {
				$session_gift_products[$rule_id][] = $remove_item['fgf_gift_product']['product_id'];
			} else {
				$session_gift_products[$rule_id] = array( $remove_item['fgf_gift_product']['product_id'] );
			}

			WC()->session->set('fgf_removed_automatic_free_gift_products', $session_gift_products);
		}
	}

	FGF_Cart_Handler::init();
}
