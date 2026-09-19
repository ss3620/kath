<?php

/**
 * Template functions.
 * */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}


if (!function_exists('fgf_get_template')) {

	/**
	 * Get the other templates from themes.
	 */
	function fgf_get_template( $template_name, $args = array() ) {
		wc_get_template($template_name, $args, 'free-gifts-for-woocommerce/', FGF()->templates());
	}

}

if (!function_exists('fgf_get_template_html')) {

	/**
	 *  Like fgf_get_template, but returns the HTML instead of outputting.
	 *
	 *  @return string
	 */
	function fgf_get_template_html( $template_name, $args = array() ) {
		ob_start();
		fgf_get_template($template_name, $args);
		return ob_get_clean();
	}

}

if (!function_exists('fgf_get_pagination_classes')) {

	/**
	 * Get the pagination classes.
	 *
	 *  @return array
	 */
	function fgf_get_pagination_classes( $page_no, $current_page ) {
		$classes = array( 'fgf_pagination', 'fgf_pagination_' . $page_no );
		if ($current_page == $page_no) {
			$classes[] = 'current';
		}
		/**
		 * This hook is used to alter the pagination classes.
		 * 
		 * @since 1.0
		 */
		return apply_filters('fgf_pagination_classes', $classes, $page_no, $current_page);
	}

}

if (!function_exists('fgf_get_pagination_number')) {

	/**
	 * Get the pagination number.
	 *
	 *  @return string
	 */
	function fgf_get_pagination_number( $start, $page_count, $current_page ) {
		$page_no = false;
		if ($current_page <= $page_count && $start <= $page_count) {
			$page_no = $start;
		} else if ($current_page > $page_count) {
			$overall_count = $current_page - $page_count + $start;
			if ($overall_count <= $current_page) {
				$page_no = $overall_count;
			}
		}
		/**
		 * This hook is used to alter the pagination number.
		 * 
		 * @since 1.0
		 */
		return apply_filters('fgf_pagination_number', $page_no, $start, $page_count, $current_page);
	}

}

if (!function_exists('fgf_get_gift_product_heading_label')) {

	/**
	 * Get the label for gift product heading.
	 *
	 * @return string.
	 * */
	function fgf_get_gift_product_heading_label() {
		/**
		 * This hook is used to alter the gift product heading label.
		 * 
		 * @since 1.0
		 */
		return apply_filters('fgf_gift_product_heading_label', get_option('fgf_settings_free_gift_heading_label'));
	}

}

if (!function_exists('fgf_get_gift_product_add_to_cart_button_label')) {

	/**
	 * Get the label for gift product add to cart button.
	 *
	 * @return string.
	 * */
	function fgf_get_gift_product_add_to_cart_button_label() {
		/**
		 * This hook is used to alter the gift product add to cart button label.
		 * 
		 * @since 1.0
		 */
		return apply_filters('fgf_gift_product_add_to_cart_button_label', get_option('fgf_settings_free_gift_add_to_cart_button_label'));
	}

}

if (!function_exists('fgf_get_gift_product_dropdown_default_value_label')) {

	/**
	 * Get the label for gift product dropdown default value.
	 *
	 * @return string.
	 * */
	function fgf_get_gift_product_dropdown_default_value_label() {
		/**
		 * This hook is used to alter the gift product dropdown default value label.
		 * 
		 * @since 1.0
		 */
		return apply_filters('fgf_gift_product_dropdown_default_value_label', get_option('fgf_settings_free_gift_dropdown_default_option_label', 'Please select a Gift'));
	}

}

if (!function_exists('fgf_get_dropdown_gift_product_name')) {

	/**
	 * Get the dropdown gift product name.
	 * 
	 * @return string.
	 * */
	function fgf_get_dropdown_gift_product_name( $product_id, $product = false ) {
		if (!is_object($product)) {
			$product = wc_get_product($product_id);
		}
		/**
		 * This hook is used to alter the drop down gift product name.
		 * 
		 * @since 1.0
		 */
		return apply_filters('fgf_get_dropdown_gift_product_name', fgf_render_product_name($product, false, false), $product);
	}

}

