<?php // phpcs:ignore

namespace WTRS\Includes\Utils;

use WTRS\Includes\DB;

defined( 'ABSPATH' ) || exit;

/**
 * Import Helper Class
 */
class ImportHelper {

	private const JOB_STATUS_OPTION_KEY = 'wtrs_rule_import_job_status';

	/**
	 * Headers of the CSV data.
	 *
	 * @var array
	 */
	private static $headers = array(
		'id',
		'generalSettings',
		'shippingMethods',
		'tax',
		'visibleToUser',
		'handlingFee',
		'publishMode',
		'version',
		'wooZone',
	);

	/**
	 * Headers of the CSV data.
	 *
	 * @var array
	 */
	private static $json_columns = array(
		'generalSettings',
		'shippingMethods',
		'tax',
		'handlingFee',
		'wooZone',
	);

	/**
	 * Init hooks
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wtrs_process_rule_import_job', array( self::class, 'process_rule_import_job' ) );
	}

	/**
	 * Validates a single row of the CSV data.
	 *
	 * @param mixed $row Row data.
	 * @return bool
	 */
	private static function validate_rule_row( $row ) {

		if ( WTRS_RULE_VER !== ( strval( $row['version'] ?? '-1' ) ) ) {
			return false;
		}

		// Allow additional optional columns (e.g., 'wooZone') and ensure required headers exist.
		if ( empty( $row ) || ! is_array( $row ) ) {
			return false;
		}

		foreach ( self::$headers as $header ) {
			// "Rest of the world" exports can have an empty/missing wooZone column.
			if ( 'wooZone' === $header ) {
				continue;
			}

			if ( ! isset( $row[ $header ] ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Undocumented function
	 *
	 * @param array $data rule data.
	 * @return bool
	 */
	public static function start_rule_import_job( $data ) {
		$job_status = array(
			'status'        => 'pending',
			'total_rows'    => count( $data ),
			'current_row'   => 0,
			'imported_rows' => 0,
			'failed_rows'   => 0,
			'start_time'    => current_time( 'mysql' ),
			'end_time'      => null,
			'data'          => $data,
			'errors'        => array(),
		);

		self::update_job_status( $job_status );

		return wp_schedule_single_event( time(), 'wtrs_process_rule_import_job' );
	}

	/**
	 * Get Import rule job status
	 *
	 * @return array|false
	 */
	public static function get_rule_import_job_status() {
		$job_status = self::get_job_status();

		if ( empty( $job_status ) ) {
			return false;
		}

		if ( 'completed' === $job_status['status'] ) {
			self::update_job_status( array( 'status' => 'none' ) );
		}

		if ( isset( $job_status['data'] ) ) {
			unset( $job_status['data'] );
		}

		return $job_status;
	}

	/**
	 * Process import job directly (called from status endpoint)
	 */
	public static function process_rule_import_job() {
		$job_status = self::get_job_status();

		if ( ! $job_status || 'pending' !== $job_status['status'] ) {
			return;
		}

		$job_status['status'] = 'processing';
		self::update_job_status( $job_status );

		$errors   = array();
		$num_rows = count( $job_status['data'] );

		// Step 1: Parse and group rows by wooZone id.
		$grouped_rows = array();
		for ( $i = 0; $i < $num_rows; $i++ ) {
			$row = $job_status['data'][ $i ];
			if ( ! self::validate_rule_row( $row ) ) {
				$errors[] = array(
					'row'     => $i + 1,
					'message' => __( 'Invalid Columns', 'wow-table-rate-shipping' ),
				);
				continue;
			}

			$data = array(
				'id'            => function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : md5( uniqid( wp_rand(), true ) ),
				'visibleToUser' => $row['visibleToUser'],
				'publishMode'   => $row['publishMode'],
				'version'       => $row['version'],
			);

			// JSON columns.
			foreach ( self::$json_columns as $key ) {
				// Special case: "Rest of the world" export leaves wooZone empty.
				if ( 'wooZone' === $key && ( ! isset( $row[ $key ] ) || '' === trim( (string) $row[ $key ] ) ) ) {
					$data[ $key ] = array();
					continue;
				}

				$d = self::json_decode( $row[ $key ] );
				if ( false === $d ) {
					$errors[] = array(
						'row'     => $i + 1,
						// translators: column name.
						'message' => sprintf( __( 'Invalid JSON data in column \'%s\'', 'wow-table-rate-shipping' ), $key ),
					);
					continue 2;
				}
				$data[ $key ] = $d;
			}

			if ( ! isset( $data['generalSettings']['shippingZone'] ) ) {
				continue;
			}

			$imported_zone_id = (int) $data['generalSettings']['shippingZone'];
			if ( -1 === $imported_zone_id ) {
				$group_key = 'id:-1';

				if ( ! isset( $grouped_rows[ $group_key ] ) ) {
					$grouped_rows[ $group_key ] = array(
						'wooZone' => array(
							'id' => -1,
						),
						'items'   => array(),
					);
				}

				$grouped_rows[ $group_key ]['items'][] = array(
					'row_number' => $i + 1,
					'data'       => $data,
				);

				if ( 0 === $i % 10 ) {
					$job_status['current_row'] = $i + 1;
					self::update_job_status( $job_status );
				}

				continue;
			}

			$woo_zone_data = isset( $data['wooZone'] ) && is_array( $data['wooZone'] ) ? $data['wooZone'] : array();
			$woo_zone_id   = isset( $woo_zone_data['id'] ) ? (int) $woo_zone_data['id'] : 0;
			$is_global     = self::is_global_zone_payload( $woo_zone_data );
			$is_rest_world = self::is_rest_of_world_zone_payload( $woo_zone_data );

			// Group primarily by the exported WooCommerce zone id.
			// If missing, fall back to a deterministic key using name+locations.
			if ( $is_global ) {
				$group_key = 'id:-1';
			} elseif ( $is_rest_world ) {
				$group_key = 'id:0';
			} elseif ( $woo_zone_id > 0 ) {
				$group_key = 'id:' . $woo_zone_id;
			} else {
				$group_key = 'key:' . self::build_zone_match_key(
					(string) ( $woo_zone_data['name'] ?? '' ),
					(array) ( $woo_zone_data['locations'] ?? array() )
				);
			}

			if ( ! isset( $grouped_rows[ $group_key ] ) ) {
				$grouped_rows[ $group_key ] = array(
					'wooZone' => $woo_zone_data,
					'items'   => array(),
				);
			}

			$grouped_rows[ $group_key ]['items'][] = array(
				'row_number' => $i + 1,
				'data'       => $data,
			);

			if ( 0 === $i % 10 ) {
				$job_status['current_row'] = $i + 1;
				self::update_job_status( $job_status );
			}
		}

		// Step 2: Resolve or create zones once per group, then assign the resolved zone id to each rule.
		$imported_rules = array();
		$zone_cache     = self::prime_existing_zone_cache();
		foreach ( $grouped_rows as $group ) {
			$zone_error       = '';
			$resolved_zone_id = self::resolve_or_create_zone( $group['wooZone'], $zone_cache, $zone_error );
			// Note: zone_id 0 ("Rest of the world") is a valid zone.
			if ( false === $resolved_zone_id ) {
				foreach ( $group['items'] as $item ) {
					$errors[] = array(
						'row'     => (int) $item['row_number'],
						'message' => '' !== $zone_error
							? $zone_error
							// translators: row number.
							: sprintf( __( 'Failed to resolve WooCommerce zone for row %d', 'wow-table-rate-shipping' ), (int) $item['row_number'] ),
					);
				}
				continue;
			}

			foreach ( $group['items'] as $item ) {
				$data                                    = $item['data'];
				$data['generalSettings']['shippingZone'] = (int) $resolved_zone_id;
				$data['instanceId']                      = null; // Will be set when adding to zone.
				$imported_rules[]                        = $data;
			}
		}

		$status = true;
		if ( count( $imported_rules ) > 0 ) {
			$status = DB::get_instance()->update_shipping_rule_batch( $imported_rules );
		}

		$job_status['imported_rows'] = $status ? count( $imported_rules ) : 0;
		$job_status['failed_rows']   = count( $errors ) + ( $status ? 0 : count( $imported_rules ) );
		$job_status['status']        = 'completed';
		$job_status['errors']        = $errors;
		$job_status['end_time']      = current_time( 'mysql' );

		self::update_job_status( $job_status );
	}

	/**
	 * Normalize WooCommerce zone locations to a stable comparable form.
	 *
	 * @param array $locations Raw locations array.
	 * @return array
	 */
	private static function normalize_zone_locations( $locations ) {
		$normalized = array();
		if ( ! is_array( $locations ) ) {
			return $normalized;
		}

		// If a single location object/array is provided, wrap it into a list.
		if (
			isset( $locations['type'] ) ||
			isset( $locations['code'] ) ||
			isset( $locations['location_type'] ) ||
			isset( $locations['location_code'] )
		) {
			$locations = array( $locations );
		}

		$allowed_types = array( 'country', 'state', 'continent', 'postcode' );

		foreach ( $locations as $loc ) {
			$type = '';
			$code = '';

			if ( is_array( $loc ) ) {
				$type = (string) ( $loc['type'] ?? ( $loc['location_type'] ?? '' ) );
				$code = (string) ( $loc['code'] ?? ( $loc['location_code'] ?? '' ) );
			} elseif ( is_object( $loc ) ) {
				// Support WC_Shipping_Zone location objects and other variants.
				$type = (string) ( $loc->type ?? ( $loc->location_type ?? '' ) );
				$code = (string) ( $loc->code ?? ( $loc->location_code ?? '' ) );
			}

			$type = strtolower( trim( $type ) );
			$code = trim( $code );

			// Avoid creating invalid DB rows: skip empty/placeholder values or invalid types.
			if ( '' === $type || '' === $code ) {
				continue;
			}
			if ( in_array( strtolower( $code ), array( 'null', 'undefined' ), true ) ) {
				continue;
			}
			if ( ! in_array( $type, $allowed_types, true ) ) {
				continue;
			}

			// Woo stores location codes in uppercase.
			$code = strtoupper( $code );

			$normalized[] = array(
				'type' => $type,
				'code' => $code,
			);
		}

		usort(
			$normalized,
			static function ( $a, $b ) {
				$ak = ( $a['type'] ?? '' ) . ':' . ( $a['code'] ?? '' );
				$bk = ( $b['type'] ?? '' ) . ':' . ( $b['code'] ?? '' );
				return strcmp( $ak, $bk );
			}
		);

		return array_map(
			function ( $item ) {
				return (object) $item;
			},
			array_values( $normalized )
		);
	}

	/**
	 * Build a deterministic cache key for matching zones by name + locations.
	 *
	 * @param string $name Zone name.
	 * @param array  $locations Zone locations.
	 * @return string
	 */
	private static function build_zone_match_key( $name, $locations ) {
		$name      = strtolower( trim( (string) $name ) );
		$locations = self::normalize_zone_locations( $locations );
		return $name . '|' . md5( wp_json_encode( $locations ) );
	}

	/**
	 * Determine whether an imported wooZone payload represents "Rest of the world".
	 *
	 * Export leaves wooZone empty when shippingZone is 0, so we treat empty payload as zone_id 0.
	 *
	 * @param array $woo_zone_data Exported wooZone payload.
	 * @return bool
	 */
	private static function is_rest_of_world_zone_payload( $woo_zone_data ) {
		if ( empty( $woo_zone_data ) || ! is_array( $woo_zone_data ) ) {
			return true;
		}

		$zone_id   = isset( $woo_zone_data['id'] ) ? (int) $woo_zone_data['id'] : 0;
		$name      = isset( $woo_zone_data['name'] ) ? trim( (string) $woo_zone_data['name'] ) : '';
		$locations = isset( $woo_zone_data['locations'] ) && is_array( $woo_zone_data['locations'] ) ? $woo_zone_data['locations'] : array();
		$has_locs  = ! empty( self::normalize_zone_locations( $locations ) );
		$has_name  = '' !== $name;
		$is_zone_0 = 0 === $zone_id;

		return $is_zone_0 && ! $has_name && ! $has_locs;
	}

	/**
	 * Determine whether an imported wooZone payload represents the synthetic global method.
	 *
	 * @param array $woo_zone_data Exported wooZone payload.
	 * @return bool
	 */
	private static function is_global_zone_payload( $woo_zone_data ) {
		if ( empty( $woo_zone_data ) || ! is_array( $woo_zone_data ) ) {
			return false;
		}

		return -1 === (int) ( $woo_zone_data['id'] ?? 0 );
	}

	/**
	 * Prime a cache of existing WooCommerce zones keyed by name+locations.
	 *
	 * @return array<string,int>
	 */
	private static function prime_existing_zone_cache() {
		$cache = array();
		if ( ! class_exists( '\\WC_Shipping_Zones' ) ) {
			return $cache;
		}

		$zones = (array) \WC_Shipping_Zones::get_zones();
		foreach ( $zones as $zone_data ) {
			$zone_id = isset( $zone_data['zone_id'] ) ? (int) $zone_data['zone_id'] : 0;
			if ( $zone_id <= 0 ) {
				continue;
			}
			$zone      = new \WC_Shipping_Zone( $zone_id );
			$name      = (string) $zone->get_zone_name();
			$locations = array();
			foreach ( (array) $zone->get_zone_locations() as $loc ) {
				$locations[] = array(
					'type' => is_object( $loc ) ? (string) ( $loc->type ?? '' ) : ( is_array( $loc ) ? (string) ( $loc['type'] ?? '' ) : '' ),
					'code' => is_object( $loc ) ? (string) ( $loc->code ?? '' ) : ( is_array( $loc ) ? (string) ( $loc['code'] ?? '' ) : '' ),
				);
			}

			$key = self::build_zone_match_key( $name, $locations );
			if ( '' !== $key && ! isset( $cache[ $key ] ) ) {
				$cache[ $key ] = (int) $zone->get_id();
			}
		}

		return $cache;
	}

	/**
	 * Resolve an existing WooCommerce zone by name+locations, or create one if not found.
	 *
	 * @param array             $woo_zone_data Exported wooZone payload.
	 * @param array<string,int> &$zone_cache Zone cache (name+locations => zone id).
	 * @param string            $error_message Optional error message when resolution fails.
	 * @return int|false Zone id on success (0 allowed for Rest of the world), false on failure.
	 */
	private static function resolve_or_create_zone( $woo_zone_data, &$zone_cache, &$error_message = '' ) {
		$error_message = '';
		$woo_zone_data = is_array( $woo_zone_data ) ? $woo_zone_data : array();

		if ( self::is_global_zone_payload( $woo_zone_data ) ) {
			return -1;
		}

		// "Rest of the world" zone (zone_id 0): do not create a new zone.
		if ( self::is_rest_of_world_zone_payload( $woo_zone_data ) ) {
			return 0;
		}

		$zone_name      = isset( $woo_zone_data['name'] ) && '' !== (string) $woo_zone_data['name'] ? (string) $woo_zone_data['name'] : __( 'Imported Zone', 'wow-table-rate-shipping' );
		$locations      = isset( $woo_zone_data['locations'] ) && is_array( $woo_zone_data['locations'] ) ? $woo_zone_data['locations'] : array();
		$norm_locations = self::normalize_zone_locations( $locations );

		// If locations were provided but none are valid, fail this zone to avoid NULL DB inserts.
		if ( ! empty( $locations ) && empty( $norm_locations ) ) {
			$error_message = __( 'Invalid WooCommerce zone locations in import data.', 'wow-table-rate-shipping' );
			return false;
		}

		$key = self::build_zone_match_key( $zone_name, $locations );

		if ( isset( $zone_cache[ $key ] ) && ! empty( $zone_cache[ $key ] ) ) {
			return (int) $zone_cache[ $key ];
		}

		$zone = new \WC_Shipping_Zone();
		$zone->set_zone_name( $zone_name );
		if ( isset( $woo_zone_data['order'] ) ) {
			$zone->set_zone_order( (int) $woo_zone_data['order'] );
		}
		$zone->set_zone_locations( $norm_locations );
		$zone->save();
		$resolved_zone_id = (int) $zone->get_id();
		if ( $resolved_zone_id <= 0 ) {
			$error_message = __( 'Failed to create WooCommerce shipping zone.', 'wow-table-rate-shipping' );
			return false;
		}
		if ( $resolved_zone_id > 0 ) {
			$zone_cache[ $key ] = $resolved_zone_id;
		}

		return $resolved_zone_id;
	}

	/**
	 * Get Import job status
	 *
	 * @return array
	 */
	private static function get_job_status() {
		return get_option( self::JOB_STATUS_OPTION_KEY, array( 'status' => 'none' ) );
	}

	/**
	 * Update Import job status
	 *
	 * @param array $data job status data.
	 * @return void
	 */
	private static function update_job_status( $data ) {
		update_option( self::JOB_STATUS_OPTION_KEY, $data );
	}

	/**
	 * JSON Decode
	 *
	 * @param string $json json string.
	 * @return array|false
	 */
	private static function json_decode( $json ) {
		$json = str_replace( '&&&', ',', $json );
		$data = json_decode( $json, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return false;
		}
		return $data;
	}
}
