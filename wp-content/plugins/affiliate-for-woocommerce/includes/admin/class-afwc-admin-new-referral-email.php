<?php
/**
 * Main class for Admin New Referral details in WooCommerce new order emails.
 *
 * @package  affiliate-for-woocommerce/includes/admin/
 * @since    6.7.0
 * @version  1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Referral_Mediums\Referral_Medium_Interface;
use AFWC\Referral_Mediums\Referral_Medium_Registry;

if ( ! class_exists( 'AFWC_Admin_New_Referral_Email' ) ) {

	/**
	 * The Admin New Referral Email class
	 */
	class AFWC_Admin_New_Referral_Email {

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'woocommerce_email_order_meta', array( $this, 'affiliate_referral_details_email' ), 10, 4 );
		}

		/**
		 * Function to add affiliate referral details in WooCommerce New Order and WooCommerce Subscriptions New Renewal Order email.
		 *
		 * @param WC_Order $order         Order instance.
		 * @param bool     $sent_to_admin If should sent to admin.
		 * @param bool     $plain_text    If is plain text email.
		 * @param object   $email         The Email object.
		 */
		public function affiliate_referral_details_email( $order = null, $sent_to_admin = false, $plain_text = false, $email = null ) {
			// Return if setting is disabled.
			if ( 'no' === get_option( 'afwc_add_referral_in_admin_emails', 'no' ) ) {
				return;
			}

			$email_id = ( is_object( $email ) && ! empty( $email->id ) ) ? $email->id : '';
			if ( empty( $email_id ) ) {
				return;
			}

			/**
			 * Filter to modify the allowed email IDs for including affiliate referral details
			 *
			 * @param array    Array of allowed email IDs.
			 * @param array    Additional information including source object
			 *
			 * @since 6.7.0
			 */
			$allowed_emails = apply_filters( 'afwc_allowed_emails_for_referral_details', array( 'new_order' ), array( 'source' => $this ) );
			if ( ! in_array( $email_id, $allowed_emails, true ) ) {
				return;
			}

			if ( ! $order instanceof WC_Order ) {
				return;
			}

			$is_commission_recorded = $order->get_meta( 'is_commission_recorded', true );
			if ( 'yes' !== $is_commission_recorded ) {
				return;
			}

			$order_id          = is_callable( array( $order, 'get_id' ) ) ? $order->get_id() : 0;
			$afwc_api          = AFWC_API::get_instance();
			$affiliate_details = is_callable( array( $afwc_api, 'get_affiliates_by_order' ) ) ? $afwc_api->get_affiliates_by_order( $order_id ) : array();

			if ( empty( $affiliate_details ) || ! is_array( $affiliate_details ) ) {
				return;
			}

			// The direct affiliate row is ordered first and carries the shared context (campaign, medium, currency).
			$direct_affiliate = ! empty( $affiliate_details[0] ) && is_array( $affiliate_details[0] ) ? $affiliate_details[0] : array();
			$currency         = ! empty( $direct_affiliate['currency_id'] ) ? $direct_affiliate['currency_id'] : '';

			// Build the per-tier commission breakdown and the total.
			$commissions      = array();
			$total_commission = 0.00;
			foreach ( $affiliate_details as $index => $affiliate_detail ) {
				if ( empty( $affiliate_detail ) || ! is_array( $affiliate_detail ) ) {
					continue;
				}

				$affiliate_id = ! empty( $affiliate_detail['affiliate_id'] ) ? intval( $affiliate_detail['affiliate_id'] ) : 0;
				$user         = ! empty( $affiliate_id ) ? get_userdata( $affiliate_id ) : false;
				$amount       = ! empty( $affiliate_detail['amount'] ) ? floatval( $affiliate_detail['amount'] ) : 0.00;

				if ( ! empty( $user ) && ! empty( $user->display_name ) ) {
					$display_name = $user->display_name;
				} elseif ( ! empty( $user ) && ! empty( $user->user_nicename ) ) {
					$display_name = $user->user_nicename;
				} else {
					// translators: %d: Affiliate (user) ID for an affiliate whose user account no longer exists.
					$display_name = sprintf( esc_html_x( 'Affiliate #%d', 'Fallback affiliate label when user is deleted', 'affiliate-for-woocommerce' ), $affiliate_id );
				}

				$commissions[] = array(
					'tier'           => intval( $index ) + 1,
					'is_direct'      => ( isset( $affiliate_detail['reference'] ) && '' === $affiliate_detail['reference'] ),
					'affiliate_id'   => $affiliate_id,
					'display_name'   => $display_name,
					'amount_display' => afwc_format_price( $amount, $currency ),
				);

				$total_commission += $amount;
			}

			// Bail if no valid commission row could be built.
			if ( empty( $commissions ) ) {
				return;
			}

			$campaign_id   = ! empty( $direct_affiliate['campaign_id'] ) ? intval( $direct_affiliate['campaign_id'] ) : 0;
			$campaign_name = ! empty( $campaign_id ) ? afwc_get_campaign_title( $campaign_id ) : '';

			$medium_type     = ! empty( $direct_affiliate['type'] ) ? $direct_affiliate['type'] : '';
			$medium_registry = is_callable( array( Referral_Medium_Registry::class, 'get_instance' ) ) ? Referral_Medium_Registry::get_instance() : null;
			$medium_obj      = ( ! empty( $medium_registry ) && is_callable( array( $medium_registry, 'get_registered' ) ) ) ? $medium_registry->get_registered( $medium_type ) : null;
			$medium_display  = ( ! empty( $medium_obj ) && $medium_obj instanceof Referral_Medium_Interface ) ? $medium_obj->get_label() : $medium_type;

			$template_args = array(
				'affiliate_id'           => ! empty( $direct_affiliate['affiliate_id'] ) ? intval( $direct_affiliate['affiliate_id'] ) : 0,
				'affiliate_display_name' => ! empty( $commissions[0]['display_name'] ) ? $commissions[0]['display_name'] : '',
				'campaign_id'            => $campaign_id,
				'campaign_name'          => ! empty( $campaign_name ) ? $campaign_name : '',
				'commission_amount'      => ! empty( $direct_affiliate['amount'] ) ? $direct_affiliate['amount'] : 0.00,
				'conversion_type'        => $medium_display,
				'order_id'               => $order_id,
				'order_currency'         => $currency,
				'order_currency_symbol'  => ! empty( $currency ) ? get_woocommerce_currency_symbol( $currency ) : '',
				'commissions'            => $commissions,
				'has_multi_tier'         => count( $commissions ) > 1,
				'total_commission'       => afwc_format_price( $total_commission, $currency ),
			);

			$template_name = $plain_text ? 'plain/afwc-admin-new-referral.php' : 'afwc-admin-new-referral.php';

			global $affiliate_for_woocommerce;

			wc_get_template(
				$template_name,
				$template_args,
				is_callable( array( $affiliate_for_woocommerce, 'get_template_base_dir' ) ) ? $affiliate_for_woocommerce->get_template_base_dir( $template_name ) : '',
				AFWC_PLUGIN_DIRPATH . '/templates/emails/'
			);
		}
	}

}

return new AFWC_Admin_New_Referral_Email();
