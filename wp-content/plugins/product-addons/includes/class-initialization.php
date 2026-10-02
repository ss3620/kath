<?php // phpcs:ignore
/**
 * Initialization Action.
 *
 * @package PRAD
 * @since 1.0.0
 */
namespace PRAD\Includes;

use PRAD\Includes\Admin\Options;
use PRAD\Includes\Admin\Product\ProductEdit;
use PRAD\Includes\Common\Hooks;
use PRAD\Includes\Compatibility\Compatibility;
use PRAD\Includes\Compatibility\ShopCompatibilty;
use PRAD\Includes\Order\CartPage;
use PRAD\Includes\Order\CheckoutPage;
use PRAD\Includes\Restapi\RequestApi;

use PRAD\Includes\Cron\Cleanup;

defined( 'ABSPATH' ) || exit;

/**
 * Initialization class.
 */
class Initialization {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		$this->requires();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts_callback' ) );
	}

	/**
	 * Necessary Requires Class
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function requires() {
		new PostType();

		new Options();

		new ProductEdit();

		new CartPage();
		new CheckoutPage();

		new Hooks();
		new RequestApi();
		new Compatibility();
		new ShopCompatibilty();

		new Cleanup();
	}


	/**
	 * Only Backend CSS and JS Scripts
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function admin_scripts_callback() {
		global $pagenow;
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing parameter.

		// Styles only this plugin's own admin menu item (#toplevel_page_prad-dashboard),
		// which is on every admin screen.
		product_addons()->enqueue_style( 'prad-admin-style', 'prad-admin' );

		if ( 'admin.php' === $pagenow ) {
			if ( 'prad-dashboard' === $page ) {
				// Keeps the menu's current item in step with the dashboard's pages.
				product_addons()->enqueue_script( 'prad-admin-script', 'prad-admin' );

				// Styles.
				product_addons()->enqueue_style( 'prad-editor-css', 'wowaddons' );
				product_addons()->enqueue_style( 'prad-editor-css2', 'style-wowaddons' );
				product_addons()->enqueue_style( 'prad-block-css', 'wowaddons-blocks' );

				// Scripts.
				product_addons()->enqueue_script( 'prad-editor-script', 'wowaddons' );
				product_addons()->enqueue_script( 'prad-date-script', 'wowdate-min' );

				wp_enqueue_media();
				wp_localize_script(
					'prad-editor-script',
					'pradBackendData',
					array_merge(
						apply_filters(
							'prad_backend_data',
							array(
								'version'         => PRAD_VER,
								'uploadFileTypes' => array(
									'png'  => 'image/png',
									'jpg'  => 'image/jpeg',
									'jpeg' => 'image/jpeg',
								),
							)
						),
						array(
							'url'            => PRAD_URL,
							'db_url'         => admin_url( 'admin.php?page=prad-dashboard#' ),
							'ajax'           => admin_url( 'admin-ajax.php' ),
							'nonce'          => wp_create_nonce( 'prad-nonce' ),
							'decimal_sep'    => get_option( 'woocommerce_price_decimal_sep', '.' ),
							'num_decimals'   => get_option( 'woocommerce_price_num_decimals', '2' ),
							'currency_pos'   => get_option( 'woocommerce_currency_pos', 'left' ),
							'currencySymbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
							'characterText'  => Xpo::get_prad_settings_item( 'characterText', 'Character' ),
							'isFreshInstall' => self::is_fresh_install(),
							'pradAttributes' => product_addons()->prad_get_attributes(),
							'date_format'    => get_option( 'date_format' ),
							/**
							 * Whether the dashboard shows "Upgrade to Pro" prompts.
							 *
							 * @since 1.8.3
							 *
							 * @param bool $show Default true.
							 */
							'showUpsell'     => (bool) apply_filters( 'prad_show_upsell', true ),
						)
					)
				);
				wp_set_script_translations( 'prad-editor-script', 'product-addons', PRAD_PATH . 'languages/' );

				/**
				 * Fires after the builder script is enqueued, so extensions can attach to it.
				 *
				 * @since 1.8.3
				 */
				do_action( 'prad_enqueue_editor_js' );
			}
		}

		if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) && 'product' === get_post_type() ) {
			product_addons()->enqueue_style( 'prad-product-edit-style', 'style-prad-product-edit' );
			product_addons()->enqueue_script( 'prad-product-edit-script', 'prad-product-edit' );

			wp_localize_script(
				'prad-product-edit-script',
				'pradBackendData',
				array(
					'url'        => PRAD_URL,
					'db_url'     => admin_url( 'admin.php?page=prad-dashboard#' ),
					'ajax'       => admin_url( 'admin-ajax.php' ),
					'version'    => PRAD_VER,
					'nonce'      => wp_create_nonce( 'prad-nonce' ),
					'product_id' => get_the_ID(),
				)
			);
		}
	}

	/**
	 * Check if the user has never created an option list.
	 *
	 * Used to show the onboarding get-started screen and trimmed
	 * header on the dashboard for brand-new users.
	 *
	 * @since v.1.6.17
	 *
	 * @return bool
	 */
	public static function is_fresh_install() {
		if ( 'yes' === get_option( 'prad_first_option_created', '' ) ) {
			return false;
		}

		$existing = get_posts(
			array(
				'post_type'      => 'prad_option',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			// Existing users from before the flag existed: backfill it.
			update_option( 'prad_first_option_created', 'yes' );
			return false;
		}

		return true;
	}
}
