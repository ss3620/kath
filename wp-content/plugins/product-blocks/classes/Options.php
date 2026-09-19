<?php
/**
 * Options Action.
 *
 * @package WOPB\Notice
 * @since v.1.0.0
 */
namespace WOPB;

use WOPB\Includes\Durbin\Xpo;
use WOPB\Includes\Notice\Notice;

defined( 'ABSPATH' ) || exit;

/**
 * Options class.
 */
class Options {

	/**
	 * Setup class.
	 *
	 * @since v.1.1.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'menu_page_callback' ) );
		add_action( 'in_admin_header', array( $this, 'remove_all_notices' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_settings_meta' ), 10, 2 );
		add_filter( 'plugin_action_links_' . WOPB_BASE, array( $this, 'plugin_action_links_callback' ) );
		add_action( 'add_meta_boxes', array( $this, 'single_product_meta_box' ) );
	}

	/**
	 * Remove All Notification From Menu Page
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public static function remove_all_notices() {
		if (
			wopb_function()->get_screen() == 'wopb-settings' ||
			wopb_function()->get_screen() == 'wopb-initial-setup-wizard'
		) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
		}
	}


	/**
	 * Plugins Settings Meta Menu Add
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function plugin_settings_meta( $links, $file ) {
		if ( strpos( $file, 'product-blocks.php' ) !== false ) {
			$new_links = array(
				'wopb_docs'     => '<a href="https://wpxpo.com/docs/wowstore/?utm_source=db-wstore-plugin&utm_medium=docs&utm_campaign=wstore-dashboard" target="_blank">' . esc_html__( 'Docs', 'product-blocks' ) . '</a>',
				'wopb_tutorial' => '<a href="https://www.youtube.com/@wpxpo/videos" target="_blank">' . esc_html__( 'Tutorials', 'product-blocks' ) . '</a>',
				'wopb_support'  => '<a href="https://www.wpxpo.com/contact/?utm_source=db-wstore-plugin&utm_medium=quick-support&utm_campaign=wstore-dashboard" target="_blank">' . esc_html__( 'Support', 'product-blocks' ) . '</a>',
			);
			$links     = array_merge( $links, $new_links );
		}
		return $links;
	}


	/**
	 * Plugins Settings Meta Pro Link Add
	 *
	 * @since v.1.1.0
	 * @return NULL
	 */
	public function plugin_action_links_callback( $links ) {
		$setting_link = array(
			'wopb_settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=wopb-settings#settings' ) ) . '">' . esc_html__( 'Settings', 'product-blocks' ) . '</a>',
		);

		$upgrade_link = array();

		// @wopb-upgrade-renew: renew or upgrade link based on the license expiry status (free or expired license user).
		if ( ! defined( 'WOPB_PRO_VER' ) || Xpo::is_lc_expired() ) {

			$license_key = Xpo::get_lc_key() ?? '';

			if ( Xpo::is_lc_expired() ) {
				$text = esc_html__( 'Renew License', 'product-blocks' );
				$url  = 'https://account.wpxpo.com/checkout/?edd_license_key=' . $license_key;
			} else {

				// Evergreen default. Not a promo, so it stays here rather than in
				// includes/notice/promos/ — a live entry there simply overrides it.
				$text = esc_html__( 'Upgrade to Pro', 'product-blocks' );
				$url  = Xpo::generate_utm_link();

				$promo = Notice::get_active_promo( 'plugin-meta' );
				if ( $promo ) {
					$text = $promo['text'];
					$url  = $promo['url'];
				}
			}

			$upgrade_link['wopb_pro'] = '<a style="color: #e83838; font-weight: bold;" target="_blank" href="' . esc_url( $url ) . '">' . esc_html( $text ) . '</a>';
		}

		return array_merge( $setting_link, $links, $upgrade_link );
	}


	/**
	 * Plugins Menu Page Added
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public static function menu_page_callback() {
		$menupage_cap = apply_filters( 'wopb_menu_page_capability', 'manage_options' );
		add_menu_page(
			esc_html__( 'WowStore', 'product-blocks' ),
			esc_html__( 'WowStore', 'product-blocks' ),
			$menupage_cap,
			'wopb-settings',
			array( self::class, 'wowstore_dashboard' ),
			plugins_url( 'product-blocks/assets/img/logo-sm.svg' ),
			58.5
		);
		add_submenu_page(
			'wopb-settings',
			esc_html__( 'WowStore Dashboard', 'product-blocks' ),
			esc_html__( 'Addons', 'product-blocks' ),
			$menupage_cap,
			'wopb-settings'
		);
		$menu_lists = array(
			'builder'         => __( 'Woo Builder', 'product-blocks' ),
			'templatekit'     => __( 'Template Kits', 'product-blocks' ),
			'blocks'          => __( 'Blocks', 'product-blocks' ),
			'saved-templates' => __( 'Saved Template', 'product-blocks' ),
			'custom-font'     => __( 'Custom Font', 'product-blocks' ),
		);

		$menu_lists = array_merge( $menu_lists, array( 'size-chart' => __( 'Size Chart', 'product-blocks' ) ) );
		$menu_lists = array_merge(
			$menu_lists,
			array(
				'revenue'  => __( 'Revenue', 'product-blocks' ) . '<span class="wopb-revenue-tag">New</span>',
				'settings' => __( 'Settings', 'product-blocks' ),
				// 'support'           => __( 'Quick Support', 'product-blocks' )
			)
		);

		if ( defined( 'WOPB_PRO_VER' ) ) {
			$menu_lists['license'] = __( 'License', 'product-blocks' );
		}

		foreach ( $menu_lists as $key => $val ) {
			add_submenu_page(
				'wopb-settings',
				$val,
				$val,
				$menupage_cap,
				'wopb-settings#' . $key,
				array( __CLASS__, 'render_main' )
			);
		}

		do_action( 'wowstore_menu' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- established hook name, renaming would break existing integrations.

		$pro_link             = '';
		$pro_link_text        = '';
		$current_date         = current_time( 'Y-m-d' );
		$menu_pro_text_period = (
			$current_date >= '2026-01-01' &&
			$current_date <= '2026-02-15'
		);

		// @wopb-upgrade-renew: renew or upgrade link based on the license expiry status.
		// swapped lc expired on if so we check the expiry first.
		// then we check if the pro is available and the pro license is valid.
		if ( Xpo::is_lc_expired() ) {
			$license_key   = Xpo::get_lc_key();
			$pro_link      = 'https://account.wpxpo.com/checkout/?edd_license_key=' . $license_key;
			$pro_link_text = __( 'Renew License', 'product-blocks' );
		} elseif ( ! Xpo::is_lc_active() ) {
			$pro_link      = Xpo::generate_utm_link(
				array(
					'utmKey' => $menu_pro_text_period ? 'new_year_sale' : 'sub_menu',
				)
			);
			$pro_link_text = $menu_pro_text_period ? __( 'New Year Offer!', 'product-blocks' ) : __( 'Upgrade to Pro', 'product-blocks' );

		}

		if ( ! empty( $pro_link ) ) {
			ob_start();
			?>
				<a href="<?php echo esc_url( $pro_link ); ?>" target="_blank" class="wopb-go-pro">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M2.86 6.553a.5.5 0 01.823-.482l3.02 2.745c.196.178.506.13.64-.098L9.64 4.779a.417.417 0 01.72 0l2.297 3.939a.417.417 0 00.64.098l3.02-2.745a.5.5 0 01.823.482l-1.99 8.63a.833.833 0 01-.813.646H5.663a.833.833 0 01-.812-.646L2.86 6.553z" stroke="currentColor" stroke-width="1.5"></path>
					</svg>
					<span><?php echo esc_html( $pro_link_text ); ?></span>
				</a>
			<?php
			$submenu_content = ob_get_clean();

			add_submenu_page(
				'wopb-settings',
				'',
				$submenu_content,
				'manage_options',
				'wopb-pro',
				array( self::class, 'handle_external_redirects' )
			);

		}
	}


	public static function handle_external_redirects() {
		if ( empty( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		if ( wopb_function()->get_screen() === 'wopb-pro' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			// @wopb-upgrade-renew: redirect to renew or upgrade link based on the license expiry status.
			$redirect_url = wopb_function()->is_lc_expired() ? wopb_function()->get_renew_link() : wopb_function()->get_premium_link( '', 'main_menu_go_pro' );
			wp_redirect( $redirect_url ); //phpcs:ignore
			die();
		}
	}

	/**
	 * Content of Tab Page
	 *
	 * @return STRING
	 */
	public static function wowstore_dashboard() {
		echo '<div id="wopb-dashboard"></div>';
	}

	/**
	 * Single Product Meta Box
	 *
	 * @return void
	 * @since v.4.0.0
	 */
	public function single_product_meta_box() {
		if ( wopb_function()->get_setting( 'wopb_builder' ) == 'true' && current_user_can( 'manage_options' ) ) {
			add_meta_box(
				'wopb-single-product-meta-box',
				'<div class="wopb-single-product-meta-box"><img src="' . esc_url( WOPB_URL . 'assets/img/logo-sm.svg' ) . '" /><span>' . esc_html__( 'WowStore Settings', 'product-blocks' ) . '</span></div>',
				array( $this, 'builder_product_metabox_html' ),
				'product',
				'side',
				'core'
			);
		}
	}

	public function builder_product_metabox_html() {
		?>
		<div class="wopb-meta-builder">
			<a class="wopb-dash-builder-btn" target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=wopb-settings#builder' ) ); ?>"><?php echo esc_html__( 'Enable Product Single Builder', 'product-blocks' ); ?></a>
		</div>
		<?php
	}
}
