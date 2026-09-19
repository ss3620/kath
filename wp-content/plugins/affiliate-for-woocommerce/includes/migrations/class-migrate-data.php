<?php
/**
 * Class for handling data migration from multiple plugins.
 *
 * @package     affiliate-for-woocommerce/includes/migration
 * @since       8.34.0
 * @version     1.3.0
 */

namespace AFWC\Migrations;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( Migrate_Data::class ) ) {

	/**
	 * Handles migration from supported affiliate plugins.
	 */
	class Migrate_Data {

		/**
		 * Holds the source plugins to migrate data.
		 *
		 * @var array
		 */
		public $source_plugins = array();

		/**
		 * Instance of this class
		 *
		 * @var self
		 */
		private static $instance = null;

		/**
		 * Get the single instance of this class.
		 *
		 * @return self
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor.
		 */
		private function __construct() {
			$this->load_sources();
			// Move the affiliate tracking from source plugin to AFW.
			$this->migrate_tracking();
			// Disable the affiliate user role setting.
			add_filter( 'afwc_general_section_admin_settings', array( $this, 'disable_affiliate_users_roles_setting' ), 20 );

			// Trigger when a single source is completed their migration.
			add_action( 'afwc_migration_completed_for_source', array( $this, 'do_post_migration' ) );
		}

		/**
		 * Load available migration sources.
		 */
		public function load_sources() {

			if ( ! class_exists( Indeed_Affiliate_Pro::class ) ) {
				require_once AFWC_PLUGIN_DIRPATH . '/includes/migrations/sources/class-indeed-affiliate-pro.php';
			}

			if ( ! class_exists( Affiliate_WP::class ) ) {
				require_once AFWC_PLUGIN_DIRPATH . '/includes/migrations/sources/class-affiliate-wp.php';
			}

			$this->source_plugins = array(
				'affiliate-wp'         => array(
					'plugin_file' => 'affiliate-wp/affiliate-wp.php',
					'instance'    => Affiliate_WP::get_instance(),
					'is_active'   => afwc_is_plugin_active( 'affiliate-wp/affiliate-wp.php' ),
				),
				'indeed-affiliate-pro' => array(
					'plugin_file' => 'indeed-affiliate-pro/indeed-affiliate-pro.php',
					'instance'    => Indeed_Affiliate_Pro::get_instance(),
					'is_active'   => afwc_is_plugin_active( 'indeed-affiliate-pro/indeed-affiliate-pro.php' ),
				),
			);
		}

		/**
		 * Import data from the specified plugin.
		 *
		 * @param string $plugin Plugin slug.
		 *
		 * @return void
		 */
		public function import_data( $plugin = '' ) {

			// If no plugin is specified, run import for all available source plugins.
			if ( empty( $plugin ) && ! empty( $this->source_plugins ) && is_array( $this->source_plugins ) ) {
				foreach ( $this->source_plugins as $plugin_slug => $plugin_data ) {
					$this->import_data( $plugin_slug );
				}
				return;
			}

			if ( empty( $this->source_plugins[ $plugin ]['is_active'] )
				|| empty( $this->source_plugins[ $plugin ]['instance'] )
				|| ! $this->source_plugins[ $plugin ]['instance'] instanceof \AFWC_Migration
				|| ! is_callable( array( $this->source_plugins[ $plugin ]['instance'], 'init' ) )
			) {
				return;
			}

			$instance = $this->source_plugins[ $plugin ]['instance'];

			if ( is_callable( array( $instance, 'is_completed' ) ) && is_callable( array( $instance, 'delete_action' ) ) ) {
				if ( $instance->is_completed() ) {
					// Forcefully delete the action to re-initiate the process.
					$instance->delete_action();
				}
			}

			if ( is_callable( array( $instance, 'setup_action' ) ) ) {
				$instance->setup_action();
			}

			$instance->init();
			update_option( 'afwc_is_migration_process_running', true, 'no' );
		}

