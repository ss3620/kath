<?php
/**
 * External Plugin Compatibility Handler
 *
 * Handles compatibility for third-party plugins (CSS, JS, Hooks, REST API, etc.)
 *
 * @package WOPB\ExternalCompatibility
 * @since v.1.0.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * Manages compatibility with external plugins and themes
 */
class ExternalCompatibility {

	/**
	 * Supported post types for compatibility
	 *
	 * @var array
	 * @since v.1.0.0
	 */
	private $supported_post_types = array( 'wopb_builder' );

	/**
	 * Setup class
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		$this->init_wopb_spectra_compatibility();
		$this->init_wopb_wp_rocket_css_exclusion();
		$this->init_wopb_wp_rocket_js_exclusion();
		$this->init_wopb_wp_rocket_style_compatibility();
		add_action( 'wopb_save_settings', array( $this, 'purge_wp_rocket_cache_on_settings_save' ) );
		add_action( 'admin_init', array( $this, 'purge_wp_rocket_cache_on_compatibility_change' ) );
	}

	// ========================================
	// SPECTRA (Ultimate Addons for Gutenberg)
	// ========================================
	/**
	 * Initialize Spectra compatibility hooks
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	private function init_wopb_spectra_compatibility() {

		add_action( 'save_post', array( $this, 'wopb_spectra_generate_css' ), 99, 1 );
		add_action( 'after_delete_post', array( $this, 'wopb_spectra_delete_css' ), 10, 2 );
		add_action( 'wopb_enqueue_plugin_css', array( $this, 'wopb_spectra_enqueue_css' ), 10, 1 );
	}

	/**
	 * Enqueue Spectra CSS for WOPB Builder post type
	 *
	 * @since v.1.0.0
	 * @param int $post_id
	 * @return void
	 */
	public function wopb_spectra_enqueue_css( $post_id ) {

		if ( ! class_exists( 'UAGB_Helper' ) ) {
			return;
		}

		if ( ! $this->is_supported_post_type( $post_id ) ) {
			return;
		}

		$upload_dir    = wp_upload_dir();
		$css_file_url  = $upload_dir['baseurl'] . '/uag-plugin/assets/0/uag-css-' . $post_id . '.css';
		$css_file_path = $upload_dir['basedir'] . '/uag-plugin/assets/0/uag-css-' . $post_id . '.css';

		if ( file_exists( $css_file_path ) ) {
			wp_enqueue_style(
				'uag-page-css-' . $post_id,
				$css_file_url,
				array(),
				filemtime( $css_file_path )
			);
		}
	}

	/**
	 * Generate Spectra CSS on post save
	 *
	 * @since v.1.0.0
	 * @param int $post_id
	 * @return void
	 */
	public function wopb_spectra_generate_css( $post_id ) {
		// Avoid autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! class_exists( 'UAGB_Helper' ) ) {
			return;
		}

