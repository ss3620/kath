<?php

/**
 * Rest API - Rule Controller.
 * 
 * @since 9.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('FGF_Rule_Controller')) {

	/**
	 * Class
	 */
	class FGF_Rule_Controller extends FGF_REST_Posts_Controller {

		/**
		 * Post type.
		 *
		 * @since 9.0.0
		 * @var string
		 */
		protected $post_type = FGF_Register_Post_Types::RULES_POSTTYPE;

		/**
		 * Route base.
		 *
		 * @since 9.0.0
		 * @var string
		 */
		protected $rest_base = 'rules';

		/**
		 * Register the Rest API.
		 * 
		 * @since 9.0.0
		 */
		public function register_routes() {

			register_rest_route($this->namespace, '/' . $this->rest_base, array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
					//                array(
					//                    'methods' => WP_REST_Server::CREATABLE,
					//                    'callback' => array($this, 'create_item'),
					//                    'permission_callback' => array($this, 'create_item_permissions_check'),
					//                    'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE)
					//                ),
					//                'schema' => array($this, 'get_public_item_schema'),
					)
			);

			register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)',
					array(
						array(
							'methods' => WP_REST_Server::READABLE,
							'callback' => array( $this, 'get_item' ),
							'permission_callback' => array( $this, 'get_item_permissions_check' ),
						),
					//                        array(
					//                            'methods' => WP_REST_Server::EDITABLE,
					//                            'callback' => array($this, 'update_item'),
					////                            'permission_callback' => array($this, 'update_item_permissions_check'),
					//                            'args' => $this->get_endpoint_args_for_item_schema(WP_REST_Server::CREATABLE)
					//                        ),
					//                        array(
					//                            'methods' => WP_REST_Server::DELETABLE,
					//                            'callback' => array($this, 'delete_item'),
					//                            'permission_callback' => array($this, 'delete_item_permissions_check'),
					//                            'args' => array(
					//                                'force' => array(
					//                                    'default' => false,
					//                                    'type' => 'boolean',
					//                                    'description' => __('Whether to bypass trash and force deletion.', 'free-gifts-for-woocommerce'),
					//                                ),
					//                            ),
					//                        ),
					//                        'schema' => array($this, 'get_public_item_schema'),
					)
			);
		}

		/**
		 * Get object.
		 *
		 * @since 9.0.0
		 * @param int $id Object ID.
		 * @return Object
		 */
		protected function get_object( $id ) {
			return fgf_get_rule($id);
		}

		/**
		 * Get all rules.
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_items( $request ) {
			$args = array(
				'post_type' => $this->post_type,
				'post_status' => fgf_get_rule_statuses(),
				'posts_per_page' => '-1',
				'fields' => 'ids',
				'orderby' => 'menu_order',
				'order' => 'ASC',
			);

			$rule_ids = get_posts($args);

			$rules = array();
			foreach ($rule_ids as $rule_id) {
				$rule = $this->get_object($rule_id);
				if (!$rule->exists()) {
					continue;
				}

				$rules[] = $this->prepare_item_for_response($rule, $request);
			}

			return rest_ensure_response($rules);
		}

		/**
		 * Get the rule's schema, conforming to JSON Schema.
		 *
		 * @since 11.1.0
		 * @return array
		 */
		public function get_item_schema() {
			return array(
				'$schema' => 'http://json-schema.org/draft-04/schema#',
				'title' => $this->post_type,
				'type' => 'object',
				'properties' => array(
					'id' => array(
						'description' => __('Unique identifier for the object.', 'free-gifts-for-woocommerce'),
						'type' => 'integer',
						'context' => array( 'view', 'edit' ),
						'readonly' => true,
					),
					'name' => array(
						'description' => __('Rule Name.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'status' => array(
						'description' => __('Rule Status.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'date_created' => array(
						'description' => __("The date the coupon was created, in the site's timezone.", 'free-gifts-for-woocommerce'),
						'type' => 'date-time',
						'context' => array( 'view', 'edit' ),
						'readonly' => true,
					),
					'date_modified' => array(
						'description' => __("The date the coupon was last modified, in the site's timezone.", 'free-gifts-for-woocommerce'),
						'type' => 'date-time',
						'context' => array( 'view', 'edit' ),
						'readonly' => true,
					),
					'type' => array(
						'description' => __('Rule Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'consider_type' => array(
						'description' => __('Rule Consider Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'gift_type' => array(
						'description' => __('Gift Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'gift_product_ids' => array(
						'description' => __('Gift Product IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array(
							'type' => 'integer',
						),
						'context' => array( 'view', 'edit' ),
					),
					'gift_category_ids' => array(
						'description' => __('Gift Category IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array(
							'type' => 'integer',
						),
						'context' => array( 'view', 'edit' ),
					),
					'automatic_product_qty' => array(
						'description' => __('Automatic Product Quantity.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'bogo_gift_type' => array(
						'description' => __('BOGO Gift Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'buy_product_type' => array(
						'description' => __('Buy Product Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'buy_category_type' => array(
						'description' => __('Buy Category Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'buy_product_ids' => array(
						'description' => __('Buy Product IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array(
							'type' => 'integer',
						),
						'context' => array( 'view', 'edit' ),
					),
					'buy_category_ids' => array(
						'description' => __('Buy Category IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array(
							'type' => 'integer',
						),
						'context' => array( 'view', 'edit' ),
					),
					'buy_product_consider_type' => array(
						'description' => __('Buy Product Consider Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'buy_category_consider_type' => array(
						'description' => __('Buy Category Consider Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'buy_product_quantity_consider_type' => array(
						'description' => __('Buy Product Quantity Consider Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'get_product_type' => array(
						'description' => __('Get Product Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'get_product_ids' => array(
						'description' => __('Get Product IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array(
							'type' => 'integer',
						),
						'context' => array( 'view', 'edit' ),
					),
					'get_category_ids' => array(
						'description' => __('Get Category IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array(
							'type' => 'integer',
						),
						'context' => array( 'view', 'edit' ),
					),
					'get_product_consider_type' => array(
						'description' => __('Get Product Consider Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'buy_product_count' => array(
						'description' => __('Buy Product Count.', 'free-gifts-for-woocommerce'),
						'type' => 'integer',
						'context' => array( 'view', 'edit' ),
					),
					'get_product_count' => array(
						'description' => __('Get Product Count.', 'free-gifts-for-woocommerce'),
						'type' => 'integer',
						'context' => array( 'view', 'edit' ),
					),
					'bogo_repeat_gifts' => array(
						'description' => __('BOGO Repeat Gifts.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'bogo_repeat_gifts_mode' => array(
						'description' => __('BOGO Repeat Gifts Mode.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'bogo_repeat_gifts_limit' => array(
						'description' => __('BOGO Repeat Gifts Limit.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'coupon_ids' => array(
						'description' => __('Coupon IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array(
							'type' => 'integer',
						),
						'context' => array( 'view', 'edit' ),
					),
					'coupon_gift_product_ids' => array(
						'description' => __('Coupon Gift Product IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array(
							'type' => 'integer',
						),
						'context' => array( 'view', 'edit' ),
					),
					'coupon_gift_products_qty' => array(
						'description' => __('Coupon Gift Product Quantity.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'total_price' => array(
						'description' => __('Total Price.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'total_gift_type' => array(
						'description' => __('Total Gift Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'total_gift_product_ids' => array(
						'description' => __('Total Gift Product IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array(
							'type' => 'integer',
						),
						'context' => array( 'view', 'edit' ),
					),
					'total_gift_category_ids' => array(
						'description' => __('Total Gift Categories IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array(
							'type' => 'integer',
						),
						'context' => array( 'view', 'edit' ),
					),
					'total_gift_products_qty' => array(
						'description' => __('Total Gift Products Quantity', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'total_repeat_gift' => array(
						'description' => __('Total Repeat Gift', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'total_repeat_gift_mode' => array(
						'description' => __('Total Repeat Gift Mode', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'total_repeat_gift_limit' => array(
						'description' => __('Total Repeat Gift Limit', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'rule_from_date' => array(
						'description' => __('Rule From Date.', 'free-gifts-for-woocommerce'),
						'type' => 'date-time',
						'context' => array( 'view', 'edit' ),
					),
					'rule_to_date' => array(
						'description' => __('Rule To Date.', 'free-gifts-for-woocommerce'),
						'type' => 'date-time',
						'context' => array( 'view', 'edit' ),
					),
					'rule_week_days' => array(
						'description' => __('Rule Week Days.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array(
							'type' => 'string',
						),
						'context' => array( 'view', 'edit' ),
					),
					'gift_count_per_order' => array(
						'description' => __('Gift Count per Order.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'rule_usage_count' => array(
						'description' => __('Rule Usage Count.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'rule_restriction_count' => array(
						'description' => __('Rule Restriction Count.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'rule_allowed_user_type' => array(
						'description' => __('Rule Allowed User Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'rule_allowed_user_count' => array(
						'description' => __('Rule Allowed User Count.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'rule_allowed_user_usage_details' => array(
						'description' => __('Rule Allowed User Usage Details.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array( 'type' => 'array' ),
						'context' => array( 'view', 'edit' ),
						'readonly' => true,
					),
					'rule_user_purchased_order_type' => array(
						'description' => __('Rule User Purchased Order Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'rule_user_purchased_order_minimum_count' => array(
						'description' => __('Rule User Purchased Order Minimum Count.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'rule_user_purchased_order_maximum_count' => array(
						'description' => __('Rule User Purchased Order Maximum Count.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'rule_restriction_by_coupon_type' => array(
						'description' => __('Rule Restriction by Coupon Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'rule_restriction_by_coupon' => array(
						'description' => __('Rule Restriction by Coupon.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'criteria_type' => array(
						'description' => __('Criteria Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'criteria_total_type' => array(
						'description' => __('Criteria Total Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'criteria_category_ids' => array(
						'description' => __('Criteria Category IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array( 'type' => 'array' ),
						'context' => array( 'view', 'edit' ),
						'readonly' => true,
					),
					'consider_criteria_subcategories_total' => array(
						'description' => __('Consider Criteria Sub Categories Total.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'calculate_discounted_category_total' => array(
						'description' => __('Calculate Discounted Categories Total.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'total_minimum_value' => array(
						'description' => __('Total Minimum Value.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'total_maximum_value' => array(
						'description' => __('Total Maximum Value.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'quantity_minimum_value' => array(
						'description' => __('Quantity Minimum Value.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'quantity_maximum_value' => array(
						'description' => __('Quantity Maximum Value.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'product_count_minimum_value' => array(
						'description' => __('Product Count Minimum Value.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'product_count_maximum_value' => array(
						'description' => __('Product Count Maximum Value.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'user_filter_type' => array(
						'description' => __('User Filter Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'filter_include_user_ids' => array(
						'description' => __('Filter Include User IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array( 'type' => 'array' ),
						'context' => array( 'view', 'edit' ),
					),
					'filter_exclude_user_ids' => array(
						'description' => __('Filter Exclude User IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array( 'type' => 'array' ),
						'context' => array( 'view', 'edit' ),
					),
					'filter_include_user_roles' => array(
						'description' => __('Filter Include User Roles.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array( 'type' => 'array' ),
						'context' => array( 'view', 'edit' ),
					),
					'filter_exclude_user_roles' => array(
						'description' => __('Filter Exclude User Roles.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array( 'type' => 'array' ),
						'context' => array( 'view', 'edit' ),
					),
					'product_filter_type' => array(
						'description' => __('Product Filter Type.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'filter_applicable_product_mode' => array(
						'description' => __('Filter Applicable Product Mode.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array( 'type' => 'array' ),
						'context' => array( 'view', 'edit' ),
					),
					'filter_include_product_ids' => array(
						'description' => __('Filter Include Product IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array( 'type' => 'array' ),
						'context' => array( 'view', 'edit' ),
					),
					'filter_include_product_count' => array(
						'description' => __('Filter Include Product Count.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'filter_exclude_product_ids' => array(
						'description' => __('Filter Exclude Product IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array( 'type' => 'array' ),
						'context' => array( 'view', 'edit' ),
					),
					'filter_applicable_category_mode' => array(
						'description' => __('Filter Applicable Category Mode.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array( 'type' => 'array' ),
						'context' => array( 'view', 'edit' ),
					),
					'filter_include_category_ids' => array(
						'description' => __('Filter Include Category IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array( 'type' => 'array' ),
						'context' => array( 'view', 'edit' ),
					),
					'filter_include_category_product_count' => array(
						'description' => __('Filter Include Category  Product Count.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'filter_exclude_category_ids' => array(
						'description' => __('Filter Exclude Category IDs.', 'free-gifts-for-woocommerce'),
						'type' => 'array',
						'items' => array( 'type' => 'array' ),
						'context' => array( 'view', 'edit' ),
					),
					'filter_virtual_product' => array(
						'description' => __('Filter Virtual Product.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'show_notice' => array(
						'description' => __('Show Notice.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'notice' => array(
						'description' => __('Notice.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
					'notice_image_id' => array(
						'description' => __('Notice Image ID.', 'free-gifts-for-woocommerce'),
						'type' => 'string',
						'context' => array( 'view', 'edit' ),
					),
				),
			);
		}

		/**
		 * Get meta keys.
		 * 
		 * @since 11.1.0
		 * @return array
		 */
		public function get_meta_keys() {
			return array(
				'type' => 'fgf_rule_type',
				'consider_type' => 'fgf_rule_consider_type',
				'gift_type' => 'fgf_gift_type',
				'gift_product_ids' => 'fgf_gift_products',
				'gift_category_ids' => 'fgf_gift_categories',
				'automatic_product_qty' => 'fgf_automatic_product_qty',
				'bogo_gift_type' => 'fgf_bogo_gift_type',
				'buy_product_type' => 'fgf_buy_product_type',
				'buy_product_ids' => 'fgf_buy_product',
				'buy_category_ids' => 'fgf_buy_categories',
				'buy_product_consider_type' => 'fgf_buy_product_consider_type',
				'buy_category_consider_type' => 'fgf_buy_category_consider_type',
				'buy_product_quantity_consider_type' => 'fgf_buy_product_quantity_consider_type',
				'buy_category_product_quantity_consider_type' => 'fgf_buy_category_type',
				'get_product_type' => 'fgf_get_product_type',
				'get_product_ids' => 'fgf_get_products',
				'get_category_ids' => 'fgf_get_categories',
				'get_product_consider_type' => 'fgf_buy_quantity_type',
				'buy_product_count' => 'fgf_buy_product_count',
				'get_product_count' => 'fgf_get_product_count',
				'bogo_repeat_gifts' => 'fgf_bogo_gift_repeat',
				'bogo_repeat_gifts_mode' => 'fgf_bogo_gift_repeat_mode',
				'bogo_repeat_gifts_limit' => 'fgf_bogo_gift_repeat_limit',
				'coupon_ids' => 'fgf_apply_coupon',
				'coupon_gift_product_ids' => 'fgf_coupon_gift_products',
				'coupon_gift_products_qty' => 'fgf_coupon_gift_products_qty',
				'total_price_type' => 'fgf_subtotal_price_type',
				'total_categories' => 'fgf_total_categories',
				'consider_subcategories_total' => 'fgf_consider_subcategories_total',
				'calculate_total_discounted_category_total' => 'fgf_calculate_total_discounted_category_total',
				'total_price' => 'fgf_subtotal_price',
				'total_gift_type' => 'fgf_subtotal_gift_type',
				'total_gift_product_ids' => 'fgf_subtotal_gift_products',
				'total_gift_category_ids' => 'fgf_subtotal_gift_categories',
				'total_gift_products_qty' => 'fgf_subtotal_gift_products_qty',
				'total_repeat_gift' => 'fgf_subtotal_repeat_gift',
				'total_repeat_gift_mode' => 'fgf_subtotal_repeat_gift_mode',
				'total_repeat_gift_limit' => 'fgf_subtotal_repeat_gift_limit',
				'bulk_quantity_rules_consider_type' => 'bulk_quantity_rules_consider_type',
				'bulk_quantity_rules' => 'fgf_bulk_quantity_rules',
				'bulk_pricing_total_type' => 'fgf_bulk_pricing_total_type',
				'bulk_pricing_categories' => 'fgf_bulk_pricing_categories',
				'consider_bulk_pricing_subcategories_total' => 'fgf_consider_bulk_pricing_subcategories_total',
				'calculate_bulk_pricing_discounted_category_total' => 'fgf_calculate_bulk_pricing_discounted_category_total',
				'bulk_pricing_rules_consider_type' => 'fgf_bulk_pricing_rules_consider_type',
				'bulk_pricing_rules' => 'fgf_bulk_pricing_rules',
				'cheapest_gift_price_type' => 'fgf_cheapest_gift_price_type',
				'cheapest_gift_quantity' => 'fgf_cheapest_gift_quantity',
				'rule_from_date' => 'fgf_rule_valid_from_date',
				'rule_to_date' => 'fgf_rule_valid_to_date',
				'rule_week_days' => 'fgf_rule_week_days_validation',
				'gift_count_per_order' => 'fgf_rule_gifts_count_per_order',
				'rule_usage_count' => 'fgf_rule_usage_count',
				'rule_restriction_count' => 'fgf_rule_restriction_count',
				'rule_allowed_user_type' => 'fgf_rule_allowed_user_type',
				'rule_allowed_user_count' => 'fgf_rule_allowed_user_count',
				'rule_allowed_user_usage_details' => 'fgf_rule_allowed_user_usage_count',
				'rule_user_purchased_order_type' => 'fgf_rule_user_purchased_order_count_type',
				'rule_user_purchased_order_minimum_count' => 'fgf_rule_user_purchased_order_min_count',
				'rule_user_purchased_order_maximum_count' => 'fgf_rule_user_purchased_order_max_count',
				'rule_restriction_by_coupon_type' => 'fgf_rule_restrict_by_wocommerce_coupon_type',
				'rule_restriction_by_coupon' => 'fgf_rule_restrict_by_wocommerce_coupon',
				'criteria_type' => 'fgf_condition_type',
				'criteria_total_type' => 'fgf_total_type',
				'criteria_category_ids' => 'fgf_cart_categories',
				'consider_criteria_subcategories_total' => 'fgf_consider_cart_subcategories_total',
				'calculate_discounted_category_total' => 'fgf_exclude_category_subtotal_discount_amount',
				'total_minimum_value' => 'fgf_cart_subtotal_min_value',
				'total_maximum_value' => 'fgf_cart_subtotal_max_value',
				'quantity_minimum_value' => 'fgf_quantity_min_value',
				'quantity_maximum_value' => 'fgf_quantity_max_value',
				'product_count_minimum_value' => 'fgf_product_count_min_value',
				'product_count_maximum_value' => 'fgf_product_count_max_value',
				'user_filter_type' => 'fgf_user_filter_type',
				'filter_include_user_ids' => 'fgf_include_users',
				'filter_exclude_user_ids' => 'fgf_exclude_users',
				'filter_include_user_roles' => 'fgf_include_user_roles',
				'filter_exclude_user_roles' => 'fgf_exclude_user_roles',
				'product_filter_type' => 'fgf_product_filter_type',
				'filter_include_product_ids' => 'fgf_include_products',
				'filter_include_product_count' => 'fgf_include_product_count',
				'filter_exclude_product_ids' => 'fgf_exclude_products',
				'filter_applicable_product_mode' => 'fgf_applicable_products_type',
				'filter_applicable_category_mode' => 'fgf_applicable_categories_type',
				'filter_include_category_ids' => 'fgf_include_categories',
				'filter_include_category_product_count' => 'fgf_include_category_product_count',
				'filter_exclude_category_ids' => 'fgf_exclude_categories',
				'filter_virtual_product' => 'fgf_virtual_product_restriction',
				'show_notice' => 'fgf_show_notice',
				'notice' => 'fgf_notice',
				'notice_image_id' => 'fgf_notice_image_id',
				'show_bogo_eligible_notice' => 'fgf_show_bogo_eligible_notice',
				'bogo_eligible_notice' => 'fgf_bogo_eligible_notice',
				'show_bulk_eligible_notice' => 'fgf_show_bulk_eligible_notice',
				'bulk_eligible_notice' => 'fgf_bulk_eligible_notice',
				'bulk_notice_image_id' => 'fgf_bulk_notice_image_id',
			);
		}

		/**
		 * Validate request.
		 * 
		 * @since 11.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error
		 */
		public function validate_request( $request ) {
			$schema = $this->get_item_schema();
			$data_keys = array_keys(array_filter($schema['properties'], array( $this, 'filter_writable_props' )));
		}

		/**
		 * Prepare item for database.
		 * 
		 * @since 11.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error|int
		 */
		public function prepare_item_for_database( $request ) {
			$parsed_data = array();
			$id = isset($request['id']) ? $request['id'] : '';
			$object = $this->get_object($id);
			$schema = $this->get_item_schema();
			$meta_keys = $this->get_meta_keys();
			$data_keys = array_keys(array_filter($schema['properties'], array( $this, 'filter_writable_props' )));

			foreach ($data_keys as $key) {
				$value = $request[$key];
				if (is_null($value)) {
					continue;
				}

				$key = array_key_exists($key, $meta_keys) ? $meta_keys[$key] : $key;
				$parsed_data[$key] = $value;
			}

			$object->set_props($parsed_data);

			return $object;
		}

		/**
		 * Prepare a single item output for response.
		 *
		 * @since 9.0.0
		 * @param  WP_REST_Request  $request Request object.
		 * @return WP_REST_Response $response Response data.
		 */
		public function prepare_item_for_response( $rule, $request ) {
			$response = array(
				'id' => $rule->get_id(),
				'name' => $rule->get_name(),
				'status' => $rule->get_status(),
				'description' => $rule->get_description(),
				'created_date' => $rule->get_created_date(),
				'modified_date' => $rule->get_modified_date(),
				'rule_type' => $rule->get_rule_type(),
				'rule_consider_type' => $rule->get_rule_consider_type(),
				'gift_type' => $rule->get_gift_type(),
				'gift_products' => $rule->get_gift_products(),
				'gift_categories' => $rule->get_gift_categories(),
				'automatic_product_qty' => $rule->get_automatic_product_qty(),
				'bogo_gift_type' => $rule->get_bogo_gift_type(),
				'buy_product_type' => $rule->get_buy_product_type(),
				'buy_category_type' => $rule->get_buy_category_type(),
				'buy_product' => $rule->get_buy_product(),
				'buy_categories' => $rule->get_buy_categories(),
				'buy_product_consider_type' => $rule->get_buy_product_consider_type(),
				'buy_category_consider_type' => $rule->get_buy_category_consider_type(),
				'buy_product_quantity_consider_type' => $rule->get_buy_product_quantity_consider_type(),
				'get_product_type' => $rule->get_product_type(),
				'get_products' => $rule->get_products(),
				'get_categories' => $rule->get_categories(),
				'buy_quantity_type' => $rule->get_buy_quantity_type(),
				'bogo_get_gift_type' => $rule->get_bogo_get_gift_type(),
				'buy_product_count' => $rule->get_buy_product_count(),
				'get_product_count' => $rule->get_product_count(),
				'bogo_gift_repeat' => $rule->get_bogo_gift_repeat(),
				'bogo_gift_repeat_mode' => $rule->get_bogo_gift_repeat_mode(),
				'bogo_gift_repeat_limit' => $rule->get_bogo_gift_repeat_limit(),
				'apply_coupon' => $rule->get_apply_coupon(),
				'coupon_gift_products' => $rule->get_coupon_gift_products(),
				'coupon_gift_products_qty' => $rule->get_coupon_gift_products_qty(),
				'total_price_type' => $rule->get_subtotal_price_type(),
				'total_price' => $rule->get_subtotal_price(),
				'subtotal_gift_type' => $rule->get_subtotal_gift_type(),
				'subtotal_gift_product_ids' => $rule->get_subtotal_gift_products(),
				'subtotal_gift_category_ids' => $rule->get_subtotal_gift_categories(),
				'subtotal_gift_products_qty' => $rule->get_subtotal_gift_products_qty(),
				'subtotal_repeat_gift' => $rule->get_subtotal_repeat_gift(),
				'subtotal_repeat_gift_mode' => $rule->get_subtotal_repeat_gift_mode(),
				'subtotal_repeat_gift_limit' => $rule->get_subtotal_repeat_gift_limit(),
				'rule_valid_from_date' => $rule->get_parsed_from_date(),
				'rule_valid_to_date' => $rule->get_parsed_to_date(),
				'rule_week_days_validation' => $rule->get_rule_week_days_validation(),
				'rule_gifts_count_per_order' => $rule->get_rule_gifts_count_per_order(),
				'rule_usage_count' => $rule->get_rule_usage_count(),
				'rule_restriction_count' => $rule->get_rule_restriction_count(),
				'rule_allowed_user_type' => $rule->get_rule_allowed_user_type(),
				'rule_allowed_user_count' => $rule->get_rule_allowed_user_count(),
				'rule_allowed_user_usage_count' => $rule->get_rule_allowed_user_usage_count(),
				'fgf_rule_user_purchased_order_count_type' => $rule->get_rule_user_purchased_order_count_type(),
				'fgf_rule_user_purchased_order_min_count' => $rule->get_rule_user_purchased_order_min_count(),
				'fgf_rule_user_purchased_order_max_count' => $rule->get_rule_user_purchased_order_max_count(),
				'fgf_rule_restrict_by_wocommerce_coupon_type' => $rule->get_rule_restrict_by_wocommerce_coupon_type(),
				'fgf_rule_restrict_by_wocommerce_coupon' => $rule->get_rule_restrict_by_wocommerce_coupon(),
				'condition_type' => $rule->get_condition_type(),
				'total_type' => $rule->get_total_type(),
				'cart_categories' => $rule->get_cart_categories(),
				'exclude_category_subtotal_discount_amount' => $rule->get_exclude_category_subtotal_discount_amount(),
				'cart_subtotal_min_value' => $rule->get_cart_subtotal_minimum_value(),
				'cart_subtotal_max_value' => $rule->get_cart_subtotal_maximum_value(),
				'quantity_min_value' => $rule->get_quantity_minimum_value(),
				'quantity_max_value' => $rule->get_quantity_maximum_value(),
				'product_count_min_value' => $rule->get_product_count_min_value(),
				'product_count_max_value' => $rule->get_product_count_max_value(),
				'user_filter_type' => $rule->get_user_filter_type(),
				'include_users' => $rule->get_include_users(),
				'exclude_users' => $rule->get_exclude_users(),
				'include_user_roles' => $rule->get_include_user_roles(),
				'exclude_user_roles' => $rule->get_exclude_user_roles(),
				'product_filter_type' => $rule->get_product_filter_type(),
				'include_products' => $rule->get_include_products(),
				'include_product_count' => $rule->get_include_product_count(),
				'exclude_products' => $rule->get_exclude_products(),
				'applicable_products_type' => $rule->get_applicable_products_type(),
				'applicable_categories_type' => $rule->get_applicable_categories_type(),
				'include_categories' => $rule->get_include_categories(),
				'include_category_product_count' => $rule->get_include_category_product_count(),
				'exclude_categories' => $rule->get_exclude_categories(),
				'virtual_product_restriction' => $rule->get_virtual_product_restriction(),
				'show_notice' => $rule->get_show_notice(),
				'notice' => $rule->get_notice(),
				'notice_image_id' => $rule->get_notice_image_id(),
				'show_bogo_eligible_notice' => $rule->get_show_bogo_eligible_notice(),
				'bogo_eligible_notice' => $rule->get_bogo_eligible_notice(),
				'show_bulk_eligible_notice' => $rule->get_show_bulk_eligible_notice(),
				'bulk_eligible_notice' => $rule->get_bulk_eligible_notice(),
				'bulk_notice_image_id' => $rule->get_bulk_notice_image_id(),
			);

			/**
			 * This hook is used to alter the item response.
			 * 
			 * @since 9.0.0
			 */
			return apply_filters('fgf_prepared_rule_item_response', $response, $rule, $request);
		}
	}

}