		/**
		 * Check if any migration is currently running.
		 *
		 * @return bool True if migration is in progress, otherwise false.
		 */
		public function is_running() {

			if ( empty( $this->source_plugins ) || ! is_array( $this->source_plugins ) ) {
				return false;
			}

			foreach ( $this->source_plugins as $source_migrate ) {
				if ( ! empty( $source_migrate['instance'] )
					&& is_callable( array( $source_migrate['instance'], 'is_running' ) )
					&& $source_migrate['instance']->is_running()
				) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Check if the migration is completed.
		 *
		 * @param string $source Plugin slug.
		 *
		 * @return bool True if migration is completed, otherwise false.
		 */
		public function is_completed( $source = '' ) {

			if ( empty( $this->source_plugins ) || ! is_array( $this->source_plugins ) ) {
				return false;
			}

			foreach ( $this->source_plugins as $plugin_slug => $source_migrate ) {

				if ( ( ! empty( $source ) && $plugin_slug !== $source ) // Skip if $source is available and doesn't match the current source.
					|| empty( $source_migrate['instance'] )
					|| ! $source_migrate['instance'] instanceof Source_Interface
				) {
					continue;
				}

				if ( ! $source_migrate['instance']->is_completed() ) {
					return false; // Return false if any source is incomplete.
				}
			}

			return true;
		}

		/**
		 * Check if any single source migration is completed.
		 *
		 * @return bool True if at least one source migration is completed, otherwise false.
		 */
		public function has_any_completed_migration() {

			if ( empty( $this->source_plugins ) || ! is_array( $this->source_plugins ) ) {
				return false;
			}

			foreach ( $this->source_plugins as $source_migrate ) {

				if ( empty( $source_migrate['instance'] )
					|| ! $source_migrate['instance'] instanceof Source_Interface
				) {
					continue;
				}

				if ( $source_migrate['instance']->is_completed() ) {
					return true; // Return true if any source is completed.
				}
			}

			return false;
		}

		/**
		 * Get the updated affiliate ID from old affiliate ID present in the source plugin.
		 *
		 * @param int $migrated_affiliate_id Migrated affiliate ID in source plugin.
		 *
		 * @throws \Exception If any error during the process.
		 * @return int New affiliate ID.
		 */
		public function get_new_affiliate_id_by_migrated_affiliate_id( $migrated_affiliate_id = 0 ) {

			if ( empty( $migrated_affiliate_id ) ) {
				return 0;
			}

			global $wpdb;

			try {
				$new_affiliate_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT u.ID
						FROM {$wpdb->users} AS u
						INNER JOIN {$wpdb->usermeta} AS um
							ON u.ID = um.user_id
						WHERE um.meta_key = 'afwc_migrated_affiliate_id'
						  AND um.meta_value = %d",
						$migrated_affiliate_id
					)
				);
				if ( is_null( $new_affiliate_id ) && ! empty( $wpdb->last_error ) ) {
					throw new \Exception( $wpdb->last_error );
				}
			} catch ( \Exception $e ) {
				$new_affiliate_id = 0;
				\Affiliate_For_WooCommerce::log_error( __METHOD__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			}

			return ! empty( $new_affiliate_id ) ? intval( $new_affiliate_id ) : 0;
		}

		/**
		 * Migrate the tracking from source plugins to AFW.
		 *
		 * Loops through all source plugins once, stops their tracking,
		 * and sets affiliate IDs for regular and renewal orders.
		 *
		 * @return void
		 */
		public function migrate_tracking() {

			if ( empty( $this->source_plugins ) || ! is_array( $this->source_plugins ) ) {
				return;
			}

			foreach ( $this->source_plugins as $source ) {

				if ( empty( $source['instance'] )
					|| ! $source['instance'] instanceof Source_Interface
					|| ! $source['instance']->is_completed() // Skip incomplete migrations.
				) {
					continue;
				}

				$source_instance = $source['instance'];

				$source_instance->stop_tracking();
				$this->set_affiliate_for_orders( $source_instance );
				$this->set_affiliate_for_renewal_orders( $source_instance );
			}
		}

		/**
		 * Run the action when a single source migration is completed.
		 *
		 * @return void.
		 */
		public function do_post_migration() {
			// Disable the affiliates generated by user role when any single source is migrated.
			$this->disable_affiliate_by_user_role_feature();

			if ( $this->is_completed() ) {
				update_option( 'affiliate_migration_data_affiliate_wc', 'no', 'no' ); // Dismiss the notice.
			}
		}

		/**
		 * Set affiliate for normal orders based on source plugin cookie.
		 *
		 * @param Source_Interface|null $source The migration source object.
		 *
		 * @return void.
		 */
		public function set_affiliate_for_orders( $source = null ) {
			if ( empty( $source ) || ! $source instanceof Source_Interface ) {
				return;
			}

			add_filter(
				'afwc_id_for_order',
				function ( $affiliate_id = 0 ) use ( $source ) {

					// Return existing affiliate if already set.
					if ( ! empty( $affiliate_id ) ) {
						return $affiliate_id;
					}

					// Get affiliate from source plugin.
					$source_affiliate_id = $source->get_affiliate_from_cookie();

					if ( empty( $source_affiliate_id ) ) {
						return $affiliate_id;
					}

					// Map to new AFW affiliate ID.
					$new_affiliate_id = $this->get_new_affiliate_id_by_migrated_affiliate_id( intval( $source_affiliate_id ) );

					return ! empty( $new_affiliate_id ) ? intval( $new_affiliate_id ) : $affiliate_id;
				},
				999
			);
		}

		/**
		 * Set affiliate for renewal orders based on source plugin.
		 *
		 * @param Source_Interface|null $source The migration source object.
		 *
		 * @return void.
		 */
		public function set_affiliate_for_renewal_orders( $source ) {
			if ( empty( $source ) || ! $source instanceof Source_Interface ) {
				return;
			}

			add_filter(
				'afwc_get_affiliate_by_order',
				function ( $affiliate_details = array(), $args = array() ) use ( $source ) {

					// Return existing details if already set or order ID missing.
					if ( ! empty( $affiliate_details ) || empty( $args['order_id'] ) ) {
						return $affiliate_details;
					}

					// Only process subscription orders.
					if ( ! function_exists( 'wcs_order_contains_subscription' ) || ! wcs_order_contains_subscription( $args['order_id'] ) ) {
						return $affiliate_details;
					}

					// Skip if running in the 'all' context.
					if ( ! doing_filter( 'afwc_id_for_order' ) || ( isset( $args['data'] ) && 'all' === $args['data'] ) ) {
						return $affiliate_details;
					}

					// Get affiliate from source plugin for renewal.
					$source_affiliate_id = $source->get_affiliate_from_order( $args['order_id'] );

					if ( empty( $source_affiliate_id ) ) {
						return $affiliate_details;
					}

					// Map to new AFW affiliate ID.
					$new_affiliate_id = $this->get_new_affiliate_id_by_migrated_affiliate_id( intval( $source_affiliate_id ) );

					return ! empty( $new_affiliate_id ) ? array( 'affiliate_id' => intval( $new_affiliate_id ) ) : $affiliate_details;
				},
				10,
				2
			);
		}

		/**
		 * Disable the affiliates by user role functionality.
		 * Rename the existing option to preserve data.
		 *
		 * @throws \Exception If any error during the process.
		 * @return void
		 */
		public function disable_affiliate_by_user_role_feature() {
			global $wpdb;

			try {
				$old_option_name = 'affiliate_users_roles';
				$current_date    = gmdate( 'Y-m-d', \Affiliate_For_WooCommerce::get_offset_timestamp() );
				$new_option_name = sanitize_key( "afwc_users_roles_{$current_date}" );

				$updated = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"UPDATE {$wpdb->options} SET option_name = %s WHERE option_name = %s",
						$new_option_name,
						$old_option_name
					)
				);

				if ( false === $updated ) {
					throw new \Exception( "Failed to rename option {$old_option_name}." );
				}

				// Delete the cache for the old option.
				wp_cache_delete( $old_option_name, 'options' );

			} catch ( \Exception $e ) {
				\Affiliate_For_WooCommerce::log_error( __METHOD__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			}
		}

