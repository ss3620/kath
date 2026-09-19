<?php

/**
 * Polylang Compatibility.
 * 
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Polylang_Compatibility')) {

	/**
	 * Class.
	 * 
	 * @since 1.0.0
	 */
	class FGF_Polylang_Compatibility extends FGF_Compatibility {

		/**
		 * Class Constructor.
		 * 
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->id = 'polylang';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 * 
		 * @since 1.0.0
		 * @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists('Polylang');
		}

		/**
		 * Action
		 * 
		 * @since 1.0.0
		 */
		public function actions() {
			// Check if the product id is valid.
			add_filter('fgf_is_valid_product', array( $this, 'valid_product' ), 10, 2);
		}

		/**
		 * Check if the product id is valid for current language?.
		 * 
		 * @since 1.0.0
		 * @return bool
		 */
		public function valid_product( $bool, $product_id ) {
			global $polylang;
			// The product is considered valid if the product post-type translation is not enabled in the settings of Polylang.
			if (!is_object($polylang) || !isset($polylang->links_model->model->post) || !$polylang->links_model->model->post->is_translated_object_type('product')) {
				return $bool;
			}

			// The product is considered valid if the current language equals the product post language.
			if (pll_get_post_language($product_id) == pll_current_language()) {
				return $bool;
			}

			return false;
		}
	}

}
