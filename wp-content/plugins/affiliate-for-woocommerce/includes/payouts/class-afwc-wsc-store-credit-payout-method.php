<?php
/**
 * Main class for Smart Coupons Store Credit Payout method.
 *
 * @package    affiliate-for-woocommerce/includes/payouts/
 * @since      8.21.0
 * @version    1.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_WSC_Store_Credit_Payout_Method' ) ) {

	/**
	 * Affiliate Coupon Store Credit Payout method class.
	 */
	class AFWC_WSC_Store_Credit_Payout_Method {

		/**
		 * Main method to process payouts.
		 *
		 * @param array $params the params to initialize the payout.
		 *
		 * @throws Exception When payout failed.
		 * @return array|WP_Error The response.
		 */
		public function execute_payout( $params = array() ) {
			try {
				// Requirements checks.
				if ( empty( $params['affiliate_id'] ) || empty( $params['currency'] ) || empty( $params['referrals'] ) ) {
					throw new Exception( _x( 'Required parameter missing for the Coupon Store Credit Payout', 'Error message for missing requirements for Coupon Store Credit payout', 'affiliate-for-woocommerce' ) );
				}

				// Validation.
				$result = $this->validate( $params );

				if ( is_wp_error( $result ) ) {
					throw new Exception( is_callable( array( $result, 'get_error_message' ) ) ? $result->get_error_message() : _x( 'Coupon Store Credit payout validation failed', 'Error message for Coupon Store Credit payout validation failed', 'affiliate-for-woocommerce' ) );
				}

				if ( true !== $result ) {
					throw new Exception( _x( 'Something went wrong during Coupon Store Credit payout', 'Error message for Coupon Store Credit payout failed', 'affiliate-for-woocommerce' ) );
				}

				// Format the records to payout.
				$records = $this->get_records( absint( $params['affiliate_id'] ), $params['referrals'] );

				if ( is_wp_error( $records ) ) {
					throw new Exception( is_callable( array( $records, 'get_error_message' ) ) ? $records->get_error_message() : _x( 'Coupon Store Credit payout failed', 'Error message for Coupon Store Credit payout failed', 'affiliate-for-woocommerce' ) );
				}

				$coupon_api = WSC_AFWC_Store_Credit_API::get_instance();

				$payout_result = is_callable( array( $coupon_api, 'process_coupon_payout' ) ) ? $coupon_api->process_coupon_payout( $records, $params['currency'] ) : array( 'ACK' => 'Error' );

				if ( is_wp_error( $payout_result ) || empty( $payout_result ) || ! is_array( $payout_result ) ) {
					/* translators: Coupon Fixed Cart response message */
					throw new Exception( sprintf( _x( 'Coupon Store Credit payout failed. Message: %1$s, Response: %2$s.', 'payout failed debug message', 'affiliate-for-woocommerce' ), is_callable( array( $payout_result, 'get_error_message' ) ) ? $payout_result->get_error_message() : '', print_r( $payout_result, true ) ) ); // phpcs:ignore
				}

				if ( ! empty( $payout_result['ACK'] ) && 'Success' === $payout_result['ACK'] ) {
					return array(
						'success'        => true,
						'transaction_id' => ( ! empty( $payout_result['coupon_code'] ) ? $payout_result['coupon_code'] : '' ), // Send coupon code back as transaction_id.
						'amount'         => ( ! empty( $payout_result['amount'] ) ? floatval( $payout_result['amount'] ) : 0.00 ),
						'receiver'       => ( ! empty( $records['email'] ) ? $records['email'] : '' ),
					);
				}

				throw new Exception( _x( 'Something went wrong', 'Coupon Store Credit payout failed message', 'affiliate-for-woocommerce' ) );

			} catch ( Exception $e ) {
				if ( is_callable( array( $e, 'getMessage' ) ) ) {
					Affiliate_For_WooCommerce::log( 'error', $e->getMessage() );
				}
				return new WP_Error( 'afwc-coupon-fixed-cart-payout-error', is_callable( array( $e, 'getMessage' ) ) ? $e->getMessage() : '' );
			}
		}

		/**
		 * Validate the records for payout.
		 *
		 * @param array $params The params.
		 *
		 * @throws Exception When Validation failed.
		 * @return bool|WP_Error Return true if validated otherwise WP_Error instance.
		 */
		public function validate( $params = array() ) {
			try {
				if ( empty( $params['currency'] ) ) {
					throw new Exception( _x( 'Currency is not provided', 'Coupon Store Credit Payout validation failed message for currency', 'affiliate-for-woocommerce' ) );
				}

				/**
				 * Action to validate the Coupon Store Credit payout method.
				 *
				 * @param array $params The params.
				 *
				 * @since 8.21.0
				 */
				do_action( 'afwc_coupon_fixed_cart_payout_method_validation', $params );

				return true;
			} catch ( Exception $e ) {
				return new WP_Error( 'afwc-coupon-fixed-cart-payout-validation-error', is_callable( array( $e, 'getMessage' ) ) ? $e->getMessage() : '' );
			}
		}

		/**
		 * Filter and arrange the records for payout.
		 *
		 * @param int   $affiliate_id The affiliate ID.
		 * @param array $records The records.
		 *
		 * @return array|WP_Error The array of response otherwise WP_Error instance if failed.
		 */
		public function get_records( $affiliate_id = 0, $records = array() ) {

			$affiliate_id = absint( $affiliate_id );

			if ( empty( $affiliate_id ) || empty( $records ) || ! is_array( $records ) ) {
				return array();
			}

			$affiliate_account_email = '';
			$affiliate_user          = get_user_by( 'id', $affiliate_id );
			if ( is_object( $affiliate_user ) && $affiliate_user instanceof WP_User ) {
				$affiliate_account_email = ( ! empty( $affiliate_user->user_email ) ? $affiliate_user->user_email : '' );
			}

			if ( empty( $affiliate_account_email ) ) {
				/* translators: The affiliate ID */
				return new WP_Error( 'afwc-coupon-payout-email-not-found', sprintf( _x( 'Account email address missing for affiliate user ID: %d', 'Coupon Store Credit Payout validation failed message for account email address missing', 'affiliate-for-woocommerce' ), $affiliate_id ) );
			}

			return array(
				'id'     => $affiliate_id,
				'amount' => ! empty( $records['amount'] ) ? floatval( $records['amount'] ) : 0,
				'note'   => ! empty( $records['note'] ) ? $records['note'] : '',
				'email'  => sanitize_email( $affiliate_account_email ),
			);
		}
	}
}
