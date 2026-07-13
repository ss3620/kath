<?php
/**
 * Shipment Request DTO
 *
 * Represents a shipment request for carrier rate calculation.
 *
 * @package WTRS\Carriers\DTO
 */

namespace WTRS\Carriers\DTO;

defined( 'ABSPATH' ) || exit;

/**
 * Class ShipmentRequest
 *
 * Data Transfer Object for shipment requests.
 */
class ShipmentRequest {

	/**
	 * Origin location details.
	 *
	 * @var array
	 */
	public $origin_location;

	/**
	 * Destination location details.
	 *
	 * @var array
	 */
	public $destination_location;

	/**
	 * List of packages in the shipment.
	 *
	 * @var \WTRS\Carriers\DTO\Package[]
	 */
	public $packages;

	/**
	 * Currency for the shipment.
	 *
	 * @var string
	 */
	public $currency;

	/**
	 * Cart subtotal.
	 *
	 * @var float
	 */
	public $cart_subtotal;

	/**
	 * Cart tax amount.
	 *
	 * @var float
	 */
	public $cart_tax;

	/**
	 * Applied coupon codes.
	 *
	 * @var array
	 */
	public $coupon_codes;

	/**
	 * Request context information.
	 *
	 * @var string
	 */
	public $request_context;

	/**
	 * Constructor.
	 *
	 * @param array                        $origin_location      Origin location details.
	 * @param array                        $destination_location Destination location details.
	 * @param \WTRS\Carriers\DTO\Package[] $packages             List of packages.
	 * @param string                       $currency             Currency.
	 * @param float                        $cart_subtotal        Cart subtotal.
	 * @param float                        $cart_tax             Cart tax.
	 * @param array                        $coupon_codes         Applied coupon codes.
	 * @param string                       $request_context      Request context.
	 */
	public function __construct( $origin_location, $destination_location, $packages, $currency, $cart_subtotal, $cart_tax, $coupon_codes, $request_context ) {
		$this->origin_location      = $origin_location;
		$this->destination_location = $destination_location;
		$this->packages             = $packages;
		$this->currency             = $currency;
		$this->cart_subtotal        = $cart_subtotal;
		$this->cart_tax             = $cart_tax;
		$this->coupon_codes         = $coupon_codes;
		$this->request_context      = $request_context;
	}

	/**
	 * Generate a hash for the shipment request.
	 *
	 * @return string MD5 hash of the request.
	 */
	public function hash(): string {
		return md5( wp_json_encode( array( $this->origin_location, $this->destination_location, array_map( fn( $p ) => $p->signature(), $this->packages ), $this->currency, $this->request_context ) ) );
	}
}
