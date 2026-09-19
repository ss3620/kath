<?php

/**
 * Common functions.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

include_once 'fgf-layout-functions.php';
include_once 'fgf-post-functions.php';
include_once 'admin/fgf-admin-functions.php';
include_once 'fgf-template-functions.php';
include_once 'fgf-default-functions.php';
include_once 'fgf-store-api-functions.php';

use Automattic\WooCommerce\Utilities\OrderUtil;

if (!function_exists('fgf_check_is_array')) {

	/**
	 * Check if the resource is array.
	 *
	 * @return bool
	 */
	function fgf_check_is_array( $data ) {
		return ( is_array($data) && !empty($data) );
	}

}

if (!function_exists('fgf_price')) {

	/**
	 *  Display Price based wc_price function
	 *
	 *  @return string
	 */
	function fgf_price( $price, $echo = true ) {

		if ($echo) {
			echo wp_kses_post(wc_price($price));
		}

		return wc_price($price);
	}

}

if (!function_exists('fgf_render_gift_product_image')) {

	/**
	 * Render the gift product image.
	 *
	 * @since 5.0.0
	 * @param object $product
	 * @param array $variation_ids
	 */
	function fgf_render_gift_product_image( $product, $variation_ids, $size = 'woocommerce_thumbnail', $echo = true ) {
		if (fgf_check_is_array($variation_ids)) {
			$variation_id = reset($variation_ids);
			$product = wc_get_product($variation_id);
		}

		if ($echo) {
			echo wp_kses_post($product->get_image($size));
		}

		return $product->get_image();
	}

}

if (!function_exists('fgf_render_product_image')) {

	/**
	 * Display the product image.
	 *
	 * @return mixed
	 */
	function fgf_render_product_image( $product, $size = 'woocommerce_thumbnail', $echo = true ) {

		if ($echo) {
			echo wp_kses_post($product->get_image($size));
		}

		return $product->get_image();
	}

}

if (!function_exists('fgf_get_wc_cart_subtotal')) {

	/**
	 * Get the WC cart subtotal.
	 *
	 * @return string/float
	 */
	function fgf_get_wc_cart_subtotal() {
		if (!is_object(WC()->cart)) {
			return 0;
		}

		if (method_exists(WC()->cart, 'get_subtotal')) {
			$subtotal = ( 'incl' == get_option('woocommerce_tax_display_cart') ) ? WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax() : WC()->cart->get_subtotal();
		} else {
			$subtotal = ( 'incl' == get_option('woocommerce_tax_display_cart') ) ? WC()->cart->subtotal + WC()->cart->subtotal_tax : WC()->cart->subtotal;
		}

		return $subtotal;
	}

}

if (!function_exists('fgf_get_wc_cart_total')) {

	/**
	 * Get the WC cart total.
	 *
	 * @return string/float
	 */
	function fgf_get_wc_cart_total() {
		if (!is_object(WC()->cart)) {
			return 0;
		}

		if (version_compare(WC()->version, '3.2.0', '>=')) {
			$total = WC()->cart->get_total(true);
		} else {
			$total = WC()->cart->total;
		}

		return $total;
	}

}


if (!function_exists('fgf_get_overall_free_gift_products_count_in_cart')) {

	/**
	 * Get the overall free gift products count in the cart.
	 *
	 * @since 10.0.0
	 * @param string $mode
	 * @return int
	 */
	function fgf_get_overall_free_gift_products_count_in_cart( $mode = 'all' ) {
		$count = 0;
		if (!is_object(WC()->cart)) {
			return $count;
		}

		switch ($mode) {
			case 'manual':
				$modes = array( 'manual', 'manual_coupon', 'manual_subtotal', 'manual_bulk_pricing', 'manual_bulk_quantity', 'manual_cheapest_gift' );
				break;

			case 'overall_manual':
				$modes = array( 'manual', 'manual_bogo', 'manual_coupon', 'manual_subtotal', 'manual_bulk_pricing', 'manual_bulk_quantity', 'manual_cheapest_gift' );
				break;

			case 'automatic':
				$modes = array( 'automatic', 'bogo', 'coupon', 'subtotal', 'bulk_pricing', 'bulk_quantity', 'cheapest_gift' );
				break;

			default:
				$modes = array( 'manual', 'automatic', 'bogo', 'manual_bogo', 'coupon', 'manual_coupon', 'manual_subtotal', 'manual_bulk_quantity', 'manual_bulk_pricing', 'manual_cheapest_gift' );
				break;
		}

		foreach (WC()->cart->get_cart() as $item) {
			if (!isset($item['fgf_gift_product'])) {
				continue;
			}

			if (!in_array($item['fgf_gift_product']['mode'], $modes)) {
				continue;
			}

			$count += $item['quantity'];
		}

		return $count;
	}

}

if (!function_exists('fgf_get_normal_taxonomy_products_in_cart')) {

	/**
	 * Get the normal taxonomy products in the cart.
	 * 
	 * @since 11.3.0
	 * @param string $taxonomy
	 * @return array
	 */
	function fgf_get_normal_taxonomy_products_in_cart( $taxonomy = 'product_cat' ) {
		return fgf_get_normal_products_in_cart(true, 'taxonomy', $taxonomy);
	}

}

