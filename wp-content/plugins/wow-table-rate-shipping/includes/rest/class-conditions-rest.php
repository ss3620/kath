<?php // phpcs:ignore

namespace WTRS\Includes\Rest;

use WTRS\Includes\Conditions;
use WP_Error;
use WTRS\Includes\Utils\Flags;

defined( 'ABSPATH' ) || exit;

/**
 * Rest class.
 */
class ConditionsRest {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/condition-data/(?P<type>[a-zA-Z_-]+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_condition_data' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'type'     => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							$allowed_types = array(
								'products',
								'categories',
								'tags',
								'users',
								'user_roles',
								'color',
								'size',
								'country',
								'state',
								'city',
								'shipping-class',
								'shipping_class',
								'attribute',
								'coupons',
								'cart_coupons',
								'weekdays',
							);
							if ( strpos( $param, 'pa_' ) === 0 ) {
								return true;
							}
							return in_array( $param, $allowed_types, true );
						},
					),
					'search'   => array(
						'required'          => false,
						'type'              => 'string',
						'validate_callback' => function ( $value ) {
							return is_string( $value );
						},
						'sanitize_callback' => 'sanitize_text_field',
					),
					'per_page' => array(
						'required'          => false,
						'type'              => 'integer',
						'default'           => 50,
						'validate_callback' => function ( $value ) {
							return is_numeric( $value ) && intval( $value ) > 0;
						},
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/shipping-classes',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_shipping_classes' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/product-attributes',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_product_attributes' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * API endpoint to get condition data based on type.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function api_get_condition_data( $request ) {
		$type     = $request->get_param( 'type' );
		$search   = $request->get_param( 'search' );
		$per_page = $request->get_param( 'per_page' ) ? (int) $request->get_param( 'per_page' ) : 50;

		$data = Conditions::get_condition_data_by_type( $type, $search, $per_page );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
				'type'    => $type,
			),
			200
		);
	}

	/**
	 * API endpoint to get all shipping classes.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function api_get_shipping_classes( $request ) {
		$shipping_classes = Conditions::get_shipping_classes_data();

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $shipping_classes,
			),
			200
		);
	}

	/**
	 * API endpoint to get all product attributes.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function api_get_product_attributes( $request ) {
		$attributes = Conditions::get_product_attributes_data();

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $attributes,
			),
			200
		);
	}

	/**
	 * Check permission for API access.
	 *
	 * @return boolean
	 */
	public function check_permission() {
		return Flags::is_user_admin();
	}
}
