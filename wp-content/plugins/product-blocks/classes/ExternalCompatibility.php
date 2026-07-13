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
			error_log( 'Spectra CSS generation error: ' . $e->getMessage() );
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
				error_log( 'Spectra CSS file does not exist: ' . $spectra_css_path );
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
		// Load plugin.php if not already loaded
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		// Check if WP Rocket is active
		if ( ! is_plugin_active( 'wp-rocket/wp-rocket.php' ) ) {
			return;
		}

		add_filter(
			'rocket_rucss_excluded_selectors',
			function ( $selectors ) {
				$selectors[] = '.wopb-product';
				$selectors[] = '.productx-global-style';
				$selectors[] = '.wopb-(.*)';
				$selectors[] = '[class^="wopb-"]';
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
}
