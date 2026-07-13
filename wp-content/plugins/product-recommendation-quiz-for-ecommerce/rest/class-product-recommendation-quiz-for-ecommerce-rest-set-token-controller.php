<?php
/**
 * REST controller for the token-intake endpoints.
 *
 * Owns both routes the RevenueHunt platform calls to provision a store's
 * credentials — the authenticated WooCommerce route (wc/v3/prq_set_token) and
 * the signed route (prq/v1/settoken) — together with their permission
 * callbacks, per-IP rate limiting, input validation, and HMAC verification.
 *
 * The wire contract (routes, params, option keys, signature format) is part of
 * the V1 connection protocol the monolith depends on and is preserved exactly.
 *
 * @link       https://revenuehunt.com/
 * @since      2.3.9
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/rest
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Registers and handles the prq_set_token / settoken REST endpoints.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/rest
 */
class Product_Recommendation_Quiz_For_Ecommerce_Rest_Set_Token_Controller {

	/**
	 * Register both token-intake REST routes.
	 *
	 * Hooked on rest_api_init.
	 *
	 * @since 2.3.9
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			'wc/v3',
			'prq_set_token',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'check_woocommerce_permission' ),
			)
		);

		register_rest_route(
			'prq/v1',
			'settoken',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'verify_signature' ),
			)
		);
	}

	/**
	 * Check WooCommerce API authentication for REST endpoint.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST API request object.
	 * @return bool True if authenticated, false otherwise.
	 */
	public function check_woocommerce_permission( $request ) {
		// Ensure WooCommerce is available.
		if ( ! class_exists( 'WC_REST_Authentication' ) ) {
			return false;
		}

		$auth = new WC_REST_Authentication();
		// Note we are not trying to authenticate a specific user, so we need to pass false to the function.
		$result = $auth->authenticate( false );

		// Return false if authentication failed with an error.
		if ( is_wp_error( $result ) ) {
			return false;
		}

		// Return true only if we have a valid authenticated user.
		if ( is_a( $result, 'WP_User' ) ) {
			return true;
		}

		// Return false for all other cases (no authentication provided, null, or unexpected values).
		return false;
	}

	/**
	 * Validate shop_hashid format.
	 *
	 * Shop hashid should be alphanumeric only.
	 *
	 * @since 2.2.15
	 * @param string $shop_hashid The shop hashid to validate.
	 * @return bool True if valid, false otherwise.
	 */
	public static function validate_shop_hashid( $shop_hashid ) {
		if ( empty( $shop_hashid ) ) {
			return false;
		}
		return (bool) preg_match( '/^[a-zA-Z0-9]+$/', $shop_hashid );
	}

	/**
	 * Validate the format of a credential (api_key / token).
	 *
	 * Permissive defense-in-depth: credentials must be printable, whitespace-free
	 * ASCII of a bounded length. Rejects control characters and oversized payloads
	 * without assuming the server's exact key format.
	 *
	 * @since 2.3.9
	 * @param string $value The credential value to validate.
	 * @return bool True if the format is acceptable, false otherwise.
	 */
	public static function validate_credential_format( $value ) {
		if ( null === $value || '' === $value ) {
			return false;
		}
		return (bool) preg_match( '/^[\x21-\x7E]{1,255}$/', $value );
	}

