<?php

/**
 * Handles the Manual Gift Order.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('FGF_Manual_Gift_Order_Handler')) {

	/*
	 * Class
	 */

	class FGF_Manual_Gift_Order_Handler {

		/**
		 * Create Free Gift Order
		 */
		public static function create_free_gift_order( $user_id, $product_ids, $order_status ) {
			// Create Order.
			$order_obj = wc_create_order(
					array(
						'status' => 'pending',
						'customer_id' => $user_id,
					)
					);

			// set billing address
			self::set_address_details($order_obj, $user_id, 'billing');

			// set shipping address
			self::set_address_details($order_obj, $user_id, 'shipping');

			// add products to orders
			if (fgf_check_is_array($product_ids)) {
				foreach ($product_ids as $product_id) {
					$order_obj->add_product(
							wc_get_product($product_id), '1', array(
						'total' => 0,
						'subtotal' => 0,
							)
					);
				}
			}

			// order item meta update
			$order_items = $order_obj->get_items();
			foreach ($order_items as $item_id => $item) {
				wc_add_order_item_meta($item_id, '_fgf_gift_product', 'yes');
				wc_add_order_item_meta($item_id, '_fgf_gift_rule_id', 'manual');
				wc_add_order_item_meta($item_id, __('Type', 'free-gifts-for-woocommerce'), __('Free Product', 'free-gifts-for-woocommerce'));
			}

			// save order object
			$order_obj->save();

			// Update Default Order status
			$order_obj->update_status($order_status);

			// create master log
			$master_log_id = self::create_master_logs($order_obj->get_id());

			// Set master log id in the order.
			// Improvement for HPOS compatibility.
			$order_obj->add_meta_data('fgf_manual_gift_product', $master_log_id);
			$order_obj->save();
			/**
			 * This hook is used to do extra action after manual gift order created.
			 * 
			 * @since 1.0
			 */
			do_action('fgf_manual_gift_order_created', $master_log_id, $order_obj->get_id());

			return $order_obj->get_id();
		}

		/**
		 * Set billing and shipping information
		 */
		public static function set_address_details( &$order_obj, $user_id, $type ) {

			$data = array(
				'first_name' => array( 'billing', 'shipping' ),
				'last_name' => array( 'billing', 'shipping' ),
				'company' => array( 'billing', 'shipping' ),
				'address_1' => array( 'billing', 'shipping' ),
				'address_2' => array( 'billing', 'shipping' ),
				'city' => array( 'billing', 'shipping' ),
				'postcode' => array( 'billing', 'shipping' ),
				'country' => array( 'billing', 'shipping' ),
				'state' => array( 'billing', 'shipping' ),
				'email' => array( 'billing' ),
				'phone' => array( 'billing' ),
					);

			// get address and shipping details
			$value = fgf_get_address($user_id, $type);

			foreach ($data as $key => $applicable_to) {

				if (is_callable(array( $order_obj, "set_{$type}_{$key}" ))) {
					$order_obj->{"set_{$type}_{$key}"}($value[$key]);
				}
			}
		}

		/**
		 * Create Master Log
		 */
		public static function create_master_logs( $order_id ) {
			$order = wc_get_order($order_id);
			$product_details = array();

			foreach ($order->get_items() as $key => $value) {
				$product_id = ( !empty($value['variation_id']) ) ? $value['variation_id'] : $value['product_id'];
				$product = wc_get_product($product_id);
				$product_details[] = array(
					'product_id' => $product_id,
					'product_name' => $product->get_name(),
					'product_price' => $product->get_price(),
					'quantity' => 1,
					'rule_id' => '',
					'mode' => 'admin',
						);
			}

			$meta_data = array(
				'fgf_product_details' => $product_details,
				'fgf_rule_ids' => '',
				'fgf_user_name' => $order->get_formatted_billing_full_name(),
				'fgf_user_email' => $order->get_billing_email(),
				'fgf_order_id' => $order_id,
					);

			return fgf_create_new_master_log(
					$meta_data, array(
				'post_parent' => $order->get_customer_id(),
				'post_status' => 'fgf_manual',
					)
					);
		}
	}

}
