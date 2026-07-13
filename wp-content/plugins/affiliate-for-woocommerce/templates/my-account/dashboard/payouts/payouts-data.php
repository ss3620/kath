<?php
/**
 * My Account > Affiliate > Reports
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/my-account/dashboard/payouts/
 * @since    8.44.0
 * @version  1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Note: We do not recommend removing existing id & classes in HTML.

foreach ( $payouts['payouts'] as $payout ) {
	echo '<tr>';
	foreach ( $payout_headers as $column_name => $column_label ) {
		if ( 'invoice' === $column_name ) {
			?>
			<td
				class="<?php echo esc_attr( $column_name ); ?>"
				data-title="<?php echo esc_attr( $column_label ); ?>"
				data-payout_id="<?php echo ! empty( $payout['payout_id'] ) ? esc_attr( $payout['payout_id'] ) : 0; ?>"
				data-affiliate_id="<?php echo esc_attr( $affiliate_id ); ?>"
				data-datetime="<?php echo ! empty( $payout['datetime'] ) ? esc_attr( $payout['datetime'] ) : ''; ?>"
				data-from_period="<?php echo ! empty( $payout['from_date'] ) ? esc_attr( $payout['from_date'] ) : ''; ?>"
				data-to_period="<?php echo ! empty( $payout['to_date'] ) ? esc_attr( $payout['to_date'] ) : ''; ?>"
				data-referral_count="<?php echo ! empty( $payout['referral_count'] ) ? esc_attr( $payout['referral_count'] ) : 0; ?>"
				data-amount="<?php echo ! empty( $payout['amount'] ) ? esc_attr( $payout['amount'] ) : 0; ?>"
				data-currency="<?php echo ! empty( $payout['currency'] ) ? esc_attr( $payout['currency'] ) : ''; ?>"
				data-method="<?php echo ! empty( $payout['method'] ) ? esc_attr( $payout['method'] ) : ''; ?>"
				data-notes="<?php echo ! empty( $payout['payout_notes'] ) ? esc_attr( $payout['payout_notes'] ) : ''; ?>"
			>
				<a class="print-invoice" title="<?php echo esc_attr_x( 'Print', 'title for printing the invoice', 'affiliate-for-woocommerce' ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5">
						<path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"></path>
					</svg>
				</a>
			</td>
			<?php
		} else {
			?>
			<td class="<?php echo esc_attr( $column_name ); ?>" data-title="<?php echo esc_attr( $column_label ); ?>">
				<?php echo ! empty( $payout[ $column_name ] ) ? wp_kses_post( $payout[ $column_name ] ) : ''; ?>
			</td>
			<?php
		}
	}
	echo '</tr>';
}
