<?php
/**
 * Referral Medium Interface
 *
 * Defines the contract that all referral medium classes must implement.
 * This ensures consistent handling of affiliate tracking across different referral sources.
 *
 * @package     affiliate-for-woocommerce/includes/referral-mediums/
 * @since       8.52.0
 * @version     1.0.1
 */

namespace AFWC\Referral_Mediums;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! interface_exists( Referral_Medium_Interface::class ) ) {
	/**
	 * Interface Referral_Medium_Interface
	 */
	interface Referral_Medium_Interface {

		/**
		 * Get the unique key for this referral medium.
		 *
		 * This key is used to identify the referral medium in the system.
		 * It should be unique across all referral mediums and remain constant.
		 *
		 * @return string The unique key for this referral medium (e.g., 'coupon', 'landing_page', 'direct_link').
		 */
		public function get_key();

		/**
		 * Get the human-readable label for this referral medium.
		 *
		 * This label is used for display purposes in the admin interface and reports.
		 *
		 * @return string The display label for this referral medium (e.g., 'Coupon', 'Landing Page').
		 */
		public function get_label();

		/**
		 * Get the affiliate ID for a WooCommerce order.
		 *
		 * This method determines which affiliate should be credited for a given order
		 * based on the specific referral medium's tracking mechanism.
		 *
		 * @param \WC_Order $order      The WooCommerce order object to check for affiliate attribution.
		 * @param array     $visit_data Optional. Visitor tracking record from the `afwc_hits` table.
		 *                              The system stores whatever columns exist in that table; at minimum
		 *                              the following keys will be available when a hit record is provided:
		 *
		 *                              [
		 *                                  'id'           => 123,            // hit record primary key
		 *                                  'affiliate_id' => 456,            // tracked affiliate id
		 *                                  'datetime'     => '2024-10-15 10:30:00', // timestamp of the hit
		 *                                  'ip'           => '192.168.1.1',  // visitor IP (nullable)
		 *                                  'user_id'      => 0,              // WP user id if known
		 *                                  'count'        => 1,              // hit counter
		 *                                  'type'         => 'link',         // referral medium key.
		 *                                  'campaign_id'  => 0,              // campaign id if applicable
		 *                                  'user_agent'   => 'Mozilla/5.0...',// user agent string (nullable)
		 *                                  'url'          => 'https://...',  // landing URL (nullable)
		 *                                  // additional columns (if present) will also be included
		 *                              ].
		 *
		 * @return int|null The affiliate ID if one should be credited, null otherwise.
		 */
		public function get_affiliate_for_order( $order, $visit_data = array() );

		/**
		 * Get the affiliate ID for registration form tracking.
		 *
		 * This method determines which affiliate should be credited when a new
		 * user registers, based on the specific referral medium's tracking mechanism.
		 *
		 * @param int $current_user_id The current user ID.
		 *
		 * @return int|null The affiliate ID if one should be credited, null otherwise.
		 */
		public function get_affiliate_for_registration( $current_user_id = 0 );
	}
}
