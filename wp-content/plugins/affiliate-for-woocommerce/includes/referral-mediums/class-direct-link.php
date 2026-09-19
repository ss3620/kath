<?php
/**
 * Handles affiliate tracking for direct link tracking domains.
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

if ( ! class_exists( Direct_Link::class ) && class_exists( Abstract_Cookie_Medium::class ) ) {

	/**
	 * Direct Link Referral Medium Class.
	 */
	class Direct_Link extends Abstract_Cookie_Medium {

		/**
		 * Get the unique key of the referral medium.
		 *
		 * @return string
		 */
		public function get_key() {
			return 'direct_link';
		}

		/**
		 * Get the display name of the referral medium.
		 *
		 * @return string
		 */
		public function get_label() {
			return _x( 'Direct Link', 'Display name of the referral medium', 'affiliate-for-woocommerce' );
		}

		/**
		 * Check if Direct Link feature is enabled.
		 *
		 * @return bool True if enabled, false otherwise.
		 */
		protected function is_medium_enabled() {
			return is_callable( array( 'AFWC_Direct_Link', 'is_enabled' ) ) && \AFWC_Direct_Link::is_enabled();
		}

		/**
		 * Get the affiliate ID from the referer for visit tracking.
		 *
		 * @return int|null Return affiliate ID if found, null otherwise.
		 */
		public function get_affiliate_for_visits() {

			if ( ! $this->is_medium_enabled() ) {
				return null;
			}

			try {
				$referer = wp_get_raw_referer();

				if ( empty( $referer ) ) {
					return null;
				}

				$referer_host = wp_parse_url( esc_url( $referer ), PHP_URL_HOST );
				$site_host    = wp_parse_url( home_url(), PHP_URL_HOST );

				// Ignore if referer is the same as site host.
				if ( $referer_host === $site_host ) {
					return null;
				}

				$direct_link = is_callable( array( 'AFWC_Direct_Link', 'get_instance' ) ) ? \AFWC_Direct_Link::get_instance() : null;

				$affiliate_id = is_callable( array( $direct_link, 'get_affiliate_id' ) )
					? $direct_link->get_affiliate_id( $referer_host, 'active' )
					: null;

				return ! empty( $affiliate_id ) ? intval( $affiliate_id ) : null;
			} catch ( \Throwable $e ) {
				\Affiliate_For_WooCommerce::log_error( __METHOD__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
				return null;
			}
		}
	}
}
