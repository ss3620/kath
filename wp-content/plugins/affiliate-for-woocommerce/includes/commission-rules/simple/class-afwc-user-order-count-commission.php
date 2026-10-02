<?php
/**
 * Class for user order count commission rule.
 *
 * @package     affiliate-for-woocommerce/includes/commission-rules/simple/
 * @since       9.15.0
 * @version     1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\Number_Rule;

if ( ! class_exists( 'AFWC_User_Order_Count_Commission' ) && class_exists( Number_Rule::class ) ) {

	/**
	 * Class for user order count commission rule.
	 */
	class AFWC_User_Order_Count_Commission extends Number_Rule {

		/**
		 * Constructor.
		 *
		 * @param array $args Optional arguments.
		 */
		public function __construct( $args = array() ) {
			$this->set_context_key( 'user_order_count' );
			$this->set_category( 'user' );
			$this->set_title(
				_x( 'User - Order Count', 'Title for user order count commission rule', 'affiliate-for-woocommerce' )
			);
			$this->set_placeholder(
				_x( 'Enter order count', 'Placeholder for user order count commission rule', 'affiliate-for-woocommerce' )
			);
			$this->set_input_props( array( 'type' => 'number' ) );
			parent::__construct( $args );
		}

		/**
		 * Define the possible operators for this rule.
		 *
		 * Exclude in/nin (not meaningful for a numeric magnitude),
		 * relabel eq/neq to =/!= for readability.
		 *
		 * @return array
		 */
		public function get_possible_operators() {
			$this->exclude_operators( array( 'in', 'nin', 'eq', 'neq' ) );

			return array_merge(
				$this->possible_operators,
				array(
					array(
						'op'    => 'eq',
						'label' => '=',
						'type'  => 'single',
					),
					array(
						'op'    => 'neq',
						'label' => '!=',
						'type'  => 'single',
					),
				)
			);
		}

		/**
		 * Get the context value: the customer's lifetime qualifying order count.
		 *
		 * Qualifying = orders in a paid status (Processing / Completed by default,
		 * controlled by afwc_get_paid_order_status()).
		 *
		 * @param array $args Context arguments (order, affiliate, ordered_product_ids).
		 *
		 * @return int|null Qualifying order count for this customer, or null when the required information cannot be identified.
		 */
		public function get_context_value( $args = array() ) {
			if ( empty( $args['order'] ) || ! $args['order'] instanceof WC_Order ) {
				return null;
			}

			$order         = $args['order'];
			$customer_id   = is_callable( array( $order, 'get_customer_id' ) ) ? $order->get_customer_id() : 0;
			$billing_email = is_callable( array( $order, 'get_billing_email' ) ) ? $order->get_billing_email() : '';

			if ( empty( $customer_id ) && empty( $billing_email ) ) {
				return null;
			}

			$afwc_api = is_callable( array( 'AFWC_API', 'get_instance' ) ) ? AFWC_API::get_instance() : null;

			$qualifying_order_ids = is_callable( array( $afwc_api, 'get_orders_by_customer' ) )
				? $afwc_api->get_orders_by_customer(
					array(
						'customer_id'   => intval( $customer_id ),
						'billing_email' => $billing_email,
						'order_status'  => afwc_get_paid_order_status(),
					)
				)
				: array();

			/**
			 * Force-include the current order because the rule compares the order count
			 * relative to the current order, such as whether it is the Nth order or whether
			 * the count is above, below, or equal to the current order.
			 */
			$order_id = is_callable( array( $order, 'get_id' ) ) ? intval( $order->get_id() ) : 0;
			if ( ! empty( $order_id ) && ! in_array( $order_id, $qualifying_order_ids, true ) ) {
				$qualifying_order_ids[] = $order_id;
			}

			return count( $qualifying_order_ids );
		}

		/**
		 * Set validated values — required for non-product-scoped rules.
		 *
		 * Merges all ordered product IDs into additional_rules so the plan
		 * applies to every line item in the order (not zero).
		 *
		 * @param array  $values      The values to validate.
		 * @param object $context_obj Context object.
		 *
		 * @return void
		 */
		public function set_validated_values( $values = array(), $context_obj = null ) {
			parent::set_validated_values( $values );

			$context_args = is_callable( array( $context_obj, 'get_args' ) ) ? $context_obj->get_args() : array();

			if ( empty( $context_args['ordered_product_ids'] ) || ! is_array( $context_args['ordered_product_ids'] ) ) {
				return;
			}

			parent::set_validated_values(
				array(
					'additional_rules' => array(
						'product_id' => $context_args['ordered_product_ids'],
					),
				)
			);
		}
	}
}
