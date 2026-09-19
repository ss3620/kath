<?php

use \zipMoney\Model\Authority;
use \zipMoney\ApiException;
use \zipMoney\Model\CreateChargeRequest;
use \zipMoney\Model\OrderShipping;
use \zipMoney\Model\ChargeOrder;
use \zipMoney\Model\CreateRefundRequest;
use \zipMoney\Model\Refund;
use \zipMoney\Model\CaptureChargeRequest;
use \zipMoney\ObjectSerializer;
use \zipMoney\Api\CheckoutsApi;
use \zipMoney\Api\TokensApi;
use \zipMoney\Model\CreateTokenRequest;

class WC_Zipmoney_Payment_Gateway_API_Request_Charge extends WC_Zipmoney_Payment_Gateway_API_Abstract {

	private $api_instance;

	public function __construct( WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway, $api_instance ) {
		parent::__construct( $WC_Zipmoney_Payment_Gateway );

		$this->api_instance = $api_instance;
	}


	/**
	 * Create refund by order charge
	 *
	 * @param WC_Order $order
	 * @param $api_key
	 * @param int      $amount
	 * @param string   $reason
	 * @return bool
	 */
	public function refund_order_charge( WC_Order $order, $api_key, $amount = 0, $currency, $reason = '' ) {
		parent::set_api_key( $api_key );

		try {
            $charge_id = $order->get_meta(WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, true );

			if ( empty( $charge_id ) ) {
				// if the charge id is empty, then we won't process the charge anymore
				throw new Exception( 'Empty charge id' );
			}
			if ( $amount <= 0 ) {
				throw new Exception( 'The amount should greater than 0' );
			}

			$body = new CreateRefundRequest(
				array(
					'charge_id' => $charge_id,
					'reason'    => $reason,
					'amount'    => $amount,
					'currency'  => $currency,
				)
			);

			WC_Zipmoney_Payment_Gateway_Util::log( 'Requested Refund for charge: ' . $charge_id, WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );

			// Call the API
			$refund = $this->api_instance->refundsCreate( $body, WC_Zipmoney_Payment_Gateway_Util::get_uuid() );

			if ( $refund->getId() ) {
				WC_Zipmoney_Payment_Gateway_Util::log( 'Order as been Refunded for order :' . $order->get_id() );
				// update the order info
				$this->_update_order_refund( $order, $refund );
			}

			return true;
		} catch ( ApiException $exception ) {

			WC_Zipmoney_Payment_Gateway_Util::log( $exception->getCode() . $exception->getMessage(), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );
			WC_Zipmoney_Payment_Gateway_Util::log( $exception->getResponseBody(), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );

			WC_Zipmoney_Payment_Gateway_Util::add_admin_notice( $exception->getMessage() );
			WC_Zipmoney_Payment_Gateway_Util::add_admin_notice( print_r( $exception->getResponseBody(), true ) );
		} catch ( Exception $exception ) {
			WC_Zipmoney_Payment_Gateway_Util::log( $exception->getCode() . $exception->getMessage(), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );

			WC_Zipmoney_Payment_Gateway_Util::add_admin_notice( $exception->getMessage() );
		}

		return false;
	}


	/**
	 * Update the order status
	 *
	 * @param WC_Order $order
	 * @param Refund   $refund
	 */
	private function _update_order_refund( WC_Order $order, Refund $refund ) {
		// write the order note
		$order->add_order_note( sprintf( 'The ZipMoney refund has been successfully performed. [Charge id:%s, Refund id:%s, Amount: %s]', $refund->getChargeId(), $refund->getId(), $refund->getAmount() ) );

		if ( wc_format_decimal( $order->get_total() ) == wc_format_decimal( $order->get_total_refunded() ) ) {
			// if the order is fully refunded
			$order->update_status( 'wc-refunded' );
		}
		// Clear transients
		$order_id = WC_Zipmoney_Payment_Gateway_Util::get_order_id( $order );
		// wc_delete_shop_order_transients($order_id);

		// log the message
		WC_Zipmoney_Payment_Gateway_Util::log( sprintf( 'ZipMoney refund success! [Order id: %s, Refund id:%s]', $order_id, $refund->getId() ) );
	}


