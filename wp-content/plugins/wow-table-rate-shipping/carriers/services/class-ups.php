<?php // phpcs:ignore
namespace WTRS\Carriers\Services;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WTRS\Carriers\AbstractCarrier;
use WTRS\Carriers\DTO\ShipmentRequest;
use WTRS\Carriers\DTO\Rate;

/**
 * UPS Carrier (OAuth + Rating v2205 Shop API)
 *
 * Builds a UPS RateRequest payload (with time-in-transit) and normalizes
 * returned rates into Rate DTOs. Token is retrieved via OAuth and cached
 * in a transient.
 */
final class Ups extends AbstractCarrier {

	private const LIVE_HOST = 'https://onlinetools.ups.com';
	private const TEST_HOST = 'https://wwwcie.ups.com';

	private const EP_RATE_SHOP = '/api/rating/v2205/shop?additionalinfo=timeintransit';
	private const EP_RATE      = '/api/rating/v2205/rate';
	private const EP_TOKEN     = '/security/v1/oauth/token';

	/** Token transient base key (suffixed by env) */
	private const TOKEN_TRANSIENT = 'wtrs_ups_token_';

	/** Default TTL fallback (seconds) if API does not return expires_in */
	private const TOKEN_TTL = 60 * 55; // 55 minutes.

	/** Default packaging code: Customer Supplied Package (02). */
	private const PKG_CODE = '02';

	/** Synthetic UI value used to represent UPS Ground Saver / SurePost. */
	private const SERVICE_GROUND_SAVER = 'ground_saver';

	/** UPS Ground Saver under-1lb code. */
	private const SERVICE_GROUND_SAVER_UNDER_1LB = '92';

	/** UPS Ground Saver 1lb-and-up code. */
	private const SERVICE_GROUND_SAVER_STANDARD = '93';

	/** Pounds to kilograms conversion threshold for UPS Ground Saver under-1lb rating. */
	private const ONE_POUND_IN_KG = 0.45359237;

	/** Kilograms to ounces conversion factor. */
	private const KG_TO_OUNCES = 35.27396195;

	/** Minimum supported UPS Ground Saver under-1lb package weight in ounces. */
	private const MIN_GROUND_SAVER_OUNCES = 0.1;

	/** Pounds to kilograms conversion factor. */
	private const POUNDS_TO_KG = self::ONE_POUND_IN_KG;

	/**
	 * Human-readable labels for UPS weight units used in requests.
	 *
	 * @var array<string, string>
	 */
	private const WEIGHT_UNIT_LABELS = array(
		'KGS' => 'Kilograms',
		'LBS' => 'Pounds',
		'OZS' => 'Ounces',
	);

	/** Basic mapping of common UPS service codes to labels (fallback when API omits description). */
	private const SERVICE_LABELS =
	array(
		array(
			'value' => '1',
			'label' => 'Next Day Air - Domestic',
		),
		array(
			'value' => '2',
			'label' => '2nd Day Air - Domestic',
		),
		array(
			'value' => '3',
			'label' => 'Ground - Domestic',
		),
		array(
			'value' => self::SERVICE_GROUND_SAVER,
			'label' => 'Ground Saver - Domestic',
		),
		array(
			'value' => '12',
			'label' => '3 Day Select - Domestic',
		),
		array(
			'value' => '13',
			'label' => 'Next Day Air Saver - Domestic',
		),
		array(
			'value' => '14',
			'label' => 'UPS Next Day Air Early - Domestic',
		),
		array(
			'value' => '59',
			'label' => '2nd Day Air A.M. - Domestic',
		),
		array(
			'value' => '75',
			'label' => 'UPS Heavy Goods - Domestic',
		),
		array(
			'value' => '7',
			'label' => 'Worldwide Express - International',
		),
		array(
			'value' => '8',
			'label' => 'Worldwide Expedited - International',
		),
		array(
			'value' => '11',
			'label' => 'Standard - International',
		),
		array(
			'value' => '54',
			'label' => 'Worldwide Express Plus - International',
		),
		array(
			'value' => '65',
			'label' => 'Saver - International',
		),
		array(
			'value' => '96',
			'label' => 'UPS Worldwide Express Freight - International',
		),
		array(
			'value' => '71',
			'label' => 'UPS Worldwide Express Freight Midday - International',
		),
	);

	/*
	 ***************************************************
	 * Required interface methods
	 ***************************************************
	*/

	/**
	 * Unique carrier key used internally by the plugin.
	 *
	 * @return string Carrier key slug (e.g., 'ups').
	 */
	public static function get_key(): string {
		return 'ups';
	}

	/**
	 * Human readable carrier label for UI.
	 *
	 * @return string 'UPS'
	 */
	public static function get_label(): string {
		return 'UPS';
	}

