<?php
/**
 * Interface for packing strategies.
 *
 * @package WTRS\Carriers\Packing
 */

namespace WTRS\Carriers\Packing;

defined( 'ABSPATH' ) || exit;

interface PackerInterface {
	/**
	 * Get the key for this packer.
	 *
	 * @return string
	 */
	public function get_key(): string;

	/**
	 * Pack items into packages.
	 *
	 * @param array $items The items to pack.
	 * @param array $config Optional packer-specific configuration parameters.
	 * @return array
	 */
	public function pack( array $items, array $config = array() ): array;
}
