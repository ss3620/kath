<?php
/**
 * Main class for Summary Email to Admin and Affiliate Manager
 *
 * @package     affiliate-for-woocommerce/includes/admin/
 * @since       8.27.0
 * @version     1.0.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Admin_Summary_Reports' ) ) {

	/**
	 * Main class for Admin and Affiliate Manager Summary Reports
	 */
	class AFWC_Admin_Summary_Reports {

		/**
		 * Variable to hold affiliate ids
		 *
		 * @var array $affiliate_ids
		 */
		public $affiliate_ids = array();

		/**
		 * From date
		 *
		 * @var string $from
		 */
		public $from = '';

		/**
		 * To date
		 *
		 * @var string $to
		 */
		public $to = '';

		/**
		 * Sales post types
		 *
		 * @var array $sales_post_types
		 */
		public $sales_post_types = array();

		/**
		 * Variable to hold paid order status.
		 *
		 * @var array $paid_order_status
		 */
		public $paid_order_status = array();

		/**
		 * Constructor
		 *
		 * @param  array  $affiliate_ids Affiliates ids.
		 * @param  string $from From date.
		 * @param  string $to To date.
		 */
		public function __construct( $affiliate_ids = array(), $from = '', $to = '' ) {
			$this->affiliate_ids = ( ! is_array( $affiliate_ids ) ) ? array( $affiliate_ids ) : $affiliate_ids;
			$this->from          = ( ! empty( $from ) ) ? gmdate( 'Y-m-d H:i:s', strtotime( $from ) ) : '';
			$this->to            = ( ! empty( $to ) ) ? gmdate( 'Y-m-d H:i:s', strtotime( $to ) ) : '';

			/**
			 * Filter to modify the sales post types for admin summary report
			 *
			 * @param array    Array of sales post types.
			 * @param array    Additional information
			 *
			 * @since 8.27.0
			 */
			$this->sales_post_types  = apply_filters( 'afwc_sales_post_types', array( 'shop_order' ) );
			$this->paid_order_status = afwc_get_paid_order_status();
		}

		/**
		 * Get the reports for admin summary email.
		 *
		 * @return array Return the array of different values of report.
		 */
		public function get_admin_summary_report_data() {
			if ( ! class_exists( 'AFWC_Admin_Affiliates' ) ) {
				$class_path = AFWC_PLUGIN_DIRPATH . '/includes/admin/class-afwc-admin-affiliates.php';
				if ( ! file_exists( $class_path ) ) {
					return array();
				}
				include_once $class_path;
			}

			$all_affiliates_data = new AFWC_Admin_Affiliates(
				array(),
				$this->from,
				$this->to,
				'',
				AFWC_CURRENCY_CODE
			);

			$date_format = get_option( 'date_format' );

			$aggregated = is_callable( array( $all_affiliates_data, 'get_commissions_customers' ) ) ? $all_affiliates_data->get_commissions_customers() : array();

			// Assign the required values to affiliate date for getting the proper net commission.
			$all_affiliates_data->paid_commissions   = floatval( ( ! empty( $aggregated['paid_commissions'] ) ) ? $aggregated['paid_commissions'] : 0 );
			$all_affiliates_data->unpaid_commissions = floatval( ( ! empty( $aggregated['unpaid_commissions'] ) ) ? $aggregated['unpaid_commissions'] : 0 );

			/**
			 * Filter to modify the top affiliates limit for admin summary report
			 *
			 * @param int      Number of top affiliates to fetch.
			 * @param array    Additional information
			 *
			 * @since 8.27.0
			 */
			$affiliates_net_sales_data = $this->get_affiliates_net_sales( array( 'limit' => apply_filters( 'afwc_top_affiliates_limit_on_admin_summary_email', 5, array( 'source' => $this ) ) ) );
			if ( ! empty( $affiliates_net_sales_data ) ) {
				foreach ( $affiliates_net_sales_data as $key => $affiliate ) {
					$affiliate_user = get_userdata( $affiliate['affiliate_id'] );
					if ( ! is_object( $affiliate_user ) || ! ( $affiliate_user instanceof WP_User ) ) {
						unset( $affiliates_net_sales_data[ $key ] );
						continue;
					}

					$affiliates_net_sales_data[ $key ]['display_name']          = ! empty( $affiliate_user->display_name ) ? $affiliate_user->display_name : 'N/A';
					$affiliates_net_sales_data[ $key ]['dashboard_routing_url'] = add_query_arg( array( 'page' => 'affiliate-for-woocommerce#!/dashboard/' . $affiliate['affiliate_id'] ), admin_url( 'admin.php' ) );
				}
			}

			$storewide_sales_data = is_callable( array( $all_affiliates_data, 'get_storewide_sales' ) ) ? $all_affiliates_data->get_storewide_sales() : array();

			$new_joined_affiliates_ids   = $this->get_new_joined_affiliates();
			$new_joined_affiliates_count = ! empty( $new_joined_affiliates_ids ) && is_array( $new_joined_affiliates_ids ) ? count( $new_joined_affiliates_ids ) : 0;

			$pending_affiliates_ids   = is_callable( array( $all_affiliates_data, 'get_affiliates_by_user_meta' ) ) ? $all_affiliates_data->get_affiliates_by_user_meta( array( 'statuses' => 'pending' ) ) : array();
			$pending_affiliates_count = ! empty( $pending_affiliates_ids ) && is_array( $pending_affiliates_ids ) ? count( $pending_affiliates_ids ) : 0;

			return array(
				'from_date'                 => ! empty( $this->from ) && ! empty( $date_format ) ? gmdate( $date_format, strtotime( get_date_from_gmt( $this->from ) ) ) : $this->from,
				'to_date'                   => ! empty( $this->to ) && ! empty( $date_format ) ? gmdate( $date_format, strtotime( get_date_from_gmt( $this->to ) ) ) : $this->to,
				'affiliates_revenue_amount' => afwc_format_price( floatval( is_callable( array( $all_affiliates_data, 'get_net_affiliates_sales' ) ) ? $all_affiliates_data->get_net_affiliates_sales() : 0 ) ),
				'site_order_total_amount'   => afwc_format_price( ! empty( $storewide_sales_data['total_sales'] ) ? $storewide_sales_data['total_sales'] : 0 ),
				'affiliates_order_count'    => is_callable( array( $all_affiliates_data, 'get_referrals_count' ) ) ? $all_affiliates_data->get_referrals_count() : 0,
				'site_order_count'          => ! empty( $storewide_sales_data['order_count'] ) ? $storewide_sales_data['order_count'] : 0,
				'paid_commissions'          => afwc_format_price( $all_affiliates_data->paid_commissions ),
				'unpaid_commissions'        => afwc_format_price( $this->get_total_unpaid_commission() ),
				'newly_joined_affiliates'   => $new_joined_affiliates_count,
				'pending_affiliates'        => $pending_affiliates_count,
				'top_performing_affiliates' => $affiliates_net_sales_data,
				'converted_urls'            => is_callable( array( $all_affiliates_data, 'get_converted_url_stats' ) )
					? $all_affiliates_data->get_converted_url_stats(
						/**
						 * Filter to modify the top referral URLs limit for admin summary report
						 *
						 * @param int      Number of top referral URLs to fetch.
						 * @param array    Additional information
						 *
						 * @since 8.27.0
						 */
						array( 'limit' => apply_filters( 'afwc_top_referral_urls_limit_on_admin_summary_email', 5, array( 'source' => $this ) ) )
					) : array(),
				'expert_tips'               => $this->get_expert_tips(),
			);
		}

		/**
		 * Method to get total unpaid commission amount of the store without affiliate status check.
		 *
		 * @return float Total unpaid commission amount.
		 */
		public function get_total_unpaid_commission() {
			global $wpdb;

			$unpaid_commissions_data = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT
						IFNULL(SUM( CASE WHEN referral.status = 'unpaid' THEN referral.amount END ), 0) AS unpaid_commissions
					FROM
						{$wpdb->prefix}afwc_referrals AS referral
					WHERE
						referral.affiliate_id != %d",
					0
				),
				'ARRAY_A'
			);

			return ( ! empty( $unpaid_commissions_data['unpaid_commissions'] ) ? floatval( $unpaid_commissions_data['unpaid_commissions'] ) : 0 );
		}


		/**
		 * Method to get newly joined affiliates in given time period.
		 *
		 * @throws Exception If any error during the process.
		 * @return array List of affiliate IDs
		 */
		public function get_new_joined_affiliates() {
			if ( empty( $this->from ) || empty( $this->to ) ) {
				return array();
			}

			global $wpdb;

			$affiliate_users = array();

			try {
				$affiliate_users = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT
							DISTINCT u.ID AS affiliate_ids
						FROM
							{$wpdb->usermeta} AS um
						JOIN
							{$wpdb->users} AS u
							ON (um.user_id = u.ID AND um.meta_key = %s)
						WHERE
							um.meta_value BETWEEN %s AND %s",
						'afwc_signup_date',
						$this->from,
						$this->to
					)
				);

				if ( ! empty( $wpdb->last_error ) ) {
					throw new Exception( $wpdb->last_error );
				}
			} catch ( Exception $e ) {
				Affiliate_For_WooCommerce::log_error( __METHOD__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			}

			return ( ! empty( $affiliate_users ) && is_array( $affiliate_users ) ) ? $affiliate_users : array();
		}

		/**
		 * Method to get affiliate wise net sales
		 *
		 * @param array $args Arguments containing 'from', 'to', and 'limit' keys.
		 * @return array Array of affiliate net sales amount
		 */
		public function get_affiliates_net_sales( $args = array() ) {
			if ( empty( $args ) || ! is_array( $args ) || empty( $this->from ) || empty( $this->to ) ) {
				return array();
			}

			// Set default limit if not provided.
			$limit = ( ! empty( $args['limit'] ) ? intval( $args['limit'] ) : 5 );

			global $wpdb;

			$ref_status = array( AFWC_REFERRAL_STATUS_PAID, AFWC_REFERRAL_STATUS_UNPAID, AFWC_REFERRAL_STATUS_REJECTED );

			$net_sales = array();

			try {
				$net_sales = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT
							ref.affiliate_id,
							IFNULL(SUM( ref.order_total ), 0) AS order_total_amount
						FROM
							{$wpdb->prefix}afwc_referrals AS ref
						WHERE
							ref.order_status IN (" . implode( ',', array_fill( 0, count( $this->paid_order_status ), '%s' ) ) . ')
							AND ref.status IN (' . implode( ',', array_fill( 0, count( $ref_status ), '%s' ) ) . ')
							AND ref.datetime BETWEEN %s AND %s
							AND ref.order_total > 0
						GROUP BY
							ref.affiliate_id
						ORDER BY
							order_total_amount DESC
						LIMIT %d',
						array_merge(
							$this->paid_order_status,
							$ref_status,
							array(
								$this->from,
								$this->to,
								$limit,
							)
						)
					),
					'ARRAY_A'
				);
			} catch ( Exception $e ) {
				Affiliate_For_WooCommerce::log_error( __METHOD__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			}

			return $net_sales;
		}

		/**
		 * Get tips to add in admin summary mail.
		 *
		 * @return array
		 */
		public function get_expert_tips() {
			$afwc_get_admin_summary_tip = get_option( 'afwc_get_admin_summary_tip', 1 );

			$tips = array(
				'tip_1'  => array(
					'tip_title'   => _x( 'The first 7 days make or break an affiliate', 'tip title for admin summary email', 'affiliate-for-woocommerce' ),
					'tip_content' => _x( "If an affiliate doesn't make their first sale within a week, there's a good chance they'll disappear. Don't let that happen!<br />Give them an easy win upfront by sending a quick-start guide, pre-made marketing materials, and personal onboarding tips.", 'tip content for admin summary email', 'affiliate-for-woocommerce' ),
				),

				'tip_2'  => array(
					'tip_title'   => _x( 'Affiliate fatigue is real', 'tip title for admin summary email', 'affiliate-for-woocommerce' ),
					'tip_content' => _x( "Here's the truth: even top affiliates get tired if promotions feel repetitive or rewards aren't exciting. And when they check out mentally, your sales dip.<br />So keep them engaged with fresh incentives, such as limited-time bonuses, surprise commission boosts, and new promotional angles. The more variety you offer, the longer they stay motivated.", 'tip content for admin summary email', 'affiliate-for-woocommerce' ),
				),

				'tip_3'  => array(
					'tip_title'   => _x( "Stay in your affiliates' inbox—Before they forget you", 'tip title for admin summary email', 'affiliate-for-woocommerce' ),
					'tip_content' => _x( "Affiliates aren't mind-readers. If you don't check in, they'll assume you don't care. A monthly email with performance insights, fresh marketing ideas, and an occasional \"Hey, keeping an eye on your stats—looking interesting!\" can work wonders.", 'tip content for admin summary email', 'affiliate-for-woocommerce' ),
				),

				'tip_4'  => array(
					'tip_title'   => _x( 'Competitors will try to steal your best affiliates', 'tip title for admin summary email', 'affiliate-for-woocommerce' ),
					'tip_content' => _x( "Affiliate poaching is a reality. The moment your top affiliates start pulling in serious revenue, competitors will try to lure them with higher commissions.<br />What's your move? Make them stay with VIP perks like exclusive bonuses, early product access, and private performance-based rewards.", 'tip content for admin summary email', 'affiliate-for-woocommerce' ),
				),

				'tip_5'  => array(
					'tip_title'   => _x( 'Focus on the 20% that drive 80% of sales', 'tip title for admin summary email', 'affiliate-for-woocommerce' ),
					'tip_content' => _x( 'Not all affiliates are equal. Most sign up, a few try, but only a handful bring real revenue. Instead of chasing inactive affiliates, double down on your top performers. Give them higher commissions, personal coaching, and VIP support. Rewarding the right people can 10x your results.', 'tip content for admin summary email', 'affiliate-for-woocommerce' ),
				),

				'tip_6'  => array(
					'tip_title'   => _x( 'Refunds kill commissions—and affiliate morale', 'tip title for admin summary email', 'affiliate-for-woocommerce' ),
					'tip_content' => _x( "Affiliates hate seeing commissions vanish because of refunds. If your refund rate is high, expect frustration (and dropouts).<br />The fix? Be upfront about your refund policy, constantly improve product quality, and keep affiliates in the loop about what's working. The more confidence they have in your product, the harder they'll sell it Feature based tips", 'tip content for admin summary email', 'affiliate-for-woocommerce' ),
				),

				'tip_7'  => array(
					'tip_title'   => _x( 'Pay affiliates smarter and not harder', 'tip title for admin summary email', 'affiliate-for-woocommerce' ),
					'tip_content' => _x( 'Not all affiliates contribute equally to your revenue—so why pay them the same? Set up flexible commission rules with Affiliate for WooCommerce to reward your top-performing influencers, give better rates to loyal customers, or even set different commissions for specific products, categories, and user roles. Why? Because fair rewards = better results!', 'tip content for admin summary email', 'affiliate-for-woocommerce' ),
				),

				'tip_8'  => array(
					'tip_title'   => _x( 'Stop affiliate fraud before it costs you', 'tip title for admin summary email', 'affiliate-for-woocommerce' ),
					'tip_content' => _x( "Affiliate fraud is real, but you don't have to fall for it. Affiliate for WooCommerce helps you block self-referrals, monitor suspicious IPs, and manually approve affiliates before they join. A little fraud prevention today can save you big losses tomorrow.", 'tip content for admin summary email', 'affiliate-for-woocommerce' ),
				),

				'tip_9'  => array(
					'tip_title'   => _x( 'Convert better without links chaos', 'tip title for admin summary email', 'affiliate-for-woocommerce' ),
					'tip_content' => _x( 'Some buyers avoid clicking affiliate links, which means lost commissions. The fix? Use affiliate landing pages. Instead of relying on links, assign landing pages to your affiliates.<br />This way they can send traffic directly to a dedicated page that tracks their referrals automatically. So no more friction or missed commissions—just better conversions.', 'tip content for admin summary email', 'affiliate-for-woocommerce' ),
				),

				'tip_10' => array(
					'tip_title'   => _x( 'Turn discount coupons into affiliate sales machines', 'tip title for admin summary email', 'affiliate-for-woocommerce' ),
					'tip_content' => _x( "Not everyone clicks affiliate links, but almost everyone loves a discount. With Affiliate for WooCommerce, you can turn WooCommerce coupons into powerful affiliate tools.<br />Shoppers get a discount, affiliates earn a commission, and you close a sale—while potentially gaining a loyal customer. It's not just a win-win. It's a win-win-win!", 'tip content for admin summary email', 'affiliate-for-woocommerce' ),
				),

				'tip_11' => array(
					'tip_title'   => _x( 'Give affiliates everything they need to sell more', 'tip title for admin summary email', 'affiliate-for-woocommerce' ),
					'tip_content' => _x( "Don't leave your affiliates guessing on how to promote your products. Set up dedicated campaigns to organize your marketing assets—brand creatives, logos, banners, swipes, videos, and guidelines—in one place. No more back-and-forth or confusion.<br />Remember, the easier you make it for them, the more they'll sell—and the more you'll earn.", 'tip content for admin summary email', 'affiliate-for-woocommerce' ),
				),

				'tip_12' => array(
					'tip_title'   => _x( 'Keep paying affiliates—even for future sales', 'tip title for admin summary email', 'affiliate-for-woocommerce' ),
					'tip_content' => _x( "What if your affiliates earned commissions not just once but on every future purchase their referrals made? With Lifetime Commissions in Affiliate for WooCommerce, affiliates earn on every future purchase their referrals make—whether it's next month or next year. Turn one-time efforts into long-term earnings and keep your affiliate motivated.", 'tip content for admin summary email', 'affiliate-for-woocommerce' ),
				),
			);

			return ( ! empty( $tips ) && ! empty( $tips[ 'tip_' . $afwc_get_admin_summary_tip ] ) ? $tips[ 'tip_' . $afwc_get_admin_summary_tip ] : array() );
		}
	}
}
