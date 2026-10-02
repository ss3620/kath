<?php //phpcs:ignore
/**
 * Options Action.
 *
 * @package PRAD\Options
 * @since v.1.0.0
 */

namespace PRAD\Includes\Admin;

defined( 'ABSPATH' ) || exit;

use PRAD\Includes\Xpo;

/**
 * Options class.
 */
class Options {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'handle_external_redirects' ) );
		add_action( 'admin_menu', array( $this, 'menu_page_callback' ) );
		add_action( 'in_admin_header', array( $this, 'remove_all_notices' ) );

		add_filter( 'plugin_action_links_' . PRAD_BASE, array( $this, 'plugin_action_links_callback' ) );
		add_filter( 'plugin_row_meta', array( $this, 'plugin_settings_meta' ), 10, 2 );
	}

	/**
	 * Adds quick action links below the plugin name.
	 *
	 * @param array $links Default plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function plugin_action_links_callback( $links ) {
		$offer_config = array(
			array(
				'start'  => '2026-09-02 00:00 Asia/Dhaka',
				'end'    => '2026-10-10 23:59 Asia/Dhaka',
				'text'   => __(
					'Get Pro at $52',
					'product-addons'
				),
				'utmKey' => 'plugin_meta_base_price',
			),
		);

		$setting_link = array(
			'prad_options' => '<a href="' . esc_url( admin_url( 'admin.php?page=prad-dashboard#lists' ) ) . '">' . esc_html__( 'Create Options', 'product-addons' ) . '</a>',
		);

		$upgrade_link = array();

		$text = esc_html__( 'Upgrade to Pro', 'product-addons' );
		$url  = Xpo::generate_utm_link(
			array(
				'utmKey' => 'plugin_meta',
			)
		);

		foreach ( $offer_config as $offer ) {
			$current_time = gmdate( 'U' );
			$notice_start = gmdate( 'U', strtotime( $offer['start'] ) );
			$notice_end   = gmdate( 'U', strtotime( $offer['end'] ) );
			if ( $current_time >= $notice_start && $current_time <= $notice_end ) {
				$url  = Xpo::generate_utm_link(
					array(
						'utmKey' => $offer['utmKey'],
					)
				);
				$text = $offer['text'];
				break;
			}
		}

		// Pro decides here whether to override this with a renew link, or suppress
		// it entirely (valid license) — free never checks license state itself.
		$link = apply_filters(
			'prad_pro_renew_link',
			array(
				'text' => $text,
				'url'  => $url,
			)
		);

		if ( $link ) {
			$upgrade_link['prad_pro'] = '<a style="color: #e83838; font-weight: bold;" target="_blank" href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['text'] ) . '</a>';
		}

		return array_merge( $setting_link, $links, $upgrade_link );
	}

	/**
	 * Adds extra links to the plugin row meta on the plugins page.
	 *
	 * @param array  $links Existing plugin meta links.
	 * @param string $file  Plugin file path.
	 * @return array Modified plugin meta links.
	 */
	public function plugin_settings_meta( $links, $file ) {
		if ( strpos( $file, 'product-addons.php' ) !== false ) {
			$new_links = array(
				'prad_docs'    => '<a href="https://wpxpo.com/docs/wowaddons/?utm_source=db-wowaddons-plugin&utm_medium=doc&utm_campaign=wowaddons-dashboard" target="_blank">' . esc_html__( 'Docs', 'product-addons' ) . '</a>',
				'prad_support' => '<a href="https://www.wpxpo.com/contact/" target="_blank">' . esc_html__( 'Support', 'product-addons' ) . '</a>',
			);
			$links     = array_merge( $links, $new_links );
		}
		return $links;
	}

	/**
	 * Admin Menu Option Page
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public static function menu_page_callback() {
		$menupage_cap = Xpo::prad_old_view_permisson_handler();

		add_menu_page(
			__( 'WowAddons', 'product-addons' ),
			__( 'WowAddons', 'product-addons' ),
			$menupage_cap,
			'prad-dashboard',
			array( self::class, 'tab_page_content' ),
			PRAD_URL . '/assets/img/wowaddons-icon-square.svg',
			58.5
		);

		add_submenu_page(
			'prad-dashboard',
			__( 'WowAddons Dashboard', 'product-addons' ),
			__( 'Dashboard', 'product-addons' ),
			$menupage_cap,
			'prad-dashboard'
		);

		$menu_lists              = array();
		$menu_lists['lists']     = esc_html__( 'Option Set', 'product-addons' );
		$menu_lists['analytics'] = esc_html__( 'Analytics', 'product-addons' );
		$menu_lists['settings']  = esc_html__( 'Settings', 'product-addons' );

		// A plain link to the dashboard: with no callback, WordPress uses the slug as the URL.
		add_submenu_page(
			'edit.php?post_type=product',
			__( 'WowAddons', 'product-addons' ),
			__( 'WowAddons', 'product-addons' ),
			$menupage_cap,
			'admin.php?page=prad-dashboard#dashboard'
		);

		$menu_lists = array_merge( $menu_lists, apply_filters( 'prad_pro_menu_lists', array() ) );

		foreach ( $menu_lists as $key => $val ) {
			add_submenu_page(
				'prad-dashboard',
				$val,
				$val,
				$menupage_cap,
				'prad-dashboard#' . $key,
				array( __CLASS__, 'tab_page_content' )
			);
		}

		$upgrade_link_text = __( 'Upgrade to Pro!', 'product-addons' );
		$upgrade_link      = Xpo::generate_utm_link(
			array(
				'utmKey' => 'sub_menu',
			)
		);

		// Pro decides here whether to override this with a renew link, or suppress
		// it entirely (valid license) — free never checks license state itself.
		$link = apply_filters(
			'prad_pro_renew_link',
			array(
				'text' => $upgrade_link_text,
				'url'  => $upgrade_link,
			)
		);

		$upgrade_link      = $link ? $link['url'] : '';
		$upgrade_link_text = $link ? $link['text'] : '';

		if ( ! empty( $upgrade_link ) ) {
			ob_start();
			?>
				<a href="<?php echo esc_url( $upgrade_link ); ?>" target="_blank" class="prad-go-pro">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M2.86 6.553a.5.5 0 01.823-.482l3.02 2.745c.196.178.506.13.64-.098L9.64 4.779a.417.417 0 01.72 0l2.297 3.939a.417.417 0 00.64.098l3.02-2.745a.5.5 0 01.823.482l-1.99 8.63a.833.833 0 01-.813.646H5.663a.833.833 0 01-.812-.646L2.86 6.553z" stroke="currentColor" stroke-width="1.5"></path>
					</svg>
					<span><?php echo esc_html( $upgrade_link_text ); ?></span>
				</a>
			<?php
			$submenu_content = ob_get_clean();

			add_submenu_page(
				'prad-dashboard',
				'',
				$submenu_content,
				$menupage_cap,
				'prad-pro',
				array( self::class, 'handle_external_redirects' )
			);

		}
	}

	/**
	 * Go to Pro URL Redirect
	 *
	 * Handles the legacy `go_prad_pro` link and the "Upgrade to Pro" submenu slug (`prad-pro`),
	 * so opening either URL directly lands on the pricing page. It is static because it is also
	 * registered as that submenu's page callback from a static context, and PHP 8 cannot call a
	 * non-static method statically.
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public static function handle_external_redirects() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing parameter.
		if ( ! in_array( $page, array( 'go_prad_pro', 'prad-pro' ), true ) ) {
			return;
		}

		// wp_safe_redirect() only allows the site's own host, so permit the destination for this one redirect.
		add_filter(
			'allowed_redirect_hosts',
			static function ( $hosts ) {
				$hosts[] = 'www.wpxpo.com';
				return $hosts;
			}
		);
		wp_safe_redirect( 'https://www.wpxpo.com/product/wowaddons/' );
		exit;
	}

	/**
	 * Initial Plugin Setting
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public static function tab_page_content() {
		echo wp_kses( '<div id="prad-dashboard-wrap"></div>', apply_filters( 'prad_allowed_html_tags', array() ) );
	}

	/**
	 * Remove All Notification From Menu Page
	 *
	 * WowAddons renders a full-page React builder on the 'prad-dashboard'
	 * screen. Third-party admin notices/headers render above or inside that
	 * app root and break its layout, so they're suppressed on this screen only.
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public static function remove_all_notices() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing parameter.
		if ( 'prad-dashboard' === $page ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'in_admin_header' );
		}
	}
}
