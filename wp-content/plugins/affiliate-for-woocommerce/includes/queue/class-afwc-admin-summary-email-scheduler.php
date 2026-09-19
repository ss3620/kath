<?php
/**
 * Main class for 'Affiliate Manager - Summary Email' Scheduler
 *
 * @package     affiliate-for-woocommerce/includes/queue/
 * @since       8.25.0
 * @version     1.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Admin_Summary_Email_Scheduler' ) ) {

	/**
	 * Class for admin summary email scheduler.
	 */
	class AFWC_Admin_Summary_Email_Scheduler {

		/**
		 * Variable to hold the action hook name for scheduled task
		 *
		 * @var string
		 */
		public $schedule_action = 'afwc_send_admin_summary_email';

		/**
		 * Variable to hold the group name for scheduled task
		 *
		 * @var string
		 */
		public $group = 'affiliate-for-woocommerce';

		/**
		 * Variable to hold the email name for scheduled task
		 *
		 * @var string
		 */
		public $email = 'afwc_admin_summary_email_reports';

		/**
		 * Variable to hold instance of this class.
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get the single instance of this class
		 *
		 * @return AFWC_Admin_Summary_Email_Scheduler
		 */
		public static function get_instance() {
			// Check if the instance already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			// Try to schedule the email task on init.
			add_action( 'init', array( $this, 'attempt_schedule_emailer' ) );

			// Listen for option changes to enable/disable the email scheduler.
			add_action( 'updated_option', array( $this, 'trigger_email_scheduler' ), 99, 3 );

			// Hook that executes when our scheduled action runs.
			add_action( $this->schedule_action, array( $this, 'send_emails' ) );
		}

		/**
		 * Check the admin summary email status
		 *
		 * @return bool True if email is enabled, false otherwise
		 */
		public function is_enabled() {
			return class_exists( 'AFWC_Emails' )
				&& is_callable( array( 'AFWC_Emails', 'is_afwc_mailer_enabled' ) )
				&& true === AFWC_Emails::is_afwc_mailer_enabled( 'afwc_email_admin_summary_reports' );
		}

		/**
		 * Trigger the scheduler when email settings are updated
		 *
		 * @param  string $option         The option key.
		 * @param  array  $old_values     The old option values.
		 * @param  array  $new_values     The new option values.
		 *
		 * @return void
		 */
		public function trigger_email_scheduler( $option = '', $old_values = array(), $new_values = array() ) {
			if ( empty( $option ) || ( "woocommerce_{$this->email}_settings" !== $option && 'woocommerce_custom_orders_table_enabled' !== $option ) ) {
				return;
			}

			// Schedule if enabled, unschedule if disabled.
			if ( is_array( $new_values ) && ! empty( $new_values['enabled'] ) && 'yes' === $new_values['enabled'] ) {
				$this->schedule_emailer();
			} elseif ( ! empty( $new_values ) && 'yes' === $new_values ) {
				$this->schedule_emailer();
			} else {
				$this->unschedule_emailer();
			}
		}

		/**
		 * Method to to attempt schedule emailer incase the next As could not scheduled.
		 * It will help to schedule the action if the setting is enabled but the site is logged after 3-4 month or
		 * incase deleted all the action from the action scheduler.
		 */
		public function attempt_schedule_emailer() {
			if ( $this->is_enabled() ) {
				$this->schedule_emailer();
			}
		}

		/**
		 * Schedules the email task if not already scheduled
		 */
		public function schedule_emailer() {
			// Exit if Action Scheduler isn't available or action is already scheduled.
			if ( ! function_exists( 'as_has_scheduled_action' ) ||
				! function_exists( 'as_schedule_single_action' ) ||
				as_has_scheduled_action( $this->schedule_action )
				) {
				return;
			}

			/**
			 * Filter to get the timestamp for scheduling the email action.
			 *
			 * @param string The timestamp to schedule the action.
			 *
			 * @since 8.25.0
			 */
			$as_timestamp = apply_filters( $this->email . '_as_timestamp', '' );

			if ( ! empty( $as_timestamp ) ) {
				// Schedule the action.
				as_schedule_single_action( $as_timestamp, $this->schedule_action, array(), $this->group );
			}
		}

		/**
		 * Unschedule the action
		 */
		public function unschedule_emailer() {
			if ( ! function_exists( 'as_unschedule_action' ) ) {
				return;
			}

			as_unschedule_action( $this->schedule_action, array(), $this->group );
		}

		/**
		 * Sends the admin summary email
		 */
		public function send_emails() {
			if ( ! $this->is_enabled() ) {
				return;
			}

			/**
			 * Action triggered to send admin summary email.
			 *
			 * @since 8.25.0
			 */
			do_action( 'afwc_email_admin_summary_reports' );
		}
	}
}

// Auto initialize the class.
if ( class_exists( 'AFWC_Admin_Summary_Email_Scheduler' ) && is_callable( array( 'AFWC_Admin_Summary_Email_Scheduler', 'get_instance' ) ) ) {
	AFWC_Admin_Summary_Email_Scheduler::get_instance();
}
