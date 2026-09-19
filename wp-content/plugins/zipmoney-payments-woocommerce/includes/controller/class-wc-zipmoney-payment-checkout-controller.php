<?php

use \zipMoney\Api\CheckoutsApi;
use zipMoney\Model\CurrencyUtil;

class WC_Zip_Controller_Checkout_Controller extends WC_Zip_Controller_Abstract_Controller {


	/**
	 * Convert the current checkout session to some static data
	 *
	 * @param $post_data
	 * @return array
	 */
	public function create_checkout( $post_data, $order_id ) {
		// update the customer details
		WC_Zipmoney_Payment_Gateway_Util::update_customer_details( $post_data );

		WC_Zipmoney_Payment_Gateway_Util::log( 'Checkout session started' );

		$WC_Zipmoney_Payment_Gateway_API_Request_Checkout = new WC_Zipmoney_Payment_Gateway_API_Request_Checkout(
			$this->WC_Zipmoney_Payment_Gateway,
			new CheckoutsApi()
		);
		$redirect_url                                     = WC_Zipmoney_Payment_Gateway_Util::get_complete_endpoint_url();
		$user = wp_get_current_user();
		if ( $this->WC_Zipmoney_Payment_Gateway->doTokenisation() ) {
			if ( $this->WC_Zipmoney_Payment_Gateway->customerHasToken() ) {
				WC_Zipmoney_Payment_Gateway_Util::log( 'customerHasToken' );
				$order         = wc_get_order( $order_id );
				$order_key     = $order->get_order_key();
				$redirect_url .= '&result=approved&token=true&checkoutId=checkoutId&key=' . $order_key;
				return array(
					'redirect_uri' => $redirect_url,
					'message'      => 'Redirecting to charge.',
					'result'       => 'success',
					'checkout_id'  => 'checkoutId',
					'token'        => true,
				);
			}
		} else {
			if ( $this->WC_Zipmoney_Payment_Gateway->showSaveAccountInCheckout() ) {
				$this->_removeCustomerToken( $user->ID );
			}
		}

		$checkout_response = $WC_Zipmoney_Payment_Gateway_API_Request_Checkout->create_checkout(
			WC()->session,
			$redirect_url,
			$this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_private_key(),
			$order_id
		);
		if ( isset( $checkout_response ) ) {
			$WC_Zipmoney_Payment_Gateway_Config = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config;
			$is_iframe_flow                     = $WC_Zipmoney_Payment_Gateway_Config->is_it_iframe_flow();
			$redirectUri                        = $checkout_response->getUri();
			$currency                           = get_option( 'woocommerce_currency' );
			if ( $is_iframe_flow && $currency == CurrencyUtil::CURRENCY_NZD ) {
				$redirectUri = $checkout_response->getUri() . '&embedded=true';
			}
			// save checkout Id into post meta.
			// $order_id = WC()->session->get('_post_id');
			// update_post_meta($order_id, WC_Zipmoney_Payment_Gateway_Config::META_CHECKOUT_ID, $checkout_response->getId());

			return array(
				'redirect_uri' => $redirectUri,
				'message'      => 'Redirecting to Zip.',
				'result'       => 'success',
				'checkout_id'  => $checkout_response->getId(),
			);
		}
		return array(
			'message' => __( 'An error occurred while getting the redirect url from Zip.', 'zippayment' ),
			'result'  => 'failure',
		);
	}

	private function _removeCustomerToken( $customerId ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'zip_tokenisation';
		$wpdb->delete( $table_name, array( 'customer_id' => $customerId ) );
	}
}
