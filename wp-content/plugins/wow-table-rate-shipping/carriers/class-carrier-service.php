<?php // phpcs:ignore

namespace WTRS\Carriers;

use WTRS\Carriers\Services\AustraliaPost;
use WTRS\Carriers\Services\CanadaPost;
use WTRS\Carriers\Services\Dhl;
use WTRS\Carriers\Services\Fedex;
use WTRS\Carriers\Services\Sendle;
use WTRS\Carriers\Services\Ups;
use WTRS\Carriers\Services\Usps;

defined( 'ABSPATH' ) || exit;

/**
 * Carrier Service Class
 */
final class CarrierService {

	/**
	 * Get available carrier services.
	 *
	 * @return AbstractCarrier[] List of available carrier services.
	 */
	public static function get_services() {
		return array(
			'fedex'      => Fedex::class,
			'usps'       => Usps::class,
			'auspost'    => AustraliaPost::class,
			'sendle'     => Sendle::class,
			'canadapost' => CanadaPost::class,
			'ups'        => Ups::class,
			'dhl'        => Dhl::class,
		);
	}

	/**
	 * Create carrier service instance.
	 *
	 * @param string $carrier_key Carrier key.
	 * @param array  $settings Non-sensitive settings.
	 * @return AbstractCarrier|null
	 */
	public static function create_service( $carrier_key, $settings = array() ) {
		$services = self::get_services();
		if ( isset( $services[ $carrier_key ] ) ) {
			return new $services[ $carrier_key ]( $settings );
		}
		return null;
	}

	/**
	 * Get values collection for a specific carrier (delegated to carrier class).
	 *
	 * @param string $carrier_key Carrier key.
	 * @param string $type        Value type requested (passed through to carrier).
	 * @return mixed
	 */
	public static function get_values( $carrier_key, $type ) {
		$service = self::get_services()[ $carrier_key ] ?? null;
		if ( $service ) {
			return $service::get_values( $type );
		}
		return array();
	}

	/**
	 * Return carrier configuration status array.
	 *
	 * @param string $carrier_key Carrier key.
	 * @return boolean
	 */
	public static function check_status( $carrier_key ) {
		$manager = new CarrierCredentialManager( $carrier_key );
		return $manager->has_credentials();
	}

	/**
	 * Validate & persist credentials for a carrier (wrapper around instance validate()).
	 *
	 * @param string $carrier_key Carrier identifier.
	 * @param array  $credentials Credential field => value.
	 * @return bool True on success, false if carrier unknown or validation failed.
	 */
	public static function validate( $carrier_key, array $credentials ) {
		$service = self::create_service( $carrier_key );
		if ( ! $service ) {
			return false;
		}
		return (bool) $service->validate( $credentials );
	}

	/**
	 * Get all stored secrets for a carrier (wrapper around instance all()).
	 *
	 * @param string $carrier_key Carrier identifier.
	 * @return array
	 */
	public static function get_secrets( $carrier_key ) {
		$manager = new CarrierCredentialManager( $carrier_key );
		return $manager->all();
	}
}
