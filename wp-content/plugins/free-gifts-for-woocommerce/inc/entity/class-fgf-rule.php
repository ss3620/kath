<?php

/**
 * Rule.
 * 
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Rule')) {

	/**
	 * Class.
	 * 
	 * @since 1.0.0
	 */
	class FGF_Rule extends FGF_Post {

		/**
		 * Post Type.
		 * 
		 * @since 1.0.0
		 * @var string
		 */
		protected $post_type = FGF_Register_Post_Types::RULES_POSTTYPE;

		/**
		 * Post Status.
		 * 
		 * @since 1.0.0
		 * @var string
		 */
		protected $post_status = 'fgf_active';

		/**
		 * Post keys.
		 * 
		 * @since 11.1.0
		 * @var array.
		 * */
		protected $post_keys = array(
			'description' => 'post_content',
		);

		/**
		 * Meta data keys.
		 * 
		 * @since 1.0.0
		 * @var array
		 */
		protected $meta_data_keys = array(
			'fgf_rule_type' => '1',
			'fgf_rule_consider_type' => '1',
			'fgf_gift_type' => '1',
			'fgf_gift_products' => array(),
			'fgf_gift_categories' => array(),
			'fgf_automatic_product_qty' => '',
			'fgf_bogo_gift_type' => '1',
			'fgf_buy_product_type' => '1',
			'fgf_buy_category_type' => '1',
			'fgf_buy_product' => array(),
			'fgf_buy_categories' => array(),
			'fgf_buy_product_consider_type' => '1',
			'fgf_buy_category_consider_type' => '1',
			'fgf_buy_product_quantity_consider_type' => '1',
			'fgf_get_product_type' => '1',
			'fgf_get_products' => array(),
			'fgf_get_categories' => array(),
			'fgf_buy_quantity_type' => '1',
			'fgf_bogo_get_gift_type' => '1',
			'fgf_buy_product_count' => '',
			'fgf_get_product_count' => '',
			'fgf_bogo_gift_repeat' => '',
			'fgf_bogo_gift_repeat_mode' => '1',
			'fgf_bogo_gift_repeat_limit' => '',
			'fgf_apply_coupon' => array(),
			'fgf_coupon_gift_products' => array(),
			'fgf_coupon_gift_products_qty' => '1',
			'fgf_subtotal_price_type' => '1',
			'fgf_total_categories' => array(),
			'fgf_consider_subcategories_total' => 1,
			'fgf_calculate_total_discounted_category_total' => 1,
			'fgf_subtotal_price' => '',
			'fgf_subtotal_gift_type' => '1',
			'fgf_subtotal_gift_products' => array(),
			'fgf_subtotal_gift_categories' => array(),
			'fgf_subtotal_gift_products_qty' => '1',
			'fgf_subtotal_repeat_gift' => 'no',
			'fgf_subtotal_repeat_gift_mode' => '1',
			'fgf_subtotal_repeat_gift_limit' => '1',
			'fgf_bulk_quantity_rules_consider_type' => '1',
			'fgf_bulk_quantity_rules' => array(),
			'fgf_bulk_pricing_total_type' => '1',
			'fgf_bulk_pricing_categories' => array(),
			'fgf_consider_bulk_pricing_subcategories_total' => '1',
			'fgf_calculate_bulk_pricing_discounted_category_total' => '1',
			'fgf_bulk_pricing_rules_consider_type' => '1',
			'fgf_bulk_pricing_rules' => array(),
			'fgf_cheapest_gift_price_type' => '1',
			'fgf_cheapest_gift_quantity' => '1',
			'fgf_rule_valid_from_date' => '',
			'fgf_rule_valid_to_date' => '',
			'fgf_rule_week_days_validation' => array(),
			'fgf_rule_gifts_count_per_order' => '',
			'fgf_rule_usage_count' => '',
			'fgf_rule_restriction_count' => '',
			'fgf_rule_allowed_user_type' => '1',
			'fgf_rule_allowed_user_count' => '1',
			'fgf_rule_allowed_user_usage_count' => array(),
			'fgf_rule_user_purchased_order_count_type' => 1,
			'fgf_rule_user_purchased_order_min_count' => 1,
			'fgf_rule_user_purchased_order_max_count' => 1,
			'fgf_rule_restrict_by_wocommerce_coupon_type' => '1',
			'fgf_rule_restrict_by_wocommerce_coupon' => '1',
			'fgf_condition_type' => '',
			'fgf_total_type' => '',
			'fgf_cart_categories' => array(),
			'fgf_consider_cart_subcategories_total' => 1,
			'fgf_exclude_category_subtotal_discount_amount' => '',
			'fgf_cart_subtotal_min_value' => '',
			'fgf_cart_subtotal_max_value' => '',
			'fgf_quantity_min_value' => '',
			'fgf_quantity_max_value' => '',
			'fgf_product_count_min_value' => '',
			'fgf_product_count_max_value' => '',
			'fgf_user_filter_type' => '',
			'fgf_include_users' => array(),
			'fgf_exclude_users' => array(),
			'fgf_include_user_roles' => array(),
			'fgf_exclude_user_roles' => array(),
			'fgf_product_filter_type' => '',
			'fgf_include_products' => array(),
			'fgf_include_product_count' => '',
			'fgf_exclude_products' => array(),
			'fgf_applicable_products_type' => '',
			'fgf_applicable_categories_type' => '',
			'fgf_include_categories' => array(),
			'fgf_include_category_product_count' => '1',
			'fgf_exclude_categories' => array(),
			'fgf_virtual_product_restriction' => '1',
			'fgf_show_notice' => '1',
			'fgf_notice' => '',
			'fgf_notice_image_id' => '',
			'fgf_show_bogo_eligible_notice'=>'1',
			'fgf_bogo_eligible_notice' => '',
			'fgf_show_bulk_eligible_notice'=>'1',
			'fgf_bulk_eligible_notice' => '',
			'fgf_bulk_notice_image_id' => '',
		);

		/**
		 * Duplicate meta data keys.
		 * 
		 * @since 9.9.0
		 * @var array
		 */
		protected $duplicate_meta_keys = array(
			'fgf_rule_type',
			'fgf_rule_consider_type',
			'fgf_gift_type',
			'fgf_gift_products',
			'fgf_gift_categories',
			'fgf_bogo_gift_type',
			'fgf_buy_product_type',
			'fgf_buy_product',
			'fgf_buy_categories',
			'fgf_buy_product_consider_type',
			'fgf_buy_category_consider_type',
			'fgf_buy_category_type',
			'fgf_buy_product_quantity_consider_type',
			'fgf_get_product_type',
			'fgf_get_products',
			'fgf_get_categories',
			'fgf_buy_quantity_type',
			'fgf_bogo_get_gift_type',
			'fgf_buy_product_count',
			'fgf_get_product_count',
			'fgf_bogo_gift_repeat',
			'fgf_bogo_gift_repeat_mode',
			'fgf_bogo_gift_repeat_limit',
			'fgf_apply_coupon',
			'fgf_coupon_gift_products',
			'fgf_coupon_gift_products_qty',
			'fgf_subtotal_price_type',
			'fgf_total_categories',
			'fgf_consider_subcategories_total',
			'fgf_calculate_total_discounted_category_total',
			'fgf_subtotal_price',
			'fgf_subtotal_gift_type',
			'fgf_subtotal_gift_products',
			'fgf_subtotal_gift_categories',
			'fgf_subtotal_gift_products_qty',
			'fgf_subtotal_repeat_gift',
			'fgf_subtotal_repeat_gift_mode',
			'fgf_subtotal_repeat_gift_limit',
			'fgf_bulk_quantity_rules_consider_type',
			'fgf_bulk_quantity_rules',
			'fgf_bulk_pricing_total_type',
			'fgf_bulk_pricing_categories',
			'fgf_consider_bulk_pricing_subcategories_total',
			'fgf_calculate_bulk_pricing_discounted_category_total',
			'fgf_bulk_pricing_rules_consider_type',
			'fgf_bulk_pricing_rules',
			'fgf_cheapest_gift_price_type',
			'fgf_cheapest_gift_quantity',
			'fgf_rule_valid_from_date',
			'fgf_rule_valid_to_date',
			'fgf_rule_week_days_validation',
			'fgf_automatic_product_qty',
			'fgf_rule_gifts_count_per_order',
			'fgf_rule_usage_count',
			'fgf_rule_restriction_count',
			'fgf_rule_allowed_user_type',
			'fgf_rule_allowed_user_count',
			'fgf_rule_allowed_user_usage_count',
			'fgf_rule_user_purchased_order_count_type',
			'fgf_rule_user_purchased_order_min_count',
			'fgf_rule_user_purchased_order_max_count',
			'fgf_exclude_category_subtotal_discount_amount',
			'fgf_rule_restrict_by_wocommerce_coupon_type',
			'fgf_rule_restrict_by_wocommerce_coupon',
			'fgf_condition_type',
			'fgf_total_type',
			'fgf_cart_categories',
			'fgf_consider_cart_subcategories_total',
			'fgf_cart_subtotal_min_value',
			'fgf_cart_subtotal_max_value',
			'fgf_quantity_min_value',
			'fgf_quantity_max_value',
			'fgf_product_count_min_value',
			'fgf_product_count_max_value',
			'fgf_show_notice',
			'fgf_notice',
			'fgf_notice_image_id',
			'fgf_show_bogo_eligible_notice',
			'fgf_bogo_eligible_notice',
			'fgf_show_bulk_eligible_notice',
			'fgf_bulk_eligible_notice',
			'fgf_bulk_notice_image_id',
			'fgf_user_filter_type',
			'fgf_include_users',
			'fgf_exclude_users',
			'fgf_include_user_roles',
			'fgf_exclude_user_roles',
			'fgf_product_filter_type',
			'fgf_include_products',
			'fgf_include_product_count',
			'fgf_exclude_products',
			'fgf_applicable_products_type',
			'fgf_applicable_categories_type',
			'fgf_include_categories',
			'fgf_include_category_product_count',
			'fgf_exclude_categories',
			'fgf_virtual_product_restriction',
		);

		/**
		 * Compatibility meta data keys.
		 * 
		 * @since 9.4.0
		 * @var array
		 */
		protected $compatibility_meta_data_keys = array(
			'fgf_gift_brands' => array(),
			'fgf_cart_brands' => array(),
			'fgf_applicable_brands_type' => '1',
			'fgf_include_brands' => array(),
			'fgf_exclude_brands' => array(),
			'fgf_brand_product_count' => '',
			'fgf_buy_product_brands' => array(),
			'fgf_buy_brand_consider_type' => '1',
			'fgf_buy_brand_quantity_consider_type' => '1',
			'fgf_get_product_brands' => array(),
			'fgf_subtotal_gift_total_brands' => array(),
			'fgf_subtotal_gift_brands' => array(),
		);

		/**
		 * Get the formatted created date time.
		 * 
		 * @since 1.0.0
		 * @return string
		 */
		public function get_formatted_created_date() {
			return FGF_Date_Time::get_wp_format_datetime($this->get_created_date());
		}

		/**
		 * Get the formatted modified date time.
		 * 
		 * @since 1.0.0
		 * @return string
		 */
		public function get_formatted_modified_date() {
			return FGF_Date_Time::get_wp_format_datetime($this->get_modified_date());
		}

		/**
		 * Get the notice image URL.
		 * 
		 * @since 1.0.0
		 * @return string
		 */
		public function get_notice_image_url() {
			return ( $this->get_notice_image_id() ) ? wp_get_attachment_image_url($this->get_notice_image_id()) : '';
		}

		/**
		 * Get the bulk notice image URL.
		 * 
		 * @since 12.0.0
		 * @return string
		 */
		public function get_bulk_notice_image_url() {
			return ( $this->get_bulk_notice_image_id() ) ? wp_get_attachment_image_url($this->get_bulk_notice_image_id()) : '';
		}

		/**
		 * Get the rule mode.
		 * 
		 * @since 1.0.0
		 * @return string
		 */
		public function get_rule_mode() {
			switch ($this->get_rule_type()) {
				case '2':
					$mode = 'automatic';
					break;
				case '3':
					$mode = 'bogo';
					break;
				case '4':
					$mode = 'coupon';
					break;
				case '5':
					$mode = 'manual_bogo';
					break;
				case '6':
					$mode = 'manual_coupon';
					break;
				case '7':
					$mode = 'manual_subtotal';
					break;
				case '8':
					$mode = 'subtotal';
					break;
				case '9':
					$mode = 'manual_bulk_pricing';
					break;
				case '10':
					$mode = 'bulk_pricing';
					break;
				case '11':
					$mode = 'manual_bulk_quantity';
					break;
				case '12':
					$mode = 'bulk_quantity';
					break;
				case '13':
					$mode = 'manual_cheapest_gift';
					break;
				case '14':
					$mode = 'cheapest_gift';
					break;
				default:
					$mode = 'manual';
					break;
			}

			return $mode;
		}

		/**
		 * Get the prepared BOGO gift details with/without frontend product page link.
		 * 
		 * @since 12.0.0
		 * @param int/bool $current_product_id
		 * @return string
		 */
		public function get_prepared_bogo_gift_details( $current_product_id = false ) {
			switch ($this->get_rule_type()) {
				case '5':
					if ('2' === $this->get_bogo_get_gift_type()) {
						return self::prepare_products_name_with_link(array( $current_product_id ));
					} 
					
					switch ($this->get_product_type()) {
						case '2':
							return self::prepare_category_name_with_link($this->get_categories());
						case 'brand':
							$brand_ids = array_filter((array) get_post_meta($this->get_id(), 'fgf_get_product_brands', true));
							return self::prepare_category_name_with_link($brand_ids, 'product_brand');
						default:
							return self::prepare_products_name_with_link($this->get_products());
					}
					
					break;
				case '3':
					if ('1' === $this->get_bogo_gift_type()) {
						return self::prepare_products_name_with_link(array( $current_product_id ));
					} else {
						return self::prepare_products_name_with_link($this->get_products());
					}
				
					break;
			}

			return '';
		}

		/**
		 * Get the prepared Bulk gift details with/without frontend product page link.
		 * 
		 * @since 11.9.0
		 * @param array $bulk_rule
		 * @return string
		 */
		public function get_prepared_bulk_gift_details( $bulk_rule ) {
			switch ($this->get_rule_type()) {
				case '9':
				case '11':
					switch ($bulk_rule['gift_type']) {
						case '2':
							return self::prepare_category_name_with_link($bulk_rule['gift_categories']);
						case 'brand':
							return self::prepare_category_name_with_link($bulk_rule['gift_brands'], 'product_brand');
						default:
							return self::prepare_products_name_with_link($bulk_rule['gift_products']);
					}
					break;
				case '10':
				case '12':
					return self::prepare_products_name_with_link($bulk_rule['gift_products']);              
					break;
			}

			return '';
		}

		/**
		 * Prepare products with link.
		 * 
		 * @since 11.9.0
		 * @return string
		 * */
		private function prepare_products_name_with_link( $product_ids ) {
			$products_name_with_link = '';

			foreach ($product_ids as $product_id) {
				$product = wc_get_product($product_id);
				//Validate the product.
				if (!$product) {
					continue;
				}

				$products_name_with_link .= '<a href="' . esc_url($product->get_permalink()) . '" >' . $product->get_name() . '</a> , ';
			}

			return rtrim($products_name_with_link, ' , ');
		}

		/**
		 * Prepare category with link.
		 * 
		 * @since 11.9.0
		 * @return string
		 * */
		private function prepare_category_name_with_link( $category_ids, $taxonomy = 'product_cat' ) {
			$category_name_with_link = '';

			foreach ($category_ids as $category_id) {
				$category = get_term_by('id', $category_id, $taxonomy);
				//Validate the product.
				if (!$category) {
					continue;
				}

				$category_name_with_link .= '<a href="' . esc_url(get_term_link($category)) . '" >' . $category->name . '</a> , ';
			}

			return rtrim($category_name_with_link, ' , ');
		}

		/**
		 * Get Buy product quantity consider mode.
		 * 
		 * @since 12.0.0
		 * @return string
		 */
		public function get_buy_product_quantity_consider_mode() {
			return '1'===$this->get_buy_category_type()?'single':'group';
		}

		/**
		 * Is exclude category subtotal discount amount?
		 * 
		 * @since 9.6.0
		 * @return boolean
		 */
		public function is_exclude_category_subtotal_discount_amount() {
			return '2' == $this->get_exclude_category_subtotal_discount_amount();
		}

		/**
		 * Is exclude bulk pricing category subtotal discount amount?
		 * 
		 * @since 12.0.0
		 * @return boolean
		 */
		public function is_exclude_bulk_pricing_category_subtotal_discount_amount() {
			return '2' == $this->get_calculate_bulk_pricing_discounted_category_total();
		}

		/**
		 * Consider subcategories total?
		 * 
		 * @since 9.6.0
		 * @return boolean
		 */
		public function consider_subcategories_total() {
			return '2' == $this->get_consider_subcategories_total();
		}

		/**
		 * Calculate total discounted category total.
		 * 
		 * @since 9.6.0
		 * @return boolean
		 */
		public function calculate_total_discounted_category_total() {
			return '2' == $this->get_calculate_total_discounted_category_total();
		}

		/**
		 * Get current buy product consider type
		 * 
		 * @since 11.1.0
		 * @return string
		 */
		public function get_current_buy_product_consider_type() {
			/**
			 * This hook is used to alter the Buy product consider type.
			 * 
			 * @since 11.3.0
			 */
			return apply_filters('fgf_rule_current_buy_product_consider_type', '2' === $this->get_buy_product_type() ? $this->get_buy_category_consider_type() : $this->get_buy_product_consider_type(), $this);
		}

		/**
		 * Get current buy product quantity consider type
		 * 
		 * @since 11.1.0
		 * @return string
		 */
		public function get_current_buy_product_quantity_consider_type() {
			/**
			 * This hook is used to alter the Buy product quantity consider type.
			 * 
			 * @since 11.3.0
			 */
			return apply_filters('fgf_rule_current_buy_product_quantity_consider_type', '2' === $this->get_buy_product_type() ? $this->get_buy_category_type() : $this->get_buy_product_quantity_consider_type(), $this);
		}

		/**
		 * Get the parsed from date time.
		 * 
		 * @since 11.5.0
		 * @return string
		 */
		public function get_parsed_from_date() {
			if (!$this->get_rule_valid_from_date() || fgf_date_contain_time($this->get_rule_valid_from_date())) {
				return $this->get_rule_valid_from_date();
			}

			return $this->get_rule_valid_from_date() . ' 00:00';
		}

		/**
		 * Get the parsed to date time.
		 * 
		 * @since 11.5.0
		 * @return string
		 */
		public function get_parsed_to_date() {
			if (!$this->get_rule_valid_to_date() || fgf_date_contain_time($this->get_rule_valid_to_date())) {
				return $this->get_rule_valid_to_date();
			}

			return $this->get_rule_valid_to_date() . ' 23:59';
		}

		/**
		 * Get the formatted from date time.
		 * 
		 * @since 11.5.0
		 * @return string
		 */
		public function get_formatted_from_date() {
			return FGF_Date_Time::get_wp_format_datetime($this->get_parsed_from_date());
		}

		/**
		 * Get the formatted to date time.
		 * 
		 * @since 11.5.0
		 * @return string
		 */
		public function get_formatted_to_date() {
			return FGF_Date_Time::get_wp_format_datetime($this->get_parsed_to_date());
		}

		/**
		 * Setters and Getters
		 */

		/**
		 * Set description.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_description( $value ) {
			$this->set_post_prop('description', $value);
		}

		/**
		 * Set rule type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_rule_type( $value ) {
			$this->set_meta_prop('fgf_rule_type', $value);
		}

		/**
		 * Set rule consider type.
		 *
		 * @since 10.3.0
		 * @param string $value
		 */
		public function set_rule_consider_type( $value ) {
			$this->set_meta_prop('fgf_rule_consider_type', $value);
		}

		/**
		 * Set gift type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_gift_type( $value ) {
			$this->set_meta_prop('fgf_gift_type', $value);
		}

		/**
		 * Set gift products.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_gift_products( $value ) {
			$this->set_meta_prop('fgf_gift_products', $value);
		}

		/**
		 * Set gift categories.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_gift_categories( $value ) {
			$this->set_meta_prop('fgf_gift_categories', $value);
		}

		/**
		 * Set BOGO gift type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_bogo_gift_type( $value ) {
			$this->set_meta_prop('fgf_bogo_gift_type', $value);
		}

		/**
		 * Set buy product type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_buy_product_type( $value ) {
			$this->set_meta_prop('fgf_buy_product_type', $value);
		}

		/**
		 * Set buy product.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_buy_product( $value ) {
			$this->set_meta_prop('fgf_buy_product', $value);
		}

		/**
		 * Set buy categories.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_buy_categories( $value ) {
			$this->set_meta_prop('fgf_buy_categories', $value);
		}

		/**
		 * Set buy product consider type.
		 * 
		 * @since 11.1.0
		 * @param string $value
		 */
		public function set_buy_product_consider_type( $value ) {
			$this->set_meta_prop('fgf_buy_product_consider_type', $value);
		}

		/**
		 * Set buy category consider type.
		 * 
		 * @since 11.1.0
		 * @param string $value
		 */
		public function set_buy_category_consider_type( $value ) {
			$this->set_meta_prop('fgf_buy_category_consider_type', $value);
		}

		/**
		 * Set buy category type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_buy_category_type( $value ) {
			$this->set_meta_prop('fgf_buy_category_type', $value);
		}

		/**
		 * Set buy product quantity consider type.
		 * 
		 * @since 11.1.0
		 * @param string $value
		 */
		public function set_buy_product_quantity_consider_type( $value ) {
			$this->set_meta_prop('fgf_buy_product_quantity_consider_type', $value);
		}

		/**
		 * Set get product type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_get_product_type( $value ) {
			$this->set_meta_prop('fgf_get_product_type', $value);
		}

		/**
		 * Set get products.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_get_products( $value ) {
			$this->set_meta_prop('fgf_get_products', $value);
		}

		/**
		 * Set get categories.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_get_categories( $value ) {
			$this->set_meta_prop('fgf_get_categories', $value);
		}

		/**
		 * Set buy quantity type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_buy_quantity_type( $value ) {
			$this->set_meta_prop('fgf_buy_quantity_type', $value);
		}

		/**
		 * Set BOGO get gift type.
		 * 
		 * @since 11.3.0
		 * @param string $value
		 */
		public function set_bogo_get_gift_type( $value ) {
			$this->set_meta_prop('fgf_bogo_get_gift_type', $value);
		}

		/**
		 * Set buy product count.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_buy_product_count( $value ) {
			$this->set_meta_prop('fgf_buy_product_count', $value);
		}

		/**
		 * Set get product count.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_get_product_count( $value ) {
			$this->set_meta_prop('fgf_get_product_count', $value);
		}

		/**
		 * Set BOGO gift repeat.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_bogo_gift_repeat( $value ) {
			$this->set_meta_prop('fgf_bogo_gift_repeat', $value);
		}

		/**
		 * Set BOGO gift repeat mode.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_bogo_gift_repeat_mode( $value ) {
			$this->set_meta_prop('fgf_bogo_gift_repeat_mode', $value);
		}

		/**
		 * Set BOGO gift repeat limit.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_bogo_gift_repeat_limit( $value ) {
			$this->set_meta_prop('fgf_bogo_gift_repeat_limit', $value);
		}

		/**
		 * Set apply coupon.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_apply_coupon( $value ) {
			$this->set_meta_prop('fgf_apply_coupon', $value);
		}

		/**
		 * Set coupon gift products.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_coupon_gift_products( $value ) {
			$this->set_meta_prop('fgf_coupon_gift_products', $value);
		}

		/**
		 * Set coupon gift products quantity.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_coupon_gift_products_qty( $value ) {
			$this->set_meta_prop('fgf_coupon_gift_products_qty', $value);
		}

		/**
		 * Set subtotal price type.
		 * 
		 * @since 11.5.0
		 * @param int $value
		 */
		public function set_subtotal_price_type( $value ) {
			$this->set_meta_prop('fgf_subtotal_price_type', $value);
		}

		/**
		 * Set total categories.
		 * 
		 * @since 11.5.0
		 * @param array $value
		 */
		public function set_total_categories( $value ) {
			$this->set_meta_prop('fgf_total_categories', $value);
		}

		/**
		 * Set consider subcategories total.
		 * 
		 * @since 11.5.0
		 * @param int $value
		 */
		public function set_consider_subcategories_total( $value ) {
			$this->set_meta_prop('fgf_consider_subcategories_total', $value);
		}

		/**
		 * Set calculate total discounted category total.
		 * 
		 * @since 11.5.0
		 * @param int $value
		 */
		public function set_calculate_total_discounted_category_total( $value ) {
			$this->set_meta_prop('fgf_calculate_total_discounted_category_total', $value);
		}

		/**
		 * Set subtotal price.
		 * 
		 * @since 11.1.0
		 * @param float $value
		 */
		public function set_subtotal_price( $value ) {
			$this->set_meta_prop('fgf_subtotal_price', $value);
		}

		/**
		 * Set subtotal gift type.
		 * 
		 * @since 11.1.0
		 * @param string $value
		 */
		public function set_subtotal_gift_type( $value ) {
			$this->set_meta_prop('fgf_subtotal_gift_type', $value);
		}

		/**
		 * Set subtotal gift products.
		 * 
		 * @since 11.1.0
		 * @param array $value
		 */
		public function set_subtotal_gift_products( $value ) {
			$this->set_meta_prop('fgf_subtotal_gift_products', $value);
		}

		/**
		 * Set subtotal gift categories.
		 * 
		 * @since 11.1.0
		 * @param array $value
		 */
		public function set_subtotal_gift_categories( $value ) {
			$this->set_meta_prop('fgf_subtotal_gift_categories', $value);
		}

		/**
		 * Set subtotal gift products quantity.
		 * 
		 * @since 11.1.0
		 * @param int $value
		 */
		public function set_subtotal_gift_products_qty( $value ) {
			$this->set_meta_prop('fgf_subtotal_gift_products_qty', $value);
		}

		/**
		 * Set subtotal repeat gift.
		 * 
		 * @since 11.1.0
		 * @param string $value
		 */
		public function set_subtotal_repeat_gift( $value ) {
			$this->set_meta_prop('fgf_subtotal_repeat_gift', $value);
		}

		/**
		 * Set subtotal repeat gift mode.
		 * 
		 * @since 11.1.0
		 * @param string $value
		 */
		public function set_subtotal_repeat_gift_mode( $value ) {
			$this->set_meta_prop('fgf_subtotal_repeat_gift_mode', $value);
		}

		/**
		 * Set subtotal repeat gift limit.
		 * 
		 * @since 11.1.0
		 * @param int $value
		 */
		public function set_subtotal_repeat_gift_limit( $value ) {
			$this->set_meta_prop('fgf_subtotal_repeat_gift_limit', $value);
		}

		/**
		 * Set bulk quantity rules consider type.
		 * 
		 * @since 12.0.0
		 * @param string $value
		 */
		public function set_bulk_quantity_rules_consider_type( $value ) {
			$this->set_meta_prop('fgf_bulk_quantity_rules_consider_type', $value);
		}

		/**
		 * Set bulk quantity rules.
		 * 
		 * @since 12.0.0
		 * @param array $value
		 */
		public function set_bulk_quantity_rules( $value ) {
			$this->set_meta_prop('fgf_bulk_quantity_rules', $value);
		}

		/**
		 * Set bulk pricing total type.
		 * 
		 * @since 12.0.0
		 * @param string $value
		 */
		public function set_bulk_pricing_total_type( $value ) {
			$this->set_meta_prop('fgf_bulk_pricing_total_type', $value);
		}

		/**
		 * Set bulk pricing categories.
		 * 
		 * @since 12.0.0
		 * @param array $value
		 */
		public function set_bulk_pricing_categories( $value ) {
			$this->set_meta_prop('fgf_bulk_pricing_categories', $value);
		}

		/**
		 * Set consider bulk pricing subcategories total.
		 * 
		 * @since 12.0.0
		 * @param string $value
		 */
		public function set_consider_bulk_pricing_subcategories_total( $value ) {
			$this->set_meta_prop('fgf_consider_bulk_pricing_subcategories_total', $value);
		}

		/**
		 * Set calculate bulk pricing discounted category total.
		 * 
		 * @since 12.0.0
		 * @param string $value
		 */
		public function set_calculate_bulk_pricing_discounted_category_total( $value ) {
			$this->set_meta_prop('fgf_calculate_bulk_pricing_discounted_category_total', $value);
		}

		/**
		 * Set bulk pricing rules consider type.
		 * 
		 * @since 12.0.0
		 * @param string $value
		 */
		public function set_bulk_pricing_rules_consider_type( $value ) {
			$this->set_meta_prop('fgf_bulk_pricing_rules_consider_type', $value);
		}

		/**
		 * Set bulk pricing rules.
		 * 
		 * @since 12.0.0
		 * @param array $value
		 */
		public function set_bulk_pricing_rules( $value ) {
			$this->set_meta_prop('fgf_bulk_pricing_rules', $value);
		}

		/**
		 * Set cheapest gift price type.
		 * 
		 * @since 12.0.0
		 * @param int $value
		 */
		public function set_cheapest_gift_price_type( $value ) {
			$this->set_meta_prop('fgf_cheapest_gift_price_type', $value);
		}

		/**
		 * Set cheapest gift quantity.
		 * 
		 * @since 12.0.0
		 * @param int $value
		 */
		public function set_cheapest_gift_quantity( $value ) {
			$this->set_meta_prop('fgf_cheapest_gift_quantity', $value);
		}

		/**
		 * Set rule valid from date.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_rule_valid_from_date( $value ) {
			$this->set_meta_prop('fgf_rule_valid_from_date', $value);
		}

		/**
		 * Set rule valid to date.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_rule_valid_to_date( $value ) {
			$this->set_meta_prop('fgf_rule_valid_to_date', $value);
		}

		/**
		 * Set rule week days validation.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_rule_week_days_validation( $value ) {
			$this->set_meta_prop('fgf_rule_week_days_validation', $value);
		}

		/**
		 * Set automatic product qty.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_automatic_product_qty( $value ) {
			$this->set_meta_prop('fgf_automatic_product_qty', $value);
		}

		/**
		 * Set rule gifts count per order.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_rule_gifts_count_per_order( $value ) {
			$this->set_meta_prop('fgf_rule_gifts_count_per_order', $value);
		}

		/**
		 * Set rule restriction count.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_rule_restriction_count( $value ) {
			$this->set_meta_prop('fgf_rule_restriction_count', $value);
		}

		/**
		 * Set rule usage count.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_rule_usage_count( $value ) {
			$this->set_meta_prop('fgf_rule_usage_count', $value);
		}

		/**
		 * Set rule allowed user type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_rule_allowed_user_type( $value ) {
			$this->set_meta_prop('fgf_rule_allowed_user_type', $value);
		}

		/**
		 * Set rule allowed user count.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_rule_allowed_user_count( $value ) {
			$this->set_meta_prop('fgf_rule_allowed_user_count', $value);
		}

		/**
		 * Set rule allowed user usage count.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_rule_allowed_user_usage_count( $value ) {
			$this->set_meta_prop('fgf_rule_allowed_user_usage_count', $value);
		}

		/**
		 * Set rule user purchased order count type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_rule_user_purchased_order_count_type( $value ) {
			$this->set_meta_prop('fgf_rule_user_purchased_order_count_type', $value);
		}

		/**
		 * Set rule user purchased order minimum count.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_rule_user_purchased_order_min_count( $value ) {
			$this->set_meta_prop('fgf_rule_user_purchased_order_min_count', $value);
		}

		/**
		 * Set rule user purchased order maximum count.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_rule_user_purchased_order_max_count( $value ) {
			$this->set_meta_prop('fgf_rule_user_purchased_order_max_count', $value);
		}

		/**
		 * Set exclude subtotal discount amount.
		 * 
		 * @since 9.6.0
		 * @param int $value
		 */
		public function set_exclude_category_subtotal_discount_amount( $value ) {
			$this->set_meta_prop('fgf_exclude_category_subtotal_discount_amount', $value);
		}

		/**
		 * Set rule restrict by WooCommerce coupon type.
		 * 
		 * @since 10.7.0
		 * @param string $value
		 */
		public function set_rule_restrict_by_wocommerce_coupon_type( $value ) {
			$this->set_meta_prop('fgf_rule_restrict_by_wocommerce_coupon_type', $value);
		}

		/**
		 * Set rule restrict by WooCommerce coupon.
		 * 
		 * @since 10.7.0
		 * @param string $value
		 */
		public function set_rule_restrict_by_wocommerce_coupon( $value ) {
			$this->set_meta_prop('fgf_rule_restrict_by_wocommerce_coupon', $value);
		}

		/**
		 * Set condition type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_condition_type( $value ) {
			$this->set_meta_prop('fgf_condition_type', $value);
		}

		/**
		 * Set total type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_total_type( $value ) {
			$this->set_meta_prop('fgf_total_type', $value);
		}

		/**
		 * Set cart categories.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_cart_categories( $value ) {
			$this->set_meta_prop('fgf_cart_categories', $value);
		}

		/**
		 * Set consider cart subcategories total.
		 * 
		 * @since 1.0.0
		 * @param float $value
		 */
		public function set_consider_cart_subcategories_total( $value ) {
			$this->set_meta_prop('fgf_consider_cart_subcategories_total', $value);
		}

		/**
		 * Set cart subtotal minimum value.
		 * 
		 * @since 1.0.0
		 * @param float $value
		 */
		public function set_cart_subtotal_minimum_value( $value ) {
			$this->set_meta_prop('fgf_cart_subtotal_min_value', $value);
		}

		/**
		 * Set cart subtotal maximum value.
		 * 
		 * @since 1.0.0
		 * @param float $value
		 */
		public function set_cart_subtotal_maximum_value( $value ) {
			$this->set_meta_prop('fgf_cart_subtotal_max_value', $value);
		}

		/**
		 * Set quantity minimum value.
		 * 
		 * @since 1.0.0
		 * @param float $value
		 */
		public function set_quantity_minimum_value( $value ) {
			$this->set_meta_prop('fgf_quantity_min_value', $value);
		}

		/**
		 * Set quantity maximum value.
		 * 
		 * @since 1.0.0
		 * @param float $value
		 */
		public function set_quantity_maximum_value( $value ) {
			$this->set_meta_prop('fgf_quantity_min_value', $value);
		}

		/**
		 * Set product count minimum value.
		 * 
		 * @since 1.0.0
		 * @param float $value
		 */
		public function set_product_count_min_value( $value ) {
			$this->set_meta_prop('fgf_product_count_min_value', $value);
		}

		/**
		 * Set product count maximum value.
		 * 
		 * @since 1.0.0
		 * @param float $value
		 */
		public function set_product_count_max_value( $value ) {
			$this->set_meta_prop('fgf_product_count_max_value', $value);
		}

		/**
		 * Set show notice.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_show_notice( $value ) {
			$this->set_meta_prop('fgf_show_notice', $value);
		}

		/**
		 * Set notice.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_notice( $value ) {
			$this->set_meta_prop('fgf_notice', $value);
		}

		/**
		 * Set notice image ID.
		 * 
		 * @since 10.4.0
		 * @param string/int $value
		 */
		public function set_notice_image_id( $value ) {
			$this->set_meta_prop('fgf_notice_image_id', $value);
		}
		
		/**
		 * Set show bogo eligible notice.
		 * 
		 * @since 11.9.0
		 * @param string $value
		 */
		public function set_show_bogo_eligible_notice( $value ) {
			$this->set_meta_prop('fgf_show_bogo_eligible_notice', $value);
		}

		/**
		 * Set bogo eligible notice.
		 * 
		 * @since 11.9.0
		 * @param string $value
		 */
		public function set_bogo_eligible_notice( $value ) {
			$this->set_meta_prop('fgf_bogo_eligible_notice', $value);
		}

		/**
		 * Set show bulk eligible notice.
		 * 
		 * @since 12.0.0
		 * @param string $value
		 */
		public function set_show_bulk_eligible_notice( $value ) {
			$this->set_meta_prop('fgf_show_bulk_eligible_notice', $value);
		}

		/**
		 * Set bulk eligible notice.
		 * 
		 * @since 12.0.0
		 * @param string $value
		 */
		public function set_bulk_eligible_notice( $value ) {
			$this->set_meta_prop('fgf_bulk_eligible_notice', $value);
		}

		/**
		 * Set bulk notice image ID.
		 * 
		 * @since 12.0.0
		 * @param string/int $value
		 */
		public function set_bulk_notice_image_id( $value ) {
			$this->set_meta_prop('fgf_bulk_notice_image_id', $value);
		}

		/**
		 * Set user filter type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_user_filter_type( $value ) {
			$this->set_meta_prop('fgf_user_filter_type', $value);
		}

		/**
		 * Set include users.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_include_users( $value ) {
			$this->set_meta_prop('fgf_include_users', $value);
		}

		/**
		 * Set exclude users.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_exclude_users( $value ) {
			$this->set_meta_prop('fgf_exclude_users', $value);
		}

		/**
		 * Set include user roles
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_include_user_roles( $value ) {
			$this->set_meta_prop('fgf_include_user_roles', $value);
		}

		/**
		 * Set exclude user roles.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_exclude_user_roles( $value ) {
			$this->set_meta_prop('fgf_exclude_user_roles', $value);
		}

		/**
		 * Set product filter type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_product_filter_type( $value ) {
			$this->set_meta_prop('fgf_product_filter_type', $value);
		}

		/**
		 * Set include products.
		 */
		public function set_include_products( $value ) {
			$this->set_meta_prop('fgf_include_products', $value);
		}

		/**
		 * Set exclude products.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_exclude_products( $value ) {
			$this->set_meta_prop('fgf_exclude_products', $value);
		}

		/**
		 * Set applicable products type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_applicable_products_type( $value ) {
			$this->set_meta_prop('fgf_applicable_products_type', $value);
		}

		/**
		 * Set include product count.
		 */
		public function set_include_product_count( $value ) {
			$this->set_meta_prop('fgf_include_product_count', $value);
		}

		/**
		 * Set applicable categories type.
		 * 
		 * @since 1.0.0
		 * @param string $value
		 */
		public function set_applicable_categories_type( $value ) {
			$this->set_meta_prop('fgf_applicable_categories_type', $value);
		}

		/**
		 * Set include categories.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_include_categories( $value ) {
			$this->set_meta_prop('fgf_include_categories', $value);
		}

		/**
		 * Set include categories product count.
		 * 
		 * @since 1.0.0
		 * @param int $value
		 */
		public function set_include_category_product_count( $value ) {
			$this->set_meta_prop('fgf_include_category_product_count', $value);
		}

		/**
		 * Set exclude categories.
		 * 
		 * @since 1.0.0
		 * @param array $value
		 */
		public function set_exclude_categories( $value ) {
			$this->set_meta_prop('fgf_exclude_categories', $value);
		}

		/**
		 * Set virtual product restriction.
		 * 
		 * @since 11.3.0
		 * @param string $value
		 */
		public function set_virtual_product_restriction( $value ) {
			$this->set_meta_prop('fgf_virtual_product_restriction', $value);
		}

		/**
		 * Get the description.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_description() {
			return $this->get_post_prop('description');
		}

		/**
		 * Get the rule type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_rule_type() {
			return $this->get_meta_prop('fgf_rule_type');
		}

		/**
		 * Get the rule consider type.
		 *
		 * @since 10.3.0
		 * @retrun string
		 */
		public function get_rule_consider_type() {
			return $this->get_meta_prop('fgf_rule_consider_type');
		}

		/**
		 * Get the gift type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_gift_type() {
			return $this->get_meta_prop('fgf_gift_type');
		}

		/**
		 * Get the gift products.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_gift_products() {
			return $this->get_meta_prop('fgf_gift_products');
		}

		/**
		 * Get the gift categories.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_gift_categories() {
			return $this->get_meta_prop('fgf_gift_categories');
		}

		/**
		 * Get the BOGO gift type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_bogo_gift_type() {
			return $this->get_meta_prop('fgf_bogo_gift_type');
		}

		/**
		 * Get the buy product type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_buy_product_type() {
			return $this->get_meta_prop('fgf_buy_product_type');
		}

		/**
		 * Get the buy product.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_buy_product() {
			return $this->get_meta_prop('fgf_buy_product');
		}

		/**
		 * Get the buy categories.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_buy_categories() {
			return $this->get_meta_prop('fgf_buy_categories');
		}

		/**
		 * Get the buy product consider type.
		 * 
		 * @since 11.1.0
		 * @retrun string
		 */
		public function get_buy_product_consider_type() {
			return $this->get_meta_prop('fgf_buy_product_consider_type');
		}

		/**
		 * Get the buy category consider type.
		 * 
		 * @since 11.1.0
		 * @retrun string
		 */
		public function get_buy_category_consider_type() {
			return $this->get_meta_prop('fgf_buy_category_consider_type');
		}

		/**
		 * Get the buy category type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_buy_category_type() {
			return $this->get_meta_prop('fgf_buy_category_type');
		}

		/**
		 * Get the buy product quantity consider type.
		 * 
		 * @since 11.1.0
		 * @retrun string
		 */
		public function get_buy_product_quantity_consider_type() {
			return $this->get_meta_prop('fgf_buy_product_quantity_consider_type');
		}

		/**
		 * Get the product type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_product_type() {
			return $this->get_meta_prop('fgf_get_product_type');
		}

		/**
		 * Get the products.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_products() {
			return $this->get_meta_prop('fgf_get_products');
		}

		/**
		 * Get the categories.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_categories() {
			return $this->get_meta_prop('fgf_get_categories');
		}

		/**
		 * Get the buy quantity type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_buy_quantity_type() {
			return $this->get_meta_prop('fgf_buy_quantity_type');
		}

		/**
		 * Get the BOGO get gift type.
		 * 
		 * @since 11.3.0
		 * @retrun string
		 */
		public function get_bogo_get_gift_type() {
			return $this->get_meta_prop('fgf_bogo_get_gift_type');
		}

		/**
		 * Get the buy product count.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_buy_product_count() {
			return $this->get_meta_prop('fgf_buy_product_count');
		}

		/**
		 * Get the product count.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_product_count() {
			return $this->get_meta_prop('fgf_get_product_count');
		}

		/**
		 * Get the BOGO gift repeat.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_bogo_gift_repeat() {
			return $this->get_meta_prop('fgf_bogo_gift_repeat');
		}

		/**
		 * Get the BOGO gift repeat mode.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_bogo_gift_repeat_mode() {
			return $this->get_meta_prop('fgf_bogo_gift_repeat_mode');
		}

		/**
		 * Get the BOGO gift repeat limit.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_bogo_gift_repeat_limit() {
			return $this->get_meta_prop('fgf_bogo_gift_repeat_limit');
		}

		/**
		 * Get the apply coupon.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_apply_coupon() {
			return $this->get_meta_prop('fgf_apply_coupon');
		}

		/**
		 * Get the coupon gift products.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_coupon_gift_products() {
			return $this->get_meta_prop('fgf_coupon_gift_products');
		}

		/**
		 * Get the coupon gift products quantity.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_coupon_gift_products_qty() {
			return $this->get_meta_prop('fgf_coupon_gift_products_qty');
		}

		/**
		 * Get the subtotal price type.
		 * 
		 * @since 11.5.0
		 * @retrun int
		 */
		public function get_subtotal_price_type() {
			return $this->get_meta_prop('fgf_subtotal_price_type');
		}

		/**
		 * Get the total categories.
		 * 
		 * @since 11.5.0
		 * @retrun array
		 */
		public function get_total_categories() {
			return $this->get_meta_prop('fgf_total_categories');
		}

		/**
		 * Get the consider subcategories total.
		 * 
		 * @since 11.5.0
		 * @retrun int
		 */
		public function get_consider_subcategories_total() {
			return $this->get_meta_prop('fgf_consider_subcategories_total');
		}

		/**
		 * Get the calculate total discounted category total.
		 * 
		 * @since 11.5.0
		 * @retrun int
		 */
		public function get_calculate_total_discounted_category_total() {
			return $this->get_meta_prop('fgf_calculate_total_discounted_category_total');
		}

		/**
		 * Get the subtotal price.
		 * 
		 * @since 11.1.0
		 * @retrun float
		 */
		public function get_subtotal_price() {
			return $this->get_meta_prop('fgf_subtotal_price');
		}

		/**
		 * Get the subtotal gift type.
		 * 
		 * @since 11.1.0
		 * @retrun string
		 */
		public function get_subtotal_gift_type() {
			return $this->get_meta_prop('fgf_subtotal_gift_type');
		}

		/**
		 * Get the subtotal gift products.
		 * 
		 * @since 11.1.0
		 * @retrun array
		 */
		public function get_subtotal_gift_products() {
			return $this->get_meta_prop('fgf_subtotal_gift_products');
		}

		/**
		 * Get the subtotal gift categories.
		 * 
		 * @since 11.1.0
		 * @retrun array
		 */
		public function get_subtotal_gift_categories() {
			return $this->get_meta_prop('fgf_subtotal_gift_categories');
		}

		/**
		 * Get the subtotal gift products qty.
		 * 
		 * @since 11.1.0
		 * @retrun int
		 */
		public function get_subtotal_gift_products_qty() {
			return $this->get_meta_prop('fgf_subtotal_gift_products_qty');
		}

		/**
		 * Get the subtotal repeat gift.
		 * 
		 * @since 11.1.0
		 * @retrun string
		 */
		public function get_subtotal_repeat_gift() {
			return $this->get_meta_prop('fgf_subtotal_repeat_gift');
		}

		/**
		 * Get the subtotal repeat gift mode.
		 * 
		 * @since 11.1.0
		 * @retrun string
		 */
		public function get_subtotal_repeat_gift_mode() {
			return $this->get_meta_prop('fgf_subtotal_repeat_gift_mode');
		}

		/**
		 * Get the subtotal repeat gift limit.
		 * 
		 * @since 11.1.0
		 * @retrun int
		 */
		public function get_subtotal_repeat_gift_limit() {
			return $this->get_meta_prop('fgf_subtotal_repeat_gift_limit');
		}

		/**
		 * Get the bulk quantity rules consider type.
		 * 
		 * @since 12.0.0
		 * @retrun string
		 */
		public function get_bulk_quantity_rules_consider_type() {
			return $this->get_meta_prop('fgf_bulk_quantity_rules_consider_type');
		}

		/**
		 * Get the bulk quantity rules.
		 * 
		 * @since 12.0.0
		 * @retrun array
		 */
		public function get_bulk_quantity_rules() {
			return $this->get_meta_prop('fgf_bulk_quantity_rules');
		}

		/**
		 * Get the bulk pricing total type.
		 * 
		 * @since 12.0.0
		 * @retrun string
		 */
		public function get_bulk_pricing_total_type() {
			return $this->get_meta_prop('fgf_bulk_pricing_total_type');
		}

		/**
		 * Get the bulk pricing categories.
		 * 
		 * @since 12.0.0
		 * @retrun array
		 */
		public function get_bulk_pricing_categories() {
			return $this->get_meta_prop('fgf_bulk_pricing_categories');
		}

		/**
		 * Get the consider bulk pricing subcategories total.
		 * 
		 * @since 12.0.0
		 * @retrun string
		 */
		public function get_consider_bulk_pricing_subcategories_total() {
			return $this->get_meta_prop('fgf_consider_bulk_pricing_subcategories_total');
		}

		/**
		 * Get the calculate bulk pricing discounted category total.
		 * 
		 * @since 12.0.0
		 * @return int
		 */
		public function get_calculate_bulk_pricing_discounted_category_total() {
			return $this->get_meta_prop('fgf_calculate_bulk_pricing_discounted_category_total');
		}

		/**
		 * Get the bulk pricing rules consider type.
		 * 
		 * @since 12.0.0
		 * @retrun string
		 */
		public function get_bulk_pricing_rules_consider_type() {
			return $this->get_meta_prop('fgf_bulk_pricing_rules_consider_type');
		}

		/**
		 * Get the bulk pricing rules.
		 * 
		 * @since 12.0.0
		 * @retrun array
		 */
		public function get_bulk_pricing_rules() {
			return $this->get_meta_prop('fgf_bulk_pricing_rules');
		}

		/**
		 * Get the cheapest gift price type.
		 * 
		 * @since 12.0.0
		 * @retrun int
		 */
		public function get_cheapest_gift_price_type() {
			return $this->get_meta_prop('fgf_cheapest_gift_price_type');
		}

		/**
		 * Get the cheapest gift quantity.
		 * 
		 * @since 12.0.0
		 * @retrun int
		 */
		public function get_cheapest_gift_quantity() {
			return $this->get_meta_prop('fgf_cheapest_gift_quantity');
		}

		/**
		 * Get the rule valid from date.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_rule_valid_from_date() {
			return $this->get_meta_prop('fgf_rule_valid_from_date');
		}

		/**
		 * Get the rule valid to date.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_rule_valid_to_date() {
			return $this->get_meta_prop('fgf_rule_valid_to_date');
		}

		/**
		 * Get the rule week days validation.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_rule_week_days_validation() {
			return $this->get_meta_prop('fgf_rule_week_days_validation');
		}

		/**
		 * Get the automatic product qty.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_automatic_product_qty() {
			return $this->get_meta_prop('fgf_automatic_product_qty');
		}

		/**
		 * Get the rule gifts count per order.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_rule_gifts_count_per_order() {
			return $this->get_meta_prop('fgf_rule_gifts_count_per_order');
		}

		/**
		 * Get the rule restriction count.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_rule_restriction_count() {
			return $this->get_meta_prop('fgf_rule_restriction_count');
		}

		/**
		 * Get the rule usage count.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_rule_usage_count() {
			return $this->get_meta_prop('fgf_rule_usage_count');
		}

		/**
		 * Get the rule allowed user type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_rule_allowed_user_type() {
			return $this->get_meta_prop('fgf_rule_allowed_user_type');
		}

		/**
		 * Get the rule allowed user count.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_rule_allowed_user_count() {
			return $this->get_meta_prop('fgf_rule_allowed_user_count');
		}

		/**
		 * Get the rule allowed user usage count.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_rule_allowed_user_usage_count() {
			return $this->get_meta_prop('fgf_rule_allowed_user_usage_count');
		}

		/**
		 * Get the rule user purchased order count type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_rule_user_purchased_order_count_type() {
			return $this->get_meta_prop('fgf_rule_user_purchased_order_count_type');
		}

		/**
		 * Get the rule user purchased order minimum count.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_rule_user_purchased_order_min_count() {
			return $this->get_meta_prop('fgf_rule_user_purchased_order_min_count');
		}

		/**
		 * Get the rule user purchased order maximum count.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_rule_user_purchased_order_max_count() {
			return $this->get_meta_prop('fgf_rule_user_purchased_order_max_count');
		}

		/**
		 * Get the exclude subtotal discount amount.
		 * 
		 * @since 9.6.0
		 * @return int
		 */
		public function get_exclude_category_subtotal_discount_amount() {
			return $this->get_meta_prop('fgf_exclude_category_subtotal_discount_amount');
		}

		/**
		 * Get the rule restrict by WooCommerce coupon type.
		 * 
		 * @since 10.7.0
		 * @retrun string
		 */
		public function get_rule_restrict_by_wocommerce_coupon_type() {
			return $this->get_meta_prop('fgf_rule_restrict_by_wocommerce_coupon_type');
		}

		/**
		 * Get the rule restrict by WooCommerce coupon.
		 * 
		 * @since 10.7.0
		 * @retrun string
		 */
		public function get_rule_restrict_by_wocommerce_coupon() {
			return $this->get_meta_prop('fgf_rule_restrict_by_wocommerce_coupon');
		}

		/**
		 * Get the condition type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_condition_type() {
			return $this->get_meta_prop('fgf_condition_type');
		}

		/**
		 * Get the total type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 * */
		public function get_total_type() {
			return $this->get_meta_prop('fgf_total_type');
		}

		/**
		 * Get the cart categories.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_cart_categories() {
			return $this->get_meta_prop('fgf_cart_categories');
		}

		/**
		 * Get the consider cart subcategories total.
		 * 
		 * @since 1.0.0
		 * @retrun boolean
		 */
		public function get_consider_cart_subcategories_total() {
			return '2' == $this->get_meta_prop('fgf_consider_cart_subcategories_total');
		}

		/**
		 * Get the cart subtotal minimum value.
		 * 
		 * @since 1.0.0
		 * @retrun float
		 */
		public function get_cart_subtotal_minimum_value() {
			return $this->get_meta_prop('fgf_cart_subtotal_min_value');
		}

		/**
		 * Get the cart subtotal maximum value.
		 * 
		 * @since 1.0.0
		 * @retrun float
		 */
		public function get_cart_subtotal_maximum_value() {
			return $this->get_meta_prop('fgf_cart_subtotal_max_value');
		}

		/**
		 * Get the quantity minimum value.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_quantity_minimum_value() {
			return $this->get_meta_prop('fgf_quantity_min_value');
		}

		/**
		 * Get the quantity maximum value.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_quantity_maximum_value() {
			return $this->get_meta_prop('fgf_quantity_max_value');
		}

		/**
		 * Get the product count minimum value.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_product_count_min_value() {
			return $this->get_meta_prop('fgf_product_count_min_value');
		}

		/**
		 * Get the product count maximum value.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_product_count_max_value() {
			return $this->get_meta_prop('fgf_product_count_max_value');
		}

		/**
		 * Get the show notice.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_show_notice() {
			return $this->get_meta_prop('fgf_show_notice');
		}

		/**
		 * Get the notice.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_notice() {
			return $this->get_meta_prop('fgf_notice');
		}

		/**
		 * Get the notice image ID.
		 * 
		 * @since 10.4.0
		 * @retrun string/int
		 */
		public function get_notice_image_id() {
			return $this->get_meta_prop('fgf_notice_image_id');
		}

		/**
		 * Get the show bogo eligible notice.
		 * 
		 * @since 11.9.0
		 * @retrun string
		 */
		public function get_show_bogo_eligible_notice() {
			return $this->get_meta_prop('fgf_show_bogo_eligible_notice');
		}

			/**
		 * Get the bogo eligible notice.
		 * 
		 * @since 11.9.0
		 * @retrun string
		 */
		public function get_bogo_eligible_notice() {
			return $this->get_meta_prop('fgf_bogo_eligible_notice');
		}

		/**
		 * Get the show bulk eligible notice.
		 * 
		 * @since 12.0.0
		 * @retrun string
		 */
		public function get_show_bulk_eligible_notice() {
			return $this->get_meta_prop('fgf_show_bulk_eligible_notice');
		}

		/**
		 * Get the bulk eligible notice.
		 * 
		 * @since 12.0.0
		 * @retrun string
		 */
		public function get_bulk_eligible_notice() {
			return $this->get_meta_prop('fgf_bulk_eligible_notice');
		}

		/**
		 * Get the bulk notice image ID.
		 * 
		 * @since 12.0.0
		 * @retrun string/int
		 */
		public function get_bulk_notice_image_id() {
			return $this->get_meta_prop('fgf_bulk_notice_image_id');
		}

		/**
		 * Get the user filter type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_user_filter_type() {
			return $this->get_meta_prop('fgf_user_filter_type');
		}

		/**
		 * Get the include users.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_include_users() {
			return $this->get_meta_prop('fgf_include_users');
		}

		/**
		 * Get the exclude users.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_exclude_users() {
			return $this->get_meta_prop('fgf_exclude_users');
		}

		/**
		 * Get the include user roles.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_include_user_roles() {
			return $this->get_meta_prop('fgf_include_user_roles');
		}

		/**
		 * Get the exclude user roles.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_exclude_user_roles() {
			return $this->get_meta_prop('fgf_exclude_user_roles');
		}

		/**
		 * Get the product filter type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_product_filter_type() {
			return $this->get_meta_prop('fgf_product_filter_type');
		}

		/**
		 * Get the include products.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_include_products() {
			return $this->get_meta_prop('fgf_include_products');
		}

		/**
		 * Get the products count.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_include_product_count() {
			return $this->get_meta_prop('fgf_include_product_count');
		}

		/**
		 * Get the exclude products.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_exclude_products() {
			return $this->get_meta_prop('fgf_exclude_products');
		}

		/**
		 * Get the applicable products type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_applicable_products_type() {
			return $this->get_meta_prop('fgf_applicable_products_type');
		}

		/**
		 * Get the applicable categories type.
		 * 
		 * @since 1.0.0
		 * @retrun string
		 */
		public function get_applicable_categories_type() {
			return $this->get_meta_prop('fgf_applicable_categories_type');
		}

		/**
		 * Get the include categories.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_include_categories() {
			return $this->get_meta_prop('fgf_include_categories');
		}

		/**
		 * Get the include categories product count.
		 * 
		 * @since 1.0.0
		 * @retrun int
		 */
		public function get_include_category_product_count() {
			return $this->get_meta_prop('fgf_include_category_product_count');
		}

		/**
		 * Get the exclude categories.
		 * 
		 * @since 1.0.0
		 * @retrun array
		 */
		public function get_exclude_categories() {
			return $this->get_meta_prop('fgf_exclude_categories');
		}

		/**
		 * Get the virtual product restriction.
		 * 
		 * @since 11.3.0
		 * @retrun string
		 */
		public function get_virtual_product_restriction() {
			return $this->get_meta_prop('fgf_virtual_product_restriction');
		}
	}

}
