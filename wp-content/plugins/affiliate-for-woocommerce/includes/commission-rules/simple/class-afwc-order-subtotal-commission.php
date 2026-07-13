<?php
/**
 * Class for order's subtotal commissions rule.
 *
 * @package   affiliate-for-woocommerce/includes/commission-rules/simple/
 * @since     8.33.0
 * @version   1.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\Number_Rule;

if ( ! class_exists( 'AFWC_Order_Subtotal_Commission' ) && class_exists( Number_Rule::class ) ) {

	/**
	 * Order's subtotal commission rule class
	 */
	class AFWC_Order_Subtotal_Commission extends Number_Rule {

		/**
		 * Constructor
		 *
		 * @param array $args props.
		 */
		public function __construct( $args = array() ) {
			// Set the context key (unique identifier for this rule).
			$this->set_context_key( 'order_subtotal' );
			// Set the category under which this rule should appear.
			$this->set_category( 'order' );
			// Set the title displayed in the rule selection.
			$this->set_title(
				_x( 'Order - Subtotal', 'Title for the order subtotal commission rule', 'affiliate-for-woocommerce' )
			);
			// Set the placeholder text displayed for the rule input field.
			$this->set_placeholder(
				_x( 'Set subtotal', 'Placeholder for the order subtotal commission rule', 'affiliate-for-woocommerce' )
			);
			// Set the input type for the rule input field.
			$this->set_input_props(
				array(
					'type'            => 'number',
					'allowed_decimal' => 'yes',
				)
			);
			parent::__construct( $args );
		}

		/**
		 * Method to return possible operators.
		 *
		 * @return array Return the array of possible operators for the rule.
		 */
		public function get_possible_operators() {
			// Exclude the operators for this rule.
			$this->exclude_operators( array( 'in', 'nin', 'eq', 'neq' ) );

			// Re-merge the eq and neq operator to change the operator label/display for this rule.
			return array_merge(
				$this->possible_operators,
				array(
					array(
						'op'    => 'eq',
						'label' => '=', // Translation not needed for operators.
						'type'  => 'single',
					),
					array(
						'op'    => 'neq',
						'label' => '!=', // Translation not needed for operators.
						'type'  => 'single',
					),
				)
			);
		}

		/**
		 * Method to get context value for this rule.
		 *
		 * @param array $args The arguments for context.
		 *
		 * @return float|null Return the order's subtotal or null if order is invalid.
		 */
		public function get_context_value( $args = array() ) {
			$order_subtotal = null;

			if ( empty( $args['order'] ) || ! $args['order'] instanceof WC_Order ) {
				return $order_subtotal;
			}

			$order_subtotal = is_callable( array( $args['order'], 'get_subtotal' ) ) ? floatval( $args['order']->get_subtotal() ) : 0;

			// Convert subtotal to base currency for accurate comparison against rule threshold.
			$order_currency = is_callable( array( $args['order'], 'get_currency' ) ) ? $args['order']->get_currency() : '';
			$order_subtotal = afwc_convert_to_base_currency( $order_subtotal, $order_currency );

			return $order_subtotal;
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
