<?php
/**
 * Abstract class for mailer integrations.
 *
 * @package     affiliate-for-woocommerce/includes/abstracts/
 * @since       9.2.0
 * @version     1.0.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Mailer_Integrations' ) ) {

	/**
	 * Core abstract class extended to implement opt-in platform integrations.
	 *
	 * @abstract
	 */
	abstract class AFWC_Mailer_Integrations {

		/**
		 * ID of the platform, i.e. "mailchimp".
		 *
		 * @var string
		 */
		public $platform_id = '';

		/**
		 * Contact details to be subscribed to the platform.
		 *
		 * @var array $contact {
		 *     Contact arguments.
		 *
		 *     @type int    $user_id    The user ID of the affiliate user.
		 *     @type string $email      The email address of the affiliate user.
		 *     @type string $first_name The first name of the affiliate user.
		 *     @type string $last_name  The last name of the affiliate user.
		 * }
		 */
		public $contact = array();

		/**
		 * ID of the list on the  platform to subscribe contacts.
		 *
		 * @var string
		 */
		public $list_id = '';

		/**
		 * Storage for API request errors.
		 *
		 * @var array
		 */
		public $errors = array();

		/**
		 * API key for authentication with the platform.
		 *
		 * @var string
		 */
		protected $api_key = '';

		/**
		 * API request URL for the  platform.
		 *
		 * @var string
		 */
		protected $api_url = '';

		/**
		 * Flag to determine if API data should be sent in JSON
		 *
		 * @var bool
		 */
		public $json = true;

		/**
		 * Constructor
		 *
		 * @return void
		 */
		public function __construct() {
			add_filter( 'afwc_email_marketing_integrations_section_admin_settings', array( $this, 'settings' ) );
			$this->init();
		}

		/**
		 * Get things started. This is where public properties should be defined if needed.
		 *
		 * @return void
		 */
		public function init() {}

		/**
		 * Setup the API call to the platform to subscribe a contact
		 *
		 * @param array $contact Contact data.
		 * @return WP_Error|array
		 */
		public function subscribe_contact( $contact = array() ) {
			return $contact;
		}

		/**
		 * Determine if an email is already subscribed
		 */
		public function already_subscribed() {}

		/**
		 * Setup settings for the platform
		 *
		 * @param array $settings Settings for the platform.
		 * @return array
		 */
		public function settings( $settings = array() ) {
			return $settings;
		}

		/**
		 * Call platform API URL with specified parameters
		 *
		 * @param string $url    Platform URL.
		 * @param array  $body    Platform Body parameters.
		 * @param array  $headers Platform Headers.
		 * @return WP_Error|array
		 */
		protected function call_api( $url = '', $body = array(), $headers = array() ) {
			if ( empty( $url ) ) {
				Affiliate_For_WooCommerce::log( 'error', _x( 'Please provide a platform API URL', 'Error when api URL is missing', 'affiliate-for-woocommerce' ) );
				return;
			}

			if ( empty( $body ) ) {
				Affiliate_For_WooCommerce::log( 'error', _x( 'Please provide platform API body parameters', 'Error when api body is missing', 'affiliate-for-woocommerce' ) );
				return;
			}

			$args = array(
				'timeout'     => 45,
				'sslverify'   => false,
				'httpversion' => '1.1',
				'headers'     => $headers,

				/**
				 * Filters the opt-in platform subscription arguments.
				 *
				 * @param string                    $body Subscription arguments for the opt-in platform.
				 * @param \AFWC_Mailer_Integrations $this Platform instance.
				 *
				 * @since 9.2.0
				 */
				'body'        => apply_filters( 'afwc_email_marketing_platform_subscribe_args', $body, array( 'source' => $this ) ),
			);

			if ( $this->json ) {
				$args['body'] = wp_json_encode( $args['body'] );
			}

			$request = wp_remote_post( $url, $args );

			if ( is_wp_error( $request ) ) {
				/* translators: 1: Request Response Error Code 2: Request Response Error Details */
				Affiliate_For_WooCommerce::log( 'error', sprintf( _x( 'Something went wrong when requesting. Response error code: %1$s and Response error detail: %2$s', 'Error when requesting platform', 'affiliate-for-woocommerce' ), $request->get_error_code(), $request->get_error_message() ) );
			}

			if ( 200 !== wp_remote_retrieve_response_code( $request ) ) {
				/* translators: 1: Request Response Error Code 2: Request Response Body Details */
				Affiliate_For_WooCommerce::log( 'error', sprintf( _x( 'Something went wrong when requesting response code. Response code: %1$s. Response message: %2$s. Response body detail: %3$s', 'Error when calling requesting platform', 'affiliate-for-woocommerce' ), wp_remote_retrieve_response_code( $request ), wp_remote_retrieve_response_message( $request ), $request['body'] ) );
			}

			return $request;
		}

		/**
		 * Method to validate the contact details.
		 *
		 * @param array $contact Contact data.
		 * @return WP_Error|array
		 */
		public function validate_contact_details( $contact = array() ) {
			if ( empty( $contact['user_id'] ) ) {
				if ( ! empty( $contact['email'] ) ) {
					$contact['user_id'] = email_exists( $contact['email'] );
				} else {
					return $contact;
				}
			}

			if ( empty( $contact['email'] ) ) {
				$user             = get_userdata( $contact['user_id'] );
				$contact['email'] = $user->user_email;
			}

			if ( empty( $contact['first_name'] ) ) {
				$contact['first_name'] = get_user_meta( $contact['user_id'], 'first_name', true );
			}

			if ( empty( $contact['last_name'] ) ) {
				$contact['last_name'] = get_user_meta( $contact['user_id'], 'last_name', true );
			}

			return $contact;
		}
	}
}
