<?php // phpcs:ignore
namespace WTRS\Carriers;

defined( 'ABSPATH' ) || exit;

use WTRS\Carriers\CarrierService;
use WTRS\Carriers\DTO\Item;
use WTRS\Carriers\DTO\Dimensions;
use WTRS\Carriers\DTO\ShipmentRequest;
use WTRS\Carriers\DTO\Rate;
use WTRS\Carriers\Packing\PackerInterface;
use WTRS\Traits\Singleton;

/**
 * LiveRateManager orchestrates packing, cache, API invocation & rate injection.
 */
class LiveRateManager {
	use Singleton;

	/**
	 * Guard flag to prevent duplicate injection during a single request.
	 *
	 * @var bool
	 */
	protected static $processed = false;

	/**
	 * Register WordPress hooks for cache invalidation and debug rendering.
	 *
	 * @return void
	 */
	public function hooks() {
		add_action( 'wtrs_carrier_invalidate_cache', array( $this, 'purge_cache' ) );
	}

	/**
	 * Increment cache version to invalidate stored transients.
	 *
	 * @return void
	 */
	public function purge_cache() {
		$v = (int) get_option( 'wtrs_carrier_cache_version', 0 );
		update_option( 'wtrs_carrier_cache_version', $v + 1 );
	}

	/**
	 * Get available item packer strategies.
	 *
	 * @return array<string, string> Map of packer key => class FQN.
	 */
	protected function get_packers(): array {
		$list = array(
			'per_item'       => \WTRS\Carriers\Packing\PerItemPacker::class,
			'box'            => \WTRS\Carriers\Packing\BoxPacker::class,
			'single_package' => \WTRS\Carriers\Packing\SinglePackagePacker::class,
			'weight'         => \WTRS\Carriers\Packing\WeightPacker::class,
		);
		return apply_filters( 'wtrs_registered_packers', $list );
	}

	/**
	 * Instantiate the configured packer by key.
	 *
	 * @param string           $key Packer identifier.
	 * @param CarrierInterface $carrier Carrier instance.
	 * @return PackerInterface|false
	 */
	protected function select_packer( string $key, CarrierInterface $carrier ) {

		$supported_packers = $carrier::get_supported_packers();

		if ( count( $supported_packers ) === 1 ) {
			$key = $supported_packers[0];
		} elseif ( ! in_array( $key, $supported_packers, true ) ) {

			$carrier->error( 'invalid_packer', 'Selected packer not supported by carrier: ' . $key );

			return false;
		}

		$packers = $this->get_packers();

		if ( ! isset( $packers[ $key ] ) ) {

			$carrier->error( 'unknown_packer', 'Unknown packer selected: ' . $key );

			return false;
		}

		return new $packers[ $key ]();
	}

