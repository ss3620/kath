<?php

/**
 * Admin functions.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!function_exists('fgf_page_screen_ids')) {

	/**
	 * Get the page screen IDs.
	 *
	 * @return array
	 */
	function fgf_page_screen_ids() {
		$wc_screen_id = sanitize_title(__('WooCommerce', 'woocommerce'));
		$wc_static_screen_id = 'woocommerce';

		/**
		 * This hook is used to alter the page screen IDs.
		 *
		 * @since 1.0
		 */
		return apply_filters(
				'fgf_page_screen_ids', array_filter(array_unique(array(
			$wc_screen_id . '_page_fgf_settings',
			$wc_screen_id . '_page_wc-orders',
			$wc_static_screen_id . '_page_fgf_settings',
			$wc_static_screen_id . '_page_wc-orders',
			'shop_coupon',
			'shop_order',
				)))
		);
	}

}

if (!function_exists('fgf_current_page_screen_id')) {

	/**
	 * Get the current page screen ID.
	 *
	 * @since 10.4.0
	 * @staticvar string $fgf_current_screen_id
	 * @return string
	 */
	function fgf_current_page_screen_id() {
		static $fgf_current_screen_id;
		if ($fgf_current_screen_id) {
			return $fgf_current_screen_id;
		}

		$fgf_current_screen_id = false;
		if (!empty($_REQUEST['screen'])) {
			$fgf_current_screen_id = wc_clean(wp_unslash($_REQUEST['screen']));
		} elseif (function_exists('get_current_screen')) {
			$screen = get_current_screen();
			$fgf_current_screen_id = isset($screen, $screen->id) ? $screen->id : '';
		}

		$fgf_current_screen_id = str_replace('edit-', '', $fgf_current_screen_id);

		return $fgf_current_screen_id;
	}

}

if (!function_exists('fgf_get_allowed_setting_tabs')) {

	/**
	 * Get the setting tabs.
	 *
	 * @return array
	 */
	function fgf_get_allowed_setting_tabs() {
		/**
		 * This hook is used to alter the settings tabs.
		 *
		 * @since 1.0
		 */
		return apply_filters('fgf_settings_tabs_array', array());
	}

}

if (!function_exists('fgf_get_wc_order_statuses')) {

	/**
	 * Get the WC order statuses.
	 *
	 * @return array
	 */
	function fgf_get_wc_order_statuses() {
		$order_statuses_keys = array_keys(wc_get_order_statuses());
		$order_statuses_keys = str_replace('wc-', '', $order_statuses_keys);
		$order_statuses_values = array_values(wc_get_order_statuses());

		return array_combine($order_statuses_keys, $order_statuses_values);
	}

}

if (!function_exists('fgf_get_paid_order_statuses')) {

	/**
	 * Get the WC paid order statuses.
	 *
	 * @return array
	 */
	function fgf_get_paid_order_statuses() {
		$statuses = array(
			'processing' => __('Processing', 'free-gifts-for-woocommerce'),
			'completed' => __('Completed', 'free-gifts-for-woocommerce'),
		);
		/**
		 * This hook is used to alter the paid order statuses.
		 *
		 * @since 1.0
		 */
		return apply_filters('fgf_paid_order_statuses', $statuses);
	}

}

if (!function_exists('fgf_get_wc_categories')) {

	/**
	 * Get the WC categories.
	 *
	 * @return array
	 */
	function fgf_get_wc_categories() {
		static $fgf_categories;
		if (isset($fgf_categories)) {
			return $fgf_categories;
		}

		$fgf_categories = array();
		$wc_categories = get_terms('product_cat');

		if (!fgf_check_is_array($wc_categories)) {
			return $fgf_categories;
		}

		foreach ($wc_categories as $category) {
			$fgf_categories[$category->term_id] = $category->name;
		}

		return $fgf_categories;
	}

}

