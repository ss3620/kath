<?php
/**
 * Handles affiliate tracking for referral links.
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

if ( ! class_exists( Referral_Link::class ) && class_exists( Abstract_Cookie_Medium::class ) ) {

	/**
	 * Referral Link Referral Medium Class.
	 */
	class Referral_Link extends Abstract_Cookie_Medium {

		/**
		 * Get the unique key of the referral medium.
		 *
		 * @return string
		 */
		public function get_key() {
			return 'link';
		}

		/**
		 * Get the display name of the referral medium.
		 *
		 * @return string
		 */
		public function get_label() {
			return _x( 'Link', 'Display name of the referral medium', 'affiliate-for-woocommerce' );
		}

		/**
		 * Check if referral Link feature is enabled.
		 *
		 * NOTE: Referral links are always enabled as core functionality.
		 *
		 * @return bool Always returns true for this medium.
		 */
		protected function is_medium_enabled() {
			return true;
		}

		/**
		 * Get the affiliate ID from the request parameters for visitor tracking.
		 *
		 * @return int|null Return affiliate ID if found, null otherwise.
		 */
		public function get_affiliate_for_visits() {
			$pname = afwc_get_pname();
			// Use $_REQUEST to access and use values inside this method.
			$pname        = ( empty( $_REQUEST[ $pname ] ) && ! empty( $_REQUEST['ref'] ) ) ? 'ref' : $pname; // phpcs:ignore
			$affiliate_id = 0;

			if ( 'yes' === get_option( 'afwc_use_pretty_referral_links', 'no' ) ) {

				$url = afwc_get_current_url();
				if ( ! is_string( $url ) || false === strpos( $url, $pname ) ) {
					return null;
				}

				$values       = wp_parse_url( $url );
				$parsed       = explode( '/', $values['path'] );
				$parsed_count = count( $parsed );
				for ( $i = 0; $i < $parsed_count; $i++ ) {
					if ( ! empty( $parsed[ $i ] ) && $parsed[ $i ] === $pname ) {
						$affiliate_id = ( ! empty( $parsed[ $i + 1 ] ) ) ? $parsed[ $i + 1 ] : 0;
						break;
					}
				}
			} else {
				if ( empty( $_REQUEST[ $pname ] ) ) { // phpcs:ignore
					return;
				}

				$affiliates_pname = ( defined( 'AFFILIATES_PNAME' ) ) ? AFFILIATES_PNAME : 'affiliates';
				$migrated_pname   = get_option( 'afwc_migrated_pname', $affiliates_pname );

				// Handle older affiliates link through migrated pname.
				if ( isset( $_REQUEST[ $migrated_pname ] ) ) { // phpcs:ignore
					$id           = wc_clean( wp_unslash( $_REQUEST[ $migrated_pname ] ) ); // phpcs:ignore
					$affiliate_id = afwc_get_user_id_based_on_affiliate_id( $id );
				} elseif ( isset( $_REQUEST[ $pname ] ) ) { // phpcs:ignore
					$affiliate_id = wc_clean( wp_unslash( $_REQUEST[ $pname ] ) ); // phpcs:ignore
				} elseif ( isset( $_REQUEST['ref'] ) ) { // phpcs:ignore
					$affiliate_id = wc_clean( wp_unslash( $_REQUEST['ref'] ) ); // phpcs:ignore
				}
			}

			return afwc_get_affiliate_id_by_identifier( $affiliate_id );
		}
	}
}
