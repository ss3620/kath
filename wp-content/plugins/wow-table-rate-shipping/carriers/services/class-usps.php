<?php // phpcs:ignore
namespace WTRS\Carriers\Services;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WTRS\Carriers\AbstractCarrier;
use WTRS\Carriers\DTO\ShipmentRequest;
use WTRS\Carriers\DTO\Rate;

/**
 * USPS Carrier (OAuth JSON Rates)
 *
 * This implementation focuses only on the modern USPS OAuth JSON endpoints, intentionally
 * ignoring deprecated XML APIs. It follows the structural patterns of other carrier
 * classes (e.g. Sendle) to provide normalized Rate objects.
 */
final class Usps extends AbstractCarrier {

	private const LIVE_HOST = 'https://apis.usps.com';
	private const TEST_HOST = 'https://apis-tem.usps.com';

	private const EP_DOMESTIC_RATES    = '/prices/v3/total-rates/search';
	private const EP_INTL_RATES        = '/international-prices/v3/base-rates-list/search';
	private const EP_TOKEN             = '/oauth2/v3/token';
	private const EP_SERVICE_STANDARDS = '/service-standards/v3/standards'; // live.
	private const EP_SERVICE_ESTIMATES = '/service-standards/v3/estimates';  // sandbox.

	/** Token transient key */
	private const TOKEN_TRANSIENT = 'wtrs_usps_token';

	private const TOKEN_TTL = 5 * 60 * 60;

	/** Default weight & dimension fallback (imperial). */
	private const DEFAULT_LB = 1.0;

	private const VALUE_DELIM     = '#|#';
	private const DEFAULT_SERVICE = 'D_GROUND_ADVANTAGE';

	/**
	 * USPS service families exposed in the UI and matched against API mailClass values.
	 */
	private const SERVICE_DEFINITIONS = array(
		'D_GROUND_ADVANTAGE'      => array(
			'label'         => 'USPS Ground Advantage | Domestic',
			'domestic'      => array( 'USPS_GROUND_ADVANTAGE' ),
			'international' => array(),
		),
		'D_FIRST_CLASS_MAIL'      => array(
			'label'         => 'First Class Mail | Domestic',
			'domestic'      => array( 'FIRST_CLASS_MAIL', 'USPS_GROUND_ADVANTAGE' ),
			'international' => array(),
		),
		'D_EXPRESS_MAIL'          => array(
			'label'         => 'Priority Mail Express | Domestic',
			'domestic'      => array( 'PRIORITY_MAIL_EXPRESS' ),
			'international' => array(),
		),
		'D_PRIORITY_MAIL'         => array(
			'label'         => 'Priority Mail | Domestic',
			'domestic'      => array( 'PRIORITY_MAIL' ),
			'international' => array(),
		),
		'D_MEDIA_MAIL'            => array(
			'label'         => 'Media Mail | Domestic',
			'domestic'      => array( 'MEDIA_MAIL' ),
			'international' => array(),
		),
		'D_LIBRARY_MAIL'          => array(
			'label'         => 'Library Mail | Domestic',
			'domestic'      => array( 'LIBRARY_MAIL' ),
			'international' => array(),
		),
		'D_BOUND_PRINTED_MATTER'  => array(
			'label'         => 'Bound Printed Matter | Domestic',
			'domestic'      => array( 'BOUND_PRINTED_MATTER' ),
			'international' => array(),
		),
		'D_USPS_CONNECT_REGIONAL' => array(
			'label'         => 'USPS Connect Regional | Domestic',
			'domestic'      => array( 'PARCEL_SELECT', 'USPS_CONNECT_REGIONAL' ),
			'international' => array(),
		),
		'D_USPS_CONNECT_MAIL'     => array(
			'label'         => 'USPS Connect Mail | Domestic',
			'domestic'      => array( 'USPS_CONNECT_MAIL' ),
			'international' => array(),
		),
		'D_USPS_CONNECT_LOCAL'    => array(
			'label'         => 'USPS Connect Local | Domestic',
			'domestic'      => array( 'USPS_CONNECT_LOCAL' ),
			'international' => array(),
		),
		'I_EXPRESS_MAIL'          => array(
			'label'         => 'Priority Mail Express | International',
			'domestic'      => array(),
			'international' => array( 'PRIORITY_MAIL_EXPRESS_INTERNATIONAL' ),
		),
		'I_PRIORITY_MAIL'         => array(
			'label'         => 'Priority Mail | International',
			'domestic'      => array(),
			'international' => array( 'PRIORITY_MAIL_INTERNATIONAL' ),
		),
		'I_FIRST_CLASS_PACKAGE'   => array(
			'label'         => 'First-Class Package | International',
			'domestic'      => array(),
			'international' => array( 'FIRST_CLASS_PACKAGE_INTERNATIONAL_SERVICE' ),
		),
	);

