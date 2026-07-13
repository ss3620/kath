<?php // phpcs:ignore

namespace WTRS\Includes;

use DateTime;
use WTRS\Includes\Utils\Flags;
use WTRS\Includes\Utils\Sanitizer;
use WTRS\Traits\Singleton;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Database Class
 */
class DB {

	use Singleton;

	/**
	 * WordPress option key for storing shipping rules.
	 */
	private const OPTION_KEY = 'wtrs-shipping-rules';

	/**
	 * WordPress option key for storing plugin settings.
	 */
	private const SETTINGS_KEY = 'wtrs-settings';

	/**
	 * Get plugin settings
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = get_option(
			self::SETTINGS_KEY,
			array(),
		);

		return wp_parse_args(
			$settings,
			array(
				'taxDefault'         => false,
				'debugMode'          => false,
				'cleanUpOnUninstall' => false,
				'wtrsAddress1'       => '',
				'wtrsAddress2'       => '',
				'wtrsCity'           => '',
				'wtrsState'          => '',
				'wtrsPostcode'       => '',
				'wtrsCountry'        => '',
			)
		);
	}

	/**
	 * Update plugin settings
	 *
	 * @param array $new_settings new settings.
	 * @return void
	 */
	public function update_settings( $new_settings ) {
		$args = wp_parse_args( $new_settings, self::get_settings() );
		update_option( self::SETTINGS_KEY, $args, true );
		do_action( 'wtrs_carrier_invalidate_cache' );
	}

