<?php // phpcs:ignore
/**
 * Compatibility with Modern Cart for WooCommerce.
 *
 * @package WTRS
 */

namespace WTRS\Includes\Compatibility;

use WTRS\Includes\ConditionEvaluator;
use WTRS\Includes\DB;
use WTRS\Includes\RuleVisibility;

defined( 'ABSPATH' ) || exit;

/**
 * Provides WowShipping free-shipping thresholds to Modern Cart's progress bar.
 */
class ModernCartCompatibility {

	/**
	 * Register compatibility hooks.
	 *
	 * @return void
	 */
	public function hooks() {
		add_filter( 'moderncart_free_shipping_min_amount', array( $this, 'filter_free_shipping_min_amount' ) );
	}

	/**
	 * Return a WowShipping free-shipping threshold when Modern Cart cannot find a native one.
	 *
	 * @param mixed $amount Native Modern Cart detected amount.
	 * @return mixed
	 */
	public function filter_free_shipping_min_amount( $amount ) {
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return $amount;
		}

		if ( $this->has_free_shipping_coupon() ) {
			return 0;
		}

		if ( (float) $amount > 0 ) {
			return $amount;
		}

		$thresholds = $this->get_matching_thresholds();

		if ( empty( $thresholds ) ) {
			return $amount;
		}

