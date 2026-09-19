<?php //phpcs:ignore
/**
 * Options Action.
 */

namespace PRAD\Includes\Admin;

defined( 'ABSPATH' ) || exit;

use PRAD\Includes\Xpo;

/**
 * OurPlugins class.
 */
class OurPlugins {

	/**
	 * Setup class.
	 */
	public function __construct() {
		add_action( 'wp_ajax_prad_install_plugin', array( $this, 'prad_install_plugin_callback' ) );
	}

	/**
	 * Handles plugin installation and activation via AJAX.
	 *
	 * @return void
	 */
	public function prad_install_plugin_callback() {

		$nonce  = isset( $_POST['wpnonce'] ) ? sanitize_key( wp_unslash( $_POST['wpnonce'] ) ) : '';
		$plugin = isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'prad-nonce' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'No plugin specified' ) );
		}

		$res = array( 'message' => 'false' );

		if ( $plugin ) {
			$res = Xpo::install_and_active_plugin( $plugin );
		}

		wp_send_json_success( array( 'message' => $res ) );

		die();
	}
}