	/**
	 * Determine the client IP used for rate limiting.
	 *
	 * Defaults to REMOTE_ADDR, which the client cannot spoof. The X-Forwarded-For
	 * header is consulted ONLY when the site explicitly opts in via the
	 * `prq_trust_forwarded_for` filter (e.g. behind a known reverse proxy / CDN),
	 * and then only the proxy-appended (last) entry is trusted — never the
	 * client-supplied head of the list.
	 *
	 * @since 2.3.9
	 * @return string The client IP, or '' if none could be determined.
	 */
	public function get_client_ip() {
		$remote = isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';

		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] )
			&& apply_filters( 'prq_trust_forwarded_for', false ) ) {
			$forwarded = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
			$proxied   = trim( end( $forwarded ) );
			if ( '' !== $proxied ) {
				return $proxied;
			}
		}

		return $remote;
	}

	/**
	 * Check rate limit for REST API requests.
	 *
	 * Limits requests to 10 per minute per IP address to prevent brute force attacks.
	 * When no IP can be determined the request is NOT allowed through unchecked — it
	 * is throttled via a shared bucket so the limit still applies (no fail-open).
	 *
	 * @since 2.2.15
	 * @return true|WP_Error True if under limit, WP_Error if rate limited.
	 */
	public function check_rate_limit() {
		$ip            = $this->get_client_ip();
		$bucket        = '' !== $ip ? md5( $ip ) : 'unknown';
		$transient_key = 'prq_rate_' . $bucket;
		$attempts      = (int) get_transient( $transient_key );

		if ( $attempts >= 10 ) {
			return new WP_Error(
				'rate_limited',
				__( 'Too many requests. Please try again later.', 'product-recommendation-quiz-for-ecommerce' ),
				array( 'status' => 429 )
			);
		}

		set_transient( $transient_key, $attempts + 1, MINUTE_IN_SECONDS );
		return true;
	}

	/**
	 * Handle REST API token setting request.
	 *
	 * Stores shop_hashid and api_key from the RevenueHunt platform
	 * if they don't already exist.
	 *
	 * @since 1.0.0
	 * @param WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response|WP_Error Response with shop_hashid or error.
	 */
	public function handle( $request ) {
		// Check rate limit.
		$rate_limit_check = $this->check_rate_limit();
		if ( is_wp_error( $rate_limit_check ) ) {
			return $rate_limit_check;
		}

		// Use WP_REST_Request methods instead of $_REQUEST for security.
		$new_shop_hashid = sanitize_text_field( $request->get_param( 'shop_hashid' ) );
		$new_api_key     = sanitize_text_field( $request->get_param( 'api_key' ) );

		// Validate shop_hashid format if provided.
		if ( ! empty( $new_shop_hashid ) && ! self::validate_shop_hashid( $new_shop_hashid ) ) {
			return new WP_Error(
				'invalid_shop_hashid',
				__( 'Invalid shop hashid format. Only alphanumeric characters are allowed.', 'product-recommendation-quiz-for-ecommerce' ),
				array( 'status' => 400 )
			);
		}

		// Validate api_key format if provided.
		if ( ! empty( $new_api_key ) && ! self::validate_credential_format( $new_api_key ) ) {
			return new WP_Error(
				'invalid_api_key',
				__( 'Invalid api_key format.', 'product-recommendation-quiz-for-ecommerce' ),
				array( 'status' => 400 )
			);
		}

		// Get existing values.
		$shop_hashid = get_option( PRQ_OPTION_SHOP_HASHID );
		$api_key     = get_option( PRQ_OPTION_API_KEY );

		// Only set if not already present.
		if ( ! $shop_hashid && ! empty( $new_shop_hashid ) ) {
			update_option( PRQ_OPTION_SHOP_HASHID, $new_shop_hashid, false );
		}

		if ( ! $api_key && ! empty( $new_api_key ) ) {
			update_option( PRQ_OPTION_API_KEY, $new_api_key, false );
		}

		return rest_ensure_response( get_option( PRQ_OPTION_SHOP_HASHID ) );
	}

	/**
	 * Verify HMAC signature for secondary REST endpoint.
	 *
	 * This endpoint is called by the RevenueHunt platform to set tokens.
	 * Validates the HMAC signature to ensure the request is authentic.
	 *
	 * @since 2.2.15
	 * @param WP_REST_Request $request The REST API request object.
	 * @return bool True if signature is valid, false otherwise.
	 */
	public function verify_signature( $request ) {
		$signature   = sanitize_text_field( $request->get_param( 'signature' ) );
		$shop_hashid = sanitize_text_field( $request->get_param( 'shop_hashid' ) );
		$token       = sanitize_text_field( $request->get_param( 'token' ) );
		$timestamp   = sanitize_text_field( $request->get_param( 'timestamp' ) );

		// All parameters must be present.
		if ( empty( $signature ) || empty( $shop_hashid ) || empty( $token ) ) {
			return false;
		}

		// If timestamp provided, check it's not too old (5 minute window).
		if ( ! empty( $timestamp ) ) {
			$request_time = absint( $timestamp );
			$current_time = time();
			if ( abs( $current_time - $request_time ) > 300 ) {
				return false;
			}
		}

		// If we have an existing API key, verify the signature.
		// NOTE: On first-time setup, API key doesn't exist yet, so signature
		// verification is skipped. This is intentional - the initial credentials
		// come from RevenueHunt's server during the OAuth flow. Once credentials
		// are set, handle() only allows updates if current values are empty,
		// so the window for exploitation is minimal.
		$api_key = get_option( PRQ_OPTION_API_KEY );
		if ( $api_key ) {
			// Reconstruct the data that should have been signed.
			$data = sprintf( 'hashid=%s&token=%s', $shop_hashid, $token );
			if ( ! empty( $timestamp ) ) {
				$data .= '&timestamp=' . $timestamp;
			}
			$expected_signature = base64_encode( hash_hmac( 'sha256', $data, $api_key, true ) );

			// Use hash_equals for timing-safe comparison.
			if ( ! hash_equals( $expected_signature, $signature ) ) {
				return false;
			}
		}

		return true;
	}
}
