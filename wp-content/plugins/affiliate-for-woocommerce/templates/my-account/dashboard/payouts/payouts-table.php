<?php
/**
 * My Account > Affiliate > Reports
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/my-account/dashboard/payouts/
 * @since    8.5.0
 * @version  2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Note: We do not recommend removing existing id & classes in HTML.
?>
<table class="afwc_payout_history afwc-reports afwc-payouts-table">
	<thead>
		<?php
		if ( ! empty( $payout_headers ) && is_array( $payout_headers ) ) {
			echo '<tr>';
			foreach ( $payout_headers as $key => $payout_header ) {
				?>
				<th class="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $payout_header ); ?></th>
				<?php
			}
			echo '</tr>';
		}
		?>
	</thead>
	<tbody>
		<?php
		if ( ! empty( $payouts ) && is_array( $payouts ) && ! empty( $payouts['payouts'] ) && is_array( $payouts['payouts'] ) && ! empty( $payout_headers ) && is_array( $payout_headers ) ) {
			/**
			 * Action to output payouts data rows.
			 *
			 * @param array Array of payouts data.
			 *
			 * @since 8.5.0
			 */
			do_action(
				'afwc_payouts_data',
				array(
					'payouts'        => $payouts,
					'payout_headers' => $payout_headers,
				)
			);
		} else {
			?>
			<tr>
				<td class="empty-table" colspan="4">
					<?php echo esc_html_x( 'No data to display', 'message to show when no payouts data', 'affiliate-for-woocommerce' ); ?>
				</td>
			</tr>
			<?php
		}
		?>
	</tbody>
	<?php if ( ! empty( $table_footer ) && true === $table_footer ) { ?>
	<tfoot>
		<tr>
			<td colspan="4">
				<div class="afwc-table-footer-container">
					<a class="afwc-back-button-wrapper" href="<?php echo esc_attr( esc_url( $dashboard_link ) ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
							<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
						</svg>
						<span><?php echo esc_html_x( 'back to dashboard', 'Link to affiliate my account dashboard', 'affiliate-for-woocommerce' ); ?></span>
					</a>
					<?php if ( ! empty( $payouts['total_count'] ) && ! empty( $payouts['payouts'] ) && ( intval( $payouts['total_count'] ) > count( $payouts['payouts'] ) ) ) { ?>
						<span class="afwc-payouts-load-more-wrapper">
							<span class="afwc-loader" style="display: none;">
								<img src="<?php echo esc_url( WC()->plugin_url() ) . '/assets/images/wpspin-2x.gif'; ?>" class="afwc-table-loader" />
								<span><?php echo esc_html_x( 'Loading...', 'Payout table load more loading text', 'affiliate-for-woocommerce' ); ?></span>
							</span>
							<a id="afwc_load_more_payouts" class="afwc-load-more-text" data-max_record="<?php echo esc_attr( intval( $payouts['total_count'] ) ); ?>">
								<span><?php echo esc_html_x( 'Load more', 'Payout load more link text in my account', 'affiliate-for-woocommerce' ); ?></span>
							</a>
							<span class="afwc-no-load-more-text" style="display: none;"><?php echo esc_html_x( 'No more data to load', 'Text for no data to load', 'affiliate-for-woocommerce' ); ?></span>
						</span>
					<?php } ?>
				</div>
			</td>
		</tr>
	</tfoot>
	<?php } ?>
</table>
<?php
