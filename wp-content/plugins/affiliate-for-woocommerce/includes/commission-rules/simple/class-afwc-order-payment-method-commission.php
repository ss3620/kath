<?php
/**
 * Class for order's payment method commissions rule
 *
 * @package   affiliate-for-woocommerce/includes/commission-rules/simple/
 * @since     8.30.0
 * @version   1.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\String_Rule;

if ( ! class_exists( 'AFWC_Order_Payment_Method_Commission' ) && class_exists( String_Rule::class ) ) {

	/**
	 * Order's payment method commission rule class
	 */
	class AFWC_Order_Payment_Method_Commission extends String_Rule {

		/**
		 * Constructor
		 *
		 * @param array $args props.
		 */
		public function __construct( $args = array() ) {
			$this->set_context_key( 'order_payment_method' );
			$this->set_category( 'order' );
			$this->set_title(
				_x( 'Order - Payment Methods', 'Title for the order payment method commission rule', 'affiliate-for-woocommerce' )
			);
			$this->set_placeholder(
				_x( 'Select payment methods', 'Placeholder for the order payment method commission rule', 'affiliate-for-woocommerce' )
			);
			parent::__construct( $args );
		}

		/**
		 * Method to return possible operators.
		 *
		 * @return array Return the array of possible operators for the rule.
		 */
		public function get_possible_operators() {
			$this->exclude_operators( array( 'eq', 'neq' ) );
			return $this->possible_operators;
		}

		/**
		 * Method to return possible options.
		 *
		 * @return array Return the pre-defined options for the rule.
		 */
		public function get_options() {
			if ( ! function_exists( 'WC' ) ) {
				return array();
			}

			$payment_gateways_instance = WC()->payment_gateways;
			if ( ! is_object( $payment_gateways_instance ) || ! is_callable( array( $payment_gateways_instance, 'payment_gateways' ) ) ) {
				return array();
			}

			$payment_gateways = $payment_gateways_instance->payment_gateways();
			if ( empty( $payment_gateways ) || ! is_array( $payment_gateways ) ) {
				return array();
			}

			$payment_gateways_options = array();
			foreach ( $payment_gateways as $key => $payment_gateway ) {
				$method_name = ( is_callable( array( $payment_gateway, 'get_method_title' ) ) ? $payment_gateway->get_method_title() : '' );
				$title       = ( is_callable( array( $payment_gateway, 'get_title' ) ) ? $payment_gateway->get_title() : '' );

				$payment_gateways_options[ $key ] = ! empty( $title ) ? sprintf( '%1$s (%2$s)', $title, $method_name ) : $method_name;
			}

			return $payment_gateways_options;
		}

		/**
		 * Method to get context value for this rule.
		 *
		 * @param array $args The arguments for context.
		 *
		 * @return string Return the order's payment method.
		 */
		public function get_context_value( $args = array() ) {
			if ( empty( $args['order'] ) || ! $args['order'] instanceof WC_Order ) {
				return '';
			}

			if ( ! is_callable( array( $args['order'], 'get_payment_method' ) ) ) {
				return '';
			}

			return $args['order']->get_payment_method();
		}

		/**
		 * Method to set validated values to this rule.
		 *
		 * @param array  $values The values.
		 * @param object $context_obj context object.
		 *
		 * @return void
		 */
		public function set_validated_values( $values = array(), $context_obj = null ) {
			parent::set_validated_values( $values );

			$context_args = is_callable( array( $context_obj, 'get_args' ) ) ? $context_obj->get_args() : array();

			if ( empty( $context_args ) || empty( $context_args['ordered_product_ids'] ) || ! is_array( $context_args['ordered_product_ids'] ) ) {
				return;
			}

			// Add the validated products to additional rules key.
			parent::set_validated_values(
				array( 'additional_rules' => array( 'product_id' => $context_args['ordered_product_ids'] ) )
			);
		}
	}

}
