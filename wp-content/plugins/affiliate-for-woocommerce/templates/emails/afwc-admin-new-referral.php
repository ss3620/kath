<?php
/**
 * Admin New Referral Email
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/emails/
 * @since    6.7.0
 * @version  1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text_align     = is_rtl() ? 'right' : 'left';
$opposite_align = is_rtl() ? 'left' : 'right';
$has_multi_tier = ! empty( $has_multi_tier );
$commissions    = ! empty( $commissions ) && is_array( $commissions ) ? $commissions : array();

?>
<h2><?php echo esc_html_x( 'Affiliate information', 'Affiliate section title', 'affiliate-for-woocommerce' ); ?></h2>

<div style="margin-bottom: 40px;">
	<table class="td" cellspacing="0" cellpadding="6" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 0.5em;" border="1">
		<tr>
			<th class="td" scope="row" style="text-align:<?php echo esc_attr( $text_align ); ?>; "><?php echo esc_html_x( 'Affiliate', 'Affiliate name', 'affiliate-for-woocommerce' ); ?></th>
			<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; "><?php echo wp_kses_post( $affiliate_display_name ); ?></td>
		</tr>
		<?php
		if ( ! empty( $campaign_id ) ) {
			?>
				<tr>
					<th class="td" scope="row" style="text-align:<?php echo esc_attr( $text_align ); ?>; "><?php echo esc_html_x( 'Campaign', 'Campaign name and id', 'affiliate-for-woocommerce' ); ?></th>
					<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; "><?php echo wp_kses_post( $campaign_name . ' (#' . $campaign_id . ')' ); ?></td>
				</tr>
				<?php
		}

		if ( $has_multi_tier ) {
			?>
			<tr>
				<th class="td" scope="row" style="text-align:<?php echo esc_attr( $text_align ); ?>; "><?php echo esc_html_x( 'Total commission', 'Total commission across all tiers', 'affiliate-for-woocommerce' ); ?></th>
				<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; font-weight: bold;"><?php echo wp_kses_post( $total_commission ); ?></td>
			</tr>
			<?php
		} else {
			?>
			<tr>
				<th class="td" scope="row" style="text-align:<?php echo esc_attr( $text_align ); ?>; "><?php echo esc_html_x( 'Commission earned', 'Commission earned amount', 'affiliate-for-woocommerce' ); ?></th>
				<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; "><?php echo wp_kses_post( afwc_format_price( $commission_amount, $order_currency ) ); ?></td>
			</tr>
			<?php
		}
		?>
		<tr>
			<th class="td" scope="row" style="text-align:<?php echo esc_attr( $text_align ); ?>; "><?php echo esc_html_x( 'Referral medium', 'Referral medium', 'affiliate-for-woocommerce' ); ?></th>
			<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; "><?php echo wp_kses_post( $conversion_type ); ?></td>
		</tr>
	</table>
</div>

<?php
if ( $has_multi_tier ) {
	$header_cell = 'padding: 11px 12px; color: #414141; border-bottom: 2px solid #cccccc;';
	$body_cell   = 'padding: 11px 12px; color: #414141; border-bottom: 1px solid #e0e0e0;';
	?>
	<h2><?php echo esc_html_x( 'Commission breakdown', 'Multi-tier commission breakdown title', 'affiliate-for-woocommerce' ); ?></h2>

	<div style="margin-bottom: 40px;">
		<table cellspacing="0" cellpadding="6" role="presentation" style="width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; margin-bottom: 0.5em; border: 0;">
			<thead>
				<tr>
					<th scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo esc_attr( $header_cell ); ?>"><?php echo esc_html_x( 'Tier', 'Commission tier column', 'affiliate-for-woocommerce' ); ?></th>
					<th scope="col" style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo esc_attr( $header_cell ); ?>"><?php echo esc_html_x( 'Affiliate', 'Affiliate name', 'affiliate-for-woocommerce' ); ?></th>
					<th scope="col" style="text-align:<?php echo esc_attr( $opposite_align ); ?>; <?php echo esc_attr( $header_cell ); ?>"><?php echo esc_html_x( 'Commission', 'Commission amount column', 'affiliate-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $commissions as $commission ) {
					if ( empty( $commission ) || ! is_array( $commission ) ) {
						continue;
					}

					// translators: %d: Commission tier number (1 = the affiliate who made the sale, 2 = their parent, etc.).
					$tier_label = sprintf( esc_html_x( 'Tier %d', 'Commission tier label', 'affiliate-for-woocommerce' ), ! empty( $commission['tier'] ) ? intval( $commission['tier'] ) : 0 );

					$affiliate_label = ! empty( $commission['display_name'] ) ? $commission['display_name'] : '';
					if ( ! empty( $commission['is_direct'] ) ) {
						$affiliate_label .= ' <span style="color:#8a8a8a;">' . esc_html_x( '(made the sale)', 'Hint marking the affiliate who made the sale', 'affiliate-for-woocommerce' ) . '</span>';
					}

					$amount_display = ! empty( $commission['amount_display'] ) ? $commission['amount_display'] : '';
					?>
					<tr>
						<td style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo esc_attr( $body_cell ); ?>"><?php echo esc_html( $tier_label ); ?></td>
						<td style="text-align:<?php echo esc_attr( $text_align ); ?>; <?php echo esc_attr( $body_cell ); ?>"><?php echo wp_kses_post( $affiliate_label ); ?></td>
						<td style="text-align:<?php echo esc_attr( $opposite_align ); ?>; white-space: nowrap; <?php echo esc_attr( $body_cell ); ?>"><?php echo wp_kses_post( $amount_display ); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</div>
	<?php
}