		return min( $thresholds );
	}

	/**
	 * Get thresholds from active WowShipping free-shipping methods matching the cart context.
	 *
	 * @return array<float>
	 */
	private function get_matching_thresholds() {
		$thresholds = array();
		$zone_id    = $this->get_cart_zone_id();

		foreach ( DB::get_instance()->get_applicable_shipping_rules() as $rule ) {
			if ( empty( $rule ) || ! RuleVisibility::is_viewable( $rule ) ) {
				continue;
			}

			if ( ! $this->is_rule_in_cart_zone( $rule, $zone_id ) ) {
				continue;
			}

			// $method_helper= new MethodHelper();

			// $methods = $method_helper->validate_shipping_methods( $rule['shippingMethods'] ?? array() );

			foreach ( $this->get_enabled_free_shipping_methods( $rule ) as $method ) {
				if ( ! $this->method_display_rules_match( $method ) ) {
					continue;
				}

				foreach ( $method['rates'] ?? array() as $rate ) {
					$threshold = $this->get_rate_threshold_if_context_matches( $rate, $method );

					if ( null !== $threshold ) {
						$thresholds[] = $threshold;
					}
				}
			}
		}

		return $thresholds;
	}

	/**
	 * Get the cart shipping zone id using the same fallback Modern Cart uses.
	 *
	 * @return int|null
	 */
	private function get_cart_zone_id() {
		if ( ! function_exists( 'wc_get_shipping_zone' ) || ! WC()->cart ) {
			return null;
		}

		$packages = WC()->cart->get_shipping_packages();
		$package  = reset( $packages );

		if ( empty( $package ) || ! is_array( $package ) ) {
			return null;
		}

		$zone = wc_get_shipping_zone( $package );

		if ( ! $this->is_destination_exists( $package ) && class_exists( '\WC_Shipping_Zones' ) ) {
			$initial_zone = \WC_Shipping_Zones::get_zone_by( 'zone_id', 1 );
			$zone         = $initial_zone instanceof \WC_Shipping_Zone ? $initial_zone : $zone;
		}

		if ( ! $zone || ! is_object( $zone ) || ! is_callable( array( $zone, 'get_id' ) ) ) {
			return null;
		}

		return (int) $zone->get_id();
	}

	/**
	 * Check whether a package has enough destination data for zone resolution.
	 *
	 * @param array $package Shipping package.
	 * @return bool
	 */
	private function is_destination_exists( $package ) {
		$destination = isset( $package['destination'] ) && is_array( $package['destination'] ) ? $package['destination'] : array();
		$country     = $destination['country'] ?? null;
		$state       = $destination['state'] ?? null;
		$postcode    = $destination['postcode'] ?? null;
		$city        = $destination['city'] ?? null;

		if ( 'AF' === $country && ! $city ) {
			$country = null;
		}

		return (bool) ( $country || $state || $postcode );
	}

	/**
	 * Check whether a WowShipping rule belongs to the cart zone or is global.
	 *
	 * @param array    $rule Rule config.
	 * @param int|null $zone_id Cart zone id.
	 * @return bool
	 */
	private function is_rule_in_cart_zone( $rule, $zone_id ) {
		$rule_zone_id = (int) ( $rule['generalSettings']['shippingZone'] ?? 0 );

		if ( -1 === $rule_zone_id ) {
			return true;
		}

		if ( null === $zone_id ) {
			return true;
		}

		return $rule_zone_id === $zone_id;
	}

	/**
	 * Get enabled free-shipping method configs from a rule.
	 *
	 * @param array $rule Rule config.
	 * @return array
	 */
	private function get_enabled_free_shipping_methods( $rule ) {
		$methods = array();

		foreach ( $rule['shippingMethods'] ?? array() as $method ) {
			if ( 'free_shipping' === ( $method['methodType'] ?? '' ) ) {
				$methods[] = $method;
			}

			if ( ! empty( $method['rates'] ) && is_array( $method['rates'] ) ) {
				$methods[] = $method;
			}
		}

		return $methods;
	}

	/**
	 * Check method-level display rules.
	 *
	 * @param array $method Method config.
	 * @return bool
	 */
	private function method_display_rules_match( $method ) {
		if ( empty( $method['drEnabled'] ) ) {
			return true;
		}

		$groups = $method['drGroups'] ?? array();

		if ( empty( $groups ) || ! is_array( $groups ) ) {
			return false;
		}

		return ConditionEvaluator::evaluate_condition_groups( $groups, $method );
	}

	/**
	 * Extract a threshold from a free-shipping rate if its non-threshold conditions match.
	 *
	 * @param array $rate Rate config.
	 * @param array $method Method config.
	 * @return float|null
	 */
	private function get_rate_threshold_if_context_matches( $rate, $method ) {
		$threshold = null;

		foreach ( $rate['conditions'] ?? array() as $condition ) {
			if ( $this->is_cart_total_threshold_condition( $condition ) ) {
				$threshold = $this->extract_threshold_amount( $condition );

				if ( ! $this->threshold_upper_bound_matches( $condition ) ) {
					return null;
				}

				continue;
			}

			if ( ! ConditionEvaluator::evaluate_single_condition( $condition, $method, $rate ) ) {
				return null;
			}
		}

		return null === $threshold || $threshold <= 0 ? null : $threshold;
	}

	/**
	 * Check whether a condition represents the minimum order amount.
	 *
	 * @param array $condition Condition config.
	 * @return bool
	 */
	private function is_cart_total_threshold_condition( $condition ) {
		$field = $condition['field'] ?? '';

		return 'Cart' === ( $condition['type'] ?? '' )
			&& in_array( $field, array( 'cart_total', 'total_amount' ), true )
			&& null !== $this->extract_threshold_amount( $condition );
	}

	/**
	 * Extract the minimum cart total needed for a rate.
	 *
	 * @param array $condition Condition config.
	 * @return float|null
	 */
	private function extract_threshold_amount( $condition ) {
		$operator = $condition['operator'] ?? '';

		if ( in_array( $operator, array( 'greaterThan', 'greaterThanOrEquals' ), true ) ) {
			return isset( $condition['value'] ) ? (float) $condition['value'] : null;
		}

		if ( 'between' === $operator ) {
			return isset( $condition['min_range'] ) ? (float) $condition['min_range'] : null;
		}

		return null;
	}

	/**
	 * For between conditions, avoid showing the bar after the configured maximum is exceeded.
	 *
	 * @param array $condition Condition config.
	 * @return bool
	 */
	private function threshold_upper_bound_matches( $condition ) {
		if ( 'between' !== ( $condition['operator'] ?? '' ) || '' === (string) ( $condition['max_range'] ?? '' ) ) {
			return true;
		}

		$current_total = ConditionEvaluator::resolve_condition_value( 'Cart', $condition['field'] ?? 'cart_total' );

		return (float) $current_total <= (float) $condition['max_range'];
	}

	/**
	 * Check whether an applied coupon grants free shipping.
	 *
	 * @return bool
	 */
	private function has_free_shipping_coupon() {
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
}