	private const EXTRA_SERVICES = array(
		array(
			'value' => '415',
			'label' => 'USPS Label Delivery Service',
		),
		array(
			'value' => '480',
			'label' => 'Tracking Plus 6 Months',
		),
		array(
			'value' => '481',
			'label' => 'Tracking Plus 1 Year',
		),
		array(
			'value' => '482',
			'label' => 'Tracking Plus 3 Years',
		),
		array(
			'value' => '483',
			'label' => 'Tracking Plus 5 Years',
		),
		array(
			'value' => '484',
			'label' => 'Tracking Plus 7 Years',
		),
		array(
			'value' => '485',
			'label' => 'Tracking Plus 10 Years',
		),
		array(
			'value' => '486',
			'label' => 'Tracking Plus Signature 3 Years',
		),
		array(
			'value' => '487',
			'label' => 'Tracking Plus Signature 5 Years',
		),
		array(
			'value' => '488',
			'label' => 'Tracking Plus Signature 7 Years',
		),
		array(
			'value' => '489',
			'label' => 'Tracking Plus Signature 10 Years',
		),
		array(
			'value' => '498',
			'label' => 'PO Box Locker – Stocking Fee (NSA Only)',
		),
		array(
			'value' => '500',
			'label' => 'PO Box Locker – Self-Service Pickup Fee (NSA Only)',
		),
		array(
			'value' => '501',
			'label' => 'PO Box Locker – Clerk-Assisted Pickup Fee (NSA Only)',
		),
		array(
			'value' => '502',
			'label' => 'PO Box Locker – Local Delivery Fee (NSA Only)',
		),
		array(
			'value' => '810',
			'label' => 'Hazardous Materials - Air Eligible Ethanol',
		),
		array(
			'value' => '811',
			'label' => 'Hazardous Materials - Class 1 – Toy Propellant/Safety Fuse Package',
		),
		array(
			'value' => '812',
			'label' => 'Hazardous Materials - Class 3 - Flammable and Combustible Liquids',
		),
		array(
			'value' => '813',
			'label' => 'Hazardous Materials - Class 7 – Radioactive Materials',
		),
		array(
			'value' => '814',
			'label' => 'Hazardous Materials - Class 8 – Air Eligible Corrosive Materials',
		),
		array(
			'value' => '815',
			'label' => 'Hazardous Materials - Class 8 – Nonspillable Wet Batteries',
		),
		array(
			'value' => '816',
			'label' => 'Hazardous Materials - Class 9 - Lithium Battery Marked Ground Only',
		),
		array(
			'value' => '817',
			'label' => 'Hazardous Materials - Class 9 - Lithium Battery Returns',
		),
		array(
			'value' => '818',
			'label' => 'Hazardous Materials - Class 9 - Marked Lithium Batteries',
		),
		array(
			'value' => '819',
			'label' => 'Hazardous Materials - Class 9 – Dry Ice',
		),
		array(
			'value' => '820',
			'label' => 'Hazardous Materials - Class 9 – Unmarked Lithium Batteries',
		),
		array(
			'value' => '821',
			'label' => 'Hazardous Materials - Class 9 – Magnetized Materials',
		),
		array(
			'value' => '822',
			'label' => 'Hazardous Materials - Division 4.1 – Mailable Flammable Solids and Safety Matches',
		),
		array(
			'value' => '823',
			'label' => 'Hazardous Materials - Division 5.1 – Oxidizers',
		),
		array(
			'value' => '824',
			'label' => 'Hazardous Materials - Division 5.2 – Organic Peroxides',
		),
		array(
			'value' => '825',
			'label' => 'Hazardous Materials - Division 6.1 – Toxic Materials',
		),
		array(
			'value' => '826',
			'label' => 'Hazardous Materials - Division 6.2 Biological Materials',
		),
		array(
			'value' => '827',
			'label' => 'Hazardous Materials - Excepted Quantity Provision',
		),
		array(
			'value' => '828',
			'label' => 'Hazardous Materials - Ground Only Hazardous Materials',
		),
		array(
			'value' => '829',
			'label' => 'Hazardous Materials - Air Eligible ID8000 Consumer Commodity',
		),
		array(
			'value' => '830',
			'label' => 'Hazardous Materials - Lighters',
		),
		array(
			'value' => '831',
			'label' => 'Hazardous Materials - Limited Quantity Ground',
		),
		array(
			'value' => '832',
			'label' => 'Hazardous Materials - Small Quantity Provision (Markings Required)',
		),
		array(
			'value' => '853',
			'label' => 'Special Handling - Perishable Material',
		),
		array(
			'value' => '856',
			'label' => 'Live Animal Transportation Fee',
		),
		array(
			'value' => '857',
			'label' => 'Hazardous Materials',
		),
		array(
			'value' => '858',
			'label' => 'Cremated Remains',
		),
		array(
			'value' => '910',
			'label' => 'Certified Mail',
		),
		array(
			'value' => '911',
			'label' => 'Certified Mail Restricted Delivery',
		),
		array(
			'value' => '912',
			'label' => 'Certified Mail Adult Signature Required',
		),
		array(
			'value' => '913',
			'label' => 'Certified Mail Adult Signature Restricted Delivery',
		),
		array(
			'value' => '915',
			'label' => 'Collect on Delivery',
		),
		array(
			'value' => '917',
			'label' => 'Collect on Delivery Restricted Delivery',
		),
		array(
			'value' => '920',
			'label' => 'USPS Tracking Electronic',
		),
		array(
			'value' => '921',
			'label' => 'Signature Confirmation',
		),
		array(
			'value' => '922',
			'label' => 'Adult Signature Required',
		),
		array(
			'value' => '923',
			'label' => 'Adult Signature Restricted Delivery',
		),
		array(
			'value' => '924',
			'label' => 'Signature Confirmation Restricted Delivery',
		),
		array(
			'value' => '925',
			'label' => 'Priority Mail Express Merchandise Insurance',
		),
		array(
			'value' => '930',
			'label' => 'Insurance <= $500',
		),
		array(
			'value' => '931',
			'label' => 'Insurance > $500',
		),
		array(
			'value' => '934',
			'label' => 'Insurance Restricted Delivery',
		),
		array(
			'value' => '940',
			'label' => 'Registered Mail',
		),
		array(
			'value' => '941',
			'label' => 'Registered Mail Restricted Delivery',
		),
		array(
			'value' => '955',
			'label' => 'Return Receipt',
		),
		array(
			'value' => '957',
			'label' => 'Return Receipt Electronic',
		),
		array(
			'value' => '972',
			'label' => 'Live Animal and Perishable Handling Fee',
		),
		array(
			'value' => '981',
			'label' => 'Signature Requested (PRIORITY_MAIL_EXPRESS only)',
		),
		array(
			'value' => '984',
			'label' => 'Parcel Locker Delivery',
		),
		array(
			'value' => '986',
			'label' => 'PO to Addressee (PRIORITY_MAIL_EXPRESS only)',
		),
		array(
			'value' => '991',
			'label' => 'Sunday Delivery',
		),
	);

