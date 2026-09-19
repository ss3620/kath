<?php //phpcs:ignore
/**
 * Options Action.
 *
 * @package WTRS\Options
 */

namespace WTRS\Includes\Rest;

use WTRS\Includes\DB;
use WTRS\Includes\Utils\ExportHelper;
use WTRS\Includes\Utils\ImportHelper;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WTRS\Includes\Utils\Flags;

defined( 'ABSPATH' ) || exit;

/**
 * Rest class.
 */
class Rest {

	/**
	 * Database object
	 *
	 * @var DB
	 */
	private $db;

	/**
	 * Setup class.
	 */
	public function __construct() {
		$this->db = DB::get_instance();
		add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );
		new WooShippingSettings();
		new ConditionsRest();
		new AnalyticsRest();
		new CarrierRest();
	}

	/**
	 * Register REST API endpoints.
	 */
	public function register_api_endpoints() {
		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/shipping-rules',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_shipping_rules' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/shipping-rules/(?P<id>[a-z0-9_-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_shipping_rule' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Duplicate a shipping rule by ID.
		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/shipping-rules/(?P<id>[a-zA-Z0-9_-]+)/duplicate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'api_duplicate_shipping_rule' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return sanitize_text_field( $param ) === $param;
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/shipping-rules',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'api_save_shipping_rule' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/shipping-rules-export',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_export_shipping_rules' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'status' => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => 'all',
						'enum'              => array( 'publish', 'draft', 'all' ),
						'sanitize_callback' => 'sanitize_text_field',
					),
					'search' => array(
						'required'          => false,
						'type'              => 'string',
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/shipping-rules-count',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_shipping_rule_count' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Endpoint for deleting a single shipping rule by ID.
		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/shipping-rules/(?P<id>[a-z0-9_-]+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'api_delete_shipping_rule' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return sanitize_text_field( $param ) === $param;
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Endpoint for importing shipping rules from CSV data.
		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/shipping-rules/import/rules',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'api_start_import_job' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'data' => array(
						'required' => true,
						'type'     => 'array',
					),
				),
			)
		);

		// Route for checking import job status.
		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/shipping-rules/import/rules/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_job_status' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		// Endpoint for bulk operations on shipping rules.
		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/shipping-rules/bulk',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'api_bulk_shipping_rules' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'action'   => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return in_array( $param, array( 'delete', 'activate', 'deactivate' ), true );
						},
					),
					'rule_ids' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_array( $param ) && ! empty( $param );
						},
					),
				),
			)
		);

		// Settings.
		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/settings',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_settings' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/settings',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'api_update_settings' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'data' => array(
						'required'            => true,
						'type'                => 'mixed',
						'validation_callback' => function ( $param ) {
							if ( ! is_array( $param ) ) {
								return false;
							}

							if ( count( $param ) === 0 ) {
								return false;
							}

							$fields = array( 'taxDefault' );

							foreach ( $fields as $field ) {
								if ( ! isset( $param[ $field ] ) ) {
									return false;
								}
							}

							return true;
						},
					),
				),
			)
		);

		// WooCommerce native shipping zones: list all zones.
		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/woo-shipping-zones',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_wc_shipping_zones' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/woo-shipping-zones',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'api_create_wc_shipping_zone' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/woo-shipping-zones/(?P<id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'api_update_wc_shipping_zone' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Get settings
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_get_settings() {
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => DB::get_instance()->get_settings(),
			)
		);
	}

	/**
	 * Update settings
	 *
	 * @param WP_REST_Request $request request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_update_settings( WP_REST_Request $request ) {
		$this->db->update_settings( $request->get_param( 'data' ) );
		return rest_ensure_response(
			array(
				'success' => true,
			)
		);
	}

	/**
	 * Check permission for API access.
	 *
	 * @return bool
	 */
	public function check_permission() {
		return Flags::is_user_admin();
	}

	/**
	 * API endpoint to get all shipping rules.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function api_get_shipping_rule( $request ) {
		$id = $request->get_param( 'id' );

		$rule = $this->db->get_shipping_rule_by_id( $id );

		return new \WP_REST_Response(
			array(
				'success' => ! empty( $rule ),
				'data'    => $rule,
			),
			200
		);
	}

	/**
	 * API endpoint to get all shipping rules.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function api_get_shipping_rules() {
		$rules = $this->db->get_shipping_rules();

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $rules,
				'message' => 'Shipping rules retrieved successfully',
			),
			200
		);
	}

	/**
	 * API endpoint to get export shipping rules data.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function api_export_shipping_rules( $request ) {
		$status = $request->get_param( 'status' );
		$search = $request->get_param( 'search' );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => ExportHelper::export_rules( $status, $search ),
			),
		);
	}

	/**
	 * API endpoint to save a shipping rule.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function api_save_shipping_rule( $request ) {
		$rule_data = $request->get_json_params();

		if ( empty( $rule_data ) ) {
			return new \WP_Error(
				'no_data',
				'No rule data provided',
				array( 'status' => 400 )
			);
		}

		$is_new = $this->db->update_shipping_rule( $rule_data );

		$saved_rule = $this->db->get_shipping_rule_by_id( $rule_data['id'] );

		$success = true;
		$msg     = __( 'Shipping rule saved successfully', 'wow-table-rate-shipping' );

		if ( empty( $saved_rule ) ) {
			$success = false;
			$msg     = __( 'Failed to save shipping rule', 'wow-table-rate-shipping' );
		}

		return new \WP_REST_Response(
			array(
				'success' => $success,
				'message' => $msg,
				'data'    => $saved_rule,
				'is_new'  => $is_new,
			),
			200
		);
	}

	/**
	 * API endpoint to delete a shipping rule.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function api_delete_shipping_rule( $request ) {
		$rule_id = $request->get_param( 'id' );

		$result = $this->db->delete_shipping_rule( $rule_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Shipping rule deleted successfully',
			),
			200
		);
	}

	/**
	 * API endpoint to get shipping rule count.
	 *
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function api_get_shipping_rule_count() {
		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $this->db->get_shipping_rules_count(),
			),
			200
		);
	}

	/**
	 * API endpoint to duplicate a shipping rule by ID.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function api_duplicate_shipping_rule( $request ) {
		$rule_id = $request->get_param( 'id' );

		$duplicate = $this->db->duplicate_shipping_rule( $rule_id );

		if ( is_wp_error( $duplicate ) ) {
			return $duplicate;
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Shipping rule duplicated successfully',
				'data'    => $duplicate,
				'is_new'  => true,
			),
			200
		);
	}

	/**
	 * API endpoint for bulk operations on shipping rules.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function api_bulk_shipping_rules( $request ) {
		$action   = $request->get_param( 'action' );
		$rule_ids = $request->get_param( 'rule_ids' );

		$result = $this->bulk_shipping_rules( $rule_ids, $action );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$action_messages = array(
			'delete'     => 'deleted',
			'activate'   => 'activated',
			'deactivate' => 'deactivated',
		);

		$action_message = $action_messages[ $action ] ?? $action;
		$message        = sprintf(
			'%d rules %s successfully',
			$result['success_count'],
			$action_message
		);

		if ( $result['error_count'] > 0 ) {
			$message .= sprintf( ', %d failed', $result['error_count'] );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'message' => $message,
				'data'    => $result,
			),
			200
		);
	}

	/**
	 * Perform bulk operations on shipping rules.
	 *
	 * @param array  $rule_ids Array of rule IDs to perform operations on.
	 * @param string $action The action to perform ('delete', 'activate', 'deactivate').
	 * @return array|WP_Error Result array with success/error counts or WP_Error on failure.
	 */
	public function bulk_shipping_rules( $rule_ids, $action ) {
		if ( empty( $rule_ids ) || ! is_array( $rule_ids ) ) {
			return new \WP_Error( 'invalid_rule_ids', 'Invalid rule IDs provided', array( 'status' => 400 ) );
		}

		if ( ! in_array( $action, array( 'delete', 'activate', 'deactivate' ), true ) ) {
			return new \WP_Error( 'invalid_action', 'Invalid action provided', array( 'status' => 400 ) );
		}

		$success_count = 0;
		$error_count   = 0;
		$errors        = array();

		foreach ( $rule_ids as $rule_id ) {

			if ( empty( $rule_id ) ) {
				++$error_count;
				$errors[] = "Invalid rule ID: {$rule_id}";
				continue;
			}

			switch ( $action ) {
				case 'delete':
					$result = $this->db->delete_shipping_rule( $rule_id );
					if ( is_wp_error( $result ) ) {
						++$error_count;
						$errors[] = "Failed to delete rule {$rule_id}: " . $result->get_error_message();
					} else {
						++$success_count;
					}
					break;

				case 'activate':
				case 'deactivate':
					// Get the rule first.
					$rule = $this->db->get_shipping_rule_by_id( $rule_id );
					if ( ! $rule ) {
						++$error_count;
						$errors[] = "Rule {$rule_id} not found";
						continue 2;
					}

					// Update the publish mode.
					$new_publish_mode    = ( 'activate' === $action ) ? 'publish' : 'draft';
					$rule['publishMode'] = $new_publish_mode;

					$this->db->update_shipping_rule( $rule );
					++$success_count;
					break;
			}
		}

		return array(
			'success_count' => $success_count,
			'error_count'   => $error_count,
			'errors'        => $errors,
			'total'         => count( $rule_ids ),
		);
	}


	/**
	 * Start import job endpoint
	 *
	 * @param WP_REST_Request $request request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_start_import_job( $request ) {
		$data = $request->get_param( 'data' );

		$res = ImportHelper::start_rule_import_job( $data );

		return rest_ensure_response(
			array(
				'success' => $res,
			)
		);
	}

	/**
	 * Get rule import job status endpoint
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_get_job_status() {
		$status = ImportHelper::get_rule_import_job_status();
		return rest_ensure_response(
			array(
				'success' => ! empty( $status ),
				'data'    => $status,
			)
		);
	}

	/**
	 * Serialize a WC_Shipping_Zone (or array zone item) into a plain array.
	 *
	 * @param mixed $zone Zone object or associative array from WC_Shipping_Zones::get_zones().
	 * @return array
	 */
	private function serialize_wc_zone( $zone ) {
		// If zone is already an array (from get_zones), normalize keys and return.
		if ( is_array( $zone ) ) {
			return array(
				'id'        => isset( $zone['id'] ) ? absint( $zone['id'] ) : ( isset( $zone['zone_id'] ) ? absint( $zone['zone_id'] ) : 0 ),
				'name'      => isset( $zone['zone_name'] ) ? $zone['zone_name'] : ( $zone['name'] ?? '' ),
				'order'     => isset( $zone['zone_order'] ) ? intval( $zone['zone_order'] ) : ( isset( $zone['order'] ) ? intval( $zone['order'] ) : null ),
				'locations' => isset( $zone['zone_locations'] ) ? $zone['zone_locations'] : array(),
			);
		}

		// Otherwise assume WC_Shipping_Zone instance.
		if ( is_object( $zone ) ) {
			$zone_id   = method_exists( $zone, 'get_id' ) ? $zone->get_id() : 0;
			$zone_name = method_exists( $zone, 'get_zone_name' ) ? $zone->get_zone_name() : '';
			$zone_ord  = method_exists( $zone, 'get_zone_order' ) ? $zone->get_zone_order() : null;
			$locations = method_exists( $zone, 'get_zone_locations' ) ? $zone->get_zone_locations() : array();

			return array(
				'id'        => absint( $zone_id ),
				'name'      => $zone_name,
				'order'     => is_null( $zone_ord ) ? null : intval( $zone_ord ),
				'locations' => is_array( $locations ) ? $locations : array(),
			);
		}

		return array();
	}

	/**
	 * Normalize WooCommerce shipping zone locations.
	 *
	 * @param array $locations Raw locations payload.
	 * @return array<int,array<string,string>>
	 */
	private function normalize_zone_locations( $locations ) {
		$normalized = array();
		if ( ! is_array( $locations ) ) {
			return $normalized;
		}

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
				$type = (string) ( $loc->type ?? ( $loc->location_type ?? '' ) );
				$code = (string) ( $loc->code ?? ( $loc->location_code ?? '' ) );
			}

			$type = strtolower( trim( $type ) );
			$code = strtoupper( trim( $code ) );

			if ( '' === $type || '' === $code ) {
				continue;
			}

			if ( in_array( strtolower( $code ), array( 'null', 'undefined' ), true ) ) {
				continue;
			}

			if ( ! in_array( $type, $allowed_types, true ) ) {
				continue;
			}

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

		return array_values(
			array_map(
				static function ( $item ) {
					return (object) array(
						'type' => (string) $item['type'],
						'code' => (string) $item['code'],
					);
				},
				array_unique( $normalized, SORT_REGULAR )
			)
		);
	}

	/**
	 * Get all WooCommerce shipping zones.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_get_wc_shipping_zones() {
		if ( ! class_exists( '\\WC_Shipping_Zones' ) ) {
			return new \WP_Error( 'woocommerce_missing', 'WooCommerce is not active', array( 'status' => 500 ) );
		}

		$zones_raw = \WC_Shipping_Zones::get_zones();
		$zones     = array();
		foreach ( $zones_raw as $zone_item ) {
			$zones[] = $this->serialize_wc_zone( $zone_item );
		}

		// Also include the default zone (ID 0) if available.
		$default_zone = \WC_Shipping_Zones::get_zone( 0 );
		if ( $default_zone ) {
			$zones[] = $this->serialize_wc_zone( $default_zone );
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $zones,
				'message' => 'WooCommerce shipping zones retrieved successfully',
			),
			200
		);
	}

	/**
	 * Create a WooCommerce shipping zone.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_create_wc_shipping_zone( WP_REST_Request $request ) {
		if ( ! class_exists( '\\WC_Shipping_Zone' ) ) {
			return new WP_Error( 'woocommerce_missing', 'WooCommerce is not active', array( 'status' => 500 ) );
		}

		$zone_name = sanitize_text_field( (string) $request->get_param( 'name' ) );
		$locations = $this->normalize_zone_locations( $request->get_param( 'locations' ) );

		if ( '' === $zone_name ) {
			return new WP_Error(
				'missing_zone_name',
				__( 'Shipping zone name is required.', 'wow-table-rate-shipping' ),
				array( 'status' => 400 )
			);
		}

		$zone = new \WC_Shipping_Zone();
		$zone->set_zone_name( $zone_name );
		$zone->set_zone_locations( $locations );
		$zone->save();

		$zone_id = (int) $zone->get_id();
		if ( $zone_id <= 0 ) {
			return new WP_Error(
				'zone_create_failed',
				__( 'Failed to create WooCommerce shipping zone.', 'wow-table-rate-shipping' ),
				array( 'status' => 500 )
			);
		}

		$created_zone = new \WC_Shipping_Zone( $zone_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $this->serialize_wc_zone( $created_zone ),
				'message' => __( 'WooCommerce shipping zone created successfully.', 'wow-table-rate-shipping' ),
			),
			201
		);
	}

	/**
	 * Update a WooCommerce shipping zone.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_update_wc_shipping_zone( WP_REST_Request $request ) {
		if ( ! class_exists( '\\WC_Shipping_Zone' ) ) {
			return new WP_Error( 'woocommerce_missing', 'WooCommerce is not active', array( 'status' => 500 ) );
		}

		$zone_id   = absint( $request->get_param( 'id' ) );
		$zone_name = sanitize_text_field( (string) $request->get_param( 'name' ) );
		$locations = $this->normalize_zone_locations( $request->get_param( 'locations' ) );

		if ( $zone_id <= 0 ) {
			return new WP_Error(
				'invalid_zone_id',
				__( 'A valid shipping zone is required.', 'wow-table-rate-shipping' ),
				array( 'status' => 400 )
			);
		}

		if ( '' === $zone_name ) {
			return new WP_Error(
				'missing_zone_name',
				__( 'Shipping zone name is required.', 'wow-table-rate-shipping' ),
				array( 'status' => 400 )
			);
		}

		$zone = new \WC_Shipping_Zone( $zone_id );
		if ( $zone_id !== (int) $zone->get_id() ) {
			return new WP_Error(
				'zone_not_found',
				__( 'Shipping zone not found.', 'wow-table-rate-shipping' ),
				array( 'status' => 404 )
			);
		}

		$zone->set_zone_name( $zone_name );
		$zone->set_zone_locations( $locations );
		$zone->save();

		$updated_zone = new \WC_Shipping_Zone( $zone_id );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $this->serialize_wc_zone( $updated_zone ),
				'message' => __( 'WooCommerce shipping zone updated successfully.', 'wow-table-rate-shipping' ),
			),
			200
		);
	}
}
