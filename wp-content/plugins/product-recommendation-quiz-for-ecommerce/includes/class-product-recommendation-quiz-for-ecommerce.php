<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://revenuehunt.com/
 * @since      1.0.0
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/includes
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/includes
 */
class Product_Recommendation_Quiz_For_Ecommerce {

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Sets the plugin name and version and defines the runtime constants used
	 * throughout the plugin. Hook registration happens in run().
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->version = PRQ_PLUGIN_VERSION;

		// Assign $storeurl based on the extracted domain or fall back to get_site_url()
		$current_url = $this->get_current_url_sanitized();
		$storeurl    = $this->extract_domain_and_path( $current_url ) ? $this->extract_domain_and_path( $current_url ) : get_site_url();

		// Remove 'http://' or 'https://'
		$storeurl = preg_replace( '#^https?://#', '', $storeurl );

		/* DEFINE CONSTANTS */
		define( 'PRQ_STORE_URL', $storeurl );
		define( 'PRQ_WOO_VERSION', $this->get_woo_version() );
		define( 'PRQ_WP_VERSION', get_bloginfo( 'version' ) );

		if ( self::is_development_environment( PRQ_STORE_URL ) ) {
			// Development environment
			// ssh -R 80:localhost:3000 ssh.localhost.run
			define( 'PRQ_API_URL', 'https://xxx-xxx.localhost.run' );
			define( 'PRQ_ADMIN_URL', 'http://localhost:9528' );
		} else {
			// Production environment
			define( 'PRQ_API_URL', 'https://api.revenuehunt.com' );
			define( 'PRQ_ADMIN_URL', 'https://admin.revenuehunt.com' );
		}

