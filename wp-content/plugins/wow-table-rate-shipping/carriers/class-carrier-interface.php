<?php // phpcs:ignore
namespace WTRS\Carriers;

defined( 'ABSPATH' ) || exit;

use WTRS\Carriers\DTO\ShipmentRequest;

interface CarrierInterface {
	/**
	 * Unique machine key for carrier (slug).
	 *
	 * @return string
	 */
	public static function get_key(): string;

	/**
	 * Human readable carrier label.
	 *
	 * @return string
	 */
	public static function get_label(): string;

	/**
	 * Get various values for settings fields.
	 *
	 * @param string $type value type.
	 * @return mixed
	 */
	public static function get_values( $type );

	/**
	 * Get supported packing strategies.
	 *
	 * @return array<string>
	 */
	public static function get_supported_packers(): array;


	/**
	 * Fetch normalized rate objects for a shipment.
	 *
	 * @param ShipmentRequest $request Shipment abstraction.
	 * @return array<\WTRS\Carriers\DTO\Rate>|false
	 */
	public function fetch_rates( ShipmentRequest $request );

	/**
	 * Log debug message.
	 *
	 * @param mixed ...$message Debug message.
	 * @return void
	 */
	public function log( ...$message );

	/**
	 * Log warning.
	 *
	 * @param string $code Warning code.
	 * @param mixed  ...$messages Human message(s).
	 * @return void
	 */
	public function warning( string $code, ...$messages );

	/**
	 * Log error.
	 *
	 * @param string $code Error code.
	 * @param mixed  ...$messages Human message(s).
	 * @return void
	 */
	public function error( string $code, ...$messages );

	/**
	 * Validate supplied credentials & related settings, persisting them on success.
	 * Returns true when credentials stored, false on validation failure.
	 *
	 * @param array $credentials Credential field => value.
	 * @return bool
	 */
	public function validate( array $credentials );
}
