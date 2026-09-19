<?php
/**
 * Rules List Table.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('WP_List_Table')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if (!class_exists('FGF_Rules_List_Table')) {

	/**
	 * Class.
	 * */
	class FGF_Rules_List_Table extends WP_List_Table {

		/**
		 * Per page count.
		 * 
		 * @var int
		 * */
		private $perpage = 10;

		/**
		 * Database.
		 * 
		 * @var object
		 * */
		private $database;

		/**
		 * Offset.
		 * 
		 * @var int
		 * */
		private $offset;

		/**
		 * Order BY.
		 * 
		 * @var string
		 * */
		private $orderby = 'ORDER BY menu_order ASC,ID ASC';

		/**
		 * Post type.
		 * 
		 * @var string
		 * */
		private $post_type = FGF_Register_Post_Types::RULES_POSTTYPE;

		/**
		 * List Slug.
		 * 
		 * @var string
		 * */
		private $list_slug = 'fgf_rules';

		/**
		 * Base URL.
		 * 
		 * @var string
		 * */
		private $base_url;

		/**
		 * Current URL.
		 * 
		 * @var string
		 * */
		private $current_url;

		/**
		 * Constructor.
		 */
		public function __construct() {

			global $wpdb;
			$this->database = &$wpdb;

			// Prepare the required data.
			$this->base_url = fgf_get_rule_page_url();

			parent::__construct(
					array(
						'singular' => 'fgf_rule',
						'plural' => 'fgf_rules',
						'ajax' => false,
					)
			);

			// Footer content.
			add_action('admin_footer', array( $this, 'footer_content' ));
		}

		/**
		 * Prepares the list of items for displaying.
		 * */
		public function prepare_items() {
			// Prepare the current url.
			$this->current_url = add_query_arg(array( 'paged' => absint($this->get_pagenum()) ), $this->base_url);

			// Prepare the bulk actions.
			$this->process_bulk_action();

			// Prepare the perpage.
			$this->perpage = $this->get_items_per_page('fgf_rules_per_page');

			// Prepare the offset.
			$this->offset = $this->perpage * ( absint($this->get_pagenum()) - 1 );

			// Prepare the header columns.
			$this->_column_headers = array( $this->get_columns(), $this->get_hidden_columns(), $this->get_sortable_columns() );

			// Prepare the query clauses.
			$join = $this->get_query_join();
			$where = $this->get_query_where();
			$limit = $this->get_query_limit();
			$offset = $this->get_query_offset();
			$orderby = $this->get_query_orderby();

			// Prepare the all items.
			$count_items = $this->database->get_var('SELECT COUNT(DISTINCT ID) FROM ' . $this->database->posts . " AS p $join $where $orderby");

			// Prepare the current page items.
			$prepare_query = $this->database->prepare('SELECT DISTINCT ID FROM ' . $this->database->posts . " AS p $join $where $orderby LIMIT %d,%d", $offset, $limit);

			$items = $this->database->get_results($prepare_query, ARRAY_A);

			// Prepare the item object.
			$this->prepare_item_object($items);

			// Prepare the pagination arguments.
			$this->set_pagination_args(
					array(
						'total_items' => $count_items,
						'per_page' => $this->perpage,
					)
			);
		}

		/**
		 * Render the table.
		 * */
		public function render() {
			if (isset($_REQUEST['s']) && strlen(wc_clean(wp_unslash($_REQUEST['s'])))) { // @codingStandardsIgnoreLine.
				/* translators: %s: search keywords */
				echo wp_kses_post(sprintf('<span class="subtitle">' . __('Search results for &#8220;%s&#8221;', 'free-gifts-for-woocommerce') . '</span>', wc_clean(wp_unslash($_REQUEST['s']))));
			}

			// Output the table.
			$this->prepare_items();
			$this->views();
			$this->search_box(__('Search Rule', 'free-gifts-for-woocommerce'), 'fgf-rules');
			$this->display();
		}

		/**
		 * Get a list of columns.
		 * 
		 * @return array
		 * */
		public function get_columns() {
			$columns = array(
				'cb' => '<input type="checkbox" />', // Render a checkbox instead of text
				'rule_name' => __('Rule Name', 'free-gifts-for-woocommerce'),
				'description' => __('Description', 'free-gifts-for-woocommerce'),
				'status' => __('Status', 'free-gifts-for-woocommerce'),
				'validity' => __('Validity', 'free-gifts-for-woocommerce'),
				'type' => __('Type', 'free-gifts-for-woocommerce'),
				'product_category' => __('Product(s) / Categories', 'free-gifts-for-woocommerce'),
				'created_date' => __('Created Date', 'free-gifts-for-woocommerce'),
				'modified_date' => __('Last Modified Date', 'free-gifts-for-woocommerce'),
				'actions' => __('Actions', 'free-gifts-for-woocommerce'),
			);

			if (!isset($_REQUEST['post_status']) && !isset($_REQUEST['s'])) {
				$columns['sort'] = '<img src="' . esc_url(FGF_PLUGIN_URL . '/assets/images/drag-icon.png') . '" title="' . __('Sort', 'free-gifts-for-woocommerce') . '"></img>';
			}
			/**
			 * This hook is used to alter the rules columns.
			 * 
			 * @since 1.0
			 */
			return apply_filters($this->list_slug . '_get_columns', $columns);
		}

		/**
		 * Get a list of hidden columns.
		 * 
		 * @return array
		 * */
		public function get_hidden_columns() {
			/**
			 * This hook is used to alter the rules hidden columns.
			 * 
			 * @since 1.0
			 */
			return apply_filters($this->list_slug . '_hidden_columns', array());
		}

		/**
		 * Get a list of sortable columns.
		 * 
		 * @return void
		 * */
		public function get_sortable_columns() {
			/**
			 * This hook is used to alter the rules sortable columns.
			 * 
			 * @since 1.0
			 */
			return apply_filters($this->list_slug . '_sortable_columns', array(
				'rule_name' => array( 'rule_name', false ),
				'status' => array( 'status', false ),
				'created_date' => array( 'created', false ),
				'modified_date' => array( 'modified', false ),
			));
		}

		/**
		 * Message to be displayed when there are no items.
		 */
		public function no_items() {
			esc_html_e('No rule to show.', 'free-gifts-for-woocommerce');
		}

		/**
		 * Get a list of bulk actions.
		 * 
		 * @return array
		 * */
		protected function get_bulk_actions() {
			$action = array();

			$action['active'] = __('Activate', 'free-gifts-for-woocommerce');
			$action['inactive'] = __('Deactivate', 'free-gifts-for-woocommerce');
			$action['delete'] = __('Delete', 'free-gifts-for-woocommerce');
			/**
			 * This hook is used to alter the rules bulk actions.
			 * 
			 * @since 1.0
			 */
			return apply_filters($this->list_slug . '_bulk_actions', $action);
		}

		/**
		 * Display the rule type drop down.
		 * 
		 * @since 11.4.0
		 */
		private function rule_type_dropdown() {
			$rule_type = isset($_REQUEST['fgf_rule_types']) ? wc_clean(wp_unslash($_REQUEST['fgf_rule_types'])) : '';
			?>
			<select class='fgf-rule-types' name='fgf_rule_types'>
				<option value='' <?php selected($rule_type, ''); ?>><?php esc_html_e('Select a Rule Type', 'free-gifts-for-woocommerce'); ?></option>
				<?php foreach (fgf_get_rule_types() as $type_id => $type_name) : ?>
					<option value='<?php echo esc_attr($type_id); ?>' <?php selected($rule_type, $type_id); ?>><?php echo esc_html($type_name); ?></option>
				<?php endforeach; ?>
			</select>
			<?php
		}

		/**
		 * Display the table filters.
		 * 
		 * @since 11.4.0
		 * @param string $which
		 */
		protected function extra_tablenav( $which ) {
			?>
			<div class="fgf-table-filters-wrapper alignleft actions">
				<?php
				if ('top' === $which) {
					ob_start();
					$this->rule_type_dropdown();
					/**
					 * This hook used to display the extra rule table filters.
					 *
					 * @since 11.4.0
					 */
					do_action('fgf_rule_table_filters');
					$output = ob_get_clean();

					if (!empty($output)) {
						echo do_shortcode($output);
						submit_button(__('Filter', 'free-gifts-for-woocommerce'), '', 'filter_action', false, array( 'id' => 'post-query-submit' ));
					}
				}
				?>
			</div>
			<?php
			/**
			 * Fires immediately following the closing "actions" div in the tablenav for the posts
			 * list table.
			 *
			 * @since 11.4.0
			 *
			 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
			 */
			do_action('fgf_rule_extra_tablenav', $which);
		}

		/**
		 * Display the list of views available on this table.
		 * 
		 * @return array
		 * */
		public function get_views() {
			$args = array();
			$status_link = array();
			/**
			 * This hook is used to alter the rules views.
			 * 
			 * @since 1.0
			 */
			$status_link_array = apply_filters($this->list_slug . '_get_views', array_filter(array_merge(array( 'all' => __('All', 'free-gifts-for-woocommerce') ), fgf_get_rule_statuses_options())));

			foreach ($status_link_array as $status_name => $status_label) {
				$status_count = $this->get_total_item_for_status($status_name);

				if (!$status_count) {
					continue;
				}

				$args['status'] = $status_name;

				$label = $status_label . ' (' . $status_count . ')';

				$class = array( strtolower($status_name) );
				if (isset($_GET['status']) && ( sanitize_title($_GET['status']) == $status_name )) { // @codingStandardsIgnoreLine.
					$class[] = 'current';
				}

				if (!isset($_GET['status']) && 'all' == $status_name) { // @codingStandardsIgnoreLine.
					$class[] = 'current';
				}

				$status_link[$status_name] = $this->get_edit_link($args, $label, implode(' ', $class));
			}

			return $status_link;
		}

		/**
		 * Get a edit link.
		 * 
		 * @rerurn string
		 * */
		private function get_edit_link( $args, $label, $class = '' ) {
			$url = add_query_arg($args, $this->base_url);
			$class_html = '';
			if (!empty($class)) {
				$class_html = sprintf(
						' class="%s"', esc_attr($class)
				);
			}

			return sprintf(
					'<a href="%s"%s>%s</a>', esc_url($url), $class_html, $label
			);
		}

		/**
		 * Get the total item by status.
		 * 
		 * @return int
		 * */
		private function get_total_item_for_status( $status = '' ) {
			// Get the current status item ids.
			$prepare_query = $this->database->prepare('SELECT COUNT(DISTINCT ID) FROM ' . $this->database->posts . " WHERE post_type=%s and post_status IN('" . $this->format_status($status) . "')", $this->post_type);

			return $this->database->get_var($prepare_query);
		}

		/**
		 * Format the status.
		 * 
		 * @return string
		 * */
		private function format_status( $status ) {
			if ('all' == $status) {
				$statuses = fgf_get_rule_statuses();
				$status = implode("', '", $statuses);
			}

			return $status;
		}

		/**
		 * Bulk action functionality
		 * */
		public function process_bulk_action() {
			$ids = isset($_REQUEST['id']) ? wc_clean(wp_unslash(( $_REQUEST['id'] ))) : array(); // @codingStandardsIgnoreLine.
			$ids = !is_array($ids) ? explode(',', $ids) : $ids;

			if (!fgf_check_is_array($ids)) {
				return;
			}

			if (!current_user_can('edit_posts')) {
				wp_die('<p class="error">' . esc_html__('Sorry, you are not allowed to edit this item.', 'free-gifts-for-woocommerce') . '</p>');
			}

			$action = $this->current_action();

			foreach ($ids as $id) {
				switch ($action) {
					case 'delete':
						wp_delete_post($id, true);
						break;

					case 'active':
						fgf_update_rule($id, array(), array( 'post_status' => 'fgf_active' ));
						break;

					case 'inactive':
						fgf_update_rule($id, array(), array( 'post_status' => 'fgf_inactive' ));
						break;

					case 'duplicate':
						$rule = fgf_get_rule($id);
						$duplicate_id = $rule->duplicate();
						if ($duplicate_id) {
							wp_redirect(add_query_arg(array( 'action' => 'edit', 'id' => $duplicate_id ), $this->base_url));
							exit();
						}

						break;
				}
			}

			wp_safe_redirect($this->current_url);
			exit();
		}

		/**
		 * Prepare the CB column data.
		 * 
		 * @return string
		 * */
		protected function column_cb( $item ) {
			return sprintf(
					'<input type="checkbox" name="id[]" value="%s" />', $item->get_id()
			);
		}

		/**
		 * Prepare a each column data.
		 * 
		 * @return mixed
		 * */
		protected function column_default( $item, $column_name ) {

			switch ($column_name) {

				case 'rule_name':
					return '<a href="' . esc_url(
									add_query_arg(
											array(
												'action' => 'edit',
												'id' => $item->get_id(),
											), $this->base_url
									)
							) . '">' . esc_html($item->get_name()) . '</a>';
				case 'description':
					return $item->get_description();
				case 'status':
					return fgf_get_status_label($item->get_status());
				case 'type':
					return fgf_get_rule_type_name($item->get_rule_type());
				case 'validity':
					$from = !empty($item->get_parsed_from_date()) ? $item->get_formatted_from_date() : '-';
					$to = !empty($item->get_parsed_to_date()) ? $item->get_formatted_to_date() : '-';

					if ('-' === $from && '-' === $to) {
						return __('Unlimited', 'free-gifts-for-woocommerce');
					} elseif ('-' === $to) {
						$to = __('Unlimited', 'free-gifts-for-woocommerce');
					}

					return sprintf('<b>%1$s&nbsp:&nbsp&nbsp</b>%2$s<br /><b>%3$s&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp&nbsp:&nbsp&nbsp</b>%4$s', __('From', 'free-gifts-for-woocommerce'), $from, __('To', 'free-gifts-for-woocommerce'), $to);
				case 'product_category':
					return $this->render_product_category($item);
				case 'created_date':
					return $item->get_formatted_created_date();
				case 'modified_date':
					return $item->get_formatted_modified_date();
				case 'actions':
					$actions = array();
					$status_action = ( $item->get_status() == 'fgf_inactive' ) ? 'active' : 'inactive';

					$actions['edit'] = fgf_display_action('edit', $item->get_id(), $this->current_url, true);
					$actions['duplicate'] = fgf_display_action('duplicate', $item->get_id(), $this->base_url);
					$actions[$status_action] = fgf_display_action($status_action, $item->get_id(), $this->current_url);
					$actions['delete'] = fgf_display_action('delete', $item->get_id(), $this->current_url);

					return implode('', $actions);
				case 'sort':
					return '<div class = "fgf_post_sort_handle">'
							. '<img src = "' . esc_url(FGF_PLUGIN_URL . '/assets/images/drag-icon.png') . '" title="' . __('Sort', 'free-gifts-for-woocommerce') . '"></img>'
							. '<input type = "hidden" class = "fgf_rules_sortable" value = "' . $item->get_id() . '" />'
							. '</div>';
			}
		}

		/**
		 * Render the product category details.
		 * 
		 * @return string
		 */
		private function render_product_category( $item ) {
			/**
			 * This hook is used to alter the product category column content.
			 * 
			 * @since 10.8.0
			 */
			$product_category_content = apply_filters('fgf_rule_product_category_column_content', '', $item);
			if ($product_category_content) {
				return $product_category_content;
			}

			if (in_array($item->get_rule_type(), array( '13', '14' ))) {
				$price_type_options=fgf_get_rule_cheapest_gift_price_type_options();
				/* translators: %s: cheapest price mode */
				return sprintf(__('The least valuable item based on %s', 'free-gifts-for-woocommerce') , $price_type_options[$item->get_cheapest_gift_price_type()]);
			} elseif (in_array($item->get_rule_type(), array( '11', '12' ))) {
				$bulk_quantity_rules=$item->get_bulk_quantity_rules();
				if (!fgf_check_is_array($bulk_quantity_rules)) {
					return '-';
				}

				$bulk_quantity_rule=reset($bulk_quantity_rules);
				$bulk_quantity_details = '<b><u>' . __('Quantity Range', 'free-gifts-for-woocommerce') . '</b></u><br />';
				$bulk_quantity_details .= '<b>' . __('Min: ', 'free-gifts-for-woocommerce') . '</b>' . $bulk_quantity_rule['minimum'];
				$bulk_quantity_details .= '<br /><b>' . __('Max: ', 'free-gifts-for-woocommerce') . '</b>' . $bulk_quantity_rule['maximum'];
				$bulk_quantity_details .= '<br /><b>' . __('Quantity: ', 'free-gifts-for-woocommerce') . '</b>' . $bulk_quantity_rule['gift_quantity'];
				if ('2' === $bulk_quantity_rule['gift_type'] && '12'!==$item->get_rule_type()) {
					$products_details = '<b><u>' . __('Categories', 'free-gifts-for-woocommerce') . '</u></b><br />' . fgf_prepare_terms_edit_link_by_ids($bulk_quantity_rule['gift_categories']);
				} elseif ('brand' === $bulk_quantity_rule['gift_type'] && '12'!==$item->get_rule_type()) {
					$products_details = '<b><u>' . __('Brands', 'free-gifts-for-woocommerce') . '</u></b><br />' . fgf_prepare_terms_edit_link_by_ids($bulk_quantity_rule['gift_brands'], 'product_brand');
				} else {
					$products_details = '<b><u>' . __('Gift Product(s)', 'free-gifts-for-woocommerce') . '</u></b> <br />' . fgf_prepare_products_edit_link_by_ids($bulk_quantity_rule['gift_products']);
				}

				/**
				 * This hook is used to alter the bulk quantity gift products column content.
				 * 
				 * @since 12.0.0
				 */
				$products_details = apply_filters('fgf_bulk_quantity_gift_products_column_content', $products_details, $item, $bulk_quantity_rule);

				$see_more_details='';
				if (count($bulk_quantity_rules)>1) {
					$see_more_details="<br /><br /><a href='#' class='fgf-preview-rules-details' data-rule_id='" . esc_attr($item->get_id()) . "'>" . __('See More', 'free-gifts-for-woocommerce') . '</a>';
				}

				return $bulk_quantity_details . '<br /><br />' . $products_details . $see_more_details;
			} if (in_array($item->get_rule_type(), array( '09', '10' ))) {
				$bulk_pricing_rules=$item->get_bulk_pricing_rules();
				if (!fgf_check_is_array($bulk_pricing_rules)) {
					return '-';
				}

				$bulk_pricing_rule=reset($bulk_pricing_rules);
				$bulk_pricing_details = '<b><u>' . __('Price Range', 'free-gifts-for-woocommerce') . '</b></u><br />';
				$bulk_pricing_details .= '<b>' . __('Min: ', 'free-gifts-for-woocommerce') . '</b>' . fgf_price($bulk_pricing_rule['minimum'], false);
				$bulk_pricing_details .= '<br /><b>' . __('Max: ', 'free-gifts-for-woocommerce') . '</b>' . fgf_price($bulk_pricing_rule['maximum'], false);
				$bulk_pricing_details .= '<br /><b>' . __('Quantity: ', 'free-gifts-for-woocommerce') . '</b>' . $bulk_pricing_rule['gift_quantity'];
				if ('2' === $bulk_pricing_rule['gift_type'] && '10'!==$item->get_rule_type()) {
					$products_details = '<b><u>' . __('Categories', 'free-gifts-for-woocommerce') . '</u></b><br />' . fgf_prepare_terms_edit_link_by_ids($bulk_pricing_rule['gift_categories']);
				} elseif ('brand' === $bulk_pricing_rule['gift_type'] && '10'!==$item->get_rule_type()) {
					$products_details = '<b><u>' . __('Brands', 'free-gifts-for-woocommerce') . '</u></b><br />' . fgf_prepare_terms_edit_link_by_ids($bulk_pricing_rule['gift_brands'], 'product_brand');
				} else {
					$products_details = '<b><u>' . __('Gift Product(s)', 'free-gifts-for-woocommerce') . '</u></b> <br />' . fgf_prepare_products_edit_link_by_ids($bulk_pricing_rule['gift_products']);
				}

				/**
				 * This hook is used to alter the bulk pricing gift products column content.
				 * 
				 * @since 12.0.0
				 */
				$products_details = apply_filters('fgf_bulk_pricing_gift_products_column_content', $products_details, $item, $bulk_pricing_rule);

				$see_more_details='';
				if (count($bulk_pricing_rules)>1) {
					$see_more_details="</br /><br /><a href='#' class='fgf-preview-rules-details' data-rule_id='" . esc_attr($item->get_id()) . "'>" . __('See More', 'free-gifts-for-woocommerce') . '</a>';
				}

				return $bulk_pricing_details . '<br /><br />' . $products_details . $see_more_details;
			} elseif (in_array($item->get_rule_type(), array( '7', '8' ))) {
				$subtotal_details = '<b><u>' . __('Total Price', 'free-gifts-for-woocommerce') . '</b></u><br />' . fgf_price($item->get_subtotal_price(), false);
				if ('2' === $item->get_subtotal_gift_type() && '8' !== $item->get_rule_type()) {
					$products_details = '<b><u>' . __('Categories', 'free-gifts-for-woocommerce') . '</u></b><br />' . fgf_prepare_terms_edit_link_by_ids($item->get_subtotal_gift_categories());
				} else {
					$products_details = '<b><u>' . __('Gift Product(s)', 'free-gifts-for-woocommerce') . '</u></b> <br />' . fgf_prepare_products_edit_link_by_ids($item->get_subtotal_gift_products());
				}

				/**
				 * This hook is used to alter the subtotal gift products column content.
				 * 
				 * @since 11.3.0
				 */
				$products_details = apply_filters('fgf_subtotal_gift_products_column_content', $products_details, $item);

				return $subtotal_details . '<br />' . $products_details;
			} elseif (in_array($item->get_rule_type(), array( '4', '6' ))) {
				$coupon_details = '<b><u>' . __('Coupon Code to Apply', 'free-gifts-for-woocommerce') . '</b></u><br />' . $this->get_coupons_link($item->get_apply_coupon());
				$products_details = '<br /><b><u>' . __('Gift Product(s)', 'free-gifts-for-woocommerce') . '</u></b> <br />' . fgf_prepare_products_edit_link_by_ids($item->get_coupon_gift_products());

				/**
				 * This hook is used to alter the coupon gift products column content.
				 * 
				 * @since 11.3.0
				 */
				$products_details = apply_filters('fgf_coupon_gift_products_column_content', $products_details, $item);

				return $coupon_details . $products_details;
			} elseif ('3' == $item->get_rule_type() || '5' == $item->get_rule_type()) {
				$buy_products_label = __('Buy Product(s)', 'free-gifts-for-woocommerce');
				if ('2' == $item->get_buy_product_type()) {
					$buy_products_details = fgf_prepare_terms_edit_link_by_ids($item->get_buy_categories());
					$buy_products_details = __('Product(s) of', 'free-gifts-for-woocommerce') . ' ' . $buy_products_details;
				} else {
					$buy_products_details = fgf_prepare_products_edit_link_by_ids($item->get_buy_product());
				}

				/**
				 * This hook is used to alter the buy products column content.
				 * 
				 * @since 11.3.0
				 */
				$buy_products_details = apply_filters('fgf_buy_product_column_content', $buy_products_details, $item);
				$bogo_products = '<b><u>' . $buy_products_label . '</u></b><br />' . $buy_products_details;

				$get_products_label = __('Get Product(s)', 'free-gifts-for-woocommerce');
				if ('5' == $item->get_rule_type() && '2' == $item->get_product_type()) {
					$get_products_details = fgf_prepare_terms_edit_link_by_ids($item->get_categories());
					$get_products_details = __('Product(s) of', 'free-gifts-for-woocommerce') . ' ' . $get_products_details;
				} else if ('5' == $item->get_rule_type() || '2' == $item->get_bogo_gift_type()) {
					$get_products_details = fgf_prepare_products_edit_link_by_ids($item->get_products());
				} else {
					$get_products_details = $buy_products_details;
				}

				/**
				 * This hook is used to alter the get products column content.
				 * 
				 * @since 11.3.0
				 */
				$get_products_details = apply_filters('fgf_get_product_column_content', $get_products_details, $item);

				return $bogo_products . '<br ><b><u>' . $get_products_label . '</u></b><br />' . $get_products_details;
			} elseif ('2' == $item->get_gift_type() && '2' != $item->get_rule_type()) {
				return '<b><u>' . __('Categories', 'free-gifts-for-woocommerce') . '</u></b><br />' . fgf_prepare_terms_edit_link_by_ids($item->get_gift_categories());
			} else {
				return '<b><u>' . __('Product(s)', 'free-gifts-for-woocommerce') . '</u></b><br />' . fgf_prepare_products_edit_link_by_ids($item->get_gift_products());
			}
		}

		/**
		 * Coupon Link.
		 * 
		 * @return string
		 * */
		private function get_coupons_link( $coupon_ids ) {
			$coupons_link = '';

			foreach ($coupon_ids as $coupon_id) {
				$the_coupon = get_post($coupon_id);

				//Return if the coupon code does not exist.
				if (!$the_coupon) {
					continue;
				}

				$coupons_link .= '<a href = "' . esc_url(
								add_query_arg(
										array(
											'post' => $coupon_id,
											'action' => 'edit',
										), admin_url('post.php')
								)
						) . '" >' . $the_coupon->post_title . '</a>, ';
			}

			return rtrim($coupons_link, ', ');
		}

		/**
		 * Prepare the item Object.
		 * 
		 * @return void
		 * */
		private function prepare_item_object( $items ) {
			$prepare_items = array();
			if (fgf_check_is_array($items)) {
				foreach ($items as $item) {
					$prepare_items[] = fgf_get_rule($item['ID']);
				}
			}

			$this->items = $prepare_items;
		}

		/**
		 * Get the query join clauses.
		 * 
		 * @return string
		 * */
		private function get_query_join() {
			$join = '';
			if (empty($_REQUEST['orderby']) && !$this->is_custom_filters()) {
				return $join;
			}

			$join = ' INNER JOIN ' . $this->database->postmeta . ' AS pm ON ( pm.post_id = p.ID )';
			/**
			 * This hook is used to alter the rules join query fields.
			 * 
			 * @since 1.0
			 */
			return apply_filters($this->list_slug . '_query_join', $join);
		}

		/**
		 * Get the query where clauses.
		 * 
		 * @return string
		 * */
		private function get_query_where() {
			$current_status = 'all';
			if (isset($_GET['status']) && ( sanitize_title($_GET['status']) != 'all' )) {
				$current_status = sanitize_title($_GET['status']);
			}

			$where = " where post_type='" . $this->post_type . "' and post_status IN('" . $this->format_status($current_status) . "')";

			// Filters.
			$where = $this->custom_filters($where);

			// Search.
			$where = $this->custom_search($where);
			/**
			 * This hook is used to alter the rules where query fields.
			 * 
			 * @since 1.0
			 */
			return apply_filters($this->list_slug . '_query_where', $where);
		}

		/**
		 * Get the query limit clauses.
		 * 
		 * @return string
		 * */
		private function get_query_limit() {
			/**
			 * This hook is used to alter the rules limit query fields.
			 * 
			 * @since 1.0
			 */
			return apply_filters($this->list_slug . '_query_limit', $this->perpage);
		}

		/**
		 * Get the query offset clauses.
		 * 
		 * @return string
		 * */
		private function get_query_offset() {
			/**
			 * This hook is used to alter the rules offset query fields.
			 * 
			 * @since 1.0
			 */
			return apply_filters($this->list_slug . '_query_offset', $this->offset);
		}

		/**
		 * Get the query order by clauses.
		 * 
		 * @return string
		 * */
		private function get_query_orderby() {

			$order = 'DESC';
			if (!empty($_REQUEST['order']) && is_string($_REQUEST['order'])) { // @codingStandardsIgnoreLine.
				if ('ASC' === strtoupper(wc_clean(wp_unslash($_REQUEST['order'])))) { // @codingStandardsIgnoreLine.
					$order = 'ASC';
				}
			}

			// Order By.
			if (isset($_REQUEST['orderby'])) {
				switch (wc_clean(wp_unslash($_REQUEST['orderby']))) { // @codingStandardsIgnoreLine.
					case 'rule_name':
						$this->orderby = ' ORDER BY p.post_title ' . $order;
						break;
					case 'status':
						$this->orderby = ' ORDER BY p.post_status ' . $order;
						break;
					case 'created':
						$this->orderby = ' ORDER BY p.post_date ' . $order;
						break;
					case 'modified':
						$this->orderby = ' ORDER BY p.post_modified ' . $order;
						break;
				}
			}
			/**
			 * This hook is used to alter the rules order by query fields.
			 * 
			 * @since 1.0
			 */
			return apply_filters($this->list_slug . '_query_orderby', $this->orderby);
		}

		/**
		 * Is custom filters?
		 * 
		 * @since 11.4.0
		 * @return boolean
		 */
		private function is_custom_filters() {
			$rule_type = isset($_REQUEST['fgf_rule_types']) ? wc_clean(wp_unslash($_REQUEST['fgf_rule_types'])) : '';

			return $rule_type ? true : false;
		}

		/**
		 * Custom filters.
		 * 
		 * @since 11.4.0
		 * @param string $where
		 * @retrun string
		 * */
		private function custom_filters( $where ) {
			$rule_type = isset($_REQUEST['fgf_rule_types']) ? wc_clean(wp_unslash($_REQUEST['fgf_rule_types'])) : '';
			if ($rule_type) {
				$where .= " AND pm.meta_key='fgf_rule_type' AND pm.meta_value='{$rule_type}'";
			}

			return $where;
		}

		/**
		 * Custom Search.
		 * 
		 * @retrun string
		 * */
		private function custom_search( $where ) {

			if (!isset($_REQUEST['s'])) { // @codingStandardsIgnoreLine.
				return $where;
			}

			$post_ids = array();
			$terms = explode(', ', wc_clean(wp_unslash($_REQUEST['s']))); // @codingStandardsIgnoreLine.

			foreach ($terms as $term) {
				$term = $this->database->esc_like(( $term ));
				$post_query = new FGF_Query($this->database->prefix . 'posts', 'p');
				$post_query->select('DISTINCT `p`.ID')
						->leftJoin($this->database->prefix . 'postmeta', 'pm', '`p`.`ID` = `pm`.`post_id`')
						->where('`p`.post_type', $this->post_type)
						->whereIn('`p`.post_status', fgf_get_rule_statuses())
						->whereLike('`p`.post_title', '%' . $term . '%');

				$post_ids = $post_query->fetchCol('ID');
			}

			$post_ids = fgf_check_is_array($post_ids) ? $post_ids : array( 0 );
			$where .= ' AND (id IN (' . implode(', ', $post_ids) . '))';

			return $where;
		}

		/**
		 * Display the footer contents.
		 * 
		 * @since 12.0.0
		 */
		public function footer_content() {
			echo '<script type="text/template" id="tmpl-fgf-modal-preview-rules-details">{{{data}}}</script>';
		}
	}

}
