<?php
/**
 * Main class for WooCommerce Subscriptions Compatibility.
 *
 * @package     affiliate-for-woocommerce/includes/integration/woocommerce-subscriptions/
 * @since       6.1.0
 * @version     1.6.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\Rule_Registry;
use AFWC\Rules\Rule;

if ( ! class_exists( 'WCS_AFWC_Compatibility' ) ) {

	/**
	 *  Compatibility class for WooCommerce Subscription plugin.
	 */
	class WCS_AFWC_Compatibility {

		/**
		 * Variable to hold instance of WCS_AFWC_Compatibility
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Constructor
		 */
		private function __construct() {
			add_filter( 'afwc_endpoint_account_settings_after_key', array( $this, 'endpoint_account_settings_after_key' ) );
			add_filter( 'afwc_add_referral_in_admin_emails_setting_description', array( $this, 'referral_in_admin_emails_setting_description' ), 10, 1 );
			add_filter( 'afwc_allowed_emails_for_referral_details', array( $this, 'allowed_subscription_email' ) );
			// Hook to register the commission rules.
			add_filter( 'afwc_rules_categories', array( $this, 'get_categories' ) );
			add_action( 'afwc_rules_registration', array( $this, 'get_rules' ) );

			// Hooks to register the subscription referral medium.
			add_filter( 'afwc_referral_mediums', array( $this, 'register_referral_medium' ), 9, 1 );
		}

		/**
		 * Get single instance of WCS_AFWC_Compatibility.
		 *
		 * @return WCS_AFWC_Compatibility Singleton object of WCS_AFWC_Compatibility.
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Function to do version compare on WooCommerce Subscriptions Core.
		 *
		 * @param  string $version The version number.
		 * @return boolean
		 */
		public static function is_wcs_core_gte( $version = '' ) {
			if ( empty( $version ) || ! class_exists( 'WC_Subscriptions_Core_Plugin' ) || ! is_callable( array( 'WC_Subscriptions_Core_Plugin', 'instance' ) ) ) {
				return false;
			}

			$wcs_core         = WC_Subscriptions_Core_Plugin::instance();
			$wcs_core_version = is_callable( array( $wcs_core, 'get_library_version' ) ) ? $wcs_core->get_library_version() : 0;

			if ( empty( $wcs_core_version ) ) {
				return false;
			}

			return version_compare( $wcs_core_version, $version, '>=' );
		}

		/**
		 * Function to do version compare on WooCommerce Subscriptions plugin version.
		 *
		 * @param  string $version The version number.
		 * @return boolean
		 */
		public static function is_wcs_gte( $version = '' ) {
			if ( empty( $version ) || ! class_exists( 'WC_Subscriptions_Plugin' ) || ! is_callable( array( 'WC_Subscriptions_Plugin', 'instance' ) ) ) {
				return false;
			}

			$wcs_plugin         = WC_Subscriptions_Plugin::instance();
			$wcs_plugin_version = is_callable( array( $wcs_plugin, 'get_plugin_version' ) ) ? $wcs_plugin->get_plugin_version() : 0;

			if ( empty( $wcs_plugin_version ) ) {
				return false;
			}

			return version_compare( $wcs_plugin_version, $version, '>=' );
		}

		/**
		 * Return field key after which the setting should appear
		 *
		 * @return string
		 */
		public function endpoint_account_settings_after_key() {
			return 'woocommerce_myaccount_subscription_payment_method_endpoint';
		}

		/**
		 * Register the referral medium for recurring subscription orders.
		 *
		 * @param array $mediums  The existing referral mediums.
		 *
		 * @return array The updated referral mediums.
		 */
		public function register_referral_medium( $mediums = array() ) {
			if ( ! is_array( $mediums ) ) {
				return $mediums;
			}

			if ( ! class_exists( AFWC_WCS_Recurring_Referral_Medium::class ) ) {
				include_once AFWC_PLUGIN_DIRPATH . '/includes/integration/woocommerce-subscriptions/class-afwc-wcs-recurring-referral-medium.php';
			}

			return array_merge(
				array( 'recurring_subscription' => AFWC_WCS_Recurring_Referral_Medium::class ),
				$mediums
			);
		}

		/**
		 * Return updated setting description.
		 *
		 * @param string $description The setting description.
		 *
		 * @return string The updated setting description.
		 */
		public function referral_in_admin_emails_setting_description( $description = '' ) {
			if ( empty( $description ) ) {
				return '';
			}

			return _x( 'Include affiliate referral details in the WooCommerce New order, WooCommerce Subscriptions New Renewal Order and Subscription Switched emails', 'Admin setting description', 'affiliate-for-woocommerce' );
		}

		/**
		 * Return new renewal order email key if recurring commission is enabled.
		 *
		 * @param array $emails The allowed emails.
		 *
		 * @return array The allowed emails.
		 */
		public function allowed_subscription_email( $emails = array() ) {
			if ( is_array( $emails ) ) {
				array_push( $emails, 'new_renewal_order', 'new_switch_order' );
			}

			return $emails;
		}

		/**
		 * Method to get subscription related description in the plans sidebar.
		 *
		 * @return string Return the description.
		 */
		public static function plan_description() {
			return _x( 'To not issue recurring commissions on subscription renewals: create a new commission plan, add a rule: Subscription Renewal Order >= 0, set the commission = 0, mark it as active and save it. Set this plan at the top to give priority over other plans.', 'Plan description for subscription renewal commission', 'affiliate-for-woocommerce' );
		}

		/**
		 * Registers the rule categories.
		 *
		 * @param array $categories The registered categories.
		 *
		 * @return array The updated categories.
		 */
		public function get_categories( $categories = array() ) {
			if ( ! is_array( $categories ) ) {
				return $categories;
			}

			$sub_rule_details                 = array();
			$sub_rule_details['subscription'] = _x( 'Subscription', 'Commission group title for subscription rules', 'affiliate-for-woocommerce' );

			// Put subscription rules just after order rules, for better context.
			$index      = array_search( 'order', array_keys( $categories ), true );
			$categories = ( false === $index )
							? ( $categories + $sub_rule_details )
							: ( array_slice( $categories, 0, $index + 1, true )
								+ $sub_rule_details
								+ array_slice( $categories, $index + 1, null, true ) );

			return $categories;
		}

		/**
		 * Registers the subscription commission rules classes.
		 *
		 * @param Rule_Registry $registry The registry class responsible for managing rule registrations.
		 *
		 * @return void
		 */
		public function get_rules( $registry = null ) {
			if ( ! $registry instanceof Rule_Registry
				|| ! is_callable( array( $registry, 'is_registered' ) )
				|| ! is_callable( array( $registry, 'register' ) )
			) {
				return;
			}

			$rule_classes = $this->get_rule_classes();
			if ( empty( $rule_classes ) || ! is_array( $rule_classes ) ) {
				return;
			}

			foreach ( $rule_classes as $slug => $class_name ) {
				if ( $registry->is_registered( $slug ) || ! class_exists( $class_name ) ) {
					continue;
				}

				$rule_instance = new $class_name();
				if ( ! $rule_instance instanceof Rule ) {
					continue; // Skip if the provided instance is not a rule.
				}

				$registry->register( $slug, $rule_instance );
			}
		}

		/**
		 * Retrieve all rule classes from a specific folder.
		 *
		 * @return array Associative array of rule slugs and class names.
		 */
		private function get_rule_classes() {
			$classes = array();
			$files   = glob( __DIR__ . '/commission-rules/class-afwc-*-commission.php' );

			if ( empty( $files ) || ! is_array( $files ) ) {
				return array();
			}

			foreach ( $files as $file ) {
				if ( ! is_file( $file ) ) {
					continue;
				}

				include_once $file;

				$base_class = basename( $file, '.php' );
				$rule_slug  = str_replace( '-', '_', substr( $base_class, strlen( 'class-afwc-' ), -strlen( '-commission' ) ) );

				$class_name = ucwords(
					str_replace(
						array( 'class-', '-', 'afwc' ),
						array( '', '_', 'AFWC' ),
						$base_class
					),
					'_'
				);

				if ( class_exists( $class_name ) ) {
					$classes[ $rule_slug ] = $class_name;
				}
			}

			if ( ! empty( $classes ) ) {
				ksort( $classes );
			}

			return $classes;
		}
	}

}

WCS_AFWC_Compatibility::get_instance();
