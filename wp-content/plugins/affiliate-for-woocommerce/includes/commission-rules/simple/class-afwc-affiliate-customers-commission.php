<?php
/**
 * Class for affiliate customers count commissions rule
 *
 * @package     affiliate-for-woocommerce/includes/commission-rules/simple/
 * @since       8.49.0
 * @version     1.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\Number_Rule;

if ( ! class_exists( AFWC_Affiliate_Customers_Commission::class ) && class_exists( Number_Rule::class ) ) {

	/**
	 * Class AFWC_Affiliate_Customers_Commission
	 */
	class AFWC_Affiliate_Customers_Commission extends Number_Rule {

		/**
		 * Constructor.
		 *
		 * @param array $args Optional arguments.
		 */
		public function __construct( $args = array() ) {
			// Set the context key (unique identifier for this rule).
			$this->set_context_key( 'affiliate_customers' );

			// Set the category under which this rule should appear.
			$this->set_category( 'affiliate' );

			// Set the title displayed in the rule selection.
			$this->set_title(
				_x( 'Affiliate - Customers', 'Title for the affiliate customers rule', 'affiliate-for-woocommerce' )
			);

			// Set the placeholder text displayed for the rule input field.
			$this->set_placeholder(
				_x( "Enter customer's count", 'Placeholder for the affiliate customers rule', 'affiliate-for-woocommerce' )
			);

			// Set the input type for the rule input field.
			$this->set_input_props( array( 'type' => 'number' ) );

			parent::__construct( $args );
		}

		/**
		 * Define the possible operators for this rule.
		 *
		 * @return array
		 */
		public function get_possible_operators() {
			// Exclude the operators for this rule.
			$this->exclude_operators( array( 'in', 'nin', 'eq', 'neq' ) );

			// Re-merge the eq and neq operator to change the operator label for this rule.
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
		 * Get the context value for this rule (i.e., the number of  customers).
		 *
		 * @param array $args Context arguments.
		 *
		 * @throws Exception If any error during the process.
		 * @return int Number of customers for affiliate, or 0 if not applicable.
		 */
		public function get_context_value( $args = array() ) {
			// Ensure we have an affiliate object.
			if ( empty( $args['affiliate'] ) ) {
				return 0;
			}

			// Get the affiliate ID.
			$affiliate_id = ! empty( $args['affiliate']->ID ) ? intval( $args['affiliate']->ID ) : 0;
			if ( empty( $affiliate_id ) ) {
				return 0;
			}

			try {
				$affiliate_details = is_callable( array( 'AFWC_My_Account', 'get_instance' ) ) ? AFWC_My_Account::get_instance() : null;
				$customers_count   = is_callable( array( $affiliate_details, 'get_customers_data' ) ) ? $affiliate_details->get_customers_data( array( 'affiliate_id' => $affiliate_id ) ) : array();

				if ( is_null( $customers_count ) || ! is_array( $customers_count ) ) {
					throw new Exception( _x( 'Unable to fetch the total count of affiliate customers', 'Error message for affiliate customers count rule', 'affiliate-for-woocommerce' ) );
				}

				$customers_count = intval( ! empty( $customers_count ) && ! empty( $customers_count['customers'] ) ? $customers_count['customers'] : 0 );
			} catch ( Exception $e ) {
				$customers_count = 0;
				Affiliate_For_WooCommerce::log_error( __METHOD__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			}

			return $customers_count;
		}

		/**
		 * Set validated values to this rule.
		 * All products are considered validated if the rule is met.
		 *
		 * @param array  $values      The values to validate.
		 * @param object $context_obj Context object.
		 *
		 * @return void
		 */
		public function set_validated_values( $values = array(), $context_obj = null ) {
			// First, let the parent class handle its validations.
			parent::set_validated_values( $values );

			$context_args = is_callable( array( $context_obj, 'get_args' ) ) ? $context_obj->get_args() : array();

			if ( empty( $context_args ) || empty( $context_args['ordered_product_ids'] ) || ! is_array( $context_args['ordered_product_ids'] ) ) {
				return;
			}

			// Consider all products valid if this rule is satisfied.
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
