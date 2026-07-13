<?php
/**
 * Abstract class for migrating data from other sources/plugins.
 *
 * @package     affiliate-for-woocommerce/abstracts
 * @since       8.34.0
 * @version     1.2.1
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'AFWC_Background_Process', false ) ) {
	require_once AFWC_PLUGIN_DIRPATH . '/includes/abstracts/class-afwc-background-process.php';
}

use AFWC\Migrations\Source_Interface;

if ( ! class_exists( 'AFWC_Migration' ) && class_exists( 'AFWC_Background_Process' ) ) {

	/**
	 * Abstract Migration class.
	 *
	 * Handles affiliate data migration from other sources/plugins to AFWC.
	 */
	abstract class AFWC_Migration extends AFWC_Background_Process implements Source_Interface {

		/**
		 * Variable to hold source slug.
		 *
		 * @var string
		 */
		public $source_slug = '';

		/**
		 * Actions for migration.
		 * It holds array of actions to migrate each data.
		 *
		 * @var array
		 */
		protected $actions = array( 'afwc_migrate_settings', 'afwc_migrate_affiliate_data' );

		/**
		 * Current action being processed.
		 *
		 * @var string
		 */
		protected $action = '';

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->setup_action();
			parent::__construct();
		}

		/**
		 * Force delete the action.
		 * This is useful when anyone wants to forcefully restart the action.
		 *
		 * @return void
		 */
		public function delete_action() {
			delete_option( 'afwc_migration_current_batch_' . $this->source_slug );
		}

		/**
		 * Sets up the initial action to start migration.
		 *
		 * @return void
		 */
		public function setup_action() {
			if ( empty( $this->actions ) ) {
				return;
			}

			$current_action = get_option( 'afwc_migration_current_batch_' . $this->source_slug, $this->actions[0] );

			if ( 'completed' === $current_action ) {
				return;
			}

			$this->action = $this->get_action_name( $current_action );

			if ( $this->action === $this->get_action_name( 'afwc_migrate_affiliate_data' ) ) {
				$this->batch_limit = 50; // Increase the limit to 50 for affiliate data migration only.
			}
		}

		/**
		 * Get the action name.
		 *
		 * @param string|array $name Name(s) of the action(s).
		 *
		 * @return string|array `{action_name}/{source_slug}` formatted action name(s).
		 */
		public function get_action_name( $name = '' ) {
			if ( empty( $name ) ) {
				return is_array( $name ) ? array() : '';
			}

			if ( is_array( $name ) ) {
				return array_map(
					function ( $n ) {
						return $n . '/' . $this->source_slug;
					},
					$name
				);
			}

			return $name . '/' . $this->source_slug;
		}

		/**
		 * Executes tasks for each batch.
		 *
		 * @param array $args Arguments for migration.
		 *
		 * @throws Exception If health check fails.
		 * @return void
		 */
		public function task( $args = array() ) {

			if ( empty( $args ) || ! is_array( $args ) ) {
				throw new Exception(
					esc_html(
						sprintf(
							/* translators: %s: Background process action name */
							_x( 'Arguments are not available to run the task: %s', 'Requirement missing to run the migration process', 'affiliate-for-woocommerce' ),
							$this->action
						)
					)
				);
			}

			if ( $this->action === $this->get_action_name( 'afwc_migrate_settings' ) ) {
				$this->migrate_settings( $args );
				$this->completed(); // Mark the settings migration as completed.
			}

			if ( $this->action === $this->get_action_name( 'afwc_migrate_affiliate_data' ) ) {
				foreach ( $args as $affiliate_data ) {
					$this->migrate_affiliate( $affiliate_data );
				}
			}

			if ( ! $this->health_status() ) {
				throw new Exception(
					esc_html(
						sprintf(
							/* translators: 1: Background process action name */
							_x( 'Batch stopped due to health status in task: %s', 'Logger for affiliate migration batch stopped due to health status', 'affiliate-for-woocommerce' ),
							$this->action
						)
					)
				);
			}
		}

		/**
		 * Migrates settings.
		 *
		 * @param array $args Settings to migrate.
		 *
		 * @return void
		 */
		private function migrate_settings( $args = array() ) {
			if ( empty( $args ) || ! is_array( $args ) ) {
				return;
			}

			foreach ( $args as $setting ) {
				if ( ! empty( $setting['callback'] ) && is_callable( $setting['callback'] ) ) {
					call_user_func_array(
						$setting['callback'],
						isset( $setting['args'] ) ? $setting['args'] : array()
					);
				}
			}
		}

		/**
		 * Migrates individual affiliate data.
		 *
		 * @param array $args Affiliate data.
		 * @return void
		 */
		private function migrate_affiliate( $args = array() ) {

			if ( empty( $args['user_id'] ) || empty( $args['affiliate_id'] ) ) {
				return;
			}

			$user_id = intval( $args['user_id'] );

			update_user_meta( $user_id, 'afwc_migrated_affiliate_id', $args['affiliate_id'] );
			update_user_meta( $user_id, 'afwc_is_affiliate', 'yes' );
			$default_identifier = ! empty( $args['identifier'] ) ? $args['identifier'] : $args['affiliate_id'];

			$assigned_affiliate      = afwc_get_affiliate_id_by_assigned_identifier( $default_identifier );
			$is_generated_identifier = false;

			// Check if the identifier or affiliate ID is already assigned to another affiliate.
			if ( ( ! empty( $assigned_affiliate ) && is_numeric( $assigned_affiliate ) && ( 'yes' === get_user_meta( $assigned_affiliate, 'afwc_is_affiliate', true ) ) )
				|| (
					is_numeric( $default_identifier )
					&& ! get_user_meta( $default_identifier, 'afwc_default_identifier', true )
					&& 'yes' === get_user_meta( $default_identifier, 'afwc_is_affiliate', true )
				)
			) {
				$is_generated_identifier = afwc_generate_default_identifier( $user_id );
			}

			empty( $is_generated_identifier ) && update_user_meta( $user_id, 'afwc_default_identifier', $default_identifier );

			if ( ! empty( $args['signup_date'] ) ) {
				update_user_meta( $user_id, 'afwc_signup_date', gmdate( 'Y-m-d H:i:s', strtotime( $args['signup_date'] ) ) );
			}

			$this->set_user_meta_mapping( $user_id );

			Affiliate_For_WooCommerce::log(
				'info',
				sprintf(
					/* translators: %s: User ID */
					_x( 'Successfully migrated affiliate with user ID: %s', 'success message on migration of affiliate user data', 'affiliate-for-woocommerce' ),
					$user_id
				)
			);
		}

		/**
		 * Update the user meta fields from source plugin's user meta.
		 *
		 * @param int $user_id The User ID.
		 *
		 * @return void
		 */
		private function set_user_meta_mapping( $user_id = 0 ) {
			if ( empty( $user_id ) ) {
				return;
			}

			$meta_keys = $this->get_user_meta_map();

			if ( empty( $meta_keys ) || ! is_array( $meta_keys ) ) {
				return;
			}

			$user_id = intval( $user_id );

			foreach ( $meta_keys as $afwc_key => $source_key ) {
				$source_value = $this->filter_user_meta( $afwc_key, get_user_meta( $user_id, $source_key, true ) );
				if ( ! empty( $source_value ) ) {
					update_user_meta( $user_id, $afwc_key, $source_value );
				}
			}
		}

		/**
		 * Filter the user meta.
		 *
		 * @param string $meta_key The user meta key.
		 * @param mixed  $value The user meta value.
		 *
		 * @return mixed The filtered value.
		 */
		public function filter_user_meta( $meta_key = '', $value = '' ) {
			if ( empty( $meta_key ) ) {
				return $value;
			}

			if ( 'afwc_payout_method' === $meta_key ) {
				$supported_payout_methods = afwc_get_payout_methods();
				if ( empty( $supported_payout_methods ) || ! is_array( $supported_payout_methods ) ) {
					return '';
				}

				if ( 'stripe_v3' === $value || 'stripe_v2' === $value ) {
					$value = 'stripe';
				}

				return in_array( $value, array_keys( $supported_payout_methods ), true ) ? $value : '';
			}

			return $value;
		}

		/**
		 * Update the option from source plugin to the affiliate for woocommerce plugin.
		 *
		 * @param string $afwc_key The affiliate for woocommerce option key.
		 * @param mixed  $value The source value key.
		 *
		 * @return void
		 */
		public function update_option( $afwc_key = '', $value = '' ) {
			if ( '' === $value ) {
				return; // Do not update if the source value is blank.
			}

			if ( 0 === $value || '0' === $value ) {
				$value = 'no';
			}

			if ( 1 === $value || '1' === $value ) {
				$value = 'yes';
			}

			! empty( $value ) && update_option( $afwc_key, $value, 'no' );
		}

		/**
		 * Retrieves remaining items for migration.
		 *
		 * @return array Remaining data to migrate.
		 */
		public function get_remaining_items() {
			if ( $this->action === $this->get_action_name( 'afwc_migrate_settings' ) ) {
				return $this->get_setups();
			}

			if ( $this->action === $this->get_action_name( 'afwc_migrate_affiliate_data' ) ) {
				return $this->get_affiliates();
			}

			return array();
		}

		/**
		 * Executes after completing migration batch.
		 */
		public function completed() {
			parent::completed();

			/**
			 * Action triggered once the migration for the source is completed.
			 *
			 * @since 8.34.0
			 */
			do_action( 'afwc_migration_completed_for_source', $this->source_slug );
			$this->run_next_action();
		}

		/**
		 * Sets up and runs the next migration action.
		 */
		protected function run_next_action() {
			$current_index = array_search( $this->action, $this->get_action_name( $this->actions ), true );

			if ( false !== $current_index && ! empty( $this->actions[ $current_index + 1 ] ) ) {
				update_option( 'afwc_migration_current_batch_' . $this->source_slug, $this->actions[ $current_index + 1 ] );
				$this->setup_action();
				$this->register_action_callback();
				// Re-initiate the background process to start the next action.
				$this->init();
			} else {
				update_option( 'afwc_migration_current_batch_' . $this->source_slug, 'completed' );
			}
		}

		/**
		 * Method to check whether the current migration is completed.
		 *
		 * @return bool
		 */
		public function is_completed() {
			return 'completed' === get_option( 'afwc_migration_current_batch_' . $this->source_slug );
		}

		/**
		 * Method to check whether the current migration is running.
		 *
		 * @return bool
		 */
		public function is_running() {
			$status = get_option( 'afwc_migration_current_batch_' . $this->source_slug );
			return ( false !== $status ) && ( 'completed' !== $status ); // The status should not start yet or not completed.
		}
	}
}
