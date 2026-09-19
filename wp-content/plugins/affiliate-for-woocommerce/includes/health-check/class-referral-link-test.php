<?php
/**
 * Health check for referral link tracking.
 *
 * This test creates a throwaway affiliate and visits a referral URL to ensure
 * that the plugin is able to create a hit record and set the tracking cookie.
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

if ( ! class_exists( Referral_Link_Test::class ) ) {

	/**
	 * Class Referral_Link_Test
	 */
	class Referral_Link_Test extends Abstract_Health_Test {

		/**
		 * The login name for the deterministic test user.
		 *
		 * @var string
		 */
		const TEST_USER_LOGIN = 'afwc_test_user';

		/**
		 * Action Scheduler hook name for cleanup.
		 *
		 * @var string
		 */
		const CLEANUP_HOOK = 'afwc_health_test_referral_link_cleanup';

		/**
		 * Token used by the test URL.
		 *
		 * @var string
		 */
		protected $token = '';

		/**
		 * Get test identifier.
		 *
		 * @return string
		 */
		public function id() {
			return 'referral_link';
		}

		/**
		 * Get human-readable label.
		 *
		 * @return string
		 */
		public function label() {
			return _x( 'Referral tracking test', 'health test label', 'affiliate-for-woocommerce' );
		}

		/**
		 * Get human-readable description.
		 *
		 * @return string
		 */
		public function description() {
			return _x(
				'Checks whether referral tracking works when someone visits through a referral link. If it fails, it is usually due to caching removing the tracking parameters from the URL.',
				'health test description',
				'affiliate-for-woocommerce'
			);
		}

		/**
		 * Register hooks used by this test.
		 *
		 * @return void
		 */
		public function register_hooks() {
			// Listen for visits to the test URL at the end of the template_redirect action to allow plugins to perform any action before we check for it.
			add_action( 'template_redirect', array( $this, 'maybe_handle' ), 99 );

			// Listen for the cleanup action to clear test state and delete the temp user.
			add_action( self::CLEANUP_HOOK, array( $this, 'clear' ) );
		}

		/**
		 * Start the referral-link health test.
		 *
		 * @return array
		 */
		public function start() {
			// Clear any previous state, temp account and scheduled cleanup.
			$this->clear();
			$this->unschedule_cleanup();

			$token = afwc_generate_random_string( 6 );

			$user_id = $this->create_temp_affiliate();

			if ( empty( $user_id ) ) {
				$this->set_status(
					'failed',
					_x( 'Unable to create temporary affiliate account.', 'health test error message', 'affiliate-for-woocommerce' ),
					array()
				);
				return array(
					'status'  => 'failed',
					'message' => _x( 'Unable to create temporary affiliate account.', 'health test error message', 'affiliate-for-woocommerce' ),
				);
			}

			$url           = add_query_arg( array( 'afwc_test_token' => $token ), home_url( '/' ) );
			$affiliate_url = afwc_get_affiliate_url( $url, '', $user_id );
			$message       = _x( 'Copy the link below and open it in an incognito browser window to verify referral tracking.', 'health test waiting message', 'affiliate-for-woocommerce' );

			$this->set_status(
				'waiting',
				$message,
				array(
					'token'        => $token,
					'affiliate_id' => $user_id,
					'action_url'   => $affiliate_url,
				)
			);

			// Schedule cleanup after 5 minutes if no action is taken.
			$this->schedule_cleanup();

			return array(
				'status'  => 'waiting',
				'message' => $message,
				'details' => array(
					'action_url' => $affiliate_url,
				),
			);
		}

		/**
		 * Handle the test URL visit on `template_redirect`.
		 *
		 * @return void
		 */
		public function maybe_handle() {
			if ( empty( $_GET['afwc_test_token'] ) ) { // phpcs:ignore
				return;
			}

			$token  = sanitize_text_field( wp_unslash( $_GET['afwc_test_token'] ) ); // phpcs:ignore
			$status = $this->get_status();

			if ( empty( $status ) || ! is_array( $status ) || empty( $token ) ) {
				return;
			}

			if ( empty( $status['status'] ) || 'waiting' !== $status['status'] ) {
				return;
			}

			$details = ! empty( $status['details'] ) && is_array( $status['details'] ) ? $status['details'] : array();

			if ( empty( $details['token'] ) || $token !== $details['token'] ) {
				$this->set_status(
					'failed',
					_x( 'Token mismatch detected. Please restart the test.', 'health test error message', 'affiliate-for-woocommerce' ),
					$details
				);
				return;
			}

			// Detect if an admin is logged in (opened the link in the same browser session).
			if ( current_user_can( 'manage_options' ) ) {
				$this->set_status(
					'waiting',
					_x( 'You opened this link while logged in. To complete the test, copy the link and open it in a new private (incognito) window where you are not logged in.', 'health test waiting message', 'affiliate-for-woocommerce' ),
					$details
				);
				return;
			}

			$expected_affiliate = ! empty( $details['affiliate_id'] ) ? intval( $details['affiliate_id'] ) : 0;
			$action_url         = ! empty( $details['action_url'] ) ? esc_url_raw( $details['action_url'] ) : '';

			if ( empty( $expected_affiliate ) ) {
				$this->set_status( 'failed', _x( 'Missing affiliate data. Please restart the test.', 'health test error message', 'affiliate-for-woocommerce' ), $details );
				$this->unschedule_cleanup();
				$this->cleanup_temp_affiliate();
				return;
			}

			global $wpdb;

			$hit_exists = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}afwc_hits
					WHERE affiliate_id = %d AND url = %s",
					$expected_affiliate,
					$action_url
				)
			);

			$cookie_affiliate = afwc_get_affiliate_from_cookie();

			if ( ! empty( $hit_exists ) && intval( $cookie_affiliate ) === $expected_affiliate ) {
				$this->set_status(
					'passed',
					_x( 'Referral cookie detected. Tracking is working correctly.', 'health test success message', 'affiliate-for-woocommerce' )
				);
			} else {
				$details = array(
					'action_label'     => _x( 'Review your caching settings →', 'health test caching documentation link label', 'affiliate-for-woocommerce' ),
					'action_url'       => AFWC_DOC_DOMAIN . 'how-does-affiliate-for-woocommerce-plugin-work-with-caching',
					'has_hit'          => boolval( $hit_exists ),
					'cookie_affiliate' => intval( $cookie_affiliate ),
				);

				$this->set_status(
					'failed',
					_x( 'No referral cookie detected. This is often caused by caching removing tracking parameters from the URL.', 'health test error message', 'affiliate-for-woocommerce' ),
					$details
				);
			}

			// Cleanup: unschedule AS action and delete the test user.
			$this->unschedule_cleanup();
			$this->cleanup_temp_affiliate();
		}

		/**
		 * Create or reuse the deterministic test user.
		 *
		 * @return int User ID or 0 on failure.
		 */
		protected function create_temp_affiliate() {
			$user = get_user_by( 'login', self::TEST_USER_LOGIN );

			if ( ! empty( $user ) && ! is_wp_error( $user ) ) {
				update_user_meta( $user->ID, 'afwc_is_affiliate', 'yes' );
				return intval( $user->ID );
			}

			$user_id = wp_insert_user(
				array(
					'user_login' => self::TEST_USER_LOGIN,
					'user_email' => self::TEST_USER_LOGIN . '@example.com',
					'user_pass'  => wp_generate_password(),
				)
			);

			if ( is_wp_error( $user_id ) || empty( $user_id ) ) {
				return 0;
			}

			update_user_meta( $user_id, 'afwc_is_affiliate', 'yes' );

			return intval( $user_id );
		}

		/**
		 * Delete the deterministic test user if it exists.
		 *
		 * @return void
		 */
		protected function cleanup_temp_affiliate() {
			$user = get_user_by( 'login', self::TEST_USER_LOGIN );

			if ( ! empty( $user ) && ! is_wp_error( $user ) && ! empty( $user->ID ) ) {
				if ( ! function_exists( 'wp_delete_user' ) ) {
					// include the user functions if not already loaded, which is the case when this runs via REST API/Action Scheduler in a non-admin context.
					require_once ABSPATH . 'wp-admin/includes/user.php';
				}
				wp_delete_user( $user->ID );
			}
		}

		/**
		 * Schedule a one-time cleanup via Action Scheduler.
		 *
		 * @return void
		 */
		protected function schedule_cleanup() {
			if ( function_exists( 'as_schedule_single_action' ) ) {
				as_schedule_single_action( time() + $this->expiry, self::CLEANUP_HOOK, array( $this->id() ) );
			}
		}

		/**
		 * Unschedule any pending cleanup actions.
		 *
		 * @return void
		 */
		protected function unschedule_cleanup() {
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::CLEANUP_HOOK, array( $this->id() ) );
			}
		}

		/**
		 * Remove stored state and delete the test user.
		 *
		 * @return void
		 */
		public function clear() {
			parent::clear();
			$this->cleanup_temp_affiliate();
		}
	}
}