if (!function_exists('fgf_show_dropdown_add_to_cart_button')) {

	/**
	 * Show the dropdown add to cart button.
	 * 
	 * @return bool.
	 * */
	function fgf_show_dropdown_add_to_cart_button() {
		/**
		 * This hook is used to validate the drop down add to cart button.
		 * 
		 * @since 1.0
		 */
		return apply_filters('fgf_show_dropdown_add_to_cart_button', '2' !== get_option('fgf_settings_dropdown_add_to_cart_behaviour'));
	}

}

if (!function_exists('fgf_is_dropdown_gift_product_with_image')) {

	/**
	 * Is drop down gift product with image? 
	 * 
	 * @since 10.6.0
	 * @return bool.
	 * */
	function fgf_is_dropdown_gift_product_with_image() {
		/**
		 * This hook is used to alter the drop down gift product with image display mode.
		 * 
		 * @since 10.6.0
		 */
		return apply_filters('fgf_show_dropdown_add_to_cart_button', '2' === get_option('fgf_settings_gift_dropdown_display_type'));
	}

}

if (!function_exists('fgf_render_product_name')) {

	/**
	 * Display the gift product name in table.
	 *
	 * @return string
	 */
	function fgf_render_product_name( $product, $echo = true, $link = true ) {
		$formatted_variation_list = in_array($product->get_type(), array( 'variation' ))? wc_get_formatted_variation( $product, true, true, true ):'';
		$formatted_variation_list = !empty($formatted_variation_list)?' - ' . $formatted_variation_list:'';
		$product_name= $product->get_name() . $formatted_variation_list;
		if ($link && '2' == get_option('fgf_settings_gift_display_product_linkable', '1')) {
			$product_name = "<a href='" . get_permalink($product->get_id()) . "'>" . esc_html($product_name) . '</a>';
		}
		
		/**
		 * This hook is used to alter the gift product name.
		 * 
		 * @since 1.0
		 */
		$product_name = apply_filters('fgf_gift_product_name', $product_name, $product);
		if ($echo) {
			echo wp_kses_post($product_name);
		}

		return $product_name;
	}

}

if (!function_exists('fgf_get_gift_product_add_to_cart_classes')) {

	/**
	 * Get the gift product add to cart classes.
	 *
	 *  @return array
	 */
	function fgf_get_gift_product_add_to_cart_classes() {
		$classes = array( 'button', 'fgf-add-manual-gift-product' );
		/**
		 * This hook is used to alter the gift product add to cart classes.
		 * 
		 * @since 1.0
		 */
		return apply_filters('fgf_gift_product_add_to_cart_classes', $classes);
	}

}

if (!function_exists('fgf_get_gift_product_add_to_cart_url')) {

	/**
	 * Get the gift product add to cart URL.
	 *
	 *  @return array
	 */
	function fgf_get_gift_product_add_to_cart_url( $gift_product, $permalink = false ) {
		if (!$permalink) {
			$permalink = get_permalink();
		}

		if ('yes' == get_option('fgf_settings_enable_ajax_add_to_cart') || fgf_is_gift_product_quantity_field_enabled() || fgf_is_block_checkout() || fgf_is_block_cart()) {
			$url = '#';
		} else {
			$args = array(
				'fgf_gift_product' => $gift_product['product_id'],
				'fgf_rule_id' => $gift_product['rule_id'],
			);

			if (isset($gift_product['buy_product_id'])) {
				$args['fgf_buy_product_id'] = $gift_product['buy_product_id'];
			}

			if (isset($gift_product['coupon_id'])) {
				$args['fgf_coupon_id'] = $gift_product['coupon_id'];
			}

			$url = esc_url(add_query_arg($args, $permalink));
		}

		/**
		 * This hook is used to alter the gift product add to cart URL.
		 * 
		 * @since 1.0
		 */
		return apply_filters('fgf_gift_product_add_to_cart_url', $url);
	}

}

if (!function_exists('fgf_get_gift_products_popup_notice')) {

	/**
	 * Get the gift products popup notice.
	 * 
	 * @since 9.8.0
	 * @return string
	 */
	function fgf_get_gift_products_popup_notice() {
		$popup_link = '<a href="#" class="fgf-popup-gift-products">' . get_option('fgf_settings_free_gift_popup_link_message') . '</a>';
		$find_shortcodes = array( '[popup_link]', '[remaining_gift_count]' );
		$replace_shortcodes = array( $popup_link, FGF_Rule_Handler::get_remaining_gift_products_count() );

		/**
		 * This hook is used to alter the gift products popup notice.
		 * 
		 * @since 9.8.0
		 */
		return apply_filters('fgf_gift_products_popup_notice', str_replace($find_shortcodes, $replace_shortcodes, get_option('fgf_settings_free_gift_popup_notice_message')));
	}

}

