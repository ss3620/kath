<?php

/**
 * Handles the Crons.
 * 
 * @since 8.7
 * */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('FGF_Cron_Handler')) {

	/**
	 * Class.
	 * */
	class FGF_Cron_Handler {

		/**
		 *  Class initialization.
		 * */
		public static function init() {

			// Maybe set the WP schedule event.
			add_action('init', array( __CLASS__, 'maybe_set_wp_schedule_event' ), 10);
			// Prepare the master log IDs for deletion WP Cron.
			add_action('fgf_master_log_deletion', array( __CLASS__, 'prepare_master_log_deletion' ));
			// Handle the master log deletion schedule action.
			add_action('fgf_clean_up_master_logs', array( __CLASS__, 'handle_master_log_deletion' ), 10, 1);
		}

		/**
		 * Maybe set the WP schedule event.
		 * */
		public static function maybe_set_wp_schedule_event() {
			$schedule_events = self::get_wp_schedule_events();
			// Return if the schedule events are not exists.
			if (!fgf_check_is_array($schedule_events)) {
				return;
			}

			// Set schedule event , if the event is not scheduled.
			foreach ($schedule_events as $key => $cron_events) {
				$interval = isset($cron_events['interval']) ? $cron_events['interval'] : '';

				if ($cron_events['set'] && !wp_next_scheduled($key)) {
					wp_schedule_event(time(), $interval, $key);
				} elseif (!$cron_events['set'] && wp_next_scheduled($key)) {
					wp_clear_scheduled_hook($key);
				}
			}
		}

		/**
		 * Get WP schedule event.
		 * 
		 * @return array.
		 * */
		public static function get_wp_schedule_events() {
			$master_log_deletion = '2' !== get_option('fgf_settings_master_log_deletion') ? true : false;
			$schedule_events = array(
				'fgf_master_log_deletion' => array(
					'interval' => 'twicedaily',
					'set' => $master_log_deletion,
				),
			);

			/**
			 * The hook is used to alter the WP schedule events.
			 * 
			 * @since 8.7
			 */
			return apply_filters('fgf_wp_schedule_events', $schedule_events);
		}

		/**
		 * Prepare the master log deletion.
		 * */
		public static function prepare_master_log_deletion() {
			// Update the WP cron current date. 
			update_option('fgf_master_log_deletion_last_updated_date', FGF_Date_Time::get_mysql_date_time_format('now', true));

			$master_log_ids = self::get_master_log_ids();
			$master_log_ids = array_filter(array_chunk($master_log_ids, 100));

			foreach ($master_log_ids as $count => $chunked_master_log_ids) {
				as_schedule_single_action(time() + $count, 'fgf_clean_up_master_logs', array( 'master_log_ids' => $chunked_master_log_ids ));
			}
		}

		/**
		 * Handles the master log deletion.
		 * */
		public static function handle_master_log_deletion( $master_log_ids ) {
			if (!fgf_check_is_array($master_log_ids)) {
				return;
			}

			foreach ($master_log_ids as $master_log_id) {
				fgf_delete_master_log($master_log_id);
			}
		}

		/**
		 * Get the master log IDs based on deletion settings.
		 * 
		 * @return array
		 */
		public static function get_master_log_ids() {
			$time_duration = self::get_master_log_deletion_time_duration();
			$date_object = FGF_Date_Time::get_date_time_object('now', true);
			$date_object->modify('-' . $time_duration['number'] . ' ' . $time_duration['unit']);

			$args = array(
				'post_type' => FGF_Register_Post_Types::MASTER_LOG_POSTTYPE,
				'post_status' => fgf_get_master_log_statuses(),
				'posts_per_page' => '-1',
				'fields' => 'ids',
				'date_query' => array(
					array(
						'column' => 'post_date_gmt',
						'before' => $date_object->format('Y-m-d H:i:s'),
					),
				),
			);

			return get_posts($args);
		}

		/**
		 * Get the master log deletion time duration.
		 * 
		 * @return array
		 */
		public static function get_master_log_deletion_time_duration() {
			$duration = get_option('fgf_settings_master_log_deletion_duration');

			return wp_parse_args($duration, array( 'number' => 1, 'unit' => 'years' ));
		}
	}

	FGF_Cron_Handler::init();
}
