<?php

/**
 * Notifications Instances Class
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'FGF_Notification_Instances' ) ) {

	/**
	 * Class FGF_Notification_Instances
	 */
	class FGF_Notification_Instances {
		/*
		 * Notifications
		 */

		private static $notifications = array();

		/*
		 * Get Notifications
		 */

		public static function get_notifications() {

			if ( ! self::$notifications ) {
				self::load_notifications();
			}

			return self::$notifications;
		}

		/*
		 * Load all Notifications
		 */

		public static function load_notifications() {

			if ( ! class_exists( 'FGF_Notifications' ) ) {
				include FGF_PLUGIN_PATH . '/inc/abstracts/class-fgf-notifications.php';
			}

			$default_notification_classes = array(
				'customer-manual-gift' => 'FGF_Customer_Manual_Gift_Notification',
			);

			foreach ( $default_notification_classes as $file_name => $notification_class ) {

				// include file
				include 'class-' . $file_name . '.php';

				// add notification
				self::add_notification( new $notification_class() );
			}
		}

		/**
		 * Add a Module
		 */
		public static function add_notification( $notification ) {

			self::$notifications[ $notification->get_id() ] = $notification;

			return new self();
		}

		/**
		 * Get notification by id
		 */
		public static function get_notification_by_id( $notification_id ) {
			$notifications = self::get_notifications();

			return isset( $notifications[ $notification_id ] ) ? $notifications[ $notification_id ] : false;
		}
	}

}