	/**
	 * Supported packing strategies for this carrier.
	 *
	 * @return array List of packer keys.
	 */
	public static function get_supported_packers(): array {
		return array( 'single_package', 'per_item', 'box', 'weight' );
	}

	/**
	 * Return structured values for UI contexts.
	 *
	 * @param string $type    Type key.
	 * @param string $context Context (view/edit).
	 * @return mixed
	 */
	public static function get_values( $type, $context = 'view' ) {
		if ( 'supported_packers' === $type ) {
			return self::add_packer_labels( self::get_supported_packers() );
		}

		if ( 'services' === $type ) {
			return self::SERVICE_LABELS;
		}

		return array();
	}

	/**
	 * Parse settings, applying defaults & validation.
	 *
	 * @param array $values Raw settings.
	 * @return array
	 */
	protected static function parse_settings( $values ) {
		$parsed = array();

		// phpcs:disable Generic.Formatting.MultipleStatementAlignment
		// Environment: sandbox | live.
		$parsed['mode']    = in_array( $values['mode'] ?? 'sandbox', array( 'sandbox', 'live' ), true ) ? $values['mode'] : 'sandbox';
		$parsed['service'] = $values['service'] ?? '';

		// Whether to request negotiated rates (if account eligible).
		$parsed['negotiated'] = ! empty( $values['negotiated'] );

		$parsed['residentialDelivery'] = ! empty( $values['residentialDelivery'] );

		// Optional: enable ETA handling later if needed.
		$parsed['enableEta'] = ! empty( $values['enableEta'] );
		// phpcs:enable Generic.Formatting.MultipleStatementAlignment

		if ( self::is_ground_saver_selection( $parsed['service'] ) ) {
			$parsed['service'] = self::SERVICE_GROUND_SAVER;
		}

		return array_merge( parent::parse_settings( $values ), $parsed );
	}

	/**
	 * Determine if live (production) environment is selected.
	 *
	 * @return bool True for live, false for sandbox.
	 */
	protected function is_live(): bool {
		return ( $this->settings['mode'] ?? 'sandbox' ) === 'live';
	}

	/**
	 * Get base host for current environment.
	 *
	 * @return string Base URL host.
	 */
	private function host(): string {
		return $this->is_live() ? self::LIVE_HOST : self::TEST_HOST;
	}

	/*
	 ***************************************************
	 * Public API
	 ***************************************************
	*/
	/**
	 * Fetch normalized UPS rates for the shipment.
	 *
	 * @param ShipmentRequest $request Shipment abstraction.
	 * @return array<Rate>|false
	 */
	public function fetch_rates( ShipmentRequest $request ) {
		$this->log( 'fetch_rates_started', 'Starting UPS rate request' );

		// Require credentials.
		$client_id     = $this->get_secret( 'client_id' );
		$client_secret = $this->get_secret( 'client_secret' );
		if ( ! $client_id || ! $client_secret ) {
			$this->error( 'missing_credentials', __( 'UPS credentials not set.', 'table-rate-shipping-pro' ) );
			return false;
		}

		// Map packages into UPS Package[] entries, and compute shipment totals.
		$pkg_map = $this->map_packages( $request );
		if ( empty( $pkg_map['packages'] ) ) {
			$this->warning( 'no_packages', 'No packages to rate' );
			return array();
		}

		$token = $this->get_token();
		if ( ! $token ) {
			$this->error( 'auth_failed', __( 'Unable to authenticate with UPS API.', 'table-rate-shipping-pro' ) );
			return false;
		}

		$this->log( 'token_obtained', 'UPS access token obtained successfully' );

		$payload = $this->build_rate_payload( $request, $pkg_map );
		$payload = apply_filters( 'wtrs_ups_rate_payload', $payload, $request, $pkg_map, $this );

		$this->log( 'UPS Rate Payload', 'Built UPS rate payload', $payload );

		$this->log( 'sending_request', 'Sending rate request to UPS API' );

		$response = $this->remote_json( 'POST', $this->host() . $this->get_rate_endpoint( $payload ), $payload, $token );
		if ( is_wp_error( $response ) ) {
			$this->error( 'api_error', $response );
			return false;
		}

		$this->log( 'UPS Rate Response', 'Received response from UPS', $response );

		$rates = $this->normalize_rates( $response, $request );
		if ( false === $rates ) {
			$this->warning( 'normalization_failed', 'Failed to normalize UPS rates' );
			return false;
		}
		if ( empty( $rates ) ) {
			$this->warning( 'no_rates_returned', 'No rates returned from UPS' );
			return array();
		}

		$this->log( 'rates_fetched', 'UPS rates fetched successfully', array( 'rate_count' => count( $rates ) ) );

		return apply_filters( 'wtrs_carrier_filter_normalized_rates', $rates, $this->get_key(), $request );
	}

