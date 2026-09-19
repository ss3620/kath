<?php
/**
 * Main class for Payout History.
 *
 * @package     affiliate-for-woocommerce/includes/reports/
 * @since       8.42.0
 * @version     1.0.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Payout_History' ) ) {

	/**
	 * Main class for Payout History reports.
	 */
	class AFWC_Payout_History {

		/**
		 * Variable to hold affiliate ids.
		 *
		 * @var array $affiliate_ids
		 */
		public $affiliate_ids = array();

		/**
		 * Variable to hold the from date.
		 *
		 * @var string $from
		 */
		public $from = '';

		/**
		 * Variable to hold the to date.
		 *
		 * @var string $to
		 */
		public $to = '';

		/**
		 * Variable to hold batch limit per request.
		 *
		 * @var int $batch_limit
		 */
		public $batch_limit = 1;

		/**
		 * Variable to hold batch start per request.
		 *
		 * @var int $start_limit
		 */
		public $start_limit = 0;

		/**
		 * Constructor.
		 *
		 * @param array $args Arguments to initialize the object.
		 */
		public function __construct( $args = array() ) {
			$this->affiliate_ids = ! empty( $args['affiliate_id'] )
				? array( $args['affiliate_id'] )
				: ( ! empty( $args['affiliate_ids'] ) ? $args['affiliate_ids'] : array() );
			$this->from          = ! empty( $args['from'] ) ? $args['from'] : '';
			$this->to            = ! empty( $args['to'] ) ? $args['to'] : '';
			$this->batch_limit   = ! empty( $args['limit'] ) ? intval( $args['limit'] ) : 1;
			$this->start_limit   = ! empty( $args['start_limit'] ) ? intval( $args['start_limit'] ) : 0;
		}

		/**
		 * Retrieves formatted report data based on provided arguments.
		 *
		 * @return array Array of structured report entries.
		 */
		public function get_reports() {
			$raw_data = $this->get_data();

			if ( empty( $raw_data ) ) {
				return array();
			}

			return $raw_data;
		}

		/**
		 * Method to get the payout history raw data.
		 *
		 * @return array The payout history data.
		 */
		public function get_data() {
			global $wpdb;

			$affiliates_payout_history = array();

			if ( ! empty( $this->affiliate_ids ) ) {
				if ( 1 === count( $this->affiliate_ids ) ) {
					if ( ! empty( $this->from ) && ! empty( $this->to ) ) {
						$affiliates_payout_history_results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT payouts.payout_id,
								DATE_FORMAT( CONVERT_TZ( payouts.datetime, '+00:00', %s ), %s ) as datetime,
								payouts.amount AS amount,
								payouts.currency AS currency,
								payouts.payment_gateway AS method,
								payouts.payout_notes
							FROM {$wpdb->prefix}afwc_payouts AS payouts
							WHERE payouts.affiliate_id = %d
								AND payouts.datetime BETWEEN %s AND %s 
							ORDER BY payouts.datetime DESC
							LIMIT %d,%d",
								AFWC_TIMEZONE_STR,
								'%d-%b-%Y',
								current( $this->affiliate_ids ),
								$this->from,
								$this->to,
								$this->start_limit,
								$this->batch_limit
							),
							'ARRAY_A'
						);
					} else {
						$affiliates_payout_history_results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT payouts.payout_id,
								DATE_FORMAT( CONVERT_TZ( payouts.datetime, '+00:00', %s ), %s ) as datetime,
								payouts.amount AS amount,
								payouts.currency AS currency,
								payouts.payment_gateway AS method,
								payouts.payout_notes
							FROM {$wpdb->prefix}afwc_payouts AS payouts
							WHERE payouts.affiliate_id = %d
							ORDER BY payouts.datetime DESC
							LIMIT %d,%d",
								AFWC_TIMEZONE_STR,
								'%d-%b-%Y',
								current( $this->affiliate_ids ),
								$this->start_limit,
								$this->batch_limit
							),
							'ARRAY_A'
						);
					}
				} else {
					$option_nm = 'afwc_payout_history_affiliate_ids_' . uniqid();
					update_option( $option_nm, implode( ',', $this->affiliate_ids ), 'no' );

					if ( ! empty( $this->from ) && ! empty( $this->to ) ) {
						$affiliates_payout_history_results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT payouts.payout_id,
								DATE_FORMAT( CONVERT_TZ( payouts.datetime, '+00:00', %s ), %s ) as datetime,
								payouts.amount AS amount,
								payouts.currency AS currency,
								payouts.payment_gateway AS method,
								payouts.payout_notes
							FROM {$wpdb->prefix}afwc_payouts AS payouts
							WHERE FIND_IN_SET ( payouts.affiliate_id, ( SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s ) )
								AND payouts.datetime BETWEEN %s AND %s 
							ORDER BY payouts.datetime DESC
							LIMIT %d,%d",
								AFWC_TIMEZONE_STR,
								'%d-%b-%Y',
								$option_nm,
								$this->from,
								$this->to,
								$this->start_limit,
								$this->batch_limit
							),
							'ARRAY_A'
						);
					} else {
						$affiliates_payout_history_results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT payouts.payout_id,
								DATE_FORMAT( CONVERT_TZ( payouts.datetime, '+00:00', %s ), %s ) as datetime,
								payouts.amount AS amount,
								payouts.currency AS currency,
								payouts.payment_gateway AS method,
								payouts.payout_notes
							FROM {$wpdb->prefix}afwc_payouts AS payouts
							WHERE FIND_IN_SET ( payouts.affiliate_id, ( SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s ) )
							ORDER BY payouts.datetime DESC,
							LIMIT %d,%d",
								AFWC_TIMEZONE_STR,
								'%d-%b-%Y',
								$option_nm,
								$this->start_limit,
								$this->batch_limit
							),
							'ARRAY_A'
						);
					}

					delete_option( $option_nm );
				}
			} elseif ( ! empty( $this->from ) && ! empty( $this->to ) ) {
				$affiliates_payout_history_results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT payouts.payout_id,
						DATE_FORMAT( CONVERT_TZ( payouts.datetime, '+00:00', %s ), %s ) as datetime,
						payouts.amount AS amount,
						payouts.currency AS currency,
						payouts.payment_gateway AS method,
						payouts.payout_notes
					FROM {$wpdb->prefix}afwc_payouts AS payouts
					WHERE payouts.affiliate_id != %d
						AND payouts.datetime BETWEEN %s AND %s 
					ORDER BY payouts.datetime DESC,
					LIMIT %d,%d",
						AFWC_TIMEZONE_STR,
						'%d-%b-%Y',
						0,
						$this->from,
						$this->to,
						$this->start_limit,
						$this->batch_limit
					),
					'ARRAY_A'
				);
			} else {
				$affiliates_payout_history_results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT payouts.payout_id,
						DATE_FORMAT( CONVERT_TZ( payouts.datetime, '+00:00', %s ), %s ) as datetime,
						payouts.amount AS amount,
						payouts.currency AS currency,
						payouts.payment_gateway AS method,
						payouts.payout_notes
					FROM {$wpdb->prefix}afwc_payouts AS payouts
					WHERE payouts.affiliate_id != %d
					ORDER BY payouts.datetime DESC
					LIMIT %d,%d",
						AFWC_TIMEZONE_STR,
						'%d-%b-%Y',
						0,
						$this->start_limit,
						$this->batch_limit
					),
					'ARRAY_A'
				);
			}

			$payout_ids           = array();
			$payout_order_details = array();
			if ( ! empty( $affiliates_payout_history_results ) ) {

				foreach ( $affiliates_payout_history_results as $result ) {
					$affiliates_payout_history[] = $result;
					$payout_ids[]                = $result['payout_id'];
				}

				if ( ! empty( $payout_ids ) ) {
					if ( is_callable( 'afwc_is_hpos_enabled' ) && afwc_is_hpos_enabled() ) {
						$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT po.payout_id,
								IFNULL( COUNT( po.post_id ), 0 ) AS order_count,
								DATE_FORMAT( CONVERT_TZ( MIN( wco.date_created_gmt ), '+00:00', %s ), %s ) AS from_date,
								DATE_FORMAT( CONVERT_TZ( MAX( wco.date_created_gmt ), '+00:00', %s ), %s ) AS `to_date`
							FROM {$wpdb->prefix}afwc_payout_orders AS po
								JOIN {$wpdb->prefix}wc_orders AS wco
									ON(wco.id = po.post_id
										AND wco.type = 'shop_order')
							WHERE po.payout_id IN (" . implode( ',', array_fill( 0, count( $payout_ids ), '%d' ) ) . ') 
							GROUP BY po.payout_id',
								array_merge( array( AFWC_TIMEZONE_STR, '%d-%b-%Y', AFWC_TIMEZONE_STR, '%d-%b-%Y' ), $payout_ids )
							),
							'ARRAY_A'
						);
					} else {
						$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT po.payout_id,
								IFNULL( COUNT( po.post_id ), 0 ) AS order_count,
								DATE_FORMAT( MIN( p.post_date ), '%%d-%%b-%%Y' ) AS from_date,
								DATE_FORMAT( MAX( p.post_date ), '%%d-%%b-%%Y' ) AS `to_date`
							FROM {$wpdb->prefix}afwc_payout_orders AS po
								JOIN {$wpdb->prefix}posts AS p
									ON(p.ID = po.post_id
										AND p.post_type = 'shop_order')
							WHERE po.payout_id IN (" . implode( ',', array_fill( 0, count( $payout_ids ), '%d' ) ) . ')
							GROUP BY po.payout_id',
								$payout_ids
							),
							'ARRAY_A'
						);
					}

					if ( ! empty( $results ) ) {
						foreach ( $results as $detail ) {
							$payout_order_details[ $detail['payout_id'] ] = array(
								'referral_count' => $detail['order_count'],
								'from_date'      => $detail['from_date'],
								'to_date'        => $detail['to_date'],
							);
						}
					}

					foreach ( $affiliates_payout_history as $key => $payout ) {
						$affiliates_payout_history[ $key ] = ( ! empty( $payout_order_details[ $payout['payout_id'] ] ) && is_array( $payout_order_details[ $payout['payout_id'] ] ) ) ? array_merge( $affiliates_payout_history[ $key ], $payout_order_details[ $payout['payout_id'] ] ) : $affiliates_payout_history_results[ $key ];
					}
				}
			}

			/**
			 * Filter to modify payout history data.
			 *
			 * @param array $affiliates_payout_history The payout history data.
			 * @param array $payout_order_details The payout order details data.
			 *
			 * @since 8.42.0
			 */
			return apply_filters( 'afwc_payout_history', $affiliates_payout_history, $payout_order_details );
		}
	}
}
