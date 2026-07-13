<?php
/**
 * Class to migrate order_total into afwc_referrals table.
 *
 * @package     affiliate-for-woocommerce/includes/upgrades/
 * @since       9.0.0
 * @version     1.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Background_Process', false ) ) {
	include_once AFWC_PLUGIN_DIRPATH . '/includes/abstracts/class-afwc-background-process.php';
}

if ( ! class_exists( 'AFWC_Order_Total_Migration' ) && class_exists( 'AFWC_Background_Process' ) ) {

	/**
	 * Class to handle order total migration in background process.
	 */
	class AFWC_Order_Total_Migration extends AFWC_Background_Process {

		/**
		 * Singleton instance.
		 *
		 * @var self|null
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
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
			$this->batch_limit = 50;
			$this->action      = 'afwc_order_total_migration';

			add_action( $this->action . '_process_completed', array( $this, 'find_mismatched_order_currency' ) );

			parent::__construct();
		}

		/**
		 * Get the option key used to store the cursor.
		 *
		 * @return string
		 */
		private function get_cursor_option_key() {
			return $this->action . '_last_id';
		}

		/**
		 * Advance the cursor down by batch_limit, floored at 0.
		 *
		 * @param int $current_id Current upper-bound referral_id.
		 * @return int New cursor value.
		 */
		private function advance_cursor( $current_id = 0 ) {
			$new_cursor = max( 0, (int) $current_id - $this->batch_limit );
			update_option( $this->get_cursor_option_key(), $new_cursor, false );
			return $new_cursor;
		}

		/**
		 * Provide the next batch item to the background process.
		 *
		 * @return array
		 */
		public function get_remaining_items() {
			$cursor_key = $this->get_cursor_option_key();
			$cursor     = get_option( $cursor_key );

			// First run: initialize cursor from MAX(referral_id).
			if ( false === $cursor ) {
				global $wpdb;

				$max_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					"SELECT MAX(referral_id) FROM {$wpdb->prefix}afwc_referrals"
				);

				if ( empty( $max_id ) ) {
					Affiliate_For_WooCommerce::log(
						'info',
						_x( 'Order total migration skipped: afwc_referrals table is empty.', 'Logger for empty table skip in order total migration', 'affiliate-for-woocommerce' )
					);
					return array();
				}

				update_option( $cursor_key, $max_id, false );

				// Set a separate option to track to know the migration had done till which referral_id.
				if ( false === get_option( '_afwc_order_total_migrated_till' ) ) {
					update_option( '_afwc_order_total_migrated_till', $max_id, false );
				}

				Affiliate_For_WooCommerce::log(
					'info',
					sprintf(
						/* translators: %d: max referral ID */
						_x( 'Order total migration started. Max referral_id: %d', 'Logger for migration start in order total migration', 'affiliate-for-woocommerce' ),
						$max_id
					)
				);

				// Range for the first batch, from (max_id - batch_limit) to (max_id), as it is in descending order.
				return array(
					'start' => max( 0, $max_id - $this->batch_limit ),
					'end'   => $max_id,
				);
			}

			$cursor = (int) $cursor;

			// Cursor at 0 means every range has been visited - we are done.
			if ( 0 === $cursor ) {
				return array();
			}

			// Move cursor down by 1 to avoid processing the same referral_id again as the range is inclusive of end.
			--$cursor;

			// Range for the next batch, from (cursor - batch_limit) to (cursor), as it is in descending order.
			return array(
				'start' => max( 0, $cursor - $this->batch_limit ),
				'end'   => $cursor,
			);
		}

		/**
		 * Execute a single migration batch.
		 *
		 * Uses LEFT JOIN + COALESCE to set order_total in one query:
		 *   - Matched rows  → real order total from WooCommerce.
		 *   - Orphaned rows → 0 (order no longer exists in WooCommerce).
		 *
		 * @param array $args { 'start' => int, 'end' => int } range of referral_id for this batch, inclusive. Used with BETWEEN in the query.
		 *
		 * @throws Exception On DB error or failed health check.
		 */
		public function task( $args = array() ) {
			if ( ! is_array( $args ) || ! array_key_exists( 'start', $args ) || ! array_key_exists( 'end', $args ) ) {
				return false;
			}

			global $wpdb;

			$start = (int) $args['start'];
			$end   = (int) $args['end'];

			$this->advance_cursor( $end );

			if ( afwc_is_hpos_enabled() ) {
				$updated = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						"UPDATE {$wpdb->prefix}afwc_referrals AS r
						LEFT JOIN {$wpdb->prefix}wc_orders AS o
							ON o.id = r.post_id
						SET r.order_total = COALESCE( o.total_amount, 0 )
						WHERE r.referral_id BETWEEN %d AND %d
							AND r.order_total IS NULL",
						$start,
						$end
					)
				);
			} else {
				$updated = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->prepare(
						"UPDATE {$wpdb->prefix}afwc_referrals AS r
						LEFT JOIN {$wpdb->prefix}postmeta AS pm
							ON ( pm.post_id = r.post_id AND pm.meta_key = '_order_total' )
						SET r.order_total = COALESCE( pm.meta_value, 0 )
						WHERE r.referral_id BETWEEN %d AND %d
							AND r.order_total IS NULL",
						$start,
						$end
					)
				);
			}

			if ( false === $updated ) {
				throw new Exception(
					esc_html(
						sprintf(
							/* translators: %d: last referral ID processed */
							_x( 'Order total migration failed. Last referral ID: %d.', 'Logger for migration batch failure', 'affiliate-for-woocommerce' ),
							$end
						)
					)
				);
			}

			Affiliate_For_WooCommerce::log(
				'info',
				sprintf(
					/* translators: 1: last referral ID, 2: rows updated */
					_x( 'Order total migration batch completed. Last referral ID: %1$d | Rows updated: %2$d.', 'Logger for migration batch complete in order total migration', 'affiliate-for-woocommerce' ),
					$end,
					$updated
				)
			);

			if ( ! $this->health_status() ) {
				throw new Exception(
					esc_html(
						sprintf(
							/* translators: %s: class name */
							_x( 'Order total migration halted due to health check failure in: %s', 'Logger for health check failure in order total migration', 'affiliate-for-woocommerce' ),
							__CLASS__
						)
					)
				);
			}
		}

		/**
		 * Check whether the migration is needed by verifying if there are any referrals with NULL order_total.
		 */
		public function is_migration_needed() {
			global $wpdb;

			$exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT 1
					FROM {$wpdb->prefix}afwc_referrals
					WHERE order_total IS NULL
					LIMIT 1"
			);

			return ! empty( $exists );
		}

		/**
		 * Called when the background process finishes all batches.
		 */
		public function completed() {
			parent::completed();

			delete_option( $this->get_cursor_option_key() );
			update_option( '_afwc_current_db_version', '1.4.3', 'no' );

			Affiliate_For_WooCommerce::log(
				'info',
				_x( 'Order total migration completed successfully.', 'Logger for migration completion in order total migration', 'affiliate-for-woocommerce' )
			);
		}

		/**
		 * Method to find mismatched order totals after migration is completed.
		 *
		 * It will check if there is any referral record whose order total match with the corresponding order total in WooCommerce But Currency is different.
		 * If found any then it will set an option 'afwc_found_currency_mismatch_order_affiliate_wc' to `yes`.
		 *
		 * @return void
		 *
		 * @throws Exception On DB error during the check.
		 */
		public function find_mismatched_order_currency() {
			global $wpdb;

			$date = '2026-02-08 00:00:00'; // 9th Feb 2026 is the date when we released 8.61.0 with multi-currency support.

			try {
				if ( function_exists( 'afwc_is_hpos_enabled' ) && afwc_is_hpos_enabled() ) {
					$order_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT r.post_id
							FROM {$wpdb->prefix}afwc_referrals r
							INNER JOIN {$wpdb->prefix}wc_orders w
								ON w.id = r.post_id
							WHERE r.datetime >= %s
								AND NOT (r.currency_id <=> w.currency)
								AND r.order_total = w.total_amount
							LIMIT 1",
							$date
						)
					);
				} else {
					$order_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT r.post_id
							FROM {$wpdb->prefix}afwc_referrals r
							INNER JOIN {$wpdb->postmeta} pm_total
								ON pm_total.post_id = r.post_id
								AND pm_total.meta_key = '_order_total'
							INNER JOIN {$wpdb->postmeta} pm_currency
								ON pm_currency.post_id = r.post_id
								AND pm_currency.meta_key = '_order_currency'
							WHERE r.datetime >= %s
								AND NOT (r.currency_id <=> pm_currency.meta_value)
								AND pm_total.meta_value = r.order_total
							LIMIT 1",
							$date
						)
					);
				}

				if ( ! empty( $order_id ) && is_numeric( $order_id ) ) {
					update_option( 'afwc_found_currency_mismatch_order_affiliate_wc', 'yes', 'no' );
				} else {
					throw new Exception( _x( 'No mismatched order currency found after order total migration.', 'Logger msg for no mismatched order currency found after order total migration', 'affiliate-for-woocommerce' ) );
				}
			} catch ( Exception $e ) {
				Affiliate_For_WooCommerce::log( 'info', ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			}
		}
	}
}
