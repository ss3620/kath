<?php

use \zipMoney\ApiException;
use \zipMoney\Model\CheckoutConfiguration;
use \zipMoney\Model\CheckoutOrder;
use \zipMoney\Model\CreateCheckoutRequest;
use \zipMoney\Model\OrderShipping;
use \zipMoney\Model\Shopper;
use \zipMoney\Model\CheckoutFeatures;
use \zipMoney\Model\CreateCheckoutRequestFeaturesTokenisation as Tokenisation;

class WC_Zipmoney_Payment_Gateway_API_Request_Checkout extends WC_Zipmoney_Payment_Gateway_API_Abstract {

	private $api_instance;

	public function __construct( WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway, $api_instance ) {
		parent::__construct( $WC_Zipmoney_Payment_Gateway );

		$this->api_instance = $api_instance;
	}

	/**
	 * Create checkout to API
	 *
	 * @param WC_Session   $WC_Session
	 * @param $redirect_url
	 * @param $api_key
	 * @return null|\zipMoney\Model\Checkout
	 */
	public function create_checkout( WC_Session $WC_Session, $redirect_url, $api_key, $order_id ) {
		try {
			parent::set_api_key( $api_key );
			$order                              = new WC_Order( $order_id );
			$WC_Zipmoney_Payment_Gateway_Config = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config;
			$is_iframe_flow                     = $WC_Zipmoney_Payment_Gateway_Config->is_it_iframe_flow();
			if ( $is_iframe_flow ) {
				$redirect_url .= '&iframe=1';
			}
			$body = $this->_prepare_request_for_checkout( $WC_Session, $redirect_url, $order );
			// log the body information
			WC_Zipmoney_Payment_Gateway_Util::log( 'Sending checkout request to API', WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_INFO );

			WC_Zipmoney_Payment_Gateway_Util::log( WC_Zipmoney_Payment_Gateway_Util::object_json_encode( $body ), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );

			$checkout = $this->api_instance->checkoutsCreate( $body );

			WC_Zipmoney_Payment_Gateway_Util::log( 'Return from checkout API', WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );
			WC_Zipmoney_Payment_Gateway_Util::log( WC_Zipmoney_Payment_Gateway_Util::object_json_encode( $checkout ), WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_DEBUG );

			// set user id if there is any
			if ( is_user_logged_in() ) {
				$WC_Session->set( WC_Zipmoney_Payment_Gateway_Config::META_USER_ID, get_current_user_id() );
			}

			// save the checkout and session into option table
			if ( version_compare( WC()->version, '3.2.0', '>=' ) ) {
				update_option( $checkout->getId(), $order_id, false );
			} else {
				update_option( $checkout->getId(), $order_id );
			}

			return $checkout;

		} catch ( ApiException $exception ) {
			WC_Zipmoney_Payment_Gateway_Util::log( $exception->getCode() . $exception->getMessage() );
			WC_Zipmoney_Payment_Gateway_Util::log( $exception->getResponseBody() );

		} catch ( Exception $exception ) {
			WC_Zipmoney_Payment_Gateway_Util::log( $exception->getCode() . $exception->getMessage() );
		}

	}

	/**
	 * Prepare the checkout request
	 *
	 * @param WC_Session   $WC_Session
	 * @param $redirect_url
	 * @return \zipMoney\Model\CreateCheckoutRequest
	 */
	private function _prepare_request_for_checkout( WC_Session $WC_Session, $redirect_url, $order ) {
		// construct the shopper
		$shopper = $this->_create_shopper( $order );

		// get the checkout object
		$checkout_order = $this->_create_checkout_order( $WC_Session, $order );

		// get the config
		$checkout_configuration = new CheckoutConfiguration(
			array(
				'redirect_uri' => $redirect_url,
			)
		);
		$checkoutReq            = new CreateCheckoutRequest();
		$checkoutReq->setType( 'standard' )
			->setShopper( $shopper )
			->setOrder( $checkout_order )
			->setMetadata( $this->_getMetadata() )
			->setConfig( $checkout_configuration );
		if ( $this->WC_Zipmoney_Payment_Gateway->doTokenisation() ) {
			$checkoutReq->setFeatures( $this->getTokenisationFeature() );
		}
		return $checkoutReq;
	}

	/**
	 * get tokenisation feature when tokenisation is enable
	 */
	public function getTokenisationFeature() {
		$feature      = new CheckoutFeatures();
		$tokenisation = new Tokenisation();
		$tokenisation->setRequired( true );
		$feature->setTokenisation( $tokenisation );

		return $feature;
	}

	/**
	 * Create the shopper object
	 *
	 * @param WC_Session $WC_Session
	 * @return \zipMoney\Model\Shopper
	 */
	private function _create_shopper( WC_Order $order ) {
		// $billing_array = $WC_Session->get('zip_billing_details');

		// get shopper's data
		if ( WC_Zipmoney_Payment_Gateway_Util::is_wc_3() ) {
			$data = array(
				'first_name'      => $order->get_billing_first_name() ? $order->get_billing_first_name() : null,
				'last_name'       => $order->get_billing_last_name() ? $order->get_billing_last_name() : null,
				'phone'           => $order->get_billing_phone() ? $order->get_billing_phone() : null,
				'email'           => $order->get_billing_email() ? $order->get_billing_email() : null,
				'billing_address' => $this->_create_billing_address( $order ),
			);
		} else {
			$billing_address = $order->get_address();

			$data = array(
				'first_name'      => $billing_address['first_name'] ? $billing_address['first_name'] : null,
				'last_name'       => $billing_address['last_name'] ? $billing_address['last_name'] : null,
				'phone'           => $billing_address['phone'] ? $billing_address['phone'] : null,
				'email'           => $billing_address['email'] ? $billing_address['email'] : null,
				'billing_address' => $this->_create_billing_address( $order ),
			);
		}

		// get teh shopper statics if it's available
		/*
		$shopper_statistics = $this->_get_shopper_statistics();
		if (!empty($shopper_statistics)) {
			$data['statistics'] = $shopper_statistics;
		}*/

		return new Shopper( $data );
	}

	/**
	 * Create checkout order object
	 *
	 * @param WC_Session $WC_Session
	 * @return \zipMoney\Model\CheckoutOrder
	 */
	private function _create_checkout_order( WC_Session $WC_Session, $order ) {
		 $chosen_shipping_methods = $WC_Session->get( 'chosen_shipping_methods', array() );

		$is_pickup = in_array( 'local_pickup', $chosen_shipping_methods );

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

		// Create the checkout order
		$checkout_order = new CheckoutOrder(
			array(
				'amount'         => $this->_get_cart_total( $order ),
				'currency'       => get_woocommerce_currency(),
				'shipping'       => $order_shipping,
				'reference'      => (string) WC_Zipmoney_Order_Compatibility::get_id( $order ),
				'items'          => $this->_get_order_items( $order ),
				'cart_reference' => (string) WC_Zipmoney_Order_Compatibility::get_id( $order ),
			)
		);

		return $checkout_order;
	}
}
