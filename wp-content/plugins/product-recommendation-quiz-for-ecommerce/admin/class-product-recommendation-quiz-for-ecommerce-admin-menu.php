<?php
/**
 * The admin menu and admin-area asset registration.
 *
 * @link       https://revenuehunt.com/
 * @since      1.0.0
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */

// Prevent direct access.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Registers the admin menu and admin-area assets.
 *
 * Delegates the settings-page rendering to the admin page unit.
 *
 * @package    Product_Recommendation_Quiz_For_Ecommerce
 * @subpackage Product_Recommendation_Quiz_For_Ecommerce/admin
 */
class Product_Recommendation_Quiz_For_Ecommerce_Admin_Menu {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * The settings page renderer (menu callback target).
	 *
	 * @since    2.3.9
	 * @var      Product_Recommendation_Quiz_For_Ecommerce_Admin_Page    $admin_page    Settings page renderer.
	 */
	private $admin_page;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string $plugin_name       The name of this plugin.
	 * @param      string $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->admin_page  = new Product_Recommendation_Quiz_For_Ecommerce_Admin_Page();
	}

	/**
	 * Register the stylesheets and JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_style(
			$this->plugin_name,
			plugin_dir_url( __FILE__ ) . 'css/product-recommendation-quiz-for-ecommerce-admin.css',
			array(),
			$this->version,
			'all'
		);

		$data_to_pass = array(
			'shop'           => PRQ_STORE_URL,
			'platform'       => 'woocommerce',
			'channel'        => 'wordpress',
			'plugin_version' => PRQ_PLUGIN_VERSION,
			'woo_version'    => PRQ_WOO_VERSION,
			'wp_version'     => PRQ_WP_VERSION,
		);

		wp_enqueue_script(
			$this->plugin_name,
			PRQ_ADMIN_URL . '/embed.js?shop=' . rawurlencode( PRQ_STORE_URL ),
			array(),
			PRQ_PLUGIN_VERSION,
			true
		);
		wp_localize_script( $this->plugin_name, 'js_vars', $data_to_pass );
	}

	/**
	 * Register the plugin admin menu.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function my_plugin_menu() {
		add_menu_page(
			__( 'Product Recommendation Quiz', 'product-recommendation-quiz-for-ecommerce' ),
			__( 'Product Quiz', 'product-recommendation-quiz-for-ecommerce' ),
			'manage_options',
			'prqfw',
			array( $this->admin_page, 'prquiz_options' ),
			'dashicons-format-chat',
			58
		);
	}
}