		if ( ! $this->is_supported_post_type( $post_id ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return;
		}

		// Parse blocks
		$blocks = parse_blocks( $post->post_content );

		// Check if there are any Spectra blocks
		if ( ! $this->has_spectra_blocks( $blocks ) ) {
			return;
		}

		// Generate CSS assets
		try {
			$uagb_helper = new \UAGB_Helper();
			if ( method_exists( $uagb_helper, 'get_assets' ) ) {
				$assets = $uagb_helper->get_assets( $blocks );

				if ( isset( $assets['css'] ) && ! empty( $assets['css'] ) ) {
					$upload_dir = wp_upload_dir();
					$dir        = $upload_dir['basedir'] . '/uag-plugin/assets/0/';

					if ( ! file_exists( $dir ) ) {
						wp_mkdir_p( $dir );
					}

					$css_file_path = $dir . 'uag-css-' . $post_id . '.css';
					file_put_contents( $css_file_path, $assets['css'] );
					// error_log( 'Spectra CSS generated: ' . $css_file_path );
				}
			}
		} catch ( \Exception $e ) {
			error_log( 'Spectra CSS generation error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- genuine error-path logging, not debug code.
		}
	}

	/**
	 * Delete Spectra CSS file
	 *
	 * @since v.1.0.0
	 * @param int    $post_id
	 * @param object $post
	 * @return void
	 */
	public function wopb_spectra_delete_css( $post_id, $post ) {
		if ( ! class_exists( 'UAGB_Helper' ) ) {
			return;
		}
		// Get post type from $post object instead of get_post_type()
		$post_type = '';
		if ( $post && isset( $post->post_type ) ) {
			$post_type = $post->post_type;
		} else {
			$post_type = get_post_type( $post_id );
		}

		// Check if it's a wopb_builder post type
		if ( $post_type == 'wopb_builder' && class_exists( 'UAGB_Helper' ) ) {
			$upload_dir       = wp_upload_dir();
			$spectra_css_path = $upload_dir['basedir'] . '/uag-plugin/assets/0/uag-css-' . $post_id . '.css';

			if ( file_exists( $spectra_css_path ) ) {
				wp_delete_file( $spectra_css_path );
				// error_log( 'Deleted Spectra CSS file: ' . $spectra_css_path . ' for post ID: ' . $post_id );
			} else {
				error_log( 'Spectra CSS file does not exist: ' . $spectra_css_path ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- genuine error-path logging, not debug code.
			}
		}
	}

	/**
	 * Check if blocks contain Spectra blocks
	 *
	 * @since v.1.0.0
	 * @param array $blocks
	 * @return bool
	 */
	private function has_spectra_blocks( $blocks ) {
		foreach ( $blocks as $block ) {
			if ( isset( $block['blockName'] ) && strpos( $block['blockName'], 'uagb/' ) === 0 ) {
				return true;
			}
			if ( ! empty( $block['innerBlocks'] ) && $this->has_spectra_blocks( $block['innerBlocks'] ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if post type is supported
	 *
	 * @since v.1.0.0
	 * @param int $post_id
	 * @return bool
	 */
	private function is_supported_post_type( $post_id ) {
		$post_type = get_post_type( $post_id );
		return in_array( $post_type, $this->supported_post_types, true );
	}

	/**
	 * Check if WP Rocket is active.
	 *
	 * @since v.1.0.0
	 * @return bool
	 */
	private function is_wp_rocket_active() {
		if ( defined( 'WP_ROCKET_VERSION' ) || function_exists( 'rocket_clean_domain' ) ) {
			return true;
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( 'wp-rocket/wp-rocket.php' );
	}

	/**
	 * WP Rocket CSS exclusion for WOPB
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function init_wopb_wp_rocket_css_exclusion() {
		$wprocket_cache_exclusion = wopb_function()->get_setting( 'wprocket_cache_exclusion' );

		if ( 'yes' !== $wprocket_cache_exclusion ) {
			return;
		}

		if ( ! $this->is_wp_rocket_active() ) {
			return;
		}

		add_filter(
			'rocket_rucss_excluded_selectors',
			function ( $selectors ) {
				$selectors[] = '.wopb-product';
				$selectors[] = '.productx-global-style';
				$selectors[] = '.wopb-(.*)';
				$selectors[] = '[class^="wopb-"]';
				$selectors[] = '.wp-block-navigation(.*)';
				$selectors[] = '[class^="wp-block-navigation"]';
				return $selectors;
			}
		);

		add_filter(
			'rocket_rucss_safelist',
			function ( $selectors ) {
				$selectors[] = '.wopb-product';
				$selectors[] = '.productx-global-style';
				$selectors[] = '.wopb-(.*)';
				$selectors[] = '[class^="wopb-"]';
				$selectors[] = '.wp-block-navigation(.*)';
				$selectors[] = '[class^="wp-block-navigation"]';
				$selectors[] = '[class*=" wp-block-navigation"]';
				$selectors[] = '.wp-block-navigation__responsive-container';
				$selectors[] = '.wp-block-navigation__responsive-container.is-menu-open';
				$selectors[] = '.wp-block-navigation__responsive-close';
				$selectors[] = '.wp-block-navigation__responsive-dialog';
				$selectors[] = '.wp-block-navigation__responsive-container-content';
				$selectors[] = '.wp-block-navigation__responsive-container-open';
				$selectors[] = '.wp-block-navigation__responsive-container-close';
				$selectors[] = '.wp-block-navigation__overlay-container';
				$selectors[] = '.has-modal-open';
				$selectors[] = 'html.has-modal-open';
				$selectors[] = '.disable-default-overlay';

				return $selectors;
			}
		);

		// Exclude inline styles from Remove Unused CSS
		add_filter(
			'rocket_rucss_inline_content_exclusions',
			function ( $excluded_inline ) {
				$excluded_inline[] = 'wopb-post-(.*?)';
				$excluded_inline[] = 'productx-global-style';
				$excluded_inline[] = 'wopb-(.*)';
				$excluded_inline[] = 'wp-block-navigation__responsive-container';
				$excluded_inline[] = 'wp-block-navigation';
				return $excluded_inline;
			}
		);

		// Exclude CSS files from optimization
		add_filter(
			'rocket_exclude_css',
			function ( $excluded_files ) {
				$excluded_files[] = '/product-blocks/wopb-css-(.*?)\.css';
				$excluded_files[] = '/wp-content/plugins/product-blocks/(.*?)\.css';
				$excluded_files[] = 'product-blocks/assets/css/.*\.css';
				return $excluded_files;
			}
		);
	}

	/**
	 * WP Rocket JS exclusion for WOPB
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function init_wopb_wp_rocket_js_exclusion() {
		$wprocket_cache_exclusion = wopb_function()->get_setting( 'wprocket_cache_exclusion' );

		if ( 'yes' !== $wprocket_cache_exclusion ) {
			return;
		}

		if ( ! $this->is_wp_rocket_active() ) {
			return;
		}

		$js_exclusions = function ( $excluded_files ) {
			$excluded_files[] = 'wopb-slick-script';
			$excluded_files[] = 'wopb-slick-script-js';
			$excluded_files[] = 'wopb-script';
			$excluded_files[] = 'wopb-script-js';
			$excluded_files[] = 'wopb-filter-script';
			$excluded_files[] = 'wopb-filter-script-js';
			$excluded_files[] = 'product-blocks/assets/js/slick.min.js';
			$excluded_files[] = 'product-blocks/assets/js/wopb.js';
			$excluded_files[] = 'product-blocks/assets/js/filter.js';
			$excluded_files[] = '/wp-content/plugins/product-blocks/assets/js/slick.min.js';
			$excluded_files[] = '/wp-content/plugins/product-blocks/assets/js/wopb.js';
			$excluded_files[] = '/wp-content/plugins/product-blocks/assets/js/filter.js';
			$excluded_files[] = '/wp-content/plugins/product-blocks/(.*)\.js';
			$excluded_files[] = '/wp-content/plugins/product-blocks-pro/(.*)\.js';
			$excluded_files[] = 'product-blocks/assets/js/.*\.js';
			$excluded_files[] = 'product-blocks-pro/(.*)\.js';

			return array_unique( $excluded_files );
		};

		$wp_script_module_exclusions = function ( $excluded_files ) use ( $js_exclusions ) {
			$excluded_files = $js_exclusions( $excluded_files );

			$excluded_files[] = '/wp-includes/js/jquery/jquery.min.js';
			$excluded_files[] = '/wp-includes/js/jquery/jquery.js';
			$excluded_files[] = '/wp-includes/js/jquery/jquery-migrate.min.js';
			$excluded_files[] = '/wp-includes/js/jquery/jquery-migrate.js';
			$excluded_files[] = '/jquery-?[0-9.]*(.min|.slim|.slim.min)?.js';
			$excluded_files[] = '/jquery-migrate(.min)?.js';
			$excluded_files[] = '/wp-includes/js/dist/script-modules/(.*)\.js';
			$excluded_files[] = '@wordpress/(.*)-js-module';

			return array_unique( $excluded_files );
		};

		add_filter( 'rocket_exclude_js', $js_exclusions );
		add_filter( 'rocket_exclude_defer_js', $wp_script_module_exclusions );
		add_filter( 'rocket_delay_js_exclusions', $wp_script_module_exclusions );
		add_filter(
			'rocket_cdn_reject_files',
			function ( $excluded_files ) {
				$excluded_files[] = '/wp-includes/js/dist/script-modules/(.*)\.js';

				return $excluded_files;
			}
		);
	}

	/**
	 * WP Rocket style compatibility for WordPress blocks inside WOPB templates
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function init_wopb_wp_rocket_style_compatibility() {
		$wprocket_cache_exclusion = wopb_function()->get_setting( 'wprocket_cache_exclusion' );

		if ( 'yes' !== $wprocket_cache_exclusion ) {
			return;
		}

		if ( ! $this->is_wp_rocket_active() ) {
			return;
		}

		add_action(
			'init',
			function () {
				wp_enqueue_block_style(
					'core/navigation',
					array(
						'handle' => 'wopb-core-navigation-compat',
						'src'    => WOPB_URL . 'assets/css/core-navigation-compat.css',
						'path'   => WOPB_PATH . 'assets/css/core-navigation-compat.css',
						'ver'    => WOPB_VER,
					)
				);
			}
		);
	}

	/**
	 * Purge WP Rocket cache when WowStore WP Rocket compatibility setting is saved.
	 *
	 * @since v.1.0.0
	 * @param string $key Saved settings key/group.
	 * @return void
	 */
	public function purge_wp_rocket_cache_on_settings_save( $key ) {
		if ( 'general' !== $key ) {
			return;
		}

		if ( 'yes' !== wopb_function()->get_setting( 'wprocket_cache_exclusion' ) ) {
			return;
		}

		$this->purge_wp_rocket_cache();
		update_option( 'wopb_wp_rocket_compatibility_version', '2026-08-12-navigation-rucss-fallback' );
	}

	/**
	 * Purge WP Rocket cache once when compatibility rules change.
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	public function purge_wp_rocket_cache_on_compatibility_change() {
		$compatibility_version = '2026-08-12-navigation-rucss-fallback';

		if ( $compatibility_version === get_option( 'wopb_wp_rocket_compatibility_version' ) ) {
			return;
		}

		if ( 'yes' !== wopb_function()->get_setting( 'wprocket_cache_exclusion' ) ) {
			return;
		}

		if ( ! $this->is_wp_rocket_active() ) {
			return;
		}

		if ( ! current_user_can( 'rocket_remove_unused_css' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$this->purge_wp_rocket_cache();
		update_option( 'wopb_wp_rocket_compatibility_version', $compatibility_version );
	}

	/**
	 * Purge WP Rocket page, minify, and Used CSS caches.
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	private function purge_wp_rocket_cache() {
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}

		if ( function_exists( 'rocket_clean_minify' ) ) {
			rocket_clean_minify();
		}

		$this->purge_wp_rocket_used_css_cache();
	}

	/**
	 * Purge WP Rocket Remove Unused CSS cache.
	 *
	 * @since v.1.0.0
	 * @return void
	 */
	private function purge_wp_rocket_used_css_cache() {
		if ( ! current_user_can( 'rocket_remove_unused_css' ) && ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( function_exists( 'wpm_apply_filters_typed' ) ) {
			wpm_apply_filters_typed( 'array', 'rocket_saas_clean_all', array() );
			return;
		}

		apply_filters( 'rocket_saas_clean_all', array() ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Rocket's own filter hook name, must match exactly to integrate with it.
	}
}
