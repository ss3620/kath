<?php // phpcs:ignore
/**
 * Single Package Packer class.
 *
 * Packs all items into a single package using 3D bin packing to
 * determine the actual envelope dimensions required.
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

/**
 * Class SinglePackagePacker
 *
 * Packs all items into a single package. Uses the native SaminYaser
 * BoxPacker packer against a generously-sized virtual box so items are
 * laid out in 3D and the resulting envelope reflects real packed
 * dimensions rather than a naive sum/max of individual item dimensions.
 */
class SinglePackagePacker implements PackerInterface {
	/**
	 * Get the key for this packer.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'single_package';
	}

	/**
	 * {@inheritDoc}
	 */
	public function pack( array $items, array $config = array() ): array {
		if ( empty( $items ) ) {
			return array();
		}

		try {
			$source_item_list = new ItemList();
			foreach ( $items as $item ) {
				if ( ! $item instanceof Item ) {
					continue;
				}

				for ( $i = 0; $i < $item->quantity; $i++ ) {
					$source_item_list->insert( new WtrsPackingItem( $item, $i ) );
				}
			}

			if ( $source_item_list->count() === 0 ) {
				return array();
			}

			$box_list = new BoxList();
			$box_list->insert( new VirtualSingleBox( VirtualSingleBox::envelope_size( $items ) ) );

			$packer = new Packer();
			$packer->setItems( $source_item_list );
			$packer->setBoxes( $box_list );
			$packed_boxes = $packer->pack();

			if ( $packed_boxes->count() === 0 ) {
				return array();
			}

			$packed_box = $packed_boxes->top();

			// Convert used envelope from mm back to cm.
			$dimensions = new Dimensions(
				$packed_box->getUsedLength() / 10,
				$packed_box->getUsedWidth() / 10,
				$packed_box->getUsedDepth() / 10,
				'cm'
			);

			$weight = 0;
			$value  = 0;

			foreach ( $items as $item ) {
				$weight += $item->weight * $item->quantity;
				$value  += $item->declared_value * $item->quantity;
			}

			return array(
				new Package(
					$items,
					$weight,
					$dimensions,
					$value,
					array( 'strategy' => $this->get_key() )
				),
			);
		} catch ( \Exception $e ) {
			return array();
		}
	}
}

/**
 * VirtualSingleBox - A virtual box used to pack all items together.
 *
 * Sized generously enough to hold every item, then handed to the native
 * BoxPacker packer; the resulting used envelope (not this box's own size)
 * is reported as the final package dimensions.
 */
// phpcs:ignore
class VirtualSingleBox implements \WTRS_Vendor\SaminYaser\BoxPackerLite\Box {

	/**
	 * Outer/inner width in millimeters.
	 *
	 * @var int
	 */
	private int $width;

	/**
	 * Outer/inner length in millimeters.
	 *
	 * @var int
	 */
	private int $length;

	/**
	 * Outer/inner depth in millimeters.
	 *
	 * @var int
	 */
	private int $depth;

	/**
	 * Constructor.
	 *
	 * @param array $size Width/length/depth in centimeters, keyed 'width', 'length', 'depth'.
	 */
	public function __construct( array $size ) {
		// Convert cm to mm and ensure a safe non-zero minimum on each axis.
		$this->width  = max( 10, (int) round( $size['width'] * 10 ) );
		$this->length = max( 10, (int) round( $size['length'] * 10 ) );
		$this->depth  = max( 10, (int) round( $size['depth'] * 10 ) );
	}

	/**
	 * Determine a virtual-box size guaranteed to fit all items.
	 *
	 * Sized as the sum of every item's largest extent per axis, so the
	 * native packer always has room to place everything regardless of
	 * orientation; the resulting used envelope (not this box size) is what
	 * gets reported as the package dimensions.
	 *
	 * @param Item[] $items Items to be packed, used to size the box.
	 * @return array{width: float, length: float, depth: float}
	 */
	public static function envelope_size( array $items ): array {
		$width  = 0;
		$length = 0;
		$depth  = 0;

		foreach ( $items as $item ) {
			if ( ! $item instanceof Item ) {
				continue;
			}

			$width  += $item->dimensions->width * $item->quantity;
			$length += $item->dimensions->length * $item->quantity;
			$depth  += $item->dimensions->height * $item->quantity;
		}

		return array(
			'width'  => $width,
			'length' => $length,
			'depth'  => $depth,
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function getReference(): string {
		return 'virtual_single_box';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getOuterWidth(): int {
		return $this->width;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getOuterLength(): int {
		return $this->length;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getOuterDepth(): int {
		return $this->depth;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getEmptyWeight(): int {
		return 0;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getInnerWidth(): int {
		return $this->width;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getInnerLength(): int {
		return $this->length;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getInnerDepth(): int {
		return $this->depth;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMaxWeight(): int {
		return PHP_INT_MAX;
	}
}
