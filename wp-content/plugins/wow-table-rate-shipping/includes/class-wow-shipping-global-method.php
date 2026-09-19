<?php // phpcs:ignore
/**
 * Global WowShipping method behavior.
 *
 * @package WTRS
 */

namespace WTRS\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Handles globally available WowShipping rules.
 */
class WowShippingGlobalMethod {

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function __construct() {
		add_filter( 'woocommerce_package_rates', array( $this, 'inject_global_shipping_rates' ), 20, 2 );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_handling_fee' ), 20 );
	}

	/**
	 * Inject global WowShipping rules into every package after WooCommerce resolves zone rates.
	 *
	 * @param array $rates Existing package rates.
	 * @param array $package Current package.
	 * @return array
	 */
	public function inject_global_shipping_rates( $rates, $package ) {
		unset( $package );

		$global_rules = DB::get_instance()->get_global_shipping_rules();

		foreach ( $global_rules as $rule ) {
			$payload = WowShippingMethod::build_rate_payload( $rule );

			if ( empty( $payload ) ) {
				continue;
			}

			$rate_data = $payload['rate'];

			if ( isset( $rates[ $rate_data['id'] ] ) ) {
				continue;
			}

			$rate = new \WC_Shipping_Rate(
				$rate_data['id'],
				$rate_data['label'],
				$rate_data['cost'],
				false === $rate_data['taxes'] ? array() : $rate_data['taxes'],
				'wtrs_wc_method',
				0,
				$payload['tax_status'],
				$payload['method']['description'] ?? '',
				$payload['method']['delivery_time'] ?? ''
			);

			foreach ( $rate_data['meta_data'] as $key => $value ) {
				$rate->add_meta_data( $key, $value );
			}

			$rates[ $rate->get_id() ] = $rate;
		}

		return $rates;
	}

	/**
	 * Apply handling fees for selected WowShipping rates.
	 *
	 * @param \WC_Cart $cart Cart instance.
	 * @return void
	 */
	public function apply_handling_fee( $cart ) {
		if ( ! $cart || ! WC()->session ) {
			return;
		}

		$chosen_methods = (array) WC()->session->get( 'chosen_shipping_methods', array() );

		if ( empty( $chosen_methods ) ) {
			return;
		}

		foreach ( DB::get_instance()->get_applicable_shipping_rules() as $rule ) {
			if ( empty( $rule['handlingFee'] ) ) {
				continue;
			}

			$payload = WowShippingMethod::build_rate_payload( $rule, (int) ( $rule['instanceId'] ?? 0 ) );

			if ( empty( $payload ) ) {
				continue;
			}

			if ( ! in_array( $payload['rate']['id'], $chosen_methods, true ) ) {
				continue;
			}

			HandlingFee::add_handling_fee_to_cart( $rule['handlingFee'], $cart );
		}
	}
}
