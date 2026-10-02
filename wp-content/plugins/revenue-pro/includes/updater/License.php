<?php //phpcs:ignore
namespace RevenuePro;

defined( 'ABSPATH' ) || exit;

/**
 * License Class
 */
class License {

	/**
	 * Contains sever url.
	 *
	 * @var string
	 */
	private $server_url = 'https://account.wpxpo.com';

	/**
	 * Contains Item ID.
	 *
	 * @var integer
	 */
	private $item_id = 57783;
	/**
	 * Contains Product Name.
	 *
	 * @var string
	 */
	private $name = 'WowRevenue Pro';
	/**
	 * Contains Pro Version.
	 *
	 * @var string
	 */
	private $version = REVENUE_PRO_VER;
	/**
	 * Contains Plugin Slug.
	 *
	 * @var string
	 */
	private $slug = 'revenue-pro/revenue-pro.php';

	/**
	 * Constuctor
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'edd_license_updater' ) );
		add_filter( 'revenue_admin_localized_data', array( $this, 'admin_localized_data' ) );
		add_filter( 'revenue_pro_features_available', array( __CLASS__, 'is_valid' ) );
		add_filter( 'revenue_submenu_slugs', array( $this, 'admin_submenu' ), 10, 2 );
		add_filter( 'plugin_action_links_' . REVENUE_PRO_BASE, array( $this, 'plugin_action_links' ) );
		add_action( 'wp_ajax_edd_revenue_activate_license', array( $this, 'edd_activate_license' ) );
		add_action( 'wp_ajax_edd_revenue_get_license_data', array( $this, 'edd_get_license_data' ) );
		add_action( 'wp_ajax_edd_revenue_deactivate_license', array( $this, 'edd_deactivate_license' ) );
	}

	public static function get_license_key() {
		return (string) get_option( 'edd_revenue_license_key', '' );
	}

	public static function is_expired() {
		return 'expired' === self::get_status();
	}

	/**
	 * Last known license status as returned by the store ('valid', 'expired', 'invalid', ...).
	 *
	 * @return string Empty string when no key has ever been activated here.
	 */
	public static function get_status() {
		$data = get_option( 'edd_revenue_license_data', array() );

		if ( ! is_array( $data ) || empty( $data['license'] ) || ! is_string( $data['license'] ) ) {
			return '';
		}

		return $data['license'];
	}

	/**
	 * Whether Pro features are unlocked on this site.
	 *
	 * @return bool
	 */
	public static function is_valid() {
		return '' !== self::get_license_key() && 'valid' === self::get_status();
	}

	public static function renewal_url() {
		return add_query_arg(
			array(
				'edd_license_key' => rawurlencode( self::get_license_key() ),
				'renew'           => 1,
			),
			'https://account.wpxpo.com/checkout/'
		);
	}

	public function admin_localized_data( $data ) {
		// Every role that sees the dashboard needs the lock state, only admins see the key itself.
		$data['is_pro_active']  = self::is_valid();
		$data['license_status'] = self::get_status();

		// Free no longer looks for a companion plugin, so Pro announces itself here.
		$data['pro_ready'] = true;

		// Shop managers share the campaign dashboard but cannot administer licenses.
		if ( current_user_can( 'manage_options' ) ) {
			$data['license']    = self::get_license_key();
			$data['is_expired'] = self::is_expired();
		}
		return $data;
	}

