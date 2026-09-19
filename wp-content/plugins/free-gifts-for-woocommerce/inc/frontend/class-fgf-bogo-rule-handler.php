<?php

/**
 * BOGO rule notice handler.
 *
 * @since 11.9.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_BOGO_Rule_Notice_Handler')) {

	/**
	 * Class.
	 *
	 * @since 11.9.0
	 */
	class FGF_BOGO_Rule_Notice_Handler extends FGF_Rule_Handler {
		/**
		 * Product ID.
		 *
		 * @since 11.9.0
		 * @var int
		 * */
		protected static $product_id;

		/**
		 * Parent ID.
		 *
		 * @since 11.9.0
		 * @var int
		 * */
		protected static $parent_id;

		/**
		 * BOGO eligible notices.
		 *
		 * @since 11.9.0
		 * */
		protected static $bogo_eligible_notices=array();

		/**
		 * Active BOGO Rule IDs.
		 *
		 * @since 11.9.0
		 * @var array
		 * */
		protected static $active_bogo_rule_ids;

		/**
		 * Get the valid eligible notices.
		 * 
		 * @since 11.9.0
		 * @param int $product
		 * @return array
		 */

		public static function get_bogo_eligible_notices( $product ) {
			if (!is_object($product)) {
				return array();
			}

			if (isset(self::$bogo_eligible_notices[$product->get_id()])) {
				return self::$bogo_eligible_notices[$product->get_id()];
			}

			   self::$product_id=$product->get_id();
			   self::$parent_id= !empty($product->get_parent_id())?$product->get_parent_id():$product->get_id();
			   self::$bogo_eligible_notices[$product->get_id()]=array();
			
			   self::prepare_eligible_notices();

			   return self::$bogo_eligible_notices[$product->get_id()];
		}

		 /**
		 * Prepare valid eligible notices.
		 * 
		 * @since 11.9.0
		 * @return array
		 */
		public static function prepare_eligible_notices() {
			$rule_ids = self::get_active_bogo_rule_ids();
			if (!fgf_check_is_array($rule_ids)) {
				return;
			}
			
			// Initialize the matched rules before prepare the notices.
			if (!isset(self::$bogo_rule_products)) {
				self::matched_rules();
			}
			
			foreach ($rule_ids as $rule_id) {
				// Set the default filter.
				self::set_default_filter();
				self::$rule = fgf_get_rule($rule_id);
				// Restrict the adding free gifts based on coupons applied in the cart.
				if (self::validate_applied_WC_coupons()) {
					continue;
				}

				// Return if rule usage count exists.
				if (!self::validate_rule_usage_count() || !self::validate_rule_user_usage_count()) {
					continue;
				}

				$product_args=array( 'product_id'=>self::$parent_id, 'variation_id'=>self::$product_id );
				if (!self::is_valid_buy_product($product_args)) {
					continue;
				}

				$get_product_quantity=self::get_total_get_product_quantity();
				if ($get_product_quantity && $get_product_quantity<=fgf_get_bogo_products_count_in_cart(self::$product_id, $rule_id, false, self::$rule->get_rule_type())) {
					continue;
				}

				if (!self::is_valid_rule()) {
					continue;
				}
				
				self::$bogo_eligible_notices[self::$product_id][]=self::get_rule_bogo_eligible_notice();
			}
		}

		/**
		 * Get the total get product quantity.
		 *
		 * @since 11.9.0
		 * @return int/bool
		 */
		public static function get_total_get_product_quantity() {
			if ('2' !== self::$rule->get_bogo_gift_repeat()) {
				return self::$rule->get_product_count();
			}
			
			// Return false ,if the repeat mode is unlimited,
			// Repeat limit is empty.
			if ('2' !== self::$rule->get_bogo_gift_repeat_mode() || empty(self::$rule->get_bogo_gift_repeat_limit())) {
				return false;
			}

			return floatval(self::$rule->get_bogo_gift_repeat_limit()) * floatval(self::$rule->get_product_count());
		}

		/**
		 * Get the rule BOGO eligible notice.
		 *
		 * @since 11.9.0
		 * @return string
		 */
		public static function get_rule_bogo_eligible_notice() {
			$shortcodes = self::get_bogo_eligible_notice_shortcodes();

			$notice = fgf_get_rule_translated_string('fgf_rule_bogo_eligible_notice_' . self::$rule->get_id(), self::$rule->get_bogo_eligible_notice());
			$notice = str_replace(array_keys($shortcodes), array_values($shortcodes), $notice);

			return wpautop(wptexturize($notice));
		}

		/**
		 * Get the BOGO eligible notice short codes.
		 *
		 * @since 11.9.0
		 * @return array
		 */
		public static function get_bogo_eligible_notice_shortcodes() {
			if (!is_object(WC()->cart)) {
				return array();
			}

			$shortcodes = array(
				'[fgf_bogo_gifts]' => self::$rule->get_prepared_bogo_gift_details(self::$product_id),
				'[fgf_buy_product_qty]' => self::$rule->get_buy_product_count(),
				'[fgf_gift_product_qty]' => self::$rule->get_product_count(),
			);
			/**
			 * This hook is used to alter the BOGO eligible notice short codes.
			 *
			 * @since 11.9.0
			 */
			return apply_filters('fgf_bogo_eligible_notice_shortcodes', $shortcodes, self::$rule);
		}

		 /**
		 * Get the active BOGO rule IDs.
		 * 
		 * @since 11.9.0
		 * @return array
		 */
		public static function get_active_bogo_rule_ids() {
			if (self::$active_bogo_rule_ids) {
				return self::$active_bogo_rule_ids;
			}

			self::$active_bogo_rule_ids = fgf_get_active_bogo_eligible_notice_rule_ids();

			return self::$active_bogo_rule_ids;
		}
	}
}
