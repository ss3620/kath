<?php
/**
 * Handles affiliate tracking for lifetime commissions.
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

if ( ! class_exists( Lifetime_Commission::class ) && interface_exists( Referral_Medium_Interface::class ) ) {

	/**
	 * Lifetime Commission Referral Medium Class.
	 */
	class Lifetime_Commission implements Referral_Medium_Interface {

		/**
		 * Get the unique key of the referral medium.
		 *
		 * @return string
		 */
		public function get_key() {
			return 'lifetime_commission';
		}

		/**
		 * Get the display name of the referral medium.
		 *
		 * @return string
		 */
		public function get_label() {
			return _x( 'Lifetime', 'Display name of the referral medium', 'affiliate-for-woocommerce' );
		}

		/**
		 * Check if the lifetime commission medium is enabled.
		 *
		 * @return bool True if enabled, false otherwise.
		 */
		protected function is_medium_enabled() {
			return 'yes' === get_option( 'afwc_enable_lifetime_commissions', 'no' );
		}

		/**
		 * Get the affiliate ID for WooCommerce order tracking.
		 *
		 * NOTE: Lifetime commissions don't use visit data because they track
		 * the stored customer-affiliate relationship, not the current visit.
		 *
		 * @param \WC_Order $order      The WooCommerce order object.
		 * @param array     $visit_data Optional. Not used by this medium.
		 *
		 * @return int|null Return affiliate ID if found, null otherwise.
		 */
		public function get_affiliate_for_order( $order = null, $visit_data = array() ) {

			if ( ! $order instanceof \WC_Order || ! $this->is_medium_enabled() ) {
				return null;
			}

			// Get customer from order.
			$customer = is_callable( array( $order, 'get_customer_id' ) ) ? $order->get_customer_id() : 0;

			if ( empty( $customer ) ) {
				$customer = is_callable( array( $order, 'get_billing_email' ) ) ? $order->get_billing_email() : '';
			}

			// Get lifetime affiliate for this customer.
			$ltc_affiliate = afwc_get_ltc_affiliate_by_customer( $customer );
			$affiliate_obj = ! empty( $ltc_affiliate ) ? new \AFWC_Affiliate( $ltc_affiliate ) : null;

			if ( is_object( $affiliate_obj )
				&& is_callable( array( $affiliate_obj, 'is_ltc_enabled' ) )
				&& $affiliate_obj->is_ltc_enabled()
			) {
				return intval( $ltc_affiliate );
			}

			return null;
		}

		/**
		 * Get the affiliate ID for registration form tracking.
		 *
		 * @param int $current_user_id The current user ID.
		 *
		 * @return int|null Return affiliate ID if found, null otherwise.
		 */
		public function get_affiliate_for_registration( $current_user_id = 0 ) {
			if ( ! $this->is_medium_enabled() ) {
				return null;
			}

			// Use provided user ID or get current user ID.
			$current_user_id = $current_user_id ?? get_current_user_id();

			if ( empty( $current_user_id ) ) {
				return null;
			}

			$ltc_affiliate = afwc_get_ltc_affiliate_by_customer( $current_user_id );
			$affiliate_obj = ! empty( $ltc_affiliate ) ? new \AFWC_Affiliate( $ltc_affiliate ) : null;

			if ( is_object( $affiliate_obj ) && is_callable( array( $affiliate_obj, 'is_ltc_enabled' ) ) && $affiliate_obj->is_ltc_enabled() ) {
				return intval( $ltc_affiliate );
			}

			return null;
		}
	}
}
