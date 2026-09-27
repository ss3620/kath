<?php
/**
 * Plugin Name: Earth Goddess AU – Registration Guard
 * Description: Closes the wp-login.php signup form and rejects automated signups on the WooCommerce account form.
 * Version: 1.0.0
 * Author: Earth Goddess AU
 */

defined( 'ABSPATH' ) || exit;

class EG_Registration_Guard {

	/**
	 * Accounts one IP address may create per hour.
	 */
	const RATE_LIMIT_MAX = 3;

	/**
	 * Mail domains seen in the September 2026 signup flood.
	 *
	 * @var string[]
	 */
	const BLOCKED_DOMAINS = array(
		'farironalds.com',
		'bestvpsfor.xyz',
	);

	/**
	 * Bootstrap.
	 */
	public static function init() {
		add_filter( 'option_users_can_register', '__return_zero' );
		add_action( 'login_form_register', array( __CLASS__, 'block_login_signup' ), 0 );

		add_filter( 'registration_errors', array( __CLASS__, 'check_signup' ), 10, 3 );
		add_filter( 'woocommerce_registration_errors', array( __CLASS__, 'check_signup' ), 10, 3 );

		add_filter( 'wp_pre_insert_user_data', array( __CLASS__, 'block_automated_insert' ), 10, 4 );

		add_action( 'user_register', array( __CLASS__, 'count_signup' ) );

		if ( self::recaptcha_keys() ) {
			add_action( 'woocommerce_register_form', array( __CLASS__, 'render_recaptcha' ) );
			add_filter( 'woocommerce_registration_errors', array( __CLASS__, 'verify_recaptcha' ), 20, 3 );

			add_action( 'register_form', array( __CLASS__, 'render_recaptcha' ) );
			add_filter( 'registration_errors', array( __CLASS__, 'verify_recaptcha' ), 20, 3 );
		}
	}

	/**
	 * Google reCAPTCHA v2 ("I'm not a robot") credentials.
	 *
	 * Prefers wp-config.php constants, then the keys saved in
	 * Elementor → Settings → Integrations → reCAPTCHA.
	 *
	 *   define( 'EG_RECAPTCHA_SITE_KEY', '6Lc...' );
	 *   define( 'EG_RECAPTCHA_SECRET_KEY', '6Lc...' );
	 *
	 * @return array{site:string,secret:string}|null
	 */
	private static function recaptcha_keys() {
		$site   = defined( 'EG_RECAPTCHA_SITE_KEY' ) ? EG_RECAPTCHA_SITE_KEY : '';
		$secret = defined( 'EG_RECAPTCHA_SECRET_KEY' ) ? EG_RECAPTCHA_SECRET_KEY : '';

		if ( ! $site || ! $secret ) {
			$site   = (string) get_option( 'pro_recaptcha_site_key' );
			$secret = (string) get_option( 'pro_recaptcha_secret_key' );
		}

		if ( ! $site || ! $secret ) {
			return null;
		}

		return array(
			'site'   => $site,
			'secret' => $secret,
		);
	}

	/**
	 * Renders the "I'm not a robot" checkbox inside the form.
	 */
	public static function render_recaptcha() {
		$keys = self::recaptcha_keys();
		if ( ! $keys ) {
			return;
		}

		wp_enqueue_script(
			'google-recaptcha',
			'https://www.google.com/recaptcha/api.js',
			array(),
			null,
			true
		);

		printf(
			'<p class="form-row form-row-wide"><div class="g-recaptcha" data-sitekey="%s"></div></p>',
			esc_attr( $keys['site'] )
		);
	}

