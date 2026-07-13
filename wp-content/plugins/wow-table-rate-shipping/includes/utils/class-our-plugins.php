<?php // phpcs:ignore

namespace WTRS\Includes\Utils;

use WTRS\Includes\Xpo;

defined( 'ABSPATH' ) || exit;

/**
 * WPXPO Plugins Page
 */
class OurPlugins {


	/**
	 * Constructor. Hooks into various WordPress actions.
	 */
	public function __construct() {
		// Our Plugin Activation Hooks.
		add_action( 'wp_ajax_wtrs_install_plugin', array( $this, 'wtrs_install_plugin_callback' ) );
	}

	/**
	 * Handles plugin installation and activation via AJAX.
	 *
	 * @return void
	 */
	public function wtrs_install_plugin_callback() {
		$nonce = isset( $_POST['wpnonce'] ) ? sanitize_key( wp_unslash( $_POST['wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'wtrs-nonce' ) ) {
			wp_send_json_error( array( 'message' => 'No plugin specified' ) );
		}

		$res = false;

		$plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';

		if ( $plugin ) {
			$res = Xpo::install_and_active_plugin( $plugin );
		}

		$res ? wp_send_json_success( array( 'message' => 'Plugin installed successfully' ) ) : wp_send_json_error( array( 'message' => 'Failed to install plugin' ) );
	}
}
