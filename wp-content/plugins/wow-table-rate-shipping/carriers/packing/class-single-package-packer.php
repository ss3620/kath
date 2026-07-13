<?php
/**
 * Single Package Packer class.
 *
 * Packs all items into a single package.
 *
 * @package WTRS\Carriers\Packing
 */

namespace WTRS\Carriers\Packing;

defined( 'ABSPATH' ) || exit;

use WTRS\Carriers\DTO\Package;
use WTRS\Carriers\DTO\Dimensions;

/**
 * Class SinglePackagePacker
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
		$weight = 0;
		$value  = 0;
		$max_l  = 0;
		$max_w  = 0;
		$max_h  = 0;

		foreach ( $items as $item ) {
			$weight += $item->weight * $item->quantity;
			$value  += $item->declared_value * $item->quantity;
			$max_l   = max( $max_l, $item->dimensions->length );
			$max_w   = max( $max_w, $item->dimensions->width );
			$max_h  += $item->dimensions->height * $item->quantity;
		}

		return array(
			new Package(
				$items,
				$weight,
				new Dimensions( $max_l, $max_w, $max_h ),
				$value,
				array( 'strategy' => $this->get_key() )
			),
		);
	}
}