if (!function_exists('fgf_get_normal_products_in_cart')) {

	/**
	 * Get the normal products in the cart.
	 * 
	 * @since 11.3.0
	 * @param string $with_variation
	 * @param string $type
	 * @param string $taxonomy
	 * @return array
	 */
	function fgf_get_normal_products_in_cart( $with_variation = true, $type = 'product', $taxonomy = 'product_cat' ) {
		if (!is_object(WC()->cart)) {
			return array();
		}

		$normal_product_details = array();
		foreach (WC()->cart->get_cart() as $cart_content) {
			if (isset($cart_content['fgf_gift_product'])) {
				continue;
			}

			switch ($type) {
				case 'taxonomy':
					$normal_product_details = array_merge($normal_product_details, wp_get_post_terms($cart_content['product_id'], $taxonomy, array( 'fields' => 'ids' )));
					break;

				default:
					$normal_product_details[] = $cart_content['product_id'];
					if ($with_variation) {
						$normal_product_details[] = $cart_content['variation_id'];
					}
					break;
			}
		}

		return array_filter(array_unique($normal_product_details));
	}

}

if (!function_exists('fgf_get_free_gift_products_in_cart')) {

	/**
	 * Get the free gift products in the cart.
	 *
	 * @return array/int
	 */
	function fgf_get_free_gift_products_in_cart( $count = false, $automatic = false ) {
		$free_gift_products = array();
		$free_gift_products_count = 0;

		if (is_object(WC()->cart)) {
			foreach (WC()->cart->get_cart() as $key => $value) {
				if (!isset($value['fgf_gift_product'])) {
					continue;
				}

				if ($automatic && 'automatic' == $value['fgf_gift_product']['mode']) {
					$value['fgf_gift_product']['quantity'] = $value['quantity'];
					$free_gift_products_count += $value['quantity'];

					if (isset($free_gift_products[$value['fgf_gift_product']['product_id']])) {

						$free_gift_products[$value['fgf_gift_product']['product_id']][$value['fgf_gift_product']['rule_id']] = $value['quantity'];
					} else {
						$free_gift_products[$value['fgf_gift_product']['product_id']] = array( $value['fgf_gift_product']['rule_id'] => $value['quantity'] );
					}
				} elseif (!$automatic && 'manual' == $value['fgf_gift_product']['mode']) {
					$value['fgf_gift_product']['quantity'] = $value['quantity'];
					$free_gift_products_count += $value['quantity'];

					if (isset($free_gift_products[$value['fgf_gift_product']['product_id']])) {

						$free_gift_products[$value['fgf_gift_product']['product_id']][$value['fgf_gift_product']['rule_id']] = $value['quantity'];
					} else {
						$free_gift_products[$value['fgf_gift_product']['product_id']] = array( $value['fgf_gift_product']['rule_id'] => $value['quantity'] );
					}
				}
			}
		}

		if ($count) {
			return $free_gift_products_count;
		}

		return $free_gift_products;
	}

}

if (!function_exists('fgf_get_bogo_products_count_in_cart')) {

	/**
	 * Get the BOGO products count in the cart.
	 *
	 * @return int
	 */
	function fgf_get_bogo_products_count_in_cart( $buy_product_id, $rule_id, $get_product_id = false, $mode = '3' ) {
		$quantity = 0;
		if (!is_object(WC()->cart)) {
			return $quantity;
		}

		$mode = ( '5' == $mode ) ? 'manual_bogo' : 'bogo';

		foreach (WC()->cart->get_cart() as $key => $value) {
			if (!isset($value['fgf_gift_product']['mode']) || $mode != $value['fgf_gift_product']['mode']) {
				continue;
			}

			if ($rule_id != $value['fgf_gift_product']['rule_id']) {
				continue;
			}

			if ($buy_product_id != $value['fgf_gift_product']['buy_product_id']) {
				continue;
			}

			if ($get_product_id && $get_product_id != $value['fgf_gift_product']['product_id']) {
				continue;
			}

			$quantity += $value['quantity'];
		}

		return $quantity;
	}

}

if (!function_exists('fgf_get_coupon_gift_product_count_in_cart')) {

	/**
	 * Get the coupon gift product count in the cart.
	 *
	 * @return int
	 */
	function fgf_get_coupon_gift_product_count_in_cart( $product_id, $coupon_id, $rule_id, $mode = '4' ) {
		$quantity = 0;
		if (!is_object(WC()->cart)) {
			return $quantity;
		}

		$mode = ( '6' == $mode ) ? 'manual_coupon' : 'coupon';

		foreach (WC()->cart->get_cart() as $key => $value) {
			if (!isset($value['fgf_gift_product']['mode']) || $mode !== $value['fgf_gift_product']['mode']) {
				continue;
			}

			if ($rule_id != $value['fgf_gift_product']['rule_id']) {
				continue;
			}

			if ($coupon_id != $value['fgf_gift_product']['coupon_id']) {
				continue;
			}

			if ($product_id != $value['fgf_gift_product']['product_id']) {
				continue;
			}

			$quantity += $value['quantity'];
		}

		return $quantity;
	}

}

