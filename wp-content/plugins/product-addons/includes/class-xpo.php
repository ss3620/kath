<?php //phpcs:ignore
/**
 * Xpo class for Product Addons plugin.
 *
 * @package PRAD
 */

namespace PRAD\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Core class for managing plugin actions and integrations.
 *
 * @package PRAD
 */
class Xpo {

	/**
	 * Gets license key
	 *
	 * @return string
	 */
	public static function get_lc_key() {
		return get_option( 'edd_prad_license_key', '' );
	}

	/**
	 * Checks if the license key is active.
	 *
	 * @return bool True if the license is active, false otherwise.
	 */
	public static function is_lc_active() {
		if ( defined( 'PRAD_PRO_VER' ) ) {
			$license_data = get_option( 'edd_prad_license_data', array() );
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
		$license_data = get_option( 'edd_prad_license_data', array() );
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
			return apply_filters( "prad_default_option_{$option}", $default_value, $option );
		}

		return apply_filters( "prad_option_{$option}", maybe_unserialize( $value ), $option );
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

		if ( apply_filters( "prad_default_option_{$option}", false, $option, false ) !== self::get_option_without_cache( $option ) ) {
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

		return apply_filters( "prad_transient_{$transient}", $value, $transient );
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

	/**
	 * Generates a URL with UTM parameters for tracking.
	 *
	 * @param array $params {
	 *     Optional. Parameters for generating the UTM link.
	 *
	 *     @type string $url       The base URL to which UTM parameters will be added.
	 *     @type string $utmKey    The key to select a default UTM configuration.
	 *     @type string $affiliate Affiliate ID to append as a 'ref' parameter.
	 *     @type string $hash      Hash fragment to append to the URL.
	 *     @type array  $config    Custom UTM configuration array.
	 * }
	 * @return string The generated URL with UTM parameters.
	 */
	public static function generate_utm_link( $params ) {
		$default_config = array(
			'example'           => array(
				'source'   => 'db-wowaddons-featurename',
				'medium'   => 'block-feature',
				'campaign' => 'wowaddons-dashboard',
			),
			'content_notice'    => array(
				'source'   => 'db-wowaddons-notice',
				'medium'   => 'summer-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'img_banner_notice' => array(
				'source'   => 'db-wowaddons-banner',
				'medium'   => 'spring-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'sub_menu'          => array(
				'source'   => 'db-wowaddons-plugin',
				'medium'   => 'sub-menu',
				'campaign' => 'wowaddons-dashboard',
			),
			'plugin_meta'       => array(
				'source'   => 'db-wowaddons-plugin',
				'medium'   => 'plugin-meta',
				'campaign' => 'wowaddons-dashboard',
			),
			'massive_sale'      => array(
				'source'   => 'db-wowaddons-notice-logo',
				'medium'   => 'massive-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'flash_sale'        => array(
				'source'   => 'db-wowaddons-notice',
				'medium'   => 'flash-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'spring_sale'       => array(
				'source'   => 'db-wowaddons-notice',
				'medium'   => 'spring-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'final_hour_sale'   => array(
				'source'   => 'db-wowaddons-notice',
				'medium'   => 'final-hour-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'surprise_sale'     => array(
				'source'   => 'db-wowaddons-notice-logo',
				'medium'   => 'surprise-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'exclusive_deals'   => array(
				'source'   => 'db-wowaddons-notice-logo',
				'medium'   => 'exclusive-deals',
				'campaign' => 'wowaddons-dashboard',
			),
		);

		// Step 1: Get parameters.
		$base_url      = $params['url'] ?? 'https://www.wpxpo.com/product/wowaddons/pricing/';
		$utm_key       = $params['utmKey'] ?? null;
		$affiliate     = $params['affiliate'] ?? apply_filters( 'prad_affiliate_id', '' );
		$hash          = $params['hash'] ?? '';
		$custom_config = $params['config'] ?? null;

		$parsed_url = wp_parse_url( $base_url );
		$scheme     = $parsed_url['scheme'] ?? 'https';
		$host       = $parsed_url['host'] ?? '';
		$path       = $parsed_url['path'] ?? '';
		$query      = array();

		// Step 3: Extract existing query params if present.
		if ( isset( $parsed_url['query'] ) ) {
			parse_str( $parsed_url['query'], $query );
		}

		// Step 4: Determine config.
		$utm_config = $custom_config ?? ( $utm_key && isset( $default_config[ $utm_key ] ) ? $default_config[ $utm_key ] : array() );

		// Step 5: Add UTM parameters.
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

		// Step 6: Add affiliate if present.
		if ( $affiliate ) {
			$query['ref'] = $affiliate;
		}

		// Step 7: Reconstruct URL.
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
				'wow_invoice'  => file_exists( WP_PLUGIN_DIR . '/wow-pdf-invoices-packing-slips/wow-pdf-invoices-packing-slips.php' ),
				'wow_shipping' => file_exists( WP_PLUGIN_DIR . '/wow-table-rate-shipping/wow-table-rate-shipping.php' ),
				'post_x'       => file_exists( WP_PLUGIN_DIR . '/ultimate-post/ultimate-post.php' ),
				'wow_store'    => file_exists( WP_PLUGIN_DIR . '/product-blocks/product-blocks.php' ),
				'wow_optin'    => file_exists( WP_PLUGIN_DIR . '/optin/optin.php' ),
				'wow_revenue'  => file_exists( WP_PLUGIN_DIR . '/revenue/revenue.php' ),
				'wholesale_x'  => file_exists( WP_PLUGIN_DIR . '/wholesalex/wholesalex.php' ),
				'wow_addon'    => file_exists( WP_PLUGIN_DIR . '/product-addons/product-addons.php' ),
			),
			'products_active' => array(
				'wow_invoice'  => defined( 'WINV_VER' ),
				'wow_shipping' => defined( 'WTRS_VER' ),
				'post_x'       => defined( 'ULTP_VER' ),
				'wow_store'    => defined( 'WOPB_VER' ),
				'wow_optin'    => defined( 'OPTN_VERSION' ),
				'wow_revenue'  => defined( 'REVENUE_VER' ),
				'wholesale_x'  => defined( 'WHOLESALEX_VER' ),
				'wow_addon'    => defined( 'PRAD_VER' ),
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
		$plugin_slug = '';
		switch ( $name ) {
			case 'wow_invoice':
				$plugin_slug = 'wow-pdf-invoices-packing-slips';
				break;
			case 'wow_shipping':
				$plugin_slug = 'wow-table-rate-shipping';
				break;
			case 'post_x':
				$plugin_slug = 'ultimate-post';
				break;
			case 'wow_store':
				$plugin_slug = 'product-blocks';
				break;
			case 'wow_optin':
				$plugin_slug = 'optin';
				break;
			case 'wow_revenue':
				$plugin_slug = 'revenue';
				break;
			case 'wholesale_x':
				$plugin_slug = 'wholesalex';
				break;
			case 'wow_addon':
				$plugin_slug = 'product-addons';
				break;
			case 'woocommerce':
				$plugin_slug = 'woocommerce';
				break;
		}

		if ( ! file_exists( WP_PLUGIN_DIR . '/' . $plugin_slug . '/' . $plugin_slug . '.php' ) ) {
				$to_r = self::plugin_install( $plugin_slug . '/' . $plugin_slug . '.php', $plugin_slug );
		} else {
			activate_plugin( $plugin_slug . '/' . $plugin_slug . '.php' );
		}
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
		include ABSPATH . 'wp-admin/includes/plugin-install.php';
		include ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			include ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		}
		if ( ! class_exists( 'WP_Ajax_Upgrader_Skin' ) ) {
			include ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';
		}

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
			wp_die( $api ); //phpcs:ignore
		}

		$upgrader       = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin( compact( 'title', 'url', 'nonce', 'plugin', 'api' ) ) );
		$install_result = $upgrader->install( $api->download_link );

