<?php //phpcs:ignore
/**
 * Shipping Condition Evaluator.
 *
 * @package WTRS\Evaluator
 */

namespace WTRS\Includes;

use WTRS\Includes\Utils\Flags;

defined( 'ABSPATH' ) || exit;

/**
 * Shipping Condition Evaluator class.
 */
class ConditionEvaluator {

	/**
	 * Extract product IDs from a given rate configuration.
	 *
	 * @param array|null $rate rate data.
	 * @return array
	 */
	private static function extract_product_ids_from_rate( $rate ) {

		if ( empty( $rate ) || empty( $rate['conditions'] ) ) {
			return array();
		}

		$product_ids = array();

		foreach ( $rate['conditions'] as $condition ) {

			if ( 'Attribute' === $condition['type'] && ! empty( $condition['field'] ) && WC()->cart ) {

				$cart_items = WC()->cart->get_cart();

				foreach ( $cart_items as $ci ) {

					$term_ids = self::get_attribute_term_ids_from_cart_item( $ci, $condition['field'] );

					$should_include = self::compare_resolved_condition(
						'Attribute',
						$condition['field'],
						$condition['operator'],
						isset( $condition['value'] ) ? $condition['value'] : null,
						$term_ids,
					);

					if ( ! $should_include ) {
						continue;
					}

					$pid = isset( $ci['product_id'] ) ? (int) $ci['product_id'] : 0;
					if ( $pid > 0 ) {
						$product_ids[] = $pid;
					}
				}

				return array_values( array_unique( $product_ids ) );
			}

			$field = $condition['field'];

			switch ( $field ) {
				case 'cart_contains_products':
					if ( in_array( $condition['operator'], array( 'equal', 'contains' ), true ) ) {
						$product_ids = array_merge( $product_ids, $condition['value'] );
					} else {
						$cart_items = WC()->cart->get_cart();
						$ids        = array();
						foreach ( $cart_items as $ci ) {
							if ( in_array( (int) ( isset( $ci['product_id'] ) ? $ci['product_id'] : 0 ), $condition['value'], true ) ) {
								continue;
							}
							$ids[] = (int) ( isset( $ci['product_id'] ) ? $ci['product_id'] : 0 );
						}
						$ids         = array_unique( $ids );
						$product_ids = array_merge( $product_ids, $ids );
					}
					break;

				case 'category_products':
					if ( ! WC()->cart ) {
						return array();
					}
					$cart_items    = WC()->cart->get_cart();
					$selected_cats = array();
					if ( isset( $condition['value'] ) ) {
						$selected_cats = is_array( $condition['value'] ) ? $condition['value'] : explode( ',', (string) $condition['value'] );
					}
					$selected_cats = array_map( 'intval', array_filter( $selected_cats, 'strlen' ) );

					$ids = array();
					foreach ( $cart_items as $cart_item ) {
						$pid          = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
						$product_cats = wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'ids' ) );
						$product_cats = is_wp_error( $product_cats ) ? array() : array_map( 'intval', (array) $product_cats );
						$has_match    = ! empty( array_intersect( $product_cats, $selected_cats ) );

						if ( in_array( $condition['operator'], array( 'equal', 'contains' ), true ) ) {
							if ( $has_match ) {
								$ids[] = $pid;
							}
						} elseif ( ! $has_match ) {
								$ids[] = $pid;
						}
					}

					$ids         = array_unique( $ids );
					$product_ids = array_merge( $product_ids, $ids );
					break;

				case 'tag_products':
					if ( ! WC()->cart ) {
						return array();
					}
					$cart_items    = WC()->cart->get_cart();
					$selected_tags = array();
					if ( isset( $condition['value'] ) ) {
						$selected_tags = is_array( $condition['value'] ) ? $condition['value'] : explode( ',', (string) $condition['value'] );
					}
					$selected_tags = array_map( 'intval', array_filter( $selected_tags, 'strlen' ) );

					$ids = array();
					foreach ( $cart_items as $cart_item ) {
						$pid          = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
						$product_tags = wp_get_post_terms( $pid, 'product_tag', array( 'fields' => 'ids' ) );
						$product_tags = is_wp_error( $product_tags ) ? array() : array_map( 'intval', (array) $product_tags );
						$has_match    = ! empty( array_intersect( $product_tags, $selected_tags ) );

						if ( in_array( $condition['operator'], array( 'equal', 'contains' ), true ) ) {
							if ( $has_match ) {
								$ids[] = $pid;
							}
						} elseif ( ! $has_match ) {
								$ids[] = $pid;
						}
					}

					$ids         = array_unique( $ids );
					$product_ids = array_merge( $product_ids, $ids );
					break;

				case 'shippingClass': // fall-through.
				case 'shipping_class':
					if ( ! WC()->cart ) {
						return array();
					}
					$cart_items       = WC()->cart->get_cart();
					$selected_classes = array();
					if ( isset( $condition['value'] ) ) {
						$selected_classes = is_array( $condition['value'] ) ? $condition['value'] : explode( ',', (string) $condition['value'] );
					}
					$selected_classes = array_map(
						static function ( $v ) {
							return strtolower( trim( (string) $v ) );
						},
						array_filter( $selected_classes, 'strlen' )
					);

					$ids = array();
					foreach ( $cart_items as $cart_item ) {
						$pid            = isset( $cart_item['product_id'] ) ? (int) $cart_item['product_id'] : 0;
						$product        = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
						$shipping_class = $product ? $product->get_shipping_class() : '';
						$shipping_class = strtolower( (string) $shipping_class );
						$has_match      = ( '' !== $shipping_class ) && in_array( $shipping_class, $selected_classes, true );

						if ( in_array( $condition['operator'], array( 'equal', 'contains' ), true ) ) {
							if ( $has_match ) {
								$ids[] = $pid;
							}
						} elseif ( ! $has_match ) {
								$ids[] = $pid;
						}
					}

					$ids         = array_unique( $ids );
					$product_ids = array_merge( $product_ids, $ids );
					break;

			}
		}

		return array_values( array_unique( $product_ids ) );
	}

	/**
	 * Resolve the raw value for a given condition without applying any comparison.
	 * This allows reusing the computed value elsewhere (e.g., for UI or chained logic).
	 *
	 * Contract:
	 * - Inputs: $type ('Cart'|'Product'|'Customer'|'Location'|'Others'|'Attribute'), $field string, optional $method array
	 * - Output shape varies by field:
	 *   - Numeric metrics: float
	 *   - String metrics: string
	 *   - Collections: array of strings/ints
	 *   - Coupons: array{ codes: string[], has_free_shipping: bool }
	 *   - Others/weekdays, time: array{ now: \DateTimeInterface, tz: \DateTimeZone }
	 * - On unsupported/empty contexts, returns null.
	 *
	 * @param string     $type   Condition type.
	 * @param string     $field  Field key.
	 * @param array|null $method Optional method context (used for product_* aggregations and product filters).
	 * @param array|null $rate Optional rate context.
	 * @return mixed Raw resolved value (see contract) or null if unavailable.
	 */
	public static function resolve_condition_value( $type, $field, $method = null, $rate = null ) {

		switch ( $type ) {
			case 'General':
				switch ( $field ) {
					case 'always':
						return true;
				}
				break;

			case 'Cart':
				if ( ! WC()->cart ) {
					return null;
				}
				$cart = WC()->cart; // phpcs:ignore
				switch ( $field ) {
					case 'cart_total':
					case 'total_amount':
						$val = (float) $cart->get_cart_contents_total();
						if ( ! wc_prices_include_tax() ) {
							$val += (float) $cart->get_cart_contents_tax();
						}
						foreach ( $cart->get_fees() as $fee ) {
							if ( $fee->amount > 0 ) {
								$val += (float) $fee->amount;
							}
						}
						return (float) $val;

					case 'subtotal_amount':
					case 'cart_subtotal':
						return (float) $cart->get_subtotal();

					case 'quantity':
					case 'cart_quantity':
						return (float) $cart->get_cart_contents_count();

					case 'weight':
					case 'cart_weight':
						return (float) $cart->get_cart_contents_weight();

					case 'length':
					case 'cart_length':
						return (float) self::get_cart_dimension( 'length' );

					case 'width':
					case 'cart_width':
						return (float) self::get_cart_dimension( 'width' );

					case 'height':
					case 'cart_height':
						return (float) self::get_cart_dimension( 'height' );

					case 'volume':
					case 'cart_volume':
						return (float) self::get_cart_volume();

					case 'cart_coupons':
					case 'coupons':
						$codes             = is_array( $cart->get_applied_coupons() ) ? array_values( $cart->get_applied_coupons() ) : array();
						$has_free_shipping = false;
						$coupons_map       = $cart->get_coupons();
						if ( is_array( $coupons_map ) ) {
							foreach ( $coupons_map as $code => $coupon_obj ) {
								if ( $coupon_obj && method_exists( $coupon_obj, 'get_free_shipping' ) && $coupon_obj->get_free_shipping() ) {
									$has_free_shipping = true;
									break;
								}
							}
						}
						return array(
							'codes'             => array_map( 'strval', $codes ),
							'has_free_shipping' => (bool) $has_free_shipping,
						);
				}
				break;

			case 'Product':
				if ( ! WC()->cart ) {
					return null;
				}

				$cart_items = self::get_filtered_cart_items( $rate );

				switch ( $field ) {
					case 'price':
					case 'product_price':
						$prices = array();
						foreach ( $cart_items as $cart_item ) {
							$product = $cart_item['data'];
							if ( $product ) {
								$prices[] = (float) $product->get_price(); // unit price per product (no qty).
							}
						}
						return $prices;

					case 'cart_contains_products':
						$ids = array();
						foreach ( $cart_items as $ci ) {
							$ids[] = (int) ( isset( $ci['product_id'] ) ? $ci['product_id'] : 0 );
						}
						$ids = array_unique( $ids );
						return array_values( $ids );

					case 'category_products':
						$cats = array();
						foreach ( $cart_items as $cart_item ) {
							$product_cats = wp_get_post_terms( $cart_item['product_id'], 'product_cat', array( 'fields' => 'ids' ) );
							$cats         = array_merge( $cats, is_wp_error( $product_cats ) ? array() : (array) $product_cats );
						}
						return array_values( array_unique( array_map( 'intval', $cats ) ) );

					case 'tag_products':
						$tags = array();
						foreach ( $cart_items as $cart_item ) {
							$product_tags = wp_get_post_terms( $cart_item['product_id'], 'product_tag', array( 'fields' => 'ids' ) );
							$tags         = array_merge( $tags, is_wp_error( $product_tags ) ? array() : (array) $product_tags );
						}
						return array_values( array_unique( array_map( 'intval', $tags ) ) );

					case 'product_sku':
					case 'sku':
						$skus = array();
						foreach ( $cart_items as $cart_item ) {
							$product = $cart_item['data'];
							if ( $product && $product->get_sku() ) {
								$skus[] = (string) $product->get_sku();
							}
						}
						return $skus;

					case 'shippingClass':
					case 'shipping_class':
						$classes = array();
						foreach ( $cart_items as $cart_item ) {
							$product = $cart_item['data'];
							if ( $product ) {
								$sc = $product->get_shipping_class();
								if ( $sc ) {
									$classes[] = (string) $sc;
								}
							}
						}
						return $classes;

					case 'product_quantity':
						// Quantities of individual line items (all products, no filtering).
						$quantities = array();
						foreach ( $cart_items as $cart_item ) {
							$product  = $cart_item['data'];
							$quantity = (int) ( $cart_item['quantity'] ?? 0 );
							if ( $product && $quantity > 0 ) {
								$quantities[] = (float) $quantity;
							}
						}
						return $quantities;

					case 'product_total':
						// Per-item total = unit price × quantity.
						$totals = array();
						foreach ( $cart_items as $cart_item ) {
							$product  = $cart_item['data'];
							$quantity = (int) ( $cart_item['quantity'] ?? 0 );
							if ( $product && $quantity > 0 ) {
								$totals[] = (float) $product->get_price() * $quantity;
							}
						}
						return $totals;

					case 'product_weight':
						// Unit weight per product (no qty multiplier).
						$weights = array();
						foreach ( $cart_items as $cart_item ) {
							$product = $cart_item['data'];
							if ( $product && $product->has_weight() ) {
								$weights[] = (float) $product->get_weight();
							}
						}
						return $weights;

					case 'product_height':
						$heights = array();
						foreach ( $cart_items as $cart_item ) {
							$product = $cart_item['data'];
							if ( $product ) {
								$heights[] = (float) $product->get_height();
							}
						}
						return $heights;

					case 'product_width':
						$widths = array();
						foreach ( $cart_items as $cart_item ) {
							$product = $cart_item['data'];
							if ( $product ) {
								$widths[] = (float) $product->get_width();
							}
						}
						return $widths;
					case 'product_length':
						$lengths = array();
						foreach ( $cart_items as $cart_item ) {
							$product = $cart_item['data'];
							if ( $product ) {
								$lengths[] = (float) $product->get_length();
							}
						}
						return $lengths;
					case 'product_volume':
						$volumes = array();
						foreach ( $cart_items as $cart_item ) {
							$product = $cart_item['data'];
							if ( $product ) {
								$length    = (float) $product->get_length();
								$width     = (float) $product->get_width();
								$height    = (float) $product->get_height();
								$volumes[] = (float) ( $length * $width * $height );
							}
						}
						return $volumes;
				}
				break;

			case 'Customer':
				$current_user_id = get_current_user_id();
				switch ( $field ) {
					case 'user':
						return array( (int) $current_user_id );
					case 'user_role':
						if ( $current_user_id ) {
							$user = get_userdata( $current_user_id );
							return $user ? (array) $user->roles : array();
						}
						return array( 'guest' );
					case 'email':
						$logged_in_user_email = '';
						if ( $current_user_id ) {
							$user                 = get_userdata( $current_user_id );
							$logged_in_user_email = ( $user ? (string) $user->user_email : '' );
						}
						return array( WC()->customer ? (string) WC()->customer->get_billing_email() : $logged_in_user_email );
					case 'phone':
						return array( WC()->customer ? (string) WC()->customer->get_billing_phone() : '' );
					case 'first_order_spent_amount':
						return (float) self::get_customer_first_order_amount( $current_user_id );
					case 'last_order_spent_amount':
						return (float) self::get_customer_last_order_amount( $current_user_id );
					case 'last_orders_count':
						return (float) self::get_customer_orders_count( $current_user_id );
				}
				break;

			case 'Location':
				if ( ! WC()->customer ) {
					return null;
				}
				$region = ZoneValidator::get_customer_region();
				switch ( $field ) {
					case 'country':
						return array( (string) $region['country'] );
					case 'state':
						return array( (string) ( $region['country'] . ZoneValidator::DELIMITER_STATE . $region['state'] ) );
					case 'city_town':
						return array( (string) $region['city'] );
					case 'zip_code':
						return array( (string) $region['postcode'] );
					case 'distance':
						return null; // Not supported without extra configuration.
				}
				break;

			case 'Others':
				$tz  = self::get_store_timezone();
				$now = new \DateTime( 'now', $tz );
				return array(
					'now' => $now,
					'tz'  => $tz,
				);

			case 'Attribute':
				if ( ! WC()->cart ) {
					return null;
				}
				return self::collect_attribute_term_ids( $field, $rate );
		}

		return null;
	}

	/**
	 * Check if numerical condition supports decimals.
	 *
	 * @param string $value Condition field.
	 * @return boolean
	 */
	public static function does_condition_have_decimals( $value ) {
		if ( stripos( $value, 'quantity' ) !== false ) {
			return false;
		}

		if ( stripos( $value, 'count' ) !== false ) {
			return false;
		}

		return true;
	}

	/**
	 * Compare a resolved condition value against an operator and condition value.
	 *
	 * The comparison strategy depends on the type/field and on the kind of value produced by resolve_condition_value().
	 *
	 * @param string $type            Condition type.
	 * @param string $field           Field key.
	 * @param string $operator        Operator.
	 * @param mixed  $condition_value The user-provided condition value (from rule).
	 * @param mixed  $resolved_value  The value returned by resolve_condition_value().
	 * @param mixed  $min_range       Optional min (for between).
	 * @param mixed  $max_range       Optional max (for between).
	 * @return bool
	 */
	public static function compare_resolved_condition( $type, $field, $operator, $condition_value, $resolved_value, $min_range = null, $max_range = null ) {
		switch ( $type ) {
			case 'General':
				switch ( $field ) {
					case 'always':
						return true;
				}
				return false;
			case 'Cart':
				switch ( $field ) {
					case 'coupons':
					case 'cart_coupons':
						return self::compare_coupon_from_resolved(
							$operator,
							$condition_value,
							is_array( $resolved_value ) ? $resolved_value : array(
								'codes'             => array(),
								'has_free_shipping' => false,
							)
						);
					default:
						// Other cart metrics are numeric.
						return self::compare_numeric_values( (float) $resolved_value, $operator, $condition_value, $min_range, $max_range );
				}

			case 'Product':
				switch ( $field ) {
					case 'cart_contains_products':
						return self::compare_array_conditions( (array) $resolved_value, $operator, $condition_value, 'products' );
					case 'category_products':
						return self::compare_array_conditions( (array) $resolved_value, $operator, $condition_value, 'categories' );
					case 'tag_products':
						return self::compare_array_conditions( (array) $resolved_value, $operator, $condition_value, 'tags' );
					case 'sku':
						$cart_skus = (array) $resolved_value;
						if ( in_array( $operator, array( 'equal', 'doesntEqual' ), true ) ) {
							return self::compare_array_conditions( $cart_skus, $operator, $condition_value, 'sku' );
						}
						return self::compare_string_conditions( $cart_skus, $operator, $condition_value );
					case 'shippingClass':
					case 'shipping_class':
						$classes = (array) $resolved_value;
						$cond_sc = $condition_value;
						if ( ! is_array( $cond_sc ) ) {
							$cond_sc = array( $cond_sc );
						}
						return self::compare_array_conditions( $classes, $operator, $cond_sc, 'shipping_class' );

					case 'price':
					case 'product_quantity':
					case 'product_price':
					case 'product_total':
					case 'product_weight':
					case 'product_height':
					case 'product_width':
					case 'product_length':
					case 'product_volume':
						$values = is_array( $resolved_value ) ? $resolved_value : array();
						return self::compare_numeric_array_values( $values, $operator, $condition_value, $min_range, $max_range );
				}
				return false;

			case 'Customer':
				switch ( $field ) {
					case 'user':
						return self::compare_array_conditions( (array) $resolved_value, $operator, $condition_value, 'users' );
					case 'user_role':
						return self::compare_array_conditions( (array) $resolved_value, $operator, $condition_value, 'user_roles' );
					case 'email':
					case 'phone':
						return self::compare_string_conditions( (array) $resolved_value, $operator, $condition_value, true );
					case 'first_order_spent_amount':
					case 'last_order_spent_amount':
					case 'last_orders_count':
						return self::compare_numeric_values( (float) $resolved_value, $operator, $condition_value, $min_range, $max_range );
				}
				return false;

			case 'Location':
				switch ( $field ) {
					case 'country':
						return self::compare_array_conditions( (array) $resolved_value, $operator, $condition_value, 'countries' );
					case 'state':
						return self::compare_array_conditions( (array) $resolved_value, $operator, $condition_value, 'states' );
					case 'city_town':
						return self::compare_string_conditions( (array) $resolved_value, $operator, $condition_value );
					case 'zip_code':
						return self::compare_postcode_conditions( (array) $resolved_value, $operator, $condition_value );
					case 'distance':
						return false; // Not implemented.
				}
				return false;

			case 'Others':
				// Keep bespoke logic: comparison depends on timezone inside the condition value.
				if ( 'weekdays' === $field ) {
					return self::evaluate_others_condition( 'weekdays', $operator, $condition_value );
				}
				if ( 'time' === $field ) {
					return self::evaluate_others_condition( 'time', $operator, $condition_value );
				}
				return false;

			case 'Attribute':
				return self::compare_array_conditions( (array) $resolved_value, $operator, $condition_value, $field );
		}

		return false;
	}

	/**
	 * Internal helper: compare coupons against resolved coupon data.
	 *
	 * @param string $operator        Operator (contains|doesntContains|equal|doesntEqual|none).
	 * @param mixed  $condition_value Condition value provided in rule (array|string).
	 * @param array  $resolved        Resolved coupon data { codes: string[], has_free_shipping: bool }.
	 * @return bool
	 */
	private static function compare_coupon_from_resolved( $operator, $condition_value, $resolved ) {
		$codes             = isset( $resolved['codes'] ) ? (array) $resolved['codes'] : array();
		$has_free_shipping = ! empty( $resolved['has_free_shipping'] );

		if ( 'none' === $operator ) {
			return empty( $codes );
		}

		// Special token: any free shipping coupon applied.
		if ( is_array( $condition_value ) && in_array( 'wtrs:coupon:free_shipping', $condition_value, true ) ) {
			return $has_free_shipping;
		}

		if ( 'equal' === $operator && in_array( 'all', (array) $condition_value, true ) ) {
			return ! empty( $codes );
		}

		return self::compare_array_conditions( $codes, $operator, $condition_value, 'coupons' );
	}

	/**
	 * Get current store timezone.
	 *
	 * @return \DateTimeZone
	 */
	private static function get_store_timezone() {
		if ( function_exists( 'wp_timezone' ) ) {
			return wp_timezone();
		}
		return new \DateTimeZone( 'UTC' );
	}

	/**
	 * Collect attribute term IDs present in cart for a given attribute slug.
	 *
	 * @param string $field Attribute field (slug, without pa_ prefix).
	 * @param array  $rate  Rate configuration.
	 * @return array<int>
	 */
	private static function collect_attribute_term_ids( $field, $rate ) {
		$cart_items            = self::get_filtered_cart_items( $rate );
		$cart_attribute_values = array();

		foreach ( $cart_items as $cart_item ) {
			$term_ids              = self::get_attribute_term_ids_from_cart_item( $cart_item, $field );
			$cart_attribute_values = array_merge( $cart_attribute_values, $term_ids );
		}

		return array_values( array_unique( $cart_attribute_values ) );
	}

	/**
	 * Get term ids for a Attribute condition from a cart item, checking both variation attributes and product attributes.
	 *
	 * @param array  $cart_item Cart item.
	 * @param string $field attribute field (slug, without pa_ prefix).
	 * @return array
	 */
	private static function get_attribute_term_ids_from_cart_item( $cart_item, $field ) {
		$cart_attribute_values = array();

		$product = $cart_item['data'] ?? null;
		if ( ! $product ) {
			return $cart_attribute_values;
		}

			// Variation attribute first.
		if ( ! empty( $cart_item['variation'] ) ) {
			$variation_attribute_key = 'attribute_' . $field;
			if ( isset( $cart_item['variation'][ $variation_attribute_key ] ) ) {
				$variation_value = $cart_item['variation'][ $variation_attribute_key ];
				if ( ! empty( $variation_value ) ) {
					$taxonomy = wc_sanitize_taxonomy_name( $field );
					$term     = get_term_by( 'slug', $variation_value, $taxonomy );
					if ( ! $term || is_wp_error( $term ) ) {
						$term = get_term_by( 'name', $variation_value, $taxonomy );
					}
					if ( $term && ! is_wp_error( $term ) ) {
						$cart_attribute_values[] = (int) $term->term_id;
						return $cart_attribute_values;
					}
				}
			}
		}

		$attributes = $product->get_attributes();
		foreach ( $attributes as $attribute_slug => $attribute_object ) { // phpcs:ignore
			$term_names_string = $product->get_attribute( $attribute_slug );
			if ( empty( $term_names_string ) ) {
				continue;
			}
			$term_names = explode( ', ', $term_names_string );
			$taxonomy   = wc_sanitize_taxonomy_name( $attribute_slug );
			foreach ( $term_names as $term_name ) {
				$term = get_term_by( 'name', trim( $term_name ), $taxonomy );
				if ( $term && ! is_wp_error( $term ) ) {
					$cart_attribute_values[] = (int) $term->term_id;
				}
			}
		}

		return $cart_attribute_values;
	}

	/**
	 * Evaluate all condition groups.
	 *
	 * @param array      $condition_groups Array of condition groups.
	 * @param array|null $method Shipping method config (if needed).
	 * @return bool True if conditions pass, false otherwise.
	 */
	public static function evaluate_condition_groups( $condition_groups, $method = null ) {
		if ( empty( $condition_groups ) ) {
			return true;
		}

		$group_results = array();
		$group_logics  = array();

		// Evaluate each group.
		foreach ( $condition_groups as $group ) {
			/*
			* Group Logic  -> Logic between current and previous group
			* Global Logic -> Logic within the group (weird name)
			*/

			$group_logic       = isset( $group['logic'] ) ? $group['logic'] : 'AND';
			$global_logic      = isset( $group['globalLogic'] ) ? $group['globalLogic'] : 'AND';
			$condition_results = array();

			// Store the logic for this group (for combining with next group).
			$group_logics[] = $group_logic;

			// TODO @samin: Add short-circuit.
			foreach ( $group['conditions'] as $condition ) {
				if ( empty( $condition ) || ! isset( $condition['type'] ) ) {
					continue;
				}

				$result = self::evaluate_single_condition( $condition, $method, $group );

				if ( null !== $result ) {
					$condition_results[] = $result;
				}
			}

			// Apply global logic within the group.
			if ( ! empty( $condition_results ) ) {
				if ( 'OR' === $global_logic ) {
					$group_result = in_array( true, $condition_results, true );
				} else {
					$group_result = ! in_array( false, $condition_results, true );
				}
				$group_results[] = $group_result;
			}
		}

		// We group AND conditions together and then OR them.
		if ( ! empty( $group_results ) ) {
			$or_group  = array();
			$and_group = array();

			for ( $i = count( $group_results ) - 1; $i >= 0; $i-- ) {
				$and_group[] = $group_results[ $i ];

				if ( 'OR' === $group_logics[ $i ] ) {
					$or_group[] = array_reduce( $and_group, fn ( $carry, $item ) => $carry && $item, true );
					$and_group  = array();
				}
			}

			if ( count( $and_group ) > 0 ) {
				$or_group[] = array_reduce( $and_group, fn ( $carry, $item ) => $carry && $item, true );
			}

			return array_reduce( $or_group, fn ( $carry, $item ) => $carry || $item, false );
		}

		return true;
	}

	/**
	 * Evaluate a single condition.
	 *
	 * @param array      $condition The condition to evaluate.
	 * @param array|null $method Shipping method config (if needed).
	 * @param array|null $conditions Array of all conditions (if needed).
	 * @return bool|null Boolean result or null if condition type not supported.
	 */
	public static function evaluate_single_condition( $condition, $method = null, $conditions = null ) {
		$type  = $condition['type'];
		$field = isset( $condition['field'] ) ? $condition['field'] : '';

		if ( ! Flags::is_pro_or_can_preview() && ! in_array( $field, Flags::CONDITION_FREE_OPTIONS, true ) ) {
			return false;
		}

		$operator  = isset( $condition['operator'] ) ? $condition['operator'] : '';
		$value     = isset( $condition['value'] ) ? $condition['value'] : '';
		$min_range = isset( $condition['min_range'] ) ? $condition['min_range'] : '';
		$max_range = isset( $condition['max_range'] ) ? $condition['max_range'] : '';

		switch ( $type ) {
			case 'General':
				$resolved = self::resolve_condition_value( 'General', $field, $method, $conditions );
				return self::compare_resolved_condition( 'General', $field, $operator, $value, $resolved, $min_range, $max_range );
			case 'Cart':
				$resolved = self::resolve_condition_value( 'Cart', $field, $method, $conditions );
				return self::compare_resolved_condition( 'Cart', $field, $operator, $value, $resolved, $min_range, $max_range );
			case 'Product':
				$resolved = self::resolve_condition_value( 'Product', $field, $method, $conditions );
				return self::compare_resolved_condition( 'Product', $field, $operator, $value, $resolved, $min_range, $max_range );
			case 'Customer':
				$resolved = self::resolve_condition_value( 'Customer', $field, $method, $conditions );
				return self::compare_resolved_condition( 'Customer', $field, $operator, $value, $resolved, $min_range, $max_range );
			case 'Location':
				$resolved = self::resolve_condition_value( 'Location', $field, $method, $conditions );
				return self::compare_resolved_condition( 'Location', $field, $operator, $value, $resolved, $min_range, $max_range );
			case 'Others':
				$resolved = self::resolve_condition_value( 'Others', $field, $method, $conditions ); // For parity; comparison uses bespoke logic.
				unset( $resolved );
				return self::compare_resolved_condition( 'Others', $field, $operator, $value, null, $min_range, $max_range );
			case 'Attribute':
				$resolved = self::resolve_condition_value( 'Attribute', $field, $method, $conditions );
				return self::compare_resolved_condition( 'Attribute', $field, $operator, $value, $resolved, $min_range, $max_range );
			default:
				return null;
		}
	}

	/**
	 * Evaluate other conditions (like weekdays, time).
	 *
	 * @param string $field field name.
	 * @param string $operator operator to use.
	 * @param mixed  $value value.
	 * @return bool|null
	 */
	private static function evaluate_others_condition( $field, $operator, $value ) {
		switch ( $field ) {
			case 'weekdays':
				// Value format: "w1,w2,...|#|Timezone/Name".
				$val_str = is_string( $value ) ? $value : '';

				list( $w_csv, $tz_str ) = array_pad( explode( '|#|', $val_str, 2 ), 2, '' );

				if ( 'all' === $w_csv ) {
					return 'contains' === $operator;
				}

				$weekdays = array();
				if ( '' !== $w_csv ) {
					$parts = explode( ',', $w_csv );
					foreach ( $parts as $p ) {
						$p = trim( $p );
						if ( '' === $p ) {
							continue;
						}
						$weekdays[] = (int) $p; // 0 (Sun) - 6 (Sat).
					}
				}

				// Determine timezone.
				$tz = null;
				try {
					$tz = '' !== $tz_str ? new \DateTimeZone( $tz_str ) : null;
				} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					// Fallback below.
				}
				if ( ! $tz ) {
					$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new \DateTimeZone( 'UTC' );
				}

				// phpcs:disable Generic.Formatting.MultipleStatementAlignment.NotSameWarning, Generic.Formatting.MultipleStatementAlignment.IncorrectWarning
				$now        = new \DateTime( 'now', $tz );
				$current_w  = (int) $now->format( 'w' ); // 0 (Sun) - 6 (Sat).
				// phpcs:enable Generic.Formatting.MultipleStatementAlignment.NotSameWarning, Generic.Formatting.MultipleStatementAlignment.IncorrectWarning

				$contains = in_array( $current_w, $weekdays, true );
				if ( 'contains' === $operator ) {
					return $contains;
				}
				if ( 'doesntContains' === $operator ) {
					return ! $contains;
				}
				return false;

			case 'time':
				// Value format: "HH:MM,HH:MM|#|Timezone/Name".
				$val_str = is_string( $value ) ? $value : '';

				list( $time_csv, $tz_str ) = array_pad( explode( '|#|', $val_str, 2 ), 2, '' );

				list( $from_str, $to_str ) = array_pad( explode( ',', $time_csv, 2 ), 2, '' );
				// phpcs:disable Generic.Formatting.MultipleStatementAlignment.NotSameWarning, Generic.Formatting.MultipleStatementAlignment.IncorrectWarning
				$from_str = trim( $from_str );
				$to_str   = trim( $to_str );
				// phpcs:enable Generic.Formatting.MultipleStatementAlignment.NotSameWarning, Generic.Formatting.MultipleStatementAlignment.IncorrectWarning

				// Parse timezone.
				$tz = null;
				try {
					$tz = '' !== $tz_str ? new \DateTimeZone( $tz_str ) : null;
				} catch ( \Exception $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
					// Fallback below.
				}
				if ( ! $tz ) {
					$tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new \DateTimeZone( 'UTC' );
				}

				// Validate time format HH:MM and convert to minutes.
				$to_minutes = function ( $hhmm ) {
					if ( ! is_string( $hhmm ) || ! preg_match( '/^\d{1,2}:\d{2}$/', $hhmm ) ) {
						return null;
					}
					list( $h, $m ) = array_map( 'intval', explode( ':', $hhmm, 2 ) );
					if ( $h < 0 || $h > 23 || $m < 0 || $m > 59 ) {
						return null;
					}
					return ( $h * 60 ) + $m;
				};

				$from_min = $to_minutes( $from_str );
				$to_min   = $to_minutes( $to_str );
				if ( null === $from_min || null === $to_min ) {
					return false; // Incomplete/invalid range.
				}

				$now           = new \DateTime( 'now', $tz );
				$current_min   = ( (int) $now->format( 'H' ) * 60 ) + (int) $now->format( 'i' );
				$within_window = false;

				if ( $from_min <= $to_min ) {
					// Same-day window.
					$within_window = ( $current_min >= $from_min && $current_min <= $to_min );
				} else {
					// Overnight window, e.g., 22:00 - 06:00.
					$within_window = ( $current_min >= $from_min || $current_min <= $to_min );
				}

				if ( 'equal' === $operator ) {
					return $within_window;
				}
				if ( 'doesntEqual' === $operator ) {
					return ! $within_window;
				}

				return false;

			default:
				return false;
		}
	}

	/**
	 * Compare numeric values based on operator.
	 *
	 * @param mixed  $cart_value Cart value.
	 * @param string $operator   Operator.
	 * @param mixed  $value      Condition value.
	 * @param mixed  $min_range  Min range for between operator.
	 * @param mixed  $max_range  Max range for between operator.
	 * @return bool
	 */
	private static function compare_numeric_values( $cart_value, $operator, $value, $min_range, $max_range ) {
		$cart_value = floatval( $cart_value );
		$min_range  = floatval( $min_range );
		$max_range  = floatval( $max_range );
		$max_range  = empty( $max_range ) ? PHP_FLOAT_MAX : $max_range;

		switch ( $operator ) {
			case 'equal':
				return floatval( $value ) === $cart_value;
			case 'doesntEqual':
				return floatval( $value ) !== $cart_value;
			case 'greaterThan':
				return $cart_value > floatval( $value );
			case 'lesserThan':
				return $cart_value < floatval( $value );
			case 'greaterThanOrEquals':
				return $cart_value >= floatval( $value );
			case 'lesserThanOrEquals':
				return $cart_value <= floatval( $value );
			case 'between':
				return $cart_value >= $min_range && $cart_value <= $max_range;
			default:
				return false;
		}
	}

	/**
	 * Compare numeric array values against an operator.
	 *
	 * Semantics: "any" match, except doesntEqual which requires none to match equality.
	 *
	 * @param array  $values     Numeric values to test (per-product values).
	 * @param string $operator   Operator (equal|doesntEqual|greaterThanOrEquals|lesserThanOrEquals|between).
	 * @param mixed  $value      Scalar value for comparisons (ignored for between).
	 * @param mixed  $min_range  Min range for between.
	 * @param mixed  $max_range  Max range for between.
	 * @return bool
	 */
	private static function compare_numeric_array_values( $values, $operator, $value, $min_range, $max_range ) {
		$vals = array_map( 'floatval', (array) $values );

		if ( empty( $vals ) ) {
			// With no items, only doesntEqual can be true (none equals the given value).
			return ( 'doesntEqual' === $operator );
		}

		switch ( $operator ) {
			case 'equal':
				$target = floatval( $value );
				foreach ( $vals as $v ) {
					if ( $v === $target ) {
						return true;
					}
				}
				return false;

			case 'doesntEqual':
				$target = floatval( $value );
				foreach ( $vals as $v ) {
					if ( $v === $target ) {
						return false; // one equals -> condition fails.
					}
				}
				return true; // none equals -> condition passes.

			case 'greaterThan':
				$target = floatval( $value );
				foreach ( $vals as $v ) {
					if ( $v > $target ) {
						return true;
					}
				}
				return false;

			case 'lesserThan':
				$target = floatval( $value );
				foreach ( $vals as $v ) {
					if ( $v < $target ) {
						return true;
					}
				}
				return false;

			case 'greaterThanOrEquals':
				$target = floatval( $value );
				foreach ( $vals as $v ) {
					if ( $v >= $target ) {
						return true;
					}
				}
				return false;

			case 'lesserThanOrEquals':
				$target = floatval( $value );
				foreach ( $vals as $v ) {
					if ( $v <= $target ) {
						return true;
					}
				}
				return false;

			case 'between':
				$min = floatval( $min_range );
				$max = floatval( $max_range );
				$max = empty( $max_range ) ? PHP_FLOAT_MAX : $max;
				foreach ( $vals as $v ) {
					if ( $v >= $min && $v <= $max ) {
						return true;
					}
				}
				return false;
		}

		return false;
	}

	/**
	 * Remove prefix before colon in a value.
	 *
	 * @param string $value Value to process.
	 * @return string Processed value without prefix.
	 */
	private static function remove_prefix_before_colon( $value ) {
		$parts = explode( ':', $value, 2 );
		return isset( $parts[1] ) ? $parts[1] : $value;
	}

	/**
	 * Compare array conditions (IDs, roles, etc.).
	 *
	 * @param array  $cart_values     Cart values.
	 * @param string $operator        Operator.
	 * @param mixed  $condition_value Condition value.
	 * @param string $type            Type for all_* values.
	 * @return bool
	 */
	private static function compare_array_conditions( $cart_values, $operator, $condition_value, $type ) {
		if ( ! is_array( $condition_value ) ) {
			$condition_value = array( $condition_value );
		}

		// Handle all_* values.
		if ( count( $condition_value ) === 1 && 0 === strpos( $condition_value[0], 'all' ) ) {
			return self::handle_all_condition( $operator, $type );
		}

		$cart_values_str      = array_map( 'strval', $cart_values );
		$condition_values_str = array();
		foreach ( $condition_value as $value ) {
			$parts = explode( ',', $value );
			foreach ( $parts as $part ) {
				$condition_values_str[] = trim( $part );
			}
		}
		$cart_values_str      = array_map( 'strtolower', $cart_values_str );
		$condition_values_str = array_map( 'strtolower', $condition_values_str );

		if ( 'countries' === $type || 'states' === $type ) {
			$condition_values_str = array_map( array( __CLASS__, 'remove_prefix_before_colon' ), $condition_values_str );
		}

		switch ( $operator ) {
			case 'equal':
				sort( $cart_values_str );
				sort( $condition_values_str );
				return $condition_values_str === $cart_values_str;

			case 'doesntEqual':
				sort( $cart_values_str );
				sort( $condition_values_str );
				return $condition_values_str !== $cart_values_str;

			case 'contains':
				return ! empty( array_intersect( $cart_values_str, $condition_values_str ) );

			case 'doesntContains':
				return empty( array_intersect( $cart_values_str, $condition_values_str ) );
			default:
				return false;
		}
	}

	/**
	 * Compare string conditions (SKU, email, phone, etc.).
	 *
	 * @param array  $cart_values     Cart values.
	 * @param string $operator        Operator.
	 * @param mixed  $condition_value Condition value.
	 * @param bool   $case_sensitive   Whether comparison is case sensitive.
	 * @return bool
	 */
	private static function compare_string_conditions( $cart_values, $operator, $condition_value, $case_sensitive = false ) {
		// Parse comma-separated values.
		if ( is_string( $condition_value ) ) {
			$condition_values = array_map( 'trim', explode( ',', $condition_value ) );
		} else {
			$condition_values = is_array( $condition_value ) ? $condition_value : array( $condition_value );
		}

		if ( ! $case_sensitive ) {
			$cart_values      = array_map( 'strtolower', $cart_values );
			$condition_values = array_map( 'strtolower', $condition_values );
		}

		switch ( $operator ) {
			case 'equal':
				return ! empty( array_intersect( $cart_values, $condition_values ) );

			case 'doesntEqual':
				return empty( array_intersect( $cart_values, $condition_values ) );

			case 'contains':
				foreach ( $cart_values as $cart_value ) {
					foreach ( $condition_values as $condition_val ) {
						if ( strpos( $cart_value, $condition_val ) !== false ) {
							return true;
						}
					}
				}
				return false;

			case 'doesntContains':
				foreach ( $cart_values as $cart_value ) {
					foreach ( $condition_values as $condition_val ) {
						if ( strpos( $cart_value, $condition_val ) !== false ) {
							return false;
						}
					}
				}
				return true;

			case 'startsWith':
				foreach ( $cart_values as $cart_value ) {
					foreach ( $condition_values as $condition_val ) {
						if ( strpos( $cart_value, $condition_val ) === 0 ) {
							return true;
						}
					}
				}
				return false;

			case 'endsWith':
				foreach ( $cart_values as $cart_value ) {
					foreach ( $condition_values as $condition_val ) {
						if ( substr( $cart_value, -strlen( $condition_val ) ) === $condition_val ) {
							return true;
						}
					}
				}
				return false;

			case 'doesntStartsWith':
				foreach ( $cart_values as $cart_value ) {
					foreach ( $condition_values as $condition_val ) {
						if ( strpos( $cart_value, $condition_val ) === 0 ) {
							return false;
						}
					}
				}
				return true;

			case 'doesntEndsWith':
				foreach ( $cart_values as $cart_value ) {
					foreach ( $condition_values as $condition_val ) {
						if ( substr( $cart_value, -strlen( $condition_val ) ) === $condition_val ) {
							return false;
						}
					}
				}
				return true;

			default:
				return false;
		}
	}

	/**
	 * Compare postcode conditions using WooCommerce-like wildcard and range matching.
	 *
	 * @param array  $cart_values     Cart postcode values.
	 * @param string $operator        Operator.
	 * @param mixed  $condition_value Condition value.
	 * @return bool
	 */
	private static function compare_postcode_conditions( $cart_values, $operator, $condition_value ) {
		$cart_postcodes = array_filter( array_map( 'strval', (array) $cart_values ), 'strlen' );

		if ( is_string( $condition_value ) ) {
			$condition_values = array_map( 'trim', explode( ',', $condition_value ) );
		} else {
			$condition_values = is_array( $condition_value ) ? $condition_value : array( $condition_value );
			$parsed_values    = array();
			foreach ( $condition_values as $value_item ) {
				$parts = explode( ',', (string) $value_item );
				foreach ( $parts as $part ) {
					$parsed_values[] = trim( $part );
				}
			}
			$condition_values = $parsed_values;
		}

		$condition_values = array_values( array_filter( array_map( 'strval', $condition_values ), 'strlen' ) );

		if ( empty( $condition_values ) ) {
			return in_array( $operator, array( 'doesntEqual', 'doesntContains' ), true );
		}

		$country = '';
		if ( WC()->customer ) {
			$country = (string) WC()->customer->get_shipping_country();
			if ( '' === $country ) {
				$country = (string) WC()->customer->get_billing_country();
			}
		}

		$has_match = false;
		foreach ( $cart_postcodes as $postcode ) {
			foreach ( $condition_values as $rule ) {
				if ( self::postcode_matches_rule( $postcode, $rule, $country ) ) {
					$has_match = true;
					break 2;
				}
			}
		}

		switch ( $operator ) {
			case 'equal':
			case 'contains':
				return $has_match;

			case 'doesntEqual':
			case 'doesntContains':
				return ! $has_match;

			default:
				return false;
		}
	}

	/**
	 * Match a postcode against one rule value.
	 *
	 * @param string $postcode Customer postcode.
	 * @param string $rule     Rule postcode pattern/range.
	 * @param string $country  Country code.
	 * @return bool
	 */
	private static function postcode_matches_rule( $postcode, $rule, $country = '' ) {
		$postcode = function_exists( 'wc_normalize_postcode' ) ? wc_normalize_postcode( (string) $postcode ) : strtoupper( str_replace( array( ' ', '-' ), '', (string) $postcode ) );
		$rule     = trim( (string) $rule );

		if ( '' === $postcode || '' === $rule ) {
			return false;
		}

		if ( function_exists( 'wc_postcode_location_matcher' ) ) {
			$rule_candidates = array( $rule );
			if ( function_exists( 'wc_normalize_postcode' ) ) {
				$rule_candidates[] = wc_normalize_postcode( $rule );
			}
			$rule_candidates = array_values( array_unique( array_filter( array_map( 'strval', $rule_candidates ), 'strlen' ) ) );

			$objects = array();
			foreach ( $rule_candidates as $idx => $candidate ) {
				$objects[] = (object) array(
					'rule_id'    => $idx,
					'rule_value' => $candidate,
				);
			}

			$matches = wc_postcode_location_matcher( $postcode, $objects, 'rule_id', 'rule_value', (string) $country );
			return ! empty( $matches );
		}

		$normalized_rule = strtoupper( str_replace( array( ' ', '-' ), '', $rule ) );

		if ( false !== strpos( $normalized_rule, '...' ) ) {
			$range = array_map( 'trim', explode( '...', $normalized_rule ) );
			if ( 2 !== count( $range ) ) {
				return false;
			}

			list( $min, $max ) = $range;
			if ( ! is_numeric( $min ) || ! is_numeric( $max ) ) {
				$compare = self::make_numeric_postcode( $postcode );
				$min     = str_pad( self::make_numeric_postcode( $min ), strlen( $compare ), '0' );
				$max     = str_pad( self::make_numeric_postcode( $max ), strlen( $compare ), '0' );
			} else {
				$compare = $postcode;
			}

			return ( $compare >= $min && $compare <= $max );
		}

		$pattern = '/^' . str_replace( '\\*', '.*', preg_quote( $normalized_rule, '/' ) ) . '$/i';
		return (bool) preg_match( $pattern, $postcode );
	}

	/**
	 * Convert alphanumeric postcode to numeric representation.
	 *
	 * @param string $postcode Postcode.
	 * @return string
	 */
	private static function make_numeric_postcode( $postcode ) {
		if ( function_exists( 'wc_make_numeric_postcode' ) ) {
			return (string) wc_make_numeric_postcode( $postcode );
		}

		$postcode           = strtoupper( str_replace( array( ' ', '-' ), '', (string) $postcode ) );
		$postcode_length    = strlen( $postcode );
		$letters_to_numbers = array_merge( array( 0 ), range( 'A', 'Z' ) );
		$letters_to_numbers = array_flip( $letters_to_numbers );
		$numeric_postcode   = '';

		for ( $i = 0; $i < $postcode_length; $i++ ) {
			if ( is_numeric( $postcode[ $i ] ) ) {
				$numeric_postcode .= str_pad( $postcode[ $i ], 2, '0', STR_PAD_LEFT );
			} elseif ( isset( $letters_to_numbers[ $postcode[ $i ] ] ) ) {
				$numeric_postcode .= str_pad( $letters_to_numbers[ $postcode[ $i ] ], 2, '0', STR_PAD_LEFT );
			} else {
				$numeric_postcode .= '00';
			}
		}

		return $numeric_postcode;
	}

	/**
	 * Handle all_* conditions.
	 *
	 * @param string $operator Operator.
	 * @param string $type     Type (unused but kept for interface consistency).
	 * @return bool
	 */
	private static function handle_all_condition( $operator, $type ) {
		// For all_* conditions, we consider them as always matching.
		// In real implementation, you might want to check if the cart actually contains any of that type.
		unset( $type ); // Prevent unused parameter warning.

		switch ( $operator ) {
			case 'equal':
			case 'contains':
				return true;
			case 'doesntEqual':
			case 'doesntContains':
			default:
				return false;
		}
	}

	/**
	 * Get cart dimension value.
	 *
	 * @param string $dimension Dimension type (length, width, height).
	 * @return float
	 */
	private static function get_cart_dimension( $dimension ) {
		if ( ! WC()->cart ) {
			return 0;
		}

		$total_dimension = 0;
		$cart_items      = WC()->cart->get_cart();

		foreach ( $cart_items as $cart_item ) {
			$product = $cart_item['data'];
			if ( ! $product ) {
				continue;
			}

			$product_dimension = 0;
			switch ( $dimension ) {
				case 'length':
					$product_dimension = floatval( $product->get_length() );
					break;
				case 'width':
					$product_dimension = floatval( $product->get_width() );
					break;
				case 'height':
					$product_dimension = floatval( $product->get_height() );
					break;
			}

			$total_dimension += $product_dimension * $cart_item['quantity'];
		}

		return $total_dimension;
	}

	/**
	 * Get cart volume.
	 *
	 * @return float
	 */
	private static function get_cart_volume() {
		if ( ! WC()->cart ) {
			return 0;
		}

		$total_volume = 0;
		$cart_items   = WC()->cart->get_cart();

		foreach ( $cart_items as $cart_item ) {
			$product = $cart_item['data'];
			if ( ! $product ) {
				continue;
			}

			$length = floatval( $product->get_length() );
			$width  = floatval( $product->get_width() );
			$height = floatval( $product->get_height() );

			$volume        = $length * $width * $height;
			$total_volume += $volume * $cart_item['quantity'];
		}

		return $total_volume;
	}

	/**
	 * Get customer's first order amount.
	 *
	 * @param int $user_id User ID.
	 * @return float
	 */
	private static function get_customer_first_order_amount( $user_id ) {
		if ( ! $user_id ) {
			return 0;
		}

		$orders = wc_get_orders(
			array(
				'customer' => $user_id,
				'status'   => array( 'completed', 'processing' ),
				'limit'    => 1,
				'orderby'  => 'date',
				'order'    => 'ASC',
			)
		);

		if ( ! empty( $orders ) ) {
			return floatval( $orders[0]->get_total() );
		}

		return 0;
	}

	/**
	 * Get filtered cart item based on rate conditions.
	 *
	 * @param array $rate Rate.
	 * @return array
	 */
	private static function get_filtered_cart_items( $rate ) {
		$cart_items       = WC()->cart->get_cart();
		$rate_product_ids = isset( $rate['basedOn'] ) && 'fixed' !== ( $rate['type'] ?? '' ) ? self::extract_product_ids_from_rate( $rate ) : null;

		if ( ! empty( $rate_product_ids ) && is_array( $rate_product_ids ) ) {
			$cart_items = array_filter(
				$cart_items,
				function ( $ci ) use ( $rate_product_ids ) {
					$pid = isset( $ci['product_id'] ) ? (int) $ci['product_id'] : 0;
					return in_array( $pid, $rate_product_ids ); // phpcs:ignore
				}
			);
		}

		return $cart_items;
	}

	/**
	 * Get customer's last order amount.
	 *
	 * @param int $user_id User ID.
	 * @return float
	 */
	private static function get_customer_last_order_amount( $user_id ) {
		if ( ! $user_id ) {
			return 0;
		}

		$orders = wc_get_orders(
			array(
				'customer' => $user_id,
				'status'   => array( 'completed', 'processing' ),
				'limit'    => 1,
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);

		if ( ! empty( $orders ) ) {
			return floatval( $orders[0]->get_total() );
		}

		return 0;
	}

	/**
	 * Get customer's orders count.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	private static function get_customer_orders_count( $user_id ) {
		if ( ! $user_id ) {
			return 0;
		}

		$orders = wc_get_orders(
			array(
				'customer' => $user_id,
				'status'   => array( 'completed', 'processing' ),
				'limit'    => -1,
				'return'   => 'ids',
			)
		);

		return is_array( $orders ) ? count( $orders ) : 0;
	}
}
