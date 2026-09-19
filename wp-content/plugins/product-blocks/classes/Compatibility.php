<?php
/**
 * Compatibility Action.
 *
 * @package WOPB\Notice
 * @since v.1.1.0
 */
namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility class.
 */
class Compatibility {

	/**
	 * Setup class.
	 *
	 * @since v.1.1.0
	 */
	public function __construct() {
		// add_action( 'upgrader_process_complete', array( $this, 'plugin_upgrade_completed' ), 10, 2 );
		add_filter( 'wp_kses_allowed_html', array( $this, 'wopb_handle_allowed_html' ), 99, 2 );
	}
	/**
	 * Add support for html tag to use svg
	 *
	 * @since 4.3.3
	 * @return supported_tags
	 */
	public function wopb_handle_allowed_html( $tags, $context ) {
		if ( 'post' !== $context && ! is_multisite() && ! current_user_can( 'edit_posts' ) ) {
			return $tags;
		}
		if ( ! isset( $tags['svg'] ) ) {
			$tags['svg'] = array_merge(
				array(
					'xmlns'               => true,
					// 'xmlns:xlink'   => true,
					// 'xlink:href'     => true,
					// 'xml:id'     => true,
					// 'xlink:title'    => true,
					// 'xml:space'  => true,
					'viewbox'             => true,
					'enable-background'   => true,
					'version'             => true,
					'preserveaspectratio' => true,
					'fill'                => true,
				)
			);
		}
		if ( ! isset( $tags['path'] ) ) {
			$tags['path'] = array(
				'd'                 => true,
				'stroke'            => true,
				'stroke-miterlimit' => true,
				'data-original'     => true,
				'class'             => true,
				'transform'         => true,
				'style'             => true,
				'opacity'           => true,
				'fill'              => true,
			);
		}
		if ( ! isset( $tags['g'] ) ) {
			$tags['g'] = array(
				'transform' => true,
				'clip-path' => true,
			);
		}
		if ( ! isset( $tags['clippath'] ) ) {
			$tags['clippath'] = array();
		}
		if ( ! isset( $tags['defs'] ) ) {
			$tags['defs'] = array();
		}
		if ( ! isset( $tags['rect'] ) ) {
			$tags['rect'] = array(
				'rx'        => true,
				'height'    => true,
				'width'     => true,
				'transform' => true,
				'x'         => true,
				'fill'      => true,
			);
		}
		if ( ! isset( $tags['circle'] ) ) {
			$tags['circle'] = array(
				'cx'        => true,
				'cy'        => true,
				'transform' => true,
				'r'         => true,
			);
		}
		if ( ! isset( $tags['polygon'] ) ) {
			$tags['polygon'] = array(
				'points' => true,
			);
		}
		if ( ! isset( $tags['lineargradient'] ) ) {
			$tags['lineargradient'] = array(
				'gradienttransform' => true,
				'id'                => true,
			);
		}
		if ( ! isset( $tags['stop'] ) ) {
			$tags['stop'] = array(
				'offset'       => true,
				'stop-color'   => true,
				'style'        => true,
				'stop-opacity' => true,
			);
		}
		return $tags;
	}

	/**
	 * Compatibility Class Run after Plugin Upgrade
	 *
	 * @since v.1.1.0
	 */
	public function plugin_upgrade_completed( $upgrader_object, $options ) {
		if ( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
			foreach ( $options['plugins'] as $plugin ) {
				if ( $plugin == WOPB_BASE ) {
					// License Check And Active
					if ( defined( 'WOPB_PRO_VER' ) ) { // for Pro Plugins
						$license  = get_option( 'edd_wopb_license_key' );
						$response = wp_remote_post(
							'https://account.wpxpo.com',
							array(
								'timeout'   => 15,
								'sslverify' => false,
								'body'      => array(
									'edd_action' => 'activate_license',
									'license'    => $license,
									'item_id'    => 1263,
									'url'        => home_url(),
								),
							)
						);
						if ( ! is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
							$license_data = json_decode( wp_remote_retrieve_body( $response ) );
							update_option( 'edd_wopb_license_status', $license_data->license );
						}
					}
					// Set Metabox Position in Product Edit
					wopb_function()->builder_metabox_position();
				}
			}
		}
	}
}
