<?php // phpcs:ignore
/**
 * Box Packer class.
 *
 * Packs items into configurable boxes using 3D bin packing algorithms.
 *
 * @package WTRS\Carriers\Packing
 */

namespace WTRS\Carriers\Packing;

defined( 'ABSPATH' ) || exit;

use WTRS\Carriers\DTO\Package;
use WTRS\Carriers\DTO\Dimensions;
use WTRS\Carriers\DTO\Item;
use WTRS_Vendor\SaminYaser\BoxPackerLite\Packer;
use WTRS_Vendor\SaminYaser\BoxPackerLite\BoxList;
use WTRS_Vendor\SaminYaser\BoxPackerLite\ItemList;
use WTRS_Vendor\SaminYaser\BoxPackerLite\PackedItem;

/**
 * Class BoxPacker
 *
 * Implements 3D bin packing using the SaminYaser BoxPacker library.
 * This packer uses real 3D constraint solving to optimally pack items
 * into configurable boxes while respecting weight limits and padding.
 */
class BoxPacker implements PackerInterface {

	/**
	 * Get the key identifier for this packer strategy.
	 *
	 * @return string The unique key for this packer type.
	 */
	public function get_key(): string {
		return 'box';
	}

	/**
	 * Pack items into packages using 3D bin packing algorithms.
	 *
	 * This method uses the SaminYaser BoxPacker library to perform true 3D bin packing,
	 * taking into account item dimensions, weights, box constraints, and padding.
	 * If packing fails or no valid boxes are configured, falls back to simple packing.
	 *
	 * @param array $items  Array of WTRS Item objects to pack.
	 * @param array $config Configuration array containing packBoxes settings.
	 * @return array Array of WTRS Package objects containing the packed items.
	 */
	public function pack( array $items, array $config = array() ): array {
		// Validate configuration and extract valid box definitions.
		$pack_boxes = $this->validate_and_parse_boxes( $config );

		if ( empty( $pack_boxes ) ) {
			return array();
		}

		try {
			// Create SaminYaser BoxList from configuration.
			$box_list = $this->create_box_list( $pack_boxes );

			// Create SaminYaser ItemList from WTRS items.
			$item_list = $this->create_item_list( $items );

			if ( $item_list->count() === 0 ) {
				return array();
			}

			// Execute 3D bin packing algorithm.
			$packer = new Packer();
			$packer->setItems( $item_list );
			$packer->setBoxes( $box_list );
			$packed_boxes = $packer->pack();

			// Convert results back to WTRS Package objects.
			return $this->convert_packed_boxes_to_packages( $packed_boxes, $pack_boxes );

		} catch ( \Exception $e ) {
			return array();
		}
	}

	/**
	 * Validate and parse box configurations from config array.
	 *
	 * Extracts packBoxes from config, validates dimensions and weights,
	 * and returns an array of valid box configurations ready for packing.
	 *
	 * @param array $config Configuration array containing packBoxes.
	 * @return array Array of valid box configurations.
	 */
	private function validate_and_parse_boxes( array $config ): array {
		$pack_boxes = $config['packBoxes'] ?? array();

		if ( empty( $pack_boxes ) || ! is_array( $pack_boxes ) ) {
			return array();
		}

		$valid_boxes = array();

		foreach ( $pack_boxes as $box ) {
			if ( ! is_array( $box ) ) {
				continue;
			}

			// Parse and validate box dimensions and weights.
			$length     = $this->parse_positive_float( $box['length'] ?? '' );
			$width      = $this->parse_positive_float( $box['width'] ?? '' );
			$height     = $this->parse_positive_float( $box['height'] ?? '' );
			$box_weight = $this->parse_positive_float( $box['boxWeight'] ?? '0' );
			$max_weight = $this->parse_positive_float( $box['boxMaxWeight'] ?? '' );
			$padding    = $this->parse_positive_float( $box['padding'] ?? '0' );

			// Skip boxes with invalid dimensions or weights.
			if ( $length <= 0 || $width <= 0 || $height <= 0 || $max_weight <= 0 ) {
				continue;
			}

			// Max weight must be greater than the box's own weight.
			if ( $max_weight <= $box_weight ) {
				continue;
			}

			$valid_boxes[] = array(
				'id'         => $box['id'] ?? uniqid(),
				'length'     => $length,
				'width'      => $width,
				'height'     => $height,
				'box_weight' => $box_weight,
				'max_weight' => $max_weight,
				'padding'    => $padding,
			);
		}

		return $valid_boxes;
	}

