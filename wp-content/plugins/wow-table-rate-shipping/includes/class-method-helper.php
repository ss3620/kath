<?php //phpcs:ignore
/**
 * Shipping Method Validation and Management.
 *
 * @package WTRS\Shipping
 */

namespace WTRS\Includes;

use WTRS\Carriers\LiveRateManager;
use WTRS\Includes\DB;
use WTRS\Includes\Utils\Flags;

defined( 'ABSPATH' ) || exit;

/**
 * Shipping validation and method management class.
 */
class MethodHelper {

	/**
	 * Cost passed to [fee] shortcode during cost evaluation.
	 *
	 * @var string
	 */
	private $fee_cost = '';

	/**
	 * Validate shipping methods based on cart and criteria.
	 *
	 * @param array $shipping_methods Array of shipping method configurations.
	 * @return array Array of validated shipping methods with costs.
	 */
	public function validate_shipping_methods( $shipping_methods ) {
		$validated_methods = array();

		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return $validated_methods;
		}

		$settings = DB::get_instance()->get_settings();

		// Show method to admin only in debug mode.
		if ( ! empty( $settings['debugMode'] ) && ! Flags::is_pro_or_can_preview() ) {
			return $validated_methods;
		}

		foreach ( $shipping_methods as $method ) {
			if ( ! isset( $method['status'] ) || true !== $method['status'] ) {
				continue; // Skip inactive methods.
			}

			$dr_groups = isset( $method['drGroups'] ) ? $method['drGroups'] : array();
			if ( $method['drEnabled'] && ( empty( $dr_groups ) || ! ConditionEvaluator::evaluate_condition_groups( $dr_groups ) ) ) {
				continue;
			}

			$type = isset( $method['type'] ) ? $method['type'] : '';

			if ( 'carrier' === $type ) {
				$validated_method = $this->validate_carrier_method( $method );
			} else {

				$method_type = isset( $method['methodType'] ) ? $method['methodType'] : '';

				switch ( $method_type ) {
					case 'flat_amount':
						$validated_method = $this->validate_flat_amount_method( $method );
						break;

					case 'flexible_amount':
						$validated_method = $this->validate_flexible_amount_method( $method );
						break;

					case 'free_shipping':
						$validated_method = $this->validate_free_shipping_method( $method );
						break;

					default:
						continue 2; // Skip unknown method types.
				}
			}

			if ( $validated_method ) {
				$validated_method['cost']        = max( $validated_method['cost'], 0.0 );
				$validated_method['description'] = isset( $method['description'] ) ? $method['description'] : '';
				$validated_methods[]             = $validated_method;
			}
		}

