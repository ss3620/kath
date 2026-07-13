<?php
/**
 * Main class for Admin New Referral details in WooCommerce new order emails.
 *
 * @package  affiliate-for-woocommerce/includes/admin/
 * @since    6.7.0
 * @version  1.1.2
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
			$affiliate_details = is_callable( array( $afwc_api, 'get_affiliate_by_order' ) ) ? $afwc_api->get_affiliate_by_order( $order_id, 'all' ) : array();

			if ( empty( $affiliate_details ) || ! is_array( $affiliate_details ) ) {
				return;
			}

			$affiliate_id   = ! empty( $affiliate_details['affiliate_id'] ) ? $affiliate_details['affiliate_id'] : 0;
			$affiliate_info = get_userdata( $affiliate_id );

			$campaign_id = ! empty( $affiliate_details['campaign_id'] ) ? $affiliate_details['campaign_id'] : 0;
			if ( ! empty( $campaign_id ) ) {
				global $wpdb;
				$campaign_name = $wpdb->get_var( // phpcs:ignore
									$wpdb->prepare( // phpcs:ignore
										"SELECT title
												FROM {$wpdb->prefix}afwc_campaigns
												WHERE id = %d",
										$campaign_id
									)
				);
			}

			$medium_registry = is_callable( array( Referral_Medium_Registry::class, 'get_instance' ) ) ? Referral_Medium_Registry::get_instance() : null;
			$medium_obj      = is_callable( array( $medium_registry, 'get_registered' ) ) ? $medium_registry->get_registered( $affiliate_details['type'] ) : null;
			$medium_display  = ! empty( $medium_obj ) && $medium_obj instanceof Referral_Medium_Interface ? $medium_obj->get_label() : $affiliate_details['type'];

			$template_args = array(
				'affiliate_id'           => $affiliate_id,
				'affiliate_display_name' => ( ! empty( $affiliate_info->display_name ) ? $affiliate_info->display_name : $affiliate_info->user_nicename ),
				'campaign_id'            => $campaign_id,
				'campaign_name'          => ( ! empty( $campaign_name ) ? $campaign_name : '' ),
				'commission_amount'      => ( ! empty( $affiliate_details['amount'] ) ? $affiliate_details['amount'] : 0.00 ),
				'conversion_type'        => $medium_display,
				'order_id'               => $order_id,
				'order_currency_symbol'  => ( ! empty( $affiliate_details['currency_id'] ) ? get_woocommerce_currency_symbol( $affiliate_details['currency_id'] ) : '' ),
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
