<?php

/**
 * Compatibility - WooCommerce Brands.
 *
 * @since 8.6
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_WC_Brands_Compatibility')) {

	/**
	 * Class.
	 */
	class FGF_WC_Brands_Compatibility extends FGF_Compatibility {

		/**
		 * Taxonomy.
		 *
		 * @var string
		 */
		const TAXONOMY = 'product_brand';

		/**
		 * Class Constructor.
		 */
		public function __construct() {
			$this->id = 'wc_brands';

			parent::__construct();
		}

		/**
		 * Is plugin enabled?.
		 *
		 *  @return bool
		 * */
		public function is_plugin_enabled() {
			return class_exists('WC_Brands') || class_exists('Automattic\WooCommerce\Internal\Brands');
		}

		/**
		 * Admin Action.
		 */
		public function admin_action() {
			// Add the brands fields in the rule.
			add_action('fgf_after_rule_general_settings', array( $this, 'add_gift_brands_fields' ));
			add_action('fgf_after_rule_buy_product_settings', array( $this, 'add_buy_product_brands_fields' ));
			add_action('fgf_after_rule_get_product_settings', array( $this, 'add_get_product_brands_fields' ));
			add_action('fgf_after_rule_subtotal_gifts_total_type_settings', array( $this, 'add_subtotal_gifts_total_brands_fields' ));
			add_action('fgf_after_rule_subtotal_gift_products_settings', array( $this, 'add_subtotal_gift_brands_fields' ));
			add_action('fgf_bulk_pricing_rule_settings', array( $this, 'add_bulk_pricing_brands_fields' ));
			add_action('fgf_bulk_rule_settings', array( $this, 'add_bulk_rule_gift_brands_fields' ), 10, 3);
			add_action('fgf_after_rule_criteria_total_type_settings', array( $this, 'add_brands_fields' ));
			add_action('fgf_after_rule_product_filters_settings', array( $this, 'add_product_filter_brands_fields' ));
			// Add the brand in the rule gift selection type options.
			add_filter('fgf_gift_product_selection_types', array( $this, 'add_custom_gift_selection_type_options' ), 10, 1);
			add_filter('fgf_subtotal_gift_product_selection_types', array( $this, 'add_custom_gift_selection_type_options' ), 10, 1);
			// Add the brand in the rule buy product selection type options.
			add_filter('fgf_buy_product_selection_types', array( $this, 'add_custom_buy_product_selection_type_options' ), 10, 1);
			// Add the brand in the rule get product selection type options.
			add_filter('fgf_get_product_selection_types', array( $this, 'add_custom_get_product_selection_type_options' ), 10, 1);
			// Add the brand in the rule criteria total type option.
			add_filter('fgf_rule_criteria_total_type_options', array( $this, 'add_custom_criteria_total_type_option' ), 20, 1);
			// Add the brand in the rule product filter option.
			add_filter('fgf_rule_product_filter_options', array( $this, 'add_custom_product_filter_option' ), 20, 1);
			// Validate and prepare the brand rule fields.
			add_filter('fgf_prepare_rule_post_data', array( $this, 'prepare_rule_post_data' ), 20, 1);
			// Validate and prepare the bulk rule fields.
			add_filter('fgf_prepare_bulk_pricing_rule', array( $this, 'prepare_bulk_rule_post_data' ), 20, 2);
			// Validate and prepare the bulk rule fields.
			add_filter('fgf_prepare_bulk_quantity_rule', array( $this, 'prepare_bulk_rule_post_data' ), 20, 2);
			// May be alter the rule product category column content. 
			add_filter('fgf_rule_product_category_column_content', array( $this, 'maybe_alter_rule_product_category_column_content' ), 10, 2);
			// May be alter the rule buy products column content. 
			add_filter('fgf_buy_product_column_content', array( $this, 'maybe_alter_rule_buy_products_column_content' ), 10, 2);
			// May be alter the rule get products column content. 
			add_filter('fgf_get_product_column_content', array( $this, 'maybe_alter_rule_get_products_column_content' ), 10, 2);
			// May be alter the rule subtotal gift products column content. 
			add_filter('fgf_subtotal_gift_products_column_content', array( $this, 'maybe_alter_rule_subtotal_gift_products_column_content' ), 10, 2);
		}

		/**
		 * Frontend Action.
		 */
		public function frontend_action() {
			// May be alter the cart criteria total based on selection.
			add_action('fgf_rule_cart_criteria_total', array( $this, 'maybe_alter_cart_criteria_total' ), 100, 2);
			// May be alter the rule total price based on selection.
			add_action('fgf_rule_total_price', array( $this, 'maybe_alter_rule_total_price' ), 100, 2);
			// May be validate the rule if the brands are not valid.
			add_action('fgf_rule_product_category_filter', array( $this, 'maybe_validate_rule_brands_filter' ), 20, 2);
			// May be alter the valid gift products based on gift selection type.
			add_filter('fgf_valid_gift_products', array( $this, 'maybe_alter_valid_gift_products' ), 100, 2);
			// May be validate the buy products based on buy product selection type.
			add_filter('fgf_is_valid_buy_product', array( $this, 'maybe_validate_buy_products' ), 100, 3);
			// May be alter the rule current buy product consider type.
			add_filter('fgf_rule_current_buy_product_consider_type', array( $this, 'maybe_alter_current_buy_product_consider_type' ), 100, 2);
			// May be alter the rule current buy product quantity consider type.
			add_filter('fgf_rule_current_buy_product_quantity_consider_type', array( $this, 'maybe_alter_current_buy_product_quantity_consider_type' ), 100, 2);
			// May be alter the bulk valid gift products based on gift selection type.
			add_filter('fgf_bulk_valid_gift_products', array( $this, 'maybe_alter_bulk_valid_gift_products' ), 100, 3);
		}

		/**
		 * Add the brands fields in the rule.
		 *
		 * @param array $rule_data
		 */
		public function add_brands_fields( $rule_data ) {
			$brands    = $this->get_wc_brands();
			$brand_ids = isset($rule_data['id']) ? array_filter((array) get_post_meta($rule_data['id'], 'fgf_cart_brands', true)) : array();

			include_once FGF_ABSPATH . 'inc/compatibility/views/html-rule-criteria-brand-total-fields.php';
		}

		/**
		 * Add the buy product brands fields in the rule.
		 *
		 * @since 11.3.0
		 * @param array $rule_data
		 */
		public function add_buy_product_brands_fields( $rule_data ) {
			$brands              = $this->get_wc_brands();
			$selected_brand_ids  = isset($rule_data['id']) ? array_filter((array) get_post_meta($rule_data['id'], 'fgf_buy_product_brands', true)) : array();
			$brand_consider_type = isset($rule_data['id']) ? get_post_meta($rule_data['id'], 'fgf_buy_brand_consider_type', true) : '1';
			$brand_type          = isset($rule_data['id']) ? get_post_meta($rule_data['id'], 'fgf_buy_brand_quantity_consider_type', true) : '1';

			include_once FGF_ABSPATH . 'inc/compatibility/views/html-rule-buy-product-brands-fields.php';
		}

		/**
		 * Add the get product brands fields in the rule.
		 *
		 * @since 11.3.0
		 * @param array $rule_data
		 */
		public function add_get_product_brands_fields( $rule_data ) {
			$brands             = $this->get_wc_brands();
			$selected_brand_ids = isset($rule_data['id']) ? array_filter((array) get_post_meta($rule_data['id'], 'fgf_get_product_brands', true)) : array();

			include_once FGF_ABSPATH . 'inc/compatibility/views/html-rule-get-product-brands-fields.php';
		}

		/**
		 * Add the gift brands fields in the rule.
		 *
		 * @since 10.8.0
		 * @param array $rule_data
		 */
		public function add_gift_brands_fields( $rule_data ) {
			$brands             = $this->get_wc_brands();
			$selected_brand_ids = isset($rule_data['id']) ? array_filter((array) get_post_meta($rule_data['id'], 'fgf_gift_brands', true)) : array();

			include_once FGF_ABSPATH . 'inc/compatibility/views/html-rule-gift-brands-fields.php';
		}

		/**
		 * Add the subtotal gift total brands fields in the rule.
		 *
		 * @since 11.5.0
		 * @param array $rule_data
		 */
		public function add_subtotal_gifts_total_brands_fields( $rule_data ) {
			$brands             = $this->get_wc_brands();
			$selected_brand_ids = isset($rule_data['id']) ? array_filter((array) get_post_meta($rule_data['id'], 'fgf_subtotal_gift_total_brands', true)) : array();

			include_once FGF_ABSPATH . 'inc/compatibility/views/html-rule-subtotal-gift-total-brands-fields.php';
		}

		/**
		 * Add the subtotal gift brands fields in the rule.
		 *
		 * @since 11.3.0
		 * @param array $rule_data
		 */
		public function add_subtotal_gift_brands_fields( $rule_data ) {
			$brands             = $this->get_wc_brands();
			$selected_brand_ids = isset($rule_data['id']) ? array_filter((array) get_post_meta($rule_data['id'], 'fgf_subtotal_gift_brands', true)) : array();

			include_once FGF_ABSPATH . 'inc/compatibility/views/html-rule-subtotal-gift-brands-fields.php';
		}
	
		/**
		 * Add the bulk pricing brands fields in the rule.
		 *
		 * @since 11.4.0
		 * @param array $rule_data
		 */
		public function add_bulk_pricing_brands_fields( $rule_data ) {
			$brands             = $this->get_wc_brands();
			$selected_brand_ids = isset($rule_data['id']) ? array_filter((array) get_post_meta($rule_data['id'], 'fgf_bulk_pricing_total_brands', true)) : array();

			include_once FGF_ABSPATH . 'inc/compatibility/views/html-rule-bulk-pricing-total-brands-fields.php';
		}

		/**
		 * Add the bulk rule gift brands fields in the rule.
		 *
		 * @since 11.4.0
		 * @param string $rule_key
		 * @param string $rule_id
		 * @param array $bulk_rule
		 */
		public function add_bulk_rule_gift_brands_fields( $rule_key, $rule_id, $bulk_rule = array() ) {
			$brands             = $this->get_wc_brands();
			$selected_brand_ids = isset($bulk_rule['gift_brands']) ? array_filter((array) $bulk_rule['gift_brands']) : array();

			include FGF_ABSPATH . 'inc/compatibility/views/html-rule-bulk-rule-gift-brands-fields.php';
		}


		/**
		 * Add the product filter brands fields in the rule.
		 *
		 * @since 9.4.0
		 * @param array $rule_data
		 */
		public function add_product_filter_brands_fields( $rule_data ) {
			$brands = $this->get_wc_brands();

			$include_brands         = isset($rule_data['id']) ? array_filter((array) get_post_meta($rule_data['id'], 'fgf_include_brands', true)) : array();
			$exclude_brands         = isset($rule_data['id']) ? array_filter((array) get_post_meta($rule_data['id'], 'fgf_exclude_brands', true)) : array();
			$applicable_brands_type = isset($rule_data['id']) ? get_post_meta($rule_data['id'], 'fgf_applicable_brands_type', true) : '1';
			$brand_product_count    = isset($rule_data['id']) ? get_post_meta($rule_data['id'], 'fgf_brand_product_count', true) : '1';

			include_once FGF_ABSPATH . 'inc/compatibility/views/html-rule-product-brand-filters.php';
		}

		/**
		 * Add the brand in the rule gift selection type options.
		 *
		 * @since 10.8.0
		 * @param array $options
		 * @return array
		 */
		public function add_custom_gift_selection_type_options( $options ) {
			$options['brand'] = __('Products from Selected Brands', 'free-gifts-for-woocommerce');

			return $options;
		}

		/**
		 * Add the brand in the rule buy product selection type options.
		 *
		 * @since 11.3.0
		 * @param array $options
		 * @return array
		 */
		public function add_custom_buy_product_selection_type_options( $options ) {
			$options['brand'] = __('Brands', 'free-gifts-for-woocommerce');

			return $options;
		}

		/**
		 * Add the brand in the rule get product selection type options.
		 *
		 * @since 11.3.0
		 * @param array $options
		 * @return array
		 */
		public function add_custom_get_product_selection_type_options( $options ) {
			$options['brand'] = __('Products from Selected Brands', 'free-gifts-for-woocommerce');

			return $options;
		}

		/**
		 * Add the brand in the rule criteria total type option.
		 *
		 * @param array $options
		 * @return array
		 */
		public function add_custom_criteria_total_type_option( $options ) {
			$options['brands'] = __('Brands Total', 'free-gifts-for-woocommerce');

			return $options;
		}

		/**
		 * Add the brand in the rule product filter option.
		 *
		 * @since 9.4.0
		 * @param array $options
		 * @return array
		 */
		public function add_custom_product_filter_option( $options ) {
			$options['include_brands'] = __('Include Brands', 'free-gifts-for-woocommerce');
			$options['exclude_brands'] = __('Exclude Brands', 'free-gifts-for-woocommerce');

			return $options;
		}

		/**
		 * Validate and prepare the brand rule fields.
		 *
		 * @param array $rule_post_data
		 * @return array
		 */
		public function prepare_rule_post_data( $rule_post_data ) {

			switch ($rule_post_data['fgf_rule_type']) {
				case '1':
					// Validate the gift brand selection.
					if ('brand' === $rule_post_data['fgf_gift_type'] && empty($rule_post_data['fgf_gift_brands'])) {
						throw new Exception(esc_html__('Please select atleast one brand', 'free-gifts-for-woocommerce'));
					}

					break;

				case '3':
				case '5':
					// Validate the buy product brand selection.
					if ('brand' === $rule_post_data['fgf_buy_product_type'] && empty($rule_post_data['fgf_buy_product_brands'])) {
						throw new Exception(esc_html__('Please select atleast one brand', 'free-gifts-for-woocommerce'));
					}

					// Validate the get product brand selection.
					if ('brand' === $rule_post_data['fgf_get_product_type'] && empty($rule_post_data['fgf_get_product_brands'])) {
						throw new Exception(esc_html__('Please select atleast one brand', 'free-gifts-for-woocommerce'));
					}

					break;

				case '7':
					// Validate the brand selection of total gift brands.
					if ('brands' === $rule_post_data['fgf_subtotal_price_type'] && empty($rule_post_data['fgf_subtotal_gift_total_brands'])) {
						throw new Exception(esc_html__('Please select atleast one brand for total type', 'free-gifts-for-woocommerce'));
					}

					// Validate the subtotal gift product brand selection.
					if ('brand' === $rule_post_data['fgf_subtotal_gift_type'] && empty($rule_post_data['fgf_subtotal_gift_brands'])) {
						throw new Exception(esc_html__('Please select atleast one brand', 'free-gifts-for-woocommerce'));
					}

					break;

				case '9':
				case '10':
					// Validate the bulk pricing gift product brand selection.
					if ('brands' === $rule_post_data['fgf_bulk_pricing_total_type'] && empty($rule_post_data['fgf_bulk_pricing_total_brands'])) {
						throw new Exception(esc_html__('Please select atleast one brand', 'free-gifts-for-woocommerce'));
					}

					break;
			}

			// Validate the brand selection.
			if ('brands' === $rule_post_data['fgf_total_type'] && empty($rule_post_data['fgf_cart_brands'])) {
				throw new Exception(esc_html__('Please select atleast one brand', 'free-gifts-for-woocommerce'));
			}

			$rule_post_data['fgf_gift_brands']                = isset($rule_post_data['fgf_gift_brands']) ? $rule_post_data['fgf_gift_brands'] : array();
			$rule_post_data['fgf_cart_brands']                = isset($rule_post_data['fgf_cart_brands']) ? $rule_post_data['fgf_cart_brands'] : array();
			$rule_post_data['fgf_subtotal_gift_total_brands'] = isset($rule_post_data['fgf_subtotal_gift_total_brands']) ? $rule_post_data['fgf_subtotal_gift_total_brands'] : array();
			$rule_post_data['fgf_subtotal_gift_brands']       = isset($rule_post_data['fgf_subtotal_gift_brands']) ? $rule_post_data['fgf_subtotal_gift_brands'] : array();
			$rule_post_data['fgf_include_brands']             = isset($rule_post_data['fgf_include_brands']) ? $rule_post_data['fgf_include_brands'] : array();
			$rule_post_data['fgf_exclude_brands']             = isset($rule_post_data['fgf_exclude_brands']) ? $rule_post_data['fgf_exclude_brands'] : array();
			$rule_post_data['fgf_buy_product_brands']         = isset($rule_post_data['fgf_buy_product_brands']) ? $rule_post_data['fgf_buy_product_brands'] : array();
			$rule_post_data['fgf_get_product_brands']         = isset($rule_post_data['fgf_get_product_brands']) ? $rule_post_data['fgf_get_product_brands'] : array();
			$rule_post_data['fgf_bulk_pricing_total_brands']   = isset($rule_post_data['fgf_bulk_pricing_total_brands']) ? $rule_post_data['fgf_bulk_pricing_total_brands'] : array();

			if ('include_brands' === $rule_post_data['fgf_product_filter_type'] && empty($rule_post_data['fgf_include_brands'])) {
				throw new Exception(esc_html__('Please select atleast one Brand', 'free-gifts-for-woocommerce'));
			}

			return $rule_post_data;
		}

		/**
		 * Validate and prepare bulk rule brand fields.
		 * 
		 * @since 11.4.0
		 * @param type $bulk_rule
		 * @param type $ruel_post_data
		 * @return array
		 */
		public function prepare_bulk_rule_post_data( $bulk_rule, $ruel_post_data ) {
			$bulk_rule['gift_brands'] = isset($bulk_rule['gift_brands']) ? $bulk_rule['gift_brands'] : array();

			return $bulk_rule;
		}

		/**
		 * May be alter the rule product category column content. 
		 * 
		 * @since 10.8.0
		 * @param string/HTML $content
		 * @param object $item
		 * @return string/HTML
		 */
		public function maybe_alter_rule_product_category_column_content( $content, $item ) {
			//Return if the rule type is not manual or the gift type is not brand.
			if ('1' !== $item->get_rule_type() || 'brand' !== $item->get_gift_type()) {
				return $content;
			}

			$brand_ids = array_filter((array) get_post_meta($item->get_id(), 'fgf_gift_brands', true));

			return '<b><u>' . __('Brands', 'free-gifts-for-woocommerce') . '</u></b><br />' . fgf_prepare_terms_edit_link_by_ids($brand_ids, self::TAXONOMY);
		}

		/**
		 * May be alter the rule buy product column content. 
		 * 
		 * @since 11.3.0
		 * @param string/HTML $content
		 * @param object $item
		 * @return string/HTML
		 */
		public function maybe_alter_rule_buy_products_column_content( $content, $item ) {
			//Return if the buy product type is not brand.
			if ('brand' !== $item->get_buy_product_type()) {
				return $content;
			}

			$brand_ids = array_filter((array) get_post_meta($item->get_id(), 'fgf_buy_product_brands', true));

			return __('Product(s) of', 'free-gifts-for-woocommerce') . ' ' . fgf_prepare_terms_edit_link_by_ids($brand_ids, self::TAXONOMY);
		}

		/**
		 * May be alter the rule get product column content. 
		 * 
		 * @since 11.3.0
		 * @param string/HTML $content
		 * @param object $item
		 * @return string/HTML
		 */
		public function maybe_alter_rule_get_products_column_content( $content, $item ) {
			//Return if the rule type is not manual BOGO or the buy product type is not brand.
			if ('5' !== $item->get_rule_type() || 'brand' !== $item->get_buy_product_type()) {
				return $content;
			}

			$brand_ids = array_filter((array) get_post_meta($item->get_id(), 'fgf_get_product_brands', true));

			return __('Product(s) of', 'free-gifts-for-woocommerce') . ' ' . fgf_prepare_terms_edit_link_by_ids($brand_ids, self::TAXONOMY);
		}

		/**
		 * May be alter the rule subtotal gift products column content. 
		 * 
		 * @since 11.3.0
		 * @param string/HTML $content
		 * @param object $item
		 * @return string/HTML
		 */
		public function maybe_alter_rule_subtotal_gift_products_column_content( $content, $item ) {
			//Return if the rule type is not manual subtotal type or the gift type is not brand.
			if ('7' !== $item->get_rule_type() || 'brand' !== $item->get_subtotal_gift_type()) {
				return $content;
			}

			$brand_ids = array_filter((array) get_post_meta($item->get_id(), 'fgf_subtotal_gift_brands', true));
			$label     = '<b><u>' . __('Brands', 'free-gifts-for-woocommerce') . '</u></b>';

			return $label . '<br />' . fgf_prepare_terms_edit_link_by_ids($brand_ids, self::TAXONOMY);
		}

		/**
		 * May be alter the cart criteria total based on selection.
		 *
		 * @param float $total
		 * @param object $rule
		 * @return float
		 */
		public function maybe_alter_cart_criteria_total( $total, $rule ) {
			// return if the total type is not brands.
			if ('brands' !== $rule->get_total_type()) {
				return $total;
			}

			$brand_ids = array_filter((array) get_post_meta($rule->get_id(), 'fgf_cart_brands', true));

			return fgf_get_wc_cart_category_subtotal($brand_ids, self::TAXONOMY, $rule->get_consider_cart_subcategories_total(), $rule->is_exclude_category_subtotal_discount_amount());
		}

		/**
		 * May be alter the total gifts price based on selection.
		 *
		 * @since 11.5.0
		 * @param float $total
		 * @param object $rule
		 * @return float
		 */
		public function maybe_alter_rule_total_price( $total, $rule ) {
			// return if the total type is not brands.
			if ('brands' !== $rule->get_subtotal_price_type()) {
				return $total;
			}

			$brand_ids = array_filter((array) get_post_meta($rule->get_id(), 'fgf_subtotal_gift_total_brands', true));

			return fgf_get_wc_cart_category_subtotal($brand_ids, self::TAXONOMY);
		}

		/**
		 * May be validate the rule if the brands are not valid.
		 *
		 * @since 9.4.0
		 * @param boolean $bool
		 * @param object $rule
		 * @return boolean
		 */
		public function maybe_validate_rule_brands_filter( $bool, $rule ) {
			if (!is_object(WC()->cart)) {
				return $bool;
			}

			$brand_ids             = array();
			$brand_product_count   = 0;
			$applicable_brand_type = get_post_meta($rule->get_id(), 'fgf_applicable_brands_type', true);
			$included_brands       = array_filter((array) get_post_meta($rule->get_id(), 'fgf_include_brands', true));

			foreach (WC()->cart->get_cart() as $cart_content) {
				if (isset($cart_content['fgf_gift_product'])) {
					continue;
				}

				switch ($rule->get_product_filter_type()) {
					case 'include_brands':
						$product_brands = get_the_terms($cart_content['product_id'], self::TAXONOMY);
						if (fgf_check_is_array($product_brands)) {
							foreach ($product_brands as $product_brand) {
								$current_brand_id = $product_brand->term_id;
								if ('1' === $applicable_brand_type && in_array($product_brand->term_id, $included_brands)) {
									return true;
								} elseif (in_array($product_brand->term_id, $included_brands)) {
									break;
								}
							}

							if (in_array($current_brand_id, $included_brands)) {
								$brand_product_count += $cart_content['quantity'];
							}

							$brand_ids[] = $current_brand_id;
						}
						break;

					case 'exclude_brands':
						$bool           = true;
						$product_brands = get_the_terms($cart_content['product_id'], self::TAXONOMY);
						if (fgf_check_is_array($product_brands)) {
							$excluded_brands = array_filter((array) get_post_meta($rule->get_id(), 'fgf_exclude_brands', true));
							foreach ($product_brands as $product_brand) {
								if (in_array($product_brand->term_id, $excluded_brands)) {
									$bool = false;
									break;
								}
							}
						}

						break;
				}
			}

			if ('include_brands' === $rule->get_product_filter_type()) {
				if ('4' == $applicable_brand_type) {
					$bool = ( $brand_product_count >= floatval(get_post_meta($rule->get_id(), 'fgf_brand_product_count', true)) );
				} else {
					$bool = FGF_Rule_Handler::validate_applicable_product_category($applicable_brand_type, $included_brands, $brand_ids);
				}
			}

			return $bool;
		}

		/**
		 * May be alter the valid gift products based on gift selection type.
		 * 
		 * @since 10.8.0
		 * @param array $products
		 * @param object $rule
		 * @return array
		 */
		public function maybe_alter_valid_gift_products( $products, $rule ) {
			$selected_brands = array();
			if ('1' === $rule->get_rule_type() && 'brand' === $rule->get_gift_type()) {
				$selected_brands = array_filter((array) get_post_meta($rule->get_id(), 'fgf_gift_brands', true));
			} elseif ('7' === $rule->get_rule_type() && 'brand' === $rule->get_subtotal_gift_type()) {
				$selected_brands = array_filter((array) get_post_meta($rule->get_id(), 'fgf_subtotal_gift_brands', true));
			} elseif ('5' === $rule->get_rule_type() && 'brand' === $rule->get_product_type()) {
				$selected_brands = array_filter((array) get_post_meta($rule->get_id(), 'fgf_get_product_brands', true));
				$selected_brands = array_intersect(fgf_get_normal_taxonomy_products_in_cart(self::TAXONOMY), $selected_brands);
			}

			// Return if the rule type is not a manual gifts or gift selection type is not a brand.
			if (!fgf_check_is_array($selected_brands)) {
				return $products;
			}

			$products = array();
			foreach ($selected_brands as $brand_id) {
				$product_ids       = array();
				$brand_product_ids = fgf_get_product_id_by_category($brand_id, self::TAXONOMY);

				foreach ($brand_product_ids as $product_id) {
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

			return $products;
		}

		/**
		 * May be alter the bulk valid gift products based on gift selection type.
		 * 
		 * @since 10.8.0
		 * @param array $products
		 * @param object $rule
		 * @return array
		 */
		public function maybe_alter_bulk_valid_gift_products( $products, $bulk_rule, $rule ) {
			$selected_brands = array();
			if (in_array($rule->get_rule_type(), array( '9', '11' )) && 'brand' === $bulk_rule['gift_type']) {
				$selected_brands = array_filter((array) $bulk_rule['gift_brands']);
			}

			// Return if the rule type is not a manual gifts or gift selection type is not a brand.
			if (!fgf_check_is_array($selected_brands)) {
				return $products;
			}

			$products = array();
			foreach ($selected_brands as $brand_id) {
				$product_ids       = array();
				$brand_product_ids = fgf_get_product_id_by_category($brand_id, self::TAXONOMY);

				foreach ($brand_product_ids as $product_id) {
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

			return $products;
		}

		/**
		 * May be validate the buy products based on buy product selection type.
		 * 
		 * @since 11.3.0
		 * @param bool $return
		 * @param type $rule
		 * @param type $cart_content
		 * @return bool
		 */
		public function maybe_validate_buy_products( $return, $rule, $cart_content ) {
			// Return if the rule type is not a BOGO rule or buy product selection type is not a brand.
			if (!in_array($rule->get_rule_type(), array( '3', '5' )) || 'brand' !== $rule->get_buy_product_type()) {
				return $return;
			}

			$product_brands  = fgf_get_term_ids($cart_content['product_id'], self::TAXONOMY);
			$selected_brands = array_filter((array) get_post_meta($rule->get_id(), 'fgf_buy_product_brands', true));
			if (array_intersect($selected_brands, $product_brands)) {
				$return = true;
			}

			return $return;
		}

		/**
		 * May be alter the rule current buy product consider type.
		 * 
		 * @since 11.3.0
		 * @param string $type
		 * @param type $rule
		 * @return string
		 */
		public function maybe_alter_current_buy_product_consider_type( $type, $rule ) {
			// Return if the buy product selection type is not a brand.
			if ('brand' !== $rule->get_buy_product_type()) {
				return $type;
			}

			return get_post_meta($rule->get_id(), 'fgf_buy_brand_consider_type', true);
		}

		/**
		 * May be alter the rule current buy product quantity consider type.
		 * 
		 * @since 11.3.0
		 * @param string $type
		 * @param type $rule
		 * @return string
		 */
		public function maybe_alter_current_buy_product_quantity_consider_type( $type, $rule ) {
			// Return if the buy product selection type is not a brand.
			if ('brand' !== $rule->get_buy_product_type()) {
				return $type;
			}

			return get_post_meta($rule->get_id(), 'fgf_buy_brand_quantity_consider_type', true);
		}

		/**
		 * Get the WC brands.
		 *
		 * @return array
		 */
		private function get_wc_brands() {
			$fgf_brands = array();
			$wc_brands  = get_terms(self::TAXONOMY);

			if (!fgf_check_is_array($wc_brands)) {
				return $fgf_brands;
			}

			foreach ($wc_brands as $category) {
				$fgf_brands[$category->term_id] = $category->name;
			}

			return $fgf_brands;
		}
	}

}
