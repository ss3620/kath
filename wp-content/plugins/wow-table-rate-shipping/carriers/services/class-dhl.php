<?php // phpcs:ignore
namespace WTRS\Carriers\Services;

defined( 'ABSPATH' ) || exit;

use WP_Error;
use WTRS\Carriers\AbstractCarrier;
use WTRS\Carriers\DTO\ShipmentRequest;
use WTRS\Carriers\DTO\Rate;

/**
 * DHL Express Carrier Class (Rates via MyDHL API)
 *
 * @link https://developer.dhl.com/api-reference/mydhlapi
 */
final class Dhl extends AbstractCarrier {

	/** Base live API root (no trailing slash) */
	const LIVE_API_ROOT = 'https://express.api.dhl.com/mydhlapi';
	/** Base sandbox API root (no trailing slash) */
	const TEST_API_ROOT = 'https://express.api.dhl.com/mydhlapi/test';

	const PRODUCTS = array(
		// Domestic.
		array(
			'value' => 'N',
			'label' => 'DOMESTIC EXPRESS - Domestic',
		),
		array(
			'value' => 'I',
			'label' => 'DOMESTIC EXPRESS 9:00 - Domestic',
		),
		array(
			'value' => 'O',
			'label' => 'DOMESTIC EXPRESS 10:30 - Domestic',
		),
		array(
			'value' => '1',
			'label' => 'DOMESTIC EXPRESS 12:00 - Domestic',
		),
		array(
			'value' => 'G',
			'label' => 'DOMESTIC ECONOMY SELECT - Domestic',
		),
		array(
			'value' => 'H',
			'label' => 'ECONOMY SELECT - Domestic',
		),
		array(
			'value' => 'W',
			'label' => 'ECONOMY SELECT - Domestic',
		),
		array(
			'value' => '5',
			'label' => 'SPRINTLINE - Domestic',
		),
		array(
			'value' => '7',
			'label' => 'EXPRESS EASY - Domestic',
		),
		array(
			'value' => 'S',
			'label' => 'SAME DAY - Domestic',
		),
		array(
			'value' => '9',
			'label' => 'EUROPACK - Domestic',
		),
		array(
			'value' => '2',
			'label' => 'B2C - Domestic',
		),

		// International.
		array(
			'value' => 'R',
			'label' => 'GLOBALMAIL BUSINESS - International',
		),
		array(
			'value' => 'D',
			'label' => 'EXPRESS WORLDWIDE - International',
		),
		array(
			'value' => 'P',
			'label' => 'EXPRESS WORLDWIDE - International',
		),
		array(
			'value' => 'U',
			'label' => 'EXPRESS WORLDWIDE - International',
		),
		array(
			'value' => '4',
			'label' => 'JETLINE - International',
		),
		array(
			'value' => '8',
			'label' => 'EXPRESS EASY - International',
		),
		array(
			'value' => 'K',
			'label' => 'EXPRESS 9:00 - International',
		),
		array(
			'value' => 'E',
			'label' => 'EXPRESS 9:10 - International',
		),
		array(
			'value' => 'L',
			'label' => 'EXPRESS 10:30 - International',
		),
		array(
			'value' => 'M',
			'label' => 'EXPRESS 10:10 - International',
		),
		array(
			'value' => 'T',
			'label' => 'EXPRESS 12:00 - International',
		),
		array(
			'value' => 'Y',
			'label' => 'EXPRESS 12:00 - International',
		),
		array(
			'value' => 'X',
			'label' => 'EXPRESS ENVELOPE - International',
		),
		array(
			'value' => 'F',
			'label' => 'FREIGHT WORLDWIDE - International',
		),
		array(
			'value' => 'B',
			'label' => 'BREAKBULK EXPRESS - International',
		),
		array(
			'value' => 'V',
			'label' => 'EUROPACK - International',
		),
		array(
			'value' => '3',
			'label' => 'B2C - International',
		),

		// Special.
		array(
			'value' => 'J',
			'label' => 'JUMBO BOX - Special',
		),
		array(
			'value' => 'C',
			'label' => 'MEDICAL EXPRESS (C) - Special',
		),
		array(
			'value' => 'Q',
			'label' => 'MEDICAL EXPRESS (Q) - Special',
		),
	);

	/**
	 * {@inheritdoc}
	 */
	public static function get_key(): string {
		return 'dhl';
	}

