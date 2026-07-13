<?php
/**
 * My Account > Affiliate > Reports
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/my-account/dashboard/payouts/
 * @since    8.5.0
 * @version  2.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Note: We do not recommend removing existing id & classes in HTML.
?>
<section id="afwc_kpi_section_wrapper" aria-label="<?php echo esc_attr_x( 'Payout Summary', 'ARIA label for payout KPI section', 'affiliate-for-woocommerce' ); ?>">
	<dl class="afwc-kpi-row">
		<?php $label = _x( 'Paid Commissions', 'Label for paid commissions in my account', 'affiliate-for-woocommerce' ); ?>
		<div class="afwc-kpi-box" role="group" aria-label="<?php echo esc_attr( $label ); ?>">
			<dt class="afwc-kpi-title">
				<?php echo esc_html( $label ); ?>
			</dt>
			<dd class="afwc-kpi-number">
				<?php echo wp_kses_post( afwc_format_price( ! empty( $kpis['paid_commission'] ) ? floatval( $kpis['paid_commission'] ) : 0 ) ); ?>
			</dd>
		</div>
		<div class="kpi-separator-wrapper" aria-hidden="true">
			<svg class="afwc-kpi-separator" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 512">
				<path d="M285.476 272.971L91.132 467.314c-9.373 9.373-24.569 9.373-33.941 0l-22.667-22.667c-9.357-9.357-9.375-24.522-.04-33.901L188.505 256 34.484 101.255c-9.335-9.379-9.317-24.544.04-33.901l22.667-22.667c9.373-9.373 24.569-9.373 33.941 0L285.475 239.03c9.373 9.372 9.373 24.568.001 33.941z"></path>
			</svg>
		</div>
		<?php $label = _x( 'Unpaid Commissions', 'Label for unpaid commissions in my account', 'affiliate-for-woocommerce' ); ?>
		<div class="afwc-kpi-box" role="group" aria-label="<?php echo esc_attr( $label ); ?>">
			<dt class="afwc-kpi-title">
				<?php echo esc_html( $label ); ?>
			</dt>
			<dd class="afwc-kpi-number">
				<?php echo wp_kses_post( afwc_format_price( ! empty( $kpis['unpaid_commission'] ) ? floatval( $kpis['unpaid_commission'] ) : 0 ) ); ?>
			</dd>
		</div>
	</dl>
</section>
<?php
