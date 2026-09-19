<?php //phpcs:ignore
/**
 * Class Hooks
 */

namespace WTRS\Includes\Utils;

use WTRS\Includes\DB;
use WTRS\Includes\WowShippingMethod;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Hooks class.
 */
class Hooks {

	/**
	 * Constructor
	 */
	public function __construct() {
		// All Woo Hook Related Work goes here.
		add_filter( 'wtrs_get_allowed_html_tags', array( $this, 'allowed_html_tags' ), 10, 1 );

		add_filter( 'woocommerce_shipping_methods', array( $this, 'register_wtrs_method' ) );
		add_filter( 'woocommerce_shipping_zone_method_added', array( $this, 'handle_wtrs_method_added' ), 10, 3 );
		add_action( 'woocommerce_shipping_zone_method_status_toggled', array( $this, 'handle_wtrs_method_status_toggled' ), 10, 4 );
		add_filter( 'woocommerce_shipping_zone_method_deleted', array( $this, 'handle_wtrs_method_deleted' ), 10, 3 );
		add_action( 'woocommerce_delete_shipping_zone', array( $this, 'handle_wtrs_wc_zone_deleted' ), 10, 1 );

		// Classic cart/checkout: render extra meta (description, delivery time) under each rate.
		add_action( 'woocommerce_after_shipping_rate', array( $this, 'render_shipping_rate_meta' ), 10, 2 );

		// Mirror wtrs_packed_boxes / wtrs_packed_boxes_json from the shipping line item onto order-level meta so
		// any code/integration can read them via $order->get_meta( 'wtrs_packed_boxes' ) / 'wtrs_packed_boxes_json'
		// without knowing which line item carries them.
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'copy_packed_boxes_to_order_meta' ), 10, 1 );
		add_action( 'woocommerce_new_order', array( $this, 'copy_packed_boxes_to_order_meta' ), 10, 1 );
	}

	/**
	 * Copy the `wtrs_packed_boxes` and `wtrs_packed_boxes_json` shipping-line-item meta onto the order itself.
	 *
	 * @param int|\WC_Order $order Order ID or object.
	 * @return void
	 */
	public function copy_packed_boxes_to_order_meta( $order ) {
		$order = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order );

		if ( ! $order ) {
			return;
		}

		$meta_keys = array( 'wtrs_packed_boxes', 'wtrs_packed_boxes_json' );
		$to_copy   = array();

		foreach ( $meta_keys as $meta_key ) {
			if ( '' !== $order->get_meta( $meta_key, true ) ) {
				continue; // Already copied.
			}
			$to_copy[ $meta_key ] = true;
		}

		if ( empty( $to_copy ) ) {
			return;
		}

		foreach ( $order->get_items( 'shipping' ) as $item ) {
			foreach ( array_keys( $to_copy ) as $meta_key ) {
				$value = $item->get_meta( $meta_key, true );

				if ( '' !== $value && null !== $value ) {
					$order->update_meta_data( $meta_key, $value );
					unset( $to_copy[ $meta_key ] );
				}
			}

			if ( empty( $to_copy ) ) {
				break;
			}
		}

		$order->save_meta_data();
	}

	/**
	 * Register WowShipping Flexible Shipping method with WooCommerce.
	 *
	 * @param array $methods Registered shipping methods.
	 * @return array
	 */
	public function register_wtrs_method( $methods ) {
		$methods['wtrs_wc_method'] = WowShippingMethod::class;
		return $methods;
	}

	/**
	 * WIP. Add WTRS method if added from WC Zone page.
	 *
	 * @param int    $instance_id method instance id.
	 * @param string $method_id method id.
	 * @param int    $zone_id zone id.
	 * @return void
	 */
	public function handle_wtrs_method_added( $instance_id, $method_id, $zone_id ) {}

	/**
	 * Enable/disable WTRS method if toggled from WC Zone page.
	 *
	 * @param int    $instance_id method instance id.
	 * @param string $method_id method id.
	 * @param int    $zone_id zone id.
	 * @param int    $is_enabled is enabled.
	 * @return void
	 */
	public function handle_wtrs_method_status_toggled( $instance_id, $method_id, $zone_id, $is_enabled ) {
		if ( 'wtrs_wc_method' === $method_id ) {
			$db   = DB::get_instance();
			$rule = $db->get_shipping_rule_by_instance_id( $instance_id );
			if ( ! empty( $rule ) ) {
				$rule['publishMode'] = ( (bool) $is_enabled ) ? 'publish' : 'draft';
				$db->update_shipping_rule( $rule );
			}
		}
	}

	/**
	 * Delete WTRS method if deleted from WC Zone page.
	 *
	 * @param int    $instance_id method instance id.
	 * @param string $method_id method id.
	 * @param int    $zone_id zone id.
	 * @return void
	 */
	public function handle_wtrs_method_deleted( $instance_id, $method_id, $zone_id ) {
		if ( 'wtrs_wc_method' === $method_id ) {
			$db = DB::get_instance();
			$db->delete_shipping_rule( $instance_id, 'instance_id' );
		}
	}

	/**
	 * Delete WTRS method if deleted from WC Zone page.
	 *
	 * @param int $zone_id zone id.
	 * @return void
	 */
	public function handle_wtrs_wc_zone_deleted( $zone_id ) {
		$db    = DB::get_instance();
		$rules = $db->get_shipping_rules_by_zone_id( $zone_id );

		foreach ( $rules as $rule ) {
			$db->delete_shipping_rule( $rule['id'] );
		}
	}

	/**
	 * Echo extra meta for a shipping rate in classic cart/checkout templates.
	 *
	 * Runs after each shipping method radio item. Safe for themes using classic templates.
	 *
	 * @param \WC_Shipping_Rate $rate  The shipping rate object.
	 * @param int               $index Index within the package.
	 * @return void
	 */
	public function render_shipping_rate_meta( $rate, $index ) { // phpcs:ignore

		if ( ! $rate || ! is_object( $rate ) ) {
			return;
		}

		// Only render for our plugin-provided rates (we tag these with rule_id/meta in validate_and_filter_shipping_methods).
		$rule_id = $this->get_rate_meta_value( $rate, 'rule_id' );
		if ( empty( $rule_id ) ) {
			return;
		}

		$description   = $this->get_rate_meta_value( $rate, 'description' );
		$delivery_time = $this->get_rate_meta_value( $rate, 'delivery_time' );

		if ( empty( $description ) && empty( $delivery_time ) ) {
			return;
		}

		echo '<div class="wtrs-shipping-rate-extra" style="margin: 4px 0 0 26px;">'; // small indent under radio label.
		if ( ! empty( $description ) ) {
			// Allow basic HTML in description as authored by admin.
			echo '<div class="wtrs-rate-description">' . wp_kses_post( $description ) . '</div>';
		}
		if ( ! empty( $delivery_time ) ) {
			echo '<div class="wtrs-rate-delivery-time"><small>' . esc_html( $delivery_time ) . '</small></div>';
		}
		echo '</div>';
	}


	/**
	 * Safely retrieve a specific meta value from a WC_Shipping_Rate.
	 * WooCommerce stores shipping rate meta as an array of arrays with keys 'key' and 'value'.
	 *
	 * @param object $rate WC_Shipping_Rate instance.
	 * @param string $key  Meta key to fetch.
	 * @return mixed|string
	 */
	private function get_rate_meta_value( $rate, $key ) {
		if ( ! $rate || ! is_object( $rate ) || ! is_callable( array( $rate, 'get_meta_data' ) ) ) {
			return '';
		}

		$meta = $rate->get_meta_data();
		if ( empty( $meta ) ) {
			return '';
		}

		// Handle associative array shape.
		if ( isset( $meta[ $key ] ) ) {
			return $meta[ $key ];
		}

		// Handle list of [ 'key' => ..., 'value' => ... ] entries.
		foreach ( $meta as $entry ) {
			if ( is_array( $entry ) ) {
				if ( ( $entry['key'] ?? null ) === $key ) {
					return $entry['value'] ?? '';
				}
			} elseif ( is_object( $entry ) ) {
				// Be extra defensive in case objects are used.
				$e_key   = property_exists( $entry, 'key' ) ? $entry->key : ( is_callable( array( $entry, 'get_key' ) ) ? $entry->get_key() : null );
				$e_value = property_exists( $entry, 'value' ) ? $entry->value : ( is_callable( array( $entry, 'get_value' ) ) ? $entry->get_value() : null );
				if ( $e_key === $key ) {
					return $e_value ?? '';
				}
			}
		}

		return '';
	}

	/**
	 * Set Allowed Html
	 *
	 * @param array $extras Allowed htmls.
	 *
	 * @return array
	 */
	public function allowed_html_tags( $extras = array() ) {
		$allowed = array(
			'del'      => array(),
			'ins'      => array(),
			'select'   => array(
				'multiple' => true,
				'data-*'   => true,
			),
			'option'   => array(
				'value'  => true,
				'data-*' => true,
			),
			'strong'   => array(),
			'b'        => array(),
			'input'    => array(
				'data-*'       => true,
				'type'         => true,
				'value'        => true,
				'placeholder'  => true,
				'name'         => true,
				'id'           => true,
				'min'          => true,
				'max'          => true,
				'format'       => true,
				'class'        => true,
				'step'         => true,
				'disabled'     => true,
				'readonly'     => true,
				'required'     => true,
				'maxlength'    => true,
				'minlength'    => true,
				'pattern'      => true,
				'autocomplete' => true,
				'accept'       => true,
			),
			'textarea' => array(
				'data-*'       => true,
				'type'         => true,
				'value'        => true,
				'placeholder'  => true,
				'name'         => true,
				'id'           => true,
				'min'          => true,
				'max'          => true,
				'rows'         => true,
				'format'       => true,
				'class'        => true,
				'disabled'     => true,
				'readonly'     => true,
				'required'     => true,
				'maxlength'    => true,
				'minlength'    => true,
				'pattern'      => true,
				'autocomplete' => true,
				'accept'       => true,
			),
			'svg'      => array(
				'xmlns'        => true,
				'width'        => true,
				'height'       => true,
				'viewbox'      => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
			),
			'g'        => array(
				'fill'            => true,
				'stroke'          => true,
				'opacity'         => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-width'    => true,
				'clip-path'       => true,
			),
			'path'     => array(
				'd'               => true,
				'fill'            => true,
				'stroke'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-width'    => true,
				'clip-rule'       => true,
			),
			'rect'     => array(
				'rx'           => true,
				'width'        => true,
				'height'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
			),
			'defs'     => array(),
			'clipPath' => array(
				'id' => true,
			),
			'style'    => array(
				'id'     => true,
				'type'   => true,
				'media'  => true,
				'title'  => true,
				'scoped' => true,
				'data-*' => true,
			),
		);

		return array_merge( wp_kses_allowed_html( 'post' ), $allowed, $extras );
	}
}
