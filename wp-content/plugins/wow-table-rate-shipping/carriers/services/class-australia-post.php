<?php // phpcs:ignore
namespace WTRS\Carriers\Services; 

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WTRS\Carriers\AbstractCarrier;
use WTRS\Carriers\DTO\ShipmentRequest;
use WTRS\Carriers\DTO\Rate;

/**
 * Australia Post Carrier Class
 *
 * @link https://developers.auspost.com.au/apis/pac/reference
 */
final class AustraliaPost extends AbstractCarrier {

	private const DOMESTIC_RATE_URL      = 'https://digitalapi.auspost.com.au/postage/parcel/domestic/calculate.json';
	private const INTL_RATE_URL          = 'https://digitalapi.auspost.com.au/postage/parcel/international/calculate.json';
	private const DOMESTIC_MAX_WEIGHT_KG = 22;
	private const INTL_MAX_WEIGHT_KG     = 20;

	/**
	 * {@inheritdoc}
	 */
	public static function get_key(): string {
		return 'auspost';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_label(): string {
		return 'Australia Post';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_supported_packers(): array {
		return array( 'single_package', 'per_item', 'box', 'weight' );
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_values( $type ) {
		if ( 'serviceCode' === $type ) {
			return array(
				// Domestic.
				array(
					'value' => 'AUS_PARCEL_REGULAR#AUS_SERVICE_OPTION_STANDARD',
					'label' => 'Domestic - Parcel Post (Standard Service)',
				),
				array(
					'value' => 'AUS_PARCEL_REGULAR#AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY',
					'label' => 'Domestic - Parcel Post (Signature on Delivery)',
				),
				array(
					'value' => 'AUS_PARCEL_EXPRESS#AUS_SERVICE_OPTION_STANDARD',
					'label' => 'Domestic - Express Post (Standard Service)',
				),
				array(
					'value' => 'AUS_PARCEL_EXPRESS#AUS_SERVICE_OPTION_SIGNATURE_ON_DELIVERY',
					'label' => 'Domestic - Express Post (Signature on Delivery)',
				),

				// International.
				array(
					'value' => 'INT_PARCEL_COR_OWN_PACKAGING#INT_TRACKING',
					'label' => 'International - Courier (Tracking)',
				),
				array(
					'value' => 'INT_PARCEL_COR_OWN_PACKAGING#INT_SMS_TRACK_ADVICE',
					'label' => 'International - Courier (SMS track advice)',
				),
				array(
					'value' => 'INT_PARCEL_COR_OWN_PACKAGING#INT_EXTRA_COVER',
					'label' => 'International - Courier (Extra Cover)',
				),
				array(
					'value' => 'INT_PARCEL_EXP_OWN_PACKAGING#INT_TRACKING',
					'label' => 'International - Express (Tracking)',
				),
				array(
					'value' => 'INT_PARCEL_EXP_OWN_PACKAGING#INT_SIGNATURE_ON_DELIVERY',
					'label' => 'International - Express (Signature on delivery)',
				),
				array(
					'value' => 'INT_PARCEL_EXP_OWN_PACKAGING#INT_SMS_TRACK_ADVICE',
					'label' => 'International - Express (SMS track advice)',
				),
				array(
					'value' => 'INT_PARCEL_EXP_OWN_PACKAGING#INT_EXTRA_COVER',
					'label' => 'International - Express (Extra Cover)',
				),
				array(
					'value' => 'INT_PARCEL_STD_OWN_PACKAGING#INT_TRACKING',
					'label' => 'International - Standard (Tracking)',
				),
				array(
					'value' => 'INT_PARCEL_STD_OWN_PACKAGING#INT_SIGNATURE_ON_DELIVERY',
					'label' => 'International - Standard (Signature on delivery)',
				),
				array(
					'value' => 'INT_PARCEL_STD_OWN_PACKAGING#INT_SMS_TRACK_ADVICE',
					'label' => 'International - Standard (SMS track advice)',
				),
				array(
					'value' => 'INT_PARCEL_STD_OWN_PACKAGING#INT_EXTRA_COVER',
					'label' => 'International - Standard (Extra Cover)',
				),
			);
		}

		if ( 'supported_packers' === $type ) {
			return self::add_packer_labels( self::get_supported_packers() );
		}

		return array();
	}

	/**
	 * Get Rate API endpoint based on shipment destination.
	 *
	 * @param ShipmentRequest $request Request object.
	 * @return string
	 */
	private function get_api_endpoint( ShipmentRequest $request ) {
		return 'AU' === $request->destination_location['country'] ? self::DOMESTIC_RATE_URL : self::INTL_RATE_URL;
	}

	/**
	 * Parse settings to apply default values and validation.
	 *
	 * @param array $values values.
	 * @return array
	 */
	protected static function parse_settings( $values ) {

		$parsed = array();

		$parts = explode( '#', $values['serviceCode'] ?? '' );

		$parsed['serviceCode']   = sanitize_text_field( $parts[0] ?? '' );
		$parsed['optionCode']    = sanitize_text_field( $parts[1] ?? '' );
		$parsed['subOptionCode'] = sanitize_text_field( $parts[2] ?? '' );
		$parsed['cover']         = floatval( $values['cover'] ?? 0 );

		return array_merge( parent::parse_settings( $values ), $parsed );
	}

	/**
	 * Validate weight limits.
	 *
	 * @param float  $weight Weight in KG.
	 * @param string $sender_country Sender country code.
	 * @param string $shipping_country Shipping country code.
	 */
	private function validate_weight( $weight, $sender_country, $shipping_country ) {
		if ( 'AU' === $sender_country && $sender_country === $shipping_country ) {
			if ( $weight > self::DOMESTIC_MAX_WEIGHT_KG ) {
				// translators: %d is weight in kg.
				$this->error( 'max_weight_exceeded', sprintf( __( 'Australia Post maximum weight for domestic shipments in Australia is %d kg.', 'table-rate-shipping-pro' ), self::DOMESTIC_MAX_WEIGHT_KG ) );

				return false;
			}
		}

		if ( $sender_country !== $shipping_country ) {
			if ( $weight > self::INTL_MAX_WEIGHT_KG ) {
				// translators: %d is weight in kg.
				$this->error( 'max_weight_exceeded', sprintf( __( 'Australia Post maximum weight for international shipments is %d kg.', 'table-rate-shipping-pro' ), self::INTL_MAX_WEIGHT_KG ) );
				return false;
			}
		}

		return true;
	}

	/**
	 * Fetch normalized rates.
	 *
	 * @param ShipmentRequest $request Shipment abstraction.
	 * @return array<Rate>|false
	 */
	public function fetch_rates( ShipmentRequest $request ) {
		$this->log( 'fetch_rates_started', 'Starting Australia Post rate request' );

		$total_weight = array_sum(
			array_map(
				function ( $package ) {
					return $package->total_weight;
				},
				$request->packages
			)
		);

		if ( ! $this->validate_weight( $total_weight, $request->origin_location['country'], $request->destination_location['country'] ) ) {
			return false;
		}

		$payloads       = $this->build_rate_request_payload( $request );
		$rates          = array();
		$api_error      = null;
		$no_rate_count  = 0;
		$response_count = 0;

		$this->log( 'rate_payloads', $payloads );

		foreach ( $payloads as $payload ) {
			$response = $this->remote_get_json( $this->get_api_endpoint( $request ), $payload );
			++$response_count;

			if ( is_wp_error( $response ) ) {
				$api_error = $response;
				break;
			}

			$rate = $this->parse_rate_response( $response, $request );

			if ( $rate ) {
				$rates[] = $rate;
			} else {
				++$no_rate_count;
			}
		}

		if ( $api_error instanceof \WP_Error ) {
			$this->error( 'api_error', $api_error );
			return false;
		}

		$this->log(
			'api_response_summary',
			'Received Australia Post responses',
			array(
				'response_count' => $response_count,
				'no_rate_count'  => $no_rate_count,
			)
		);

		$this->log( 'normalized_rates', 'Normalized rates', array( 'rate_count' => count( $rates ) ) );

		if ( count( $rates ) > 1 ) {
			$this->log( 'combining_rates', 'Combining multiple package rates' );
			$rates = array( $this->combine_rates( $rates ) );
		}

		$this->log( 'rates_fetched', 'Australia Post rates fetched successfully', array( 'final_count' => count( $rates ) ) );

		return apply_filters( 'wtrs_carrier_filter_normalized_rates', $rates, $this->get_key(), $request );
	}


	/**
	 * Build minimal FedEx rate request body from shipment request.
	 *
	 * @param ShipmentRequest $request Request object.
	 * @return array
	 */
	protected function build_rate_request_payload( ShipmentRequest $request ): array {
		$packages = $request->packages;

		$store_address = $request->origin_location;
		$destination   = $request->destination_location;

		$is_intl = 'AU' !== $destination['country'];

		$payloads = array();

		foreach ( $packages as $package ) {
			$payload = array(
				'length'       => $package->packed_dimensions->length,
				'width'        => $package->packed_dimensions->width,
				'height'       => $package->packed_dimensions->height,
				'weight'       => $package->total_weight,
				'service_code' => $this->get_setting( 'serviceCode' ),
				'option_code'  => $this->get_setting( 'optionCode' ),
				'extra_cover'  => $this->get_setting( 'cover' ),
			);

			if ( $is_intl ) {
				$payload['country_code'] = $destination['country'];
			} else {
				$payload['from_postcode'] = $store_address['postcode'];
				$payload['to_postcode']   = $destination['postcode'];
			}

			$payloads[] = array_filter( $payload );
		}

		return $payloads;
	}


	/**
	 * Perform a remote GET returning decoded JSON or WP_Error.
	 *
	 * @param string $url Endpoint.
	 * @param array  $params Query parameters.
	 * @param mixed  $auth_key Authentication key.
	 * @return array|\WP_Error
	 */
	protected function remote_get_json( string $url, array $params, $auth_key = null ) {
		$url = add_query_arg( $params, $url );

		$headers = array(
			'Accept'     => 'application/json',
			'AUTH-KEY'   => ! empty( $auth_key ) ? $auth_key : $this->get_secret( 'auth_key' ),
			'User-Agent' => 'WowShipping/' . WTRS_VER,
		);

		$args = array(
			'headers'   => $headers,
			'timeout'   => 40,
			'sslverify' => false,
		);

		$response = wp_remote_get( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->error( 'remote_request_failed', $response );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$error = new \WP_Error(
				'http_error',
				'rate request failed',
				array(
					'status' => $code,
					'body'   => $body,
				)
			);
			$this->error( 'http_error', $error );
			return $error;
		}

		return $body;
	}

	/**
	 * Parse Auspost API response array into array of Rate.
	 *
	 * @param array           $data Raw decoded response.
	 * @param ShipmentRequest $request Original shipment request.
	 * @return Rate|null
	 */
	protected function parse_rate_response( $data, ShipmentRequest $request ) {
		if ( isset( $data['error'] ) ) {
			$this->error( 'api_error', 'Australia Post API returned error', $data );
			return null;
		}

		if ( ! is_array( $data ) || ! isset( $data['postage_result']['total_cost'] ) ) {
			$this->error( 'invalid_response', 'API response was not in the expected format.', array( 'response' => $data ) );
			return null;
		}

		$cost = (float) $data['postage_result']['total_cost'];
		$cost = $this->convert_currency( $cost, 'AUD', $request->currency );

		if ( false === $cost ) {
			$this->error( 'currency_conversion_failed', 'Failed to convert AUD to store currency' );
			return null;
		}

		$delivery_time = $data['postage_result']['delivery_time'] ?? '';

		return new Rate(
			$this->get_key(),
			$this->get_setting( 'serviceCode' ),
			sanitize_text_field( $data['postage_result']['service'] ),
			$cost,
			$request->currency,
			$delivery_time,
			null
		);
	}

	/**
	 * Validate credentials & settings, then persist them.
	 *
	 * @param array $credentials Credential inputs.
	 * @return bool True if stored, false if required fields missing.
	 */
	public function validate( array $credentials ) {
		$this->log( 'validation_started', 'Starting Australia Post credentials validation' );

		$required = array( 'auth_key' );

		foreach ( $required as $field ) {
			if ( empty( $credentials[ $field ] ) ) {
				$this->error( 'validation_failed', 'Missing required credential field', array( 'field' => $field ) );
				return false;
			}
		}

		$auth_key = $credentials['auth_key'];

		$params = array(
			'from_postcode' => '2000',
			'to_postcode'   => '2000',
			'length'        => '2',
			'width'         => '2',
			'height'        => '2',
			'weight'        => '2',
			'service_code'  => 'AUS_PARCEL_REGULAR',
		);

		$this->log( 'validation_testing', 'Testing credentials with sample request' );
		$response = $this->remote_get_json( self::DOMESTIC_RATE_URL, $params, $auth_key );

		if ( is_wp_error( $response ) ) {
			$this->error( 'validation_request_failed', $response );
			return false;
		}

		if ( isset( $response['error'] ) ) {
			$this->error( 'validation_api_error', 'API returned error', $response );
			return false;
		}

		if ( ! isset( $response['postage_result']['total_cost'] ) ) {
			$this->error( 'validation_invalid_response', 'Response missing expected data' );
			return false;
		}

		$this->log( 'validation_success', 'Credentials validated successfully' );
		$this->credential_manager->bulk_set(
			array(
				'auth_key' => $auth_key,
			)
		);

		return true;
	}
}
