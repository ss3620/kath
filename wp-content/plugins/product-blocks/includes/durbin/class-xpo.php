<?php

namespace WOPB\Includes\Durbin;

defined( 'ABSPATH' ) || exit;

/**
 * Core class for managing plugin actions and integrations.
 */
class Xpo {

	/**
	 * Gets license key
	 *
	 * @return string
	 */
	public static function get_lc_key() {
		return get_option( 'edd_wopb_license_key', '' );
	}

	public static function is_lc_active() {
		if ( defined( 'WOPB_PRO_VER' ) ) {
			$license_data = get_option( 'edd_wopb_license_data', array() );
			return isset( $license_data['license'] ) && 'valid' === $license_data['license'];
		}
		return false;
	}

	/**
	 * Checks if the license has expired.
	 *
	 * This method checks the stored license data in the WordPress options table
	 * and determines if the license status is set to 'expired'. It returns `true`
	 * if the license is expired, otherwise `false`.
	 *
	 * @return bool True if the license is expired, otherwise false.
	 */
	public static function is_lc_expired() {
		$license_data = get_option( 'edd_wopb_license_data', array() );
		return isset( $license_data['license'] ) && 'expired' === $license_data['license'];
	}

	/**
	 * Get Option Value bypassing cache
	 * Inspired By WordPress Core get_option
	 *
	 * @since v.1.0.7
	 * @param string  $option Option Name.
	 * @param boolean $default_value option default value.
	 * @return mixed
	 */
	public static function get_option_without_cache( $option, $default_value = false ) {
		global $wpdb;

		if ( is_scalar( $option ) ) {
			$option = trim( $option );
		}

		if ( empty( $option ) ) {
			return false;
		}

		$value = $default_value;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( is_object( $row ) ) {
			$value = $row->option_value;
		} else {
			return apply_filters( "wopb_default_option_{$option}", $default_value, $option );
		}

		return apply_filters( "wopb_option_{$option}", maybe_unserialize( $value ), $option );
	}

	/**
	 * Add option without adding to the cache
	 * Inspired By WordPress Core set_transient
	 *
	 * @since v.1.0.7
	 * @param string $option option name.
	 * @param string $value option value.
	 * @param string $autoload whether to load WordPress startup.
	 * @return bool
	 */
	public static function add_option_without_cache( $option, $value = '', $autoload = 'yes' ) {
		global $wpdb;

		if ( is_scalar( $option ) ) {
			$option = trim( $option );
		}

		if ( empty( $option ) ) {
			return false;
		}

		wp_protect_special_option( $option );

		if ( is_object( $value ) ) {
			$value = clone $value;
		}

		$value = sanitize_option( $option, $value );

		/*
		* Make sure the option doesn't already exist.
		*/

		if ( apply_filters( "wopb_default_option_{$option}", false, $option, false ) !== self::get_option_without_cache( $option ) ) {
			return false;
		}

		$serialized_value = maybe_serialize( $value );
		$autoload         = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';

		$result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! $result ) {
			return false;
		}

