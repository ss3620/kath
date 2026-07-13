<?php
use \zipMoney\Model\ShopperStatistics;
use \zipMoney\Model\OrderItem;
use \zipMoney\Model\Address;


class WC_Zipmoney_Payment_Gateway_API_Abstract {
	protected $WC_Zipmoney_Payment_Gateway;

	public function __construct( WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway ) {
		$this->WC_Zipmoney_Payment_Gateway = $WC_Zipmoney_Payment_Gateway;

		// set the environment
		$is_sandbox = $this->WC_Zipmoney_Payment_Gateway->get_environment_status();

		if ( $is_sandbox == true ) {
			zipMoney\Configuration::getDefaultConfiguration()->setEnvironment( 'sandbox' );
		} else {
			zipMoney\Configuration::getDefaultConfiguration()->setEnvironment( 'production' );
		}

		// set the platform string
		zipMoney\Configuration::getDefaultConfiguration()->setPlatform(
			WC_Zipmoney_Payment_Gateway_Util::get_platform_string(
				$this->WC_Zipmoney_Payment_Gateway
			)
		);

	}

	/**
	 * Set the api key
	 *
	 * @param $api_key
	 */
	protected function set_api_key( $api_key ) {
		zipMoney\Configuration::getDefaultConfiguration()->setApiKey( 'Authorization', 'Bearer ' . $api_key );
		zipMoney\Configuration::getDefaultConfiguration()->setCurlTimeout( 30 );
	}

	/**
	 * Get the login user statistics
	 *
	 * @return \zipMoney\Model\ShopperStatistics
	 */
	protected function _get_shopper_statistics() {
		if ( is_user_logged_in() == false ) {
			// we won't return anything if the user is not login
			return null;
		}

		$current_user    = wp_get_current_user();
        $customer_orders = wc_get_orders( array(
            'limit'       => -1,
            'customer_id' => get_current_user_id(),
            'status'      => array( 'completed', 'refunded' ),
        ) );

		$account_created      = DateTime::createFromFormat( 'Y-m-d H:i:s', $current_user->get( 'user_registered' ) );
		$sales_total_count    = 0;
		$sales_total_amount   = 0;
		$sales_avg_amount     = 0;
		$sales_max_amount     = 0;
		$refunds_total_amount = 0;
		$currency             = get_woocommerce_currency();
		$last_login           = DateTime::createFromFormat( 'Y-m-d H:i:s', $current_user->get( 'user_login' ) );

		if ( ! empty( $customer_orders ) ) {
			foreach ( $customer_orders as $order ) {

				if ( $order->get_status() == 'completed' ) {
					$sales_total_count++;
					$sales_total_amount += round( $order->get_total(), 2 );

					if ( $sales_max_amount < $order->get_total() ) {
						$sales_max_amount = round( $order->get_total(), 2 );
					}
				} elseif ( $order->get_status() == 'refunded' ) {
					$refunds_total_amount += round( $order->get_total(), 2 );
				}
			}
		}

		if ( $sales_total_count > 0 ) {
			$sales_avg_amount = round( $sales_total_count / $sales_total_count, 2 );
		}

		$data = array(
			'sales_total_count'    => $sales_total_count,
			'sales_total_amount'   => $sales_total_amount,
			'sales_avg_amount'     => $sales_avg_amount,
			'sales_max_amount'     => $sales_max_amount,
			'refunds_total_amount' => $refunds_total_amount,
			'currency'             => $currency,
		);

		if ( ! empty( $account_created ) ) {
			$data['account_created'] = $account_created;
		}

		if ( ! empty( $last_login ) ) {
			$data['last_login'] = $last_login;
		}

		return new ShopperStatistics( $data );
	}