	/**
	 * {@inheritdoc}
	 */
	public static function get_label(): string {
		return 'DHL Express';
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
	 * @param string $path API path (e.g., '/rates').
	 * @return string
	 */
	private function get_api_endpoint( string $path ): string {
		$root = $this->is_live() ? self::LIVE_API_ROOT : self::TEST_API_ROOT;
		return rtrim( $root, '/' ) . '/' . ltrim( $path, '/' );
	}

	/**
	 * Determine environment.
	 */
	protected function is_live(): bool {
		return ( $this->settings['mode'] ?? 'sandbox' ) === 'live';
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $type    Values type.
	 * @param string $context Context (view|edit).
	 * @return mixed
	 */
	public static function get_values( $type, $context = 'view' ) {
		if ( 'supported_packers' === $type ) {
			return self::add_packer_labels( self::get_supported_packers() );
		}

		// Could be extended to list DHL products once available.
		if ( 'product' === $type ) {
			return self::PRODUCTS;
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
		$parsed                = array();
		$parsed['mode']        = in_array( $values['mode'] ?? '', array( 'sandbox', 'live' ), true ) ? $values['mode'] : 'sandbox';
		$parsed['productCode'] = sanitize_text_field( $values['productCode'] ?? '' );
		$parsed['insurance']   = (bool) ( $values['insurance'] ?? false );
		$parsed['saturday']    = (bool) ( $values['saturday'] ?? false );

		return array_merge( parent::parse_settings( $values ), $parsed );
	}

	/**
	 * Fetch normalized rates.
	 *
	 * @param ShipmentRequest $request Shipment abstraction.
	 * @return array<Rate>|false
	 */
	public function fetch_rates( ShipmentRequest $request ) {
		$this->log( 'fetch_rates_started', 'Starting DHL rate request' );

		$payload = $this->build_rate_request_payload( $request );

		$this->log( 'dhl_rate_payload', 'Built DHL rate payload', $payload );

		$this->log( 'sending_request', 'Sending rate request to DHL API' );

		$response = $this->remote_post_json( $this->get_api_endpoint( '/rates' ), $payload );

		if ( is_wp_error( $response ) ) {
			$this->error( 'api_error', $response );
			return false;
		}

		$this->log( 'dhl_api_response', 'Received response from DHL', $response );

		$rates = $this->parse_rate_response( $response, $request );

		$this->log( 'normalized_rates', 'Normalized rates', array( 'rate_count' => count( $rates ) ) );

		if ( empty( $rates ) ) {
			$this->warning( 'no_rates_parsed', 'No valid rates could be parsed from DHL response' );
			return false;
		}

		if ( count( $rates ) > 1 ) {
			$this->log( 'combining_rates', 'Combining multiple package rates' );
			$rates = array( $this->combine_rates( $rates ) );
		}

		$this->log( 'rates_fetched', 'DHL rates fetched successfully', array( 'final_count' => count( $rates ) ) );

		return apply_filters( 'wtrs_carrier_filter_normalized_rates', $rates, $this->get_key(), $request );
	}

	/**
	 * Build DHL rate request body from shipment request.
	 *
	 * @param ShipmentRequest $request Request object.
	 * @return array
	 */
	protected function build_rate_request_payload( ShipmentRequest $request ): array {
		$store_address = $request->origin_location;
		$destination   = $request->destination_location;

		// phpcs:disable WordPress.Arrays.MultipleStatementAlignment,Generic.Formatting.MultipleStatementAlignment
		$payload = array(
			'customerDetails' => array(
				'shipperDetails'  => $this->format_address( $store_address ),
				'receiverDetails' => $this->format_address( $destination ),
			),
			'accounts'        => array(),
			'plannedShippingDateAndTime' => $this->compute_planned_shipping_time(),
			'unitOfMeasurement'          => 'metric',
			'isCustomsDeclarable'        => $this->is_customs_declarable( $store_address['country'] ?? '', $destination['country'] ?? '' ),
			'estimatedDeliveryDate'      => array( 'isRequested' => true ),
			'packages'                   => $this->format_packages( $request ),
		);
		// phpcs:enable WordPress.Arrays.MultipleStatementAlignment,Generic.Formatting.MultipleStatementAlignment

		$account_number = $this->get_secret( 'account_number' );
		if ( ! empty( $account_number ) ) {
			$payload['accounts'][] = array(
				'typeCode' => 'shipper',
				'number'   => $account_number,
			);
		}

		$store_currency = $request->currency; // Store currency per system design.

		// Declared value if customs declarable and value is available.
		$declared_value = apply_filters( 'wtrs_dhl_declared_value', null, $request );
		if ( $payload['isCustomsDeclarable'] && is_numeric( $declared_value ) && $declared_value > 0 ) {
			// phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
			$payload['monetaryAmount'][] = array(
				'typeCode' => 'declaredValue',
				'value'    => round( (float) $declared_value, 2 ),
				'currency' => $store_currency,
			);
		}

		// Insurance (optional) in store currency.
		if ( (bool) $this->get_setting( 'insurance' ) ) {
			$insured_value = apply_filters( 'wtrs_dhl_insured_value', $declared_value, $request );
			if ( is_numeric( $insured_value ) && $insured_value > 0 ) {
				// phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
				$payload['monetaryAmount'][] = array(
					'typeCode' => 'insuredValue',
					'value'    => round( (float) $insured_value, 2 ),
					'currency' => $store_currency,
				);
				$payload['valueAddedServices'][] = array(
					'serviceCode' => 'II',
					'value'       => round( (float) $insured_value, 2 ),
					'currency'    => $store_currency,
				);
			}
		}

		// Saturday delivery (optional).
		if ( (bool) $this->get_setting( 'saturday' ) ) {
			$payload['valueAddedServices'][] = array( 'serviceCode' => 'AA' );
		}

		// Optional product filter.
		$product_code = $this->get_setting( 'productCode' );
		if ( ! empty( $product_code ) ) {
			$payload['productsAndServices'] = array(
				'valueAddedServiceCodes' => array(),
				'productCode'            => $product_code,
			);
		}

		return array_filter( $payload );
	}

	/**
	 * Format address for DHL request.
	 *
	 * @param array $addr Address data: address1, address2, city, state, postcode, country.
	 * @return array
	 */
	private function format_address( array $addr ): array {
		$formatted = array(
			'postalCode'  => $addr['postcode'] ?? '',
			'cityName'    => $addr['city'] ?? '',
			'countryCode' => $addr['country'] ?? '',
		);

		$state = $addr['state'] ?? '';
		if ( is_string( $state ) && strlen( $state ) > 1 ) {
			$formatted['provinceCode'] = $state;
		}

		if ( ! empty( $addr['address1'] ?? '' ) ) {
			$formatted['addressLine1'] = $addr['address1'];
		}

		if ( ! empty( $addr['address2'] ?? '' ) ) {
			$formatted['addressLine2'] = $addr['address2'];
		}

		return array_filter( $formatted );
	}

	/**
	 * Build packages array from request packages.
	 *
	 * @param ShipmentRequest $request Shipment request containing packages with metric units.
	 * @return array
	 */
	private function format_packages( ShipmentRequest $request ): array {
		$packages = array();
		foreach ( $request->packages as $package ) {
			$packages[] = array(
				'weight'     => (float) $package->total_weight,
				'dimensions' => array(
					'length' => (float) $package->packed_dimensions->length,
					'width'  => (float) $package->packed_dimensions->width,
					'height' => (float) $package->packed_dimensions->height,
				),
			);
		}
		return $packages;
	}

	/**
	 * Compute planned shipping time similar to plugin reference.
	 * Returns RFC 3339 timestamp with site timezone offset.
	 */
	private function compute_planned_shipping_time(): string {
		$tz  = wp_timezone();
		$now = new \DateTime( 'now', $tz );

		// If late in the day, move to next day.
		$hour = (int) $now->format( 'H' );
		if ( $hour >= 22 ) {
			$now->modify( '+1 day' );
		}

		// Set to 14:00 local time.
		$now->setTime( 14, 0, 0 );

		// Weekend handling: move to Monday.
		$day = (int) $now->format( 'N' );
		if ( 6 === $day ) {
			$now->modify( '+2 day' );
		} elseif ( 7 === $day ) {
			$now->modify( '+1 day' );
		}

		return $now->format( 'c' );
	}

	/**
	 * Determine if customs are required.
	 *
	 * @param string $origin_country ISO 3166-1 alpha-2 origin country.
	 * @param string $dest_country   ISO 3166-1 alpha-2 destination country.
	 * @return bool
	 */
	private function is_customs_declarable( string $origin_country, string $dest_country ): bool {
		if ( empty( $origin_country ) || empty( $dest_country ) ) {
			return true; // Unknown: assume declarable.
		}

		if ( strtoupper( $origin_country ) === strtoupper( $dest_country ) ) {
			return false;
		}

		$eu = array( 'AT', 'BE', 'BG', 'CY', 'CZ', 'DE', 'DK', 'EE', 'ES', 'FI', 'FR', 'HU', 'IE', 'IT', 'LT', 'LU', 'LV', 'MT', 'NL', 'PL', 'PT', 'RO', 'SE', 'SI', 'SK', 'HR', 'GR' ); // GB excluded per post-Brexit.
		if ( in_array( strtoupper( $origin_country ), $eu, true ) && in_array( strtoupper( $dest_country ), $eu, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Perform a remote POST returning decoded JSON or WP_Error.
	 *
	 * @param string $url Endpoint.
	 * @param array  $body Request body (associative array).
	 * @return array|\WP_Error
	 */
	protected function remote_post_json( string $url, array $body ) {
		$api_key    = $this->get_secret( 'api_key' );
		$api_secret = $this->get_secret( 'api_secret' );

		if ( empty( $api_key ) || empty( $api_secret ) ) {
			$error = new \WP_Error( 'missing_credentials', 'DHL API credentials are missing.' );
			$this->error( 'missing_credentials', $error );
			return $error;
		}

		$headers = array(
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
			'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
			'Accept'        => 'application/json',
			'Content-Type'  => 'application/json',
			'User-Agent'    => 'WowShipping/' . ( defined( 'WTRS_VER' ) ? WTRS_VER : 'dev' ),
		);

		$args = array(
			'headers'   => $headers,
			'timeout'   => 45,
			'sslverify' => false,
			'body'      => wp_json_encode( $body ),
			'method'    => 'POST',
		);

		$response = wp_remote_post( $url, $args );

		if ( is_wp_error( $response ) ) {
			$this->error( 'remote_request_failed', $response );
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$error = new \WP_Error(
				'http_error',
				'Rate request failed',
				array(
					'url'    => $url,
					'status' => $code,
					'body'   => $body,
				)
			);
			$this->error( 'http_error', $error );
			return $error;
		}

		return is_array( $body ) ? $body : array();
	}

	/**
	 * Parse DHL API response into array of Rate.
	 *
	 * @param array           $data Raw decoded response.
	 * @param ShipmentRequest $request Original shipment request.
	 * @return array<Rate>
	 */
	protected function parse_rate_response( $data, ShipmentRequest $request ): array {
		$rates = array();

		if ( ! is_array( $data ) ) {
			$this->error( 'invalid_response', 'API response was not in the expected format.' );
			return $rates;
		}

		$products = array();
		if ( isset( $data['products'] ) && is_array( $data['products'] ) ) {
			$products = $data['products'];
		} elseif ( isset( $data[0] ) && is_array( $data[0] ) ) {
			// Some APIs return a top-level array.
			$products = $data;
		}

		if ( empty( $products ) ) {
			return $rates;
		}

		$desired_product = $this->get_setting( 'productCode' );

		foreach ( $products as $product ) {
			$code = isset( $product['productCode'] ) ? $product['productCode'] : ( isset( $product['serviceCode'] ) ? $product['serviceCode'] : null );
			if ( isset( $product['productName'] ) ) {
				$name = $product['productName'];
			} elseif ( isset( $product['name'] ) ) {
				$name = $product['name'];
			} else {
				$name = $code ? $code : 'DHL Service';
			}

			if ( ! empty( $desired_product ) && $desired_product !== $code ) {
				continue;
			}

			// Try to locate price & currency from common patterns used by DHL API.
			// phpcs:disable Generic.Formatting.MultipleStatementAlignment
			$amount = null;
			$currency = null;
			$total_price = isset( $product['totalPrice'] ) ? $product['totalPrice'] : null; // Can be a list of entries with priceCurrency/price.
			$pricing = isset( $product['price'] ) ? $product['price'] : null; // Fallback.
			// phpcs:enable Generic.Formatting.MultipleStatementAlignment

			// Handle totalPrice as either associative or list.
			if ( is_array( $total_price ) ) {
				// Associative style.
				if ( isset( $total_price['price'] ) && ( isset( $total_price['currency'] ) || isset( $total_price['priceCurrency'] ) ) ) {
					$amount   = $total_price['price'];
					$currency = isset( $total_price['currency'] ) ? $total_price['currency'] : $total_price['priceCurrency'];
				}

				// List of entries: prefer store currency match.
				if ( null === $amount && isset( $total_price[0] ) && is_array( $total_price[0] ) ) {
					foreach ( $total_price as $entry ) {
						if ( isset( $entry['price'] ) && isset( $entry['priceCurrency'] ) && strtoupper( $entry['priceCurrency'] ) === strtoupper( $request->currency ) ) {
							$amount   = $entry['price'];
							$currency = $entry['priceCurrency'];
							break;
						}
					}
					// Fallback to first entry if no exact currency match.
					if ( null === $amount ) {
						$first = $total_price[0];
						if ( isset( $first['price'] ) ) {
							$amount   = $first['price'];
							$currency = isset( $first['priceCurrency'] ) ? $first['priceCurrency'] : ( $first['currency'] ?? null );
						}
					}
				}
			}

			if ( ( null === $amount || null === $currency ) && is_array( $pricing ) ) {
				if ( isset( $pricing['total'], $pricing['currency'] ) ) {
					$amount   = $pricing['total'];
					$currency = $pricing['currency'];
				} elseif ( isset( $pricing['amount'], $pricing['currency'] ) ) {
					$amount   = $pricing['amount'];
					$currency = $pricing['currency'];
				}
			}

			if ( null === $amount ) {
				// Last resort: try detailedPriceBreakdown by summing line item prices for a matching currency.
				if ( isset( $product['detailedPriceBreakdown'] ) && is_array( $product['detailedPriceBreakdown'] ) ) {
					$chosen = null;
					foreach ( $product['detailedPriceBreakdown'] as $detail ) {
						if ( isset( $detail['priceCurrency'] ) && strtoupper( $detail['priceCurrency'] ) === strtoupper( $request->currency ) ) {
							$chosen = $detail;
							break;
						}
					}
					if ( null === $chosen && isset( $product['detailedPriceBreakdown'][0] ) ) {
						$chosen = $product['detailedPriceBreakdown'][0];
					}
					if ( is_array( $chosen ) && isset( $chosen['breakdown'] ) && is_array( $chosen['breakdown'] ) ) {
						$sum = 0.0;
						foreach ( $chosen['breakdown'] as $line ) {
							if ( isset( $line['price'] ) ) {
								$sum += (float) $line['price'];
							}
						}
						if ( $sum > 0 ) {
							$amount   = $sum;
							$currency = $chosen['priceCurrency'] ?? $currency;
						}
					}
				}
			}

			if ( null === $amount ) {
				continue;
			}

			// phpcs:disable Generic.Formatting.MultipleStatementAlignment
			$amount = (float) $amount;
			$currency = $currency ? $currency : $request->currency;
			$converted = $this->convert_currency( $amount, $currency, $request->currency );
			// phpcs:enable Generic.Formatting.MultipleStatementAlignment
			if ( false === $converted ) {
				continue;
			}

			$delivery_time = null;
			$transit_days  = null;
			if ( isset( $product['deliveryCapabilities']['totalTransitDays'] ) ) {
				$transit_days = (int) $product['deliveryCapabilities']['totalTransitDays'];
				if ( $transit_days > 0 ) {
					// translators: %d is number of days.
					$delivery_time = sprintf( _n( '%d business day', '%d business days', $transit_days, 'table-rate-shipping-pro' ), $transit_days );
				}
			} elseif ( isset( $product['deliveryTime'] ) && is_string( $product['deliveryTime'] ) ) {
				$delivery_time = $product['deliveryTime'];
			}

			$rates[] = new Rate(
				$this->get_key(),
				$code ? $code : 'DHL',
				$name,
				$converted,
				$request->currency,
				$delivery_time,
				$transit_days
			);
		}

		return $rates;
	}

	/**
	 * Validate DHL credentials & settings, then persist them.
	 * Expected credentials: api_key, api_secret, account_number (optional).
	 *
	 * @param array $credentials Credential inputs.
	 * @return bool True if stored, false if required fields missing.
	 */
	public function validate( array $credentials ) {
		$required = array( 'api_key', 'api_secret', 'account_number' );
		foreach ( $required as $field ) {
			if ( empty( $credentials[ $field ] ) ) {
				return false;
			}
		}

		$this->credential_manager->bulk_set(
			array(
				'api_key'        => $credentials['api_key'],
				'api_secret'     => $credentials['api_secret'],
				'account_number' => $credentials['account_number'],
			)
		);

		return true;
	}
}
