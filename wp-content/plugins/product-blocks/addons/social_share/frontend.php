<?php
defined( 'ABSPATH' ) || exit;

/**
 * Require Main File
 *
 * @since v.3.2.0
 */
add_action( 'wp_loaded', 'wopb_social_share_config' );
function wopb_social_share_config() {
	if ( wopb_function()->get_setting( 'wopb_social_share' ) == 'true' ) {
		require_once WOPB_PATH . '/addons/social_share/SocialShare.php';
		new \WOPB\SocialShare();
	}
}
