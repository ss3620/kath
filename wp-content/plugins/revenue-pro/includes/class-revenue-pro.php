<?php

/**
 * Revenue Pro Setup
 *
 * @package Revenue Pro
 * @since   1.0.0
 */

use RevenuePro\Revenue_Pro_Install;
use RevenuePro\Revenue_Mix_Match;
use RevenuePro\Revenue_Frequently_Bought_Together;
use RevenuePro\Revenue_Double_Order;
use RevenuePro\Revenue_Spending_Goal;
use RevenuePro\Revenue_Pro_Campaign;

defined( 'ABSPATH' ) || exit;

final class Revenue_Pro {



	/**
	 * Contain Plugin Version
	 *
	 * @todo Version Should changes each update
	 *
	 * @var   string
	 * @since 1.0.0
	 */
	public $version = '2.2.1';

	/**
	 * Containt Instance of this class
	 *
	 * @var   RevenueX
	 * @since 1.0.0
	 */
	private static $instance = null;

	private $is_dependent_plugin_installing = false;

	/**
	 * Contains class instances
	 *
	 * @var   array
	 * @since 1.0.0
	 */
	private $container = array();

	private function __construct() {
		$this->define_constants();

		register_activation_hook( REVENUE_PRO_FILE, array( $this, 'activate' ) );
		register_deactivation_hook( REVENUE_PRO_FILE, array( $this, 'deactivate' ) );

		// Handle one-time activation redirect to settings/license page.
		add_action( 'admin_init', array( $this, 'pro_activation_redirect' ) );

		$this->include_ajax();

		$this->include_notice();

		add_action( 'revenue_loaded', array( $this, 'init_plugin' ) );
	}

	/**
	 * Initializes the RevenueX class
	 *
	 * Checks for an existing RevenueX instance
	 * and if it doesn't find one, creates it.
	 *
	 * @return Revenue
	 * @since  1.0.0
	 */
	public static function init() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Magic Getter Functions
	 *
	 * @param  string $name
	 * @return Class Instance
	 */
	public function __get( $name ) {
		if ( array_key_exists( $name, $this->container ) ) {
			return $this->container[ $name ];
		}
	}


	/**
	 * Initialize localization setup for localization
	 *
	 * @uses load_plugin_textdomain()
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'revenue-pro', false, dirname( REVENUE_PRO_BASE ) . '/languages/' );
	}

	/**
	 * Loads after woocommerce loaded
	 *
	 * @return void
	 */
	public function init_plugin() {
		/**
		 * Action triggered before RevenueX initialization begins.
		 */
		do_action( 'before_revenue_pro_init' );

		// Includes Files.
		$this->includes();

		// Load init hooks.
		$this->init_hooks();

		do_action( 'revenue_pro_loaded' );
	}

	/**
	 * Define Required Constants
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function define_constants() {
		define( 'REVENUE_PRO_VER', $this->version );
		define( 'REVENUE_PRO_BASE', plugin_basename( REVENUE_PRO_FILE ) );
	}

	/**
	 * Include all required files
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function includes() {
		// EDD Licensing File.
		include_once REVENUE_PRO_PATH . 'includes/updater/License.php';

		// Campaign.
		require_once REVENUE_PRO_PATH . 'includes/class-revenue-pro-template-utils.php';
		include_once REVENUE_PRO_PATH . 'includes/class-revenue-pro-campaign.php';
		include_once REVENUE_PRO_PATH . 'includes/class-revenue-pro-rest.php';
		include_once REVENUE_PRO_PATH . 'includes/class-revenue-pro-assets.php';

		// Campaigns.
		include_once REVENUE_PRO_PATH . 'includes/campaigns/class-revenue-mix-match.php';
		include_once REVENUE_PRO_PATH . 'includes/campaigns/class-revenue-frequently-bought-together.php';
		include_once REVENUE_PRO_PATH . 'includes/campaigns/class-revenue-double-order.php';
		include_once REVENUE_PRO_PATH . 'includes/campaigns/class-revenue-spending-goal.php';
	}

	/**
	 * Initialize the actions
	 *
	 * @return void
	 */
	public function init_hooks() {
		// Localize our plugin.
		add_action( 'init', array( $this, 'localization_setup' ) );

		// initialize the classes.
		add_action( 'init', array( $this, 'init_classes' ), 4 );
	}


	/**
	 * Include ajax
	 *
	 * @return void
	 */
	public function include_ajax() {

		if ( ! class_exists( '\RevenuePro\Revenue_Pro_Ajax', false ) ) {
			include_once REVENUE_PRO_PATH . 'includes/class-revenue-pro-ajax.php';
			new RevenuePro\Revenue_Pro_Ajax();
		}
	}