		return $validated_methods;
	}

	/**
	 * Validate flat amount shipping method.
	 *
	 * @param array $method Shipping method configuration.
	 * @return array|false Validated method or false if invalid.
	 */
	private function validate_carrier_method( $method ) {

		if ( ! Flags::is_pro_or_can_preview() ) {
			return false;
		}

		if ( empty( $method['carrierKey'] ) ) {
			return false;
		}

		$cart = WC()->cart;

		if ( ! $cart || $cart->is_empty() ) {
			return false;
		}

		$live_rate_manager = LiveRateManager::get_instance();

		$cart_data = array();
		foreach ( $cart->get_cart() as $cart_item ) {
			$product  = $cart_item['data'];
			$quantity = $cart_item['quantity'];

			if ( $quantity <= 0 || ! $product || ! method_exists( $product, 'needs_shipping' ) || ! $product->needs_shipping() ) {
				continue;
			}
			$cart_data[] = array(
				'id'       => $product->get_id(),
				'quantity' => (int) $quantity,
				'weight'   => (float) $product->get_weight(),
				'length'   => (float) $product->get_length(),
				'width'    => (float) $product->get_width(),
				'height'   => (float) $product->get_height(),
				'declared' => (float) $product->get_price(),
				'sku'      => $product->get_sku(),
			);
		}

		$origin = \WTRS\Includes\ZoneValidator::get_store_address();
		$dest   = \WTRS\Includes\ZoneValidator::get_customer_region();

		$rates = $live_rate_manager->fetch_rates(
			array(
				'carrier_key'  => $method['carrierKey'],
				'cart_data'    => $cart_data,
				'origin'       => $origin,
				'dest'         => $dest,
				'currency'     => get_woocommerce_currency(),
				'subtotal'     => (float) $cart->get_subtotal(),
				'subtotal_tax' => (float) $cart->get_subtotal_tax(),
				'context'      => is_checkout() ? 'checkout' : 'cart',
				'method_id'    => $method['id'],
				'settings'     => $method['carrierSettings'] ?? array(),
				'packSettings' => array(
					'packMethod'    => $method['packMethod'] ?? 'per_item',
					'packMaxWeight' => (float) ( $method['packMaxWeight'] ?? 0 ),
					'packBoxes'     => isset( $method['packBoxes'] ) && is_array( $method['packBoxes'] ) ? $method['packBoxes'] : array(),
				),
				'markUpRate'   => isset( $method['markUpRate'] ) ? (float) $method['markUpRate'] : 0,
				'markUpType'   => isset( $method['markUpType'] ) ? $method['markUpType'] : 'fixed',
				'fallbackRate' => isset( $method['fallbackRate'] ) ? (float) $method['fallbackRate'] : '',
				'fallbackType' => isset( $method['fallbackType'] ) ? $method['fallbackType'] : 'fixed',
				'use_cache'    => true,
				'cache_ttl'    => 60,
			)
		);

		if ( false === $rates || empty( $rates ) ) {
			return false;
		}

		foreach ( $rates as $rate ) {

			$label = empty( $rate->warnings['fallback'] ) ? ( $method['methodName'] ?? $rate->service_name ) : ( $method['fallbackTitle'] ?? ( $method['methodName'] ?? $rate->service_name ) );

			return array(
				'id'            => $rate->method_id,
				'method_id'     => 'wtrs_carrier_' . $rate->carrier_key . '_' . $rate->method_id,
				'label'         => $label,
				'cost'          => (float) $rate->cost,
				'method_type'   => 'carrier',
				'description'   => $method['description'] ?? '',
				'delivery_time' => $rate->delivery_time,
				'rate_details'  => $rate->rate_details,
			);
		}
	}

	/**
	 * Validate flat amount shipping method.
	 *
	 * @param array $method Shipping method configuration.
	 * @return array|false Validated method or false if invalid.
	 */
	private function validate_flat_amount_method( $method ) {
		$base_cost_string = isset( $method['rates'][0]['initialValue'] ) ? (string) $method['rates'][0]['initialValue'] : '';

		$has_costs   = false;
		$total_cost  = 0.0;
		$cart_qty    = $this->get_cart_item_qty();
		$cart_amount = $this->get_cart_contents_cost();

		// Evaluate base cost if provided.
		if ( '' !== trim( $base_cost_string ) ) {
			$has_costs   = true;
			$total_cost += (float) $this->evaluate_cost(
				$base_cost_string,
				array(
					'qty'  => $cart_qty,
					'cost' => $cart_amount,
				)
			);
		}

		// Process flatRateConfig if present.
		$config = isset( $method['flatRateConfig'] ) && is_array( $method['flatRateConfig'] ) ? $method['flatRateConfig'] : array();
		if ( isset( $config['type'] ) &&
			'shipping_class' === $config['type'] &&
			! empty( $config['values'] ) &&
			is_array( $config['values'] ) &&
			! empty( $config['isEnabled'] )
		) {
			$calc_type              = isset( $config['calcType'] ) && in_array( $config['calcType'], array( 'per_order', 'per_class' ), true ) ? $config['calcType'] : 'per_class';
			$found_shipping_classes = $this->find_cart_shipping_classes();
			$highest_class_cost     = 0.0;
			$no_class_cost          = isset( $config['noShippingClassCost'] ) ? (string) $config['noShippingClassCost'] : '';

			foreach ( $found_shipping_classes as $shipping_class_slug => $products ) {
				$class_cost_string = $this->get_shipping_class_cost_from_config( $config['values'], $shipping_class_slug, $no_class_cost );

				if ( '' === $class_cost_string ) {
					continue;
				}

				$has_costs  = true;
				$class_cost = (float) $this->evaluate_cost(
					$class_cost_string,
					array(
						'qty'  => array_sum( wp_list_pluck( $products, 'quantity' ) ),
						'cost' => array_sum( wp_list_pluck( $products, 'line_total' ) ),
					)
				);

				if ( 'per_class' === $calc_type ) {
					$total_cost += $class_cost;
				} else { // per_order.
					$highest_class_cost = $class_cost > $highest_class_cost ? $class_cost : $highest_class_cost;
				}
			}

			if ( 'per_order' === $calc_type && $highest_class_cost > 0 ) {
				$total_cost += $highest_class_cost;
			}
		}

		return array(
			'id'            => $method['id'],
			'method_id'     => 'wtrs_flat_' . $method['id'],
			'label'         => $method['methodName'],
			'cost'          => $has_costs ? (float) $total_cost : 0.0,
			'method_type'   => 'flat_amount',
			'description'   => isset( $method['description'] ) ? $method['description'] : '',
			'delivery_time' => $this->get_delivery_time_text( $method ),
		);
	}

	/**
	 * Evaluate a cost expression similar to WooCommerce flat rate evaluate_cost.
	 * Supports [qty], [cost] tokens and [fee] shortcode with percent, min_fee, max_fee.
	 *
	 * @param string $sum  Cost expression, e.g. "10 + (2 * [qty]) + [fee percent=5]".
	 * @param array  $args Must contain 'qty' and 'cost' keys.
	 * @return float Numeric evaluated cost (0 if empty/invalid).
	 */
	private function evaluate_cost( $sum, $args = array() ) {
		if ( ! is_array( $args ) || ! array_key_exists( 'qty', $args ) || ! array_key_exists( 'cost', $args ) ) {
			// Silently guard; keep behavior consistent with Woo but without admin notice.
			$args = array(
				'qty'  => 0,
				'cost' => 0,
			);
		}

		// Load math evaluator from WooCommerce.
		if ( function_exists( 'WC' ) ) {
			include_once WC()->plugin_path() . '/includes/libraries/class-wc-eval-math.php';
		}

		// Allow 3rd parties to alter args similar to Woo filter if available.
		if ( function_exists( 'apply_filters' ) ) {
			$args = apply_filters( 'wtrs_evaluate_shipping_cost_args', $args, $sum, $this );
		}

		$locale         = localeconv();
		$decimals       = array( wc_get_price_decimal_separator(), $locale['decimal_point'], $locale['mon_decimal_point'], ',' );
		$this->fee_cost = $args['cost'];

		// Register [fee] shortcode temporarily.
		add_shortcode( 'fee', array( $this, 'fee' ) );

		$sum = do_shortcode(
			str_replace(
				array( '[qty]', '[cost]' ),
				array( $args['qty'], $args['cost'] ),
				(string) $sum
			)
		);

		remove_shortcode( 'fee', array( $this, 'fee' ) );

		// Normalize string for math evaluation.
		$sum = preg_replace( '/\s+/', '', $sum );
		$sum = str_replace( $decimals, '.', $sum );
		$sum = rtrim( ltrim( $sum, "\t\n\r\0\x0B+*/" ), "\t\n\r\0\x0B+-*/" );

		if ( empty( $sum ) ) {
			return 0.0;
		}

		if ( class_exists( '\\WC_Eval_Math' ) ) {
			$value = \WC_Eval_Math::evaluate( $sum );
			return is_numeric( $value ) ? (float) $value : 0.0;
		}

		// Fallback: try to cast to float if it's a plain number.
		return is_numeric( $sum ) ? (float) $sum : 0.0;
	}

	/**
	 * [fee] shortcode callback similar to Woo.
	 *
	 * @param array $atts Shortcode attributes: percent, min_fee, max_fee.
	 * @return float
	 */
	public function fee( $atts ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid
		$atts = shortcode_atts(
			array(
				'percent' => '',
				'min_fee' => '',
				'max_fee' => '',
			),
			$atts,
			'fee'
		);

		$calculated_fee = 0.0;

		if ( $atts['percent'] ) {
			$calculated_fee = (float) $this->fee_cost * ( (float) $atts['percent'] / 100 );
		}

		if ( '' !== $atts['min_fee'] && $calculated_fee < (float) $atts['min_fee'] ) {
			$calculated_fee = (float) $atts['min_fee'];
		}

		if ( '' !== $atts['max_fee'] && $calculated_fee > (float) $atts['max_fee'] ) {
			$calculated_fee = (float) $atts['max_fee'];
		}

		return $calculated_fee;
	}

	/**
	 * Get total shippable item quantity in the cart.
	 *
	 * @return int
	 */
	private function get_cart_item_qty() {
		$total_quantity = 0;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			if ( $cart_item['quantity'] > 0 && $product && method_exists( $product, 'needs_shipping' ) && $product->needs_shipping() ) {
				$total_quantity += (int) $cart_item['quantity'];
			}
		}
		return $total_quantity;
	}

	/**
	 * Sum of cart contents cost (line totals) for shippable items.
	 *
	 * @return float
	 */
	private function get_cart_contents_cost() {
		$total = 0.0;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product = $cart_item['data'];
			if ( $product && method_exists( $product, 'needs_shipping' ) && $product->needs_shipping() ) {
				$total += isset( $cart_item['line_total'] ) ? (float) $cart_item['line_total'] : 0.0;
			}
		}
		return $total;
	}

	/**
	 * Group cart items by shipping class slug.
	 *
	 * @return array slug => [ item_id => [ 'quantity' => int, 'line_total' => float ] ]
	 */
	private function find_cart_shipping_classes() {
		$found = array();
		foreach ( WC()->cart->get_cart() as $item_id => $values ) {
			$product = $values['data'];
			if ( $product && method_exists( $product, 'needs_shipping' ) && $product->needs_shipping() ) {
				$slug = method_exists( $product, 'get_shipping_class' ) ? (string) $product->get_shipping_class() : '';
				if ( ! isset( $found[ $slug ] ) ) {
					$found[ $slug ] = array();
				}
				$found[ $slug ][ $item_id ] = array(
					'quantity'   => (int) ( $values['quantity'] ?? 0 ),
					'line_total' => (float) ( $values['line_total'] ?? 0 ),
				);
			}
		}
		return $found;
	}

	/**
	 * Resolve a shipping class cost string from config entries given a product shipping class slug.
	 * Tries by term_id (string) first, then by slug.
	 *
	 * @param array  $config_values      Array of [ [ 'id' => string, 'cost' => string ], ... ].
	 * @param string $shipping_class_slug Product shipping class slug ('' for none).
	 * @param string $no_class_cost       Cost expression for products without shipping class.
	 * @return string Cost expression or empty string if not configured.
	 */
	private function get_shipping_class_cost_from_config( $config_values, $shipping_class_slug, $no_class_cost = '' ) {
		if ( '' === $shipping_class_slug ) {
			return (string) $no_class_cost;
		}

		$term        = get_term_by( 'slug', $shipping_class_slug, 'product_shipping_class' );
		$term_id_str = ( $term && isset( $term->term_id ) ) ? (string) $term->term_id : '';

		// First try matching by term ID.
		if ( $term_id_str ) {
			foreach ( $config_values as $entry ) {
				if ( isset( $entry['id'] ) && (string) $entry['id'] === $term_id_str ) {
					return isset( $entry['cost'] ) ? (string) $entry['cost'] : '';
				}
			}
		}

		// Fallback to slug match.
		foreach ( $config_values as $entry ) {
			if ( isset( $entry['id'] ) && (string) $entry['id'] === (string) $shipping_class_slug ) {
				return isset( $entry['cost'] ) ? (string) $entry['cost'] : '';
			}
		}

		return '';
	}

	/**
	 * Validate flexible amount shipping method.
	 *
	 * @param array $method Shipping method configuration.
	 * @return array|false Validated method or false if invalid.
	 */
	private function validate_flexible_amount_method( $method ) {
		$selected_rates = array();

		if ( ! empty( $method['rates'] ) && is_array( $method['rates'] ) ) {
			foreach ( $method['rates'] as $rate ) {

				if ( ! Flags::is_pro_or_can_preview() ) {
					$rate['conditions'] = array_slice( $rate['conditions'], 0, Flags::MAX_RATE_TIER_CONDITION );
				}

				if ( ConditionEvaluator::evaluate_condition_groups( array( $rate ), $method ) ) {
					$special_action = isset( $rate['specialAction'] ) ? $rate['specialAction'] : 'pass';

					$selected_rates[] = $rate;

					// Pro check for special actions.
					if ( Flags::SPECIAL_ACTIONS_PRO && ! Flags::is_pro_or_can_preview() ) {
						continue;
					}

					if ( $method['enableSpecialActions'] ?? false ) {
						if ( 'deny' === $special_action ) {
							return false;
						}

						if ( 'stop' === $special_action ) {
							break;
						}
					}
				}
			}
		}

		if ( count( $selected_rates ) < 1 ) {
			return false;
		}

		// Find matching rate.
		$cost = $this->get_rate( $selected_rates, $method );

		if ( false === $cost ) {
			return false; // No matching rate found.
		}

		return array(
			'id'            => $method['id'],
			'method_id'     => 'wtrs_flexible_' . $method['id'],
			'label'         => $method['methodName'],
			'cost'          => $cost,
			'method_type'   => 'flexible_amount',
			'description'   => isset( $method['description'] ) ? $method['description'] : '',
			'delivery_time' => $this->get_delivery_time_text( $method ),
		);
	}

	/**
	 * Validate free shipping method.
	 *
	 * @param array $method Shipping method configuration.
	 * @return array|false Validated method or false if invalid.
	 */
	private function validate_free_shipping_method( $method ) {
		$rate = isset( $method['rates'][0] ) ? $method['rates'][0] : null;

		if ( empty( $rate['conditions'] ) ) {
			return false;
		}

		foreach ( $rate['conditions'] as $condition ) {
			$criteria = isset( $condition['field'] ) ? $condition['field'] : '';

			// Before -> Cart total without discount.
			// After  -> Cart total with discount.
			if (
			( $method['applyFreeShippingBeforeCoupon'] ?? false ) &&
			'cart_total' === $criteria
			) {
				$condition['field'] .= '_wo_discount';
			}

			// Free shipping conditions are always AND conditions.
			if ( ! ConditionEvaluator::evaluate_single_condition( $condition, $method ) ) {
				return false;
			}
		}

		return array(
			'id'            => $method['id'],
			'method_id'     => 'wtrs_free_' . $method['id'],
			'label'         => $method['methodName'],
			'cost'          => 0,
			'method_type'   => 'free_shipping',
			'description'   => isset( $method['description'] ) ? $method['description'] : '',
			'delivery_time' => $this->get_delivery_time_text( $method ),
		);
	}

	/**
	 * Get rate value from rate array
	 *
	 * @param array $rates Rates.
	 * @param array $method Method data.
	 * @return float|false
	 */
	private function get_rate( $rates, $method ) {
		$calc_rates = array();

		foreach ( $rates as $rate ) {
			$type = isset( $rate['type'] ) ? $rate['type'] : 'fixed';

			// Simple fixed cost.
			if ( 'fixed' === $type ) {
				$calc_rates[] = (float) ( $rate['initialValue'] ?? 0 );
				continue;
			}

			// For incremental variants we need the cart value derived from `basedOn`.
			$cart_value  = $this->resolve_based_on_value( $rate, $method );
			$has_decimal = ConditionEvaluator::does_condition_have_decimals( $rate['basedOn'] ?? '' );
			if ( false === $cart_value ) {
				return false;
			}

			if ( 'incremental' === $type ) {
				$cost  = (float) ( $rate['initialValue'] ?? 0 );
				$every = (float) ( $rate['everyValue'] ?? 0 );

				if ( $every > 0 ) {
					$per_value = (float) ( $cart_value / $every );
					if ( ! $has_decimal ) {
						$per_value = (int) ceil( $per_value );
					}
					$calc_rates[] = (float) ( $per_value * $cost );
				} else {
					$calc_rates[] = 0.0;
				}

				continue;
			}

			if ( 'fixed_incremental' === $type ) {
				$cost  = (float) ( $rate['initialValue'] ?? 0 );
				$first = (float) ( $rate['firstValue'] ?? 0 );

				if ( $cart_value > $first ) {
					$every = (float) ( $rate['everyValue'] ?? 0 );
					$then  = (float) ( $rate['thenValue'] ?? 0 );
					$delta = $cart_value - $first;
					if ( $every > 0 ) {
						$steps = (float) ( $delta / $every );
						if ( ! $has_decimal ) {
							$steps = (int) ceil( $steps );
						}
						$cost += $then * $steps;
					}
				}

				$calc_rates[] = $cost;
				continue;
			}
		}

		if ( empty( $calc_rates ) ) {
			return 0.0;
		}

		$rate_calc_type = $method['rateCalcType'] ?? 'sum';

		if ( ! Flags::is_pro_or_can_preview() ) {
			$rate_calc_type = 'sum';
		}

		switch ( $rate_calc_type ) {
			case 'highest':
				return max( $calc_rates );

			case 'lowest':
				return min( $calc_rates );

			case 'first':
				return $calc_rates[0];

			case 'last':
				return $calc_rates[ count( $calc_rates ) - 1 ];

			case 'sum':
			default:
				return array_sum( $calc_rates );
		}
	}

	/**
	 * Resolve cart metric from a rate's `basedOn` config.
	 *
	 * @param array $rate Rate.
	 * @param array $method Method data.
	 * @return float|false
	 */
	private function resolve_based_on_value( $rate, $method ) {
		if ( empty( $rate['basedOn'] ) ) {
			return false;
		}

		list( $type, $field ) = array_pad( explode( ':', $rate['basedOn'], 2 ), 2, '' );

		if ( ! Flags::is_pro_or_can_preview() && ! in_array( $field, Flags::CONDITION_FREE_OPTIONS, true ) ) {
			return false;
		}

		$cart_value = ConditionEvaluator::resolve_condition_value( $type, $field, $method, $rate );

		if ( false === $cart_value ) {
			return false;
		}

		if ( is_array( $cart_value ) ) {
			$cart_value = (float) array_sum( $cart_value );
		}

		return (float) $cart_value;
	}

	/**
	 * Get delivery time text.
	 *
	 * @param array $method Shipping method configuration.
	 * @return string Delivery time text.
	 */
	private function get_delivery_time_text( $method ) {

		if ( ! ( $method['deliveryTimeEnabled'] ?? false ) ) {
			return '';
		}

		$from = isset( $method['deliveryTimeFrom'] ) ? intval( $method['deliveryTimeFrom'] ) : 0;
		$to   = isset( $method['deliveryTimeTo'] ) ? intval( $method['deliveryTimeTo'] ) : 0;

		if ( $from && $to ) {
			if ( $from === $to ) {
				// translators: Delivery day text.
				return sprintf( __( '%d day(s)', 'wow-table-rate-shipping' ), $from );
			} else {
				// translators: Delivery day text.
				return sprintf( __( '%1$d-%2$d day(s)', 'wow-table-rate-shipping' ), $from, $to );
			}
		} elseif ( $from ) {
			// translators: Delivery day text.
			return sprintf( __( '%d+ day(s)', 'wow-table-rate-shipping' ), $from );
		} elseif ( $to ) {
			// translators: Delivery day text.
			return sprintf( __( 'Up to %d day(s)', 'wow-table-rate-shipping' ), $to );
		}

		return '';
	}
}
