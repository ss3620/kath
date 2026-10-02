<?php

namespace RevenuePro;

defined( 'ABSPATH' ) || exit;

use Revenue\Revenue_Campaign_REST_Controller;
use WP_Error;

/**
 * Owns REST writes and field registration for Pro campaign types.
 */
class Revenue_Pro_REST {

	/**
	 * Pro campaign types handled by this provider.
	 *
	 * @var string[]
	 */
	private $campaign_types = array(
		'mix_match',
		'frequently_bought_together',
		'double_order',
		'spending_goal',
	);

	/**
	 * Register provider hooks.
	 */
	public function __construct() {
		add_filter( 'revenue_campaign_types', array( $this, 'add_campaign_types' ) );
		add_filter( 'revenue_campaign_rest_dispatch', array( $this, 'dispatch' ), 10, 5 );
		add_filter( 'revenue_campaign_schema_properties', array( $this, 'add_schema_properties' ) );
		add_filter( 'revenue_campaign_meta_keys', array( $this, 'add_meta_keys' ) );
		add_filter( 'revenue_rest_before_prepare_campaign', array( $this, 'prepare_campaign_response' ), 9 );
		add_filter( 'revenue_rest_pre_insert_campaign', array( $this, 'prepare_campaign_for_database' ), 10, 2 );
	}

	/**
	 * Add labels for campaign types implemented by Pro.
	 *
	 * @param array $types Campaign type labels.
	 * @return array
	 */
	public function add_campaign_types( $types ) {
		$types['mix_match']                  = _x( 'Product Mix Match', 'Campaign Types', 'revenue-pro' );
		$types['frequently_bought_together'] = _x( 'Frequently Bought Together', 'Campaign Types', 'revenue-pro' );
		$types['double_order']               = _x( 'Double Order', 'Campaign Types', 'revenue-pro' );
		$types['spending_goal']              = _x( 'Spending Goal', 'Campaign Types', 'revenue-pro' );

		return $types;
	}

	/**
	 * Apply Pro-owned persistence defaults.
	 *
	 * @param array           $data    Prepared campaign data.
	 * @param WP_REST_Request $request REST request.
	 * @return array
	 */
	public function prepare_campaign_for_database( $data, $request ) {
		if ( isset( $request['campaign_type'] ) && 'spending_goal' === $request['campaign_type'] ) {
			$data['campaign_trigger_type'] = 'all_products';
		}

		return $data;
	}

