<?php
/**
 * My Account > Affiliate > Reports
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/my-account/
 * @since    8.5.0
 * @version  1.0.0
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
	 *     @type string $title          Title for referral dashboard.
	 *     @type string $dashboard_link Link to affiliate dashboard.
	 *     @type string $from           Start date for date range filter.
	 *     @type string $to             End date for date range filter.
	 * }
	 *
	 * @since 8.5.0
	 */
	do_action(
		'afwc_my_account_header',
		array(
			'title'          => _x( 'Referrals', 'Title for referral dashboard', 'affiliate-for-woocommerce' ),
			'dashboard_link' => ! empty( $dashboard_link ) ? $dashboard_link : '',
			'from'           => ! empty( $date_range['from'] ) ? $date_range['from'] : '',
			'to'             => ! empty( $date_range['to'] ) ? $date_range['to'] : '',
		)
	);

	/**
	 * Action to output referral table.
	 *
	 * @param array $args {
	 *     Array of arguments.
	 *
	 *     @type string $from           Start date for date range filter.
	 *     @type string $to             End date for date range filter.
	 *     @type string $dashboard_link Link to affiliate dashboard.
	 *     @type bool   $table_footer   Whether to show table footer or not.
	 * }
	 *
	 * @since 8.5.0
	 */
	do_action(
		'afwc_referral_table',
		array(
			'from'           => ! empty( $date_range['from'] ) ? $date_range['from'] : '',
			'to'             => ! empty( $date_range['to'] ) ? $date_range['to'] : '',
			'dashboard_link' => ! empty( $dashboard_link ) ? $dashboard_link : '',
			'table_footer'   => true,
		)
	);
	?>
</div>
<?php
