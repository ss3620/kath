<?php
/**
 * Handles affiliate tracking for WooCommerce Subscriptions renewal orders.
 *
 * @package     affiliate-for-woocommerce/includes/integration/woocommerce-subscriptions/
 * @since       8.52.0
 * @version     1.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Referral_Mediums\Referral_Medium_Interface;

if ( ! class_exists( AFWC_WCS_Recurring_Referral_Medium::class ) && interface_exists( Referral_Medium_Interface::class ) ) {

	/**
	 * Class for WooCommerce Subscriptions order referral medium
	 */
	class AFWC_WCS_Recurring_Referral_Medium implements Referral_Medium_Interface {

		/**
		 * Get the unique key of the referral medium.
		 *
		 * @return string
		 */
		public function get_key() {
			return 'recurring_subscription';
		}

		/**
		 * Get the display name of the referral medium.
		 *
		 * @return string
		 */
		public function get_label() {
			return _x( 'Subscription Renewal', 'Display name of the referral medium', 'affiliate-for-woocommerce' );
		}

		/**
		 * Get the affiliate ID associated with the parent subscription order.
		 *
		 * @param WC_Order|null $order The WooCommerce order object.
		 * @param array         $visit_data Additional visit data (not used here).
		 *
		 * @return int|null The affiliate ID if found, otherwise null.
		 */
		public function get_affiliate_for_order( $order = null, $visit_data = array() ) {
			if ( ! $order instanceof \WC_Order ) {
				return null;
			}

			$sub_types = array( 'renewal' );

			/**
			 * Filter to continue the affiliate on resubscribe orders from previous subscription.
			 *
			 * @since 8.39.0
			 * @param bool Whether to continue affiliate on resubscribe orders. Default true.
			 */
			if ( true === apply_filters( 'afwc_should_continue_affiliate_on_resubscribe', true ) ) {
				$sub_types[] = 'resubscribe';
			}

			if ( ! wcs_order_contains_subscription( $order, $sub_types ) ) {
				return null;
			}

			$subscriptions = wcs_get_subscriptions_for_order( $order, array( 'order_type' => $sub_types ) );

			if ( ! empty( $subscriptions ) ) {
				$subscription = is_array( $subscriptions ) ? end( $subscriptions ) : $subscriptions;
				$parent_id    = $subscription instanceof WC_Subscription && is_callable( array( $subscription, 'get_parent_id' ) ) ? $subscription->get_parent_id() : 0;

				if ( empty( $parent_id ) ) {
					return null;
				}

				$afwc_api          = is_callable( array( 'AFWC_API', 'get_instance' ) ) ? AFWC_API::get_instance() : null;
				$affiliate_details = is_callable( array( $afwc_api, 'get_affiliate_by_order' ) ) ? $afwc_api->get_affiliate_by_order( intval( $parent_id ) ) : array();

				// Inherit the affiliate from the parent order, or 0 if no affiliate is assigned to prevent other affiliate being assigned to the renewal orders.
				return ( ! empty( $affiliate_details ) && ! empty( $affiliate_details['affiliate_id'] ) ) ? intval( $affiliate_details['affiliate_id'] ) : 0;
			}

			return null;
		}

		/**
		 * Get the affiliate ID for registration form tracking.
		 * This medium does not track registrations, so it always returns null.
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