if (!function_exists('fgf_get_wp_user_roles')) {

	/**
	 * Get the WordPress user roles.
	 *
	 * @return array
	 */
	function fgf_get_wp_user_roles() {
		static $fgf_user_roles;
		if (isset($fgf_user_roles)) {
			return $fgf_user_roles;
		}

		global $wp_roles;
		$fgf_user_roles = array();

		if (!isset($wp_roles->roles) || !fgf_check_is_array($wp_roles->roles)) {
			return $fgf_user_roles;
		}

		foreach ($wp_roles->roles as $slug => $role) {
			$fgf_user_roles[$slug] = $role['name'];
		}

		return $fgf_user_roles;
	}

}

if (!function_exists('fgf_get_user_roles')) {

	/**
	 * Get the user roles.
	 *
	 * @return array
	 */
	function fgf_get_user_roles( $extra_options = array() ) {
		$user_roles = fgf_get_wp_user_roles();
		$user_roles['guest'] = __('Guest', 'free-gifts-for-woocommerce');

		$user_roles = array_merge($user_roles, $extra_options);

		return $user_roles;
	}

}

if (!function_exists('fgf_get_settings_page_url')) {

	/**
	 * Get the settings page URL.
	 *
	 * @return string
	 */
	function fgf_get_settings_page_url( $args = array() ) {
		$url = add_query_arg(array( 'page' => 'fgf_settings' ), admin_url('admin.php'));
		if (fgf_check_is_array($args)) {
			$url = add_query_arg($args, $url);
		}

		return $url;
	}

}

if (!function_exists('fgf_get_rule_page_url')) {

	/**
	 * Get the rule page URL.
	 *
	 * @return string
	 */
	function fgf_get_rule_page_url( $args = array() ) {
		$url = add_query_arg(
				array(
					'page' => 'fgf_settings',
					'tab' => 'rules',
				), admin_url('admin.php')
		);

		if (fgf_check_is_array($args)) {
			$url = add_query_arg($args, $url);
		}

		return $url;
	}

}

if (!function_exists('fgf_filter_readable_products')) {

	/**
	 * Filter the readable products.
	 *
	 * @return array
	 */
	function fgf_filter_readable_products( $product_ids ) {
		if (!fgf_check_is_array($product_ids)) {
			return array();
		}

		if (function_exists('wc_products_array_filter_readable')) {
			return array_filter(array_map('wc_get_product', $product_ids), 'wc_products_array_filter_readable');
		} else {
			return array_filter(array_map('wc_get_product', $product_ids), 'fgf_products_array_filter_readable');
		}
	}

}
if (!function_exists('fgf_products_array_filter_readable')) {

	/**
	 * Filter the readable product.
	 *
	 * @return array
	 */
	function fgf_products_array_filter_readable( $product ) {
		return $product && is_a($product, 'WC_Product') && current_user_can('read_product', $product->get_id());
	}

}

if (!function_exists('fgf_get_master_log_page_url')) {

	/**
	 * Get the master log page URL.
	 *
	 * @return string
	 */
	function fgf_get_master_log_page_url( $args = array() ) {
		$url = add_query_arg(
				array(
					'page' => 'fgf_settings',
					'tab' => 'master-log',
				), admin_url('admin.php')
		);

		if (fgf_check_is_array($args)) {
			$url = add_query_arg($args, $url);
		}

		return $url;
	}

}

