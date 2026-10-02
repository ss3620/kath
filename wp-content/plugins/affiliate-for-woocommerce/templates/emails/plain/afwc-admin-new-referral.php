<?php
/**
 * Admin New Referral Email
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/emails/plain/
 * @since    6.7.0
 * @version  1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$has_multi_tier = ! empty( $has_multi_tier );
$commissions    = ! empty( $commissions ) && is_array( $commissions ) ? $commissions : array();

echo esc_html_x( 'Affiliate information', 'Affiliate section title', 'affiliate-for-woocommerce' ) . "\n\n";

echo esc_html_x( 'Affiliate', 'Affiliate name', 'affiliate-for-woocommerce' ) . "\t " . wp_kses_post( $affiliate_display_name ) . "\n";

if ( ! empty( $campaign_id ) ) {
	echo esc_html_x( 'Campaign', 'Campaign name and id', 'affiliate-for-woocommerce' ) . "\t " . wp_kses_post( $campaign_name . ' (#' . $campaign_id . ')' ) . "\n";
}

if ( $has_multi_tier ) {
	echo esc_html_x( 'Total commission', 'Total commission across all tiers', 'affiliate-for-woocommerce' ) . "\t " . wp_kses_post( $total_commission ) . "\n";
} else {
	echo esc_html_x( 'Commission earned', 'Commission earned amount', 'affiliate-for-woocommerce' ) . "\t " . wp_kses_post( afwc_format_price( $commission_amount, $order_currency ) ) . "\n";
}

echo esc_html_x( 'Referral medium', 'Referral medium', 'affiliate-for-woocommerce' ) . "\t " . wp_kses_post( $conversion_type ) . "\n";

if ( $has_multi_tier ) {
	echo "\n" . esc_html_x( 'Commission breakdown', 'Multi-tier commission breakdown title', 'affiliate-for-woocommerce' ) . "\n\n";

	foreach ( $commissions as $commission ) {
		if ( empty( $commission ) || ! is_array( $commission ) ) {
			continue;
		}

		// translators: %d: Commission tier number (1 = the affiliate who made the sale, 2 = their parent, etc.).
		$tier_label   = sprintf( esc_html_x( 'Tier %d', 'Commission tier label', 'affiliate-for-woocommerce' ), ! empty( $commission['tier'] ) ? intval( $commission['tier'] ) : 0 );
		$display_name = ! empty( $commission['display_name'] ) ? $commission['display_name'] : '';
		$amount       = ! empty( $commission['amount_display'] ) ? $commission['amount_display'] : '';

		echo wp_kses_post( $tier_label ) . "\t " . wp_kses_post( $display_name ) . "\t " . wp_kses_post( $amount ) . "\n";
	}
}

echo "\n\n----------------------------------------\n\n";
