<?php // phpcs:ignore

namespace WTRS\Includes\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizer Class
 */
class Sanitizer {

	/**
	 * Sanitize rule data before saving.
	 *
	 * @param array $rule_data Raw rule data.
	 * @return array Sanitized rule data.
	 */
	public static function sanitize_rule_data( $rule_data ) {
		$sanitized = array();

		$sanitized['id']         = isset( $rule_data['id'] ) ? sanitize_text_field( $rule_data['id'] ) : '';
		$sanitized['instanceId'] = isset( $rule_data['instanceId'] ) ? intval( $rule_data['instanceId'] ) : null;

		if ( isset( $rule_data['generalSettings'] ) && is_array( $rule_data['generalSettings'] ) ) {
			$sanitized['generalSettings'] = array(
				'scenarioName' => sanitize_text_field( $rule_data['generalSettings']['scenarioName'] ?? '' ), // phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
				'shippingZone' => isset( $rule_data['generalSettings']['shippingZone'] ) ? sanitize_text_field( $rule_data['generalSettings']['shippingZone'] ) : '', // phpcs:ignore WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
			);
		}

		if ( isset( $rule_data['shippingMethods'] ) && is_array( $rule_data['shippingMethods'] ) ) {
			$sanitized['shippingMethods'] = self::sanitize_shipping_method_config( $rule_data['shippingMethods'] );
		}

		$sanitized['tax'] = array(
			'status'   => sanitize_text_field( $rule_data['tax']['status'] ?? 'none' ),
			'included' => (bool) ( $rule_data['tax']['included'] ?? false ),
		);

		$sanitized['visibleToUser'] = sanitize_text_field( $rule_data['visibleToUser'] ?? 'both' );

		$sanitized['handlingFee'] = array(
			'isEnabled' => (bool) ( $rule_data['handlingFee']['isEnabled'] ?? false ),
			'label'     => sanitize_text_field( $rule_data['handlingFee']['label'] ?? '' ),
			'type'      => sanitize_text_field( $rule_data['handlingFee']['type'] ?? 'fixed' ),
			'amount'    => sanitize_text_field( $rule_data['handlingFee']['amount'] ?? 0 ),
			'minAmount' => sanitize_text_field( $rule_data['handlingFee']['minAmount'] ?? '' ),
			'maxAmount' => sanitize_text_field( $rule_data['handlingFee']['maxAmount'] ?? '' ),
			'applyTo'   => sanitize_text_field( $rule_data['handlingFee']['applyTo'] ?? 'entire_order' ),
			'isTaxable' => (bool) ( $rule_data['handlingFee']['isTaxable'] ?? false ),
		);

		$sanitized['isDebugEnabled'] = (bool) ( $rule_data['isDebugEnabled'] ?? false );

		$sanitized['publishMode'] = sanitize_text_field( $rule_data['publishMode'] ?? 'draft' );

		$sanitized['version']   = sanitize_text_field( $rule_data['version'] ?? '2' );
		$sanitized['createdAt'] = isset( $rule_data['createdAt'] ) ? absint( $rule_data['createdAt'] ) : time();

		return $sanitized;
	}

	/**
	 * Sanitize conditions configuration.
	 *
	 * @param array $condition_groups Raw conditions config.
	 * @return array Sanitized conditions config.
	 */
	private static function sanitize_condition_groups( $condition_groups ) {
		$sanitized = array();

		foreach ( $condition_groups as $group ) {
			if ( ! is_array( $group ) ) {
				continue;
			}

			$sanitized_group = array(
				'id'          => sanitize_text_field( $group['id'] ?? '' ),
				'logic'       => sanitize_text_field( $group['logic'] ?? 'AND' ),
				'globalLogic' => sanitize_text_field( $group['globalLogic'] ?? 'AND' ),
			);

			if ( isset( $group['conditions'] ) && is_array( $group['conditions'] ) ) {
				$sanitized_group['conditions'] = array();

				foreach ( $group['conditions'] as $dr ) {
					if ( ! is_array( $dr ) ) {
						continue;
					}

					$sanitized_condition = array(
						'id'       => sanitize_text_field( $dr['id'] ?? '' ),
						'type'     => sanitize_text_field( $dr['type'] ?? '' ),
						'field'    => sanitize_text_field( $dr['field'] ?? '' ),
						'operator' => sanitize_text_field( $dr['operator'] ?? '' ),
						'value'    => self::sanitize_condition_value( $dr['value'] ?? '' ),
					);

					// Add min_range and max_range for 'between' operator.
					if ( isset( $dr['min_range'] ) ) {
						$sanitized_condition['min_range'] = self::sanitize_condition_value( $dr['min_range'] );
					}
					if ( isset( $dr['max_range'] ) ) {
						$sanitized_condition['max_range'] = self::sanitize_condition_value( $dr['max_range'] );
					}

					$sanitized_group['conditions'][] = $sanitized_condition;
				}
			}

			$sanitized[] = $sanitized_group;
		}

		return $sanitized;
	}