if (!function_exists('fgf_get_rule_types')) {

	/**
	 * Get the rule types
	 *
	 * @since 11.4.0
	 * @return array
	 */
	function fgf_get_rule_types() {
		static $types;
		if (isset($types)) {
			return $types;
		}

		/**
		 * This hook is used to alter the rule types.
		 * 
		 * @since 11.4.0
		 */
		$types = apply_filters('fgf_rule_types', array(
			'1' => __('Manual', 'free-gifts-for-woocommerce'),
			'2' => __('Automatic', 'free-gifts-for-woocommerce'),
			'5' => __('Buy X Get Y - Manual', 'free-gifts-for-woocommerce'),
			'3' => __('Buy X Get Y - Automatic', 'free-gifts-for-woocommerce'),
			'6' => __('Coupon based Free Gift - Manual', 'free-gifts-for-woocommerce'),
			'4' => __('Coupon based Free Gift - Automatic', 'free-gifts-for-woocommerce'),
			'7' => __('Total based Free Gift - Manual', 'free-gifts-for-woocommerce'),
			'8' => __('Total based Free Gift - Autotmatic', 'free-gifts-for-woocommerce'),
			'9' => __('Bulk Pricing Based Free Gift - Manual', 'free-gifts-for-woocommerce'),
			'10' => __('Bulk Pricing Based Free Gift - Autotmatic', 'free-gifts-for-woocommerce'),
			'11' => __('Bulk Quantity Based Free Gift - Manual', 'free-gifts-for-woocommerce'),
			'12' => __('Bulk Quantity Based Free Gift - Autotmatic', 'free-gifts-for-woocommerce'),
			'13' => __('Cheapest Cart Based Free Gift - Manual', 'free-gifts-for-woocommerce'),
			'14' => __('Cheapest Cart Based Free Gift - Autotmatic', 'free-gifts-for-woocommerce'),
		));

		return $types;
	}

}

if (!function_exists('fgf_get_rule_type_name')) {

	/**
	 * Get the rule type name.
	 *
	 *  @return string
	 */
	function fgf_get_rule_type_name( $type ) {
		$types = fgf_get_rule_types();
		if (!isset($types[$type])) {
			return '';
		}

		return $types[$type];
	}

}

if (!function_exists('fgf_get_gift_product_selection_types')) {

	/**
	 * Get the gift product selection types
	 *
	 * @since 10.8.0
	 * @return array
	 */
	function fgf_get_gift_product_selection_types() {
		static $types;
		if (isset($types)) {
			return $types;
		}

		/**
		 * This hook is used to alter the gift product selection types.
		 * 
		 * @since 10.8.0
		 */
		$types = apply_filters('fgf_gift_product_selection_types', array(
			'1' => __('Selected Product(s)', 'free-gifts-for-woocommerce'),
			'2' => __('Products from Selected Categories', 'free-gifts-for-woocommerce'),
		));

		return $types;
	}

}

if (!function_exists('fgf_get_subtotal_gift_product_selection_types')) {

	/**
	 * Get the subtotal gift product selection types
	 *
	 * @since 11.3.0
	 * @return array
	 */
	function fgf_get_subtotal_gift_product_selection_types() {
		static $types;
		if (isset($types)) {
			return $types;
		}

		/**
		 * This hook is used to alter the subtotal gift product selection types.
		 * 
		 * @since 11.3.0
		 */
		$types = apply_filters('fgf_subtotal_gift_product_selection_types', array(
			'1' => __('Selected Product(s)', 'free-gifts-for-woocommerce'),
			'2' => __('Products from Selected Categories', 'free-gifts-for-woocommerce'),
		));

		return $types;
	}

}

if (!function_exists('fgf_get_buy_product_selection_types')) {

	/**
	 * Get the Buy product selection types.
	 *
	 * @since 11.3.0
	 * @return array
	 */
	function fgf_get_buy_product_selection_types() {
		static $types;
		if (isset($types)) {
			return $types;
		}

		/**
		 * This hook is used to alter the buy product selection types.
		 * 
		 * @since 11.3.0
		 */
		$types = apply_filters('fgf_buy_product_selection_types', array(
			'1' => __('Product', 'free-gifts-for-woocommerce'),
			'2' => __('Category', 'free-gifts-for-woocommerce'),
		));

		return $types;
	}

}

