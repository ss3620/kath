<?php
/**
 * Main class for Commission Dashboard
 *
 * @package     affiliate-for-woocommerce/includes/admin/
 * @since       2.5.0
 * @version     1.9.2
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Rules\Rule_Registry;

if ( ! class_exists( 'AFWC_Commission_Dashboard' ) ) {

	/**
	 * Main class for Commission Dashboard
	 */
	class AFWC_Commission_Dashboard extends AFWC_Commission_Plans {

		/**
		 * The Ajax events.
		 *
		 * @var array $ajax_events
		 */
		private $ajax_events = array(
			'save_commission',
			'delete_commission',
			'fetch_dashboard_data',
			'save_plan_order',
			'fetch_extra_data',
			'fetch_templates',
			'import_template',
			'duplicate_plan',
		);

		/**
		 * Variable to hold instance of AFWC_Commission_Dashboard
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return AFWC_Commission_Dashboard Singleton object of this class
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			add_action( 'wp_ajax_afwc_commission_controller', array( $this, 'request_handler' ) );
			add_action( 'wp_ajax_afwc_json_search_rule_values', array( $this, 'afwc_json_search_rule_values' ), 1, 2 );
		}

		/**
		 * Function to handle all ajax request
		 */
		public function request_handler() {
			if ( ! afwc_current_user_can_manage_affiliate() || empty( $_REQUEST ) || empty( wc_clean( wp_unslash( $_REQUEST['cmd'] ) ) ) ) { // phpcs:ignore
				return;
			}

			foreach ( $_REQUEST as $key => $value ) { // phpcs:ignore
				if ( 'commission' === $key ) {
					$params[ $key ] = wp_unslash( $value );
				} else {
					$params[ $key ] = wc_clean( wp_unslash( $value ) );
				}
			}

			$func_nm = ! empty( $params['cmd'] ) ? $params['cmd'] : '';

			if ( empty( $func_nm ) || ! in_array( $func_nm, $this->ajax_events, true ) ) {
				wp_die( esc_html_x( 'You are not allowed to use this action', 'authorization failure message', 'affiliate-for-woocommerce' ) );
			}

			if ( is_callable( array( $this, $func_nm ) ) ) {
				$this->$func_nm( $params );
			}
		}

		/**
		 * AJAX callback method to handle save commission plan.
		 *
		 * @param array $params Save commission params.
		 */
		public function save_commission( $params = array() ) {

			check_admin_referer( 'afwc-admin-save-commissions', 'security' );

			try {
				$commission_id = $this->process_commission_plan( $params );

				// Failed to save commission plan.
				if ( empty( $commission_id ) ) {
					wp_send_json( array( 'ACK' => 'Failed' ) );
				}

				// Updated existing commission plan.
				if ( true === $commission_id ) {
					wp_send_json( array( 'ACK' => 'Success' ) );
				}

				// Inserted new commission plan.
				wp_send_json(
					array(
						'ACK'              => 'Success',
						'last_inserted_id' => $commission_id,
					)
				);
			} catch ( RuntimeException $e ) {
				wp_send_json(
					array(
						'ACK' => 'Failed',
						'msg' => $e->getMessage(),
					)
				);
			}
		}

		/**
		 * Method to insert or update commission plan
		 *
		 * @param array $params Commission params.
		 * @return int|bool Commission ID on success when inserting the data, true on successful update, false on invalid data.
		 * @throws RuntimeException On validation failure or database failure.
		 */
		public function process_commission_plan( $params = array() ) {
			global $wpdb;

			if ( empty( $params['commission'] ) ) {
				return false;
			}

			$commission = json_decode( $params['commission'], true );
			if ( ! is_array( $commission ) ) {
				return false;
			}

			$values = array();

			$commission_id  = ! empty( $commission['id'] ) ? intval( $commission['id'] ) : 0;
			$values['name'] = ! empty( $commission['name'] ) ? sanitize_text_field( $commission['name'] ) : '';

			if ( empty( $values['name'] ) ) {
				throw new RuntimeException(
					esc_html_x( 'Please add a plan title', 'commission plan save validation error message when commission plan title is missing', 'affiliate-for-woocommerce' )
				);
			}

			$values['rules']                = ! empty( $commission['rules'] ) ? wp_json_encode( $commission['rules'] ) : '';
			$values['amount']               = ! empty( $commission['amount'] ) ? floatval( $commission['amount'] ) : 0;
			$values['type']                 = ! empty( $commission['type'] ) ? sanitize_text_field( $commission['type'] ) : 'Percentage';
			$values['status']               = ! empty( $commission['status'] ) ? sanitize_text_field( $commission['status'] ) : 'Active';
			$values['apply_to']             = ! empty( $commission['apply_to'] ) ? sanitize_text_field( $commission['apply_to'] ) : 'all';
			$values['action_for_remaining'] = ! empty( $commission['action_for_remaining'] ) ? sanitize_text_field( $commission['action_for_remaining'] ) : 'continue';

			$max_tiers             = class_exists( 'AFWC_Multi_Tier' ) ? AFWC_Multi_Tier::MAX_TIERS : 0;
			$no_of_tiers           = max( 1, min( intval( ! empty( $commission['no_of_tiers'] ) ? $commission['no_of_tiers'] : 0 ), $max_tiers ) );
			$values['no_of_tiers'] = $no_of_tiers;

			// Tier 1 is the base amount, so keep only the ( no_of_tiers - 1 ) distribution entries.
			$distribution           = ! empty( $commission['distribution'] ) ? array_values( (array) $commission['distribution'] ) : array();
			$distribution           = array_slice( $distribution, 0, max( 0, $no_of_tiers - 1 ) );
			$values['distribution'] = ! empty( $distribution ) ? implode( '|', $distribution ) : '';

			// Setup multi-tier values if the feature is enabled.
			$multi_tier            = is_callable( array( 'AFWC_Multi_Tier', 'get_instance' ) ) ? AFWC_Multi_Tier::get_instance() : null;
			$is_multi_tier_enabled = $multi_tier instanceof AFWC_Multi_Tier && ! empty( $multi_tier->is_enabled );

			if ( false === $is_multi_tier_enabled ) {
				if ( isset( $values['no_of_tiers'] ) ) {
					unset( $values['no_of_tiers'] );
				}
				if ( isset( $values['distribution'] ) ) {
					unset( $values['distribution'] );
				}
			}

			$result = false;

			// Update existing commission plan.
			if ( $commission_id > 0 ) {
				$values['commission_id'] = $commission_id;
				if ( true === $is_multi_tier_enabled ) {
					// Allow multi-tier fields to be updated.
					$result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
							"UPDATE {$wpdb->prefix}afwc_commission_plans SET name = %s, rules = %s, amount = %f, type = %s, status = %s, apply_to = %s, action_for_remaining = %s, no_of_tiers = %d, distribution = %s WHERE id = %s",
							$values
						)
					);
				} else {
					// Does not allow multi-tier fields to be updated.
					$result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
							"UPDATE {$wpdb->prefix}afwc_commission_plans SET name = %s, rules = %s, amount = %f, type = %s, status = %s, apply_to = %s, action_for_remaining = %s WHERE id = %s",
							$values
						)
					);
				}

				if ( false === $result ) {
					throw new RuntimeException(
						esc_html_x( 'Unable to update commission plan.', 'commission plan save error message', 'affiliate-for-woocommerce' )
					);
				}

				return true;
			}

			$values['meta_data'] = '';
			if ( ! empty( $commission['slug'] ) ) {
				$values['meta_data'] = wp_json_encode(
					array(
						'template' => sanitize_text_field( $commission['slug'] ),
					)
				);
			}

			// Insert new commission plan.
			if ( true === $is_multi_tier_enabled ) {
				// Allow multi-tier fields to be inserted.
				$result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
						"INSERT INTO {$wpdb->prefix}afwc_commission_plans ( name, rules, amount, type, status, apply_to, action_for_remaining, no_of_tiers, distribution, meta_data ) VALUES ( %s, %s, %f, %s, %s, %s, %s, %d, %s, %s )",
						$values
					)
				);
			} else {
				// Does not allow multi-tier fields to be inserted.
				$result = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
						"INSERT INTO {$wpdb->prefix}afwc_commission_plans ( name, rules, amount, type, status, apply_to, action_for_remaining, meta_data ) VALUES ( %s, %s, %f, %s, %s, %s, %s, %s )",
						$values
					)
				);
			}

			if ( false === $result ) {
				throw new RuntimeException(
					esc_html_x( 'Unable to insert commission plan.', 'commission plan save error message', 'affiliate-for-woocommerce' )
				);
			}

			$last_id = ! empty( $wpdb->insert_id ) ? $wpdb->insert_id : 0;

			$plan_order = is_callable( array( $this, 'get_plans_order' ) ) ? $this->get_plans_order() : array();

			$priority = isset( $params['priority'] ) && is_numeric( $params['priority'] )
				? intval( $params['priority'] )
				: ( ( ! empty( $plan_order ) && is_array( $plan_order ) ) ? count( $plan_order ) : 0 );

			array_splice( $plan_order, $priority, 0, $last_id );

			update_option( 'afwc_plan_order', $plan_order, 'no' );

			return $last_id;
		}

		/**
		 * Function to handle delete commission
		 *
		 * @param array $params delete commission params.
		 */
		public function delete_commission( $params = array() ) {

			check_admin_referer( 'afwc-admin-delete-commissions', 'security' );

			global $wpdb;

			$response = array( 'ACK' => 'Failed' );
			if ( ! empty( $params['commission_id'] ) ) {

				$default_plan = afwc_get_default_commission_plan_id();

				if ( intval( $params['commission_id'] ) === $default_plan ) {
					return wp_send_json(
						array(
							'ACK' => 'Error',
							'msg' => _x( 'Default plan can not be deleted', 'commission default plan delete error message', 'affiliate-for-woocommerce' ),
						)
					);
				}

				$result = $wpdb->query( // phpcs:ignore
					$wpdb->prepare(
						"DELETE FROM {$wpdb->prefix}afwc_commission_plans WHERE id = %d",
						$params['commission_id']
					)
				);
				if ( false === $result ) {
					wp_send_json(
						array(
							'ACK' => 'Error',
							'msg' => _x( 'Failed to delete the plan', 'Notification error message for failed commission plan deletion', 'affiliate-for-woocommerce' ),
						)
					);
				} else {
					// delete from plan order.
					$plan_order = is_callable( array( $this, 'get_plans_order' ) ) ? $this->get_plans_order() : array();
					if ( ! empty( $plan_order ) && is_array( $plan_order ) ) {
						$c          = $params['commission_id'];
						$plan_order = array_filter(
							$plan_order,
							function ( $e ) use ( $c ) {
								return ( absint( $e ) !== absint( $c ) );
							}
						);
						update_option( 'afwc_plan_order', $plan_order, 'no' );
					}
					wp_send_json(
						array(
							'ACK' => 'Success',
							'msg' => _x( 'Plan deleted successfully', 'Notification success message after deleting a commission plan', 'affiliate-for-woocommerce' ),
						)
					);
				}
			}
		}

		/**
		 * Function to handle fetch data
		 *
		 * @param array $params fetch commission dashboard data params.
		 */
		public function fetch_dashboard_data( $params = array() ) {

			check_admin_referer( 'afwc-admin-commissions-dashboard-data', 'security' );

			$commission_plans = is_callable( array( $this, 'get_plans_order' ) )
				? $this->get_plans(
					! empty( $params['commission_status'] ) ? array( 'status' => $params['commission_status'] ) : array()
				) : array();

			if ( ! empty( $commission_plans ) ) {

				wp_send_json(
					array(
						'ACK'    => 'Success',
						'result' => array(
							'commissions' => $commission_plans,
							'plan_order'  => is_callable( array( $this, 'get_plans_order' ) ) ? $this->get_plans_order() : array(),
						),
					)
				);
			}

			wp_send_json(
				array(
					'ACK' => 'Failed',
					'msg' => _x( 'No commission plans found', 'commission plans not found message', 'affiliate-for-woocommerce' ),
				)
			);
		}

		/**
		 * Function to handle save plan order
		 *
		 * @param array $params save plan order params.
		 */
		public static function save_plan_order( $params = array() ) {

			check_admin_referer( 'afwc-admin-save-commission-order', 'security' );

			$default_plan_id = afwc_get_default_commission_plan_id();
			if ( ! empty( $params['plan_order'] ) ) {
				$plan_order = (array) json_decode( $params['plan_order'], true );

				if ( ! empty( $default_plan_id ) ) {
					$key = array_search( $default_plan_id, $plan_order, true );
					if ( false !== $key ) {
						unset( $plan_order[ $key ] );
					}
					$plan_order[] = $default_plan_id;
				}
				$plan_order = array_values( $plan_order );
				update_option( 'afwc_plan_order', $plan_order, 'no' );
				wp_send_json(
					array(
						'ACK'    => 'Success',
						'result' => $plan_order,
					)
				);
			} else {
				wp_send_json(
					array(
						'msg' => _x( 'No commission plan order to save', 'no commission plan order save message', 'affiliate-for-woocommerce' ),
					)
				);
			}
		}

		/**
		 * Search for attribute values and return json
		 *
		 * @return void
		 */
		public function afwc_json_search_rule_values() {

			check_admin_referer( 'afwc-admin-search-commission-plans', 'security' );

			if ( ! afwc_current_user_can_manage_affiliate() ) {
				wp_die( esc_html_x( 'You are not allowed to use this action', 'authorization failure message', 'affiliate-for-woocommerce' ) );
			}

			$term = ( ! empty( $_GET['term'] ) ) ? (string) urldecode( wp_strip_all_tags( wp_unslash( $_GET ['term'] ) ) ) : '';
			$type = ( ! empty( $_GET['type'] ) ) ? (string) urldecode( wp_strip_all_tags( wp_unslash( $_GET ['type'] ) ) ) : 'affiliate';

			if ( empty( $term ) ) {
				wp_die();
			}

			$rule_values = array();

			if ( ! empty( $type ) ) {
				$rule_registry = is_callable( array( Rule_Registry::class, 'get_instance' ) ) ? Rule_Registry::get_instance() : null;
				$rule_obj      = is_callable( array( $rule_registry, 'get_registered' ) ) ? $rule_registry->get_registered( $type ) : null;
				$rule_values   = is_callable( array( $rule_obj, 'search_values' ) ) ? $rule_obj->search_values( $term ) : array();
			}

			echo wp_json_encode( $rule_values );
			wp_die();
		}

		/**
		 * Fetch data call
		 *
		 * @param string $params mixed.
		 */
		public function fetch_extra_data( $params ) {
			check_admin_referer( 'afwc-admin-extra-data', 'security' );
			$data = json_decode( $params['data'], true );

			if ( empty( $data ) || ! is_array( $data ) ) {
				wp_send_json(
					array(
						'ACK' => 'Failed',
						'msg' => _x( 'Data not available', 'Requirement missing message to fetch extra data for commission plans', 'affiliate-for-woocommerce' ),
					)
				);
			}

			$rule_registry = is_callable( array( Rule_Registry::class, 'get_instance' ) ) ? Rule_Registry::get_instance() : null;
			$rule_values   = array();
			foreach ( $data as $type => $ids ) {
				$rule_obj             = is_callable( array( $rule_registry, 'get_registered' ) ) ? $rule_registry->get_registered( $type ) : null;
				$rule_values[ $type ] = is_callable( array( $rule_obj, 'search_values' ) ) ? $rule_obj->search_values( $ids, false ) : array();
			}

			if ( ! empty( $rule_values ) ) {
				wp_send_json(
					array(
						'ACK'    => 'Success',
						'result' => $rule_values,
					)
				);
			} else {
				wp_send_json(
					array(
						'ACK' => 'Success',
						'msg' => _x( 'No commission plans found', 'commission plans not found message', 'affiliate-for-woocommerce' ),
					)
				);
			}
		}

		/**
		 * Get commission plan statuses.
		 *
		 * @param string $status Plan Status.
		 *
		 * @return array|string Return the status title if the status is provided otherwise return array of all statuses.
		 */
		public static function get_statuses( $status = '' ) {
			$statuses = array(
				'Active' => _x( 'Active', 'active commission plan status', 'affiliate-for-woocommerce' ),
				'Draft'  => _x( 'Draft', 'draft commission plan status', 'affiliate-for-woocommerce' ),
			);

			return empty( $status ) ? $statuses : ( ! empty( $statuses[ $status ] ) ? $statuses[ $status ] : '' );
		}

		/**
		 * AJAX callback to fetch commission plan templates list
		 */
		public function fetch_templates() {
			check_admin_referer( 'afwc-admin-fetch-commission-plan-templates', 'security' );

			$templates_dir = AFWC_PLUGIN_DIRPATH . '/includes/commission-plans/templates';
			if ( ! is_dir( $templates_dir ) || ! is_readable( $templates_dir ) ) {
				wp_send_json_error( array( 'msg' => _x( 'No templates found', 'no templates message', 'affiliate-for-woocommerce' ) ) );
			}

			$files = glob( $templates_dir . '/*.php' );
			if ( empty( $files ) || ! is_array( $files ) ) {
				$files = array();
			}

			/**
			 * Filter to modify the commission plan templates files list.
			 *
			 * @param array $files Array of commission plan templates files.
			 *
			 * @since 9.9.0
			 */
			$files = apply_filters( 'afwc_commission_plan_templates', $files );
			if ( empty( $files ) || ! is_array( $files ) ) {
				wp_send_json_error( array( 'msg' => _x( 'No templates found', 'no templates message', 'affiliate-for-woocommerce' ) ) );
			}

			$templates = array();
			foreach ( $files as $file ) {
				if ( ! is_readable( $file ) ) {
					continue;
				}

				$template = require_once $file;

				if ( ! is_array( $template )
					|| empty( $template['slug'] ) || ! is_string( $template['slug'] )
					|| empty( $template['name'] ) || ! is_string( $template['name'] )
					|| empty( $template['description'] ) || ! is_string( $template['description'] )
				) {
					continue;
				}

				if ( basename( $file, '.php' ) !== 'afwc-' . $template['slug'] ) {
					continue;
				}

				$templates[] = array(
					'slug'        => sanitize_key( $template['slug'] ),
					'name'        => sanitize_text_field( $template['name'] ),
					'description' => sanitize_textarea_field( $template['description'] ),
				);
			}

			/**
			 * Filter to modify the commission plan templates list.
			 *
			 * @param array $templates Array of commission plan templates.
			 * @param array            Additional context information.
			 *
			 * @since 8.58.0
			 */
			wp_send_json_success( apply_filters( 'afwc_commission_templates_list', $templates, array( 'source' => $this ) ) );
		}

		/**
		 * AJAX callback method to import commission plan template
		 *
		 * @param array $params Import commission plan template params.
		 */
		public function import_template( $params = array() ) {
			check_admin_referer( 'afwc-admin-import-commission-plan-template', 'security' );

			if ( empty( $params['slug'] ) ) {
				wp_send_json_error( array( 'msg' => _x( 'Invalid template slug.', 'invalid template slug message', 'affiliate-for-woocommerce' ) ) );
			}

			$slug = $params['slug'];

			$templates = array();

			$templates_dir = AFWC_PLUGIN_DIRPATH . '/includes/commission-plans/templates';
			$files         = ( is_dir( $templates_dir ) && is_readable( $templates_dir ) )
				? glob( $templates_dir . '/afwc-*.php' )
				: array();

			if ( empty( $files ) || ! is_array( $files ) ) {
				$files = array();
			}

			/**
			 * Filter to modify the commission plan templates files list.
			 *
			 * @param array $files Array of commission plan templates files.
			 *
			 * @since 9.9.0
			 */
			$files = apply_filters( 'afwc_commission_plan_templates', $files );
			if ( empty( $files ) || ! is_array( $files ) ) {
				wp_send_json_error( array( 'msg' => _x( 'Template not found.', 'template not found message', 'affiliate-for-woocommerce' ) ) );
			}

			foreach ( $files as $file ) {
				$key               = basename( $file, '.php' );
				$key               = substr( $key, 5 );
				$templates[ $key ] = $file;
			}

			if ( ! isset( $templates[ $slug ] ) || ! is_readable( $templates[ $slug ] ) ) {
				wp_send_json_error( array( 'msg' => _x( 'Template not found.', 'template not found message', 'affiliate-for-woocommerce' ) ) );
			}

			$template = require_once $templates[ $slug ];

			if ( ! is_array( $template ) || empty( $template['plan-data'] ) || ! is_array( $template['plan-data'] ) ) {
				wp_send_json_error( array( 'msg' => _x( 'Invalid template data.', 'invalid template data message', 'affiliate-for-woocommerce' ) ) );
			}

			$plan_data = $this->validate_commission_plan_data( $template['plan-data'] );
			if ( empty( $plan_data ) ) {
				wp_send_json_error( array( 'msg' => _x( 'Invalid template data.', 'invalid template data message', 'affiliate-for-woocommerce' ) ) );
			}

			$plan_data['slug']    = $slug;
			$params['commission'] = wp_json_encode( $plan_data );

			if ( isset( $template['priority'] ) ) {
				$params['priority'] = $template['priority'];
			}

			try {
				$commission_id = $this->process_commission_plan( $params );

				// Failed to save commission plan.
				if ( empty( $commission_id ) ) {
					wp_send_json( array( 'ACK' => 'Failed' ) );
				}

				// Successfully imported template.
				wp_send_json(
					array(
						'ACK'              => 'Success',
						'last_inserted_id' => $commission_id,
					)
				);
			} catch ( RuntimeException $e ) {
				wp_send_json(
					array(
						'ACK' => 'Failed',
						'msg' => $e->getMessage(),
					)
				);
			}
		}

		/**
		 * AJAX callback method to import commission plan template
		 *
		 * @param array $params Import commission plan template params.
		 */
		public function duplicate_plan( $params = array() ) {
			check_admin_referer( 'afwc-admin-duplicate-commission-plan', 'security' );
			if ( empty( $params['plan_data'] ) || ! is_string( $params['plan_data'] ) ) {
				wp_send_json_error( array( 'msg' => _x( 'Invalid plan data.', 'invalid plan data message', 'affiliate-for-woocommerce' ) ) );
			}

			$plan_data = json_decode( wp_unslash( $params['plan_data'] ), true );
			$plan_data = $this->validate_commission_plan_data( $plan_data );
			if ( ! is_array( $plan_data ) ) {
				wp_send_json_error( array( 'msg' => _x( 'Invalid plan data.', 'invalid plan data message', 'affiliate-for-woocommerce' ) ) );
			}

			$data['commission'] = wp_json_encode( $plan_data );

			try {
				$commission_id = $this->process_commission_plan( $data );

				// Failed to save commission plan.
				if ( empty( $commission_id ) ) {
					wp_send_json( array( 'ACK' => 'Failed' ) );
				}

				// Successfully imported template.
				wp_send_json(
					array(
						'ACK'              => 'Success',
						'last_inserted_id' => $commission_id,
					)
				);
			} catch ( RuntimeException $e ) {
				wp_send_json(
					array(
						'ACK' => 'Failed',
						'msg' => $e->getMessage(),
					)
				);
			}
		}

		/**
		 * Validate commission plan structure and data types
		 *
		 * @param array $plan Commission plan data.
		 * @return false|array Validated commission plan data or false on invalid data.
		 */
		private function validate_commission_plan_data( $plan = array() ) {
			if ( empty( $plan ) || ! is_array( $plan ) ) {
				return false;
			}

			foreach ( $plan as $key => $value ) {

				switch ( $key ) {
					case 'name':
					case 'status':
					case 'type':
					case 'apply_to':
					case 'action_for_remaining':
						if ( ! is_string( $value ) ) {
							return false;
						}
						break;

					case 'amount':
						if ( ! is_numeric( $value ) ) {
							return false;
						}
						break;

					case 'no_of_tiers':
						if ( ! is_int( $value ) ) {
							return false;
						}
						break;

					case 'rules':
					case 'distribution':
					case 'meta_data':
						if ( ! is_array( $value ) ) {
							return false;
						}
						break;

					default:
						return false;
				}
			}

			$plan['rules'] = $this->validate_rules( $plan['rules'] );
			if ( empty( $plan['rules'] ) ) {
				return false;
			}

			return $plan;
		}

		/**
		 * Recursive validation of rules structure and data types
		 *
		 * @param array $rules Rules data.
		 * @return false|array Validated rules data or false on invalid data.
		 */
		private function validate_rules( $rules = array() ) {
			if ( empty( $rules ) || ! is_array( $rules ) ) {
				return false;
			}

			if ( empty( $rules['condition'] ) || empty( $rules['rules'] ) ) {
				return false;
			}

			if ( ! is_string( $rules['condition'] ) || ! is_array( $rules['rules'] ) ) {
				return false;
			}

			foreach ( $rules['rules'] as $rule ) {
				if ( isset( $rule['condition'] ) ) {
					if ( ! $this->validate_rules( $rule ) ) {
						return false;
					}
					continue;
				}

				if ( empty( $rule['type'] ) || ! is_string( $rule['type'] ) ) {
					return false;
				}

				if ( empty( $rule['operator'] ) || ! is_string( $rule['operator'] ) ) {
					return false;
				}

				if ( ! array_key_exists( 'value', $rule ) ) {
					return false;
				}

				unset( $rule['key'] );
			}

			return $rules;
		}
	}

}

AFWC_Commission_Dashboard::get_instance();
