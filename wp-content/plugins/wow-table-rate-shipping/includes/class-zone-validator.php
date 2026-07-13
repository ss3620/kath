<?php // phpcs:ignore

namespace WTRS\Includes;

use WTRS\Includes\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Zone Validation.
 */
class ZoneValidator {

	public const DELIMITER       = '.';
	public const DELIMITER_STATE = '#';

	/**
	 * Check if zone is valid.
	 *
	 * @param string $zone_id Zone ID.
	 * @return boolean
	 */
	public static function is_valid_zone( $zone_id ) {
		$zone = DB::get_instance()->get_custom_shipping_zone_by_id( $zone_id );

		if ( empty( $zone ) || empty( $zone['regions'] ) || ! is_array( $zone['regions'] ) ) {
			return false;
		}

		$customer_region    = self::get_customer_region();
		$customer_continent = $customer_region['continent'];
		$customer_country   = $customer_region['country'];
		$customer_state     = $customer_region['state'];

		foreach ( $zone['regions'] as $region ) {
			$splits = self::split_region( $region );

			$continent = $splits['continent'];
			$country   = $splits['country'];
			$state     = $splits['state'];

			// Priority 1: Check if country and state match.
			if ( ! empty( $country ) && $customer_country === $country ) {
				if ( ! empty( $state ) ) {
					if ( $customer_state === $state ) {
						return true;
					}
				} else {
					return true;
				}
			}

			// Priority 2: If the above fails, check if continent matches.
			if ( ! empty( $continent ) && empty( $country ) && empty( $state ) ) {
				if ( $customer_continent === $continent ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Get customer region with enhanced logic.
	 *
	 * @return array{
	 *   continent: string|null,
	 *   country: string|null,
	 *   state: string|null,
	 *   city: string|null,
	 *   postcode: string|null,
	 *   address1: string|null,
	 *   address2: string|null
	 * }
	 */
	public static function get_customer_region() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		static $cache = null;
		if ( null !== $cache ) {
			return $cache;
		}

		if ( ! function_exists( 'WC' ) || empty( WC()->customer ) ) {
			$cache = array(
				'continent' => null,
				'country'   => null,
				'state'     => null,
				'city'      => null,
				'postcode'  => null,
				'address1'  => null,
				'address2'  => null,
			);
			return $cache;
		}

		$customer = WC()->customer; // WC_Customer instance.

		// Primary attempt: shipping fields.
		$country  = $customer->get_shipping_country();
		$state    = $customer->get_shipping_state();
		$city     = $customer->get_shipping_city();
		$postcode = $customer->get_shipping_postcode();
		$address1 = method_exists( $customer, 'get_shipping_address_1' ) ? $customer->get_shipping_address_1() : ( method_exists( $customer, 'get_shipping_address' ) ? $customer->get_shipping_address() : '' );
		$address2 = method_exists( $customer, 'get_shipping_address_2' ) ? $customer->get_shipping_address_2() : '';

		// Fallback wholesale to billing if no shipping country set.
		if ( empty( $country ) ) {
			$country  = $customer->get_billing_country();
			$state    = $customer->get_billing_state();
			$city     = $customer->get_billing_city();
			$postcode = $customer->get_billing_postcode();
			$address1 = method_exists( $customer, 'get_billing_address_1' ) ? $customer->get_billing_address_1() : $address1;
			$address2 = method_exists( $customer, 'get_billing_address_2' ) ? $customer->get_billing_address_2() : $address2;
		}

		$country = $country ? strtoupper( $country ) : '';
		$state   = $state ? strtoupper( $state ) : '';

		$continent = $country ? WC()->countries->get_continent_code_for_country( $country ) : null;

		$cache = array(
			'continent' => ! empty( $continent ) ? $continent : null,
			'country'   => ! empty( $country ) ? $country : null,
			'state'     => ! empty( $state ) ? $state : null,
			'city'      => ! empty( $city ) ? $city : null,
			'postcode'  => ! empty( $postcode ) ? $postcode : null,
			'address1'  => ! empty( $address1 ) ? $address1 : null,
			'address2'  => ! empty( $address2 ) ? $address2 : null,
		);

		return $cache;
	}

	/**
	 * Split region into continent, country, state.
	 *
	 * @param string $region region.
	 * @return array
	 */
	private static function split_region( $region ) {
		$splits = explode( self::DELIMITER, $region );

		$continent = $splits[0] ?? null;
		$country   = $splits[1] ?? null;
		$state     = $splits[2] ?? null;

		if ( is_string( $state ) && strpos( $state, self::DELIMITER_STATE ) !== false ) {
			$state_parts = explode( self::DELIMITER_STATE, $state );
			$state       = $state_parts[1] ?? null;
		}

		return array(
			'continent' => $continent,
			'country'   => $country,
			'state'     => $state,
		);
	}

	/**
	 * Pull store origin from WooCommerce general settings.
	 *
	 * @return array [zip,country,state,city]
	 */
	public static function get_store_address() {

		$settings = DB::get_instance()->get_settings();

		$postcode     = get_option( 'woocommerce_store_postcode', '' );
		$country      = get_option( 'woocommerce_default_country', '' ); // May include CC:STATE.
		$parts        = explode( ':', $country );
		$country_code = strtoupper( $parts[0] ?? '' );
		$state_code   = strtoupper( $parts[1] ?? '' );
		$city         = get_option( 'woocommerce_store_city', '' );
		$address1     = get_option( 'woocommerce_store_address', '' );
		$address2     = get_option( 'woocommerce_store_address_2', '' );

		// Override with plugin settings if provided (non-empty strings).
		if ( ! empty( $settings['wtrsPostcode'] ) ) {
			$postcode = $settings['wtrsPostcode'];
		}
		if ( ! empty( $settings['wtrsCountry'] ) ) {
			$country_code = strtoupper( $settings['wtrsCountry'] );
		}
		if ( ! empty( $settings['wtrsState'] ) ) {
			$state_code = strtoupper( $settings['wtrsState'] );
		}
		if ( ! empty( $settings['wtrsCity'] ) ) {
			$city = $settings['wtrsCity'];
		}
		if ( isset( $settings['wtrsAddress1'] ) && '' !== $settings['wtrsAddress1'] ) {
			$address1 = $settings['wtrsAddress1'];
		}
		if ( isset( $settings['wtrsAddress2'] ) && '' !== $settings['wtrsAddress2'] ) {
			$address2 = $settings['wtrsAddress2'];
		}

		$continent = WC()->countries->get_continent_code_for_country( $country_code );

		return array(
			'continent' => $continent,
			'country'   => $country_code,
			'state'     => $state_code,
			'city'      => $city,
			'postcode'  => $postcode,
			'address1'  => $address1,
			'address2'  => $address2,
		);
	}
}
