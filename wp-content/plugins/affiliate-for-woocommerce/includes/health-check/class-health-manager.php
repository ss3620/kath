<?php
/**
 * Manager for health tests. Responsible for registering tests.
 *
 * @package     affiliate-for-woocommerce/includes/health-check/
 * @since       9.2.0
 * @version     1.0.0
 */

namespace AFWC\Health;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Registry;

if ( ! class_exists( Registry::class, false ) ) {
	include_once AFWC_PLUGIN_DIRPATH . '/includes/abstracts/class-registry.php';
}

if ( ! class_exists( Health_Manager::class ) && class_exists( Registry::class ) ) {

	/**
	 * Class Health_Manager
	 */
	class Health_Manager extends Registry {

		/**
		 * Singleton instance.
		 *
		 * @var Health_Manager|null
		 */
		private static $instance = null;

		/**
		 * Get singleton instance.
		 *
		 * @return Health_Manager
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			$this->include_files();
			$this->register_tests();

			// hook the REST endpoints.
			add_action( 'rest_api_init', array( $this, 'register_routes' ) );
		}

		/**
		 * Include health test files and dependencies.
		 *
		 * @return void
		 */
		public function include_files() {
			include_once AFWC_PLUGIN_DIRPATH . '/includes/abstracts/class-abstract-health-test.php';

			$test_files = glob( AFWC_PLUGIN_DIRPATH . '/includes/health-check/class-*.php' );

			if ( empty( $test_files ) || ! is_array( $test_files ) ) {
				return;
			}

			foreach ( $test_files as $file ) {
				if ( is_file( $file ) && 'class-health-manager.php' !== basename( $file ) ) {
					include_once $file;
				}
			}
		}

		/**
		 * Register all available health tests.
		 *
		 * @return void
		 */
		public function register_tests() {

			$tests = array(
				'referral_link' => Referral_Link_Test::class,
			);

			foreach ( $tests as $key => $class ) {
				if ( ! class_exists( $class ) ) {
					continue;
				}

				$test_instance = new $class();

				if ( ! $test_instance instanceof Abstract_Health_Test ) {
					continue;
				}

				// allow the test to hook when loaded.
				if ( is_callable( array( $test_instance, 'register_hooks' ) ) ) {
					$test_instance->register_hooks();
				}

				$this->register( $key, $test_instance );
			}
		}

		/**
		 * Register REST routes used by the health checker UI.
		 *
		 * GET  /afwc/v1/health/status?id={test}
		 * POST /afwc/v1/health/start?id={test}
		 *
		 * @return void
		 */
		public function register_routes() {
			register_rest_route(
				'afwc/v1',
				'/health/start',
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'start_test' ),
					'permission_callback' => 'afwc_current_user_can_manage_affiliate',
				)
			);

			register_rest_route(
				'afwc/v1',
				'/health/status',
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => 'afwc_current_user_can_manage_affiliate',
				)
			);
		}

		/**
		 * Method to get all registered tests.
		 *
		 * @return array Array of test definitions with id, name, and description.
		 */
		public function get_tests() {

			$tests = array();

			if ( empty( $this->instances ) || ! is_array( $this->instances ) ) {
				return $tests;
			}

			foreach ( $this->instances as $test ) {
				if ( ! $test instanceof Abstract_Health_Test ) {
					continue;
				}
				$tests[] = array(
					'id'          => $test->id(),
					'name'        => $test->label(),
					'description' => $test->description(),
				);
			}

			return $tests;
		}

		/**
		 * Handle route to start a test.
		 *
		 * @param \WP_REST_Request $request Request object.
		 * @return mixed
		 */
		public function start_test( \WP_REST_Request $request ) {

			$id = is_callable( array( $request, 'get_param' ) ) ? $request->get_param( 'test_id' ) : '';

			if ( empty( $id ) || ! $this->is_registered( $id ) ) {
				return new \WP_Error(
					'invalid_afwc_test',
					_x( 'Invalid health test id', 'health test error message', 'affiliate-for-woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$test = $this->get_registered( $id );
			return rest_ensure_response( $test instanceof Abstract_Health_Test ? $test->start() : null );
		}

		/**
		 * Handle route to fetch a test status.
		 *
		 * @param \WP_REST_Request $request Request object.
		 * @return mixed
		 */
		public function get_status( \WP_REST_Request $request ) {

			$id = is_callable( array( $request, 'get_param' ) ) ? $request->get_param( 'test_id' ) : '';

			if ( empty( $id ) || ! $this->is_registered( $id ) ) {
				return new \WP_Error(
					'invalid_afwc_test',
					_x( 'Invalid health test id', 'health test error message', 'affiliate-for-woocommerce' ),
					array( 'status' => 400 )
				);
			}

			$test = $this->get_registered( $id );
			return rest_ensure_response( $test instanceof Abstract_Health_Test ? $test->get_status() : null );
		}
	}
}

Health_Manager::get_instance();
