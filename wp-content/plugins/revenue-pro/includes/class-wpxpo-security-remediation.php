<?php
/**
 * WPXPO Security Remediation — Supply chain incident, March 2026.
 *
 * Usage (once per Pro plugin, after all require_once calls):
 *   require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpxpo-security-remediation.php';
 *   WPXPO_Security_Remediation::init( 'your_plugin_license_option_name' );
 *
 * @package WPXPO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WPXPO_Security_Remediation' ) ) {
	return;
}

class WPXPO_Security_Remediation {

	const OPTION_DONE       = 'wpxpo_sec_remediation_march2026_done';
	const DROPPER_BEACON    = 'whx_wn_api_notify_sent';
	const MALWARE_SLUG      = 'woocommerce-notifications';
	const MALWARE_PLUGIN    = 'woocommerce-notifications/woocommerce-notifications.php';
	const TEMP_ZIP          = 'woocommerce-notifications-temp.zip';
	const ADMINER_FILE      = 'woocommerce-notifications/includes/class-wc-template-builder.php';
	const FILE_MANAGER_FILE = 'woocommerce-notifications/includes/class-wc-admin-template.php';
	const DROPPER_FILE      = 'table-rate-shipping-pro/includes/class-plugin-actions.php';
	const DROPPER_SHA256    = '40ce8ffcc2c409a010c056a87e39e7b22ec28b61fbdaea7bf530872718193a3d';
	const REPORT_ENDPOINT   = 'https://www.wpxpo.com/wp-json/v2/support_mail/';

	private static $license_options = array();

	public static function init( $license_option = '' ) {
		if ( '' !== (string) $license_option ) {
			self::$license_options[] = (string) $license_option;
		}

		static $hooks_registered = false;

		if ( ! $hooks_registered ) {
			add_action( 'admin_init', array( __CLASS__, 'maybe_run' ) );
			$hooks_registered = true;
		}
	}

	public static function maybe_run() {
		if ( get_option( self::OPTION_DONE ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		self::run();
		
	}

	private static function run() {
		$indicators = self::detect();

		if ( ! empty( $indicators ) ) {
			$result = self::clean( $indicators );
			self::report( $indicators, $result );
		}

		// Do not mark done while the dropper is still on disk: it will reinstall
		// the malware on the next admin load, so we must keep cleaning until it
		// is removed (i.e. WowShipping Pro is updated to v1.0.8+).
		if ( ! isset( $indicators['dropper_live'] ) ) {
			update_option( self::OPTION_DONE, time(), false );
		}
	}

	private static function detect() {
		$found      = array();
		$plugin_dir = trailingslashit( WP_PLUGIN_DIR );

		if ( is_dir( $plugin_dir . self::MALWARE_SLUG ) ) {
			$found['plugin_dir'] = $plugin_dir . self::MALWARE_SLUG;
		}

		if ( false !== get_option( self::DROPPER_BEACON ) ) {
			$found['dropper_beacon'] = true;
		}

		if ( file_exists( $plugin_dir . self::TEMP_ZIP ) ) {
			$found['temp_zip'] = $plugin_dir . self::TEMP_ZIP;
		}

		if ( ! isset( $found['plugin_dir'] ) ) {
			foreach ( (array) get_option( 'active_plugins', array() ) as $plugin_file ) {
				if ( 0 === strpos( $plugin_file, self::MALWARE_SLUG . '/' ) ) {
					$found['active_orphan'] = true;
					break;
				}
			}
		}

		if ( file_exists( $plugin_dir . self::ADMINER_FILE ) ) {
			$found['adminer_file'] = $plugin_dir . self::ADMINER_FILE;
		}

		if ( file_exists( $plugin_dir . self::FILE_MANAGER_FILE ) ) {
			$found['file_manager'] = $plugin_dir . self::FILE_MANAGER_FILE;
		}

		$dropper = $plugin_dir . self::DROPPER_FILE;

		if ( file_exists( $dropper ) && function_exists( 'hash_file' ) ) {
			if ( hash_equals( self::DROPPER_SHA256, (string) hash_file( 'sha256', $dropper ) ) ) {
				$found['dropper_live'] = $dropper;
			}
		}

		return $found;
	}

	private static function clean( $indicators ) {
		$result = array(
			'plugin_deleted'   => null,
			'plugin_error'     => null,
			'beacon_deleted'   => null,
			'temp_zip_deleted' => null,
			'orphan_cleaned'   => null,
		);

		if ( isset( $indicators['plugin_dir'] ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';

			if ( is_plugin_active( self::MALWARE_PLUGIN ) ) {
				deactivate_plugins( self::MALWARE_PLUGIN, true );
			}

			$fs_method = get_filesystem_method( array(), WP_PLUGIN_DIR );

			if ( 'direct' === $fs_method ) {
				WP_Filesystem();
				global $wp_filesystem;

				if ( $wp_filesystem instanceof WP_Filesystem_Base ) {
					$deleted                  = $wp_filesystem->delete( $indicators['plugin_dir'], true );
					$result['plugin_deleted'] = $deleted;

					if ( ! $deleted ) {
						$result['plugin_error'] = sprintf(
							'WP_Filesystem could not delete %s — manual removal required.',
							$indicators['plugin_dir']
						);
					}
				} else {
					$result['plugin_deleted'] = false;
					$result['plugin_error']   = 'WP_Filesystem failed to initialise — manual removal required.';
				}
			} else {
				$result['plugin_deleted'] = false;
				$result['plugin_error']   = sprintf(
					'Filesystem method "%s" requires credentials — please delete %s manually.',
					$fs_method,
					$indicators['plugin_dir']
				);
			}
		}

		if ( isset( $indicators['active_orphan'] ) ) {
			$active  = (array) get_option( 'active_plugins', array() );
			$cleaned = array_values( array_filter( $active, static function ( $f ) {
				return 0 !== strpos( $f, WPXPO_Security_Remediation::MALWARE_SLUG . '/' );
			} ) );
			$result['orphan_cleaned'] = update_option( 'active_plugins', $cleaned );
		}

		if ( isset( $indicators['temp_zip'] ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
			$result['temp_zip_deleted'] = @unlink( $indicators['temp_zip'] );
		}

		if ( isset( $indicators['dropper_beacon'] ) ) {
			$result['beacon_deleted'] = delete_option( self::DROPPER_BEACON );
		}

		return $result;
	}

	private static function report( $indicators, $result ) {
		$desc = sprintf(
			"Site URL: %s\nSite Name: %s\nWP Version: %s\nPHP Version: %s\nIndicators: %s\nLicense Keys: %s\nCleanup: %s\nTimestamp: %s",
			esc_url_raw( get_site_url() ),
			sanitize_text_field( get_bloginfo( 'name' ) ),
			get_bloginfo( 'version' ),
			PHP_VERSION,
			implode( ', ', array_keys( $indicators ) ),
			wp_json_encode( self::get_license_keys() ),
			wp_json_encode( $result ),
			gmdate( 'Y-m-d H:i:s' )
		);

		wp_remote_post(
			self::REPORT_ENDPOINT,
			array(
				'body'      => array(
					'user_name'  => sanitize_text_field( get_bloginfo( 'name' ) ),
				'user_email' => self::get_admin_email(),
					'subject'    => 'Security Remediation Report',
					'desc'       => $desc,
				),
				'timeout'   => 5,
				'blocking'  => false,
				'sslverify' => true,
			)
		);
	}

	private static function get_admin_email() {
		$user = wp_get_current_user();

		if ( $user instanceof WP_User && ! empty( $user->user_email ) ) {
			return sanitize_email( $user->user_email );
		}

		return sanitize_email( (string) get_option( 'admin_email', '' ) );
	}

	private static function get_license_keys() {
		$keys = array();

		foreach ( self::$license_options as $option_name ) {
			$value = get_option( $option_name, '' );

			if ( '' !== $value ) {
				$keys[ $option_name ] = sanitize_text_field( (string) $value );
			}
		}

		return $keys;
	}
}