	protected function _get_order_item_data( $item ) {
		if ( WC_Zipmoney_Payment_Gateway_Util::is_wc_3() ) {
			$product_id        = $item->get_product_id();
			$item_quantity     = intval( $item->get_quantity() );
			$item_subtotal     = round( $item->get_subtotal(), 2 );
			$item_subtotal_tax = round( $item->get_subtotal_tax(), 2 );
		} else {
			$product_id        = $item['item_meta']['_product_id'][0];
			$item_quantity     = intval( $item['item_meta']['_qty'][0] );
			$item_subtotal     = round( $item['item_meta']['_line_subtotal'][0], 2 );
			$item_subtotal_tax = round( $item['item_meta']['_line_subtotal_tax'][0], 2 );
		}

		$product = new WC_Product( $product_id );

		$order_item_data = array(
			'name'         => $product->get_title(),
			'amount'       => round( ( floatval( $item_subtotal ) + floatval( $item_subtotal_tax ) ) / $item_quantity, 2 ),
			'reference'    => strval( $product_id ),
			'quantity'     => $item_quantity,
			'type'         => 'sku',
			'item_uri'     => $product->get_permalink(),
			'product_code' => $product->get_sku(),
		);

		$attachment_ids = WC_Zipmoney_Payment_Gateway_Util::get_product_images_ids( $product );
		if ( ! empty( $attachment_ids ) ) {
			$image_uri                    = wp_get_attachment_url( $attachment_ids[0] );
			$order_item_data['image_uri'] = $image_uri ? $image_uri : null;
		}

		return $order_item_data;
	}

	/**
	 * Create the order items
	 *
	 * @param WC_Order $order
	 * @return array
	 */
	protected function _get_order_items( WC_Order $order ) {
		 $order_items = array();

		foreach ( $order->get_items() as $product_item ) {

			$order_item_data = $this->_get_order_item_data( $product_item );

			$order_items[] = new OrderItem( $order_item_data );
		}

		$itemTotal = 0.0;
		foreach ( $order_items as $item ) {
			$itemTotal += round( ( round( $item->getAmount(), 2 ) * $item->getQuantity() ), 2 );
		}

		// get the shipping cost
		$shipping_amount = 0.0;
		if ( WC_Zipmoney_Payment_Gateway_Util::is_wc_3() ) {
			$shipping_amount = round( $order->get_shipping_total(), 2 ) + round( $order->get_shipping_tax(), 2 );
		} else {
			$shipping_amount = round( $order->get_total_shipping(), 2 ) + round( $order->get_shipping_tax(), 2 );
		}

		if ( $shipping_amount > 0 ) {
			$order_items[] = new OrderItem(
				array(
					'name'      => 'Shipping cost',
					'amount'    => round( floatval( $shipping_amount ), 2 ),
					'reference' => 'shipping',
					'quantity'  => 1,
					'type'      => 'shipping',
				)
			);
		}

		// @HOTFIX  re-calculate discount or surcharge
		$diff = round( $order->get_total() - round( $shipping_amount, 2 ) - $itemTotal, 2 );
		WC_Zipmoney_Payment_Gateway_Util::log( 'total:' . $order->get_total() );
		WC_Zipmoney_Payment_Gateway_Util::log( 'shipping:' . $shipping_amount );
		WC_Zipmoney_Payment_Gateway_Util::log( 'items:' . $itemTotal );

		// surcharge case
		if ( $diff > 0 ) {
			$order_items[] = new OrderItem(
				array(
					'name'      => 'other',
					'amount'    => $diff,
					'reference' => 'other',
					'quantity'  => 1,
					'type'      => 'sku',
				)
			);
		}
		// discount case
		if ( $diff < 0 ) {
			$order_items[] = new OrderItem(
				array(
					'name'      => 'Discount',
					'amount'    => $diff,
					'reference' => 'discount',
					'quantity'  => 1,
					'type'      => 'discount',
				)
			);
		}

		return $order_items;
	}


