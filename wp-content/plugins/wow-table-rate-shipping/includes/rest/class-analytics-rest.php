<?php // phcps:ignore

namespace WTRS\Includes\Rest;

use WTRS\Includes\DB;
use WP_REST_Request;
use WP_REST_Response;
use WTRS\Includes\Utils\Flags;

defined( 'ABSPATH' ) || exit;

/**
 * Analytics REST class.
 */
class AnalyticsRest {

	/**
	 * DB instance
	 *
	 * @var \WTRS\Includes\DB
	 */
	private $db;

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->db = DB::get_instance();

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
			'/analytics/order-log',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_order_log' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'page'     => array(
						'required'            => false,
						'type'                => 'integer',
						'default'             => 1,
						'validation_callback' => function ( $value ) {
							return is_numeric( $value ) && intval( $value ) > 0;
						},
						'sanitize_callback'   => 'absint',
					),
					'per_page' => array(
						'required'            => false,
						'type'                => 'integer',
						'default'             => 10,
						'validation_callback' => function ( $value ) {
							return is_numeric( $value ) && intval( $value ) > 0;
						},
						'sanitize_callback'   => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/analytics/avg-shipping-cost',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_avg_shipping_cost' ),
				'permission_callback' => array( $this, 'check_permission' ),
				'args'                => array(
					'days' => array(
						'required'            => false,
						'type'                => 'integer',
						'default'             => 7,
						'validation_callback' => function ( $value ) {
							return is_numeric( $value ) && intval( $value ) >= 7;
						},
						'sanitize_callback'   => 'absint',
					),
				),
			)
		);

		register_rest_route(
			'wow-table-rate-shipping/v1',
			'/analytics/rule-usage',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'api_get_rule_usage_pct' ),
				'permission_callback' => array( $this, 'check_permission' ),
			)
		);
	}

	/**
	 * Get order log
	 *
	 * @param WP_REST_Request $request request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_get_order_log( WP_REST_Request $request ) {
		$page     = $request->get_param( 'page' );
		$per_page = $request->get_param( 'per_page' );

		$data = $this->db->get_order_log( $page, $per_page );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Get AVG shipping cost
	 *
	 * @param WP_REST_Request $request request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_get_avg_shipping_cost( WP_REST_Request $request ) {
		$days = $request->get_param( 'days' );

		$data = $this->db->get_avg_shipping_cost( $days );

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
			),
			200
		);
	}

	/**
	 * Get Rule usage pct
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function api_get_rule_usage_pct() {
		$data = $this->db->get_rule_usage_pct();

		return new \WP_REST_Response(
			array(
				'success' => true,
				'data'    => $data,
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
