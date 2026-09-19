<?php // phcps:ignore

namespace WTRS\Includes\Rest;

use WP_REST_Request;
use WP_REST_Response;
use WTRS\Includes\Utils\Flags;

defined( 'ABSPATH' ) || exit;

/**
 * Carrier REST class.
 */
class CarrierRest {

	/**
	 * Carrier Service
	 *
	 * @var \WTRS\Carriers\CarrierService
	 */
	private $carrier_service;

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( class_exists( '\WTRS\Carriers\CarrierService' ) ) {
			$this->carrier_service = \WTRS\Carriers\CarrierService::class;
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}
	}

	/**
	 * Register routes
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/carriers/values',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_carrier_values' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'carrier' => array(
						'required'            => true,
						'type'                => 'string',
						'validation_callback' => 'is_string',
						'sanitize_callback'   => 'sanitize_text_field',
					),
					'type'    => array(
						'required'            => true,
						'type'                => 'string',
						'validation_callback' => 'is_string',
						'sanitize_callback'   => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/carriers/status',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_carrier_status' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'carrier' => array(
						'required'            => true,
						'type'                => 'string',
						'validation_callback' => 'is_string',
						'sanitize_callback'   => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/carriers/validate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'api_validate_carrier' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'carrier' => array(
						'required'            => true,
						'type'                => 'string',
						'validation_callback' => 'is_string',
						'sanitize_callback'   => 'sanitize_text_field',
					),
					'data'    => array(
						'required'            => true,
						'type'                => 'mixed',
						'validation_callback' => 'is_array',
					),
				),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/carriers/secrets',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_secrets' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'carrier' => array(
						'required'            => true,
						'type'                => 'string',
						'validation_callback' => 'is_string',
						'sanitize_callback'   => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Get order log
	 *
	 * @param WP_REST_Request $request request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_get_carrier_values( WP_REST_Request $request ) {
		$carrier = $request->get_param( 'carrier' );
		$type    = $request->get_param( 'type' );

		$data = $this->carrier_service::get_values( $carrier, $type );

		return new \WP_REST_Response(
			array(
				'success' => ! empty( $data ),
				'data'    => ! empty( $data ) ? $data : array(),
			),
			200
		);
	}

	/**
	 * Get carrier status
	 *
	 * @param WP_REST_Request $request request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_get_carrier_status( WP_REST_Request $request ) {
		$carrier = $request->get_param( 'carrier' );

		$data = $this->carrier_service::check_status( $carrier );

		return new \WP_REST_Response(
			array(
				'success' => $data,
			),
			200
		);
	}

	/**
	 * Get carrier status
	 *
	 * @param WP_REST_Request $request request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_validate_carrier( WP_REST_Request $request ) {
		$carrier = $request->get_param( 'carrier' );
		$data    = $request->get_param( 'data' );

		$success = $this->carrier_service::validate( $carrier, $data );

		return new \WP_REST_Response(
			array(
				'success' => $success,
			),
			200
		);
	}

	/**
	 * Get carrier status
	 *
	 * @param WP_REST_Request $request request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_get_secrets( WP_REST_Request $request ) {
		$carrier = $request->get_param( 'carrier' );

		$data = $this->carrier_service::get_secrets( $carrier );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/** Check permission for API access .
	 *
	 * @return boolean
	 */
	public function check_permission() {
		return Flags::is_user_admin();
	}
}
