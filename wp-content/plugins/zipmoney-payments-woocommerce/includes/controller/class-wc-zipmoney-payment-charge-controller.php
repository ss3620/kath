<?php
use \zipMoney\Api\ChargesApi;

class WC_Zip_Controller_Charge_Controller extends WC_Zip_Controller_Abstract_Controller {

	/**
	 * Create a charge
	 *
	 * @param $options => array(
	 *      'checkoutId' => '',
	 *      'result' => 'approved'
	 * )
	 * @return array
	 */
	public function create_charge( $options ) {
		 $result = array( 'result' => false );

		// validate the $options
		if ( ! isset( $options['result'] ) || ! isset( $options['checkoutId'] ) ) {
			$result['title']   = 'Invalid request';
			$result['content'] = 'There are some parameters missing in the request url.';
			wc_add_notice( __( 'The payment has been cancelled.', 'zippayment' ), 'error' );
			$result['redirect_url'] = $this->wc_get_checkout_url();
			return $result;
		}

		// clean up checkout id as our api return shopify data without checking
		if ( stripos( $options['checkoutId'], '?' ) !== false ) {
			$checkoutId = stristr( $options['checkoutId'], '?', true );
		} else {
			$checkoutId = trim( $options['checkoutId'] );
		}

		WC_Zipmoney_Payment_Gateway_Util::log(
			sprintf( 'CheckoutId: %s, Result: %s', $checkoutId, $options['result'] ),
			WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG
		);

		try {
			switch ( $options['result'] ) {
				case 'approved':
					// if it is approved, then we will create a charge

					// Once the result is approved, change the post_type from "shop_quote" to "shop_order"
					if ( $this->WC_Zipmoney_Payment_Gateway->doTokenisation() ) {
						$order_id = wc_get_order_id_by_order_key( wc_clean( wp_unslash( $options['key'] ) ) );
					}

					if ( ! $order_id ) {
						$order_id = get_option( $checkoutId );
					}
					// AU SMI adding prefix but lightbox redirect will not have prefix
					if ( ! $order_id ) {
						$order_id = get_option( 'au-' . $checkoutId );
						if ( $order_id ) {
							$checkoutId = 'au-' . $checkoutId;
						}
					}
					if ( $order_id ) {
						$chargeApi = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
							$this->WC_Zipmoney_Payment_Gateway,
							new ChargesApi()
						);
						// return charge response from zip server.
						$response = $chargeApi->create_charge(
							WC()->session,
							$checkoutId,
							$order_id,
							$this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_private_key()
						);
						if ( isset( $response['success'] ) && $response['success'] ) {
							$result['result']  = true;
							$result['order']   = $response['order'];
							$result['title']   = 'Success';
							$result['content'] = $response['message'];
							// wc_add_notice('Your zip payment successful, thank you.', 'success');
						} else {
							$result['redirect_url'] = $this->wc_get_checkout_url();
							$result['content']      = $response['message'];
							$result['title']        = 'Error';
							wc_add_notice( __( 'An error occurred while processing payment', 'zippayment' ), 'error' );
						}
					} else {
						$result['redirect_url'] = $this->wc_get_checkout_url();
						$result['content']      = 'Could not find the order, please try again later.';
						$result['title']        = 'Error';
						wc_add_notice( __( 'Could not find the order, please try again later.', 'zippament' ), 'error' );
					}
					break;
				case 'referred':
					$result['title']   = 'The payment is in referred state';
					$result['content'] = 'Your application is currently under review by zipMoney and will be processed very shortly. You can contact the customer care at customercare@zipmoney.com.au for any enquiries.';
					wc_add_notice( __( 'Payment pending, your application is currently under review and will be processed shortly.', 'zippayment' ), 'success' );
					WC()->cart->empty_cart();
					break;
				case 'declined':
					$result['title']        = 'The checkout is declined';
					$result['content']      = 'Your application has been declined by zipMoney. Please contact zipMoney for further information.';
					$result['redirect_url'] = $this->wc_get_checkout_url();
					wc_add_notice( __( 'Your application has been declined. Please contact Zip Co for further information.', 'zippayment' ), 'error' );
					// remove the wp_option
					delete_option( $checkoutId );
					break;
				case 'cancelled':
					$result['title']   = 'The checkout has been cancelled';
					$result['content'] = 'The checkout has been cancelled.';
					wc_add_notice( __( 'The payment has been cancelled.', 'zippayment' ), 'error' );
					$result['redirect_url'] = $this->wc_get_checkout_url();
					// remove the wp_option
					delete_option( $checkoutId );
					break;
			}
		} catch ( Exception $ex ) {
			$result['redirect_url'] = $this->wc_get_checkout_url();
			$result['content']      = $ex->getMessage();
			$result['title']        = 'Error';
			wc_add_notice( __( 'An error occurred while processing payment', 'zippayment' ), 'error' );
		}
		return $result;
	}

	/**
	 * Cancel an authorized charge
	 *
	 * @param $order_id
	 * @return bool
	 */
	public function cancel_charge( $order_id ) {
		$order = new WC_Order( $order_id );

		if ( empty( $order ) ) {
			// if it can't find the order
			wc_add_notice( __( 'Unable to find order by id: ' . $order_id, 'zippayment' ), 'error' );
			return false;
		}

		$WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
			$this->WC_Zipmoney_Payment_Gateway,
			new ChargesApi()
		);

		$is_success = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->cancel_order_charge(
			$order,
			$this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_private_key()
		);

		if ( $is_success == true ) {
			WC_Zipmoney_Payment_Gateway_Util::add_admin_notice( __( 'The zipMoney payment has been cancelled.', 'zippayment' ), 'success' );
		} else {
			WC_Zipmoney_Payment_Gateway_Util::add_admin_notice( __( 'Unable to cancel payment.', 'zippayment' ), 'error' );
		}
	}


	/**
	 * Capture an authorized charge
	 *
	 * @param $order_id
	 * @return bool
	 */
	public function capture_charge( $order_id ) {
		$order = new WC_Order( $order_id );

		if ( empty( $order ) ) {
			// if it can't find the order
			wc_add_notice( __( 'Unable to find order by id: ' . $order_id, 'zippayment' ), 'error' );
			return false;
		}

		$WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
			$this->WC_Zipmoney_Payment_Gateway,
			new ChargesApi()
		);

		$is_success = $WC_Zipmoney_Payment_Gateway_API_Request_Charge->capture_order_charge(
			$order,
			$this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_private_key()
		);

		if ( $is_success == true ) {
			WC_Zipmoney_Payment_Gateway_Util::add_admin_notice( 'The zipMoney payment has been captured.', 'success' );
		} else {
			WC_Zipmoney_Payment_Gateway_Util::add_admin_notice( 'Unable to capture payment.', 'error' );
		}
	}

	private function wc_get_checkout_url() {
		if ( function_exists( 'wc_get_checkout_url' ) ) {
			return wc_get_checkout_url();
		}
		global $woocommerce;

		$checkout_url = $woocommerce->cart->get_checkout_url();
		return $checkout_url;
	}
}