	/**
	 * Cancel an authorized charge
	 *
	 * @param WC_Order $order
	 * @param $api_key
	 * @return bool
	 */
	public function cancel_order_charge( WC_Order $order, $api_key ) {
		parent::set_api_key( $api_key );

		try {
            $charge_id = $order->get_meta(WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, true );

			if ( empty( $charge_id ) ) {
				// if the charge id is empty, then we won't process the charge anymore
				throw new Exception( 'Empty charge id' );
			}

			if ( $order->get_status() != WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY_COMPARE ) {
				// if the order status is not authorized, then we won't charge it again
				throw new Exception( 'The order status is not in Authorized status' );
			}

			WC_Zipmoney_Payment_Gateway_Util::log( 'Cancel charge request: charge_id:' . $charge_id );

			$charge = $this->api_instance->chargesCancel( $charge_id, WC_Zipmoney_Payment_Gateway_Util::get_uuid() );

			WC_Zipmoney_Payment_Gateway_Util::log( 'Cancel charge response recieved' );

			if ( $charge->getState() == 'cancelled' ) {
				WC_Zipmoney_Payment_Gateway_Util::log( 'Charge has been cancelled. charge_id: ' . $charge->getId() );

				$order->update_status( 'wc-cancelled', sprintf( 'The zipMoney charge (id:%s) has been cancelled.', $charge->getId() ) );
				return true;
			}
		} catch ( ApiException $exception ) {
			WC_Zipmoney_Payment_Gateway_Util::log( $exception->getCode() . $exception->getMessage(), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );
			WC_Zipmoney_Payment_Gateway_Util::log( $exception->getResponseBody(), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );

			WC_Zipmoney_Payment_Gateway_Util::add_admin_notice( $exception->getMessage() );
			WC_Zipmoney_Payment_Gateway_Util::add_admin_notice( print_r( $exception->getResponseBody(), true ) );
		} catch ( Exception $exception ) {
			WC_Zipmoney_Payment_Gateway_Util::log( $exception->getCode() . $exception->getMessage(), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );

			WC_Zipmoney_Payment_Gateway_Util::add_admin_notice( $exception->getMessage() );
		}

		return false;
	}

	/**
	 * Capture order charge
	 *
	 * @param WC_Order $order
	 * @param $api_key
	 * @return bool
	 */
	public function capture_order_charge( WC_Order $order, $api_key ) {
		 parent::set_api_key( $api_key );

		try {
            $charge_id = $order->get_meta(WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, true );

			if ( empty( $charge_id ) ) {
				// if the charge id is empty, then we won't process the charge anymore
				throw new Exception( 'Empty charge id' );
			}

			if ( $order->get_status() != WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY_COMPARE ) {
				// if the order status is not authorized, then we won't charge it again
				throw new Exception( 'The order status is not in Authorized status' );
			}

			$body = new CaptureChargeRequest(
				array( 'amount' => $order->get_total() )
			);

			WC_Zipmoney_Payment_Gateway_Util::log( 'Capture charge for Id:' . $charge_id, WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );

			$charge = $this->api_instance->chargesCapture( $charge_id, $body, WC_Zipmoney_Payment_Gateway_Util::get_uuid() );

			if ( $charge->getState() == 'captured' || $charge->getState() == 'approved' ) {

				WC_Zipmoney_Payment_Gateway_Util::log( 'Order captured, charge successfull for: ' . $charge->getId(), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );
				$order->payment_complete( $charge->getId() );
				return true;
			} else {

				WC_Zipmoney_Payment_Gateway_Util::log( 'Charge failed. charge_id: ' . $charge->getId(), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_INFO );
				return false;
			}
		} catch ( ApiException $exception ) {
			WC_Zipmoney_Payment_Gateway_Util::handle_capture_charge_api_exception( $exception, $order );
		} catch ( Exception $exception ) {
			WC_Zipmoney_Payment_Gateway_Util::log( 'Cancel charge exception ' . $exception->getCode() . $exception->getMessage(), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_INFO );
			WC_Zipmoney_Payment_Gateway_Util::add_admin_notice( $exception->getMessage() );
		}

		return false;
	}