	/**
	 * Compute live carrier rates based on provided arguments and return method-like arrays.
	 *
	 * Notes:
	 * - Avoids calling WooCommerce helper functions inside this method.
	 * - Processes a single carrier instance provided via arguments.
	 * - Simplified caching (optional) without locks.
	 *
	 * Expected $args keys (assoc array):
	 * - carrier: CarrierInterface instance (required)
	 * - cart_data: array
	 * - pack_strategy: string (optional, default 'aggregate_weight') when items are provided
	 * - origin: array{country:string, postcode:string} (required by carrier/request)
	 * - dest: array{country:string, postcode:string} (required by carrier/request)
	 * - currency: string (default 'USD')
	 * - subtotal: float (default 0)
	 * - subtotal_tax: float (default 0)
	 * - context: 'cart'|'checkout' (default 'cart')
	 * - markup_type: 'none'|'percent'|'flat' (optional)
	 * - markup_value: float (optional)
	 * - cache_ttl: int seconds (optional; default 600 when cache_key provided)
	 * - fallback: array{enabled:bool, cost:float, label:string} (optional)
	 *
	 * Returns: array<int, array>|false  List of method-like arrays similar to MethodHelper validate_* outputs, or false on failure.
	 * Each returned method has keys: id, method_id, label, cost, method_type, description, delivery_time, rate_details.
	 *
	 * @param array $args Input arguments (see above).
	 * @return Rate[]|false
	 */
	public function fetch_rates( array $args ) {
		$carrier_key = $args['carrier_key'] ?? null;
		if ( ! $carrier_key ) {
			return false;
		}

		$carrier = CarrierService::create_service( $carrier_key, $args['settings'] );

		if ( ! $carrier ) {
			return false;
		}

		$items = array();

		foreach ( $args['cart_data']  as $data ) {
			$item    = new Item(
				$data['id'],
				$data['quantity'],
				! empty( $data['weight'] ) ? $data['weight'] : (float) $carrier->get_setting( 'defWeight', 0.001 ),
				new Dimensions(
					! empty( $data['length'] ) ? $data['length'] : (float) $carrier->get_setting( 'defLength', 1 ),
					! empty( $data['width'] ) ? $data['width'] : (float) $carrier->get_setting( 'defWidth', 1 ),
					! empty( $data['height'] ) ? $data['height'] : (float) $carrier->get_setting( 'defHeight', 1 )
				),
				$data['declared'],
				$data['sku']
			);
			$items[] = $item;
		}

		$pack_key = $args['packSettings']['packMethod'] ?? 'per_item';
		$packer   = $this->select_packer( $pack_key, $carrier );

		if ( ! $packer ) {
			$carrier->error( 'invalid_packer', 'Selected packer not supported by carrier: ' . $pack_key );
			return false;
		}

		$packages = $packer->pack( $items, $args['packSettings'] ?? array() );

		if ( empty( $packages ) ) {
			$carrier->error( 'no_packages', 'No packages could be created from items.' );
			return false;
		}

		$origin = $args['origin'];
		$dest   = $args['dest'];

		$currency     = $args['currency'];
		$subtotal     = (float) ( $args['subtotal'] ?? 0 );
		$subtotal_tax = (float) ( $args['subtotal_tax'] ?? 0 );
		$context      = $args['context'] ?? 'cart';

		$request = new ShipmentRequest( $origin, $dest, $packages, $currency, $subtotal, $subtotal_tax, array(), $context );

		$cache_key = $this->build_cache_key( $carrier_key, $args['method_id'], $request );
		$payload   = null;

		if ( ! $carrier->is_debug_mode() && ( $args['use_cache'] ?? true ) ) {
			$payload = get_transient( $cache_key );
		}

		if ( ! $payload ) {
			$payload = $carrier->fetch_rates( $request );
			if ( $payload && ! $carrier->is_debug_mode() && ( $args['use_cache'] ?? true ) ) {
				$ttl = (int) ( $args['cache_ttl'] ?? 600 );
				set_transient( $cache_key, $payload, $ttl );
			}
		}

		if ( ! $payload ) {
			$fallback_rate = (float) $args['fallbackRate'];
			if ( ! empty( $fallback_rate ) ) {
				$carrier->warning( 'fallback_rate', 'Using fallback rate: ' . $fallback_rate . ' ' . $args['fallbackType'] );
				$cost = $fallback_rate;

				if ( 'percent' === $args['fallbackType'] ) {
					$cost = ( $subtotal + $subtotal_tax ) * ( $fallback_rate / 100 );
				}

				$payload = array(
					new Rate(
						'wtrs_' . $carrier_key . '_fallback',
						'wtrs_' . $carrier_key . '_fallback',
						$carrier->get_label(),
						$cost,
						$currency,
						null,
						null,
						array(),
						array(
							'fallback' => true,
						),
					),
				);
			} else {
				return false;
			}
		}

		$rates        = array();
		$markup_type  = isset( $args['markUpType'] ) ? $args['markUpType'] : '';
		$markup_value = isset( $args['markUpRate'] ) ? (float) $args['markUpRate'] : 0.0;

		foreach ( $payload as $obj ) {
			if ( $obj instanceof Rate ) {
				$rate = clone $obj;

				$is_fallback = $rate->warnings['fallback'] ?? false;

				if ( ! $is_fallback ) {
					$cost = (float) $obj->cost;
					if ( ! str_contains( $obj->carrier_key, 'fallback' ) ) {
						if ( 'percent' === $markup_type ) {
							$cost += $cost * ( $markup_value / 100 );
						} elseif ( 'fixed' === $markup_type ) {
							$cost += $markup_value;
						}
					}
					$cost       = max( 0, $cost );
					$rate->cost = $cost;
				}

				$rates[] = $rate;
			}
		}

		if ( empty( $rates ) ) {
			return false;
		}

		return $rates;
	}

	/**
	 * Build a cache key for rate results based on carrier and shipment request.
	 *
	 * @param string          $carrier_key Carrier identifier.
	 * @param string          $method_id   Method identifier.
	 * @param ShipmentRequest $request     Shipment request DTO.
	 * @return string Cache key string.
	 */
	protected function build_cache_key( string $carrier_key, string $method_id, ShipmentRequest $request ): string {
		$salt = get_option( 'wtrs_carrier_cache_version', 0 );
		return 'wtrs_carrier_rates_' . md5( $carrier_key . $method_id . $request->hash() ) . $salt;
	}
}
