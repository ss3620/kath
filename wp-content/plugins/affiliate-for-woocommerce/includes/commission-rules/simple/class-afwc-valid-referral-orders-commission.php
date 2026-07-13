<?php
/**
 * Class for valid referral orders commissions rule
 *
 * @package     affiliate-for-woocommerce/includes/commission-rules/simple/
 * @since       8.31.0
 * @version     1.0.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\Number_Rule;

if ( ! class_exists( AFWC_Valid_Referral_Orders_Commission::class ) && class_exists( Number_Rule::class ) ) {

	/**
	 * Class AFWC_Valid_Referral_Orders_Commission
	 */
	class AFWC_Valid_Referral_Orders_Commission extends Number_Rule {

		/**
		 * Constructor.
		 *
		 * @param array $args Optional arguments.
		 */
		public function __construct( $args = array() ) {
			// Set the context key (unique identifier for this rule).
			$this->set_context_key( 'valid_referral_orders' );

			// Set the category under which this rule should appear.
			$this->set_category( 'affiliate' );

			// Set the title displayed in the rule selection.
			$this->set_title(
				_x( 'Affiliate - Valid Referral Orders', 'Title for the affiliate valid referral orders rule', 'affiliate-for-woocommerce' )
			);

			// Set the placeholder text displayed for the rule input field.
			$this->set_placeholder(
				_x( 'Enter referral order count', 'Placeholder for the affiliate valid referral orders rule', 'affiliate-for-woocommerce' )
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
		 * Get the context value for this rule (i.e., the number of referral orders).
		 *
		 * @param array $args Context arguments.
		 *
		 * @throws Exception If any error during the process.
		 * @return int Number of referral orders, or 0 if not applicable.
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

			$paid_order_statuses = afwc_get_paid_order_status();
			if ( empty( $paid_order_statuses ) || ! is_array( $paid_order_statuses ) ) {
				return 0;
			}

			global $wpdb;

			try {
				$total_order_count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT post_id)
                        FROM {$wpdb->prefix}afwc_referrals
                        WHERE affiliate_id = %d
				            AND ( reference IS NULL OR reference = '' OR reference = '0' )
                            AND order_status IN (" . implode( ',', array_fill( 0, count( $paid_order_statuses ), '%s' ) ) . ')',
						array_merge(
							array(
								$affiliate_id,
							),
							$paid_order_statuses
						)
					)
				);

				if ( is_null( $total_order_count ) ) {
					throw new Exception(
						_x( 'Cannot fetch the total count for valid referral orders', 'Error message for referral order count rule', 'affiliate-for-woocommerce' )
					);
				}
			} catch ( Exception $e ) {
				$total_order_count = 0;
				Affiliate_For_WooCommerce::log_error( __METHOD__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			}

			return ! empty( $total_order_count ) ? intval( $total_order_count ) : 0;
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
