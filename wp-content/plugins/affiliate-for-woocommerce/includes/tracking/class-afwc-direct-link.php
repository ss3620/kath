<?php
/**
 * Main class for Affiliates Direct Link functionality.
 *
 * @package     affiliate-for-woocommerce/includes/tracking/
 * @since       8.46.0
 * @version     1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Direct_Link' ) ) {

	/**
	 * Main class for Affiliates Direct Link functionality.
	 */
	class AFWC_Direct_Link {

		/**
		 * Direct Link statuses
		 *
		 * @var array
		 */
		public static $statuses = array( 'active', 'pending', 'rejected' );

		/**
		 * Variable to hold instance of AFWC_Direct_Link
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return AFWC_Direct_Link Singleton object of this class
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Check if Direct Link feature is enabled
		 *
		 * @return bool True if enabled, false otherwise
		 */
		public static function is_enabled() {
			return 'yes' === get_option( 'afwc_use_direct_links', 'no' );
		}

		/**
		 * Get affiliate ID for a given URL if it matches a direct link
		 *
		 * @param string      $url The URL to check.
		 * @param string|null $status Optional status to filter by ('active', 'pending', 'rejected').
		 *
		 * @return int Affiliate ID if found, 0 otherwise
		 */
		public function get_affiliate_id( $url = '', $status = null ) {

			if ( empty( $url ) || ! self::is_enabled() ) {
				return 0;
			}

			// Return zero if the provided status is not a valid status.
			if ( ! empty( $status ) && ! in_array( $status, self::$statuses, true ) ) {
				return 0;
			}

			$domain = wp_parse_url( esc_url( $url ), PHP_URL_HOST );

			global $wpdb;

			try {
				if ( empty( $status ) ) {
					$affiliate_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT affiliate_id
                            FROM {$wpdb->prefix}afwc_direct_links
                            WHERE domain = %s",
							$domain
						)
					);
				} else {
					$affiliate_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT affiliate_id
                            FROM {$wpdb->prefix}afwc_direct_links
                            WHERE domain = %s AND status = %s",
							$domain,
							$status
						)
					);
				}
			} catch ( Exception $e ) {
				Affiliate_For_WooCommerce::log_error( __FUNCTION__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
				$affiliate_id = 0;
			}

			return ! empty( $affiliate_id ) ? intval( $affiliate_id ) : 0;
		}

		/**
		 * Get assigned links for a given affiliate ID
		 *
		 * @param int         $affiliate_id The affiliate ID.
		 * @param string|null $status Optional status to filter by ('active', 'pending', 'rejected').
		 *
		 * @return array Array of [id => link]
		 */
		public function get_direct_links_by_affiliate_id( $affiliate_id = 0, $status = null ) {
			if ( empty( $affiliate_id ) || ! self::is_enabled() ) {
				return array();
			}

			// Return empty array if the provided status is not a valid status.
			if ( ! empty( $status ) && ! in_array( $status, self::$statuses, true ) ) {
				return array();
			}

			global $wpdb;

			try {
				if ( empty( $status ) ) {
					$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT id, domain
                            FROM {$wpdb->prefix}afwc_direct_links
                            WHERE affiliate_id = %d",
							$affiliate_id
						),
						ARRAY_A
					);
				} else {
					$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT id, domain
                            FROM {$wpdb->prefix}afwc_direct_links
                            WHERE affiliate_id = %d AND status = %s",
							$affiliate_id,
							$status
						),
						ARRAY_A
					);
				}

				if ( empty( $rows ) || ! is_array( $rows ) ) {
					return array();
				}

				$links = array();
				// Convert results to id => domain format.
				foreach ( $rows as $row ) {
					if ( empty( $row['id'] ) || empty( $row['domain'] ) ) {
						continue;
					}
					$id           = intval( $row['id'] );
					$links[ $id ] = $row['domain'];
				}
			} catch ( Exception $e ) {
				Affiliate_For_WooCommerce::log_error( __FUNCTION__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
				$links = array();
			}

			return $links;
		}

		/**
		 * Add a direct link for an affiliate
		 *
		 * @param string $url The URL to link.
		 * @param int    $affiliate_id The affiliate ID to link to.
		 * @param string $status The status of the link (default 'pending').
		 *
		 * @throws Exception If any error during query execution.
		 * @return int|WP_Error The inserted link id, or WP_Error on failure
		 */
		public function add_direct_link( $url = '', $affiliate_id = 0, $status = 'pending' ) {
			if ( empty( $url ) || empty( $affiliate_id ) ) {
				return new WP_Error(
					'missing_parameters',
					_x(
						'Parameters URL and Affiliate ID are required.',
						'Error message for missing parameters',
						'affiliate-for-woocommerce'
					)
				);
			}

			try {
				$domain = wp_parse_url( esc_url( $url ), PHP_URL_HOST );

				if ( empty( $domain ) ) {
					return new WP_Error(
						'invalid_direct_link',
						sprintf(
							/* translators: %s: user-entered URL */
							_x(
								'The link "%s" has not a valid domain.',
								'Error message for invalid direct link input',
								'affiliate-for-woocommerce'
							),
							esc_html( $url )
						)
					);
				}

				// Check if the link is already assigned to an affiliate.
				$existing_affiliate_id = $this->get_affiliate_id( $url );
				if ( ! empty( $existing_affiliate_id ) && ( intval( $existing_affiliate_id ) > 0 ) ) {
					return new WP_Error(
						'duplicate_direct_link',
						sprintf(
							/* translators: %s: direct link URL */
							_x(
								'The link %s is already linked to an affiliate.',
								'Error message for duplicate direct link',
								'affiliate-for-woocommerce'
							),
							$domain
						)
					);
				}

				global $wpdb;

				$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prefix . 'afwc_direct_links',
					array(
						'domain'       => $domain,
						'affiliate_id' => intval( $affiliate_id ),
						'status'       => $status,
					),
					array( '%s', '%d', '%s' )
				);

				if ( ! empty( $inserted ) && ( intval( $inserted ) > 0 ) && ! empty( $wpdb->insert_id ) ) {
					return intval( $wpdb->insert_id );
				} else {
					throw new Exception( 'Failed to insert direct link' );
				}
			} catch ( Exception $e ) {
				Affiliate_For_WooCommerce::log_error( __FUNCTION__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
				return new WP_Error(
					'insert_failed',
					_x(
						'Failed to add direct link. Please try again.',
						'Error message for failed direct link addition',
						'affiliate-for-woocommerce'
					)
				);
			}
		}

		/**
		 * Remove a direct link
		 *
		 * @param array $args The arguments array containing 'link' or 'id' to identify the link.
		 *
		 * @throws Exception If any error during query execution.
		 * @return bool|WP_Error True on success, WP_Error on failure
		 */
		public function remove_direct_link( $args = array() ) {
			if ( empty( $args['link'] ) && empty( $args['id'] ) ) {
				return new WP_Error(
					'missing_parameters',
					_x(
						'Either link or ID is required.',
						'Error message for missing parameters',
						'affiliate-for-woocommerce'
					)
				);
			}

			global $wpdb;

			try {
				$deleted = false;

				if ( ! empty( $args['id'] ) ) {
					$deleted = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prefix . 'afwc_direct_links',
						array( 'id' => intval( $args['id'] ) ),
						array( '%d' )
					);

				} elseif ( ! empty( $args['link'] ) ) {
					$domain = wp_parse_url( esc_url( $args['link'] ), PHP_URL_HOST );

					$deleted = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prefix . 'afwc_direct_links',
						array( 'domain' => $domain ),
						array( '%s' )
					);
				}

				if ( ! empty( $deleted ) && ( intval( $deleted ) > 0 ) ) {
					return true;
				} else {
					throw new Exception( 'Failed to delete direct link or link not found' );
				}
			} catch ( Exception $e ) {
				Affiliate_For_WooCommerce::log_error( __FUNCTION__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
				return new WP_Error(
					'delete_failed',
					_x(
						'Failed to remove direct link. Please try again.',
						'Error message for failed direct link removal',
						'affiliate-for-woocommerce'
					)
				);
			}
		}
	}
}
