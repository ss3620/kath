<?php
/**
 * Class to handle payouts related settings
 *
 * @package     affiliate-for-woocommerce/includes/admin/settings/
 * @since       7.18.0
 * @version     1.8.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Payouts_Admin_Settings' ) ) {

	/**
	 * Main class get payouts section settings
	 */
	class AFWC_Payouts_Admin_Settings {

		/**
		 * Variable to hold instance of AFWC_Payouts_Admin_Settings
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
		 * @return AFWC_Payouts_Admin_Settings Singleton object of this class
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

			// Render automatic payouts select2 row.
			add_action( 'woocommerce_admin_field_afwc_ap_includes_list', array( $this, 'render_ap_include_list_input' ) );

			// Required to save the setting value properly.
			add_filter( 'woocommerce_admin_settings_sanitize_option_afwc_automatic_payout_includes', array( $this, 'sanitize_ap_include_list' ), 10, 2 );

			add_filter( 'woocommerce_admin_settings_sanitize_option_afwc_commission_payout_day', array( $this, 'sanitize_commission_payout_day' ) );

			// Ajax action for automatic payouts.
			add_action( 'wp_ajax_afwc_search_ap_includes_list', array( $this, 'afwc_json_search_include_ap_list' ) );

			// Register custom media upload input. It will be removed once WooCommerce introduce any media upload field.
			add_action( 'woocommerce_admin_field_afwc_media_uploader', array( $this, 'render_image_upload_input' ) );

			// Enqueue the scripts.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		}

		/**
		 * Method to get payouts section settings
		 *
		 * @return array
		 */
		public function get_section_settings() {
			// Check if PayPal API is enabled.
			$paypal_api_settings = array();
			if ( is_callable( array( 'AFWC_PayPal_API', 'get_instance' ) ) ) {
				$afwc_paypal_api_instance = AFWC_PayPal_API::get_instance();
				if ( is_callable( array( $afwc_paypal_api_instance, 'get_api_setting_status' ) ) ) {
					$paypal_api_settings = $afwc_paypal_api_instance->get_api_setting_status();
				}
			}

			/**
			 * Filter to allow modifying the allowed coupon types for payout via coupons setting.
			 *
			 * @since 7.21.0
			 */
			$allowed_coupon_types   = apply_filters( 'afwc_allowed_coupon_type_for_payouts', array( 'fixed_cart' ), array( 'source' => $this ) );
			$available_coupon_types = array();
			// Intersect two arrays to get result as coupon_type => coupon label for the setting.
			$available_coupon_types = array_intersect_key( wc_get_coupon_types(), array_flip( $allowed_coupon_types ) );

			/**
			 * Filter to allow modifying the description for payout via coupons setting.
			 *
			 * @since 7.21.0
			 */
			$payout_via_coupon_desc = apply_filters(
				'afwc_coupon_payouts_setting_description',
				sprintf(
					/* translators: %s Plugin URI of Affiliate Store Credit Payouts Integration for WooCommerce. */
					_x(
						'Leave it blank to disable payout via coupons. Use %s (free WordPress plugin) to issue a Store Credit/Gift Certificate payout.',
						'setting description',
						'affiliate-for-woocommerce'
					),
					'<a target="_blank" href="https://wordpress.org/plugins/affiliate-store-credit-payouts-integration-for-woocommerce/">' . _x( 'Affiliate Store Credit Payouts Integration', 'free plugin name', 'affiliate-for-woocommerce' ) . '</a>'
				),
				array( 'source' => $this )
			);

			// Stripe.
			$redirect_uri               = afwc_myaccount_dashboard_url() . '?afwc-tab=resources';
			$get_stripe_connect_details = 'https://dashboard.stripe.com/settings/connect/onboarding-options/oauth';

			$is_payout_via_stripe_enabled = ( 'yes' === get_option( 'afwc_enable_stripe_payout', 'no' ) );
			$is_automatic_payouts_enabled = ( 'yes' === get_option( 'afwc_enable_automatic_payouts', 'no' ) );
			$is_invoice_enabled           = ( 'yes' === get_option( 'afwc_enable_payout_invoice', 'no' ) );

			$afwc_payouts_admin_settings = array(
				array(
					'title' => afwc_get_settings_sections( $this->section ),
					'type'  => 'title',
					'id'    => 'afwc_payouts_admin_settings',
					'desc'  => _x( 'Settings to manage affiliate commission payouts.', 'Payouts setting section description', 'affiliate-for-woocommerce' ),
				),
				array(
					'name'              => _x( 'Refund period', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'              => _x( 'A refund isn\'t a successful referral. Therefore, enter how many days to wait before paying commissions for successful referrals. If you don\'t have a refund period, enter 0. Each referral within the refund period will be marked to show the remaining days in the refund period. In case automatic payouts are enabled, referrals within the refund period will not be included in it.', 'setting description', 'affiliate-for-woocommerce' ),
					'id'                => 'afwc_order_refund_period_in_days',
					'type'              => 'number',
					'default'           => 30,
					'autoload'          => false,
					'desc_tip'          => false,
					'custom_attributes' => array(
						'min' => 0,
					),
					'placeholder'       => _x( 'Enter the number of days. Default is 30.', 'placeholder for refund window setting', 'affiliate-for-woocommerce' ),
				),
				array(
					'name'              => _x( 'Minimum affiliate commission for payout', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'              => _x( 'An affiliate earnings must reach this minimum threshold value to qualify for commission payouts. This setting ensures that only eligible order\'s referrals are included for payouts. In case automatic payouts are enabled, order referrals below this threshold will not qualify for payouts.', 'setting description', 'affiliate-for-woocommerce' ),
					'id'                => 'afwc_minimum_commission_balance',
					'type'              => 'number',
					'default'           => 50,
					'autoload'          => false,
					'desc_tip'          => false,
					'custom_attributes' => array(
						'min' => 1,
					),
					'placeholder'       => _x( 'Enter minimum commission amount. Default is 50.', 'placeholder for payment day setting', 'affiliate-for-woocommerce' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'afwc_payouts_admin_settings',
				),
				array(
					'title' => _x( 'PayPal', 'Paypal payout setting section title', 'affiliate-for-woocommerce' ),
					'type'  => 'title',
					'id'    => 'afwc_paypal_payout_admin_settings',
					'desc'  => _x( 'Set up PayPal as a payout method to send affiliate commissions.', 'PayPal payout setting description', 'affiliate-for-woocommerce' ),
				),
				array(
					'name'     => _x( 'PayPal email address', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'     => _x( 'Allow affiliates to enter their PayPal email address from their My Account > Affiliates > Profile for PayPal payouts', 'setting description', 'affiliate-for-woocommerce' ),
					'desc_tip' => _x( 'Disabling this will not show it to affiliates in their account.', 'setting description tip', 'affiliate-for-woocommerce' ),
					'id'       => 'afwc_allow_paypal_email',
					'type'     => 'checkbox',
					'default'  => 'no',
					'autoload' => false,
				),
				array(
					'name'              => _x( 'Payout via PayPal', 'setting name', 'affiliate-for-woocommerce' ),
					'type'              => 'checkbox',
					'default'           => 'no',
					'autoload'          => false,
					'value'             => ( ! empty( $paypal_api_settings['value'] ) ) ? $paypal_api_settings['value'] : 'no',
					'desc'              => ( ! empty( $paypal_api_settings['desc'] ) ) ? $paypal_api_settings['desc'] : '',
					'desc_tip'          => ( ! empty( $paypal_api_settings['desc_tip'] ) ) ? $paypal_api_settings['desc_tip'] : '',
					'id'                => 'afwc_paypal_payout',
					'custom_attributes' => array(
						'disabled' => 'disabled',
					),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'afwc_paypal_payout_admin_settings',
				),
				// Stripe settings
				// allow enabling + same will show connect in affiliate's account.
				array(
					'title' => _x( 'Stripe', 'Stripe payout setting section title', 'affiliate-for-woocommerce' ),
					'type'  => 'title',
					'id'    => 'afwc_stripe_payout_admin_settings',
					'desc'  => _x( 'Set up Stripe as a payout method to send affiliate commissions.', 'Stripe payout setting description', 'affiliate-for-woocommerce' ),
				),
				array(
					'name'     => _x( 'Payout via Stripe', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'     => _x( 'Pay commissions via Stripe by allowing affiliates to link their Stripe accounts to your store', 'setting description', 'affiliate-for-woocommerce' ),
					'id'       => 'afwc_enable_stripe_payout',
					'type'     => 'checkbox',
					'default'  => 'no',
					'autoload' => false,
					'desc_tip' => _x( 'Disabling this will stop payouts through Stripe.', 'setting description tip', 'affiliate-for-woocommerce' ),
				),
				// to accept publishable_key.
				array(
					'name'              => _x( 'Stripe Publishable Key', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'              => sprintf(
						/* translators: 1: Link to Stripe API keys dashboard 2: Stripe Connect documentation for testing with OAuth  */
						_x( 'To locate, go to <a href="%1$s" target="_blank">Stripe Dashboard > Developers > API keys ></a> <strong>Standard keys > Publishable key</strong> (more info: <a href="%2$s" target="_blank">%2$s</a>). Mandatory to add this for commission payouts to work.', 'setting description', 'affiliate-for-woocommerce' ),
						'https://dashboard.stripe.com/apikeys',
						'https://docs.stripe.com/keys'
					),
					'id'                => 'afwc_stripe_live_publishable_key',
					'type'              => 'text',
					'placeholder'       => _x( 'Values starting with "pk_"', 'Placeholder for Stripe Publishable Key setting', 'affiliate-for-woocommerce' ),
					'autoload'          => false,
					'desc_tip'          => false,
					'row_class'         => ( ! $is_payout_via_stripe_enabled ) ? 'afwc-hide' : '',
					'custom_attributes' => array(
						'data-afwc-show-when' => 'afwc_enable_stripe_payout',
					),
				),
				// to accept secret_key.
				array(
					'name'              => _x( 'Stripe Secret Key', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'              => sprintf(
						/* translators: 1: Link to Stripe dashboard 2: Stripe Connect documentation for testing with OAuth  */
						_x( 'To locate, go to <a href="%1$s" target="_blank">Stripe Dashboard > Developers > API keys ></a> <strong>Standard keys > Secret key > Reveal live key</strong> (more info: <a href="%2$s" target="_blank">%2$s</a>). Mandatory to add this for commission payouts to work.', 'setting description', 'affiliate-for-woocommerce' ),
						'https://dashboard.stripe.com/apikeys',
						'https://docs.stripe.com/keys'
					),
					'id'                => 'afwc_stripe_live_secret_key',
					'type'              => 'text',
					'placeholder'       => _x( 'Values starting with "sk_"', 'Placeholder for Stripe Secret Key setting', 'affiliate-for-woocommerce' ),
					'autoload'          => false,
					'desc_tip'          => false,
					'row_class'         => ( ! $is_payout_via_stripe_enabled ) ? 'afwc-hide' : '',
					'custom_attributes' => array(
						'data-afwc-show-when' => 'afwc_enable_stripe_payout',
					),
				),
				// to accept client_id.
				array(
					'name'              => _x( 'Stripe Client ID', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'              => sprintf(
						/* translators: 1: Link to Stripe dashboard 2: Stripe Connect documentation for testing with OAuth  */
						_x( 'To locate, go to <a href="%1$s" target="_blank">Stripe Dashboard > Settings > Connect > Onboarding options > OAuths ></a> <strong>Live mode client ID</strong> (more info: <a href="%2$s" target="_blank">%2$s</a>). Mandatory to add this for commission payouts to work.', 'setting description', 'affiliate-for-woocommerce' ),
						$get_stripe_connect_details,
						'https://docs.stripe.com/connect/testing#using-oauth'
					),
					'id'                => 'afwc_stripe_connect_live_client_id',
					'type'              => 'text',
					'placeholder'       => _x( 'Account ID - minimum 35 characters', 'Placeholder for Stripe Client ID setting', 'affiliate-for-woocommerce' ),
					'autoload'          => false,
					'desc_tip'          => false,
					'row_class'         => ( ! $is_payout_via_stripe_enabled ) ? 'afwc-hide' : '',
					'custom_attributes' => array(
						'data-afwc-show-when' => 'afwc_enable_stripe_payout',
					),
				),
				// to set redirect URI.
				array(
					'name'              => _x( 'Add redirect URIs', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'              => sprintf(
						/* translators: 1: Link to Stripe dashboard 2: current site's affiliate's my account endpoint for resources/profile tab  */
						_x( 'A <strong>Redirection URI is required</strong> when users connect their account to your site.<br><br>Go to <a href="%1$s" target="_blank">Stripe Dashboard > Settings > Connect > Onboarding options > OAuths ></a> <strong>Redirects</strong> section and add the following URl to redirect: <code>%2$s</code><br><br>Redirects URI can be defined on test and live mode, we would recommend to test both scenarios.', 'setting description', 'affiliate-for-woocommerce' ),
						$get_stripe_connect_details,
						$redirect_uri
					),
					'id'                => 'afwc_stripe_add_redirect_uris',
					'type'              => 'checkbox',
					'default'           => 'no',
					'autoload'          => false,
					'desc_tip'          => _x( 'It is mandatory to set this in your Stripe account to process commission payouts. Otherwise, payouts won\'t be processed.', 'setting description tip', 'affiliate-for-woocommerce' ),
					'row_class'         => ( ! $is_payout_via_stripe_enabled ) ? 'afwc-hide' : '',
					'custom_attributes' => array(
						'data-afwc-show-when' => 'afwc_enable_stripe_payout',
						'style'               => 'display: none;', // We do not want to allow selecting checkbox of this setting. So hide it for now.
					),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'afwc_stripe_payout_admin_settings',
				),
				array(
					'title' => _x( 'Coupons', 'Coupons payout setting section title', 'affiliate-for-woocommerce' ),
					'type'  => 'title',
					'id'    => 'afwc_coupon_payout_method_admin_settings',
					'desc'  => _x( 'Select coupon types to send affiliate commissions as coupons.', 'Coupons payout setting description', 'affiliate-for-woocommerce' ),
				),
				array(
					'name'              => _x( 'Coupon types', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'              => $payout_via_coupon_desc,
					'id'                => 'afwc_enabled_for_coupon_payout',
					'type'              => 'multiselect',
					'class'             => 'wc-enhanced-select',
					'desc_tip'          => false,
					'options'           => $available_coupon_types,
					'row_class'         => ( 'no' === get_option( 'woocommerce_enable_coupons' ) ? 'afwc-hide' : '' ), // Hide if WooCommerce > Enable coupons is disabled.
					'autoload'          => false,
					'custom_attributes' => array(
						'data-placeholder' => _x( 'Select the coupon type...', 'placeholder for allowed coupon types for payouts', 'affiliate-for-woocommerce' ),
					),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'afwc_coupon_payout_method_admin_settings',
				),
				array(
					'title' => _x( 'Auto-pay commissions', 'Automatic payouts setting section title', 'affiliate-for-woocommerce' ),
					'type'  => 'title',
					'id'    => 'afwc_automatic_payouts_admin_settings',
					'desc'  => _x( 'Automatically send commission payouts to your affiliates once a month.', 'Auto Pay commissions setting description', 'affiliate-for-woocommerce' ),
				),
				array(
					'name'     => _x( 'Automatic payouts', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'     => _x( 'Configure the settings and automate the payout process based on your preference.', 'setting description', 'affiliate-for-woocommerce' ),
					'desc_tip' => _x( 'Supports PayPal, Stripe and Coupons - if enabled.', 'setting description tip', 'affiliate-for-woocommerce' ),
					'id'       => 'afwc_enable_automatic_payouts',
					'type'     => 'checkbox',
					'default'  => 'no',
					'autoload' => false,
				),
				array(
					'name'              => _x( 'Automatic payouts include affiliates', 'setting name for automatic payouts to include affiliates', 'affiliate-for-woocommerce' ),
					'desc'              => sprintf(
						/* translators: Number of affiliates allowed for automatic payouts */
						_x( 'Select up to %s affiliates for automatic commission payouts (beta launch). Affiliates qualify for automatic payouts if they have set a payout method in their account.', 'Admin setting description for affiliates to include for automatic payouts', 'affiliate-for-woocommerce' ),
						AFWC_AP_INCLUDE_AFFILIATES_LIMIT
					),
					'id'                => 'afwc_automatic_payout_includes',
					'type'              => 'afwc_ap_includes_list',
					'class'             => 'afwc-automatic-payouts-includes-search wc-enhanced-select',
					'placeholder'       => _x( 'Search affiliates by email, username or name', 'Admin setting placeholder to search affiliates to include them for automatic payouts', 'affiliate-for-woocommerce' ),
					'options'           => get_option( 'afwc_automatic_payout_includes', array() ),
					'row_class'         => ( ! $is_automatic_payouts_enabled ) ? 'afwc-hide' : '',
					'custom_attributes' => array(
						'data-afwc-show-when' => 'afwc_enable_automatic_payouts',
					),
				),
				array(
					'name'              => _x( 'Maximum commission to pay an affiliate', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'              => _x( 'Set the maximum commission an affiliate can receive in automatic payouts. Set it to 0 if no limit. This setting ensures automatic payouts stay within a specified limit. Referrals exceeding this limit won\'t be included in automatic payouts.', 'setting description', 'affiliate-for-woocommerce' ),
					'id'                => 'afwc_maximum_commission_balance',
					'type'              => 'number',
					'default'           => 0,
					'autoload'          => false,
					'desc_tip'          => false,
					'row_class'         => ( ! $is_automatic_payouts_enabled ) ? 'afwc-hide' : '',
					'custom_attributes' => array(
						'min'                 => 0,
						'data-afwc-show-when' => 'afwc_enable_automatic_payouts',
					),
					'placeholder'       => _x( 'Enter maximum commission amount. Default is 0.', 'placeholder for payment day setting', 'affiliate-for-woocommerce' ),
				),
				array(
					'name'              => _x( 'Commission payout day', 'setting name', 'affiliate-for-woocommerce' ),
					'desc'              => _x( 'Automatic commission payouts will be issued on this fixed day of each month you enter in the box. Leaving it blank will set the default day to the 15th of each month. If the entered date falls between the 28th and 31st, payouts will be automatically sent on the last day of that particular month. Payouts are processed in UTC so the date you see in your own timezone may vary.', 'setting description', 'affiliate-for-woocommerce' ),
					'id'                => 'afwc_commission_payout_day',
					'type'              => 'number',
					'default'           => 15,
					'autoload'          => false,
					'desc_tip'          => false,
					'row_class'         => ( ! $is_automatic_payouts_enabled ) ? 'afwc-hide' : '',
					'custom_attributes' => array(
						'min'                 => 1,
						'max'                 => 31,
						'data-afwc-show-when' => 'afwc_enable_automatic_payouts',
					),
					'placeholder'       => _x( 'Enter day of the month. Default is 15.', 'placeholder for payment day setting', 'affiliate-for-woocommerce' ),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'afwc_automatic_payouts_admin_settings',
				),
				array(
					'title' => _x( 'Generate invoices for payouts', 'Payout invoice setting section title', 'affiliate-for-woocommerce' ),
					'type'  => 'title',
					'desc'  => sprintf(
						/* translators: WooCommerce store settings link for store address */
						_x( 'The store address details on the invoice are taken from <a href="%1$s" target="_blank">WooCommerce > Settings > General > Store Address</a>. The affiliate\'s address is taken from their My Account > Addresses > Billing address.', 'Admin setting description for payout invoice title', 'affiliate-for-woocommerce' ),
						esc_url(
							add_query_arg(
								array(
									'page' => 'wc-settings',
									'tab'  => 'general',
								),
								admin_url( 'admin.php' )
							)
						)
					) . '<br>' . _x( 'If the address is not found, it will not be shown on the invoice.', 'Admin setting description for payout invoice address not found', 'affiliate-for-woocommerce' ),
					'id'    => 'afwc_payout_invoice_admin_settings',
				),
				array(
					'name'     => _x( 'Payout invoice', 'Admin setting name for Payout invoice', 'affiliate-for-woocommerce' ),
					'desc'     => _x( 'Enable this to generate an invoice for each commission payout', 'Admin setting description for Payout invoice', 'affiliate-for-woocommerce' ),
					'id'       => 'afwc_enable_payout_invoice',
					'type'     => 'checkbox',
					'autoload' => false,
					'default'  => 'no',
					'desc_tip' => _x( 'When enabled, you can print invoices from the Payouts tab. To allow affiliates to print their invoices, enable the "Show and allow affiliates to print their invoice" below.', 'Admin setting description tip for Payout invoice', 'affiliate-for-woocommerce' ),
				),
				array(
					'name'              => _x( 'Select a logo', 'Admin setting name for Logo for payout invoice', 'affiliate-for-woocommerce' ),
					'desc'              => _x( 'Upload a logo representing your shop or business. It will be shown on the invoice.', 'Admin setting description for Logo for payout invoice', 'affiliate-for-woocommerce' ),
					'id'                => 'afwc_payout_invoice_logo',
					'type'              => 'afwc_media_uploader',
					'autoload'          => false,
					'row_class'         => ( ! $is_invoice_enabled ) ? 'afwc-hide' : '',
					'custom_attributes' => array(
						'data-afwc-show-when' => 'afwc_enable_payout_invoice',
					),
				),
				array(
					'name'              => _x( 'Show and allow affiliates to print their invoice', 'Admin setting name for Show and allow affiliates to print their invoice', 'affiliate-for-woocommerce' ),
					'desc'              => _x( 'Enable this to allow affiliates to print their invoices', 'Admin setting description for Show and allow affiliates to print their invoice', 'affiliate-for-woocommerce' ),
					'id'                => 'afwc_enable_payout_invoice_for_affiliate',
					'type'              => 'checkbox',
					'default'           => 'no',
					'desc_tip'          => _x( "When enabled, a new column to print invoices will be visible in the affiliate's account > Reports > Payout History.", 'Admin setting description tip for Show and allow affiliates to print their invoice', 'affiliate-for-woocommerce' ),
					'autoload'          => false,
					'row_class'         => ( ! $is_invoice_enabled ) ? 'afwc-hide' : '',
					'custom_attributes' => array(
						'data-afwc-show-when' => 'afwc_enable_payout_invoice',
					),
				),
				array(
					'type' => 'sectionend',
					'id'   => 'afwc_payout_invoice_admin_settings',
				),

			);

			return $afwc_payouts_admin_settings;
		}

		/**
		 * Method to get the affiliates who has either PayPal or Stripe meta set - to allow in automatic payouts.
		 *
		 * @param string $term The value.
		 * @param bool   $for_search Whether the method will be used for searching or fetching the details by id.
		 *
		 * @return array The list of found affiliate users.
		 */
		public function get_affiliates_with_automatic_payout_meta_data( $term = '', $for_search = false ) {
			if ( empty( $term ) ) {
				return array();
			}

			if ( true === $for_search ) {
				$affiliate_search = array_merge(
					afwc_get_default_user_search_args( $term ),
					array(
						'number'     => AFWC_AP_INCLUDE_AFFILIATES_LIMIT, // We are fetching search results (affiliates) respecting limit of how many affiliates are allowed for automatic payouts.
						'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
							// Filter affiliates based on if they have a payout method set or not.
							array(
								'key'     => 'afwc_payout_method',
								'value'   => '',
								'compare' => '!=',
							),
						),
					)
				);
			} else {
				$affiliate_search = array(
					'include' => ( ! is_array( $term ) ) ? (array) $term : $term,
				);
			}

			global $affiliate_for_woocommerce;

			$values = array();
			$values = is_callable( array( $affiliate_for_woocommerce, 'get_affiliates' ) ) ? $affiliate_for_woocommerce->get_affiliates( $affiliate_search ) : array();

			return $values;
		}

		/**
		 * Method to rendering the include list input field.
		 *
		 * @param array $value The value.
		 *
		 * @return void.
		 */
		public function render_ap_include_list_input( $value = array() ) {
			if ( empty( $value ) ) {
				return;
			}

			$id                = ! empty( $value['id'] ) ? $value['id'] : '';
			$options           = ! empty( $value['options'] ) ? $value['options'] : array();
			$field_description = is_callable( array( 'WC_Admin_Settings', 'get_field_description' ) ) ? WC_Admin_Settings::get_field_description( $value ) : array();
			?>	
				<tr valign="top" class="<?php echo ! empty( $value['row_class'] ) ? esc_attr( $value['row_class'] ) : ''; ?>">
					<th scope="row" class="titledesc"> 
						<label for="<?php echo esc_attr( $id ); ?>"> <?php echo ( ! empty( $value['title'] ) ? esc_html( $value['title'] ) : '' ); ?> </label>
					</th>
					<td class="forminp">
						<select
							name="<?php echo esc_attr( ! empty( $value['field_name'] ) ? $value['field_name'] : $id ); ?>[]"
							id="<?php echo esc_attr( $id ); ?>"
							style="<?php echo ! empty( $value['css'] ) ? esc_attr( $value['css'] ) : ''; ?>"
							class="<?php echo ! empty( $value['class'] ) ? esc_attr( $value['class'] ) : ''; ?>"
							data-placeholder="<?php echo ! empty( $value['placeholder'] ) ? esc_attr( $value['placeholder'] ) : ''; ?>"
							multiple="multiple"
							<?php echo is_callable( array( 'AFWC_Admin_Settings', 'get_html_attributes_string' ) ) ? wp_kses_post( AFWC_Admin_Settings::get_html_attributes_string( $value ) ) : ''; ?>
						>
						<?php
						foreach ( $options as $ids ) {
							$current_list = $this->get_affiliates_with_automatic_payout_meta_data( $ids );
							if ( ! empty( $current_list ) && is_array( $current_list ) ) {
								foreach ( $current_list as $id => $text ) {
									?>
									<option value="<?php echo esc_attr( $id ); ?>" selected="selected">
										<?php echo ! empty( $text ) ? esc_html( $text ) : ''; ?>
									</option>
									<?php
								}
							}
						}
						?>
						</select>
						<?php echo ! empty( $field_description['description'] ) ? wp_kses_post( $field_description['description'] ) : ''; ?>
					</td>
				</tr>
			<?php
		}

		/**
		 * Method to sanitize and format the value for ltc exclude list.
		 *
		 * @param array $value The value.
		 *
		 * @return array.
		 */
		public function sanitize_ap_include_list( $value = array() ) {
			if ( empty( $value ) || ! is_array( $value ) ) {
				return array();
			}

			// Manage & edit $value here if options are in groups.
			return $value;
		}

		/**
		 * Method to sanitize the commission payout day value.
		 * Ensures the default value of 15 is used when the field is left empty.
		 *
		 * @param mixed $value The value.
		 *
		 * @return int The sanitized payout day.
		 */
		public function sanitize_commission_payout_day( $value = '' ) {
			$value = absint( $value );

			return ( ! empty( $value ) ) ? $value : 15;
		}

		/**
		 * Ajax callback function to search the affiliates and affiliate tag.
		 */
		public function afwc_json_search_include_ap_list() {
			check_admin_referer( 'afwc-search-include-ap-list', 'security' );

			$term = ( ! empty( $_GET['term'] ) ) ? (string) urldecode( wp_strip_all_tags( wp_unslash( $_GET ['term'] ) ) ) : '';
			if ( empty( $term ) ) {
				wp_die();
			}

			$searched_list = $this->get_affiliates_with_automatic_payout_meta_data( $term, true );
			if ( empty( $searched_list ) || ! is_array( $searched_list ) ) {
				wp_send_json( array() );
			}

			$data = array();
			foreach ( $searched_list as $affiliate_user_id => $affiliate_details ) {
				$data[ $affiliate_user_id ] = $affiliate_details;
			}

			wp_send_json( $data );
		}

		/**
		 * Method to enqueue the scripts for payout invoice section.
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			wp_enqueue_media();
		}

		/**
		 * Method to rendering the image upload field.
		 *
		 * @param array $value The value.
		 *
		 * @return void.
		 */
		public function render_image_upload_input( $value = array() ) {
			if ( empty( $value ) ) {
				return;
			}

			$id         = ! empty( $value['id'] ) ? wc_clean( $value['id'] ) : '';
			$field_name = ! empty( $value['field_name'] ) ? wc_clean( $value['field_name'] ) : $id;
			if ( empty( $field_name ) ) {
				return;
			}
			$option_value       = get_option( $field_name, '' );
			$field_description  = is_callable( array( 'WC_Admin_Settings', 'get_field_description' ) ) ? WC_Admin_Settings::get_field_description( $value ) : array();
			$media_src          = ! empty( $option_value ) ? wp_get_attachment_url( $option_value ) : '';
			$upload_button_name = ! empty( $value['upload_button_text'] ) ? wc_clean( $value['upload_button_text'] ) : _x( 'Upload image', 'Button text for media upload button', 'affiliate-for-woocommerce' );
			$change_button_name = ! empty( $value['change_button_text'] ) ? wc_clean( $value['change_button_text'] ) : _x( 'Change image', 'Button text for media change button', 'affiliate-for-woocommerce' );
			?>
				<tr valign="top" class="<?php echo ! empty( $value['row_class'] ) ? esc_attr( $value['row_class'] ) : ''; ?>">
					<th scope="row" class="titledesc"> 
						<label for="<?php echo esc_attr( $id ); ?>"> <?php echo ( ! empty( $value['title'] ) ? esc_html( $value['title'] ) : '' ); ?> </label>
					</th>
					<td class="forminp">
						<div class="afwc-media-upload-section">
							<p class="afwc-media-preview <?php echo empty( $media_src ) ? 'afwc-hide' : ''; ?>">
								<img src="<?php echo esc_attr( $media_src ); ?>" width="200"/>
							</p>
							<input
								type="hidden"
								name="<?php echo esc_attr( $field_name ); ?>"
								id="<?php echo esc_attr( $id ); ?>"
								class="afwc-media-uploader-value"
								value="<?php echo esc_attr( $option_value ); ?>"
								<?php echo is_callable( array( 'AFWC_Admin_Settings', 'get_html_attributes_string' ) ) ? wp_kses_post( AFWC_Admin_Settings::get_html_attributes_string( $value ) ) : ''; ?>
							>
							<div class="afwc-action-buttons">
								<button
									type="button"
									id="afwc-select-media-btn"
									class="<?php echo ! empty( $media_src ) ? 'button' : 'afwc-media-uploader'; ?>"
									data-uploader-title="<?php echo ! empty( $value['uploader_title'] ) ? esc_attr( $value['uploader_title'] ) : ''; ?>"
									data-uploader-button-text="<?php echo ! empty( $value['uploader_button_text'] ) ? esc_attr( $value['uploader_button_text'] ) : ''; ?>"
									data-upload-button-text="<?php echo esc_attr( $upload_button_name ); ?>"
									data-change-button-text="<?php echo esc_attr( $change_button_name ); ?>"
								>
									<?php echo esc_html( ! empty( $media_src ) ? $change_button_name : $upload_button_name ); ?>
								</button>
								<button
									type="button" 
									class="afwc-media-remove button button-secondary <?php echo empty( $media_src ) ? 'afwc-hide' : ''; ?>"
								>
									<?php echo esc_html_x( 'Remove image', 'Button name for remove media', 'affiliate-for-woocommerce' ); ?>
								</button>
							</div>
						</div>
						<?php echo ! empty( $field_description['description'] ) ? wp_kses_post( $field_description['description'] ) : ''; ?>
					</td>
				</tr>
			<?php
		}
	}

}

AFWC_Payouts_Admin_Settings::get_instance();
