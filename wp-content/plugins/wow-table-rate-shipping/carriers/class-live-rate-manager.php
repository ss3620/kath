<?php // phpcs:ignore
namespace WTRS\Carriers;

defined( 'ABSPATH' ) || exit;

use WTRS\Carriers\CarrierService;
use WTRS\Carriers\DTO\Item;
use WTRS\Carriers\DTO\Dimensions;
use WTRS\Carriers\DTO\ShipmentRequest;
use WTRS\Carriers\DTO\Package;
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

		// Original, unconverted box configs as entered by the admin (store units) — keyed by box id —
		// so the text summary below can show what was actually typed in Custom Boxes settings instead
		// of the internally-normalized cm/kg values used for the carrier API request.
		$raw_boxes_by_id = array();
		foreach ( $args['packSettings']['packBoxes'] ?? array() as $raw_box_config ) {
			if ( isset( $raw_box_config['id'] ) ) {
				$raw_boxes_by_id[ $raw_box_config['id'] ] = $raw_box_config;
			}
		}

		$dimension_unit = get_option( 'woocommerce_dimension_unit', 'in' );
		$weight_unit    = get_option( 'woocommerce_weight_unit', 'lbs' );

		$packed_boxes = array();

		foreach ( $packages as $package ) {
			$box_id      = $package->meta['box_id'] ?? '';
			$raw_box     = $raw_boxes_by_id[ $box_id ] ?? array();
			$product_ids = array();

			foreach ( $package->items as $item ) {
				for ( $i = 0; $i < $item->quantity; $i++ ) {
					$product_ids[] = $item->product_id;
				}
			}

			$dimensions = sprintf(
				'%s x %s x %s cm',
				$package->packed_dimensions->length,
				$package->packed_dimensions->width,
				$package->packed_dimensions->height
			);

			$raw_dimensions = isset( $raw_box['length'], $raw_box['width'], $raw_box['height'] )
				? sprintf(
					'%1$s%4$s x %2$s%4$s x %3$s%4$s',
					$raw_box['length'],
					$raw_box['width'],
					$raw_box['height'],
					$dimension_unit
				)
				: $dimensions;

			$raw_weight = ! empty( $raw_box['boxWeight'] ) ? sprintf( '%s %s', $raw_box['boxWeight'], $weight_unit ) : '';

			$group_key = $box_id . '|' . $raw_dimensions;

			if ( ! isset( $packed_boxes[ $group_key ] ) ) {
				$packed_boxes[ $group_key ] = array(
					'box_id'         => $box_id,
					'length'         => $package->packed_dimensions->length,
					'width'          => $package->packed_dimensions->width,
					'height'         => $package->packed_dimensions->height,
					'dimensions'     => $dimensions,
					'raw_dimensions' => $raw_dimensions,
					'raw_weight'     => $raw_weight,
					'quantity'       => 0,
					'items'          => array(),
					// Total shipment weight contributed by this box: packed items' weight + the box's own tare weight
					// (see BoxPacker::convert_packed_boxes_to_packages() — $total_weight + $box_config['box_weight']),
					// summed across every instance of this box used. Not box-only or product-only weight.
					'weight'         => 0.0,
				);
			}

			++$packed_boxes[ $group_key ]['quantity'];
			$packed_boxes[ $group_key ]['items']   = array_merge( $packed_boxes[ $group_key ]['items'], $product_ids );
			$packed_boxes[ $group_key ]['weight'] += $package->total_weight;
		}

		$packed_boxes = array_values( $packed_boxes );

		/**
		 * Filter the packed box details attached to a carrier rate's meta data.
		 *
		 * Runs once per fetch_rates() call, before the box summary is merged into every returned
		 * Rate's `rate_details['wtrs_packed_boxes']` / `rate_details['wtrs_packed_boxes_json']` (which
		 * WooCommerce persists as order shipping-line-item meta).
		 *
		 * @param array             $packed_boxes Array of box summaries (box_id, length, width, height, dimensions, raw_dimensions, raw_weight, quantity, items, weight).
		 * @param Package[]         $packages     The packed Package DTOs for this shipment.
		 * @param ShipmentRequest   $request      The shipment request sent to the carrier.
		 */
		$packed_boxes = apply_filters( 'wtrs_rate_packed_boxes', $packed_boxes, $packages, $request );

		// Human-readable text summary — uses the box dimensions exactly as configured by the admin
		// (store units), not the internally-converted cm values, so it matches what's printed on the
		// Custom Boxes settings screen and is easy to match against a physical box.
		$packed_boxes_text = implode(
			' | ',
			array_map(
				function ( $box ) {
					return sprintf( '%s (%d)', $box['raw_dimensions'], $box['quantity'] );
				},
				$packed_boxes
			)
		);

		// Machine-parseable JSON summary (normalized cm/kg — packed_dimensions/total_weight are always
		// stored internally as cm/kg regardless of store units, see Package DTO) — a JSON string is
		// still scalar, so WooCommerce's admin order screen (which only renders scalar order-item meta
		// values, see WC_Order_Item::get_formatted_meta_data()) still displays it, while any integration
		// can json_decode() it directly.
		$packed_boxes_json = wp_json_encode(
			array_map(
				function ( $box ) {
					// Formatted as fixed-decimal strings (not floats) so json_encode() can't emit binary-float
					// artifacts like 5.0800000000000001 — this depends on the host's `serialize_precision`
					// ini setting, which we can't control across different environments.
					return array(
						'length'         => sprintf( '%.2f', $box['length'] ),
						'width'          => sprintf( '%.2f', $box['width'] ),
						'height'         => sprintf( '%.2f', $box['height'] ),
						'dimension_unit' => 'cm',
						'quantity'       => $box['quantity'],
						'items'          => $box['items'],
						'weight'         => sprintf( '%.2f', $box['weight'] ),
						'weight_unit'    => 'kg',
					);
				},
				$packed_boxes
			)
		);

		$rates        = array();
		$markup_type  = isset( $args['markUpType'] ) ? $args['markUpType'] : '';
		$markup_value = isset( $args['markUpRate'] ) ? (float) $args['markUpRate'] : 0.0;

		foreach ( $payload as $obj ) {
			if ( $obj instanceof Rate ) {
				$rate = clone $obj;

				if ( ! empty( $packed_boxes ) ) {
					$rate->rate_details['wtrs_packed_boxes']      = $packed_boxes_text;
					$rate->rate_details['wtrs_packed_boxes_json'] = $packed_boxes_json;
				}

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
