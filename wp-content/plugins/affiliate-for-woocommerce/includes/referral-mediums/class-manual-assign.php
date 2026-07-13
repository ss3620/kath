<?php
/**
 * Handles manual affiliate assignments by admin.
 *
 * @package     affiliate-for-woocommerce/includes/referral-mediums/
 * @since       8.52.1
 * @version     1.0.1
 */

namespace AFWC\Referral_Mediums;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( Manual_Assign::class ) && interface_exists( Referral_Medium_Interface::class ) ) {

	/**
	 * Manual Assignment Referral Medium Class.
	 */
	class Manual_Assign implements Referral_Medium_Interface {

		/**
		 * Get the unique key of the referral medium.
		 *
		 * @return string
		 */
		public function get_key() {
			return 'manual';
		}

		/**
		 * Get the display name of the referral medium.
		 *
		 * @return string
		 */
		public function get_label() {
			return _x( 'Manual', 'Display name of the referral medium', 'affiliate-for-woocommerce' );
		}

		/**
		 * Get the affiliate ID for WooCommerce order tracking.
		 *
		 * This method checks if the order has a manual affiliate assignment by admin.
		 *
		 * @param \WC_Order $order      The WooCommerce order object.
		 * @param array     $visit_data Optional. Not used by this medium.
		 *
		 * @return int|null Return affiliate ID if manually assigned, null otherwise.
		 */
		public function get_affiliate_for_order( $order = null, $visit_data = array() ) {
			if ( ! $order instanceof \WC_Order ) {
				return null;
			}

			// Verify nonce and screen context to ensure it's from admin order edit.
			if ( empty( $_POST['woocommerce_meta_nonce'] ) || ! wp_verify_nonce( wc_clean( wp_unslash( $_POST['woocommerce_meta_nonce'] ) ), 'woocommerce_save_data' ) ) { // phpcs:ignore
				return;
			}

			$current_screen_id = afwc_get_current_screen_data();
			if ( empty( $current_screen_id ) ) {
				return;
			}

			$wc_shop_order_screen_id = function_exists( 'wc_get_page_screen_id' ) && is_callable( 'wc_get_page_screen_id' ) ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

			if ( $wc_shop_order_screen_id !== $current_screen_id ) {
				return;
			}

			// Check if order has manual affiliate assignment.
			$affiliate_id = ! empty( $_POST['afwc_referral_order_of'] ) ? wc_clean( wp_unslash( $_POST['afwc_referral_order_of'] ) ) : ''; // phpcs:ignore

			return ! empty( $affiliate_id ) ? intval( $affiliate_id ) : null;
		}

		/**
		 * Get the affiliate ID for registration form tracking.
		 *
		 * Manual assignment does not apply to user registrations as it's only
		 * used for order assignments by admin.
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
