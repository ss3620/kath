<?php
/**
 * Class of commission rule Affiliate - Parent to check affiliate has any of/none of parent.
 *
 * @package     affiliate-for-woocommerce/includes/multi-tier/commission-rules/
 * @since       9.4.0
 * @version     1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\Number_Rule;

if ( ! class_exists( 'AFWC_Affiliate_Match_Parents_Commission' ) && class_exists( Number_Rule::class ) ) {

	/**
	 * Class for affiliate match parents commission rule.
	 */
	class AFWC_Affiliate_Match_Parents_Commission extends Number_Rule {

		/**
		 * Constructor
		 *
		 * @param array $args Properties.
		 */
		public function __construct( $args = array() ) {
			$this->set_context_key( 'affiliate_match_parents' );
			$this->set_category( 'affiliate' );
			$this->set_title(
				_x( 'Affiliate - Parent', 'Title for affiliate parent rule', 'affiliate-for-woocommerce' )
			);
			$this->set_placeholder(
				_x( 'Search for an affiliate', 'commission rule placeholder', 'affiliate-for-woocommerce' )
			);
			parent::__construct( $args );
		}

		/**
		 * Method to return possible operators.
		 *
		 * @return array Array of possible operators.
		 */
		public function get_possible_operators() {
			$this->exclude_operators( array( 'gt', 'gte', 'lt', 'eq', 'lte', 'neq' ) );
			return $this->possible_operators;
		}

		/**
		 * Get Affiliates based on the search term.
		 *
		 * @param string|array $term     Affiliate for searching or array of affiliate IDs for inclusion.
		 * @param bool         $for_ajax If true, search for affiliates; otherwise, include specific affiliate IDs.
		 *
		 * @return array Array of affiliate IDs and names. Returns an empty array if no affiliate match or an error occurs.
		 */
		public function search_values( $term = '', $for_ajax = true ) {

			$rule_values = array();

			if ( empty( $term ) ) {
				return $rule_values;
			}

			global $affiliate_for_woocommerce;

			if ( true === $for_ajax ) {
				$search = afwc_get_default_user_search_args( $term );
			} else {
				// Fetch affiliates by user ids.
				if ( ! is_array( $term ) ) {
					$term = (array) $term;
				}
				$search = array(
					'include' => $term,
				);
			}

			$rule_values = is_callable( array( $affiliate_for_woocommerce, 'get_affiliates' ) ) ? $affiliate_for_woocommerce->get_affiliates( $search ) : $rule_values;

			return $rule_values;
		}

		/**
		 * Method to get context value for this rule.
		 *
		 * @param array $args The arguments for context.
		 *
		 * @return array Array of parent affiliate IDs.
		 */
		public function get_context_value( $args = array() ) {
			$parent_affiliates = array();
			// Ensure we have an affiliate object.
			if ( empty( $args['affiliate'] ) || ! ( $args['affiliate'] instanceof AFWC_Affiliate ) ) {
				return $parent_affiliates;
			}

			// Get the affiliate ID.
			$affiliate_id = ! empty( $args['affiliate']->ID ) ? intval( $args['affiliate']->ID ) : 0;
			if ( empty( $affiliate_id ) ) {
				return $parent_affiliates;
			}

			$afwc_multi_tier = ( is_callable( array( 'AFWC_Multi_Tier', 'get_instance' ) ) ) ? AFWC_Multi_Tier::get_instance() : null;
			if ( is_callable( array( $afwc_multi_tier, 'get_parents' ) ) ) {
				$parent_affiliates = $afwc_multi_tier->get_parents( $affiliate_id );
			}
			return $parent_affiliates;
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