	/**
	 * @param WP_Error $errors Collected errors.
	 * @param string   $login  Submitted username.
	 * @param string   $email  Submitted email address.
	 * @return WP_Error
	 */
	public static function verify_recaptcha( $errors, $login, $email ) {
		unset( $login, $email );

		$keys = self::recaptcha_keys();
		if ( ! $keys ) {
			return $errors;
		}

		$token = isset( $_POST['g-recaptcha-response'] )
			? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) )
			: '';

		if ( '' === $token ) {
			$errors->add( 'eg_recaptcha_missing', __( 'Please confirm you are not a robot.', 'earth-goddess' ) );
			return $errors;
		}

		$response = wp_remote_post(
			'https://www.google.com/recaptcha/api/siteverify',
			array(
				'timeout' => 10,
				'body'    => array(
					'secret'   => $keys['secret'],
					'response' => $token,
					'remoteip' => self::client_ip(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $errors;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['success'] ) ) {
			$errors->add( 'eg_recaptcha_failed', __( 'We could not verify that request. Please try again.', 'earth-goddess' ) );
		}

		return $errors;
	}

	/**
	 * Last line of defence.
	 *
	 * Every signup route ends in wp_insert_user(), including ones that never run the
	 * registration_errors filters: Affiliate for WooCommerce's [afwc_registration_form],
	 * wpForo, Jetpack SSO and the REST API. Returning an empty array here aborts the
	 * insert with a WP_Error.
	 *
	 * @param array    $data     Sanitised user row headed for the database.
	 * @param bool     $update   Whether an existing user is being updated.
	 * @param int|null $user_id  User being updated, or null on create.
	 * @param array    $userdata Raw arguments passed to wp_insert_user().
	 * @return array
	 */
	public static function block_automated_insert( $data, $update, $user_id, $userdata ) {
		unset( $user_id, $userdata );

		if ( $update ) {
			return $data;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return $data;
		}

		if ( current_user_can( 'create_users' ) ) {
			return $data;
		}

		$login = isset( $data['user_login'] ) ? $data['user_login'] : '';
		$email = isset( $data['user_email'] ) ? $data['user_email'] : '';

		if ( self::looks_automated( $login, $email ) || self::domain_is_blocked( $email ) || self::domain_is_undeliverable( $email ) ) {
			return array();
		}

		return $data;
	}

	/**
	 * Refuse wp-login.php?action=register outright.
	 */
	public static function block_login_signup() {
		wp_die(
			esc_html__( 'Account registration is not available on this page. Please use the My Account page.', 'earth-goddess' ),
			esc_html__( 'Registration closed', 'earth-goddess' ),
			array( 'response' => 403 )
		);
	}

	/**
	 * Shared validation for the core and WooCommerce signup forms.
	 *
	 * Both hooks pass ( WP_Error, username, email ).
	 *
	 * @param WP_Error $errors Collected errors.
	 * @param string   $login  Submitted username.
	 * @param string   $email  Submitted email address.
	 * @return WP_Error
	 */
	public static function check_signup( $errors, $login, $email ) {
		if ( self::looks_automated( $login, $email ) ) {
			$errors->add(
				'eg_automated_signup',
				__( 'Please choose a username that contains letters.', 'earth-goddess' )
			);
			return $errors;
		}

		if ( self::domain_is_blocked( $email ) || self::domain_is_undeliverable( $email ) ) {
			$errors->add(
				'eg_unusable_email',
				__( 'Please use an email address we can deliver to.', 'earth-goddess' )
			);
			return $errors;
		}

		if ( self::over_rate_limit() ) {
			$errors->add(
				'eg_signup_rate_limit',
				__( 'Too many accounts have been created from your connection. Please try again later.', 'earth-goddess' )
			);
		}

		return $errors;
	}

	/**
	 * The September 2026 bots used an all-digit username reused as the email local part,
	 * e.g. 9991 / 9991@farironalds.com.
	 *
	 * @param string $login Submitted username.
	 * @param string $email Submitted email address.
	 * @return bool
	 */
	private static function looks_automated( $login, $email ) {
		$login = trim( (string) $login );

		if ( '' !== $login && preg_match( '/^\d+$/', $login ) ) {
			return true;
		}

		$local = self::email_local_part( $email );

		return ( '' !== $local && preg_match( '/^\d+$/', $local ) );
	}

	/**
	 * @param string $email Submitted email address.
	 * @return bool
	 */
	private static function domain_is_blocked( $email ) {
		$domain = self::email_domain( $email );
		if ( '' === $domain ) {
			return false;
		}

		foreach ( self::BLOCKED_DOMAINS as $blocked ) {
			if ( $domain === $blocked || self::str_ends_with( $domain, '.' . $blocked ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $email Submitted email address.
	 * @return bool
	 */
	private static function domain_is_undeliverable( $email ) {
		$domain = self::email_domain( $email );
		if ( '' === $domain || ! function_exists( 'checkdnsrr' ) ) {
			return false;
		}

		return ! checkdnsrr( $domain, 'MX' ) && ! checkdnsrr( $domain, 'A' );
	}

	/**
	 * @return bool
	 */
	private static function over_rate_limit() {
		$key = self::rate_limit_key();

		return $key && (int) get_transient( $key ) >= self::RATE_LIMIT_MAX;
	}

	/**
	 * @param int $user_id Newly created user.
	 */
	public static function count_signup( $user_id ) {
		unset( $user_id );

		$key = self::rate_limit_key();
		if ( ! $key ) {
			return;
		}

		set_transient( $key, (int) get_transient( $key ) + 1, HOUR_IN_SECONDS );
	}

	/**
	 * @return string
	 */
	private static function rate_limit_key() {
		$ip = self::client_ip();

		return $ip ? 'eg_reg_ip_' . md5( $ip ) : '';
	}

	/**
	 * @return string
	 */
	private static function client_ip() {
		foreach ( array( 'HTTP_CF_CONNECTING_IP', 'REMOTE_ADDR' ) as $header ) {
			if ( empty( $_SERVER[ $header ] ) ) {
				continue;
			}

			$ip = filter_var( wp_unslash( $_SERVER[ $header ] ), FILTER_VALIDATE_IP );
			if ( $ip ) {
				return $ip;
			}
		}

		return '';
	}

	/**
	 * @param string $email Email address.
	 * @return string
	 */
	private static function email_local_part( $email ) {
		$parts = explode( '@', (string) $email );

		return ( count( $parts ) > 1 ) ? trim( $parts[0] ) : '';
	}

	/**
	 * @param string $email Email address.
	 * @return string
	 */
	private static function email_domain( $email ) {
		$parts = explode( '@', (string) $email );

		return ( count( $parts ) > 1 ) ? strtolower( trim( end( $parts ) ) ) : '';
	}

	/**
	 * @param string $haystack Subject.
	 * @param string $needle   Suffix.
	 * @return bool
	 */
	private static function str_ends_with( $haystack, $needle ) {
		if ( function_exists( 'str_ends_with' ) ) {
			return str_ends_with( $haystack, $needle );
		}

		return '' === $needle || substr( $haystack, -strlen( $needle ) ) === $needle;
	}
}

EG_Registration_Guard::init();