if (!function_exists('fgf_get_manual_gift_products_notice')) {

	/**
	 * Get the gift products notice.
	 * 
	 * @since 9.8.0
	 * @return string
	 */
	function fgf_get_manual_gift_products_notice() {

		/**
		 * This hook is used to alter the manual gift products notice.
		 * 
		 * @since 9.8.0
		 */
		return apply_filters('fgf_manual_gift_products_notice', str_replace('[remaining_gift_count]', FGF_Rule_Handler::get_remaining_gift_products_count(), get_option('fgf_settings_free_gift_notice_message')));
	}

}

if (!function_exists('fgf_get_progress_bar_heading_label')) {

	/**
	 * Get the label for progress bar heading.
	 *
	 * @since 9.8.0
	 * @return string.
	 * */
	function fgf_get_progress_bar_heading_label() {
		/**
		 * This hook is used to alter the progress bar heading label.
		 * 
		 * @since 9.8.0
		 */
		return apply_filters('fgf_progress_bar_heading_label', get_option('fgf_settings_progress_bar_heading_label'));
	}

}


if (!function_exists('fgf_get_progress_bar_maximum_gift_count_label')) {

	/**
	 * Get the label for progress bar maximum gift count.
	 *
	 * @since 9.8.0
	 * @return string.
	 * */
	function fgf_get_progress_bar_maximum_gift_count_label() {
		/**
		 * This hook is used to alter the progress bar maximum gift count label.
		 * 
		 * @since 9.8.0
		 */
		return apply_filters('fgf_progress_bar_maximum_gift_count_label', str_replace('[maximum_gift_count]', intval(FGF_Rule_Handler::get_total_gift_products_count()), get_option('fgf_settings_progress_bar_maximum_gift_count_label')));
	}

}

if (!function_exists('fgf_get_progress_bar_added_gift_count_label')) {

	/**
	 * Get the label for progress bar added gift count.
	 *
	 * @since 9.8.0
	 * @return string.
	 * */
	function fgf_get_progress_bar_added_gift_count_label() {
		/**
		 * This hook is used to alter the progress bar added gift count label.
		 * 
		 * @since 9.8.0
		 */
		return apply_filters('fgf_progress_bar_added_gift_count_label', str_replace('[added_gift_count]', intval(FGF_Rule_Handler::get_added_gift_products_count()), get_option('fgf_settings_progress_bar_added_gift_count_label')));
	}

}

if (!function_exists('fgf_get_progress_bar_remaining_gift_count_label')) {

	/**
	 * Get the label for progress bar remaining gift count.
	 *
	 * @since 9.8.0
	 * @return string.
	 * */
	function fgf_get_progress_bar_remaining_gift_count_label() {
		/**
		 * This hook is used to alter the progress bar remaining gift count label.
		 * 
		 * @since 9.8.0
		 */
		return apply_filters('fgf_progress_bar_remaining_gift_count_label', str_replace('[remaining_gift_count]', intval(FGF_Rule_Handler::get_remaining_gift_products_count()), get_option('fgf_settings_progress_bar_remaining_gift_count_label')));
	}

}

if (!function_exists('fgf_is_valid_to_show_gift_product_quantity_field')) {

	/**
	 * Is valid to show the gift product quantity field.
	 *
	 * @since 10.1.0
	 * @param array $gift_product It contains gift product data
	 * @return boolean.
	 * */
	function fgf_is_valid_to_show_gift_product_quantity_field( $gift_product ) {
		if (!fgf_is_gift_product_quantity_field_enabled()) {
			return false;
		}

		$bool = !$gift_product['hide_add_to_cart'] && $gift_product['qty'] > 1;
		/**
		 * This hook is used to validate the gift product quantity field display.
		 * 
		 * @since 10.1.0
		 */
		return apply_filters('fgf_is_valid_to_show_gift_product_quantity_field', $bool, $gift_product);
	}

}