		$this->plugin_name = 'product-recommendation-quiz-for-ecommerce';
	}

	/**
	 * Determine if running in a local development environment.
	 *
	 * Uses domain pattern detection to identify common local development setups
	 * (e.g., .local, .test, localhost). This determines whether to use the
	 * local development API or production RevenueHunt API.
	 *
	 * Note: We intentionally do NOT check wp_get_environment_type() here because
	 * even if a customer's WordPress is set to 'development' mode, they still
	 * need to connect to the production RevenueHunt API. The local API URL is
	 * only for plugin developers running on actual local domains.
	 *
	 * @since 2.2.15
	 * @param string $store_url The store URL to check. Defaults to PRQ_STORE_URL if empty.
	 * @return bool True if local development environment, false otherwise.
	 */
	public static function is_development_environment( $store_url = '' ) {
		// Use PRQ_STORE_URL constant if no URL provided
		if ( empty( $store_url ) && defined( 'PRQ_STORE_URL' ) ) {
			$store_url = PRQ_STORE_URL;
		}

		// Note: We intentionally do NOT check wp_get_environment_type() here.
		// Even if a customer's WordPress is set to 'development' or 'local' environment,
		// they still need to connect to the production RevenueHunt API.
		// The development API URL (localhost.run) is only for plugin developers
		// running the plugin on an actual local domain.

		// Domain detection for common local development patterns
		// Note: .dev is NOT included because it's now a legitimate production TLD (owned by Google)
		$dev_patterns = array(
			'/\.local$/i',      // .local domains (Local by Flywheel, etc.)
			'/\.test$/i',       // .test domains (Laravel Valet, etc.)
			'/localhost/i',     // localhost
			'/\.ddev\.site$/i', // DDEV
			'/\.lndo\.site$/i', // Lando
		);

		foreach ( $dev_patterns as $pattern ) {
			if ( preg_match( $pattern, $store_url ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the current URL with sanitization.
	 *
	 * @since 1.0.0
	 * @return string|false The sanitized current URL or false if host is not available.
	 */
	private function get_current_url_sanitized() {
		// Use WordPress's is_ssl() to check for HTTPS
		$scheme = is_ssl() ? 'https' : 'http';

		$host        = false;
		$request_uri = '';

		// Use wp_unslash() and esc_url_raw() to sanitize the host and request URI
		if ( isset( $_SERVER['HTTP_HOST'] ) ) {
			$host = esc_url_raw( wp_unslash( $_SERVER['HTTP_HOST'] ) );
		}
		if ( isset( $_SERVER['REQUEST_URI'] ) ) {
			$request_uri = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		}

		if ( ! $host ) {
			return false;
		}

		return $scheme . '://' . $host . $request_uri;
	}

	/**
	 * Extract domain and path from a URL.
	 *
	 * @since 1.0.0
	 * @param string $url The URL to extract from.
	 * @return string|false The extracted domain and path or false if not found.
	 */
	private function extract_domain_and_path( $url ) {
		if ( ! $url ) {
			return false;
		}

		$pattern = '/https?:\/\/(.*?)\/wp-admin\//';
		preg_match( $pattern, $url, $matches );

		return isset( $matches[1] ) ? $matches[1] : false;
	}

	/**
	 * Get the WooCommerce version.
	 *
	 * @since 1.0.0
	 * @return string|null The WooCommerce version or null if not found.
	 */
	private function get_woo_version() {
		// If get_plugins() isn't available, require it
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Create the plugins folder and file variables
		$plugin_folder = get_plugins( '/woocommerce' );
		$plugin_file   = 'woocommerce.php';

		// If the plugin version number is set, return it
		if ( isset( $plugin_folder[ $plugin_file ]['Version'] ) ) {
			return $plugin_folder[ $plugin_file ]['Version'];
		} else {
			// Otherwise return null
			return null;
		}
	}

	/**
	 * Register all of the plugin's WordPress hooks.
	 *
	 * Collaborators are instantiated here and their hooks registered directly;
	 * WordPress holds the callback references for the rest of the request, so
	 * the local instances persist. Classes are pulled in on demand by the
	 * plugin autoloader (registered in the bootstrap file).
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$i18n      = new Product_Recommendation_Quiz_For_Ecommerce_I18n();
		$menu      = new Product_Recommendation_Quiz_For_Ecommerce_Admin_Menu( $this->plugin_name, $this->version );
		$embed     = new Product_Recommendation_Quiz_For_Ecommerce_Front_Embed_Script( $this->plugin_name, $this->version );
		$delivery  = Product_Recommendation_Quiz_For_Ecommerce_Delivery_Resolver::resolve( $this->plugin_name, $this->version );
		$shortcode = new Product_Recommendation_Quiz_For_Ecommerce_Front_Shortcode( $delivery );
		$block     = new Product_Recommendation_Quiz_For_Ecommerce_Front_Block( $delivery );
		$health    = new Product_Recommendation_Quiz_For_Ecommerce_Site_Health();
		$embeds    = new Product_Recommendation_Quiz_For_Ecommerce_Admin_Embeds_Page();
		$global    = new Product_Recommendation_Quiz_For_Ecommerce_Front_Global_Embed();
		$blocks    = new Product_Recommendation_Quiz_For_Ecommerce_Front_Embed_Blocks();
		$operator  = new Product_Recommendation_Quiz_For_Ecommerce_Admin_Operator_Capture();

		add_action( 'plugins_loaded', array( $i18n, 'load_plugin_textdomain' ) );
		add_action( 'admin_enqueue_scripts', array( $menu, 'enqueue_scripts' ) );
		add_action( 'admin_menu', array( $menu, 'my_plugin_menu' ) );
		add_action( 'admin_menu', array( $embeds, 'add_menu' ) );
		add_action( 'admin_init', array( $embeds, 'register_settings' ) );
		add_action( 'admin_init', array( $operator, 'maybe_capture' ) );
		add_action( 'wp_enqueue_scripts', array( $embed, 'enqueue_scripts' ) );
		add_filter( 'script_loader_tag', array( $embed, 'add_async_to_embed_script' ), 10, 3 );
		add_action( 'wp_footer', array( $global, 'render' ) );
		add_action( 'init', array( $shortcode, 'register' ) );
		add_action( 'init', array( $block, 'register' ) );
		add_action( 'init', array( $blocks, 'register' ) );
		add_filter( 'site_status_tests', array( $health, 'register_tests' ) );
	}

}
