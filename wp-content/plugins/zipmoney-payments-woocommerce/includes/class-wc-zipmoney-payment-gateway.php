<?php

use \zipMoney\ApiException;
use zipMoney\Configuration;
use zipMoney\Model\CurrencyUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Zipmoney_Payment_Gateway extends WC_Payment_Gateway {

	const ADMIN_CSS_VERSION = '1.0';
	const FRONT_CSS_VERSION = '1.2';

	// essential settings
	public $id            = 'zipmoney';
	public $check_post_id = '';
	public $icon          = '';
	public $has_fields    = true;
	public $method_title  = 'Zip';
	// public $method_description = 'Zip Payments allows real-time credit to customers in a seamless and user friendly way.';
	public $title       = 'Zip now, pay later';
	public $description = 'Own the way you pay';

	public $version = '2.3.32';

	public $supports = array( 'products', 'refunds' );

	public $form_fields;

	public $WC_Zipmoney_Payment_Gateway_Config;
	public $WC_Zipmoney_Payment_Gateway_Widget;

	public function __construct() {
		 // load dependencies
		$this->_load_dependencies();

		// load form fields
		$this->init_form_fields();

		// load settings
		$this->init_settings();
	}

	public static function getAdminCSSVersion() {
		return self::ADMIN_CSS_VERSION;
	}

	public static function getFrontendCSSVersion() {
		return self::FRONT_CSS_VERSION;
	}

	/**
	 * Initialize the web hook
	 */
	private function _init_hooks() {
		add_action( 'init', array( 'WC_Zipmoney_Payment_Gateway_Util', 'add_rewrite_rules' ) );
		add_action( 'init', array( 'WC_Zipmoney_Payment_Gateway_Util', 'register_zip_order_statuses' ) );
		// add the zipmoney status
		add_filter( 'wc_order_statuses', array( 'WC_Zipmoney_Payment_Gateway_Util', 'add_zipmoney_to_order_statuses' ) );

		add_action( 'parse_request', array( $this, 'process_zipmoney_actions' ) );

		// have some checking
		add_action( 'admin_notices', array( $this, 'check_requirement' ) );

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

		$this->WC_Zipmoney_Payment_Gateway_Widget->init_hooks();
		add_action( 'admin_notices', array( $this, 'invalid_key_error_notice' ) );
		add_action( 'admin_notices', array( $this, 'show_notices' ) );
		add_filter( 'woocommerce_order_get_payment_method_title', array( $this, 'zip_order_payment_title' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );
		add_action( 'wp_footer', array( $this, 'update_zip_session' ), 99 );
	}
	// when zip private key is not valid then show this error in admin
	public function invalid_key_error_notice( $display = false ) {
		if ( $display ) {

			echo '<div class="error" style="color: red;"><p><strong>' . __( 'Error:', 'zippayment' ) . '</strong>' . __( ' Zip private key is invalid.', 'zippayment' ) . '</p></div>';
		}
	}

	public function get_title() {
      return __( 'Zip now, pay later', 'zippayment' );
	}

	public function zip_order_payment_title( $title, $order ) {
		if ( $order->get_payment_method() == 'zipmoney' ) {
			return strip_tags( __( 'Zip now, pay later', 'zippayment' ) );
		}
		return $title;
	}

	private function _load_dependencies() {
		 require_once plugin_dir_path( dirname( __FILE__ ) ) . '/includes/compatibility/class-wc-core-compatibility.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . '/includes/compatibility/abstract-wc-data-compatibility.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . '/includes/compatibility/class-wc-order-compatibility.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-zipmoney-payment-gateway-config.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-zipmoney-payment-gateway-widget.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wc-zipmoney-payment-gateway-util.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/controller/class-wc-zipmoney-payment-abstract-controller.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/controller/class-wc-zipmoney-payment-checkout-controller.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/controller/class-wc-zipmoney-payment-charge-controller.php';

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/api/class-wc-zipmoney-payment-gateway-api-abstract.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/api/class-wc-zipmoney-payment-gateway-api-checkout.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/api/class-wc-zipmoney-payment-gateway-api-charge.php';
	}

	/**
	 * Return the form fields array
	 */
	public function init_form_fields() {
		$this->form_fields = WC_Zipmoney_Payment_Gateway_Config::get_admin_form_fields();
	}

	public function init_settings() {
		parent::init_settings();
		// $this->getHealthResult();
		$this->title = $this->get_option( WC_Zipmoney_Payment_Gateway_Config::CONFIG_TITLE, '' );
		$this->icon  = WC_Zipmoney_Payment_Gateway_Config::LOGO_ZIP;
	}

	/**
	 * Print the admin options fields
	 */
	public function admin_options() {
		// this variable will be used in the include php file

		include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/view/backend/admin_options.php';
	}

	public function getAdminJsUrl() {
		$time = date( 'YmdHi' );
		return plugin_dir_url( __dir__ ) . 'assets/js/admin_options.js?v=' . $time;
	}

	/**
	 * Add the hash api key process into admin option processing
	 *
	 * @return bool
	 */
	public function process_admin_options() {
		$result = parent::process_admin_options();

		$this->WC_Zipmoney_Payment_Gateway_Config->hash_api_key( $this );
		// update the log level
		WC_Zipmoney_Payment_Gateway_Util::$config_log_level = $this->get_option( WC_Zipmoney_Payment_Gateway_Config::CONFIG_LOGGING_LEVEL );
		if ( $this->get_option( WC_Zipmoney_Payment_Gateway_Config::CONFIG_ENABLED ) ) {
			$environment = $this->WC_Zipmoney_Payment_Gateway_Config->get_environment();
			$privateKey  = $this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_private_key();
			$response    = $this->key_validation( $environment, $privateKey );
			if ( $response['error'] && ( $response['code'] == '403' ) || $response['code'] == '401' ) {
				$this->invalid_key_error_notice( true );
			}
		}
		return $result;
	}

	// Zip money private key validation check
	public function key_validation( $environment, $privateKey ) {
		$config = Configuration::getDefaultConfiguration();
		$config->setEnvironment( $environment );
		$host = $config->getHost();
		$config->setApiKey( 'Authorization', 'Bearer ' . $privateKey );
		$config->setCurlTimeout( 30 );
		$headerParams['Idempotency-Key'] = WC_Zipmoney_Payment_Gateway_Util::get_uuid();
		$headers                         = array(
			'Zip-Version'     => '2017-03-01',
			'Accept'          => 'application/json',
			'Authorization'   =>
			$config->getApiKeyPrefix( 'Authorization' ) .
				'' .
				$config->getApiKey( 'Authorization' ),
			'Content-Type'    => 'application/json',
			'Idempotency-Key' => WC_Zipmoney_Payment_Gateway_Util::get_uuid(),
		);
		$url                             = $host . '/me';
		$isAuEndpoint                    = false;
		// check api key length if it is more than or equal 50 then call SMI merchant info endpoint
		// otherwise call checkout get api endpoint only for Australia
		if ( strlen( $privateKey ) <= 50 ) {
			$checkoutId   = 'co_healthcheck';
			$url          = $host . '/checkouts/' . $checkoutId;
			$isAuEndpoint = true;
		}
		$response     = wp_remote_get(
			$url,
			array(
				'headers'    => $headers,
				'timeout'    => 300,
				'user-agent' => $config->getUserAgent(),
			)
		);
		$responseCode = $response['response']['code'];
		$htmlMessage  = '';
		if ( $responseCode == '200' && $isAuEndpoint == false ) {
			$body        = json_decode( $response['body'] );
			$message     = ucfirst( $environment ) . __( ' private key is valid for ', 'zippayment' ) . $body->name;
			$htmlMessage = '<div class="notice notice-success notice-alt is-dismissible zip-notice"><p>' . $message . '</p>';
			$regions     = $body->regions;
			// var_dump($regions);
			if ( $regions ) {
				$regionList   = '<p>' . __( 'key is valid for below regions ', 'zippayment' ) . ucfirst( $environment ) . ' environment:<br>';
				$countriesObj = new WC_Countries();
				$allCountries = $countriesObj->get_countries();
				foreach ( $regions as $key => $value ) {
					if ( array_key_exists( strtoupper( $regions[ $key ] ), $allCountries ) ) {
						$regionList .= $allCountries[ strtoupper( $regions[ $key ] ) ] . '<br>';
					} elseif ( $value == 'uk' ) {
						$regionList .= 'United Kingdom <br>';
					} elseif ( $value == 'twisto' ) {
						$regionList .= 'Poland <br>';
						$regionList .= 'Czech Republic <br>';
					}
				}
				$htmlMessage .= $regionList . '</p>';
			}
			$htmlMessage .= '</div>';
		}
		if ( ( $responseCode == '404' || $responseCode == '200' ) && $isAuEndpoint == true ) {
			$message     = ucfirst( $environment ) . __( ' private key valid for Australia region.', 'zippayment' );
			$htmlMessage = '<div class="notice notice-success notice-alt is-dismissible zip-notice"><p>' . $message . '</p></div>';
		}
		$result = $result = array(
			'error'   => false,
			'code'    => 200,
			'message' => wp_kses_post( $htmlMessage ),
		);
		if ( ! is_wp_error( $response ) ) {
			if ( $responseCode == '401' || $responseCode == '403' || ( $responseCode == '404' && $isAuEndpoint == false ) ) {
				$htmlMessage = '<div class="notice notice-error notice-alt is-dismissible zip-notice"><p>' . __( 'Invalid Zip private key. Please check with Zip.', 'zippayment' ) . '</p></div>';
				$result      = array(
					'error'   => true,
					'code'    => $responseCode,
					'message' => wp_kses_post( $htmlMessage ),
				);
			}
		} else {
			$htmlMessage = '<div class="notice notice-error notice-alt is-dismissible zip-notice"><p>' . __( 'Connection error. Please try later.', 'zippayment' ) . '</p></div>';
			$result      = array(
				'error'   => true,
				'code'    => 500,
				'message' => wp_kses_post( $htmlMessage ),
			);
		}
		return $result;
	}

	public function run() {
		 $this->WC_Zipmoney_Payment_Gateway_Config = new WC_Zipmoney_Payment_Gateway_Config( $this );
		$this->WC_Zipmoney_Payment_Gateway_Widget  = new WC_Zipmoney_Payment_Gateway_Widget( $this );

		// check the logger is enable or not
		WC_Zipmoney_Payment_Gateway_Util::$config_log_level =
			$this->WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key( WC_Zipmoney_Payment_Gateway_Config::CONFIG_LOGGING_LEVEL );

		// load the hooks
		$this->_init_hooks();
	}


	public function show_notices() {
		if ( is_user_logged_in() ) {
			$user_id  = get_current_user_id();
			$messages = get_user_meta( $user_id, WC_Zipmoney_Payment_Gateway_Config::USER_META_ADMIN_NOTICE, true );

			if ( ! empty( $messages ) ) {
				foreach ( $messages as $message ) {
					printf( '<div class="notice notice-%s">%s</div>', $message['type'], $message['message'] );
				}
				// remove user meta
				update_user_meta( $user_id, WC_Zipmoney_Payment_Gateway_Config::USER_META_ADMIN_NOTICE, array() );
			}
		}
	}

	public function get_environment_status() {
		$this->WC_Zipmoney_Payment_Gateway_Config = new WC_Zipmoney_Payment_Gateway_Config( $this );

		return $this->WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key( WC_Zipmoney_Payment_Gateway_Config::CONFIG_SANDBOX );
	}

	/**
	 * get tokenisation option status from zip admin setting
	 */
	public function get_tokenisation_status() {
		 $this->WC_Zipmoney_Payment_Gateway_Config = new WC_Zipmoney_Payment_Gateway_Config( $this );

		return $this->WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key( WC_Zipmoney_Payment_Gateway_Config::CONFIG_ENABLE_TOKENISATION );
	}

	/**
	 * Check the environment meet the minimum requirement
	 */
	public function check_requirement() {
		if ( $this->WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key( WC_Zipmoney_Payment_Gateway_Config::CONFIG_ENABLED ) == false ) {
			return;
		}

		if ( version_compare( phpversion(), '8.0', '<' ) ) {
			// PHP Version
			echo '<div class="error"><p>' . sprintf( __( 'ZipMoney Error: ZipMoney requires PHP 8.0 and above. You are using version %s.', 'zippayment' ), phpversion() ) . '</p></div>';
		} elseif ( is_checkout() && ! is_ssl() ) {
			// Show message if enabled and FORCE SSL is disabled and WordPressHTTPS plugin is not detected
			echo '<div class="error"><p>' . sprintf( __( 'WARN: ZipMoney is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout may not be secure! Please enable SSL and ensure your server has a valid SSL certificate - ZipMoney will only work in sandbox mode.', 'zippayment' ), admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) . '</p></div>';
		}
	}

	/**
	 * This is the function to process the custom defined endpoint
	 *
	 * @param $wp
	 * @return bool
	 */
	public function process_zipmoney_actions( $wp ) {
		$query_vars = $wp->query_vars;

		if ( isset( $query_vars['p'] ) == false || $query_vars['p'] != 'zipmoneypayment' ) {
			return false;
		}

		if ( isset( $query_vars['route'] ) == false ) {
			return false;
		}

		WC_Zipmoney_Payment_Gateway_Util::log( 'Query vars:' . print_r( $query_vars, true ) );

		switch ( $query_vars['route'] ) {
			case 'charge':
				if ( isset( $query_vars['data'] ) == false ) {
					$query_vars['data'] = array();
				}
				$this->_handle_charge_request( $query_vars['action_type'] );
				break;
			case 'error':
				WC_Zipmoney_Payment_Gateway_Util::show_error_page();
				break;
			case 'clear':
				WC_Zipmoney_Payment_Gateway_Util::log( sanitize_text_field( $_POST ) );

				if ( ! empty( $_POST['checkout_id'] ) ) {
					delete_option( sanitize_text_field( $_POST['checkout_id'] ) );
				}
				break;
			case 'key-validation':
				$environment = sanitize_text_field( $_POST['environment'] );
				$privateKey  = sanitize_text_field( $_POST['private_key'] );
				$result      = $this->key_validation( $environment, $privateKey );
				wp_send_json( $result );
				break;
			case 'updatesession':
				// this route use for setting session for save zip account option wheither it is checked or unchecked
				$saveZipaccount = sanitize_text_field( $_POST['savezipaccount'] );
				session_status() === PHP_SESSION_ACTIVE ?: session_start();
				$_SESSION['saveZipAccount'] = $saveZipaccount;
				$result                     = $result = array(
					'code'    => 200,
					'message' => 'success',
				);
				wp_send_json( $result );
		}
	}


	/**
	 * This is standard woocommerce function to process payment, then handle checkout response and set the
	 * post_type in DB to shop_quote to hide pending order in CMS orders list.
	 *
	 * @param $order_id
	 */
	public function process_payment( $order_id ) {
		try {
			WC_Zipmoney_Payment_Gateway_Util::log( $order_id );

			$checkout_controller = new WC_Zip_Controller_Checkout_Controller( $this );

			$checkout = array();

			$checkout = $checkout_controller->create_checkout( $_POST, $order_id );
		} catch ( ApiException $exception ) {
			WC_Zipmoney_Payment_Gateway_Util::log( $exception->getCode() . $exception->getMessage() );
			WC_Zipmoney_Payment_Gateway_Util::log( $exception->getResponseBody() );

			wc_add_notice( __( 'Payment error:', 'zippayment' ) . $exception->getMessage(), 'error' );

			return array(
				'result'        => 'failure',
				'messages'      => $exception->getMessage(),
				'error_message' => $exception->getResponseBody(),
			);
		}
		if ( isset( $checkout['redirect_uri'] ) ) {

			$response = array(
				'result'      => $checkout['result'],
				'checkout_id' => $checkout['checkout_id'],
				'redirect'    => $checkout['redirect_uri'],
			);

			if ( isset( $checkout['token'] ) ) {
				$token             = $checkout['token'];
				$response['token'] = $token;
			}
			return $response;
		}
		wc_add_notice( __( 'Payment error: ', 'zippayment' ) . $checkout['message'], 'error' );

		return array(
			'result'   => 'failure',
			'messages' => $checkout['message'],
		);
	}

	/**
	 *
	 *
	 * @param int    $order_id
	 * @param null   $amount
	 * @param string $reason
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		 WC_Zipmoney_Payment_Gateway_Util::log( 'process refund', WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_INFO );

		$order = new WC_Order( $order_id );

		WC_Zipmoney_Payment_Gateway_Util::log( sprintf( 'order value: %s, amount: %s, refund: %s', $order->get_total(), $amount, $order->get_total_refunded() ) );

		$this->WC_Zipmoney_Payment_Gateway_Config       = new WC_Zipmoney_Payment_Gateway_Config( $this );
		$WC_Zipmoney_Payment_Gateway_API_Request_Charge = new WC_Zipmoney_Payment_Gateway_API_Request_Charge(
			$this,
			new \zipMoney\Api\RefundsApi()
		);

		$amount   = empty( $amount ) ? 0 : round( $amount, 2 );
		$reason   = $order_id . strtotime( 'now' );
		$currency = get_woocommerce_currency();

		return $WC_Zipmoney_Payment_Gateway_API_Request_Charge->refund_order_charge(
			$order,
			$this->WC_Zipmoney_Payment_Gateway_Config->get_merchant_private_key(),
			$amount,
			$currency,
			$reason
		);
	}

	/**
	 * handle the charge request by custom url call
	 *
	 * @param $action_type
	 */
	private function _handle_charge_request( $action_type ) {
		WC_Zipmoney_Payment_Gateway_Util::log( 'Charge process started' );

		// process the charge process
		$charge_controller = new WC_Zip_Controller_Charge_Controller( $this );

		// store the referrer
		$referrer = isset( $_SERVER['HTTP_REFERER'] ) ? sanitize_text_field( $_SERVER['HTTP_REFERER'] ) : '';

		WC_Zipmoney_Payment_Gateway_Util::log( 'Charge process after controller' );
		switch ( $action_type ) {
			case 'create':
				$currency       = get_option( 'woocommerce_currency' );
				$is_iframe_flow = $this->WC_Zipmoney_Payment_Gateway_Config->is_it_iframe_flow();
				if ( isset( $_GET['iframe'] ) && $currency != CurrencyUtil::CURRENCY_AUD && $is_iframe_flow ) {
					$checkoutId  = sanitize_text_field( $_GET['checkoutId'] );
					$state       = sanitize_text_field( $_GET['result'] );
					$redirectUrl = WC_Zipmoney_Payment_Gateway_Util::get_complete_endpoint_url()
						. '&checkoutId=' . $checkoutId
						. '&result=' . $state;
					WC_Zipmoney_Payment_Gateway_Util::iframe_redirect_page( $checkoutId, $state, $redirectUrl );
					exit;
				}
				$result = $charge_controller->create_charge( $_GET );

				WC_Zipmoney_Payment_Gateway_Util::log( 'Charge process after controller' );
				if ( $result['result'] == true ) {
					// successfully create charge
					wp_redirect( $this->get_return_url( $result['order'] ) );
					exit;
				}

				if ( ! empty( $result['redirect_url'] ) ) {
					// if it contains redirect url
					wp_redirect( esc_url( $result['redirect_url'] ) );
					exit;
				}

				WC_Zipmoney_Payment_Gateway_Util::show_notification_page( $result['title'], $result['content'] );
				exit;
				break;
			case 'capture':
				$charge_controller->capture_charge( sanitize_text_field( $_POST['zip_order_id'] ) );
				wp_redirect( $referrer );
				exit;
				break;
			case 'cancel':
				$charge_controller->cancel_charge( sanitize_text_field( $_POST['zip_order_id'] ) );
				wp_redirect( $referrer );
				exit;
				break;
		}
	}
	/**
	 *  check tokenisation is enable, user logged in and currency is AUD
	 */
	public function showSaveAccountInCheckout() {
		$currency = get_option( 'woocommerce_currency' );
		if ( is_user_logged_in() && $this->get_tokenisation_status() && $currency == CurrencyUtil::CURRENCY_AUD ) {
			return true;
		}
		return false;
	}

	/**
	 * check tokenisation is enable, user logged in and currency is AUD
	 * as well as checking saveZipAccount is checked or not
	 */
	public function doTokenisation() {
		session_status() === PHP_SESSION_ACTIVE ?: session_start();
		if ( isset( $_SESSION['saveZipAccount'] ) && filter_var( $_SESSION['saveZipAccount'], FILTER_VALIDATE_BOOLEAN ) && $this->showSaveAccountInCheckout() ) {
			return true;
		}
		return false;
	}

	/**
	 * check customer has token
	 */
	public function customerHasToken() {
		global $wpdb;
		$current_user = wp_get_current_user();
		$uid          = $current_user->ID;
		$tokenTable   = $wpdb->prefix . 'zip_tokenisation';
		$result       = $wpdb->get_results( "SELECT * FROM $tokenTable WHERE `customer_id` = $uid" );
		foreach ( $result as $customerToken ) {
			return $customerToken->token;
		}
		return false;
	}
	/**
	 * update session when customer check or uncheck Save Zip account in checkout page
	 */
    public function update_zip_session() {
        if (is_checkout()) {
            $url = home_url();
            ?>

            <script type="text/javascript">
              function Check(value) {
                let formData = new FormData();
                var url = '<?php echo esc_url($url) . '/?p=zipmoneypayment&route=updatesession'; ?>';
                formData.append("savezipaccount", value.checked);
                const requestOptions = {
                  method: "POST",
                  body: formData
                };
                fetch(url, requestOptions)
                  .then(response => response.json());
              };
            </script>
            <?php
        }
    }
}
