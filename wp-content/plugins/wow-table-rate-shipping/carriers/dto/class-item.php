<?php
/**
 * Item DTO
 *
 * Represents a shipping item with product details for rate calculation.
 *
 * @package WTRS\Carriers\DTO
 */

namespace WTRS\Carriers\DTO;

defined( 'ABSPATH' ) || exit;

/**
 * Class Item
 *
 * Represents a shipping item with product details for rate calculation.
 */
class Item {

	/**
	 * Product ID.
	 *
	 * @var int
	 */
	public $product_id;

	/**
	 * Quantity of the product.
	 *
	 * @var int
	 */
	public $quantity;

	/**
	 * Weight of the product (per unit).
	 *
	 * @var float
	 */
	public $weight;

	/**
	 * Dimensions object representing the product's size.
	 *
	 * @var Dimensions
	 */
	public $dimensions;

	/**
	 * Declared value of the product.
	 *
	 * @var float
	 */
	public $declared_value;

	/**
	 * SKU (Stock Keeping Unit) of the product.
	 *
	 * @var string
	 */
	public $sku;

	/**
	 * Constructor.
	 *
	 * @param int        $product_id     Product ID.
	 * @param int        $quantity       Quantity of the product.
	 * @param float      $weight         Weight of the product (per unit).
	 * @param Dimensions $dimensions     Dimensions object for the product.
	 * @param float      $declared_value Declared value of the product.
	 * @param string     $sku            SKU of the product.
	 */
	public function __construct( int $product_id, int $quantity, float $weight, Dimensions $dimensions, float $declared_value, string $sku ) {
		$this->product_id     = $product_id;
		$this->quantity       = $quantity;
		$this->weight         = wc_get_weight( $weight, 'kg' ); // Normalize to kg.
		$this->dimensions     = $dimensions;
		$this->declared_value = $declared_value;
		$this->sku            = $sku;
	}
}
