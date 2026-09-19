<?php
/**
 * Rate DTO
 *
 * Represents a shipping rate for a carrier service.
 *
 * @package WTRS\Carriers\DTO
 */

namespace WTRS\Carriers\DTO;

defined( 'ABSPATH' ) || exit;

/**
 * Class Rate
 *
 * Represents a shipping rate for a carrier service.
 */
class Rate {
	/**
	 * The key identifying the carrier.
	 *
	 * @var string
	 */
	public $carrier_key;
	/**
	 * The code for the shipping service.
	 *
	 * @var string
	 */
	public $service_code;
	/**
	 * The name of the shipping service.
	 *
	 * @var string
	 */
	public $service_name;
	/**
	 * The unique method ID for the rate.
	 *
	 * @var string
	 */
	public $method_id;
	/**
	 * The cost of the shipping rate.
	 *
	 * @var float
	 */
	public $cost;
	/**
	 * The currency of the cost.
	 *
	 * @var string
	 */
	public $currency;
	/**
	 * The estimated delivery time.
	 *
	 * @var string|null
	 */
	public $delivery_time;
	/**
	 * The number of transit days.
	 *
	 * @var int|null
	 */
	public $transit_days;
	/**
	 * Additional details about the rate.
	 *
	 * @var array
	 */
	public $rate_details;
	/**
	 * Any warnings associated with the rate.
	 *
	 * @var array
	 */
	public $warnings;
	/**
	 * A hash for the rate based on key parameters.
	 *
	 * @var string
	 */
	public $hash;

	/**
	 * Constructor for Rate.
	 *
	 * @param string      $carrier_key   The carrier key.
	 * @param string      $service_code  The service code.
	 * @param string      $service_name  The service name.
	 * @param float       $cost          The cost of the rate.
	 * @param string|null $currency      The currency.
	 * @param string|null $delivery_time The delivery time.
	 * @param int|null    $transit_days  The transit days.
	 * @param array       $rate_details  Additional rate details.
	 * @param array       $warnings      Any warnings.
	 */
	public function __construct( string $carrier_key, string $service_code, string $service_name, float $cost, $currency, ?string $delivery_time, ?int $transit_days, array $rate_details = array(), array $warnings = array() ) {
		$this->carrier_key  = $carrier_key;
		$this->service_code = $service_code;
		$this->service_name = $service_name;
		$this->method_id    = 'wtrs_carrier_' . $carrier_key . '_' . $service_code;
		$this->cost         = $cost;
		if ( empty( $currency ) ) {
			$currency = get_woocommerce_currency();
		}
		$this->currency      = $currency;
		$this->delivery_time = $delivery_time;
		$this->transit_days  = $transit_days;
		$this->rate_details  = $rate_details;
		$this->warnings      = $warnings;
		$this->hash          = md5( $carrier_key . $service_code . $cost . $currency . ( $delivery_time ?? '' ) . ( $transit_days ?? '' ) );
	}
}
