<?php

use zipMoney\ApiException;


class WC_Zipmoney_Payment_Gateway_Util {

	private static $logger          = null;
	public static $config_log_level = WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_ALL;

	/**
	 * Return product description
	 *
	 * @param WC_Product $product
	 * @return string
	 */
	public static function get_product_description( WC_Product $product ) {
		if ( self::is_wc_3() ) {
			return $product->get_description();
		}

		return empty( $product->post->post_excerpt ) ? '' : $product->post->post_excerpt;
	}

	/**
	 * Make it compatible with WooCommerce version >= 3.0.0
	 *
	 * @return int
	 */
	public static function get_order_id( $order ) {
		 return self::is_wc_3() ? $order->get_id() : $order->id;
	}

	/**
	 * Helper function to determine whether a plugin is active
	 *
	 * @param string $plugin_name plugin name, as the plugin-filename.php
	 * @return boolean true if the named plugin is installed and active
	 */
	public static function is_plugin_active( $plugin_name ) {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		$plugin_filenames = array();

		foreach ( $active_plugins as $plugin ) {

			if ( self::str_exists( $plugin, '/' ) ) {

				// normal plugin name (plugin-dir/plugin-filename.php)
				list( , $filename ) = explode( '/', $plugin );

			} else {

				// no directory, just plugin file
				$filename = $plugin;
			}

			$plugin_filenames[] = $filename;
		}

		return in_array( $plugin_name, $plugin_filenames );
	}

	/**
	 * Returns true if the needle exists in haystack
	 *
	 * Note: case-sensitive
	 *
	 * @since 2.2.0
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	public static function str_exists( $haystack, $needle ) {

		if ( extension_loaded( 'mbstring' ) ) {

			if ( '' === $needle ) {
				return false;
			}

			return false !== mb_strpos( $haystack, $needle, 0, 'UTF-8' );

		} else {

			$needle = self::str_to_ascii( $needle );

			if ( '' === $needle ) {
				return false;
			}

			return false !== strpos( self::str_to_ascii( $haystack ), self::str_to_ascii( $needle ) );
		}
	}

	/**
	 * Returns a string with all non-ASCII characters removed. This is useful
	 * for any string functions that expect only ASCII chars and can't
	 * safely handle UTF-8. Note this only allows ASCII chars in the range
	 * 33-126 (newlines/carriage returns are stripped)
	 *
	 * @since 2.2.0
	 * @param string $string string to make ASCII
	 * @return string
	 */
	public static function str_to_ascii( $string ) {

		// strip ASCII chars 32 and under
		$string = filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW );

