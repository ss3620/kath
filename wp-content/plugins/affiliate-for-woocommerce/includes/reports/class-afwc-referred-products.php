<?php
/**
 * Main class for Affiliate's referred products.
 *
 * @package     affiliate-for-woocommerce/includes/reports/
 * @since       8.42.0
 * @version     1.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Referred_Products' ) ) {

	/**
	 * Main class for Affiliate's referred products reports.
	 */
	class AFWC_Referred_Products {

		/**
		 * Variable to hold affiliate id.
		 *
		 * @var array $affiliate_id
		 */
		public $affiliate_id = 0;

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
			$this->affiliate_id = ! empty( $args['affiliate_id'] ) ? $args['affiliate_id'] : 0;
			$this->from         = ! empty( $args['from'] ) ? $args['from'] : '';
			$this->to           = ! empty( $args['to'] ) ? $args['to'] : '';
			$this->batch_limit  = ! empty( $args['limit'] ) ? $args['limit'] : 1;
			$this->start_limit  = ! empty( $args['start_limit'] ) ? $args['start_limit'] : 0;
		}

		/**
		 * Retrieves formatted report data based on provided arguments.
		 *
		 * @param array $args Optional. Arguments to customize report output.
		 *
		 * @return array Array of structured report entries.
		 */
		public function get_reports( $args = array() ) {
			$raw_data = $this->get_data( $args );

			if ( empty( $raw_data ) ) {
				return array();
			}

			return $raw_data;
		}

		/**
		 * Method to get the products raw data.
		 *
		 * @param array $args Optional. Arguments to customize report output.
		 *
		 * @return array The products data.
		 */
		public function get_data( $args = array() ) {
			global $wpdb;

			$afwc_excluded_products = afwc_get_storewide_excluded_products();

			$option_order_status = 'afwc_order_statuses_' . uniqid();
			update_option( $option_order_status, implode( ',', afwc_get_prefixed_order_statuses() ), 'no' );

			// TODO:: Need to check query for limits and get products properly.
			if ( afwc_can_convert_currency() ) {
				if ( ! empty( $this->from ) && ! empty( $this->to ) ) {
					$products_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT CONCAT(fpid,'_',fvid) as p_vid,
								fpid as pid,
								fvid as vid,
								IFNULL(SUM(fqty), 0) as tot_qty
							FROM
								(SELECT
									CASE WHEN @order_item_id != order_item_id THEN @pid := -1 END,
									CASE WHEN @order_item_id != order_item_id THEN @vid := -1 END,
									CASE WHEN @order_item_id != order_item_id THEN @qty := -1 END,
									@order_item_id := order_item_id as foid,
									@pid := CASE WHEN pid > -1 THEN pid ELSE @pid END as fpid,
									@vid := CASE WHEN vid > -1 THEN vid ELSE @vid END as fvid,
									@qty := CASE WHEN qty > -1 THEN qty ELSE @qty END as fqty
								FROM(
									SELECT woim.order_item_id as order_item_id,
										IFNULL(CASE WHEN woim.meta_key = '_product_id' THEN woim.meta_value END, -1) as pid,
										IFNULL(CASE WHEN woim.meta_key = '_variation_id' THEN woim.meta_value END, -1) as vid,
										IFNULL(CASE WHEN woim.meta_key = '_qty' THEN woim.meta_value END, -1) as qty
									FROM {$wpdb->prefix}woocommerce_order_items AS woi
										JOIN {$wpdb->prefix}afwc_referrals AS afwcr
											ON (woi.order_id = afwcr.post_id
												AND woi.order_item_type = 'line_item'
												AND afwcr.affiliate_id = %d AND afwcr.status != %s)
										JOIN {$wpdb->prefix}woocommerce_order_itemmeta as woim
											ON(woim.order_item_id = woi.order_item_id
												AND woim.meta_key IN ('_product_id', '_variation_id', '_qty'))
									WHERE (afwcr.datetime BETWEEN %s AND %s ) AND FIND_IN_SET( CONVERT(afwcr.order_status USING %s) COLLATE %s, (SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s ))
								) as temp,
								(SELECT @order_item_id := 0,
									@pid := 0,
									@vid := 0,
									@qty := 0
								) as temp_variable
							) as t1
							WHERE fpid > -1
								AND fvid > -1
								AND fqty > -1
							GROUP BY p_vid
							ORDER BY tot_qty DESC
							LIMIT %d, %d",
							$this->affiliate_id,
							AFWC_REFERRAL_STATUS_DRAFT,
							$this->from,
							$this->to,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$option_order_status,
							$this->start_limit,
							$this->batch_limit
						),
						'ARRAY_A'
					);
				} else {
					$products_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT CONCAT(fpid,'_',fvid) as p_vid,
								fpid as pid,
								fvid as vid,
								IFNULL(SUM(fqty), 0) as tot_qty
							FROM
								(SELECT
									CASE WHEN @order_item_id != order_item_id THEN @pid := -1 END,
									CASE WHEN @order_item_id != order_item_id THEN @vid := -1 END,
									CASE WHEN @order_item_id != order_item_id THEN @qty := -1 END,
									@order_item_id := order_item_id as foid,
									@pid := CASE WHEN pid > -1 THEN pid ELSE @pid END as fpid,
									@vid := CASE WHEN vid > -1 THEN vid ELSE @vid END as fvid,
									@qty := CASE WHEN qty > -1 THEN qty ELSE @qty END as fqty
								FROM(
									SELECT woim.order_item_id as order_item_id,
										IFNULL(CASE WHEN woim.meta_key = '_product_id' THEN woim.meta_value END, -1) as pid,
										IFNULL(CASE WHEN woim.meta_key = '_variation_id' THEN woim.meta_value END, -1) as vid,
										IFNULL(CASE WHEN woim.meta_key = '_qty' THEN woim.meta_value END, -1) as qty
									FROM {$wpdb->prefix}woocommerce_order_items AS woi
										JOIN {$wpdb->prefix}afwc_referrals AS afwcr
											ON (woi.order_id = afwcr.post_id
												AND woi.order_item_type = 'line_item'
												AND afwcr.affiliate_id = %d AND afwcr.status != %s)
										JOIN {$wpdb->prefix}woocommerce_order_itemmeta as woim
											ON(woim.order_item_id = woi.order_item_id
												AND woim.meta_key IN ('_product_id', '_variation_id', '_qty'))
									WHERE FIND_IN_SET( CONVERT(afwcr.order_status USING %s) COLLATE %s, (SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s ))
								) as temp,
								(SELECT @order_item_id := 0,
									@pid := 0,
									@vid := 0,
									@qty := 0
								) as temp_variable
							) as t1
							WHERE fpid > -1
								AND fvid > -1
								AND fqty > -1
							GROUP BY p_vid
							ORDER BY tot_qty DESC
							LIMIT %d, %d",
							$this->affiliate_id,
							AFWC_REFERRAL_STATUS_DRAFT,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$option_order_status,
							$this->start_limit,
							$this->batch_limit
						),
						'ARRAY_A'
					);
				}
			} elseif ( ! empty( $this->from ) && ! empty( $this->to ) ) {
					$products_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT CONCAT(fpid,'_',fvid) as p_vid,
								fpid as pid,
								fvid as vid,
								IFNULL(SUM(fqty), 0) as tot_qty,
								IFNULL(SUM(ftot), 0) as tot_sales
							FROM
								(SELECT
									CASE WHEN @order_item_id != order_item_id THEN @pid := -1 END,
									CASE WHEN @order_item_id != order_item_id THEN @vid := -1 END,
									CASE WHEN @order_item_id != order_item_id THEN @qty := -1 END,
									CASE WHEN @order_item_id != order_item_id THEN @tot := -1 END,
									@order_item_id := order_item_id as foid,
									@pid := CASE WHEN pid > -1 THEN pid ELSE @pid END as fpid,
									@vid := CASE WHEN vid > -1 THEN vid ELSE @vid END as fvid,
									@qty := CASE WHEN qty > -1 THEN qty ELSE @qty END as fqty,
									@tot := CASE WHEN tot > -1 THEN tot ELSE @tot END as ftot
								FROM(
									SELECT woim.order_item_id as order_item_id,
										IFNULL(CASE WHEN woim.meta_key = '_product_id' THEN woim.meta_value END, -1) as pid,
										IFNULL(CASE WHEN woim.meta_key = '_variation_id' THEN woim.meta_value END, -1) as vid,
										IFNULL(CASE WHEN woim.meta_key = '_line_total' THEN woim.meta_value END, -1) as tot,
										IFNULL(CASE WHEN woim.meta_key = '_qty' THEN woim.meta_value END, -1) as qty
									FROM {$wpdb->prefix}woocommerce_order_items AS woi
										JOIN {$wpdb->prefix}afwc_referrals AS afwcr
											ON (woi.order_id = afwcr.post_id
												AND woi.order_item_type = 'line_item'
												AND afwcr.affiliate_id = %d AND afwcr.status != %s)
										JOIN {$wpdb->prefix}woocommerce_order_itemmeta as woim
											ON(woim.order_item_id = woi.order_item_id
												AND woim.meta_key IN ('_product_id', '_variation_id', '_line_total', '_qty'))
									WHERE (afwcr.datetime BETWEEN %s AND %s ) AND FIND_IN_SET( CONVERT(afwcr.order_status USING %s) COLLATE %s, (SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s ))
								) as temp,
								(SELECT @order_item_id := 0,
									@pid := 0,
									@vid := 0,
									@qty := 0,
									@tot := 0
								) as temp_variable
							) as t1
							WHERE fpid > -1
								AND fvid > -1
								AND fqty > -1
								AND ftot > -1
							GROUP BY p_vid
							ORDER BY tot_sales DESC, tot_qty DESC
							LIMIT %d, %d",
							$this->affiliate_id,
							AFWC_REFERRAL_STATUS_DRAFT,
							$this->from,
							$this->to,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$option_order_status,
							$this->start_limit,
							$this->batch_limit
						),
						'ARRAY_A'
					);
			} else {
				$products_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT CONCAT(fpid,'_',fvid) as p_vid,
								fpid as pid,
								fvid as vid,
								IFNULL(SUM(fqty), 0) as tot_qty,
								IFNULL(SUM(ftot), 0) as tot_sales
							FROM
								(SELECT
									CASE WHEN @order_item_id != order_item_id THEN @pid := -1 END,
									CASE WHEN @order_item_id != order_item_id THEN @vid := -1 END,
									CASE WHEN @order_item_id != order_item_id THEN @qty := -1 END,
									CASE WHEN @order_item_id != order_item_id THEN @tot := -1 END,
									@order_item_id := order_item_id as foid,
									@pid := CASE WHEN pid > -1 THEN pid ELSE @pid END as fpid,
									@vid := CASE WHEN vid > -1 THEN vid ELSE @vid END as fvid,
									@qty := CASE WHEN qty > -1 THEN qty ELSE @qty END as fqty,
									@tot := CASE WHEN tot > -1 THEN tot ELSE @tot END as ftot
								FROM(
									SELECT woim.order_item_id as order_item_id,
										IFNULL(CASE WHEN woim.meta_key = '_product_id' THEN woim.meta_value END, -1) as pid,
										IFNULL(CASE WHEN woim.meta_key = '_variation_id' THEN woim.meta_value END, -1) as vid,
										IFNULL(CASE WHEN woim.meta_key = '_line_total' THEN woim.meta_value END, -1) as tot,
										IFNULL(CASE WHEN woim.meta_key = '_qty' THEN woim.meta_value END, -1) as qty
									FROM {$wpdb->prefix}woocommerce_order_items AS woi
										JOIN {$wpdb->prefix}afwc_referrals AS afwcr
											ON (woi.order_id = afwcr.post_id
												AND woi.order_item_type = 'line_item'
												AND afwcr.affiliate_id = %d AND afwcr.status != %s)
										JOIN {$wpdb->prefix}woocommerce_order_itemmeta as woim
											ON(woim.order_item_id = woi.order_item_id
												AND woim.meta_key IN ('_product_id', '_variation_id', '_line_total', '_qty'))
									WHERE FIND_IN_SET( CONVERT(afwcr.order_status USING %s) COLLATE %s, (SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s ))
								) as temp,
								(SELECT @order_item_id := 0,
									@pid := 0,
									@vid := 0,
									@qty := 0,
									@tot := 0
								) as temp_variable
							) as t1
							WHERE fpid > -1
								AND fvid > -1
								AND fqty > -1
								AND ftot > -1
							GROUP BY p_vid
							ORDER BY tot_sales DESC, tot_qty DESC
							LIMIT %d, %d",
						$this->affiliate_id,
						AFWC_REFERRAL_STATUS_DRAFT,
						AFWC_SQL_CHARSET,
						AFWC_SQL_COLLATION,
						AFWC_SQL_CHARSET,
						AFWC_SQL_COLLATION,
						$option_order_status,
						$this->start_limit,
						$this->batch_limit
					),
					'ARRAY_A'
				);
			}

			$products    = array();
			$product_ids = array();
			if ( ! empty( $products_result ) ) {
				// get the product id name map.
				$product_ids = array_map(
					function ( $res ) {
						$product_id = ! empty( $res['vid'] ) ? $res['vid'] : $res['pid'];
						return $product_id;
					},
					$products_result
				);

				$option_prod_ids = 'afwc_prod_ids_' . uniqid();
				update_option( $option_prod_ids, implode( ',', $product_ids ), 'no' );
				$prod_res = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT ID, post_title
						FROM {$wpdb->prefix}posts
						WHERE FIND_IN_SET( ID, ( SELECT option_value FROM {$wpdb->prefix}options WHERE option_name = %s ) )",
						$option_prod_ids
					),
					'ARRAY_A'
				);
				foreach ( $prod_res as $res ) {
					$prod_id_name_map[ $res['ID'] ] = $res['post_title'];
				}

				$products = array();
				foreach ( $products_result as $result ) {
					if ( in_array( $result['pid'], $afwc_excluded_products, true ) || in_array( $result['vid'], $afwc_excluded_products, true ) ) {
						continue;
					}
					// The product name will be blank if the product ID or variation ID is unavailable or if the product is deleted.
					$product_name = ( ! empty( $prod_id_name_map[ $result['vid'] ] ) )
						? $prod_id_name_map[ $result['vid'] ]
						: ( ( ! empty( $prod_id_name_map[ $result['pid'] ] ) ) ? $prod_id_name_map[ $result['pid'] ] : '' );

					$products[ $result['p_vid'] ]['product'] = $product_name;
					$products[ $result['p_vid'] ]['qty']     = $result['tot_qty'];
					if ( isset( $result['tot_sales'] ) ) {
						$products[ $result['p_vid'] ]['sales'] = ! empty( $result['tot_sales'] ) ? $result['tot_sales'] : 0.00;
					}
				}

				delete_option( $option_prod_ids );
			}

			delete_option( $option_order_status );

			/**
			 * Filter to modify referred products data.
			 *
			 * @param array $products The referred products data.
			 * @param array $args The arguments.
			 *
			 * @since 8.42.0
			 */
			return apply_filters( 'afwc_products_result', $products, $args );
		}
	}
}