if (!function_exists('fgf_get_gift_product_count_in_cart')) {

	/**
	 * Get the gift product count in the cart.
	 * 
	 * @since 11.1.0
	 * @param int $product_id
	 * @param int $rule_id
	 * @param string $mode
	 * @return int
	 */
	function fgf_get_gift_product_count_in_cart( $product_id, $rule_id, $mode ) {
		$quantity = 0;
		if (!is_object(WC()->cart)) {
			return $quantity;
		}

		foreach (WC()->cart->get_cart() as $cart_content) {
			if (!isset($cart_content['fgf_gift_product']['mode']) || $mode !== $cart_content['fgf_gift_product']['mode']) {
				continue;
			}

			if ($rule_id != $cart_content['fgf_gift_product']['rule_id']) {
				continue;
			}

			if ($product_id != $cart_content['fgf_gift_product']['product_id']) {
				continue;
			}

			$quantity += $cart_content['quantity'];
		}

		return $quantity;
	}

}

if (!function_exists('fgf_get_free_gift_products_count_in_cart')) {

	/**
	 * Get the free gift products count in the cart.
	 *
	 * @since 1.0.0
	 * @param bool $skip_rules
	 * @return integer
	 */
	function fgf_get_free_gift_products_count_in_cart( $skip_rules = false ) {
		$free_gift_products_count = 0;
		if (!is_object(WC()->cart)) {
			return $free_gift_products_count;
		}

		foreach (WC()->cart->get_cart() as $value) {
			if (!isset($value['fgf_gift_product'])) {
				continue;
			}

			if ($skip_rules && ( !isset($value['fgf_gift_product']['mode']) || in_array($value['fgf_gift_product']['mode'], fgf_global_gift_count_skipped_rules()) )) {
				continue;
			}

			$value['fgf_gift_product']['quantity'] = $value['quantity'];
			$free_gift_products_count += $value['quantity'];
		}

		return $free_gift_products_count;
	}

}

if (!function_exists('fgf_global_gift_count_skipped_rules')) {

	/**
	 * Get the global gift count skipped rules.
	 *
	 * @since 12.0.0
	 * @return array
	 */
	function fgf_global_gift_count_skipped_rules() {
		return array( 
					'bogo', 
					'manual_bogo',
					'subtotal',
					'manual_subtotal',
					'bulk_pricing',
					'manual_bulk_pricing',
					'bulk_quantity',
					'manual_bulk_quantity',
					'cheapest_gift',
					'manual_cheapest_gift',
				);
	}
}

if (!function_exists('fgf_get_rule_products_count_in_cart')) {

	/**
	 * Get the rule products count in Cart
	 *
	 * @return int
	 */
	function fgf_get_rule_products_count_in_cart( $rule_id ) {
		$count = 0;
		if (!is_object(WC()->cart)) {
			return $count;
		}

		foreach (WC()->cart->get_cart() as $key => $value) {
			if (!isset($value['fgf_gift_product'])) {
				continue;
			}

			if ($value['fgf_gift_product']['rule_id'] != $rule_id) {
				continue;
			}

			$count += $value['quantity'];
		}

		return $count;
	}

}

if (!function_exists('fgf_cart_contains_only_virtual_products')) {

	/**
	 * Check if the cart contains only virtual products.
	 *
	 * @since 11.3.0
	 * @return boolean
	 */
	function fgf_cart_contains_only_virtual_products() {
		if (!is_object(WC()->cart)) {
			return false;
		}

		$bool = true;
		foreach (WC()->cart->get_cart() as $cart_content) {
			if (isset($cart_content['fgf_gift_product']) || $cart_content['data']->is_virtual()) {
				continue;
			}

			$bool = false;
			break;
		}

		return $bool;
	}

}

if (!function_exists('fgf_get_cart_item_count')) {

	/**
	 * Get the cart item count from the cart.
	 *
	 * @return int
	 */
	function fgf_get_cart_item_count( $exclude_gift = true ) {
		$count = 0;
		if (!is_object(WC()->cart)) {
			return $count;
		}

		foreach (WC()->cart->get_cart() as $key => $value) {
			if (isset($value['fgf_gift_product']) && $exclude_gift) {
				continue;
			}

			$count++;
		}

		return $count;
	}

}

