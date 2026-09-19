<?php // phpcs:ignore
/**
 * WTRS Packing Item class.
 *
 * @package WTRS\Carriers\Packing
 */

namespace WTRS\Carriers\Packing;

defined( 'ABSPATH' ) || exit;

use WTRS\Carriers\DTO\Item;

/**
 * WtrsPackingItem - Adapter class implementing SaminYaser Item interface.
 *
 * Converts WTRS Item objects into objects compatible with the SaminYaser BoxPacker
 * library. Handles unit conversions and maintains reference to the original item.
 */
// phpcs:ignore
class WtrsPackingItem implements \WTRS_Vendor\SaminYaser\BoxPackerLite\Item {

	/**
	 * Item description for identification.
	 *
	 * @var string
	 */
	private string $description;

	/**
	 * Item width in millimeters.
	 *
	 * @var int
	 */
	private int $width;

	/**
	 * Item length in millimeters.
	 *
	 * @var int
	 */
	private int $length;

	/**
	 * Item depth (height) in millimeters.
	 *
	 * @var int
	 */
	private int $depth;

	/**
	 * Item weight in grams.
	 *
	 * @var int
	 */
	private int $weight;

	/**
	 * Reference to the original WTRS Item object.
	 *
	 * @var Item
	 */
	private Item $original_item;

	/**
	 * Sequence number for items with multiple quantities.
	 *
	 * @var int
	 */
	private int $sequence;

	/**
	 * Constructor.
	 *
	 * Initializes the WtrsPackingItem from a WTRS Item object.
	 * Performs unit conversions and creates unique description.
	 *
	 * @param Item $item     WTRS Item object to convert.
	 * @param int  $sequence Sequence number for multiple quantities.
	 */
	public function __construct( Item $item, int $sequence = 0 ) {
		$this->original_item = $item;
		$this->sequence      = $sequence;
		$this->description   = $item->sku . '_' . $sequence;

		// Convert cm to mm (SaminYaser requires millimeters).
		$this->length = (int) round( $item->dimensions->length * 10 );
		$this->width  = (int) round( $item->dimensions->width * 10 );
		$this->depth  = (int) round( $item->dimensions->height * 10 );

		// Convert kg to grams (SaminYaser requires grams).
		$this->weight = (int) round( $item->weight * 1000 );
	}

	/**
	 * Get the item description for identification.
	 *
	 * @return string Item description.
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * Get the item width in millimeters.
	 *
	 * @return int Item width in mm.
	 */
	public function getWidth(): int {
		return $this->width;
	}

	/**
	 * Get the item length in millimeters.
	 *
	 * @return int Item length in mm.
	 */
	public function getLength(): int {
		return $this->length;
	}

	/**
	 * Get the item depth (height) in millimeters.
	 *
	 * @return int Item depth in mm.
	 */
	public function getDepth(): int {
		return $this->depth;
	}

	/**
	 * Get the item weight in grams.
	 *
	 * @return int Item weight in grams.
	 */
	public function getWeight(): int {
		return $this->weight;
	}

	/**
	 * Get whether this item should be kept flat.
	 *
	 * Returns false to allow the packer to optimize placement by rotating
	 * items in any orientation for optimal packing efficiency.
	 * This is compatible with BoxPacker v3.12 interface.
	 *
	 * @return bool Whether the item must be kept flat (false = can rotate freely).
	 */
	public function getKeepFlat(): bool {
		return false;
	}

	/**
	 * Get the original WTRS Item object.
	 *
	 * Used to restore item properties when converting back to WTRS packages.
	 *
	 * @return Item Original WTRS Item object.
	 */
	public function getOriginalItem(): Item {
		return $this->original_item;
	}
}
