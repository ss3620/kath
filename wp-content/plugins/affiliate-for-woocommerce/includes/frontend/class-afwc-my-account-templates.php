<?php
/**
 * Main class for Affiliates frontend templates.
 *
 * @package  affiliate-for-woocommerce/includes/frontend/
 * @since    8.5.0
 * @version  2.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_My_Account_Templates' ) ) {

	/**
	 * Main class for Affiliate frontend template functionality.
	 */
	class AFWC_My_Account_Templates {

		/**
		 * Template maps of sub dashboard pages.
		 *
		 * @var array
		 */
		private $table_dashboard_map = array(
			'visits'    => 'my-account/affiliate-visits.php',
			'referrals' => 'my-account/affiliate-referrals.php',
			'products'  => 'my-account/affiliate-products.php',
			'payouts'   => 'my-account/affiliate-payouts.php',
		);

		/**
		 * Template maps of reports table wrapper.
		 *
		 * @var array
		 */
		private $table_wrapper_map = array(
			'visits'   => 'my-account/dashboard/visits/visits-table.php',
			'referral' => 'my-account/dashboard/referrals/referrals-table.php',
			'product'  => 'my-account/dashboard/products/products-table.php',
			'payout'   => 'my-account/dashboard/payouts/payouts-table.php',
		);

		/**
		 * Template maps of reports table data.
		 *
		 * @var array
		 */
		private $table_data_map = array(
			'visits'    => 'my-account/dashboard/visits/visits-data.php',
			'referrals' => 'my-account/dashboard/referrals/referrals-data.php',
			'products'  => 'my-account/dashboard/products/products-data.php',
			'payouts'   => 'my-account/dashboard/payouts/payouts-data.php',
		);

		/**
		 * Property to hold instance of AFWC_My_Account_Templates
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return AFWC_My_Account_Templates Singleton object of this class
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			add_action( 'afwc_reports_dashboard', array( $this, 'my_account_dashboard' ) );

			add_action( 'afwc_my_account_header', array( $this, 'my_account_header' ) );
			add_action( 'afwc_dashboard_kpi', array( $this, 'dashboard_kpi' ) );

			foreach ( array_keys( $this->table_dashboard_map ) as $section ) {
				add_action(
					"afwc_{$section}_dashboard",
					function ( $args = array() ) use ( $section ) {
						if ( ! is_array( $args ) ) {
							return;
						}
						$args['section'] = $section;
						$this->render_reports_table_dashboard( $args );
					}
				);
			}

			foreach ( array_keys( $this->table_wrapper_map ) as $section ) {
				add_action(
					"afwc_{$section}_table",
					function ( $args = array() ) use ( $section ) {
						if ( ! is_array( $args ) ) {
							return;
						}
						$args['section'] = $section;
						$this->render_reports_table_wrapper( $args );
					}
				);
			}

			foreach ( array_keys( $this->table_data_map ) as $section ) {
				add_action(
					"afwc_{$section}_data",
					function ( $args = array() ) use ( $section ) {
						if ( ! is_array( $args ) ) {
							return;
						}
						$args['section'] = $section;
						$this->render_reports_table_data( $args );
					}
				);
			}

			add_action( 'afwc_payout_kpi', array( $this, 'payout_kpi' ) );
		}

		/**
		 * Method to show my account dashboard.
		 *
		 * @param array $args Arguments to be passed to the template.
		 *
		 * @return void
		 */
		public function my_account_dashboard( $args = array() ) {
			$args     = ! is_array( $args ) ? array() : $args;
			$template = 'my-account/affiliate-reports.php';

			$affiliate_details = is_callable( array( 'AFWC_My_Account', 'get_instance' ) ) ? AFWC_My_Account::get_instance() : null;
			$tab_endpoint      = ! empty( $affiliate_details->afwc_tab_endpoint ) ? $affiliate_details->afwc_tab_endpoint : '';
			$section_endpoint  = ! empty( $affiliate_details->afwc_section_endpoint ) ? $affiliate_details->afwc_section_endpoint : '';
			$from              = ! empty( $args['date_range'] ) && ! empty( $args['date_range']['from'] ) ? $args['date_range']['from'] : '';
			$to                = ! empty( $args['date_range'] ) && ! empty( $args['date_range']['to'] ) ? $args['date_range']['to'] : '';
			$current_url       = ! empty( $args['current_url'] ) && ! empty( $args['current_url'] ) ? $args['current_url'] : afwc_get_current_url();
			$report_args       = $this->filter_arguments_for_report(
				array(
					'from' => $from,
					'to'   => $to,
				) + $args
			);

			global $affiliate_for_woocommerce;
			if ( is_callable( array( $affiliate_for_woocommerce, 'afwc_get_template_version' ) ) && version_compare( $affiliate_for_woocommerce->afwc_get_template_version( $template ), '2.0', '>=' ) ) {
				$query_vars = array_filter(
					array(
						$tab_endpoint => 'reports',
						'from-date'   => $from,
						'to-date'     => $to,
					)
				);

				$template_args = array(
					'visits_dashboard_link'   => add_query_arg(
						$query_vars + array(
							$section_endpoint => 'visits',
						),
						$current_url
					),
					'referral_dashboard_link' => add_query_arg(
						$query_vars + array(
							$section_endpoint => 'referrals',
						),
						$current_url
					),
					'product_dashboard_link'  => add_query_arg(
						$query_vars + array(
							$section_endpoint => 'products',
						),
						$current_url
					),
					'payout_dashboard_link'   => add_query_arg(
						$query_vars + array(
							$section_endpoint => 'payouts',
						),
						$current_url
					),
				);
			} else {
				$kpis = is_callable( array( $affiliate_details, 'get_kpis_data' ) ) ? $affiliate_details->get_kpis_data( $report_args, true ) : array();

				$paid_commission   = ! empty( $kpis['paid_commission'] ) ? floatval( $kpis['paid_commission'] ) : 0;
				$unpaid_commission = ! empty( $kpis['unpaid_commission'] ) ? floatval( $kpis['unpaid_commission'] ) : 0;

				$gross_commission = ! empty( $kpis['gross_commission'] ) ? floatval( $kpis['gross_commission'] ) : 0;
				$net_commission   = $paid_commission + $unpaid_commission;

				$paid_commission_percentage = ( ! empty( $paid_commission ) && ! empty( $net_commission ) ) ? ( $paid_commission / $net_commission ) * 100 : 0;
				$paid_commission_percentage = ! empty( $paid_commission_percentage ) ? round( $paid_commission_percentage, 2, PHP_ROUND_HALF_UP ) : 0;

				$unpaid_commission_percentage = ( ! empty( $unpaid_commission ) && ! empty( $net_commission ) ) ? ( $unpaid_commission / $net_commission ) * 100 : 0;
				$unpaid_commission_percentage = ! empty( $unpaid_commission_percentage ) ? round( $unpaid_commission_percentage, 2, PHP_ROUND_HALF_UP ) : 0;

				/**
				 * Filter to show/hide customer column in referrals report.
				 *
				 * @param bool  Whether to show customer column or not.
				 * @param array Additional information.
				 *
				 * @since 8.5.0
				 */
				$show_customer_column = apply_filters( 'afwc_account_show_customer_column', false, array( 'source' => $this ) );

				$template_args = array(
					'paid_commission_percentage'   => $paid_commission_percentage,
					'unpaid_commission_percentage' => $unpaid_commission_percentage,
					'gross_commission'             => $gross_commission,
					'kpis'                         => $kpis,
					'refunds'                      => is_callable( array( $affiliate_details, 'get_refunds_data' ) ) ? $affiliate_details->get_refunds_data( $report_args ) : array(),
					'net_commission'               => $net_commission,
					'visitors'                     => is_callable( array( $affiliate_details, 'get_visitors_data' ) ) ? $affiliate_details->get_visitors_data( $report_args ) : array(),
					'customers_count'              => is_callable( array( $affiliate_details, 'get_customers_data' ) ) ? $affiliate_details->get_customers_data( $report_args ) : array(),
					'is_show_customer_column'      => $show_customer_column,
					'referral_headers'             => is_callable( array( $affiliate_details, 'get_referrals_report_headers' ) ) ? $affiliate_details->get_referrals_report_headers() : array(),
					'product_headers'              => is_callable( array( $affiliate_details, 'get_products_report_headers' ) ) ? $affiliate_details->get_products_report_headers() : array(),
					'payout_headers'               => is_callable( array( $affiliate_details, 'get_payouts_report_headers' ) ) ? $affiliate_details->get_payouts_report_headers() : array(),
					'referrals'                    => is_callable( array( $affiliate_details, 'get_referrals_data' ) ) ? $affiliate_details->get_referrals_data( $report_args ) : array(),
					'products'                     => is_callable( array( $affiliate_details, 'get_products_data' ) ) ? $affiliate_details->get_products_data( $report_args ) : array(),
					'payouts'                      => is_callable( array( $affiliate_details, 'get_payouts_data' ) ) ? $affiliate_details->get_payouts_data( $report_args ) : array(),
					'date_filters'                 => afwc_get_smart_date_filters(),
				);
			}

			is_callable( array( $affiliate_for_woocommerce, 'afwc_get_template' ) ) ? $affiliate_for_woocommerce->afwc_get_template(
				$template,
				array_merge(
					array(
						'affiliate_id' => ! empty( $report_args['affiliate_id'] ) ? intval( $report_args['affiliate_id'] ) : 0,
						'date_range'   => array(
							'from' => $from,
							'to'   => $to,
						),
					),
					$template_args
				)
			) : '';
		}

		/**
		 * Method to show my account header.
		 *
		 * @param array $args Arguments to be passed to the template.
		 *
		 * @return void
		 */
		public function my_account_header( $args = array() ) {
			$args = ! is_array( $args ) ? array() : $args;

			global $affiliate_for_woocommerce;
			is_callable( array( $affiliate_for_woocommerce, 'afwc_get_template' ) ) ? $affiliate_for_woocommerce->afwc_get_template(
				'my-account/dashboard/header.php',
				array_merge(
					$args,
					array(
						'date_filters' => afwc_get_smart_date_filters(),
					)
				)
			) : '';
		}

		/**
		 * Method to show main dashboard KPIs.
		 *
		 * @param array $args Arguments to be passed to the template.
		 *
		 * @return void
		 */
		public function dashboard_kpi( $args = array() ) {
			global $affiliate_for_woocommerce;

			$args = $this->filter_arguments_for_report( $args );

			$template_version    = is_callable( array( $affiliate_for_woocommerce, 'afwc_get_template_version' ) ) ? $affiliate_for_woocommerce->afwc_get_template_version( 'my-account/dashboard/kpi.php' ) : '';
			$get_deprecated_kpis = version_compare( $template_version, '1.1.0', '<' );

			$affiliate_details = is_callable( array( 'AFWC_My_Account', 'get_instance' ) ) ? AFWC_My_Account::get_instance() : null;
			$kpis              = is_callable( array( $affiliate_details, 'get_kpis_data' ) ) ? $affiliate_details->get_kpis_data( $args, $get_deprecated_kpis ) : array();

			$paid_commission   = ! empty( $kpis['paid_commission'] ) ? floatval( $kpis['paid_commission'] ) : 0;
			$unpaid_commission = ! empty( $kpis['unpaid_commission'] ) ? floatval( $kpis['unpaid_commission'] ) : 0;
			$net_commission    = $paid_commission + $unpaid_commission;

			$gross_commission = ! empty( $kpis['gross_commission'] ) ? floatval( $kpis['gross_commission'] ) : 0;

			is_callable( array( $affiliate_for_woocommerce, 'afwc_get_template' ) ) ? $affiliate_for_woocommerce->afwc_get_template(
				'my-account/dashboard/kpi.php',
				array_merge(
					is_array( $args ) ? $args : array(),
					array(
						'kpis'             => $kpis,
						'gross_commission' => $gross_commission,
						'net_commission'   => $net_commission,
						'visitors'         => is_callable( array( $affiliate_details, 'get_visitors_data' ) ) ? $affiliate_details->get_visitors_data( $args ) : array(),
						'customers_count'  => is_callable( array( $affiliate_details, 'get_customers_data' ) ) ? $affiliate_details->get_customers_data( $args ) : array(),
					)
				)
			) : '';
		}

		/**
		 * Method to show Visits|Referrals|Products|Payouts dashboard.
		 *
		 * @param array $args Arguments to be passed to the template.
		 *
		 * @return void
		 */
		public function render_reports_table_dashboard( $args = array() ) {
			$section = is_array( $args ) && ! empty( $args['section'] ) ? $args['section'] : '';
			if ( empty( $section ) || empty( $this->table_dashboard_map[ $section ] ) ) {
				return;
			}

			$args = $this->prepare_table_dashboard_args( $section, $args );

			global $affiliate_for_woocommerce;
			is_callable( array( $affiliate_for_woocommerce, 'afwc_get_template' ) ) ? $affiliate_for_woocommerce->afwc_get_template(
				$this->table_dashboard_map[ $section ],
				$args
			) : '';
		}

		/**
		 * Prepare arguments for Visits|Referrals|Products|Payouts dashboard.
		 *
		 * @param string $section Section name.
		 * @param array  $args    Arguments to be passed to the template.
		 *
		 * @return array $args Prepared arguments.
		 */
		private function prepare_table_dashboard_args( $section = '', $args = array() ) {
			if ( empty( $section ) ) {
				return $args;
			}

			$afwc_myaccount_details = is_callable( array( 'AFWC_My_Account', 'get_instance' ) ) ? AFWC_My_Account::get_instance() : null;

			$data = array(
				'dashboard_link' => is_callable( array( $afwc_myaccount_details, 'get_tab_link' ) )
					? $afwc_myaccount_details->get_tab_link(
						'reports',
						! empty( $args['current_url'] ) ? $args['current_url'] : afwc_get_current_url(),
						array(
							'from-date' => ! empty( $args['date_range'] ) && ! empty( $args['date_range']['from'] ) ? $args['date_range']['from'] : '',
							'to-date'   => ! empty( $args['date_range'] ) && ! empty( $args['date_range']['to'] ) ? $args['date_range']['to'] : '',
						)
					) : '',
			);

			return array_merge( $args, $data );
		}

		/**
		 * Method to show Visits|Referrals|Products|Payouts table.
		 *
		 * @param array $args Arguments to be passed to the template.
		 *
		 * @return void
		 */
		public function render_reports_table_wrapper( $args = array() ) {
			$section = is_array( $args ) && ! empty( $args['section'] ) ? $args['section'] : '';
			if ( empty( $section ) || empty( $this->table_wrapper_map[ $section ] ) ) {
				return;
			}

			$args = $this->filter_arguments_for_report( $args );
			$args = $this->prepare_table_wrapper_args( $section, $args );

			global $affiliate_for_woocommerce;
			is_callable( array( $affiliate_for_woocommerce, 'afwc_get_template' ) ) ? $affiliate_for_woocommerce->afwc_get_template(
				$this->table_wrapper_map[ $section ],
				$args
			) : '';
		}

		/**
		 * Prepare arguments for Visits|Referrals|Products|Payouts table.
		 *
		 * @param string $section Section name.
		 * @param array  $args    Arguments to be passed to the template.
		 *
		 * @return array $args Prepared arguments.
		 */
		private function prepare_table_wrapper_args( $section = '', $args = array() ) {
			$afwc_myaccount_details = is_callable( array( 'AFWC_My_Account', 'get_instance' ) ) ? AFWC_My_Account::get_instance() : null;

			$data = array();
			switch ( $section ) {
				case 'visits':
					/**
					 * Filter to show/hide user agent column in visits report.
					 *
					 * @param bool  Whether to show user agent column or not.
					 * @param array Additional information.
					 *
					 * @since 8.5.0
					 */
					$show_user_agent_column = apply_filters( 'afwc_account_show_user_agent_column', true, array( 'source' => $this ) );
					$data                   = array(
						'visits_headers'            => is_callable( array( $afwc_myaccount_details, 'get_visits_report_headers' ) ) ? $afwc_myaccount_details->get_visits_report_headers() : array(),
						'visits_data'               => is_callable( array( $afwc_myaccount_details, 'get_visits_data' ) ) ? $afwc_myaccount_details->get_visits_data( $args ) : array(),
						'is_show_user_agent_column' => $show_user_agent_column,
					);
					break;

				case 'referral':
					/**
					 * Filter to show/hide customer column in referrals report.
					 *
					 * @param bool  Whether to show customer column or not.
					 * @param array Additional information.
					 *
					 * @since 8.5.0
					 */
					$show_customer_column = apply_filters( 'afwc_account_show_customer_column', false, array( 'source' => $this ) );
					$current_url          = ( ! empty( $_REQUEST['current_url'] ) ) ? wc_clean( wp_unslash( $_REQUEST['current_url'] ) ) : afwc_get_current_url(); // phpcs:ignore
					$data                 = array(
						'referral_headers'        => is_callable( array( $afwc_myaccount_details, 'get_referrals_report_headers' ) ) ? $afwc_myaccount_details->get_referrals_report_headers() : array(),
						'referrals'               => is_callable( array( $afwc_myaccount_details, 'get_referrals_data' ) ) ? $afwc_myaccount_details->get_referrals_data( $args ) : array(),
						'is_show_customer_column' => $show_customer_column,
						'campaign_link'           => is_callable( array( $afwc_myaccount_details, 'get_tab_link' ) ) ? ( $afwc_myaccount_details->get_tab_link( 'campaigns', $current_url ) . '#!/' ) : '',
					);
					break;

				case 'product':
					$data = array(
						'product_headers' => is_callable( array( $afwc_myaccount_details, 'get_products_report_headers' ) ) ? $afwc_myaccount_details->get_products_report_headers() : array(),
						'products'        => is_callable( array( $afwc_myaccount_details, 'get_products_data' ) ) ? $afwc_myaccount_details->get_products_data( $args ) : array(),
					);
					break;

				case 'payout':
					$data = array(
						'payout_headers' => is_callable( array( $afwc_myaccount_details, 'get_payouts_report_headers' ) ) ? $afwc_myaccount_details->get_payouts_report_headers() : array(),
						'payouts'        => is_callable( array( $afwc_myaccount_details, 'get_payouts_data' ) ) ? $afwc_myaccount_details->get_payouts_data( $args ) : array(),
					);
					break;
			}

			return array_merge( $args, $data );
		}

		/**
		 * Method to show Visits|Referrals|Products|Payouts table data.
		 *
		 * @param array $args Arguments to be passed to the template.
		 *
		 * @return void
		 */
		public function render_reports_table_data( $args = array() ) {
			$section = is_array( $args ) && ! empty( $args['section'] ) ? $args['section'] : '';
			if ( empty( $section ) || empty( $this->table_data_map[ $section ] ) ) {
				return;
			}

			$args = $this->filter_arguments_for_report( $args );

			global $affiliate_for_woocommerce;
			is_callable( array( $affiliate_for_woocommerce, 'afwc_get_template' ) ) ? $affiliate_for_woocommerce->afwc_get_template(
				$this->table_data_map[ $section ],
				$args
			) : '';
		}

		/**
		 * Method to show Payout KPIs.
		 *
		 * @param array $args Arguments to be passed to the template.
		 *
		 * @return void
		 */
		public function payout_kpi( $args = array() ) {
			$args              = $this->filter_arguments_for_report( $args );
			$affiliate_details = is_callable( array( 'AFWC_My_Account', 'get_instance' ) ) ? AFWC_My_Account::get_instance() : null;

			global $affiliate_for_woocommerce;
			is_callable( array( $affiliate_for_woocommerce, 'afwc_get_template' ) ) ? $affiliate_for_woocommerce->afwc_get_template(
				'my-account/dashboard/payouts/payouts-kpi.php',
				array_merge(
					is_array( $args ) ? $args : array(),
					array(
						'kpis' => is_callable( array( $affiliate_details, 'get_payout_kpis' ) ) ? $affiliate_details->get_payout_kpis( $args ) : array(),
					)
				)
			) : '';
		}

		/**
		 * Method to filter arguments for report tab.
		 *
		 * @param array $args Arguments to be filtered.
		 *
		 * @return array $args Filtered arguments.
		 */
		private function filter_arguments_for_report( $args = array() ) {
			$args = ! is_array( $args ) ? array() : $args;

			$args['affiliate_id'] = ( ! empty( $args['affiliate_id'] ) ) ? intval( $args['affiliate_id'] ) : afwc_get_affiliate_id_based_on_user_id( get_current_user_id() );
			$args['from']         = ( ! empty( $args['from'] ) ) ? get_gmt_from_date( $args['from'] . ' 00:00:00', 'Y-m-d H:m:s' ) : '';
			$args['to']           = ( ! empty( $args['to'] ) ) ? get_gmt_from_date( $args['to'] . ' 23:59:59', 'Y-m-d H:m:s' ) : '';

			return $args;
		}
	}
}

AFWC_My_Account_Templates::get_instance();
