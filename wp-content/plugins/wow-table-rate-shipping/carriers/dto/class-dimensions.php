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
	 * @param float       $length    The length of the item.
	 * @param float       $width     The width of the item.
	 * @param float       $height    The height of the item.
	 * @param string|null $from_unit Source dimension unit. Defaults to WooCommerce store unit.
	 */
	public function __construct( float $length, float $width, float $height, ?string $from_unit = null ) {
		$this->length = null === $from_unit ? wc_get_dimension( $length, 'cm' ) : wc_get_dimension( $length, 'cm', $from_unit );
		$this->width  = null === $from_unit ? wc_get_dimension( $width, 'cm' ) : wc_get_dimension( $width, 'cm', $from_unit );
		$this->height = null === $from_unit ? wc_get_dimension( $height, 'cm' ) : wc_get_dimension( $height, 'cm', $from_unit );
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
