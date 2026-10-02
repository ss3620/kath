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
			'example'                => array(
				'source'   => 'db-wowaddons-featurename',
				'medium'   => 'block-feature',
				'campaign' => 'wowaddons-dashboard',
			),
			'content_notice'         => array(
				'source'   => 'db-wowaddons-notice',
				'medium'   => 'summer-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'img_banner_notice'      => array(
				'source'   => 'db-wowaddons-banner',
				'medium'   => 'spring-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'sub_menu'               => array(
				'source'   => 'db-wowaddons-plugin',
				'medium'   => 'sub-menu',
				'campaign' => 'wowaddons-dashboard',
			),
			'plugin_meta'            => array(
				'source'   => 'db-wowaddons-plugin',
				'medium'   => 'plugin-meta',
				'campaign' => 'wowaddons-dashboard',
			),
			'plugin_meta_base_price' => array(
				'source'   => 'db-wowaddons-plugin',
				'medium'   => 'base-price',
				'campaign' => 'wowaddons-dashboard',
			),
			'plugin_meta_summer_db'  => array(
				'source'   => 'db-wowaddons-plugin-meta',
				'medium'   => 'summer-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'massive_sale'           => array(
				'source'   => 'db-wowaddons-notice-logo',
				'medium'   => 'massive-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'flash_sale'             => array(
				'source'   => 'db-wowaddons-notice',
				'medium'   => 'flash-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'summer_db'              => array(
				'source'   => 'db-wowaddons-notice',
				'medium'   => 'summer-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'spring_sale'            => array(
				'source'   => 'db-wowaddons-notice',
				'medium'   => 'spring-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'final_hour_sale'        => array(
				'source'   => 'db-wowaddons-notice',
				'medium'   => 'final-hour-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'surprise_sale'          => array(
				'source'   => 'db-wowaddons-notice-logo',
				'medium'   => 'surprise-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'exclusive_deals'        => array(
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
	public static function prad_old_view_permisson_handler( $def = 'manage_woocommerce' ) {
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
	public static function prad_manage_admin_permisson_handler( $def = 'manage_woocommerce' ) {
		$admin_capability = apply_filters( 'prad_handle_capability_admin_only', $def );  // check for admin hook.

		return $admin_capability;
	}
}
