<?php
// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Flush logs action (nonce + capability + safe SQL + cache invalidation)
 * Use priority 5 to run early in admin_init
 */
add_action( 'admin_init', 'ossm_handle_flush_logs_action', 5 );

function ossm_handle_flush_logs_action() {

	// Clean up any &amp; entities in REQUEST keys (caused by HTML entity encoding)
	$cleaned_request = array();
	foreach ( $_REQUEST as $key => $val ) {
		$clean_key = str_replace( 'amp;', '', $key );
		$cleaned_request[ $clean_key ] = $val;
	}
	$_REQUEST = array_merge( $_REQUEST, $cleaned_request );

	// Only process if we're on the sendle_logs page
	$page = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';
	if ( 'sendle_logs' !== $page ) {
		return;
	}

	// Only process if logs parameter is set to 'flush'
	if ( ! isset( $_REQUEST['logs'] ) || 'flush' !== sanitize_text_field( wp_unslash( $_REQUEST['logs'] ) ) ) {
		return;
	}

	// Check capability
	if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Unauthorized', 'official-sendle-shipping-method' ), '', array( 'response' => 403 ) );
	}

	// Verify nonce (must be present in the flush link)
	$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ossm_flush_logs' ) ) {
		wp_die( esc_html__( 'Invalid nonce', 'official-sendle-shipping-method' ), '', array( 'response' => 403 ) );
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'sendlelogs';

	// TRUNCATE cannot be prepared with placeholders; the identifier is built from trusted $wpdb->prefix.
	$wpdb->query( 'TRUNCATE TABLE ' . esc_sql( $table_name ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

	// Clear object cache completely for this group
	$cache_group = 'ossm_sendle_logs';
	$ver_key     = 'ossm_sendle_logs_ver';
	wp_cache_delete( $ver_key, $cache_group );
	
	// Force cache version increment
	$ver = (int) wp_cache_get( $ver_key, $cache_group );
	wp_cache_set( $ver_key, $ver + 1, $cache_group );

	// Redirect safely (no JS redirect) with timestamp to prevent caching
	wp_safe_redirect(
		add_query_arg(
			array(
				'page' => 'sendle_logs',
				'logs' => 'success',
				't'    => time(), // Cache buster
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}

/**
 * Register the admin menu for Sendle Logs
 */
add_action( 'admin_menu', 'ossm_register_sendle_logs_menu' );

function ossm_register_sendle_logs_menu() {
	$sendle_setting = maybe_unserialize( get_option( 'woocommerce_ossmsendle_settings' ) );

	if ( isset( $sendle_setting['enable_log'] ) && 'yes' === $sendle_setting['enable_log'] ) {

		/**
		 * IMPORTANT: ossm_getAssignRole() must return a CAPABILITY, not a role name.
		 * Fallback to manage_options to avoid "Sorry, you are not allowed..."
		 */
		$capability = function_exists( 'ossm_getAssignRole' ) ? ossm_getAssignRole() : 'manage_options';
		if ( empty( $capability ) || ! is_string( $capability ) ) {
			$capability = 'manage_options';
		}

		add_submenu_page(
			'woocommerce',
			esc_attr__( 'Sendle Logs Table', 'official-sendle-shipping-method' ),
			esc_html__( 'Sendle Logs', 'official-sendle-shipping-method' ),
			$capability,
			'sendle_logs',
			'ossm_render_sendle_logs_page',
			4
		);
	}
}

/**
 * Render the Sendle Logs page
 */
function ossm_render_sendle_logs_page() {
	$logs_table = new Sendle_Logs_Table();
	$logs_table->prepare_items();

	$logs_flag = isset( $_GET['logs'] ) ? sanitize_text_field( wp_unslash( $_GET['logs'] ) ) : '';
	?>
	<div class="wrap">
		<?php if ( 'success' === $logs_flag ) : ?>
			<p><?php echo esc_html__( 'All Logs have been deleted successfully.', 'official-sendle-shipping-method' ); ?></p>
		<?php endif; ?>

		<button class="flush" id="flush" type="button" onclick="flush_logs();">
			<?php echo esc_html__( 'Flush Logs', 'official-sendle-shipping-method' ); ?>
		</button>
	</div>

	<div class="wrap">
		<h2><?php echo esc_html__( 'All Sendle Logs', 'official-sendle-shipping-method' ); ?></h2>
		<?php $logs_table->display(); ?>
	</div>

	<script>
	function flush_logs() {
		if (confirm(<?php echo wp_json_encode( __( 'Are you sure you want to delete all sendle logs?', 'official-sendle-shipping-method' ) ); ?>)) {
			var flushUrl = <?php 
				$flush_url = add_query_arg(
					array(
						'page' => 'sendle_logs',
						'logs' => 'flush',
					),
					admin_url( 'admin.php' )
				);
				$flush_url = wp_nonce_url( $flush_url, 'ossm_flush_logs' );
				// Remove HTML entities for JavaScript
				$flush_url = html_entity_decode( $flush_url, ENT_QUOTES, 'UTF-8' );
				echo wp_json_encode( $flush_url ); 
			?>;
			window.location.href = flushUrl;
		}
	}
	</script>
	<?php
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'Sendle_Logs_Table' ) ) {

	class Sendle_Logs_Table extends WP_List_Table {

		protected $cache_group = 'ossm_sendle_logs';

		public function __construct() {
			parent::__construct( array( 'ajax' => false ) );
		}

		public function get_columns() {
			return array(
				'id'        => 'ID',
				'logs'      => 'Logs',
				'timestamp' => 'Time',
			);
		}

		public function column_default( $item, $column_name ) {
			switch ( $column_name ) {
				case 'id':
					return esc_html( (string) $item['id'] );
				case 'logs':
					return esc_html( (string) $item['logs'] );
				case 'timestamp':
					return esc_html( (string) $item['timestamp'] );
				default:
					return '';
			}
		}

		protected function get_cache_version() {
			$ver_key = 'ossm_sendle_logs_ver';
			$ver     = wp_cache_get( $ver_key, $this->cache_group );
			if ( false === $ver ) {
				$ver = 1;
				wp_cache_set( $ver_key, $ver, $this->cache_group );
			}
			return (int) $ver;
		}

		protected function build_cache_key( $suffix, array $parts ) {
			$ver = $this->get_cache_version();
			return 'v' . $ver . ':' . $suffix . ':' . md5( wp_json_encode( $parts ) );
		}

		public function prepare_items() {
			global $wpdb;

			$table_name   = $wpdb->prefix . 'sendlelogs';
			$per_page     = 50;
			$current_page = (int) $this->get_pagenum();
			$offset       = ( $current_page > 1 ) ? $per_page * ( $current_page - 1 ) : 0;

			$srcCon = '';

			$items_cache_key = $this->build_cache_key(
				'items',
				array(
					'table'  => $table_name,
					'per'    => $per_page,
					'offset' => $offset,
					'srcCon' => $srcCon,
				)
			);

			$items = wp_cache_get( $items_cache_key, $this->cache_group );

			if ( false === $items ) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$items = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id, logs, timestamp
						 FROM {$table_name}
						 WHERE 1=1 {$srcCon}
						 ORDER BY id DESC
						 LIMIT %d OFFSET %d",
						$per_page,
						$offset
					),
					ARRAY_A
				);

				wp_cache_set( $items_cache_key, $items, $this->cache_group, 60 );
			}

			$this->_column_headers = array( $this->get_columns() );
			$this->items           = is_array( $items ) ? $items : array();

			$count_cache_key = $this->build_cache_key(
				'count',
				array(
					'table'  => $table_name,
					'srcCon' => $srcCon,
				)
			);

			$count = wp_cache_get( $count_cache_key, $this->cache_group );

			if ( false === $count ) {
				$count = (int) $wpdb->get_var(
					"SELECT COUNT(id) FROM {$table_name} WHERE 1=1 {$srcCon}"
				); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

				wp_cache_set( $count_cache_key, $count, $this->cache_group, 60 );
			}

			$this->set_pagination_args(
				array(
					'total_items' => (int) $count,
					'per_page'    => $per_page,
					'total_pages' => ( $per_page > 0 ) ? (int) ceil( (int) $count / $per_page ) : 0,
				)
			);
		}
	}
}