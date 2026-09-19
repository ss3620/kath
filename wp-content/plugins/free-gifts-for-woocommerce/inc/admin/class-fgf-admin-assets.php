<?php

/**
 * Admin Assets
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
if (!class_exists('FGF_Admin_Assets')) {

	/**
	 * Class.
	 */
	class FGF_Admin_Assets {

		/**
		 * Suffix.
		 *
		 * @var string
		 */
		private static $suffix;

		/**
		 * Class Initialization.
		 */
		public static function init() {

			self::$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

			add_action('admin_enqueue_scripts', array( __CLASS__, 'external_js_files' ));
			add_action('admin_enqueue_scripts', array( __CLASS__, 'external_css_files' ));
		}

		/**
		 * Enqueue external JS files
		 */
		public static function external_css_files() {
			$screen_ids = fgf_page_screen_ids();
			$newscreenids = get_current_screen();
			$screenid = str_replace('edit-', '', $newscreenids->id);

			if (!in_array($screenid, $screen_ids)) {
				return;
			}

			wp_enqueue_style('fgf-admin', FGF_PLUGIN_URL . '/assets/css/admin.css', array( 'wc-admin-layout' ), FGF_VERSION);

			// Timepicker Addon.
			wp_enqueue_style('jquery-ui-datepicker-addon', FGF_PLUGIN_URL . '/assets/lib/timepicker-addon/jquery-ui-timepicker-addon' . self::$suffix . '.css', array(), FGF_VERSION);
		}

		/**
		 * Enqueue external JS files
		 */
		public static function external_js_files() {
			$suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

			$screen_ids = fgf_page_screen_ids();
			$newscreenids = get_current_screen();
			$screenid = str_replace('edit-', '', $newscreenids->id);

			$enqueue_array = array(
				'fgf-admin' => array(
					'callable' => array( 'FGF_Admin_Assets', 'admin' ),
					'restrict' => in_array($screenid, $screen_ids),
				),
				'fgf-select2' => array(
					'callable' => array( 'FGF_Admin_Assets', 'select2' ),
					'restrict' => in_array($screenid, $screen_ids),
				),
			);

			/**
			 * This hook is used to alter the admin assets.
			 *
			 * @since 1.0
			 */
			$enqueue_array = apply_filters('fgf_admin_assets', $enqueue_array);
			if (!fgf_check_is_array($enqueue_array)) {
				return;
			}

			foreach ($enqueue_array as $key => $enqueue) {
				if (!fgf_check_is_array($enqueue)) {
					continue;
				}

				if ($enqueue['restrict']) {
					call_user_func_array($enqueue['callable'], array( $suffix ));
				}
			}
		}

		/**
		 * Enqueue Admin end required JS files
		 */
		public static function admin( $suffix ) {
			// Media.
			wp_enqueue_media();

			// Admin
			wp_enqueue_script('fgf-admin', FGF_PLUGIN_URL . '/assets/js/admin.js', array( 'jquery', 'jquery-blockui', 'wc-backbone-modal' ), FGF_VERSION);
			wp_localize_script(
					'fgf-admin', 'fgf_admin_params', array(
				'manual_gift_nonce' => wp_create_nonce('fgf-manual-gift-nonce'),
				'preview_nonce' => wp_create_nonce('fgf-preview-nonce'),
				'fgf_master_log_info_nonce' => wp_create_nonce('fgf-master-log-info-nonce'),
				'delete_confirm_msg' => __('Are you sure you want to delete?', 'free-gifts-for-woocommerce'),
				'duplicate_confirm_msg' => __('Are you sure you want to duplicate?', 'free-gifts-for-woocommerce'),
				'products_empty_msg' => __('Please select at least one product to add as Gift item', 'free-gifts-for-woocommerce'),
				'media_title' => __('Select Image', 'free-gifts-for-woocommerce'),
				'media_button_text' => __('Upload', 'free-gifts-for-woocommerce'),
					)
			);

			wp_enqueue_script('fgf-admin-compatibility', FGF_PLUGIN_URL . '/assets/js/admin-compatibility.js', array( 'jquery' ), FGF_VERSION);

			// Rule.
			wp_enqueue_script('fgf-rule', FGF_PLUGIN_URL . '/assets/js/rule.js', array( 'jquery', 'jquery-blockui' ), FGF_VERSION);
			wp_localize_script(
					'fgf-rule', 'fgf_rule_params', array(
				'fgf_rules_nonce' => wp_create_nonce('fgf-rules-nonce'),
				'fgf_rules_drag_nonce' => wp_create_nonce('fgf-rules-drag-nonce'),
					)
			);
		}

		/**
		 * Enqueue select2 scripts and CSS
		 */
		public static function select2( $suffix ) {

			// Timepicker Addon.
			wp_enqueue_script('jquery-ui-timpicker-addon', FGF_PLUGIN_URL . '/assets/lib/timepicker-addon/jquery-ui-timepicker-addon' . self::$suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), FGF_VERSION);

			wp_enqueue_script('fgf-enhanced', FGF_PLUGIN_URL . '/assets/js/fgf-enhanced.js', array( 'jquery', 'select2', 'jquery-ui-datepicker' ), FGF_VERSION);
			wp_localize_script(
					'fgf-enhanced', 'fgf_enhanced_params', array(
				'i18n_no_matches' => __('No matches found', 'free-gifts-for-woocommerce'),
				'i18n_input_too_short_1' => __('Please enter 1 or more characters', 'free-gifts-for-woocommerce'),
				'i18n_input_too_short_n' => __('Please enter %qty% or more characters', 'free-gifts-for-woocommerce'),
				'i18n_input_too_long_1' => __('Please delete 1 character', 'free-gifts-for-woocommerce'),
				'i18n_input_too_long_n' => __('Please delete %qty% characters', 'free-gifts-for-woocommerce'),
				'i18n_selection_too_long_1' => __('You can only select 1 item', 'free-gifts-for-woocommerce'),
				'i18n_selection_too_long_n' => __('You can only select %qty% items', 'free-gifts-for-woocommerce'),
				'i18n_load_more' => __('Loading more results&hellip;', 'free-gifts-for-woocommerce'),
				'i18n_searching' => __('Searching&hellip;', 'free-gifts-for-woocommerce'),
				'search_nonce' => wp_create_nonce('fgf-search-nonce'),
				'ajaxurl' => FGF_ADMIN_AJAX_URL,
				'calendar_image' => WC()->plugin_url() . '/assets/images/calendar.png',
				'date_format' => fgf_convert_wp_date_format_php_to_jquery(),
					)
			);
		}
	}

	FGF_Admin_Assets::init();
}
