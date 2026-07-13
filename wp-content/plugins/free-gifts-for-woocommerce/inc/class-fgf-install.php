<?php

/**
 * Initialize the Plugin.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'FGF_Install' ) ) {

	/**
	 * Class.
	 */
	class FGF_Install {

		/**
		 * Class initialization.
		 */
		public static function init() {
			add_action( 'woocommerce_init', array( __CLASS__, 'check_version' ) ) ;
			add_filter( 'plugin_action_links_' . FGF_PLUGIN_SLUG, array( __CLASS__, 'settings_link' ) ) ;
		}

		/**
		 * Check current version of the plugin is updated when activating plugin, if not run updater.
		 */
		public static function check_version() {
			if ( version_compare( get_option( 'fgf_version' ), FGF_VERSION, '>=' ) ) {
				return ;
			}

			self::install() ;
		}

		/**
		 * Install
		 */
		public static function install() {
			self::set_default_values() ; // Default values
			self::update_version() ;
		}

		/**
		 * Update current version.
		 */
		private static function update_version() {
			update_option( 'fgf_version', FGF_VERSION ) ;
		}

		/**
		 * Add the settings link in the plugin table.
		 */
		public static function settings_link( $links ) {
			$setting_page_link = '<a href="' . fgf_get_settings_page_url() . '">' . __( 'Settings', 'free-gifts-for-woocommerce' ) . '</a>' ;

			array_unshift( $links, $setting_page_link ) ;

			return $links ;
		}

		/**
		 * Set settings default values.
		 */
		public static function set_default_values() {
			if ( ! class_exists( 'FGF_Settings' ) ) {
				include_once FGF_PLUGIN_PATH . '/inc/admin/menu/class-fgf-settings.php'  ;
			}

			// Default for settings.
			$settings = FGF_Settings::get_settings_pages() ;

			foreach ( $settings as $setting ) {
				$sections = $setting->get_sections() ;
				if ( ! fgf_check_is_array( $sections ) ) {
					continue ;
				}

				foreach ( $sections as $section_key => $section ) {
					$settings_array = $setting->get_settings( $section_key ) ;
					foreach ( $settings_array as $value ) {
						if ( isset( $value[ 'default' ] ) && isset( $value[ 'id' ] ) ) {
							if ( get_option( $value[ 'id' ] ) === false ) {
								add_option( $value[ 'id' ], $value[ 'default' ] ) ;
							}
						}
					}
				}
			}
		}
	}

	FGF_Install::init() ;
}
