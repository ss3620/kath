<?php
/**
 * Package DTO
 *
 * Represents a shipping package with items, weight, dimensions, declared value, and meta data.
 *
 * @package WTRS\Carriers\DTO
 */

namespace WTRS\Carriers\DTO;

defined( 'ABSPATH' ) || exit;

/**
 * Class Package
 *
 * Represents a shipping package with items, weight, dimensions, declared value, and meta data.
 */
class Package {

	/**
	 * List of items in the package.
	 *
	 * @var array
	 */
	public $items;

	/**
	 * Total weight of the package.
	 *
	 * @var float
	 */
	public $total_weight;

	/**
	 * Packed dimensions of the package.
	 *
	 * @var Dimensions
	 */
	public $packed_dimensions;

	/**
	 * Declared value of the package.
	 *
	 * @var float
	 */
	public $declared_value;

	/**
	 * Additional meta data for the package.
	 *
	 * @var array
	 */
	public $meta;

	/**
	 * Package constructor.
	 *
	 * @param array      $items             List of items in the package.
	 * @param float      $total_weight      Total weight of the package.
	 * @param Dimensions $packed_dimensions Packed dimensions of the package.
	 * @param float      $declared_value    Declared value of the package.
	 * @param array      $meta              Additional meta data (optional).
	 */
	public function __construct(
		array $items,
		float $total_weight,
		Dimensions $packed_dimensions,
		float $declared_value,
		array $meta = array()
	) {
		$this->items             = $items;
		$this->total_weight      = $total_weight;
		$this->packed_dimensions = $packed_dimensions;
		$this->declared_value    = $declared_value;
		$this->meta              = $meta;
	}

	/**
	 * Generate a unique signature for the package based on its properties.
	 *
	 * @return string MD5 hash signature.
	 */
	public function signature(): string {
		return md5(
			wp_json_encode(
				array(
					$this->total_weight,
					$this->packed_dimensions->length,
					$this->packed_dimensions->width,
					$this->packed_dimensions->height,
					$this->declared_value,
					count( $this->items ),
				)
			)
		);
	}
}