	public function admin_submenu( $items, $slug ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $items;
		}
		if ( ! revenue()->is_whitelabel_enabled() ) {
			$items[] = array( __( 'License', 'revenue-pro' ), 'manage_options', 'admin.php?page=' . $slug . '#/license' );
		}
		if ( self::is_expired() ) {
			$items[] = array( __( 'Renew License', 'revenue-pro' ), 'manage_options', esc_url( self::renewal_url() ) );
		}
		return $items;
	}

	public function plugin_action_links( $links ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return $links;
		}
		$links['license'] = '<a href="' . esc_url( admin_url( 'admin.php?page=' . revenue()->get_admin_menu_slug() . '#/license' ) ) . '">' . esc_html__( 'License', 'revenue-pro' ) . '</a>';
		if ( self::is_expired() ) {
			$links['renew_license'] = '<a href="' . esc_url( self::renewal_url() ) . '">' . esc_html__( 'Renew License', 'revenue-pro' ) . '</a>';
		}
		return $links;
	}

	/**
	 * EDD License Updater.
	 *
	 * @return void
	 */
	public function edd_license_updater() {

		if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
			require_once REVENUE_PRO_PATH . 'includes/updater/EDD_SL_Plugin_Updater.php';
		}

		$license_key = trim( self::get_license_key() );

		$edd_updater = new \EDD_SL_Plugin_Updater(
			$this->server_url,
			$this->slug,
			array(
				'version' => $this->version,
				'license' => $license_key,
				'item_id' => $this->item_id,
				'author'  => $this->name,
				'url'     => home_url(),
				'beta'    => false,
			)
		);
	}


	/**
	 * Activate License.
	 *
	 * @return void|string
	 */
	public function edd_activate_license() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'data' => __( 'You do not have permission to manage licenses.', 'revenue-pro' ) ), 403 );
			return;
		}

		// Verify the nonce for security
		if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_key( $_POST['security'] ), 'revenue-dashboard' ) ) {
			wp_send_json_error( array( 'data' => 'Failed due to security check!' ) );
			return;
		}

		// Initialize response data
		$res_data = array(
			'status' => false,
			'data'   => 'Failed!',
		);

		// Sanitize and retrieve the license key
		$license = isset( $_POST['license_key'] ) ? sanitize_text_field( $_POST['license_key'] ) : '';

		if ( ! empty( $license ) ) {
			// Update the license key in options
			update_option( 'edd_revenue_license_key', $license );

			// Prepare API request parameters
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_id'    => $this->item_id,
				'url'        => home_url(),
			);

			// Send the request to the license server
			$response = wp_remote_post(
				$this->server_url,
				array(
					'timeout'   => 50,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);

			// Check for errors in the response
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
				$res_data['data'] = is_wp_error( $response ) ? $response->get_error_message() : __( 'An error occurred, please try again.', 'revenue-pro' );
			} else {
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				// Handle different error scenarios from the license activation response
				if ( false === $license_data->success ) {
					switch ( $license_data->error ) {
						case 'expired':
							$res_data['data'] = sprintf(
								/* translators: %s: license expiration date. */
								__( 'Your license key expired on %s.', 'revenue-pro' ),
								date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, time() ) )
							);
							break;
						case 'revoked':
							$res_data['data'] = __( 'Your license key has been disabled.', 'revenue-pro' );
							break;
						case 'missing':
							$res_data['data'] = __( 'Invalid license.', 'revenue-pro' );
							break;
						case 'invalid':
						case 'site_inactive':
							$res_data['data'] = __( 'Your license is not active for this URL.', 'revenue-pro' );
							break;
						case 'item_name_mismatch':
							$res_data['data'] = __( 'This appears to be an invalid license key.', 'revenue-pro' );
							break;
						case 'no_activations_left':
							$res_data['data'] = __( 'Your license key has reached its activation limit.', 'revenue-pro' );
							break;
						default:
							$res_data['data'] = __( 'An error occurred, please try again.', 'revenue-pro' );
							break;
					}
				} else {
					// License successfully activated
					$res_data = array(
						'status' => true,
						'data'   => __( 'License Activated!', 'revenue-pro' ),
					);
				}

				$res_data['license_data'] = $this->format_license_data_response( $license_data );

				// Store the license data in the options
				update_option( 'edd_revenue_license_data', (array) $license_data );
			}
		}
		// Return the response as JSON
		wp_send_json( $res_data );
	}

	/**
	 * Deactivate License.
	 *
	 * @return void
	 */
	public function edd_deactivate_license() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'data' => __( 'You do not have permission to manage licenses.', 'revenue-pro' ) ), 403 );
			return;
		}

		// Verify the nonce for security
		if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_key( $_POST['security'] ), 'revenue-dashboard' ) ) {
			wp_send_json_error( array( 'data' => 'Failed due to security check!' ) );
			return;
		}

		// Check if deactivation is requested
		if ( ! isset( $_POST['deactivate'] ) || 'yes' !== $_POST['deactivate'] ) {
			wp_send_json_error( array( 'data' => 'Deactivation not confirmed!' ) );
			return;
		}

		// Initialize response data
		$ajax_response = array(
			'status' => false,
			'data'   => 'Failed!',
		);

		// Retrieve the stored license key
		$license = get_option( 'edd_revenue_license_key', '' );

		// Prepare API request parameters
		$api_params = array(
			'edd_action' => 'deactivate_license',
			'license'    => $license,
			'item_id'    => $this->item_id,
			'url'        => home_url(),
		);

		// Send the request to the license server
		$response = wp_remote_post(
			$this->server_url,
			array(
				'timeout'   => 50,
				'sslverify' => true,
				'body'      => $api_params,
			)
		);

		// Check for errors in the response
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$ajax_response['data'] = is_wp_error( $response ) ? $response->get_error_message() : __( 'An error occurred, please try again.', 'revenue-pro' );
		} else {
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			// Handle possible errors returned by the API
			if ( false === $license_data->success ) {
				switch ( $license_data->error ) {
					case 'expired':
						$ajax_response['data'] = sprintf(
							/* translators: %s: license expiration date. */
							__( 'Your license key expired on %s.', 'revenue-pro' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, time() ) )
						);
						break;
					case 'revoked':
						$ajax_response['data'] = __( 'Your license key has been disabled.', 'revenue-pro' );
						break;
					case 'missing':
						$ajax_response['data'] = __( 'Invalid license.', 'revenue-pro' );
						break;
					case 'invalid':
					case 'site_inactive':
						$ajax_response['data'] = __( 'Your license is not active for this URL.', 'revenue-pro' );
						break;
					case 'item_name_mismatch':
						$ajax_response['data'] = __( 'This appears to be an invalid license key.', 'revenue-pro' );
						break;
					case 'no_activations_left':
						$ajax_response['data'] = __( 'Your license key has reached its activation limit.', 'revenue-pro' );
						break;
					default:
						$ajax_response['data'] = __( 'An error occurred, please try again.', 'revenue-pro' );
						break;
				}
			} else {
				// License successfully deactivated
				$ajax_response = array(
					'status' => true,
					'data'   => __( 'License Deactivated.', 'revenue-pro' ),
				);

				// Check the license status
				$this->edd_check_license( true );
			}

			$ajax_response['license_data'] = $this->format_license_data_response( $license_data );

			// Update the license data in the options
			update_option( 'edd_revenue_license_data', (array) $license_data );
		}

		// Return the response as JSON
		wp_send_json( $ajax_response );
	}


	/**
	 * Check License Status.
	 *
	 * @param bool $force Force license check.
	 * @return void
	 */
	public function edd_check_license( $force = false ) {
		// Generate a unique transient name based on the site URL
		$site_hash      = md5( home_url() );
		$transient_name = 'edd_revenue_' . $site_hash;
		$check_interval = DAY_IN_SECONDS; // 1 day

		// Check if license status has already been checked or if forced
		if ( ! get_transient( $transient_name ) || $force ) {
			// Retrieve the stored license key
			$license = get_option( 'edd_revenue_license_key', '' );

			// Prepare API request parameters
			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => $license,
				'item_id'    => $this->item_id,
				'url'        => home_url(),
			);

			// Send the request to the license server
			$response = wp_remote_post(
				$this->server_url,
				array(
					'timeout'   => 50,
					'sslverify' => true,
					'body'      => $api_params,
				)
			);

			// Set transient to avoid checking license status frequently
			set_transient( $transient_name, true, $check_interval );

			// Check for errors in the response
			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				// Retrieve and store the license data
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );
				update_option( 'edd_revenue_license_data', (array) $license_data );
			}
		}
	}


	public function edd_get_license_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'data' => __( 'You do not have permission to manage licenses.', 'revenue-pro' ) ), 403 );
			return;
		}

		// Verify the nonce for security
		if ( ! isset( $_POST['security'] ) || ! wp_verify_nonce( sanitize_key( $_POST['security'] ), 'revenue-dashboard' ) ) {
			wp_send_json_error( array( 'data' => 'Failed due to security check!' ) );
			return;
		}

		$this->edd_check_license();

		$data = get_option( 'edd_revenue_license_data', array() );

		wp_send_json_success( array( 'license_data' => $this->format_license_data_response( $data ) ) );
	}

	public function format_license_data_response( $license_data = array() ) {
		$res = array();
		if ( empty( $license_data ) ) {
			$license_data = get_option( 'edd_revenue_license_data', array() );
		}

		if ( ! is_array( $license_data ) ) {
			$license_data = (array) $license_data;
		}

		if ( isset( $license_data['license'] ) ) {
			$res['license'] = $license_data['license'];
		}
		if ( isset( $license_data['success'] ) ) {
			$res['success'] = $license_data['success'];
		}

		// Peform Format
		if ( isset( $license_data['expires'] ) && 'lifetime' === $license_data['expires'] ) {
			$res['expires'] = 'Never';
		}

		if ( isset( $license_data['activations_left'] ) ) {
			if ( 'unlimited' === $license_data['activations_left'] ) {
				$res['licenseType'] = 'lifetime' === $license_data['expires'] ? 'Unlimited Sites | Lifetime' : 'Unlimited Sites | Yearly';
			}
		}

		if ( isset( $license_data['price_id'] ) ) {
			switch ( $license_data['price_id'] ) {
				case '1':
					$res['licenseType'] = '1 Site License | Yearly';
					break;
				case '2':
					$res['licenseType'] = 'Unlimited License | Yearly';
					break;
				case '3':
					$res['licenseType'] = '1 Site License | Lifetime';
					break;
				case '4':
					$res['licenseType'] = 'Unlimited Sites License | Lifetime';
					break;

				default:
					# code...
					break;
			}

			$key = get_option( 'edd_revenue_license_key' );

			if ( $key ) {
				$res['licenseKeySuffix'] = substr( get_option( 'edd_revenue_license_key' ), -4 );
			}
		}

		return $res; // Formatted License Data
	}
}
