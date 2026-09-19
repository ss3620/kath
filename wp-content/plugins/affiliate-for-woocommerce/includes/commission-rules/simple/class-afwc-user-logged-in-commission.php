<?php
/**
 * Class to create a commission rule for logged-in or guest users
 *
 * @package   affiliate-for-woocommerce/includes/commission-rules/simple/
 * @since     8.32.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\Boolean_Rule;

if ( ! class_exists( 'AFWC_User_Logged_In_Commission' ) && class_exists( Boolean_Rule::class ) ) {

	/**
	 * Class for user logged in commission rule
	 */
	class AFWC_User_Logged_In_Commission extends Boolean_Rule {

		/**
		 * Constructor
		 *
		 * @param array $args props.
		 */
		public function __construct( $args = array() ) {
			$this->set_context_key( 'user_logged_in' );
			$this->set_category( 'user' );
			$this->set_title(
				_x( 'User - Logged In', 'Title for user logged in commission rule', 'affiliate-for-woocommerce' )
			);
			$this->set_placeholder(
				_x( 'Select yes/no', 'Placeholder for user logged in commission rule', 'affiliate-for-woocommerce' )
			);
			parent::__construct( $args );
		}

		/**
		 * Method to return possible operators.
		 *
		 * @return array $possible_operators
		 */
		public function get_possible_operators() {
			return $this->possible_operators;
		}

		/**
		 * Method to return possible options.
		 *
		 * @return array Return the pre-defined options for the rule.
		 */
		public function get_options() {
			return array(
				'yes' => _x( 'Yes', 'Positive value title for user logged in commission rule', 'affiliate-for-woocommerce' ),
				'no'  => _x( 'No', 'Negative value title for user logged in commission rule', 'affiliate-for-woocommerce' ),
			);
		}

		/**
		 * Method to get context value for this rule.
		 *
		 * @param array $args The arguments for context.
		 *
		 * @return string Return 'yes' if the user is logged in, else 'no' as guest.
		 */
		public function get_context_value( $args = array() ) {
			if ( empty( $args['order'] ) || ! $args['order'] instanceof WC_Order || ! is_callable( array( $args['order'], 'get_customer_id' ) ) ) {
				return 'no';
			}
			return boolval( $args['order']->get_customer_id() ) ? 'yes' : 'no';
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
