<?php
/**
 * Some common functions for Affiliate For WooCommerce to manage WordPress compatibility
 *
 * @package     affiliate-for-woocommerce/includes/
 * @since       8.37.0
 * @version     1.2.0
 */

if ( ! function_exists( 'afwc_is_wp_doing_ajax' ) ) {
	/**
	 * Determines whether the current request is a WordPress Ajax request.
	 *
	 * @return bool True if it's a WordPress Ajax request, false otherwise.
	 */
	function afwc_is_wp_doing_ajax() {
		return function_exists( 'wp_doing_ajax' ) ? wp_doing_ajax() : defined( 'DOING_AJAX' ) && DOING_AJAX;
	}
}

if ( ! function_exists( 'afwc_get_default_user_search_args' ) ) {
	/**
	 * Method to get default user search arguments for get_users arguments.
	 *
	 * @param string $term Search term used to find users/affiliates.
	 *
	 * @return array Default search arguments.
	 */
	function afwc_get_default_user_search_args( $term = '' ) {
		return array(
			'search'         => '*' . $term . '*',
			'search_columns' => array( 'ID', 'user_nicename', 'user_login', 'user_email', 'display_name' ),
		);
	}
}

if ( ! function_exists( 'afwc_get_current_screen_data' ) ) {
	/**
	 * Method to retrieve specific data from the current WordPress admin screen.
	 *
	 * It fetches a property from the current `WP_Screen` object, if available.
	 * It can be used to get details like the screen ID, base, or other screen-related data.
	 * If the requested data is not available it returns an empty string.
	 *
	 * @param string $data Optional. The property name to retrieve from the current screen.
	 *                     Defaults to 'id'. Use 'data' to return the full `WP_Screen` object.
	 *
	 * @return string|WP_Screen The requested screen property value, the `WP_Screen` object,
	 *                          or an empty string if not available.
	 */
	function afwc_get_current_screen_data( $data = 'id' ) {
		$screen = is_callable( 'get_current_screen' ) ? get_current_screen() : null;
		if ( empty( $screen ) || ! $screen instanceof WP_Screen ) {
			return '';
		}

		if ( ! empty( $data ) && ! empty( $screen->$data ) ) {
			return $screen->$data;
		} elseif ( 'data' === $data ) {
			return $screen;
		} else {
			return '';
		}
	}
}
