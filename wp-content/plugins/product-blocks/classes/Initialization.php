<?php
/**
 * Initialization Action.
 *
 * @package WOPB\Notice
 * @since v.1.1.0
 */
namespace WOPB;

use WOPB\Includes\Durbin\Xpo;
use WOPB\Includes\Notice\Notice;

defined( 'ABSPATH' ) || exit;

/**
 * Initialization class.
 */
class Initialization {

	/**
	 * Setup class.
	 *
	 * @since v.1.1.0
	 */
	public function __construct() {
		$this->compatibility_check();
		$this->requires();
		$this->include_addons(); // Include Addons.

		add_action( 'enqueue_block_editor_assets', array( $this, 'register_scripts_back_callback' ) ); // Only editor
		add_action( 'admin_enqueue_scripts', array( $this, 'register_scripts_option_panel_callback' ) ); // Option Panel
		add_action( 'activated_plugin', array( $this, 'wopb_plugin_activation' ) ); // Plugin Activation Call

		add_filter( 'block_categories_all', array( $this, 'register_category_callback' ), 10, 1 ); // Block Category Register
		register_activation_hook( WOPB_PATH . 'product-blocks.php', array( $this, 'install_hook' ) ); // Initial Activation Call
		add_action( 'wp_footer', array( $this, 'footer_callback' ) ); // Footer Text Added
		add_action( 'wp_enqueue_scripts', array( $this, 're_enqueue_wc_scripts' ), 99999 );

		$this->include_promotions();
	}

	/**
	 * Inlcudes promotional classes.
	 *
	 * @return void
	 */
	public function include_promotions() {
		require_once WOPB_PATH . 'classes/class-wow-addons-promotion.php';
		require_once WOPB_PATH . 'classes/class-wow-revenue-promotion.php';
		new WowAddonsPromotion();
		new WowRevenuePromotion();
	}

	/**
	 * Re-enqueues WooCommerce scripts on the single product page.
	 *
	 * @return void
	 */
	public function re_enqueue_wc_scripts() {
		if ( ! is_product() ) {
			return;
		}

		$register_scripts                      = array();
		$register_scripts['wc-single-product'] = array(
			'src'  => plugins_url( 'assets/js/frontend/single-product.min.js', WC_PLUGIN_FILE ),
			'deps' => array( 'jquery' ),
		);

		if ( current_theme_supports( 'wc-product-gallery-zoom' ) ) {
			$register_scripts['zoom'] = array(
				'src'  => plugins_url( 'assets/js/zoom/jquery.zoom.min.js', WC_PLUGIN_FILE ),
				'deps' => array( 'jquery' ),
			);
		}

		// Enqueue variation scripts.
		$product = wc_get_product();
		if ( $product && $product->is_type( 'variable' ) ) {
			$register_scripts['wc-add-to-cart-variation'] = array(
				'src'  => plugins_url( 'assets/js/frontend/add-to-cart-variation.min.js', WC_PLUGIN_FILE ),
				'deps' => array( 'jquery', 'wp-util', 'jquery-blockui' ),
			);
		}

		foreach ( $register_scripts as $name => $props ) {
			wp_enqueue_script( $name, $props['src'], $props['deps'], WC_VERSION, true );
		}
	}

	/**
	 * Redirect after plugin activation
	 *
	 * @since v.4.0.0
	 * @return NULL
	 */
	public function wopb_plugin_activation( $plugin ) {
		if ( wp_doing_ajax() ) {
			return;
		}
		if ( $plugin == 'product-blocks/product-blocks.php' ) {
			if ( wp_doing_ajax() || is_network_admin() || isset( $_GET['activate-multi'] ) ) { //phpcs:ignore
				return;
			}
			if ( get_option( 'wopb_setup_wizard_data', '' ) != 'yes' ) {
				update_option( 'wopb_setup_wizard_data', 'yes' );
                exit( wp_safe_redirect( admin_url( 'admin.php?page=wopb-initial-setup-wizard' ) ) ); //phpcs:ignore
			} else {
                exit( wp_safe_redirect( admin_url( 'admin.php?page=wopb-settings#home' ) ) ); //phpcs:ignore
			}
		}
	}

