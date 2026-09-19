<?php
/**
 * Handles affiliate tracking via affiliate-specific landing pages.
 *
 * @package     affiliate-for-woocommerce/includes/referral-mediums/
 * @since       8.52.0
 * @version     1.0.0
 */

namespace AFWC\Referral_Mediums;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( Landing_Page::class ) && class_exists( Abstract_Cookie_Medium::class ) ) {

	/**
	 * Landing Page Referral Medium Class.
	 */
	class Landing_Page extends Abstract_Cookie_Medium {

		/**
		 * Get the unique key of the referral medium.
		 *
		 * @return string
		 */
		public function get_key() {
			return 'landing_page';
		}

		/**
		 * Get the display name of the referral medium.
		 *
		 * @return string
		 */
		public function get_label() {
			return _x( 'Landing Page', 'Display name of the referral medium', 'affiliate-for-woocommerce' );
		}

		/**
		 * Check if Landing Page feature is enabled.
		 *
		 * @return bool True if enabled, false otherwise.
		 */
		protected function is_medium_enabled() {
			return is_callable( array( 'AFWC_Landing_Page', 'is_enabled' ) ) && \AFWC_Landing_Page::is_enabled();
		}

		/**
		 * Get the affiliate ID from the current singular post for visit tracking.
		 *
		 * @return int|null Return affiliate ID if found, null otherwise.
		 */
		public function get_affiliate_for_visits() {

			if ( ! $this->is_medium_enabled() ) {
				return null;
			}

			$landing_page = is_callable( array( 'AFWC_Landing_Page', 'get_instance' ) ) ? \AFWC_Landing_Page::get_instance() : null;

			if ( ! is_callable( array( $landing_page, 'get_affiliate_id_by_current_singular_post' ) ) ) {
				return null;
			}

			// Get the affiliate id by the current single post page.
			$affiliate_id = $landing_page->get_affiliate_id_by_current_singular_post();

			return ! empty( $affiliate_id ) ? intval( $affiliate_id ) : null;
		}
	}
}