	/**
	 * Get all shipping rules from the database.
	 *
	 * @return array Array of shipping rules.
	 */
	public function get_shipping_rules() {
		$rules = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $rules ) ) {
			$rules = array();
		}

		$valid_rules = array();

		foreach ( $rules as &$rule ) {
			// Getting all the new rules.
			if ( '3' === ( $rule['version'] ?? null ) ) {
				$valid_rules[] = $rule;
			}
		}

		usort(
			$valid_rules,
			function ( $a, $b ) {
				$x = isset( $a['createdAt'] ) ? intval( $a['createdAt'] ) : 0;
				$y = isset( $b['createdAt'] ) ? intval( $b['createdAt'] ) : 0;
				return $y - $x;
			}
		);

		$rules = $valid_rules;

		return $rules;
	}

	/**
	 * Get active shipping rules only.
	 *
	 * @return array Array of active shipping rules.
	 */
	public function get_applicable_shipping_rules() {
		$all_rules    = $this->get_shipping_rules();
		$active_rules = array();

		foreach ( $all_rules as $rule ) {
			if ( -1 === (int) ( $rule['generalSettings']['shippingZone'] ?? 0 ) ) {
				if ( 'publish' === ( $rule['publishMode'] ?? 'draft' ) ) {
					$active_rules[] = $rule;
				}
				continue;
			}

			$instance_id = $rule['instanceId'];

			$instance = \WC_Shipping_Zones::get_shipping_method( (int) $instance_id );

			if ( ! $instance || ! $instance->is_enabled() ) {
				continue;
			}

			$active_rules[] = $rule;
		}

		if ( Xpo::is_lc_active() ) {
			return $active_rules;
		}

		if ( Flags::show_pro_preview() ) {
			foreach ( $active_rules as &$rule ) {
				if ( isset( $rule['shippingMethods'] ) && is_array( $rule['shippingMethods'] ) ) {
					$num_of_methods = count( $rule['shippingMethods'] );
					for ( $i = Flags::MAX_SHIPPING_METHODS; $i < $num_of_methods; $i++ ) {
						$rule['shippingMethods'][ $i ]['methodName'] = ( $rule['shippingMethods'][ $i ]['methodName'] ?? '' ) . ' (PRO)';
					}
				}
			}

			return $active_rules;
		}

		$active_rules = array_slice( $active_rules, 0, Flags::MAX_SHIPPING_RULES );

		foreach ( $active_rules as &$rule ) {

			// Conditions.
			if ( isset( $rule['shippingMethods']['drGroups'] ) && is_array( $rule['shippingMethods']['drGroups'] ) ) {
				$rule['shippingMethods']['drGroups'] = array_slice( $rule['shippingMethods']['drGroups'], 0, Flags::MAX_CONDITION_GROUPS );

				foreach ( $rule['shippingMethods']['drGroups'] as &$group ) {
					if ( isset( $group['conditions'] ) && is_array( $group['conditions'] ) ) {
						$group['conditions'] = array_slice( $group['conditions'], 0, Flags::MAX_NESTED_CONDITIONS );

						// Removing pro conditions.
						if ( ! Flags::is_pro_or_can_preview() ) {
							$idx_to_remove = array();
							$num_of_groups = count( $group['conditions'] );
							for ( $i = 0; $i < $num_of_groups; $i++ ) {
								if ( ! in_array( $group['conditions'][ $i ]['field'], Flags::CONDITION_FREE_OPTIONS, true ) ) {
									$idx_to_remove[] = $i;
								}
							}

							for ( $j = count( $idx_to_remove ) - 1; $j >= 0; $j-- ) {
								unset( $group['conditions'][ $idx_to_remove[ $j ] ] );
							}

							$group['conditions'] = array_values( $group['conditions'] );
						}
					}
				}
			}

			// Methods.
			if ( isset( $rule['shippingMethods'] ) && is_array( $rule['shippingMethods'] ) ) {
				$rule['shippingMethods'] = array_slice( $rule['shippingMethods'], 0, Flags::MAX_NESTED_CONDITIONS );

				// Flexible segments.
				foreach ( $rule['shippingMethods'] as &$method ) {
					if ( isset( $method['methodType'] ) &&
						'flexible_amount' === $method['methodType'] &&
						isset( $method['rates'] ) &&
						is_array( $method['rates'] )
					) {
						$method['rates'] = array_slice( $method['rates'], 0, Flags::MAX_FLEXIBLE_SEGMENTS );

						// Remove pro rate conditions.
						$num_of_rates = count( $method['rates'] );
						for ( $i = 0; $i < $num_of_rates; $i++ ) {
							$rate_conditions = isset( $method['rates'][ $i ]['conditions'] ) ? $method['rates'][ $i ]['conditions'] : array();

							if ( ! Flags::is_pro_or_can_preview() ) {
								$idx_to_remove          = array();
								$num_of_rate_conditions = count( $rate_conditions );
								for ( $j = 0; $j < $num_of_rate_conditions; $j++ ) {
									if ( ! in_array( $rate_conditions[ $j ]['field'], Flags::CRITERIA_FREE_OPTIONS, true ) ) {
										$idx_to_remove[] = $j;
									}
								}

								for ( $k = count( $idx_to_remove ) - 1; $k >= 0; $k-- ) {
									unset( $method['rates'][ $i ]['conditions'][ $idx_to_remove[ $k ] ] );
								}

								$method['rates'][ $i ]['conditions'] = array_values( $method['rates'][ $i ]['conditions'] );
							}
						}
					}
				}
			}
		}

		return $active_rules;
	}

	/**
	 * Get globally available shipping rules.
	 *
	 * @return array
	 */
	public function get_global_shipping_rules() {
		return array_values(
			array_filter(
				$this->get_shipping_rules(),
				function ( $rule ) {
					return -1 === (int) ( $rule['generalSettings']['shippingZone'] ?? 0 )
						&& 'publish' === ( $rule['publishMode'] ?? 'draft' );
				}
			)
		);
	}

	/**
	 * Update or add a shipping rule.
	 *
	 * @param array $rule_data The rule data to save.
	 * @return bool True if new rule created, false if updated.
	 */
	public function update_shipping_rule( $rule_data ) {
		if ( empty( $rule_data['id'] ) ) {
			return new WP_Error( 'missing_id', 'Rule ID is required', array( 'status' => 400 ) );
		}

		$is_new         = false;
		$existing_rules = $this->get_shipping_rules();

		$rule_index = null;
		foreach ( $existing_rules as $index => $existing_rule ) {
			if ( isset( $existing_rule['id'] ) && $existing_rule['id'] === $rule_data['id'] ) {
				$rule_index = $index;
				break;
			}
		}

		$sanitized_rule = Sanitizer::sanitize_rule_data( $rule_data );

		$instance_id = $sanitized_rule['instanceId'] ?? null;

		$new_zone_id = $sanitized_rule['generalSettings']['shippingZone'] ?? null;
		$is_global   = -1 === (int) $new_zone_id;

		if ( $is_global ) {
			if ( ! empty( $instance_id ) ) {
				$curr_zone = \WC_Shipping_Zones::get_zone_by( 'instance_id', (int) $instance_id );

				if ( false !== $curr_zone ) {
					$curr_zone->delete_shipping_method( (int) $instance_id );
				}
			}

			$sanitized_rule['instanceId'] = null;
			$instance_id                  = null;
		} elseif ( empty( $instance_id ) ) {

			// If no instance ID, we must create a new method instance in the specified zone.
			if ( null === $new_zone_id ) {
				return new WP_Error( 'missing_zone', 'Shipping zone is required when instanceId is missing', array( 'status' => 400 ) );
			}

			$target_zone = \WC_Shipping_Zones::get_zone( (int) $new_zone_id );
			if ( false === $target_zone ) {
				return new WP_Error( 'invalid_zone', 'Shipping zone does not exist', array( 'status' => 400 ) );
			}

			// Create a new shipping method instance in the target zone.
			$created_instance_id          = $target_zone->add_shipping_method( 'wtrs_wc_method' );
			$sanitized_rule['instanceId'] = $created_instance_id;
			$instance_id                  = $created_instance_id;
		} else {
			// Validate existing instance and handle zone change if needed.
			$instance = \WC_Shipping_Zones::get_shipping_method( (int) $instance_id );

			if ( false === $instance ) {
				return new WP_Error( 'invalid_instance', 'Shipping method instance does not exist', array( 'status' => 400 ) );
			}

			$curr_zone = \WC_Shipping_Zones::get_zone_by( 'instance_id', (int) $instance_id );

			if ( false === $curr_zone ) {
				return new WP_Error( 'invalid_zone', 'Shipping zone does not exist', array( 'status' => 400 ) );
			}

			$curr_zone_id = (int) $curr_zone->get_id();

			// Zone changed.
			if ( null !== $new_zone_id && ( (int) $new_zone_id ) !== $curr_zone_id ) {
				$new_zone = \WC_Shipping_Zones::get_zone( (int) $new_zone_id );
				if ( false === $new_zone ) {
					return new WP_Error( 'invalid_new_zone', 'Target shipping zone does not exist', array( 'status' => 400 ) );
				}

				// Remove the method instance from current zone.
				$curr_zone->delete_shipping_method( (int) $instance_id );

				// Add a new method instance to the new zone using our method id.
				$instance_id = $new_zone->add_shipping_method( 'wtrs_wc_method' );

				// Update rule to reflect the new instance id.
				$sanitized_rule['instanceId'] = $instance_id;
			}
		}

		$enabled = 'publish' === ( $sanitized_rule['publishMode'] ?? 'draft' );

		// Persist the enabled/disabled state to both instance settings and zone methods table.
		if ( ! empty( $instance_id ) ) {
			global $wpdb;
			$wpdb->update( // phpcs:ignore
				$wpdb->prefix . 'woocommerce_shipping_zone_methods',
				array( 'is_enabled' => $enabled ? 1 : 0 ),
				array( 'instance_id' => (int) $instance_id ),
				array( '%d' ),
				array( '%d' )
			);
		}

		if ( null !== $rule_index ) {
			$existing_rules[ $rule_index ] = $sanitized_rule;
		} else {
			$existing_rules[] = $sanitized_rule;
			$is_new           = true;
		}

		update_option( self::OPTION_KEY, $existing_rules );

		do_action( 'wtrs_carrier_invalidate_cache' );

		return $is_new;
	}


	/**
	 * Update or add a shipping rule batch.
	 *
	 * @param array $rules The rules to save/update.
	 * @return bool
	 */
	public function update_shipping_rule_batch( $rules ) {
		foreach ( $rules as $rule ) {
			$this->update_shipping_rule( $rule );
		}
		return true;
	}

	/**
	 * Duplicate a shipping rule by ID.
	 *
	 * @param string $rule_id Rule ID to duplicate.
	 * @return array|WP_Error Newly duplicated rule data or error.
	 */
	public function duplicate_shipping_rule( $rule_id ) {
		$original = $this->get_shipping_rule_by_id( $rule_id );

		if ( empty( $original ) ) {
			return new WP_Error( 'rule_not_found', 'Shipping rule not found', array( 'status' => 404 ) );
		}

		$copy = $original;

		$new_id     = function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : strtolower( preg_replace( '/[^a-z0-9_-]/', '', uniqid( 'wtrs_', true ) ) );
		$copy['id'] = $new_id;

		$copy['generalSettings']['scenarioName']  .= ' - Copy';
		$copy['shippingMethods'][0]['methodName'] .= ' - Copy';
		$copy['createdAt']                         = time();

		// Remove instanceId so update_shipping_rule creates a new WooCommerce method instance.
		unset( $copy['instanceId'] );

		$save_result = $this->update_shipping_rule( $copy );

		if ( is_wp_error( $save_result ) ) {
			return $save_result;
		}

		return $this->get_shipping_rule_by_id( $new_id );
	}


	/**
	 * Get shipping rules count.
	 *
	 * @return array
	 */
	public function get_shipping_rules_count() {
		$counts = array(
			'all'     => 0,
			'publish' => 0,
			'draft'   => 0,
		);

		$rules = $this->get_shipping_rules();

		foreach ( $rules as $rule ) {
			$instance = \WC_Shipping_Zones::get_shipping_method( $rule['instanceId'] );

			if ( ! $instance ) {
				continue;
			}

			++$counts[ $instance->is_enabled() ? 'publish' : 'draft' ];
		}

		$counts['all'] = $counts['publish'] + $counts['draft'];

		return $counts;
	}

	/**
	 * Get a single shipping rule by ID.
	 *
	 * @param int $rule_id The rule ID.
	 * @return array|null Rule data or null if not found.
	 */
	public function get_shipping_rule_by_id( $rule_id ) {
		$rules = $this->get_shipping_rules();

		foreach ( $rules as $rule ) {
			if ( isset( $rule['id'] ) && $rule['id'] === $rule_id ) {
				return $rule;
			}
		}

		return null;
	}

	/**
	 * Get a single shipping rule by Woo Shipping Method Instance ID.
	 *
	 * @param int $instance_id Instance ID.
	 * @return array|null Rule data or null if not found.
	 */
	public function get_shipping_rule_by_instance_id( $instance_id ) {
		$rules = $this->get_shipping_rules();

		foreach ( $rules as $rule ) {
			if ( isset( $rule['instanceId'] ) && $rule['instanceId'] === $instance_id ) {
				return $rule;
			}
		}

		return null;
	}

	/**
	 * Get shipping rules by Woo Shipping Zone ID.
	 *
	 * @param int $zone_id Zone ID.
	 * @return array|null Rule data or null if not found.
	 */
	public function get_shipping_rules_by_zone_id( $zone_id ) {
		$rules = $this->get_shipping_rules();

		$matched_rules = array();

		foreach ( $rules as $rule ) {
			$_zone_id = (string) ( $rule['generalSettings']['shippingZone'] ?? null );
			if ( $_zone_id && $_zone_id === (string) $zone_id ) {
				$matched_rules[] = $rule;
			}
		}

		return $matched_rules;
	}

	/**
	 * Check if a rule exists by ID.
	 *
	 * @param int $rule_id The rule ID.
	 * @return bool True if rule exists, false otherwise.
	 */
	public function rule_exists( $rule_id ) {
		return $this->get_shipping_rule_by_id( $rule_id ) !== null;
	}

	/**
	 * Delete a shipping rule by ID.
	 *
	 * @param int    $rule_id The ID of the rule to delete.
	 * @param string $type The type of identifier (default is 'id').
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function delete_shipping_rule( $rule_id, $type = 'id' ) {
		if ( empty( $rule_id ) ) {
			return new \WP_Error( 'missing_id', 'Rule ID is required', array( 'status' => 400 ) );
		}

		$existing_rules = $this->get_shipping_rules();

		$rule_found = false;
		foreach ( $existing_rules as $index => $existing_rule ) {
			$id_to_match = (string) ( ( 'instance_id' === $type ) ? $existing_rule['instanceId'] : $existing_rule['id'] );
			if ( $id_to_match === $rule_id ) {
				unset( $existing_rules[ $index ] );

				// If deleting by Id, also remove the shipping method instance from WooCommerce.
				if ( 'id' === $type ) {
					$instance_id = $existing_rule['instanceId'];
					$instance    = \WC_Shipping_Zones::get_shipping_method( $instance_id );
					if ( $instance ) {
						$zone = \WC_Shipping_Zones::get_zone_by( 'instance_id', $instance_id );
						if ( $zone ) {
							$zone->delete_shipping_method( $instance_id );
						}
					}
				}

				$rule_found = true;
				break;
			}
		}

		if ( ! $rule_found ) {
			return new \WP_Error( 'rule_not_found', 'Shipping rule not found', array( 'status' => 404 ) );
		}

		$existing_rules = array_values( $existing_rules );

		update_option( self::OPTION_KEY, $existing_rules );

		do_action( 'wtrs_carrier_invalidate_cache' );

		return true;
	}

	/**
	 * Get order log (using wc_get_orders).
	 *
	 * Same output shape as get_order_log(), but avoids direct SQL.
	 *
	 * Note: we filter shipping line items by the stored shipping item meta
	 * `method_id` which is expected to start with `wtrs_`.
	 *
	 * @param int $page page num.
	 * @param int $per_page per page.
	 * @return array
	 */
	public function get_order_log( $page, $per_page ) {
		$page     = max( 1, absint( $page ) );
		$per_page = max( 1, absint( $per_page ) );

		$rules = $this->get_shipping_rules();

		// Prebuild a map for faster rule title lookup.
		$rule_title_map = array();
		foreach ( $rules as $rule ) {
			if ( isset( $rule['id'], $rule['generalSettings']['scenarioName'] ) ) {
				$rule_title_map[ (string) $rule['id'] ] = (string) $rule['generalSettings']['scenarioName'];
			}
		}

		$offset      = ( $per_page * ( $page - 1 ) );
		$target_end  = $offset + $per_page;
		$seen        = 0;
		$data        = array();
		$total_items = 0;

		$order_page     = 1;
		$order_per_page = 200;
		$total_orders   = 0;

		do {
			$query = wc_get_orders(
				array(
					'limit'    => $order_per_page,
					'page'     => $order_page,
					'paginate' => true,
					'return'   => 'objects',
					'type'     => 'shop_order',
					'status'   => array( 'completed', 'processing' ),
					'orderby'  => 'date',
					'order'    => 'DESC',
				)
			);

			$orders       = $query->orders ?? array();
			$total_orders = isset( $query->total ) ? absint( $query->total ) : 0;

			foreach ( $orders as $order ) {
				if ( ! $order instanceof \WC_Order ) {
					continue;
				}

				$shipping_items = $order->get_items( 'shipping' );
				if ( empty( $shipping_items ) ) {
					continue;
				}

				foreach ( $shipping_items as $shipping_item ) {
					if ( ! $shipping_item instanceof \WC_Order_Item_Shipping ) {
						continue;
					}

					$has_wtrs_shipping = (bool) $shipping_item->get_meta( 'method_type', true );
					if ( ! $has_wtrs_shipping ) {
						continue;
					}

					++$total_items;

					// Collect only the requested slice.
					if ( $seen < $offset ) {
						++$seen;
						continue;
					}

					if ( $seen >= $target_end ) {
						// We already collected the requested page, keep counting only.
						++$seen;
						continue;
					}

					$rule_id    = (string) $shipping_item->get_meta( 'rule_id', true );
					$rule_title = $rule_title_map[ $rule_id ] ?? '-';

					$date_created = $order->get_date_created();
					$date         = $date_created ? gmdate( 'Y-m-d', $date_created->getTimestamp() ) : '';

					// Prefer stored meta cost (matches SQL approach), fallback to item total.
					$cost = $shipping_item->get_meta( 'cost', true );
					$cost = '' !== $cost ? floatval( $cost ) : floatval( $shipping_item->get_total() );

					$data[] = array(
						'date'          => $date,
						'method'        => (string) $shipping_item->get_name(),
						'order_id'      => $order->get_id(),
						'rule'          => $rule_title,
						'shipping_cost' => round( $cost, 2 ),
					);

					++$seen;
				}
			}

			++$order_page;
		} while ( ( $order_page - 1 ) * $order_per_page < $total_orders );

		$total_pages = (int) ceil( $total_items / $per_page );

		return array(
			'totalPages'  => $total_pages,
			'totalItems'  => $total_items,
			'data'        => $data,
			'perPage'     => $per_page,
			'currentPage' => $page,
		);
	}

	/**
	 * Get avg shipping cost (using wc_get_orders).
	 *
	 * Same output shape as get_avg_shipping_cost(), but avoids direct SQL.
	 *
	 * Note: WooCommerce stores the shipping method identifier on shipping line items
	 * (order item meta), not reliably on order meta. We therefore fetch orders via
	 * wc_get_orders() and then filter shipping items in PHP. A best-effort meta_query
	 * is attempted first for backward compatibility, with a fallback when it yields
	 * no results.
	 *
	 * @param int $days days.
	 * @return array
	 */
	public function get_avg_shipping_cost( $days ) {
		$days = max( 0, absint( $days ) - 1 );

		$start_date = gmdate( 'Y-m-d', strtotime( "-{$days} day" ) );
		$end_date   = gmdate( 'Y-m-d' );
		$dates      = $this->get_dates_between( $start_date, $end_date );

		// Build maps so we can compute daily averages per type.
		$totals_by_day = array(
			'flat'     => array(),
			'flexible' => array(),
			'free'     => array(),
			'carrier'  => array(),
		);
		$counts_by_day = array(
			'flat'     => array(),
			'flexible' => array(),
			'free'     => array(),
			'carrier'  => array(),
		);

		foreach ( $dates as $date ) {
			$totals_by_day['flat'][ $date ]     = 0.0;
			$totals_by_day['flexible'][ $date ] = 0.0;
			$totals_by_day['free'][ $date ]     = 0.0;
			$totals_by_day['carrier'][ $date ]  = 0.0;

			$counts_by_day['flat'][ $date ]     = 0;
			$counts_by_day['flexible'][ $date ] = 0;
			$counts_by_day['free'][ $date ]     = 0;
			$counts_by_day['carrier'][ $date ]  = 0;
		}

		$base_args = array(
			'limit'        => -1,
			'paginate'     => false,
			'return'       => 'objects',
			'type'         => 'shop_order',
			'status'       => array( 'completed', 'processing' ),
			'date_created' => '>=' . $start_date,
			'orderby'      => 'date',
			'order'        => 'DESC',
		);

		$orders = wc_get_orders( $base_args );

		foreach ( $orders as $order ) {
			if ( ! $order instanceof \WC_Order ) {
				continue;
			}

			$date_created = $order->get_date_created();
			if ( ! $date_created ) {
				continue;
			}

			$order_day = gmdate( 'Y-m-d', $date_created->getTimestamp() );
			if ( ! isset( $totals_by_day['flat'][ $order_day ] ) ) {
				// Outside the expected date bucket window.
				continue;
			}

			$shipping_items = $order->get_items( 'shipping' );
			if ( empty( $shipping_items ) ) {
				continue;
			}

			foreach ( $shipping_items as $shipping_item ) {
				if ( ! $shipping_item instanceof \WC_Order_Item_Shipping ) {
					continue;
				}

				$method_type = (string) $shipping_item->get_meta( 'method_type', true );
				if ( '' === $method_type ) {
					continue;
				}

				$bucket_map = array(
					'flat_amount'     => 'flat',
					'flexible_amount' => 'flexible',
					'free_shipping'   => 'free',
					'carrier'         => 'carrier',
				);

				$type = $bucket_map[ $method_type ] ?? null;
				if ( null === $type ) {
					continue;
				}

				// Prefer the stored item meta (mirrors the SQL approach), fallback to item total.
				$cost = $shipping_item->get_meta( 'cost', true );
				$cost = '' !== $cost ? floatval( $cost ) : floatval( $shipping_item->get_total() );

				$totals_by_day[ $type ][ $order_day ] += $cost;
				$counts_by_day[ $type ][ $order_day ] += 1;
			}
		}

		$day_avg = array();
		$total   = 0;

		foreach ( $dates as $date ) {
			$t_flat     = $counts_by_day['flat'][ $date ] > 0
				? ( $totals_by_day['flat'][ $date ] / $counts_by_day['flat'][ $date ] )
				: 0;
			$t_flexible = $counts_by_day['flexible'][ $date ] > 0
				? ( $totals_by_day['flexible'][ $date ] / $counts_by_day['flexible'][ $date ] )
				: 0;
			$t_free     = $counts_by_day['free'][ $date ] > 0
				? ( $totals_by_day['free'][ $date ] / $counts_by_day['free'][ $date ] )
				: 0;
			$t_carrier  = $counts_by_day['carrier'][ $date ] > 0
				? ( $totals_by_day['carrier'][ $date ] / $counts_by_day['carrier'][ $date ] )
				: 0;

			$day_avg[] = array(
				'date'     => $date,
				'flat'     => round( $t_flat, 2 ),
				'flexible' => round( $t_flexible, 2 ),
				'free'     => round( $t_free, 2 ),
				'carrier'  => round( $t_carrier, 2 ),
			);

			$total += (
				$totals_by_day['flat'][ $date ] +
				$totals_by_day['flexible'][ $date ] +
				$totals_by_day['free'][ $date ] +
				$totals_by_day['carrier'][ $date ]
			);
		}

		return array(
			'avg'       => round( $total / count( $dates ), 2 ),
			'daily_avg' => $day_avg,
		);
	}

	/**
	 * Get rule usage in percentage (using wc_get_orders).
	 *
	 * Similar output shape as get_rule_usage_pct(), but avoids direct SQL.
	 *
	 * We count shipping line items whose stored shipping item meta `method_id`
	 * starts with the expected WowShipping prefixes (e.g. `wtrs_flat_`).
	 *
	 * @return array
	 */
	public function get_rule_usage_pct() {
		$counts = array(
			'flat'     => 0,
			'flexible' => 0,
			'free'     => 0,
			'carrier'  => 0,
		);

		$bucket_map = array(
			'flat_amount'     => 'flat',
			'flexible_amount' => 'flexible',
			'free_shipping'   => 'free',
			'carrier'         => 'carrier',
		);

		$page     = 1;
		$per_page = 200;

		do {
			$query = wc_get_orders(
				array(
					'limit'    => $per_page,
					'page'     => $page,
					'paginate' => true,
					'return'   => 'objects',
					'type'     => 'shop_order',
					'status'   => array( 'completed', 'processing' ),
					'orderby'  => 'date',
					'order'    => 'DESC',
				)
			);

			$orders = $query->orders ?? array();
			$total  = isset( $query->total ) ? absint( $query->total ) : 0;

			foreach ( $orders as $order ) {
				if ( ! $order instanceof \WC_Order ) {
					continue;
				}

				$shipping_items = $order->get_items( 'shipping' );
				if ( empty( $shipping_items ) ) {
					continue;
				}

				foreach ( $shipping_items as $shipping_item ) {
					if ( ! $shipping_item instanceof \WC_Order_Item_Shipping ) {
						continue;
					}

					$method_type = (string) $shipping_item->get_meta( 'method_type', true );
					if ( '' === $method_type ) {
						continue;
					}

					$type = $bucket_map[ $method_type ] ?? null;
					if ( null === $type ) {
						continue;
					}

					++$counts[ $type ];
				}
			}

			++$page;
		} while ( ( $page - 1 ) * $per_page < $total );

		$total_count = $counts['flat'] + $counts['flexible'] + $counts['free'] + $counts['carrier'];

		return array(
			'flat'     => $total_count > 0 ? round( ( $counts['flat'] / $total_count ) * 100, 2 ) : 0,
			'flexible' => $total_count > 0 ? round( ( $counts['flexible'] / $total_count ) * 100, 2 ) : 0,
			'free'     => $total_count > 0 ? round( ( $counts['free'] / $total_count ) * 100, 2 ) : 0,
			'carrier'  => $total_count > 0 ? round( ( $counts['carrier'] / $total_count ) * 100, 2 ) : 0,
		);
	}

	/**
	 * Get dates between two dates
	 *
	 * @param string $start_date start date.
	 * @param string $end_date end date.
	 * @param string $format date format.
	 * @return array
	 */
	private function get_dates_between( $start_date, $end_date, $format = 'Y-m-d' ) {
		$dates = array();

		$start = new DateTime( $start_date );
		$end   = new DateTime( $end_date );

		$step = ( $start <= $end ) ? 1 : -1;

		while ( ( $step > 0 && $start <= $end ) || ( $step < 0 && $start >= $end ) ) {
			$dates[] = $start->format( $format );
			$start->modify( ( $step > 0 ? '+1 day' : '-1 day' ) );
		}

		return $dates;
	}
}