	/**
	 * Supported price types.
	 */
	private const PRICE_TYPES = array(
		array(
			'label' => 'Commercial',
			'value' => 'COMMERCIAL',
		),
		array(
			'label' => 'Retail',
			'value' => 'RETAIL',
		),
	);

	/*
	 ***************************************************
	 * Required interface methods
	 ***************************************************
	*/

	/**
	 * Unique carrier key.
	 *
	 * @return string
	 */
	public static function get_key(): string {
		return 'usps';
	}

	/**
	 * Human readable label.
	 *
	 * @return string
	 */
	public static function get_label(): string {
		return 'USPS';
	}

	/**
	 * Supported packers.
	 *
	 * @return array
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
			$services = array();
			foreach ( self::SERVICE_DEFINITIONS as $value => $definition ) {
				$services[] = array(
					'label' => $definition['label'],
					'value' => $value,
				);
			}
			return $services;
		}

		if ( 'priceTypes' === $type ) {
			return self::PRICE_TYPES;
		}

		if ( 'extraServices' === $type ) {
			return self::EXTRA_SERVICES;
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

		$parsed['mode']          = in_array( $values['mode'] ?? 'sandbox', array( 'sandbox', 'live' ), true ) ? $values['mode'] : 'sandbox';
		$parsed['priceType']     = in_array( $values['priceType'] ?? 'COMMERCIAL', array( 'COMMERCIAL', 'RETAIL' ), true ) ? $values['priceType'] : 'COMMERCIAL';
		$parsed['enableEta']     = ! empty( $values['enableEta'] );
		$parsed['extraServices'] = array();

		if ( isset( $values['extraServices'] ) && is_array( $values['extraServices'] ) ) {
			foreach ( $values['extraServices'] as $svc ) {
				$parsed['extraServices'][] = intval( trim( $svc ) );
			}
		}

		if ( isset( $values['service'] ) && is_string( $values['service'] ) && '' !== trim( $values['service'] ) ) {
			$parsed['service'] = trim( $values['service'] );
		} else {
			$parsed['service'] = self::DEFAULT_SERVICE;
		}

		return array_merge( parent::parse_settings( $values ), $parsed );
	}

	/** Determine environment. */
	protected function is_live(): bool {
		return ( $this->settings['mode'] ?? 'sandbox' ) === 'live';
	}

	/** Base host. */
	private function host(): string {
		return $this->is_live() ? self::LIVE_HOST : self::TEST_HOST;
	}

