<?php
/**
 * Initial Setup.
 *
 * @package WOPB\Notice
 * @since v.2.4.4
 */
namespace WOPB;

use WOPB\Includes\Durbin\DurbinClient;
use WOPB\Includes\Durbin\Xpo;

defined( 'ABSPATH' ) || exit;

class SetupWizard {

	public function __construct() {
		add_action( 'wowstore_menu', array( $this, 'menu_page_callback' ) );
		add_action( 'rest_api_init', array( $this, 'wopb_register_route' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'script_wizard_callback' ) ); // Option Panel
	}

	/**
	 * Setup Wizard Function
	 *
	 * @since v.4.0.0
	 * @return NULL
	 */
	public function script_wizard_callback() {
		if ( wopb_function()->get_screen() == 'wopb-initial-setup-wizard' ) {
			wp_enqueue_script( 'wopb-setup-wizard', WOPB_URL . 'assets/js/setup.wizard.js', array( 'wp-i18n', 'wp-api-fetch', 'wp-api-request', 'wp-components', 'wp-blocks' ), WOPB_VER, true );
			wp_localize_script(
				'wopb-setup-wizard',
				'setup_wizard',
				array(
					'url'      => WOPB_URL,
					'version'  => WOPB_VER,
					'security' => wp_create_nonce( 'wopb-nonce' ),
					'redirect' => admin_url( 'admin.php?page=wopb-settings#home' ),
				)
			);
			wp_set_script_translations( 'wopb-setup-wizard', 'product-blocks', WOPB_URL . 'languages/' );
		}
	}

	/**
	 * Plugins Menu Page Added
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function menu_page_callback() {
		add_submenu_page(
			'wopb-settings',
			esc_html__( 'Setup Wizard', 'product-blocks' ),
			esc_html__( 'Setup Wizard', 'product-blocks' ),
			'manage_options',
			'wopb-initial-setup-wizard',
			array( $this, 'initial_setup' )
		);
	}

	/**
	 * Initial Plugin Setting
	 *
	 * * @since 3.0.0
	 *
	 * @return STRING
	 */
	public function initial_setup() {
		?>
		<div class="wopb-initial-setting-wrap" id="wopb-initial-setting"></div>
		<?php
	}

	/**
	 * REST API Action
	 *  * @since 3.0.0
	 *
	 * @return NULL
	 */
	public function wopb_register_route() {
		register_rest_route(
			'wopb/v2',
			'/wizard_action/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'wizard_site_action_callback' ),
					'permission_callback' => function ( $request ) {
						if ( 'install' === $request->get_param( 'action' ) ) {
							return current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' );
						}

						return current_user_can( 'manage_options' );
					},
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			'wopb/v2',
			'/install-extra-plugin/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'install_extra_plugin' ),
					'permission_callback' => function () {
						return current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Save General Settings Data.
	 *
	 * @return void
	 * @since 3.0.0
	 */
	public function wizard_site_action_callback( $server ) {
		$params = $server->get_params();
		if ( ! isset( $params['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $params['wpnonce'] ) ), 'wopb-nonce' ) ) {
			die();
		}

		$action = sanitize_text_field( $params['action'] );

		$woocommerce_required = isset( $params['install_woocommerce'] ) && 'yes' === $params['install_woocommerce'];
		$revenue__required    = isset( $params['install_revenue'] ) && 'yes' === $params['install_revenue'];
		$wowshipping_required = isset( $params['install_wowshipping'] ) && 'yes' === $params['install_wowshipping'];

		if ( isset( $params['siteType'] ) ) {
			$site_type = sanitize_text_field( $params['siteType'] );
			update_option( '__wopb_site_type', $site_type );
		}

		if ( $action == 'install' ) {
			if ( $woocommerce_required ) {
				$this->handle_plugin_activation( 'woocommerce' );
			}
			if ( $revenue__required ) {
				$this->handle_plugin_activation( 'revenue' );
			}
			if ( $wowshipping_required ) {
				$this->handle_plugin_activation( 'wowshipping' );
			}

			return rest_ensure_response( array( 'success' => true ) );

		} elseif ( $action == 'send' ) {
			update_option( 'wopb_setup_wizard_data', 'yes' );
			DurbinClient::send( DurbinClient::WIZARD_ACTION );

			return rest_ensure_response(
				array(
					'success' => true,
				)
			);
		}
	}

	/**
	 * Install Extra Plugin
	 *
	 * @param object $server get request.
	 * @return \WP_Error|\WP_HTTP_Response|\WP_REST_Response
	 * @since v.4.1.7
	 */
	public function install_extra_plugin( $server ) {
		$params = $server->get_params();
		if ( ! isset( $params['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $params['wpnonce'] ) ), 'wopb-nonce' ) ) {
			die();
		}

		$plugin = $this->handle_plugin_activation( sanitize_text_field( $params['name'] ) );
		return rest_ensure_response(
			array(
				'redirect' => ! empty( $plugin['redirect'] ) ? $plugin['redirect'] : '',
				'success'  => true,
			)
		);
	}

	/**
	 * Install and Active Plugin
	 *
	 * @param string $name get plugin name.
	 * @return array
	 * @since v.4.1.7
	 */
	public function handle_plugin_activation( $name ) {
		$redirect = '';
		switch ( $name ) {
			case 'woocommerce':
				Xpo::install_and_active_plugin( 'woocommerce' );
				break;
			case 'revenue':
				Xpo::install_and_active_plugin( 'revenue' );
				$redirect = admin_url( 'admin.php?page=revenue' );
				break;
			case 'wowshipping':
				Xpo::install_and_active_plugin( 'wow-table-rate-shipping' );
				break;
			default:
				break;
		}
		return array(
			'redirect' => $redirect,
			'success'  => true,
		);
	}
}