	/**
	 * Include Notice
	 *
	 * @return void
	 */
	public function include_notice() {

		if ( ! class_exists( '\RevenuePro\Revenue_Pro_Notice', false ) ) {
			include_once REVENUE_PRO_PATH . 'includes/class-revenue-pro-notice.php';
		}
	}

	/**
	 * Initialize all classes
	 *
	 * @return void
	 */
	public function init_classes() {
		new RevenuePro\License();
		new Revenue_Pro_Campaign();
		new RevenuePro\Revenue_Pro_REST();
		new RevenuePro\Revenue_Pro_Assets();

		Revenue_Mix_Match::instance()->init();
		Revenue_Frequently_Bought_Together::instance()->init();
		Revenue_Double_Order::instance()->init();
		Revenue_Spending_Goal::instance()->init();
	}


	public function load_scripts() {
	}


	/**
	 * Plugin action links
	 * Use custom links on Plugin actions
	 *
	 * @param array $links
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public function plugin_action_links( $links ) {
		$links['license'] = '<a href="' . esc_url( admin_url( 'admin.php?page=license' ) ) . '">' . esc_html__( 'Settings', 'revenue-pro' ) . '</a>';

		return $links;
	}


	/**
	 * Check whether revenue free is installed and active
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public function has_revenue() {
		return class_exists( 'Revenue' );
	}



	/**
	 * Check whether woocommerce is installed
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public function is_revenue_installed() {
		return file_exists( WP_PLUGIN_DIR . '/revenue/revenue.php' );
	}

	/**
	 * Placeholder for activation function
	 *
	 * Nothing being called here yet.
	 */
	public function activate() {
		// Set a flag so we can redirect the user to the license/settings page after activation.
		update_option( 'revenue_pro_do_activation_redirect', true );

		$installer = new Revenue_Pro_Install();
		$installer->install();
	}

	/**
	 * Redirect the user once after activation to the license/settings page.
	 */
	public function pro_activation_redirect() {
		// Only redirect in admin for users who can manage plugins.
		if ( ! is_admin() ) {
			return;
		}

		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		// Bail if flag not present.
		if ( ! get_option( 'revenue_pro_do_activation_redirect', false ) ) {
			return;
		}

		// Remove the flag so we only redirect once.
		delete_option( 'revenue_pro_do_activation_redirect' );

		// Prevent redirect during bulk plugin activation. This flag only suppresses navigation.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Does not perform an action based on request data.
		if ( isset( $_GET['activate-multi'] ) ) {
			return;
		}

		// Use the license/settings page used elsewhere in the plugin.
		$redirect_url = admin_url( 'admin.php?page=revenue#/license' );

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Placeholder for deactivate function
	 *
	 * Nothing being called here yet.
	 */
	public function deactivate() {
	}

	public function install_dependent_plugin( $plugin ) {
		// Check if the plugin to install is 'revenue-pro'.
		if ( 'revenue-pro/revenue-pro.php' === $plugin ) {

			// Include required files if not already included.
			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}
			if ( ! class_exists( 'WP_Upgrader' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}
			if ( ! class_exists( 'Plugin_Installer_Skin' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php';
			}
			if ( ! class_exists( 'Plugin_Upgrader' ) ) {
				require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
			}

			// Check if the 'revenue' plugin is already active or installed.
			if ( is_plugin_active( 'revenue/revenue.php' ) || in_array( 'revenue/revenue.php', array_keys( get_plugins() ), true ) ) {
				return;
			}

			// Avoid header already sent issues.
			if ( headers_sent() ) {
				return;
			}

			// Download the plugin ZIP file
			$plugin_zip_url = 'https://downloads.wordpress.org/plugin/revenue.zip';
			$tmp_file       = download_url( $plugin_zip_url );

			// Check for download errors.
			if ( is_wp_error( $tmp_file ) ) {
				return $tmp_file;
			}

			// Install the plugin.
			$result = $this->install_plugin( $tmp_file );

			if ( file_exists( $tmp_file ) ) {
				wp_delete_file( $tmp_file );
			}

			// Check for installation errors.
			if ( is_wp_error( $result ) || ! $result ) {
				return is_wp_error( $result ) ? $result : new WP_Error( 'revenue_free_install_failed', __( 'WowRevenue could not be installed.', 'revenue-pro' ) );
			}

			// Activate the plugin.
			$activate_result = activate_plugin( 'revenue/revenue.php', admin_url( 'plugins.php' ) );

			// Check for activation errors.
			if ( is_wp_error( $activate_result ) ) {
				return $activate_result;
			}
		}
	}

	public function install_plugin( $tmp_file, $overwrite = false ) {
		// Initialize the upgrader with the plugin installer skin.
		$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );

		// Perform the installation.
		$install_result = $upgrader->install( $tmp_file, array( 'overwrite_package' => $overwrite ) );

		return $install_result;
	}
}
