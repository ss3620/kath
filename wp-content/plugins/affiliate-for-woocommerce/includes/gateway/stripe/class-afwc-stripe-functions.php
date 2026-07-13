<?php
/**
 * Main class for Stripe functions and settings.
 *
 * @package   affiliate-for-woocommerce/includes/gateway/stripe/
 * @since     8.9.0
 * @version   1.1.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Stripe_Functions' ) ) {

	/**
	 * Main class for Affiliate Stripe Functions
	 */
	class AFWC_Stripe_Functions {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return AFWC_Stripe_Functions Singleton object of this class
		 */
		public static function get_instance() {

			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			// Ajax calls.
			add_action( 'wp_ajax_disconnect_stripe_connect', array( $this, 'disconnect_account' ) );
		}

		/**
		 * Function to handle WC compatibility related function call from appropriate class
		 *
		 * @param string $function_name Function to call.
		 * @param array  $arguments     Array of arguments passed while calling $function_name.
		 *
		 * @return mixed Result of function call.
		 */
		public function __call( $function_name = '', $arguments = array() ) {

			if ( empty( $function_name ) || ! is_callable( 'SA_WC_AFW_Compatibility', $function_name ) ) {
				return;
			}

			if ( ! empty( $arguments ) ) {
				return call_user_func_array( 'SA_WC_AFW_Compatibility::' . $function_name, $arguments );
			} else {
				return call_user_func( 'SA_WC_AFW_Compatibility::' . $function_name );
			}
		}

		/**
		 * Get stripe connection status for the affiliate user.
		 *
		 * @param int $user_id User id.
		 * @return string
		 */
		public function afwc_get_stripe_user_status( $user_id = 0 ) {
			$connection_status = 'disconnect';
			if ( empty( $user_id ) ) {
				return $connection_status;
			}

			$stripe_user_id    = get_user_meta( $user_id, 'afwc_stripe_user_id', true );
			$connection_status = ( ! empty( $stripe_user_id ) ) ? 'connect' : $connection_status;

			return $connection_status;
		}

		/**
		 * Method to connect stripe by user ID.
		 *
		 * @param int    $user_id User ID.
		 * @param string $code Code for authentication.
		 *
		 * @return bool|void
		 */
		public function connect_by_user_id_and_access_code( $user_id = 0, $code = '' ) {
			if ( empty( $user_id ) ) {
				return;
			}

			$current_status = $this->afwc_get_stripe_user_status( $user_id );

			$stripe_connect_api = is_callable( array( 'AFWC_Stripe_Connect', 'get_instance' ) ) ? AFWC_Stripe_Connect::get_instance() : null;
			$stripe_token       = ( 'disconnect' === $current_status ) ? ( ( ! empty( $stripe_connect_api ) && is_callable( array( $stripe_connect_api, 'get_oauth_token' ) ) ) ? $stripe_connect_api->get_oauth_token( $code ) : false ) : false;

			if ( $stripe_token ) {
				$stripe_serialized = $stripe_token->jsonSerialize();
				update_user_meta( $user_id, 'afwc_stripe_user_id', $stripe_serialized['stripe_user_id'] );
				update_user_meta( $user_id, 'afwc_stripe_access_token', $stripe_serialized['access_token'] );
				$receiver = array(
					'user_id'         => $user_id,
					'status_receiver' => 'connect',
					'stripe_id'       => $stripe_serialized['stripe_user_id'],
				);
				update_user_meta( $user_id, 'afwc_payout_method', 'stripe' );

				/**
				 * Action triggered after connecting with Stripe.
				 *
				 * @param int    $user_id           User ID.
				 * @param string $code              Code for authentication.
				 * @param array  $stripe_serialized Serialized Stripe token data.
				 *
				 * @since 8.9.0
				 */
				do_action( 'afwc_after_connect_with_stripe', $user_id, $code, $stripe_serialized );

				return true;
			}

			return false;
		}

		/**
		 * Method disconnect Stripe connect account.
		 *
		 * @param int $user_id User ID.
		 *
		 * @return array|void array with status and message or void if user is empty.
		 */
		public function disconnect_by_user_id( $user_id = 0 ) {
			if ( empty( $user_id ) ) {
				return;
			}

			$stripe_user_id               = get_user_meta( $user_id, 'afwc_stripe_user_id', true );
			$is_account_disconnected      = false;
			$is_account_deleted_from_site = false;

			$result = array(
				'disconnected' => false,
				'message'      => '',
			);

			if ( ! empty( $stripe_user_id ) ) {
				$stripe_connect_api = is_callable( array( 'AFWC_Stripe_Connect', 'get_instance' ) ) ? AFWC_Stripe_Connect::get_instance() : null;
				$stripe_object      = ( ! empty( $stripe_connect_api ) && is_callable( array( $stripe_connect_api, 'deauthorize_account' ) ) ) ? $stripe_connect_api->deauthorize_account( $stripe_user_id ) : null;

				if ( ! is_null( $stripe_object ) && ( $stripe_object instanceof Stripe\StripeObject || $stripe_object instanceof \Stripe\Exception\OAuth\InvalidClientException ) ) {
					$is_account_disconnected = true;
				} else {
					$result['message'] = _x( 'A problem occurred while trying to disconnect from the Stripe Connect.', 'Error message when trying to disconnect account from Stripe Connect', 'affiliate-for-woocommerce' );
				}
			}

			if ( $is_account_disconnected ) {
				$is_account_deleted_from_site = delete_user_meta( $user_id, 'afwc_stripe_user_id' );
				$account_deleted_access_token = delete_user_meta( $user_id, 'afwc_stripe_access_token' );
				$receiver                     = array(
					'user_id'         => $user_id,
					'status_receiver' => 'disconnect',
					'stripe_id'       => '',
				);

				if ( ! $is_account_deleted_from_site && ! $account_deleted_access_token ) {
					$result['message'] = _x( 'A problem occurred while trying to disconnect.', 'Error message when trying to disconnect from page', 'affiliate-for-woocommerce' );
				}
			}

			if ( $is_account_deleted_from_site && $is_account_disconnected ) {
				$result['disconnected'] = true;
				$result['message']      = _x( 'Your account has been disconnected', 'Success message when Stripe account connect is successful', 'affiliate-for-woocommerce' );
			}

			/**
			 * Action triggered after disconnecting with Stripe.
			 *
			 * @param int    $user_id        User ID.
			 * @param string $stripe_user_id Stripe User ID.
			 * @param array  $result         Result array.
			 *
			 * @since 8.9.0
			 */
			do_action( 'afwc_after_disconnect_with_stripe', $user_id, $stripe_user_id, $result );

			return $result;
		}

		/**
		 * Disconnect user from My Account page
		 *
		 * @return void
		 */
		public function disconnect_account() {
			$user_id = get_current_user_id();

			$result = $this->disconnect_by_user_id( $user_id );

			wp_send_json( $result );
		}
	}

}

AFWC_Stripe_Functions::get_instance();