	/**
	 * Parse a string value to a positive float.
	 *
	 * Converts string input to float and ensures the result is non-negative.
	 * Returns 0 for invalid or negative values.
	 *
	 * @param string $value String value to parse.
	 * @return float Parsed positive float value or 0 if invalid.
	 */
	private function parse_positive_float( string $value ): float {
		$parsed = (float) trim( $value );
		return max( 0, $parsed );
	}

	/**
	 * Create SaminYaser BoxList from validated box configurations.
	 *
	 * Converts WTRS box configurations into SaminYaser BoxList by creating
	 * PackingBox adapter instances for each valid box configuration.
	 *
	 * @param array $pack_boxes Array of valid box configurations.
	 * @return BoxList SaminYaser BoxList object ready for packing.
	 */
	private function create_box_list( array $pack_boxes ): BoxList {
		$box_list = new BoxList();

		foreach ( $pack_boxes as $box_config ) {
			$box_list->insert( new PackingBox( $box_config ) );
		}

		return $box_list;
	}

	/**
	 * Create SaminYaser ItemList from WTRS items, expanding by quantity.
	 *
	 * Converts WTRS Item objects into SaminYaser ItemList by creating WtrsPackingItem
	 * adapter instances. Items with quantity > 1 are expanded into separate
	 * WtrsPackingItem instances for optimal packing algorithms.
	 *
	 * @param array $items Array of WTRS Item objects to convert.
	 * @return ItemList SaminYaser ItemList object ready for packing.
	 */
	private function create_item_list( array $items ): ItemList {
		$item_list = new ItemList();

		foreach ( $items as $item ) {
			if ( ! $item instanceof Item ) {
				continue;
			}

			// Add each quantity as a separate item for optimal packing.
			for ( $i = 0; $i < $item->quantity; $i++ ) {
				$item_list->insert( new WtrsPackingItem( $item, $i ) );
			}
		}

		return $item_list;
	}

	/**
	 * Convert SaminYaser PackedBoxList to WTRS Package objects.
	 *
	 * Transforms the results from SaminYaser packer back into WTRS Package objects,
	 * grouping items by product ID to restore original quantities and calculating
	 * totals including box weights.
	 *
	 * @param \WTRS_Vendor\SaminYaser\BoxPackerLite\PackedBoxList $packed_boxes SaminYaser packed box results.
	 * @param array                                               $pack_boxes   Original box configurations for reference.
	 * @return array Array of WTRS Package objects.
	 */
	private function convert_packed_boxes_to_packages( $packed_boxes, array $pack_boxes ): array {
		$packages = array();

		foreach ( $packed_boxes as $packed_box ) {
			$box           = $packed_box->getBox();
			$items         = $packed_box->getItems();
			$box_reference = $box->getReference();
			$box_config    = $this->find_box_config_by_id( $box_reference, $pack_boxes );

			if ( ! $box_config ) {
				continue;
			}

			// Collect items and calculate package totals.
			$package_items = array();
			$total_weight  = 0;
			$total_value   = 0;
			$item_map      = array();

			foreach ( $items as $original_item ) {
				if ( $original_item instanceof PackedItem ) {
					$packing_item = $original_item->getItem();
					if ( $packing_item instanceof WtrsPackingItem ) {
						$wtrs_item = $packing_item->getOriginalItem();
						$item_id   = $wtrs_item->product_id;

						// Group items by product ID to restore original quantities.
						if ( ! isset( $item_map[ $item_id ] ) ) {
							$item_map[ $item_id ] = array(
								'item'     => $wtrs_item,
								'quantity' => 0,
							);
						}
						++$item_map[ $item_id ]['quantity'];

						$total_weight += $wtrs_item->weight;
						$total_value  += $wtrs_item->declared_value;
					}
				}
			}

			// Create items with restored quantities.
			foreach ( $item_map as $item_data ) {
				$item            = clone $item_data['item'];
				$item->quantity  = $item_data['quantity'];
				$package_items[] = $item;
			}

			// Create package using box dimensions.
			$package_dimensions = new Dimensions(
				$box_config['length'],
				$box_config['width'],
				$box_config['height']
			);

			$packages[] = new Package(
				$package_items,
				$total_weight + $box_config['box_weight'], // Include the box's own weight.
				$package_dimensions,
				$total_value,
				array(
					'strategy' => $this->get_key(),
				)
			);
		}

		return $packages;
	}

	/**
	 * Find box configuration by ID reference.
	 *
	 * Searches through the pack_boxes array to find a box configuration
	 * matching the given ID reference.
	 *
	 * @param string $box_id     Box ID to search for.
	 * @param array  $pack_boxes Array of box configurations to search.
	 * @return array|null Box configuration array or null if not found.
	 */
	private function find_box_config_by_id( string $box_id, array $pack_boxes ): ?array {
		foreach ( $pack_boxes as $box_config ) {
			if ( $box_config['id'] === $box_id ) {
				return $box_config;
			}
		}
		return null;
	}
}

