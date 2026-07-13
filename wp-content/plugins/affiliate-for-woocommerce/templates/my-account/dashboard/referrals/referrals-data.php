<?php
/**
 * My Account > Affiliate > Reports
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/my-account/dashboard/referrals/
 * @since    8.44.0
 * @version  1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Note: We do not recommend removing existing id & classes in HTML.

foreach ( $referrals['rows'] as $referral ) {
	echo '<tr>';
	foreach ( $referral_headers as $column_name => $column_label ) {
		if ( 'customer_name' === $column_name ) {
			$customer_name = ! empty( $referral[ $column_name ] ) ? ( ( mb_strlen( $referral[ $column_name ] ) > 20 ) ? mb_substr( $referral[ $column_name ], 0, 19 ) . '...' : $referral[ $column_name ] ) : '';
			?>
			<td class="<?php echo esc_attr( $column_name ); ?>" data-title="<?php echo esc_attr( $column_label ); ?>" title="<?php echo ( ! empty( $referral[ $column_name ] ) ) ? esc_html( $referral[ $column_name ] ) : ''; ?>">
				<?php echo esc_html( $customer_name ); ?>
			</td>
			<?php
		} elseif ( 'status' === $column_name ) {
			$referral_status = ( ! empty( $referral[ $column_name ] ) ) ? $referral[ $column_name ] : '';
			?>
			<td class="<?php echo esc_attr( $column_name ); ?>" data-title="<?php echo esc_attr( $column_label ); ?>">
				<span class="<?php echo esc_attr( 'text_' . ( ! empty( $referral_status ) ? afwc_get_commission_status_colors( $referral_status ) : '' ) ); ?>">
					<?php echo esc_html( ( ! empty( $referral_status ) ) ? afwc_get_commission_statuses( $referral_status ) : '' ); ?>
				</span>
				<?php if ( ! empty( $referral['days_remaining_for_refund'] ) && $referral['days_remaining_for_refund'] > 0 ) { ?>
				<span class="afwc-referrals-remaining-refund-period" title="
					<?php
					printf(
						/* translators: %d - days remaining for refund period */
						esc_html_x( 'Refund period: Payout ready after %d days', 'title text for refund period remaining for referral', 'affiliate-for-woocommerce' ),
						esc_html( $referral['days_remaining_for_refund'] )
					);
					?>
				">
					<svg viewBox="0 0 6 6" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
						<circle cx="3" cy="3" r="3"></circle>
					</svg>
				</span>
				<?php } ?>
			</td>
			<?php
		} elseif ( 'source' === $column_name ) {
			// To display Campaign and Medium.
			?>
			<td class="<?php echo esc_attr( $column_name ); ?>" data-title="<?php echo esc_attr( $column_label ); ?>">
				<div class="afwc-referrals-campaign-wrapper">
					<span class="afwc-referrals-source-label" id="afwc-campaign-label">
						<?php echo esc_html_x( 'Campaign:', 'Campaign label in the referrals dashboard', 'affiliate-for-woocommerce' ); ?>
					</span>
					<span class="afwc-referrals-source-value afwc-referrals-campaign" aria-labelledby="afwc-campaign-label">
						<?php
						$campaign_id = ( ! empty( $referral['campaign'] ) ) ? $referral['campaign'] : '';
						if ( ! empty( $referral['campaign_title'] ) ) {
							?>
							<a href="<?php echo esc_attr( ! empty( $campaign_link ) ? ( $campaign_link . $campaign_id ) : '#' ); ?>" title="<?php echo esc_html( $referral['campaign_title'] ); ?>" target="_blank">
								<?php echo esc_html( '#' . $campaign_id ); ?>
							</a>
						<?php } elseif ( ! empty( $campaign_id ) ) { ?>
							<span title="<?php echo ! empty( $referral['is_campaign_deleted'] ) ? esc_attr_x( 'Deleted', 'Deleted campaign ID in referral dashboard', 'affiliate-for-woocommerce' ) : ''; ?>"><?php echo esc_html( '#' . $campaign_id ); ?> </span>
						<?php } else { ?>
							- 
						<?php } ?>
					</span>
				</div>
				<div class="afwc-referrals-medium-wrapper">
					<span class="afwc-referrals-source-label" id="afwc-medium-label">
						<?php echo esc_html_x( 'Medium:', 'Medium label in the referrals dashboard', 'affiliate-for-woocommerce' ); ?>
					</span>
					<span class="afwc-referrals-source-value afwc-referrals-medium" aria-labelledby="afwc-medium-label">
						<?php
						echo esc_html( ucwords( ( ! empty( $referral['medium'] ) ) ? $referral['medium'] : 'link' ) ); // phpcs:ignore
						?>
					</span>
				</div>
			</td>
			<?php
		} elseif ( 'order_id' === $column_name ) {
			?>
			<td class="<?php echo esc_attr( $column_name ); ?>" data-title="<?php echo esc_attr( $column_label ); ?>">
				<span class="afwc-referrals-order-id">
					<?php echo esc_html( ! empty( $referral['order_id'] ) ? ( '#' . $referral['order_id'] ) : '' ); ?>
				</span>
				<span class="afwc-referrals-order-status">
					<?php echo esc_html( ! empty( $referral['order_status'] ) ? $referral['order_status'] : '' ); ?>
				</span>
			</td>
			<?php
		} else {
			?>
			<td class="<?php echo esc_attr( $column_name ); ?>" data-title="<?php echo esc_attr( $column_label ); ?>">
				<?php echo ! empty( $referral[ $column_name ] ) ? wp_kses_post( $referral[ $column_name ] ) : ''; ?>
			</td>
			<?php
		}
	}
	echo '</tr>';
}