		/**
		 * Disable the Affiliate User Roles setting in the admin UI after migration.
		 *
		 * @param array $settings The settings.
		 *
		 * @return array The modified settings array.
		 */
		public function disable_affiliate_users_roles_setting( $settings = array() ) {

			// Early exists if migration is not completed.
			if ( ! $this->has_any_completed_migration() || ! is_array( $settings ) || empty( $settings ) ) {
				return $settings;
			}

			foreach ( $settings as &$setting ) {

				if ( empty( $setting['id'] ) || 'affiliate_users_roles' !== $setting['id'] ) {
					continue;
				}

				// Disable the field by adding the disabled attribute.
				if ( ! isset( $setting['custom_attributes'] ) || ! is_array( $setting['custom_attributes'] ) ) {
					$setting['custom_attributes'] = array();
				}

				$setting['custom_attributes']['disabled'] = 'disabled';

				// Update the description to indicate that it's no longer functional.
				$setting['desc'] = _x(
					'This setting is no longer functional because some affiliates are imported from other plugins.',
					'Disabled description for Affiliate users roles setting',
					'affiliate-for-woocommerce'
				);
			}
			unset( $setting ); // Prevent accidental reference usage.

			return $settings;
		}
	}
}

// Initialize the migration process.
Migrate_Data::get_instance();
