<?php

/**
 * WooCommerce Blocks Compatibility.
 *
 * @since 11.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_WC_Blocks_Compatibility')) {

	/**
	 * Class.
	 * 
	 * @since 11.0.0
	 */
	class FGF_WC_Blocks_Compatibility {

		/**
		 * Class initialization.
		 * 
		 * @since 11.0.0
		 */
		public static function init() {
			add_action('woocommerce_blocks_loaded', array( __CLASS__, 'register_integration' ), 10);
		}

		/**
		 * Register integration.
		 * 
		 * @since 11.0.0
		 */
		public static function register_integration() {
			self::initialize();

			/**
			 * This hook is used to alter the compatible block names.
			 * 
			 * @since 11.0.0
			 */
			$compatible_block_names = apply_filters('fgf_compatible_block_names', array( 'cart', 'checkout' ));

			foreach ($compatible_block_names as $block_name) {
				add_action(
						"woocommerce_blocks_{$block_name}_block_registration",
						function ( $registry ) {
							$registry->register(FGF_WC_Blocks_Integration::instance());
						}
				);
			}
		}

		/**
		 * Initialize require files and store API.
		 * 
		 * @since 11.0.0
		 */
		private static function initialize() {
			// Require files.
			include_once FGF_ABSPATH . 'inc/wc-blocks/class-fgf-wc-blocks-integration.php';
			include_once FGF_ABSPATH . 'inc/wc-blocks/class-fgf-wc-blocks-store-api.php';

			// Initialize the store API.
			FGF_WC_Blocks_Store_API::init();
		}
	}

	FGF_WC_Blocks_Compatibility::init();
}
