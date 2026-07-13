<?php

/**
 * Rules Tab
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (class_exists('FGF_Rules_Tab')) {
	return new FGF_Rules_Tab();
}

/**
 * FGF_Rules_Tab.
 */
class FGF_Rules_Tab extends FGF_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id           = 'rules';
		$this->show_buttons = false;
		$this->label        = __('Rules', 'free-gifts-for-woocommerce');

		parent::__construct();
	}

	/**
	 * Output the Rules
	 */
	public function output_extra_fields() {
		global $current_action;

		switch ($current_action) {
			case 'new':
				$this->display_rule_configuration_notices();
				$this->render_new_rule_page();
				break;
			case 'edit':
				$this->display_rule_configuration_notices();
				$this->render_edit_rule_page();
				break;
			default:
				$this->render_rules_table();
				break;
		}
	}

	/**
	 * Display the rule configuration notices.
	 *
	 * @return void
	 */
	public function display_rule_configuration_notices() {
		FGF_Settings::notice(__('Please make sure the products you select as free gifts have been published and are currently In-stock.', 'free-gifts-for-woocommerce'));
	}

	/**
	 * Output the Rule New Page
	 */
	public function render_new_rule_page() {
		global $post_rules, $rule_data;

		$default_field_data = array(
			'id' => '',
			'fgf_rule_status' => 'fgf_active',
			'fgf_rule_name' => '',
			'fgf_rule_description' => '',
			'fgf_rule_type' => '1',
			'fgf_rule_consider_type' => '1',
			'fgf_gift_type' => '1',
			'fgf_gift_products' => array(),
			'fgf_gift_categories' => array(),
			'fgf_automatic_product_qty' => '1',
			'fgf_bogo_gift_type' => '1',
			'fgf_buy_product_type' => '1',
			'fgf_buy_product' => array(),
			'fgf_buy_categories' => array(),
			'fgf_buy_product_consider_type' => '1',
			'fgf_buy_category_consider_type' => '1',
			'fgf_buy_category_type' => '1',
			'fgf_buy_product_quantity_consider_type' => '1',
			'fgf_get_product_type' => '1',
			'fgf_get_products' => array(),
			'fgf_get_categories' => array(),
			'fgf_buy_quantity_type' => '1',
			'fgf_bogo_get_gift_type' => '1',
			'fgf_buy_product_count' => '1',
			'fgf_get_product_count' => '1',
			'fgf_bogo_gift_repeat' => '1',
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
			'fgf_rule_allowed_user_type' => '1',
			'fgf_rule_allowed_user_count' => '1',
			'fgf_rule_user_purchased_order_count_type' => 1,
			'fgf_rule_user_purchased_order_min_count' => 1,
			'fgf_rule_user_purchased_order_max_count' => 1,
			'fgf_exclude_category_subtotal_discount_amount' => 1,
			'fgf_rule_restrict_by_wocommerce_coupon_type' => '1',
			'fgf_rule_restrict_by_wocommerce_coupon' => '1',
			'fgf_condition_type' => '1',
			'fgf_total_type' => '1',
			'fgf_cart_categories' => array(),
			'fgf_consider_cart_subcategories_total' => 1,
			'fgf_cart_subtotal_min_value' => '',
			'fgf_cart_subtotal_max_value' => '',
			'fgf_quantity_min_value' => '',
			'fgf_quantity_max_value' => '',
			'fgf_product_count_min_value' => '',
			'fgf_product_count_max_value' => '',
			'fgf_rule_gifts_count_per_order' => '',
			'fgf_rule_restriction_count' => '',
			'fgf_show_notice' => '1',
			'fgf_notice' => '',
			'fgf_notice_image_id' => '',
			'fgf_show_bogo_eligible_notice' => '1',
			'fgf_bogo_eligible_notice' => '',
			'fgf_show_bulk_eligible_notice' => '1',
			'fgf_bulk_eligible_notice' => '',
			'fgf_bulk_notice_image_id' => '',
			'fgf_user_filter_type' => '1',
			'fgf_include_users' => array(),
			'fgf_exclude_users' => array(),
			'fgf_exclude_user_roles' => array(),
			'fgf_include_user_roles' => array(),
			'fgf_product_filter_type' => '1',
			'fgf_include_products' => array(),
			'fgf_include_product_count' => '1',
			'fgf_exclude_products' => array(),
			'fgf_applicable_products_type' => '1',
			'fgf_applicable_categories_type' => '1',
			'fgf_include_categories' => array(),
			'fgf_include_category_product_count' => '1',
			'fgf_exclude_categories' => array(),
			'fgf_virtual_product_restriction' => '1',
		);

		// may be sanitize post data
		$rule_post_data = isset($post_rules) ? $post_rules : array();  // @codingStandardsIgnoreLine.

		$rule_data = wp_parse_args($rule_post_data, $default_field_data);

		// Html for New Rule Page
		include_once FGF_PLUGIN_PATH . '/inc/admin/menu/views/add-new-rule.php';
	}

	/**
	 * Output the Rule Edit Page
	 */
	public function render_edit_rule_page() {
		global $post_rules, $rule_data;
		if (!isset($_GET['id'])) { // @codingStandardsIgnoreLine.
			return;
		}

		$rule_id = absint($_GET['id']); // @codingStandardsIgnoreLine.
		$rule    = fgf_get_rule($rule_id);
		if (!$rule->exists()) {
			return;
		}

		$default_field_data = array(
			'id' => $rule->get_id(),
			'fgf_rule_status' => $rule->get_status(),
			'fgf_rule_name' => $rule->get_name(),
			'fgf_rule_description' => $rule->get_description(),
			'fgf_rule_type' => $rule->get_rule_type(),
			'fgf_rule_consider_type' => $rule->get_rule_consider_type(),
			'fgf_gift_type' => $rule->get_gift_type(),
			'fgf_gift_products' => $rule->get_gift_products(),
			'fgf_gift_categories' => $rule->get_gift_categories(),
			'fgf_automatic_product_qty' => $rule->get_automatic_product_qty(),
			'fgf_buy_product_type' => $rule->get_buy_product_type(),
			'fgf_bogo_gift_type' => $rule->get_bogo_gift_type(),
			'fgf_buy_product' => $rule->get_buy_product(),
			'fgf_buy_categories' => $rule->get_buy_categories(),
			'fgf_buy_product_consider_type' => $rule->get_buy_product_consider_type(),
			'fgf_buy_category_consider_type' => $rule->get_buy_category_consider_type(),
			'fgf_buy_category_type' => $rule->get_buy_category_type(),
			'fgf_buy_product_quantity_consider_type' => $rule->get_buy_product_quantity_consider_type(),
			'fgf_get_product_type' => $rule->get_product_type(),
			'fgf_get_products' => $rule->get_products(),
			'fgf_get_categories' => $rule->get_categories(),
			'fgf_buy_quantity_type' => $rule->get_buy_quantity_type(),
			'fgf_bogo_get_gift_type' => $rule->get_bogo_get_gift_type(),
			'fgf_buy_product_count' => $rule->get_buy_product_count(),
			'fgf_get_product_count' => $rule->get_product_count(),
			'fgf_bogo_gift_repeat' => $rule->get_bogo_gift_repeat(),
			'fgf_bogo_gift_repeat_mode' => $rule->get_bogo_gift_repeat_mode(),
			'fgf_bogo_gift_repeat_limit' => $rule->get_bogo_gift_repeat_limit(),
			'fgf_apply_coupon' => $rule->get_apply_coupon(),
			'fgf_coupon_gift_products' => $rule->get_coupon_gift_products(),
			'fgf_coupon_gift_products_qty' => $rule->get_coupon_gift_products_qty(),
			'fgf_subtotal_price_type' => $rule->get_subtotal_price_type(),
			'fgf_total_categories' => $rule->get_total_categories(),
			'fgf_consider_subcategories_total' => $rule->get_consider_subcategories_total(),
			'fgf_calculate_total_discounted_category_total' => $rule->get_calculate_total_discounted_category_total(),
			'fgf_subtotal_price' => $rule->get_subtotal_price(),
			'fgf_subtotal_gift_type' => $rule->get_subtotal_gift_type(),
			'fgf_subtotal_gift_products' => $rule->get_subtotal_gift_products(),
			'fgf_subtotal_gift_categories' => $rule->get_subtotal_gift_categories(),
			'fgf_subtotal_gift_products_qty' => $rule->get_subtotal_gift_products_qty(),
			'fgf_subtotal_repeat_gift' => $rule->get_subtotal_repeat_gift(),
			'fgf_subtotal_repeat_gift_mode' => $rule->get_subtotal_repeat_gift_mode(),
			'fgf_subtotal_repeat_gift_limit' => $rule->get_subtotal_repeat_gift_limit(),
			'fgf_bulk_quantity_rules_consider_type' => $rule->get_bulk_quantity_rules_consider_type(),
			'fgf_bulk_quantity_rules' => $rule->get_bulk_quantity_rules(),
			'fgf_bulk_pricing_total_type' => $rule->get_bulk_pricing_total_type(),
			'fgf_bulk_pricing_categories' => $rule->get_bulk_pricing_categories(),
			'fgf_consider_bulk_pricing_subcategories_total' => $rule->get_consider_bulk_pricing_subcategories_total(),
			'fgf_calculate_bulk_pricing_discounted_category_total' => $rule->get_calculate_bulk_pricing_discounted_category_total(),
			'fgf_bulk_pricing_rules_consider_type' => $rule->get_bulk_pricing_rules_consider_type(),
			'fgf_bulk_pricing_rules' => $rule->get_bulk_pricing_rules(),
			'fgf_cheapest_gift_price_type' => $rule->get_cheapest_gift_price_type(),
			'fgf_cheapest_gift_quantity' => $rule->get_cheapest_gift_quantity(),            
			'fgf_rule_valid_from_date' => $rule->get_parsed_from_date(),
			'fgf_rule_valid_to_date' => $rule->get_parsed_to_date(),
			'fgf_rule_week_days_validation' => $rule->get_rule_week_days_validation(),
			'fgf_rule_gifts_count_per_order' => $rule->get_rule_gifts_count_per_order(),
			'fgf_rule_restriction_count' => $rule->get_rule_restriction_count(),
			'fgf_rule_usage_count' => floatval($rule->get_rule_usage_count()),
			'fgf_rule_allowed_user_type' => $rule->get_rule_allowed_user_type(),
			'fgf_rule_allowed_user_count' => $rule->get_rule_allowed_user_count(),
			'fgf_rule_user_purchased_order_count_type' => $rule->get_rule_user_purchased_order_count_type(),
			'fgf_rule_user_purchased_order_min_count' => $rule->get_rule_user_purchased_order_min_count(),
			'fgf_rule_user_purchased_order_max_count' => $rule->get_rule_user_purchased_order_max_count(),
			'fgf_exclude_category_subtotal_discount_amount' => $rule->get_exclude_category_subtotal_discount_amount(),
			'fgf_rule_restrict_by_wocommerce_coupon_type' => $rule->get_rule_restrict_by_wocommerce_coupon_type(),
			'fgf_rule_restrict_by_wocommerce_coupon' => $rule->get_rule_restrict_by_wocommerce_coupon(),
			'fgf_condition_type' => $rule->get_condition_type(),
			'fgf_total_type' => $rule->get_total_type(),
			'fgf_cart_categories' => $rule->get_cart_categories(),
			'fgf_consider_cart_subcategories_total' => $rule->get_consider_cart_subcategories_total(),
			'fgf_cart_subtotal_min_value' => $rule->get_cart_subtotal_minimum_value(),
			'fgf_cart_subtotal_max_value' => $rule->get_cart_subtotal_maximum_value(),
			'fgf_quantity_min_value' => $rule->get_quantity_minimum_value(),
			'fgf_quantity_max_value' => $rule->get_quantity_maximum_value(),
			'fgf_product_count_min_value' => $rule->get_product_count_min_value(),
			'fgf_product_count_max_value' => $rule->get_product_count_max_value(),
			'fgf_show_notice' => $rule->get_show_notice(),
			'fgf_notice' => $rule->get_notice(),
			'fgf_notice_image_id' => $rule->get_notice_image_id(),
			'fgf_show_bogo_eligible_notice' => $rule->get_show_bogo_eligible_notice(),
			'fgf_bogo_eligible_notice' => $rule->get_bogo_eligible_notice(),
			'fgf_show_bulk_eligible_notice' => $rule->get_show_bulk_eligible_notice(),
			'fgf_bulk_eligible_notice' => $rule->get_bulk_eligible_notice(),
			'fgf_bulk_notice_image_id' => $rule->get_bulk_notice_image_id(),
			'fgf_user_filter_type' => $rule->get_user_filter_type(),
			'fgf_include_users' => $rule->get_include_users(),
			'fgf_exclude_users' => $rule->get_exclude_users(),
			'fgf_exclude_user_roles' => $rule->get_exclude_user_roles(),
			'fgf_include_user_roles' => $rule->get_include_user_roles(),
			'fgf_product_filter_type' => $rule->get_product_filter_type(),
			'fgf_include_products' => $rule->get_include_products(),
			'fgf_include_product_count' => $rule->get_include_product_count(),
			'fgf_exclude_products' => $rule->get_exclude_products(),
			'fgf_applicable_products_type' => $rule->get_applicable_products_type(),
			'fgf_applicable_categories_type' => $rule->get_applicable_categories_type(),
			'fgf_include_categories' => $rule->get_include_categories(),
			'fgf_include_category_product_count' => $rule->get_include_category_product_count(),
			'fgf_exclude_categories' => $rule->get_exclude_categories(),
			'fgf_virtual_product_restriction' => $rule->get_virtual_product_restriction(),
		);

		// may be sanitize post data
		$rule_post_data = isset($post_rules) ? $post_rules : array();
		$rule_data      = wp_parse_args($rule_post_data, $default_field_data);

		// Html for Edit Rule Page
		include_once FGF_PLUGIN_PATH . '/inc/admin/menu/views/edit-rule.php';
	}

	/**
	 * Output the Rules WP List Table
	 */
	public function render_rules_table() {
		if (!class_exists('FGF_Rules_List_Table')) {
			require_once FGF_PLUGIN_PATH . '/inc/admin/menu/wp-list-table/class-fgf-rules-list-table.php';
		}

		echo '<div class="fgf_table_wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__('Free Gift Rules', 'free-gifts-for-woocommerce') . '</h1>';
		echo '<a class="page-title-action fgf_add_btn" href="' . esc_url(fgf_get_rule_page_url(array( 'action' => 'new' ))) . '">' . esc_html__('Add New Rule', 'free-gifts-for-woocommerce') . '</a>';
		echo '<hr class="wp-header-end">';
		$post_table = new FGF_Rules_List_Table();
		$post_table->render();
		echo '</div>';
	}

	/**
	 * Show the rule panel.
	 *
	 * @return void
	 */
	private static function output_panel() {
		global $rule_data;

		include_once FGF_PLUGIN_PATH . '/inc/admin/menu/views/rule-panel/html-rule-data-panel.php';
	}

	/**
	 * Show the rule panel tabs.
	 *
	 * @return void
	 */
	private static function output_tabs() {
		global $rule_data;

		include_once FGF_PLUGIN_PATH . '/inc/admin/menu/views/rule-panel/html-rule-data-general.php';
		include_once FGF_PLUGIN_PATH . '/inc/admin/menu/views/rule-panel/html-rule-data-notices.php';
		include_once FGF_PLUGIN_PATH . '/inc/admin/menu/views/rule-panel/html-rule-data-restrictions.php';
		include_once FGF_PLUGIN_PATH . '/inc/admin/menu/views/rule-panel/html-rule-data-criteria.php';
		include_once FGF_PLUGIN_PATH . '/inc/admin/menu/views/rule-panel/html-rule-data-filters.php';
	}

	/**
	 * Return array of tabs.
	 *
	 * @return array
	 */
	private static function get_rule_data_tabs() {
		/**
		 * This hook is used to alter the rule tabs.
		 *
		 * @since 1.0
		 */
		$tabs = apply_filters(
				'fgf_rule_data_tabs', array(
			'general' => array(
				'label' => __('General', 'free-gifts-for-woocommerce'),
				'target' => 'fgf_rule_data_general',
				'class' => array(),
				'priority' => 10,
				),
				'restrictions' => array(
				'label' => __('Restrictions', 'free-gifts-for-woocommerce'),
				'target' => 'fgf_rule_data_restrictions',
				'class' => array(),
				'priority' => 20,
				),
				'criteria' => array(
				'label' => __('Criteria', 'free-gifts-for-woocommerce'),
				'target' => 'fgf_rule_data_criteria',
				'class' => array(),
				'priority' => 30,
				),
				'filters' => array(
				'label' => __('Filters', 'free-gifts-for-woocommerce'),
				'target' => 'fgf_rule_data_filters',
				'class' => array(),
				'priority' => 40,
				), 'notices' => array(
				'label' => __('Notice', 'free-gifts-for-woocommerce'),
				'target' => 'fgf_rule_data_notices',
				'class' => array(),
				'priority' => 50,
				),
		));

		// Sort tabs based on priority.
		uasort($tabs, array( __CLASS__, 'rule_data_tabs_sort' ));

		return $tabs;
	}

	/**
	 * Callback to sort tabs on priority.
	 *
	 * @return boolean
	 */
	private static function rule_data_tabs_sort( $a, $b ) {
		if (!isset($a['priority'], $b['priority'])) {
			return -1;
		}

		if ($a['priority'] === $b['priority']) {
			return 0;
		}

		return $a['priority'] < $b['priority'] ? -1 : 1;
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_action, $post_rules;

		// Show success message
		if (isset($_GET['message'])) {
			$message = sanitize_title($_GET['message']);
			switch ($message) {
				case 'created':
					FGF_Settings::add_message(__('New Rule has been created successfuly', 'free-gifts-for-woocommerce'));
					break;

				case 'updated':
					FGF_Settings::add_message(__('Rule has been updated successfully', 'free-gifts-for-woocommerce'));
					break;
			}
		}

		$post_rules = !empty($_REQUEST['fgf_rule']) ? wc_clean(wp_unslash($_REQUEST['fgf_rule'])) : $post_rules;

		if (!isset($_REQUEST['fgf_save'])) {
			return;
		}

		switch ($current_action) {
			case 'new':
				$this->create_new_rule();
				break;
			case 'edit':
				$this->update_rule();
				break;
		}
	}

	/**
	 * Create a new rule.
	 */
	public function create_new_rule() {
		check_admin_referer('fgf_new_rule', '_fgf_nonce');

		try {

			$rule_post_data = self::prepare_rule_data();

			$post_args = array(
				'post_status' => $rule_post_data['fgf_rule_status'],
				'post_title' => $rule_post_data['fgf_rule_name'],
				'post_content' => $rule_post_data['fgf_rule_description'],
				'menu_order' => 99999,
			);

			$rule_id = fgf_create_new_rule($rule_post_data, $post_args);
			/**
			 * This hook is used to do extra action after rule created.
			 *
			 * @since 1.0
			 */
			do_action('fgf_after_created_new_rule', $rule_id, $rule_post_data);

			wp_safe_redirect(
					fgf_get_rule_page_url(
							array(
								'action' => 'edit',
								'id' => $rule_id,
								'message' => 'created',
							)
					)
			);
			exit();
		} catch (Exception $ex) {
			FGF_Settings::add_error($ex->getMessage());
		}
	}

	/**
	 * Update the rule.
	 */
	public function update_rule() {
		check_admin_referer('fgf_update_rule', '_fgf_nonce');

		try {

			$rule_id = !empty($_POST['fgf_rule_id']) ? absint($_POST['fgf_rule_id']) : 0; // @codingStandardsIgnoreLine.
			$rule_post_data = self::prepare_rule_data();

			$post_args = array(
				'post_status' => $rule_post_data['fgf_rule_status'],
				'post_title' => $rule_post_data['fgf_rule_name'],
				'post_content' => $rule_post_data['fgf_rule_description'],
			);

			fgf_update_rule($rule_id, $rule_post_data, $post_args);
			/**
			 * This hook is used to do extra action after rule updated.
			 *
			 * @since 1.0
			 */
			do_action('fgf_after_updated_rule', $rule_id, $rule_post_data);

			wp_safe_redirect(
					fgf_get_rule_page_url(
							array(
								'action' => 'edit',
								'id' => $rule_id,
								'message' => 'updated',
							)
					)
			);
			exit();
		} catch (Exception $ex) {
			FGF_Settings::add_error($ex->getMessage());
		}
	}

	/**
	 * Prepare the rule data.
	 *
	 * @return array
	 */
	public function prepare_rule_data() {
		$rule_post_data = !empty($_REQUEST['fgf_rule']) ? wc_clean(wp_unslash(( $_REQUEST['fgf_rule'] ))) : array();

		// Validate rule name
		if ('' == $rule_post_data['fgf_rule_name']) {
			throw new Exception(esc_html__('Rule Name is mandatory', 'free-gifts-for-woocommerce'));
		}

		$gift_categories         = isset($rule_post_data['fgf_gift_categories']) ? $rule_post_data['fgf_gift_categories'] : array();
		$gift_products           = isset($rule_post_data['fgf_gift_products']) ? $rule_post_data['fgf_gift_products'] : array();
		$include_products        = isset($rule_post_data['fgf_include_products']) ? $rule_post_data['fgf_include_products'] : array();
		$include_categories      = isset($rule_post_data['fgf_include_categories']) ? $rule_post_data['fgf_include_categories'] : array();
		$buy_product             = isset($rule_post_data['fgf_buy_product']) ? $rule_post_data['fgf_buy_product'] : array();
		$buy_categories          = isset($rule_post_data['fgf_buy_categories']) ? $rule_post_data['fgf_buy_categories'] : array();
		$get_products            = isset($rule_post_data['fgf_get_products']) ? $rule_post_data['fgf_get_products'] : array();
		$get_categories          = isset($rule_post_data['fgf_get_categories']) ? $rule_post_data['fgf_get_categories'] : array();
		$apply_coupon            = isset($rule_post_data['fgf_apply_coupon']) ? $rule_post_data['fgf_apply_coupon'] : array();
		$coupon_gift_products    = isset($rule_post_data['fgf_coupon_gift_products']) ? $rule_post_data['fgf_coupon_gift_products'] : array();
		$total_categories        = isset($rule_post_data['fgf_total_categories']) ? $rule_post_data['fgf_total_categories'] : array();
		$subtotal_gift_products  = isset($rule_post_data['fgf_subtotal_gift_products']) ? $rule_post_data['fgf_subtotal_gift_products'] : array();
		$subtotal_categories     = isset($rule_post_data['fgf_subtotal_gift_categories']) ? $rule_post_data['fgf_subtotal_gift_categories'] : array();
		$bulk_pricing_categories = isset($rule_post_data['fgf_bulk_pricing_categories']) ? $rule_post_data['fgf_bulk_pricing_categories'] : array();
		$bulk_quantity_rules     = self::prepare_bulk_quantity_rules($rule_post_data);
		$bulk_pricing_rules      = self::prepare_bulk_pricing_rules($rule_post_data);

		$rule_products = array();

		switch ($rule_post_data['fgf_rule_type']) {
			case '13':
			case '14':
				if (empty($rule_post_data['fgf_cheapest_gift_quantity'])) {
					throw new Exception(esc_html__('Please enter the desired quantity for the gift product(s)', 'free-gifts-for-woocommerce'));
				}
				break;
			case '11':
			case '12':
				if (empty($bulk_quantity_rules)) {
					throw new Exception(esc_html__('Please add the bulk quantity rule', 'free-gifts-for-woocommerce'));
				}
				break;
			case '10':
			case '9':
				if ('3' == $rule_post_data['fgf_bulk_pricing_total_type'] && empty($bulk_pricing_categories)) {
					throw new Exception(esc_html__('Please select a category', 'free-gifts-for-woocommerce'));
				}

				if (empty($bulk_pricing_rules)) {
					throw new Exception(esc_html__('Please add the bulk pricing rule', 'free-gifts-for-woocommerce'));
				}
				break;
			case '7':
			case '8':
				if (empty($rule_post_data['fgf_subtotal_price'])) {
					throw new Exception(esc_html__('Please provide the value for subtotal price', 'free-gifts-for-woocommerce'));
				}

				if ('8' === $rule_post_data['fgf_rule_type'] && empty($subtotal_gift_products)) {
					throw new Exception(esc_html__('Please select atleast one product', 'free-gifts-for-woocommerce'));
				} elseif ('7' === $rule_post_data['fgf_rule_type'] && '1' === $rule_post_data['fgf_gift_type'] && empty($subtotal_gift_products)) {
					throw new Exception(esc_html__('Please select atleast one product', 'free-gifts-for-woocommerce'));
				} elseif ('7' === $rule_post_data['fgf_rule_type'] && '2' === $rule_post_data['fgf_gift_type'] && empty($subtotal_categories)) {
					throw new Exception(esc_html__('Please select atleast one category', 'free-gifts-for-woocommerce'));
				}

				if (empty($rule_post_data['fgf_subtotal_gift_products_qty'])) {
					throw new Exception(esc_html__('Quantity for Selected Free Gift Product(s) cannot be empty', 'free-gifts-for-woocommerce'));
				}

				if (isset($rule_post_data['fgf_subtotal_repeat_gift']) && '2' === $rule_post_data['fgf_subtotal_repeat_gift_mode'] && empty($rule_post_data['fgf_subtotal_repeat_gift_limit'])) {
					throw new Exception(esc_html__('Please enter the limit', 'free-gifts-for-woocommerce'));
				}
				break;

			case '6':
			case '4':
				if (empty($apply_coupon)) {
					throw new Exception(esc_html__('Please select a coupon', 'free-gifts-for-woocommerce'));
				}

				if (empty($coupon_gift_products)) {
					throw new Exception(esc_html__('Please select atleast one product', 'free-gifts-for-woocommerce'));
				} else {
					$rule_products = $coupon_gift_products;
				}

				if ('4' === $rule_post_data['fgf_rule_type'] && empty($rule_post_data['fgf_coupon_gift_products_qty'])) {
					throw new Exception(esc_html__('Quantity for Selected Free Gift Product(s) cannot be empty', 'free-gifts-for-woocommerce'));
				}
				break;
			case '3':
			case '5':
				//Validate if the buy product is not selected.
				if ('1' == $rule_post_data['fgf_buy_product_type'] && empty($buy_product)) {
					throw new Exception(esc_html__('Please select atleast one Product for buy product', 'free-gifts-for-woocommerce'));
				} elseif ('2' == $rule_post_data['fgf_buy_product_type'] && empty($buy_categories)) {
					throw new Exception(esc_html__('Please select atleast one Category for buy product', 'free-gifts-for-woocommerce'));
				}

				//Validate if the get product is not selected.
				if ('5' != $rule_post_data['fgf_rule_type'] && '2' == $rule_post_data['fgf_bogo_gift_type'] && empty($get_products)) {
					throw new Exception(esc_html__('Please select atleast one Product for get product', 'free-gifts-for-woocommerce'));
				} elseif ('5' == $rule_post_data['fgf_rule_type'] && '2' == $rule_post_data['fgf_get_product_type'] && empty($get_categories)) {
					throw new Exception(esc_html__('Please select atleast one Category for get product', 'free-gifts-for-woocommerce'));
				} elseif ('5' == $rule_post_data['fgf_rule_type'] && '1' == $rule_post_data['fgf_get_product_type'] && empty($get_products)) {
					throw new Exception(esc_html__('Please select atleast one Product for get product', 'free-gifts-for-woocommerce'));
				}

				//Validate if the buy product count does not exist.
				if (empty($rule_post_data['fgf_buy_product_count'])) {
					throw new Exception(esc_html__('Buy Product count cannot be empty.', 'free-gifts-for-woocommerce'));
				}

				//Validate if the get product count does not exist.
				if (empty($rule_post_data['fgf_get_product_count'])) {
					throw new Exception(esc_html__('Get Product count cannot be empty.', 'free-gifts-for-woocommerce'));
				}

				//Validate if the repeat count does not exist.
				if (isset($rule_post_data['fgf_bogo_gift_repeat']) && '2' == $rule_post_data['fgf_bogo_gift_repeat_mode'] && empty($rule_post_data['fgf_bogo_gift_repeat_limit'])) {
					throw new Exception(esc_html__('Repeat Limit field cannot be empty.', 'free-gifts-for-woocommerce'));
				}

				if ('2' == $rule_post_data['fgf_bogo_gift_type'] && !empty($get_products)) {
					$rule_products = $get_products;
				}
				break;

			case '2':
				if (empty($gift_products)) {
					throw new Exception(esc_html__('Please select atleast one product', 'free-gifts-for-woocommerce'));
				} else {
					$rule_products = $gift_products;
				}

				if (empty($rule_post_data['fgf_automatic_product_qty'])) {
					throw new Exception(esc_html__('Quantity for Selected Free Gift Product(s)cannot be empty', 'free-gifts-for-woocommerce'));
				}
				break;

			case '1':
				if ('2' == $rule_post_data['fgf_gift_type'] && empty($gift_categories)) {
					throw new Exception(esc_html__('Please select atleast one category', 'free-gifts-for-woocommerce'));
				} elseif ('1' == $rule_post_data['fgf_gift_type'] && empty($gift_products)) {
					throw new Exception(esc_html__('Please select atleast one product', 'free-gifts-for-woocommerce'));
				}

				if ('1' == $rule_post_data['fgf_gift_type'] && !empty($gift_products)) {
					$rule_products = $gift_products;
				}
				break;
		}

		//Validate the Products is purchasable.
		$non_purchasable_product = self::get_non_purchasable_product($rule_products);
		if ($non_purchasable_product) {
			/* translators: %s: products */
			throw new Exception(wp_kses_post(sprintf(__('The selected product(s) %s cannot be set as free gifts. Please make sure the products you select as free gifts have been published and are currently In-stock.', 'free-gifts-for-woocommerce'), $non_purchasable_product)));
		}

		//Validate the include Products/Category selection.
		if ('2' == $rule_post_data['fgf_product_filter_type'] && empty($include_products)) {
			throw new Exception(esc_html__('Please select atleast one Product', 'free-gifts-for-woocommerce'));
		} elseif ('5' == $rule_post_data['fgf_product_filter_type'] && empty($include_categories)) {
			throw new Exception(esc_html__('Please select atleast one Category', 'free-gifts-for-woocommerce'));
		}

		//Validate the product count selection.
		if ('2' == $rule_post_data['fgf_product_filter_type'] && '4' == $rule_post_data['fgf_applicable_products_type']) {
			if ($rule_post_data['fgf_include_product_count'] && count($include_products) < $rule_post_data['fgf_include_product_count']) {
				throw new Exception(esc_html__('Product Count cannot be more than the number of selected products.', 'free-gifts-for-woocommerce'));
			} elseif (!$rule_post_data['fgf_include_product_count']) {
				throw new Exception(esc_html__('Product Count cannot be empty.', 'free-gifts-for-woocommerce'));
			}
		}

		//Validate the categories product count selection.
		if ('5' == $rule_post_data['fgf_product_filter_type'] && '4' == $rule_post_data['fgf_applicable_categories_type'] && !$rule_post_data['fgf_include_category_product_count']) {
			throw new Exception(esc_html__('Minimum Quantity field cannot be empty.', 'free-gifts-for-woocommerce'));
		}

		$rule_post_data['fgf_subtotal_price']                            = wc_format_decimal($rule_post_data['fgf_subtotal_price']);
		$rule_post_data['fgf_calculate_total_discounted_category_total'] = isset($_REQUEST['fgf_rule']['fgf_calculate_total_discounted_category_total']) ? wp_kses_post($_REQUEST['fgf_rule']['fgf_calculate_total_discounted_category_total']) : '1';
		$rule_post_data['fgf_cart_subtotal_max_value']                   = wc_format_decimal($rule_post_data['fgf_cart_subtotal_max_value']);
		$rule_post_data['fgf_cart_subtotal_max_value']                   = wc_format_decimal($rule_post_data['fgf_cart_subtotal_max_value']);
		$rule_post_data['fgf_notice']                                    = isset($_REQUEST['fgf_rule']['fgf_notice']) ? wp_kses_post($_REQUEST['fgf_rule']['fgf_notice']) : '';
		$rule_post_data['fgf_bulk_eligible_notice']                      = isset($_REQUEST['fgf_rule']['fgf_bulk_eligible_notice']) ? wp_kses_post($_REQUEST['fgf_rule']['fgf_bulk_eligible_notice']) : '';
		$rule_post_data['fgf_bogo_eligible_notice'] = isset($_REQUEST['fgf_rule']['fgf_bogo_eligible_notice']) ? wp_kses_post($_REQUEST['fgf_rule']['fgf_bogo_eligible_notice']) : '';
		
			// Validate Select2 values.
		$rule_post_data['fgf_gift_categories']                                  = $gift_categories;
		$rule_post_data['fgf_gift_products']                                    = $gift_products;
		$rule_post_data['fgf_buy_product']                                      = $buy_product;
		$rule_post_data['fgf_buy_categories']                                   = $buy_categories;
		$rule_post_data['fgf_get_products']                                     = $get_products;
		$rule_post_data['fgf_get_categories']                                   = $get_categories;
		$rule_post_data['fgf_apply_coupon']                                     = $apply_coupon;
		$rule_post_data['fgf_coupon_gift_products']                             = $coupon_gift_products;
		$rule_post_data['fgf_total_categories']                                 = $total_categories;
		$rule_post_data['fgf_subtotal_gift_products']                           = $subtotal_gift_products;
		$rule_post_data['fgf_subtotal_gift_categories']                         = $subtotal_categories;
		$rule_post_data['fgf_bulk_quantity_rules']                              = $bulk_quantity_rules;
		$rule_post_data['fgf_bulk_pricing_categories']                          = $bulk_pricing_categories;
		$rule_post_data['fgf_calculate_bulk_pricing_discounted_category_total'] = isset($rule_post_data['fgf_calculate_bulk_pricing_discounted_category_total']) ? '2' : '1';
		$rule_post_data['fgf_bulk_pricing_rules']                               = $bulk_pricing_rules;
		$rule_post_data['fgf_subtotal_repeat_gift']                             = isset($rule_post_data['fgf_subtotal_repeat_gift']) ? '2' : '1';
		$rule_post_data['fgf_exclude_category_subtotal_discount_amount']        = isset($rule_post_data['fgf_exclude_category_subtotal_discount_amount']) ? '2' : '1';
		$rule_post_data['fgf_bogo_gift_repeat']                                 = isset($rule_post_data['fgf_bogo_gift_repeat']) ? '2' : '1';
		$rule_post_data['fgf_include_users']                                    = isset($rule_post_data['fgf_include_users']) ? $rule_post_data['fgf_include_users'] : array();
		$rule_post_data['fgf_exclude_users']                                    = isset($rule_post_data['fgf_exclude_users']) ? $rule_post_data['fgf_exclude_users'] : array();
		$rule_post_data['fgf_include_user_roles']                               = isset($rule_post_data['fgf_include_user_roles']) ? $rule_post_data['fgf_include_user_roles'] : array();
		$rule_post_data['fgf_exclude_user_roles']                               = isset($rule_post_data['fgf_exclude_user_roles']) ? $rule_post_data['fgf_exclude_user_roles'] : array();
		$rule_post_data['fgf_include_products']                                 = $include_products;
		$rule_post_data['fgf_exclude_products']                                 = isset($rule_post_data['fgf_exclude_products']) ? $rule_post_data['fgf_exclude_products'] : array();
		$rule_post_data['fgf_cart_categories']                                  = isset($rule_post_data['fgf_cart_categories']) ? $rule_post_data['fgf_cart_categories'] : array();
		$rule_post_data['fgf_rule_week_days_validation']                        = isset($rule_post_data['fgf_rule_week_days_validation']) ? $rule_post_data['fgf_rule_week_days_validation'] : array();
		/**
		 * This hook is used to alter the rule post data.
		 *
		 * @since 8.6
		 */
		return apply_filters('fgf_prepare_rule_post_data', $rule_post_data);
	}

	/**
	 * Get the non purchasable product.
	 *
	 * @return false/array
	 */
	public function get_non_purchasable_product( $product_ids ) {

		if (!fgf_check_is_array($product_ids)) {
			return false;
		}

		$non_puchasable_products = array();

		foreach ($product_ids as $product_id) {
			$product = wc_get_product($product_id);

			if (!is_object($product) || ( $product->is_purchasable() && $product->is_in_stock() )) {
				continue;
			}

			$non_puchasable_products[] = '<a href="' . get_edit_post_link($product_id) . '">' . $product->get_name() . '</a>';
		}

		if (!fgf_check_is_array($non_puchasable_products)) {
			return false;
		}

		return implode(', ', $non_puchasable_products);
	}

	/**
	 * Prepare bulk quantity rules.
	 * 
	 * @since 11.4.0
	 * @param array $rule_post_data
	 * @return array
	 */
	private static function prepare_bulk_quantity_rules( $rule_post_data ) {
		$bulk_quantity_rules = isset($rule_post_data['fgf_bulk_quantity_rules']) ? $rule_post_data['fgf_bulk_quantity_rules'] : array();
		if (!fgf_check_is_array($bulk_quantity_rules)) {
			return array();
		}

		$formatted_bulk_quantity_rules = array();
		foreach ($bulk_quantity_rules as $bulk_quantity_rule_key => $bulk_quantity_rule) {
			$bulk_quantity_rule['gift_products']   = isset($bulk_quantity_rule['gift_products']) ? $bulk_quantity_rule['gift_products'] : array();
			$bulk_quantity_rule['gift_categories'] = isset($bulk_quantity_rule['gift_categories']) ? $bulk_quantity_rule['gift_categories'] : array();

			/**
			 * Prepare bulk quantity rule.
			 * 
			 * @since 11.4.0
			 */
			$formatted_bulk_quantity_rules[$bulk_quantity_rule_key] = apply_filters('fgf_prepare_bulk_quantity_rule', $bulk_quantity_rule, $rule_post_data);
		}

		return $formatted_bulk_quantity_rules;
	}

	/**
	 * Prepare bulk pricing rules.
	 * 
	 * @since 11.4.0
	 * @param array $rule_post_data
	 * @return array
	 */
	private static function prepare_bulk_pricing_rules( $rule_post_data ) {
		$bulk_pricing_rules = isset($rule_post_data['fgf_bulk_pricing_rules']) ? $rule_post_data['fgf_bulk_pricing_rules'] : array();
		if (!fgf_check_is_array($bulk_pricing_rules)) {
			return array();
		}

		$formatted_bulk_pricing_rules = array();
		foreach ($bulk_pricing_rules as $bulk_pricing_rule_key => $bulk_pricing_rule) {
			$bulk_pricing_rule['gift_products']   = isset($bulk_pricing_rule['gift_products']) ? $bulk_pricing_rule['gift_products'] : array();
			$bulk_pricing_rule['gift_categories'] = isset($bulk_pricing_rule['gift_categories']) ? $bulk_pricing_rule['gift_categories'] : array();
			$bulk_pricing_rule['minimum']         = wc_format_decimal($bulk_pricing_rule['minimum']);
			$bulk_pricing_rule['maximum']         = wc_format_decimal($bulk_pricing_rule['maximum']);

			/**
			 * Prepare bulk pricing rule.
			 * 
			 * @since 11.4.0
			 */
			$formatted_bulk_pricing_rules[$bulk_pricing_rule_key] = apply_filters('fgf_prepare_bulk_pricing_rule', $bulk_pricing_rule, $rule_post_data);
		}

		return $formatted_bulk_pricing_rules;
	}
}

return new FGF_Rules_Tab();