	/*
	 ***************************************************
	 * Public API
	 ***************************************************
	*/
	/**
	 * Fetch normalized USPS rates.
	 *
	 * @param ShipmentRequest $request Shipment abstraction.
	 * @return array<Rate>|false
	 */
	public function fetch_rates( ShipmentRequest $request ) {
		$this->log( 'fetch_rates_started', 'Starting USPS rate request' );

		// Sanity: credentials.
		if ( ! $this->get_secret( 'client_id' ) || ! $this->get_secret( 'client_secret' ) ) {
			$this->error( 'missing_credentials', __( 'USPS credentials not set.', 'table-rate-shipping-pro' ) );
			return false;
		}

		$domestic = $request->origin_location['country'] === $request->destination_location['country'];

		$this->log( 'shipment_type', 'Determined shipment type', array( 'domestic' => $domestic ) );

		$packages = $this->map_packages( $request );
		if ( empty( $packages ) ) {
			$this->warning( 'no_packages', 'No packages to rate' );
			return array();
		}

		$token = $this->get_token();
		if ( ! $token ) {
			$this->error( 'auth_failed', __( 'Unable to authenticate with USPS API.', 'table-rate-shipping-pro' ) );
			return false;
		}

		$this->log( 'token_obtained', 'USPS access token obtained successfully' );

		$endpoint = $domestic ? self::EP_DOMESTIC_RATES : self::EP_INTL_RATES;

		$this->log( 'api_endpoint', 'Using USPS endpoint', array( 'endpoint' => $endpoint ) );

		$this->log(
			'selected_service_setting',
			'Selected USPS service setting',
			array(
				'selected' => $this->settings['service'] ?? '',
			)
		);

		$responses = array();
		foreach ( $packages as $pkg ) {
			$payload = $this->build_rate_payload( $pkg, $request, $domestic );
			$payload = apply_filters( 'wtrs_usps_rate_payload', $payload, $pkg, $request, $this );

			$this->log( 'sending_request', $payload );

			$response = $this->remote_json( 'POST', $this->host() . $endpoint, $payload, $token );
			if ( is_wp_error( $response ) ) {
				$this->error( 'api_error', $response );
				return false;
			}
			$responses[] = array(
				'body'    => $response,
				'package' => $pkg,
			);
		}

		$this->log( 'USPS Rate Responses', 'Received summarized responses from USPS', $this->summarize_rate_responses( $responses ) );

		$eta_map = array();
		if ( $domestic && ! empty( $this->settings['enableEta'] ) ) {
			$this->log( 'fetching_eta', 'Fetching service standards (ETA)' );
			$eta_map = $this->fetch_service_standards( $request, $packages, $token );
			$this->log( 'USPS ETA API', 'Received ETA data', $eta_map );
		}

		$rates = $this->aggregate_responses( $responses, $eta_map, $request, $domestic );

		if ( false === $rates ) {
			$this->warning( 'aggregation_failed', 'Failed to aggregate USPS rates' );
			return false;
		}
		if ( empty( $rates ) ) {
			$this->warning( 'no_rates_returned', 'No rates returned from USPS' );
			return array();
		}

		$this->log( 'rates_fetched', 'USPS rates fetched successfully', array( 'final_count' => count( $rates ) ) );

		return apply_filters( 'wtrs_carrier_filter_normalized_rates', $rates, $this->get_key(), $request );
	}

	/*
	 ***************************************************
	 * Token Handling
	 ***************************************************
	*/
	/**
	 * Retrieve cached token or request a new one.
	 *
	 * @return string|null
	 */
	private function get_token(): ?string {
		$cached = get_transient( self::TOKEN_TRANSIENT );
		if ( $cached ) {
			$this->log( 'token_from_cache', 'Using cached USPS access token' );
			return $cached;
		}
		$this->log( 'requesting_token', 'Requesting new USPS access token' );
		return $this->request_token();
	}

	/**
	 * Request a fresh OAuth token from USPS.
	 *
	 * @return string|null
	 */
	private function request_token(): ?string {
		$body     = array(
			'client_id'     => $this->get_secret( 'client_id' ),
			'client_secret' => $this->get_secret( 'client_secret' ),
			'grant_type'    => 'client_credentials',
		);
		$response = $this->remote_json( 'POST', $this->host() . self::EP_TOKEN, $body, null, true );
		if ( is_wp_error( $response ) ) {
			$this->error( 'token_request_failed', $response );
			return null;
		}
		if ( ( $response['status'] ?? '' ) === 'approved' && ! empty( $response['access_token'] ) ) {
			$this->log( 'token_received', 'USPS access token received successfully' );
			set_transient( self::TOKEN_TRANSIENT, $response['access_token'], self::TOKEN_TTL );
			return $response['access_token'];
		}
		$error = new \WP_Error( 'token_status_error', 'Token request not approved', $response );
		$this->error( 'token_status_error', $error );
		return null;
	}

