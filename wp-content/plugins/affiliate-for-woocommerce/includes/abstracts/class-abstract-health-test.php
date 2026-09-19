<?php
/**
 * Abstract base class for health tests.
 *
 * @package     affiliate-for-woocommerce/includes/abstracts
 * @since       9.2.0
 * @version     1.0.0
 */

namespace AFWC\Health;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( Abstract_Health_Test::class ) ) {

	/**
	 * Class Abstract_Health_Test
	 */
	abstract class Abstract_Health_Test {

		/**
		 * Cached storage key.
		 *
		 * @var string
		 */
		protected $storage_key;

		/**
		 * How long a test result should survive in seconds. Defaults to 10 minutes.
		 *
		 * @var int
		 */
		protected $expiry = 600;

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->storage_key = $this->get_storage_key();
		}

		/**
		 * Unique slug for the individual test.
		 *
		 * @return string
		 */
		abstract public function id();

		/**
		 * Human readable name shown in UI.
		 *
		 * @return string
		 */
		abstract public function label();

		/**
		 * Human readable description for admin UI.
		 *
		 * @return string
		 */
		abstract public function description();

		/**
		 * Kick off the test, store initial state and return data.
		 *
		 * Must set status via set_status() before returning.
		 *
		 * @return array Structured response that will be returned to caller.
		 */
		abstract public function start();

		/**
		 * Optional: run whenever a relevant event occurs.
		 *
		 * Tests are free to bail early if the current request is unrelated. No
		 * output expected; implementations should simply update internal state
		 * as necessary.
		 *
		 * @return void
		 */
		abstract public function maybe_handle();

		/**
		 * Optional helper for tests that need WP hooks to fire later on.
		 *
		 * Child classes may override and add their own add_action calls. Base
		 * implementation is a no-op so callers can unconditionally invoke it.
		 *
		 * @return void
		 */
		public function register_hooks() {
			// noop by default.
		}

		/**
		 * Get storage key for the transient.
		 *
		 * @return string
		 */
		protected function get_storage_key() {
			return 'afwc_health_' . $this->id();
		}

		/**
		 * Persist a status record to transient storage.
		 *
		 * @param string $status   One of the standard status values.
		 * @param string $message  Optional human message.
		 * @param array  $details  Optional additional details.
		 *
		 * @return void
		 */
		protected function set_status( string $status = '', string $message = '', array $details = array() ) {
			set_transient(
				$this->storage_key,
				array(
					'status'  => $status,
					'message' => $message,
					'details' => $details,
				),
				$this->expiry
			);
		}

		/**
		 * Retrieve the current status record.
		 *
		 * @return mixed Current status record or expired state.
		 */
		public function get_status() {

			$status = get_transient( $this->storage_key );

			// Return a default expired state if transient is missing or expired.
			return ! empty( $status ) ? $status : array(
				'status'  => 'failed',
				'message' => _x( 'Test took too long.', 'Setup testing timeout message', 'affiliate-for-woocommerce' ),
			);
		}

		/**
		 * Delete the transient completely. Called when resetting or after test
		 * completion.
		 *
		 * @return void
		 */
		public function clear() {
			delete_transient( $this->storage_key );
		}
	}
}
