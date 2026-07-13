<?php

/**
 * Frontend Assets,
 * 
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
if (!class_exists('FGF_Fronend_Assets')) {

	/**
	 * Class.
	 * 
	 * @since 1.0.0
	 */
	class FGF_Fronend_Assets {

		/**
		 * Suffix.
		 * 
		 * @since 1.0.0
		 * @var string
		 */
		private static $suffix;

		/**
		 * Scripts.
		 *
		 * @since 5.2.0
		 * @var array
		 */
		private static $scripts = array();

		/**
		 * Styles.
		 *
		 * @since 5.2.0
		 * @var array
		 */
		private static $styles = array();

		/**
		 * Localized scripts.
		 *
		 * @since 10.2.0
		 * @var array
		 */
		private static $wp_localized_scripts = array();

		/**
		 * In Footer.
		 * 
		 * @since 1.0.0
		 * @var bool
		 */
		private static $in_footer = false;

		/**
		 * Class Initialization.
		 * 
		 * @since 1.0.0
		 */
		public static function init() {
			self::$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

			// Enqueue script in footer.
			if ('2' == get_option('fgf_settings_frontend_enqueue_scripts_type')) {
				self::$in_footer = true;
			}

			add_action('wp_enqueue_scripts', array( __CLASS__, 'load_scripts' ));
			add_action('wp_print_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5);
			add_action('wp_print_footer_scripts', array( __CLASS__, 'localize_printed_scripts' ), 5);
		}

		/**
		 * Get the default scripts to register.
		 * 
		 * @since 11.0.0
		 * @return array
		 */
		private static function get_default_scripts() {
			/**
			 * This hook is used to alter the default register scripts.
			 * 
			 * @since 11.0.0
			 */
			return apply_filters('fgf_default_register_scripts',
					array(
						'fgf-frontend' => array(
							'src' => self::get_asset_url('assets/js/frontend.js'),
							'deps' => array( 'jquery-blockui' ),
						),
						'owl-carousel' => array(
							'src' => self::get_asset_url('assets/js/owl.carousel' . self::$suffix . '.js'),
						),
						'fgf-owl-carousel' => array(
							'src' => self::get_asset_url('assets/js/owl-carousel-enhanced.js'),
							'deps' => array( 'owl-carousel' ),
						),
						'lightcase' => array(
							'src' => self::get_asset_url('assets/js/lightcase' . self::$suffix . '.js'),
						),
						'fgf-lightcase' => array(
							'src' => self::get_asset_url('assets/js/fgf-lightcase-enhanced.js'),
							'deps' => array( 'lightcase' ),
						),
			));
		}

		/**
		 * Get the default styles to register.
		 * 
		 * @since 11.0.0
		 * @return array
		 */
		private static function get_default_styles() {
			/**
			 * This hook is used to alter the default register styles.
			 * 
			 * @since 11.0.0
			 */
			return apply_filters('fgf_default_register_styles',
					array(
						'fgf-frontend' => array(
							'src' => self::get_asset_url('assets/css/frontend.css'),
						),
						'owl-carousel' => array(
							'src' => self::get_asset_url('assets/css/owl.carousel' . self::$suffix . '.css'),
						),
						'fgf-owl-carousel' => array(
							'src' => self::get_asset_url('assets/css/owl-carousel-enhanced.css'),
							'deps' => array( 'owl-carousel' ),
						),
						'lightcase' => array(
							'src' => self::get_asset_url('assets/css/lightcase' . self::$suffix . '.css'),
						),
			));
		}

		/**
		 * Get script data.
		 * 
		 * @since 11.0.0
		 * @param string $handle
		 * @return array/false
		 */
		public static function get_script_data( $handle ) {
			switch ($handle) {
				case 'fgf-frontend':
					$params = array(
						'is_block_cart' => fgf_is_block_cart(),
						'is_block_checkout' => fgf_is_block_checkout(),
						'gift_products_pagination_nonce' => wp_create_nonce('fgf-gift-products-pagination'),
						'gift_product_nonce' => wp_create_nonce('fgf-gift-product'),
						'ajaxurl' => FGF_ADMIN_AJAX_URL,
						'current_page_url' => get_permalink(),
						'add_to_cart_link' => esc_url(add_query_arg(array( 'fgf_gift_product' => '%s', 'fgf_rule_id' => '%s', 'fgf_buy_product_id' => '%s', 'fgf_coupon_id' => '%s' ), get_permalink())),
						'ajax_add_to_cart' => get_option('fgf_settings_enable_ajax_add_to_cart', 'no'),
						'quantity_field_enabled' => get_option('fgf_settings_gift_product_quantity_field_enabled', '2'),
						'dropdown_add_to_cart_behaviour' => get_option('fgf_settings_dropdown_add_to_cart_behaviour'),
						'dropdown_display_type' => get_option('fgf_settings_gift_dropdown_display_type'),
						'add_to_cart_alert_message' => get_option('fgf_settings_gift_product_dropdown_valid_message', 'Please select a Gift'),
					);
					break;

				case 'fgf-owl-carousel':
					$params = fgf_get_carousel_options();
					break;

				default:
					$params = false;
					break;
			}

			return $params;
		}

		/**
		 * Register and enqueue frontend scripts.
		 * 
		 * @since 11.0.0
		 */
		public static function load_scripts() {
			global $post;

			self::register_scripts();
			self::register_styles();

			// Enqueue scripts in cart and checkout page.
			if (is_cart() || is_checkout()|| is_singular()) {
				self::enqueue_registered_scripts();
				self::enqueue_registered_styles();
			}

			// Enqueue scripts in short code page.
			if (is_object($post) && !empty($post->post_content) && strstr($post->post_content, '[fgf_')) {
				self::enqueue_registered_scripts();
				self::enqueue_registered_styles();
			}

			self::add_inline_style();
		}

		/**
		 * Localize scripts only when enqueued.
		 * 
		 * @since 11.0.0
		 */
		public static function localize_printed_scripts() {
			foreach (self::$scripts as $handle) {
				self::localize_script($handle);
			}
		}

		/**
		 * Register all scripts.
		 * 
		 * @since 11.0.0 
		 */
		private static function register_scripts() {
			$default_scripts = self::get_default_scripts();
			// Returns if there is no scripts to register.
			if (!fgf_check_is_array($default_scripts)) {
				return;
			}

			foreach ($default_scripts as $handle => $script) {
				if (!isset($script['src'])) {
					continue;
				}

				$deps = isset($script['deps']) ? array_merge(array( 'jquery' ), $script['deps']) : array( 'jquery' );
				$version = isset($script['version']) ? $script['version'] : FGF_VERSION;
				$in_footer = isset($script['in_footer']) ? $script['in_footer'] : self::$in_footer;
				if (!wp_register_script($handle, $script['src'], $deps, $version, $in_footer)) {
					continue;
				}

				self::$scripts[] = $handle;
			}
		}

		/**
		 * Register all styles.
		 * 
		 * @since 11.0.0 
		 */
		private static function register_styles() {
			$default_styles = self::get_default_styles();
			// Returns if there is no styles to register.
			if (!fgf_check_is_array($default_styles)) {
				return;
			}

			foreach ($default_styles as $handle => $style) {
				if (!isset($style['src'])) {
					continue;
				}

				$deps = isset($style['deps']) ? $style['deps'] : array();
				$version = isset($style['version']) ? $style['version'] : FGF_VERSION;
				$media = isset($style['media']) ? $style['media'] : 'all';
				$has_rtl = isset($style['has_rtl']) ? $style['has_rtl'] : false;
				if (!wp_register_style($handle, $style['src'], $deps, $version, $media)) {
					continue;
				}

				self::$styles[] = $handle;

				if ($has_rtl) {
					wp_style_add_data($handle, 'rtl', 'replace');
				}
			}
		}

		/**
		 * Enqueue all registered scripts.
		 * 
		 * @since 11.0.0
		 */
		private static function enqueue_registered_scripts() {
			foreach (self::$scripts as $handle) {
				self::enqueue_script($handle);
			}
		}

		/**
		 * Enqueue script.
		 * 
		 * @param string $handle
		 * @since 11.0.0
		 */
		private static function enqueue_script( $handle ) {
			if (!wp_script_is($handle, 'registered')) {
				return;
			}

			wp_enqueue_script($handle);
		}

		/**
		 * Enqueue all registered styles.
		 * 
		 * @since 11.0.0
		 */
		private static function enqueue_registered_styles() {
			foreach (self::$styles as $handle) {
				self::enqueue_style($handle);
			}
		}

		/**
		 * Enqueue style.
		 * 
		 * @param string $handle
		 * @since 11.0.0
		 */
		private static function enqueue_style( $handle ) {
			if (!wp_script_is($handle, 'registered')) {
				return;
			}

			wp_enqueue_style($handle);
		}

		/**
		 * Add Inline style.
		 * 
		 * @since 1.0.0
		 */
		private static function add_inline_style() {
			/**
			 * This hook is used to alter the custom CSS.
			 * 
			 * @since 11.3.0
			 */
			$contents = apply_filters('fgf_custom_css', get_option('fgf_settings_custom_css', ''));
			if (!$contents) {
				return;
			}

			wp_register_style('fgf-inline-style', false, array(), FGF_VERSION); // phpcs:ignore
			wp_enqueue_style('fgf-inline-style');

			//Add custom css as inline style.
			wp_add_inline_style('fgf-inline-style', $contents);
		}

		/**
		 * Localize the enqueued script.
		 * 
		 * @since 11.0.0
		 * @param string $handle
		 * @return null
		 */
		private static function localize_script( $handle ) {
			// Return if already localized script or not enqueued script.
			if (in_array($handle, self::$wp_localized_scripts, true) || !wp_script_is($handle)) {
				return;
			}

			// Get the data for current script.
			$data = self::get_script_data($handle);
			if (!$data) {
				return;
			}

			$name = str_replace('-', '_', $handle) . '_params';

			/**
			 * This hook is used to alter the script data.
			 * 
			 * @since 11.0.0
			 */
			if (wp_localize_script($handle, $name, apply_filters($name, $data))) {
				self::$wp_localized_scripts[] = $handle;
			}
		}

		/**
		 * Get asset URL.
		 *
		 * @since 11.0.0
		 * @param string $path Assets path.
		 * @return string
		 */
		private static function get_asset_url( $path ) {
			/**
			 * This hook is used to alter the asset URL.
			 * 
			 * @since 11.0.0
			 */
			return apply_filters('fgf_get_asset_url', FGF_PLUGIN_URL . '/' . $path, $path);
		}
	}

	FGF_Fronend_Assets::init();
}
