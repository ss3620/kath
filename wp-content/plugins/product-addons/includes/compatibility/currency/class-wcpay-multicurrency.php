<?php //phpcs:ignore
namespace PRAD\Includes\Compatibility\Currency;

defined( 'ABSPATH' ) || exit;

/**
 *  WCPay Multicurrency
 */
class WcpayMulticurrency {

	public function convert( $price ) {
		$multi_currency = null;

		if ( class_exists( 'WC_Payments' ) && method_exists( '\WC_Payments', 'get_gateway' ) ) {
			$gateway = \WC_Payments::get_gateway();

			if ( class_exists( '\WCPay\WC_Payments_Currency_Manager' ) ) {
				$currency_manager = new \WCPay\WC_Payments_Currency_Manager( $gateway );

				if ( method_exists( $currency_manager, 'get_multi_currency_instance' ) ) {
					$multi_currency = $currency_manager->get_multi_currency_instance();
				}
			}
		}

		// Convert price if instance exists
		if ( $multi_currency instanceof \WCPay\MultiCurrency\MultiCurrency && method_exists( $multi_currency, 'get_price' ) ) {
			$price = $multi_currency->get_price( $price, 'product' );
			return $price;
		}

		return $price;
	}

	public function revert( $price ) {
		$multi_currency  = null;
		$converted_price = $price;

		if ( class_exists( 'WC_Payments' ) && method_exists( '\WC_Payments', 'get_gateway' ) ) {
			$gateway = \WC_Payments::get_gateway();

			if ( class_exists( '\WCPay\WC_Payments_Currency_Manager' ) ) {
				$currency_manager = new \WCPay\WC_Payments_Currency_Manager( $gateway );

				if ( method_exists( $currency_manager, 'get_multi_currency_instance' ) ) {
					$multi_currency = $currency_manager->get_multi_currency_instance();
				}
			}
		}

		// Convert price if instance exists
		if ( $multi_currency instanceof \WCPay\MultiCurrency\MultiCurrency && method_exists( $multi_currency, 'get_price' ) ) {
			$currency = $multi_currency->get_selected_currency();
			$rate     = $currency->get_rate();
			if ( $currency->get_is_default() || $rate <= 0 ) {
				return $converted_price;
			}

			// Reverse conversion
			$base_price = (float) $converted_price / $rate;

			return (float) $base_price;
		}
		return $price;
	}
}
