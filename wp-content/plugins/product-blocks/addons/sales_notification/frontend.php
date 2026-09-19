<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.4.0.0
 */
add_action( 'wp_loaded', 'wopb_sales_notification_init' );
function wopb_sales_notification_init() {
	if ( wopb_function()->get_setting( 'wopb_sales_notification' ) == 'true' ) {
		require_once WOPB_PATH . '/addons/sales_notification/SalesNotification.php';
		new \WOPB\SalesNotification();
	}
}
