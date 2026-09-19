<?php
/**
 * Main class for Affiliate For WooCommerce
 *
 * @package     affiliate-for-woocommerce/includes/
 * @since       1.0.0
 * @version     1.31.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Referral_Mediums\Referral_Medium_Registry;
use AFWC\Referral_Mediums\Abstract_Cookie_Medium;

if ( ! class_exists( 'Affiliate_For_WooCommerce' ) ) {

	/**
	 * Main class for Affiliate For WooCommerce
	 */
	final class Affiliate_For_WooCommerce {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return Affiliate_For_WooCommerce Singleton object of this class
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

			$this->constants();
			add_action( 'init', array( $this, 'init_afwc' ) );
			add_action( 'woocommerce_init', array( $this, 'init_afwc_on_wc' ) );
			$this->includes();

			if ( is_admin() ) {
				add_action( 'admin_menu', array( $this, 'add_afwc_admin_menu' ), 20 );
				add_action( 'admin_head', array( $this, 'add_afwc_remove_submenu' ) );
			}

			// Calling it early so our process will be completed before anything else.
			add_action( 'template_redirect', array( $this, 'handle_affiliate_link_hits' ), 1 );

			add_action( 'valid-paypal-standard-ipn-request', array( $this, 'handle_ipn_request' ) );

			// Show after add to cart button.
			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'add_product_referral_button' ), 99 );

			// Ajax for updating the product affiliate link.
			add_action( 'wc_ajax_afwc_get_product_affiliate_link', array( $this, 'get_product_affiliate_link' ) );
			add_action( 'wp_ajax_afwc_json_search_affiliates', array( $this, 'afwc_json_search_affiliates' ) );

			// Register the scripts.
			add_action( 'wp_enqueue_scripts', array( $this, 'register_global_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'register_global_scripts' ) );
		}

		/**
		 * Function to handle WC compatibility related function call from appropriate class
		 *
		 * @param string $function_name Function to call.
		 * @param array  $arguments     Array of arguments passed while calling $function_name.
		 *
		 * @return mixed Result of function call.
		 */
		public function __call( $function_name = '', $arguments = array() ) {

			if ( empty( $function_name ) || ! is_callable( 'SA_WC_AFW_Compatibility', $function_name ) ) {
				return;
			}

			if ( ! empty( $arguments ) ) {
				return call_user_func_array( 'SA_WC_AFW_Compatibility::' . $function_name, $arguments );
			} else {
				return call_user_func( 'SA_WC_AFW_Compatibility::' . $function_name );
			}
		}

		/**
		 * Function to define constants
		 */
		public function constants() {
			// Cookies.
			if ( ! defined( 'AFWC_AFFILIATES_COOKIE_NAME' ) ) {
				define( 'AFWC_AFFILIATES_COOKIE_NAME', 'affiliate_for_woocommerce' );
			}
			if ( ! defined( 'AFWC_CAMPAIGN_COOKIE_NAME' ) ) {
				define( 'AFWC_CAMPAIGN_COOKIE_NAME', 'afwc_campaign' );
			}
			if ( ! defined( 'AFWC_HIT_COOKIE_NAME' ) ) {
				define( 'AFWC_HIT_COOKIE_NAME', 'afwc_hit' );
			}
			if ( ! defined( 'AFWC_COOKIE_TIMEOUT_BASE' ) ) {
				define( 'AFWC_COOKIE_TIMEOUT_BASE', 86400 );
			}

			if ( ! defined( 'AFWC_PLUGIN_BASENAME' ) ) {
				define( 'AFWC_PLUGIN_BASENAME', plugin_basename( dirname( AFWC_PLUGIN_FILE ) ) );
			}
			if ( ! defined( 'AFWC_PLUGIN_DIR' ) ) {
				define( 'AFWC_PLUGIN_DIR', dirname( plugin_basename( AFWC_PLUGIN_FILE ) ) );
			}
			if ( ! defined( 'AFWC_PLUGIN_URL' ) ) {
				define( 'AFWC_PLUGIN_URL', plugins_url( AFWC_PLUGIN_DIR ) );
			}
			if ( ! defined( 'AFWC_PLUGIN_DIR_PATH' ) ) {
				define( 'AFWC_PLUGIN_DIR_PATH', plugin_dir_path( AFWC_PLUGIN_FILE ) );
			}

			if ( ! defined( 'AFWC_REGEX_PATTERN' ) ) {
				define( 'AFWC_REGEX_PATTERN', 'affiliates/([^/]+)/?$' );
			}

			if ( ! defined( 'AFWC_DEFAULT_COMMISSION_STATUS' ) ) {
				define( 'AFWC_DEFAULT_COMMISSION_STATUS', get_option( 'afwc_default_commission_status' ) );
			}
			if ( ! defined( 'AFWC_REFERRAL_STATUS_PENDING' ) ) {
				define( 'AFWC_REFERRAL_STATUS_PENDING', 'pending' );
			}
			if ( ! defined( 'AFWC_REFERRAL_STATUS_DRAFT' ) ) {
				define( 'AFWC_REFERRAL_STATUS_DRAFT', 'draft' );
			}
			if ( ! defined( 'AFWC_REFERRAL_STATUS_PAID' ) ) {
				define( 'AFWC_REFERRAL_STATUS_PAID', 'paid' );
			}
			if ( ! defined( 'AFWC_REFERRAL_STATUS_UNPAID' ) ) {
				define( 'AFWC_REFERRAL_STATUS_UNPAID', 'unpaid' );
			}
			if ( ! defined( 'AFWC_REFERRAL_STATUS_REJECTED' ) ) {
				define( 'AFWC_REFERRAL_STATUS_REJECTED', 'rejected' );
			}

			// My account - default limit to load records.
			if ( ! defined( 'AFWC_MY_ACCOUNT_DEFAULT_BATCH_LIMIT' ) ) {
				define( 'AFWC_MY_ACCOUNT_DEFAULT_BATCH_LIMIT', 15 );
			}

			// Admin - default limit to load orders and payouts.
			if ( ! defined( 'AFWC_ADMIN_DASHBOARD_DEFAULT_BATCH_LIMIT' ) ) {
				define( 'AFWC_ADMIN_DASHBOARD_DEFAULT_BATCH_LIMIT', 50 );
			}

			// Automatic Payouts Include Affiliates default limit i.e. allowed affiliates.
			if ( ! defined( 'AFWC_AP_INCLUDE_AFFILIATES_LIMIT' ) ) {
				define( 'AFWC_AP_INCLUDE_AFFILIATES_LIMIT', 25 );
			}

			if ( ! defined( 'AFWC_TIMEZONE_STR' ) ) {
				$offset       = get_option( 'gmt_offset' );
				$timezone_str = sprintf( '%+02d:%02d', (int) $offset, ( $offset - floor( $offset ) ) * 60 );
				define( 'AFWC_TIMEZONE_STR', $timezone_str );
			}

			// Set the charset for SQL Queries.
			if ( ! defined( 'AFWC_SQL_CHARSET' ) ) {
				define( 'AFWC_SQL_CHARSET', 'utf32' );
			}

			// Set the collation for SQL Queries.
			if ( ! defined( 'AFWC_SQL_COLLATION' ) ) {
				define( 'AFWC_SQL_COLLATION', 'utf32_general_ci' );
			}

			// Set documentation link - WooCommerce.com.
			if ( ! defined( 'AFWC_DOC_DOMAIN' ) ) {
				define( 'AFWC_DOC_DOMAIN', 'https://woocommerce.com/document/affiliate-for-woocommerce/' );
			}
			// Set plugin review link - WooCommerce.com.
			if ( ! defined( 'AFWC_REVIEW_URL' ) ) {
				define( 'AFWC_REVIEW_URL', 'https://woocommerce.com/products/affiliate-for-woocommerce/?review' );
			}
			// Set contact human support link - WooCommerce.com.
			if ( ! defined( 'AFW_CONTACT_SUPPORT_URL' ) ) {
				define( 'AFW_CONTACT_SUPPORT_URL', 'https://woocommerce.com/my-account/contact-support/?select=affiliate-for-woocommerce#contact-us' );
			}
		}

		/**
		 * Init Affiliate for WooCommerce functions when WordPress Initializes.
		 */
		public function init_afwc() {
			$this->load_plugin_textdomain();
			$this->register_user_tags_taxonomy();
			$this->set_payout_method();
		}

		/**
		 * Init Affiliate for WooCommerce constants when WooCommerce Initializes.
		 */
		public function init_afwc_on_wc() {
			// Constant for WooCommerce store currency - in code.
			if ( ! defined( 'AFWC_CURRENCY_CODE' ) ) {
				define( 'AFWC_CURRENCY_CODE', afwc_get_base_currency() );
			}

			// Constant for WooCommerce store currency symbol.
			if ( ! defined( 'AFWC_CURRENCY' ) ) {
				define( 'AFWC_CURRENCY', get_woocommerce_currency_symbol( AFWC_CURRENCY_CODE ) );
			}
		}

		/**
		 * Load plugin Localization files.
		 *
		 * Note: the first-loaded translation file overrides any following ones if the same translation is present.
		 *
		 * Locales found in:
		 *      - WP_LANG_DIR/affiliate-for-woocommerce/affiliate-for-woocommerce-LOCALE.mo
		 *      - WP_LANG_DIR/plugins/affiliate-for-woocommerce-LOCALE.mo
		 */
		public function load_plugin_textdomain() {
			$locale = apply_filters( 'plugin_locale', determine_locale(), 'affiliate-for-woocommerce' ); // phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment

			unload_textdomain( 'affiliate-for-woocommerce' );
			load_textdomain( 'affiliate-for-woocommerce', WP_LANG_DIR . '/affiliate-for-woocommerce/affiliate-for-woocommerce-' . $locale . '.mo' );
			load_plugin_textdomain( 'affiliate-for-woocommerce', false, AFWC_PLUGIN_BASENAME . '/languages' );
		}

		/**
		 * Function to register affiliate tags taxonomy
		 */
		public function register_user_tags_taxonomy() {
			register_taxonomy(
				'afwc_user_tags', // taxonomy name.
				'user', // object for which the taxonomy is created.
				array( // taxonomy details.
					'public'       => true,
					'labels'       => array(
						'name'          => __( 'Affiliate Tags', 'affiliate-for-woocommerce' ),
						'singular_name' => __( 'Affiliate Tag', 'affiliate-for-woocommerce' ),
						'menu_name'     => __( 'Affiliate Tags', 'affiliate-for-woocommerce' ),
						'search_items'  => __( 'Search Affiliate Tag', 'affiliate-for-woocommerce' ),
						'popular_items' => __( 'Popular Affiliate Tags', 'affiliate-for-woocommerce' ),
						'all_items'     => __( 'All Affiliate Tags', 'affiliate-for-woocommerce' ),
						'edit_item'     => __( 'Edit Affiliate Tag', 'affiliate-for-woocommerce' ),
						'update_item'   => __( 'Update Affiliate Tag', 'affiliate-for-woocommerce' ),
						'add_new_item'  => __( 'Add New Affiliate Tag', 'affiliate-for-woocommerce' ),
						'new_item_name' => __( 'New Affiliate Tag Name', 'affiliate-for-woocommerce' ),
						'not_found'     => __( 'No Affiliate Tags found', 'affiliate-for-woocommerce' ),
					),
					'show_in_menu' => false,
					'hierarchical' => true,
				)
			);

			$default_affiliate_tags    = array( 'Gold', 'Silver', 'Bronze', 'Platinum', 'Dormant', 'Active', 'Promoter', 'Influencer' );
			$afwc_default_tags_created = get_option( 'afwc_default_tags_created', false );
			if ( ! $afwc_default_tags_created ) {
				foreach ( $default_affiliate_tags  as $value ) {
					wp_insert_term( $value, 'afwc_user_tags' );
				}
				update_option( 'afwc_default_tags_created', true, 'no' );
			}
		}

		/**
		 * Includes
		 */
		public function includes() {
			include_once 'integration/woocommerce/compat/class-sa-wc-afw-compatibility.php';
			include_once 'afw-wp-compatibility-functions.php';
			include_once 'affiliate-for-woocommerce-functions.php';
			include_once 'lib/class-afwc-user-agent-parser.php';

			include_once 'rules/class-rule.php';
			$afwc_base_rule_classes = glob( AFWC_PLUGIN_DIRPATH . '/includes/rules/types/*.php' );
			foreach ( $afwc_base_rule_classes as $rule_class ) {
				if ( is_file( $rule_class ) ) {
					include_once $rule_class;
				}
			}
			include_once 'rules/class-context.php';
			include_once 'rules/class-rule-registry.php';
			include_once 'rules/class-group.php';
			include_once 'rules/class-groups.php';
			include_once 'commission-rules/class-afwc-commission-rules.php';

			include_once 'multi-tier/class-afwc-multi-tier.php';
			include_once 'multi-tier/class-afwc-multi-tier-commission-calculation.php';

			include_once 'class-afwc-plans.php';

			// Include all common classes.
			include_once 'common/class-afwc-commission-plans.php';
			include_once 'common/class-afwc-payout-invoice.php';
			include_once 'common/class-afwc-user-roles-handler.php';
			include_once 'common/class-afwc-affiliate.php';
			include_once 'common/class-afwc-housekeeping.php';

			include_once 'tracking/class-afwc-coupon.php';
			include_once 'tracking/class-afwc-landing-page.php';
			include_once 'tracking/class-afwc-direct-link.php';

			include_once 'referral-mediums/class-referral-medium-registry.php';
			include_once 'health-check/class-health-manager.php';

			if ( is_admin() ) {
				include_once 'migrations/class-afwc-migrate-affiliates.php';
				include_once 'admin/class-afwc-admin-settings.php';
				foreach ( glob( AFWC_PLUGIN_DIRPATH . '/includes/admin/settings/*.php' ) as $setting_section_file ) {
					if ( is_file( $setting_section_file ) ) {
						include_once $setting_section_file;
					}
				}
				include_once 'admin/class-afwc-admin-affiliates.php';
				include_once 'admin/class-afwc-admin-summary-reports.php';
				include_once 'admin/class-afwc-admin-dashboard.php';
				include_once 'admin/class-afwc-campaign-dashboard.php';
				include_once 'admin/class-afwc-commission-dashboard.php';
				include_once 'admin/class-afwc-admin-affiliate.php';
				include_once 'admin/class-afwc-admin-docs.php';
				include_once 'admin/class-afwc-privacy.php';
				include_once 'admin/class-afwc-admin-notifications.php';
				include_once 'admin/class-afwc-admin-affiliate-users.php';
				include_once 'admin/class-afwc-admin-order-affiliate-details.php';
				include_once 'admin/class-afwc-admin-link-unlink-in-order.php';
				include_once 'admin/class-afwc-onboarding.php';
				include_once 'admin/class-afwc-pending-payout-dashboard.php';
				include_once 'admin/class-afwc-system-status-report.php';

				// Admin Dashboard widget should be displayed if current user can mange affiliates.
				if ( afwc_current_user_can_manage_affiliate() ) {
					include_once 'admin/class-afwc-admin-dashboard-widget.php';
				}
			}
			// Admin bar link should be displayed if option is enabled and current user can mange affiliates.
			if ( 'yes' === get_option( 'afwc_show_admin_bar_menu', 'yes' ) && afwc_current_user_can_manage_affiliate() ) {
				include_once 'admin/class-afwc-admin-bar-menu.php';
			}

			include_once 'admin/class-afwc-admin-new-referral-email.php';

			include_once 'upgrades/class-afwc-db-background-process.php';

			// Stripe payouts.
			if ( 'yes' === get_option( 'afwc_enable_stripe_payout', 'no' ) ) {
				include_once 'gateway/stripe/class-afwc-stripe-functions.php';
				include_once 'gateway/stripe/class-afwc-stripe-api.php';
				include_once 'gateway/stripe/class-afwc-stripe-connect.php';
			}

			if ( class_exists( 'WC_Subscriptions_Core_Plugin' ) || class_exists( 'WC_Subscriptions' ) ) {
				include_once 'integration/woocommerce-subscriptions/class-wcs-afwc-compatibility.php';
			}

			if ( 'yes' === get_option( 'woocommerce_enable_coupons' ) ) {
				$allowed_coupon_types_for_payout    = get_option( 'afwc_enabled_for_coupon_payout', array() );
				$afwc_enable_affiliate_made_coupons = ( 'yes' === get_option( 'afwc_enable_affiliate_made_coupons', 'no' ) );
				if (
					( ! empty( $allowed_coupon_types_for_payout ) && is_array( $allowed_coupon_types_for_payout ) && in_array( 'fixed_cart', $allowed_coupon_types_for_payout, true ) )
					|| $afwc_enable_affiliate_made_coupons
				) {
					include_once 'integration/woocommerce/class-afwc-coupon-api.php';
				}

				if ( 'yes' === get_option( 'afwc_use_referral_coupons', 'yes' ) ) {
					if ( afwc_is_plugin_active( 'url-coupons-by-smart-coupons-for-woocommerce/url-coupons-by-smart-coupons-for-woocommerce.php' ) ) {
						include_once 'integration/url-coupons-by-smart-coupons-for-woocommerce/class-ucscwc-afwc-compatibility.php';
					}

					if ( afwc_is_plugin_active( 'woocommerce-smart-coupons/woocommerce-smart-coupons.php' ) ) {
						include_once 'integration/woocommerce-smart-coupons/class-wsc-afwc-compatibility.php';
					}
				}
			}
			if ( afwc_is_plugin_active( 'elementor/elementor.php' ) && afwc_is_plugin_active( 'elementor-pro/elementor-pro.php' ) ) {
				include_once 'integration/elementor/class-afwc-elementor-form-actions.php';
				include_once 'integration/elementor/class-afwc-elementor-dynamic-tags.php';
			}
			if ( afwc_is_plugin_active( 'contact-form-7/wp-contact-form-7.php' ) && ! afwc_is_plugin_active( 'affiliate-contact-form-7-integration-for-woocommerce/affiliate-contact-form-7-integration-for-woocommerce.php' ) ) {
				include_once 'integration/contact-form-7/class-afwc-cf7-registration-form.php';
			}

			include_once 'upgrades/class-afwc-ip-field-updates.php';
			include_once 'upgrades/class-afwc-signup-date-batch-assign.php';
			include_once 'upgrades/class-afwc-paypal-payout-method-assign.php';
			include_once 'upgrades/class-afwc-order-total-migration.php';

			include_once 'gateway/paypal/class-afwc-paypal-api.php'; // TODO: remove usage from my account and then move this file to include under only admin.
			include_once 'class-afwc-api.php';

			include_once 'upgrades/class-afwc-db-upgrade.php';
			include_once 'class-afwc-emails.php';
			include_once 'class-afwc-registration-submissions.php';
			include_once 'class-afwc-rewrite-rules.php';
			include_once 'class-afwc-merge-tags.php';

			include_once 'handlers/class-afwc-url-handler.php';

			include_once 'reports/class-afwc-payout-history.php';
			include_once 'reports/class-afwc-referred-products.php';
			include_once 'reports/class-afwc-visits.php';

			include_once 'queue/class-afwc-report-background-emailer.php';
			include_once 'queue/class-afwc-admin-summary-email-scheduler.php';

			include_once 'payouts/class-afwc-payout-handler.php';
			include_once 'migrations/class-source-interface.php';
			include_once 'migrations/class-migrate-data.php';

			// commission payouts.
			include_once 'commission-payouts/class-afwc-commission-payouts.php';
			include_once 'commission-payouts/class-afwc-automatic-payouts-handler.php';

			include_once 'frontend/class-afwc-my-account.php';
			include_once 'frontend/class-afwc-registration-form.php';
			include_once 'frontend/class-afwc-my-account-templates.php';

			if ( 'yes' === get_option( 'woocommerce_analytics_enabled' ) ) {
				include_once 'integration/woocommerce/analytics/class-afwc-wc-orders-analytics.php';
			}

			// Load multi-currency integrations.
			if ( class_exists( 'WC_Aelia_CurrencySwitcher' ) ) {
				include_once 'integration/aelia-currency-switcher/class-afwc-aelia-compatibility.php';
			}
			if ( class_exists( 'woocommerce_wpml' ) || class_exists( 'WCML_Multi_Currency' ) ) {
				include_once 'integration/wcml/class-afwc-wcml-compatibility.php';
			}

			// Load Mailchimp integration.
			include_once 'integration/mailchimp/class-afwc-mailchimp-compatibility.php';
		}

		/**
		 * Function to log messages generated by Affiliate plugin
		 *
		 * @param  string $level   Message type. Valid values: debug, info, notice, warning, error, critical, alert, emergency.
		 * @param  string $message The message to log.
		 * @param  array  $args    Optional.  Additional args.
		 *
		 * @return void
		 */
		public static function log( $level = 'notice', $message = '', $args = array() ) {
			if ( empty( $message ) ) {
				return;
			}

			$defaults = array(
				'add_contact_support' => false,
			);
			$args     = wp_parse_args( $args, $defaults );

			if ( ! empty( $args['add_contact_support'] ) && defined( 'AFW_CONTACT_SUPPORT_URL' ) ) {
				$message .= "\n" . sprintf(
					/* translators: URL to contact support */
					esc_html_x( 'Need help? Get in touch with our support team: %s', 'text appended to log that provides a link to contact support', 'affiliate-for-woocommerce' ),
					AFW_CONTACT_SUPPORT_URL
				);
			}

			if ( function_exists( 'wc_get_logger' ) ) {
				$logger  = wc_get_logger();
				$context = array( 'source' => 'affiliate-for-woocommerce' );
				$logger->log( $level, $message, $context );
			} else {
				include_once plugin_dir_path( WC_PLUGIN_FILE ) . 'includes/class-wc-logger.php';
				$logger = new WC_Logger();
				$logger->add( 'affiliate-for-woocommerce', $message );
			}
		}

		/**
		 * Admin menus
		 */
		public function add_afwc_admin_menu() {
			/* translators: A small arrow */
			add_submenu_page( 'woocommerce', __( 'Affiliates Dashboard', 'affiliate-for-woocommerce' ), __( 'Affiliates', 'affiliate-for-woocommerce' ), 'manage_woocommerce', 'affiliate-for-woocommerce', 'AFWC_Admin_Dashboard::afwc_dashboard_page' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown

			$get_page = ( ! empty( $_GET['page'] ) ) ? wc_clean( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore

			if ( empty( $get_page ) ) {
				return;
			}

			if ( 'affiliate-for-woocommerce-documentation' === $get_page ) {
				add_submenu_page( 'woocommerce', _x( 'Getting Started', 'Page title for setup guide page', 'affiliate-for-woocommerce' ), _x( 'Getting Started', 'Menu name for setup guide page', 'affiliate-for-woocommerce' ), 'manage_woocommerce', 'affiliate-for-woocommerce-documentation', 'AFWC_Admin_Docs::afwc_docs' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
			}
		}

		/**
		 * Remove Affiliate For WooCommerce's unnecessary submenus.
		 */
		public function add_afwc_remove_submenu() {
			remove_submenu_page( 'woocommerce', 'affiliate-for-woocommerce-documentation' );
		}

		/**
		 * Method to track visitor and set affiliate cookie
		 *
		 * @return void
		 */
		public function handle_affiliate_link_hits() {

			// Ignore AJAX requests.
			if ( afwc_is_wp_doing_ajax() ) {
				return;
			}

			// Prevent the hit tracking if referral determination is set for first referrer when an affiliate id is present in the cookies.
			// If there is a referral link hit, the referral parameter will be stay in the URL.
			if ( afwc_get_affiliate_from_cookie() && 'first' === get_option( 'afwc_credit_affiliate', 'last' ) ) {
				return;
			}

			$medium_registry = is_callable( array( Referral_Medium_Registry::class, 'get_instance' ) ) ? Referral_Medium_Registry::get_instance() : null;

			$referral_mediums = is_callable( array( $medium_registry, 'get_all_registered' ) )
				? $medium_registry->get_all_registered()
				: array();

			if ( empty( $referral_mediums ) || ! is_array( $referral_mediums ) ) {
				return;
			}

			foreach ( $referral_mediums as $medium_key => $medium ) {

				if ( ! $medium instanceof Abstract_Cookie_Medium || ! is_callable( array( $medium, 'get_affiliate_for_visits' ) ) ) {
					continue;
				}

				$affiliate_id = $medium->get_affiliate_for_visits();

				if ( ! empty( $affiliate_id ) ) {
					$this->handle_hit( $affiliate_id, $medium_key );

					/**
					 * Filter to preserve referral tracking parameter in URL
					 *
					 * @param bool   Whether to preserve the tracking parameter in URL.
					 * @param array  Additional information.
					 *
					 * @since 8.11.0
					 */
					if ( 'link' === $medium_key && ! apply_filters( 'afwc_preserve_referral_tracking_parameter_in_url', false, array( 'source' => $this ) ) ) {

						// Remove tracking parameter from URL and redirect to clean URL.
						$current_url = afwc_get_current_url_without_tracking_parameter();

						if ( empty( $current_url ) ) {
							return;
						}

						/**
						 * Filter to modify the redirect status code
						 *
						 * @param int HTTP status code for redirection. Default is 302.
						 *
						 * @since 1.31.1
						 */
						$status = apply_filters( 'affiliates_redirect_status_code', 302 );
						$status = intval( $status );
						switch ( $status ) {
							case 300:
							case 301:
							case 302:
							case 303:
							case 304:
							case 305:
							case 306:
							case 307:
								break;
							default:
								$status = 302;
						}

						wp_safe_redirect( $current_url, $status );
						exit;
					}

					return;
				}
			}
		}

		/**
		 * Handle hits by referral
		 *
		 * @param integer $affiliate_id The affiliate id.
		 * @param string  $source       The source medium.
		 *
		 * @return void
		 */
		public function handle_hit( $affiliate_id = 0, $source = '' ) {

			// Prevent the hit tracking if the affiliate id is missing or referral determination is set for first referrer when an affiliate id is present in the cookies.
			if ( empty( $affiliate_id ) || ( ! empty( $_COOKIE[ AFWC_AFFILIATES_COOKIE_NAME ] ) && 'first' === get_option( 'afwc_credit_affiliate', 'last' ) ) ) {
				return;
			}

			$affiliate = new AFWC_Affiliate( $affiliate_id );

			if ( ! $affiliate instanceof AFWC_Affiliate || empty( $affiliate->ID ) || ! is_callable( array( $affiliate, 'is_valid' ) ) || ! $affiliate->is_valid() ) {
				return;
			}

			$encoded_affiliate_id = afwc_encode_affiliate_id( $affiliate_id );
			$days                 = get_option( 'afwc_cookie_expiration', 60 );
			$expire               = ( $days > 0 ) ? ( time() + AFWC_COOKIE_TIMEOUT_BASE * $days ) : 0;
			$params               = array();

			if ( ! empty( $encoded_affiliate_id ) ) {
				// Set affiliate ID in cookie.
				setcookie(
					AFWC_AFFILIATES_COOKIE_NAME,
					$encoded_affiliate_id,
					$expire,
					COOKIEPATH ? COOKIEPATH : '/',
					COOKIE_DOMAIN,
					( wc_site_is_https() && is_ssl() )
				);
			}

			// check for campaign.
			$utm_campaign = ( ! empty( $_REQUEST ) && ! empty( $_REQUEST['utm_campaign'] ) ) ? wc_clean( wp_unslash( $_REQUEST['utm_campaign'] ) ) : '';// phpcs:ignore
			$campaign_id  = ( ! empty( $utm_campaign ) ) ? afwc_get_campaign_id_by_slug( $utm_campaign ) : 0;

			// Set campaign ID in cookie.
			setcookie(
				AFWC_CAMPAIGN_COOKIE_NAME,
				$campaign_id,
				$expire,
				COOKIEPATH ? COOKIEPATH : '/',
				COOKIE_DOMAIN,
				( wc_site_is_https() && is_ssl() )
			);

			$params['campaign_id'] = $campaign_id;

			$affiliate_api = AFWC_API::get_instance();
			if ( is_callable( array( $affiliate_api, 'track_visitor' ) ) ) {
				$hit_id = $affiliate_api->track_visitor( $affiliate_id, 0, $source, $params );

				if ( ! empty( $hit_id ) ) {
					// Set Hit ID in cookie.
					setcookie(
						AFWC_HIT_COOKIE_NAME,
						$hit_id,
						$expire,
						COOKIEPATH ? COOKIEPATH : '/',
						COOKIE_DOMAIN,
						( wc_site_is_https() && is_ssl() )
					);
				}
			}
		}

		/**
		 * Get referral type
		 *
		 * @param  integer $affiliate_id The affiliate id.
		 * @param  array   $used_coupons The used coupons.
		 * @return string
		 */
		public function get_referral_type( $affiliate_id = 0, $used_coupons = array() ) {
			if ( ! empty( $affiliate_id ) && ! empty( $used_coupons ) ) {
				$afwc_coupon      = AFWC_Coupon::get_instance();
				$referral_coupons = $afwc_coupon->get_referral_coupon( array( 'user_id' => $affiliate_id ) );
				if ( ! empty( $referral_coupons ) && is_array( $referral_coupons ) ) {
					foreach ( $referral_coupons as $coupon_id => $coupon_code ) {
						$referral_coupon = wc_strtolower( $coupon_code );
						if ( ! empty( $referral_coupon ) && in_array( $referral_coupon, array_map( 'wc_strtolower', $used_coupons ), true ) ) {
							return 'coupon';
						}
					}
				}
			}
			return 'link';
		}

		/**
		 * Handle IPN requests
		 *
		 * Used to save transaction ID of the commission payout
		 *
		 * @param array $posted The posted data.
		 */
		public function handle_ipn_request( $posted = array() ) {

			if ( empty( $posted )
				|| empty( $posted['ipn_track_id'] )
				|| empty( $posted['masspay_txn_id_1'] )
				|| empty( $posted['txn_type'] ) || 'masspay' !== $posted['txn_type']
				|| empty( $posted['unique_id_1'] ) || 'afwc_mass_payment' !== $posted['unique_id_1']
			) {
				return;
			}

			global $wpdb;

			$correlation_id = $posted['ipn_track_id'];
			$transaction_id = $posted['masspay_txn_id_1'];

			$search  = 'CorrelationID:' . $correlation_id;
			$replace = 'TransactionID:' . $transaction_id;

			// phpcs:disable
			$result = $wpdb->query(
									$wpdb->prepare("UPDATE {$wpdb->prefix}afwc_payouts
													SET payout_notes = REPLACE( payout_notes, %s, %s )",
													$search,
													$replace
												)
			);
			// phpcs:enable
		}

		/**
		 * Insert a setting or an array of settings after another specific setting by its ID.
		 *
		 * @since 1.2.1
		 * @param array  $settings                The original list of settings.
		 * @param string $insert_after_setting_id The setting id to insert the new setting after.
		 * @param array  $new_setting             The new setting to insert. Can be a single setting or an array of settings.
		 * @param string $insert_type             The type of insert to perform. Can be 'single_setting' or 'multiple_settings'. Optional. Defaults to a single setting insert.
		 *
		 * @credit: WooCommerce Subscriptions
		 */
		public static function insert_setting_after( &$settings = array(), $insert_after_setting_id = '', $new_setting = array(), $insert_type = 'single_setting' ) {
			if ( ! is_array( $settings ) ) {
				return;
			}

			$original_settings = $settings;
			$settings          = array();

			foreach ( $original_settings as $setting ) {
				$settings[] = $setting;

				if ( isset( $setting['id'] ) && $insert_after_setting_id === $setting['id'] ) {
					if ( 'single_setting' === $insert_type ) {
						$settings[] = $new_setting;
					} else {
						$settings = array_merge( $settings, $new_setting );
					}
				}
			}
		}

		/**
		 * Generate a unique string.
		 *
		 * @param  string $prefix The prefix.
		 * @return string
		 */
		public static function uniqid( $prefix = null ) {
			$uniqid = self::number_to_alphabet( gmdate( 'dmyHis', self::get_offset_timestamp() ) );
			if ( ! empty( $prefix ) ) {
				$uniqid = $prefix . $uniqid;
			}
			return $uniqid;
		}

		/**
		 * Convert number to alphabet.
		 *
		 * @param  string $number The number to convert.
		 * @return string
		 */
		public static function number_to_alphabet( $number = null ) {
			if ( ! is_null( $number ) ) {
				$alphabets     = range( 'a', 'z' );
				$absint_number = absint( $number );
				$length        = strlen( $number );
				if ( 2 < $length || 25 < $absint_number ) {
					$numbers = str_split( strval( $number ), 2 );
				} else {
					$numbers = str_split( strval( $number ), 1 );
				}
				$string = '';
				foreach ( $numbers as $num ) {
					if ( ( 1 < strlen( $num ) && 10 > absint( $num ) ) || 25 < absint( $num ) ) {
						$nums = str_split( $num, 1 );
						foreach ( $nums as $_num ) { // This foreach loop will run for maximum 2 iterations.
							$string .= $alphabets[ $_num ];
						}
					} else {
						$string .= $alphabets[ $num ];
					}
				}
				return $string;
			}
			return '';
		}

		/**
		 * Get offset timestamp
		 *
		 * @param  int $timestamp The timestamp.
		 *
		 * @return int Return the timestamp offset.
		 */
		public static function get_offset_timestamp( $timestamp = 0 ) {
			if ( empty( $timestamp ) ) {
				$timestamp = time();
			}

			$gmt_offset = self::get_gmt_offset();
			return $timestamp + ( $gmt_offset ? intval( $gmt_offset ) : 0 );
		}

		/**
		 * Get site's GMT offset.
		 *
		 * @return int Return the offset. It will always return the integer due to the design of timezone.
		 */
		public static function get_gmt_offset() {
			$offset = get_option( 'gmt_offset', 0 );
			return floatval( $offset ) * HOUR_IN_SECONDS;
		}

		/**
		 * Get plugins data
		 *
		 * @param string $plugin_file The plugin file to get the data.
		 * @see https://developer.wordpress.org/reference/functions/get_plugin_data/
		 *
		 * @return array
		 */
		public static function get_plugin_data( $plugin_file = AFWC_PLUGIN_FILE ) {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			return get_plugin_data( $plugin_file, true, false );
		}

		/**
		 * Get affiliate users.
		 *
		 * @param array $params Arguments of WP_User_Query.
		 * @return array
		 */
		public function get_affiliates( $params = array() ) {
			$args = array_merge(
				$params,
				array(
					'meta_key'   => 'afwc_is_affiliate', // phpcs:ignore
					'meta_value' => 'yes', // phpcs:ignore
				)
			);

			$affiliate_users = get_users( $args );
			// Get assigned affiliate roles.
			$affiliate_user_roles = get_option( 'affiliate_users_roles', '' );

			if ( ! empty( $affiliate_user_roles ) ) {
				$args = array_merge(
					$params,
					array(
						'role__in' => $affiliate_user_roles,
					)
				);
				// Get users by assigned affiliate user roles.
				$affiliate_role_users = get_users( $args );
				if ( ! empty( $affiliate_role_users ) ) {
					// Merge users of affiliate and users in affiliate user role.
					$affiliate_users = array_merge( $affiliate_users, $affiliate_role_users );
				}
			}

			$users = array();
			if ( ! empty( $affiliate_users ) ) {
				foreach ( $affiliate_users as $user ) {
					$user_data = ! empty( $user->data ) ? $user->data : null;
					if ( ! empty( $user_data ) && isset( $user_data->ID ) && isset( $user_data->user_email ) ) {
						$users[ $user_data->ID ] = sprintf(
							'%1$s (#%2$d &ndash; %3$s)',
							isset( $user_data->display_name ) ? $user_data->display_name : '',
							absint( $user_data->ID ),
							$user_data->user_email
						);
					}
				}
			}

			/**
			 * Filter to modify the affiliates list.
			 *
			 * @param array $users The affiliates list.
			 *
			 * @since 1.7.0
			 */
			return apply_filters( 'afwc_get_affiliates', $users );
		}

		/**
		 * Method to get the template.
		 *
		 * @param string $template Template name.
		 * @param array  $args     Arguments to be passed to the template.
		 *
		 * @return void
		 */
		public function afwc_get_template( $template = '', $args = array() ) {
			if ( empty( $template ) ) {
				return;
			}

			$template = $this->resolve_template_path( $template );

			wc_get_template(
				$template,
				$args,
				$this->get_template_base_dir( $template ),
				AFWC_PLUGIN_DIRPATH . '/templates/'
			);
		}

		/**
		 * Method to get the version of the template.
		 * Template file's comment should have `@version` OR `version:` to detect template file version.
		 *
		 * @see get_file_data()
		 * @see WC_Admin_Status::get_file_version()
		 *
		 * @param string $template The template name.
		 *
		 * @return string Version number.
		 */
		public function afwc_get_template_version( $template = '' ) {
			if ( empty( $template ) ) {
				return '';
			}

			global $affiliate_for_woocommerce;

			$base_dir = is_callable( array( $affiliate_for_woocommerce, 'get_template_base_dir' ) ) ? $affiliate_for_woocommerce->get_template_base_dir( $template ) : '';
			$file     = ( ! empty( $base_dir ) ? locate_template( array( $base_dir ) ) : AFWC_PLUGIN_DIRPATH . '/templates/' ) . $template;
			if ( ! file_exists( $file ) ) {
				return '';
			}

			$file_data = file_get_contents( $file, false, null, 0, 2 * KB_IN_BYTES ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( false === $file_data ) {
				$file_data = '';
			}
			$file_data = str_replace( "\r", "\n", $file_data );

			$version = '';
			if ( preg_match( '/^[ \t\/*#@]*(?:@version|version:)\s*(.*)$/mi', $file_data, $match ) && $match[1] ) {
				$version = trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $match[1] ) );
			}

			return $version;
		}

		/**
		 * Method to find the override directory for a given template path.
		 *
		 * @param string $template_name The template name (relative path).
		 * @param string $return_data   What to return:
		 *                              'dir'      => The directory path where the template override exists (default),
		 *                              'template' => The full template file path found by locate_template(),
		 *                              'both'     => An associative array with both directory and template path.
		 *
		 * @return string|array|false Returns:
		 *                            - Directory path (string) if `$return_data` is 'dir'.
		 *                            - Template file path (string) if `$return_data` is 'template'.
		 *                            - Associative array ['dir' => string, 'template' => string] if `$return_data` is 'both'.
		 *                            - false if no override is found.
		 */
		public function locate_template_override( $template_name = '', $return_data = 'dir' ) {
			$afwc_dir    = trailingslashit( basename( AFWC_PLUGIN_DIRPATH ) ); // Folder: `affiliate-for-woocommerce/`.
			$wc_afwc_dir = 'woocommerce/' . $afwc_dir; // Folder: `woocommerce/affiliate-for-woocommerce/`.

			// 1. Check inside active theme's or parent theme's `woocommerce/affiliate-for-woocommerce/` folder.
			$found_template = locate_template( $wc_afwc_dir . $template_name );
			if ( ! empty( $found_template ) ) {
				$found_dir = $wc_afwc_dir;
			} else {
				// 2. If not found, check inside active theme's or parent theme's `affiliate-for-woocommerce/` folder.
				$found_template = locate_template( $afwc_dir . $template_name );
				if ( ! empty( $found_template ) ) {
					$found_dir = $afwc_dir;
				} else {
					return false;
				}
			}

			if ( 'template' === $return_data ) {
				return $found_template;
			} elseif ( 'both' === $return_data ) {
				return array(
					'dir'      => $found_dir,
					'template' => $found_template,
				);
			} else {
				return $found_dir;
			}
		}

		/**
		 * Method to retrieve the base directory for overridden templates of plugin.
		 *
		 * @param string $template_name The template name (relative path) to locate.
		 * @return string The base directory path (relative to the theme) if found, otherwise an empty string.
		 */
		public function get_template_base_dir( $template_name = '' ) {
			$template_base_dir = '';

			// Get the template migration map for backward compatibility.
			$migration_map = $this->get_template_migration_map();

			// Build the list of template paths to check.
			// Priority is given to the old template path (if defined in the migration map) to maintain backward compatibility for existing overrides.
			// The current template path is then added as the default lookup path.
			$templates_to_check = array();
			if ( ! empty( $migration_map[ $template_name ] ) ) {
				$templates_to_check[] = $migration_map[ $template_name ];
			}
			$templates_to_check[] = $template_name;

			foreach ( $templates_to_check as $template_to_check ) {
				$template_base_dir = $this->locate_template_override( $template_to_check );
				if ( ! empty( $template_base_dir ) ) {
					break;
				}
			}

			/**
			 * Filter to modify the template base directory.
			 *
			 * @param string $template_base_dir The template base directory.
			 * @param string $template_name     The template name.
			 *
			 * @since 1.7.0
			 */
			return apply_filters( 'afwc_template_base_dir', $template_base_dir, $template_name );
		}

		/**
		 * Resolves the correct template path (new or legacy) to use.
		 *
		 * This method ensures backward compatibility for templates that have been
		 * moved or renamed. It first attempts to use the requested template path.
		 * If the template has a mapping defined in the migration map and the
		 * old template exists as a theme override, it will return the old template path instead.
		 * If no override is found for the old template, the new path is used.
		 *
		 * @param string $template_name The template name (relative path).
		 * @return string The resolved template path (new or old).
		 */
		public function resolve_template_path( $template_name = '' ) {
			$migration_map = $this->get_template_migration_map();

			// If no migration exists for this template, return as is.
			if ( empty( $migration_map[ $template_name ] ) ) {
				return $template_name;
			}

			// Check if override exists for old template.
			$old_template = $migration_map[ $template_name ];
			if ( $this->locate_template_override( $old_template ) ) {
				return $old_template;
			}

			return $template_name;
		}

		/**
		 * Returns a mapping of new template paths to their legacy (old) paths.
		 *
		 * Format: 'new/path/to/template.php' => 'old/path/to/template.php'.
		 *
		 * @return array Array of new => old template paths.
		 */
		public function get_template_migration_map() {
			return array(
				'my-account/dashboard/payouts/payouts-kpi.php' => 'my-account/dashboard/payout-kpi.php',
				'my-account/dashboard/payouts/payouts-table.php' => 'my-account/dashboard/payouts-table.php',
				'my-account/dashboard/products/products-table.php' => 'my-account/dashboard/products-table.php',
				'my-account/dashboard/referrals/referrals-table.php' => 'my-account/dashboard/referrals-table.php',
				'my-account/dashboard/visits/visits-table.php' => 'my-account/dashboard/visits-table.php',
				'emails/afwc-new-conversion.php'          => 'afwc-new-conversion.php',
				'emails/plain/afwc-new-conversion.php'    => 'plain/afwc-new-conversion.php',
				'emails/afwc-welcome-affiliate-email.php' => 'afwc-welcome-affiliate-email.php',
				'emails/plain/afwc-welcome-affiliate-email.php' => 'plain/afwc-welcome-affiliate-email.php',
				'emails/afwc-new-registration-received.php' => 'afwc-new-registration-received.php',
				'emails/plain/afwc-new-registration-received.php' => 'plain/afwc-new-registration-received.php',
				'emails/afwc-commission-paid.php'         => 'afwc-commission-paid.php',
				'emails/plain/afwc-commission-paid.php'   => 'plain/afwc-commission-paid.php',
				'emails/afwc-affiliate-summary-reports.php' => 'afwc-affiliate-summary-reports.php',
				'emails/plain/afwc-affiliate-summary-reports.php' => 'plain/afwc-affiliate-summary-reports.php',
				'emails/afwc-affiliate-pending-request.php' => 'afwc-affiliate-pending-request.php',
				'emails/plain/afwc-affiliate-pending-request.php' => 'plain/afwc-affiliate-pending-request.php',
			);
		}

		/**
		 * Set payout method if not exist.
		 * To set method on plugin upgrade/activation for commission payouts
		 *
		 * @return void.
		 */
		public function set_payout_method() {
			if ( 'no' === get_option( 'afwc_is_set_commission_payout_method', 'no' ) ) {
				$afwc_paypal = is_callable( array( 'AFWC_PayPal_API', 'get_instance' ) ) ? AFWC_PayPal_API::get_instance() : null;

				if ( ! empty( $afwc_paypal ) && is_callable( array( $afwc_paypal, 'get_payout_method' ) ) ) {
					$afwc_paypal->get_payout_method( true );
				}

				update_option( 'afwc_is_set_commission_payout_method', 'yes', 'no' );
			} elseif ( empty( get_option( 'afwc_commission_payout_method' ) ) ) {
				$afwc_paypal = is_callable( array( 'AFWC_PayPal_API', 'get_instance' ) ) ? AFWC_PayPal_API::get_instance() : null;

				if ( ! empty( $afwc_paypal ) && is_callable( array( $afwc_paypal, 'check_for_paypal_payout' ) ) ) {
					$afwc_paypal->check_for_paypal_payout();
				}
			}
		}

		/**
		 * Method to render the single select affiliate search select2.
		 *
		 * @param string $id The ID of the field.
		 * @param array  $args The arguments.
		 *
		 * @return void
		 */
		public function render_affiliate_search( $id = '', $args = array() ) {
			if ( empty( $id ) || ! is_array( $args ) ) {
				return;
			}

			$default_localize_data = array(
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'security' => wp_create_nonce( 'afwc-search-affiliate-users' ),
			);
			$args['localize_data'] = ( ! empty( $args['localize_data'] ) && is_array( $args['localize_data'] ) )
				? array_merge( $default_localize_data, $args['localize_data'] )
				: $default_localize_data;

			$default_args = array(
				'affiliate_id' => 0,
				'style'        => 'width: 100%;',
				'allow_clear'  => 'true',
				'disabled'     => false,
			);
			$args         = wp_parse_args( $args, $default_args );

			$affiliate_id = intval( $args['affiliate_id'] );
			$class        = 'afwc-affiliate-search';

			if ( ! wp_script_is( 'affiliate-user-search' ) ) {
				$plugin_data = self::get_plugin_data();
				wp_register_script( 'affiliate-user-search', AFWC_PLUGIN_URL . '/assets/js/lib/affiliate-search.js', array( 'jquery', 'wp-i18n', 'select2', 'wc-enhanced-select' ), $plugin_data['Version'], true );

				if ( function_exists( 'wp_set_script_translations' ) ) {
					wp_set_script_translations( 'affiliate-user-search', 'affiliate-for-woocommerce' );
				}

				wp_enqueue_script( 'affiliate-user-search' );

				wp_localize_script( 'affiliate-user-search', 'affiliateParams', $args['localize_data'] );
			}

			$user_string = '';
			if ( ! empty( $affiliate_id ) ) {
				$user_id = afwc_get_user_id_based_on_affiliate_id( $affiliate_id );
				if ( ! empty( $user_id ) ) {
					$user = get_user_by( 'id', $user_id );
					if ( is_object( $user ) && $user instanceof WP_User ) {
						$user_string = sprintf(
							/* translators: 1: user display name 2: user ID 3: user email */
							esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'affiliate-for-woocommerce' ),
							! empty( $user->display_name ) ? $user->display_name : '',
							absint( $user_id ),
							! empty( $user->user_email ) ? $user->user_email : ''
						);
					}
				}
			}

			?>
			<select id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $class ); ?>" style="<?php echo esc_attr( $args['style'] ); ?>" data-placeholder="<?php echo esc_attr_x( 'Search by email, username or name', 'affiliate search placeholder', 'affiliate-for-woocommerce' ); ?>" data-allow-clear="<?php echo esc_attr( $args['allow_clear'] ); ?>" data-action="afwc_json_search_affiliates" <?php disabled( (bool) $args['disabled'] ); ?> >
				<?php if ( ! empty( $affiliate_id ) ) { ?>
					<option value="<?php echo esc_attr( $affiliate_id ); ?>" selected="selected"><?php echo esc_html( wp_kses_post( $user_string ) ); ?></option>
				<?php } ?>
			</select>
			<?php
		}

		/**
		 * Method to provide the values to select2 affiliate search.
		 *
		 * @return void
		 */
		public function afwc_json_search_affiliates() {
			check_ajax_referer( 'afwc-search-affiliate-users', 'security' );

			$term = ( ! empty( $_GET['term'] ) ) ? (string) urldecode( stripslashes( wp_strip_all_tags( $_GET ['term'] ) ) ) : ''; // phpcs:ignore
			if ( empty( $term ) ) {
				wp_die();
			}

			$users = $this->get_affiliates( afwc_get_default_user_search_args( $term ) );

			echo wp_json_encode( ! empty( $users ) ? $users : array() );
			wp_die();
		}

		/**
		 * Method to display the product referral the button.
		 *
		 * @return void.
		 */
		public function add_product_referral_button() {
			global $product;

			if ( ! ( $product instanceof WC_Product ) ) {
				return;
			}

			$product_id = is_callable( array( $product, 'get_id' ) ) ? absint( $product->get_id() ) : 0;
			if ( empty( $product_id ) ) {
				return;
			}

			$product_type = is_callable( array( $product, 'get_type' ) ) ? $product->get_type() : '';

			$style = '';
			$class = 'single-product-affiliate-link';

			if ( 'variable' === $product_type || 'variable-subscription' === $product_type ) {
				$style  = 'display:none;';
				$class .= ' disabled';
			}

			$theme = wp_get_theme();
			if ( $theme instanceof WP_Theme && is_callable( array( $theme, 'get_template' ) ) && 'astra' === $theme->get_template() ) {
				$style .= 'padding: 10px 20px;margin: 0 10px;';
			}

			$this->print_product_referral_button(
				$product_id,
				array(
					'class' => $class,
					'style' => $style,
				)
			);
		}

		/**
		 * Method to display the button with referral URL.
		 *
		 * @param int   $product_id The product Id.
		 * @param array $args The arguments.
		 * @return void.
		 */
		public function print_product_referral_button( $product_id = 0, $args = array() ) {
			$product_id = absint( $product_id );
			if ( empty( $product_id ) ) {
				return;
			}

			$link = afwc_get_product_affiliate_url( $product_id, get_current_user_id() );
			if ( empty( $link ) ) {
				return;
			}

			if ( ! wp_script_is( 'afwc-affiliate-link' ) ) {
				wp_enqueue_script( 'afwc-affiliate-link' );
			}

			if ( ! wp_script_is( 'afwc-click-to-copy' ) ) {
				wp_enqueue_script( 'afwc-click-to-copy' );
			}

			$class = 'woocommerce-button button afwc-click-to-copy ' . ( ! empty( $args['class'] ) ? $args['class'] : '' );
			$style = ! empty( $args['style'] ) ? $args['style'] : '';

			/**
			 * Filter to modify the label of the product referral link button.
			 *
			 * @param string The label of the button.
			 * @param array  The context array.
			 *
			 * @since 8.10.0
			 */
			$label = apply_filters(
				'afwc_product_referral_link_label',
				_x( 'Click to copy referral link', "text to copy the affiliate's product-specific referral link", 'affiliate-for-woocommerce' ),
				array(
					'product_id' => $product_id,
					'source'     => $this,
				)
			);

			printf(
				'<a href="%1$s" data-ctp="%1$s" data-product-referral-link="%1$s" class="%2$s" style="%3$s">%4$s</a>',
				esc_url( $link ),
				esc_attr( $class ),
				esc_attr( $style ),
				esc_attr( $label )
			);
		}

		/**
		 * Ajax callback method to get the referral url.
		 *
		 * @return void.
		 */
		public function get_product_affiliate_link() {
			check_ajax_referer( 'afwc-product-affiliate-link', 'security' );

			if ( empty( $_POST['product_id'] ) ) {
				wp_send_json_error();
			}

			wp_send_json_success(
				array(
					'url' => afwc_get_product_affiliate_url( absint( $_POST['product_id'] ), get_current_user_id() ),
				)
			);
		}

		/**
		 * Register the global scripts.
		 *
		 * @return void.
		 */
		public function register_global_scripts() {
			$plugin_data = self::get_plugin_data();
			wp_register_script( 'afwc-click-to-copy', AFWC_PLUGIN_URL . '/assets/js/lib/afwc-click-to-copy.js', array(), $plugin_data['Version'], true );
			wp_register_script( 'afwc-affiliate-link', AFWC_PLUGIN_URL . '/assets/js/lib/afwc-affiliate-link.js', array( 'jquery', 'wp-i18n' ), $plugin_data['Version'], true );
			wp_localize_script(
				'afwc-affiliate-link',
				'afwcAffiliateLinkParams',
				array(
					'product' => array(
						'ajaxURL'  => WC_AJAX::get_endpoint( 'afwc_get_product_affiliate_link' ),
						'security' => wp_create_nonce( 'afwc-product-affiliate-link' ),
					),
				)
			);
			if ( ! wp_script_is( 'afwc-date-functions', 'registered' ) ) {
				wp_register_script( 'afwc-date-functions', AFWC_PLUGIN_URL . '/assets/js/lib/afwc-date-functions.js', array(), $plugin_data['Version'], true );
			}
		}

		/**
		 * Method to log errors during any process within a function or method.
		 *
		 * @param string $callable_name  The name of the function or method.
		 * @param string $error          The error message.
		 * @param array  $args           Optional.  Additional args.
		 *
		 * @return void
		 */
		public static function log_error( $callable_name = '', $error = '', $args = array() ) {
			self::log(
				'error',
				sprintf(
					/* translators: 1: Callable name 2: Error message */
					_x(
						'Error in %1$s. Details: %2$s',
						'Error message details',
						'affiliate-for-woocommerce'
					),
					! empty( $callable_name ) ? $callable_name : '',
					! empty( $error ) ? $error : ''
				),
				$args
			);
		}

		/**
		 * Get the minimum date time from the trackers.
		 * Currently, it considers only visitors and referrals.
		 *
		 * @param string $format The format to return the datetime.
		 * @param bool   $gmt Whether to return the GMT based datetime.
		 * @param int    $affiliate_id The affiliate ID if get the datetime based on affiliate ID.
		 *
		 * @return string Return the datetime.
		 */
		public function get_minimum_tracking_datetime( $format = 'Y-m-d H:i:s', $gmt = false, $affiliate_id = 0 ) {
			global $wpdb;

			try {
				if ( ! empty( $affiliate_id ) && is_scalar( $affiliate_id ) ) {
					$datetime = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT MIN(trackers.min_datetime)
								FROM (
									SELECT MIN(datetime) AS min_datetime
										FROM {$wpdb->prefix}afwc_hits
									WHERE affiliate_id = %d
									UNION ALL
									SELECT MIN(datetime) AS min_datetime
										FROM {$wpdb->prefix}afwc_referrals
									WHERE affiliate_id = %d
								) AS trackers",
							intval( $affiliate_id ),
							intval( $affiliate_id )
						)
					);
				} else {
					$datetime = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						"SELECT MIN(trackers.min_datetime)
							FROM (
								SELECT MIN(datetime) AS min_datetime
									FROM {$wpdb->prefix}afwc_hits
								UNION ALL
								SELECT MIN(datetime) AS min_datetime
									FROM {$wpdb->prefix}afwc_referrals
							) AS trackers"
					);
				}
				if ( empty( $datetime ) || ! is_scalar( $datetime ) ) {
					return '';
				}
				$result = empty( $gmt ) ? get_date_from_gmt( $datetime, 'Y-m-d H:i:s' ) : gmdate( $format, strtotime( $datetime ) );
			} catch ( Exception $e ) {
				self::log_error( __METHOD__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
				$result = '';
			}

			return ! empty( $result ) ? $result : '';
		}

		/**
		 * Method to save referral URL identifier.
		 *
		 * @param int    $user_id The affiliate ID.
		 * @param string $identifier The new identifier.
		 *
		 * @throws Exception If any error during the process.
		 * @return bool Return true if updated otherwise false.
		 */
		public function save_ref_url_identifier( $user_id = 0, $identifier = '' ) {
			if ( empty( $user_id ) ) {
				throw new Exception(
					esc_html_x(
						'Affiliate ID missing for updating affiliate URL identifier.',
						'referral url identifier updating error message',
						'affiliate-for-woocommerce'
					)
				);
			}

			if ( empty( $identifier ) ) {
				throw new Exception(
					esc_html_x(
						'The affiliate URL identifier can not be empty.',
						'referral url identifier updating error message',
						'affiliate-for-woocommerce'
					)
				);
			}

			if ( is_numeric( $identifier ) ) {
				throw new Exception( esc_html_x( 'Numeric values are not allowed for affiliate URL identifier.', 'referral url identifier updating error message', 'affiliate-for-woocommerce' ) );
			}

			$identifier_regex_pattern = afwc_affiliate_identifier_regex_pattern();

			if ( ! empty( $identifier_regex_pattern ) && ! preg_match( '/' . $identifier_regex_pattern . '/', $identifier ) ) {
				throw new Exception(
					esc_html(
						/**
						 * Filter to modify the affiliate URL identifier regex pattern error message.
						 *
						 * @param string The error message.
						 *
						 * @since 6.27.0
						 */
						apply_filters(
							'afwc_affiliate_identifier_regex_pattern_error_message',
							_x(
								'Invalid affiliate URL identifier. It should be a combination of alphabets and numbers, but the number should not be in the first position.',
								'referral identifier pattern validation error message',
								'affiliate-for-woocommerce'
							)
						)
					)
				);
			}

			$user_with_ref_url_id = afwc_get_affiliate_id_by_assigned_identifier( $identifier );

			if ( ! empty( $user_with_ref_url_id ) ) {
				// Throw error if the identifier is already exists.
				if ( intval( $user_id ) === intval( $user_with_ref_url_id ) ) {
					throw new Exception(
						esc_html_x(
							'Old and new affiliate URL identifier are same. Please choose a different identifier.',
							'referral url identifier updating error message',
							'affiliate-for-woocommerce'
						)
					);
				} else {
					throw new Exception(
						esc_html_x(
							'The URL identifier already exists. Please choose a different identifier.',
							'referral url identifier updating error message',
							'affiliate-for-woocommerce'
						)
					);
				}
			}

			return (bool) update_user_meta( $user_id, 'afwc_ref_url_id', $identifier );
		}

		/**
		 * Method to get the remaining refund days for the order.
		 *
		 * @param int $order_created_date The order created date in UNIX timestamp.
		 *
		 * @return int Return the remaining days.
		 */
		public function get_remaining_refund_days_for_order( $order_created_date = '' ) {
			if ( empty( $order_created_date ) || ! is_numeric( $order_created_date ) || $order_created_date < 0 ) {
				return 0;
			}
			$refund_period_in_seconds          = absint( get_option( 'afwc_order_refund_period_in_days', 30 ) ) * DAY_IN_SECONDS;
			$order_refund_time_diff_in_seconds = time() - absint( $order_created_date );
			if ( $order_refund_time_diff_in_seconds < $refund_period_in_seconds ) {
				return ceil( ( $refund_period_in_seconds - $order_refund_time_diff_in_seconds ) / DAY_IN_SECONDS ); // Used `ceil` here to round-up remaining refund days, E.g.: return 2 for 1.3 days.
			}
			return 0;
		}
	}
}
