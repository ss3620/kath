<?php // phpcs:ignore
namespace WTRS\Carriers\Services; 

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WTRS\Carriers\AbstractCarrier;
use WTRS\Carriers\DTO\ShipmentRequest;
use WTRS\Carriers\DTO\Rate;

/**
 * Canada Post Carrier Class
 *
 * @link https://developers.sendle.com/reference/getproducts
 */
final class CanadaPost extends AbstractCarrier {

	/** Base live rate endpoint */
	const LIVE_RATE_URL = 'https://soa-gw.canadapost.ca/rs/ship/price';
	/** Base sandbox rate endpoint */
	const TEST_RATE_URL = 'https://ct.soa-gw.canadapost.ca/rs/ship/price';

	const SANDBOX_CUSTOMER_NUMBER = '2004381';
	const SANDBOX_CONTRACT_ID     = '42708517';
	const SANDBOX_USERNAME        = '6e93d53968881714';
	const SANDBOX_PASSWORD        = '0bfa9fcb9853d1f51ee57a';

	/**
	 * {@inheritdoc}
	 */
	public static function get_key(): string {
		return 'canadapost';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_label(): string {
		return 'Canada Post';
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
	 * @return string
	 */
	private function get_api_endpoint() {
		return $this->is_live() ? self::LIVE_RATE_URL : self::TEST_RATE_URL;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function get_values( $type, $context = 'view' ) {

		if ( 'services' === $type ) {
			return array(
				array(
					'value' => 'DOM.RP',
					'label' => 'Regular Parcel',
				),
				array(
					'value' => 'DOM.EP',
					'label' => 'Expedited Parcel',
				),
				array(
					'value' => 'DOM.XP',
					'label' => 'Xpresspost',
				),
				array(
					'value' => 'DOM.XP.CERT',
					'label' => 'Xpresspost Certified',
				),
				array(
					'value' => 'DOM.PC',
					'label' => 'Priority',
				),
				array(
					'value' => 'DOM.LIB',
					'label' => 'Library Materials',
				),
				array(
					'value' => 'USA.EP',
					'label' => 'Expedited Parcel USA',
				),
				array(
					'value' => 'USA.SP.AIR',
					'label' => 'Small Packet USA Air',
				),
				array(
					'value' => 'USA.TP',
					'label' => 'Tracked Packet - USA',
				),
				array(
					'value' => 'USA.TP.LVM',
					'label' => 'Tracked Packet - USA (LVM) (large volume mailers)',
				),
				array(
					'value' => 'USA.XP',
					'label' => 'Xpresspost USA',
				),
				array(
					'value' => 'INT.XP',
					'label' => 'Xpresspost International',
				),
				array(
					'value' => 'INT.IP.AIR',
					'label' => 'International Parcel Air',
				),
				array(
					'value' => 'INT.IP.SURF',
					'label' => 'International Parcel Surface',
				),
				array(
					'value' => 'INT.SP.AIR',
					'label' => 'Small Packet International Air',
				),
				array(
					'value' => 'INT.SP.SURF',
					'label' => 'Small Packet International Surface',
				),
				array(
					'value' => 'INT.TP',
					'label' => 'Tracked Packet - International',
				),
			);
		}

		if ( 'options' === $type ) {
			return array(
				array(
					'value' => 'SO',
					'label' => 'Signature',
				),
				array(
					'value' => 'COV',
					'label' => 'Coverage',
				),
				array(
					'value' => 'COD',
					'label' => 'COD',
				),
				array(
					'value' => 'PA18',
					'label' => 'Proof of Age Required - 18',
				),
				array(
					'value' => 'PA19',
					'label' => 'Proof of Age Required - 19',
				),
				array(
					'value' => 'HFP',
					'label' => 'Card for pickup',
				),
				array(
					'value' => 'DNS',
					'label' => 'Do not safe drop',
				),
				array(
					'value' => 'LAD',
					'label' => 'Leave at door - do not card',
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

		$parsed['mode']    = in_array( $values['mode'] ?? '', array( 'development', 'production' ), true ) ? $values['mode'] : 'production';
		$parsed['options'] = array();

		if ( ! empty( $values['options'] ) && is_array( $values['options'] ) ) {
			$parsed['options'] = array_map(
				function ( $item ) {
					$parts  = explode( '|#|', $item );
					$amount = ! empty( $parts[1] ) && is_numeric( $parts[1] ) ? floatval( $parts[1] ) : null;
					return array(
						'code'   => sanitize_text_field( $parts[0] ?? '' ),
						'amount' => $amount,
					);
				},
				$values['options']
			);
		}

		$parsed['unpackaged']  = isset( $values['unpackaged'] ) && (bool) $values['unpackaged'];
		$parsed['mailingTube'] = isset( $values['mailingTube'] ) && (bool) $values['mailingTube'];
		$parsed['service']     = sanitize_text_field( $values['service'] ?? '' );

		return array_merge( parent::parse_settings( $values ), $parsed );
	}

	/**
	 * Fetch normalized rates.
	 *
	 * @param ShipmentRequest $request Shipment abstraction.
	 * @return array<Rate>|false
	 */
	public function fetch_rates( ShipmentRequest $request ) {
		$this->log( 'fetch_rates_started', 'Starting Canada Post rate request', array( 'mode' => $this->get_setting( 'mode' ) ) );

		$payloads = $this->build_rate_request_payload( $request );

		$this->log( 'rate_payloads', 'Built rate request payloads', array( 'payload_count' => count( $payloads ) ) );

		$rates          = array();
		$api_error      = null;
		$no_rate_count  = 0;
		$response_count = 0;

		foreach ( $payloads as $payload ) {
			$response = $this->remote_get_json( $this->get_api_endpoint(), $payload );
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
			'Received Canada Post responses',
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

		$this->log( 'rates_fetched', 'Canada Post rates fetched successfully', array( 'final_count' => count( $rates ) ) );

		return apply_filters( 'wtrs_carrier_filter_normalized_rates', $rates, $this->get_key(), $request );
	}

	/**
	 * Determine environment.
	 */
	protected function is_live(): bool {
		return ( $this->settings['mode'] ?? 'production' ) === 'production';
	}

	/**
	 * Build minimal FedEx rate request body from shipment request.
	 *
	 * @param ShipmentRequest $request Request object.
	 * @see https://www.canadapost-postescanada.ca/info/mc/business/productsservices/developers/services/rating/getrates/default.jsf
	 * @return array
	 */
	protected function build_rate_request_payload( ShipmentRequest $request ): array {
		$packages      = $request->packages;
		$store_address = $request->origin_location;
		$destination   = $request->destination_location;

		$payloads = array();

		foreach ( $packages as $package ) {

			$xml = '<mailing-scenario xmlns="http://www.canadapost.ca/ws/ship/rate-v4">';

			// Customer Number.
			$customer_number = $this->get_secret( 'customer_number' );
			$contract_id     = $this->get_secret( 'contract_id' );

			if ( 'development' === $this->get_setting( 'mode' ) ) {
				if ( empty( $customer_number ) ) {
					$customer_number = self::SANDBOX_CUSTOMER_NUMBER;
				}
				if ( empty( $contract_id ) ) {
					$contract_id = self::SANDBOX_CONTRACT_ID;
				}
			}

			if ( ! empty( $customer_number ) && ! empty( $contract_id ) ) {
				$xml .= '<customer-number>' . esc_xml( $customer_number ) . '</customer-number>';
				$xml .= '<contract-id>' . esc_xml( $contract_id ) . '</contract-id>';
			} else {
				$xml .= '<quote-type>counter</quote-type>';
			}

			// Parcel characteristics.
			$xml .= '<parcel-characteristics>';
			$xml .= '<weight>' . round( $package->total_weight, 3 ) . '</weight>';
			$xml .= '<dimensions>';
			$xml .= '<length>' . round( $package->packed_dimensions->length, 1 ) . '</length>';
			$xml .= '<width>' . round( $package->packed_dimensions->width, 1 ) . '</width>';
			$xml .= '<height>' . round( $package->packed_dimensions->height, 1 ) . '</height>';
			$xml .= '</dimensions>';
			if ( $this->get_setting( 'mailingTube' ) ) {
				$xml .= '<mailing-tube>true</mailing-tube>';
			}
			if ( $this->get_setting( 'unpackaged' ) ) {
				$xml .= '<unpackaged>true</unpackaged>';
			}
			$xml .= '</parcel-characteristics>';

			// Options.
			$options = $this->get_setting( 'options', array() );
			if ( ! empty( $options ) ) {
				$xml .= '<options>';
				foreach ( $options as $option ) {
					$xml .= '<option>';
					$xml .= '<option-code>' . esc_xml( $option['code'] ) . '</option-code>';
					if ( ! empty( $option['amount'] ) && is_numeric( $option['amount'] ) && $option['amount'] > 0 ) {
						$xml .= '<option-amount>' . number_format( (float) $option['amount'], 2, '.', '' ) . '</option-amount>';
					}
					$xml .= '</option>';
				}
				$xml .= '</options>';
			}

			// Origin.
			$xml .= '<origin-postal-code>' . $store_address['postcode'] . '</origin-postal-code>';

			// Destination.
			$xml .= '<destination>';
			if ( 'CA' === $destination['country'] ) {
				$xml .= '<domestic>';
				$xml .= '<postal-code>' . $destination['postcode'] . '</postal-code>';
				$xml .= '</domestic>';
			} elseif ( 'US' === $destination['country'] ) {
				$xml .= '<united-states>';
				$xml .= '<zip-code>' . $destination['postcode'] . '</zip-code>';
				$xml .= '</united-states>';
			} else {
				$xml .= '<international>';
				$xml .= '<country-code>' . $destination['country'] . '</country-code>';
				$xml .= '<postal-code>' . $destination['postcode'] . '</postal-code>';
				$xml .= '</international>';
			}
			$xml .= '</destination>';

			$xml .= '</mailing-scenario>';

			$payloads[] = $xml;
		}

		return $payloads;
	}

	/**
	 * Perform a remote GET returning decoded JSON or WP_Error.
	 *
	 * @param string $url Endpoint.
	 * @param string $params XML string.
	 * @return array|\WP_Error
	 */
	protected function remote_get_json( string $url, string $params ) {
		$username = $this->get_secret( 'username' );
		$password = $this->get_secret( 'password' );

		if ( 'development' === $this->get_setting( 'mode' ) ) {
			if ( empty( $username ) ) {
				$username = self::SANDBOX_USERNAME;
			}
			if ( empty( $password ) ) {
				$password = self::SANDBOX_PASSWORD;
			}
		}

		if ( empty( $username ) || empty( $password ) ) {
			$error = new WP_Error( 'missing_credentials', 'Missing API credentials' );
			$this->error( 'missing_credentials', $error );
			return $error;
		}

		$headers = array(
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'Authorization'   => 'Basic ' . base64_encode( $username . ':' . $password ),
			'Accept'          => 'application/vnd.cpc.ship.rate-v4+xml',
			'Content-Type'    => 'application/vnd.cpc.ship.rate-v4+xml',
			'Accept-language' => 'en-CA',
			'User-Agent'      => 'WowShipping/' . WTRS_VER,
		);

		$args = array(
			'headers'   => $headers,
			'timeout'   => 40,
			'sslverify' => false,
			'body'      => $params,
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->error( 'remote_request_failed', $response );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

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

		return json_decode( wp_json_encode( simplexml_load_string( $body ) ), true );
	}

	/**
	 * Parse Sendle API response array into array of Rate.
	 *
	 * @param array           $data Raw decoded response.
	 * @param ShipmentRequest $request Original shipment request.
	 * @return Rate|null
	 */
	protected function parse_rate_response( $data, ShipmentRequest $request ) {
		if ( ! is_array( $data ) || ! isset( $data['price-quote'] ) ) {
			$this->error( 'invalid_response', 'Unexpected format in API response', $data );
			return null;
		}

		$service = $this->get_setting( 'service' );

		if ( ! $service ) {
			$this->error( 'no_service_selected', 'No service selected in settings' );
			return null;
		}

		if ( isset( $data['price-quote']['service-code'] ) ) {
			// Single quote, make it an array of one.
			$data['price-quote'] = array( $data['price-quote'] );
		}

		foreach ( $data['price-quote'] as $quote ) {
			if ( $quote['service-code'] !== $service ) {
				continue;
			}
			$cost = floatval( $quote['price-details']['due'] );

			$cost = $this->convert_currency( $cost, 'CAD', $request->currency );

			if ( false === $cost ) {
				$this->error( 'currency_conversion_failed', 'Failed to convert CAD to store currency' );
				continue;
			}

			$delivery_time = '';
			$transit_days  = ! empty( $quote['service-standard']['expected-transit-time'] ) ? intval( $quote['service-standard']['expected-transit-time'] ) : null;

			if ( ! empty( $transit_days ) ) {
				// translators: %d is the number of days.
				$delivery_time = sprintf( _n( '%d day', '%d days', $transit_days, 'table-rate-shipping-pro' ), $transit_days );
			}

			return new Rate(
				$this->get_key(),
				$quote['service-code'],
				$quote['service-name'],
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
		$this->log( 'validation_started', 'Starting Canada Post credentials validation' );

		$required = array( 'username', 'password' );
		foreach ( $required as $field ) {
			if ( empty( $credentials[ $field ] ) ) {
				$this->error( 'validation_failed', 'Missing required credential field', array( 'field' => $field ) );
				return false;
			}
		}

		$username = $credentials['username'];
		$password = $credentials['password'];

		$headers = array(
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'Authorization'   => 'Basic ' . base64_encode( $username . ':' . $password ),
			'Accept'          => 'application/vnd.cpc.serviceinfo-v2+xml',
			'Accept-Language' => 'en-CA',
			'User-Agent'      => 'WowShipping/' . WTRS_VER,
		);

		$args = array(
			'headers'   => $headers,
			'timeout'   => 15,
			'sslverify' => false,
		);

		$test_urls = array(
			'https://soa-gw.canadapost.ca/rs/serviceinfo/shipment?messageType=SO',
			'https://ct.soa-gw.canadapost.ca/rs/serviceinfo/shipment?messageType=SO',
		);

		$is_valid = false;

		foreach ( $test_urls as $url ) {
			$this->log( 'validation_testing', 'Testing credentials', array( 'url' => $url ) );
			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				$this->warning( 'validation_request_failed', $response );
				continue;
			}

			if ( 200 === wp_remote_retrieve_response_code( $response ) ) {
				$is_valid = true;
				$this->log( 'validation_success', 'Credentials validated successfully', array( 'url' => $url ) );
				break;
			} else {
				$this->warning(
					'validation_auth_failed',
					'Authentication failed',
					array(
						'url'    => $url,
						'status' => wp_remote_retrieve_response_code( $response ),
					)
				);
			}
		}

		if ( ! $is_valid ) {
			$this->error( 'validation_failed_all', 'Credentials validation failed for all endpoints' );
			return false;
		}

		if ( $is_valid ) {
			$this->credential_manager->bulk_set(
				array(
					'username'        => $username,
					'password'        => $password,
					'customer_number' => $credentials['customer_number'] ?? '',
					'contract_id'     => $credentials['contract_id'] ?? '',
				)
			);
		}

		return $is_valid;
	}
}
