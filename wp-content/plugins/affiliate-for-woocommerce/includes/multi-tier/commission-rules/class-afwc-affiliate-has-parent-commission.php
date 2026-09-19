<?php
/**
 * Class of commission rule Affiliate - Parent to check affiliate has a parent or not.
 *
 * @package     affiliate-for-woocommerce/includes/multi-tier/commission-rules/
 * @since       9.4.0
 * @version     1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\Boolean_Rule;

if ( ! class_exists( 'AFWC_Affiliate_Has_Parent_Commission' ) && class_exists( Boolean_Rule::class ) ) {

	/**
	 * Class for affiliate has parent commission rule.
	 */
	class AFWC_Affiliate_Has_Parent_Commission extends Boolean_Rule {

		/**
		 * Constructor
		 *
		 * @param array $args props.
		 */
		public function __construct( $args = array() ) {
			$this->set_context_key( 'affiliate_has_parent' );
			$this->set_category( 'affiliate' );
			$this->set_title(
				_x( 'Affiliate - Parent', 'Title for affiliate parent rule', 'affiliate-for-woocommerce' )
			);
			$this->set_placeholder(
				_x( 'Select yes/no', 'Placeholder for affiliate has parent rule', 'affiliate-for-woocommerce' )
			);
			parent::__construct( $args );
		}

		/**
		 * Method to return possible options.
		 *
		 * @return array Return the pre-defined options for the rule.
		 */
		public function get_options() {
			return array(
				'yes' => _x( 'Yes', 'Positive value title for affiliate has parent rule', 'affiliate-for-woocommerce' ),
				'no'  => _x( 'No', 'Negative value title for affiliate has parent rule', 'affiliate-for-woocommerce' ),
			);
		}

		/**
		 * Get the context value for this rule.
		 *
		 * @param array $args The arguments for context.
		 *
		 * @return string 'yes' if the affiliate has a parent, otherwise 'no'.
		 */
		public function get_context_value( $args = array() ) {
			// Ensure we have an affiliate object.
			if ( empty( $args['affiliate'] ) || ! ( $args['affiliate'] instanceof AFWC_Affiliate ) ) {
				return 'no';
			}

			// Get the affiliate ID.
			$affiliate_id = ! empty( $args['affiliate']->ID ) ? intval( $args['affiliate']->ID ) : 0;
			if ( empty( $affiliate_id ) ) {
				return 'no';
			}

			$parent_affiliates = array();
			$afwc_multi_tier   = ( is_callable( array( 'AFWC_Multi_Tier', 'get_instance' ) ) ) ? AFWC_Multi_Tier::get_instance() : null;
			if ( is_callable( array( $afwc_multi_tier, 'get_parents' ) ) ) {
				$parent_affiliates = $afwc_multi_tier->get_parents( $affiliate_id );
			}

			return ( ! empty( $parent_affiliates ) ) ? 'yes' : 'no';
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
