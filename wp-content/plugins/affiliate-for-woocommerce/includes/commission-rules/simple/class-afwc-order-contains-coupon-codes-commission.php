<?php
/**
 * Class for Order - Coupon Codes commissions rule
 *
 * @package     affiliate-for-woocommerce/includes/commission-rules/simple/
 * @since       8.36.0
 * @version     1.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\Number_Rule;

if ( ! class_exists( 'AFWC_Order_Contains_Coupon_Codes_Commission' ) && class_exists( Number_Rule::class ) ) {

	/**
	 * Order coupon codes commission rule class
	 */
	class AFWC_Order_Contains_Coupon_Codes_Commission extends Number_Rule {

		/**
		 * Constructor
		 *
		 * @param array $args Properties.
		 */
		public function __construct( $args = array() ) {
			$this->set_context_key( 'order_contains_coupon_codes' );
			$this->set_category( 'order' );
			$this->set_title(
				_x( 'Order - Coupons', 'Title for order coupon rule', 'affiliate-for-woocommerce' )
			);
			$this->set_placeholder(
				_x( 'Search for coupon codes', 'Placeholder for order contains coupon codes rule', 'affiliate-for-woocommerce' )
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
		 * Get Coupons based on the search term.
		 *
		 * @param string|array $term     Coupon code for searching or array of coupon IDs for inclusion.
		 * @param bool         $for_ajax If true, search for coupons; otherwise, include specific coupon IDs.
		 *
		 * @return array Array of coupon IDs and codes. Returns an empty array if no coupon match or an error occurs.
		 */
		public function search_values( $term = '', $for_ajax = true ) {

			if ( empty( $term ) ) {
				return array();
			}

			global $wpdb;

			try {
				if ( $for_ajax ) {
					$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT ID, post_title
							FROM {$wpdb->posts}
							WHERE post_type = 'shop_coupon'
								AND post_status = 'publish'
								AND post_title LIKE %s",
							'%' . $wpdb->esc_like( $term ) . '%'
						),
						'ARRAY_A'
					);
				} else {

					if ( ! is_array( $term ) ) {
						$term = (array) $term;
					}
					$ids = array_map( 'intval', $term );

					if ( ! empty( $ids ) ) {
						$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT ID, post_title
								FROM {$wpdb->posts}
								WHERE post_type = 'shop_coupon'
									AND post_status = 'publish'
									AND ID IN (" . implode( ',', array_fill( 0, count( $ids ), '%s' ) ) . ')',
								$ids
							),
							'ARRAY_A'
						);
					}
				}
			} catch ( Exception $e ) {
				Affiliate_For_WooCommerce::log_error( __FUNCTION__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
				$results = array();
			}

			if ( empty( $results ) || ! is_array( $results ) ) {
				return array();
			}

			$coupons = array();

			foreach ( $results as $row ) {
				if ( ! empty( $row['ID'] ) && ! empty( $row['post_title'] ) ) {
					$coupons[ $row['ID'] ] = sprintf(
						'%1$s (#%2$s)',
						wp_strip_all_tags( $row['post_title'] ),
						absint( $row['ID'] )
					);
				}
			}

			return $coupons;
		}

		/**
		 * Method to get context value for this rule.
		 *
		 * @param array $args The arguments for context.
		 *
		 * @return array Array of coupon IDs applied to the order.
		 */
		public function get_context_value( $args = array() ) {
			if ( empty( $args['order'] ) ||
				! $args['order'] instanceof WC_Order ||
				! is_callable( array( $args['order'], 'get_coupon_codes' ) )
			) {
				return array();
			}

			$coupon_codes = $args['order']->get_coupon_codes();

			if ( empty( $coupon_codes ) || ! is_array( $coupon_codes ) ) {
				return array();
			}

			global $wpdb;

			$coupon_ids = array();

			try {
				$coupon_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT ID
						FROM {$wpdb->posts}
						WHERE post_type = 'shop_coupon'
							AND post_status = 'publish'
							AND post_title IN (" . implode( ',', array_fill( 0, count( $coupon_codes ), '%s' ) ) . ')',
						$coupon_codes
					)
				);
			} catch ( Exception $e ) {
				Affiliate_For_WooCommerce::log_error( __FUNCTION__, ( is_callable( array( $e, 'getMessage' ) ) ? $e->getMessage() : '' ) );
				$coupon_ids = array();
			}

			return ( ! empty( $coupon_ids ) && is_array( $coupon_ids ) ) ? $coupon_ids : array();
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
