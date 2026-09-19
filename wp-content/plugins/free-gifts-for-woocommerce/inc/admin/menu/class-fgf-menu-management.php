<?php

/*
 * Menu Management
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Menu_Management')) {

	include_once 'class-fgf-settings.php' ;

	/**
	 * FGF_Menu_Management Class.
	 */
	class FGF_Menu_Management {

		/**
		 * Plugin slug.
		 *
		 * @var string
		 */
		protected static $plugin_slug = 'fgf';

		/**
		 * Menu slug.
		 *
		 * @var string
		 */
		protected static $menu_slug = 'woocommerce';

		/**
		 * Settings slug.
		 *
		 * @var string
		 */
		protected static $settings_slug = 'fgf_settings';

		/**
		 * Class initialization.
		 */
		public static function init() {
			add_action('admin_menu', array( __CLASS__, 'add_menu_pages' ));
			add_filter('woocommerce_screen_ids', array( __CLASS__, 'add_custom_wc_screen_ids' ), 9, 1);
			//Sanitize the settings value.
			add_filter('woocommerce_admin_settings_sanitize_option', array( 'FGF_Settings', 'save_fields' ), 10, 3);
			// Set screen option.
			add_filter('set-screen-option', array( __CLASS__, 'set_screen_option' ), 10, 3);
			// May be add the notices.
			add_action('admin_notices', array( __CLASS__, 'maybe_add_notices' ), 10);
		}

		/**
		 * May be add notices in the free gift page.
		 *
		 * @since 10.4.0
		 */
		public static function maybe_add_notices() {
			// Return if current page is not free products page.
			if (!in_array(fgf_current_page_screen_id(), fgf_page_screen_ids())) {
				return;
			}

			// Return if the gifts count exists.
			if ('' !== get_option('fgf_settings_gifts_count_per_order')) {
				return;
			}

			FGF_Settings::add_error(__('Please set any value as per your need in the Maximum Gifts in an Order field to proceed furthermore.', 'free-gifts-for-woocommerce'));
		}

		/**
		 * Add Custom Screen IDs in WooCommerce
		 */
		public static function add_custom_wc_screen_ids( $wc_screen_ids ) {
			$screen_ids = fgf_page_screen_ids();

			$newscreenids = get_current_screen();
			$screenid = str_replace('edit-', '', $newscreenids->id);

			// return if current page is not free products page
			if (!in_array($screenid, $screen_ids)) {
				return $wc_screen_ids;
			}

			$wc_screen_ids[] = $screenid;

			return $wc_screen_ids;
		}

		/**
		 * Add menu pages
		 */
		public static function add_menu_pages() {
			// Settings Submenu
			$settings_page = add_submenu_page(self::$menu_slug, __('Free Gifts', 'free-gifts-for-woocommerce'), __('Free Gifts', 'free-gifts-for-woocommerce'), 'manage_woocommerce', self::$settings_slug, array( 'FGF_Settings', 'output' ));

			add_action('load-' . $settings_page, array( __CLASS__, 'settings_page_init' ));
		}

		/**
		 * Settings page init
		 */
		public static function settings_page_init() {
			global $current_tab, $current_section, $current_sub_section, $current_action;

			// Include settings pages.
			$settings = FGF_Settings::get_settings_pages();
			$tabs = fgf_get_allowed_setting_tabs();

			// Get current tab/section.
			$current_tab = key($tabs);
			if (!empty($_GET['tab'])) {
				$sanitize_current_tab = sanitize_title(wp_unslash($_GET['tab'])); // @codingStandardsIgnoreLine.
				if (array_key_exists($sanitize_current_tab, $tabs)) {
					$current_tab = $sanitize_current_tab;
				}
			}

			$section = isset($settings[$current_tab]) ? $settings[$current_tab]->get_sections() : array();
			$current_section = empty($_REQUEST['section']) ? key($section) : sanitize_title(wp_unslash($_REQUEST['section'])); // @codingStandardsIgnoreLine.
			$current_section = empty($current_section) ? $current_tab : $current_section;
			$current_sub_section = empty($_REQUEST['subsection']) ? '' : sanitize_title(wp_unslash($_REQUEST['subsection'])); // @codingStandardsIgnoreLine.
			$current_action = empty($_REQUEST['action']) ? '' : sanitize_title(wp_unslash($_REQUEST['action'])); // @codingStandardsIgnoreLine.

			/**
			 * This hook is used to do extra action after settings loaded.
			 *
			 * @hooked FGF_Settings_Page->save - 10 (save the settings).
			 * @hooked FGF_Settings_Page->reset - 20 (reset the settings).
			 * @since 1.0
			 */
			do_action(sanitize_key(self::$plugin_slug . '_settings_loaded_' . $current_tab), $current_section);

			add_action('fgf_settings_content', array( 'FGF_Settings', 'show_messages' ));
			add_action('woocommerce_admin_field_fgf_custom_fields', array( 'FGF_Settings', 'output_fields' ));

			switch ($current_tab) {
				case 'rules':
					// Add screen option.
					add_screen_option(
							'per_page', array(
						'default' => 20,
						'option' => 'fgf_rules_per_page',
							)
					);
					break;

				case 'master-log':
					// Add screen option.
					add_screen_option(
							'per_page', array(
						'default' => 20,
						'option' => 'fgf_master_logs_per_page',
							)
					);
					break;
			}
		}

		/**
		 * Validate screen options on update.
		 *
		 * @param bool|int $status Screen option value. Default false to skip.
		 * @param string   $option The option name.
		 * @param int      $value  The number of rows to use.
		 *
		 * @return string
		 */
		public static function set_screen_option( $status, $option, $value ) {
			if ('fgf_rules_per_page' === $option || 'fgf_master_logs_per_page' === $option) {
				return $value;
			}

			return $status;
		}
	}

	FGF_Menu_Management::init();
}