	/*
	 ***************************************************
	 * Token Handling
	 ***************************************************
	*/
	/**
	 * Retrieve cached OAuth token, requesting a new token on cache miss.
	 *
	 * @return string|null Bearer token or null on failure.
	 */
	private function get_token(): ?string {
		$key    = self::TOKEN_TRANSIENT . ( $this->is_live() ? 'live' : 'test' );
		$cached = get_transient( $key );
		if ( $cached ) {
			$this->log( 'token_from_cache', 'Using cached UPS access token' );
			return $cached;
		}
		$this->log( 'requesting_token', 'Requesting new UPS access token' );
		return $this->request_token( $key );
	}

	/**
	 * Request a fresh OAuth token (Client Credentials flow) and cache it.
	 *
	 * @param string $cache_key Transient key to store token against.
	 * @return string|null Access token or null on failure.
	 */
	private function request_token( string $cache_key ): ?string {
		$client_id     = $this->get_secret( 'client_id' );
		$client_secret = $this->get_secret( 'client_secret' );
		if ( ! $client_id || ! $client_secret ) {
			return null;
		}

		$url  = $this->host() . self::EP_TOKEN;
		$args = array(
			'method'      => 'POST',
			'timeout'     => 40,
			'headers'     => array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'x-merchant-id' => $client_id,
				// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required by UPS OAuth Basic Auth header.
				'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ),
				'User-Agent'    => 'WowShipping/' . ( defined( 'WTRS_VER' ) ? WTRS_VER : 'dev' ),
			),
			'body'        => 'grant_type=client_credentials',
			'sslverify'   => false,
			'data_format' => 'body',
		);

