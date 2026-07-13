<?php

/**
 * Tab - Shortcode
 * 
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (class_exists('FGF_Shortcode_Tab')) {
	return new FGF_Shortcode_Tab();
}

/**
 * Class.
 * 
 * @since 1.0.0
 */
class FGF_Shortcode_Tab extends FGF_Settings_Page {

	/**
	 * Constructor.
	 * 
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->id = 'shortcodes';
		$this->show_buttons = false;
		$this->label = __('Shortcodes', 'free-gifts-for-woocommerce');

		parent::__construct();
	}

	/**
	 * Display the short codes details.
	 * 
	 * @since 10.1.0
	 */
	public function output_extra_fields() {
		include_once FGF_ABSPATH . 'inc/admin/menu/views/shortcode/html-shortcodes.php';
	}

	/**
	 * Get the short code tabs.
	 * 
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_shortcode_tabs() {
		/**
		 * This hook is used to alter the short code tabs.
		 * 
		 * @since 11.4.0
		 */
		return apply_filters('fgf_shortcode_tabs', array(
			'common' => __('Common Shortcodes', 'free-gifts-for-woocommerce'),
			'parameters' => __('Parameters Value', 'free-gifts-for-woocommerce'),
			'example' => __('Example', 'free-gifts-for-woocommerce'),
		));
	}

	/**
	 * Get the common short codes.
	 * 
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_common_shortcodes() {
		/**
		 * This hook is used to alter the common short codes.
		 * 
		 * @since 11.4.0
		 */
		return apply_filters('fgf_common_shortcodes', array(
			'[fgf_gift_products]' => array(
				'supported_parameters' => 'type, mode ,per_page',
				'usage' => __('Displays gifts products based on the cart contents', 'free-gifts-for-woocommerce'),
			),
			'[fgf_cart_eligible_notices]' => array(
				'supported_parameters' => 'No',
				'usage' => __('Displays the cart eligible notices based on cart contents.', 'free-gifts-for-woocommerce'),
			),
			'[fgf_bogo_eligible_notices]' => array(
				'supported_parameters' => 'product_id',
				'usage' => __('Displays eligibility notices on a product that can be used in the Buy X Get Y mode.', 'free-gifts-for-woocommerce'),
			),
			'[fgf_progress_bar]' => array(
				'supported_parameters' => 'No',
				'usage' => __('Displays the progress bar of gift products count', 'free-gifts-for-woocommerce'),
			),
		));
	}

	/**
	 * Get the short code parameter value.
	 * 
	 * @since 11.4.0
	 * @return array
	 */
	public static function get_shortcode_parameter_values() {
		/**
		 * This hook is used to alter the short code parameter values.
		 * 
		 * @since 11.4.0
		 */
		return apply_filters('fgf_shortcode_parameter_values', array(
			'product_id' => 'any number',
			'type' => 'table, carousel, selectbox',
			'mode' => 'inline, popup',
			'per_page' => 'any number',
		));
	}
}

return new FGF_Shortcodes_Tab();
