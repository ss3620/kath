<?php

/**
 * Settings Page/Tab.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('FGF_Settings_Page')) {

	/**
	 * Class.
	 */
	abstract class FGF_Settings_Page {

		/**
		 * Setting page id.
		 *
		 * @var string
		 */
		protected $id = '';

		/**
		 * Setting page label.
		 *
		 * @var string
		 */
		protected $label = '';

		/**
		 * Show buttons.
		 *
		 * @var bool
		 */
		protected $show_buttons = true;

		/**
		 * Show reset button.
		 *
		 * @var bool
		 */
		protected $show_reset_button = true;

		/**
		 * Plugin slug.
		 *
		 * @var string
		 */
		protected $plugin_slug = 'fgf';

		/**
		 * Constructor.
		 */
		public function __construct() {
			add_filter(sanitize_key($this->get_plugin_slug() . '_settings_tabs_array'), array( $this, 'add_settings_page' ), 20);
			add_action(sanitize_key($this->get_plugin_slug() . '_before_settings_tab_content_' . $this->get_id()), array( $this, 'output_sections' ));
			add_action(sanitize_key($this->get_plugin_slug() . '_settings_' . $this->get_id()), array( $this, 'output_notices' ), 5);
			add_action(sanitize_key($this->get_plugin_slug() . '_settings_' . $this->get_id()), array( $this, 'output' ), 10);
			add_action(sanitize_key($this->get_plugin_slug() . '_settings_' . $this->get_id()), array( $this, 'output_buttons' ), 20);
			add_action(sanitize_key($this->get_plugin_slug() . '_settings_' . $this->get_id()), array( $this, 'output_extra_fields' ), 30);
			add_action(sanitize_key($this->get_plugin_slug() . '_settings_loaded_' . $this->get_id()), array( $this, 'save' ), 10);
			add_action(sanitize_key($this->get_plugin_slug() . '_settings_loaded_' . $this->get_id()), array( $this, 'reset' ), 20);
		}

		/**
		 * Get the settings page ID.
		 *
		 * @return string
		 */
		public function get_id() {
			return $this->id;
		}

		/**
		 * Get settings page label.
		 *
		 * @return string
		 */
		public function get_label() {
			return $this->label;
		}

		/**
		 * Get the plugin slug.
		 *
		 * @return string
		 */
		public function get_plugin_slug() {
			return $this->plugin_slug;
		}

		/**
		 * Show reset button?.
		 *
		 * @return string
		 */
		public function show_reset_button() {
			return $this->show_reset_button;
		}

		/**
		 * Show buttons?.
		 *
		 * @return string
		 */
		public function show_buttons() {
			return $this->show_buttons;
		}

		/**
		 * Add this page to settings.
		 *
		 * @return array
		 */
		public function add_settings_page( $pages ) {
			$pages[$this->get_id()] = $this->get_label();

			return $pages;
		}

		/**
		 * Get the settings array.
		 *
		 * @return array
		 */
		public function get_settings( $current_section = '' ) {
			$settings = array();
			$function = $current_section . '_section_array';

			if (method_exists($this, $function)) {
				$settings = $this->$function();
			}
			/**
			 * This hook is used to alter the settings fields.
			 *
			 * @since 1.0
			 */
			return apply_filters(sanitize_key($this->get_plugin_slug() . '_get_settings_' . $this->get_id()), $settings, $current_section);
		}

		/**
		 * Get the sections.
		 *
		 * @return array
		 */
		public function get_sections() {
			/**
			 * This hook is used to alter the settings sections.
			 *
			 * @since 1.0
			 */
			return apply_filters(sanitize_key($this->get_plugin_slug() . '_get_sections_' . $this->get_id()), array());
		}

		/**
		 * Output sections.
		 */
		public function output_sections() {
			global $current_section;

			$sections = $this->get_sections();
			if (!fgf_check_is_array($sections) || 1 === count($sections)) {
				return;
			}

			$section = '<ul class="subsubsub ' . $this->get_plugin_slug() . '_sections ' . $this->get_plugin_slug() . '_subtab">';
			foreach ($sections as $id => $label) {
				$section .= '<li>'
						. '<a href="' . esc_url(
								fgf_get_settings_page_url(
										array(
											'tab' => $this->get_id(),
											'section' => sanitize_title($id),
										)
								)
						) . '" '
						. 'class="' . ( $current_section == $id ? 'current' : '' ) . '">' . esc_html($label) . '</a></li> | ';
			}

			$section = rtrim($section, '| ');
			$section .= '</ul><br class="clear">';

			echo wp_kses_post($section);
		}

		/**
		 * Output the settings.
		 */
		public function output() {
			global $current_section, $current_sub_section;

			$section = ( $current_sub_section ) ? $current_sub_section : $current_section;
			$settings = $this->get_settings($section);

			WC_Admin_Settings::output_fields($settings);

			/**
			 * This hook is used to display the extra contents.
			 *
			 * @since 1.0
			 */
			do_action(sanitize_key($this->get_plugin_slug() . '_after_' . $this->get_id() . '_' . $section . '_settings_fields'));
		}

		/**
		 * Output the settings buttons.
		 */
		public function output_buttons() {
			if (!$this->show_buttons()) {
				return;
			}

			FGF_Settings::output_buttons($this->show_reset_button());
		}

		/**
		 * Save settings.
		 */
		public function save() {
			global $current_section, $current_sub_section;

			if (!isset($_POST['save']) || empty($_POST['save'])) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
				return;
			}

			check_admin_referer('fgf_save_settings', '_fgf_nonce');

			$section = ( $current_sub_section ) ? $current_sub_section : $current_section;
			$settings = $this->get_settings($section);

			WC_Admin_Settings::save_fields($settings);
			FGF_Settings::add_message(__('Your settings have been saved', 'free-gifts-for-woocommerce'));

			/**
			 * This hook is used to do extra action after settings saved.
			 *
			 * @since 1.0
			 */
			do_action(sanitize_key($this->get_plugin_slug() . '_after_' . $this->get_id() . '_settings_saved'));
		}

		/**
		 * Reset settings.
		 */
		public function reset() {
			global $current_section, $current_sub_section;

			if (!isset($_POST['reset']) || empty($_POST['reset'])) { // phpcs:ignore WordPress.Security.NonceVerification.NoNonceVerification
				return;
			}

			check_admin_referer('fgf_reset_settings', '_fgf_nonce');

			$section = ( $current_sub_section ) ? $current_sub_section : $current_section;
			$settings = $this->get_settings($section);

			FGF_Settings::reset_fields($settings);
			FGF_Settings::add_message(__('Your settings have been reset', 'free-gifts-for-woocommerce'));

			/**
			 * This hook is used to do extra action after settings reset.
			 *
			 * @since 1.0
			 */
			do_action(sanitize_key($this->get_plugin_slug() . '_after_' . $this->get_id() . '_settings_reset'));
		}

		/**
		 * May be display the notices.
		 */
		public function output_notices() {
		}

		/**
		 * Output the extra fields
		 */
		public function output_extra_fields() {
		}

		/**
		 * Get the option key.
		 *
		 * @return string
		 */
		public function get_option_key( $key ) {
			return sanitize_key($this->get_plugin_slug() . '_' . $this->get_id() . '_' . $key);
		}
	}

}
