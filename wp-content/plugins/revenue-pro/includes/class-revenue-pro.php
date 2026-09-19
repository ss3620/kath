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
	public $version = '2.1.4';

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

		add_action( 'activated_plugin', array( $this, 'install_dependent_plugin' ) );

		add_action( 'admin_init', array( $this, 'may_by_update_free' ) );

		// Handle one-time activation redirect to settings/license page.
		add_action( 'admin_init', array( $this, 'pro_activation_redirect' ) );

		$this->include_ajax();

		$this->include_notice();

		add_action( 'revenue_loaded', array( $this, 'init_plugin' ) );

		// Register admin notices to container and load notices.

		add_action( 'plugins_loaded', array( $this, 'revenue_not_loaded' ), 11 );
	}

	public function may_by_update_free() {

		// Minimum free version this pro version requires.
		$required_free_version = '2.2.8';

		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Path to the main plugin file.
		$plugin_file = WP_PLUGIN_DIR . '/revenue/revenue.php';

		// Missing free plugin is handled by revenue_not_loaded() notices, not here.
		if ( ! file_exists( $plugin_file ) ) {
			return;
		}

		if ( ! function_exists( 'get_plugin_data' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$plugin_data     = get_plugin_data( $plugin_file );
		$current_version = isset( $plugin_data['Version'] ) ? $plugin_data['Version'] : '';

		if ( empty( $current_version ) || version_compare( $current_version, $required_free_version, '>=' ) ) {
			return;
		}

		if ( get_transient( 'revenue_free_version_updating' ) ) {
			return;
		}

		// Guard against concurrent requests before any file operation starts.
		set_transient( 'revenue_free_version_updating', true, HOUR_IN_SECONDS );

		if ( ! function_exists( 'plugins_api' ) ) {
			include ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		if ( ! class_exists( 'WP_Upgrader' ) ) {
			include ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! class_exists( 'Plugin_Installer_Skin' ) ) {
			include ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php';
		}
		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			include ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
		}
		include_once ABSPATH . 'wp-admin/includes/file.php';

		// Download the new version before touching the installed plugin, so a
		// failed download never leaves the site without the free plugin.
		$plugin_zip_url = 'https://downloads.wordpress.org/plugin/revenue.zip';
		$tmp_file       = download_url( $plugin_zip_url );

		if ( is_wp_error( $tmp_file ) ) {
			error_log( 'WowRevenue Pro: error downloading free plugin update: ' . $tmp_file->get_error_message() );
			return;
		}

		$plugin_slug = 'revenue/revenue.php';

		// Overwrite install replaces the files in place; no deactivate/delete needed.
		$result = $this->install_plugin( $tmp_file, true );

		if ( file_exists( $tmp_file ) ) {
			wp_delete_file( $tmp_file );
		}

		if ( is_wp_error( $result ) || ! $result ) {
			$message = is_wp_error( $result ) ? $result->get_error_message() : 'installation failed';
			error_log( 'WowRevenue Pro: error updating free plugin: ' . $message );
			return;
		}

		if ( ! is_plugin_active( $plugin_slug ) ) {
			activate_plugin( $plugin_slug );
		}

		// Only clear the guard once the installed version actually satisfies the
		// requirement; otherwise every admin request would retry the install.
		$plugin_data = get_plugin_data( $plugin_file );

		if ( version_compare( $plugin_data['Version'], $required_free_version, '>=' ) ) {
			delete_transient( 'revenue_free_version_updating' );
			error_log( 'WowRevenue Pro: free plugin updated to version ' . $plugin_data['Version'] );
		} else {
			error_log( 'WowRevenue Pro: free plugin is still ' . $plugin_data['Version'] . ' after update; ' . $required_free_version . ' or later is required.' );
		}
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
		if ( self::$instance === null ) {
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

		// add_action('admin_init',[ $this, 'install_dependent_plugin' ] );

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
		// define( 'REVENUE_PRO_URL', plugin_dir_url( __FILE__ ) );
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
		include_once REVENUE_PRO_PATH . 'includes/class-revenue-pro-campaign.php';

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

		Revenue_Mix_Match::instance()->init();
		Revenue_Frequently_Bought_Together::instance()->init();
		Revenue_Double_Order::instance()->init();
		Revenue_Spending_Goal::instance()->init();
	}


	public function load_scripts() {
		// wp_enqueue_style( 'revennue-pro-style', REVENUE_PRO_URL . 'assets/css/revenue-pro.css', array(), REVENUE_PRO_VER );
		// wp_enqueue_script( 'evennue-pro-script', REVENUE_PRO_URL . 'assets/js/revenue-pro.js', array( 'wp-api-fetch', 'jquery' ), REVENUE_PRO_VER, true );
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
	 * Handles scenerios when WooCommerce is not active
	 *
	 * @return void
	 * @since  1.0.0
	 */
	public function revenue_not_loaded() {
		if ( did_action( 'revenue_loaded' ) || ! is_admin() ) {
			return;
		}

		if ( $this->is_revenue_installed() && ! $this->has_revenue() ) {
			// Should Activate Notice.

			add_action( 'admin_notices', array( RevenuePro\Revenue_Pro_Notice::class, 'get_wowrevenue_not_active_notice' ) );

		} elseif ( ! $this->is_revenue_installed() ) {
			// Should Show Installation Notice.
			add_action( 'admin_notices', array( RevenuePro\Revenue_Pro_Notice::class, 'get_wowrevenue_not_installed_notice' ) );
		}
	}

	/**
	 * Placeholder for activation function
	 *
	 * Nothing being called here yet.
	 */
	public function activate() {
		if ( ! $this->has_revenue() ) {
		}

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

		// Prevent redirect during bulk plugin activation.
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
				include ABSPATH . 'wp-admin/includes/plugin-install.php';
			}
			if ( ! class_exists( 'WP_Upgrader' ) ) {
				include ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			}
			if ( ! class_exists( 'Plugin_Installer_Skin' ) ) {
				include ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php';
			}
			if ( ! class_exists( 'Plugin_Upgrader' ) ) {
				include ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
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
				error_log( 'WowRevenue Pro: error downloading the free plugin: ' . $tmp_file->get_error_message() );
				return;
			}

			// Install the plugin.
			$result = $this->install_plugin( $tmp_file );

			if ( file_exists( $tmp_file ) ) {
				wp_delete_file( $tmp_file );
			}

			// Check for installation errors.
			if ( is_wp_error( $result ) || ! $result ) {
				error_log( 'WowRevenue Pro: error installing the free plugin.' );
				return;
			}

			// Activate the plugin.
			$activate_result = activate_plugin( 'revenue/revenue.php', admin_url( 'plugins.php' ) );

			// Check for activation errors.
			if ( is_wp_error( $activate_result ) ) {
				error_log( 'WowRevenue Pro: error activating the free plugin: ' . $activate_result->get_error_message() );
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
