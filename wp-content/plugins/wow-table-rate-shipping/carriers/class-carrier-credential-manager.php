<?php // phpcs:ignore
namespace WTRS\Carriers; defined( 'ABSPATH' ) || exit;

/**
 * CarrierCredentialManager
 *
 * Centralized storage & retrieval of sensitive carrier credentials. All
 * credential fields for a carrier are stored together in a single option
 * (array) to simplify persistence & retrieval and to avoid scattering
 * secret values across many individual options. Autoload is disabled.
 *
 * Previous version stored each field as its own option and used filters.
 * This refactored version removes those filters (per user request) and
 * fixes undefined variable issues around $carrier_key.
 */
class CarrierCredentialManager {
	/**
	 * Option key prefix (final option name will be prefix + carrier_key).
	 *
	 * @var string
	 */
	const OPTION_PREFIX = 'wtrs_carrier_secret_';

	/**
	 * The full option name holding the associative array of secrets.
	 *
	 * @var string
	 */
	private $option_name;

	/**
	 * Cached in-memory array of secrets (lazy loaded).
	 *
	 * @var array|null
	 */
	private $cache = null;

	/**
	 * Constructor.
	 *
	 * @param string $carrier_key Carrier identifier (e.g. 'fedex').
	 */
	public function __construct( string $carrier_key ) {
		$this->option_name = self::OPTION_PREFIX . $carrier_key; // No trailing underscore needed.
	}

	/**
	 * Load (or return cached) secrets array.
	 *
	 * @return array
	 */
	private function load(): array {
		if ( null === $this->cache ) {
			$value = get_option( $this->option_name, array() );
			// Guarantee array.
			$this->cache = is_array( $value ) ? $value : array();
		}
		return $this->cache;
	}

	/**
	 * Persist current cache to the single option.
	 *
	 * @return void
	 */
	private function persist(): void {
		update_option( $this->option_name, $this->cache, false ); // Autoload disabled.
	}

	/**
	 * Retrieve a secret value.
	 *
	 * @param string $field    Secret field key (e.g. api_key, password, token).
	 * @param string $fallback Default value if not present.
	 * @return string
	 */
	public function get( string $field, string $fallback = '' ): string {
		$data = $this->load(); // Single assignment; alignment not required.
		return isset( $data[ $field ] ) ? (string) $data[ $field ] : $fallback;
	}

	/**
	 * Set/overwrite a secret field value.
	 *
	 * @param string $field Field key.
	 * @param string $value Plain (already sanitized) value.
	 * @return void
	 */
	public function set( string $field, string $value ): void {
		$data           = $this->load();
		$data[ $field ] = $value;
		$this->cache    = $data;
		$this->persist();
	}

	/**
	 * Bulk set multiple secrets (array field => value).
	 *
	 * @param array $data Assoc array of field => value.
	 * @return void
	 */
	public function bulk_set( array $data ): void {
		$current     = $this->load();
		$this->cache = array_merge( $current, $data );
		$this->persist();
	}

	/**
	 * Return masked representation for debugging/logging (last 4 chars visible).
	 *
	 * @param array $fields List of fields to include in the mask output.
	 * @return array field => masked value (empty string if not set)
	 */
	public function masked( array $fields ): array {
		$out = array();
		foreach ( $fields as $field ) {
			$val = $this->get( $field, '' );
			if ( '' === $val ) {
				$out[ $field ] = '';
				continue;
			}
			$len           = strlen( $val );
			$out[ $field ] = str_repeat( '*', max( 0, $len - 4 ) ) . substr( $val, -4 );
		}
		return $out;
	}

	/**
	 * Get all stored secrets (unmasked). Use cautiously.
	 *
	 * @return array
	 */
	public function all(): array {
		return $this->load();
	}

	/**
	 * Determine if at least one credential field has a non-empty value.
	 * Useful to quickly check if the carrier is configured.
	 *
	 * Empty strings, null, and values that trim to '' are considered empty.
	 *
	 * @return bool True if any non-empty credential exists, false otherwise.
	 */
	public function has_credentials(): bool {
		$data = $this->load();
		foreach ( $data as $value ) {
			if ( is_scalar( $value ) ) {
				$trimmed = trim( (string) $value );
				if ( '' !== $trimmed ) {
					return true;
				}
			} elseif ( ! empty( $value ) ) { // In case future structure stores nested arrays.
				return true;
			}
		}
		return false;
	}
}
