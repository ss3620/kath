<?php

/**
 * Default functions.
 * 
 * @since 9.2.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!function_exists('fgf_get_rule_statuses_options')) {

	/**
	 * Get the rule statuses options.
	 *
	 * @since 9.6.0
	 * @staticvar $rule_statuses
	 * @return array
	 */
	function fgf_get_rule_statuses_options() {
		static $rule_statuses;
		if ($rule_statuses) {
			return $rule_statuses;
		}

		$rule_statuses = array(
			'fgf_active' => __('Active', 'free-gifts-for-woocommerce'),
			'fgf_inactive' => __('In-active', 'free-gifts-for-woocommerce'),
		);

		/**
		 * This hook is used to alter the rule statuses options.
		 * 
		 * @param array $rule_statuses rule statuses
		 * @since 9.6.0
		 */
		$rule_statuses= apply_filters('fgf_rule_statuses_options', $rule_statuses);

		return $rule_statuses;
	}

}

if (!function_exists('fgf_get_rule_criteria_total_type_options')) {

	/**
	 * Get the rule criteria total type options.
	 *
	 * @since 8.6
	 * @return array
	 */
	function fgf_get_rule_criteria_total_type_options() {
		static $options;
		if (isset($options)) {
			return $options;
		}

		$options = array(
			'1' => __('Cart Subtotal', 'free-gifts-for-woocommerce'),
			'2' => __('Order Total', 'free-gifts-for-woocommerce'),
			'3' => __('Category Total', 'free-gifts-for-woocommerce'),
		);

		/**
		 * This hook is used to alter the rule criteria total type options
		 * 
		 * @since 8.6
		 */
		$options= apply_filters('fgf_rule_criteria_total_type_options', $options);

		return $options;
	}

}

if (!function_exists('fgf_get_rule_cheapest_gift_price_type_options')) {

	/**
	 * Get the rule cheapest gift price type options.
	 *
	 * @since 11.4.0
	 * @return array
	 */
	function fgf_get_rule_cheapest_gift_price_type_options() {
		static $options;
		if (isset($options)) {
			return $options;
		}

		$options = array(
			'1' => __('Price of the Product in the Cart', 'free-gifts-for-woocommerce'),
			'2' => __('Sub-total Price of the Product in the Cart', 'free-gifts-for-woocommerce'),
			'3' => __('Regular Price of the Product', 'free-gifts-for-woocommerce'),
			'4' => __('Sale Price of the Product', 'free-gifts-for-woocommerce'),
		);

		/**
		 * This hook is used to alter the rule cheapest gift price type options
		 * 
		 * @since 11.4.0
		 */
		$options= apply_filters('fgf_rule_cheapest_gift_price_type_options', $options);

		return $options;
	}

}

if (!function_exists('fgf_rule_user_filter_options')) {

	/**
	 * Get the rule user filter options
	 *
	 * @since 9.2
	 * @return array
	 */
	function fgf_rule_user_filter_options() {
		static $rule_user_filters;
		if (isset($rule_user_filters)) {
			return $rule_user_filters;
		}

		$rule_user_filters = array(
			'1' => __('All User(s)', 'free-gifts-for-woocommerce'),
			'2' => __('Include User(s)', 'free-gifts-for-woocommerce'),
			'3' => __('Exclude User(s)', 'free-gifts-for-woocommerce'),
			'4' => __('Include User Role(s)', 'free-gifts-for-woocommerce'),
			'5' => __('Exclude User Role(s)', 'free-gifts-for-woocommerce'),
		);
		/**
		 * This hook is used to alter the rule user filter options
		 * 
		 * @since 9.2
		 */
		$rule_user_filters= apply_filters('fgf_rule_user_filter_options', $rule_user_filters);

		return $rule_user_filters;
	}

}

if (!function_exists('fgf_rule_product_filter_options')) {

	/**
	 * Get the rule product filter options
	 *
	 * @since 9.2
	 * @return array
	 */
	function fgf_rule_product_filter_options() {
		static $rule_product_filters;
		if (isset($rule_product_filters)) {
			return $rule_product_filters;
		}

		$rule_product_filters = array(
			'1' => __('All Product(s)', 'free-gifts-for-woocommerce'),
			'2' => __('Include Product(s)', 'free-gifts-for-woocommerce'),
			'3' => __('Exclude Product(s)', 'free-gifts-for-woocommerce'),
			'4' => __('All Categories', 'free-gifts-for-woocommerce'),
			'5' => __('Include Categories', 'free-gifts-for-woocommerce'),
			'6' => __('Exclude Categories', 'free-gifts-for-woocommerce'),
		);
		/**
		 * This hook is used to alter the rule product filter options
		 * 
		 * @since 9.2
		 */
		$rule_product_filters= apply_filters('fgf_rule_product_filter_options', $rule_product_filters);

		return $rule_product_filters;
	}

}

