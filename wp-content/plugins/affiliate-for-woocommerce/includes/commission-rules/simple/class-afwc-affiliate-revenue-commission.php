<?php
/**
 * Class for affiliate's revenue commissions rule
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

if ( ! class_exists( AFWC_Affiliate_Revenue_Commission::class ) && class_exists( Number_Rule::class ) ) {

	/**
	 * Class AFWC_Affiliate_Revenue_Commission
	 */
	class AFWC_Affiliate_Revenue_Commission extends Number_Rule {

		/**
		 * Constructor.
		 *
		 * @param array $args Optional arguments.
		 */
		public function __construct( $args = array() ) {
			// Set the context key (unique identifier for this rule).
			$this->set_context_key( 'affiliate_revenue' );

			// Set the category under which this rule should appear.
			$this->set_category( 'affiliate' );

			// Set the title displayed in the rule selection.
			$this->set_title(
				_x( 'Affiliate - Revenue', 'Title for the affiliate revenue rule', 'affiliate-for-woocommerce' )
			);

			// Set the placeholder text displayed for the rule input field.
			$this->set_placeholder(
				_x( 'Enter revenue amount', 'Placeholder for the affiliate revenue rule', 'affiliate-for-woocommerce' )
			);

			// Set the input type for the rule input field.
			$this->set_input_props( array( 'type' => 'number' ) );
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
		 * Get the context value for this rule (i.e., affiliate revenue).
		 *
		 * @param array $args Context arguments.
		 *
		 * @throws Exception If any error during the process.
		 * @return int Affiliate revenue, or 0 if not applicable.
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
				// Include file if class not found.
				if ( ! class_exists( 'AFWC_Admin_Affiliates' ) ) {
					$class_path = AFWC_PLUGIN_DIRPATH . '/includes/admin/class-afwc-admin-affiliates.php';
					if ( ! file_exists( $class_path ) ) {
						return array();
					}
					include_once $class_path;
				}

				// Initialize admin affiliates class.
				$admin_affiliates  = new AFWC_Admin_Affiliates( $affiliate_id, '', '', '', AFWC_CURRENCY_CODE );
				$affiliate_revenue = is_callable( array( $admin_affiliates, 'get_net_affiliates_sales' ) ) ? floatval( $admin_affiliates->get_net_affiliates_sales() ) : 0;

				if ( is_null( $affiliate_revenue ) ) {
					throw new Exception( _x( 'Unable to fetch the affiliate revenue', 'Error message for affiliate revenue rule', 'affiliate-for-woocommerce' ) );
				}
			} catch ( Exception $e ) {
				$affiliate_revenue = 0;
				Affiliate_For_WooCommerce::log_error( __METHOD__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			}

			return ( ! empty( $affiliate_revenue ) ? $affiliate_revenue : 0 );
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
