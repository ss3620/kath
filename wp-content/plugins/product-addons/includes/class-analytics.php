<?php // phpcs:ignore
/**
 * Initialization Action.
 *
 * @package PRAD
 * @since 1.0.0
 */
namespace PRAD\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * Initialization class.
 */
class Analytics {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		add_action( 'prad_update_stats_table_data', array( $this, 'update_stats_table' ), 10, 3 );
	}

	/**
	 * Creates the stats tables. Called from the activation hook in product-addons.php.
	 *
	 * @since v.1.8.3
	 *
	 * @return void
	 */
	public static function create_tables() {
		global $wpdb;
		$wpdb->hide_errors();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		self::create_stats_table();
		self::create_stats_graph_table();
	}

	/**
	 * Creates the database table used to store plugin statistics.
	 *
	 * This method is responsible for initializing the stats table structure
	 * in the WordPress database if it does not already exist.
	 *
	 * @global wpdb $wpdb WordPress database access abstraction object.
	 *
	 * @return void
	 */
	public static function create_stats_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'prad_stats_table';

		$sql = "CREATE TABLE $table_name (
            id int unsigned NOT NULL AUTO_INCREMENT,
            option_id bigint(20) unsigned NOT NULL,
            impression_count int unsigned NOT NULL DEFAULT '0',
            click_count int unsigned NOT NULL DEFAULT '0',
            add_to_cart_count int unsigned NOT NULL DEFAULT '0',
            order_count int unsigned NOT NULL DEFAULT '0',
            sales float NOT NULL DEFAULT '0',
            PRIMARY KEY  (id),
            KEY option_id_index (option_id)
        ) {$wpdb->get_charset_collate()};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Creates the database table used to store statistical graph data.
	 *
	 * This method initializes the stats graph table in the WordPress database
	 * if it does not already exist. The table is typically used to record and
	 * display time-based statistical data for the plugin.
	 *
	 * @global wpdb $wpdb WordPress database access abstraction object.
	 *
	 * @return void
	 */
	public static function create_stats_graph_table() {
		global $wpdb;

		$table_name = $wpdb->prefix . 'prad_stats_graph';

		$sql = "CREATE TABLE $table_name (
            id int unsigned NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            impression_count int unsigned NOT NULL DEFAULT '0',
            click_count int unsigned NOT NULL DEFAULT '0',
            add_to_cart_count int unsigned NOT NULL DEFAULT '0',
            order_count int unsigned NOT NULL DEFAULT '0',
            sales float NOT NULL DEFAULT '0',
            PRIMARY KEY  (id),
            KEY option_date (date)
        ) {$wpdb->get_charset_collate()};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Updates the stats table for a given option and stat type.
	 *
	 * @param int       $option_id The ID of the related option or record.
	 * @param string    $stat_type The type of statistic to update (e.g., 'views', 'clicks').
	 * @param int|float $count  The count value to update or increment.
	 *
	 * @return void
	 */
	public function update_stats_table( $option_id, $stat_type, $count ) {
		global $wpdb;

		$table_name = "{$wpdb->prefix}prad_stats_table";
		if ( $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name )
		) !== $table_name ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$this->create_stats_table();
			$this->create_stats_graph_table();
		}

		$allowed_columns = array(
			'impression_count',
			'click_count',
			'add_to_cart_count',
			'order_count',
			'sales',
		);

		if ( ! in_array( $stat_type, $allowed_columns, true ) ) {
			return;
		}

		$date      = current_time( 'Y-m-d' );
		$stat_type = esc_sql( $stat_type );

		$this->update_stats_graph( $date, $stat_type, $count );

		$existing_record = $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare(
				"SELECT id, `$stat_type` FROM `{$wpdb->prefix}prad_stats_table` WHERE option_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$option_id
			),
			ARRAY_A
		);

		if ( $existing_record ) {
			if ( 'sales' === $stat_type ) {
				$new_count = isset( $existing_record[ $stat_type ] ) ? ( floatval( $existing_record[ $stat_type ] ) + floatval( $count ? $count : 0 ) ) : floatval( $count ? $count : 0 );
			} else {
				$new_count = isset( $existing_record[ $stat_type ] ) ? $existing_record[ $stat_type ] + 1 : 1;
			}

			$wpdb->update( //phpcs:ignore
				"{$wpdb->prefix}prad_stats_table",
				array( $stat_type => $new_count ),
				array( 'id' => $existing_record['id'] ),
				array( '%f' ),
				array( '%d' )
			);
		} else {
			$new_count = 'sales' === $stat_type ? 0 : 1;

			$wpdb->insert( //phpcs:ignore
				"{$wpdb->prefix}prad_stats_table",
				array(
					'option_id' => $option_id,
					$stat_type  => $new_count,
				),
				array( '%d', '%s' )
			);
		}
	}

	/**
	 * Updates the stats graph with a new count for the given date and stat type.
	 *
	 * @param string    $datekey   The date key (e.g., '2025-08-17') used to group statistics.
	 * @param string    $stat_type The type of statistic to update (e.g., 'views', 'clicks').
	 * @param int|float $count  The count value to update or increment.
	 *
	 * @return void
	 */
	public function update_stats_graph( $datekey, $stat_type, $count ) {
		global $wpdb;

		$existing_record = $wpdb->get_row( // phpcs:ignore
			$wpdb->prepare(
				"SELECT id, `$stat_type` FROM `{$wpdb->prefix}prad_stats_graph` WHERE date = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$datekey
			),
			ARRAY_A
		);

		if ( $existing_record ) {
			if ( 'sales' === $stat_type ) {
				$new_count = isset( $existing_record[ $stat_type ] ) ? ( floatval( $existing_record[ $stat_type ] ) + floatval( $count ? $count : 0 ) ) : floatval( $count ? $count : 0 );
			} else {
				$new_count = isset( $existing_record[ $stat_type ] ) ? $existing_record[ $stat_type ] + 1 : 1;
			}

			$wpdb->update( // phpcs:ignore
				"{$wpdb->prefix}prad_stats_graph",
				array( $stat_type => $new_count ),
				array( 'id' => $existing_record['id'] ),
				array( '%f' ),
				array( '%d' )
			);
		} else {
			$new_count = 'sales' === $stat_type ? 0 : 1;

			$wpdb->insert( // phpcs:ignore
				"{$wpdb->prefix}prad_stats_graph",
				array(
					'date'     => $datekey,
					$stat_type => $new_count,
				),
				array( '%s', '%s' )
			);
		}
	}
}