if (!function_exists('fgf_get_wc_cart_category_subtotal')) {

	/**
	 * Get the category subtotal from the cart.
	 *
	 * @param array $category_ids
	 * @param string $taxanomy
	 * @param boolean $consider_subcategories
	 * @param boolean $exclude_discount
	 * @return float
	 */
	function fgf_get_wc_cart_category_subtotal( $category_ids, $taxanomy = 'product_cat', $consider_subcategories = false, $exclude_discount = false ) {
		$cart_total = 0;
		if (!fgf_check_is_array($category_ids)) {
			return $cart_total;
		}

		if (!is_object(WC()->cart)) {
			return $cart_total;
		}

		$overall_category_ids = $category_ids;
		if ($consider_subcategories) {
			foreach ($category_ids as $category_id) {
				$child_categories = get_categories(array( 'taxonomy' => $taxanomy, 'child_of' => $category_id, 'hide_empty' => true ));
				$child_category_ids = array_column($child_categories, 'term_id');
				$overall_category_ids = array_filter(array_unique(array_merge($overall_category_ids, $child_category_ids)));
			}
		}

		$tax_display_cart = get_option('woocommerce_tax_display_cart');
		foreach (WC()->cart->get_cart() as $key => $value) {
			if (isset($value['fgf_gift_product'])) {
				continue;
			}

			$product_categories = get_the_terms($value['product_id'], $taxanomy);
			if (!fgf_check_is_array($product_categories)) {
				continue;
			}

			foreach ($product_categories as $product_category) {
				if (in_array($product_category->term_id, $overall_category_ids)) {
					if ($exclude_discount) {
						$cart_total += ( 'incl' == $tax_display_cart ) ? $value['line_total'] + $value['line_tax'] : $value['line_total'];
					} else {
						$cart_total += ( 'incl' == $tax_display_cart ) ? $value['line_subtotal'] + $value['line_subtotal_tax'] : $value['line_subtotal'];
					}

					break;
				}
			}
		}

		return $cart_total;
	}

}

if (!function_exists('fgf_get_product_count_in_cart')) {

	/**
	 * Get the product count in the cart.
	 *
	 * @return int
	 */
	function fgf_get_product_count_in_cart( $product_id ) {
		$product_count = 0;
		if (!is_object(WC()->cart)) {
			return $product_count;
		}

		foreach (WC()->cart->get_cart() as $key => $value) {

			$cart_product_id = !empty($value['variation_id']) ? $value['variation_id'] : $value['product_id'];

			if ($cart_product_id != $product_id) {
				continue;
			}

			$product_count += $value['quantity'];
		}

		return $product_count;
	}

}

if (!function_exists('fgf_get_address_metas')) {

	/**
	 * Get the user address meta(s).
	 *
	 * @return array
	 */
	function fgf_get_address_metas( $flag ) {

		$address_metas = array(
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'country',
			'postcode',
			'state',
		);

		return 'billing' == $flag ? array_merge($address_metas, array( 'email', 'phone' )) : $address_metas;
	}

}

if (!function_exists('fgf_get_address')) {

	/**
	 * Get the user address.
	 *
	 * @return array
	 */
	function fgf_get_address( $user_id, $flag ) {
		$billing_metas = fgf_get_address_metas($flag);

		foreach ($billing_metas as $each_meta) {
			$billing_address[$each_meta] = get_user_meta($user_id, $flag . '_' . $each_meta, true);
		}

		return $billing_address;
	}

}

if (!function_exists('fgf_get_free_gifts_per_page_column_count')) {

	/**
	 * Get the free gifts per page column count.
	 *
	 * @return int
	 */
	function fgf_get_free_gifts_per_page_column_count() {
		// To avoid pagination if the table pagination is disabled.
		$display_table_pagination = get_option('fgf_settings_gift_display_table_pagination');
		if ('2' == $display_table_pagination) {
			return 10000;
		}

		$per_page = get_option('fgf_settings_free_gift_per_page_column_count', 4);

		if (!$per_page) {
			return 4;
		}

		return $per_page;
	}

}

if (!function_exists('fgf_get_carousel_options')) {

	/**
	 * Get the carousel options.
	 *
	 * @return array
	 */
	function fgf_get_carousel_options() {

		// Declare values.
		$nav = ( 'yes' == get_option('fgf_settings_carousel_navigation') ) ? true : false;
		$auto_play = ( 'yes' == get_option('fgf_settings_carousel_auto_play') ) ? true : false;
		$pagination = ( 'yes' == get_option('fgf_settings_carousel_pagination') ) ? true : false;
		$nav_prev_text = get_option('fgf_settings_carousel_navigation_prevoius_text');
		$nav_next_text = get_option('fgf_settings_carousel_navigation_next_text');
		$desktop_count = get_option('fgf_settings_carousel_gift_per_page', 3);
		$tablet_count = get_option('fgf_settings_carousel_gift_per_page_tablet', 2);
		$mobile_count = get_option('fgf_settings_carousel_gift_per_page_mobile', 1);
		$item_margin = get_option('fgf_settings_carousel_item_margin');
		$item_per_slide = get_option('fgf_settings_carousel_item_per_slide');
		$slide_speed = get_option('fgf_settings_carousel_slide_speed');

		$nav_prev_text = ( empty($nav_prev_text) ) ? '<' : $nav_prev_text;
		$nav_next_text = ( empty($nav_next_text) ) ? '<' : $nav_next_text;
		$desktop_count = ( empty($desktop_count) ) ? '3' : $desktop_count;
		$tablet_count = ( empty($tablet_count) ) ? '2' : $tablet_count;
		$mobile_count = ( empty($mobile_count) ) ? '1' : $mobile_count;
		$item_margin = ( empty($item_margin) ) ? '10' : $item_margin;
		$item_per_slide = ( empty($item_per_slide) ) ? '1' : $item_per_slide;
		$slide_speed = ( empty($slide_speed) ) ? '5000' : $slide_speed;

		return array(
			'desktop_count' => $desktop_count,
			'tablet_count' => $tablet_count,
			'mobile_count' => $mobile_count,
			'item_margin' => $item_margin,
			'nav' => json_encode($nav),
			'nav_prev_text' => $nav_prev_text,
			'nav_next_text' => $nav_next_text,
			'pagination' => json_encode($pagination),
			'item_per_slide' => $item_per_slide,
			'slide_speed' => $slide_speed,
			'auto_play' => json_encode($auto_play),
		);
	}

}