if (!function_exists('fgf_get_product_selection_types')) {

	/**
	 * Get product selection types.
	 *
	 * @since 11.3.0
	 * @return array
	 */
	function fgf_get_product_selection_types() {
		static $types;
		if (isset($types)) {
			return $types;
		}

		/**
		 * This hook is used to alter the get product selection types.
		 * 
		 * @since 11.3.0
		 */
		$types = apply_filters('fgf_get_product_selection_types', array(
			'1' => __('Selected Product(s)', 'free-gifts-for-woocommerce'),
			'2' => __('Products from Selected Categories', 'free-gifts-for-woocommerce'),
		));

		return $types;
	}

}

if (!function_exists('fgf_get_rule_week_days_options')) {

	/**
	 * Get the rule weekdays options.
	 *
	 * @return array
	 * */
	function fgf_get_rule_week_days_options() {
		return array(
			'1' => __('Monday', 'free-gifts-for-woocommerce'),
			'2' => __('Tuesday', 'free-gifts-for-woocommerce'),
			'3' => __('Wednesday', 'free-gifts-for-woocommerce'),
			'4' => __('Thursday', 'free-gifts-for-woocommerce'),
			'5' => __('Friday', 'free-gifts-for-woocommerce'),
			'6' => __('Saturday', 'free-gifts-for-woocommerce'),
			'7' => __('Sunday', 'free-gifts-for-woocommerce'),
		);
	}

}


if (!function_exists('fgf_display_action')) {

	/**
	 * Display the post action.
	 *
	 * @return string
	 */
	function fgf_display_action( $status, $id, $current_url, $action = false ) {
		switch ($status) {
			case 'edit':
				$status_name = '<span class="dashicons dashicons-edit"></span>';
				$title = __('Edit', 'free-gifts-for-woocommerce');
				break;
			case 'active':
				$status_name = '<img src="' . esc_url(FGF_PLUGIN_URL . '/assets/images/button-on.png') . '"/>';
				$title = __('Activate', 'free-gifts-for-woocommerce');
				break;
			case 'inactive':
				$status_name = '<img src="' . esc_url(FGF_PLUGIN_URL . '/assets/images/button-off.png') . '"/>';
				$title = __('Deactivate', 'free-gifts-for-woocommerce');
				break;
			case 'duplicate':
				$status_name = '<img src="' . esc_url(FGF_PLUGIN_URL . '/assets/images/copy.png') . '"/>';
				$title = __('Duplicate', 'free-gifts-for-woocommerce');
				break;
			default:
				$status_name = '<span class="dashicons dashicons-trash"></span>';
				$title = __('Delete Permanently', 'free-gifts-for-woocommerce');
				break;
		}

		$section_name = 'section';
		if ($action) {
			$section_name = 'action';
		}

		if ('edit' == $status) {
			return '<a href="' . esc_url(
							add_query_arg(
									array(
										$section_name => $status,
										'id' => $id,
									), $current_url
							)
					) . '" title="' . $title . '">' . $status_name . '</a>';
		} else {
			return '<a class="fgf-action fgf-' . $status . '-post" data-action="' . $status . '" href="' . esc_url(
							add_query_arg(
									array(
										'action' => $status,
										'id' => $id,
									), $current_url
							)
					) . '" title="' . $title . '">' . $status_name . '</a>';
		}
	}

}

if (!function_exists('fgf_get_status_label')) {

	/**
	 * Get the status label.
	 * 
	 * @since 1.0.0
	 * @param string $status
	 * @param boolean $html
	 * @return mixed
	 */
	function fgf_get_status_label( $status, $html = true ) {
		$status_object = get_post_status_object($status);
		if (!isset($status_object)) {
			return '';
		}

		return $html ? '<mark class="fgf_status_label ' . esc_attr($status) . '_status"><span >' . esc_html($status_object->label) . '</span></mark>' : esc_html($status_object->label);
	}

}

if (!function_exists('fgf_wc_help_tip')) {

	/**
	 * Display the tool tip based on WC help tip.
	 *
	 *  @return string
	 */
	function fgf_wc_help_tip( $tip, $allow_html = false, $echo = true ) {
		$formatted_tip = wc_help_tip($tip, $allow_html);
		if ($echo) {
			echo wp_kses_post($formatted_tip);
		}

		return $formatted_tip;
	}

}

