<?php
/**
 * Class to create a commission rule for order contains a coupon or not.
 *
 * @package     affiliate-for-woocommerce/includes/commission-rules/simple/
 * @since       8.36.0
 * @version     1.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\Boolean_Rule;

if ( ! class_exists( 'AFWC_Order_Contains_Coupons_Commission' ) && class_exists( Boolean_Rule::class ) ) {

	/**
	 * Class for order contains coupon commission rule.
	 */
	class AFWC_Order_Contains_Coupons_Commission extends Boolean_Rule {

		/**
		 * Constructor
		 *
		 * @param array $args props.
		 */
		public function __construct( $args = array() ) {
			$this->set_context_key( 'order_contains_coupons' );
			$this->set_category( 'order' );
			$this->set_title(
				_x( 'Order - Coupons', 'Title for order coupon rule', 'affiliate-for-woocommerce' )
			);
			$this->set_placeholder(
				_x( 'Select yes/no', 'Placeholder for order contains coupons rule', 'affiliate-for-woocommerce' )
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
				'yes' => _x( 'Yes', 'Positive value title for order contains coupons rule', 'affiliate-for-woocommerce' ),
				'no'  => _x( 'No', 'Negative value title for order contains coupons rule', 'affiliate-for-woocommerce' ),
			);
		}

		/**
		 * Get the context value for this rule.
		 *
		 * @param array $args The arguments for context.
		 *
		 * @return string 'yes' if the order has at least one non-affiliate coupon, otherwise 'no'.
		 */
		public function get_context_value( $args = array() ) {
			if (
				empty( $args['order'] ) ||
				! $args['order'] instanceof WC_Order ||
				! is_callable( array( $args['order'], 'get_coupon_codes' ) )
			) {
				return 'no';
			}

			$coupons = $args['order']->get_coupon_codes();

			if ( empty( $coupons ) || ! is_array( $coupons ) ) {
				return 'no';
			}

			$afwc_coupon = ( is_callable( array( 'AFWC_Coupon', 'get_instance' ) ) ) ? AFWC_Coupon::get_instance() : null;

			foreach ( $coupons as $coupon ) {

				if ( is_callable( array( $afwc_coupon, 'get_affiliate' ) ) &&
					! empty( $afwc_coupon->get_affiliate( $coupon ) )
				) {
					continue; // Skip affiliate coupons.
				}

				return 'yes'; // Found a non-affiliate coupon.
			}

			return 'no'; // No non-affiliate coupon found.
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
