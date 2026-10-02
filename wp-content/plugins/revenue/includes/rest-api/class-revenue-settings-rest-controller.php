<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * REST API for settings.
 *
 * @package Revenue
 */

namespace Revenue;

defined( 'ABSPATH' ) || exit;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;

/**
 * REST API for settings.
 */
class Revenue_Settings_REST_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $namespace = 'revenue/v1';

	/**
	 * Route name
	 *
	 * @since 1.0.0
	 * @var   string
	 */
	protected $base = 'settings';



	/**
	 * Register all routes related with stores
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_settings' ),
				'permission_callback' => array( $this, 'get_settings_permission_check' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->base,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'update_setting' ),
				'permission_callback' => array( $this, 'get_update_setting_permission_check' ),
			)
		);
	}

	/**
	 * Get setting permission check.
	 *
	 * @return bool
	 */
	public function get_settings_permission_check() {
		return $this->get_update_setting_permission_check();
	}

	/**
	 * Get update setting permission check.
	 *
	 * @return bool
	 */
	public function get_update_setting_permission_check() {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		return $has_permission;
	}


	/**
	 * Get all settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_settings( $request ) {
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		return rest_ensure_response( revenue()->get_setting() );
	}

	/**
	 * Verify the dashboard nonce sent with the request.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return true|WP_Error
	 */
	protected function verify_nonce( $request ) {
		$nonce = '';
		if ( isset( $request['security'] ) ) {
			$nonce = sanitize_key( $request['security'] );
		}
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			return new WP_Error( 'revenue_rest_nonce_error', __( 'Nonce Verification Failed!', 'revenue' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Update a setting.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_setting( $request ) {
		$nonce_check = $this->verify_nonce( $request );
		if ( is_wp_error( $nonce_check ) ) {
			return $nonce_check;
		}

		$key   = $request->get_param( 'key' );
		$value = $request->get_param( 'value' );

		if ( empty( $key ) ) {
			return new WP_Error( 'invalid_key', 'Invalid setting key', array( 'status' => 400 ) );
		}

		// Update the setting using update_option.
		$updated = revenue()->set_setting( $key, $value );

		if ( $updated ) {
			return rest_ensure_response(
				array(
					'success' => true,
					'message' => 'Setting updated successfully',
				)
			);
		} else {
			return new WP_Error( 'update_failed', 'Failed to update setting', array( 'status' => 500 ) );
		}
	}
}
