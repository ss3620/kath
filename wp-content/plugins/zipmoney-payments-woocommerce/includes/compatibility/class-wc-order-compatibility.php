<?php
/**
 *
 */
defined( 'ABSPATH' ) or exit;

if ( ! class_exists( 'WC_Zipmoney_Order_Compatibility' ) ) :

	/**
	 * WooCommerce order compatibility class.
	 *
	 * @since 4.6.0
	 */
	class WC_Zipmoney_Order_Compatibility extends WC_Zipmoney_Data_Compatibility {

		/**
		 * Backports WC_Order::get_id() method to pre-2.6.0
		 *
		 * @since 4.2.0
		 * @param WC_Abstract_Order $order order object
		 *
		 * @return string|int order ID
		 */
		public static function get_id( $order ) {

			if ( method_exists( $order, 'get_id' ) ) {

				return $order->get_id();

			} else {

				return isset( $order->id ) ? $order->id : false;
			}
		}
		/**
		 * Determines if an order has an available shipping address.
		 *
		 * WooCommerce 3.0+ no longer fills the shipping address with the billing if
		 * a shipping address was never set by the customer at checkout, as is the
		 * case with virtual orders. This method is helpful for gateways that may
		 * reject such transactions with blank shipping information.
		 *
		 * TODO: Remove when WC 3.0.4 can be required {CW 2017-04-17}
		 *
		 * @since 4.6.1
		 *
		 * @param \WC_Order $order order object
		 *
		 * @return bool
		 */
		public static function has_shipping_address( WC_Order $order ) {

			return self::get_prop( $order, 'shipping_address_1' ) || self::get_prop( $order, 'shipping_address_2' );
		}


	}
endif; // Class exists check