	/**
	 * Create the billing address
	 *
	 * @param array $billing_array => array(
	 *      'zip_billing_address_1' => '',
	 *      'zip_billing_address_2' =>,
	 *      'zip_billing_city' =>,
	 *      'zip_billing_state' =>,
	 *      'zip_billing_postcode' =>,
	 *      'zip_billing_country' =>,
	 *      'zip_billing_first_name' =>,
	 *      'zip_billing_last_name' =>
	 * )
	 * @return \zipMoney\Model\Address
	 */
	protected function _create_billing_address( WC_Order $order ) {
		if ( WC_Zipmoney_Payment_Gateway_Util::is_wc_3() ) {
			return new Address(
				array(
					'line1'       => $order->get_billing_address_1() ? $order->get_billing_address_1() : null,
					'line2'       => $order->get_billing_address_2() ? $order->get_billing_address_2() : null,
					'city'        => $order->get_billing_city() ? $order->get_billing_city() : null,
					'state'       => $order->get_billing_state() ? $order->get_billing_state() : null,
					'postal_code' => $order->get_billing_postcode() ? $order->get_billing_postcode() : null,
					'country'     => $order->get_billing_country() ? $order->get_billing_country() : null,
					'first_name'  => $order->get_billing_first_name() ? $order->get_billing_first_name() : null,
					'last_name'   => $order->get_billing_last_name() ? $order->get_billing_last_name() : null,
				)
			);
		} else {
			$billing_address = $order->get_address();

			return new Address(
				array(
					'line1'       => $billing_address['address_1'] ? $billing_address['address_1'] : null,
					'line2'       => $billing_address['address_2'] ? $billing_address['address_2'] : null,
					'city'        => $billing_address['city'] ? $billing_address['city'] : null,
					'state'       => $billing_address['state'] ? $billing_address['state'] : null,
					'postal_code' => $billing_address['postcode'] ? $billing_address['postcode'] : null,
					'country'     => $billing_address['country'] ? $billing_address['country'] : null,
					'first_name'  => $billing_address['first_name'] ? $billing_address['first_name'] : null,
					'last_name'   => $billing_address['last_name'] ? $billing_address['last_name'] : null,
				)
			);

		}
	}

	/**
	 * Create the shipping address
	 *
	 * @param array $shipping_array
	 * @return \zipMoney\Model\Address
	 */
	protected function _create_shipping_address( WC_Order $order ) {
		if ( WC_Zipmoney_Payment_Gateway_Util::is_wc_3() ) {
				return new Address(
					array(
						'line1'       => $order->get_shipping_address_1() ? $order->get_shipping_address_1() : null,
						'line2'       => $order->get_shipping_address_2() ? $order->get_shipping_address_2() : null,
						'city'        => $order->get_shipping_city() ? $order->get_shipping_city() : null,
						'state'       => $order->get_shipping_state() ? $order->get_shipping_state() : null,
						'postal_code' => $order->get_shipping_postcode() ? $order->get_shipping_postcode() : null,
						'country'     => $order->get_shipping_country() ? $order->get_shipping_country() : null,
						'first_name'  => $order->get_shipping_first_name() ? $order->get_shipping_first_name() : null,
						'last_name'   => $order->get_shipping_last_name() ? $order->get_shipping_last_name() : null,
					)
				);
		} else {
			$shipping_address = $order->get_address( 'shipping' );
			return new Address(
				array(
					'line1'       => $shipping_address['address_1'] ? $shipping_address['address_1'] : null,
					'line2'       => $shipping_address['address_2'] ? $shipping_address['address_2'] : null,
					'city'        => $shipping_address['city'] ? $shipping_address['city'] : null,
					'state'       => $shipping_address['state'] ? $shipping_address['state'] : null,
					'postal_code' => $shipping_address['postcode'] ? $shipping_address['postcode'] : null,
					'country'     => $shipping_address['country'] ? $shipping_address['country'] : null,
					'first_name'  => $shipping_address['first_name'] ? $shipping_address['first_name'] : null,
					'last_name'   => $shipping_address['last_name'] ? $shipping_address['last_name'] : null,
				)
			);
		}

	}

	/**
	 * Retrieve cart total amount regardless of woocommerce version
	 */
	protected function _get_cart_total( WC_Order $order) {
		return $order->get_total();
	}

	/**
	 * Returns the prepared metadata model
	 *
	 * @return ZipMoney\Model\Metadata
	 */
	protected function _getMetadata() {
		 // object not working must use array
		global $woocommerce;
		$metadata['platform'] = 'Woocommerce';
		if ( function_exists( 'WC' ) || ! empty( $woocommerce ) ) {
			$woocommerce_version          = function_exists( 'WC' ) ? WC()->version : $woocommerce->version;
			$metadata['platform_version'] = $woocommerce_version;
		}
		$metadata['plugin']         = 'zip woocommerce plugin';
		$metadata['plugin_version'] = $this->WC_Zipmoney_Payment_Gateway->version;

		return $metadata;
	}
}
