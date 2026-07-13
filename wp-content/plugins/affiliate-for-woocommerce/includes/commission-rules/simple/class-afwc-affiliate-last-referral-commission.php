<?php
/**
 * Class for affiliate's last referral (in days) commissions rule
 *
 * @package     affiliate-for-woocommerce/includes/commission-rules/simple/
 * @since       8.49.0
 * @version     1.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\Number_Rule;

if ( ! class_exists( AFWC_Affiliate_Last_Referral_Commission::class ) && class_exists( Number_Rule::class ) ) {

	/**
	 * Class AFWC_Affiliate_Last_Referral_Commission
	 */
	class AFWC_Affiliate_Last_Referral_Commission extends Number_Rule {

		/**
		 * Constructor.
		 *
		 * @param array $args Optional arguments.
		 */
		public function __construct( $args = array() ) {
			// Set the context key (unique identifier for this rule).
			$this->set_context_key( 'affiliate_last_referral' );

			// Set the category under which this rule should appear.
			$this->set_category( 'affiliate' );

			// Set the title displayed in the rule selection.
			$this->set_title(
				_x( 'Affiliate - Last Referral (days)', 'Title for the affiliate last referral (days) rule', 'affiliate-for-woocommerce' )
			);

			// Set the placeholder text displayed for the rule input field.
			$this->set_placeholder(
				_x( 'Enter number in days', 'Placeholder for the affiliate last referral (days) rule', 'affiliate-for-woocommerce' )
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
			$this->exclude_operators( array( 'in', 'nin', 'eq', 'neq', 'lt', 'lte', 'gt', 'gte' ) );

			// Re-merge the operators to change the operator label for this rule.
			return array_merge(
				$this->possible_operators,
				array(
					array(
						'op'    => 'lte',
						'label' => _x( 'in last', 'Less than or equal to operator label for the affiliate last referral (days) rule', 'affiliate-for-woocommerce' ),
						'type'  => 'single',
					),
					array(
						'op'    => 'gt',
						'label' => _x( 'not in last', 'Greater than operator label for the affiliate last referral (days) rule', 'affiliate-for-woocommerce' ),
						'type'  => 'single',
					),
					array(
						'op'    => 'gte',
						'label' => _x( 'older than', 'Greater than or equal to operator label for the affiliate last referral (days) rule', 'affiliate-for-woocommerce' ),
						'type'  => 'single',
					),
				)
			);
		}

		/**
		 * Get the context value for this rule i.e., affiliate last referral (days).
		 *
		 * @param array $args Context arguments.
		 *
		 * @throws \Exception If any error during the process.
		 * @return int Affiliate referral days since.
		 */
		public function get_context_value( $args = array() ) {
			// Do not return 0 as the rule considers it for validation.
			$referral_days = null;

			// Ensure we have an affiliate object.
			if ( empty( $args['affiliate'] ) ) {
				return $referral_days;
			}

			// Get the affiliate ID.
			$affiliate_id = ! empty( $args['affiliate']->ID ) ? intval( $args['affiliate']->ID ) : 0;
			if ( empty( $affiliate_id ) ) {
				return $referral_days;
			}

			$paid_order_statuses = afwc_get_paid_order_status();
			if ( empty( $paid_order_statuses ) || ! is_array( $paid_order_statuses ) ) {
				return $referral_days;
			}

			global $wpdb;

			try {
				$referral_days = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT
							IFNULL(DATEDIFF(CURDATE(), MAX(datetime)), 0) AS days_ago
						FROM {$wpdb->prefix}afwc_referrals
						WHERE affiliate_id = %d
							AND ( reference IS NULL OR reference = '' OR reference = '0' )
							AND status != %s
							AND order_status IN (" . implode( ',', array_fill( 0, count( $paid_order_statuses ), '%s' ) ) . ')',
						array_merge(
							array(
								$affiliate_id,
								'draft',
							),
							$paid_order_statuses
						)
					)
				);

				if ( is_null( $referral_days ) ) {
					throw new \Exception(
						! empty( $wpdb->last_error ) ? $wpdb->last_error : esc_html_x(
							'Unknown error.',
							'Unknown error message',
							'affiliate-for-woocommerce'
						)
					);
				}
			} catch ( \Throwable $e ) {
				Affiliate_For_WooCommerce::log_error( __METHOD__, $e->getMessage() );
				$referral_days = null;
			}

			return $referral_days;
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