if (!function_exists('fgf_get_rule_translated_string')) {

	/**
	 * Get the rule translated string.
	 *
	 * @return mixed
	 */
	function fgf_get_rule_translated_string( $option_name, $value, $language = null ) {
		/**
		 * This hook is used to alter the rule translated string.
		 *
		 * @since 1.0
		 */
		return apply_filters('fgf_rule_translate_string', $value, $option_name, $language);
	}

}

if (!function_exists('fgf_get_product')) {

	/**
	 * Get the product object by product id.
	 *
	 * @return object/bool
	 */
	function fgf_get_product( $product_id ) {
		/**
		 * This hook is used to validate the product.
		 *
		 * @since 1.0
		 */
		if (!apply_filters('fgf_is_valid_product', true, $product_id)) {
			return false;
		}
		/**
		 * This hook is used to alter the product.
		 *
		 * @since 1.0
		 */
		return apply_filters('fgf_get_product', wc_get_product($product_id), $product_id);
	}

}

if (!function_exists('fgf_rule_available_product_count')) {

	/**
	 * Get the rule available product count.
	 * 
	 * @since 11.2.0
	 * @param object $rule
	 * @param int $product_id
	 * @param boolean/int $buy_product_id
	 * @param boolean/int $coupon_id
	 * @return int
	 */
	function fgf_rule_available_product_count( $rule, $product_id, $buy_product_id = false, $coupon_id = false ) {
		$count = false;
		if (!is_object($rule)) {
			return $count;
		}

		switch ($rule->get_rule_mode()) {
			case 'manual':
				$count = FGF_Rule_Handler::rule_product_exists($rule->get_id(), $product_id);
				break;

			case 'manual_bogo':
				// Manual Bogo.
				$count = FGF_Rule_Handler::get_bogo_rule_product_qty($rule->get_id(), $product_id, $buy_product_id);
				break;

			case 'manual_coupon':
				// Manual coupon.
				$count = FGF_Rule_Handler::get_coupon_rule_product_qty($rule->get_id(), $product_id, $coupon_id);
				break;

			case 'manual_subtotal':
				// Manual subtotal.
				$count = FGF_Rule_Handler::get_subtotal_rule_product_qty($rule->get_id(), $product_id);
				break;

			case 'manual_bulk_pricing':
				// Manual blk pricing.
				$count = FGF_Rule_Handler::get_bulk_pricing_rule_product_qty($rule->get_id(), $product_id);
				break;

			case 'manual_bulk_quantity':
				// Manual bulk quantity.
				$count = FGF_Rule_Handler::get_bulk_quantity_rule_product_qty($rule->get_id(), $product_id);
				break;

			case 'manual_cheapest_gift':
				// Manual cheapest gift.
				$count = FGF_Rule_Handler::get_cheapest_gift_rule_product_qty($rule->get_id(), $product_id);
				break;
		}

		return $count;
	}

}

if (!function_exists('fgf_rule_product_exists')) {

	/**
	 * Check the rule product exists?
	 *
	 * @return bool
	 */
	function fgf_rule_product_exists( $rule, $product_id, $buy_product_id = false, $coupon_id = false ) {
		return fgf_rule_available_product_count($rule, $product_id, $buy_product_id, $coupon_id);
	}

}

if (!function_exists('fgf_get_term_ids')) {

	/**
	 * Get the term ids.
	 *
	 * @return array
	 */
	function fgf_get_term_ids( $object, $taxonomy = 'product_cat' ) {
		if (is_numeric($object)) {
			$object_id = $object;
		} else {
			$object_id = $object->get_id();
		}

		$terms = get_the_terms($object_id, $taxonomy);
		if (false === $terms || is_wp_error($terms)) {
			return array();
		}

		return wp_list_pluck($terms, 'term_id');
	}

}

