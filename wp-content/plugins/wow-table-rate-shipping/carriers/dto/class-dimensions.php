<?php // phpcs:ignore
namespace WTRS\Carriers\DTO;
defined( 'ABSPATH' ) || exit;

/**
 * Class Dimensions
 *
 * Represents the dimensions of a package or item, including length, width, height, and unit.
 *
 * @package WTRS\Carriers\DTO
 */
class Dimensions {
	/**
	 * The length of the item.
	 *
	 * @var float
	 */
	public $length;

	/**
	 * The width of the item.
	 *
	 * @var float
	 */
	public $width;

	/**
	 * The height of the item.
	 *
	 * @var float
	 */
	public $height;

	/**
	 * Constructor for Dimensions.
	 *
	 * @param float $length The length of the item.
	 * @param float $width  The width of the item.
	 * @param float $height The height of the item.
	 */
	public function __construct( float $length, float $width, float $height ) {
		$this->length = wc_get_dimension( $length, 'cm' );
		$this->width  = wc_get_dimension( $width, 'cm' );
		$this->height = wc_get_dimension( $height, 'cm' );
	}

	/**
	 * Calculate the volume of the item.
	 *
	 * @return float The calculated volume.
	 */
	public function volume(): float {
		return $this->length * $this->width * $this->height;
	}
}
