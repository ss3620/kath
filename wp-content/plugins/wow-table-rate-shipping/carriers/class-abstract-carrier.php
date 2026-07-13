<?php // phpcs:ignore
namespace WTRS\Carriers;

use WTRS\Carriers\DTO\Rate;
use WTRS\Includes\DB;
use WTRS\Includes\Utils\Flags;

defined( 'ABSPATH' ) || exit;

/**
 * Base carrier implementation providing shared helpers for concrete carriers.
 */
abstract class AbstractCarrier implements CarrierInterface {

	/**
	 * Rule ID
	 *
	 * @var string
	 */
	protected $rule_id;

	/**
	 * Non-sensitive configuration (enable flags, origin, packing).
	 *
	 * @var array
	 */
	protected $settings = array();

	/**
	 * Credential manager instance.
	 *
	 * @var CarrierCredentialManager
	 */
	protected $credential_manager;

	/**
	 * Constructor.
	 *
	 * @param array $settings settings.
	 */
	public function __construct( $settings = array() ) {
		$this->settings           = $this->parse_settings( $settings );
		$this->credential_manager = new CarrierCredentialManager( static::get_key() );
	}

	/**
	 * Parse common settings.
	 *
	 * @param array $values values.
	 * @return array
	 */
	protected static function parse_settings( $values ) {
		$parsed = array();

		// Default values.
		$parsed['defWeight'] = floatval( $values['defWeight'] ?? 0.001 );
		$parsed['defLength'] = floatval( $values['defLength'] ?? 10 );
		$parsed['defWidth']  = floatval( $values['defWidth'] ?? 10 );
		$parsed['defHeight'] = floatval( $values['defHeight'] ?? 10 );

		// fallback.
		$parsed['fallbackEnabled']  = boolval( $values['fallbackEnabled'] ?? false );
		$parsed['fallbackRate']     = floatval( $values['fallbackRate'] ?? 0 );
		$parsed['fallbackRateType'] = floatval( $values['fallbackRateType'] ?? 'fixed' );

		return $parsed;
	}

	/**
	 * Retrieve a non-sensitive setting with default.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Default value.
	 * @return mixed
	 */
	public function get_setting( string $key, $fallback = '' ) {
		return $this->settings[ $key ] ?? $fallback;
	}

	/**
	 * Retrieve a secret via credential manager.
	 *
	 * @param string $field    Secret field key.
	 * @param string $fallback Default value.
	 * @return string
	 */
	protected function get_secret( string $field, string $fallback = '' ): string {
		return $this->credential_manager->get( $field, $fallback );
	}

	/**
	 * Log debug/informational message (honors debug filter).
	 * Implements CarrierInterface::log.
	 *
	 * @param mixed ...$message Message part(s).
	 * @return void
	 */
	public function log( ...$message ) {
		if ( ! $this->is_debug_mode() ) {
			return;
		}
		if ( empty( $message ) ) {
			return; // Nothing to log.
		}
		if ( function_exists( 'wc_get_logger' ) ) {
			$parts = array_map( array( $this, 'stringify' ), $message );
			wc_get_logger()->info( '[' . static::get_key() . ':log] ' . implode( ' | ', $parts ), array( 'source' => 'wtrs_carrier_' . static::get_key() ) );
		}
	}

	/**
	 * Log warning.
	 * Implements CarrierInterface::warning.
	 *
	 * @param string $code Warning code.
	 * @param mixed  ...$messages Message part(s).
	 * @return void
	 */
	public function warning( string $code, ...$messages ) {
		if ( ! $this->is_debug_mode() ) {
			return;
		}
		if ( empty( $messages ) ) {
			return; // Nothing to log.
		}
		if ( function_exists( 'wc_get_logger' ) ) {
			$parts = array_map( array( $this, 'stringify' ), $messages );
			wc_get_logger()->warning( '[' . static::get_key() . ':' . $code . '] ' . implode( ' | ', $parts ), array( 'source' => 'wtrs_carrier_' . static::get_key() ) );
		}
	}

	/**
	 * Log error.
	 * Implements CarrierInterface::error.
	 *
	 * @param string $code Error code.
	 * @param mixed  ...$messages Message part(s).
	 * @return void
	 */
	public function error( string $code, ...$messages ) {
		if ( ! $this->is_debug_mode() ) {
			return;
		}
		if ( empty( $messages ) ) {
			return; // Nothing to log.
		}
		if ( function_exists( 'wc_get_logger' ) ) {
			$parts = array_map( array( $this, 'stringify' ), $messages );
			wc_get_logger()->error( '[' . static::get_key() . ':' . $code . '] ' . implode( ' | ', $parts ), array( 'source' => 'wtrs_carrier_' . static::get_key() ) );
		}
	}

	/**
	 * Check if debug mode is enabled (via filter).
	 *
	 * @return boolean
	 */
	public function is_debug_mode() {
		$settings = DB::get_instance()->get_settings();
		return ( $settings['debugMode'] ?? false ) && Flags::is_user_admin();
	}

