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
		register_activation_hook( PRAD_PATH . 'product-addons.php', array( $this, 'plugin_activation_hook' ) );
		add_action( 'prad_update_stats_table_data', array( $this, 'update_stats_table' ), 10, 3 );
	}

	/**
	 * Redirect After Active Plugin
	 *
	 * @since v.1.0.0
	 *
	 * @param string $plugin Plugin name.
	 *
	 * @return void
	 */
	public function plugin_activation_hook( $plugin ) { // phpcs:ignore
		global $wpdb;
		$wpdb->hide_errors();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$this->create_stats_table();
		$this->create_stats_graph_table();
		$this->handle_default_option_creations();
	}

	/**
	 * Handles the creation of default options for the plugin.
	 *
	 * Checks whether the default options have already been created by verifying
	 * the `prad_addons_default_option_created` option. If the option exists,
	 * the function returns early and no further action is taken.
	 *
	 * @return void
	 */
	public function handle_default_option_creations() {
		$exists = get_option( 'prad_addons_default_option_created', false );
		if ( $exists ) {
			return;
		}
		$dummy_content = array(
			array(
				'title'   => 'Delicious HandMade Pizza',
				'content' => array(
					array(
						'type'          => 'checkbox',
						'blockid'       => 'm0tx-pwu08v',
						'label'         => 'Toppings',
						'desc'          => '',
						'columns'       => '2',
						'enableCount'   => false,
						'hide'          => false,
						'required'      => false,
						'min'           => 1,
						'max'           => 100,
						'minSelect'     => '',
						'maxSelect'     => '',
						'pricePosition' => 'with_option',
						'_options'      => array(
							array(
								'value'   => 'Olives',
								'type'    => 'fixed',
								'regular' => '2',
								'sale'    => '',
							),
							array(
								'value'   => 'Mushrooms',
								'type'    => 'fixed',
								'regular' => '2',
								'sale'    => '',
							),
							array(
								'value'   => 'Pepperoni',
								'type'    => 'fixed',
								'regular' => '2',
								'sale'    => '',
							),
							array(
								'value'   => 'Chicken',
								'type'    => 'fixed',
								'regular' => '3',
								'sale'    => '10',
							),
						),
						'class'         => '',
						'id'            => '',
						'defval'        => array( 3, 2 ),
					),
					array(
						'type'          => 'checkbox',
						'blockid'       => 'mbt7-iizxj3',
						'label'         => 'Dipping Sauce Add-ons',
						'desc'          => '',
						'columns'       => '1',
						'enableCount'   => true,
						'hide'          => false,
						'required'      => false,
						'min'           => 1,
						'max'           => 100,
						'minSelect'     => '',
						'maxSelect'     => '',
						'pricePosition' => 'with_option',
						'_options'      => array(
							array(
								'value'   => 'Tomato Sauce',
								'type'    => 'no_cost',
								'regular' => '',
								'sale'    => '',
							),
							array(
								'value'   => 'Spicy Marinara',
								'type'    => 'per_unit',
								'regular' => '1',
								'sale'    => '',
							),
							array(
								'value'   => 'Ranch',
								'type'    => 'per_unit',
								'regular' => '2',
								'sale'    => '',
							),
						),
						'class'         => '',
						'id'            => '',
						'defval'        => array( 1, 2 ),
					),
					array(
						'type'     => 'button',
						'blockid'  => 'mcw4-d1i4ws',
						'label'    => 'Spice Level',
						'desc'     => '',
						'multiple' => false,
						'hide'     => false,
						'required' => false,
						'vertical' => false,
						'_options' => array(
							array(
								'value'   => 'Regular',
								'type'    => 'no_cost',
								'regular' => '8',
								'sale'    => '',
							),
							array(
								'value'   => 'Medium',
								'type'    => 'no_cost',
								'regular' => '6',
								'sale'    => '',
							),
							array(
								'value'   => 'Extreme',
								'type'    => 'no_cost',
								'regular' => '3',
								'sale'    => '',
							),
						),
						'class'    => '',
						'id'       => '',
						'defval'   => array( 0 ),
					),
					array(
						'type'     => 'range',
						'blockid'  => 'm9uz-kt4i55',
						'label'    => 'Crust Thickness',
						'_options' => array(
							array(
								'type'    => 'no_cost',
								'regular' => '2',
								'sale'    => '',
							),
						),
						'min'      => 1,
						'max'      => '20',
						'value'    => '3',
						'step'     => 1,
						'class'    => '',
						'id'       => '',
						'hide'     => false,
						'required' => false,
					),
				),

			),
			array(
				'title'   => 'Buttercream Bluff Cake',
				'content' => array(
					array(
						'type'     => 'button',
						'blockid'  => 'mj3f-z9ggz4',
						'label'    => 'Cake Size',
						'desc'     => '',
						'multiple' => false,
						'hide'     => false,
						'required' => false,
						'vertical' => false,
						'_options' => array(
							array(
								'value'   => '1 pound',
								'type'    => 'fixed',
								'regular' => '26',
								'sale'    => '',
							),
							array(
								'value'   => '3 pound',
								'type'    => 'fixed',
								'regular' => '78',
								'sale'    => '70',
							),
							array(
								'value'   => '5 pound',
								'type'    => 'fixed',
								'regular' => '130',
								'sale'    => '',
							),
						),
						'class'    => '',
						'id'       => '',
						'defval'   => array( 0 ),
					),
					array(
						'type'            => 'radio',
						'blockid'         => 'mc2a-nctpsf',
						'label'           => 'Choose Flavor',
						'desc'            => '',
						'columns'         => '1',
						'enableCount'     => false,
						'hide'            => false,
						'required'        => false,
						'min'             => 1,
						'max'             => 100,
						'pricePosition'   => 'with_option',
						'_options'        => array(
							array(
								'value'   => 'Strawberry',
								'type'    => 'no_cost',
								'regular' => '',
								'sale'    => '',
								'def'     => false,
							),
							array(
								'value'   => 'Avocado',
								'type'    => 'no_cost',
								'regular' => '6',
								'sale'    => '',
								'def'     => false,
							),
							array(
								'value'   => 'Blueberry',
								'type'    => 'fixed',
								'regular' => '12',
								'sale'    => '',
							),
							array(
								'value'   => 'Chocolate',
								'type'    => 'fixed',
								'regular' => '10',
								'sale'    => '8',
								'def'     => false,
							),
						),
						'en_logic'        => false,
						'fieldConditions' => array(),
					),
					array(
						'type'     => 'button',
						'blockid'  => 'm60j-i43lv3',
						'label'    => 'Cake Tiers',
						'desc'     => '',
						'multiple' => false,
						'hide'     => false,
						'required' => false,
						'vertical' => false,
						'_options' => array(
							array(
								'value'   => 'Single',
								'type'    => 'no_cost',
								'regular' => '8',
								'sale'    => '',
							),
							array(
								'value'   => 'Two - Tier',
								'type'    => 'fixed',
								'regular' => '6',
								'sale'    => '',
							),
							array(
								'value'   => 'Three - Tier',
								'type'    => 'fixed',
								'regular' => '8',
								'sale'    => '',
							),
						),
						'class'    => '',
						'id'       => '',
						'defval'   => array( 0 ),
					),
					array(
						'type'          => 'textfield',
						'blockid'       => 'mbg8-joml77',
						'label'         => 'Message on Cake',
						'placeholder'   => 'Happy Birthday',
						'pricePosition' => 'with_title',
						'_options'      => array(
							array(
								'type'    => 'no_cost',
								'regular' => '2',
								'sale'    => '',
							),
						),
						'class'         => '',
						'id'            => '',
						'hide'          => false,
						'required'      => false,
					),
				),
			),
		);

		foreach ( $dummy_content as $addon ) {
			$attr = array(
				'post_title'   => $addon['title'],
				'post_status'  => 'draft',
				'post_content' => $addon['title'],
				'post_type'    => 'prad_option',
			);
			$id   = wp_insert_post( $attr );
			if ( $id ) {
				update_post_meta( $id, 'prad_addons_blocks', $addon['content'] );
			}
		}

		update_option( 'prad_addons_default_option_created', true );
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
	public function create_stats_table() {
		global $wpdb;

		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}prad_stats_table` (
            `id` INT unsigned NOT NULL AUTO_INCREMENT,
            `option_id` bigint(20) unsigned NOT NULL,
            `impression_count` INT unsigned NOT NULL default '0',
            `click_count` INT unsigned NOT NULL default '0',
            `add_to_cart_count` INT unsigned NOT NULL default '0',
            `order_count` INT unsigned NOT NULL default '0',
            `sales` FLOAT NOT NULL default '0',
            PRIMARY KEY (id),
            KEY option_id_index (option_id)
        ) {$wpdb->get_charset_collate()};";

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
	public function create_stats_graph_table() {
		global $wpdb;

		$sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}prad_stats_graph` (
            `id` INT unsigned NOT NULL AUTO_INCREMENT,
            `date` date NOT NULL,
            `impression_count` INT unsigned NOT NULL default '0',
            `click_count` INT unsigned NOT NULL default '0',
            `add_to_cart_count` INT unsigned NOT NULL default '0',
            `order_count` INT unsigned NOT NULL default '0',
            `sales` FLOAT NOT NULL default '0',
            PRIMARY KEY (id),
            KEY option_date (date)
        ) {$wpdb->get_charset_collate()};";

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
