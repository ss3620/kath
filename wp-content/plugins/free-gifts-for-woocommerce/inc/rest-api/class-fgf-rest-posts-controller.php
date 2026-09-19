<?php

/**
 * Rest API - Posts Controller.
 * 
 * @since 11.1.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('FGF_REST_POSTS_Controller')) {

	/**
	 * Class.
	 * 
	 * @since 9.0.0
	 */
	class FGF_REST_Posts_Controller extends WC_REST_Controller {

		/**
		 * Endpoint namespace.
		 *
		 * @since 9.0.0
		 * @var string
		 */
		protected $namespace = 'fgf';

		/**
		 * Route base.
		 *
		 * @since 9.0.0
		 * @var string
		 */
		protected $rest_base = '';

		/**
		 * Post type.
		 *
		 * @since 9.0.0
		 * @var string
		 */
		protected $post_type = '';

		/**
		 * Get object.
		 *
		 * @since 11.1.0
		 * @param int $id Object ID.
		 * @return Object
		 */
		protected function get_object( $id ) {
			return get_post(intval($id));
		}

		/**
		 * Checks if a given request has access to get a specific item.
		 *
		 * @since 9.0.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
		 */
		public function get_item_permissions_check( $request ) {
			$object = $this->get_object($request['id']);

			if ($object && $object->exists() && !wc_rest_check_post_permissions($this->post_type, 'read', $object->get_id())) {
				return new WP_Error('fgf_rest_cannot_view', __('Sorry, you cannot view this resource.', 'free-gifts-for-woocommerce'), array( 'status' => rest_authorization_required_code() ));
			}

			return true;
		}

		/**
		 * Checks if a given request has access to get specific post type items.
		 *
		 * @since 9.0.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return true|WP_Error True if the request has read access for the item, WP_Error object otherwise.
		 */
		public function get_items_permissions_check( $request ) {
			if (!wc_rest_check_post_permissions($this->post_type, 'read')) {
				return new WP_Error('fgf_rest_cannot_view', __('Sorry, you cannot list resources.', 'free-gifts-for-woocommerce'), array( 'status' => rest_authorization_required_code() ));
			}

			return true;
		}

		/**
		 * Check if a given request has access to create an item.
		 *
		 * @since 11.1.0
		 * @param  WP_REST_Request $request Full details about the request.
		 * @return WP_Error|boolean
		 */
		public function create_item_permissions_check( $request ) {
			$object = $this->get_object((int) $request['id']);

			if ($object && $object->exists() && !wc_rest_check_post_permissions($this->post_type, 'create', $object->get_id())) {
				return new WP_Error('fgf_rest_cannot_create', __('Sorry, you are not allowed to create this resource.', 'free-gifts-for-woocommerce'), array( 'status' => rest_authorization_required_code() ));
			}

			return true;
		}

		/**
		 * Check if a given request has access to update an item.
		 *
		 * @since 11.1.0
		 * @param  WP_REST_Request $request Full details about the request.
		 * @return WP_Error|boolean
		 */
		public function update_item_permissions_check( $request ) {
			$object = $this->get_object((int) $request['id']);

			if ($object && $object->exists() && !wc_rest_check_post_permissions($this->post_type, 'edit', $object->get_id())) {
				return new WP_Error('fgf_rest_cannot_edit', __('Sorry, you are not allowed to edit this resource.', 'free-gifts-for-woocommerce'), array( 'status' => rest_authorization_required_code() ));
			}

			return true;
		}

		/**
		 * Checks if a given request has access to delete a specific item.
		 *
		 * @since 11.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return true|WP_Error True if the request has delete access for the item, WP_Error object otherwise.
		 */
		public function delete_item_permissions_check( $request ) {
			$object = $this->get_object((int) $request['id']);

			if ($object && $object->exists() && !wc_rest_check_post_permissions($this->post_type, 'delete', $object->get_id())) {
				return new WP_Error('fgf_rest_cannot_delete', __('Sorry, you are not allowed to delete this resource.', 'free-gifts-for-woocommerce'), array( 'status' => rest_authorization_required_code() ));
			}

			return true;
		}

		/**
		 * Retrieves one item from the collection.
		 *         
		 * @since 9.0.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_item( $request ) {
			$object = $this->get_object($request['id']);
			if (!$object->exists()) {
				return new WP_Error('fgf_rest_rules_invalid_id', __('Invalid ID', 'free-gifts-for-woocommerce'), array( 'status' => 404 ));
			}

			return rest_ensure_response($this->prepare_item_for_response($object, $request));
		}

		/**
		 * Create a single item.
		 *
		 * @since 11.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error|WP_REST_Response
		 */
		public function create_item( $request ) {
			if (!empty($request['id'])) {
				/* translators: %s: post type */
				return new WP_Error("fgf_rest_{$this->post_type}_exists", sprintf(__('Cannot create existing %s.', 'free-gifts-for-woocommerce'), $this->post_type), array( 'status' => 400 ));
			}

			try {
				$object_id = $this->save_object($request);
				if (is_wp_error($object_id)) {
					return $object_id;
				}

				$new_object = $this->get_object($object_id);

				/**
				 * Fires after a single item is created via the REST API.
				 *
				 * @since 11.1.0
				 * @param object $new_object.
				 * @param WP_REST_Request $request Request object.
				 */
				do_action("fgf_rest_created_{$this->post_type}_item", $new_object, $request);

				return rest_ensure_response($this->prepare_item_for_response($new_object, $request));
			} catch (WC_Data_Exception $e) {
				return new WP_Error($e->getErrorCode(), $e->getMessage(), $e->getErrorData());
			} catch (WC_REST_Exception $e) {
				return new WP_Error($e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ));
			}
		}

		/**
		 * Update a single item.
		 *
		 * @since 11.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error|WP_REST_Response
		 */
		public function update_item( $request ) {
			$object = $this->get_object($request['id']);
			if (!$object->exists()) {
				return new WP_Error("fgf_rest_{$this->post_type}_invalid_id", __('ID is invalid.', 'free-gifts-for-woocommerce'), array( 'status' => 400 ));
			}

			try {
				$object_id = $this->save_object($request);
				if (is_wp_error($object_id)) {
					return $object_id;
				}

				$new_object = $this->get_object($object_id);

				/**
				 * Fires after a single item is updated via the REST API.
				 *
				 * @since 11.1.0
				 * @param object $new_object.
				 * @param WP_REST_Request $request Request object.
				 */
				do_action("fgf_rest_updated_{$this->post_type}_item", $new_object, $request);

				return rest_ensure_response($this->prepare_item_for_response($new_object, $request));
			} catch (WC_Data_Exception $e) {
				return new WP_Error($e->getErrorCode(), $e->getMessage(), $e->getErrorData());
			} catch (WC_REST_Exception $e) {
				return new WP_Error($e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ));
			}
		}

		/**
		 * Save object.
		 * 
		 * @since 11.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error|int
		 */
		public function save_object( $request ) {
			try {
				// Validate the request data fields.
				$this->validate_request($request);

				$object = $this->prepare_item_for_database($request);
				if (is_wp_error($object)) {
					return $object;
				}

				$object->save();

				return $object->get_id();
			} catch (WC_Data_Exception $e) {
				return new WP_Error($e->getErrorCode(), $e->getMessage(), $e->getErrorData());
			} catch (WC_REST_Exception $e) {
				return new WP_Error($e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ));
			}
		}

		/**
		 * Only return writable props from schema.
		 * 
		 * @since 11.1.0
		 * @param  array $schema
		 * @return bool
		 */
		protected function filter_writable_props( $schema ) {
			return empty($schema['readonly']);
		}

		/**
		 * Validate request.
		 * 
		 * @since 11.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error
		 */
		public function validate_request( $request ) {
		}

		/**
		 * Prepare item for database.
		 * 
		 * @since 11.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error|int
		 */
		public function prepare_item_for_database( $request ) {
		}

		/**
		 * Delete a single item.
		 *
		 * @since 11.1.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_REST_Response|WP_Error
		 */
		public function delete_item( $request ) {
			$force = (bool) $request['force'];
			$object = $this->get_object($request['id']);

			if (empty($request['id']) || !$object->exists()) {
				return new WP_Error('fgf_rest_rules_invalid_id', __('ID is invalid.', 'free-gifts-for-woocommerce'), array( 'status' => 404 ));
			}

			$response = $this->prepare_item_for_response($object, $request);

			// If we're forcing, then delete permanently.
			if ($force) {
				$result = wp_delete_post($object->get_id(), true);
			} else {
				// Otherwise, only trash if we haven't already.
				if ('trash' === $object->get_status()) {
					/* translators: %s: post type */
					return new WP_Error('fgf_rest_rules_already_trashed', sprintf(__('The %s has already been deleted.', 'free-gifts-for-woocommerce'), $this->post_type), array( 'status' => 410 ));
				}

				// (Note that internally this falls through to `wp_delete_post` if
				// the trash is disabled.)
				$result = wp_trash_post($object->get_id());
			}

			if (!$result) {
				/* translators: %s: post type */
				return new WP_Error('fgf_rest_cannot_delete', sprintf(__('The %s cannot be deleted.', 'free-gifts-for-woocommerce'), $this->post_type), array( 'status' => 500 ));
			}

			return $response;
		}

		/**
		 * Get a collection of posts.
		 *
		 * @since 9.0.0
		 * @param WP_REST_Request $request Full details about the request.
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_items( $request ) {
			$args = array(
				'post_type' => $this->post_type,
				'post_status' => fgf_get_rule_statuses(),
				'posts_per_page' => '-1',
				'fields' => 'ids',
				'orderby' => 'menu_order',
				'order' => 'ASC',
			);

			$rule_ids = get_posts($args);

			$rules = array();
			foreach ($rule_ids as $rule_id) {
				$rule = $this->get_object($rule_id);
				if (!$rule->exists()) {
					continue;
				}

				$rules[] = $this->prepare_item_for_response($rule, $request);
			}

			return rest_ensure_response($rules);
		}
	}

}