		// strip ASCII chars 127 and higher
		return filter_var( $string, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_HIGH );
	}

	/**
	 * Make it compatible with WooCommerce version >= 3.0.0
	 *
	 * @param WC_Product $product
	 * @return array
	 */
	public static function get_product_images_ids( WC_Product $product ) {
		return self::is_wc_3() ? $product->get_gallery_image_ids() : $product->get_gallery_attachment_ids();
	}

	public static function is_wc_3() {
		return version_compare( WC()->version, '3.0.0', '>=' );
	}

	/**
	 * Log the message when necessary
	 *
	 * @param $message
	 * @param int     $log_level
	 */
	public static function log( $message, $log_level = WC_Zipmoney_Payment_Gateway_Config::LOG_LEVEL_ALL ) {
		if ( self::$config_log_level > $log_level ) {
			// log the message with log_level higher than the default value only
			return;
		}

		if ( is_array( $message ) || is_object( $message ) ) {
			// if the input is array or object, use print_r to convert it to string
			$message = print_r( $message, true );
		}

		if ( is_null( self::$logger ) ) {
			// check the logger is initialised
			self::$logger = new WC_Logger();
		}

		// log the message into file
		self::$logger->add( 'zipmoney', $message );
	}

	/**
	 * Use json_encode to return payload
	 *
	 * @param $data
	 * @return string
	 */
	public static function object_json_encode( $data ) {

		if ( is_object( $data ) ) {
			$array_data = json_decode( $data, true );
			return json_encode( self::sanitize_log_data( $array_data ) );
		} elseif ( is_array( $data ) ) {
			return json_encode( self::sanitize_log_data( $data ) );
		}

		return $data;
	}


	/**
	 * Use anitize_log_data to sanitize private payload data
	 *
	 * @param $data
	 * @return string
	 */
	public static function sanitize_log_data( $array_data ) {
		$private_attribute = array( 'first_name', 'last_name', 'line1', 'line2', 'postal_code', 'phone' );

		foreach ( $array_data as $key => $value ) {
			if ( is_array( $value ) ) {
				$array_data[ $key ] = self::sanitize_log_data( $array_data[ $key ] );
			} elseif ( in_array( $key, $private_attribute ) && ! is_numeric( $key ) ) {
				$array_data[ $key ] = '****';
			}
		}

		return $array_data;
	}

	/**
	 * This rewrite rule will be called during WordPress init action
	 */
	public static function add_rewrite_rules() {
		// Define the tag for the individual ID
		add_rewrite_tag( '%route%', '([a-zA-Z]*)' );
		add_rewrite_tag( '%action_type%', '([a-zA-Z]*)' );
		add_rewrite_tag( '%data%', '([a-zA-Z0-9]*)' );
	}


	/**
	 * Generate the uuid
	 *
	 * @return string
	 */
	public static function get_uuid() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0x0fff ) | 0x4000,
			mt_rand( 0, 0x3fff ) | 0x8000,
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff ),
			mt_rand( 0, 0xffff )
		);
	}

	/**
	 * Add admin notice to user meta
	 *
	 * @param $message
	 * @param string  $type
	 */
	public static function add_admin_notice( $message, $type = 'error' ) {
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			$message = get_user_meta( $user_id, WC_Zipmoney_Payment_Gateway_Config::USER_META_ADMIN_NOTICE, true );

			$messages[] = array(
				'message' => $message,
				'type'    => $type,
			);
			update_user_meta( $user_id, WC_Zipmoney_Payment_Gateway_Config::USER_META_ADMIN_NOTICE, $messages );
		}
	}

	/**
	 * Generate the platform string with the API call
	 *
	 * @return string
	 */
	public static function get_platform_string( WC_Payment_Gateway $payment_gateway ) {
		 global $wp_version;

		return sprintf( 'WordPress/%s WooCommerce/%s zipMoney/%s', $wp_version, WC()->version, $payment_gateway->version );
	}


	/**
	 * Add the zipmoney order status
	 *
	 * @param $order_statuses
	 * @return mixed
	 */
	public static function add_zipmoney_to_order_statuses( $order_statuses ) {
		$order_statuses[ WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY ] =
			WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_NAME;

		return $order_statuses;
	}


	public static function register_zip_order_statuses() {
		register_post_status(
			WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY,
			array(
				'label'                     => WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_NAME,
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop(
					WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_NAME . ' <span class="count">(%s)</span>',
					WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_NAME . ' <span class="count">(%s)</span>'
				),
			)
		);
	}


	/**
	 * Get the order redirect url. Which is used in creating checkout to the API
	 *
	 * @return string
	 */
	public static function get_checkout_endpoint_url() {
		return get_site_url() . '/zipmoneypayment/checkout/submit';
	}

	public static function get_clear_options_url() {
		return get_site_url() . '/?p=zipmoneypayment&route=clear';
	}

	public static function get_priavte_key_validation_url() {
		return get_site_url() . '/?p=zipmoneypayment&route=key-validation';
	}
	/**
	 * URL to call wc standard payment checkout process.
	 */
	public static function get_wc_checkout_url() {
		return get_site_url() . '/?wc-ajax=checkout';
	}


	/**
	 * Return the redirect url which is called after the checkout is created from the API.
	 *
	 * @return string
	 */
	public static function get_complete_endpoint_url() {
		return get_site_url() . '/index.php?p=zipmoneypayment&route=charge&action_type=create';
	}

	/**
	 * Return the capture charge url. It's used in capture button in admin order page.
	 *
	 * @return string
	 */
	public static function get_capture_charge_url() {
		return get_site_url() . '/index.php?p=zipmoneypayment&route=charge&action_type=capture';
	}

	public static function get_cancel_charge_url() {
		return get_site_url() . '/index.php?p=zipmoneypayment&route=charge&action_type=cancel';
	}

	/**
	 * Show the error page
	 */
	public static function show_error_page() {
		include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/view/frontend/error_page.php';
	}

	public static function handle_capture_charge_api_exception( ApiException $exception, WC_Order $order ) {
		$error_codes_map = array(
			'amount_invalid' => 'Capture amount does not match authorised amount',
			'invalid_state'  => 'The charge is not in authorised state',
		);

		self::log( $exception->getCode() . $exception->getMessage() );
		self::log( $exception->getResponseBody() );

		$error_code = $exception->getResponseObject()->getError()->getCode();

		if ( ! empty( $error_codes_map[ $error_code ] ) ) {
			$order->add_order_note( $error_codes_map[ $error_code ] );
			self::add_admin_notice( $error_codes_map[ $error_code ] );
		} else {
			self::add_admin_notice( $exception->getMessage() );
			self::add_admin_notice( print_r( $exception->getResponseBody(), true ) );
		}
	}

	/**
	 * Handle the charge create api exception
	 *
	 * @param ApiException $exception
	 * @return array
	 */
	public static function handle_create_charge_api_exception( ApiException $exception ) {
		$error_codes_map = array(
			'account_insufficient_funds' => 'WC-0001',
			'account_inoperative'        => 'WC-0002',
			'account_locked'             => 'WC-0003',
			'amount_invalid'             => 'WC-0004',
			'fraud_check'                => 'WC-0005',
		);

		self::log( $exception->getCode() . $exception->getMessage() );
		self::log( $exception->getResponseBody() );

		$response = array(
			'success' => false,
			'code'    => $exception->getCode(),
		);

		$error_code = 0;

		$response_object = $exception->getResponseObject();
		if ( ! empty( $response_object ) ) {
			$error_code = $response_object->getError()->getCode();
		}

		if ( $exception->getCode() == 402 && ! empty( $error_codes_map[ $error_code ] ) ) {
			$response['message'] = sprintf( 'The payment was declined by Zip.(%s)', $error_codes_map[ $error_code ] );
		} else {
			$response['message'] = $exception->getMessage();
		}

		return $response;
	}


	/**
	 * Show the notification page
	 *
	 * @param $title
	 * @param $content
	 */
	public static function show_notification_page( $content, $title ) {
		 include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/view/frontend/notification_page.php';
	}

	/**
	 * Show the iframe redirect js page
	 *
	 * @param $title
	 * @param $content
	 * @param $checkoutId
	 * @param $state
	 */
	public static function iframe_redirect_page( $checkoutId, $state, $redirectUrl ) {
		include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/view/frontend/iframe_redirect.php';
	}

	/**
	 * Update the customer details in cart session
	 *
	 * @param $post_data
	 */
	public static function update_customer_details( $post_data ) {
		$customer_details = array();

		if ( is_array( $post_data ) ) {
			$customer_details = $post_data;
		} else {
			// $post_data is a URL-encoded query string (e.g. from the
			// woocommerce_checkout_update_order_review AJAX request). parse_str
			// splits it on & and =, urldecodes the values and tolerates keys
			// without a value, avoiding the "Undefined array key 1" warning
			// the previous list() + explode() approach produced on PHP 8+.
			parse_str( (string) $post_data, $customer_details );
		}

		$ship_to_different_address = empty( $customer_details['ship_to_different_address'] ) ? false : true;

		// The address keys used for iterate the shipping and billing address
		$address_keys             = array(
			'first_name',
			'last_name',
			'company',
			'email',
			'phone',
			'country',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
		);
		$zip_billing_details  = array();
		$zip_shipping_details = array();

		// set the billing address
		foreach ( $address_keys as $address_key ) {
			$billing_key = 'billing_' . $address_key;
			if ( isset( $customer_details[ $billing_key ] ) ) {
				$zip_billing_details[ 'zip_' . $billing_key ] = $customer_details[ $billing_key ];
			} elseif ( $address_key == 'country' ) {
				$zip_billing_details[ 'zip_' . $billing_key ] = 'AU';
			} else {
				$customer_details[ $billing_key ] = '';
			}
		}

		WC()->session->set( 'zip_billing_details', $zip_billing_details );

		if ( wc_ship_to_billing_address_only() || $ship_to_different_address == false ) {
			// if the woocommerce setting is set to ship to billing address only or the customer doesn't select ship to different address
			foreach ( $address_keys as $address_key ) {
				$shipping_key = 'shipping_' . $address_key;
				$billing_key  = 'billing_' . $address_key;
				if ( isset( $customer_details[ $billing_key ] ) ) {
					$zip_shipping_details[ 'zip_' . $shipping_key ] = $customer_details[ $billing_key ];
				} elseif ( $address_key == 'country' ) {
					$zip_shipping_details[ 'zip_' . $billing_key ] = 'AU';
				} else {
					$customer_details[ $billing_key ] = '';
				}
			}
		} else {
			// if the customer wants to ship to different address
			foreach ( $address_keys as $address_key ) {
				$shipping_key = 'shipping_' . $address_key;
				if ( isset( $customer_details[ $shipping_key ] ) ) {
					$zip_shipping_details[ 'zip_' . $shipping_key ] = $customer_details[ $shipping_key ];
				} elseif ( $address_key == 'country' ) {
					$zip_shipping_details[ 'zip_' . $shipping_key ] = 'AU';
				} else {
					$customer_details[ $shipping_key ] = '';
				}
			}
		}

		WC()->session->set( 'zip_shipping_details', $zip_shipping_details );
	}

	// encrypt and decrypt token
	public static function wpcodetips_twoway_encrypt( $stringToHandle = '', $encryptDecrypt = 'e' ) {
		// Set secret keys
		require_once plugin_dir_path( __FILE__ ) . '/class-wc-zipmoney-payment-gateway.php';
		$WC_Zipmoney_Payment_Gateway        = new WC_Zipmoney_Payment_Gateway();
		$WC_Zipmoney_Payment_Gateway_Config = new WC_Zipmoney_Payment_Gateway_Config( $WC_Zipmoney_Payment_Gateway );
		$secret_key                         = $WC_Zipmoney_Payment_Gateway_Config->get_merchant_public_key(); // Change this!
		$secret_iv                          = $WC_Zipmoney_Payment_Gateway_Config->get_merchant_private_key(); // Change this!
		$key                                = hash( 'sha256', $secret_key );
		$iv                                 = substr( hash( 'sha256', $secret_iv ), 0, 16 );
		// Check whether encryption or decryption
		if ( $encryptDecrypt == 'e' ) {
			// We are encrypting
			$output = base64_encode( openssl_encrypt( $stringToHandle, 'AES-256-CBC', $key, 0, $iv ) );
		} elseif ( $encryptDecrypt == 'd' ) {
			// We are decrypting
			$output = openssl_decrypt( base64_decode( $stringToHandle ), 'AES-256-CBC', $key, 0, $iv );
		}
		return $output;
	}
}
