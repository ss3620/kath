<?php
/**
 * Admin summary email report content (Affiliate Manager - Summary Email)
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/emails/plain/
 * @since    8.25.0
 * @version  1.0.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text_align = is_rtl() ? 'right' : 'left';

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo esc_html_x( 'Hi there,', 'Greeting message for the admin on admin summary report email', 'affiliate-for-woocommerce' );
echo "\n\n";

printf(
	/* translators: %1$s: Site address, %2$s: From date, %3$s: To date */
	esc_html_x(
		'Here\'s how your affiliate program on %1$s performed in the last month (%2$s to %3$s).',
		'Introduction to the admin summary report email',
		'affiliate-for-woocommerce'
	),
	! empty( $site_address ) ? '<strong>' . esc_html( $site_address ) . '</strong>' : '',
	esc_html( ! empty( $from_date ) ? $from_date : '' ),
	esc_html( ! empty( $to_date ) ? $to_date : '' )
);

echo "\n\n----------------------------------------\n\n";

printf( '%s: %s', esc_html_x( 'Revenue', 'label for total revenue in the admin summary email', 'affiliate-for-woocommerce' ), wp_kses_post( $affiliates_revenue_amount ) );
echo "\n";
printf( '%s: %s', esc_html_x( 'Total Order Value', 'label for total order amount in the admin summary email', 'affiliate-for-woocommerce' ), wp_kses_post( $site_order_total_amount ) );
echo "\n";
printf( '%s: %s • %s', esc_html_x( 'Orders: Affiliate • Total ', 'label for order counts in the admin summary email', 'affiliate-for-woocommerce' ), esc_html( $affiliates_order_count ), esc_html( $site_order_count ) );
echo "\n";

echo "\n----------------------------------------\n\n";

printf( '%s: %s', esc_html_x( 'Paid Commissions: ', 'label for paid commission in the admin summary email', 'affiliate-for-woocommerce' ), wp_kses_post( $paid_commissions ) );
echo "\n";
printf( '%s: %s', esc_html_x( 'Unpaid Commissions: ', 'label for unpaid commission in the admin summary email', 'affiliate-for-woocommerce' ), wp_kses_post( $unpaid_commissions ) );
echo "\n";
printf( '%s: %s', esc_html_x( 'View all', 'link text to view all unpaid commission on pending payouts dashboard in the admin summary email', 'affiliate-for-woocommerce' ), esc_html( $pending_payouts_dashboard_url ) );
echo "\n";

echo "\n----------------------------------------\n\n";

printf( '%s: %s', esc_html_x( 'New Approved Affiliates', 'label for new approved affiliates in the admin summary email', 'affiliate-for-woocommerce' ), wp_kses_post( $newly_joined_affiliates ) );
echo "\n";
printf( '%s: %s', esc_html_x( 'Pending Affiliates', 'label for pending affiliates in the admin summary email', 'affiliate-for-woocommerce' ), wp_kses_post( $pending_affiliates ) );
echo "\n";

echo "\n----------------------------------------\n\n";

if ( ! empty( $top_performing_affiliates ) && is_array( $top_performing_affiliates ) ) {

	echo esc_html_x( 'Top Performing Affiliates', 'label for top performing affiliates in the admin summary email', 'affiliate-for-woocommerce' ) . "\n";

	foreach ( $top_performing_affiliates as $key => $affiliate_data ) {
		if ( ! is_array( $affiliate_data ) || empty( $affiliate_data['display_name'] ) ) {
			continue;
		}
		printf(
			'%s. %s • %s',
			esc_html( $key + 1 ),
			esc_html( $affiliate_data['display_name'] ),
			wp_kses_post( afwc_format_price( ! empty( $affiliate_data['order_total_amount'] ) ? esc_html( $affiliate_data['order_total_amount'] ) : '' ) )
		);
		echo "\n";
	}

	echo "\n----------------------------------------\n\n";
}


if ( ! empty( $converted_urls ) && is_array( $converted_urls ) ) {

	echo esc_html_x( 'Top Converting URLs', 'label for highest converting URLs in the admin summary email', 'affiliate-for-woocommerce' ) . "\n";

	foreach ( $converted_urls as $key => $url_stats ) {
		if ( ! is_array( $url_stats ) || empty( $url_stats['url'] ) ) {
			continue;
		}
		printf(
			'%s. %s',
			esc_html( $key + 1 ),
			esc_html( $url_stats['url'] )
		);
		echo "\n";
	}

	echo "\n----------------------------------------\n\n";
}


if ( ! empty( $expert_tips ) && is_array( $expert_tips ) ) {

	echo esc_html_x( '★ Pro tip from our expert:', 'label for pro tips section in the admin summary email', 'affiliate-for-woocommerce' ) . "\n";

	if ( ! empty( $expert_tips['tip_title'] ) ) {
		echo esc_html( $expert_tips['tip_title'] ) . "\n\n";
	}
	if ( ! empty( $expert_tips['tip_content'] ) ) {
		echo esc_html( wp_strip_all_tags( wptexturize( $expert_tips['tip_content'] ) ) ) . "\n\n";
	}

	echo "\n----------------------------------------\n\n";
}

printf( '%s: %s', esc_html_x( 'View documentation', 'label for documentation link text in the admin summary email', 'affiliate-for-woocommerce' ), esc_html( AFWC_DOC_DOMAIN ) );
echo "\n\n";
printf( '%s: %s', esc_html_x( 'Rate 5-star', 'label for review link text in the admin summary email', 'affiliate-for-woocommerce' ), esc_html( AFWC_REVIEW_URL ) );

echo "\n\n----------------------------------------\n\n";

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) ) . "\n\n";
	echo "----------------------------------------\n\n";
}

// Output the email footer.
echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) ) . "\n"; //phpcs:ignore WooCommerce.Commenting.CommentHooks
