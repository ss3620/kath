<?php
namespace RevenuePro;

/**
 * RevenueX Campaign
 *
 * @hooked on init
 */
class Revenue_Pro_Ajax {

	public function __construct() {
		add_action('wp_ajax_revx_activate_wowrevenue',[$this,'activate_wowrevenue']);
		add_action('wp_ajax_revx_install_wowrevenue',[$this,'install_wowrevenue']);

	}


	public function activate_wowrevenue() {
		check_ajax_referer('activate_wowrevenue', '_ajax_nonce');
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions to activate plugins.', 'revenue-pro' ) );
		}
		
		$result = activate_plugin('revenue/revenue.php');
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		wp_send_json_success();
	}


	public function install_wowrevenue() {
		check_ajax_referer('install_wowrevenue', '_ajax_nonce');
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions to install plugins.', 'revenue-pro' ) );
		}

		if (!function_exists('plugins_api')) {
			include ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		if (!class_exists('WP_Upgrader')) {
			include ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if (!class_exists('Plugin_Installer_Skin')) {
			include ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php';
		}
		if (!class_exists('Plugin_Upgrader')) {
			include ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		}
		// $plugin_slug = 'revenue';
		// $api = plugins_api( 'plugin_information', array(
		// 	'slug' => $plugin_slug,
		// 	'fields' => array(
		// 		'sections' => false,
		// 	),
		// ) );

		// Download the plugin ZIP file
		$plugin_zip_url = 'https://downloads.wordpress.org/plugin/revenue.zip';
		$tmp_file = download_url($plugin_zip_url);

		// Check for download errors
		if (is_wp_error($tmp_file)) {
			wp_die('Error downloading the plugin: ' . $tmp_file->get_error_message());
		}

		if ( is_wp_error( $api ) ) {
			wp_send_json_error( $api->get_error_message() );
		}
		$upgrader = new \Plugin_Upgrader(new \WP_Ajax_Upgrader_Skin());

		$result = $upgrader->install($tmp_file);


		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		if ( ! $result ) {
			wp_send_json_error( __( 'Plugin installation failed.', 'revenue-pro' ) );
		}
		wp_send_json_success();
	}


}
