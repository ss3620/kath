<?php
/**
 * Connection layer: builds the URLs that connect the store to the backend.
 *
 * One implementation of the plugin's connection seam — the V1 WooCommerce
 * OAuth handshake against admin.revenuehunt.com, including the exact HMAC
 * payload and RFC3986 query strings the monolith depends on. Isolating it here
 * keeps "how the plugin connects to its backend" swappable without changing the
 * V1 wire format.
 *
 * @link       https://revenuehunt.com/
 * @since      2.3.9
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Builds the WooCommerce authorization and authenticated OAuth URLs.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */
class Product_Recommendation_Quiz_For_Ecommerce_Admin_Oauth_Url_Builder {

	/**
	 * Get WooCommerce authorization URL for initial plugin setup.
	 *
	 * @since 1.0.0
	 * @return string The WooCommerce authorization URL.
	 */
	public function prquiz_get_woocommerce_auth_url() {
		$auth_base    = get_site_url( null, '/wc-auth/v1/authorize/' );
		$return_url   = admin_url( 'admin.php?page=prqfw' );
		$callback_url = PRQ_API_URL . '/api/v1/woocommerce/create';

		$params = array(
			'app_name'     => 'Product Recommendation Quiz',
			'scope'        => 'read_write',
			'user_id'      => PRQ_STORE_URL,
			'return_url'   => $return_url,
			'callback_url' => $callback_url,
		);

		// Add PHP_QUERY_RFC3986 so spaces are encoded as %20 and not +.
		$query = http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );

		return $auth_base . '?' . $query;
	}

	/**
	 * Get OAuth URL for authenticated admin panel access.
	 *
	 * @since 1.0.0
	 * @return string The OAuth URL with all required parameters.
	 */
	public function prquiz_get_oauth_url() {
		if ( Product_Recommendation_Quiz_For_Ecommerce::is_development_environment() ) {
			$oauth_url = 'http://localhost:9528/public/woocommerce/oauth';
		} else {
			$oauth_url = 'https://admin.revenuehunt.com/public/woocommerce/oauth';
		}

		$shop_hashid = get_option( PRQ_OPTION_SHOP_HASHID );
		$api_key     = get_option( PRQ_OPTION_API_KEY );
		$country     = function_exists( 'WC' ) && WC() && WC()->countries ? WC()->countries->get_base_country() : '';
		$time        = time();

		$data = sprintf(
			'hashid=%s&domain=%s&plugin_version=%s&timestamp=%s',
			$shop_hashid,
			PRQ_STORE_URL,
			PRQ_PLUGIN_VERSION,
			(string) $time
		);
		$hmac = base64_encode( hash_hmac( 'sha256', $data, $api_key, true ) );

		$locale_parts = explode( '_', get_locale() );
		$locale       = isset( $locale_parts[0] ) ? $locale_parts[0] : 'en';

		$params = array(
			'timestamp'      => $time,
			'domain'         => PRQ_STORE_URL,
			'shop_hashid'    => $shop_hashid,
			'channel'        => 'wordpress',
			'country'        => $country,
			'plugin_version' => PRQ_PLUGIN_VERSION,
			'woo_version'    => PRQ_WOO_VERSION,
			'wp_version'     => PRQ_WP_VERSION,
			'name'           => get_bloginfo( 'name' ),
			'email'          => get_bloginfo( 'admin_email' ),
			'locale'         => $locale,
			'timezone'       => get_option( 'gmt_offset' ),
			'currency'       => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : '',
			'symbol'         => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) : '',
			'hmac'           => $hmac,
		);

		// Use PHP_QUERY_RFC3986 so spaces are encoded as %20 and not +.
		$query = http_build_query( $params, '', '&', PHP_QUERY_RFC3986 );

		return $oauth_url . '?' . $query;
	}
}
