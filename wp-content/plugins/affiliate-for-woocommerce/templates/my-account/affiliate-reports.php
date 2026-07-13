<?php
/**
 * My Account > Affiliate > Reports
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/my-account/
 * @since    6.25.0
 * @version  2.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Note: We do not recommend removing existing id & classes in HTML.
?>
<div id="afwc_dashboard_wrapper">
	<?php
	/**
	 * Action to output my account header.
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *
	 *     @type string $from Start date for date range filter.
	 *     @type string $to   End date for date range filter.
	 * }
	 *
	 * @since 8.5.0
	 */
	do_action(
		'afwc_my_account_header',
		array(
			'from' => ! empty( $date_range['from'] ) ? $date_range['from'] : '',
			'to'   => ! empty( $date_range['to'] ) ? $date_range['to'] : '',
		)
	);

	/**
	 * Action to output dashboard KPI.
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *
	 *     @type string $from Start date for date range filter.
	 *     @type string $to   End date for date range filter.
	 * }
	 *
	 * @since 8.5.0
	 */
	do_action(
		'afwc_dashboard_kpi',
		array(
			'from' => ! empty( $date_range['from'] ) ? $date_range['from'] : '',
			'to'   => ! empty( $date_range['to'] ) ? $date_range['to'] : '',
		)
	);
	?>
	<div class="afwc-visits-section">
		<div class="afwc-table-header-wrapper">
			<span class="afwc-table-header">
				<?php echo esc_html_x( 'Recent Visits', 'Visits section title', 'affiliate-for-woocommerce' ); ?>
			</span>
			<span class="afwc-view-all-reports">
				<a href="<?php echo esc_url( $visits_dashboard_link ); ?>">
					<?php echo esc_attr_x( 'View all', 'view all text for visits table', 'affiliate-for-woocommerce' ); ?>
				</a>
			</span>
		</div>
		<?php

		/**
		 * Action to output visits table.
		 *
		 * @param array $args {
		 *     Array of arguments.
		 *
		 *     @type string $from         Start date for date range filter.
		 *     @type string $to           End date for date range filter.
		 *     @type int    $limit        Number of records to show.
		 *     @type bool   $table_footer Whether to show table footer or not.
		 * }
		 *
		 * @since 8.5.0
		 */
		do_action(
			'afwc_visits_table',
			array(
				'from'         => ! empty( $date_range['from'] ) ? $date_range['from'] : '',
				'to'           => ! empty( $date_range['to'] ) ? $date_range['to'] : '',
				'limit'        => 5,
				'table_footer' => false,
			)
		);
		?>
	</div>
	<div class="afwc-referrals-section">
		<div class="afwc-table-header-wrapper">
			<span class="afwc-table-header">
				<?php echo esc_html_x( 'Recent Referrals', 'Referrals section title', 'affiliate-for-woocommerce' ); ?>
			</span>
			<span class="afwc-view-all-reports">
				<a href="<?php echo esc_url( $referral_dashboard_link ); ?>">
					<?php echo esc_attr_x( 'View all', 'view all text for referrals table', 'affiliate-for-woocommerce' ); ?>
				</a>
			</span>
		</div>
		<?php

		/**
		 * Action to output referral table.
		 *
		 * @param array $args {
		 *     Array of arguments.
		 *
		 *     @type string $from         Start date for date range filter.
		 *     @type string $to           End date for date range filter.
		 *     @type int    $limit        Number of records to show.
		 *     @type bool   $table_footer Whether to show table footer or not.
		 * }
		 *
		 * @since 8.5.0
		 */
		do_action(
			'afwc_referral_table',
			array(
				'from'         => ! empty( $date_range['from'] ) ? $date_range['from'] : '',
				'to'           => ! empty( $date_range['to'] ) ? $date_range['to'] : '',
				'limit'        => 5,
				'table_footer' => false,
			)
		);
		?>
	</div>
	<div class="afwc-products-section">
		<div class="afwc-table-header-wrapper">
			<span class="afwc-table-header">
				<?php echo esc_html_x( 'Top Products', 'Product section title', 'affiliate-for-woocommerce' ); ?>
			</span>
			<span class="afwc-view-all-reports">
				<a href="<?php echo esc_url( $product_dashboard_link ); ?>">
					<?php echo esc_attr_x( 'View all', 'view all text for products table', 'affiliate-for-woocommerce' ); ?>
				</a>
			</span>
		</div>
		<?php

		/**
		 * Action to output product table.
		 *
		 * @param array $args {
		 *     Array of arguments.
		 *
		 *     @type string $from         Start date for date range filter.
		 *     @type string $to           End date for date range filter.
		 *     @type int    $limit        Number of records to show.
		 *     @type bool   $table_footer Whether to show table footer or not.
		 * }
		 *
		 * @since 8.5.0
		 */
		do_action(
			'afwc_product_table',
			array(
				'from'         => ! empty( $date_range['from'] ) ? $date_range['from'] : '',
				'to'           => ! empty( $date_range['to'] ) ? $date_range['to'] : '',
				'limit'        => 5,
				'table_footer' => false,
			)
		);
		?>
	</div>
	<div class="afwc-payouts-section">
		<div class="afwc-table-header-wrapper">
			<span class="afwc-table-header">
				<?php echo esc_html_x( 'Recent Payouts', 'Payout history section title', 'affiliate-for-woocommerce' ); ?>
			</span>
			<span class="afwc-view-all-reports">
				<a href="<?php echo esc_url( $payout_dashboard_link ); ?>">
					<?php echo esc_attr_x( 'View all', 'view all text for payout history table', 'affiliate-for-woocommerce' ); ?>
				</a>
			</span>
		</div>
		<?php

		/**
		 * Action to output payout table.
		 *
		 * @param array $args {
		 *     Array of arguments.
		 *
		 *     @type string $from         Start date for date range filter.
		 *     @type string $to           End date for date range filter.
		 *     @type int    $limit        Number of records to show.
		 *     @type bool   $table_footer Whether to show table footer or not.
		 * }
		 *
		 * @since 8.5.0
		 */
		do_action(
			'afwc_payout_table',
			array(
				'from'         => ! empty( $date_range['from'] ) ? $date_range['from'] : '',
				'to'           => ! empty( $date_range['to'] ) ? $date_range['to'] : '',
				'limit'        => 5,
				'table_footer' => false,
			)
		);
		?>
	</div>
</div>
<?php
