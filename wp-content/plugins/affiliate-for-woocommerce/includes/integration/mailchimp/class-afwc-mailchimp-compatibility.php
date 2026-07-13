<?php
/**
 * Class for Mailchimp Integration
 *
 * @package    affiliate-for-woocommerce/includes/integrations/mailchimp/
 * @since      9.2.0
 * @version    1.0.2
 */

if ( ! class_exists( 'AFWC_Mailer_Integrations', false ) ) {
	include_once AFWC_PLUGIN_DIRPATH . '/includes/abstracts/class-afwc-mailer-integrations.php';
}

if ( ! class_exists( 'AFWC_Mailchimp_Compatibility' ) && class_exists( 'AFWC_Mailer_Integrations' ) ) {

	/**
	 * Mailchimp opt-in platform integration.
	 *
	 * @abstract
	 */
	class AFWC_Mailchimp_Compatibility extends AFWC_Mailer_Integrations {

		/**
		 * Variable to hold instance of this class.
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get the single instance of this class
		 *
		 * @return AFWC_Mailchimp_Compatibility
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
			add_action( 'afwc_subscribe_affiliate', array( $this, 'subscribe_contact' ), 10, 1 );

			add_filter( 'afwc_allowed_email_marketing_integrations', array( $this, 'afwc_enable_mailchimp' ), 10, 1 );

			add_filter( 'afwc_email_marketing_integrations_section_admin_settings', array( $this, 'add_settings' ) );

			// Initialize the parent class to execute background process.
			parent::__construct();
		}

		/**
		 * Initialize our API keys and platform variables.
		 *
		 * @return void
		 */
		public function init() {
			$this->platform_id = 'mailchimp';
			$this->api_key     = get_option( 'afwc_mailchimp_api_key', '' );
			$this->list_id     = get_option( 'afwc_mailchimp_audience_id', '' );
			$data_center       = 'us4';

			if ( ! empty( $this->api_key ) ) {
				$data_center = substr( $this->api_key, strpos( $this->api_key, '-' ) + 1 );
			}

			if ( ! empty( $this->list_id ) ) {
				$this->api_url = 'https://' . $data_center . '.api.mailchimp.com/3.0/lists/' . $this->list_id . '/members/';
			}
		}

		/**
		 * Subscribe a contact.
		 *
		 * @param array $contact Subscriber Details.
		 * @return array|WP_Error
		 */
		public function subscribe_contact( $contact = array() ) {
			$this->contact = $this->validate_contact_details( $contact );

			$headers = array(
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $this->api_key ), // phpcs:ignore
			);

			$exists = $this->already_subscribed();
			if ( $exists ) {
				/* translators: Email address */
				Affiliate_For_WooCommerce::log( 'error', sprintf( _x( '%s is already subscribed to this list.', 'Error when an email is already subscribed in the Mailchimp Audience/List', 'affiliate-for-woocommerce' ), $this->contact['email'] ) );
				return;

			}

			$body = array(
				'email_address' => $this->contact['email'],
				'status'        => 'subscribed', // send by default as subscribed.
				'merge_fields'  => array(
					'FNAME' => $this->contact['first_name'],
					'LNAME' => $this->contact['last_name'],
				),
			);

			$response = $this->call_api( $this->api_url, $body, $headers );
			if ( ! empty( $response ) && 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$body = json_decode( wp_remote_retrieve_body( $response ) );

				/* translators: 1: Request Response Error Code 2: Request Response Body Details */
				Affiliate_For_WooCommerce::log( 'error', sprintf( _x( 'Something went wrong while subscribing. Response code: %1$s. Response body detail: %2$s', 'Error when calling Mailchimp API', 'affiliate-for-woocommerce' ), wp_remote_retrieve_response_code( $response ), $body->detail ) );
			}

			return $response;
		}

		/**
		 * Determine if an email is already subscribed.
		 *
		 * @return true|false
		 */
		public function already_subscribed() {
			$ret  = false;
			$hash = md5( strtolower( $this->contact['email'] ) );
			$url  = $this->api_url . $hash;

			$headers = array(
				'Authorization' => 'Basic ' . base64_encode( 'user:' . $this->api_key ), // phpcs:ignore
			);

			$args = array(
				'timeout'     => 45,
				'sslverify'   => false,
				'httpversion' => '1.1',
				'headers'     => $headers,
			);

			$request = wp_remote_get( $url, $args );

			if ( is_wp_error( $request ) ) {
				/* translators: 1: Request Response Error Code 2: Request Response Error Message */
				Affiliate_For_WooCommerce::log( 'error', sprintf( _x( 'Something went wrong while requesting. Request code: %1$s and Request error message: %2$s', 'Error when Mailchimp tries to subscribe an email in the Audience/List but encouters an error', 'affiliate-for-woocommerce' ), $request->get_error_code(), $request->get_error_message() ) );
			}

			if ( 200 === wp_remote_retrieve_response_code( $request ) ) {
				$ret = true;
			}

			return $ret;
		}

		/**
		 * Register our platform settings.
		 *
		 * @param array $settings Array of settings for Mailchimp.
		 * @return array
		 */
		public function afwc_enable_mailchimp( $settings = array() ) {
			if ( is_array( $settings ) ) {
				$settings['mailchimp'] = 'Mailchimp';
			}

			return $settings;
		}

		/**
		 * Function to add Mailchimp specific settings.
		 *
		 * @param  array $settings Existing settings.
		 * @return array $settings Updated settings.
		 */
		public function add_settings( $settings = array() ) {
			$enabled_email_marketing_integrations = get_option( 'afwc_enabled_mailer_integration', array() );

			$mailchimp_options = array(
				array(
					'name'              => _x( 'Mailchimp API Key', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'              => sprintf(
						/* translators: Link to MailChimp API doc */
						_x( 'Use your Mailchimp API key to connect your account. To locate, learn more <a href="%s" target="_blank">here</a>.', 'setting description', 'affiliate-for-woocommerce' ),
						'https://mailchimp.com/help/about-api-keys/'
					),
					'id'                => 'afwc_mailchimp_api_key',
					'type'              => 'text',
					'placeholder'       => _x( 'Enter your Mailchimp API Key', 'Placeholder for Mailchimp API Key setting', 'affiliate-for-woocommerce' ),
					'autoload'          => false,
					'desc_tip'          => false,
					'row_class'         => ( ! empty( $enabled_email_marketing_integrations ) && ( 'mailchimp' !== $enabled_email_marketing_integrations ) ) ? 'afwc-hide' : '',
					'custom_attributes' => array(
						'data-afwc-show-when' => 'afwc_enabled_mailer_integration=mailchimp',
					),
				),
				array(
					'name'              => _x( 'Mailchimp Audience ID', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'              => sprintf(
						/* translators: Link to Mailchimp Audience ID doc */
						_x( 'Your affiliates will be added to this list. To locate, learn more <a href="%s" target="_blank">here</a>.', 'setting description', 'affiliate-for-woocommerce' ),
						'https://mailchimp.com/help/find-audience-id/'
					),
					'id'                => 'afwc_mailchimp_audience_id',
					'type'              => 'text',
					'placeholder'       => _x( 'Enter your Mailchimp Audience ID', 'Placeholder for Audience ID setting', 'affiliate-for-woocommerce' ),
					'autoload'          => false,
					'desc_tip'          => false,
					'row_class'         => ( ! empty( $enabled_email_marketing_integrations ) && ( 'mailchimp' !== $enabled_email_marketing_integrations ) ) ? 'afwc-hide' : '',
					'custom_attributes' => array(
						'data-afwc-show-when' => 'afwc_enabled_mailer_integration=mailchimp',
					),
				),
			);

			array_splice( $settings, ( count( $settings ) - 1 ), 0, $mailchimp_options );

			return $settings;
		}
	}

}

AFWC_Mailchimp_Compatibility::get_instance();