/**
 * PackingBox - Adapter class implementing SaminYaser Box interface.
 *
 * Converts WTRS box configuration arrays into objects compatible with
 * the SaminYaser BoxPacker library. Handles unit conversions (cm to mm, kg to grams)
 * and padding calculations for inner dimensions.
 */
// phpcs:ignore
class PackingBox implements \WTRS_Vendor\SaminYaser\BoxPackerLite\Box {

	/**
	 * Box reference identifier.
	 *
	 * @var string
	 */
	private string $reference;

	/**
	 * Outer width of the box in millimeters.
	 *
	 * @var int
	 */
	private int $outer_width;

	/**
	 * Outer length of the box in millimeters.
	 *
	 * @var int
	 */
	private int $outer_length;

	/**
	 * Outer depth (height) of the box in millimeters.
	 *
	 * @var int
	 */
	private int $outer_depth;

	/**
	 * Empty weight of the box itself in grams.
	 *
	 * @var int
	 */
	private int $empty_weight;

	/**
	 * Inner width of the box in millimeters (accounting for padding).
	 *
	 * @var int
	 */
	private int $inner_width;

	/**
	 * Inner length of the box in millimeters (accounting for padding).
	 *
	 * @var int
	 */
	private int $inner_length;

	/**
	 * Inner depth (height) of the box in millimeters (accounting for padding).
	 *
	 * @var int
	 */
	private int $inner_depth;

	/**
	 * Maximum weight the box can hold in grams (including its own weight).
	 *
	 * @var int
	 */
	private int $max_weight;

	/**
	 * Constructor.
	 *
	 * Initializes the PackingBox from a WTRS box configuration array.
	 * Performs unit conversions and calculates inner dimensions based on padding.
	 *
	 * @param array $box_config Box configuration array with dimensions and weights.
	 */
	public function __construct( array $box_config ) {
		$this->reference = $box_config['id'];

		// Convert cm to mm (SaminYaser requires millimeters).
		$this->outer_length = (int) round( $box_config['length'] * 10 );
		$this->outer_width  = (int) round( $box_config['width'] * 10 );
		$this->outer_depth  = (int) round( $box_config['height'] * 10 );

		// Convert kg to grams (SaminYaser requires grams).
		$this->empty_weight = (int) round( $box_config['box_weight'] * 1000 );
		$this->max_weight   = (int) round( $box_config['max_weight'] * 1000 );

		// Calculate inner dimensions accounting for padding on all sides.
		$padding_mm         = (int) round( $box_config['padding'] * 10 );
		$this->inner_length = max( 1, $this->outer_length - ( $padding_mm * 2 ) );
		$this->inner_width  = max( 1, $this->outer_width - ( $padding_mm * 2 ) );
		$this->inner_depth  = max( 1, $this->outer_depth - ( $padding_mm * 2 ) );
	}

	/**
	 * Get the reference identifier for this box type.
	 *
	 * @return string Box reference identifier.
	 */
	public function getReference(): string {
		return $this->reference;
	}

	/**
	 * Get the outer width of the box in millimeters.
	 *
	 * @return int Outer width in mm.
	 */
	public function getOuterWidth(): int {
		return $this->outer_width;
	}

	/**
	 * Get the outer length of the box in millimeters.
	 *
	 * @return int Outer length in mm.
	 */
	public function getOuterLength(): int {
		return $this->outer_length;
	}

	/**
	 * Get the outer depth (height) of the box in millimeters.
	 *
	 * @return int Outer depth in mm.
	 */
	public function getOuterDepth(): int {
		return $this->outer_depth;
	}

	/**
	 * Get the empty weight of the box itself in grams.
	 *
	 * @return int Empty box weight in grams.
	 */
	public function getEmptyWeight(): int {
		return $this->empty_weight;
	}

	/**
	 * Get the inner width of the box in millimeters (after padding).
	 *
	 * @return int Inner width in mm.
	 */
	public function getInnerWidth(): int {
		return $this->inner_width;
	}

	/**
	 * Get the inner length of the box in millimeters (after padding).
	 *
	 * @return int Inner length in mm.
	 */
	public function getInnerLength(): int {
		return $this->inner_length;
	}

	/**
	 * Get the inner depth (height) of the box in millimeters (after padding).
	 *
	 * @return int Inner depth in mm.
	 */
	public function getInnerDepth(): int {
		return $this->inner_depth;
	}

	/**
	 * Get the maximum weight the box can hold in grams.
	 *
	 * @return int Maximum weight capacity in grams.
	 */
	public function getMaxWeight(): int {
		return $this->max_weight;
	}
}

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
