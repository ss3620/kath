<?php
/**
 * Handles affiliate tracking via coupon codes linked to affiliates.
 *
 * @package     affiliate-for-woocommerce/includes/referral-mediums/
 * @since       8.52.0
 * @version     1.0.2
 */

namespace AFWC\Referral_Mediums;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( Referral_Coupon::class ) && interface_exists( Referral_Medium_Interface::class ) ) {

	/**
	 * Coupon Referral Medium Class.
	 */
	class Referral_Coupon implements Referral_Medium_Interface {

		/**
		 * Get the unique key of the referral medium.
		 *
		 * @return string
		 */
		public function get_key() {
			return 'coupon';
		}

		/**
		 * Get the display name of the referral medium.
		 *
		 * @return string
		 */
		public function get_label() {
			return _x( 'Coupon', 'Display name of the referral medium', 'affiliate-for-woocommerce' );
		}

		/**
		 * Get the affiliate ID for WooCommerce order tracking.
		 *
		 * This method checks if any coupons used in the order are linked to an affiliate
		 * and follows the first & last credit policy.
		 *
		 * NOTE: Coupon tracking doesn't use visit data because it determines the affiliate
		 * based on coupons applied at checkout, not the visitor's landing/tracking data.
		 *
		 * @param \WC_Order $order      The WooCommerce order object.
		 * @param array     $visit_data Optional. Not used by this medium.
		 *
		 * @return int|null Return affiliate ID if found, null otherwise.
		 */
		public function get_affiliate_for_order( $order = null, $visit_data = array() ) {
			if ( ! $order instanceof \WC_Order ) {
				return null;
			}

			// Get coupons used in the order.
			$used_coupons = is_callable( array( $order, 'get_coupon_codes' ) ) ? $order->get_coupon_codes() : array();

			if ( empty( $used_coupons ) || ! is_array( $used_coupons ) ) {
				return null;
			}

			// Get credit policy setting (first or last).
			$credit_policy = get_option( 'afwc_credit_affiliate', 'last' );
			$afwc_coupon   = ( is_callable( array( 'AFWC_Coupon', 'get_instance' ) ) ) ? \AFWC_Coupon::get_instance() : null;

			if ( empty( $afwc_coupon ) || ! is_callable( array( $afwc_coupon, 'get_affiliate' ) ) ) {
				return null;
			}

			$affiliate_id = null;

			foreach ( $used_coupons as $coupon_code ) {
				$affiliate_id = $afwc_coupon->get_affiliate( $coupon_code );

				// If the credit policy is set to 'first', we only need the first affiliate ID.
				if ( 'first' === $credit_policy && ! empty( $affiliate_id ) ) {
					return intval( $affiliate_id );
				}
			}

			return ! empty( $affiliate_id ) ? intval( $affiliate_id ) : null;
		}

		/**
		 * Get the affiliate ID for registration form tracking.
		 *
		 * Coupon tracking does not apply to user registrations as coupons
		 * are only applicable during checkout.
		 *
		 * @param int $current_user_id The current user ID.
		 *
		 * @return int|null Always returns null for this medium.
		 */
		public function get_affiliate_for_registration( $current_user_id = 0 ) {
			return null;
		}
	}
}
