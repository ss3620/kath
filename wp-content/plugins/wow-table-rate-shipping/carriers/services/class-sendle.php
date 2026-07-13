<?php // phpcs:ignore
namespace WTRS\Carriers\Services; 

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WTRS\Carriers\AbstractCarrier;
use WTRS\Carriers\DTO\ShipmentRequest;
use WTRS\Carriers\DTO\Rate;

/**
 * Sendle Carrier Class
 *
 * @link https://developers.sendle.com/reference/getproducts
 */
final class Sendle extends AbstractCarrier {

	/** Base live rate endpoint */
	const LIVE_RATE_URL = 'https://api.sendle.com/api';
	/** Base sandbox rate endpoint */
	const TEST_RATE_URL = 'https://sandbox.sendle.com/api';

	private const AU_DOMESTIC_MAX_WEIGHT_KG  = 25;
	private const CA_DOMESTIC_MAX_WEIGHT_KG  = 30;
	private const US_DOMESTIC_MAX_WEIGHT_KG  = 22.67;
	private const DEFAULT_INTL_MAX_WEIGHT_KG = 20;

	/**
	 * {@inheritdoc}
	 */
	public static function get_key(): string {
		return 'sendle';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_label(): string {
		return 'Sendle';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_supported_packers(): array {
		return array( 'single_package', 'per_item', 'box', 'weight' );
	}

	/**
	 * Get API endpoint based on environment.
	 *
	 * @param string $path API path.
	 * @return string
	 */
	private function get_api_endpoint( $path ) {
		$url = $this->is_live() ? self::LIVE_RATE_URL : self::TEST_RATE_URL;
		return rtrim( $url, '/' ) . '/' . ltrim( $path, '/' );
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_values( $type, $context = 'view' ) {
		if ( 'product' === $type ) {
			return array(
				array(
					'value' => 'SAVER-DROPOFF',
					'label' => 'Sendle Saver Drop off',
				),
				array(
					'value' => 'SAVER-PICKUP',
					'label' => 'Sendle Saver Pickup',
				),
				array(
					'value' => 'STANDARD-DROPOFF',
					'label' => 'Sendle Preferred Drop off',
				),
				array(
					'value' => 'STANDARD-PICKUP',
					'label' => 'Sendle Preferred Pickup',
				),
				array(
					'value' => 'THREE-DAY-PICKUP',
					'label' => 'Sendle 3-Day Guaranteed Pickup',
				),
				array(
					'value' => 'THREE-DAY-DROPOFF',
					'label' => 'Sendle 3-Day Guaranteed Drop off',
				),
				array(
					'value' => 'TWO-DAY-PICKUP',
					'label' => 'Sendle 2-Day Guaranteed Pickup',
				),
				array(
					'value' => 'TWO-DAY-DROPOFF',
					'label' => 'Sendle 2-Day Guaranteed Drop off',
				),
				array(
					'value' => 'GROUND-ADVANTAGE-PICKUP',
					'label' => 'Ground Advantage Plus Pickup',
				),
				array(
					'value' => 'GROUND-ADVANTAGE-DROPOFF',
					'label' => 'Ground Advantage Plus Drop off',
				),
				array(
					'value' => 'PRIORITY-PICKUP',
					'label' => 'Priority Mail Plus Pickup',
				),
				array(
					'value' => 'PRIORITY-DROPOFF',
					'label' => 'Priority Mail Plus Drop off',
				),
				array(
					'value' => 'PRIORITY-EXPRESS-PICKUP',
					'label' => 'Priority Mail Express Plus Pickup',
				),
				array(
					'value' => 'PRIORITY-EXPRESS-DROPOFF',
					'label' => 'Priority Mail Express Plus Drop off',
				),
			);
		}

		if ( 'supported_packers' === $type ) {
			return self::add_packer_labels( self::get_supported_packers() );
		}

		return array();
	}

	/**
	 * Parse settings to apply default values and validation.
	 *
	 * @param array $values values.
	 * @return array
	 */
	protected static function parse_settings( $values ) {

		$parsed = array();

		$parsed['mode']          = in_array( $values['mode'] ?? '', array( 'sandbox', 'live' ), true ) ? $values['mode'] : 'sandbox';
		$parsed['packagingType'] = sanitize_text_field( $values['packagingType'] ?? '' );
		$parsed['productCode']   = sanitize_text_field( $values['productCode'] ?? '' );

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
			if ( $weight > self::AU_DOMESTIC_MAX_WEIGHT_KG ) {
				// translators: %d is weight in kg.
				$this->error( 'max_weight_exceeded', sprintf( __( 'Sendle maximum weight for domestic shipments in Australia is %d kg.', 'table-rate-shipping-pro' ), self::AU_DOMESTIC_MAX_WEIGHT_KG ) );

				return false;
			}
		}

		if ( 'CA' === $sender_country && $sender_country === $shipping_country ) {
			if ( $weight > self::CA_DOMESTIC_MAX_WEIGHT_KG ) {
				// translators: %d is weight in kg.
				$this->error( 'max_weight_exceeded', sprintf( __( 'Sendle maximum weight for domestic shipments in Canada is %d kg.', 'table-rate-shipping-pro' ), self::CA_DOMESTIC_MAX_WEIGHT_KG ) );
				return false;
			}
		}

		if ( 'US' === $sender_country && $sender_country === $shipping_country ) {
			if ( $weight > self::US_DOMESTIC_MAX_WEIGHT_KG ) {
				// translators: %d is weight in kg.
				$this->error( 'max_weight_exceeded', sprintf( __( 'Sendle maximum weight for domestic shipments in the United States is %d kg.', 'table-rate-shipping-pro' ), self::US_DOMESTIC_MAX_WEIGHT_KG ) );
				return false;
			}
		}

		if ( $sender_country !== $shipping_country ) {
			if ( $weight > self::DEFAULT_INTL_MAX_WEIGHT_KG ) {
				// translators: %d is weight in kg.
				$this->error( 'max_weight_exceeded', sprintf( __( 'Sendle maximum weight for international shipments is %d kg.', 'table-rate-shipping-pro' ), self::DEFAULT_INTL_MAX_WEIGHT_KG ) );
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
		$this->log( 'fetch_rates_started', 'Starting Sendle rate request' );

		$total_weight = array_sum(
			array_map(
				function ( $package ) {
					return $package->total_weight;
				},
				$request->packages
			)
		);

		if ( $this->validate_weight( $total_weight, $request->origin_location['country'], $request->destination_location['country'] ) === false ) {
			$this->error( 'weight_validation_failed', 'Package weight validation failed' );
			return false;
		}

		$payloads = $this->build_rate_request_payload( $request );

		$this->log( 'rate_payloads', 'Built Sendle rate payloads', array( 'payload_count' => count( $payloads ) ) );

		$rates          = array();
		$api_error      = null;
		$no_rate_count  = 0;
		$response_count = 0;

		foreach ( $payloads as $payload ) {
			$response = $this->remote_get_json( $this->get_api_endpoint( '/products' ), $payload );
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
			'Received Sendle responses',
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

		$this->log( 'rates_fetched', 'Sendle rates fetched successfully', array( 'final_count' => count( $rates ) ) );

		return apply_filters( 'wtrs_carrier_filter_normalized_rates', $rates, $this->get_key(), $request );
	}

	/**
	 * Determine environment.
	 */
	protected function is_live(): bool {
		return ( $this->settings['mode'] ?? 'sandbox' ) === 'live';
	}


	/**
	 * Build minimal FedEx rate request body from shipment request.
	 *
	 * @param ShipmentRequest $request Request object.
	 * @return array
	 */
	protected function build_rate_request_payload( ShipmentRequest $request ): array {
		$packages      = $request->packages;
		$store_address = $request->origin_location;
		$destination   = $request->destination_location;

		$payloads = array();

		foreach ( $packages as $package ) {
			$payload    = array(
				'sender_address_line1'   => $store_address['address1'],
				'sender_address_line2'   => $store_address['address2'],
				'sender_suburb'          => $store_address['city'],
				'sender_postcode'        => $store_address['postcode'],
				'sender_country'         => $store_address['country'],
				'receiver_address_line1' => $destination['address1'],
				'receiver_address_line2' => $destination['address2'],
				'receiver_suburb'        => $destination['city'],
				'receiver_postcode'      => $destination['postcode'],
				'receiver_country'       => $destination['country'],
				'weight_value'           => $package->total_weight,
				'weight_units'           => 'kg',
				'length_value'           => $package->packed_dimensions->length,
				'width_value'            => $package->packed_dimensions->width,
				'height_value'           => $package->packed_dimensions->height,
				'dimension_units'        => 'cm',
			);
			$payloads[] = array_filter( $payload );
		}

		return $payloads;
	}


	/**
	 * Perform a remote GET returning decoded JSON or WP_Error.
	 *
	 * @param string $url Endpoint.
	 * @param array  $params Query parameters.
	 * @return array|\WP_Error
	 */
	protected function remote_get_json( string $url, array $params ) {
		$url = add_query_arg( $params, $url );

		$sendle_id = $this->get_secret( 'sendle_id' );
		$api_key   = $this->get_secret( 'api_key' );

		$headers = array(
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'Authorization' => 'Basic ' . base64_encode( $sendle_id . ':' . $api_key ),
			'Accept'        => 'application/json',
			'User-Agent'    => 'WowShipping/' . WTRS_VER,
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
				'Rate request failed',
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
	 * Parse Sendle API response array into array of Rate.
	 *
	 * @param array           $data Raw decoded response.
	 * @param ShipmentRequest $request Original shipment request.
	 * @return Rate|null
	 */
	protected function parse_rate_response( $data, ShipmentRequest $request ) {
		if ( isset( $data['error'] ) ) {
			$this->error( 'api_error', $data['error_description'] ?? 'Unknown API error' );
			return null;
		}

		if ( ! is_array( $data ) ) {
			$this->error( 'invalid_response', 'API response was not in the expected format.' );
			return null;
		}

		foreach ( $data as $product ) {
			if ( ! isset( $product['quote']['gross']['amount'], $product['product']['name'], $product['product']['code'] ) ) {
				continue;
			}

			if ( $this->get_setting( 'productCode' ) !== $product['product']['code'] ) {
				continue;
			}

			$cost          = (float) $product['quote']['gross']['amount'];
			$currency_code = $product['quote']['gross']['currency'];
			$cost          = $this->convert_currency( $cost, $currency_code, $request->currency );

			if ( false === $cost ) {
				$this->error(
					'currency_conversion_failed',
					'Failed to convert Sendle currency',
					array(
						'from' => $currency_code,
						'to'   => $request->currency,
					)
				);
				continue;
			}

			$delivery_time = null;
			$transit_days  = null;

			if ( isset( $product['eta']['days_range'] ) && is_array( $product['eta']['days_range'] ) ) {
				$min_days = $product['eta']['days_range'][0] ?? null;
				$max_days = $product['eta']['days_range'][1] ?? null;
				if ( $min_days && $max_days ) {
					if ( $min_days === $max_days ) {
						// translators: %d is number of days.
						$delivery_time = sprintf( _n( '%d business day', '%d business days', $max_days, 'table-rate-shipping-pro' ), $max_days );
					} else {
						// translators: %d is number of days.
						$delivery_time = sprintf( __( '%1$d-%2$d business days', 'table-rate-shipping-pro' ), $min_days, $max_days );
					}
					$transit_days = (int) $max_days;
				}
			}

			return new Rate(
				$this->get_key(),
				$product['product']['code'],
				$product['product']['name'],
				$cost,
				$request->currency,
				$delivery_time,
				$transit_days
			);
		}

		return null;
	}

	/**
	 * Validate Sendle credentials & settings, then persist them.
	 * Expected credentials: sendle_id, api_key.
	 *
	 * @param array $credentials Credential inputs.
	 * @return bool True if stored, false if required fields missing.
	 */
	public function validate( array $credentials ) {
		$required = array( 'sendle_id', 'api_key' );
		foreach ( $required as $field ) {
			if ( empty( $credentials[ $field ] ) ) {
				return false;
			}
		}

		$sendle_id = $credentials['sendle_id'];
		$api_key   = $credentials['api_key'];

		$headers = array(
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'Authorization' => 'Basic ' . base64_encode( $sendle_id . ':' . $api_key ),
			'Accept'        => 'application/json',
			'User-Agent'    => 'WowShipping/' . WTRS_VER,
		);

		$args = array(
			'headers'   => $headers,
			'timeout'   => 40,
			'sslverify' => false,
		);

		$response = wp_remote_get( $this->get_api_endpoint( '/ping' ), $args );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );

		$success = 200 === $code;

		if ( $success ) {
			$this->credential_manager->bulk_set(
				array(
					'sendle_id' => $sendle_id,
					'api_key'   => $api_key,
				)
			);
		}

		return $success;
	}
}
