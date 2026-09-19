<?php
/**
 * Captures the logged-in WordPress admin as a RevenueHunt operator contact.
 *
 * @link       https://revenuehunt.com/
 * @since      2.4.0
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Sends a low-frequency signed operator capture request to RevenueHunt.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */
class Product_Recommendation_Quiz_For_Ecommerce_Admin_Operator_Capture {

	/**
	 * Capture at most once per WordPress user per day.
	 *
	 * @since 2.4.0
	 * @return void
	 */
	public function maybe_capture() {
		$shop_hashid = get_option( PRQ_OPTION_SHOP_HASHID );
		$api_key     = get_option( PRQ_OPTION_API_KEY );

		if ( empty( $shop_hashid ) || empty( $api_key ) || ! is_user_logged_in() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$current_user = wp_get_current_user();
		if ( ! $current_user->exists() ) {
			return;
		}

		$transient_key = 'prq_operator_capture_' . md5( $shop_hashid . ':' . $current_user->ID );
		if ( get_transient( $transient_key ) ) {
			return;
		}

		// Set before the HTTP request so repeated admin page loads do not become noisy if the API is temporarily unavailable.
		set_transient( $transient_key, 1, DAY_IN_SECONDS );

		$timestamp = time();
		$operator  = array(
			'email'        => $current_user->user_email,
			'display_name' => $current_user->display_name,
			'first_name'   => get_user_meta( $current_user->ID, 'first_name', true ),
			'last_name'    => get_user_meta( $current_user->ID, 'last_name', true ),
			'roles'        => array_values( (array) $current_user->roles ),
			'locale'       => get_user_locale( $current_user ),
		);
		$data      = implode(
			'&',
			array(
				'purpose=operator_capture',
				'hashid=' . $shop_hashid,
				'domain=' . PRQ_STORE_URL,
				'plugin_version=' . PRQ_PLUGIN_VERSION,
				'timestamp=' . (string) $timestamp,
				'operator_email=' . $operator['email'],
			)
		);
		$hmac      = base64_encode( hash_hmac( 'sha256', $data, $api_key, true ) );

		$body = array(
			'shop_hashid'    => $shop_hashid,
			'domain'         => PRQ_STORE_URL,
			'plugin_version' => PRQ_PLUGIN_VERSION,
			'timestamp'      => $timestamp,
			'hmac'           => $hmac,
			'operator'       => $operator,
		);

		wp_remote_post(
			PRQ_API_URL . '/api/v1/woocommerce/operator_capture',
			array(
				'timeout' => 2,
				'headers' => array(
					'Content-Type' => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
			)
		);
	}
}
