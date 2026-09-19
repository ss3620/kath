<?php
/**
 * Class for migrating affiliates data from Affiliate WP.
 *
 * @package     affiliate-for-woocommerce/includes/migrations/sources
 * @since       8.48.0
 * @version     1.0.1
 */

namespace AFWC\Migrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( '\AFWC_Migration', false ) ) {
	require_once AFWC_PLUGIN_DIRPATH . '/includes/abstracts/class-afwc-migration.php';
}

if ( ! class_exists( Affiliate_WP::class ) && class_exists( '\AFWC_Migration' ) ) {

	/**
	 * Migration class for Affiliate WP plugin.
	 */
	class Affiliate_WP extends \AFWC_Migration {

		/**
		 * Plugin slug for reference.
		 *
		 * @var string
		 */
		public $source_slug = 'affiliate-wp';

		/**
		 * Singleton instance.
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Get the singleton instance.
		 *
		 * @return self
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
			// Run migration only if source plugin is active.
			if ( afwc_is_plugin_active( 'affiliate-wp/affiliate-wp.php' ) ) {
				parent::__construct();
			}
		}

		/**
		 * Get the setup array for the migration from Affiliate WP.
		 *
		 * @return array Setup array.
		 */
		public function get_setups() {

			if ( ! function_exists( 'affiliate_wp' ) || empty( affiliate_wp()->settings ) ) {
				return array();
			}

			$affwp_settings = affiliate_wp()->settings;

			if ( ! is_callable( array( $affwp_settings, 'get' ) ) ) {
				return array();
			}

			return array(
				array(
					'callback' => array( $this, 'update_option' ),
					'args'     => array(
						'afwc_pname',
						$affwp_settings->get( 'referral_var' ),
					),
				),
				array(
					'callback' => array( $this, 'update_option' ),
					'args'     => array(
						'afwc_use_pretty_referral_links',
						$affwp_settings->get( 'referral_pretty_urls' ),
					),
				),
				array(
					'callback' => array( $this, 'update_option' ),
					'args'     => array(
						'afwc_credit_affiliate',
						in_array( $affwp_settings->get( 'referral_credit_last' ), array( 1, '1' ), true ) ? 'last' : 'first',
					),
				),
				array(
					'callback' => array( $this, 'update_option' ),
					'args'     => array(
						'afwc_auto_add_affiliate',
						$affwp_settings->get( 'require_approval' ),
					),
				),
			);
		}

		/**
		 * Retrieve affiliates from the Affiliate WP database that have not been migrated.
		 *
		 * @throws \Exception If any error during the process.
		 * @return array Affiliates data to migrate.
		 */
		public function get_affiliates() {
			global $wpdb;

			$limit = $this->get_batch_limit();
			$limit = ! empty( $limit ) ? intval( $limit ) : -1;

			$data = array();

			try {
				// Get the default referral format option.
				$affwp_settings = get_option( 'affwp_settings', array() );
				$ref_format     = ! empty( $affwp_settings['referral_format'] ) ? $affwp_settings['referral_format'] : '';

				if ( 'username' === $ref_format ) {
					// Include username in results.
					if ( $limit > 0 ) {
						$data = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT 
									aff.affiliate_id       AS affiliate_id, 
									aff.user_id            AS user_id, 
									u.user_login           AS identifier,
									aff.date_registered    AS signup_date
								FROM {$wpdb->prefix}affiliate_wp_affiliates AS aff
								INNER JOIN {$wpdb->users} AS u
									ON u.ID = aff.user_id
								LEFT JOIN {$wpdb->usermeta} AS um
									ON um.user_id = aff.user_id
									AND um.meta_key = 'afwc_migrated_affiliate_id'
								WHERE um.umeta_id IS NULL
									AND aff.status = 'active'
								LIMIT %d",
								$limit
							),
							'ARRAY_A'
						);
					} else {
						$data = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							"SELECT 
								aff.affiliate_id       AS affiliate_id, 
								aff.user_id            AS user_id, 
								u.user_login           AS identifier,
								aff.date_registered    AS signup_date
							FROM {$wpdb->prefix}affiliate_wp_affiliates AS aff
							INNER JOIN {$wpdb->users} AS u
								ON u.ID = aff.user_id
							LEFT JOIN {$wpdb->usermeta} AS um
								ON um.user_id = aff.user_id
								AND um.meta_key = 'afwc_migrated_affiliate_id'
							WHERE um.umeta_id IS NULL
								AND aff.status = 'active'",
							'ARRAY_A'
						);
					}
				} elseif ( $limit > 0 ) {
					$data = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT 
								aff.affiliate_id       AS affiliate_id, 
								aff.user_id            AS user_id, 
								aff.date_registered    AS signup_date
							FROM {$wpdb->prefix}affiliate_wp_affiliates AS aff
							LEFT JOIN {$wpdb->usermeta} AS um
								ON um.user_id = aff.user_id
								AND um.meta_key = 'afwc_migrated_affiliate_id'
							WHERE um.umeta_id IS NULL
								AND aff.status = 'active'
							LIMIT %d",
							$limit
						),
						'ARRAY_A'
					);
				} else {
					$data = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						"SELECT 
							aff.affiliate_id       AS affiliate_id, 
							aff.user_id            AS user_id, 
							aff.date_registered    AS signup_date
						FROM {$wpdb->prefix}affiliate_wp_affiliates AS aff
						LEFT JOIN {$wpdb->usermeta} AS um
							ON um.user_id = aff.user_id
							AND um.meta_key = 'afwc_migrated_affiliate_id'
						WHERE um.umeta_id IS NULL
							AND aff.status = 'active'",
						'ARRAY_A'
					);
				}

				if ( ! empty( $wpdb->last_error ) ) {
					throw new \Exception( $wpdb->last_error );
				}
			} catch ( \Exception $e ) {
				\Affiliate_For_WooCommerce::log_error(
					__METHOD__,
					is_callable( array( $e, 'getMessage' ) ) ? $e->getMessage() : ''
				);
			}

			return ! empty( $data ) ? $data : array();
		}

		/**
		 * Get user meta mapping for migration.
		 *
		 * @return array
		 */
		public function get_user_meta_map() {
			return array();
		}

		/**
		 * Stop tracking in the source plugin.
		 *
		 * @return void
		 */
		public function stop_tracking() {
			// Prevent visitor tracking.
			add_filter( 'affwp_tracking_skip_track_visit', '__return_true' );
			// Prevent referral tracking.
			add_filter( 'affwp_was_referred', '__return_false' );
			// Prevent referral tracking. It's a fallback hooks for `affwp_was_referred`.
			add_filter( 'affwp_woocommerce_add_pending_referral_amount', '__return_zero' );
		}

		/**
		 * Get affiliate ID from cookie if available.
		 *
		 * @return int The affiliate ID.
		 */
		public function get_affiliate_from_cookie() {
			return ! empty( $_COOKIE['affwp_ref'] ) ? intval( $_COOKIE['affwp_ref'] ) : 0;
		}

		/**
		 * Get affiliate ID for an order if available.
		 *
		 * @param int $order_id WooCommerce order ID.
		 *
		 * @return int The affiliate ID.
		 */
		public function get_affiliate_from_order( $order_id = 0 ) {

			if ( empty( $order_id ) ) {
				return 0;
			}

			global $wpdb;

			try {
				$result = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT affiliate_id FROM {$wpdb->prefix}affiliate_wp_referrals WHERE reference = %d",
						$order_id
					)
				);

				return ! empty( $result ) ? intval( $result ) : 0;

			} catch ( \Exception $e ) {
				\Affiliate_For_WooCommerce::log_error( __METHOD__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			}

			return 0;
		}
	}
}
