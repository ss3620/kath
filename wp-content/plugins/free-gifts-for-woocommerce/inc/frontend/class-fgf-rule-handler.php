<?php

/**
 * Rule Handler.
 *
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
if (!class_exists('FGF_Rule_Handler')) {

	/**
	 * Class.
	 *
	 * @since 1.0.0
	 */
	class FGF_Rule_Handler {

		/**
		 * Gift products.
		 *
		 * @since 1.0.0
		 * @var array
		 * */
		protected static $gift_products;

		/**
		 * Valid gift products.
		 *
		 * @since 9.6.0
		 * @var array
		 */
		protected static $valid_gift_products;

		/**
		 * Manual Gift Products.
		 *
		 * @since 1.0.0
		 * @var array
		 * */
		protected static $manual_gift_products;

		/**
		 * Automatic Gift Products.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $automatic_gift_products;

		/**
		 * BOGO Gift Products.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $bogo_gift_products;

		/**
		 * Manual BOGO Gift Products.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $manual_bogo_gift_products;

		/**
		 * Coupon Gift Products.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $coupon_gift_products;

		/**
		 * Coupon Manual Gift Products.
		 *
		 * @since 10.0.0
		 * @var array
		 */
		protected static $coupon_manual_gift_products;

		/**
		 * Subtotal Gift Products.
		 *
		 * @since 11.1.0
		 * @var array
		 */
		protected static $subtotal_gift_products;

		/**
		 * Subtotal Manual Gift Products.
		 *
		 * @since 11.1.0
		 * @var array
		 */
		protected static $subtotal_manual_gift_products;

		/**
		 * Bulk Pricing Gift Products.
		 *
		 * @since 12.0.0
		 * @var array
		 */
		protected static $bulk_pricing_gift_products;

		/**
		 * Bulk Pricing Manual Gift Products.
		 *
		 * @since 12.0.0
		 * @var array
		 */
		protected static $bulk_pricing_manual_gift_products;

		/**
		 * Bulk Quantity Gift Products.
		 *
		 * @since 12.0.0
		 * @var array
		 */
		protected static $bulk_quantity_gift_products;

		/**
		 * Bulk Quantity Manual Gift Products.
		 *
		 * @since 12.0.0
		 * @var array
		 */
		protected static $bulk_quantity_manual_gift_products;

		/**
		 * Cheapest Gift Products.
		 *
		 * @since 12.0.0
		 * @var array
		 */
		protected static $cheapest_gift_products;

		/**
		 * Cheapest Manual Gift Products.
		 *
		 * @since 12.0.0
		 * @var array
		 */
		protected static $cheapest_manual_gift_products;

		/**
		 * Overall Manual Gift Products.
		 *
		 * @since 1.0.0
		 * @var array
		 * */
		protected static $overall_manual_gift_products;

		/**
		 * Manual Product Already Exists.
		 *
		 * @since 1.0.0
		 * @var array
		 * */
		protected static $manual_product_already_exists = array();

		/**
		 * Manual product exists.
		 *
		 * @since 1.0.0
		 * @var bool
		 * */
		protected static $manual_product_exists;

		/**
		 * Cart Notices.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $cart_notices;

		/**
		 * Rule IDs.
		 *
		 * @since 1.0.0
		 * @var array
		 * */
		protected static $rule_ids;

		/**
		 * Rule.
		 *
		 * @since 1.0.0
		 * @var object
		 * */
		protected static $rule;

		/**
		 * Active Rule IDs.
		 *
		 * @since 1.0.0
		 * @var array
		 * */
		protected static $active_rule_ids;

		/**
		 * Manual Rule Products.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $manual_rule_products;

		/**
		 * Automatic Rule Products.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $automatic_rule_products;

		/**
		 * BOGO Rule Products.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $bogo_rule_products;

		/**
		 * Coupon rule products.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $coupon_rule_products;

		/**
		 * Subtotal rule products.
		 *
		 * @since 11.1.0
		 * @var array
		 */
		protected static $subtotal_rule_products;

		/**
		 * Bulk pricing rule products.
		 *
		 * @since 12.0.0
		 * @var array
		 */
		protected static $bulk_pricing_rule_products;

		/**
		 * Bulk quantity rule products.
		 *
		 * @since 12.0.0
		 * @var array
		 */
		protected static $bulk_quantity_rule_products;

		/**
		 * Cheapest rule products.
		 *
		 * @since 12.0.0
		 * @var array
		 */
		protected static $cheapest_rule_products;

		/**
		 * Manual Gift Products In cart
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $manual_gift_products_in_cart;

		/**
		 * Automatic Gift Products In cart
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $automatic_gift_products_in_cart;

		/**
		 * Date filter.
		 *
		 * @since 1.0.0
		 * @var bool
		 * */
		protected static $date_filter;

		/**
		 * Criteria filter.
		 *
		 * @since 1.0.0
		 * @var bool
		 * */
		protected static $criteria_filter;

		/**
		 * Product filter.
		 *
		 * @since 1.0.0
		 * @var bool
		 * */
		protected static $product_filter;

		/**
		 * User filter.
		 *
		 * @since 1.0.0
		 * @var bool
		 * */
		protected static $user_filter;

		/**
		 * Rule gift products count.
		 *
		 * @since 9.8.0
		 * @var int
		 */
		protected static $rule_gift_products_count;

		/**
		 * BOGO manual rule gift products count.
		 *
		 * @since 10.0.0
		 * @var int
		 */
		protected static $bogo_manual_rule_gift_products_count;

		/**
		 * Subtotal manual rule gift products count.
		 *
		 * @since 11.1.0
		 * @var int
		 */
		protected static $subtotal_manual_rule_gift_products_count;

		/**
		 * Bulk pricing manual rule gift products count.
		 *
		 * @since 12.0.0
		 * @var int
		 */
		protected static $bulk_pricing_manual_rule_gift_products_count;

		/**
		 * Bulk quantity manual rule gift products count.
		 *
		 * @since 12.0.0
		 * @var int
		 */
		protected static $bulk_quantity_manual_rule_gift_products_count;

		/**
		 * Cheapest manual rule gift products count.
		 *
		 * @since 12.0.0
		 * @var int
		 */
		protected static $cheapest_manual_rule_gift_products_count;

		/**
		 * Added gift products count.
		 *
		 * @since 9.8.0
		 * @var int
		 */
		protected static $added_gift_products_count;

		/**
		 * Unlimited products of rule exists.
		 *
		 * @since 10.1.0
		 * @var boolean
		 */
		protected static $rule_unlimited_products_exists = false;

		/**
		 * Applied coupon in the cart.
		 *
		 * @since 10.7.0
		 * @var boolean
		 */
		protected static $applied_coupon_in_cart;

		/**
		 * Prepare matched rule gift products.
		 *
		 * @since 1.0.0
		 */
		public static function prepare_matched_rule_gift_products() {
			$matched_rules = self::matched_rules();

			self::$manual_gift_products = $matched_rules['manual'];
			self::$automatic_gift_products = $matched_rules['automatic'];
			self::$bogo_gift_products = $matched_rules['bogo'];
			self::$manual_bogo_gift_products = $matched_rules['manual_bogo'];
			self::$coupon_gift_products = $matched_rules['coupon'];
			self::$coupon_manual_gift_products = $matched_rules['manual_coupon'];
			self::$subtotal_gift_products = $matched_rules['subtotal'];
			self::$subtotal_manual_gift_products = $matched_rules['manual_subtotal'];
			self::$bulk_pricing_gift_products = $matched_rules['bulk_pricing'];
			self::$bulk_pricing_manual_gift_products = $matched_rules['manual_bulk_pricing'];
			self::$bulk_quantity_gift_products = $matched_rules['bulk_quantity'];
			self::$bulk_quantity_manual_gift_products = $matched_rules['manual_bulk_quantity'];
			self::$cheapest_gift_products = $matched_rules['cheapest_gift'];
			self::$cheapest_manual_gift_products = $matched_rules['manual_cheapest_gift'];
			self::$cart_notices = $matched_rules['notices'];
			self::$overall_manual_gift_products = array_merge($matched_rules['manual'], $matched_rules['manual_bogo'], $matched_rules['manual_coupon'], $matched_rules['manual_subtotal'], $matched_rules['manual_bulk_pricing'], $matched_rules['manual_bulk_quantity'], $matched_rules['manual_cheapest_gift']);
		}

		/**
		 * Has reached maximum gift count?
		 *
		 * @since 10.1.0
		 * @return boolean
		 */
		public static function has_reached_maximum_gift_count() {
			$maximum_gift_count = floatval(get_option('fgf_settings_gifts_count_per_order'));
			if (!$maximum_gift_count) {
				return false;
			}

			// Validate the maximum gift products added in the cart.
			if ($maximum_gift_count > fgf_get_overall_free_gift_products_count_in_cart('manual')) {
				return false;
			}

			return true;
		}

		/**
		 * Get the added gift products count.
		 *
		 * @since 9.8.0
		 * @return int
		 */
		public static function get_added_gift_products_count() {
			if (isset(self::$added_gift_products_count)) {
				return self::$added_gift_products_count;
			}

			self::$added_gift_products_count = fgf_get_overall_free_gift_products_count_in_cart('overall_manual');

			return self::$added_gift_products_count;
		}

		/**
		 * Get the rule gift products count.
		 *
		 * @since 9.8.0
		 * @return int
		 */
		public static function get_rule_gift_products_count() {
			if (isset(self::$rule_gift_products_count)) {
				return self::$rule_gift_products_count;
			}

			self::prepare_matched_rule_gift_products();

			return self::$rule_gift_products_count;
		}

		/**
		 * Get the BOGO manual rule gift products count.
		 *
		 * @since 10.0.0
		 * @return int
		 */
		public static function get_bogo_manual_rule_gift_products_count() {
			if (isset(self::$bogo_manual_rule_gift_products_count)) {
				return self::$bogo_manual_rule_gift_products_count;
			}

			self::prepare_matched_rule_gift_products();

			return self::$bogo_manual_rule_gift_products_count;
		}

		/**
		 * Get the subtotal manual rule gift products count.
		 *
		 * @since 11.1.0
		 * @return int
		 */
		public static function get_subtotal_manual_rule_gift_products_count() {
			if (isset(self::$subtotal_manual_rule_gift_products_count)) {
				return self::$subtotal_manual_rule_gift_products_count;
			}

			self::prepare_matched_rule_gift_products();

			return self::$subtotal_manual_rule_gift_products_count;
		}

		/**
		 * Get the bulk pricing manual rule gift products count.
		 *
		 * @since 12.0.0
		 * @return int
		 */
		public static function get_bulk_pricing_manual_rule_gift_products_count() {
			if (isset(self::$bulk_pricing_manual_rule_gift_products_count)) {
				return self::$bulk_pricing_manual_rule_gift_products_count;
			}

			self::prepare_matched_rule_gift_products();

			return self::$bulk_pricing_manual_rule_gift_products_count;
		}

		/**
		 * Get the bulk quantity manual rule gift products count.
		 *
		 * @since 12.0.0
		 * @return int
		 */
		public static function get_bulk_quantity_manual_rule_gift_products_count() {
			if (isset(self::$bulk_quantity_manual_rule_gift_products_count)) {
				return self::$bulk_quantity_manual_rule_gift_products_count;
			}

			self::prepare_matched_rule_gift_products();

			return self::$bulk_quantity_manual_rule_gift_products_count;
		}

		/**
		 * Get the cheapest manual rule gift products count.
		 *
		 * @since 12.0.0
		 * @return int
		 */
		public static function get_cheapest_manual_rule_gift_products_count() {
			if (isset(self::$cheapest_manual_rule_gift_products_count)) {
				return self::$cheapest_manual_rule_gift_products_count;
			}

			self::prepare_matched_rule_gift_products();

			return self::$cheapest_manual_rule_gift_products_count;
		}

		/**
		 * Get the total gift products count.
		 *
		 * @since 9.7.0
		 * @return int
		 */
		public static function get_total_gift_products_count() {
			$per_order_gifts_count = get_option('fgf_settings_gifts_count_per_order');

			if (self::manual_product_exists() && 'yes' === get_option('fgf_settings_gifts_selection_per_user')) {
				$total_gift_products_count = self::$rule_unlimited_products_exists ? $per_order_gifts_count : self::get_rule_gift_products_count();
				$total_gift_products_count = ( $per_order_gifts_count && ( $total_gift_products_count > $per_order_gifts_count ) ) ? $per_order_gifts_count : $total_gift_products_count;
			} else {
				$total_gift_products_count = ( $per_order_gifts_count ) ? ( ( $per_order_gifts_count > self::get_rule_gift_products_count() ) ? self::get_rule_gift_products_count() : $per_order_gifts_count ) : self::get_rule_gift_products_count();
			}

			$total_gift_products_count += self::get_bogo_manual_rule_gift_products_count();
			$total_gift_products_count += self::get_subtotal_manual_rule_gift_products_count();
			$total_gift_products_count += self::get_bulk_pricing_manual_rule_gift_products_count();
			$total_gift_products_count += self::get_bulk_quantity_manual_rule_gift_products_count();
			$total_gift_products_count += self::get_cheapest_manual_rule_gift_products_count();

			/**
			 * This hook is used to alter the total gift products count.
			 *
			 * @since 9.7.0
			 */
			return apply_filters('fgf_total_gift_products_count', intval($total_gift_products_count));
		}

		/**
		 * Get the remaining gift products count.
		 *
		 * @since 9.7.0
		 * @return int
		 */
		public static function get_remaining_gift_products_count() {
			/**
			 * This hook is used to alter the remaining gift products count.
			 *
			 * @since 9.7.0
			 */
			return apply_filters('fgf_remaining_gift_products_count', self::get_total_gift_products_count() - self::get_added_gift_products_count());
		}

		/**
		 * Get overall manual gift products.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_overall_manual_gift_products() {
			if (isset(self::$overall_manual_gift_products)) {
				return self::$overall_manual_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$overall_manual_gift_products;
		}

		/**
		 * Get the manual gift products.
		 *
		 * @since 1.0.0
		 */
		public static function get_manual_gift_products() {
			if (isset(self::$manual_gift_products)) {
				return self::$manual_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$manual_gift_products;
		}

		/**
		 * Get automatic gift products.
		 */
		public static function get_automatic_gift_products() {

			if (isset(self::$automatic_gift_products)) {
				return self::$automatic_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$automatic_gift_products;
		}

		/**
		 * Manual product exists?
		 *
		 * @return bool
		 */
		public static function manual_product_exists() {

			if (isset(self::$manual_product_exists)) {
				return self::$manual_product_exists;
			}

			self::prepare_matched_rule_gift_products();

			return self::$manual_product_exists;
		}

		/**
		 * Get BOGO gift products.
		 *
		 * @return array
		 */
		public static function get_bogo_gift_products() {

			if (isset(self::$bogo_gift_products)) {
				return self::$bogo_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$bogo_gift_products;
		}

		/**
		 * Get manual BOGO gift products.
		 *
		 * @return array
		 */
		public static function get_manual_bogo_gift_products() {

			if (isset(self::$manual_bogo_gift_products)) {
				return self::$manual_bogo_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$manual_bogo_gift_products;
		}

		/**
		 * Get coupon gift products.
		 */
		public static function get_coupon_gift_products() {

			if (isset(self::$coupon_gift_products)) {
				return self::$coupon_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$coupon_gift_products;
		}

		/**
		 * Get the coupon manual gift products.
		 *
		 * @since 10.0.0
		 * @return array
		 */
		public static function get_coupon_manual_gift_products() {
			if (isset(self::$coupon_manual_gift_products)) {
				return self::$coupon_manual_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$coupon_manual_gift_products;
		}

		/**
		 * Get subtotal gift products.
		 * 
		 * @since 11.1.0
		 * @return array
		 */
		public static function get_subtotal_gift_products() {
			if (isset(self::$subtotal_gift_products)) {
				return self::$subtotal_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$subtotal_gift_products;
		}

		/**
		 * Get the subtotal manual gift products.
		 *
		 * @since 11.1.0
		 * @return array
		 */
		public static function get_subtotal_manual_gift_products() {
			if (isset(self::$subtotal_manual_gift_products)) {
				return self::$subtotal_manual_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$subtotal_manual_gift_products;
		}

		/**
		 * Get the bulk pricing gift products.
		 *
		 * @since 12.0.0
		 * @return array
		 */
		public static function get_bulk_pricing_gift_products() {
			if (isset(self::$bulk_pricing_gift_products)) {
				return self::$bulk_pricing_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$bulk_pricing_gift_products;
		}

		/**
		 * Get the bulk pricing manual gift products.
		 *
		 * @since 12.0.0
		 * @return array
		 */
		public static function get_bulk_pricing_manual_gift_products() {
			if (isset(self::$bulk_pricing_manual_gift_products)) {
				return self::$bulk_pricing_manual_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$bulk_pricing_manual_gift_products;
		}

		/**
		 * Get the bulk quantity gift products.
		 *
		 * @since 12.0.0
		 * @return array
		 */
		public static function get_bulk_quantity_gift_products() {
			if (isset(self::$bulk_quantity_gift_products)) {
				return self::$bulk_quantity_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$bulk_quantity_gift_products;
		}

		/**
		 * Get the bulk quantity manual gift products.
		 *
		 * @since 12.0.0
		 * @return array
		 */
		public static function get_bulk_quantity_manual_gift_products() {
			if (isset(self::$bulk_quantity_manual_gift_products)) {
				return self::$bulk_quantity_manual_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$bulk_quantity_manual_gift_products;
		}

		/**
		 * Get the cheapest gift products.
		 *
		 * @since 12.0.0
		 * @return array
		 */
		public static function get_cheapest_gift_products() {
			if (isset(self::$cheapest_gift_products)) {
				return self::$cheapest_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$cheapest_gift_products;
		}

		/**
		 * Get the cheapest manual gift products.
		 *
		 * @since 12.0.0
		 * @return array
		 */
		public static function get_cheapest_manual_gift_products() {
			if (isset(self::$cheapest_manual_gift_products)) {
				return self::$cheapest_manual_gift_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$cheapest_manual_gift_products;
		}

		/**
		 * Get the cart notices.
		 */
		public static function get_cart_notices() {

			if (isset(self::$cart_notices)) {
				return self::$cart_notices;
			}

			self::prepare_matched_rule_gift_products();

			return self::$cart_notices;
		}

		/**
		 * Get Manual Rule Products.
		 */
		public static function get_manual_rule_products() {

			if (isset(self::$manual_rule_products)) {
				return self::$manual_rule_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$manual_rule_products;
		}

		/**
		 * Get Automatic Rule Products.
		 */
		public static function get_automatic_rule_products() {

			if (isset(self::$automatic_rule_products)) {
				return self::$automatic_rule_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$automatic_rule_products;
		}

		/**
		 * Get BOGO Rule Products.
		 */
		public static function get_bogo_rule_products() {

			if (isset(self::$bogo_rule_products)) {
				return self::$bogo_rule_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$bogo_rule_products;
		}

		/**
		 * Get coupon Rule Products.
		 */
		public static function get_coupon_rule_products() {

			if (isset(self::$coupon_rule_products)) {
				return self::$coupon_rule_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$coupon_rule_products;
		}

		/**
		 * Get subtotal rule products.
		 * 
		 * @since 11.1.0
		 * @return array
		 */
		public static function get_subtotal_rule_products() {
			if (isset(self::$subtotal_rule_products)) {
				return self::$subtotal_rule_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$subtotal_rule_products;
		}

		/**
		 * Get the bulk pricing rule products.
		 * 
		 * @since 12.0.0
		 * @return array
		 */
		public static function get_bulk_pricing_rule_products() {
			if (isset(self::$bulk_pricing_rule_products)) {
				return self::$bulk_pricing_rule_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$bulk_pricing_rule_products;
		}

		/**
		 * Get the bulk quantity rule products.
		 * 
		 * @since 12.0.0
		 * @return array
		 */
		public static function get_bulk_quantity_rule_products() {
			if (isset(self::$bulk_quantity_rule_products)) {
				return self::$bulk_quantity_rule_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$bulk_quantity_rule_products;
		}

		/**
		 * Get the cheapest rule products.
		 * 
		 * @since 12.0.0
		 * @return array
		 */
		public static function get_cheapest_rule_products() {
			if (isset(self::$cheapest_rule_products)) {
				return self::$cheapest_rule_products;
			}

			self::prepare_matched_rule_gift_products();

			return self::$cheapest_rule_products;
		}

		/**
		 * Get Gift Products In cart.
		 */
		public static function get_manual_gift_products_in_cart() {

			if (isset(self::$manual_gift_products_in_cart)) {
				return self::$manual_gift_products_in_cart;
			}

			self::$manual_gift_products_in_cart = fgf_get_free_gift_products_in_cart();

			return self::$manual_gift_products_in_cart;
		}

		/**
		 * Get automatic Gift Products In cart.
		 */
		public static function get_automatic_gift_products_in_cart() {

			if (isset(self::$automatic_gift_products_in_cart)) {
				return self::$automatic_gift_products_in_cart;
			}

			self::$automatic_gift_products_in_cart = fgf_get_free_gift_products_in_cart(false, true);

			return self::$automatic_gift_products_in_cart;
		}

		/**
		 * May be Gift Product in cart.
		 */
		public static function maybe_gift_product_in_cart( $product_id, $type, $rule_id ) {
			$gift_products_in_cart = ( '2' == $type ) ? self::get_automatic_gift_products_in_cart() : self::get_manual_gift_products_in_cart();

			if (!fgf_check_is_array($gift_products_in_cart)) {
				return false;
			}

			// Return if the product is not exists.
			if (!array_key_exists($product_id, $gift_products_in_cart)) {
				return false;
			}

			// Check if the rule is empty.
			if (!fgf_check_is_array($gift_products_in_cart[$product_id])) {
				return false;
			}

			// Check if a rule is exists.
			if (!array_key_exists($rule_id, $gift_products_in_cart[$product_id])) {
				return false;
			}

			return $gift_products_in_cart[$product_id][$rule_id];
		}

		/**
		 * Check Rule Product exists
		 */
		public static function rule_product_exists( $rule_id, $product_id, $automatic = false ) {
			$rule_products = ( $automatic ) ? self::get_automatic_rule_products() : self::get_manual_rule_products();
			if (!fgf_check_is_array($rule_products)) {
				return false;
			}

			if (!isset($rule_products[$rule_id])) {
				return false;
			}

			if (!array_key_exists($product_id, $rule_products[$rule_id])) {
				return false;
			}

			return $rule_products[$rule_id][$product_id]['qty'];
		}

		/**
		 * Get BOGO Rule Product qty.
		 */
		public static function get_bogo_rule_product_qty( $rule_id, $product_id, $buy_product_id ) {
			$rule_products = self::get_bogo_rule_products();
			if (!fgf_check_is_array($rule_products)) {
				return false;
			}

			if (!isset($rule_products[$rule_id])|| !fgf_check_is_array($rule_products[$rule_id])) {
				return false;
			}

			$rule=fgf_get_rule($rule_id);
			if ('single'===$rule->get_buy_product_quantity_consider_mode()&&!isset($rule_products[$rule_id][$buy_product_id][$product_id])) {
				return false;
			}

			if ('group'===$rule->get_buy_product_quantity_consider_mode()) {
				$group_product=reset($rule_products[$rule_id]);
				if (!isset($group_product[$product_id])) {
					return false;
				}

				$qty=$group_product[$product_id];
			} else {
				$qty=$rule_products[$rule_id][$buy_product_id][$product_id];
			}

			return $qty;
		}

		/**
		 * Check the coupon rule product qty.
		 *
		 * @return bool
		 */
		public static function get_coupon_rule_product_qty( $rule_id, $product_id, $coupon_id ) {
			$rule_products = self::get_coupon_rule_products();

			if (!fgf_check_is_array($rule_products)) {
				return false;
			}

			if (!isset($rule_products[$rule_id])) {
				return false;
			}

			if (!isset($rule_products[$rule_id][$coupon_id])) {
				return false;
			}

			if (!in_array($product_id, $rule_products[$rule_id][$coupon_id]['product_ids'])) {
				return false;
			}

			return $rule_products[$rule_id][$coupon_id]['qty'];
		}

		/**
		 * Get the subtotal rule product qty.
		 *
		 * @since 11.1.0
		 * @return bool
		 */
		public static function get_subtotal_rule_product_qty( $rule_id, $product_id ) {
			$rule_products = self::get_subtotal_rule_products();

			if (!fgf_check_is_array($rule_products)) {
				return false;
			}

			if (!isset($rule_products[$rule_id])) {
				return false;
			}

			if (!array_key_exists($product_id, $rule_products[$rule_id])) {
				return false;
			}

			return $rule_products[$rule_id][$product_id];
		}

		/**
		 * Get the bulk pricing rule product qty.
		 *
		 * @since 12.0.0
		 * @return bool
		 */
		public static function get_bulk_pricing_rule_product_qty( $rule_id, $product_id ) {
			$rule_products = self::get_bulk_pricing_rule_products();
			if (!fgf_check_is_array($rule_products)) {
				return false;
			}

			if (!isset($rule_products[$rule_id])) {
				return false;
			}

			if (!array_key_exists($product_id, $rule_products[$rule_id])) {
				return false;
			}

			return $rule_products[$rule_id][$product_id];
		}

		/**
		 * Get the bulk quantity rule product qty.
		 *
		 * @since 12.0.0
		 * @return bool
		 */
		public static function get_bulk_quantity_rule_product_qty( $rule_id, $product_id ) {
			$rule_products = self::get_bulk_quantity_rule_products();
			if (!fgf_check_is_array($rule_products)) {
				return false;
			}

			if (!isset($rule_products[$rule_id])) {
				return false;
			}

			if (!array_key_exists($product_id, $rule_products[$rule_id])) {
				return false;
			}

			return $rule_products[$rule_id][$product_id];
		}

		/**
		 * Get the cheapest gift rule product qty.
		 *
		 * @since 12.0.0
		 * @return bool
		 */
		public static function get_cheapest_gift_rule_product_qty( $rule_id, $product_id ) {
			$rule_products = self::get_cheapest_rule_products();
			if (!fgf_check_is_array($rule_products)) {
				return false;
			}

			if (!isset($rule_products[$rule_id])) {
				return false;
			}

			if (!array_key_exists($product_id, $rule_products[$rule_id])) {
				return false;
			}

			return $rule_products[$rule_id][$product_id];
		}

		/**
		 * Matched Rules
		 */
		public static function matched_rules() {
			self::$rule_gift_products_count = 0;
			self::$bogo_manual_rule_gift_products_count = 0;
			self::$subtotal_manual_rule_gift_products_count = 0;
			self::$bulk_pricing_manual_rule_gift_products_count = 0;
			self::$bulk_quantity_manual_rule_gift_products_count = 0;
			self::$cheapest_manual_rule_gift_products_count = 0;

			$matched_rules = array(
				'manual' => array(),
				'automatic' => array(),
				'bogo' => array(),
				'notices' => array(),
				'coupon' => array(),
				'manual_bogo' => array(),
				'manual_coupon' => array(),
				'subtotal' => array(),
				'manual_subtotal' => array(),
				'bulk_pricing' => array(),
				'manual_bulk_pricing' => array(),
				'bulk_quantity' => array(),
				'manual_bulk_quantity' => array(),
				'cheapest_gift' => array(),
				'manual_cheapest_gift' => array(),
			);
			/**
			 * This hook is used to validate the rules.
			 *
			 * @since 1.0
			 */
			if (apply_filters('fgf_restrict_rules', false)) {
				return $matched_rules;
			}

			$rule_ids = self::get_active_rule_ids();
			if (!fgf_check_is_array($rule_ids)) {
				return $matched_rules;
			}

			self::$manual_rule_products = array();
			self::$automatic_rule_products = array();
			self::$bogo_rule_products = array();
			self::$coupon_rule_products = array();
			self::$subtotal_rule_products = array();
			self::$bulk_pricing_rule_products = array();
			self::$bulk_quantity_rule_products = array();
			self::$cheapest_rule_products = array();

			foreach ($rule_ids as $rule_id) {

				// Set the default filter.
				self::set_default_filter();

				self::$rule = fgf_get_rule($rule_id);

				// Restrict the adding free gifts based on coupons applied in the cart.
				if (self::validate_applied_WC_coupons()) {
					continue;
				}

				if (self::is_valid_rule()) {

					switch (self::$rule->get_rule_type()) {
						case '14':
							// Cheapest gift rule matched products.
							$each_cheapest_gift_products = self::cheapest_gift_rule_products();

							if (!empty($each_cheapest_gift_products['overall_rule_products'])) {
								self::$cheapest_rule_products[$rule_id] = $each_cheapest_gift_products['overall_rule_products'];

								// Matched cheapest gift rule.
								$matched_rules['cheapest_gift'] = array_merge($matched_rules['cheapest_gift'], $each_cheapest_gift_products['each_rule_products']);
							}
							break;

						case '13':
							// Cheapest gift rule matched products.
							$each_cheapest_gift_products = self::cheapest_gift_rule_products();

							if (!empty($each_cheapest_gift_products['overall_rule_products'])) {
								self::$cheapest_rule_products[$rule_id] = $each_cheapest_gift_products['overall_rule_products'];

								// Matched cheapest gift rule.
								$matched_rules['manual_cheapest_gift'] = array_merge($matched_rules['manual_cheapest_gift'], $each_cheapest_gift_products['each_rule_products']);
							}
							break;

						case '12':
							// Bulk quantity rule matched products.
							$each_bulk_quantity_products = self::bulk_quantity_rule_products();

							if (!empty($each_bulk_quantity_products['overall_rule_products'])) {
								self::$bulk_quantity_rule_products[$rule_id] = $each_bulk_quantity_products['overall_rule_products'];

								// Matched bulk quantity rule.
								$matched_rules['bulk_quantity'] = array_merge($matched_rules['bulk_quantity'], $each_bulk_quantity_products['each_rule_products']);
							}

							$notice=self::get_bulk_quantity_next_eligible_notice();
							if ($notice) {
								$matched_rules['notices'][$rule_id] = $notice;
							}
							break;

						case '11':
							// Bulk quantity rule matched products.
							$each_bulk_quantity_products = self::bulk_quantity_rule_products();

							if (!empty($each_bulk_quantity_products['overall_rule_products'])) {
								self::$bulk_quantity_rule_products[$rule_id] = $each_bulk_quantity_products['overall_rule_products'];

								// Matched bulk quantity rule.
								$matched_rules['manual_bulk_quantity'] = array_merge($matched_rules['manual_bulk_quantity'], $each_bulk_quantity_products['each_rule_products']);
							}

							$notice=self::get_bulk_quantity_next_eligible_notice();
							if ($notice) {
								$matched_rules['notices'][$rule_id] = $notice;
							}
							
							break;

						case '10':
							// Bulk pricing rule matched products.
							$each_bulk_pricing_products = self::bulk_pricing_rule_products();

							if (!empty($each_bulk_pricing_products['overall_rule_products'])) {
								self::$bulk_pricing_rule_products[$rule_id] = $each_bulk_pricing_products['overall_rule_products'];

								// Matched bulk pricing rule.
								$matched_rules['bulk_pricing'] = array_merge($matched_rules['bulk_pricing'], $each_bulk_pricing_products['each_rule_products']);
							}

							$notice=self::get_bulk_pricing_next_eligible_notice();
							if ($notice) {
								$matched_rules['notices'][$rule_id] = $notice;
							}
							break;

						case '9':
							// Bulk pricing rule matched products.
							$each_bulk_pricing_products = self::bulk_pricing_rule_products();

							if (!empty($each_bulk_pricing_products['overall_rule_products'])) {
								self::$bulk_pricing_rule_products[$rule_id] = $each_bulk_pricing_products['overall_rule_products'];

								// Matched bulk pricing rule.
								$matched_rules['manual_bulk_pricing'] = array_merge($matched_rules['manual_bulk_pricing'], $each_bulk_pricing_products['each_rule_products']);
							}

							$notice=self::get_bulk_pricing_next_eligible_notice();
							if ($notice) {
								$matched_rules['notices'][$rule_id] = $notice;
							}
							break;

						case '8':
							// Subtotal rule matched products.
							$each_subtotal_products = self::subtotal_rule_products();

							if (!empty($each_subtotal_products['overall_rule_products'])) {
								self::$subtotal_rule_products[$rule_id] = $each_subtotal_products['overall_rule_products'];

								// Matched subtotal rule.
								$matched_rules['subtotal'] = array_merge($matched_rules['subtotal'], $each_subtotal_products['each_rule_products']);
							}
							break;

						case '7':
							// Subtotal rule matched products.
							$each_subtotal_products = self::subtotal_rule_products();

							if (!empty($each_subtotal_products['overall_rule_products'])) {
								self::$subtotal_rule_products[$rule_id] = $each_subtotal_products['overall_rule_products'];

								// Matched subtotal rule.
								$matched_rules['manual_subtotal'] = array_merge($matched_rules['manual_subtotal'], $each_subtotal_products['each_rule_products']);
							}
							break;

						case '6':
							// Coupon rule matched products.
							$each_coupon_products = self::coupon_rule_products();

							if (!empty($each_coupon_products['overall_rule_products'])) {
								self::$coupon_rule_products[$rule_id] = $each_coupon_products['overall_rule_products'];

								// Matched Coupon rule.
								$matched_rules['manual_coupon'] = array_merge($matched_rules['manual_coupon'], $each_coupon_products['each_rule_products']);
							}


							break;
						case '5':
							//BOGO rule matched products.
							$each_bogo_products = self::bogo_rule_products();

							if (!empty($each_bogo_products['overall_rule_products'])) {
								self::$bogo_rule_products[$rule_id] = $each_bogo_products['overall_rule_products'];

								// Matched manual BOGO rule.
								$matched_rules['manual_bogo'] = array_merge($matched_rules['manual_bogo'], $each_bogo_products['each_rule_products']);
							}
							break;
						case '4':
							// Coupon rule matched products.
							$each_coupon_products = self::coupon_rule_products();

							if (!empty($each_coupon_products['overall_rule_products'])) {
								self::$coupon_rule_products[$rule_id] = $each_coupon_products['overall_rule_products'];

								// Matched Coupon rule.
								$matched_rules['coupon'] = array_merge($matched_rules['coupon'], $each_coupon_products['each_rule_products']);
							}
							break;
						case '3':
							//BOGO rule matched products.
							$each_bogo_products = self::bogo_rule_products();

							if (!empty($each_bogo_products['overall_rule_products'])) {
								self::$bogo_rule_products[$rule_id] = $each_bogo_products['overall_rule_products'];

								// Matched BOGO rule.
								$matched_rules['bogo'] = array_merge($matched_rules['bogo'], $each_bogo_products['each_rule_products']);
							}
							break;
						case '2':
							// Each automatic rule matched products.
							$each_rule_automatic_products = self::rule_products();

							if (!empty($each_rule_automatic_products['overall_rule_products'])) {
								// All automatic each rule products.
								self::$automatic_rule_products[$rule_id] = $each_rule_automatic_products['overall_rule_products'];

								// Matched automatic rule.
								$matched_rules['automatic'] = array_merge($matched_rules['automatic'], $each_rule_automatic_products['each_rule_products']);
							}
							break;

						default:
							// Each rule matched products.
							$each_rule_manaul_products = self::rule_products();

							if (!empty($each_rule_manaul_products['overall_rule_products'])) {
								// All each rule products.
								self::$manual_rule_products[$rule_id] = $each_rule_manaul_products['overall_rule_products'];

								// matched rule
								$matched_rules['manual'] = array_merge($matched_rules['manual'], $each_rule_manaul_products['each_rule_products']);
							}
							break;
					}
				} elseif (self::is_valid_notice_rule()) {
					// Prepare the eligible notices.
					$matched_rules['notices'][$rule_id] = self::get_rule_notice();
				}
			}

			return $matched_rules;
		}

		/**
		 * Get active rule IDs
		 */
		public static function get_active_rule_ids() {

			if (self::$active_rule_ids) {
				return self::$active_rule_ids;
			}

			self::$active_rule_ids = fgf_get_active_rule_ids();

			return self::$active_rule_ids;
		}

		/**
		 * Get rule IDs
		 */
		public static function get_rule_ids() {

			if (self::$rule_ids) {
				return self::$rule_ids;
			}

			self::$rule_ids = fgf_get_rule_ids();

			return self::$rule_ids;
		}

		/**
		 * Get the gift products.
		 *
		 * @return array
		 */
		public static function get_gift_products() {

			if (self::$gift_products) {
				return self::$gift_products;
			}

			$products = array();

			$rule_ids = self::get_rule_ids();
			if (fgf_check_is_array($rule_ids)) {
				foreach ($rule_ids as $rule_id) {

					self::$rule = fgf_get_rule($rule_id);

					// Each all rule products.
					$products = array_merge($products, self::get_products(true));
				}
			}

			// reset the rule.
			self::$rule = null;

			self::$gift_products = array_filter(array_unique($products));

			return self::$gift_products;
		}

		/**
		 * Get the valid gift products.
		 *
		 * @since 9.6.0
		 * @return array
		 */
		public static function get_valid_gift_products() {
			if (self::$valid_gift_products) {
				return self::$valid_gift_products;
			}

			$products = array();

			$rule_statuses = array_filter((array) get_option('fgf_settings_gift_products_valid_rule_statuses'));
			$rule_ids = fgf_get_rule_ids($rule_statuses);
			if (fgf_check_is_array($rule_ids)) {
				foreach ($rule_ids as $rule_id) {

					self::$rule = fgf_get_rule($rule_id);

					// Each all rule products.
					$products = array_merge($products, self::get_products(true));
				}
			}

			// reset the rule.
			self::$rule = null;

			self::$valid_gift_products = array_filter(array_unique($products));

			return self::$valid_gift_products;
		}

		/**
		 * Get Rule Products
		 */
		public static function rule_products() {
			$rule_products = array( 'each_rule_products' => array(), 'overall_rule_products' => array() );
			$gifts_selection_per_user = get_option('fgf_settings_gifts_selection_per_user');
			$usage_count_exists = self::validate_rule_usage_count();
			$user_usage_count_exists = self::validate_rule_user_usage_count();

			// Return if rule usage count exists.
			if (!$usage_count_exists || !$user_usage_count_exists) {
				return $rule_products;
			}

			$products = self::get_products();
			// Return if the product is not exists..
			if (!fgf_check_is_array($products)) {
				return $rule_products;
			}

			$rule_product_count = 0;
			$rule_order_count_exists = self::validate_rule_per_order_count();
			foreach ($products as $parent_id) {
				// If the product is already exists.
				// If the rule type is 1.
				if ('1' == self::$rule->get_rule_type() && in_array($parent_id, self::$manual_product_already_exists)) {
					continue;
				}

				$product = fgf_get_product($parent_id);
				if (!$product) {
					continue;
				}

				$eligible_product = array();
				$product_ids = ( 'variable' == $product->get_type() ) ? $product->get_children() : array( $parent_id );

				foreach ($product_ids as $product_id) {
					$hide_add_to_cart = true;
					// Get each gift product quantity.
					$rule_quantity = self::get_rule_quantity();
					// Get gift product quantity from the cart.
					$cart_qty = (float) self::maybe_gift_product_in_cart($product_id, self::$rule->get_rule_type(), self::$rule->get_id());

					$current_rule_quantity = $rule_quantity;

					// Get the gift product quantity from the cart.
					if ('1' === self::$rule->get_rule_type() && 'no' === $gifts_selection_per_user && $cart_qty) {
						$hide_add_to_cart = true;
					} elseif ($cart_qty < $current_rule_quantity) {
						$current_rule_quantity = $current_rule_quantity - $cart_qty;
						$hide_add_to_cart = false;
					}

					$qty = self::get_product_available_quantity($current_rule_quantity, $product_id);

					// Hide product if the product is out of stock.
					if (!$qty) {
						$hide_add_to_cart = true;
					}

					// Check if the rule order count exists.
					// Check if the product having quantity.
					if ($rule_order_count_exists && ( $qty || '1' != self::$rule->get_rule_type() )) {

						if (!fgf_check_is_array($eligible_product)) {
							// Prepare the eligible gift products.
							$eligible_product = array(
								'parent_id' => $parent_id,
								'product_id' => $product_id,
								'rule_id' => self::$rule->get_id(),
								'qty' => self::prepare_manual_gift_product_quantity($qty, $cart_qty),
								'hide_add_to_cart' => $hide_add_to_cart,
								'variation_ids' => array(),
							);
						}

						// Consider the valid variation in variable product.
						if ('variable' == $product->get_type() && !$hide_add_to_cart) {
							$eligible_product['variation_ids'][] = $product_id;
						}

						// Record to avoid manual gifts duplicate products.
						if ('1' === self::$rule->get_rule_type() && ( 'yes' == $gifts_selection_per_user || !$hide_add_to_cart )) {
							self::$manual_product_already_exists[] = $product_id;
						}

						if (!$hide_add_to_cart) {
							self::$manual_product_exists = true;
						}
					}

					// To avoid removed gift products from cart if the rule is count over.
					$rule_products['overall_rule_products'][$product_id] = array(
						'product_id' => $product_id,
						'rule_id' => self::$rule->get_id(),
						'qty' => !$rule_order_count_exists && !$cart_qty ? 0 : $rule_quantity,
					);

					if ('1' === self::$rule->get_rule_type() && ( !self::get_rule_gifts_count_per_order() || ( self::$rule->get_rule_gifts_count_per_order() > $rule_product_count ) )) {
						$rule_product_count++;
					}

					if ('1' === self::$rule->get_rule_type() && !self::$rule->get_rule_gifts_count_per_order()) {
						self::$rule_unlimited_products_exists = true;
					}
				}

				if (fgf_check_is_array($eligible_product)) {
					if ('variable' == $product->get_type() && fgf_check_is_array($eligible_product['variation_ids'])) {
						$eligible_product['hide_add_to_cart'] = false;
					} elseif ('variable' == $product->get_type()) {
						$eligible_product['hide_add_to_cart'] = true;
					}

					$rule_products['each_rule_products'][] = $eligible_product;
				}
			}

			self::update_rule_gift_products_count($rule_product_count);

			return $rule_products;
		}

		/**
		 * Update the rule gift products count.
		 *
		 * @since 10.2.0
		 * @param int $rule_product_count count of products
		 */
		private static function update_rule_gift_products_count( $rule_product_count ) {
			if (!$rule_product_count) {
				return;
			}

			if (!in_array(self::$rule->get_rule_type(), array( '1', '6', '7', '9', '11', '13' ))) {
				return;
			}

			$per_order_count = self::get_rule_gifts_count_per_order();
			if ($per_order_count) {
				$valid_rule_count = ( 'yes' === get_option('fgf_settings_gifts_selection_per_user') ) ? $per_order_count : $rule_product_count;
				$valid_rule_count = ( $valid_rule_count >= $per_order_count ) ? $per_order_count : $valid_rule_count;
			} else {
				$overall_maximum_gift_count = floatval(get_option('fgf_settings_gifts_count_per_order'));
				$valid_rule_count = ( 'yes' === get_option('fgf_settings_gifts_selection_per_user') ) ? $overall_maximum_gift_count : $rule_product_count;
				$valid_rule_count = ( $valid_rule_count >= $overall_maximum_gift_count ) ? $overall_maximum_gift_count : $valid_rule_count;
			}

			self::$rule_gift_products_count = self::$rule_gift_products_count + $valid_rule_count;
		}

		/**
		 * Prepare the manual gift product quantity.
		 *
		 * @since 10.1.0
		 * @param int $qty
		 * @param int $cart_qty
		 * @return int
		 */
		public static function prepare_manual_gift_product_quantity( $qty, $cart_qty ) {
			switch (self::$rule->get_rule_type()) {
				case '1':
				case '6':
					$rule_gift_product_count = self::get_rule_gift_product_count();
					if ('' === $rule_gift_product_count) {
						$qty = '';
					} elseif ($rule_gift_product_count > $cart_qty) {
						$qty = $rule_gift_product_count - $cart_qty;
					}
					break;
			}

			return $qty;
		}

		/**
		 * Get the rule gift product count.
		 *
		 * @since 10.1.0
		 * @return float/string
		 */
		public static function get_rule_gift_product_count() {
			$order_type = get_option('fgf_settings_gifts_count_per_order_type');
			$global_maximum_count = get_option('fgf_settings_gifts_count_per_order');

			switch ($order_type) {
				case '2':
					$rule_maximum_count = self::$rule->get_rule_gifts_count_per_order();
					// Consider the global maximum gift as the rule gift count when the rule maximum gift count is empty and the global maximum gift count is set.
					if (empty($rule_maximum_count) && $global_maximum_count) {
						$rule_gift_count = $global_maximum_count;
						// Consider the global maximum gift as the rule gift count when the rule maximum gift count is greater than the global maximum gift count is set.
					} elseif ($rule_maximum_count && $rule_maximum_count > $global_maximum_count) {
						$rule_gift_count = $global_maximum_count;
					} else {
						$rule_gift_count = $rule_maximum_count;
					}
					break;
				case '1':
					$rule_gift_count = $global_maximum_count;
					break;
			}

			return $rule_gift_count;
		}

		/**
		 * Get the rule products.
		 *
		 * @return array
		 */
		public static function get_products( $include_parent = false ) {
			$products = array();
			$selected_products = array();
			$selected_categories = array();
			$type = 'product';

			switch (self::$rule->get_rule_type()) {
				// Subtotal based rule.
				case '8':
					$selected_products = self::$rule->get_subtotal_gift_products();
					break;

				case '7':
					if ('2' === self::$rule->get_subtotal_gift_type()) {
						$type = 'category';
						$selected_categories = self::$rule->get_subtotal_gift_categories();
					} else {
						$selected_products = self::$rule->get_subtotal_gift_products();
					}
					break;

				// Coupon based Rule.
				case '4':
				case '6':
					$selected_products = self::$rule->get_coupon_gift_products();
					break;

				// BOGO based Rule.
				case '5':
					if ('2' === self::$rule->get_product_type()) {
						$type = 'category';
						$selected_categories = self::$rule->get_categories();
					} else {
						$selected_products = self::$rule->get_products();
					}
					break;

				case '3':
					if ('1' == self::$rule->get_bogo_gift_type()) {
						return $products;
					} else {
						$selected_products = self::$rule->get_products();
					}
					break;

				case '2':
					$selected_products = self::$rule->get_gift_products();
					break;

				default:
					if ('2' === self::$rule->get_gift_type()) {
						$type = 'category';
						$selected_categories = self::$rule->get_gift_categories();
					} else {
						$selected_products = self::$rule->get_gift_products();
					}

					break;
			}

			return self::get_valid_products($selected_products, $selected_categories, $type, $include_parent);
		}

		/**
		 * Get the subtotal rule products.
		 *
		 * @since 11.1.0
		 * @return array
		 */
		public static function get_subtotal_products( $include_parent = false ) {
			$selected_products = array();
			$selected_categories = array();
			$type = 'product';

			if ('7' === self::$rule->get_rule_type() && '2' === self::$rule->get_subtotal_gift_type()) {
				$type = 'category';
				$selected_categories = self::$rule->get_subtotal_gift_categories();
			} else {
				$selected_products = self::$rule->get_subtotal_gift_products();
			}

			return self::get_valid_products($selected_products, $selected_categories, $type, $include_parent);
		}

		/**
		 * Get the bulk pricing rule products.
		 * 
		 * @since 12.0.0
		 * @param boolean  $include_parent
		 * @param boolean/array $bulk_pricing_rules
		 * @return array
		 */
		public static function get_bulk_pricing_products( $include_parent = false, $bulk_pricing_rules = false ) {
			$valid_products = array();

			$bulk_pricing_rules = ( !$bulk_pricing_rules ) ? self::$rule->get_bulk_pricing_rules() : $bulk_pricing_rules;
			foreach ($bulk_pricing_rules as $bulk_pricing_rule) {
				$selected_products = array();
				$selected_categories = array();
				$type = 'product';

				if ('9' === self::$rule->get_rule_type() && '2' === $bulk_pricing_rule['gift_type']) {
					$type = 'category';
					$selected_categories = $bulk_pricing_rule['gift_categories'];
				} else {
					$selected_products = $bulk_pricing_rule['gift_products'];
				}

				/**
				 * This hook is used to alter the bulk valid gift products.
				 * 
				 * @since 12.0.0
				 */
				$selected_products=apply_filters('fgf_bulk_valid_gift_products', self::get_valid_products($selected_products, $selected_categories, $type, $include_parent), $bulk_pricing_rule, self::$rule);
				
				$valid_products = array_merge($valid_products, $selected_products);
			}

			return $valid_products;
		}

		/**
		 * Get the bulk quantity rule products.
		 *
		 * @since 12.0.0
		 * @return array
		 */
		public static function get_bulk_quantity_products( $include_parent = false, $bulk_quantity_rules = false ) {
			$valid_products = array();

			$bulk_quantity_rules = ( !$bulk_quantity_rules ) ? self::$rule->get_bulk_quantity_rules() : $bulk_quantity_rules;
			foreach ($bulk_quantity_rules as $bulk_quantity_rule) {
				$selected_products = array();
				$selected_categories = array();
				$type = 'product';

				if ('11' === self::$rule->get_rule_type() && '2' === $bulk_quantity_rule['gift_type']) {
					$type = 'category';
					$selected_categories = $bulk_quantity_rule['gift_categories'];
				} else {
					$selected_products = $bulk_quantity_rule['gift_products'];
				}

				/**
				 * This hook is used to alter the bulk valid gift products.
				 * 
				 * @since 12.0.0
				 */
				$selected_products=apply_filters('fgf_bulk_valid_gift_products', self::get_valid_products($selected_products, $selected_categories, $type, $include_parent), $bulk_quantity_rule, self::$rule);
				
				$valid_products = array_merge($valid_products, $selected_products);
			}

			return $valid_products;
		}

		/**
		 * Get the bulk pricing cart total.
		 * 
		 * @since 12.0.0
		 * @return float
		 */
		public static function get_bulk_pricing_cart_total() {
			switch (self::$rule->get_bulk_pricing_total_type()) {
				case '2':
					$total = fgf_get_wc_cart_total();
					break;
				case '3':
					$total = fgf_get_wc_cart_category_subtotal(self::$rule->get_bulk_pricing_categories(), 'product_cat', self::$rule->get_consider_bulk_pricing_subcategories_total(), self::$rule->is_calculate_bulk_pricing_discounted_category_total());
					break;
				default:
					$total = fgf_get_wc_cart_subtotal();
					break;
			}

			/**
			 * This hook is used to alter the rule bulk pricing cart total.
			 *
			 * @since 12.0.0
			 */
			return apply_filters('fgf_rule_bulk_pricing_cart_total', $total, self::$rule);
		}

		/**
		 * Get the valid bulk pricing rules.
		 *
		 * @since 12.0.0
		 * @return array
		 */
		public static function get_valid_bulk_pricing_rules() {
			$valid_bulk_pricing_rules = array();
			if (!is_object(WC()->cart) || !fgf_check_is_array(self::$rule->get_bulk_pricing_rules())) {
				return $valid_bulk_pricing_rules;
			}

			$total = self::get_bulk_pricing_cart_total();
			foreach (self::$rule->get_bulk_pricing_rules() as $bulk_pricing_rule) {
				if (!fgf_check_is_array($bulk_pricing_rule)) {
					continue;
				}

				// Validate minimum cart price
				if ($bulk_pricing_rule['minimum'] && fgf_convert_price_to_base_currency($bulk_pricing_rule['minimum']) > $total) {
					continue;
				}

				// Validate maximum cart price
				if ($bulk_pricing_rule['maximum'] && fgf_convert_price_to_base_currency($bulk_pricing_rule['maximum']) < $total) {
					continue;
				}

				$valid_bulk_pricing_rules[] = $bulk_pricing_rule;

				if ('2' !== self::$rule->get_bulk_pricing_rules_consider_type()) {
					break;
				}
			}

			return $valid_bulk_pricing_rules;
		}

		/**
		 * Get the bulk pricing next eligible notice.
		 *
		 * @since 12.0.0
		 * @return string
		 */
		public static function get_bulk_pricing_next_eligible_notice() {
			if (!is_object(WC()->cart) || !fgf_check_is_array(self::$rule->get_bulk_pricing_rules())) {
				return '';
			}
			
			$bulk_pricing_rules=self::$rule->get_bulk_pricing_rules();
			uasort( $bulk_pricing_rules, array( __CLASS__, 'ascending_usort' ) ) ;
			$total = self::get_bulk_pricing_cart_total();
			foreach ($bulk_pricing_rules as $bulk_pricing_rule) {
				if (!fgf_check_is_array($bulk_pricing_rule)) {
					continue;
				}

				// Continue if the rule is valid.
				if (!$bulk_pricing_rule['minimum'] || fgf_convert_price_to_base_currency($bulk_pricing_rule['minimum']) <= $total) {
					continue;
				}

				return self::get_rule_bulk_eligible_notice($bulk_pricing_rule);
				
			}

			return '';
		}

		/**
		 * Callback to ascending sort on rules.
		 *
		 * @since 12.0.0
		 * @param array $a
		 * @param array $b
		 * @return bool
		 */
		private static function ascending_usort( $a, $b ) {
			if (!isset( $a['minimum']) || ''===$a['minimum']) {
				return -1 ;
			}

			if (!isset( $b['minimum']) || ''===$b['minimum']) {
				return -1 ;
			}

			if ( $a['minimum'] === $b['minimum'] ) {
				return 0 ;
			}

			return $a['minimum'] < $b['minimum'] ? -1 : 1 ;
		}

		/**
		 * Get the bulk quantity next eligible notice.
		 *
		 * @since 12.0.0
		 * @return string
		 */
		public static function get_bulk_quantity_next_eligible_notice() {
			if (!is_object(WC()->cart) || !fgf_check_is_array(self::$rule->get_bulk_quantity_rules())) {
				return '';
			}

			$bulk_quantity_rules=self::$rule->get_bulk_quantity_rules();
			uasort( $bulk_quantity_rules, array( __CLASS__, 'ascending_usort' ) ) ;
			$cart_quantity = intval(WC()->cart->get_cart_contents_count()) - fgf_get_free_gift_products_count_in_cart();
			foreach ($bulk_quantity_rules as $bulk_quantity_rule) {
				if (!fgf_check_is_array($bulk_quantity_rule)) {
					continue;
				}

				// Continue if the rule is valid.
				if (!$bulk_quantity_rule['minimum'] || fgf_convert_price_to_base_currency($bulk_quantity_rule['minimum']) <= $cart_quantity) {
					continue;
				}

				return self::get_rule_bulk_eligible_notice($bulk_quantity_rule);
				
			}

			return '';
		}

		/**
		 * Get the valid bulk quantity rules.
		 *
		 * @since 12.0.0
		 * @return array
		 */
		public static function get_valid_bulk_quantity_rules() {
			$valid_products = array();
			if (!is_object(WC()->cart) || !fgf_check_is_array(self::$rule->get_bulk_quantity_rules())) {
				return $valid_products;
			}

			$cart_quantity = intval(WC()->cart->get_cart_contents_count()) - fgf_get_free_gift_products_count_in_cart();
			foreach (self::$rule->get_bulk_quantity_rules() as $bulk_quantity_rule) {
				if (!fgf_check_is_array($bulk_quantity_rule)) {
					continue;
				}

				// Validate minimum cart quantity
				if ($bulk_quantity_rule['minimum'] && $bulk_quantity_rule['minimum'] > $cart_quantity) {
					continue;
				}

				// Validate maximum cart quantity
				if ($bulk_quantity_rule['maximum'] && $bulk_quantity_rule['maximum'] < $cart_quantity) {
					continue;
				}

				$valid_products[] = $bulk_quantity_rule;

				if ('2' !== self::$rule->get_bulk_quantity_rules_consider_type()) {
					break;
				}
			}

			return $valid_products;
		}

		/**
		 * Get the rule valid products.
		 *
		 * @return array
		 */
		public static function get_valid_products( $selected_products = array(), $selected_categories = array(), $type = 'product', $include_parent = false ) {
			$products = array();

			switch ($type) {
				case 'category':
					foreach ($selected_categories as $category_id) {
						$product_ids = array();
						$category_product_ids = fgf_get_product_id_by_category($category_id);

						foreach ($category_product_ids as $product_id) {
							$product = fgf_get_product($product_id);
							if ($product) {
								//Variable
								if ($product->is_type('variable')) {
									$product_ids = array_merge($product_ids, $product->get_children());
								} else {
									$product_ids[] = $product_id;
								}
							}
						}

						$products = array_merge($products, $product_ids);
					}
					break;

				default:
					if (fgf_check_is_array($selected_products)) {
						foreach ($selected_products as $product_id) {
							$product_object = fgf_get_product($product_id);

							//Return if the Product does not exist.
							if (!$product_object || !$product_object->is_purchasable()) {
								continue;
							}

							$products[] = $product_id;

							if ($include_parent && !empty($product_object->get_parent_id())) {
								$products[] = $product_object->get_parent_id();
							} elseif ($include_parent && $product_object->is_type('variable')) {
								$products = array_merge($products, $product_object->get_children());
							}
						}
					}

					break;
			}

			/**
			 * This hook is used to alter the valid gift products based settings.
			 * 
			 * @since 10.8.0
			 */
			return apply_filters('fgf_valid_gift_products', $products, self::$rule);
		}

		/**
		 * Get the BOGO Rule Products.
		 *
		 * @return array
		 */
		public static function bogo_rule_products() {
			$rule_products = array( 'each_rule_products' => array(), 'overall_rule_products' => array() );
			$usage_count_exists = self::validate_rule_usage_count();
			$user_usage_count_exists = self::validate_rule_user_usage_count();

			// Return if rule usage count exists.
			if (!$usage_count_exists || !$user_usage_count_exists) {
				return $rule_products;
			}

			$assigned_products = array();
			//Get Selected buy products.
			$selected_buy_products = self::get_selected_buy_product();
			//Get Selected get products.
			$selected_get_products = self::get_selected_get_product();
			foreach ($selected_buy_products as $buy_product) {

				$original_buy_product_qty = self::get_bogo_buy_product_quantity($buy_product['product_count']);
				//Continue if quantity does not exist.
				if (!$original_buy_product_qty) {
					continue;
				}

				if ('5' === self::$rule->get_rule_type() && '2' === self::$rule->get_bogo_get_gift_type() && !in_array($buy_product['product_id'], $selected_get_products)) {
					continue;
				}

				//Hide the add to cart when following criteria matched.
				// 1. Manual BOGO Rule
				// 2. Get the quantity consider total
				$global_hide_add_to_cart = false;
				$buy_product_total_quantity = 0;
				if ('5' == self::$rule->get_rule_type() && '2' == self::$rule->get_buy_quantity_type()) {
					$get_quantity_in_cart = fgf_get_bogo_products_count_in_cart($buy_product['product_id'], self::$rule->get_id(), false, self::$rule->get_rule_type());
					$global_hide_add_to_cart = ( $get_quantity_in_cart >= $original_buy_product_qty ) ? true : false;
				}

				// Buy product if BOGO gift type is the same product.
				if ('3' == self::$rule->get_rule_type() && '1' == self::$rule->get_bogo_gift_type()) {
					$selected_get_products = array( $buy_product['product_id'] );
				}

				foreach ($selected_get_products as $get_parent_id) {
					$product = fgf_get_product($get_parent_id);
					if (!$product) {
						continue;
					}

					$eligible_product = array();
					$product_ids = ( 'variable' == $product->get_type() ) ? $product->get_children() : array( $get_parent_id );

					foreach ($product_ids as $get_product_id) {
						if (self::is_valid_get_product($get_product_id, $selected_buy_products)) {
							if ('3' == self::$rule->get_rule_type() || !array_key_exists($buy_product['product_id'], $assigned_products)||!in_array($get_product_id, $assigned_products[$buy_product['product_id']])) {
								$hide_add_to_cart = true;
								$buy_product_qty = self::get_valid_get_product_quantity($original_buy_product_qty, $get_product_id, $selected_buy_products);
								$quantity = $buy_product_qty;
								$current_quantity = $buy_product_qty;
								$assigned_products[$buy_product['product_id']][] = $get_product_id;
								// Get product count in cart.
								$get_product_cart_count = fgf_get_bogo_products_count_in_cart($buy_product['product_id'], self::$rule->get_id(), $get_product_id, self::$rule->get_rule_type());
								if (!$global_hide_add_to_cart) {

									// Check if the get product count less than rule quantity count,
									// subtract rule quantity count from get product count,
									// otherwise hide product.
									if ($get_product_cart_count < $buy_product_qty) {
										$quantity = $buy_product_qty - $get_product_cart_count;
										$hide_add_to_cart = false;
									}

									// Check the get product having a stock.
									$quantity = self::get_product_available_quantity($quantity, $get_product_id);
									if (!$quantity) {
										$hide_add_to_cart = true;
									}
								}

								if (!fgf_check_is_array($eligible_product)) {
									$eligible_product = array(
										'parent_id' => $get_parent_id,
										'product_id' => $get_product_id,
										'rule_id' => self::$rule->get_id(),
										'buy_product_id' => $buy_product['product_id'],
										'qty' => $quantity,
										'hide_add_to_cart' => $hide_add_to_cart,
										'variation_ids' => array(),
									);
								}

								// Consider the valid variation in variable product.
								if ('variable' == $product->get_type() && !$hide_add_to_cart) {
									$eligible_product['variation_ids'][] = $get_product_id;
								}

								// Handles quantities for the total quantity of gift products option.
								if ($global_hide_add_to_cart && '2' == self::$rule->get_buy_quantity_type()) {
									$current_quantity = $get_product_cart_count;
									if ($buy_product_total_quantity >= $buy_product_qty) {
										$current_quantity = 0;
									} elseif (( $get_product_cart_count + $buy_product_total_quantity ) > $buy_product_qty) {
										$current_quantity = ( $get_product_cart_count + $buy_product_total_quantity ) - $buy_product_qty;
									}

									$buy_product_total_quantity += $get_product_cart_count;
								}

								// Prepare the overall BOGO rule products.
								if (isset($rule_products['overall_rule_products'][$buy_product['product_id']][$get_product_id])) {
									$rule_products['overall_rule_products'][$buy_product['product_id']][$get_product_id] = $rule_products['overall_rule_products'][$buy_product['product_id']][$get_product_id]+$current_quantity;
								} else {
									$rule_products['overall_rule_products'][$buy_product['product_id']][$get_product_id]=$current_quantity;
								}

								if (fgf_check_is_array($eligible_product)) {
									if ('variable' == $product->get_type() && fgf_check_is_array($eligible_product['variation_ids'])) {
										$eligible_product['hide_add_to_cart'] = false;
									} elseif ('variable' == $product->get_type()) {
										$eligible_product['hide_add_to_cart'] = true;
									}

									$rule_products['each_rule_products'][] = $eligible_product;
								}

								if (!$eligible_product['hide_add_to_cart']) {
									self::$manual_product_exists = true;
								}

								if ('5' === self::$rule->get_rule_type() && '1' == self::$rule->get_buy_quantity_type()) {
									self::$bogo_manual_rule_gift_products_count = self::$bogo_manual_rule_gift_products_count + $buy_product_qty;
								}
							}
						}
					}
				}

				if ('5' === self::$rule->get_rule_type() && '2' == self::$rule->get_buy_quantity_type()) {
					self::$bogo_manual_rule_gift_products_count = self::$bogo_manual_rule_gift_products_count + $original_buy_product_qty;
				}
			}

			return $rule_products;
		}

		/**
		 * Get the valid get product quantity.
		 * 
		 * @since 11.3.0
		 * @param int $quantity
		 * @param int $product_id
		 * @param array $selected_buy_products
		 * @return int
		 */
		public static function get_valid_get_product_quantity( $quantity, $product_id, $selected_buy_products ) {
			if ('5' !== self::$rule->get_rule_type() || '2' !== self::$rule->get_bogo_get_gift_type()) {
				return $quantity;
			}

			$buy_product_key = array_search($product_id, array_column($selected_buy_products, 'product_id'));

			return self::get_bogo_buy_product_quantity($selected_buy_products[$buy_product_key]['product_count']);
		}

		/**
		 * Is valid get product?
		 * 
		 * @since 11.3.0
		 * @param int $product_id
		 * @param array $selected_buy_products
		 * @return bool
		 */
		public static function is_valid_get_product( $product_id, $selected_buy_products ) {
			if ('5' !== self::$rule->get_rule_type() || '2' !== self::$rule->get_bogo_get_gift_type()) {
				return true;
			}

			$buy_product_ids = array_column($selected_buy_products, 'product_id');
			$buy_product_key = array_search($product_id, array_column($selected_buy_products, 'product_id'));
			if (in_array($product_id, $buy_product_ids) && self::$rule->get_buy_product_count() <= $selected_buy_products[$buy_product_key]['product_count']) {
				return true;
			}

			return false;
		}

		/**
		 * Get the coupon rule products.
		 *
		 * @return array
		 */
		public static function coupon_rule_products() {
			$rule_products = array( 'each_rule_products' => array(), 'overall_rule_products' => array() );
			$usage_count_exists = self::validate_rule_usage_count();
			$user_usage_count_exists = self::validate_rule_user_usage_count();

			// Return if rule usage count exists.
			if (!$usage_count_exists || !$user_usage_count_exists) {
				return $rule_products;
			}

			// Check if the coupon is exists,
			if (!fgf_check_is_array(self::$rule->get_apply_coupon())) {
				return $rule_products;
			}

			$coupon_id = self::$rule->get_apply_coupon();
			// Check if the coupon is used in cart.
			$coupon_id = reset($coupon_id);
			if (!self::check_coupon_applied_cart($coupon_id)) {
				return $rule_products;
			}

			// Check if the gift products is valid.
			$selected_get_products = self::get_valid_products(self::$rule->get_coupon_gift_products());
			if (!fgf_check_is_array($selected_get_products)) {
				return $rule_products;
			}

			$valid_product_ids = array();
			$rule_product_count = 0;
			foreach ($selected_get_products as $parent_id) {
				$product = fgf_get_product($parent_id);
				if (!$product) {
					continue;
				}

				$eligible_product = array();
				$rule_order_count_exists = self::validate_rule_per_order_count();
				$product_ids = ( 'variable' == $product->get_type() ) ? $product->get_children() : array( $parent_id );

				foreach ($product_ids as $product_id) {
					$hide_add_to_cart = true;
					$quantity = self::$rule->get_coupon_gift_products_qty();

					// Get the product count in cart.
					$cart_count = fgf_get_coupon_gift_product_count_in_cart($product_id, $coupon_id, self::$rule->get_id(), self::$rule->get_rule_type());

					// Check if the coupon product count less than rule quantity count,
					// subtract rule quantity count from coupon product count,
					// otherwise hide product.
					if ('6' === self::$rule->get_rule_type() && 'no' === get_option('fgf_settings_gifts_selection_per_user') && $cart_count) {
						$hide_add_to_cart = true;
					} elseif ($cart_count < $quantity) {
						$quantity = $quantity - $cart_count;
						$hide_add_to_cart = false;
					}

					// Check the coupon product having a stock.
					$quantity = self::get_product_available_quantity($quantity, $product_id);
					if (!$quantity) {
						$hide_add_to_cart = true;
					}

					// Check if the rule order count exists.
					// Check if the product having quantity.
					if ($rule_order_count_exists && ( $quantity || '6' != self::$rule->get_rule_type() )) {

						if (!fgf_check_is_array($eligible_product)) {
							$eligible_product = array(
								'parent_id' => $parent_id,
								'product_id' => $product_id,
								'rule_id' => self::$rule->get_id(),
								'coupon_id' => $coupon_id,
								'qty' => self::prepare_manual_gift_product_quantity($quantity, $cart_count),
								'hide_add_to_cart' => $hide_add_to_cart,
								'variation_ids' => array(),
							);
						}


						// Consider the valid variation in variable product.
						if ('variable' == $product->get_type() && !$hide_add_to_cart) {
							$eligible_product['variation_ids'][] = $product_id;
						}

						if (!$hide_add_to_cart) {
							self::$manual_product_exists = true;
						}
					}

					if ('6' === self::$rule->get_rule_type() && ( !self::get_rule_gifts_count_per_order() || ( self::$rule->get_rule_gifts_count_per_order() > $rule_product_count ) )) {
						$rule_product_count++;
					}

					if ('1' === self::$rule->get_rule_type() && !self::$rule->get_rule_gifts_count_per_order()) {
						self::$rule_unlimited_products_exists = true;
					}

					$valid_product_ids[] = $product_id;
				}

				if (fgf_check_is_array($eligible_product)) {
					if ('variable' == $product->get_type() && fgf_check_is_array($eligible_product['variation_ids'])) {
						$eligible_product['hide_add_to_cart'] = false;
					} elseif ('variable' == $product->get_type()) {
						$eligible_product['hide_add_to_cart'] = true;
					}

					$rule_products['each_rule_products'][] = $eligible_product;
				}
			}

			// Prepare the overall coupon rule products.
			$rule_products['overall_rule_products'][$coupon_id] = array(
				'product_ids' => $valid_product_ids,
				'qty' => self::$rule->get_coupon_gift_products_qty(),
			);

			self::update_rule_gift_products_count($rule_product_count);

			return $rule_products;
		}

		/**
		 * Get the subtotal rule products.
		 *
		 * @since 11.1.0
		 * @return array
		 */
		public static function subtotal_rule_products() {
			$rule_products = array( 'each_rule_products' => array(), 'overall_rule_products' => array() );
			$usage_count_exists = self::validate_rule_usage_count();
			$user_usage_count_exists = self::validate_rule_user_usage_count();

			// Return if rule usage count exists.
			if (!$usage_count_exists || !$user_usage_count_exists) {
				return $rule_products;
			}

			// Return if the subtotal does not met.
			if (self::$rule->get_subtotal_price() > self::get_total_price()) {
				return $rule_products;
			}

			// Check if the gift products is valid.
			$selected_products = self::get_subtotal_products();
			if (!fgf_check_is_array($selected_products)) {
				return $rule_products;
			}

			$current_total_quantity=0;
			$original_quantity = self::get_subtotal_quantity();
			$global_cart_count = fgf_get_rule_products_count_in_cart(self::$rule->get_id());
			$quantity_exists = ( $original_quantity <= $global_cart_count ) ? true : false;
			foreach ($selected_products as $parent_id) {
				$product = fgf_get_product($parent_id);
				if (!$product) {
					continue;
				}

				$eligible_product = array();
				$rule_order_count_exists = self::validate_rule_per_order_count();
				$product_ids = ( 'variable' == $product->get_type() ) ? $product->get_children() : array( $parent_id );

				foreach ($product_ids as $product_id) {
					$hide_add_to_cart = true;
					$quantity = $original_quantity;
					$current_quantity = $original_quantity;

					if (!$quantity_exists) {
						// subtract rule quantity count from coupon product count,
						// otherwise hide product.
						if ($global_cart_count < $quantity) {
							$quantity = $quantity - $global_cart_count;
							$hide_add_to_cart = false;
						}

						// Check the product having a stock.
						$quantity = self::get_product_available_quantity($quantity, $product_id);
						if (!$quantity) {
							$hide_add_to_cart = true;
						}
					} else {
						$quantity = 0;
						$current_quantity = fgf_get_gift_product_count_in_cart($product_id, self::$rule->get_id(), self::$rule->get_rule_mode());
						if ($current_total_quantity>=$original_quantity) {
							$current_quantity=0;
						} elseif ($current_quantity>$original_quantity) {
							$current_quantity=$current_quantity-$original_quantity;
						}

						$current_total_quantity+=$current_quantity;
					}

					// Check if the rule order count exists.
					// Check if the product having quantity.
					if ($rule_order_count_exists && ( $quantity || '8' !== self::$rule->get_rule_type() )) {

						if (!fgf_check_is_array($eligible_product)) {
							$eligible_product = array(
								'parent_id' => $parent_id,
								'product_id' => $product_id,
								'rule_id' => self::$rule->get_id(),
								'qty' => $quantity,
								'hide_add_to_cart' => $hide_add_to_cart,
								'variation_ids' => array(),
							);
						}

						// Consider the valid variation in variable product.
						if ('variable' == $product->get_type() && !$hide_add_to_cart) {
							$eligible_product['variation_ids'][] = $product_id;
						}

						if (!$hide_add_to_cart) {
							self::$manual_product_exists = true;
						}
					}

					if ('7' === self::$rule->get_rule_type()) {
						self::$subtotal_manual_rule_gift_products_count += $original_quantity;
					}

					// Prepare the overall coupon rule products.
					$rule_products['overall_rule_products'][$product_id] = $current_quantity;
				}

				if (fgf_check_is_array($eligible_product)) {
					if ('variable' == $product->get_type() && fgf_check_is_array($eligible_product['variation_ids'])) {
						$eligible_product['hide_add_to_cart'] = false;
					} elseif ('variable' == $product->get_type()) {
						$eligible_product['hide_add_to_cart'] = true;
					}

					$rule_products['each_rule_products'][] = $eligible_product;
				}
			}

			return $rule_products;
		}

		/**
		 * Get the total price.
		 * 
		 * @since 11.5.0
		 * @return float
		 */
		public static function get_total_price() {
			switch (self::$rule->get_subtotal_price_type()) {
				case '2':
					$total = fgf_get_wc_cart_total();
					break;
				case '3':
					$total = fgf_get_wc_cart_category_subtotal(self::$rule->get_total_categories(), 'product_cat', self::$rule->consider_subcategories_total(), self::$rule->calculate_total_discounted_category_total());
					break;
				default:
					$total = fgf_get_wc_cart_subtotal();
					break;
			}

			/**
			 * This hook is used to alter the rule total price.
			 *
			 * @since 11.5.0
			 */
			return apply_filters('fgf_rule_total_price', $total, self::$rule);
		}

		/**
		 * Get subtotal quantity.
		 * 
		 * @since 11.1.0
		 * @return int
		 */
		public static function get_subtotal_quantity() {
			if ('1' === self::$rule->get_subtotal_repeat_gift()) {
				return self::$rule->get_subtotal_gift_products_qty();
			}

			$total_count = intval(self::get_total_price() / floatval(self::$rule->get_subtotal_price()));
			$count = ( '2' === self::$rule->get_subtotal_repeat_gift_mode() && ( $total_count > self::$rule->get_subtotal_repeat_gift_limit() ) ) ? self::$rule->get_subtotal_repeat_gift_limit() : $total_count;

			return $count * self::$rule->get_subtotal_gift_products_qty();
		}

		/**
		 * Get the bulk pricing rule products.
		 *
		 * @since 12.0.0
		 * @return array
		 */
		public static function bulk_pricing_rule_products() {
			$rule_products = array( 'each_rule_products' => array(), 'overall_rule_products' => array() );
			$usage_count_exists = self::validate_rule_usage_count();
			$user_usage_count_exists = self::validate_rule_user_usage_count();

			// Return if rule usage count exists.
			if (!$usage_count_exists || !$user_usage_count_exists) {
				return $rule_products;
			}

			$valid_bulk_pricing_rules = self::get_valid_bulk_pricing_rules();
			if (!fgf_check_is_array($valid_bulk_pricing_rules)) {
				return $rule_products;
			}

			$valid_product_ids = array();
			foreach ($valid_bulk_pricing_rules as $valid_bulk_pricing_rule) {
				$selected_products = self::get_bulk_pricing_products(false, array( $valid_bulk_pricing_rule ));
				if (!fgf_check_is_array($selected_products)) {
					continue;
				}

				foreach ($selected_products as $parent_id) {
					$product = fgf_get_product($parent_id);
					if (!$product) {
						continue;
					}

					$eligible_product = array();
					$rule_order_count_exists = self::validate_rule_per_order_count();
					$product_ids = ( 'variable' == $product->get_type() ) ? $product->get_children() : array( $parent_id );

					foreach ($product_ids as $product_id) {
						$hide_add_to_cart = true;
						$original_quantity = $valid_bulk_pricing_rule['gift_quantity'];
						$quantity = $original_quantity;

						// Get the product count in cart.
						$cart_count = fgf_get_gift_product_count_in_cart($product_id, self::$rule->get_id(), self::$rule->get_rule_mode());

						// subtract rule quantity count from coupon product count,
						// otherwise hide product.
						if ($cart_count < $quantity) {
							$quantity = $quantity - $cart_count;
							$hide_add_to_cart = false;
						}

						// Check the product having a stock.
						$quantity = self::get_product_available_quantity($quantity, $product_id);
						if (!$quantity) {
							$hide_add_to_cart = true;
						}

						// Check if the rule order count exists.
						// Check if the product having quantity.
						if ($rule_order_count_exists && ( $quantity || '10' !== self::$rule->get_rule_type() )) {

							if (!fgf_check_is_array($eligible_product)) {
								$eligible_product = array(
									'parent_id' => $parent_id,
									'product_id' => $product_id,
									'rule_id' => self::$rule->get_id(),
									'qty' => $quantity,
									'hide_add_to_cart' => $hide_add_to_cart,
									'variation_ids' => array(),
								);
							}

							// Consider the valid variation in variable product.
							if ('variable' == $product->get_type() && !$hide_add_to_cart) {
								$eligible_product['variation_ids'][] = $product_id;
							}

							if (!$hide_add_to_cart) {
								self::$manual_product_exists = true;
							}
						}

						if ('9' === self::$rule->get_rule_type()) {
							self::$bulk_pricing_manual_rule_gift_products_count += $original_quantity;
						}

						$valid_product_ids[] = $product_id;

						if (isset($rule_products['overall_rule_products'][$product_id])) {
							$rule_products['overall_rule_products'][$product_id] = $rule_products['overall_rule_products'][$product_id] + $original_quantity;
						} else {
							$rule_products['overall_rule_products'][$product_id] = $original_quantity;
						}
					}

					if (fgf_check_is_array($eligible_product)) {
						if ('variable' == $product->get_type() && fgf_check_is_array($eligible_product['variation_ids'])) {
							$eligible_product['hide_add_to_cart'] = false;
						} elseif ('variable' == $product->get_type()) {
							$eligible_product['hide_add_to_cart'] = true;
						}

						$rule_products['each_rule_products'][] = $eligible_product;
					}
				}
			}

			return $rule_products;
		}

		/**
		 * Get the bulk quantity rule products.
		 *
		 * @since 12.0.0
		 * @return array
		 */
		public static function bulk_quantity_rule_products() {
			$rule_products = array( 'each_rule_products' => array(), 'overall_rule_products' => array() );
			$usage_count_exists = self::validate_rule_usage_count();
			$user_usage_count_exists = self::validate_rule_user_usage_count();

			// Return if rule usage count exists.
			if (!$usage_count_exists || !$user_usage_count_exists) {
				return $rule_products;
			}

			$valid_bulk_quantity_rules = self::get_valid_bulk_quantity_rules();
			if (!fgf_check_is_array($valid_bulk_quantity_rules)) {
				return $rule_products;
			}

			$valid_product_ids = array();
			foreach ($valid_bulk_quantity_rules as $valid_bulk_quantity_rule) {
				$selected_products = self::get_bulk_quantity_products(false, array( $valid_bulk_quantity_rule ));
				if (!fgf_check_is_array($selected_products)) {
					continue;
				}

				foreach ($selected_products as $parent_id) {
					$product = fgf_get_product($parent_id);
					if (!$product) {
						continue;
					}

					$eligible_product = array();
					$rule_order_count_exists = self::validate_rule_per_order_count();
					$product_ids = ( 'variable' == $product->get_type() ) ? $product->get_children() : array( $parent_id );

					foreach ($product_ids as $product_id) {
						$hide_add_to_cart = true;
						$original_quantity = $valid_bulk_quantity_rule['gift_quantity'];
						$quantity = $original_quantity;

						// Get the product count in cart.
						$cart_count = fgf_get_gift_product_count_in_cart($product_id, self::$rule->get_id(), self::$rule->get_rule_mode());

						// subtract rule quantity count from coupon product count,
						// otherwise hide product.
						if ($cart_count < $quantity) {
							$quantity = $quantity - $cart_count;
							$hide_add_to_cart = false;
						}

						// Check the product having a stock.
						$quantity = self::get_product_available_quantity($quantity, $product_id);
						if (!$quantity) {
							$hide_add_to_cart = true;
						}

						// Check if the rule order count exists.
						// Check if the product having quantity.
						if ($rule_order_count_exists && ( $quantity || '12' !== self::$rule->get_rule_type() )) {

							if (!fgf_check_is_array($eligible_product)) {
								$eligible_product = array(
									'parent_id' => $parent_id,
									'product_id' => $product_id,
									'rule_id' => self::$rule->get_id(),
									'qty' => $quantity,
									'hide_add_to_cart' => $hide_add_to_cart,
									'variation_ids' => array(),
								);
							}

							// Consider the valid variation in variable product.
							if ('variable' == $product->get_type() && !$hide_add_to_cart) {
								$eligible_product['variation_ids'][] = $product_id;
							}

							if (!$hide_add_to_cart) {
								self::$manual_product_exists = true;
							}
						}

						if ('11' === self::$rule->get_rule_type()) {
							self::$bulk_quantity_manual_rule_gift_products_count += $original_quantity;
						}

						$valid_product_ids[] = $product_id;

						if (isset($rule_products['overall_rule_products'][$product_id])) {
							$rule_products['overall_rule_products'][$product_id] = $rule_products['overall_rule_products'][$product_id] + $original_quantity;
						} else {
							$rule_products['overall_rule_products'][$product_id] = $original_quantity;
						}
					}

					if (fgf_check_is_array($eligible_product)) {
						if ('variable' == $product->get_type() && fgf_check_is_array($eligible_product['variation_ids'])) {
							$eligible_product['hide_add_to_cart'] = false;
						} elseif ('variable' == $product->get_type()) {
							$eligible_product['hide_add_to_cart'] = true;
						}

						$rule_products['each_rule_products'][] = $eligible_product;
					}
				}
			}

			return $rule_products;
		}

		/**
		 * Get the cheapest gift rule products.
		 *
		 * @since 12.0.0
		 * @return array
		 */
		public static function cheapest_gift_rule_products() {
			$rule_products = array( 'each_rule_products' => array(), 'overall_rule_products' => array() );
			$usage_count_exists = self::validate_rule_usage_count();
			$user_usage_count_exists = self::validate_rule_user_usage_count();

			// Return if rule usage count exists.
			if (!$usage_count_exists || !$user_usage_count_exists) {
				return $rule_products;
			}

			$product_id = fgf_get_cheapset_cart_item_from_cart(self::$rule->get_cheapest_gift_price_type());
			if (!$product_id) {
				return $rule_products;
			}

			$product = fgf_get_product($product_id);
			if (!$product) {
				return $rule_products;
			}

			$hide_add_to_cart = true;
			$quantity = self::$rule->get_cheapest_gift_quantity();
			// Get the product count in cart.
			$cart_count = fgf_get_gift_product_count_in_cart($product_id, self::$rule->get_id(), self::$rule->get_rule_mode());

			// subtract rule quantity count from coupon product count,
			// otherwise hide product.
			if ($cart_count < $quantity) {
				$quantity = $quantity - $cart_count;
				$hide_add_to_cart = false;
			}

			// Check the product having a stock.
			$quantity = self::get_product_available_quantity($quantity, $product_id);
			if (!$quantity) {
				$hide_add_to_cart = true;
			}

			// Check if the product having quantity.
			$rule_products['each_rule_products'][] = array(
				'parent_id' => $product_id,
				'product_id' => $product_id,
				'rule_id' => self::$rule->get_id(),
				'qty' => $quantity,
				'hide_add_to_cart' => $hide_add_to_cart,
				'variation_ids' => array(),
			);

			if (!$hide_add_to_cart) {
				self::$manual_product_exists = true;
			}

			if ('13' === self::$rule->get_rule_type()) {
				self::$cheapest_manual_rule_gift_products_count += self::$rule->get_cheapest_gift_quantity();
			}

			if (isset($rule_products['overall_rule_products'][$product_id])) {
				$rule_products['overall_rule_products'][$product_id] = $rule_products['overall_rule_products'][$product_id] + self::$rule->get_cheapest_gift_quantity();
				;
			} else {
				$rule_products['overall_rule_products'][$product_id] = self::$rule->get_cheapest_gift_quantity();
			}

			return $rule_products;
		}

		/**
		 * Check if coupon is applied in the cart.
		 *
		 * @return float/int.
		 */
		public static function check_coupon_applied_cart( $coupon_id ) {
			// Return false if no one coupon is not applied in the cart.
			if (!is_object(WC()->cart)|| empty(WC()->cart->get_applied_coupons())) {
				return false;
			}

			// Get the coupon.
			$the_coupon = new WC_Coupon($coupon_id);
			if (empty($the_coupon->get_code())) {
				return false;
			}

			return in_array(wc_format_coupon_code($the_coupon->get_code()), WC()->cart->get_applied_coupons(), true);
		}

		/**
		 * Get rule quantity.
		 *
		 * @return float/int.
		 */
		public static function get_rule_quantity() {

			// Check if the rule type is automatic
			// Automatic product qty is not empty.
			// Return Automatic qty.
			if ('2' == self::$rule->get_rule_type() && !empty(self::$rule->get_automatic_product_qty())) {
				return self::$rule->get_automatic_product_qty();
			}

			return self::get_rule_gift_product_count();
		}

		/**
		 * Get the product available quantity.
		 *
		 * @return float/int
		 */
		public static function get_product_available_quantity( $quantity, $product_id ) {
			$product = fgf_get_product($product_id);

			//Return if stock is out of stock.
			if (!$product || ( !$product->is_on_backorder() && !$product->is_in_stock() )) {
				return 0;
			}

			// Return if managing stock is not enabled.
			if (!$product->managing_stock() || $product->is_on_backorder()) {
				return $quantity;
			}

			// Get product count in cart
			$cart_quantity = fgf_get_product_count_in_cart($product_id);
			if ($product->get_stock_quantity() <= $cart_quantity) {
				return 0;
			}

			$overall_quantity = $cart_quantity + $quantity;
			if ($product->get_stock_quantity() < $overall_quantity) {
				$quantity = $product->get_stock_quantity() - $cart_quantity;
			}

			return $quantity;
		}

		/**
		 * Get the selected buy product in the cart.
		 *
		 * @return array
		 */
		public static function get_selected_buy_product() {
			$valid_products = array();

			// Return if cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return $valid_products;
			}

			$cart_contents = WC()->cart->get_cart();
			if (!fgf_check_is_array($cart_contents)) {
				return $valid_products;
			}

			$products = array();
			$cart_category_count = 0;
			$valid_category_ids = array();
			$valid_product_ids = array();
			foreach ($cart_contents as $cart_content) {
				// Skip if the cart product is a gift product.
				if (isset($cart_content['fgf_gift_product']['mode'])) {
					continue;
				}

				$product_id = !empty($cart_content['variation_id']) ? $cart_content['variation_id'] : $cart_content['product_id'];
				if (!self::is_valid_buy_product($cart_content)) {
					continue;
				}

				// Prepare valid categories.
				if ('2' === self::$rule->get_current_buy_product_consider_type()) {
					$valid_category_ids = array_merge($valid_category_ids, fgf_get_term_ids($cart_content['product_id']));
					$valid_product_ids = array_merge($valid_product_ids, array_filter(array( $cart_content['variation_id'], $cart_content['product_id'] )));
				}

				// Overall product quantity.
				if ('2' === self::$rule->get_current_buy_product_quantity_consider_type()) {
					$cart_category_count += $cart_content['quantity'];
					continue;
				}

				if (isset($products[$product_id])) {
					$products[$product_id] = array(
						'product_id' => $product_id,
						'product_count' => $products[$product_id]['product_count'] + $cart_content['quantity'],
					);
				} else {
					$products[$product_id] = array(
						'product_id' => $product_id,
						'product_count' => $cart_content['quantity'],
					);
				}
			}

			// Return if the all products/categories does not exists in the cart.
			if ('2' === self::$rule->get_current_buy_product_consider_type()) {
				if ('2' === self::$rule->get_buy_product_type() && !empty(array_diff(self::$rule->get_buy_categories(), $valid_category_ids))) {
					return $valid_products;
				} elseif ('1' === self::$rule->get_buy_product_type() && !empty(array_diff(self::$rule->get_buy_product(), $valid_product_ids))) {
					return $valid_products;
				}
			}

			switch (self::$rule->get_current_buy_product_quantity_consider_type()) {
				case '3':
					if (fgf_check_is_array($products)) {
						$valid_products[] = array(
							'product_id' => $product_id,
							'product_count' => min(array_column($products, 'product_count')),
						);
					}
					break;

				case '2':
					if ($cart_category_count) {
						$valid_products[] = array(
							'product_id' => $product_id,
							'product_count' => $cart_category_count,
						);
					}
					break;

				default:
					$valid_products = array_merge($products);
					break;
			}

			return array_merge($valid_products);
		}

		/**
		 * Is valid buy product?.
		 *
		 * @return bool
		 */
		public static function is_valid_buy_product( $cart_content ) {
			$return = false;
			switch (self::$rule->get_buy_product_type()) {
				case '2':
					$product_categories = fgf_get_term_ids($cart_content['product_id']);
					if (array_intersect(self::$rule->get_buy_categories(), $product_categories)) {
						$return = true;
					}

					break;

				default:
					$cart_product_ids = array_filter(array( $cart_content['variation_id'], $cart_content['product_id'] ));
					if (array_intersect(self::$rule->get_buy_product(), $cart_product_ids)) {
						$return = true;
					}

					break;
			}

			/**
			 * This hook is used to check buy product is valid.
			 * 
			 * @since 11.3.0
			 */
			return apply_filters('fgf_is_valid_buy_product', $return, self::$rule, $cart_content);
		}

		/**
		 * Get the selected get product.
		 *
		 * @return array
		 */
		public static function get_selected_get_product() {

			//Return buy product if BOGO gift type is the same product.
			if ('3' == self::$rule->get_rule_type() && '1' == self::$rule->get_bogo_gift_type()) {
				return array();
			}

			return self::prepare_bogo_valid_products();
		}

		/**
		 * Prepare the BOGO valid products.
		 *
		 * @return array.
		 */
		public static function prepare_bogo_valid_products() {
			$products = array();
			if ('5' == self::$rule->get_rule_type() && '2' == self::$rule->get_product_type()) {
				$products = self::get_valid_products(array(), self::prepare_selected_bogo_gift_categories(), 'category', false);
			} else {
				$products = self::get_valid_products(self::prepare_selected_bogo_gift_products());
			}

			return $products;
		}

		/**
		 * Prepare the selected BOGO gift products.
		 *
		 * @since 11.3.0
		 * @return array.
		 */
		public static function prepare_selected_bogo_gift_products() {
			if ('5' !== self::$rule->get_rule_type() || '2' !== self::$rule->get_bogo_get_gift_type()) {
				return self::$rule->get_products();
			}

			return array_intersect(fgf_get_normal_products_in_cart(), self::$rule->get_products());
		}

		/**
		 * Prepare the selected BOGO gift categories.
		 *
		 * @since 11.3.0
		 * @return array.
		 */
		public static function prepare_selected_bogo_gift_categories() {
			if ('5' !== self::$rule->get_rule_type()) {
				return array();
			}

			if ('2' !== self::$rule->get_bogo_get_gift_type()) {
				return self::$rule->get_categories();
			}

			return array_intersect(fgf_get_normal_taxonomy_products_in_cart(), self::$rule->get_categories());
		}

		/**
		 * Get BOGO product quantity.
		 */
		public static function get_bogo_buy_product_quantity( $buy_product_count ) {
			$quantity = 0;

			if ('2' == self::$rule->get_bogo_gift_repeat()) {
				$quantity = intval($buy_product_count / self::$rule->get_buy_product_count()) * self::$rule->get_product_count();
			} elseif (self::$rule->get_buy_product_count() <= $buy_product_count) {
				$quantity = self::$rule->get_product_count();
			}

			// Return same quantity ,if the repeat mode is unlimited,
			// Repeat limit is empty.
			if ('2' != self::$rule->get_bogo_gift_repeat_mode() || empty(self::$rule->get_bogo_gift_repeat_limit())) {
				return $quantity;
			}

			$quantity_limit = floatval(self::$rule->get_bogo_gift_repeat_limit()) * floatval(self::$rule->get_product_count());

			if ($quantity_limit >= $quantity) {
				return $quantity;
			}

			return $quantity_limit;
		}

		/**
		 * Validate the adding free gifts when the WooCommerce coupon is applied to the cart.
		 *
		 * @since 1.0.0
		 * @return bool
		 */
		public static function validate_applied_WC_coupons() {
			// Return false if the rule is coupon based.
			if (in_array(self::$rule->get_rule_type(), array( '4', '6' ))) {
				return false;
			}

			// Return false if the option "Restrict Free Gift if WooCommerce Coupon is used" is disabled in global level.
			if ('2' !== self::$rule->get_rule_restrict_by_wocommerce_coupon_type() && 'yes' !== get_option('fgf_settings_gift_restriction_based_coupon')) {
				return false;
			}

			// Return false if the option "Restrict Free Gift if WooCommerce Coupon is used" is disabled in rule level.
			if ('2' === self::$rule->get_rule_restrict_by_wocommerce_coupon_type() && '2' !== self::$rule->get_rule_restrict_by_wocommerce_coupon()) {
				return false;
			}

			return self::applied_coupon_exists_in_cart();
		}

		/**
		 * 
		 * Check if the applied coupon exists in the cart
		 * 
		 * @since 10.7.0
		 * @return boolean
		 */
		public static function applied_coupon_exists_in_cart() {
			if (isset(self::$applied_coupon_in_cart)) {
				return self::$applied_coupon_in_cart;
			}

			self::$applied_coupon_in_cart = false;
			// Return if cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return self::$applied_coupon_in_cart;
			}

			// Return false if no one coupon is not applied in the cart.
			if (empty(WC()->cart->get_applied_coupons())) {
				return self::$applied_coupon_in_cart;
			}

			foreach (WC()->cart->get_applied_coupons() as $code) {
				$coupon = new WC_Coupon($code);
				// Don't consider the free gift coupons.
				if ('fgf_free_gift' == $coupon->get_discount_type()) {
					continue;
				}

				self::$applied_coupon_in_cart = true;
				break;
			}

			return self::$applied_coupon_in_cart;
		}

		/**
		 * Check if gift products exists per order count.
		 *
		 * @return bool
		 * */
		public static function check_per_order_count_exists() {
			$free_gifts_products_count = floatval(get_option('fgf_settings_gifts_count_per_order'));

			// Restriction based on per order count exists
			if ($free_gifts_products_count && fgf_get_free_gift_products_count_in_cart(true) >= $free_gifts_products_count) {
				return true;
			}

			return false;
		}

		/**
		 * Check if rule is valid to display.
		 */
		public static function is_valid_rule() {
			if (!self::validate_rule_priority()) {
				return false;
			}

			self::$date_filter = self::validate_date();
			if (!self::$date_filter) {
				return false;
			}

			self::$user_filter = self::validate_users();
			if (!self::$user_filter) {
				return false;
			}

			self::$product_filter = self::validate_product_category();
			if (!self::$product_filter) {
				return false;
			}

			self::$criteria_filter = self::validate_rule_criteria();
			if (!self::$criteria_filter) {
				return false;
			}
			/**
			 * This hook is used to validate the rule gift products.
			 *
			 * @since 1.0
			 */
			return apply_filters('fgf_validate_gift_products_rule', true);
		}

		/**
		 * Validate the rule priority.
		 *
		 * @since 10.3.0
		 * @retrun Boolean
		 */
		public static function validate_rule_priority() {
			$valid_rules = array_merge(self::$manual_rule_products, self::$automatic_rule_products, self::$bogo_rule_products, self::$coupon_rule_products);

			return ( !empty($valid_rules) && '2' === self::$rule->get_rule_consider_type() ) ? false : true;
		}

		/**
		 * Validate rule usage count
		 */
		public static function validate_rule_usage_count() {
			// Return true if restriction count is empty.
			if (!floatval(self::$rule->get_rule_restriction_count())) {
				return true;
			}

			if (floatval(self::$rule->get_rule_restriction_count()) <= floatval(self::$rule->get_rule_usage_count())) {
				return false;
			}

			return true;
		}

		/**
		 * Get the rule gifts count per order.
		 *
		 * @since 10.0.0
		 * @return string/int
		 */
		public static function get_rule_gifts_count_per_order() {
			// Return if per order count is empty
			if ('1' === get_option('fgf_settings_gifts_count_per_order_type') || !floatval(self::$rule->get_rule_gifts_count_per_order())) {
				return '';
			}

			return floatval(self::$rule->get_rule_gifts_count_per_order());
		}

		/**
		 * Validate rule per order count
		 */
		public static function validate_rule_per_order_count() {
			// Return if per order count is empty
			if (!floatval(self::get_rule_gifts_count_per_order())) {
				return true;
			}

			// Return true for automatic, BOGO, Subtotal rules.
			if (!in_array(self::$rule->get_rule_type(), array( '1', '6' ))) {
				return true;
			}

			if (floatval(self::$rule->get_rule_gifts_count_per_order()) <= floatval(fgf_get_rule_products_count_in_cart(self::$rule->get_id()))) {
				return false;
			}

			return true;
		}

		/**
		 * Validate the rule user usage count.
		 *
		 * @var bool
		 */
		public static function validate_rule_user_usage_count() {
			// Return true if allowed the all users.
			if ('2' != self::$rule->get_rule_allowed_user_type()) {
				return true;
			}

			/**
			 * This hook is used to validate the user.
			 *
			 * @since 1.0
			 */
			if (!apply_filters('fgf_allow_user_rule_usage_count', is_user_logged_in())) {
				return false;
			}

			// Validate the user used the rule based on the order count.
			if (self::validate_user_rule_used_count()) {
				return false;
			}

			// Validate the user purchased order count.
			if (self::validate_user_purchased_order_count()) {
				return false;
			}

			return true;
		}

		/**
		 * Validate the user purchased order count.
		 *
		 * @since 8.8
		 *
		 * @return bool
		 */
		public static function validate_user_purchased_order_count() {
			$return = false;
			switch (self::$rule->get_rule_user_purchased_order_count_type()) {
				case '3':
					$order_count = wc_get_customer_order_count(get_current_user_id());

					// Validate the minimum user order count.
					if (self::$rule->get_rule_user_purchased_order_min_count() && self::$rule->get_rule_user_purchased_order_min_count() > $order_count) {
						$return = true;
					}

					// Validate the maximum user order count.
					if (self::$rule->get_rule_user_purchased_order_max_count() && self::$rule->get_rule_user_purchased_order_max_count() < $order_count) {
						$return = true;
					}
					break;
				case '2':
					$order_count = wc_get_customer_order_count(get_current_user_id());
					if ($order_count) {
						$return = true;
					}
					break;
			}

			return $return;
		}

		/**
		 * Validate the user rule used count.
		 *
		 * @since 8.8
		 *
		 * @return bool
		 */
		public static function validate_user_rule_used_count() {
			$user_usage_array = self::$rule->get_rule_allowed_user_usage_count();
			// Return true, if the allowed user usage count does not exists.
			if (!fgf_check_is_array($user_usage_array)) {
				return false;
			}

			$current_user_id = get_current_user_id();
			// Return true, if the allowed current user usage count does not exists.
			if (!isset($user_usage_array[$current_user_id]['count'])) {
				return false;
			}

			if (self::$rule->get_rule_allowed_user_count() && floatval(self::$rule->get_rule_allowed_user_count()) <= floatval($user_usage_array[$current_user_id]['count'])) {
				return true;
			}

			return false;
		}

		/**
		 * Validate date.
		 *
		 * @return bool
		 */
		public static function validate_date() {
			if (!self::validate_from_to_date()) {
				return false;
			}

			if (!self::validate_weekdays()) {
				return false;
			}
			/**
			 * This hook is used to validate the rule date filters.
			 *
			 * @since 1.0
			 */
			return apply_filters('fgf_validate_rule_date_filter', true, self::$rule);
		}

		/**
		 * Validate from/to date
		 */
		public static function validate_from_to_date() {
			$return = false;
			$from_date = true;
			$to_date = true;
			$current_date_object = FGF_Date_Time::get_date_time_object('now');
			// Validate from date
			if (self::$rule->get_parsed_from_date()) {
				$from_date_object = FGF_Date_Time::get_date_time_object(self::$rule->get_parsed_from_date());
				if ($from_date_object > $current_date_object) {
					$from_date = false;
				}
			}
			// Validate to date
			if (self::$rule->get_parsed_to_date()) {
				$to_date_object = FGF_Date_Time::get_date_time_object(self::$rule->get_parsed_to_date());
				if ($to_date_object < $current_date_object) {
					$to_date = false;
				}
			}

			if ($from_date && $to_date) {
				$return = true;
			}
			/**
			 * This hook is used to validate the rule dates.
			 *
			 * @since 1.0
			 */
			return apply_filters('fgf_validate_rule_from_to_date', $return, self::$rule);
		}

		/**
		 * Validate the weekdays restriction.
		 *
		 * @return bool
		 */
		public static function validate_weekdays() {
			$return = false;
			$today = gmdate('N', current_time('timestamp'));

			if (!fgf_check_is_array(self::$rule->get_rule_week_days_validation()) || in_array($today, self::$rule->get_rule_week_days_validation())) {
				$return = true;
			}
			/**
			 * This hook is used to validate the rule week days.
			 *
			 * @since 1.0
			 */
			return apply_filters('fgf_validate_rule_week_days', $return, self::$rule);
		}

		/**
		 * Validate rule criteria
		 */
		public static function validate_rule_criteria( $only_min = false ) {

			$cart_subtotal_criteria = self::validate_cart_total_criteria($only_min);
			$cart_quantity_criteria = self::validate_cart_quantity_criteria($only_min);
			$cart_product_count_criteria = self::validate_product_count_criteria($only_min);

			if (( self::$rule->get_condition_type() == '2' ) && ( !( $cart_subtotal_criteria || $cart_quantity_criteria || $cart_product_count_criteria ) )) {
				return false;
			} elseif (( self::$rule->get_condition_type() == '1' ) && ( !( $cart_subtotal_criteria && $cart_quantity_criteria && $cart_product_count_criteria ) )) {
				return false;
			}
			/**
			 * This hook is used to validate the rule criteria.
			 *
			 * @since 1.0
			 */
			return apply_filters('fgf_validate_rule_criteria', true, self::$rule, $only_min);
		}

		/**
		 * Validate cart total criteria
		 */
		public static function validate_cart_total_criteria( $only_min = false ) {
			$minimum_cart_subtotal = true;
			$maximum_cart_subtotal = true;

			switch (self::$rule->get_total_type()) {
				case '2':
					$total = fgf_get_wc_cart_total();
					break;
				case '3':
					$total = fgf_get_wc_cart_category_subtotal(self::$rule->get_cart_categories(), 'product_cat', self::$rule->get_consider_cart_subcategories_total(), self::$rule->is_exclude_category_subtotal_discount_amount());
					break;
				default:
					$total = fgf_get_wc_cart_subtotal();
					break;
			}

			/**
			 * This hook is used to alter the rule cart criteria total.
			 *
			 * @since 8.6
			 */
			$total = apply_filters('fgf_rule_cart_criteria_total', $total, self::$rule);

			/**
			 * This hook is used to alter the rule minimum cart subtotal.
			 *
			 * @since 1.0
			 */
			$min_subtotal = apply_filters('fgf_rule_minimum_cart_subtotal', fgf_convert_price_to_base_currency(self::$rule->get_cart_subtotal_minimum_value(), self::$rule), self::$rule);
			/**
			 * This hook is used to alter the rule maximum cart subtotal.
			 *
			 * @since 1.0
			 */
			$max_subtotal = apply_filters('fgf_rule_maximum_cart_subtotal', fgf_convert_price_to_base_currency(self::$rule->get_cart_subtotal_maximum_value(), self::$rule), self::$rule);
			// Validate minimum cart subtotal
			if ($min_subtotal && $min_subtotal > $total) {
				$minimum_cart_subtotal = false;
			}

			// Validate maximum cart subtotal
			if (!$only_min && $max_subtotal && $max_subtotal < $total) {
				$maximum_cart_subtotal = false;
			}

			if ($minimum_cart_subtotal && $maximum_cart_subtotal) {
				return true;
			}

			return false;
		}

		/**
		 * Validate Cart Quantity criteria
		 */
		public static function validate_cart_quantity_criteria( $only_min = false ) {
			if (!is_object(WC()->cart)) {
				return true;
			}

			$minimum_cart_quantity = true;
			$maximum_cart_quantity = true;
			$cart_quantity = intval(WC()->cart->get_cart_contents_count()) - fgf_get_free_gift_products_count_in_cart();

			// Validate minimum cart quantity
			if (self::$rule->get_quantity_minimum_value() && self::$rule->get_quantity_minimum_value() > $cart_quantity) {
				$minimum_cart_quantity = false;
			}

			// Validate maximum cart quantity
			if (!$only_min && self::$rule->get_quantity_maximum_value() && self::$rule->get_quantity_maximum_value() < $cart_quantity) {
				$maximum_cart_quantity = false;
			}

			if ($minimum_cart_quantity && $maximum_cart_quantity) {
				return true;
			}

			return false;
		}

		/**
		 * Validate Cart Product count criteria.
		 */
		public static function validate_product_count_criteria( $only_min = false ) {
			$minimum_cart_item = true;
			$maximum_cart_item = true;
			$cart_item_count = fgf_get_cart_item_count();

			// Validate minimum cart quantity
			if (self::$rule->get_product_count_min_value() && self::$rule->get_product_count_min_value() > $cart_item_count) {
				$minimum_cart_item = false;
			}

			// Validate maximum cart quantity
			if (!$only_min && self::$rule->get_product_count_max_value() && self::$rule->get_product_count_max_value() < $cart_item_count) {
				$maximum_cart_item = false;
			}

			if ($minimum_cart_item && $maximum_cart_item) {
				return true;
			}

			return false;
		}

		/**
		 * Validate the users.
		 *
		 * @return bool.
		 */
		public static function validate_users() {
			$return = false;
			switch (self::$rule->get_user_filter_type()) {

				case '2':
					$user_id = get_current_user_id();
					if (in_array($user_id, self::$rule->get_include_users())) {
						$return = true;
					}

					break;
				case '3':
					$return = true;
					$user_id = get_current_user_id();
					if (in_array($user_id, self::$rule->get_exclude_users())) {
						$return = false;
					}

					break;
				case '4':
					$user = wp_get_current_user();

					// Loggedin user restriction
					if (fgf_check_is_array($user->roles)) {
						foreach ($user->roles as $role) {
							if (in_array($role, self::$rule->get_include_user_roles())) {
								$return = true;
							}
						}
						// Guest user restriction
					} else if (in_array('guest', self::$rule->get_include_user_roles())) {
						$return = true;
					}

					break;
				case '5':
					$return = true;
					$user = wp_get_current_user();

					// Loggedin user restriction
					if (fgf_check_is_array($user->roles)) {
						foreach ($user->roles as $role) {
							if (in_array($role, self::$rule->get_exclude_user_roles())) {
								$return = false;
							}
						}
						// Guest user restriction
					} else if (in_array('guest', self::$rule->get_exclude_user_roles())) {
						$return = false;
					}

					break;
				default:
					$return = true;
					break;
			}

			/**
			 * This hook is used to validate the rule users.
			 *
			 * @since 9.2
			 */
			return apply_filters('fgf_validate_rule_users', $return, self::$rule);
		}

		/**
		 * Validate Products/Categories
		 */
		public static function validate_product_category() {
			// Validate the if the cart contains only virtual products.
			if ('1' !== self::$rule->get_virtual_product_restriction() && fgf_cart_contains_only_virtual_products()) {
				return false;
			}

			// return if selected as all products
			if (self::$rule->get_product_filter_type() == '1') {
				return true;
			}

			// Return if cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return true;
			}

			$cart_contents = WC()->cart->get_cart();

			if (!fgf_check_is_array($cart_contents)) {
				return true;
			}

			$return = false;
			$product_ids = array();
			$category_ids = array();
			$category_product_count = 0;
			foreach ($cart_contents as $cart_content) {

				if (isset($cart_content['fgf_gift_product'])) {
					continue;
				}

				/**
				 * This hook is used to alter the skip product validation.
				 * 
				 * @since 11.5.0
				 */
				if (apply_filters('fgf_skip_product_validation', false, self::$rule, $cart_content)) {
					continue;
				}

				switch (self::$rule->get_product_filter_type()) {
					case '2':
						// return if any selected products in the cart
						if (in_array($cart_content['product_id'], self::$rule->get_include_products())) {
							if ('1' == self::$rule->get_applicable_products_type()) {
								return true;
							}

							$product_ids[] = $cart_content['product_id'];
						} elseif (in_array($cart_content['variation_id'], self::$rule->get_include_products())) {
							if ('1' == self::$rule->get_applicable_products_type()) {
								return true;
							}

							$product_ids[] = $cart_content['variation_id'];
						} else {
							$product_ids[] = $cart_content['product_id'];
						}

						break;
					case '3':
						$return = true;
						// excluded products.
						if (in_array($cart_content['product_id'], self::$rule->get_exclude_products()) || in_array($cart_content['variation_id'], self::$rule->get_exclude_products())) {
							return false;
						}
						break;
					case '4':
						// All Categories.
						$product_categories = get_the_terms($cart_content['product_id'], 'product_cat');
						if (fgf_check_is_array($product_categories)) {
							return true;
						}
						break;
					case '5':
						//included categories.
						$product_categories = get_the_terms($cart_content['product_id'], 'product_cat');

						if (fgf_check_is_array($product_categories)) {
							foreach ($product_categories as $product_category) {
								$current_category_id = $product_category->term_id;
								// return if any selected categories products in the cart.
								if ('1' == self::$rule->get_applicable_categories_type() && in_array($product_category->term_id, self::$rule->get_include_categories())) {
									return true;
								} elseif (in_array($product_category->term_id, self::$rule->get_include_categories())) {
									break;
								}
							}

							// return if all the selected products/categories in the cart.
							if (in_array($current_category_id, self::$rule->get_include_categories())) {
								$category_product_count += $cart_content['quantity'];
							}

							$category_ids[] = $current_category_id;
						}
						break;
					case '6':
						// excluded categories.
						$return = true;
						$product_categories = get_the_terms($cart_content['product_id'], 'product_cat');
						if (fgf_check_is_array($product_categories)) {
							foreach ($product_categories as $product_category) {
								if (in_array($product_category->term_id, self::$rule->get_exclude_categories())) {
									return false;
								}
							}
						}
						break;
				}
			}

			//For include products filter.
			if ('2' == self::$rule->get_product_filter_type()) {
				if ('4' == self::$rule->get_applicable_products_type()) {
					$return = self::validate_applicable_product_count(self::$rule->get_include_product_count(), self::$rule->get_include_products(), $product_ids);
				} else {
					$return = self::validate_applicable_product_category(self::$rule->get_applicable_products_type(), self::$rule->get_include_products(), $product_ids);
				}
			} elseif ('5' == self::$rule->get_product_filter_type()) {
				if ('4' == self::$rule->get_applicable_categories_type()) {
					$return = ( $category_product_count >= floatval(self::$rule->get_include_category_product_count()) );
				} else {
					//For include categories filter.
					$return = self::validate_applicable_product_category(self::$rule->get_applicable_categories_type(), self::$rule->get_include_categories(), $category_ids);
				}
			}

			/**
			 * This hook is used to alter the rule product category filter.
			 *
			 * @since 9.4.0
			 */
			return apply_filters('fgf_rule_product_category_filter', $return, self::$rule);
		}

		/**
		 * Validate the applicable products or categories in the cart.
		 *
		 * @param string $applicable_type
		 * @param array $selected_data
		 * @param array $current_data
		 * @return boolean
		 */
		public static function validate_applicable_product_category( $applicable_type, $selected_data, $current_data ) {
			// Return if all the selected products/categories in the cart.
			if ('2' == $applicable_type && empty(array_diff($selected_data, $current_data))) {
				return true;
			} elseif ('3' == $applicable_type && empty(array_diff($current_data, $selected_data)) && empty(array_diff($selected_data, $current_data))) {
				// Return if only the selected products/categories in the cart.
				return true;
			}

			return false;
		}

		/**
		 * Validate the applicable product count in the cart.
		 *
		 * @param string $product_count
		 * @param array $include_product_ids
		 * @param array $cart_product_ids
		 * @return boolean
		 */
		public static function validate_applicable_product_count( $product_count, $include_product_ids, $cart_product_ids ) {
			return count(array_intersect($cart_product_ids, $include_product_ids)) == $product_count;
		}

		/**
		 * Check if the rule is valid to display notice.
		 *
		 * @return bool
		 */
		public static function is_valid_notice_rule() {
			if (!self::$date_filter || !self::$user_filter || !self::$product_filter) {
				return false;
			}

			// Skip the notice for coupon based rules.
			if (in_array(self::$rule->get_rule_type(), array( '4', '6' ))) {
				return false;
			}

			// Skip the notice if the notice did not enable.
			if ('2' != self::$rule->get_show_notice()) {
				return false;
			}

			// Skip the notice if the notice content did not provide.
			if (empty(self::$rule->get_notice())) {
				return false;
			}

			// Skip the notice if the maximum gift count is reached and rules are manual or automatic.
			if (in_array(self::$rule->get_rule_type(), array( '1', '2' )) && self::has_reached_maximum_gift_count()) {
				return false;
			}

			if (self::validate_rule_criteria(true)) {
				return false;
			}
			/**
			 * This hook is used to validate the rule gift products notice.
			 *
			 * @since 1.0
			 */
			return apply_filters('fgf_validate_gift_products_notice_rule', true);
		}

		/**
		 * Get the rule notice.
		 *
		 * @return string
		 */
		public static function get_rule_notice() {
			$shortcodes = self::get_notice_shortcodes();

			$notice = fgf_get_rule_translated_string('fgf_rule_notice_' . self::$rule->get_id(), self::$rule->get_notice());
			$notice = str_replace(array_keys($shortcodes), array_values($shortcodes), $notice);

			return array( 'notice' => wpautop(wptexturize($notice)), 'icon_url' => self::$rule->get_notice_image_url() );
		}

		/**
		 * Get the rule bulk eligible notice.
		 *
		 * @since 12.0.0
		 * @param array $bulk_rule
		 * @return string
		 */
		public static function get_rule_bulk_eligible_notice( $bulk_rule ) {
			$shortcodes = self::get_bulk_eligible_notice_shortcodes($bulk_rule);

			$notice = fgf_get_rule_translated_string('fgf_rule_bulk_eligible_notice_' . self::$rule->get_id(), self::$rule->get_bulk_eligible_notice());
			$notice = str_replace(array_keys($shortcodes), array_values($shortcodes), $notice);

			return array( 'notice' => wpautop(wptexturize($notice)), 'icon_url' => self::$rule->get_bulk_notice_image_url() );
		}

		/**
		 * Get the bulk eligible notice short codes.
		 *
		 * @since 12.0.0
		 * @param array $bulk_rule
		 * @return array
		 */
		public static function get_bulk_eligible_notice_shortcodes( $bulk_rule ) {
			if (!is_object(WC()->cart)) {
				return array();
			}
			
			$shortcodes = array(
				'[free_gift_qty]' => $bulk_rule['gift_quantity'],
				'[free_gifts]'=>self::$rule->get_prepared_bulk_gift_details($bulk_rule),
			);

			if (in_array(self::$rule->get_rule_type(), array( '9', '10' ))) {
				$shortcodes['[free_gift_min_price]']= fgf_price(self::get_rule_bulk_pricing_notice_total($bulk_rule['minimum']), false);
				$shortcodes['[free_gift_max_price]']= fgf_price($bulk_rule['maximum'], false);
				$shortcodes['[cart_subtotal]']= fgf_price(fgf_get_wc_cart_subtotal(), false);
				$shortcodes['[cart_order_total]']= fgf_price(fgf_get_wc_cart_total(), false);
				$shortcodes['[cart_category_subtotal]']= fgf_price(fgf_get_wc_cart_category_subtotal(self::$rule->get_cart_categories(), 'product_cat', self::$rule->get_consider_cart_subcategories_total(), self::$rule->is_exclude_category_subtotal_discount_amount()), false);
			} else {
				$cart_quantity = intval(WC()->cart->get_cart_contents_count()) - fgf_get_free_gift_products_count_in_cart();
				$shortcodes['[free_gift_min_qty]']= $bulk_rule['minimum']-$cart_quantity;
				$shortcodes['[free_gift_max_qty]']= $bulk_rule['maximum'];
				$shortcodes['[cart_quantity]']= $cart_quantity;
			}

			/**
			 * This hook is used to alter the bulk eligible notice short codes.
			 *
			 * @since 12.0.0
			 */
			return apply_filters('fgf_bulk_eligible_notice_shortcodes', $shortcodes, self::$rule);
		}

		/**
		 * Get the notice short codes.
		 *
		 * @return array
		 */
		public static function get_notice_shortcodes() {
			if (!is_object(WC()->cart)) {
				return array();
			}

			$shortcodes = array(
				'[free_gift_min_sub_total]' => fgf_price(self::get_rule_notice_total(1), false),
				'[free_gift_min_order_total]' => fgf_price(self::get_rule_notice_total(2), false),
				'[free_gift_min_category_sub_total]' => fgf_price(self::get_rule_notice_total(3), false),
				'[free_gift_min_cart_qty]' => self::get_rule_notice_cart_quantity(),
				'[free_gift_min_product_count]' => self::get_rule_notice_product_count(),
				'[cart_sub_total]' => fgf_price(fgf_get_wc_cart_subtotal(), false),
				'[cart_order_total]' => fgf_price(fgf_get_wc_cart_total(), false),
				'[cart_category_sub_total]' => fgf_price(fgf_get_wc_cart_category_subtotal(self::$rule->get_cart_categories(), 'product_cat', self::$rule->get_consider_cart_subcategories_total(), self::$rule->is_exclude_category_subtotal_discount_amount()), false),
				'[cart_quantity]' => intval(WC()->cart->get_cart_contents_count()) - fgf_get_free_gift_products_count_in_cart(),
				'[cart_product_count]' => intval(fgf_get_cart_item_count()),
			);
			/**
			 * This hook is used to alter the notice short codes.
			 *
			 * @since 1.0
			 */
			return apply_filters('fgf_notice_shortcodes', $shortcodes, self::$rule);
		}

		/**
		 * Get the rule bulk pricing notice total.
		 *
		 * @since 12.0.0
		 * @param float $minimum
		 * @return float
		 */
		public static function get_rule_bulk_pricing_notice_total( $minimum ) {

			switch (self::$rule->get_bulk_pricing_total_type()) {
				case '2':
					$total = fgf_get_wc_cart_total();
					break;
				case '3':
					$total = fgf_get_wc_cart_category_subtotal(self::$rule->get_bulk_pricing_categories(), 'product_cat', self::$rule->get_consider_bulk_pricing_subcategories_total(), self::$rule->is_exclude_bulk_pricing_category_subtotal_discount_amount());
					break;
				default:
					$total = fgf_get_wc_cart_subtotal();
					break;
			}

			/**
			 * This hook is used to alter the rule bulk pricing cart criteria total.
			 *
			 * @since 12.0.0
			 */
			$total = apply_filters('fgf_rule_bulk_pricing_cart_criteria_total', $total, self::$rule);

			/**
			 * This hook is used to alter the rule bulk pricing minimum cart subtotal.
			 *
			 * @since 12.0.0
			 */
			$min_subtotal = apply_filters('fgf_rule_bulk_pricing_minimum_cart_subtotal', fgf_convert_price_to_base_currency($minimum, self::$rule), self::$rule);
			// Validate minimum cart subtotal
			if (!$min_subtotal || $min_subtotal <= $total) {
				return 0;
			}

			return $min_subtotal - $total;
		}

		/**
		 * Get the rule notice total.
		 *
		 * @return float
		 */
		public static function get_rule_notice_total( $total_type ) {

			if ($total_type != self::$rule->get_total_type()) {
				return 0;
			}

			switch (self::$rule->get_total_type()) {
				case '2':
					$total = fgf_get_wc_cart_total();
					break;
				case '3':
					$total = fgf_get_wc_cart_category_subtotal(self::$rule->get_cart_categories(), 'product_cat', self::$rule->get_consider_cart_subcategories_total(), self::$rule->is_exclude_category_subtotal_discount_amount());
					break;
				default:
					$total = fgf_get_wc_cart_subtotal();
					break;
			}

			/**
			 * This hook is used to alter the rule cart criteria total.
			 *
			 * @since 8.6.0
			 */
			$total = apply_filters('fgf_rule_cart_criteria_total', $total, self::$rule);

			/**
			 * This hook is used to alter the rule minimum cart subtotal.
			 *
			 * @since 1.0.0
			 */
			$min_subtotal = apply_filters('fgf_rule_minimum_cart_subtotal', fgf_convert_price_to_base_currency(self::$rule->get_cart_subtotal_minimum_value(), self::$rule), self::$rule);
			// Validate minimum cart subtotal
			if (!$min_subtotal || $min_subtotal <= $total) {
				return 0;
			}

			return $min_subtotal - $total;
		}

		/**
		 * Get the rule notice cart quantity.
		 *
		 * @return int
		 */
		public static function get_rule_notice_cart_quantity() {
			if (!is_object(WC()->cart)) {
				return 0;
			}

			$cart_quantity = intval(WC()->cart->get_cart_contents_count()) - fgf_get_free_gift_products_count_in_cart();

			// Validate minimum cart quantity
			if (!self::$rule->get_quantity_minimum_value() || self::$rule->get_quantity_minimum_value() <= $cart_quantity) {
				return 0;
			}

			return self::$rule->get_quantity_minimum_value() - $cart_quantity;
		}

		/**
		 * Get the rule notice product count.
		 *
		 * @return int
		 */
		public static function get_rule_notice_product_count() {
			$cart_item_count = fgf_get_cart_item_count();

			// Validate minimum cart quantity
			if (!self::$rule->get_product_count_min_value() || self::$rule->get_product_count_min_value() <= $cart_item_count) {
				return 0;
			}

			return self::$rule->get_product_count_min_value() - $cart_item_count;
		}

		/**
		 * Reset
		 */
		public static function reset() {
			self::$gift_products = null;
			self::$valid_gift_products = null;
			self::$manual_gift_products = null;
			self::$automatic_gift_products = null;
			self::$bogo_gift_products = null;
			self::$manual_bogo_gift_products = null;
			self::$coupon_gift_products = null;
			self::$coupon_manual_gift_products = null;
			self::$overall_manual_gift_products = null;
			self::$manual_product_already_exists = array();
			self::$manual_product_exists = null;
			self::$cart_notices = null;
			self::$manual_rule_products = null;
			self::$automatic_rule_products = null;
			self::$bogo_rule_products = null;
			self::$coupon_rule_products = null;
			self::$manual_gift_products_in_cart = null;
			self::$automatic_gift_products_in_cart = null;
			self::$rule_gift_products_count = null;
			self::$bogo_manual_rule_gift_products_count = null;
			self::$added_gift_products_count = null;
			self::$rule_unlimited_products_exists = false;
			self::$applied_coupon_in_cart = null;
		}

		/**
		 * Set the default filter.
		 */
		public static function set_default_filter() {
			self::$rule = false;
			self::$date_filter = false;
			self::$criteria_filter = false;
			self::$user_filter = false;
			self::$product_filter = false;
		}
	}

}