if (!function_exists('fgf_is_gift_product_quantity_field_enabled')) {

	/**
	 * Is gift product quantity field enabled?.
	 *
	 * @since 10.1.0
	 * @return boolean.
	 * */
	function fgf_is_gift_product_quantity_field_enabled() {
		$bool = ( '2' === get_option('fgf_settings_gift_product_quantity_field_enabled') ) ? false : true;

		/**
		 * This hook is used to validate the gift product quantity field.
		 * 
		 * @since 10.1.0
		 */
		return apply_filters('fgf_is_gift_product_quantity_field_enabled', $bool);
	}

}

if (!function_exists('fgf_get_cart_free_gifts_html')) {

	/**
	 * Get the cart free gifts HTML.
	 *
	 * @since 11.0.0
	 * @return string
	 */
	function fgf_get_cart_free_gifts_html() {
		ob_start();
		FGF_Gift_Products_Handler::render_gift_products_cart_page();
		$contents = ob_get_contents();
		ob_end_clean();

		/**
		 * This hook is used to alter the cart free gifts HTML.
		 * 
		 * @since 11.0.0
		 */
		return apply_filters('fgf_cart_free_gifts_html', $contents);
	}

}

if (!function_exists('fgf_get_checkout_free_gifts_html')) {

	/**
	 * Get the checkout free gifts HTML.
	 *
	 * @since 11.0.0
	 * @return string
	 */
	function fgf_get_checkout_free_gifts_html() {
		ob_start();
		FGF_Gift_Products_Handler::render_gift_products_checkout_page();
		$contents = ob_get_contents();
		ob_end_clean();

		/**
		 * This hook is used to alter the checkout free gifts HTML.
		 * 
		 * @since 11.0.0
		 */
		return apply_filters('fgf_checkout_free_gifts_html', $contents);
	}

}

if (!function_exists('fgf_get_cart_progress_bar_html')) {

	/**
	 * Get the cart progress bar HTML.
	 *
	 * @since 11.0.0
	 * @return string
	 */
	function fgf_get_cart_progress_bar_html() {
		ob_start();
		FGF_Gift_Products_Handler::render_progress_bar_cart_page();
		$contents = ob_get_contents();
		ob_end_clean();

		/**
		 * This hook is used to alter the cart progress bar HTML.
		 * 
		 * @since 11.0.0
		 */
		return apply_filters('fgf_cart_progress_bar_html', $contents);
	}

}

if (!function_exists('fgf_get_checkout_progress_bar_html')) {

	/**
	 * Get the checkout progress bar HTML.
	 *
	 * @since 11.0.0
	 * @return string
	 */
	function fgf_get_checkout_progress_bar_html() {
		ob_start();
		FGF_Gift_Products_Handler::render_progress_bar_checkout_page();
		$contents = ob_get_contents();
		ob_end_clean();

		/**
		 * This hook is used to alter the checkout progress bar HTML.
		 * 
		 * @since 11.0.0
		 */
		return apply_filters('fgf_checkout_progress_bar_html', $contents);
	}

}

if (!function_exists('fgf_get_checkout_free_gifts_notices_html')) {

	/**
	 * Get the checkout free gifts notices HTML.
	 *
	 * @since 11.2.0
	 * @return string
	 */
	function fgf_get_checkout_free_gifts_notices_html() {
		ob_start();
		FGF_Notices_Handler::maybe_show_checkout_notices();
		$contents = ob_get_contents();
		ob_end_clean();

		/**
		 * This hook is used to alter the checkout free gifts notices HTML.
		 * 
		 * @since 11.2.0
		 */
		return apply_filters('fgf_checkout_free_gifts_notices_html', $contents);
	}

}

if (!function_exists('fgf_get_free_gifts_table_columns')) {

	/**
	 * Get the free gifts table columns.
	 *
	 * @since 11.0.0
	 * @return array
	 */
	function fgf_get_free_gifts_table_columns() {
		static $columns;
		if (isset($columns)) {
			return $columns;
		}

		$columns = array(
			'product_name' => __('Product Name', 'free-gifts-for-woocommerce'),
			'product_image' => __('Product Image', 'free-gifts-for-woocommerce'),
			'add_to_cart' => __('Add to cart', 'free-gifts-for-woocommerce'),
		);

		/**
		 * This hook is used to alter the free gifts table columns.
		 * 
		 * @since 11.0.0
		 */
		return apply_filters('fgf_free_gifts_table_columns', $columns);
	}

}
