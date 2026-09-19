<?php // phpcs:ignore
/**
 * Wow Flexible Shipping Method.
 *
 * @package WTRS
 */

namespace WTRS\Includes;

use Automattic\WooCommerce\Enums\ProductTaxStatus;
use WC_Tax;
use WTRS\Includes\MethodHelper;
use WTRS\Includes\RuleVisibility;

defined( 'ABSPATH' ) || exit;

/**
 * WoWShippingClass
 */
class WowShippingMethod extends \WC_Shipping_Method {

	/**
	 * Rule
	 *
	 * @var array
	 */
	private $rule;

	/**
	 * Method
	 *
	 * @var array
	 */
	private $method;

	/**
	 * DB instance
	 *
	 * @var \WTRS\Includes\DB
	 */
	private $db;

	/**
	 * Method helper instance
	 *
	 * @var \WTRS\Includes\MethodHelper
	 */
	private $method_helper;

	/**
	 * Rule id
	 *
	 * @var string
	 */
	private $rule_id;

	// phpcs:disable Squiz.Commenting.FunctionComment.Missing
  // phpcs:ignore Squiz.Commenting.FunctionComment.Missing
   public static function build_rate_payload( $rule, $instance_id = 0, $rate_id = '' ) {
		if ( empty( $rule ) || ! RuleVisibility::is_viewable( $rule ) ) {
			return null;
		}

		$method_helper = new MethodHelper();
		$method        = $method_helper->validate_shipping_methods( $rule['shippingMethods'] )[0] ?? null;

		if ( empty( $method ) ) {
			return null;
		}

		$resolved_rate_id = $rate_id;
		if ( empty( $resolved_rate_id ) ) {
			$resolved_rate_id = $instance_id
				? $method['method_id'] . $instance_id
				: 'wtrs_global_' . ( $rule['id'] ?? 'rule' ) . '_' . $method['method_id'];
		}

		$meta_data = array(
			'method_type'   => $method['method_type'],
			'description'   => $method['description'],
			'delivery_time' => $method['delivery_time'],
			'rule_id'       => $rule['id'] ?? '',
			'rate_id'       => $resolved_rate_id,
		);

		if ( isset( $method['rate_details'] ) && is_array( $method['rate_details'] ) ) {
			foreach ( $method['rate_details'] as $key => $value ) {
				$meta_data[ $key ] = $value;
			}
		}

		$no_tax     = 'none' === ( $rule['tax']['status'] ?? 'none' ); // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
		$cost       = $method['cost']; // phpcs:ignore Generic.Formatting.MultipleStatementAlignment.NotSameWarning
		$tax_status = $no_tax ? ProductTaxStatus::NONE : ProductTaxStatus::TAXABLE;

		if ( self::has_free_shipping_coupon() ) {
			$cost = 0.0;
       }
      if ( ! $no_tax && ! empty( $rule['tax']['included'] ) ) {
			$tax_amount = WC_Tax::calc_shipping_tax( $cost, WC_Tax::get_shipping_tax_rates() );
			$cost      += array_sum( $tax_amount );
			$tax_status = ProductTaxStatus::NONE;
			$no_tax     = true;
			}

			return array(
				'method'     => $method,
				'tax_status' => $tax_status,
				'rate'       => array(
					'id'        => $resolved_rate_id,
					'label'     => $method['label'],
					'cost'      => $cost,
					'taxes'     => $no_tax ? false : '',
					'meta_data' => $meta_data,
				),
			);
	}
  /**
     * Check whether an applied coupon enables free shipping.
   *
  * @return bool
     */
    private static function has_free_shipping_coupon() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

     $cart = WC()->cart;
		if ( ! method_exists( $cart, 'get_coupons' ) ) {
			return false;
		}

     foreach ( $cart->get_coupons() as $coupon ) {
			if ( $coupon && method_exists( $coupon, 'get_free_shipping' ) && $coupon->get_free_shipping() ) {
				return true;
           }
		}

     return false;
  }
  // phpcs:enable Squiz.Commenting.FunctionComment.Missing

  /**
     * Constructor
  *
																								 *
																								 * @param integer $instance_id Instance ID.
																								 */
	public function __construct( $instance_id = 0 ) {
		$this->id                 = 'wtrs_wc_method';
		$this->instance_id        = absint( $instance_id );
		$this->method_title       = __( 'Flexible Shipping (WowShipping)', 'wow-table-rate-shipping' );
		$this->method_description = __( 'Set flexible Table Rate shipping based on 30+ conditions, including cart weight, cart quantity, price, and more.', 'wow-table-rate-shipping' );
		$this->title              = $this->method_title;

		$this->supports = array(
			'shipping-zones',
			'instance-settings',
			'global-instance',
		);

		$this->init_wtrs_data();
	}

	/**
	 * Load everything related to WTRS
	 *
	 * @return void
	 */
	private function init_wtrs_data() {
		$this->method_helper = new MethodHelper();
		$this->db            = DB::get_instance();
		$this->rule          = $this->db->get_shipping_rule_by_instance_id( $this->instance_id );
		$this->rule_id       = $this->rule['id'] ?? '';

		if ( ! empty( $this->rule['generalSettings']['scenarioName'] ) ) {
			$this->title = $this->rule['generalSettings']['scenarioName'];
		}

		if ( ! empty( $this->rule ) ) {
			$no_tax = 'none' === ( $this->rule['tax']['status'] ?? '' );

			// Tax is disabled.
			if ( $no_tax ) {
				$this->tax_status = ProductTaxStatus::NONE;
			}

			// Tax is enabled but should be included in the cost.
			if ( ! $no_tax && ! empty( $this->rule['tax']['included'] ) ) {
				$this->tax_status = ProductTaxStatus::NONE;
			}
		}

		add_action( 'woocommerce_shipping_method_add_rate', array( $this, 'wtrs_modify_rate' ), 10, 3 );
	}

	/**
	 * Calculate shipping rates.
	 *
	 * @param array $package Shipping package.
	 * @return void
	 */
	public function calculate_shipping( $package = array() ) {
		if ( ! $this->rule ) {
			return;
		}

		$payload = self::build_rate_payload( $this->rule, $this->instance_id );

		if ( empty( $payload ) ) {
			return;
		}

		$this->method     = $payload['method'];
		$this->tax_status = $payload['tax_status'];

		$this->add_rate( $payload['rate'] );
	}

	/**
	 * Calculate shipping rates.
	 *
	 * @param \WC_Shipping_Rate $rate Rate object.
	 * @param array             $args Args.
	 * @param string            $this_class Shipping class.
	 * @return \WC_Shipping_Rate
	 */
	public function wtrs_modify_rate( $rate, $args, $this_class ) {
		$rate_id = ( $this->method['method_id'] ?? 'null' ) . $this->instance_id;

		if ( $rate->get_id() === $rate_id ) {
			$rate->set_description( $this->method['description'] );
			$rate->set_delivery_time( $this->method['delivery_time'] );
		}

		return $rate;
	}

	/**
	 * Add HTML
	 *
	 * @return void
	 */
	public function admin_options() {
		// TODO @samin: Add spinner.
		echo '<div id="wtrs-dashboard-wrap"></div>';
	}

	/**
	 * No location set fix.
	 *
	 * @param array $package Shipping package.
	 * @return bool
	 */
	public function is_available( $package ): bool {
		return $this->is_enabled();
	}

	/**
	 * Compatibility for old woo versions.
	 *
	 * @return integer
	 */
	public function get_instance_id(): int {
		return $this->instance_id ? $this->instance_id : -1;
	}
}