	/*
	 ***************************************************
	 * Package Mapping & Payload
	 ***************************************************
	*/
	/**
	 * Convert shipment packages to simplified USPS package arrays.
	 *
	 * @param ShipmentRequest $request Shipment request.
	 * @return array
	 */
	private function map_packages( ShipmentRequest $request ): array {
		$out = array();
		foreach ( $request->packages as $idx => $pkg ) {
			// Package weights are normalized to kg and dimensions to cm by the DTOs.
			// USPS total-rates expects pounds and inches.
			$raw_weight_kg = max( 0.0, (float) $pkg->total_weight );
			$raw_length_cm = max( 0.0, (float) $pkg->packed_dimensions->length );
			$raw_width_cm  = max( 0.0, (float) $pkg->packed_dimensions->width );
			$raw_height_cm = max( 0.0, (float) $pkg->packed_dimensions->height );

			$weight_lb = function_exists( 'wc_get_weight' ) ? (float) wc_get_weight( $raw_weight_kg, 'lbs', 'kg' ) : ( $raw_weight_kg / 0.45359237 );
			if ( $weight_lb <= 0 ) {
				$weight_lb = self::DEFAULT_LB;
			}

			if ( function_exists( 'wc_get_dimension' ) ) {
				$length_in = (float) wc_get_dimension( $raw_length_cm, 'in', 'cm' );
				$width_in  = (float) wc_get_dimension( $raw_width_cm, 'in', 'cm' );
				$height_in = (float) wc_get_dimension( $raw_height_cm, 'in', 'cm' );
			} else {
				$length_in = $raw_length_cm / 2.54;
				$width_in  = $raw_width_cm / 2.54;
				$height_in = $raw_height_cm / 2.54;
			}

			$mapped = array(
				'index'  => $idx,
				'weight' => (float) $weight_lb,
				'length' => round( max( 0, $length_in ), 3 ),
				'width'  => round( max( 0, $width_in ), 3 ),
				'height' => round( max( 0, $height_in ), 3 ),
				'qty'    => 1, // Each package is aggregated already by packer; adapt if multi-qty needed.
			);

			$this->log(
				'package_mapped',
				'Mapped USPS package dimensions',
				array(
					'package_index'         => $idx,
					'source_dimension_unit' => 'cm',
					'source_dimensions'     => array(
						'length' => $raw_length_cm,
						'width'  => $raw_width_cm,
						'height' => $raw_height_cm,
					),
					'outbound_dimensions'   => array(
						'length' => $mapped['length'],
						'width'  => $mapped['width'],
						'height' => $mapped['height'],
					),
					'outbound_weight_lbs'   => $mapped['weight'],
				)
			);

			$out[] = $mapped;
		}
		return $out;
	}

	/**
	 * Build USPS rate payload for a single package.
	 *
	 * @param array           $pkg Package array.
	 * @param ShipmentRequest $request Shipment request.
	 * @param bool            $domestic Is domestic shipment.
	 * @return array
	 */
	private function build_rate_payload( array $pkg, ShipmentRequest $request, bool $domestic ): array {
		$origin       = $request->origin_location;
		$destination  = $request->destination_location;
		$mailing_date = gmdate( 'Y-m-d' ); // Could mirror existing cut-off + working day logic if exposed later.

		if ( $domestic ) {
			return array(
				'originZIPCode'      => strtoupper( substr( preg_replace( '/\s+/', '', $origin['postcode'] ), 0, 5 ) ),
				'destinationZIPCode' => strtoupper( substr( preg_replace( '/\s+/', '', $destination['postcode'] ), 0, 5 ) ),
				'weight'             => $pkg['weight'],
				'length'             => $pkg['length'],
				'width'              => $pkg['width'],
				'height'             => $pkg['height'],
				'mailClass'          => 'ALL',
				'priceType'          => $this->settings['priceType'] ?? 'COMMERCIAL',
				'mailingDate'        => $mailing_date,
				'extraServices'      => $this->get_setting( 'extraServices', array() ),
			);
		}
		return array(
			'originZIPCode'          => strtoupper( substr( preg_replace( '/\s+/', '', $origin['postcode'] ), 0, 5 ) ),
			'foreignPostalCode'      => strtoupper( substr( preg_replace( '/\s+/', '', $destination['postcode'] ), 0, 5 ) ),
			'destinationCountryCode' => strtoupper( $destination['country'] ),
			'mailingDate'            => $mailing_date,
			'mailClass'              => 'ALL',
			'weight'                 => $pkg['weight'],
			'length'                 => $pkg['length'],
			'width'                  => $pkg['width'],
			'height'                 => $pkg['height'],
			'extraServices'          => $this->get_setting( 'extraServices', array() ),
		);
	}

