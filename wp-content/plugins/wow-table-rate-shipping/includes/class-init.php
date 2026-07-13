<?php // phpcs:ignore
/**
 * Initialization Action.
 *
 * @package WTRS
 */
namespace WTRS\Includes;

use WTRS\Carriers\LiveRateManager;
use WTRS\Includes\Utils\Options;
use WTRS\Includes\Utils\Notice;
use WTRS\Includes\Utils\Hooks;
use WTRS\Includes\Rest\Rest;
use WTRS\Includes\Utils\Deactive;
use WTRS\Includes\Utils\Flags;
use WTRS\Includes\Utils\ImportHelper;
use WTRS\Includes\Utils\OurPlugins;
use WTRS\Includes\Utils\PluginActions;

defined( 'ABSPATH' ) || exit;

/**
 * Initialization class.
 */
class Init {

	/**
	 * Setup class.
	 */
	public function __construct() {
		add_action( 'activated_plugin', array( $this, 'activation_redirect' ) );
		add_action( 'plugins_loaded', array( $this, 'load' ) );

		new WowAddonsPromotion();
		new WowRevenuePromotion();
	}

	/**
	 * Load plugin
	 *
	 * @return void
	 */
	public function load() {
		if ( ! class_exists( '\WooCommerce' ) ) {
			return;
		}

		LiveRateManager::get_instance()->hooks();

		new Rest();
		new Hooks();
		new WowShippingGlobalMethod();
		ImportHelper::init();

		if ( is_admin() ) {
			new OurPlugins();
			new Options();
			new Notice();
			new Deactive();
			new PluginActions();
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_callback' ) );
		}
	}

