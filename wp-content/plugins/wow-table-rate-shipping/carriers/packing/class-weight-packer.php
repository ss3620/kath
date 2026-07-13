<?php
/**
 * Weight Packer class.
 *
 * Creates packages based on maximum weight constraints.
 *
 * @package WTRS\Carriers\Packing
 */

namespace WTRS\Carriers\Packing;

defined( 'ABSPATH' ) || exit;

use WTRS\Carriers\DTO\Package;
use WTRS\Carriers\DTO\Dimensions;

/**
 * Class WeightPacker
 */
class WeightPacker implements PackerInterface {

	/**
	 * Get the key for this packer.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'weight';
	}

	/**
	 * Pack items into packages using First-Fit-Decreasing weight-based bin packing.
	 *
	 * @param array<\WTRS\Carriers\DTO\Item> $items The items to pack.
	 * @param array                          $config Optional configuration array with keys:
	 *                                              - packMaxWeight: Maximum weight per package (kg).
	 * @return array Array of Package objects.
	 */
	public function pack( array $items, array $config = array() ): array {
		$max_weight = floatval( $config['packMaxWeight'] ?? false );

		if ( empty( $items ) || $max_weight <= 0 ) {
			return array();
		}

		// Expand items by quantity for individual processing.
		$individual_items = array();
		foreach ( $items as $item ) {
			for ( $q = 0; $q < $item->quantity; $q++ ) {
				$individual_items[] = array(
					'item'   => $item,
					'weight' => $item->weight,
					'key'    => $item->sku . '_' . $q,
				);
			}
		}

		// First-Fit-Decreasing: Sort items by weight in descending order.
		usort(
			$individual_items,
			function ( $a, $b ) {
				return $b['weight'] <=> $a['weight']; // Descending order.
			}
		);

		$bins = array(); // Array of bins, each containing items and current weight.

		// First-Fit-Decreasing Algorithm.
		foreach ( $individual_items as $individual_item ) {
			$item_weight = $individual_item['weight'];
			$placed      = false;

			// Try to fit item in first available bin (First-Fit).
			foreach ( $bins as &$bin ) {
				if ( $bin['current_weight'] + $item_weight <= $max_weight ) {
					$bin['items'][]         = $individual_item;
					$bin['current_weight'] += $item_weight;
					$placed                 = true;
					break;
				}
			}

			// If item doesn't fit in any existing bin, create a new bin.
			if ( ! $placed ) {
				$bins[] = array(
					'items'          => array( $individual_item ),
					'current_weight' => $item_weight,
				);
			}
		}

		// Convert bins to Package objects.
		$packages = array();
		foreach ( $bins as $bin ) {
			$packages[] = $this->create_package_from_bin( $bin );
		}

		return $packages;
	}

	/**
	 * Create a Package object from a bin of items.
	 *
	 * @param array $bin Array with 'items' and 'current_weight'.
	 * @return Package Package object.
	 */
	private function create_package_from_bin( array $bin ): Package {
		$grouped_items = array();
		$total_weight  = 0;
		$total_value   = 0;
		$max_l         = 0;
		$max_w         = 0;
		$total_h       = 0; // Stack height by adding all item heights.

		// Group items by SKU to consolidate quantities.
		$item_quantities = array();
		foreach ( $bin['items'] as $individual_item ) {
			$original_item = $individual_item['item'];
			$sku           = $original_item->sku;

			if ( ! isset( $item_quantities[ $sku ] ) ) {
				$item_quantities[ $sku ] = array(
					'item'     => $original_item,
					'quantity' => 0,
				);
			}
			++$item_quantities[ $sku ]['quantity'];
		}

		// Create grouped Item objects and calculate totals.
		foreach ( $item_quantities as $sku => $group ) {
			$original_item = $group['item'];
			$quantity      = $group['quantity'];

			$grouped_item = new \WTRS\Carriers\DTO\Item(
				$original_item->product_id,
				$quantity,
				$original_item->weight,
				$original_item->dimensions,
				$original_item->declared_value,
				$original_item->sku
			);

			$grouped_items[] = $grouped_item;
			$total_weight   += $original_item->weight * $quantity;
			$total_value    += $original_item->declared_value * $quantity;

			// Calculate dimensions by taking max length/width and stacking height.
			$max_l    = max( $max_l, $original_item->dimensions->length );
			$max_w    = max( $max_w, $original_item->dimensions->width );
			$total_h += $original_item->dimensions->height * $quantity; // Stack items.
		}

		return new Package(
			$grouped_items,
			$total_weight,
			new Dimensions( $max_l, $max_w, $total_h ),
			$total_value,
			array(
				'strategy' => $this->get_key(),
			)
		);
	}
}