		$resp = wp_remote_request( $url, $args );
		if ( is_wp_error( $resp ) ) {
			$this->error( 'token_request_failed', $resp );
			return null;
		}
		$code   = wp_remote_retrieve_response_code( $resp );
		$raw    = wp_remote_retrieve_body( $resp );
		$parsed = json_decode( $raw, true );
		if ( 200 !== $code || empty( $parsed['access_token'] ) ) {
			$error = new \WP_Error(
				'token_response_error',
				'Failed to obtain token',
				array(
					'status' => $code,
					'body'   => $parsed,
				)
			);
			$this->error( 'token_response_error', $error );
			return null;
		}
		$this->log( 'token_received', 'UPS access token received successfully' );
		$ttl = isset( $parsed['expires_in'] ) ? max( 60, (int) $parsed['expires_in'] - 60 ) : self::TOKEN_TTL;
		set_transient( $cache_key, $parsed['access_token'], $ttl );
		return $parsed['access_token'];
	}

	/*
	 ***************************************************
	 * Mapping & Payload
	 ***************************************************
	*/
	/**
	 * Compute units and map packages to UPS package array.
	 * Assumes incoming packer values are in kg/cm; converts to lbs/in if needed.
	 *
	 * @param ShipmentRequest $request Request.
	 * @return array { packages: array, total_weight: float, unit_w: string, unit_d: string }
	 */
	private function map_packages( ShipmentRequest $request ): array {
		// Determine target units from store settings.
		$wc_wu  = get_option( 'woocommerce_weight_unit', 'kg' );
		$wc_du  = get_option( 'woocommerce_dimension_unit', 'cm' );
		$use_si = in_array( strtolower( $wc_wu ), array( 'kg', 'g' ), true );

		$unit_w = $use_si ? 'KGS' : 'LBS';
		$unit_d = $use_si ? 'CM' : 'IN';

		$packages     = array();
		$total_weight = 0.0;

		foreach ( $request->packages as $pkg ) {
			// Base values from packer (kg/cm assumed).
			$w_kg  = max( 0.001, (float) $pkg->total_weight );
			$len_c = (float) $pkg->packed_dimensions->length;
			$wid_c = (float) $pkg->packed_dimensions->width;
			$hgt_c = (float) $pkg->packed_dimensions->height;

			// Convert using WC helpers when available.
			if ( function_exists( 'wc_get_weight' ) ) {
				$w_val = $use_si ? (float) wc_get_weight( $w_kg, 'kg', 'kg' ) : (float) wc_get_weight( $w_kg, 'lbs', 'kg' );
			} else {
				$w_val = $use_si ? $w_kg : $w_kg / 0.45359237;
			}
			if ( function_exists( 'wc_get_dimension' ) ) {
				$l_val     = $use_si ? (float) wc_get_dimension( $len_c, 'cm', 'cm' ) : (float) wc_get_dimension( $len_c, 'in', 'cm' );
				$w_val_dim = $use_si ? (float) wc_get_dimension( $wid_c, 'cm', 'cm' ) : (float) wc_get_dimension( $wid_c, 'in', 'cm' );
				$h_val     = $use_si ? (float) wc_get_dimension( $hgt_c, 'cm', 'cm' ) : (float) wc_get_dimension( $hgt_c, 'in', 'cm' );
			} else {
				$l_val     = $use_si ? $len_c : $len_c / 2.54;
				$w_val_dim = $use_si ? $wid_c : $wid_c / 2.54;
				$h_val     = $use_si ? $hgt_c : $hgt_c / 2.54;
			}

			$total_weight += $w_val;

			$pkg_entry = array(
				'PackagingType' => array( 'Code' => self::PKG_CODE ),
				'PackageWeight' => array(
					'UnitOfMeasurement' => array(
						'Code'        => $unit_w,
						'Description' => ( 'KGS' === $unit_w ) ? 'Kilograms' : 'Pounds',
					),
					'Weight'            => substr( number_format( $w_val, 4 ), 0, 6 ),
				),
			);

			// Add dimensions if non-zero.
			if ( $l_val > 0 && $w_val_dim > 0 && $h_val > 0 ) {
				$pkg_entry['Dimensions'] = array(
					'UnitOfMeasurement' => array(
						'Code'        => $unit_d,
						'Description' => ( 'CM' === $unit_d ) ? 'Centimeter' : 'Inches',
					),
					'Length'            => substr( number_format( $l_val, 4 ), 0, 6 ),
					'Width'             => substr( number_format( $w_val_dim, 4 ), 0, 6 ),
					'Height'            => substr( number_format( $h_val, 4 ), 0, 6 ),
				);
			}

			$packages[] = $pkg_entry;
		}

		return array(
			'packages'     => $packages,
			'total_weight' => $total_weight,
			'unit_w'       => $unit_w,
			'unit_d'       => $unit_d,
		);
	}

	/**
	 * Build complete UPS Rating (v2205 Shop) request payload.
	 *
	 * @param ShipmentRequest $request Shipment abstraction from packing pipeline.
	 * @param array           $pkg_map  Output of map_packages():
	 *                                  { packages: array, total_weight: float, unit_w: string, unit_d: string }.
	 * @return array JSON-serializable payload for UPS API.
	 */
	private function build_rate_payload( ShipmentRequest $request, array $pkg_map ): array {
		$origin = $request->origin_location;
		$dest   = $request->destination_location;

		// Shipper and ShipFrom use origin; ShipTo uses destination.
		$shipper = $this->build_address_block( $origin, true );
		$shipto  = $this->build_address_block( $dest, false, ! empty( $this->settings['residentialDelivery'] ) );
		$shipfrm = $this->build_address_block( $origin, true );

		$selected_service = (string) $this->get_setting( 'service', '' );
		$service_code     = $this->resolve_requested_service_code( $selected_service, $pkg_map );
		$pkg_map          = $this->normalize_pkg_map_for_service_code( $pkg_map, $service_code );
		$request_option   = self::is_ground_saver_selection( $selected_service ) ? 'Rate' : 'Shoptimeintransit';

		$currency                = $request->currency ? $request->currency : 'USD';
		$total_w                 = substr( number_format( (float) $pkg_map['total_weight'], 4 ), 0, 6 );
		$weight_unit_description = $this->get_weight_unit_description( (string) $pkg_map['unit_w'] );

		// Optional negotiated rates indicator.
		$rating_options = array();
		if ( ! empty( $this->settings['negotiated'] ) ) {
			$rating_options['NegotiatedRatesIndicator'] = '1';
		}

		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment
		$payload = array(
			'RateRequest' => array(
				'Request'  => array(
					'RequestOption' => $request_option,
				),
				'Shipment' => array(
					'Shipper'                 => $shipper,
					'ShipTo'                  => $shipto,
					'ShipFrom'                => $shipfrm,
					'Package'                 => $pkg_map['packages'],
					'ShipmentTotalWeight'     => array(
						'UnitOfMeasurement' => array(
							'Code'        => $pkg_map['unit_w'],
							'Description' => $weight_unit_description,
						),
						'Weight'            => $total_w,
					),
					'InvoiceLineTotal'        => array(
						'CurrencyCode'  => $currency,
						'MonetaryValue' => (string) round( (float) ( $request->cart_subtotal ?? 0 ), 2 ),
					),
				),
			),
		);
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment

		// CustomerClassification is US-only in original reference; include when origin is US.
		if ( ( $origin['country'] ?? '' ) === 'US' ) {
			$payload['RateRequest']['Request']['CustomerClassification'] = '00';
		}
		if ( ! empty( $rating_options ) ) {
			$payload['RateRequest']['Shipment']['ShipmentRatingOptions'] = $rating_options;
		}
		if ( 'Shoptimeintransit' === $request_option ) {
			$payload['RateRequest']['Shipment']['DeliveryTimeInformation'] = array( 'PackageBillType' => '03' );
		}
		if ( '' !== $service_code ) {
			$payload['RateRequest']['Shipment']['Service'] = array(
				'Code' => $service_code,
			);
		}

		return $payload;
	}

	/**
	 * Build UPS Address structure from a location array.
	 *
	 * @param array $loc Location array with keys: address_1, address_2, city, state, postcode, country.
	 * @param bool  $include_shipper_number Whether to include Name and ShipperNumber (from saved secrets).
	 * @param bool  $include_residential_indicator Whether to mark the address as residential.
	 * @return array UPS-formatted address block.
	 */
	private function build_address_block( array $loc, bool $include_shipper_number = false, bool $include_residential_indicator = false ): array {
		$state = isset( $loc['state'] ) ? substr( (string) $loc['state'], 0, 2 ) : '';
		$lines = array();
		if ( ! empty( $loc['address_1'] ) ) {
			$lines[] = (string) $loc['address_1'];
		}
		if ( ! empty( $loc['address_2'] ) ) {
			$lines[] = (string) $loc['address_2'];
		}
		if ( empty( $lines ) && ! empty( $loc['city'] ) ) {
			$lines[] = (string) $loc['city'];
		}

		$addr = array(
			'Address' => array(
				'AddressLine'       => $lines,
				'City'              => (string) ( $loc['city'] ?? '' ),
				'StateProvinceCode' => $state,
				'PostalCode'        => (string) ( $loc['postcode'] ?? '' ),
				'CountryCode'       => (string) ( $loc['country'] ?? '' ),
			),
		);

		if ( $include_residential_indicator ) {
			$addr['Address']['ResidentialAddressIndicator'] = '1';
		}

		if ( $include_shipper_number ) {
			// Optional fields if merchant provides them via credential manager.
			$name  = $this->get_secret( 'shipper_name', '' );
			$accno = $this->get_secret( 'account_number', '' );
			if ( $name ) {
				$addr['Name'] = $name;
			}
			if ( $accno ) {
				$addr['ShipperNumber'] = $accno;
			}
		}

		return $addr;
	}

	/**
	 * Determine whether the selection should be treated as UPS Ground Saver.
	 *
	 * @param string $service_code Selected service value.
	 * @return bool
	 */
	private static function is_ground_saver_selection( string $service_code ): bool {
		$service_code = strtoupper( trim( $service_code ) );

		return in_array(
			$service_code,
			array(
				strtoupper( self::SERVICE_GROUND_SAVER ),
				self::SERVICE_GROUND_SAVER_UNDER_1LB,
				self::SERVICE_GROUND_SAVER_STANDARD,
				'94',
				'95',
			),
			true
		);
	}

	/**
	 * Choose the correct UPS rating endpoint for the current payload.
	 *
	 * @param array $payload UPS rating payload.
	 * @return string
	 */
	private function get_rate_endpoint( array $payload ): string {
		$request_option = (string) ( $payload['RateRequest']['Request']['RequestOption'] ?? '' );

		return 'Rate' === $request_option ? self::EP_RATE : self::EP_RATE_SHOP;
	}

	/**
	 * Resolve a fallback UPS service label by service code.
	 *
	 * @param string $service_code UPS service code.
	 * @return string
	 */
	private function get_service_label_by_code( string $service_code ): string {
		$normalized_service_code = $this->normalize_service_code( $service_code );
		if ( in_array( $normalized_service_code, array( '92', '93', '94', '95' ), true ) ) {
			return 'UPS Ground Saver - Domestic';
		}

		foreach ( self::SERVICE_LABELS as $service ) {
			if ( $this->normalize_service_code( (string) ( $service['value'] ?? '' ) ) === $normalized_service_code ) {
				return (string) ( $service['label'] ?? '' );
			}
		}

		return '';
	}

	/**
	 * Resolve a weight unit description for UPS payloads.
	 *
	 * @param string $unit_code UPS weight unit code.
	 * @return string
	 */
	private function get_weight_unit_description( string $unit_code ): string {
		return self::WEIGHT_UNIT_LABELS[ $unit_code ] ?? $unit_code;
	}

	/**
	 * Normalize a UPS service code for internal comparisons.
	 *
	 * @param string $service_code Raw service code.
	 * @return string
	 */
	private function normalize_service_code( string $service_code ): string {
		$service_code = trim( $service_code );
		if ( '' === $service_code ) {
			return '';
		}

		if ( ctype_digit( $service_code ) ) {
			return (string) intval( $service_code );
		}

		return strtoupper( $service_code );
	}

	/**
	 * Format a UPS service code for API requests.
	 *
	 * @param string $service_code Raw service code.
	 * @return string
	 */
	private function format_service_code_for_request( string $service_code ): string {
		$normalized_code = $this->normalize_service_code( $service_code );
		if ( '' === $normalized_code ) {
			return '';
		}

		if ( ctype_digit( $normalized_code ) ) {
			return str_pad( $normalized_code, 2, '0', STR_PAD_LEFT );
		}

		return $normalized_code;
	}

	/**
	 * Resolve the actual UPS service code to send in the rating request.
	 *
	 * @param string $selected_service Saved service selection.
	 * @param array  $pkg_map          Output of map_packages().
	 * @return string
	 */
	private function resolve_requested_service_code( string $selected_service, array $pkg_map ): string {
		if ( self::is_ground_saver_selection( $selected_service ) ) {
			return $this->resolve_ground_saver_service_code( $pkg_map );
		}

		return $this->format_service_code_for_request( $selected_service );
	}

	/**
	 * Select UPS Ground Saver code 92 or 93 based on package weight.
	 *
	 * UPS expects 92 for packages under 1 lb and 93 for packages at or above 1 lb.
	 * Because the rating request only carries one shipment service code, use 92 only
	 * when every packed package is below the threshold.
	 *
	 * @param array $pkg_map Output of map_packages().
	 * @return string
	 */
	private function resolve_ground_saver_service_code( array $pkg_map ): string {
		$threshold = 'KGS' === ( $pkg_map['unit_w'] ?? '' ) ? self::ONE_POUND_IN_KG : 1.0;

		foreach ( $pkg_map['packages'] ?? array() as $package ) {
			$weight = isset( $package['PackageWeight']['Weight'] ) ? (float) $package['PackageWeight']['Weight'] : 0.0;
			if ( $weight >= $threshold ) {
				return self::SERVICE_GROUND_SAVER_STANDARD;
			}
		}

		return self::SERVICE_GROUND_SAVER_UNDER_1LB;
	}

	/**
	 * Adjust request weights for service-specific UPS requirements.
	 *
	 * UPS Ground Saver code 92 requires package and shipment weights in ounces,
	 * with a minimum supported value of 0.1 oz.
	 *
	 * @param array  $pkg_map      Output of map_packages().
	 * @param string $service_code UPS service code chosen for the request.
	 * @return array
	 */
	private function normalize_pkg_map_for_service_code( array $pkg_map, string $service_code ): array {
		$normalized_service_code = $this->normalize_service_code( $service_code );

		if ( self::SERVICE_GROUND_SAVER_UNDER_1LB === $normalized_service_code ) {
			return $this->convert_pkg_map_weight_unit( $pkg_map, 'OZS', self::MIN_GROUND_SAVER_OUNCES );
		}

		if ( self::SERVICE_GROUND_SAVER_STANDARD === $normalized_service_code ) {
			return $this->convert_pkg_map_weight_unit( $pkg_map, 'LBS' );
		}

		return $pkg_map;
	}

	/**
	 * Convert the pkg_map weight values to a target UPS unit.
	 *
	 * @param array  $pkg_map      Output of map_packages().
	 * @param string $target_unit  UPS target weight unit.
	 * @param float  $minimum      Optional minimum weight after conversion.
	 * @return array
	 */
	private function convert_pkg_map_weight_unit( array $pkg_map, string $target_unit, float $minimum = 0.0 ): array {
		$source_unit = (string) ( $pkg_map['unit_w'] ?? '' );
		if ( '' === $source_unit ) {
			return $pkg_map;
		}

		$pkg_map['unit_w']       = $target_unit;
		$pkg_map['total_weight'] = max( $minimum, $this->convert_weight_value( (float) ( $pkg_map['total_weight'] ?? 0 ), $source_unit, $target_unit ) );

		foreach ( $pkg_map['packages'] as &$package ) {
			$weight = isset( $package['PackageWeight']['Weight'] ) ? (float) $package['PackageWeight']['Weight'] : 0.0;
			$weight = max( $minimum, $this->convert_weight_value( $weight, $source_unit, $target_unit ) );

			$package['PackageWeight']['UnitOfMeasurement']['Code']        = $target_unit;
			$package['PackageWeight']['UnitOfMeasurement']['Description'] = $this->get_weight_unit_description( $target_unit );
			$package['PackageWeight']['Weight']                           = substr( number_format( $weight, 4 ), 0, 6 );
		}
		unset( $package );

		return $pkg_map;
	}

	/**
	 * Convert a weight value between the UPS units used by this carrier.
	 *
	 * @param float  $weight      Weight value to convert.
	 * @param string $source_unit Source UPS unit.
	 * @param string $target_unit Target UPS unit.
	 * @return float
	 */
	private function convert_weight_value( float $weight, string $source_unit, string $target_unit ): float {
		if ( $source_unit === $target_unit ) {
			return $weight;
		}

		switch ( $source_unit ) {
			case 'KGS':
				$weight_in_kg = $weight;
				break;
			case 'LBS':
				$weight_in_kg = $weight * self::POUNDS_TO_KG;
				break;
			case 'OZS':
				$weight_in_kg = $weight / self::KG_TO_OUNCES;
				break;
			default:
				return $weight;
		}

		switch ( $target_unit ) {
			case 'KGS':
				return $weight_in_kg;
			case 'LBS':
				return $weight_in_kg / self::ONE_POUND_IN_KG;
			case 'OZS':
				return $weight_in_kg * self::KG_TO_OUNCES;
			default:
				return $weight;
		}
	}

	/**
	 * Compare two UPS service codes, allowing for padded numeric forms.
	 *
	 * @param string $expected Expected service code.
	 * @param string $actual   Actual service code.
	 * @return bool
	 */
	private function service_codes_match( string $expected, string $actual ): bool {
		if ( self::is_ground_saver_selection( $expected ) ) {
			return in_array( $this->normalize_service_code( $actual ), array( '92', '93' ), true );
		}

		$normalized_expected = $this->normalize_service_code( $expected );
		$normalized_actual   = $this->normalize_service_code( $actual );

		return '' !== $normalized_expected && $normalized_expected === $normalized_actual;
	}

	/**
	 * Resolve a safe service label from the UPS response.
	 *
	 * @param mixed  $service_desc Raw UPS service description.
	 * @param string $service_code UPS service code.
	 * @return string
	 */
	private function resolve_service_label( $service_desc, string $service_code ): string {
		if ( is_string( $service_desc ) && '' !== trim( $service_desc ) ) {
			return $service_desc;
		}

		if ( is_array( $service_desc ) ) {
			$candidates = array(
				$service_desc['Description'] ?? null,
				$service_desc['label'] ?? null,
				$service_desc['value'] ?? null,
				reset( $service_desc ),
			);

			foreach ( $candidates as $candidate ) {
				if ( is_string( $candidate ) && '' !== trim( $candidate ) ) {
					return $candidate;
				}
			}
		}

		$fallback = $this->get_service_label_by_code( $service_code );

		return '' !== $fallback ? $fallback : 'UPS ' . $service_code;
	}

	/**
	 * Normalize UPS shop rate response into Rate DTOs.
	 * Returns multiple Rates (one per service) so the caller can choose.
	 *
	 * @param array           $response API response.
	 * @param ShipmentRequest $request Shipment request.
	 * @return array<Rate>|false
	 */
	private function normalize_rates( array $response, ShipmentRequest $request ) {
		$root = $response['RateResponse'] ?? null;
		if ( ! is_array( $root ) ) {
			return false;
		}
		$list = $root['RatedShipment'] ?? array();
		if ( empty( $list ) || ! is_array( $list ) ) {
			return array();
		}
		if ( isset( $list['Service'] ) ) {
			$list = array( $list );
		}

		$use_negotiated   = ! empty( $this->settings['negotiated'] );
		$rates            = array();
		$store_currency   = $request->currency ? $request->currency : 'USD';
		$selected_service = $this->get_setting( 'service', '' );

		if ( empty( $selected_service ) ) {
			return false;
		}

		foreach ( $list as $entry ) {
			$service_code = (string) ( $entry['Service']['Code'] ?? '' );
			$service_desc = $entry['Service']['Description'] ?? '';

			if ( ! $this->service_codes_match( $selected_service, $service_code ) ) {
				continue;
			}

			// Default to published charges.
			$charge_node = $entry['TotalCharges'] ?? array();
			if ( $use_negotiated && isset( $entry['NegotiatedRateCharges']['TotalCharge'] ) ) {
				$charge_node = $entry['NegotiatedRateCharges']['TotalCharge'];
			}
			$amount   = isset( $charge_node['MonetaryValue'] ) ? (float) $charge_node['MonetaryValue'] : null;
			$currency = (string) ( $charge_node['CurrencyCode'] ?? $store_currency );

			if ( null === $amount ) {
				continue;
			}

			// Delivery estimate: try time in transit when available.
			$delivery_txt = null;
			$transit_days = null;
			if ( isset( $entry['GuaranteedDelivery']['BusinessDaysInTransit'] ) ) {
				$days = (int) $entry['GuaranteedDelivery']['BusinessDaysInTransit'];
				if ( $days > 0 ) {
					// translators: %d is number of business days in delivery estimate.
					$delivery_txt = sprintf( _n( '%d business day', '%d business days', $days, 'table-rate-shipping-pro' ), $days );
					$transit_days = $days;
				}
			}

			$label = $this->resolve_service_label( $service_desc, $service_code );

			$converted = $this->convert_currency( $amount, $currency, $store_currency );
			if ( false === $converted ) {
				// Skip if conversion failed for this line.
				continue;
			}

			$rates[] = new Rate(
				$this->get_key(),
				$service_code,
				$label,
				$converted,
				$store_currency,
				$delivery_txt,
				$transit_days
			);
		}

		return $rates;
	}

	/*
	 ***************************************************
	 * HTTP helper
	 ***************************************************
	*/
	/**
	 * Perform a remote HTTP JSON request with optional Bearer token.
	 *
	 * @param string      $method    HTTP method (GET|POST|...).
	 * @param string      $url       Target URL.
	 * @param array       $body      Request body (assoc array) prior to json_encode.
	 * @param string|null $token     Bearer token, if any.
	 * @param bool        $send_body Whether to send the body for non-GET methods.
	 * @return array|WP_Error Decoded JSON array or WP_Error on failure.
	 */
	private function remote_json( string $method, string $url, array $body, ?string $token, bool $send_body = true ) {
		$args = array(
			'method'      => $method,
			'timeout'     => 70,
			'headers'     => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
				'User-Agent'   => 'WowShipping/' . ( defined( 'WTRS_VER' ) ? WTRS_VER : 'dev' ),
			),
			'sslverify'   => false,
			'data_format' => 'body',
		);
		if ( $token ) {
			$args['headers']['Authorization'] = 'Bearer ' . $token;
		}
		if ( 'GET' !== $method && $send_body && ! empty( $body ) ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $url, $args );
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		$code = wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$dec  = json_decode( $raw, true );
		if ( 200 !== $code ) {
			return new WP_Error(
				'http_error',
				'UPS HTTP error',
				array(
					'status' => $code,
					'body'   => $dec,
				)
			);
		}
		return is_array( $dec ) ? $dec : array();
	}

	/*
	 ***************************************************
	 * Validation
	 ***************************************************
	*/
	/**
	 * Validate and persist UPS OAuth credentials.
	 * Accepts either { client_id, client_secret } or { username, password } keys.
	 *
	 * @param array $credentials Credentials.
	 * @return bool True on success.
	 */
	public function validate( array $credentials ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$client_id      = $credentials['client_id'] ?? $credentials['username'] ?? '';
		$client_secret  = $credentials['client_secret'] ?? $credentials['password'] ?? '';
		$account_number = $credentials['account_number'] ?? '';
		if ( ! $client_id || ! $client_secret || ! $account_number ) {
			return false;
		}

		// Attempt to get a token with provided credentials.
		$url  = $this->host() . self::EP_TOKEN;
		$args = array(
			'method'      => 'POST',
			'timeout'     => 40,
			'headers'     => array(
				'Content-Type'  => 'application/x-www-form-urlencoded',
				'x-merchant-id' => $client_id,
				'Authorization' => 'Basic ' . base64_encode( $client_id . ':' . $client_secret ), // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required by UPS OAuth Basic Auth header.
				'User-Agent'    => 'WowShipping/' . ( defined( 'WTRS_VER' ) ? WTRS_VER : 'dev' ),
			),
			'body'        => 'grant_type=client_credentials',
			'sslverify'   => false,
			'data_format' => 'body',
		);

		$resp = wp_remote_request( $url, $args );
		if ( is_wp_error( $resp ) ) {
			return false;
		}
		$code   = wp_remote_retrieve_response_code( $resp );
		$raw    = wp_remote_retrieve_body( $resp );
		$parsed = json_decode( $raw, true );
		if ( 200 !== $code || empty( $parsed['access_token'] ) ) {
			return false;
		}

		// Persist credentials.
		if ( $this->credential_manager ) {
			$this->credential_manager->bulk_set(
				array(
					'client_id'      => $client_id,
					'client_secret'  => $client_secret,
					'account_number' => sanitize_text_field( $account_number ),
				)
			);
		}
		$key = self::TOKEN_TRANSIENT . ( $this->is_live() ? 'live' : 'test' );
		$ttl = isset( $parsed['expires_in'] ) ? max( 60, (int) $parsed['expires_in'] - 60 ) : self::TOKEN_TTL;
		set_transient( $key, $parsed['access_token'], $ttl );

		return true;
	}
}