	/**
	 * Include Addons Main File
	 *
	 * @since v.1.1.0
	 * @return NULL
	 */
	public function include_addons() {
		$is_admin   = is_admin();
		$addons_dir = array_filter( glob( WOPB_PATH . 'addons/*' ), 'is_dir' );
		$wc_ready   = wopb_function()->get_setting( 'is_wc_ready' );
		foreach ( $addons_dir as $key => $value ) {
			$addon_dir_name = str_replace( dirname( $value ) . '/', '', $value );
			$file_name      = WOPB_PATH . 'addons/' . $addon_dir_name;
			if ( $is_admin && wopb_function()->get_screen() == 'wopb-settings' ) { // Only include in settings page
				if ( file_exists( $file_name . '/backend.php' ) ) {
					include_once $file_name . '/backend.php';
				}
			} elseif ( $wc_ready && file_exists( $file_name . '/frontend.php' ) ) {
				// include if is not in settings
					include_once $file_name . '/frontend.php';
			}
		}
	}

	/**
	 * Footer Callback
	 *
	 * @since v.4.0.6
	 * @return NULL
	 */
	public function footer_callback() {
		$html  = '';
		$html .= '<div class="wopb-footer-section">';
			ob_start();
				$this->modal_callback();
				do_action( 'wopb_footer' );
			$html .= ob_get_clean();

		$html        .= '</div>';
		$content_safe = wopb_function()->wp_kses_safe( $html );
        echo $content_safe; // phpcs:ignore
	}

	/**
	 * Footer Modal Callback
	 *
	 * @since v.1.1.0
	 * @return NULL
	 */
	public function modal_callback() {
		if ( $modal_loaders = apply_filters( 'wopb_active_modal', array() ) ) { ?>
			<div class="wopb-modal-wrap">
				<div class="wopb-modal-overlay"></div>
				<div class="wopb-modal-content"></div>
				<div class="wopb-modal-loading">
					<div class="wopb-loading">
						<?php
						foreach ( $modal_loaders as $loader ) {
							echo '<span class="wopb-loader wopb-d-none ' . esc_attr( $loader ) . '">';
							switch ( $loader ) {
								case 'loader_2':
								case 'loader_3':
									for ( $i = 0; $i <= 11; $i++ ) {
										echo '<div></div>';
									}
									break;
								case 'loader_4':
									?>
											<span class="dot_line"></span>
											<svg width="100" height="100" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M6 7V5.5C6 4.67157 6.67157 4 7.5 4H16.5C17.3284 4 18 4.67157 18 5.5V8.15071C18 8.67761 17.7236 9.16587 17.2717 9.43695L13.7146 11.5713C13.3909 11.7655 13.3909 12.2345 13.7146 12.4287L17.2717 14.563C17.7236 14.8341 18 15.3224 18 15.8493V18.5C18 19.3284 17.3284 20 16.5 20H7.5C6.67157 20 6 19.3284 6 18.5V15.8493C6 15.3224 6.27645 14.8341 6.72826 14.563L10.2854 12.4287C10.6091 12.2345 10.6091 11.7655 10.2854 11.5713L6.72826 9.43695C6.27645 9.16587 6 8.67761 6 8.15071V8" stroke="white" />
											</svg>
									<?php
									break;
								case 'loader_5':
									for ( $i = 0;$i <= 14;$i++ ) {
										echo '<div style="--index:' . esc_attr( $i ) . '"></div>';
									}
									break;
								default:
									break;
							}
								echo '</span>';
						}
						?>
					</div>
				</div>
				<div class="wopb-after-modal-content"></div>
			</div>
			<?php
		}
	}

