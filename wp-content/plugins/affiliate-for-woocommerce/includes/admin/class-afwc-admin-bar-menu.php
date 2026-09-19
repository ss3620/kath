<?php
/**
 * Main class for Admin bar Menu.
 *
 * @package     affiliate-for-woocommerce/includes/admin/
 * @since       6.36.0
 * @version     1.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Admin_Bar_Menu' ) ) {

	/**
	 * Admin bar menu class.
	 */
	class AFWC_Admin_Bar_Menu {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return AFWC_Admin_Bar_Menu Singleton object of this class
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
			add_action( 'admin_bar_menu', array( $this, 'add_admin_bar_menu' ), 99 );
		}

		/**
		 * Method to register the Affiliates dashboard link in WordPress admin menu bar.
		 *
		 * @param WP_Admin_Bar $wp_admin_bar The instance of WP_Admin_Bar.
		 *
		 * @return void.
		 */
		public function add_admin_bar_menu( $wp_admin_bar = null ) {

			if ( empty( $wp_admin_bar ) || ! $wp_admin_bar instanceof WP_Admin_Bar || ! is_callable( array( $wp_admin_bar, 'add_node' ) ) || ! afwc_current_user_can_manage_affiliate() ) {
				return;
			}

			$wp_admin_bar->add_node(
				array(
					'id'    => 'afwc-admin-bar-button',
					'title' => sprintf(
						/* translators: Affiliate For WooCommerce icon */
						_x( '%s Affiliates', 'label in the admin bar', 'affiliate-for-woocommerce' ),
						'<span class="ab-icon"><img style="padding:auto 2px;" class="ab-icon" src="' . AFWC_PLUGIN_URL . '/assets/images/afwc-admin-bar-icon-17.svg" /></span>'
					),
					'href'  => add_query_arg( array( 'page' => 'affiliate-for-woocommerce#!/dashboard' ), admin_url( 'admin.php' ) ),
					'meta'  => array(
						'title' => esc_html_x( 'Access affiliates dashboard', 'Meta title of the admin bar', 'affiliate-for-woocommerce' ),
					),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'id'     => 'afwc-admin-bar-dashboard-link',
					'parent' => 'afwc-admin-bar-button',
					'title'  => esc_html_x( 'Dashboard', 'Dashboard tab label in the admin bar submenu', 'affiliate-for-woocommerce' ),
					'href'   => add_query_arg( array( 'page' => 'affiliate-for-woocommerce#!/dashboard' ), admin_url( 'admin.php' ) ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'id'     => 'afwc-admin-bar-campaigns-link',
					'parent' => 'afwc-admin-bar-button',
					'title'  => esc_html_x( 'Campaigns', 'Campaigns tab label in the admin bar submenu', 'affiliate-for-woocommerce' ),
					'href'   => add_query_arg( array( 'page' => 'affiliate-for-woocommerce#!/campaigns' ), admin_url( 'admin.php' ) ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'id'     => 'afwc-admin-bar-plans-link',
					'parent' => 'afwc-admin-bar-button',
					'title'  => esc_html_x( 'Plans', 'Plans tab label in the admin bar submenu', 'affiliate-for-woocommerce' ),
					'href'   => add_query_arg( array( 'page' => 'affiliate-for-woocommerce#!/plans' ), admin_url( 'admin.php' ) ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'id'     => 'afwc-admin-bar-pending-payout-link',
					'parent' => 'afwc-admin-bar-button',
					'title'  => esc_html_x( 'Pending Payouts', 'Pending payouts tab label in the admin bar submenu', 'affiliate-for-woocommerce' ),
					'href'   => add_query_arg( array( 'page' => 'affiliate-for-woocommerce#!/pending-payouts' ), admin_url( 'admin.php' ) ),
				)
			);

			$wp_admin_bar->add_node(
				array(
					'id'     => 'afwc-admin-bar-settings-link',
					'parent' => 'afwc-admin-bar-button',
					'title'  => esc_html_x( 'Settings', 'Settings tab label in the admin bar submenu', 'affiliate-for-woocommerce' ),
					'href'   => add_query_arg(
						array(
							'page' => 'wc-settings',
							'tab'  => 'affiliate-for-woocommerce-settings',
						),
						admin_url( 'admin.php' )
					),
				)
			);

			$setting_sections = afwc_get_settings_sections();
			if ( ! empty( $setting_sections ) && is_array( $setting_sections ) ) {
				foreach ( $setting_sections as $section_key => $section_name ) {
					$wp_admin_bar->add_node(
						array(
							'id'     => 'afwc-admin-bar-settings-' . $section_key . '-link',
							'parent' => 'afwc-admin-bar-settings-link',
							'title'  => $section_name,
							'href'   => add_query_arg(
								array(
									'page'    => 'wc-settings',
									'tab'     => 'affiliate-for-woocommerce-settings',
									'section' => $section_key,
								),
								admin_url( 'admin.php' )
							),
						)
					);
				}
			}

			$wp_admin_bar->add_node(
				array(
					'id'     => 'afwc-admin-bar-tags-link',
					'parent' => 'afwc-admin-bar-settings-link',
					'title'  => esc_html_x( 'Tags', 'Manage tags link label in the admin bar submenu', 'affiliate-for-woocommerce' ),
					'href'   => add_query_arg( array( 'taxonomy' => 'afwc_user_tags' ), admin_url( 'edit-tags.php' ) ),
				)
			);
		}
	}
}

AFWC_Admin_Bar_Menu::get_instance();
