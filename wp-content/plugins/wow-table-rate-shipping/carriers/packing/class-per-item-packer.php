<?php
/**
 * Per Item Packer class.
 *
 * Packs each item into its own package.
 *
 * @package WTRS\Carriers\Packing
 */

namespace WTRS\Carriers\Packing;

defined( 'ABSPATH' ) || exit;

use WTRS\Carriers\DTO\Package;

/**
 * Class PerItemPacker
 */
class PerItemPacker implements PackerInterface {
	/**
	 * Get the key for this packer.
	 *
	 * @return string
	 */
	public function get_key(): string {
		return 'per_item';
	}

	/**
	 * Pack items into packages.
	 *
	 * @param array $items The items to pack.
	 * @param array $config Optional packer-specific configuration parameters.
	 * @return array
	 */
	public function pack( array $items, array $config = array() ): array {
		$packages = array();

		foreach ( $items as $item ) {
			for ( $i = 0; $i < $item->quantity; $i++ ) {
				$packages[] = new Package(
					array( $item ),
					$item->weight,
					$item->dimensions,
					$item->declared_value,
					array( 'strategy' => $this->get_key() )
				);
			}
		}

		return $packages;
	}
}