	/**
	 * Only Backend CSS and JS Scripts
	 *
	 * @return void
	 */
	public function admin_scripts_callback() {
		global $pagenow;
		$page            = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab             = sanitize_text_field( wp_unslash( $_GET['tab'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$has_zone_id     = (bool) sanitize_text_field( wp_unslash( $_GET['zone_id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$has_instance_id = (bool) sanitize_text_field( wp_unslash( $_GET['instance_id'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$instance_id     = absint( sanitize_text_field( wp_unslash( $_GET['instance_id'] ?? 0 ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$in_wc_submenu_page = ( 'admin.php' === $pagenow && 'wtrs-dashboard' === $page );

		$in_wc_instance_page = ( 'admin.php' === $pagenow
			&& 'wc-settings' === $page
			&& 'shipping' === $tab
			&& $has_instance_id
			&& ! $has_zone_id );

		$asset_file = WTRS_PATH . 'assets/js/wtrs-backend.asset.php';

		if ( ! file_exists( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		if ( ! is_array( $asset ) ) {
			return;
		}

		if ( $in_wc_submenu_page || $in_wc_instance_page ) {

			$this->load_styles(
				array(
					'asset' => $asset,
				)
			);

			$this->load_scripts(
				array(
					'in_wc_instance_page' => $in_wc_instance_page,
					'instance_id'         => $instance_id,
					'asset'               => $asset,
				)
			);
		}
	}

	/**
	 * Load wtrs styles
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	private function load_styles( $args ) {

		$asset = $args['asset'];

		// WP DataTables CSS.
		$datatable_css_file = is_rtl() ? 'wtrs-backend-datatable-rtl.css' : 'wtrs-backend-datatable.css';
		wp_enqueue_style( 'wtrs-datatable-css', WTRS_URL . "assets/css/{$datatable_css_file}", array(), $asset['version'] );

		// Main CSS.
		$main_css_file = is_rtl() ? 'wtrs-backend-rtl.css' : 'wtrs-backend.css';
		wp_enqueue_style( 'wtrs-editor-css', WTRS_URL . "assets/css/{$main_css_file}", array(), $asset['version'] );
	}

	/**
	 * Load wtrs scripts
	 *
	 * @param array $args Arguments.
	 * @return void
	 */
	private function load_scripts( $args ) {
		$in_wc_instance_page = $args['in_wc_instance_page'];
		$instance_id         = $args['instance_id'];
		$asset               = $args['asset'];

		$current_zone_id = null;
		if ( $in_wc_instance_page && class_exists( '\\WC_Shipping_Zones' ) && $instance_id ) {
			$zone = \WC_Shipping_Zones::get_zone_by( 'instance_id', $instance_id );
			if ( $zone && is_object( $zone ) && method_exists( $zone, 'get_id' ) ) {
				$current_zone_id = (int) $zone->get_id();
			}
		}

		$user_info = get_userdata( get_current_user_id() );

		$script_dependencies = array_unique(
			array_merge(
				$asset['dependencies'],
				array( 'wc-components', 'wc-settings' )
			)
		);

		if ( wp_style_is( 'wc-components', 'registered' ) ) {
			wp_enqueue_style( 'wc-components' );
		}

		if ( wp_style_is( 'wc-admin-layout', 'registered' ) ) {
			wp_enqueue_style( 'wc-admin-layout' );
		}

		if ( wp_style_is( 'woocommerce_admin_styles', 'registered' ) ) {
			wp_enqueue_style( 'woocommerce_admin_styles' );
		}

		wp_enqueue_script( 'wtrs-editor-script', WTRS_URL . 'assets/js/wtrs-backend.js', $script_dependencies, $asset['version'], true );

		wp_set_script_translations( 'wtrs-editor-script', 'wow-table-rate-shipping', WTRS_PATH . 'languages/' );

		$wow_plugins = Xpo::get_wow_products_details();

		wp_localize_script(
			'wtrs-editor-script',
			'wtrsAdmin',
			array(
				'ajax'             => admin_url( 'admin-ajax.php' ),
				'url'              => WTRS_URL,
				'debugLogUrl'      => admin_url( 'admin.php?page=wc-status&tab=logs' ),
				'db_url'           => admin_url( 'admin.php?page=wtrs-dashboard#' ),
				'version'          => WTRS_VER,
				'isActive'         => Xpo::is_lc_active(),
				'license'          => get_option( 'edd_wtrs_license_key' ),
				'nonce'            => wp_create_nonce( 'wtrs-nonce' ),
				'decimal_sep'      => get_option( 'woocommerce_price_decimal_sep', '.' ),
				'num_decimals'     => get_option( 'woocommerce_price_num_decimals', '2' ),
				'currency_pos'     => get_option( 'woocommerce_currency_pos', 'left' ),
				'currencySymbol'   => function_exists( 'get_woocommerce_currency_symbol' ) ? trim( get_woocommerce_currency_symbol() ) : '$',
				'currencyCode'     => function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD',
				'weightUnit'       => get_option( 'woocommerce_weight_unit' ),
				'dimensionUnit'    => get_option( 'woocommerce_dimension_unit' ),
				'products'         => $wow_plugins['products'],
				'products_active'  => $wow_plugins['products_active'],
				'userInfo'         => array(
					'name'  => $user_info->first_name ? $user_info->first_name . ( $user_info->last_name ? ' ' . $user_info->last_name : '' ) : $user_info->user_login,
					'email' => $user_info->user_email,
				),
				'show_lic_page'    => defined( 'WTRS_PRO_VER' ) ? 'true' : 'false',
				'settings'         => DB::get_instance()->get_settings(),
				'flags'            => Flags::get_flags(),
				'currentZoneId'    => $current_zone_id,
				'helloBar'         => Notice::get_hellobar_config(),
				'isWooMarketplace' => defined( 'WTRS_WOO_MARKETPLACE' ) && WTRS_WOO_MARKETPLACE === true ? 'true' : 'false',
			)
		);
	}


	/**
	 * Redirect After Active Plugin
	 *
	 * @param string $plugin Plugin name.
	 *
	 * @return NULL
	 */
	public function activation_redirect( $plugin ) {
		if ( 'wow-table-rate-shipping/wow-table-rate-shipping.php' === $plugin ) {
			$action = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) ); // phpcs:ignore
			if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) || 'activate-selected' === $action ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}
			exit( wp_safe_redirect( admin_url( 'admin.php?page=wtrs-dashboard#overview' ) ) ); // phpcs:ignore
		}
	}
}