		if ( ! is_wp_error( $install_result ) ) {
			activate_plugin( $plugin );
			return array( 'done' => false );
		}

		return array( 'done' => true );
	}

	/**
	 * Retrieve a specific item from the 'prad_settings' option.
	 *
	 * Handles both array and object formats for backward compatibility.
	 *
	 * @param string $key The key of the setting to retrieve.
	 * @param mixed  $def The default value to return if the key is not found.
	 * @return mixed|null The value of the setting if found, otherwise null.
	 */
	public static function get_prad_settings_item( $key, $def = '' ) {
		if ( empty( $key ) ) {
			return $def;
		}

		$prad_settings = get_option( 'prad_settings', array() );

		// Handle both array and object (from REST API) formats.
		if ( is_array( $prad_settings ) && array_key_exists( $key, $prad_settings ) ) {
			return $prad_settings[ $key ];
		} elseif ( is_object( $prad_settings ) && isset( $prad_settings->$key ) ) {
			return $prad_settings->$key;
		}

		return $def;
	}

	/**
	 * Handles view permission capability for old demo and admin hooks.
	 *
	 * Applies filters for admin-only, view-only, and old demo capability checks.
	 *
	 * @param string $def Default capability (usually 'manage_options').
	 * @return string The resolved capability.
	 */
	public static function prad_old_view_permisson_handler( $def = 'manage_options' ) {
		$view_capability = apply_filters( 'prad_handle_capability_admin_only', $def );  // check for admin hook first.
		$view_capability = apply_filters( 'prad_handle_capability_view_only', $view_capability );   // then check for view only hook.
		$view_capability = apply_filters( 'prad_demo_capability_check', $view_capability ); // finally check for old demo hook for backward compatibility.

		return $view_capability;
	}

	/**
	 * Handles admin permission capability for admin hooks.
	 *
	 * Applies filter for admin-only capability checks.
	 *
	 * @param string $def Default capability (usually 'manage_options').
	 * @return string The resolved capability.
	 */
	public static function prad_manage_admin_permisson_handler( $def = 'manage_options' ) {
		$admin_capability = apply_filters( 'prad_handle_capability_admin_only', $def );  // check for admin hook.

		return $admin_capability;
	}
}
