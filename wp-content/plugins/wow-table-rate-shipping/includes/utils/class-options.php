<?php //phpcs:ignore
/**
 * Options Action.
 *
 * @package WTRS\Options
 */

namespace WTRS\Includes\Utils;

use WTRS\Includes\Xpo;

defined( 'ABSPATH' ) || exit;

/**
 * Options class.
 */
class Options {

	/**
	 * Setup class.
	 */
	public function __construct() {
		// Add WooCommerce submenu: Table Rate Shipping.
		add_action( 'admin_menu', array( $this, 'register_wc_submenu' ), 60 );
		// Reorder Woo submenu to appear right after Settings.
		add_action( 'admin_menu', array( $this, 'reorder_wc_submenu' ), 100 );
		add_action( 'in_admin_header', array( $this, 'remove_all_notices' ) );

		add_filter( 'admin_footer_text', array( $this, 'admin_footer_left_callback' ), 255, 1 );
		add_filter( 'update_footer', array( $this, 'admin_footer_right_callback' ), 255, 1 );
	}

	/**
	 * Customizes the footer left text.
	 *
	 * @param string $text footer text.
	 * @return string
	 */
	public function admin_footer_left_callback( $text ) {
		if ( 'wtrs-dashboard' !== sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) ) ) { // phpcs:ignore
			return $text;
		}

		$html  = '<span>';
		$html .= '<span>If you like <span style="color:#0062ff;font-weight:bold;">WowShipping</span>, please leave us a <a class="wtrs-link" href="https://wordpress.org/support/plugin/wow-table-rate-shipping/reviews?rate=5#new-post" target="_blank">★★★★★</a> rating!</span>';
		$html .= '</span>';

		return $html;
	}

	/**
	 * Customizes the footer text.
	 *
	 * @param string $text footer right text.
	 * @return string
	 */
	public function admin_footer_right_callback( $text ) {
		if ( 'wtrs-dashboard' !== sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) ) ) { // phpcs:ignore
			return $text;
		}

		ob_start();
		?>
		<div class="alignright wtrs-admin-footer-right" style="margin-right:50px;display:flex;gap:16px;align-items:center;font-size:13px;">
			<span><?php esc_html_e( 'Follow Us', 'wow-table-rate-shipping' ); ?></span>
			<div class="wtrs-admin-footer-right-socials" style="display:flex;gap:5px;align-items:center;">
				<a href="https://www.facebook.com/wpxpo" target="_blank" style="color:#1877F2;font-weight:bold;">Facebook</a>
				<span>|</span>
				<a href="https://x.com/wpxpoofficial" target="_blank" style="color:#1DA1F2;font-weight:bold;">X (Twitter)</a>
				<span>|</span>
				<a href="https://www.youtube.com/@wpxpo" target="_blank" style="color:#cc181e;font-weight:bold;">YouTube</a>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Add WooCommerce -> Table Rate Shipping submenu linking to the dashboard.
	 *
	 * @return void
	 */
	public function register_wc_submenu() {
		// Keep permission aligned with WooCommerce screens; ignore PHPCS for custom cap.
		$capability = apply_filters( 'wtrs_wc_submenu_capability', 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown

		add_submenu_page(
			'woocommerce',
			__( 'WowShipping', 'wow-table-rate-shipping' ),
			__( 'WowShipping', 'wow-table-rate-shipping' ),
			$capability, // phpcs:ignore WordPress.WP.Capabilities.Unknown
			'wtrs-dashboard',
			array( __CLASS__, 'tab_page_content' )
		);
	}

	/**
	 * Reorder WooCommerce submenu so our item appears just after Settings.
	 *
	 * @return void
	 */
	public function reorder_wc_submenu() {
		global $submenu;
		if ( empty( $submenu['woocommerce'] ) || ! is_array( $submenu['woocommerce'] ) ) {
			return;
		}

		$wc_menu        = $submenu['woocommerce'];
		$settings_index = null;
		$our_index      = null;

		foreach ( $wc_menu as $index => $item ) {
			$slug = isset( $item[2] ) ? (string) $item[2] : '';
			if ( 'wc-settings' === $slug || 'woocommerce_settings' === $slug ) {
				$settings_index = $index;
			}
			if ( 'wtrs-dashboard' === $slug ) {
				$our_index = $index;
			}
		}

		if ( null === $our_index ) {
			return;
		}

		if ( null !== $settings_index ) {
			$our_item = $wc_menu[ $our_index ];
			unset( $wc_menu[ $our_index ] );

			$before = array_slice( $wc_menu, 0, $settings_index + 1, true );
			$after  = array_slice( $wc_menu, $settings_index + 1, null, true );

			$wc_menu                = $before + array( 'wtrs-temp-key' => $our_item ) + $after;
			$submenu['woocommerce'] = array_values( $wc_menu ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Initial Plugin Setting
	 *
	 * @return void
	 */
	public static function tab_page_content() {
		echo wp_kses( '<div id="wtrs-dashboard-wrap"></div>', apply_filters( 'wtrs_get_allowed_html_tags', array() ) );
	}

	/**
	 * Remove All Notification From Menu Page
	 *
	 * @return void
	 */
	public static function remove_all_notices() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash($_GET['page']) ) : ''; // phpcs:ignore
		if ( 'wtrs-dashboard' === $page ) {
			remove_all_actions( 'admin_notices' );
			remove_all_actions( 'all_admin_notices' );
			remove_all_actions( 'in_admin_header' );
		}
	}
}