if (!function_exists('fgf_rule_virtual_product_restriction_options')) {

	/**
	 * Get the rule virtual product restriction options
	 *
	 * @since 11.3.0
	 * @return array
	 */
	function fgf_rule_virtual_product_restriction_options() {
		static $rule_virtual_product_restriction_options;
		if (isset($rule_virtual_product_restriction_options)) {
			return $rule_virtual_product_restriction_options;
		}

		$rule_virtual_product_restriction_options = array(
			'1' => __('Select an option', 'free-gifts-for-woocommerce'),
			'2' => __('Only Virtual Products', 'free-gifts-for-woocommerce'),
		);
		/**
		 * This hook is used to alter the rule virtual product restriction options
		 * 
		 * @since 11.3.0
		 */
		$rule_virtual_product_restriction_options= apply_filters('fgf_rule_virtual_product_restriction_options', $rule_virtual_product_restriction_options);
	
		return $rule_virtual_product_restriction_options;
	}

}

if (!function_exists('fgf_rule_product_applicable_filter_options')) {

	/**
	 * Get the rule product applicable filter options
	 *
	 * @since 9.2
	 * @return array
	 */
	function fgf_rule_product_applicable_filter_options() {
		static $rule_product_applicable_filters;
		if (isset($rule_product_applicable_filters)) {
			return $rule_product_applicable_filters;
		}

		$rule_product_applicable_filters = array(
			'1' => __('Any one of the selected Product(s) must be in cart', 'free-gifts-for-woocommerce'),
			'2' => __('All the selected Product(s) must be in cart', 'free-gifts-for-woocommerce'),
			'3' => __('Only the selected Product(s) must be in cart', 'free-gifts-for-woocommerce'),
			'4' => __('User purchases the Specified Number of Products', 'free-gifts-for-woocommerce'),
		);
		/**
		 * This hook is used to alter the rule product applicable filter options
		 * 
		 * @since 9.2
		 */
		$rule_product_applicable_filters= apply_filters('fgf_rule_product_applicable_filter_options', $rule_product_applicable_filters);
	
		return $rule_product_applicable_filters;
	}

}

if (!function_exists('fgf_rule_category_applicable_filter_options')) {

	/**
	 * Get the rule category applicable filter options
	 *
	 * @since 9.2
	 * @return array
	 */
	function fgf_rule_category_applicable_filter_options() {
		static $rule_category_applicable_filters;
		if (isset($rule_category_applicable_filters)) {
			return $rule_category_applicable_filters;
		}

		$rule_category_applicable_filters = array(
			'1' => __('Any one of the product(s) should be from the selected category', 'free-gifts-for-woocommerce'),
			'2' => __('One product from each category must be in cart', 'free-gifts-for-woocommerce'),
			'3' => __('Only products from the selected category should be in cart', 'free-gifts-for-woocommerce'),
			'4' => __('Total Quantity of the Product(s) from the selected categories', 'free-gifts-for-woocommerce'),
		);
		/**
		 * This hook is used to alter the rule category applicable filter options
		 * 
		 * @since 9.2
		 */
		$rule_category_applicable_filters= apply_filters('fgf_rule_category_applicable_filter_options', $rule_category_applicable_filters);
	
		return $rule_category_applicable_filters;
	}

}

if (!function_exists('fgf_rule_applicable_brands_filter_options')) {

	/**
	 * Get the rule applicable brands filter options
	 *
	 * @since 9.4.0
	 * @return array
	 */
	function fgf_rule_applicable_brands_filter_options() {
		static $rule_applicable_brand_filters;
		if (isset($rule_applicable_brand_filters)) {
			return $rule_applicable_brand_filters;
		}

		$rule_applicable_brand_filters = array(
			'1' => __('Any one of the product(s) should be in the cart from the selected Brand(s)', 'free-gifts-for-woocommerce'),
			'2' => __('One product from each Brand must be in cart', 'free-gifts-for-woocommerce'),
			'3' => __('Only products from the selected Brand(s) should be in cart', 'free-gifts-for-woocommerce'),
			'4' => __('Total Quantity of the Product(s) from the selected brands', 'free-gifts-for-woocommerce'),
		);
		/**
		 * This hook is used to alter the rule applicable brands filter options
		 * 
		 * @since 9.4.0
		 */
		$rule_applicable_brand_filters= apply_filters('fgf_rule_applicable_brands_filter_options', $rule_applicable_brand_filters);
	
		return $rule_applicable_brand_filters;
	}

}
