<?php

/**
 * Free Gifts for WooCommerce Main Class.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FP_Free_Gift')) {

	/**
	 * Main FP_Free_Gift Class.
	 * */
	final class FP_Free_Gift {

		/**
		 * Version.
		 *
		 * @var string
		 * */
		private $version = '12.1.0';

		/**
		 * Locale.
		 *
		 * @var string
		 * */
		private $locale = 'free-gifts-for-woocommerce';

		/**
		 * Folder Name.
		 *
		 * @var string
		 * */
		private $folder_name = 'free-gifts-for-woocommerce';

		/**
		 * WC minimum version.
		 *
		 * @var string
		 */
		public static $wc_minimum_version = '3.0.0';

		/**
		 * WP minimum version.
		 *
		 * @var string
		 */
		public static $wp_minimum_version = '4.6.0';

		/**
		 * Notifications.
		 *
		 * @var array
		 * */
		protected $notifications;

		/**
		 * The single instance of the class.
		 *
		 * @var object
		 * */
		protected static $_instance = null;

		/**
		 * Load FP_Free_Gift Class in Single Instance.
		 */
		public static function instance() {
			if (is_null(self::$_instance)) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}

		/* Cloning has been forbidden */

		public function __clone() {
			_doing_it_wrong(__FUNCTION__, 'You are not allowed to perform this action!!!', esc_html($this->version));
		}

		/**
		 * Unserialize the class data has been forbidden.
		 * */
		public function __wakeup() {
			_doing_it_wrong(__FUNCTION__, 'You are not allowed to perform this action!!!', esc_html($this->version));
		}

		/**
		 * Constructor.
		 * */
		public function __construct() {
			$this->define_constants();
			$this->include_files();
			$this->init_hooks();
		}

		/**
		 * Load plugin the translate files.
		 * */
		private function load_plugin_textdomain() {
			if (function_exists('determine_locale')) {
				$locale = determine_locale();
			} else {
				// @todo Remove when start supporting WP 5.0 or later.
				$locale = is_admin() ? get_user_locale() : get_locale();
			}
			/**
			 * This hook is used to alter the plugin locale.
			 *
			 * @since 1.0
			 */
			$locale = apply_filters('plugin_locale', $locale, FGF_LOCALE);

			// Unload the text domain if other plugins/themes loaded the same text domain by mistake.
			unload_textdomain(FGF_LOCALE, true);
			// Load the text domain from the "wp-content" languages folder. we have handles the plugin folder in languages folder for easily handle it.
			load_textdomain(FGF_LOCALE, WP_LANG_DIR . '/' . FGF_FOLDER_NAME . '/' . FGF_LOCALE . '-' . $locale . '.mo');
			// Load the text domain from the current plugin languages folder.
			load_plugin_textdomain(FGF_LOCALE, false, dirname(plugin_basename(FGF_PLUGIN_FILE)) . '/languages');
		}

		/**
		 * Prepare the constants value array.
		 * */
		private function define_constants() {

			$constant_array = array(
				'FGF_VERSION' => $this->version,
				'FGF_LOCALE' => $this->locale,
				'FGF_FOLDER_NAME' => $this->folder_name,
				'FGF_ABSPATH' => dirname(FGF_PLUGIN_FILE) . '/',
				'FGF_ADMIN_URL' => admin_url('admin.php'),
				'FGF_ADMIN_AJAX_URL' => admin_url('admin-ajax.php'),
				'FGF_PLUGIN_SLUG' => plugin_basename(FGF_PLUGIN_FILE),
				'FGF_PLUGIN_PATH' => untrailingslashit(plugin_dir_path(FGF_PLUGIN_FILE)),
				'FGF_PLUGIN_URL' => untrailingslashit(plugins_url('/', FGF_PLUGIN_FILE)),
			);

			/**
			 * This hook is used to alter the constants.
			 *
			 * @since 1.0
			 */
			$constant_array = apply_filters('fgf_define_constants', $constant_array);

			if (is_array($constant_array) && !empty($constant_array)) {
				foreach ($constant_array as $name => $value) {
					$this->define_constant($name, $value);
				}
			}
		}

		/**
		 * Define the Constants value.
		 * */
		private function define_constant( $name, $value ) {
			if (!defined($name)) {
				define($name, $value);
			}
		}

		/**
		 * Include required files.
		 * */
		private function include_files() {
			// Function.
			include_once FGF_ABSPATH . 'inc/fgf-common-functions.php';

			// Abstract classes.
			include_once FGF_ABSPATH . 'inc/abstracts/abstract-fgf-post.php';
			// Classes.
			include_once FGF_ABSPATH . 'inc/notifications/class-fgf-notification-instances.php';
			include_once FGF_ABSPATH . 'inc/compatibility/class-fgf-compatibility-instances.php';

			include_once FGF_ABSPATH . 'inc/class-fgf-register-post-types.php';
			include_once FGF_ABSPATH . 'inc/class-fgf-register-post-status.php';

			include_once FGF_ABSPATH . 'inc/class-fgf-install.php';
			include_once FGF_ABSPATH . 'inc/class-fgf-date-time.php';
			include_once FGF_ABSPATH . 'inc/privacy/class-fgf-privacy.php';

			// Coupon.
			include_once FGF_ABSPATH . 'inc/class-fgf-coupon.php';

			// Query.
			include_once FGF_ABSPATH . 'inc/class-fgf-query.php';

			include_once FGF_ABSPATH . 'inc/class-fgf-order-handler.php';
			include_once FGF_ABSPATH . 'inc/class-fgf-cron-handler.php';

			// Entity.
			include_once FGF_ABSPATH . 'inc/entity/class-fgf-rule.php';
			include_once FGF_ABSPATH . 'inc/entity/class-fgf-master-log.php';

			include_once FGF_ABSPATH . 'inc/rest-api/class-fgf-rest-api-handler.php';

			// Block compatibility.
			include_once FGF_ABSPATH . 'inc/wc-blocks/class-fgf-wc-blocks-compatibility.php';

			if (is_admin()) {
				$this->include_admin_files();
			}

			if (!is_admin() || defined('DOING_AJAX')) {
				$this->include_frontend_files();
			}
		}

		/**
		 * Include admin files.
		 * */
		private function include_admin_files() {
			include_once FGF_ABSPATH . 'inc/admin/class-fgf-admin-assets.php';
			include_once FGF_ABSPATH . 'inc/admin/class-fgf-admin-ajax.php';
			include_once FGF_ABSPATH . 'inc/admin/class-fgf-admin-post-handler.php';
			include_once FGF_ABSPATH . 'inc/admin/menu/class-fgf-menu-management.php';
			include_once FGF_ABSPATH . 'inc/class-fgf-manual-gift-order-handler.php';
		}

		/**
		 * Include frontend files.
		 * */
		private function include_frontend_files() {
			include_once FGF_ABSPATH . 'inc/frontend/class-fgf-frontend-assets.php';
			include_once FGF_ABSPATH . 'inc/frontend/class-fgf-frontend.php';
			include_once FGF_ABSPATH . 'inc/frontend/class-fgf-shortcodes.php';
			include_once FGF_ABSPATH . 'inc/frontend/class-fgf-gift-products-handler.php';
			include_once FGF_ABSPATH . 'inc/frontend/class-fgf-cart-handler.php';
			include_once FGF_ABSPATH . 'inc/frontend/class-fgf-notices-handler.php';
			include_once FGF_ABSPATH . 'inc/frontend/class-fgf-rule-handler.php';
			include_once FGF_ABSPATH . 'inc/frontend/class-fgf-bogo-rule-handler.php';
		}

		/**
		 * Define the hooks.
		 * */
		private function init_hooks() {
			// WC compatibility to the plugin.
			add_action('before_woocommerce_init', array( $this, 'declare_WC_compatibility' ));

			// Init the plugin.
			add_action('init', array( $this, 'init' ));

			add_action('plugins_loaded', array( $this, 'plugins_loaded' ));
			// Register the plugin.
			register_activation_hook(FGF_PLUGIN_FILE, array( 'FGF_Install', 'install' ));
		}

		/**
		 * Declare the plugin is compatibility with WC features.
		 * 
		 * @since 11.0.0
		 * @return void
		 */
		public function declare_WC_compatibility() {
			// HPOS compatibility.
			$this->declare_WC_HPOS_compatibility();

			// Block compatibility.
			$this->declare_WC_Block_compatibility();
		}

		/**
		 * Declare the plugin is compatibility with WC HPOS.
		 * 
		 * @since 9.9.0
		 * @return void
		 */
		public function declare_WC_HPOS_compatibility() {
			if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', FGF_PLUGIN_FILE, true);
			}
		}

		/**
		 * Declare the plugin is compatibility with WC block.
		 * 
		 * @since 11.0.0
		 * @return void
		 */
		public function declare_WC_Block_compatibility() {
			if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', FGF_PLUGIN_FILE, true);
			}
		}

		/**
		 * Init.
		 * */
		public function init() {
			$this->load_plugin_textdomain();
		}

		/**
		 * Plugins Loaded.
		 * */
		public function plugins_loaded() {
			/**
			 * This hook is used to do extra action before plugin loaded.
			 *
			 * @since 1.0
			 */
			do_action('fgf_before_plugin_loaded');

			$this->notifications = FGF_Notification_Instances::get_notifications();
			FGF_Compatibility_Instances::instance();
			/**
			 * This hook is used to do extra action after plugin loaded.
			 *
			 * @since 1.0
			 */
			do_action('fgf_after_plugin_loaded');
		}

		/**
		 * Templates.
		 * */
		public function templates() {
			return FGF_PLUGIN_PATH . '/templates/';
		}

		/**
		 * Notifications instances.
		 * */
		public function notifications() {
			return $this->notifications;
		}

		/**
		 * Compatibility instances.
		 * */
		public function compatibility() {
			return FGF_Compatibility_Instances::instance();
		}
	}

}