if (!function_exists('fgf_get_rule_notice_shortcode_details')) {

	/**
	 * Get the rule notice shortcode details.
	 *
	 *  @return array
	 */
	function fgf_get_rule_notice_shortcode_details() {
		static $shortcode_details;

		if (isset($shortcode_details)) {
			return $shortcode_details;
		}

		$shortcode_details = array(
			array(
				'shortcode' => '[free_gift_min_order_total]',
				'desc' => __('The minimum order total required to receive free gift(s)', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[free_gift_min_sub_total]',
				'desc' => __('The minimum cart subtotal required to receive free gift(s)', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[free_gift_min_category_sub_total]',
				'desc' => __('The minimum category subtotal required in the cart to receive free gift(s)', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[free_gift_min_cart_qty]',
				'desc' => __('The minimum cart quantity required to receive free gift(s)', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[free_gift_min_product_count]',
				'desc' => __('The minimum no.of products which has to be purchased to receive free gift(s)', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[cart_order_total]',
				'desc' => __('Display the current Cart Order Total', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[cart_sub_total]',
				'desc' => __('Display the current Cart Sub-Total', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[cart_category_sub_total]',
				'desc' => __('Display the current sum of all Product Prices plus applicable Taxes that belong to a particular category in the cart', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[cart_quantity]',
				'desc' => __('Display the current Cart Quantity', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[cart_product_count]',
				'desc' => __('Display the number of product(s) available in the current cart', 'free-gifts-for-woocommerce'),
			),
		);

		return $shortcode_details;
	}

}

if (!function_exists('fgf_get_bogo_rule_notice_shortcode_details')) {

	/**
	 * Get the BOGO rule notice shortcode details.
	 * 
	 * @since 11.9.0
	 * @return array
	 */
	function fgf_get_bogo_rule_notice_shortcode_details() {
		static $shortcode_details;

		if (isset($shortcode_details)) {
			return $shortcode_details;
		}

		$shortcode_details = array(
			array(
				'shortcode' => '[fgf_bogo_gifts]',
				'desc' => __('Display the BOGO gift(s)', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[fgf_buy_product_qty]',
				'desc' => __('Display the BOGO buy product quantity', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[fgf_gift_product_qty]',
				'desc' => __('Display the BOGO gift product quantity', 'free-gifts-for-woocommerce'),
			),
		);

		return $shortcode_details;
	}

}

if (!function_exists('fgf_get_bulk_rule_notice_shortcode_details')) {

	/**
	 * Get the bulk rule notice shortcode details.
	 * 
	 * @since 12.0.0
	 * @return array
	 */
	function fgf_get_bulk_rule_notice_shortcode_details() {
		static $shortcode_details;

		if (isset($shortcode_details)) {
			return $shortcode_details;
		}

		$shortcode_details = array(
			array(
				'shortcode' => '[free_gift_min_price]',
				'desc' => __('Display the gift minimum price', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[free_gift_max_price]',
				'desc' => __('Display the gift maximum price', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[free_gift_min_qty]',
				'desc' => __('Display the gift minimum quantity', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[free_gift_max_qty]',
				'desc' => __('Display the gift maximum quantity', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[free_gifts]',
				'desc' => __('Display the free gifts', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[free_gift_qty]',
				'desc' => __('Display the gift quantity', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[cart_subtotal]',
				'desc' => __('Display the cart subtotal', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[cart_order_total]',
				'desc' => __('Display the cart order total', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[cart_category_subtotal]',
				'desc' => __('Display the cart category subtotal', 'free-gifts-for-woocommerce'),
			),
			array(
				'shortcode' => '[cart_quantity]',
				'desc' => __('Display the cart quantity', 'free-gifts-for-woocommerce'),
			),
		);

		return $shortcode_details;
	}

}

if (!function_exists('fgf_get_rule_notice_shortcode_tabs')) {

	/**
	 * Get the rule notice shortcode tabs.
	 * 
	 * @since 11.9.0
	 * @return array
	 */
	function fgf_get_rule_notice_shortcode_tabs() {
		static $shortcode_tabs;

		if (isset($shortcode_tabs)) {
			return $shortcode_tabs;
		}

		$shortcode_tabs = array(
				'eligible-notice' =>  __('Eligible Notice', 'free-gifts-for-woocommerce'),
				'bogo-eligible-notice' =>  __('BOGO Eligible Notice', 'free-gifts-for-woocommerce'),
				'bulk-eligible-notice' =>  __('Bulk Eligible Notice', 'free-gifts-for-woocommerce'),
		);

		return $shortcode_tabs;
	}

}

if (!function_exists('fgf_prepare_products_edit_link_by_ids')) {

	/**
	 * Prepare the products edit link by IDs.
	 * 
	 * @since 12.0.0
	 * @param array $product_ids
	 * @return array
	 */
	function fgf_prepare_products_edit_link_by_ids( $product_ids ) {
		$products_link = '';

		foreach ($product_ids as $product_id) {
			$product = wc_get_product($product_id);

			//Return if the product does not exist.
			if (!$product) {
				continue;
			}

			$products_link .= '<a href="' . esc_url(
				add_query_arg(
					array(
						'post' => $product_id,
						'action' => 'edit',
						), admin_url('post.php')
				)
			) . '" >' . $product->get_name() . '</a> , ';
		}

		return rtrim($products_link, ' , ');
	}
}

if (!function_exists('fgf_prepare_terms_edit_link_by_ids')) {

	/**
	 * Prepare the terms edit link by IDs.
	 * 
	 * @since 10.8.0
	 * @param array $categories_ids
	 * @param string $toxonomy
	 * @return array
	 */
	function fgf_prepare_terms_edit_link_by_ids( $categories_ids, $toxonomy = 'product_cat' ) {
		$categories_link = '';

		foreach ($categories_ids as $category_id) {
			$category = get_term_by('id', $category_id, $toxonomy);
			if (!is_object($category)) {
				continue;
			}

			$categories_link .= '<a href = "' . esc_url(
							add_query_arg(
									array(
										'product_cat' => $category->slug,
										'post_type' => 'product',
									), admin_url('edit.php')
							)
					) . '" >' . $category->name . '</a>, ';
		}

		return rtrim($categories_link, ', ');
	}

}

if ( ! function_exists( 'fgf_get_bogo_eligible_notice_on_single_product_display_position_options' ) ) {

	/**
	 * Get the BOGO Eligible notice on single product display position options.
	 *
	 * @since 11.9.0
	 * @return array
	 * */
	function fgf_get_bogo_eligible_notice_on_single_product_display_position_options() {

		/**
		 * This hook is used to alter the BOGO Eligible notice on single product display position options.
		 *        
		 * @since 11.9.0
		 */
		return apply_filters( 'fgf_bogo_eligible_notice_on_single_product_display_position_options', array_column( fgf_get_bogo_eligible_notice_on_single_product_display_positions(), 'label', 'key' ) ) ;
	}

}

if ( ! function_exists( 'fgf_get_bogo_eligible_notice_on_loop_page_display_position_options' ) ) {

	/**
	 * Get the BOGO Eligible notice on loop page display position options.
	 *
	 * @since 11.9.0
	 * @return array
	 * */
	function fgf_get_bogo_eligible_notice_on_loop_page_display_position_options() {

		/**
		 * This hook is used to alter the BOGO Eligible notice on loop page display position options.
		 *        
		 * @since 11.9.0
		 */
		return apply_filters( 'fgf_bogo_eligible_notice_on_loop_page_display_position_options', array_column( fgf_get_bogo_eligible_notice_on_loop_page_display_positions(), 'label', 'key' ) ) ;
	}

}