	/**
	 * Add package labels for settings.
	 *
	 * @param array $pack_strats Packing strategies.
	 * @return array
	 */
	protected static function add_packer_labels( $pack_strats ) {
		$list = array(
			'per_item'       => array(
				'label'       => __( 'Pack Each Product Separately', 'table-rate-shipping-pro' ),
				'description' => __( 'Packs each item in a separate package.', 'table-rate-shipping-pro' ),
			),
			'box'            => array(
				'label'       => __( 'Custom Boxes', 'table-rate-shipping-pro' ),
				'description' => __( 'Uses predefined box sizes to efficiently fit items and reduce wasted space.', 'table-rate-shipping-pro' ),
			),
			'single_package' => array(
				'label'       => __( 'Single Package', 'table-rate-shipping-pro' ),
				'description' => __( 'Packs all items into one package, combining both weight and stacking dimensions.', 'table-rate-shipping-pro' ),
			),
			'weight'         => array(
				'label'       => __( 'Weight Based', 'table-rate-shipping-pro' ),
				'description' => __( 'Ignores item dimensions and packs based on maximum box weight.', 'table-rate-shipping-pro' ),
			),
		);

		$result = array();

		foreach ( $pack_strats as $value ) {
			if ( isset( $list[ $value ] ) ) {
				$result[] = array(
					'value'       => $value,
					'label'       => $list[ $value ]['label'],
					'description' => $list[ $value ]['description'],
				);
			}
		}

		return $result;
	}

	/**
	 * Combine multiple rate arrays into one.
	 *
	 * @param Rate[] $rates List of rate arrays to combine.
	 * @return Rate
	 */
	protected function combine_rates( $rates ) {
		$data = array(
			'carrier_key'   => '',
			'service_code'  => array(),
			'service_name'  => array(),
			'cost'          => 0,
			'currency'      => '',
			'delivery_time' => '',
			'transit_days'  => 0,
			'rate_details'  => array(),
			'warnings'      => array(),
		);

		usort(
			$rates,
			function ( $a, $b ) {
				return $a->cost <=> $b->cost;
			}
		);

		foreach ( $rates as $rate ) {
			$data['carrier_key']    = $rate->carrier_key;
			$data['service_code'][] = $rate->service_code;
			$data['service_name'][] = $rate->service_name;
			$data['cost']          += $rate->cost;
			$data['currency']       = $rate->currency;
			$data['delivery_time']  = $rate->delivery_time;
			$data['transit_days']   = max( $data['transit_days'], (int) $rate->transit_days );
			$data['rate_details']   = array_merge( $data['rate_details'], $rate->rate_details ?? array() );
			$data['warnings']       = array_merge( $data['warnings'], $rate->warnings ?? array() );
		}

		return new Rate(
			$data['carrier_key'],
			implode( '|', array_unique( $data['service_code'] ) ),
			implode( '|', array_unique( $data['service_name'] ) ),
			$data['cost'],
			$data['currency'],
			$data['delivery_time'],
			$data['transit_days'],
			$data['rate_details'],
			$data['warnings']
		);
	}

	/**
	 * Get amount to store currency
	 *
	 * @param float  $amount Amount to convert.
	 * @param string $from_currency_code Currency code of the amount.
	 * @param string $to_currency_code Currency code to convert to.
	 * @return float|false
	 */
	public function convert_currency( $amount, $from_currency_code, $to_currency_code ) {
		$target_currency    = strtolower( $to_currency_code );
		$from_currency_code = strtolower( $from_currency_code );
		if ( $from_currency_code === $target_currency ) {
			return $amount;
		}

		$currency_rates_meta_key = 'wtrs_currency_rates_' . $target_currency;

		$rates = get_transient( $currency_rates_meta_key );

		if ( ! empty( $rates['date'] ) ) {
			$rate_time = strtotime( $rates['date'] );
			if ( false !== $rate_time ) {
				if ( $rate_time < strtotime( '-2 days' ) ) {
					delete_transient( $currency_rates_meta_key );
				}
			}
		}

		if ( ! isset( $rates[ $target_currency ] ) ) {

			$urls = array(
				'https://cdn.jsdelivr.net/npm/@fawazahmed0/currency-api@latest/v1/currencies/',
				'https://latest.currency-api.pages.dev/v1/currencies/',
			);

			foreach ( $urls as $url ) {
				$response = wp_remote_get( $url . $target_currency . '.min.json' );
				if ( is_wp_error( $response ) ) {
					continue;
				}
				$rates = json_decode( wp_remote_retrieve_body( $response ), true );
				if ( isset( $rates[ $target_currency ] ) && is_array( $rates[ $target_currency ] ) ) {
					set_transient( $currency_rates_meta_key, $rates, HOUR_IN_SECONDS );
					break;
				}
			}
		}

		if ( isset( $rates[ $target_currency ][ $from_currency_code ] ) ) {
			$current_rate = floatval( $rates[ $target_currency ][ $from_currency_code ] );

			$this->log(
				sprintf(
				/* translators: %1$s for store currency, %2$s for current rate, %3$s for converted currency */
					esc_html__( 'The current exchange rate is 1 %1$s = %2$s %3$s.', 'table-rate-shipping-pro' ),
					strtoupper( $target_currency ),
					$current_rate,
					strtoupper( $from_currency_code )
				)
			);

			return $amount / $current_rate;
		}

		$this->error(
			'currency_conversion_failure',
			__( 'Failed to retrieve the exchange rate from the API.', 'table-rate-shipping-pro' )
		);

		return false;
	}

	/**
	 * Safely convert any value to string for logging.
	 *
	 * @param mixed $value Value to convert.
	 * @return string
	 */
	private function stringify( $value ) {
		if ( is_string( $value ) ) {
			return $value;
		} elseif ( is_array( $value ) || is_object( $value ) ) {
			return wp_json_encode( $value, JSON_PRETTY_PRINT );
		} elseif ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		} elseif ( is_null( $value ) ) {
			return 'null';
		} else {
			return strval( $value );
		}
	}
}