	/**
	 * Fetch service standards (ETA) for domestic shipments.
	 *
	 * @param ShipmentRequest $request Shipment request.
	 * @param array           $packages Packages array.
	 * @param string          $token OAuth token.
	 * @return array Map of mailClass => ISO date.
	 */
	private function fetch_service_standards( ShipmentRequest $request, array $packages, string $token ): array {
		$total_weight = array_sum(
			array_map(
				function ( $p ) {
					return $p['weight'];
				},
				$packages
			)
		);
		$origin       = $request->origin_location;
		$dest         = $request->destination_location;
		$accept       = gmdate( 'Y-m-d' );

		$query = http_build_query(
			array(
				'originZIPCode'                => strtoupper( substr( preg_replace( '/\s+/', '', $origin['postcode'] ), 0, 5 ) ),
				'destinationZIPCode'           => strtoupper( substr( preg_replace( '/\s+/', '', $dest['postcode'] ), 0, 5 ) ),
				'acceptanceDate'               => $accept,
				'mailClass'                    => 'ALL',
				'destinationType'              => 'STREET',
				'destinationEntryFacilityType' => 'NONE',
				'weight'                       => $total_weight,
			)
		);

		$path = $this->is_live() ? self::EP_SERVICE_STANDARDS : self::EP_SERVICE_ESTIMATES;
		$url  = $this->host() . $path . '?' . $query;

		$response = $this->remote_json( 'GET', $url, array(), $token );
		if ( is_wp_error( $response ) || empty( $response ) ) {
			return array();
		}
		$map = array();
		foreach ( (array) $response as $row ) {
			if ( isset( $row['mailClass'], $row['delivery']['scheduledDeliveryDateTime'] ) ) {
				$map[ $row['mailClass'] ] = $row['delivery']['scheduledDeliveryDateTime'];
			}
		}
		return apply_filters( 'wtrs_usps_eta_map', $map, $request, $this );
	}

	/**
	 * Aggregate multiple package responses into a single Rate object.
	 *
	 * @param array           $responses Response list.
	 * @param array           $eta_map ETA map.
	 * @param ShipmentRequest $request Shipment request.
	 * @param bool            $domestic Domestic flag.
	 * @return array|false Array with one Rate on success, false if no match.
	 */
	private function aggregate_responses( array $responses, array $eta_map, ShipmentRequest $request, bool $domestic ) {

		$selected            = $this->settings['service'] ?? '';
		$resolved            = $this->resolve_selected_service( $selected );
		$target_group        = $resolved['group'];
		$target_label        = $resolved['label'];
		$target_mail_classes = $this->get_service_mail_classes( $target_group, $domestic );

		$this->log(
			'selected_service',
			'Resolved USPS selected service',
			array(
				'selected'     => $selected,
				'group'        => $target_group,
				'label'        => $target_label,
				'mail_classes' => $target_mail_classes,
			)
		);

		$package_count = count( $responses );
		$matched       = $this->collect_matching_rates( $responses, $target_mail_classes );

		if ( null === $matched && self::DEFAULT_SERVICE !== $target_group ) {
			$this->warning(
				'service_not_matched',
				'USPS returned rates, but none matched the configured service. Falling back to Ground Advantage.',
				array(
					'selected'     => $selected,
					'group'        => $target_group,
					'mail_classes' => $target_mail_classes,
				)
			);

			$target_group        = self::DEFAULT_SERVICE;
			$target_label        = $this->get_service_group_label( self::DEFAULT_SERVICE );
			$target_mail_classes = $this->get_service_mail_classes( self::DEFAULT_SERVICE, $domestic );
			$matched             = $this->collect_matching_rates( $responses, $target_mail_classes );
		}

		if ( null === $matched ) {
			return false;
		}

		$matched_prices    = $matched['prices'];
		$matched_classes   = $matched['classes'];
		$first_description = $matched['description'];

		if ( count( $matched_prices ) !== $package_count ) {
			return false;
		}

		$total_cost = array_sum( $matched_prices );
		if ( $target_label ) {
			$label = $target_label;
		} elseif ( $first_description ) {
			$label = $first_description;
		} else {
			$label = 'USPS Shipping';
		}

		// Determine maximum transit days among matched mailClasses.
		$delivery_txt = null;
		$transit_days = null;
		if ( ! empty( $eta_map ) && ! empty( $matched_classes ) ) {
			$max_days = 0;
			foreach ( array_keys( $matched_classes ) as $mc ) {
				if ( ! isset( $eta_map[ $mc ] ) ) {
					continue;
				}
				try { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					$target = new \DateTime( $eta_map[ $mc ] );
					$now    = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
					$diff   = (int) ceil( ( $target->getTimestamp() - $now->getTimestamp() ) / 86400 );
					if ( $diff > $max_days ) {
						$max_days = $diff;
					}
				} catch ( \Exception $e ) { } // phpcs:ignore
			}
			if ( $max_days > 0 ) {
				// translators: %d is number of business days in delivery estimate (maximum across packages).
				$delivery_txt = sprintf( _n( '%d business day', '%d business days', $max_days, 'table-rate-shipping-pro' ), $max_days );
				$transit_days = $max_days;
			}
		}

		$currency  = $request->currency;
		$converted = $this->convert_currency( $total_cost, 'USD', $currency );
		if ( false === $converted ) {
			return false;
		}

		$service_key = $target_group ? $target_group : self::DEFAULT_SERVICE;
		$rate        = new Rate(
			$this->get_key(),
			$service_key,
			$label,
			$converted,
			$currency,
			$delivery_txt,
			$transit_days
		);

		return array( $rate );
	}