	/**
	 * @param WC_Session    $WC_Session
	 * @param $api_key
	 * @param WC_Order|null $order
	 * @return array    =>  array(
	 *                          'success' => bool,
	 *                          'order' => order object,
	 *                          'message' => ''
	 *                      )
	 */
	public function create_charge( WC_Session $WC_Session, $checkout_id, $orderId, $api_key ) {
		 $response = array(
			 'success' => false,
			 'message' => '',
		 );

		 parent::set_api_key( $api_key );

		 try {
			 if ( $this->WC_Zipmoney_Payment_Gateway->doTokenisation() ) {
				 $order_id = $orderId;
			 }
			 if ( ! $order_id ) {
				 $order_id = get_option( $checkout_id );
			 }
			 // $region = $this->WC_Zipmoney_Payment_Gateway->get_option(WC_Zipmoney_Payment_Gateway_Config::CONFIG_SELECT_REGION);
			 if ( $order_id == null ) {

				 WC_Zipmoney_Payment_Gateway_Util::log( ' get checkout API' );
				 $checkout = new CheckoutsApi();
				 $order_id = $checkout->checkoutsGet( $checkout_id )->getOrder()->getReference();
			 }

			 $order = new WC_Order( $order_id );
			 if ( $order->get_status() == 'processing' || $order->get_status() == 'zip-authorised' ) {
				 WC_Zipmoney_Payment_Gateway_Util::log( ' calling charge 2nd time, order completed, redirect to thank you page' );
				 wp_redirect( $order->get_checkout_order_received_url() );
				 exit;
			 }

			 $body = $this->_prepare_charges_request( $checkout_id, $order_id, $order );
			 WC_Zipmoney_Payment_Gateway_Util::log( sprintf( 'Charge request sent for order (%s)', $order_id ), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );

			 // write the charge object info to order meta

			 $user_id = $WC_Session->get( WC_Zipmoney_Payment_Gateway_Config::META_USER_ID, '' );
			 if ( ! empty( $user_id ) ) {
                 $order->update_meta_data('_customer_user', $user_id);
			 }

			 $charge = $this->api_instance->chargesCreate( $body, WC_Zipmoney_Payment_Gateway_Util::get_uuid() );

			 // if it is not successful, throw exception
			 $charge_state = $charge->getState();
			 if ( empty( $charge_state ) ) {
				 throw new Exception( 'Unable to create charges' );
			 }

			 // set the charge id to order
			 $order->update_meta_data(WC_Zipmoney_Payment_Gateway_Config::META_CHARGE_ID, $charge->getId());

			 if ( $charge->getState() == 'captured' ) {
				 $order->payment_complete( $charge->getId() );
				 do_action( 'woocommerce_checkout_order_processed', $order_id, (array) $WC_Session, $order );
				 WC_Zipmoney_Payment_Gateway_Util::log( sprintf( 'Charged successfull with id (%s)', $charge->getId() ), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );
				 WC()->cart->empty_cart();
				 $response['success'] = true;
			 } elseif ( $charge->getState() == 'authorised' ) {
				 // if it is authorised, then we will charge the order later
				 $order->add_order_note( 'A zipMoney charge authorization is completed. Waiting for shop administrator to complete the charge. Charge id: ' . $charge->getId() );
				 $order->update_status( WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY );
				 $response['success'] = true;
				 WC_Zipmoney_Payment_Gateway_Util::log( sprintf( 'Order authorised for charge (%s)', $charge->getId() ), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );
			 } elseif ( $charge->getState() == 'approved' ) {
				 $capture_charge_option = $this->WC_Zipmoney_Payment_Gateway->get_option( WC_Zipmoney_Payment_Gateway_Config::CONFIG_CHARGE_CAPTURE );
				 $is_capture            = $capture_charge_option == WC_Zipmoney_Payment_Gateway_Config::CAPTURE_CHARGE_IMMEDIATELY ? true : false;
				 if ( $is_capture ) {
					 $order->payment_complete( $charge->getId() );
					 do_action( 'woocommerce_checkout_order_processed', $order_id, (array) $WC_Session, $order );
					 WC_Zipmoney_Payment_Gateway_Util::log( sprintf( 'Charged successfull with id (%s)', $charge->getId() ), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );
				 } else {
					 $order->add_order_note( 'A zipMoney charge authorization is completed. Waiting for shop administrator to complete the charge. Charge id: ' . $charge->getId() );
					 $order->update_status( WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY );
					 WC_Zipmoney_Payment_Gateway_Util::log( sprintf( 'Order authorised for charge (%s)', $charge->getId() ), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );
				 }
				 WC()->cart->empty_cart();
				 $response['success'] = true;
			 } else {
				 // otherwise, we will cancelled the order
				 $order->update_status( 'cancelled', 'order_note' );
				 $response['message'] = 'Unable to create charge. The charge state is: ' . $charge->getState();
				 WC_Zipmoney_Payment_Gateway_Util::log( 'Order cancelled', WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );
			 }
		 } catch ( ApiException $exception ) {
			 $response = WC_Zipmoney_Payment_Gateway_Util::handle_create_charge_api_exception( $exception );
			 WC_Zipmoney_Payment_Gateway_Util::log( $response['message'], WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_INFO );
			 if ( $this->WC_Zipmoney_Payment_Gateway->doTokenisation() && $response['code'] == 403 ) {
				 $current_user = wp_get_current_user();
				 $uId          = $current_user->ID;
				 $this->_removeCustomerToken( $uId );
			 }
			 if ( ! empty( $order ) ) {
				 $order->add_order_note( $response['message'] );
				 $order->update_status( 'cancelled', 'order_note' );
			 }

			 // delete the option
			 delete_option( $checkout_id );
		 } catch ( Exception $exception ) {
			 if ( ! empty( $order ) ) {
				 $order->add_order_note( $exception->getCode() . $exception->getMessage() );
				 $order->update_status( 'cancelled', 'order_note' );
			 }

			 $response['message'] = $exception->getMessage();

			 WC_Zipmoney_Payment_Gateway_Util::log( 'Create charge exception ' . $exception->getCode() . $exception->getMessage(), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_INFO );
			 wc_add_notice( __( 'Payment error:', 'zippayment' ) . $exception->getMessage(), 'error' );
			 delete_option( $checkout_id );
		 }

		 delete_option( $checkout_id );
		 $response['order'] = $order;

		 return $response;
	}