	/**
	 * Restore response defaults owned by Pro campaign types.
	 *
	 * @param array $data Campaign response data.
	 * @return array
	 */
	public function prepare_campaign_response( $data ) {
		if ( empty( $data['campaign_type'] ) ) {
			return $data;
		}

		if ( 'mix_match' === $data['campaign_type'] ) {
			$data['campaign_trigger_relation'] = 'and';
		}

		if ( 'double_order' === $data['campaign_type'] ) {
			if ( ! isset( $data['double_order_animation_type'] ) ) {
				$data['double_order_animation_type'] = 'shake';
			}

			if ( isset( $data['campaign_placement'] ) && 'multiple' !== $data['campaign_placement'] && empty( $data['placement_settings'] ) ) {
				$placement                        = $data['campaign_placement'];
				$data['placement_settings']       = array(
					$placement => array(
						'page'                     => $placement,
						'status'                   => 'yes',
						'display_style'            => ! empty( $data['campaign_display_style'] ) ? $data['campaign_display_style'] : 'inpage',
						'builder_view'             => ! empty( $data['campaign_builder_view'] ) ? $data['campaign_builder_view'] : 'list',
						'inpage_position'          => ! empty( $data['campaign_inpage_position'] ) ? $data['campaign_inpage_position'] : 'review_order_before_payment',
						'popup_animation'          => isset( $data['campaign_popup_animation'] ) ? $data['campaign_popup_animation'] : '',
						'popup_animation_delay'    => isset( $data['campaign_popup_animation_delay'] ) ? $data['campaign_popup_animation_delay'] : '',
						'floating_position'        => isset( $data['campaign_floating_position'] ) ? $data['campaign_floating_position'] : '',
						'floating_animation_delay' => isset( $data['campaign_floating_animation_delay'] ) ? $data['campaign_floating_animation_delay'] : '',
						'drawer_position'          => 'top-left',
					),
				);
				$data['campaign_placement']       = 'multiple';
				$data['campaign_display_style']   = 'multiple';
				$data['campaign_inpage_position'] = 'multiple';
			} elseif ( ! empty( $data['placement_settings'] ) && is_array( $data['placement_settings'] ) ) {
				// Repair rows saved with an empty display style; the storefront only matches 'inpage'.
				foreach ( $data['placement_settings'] as $page => $settings ) {
					if ( is_array( $settings ) && empty( $settings['display_style'] ) ) {
						$data['placement_settings'][ $page ]['display_style'] = 'inpage';
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Process a write for a Pro-owned campaign.
	 *
	 * @param mixed           $result      Previous provider result.
	 * @param string          $operation   Write operation.
	 * @param string          $type        Campaign type.
	 * @param WP_REST_Request $request     REST request.
	 * @param int             $campaign_id Stored campaign ID.
	 * @return mixed
	 */
	public function dispatch( $result, $operation, $type, $request, $campaign_id ) {
		if ( null !== $result || ! in_array( $type, $this->campaign_types, true ) ) {
			return $result;
		}

		$blocked = $this->check_license( $operation, $request );
		if ( is_wp_error( $blocked ) ) {
			return $blocked;
		}

		$controller = new Revenue_Campaign_REST_Controller();

		switch ( $operation ) {
			case 'create':
				$campaign_id = $controller->save_campaign( $request );
				break;

			case 'update':
				$campaign_id = $controller->update_campaign( $request );
				break;

			case 'clone':
				$campaign = revenue()->get_campaign_data( $campaign_id );
				$campaign = revenue()->set_product_image_trigger_item_response( $campaign, true );

				if ( ! $campaign ) {
					return new WP_Error( 'revenue_rest_invalid_campaign', __( 'Invalid Campaign.', 'revenue-pro' ), array( 'status' => 404 ) );
				}

				unset( $campaign['id'] );
				$campaign['campaign_name'] = __( 'Duplicate of ', 'revenue-pro' ) . $campaign['campaign_name'];
				$campaign_id               = $controller->save_campaign( $campaign, true );
				break;

			case 'delete':
			case 'delete_bulk':
				do_action( 'revenue_before_delete_campaign', $campaign_id );
				if ( ! $controller->delete_campaign_trigger_indexes( $campaign_id ) ) {
					return new WP_Error( 'revenue_rest_invalid_campaign', __( 'Invalid Campaign.', 'revenue-pro' ), array( 'status' => 404 ) );
				}
				$result = revenue()->delete_campaign( $campaign_id );
				if ( ! $result ) {
					return new WP_Error( 'revenue_rest_cannot_delete', __( 'The campaign cannot be deleted.', 'revenue-pro' ), array( 'status' => 500 ) );
				}
				do_action( 'revenue_delete_campaign', $campaign_id );
				break;

			case 'status':
				$status_result = $this->update_status( $campaign_id, $request->get_param( 'status' ) );
				if ( is_wp_error( $status_result ) ) {
					return $status_result;
				}
				$status_changed = $status_result;
				break;

			default:
				return new WP_Error( 'revenue_rest_invalid_operation', __( 'Invalid campaign operation.', 'revenue-pro' ), array( 'status' => 400 ) );
		}

		if ( is_wp_error( $campaign_id ) || ! $campaign_id ) {
			return is_wp_error( $campaign_id ) ? $campaign_id : new WP_Error( 'revenue_rest_campaign_write_failed', __( 'The campaign could not be saved.', 'revenue-pro' ), array( 'status' => 500 ) );
		}

		$response = array(
			'handled'     => true,
			'campaign_id' => (int) $campaign_id,
		);
		if ( isset( $status_changed ) ) {
			$response['changed'] = $status_changed;
		}

		return $response;
	}

	/**
	 * Refuse writes that would create or extend a Pro campaign without a valid license.
	 *
	 * Deleting and pausing stay open so a merchant with a lapsed key can still clean up.
	 *
	 * @param string          $operation Write operation.
	 * @param WP_REST_Request $request   REST request.
	 * @return null|WP_Error
	 */
	private function check_license( $operation, $request ) {
		if ( License::is_valid() ) {
			return null;
		}

		if ( in_array( $operation, array( 'delete', 'delete_bulk' ), true ) ) {
			return null;
		}

		if ( 'status' === $operation && 'publish' !== $request->get_param( 'status' ) ) {
			return null;
		}

		return new WP_Error(
			'revenue_rest_campaign_license_required',
			License::is_expired()
				? __( 'Your WowRevenue license has expired. Renew it to keep using this campaign type.', 'revenue-pro' )
				: __( 'A valid WowRevenue license is required for this campaign type.', 'revenue-pro' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Update a Pro campaign status.
	 *
	 * @param int    $campaign_id Campaign ID.
	 * @param string $status      New status.
	 * @return bool|WP_Error
	 */
	private function update_status( $campaign_id, $status ) {
		global $wpdb;

		$status   = sanitize_text_field( $status );
		$campaign = revenue()->get_campaign_data( $campaign_id );
		if ( isset( $campaign['campaign_status'] ) && $campaign['campaign_status'] === $status ) {
			return false;
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'revenue_campaigns',
			array(
				'campaign_status'   => $status,
				'date_modified'     => current_time( 'mysql' ),
				'date_modified_gmt' => current_time( 'mysql', 1 ),
			),
			array( 'id' => $campaign_id )
		);

		if ( false === $result ) {
			return new WP_Error( 'revenue_rest_campaign_status_failed', __( 'The campaign status could not be updated.', 'revenue-pro' ), array( 'status' => 500 ) );
		}

		return true;
	}

	/**
	 * Add Pro campaign REST fields.
	 *
	 * @param array $properties Schema properties.
	 * @return array
	 */
	public function add_schema_properties( $properties ) {
		$string_fields = array(
			'is_required_products',
			'initial_product_selection',
			'reward_type',
			'spending_goal',
			'spending_goal_upsell_product_selection_strategy',
			'spending_goal_on_cta_click',
			'spending_goal_calculate_based_on',
			'spending_goal_discount_type',
			'spending_goal_discount_value',
			'spending_goal_is_upsell_enable',
			'mix_match_is_required_products',
			'mix_match_initial_product_selection',
			'fbt_is_trigger_product_required',
			'double_order_animation_type',
			'double_order_animation_delay_between',
			'double_order_animation_enabled',
			'double_order_success_message',
			'double_order_countdown_duration',
			'spending_goal_upsell_product_status',
			'show_confetti',
			'spending_goal_progress_show_icon',
			'is_show_free_shipping_bar',
			'show_close_icon',
			'enable_cta_button',
			'cta_button_text',
			'all_goals_complete_message',
		);

		foreach ( $string_fields as $field ) {
			$properties[ $field ] = array(
				'description' => __( 'Pro campaign setting.', 'revenue-pro' ),
				'type'        => 'string',
				'context'     => array( 'view', 'edit' ),
			);
		}

		$array_fields = array(
			'spending_goal_free_shipping_progress_messages',
			'spending_goal_discount_progress_messages',
			'mix_match_required_products',
		);

		foreach ( $array_fields as $field ) {
			$properties[ $field ] = array(
				'description' => __( 'Pro campaign setting.', 'revenue-pro' ),
				'type'        => 'array',
				'context'     => array( 'view', 'edit' ),
			);
		}

		$properties['initial_product_selection']['enum'] = array( 'all_product', 'no_product' );
		$properties['reward_type']['enum']               = array( 'free_shipping', 'discount' );

		$message_items = array(
			'type'       => 'object',
			'properties' => array(
				'status'  => array(
					'description' => __( 'Spending progress status.', 'revenue-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'message' => array(
					'description' => __( 'Spending progress message.', 'revenue-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);
		$properties['spending_goal_free_shipping_progress_messages']['items']    = $message_items;
		$properties['spending_goal_free_shipping_progress_messages']['maxItems'] = 3;
		$properties['spending_goal_discount_progress_messages']['items']         = $message_items;
		$properties['spending_goal_discount_progress_messages']['maxItems']      = 3;

		$properties['spending_goal_upsell_discount_configuration'] = array(
			'description' => __( 'Spending goal upsell discount configuration.', 'revenue-pro' ),
			'type'        => 'object',
			'context'     => array( 'view', 'edit' ),
			'items'       => array(
				'type'       => 'object',
				'properties' => array(
					'id'       => array(
						'description' => __( 'Offer row ID.', 'revenue-pro' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
					),
					'products' => array(
						'description' => __( 'Offered products.', 'revenue-pro' ),
						'type'        => 'array',
						'context'     => array( 'view', 'edit' ),
					),
					'quantity' => array(
						'description' => __( 'Offer quantity.', 'revenue-pro' ),
						'type'        => 'integer',
						'context'     => array( 'view', 'edit' ),
					),
					'value'    => array(
						'description' => __( 'Offer value.', 'revenue-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'type'     => array(
						'description' => __( 'Offer type.', 'revenue-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'tags'     => array(
						'description' => __( 'Offer tags.', 'revenue-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
					'desc'     => array(
						'description' => __( 'Offer description.', 'revenue-pro' ),
						'type'        => 'string',
						'context'     => array( 'view', 'edit' ),
					),
				),
			),
		);
		$properties['spending_goal_upsell_products']               = array(
			'description' => __( 'Spending goal upsell products.', 'revenue-pro' ),
			'type'        => 'mixed',
			'context'     => array( 'view', 'edit' ),
		);
		// upsell_products and upsell_products_status are declared by Free, which reads them too.

		return $properties;
	}

	/**
	 * Add Pro-owned campaign meta keys.
	 *
	 * @param string[] $keys Meta keys.
	 * @return string[]
	 */
	public function add_meta_keys( $keys ) {
		return array_values(
			array_unique(
				array_merge(
					$keys,
					array(
						'mix_match_is_required_products',
						'mix_match_initial_product_selection',
						'mix_match_required_products',
						'fbt_is_trigger_product_required',
						'double_order_animation_type',
						'double_order_animation_delay_between',
						'double_order_animation_enabled',
						'double_order_success_message',
						'double_order_countdown_duration',
						'reward_type',
						'spending_goal',
						'spending_goal_calculate_based_on',
						'spending_goal_discount_type',
						'spending_goal_discount_value',
						'spending_goal_free_shipping_progress_messages',
						'spending_goal_discount_progress_messages',
						'spending_goal_is_upsell_enable',
						'spending_goal_upsell_product_selection_strategy',
						'spending_goal_upsell_discount_configuration',
						'spending_goal_on_cta_click',
						'spending_goal_upsell_products',
						'spending_goal_upsell_product_status',
						'spending_goal_progress_show_icon',
						// upsell_products(_status), is_show_free_shipping_bar, enable_cta_button,
						// cta_button_text, show_close_icon, show_confetti and
						// all_goals_complete_message are registered by Free, which reads them too.
					)
				)
			)
		);
	}
}
