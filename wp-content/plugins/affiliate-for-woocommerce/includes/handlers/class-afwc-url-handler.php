<?php
/**
 * Handles URL routing and redirection.
 *
 * @package    affiliate-for-woocommerce/includes/handlers
 * @since      8.43.0
 * @version    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_URL_Handler' ) ) {

	/**
	 * Class to manage routing, URL building, and redirection.
	 */
	class AFWC_URL_Handler {

		/**
		 * Base URL for Affiliate dashboard on Admin side.
		 *
		 * @var string
		 */
		protected static $affiliate_admin_base_url = 'admin.php?page=affiliate-for-woocommerce';

		/**
		 * Singleton instance.
		 *
		 * @var AFWC_URL_Handler|null
		 */
		private static $instance = null;

		/**
		 * Get the singleton instance.
		 *
		 * @return AFWC_URL_Handler
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
			add_action( 'admin_init', array( $this, 'handle_admin_affiliate_dashboard_redirection' ) );
		}

		/**
		 * Redirects from clean URL (with `path`) to SPA hash-based URL.
		 *
		 * @return void
		 */
		public function handle_admin_affiliate_dashboard_redirection() {
			if ( afwc_is_wp_doing_ajax() || ! afwc_current_user_can_manage_affiliate() ) {
				return;
			}

			$page = ! empty( $_GET['page'] ) ? wc_clean( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( 'affiliate-for-woocommerce' !== $page ) {
				return;
			}

			$path = ! empty( $_GET['path'] ) ? wc_clean( wp_unslash( $_GET['path'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( empty( $path ) ) {
				return;
			}

			wp_safe_redirect( self::get_admin_dashboard_spa_url( $path ) );
			exit;
		}

		/**
		 * Returns clean URL for Admin Dashboard.
		 *
		 * @param string $path Optional SPA path to append.
		 *
		 * @return string Clean URL.
		 */
		public static function get_admin_dashboard_clean_url( $path = '' ) {
			return self::get_clean_url( admin_url( self::$affiliate_admin_base_url ), $path );
		}

		/**
		 * Returns SPA URL for Admin Dashboard.
		 *
		 * @param string $path Optional SPA path to append.
		 *
		 * @return string SPA URL.
		 */
		public static function get_admin_dashboard_spa_url( $path = '' ) {
			return self::get_spa_url( admin_url( self::$affiliate_admin_base_url ), $path );
		}

		/**
		 * Returns a clean URL for a given base and path.
		 *
		 * @param string $base_url Base page URL.
		 * @param string $path Optional SPA path to append.
		 *
		 * @return string Clean URL.
		 */
		public static function get_clean_url( $base_url = '', $path = '' ) {
			if ( empty( $base_url ) ) {
				return '';
			}

			return esc_url(
				empty( $path )
				? $base_url
				: add_query_arg( 'path', $path, $base_url )
			);
		}

		/**
		 * Returns SPA URL for a given base and path.
		 *
		 * @param string $base_url Base page URL.
		 * @param string $path Optional SPA path to append.
		 *
		 * @return string SPA URL.
		 */
		public static function get_spa_url( $base_url = '', $path = '' ) {
			if ( empty( $base_url ) ) {
				return '';
			}

			return esc_url(
				empty( $path )
				? $base_url
				: ( $base_url . '#!/' . ltrim( wc_clean( $path ), '/' ) )
			);
		}
	}
}

AFWC_URL_Handler::get_instance();
