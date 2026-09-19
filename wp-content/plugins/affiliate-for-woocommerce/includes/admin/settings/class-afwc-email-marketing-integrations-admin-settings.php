<?php
/**
 * Class to handle email marketing integrations related settings
 *
 * @package   affiliate-for-woocommerce/includes/admin/settings/
 * @since     9.2.0
 * @version   1.0.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Email_Marketing_Integrations_Admin_Settings' ) ) {

	/**
	 * Main class get payouts section settings
	 */
	class AFWC_Email_Marketing_Integrations_Admin_Settings {

		/**
		 * Variable to hold instance of AFWC_Email_Marketing_Integrations_Admin_Settings
		 *
		 * @var self $instance
		 */
		private static $instance = null;

		/**
		 * Section name
		 *
		 * @var string $section
		 */
		private $section;

		/**
		 * Get single instance of this class
		 *
		 * @return AFWC_Email_Marketing_Integrations_Admin_Settings Singleton object of this class
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			$this->section = str_replace( '-', '_', str_replace( array( 'class-afwc-', '-admin-settings.php' ), '', basename( __FILE__ ) ) );
			add_filter( "afwc_{$this->section}_section_admin_settings", array( $this, 'get_section_settings' ) );
		}

		/**
		 * Method to get payouts section settings
		 *
		 * @return array
		 */
		public function get_section_settings() {
			/**
			 * Filters the allowed mailer platform integrations.
			 *
			 * @param array                                             array Available Platforms.
			 * @param \AFWC_Email_Marketing_Integrations_Admin_Settings $this Email Marketing Integrations Setting instance.
			 *
			 * @since 9.2.0
			 */
			$allowed_email_marketing_integrations = apply_filters( 'afwc_allowed_email_marketing_integrations', array( 'none' => '(select one)' ), array( 'source' => $this ) );

			$afwc_email_marketing_platform_integrations_admin_settings = array(
				array(
					'title' => afwc_get_settings_sections( $this->section ),
					'type'  => 'title',
					'id'    => 'afwc_email_marketing_integrations_admin_settings',
					'desc'  => _x( 'Connect a platform to automatically add affiliates as contacts when they join your affiliate program.', '', 'affiliate-for-woocommerce' ),
				),
				array(
					'name'              => _x( 'Platform', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'              => _x( 'Select the email marketing platform you want to use.', 'setting description', 'affiliate-for-woocommerce' ),
					'id'                => 'afwc_enabled_mailer_integration',
					'type'              => 'select',
					'class'             => 'wc-enhanced-select',
					'desc_tip'          => false,
					'options'           => $allowed_email_marketing_integrations,
					'autoload'          => false,
					'custom_attributes' => array(
						'data-placeholder' => _x( 'Select the platform...', 'placeholder to select supported email marketing integrations', 'affiliate-for-woocommerce' ),
					),
				),
				array(
					'type' => 'sectionend',
					'id'   => "afwc_{$this->section}_admin_settings",
				),
			);

			return $afwc_email_marketing_platform_integrations_admin_settings;
		}
	}

}

AFWC_Email_Marketing_Integrations_Admin_Settings::get_instance();