if (!function_exists('fgf_add_html_inline_style')) {

	/**
	 * Add the custom CSS to HTML elements.
	 *
	 * @since 8.4
	 * @return Mixed
	 */
	function fgf_add_html_inline_style( $content, $css, $full_content = false ) {
		if (!$css || !$content) {
			return $content;
		}

		// Return the content with style css when DOMDocument class not exists.
		if (!class_exists('DOMDocument')) {
			return '<style type="text/css">' . $css . '</style>' . $content;
		}

		if (class_exists('\Pelago\Emogrifier\CssInliner')) {
			// To create a instance with original HTML.
			$css_inliner_class = 'Pelago\Emogrifier\CssInliner';
			$domDocument = $css_inliner_class::fromHtml($content)->inlineCss($css)->getDomDocument();
			// Removing the elements with display:none style declaration from the content.
			$html_pruner_class = 'Pelago\Emogrifier\HtmlProcessor\HtmlPruner';
			$html_pruner_class::fromDomDocument($domDocument)->removeElementsWithDisplayNone();
			// Converts a few style attributes values to visual HTML attributes.
			$attribute_converter_class = 'Pelago\Emogrifier\HtmlProcessor\CssToAttributeConverter';
			$visual_html = $attribute_converter_class::fromDomDocument($domDocument)->convertCssToVisualAttributes();

			$content = ( $full_content ) ? $visual_html->render() : $visual_html->renderBodyContent();
		} elseif (class_exists('\Pelago\Emogrifier')) {
			$emogrifier_class = 'Pelago\Emogrifier';
			$emogrifier = new Emogrifier($content, $css);
			$content = ( $full_content ) ? $emogrifier->emogrify() : $emogrifier->emogrifyBodyContent();
		} elseif (version_compare(WC_VERSION, '4.0', '<')) {
			$emogrifier_class = 'Emogrifier';
			if (!class_exists($emogrifier_class)) {
				include_once dirname(WC_PLUGIN_FILE) . '/includes/libraries/class-emogrifier.php';
			}

			$emogrifier = new Emogrifier($content, $css);
			$content = ( $full_content ) ? $emogrifier->emogrify() : $emogrifier->emogrifyBodyContent();
		}

		return $content;
	}

}

if (!function_exists('fgf_get_rule_valid_gift_products')) {

	/**
	 * Get the rule valid gift products.
	 *
	 * @since 9.6.0
	 * @return array
	 */
	function fgf_get_rule_valid_gift_products() {
		/**
		 * This hook is used to alter the rule valid gift products.
		 *
		 * @since 9.6.0
		 */
		return apply_filters('fgf_rule_valid_gift_products', FGF_Rule_Handler::get_valid_gift_products());
	}

}

if (!function_exists('fgf_get_manual_gift_products_progress_bar_width')) {

	/**
	 * Get the manual gift products progress bar width.
	 *
	 * @since 9.8.0
	 * @return float
	 */
	function fgf_get_manual_gift_products_progress_bar_width() {
		$width = 0;
		if (FGF_Rule_Handler::get_added_gift_products_count()) {
			$width = ( intval(FGF_Rule_Handler::get_added_gift_products_count()) / intval(FGF_Rule_Handler::get_total_gift_products_count()) * 100 );
		}

		/**
		 * This hook is used to alter the manual gift products progress bar width.
		 *
		 * @since 9.8.0
		 */
		return apply_filters('fgf_manual_gift_products_progress_bar_width', $width);
	}

}

if (!function_exists('fgf_doing_ajax')) {

	/**
	 * Doing ajax.
	 *
	 * @since 9.9.0
	 * @return boolean
	 */
	function fgf_doing_ajax() {
		//Return true if the request via ajax.
		if (defined('DOING_AJAX') || !empty($_GET['wc-ajax'])) {
			return true;
		}

		return false;
	}

}

if (!function_exists('fgf_get_manual_rule_types')) {

	/**
	 * Get the manual rule types.
	 *
	 * @since 10.0.0
	 * @return array
	 */
	function fgf_get_manual_rule_types() {
		/**
		 * This hook is alter the manual rule types.
		 *
		 * @since 10.0.0
		 */
		return apply_filters('fgf_manual_rule_types', array( 'manual', 'manual_bogo', 'manual_coupon', 'manual_subtotal', 'manual_bulk_pricing', 'manual_bulk_quantity', 'manual_cheapest_gift' ));
	}

}

if (!function_exists('fgf_get_automatic_rule_types')) {

	/**
	 * Get the automatic rule types.
	 *
	 * @since 10.0.0
	 * @return array
	 */
	function fgf_get_automatic_rule_types() {
		/**
		 * This hook is alter the automatic rule types.
		 *
		 * @since 10.0.0
		 */
		return apply_filters('fgf_automatic_rule_types', array( 'automatic', 'bogo', 'coupon', 'subtotal', 'bulk_pricing', 'bulk_quantity', 'cheapest_gift' ));
	}

}

if (!function_exists('fgf_get_overall_rule_types')) {

	/**
	 * Get the overall rule types.
	 *
	 * @since 10.0.0
	 * @return array
	 */
	function fgf_get_overall_rule_types() {
		return array_merge(fgf_get_manual_rule_types(), fgf_get_automatic_rule_types());
	}

}

if (!function_exists('fgf_show_automatic_free_gift_product_cart_item_remove_link')) {

	/**
	 * Show automatic free gift product cart item remove link.
	 *
	 * @since 10.1.0
	 * @return bool
	 */
	function fgf_show_automatic_free_gift_product_cart_item_remove_link() {
		return 'yes' === get_option('fgf_settings_show_automatic_free_gift_product_remove_link', 'no') ? true : false;
	}

}

