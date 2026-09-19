<?php

/**
 * Handles the order.
 * 
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('FGF_Order_Handler')) {

	/**
	 * Class.
	 * 
	 * @since 1.0.0
	 */
	class FGF_Order_Handler {

		/**
		 * Class Initialization.
		 * 
		 * @since 1.0.0
		 */
		public static function init() {
			// May be add extra order line item meta when it is free gift product while creating order line item.
			add_action('woocommerce_checkout_create_order_line_item', array( __CLASS__, 'maybe_add_free_gift_line_item_meta' ), 10, 4);
			// Create master logs by short code checkout.
			add_action('woocommerce_checkout_update_order_meta', array( __CLASS__, 'maybe_create_master_logs' ), 1);
			// Create master logs by block checkout.
			add_action('woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'maybe_create_master_logs' ));
			// Register free gift related order item meta in hidden order item meta.
			add_action('woocommerce_hidden_order_itemmeta', array( __CLASS__, 'register_hidden_order_item_meta' ), 10, 2);
			// Unset removed automatic free gift products from session data by shortcode checkout.
			add_action('woocommerce_checkout_order_processed', array( __CLASS__, 'unset_removed_automatic_free_gifts_session_data' ), 10, 1);
			// Unset removed automatic free gift products from session data by block checkout.
			add_action('woocommerce_store_api_checkout_order_processed', array( __CLASS__, 'unset_removed_automatic_free_gifts_session_data' ), 10, 1);
		}

		/**
		 * May be add extra order line item meta when it is free gift product while creating order line item.
		 * 
		 * @since 1.0.0
		 * @param object $item
		 * @param string $cart_item_key
		 * @param array $values
		 * @param object $order
		 * @return nul
		 */
		public static function maybe_add_free_gift_line_item_meta( $item, $cart_item_key, $values, $order ) {
			if (!isset($values['fgf_gift_product'])) {
				return;
			}

			// Update order item meta.
			$item->add_meta_data('_fgf_gift_product', 'yes');
			$item->add_meta_data('_fgf_gift_rule_id', $values['fgf_gift_product']['rule_id']);
			$item->add_meta_data('_fgf_gift_rule_mode', $values['fgf_gift_product']['mode']);

			$type = get_option('fgf_settings_free_gift_cart_item_type_localization', __('Type', 'free-gifts-for-woocommerce'));
			$type_value = get_option('fgf_settings_free_gift_cart_item_type_value_localization', __('Free Product', 'free-gifts-for-woocommerce'));

			$item->add_meta_data($type, $type_value);
		}

		/**
		 * Create master logs based order item which is supported both block and short code checkout.
		 * 
		 * @since 1.0.0
		 * @param int/object $order_id
		 * @return null
		 */
		public static function maybe_create_master_logs( $order_id ) {
			$order_id = is_object($order_id) ? $order_id->get_id() : $order_id;
			$order = wc_get_order($order_id);
			if (!is_object($order)) {
				return;
			}

			$rule_ids = array();
			$product_details = array();
			foreach ($order->get_items() as $value) {

				if (!isset($value['fgf_gift_product'])) {
					continue;
				}

				$product_id = !empty($value['variation_id']) ? $value['variation_id'] : $value['product_id'];
				$product = wc_get_product($product_id);
				$rule_ids[] = $value['fgf_gift_rule_id'];

				// Prepare product details
				$product_details[] = array(
					'product_id' => $product_id,
					'product_name' => $product->get_name(),
					'product_price' => $product->get_price(),
					'quantity' => $value['quantity'],
					'rule_id' => $value['fgf_gift_rule_id'],
					'mode' => $value['fgf_gift_rule_mode'],
				);
			}

			if (!fgf_check_is_array($rule_ids)) {
				return;
			}

			$meta_data = array(
				'fgf_product_details' => $product_details,
				'fgf_rule_ids' => $rule_ids,
				'fgf_user_name' => $order->get_formatted_billing_full_name(),
				'fgf_user_email' => $order->get_billing_email(),
				'fgf_order_id' => $order_id,
			);

			// create a master log
			$master_log_id = fgf_create_new_master_log(
					$meta_data, array(
				'post_parent' => $order->get_customer_id(),
				'post_status' => 'fgf_automatic',
					)
			);

			// Set master log id in the order.
			// Improvement for HPOS compatibility.
			$order->add_meta_data('fgf_automatic_gift_product', $master_log_id);
			$order->save();

			// Update the rule usage count.
			self::update_rule_usage_count($rule_ids, $order);

			return $master_log_id;
		}

		/**
		 * Update the rule usage count.
		 * 
		 * @since 1.0.0
		 * @param array $rule_ids
		 * @param object $order
		 */
		public static function update_rule_usage_count( $rule_ids, &$order ) {
			$rule_ids = array_filter(array_unique($rule_ids));
			foreach ($rule_ids as $rule_id) {
				$rule = fgf_get_rule($rule_id);

				// Update the rule order count.
				fgf_update_rule_order_count($rule);

				// Update the rule user usage count.
				fgf_update_rule_user_usage_count($order->get_customer_id(), $rule);
			}
		}

		/**
		 * Register free gift related order item meta in hidden order item meta.
		 * 
		 * @since 1.0.0
		 * @param array $hidden_order_itemmeta
		 * @return array
		 */
		public static function register_hidden_order_item_meta( $hidden_order_itemmeta ) {
			return array_merge($hidden_order_itemmeta, array( '_fgf_gift_product', '_fgf_gift_rule_id', '_fgf_gift_rule_mode' ));
		}

		/**
		 * Unset removed automatic free gift products from session data
		 *
		 * @since 10.1.0
		 * @param int $order_id
		 * @return void
		 * */
		public static function unset_removed_automatic_free_gifts_session_data( $order_id ) {
			$order_id = is_object($order_id) ? $order_id->get_id() : $order_id;
			if ('shop_order' !== fgf_get_order_type($order_id)) {
				return;
			}

			// Unset session values.
			WC()->session->__unset('fgf_removed_automatic_free_gift_products');
		}
	}

	FGF_Order_Handler::init();
}
