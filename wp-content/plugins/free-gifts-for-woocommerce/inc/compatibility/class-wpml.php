<?php

/**
 * WPML Compatibility.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'FGF_WPML_Compatibility' ) ) {

	/**
	 * Class FGF_WPML_Compatibility.
	 */
	class FGF_WPML_Compatibility extends FGF_Compatibility {

		/**
		 * Context
		 * 
		 * @var string
		 */
		private $context = 'free-gifts-for-woocommerce' ;

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id = 'wpml' ;

			parent::__construct() ;
		}

		/**
		 * Is plugin enabled?.
		 * 
		 *  @return bool
		 * */
		public function is_plugin_enabled() {

			return function_exists( 'icl_register_string' ) ;
		}

		/**
		 * Admin Action
		 */
		public function admin_action() {

			// Register the string.
			add_filter( 'admin_init' , array( $this, 'register_string' ) , 10 , 3 ) ;
		}

		/**
		 * Action
		 */
		public function actions() {

			// Get the string.
			add_filter( 'fgf_rule_translate_string' , array( $this, 'translate_string' ) , 10 , 3 ) ;
			// Check if the product id is valid.
			add_filter( 'fgf_is_valid_product' , array( $this, 'valid_product' ) , 10 , 2 ) ;
		}

		/**
		 * Check if the product id is valid for current language?.
		 * 
		 * @return bool
		 */
		public function valid_product( $bool, $product_id ) {
			global $sitepress ;
			if ( ! is_object( $sitepress ) ) {
				return $bool ;
			}

			if ( $sitepress->get_language_for_element( $product_id , 'post_product' ) == $sitepress->get_current_language() ) {
				return $bool ;
			}

			return false ;
		}

		/**
		 * Register the string in WPML.
		 * 
		 * @return void
		 */
		public function register_string() {

			$rule_ids = fgf_get_rule_ids() ;
			// Return if the rule ids not exists.
			if ( ! fgf_check_is_array( $rule_ids ) ) {
				return ;
			}

			foreach ( $rule_ids as $rule_id ) {
				$rule = fgf_get_rule( $rule_id ) ;

				$register_strings = array(
					'fgf_rule_notice_' . $rule_id => $rule->get_notice(),
					'fgf_rule_bulk_eligible_notice_' . $rule_id => $rule->get_bulk_eligible_notice(),
					'fgf_rule_bogo_eligible_notice_' . $rule_id => $rule->get_bogo_eligible_notice(),
						) ;

				foreach ( $register_strings as $name => $value ) {

					// Registering the rule string.
					icl_register_string( $this->context , $name , $value ) ;
				}
			}
		}

		/**
		 * Get the string in WPML.
		 * 
		 * @return string
		 */
		public function translate_string( $value, $option_name, $language ) {
			$has_translation = null ;

			return icl_translate( $this->context , $option_name , $value , false , $has_translation , $language ) ;
		}
	}

}
