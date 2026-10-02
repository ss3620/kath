<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Installation related functions and actions.
 *
 * @package Revenue
 * @version 1.0.0
 */

namespace Revenue;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Installation class
 */
class Revenue_Install {

	/** Database schema revision, independent of the plugin release version. */
	const SCHEMA_VERSION = '1.0.0';

	/** Upgrade existing sites without requiring plugin reactivation. */
	public function maybe_upgrade() {
		if ( self::SCHEMA_VERSION !== get_option( 'revenue_db_version' ) ) {
			$this->create_tables();
		}
	}


	/**
	 * Perform Installation
	 *
	 * @return void
	 */
	public function install() {
		// Set installation time if user install it first time.
		$this->set_installed_time();

		// Create Required Tables.
		$this->create_tables();
	}

	/**
	 * Set installed time
	 *
	 * @return void
	 */
	public function set_installed_time() {
		if ( empty( get_option( 'revenue_installed_time' ) ) ) {
			update_option( 'revenue_installed_time', time() );
			set_transient( '_revenue_activation_redirect', 1, 30 );
		}
	}

	/**
	 * Create or upgrade required tables and record only verified schemas.
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether every table matches the required schema.
	 */
	public function create_tables() {
		global $wpdb;

		$show_errors = $wpdb->hide_errors();

		try {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$results = array(
				$this->create_campaigns_table(),
				$this->create_campaign_meta_table(),
				$this->create_campaign_triggers_table(),
				$this->create_campaign_analytics_table(),
			);

			if ( in_array( false, $results, true ) ) {
				return false;
			}

			update_option( 'revenue_db_version', self::SCHEMA_VERSION );
			return true;
		} finally {
			$wpdb->show_errors( $show_errors );
		}
	}


	/**
	 * Create campaigns table if not exist
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the table matches its required schema.
	 */
	public function create_campaigns_table() {
		global $wpdb;

		$sql = "CREATE TABLE {$wpdb->prefix}revenue_campaigns (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_name text NOT NULL,
            campaign_author bigint(20) unsigned NOT NULL default '0',
            date_created datetime NOT NULL default '0000-00-00 00:00:00',
            date_created_gmt datetime NOT NULL default '0000-00-00 00:00:00',
            date_modified datetime NOT NULL default '0000-00-00 00:00:00',
            date_modified_gmt datetime NOT NULL default '0000-00-00 00:00:00',
            campaign_status varchar(50) NOT NULL default 'publish',
            campaign_type varchar(50) NOT NULL,
            campaign_placement varchar(50) NOT NULL,
            campaign_behavior varchar(50) NOT NULL default 'cross_sell',
            campaign_recommendation varchar(50) NOT NULL default 'manual',
            campaign_inpage_position varchar(50),
            campaign_display_style varchar(50),
            campaign_trigger_type varchar(50),
            campaign_trigger_relation varchar(50),
            campaign_start_date_time datetime,
            campaign_end_date_time datetime,
            PRIMARY KEY  (id),
            KEY campaign_author (campaign_author)
       ) {$wpdb->get_charset_collate()};";

		dbDelta( $sql );

		// A dry run must find no outstanding changes before marking an upgrade complete.
		return empty( dbDelta( $sql, false ) );
	}

	/**
	 * Create campaigns meta table if not exist
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the table matches its required schema.
	 */
	public function create_campaign_meta_table() {
		global $wpdb;

		$sql = "CREATE TABLE {$wpdb->prefix}revenue_campaign_meta (
            meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL default '0',
            meta_key varchar(255) default NULL,
            meta_value longtext,
            PRIMARY KEY  (meta_id),
            KEY campaign_id (campaign_id)
       ) {$wpdb->get_charset_collate()};";

		dbDelta( $sql );

		// A dry run must find no outstanding changes before marking an upgrade complete.
		return empty( dbDelta( $sql, false ) );
	}


	/**
	 * Create campaigns triggers table if not exist
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the table matches its required schema.
	 */
	public function create_campaign_triggers_table() {
		global $wpdb;

		$sql = "CREATE TABLE {$wpdb->prefix}revenue_campaign_triggers (
            trigger_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL default '0',
            trigger_action varchar(50) NOT NULL,
            trigger_type varchar(50) NOT NULL,
            item_quantity varchar(50) NOT NULL,
            item_id varchar(50) NOT NULL,
            PRIMARY KEY  (trigger_id),
            KEY campaign_id (campaign_id)
       ) {$wpdb->get_charset_collate()};";

		dbDelta( $sql );

		// A dry run must find no outstanding changes before marking an upgrade complete.
		return empty( dbDelta( $sql, false ) );
	}


	/**
	 * Create campaign analytics table if not exist
	 *
	 * @since 1.0.0
	 *
	 * @return bool Whether the table matches its required schema.
	 */
	public function create_campaign_analytics_table() {
		global $wpdb;

		$sql = "CREATE TABLE {$wpdb->prefix}revenue_campaign_analytics (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            campaign_id bigint(20) unsigned NOT NULL,
            date date NOT NULL,
            add_to_cart_count bigint(20) unsigned NOT NULL default '0',
            checkout_count bigint(20) unsigned NOT NULL default '0',
            order_count bigint(20) unsigned NOT NULL default '0',
            impression_count bigint(20) unsigned NOT NULL default '0',
            rejection_count bigint(20) unsigned NOT NULL default '0',
            PRIMARY KEY  (id),
            KEY campaign_date (campaign_id, date)
        ) {$wpdb->get_charset_collate()};";

		dbDelta( $sql );

		// A dry run must find no outstanding changes before marking an upgrade complete.
		return empty( dbDelta( $sql, false ) );
	}
}
