<?php
/**
 * Abstract base class for cookie-based referral mediums.
 *
 * Provides common functionality for mediums that track affiliates via cookies
 * (Landing Page, Direct Link, Referral Link, etc.).
 *
 * @package     affiliate-for-woocommerce/includes/abstracts/
 * @since       8.52.0
 * @version     1.0.1
 */

namespace AFWC\Referral_Mediums;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( Abstract_Cookie_Medium::class ) ) {

	/**
	 * Abstract Cookie-Based Referral Medium Class.
	 */
	abstract class Abstract_Cookie_Medium implements Referral_Medium_Interface {

		/**
		 * Get the affiliate ID for WooCommerce order tracking.
		 *
		 * This method validates the visit data and retrieves the affiliate ID from cookie.
		 * Common implementation for all cookie-based tracking mediums.
		 *
		 * @param \WC_Order $order      The WooCommerce order object.
		 * @param array     $visit_data The visit data from tracking table.
		 *
		 * @return int|null Return affiliate ID if found, null otherwise.
		 */
		public function get_affiliate_for_order( $order = null, $visit_data = array() ) {
			if ( ! $order instanceof \WC_Order || ! $this->is_medium_enabled() ) {
				return null;
			}

			// Validate visit data matches this medium.
			if ( ! $this->was_referred_by_this_medium( $visit_data ) ) {
				return null;
			}

			// Get the affiliate ID from cookie.
			$affiliate_id = afwc_get_affiliate_from_cookie();

			return ! empty( $affiliate_id ) ? intval( $affiliate_id ) : null;
		}

		/**
		 * Get the affiliate ID for registration form tracking.
		 *
		 * This method retrieves the parent affiliate ID from cookie for affiliate registrations.
		 * Common implementation for all cookie-based tracking mediums.
		 *
		 * @param int $current_user_id The current user ID.
		 *
		 * @return int|null Return affiliate ID if found, null otherwise.
		 */
		public function get_affiliate_for_registration( $current_user_id = 0 ) {
			if ( ! $this->is_medium_enabled() ) {
				return null;
			}

			// Get the affiliate ID from cookie.
			$affiliate_id = afwc_get_affiliate_from_cookie();

			if ( empty( $affiliate_id ) ) {
				return null;
			}

			return intval( $affiliate_id );
		}

		/**
		 * Check if the current visit was referred by this medium.
		 *
		 * Validates that the visit_data contains the correct referral_medium value.
		 *
		 * @param array $visit_data The visit data captured during the page hit.
		 *
		 * @return bool True if referred by this medium, false otherwise.
		 */
		protected function was_referred_by_this_medium( $visit_data = array() ) {
			if ( empty( $visit_data ) || ! is_array( $visit_data ) ) {
				return false;
			}

			// Check if the referral_medium in visit data matches this medium's key.
			return ( ! empty( $visit_data['type'] ) && $visit_data['type'] === $this->get_key() );
		}

		/**
		 * Get the unique key for this referral medium.
		 *
		 * Must be implemented by child classes.
		 *
		 * @return string The unique key.
		 */
		abstract public function get_key();

		/**
		 * Get the display label for this referral medium.
		 *
		 * Must be implemented by child classes.
		 *
		 * @return string The display label.
		 */
		abstract public function get_label();

		/**
		 * Check if this medium is enabled.
		 *
		 * Must be implemented by child classes to implement
		 * medium-specific enable/disable logic.
		 *
		 * @return bool True if enabled, false otherwise.
		 */
		abstract protected function is_medium_enabled();

		/**
		 * Get the affiliate ID for visit tracking.
		 *
		 * Must be implemented by child classes.
		 *
		 * @return int|null Return affiliate ID if found, null otherwise.
		 */
		abstract public function get_affiliate_for_visits();
	}
}
