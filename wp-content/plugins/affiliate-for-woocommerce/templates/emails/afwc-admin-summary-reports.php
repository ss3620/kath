<?php
/**
 * Admin summary email report content (Affiliate Manager - Summary Email)
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/emails/
 * @since    8.25.0
 * @version  1.0.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text_align = is_rtl() ? 'right' : 'left';

/*
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); // phpcs:ignore WooCommerce.Commenting.CommentHooks
?>

<p>
	<?php echo esc_html_x( 'Hi there,', 'Greeting message for the admin on admin summary report email', 'affiliate-for-woocommerce' ); ?>
</p>

<p style="margin-bottom: 2.5em;">
	<?php
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
	?>
</p>

<div style="margin-bottom: 2.5em;">
	<table class="td" cellspacing="0" cellpadding="10" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;border: none;">
		<tr>
			<th style="padding: 0.625em; text-align: center; width: 33.33%; color: #00a32a;">
				<?php echo wp_kses_post( ! empty( $affiliates_revenue_amount ) ? $affiliates_revenue_amount : afwc_format_price( 0 ) ); ?>
			</th>
			<th style="padding: 0.625em; text-align: center; width: 33.33%;">
				<?php echo wp_kses_post( ! empty( $site_order_total_amount ) ? $site_order_total_amount : afwc_format_price( 0 ) ); ?>
			</th>
			<th style="padding: 0.625em; text-align: center; width: 33.33%;">
				<?php echo ! empty( $affiliates_order_count ) ? esc_html( $affiliates_order_count ) : 0; ?>
				•
				<?php echo ! empty( $site_order_count ) ? esc_html( $site_order_count ) : 0; ?>
			</th>
		</tr>
		<tr>
			<td style="padding: 0.625em; text-align: center; width: 33.33%; color: #00a32a;">
				<?php echo esc_html_x( 'Revenue', 'label for total revenue in the admin summary email', 'affiliate-for-woocommerce' ); ?>
			</td>
			<td style="padding: 0.625em; text-align: center; width: 33.33%;">
				<?php echo esc_html_x( 'Total Order Value', 'label for total order amount in the admin summary email', 'affiliate-for-woocommerce' ); ?>
			</td>
			<td style="padding: 0.625em; text-align: center; width: 33.33%;">
				<?php echo esc_html_x( 'Orders: Affiliate • Total ', 'label for order counts in the admin summary email', 'affiliate-for-woocommerce' ); ?>
			</td>
		</tr>
	</table>
</div>

<div style="margin-bottom: 2.5em;">
	<table class="td" cellspacing="0" cellpadding="10" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;border: none;">
		<tr>
			<td style="padding: 0.625em;">
				<?php
				echo esc_html_x( 'Paid Commissions: ', 'label for paid commission in the admin summary email', 'affiliate-for-woocommerce' );
				echo wp_kses_post( ! empty( $paid_commissions ) ? $paid_commissions : afwc_format_price( 0 ) );
				?>
			</td>
		</tr>
		<tr>
			<td style="padding: 0.625em;">
				<?php
				echo esc_html_x( 'Unpaid Commissions: ', 'label for unpaid commission in the admin summary email', 'affiliate-for-woocommerce' );
				echo wp_kses_post( ! empty( $unpaid_commissions ) ? $unpaid_commissions : afwc_format_price( 0 ) );
				?>
				•
				<a href="<?php echo ! empty( $pending_payouts_dashboard_url ) ? esc_attr( $pending_payouts_dashboard_url ) : ''; ?>" target="_blank">
					<?php echo esc_html_x( 'View all', 'link text to view all unpaid commission on pending payouts dashboard in the admin summary email', 'affiliate-for-woocommerce' ); ?>
				</a>
			</td>
		</tr>
	</table>
</div>

<div style="margin-bottom: 2.5em;">
	<table class="td" cellspacing="0" cellpadding="10" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;border: none;">
		<tr>
			<th style="padding: 0.625em; text-align: center; width: 50%;">
				<?php echo ! empty( $newly_joined_affiliates ) ? esc_html( $newly_joined_affiliates ) : 0; ?>
			</th>
			<th style="padding: 0.625em; text-align: center; width: 50%;">
				<?php echo ! empty( $pending_affiliates ) ? esc_html( $pending_affiliates ) : 0; ?>
			</th>
		</tr>
		<tr>
			<td style="padding: 0.625em; text-align: center; width: 50%;">
				<?php echo esc_html_x( 'New Approved Affiliates', 'label for new approved affiliates in the admin summary email', 'affiliate-for-woocommerce' ); ?>
			</td>
			<td style="padding: 0.625em; text-align: center; width: 50%;">
				<?php echo esc_html_x( 'Pending Affiliates', 'label for pending affiliates in the admin summary email', 'affiliate-for-woocommerce' ); ?>
			</td>
		</tr>
	</table>
</div>

<?php if ( ! empty( $top_performing_affiliates ) && is_array( $top_performing_affiliates ) ) { ?>
	<p style="font-size: 1.15em;">
		<strong><?php echo esc_html_x( 'Top Performing Affiliates', 'label for top performing affiliates in the admin summary email', 'affiliate-for-woocommerce' ); ?></strong>
	</p>

	<div style="margin-bottom: 2.5em;">
		<table class="td" cellspacing="0" cellpadding="10" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;border: none;">
			<?php
			foreach ( $top_performing_affiliates as $key => $affiliate_data ) {
				if ( ! is_array( $affiliate_data ) || empty( $affiliate_data['display_name'] ) ) {
					continue;
				}
				?>
				<tr>
					<th style="padding: 0.625em; width: 5%;">
						<?php echo esc_html( ( $key + 1 ) . '.' ); ?>
					</th>
					<td style="padding: 0.625em;">
						<a href="<?php echo ! empty( $affiliate_data['dashboard_routing_url'] ) ? esc_attr( $affiliate_data['dashboard_routing_url'] ) : ''; ?>"><?php echo esc_html( $affiliate_data['display_name'] ); ?></a>
						<span style="padding: 0px 0.3em;">•</span>
						<span><?php echo wp_kses_post( afwc_format_price( ! empty( $affiliate_data['order_total_amount'] ) ? $affiliate_data['order_total_amount'] : 0 ) ); ?></span>
					</td>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php } ?>

<?php if ( ! empty( $converted_urls ) && is_array( $converted_urls ) ) { ?>
	<p style="font-size: 1.15em;">
		<strong><?php echo esc_html_x( 'Top Converting URLs', 'label for highest converting URLs in the admin summary email', 'affiliate-for-woocommerce' ); ?></strong>
	</p>

	<div style="margin-bottom: 2.5em;">
		<table class="td" cellspacing="0" cellpadding="10" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;border: none;">
			<?php
			foreach ( $converted_urls as $key => $url_stats ) {
				if ( ! is_array( $url_stats ) || empty( $url_stats['url'] ) ) {
					continue;
				}
				?>
				<tr>
					<th style="padding: 0.625em; width: 5%;">
						<?php echo esc_html( ( absint( $key ) + 1 ) . '.' ); ?>
					</th>
					<td style="padding: 0.625em;">
						<a href="<?php echo esc_attr( $url_stats['url'] ); ?>" target="_blank"><?php echo esc_html( $url_stats['url'] ); ?></a>
					</td>
				</tr>
			<?php } ?>
		</table>
	</div>
<?php } ?>

<?php if ( ! empty( $expert_tips ) && is_array( $expert_tips ) ) { ?>
	<div style="margin-bottom: 2.5em;">
		<p style="font-size: 1.15em; text-align: center;">
			<strong><?php echo esc_html_x( '★ Pro tip from our expert:', 'label for pro tips section in the admin summary email', 'affiliate-for-woocommerce' ); ?></strong>
		</p>
		<?php if ( ! empty( $expert_tips['tip_title'] ) ) { ?>
		<div>
			<p style="margin-bottom: 0; padding-bottom: 0;">
				<strong><?php echo esc_html( $expert_tips['tip_title'] ); ?></strong>
			</p>
			<?php } ?>
			<?php if ( ! empty( $expert_tips['tip_content'] ) ) { ?>
			<p>
				<?php echo wp_kses_post( $expert_tips['tip_content'] ); ?>
			</p>
			<?php } ?>
		</div>
	</div>
<?php } ?>

<div>
	<table class="td" cellspacing="0" cellpadding="10"
		style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;border: none;">
		<tr>
			<td style="padding: 0.625em; text-align: center; border-style: none solid none none; border-width: 1px; border-color: #ccc; width: 50%">
				<a href="<?php echo esc_attr( AFWC_DOC_DOMAIN ); ?>" target="_blank">
					<?php echo esc_html_x( 'View documentation', 'label for documentation link text in the admin summary email', 'affiliate-for-woocommerce' ); ?>
				</a>
			</td>
			<td style="padding: 0.625em; text-align: center; border-style: none none none solid; border-width: 1px; border-color: #ccc;  width: 50%">
				<a href="<?php echo esc_attr( AFWC_REVIEW_URL ); ?>" target="_blank">
					<?php echo esc_html_x( 'Rate 5-star', 'label for review link text in the admin summary email', 'affiliate-for-woocommerce' ); ?>
				</a>
			</td>
		</tr>
	</table>
</div>

<?php

/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ( $additional_content ) {
	?>
	<div style="margin-top: 2.5em;">
		<?php echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) ); ?>
	</div>
	<?php
}

/**
 * Output the email footer
 *
 * @hooked WC_Emails::email_footer() Output the email footer.
 * @param string $email.
 */
do_action( 'woocommerce_email_footer', $email ); // phpcs:ignore WooCommerce.Commenting.CommentHooks