if (!function_exists('fgf_get_removed_automatic_free_gift_products_from_session')) {

	/**
	 * Get removed automatic free gift products from session
	 *
	 * @since 10.1.0
	 * @return array
	 */
	function fgf_get_removed_automatic_free_gift_products_from_session() {
		return array_filter(WC()->session->get('fgf_removed_automatic_free_gift_products', array()));
	}

}

if (!function_exists('fgf_get_order_type')) {

	/**
	 * Get the order type by Order ID.
	 *
	 * @since 10.5.0
	 * @param int $order_id
	 * @return string
	 */
	function fgf_get_order_type( $order_id ) {
		if (!class_exists('OrderUtil')) {
			return get_post_type($order_id);
		}

		return OrderUtil::get_order_type($order_id);
	}

}

if (!function_exists('fgf_get_free_gift_product_price')) {

	/**
	 * Get the free gift product price.
	 *
	 * @since 10.8.0
	 * @return string
	 */
	function fgf_get_free_gift_product_price() {
		/**
		 * This hook is used to alter the free gift product price.
		 * 
		 * @since 10.8.0
		 */
		return apply_filters('fgf_free_gift_product_price', get_option('fgf_settings_gift_product_price') ? get_option('fgf_settings_gift_product_price') : 0);
	}

}

if (!function_exists('fgf_add_wc_notice')) {

	/**
	 * Add a WC notice.
	 * 
	 * @since 11.0.0
	 * @param string $message
	 * @param string $notice_type
	 * @param array $data
	 */
	function fgf_add_wc_notice( $message, $notice_type = 'success', $data = array() ) {
		if (fgf_is_block_cart() || fgf_is_block_checkout() || wc_has_notice($message)) {
			return;
		}

		wc_add_notice($message, $notice_type, $data);
	}

}

if (!function_exists('fgf_date_contain_time')) {

	/**
	 * Check if the date contain time.
	 * 
	 * @since 11.5.0
	 * @param string $date
	 * @return boolean
	 */
	function fgf_date_contain_time( $date ) {
		$exploded_date = explode(' ', $date);

		return isset($exploded_date['1']) ? true : false;
	}

}