	/**
	 * Prepare the charge request
	 *
	 * @param WC_Session $WC_Session
	 * @return \zipMoney\Model\CreateChargeRequest
	 */
	private function _prepare_charges_request( $checkout_id, $order_id, WC_Order $order ) {
		 // get the charge order
		$charge_order = $this->_create_charge_order( $order_id, $order );
		$accountType  = 'checkout_id';
		$accountValue = $checkout_id;
		if ( $this->WC_Zipmoney_Payment_Gateway->doTokenisation() ) {
			$accountType  = 'account_token';
			$accountValue = $this->getCustomerToken( $checkout_id );
		}
		// get authority
		$authority = new Authority(
			array(
				'type'  => $accountType,
				'value' => $accountValue,
			)
		);

		$capture_charge_option = $this->WC_Zipmoney_Payment_Gateway->get_option( WC_Zipmoney_Payment_Gateway_Config::CONFIG_CHARGE_CAPTURE );

		return new CreateChargeRequest(
			array(
				'authority' => $authority,
				'reference' => (string) $order_id,
				'amount'    => $this->_get_cart_total( $order ),
				'currency'  => $order->get_currency(),
				'order'     => $charge_order,
				'capture'   => $capture_charge_option == WC_Zipmoney_Payment_Gateway_Config::CAPTURE_CHARGE_IMMEDIATELY ? true : false,
			)
		);
	}


	/**
	 * Construct the charge order object
	 *
	 * @param WC_Session $WC_Session
	 * @return \zipMoney\Model\ChargeOrder
	 */
	private function _create_charge_order( $order_id, WC_Order $order ) {
		$is_pickup = false;

		// check order containing physical products, then create shipping address
		// if order contains only virtual & downloadable products shipping address is not required.
		if ( WC_Zipmoney_Order_Compatibility::has_shipping_address( $order ) ) {
			$order_shipping = new OrderShipping(
				array(
					'address' => $this->_create_shipping_address( $order ),
					'pickup'  => $is_pickup,
				)
			);
		} else {
			$is_pickup      = true;
			$order_shipping = new OrderShipping(
				array(
					'pickup' => $is_pickup,
				)
			);
		}
		$website_abbr = substr( sanitize_key( get_bloginfo( 'name' ) ), 0, 3 );

		return new ChargeOrder(
			array(
				'reference' => strtoupper( $website_abbr ) . '-' . $order_id . '-' . strtotime( 'now' ),
				'shipping'  => $order_shipping,
				'items'     => $this->_get_order_items( $order ),
			)
		);
	}

	/**
	 * save customer in the DB
	 */
	private function _saveToken( $customerId, $token ) {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'zip_tokenisation';
		$encrypttedToken = WC_Zipmoney_Payment_Gateway_Util::wpcodetips_twoway_encrypt( $token, 'e' );
		$wpdb->insert(
			$table_name,
			array(
				'customer_id' => $customerId,
				'token'       => $encrypttedToken,
			)
		);
	}

	/**
	 * check the DB for customer token.
	 * if not in the DB then get it by call token api
	 */
	public function getCustomerToken( $checkout_id ) {
		global $wpdb;
		$current_user = wp_get_current_user();
		$uId          = $current_user->ID;
		$tokenTable   = $wpdb->prefix . 'zip_tokenisation';
		$result       = $wpdb->get_results( "SELECT * FROM $tokenTable WHERE `customer_id` = $uId" );
		foreach ( $result as $customerToken ) {
			$dycrpttedToken = WC_Zipmoney_Payment_Gateway_Util::wpcodetips_twoway_encrypt( $customerToken->token, 'd' );
			return $dycrpttedToken;
		}
		// get authority
		$authority = new Authority(
			array(
				'type'  => 'checkout_id',
				'value' => $checkout_id,
			)
		);
		$tokenReq  = new CreateTokenRequest();
		$tokenReq->setAuthority( $authority );
		$tokenApi = new TokensApi();
		$token    = $tokenApi->tokensCreate( $tokenReq, WC_Zipmoney_Payment_Gateway_Util::get_uuid() );
		$this->_saveToken( $uId, $token->getValue() );
		return $token->getValue();
	}

	private function _removeCustomerToken( $customerId ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'zip_tokenisation';
		$wpdb->delete( $table_name, array( 'customer_id' => $customerId ) );
	}
}
