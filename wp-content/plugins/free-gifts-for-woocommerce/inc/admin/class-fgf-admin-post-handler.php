<?php

/**
 * Admin - Post Handler.
 * 
 * @since 10.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Admin_Post_Handler')) {

	/**
	 * Class.
	 * 
	 * @since 10.0.0
	 */
	class FGF_Admin_Post_Handler {

		/**
		 * Class initialization.
		 * 
		 * @since 10.0.0
		 */
		public static function init() {
			// Add order item action buttons.
			add_action('woocommerce_order_item_add_action_buttons', array( __CLASS__, 'add_order_item_action_buttons' ), 10, 1);
			// May be create the master log for manual order creation.
			add_action('woocommerce_process_shop_order_meta', array( __CLASS__, 'maybe_create_master_log_for_manual_order' ), 100, 1);
		}

		/**
		 * May be create the master log for the manual order creation.
		 * 
		 * @since 10.0.0
		 * @param int $order_id
		 */
		public static function maybe_create_master_log_for_manual_order( $order_id ) {
			// Omit it if the order is not manual order creation.
			if (!get_transient('fgf_gifts_added_manually_for_' . $order_id)) {
				return;
			}

			$order = wc_get_order($order_id);
			if (!is_object($order) || 'auto-draft' === $order->get_status()) {
				return;
			}

			$product_details = array();
			foreach ($order->get_items() as $item) {
				if (!isset($item['fgf_gift_product'])) {
					continue;
				}

				$product_id = !empty($item['variation_id']) ? $item['variation_id'] : $item['product_id'];
				$product = wc_get_product($product_id);

				// Prepare product details
				$product_details[] = array(
					'product_id' => $product_id,
					'product_name' => $product->get_name(),
					'product_price' => $product->get_price(),
					'quantity' => $item['quantity'],
					'rule_id' => '',
					'mode' => 'admin',
				);
			}

			if (!fgf_check_is_array($product_details)) {
				return;
			}

			$meta_data = array(
				'fgf_product_details' => $product_details,
				'fgf_rule_ids' => '',
				'fgf_user_name' => $order->get_formatted_billing_full_name(),
				'fgf_user_email' => $order->get_billing_email(),
				'fgf_order_id' => $order_id,
			);

			// create a master log
			$master_log_id = fgf_create_new_master_log(
					$meta_data, array(
				'post_parent' => $order->get_customer_id(),
				'post_status' => 'fgf_manual',
					)
			);

			// Set master log id in the order.
			$order->add_meta_data('fgf_manual_gift_product', $master_log_id);
			$order->save();

			// Delete the transient after the master log created.
			delete_transient('fgf_gifts_added_manually_for_' . $order_id);
		}

		/**
		 * Add the order item action buttons.
		 * 
		 * @since 10.0.0
		 * @param object $order
		 */
		public static function add_order_item_action_buttons( $order ) {
			// Don't consider if the order is completed.
			if (!is_object($order) || !$order->is_editable()) {
				return;
			}

			include_once FGF_ABSPATH . 'inc/admin/menu/views/html-order-item-add-gift.php' ;
		}
	}

	FGF_Admin_Post_Handler::init();
}
