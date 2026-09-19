<?php
/**
 * Master Log List Table.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('WP_List_Table')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php' ;
}

if (!class_exists('FGF_Master_Log_List_Table')) {

	/**
	 * Class.
	 * */
	class FGF_Master_Log_List_Table extends WP_List_Table {

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
		private $orderby = 'ORDER BY ID DESC';

		/**
		 * Post type.
		 * 
		 * @var string
		 * */
		private $post_type = FGF_Register_Post_Types::MASTER_LOG_POSTTYPE;

		/**
		 * List Slug.
		 * 
		 * @var string
		 * */
		private $list_slug = 'fgf_master_log';

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
			$this->base_url = fgf_get_master_log_page_url();

			parent::__construct(
					array(
						'singular' => 'master_log',
						'plural' => 'master_logs',
						'ajax' => false,
					)
			);
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
			$this->perpage = $this->get_items_per_page('fgf_master_logs_per_page');

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
			$this->search_box(__('Search Master Log', 'free-gifts-for-woocommerce'), 'fgf-master-log');
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
				'order_id' => __('Order Id', 'free-gifts-for-woocommerce'),
				'user_details' => __('User Details', 'free-gifts-for-woocommerce'),
				'status' => __('Status', 'free-gifts-for-woocommerce'),
				'date' => __('Created Date', 'free-gifts-for-woocommerce'),
				'actions' => __('Preview', 'free-gifts-for-woocommerce'),
			);
			/**
			 * This hook is used to alter the master log columns.
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
			 * This hook is used to alter the master log hidden columns.
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
			 * This hook is used to alter the master log sortable columns.
			 * 
			 * @since 1.0
			 */
			return apply_filters($this->list_slug . '_sortable_columns', array(
				'order_id' => array( 'order_id', false ),
				'user_details' => array( 'user_details', false ),
				'status' => array( 'status', false ),
				'date' => array( 'date', false ),
			));
		}

		/**
		 * Message to be displayed when there are no items.
		 */
		public function no_items() {
			esc_html_e('No master log to show.', 'free-gifts-for-woocommerce');
		}

		/**
		 * Get a list of bulk actions.
		 * 
		 * @return array
		 * */
		protected function get_bulk_actions() {
			$action = array();
			$action['delete'] = __('Delete', 'free-gifts-for-woocommerce');
			/**
			 * This hook is used to alter the master log bulk actions.
			 * 
			 * @since 1.0
			 */
			return apply_filters($this->list_slug . '_bulk_actions', $action);
		}

		/**
		 * Get the name of the primary column.
		 * 
		 * @rerurn string
		 * */
		protected function get_primary_column_name() {
			return 'order_id';
		}

		/**
		 * Generates and displays row action links.
		 * 
		 * @rerurn string
		 * */
		protected function handle_row_actions( $item, $column_name, $primary ) {
			if ($column_name != $primary) {
				return '';
			}

			return $this->row_actions($this->prepare_row_actions($item));
		}

		/**
		 * Prepare the list of row action links.
		 * 
		 * @rerurn array
		 * */
		protected function prepare_row_actions( $item ) {
			$actions = array();

			$actions['delete'] = fgf_display_action('delete', $item->get_id(), $this->current_url);

			return $actions;
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
			 * This hook is used to alter the master log views.
			 * 
			 * @since 1.0
			 */
			$status_link_array = apply_filters($this->list_slug . '_get_views', array(
				'all' => __('All', 'free-gifts-for-woocommerce'),
				'fgf_automatic' => __('Automatic', 'free-gifts-for-woocommerce'),
				'fgf_manual' => __('Manual', 'free-gifts-for-woocommerce'),
					)
			);

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
				$statuses = fgf_get_master_log_statuses();
				$status = implode("', '", $statuses);
			}

			return $status;
		}

		/**
		 * Bulk action functionality.
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
				if ('delete' === $action) {
					wp_delete_post($id, true);
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
				case 'order_id':
					return '<a href="' . esc_url(
									add_query_arg(
											array(
												'post' => $item->get_order_id(),
												'action' => 'edit',
											), admin_url('post.php')
									)
							) . '" >#' . $item->get_order_id() . '</a>';
					break;
				case 'user_details':
					return $item->get_customer_name();
					break;
				case 'status':
					return fgf_get_status_label($item->get_status());
					break;
				case 'date':
					return $item->get_formatted_created_date();
					break;
				case 'actions':
					return $this->view_more($item->get_id());
					break;
			}
		}

		/**
		 * View more.
		 * */
		private function view_more( $log_id ) {
			?>
			<a href="#" class="button fgf_master_log_info" data-fgf_master_log_id="<?php echo esc_attr($log_id); ?>" title="<?php esc_html_e('Preview', 'free-gifts-for-woocommerce'); ?>">
				<img src="<?php echo esc_url(FGF_PLUGIN_URL . '/assets/images/view.png'); ?>">
			</a>
			<?php
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
					$prepare_items[] = fgf_get_master_log($item['ID']);
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
			if (empty($_REQUEST['orderby'])) { // @codingStandardsIgnoreLine.
				return $join;
			}

			$join = ' INNER JOIN ' . $this->database->postmeta . ' AS pm ON ( pm.post_id = p.ID )';
			/**
			 * This hook is used to alter the master log join query fields.
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

			// Search.
			$where = $this->custom_search($where);
			/**
			 * This hook is used to alter the master log where query fields.
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
			 * This hook is used to alter the master log limit query fields.
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
			 * This hook is used to alter the master log offset query fields.
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
					case 'order_id':
						$this->orderby = " AND pm.meta_key='fgf_order_id' ORDER BY pm.meta_value " . $order;
						break;
					case 'user_details':
						$this->orderby = " AND pm.meta_key='fgf_user_name' ORDER BY pm.meta_value  " . $order;
						break;
					case 'status':
						$this->orderby = ' ORDER BY p.post_status ' . $order;
						break;
					case 'date':
						$this->orderby = ' ORDER BY p.post_date ' . $order;
						break;
				}
			}
			/**
			 * This hook is used to alter the master log order by query fields.
			 * 
			 * @since 1.0
			 */
			return apply_filters($this->list_slug . '_query_orderby', $this->orderby);
		}

		/**
		 * Custom Search.
		 * 
		 * @retrun string
		 * */
		public function custom_search( $where ) {

			if (!isset($_REQUEST['s'])) { // @codingStandardsIgnoreLine.
				return $where;
			}

			$post_ids = array();
			$terms = explode(' , ', wc_clean(wp_unslash($_REQUEST['s']))); // @codingStandardsIgnoreLine.

			foreach ($terms as $term) {
				$term = $this->database->esc_like(( $term ));
				$post_query = new FGF_Query($this->database->prefix . 'posts', 'p');
				$post_query->select('DISTINCT `p`.ID')
						->leftJoin($this->database->prefix . 'postmeta', 'pm', '`p`.`ID` = `pm`.`post_id`')
						->where('`p`.post_type', $this->post_type)
						->whereIn('`p`.post_status', fgf_get_master_log_statuses())
						->whereIn('`pm`.meta_key', array( 'fgf_user_name', 'fgf_user_email', 'fgf_order_id' ))
						->whereLike('`pm`.meta_value', '%' . $term . '%');

				$post_ids = $post_query->fetchCol('ID');
			}

			$post_ids = fgf_check_is_array($post_ids) ? $post_ids : array( 0 );
			$where .= ' AND (id IN (' . implode(' , ', $post_ids) . '))';

			return $where;
		}
	}

}