	/**
	 * Collect matching rates for every package response.
	 *
	 * @param array $responses Response list.
	 * @param array $mail_classes Allowed mail classes.
	 * @return array|null
	 */
	private function collect_matching_rates( array $responses, array $mail_classes ): ?array {
		$matched_prices    = array();
		$matched_classes   = array();
		$first_description = '';

		foreach ( $responses as $resp ) {
			$body  = $resp['body'];
			$pkg   = $resp['package'];
			$index = $pkg['index'];
			if ( empty( $body['rateOptions'] ) || ! is_array( $body['rateOptions'] ) ) {
				return null;
			}

			$matched_candidate = $this->find_rate_candidate( $body['rateOptions'], $mail_classes );
			if ( null === $matched_candidate ) {
				return null;
			}

			$matched_prices[ $index ] = $matched_candidate['price'];
			if ( $matched_candidate['mail_class'] ) {
				$matched_classes[ $matched_candidate['mail_class'] ] = true;
			}
			if ( ! $first_description && $matched_candidate['description'] ) {
				$first_description = $matched_candidate['description'];
			}
		}

		return array(
			'prices'      => $matched_prices,
			'classes'     => $matched_classes,
			'description' => $first_description,
		);
	}

	/**
	 * Build a compact debug summary of USPS rate responses.
	 *
	 * @param array $responses Response list.
	 * @return array
	 */
	private function summarize_rate_responses( array $responses ): array {
		$summary = array(
			'package_count' => count( $responses ),
			'packages'      => array(),
		);

		foreach ( $responses as $resp ) {
			$body         = $resp['body'];
			$pkg          = $resp['package'];
			$rate_options = ( isset( $body['rateOptions'] ) && is_array( $body['rateOptions'] ) ) ? $body['rateOptions'] : array();
			$package      = array(
				'package_index' => $pkg['index'] ?? null,
				'rate_count'    => count( $rate_options ),
				'rates'         => array(),
			);

			foreach ( $rate_options as $opt ) {
				$rate_line = $opt['rates'][0] ?? null;
				if ( ! is_array( $rate_line ) ) {
					continue;
				}

				$package['rates'][] = array(
					'title'                => (string) ( $rate_line['description'] ?? '' ),
					'mailClass'            => (string) ( $rate_line['mailClass'] ?? '' ),
					'price'                => isset( $rate_line['price'] ) ? (float) $rate_line['price'] : null,
					'SKU'                  => (string) ( $rate_line['SKU'] ?? '' ),
					'productName'          => (string) ( $rate_line['productName'] ?? '' ),
					'processingCategory'   => (string) ( $rate_line['processingCategory'] ?? '' ),
					'extraServiceCount'    => isset( $opt['extraServices'] ) && is_array( $opt['extraServices'] ) ? count( $opt['extraServices'] ) : 0,
				);
			}

			$summary['packages'][] = $package;
		}

		return $summary;
	}

	/**
	 * Map stored service groups to USPS mailClass values returned by the Prices API.
	 *
	 * @param string $group Selected service group.
	 * @param bool   $domestic Domestic shipment flag.
	 * @return array
	 */
	private function get_service_mail_classes( string $group, bool $domestic ): array {
		$key = $domestic ? 'domestic' : 'international';
		return self::SERVICE_DEFINITIONS[ $group ][ $key ] ?? array();
	}

	/**
	 * Resolve stored service settings from current, old compound, or malformed values.
	 *
	 * @param string $selected Stored selected service.
	 * @return array{group:string,label:string}
	 */
	private function resolve_selected_service( string $selected ): array {
		$selected = trim( $selected );

		if ( isset( self::SERVICE_DEFINITIONS[ $selected ] ) ) {
			return $this->build_service_resolution( $selected );
		}

		if ( strpos( $selected, self::VALUE_DELIM ) !== false ) {
			list( $group ) = explode( self::VALUE_DELIM, $selected, 2 );
			if ( isset( self::SERVICE_DEFINITIONS[ $group ] ) ) {
				return $this->build_service_resolution( $group );
			}
		}

		return $this->build_service_resolution( self::DEFAULT_SERVICE );
	}

	/**
	 * Return the UI label for a simplified service group.
	 *
	 * @param string $group Service group key.
	 * @return string
	 */
	private function get_service_group_label( string $group ): string {
		return self::SERVICE_DEFINITIONS[ $group ]['label'] ?? '';
	}

	/**
	 * Build a normalized service resolution payload.
	 *
	 * @param string $group Service group key.
	 * @return array{group:string,label:string}
	 */
	private function build_service_resolution( string $group ): array {
		return array(
			'group' => $group,
			'label' => $this->get_service_group_label( $group ),
		);
	}

