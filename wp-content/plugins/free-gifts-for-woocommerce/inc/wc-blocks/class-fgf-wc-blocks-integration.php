<?php

/**
 * WooCommerce Blocks Integration.
 *
 * @since 11.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks scripts
 *
 * @since 11.0.0
 */
class FGF_WC_Blocks_Integration implements IntegrationInterface {

	/**
	 * Whether the integration has been initialized.
	 *
	 * @since 11.0.0
	 * @var boolean
	 */
	protected $is_initialized;

	/**
	 * The single instance of the class.
	 *
	 * @since 11.0.0
	 * @var FGF_WC_Blocks_Integration
	 */
	protected static $_instance = null;

	/**
	 * Main FGF_WC_Blocks_Integration instance. Ensures only one instance of FGF_WC_Blocks_Integration is loaded or can be loaded.
	 *
	 * @since 11.0.0
	 * @static
	 * @return FGF_WC_Blocks_Integration
	 */
	public static function instance() {
		if (is_null(self::$_instance)) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 * 
	 * @since 11.0.0
	 */
	public function __clone() {
		_doing_it_wrong(__FUNCTION__, esc_html__('Foul!', 'free-gifts-for-woocommerce'), '11.0.0');
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 * 
	 * @since 11.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong(__FUNCTION__, esc_html__('Foul!', 'free-gifts-for-woocommerce'), '11.0.0');
	}

	/**
	 * The name of the integration.
	 *
	 * @since 11.0.0
	 * @return string
	 */
	public function get_name() {
		return 'fgf-wc-blocks';
	}

	/**
	 * When called invokes any initialization/setup for the integration.
	 * 
	 * @since 11.0.0
	 */
	public function initialize() {
		if ($this->is_initialized) {
			return;
		}

		// Enqueue block assets for the editor.
		add_action('enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ));
		// Enqueue block assets for the front-end.
		add_action('enqueue_block_assets', array( $this, 'enqueue_block_assets' ));
	}

	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @since 11.0.0
	 * @return string[]
	 */
	public function get_script_handles() {
		return array( 'fgf-wc-blocks' );
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @since 11.0.0
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return array( 'fgf-wc-blocks' );
	}

	/**
	 * Enqueue block assets for the editor.
	 *
	 * @since 11.0.0
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		// Load script.
		$script_asset_details = $this->get_script_asset_details('admin');

		wp_register_script(
				'fgf-wc-blocks',
				FGF_PLUGIN_URL . '/assets/blocks/admin/index.js',
				$script_asset_details['dependencies'],
				$script_asset_details['version'],
				true
		);

		wp_enqueue_style(
				'fgf-wc-blocks',
				FGF_PLUGIN_URL . '/assets/blocks/admin/index.css',
				'',
				$script_asset_details['version']
		);
	}

	/**
	 * Get the script asset details from the file if exists.
	 * 
	 * @since 11.0.0
	 * @param string $site
	 * @return array
	 */
	private function get_script_asset_details( $site = 'frontend' ) {
		return array( 'dependencies' => array( 'react', 'wc-settings', 'wp-element', 'wp-i18n' ), 'version' => FGF_VERSION );
	}

	/**
	 * Enqueue block assets for the front-end.
	 *
	 * @since 11.0.0
	 *
	 * @return void
	 */
	public function enqueue_block_assets() {
		// Load script.
		$script_asset_details = $this->get_script_asset_details();

		wp_register_script(
				'fgf-wc-blocks',
				FGF_PLUGIN_URL . '/assets/blocks/frontend/index.js',
				$script_asset_details['dependencies'],
				$script_asset_details['version'],
				true
		);
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @since 11.0.0
	 * @return array
	 */
	public function get_script_data() {
		if (!is_admin()) {
			return array(
				'gift_added_message' => get_option('fgf_settings_free_gift_success_message'),
			);
		} else {
			return array(
				'free_gifts_preview_html' => $this->get_free_gifts_preview_html(),
				'progress_bar_preview_html' => $this->get_progress_bar_preview_html(),
			);
		}
	}

	/**
	 * Get the free gifts preview HTML.
	 * 
	 * @since 11.0.0
	 * @return HTML
	 */
	private function get_free_gifts_preview_html() {
		ob_start();
		include_once FGF_ABSPATH . 'inc/admin/menu/views/blocks/html-free-gifts-block-preview-template.php';
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}

	/**
	 * Get the progress bar preview HTML.
	 * 
	 * @since 11.0.0
	 * @return HTML
	 */
	private function get_progress_bar_preview_html() {
		ob_start();
		include_once FGF_ABSPATH . 'inc/admin/menu/views/blocks/html-progress-bar-block-preview-template.php';
		$contents = ob_get_contents();
		ob_end_clean();

		return $contents;
	}
}