		return true;
	}

	/**
	 * Get Transient Value bypassing cache
	 * Inspired By WordPress Core get_transient
	 *
	 * @since v.1.0.7
	 * @param string $transient Transient Name.
	 * @return mixed
	 */
	public static function get_transient_without_cache( $transient ) {
		$transient_option  = '_transient_' . $transient;
		$transient_timeout = '_transient_timeout_' . $transient;
		$timeout           = self::get_option_without_cache( $transient_timeout );

		if ( false !== $timeout && $timeout < time() ) {
			delete_option( $transient_option );
			delete_option( $transient_timeout );
			$value = false;
		}

		if ( ! isset( $value ) ) {
			$value = self::get_option_without_cache( $transient_option );
		}

		return apply_filters( "wopb_transient_{$transient}", $value, $transient );
	}

	/**
	 * Set transient without adding to the cache
	 * Inspired By WordPress Core set_transient
	 *
	 * @since v.1.0.7
	 * @param string  $transient Transient Name.
	 * @param mixed   $value Transient Value.
	 * @param integer $expiration Time until expiration in seconds.
	 * @return bool
	 */
	public static function set_transient_without_cache( $transient, $value, $expiration = 0 ) {
		$expiration = (int) $expiration;

		$transient_timeout = '_transient_timeout_' . $transient;
		$transient_option  = '_transient_' . $transient;

		$result = false;

		if ( false === self::get_option_without_cache( $transient_option ) ) {
			$autoload = 'yes';
			if ( $expiration ) {
				$autoload = 'no';
				self::add_option_without_cache( $transient_timeout, time() + $expiration, 'no' );
			}
			$result = self::add_option_without_cache( $transient_option, $value, $autoload );
		} else {
			/*
			* If expiration is requested, but the transient has no timeout option,
			* delete, then re-create transient rather than update.
			*/
			$update = true;

			if ( $expiration ) {
				if ( false === self::get_option_without_cache( $transient_timeout ) ) {
					delete_option( $transient_option );
					self::add_option_without_cache( $transient_timeout, time() + $expiration, 'no' );
					$result = self::add_option_without_cache( $transient_option, $value, 'no' );
					$update = false;
				} else {
					update_option( $transient_timeout, time() + $expiration );
				}
			}

			if ( $update ) {
				$result = update_option( $transient_option, $value );
			}
		}

		return $result;
	}

	public static function generate_utm_link( $params = array() ) {
		$default_config = array(
			'example'                     => array(
				'source'   => 'db-wowaddons-featurename',
				'medium'   => 'block-feature',
				'campaign' => 'wowaddons-dashboard',
			),
			'menu_saved_template_go_pro'  => array(
				'source'   => 'wowstore-ghost',
				'medium'   => 'ST-upgrade_to_pro',
				'campaign' => 'wowstore-dashboard',
			),
			'menu_WB_go_pro'              => array(
				'source'   => 'wowstore-ghost',
				'medium'   => 'WB-upgrade_to_pro',
				'campaign' => 'wowstore-dashboard',
			),

			// productx main menu
			'main_menu_go_pro'            => array(
				'source'   => 'db-wstore-plugin',
				'medium'   => 'left-menu-upgrade',
				'campaign' => 'wstore-dashboard',
			),

			// Wordpress plugin list
			'plugin_list_productx_go_pro' => array(
				'source'   => 'db-wstore-plugin',
				'medium'   => 'upgrade',
				'campaign' => 'wstore-dashboard',
			),
			'plugin_dir_pro'              => array(
				'source'   => 'db-wstore-plugin',
				'medium'   => 'upgrade-pro',
				'campaign' => 'wstore-dashboard',
			),
			'sub_menu'                    => array(
				'source'   => 'db-wstore-plugin',
				'medium'   => 'sub-menu',
				'campaign' => 'wstore-dashboard',
			),
			'exclusive_deals'             => array(
				'source'   => 'db-wowstore-notice-logo',
				'medium'   => 'exclusive-deals',
				'campaign' => 'wowstore-dashboard',
			),
			'black_friday'                => array(
				'source'   => 'db-wowstore-notice-logo',
				'medium'   => 'black-friday',
				'campaign' => 'wowstore-dashboard',
			),
			'new_year_sale'               => array(
				'source'   => 'db-wowstore-notice-logo',
				'medium'   => 'new-year-sale',
				'campaign' => 'wowstore-dashboard',
			),
			'preorder'                    => array(
				'source'   => 'wowstore',
				'medium'   => 'preorder',
				'campaign' => 'product-addons',
			),

			// Flash sale
			'flash_sale'                  => array(
				'source'   => 'db-wowstore-notice-content',
				'medium'   => 'summer-flash-sale',
				'campaign' => 'wowstore-dashboard',
			),
			'flash_sale_content'          => array(
				'source'   => 'db-wowstore-notice',
				'medium'   => 'summer-flash-sale',
				'campaign' => 'wowstore-dashboard',
			),
			'flash_sale_meta'             => array(
				'source'   => 'db-wowstore-plugin-meta',
				'medium'   => 'summer-flash-sale',
				'campaign' => 'wowstore-dashboard',
			),

			// surprise sale
			'surprise_sale'               => array(
				'source'   => 'db-wowstore-notice-content',
				'medium'   => 'summer-surprise-sale',
				'campaign' => 'wowstore-dashboard',
			),
			'surprise_sale_content'       => array(
				'source'   => 'db-wowstore-notice',
				'medium'   => 'summer-surprise-sale',
				'campaign' => 'wowstore-dashboard',
			),
			'surprise_sale_meta'          => array(
				'source'   => 'db-wowstore-notice-meta',
				'medium'   => 'summer-surprise-sale',
				'campaign' => 'wowstore-dashboard',
			),
			// massive sale
			'massive_sale'                => array(
				'source'   => 'db-wowstore-notice-content',
				'medium'   => 'summer-massive-sale',
				'campaign' => 'wowstore-dashboard',
			),
			'massive_sale_content'        => array(
				'source'   => 'db-wowstore-notice',
				'medium'   => 'summer-massive-sale',
				'campaign' => 'wowstore-dashboard',
			),
			'massive_sale_meta'           => array(
				'source'   => 'db-wowstore-notice-meta',
				'medium'   => 'summer-massive-sale',
				'campaign' => 'wowstore-dashboard',
			),

			// Final hours
			'final_hour'                  => array(
				'source'   => 'db-wowstore-notice-content',
				'medium'   => 'summer-final-sale',
				'campaign' => 'wowstore-dashboard',
			),
			'final_hour_content'          => array(
				'source'   => 'db-wowstore-notice',
				'medium'   => 'summer-final-sale',
				'campaign' => 'wowstore-dashboard',
			),
			'final_hour_meta'             => array(
				'source'   => 'db-wowstore-plugin-meta',
				'medium'   => 'summer-final-sale',
				'campaign' => 'wowstore-dashboard',
			),

			// 2026 spring sale utms
			'spring_sale_meta'            => array(
				'source'   => 'db-wowstore-plugin-meta',
				'medium'   => 'spring-sale',
				'campaign' => 'wowstore-dashboard',
			),
			'spring_sale'                 => array(
				'source'   => 'db-wowstore-notice',
				'medium'   => 'spring-sale',
				'campaign' => 'wowstore-dashboard',
			),
			'content_notice'              => array(
				'source'   => 'db-wowstore-notice',
				'medium'   => 'spring-sale',
				'campaign' => 'wowstore-dashboard',
			),
			// -----------------------------------------

		);

		// Step 1: Get parameters
		$base_url      = $params['url'] ?? 'https://www.wpxpo.com/product/wowstore/';
		$utm_key       = $params['utmKey'] ?? null;
		$affiliate     = $params['affiliate'] ?? apply_filters( 'wopb_affiliate_id', '' );
		$hash          = $params['hash'] ?? 'pricing';
		$custom_config = $params['config'] ?? null;

		$parsed_url = wp_parse_url( $base_url );
		$scheme     = $parsed_url['scheme'] ?? 'https';
		$host       = $parsed_url['host'] ?? '';
		$path       = $parsed_url['path'] ?? '';
		$query      = array();

		// Step 3: Extract existing query params if present
		if ( isset( $parsed_url['query'] ) ) {
			parse_str( $parsed_url['query'], $query );
		}

		// Step 4: Determine config
		$utm_config = $custom_config ?? ( $utm_key && isset( $default_config[ $utm_key ] ) ? $default_config[ $utm_key ] : array() );

		// Step 5: Add UTM parameters
		if ( ! empty( $utm_config ) ) {
			$query = array_merge(
				$query,
				array(
					'utm_source'   => $utm_config['source'],
					'utm_medium'   => $utm_config['medium'],
					'utm_campaign' => $utm_config['campaign'],
				)
			);
		}

		// Step 6: Add affiliate if present
		if ( $affiliate ) {
			$query['ref'] = $affiliate;
		}

		// Step 7: Reconstruct URL
		$final_url = $scheme . '://' . $host . $path;

		if ( ! empty( $query ) ) {
			$final_url .= '?' . http_build_query( $query );
		}

		if ( $hash ) {
			$final_url .= '#' . $hash;
		}

		return $final_url;
	}


	/**
	 * Get WOW Products Details
	 *
	 * @return array
	 */
	public static function get_wow_products_details() {
		return array(
			'products'        => array(
				'wow_shipping' => file_exists( WP_PLUGIN_DIR . '/wow-table-rate-shipping/wow-table-rate-shipping.php' ),
				'post_x'       => file_exists( WP_PLUGIN_DIR . '/ultimate-post/ultimate-post.php' ),
				'wow_store'    => file_exists( WP_PLUGIN_DIR . '/product-blocks/product-blocks.php' ),
				'wow_optin'    => file_exists( WP_PLUGIN_DIR . '/optin/optin.php' ),
				'wow_revenue'  => file_exists( WP_PLUGIN_DIR . '/revenue/revenue.php' ),
				'wholesale_x'  => file_exists( WP_PLUGIN_DIR . '/wholesalex/wholesalex.php' ),
				'wow_addon'    => file_exists( WP_PLUGIN_DIR . '/product-addons/product-addons.php' ),
				'wow_invoice'  => file_exists( WP_PLUGIN_DIR . '/wow-pdf-invoices-packing-slips/wow-pdf-invoices-packing-slips.php' ),
			),
			'products_active' => array(
				'wow_shipping' => defined( 'WTRS_VER' ),
				'post_x'       => defined( 'ULTP_VER' ),
				'wow_store'    => defined( 'WOPB_VER' ),
				'wow_optin'    => defined( 'OPTN_VERSION' ),
				'wow_revenue'  => defined( 'REVENUE_VER' ),
				'wholesale_x'  => defined( 'WHOLESALEX_VER' ),
				'wow_addon'    => defined( 'PRAD_VER' ),
				'wow_invoice'  => defined( 'WINV_VER' ),
			),
		);
	}


	/**
	 * Installs and activates a plugin by its name only.
	 *
	 * @param string $name The name or slug of the plugin to install and activate.
	 */
	public static function install_and_active_plugin( $name ) {
		$to_r        = array( 'done' => true );
		$plugin_slug = $name;
		$active_url  = '';
		switch ( $name ) {
			case 'wow_invoice':
				$plugin_slug = 'wow-pdf-invoices-packing-slips';
				$active_url  = admin_url( 'admin.php?page=winv-dashboard#overview' );
				break;
			case 'wow_shipping':
				$plugin_slug = 'wow-table-rate-shipping';
				$active_url  = admin_url( 'admin.php?page=wtrs-dashboard#overview' );
				break;
			case 'post_x':
				$plugin_slug = 'ultimate-post';
				$active_url  = admin_url( 'admin.php?page=ultp-settings#home' );
				break;
			case 'wow_store':
				$plugin_slug = 'product-blocks';
				$active_url  = admin_url( 'admin.php?page=wow-invoices' );
				break;
			case 'wow_optin':
				$plugin_slug = 'optin';
				$active_url  = admin_url( 'admin.php?page=wowoptin-dashboard' );
				break;
			case 'wow_revenue':
				$plugin_slug = 'revenue';
				$active_url  = admin_url( 'admin.php?page=revenue' );
				break;
			case 'wholesale_x':
				$plugin_slug = 'wholesalex';
				$active_url  = admin_url( 'admin.php?page=wholesalex/' );
				break;
			case 'wow_addon':
				$plugin_slug = 'product-addons';
				$active_url  = admin_url( 'admin.php?page=prad-dashboard#dashboard' );
				break;
		}

		$plugin_file = $plugin_slug . '/' . $plugin_slug . '.php';

		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
			if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
				return array(
					'done'  => false,
					'error' => esc_html__( 'Sorry, you are not allowed to install and activate plugins.', 'product-blocks' ),
				);
			}

			$to_r = self::plugin_install( $plugin_file, $plugin_slug );
		} else {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return array(
					'done'  => false,
					'error' => esc_html__( 'Sorry, you are not allowed to activate plugins.', 'product-blocks' ),
				);
			}

			$activation_result = activate_plugin( $plugin_file );
			if ( is_wp_error( $activation_result ) ) {
				return array(
					'done'  => false,
					'error' => $activation_result->get_error_message(),
				);
			}
		}
		$to_r['active_url'] = $active_url;
		return $to_r;
	}

	/**
	 * Installs a plugin based on the provided plugin file and slug.
	 *
	 * This function is expected to handle the logic required to install a plugin,
	 * such as downloading, unpacking, and activating the plugin using the provided
	 * plugin file and slug.
	 *
	 * @param string $plugin The plugin file path or identifier (e.g., 'plugin-directory/plugin-file.php').
	 * @param string $slug   The plugin slug (typically the directory name of the plugin).
	 */
	public static function plugin_install( $plugin, $slug ) {
		if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ) {
			return array(
				'done'  => false,
				'error' => esc_html__( 'Sorry, you are not allowed to install and activate plugins.', 'product-blocks' ),
			);
		}

		// Load required admin files (not auto-loaded in REST API context)
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		if ( ! function_exists( 'request_filesystem_credentials' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'show_message' ) ) {
			require_once ABSPATH . 'wp-admin/includes/misc.php';
		}
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		}
		if ( ! class_exists( 'WP_Ajax_Upgrader_Skin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
		}

		// Initialize the WP filesystem (required for upgrader file operations)
		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		WP_Filesystem(); // file system and all other's add manually because Rest Api not call all of those thing

		$api = plugins_api(
			'plugin_information',
			array(
				'slug'   => $slug,
				'fields' => array(
					'short_description' => false,
					'sections'          => false,
					'requires'          => false,
					'rating'            => false,
					'ratings'           => false,
					'downloaded'        => false,
					'last_updated'      => false,
					'added'             => false,
					'tags'              => false,
					'compatibility'     => false,
					'homepage'          => false,
					'donate_link'       => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			return array(
				'done'  => false,
				'error' => $api->get_error_message(),
			);
		}

		$upgrader       = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin( compact( 'title', 'url', 'nonce', 'plugin', 'api' ) ) );
		$install_result = $upgrader->install( $api->download_link );

		if ( is_wp_error( $install_result ) ) {
			return array(
				'done'  => false,
				'error' => $install_result->get_error_message(),
			);
		}

		if ( $install_result === false ) {
			return array(
				'done'  => false,
				'error' => 'Installation failed. Check filesystem permissions.',
			);
		}

		$activation_result = activate_plugin( $plugin );

		if ( is_wp_error( $activation_result ) ) {
			return array(
				'done'  => false,
				'error' => $activation_result->get_error_message(),
			);
		}

		return array( 'done' => true );
	}

	public static function is_pro_feature_visible( $date ) {
		if ( defined( 'WOPB_PRO_VER' ) ) {
			$license_data = get_option( 'edd_wopb_license_data' );
			if ( isset( $license_data['expires'] ) ) {
				$expires = $license_data['expires'];
				if ( 'lifetime' === $expires ) {
					return true;
				}
				return strtotime( $date ) < strtotime( $expires );
			}
			return true;
		}
		return false;
	}

	public static function handle_hellobar_action( $type, $extras = array() ) {

		$hellobar_key = 'wopb_hellobar';

		if ( 'get' === $type ) {
			return self::get_transient_without_cache( $hellobar_key );
		} else {
			self::set_transient_without_cache( $hellobar_key, 'hide', 20 * DAY_IN_SECONDS );
		}
	}
}