	/**
	 * Find the cheapest USPS rate option for the allowed mail classes.
	 *
	 * @param array $rate_options USPS rateOptions array.
	 * @param array $mail_classes Allowed mail classes.
	 * @return array|null
	 */
	private function find_rate_candidate( array $rate_options, array $mail_classes ): ?array {
		if ( empty( $mail_classes ) ) {
			return null;
		}

		$matched_candidate = null;
		$extra_services    = $this->get_setting( 'extraServices', array() );

		foreach ( $rate_options as $opt ) {
			$rate_line = $opt['rates'][0] ?? null;
			if ( ! is_array( $rate_line ) ) {
				continue;
			}

			$price = isset( $rate_line['price'] ) ? (float) $rate_line['price'] : null;
			if ( null === $price ) {
				continue;
			}

			$mail_class = (string) ( $rate_line['mailClass'] ?? '' );
			if ( ! in_array( $mail_class, $mail_classes, true ) ) {
				continue;
			}

			$price = $this->apply_extra_service_prices( $price, $opt, $extra_services );

			if ( null === $matched_candidate || $price < $matched_candidate['price'] ) {
				$matched_candidate = array(
					'price'       => $price,
					'mail_class'  => $mail_class,
					'description' => (string) ( $rate_line['description'] ?? '' ),
				);
			}
		}

		return $matched_candidate;
	}

	/**
	 * Add selected extra service prices to a USPS base rate.
	 *
	 * @param float $price Base price.
	 * @param array $rate_option USPS rate option.
	 * @param array $extra_services Selected extra service IDs.
	 * @return float
	 */
	private function apply_extra_service_prices( float $price, array $rate_option, array $extra_services ): float {
		if ( empty( $extra_services ) || empty( $rate_option['extraServices'] ) || ! is_array( $rate_option['extraServices'] ) ) {
			return $price;
		}

		foreach ( $rate_option['extraServices'] as $extra ) {
			if ( in_array( (int) ( $extra['extraService'] ?? 0 ), $extra_services, true ) && isset( $extra['price'] ) ) {
				$price += (float) $extra['price'];
			}
		}

		return $price;
	}

	/**
	 * Perform remote HTTP JSON request.
	 *
	 * @param string      $method HTTP method.
	 * @param string      $url Target URL.
	 * @param array       $body Request body (assoc array) prior to json_encode.
	 * @param string|null $token Bearer token if any.
	 * @param bool        $send_body Whether to attach body for non-GET methods.
	 * @return array|WP_Error Decoded JSON array or WP_Error on failure.
	 */
	private function remote_json( string $method, string $url, array $body, ?string $token, bool $send_body = true ) {
		$args = array(
			'method'      => $method,
			'timeout'     => 40,
			'headers'     => array(
				'Accept'       => 'application/json',
				'Content-Type' => 'application/json',
				'User-Agent'   => 'WowShipping/' . ( defined( 'WTRS_VER' ) ? WTRS_VER : 'dev' ),
			),
			'sslverify'   => false, // follow existing pattern; can be made configurable.
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
				'USPS HTTP error',
				array(
					'status' => $code,
					'body'   => $dec,
				)
			);
		}
		return is_array( $dec ) ? $dec : array();
	}

	/**
	 * Validate and persist USPS OAuth credentials.
	 *
	 * Attempts to obtain a token using the provided client_id and client_secret.
	 * On success, credentials are stored via the credential manager and the
	 * retrieved token cached (transient) for subsequent requests.
	 *
	 * @param array $credentials { client_id:string, client_secret:string }.
	 * @return bool True if credentials are valid.
	 */
	public function validate( array $credentials ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$required = array( 'client_id', 'client_secret' );
		foreach ( $required as $field ) {
			if ( empty( $credentials[ $field ] ) ) {
				return false;
			}
		}

		$body = array(
			'client_id'     => $credentials['client_id'],
			'client_secret' => $credentials['client_secret'],
			'response_type' => 'code',
			'scope'         => 'prices labels tracking payments international-prices international-labels carrier-pickup standards service-standards',
			'grant_type'    => 'client_credentials',
		);

		$response = $this->remote_json( 'POST', $this->host() . self::EP_TOKEN, $body, null, true );
		if ( is_wp_error( $response ) ) {
			return false;
		}
		if ( ( $response['status'] ?? '' ) === 'approved' && ! empty( $response['access_token'] ) ) {
			// Persist credentials.
			if ( $this->credential_manager ) {
				$this->credential_manager->bulk_set(
					array(
						'client_id'     => $credentials['client_id'],
						'client_secret' => $credentials['client_secret'],
					)
				);
			}
			// Cache token immediately for subsequent rate calls.
			set_transient( self::TOKEN_TRANSIENT, $response['access_token'], self::TOKEN_TTL );
			return true;
		}

		return false;
	}
}