	/**
	 * Option Panel Enqueue Script
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function register_scripts_option_panel_callback( $screen ) {
		global $post;
		$is_active   = wopb_function()->is_lc_active();
		$post_id     = isset( $post->ID ) ? $post->ID : '';
		$post_type   = isset( $post->post_type ) ? $post->post_type : '';
		$_page       = wopb_function()->get_screen();
		$license_key = get_option( 'edd_wopb_license_key' );

		// Custom Font Support Added
		$font_settings = wopb_function()->get_setting( 'wopb_custom_font' );
		$custom_fonts  = array();
		if ( $font_settings == 'true' ) {
			$args  = array(
				'post_type'   => 'wopb_custom_font',
				'post_status' => 'publish',
				'numberposts' => 10,
				'order'       => 'ASC',
			);
			$posts = get_posts( $args );
			if ( $posts ) {
				foreach ( $posts as $post_data ) {
					setup_postdata( $post );
					$font = get_post_meta( $post_data->ID, '__font_settings', true );
					if ( $font ) {
						$custom_fonts[ $post_data->post_title ] = $font;
					}
				}
				wp_reset_postdata();
			}
		}

		wp_enqueue_media();

		$taxonomy                               = isset( $_GET['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$variation_swatches_attribute_poststype = wopb_function()->get_setting( 'wopb_variation_swatches' ) == 'true' && strpos( $taxonomy, 'pa_' ) == 0;
		if ( $taxonomy == 'pa_color' || $variation_swatches_attribute_poststype ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
		}

		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_script( 'wopb-option-script', WOPB_URL . 'assets/js/wopb-option.js', array( 'jquery' ), WOPB_VER, true );
		wp_enqueue_style( 'wopb-option-style', WOPB_URL . 'assets/css/wopb-option.css', array(), WOPB_VER );
		wp_localize_script(
			'wopb-option-script',
			'wopb_option',
			array(
				'url'                => WOPB_URL,
				'version'            => WOPB_VER,
				'active'             => $is_active,
				'width'              => wopb_function()->get_setting( 'editor_container' ),
				'security'           => wp_create_nonce( 'wopb-nonce' ),
				'ajax'               => admin_url( 'admin-ajax.php' ),
				'settings'           => wopb_function()->get_setting(),
				'post_type'          => $post_type,
				'saved_template_url' => admin_url( 'admin.php?page=wopb-settings#saved-templates' ),
				'custom_fonts'       => $custom_fonts,
				'revenue_active'     => is_plugin_active( 'revenue/revenue.php' ),
				'revenue_url'        => admin_url( 'admin.php?page=revenue' ),
				'revenue_campaigns'  => esc_url( admin_url( 'admin.php?page=revenue#/campaigns' ) ),
			)
		);

		if ( $_page == 'wopb-settings' || $post_type == 'wopb_builder' ) { // Conditions JS
			wp_enqueue_script( 'wopb-conditions-script', WOPB_URL . 'addons/builder/assets/js/conditions.min.js', array(), WOPB_VER, true );
			wp_localize_script(
				'wopb-conditions-script',
				'wopb_condition',
				array(
					'url'          => WOPB_URL,
					'active'       => $is_active,
					'license'      => $is_active ? $license_key : '',
					'builder_url'  => admin_url( 'admin.php?page=wopb-settings#builder' ),
					'builder_type' => $post_id ? get_post_meta( $post_id, '_wopb_builder_type', true ) : '',
				)
			);
		}

		/* === Dashboard === */
		if ( $_page == 'wopb-settings' ) {
			$user_info = get_userdata( get_current_user_id() );
			wp_enqueue_script( 'wopb-dashboard-script', WOPB_URL . 'assets/js/wopb_dashboard_min.js', array( 'wp-i18n', 'wp-api-fetch', 'wp-api-request', 'wp-components', 'wp-blocks' ), WOPB_VER, true );
			wp_localize_script(
				'wopb-dashboard-script',
				'wopb_dashboard_pannel',
				array_merge(
					array(
						'url'               => WOPB_URL,
						'ajax'              => admin_url( 'admin-ajax.php' ),
						'security'          => wp_create_nonce( 'wopb-nonce' ),
						'active'            => $is_active,
						'license'           => Xpo::get_lc_key(),
						'settings'          => wopb_function()->get_setting(),
						'addons'            => apply_filters( 'wopb_addons_config', array() ),
						'addons_settings'   => apply_filters( 'wopb_settings', array() ),
						'version'           => WOPB_VER,
						'setup_wizard_link' => admin_url( 'admin.php?page=wopb-initial-setup-wizard' ),
						'helloBar'          => Notice::get_hellobar_config(),
						'userInfo'          => array(
							'name'  => $user_info->first_name ? $user_info->first_name . ( $user_info->last_name ? ' ' . $user_info->last_name : '' ) : $user_info->user_login,
							'email' => $user_info->user_email,
						),
					),
					Xpo::get_wow_products_details()
				)
			);
			wp_set_script_translations( 'wopb-dashboard-script', 'product-blocks', WOPB_PATH . 'languages/' );
		}
	}

	/**
	 * Only Backend Enqueue Scripts
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function register_scripts_back_callback() {
		global $post;
		wopb_function()->register_scripts_common();
		if ( wopb_function()->get_setting( 'is_wc_ready' ) ) {
			global $pagenow;
			$is_active  = wopb_function()->get_setting( 'is_lc_active' );
			$is_builder = ( isset( $post->post_type ) && $post->post_type == 'wopb_builder' ) ? true : false;
			if ( $pagenow != 'widgets.php' ) {
				wp_enqueue_script( 'wp-editor' );
			}
			wp_enqueue_script( 'wopb-blocks-editor-script', WOPB_URL . 'assets/js/editor.blocks.js', array( 'wp-i18n', 'wp-element', 'wp-blocks', 'wp-components' ), WOPB_VER, true );
			wp_enqueue_style( 'wopb-blocks-editor-css', WOPB_URL . 'assets/css/blocks.editor.css', array(), WOPB_VER );

			wp_localize_script(
				'wopb-blocks-editor-script',
				'wopb_data',
				array(
					'url'                     => WOPB_URL,
					'security'                => wp_create_nonce( 'wopb-nonce' ),
					'hide_import_btn'         => wopb_function()->get_setting( 'hide_import_btn' ),
					'premium_link'            => wopb_function()->get_premium_link(),
					'license'                 => $is_active ? get_option( 'edd_wopb_license_key' ) : '',
					'active'                  => $is_active,
					'isBuilder'               => $is_builder,
					'isVariationSwitchActive' => wopb_function()->get_setting( 'wopb_variation_swatches' ),
					'settings'                => wopb_function()->get_setting(),
					// 'productTaxonomyList'     => has_block( 'product-blocks/filter' ) ? wopb_function()->get_product_taxonomies( array( 'term_limit' => 3 ) ) : array(),
					'product_category'        => get_terms(
						array(
							'taxonomy'   => 'product_cat',
							'hide_empty' => true,
							'number'     => 10,
						)
					),
					'builder_type'            => $is_builder ? get_post_meta( $post->ID, '_wopb_builder_type', true ) : '',
					'taxonomyCatUrl'          => admin_url( 'edit-tags.php?taxonomy=category' ),
				)
			);

			wp_set_script_translations( 'wopb-blocks-editor-script', 'product-blocks', WOPB_PATH . 'languages/' );
		}
	}

	/**
	 * Fire When Plugin First Installs
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function install_hook() {
		$data      = get_option( 'wopb_options', array() );
		$init_data = array(
			'preloader_style'          => 'style1',
			'preloader_color'          => '#FF176B',
			'container_width'          => '1140',
			'hide_import_btn'          => '',
			'editor_container'         => 'theme_default',
			'wopb_builder'             => 'true',
			'wopb_compare'             => 'true',
			'wopb_flipimage'           => 'true',
			'wopb_quickview'           => 'true',
			'wopb_wishlist'            => 'true',
			'wopb_product_video'       => 'true',
			'save_version'             => wp_rand( 1, 1000 ),
			'disable_google_font'      => '',
			'wopb_custom_font'         => 'true',
			'wprocket_cache_exclusion' => 'false',
		);

		if ( empty( $data ) ) {
			update_option( 'wopb_options', $init_data );
			$GLOBALS['wopb_settings'] = $init_data;
		} else {
			foreach ( $init_data as $key => $single ) {
				if ( ! isset( $data[ $key ] ) ) {
					$data[ $key ] = $single;
				}
			}
			update_option( 'wopb_options', $data );
			$GLOBALS['wopb_settings'] = $data;
		}

		if ( ! get_option( 'wopb_activation' ) ) {
			update_option( 'wopb_activation', gmdate( 'U' ) ); // Set Activation Time
		}

		// Set Metabox Position in Product Edit
		wopb_function()->builder_metabox_position();

		// Delete Dismiss Options WC install
		delete_option( 'dismiss_notice' );
	}

	/**
	 * Compatibility Check Require
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function compatibility_check() {
		require_once WOPB_PATH . 'classes/Compatibility.php';
		new \WOPB\Compatibility();
	}

	/**
	 * Require Necessary Libraries
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function requires() {
		require_once WOPB_PATH . 'classes/Dashboard.php';
		require_once WOPB_PATH . 'classes/Options.php';
		require_once WOPB_PATH . 'classes/ProPlugins.php';
		require_once WOPB_PATH . 'classes/SetupWizard.php';
		new \WOPB\Dashboard();
		new \WOPB\Options();
		new \WOPB\ProPlugins();
		new \WOPB\SetupWizard();

		require_once WOPB_PATH . 'includes/durbin/class-deactive.php';
		require_once WOPB_PATH . 'includes/durbin/class-durbin-client.php';
		require_once WOPB_PATH . 'includes/durbin/class-xpo.php';
		require_once WOPB_PATH . 'includes/notice/class-notice.php';
		require_once WOPB_PATH . 'includes/durbin/class-our-plugins.php';
		new \WOPB\Includes\Durbin\Deactive();
		new \WOPB\Includes\Notice\Notice();
		new \WOPB\Includes\Durbin\OurPlugins();

		if ( wopb_function()->get_setting( 'is_wc_ready' ) ) {
			require_once WOPB_PATH . 'classes/REST_API.php';
			require_once WOPB_PATH . 'classes/Blocks.php';
			require_once WOPB_PATH . 'classes/Styles.php';
			require_once WOPB_PATH . 'classes/Caches.php';
			require_once WOPB_PATH . 'classes/WooHooks.php';
			require_once WOPB_PATH . 'classes/ExternalCompatibility.php';
			require_once WOPB_PATH . 'classes/compatibility/GermanizedSupport.php';
			new \WOPB\REST_API();
			new \WOPB\Styles();
			new \WOPB\Blocks();
			new \WOPB\Caches();
			new \WOPB\WooHooks();
			new \WOPB\ExternalCompatibility();
			new \WOPB\Compatibility\GermanizedSupport();
		}
	}

	/**
	 * Block Categories Initialization
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function register_category_callback( $categories ) {
		$attr = array(
			array(
				'slug'  => 'product-blocks',
				'title' => __( 'WooCommerce Blocks (WowStore)', 'product-blocks' ),
			),
			array(
				'slug'  => 'single-product',
				'title' => __( 'Single Product (WowStore)', 'product-blocks' ),
			),
			array(
				'slug'  => 'single-cart',
				'title' => __( 'Cart (WowStore)', 'product-blocks' ),
			),
			array(
				'slug'  => 'single-checkout',
				'title' => __( 'Checkout (WowStore)', 'product-blocks' ),
			),
			array(
				'slug'  => 'thank-you',
				'title' => __( 'Thank You (WowStore)', 'product-blocks' ),
			),
			array(
				'slug'  => 'my-account',
				'title' => __( 'My Account (WowStore)', 'product-blocks' ),
			),
		);
		return array_merge( $attr, $categories );
	}
}