	/**
	 * Sanitize condition value based on its type.
	 *
	 * @param mixed $value The condition value to sanitize.
	 * @return mixed Sanitized condition value.
	 */
	private static function sanitize_condition_value( $value ) {
		if ( empty( $value ) ) {
			return '';
		}

		// Handle arrays.
		if ( is_array( $value ) ) {
			$sanitized_array = array();

			foreach ( $value as $item ) {
				// If array contains objects with 'id' property.
				if ( is_array( $item ) && isset( $item['id'] ) ) {
					// Check for special "select all" identifiers.
					$select_all_identifiers = array(
						'all_products',
						'all_categories',
						'all_tags',
						'all_users',
						'all_user_roles',
						'all_colors',
						'all_sizes',
						'all_countries',
						'all_states',
						'all', // on new design multiselect all options value will be 'all'.
					);

					if ( is_numeric( $item['id'] ) ) {
						$sanitized_array[] = absint( $item['id'] );
					} else {
						$sanitized_array[] = sanitize_text_field( $item['id'] );
					}
				} elseif ( is_numeric( $item ) ) { // If array contains simple values (numbers, strings).
					$sanitized_array[] = absint( $item );
				} elseif ( is_string( $item ) ) {
					$sanitized_array[] = sanitize_text_field( $item );
				}
			}

			return $sanitized_array;
		}

		// Handle numeric values.
		if ( is_numeric( $value ) ) {
			return $value; // Keep original numeric format (int or float).
		}

		// Handle string values.
		if ( is_string( $value ) ) {
			return $value;
		}

		// For any other type, convert to string and sanitize.
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitize shipping method configuration.
	 *
	 * @param array $shipping_methods Raw shipping method config.
	 * @return array Sanitized shipping method config.
	 */
	private static function sanitize_shipping_method_config( $shipping_methods ) {
		$sanitized = array();

		if ( empty( $shipping_methods ) || ! is_array( $shipping_methods ) ) {
			return $sanitized;
		}

		foreach ( $shipping_methods as $method ) {
			if ( ! is_array( $method ) ) {
				continue;
			}

			$sanitized_method = array(
				'id'                            => sanitize_text_field( $method['id'] ?? '' ),
				'type'                          => sanitize_text_field( $method['type'] ?? '' ),
				'status'                        => (bool) ( $method['status'] ?? false ),
				'description'                   => sanitize_textarea_field( $method['description'] ?? '' ),
				'methodName'                    => sanitize_text_field( $method['methodName'] ?? '' ),
				'methodType'                    => sanitize_text_field( $method['methodType'] ?? '' ),
				'deliveryTimeFrom'              => sanitize_text_field( $method['deliveryTimeFrom'] ?? '' ),
				'deliveryTimeTo'                => sanitize_text_field( $method['deliveryTimeTo'] ?? '' ),
				'deliveryTimeEnabled'           => (bool) ( $method['deliveryTimeEnabled'] ?? false ),
				'rateCalcType'                  => sanitize_text_field( $method['rateCalcType'] ?? 'sum' ),
				'flatRateConfig'                => array(
					'isEnabled'           => false,
					'type'                => 'shipping_class',
					'calcType'            => 'per_class',
					'values'              => array(),
					'noShippingClassCost' => '',
				),
				'applyFreeShippingBeforeCoupon' => (bool) ( $method['applyFreeShippingBeforeCoupon'] ?? false ),
				'drEnabled'                     => (bool) ( $method['drEnabled'] ?? false ),
				'drGroups'                      => array(),
				'enableSpecialActions'          => (bool) ( $method['enableSpecialActions'] ?? false ),
			);

			// Display Rules.
			if ( isset( $method['drGroups'] ) && is_array( $method['drGroups'] ) ) {
				$sanitized_method['drGroups'] = self::sanitize_condition_groups( $method['drGroups'] );
			}

			// Sanitize flatRateConfig.
			if ( isset( $method['flatRateConfig'] ) && is_array( $method['flatRateConfig'] ) ) {
				$flat_rate_config = $method['flatRateConfig'];

				$sanitized_method['flatRateConfig']['isEnabled']           = (bool) ( $flat_rate_config['isEnabled'] ?? false );
				$sanitized_method['flatRateConfig']['type']                = sanitize_text_field( $flat_rate_config['type'] ?? 'shipping_class' );
				$sanitized_method['flatRateConfig']['calcType']            = in_array( $flat_rate_config['calcType'] ?? '', array( 'per_order', 'per_class' ), true ) ? $flat_rate_config['calcType'] : 'per_class';
				$sanitized_method['flatRateConfig']['noShippingClassCost'] = isset( $flat_rate_config['noShippingClassCost'] ) && is_string( $flat_rate_config['noShippingClassCost'] ) ? $flat_rate_config['noShippingClassCost'] : '';

				if ( isset( $flat_rate_config['values'] ) && is_array( $flat_rate_config['values'] ) ) {
					foreach ( $flat_rate_config['values'] as $entry ) {
						if ( ! is_array( $entry ) ) {
							continue;
						}

						$sanitized_method['flatRateConfig']['values'][] = array(
							'id'   => sanitize_text_field( $entry['id'] ?? '' ),
							'cost' => isset( $entry['cost'] ) && is_string( $entry['cost'] ) ? $entry['cost'] : '',
						);
					}
				}
			}

			if ( isset( $method['rates'] ) && is_array( $method['rates'] ) ) {
				$sanitized_method['rates'] = array();

				foreach ( $method['rates'] as $rate ) {
					if ( ! is_array( $rate ) ) {
						continue;
					}

					$sanitized_method['rates'][] = array(
						'id'            => sanitize_text_field( $rate['id'] ?? '' ),
						'min'           => sanitize_text_field( $rate['min'] ?? '' ),
						'max'           => sanitize_text_field( $rate['max'] ?? '' ),
						'type'          => sanitize_text_field( $rate['type'] ?? '' ),
						'initialValue'  => sanitize_text_field( $rate['initialValue'] ?? '' ),
						'everyValue'    => floatval( $rate['everyValue'] ?? 0 ),
						'thenValue'     => floatval( $rate['thenValue'] ?? 0 ),
						'firstValue'    => floatval( $rate['firstValue'] ?? 0 ),
						'basedOn'       => sanitize_text_field( $rate['basedOn'] ?? '' ),
						'specialAction' => sanitize_text_field( $rate['specialAction'] ?? 'pass' ),
						'conditions'    => isset( $rate['conditions'] ) && is_array( $rate['conditions'] ) ? $rate['conditions'] : array(),
					);
				}
			}

			// Carrier.
			$sanitized_method['carrierKey']      = sanitize_text_field( $method['carrierKey'] ?? '' );
			$sanitized_method['carrierSettings'] = $method['carrierSettings'] ?? array();
			$sanitized_method['packMethod']      = sanitize_text_field( $method['packMethod'] ?? 'per_item' );
			$sanitized_method['packMaxWeight']   = floatval( $method['packMaxWeight'] );
			$sanitized_method['packBoxes']       = array();

			if ( isset( $method['packBoxes'] ) && is_array( $method['packBoxes'] ) ) {
				foreach ( $method['packBoxes'] as $box ) {
					$sanitized_method['packBoxes'][] = array(
						'id'           => sanitize_text_field( $box['id'] ?? '' ),
						'length'       => floatval( $box['length'] ?? 0 ),
						'width'        => floatval( $box['width'] ?? 0 ),
						'height'       => floatval( $box['height'] ?? 0 ),
						'weight'       => floatval( $box['weight'] ?? 0 ),
						'boxWeight'    => floatval( $box['boxWeight'] ?? 0 ),
						'boxMaxWeight' => floatval( $box['boxMaxWeight'] ?? 0 ),
						'padding'      => floatval( $box['padding'] ?? 0 ),
						'data'         => $box['data'] ?? array(),
					);
				}
			}

			// Markup.
			$sanitized_method['markUpRate'] = floatval( $method['markUpRate'] ?? 0 );
			$sanitized_method['markUpType'] = in_array( $method['markUpType'] ?? '', array( 'fixed', 'percent' ), true ) ? $method['markUpType'] : 'fixed';

			// Fallback.
			$sanitized_method['fallbackTitle'] = sanitize_text_field( $method['fallbackTitle'] ?? '' );
			$sanitized_method['fallbackRate']  = floatval( $method['fallbackRate'] ?? 0 );
			$sanitized_method['fallbackType']  = in_array( $method['fallbackType'] ?? '', array( 'fixed', 'percent' ), true ) ? $method['fallbackType'] : 'fixed';

			$sanitized[] = $sanitized_method;
		}

		return $sanitized;
	}

	/**
	 * Sanitize shipping zones data before saving.
	 *
	 * @param array $zone Raw zones data.
	 * @return array Sanitized zones data.
	 */
	public static function sanitize_shipping_zones_data( $zone ) {
		$sanitized_zone = array();

		$sanitized_zone['id'] = isset( $zone['id'] ) ? sanitize_text_field( $zone['id'] ) : '';

		$sanitized_zone['name'] = isset( $zone['name'] ) ? sanitize_text_field( $zone['name'] ) : '';

		$sanitized_zone['regions'] = array();

		if ( isset( $zone['regions'] ) && is_array( $zone['regions'] ) ) {
			foreach ( $zone['regions'] as $region_key => $region_value ) {
				$sanitized_key                               = sanitize_text_field( $region_key );
				$sanitized_value                             = sanitize_text_field( $region_value );
				$sanitized_zone['regions'][ $sanitized_key ] = $sanitized_value;
			}
		}

		return $sanitized_zone;
	}
}
