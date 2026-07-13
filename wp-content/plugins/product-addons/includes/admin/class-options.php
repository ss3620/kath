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
	 * **YOU NEED TO CUSTOMIZE THIS FUNCTION**
	 *
	 * @param array $links Default plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function plugin_action_links_callback( $links ) {
		$offer_config = array(
			array(
				'start'  => '2026-05-07 00:00 Asia/Dhaka',
				'end'    => '2026-05-21 23:59 Asia/Dhaka',
				'text'   => __(
					'Flash Sale - Up to 50% OFF',
					'product-addons'
				),
				'utmKey' => 'plugin_meta',
			),
			array(
				'start'  => '2026-05-22 00:00 Asia/Dhaka',
				'end'    => '2026-06-01 23:59 Asia/Dhaka',
				'text'   => __(
					'Surprise Sale - Up to 55% OFF',
					'product-addons'
				),
				'utmKey' => 'plugin_meta',
			),
			array(
				'start'  => '2026-06-02 00:00 Asia/Dhaka',
				'end'    => '2026-06-20 23:59 Asia/Dhaka',
				'text'   => __(
					'Massive Sale - Up to 50% OFF',
					'product-addons'
				),
				'utmKey' => 'plugin_meta',
			),
			array(
				'start'  => '2026-06-21 00:00 Asia/Dhaka',
				'end'    => '2026-06-30 23:59 Asia/Dhaka',
				'text'   => __(
					'Final Hour Sale - Up to 55% OFF',
					'product-addons'
				),
				'utmKey' => 'plugin_meta',
			),
		);

		$setting_link = array(
			'prad_options' => '<a href="' . esc_url( admin_url( 'admin.php?page=prad-dashboard#lists' ) ) . '">' . esc_html__( 'Create Options', 'product-addons' ) . '</a>',
		);

		$upgrade_link = array();

		// Free user or expired license user.
		if ( ! defined( 'PRAD_PRO_VER' ) || Xpo::is_lc_expired() ) {

			if ( Xpo::is_lc_expired() ) {
				$text = esc_html__( 'Renew License', 'product-addons' );
				$url  = 'https://account.wpxpo.com/checkout/?edd_license_key=' . Xpo::get_lc_key() . '&renew=1';
			} else {

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
			}

			$upgrade_link['prad_pro'] = '<a style="color: #e83838; font-weight: bold;" target="_blank" href="' . esc_url( $url ) . '">' . $text . '</a>';
		}

		return array_merge( $setting_link, $links, $upgrade_link );
	}

	/**
	 * Adds extra links to the plugin row meta on the plugins page.
	 * **YOU NEED TO CUSTOMIZE THIS FUNCTION**
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
		$menu_lists['lists']     = esc_html__( 'Option Lists', 'product-addons' );
		$menu_lists['analytics'] = esc_html__( 'Analytics', 'product-addons' );
		$menu_lists['settings']  = esc_html__( 'Settings', 'product-addons' );

		add_submenu_page(
			'edit.php?post_type=product',
			__( 'WowAddons', 'product-addons' ),
			__( 'WowAddons', 'product-addons' ),
			$menupage_cap,
			'wowaddons-page',
			array( __CLASS__, 'render_main' )
		);

		if ( defined( 'PRAD_PRO_VER' ) ) {
			$menu_lists['license'] = esc_html__( 'License', 'product-addons' );
		}
		foreach ( $menu_lists as $key => $val ) {
			add_submenu_page(
				'prad-dashboard',
				$val,
				$val,
				$menupage_cap,
				'prad-dashboard#' . $key,
				array( __CLASS__, 'render_main' )
			);
		}

		$pro_link      = '';
		$pro_link_text = '';
		if ( Xpo::is_lc_expired() ) {
			$license_key   = Xpo::get_lc_key();
			$pro_link      = 'https://account.wpxpo.com/checkout/?edd_license_key=' . $license_key. '&renew=1';
			$pro_link_text = __( 'Renew License', 'product-addons' );
		} elseif ( ! Xpo::is_lc_active() ) {
			$pro_link      = Xpo::generate_utm_link(
				array(
					'utmKey' => 'sub_menu',
				)
			);
			$pro_link_text = __( 'Upgrade to Pro!', 'product-addons' );

			$is_offer_running = true;
			if ( $is_offer_running ) {
				$current_time = gmdate( 'U' );
				$start        = '2026-01-01 00:00 Asia/Dhaka';
				$end          = '2026-02-15 23:59 Asia/Dhaka';
				$notice_start = gmdate( 'U', strtotime( $start ) );
				$notice_end   = gmdate( 'U', strtotime( $end ) );
				if ( $current_time >= $notice_start && $current_time <= $notice_end ) {
					$pro_link_text = esc_html__( 'New Year Offer!', 'product-addons' );
				}
			}
		} 

		if ( ! empty( $pro_link ) ) {
			ob_start();
			?>
				<a href="<?php echo esc_url( $pro_link ); ?>" target="_blank" class="prad-go-pro">
					<svg width="20" height="20" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M2.86 6.553a.5.5 0 01.823-.482l3.02 2.745c.196.178.506.13.64-.098L9.64 4.779a.417.417 0 01.72 0l2.297 3.939a.417.417 0 00.64.098l3.02-2.745a.5.5 0 01.823.482l-1.99 8.63a.833.833 0 01-.813.646H5.663a.833.833 0 01-.812-.646L2.86 6.553z" stroke="currentColor" stroke-width="1.5"></path>
					</svg>
					<span><?php echo esc_html( $pro_link_text ); ?></span>
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
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function handle_external_redirects() {
        if ( empty( $_GET['page'] ) ) {     // @codingStandardsIgnoreLine
			return;
		}
        if ( 'go_prad_pro' === sanitize_text_field( $_GET['page'] ) ) {   // @codingStandardsIgnoreLine
			wp_safe_redirect( 'https://www.wpxpo.com/product/wowaddons/' );
			die();
		}
	}

	/**
	 * Initial Plugin Setting
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public static function tab_page_content() {
		echo wp_kses( '<div id="prad-dashboard-wrap"></div>', apply_filters( 'prad_allowed_html_tags', array() ) );// phpcs:ignore
	}

	/**
	 * Remove All Notification From Menu Page
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public static function remove_all_notices() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash($_GET['page']) ) : ''; // phpcs:ignore
		if ( 'prad-dashboard' === $page ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'in_admin_header' );
		}
	}
}
