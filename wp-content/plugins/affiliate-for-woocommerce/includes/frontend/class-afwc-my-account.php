<?php
/**
 * Main class for Affiliates My Account
 *
 * @package   affiliate-for-woocommerce/includes/frontend/
 * @since     1.0.0
 * @version   1.17.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Referral_Mediums\Referral_Medium_Interface;
use AFWC\Referral_Mediums\Referral_Medium_Registry;

if ( ! class_exists( 'AFWC_My_Account' ) ) {

	/**
	 * Main class for Affiliates My Account
	 */
	class AFWC_My_Account {

		/**
		 * Variable to hold instance of AFWC_My_Account
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Endpoint
		 *
		 * @var $endpoint
		 */
		public $endpoint;

		/**
		 * Affiliate tab Endpoint.
		 *
		 * @var $afwc_tab_endpoint
		 */
		public $afwc_tab_endpoint;

		/**
		 * Affiliate section Endpoint.
		 *
		 * @var $afwc_section_endpoint
		 */
		public $afwc_section_endpoint = 'section';

		/**
		 * Report tables
		 *
		 * @var array
		 */
		private $report_tables = array( 'visits', 'products', 'referrals', 'payouts' );

		/**
		 * Get single instance of AFWC_My_Account
		 *
		 * @return AFWC_My_Account Singleton object of AFWC_My_Account
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

			$this->endpoint = get_option( 'woocommerce_myaccount_afwc_dashboard_endpoint', 'afwc-dashboard' );

			/**
			 * Filter to modify the affiliate dashboard endpoint in WooCommerce My Account page.
			 *
			 * @param string The endpoint.
			 *
			 * @since 1.17.2
			 */
			$this->afwc_tab_endpoint = apply_filters( 'afwc_dashboard_tab_endpoint', get_option( 'afwc_dashboard_tab_endpoint', 'afwc-tab' ) );

			// Register affiliate endpoint.
			add_action( 'init', array( $this, 'endpoint' ) );
			// Add admin setting to change affiliate dashboard endpoint.
			add_action( 'init', array( $this, 'endpoint_hooks' ) );

			add_action( 'wp_loaded', array( $this, 'afw_myaccount' ) );

			add_action( 'wc_ajax_afwc_reload_dashboard', array( $this, 'ajax_reload_dashboard' ) );
			foreach ( $this->report_tables as $table ) {
				add_action( 'wc_ajax_afwc_load_more_' . $table, array( $this, 'ajax_load_more_handler' ) );
			}
			add_action( 'wc_ajax_afwc_payout_invoice', array( $this, 'ajax_payout_invoice' ) );

			add_action( 'wc_ajax_afwc_save_ref_url_identifier', array( $this, 'afwc_save_ref_url_identifier' ) );
			add_action( 'wc_ajax_afwc_save_account_details', array( $this, 'afwc_save_account_details' ) );

			add_filter( 'afwc_get_merge_tag_value_afwc_affiliate_coupon', array( $this, 'get_afwc_affiliate_coupon_merge_tag_value' ), 10, 2 );

			// Stripe connect's connection handle.
			add_action( 'template_redirect', array( $this, 'afwc_handle_stripe_connect' ) );

			// Register the shortcode for affiliate dashboard.
			add_shortcode( 'afwc_dashboard', array( $this, 'afwc_dashboard_shortcode_content' ) );
		}

		/**
		 * Method to handle WC compatibility related function call from appropriate class
		 *
		 * @param string $function_name Function to call.
		 * @param array  $arguments     Array of arguments passed while calling $function_name.
		 *
		 * @return mixed Result of function call.
		 */
		public function __call( $function_name = '', $arguments = array() ) {

			if ( empty( $function_name ) || ! is_callable( array( 'SA_WC_AFW_Compatibility', $function_name ) ) ) {
				return;
			}

			if ( ! empty( $arguments ) ) {
				return call_user_func_array( 'SA_WC_AFW_Compatibility::' . $function_name, $arguments );
			} else {
				return call_user_func( 'SA_WC_AFW_Compatibility::' . $function_name );
			}
		}

		/**
		 * Function to add affiliates endpoint to My Account.
		 *
		 * @see https://developer.woocommerce.com/2016/04/21/tabbed-my-account-pages-in-2-6/
		 */
		public function endpoint() {
			add_rewrite_endpoint( $this->endpoint, EP_ROOT | EP_PAGES );
		}

		/**
		 * Hooks for endpoint
		 */
		public function endpoint_hooks() {
			if ( is_callable( array( $this, 'is_wc_gte_34' ) ) && $this->is_wc_gte_34() ) {
				add_filter( 'woocommerce_get_settings_advanced', array( $this, 'add_endpoint_account_settings' ) );
			} else {
				add_filter( 'woocommerce_account_settings', array( $this, 'add_endpoint_account_settings' ) );
			}
		}

		/**
		 * Add UI option for changing Affiliate endpoints in WC settings
		 *
		 * @param mixed $settings Existing settings.
		 * @return mixed $settings
		 */
		public function add_endpoint_account_settings( $settings ) {
			$affiliate_endpoint_setting = array(
				'title'    => __( 'Affiliate', 'affiliate-for-woocommerce' ),
				'desc'     => __( 'Endpoint for the My Account &rarr; Affiliate page', 'affiliate-for-woocommerce' ),
				'id'       => 'woocommerce_myaccount_afwc_dashboard_endpoint',
				'type'     => 'text',
				'default'  => 'afwc-dashboard',
				'desc_tip' => true,
			);

			$after_key = 'woocommerce_myaccount_view_order_endpoint';

			/**
			 * Filter to modify the key after which affiliate endpoint setting to be added.
			 *
			 * @param string $after_key The key after which affiliate endpoint setting to be added.
			 * @param array             Additional information.
			 *
			 * @since 1.17.2
			 */
			$after_key = apply_filters(
				'afwc_endpoint_account_settings_after_key',
				$after_key,
				array(
					'settings' => $settings,
					'source'   => $this,
				)
			);

			Affiliate_For_WooCommerce::insert_setting_after( $settings, $after_key, $affiliate_endpoint_setting );

			return $settings;
		}

		/**
		 * Function to add endpoint in My Account if user is an affiliate
		 */
		public function afw_myaccount() {
			if ( ! is_user_logged_in() ) {
				return;
			}

			$user = wp_get_current_user();
			if ( ! is_object( $user ) || empty( $user->ID ) ) {
				return;
			}

			$is_affiliate = afwc_is_user_affiliate( $user );
			if ( in_array( $is_affiliate, array( 'yes', 'not_registered', 'pending', 'no' ), true ) ) {
				// Register endpoints to WordPress query vars.
				add_filter( 'query_vars', array( $this, 'register_wp_query_vars' ) );
				// Register endpoint in WooCommerce query vars.
				add_filter( 'woocommerce_get_query_vars', array( $this, 'add_query_vars' ) );
				add_filter( 'woocommerce_account_menu_items', array( $this, 'wc_my_account_menu_item' ) );
				add_action( 'woocommerce_account_' . $this->endpoint . '_endpoint', array( $this, 'endpoint_content' ) );
				// Change the My Account page title.
				add_filter( 'the_title', array( $this, 'afw_endpoint_title' ) );
				add_filter( 'woocommerce_endpoint_' . $this->endpoint . '_title', array( $this, 'get_endpoint_title' ) );
			}

			if ( 'yes' === $is_affiliate ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
				add_action( 'wp_footer', array( $this, 'footer_styles_scripts' ) );
			}
		}

		/**
		 * Add new query var to WooCommerce.
		 *
		 * @param array $vars The query vars.
		 * @return array
		 */
		public function add_query_vars( $vars = array() ) {
			$vars[ $this->endpoint ] = $this->endpoint;
			return $vars;
		}

		/**
		 * Register the required endpoints in WordPress query vars.
		 *
		 * @param array $vars The query vars.
		 * .
		 * @return array The modified query vars.
		 */
		public function register_wp_query_vars( $vars = array() ) {
			$vars[] = $this->afwc_tab_endpoint;
			$vars[] = $this->afwc_section_endpoint;
			return $vars;
		}

		/**
		 * Set endpoint title.
		 *
		 * @param string $title The endpoint page title.
		 *
		 * @return string
		 */
		public function afw_endpoint_title( $title = '' ) {
			global $wp_query;

			if ( ! empty( $wp_query->query_vars ) && ! empty( $wp_query->query_vars[ $this->afwc_tab_endpoint ] ) && ! is_admin() && is_main_query() && in_the_loop() && is_account_page() ) {
				$title = $this->get_endpoint_title( $title );
				remove_filter( 'the_title', array( $this, 'afw_endpoint_title' ) );
			}

			return $title;
		}

		/**
		 * Get the endpoint title.
		 *
		 * @param string $title    The endpoint title.
		 * @param string $endpoint The endpoint name.
		 *
		 * @return string.
		 */
		public function get_endpoint_title( $title = '', $endpoint = '' ) {
			global $wp_query;

			$endpoint = ! empty( $endpoint ) ? $endpoint : ( ! empty( $wp_query->query_vars ) && ! empty( $wp_query->query_vars[ $this->afwc_tab_endpoint ] ) ? $wp_query->query_vars[ $this->afwc_tab_endpoint ] : '' );

			$affiliate_status = afwc_is_user_affiliate( wp_get_current_user() );

			if ( 'not_registered' === $affiliate_status ) {
				$title = _x( 'Register as an affiliate', 'Affiliate my account page title when a user has not submitted a request to become an affiliate', 'affiliate-for-woocommerce' );
			} elseif ( in_array( $affiliate_status, array( 'pending', 'no' ), true ) ) {
				$title = _x( 'Affiliate request status', 'Affiliate my account page title when a user\'s request to become an affiliate is either pending or rejected', 'affiliate-for-woocommerce' );
			} elseif ( 'yes' === $affiliate_status ) {
				$title = _x( 'Affiliate Dashboard', 'Affiliate my account page title when user is an affiliate', 'affiliate-for-woocommerce' );
			}

			switch ( $endpoint ) {
				case 'resources':
					return _x( 'Affiliate Resources', 'Affiliate my account page title for resources', 'affiliate-for-woocommerce' );
				case 'campaigns':
					return _x( 'Affiliate Campaigns', 'Affiliate my account page title for campaigns', 'affiliate-for-woocommerce' );
				case 'multi-tier':
					return _x( 'Affiliate Network', 'Affiliate my account page title for multi-tier', 'affiliate-for-woocommerce' );
				default:
					return $title;
			}
		}

		/**
		 * Function to add menu items in My Account.
		 *
		 * @param array $menu_items menu items.
		 * @return array $menu_items menu items.
		 */
		public function wc_my_account_menu_item( $menu_items = array() ) {
			// Return if the affiliate endpoint does not exist.
			if ( empty( $this->endpoint ) ) {
				return $menu_items;
			}

			$user = wp_get_current_user();
			if ( is_object( $user ) && $user instanceof WP_User && ! empty( $user->ID ) ) {
				$is_affiliate              = afwc_is_user_affiliate( $user );
				$insert_at_index           = array_search( 'edit-account', array_keys( $menu_items ), true );
				$afwc_is_registration_open = get_option( 'afwc_show_registration_form_in_account', 'yes' );

				// WooCommerce uses the same on the admin side to get list of WooCommerce Endpoints under Appearance > Menus.
				// So return main endpoint name irrespective of admin's affiliate status.
				if ( is_admin() ) {
					$menu_item = array( $this->endpoint => __( 'Affiliate', 'affiliate-for-woocommerce' ) );
				} else {
					if ( 'yes' === $is_affiliate ) {
						$menu_item = array( $this->endpoint => _x( 'Affiliate', 'Affiliate my account page menu title when user is an affiliate', 'affiliate-for-woocommerce' ) );
					}
					if ( in_array( $is_affiliate, array( 'pending', 'no' ), true ) ) {
						$menu_item = array( $this->endpoint => _x( 'Affiliate request status', 'Affiliate my account page menu title when a user\'s request to become an affiliate is either pending or rejected', 'affiliate-for-woocommerce' ) );
					}
					if ( 'not_registered' === $is_affiliate && 'yes' === $afwc_is_registration_open ) {
						$menu_item = array( $this->endpoint => _x( 'Register as an affiliate', 'Affiliate my account page menu title when a user has not submitted a request to become an affiliate', 'affiliate-for-woocommerce' ) );
					}
				}

				if ( ! empty( $menu_item ) ) {
					$new_menu_items = array_merge(
						array_slice( $menu_items, 0, $insert_at_index ),
						$menu_item,
						array_slice( $menu_items, $insert_at_index, null )
					);
					return $new_menu_items;
				}
			}
			return $menu_items;
		}

		/**
		 * Function to check if current page has affiliates' endpoint.
		 */
		public function is_afwc_endpoint() {
			global $wp;

			if ( ! empty( $wp->query_vars ) && array_key_exists( $this->endpoint, $wp->query_vars ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Function to display endpoint content
		 */
		public function endpoint_content() {

			$user = wp_get_current_user();
			if ( ! is_object( $user ) || empty( $user->ID ) ) {
				return;
			}

			$is_affiliate = afwc_is_user_affiliate( $user );

			if ( 'not_registered' === $is_affiliate ) {

				/**
				 * Action hook before registration form in My Account page.
				 *
				 * @param array Arguments.
				 *
				 * @since 5.3.0
				*/
				do_action(
					'afwc_before_registration_form',
					array(
						'user_id' => $user->ID,
						'source'  => $this,
					)
				);
				echo do_shortcode( '[afwc_registration_form]' );

				/**
				 * Action hook after registration form in My Account page.
				 *
				 * @param array Arguments.
				 *
				 * @since 5.3.0
				 */
				do_action(
					'afwc_after_registration_form',
					array(
						'user_id' => $user->ID,
						'source'  => $this,
					)
				);
			} else {
				$this->afwc_dashboard_content( $user );
			}
		}

		/**
		 * Function to display the affiliate dashboard.
		 *
		 * @param WP_User $user The user object.
		 *
		 * @return void
		 */
		public function afwc_dashboard_content( $user = null ) {
			if ( empty( $user ) || ! $user instanceof WP_User ) {
				$user = wp_get_current_user();
			}

			$is_affiliate = $user instanceof WP_User ? afwc_is_user_affiliate( $user ) : '';

			if ( ! empty( $is_affiliate ) && 'yes' === $is_affiliate ) {
				$this->affiliate_card( $user );
				$this->tabs( $user );
				$this->tab_content( $user );
			}

			if ( in_array( $is_affiliate, array( 'pending', 'no' ), true ) ) {
				$afwc_registration = AFWC_Registration_Submissions::get_instance();
				if ( is_callable( array( $afwc_registration, 'get_message' ) ) ) {
					// Show message for pending and rejected affiliates.
					echo '<p>' . wp_kses_post( $afwc_registration->get_message( $is_affiliate ) ) . '</p>';
				}
			}
		}

		/**
		 * Method to check whether the current page having affiliate dashboard.
		 *
		 * @return bool.
		 */
		public function is_afwc_dashboard() {
			return $this->is_afwc_endpoint() || ( is_callable( 'wc_post_content_has_shortcode' ) && wc_post_content_has_shortcode( 'afwc_dashboard' ) );
		}

		/**
		 * Function to add styles.
		 */
		public function enqueue_styles_scripts() {
			if ( ! $this->is_afwc_dashboard() ) {
				return;
			}

			$plugin_data = Affiliate_For_WooCommerce::get_plugin_data();

			if ( ! wp_script_is( 'wp-i18n' ) ) {
				wp_enqueue_script( 'wp-i18n' );
			}

			wp_enqueue_style( 'afwc-my-account', AFWC_PLUGIN_URL . '/assets/css/frontend/my-account/afwc-my-account.css', array(), $plugin_data['Version'] );
		}

		/**
		 * Function to add scripts in footer.
		 */
		public function footer_styles_scripts() {
			if ( ! $this->is_afwc_dashboard() ) {
				return;
			}

			global $wp;

			if ( ! wp_script_is( 'jquery' ) ) {
				wp_enqueue_script( 'jquery' );
			}
			if ( ! class_exists( 'WC_AJAX' ) ) {
				include_once WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-ajax.php';
			}

			$user = wp_get_current_user();
			if ( ! is_object( $user ) || empty( $user->ID ) ) {
				return;
			}

			$affiliate_id = afwc_get_affiliate_id_based_on_user_id( $user->ID );

			if ( ( ! empty( $wp->query_vars ) && ! empty( $wp->query_vars[ $this->afwc_tab_endpoint ] ) ) && ( 'campaigns' === $wp->query_vars[ $this->afwc_tab_endpoint ] || 'multi-tier' === $wp->query_vars[ $this->afwc_tab_endpoint ] ) ) {
				$plugin_data = Affiliate_For_WooCommerce::get_plugin_data();
				// Dashboard scripts.
				wp_register_script( 'mithril', AFWC_PLUGIN_URL . '/assets/js/mithril/mithril.min.js', array(), $plugin_data['Version'], true );
				wp_register_script( 'afwc-frontend-styles', AFWC_PLUGIN_URL . '/assets/js/styles.js', array( 'mithril' ), $plugin_data['Version'], true );
				wp_register_script( 'afwc-frontend-dashboard', AFWC_PLUGIN_URL . '/assets/js/frontend/frontend.js', array( 'afwc-frontend-styles', 'wp-i18n', 'afwc-click-to-copy' ), $plugin_data['Version'], true );
				if ( function_exists( 'wp_set_script_translations' ) ) {
					wp_set_script_translations( 'afwc-frontend-dashboard', 'affiliate-for-woocommerce', AFWC_PLUGIN_DIR_PATH . 'languages' );
				}
				if ( ! wp_script_is( 'afwc-frontend-dashboard' ) ) {
					wp_enqueue_script( 'afwc-frontend-dashboard' );
				}

				$affiliate_id  = afwc_get_affiliate_id_based_on_user_id( $user->ID );
				$affiliate_obj = new AFWC_Affiliate( $affiliate_id );

				wp_localize_script(
					'afwc-frontend-dashboard',
					'afwcDashboardParams',
					array(
						'security'                => array(
							'campaign'  => array(
								'fetchData' => wp_create_nonce( 'afwc-fetch-campaign' ),
							),
							'dashboard' => array(
								'multiTierData' => wp_create_nonce( 'afwc-multi-tier-data' ),
							),
						),
						'currencySymbol'          => AFWC_CURRENCY,
						'pname'                   => afwc_get_pname(),
						'affiliate_id'            => $affiliate_id,
						'affiliateIdentifier'     => ( $affiliate_obj instanceof AFWC_Affiliate && is_callable( array( $affiliate_obj, 'get_identifier' ) ) ) ? $affiliate_obj->get_identifier() : '',
						'ajaxurl'                 => admin_url( 'admin-ajax.php' ),
						'campaign_status'         => 'Active',
						'isPrettyReferralEnabled' => get_option( 'afwc_use_pretty_referral_links', 'no' ),
						'wcSpinLoader'            => WC()->plugin_url() . '/assets/images/wpspin-2x.gif',
					)
				);

				wp_register_style( 'afwc_frontend', AFWC_PLUGIN_URL . '/assets/css/frontend/frontend.css', array(), $plugin_data['Version'] );
				if ( ! wp_style_is( 'afwc_frontend' ) ) {
					wp_enqueue_style( 'afwc_frontend' );
				}

				wp_register_style( 'afwc-common-tailwind', AFWC_PLUGIN_URL . '/assets/css/common.css', array(), $plugin_data['Version'] );
				if ( ! wp_style_is( 'afwc-common-tailwind' ) ) {
					wp_enqueue_style( 'afwc-common-tailwind' );
				}
			}
		}

		/**
		 * Method to display affiliate title card
		 *
		 * @param WP_User $user The user object.
		 *
		 * @return void
		 */
		public function affiliate_card( $user = null ) {
			if ( ! $user instanceof WP_User || empty( $user->ID ) ) {
				return;
			}

			global $affiliate_for_woocommerce;

			$affiliate_id  = intval( $user->ID );
			$affiliate_obj = new AFWC_Affiliate( $affiliate_id );

			$affiliate_identifier = is_callable( array( $affiliate_obj, 'get_identifier' ) ) ? $affiliate_obj->get_identifier() : '';

			/**
			 * Filter to modify the affiliate redirection URL.
			 *
			 * @param string  $affiliate_redirection The affiliate redirection URL.
			 * @param int     $affiliate_id          The affiliate ID.
			 * @param array                          Array Additional information.
			 *
			 * @since 5.0.0
			 */
			$affiliate_redirection = apply_filters( 'afwc_referral_redirection_url', trailingslashit( home_url() ), $affiliate_id, array( 'source' => $affiliate_for_woocommerce ) );

			$template = 'my-account/affiliate-title-card.php';

			wc_get_template(
				$template,
				array(
					'affiliate_id'                   => $affiliate_id,
					'affiliate_display_name'         => ! empty( $user->display_name ) ? $user->display_name : '',
					'affiliate_signup_date'          => is_callable( array( $affiliate_obj, 'get_signup_date' ) ) ? $affiliate_obj->get_signup_date() : '',
					'affiliate_avatar_url'           => get_avatar_url( $affiliate_id ),
					'affiliate_redirection'          => $affiliate_redirection,
					'affiliate_url_with_redirection' => afwc_get_affiliate_url( $affiliate_redirection, '', $affiliate_identifier ),
				),
				is_callable( array( $affiliate_for_woocommerce, 'get_template_base_dir' ) ) ? $affiliate_for_woocommerce->get_template_base_dir( $template ) : '',
				AFWC_PLUGIN_DIRPATH . '/templates/'
			);
		}

		/**
		 * Function to display tabs headers
		 *
		 * @param WP_User $user The user object.
		 */
		public function tabs( $user = null ) {
			if ( ! $user instanceof WP_User || empty( $user->ID ) ) {
				return;
			}

			global $wp;
			$tabs = array();
			$tabs = array(
				'reports'   => esc_html_x( 'Reports', 'Affiliate my account tab title for report', 'affiliate-for-woocommerce' ),
				'resources' => esc_html_x( 'Profile', 'Affiliate my account tab title for profile', 'affiliate-for-woocommerce' ),
			);

			$afwc_multi_tier = AFWC_Multi_Tier::get_instance();
			// Add network tab only if multi tier is enabled and found any child for the current affiliate.
			if ( ! empty( $afwc_multi_tier->is_enabled ) && is_callable( array( $afwc_multi_tier, 'get_children' ) ) && ! empty( $afwc_multi_tier->get_children( intval( $user->ID ) ) ) ) {
				$tabs['multi-tier'] = esc_html_x( 'Network', 'Affiliate my account tab title for multi-tier', 'affiliate-for-woocommerce' );
			}

			// Add campaigns tab only if we find any active campaigns on the store for the current affiliate.
			if ( afwc_is_campaign_active( true ) ) {
				$tabs['campaigns'] = esc_html_x( 'Campaigns', 'Affiliate my account tab title for campaigns', 'affiliate-for-woocommerce' );
			}

			/**
			 * Filter to modify the affiliate my account tabs.
			 *
			 * @param array $tabs The affiliate my account tabs.
			 *
			 * @since 1.17.0
			 */
			$tabs       = apply_filters( 'afwc_myaccount_tabs', $tabs );
			$active_tab = ! empty( $wp->query_vars ) && ! empty( $wp->query_vars[ $this->afwc_tab_endpoint ] ) ? $wp->query_vars[ $this->afwc_tab_endpoint ] : 'reports';
			?>

			<nav class="nav-tab-wrapper">
				<?php
				if ( ! empty( $tabs ) ) {
					foreach ( $tabs as $id => $name ) {
						?>
						<a href="<?php echo esc_url( $this->get_tab_link( $id ) ); ?>" class="nav-tab <?php echo ( $id === $active_tab ) ? esc_attr( 'nav-tab-active' ) : ''; ?>"><?php echo esc_attr( $name ); ?></a>
						<?php
					}
				}
				?>
			</nav>
			<?php
		}

		/**
		 * Method to get the tab link.
		 *
		 * @param string $tab The tab name.
		 * @param string $current_url The current URL.
		 * @param array  $query_vars The query variables.
		 *
		 * @return string Return the tab link.
		 */
		public function get_tab_link( $tab = '', $current_url = '', $query_vars = array() ) {
			if ( empty( $tab ) ) {
				return '';
			}

			$current_url = remove_query_arg(
				array( $this->afwc_tab_endpoint, $this->afwc_section_endpoint, 'from-date', 'to-date' ),
				! empty( $current_url ) ? $current_url : afwc_get_current_url()
			);
			return add_query_arg( array_filter( array_merge( array( $this->afwc_tab_endpoint => $tab ), $query_vars ) ), $current_url );
		}

		/**
		 * Function to display tabs content on my account.
		 *
		 * @param WP_User $user The user object.
		 *
		 * @return void.
		 */
		public function tab_content( $user = null ) {
			if ( ! $user instanceof WP_User || empty( $user->ID ) ) {
				return;
			}
			global $wp;

			if ( ! empty( $wp->query_vars ) && ! empty( $wp->query_vars[ $this->afwc_tab_endpoint ] ) && 'resources' === $wp->query_vars[ $this->afwc_tab_endpoint ] ) {
				$this->profile_resources_content( $user );
			} elseif ( ! empty( $wp->query_vars ) && ! empty( $wp->query_vars[ $this->afwc_tab_endpoint ] ) && 'campaigns' === $wp->query_vars[ $this->afwc_tab_endpoint ] && afwc_is_campaign_active() ) {
				$this->campaigns_content( $user );
			} elseif ( ! empty( $wp->query_vars ) && ! empty( $wp->query_vars[ $this->afwc_tab_endpoint ] ) && 'multi-tier' === $wp->query_vars[ $this->afwc_tab_endpoint ] ) {
				$afwc_multi_tier = AFWC_Multi_Tier::get_instance();
				// Check if multi tier is enabled and affiliate has some children.
				if ( ! empty( $afwc_multi_tier->is_enabled ) && is_callable( array( $afwc_multi_tier, 'get_children' ) ) && ! empty( $afwc_multi_tier->get_children( intval( $user->ID ) ) ) ) {
					$this->multi_tier_content( $user );
				}
			} else {
				$this->reports_dashboard_content( $user );
			}
		}

		/**
		 * Function to display dashboard content on my account.
		 * Default: Reports tab.
		 *
		 * @param WP_User $user The user object.
		 */
		public function reports_dashboard_content( $user = null ) {
			if ( defined( 'WC_DOING_AJAX' ) && true === WC_DOING_AJAX ) {
				check_ajax_referer( 'afwc-reload-dashboard', 'security' );
			}

			if ( ! is_object( $user ) || empty( $user->ID ) ) {
				return;
			}

			$is_affiliate = $user instanceof WP_User ? afwc_is_user_affiliate( $user ) : '';
			if ( empty( $is_affiliate ) || 'yes' !== $is_affiliate ) {
				return;
			}

			global $affiliate_for_woocommerce, $wp;

			$from        = ( ! empty( $_REQUEST['from-date'] ) ) ? wc_clean( wp_unslash( $_REQUEST['from-date'] ) ) : ''; // phpcs:ignore
			$to          = ( ! empty( $_REQUEST['to-date'] ) ) ? wc_clean( wp_unslash( $_REQUEST['to-date'] ) ) : ''; // phpcs:ignore
			$section     = ( ! empty( $_POST['section'] ) ) ? wc_clean( wp_unslash( $_POST['section'] ) ) : ''; // phpcs:ignore
			$current_url = ( ! empty( $_POST['current_url'] ) ) ? wc_clean( wp_unslash( $_POST['current_url'] ) ) : afwc_get_current_url(); // phpcs:ignore

			$plugin_data = is_callable( array( $affiliate_for_woocommerce, 'get_plugin_data' ) ) ? $affiliate_for_woocommerce->get_plugin_data() : array();

			if ( is_callable( array( 'AFWC_Payout_Invoice', 'is_enabled_for_affiliate' ) ) && AFWC_Payout_Invoice::is_enabled_for_affiliate() ) {
				// Register the scripts for payout invoice.
				if ( ! wp_style_is( 'afwc-report-payout-invoice' ) ) {
					wp_enqueue_style( 'afwc-report-payout-invoice', AFWC_PLUGIN_URL . '/assets/css/frontend/my-account/afwc-report-payout-invoice.css', array(), $plugin_data['Version'], 'all' );
				}

				if ( ! wp_script_is( 'afwc-print-invoice', 'registered' ) ) {
					global $wp_version;
					wp_register_script(
						'afwc-print-invoice',
						AFWC_PLUGIN_URL . '/assets/js/frontend/my-account/afwc-print-invoice.js',
						array(),
						$plugin_data['Version'],
						version_compare( $wp_version, '6.3', '>=' ) ? array( 'strategy' => 'defer' ) : true
					);
				}
				wp_enqueue_script( 'afwc-print-invoice' );
			}

			wp_register_script( 'afwc-country-flag', AFWC_PLUGIN_URL . '/assets/js/lib/afwc-country-flag.js', array(), $plugin_data['Version'], true );

			if ( ! wp_script_is( 'afwc-reports' ) ) {
				wp_register_script( 'afwc-reports', AFWC_PLUGIN_URL . '/assets/js/frontend/my-account/affiliate-reports.js', array_filter( array( 'jquery', 'wp-i18n', 'wp-url', 'afwc-date-functions', 'afwc-click-to-copy', 'afwc-country-flag', wp_script_is( 'afwc-print-invoice', 'registered' ) ? 'afwc-print-invoice' : '' ), 'strlen' ), $plugin_data['Version'], true );
				if ( function_exists( 'wp_set_script_translations' ) ) {
					wp_set_script_translations( 'afwc-reports', 'affiliate-for-woocommerce', AFWC_PLUGIN_DIR_PATH . 'languages' );
				}
			}

			wp_localize_script(
				'afwc-reports',
				'afwcDashboardParams',
				array(
					'visits'          => array(
						'ajaxURL' => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_load_more_visits' ) ),
						'nonce'   => esc_js( wp_create_nonce( 'afwc-load-more-visits' ) ),
					),
					'products'        => array(
						'ajaxURL' => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_load_more_products' ) ),
						'nonce'   => esc_js( wp_create_nonce( 'afwc-load-more-products' ) ),
					),
					'referrals'       => array(
						'ajaxURL' => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_load_more_referrals' ) ),
						'nonce'   => esc_js( wp_create_nonce( 'afwc-load-more-referrals' ) ),
					),
					'payouts'         => array(
						'ajaxURL' => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_load_more_payouts' ) ),
						'nonce'   => esc_js( wp_create_nonce( 'afwc-load-more-payouts' ) ),
					),
					'loadAllData'     => array(
						'ajaxURL' => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_reload_dashboard' ) ),
						'nonce'   => esc_js( wp_create_nonce( 'afwc-reload-dashboard' ) ),
					),
					'invoiceTemplate' => array(
						'ajaxURL' => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_payout_invoice' ) ),
						'nonce'   => esc_js( wp_create_nonce( 'afwc-payout-invoice' ) ),
					),
				)
			);

			wp_enqueue_script( 'afwc-reports' );

			$template_key = ! empty( $section ) ? $section : ( ! empty( $wp->query_vars[ $this->afwc_section_endpoint ] ) ? $wp->query_vars[ $this->afwc_section_endpoint ] : 'reports' );

			/**
			 * Action hook to load affiliate dashboard sections.
			 *
			 * @param array Arguments.
			 *
			 * @since 1.0.0
			 */
			do_action(
				"afwc_{$template_key}_dashboard",
				array(
					'affiliate_id' => afwc_get_affiliate_id_based_on_user_id( intval( $user->ID ) ),
					'current_url'  => $current_url,
					'date_range'   => array(
						'from' => $from,
						'to'   => $to,
					),
				)
			);
		}

		/**
		 * Function to retrieve more products.
		 */
		public function ajax_reload_dashboard() {
			check_ajax_referer( 'afwc-reload-dashboard', 'security' );

			$this->reports_dashboard_content( wp_get_current_user() );

			die();
		}

		/**
		 * Unified AJAX handler for Visits|Referrals|Products|Payouts table's load more requests
		 */
		public function ajax_load_more_handler() {
			$type = str_replace( 'wc_ajax_afwc_load_more_', '', current_action() );
			if ( ! in_array( $type, $this->report_tables, true ) ) {
				wp_die();
			}

			check_ajax_referer( "afwc-load-more-$type", 'security' );

			$config = $this->get_load_more_config( $type );

			$args = array(
				'from'         => ! empty( $_POST['from'] ) ? $this->gmt_from_date( wc_clean( wp_unslash( $_POST['from'] ) ) ) : '', // phpcs:ignore
				'to'           => ! empty( $_POST['to'] ) ? $this->gmt_from_date( wc_clean( wp_unslash( $_POST['to'] ) ) ) : '', // phpcs:ignore
				'affiliate_id' => afwc_get_affiliate_id_based_on_user_id( get_current_user_id() ),
				'table_footer' => true,
			);

			// Add offset with the correct key name.
			$args[ $config['offset_key'] ] = ! empty( $_POST['offset'] ) ? wc_clean( wp_unslash( $_POST['offset'] ) ) : 0; // phpcs:ignore

			if ( ! empty( $_POST['search'] ) ) { // phpcs:ignore
				$args['search'] = wc_clean( wp_unslash( $_POST['search'] ) ); // phpcs:ignore
			}

			if ( 'referrals' === $type ) {
				$args['current_url'] = ! empty( $_POST['current_url'] ) ? wc_clean( wp_unslash( $_POST['current_url'] ) ) : afwc_get_current_url(); // phpcs:ignore
			}

			/**
			 * Filter to modify the ajax load more arguments for each table type.
			 *
			 * @param array  $args The ajax load more arguments.
			 * @param string $type The type (visits, products, referrals, payouts).
			 *
			 * @since 1.17.2
			 */
			$args = apply_filters( 'afwc_ajax_load_more_' . $type, $args );

			$headers = is_callable( array( $this, $config['headers_method'] ) ) ? call_user_func( array( $this, $config['headers_method'] ) ) : array();
			if ( empty( $headers ) || ! is_array( $headers ) ) {
				wp_die();
			}

			$data       = is_callable( array( $this, $config['data_method'] ) ) ? call_user_func( array( $this, $config['data_method'] ), $args ) : array();
			$output_key = $config['output_key'];
			if ( empty( $data ) || ! is_array( $data ) || empty( $data[ $output_key ] ) || ! is_array( $data[ $output_key ] ) ) {
				wp_die();
			}

			ob_start();

			/**
			 * Action hook before loading more data via ajax.
			 *
			 * @param array  $data The table data.
			 * @param array  $args The arguments.
			 * @param object $this The source object.
			 *
			 * @since 8.12.0
			 */
			do_action( 'afwc_before_ajax_load_more_' . $type, $data, $args, $this );

			/**
			 * Action hook to modify the data after loading more data via ajax.
			 *
			 * @param array  The prepared data for action hook.
			 *
			 * @since 1.17.2
			 */
			do_action( 'afwc_' . $type . '_data', $this->prepare_action_data( $type, $data, $headers, $args ) );

			/**
			 * Load the template for each table type
			 *
			 * @param array  $data The table data.
			 * @param array  $args The arguments.
			 * @param object $this The source object.
			 *
			 * @since 8.12.0
			 */
			do_action( 'afwc_after_ajax_load_more_' . $type, $data, $args, $this );

			wp_send_json(
				array(
					'html'      => ob_get_clean(),
					'load_more' => ! empty( $data['has_load_more'] ) ? $data['has_load_more'] : false,
				)
			);
		}

		/**
		 * Get configuration for each table type
		 *
		 * @param string $type The type (visits, products, referrals, payouts).
		 * @return array Configuration array
		 */
		private function get_load_more_config( $type = '' ) {
			if ( empty( $type ) ) {
				return array();
			}
			$configs = array(
				'visits'    => array(
					'headers_method' => 'get_visits_report_headers',
					'data_method'    => 'get_visits_data',
					'offset_key'     => 'start',
					'output_key'     => 'rows',
				),
				'referrals' => array(
					'headers_method' => 'get_referrals_report_headers',
					'data_method'    => 'get_referrals_data',
					'offset_key'     => 'offset',
					'output_key'     => 'rows',
				),
				'products'  => array(
					'headers_method' => 'get_products_report_headers',
					'data_method'    => 'get_products_data',
					'offset_key'     => 'start_limit',
					'output_key'     => 'rows',
				),
				'payouts'   => array(
					'headers_method' => 'get_payouts_report_headers',
					'data_method'    => 'get_payouts_data',
					'offset_key'     => 'start_limit',
					'output_key'     => 'payouts',
				),
			);

			return $configs[ $type ];
		}

		/**
		 * Prepare data for the action hook
		 *
		 * @param string $type The type (visits, products, referrals, payouts).
		 * @param array  $data The table data.
		 * @param array  $headers The table headers.
		 * @param array  $args The arguments.
		 * @return array Prepared data for do_action
		 */
		private function prepare_action_data( $type = '', $data = array(), $headers = array(), $args = array() ) {
			$prepared = array();

			switch ( $type ) {
				case 'visits':
					$prepared = array(
						'visits_data'    => $data,
						'visits_headers' => $headers,
					);
					break;

				case 'referrals':
					$campaign_link = $this->get_tab_link( 'campaigns', ! empty( $args['current_url'] ) ? $args['current_url'] : '' ) . '#!/';
					$prepared      = array(
						'referrals'        => $data,
						'referral_headers' => $headers,
						'campaign_link'    => $campaign_link,
					);
					break;

				case 'products':
					$prepared = array(
						'products'        => $data,
						'product_headers' => $headers,
					);
					break;

				case 'payouts':
					$prepared = array(
						'payouts'        => $data,
						'payout_headers' => $headers,
					);
					break;
			}

			return $prepared;
		}

		/**
		 * Get gmt date time.
		 *
		 * @param string $date The date.
		 * @param string $format The date format.
		 *
		 * @return string Return the date with gmt formatted if date is provided otherwise empty string.
		 */
		public function gmt_from_date( $date = '', $format = 'Y-m-d H:m:s' ) {
			if ( empty( $date ) ) {
				return '';
			}

			return get_gmt_from_date( $date, $format );
		}

		/**
		 * Method to get the visits report headers.
		 *
		 * @return array Return the array header data.
		 */
		public function get_visits_report_headers() {
			$headers = array(
				'datetime'      => _x( 'Datetime', 'Visits table header title for date column', 'affiliate-for-woocommerce' ),
				'medium'        => _x( 'Medium', 'Visits table header title for medium column', 'affiliate-for-woocommerce' ),
				'referring_url' => _x( 'Landing URL', 'Visits table header title for landing url column', 'affiliate-for-woocommerce' ),
				'is_converted'  => _x( 'Converted', 'Visits table header title for conversion column', 'affiliate-for-woocommerce' ),
			);

			/**
			 * Filter to show/hide user agent column in visits report.
			 *
			 * @param bool   Whether to show user agent column or not.
			 * @param array  Additional information.
			 *
			 * @since 1.17.2
			 */
			if ( apply_filters( 'afwc_account_show_user_agent_column', true, array( 'source' => $this ) ) ) {
				$headers['user_agent_info'] = _x( 'User Agent', 'Visits table header title for user agent info column', 'affiliate-for-woocommerce' );
			}

			/**
			 * Filter to modify the visits report headers.
			 *
			 * @param array $headers The visits report headers.
			 * @param array Additional information.
			 *
			 * @since 5.0.0
			 */
			return apply_filters( 'afwc_my_account_get_visits_report_header', $headers, array( 'source' => $this ) );
		}

		/**
		 * Method to get the referral report headers.
		 *
		 * @return array Return the array header data.
		 */
		public function get_referrals_report_headers() {
			$headers = array(
				'date'       => _x( 'Date', 'Referrals table header title for date column', 'affiliate-for-woocommerce' ),
				'order_id'   => _x( 'Order', 'Referrals table header title for order column', 'affiliate-for-woocommerce' ), // We kept order_id key unchanged for backward compatibility to display older ID column.
				'commission' => _x( 'Commission', 'Referrals table header title for commission column', 'affiliate-for-woocommerce' ),
				'status'     => _x( 'Payout status', 'Referrals table header title for payout status column', 'affiliate-for-woocommerce' ),
			);

			/**
			 * Filter to show/hide customer column in referrals report.
			 *
			 * @param bool   Whether to show customer column or not.
			 * @param array  Additional information.
			 *
			 * @since 1.17.2
			 */
			if ( apply_filters( 'afwc_account_show_customer_column', false, array( 'source' => $this ) ) ) {
				$headers['customer_name'] = _x( 'Customer', 'Referrals table header title for customer column', 'affiliate-for-woocommerce' );
			}

			// Source goes after customer details.
			$headers['source'] = _x( 'Source', 'Referrals table header title for source column', 'affiliate-for-woocommerce' );

			/**
			 * Filter to modify the referral report headers.
			 *
			 * @param array $headers The referral report headers.
			 * @param array Additional information.
			 *
			 * @since 5.0.0
			 */
			return apply_filters( 'afwc_my_account_get_referral_report_header', $headers, array( 'source' => $this ) );
		}

		/**
		 * Method to get the product report headers.
		 *
		 * @return array Return the array header data.
		 */
		public function get_products_report_headers() {

			$headers = array(
				'product' => _x( 'Product', 'Products table header title for product name column', 'affiliate-for-woocommerce' ),
				'qty'     => _x( 'Quantity', 'Products table header title for quantity column', 'affiliate-for-woocommerce' ),
			);

			if ( ! afwc_can_convert_currency() ) {
				$headers['sales'] = _x( 'Sales', 'Products table header title for sales column', 'affiliate-for-woocommerce' );
			}

			/**
			 * Filter to modify the products report headers.
			 *
			 * @param array The products report headers.
			 * @param array Additional information.
			 *
			 * @since 8.5.0
			 */
			return apply_filters(
				'afwc_my_account_get_products_report_header',
				$headers,
				array( 'source' => $this )
			);
		}

		/**
		 * Method to get the payout report headers.
		 *
		 * @return array Return the array header data.
		 */
		public function get_payouts_report_headers() {

			/**
			 * Filter to modify the payouts report headers.
			 *
			 * @param array The payouts report headers.
			 * @param array Additional information.
			 *
			 * @since 8.5.0
			 */
			return apply_filters(
				'afwc_my_account_get_payouts_report_header',
				array(
					'date'          => _x( 'Date', 'Payouts table header title for date column', 'affiliate-for-woocommerce' ),
					'payout_amount' => _x( 'Amount', 'Payouts table header title for payout amount column', 'affiliate-for-woocommerce' ),
					'method'        => _x( 'Method', 'Payouts table header title for payout method column', 'affiliate-for-woocommerce' ),
					'payout_notes'  => _x( 'Notes', 'Payouts table header title for payout notes column', 'affiliate-for-woocommerce' ),
				),
				array( 'source' => $this )
			);
		}

		/**
		 * Method to retrieve visit data.
		 *
		 * @param array $args Arguments for filtering and pagination.
		 *
		 * @return array An array containing visit data.
		 */
		public function get_visits_data( $args = array() ) {
			return $this->get_table_data(
				$args,
				function ( $params ) {
					$affiliate_id = ! empty( $params['affiliate_id'] ) ? intval( $params['affiliate_id'] ) : 0;
					if ( empty( $affiliate_id ) ) {
						return array();
					}
					$visits = new AFWC_Visits( $affiliate_id, $params );

					/**
					 * Filter to show/hide user agent column in visits report.
					 *
					 * @param bool   Whether to show user agent column or not.
					 * @param array  Additional information.
					 *
					 * @since 6.31.0
					 */
					$show_user_agent_info = apply_filters( 'afwc_account_show_user_agent_column', true, array( 'source' => $this ) );
					return is_callable( array( $visits, 'get_reports' ) )
						? $visits->get_reports(
							array(
								'is_affiliate_dashboard' => true,
								'get_user_agent_info'    => $show_user_agent_info,
							)
						)
						: array();
				},
				'visits',
				'rows'
			);
		}

		/**
		 * Method to retrieve referrals data
		 *
		 * @param array $args Arguments for filtering and pagination.
		 *
		 * @return array An array containing referrals data.
		 */
		public function get_referrals_data( $args = array() ) {
			return $this->get_table_data(
				$args,
				function ( $params ) {
					return $this->get_referrals_report( $params );
				},
				'referrals',
				'rows'
			);
		}

		/**
		 * Method to retrieve product data.
		 *
		 * @param array $args Arguments for filtering and pagination.
		 *
		 * @return array An array containing product data.
		 */
		public function get_products_data( $args = array() ) {
			return $this->get_table_data(
				$args,
				function ( $params ) {
					$products_obj = new AFWC_Referred_Products( $params );
					$products     = is_callable( array( $products_obj, 'get_reports' ) ) ? $products_obj->get_reports() : array();

					foreach ( $products as $key => &$product ) {
						if ( ! afwc_can_convert_currency() ) {
							$product['sales'] = afwc_format_price( floatval( ! empty( $product['sales'] ) ? $product['sales'] : 0 ) );
						} elseif ( isset( $product['sales'] ) ) {
							unset( $product['sales'] );
						}

						$parts              = explode( '_', $key );
						$product_id         = ! empty( $parts[1] ) ? $parts[1] : $parts[0];
						$product['product'] = sprintf( '<a href="%s" target="_blank">%s</a>', esc_url( get_permalink( $product_id ) ), $product['product'] );
					}
					return $products;
				},
				'products',
				'rows'
			);
		}

		/**
		 * Method to retrieve payout data.
		 *
		 * @param array $args Arguments for filtering and pagination.
		 *
		 * @return array An array containing payout history.
		 */
		public function get_payouts_data( $args = array() ) {
			return $this->get_table_data(
				$args,
				function ( $params ) {
					$date_format = get_option( 'date_format', 'Y-m-d' );

					$payouts_history_obj = new AFWC_Payout_History( $params );
					$payouts             = is_callable( array( $payouts_history_obj, 'get_reports' ) ) ? $payouts_history_obj->get_reports() : array();

					foreach ( $payouts as &$payout ) {
						$payout['payout_amount'] = afwc_format_price(
							floatval( ! empty( $payout['amount'] ) ? $payout['amount'] : 0 ),
							! empty( $payout['currency'] ) ? $payout['currency'] : ''
						);
						$payout['method']        = ! empty( $payout['method'] ) ? afwc_get_payout_methods( $payout['method'] ) : '';
						$payout['date']          = ! empty( $payout['datetime'] ) ? gmdate( $date_format, strtotime( $payout['datetime'] ) ) : '';
					}
					return $payouts;
				},
				'payouts',
				'payouts'
			);
		}

		/**
		 * Core method to fetch and format table data for account views.
		 *
		 * @param array    $args           Arguments for data filtering and limits.
		 * @param callable $data_callback  Callback to fetch the data.
		 * @param string   $data_name      Slug for identifying the type of data.
		 * @param string   $output_key     The key to store data in final result array.
		 *
		 * @return array An array containing formatted data with optional pagination info.
		 */
		private function get_table_data( $args = array(), $data_callback = null, $data_name = '', $output_key = 'rows' ) {
			$args = is_array( $args ) ? $args : array();

			if ( ! afwc_is_valid_date_range( $args ) ) {
				return array();
			}

			$is_for_load_more = isset( $args['table_footer'] ) ? $args['table_footer'] : true;

			/**
			 * Filter to modify the limit for each data type.
			 *
			 * @param int    The limit value.
			 * @param array  Additional information.
			 *
			 * @since 5.0.0
			 */
			$limit = ! empty( $args['limit'] ) ? (int) $args['limit'] : apply_filters( "afwc_my_account_{$data_name}_per_page", get_option( "afwc_my_account_{$data_name}_per_page", AFWC_MY_ACCOUNT_DEFAULT_BATCH_LIMIT ), array( 'source' => $this ) );

			// If data is with load more logic, fetch one extra row to check if more data exists.
			$limit         = $is_for_load_more ? $limit + 1 : $limit;
			$args['limit'] = $limit;

			$data_rows = is_callable( $data_callback ) ? call_user_func( $data_callback, $args ) : array();

			$row_count       = count( $data_rows );
			$found_extra_row = $is_for_load_more && ( $row_count === $limit );

			// Remove the extra row used to determine if more results exist.
			if ( $found_extra_row ) {
				array_pop( $data_rows );
			}

			$result[ $output_key ] = $data_rows;

			if ( $is_for_load_more ) {
				$result['total_count']   = $found_extra_row ? $limit : $row_count;
				$result['has_load_more'] = $found_extra_row;
			}

			/**
			 * Filter to modify the final result for each data type.
			 *
			 * @param array  $result The final result array.
			 * @param array  $args The arguments.
			 *
			 * @since 5.0.0
			 */
			return apply_filters( "afwc_my_account_{$data_name}_result", $result, $args );
		}

		/**
		 * Ajax callback method to return the invoice with HTML template.
		 */
		public function ajax_payout_invoice() {
			check_ajax_referer( 'afwc-payout-invoice', 'security' );

			if (
				! is_callable( array( 'AFWC_Payout_Invoice', 'is_enabled_for_affiliate' ) )
				|| ! AFWC_Payout_Invoice::is_enabled_for_affiliate()
				|| ! is_callable( array( 'AFWC_Payout_Invoice', 'get_instance' ) )
			) {
				wp_die();
			}

			$payout_invoice = AFWC_Payout_Invoice::get_instance();

			if ( ! is_callable( array( $payout_invoice, 'render_payout_invoice' ) ) ) {
				wp_die();
			}

			$payout_invoice->render_payout_invoice(
				array(
					'payout_id'      => ( ! empty( $_POST['payout_id'] ) ) ? intval( wc_clean( $_POST['payout_id'] ) ) : 0, // phpcs:ignore
					'affiliate_id'   => afwc_get_affiliate_id_based_on_user_id( get_current_user_id() ),
					'date_time'      => ( ! empty( $_POST['date_time'] ) ) ? wc_clean( $_POST['date_time']  ) : '', // phpcs:ignore
					'from_period'    => ( ! empty( $_POST['from_period'] ) ) ? wc_clean( $_POST['from_period']  ) : '', // phpcs:ignore
					'to_period'      => ( ! empty( $_POST['to_period'] ) ) ? wc_clean( $_POST['to_period']  ) : '', // phpcs:ignore
					'referral_count' => ( ! empty( $_POST['referral_count'] ) ) ? intval( wc_clean( $_POST['referral_count'] ) ) : 0, // phpcs:ignore
					'amount'         => ( ! empty( $_POST['amount'] ) ) ? floatval( wc_clean( $_POST['amount'] ) ) : 0, // phpcs:ignore
					'currency'       => ( ! empty( $_POST['currency'] ) ) ? wc_clean( $_POST['currency']  ) : '', // phpcs:ignore
					'method'         => ( ! empty( $_POST['method'] ) ) ? wc_clean( $_POST['method']  ) : '', // phpcs:ignore
					'notes'          => ( ! empty( $_POST['notes'] ) ) ? wc_clean( $_POST['notes'] )  : '', // phpcs:ignore
				)
			);

			wp_die();
		}

		/**
		 * Function to get visitors data
		 *
		 * @param array $args arguments.
		 * @return array visitors data
		 */
		public function get_visitors_data( $args = array() ) {
			global $wpdb;

			$from         = ( ! empty( $args['from'] ) ) ? $args['from'] : '';
			$to           = ( ! empty( $args['to'] ) ) ? $args['to'] : '';
			$affiliate_id = ( ! empty( $args['affiliate_id'] ) ) ? $args['affiliate_id'] : 0;

			if ( ! empty( $from ) && ! empty( $to ) ) {
				$visitors_result = $wpdb->get_var( // phpcs:ignore
												$wpdb->prepare( // phpcs:ignore
													"SELECT IFNULL(COUNT( DISTINCT CONCAT_WS( ':', ip, user_id ) ), 0)
																FROM {$wpdb->prefix}afwc_hits
																WHERE affiliate_id = %d
																	AND (datetime BETWEEN %s AND %s)",
													$affiliate_id,
													$from,
													$to
												)
				);
			} else {
				$visitors_result = $wpdb->get_var( // phpcs:ignore
												$wpdb->prepare( // phpcs:ignore
													"SELECT IFNULL(COUNT( DISTINCT CONCAT_WS( ':', ip, user_id ) ), 0)
																FROM {$wpdb->prefix}afwc_hits
																WHERE affiliate_id = %d",
													$affiliate_id
												)
				);
			}

			/**
			 * Filter to modify the visitors data result.
			 *
			 * @param array  The visitors data result.
			 * @param array  $args The arguments.
			 *
			 * @since 5.0.0
			 */
			return apply_filters( 'afwc_my_account_clicks_result', array( 'visitors' => $visitors_result ), $args );
		}

		/**
		 * Function to get customers data
		 *
		 * @param array $args arguments.
		 * @return array customers data
		 */
		public function get_customers_data( $args = array() ) {
			global $wpdb;

			$from         = ( ! empty( $args['from'] ) ) ? $args['from'] : '';
			$to           = ( ! empty( $args['to'] ) ) ? $args['to'] : '';
			$affiliate_id = ( ! empty( $args['affiliate_id'] ) ) ? $args['affiliate_id'] : 0;

			if ( ! empty( $from ) && ! empty( $to ) ) {
				$customers_result = $wpdb->get_var( // phpcs:ignore
												$wpdb->prepare( // phpcs:ignore
													"SELECT IFNULL(COUNT( DISTINCT IF( user_id > 0, user_id, CONCAT_WS( ':', ip, user_id ) ) ), 0) as customers_count
																FROM {$wpdb->prefix}afwc_referrals
																WHERE affiliate_id = %d
																	AND (datetime BETWEEN %s AND %s)",
													$affiliate_id,
													$from,
													$to
												)
				);
			} else {
				$customers_result = $wpdb->get_var( // phpcs:ignore
												$wpdb->prepare( // phpcs:ignore
													"SELECT IFNULL(COUNT( DISTINCT IF( user_id > 0, user_id, CONCAT_WS( ':', ip, user_id ) ) ), 0) as customers_count
																FROM {$wpdb->prefix}afwc_referrals
																WHERE affiliate_id = %d",
													$affiliate_id
												)
				);
			}

			/**
			 * Filter to modify the customers data result.
			 *
			 * @param array  The customers data result.
			 * @param array  $args The arguments.
			 *
			 * @since 5.0.0
			 */
			return apply_filters( 'afwc_my_account_customers_result', array( 'customers' => $customers_result ), $args );
		}

		/**
		 * Method to retrieve KPIs data
		 *
		 * @param array $args Arguments for filtering.
		 * @param bool  $get_deprecated_kpis Whether to include deprecated KPIs.
		 *
		 * @return array KPI of data the affiliate.
		 */
		public function get_kpis_data( $args = array(), $get_deprecated_kpis = false ) {
			if ( ! afwc_is_valid_date_range( $args ) ) {
				return array();
			}

			global $wpdb;

			$from         = ! empty( $args['from'] ) ? $args['from'] : '';
			$to           = ! empty( $args['to'] ) ? $args['to'] : '';
			$affiliate_id = ! empty( $args['affiliate_id'] ) ? $args['affiliate_id'] : 0;
			$currency_id  = ! empty( $args['currency_id'] ) ? $args['currency_id'] : AFWC_CURRENCY_CODE;

			$temp_option_key     = 'afwc_order_status_' . uniqid();
			$paid_order_statuses = afwc_get_paid_order_status();
			update_option( $temp_option_key, implode( ',', $paid_order_statuses ), 'no' );

			if ( ! empty( $from ) && ! empty( $to ) ) {
				// Need to consider all order_statuses to get correct rejected_commission and hence not passing order_statuses.
				if ( is_callable( 'afwc_is_hpos_enabled' ) && afwc_is_hpos_enabled() ) {
					if ( $get_deprecated_kpis ) {
						$kpis_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT
									IFNULL( SUM( CASE WHEN FIND_IN_SET( CONVERT(afwcr.order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s ) ) THEN afwcr.order_total END ), 0) AS order_total,
									IFNULL(count(DISTINCT wco.id), 0) AS number_of_orders,
									IFNULL(SUM( afwcr.amount ), 0) AS gross_commissions,
									IFNULL(SUM(CASE WHEN afwcr.status = %s THEN afwcr.amount END), 0) AS paid_commission,
									IFNULL(SUM(CASE WHEN afwcr.status = %s AND FIND_IN_SET ( CONVERT( afwcr.order_status USING %s ) COLLATE %s, ( SELECT CONVERT( option_value USING %s ) COLLATE %s
																		FROM {$wpdb->prefix}options
																		WHERE option_name = %s )  ) THEN afwcr.amount END), 0) AS unpaid_commission,
									IFNULL(SUM(CASE WHEN afwcr.status = %s THEN afwcr.amount END), 0) AS rejected_commission,
									IFNULL(COUNT(CASE WHEN afwcr.status = %s THEN 1 END), 0) AS paid_count,
									IFNULL(COUNT(CASE WHEN afwcr.status = %s AND FIND_IN_SET ( CONVERT( afwcr.order_status USING %s ) COLLATE %s, ( SELECT CONVERT( option_value USING %s ) COLLATE %s
																		FROM {$wpdb->prefix}options
																		WHERE option_name = %s )  )  THEN 1 END), 0) AS unpaid_count,
									IFNULL(COUNT(CASE WHEN afwcr.status = %s THEN 1 END), 0) AS rejected_count
								FROM {$wpdb->prefix}afwc_referrals AS afwcr
									JOIN {$wpdb->prefix}wc_orders AS wco
										ON (afwcr.post_id = wco.id
											AND wco.type = %s
											AND afwcr.affiliate_id = %d)
								WHERE afwcr.status != %s
									AND (afwcr.datetime BETWEEN %s AND %s)
									AND (afwcr.currency_id = %s)",
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								$temp_option_key,
								AFWC_REFERRAL_STATUS_PAID,
								AFWC_REFERRAL_STATUS_UNPAID,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								$temp_option_key,
								AFWC_REFERRAL_STATUS_REJECTED,
								AFWC_REFERRAL_STATUS_PAID,
								AFWC_REFERRAL_STATUS_UNPAID,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								$temp_option_key,
								AFWC_REFERRAL_STATUS_REJECTED,
								'shop_order',
								$affiliate_id,
								AFWC_REFERRAL_STATUS_DRAFT,
								$from,
								$to,
								$currency_id
							),
							'ARRAY_A'
						);
					} else {
						$kpis_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT
									IFNULL( SUM( CASE WHEN FIND_IN_SET( CONVERT(afwcr.order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s ) ) THEN afwcr.order_total END ), 0) AS order_total,
									IFNULL(SUM( afwcr.amount ), 0) AS gross_commissions,
									IFNULL(SUM(CASE WHEN afwcr.status = %s THEN afwcr.amount END), 0) AS paid_commission,
									IFNULL(SUM(CASE WHEN afwcr.status = %s AND FIND_IN_SET(
										CONVERT(afwcr.order_status USING %s) COLLATE %s,
										(SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s)
									) THEN afwcr.amount END), 0) AS unpaid_commission
								FROM {$wpdb->prefix}afwc_referrals AS afwcr
									JOIN {$wpdb->prefix}wc_orders AS wco
										ON (
											afwcr.post_id = wco.id
											AND wco.type = %s
											AND afwcr.affiliate_id = %d
										)
								WHERE afwcr.status != %s
									AND (afwcr.datetime BETWEEN %s AND %s)
									AND (afwcr.currency_id = %s)",
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								$temp_option_key,
								AFWC_REFERRAL_STATUS_PAID,
								AFWC_REFERRAL_STATUS_UNPAID,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								$temp_option_key,
								'shop_order',
								$affiliate_id,
								AFWC_REFERRAL_STATUS_DRAFT,
								$from,
								$to,
								$currency_id
							),
							'ARRAY_A'
						);
					}
				} elseif ( $get_deprecated_kpis ) {
						$kpis_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT
									IFNULL( SUM( CASE WHEN FIND_IN_SET( CONVERT(afwcr.order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s ) ) THEN afwcr.order_total END ), 0) AS order_total,
									IFNULL(count(DISTINCT p.ID), 0) AS number_of_orders,
									IFNULL(SUM( afwcr.amount ), 0) as gross_commissions,
									IFNULL(SUM(CASE WHEN afwcr.status = %s THEN afwcr.amount END), 0) AS paid_commission,
									IFNULL(SUM(CASE WHEN afwcr.status = %s AND FIND_IN_SET ( CONVERT(order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s
																		FROM {$wpdb->prefix}options
																		WHERE option_name = %s )  ) THEN afwcr.amount END), 0) AS unpaid_commission,
									IFNULL(SUM(CASE WHEN afwcr.status = %s THEN afwcr.amount END), 0) AS rejected_commission,
									IFNULL(COUNT(CASE WHEN afwcr.status = %s THEN 1 END), 0) AS paid_count,
									IFNULL(COUNT(CASE WHEN afwcr.status = %s AND FIND_IN_SET ( CONVERT(order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s
																		FROM {$wpdb->prefix}options
																		WHERE option_name = %s )  )  THEN 1 END), 0) AS unpaid_count,
									IFNULL(COUNT(CASE WHEN afwcr.status = %s THEN 1 END), 0) AS rejected_count
								FROM {$wpdb->prefix}afwc_referrals AS afwcr
									JOIN {$wpdb->posts} AS p
										ON (afwcr.post_id = p.ID
												AND afwcr.affiliate_id = %d)
								WHERE afwcr.status != %s
									AND (afwcr.datetime BETWEEN %s AND %s)
									AND (afwcr.currency_id = %s)",
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								$temp_option_key,
								AFWC_REFERRAL_STATUS_PAID,
								AFWC_REFERRAL_STATUS_UNPAID,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								$temp_option_key,
								AFWC_REFERRAL_STATUS_REJECTED,
								AFWC_REFERRAL_STATUS_PAID,
								AFWC_REFERRAL_STATUS_UNPAID,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								AFWC_SQL_CHARSET,
								AFWC_SQL_COLLATION,
								$temp_option_key,
								AFWC_REFERRAL_STATUS_REJECTED,
								$affiliate_id,
								AFWC_REFERRAL_STATUS_DRAFT,
								$from,
								$to,
								$currency_id
							),
							'ARRAY_A'
						);
				} else {
					$kpis_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT
									IFNULL( SUM( CASE WHEN FIND_IN_SET( CONVERT(afwcr.order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s ) ) THEN afwcr.order_total END ), 0) AS order_total,
									IFNULL(SUM( afwcr.amount ), 0) AS gross_commissions,
									IFNULL(SUM(CASE WHEN afwcr.status = %s THEN afwcr.amount END), 0) AS paid_commission,
									IFNULL(SUM(CASE WHEN afwcr.status = %s AND FIND_IN_SET ( CONVERT(order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s
										FROM {$wpdb->prefix}options
										WHERE option_name = %s )  ) THEN afwcr.amount END), 0) AS unpaid_commission
								FROM {$wpdb->prefix}afwc_referrals AS afwcr
								JOIN {$wpdb->posts} AS p
									ON (afwcr.post_id = p.ID
										AND afwcr.affiliate_id = %d)
								WHERE afwcr.status != %s
									AND (afwcr.datetime BETWEEN %s AND %s)
									AND (afwcr.currency_id = %s)",
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$temp_option_key,
							AFWC_REFERRAL_STATUS_PAID,
							AFWC_REFERRAL_STATUS_UNPAID,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$temp_option_key,
							$affiliate_id,
							AFWC_REFERRAL_STATUS_DRAFT,
							$from,
							$to,
							$currency_id
						),
						'ARRAY_A'
					);
				}
			} elseif ( is_callable( 'afwc_is_hpos_enabled' ) && afwc_is_hpos_enabled() ) {
				if ( $get_deprecated_kpis ) {
					$kpis_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT
								IFNULL( SUM( CASE WHEN FIND_IN_SET( CONVERT(afwcr.order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s ) ) THEN afwcr.order_total END ), 0) AS order_total,
								IFNULL(count(DISTINCT wco.id), 0) AS number_of_orders,
								IFNULL(SUM( afwcr.amount ), 0) as gross_commissions,
								IFNULL(SUM(CASE WHEN afwcr.status = %s THEN afwcr.amount END), 0) AS paid_commission,
								IFNULL(SUM(CASE WHEN afwcr.status = %s AND FIND_IN_SET ( CONVERT( order_status USING %s ) COLLATE %s, ( SELECT CONVERT( option_value USING %s ) COLLATE %s
															FROM {$wpdb->prefix}options
															WHERE option_name = %s )  ) THEN afwcr.amount END), 0) AS unpaid_commission,
								IFNULL(SUM(CASE WHEN afwcr.status = %s THEN afwcr.amount END), 0) AS rejected_commission,
								IFNULL(COUNT(CASE WHEN afwcr.status = %s THEN 1 END), 0) AS paid_count,
								IFNULL(COUNT(CASE WHEN afwcr.status = %s AND FIND_IN_SET ( CONVERT( order_status USING %s ) COLLATE %s, ( SELECT CONVERT( option_value USING %s ) COLLATE %s
															FROM {$wpdb->prefix}options
															WHERE option_name = %s )  ) THEN 1 END), 0) AS unpaid_count,
								IFNULL(COUNT(CASE WHEN afwcr.status = %s THEN 1 END), 0) AS rejected_count
							FROM {$wpdb->prefix}afwc_referrals AS afwcr
								JOIN {$wpdb->prefix}wc_orders AS wco
									ON (afwcr.post_id = wco.id
										AND wco.type = %s
										AND afwcr.affiliate_id = %d)
							WHERE afwcr.status != %s
								AND (afwcr.currency_id = %s)",
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$temp_option_key,
							AFWC_REFERRAL_STATUS_PAID,
							AFWC_REFERRAL_STATUS_UNPAID,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$temp_option_key,
							AFWC_REFERRAL_STATUS_REJECTED,
							AFWC_REFERRAL_STATUS_PAID,
							AFWC_REFERRAL_STATUS_UNPAID,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$temp_option_key,
							AFWC_REFERRAL_STATUS_REJECTED,
							'shop_order',
							$affiliate_id,
							AFWC_REFERRAL_STATUS_DRAFT,
							$currency_id
						),
						'ARRAY_A'
					);
				} else {
					$kpis_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT
								IFNULL( SUM( CASE WHEN FIND_IN_SET( CONVERT(afwcr.order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s ) ) THEN afwcr.order_total END ), 0) AS order_total,
								IFNULL(SUM( afwcr.amount ), 0) as gross_commissions,
								IFNULL(SUM(CASE WHEN afwcr.status = %s THEN afwcr.amount END), 0) AS paid_commission,
								IFNULL(SUM(CASE WHEN afwcr.status = %s AND FIND_IN_SET ( CONVERT( order_status USING %s ) COLLATE %s, ( SELECT CONVERT( option_value USING %s ) COLLATE %s
									FROM {$wpdb->prefix}options
									WHERE option_name = %s )  ) THEN afwcr.amount END), 0) AS unpaid_commission
							FROM {$wpdb->prefix}afwc_referrals AS afwcr
								JOIN {$wpdb->prefix}wc_orders AS wco
									ON (afwcr.post_id = wco.id
										AND wco.type = %s
										AND afwcr.affiliate_id = %d)
							WHERE afwcr.status != %s
								AND (afwcr.currency_id = %s)",
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$temp_option_key,
							AFWC_REFERRAL_STATUS_PAID,
							AFWC_REFERRAL_STATUS_UNPAID,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$temp_option_key,
							'shop_order',
							$affiliate_id,
							AFWC_REFERRAL_STATUS_DRAFT,
							$currency_id
						),
						'ARRAY_A'
					);
				}
			} elseif ( $get_deprecated_kpis ) {
					$kpis_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare(
							"SELECT
								IFNULL( SUM( CASE WHEN FIND_IN_SET( CONVERT(afwcr.order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s ) ) THEN afwcr.order_total END ), 0) AS order_total,
								IFNULL(count(DISTINCT p.ID), 0) AS number_of_orders,
								IFNULL(SUM( afwcr.amount ), 0) as gross_commissions,
								IFNULL(SUM(CASE WHEN afwcr.status = %s THEN afwcr.amount END), 0) AS paid_commission,
								IFNULL(SUM(CASE WHEN afwcr.status = %s AND FIND_IN_SET ( CONVERT(order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s
															FROM {$wpdb->prefix}options
															WHERE option_name = %s )  ) THEN afwcr.amount END), 0) AS unpaid_commission,
								IFNULL(SUM(CASE WHEN afwcr.status = %s THEN afwcr.amount END), 0) AS rejected_commission,
								IFNULL(COUNT(CASE WHEN afwcr.status = %s THEN 1 END), 0) AS paid_count,
								IFNULL(COUNT(CASE WHEN afwcr.status = %s AND FIND_IN_SET ( CONVERT(order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s
															FROM {$wpdb->prefix}options
															WHERE option_name = %s )  ) THEN 1 END), 0) AS unpaid_count,
								IFNULL(COUNT(CASE WHEN afwcr.status = %s THEN 1 END), 0) AS rejected_count
							FROM {$wpdb->prefix}afwc_referrals AS afwcr
								JOIN {$wpdb->posts} AS p
									ON (afwcr.post_id = p.ID
											AND afwcr.affiliate_id = %d)
							WHERE afwcr.status != %s
								AND (afwcr.currency_id = %s)",
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$temp_option_key,
							AFWC_REFERRAL_STATUS_PAID,
							AFWC_REFERRAL_STATUS_UNPAID,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$temp_option_key,
							AFWC_REFERRAL_STATUS_REJECTED,
							AFWC_REFERRAL_STATUS_PAID,
							AFWC_REFERRAL_STATUS_UNPAID,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							AFWC_SQL_CHARSET,
							AFWC_SQL_COLLATION,
							$temp_option_key,
							AFWC_REFERRAL_STATUS_REJECTED,
							$affiliate_id,
							AFWC_REFERRAL_STATUS_DRAFT,
							$currency_id
						),
						'ARRAY_A'
					);
			} else {
				$kpis_result = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT
							IFNULL( SUM( CASE WHEN FIND_IN_SET( CONVERT(afwcr.order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s FROM {$wpdb->prefix}options WHERE option_name = %s ) ) THEN afwcr.order_total END ), 0) AS order_total,
							IFNULL(SUM( afwcr.amount ), 0) as gross_commissions,
							IFNULL(SUM(CASE WHEN afwcr.status = %s THEN afwcr.amount END), 0) AS paid_commission,
							IFNULL(SUM(CASE WHEN afwcr.status = %s AND FIND_IN_SET ( CONVERT(order_status USING %s) COLLATE %s, ( SELECT CONVERT(option_value USING %s) COLLATE %s
													FROM {$wpdb->prefix}options
													WHERE option_name = %s )  ) THEN afwcr.amount END), 0) AS unpaid_commission
						FROM {$wpdb->prefix}afwc_referrals AS afwcr
							JOIN {$wpdb->posts} AS p
								ON (afwcr.post_id = p.ID
									AND afwcr.affiliate_id = %d)
						WHERE afwcr.status != %s
							AND (afwcr.currency_id = %s)",
						AFWC_SQL_CHARSET,
						AFWC_SQL_COLLATION,
						AFWC_SQL_CHARSET,
						AFWC_SQL_COLLATION,
						$temp_option_key,
						AFWC_REFERRAL_STATUS_PAID,
						AFWC_REFERRAL_STATUS_UNPAID,
						AFWC_SQL_CHARSET,
						AFWC_SQL_COLLATION,
						AFWC_SQL_CHARSET,
						AFWC_SQL_COLLATION,
						$temp_option_key,
						$affiliate_id,
						AFWC_REFERRAL_STATUS_DRAFT,
						$currency_id
					),
					'ARRAY_A'
				);
			}

			delete_option( $temp_option_key );

			/**
			 * Filter to modify the KPIs data result.
			 *
			 * @param array  The KPIs data result.
			 * @param array  $args The arguments.
			 *
			 * @since 5.0.0
			 */
			return apply_filters(
				'afwc_my_account_kpis_result',
				array(
					'sales'               => ( ! empty( $kpis_result[0]['order_total'] ) ) ? $kpis_result[0]['order_total'] : 0,
					'number_of_orders'    => ( ! empty( $kpis_result[0]['number_of_orders'] ) ) ? $kpis_result[0]['number_of_orders'] : 0,
					'paid_commission'     => ( ! empty( $kpis_result[0]['paid_commission'] ) ) ? $kpis_result[0]['paid_commission'] : 0,
					'unpaid_commission'   => ( ! empty( $kpis_result[0]['unpaid_commission'] ) ) ? $kpis_result[0]['unpaid_commission'] : 0,
					'rejected_commission' => ( ! empty( $kpis_result[0]['rejected_commission'] ) ) ? $kpis_result[0]['rejected_commission'] : 0,
					'paid_count'          => ( ! empty( $kpis_result[0]['paid_count'] ) ) ? $kpis_result[0]['paid_count'] : 0,
					'unpaid_count'        => ( ! empty( $kpis_result[0]['unpaid_count'] ) ) ? $kpis_result[0]['unpaid_count'] : 0,
					'rejected_count'      => ( ! empty( $kpis_result[0]['rejected_count'] ) ) ? $kpis_result[0]['rejected_count'] : 0,
					'gross_commission'    => ( ! empty( $kpis_result[0]['gross_commissions'] ) ) ? $kpis_result[0]['gross_commissions'] : 0,
				),
				array(
					'source'      => $this,
					'kpis_result' => $kpis_result,
				)
			);
		}

		/**
		 * Get payout KPIs data
		 *
		 * @param array $args arguments.
		 * @return array $kpis.
		 */
		public function get_payout_kpis( $args = array() ) {
			if ( ! afwc_is_valid_date_range( $args ) ) {
				return array();
			}

			global $wpdb;

			$from         = ( ! empty( $args['from'] ) ) ? $args['from'] : '';
			$to           = ( ! empty( $args['to'] ) ) ? $args['to'] : '';
			$affiliate_id = ( ! empty( $args['affiliate_id'] ) ) ? intval( $args['affiliate_id'] ) : 0;

			$paid_order_statuses = afwc_get_paid_order_status(); // Assume this returns an array of statuses.

			if ( ! empty( $from ) && ! empty( $to ) ) {
				$kpis = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						"SELECT IFNULL(SUM( CASE WHEN status = 'paid' THEN amount END ), 0) as paid_commission,
								IFNULL(SUM( CASE WHEN status = 'unpaid' AND order_status IN ( " . implode( ',', array_fill( 0, count( $paid_order_statuses ), '%s' ) ) . " ) THEN amount END ), 0) as unpaid_commission
						FROM {$wpdb->prefix}afwc_referrals
						WHERE
							affiliate_id = %d
							AND datetime BETWEEN %s AND %s
							AND status != %s",
						array_merge(
							$paid_order_statuses,
							array(
								$affiliate_id,
								$from,
								$to,
								AFWC_REFERRAL_STATUS_DRAFT,
							)
						)
					),
					'ARRAY_A'
				);
			} else {
				$kpis = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						"SELECT IFNULL(SUM( CASE WHEN status = 'paid' THEN amount END ), 0) as paid_commission,
								IFNULL(SUM( CASE WHEN status = 'unpaid' AND order_status IN ( " . implode( ',', array_fill( 0, count( $paid_order_statuses ), '%s' ) ) . " ) THEN amount END ), 0) as unpaid_commission
						FROM {$wpdb->prefix}afwc_referrals
						WHERE
							affiliate_id = %d
							AND status != %s",
						array_merge(
							$paid_order_statuses,
							array(
								$affiliate_id,
								AFWC_REFERRAL_STATUS_DRAFT,
							)
						)
					),
					'ARRAY_A'
				);
			}

			return is_array( $kpis ) ? current( $kpis ) : array();
		}

		/**
		 * Function to get refunds data
		 *
		 * @param array $args arguments.
		 * @return array $refunds refunds.
		 */
		public function get_refunds_data( $args = array() ) {
			global $wpdb;

			$from         = ( ! empty( $args['from'] ) ) ? $args['from'] : '';
			$to           = ( ! empty( $args['to'] ) ) ? $args['to'] : '';
			$affiliate_id = ( ! empty( $args['affiliate_id'] ) ) ? $args['affiliate_id'] : 0;

			if ( ! empty( $from ) && ! empty( $to ) ) {
				if ( is_callable( 'afwc_is_hpos_enabled' ) && afwc_is_hpos_enabled() ) {
					$refunds_result = $wpdb->get_results( // phpcs:ignore
														$wpdb->prepare( // phpcs:ignore
															"SELECT IFNULL(SUM(ABS(wco.total_amount)), 0) AS refund_amount,
																				IFNULL(COUNT(DISTINCT wco.parent_order_id), 0) AS refund_order_count
																		FROM {$wpdb->prefix}wc_orders AS wco
																			JOIN {$wpdb->prefix}afwc_referrals AS afwcr
																				ON (afwcr.post_id = wco.parent_order_id
																					AND wco.type = %s
																					AND afwcr.affiliate_id = %d)
																			WHERE afwcr.datetime BETWEEN %s AND %s",
															'shop_order_refund',
															$affiliate_id,
															$from,
															$to
														),
						'ARRAY_A'
					);
				} else {
					$refunds_result = $wpdb->get_results( // phpcs:ignore
														$wpdb->prepare( // phpcs:ignore
															"SELECT IFNULL(SUM(pm.meta_value), 0) AS refund_amount,
																				IFNULL(COUNT(DISTINCT p.post_parent), 0) AS refund_order_count
																		FROM {$wpdb->posts} AS p
																			JOIN {$wpdb->postmeta} AS pm
																				ON (pm.post_id = p.ID
																						AND pm.meta_key = %s
																						AND p.post_type = %s)
																			JOIN {$wpdb->prefix}afwc_referrals AS afwcr
																				ON (afwcr.post_id = p.post_parent)
																		WHERE afwcr.affiliate_id = %d
																			AND (afwcr.datetime BETWEEN %s AND %s) ",
															'_refund_amount',
															'shop_order_refund',
															$affiliate_id,
															$from,
															$to
														),
						'ARRAY_A'
					);
				}
			} elseif ( is_callable( 'afwc_is_hpos_enabled' ) && afwc_is_hpos_enabled() ) {
					$refunds_result = $wpdb->get_results( // phpcs:ignore
														$wpdb->prepare( // phpcs:ignore
															"SELECT IFNULL(SUM(ABS(wco.total_amount)), 0) AS refund_amount,
																				IFNULL(COUNT(DISTINCT wco.parent_order_id), 0) AS refund_order_count
																		FROM {$wpdb->prefix}wc_orders AS wco
																			JOIN {$wpdb->prefix}afwc_referrals AS afwcr
																				ON (afwcr.post_id = wco.parent_order_id
																					AND wco.type = %s
																					AND afwcr.affiliate_id = %d)",
															'shop_order_refund',
															$affiliate_id
														),
						'ARRAY_A'
					);
			} else {
				$refunds_result = $wpdb->get_results( // phpcs:ignore
													$wpdb->prepare( // phpcs:ignore
														"SELECT IFNULL(SUM(pm.meta_value), 0) AS refund_amount,
																				IFNULL(COUNT(DISTINCT p.post_parent), 0) AS refund_order_count
																		FROM {$wpdb->posts} AS p
																			JOIN {$wpdb->postmeta} AS pm
																				ON (pm.post_id = p.ID
																						AND pm.meta_key = %s
																						AND p.post_type = %s)
																			JOIN {$wpdb->prefix}afwc_referrals AS afwcr
																				ON (afwcr.post_id = p.post_parent)
																		WHERE afwcr.affiliate_id = %d",
														'_refund_amount',
														'shop_order_refund',
														$affiliate_id
													),
					'ARRAY_A'
				);
			}
			$refunds = array(
				'refund_amount'      => ( isset( $refunds_result[0]['refund_amount'] ) ) ? $refunds_result[0]['refund_amount'] : 0,
				'refund_order_count' => ( isset( $refunds_result[0]['refund_order_count'] ) ) ? $refunds_result[0]['refund_order_count'] : 0,
			);

			/**
			 * Filter to modify the refunds data result.
			 *
			 * @param array  The refunds data result.
			 * @param array  $args The arguments.
			 *
			 * @since 5.0.0
			 */
			return apply_filters( 'afwc_my_account_refunds_result', $refunds, $args );
		}

		/**
		 * Retrieves the raw referrals report data.
		 *
		 * @param array $args Arguments including filters like date range and affiliate ID.
		 * @return array Referral data rows.
		 */
		public function get_referrals_report( $args = array() ) {
			global $wpdb;

			$from         = ! empty( $args['from'] ) ? $args['from'] : '';
			$to           = ! empty( $args['to'] ) ? $args['to'] : '';
			$affiliate_id = ! empty( $args['affiliate_id'] ) ? intval( $args['affiliate_id'] ) : 0;
			$offset       = ! empty( $args['offset'] ) ? intval( $args['offset'] ) : 0;
			$limit        = ! empty( $args['limit'] ) ? intval( $args['limit'] ) : 0;

			$referrals_result   = array();
			$referrals_data_map = array();

			if ( ! empty( $from ) && ! empty( $to ) ) {
				// Queries if the date range is provided.
				$referrals_result = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						"SELECT CONVERT_TZ(afwcr.datetime, '+00:00', %s) as datetime,
							   afwcr.amount,
							   afwcr.currency_id,
							   afwcr.status,
							   afwcr.type AS medium,
							   IFNULL( afwcr.campaign_id, 0 ) AS campaign,
							   afwcr.post_id AS order_id
						FROM {$wpdb->prefix}afwc_referrals AS afwcr
						WHERE afwcr.affiliate_id = %d
							AND (afwcr.datetime BETWEEN %s AND %s)
						ORDER BY afwcr.datetime DESC
						LIMIT %d OFFSET %d",
						AFWC_TIMEZONE_STR,
						$affiliate_id,
						$from,
						$to,
						$limit,
						$offset
					),
					'ARRAY_A'
				);

			} else {
				// Queries if the date range is not provided.
				$referrals_result = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						"SELECT CONVERT_TZ(afwcr.datetime, '+00:00', %s) as datetime,
							   afwcr.amount,
							   afwcr.currency_id,
							   afwcr.status,
							   afwcr.type AS medium,
							   IFNULL( afwcr.campaign_id, 0 ) AS campaign,
							   afwcr.post_id as order_id
						FROM {$wpdb->prefix}afwc_referrals AS afwcr
						WHERE afwcr.affiliate_id = %d
						ORDER BY afwcr.datetime DESC
						LIMIT %d OFFSET %d",
						AFWC_TIMEZONE_STR,
						$affiliate_id,
						$limit,
						$offset
					),
					'ARRAY_A'
				);

			}

			if ( ! empty( $referrals_result ) && is_array( $referrals_result ) ) {
				// Get the order Ids.
				$order_ids = array_filter(
					array_map(
						function ( $referral ) {
							return ! empty( $referral['order_id'] ) ? absint( $referral['order_id'] ) : 0;
						},
						$referrals_result
					)
				);

				$referrals_order_details = array();
				if ( ! empty( $order_ids ) ) {
					if ( is_callable( 'afwc_is_hpos_enabled' ) && afwc_is_hpos_enabled() ) {
						$referrals_order_details = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT wco.id AS order_id,
									CONCAT_WS( ' ', wcoa.first_name, wcoa.last_name ) AS display_name,
									wco.status AS order_status,
									UNIX_TIMESTAMP(wco.date_created_gmt) AS order_date
								FROM {$wpdb->prefix}wc_orders AS wco
									LEFT JOIN {$wpdb->prefix}wc_order_addresses AS wcoa
										ON ( wco.id = wcoa.order_id AND wcoa.address_type = 'billing' AND wco.type = 'shop_order' )
								WHERE wco.id IN (" . implode( ',', array_fill( 0, count( $order_ids ), '%d' ) ) . ')
								GROUP BY order_id',
								$order_ids
							),
							'ARRAY_A'
						);
					} else {
						$referrals_order_details = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
							$wpdb->prepare(
								"SELECT post_id AS order_id,
									GROUP_CONCAT(CASE WHEN meta_key IN ('_billing_first_name', '_billing_last_name') THEN meta_value END SEPARATOR ' ') AS display_name,
									post.post_status as order_status,
									UNIX_TIMESTAMP(post.post_date_gmt) AS order_date
								FROM {$wpdb->postmeta} AS postmeta
									JOIN {$wpdb->posts} AS post
										ON ( post.ID = postmeta.post_id )
								WHERE meta_key IN ('_billing_first_name', '_billing_last_name')
									AND post_id IN (" . implode( ',', array_fill( 0, count( $order_ids ), '%d' ) ) . ')
								GROUP BY order_id',
								$order_ids
							),
							'ARRAY_A'
						);
					}
				}

				// Format the customer name for fetched orders.
				if ( ! empty( $referrals_order_details ) && is_array( $referrals_order_details ) ) {
					global $affiliate_for_woocommerce;
					foreach ( $referrals_order_details as $detail ) {
						if ( empty( $detail['order_id'] ) ) {
							continue;
						}
						$referrals_data_map[ $detail['order_id'] ]                              = array();
						$referrals_data_map[ $detail['order_id'] ]['display_name']              = ! empty( $detail['display_name'] ) ? $detail['display_name'] : '';
						$referrals_data_map[ $detail['order_id'] ]['days_remaining_for_refund'] = ! empty( $detail['order_date'] ) && is_callable( array( $affiliate_for_woocommerce, 'get_remaining_refund_days_for_order' ) ) ? $affiliate_for_woocommerce->get_remaining_refund_days_for_order( $detail['order_date'] ) : 0;
						$referrals_data_map[ $detail['order_id'] ]['order_status']              = ! empty( $detail['order_status'] ) ? $detail['order_status'] : '';
					}
				}

				$date_format     = get_option( 'date_format' );
				$medium_registry = is_callable( array( Referral_Medium_Registry::class, 'get_instance' ) ) ? Referral_Medium_Registry::get_instance() : null;

				// Format the referral result.
				foreach ( $referrals_result as $key => $ref ) {
					$campaign_title     = ! empty( $ref['campaign'] ) ? afwc_get_campaign_title( intval( $ref['campaign'] ) ) : '';
					$is_campaign_active = ! empty( $ref['campaign'] ) && afwc_is_campaign_active( true, $ref['campaign'] );
					// Get the medium key otherwise set to 'link' by default.
					$medium = ! empty( $ref['medium'] ) ? $ref['medium'] : 'link';

					// For affiliate, Show '-' as referral medium for manually assigned referrals to avoid distrust & confusion, else show the referral medium’s label.
					if ( 'manual' === $medium ) {
						$medium_display = '-';
					} else {
						$medium_obj     = is_callable( array( $medium_registry, 'get_registered' ) ) ? $medium_registry->get_registered( $medium ) : null;
						$medium_display = ! empty( $medium_obj ) && $medium_obj instanceof Referral_Medium_Interface ? $medium_obj->get_label() : $medium;
					}

					$referrals_result[ $key ]['customer_name']             = ( ! empty( $ref['order_id'] ) && ! empty( $referrals_data_map[ $ref['order_id'] ] ) && ! empty( $referrals_data_map[ $ref['order_id'] ]['display_name'] ) ) ? $referrals_data_map[ $ref['order_id'] ]['display_name'] : _x( 'Guest', 'Default value for customer name in my account referral reports', 'affiliate-for-woocommerce' );
					$referrals_result[ $key ]['days_remaining_for_refund'] = ( ! empty( $ref['order_id'] ) && ! empty( $referrals_data_map[ $ref['order_id'] ] ) && ! empty( $referrals_data_map[ $ref['order_id'] ]['days_remaining_for_refund'] ) ) ? $referrals_data_map[ $ref['order_id'] ]['days_remaining_for_refund'] : 0;
					$referrals_result[ $key ]['commission']                = afwc_format_price( ( ! empty( $ref['amount'] ) ? floatval( $ref['amount'] ) : 0 ), ( ! empty( $ref['currency_id'] ) ? $ref['currency_id'] : '' ) );
					$referrals_result[ $key ]['date']                      = ! empty( $ref['datetime'] ) && ! empty( $date_format ) ? gmdate( $date_format, strtotime( $ref['datetime'] ) ) : $ref['datetime'];
					$referrals_result[ $key ]['campaign']                  = ! empty( $ref['campaign'] ) ? intval( $ref['campaign'] ) : 0;
					$referrals_result[ $key ]['campaign_title']            = ! empty( $is_campaign_active ) ? $campaign_title : ''; // Title should show if campaign is active for the affiliate.
					$referrals_result[ $key ]['is_campaign_deleted']       = empty( $campaign_title ); // Check whether deleted or not based on campaign title as campaign could not exists without title.
					$referrals_result[ $key ]['order_status']              = ! empty( $referrals_data_map[ $ref['order_id'] ] ) && ! empty( $referrals_data_map[ $ref['order_id'] ]['order_status'] ) ? wc_get_order_status_name( $referrals_data_map[ $ref['order_id'] ]['order_status'] ) : '';
					$referrals_result[ $key ]['medium']                    = $medium_display;
				}
			}

			return $referrals_result;
		}

		/**
		 * Function to show content in affiliate profile tab.
		 *
		 * @param WP_User $user The user object.
		 */
		public function profile_resources_content( $user = null ) {
			if ( ! is_object( $user ) || empty( $user->ID ) ) {
				return;
			}

			if ( ! wp_script_is( 'jquery' ) ) {
				wp_enqueue_script( 'jquery' );
			}

			if ( ! class_exists( 'WC_AJAX' ) ) {
				include_once WP_PLUGIN_DIR . '/woocommerce/includes/class-wc-ajax.php';
			}

			global $affiliate_for_woocommerce;

			// Data.
			$user_id                                = intval( $user->ID );
			$affiliate                              = new AFWC_Affiliate( $user_id );
			$pname                                  = afwc_get_pname();
			$affiliate_id                           = afwc_get_affiliate_id_based_on_user_id( $user_id );
			$affiliate_identifier                   = is_callable( array( $affiliate, 'get_identifier' ) ) ? $affiliate->get_identifier() : '';
			$afwc_allow_custom_affiliate_identifier = get_option( 'afwc_allow_custom_affiliate_identifier', 'yes' );
			$afwc_use_pretty_referral_links         = get_option( 'afwc_use_pretty_referral_links', 'no' );
			$use_referral_coupon                    = get_option( 'afwc_use_referral_coupons', 'yes' );
			$afwc_enable_stripe_payout              = get_option( 'afwc_enable_stripe_payout', 'no' );
			$affiliate_payout_method                = get_user_meta( $user_id, 'afwc_payout_method', true );
			$use_direct_links                       = ( is_callable( array( 'AFWC_Direct_Link', 'is_enabled' ) ) && AFWC_Direct_Link::is_enabled() ) ? 'yes' : 'no';

			$plugin_data = $affiliate_for_woocommerce->get_plugin_data();

			if ( ! wp_script_is( 'afwc-build-ref-url' ) ) {
				wp_register_script( 'afwc-build-ref-url', AFWC_PLUGIN_URL . '/assets/js/lib/afwc-build-ref-url.js', array( 'jquery', 'wp-i18n' ), $plugin_data['Version'], true );
				if ( function_exists( 'wp_set_script_translations' ) ) {
					wp_set_script_translations( 'afwc-build-ref-url', 'affiliate-for-woocommerce', AFWC_PLUGIN_DIR_PATH . 'languages' );
				}
			}
			wp_enqueue_script( 'afwc-build-ref-url' );

			wp_localize_script(
				'afwc-build-ref-url',
				'afwcBuildRefUrlParams',
				array(
					'homeURL'      => home_url( '/' ),
					'refParam'     => afwc_get_pname(),
					'isPrettyLink' => 'yes' === $afwc_use_pretty_referral_links ? 'yes' : 'no',
				)
			);

			if ( ! wp_script_is( 'afwc-profile-js' ) ) {
				wp_register_script( 'afwc-profile-js', AFWC_PLUGIN_URL . '/assets/js/frontend/my-account/affiliate-profile.js', array( 'jquery', 'wp-i18n', 'afwc-affiliate-link', 'afwc-click-to-copy', 'afwc-build-ref-url' ), $plugin_data['Version'], true );
				if ( function_exists( 'wp_set_script_translations' ) ) {
					wp_set_script_translations( 'afwc-profile-js', 'affiliate-for-woocommerce', AFWC_PLUGIN_DIR_PATH . 'languages' );
				}
			}
			wp_enqueue_script( 'afwc-profile-js' );

			$localize_params = array(
				'pName'                    => $pname,
				'homeURL'                  => esc_url( trailingslashit( home_url() ) ),
				'saveAccountDetailsURL'    => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_save_account_details' ) ),
				'saveAccountSecurity'      => wp_create_nonce( 'afwc-save-account-details' ),
				'isPrettyReferralEnabled'  => $afwc_use_pretty_referral_links,
				'savedAffiliateIdentifier' => $affiliate_identifier,
				'stripeJustConnected'      => 'no',
			);

			if ( 'yes' === $afwc_allow_custom_affiliate_identifier ) {
				$localize_params['identifierRegexPattern'] = afwc_affiliate_identifier_regex_pattern();
				/**
				 * Filter to modify the the error message when identifier pattern validation failed.
				 *
				 * @param string The error message.
				 *
				 * @since 1.17.2
				 */
				$localize_params['identifierPatternValidationErrorMessage'] = apply_filters( 'afwc_affiliate_identifier_regex_pattern_error_message', _x( 'Invalid identifier. It should be a combination of alphabets and numbers, but the number should not be in the first position.', 'referral identifier pattern validation error message', 'affiliate-for-woocommerce' ) );
				$localize_params['saveReferralURLIdentifier']               = esc_url_raw( WC_AJAX::get_endpoint( 'afwc_save_ref_url_identifier' ) );
				$localize_params['saveIdentifierSecurity']                  = wp_create_nonce( 'afwc-save-ref-url-identifier' );
			}

			if ( 'yes' === get_option( 'afwc_enable_stripe_payout', 'no' ) ) {
				$stripe_connect_api = is_callable( array( 'AFWC_Stripe_Connect', 'get_instance' ) ) ? AFWC_Stripe_Connect::get_instance() : null;
				$oauth_link         = ( ! empty( $stripe_connect_api ) && is_callable( array( $stripe_connect_api, 'get_oauth_link' ) ) ) ? $stripe_connect_api->get_oauth_link() : '';

				$stripe_functions = is_callable( array( 'AFWC_Stripe_Functions', 'get_instance' ) ) ? AFWC_Stripe_Functions::get_instance() : null;
				$current_status   = ( ! empty( $stripe_functions ) && is_callable( array( $stripe_functions, 'afwc_get_stripe_user_status' ) ) ) ? $stripe_functions->afwc_get_stripe_user_status( $user_id ) : 'disconnect';

				$localize_params['ajaxURL']                       = admin_url( 'admin-ajax.php' );
				$localize_params['disconnectStripeConnectAction'] = 'disconnect_stripe_connect';
				$localize_params['oauthLink']                     = $oauth_link;
				$localize_params['stripeJustConnected']           = isset( $_GET['scope'], $_GET['code'] ) // phpcs:ignore
					? ( 'connect' === $current_status ? 'success' : 'failure' )
					: 'no';
			}

			wp_localize_script( 'afwc-profile-js', 'afwcProfileParams', $localize_params );

			wp_register_style( 'afwc-profile-css', AFWC_PLUGIN_URL . '/assets/css/frontend/my-account/affiliate-profile.css', array(), $plugin_data['Version'], 'all' );
			if ( ! wp_style_is( 'afwc-profile-css', 'enqueued' ) ) {
				wp_enqueue_style( 'afwc-profile-css' );
			}

			$afwc_enable_affiliate_made_coupons        = ( 'yes' === get_option( 'afwc_enable_affiliate_made_coupons', 'no' ) );
			$afwc_affiliate_made_coupon_discount_limit = get_option( 'afwc_affiliate_made_coupon_discount_limit', 0 );
			if ( ( 'yes' === $use_referral_coupon ) && $afwc_enable_affiliate_made_coupons ) {
				wp_register_style( 'afwc-create-coupon-css', AFWC_PLUGIN_URL . '/assets/css/frontend/my-account/afwc-create-coupon.css', array(), $plugin_data['Version'], 'all' );
				if ( ! wp_style_is( 'afwc-create-coupon-css', 'enqueued' ) ) {
					wp_enqueue_style( 'afwc-create-coupon-css' );
				}
				if ( ! wp_script_is( 'afwc-create-coupon-js' ) ) {
					wp_register_script( 'afwc-create-coupon-js', AFWC_PLUGIN_URL . '/assets/js/frontend/my-account/afwc-create-coupon.js', array( 'jquery', 'wp-i18n' ), $plugin_data['Version'], true );
					if ( function_exists( 'wp_set_script_translations' ) ) {
						wp_set_script_translations( 'afwc-create-coupon-js', 'affiliate-for-woocommerce', AFWC_PLUGIN_DIR_PATH . 'languages' );
					}
				}
				wp_localize_script(
					'afwc-create-coupon-js',
					'afwcCreateCouponParams',
					array(
						'canCreateReferralCoupons'       => true,
						'maxReferralCouponAmount'        => ! empty( $afwc_affiliate_made_coupon_discount_limit ) ? $afwc_affiliate_made_coupon_discount_limit : 0,
						'generateReferralCouponEndpoint' => esc_url_raw( WC_AJAX::get_endpoint( 'afwc_generate_referral_coupon' ) ),
						'generateReferralCouponSecurity' => wp_create_nonce( 'afwc-affiliate-create-referral-coupon' ),
					)
				);
				wp_enqueue_script( 'afwc-create-coupon-js' );
			}

			// Template name.
			$template = 'my-account/affiliate-profile.php';
			// Default path of above template.
			$default_path = AFWC_PLUGIN_DIRPATH . '/templates/';
			// Pick from another location if found.
			$template_path = $affiliate_for_woocommerce->get_template_base_dir( $template );

			/**
			 * Filter to show coupon URL in affiliate profile page in My Account.
			 *
			 * @param string  'yes' or 'no'.
			 *
			 * @since 8.21.0
			 */
			$show_coupon_url = apply_filters( 'afwc_show_coupon_url_in_my_account_profile', ( afwc_can_generate_coupon_url() && 'yes' === $use_referral_coupon ) ? 'yes' : 'no' );

			wc_get_template(
				$template,
				array(
					'user'                               => $user,
					'user_id'                            => $user_id,
					'pname'                              => $pname,
					'affiliate_url'                      => is_callable( array( $affiliate, 'get_affiliate_link' ) ) ? $affiliate->get_affiliate_link() : '',
					'affiliate_id'                       => $affiliate_id,
					'affiliate_identifier'               => $affiliate_identifier,
					'affiliate_manager_contact_email'    => get_option( 'afwc_contact_admin_email_address', '' ),
					'afwc_use_referral_coupons'          => $use_referral_coupon,
					'afwc_landings_pages'                => is_callable( array( $affiliate, 'get_landing_page_links' ) ) ? $affiliate->get_landing_page_links() : array(),
					'afwc_allow_custom_affiliate_identifier' => $afwc_allow_custom_affiliate_identifier,
					'afwc_use_pretty_referral_links'     => $afwc_use_pretty_referral_links,
					'show_coupon_url'                    => $show_coupon_url,
					'afwc_enable_stripe_payout'          => $afwc_enable_stripe_payout,
					'payout_method'                      => $affiliate_payout_method,
					'available_payout_methods'           => afwc_get_available_payout_methods_for_affiliate( $user_id ),
					'current_status'                     => ( ! empty( $current_status ) ? $current_status : '' ),
					'afwc_use_direct_links'              => $use_direct_links,
					'afwc_direct_links'                  => is_callable( array( $affiliate, 'get_direct_links' ) ) ? $affiliate->get_direct_links() : array(),
					'afwc_enable_affiliate_made_coupons' => $afwc_enable_affiliate_made_coupons,
					'afwc_affiliate_made_coupon_discount_limit' => $afwc_affiliate_made_coupon_discount_limit,
					'afwc_affiliate_made_coupon_create_limit' => afwc_get_self_made_coupon_create_limit( $user_id ),
				),
				$template_path,
				$default_path
			);
		}

		/**
		 * Function to save referral URL identifier
		 *
		 * @throws Exception If any error during the process.
		 */
		public function afwc_save_ref_url_identifier() {
			check_ajax_referer( 'afwc-save-ref-url-identifier', 'security' );

			$ref_url_id = ( ! empty( $_POST['ref_url_id'] ) ) ? wc_clean( wp_unslash( $_POST['ref_url_id'] ) ) : ''; // phpcs:ignore

			if ( empty( $ref_url_id ) ) {
				wp_send_json(
					array(
						'success' => 'no',
						'message' => _x(
							'Missing data',
							'referral url identifier updating error message',
							'affiliate-for-woocommerce'
						),
					)
				);
			}

			global $affiliate_for_woocommerce;

			try {

				if ( 'yes' !== get_option( 'afwc_allow_custom_affiliate_identifier', 'yes' ) ) {
					throw new Exception(
						_x(
							'Custom affiliate URL identifier not allowed.',
							'referral url identifier updating error message',
							'affiliate-for-woocommerce'
						)
					);
				}

				if ( ! is_callable( array( $affiliate_for_woocommerce, 'save_ref_url_identifier' ) ) || ! $affiliate_for_woocommerce->save_ref_url_identifier( get_current_user_id(), $ref_url_id ) ) {
					throw new Exception(
						_x(
							'The URL identifier could not updated',
							'referral url identifier updating error message',
							'affiliate-for-woocommerce'
						)
					);
				}

				wp_send_json(
					array(
						'success' => 'yes',
						'message' => _x(
							'Identifier saved successfully.',
							'referral url identifier updated message',
							'affiliate-for-woocommerce'
						),
					)
				);
			} catch ( Exception $e ) {

				wp_send_json(
					array(
						'success' => 'no',
						'message' => is_callable( array( $e, 'getMessage' ) ) ? $e->getMessage() : _x(
							'Something went wrong',
							'referral url identifier updating error message',
							'affiliate-for-woocommerce'
						),
					)
				);
			}
		}

		/**
		 * Function to save account details
		 */
		public function afwc_save_account_details() {
			check_ajax_referer( 'afwc-save-account-details', 'security' );

			$user_id = get_current_user_id();
			if ( empty( $user_id ) ) {
				wp_send_json(
					array(
						'success' => 'no',
						'message' => _x( 'Invalid user', 'account details updating error message', 'affiliate-for-woocommerce' ),
					)
				);
			}

			$form_data = ( ! empty( $_POST['form_data'] ) ) ? sanitize_text_field( wp_unslash( $_POST['form_data'] ) ) : '';
			if ( empty( $form_data ) ) {
				wp_send_json(
					array(
						'success' => 'no',
						'message' => _x(
							'Missing data',
							'account details updating error message',
							'affiliate-for-woocommerce'
						),
					)
				);
			}

			if ( ! empty( $form_data ) ) {
				parse_str( $form_data, $data );
			}

			$payout_method = ! empty( $data['afwc_payout_method'] ) ? $data['afwc_payout_method'] : '';
			if ( empty( $payout_method ) ) {
				delete_user_meta( $user_id, 'afwc_payout_method' );
				wp_send_json( array( 'success' => 'yes' ) );
			} else {
				update_user_meta( $user_id, 'afwc_payout_method', $payout_method );
			}

			$paypal_email = ! empty( $data['afwc_affiliate_paypal_email'] ) ? $data['afwc_affiliate_paypal_email'] : '';

			// Send success and delete the user meta if PayPal email is empty.
			if ( empty( $paypal_email ) ) {
				delete_user_meta( $user_id, 'afwc_paypal_email' );
				wp_send_json( array( 'success' => 'yes' ) );
			}

			// Send failure message if the email address is not valid.
			if ( false === is_email( $paypal_email ) ) {
				wp_send_json(
					array(
						'success' => 'no',
						'message' => _x( 'The PayPal email address is incorrect.', 'Affiliate My Account page: PayPal email validation', 'affiliate-for-woocommerce' ),
					)
				);
			}

			// Send success and update the PayPal email.
			update_user_meta( $user_id, 'afwc_paypal_email', sanitize_email( $paypal_email ) );
			wp_send_json( array( 'success' => 'yes' ) );
		}

		/**
		 * Get the value of the {afwc_affiliate_coupon} merge tag.
		 * The value will be available for Profile tab only.
		 *
		 * @param string $value The value.
		 * @param array  $args An array of arguments.
		 *
		 * @return string The value.
		 */
		public function get_afwc_affiliate_coupon_merge_tag_value( $value = '', $args = array() ) {
			global $wp;

			if ( empty( $wp->query_vars ) || empty( $wp->query_vars[ $this->afwc_tab_endpoint ] ) || 'resources' !== $wp->query_vars[ $this->afwc_tab_endpoint ] ) { // Check whether profile tab is active.
				return $value;
			}

			$attrs = ! empty( $args['attrs'] ) && is_array( $args['attrs'] ) ? $args['attrs'] : array();

			if ( empty( $attrs ) || empty( $attrs['code'] ) ) { // Check whether the coupon code is provided.
				return $value;
			}

			$afwc_coupon = is_callable( array( 'AFWC_Coupon', 'get_instance' ) ) ? AFWC_Coupon::get_instance() : null;
			$coupon_url  = is_callable( array( $afwc_coupon, 'get_coupon_url' ) ) ? $afwc_coupon->get_coupon_url( $attrs['code'] ) : '';
			return ! empty( $coupon_url ) ? afwc_get_click_to_copy_html( $coupon_url ) : '';
		}

		/**
		 * Method to handle the Stripe Connect's connection.
		 */
		public function afwc_handle_stripe_connect() {
			// The page is NOT loaded from Stripe Platform.
			if ( ! isset( $_GET['scope'], $_GET['code'] ) ) { // phpcs:ignore
				return;
			}

			if ( ! is_user_logged_in() ) {
				return;
			}

			$user_id = get_current_user_id();
			if ( empty( $user_id ) ) {
				return;
			}

			if ( ! $this->is_afwc_dashboard() ) {
				return;
			}

			global $wp;
			if ( empty( $wp->query_vars ) || empty( $wp->query_vars[ $this->afwc_tab_endpoint ] ) || 'resources' !== $wp->query_vars[ $this->afwc_tab_endpoint ] ) {
				return;
			}

			$stripe_functions = is_callable( array( 'AFWC_Stripe_Functions', 'get_instance' ) ) ? AFWC_Stripe_Functions::get_instance() : null;
			if ( empty( $stripe_functions ) ) {
				return;
			}

			$code           = sanitize_text_field( wp_unslash( $_GET['code'] ) ); // phpcs:ignore
			$just_connected = is_callable( array( $stripe_functions, 'afwc_get_stripe_user_status' ) )
				? $stripe_functions->connect_by_user_id_and_access_code( $user_id, $code )
				: false;
		}

		/**
		 * Function to show campaigns content resources
		 *
		 * @param WP_User $user The user object.
		 */
		public function campaigns_content( $user = null ) {
			if ( ! is_object( $user ) || empty( $user->ID ) ) {
				return;
			}
			?>
			<div class="afw-campaigns"></div>
			<?php
		}

		/**
		 * Function to show multi tier content resources.
		 *
		 * @param WP_User $user The user object.
		 */
		public function multi_tier_content( $user = null ) {
			if ( ! is_object( $user ) || empty( $user->ID ) ) {
				return;
			}
			?>
			<div class="afw-multi-tier"></div>
			<?php
		}

		/**
		 * Method to render the affiliate dashboard by shortcode.
		 *
		 * @return string Show the Affiliate dashboard screen for logged in user otherwise WooCommerce login form.
		 */
		public function afwc_dashboard_shortcode_content() {
			ob_start();

			$current_user = wp_get_current_user();
			if ( ! $current_user instanceof WP_User || empty( $current_user->ID ) ) {
				// Show the WooCommerce login form if user is not logged in.
				woocommerce_login_form();
			} else {
				$affiliate_status = afwc_is_user_affiliate( $current_user );

				if ( ! empty( $affiliate_status ) ) {

					$afwc_registration = AFWC_Registration_Submissions::get_instance();

					if ( in_array( $affiliate_status, array( 'not_registered', 'pending', 'no' ), true ) && is_callable( array( $afwc_registration, 'get_message' ) ) ) {
						// Show message for not registered, pending and rejected affiliates.
						echo wp_kses_post( $afwc_registration->get_message( $affiliate_status ) );
					} elseif ( 'yes' === $affiliate_status ) {
						// Render the dashboard for approved affiliate.
						$this->afwc_dashboard_content( $current_user );
					}
				}
			}

			return ob_get_clean();
		}
	}
}

AFWC_My_Account::get_instance();