if ( ! function_exists( 'fgf_get_bogo_eligible_notice_on_single_product_display_positions' ) ) {

	/**
	 * Get the BOGO Eligible notice on single product display positions.
	 *
	 * @since 11.9.0
	 * @return array
	 * */
	function fgf_get_bogo_eligible_notice_on_single_product_display_positions() {

		/**
		 * This hook is used to alter the BOGO Eligible notice on single product display positions.
		 *        
		 * @since 11.9.0
		 */
		return apply_filters( 'fgf_bogo_eligible_notice_on_single_product_display_positions', array(
			'1'     => array(
				'key'      => '1',
				'label'    => esc_html__( 'Before the Add to Cart Button', 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_before_add_to_cart_button',
				'priority' => 10,
			),
			'2'         => array(
				'key'      => '2',
				'label'    => esc_html__( 'After the Add to Cart Button', 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_before_single_product',
				'priority' => 10,
			),
			'3'       => array(
				'key'      => '3',
				'label'    => esc_html__( 'Before the Single Product', 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_before_add_to_cart_form',
				'priority' => 10,
			),
			'4'      => array(
				'key'      => '4',
				'label'    => esc_html__( 'Before the Add to Cart Form', 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_after_add_to_cart_button',
				'priority' => 10,
			),
			'5'        => array(
				'key'      => '5',
				'label'    => esc_html__( 'After the Add to Cart Form', 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_after_add_to_cart_form',
				'priority' => 10,
			),
			'6'            => array(
				'key'      => '6',
				'label'    => esc_html__( 'Before the Product Meta', 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_product_meta_start',
				'priority' => 10,
			),
			'7'              => array(
				'key'      => '7',
				'label'    => esc_html__( 'After the Product Meta', 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_product_meta_end',
				'priority' => 10,
			),
			'8' => array(
				'key'      => '8',
				'label'    => esc_html__( 'Before the Single Product Summary', 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_before_single_product_summary',
				'priority' => 10,
			),
			'9'  => array(
				'key'      => '9',
				'label'    => esc_html__( 'After the Single Product Summary', 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_after_single_product_summary',
				'priority' => 10,
			),
				)
				) ;
	}

}

if ( ! function_exists( 'fgf_get_bogo_eligible_notice_on_loop_page_display_positions' ) ) {

	/**
	 * Get the BOGO Eligible notice on loop page display positions.
	 *
	 * @since 11.9.0
	 * @return array
	 * */
	function fgf_get_bogo_eligible_notice_on_loop_page_display_positions() {

		/**
		 * This hook is used to alter the BOGO Eligible notice on loop page display positions.
		 *        
		 * @since 11.9.0
		 */
		return apply_filters( 'fgf_bogo_eligible_notice_on_loop_page_display_positions', array(
			'1'  => array(
				'key'      => '1',
				'label'    => esc_html__( "Before Item's Title", 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_before_shop_loop_item_title',
				'priority' => 10,
			),
			'2' => array(
				'key'      => '2',
				'label'    => esc_html__( "After Item's Title", 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_after_shop_loop_item_title',
				'priority' => 10,
			),
			'3'        => array(
				'key'      => '3',
				'label'    => esc_html__( 'Before Shop Loop Item', 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_before_shop_loop_item',
				'priority' => 10,
			),
			'4'       => array(
				'key'      => '4',
				'label'    => esc_html__( 'After Shop Loop Item', 'free-gifts-for-woocommerce' ),
				'hook'     => 'woocommerce_after_shop_loop_item',
				'priority' => 10,
			),
				)
				) ;
	}

}

if (!function_exists('fgf_parsed_bulk_quantity_rules')) {

	/**
	 * Parsed bulk quantity rules.
	 *
	 * @since 12.0.0
	 * @param array $bulk_quantity_rules
	 * @return array
	 */
	function fgf_parsed_bulk_quantity_rules( $bulk_quantity_rules ) {
		return wp_parse_args($bulk_quantity_rules, fgf_default_bulk_quantity_rule_options());
	}

}

if (!function_exists('fgf_default_bulk_quantity_rule_options')) {

	/**
	 * Get the default bulk quantity rule options.
	 *
	 * @since 12.0.0
	 * @return array
	 */
	function fgf_default_bulk_quantity_rule_options() {
		static $options;
		if (isset($options)) {
			return $options;
		}

		/**
		 * This hook is used to alter the default bulk quantity rule options.
		 * 
		 * @since 12.0.0
		 */
		$types = apply_filters('fgf_default_bulk_quantity_rule_options', array(
			'minimum' => '1',
			'maximum' => '1',
			'gift_quantity' => '1',
			'gift_type' => '1',
			'gift_products' => array(),
			'gift_categories' => array(),
		));

		return $types;
	}

}

if (!function_exists('fgf_parsed_bulk_pricing_rules')) {

	/**
	 * Parsed bulk pricing rules.
	 *
	 * @since 12.0.0
	 * @param array $bulk_pricing_rules
	 * @return array
	 */
	function fgf_parsed_bulk_pricing_rules( $bulk_pricing_rules ) {
		return wp_parse_args($bulk_pricing_rules, fgf_default_bulk_pricing_rule_options());
	}

}

if (!function_exists('fgf_default_bulk_pricing_rule_options')) {

	/**
	 * Get the default bulk pricing rule options.
	 *
	 * @since 12.0.0
	 * @return array
	 */
	function fgf_default_bulk_pricing_rule_options() {
		static $options;
		if (isset($options)) {
			return $options;
		}

		/**
		 * This hook is used to alter the default bulk pricing rule options.
		 * 
		 * @since 12.0.0
		 */
		$types = apply_filters('fgf_default_bulk_pricing_rule_options', array(
			'minimum' => '1',
			'maximum' => '1',
			'gift_quantity' => '1',
			'gift_type' => '1',
			'gift_products' => array(),
			'gift_categories' => array(),
		));

		return $types;
	}

}

if (!function_exists('fgf_get_cheapset_cart_item_from_cart')) {

	/**
	 * Get the cheapest cart item from the cart.
	 * 
	 * @since 12.0.0
	 * @param string $price_consider_type
	 * @return boolean/int
	 */
	function fgf_get_cheapset_cart_item_from_cart( $price_consider_type = '1' ) {
		if (!is_object(WC()->cart)) {
			return false;
		}

		$product_ids = array();
		$tax_display_cart = get_option('woocommerce_tax_display_cart');
		foreach (WC()->cart->get_cart() as $cart_content) {
			if (isset($cart_content['fgf_gift_product'])) {
				continue;
			}

			$product_id = !empty($cart_content['variation_id']) ? $cart_content['variation_id'] : $cart_content['product_id'];

			switch ($price_consider_type) {
				// Sale price
				case '4':
					$price = get_post_meta($product_id, '_sale_price', true);
					break;
				// Regular price
				case '3':
					$price = get_post_meta($product_id, '_regular_price', true);
					break;
				// Cart product subtotal price
				case '2':
					$price = ( 'incl' == $tax_display_cart ) ? $cart_content['line_subtotal'] + $cart_content['line_subtotal_tax'] : $cart_content['line_subtotal'];
					break;
				default:
					// Cart product price
					$price = $cart_content['data']->get_price();
					break;
			}

			$product_ids[$product_id] = $price;
		}

		if (!fgf_check_is_array($product_ids)) {
			return false;
		}

		asort($product_ids);

		return key($product_ids);
	}

}

if (!function_exists('fgf_convert_price_to_base_currency')) {

	/**
	 * Convert price to base currency based on other plugins like currency switcher plugins.
	 * 
	 * @since 12.0.0
	 * @param float $price
	 * @param object/boolean $rule
	 * @return float
	 */
	function fgf_convert_price_to_base_currency( $price, $rule = false ) {
		/**
		 * Convert price to base currency.
		 * 
		 * @since 12.0.0
		 */
		return floatval(apply_filters('fgf_convert_price_to_base_currency', $price, $rule));
	}

}
