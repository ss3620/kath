<?php	// phpcs:ignore
/**
 * RequestAPI Action.
 *
 * @package PRAD\Options
 * @since 1.0.0
 */

namespace PRAD\Includes\Restapi;

use PRAD\Includes\Analytics;
use PRAD\Includes\Xpo;
use WP_REST_Response;
use WC_Data_Store;

defined( 'ABSPATH' ) || exit;
/**
 * RequestAPI class to handle API requests.
 *
 * @since 1.0.0
 */
class RequestApi {

	/**
	 * Initialize the RequestAPI class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	/**
	 * Register hook
	 *
	 * @since 1.0.0
	 */
	public function register_route() {
		$routes = array(
			// Single Product Page file upload.
			array(
				'endpoint'            => 'upload-file',
				'methods'             => 'POST',
				'callback'            => array( $this, 'upload_files_callback' ),
				'permission_callback' => '__return_true',
			),
			array(
				'endpoint'            => 'set_analytics',
				'methods'             => 'POST',
				'callback'            => array( $this, 'set_analytics_data_callback' ),
				'permission_callback' => '__return_true',
			),

			// Get Analytics Data.
			array(
				'endpoint'            => 'get_analytics',
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_analytics_data_callback' ),
				'permission_callback' => array( $this, 'prad_get_view_only_permissions' ),
			),

			// Backend Option Listing.
			array(
				'endpoint'            => 'option_list',
				'methods'             => 'POST',
				'callback'            => array( $this, 'option_listing_callback' ),
				'permission_callback' => array( $this, 'prad_get_view_only_permissions' ),
			),
			// Duplicate List.
			array(
				'endpoint'            => 'list_duplicate',
				'methods'             => 'POST',
				'callback'            => array( $this, 'list_duplicate_callback' ),
				'permission_callback' => array( $this, 'prad_get_admin_permissions' ),
			),
			// Import List.
			array(
				'endpoint'            => 'list_import',
				'methods'             => 'POST',
				'callback'            => array( $this, 'list_import_callback' ),
				'permission_callback' => array( $this, 'prad_get_admin_permissions' ),
			),
			// Delete List.
			array(
				'endpoint'            => 'list_delete',
				'methods'             => 'POST',
				'callback'            => array( $this, 'list_delete_callback' ),
				'permission_callback' => array( $this, 'prad_get_admin_permissions' ),
			),
			// Update List.
			array(
				'endpoint'            => 'list_update',
				'methods'             => 'POST',
				'callback'            => array( $this, 'list_update_callback' ),
				'permission_callback' => array( $this, 'prad_get_admin_permissions' ),
			),
			// Get Option Edit Page Data.
			array(
				'endpoint'            => 'get_option',
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_option_callback' ),
				'permission_callback' => array( $this, 'prad_get_view_only_permissions' ),
			),
			// Save Option Edit Page Data.
			array(
				'endpoint'            => 'set_option',
				'methods'             => 'POST',
				'callback'            => array( $this, 'set_option_callback' ),
				'permission_callback' => array( $this, 'prad_get_admin_permissions' ),
			),
			// Option Assign Search Products.
			array(
				'endpoint'            => 'assign_search',
				'methods'             => 'POST',
				'callback'            => array( $this, 'assign_search_callback' ),
				'permission_callback' => array( $this, 'prad_get_view_only_permissions' ),
			),
			array(
				'endpoint'            => 'product_search',
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_product_search_callback' ),
				'permission_callback' => array( $this, 'prad_get_view_only_permissions' ),
			),
			array(
				'endpoint'            => 'products_details',
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_products_details_callback' ),
				'permission_callback' => array( $this, 'prad_get_view_only_permissions' ),
			),
			// Get Assign Data.
			array(
				'endpoint'            => 'get_assign',
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_assign_product_callback' ),
				'permission_callback' => array( $this, 'prad_get_view_only_permissions' ),
			),
			// Set Assign Data.
			array(
				'endpoint'            => 'set_assign',
				'methods'             => 'POST',
				'callback'            => array( $this, 'set_assign_product_callback' ),
				'permission_callback' => array( $this, 'prad_get_admin_permissions' ),
			),
			// Get Global Settings.
			array(
				'endpoint'            => 'get_global',
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_global_callback' ),
				'permission_callback' => array( $this, 'prad_get_view_only_permissions' ),
			),
			// Set Global Settings.
			array(
				'endpoint'            => 'set_global',
				'methods'             => 'POST',
				'callback'            => array( $this, 'set_global_callback' ),
				'permission_callback' => array( $this, 'prad_get_admin_permissions' ),
			),

			// Get Global Settings.
			array(
				'endpoint'            => 'get_settings',
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_settings_callback' ),
				'permission_callback' => array( $this, 'prad_get_view_only_permissions' ),
			),
			// Set Global Settings.
			array(
				'endpoint'            => 'set_settings',
				'methods'             => 'POST',
				'callback'            => array( $this, 'set_settings_callback' ),
				'permission_callback' => array( $this, 'prad_get_admin_permissions' ),
			),
			// Product Image Compatibility.
			array(
				'endpoint'            => 'product_image',
				'methods'             => 'POST',
				'callback'            => array( $this, 'product_image_callback' ),
				'permission_callback' => array( $this, 'prad_get_admin_permissions' ),
			),
			array(
				'endpoint'            => 'product_link',
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_product_link_callback' ),
				'permission_callback' => array( $this, 'prad_get_view_only_permissions' ),
			),

			// Dismiss the builder onboarding tour.
			array(
				'endpoint'            => 'dismiss_tour',
				'methods'             => 'POST',
				'callback'            => array( $this, 'dismiss_tour_callback' ),
				'permission_callback' => array( $this, 'prad_get_admin_permissions' ),
			),
		);

		foreach ( $routes as $route ) {
			register_rest_route(
				'prad',
				$route['endpoint'],
				array(
					array(
						'methods'             => $route['methods'],
						'callback'            => $route['callback'],
						'permission_callback' => $route['permission_callback'],
					),
				)
			);
		}
	}

	/**
	 * Check permissions for endpoint.
	 *
	 * @return bool
	 */
	public function prad_get_view_only_permissions() {
		return current_user_can( Xpo::prad_old_view_permisson_handler() );
	}
	/**
	 * Check permissions for Edit, Update endpoint.
	 *
	 * @return bool
	 */
	public function prad_get_admin_permissions() {
		return current_user_can( Xpo::prad_manage_admin_permisson_handler() );
	}

	/**
	 * Retrieves option data for a given post ID.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response containing option data or an error message.
	 */
	public function get_option_callback( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$id     = isset( $params['id'] ) ? sanitize_text_field( $params['id'] ) : 0;
		$post   = get_post( $id );

		if ( ! $post ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post not found.', 'product-addons' ),
				),
				404
			);
		}

		// Prepare the response data.
		$data = array(
			'id'      => $post->ID,
			'title'   => $post->post_title,
			'status'  => $post->post_status,
			'content' => get_post_meta( $id, 'prad_addons_blocks', true ),
			'error'   => json_last_error_msg(),
		);

		// Return the success response with the post data.
		return new WP_REST_Response(
			array(
				'success' => true,
				'post'    => $data,
			),
			200
		);
	}

	/**
	 * Updates or creates option data.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response indicating success or failure.
	 */
	public function set_option_callback( \WP_REST_Request $request ) {
		// Retrieve and sanitize request parameters.
		$params          = $request->get_params();
		$id              = isset( $params['id'] ) ? sanitize_text_field( $params['id'] ) : '';
		$title           = isset( $params['title'] ) ? sanitize_text_field( $params['title'] ) : 'Untitled';
		$status          = isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : 'draft';
		$content         = isset( $params['content'] ) && is_array( $params['content'] ) ? product_addons()->sanitize_rest_params( $params['content'] ) : '';
		$required_fields = isset( $params['required_fields'] ) && is_array( $params['required_fields'] ) ? product_addons()->sanitize_rest_params( $params['required_fields'] ) : '';
		$css             = isset( $params['css'] ) && is_string( $params['css'] ) ? sanitize_textarea_field( $params['css'] ) : '';
		$nonce           = isset( $params['wpnonce'] ) ? sanitize_text_field( $params['wpnonce'] ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'prad-nonce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid or missing nonce.', 'product-addons' ),
				),
				403
			);
		}

		// Prepare the attributes for the post.
		$attr    = array(
			'post_title'   => $title,
			'post_status'  => $status,
			'post_content' => $title,
			'post_type'    => 'prad_option',
		);
		$message = 'publish' === $status
				? __( 'Option set updated & published.', 'product-addons' )
				: __( 'Option set updated & saved as a draft.', 'product-addons' );
		if ( 'new' === $id ) {
			$id = wp_insert_post( $attr );
			if ( is_wp_error( $id ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'Failed to create a new option.', 'product-addons' ),
					),
					400
				);
			}

			update_option( 'prad_first_option_created', 'yes' );
			update_post_meta( $id, 'prad_addons_blocks', $content );
			$required_options = $required_fields ? $required_fields : array();
			update_post_meta( $id, 'prad_required_options', $required_options );
			if ( $css ) {
				update_post_meta( $id, 'prad_addons_css', $css );
			}
			do_action( 'prad_handle_cache_on_save' );

			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => $message,
					'id'      => $id,
				),
				200
			);
		} else {
			$attr['ID'] = $id;
			$update     = wp_update_post( $attr, true );

			if ( is_wp_error( $update ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => __( 'Failed to update the option.', 'product-addons' ),
					),
					400
				);
			}

			if ( $css ) {
				update_post_meta( $id, 'prad_addons_css', $css );
			}

			update_post_meta( $id, 'prad_addons_blocks', $content );
			$required_options = $required_fields ? $required_fields : array();
			update_post_meta( $id, 'prad_required_options', $required_options );
			do_action( 'prad_handle_cache_on_save' );

			return new WP_REST_Response(
				array(
					'success' => true,
					'content' => $content,
					'message' => $message,
				),
				200
			);
		}
	}

	/**
	 * Updates the status of multiple options based on provided IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response indicating success or failure of the update.
	 */
	public function list_update_callback( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$ids    = isset( $params['ids'] ) ? sanitize_text_field( $params['ids'] ) : '';
		$status = isset( $params['status'] ) ? sanitize_text_field( $params['status'] ) : '';
		$nonce  = isset( $params['wpnonce'] ) ? sanitize_text_field( $params['wpnonce'] ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'prad-nonce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid or missing nonce.', 'product-addons' ),
				),
				403
			);
		}

		if ( empty( $ids ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No IDs provided.', 'product-addons' ),
				),
				400
			);
		}

		// Convert IDs to an array and update each post.
		$ids_array = explode( ',', $ids );
		foreach ( $ids_array as $id ) {
			$attr = array(
				'ID'          => (int) $id,
				'post_status' => ( 'active' === $status ) ? 'publish' : 'draft',
			);

			$update = wp_update_post( $attr, true );

			if ( is_wp_error( $update ) ) {
				return new WP_REST_Response(
					array(
						'success' => false,
						'message' => sprintf(
							/* translators: %1s - Post Id, %2s - Error Message */
							__( 'Failed to update post ID: %1$s. Error: %2$s', 'product-addons' ),
							$id,
							$update->get_error_message()
						),
					),
					400
				);
			}
		}

		do_action( 'prad_handle_cache_on_save' );
		// Return success response.
		return new WP_REST_Response(
			array(
				'success' => true,
				'status'  => $status,
				'message' => __( 'Items updated successfully.', 'product-addons' ),
			),
			200
		);
	}

	/**
	 * Deletes multiple options based on provided IDs.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response indicating success or failure of the deletion.
	 */
	public function list_delete_callback( \WP_REST_Request $request ) {
		// Retrieve and sanitize request parameters.
		$params = $request->get_params();
		$ids    = isset( $params['ids'] ) ? sanitize_text_field( $params['ids'] ) : '';
		$nonce  = isset( $params['wpnonce'] ) ? sanitize_text_field( $params['wpnonce'] ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'prad-nonce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid or missing nonce.', 'product-addons' ),
				),
				403
			);
		}

		if ( empty( $ids ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No IDs provided.', 'product-addons' ),
				),
				400
			);
		}

		// Convert IDs to an array and delete each post.
		$gallery_image_data = get_option( 'prad_product_image_update_data', array() );
		$ids_array          = explode( ',', $ids );
		foreach ( $ids_array as $id ) {
			$id = (int) $id; // Ensure ID is an integer.

			if ( isset( $gallery_image_data[ $id ] ) ) {
				unset( $gallery_image_data[ $id ] );
			}

			/**
			 * Fires before deleting a post with a specific ID.
			 *
			 * @param int $id The ID of the post to be deleted.
			 */
			do_action( 'prad_delete_option_product_meta', $id );

			wp_delete_post( $id, true );
		}

		update_option( 'prad_product_image_update_data', $gallery_image_data );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Items deleted successfully.', 'product-addons' ),
			),
			200
		);
	}


	/**
	 * Duplicates an option based on the provided ID.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response indicating success or failure of the duplication.
	 */
	public function list_duplicate_callback( \WP_REST_Request $request ) {
		// Retrieve and sanitize the request parameter.
		$params  = $request->get_params();
		$id      = isset( $params['id'] ) ? sanitize_text_field( $params['id'] ) : '';
		$content = isset( $params['content'] ) && is_array( $params['content'] ) ? product_addons()->sanitize_rest_params( $params['content'] ) : array();
		$nonce   = isset( $params['wpnonce'] ) ? sanitize_text_field( $params['wpnonce'] ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'prad-nonce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid or missing nonce.', 'product-addons' ),
				),
				403
			);
		}

		if ( empty( $id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No ID provided.', 'product-addons' ),
				),
				400
			);
		}

		// Retrieve the post object and proceed with duplication.
		$post = get_post( $id );
		if ( ! $post ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Post not found.', 'product-addons' ),
				),
				404
			);
		}

		// Set up the arguments for duplicating the post.
		$args = array(
			'post_author'  => $post->post_author,
			'post_content' => $post->post_content,
			'post_name'    => $post->post_name,
			'post_status'  => 'draft',
			'post_title'   => $post->post_title . ' Copy',
			'post_type'    => $post->post_type,
		);

		// Insert the new post (duplicate).
		$new_id = wp_insert_post( $args );

		if ( is_wp_error( $new_id ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => sprintf(
						/* translators: %1s - Post Id */
						__( 'Failed to duplicate post ID: %s.', 'product-addons' ),
						$id
					),
				),
				400
			);
		}

		// Copy the custom meta data.
		update_option( 'prad_first_option_created', 'yes' );
		$blocks = $content ? $content : get_post_meta( $id, 'prad_addons_blocks', true );
		update_post_meta( $new_id, 'prad_addons_blocks', $blocks );

		// Return success response.
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Item duplicated successfully.', 'product-addons' ),
				'new_id'  => $new_id,
			),
			200
		);
	}
	/**
	 * Import List
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response indicating success or failure of the duplication.
	 */
	public function list_import_callback( \WP_REST_Request $request ) {
		$params  = $request->get_params();
		$title   = isset( $params['title'] ) ? sanitize_text_field( $params['title'] ) : '';
		$content = isset( $params['content'] ) && is_array( $params['content'] ) ? product_addons()->sanitize_rest_params( $params['content'] ) : array();
		$nonce   = isset( $params['wpnonce'] ) ? sanitize_text_field( $params['wpnonce'] ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'prad-nonce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid or missing nonce.', 'product-addons' ),
				),
				403
			);
		}

		$args = array(
			'post_status' => 'draft',
			'post_title'  => $title . ' Imported',
			'post_type'   => 'prad_option',
		);

		$new_id = wp_insert_post( $args );
		update_option( 'prad_first_option_created', 'yes' );
		update_post_meta( $new_id, 'prad_addons_blocks', $content );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Imported successfully.', 'product-addons' ),
				'new_id'  => $new_id,
			),
			200
		);
	}

	/**
	 * Retrieves a list of options with search and pagination functionality.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response containing the list of options and pagination info.
	 */
	public function option_listing_callback( \WP_REST_Request $request ) {
		$params     = $request->get_params();
		$search     = isset( $params['search'] ) ? sanitize_text_field( $params['search'] ) : '';
		$paged      = isset( $params['page'] ) ? sanitize_text_field( $params['page'] ) : 1;
		$per_page   = isset( $params['per_page'] ) ? sanitize_text_field( $params['per_page'] ) : 3;
		$order      = isset( $params['order'] ) ? sanitize_text_field( $params['order'] ) : 'DESC';
		$product_id = isset( $params['product_id'] ) ? absint( $params['product_id'] ) : 0;
		$nonce      = isset( $params['wpnonce'] ) ? sanitize_text_field( $params['wpnonce'] ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'prad-nonce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid or missing nonce.', 'product-addons' ),
				),
				403
			);
		}

		$args = array(
			'post_type'      => 'prad_option',
			'posts_per_page' => $per_page,
			'order'          => $order,
			'orderby'        => 'ID',
			'post_status'    => array( 'publish', 'draft' ),
			'paged'          => $paged,
		);

		if ( $product_id ) {
			$args['post__in'] = product_addons()->get_product_option_ids( $product_id );
			$args['post__in'] = ! empty( $args['post__in'] ) ? $args['post__in'] : array( 0 );
		}

		$id_search_filter = null;

		if ( ! empty( $search ) ) {
			if ( ctype_digit( $search ) ) {
				$id_search_filter = function ( $where ) use ( $search ) {
					global $wpdb;
					return $where . $wpdb->prepare( " AND {$wpdb->posts}.ID LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
				};
				add_filter( 'posts_where', $id_search_filter );
			} else {
				$args['s'] = $search;
			}
		}

		$query = new \WP_Query( $args );

		if ( $id_search_filter ) {
			remove_filter( 'posts_where', $id_search_filter );
		}
		$data       = array();
		$all_blocks = array();
		$page_num   = 0;

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$id                = get_the_ID();
				$blocks            = get_post_meta( $id, 'prad_addons_blocks', true );
				$all_blocks[ $id ] = $blocks;
				$data[]            = array(
					'id'       => $id,
					'title'    => get_the_title(),
					'status'   => get_post_status() === 'publish',
					'options'  => is_string( $blocks ) ? substr_count( $blocks, 'blockid' ) : substr_count( wp_json_encode( $blocks ), 'blockid' ),
					'assigned' => product_addons()->get_assigned_product_data( $id ),
				);
			}
			$page_num = $query->max_num_pages;

			wp_reset_postdata();
		}

		return new WP_REST_Response(
			array(
				'success'    => true,
				'page'       => $page_num,
				'posts'      => $data,
				'all_blocks' => $all_blocks,
			),
			200
		);
	}

	/**
	 * Searches for products or categories based on the provided keyword and trigger type.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response containing the search results.
	 */
	public function assign_search_callback( \WP_REST_Request $request ) {
		$params           = $request->get_params();
		$trigger_type     = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'products';
		$search_keyword   = isset( $params['term'] ) ? sanitize_text_field( $params['term'] ) : '';
		$limit            = isset( $params['limit'] ) ? absint( $params['limit'] ) : 60;
		$tax_type         = isset( $params['tax_type'] ) ? sanitize_text_field( $params['tax_type'] ) : '';
		$tax_term_ids_raw = isset( $params['tax_term_ids'] ) && is_array( $params['tax_term_ids'] ) ? $params['tax_term_ids'] : array();
		$tax_term_ids     = array_map( 'absint', $tax_term_ids_raw );
		$taxonomy_map     = product_addons()->get_assign_trigger_taxonomies();

		$response_data = array();
		switch ( $trigger_type ) {
			case 'products':
				$tax_filter = array();
				if ( $tax_term_ids && isset( $taxonomy_map[ $tax_type ] ) ) {
					$tax_filter = array(
						'taxonomy' => $taxonomy_map[ $tax_type ],
						'term_ids' => $tax_term_ids,
					);
				}
				$response_data = product_addons()->get_searched_products( $search_keyword, false, $limit, array(), $tax_filter );
				break;
			default:
				if ( isset( $taxonomy_map[ $trigger_type ] ) ) {
					$response_data = product_addons()->get_searched_categories(
						array(
							'term'         => $search_keyword,
							'limit'        => $limit,
							'includes'     => '',
							'trigger_type' => $trigger_type,
						)
					);
				}
				break;
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $response_data,
			),
			200
		);
	}

	/**
	 * Searches for products or categories based on the provided keyword and trigger type.
	 *
	 * @since 1.0.3
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response containing the search results.
	 */
	public function get_product_search_callback( \WP_REST_Request $request ) {
		$params         = $request->get_params();
		$search_keyword = isset( $params['term'] ) ? sanitize_text_field( $params['term'] ) : '';
		$limit          = isset( $params['limit'] ) ? absint( $params['limit'] ) : 5;
		$exclude_ids    = isset( $params['excludes'] ) ? $params['excludes'] : array();

		// Load the product data store.
		$data_store = WC_Data_Store::load( 'product' );

		$include_ids = array();
		$limit       = '5';
		$ids         = $data_store->search_products( $search_keyword, '', false, false, $limit, $include_ids, $exclude_ids );
		$products    = array();

		foreach ( $ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				$datas = array(
					'id'         => $product_id,
					'url'        => get_permalink( $product_id ),
					'value'      => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
					'img'        => wp_get_attachment_url( $product->get_image_id() ),
					'isVariable' => $product->is_type( 'variable' ),
				);

				if ( $product->is_type( 'variable' ) ) {
					$available_variations = $product->get_available_variations();
					$variations_data      = array();

					foreach ( $available_variations as $variation_data ) {
						$variation_id = $variation_data['variation_id'];
						$variation    = wc_get_product( $variation_id );

						if ( $variation && $variation->is_purchasable() && $variation->is_in_stock() ) {
							$variation_formatted = wc_get_formatted_variation( $variation, true, false, true );
							$variations_data[]   = array(
								'id'         => $variation_id,
								'url'        => get_permalink( $variation_id ),
								'value'      => rawurldecode( wp_strip_all_tags( $variation_formatted ? $variation->get_name() . ' - ' . $variation_formatted : $variation->get_name() ) ),
								'img'        => wp_get_attachment_url( $variation->get_image_id() ),
								'attributes' => wc_get_product_variation_attributes( $variation_id ),
								'regular'    => $variation->get_regular_price( 'edit' ),
								'sale'       => $variation->get_sale_price( 'edit' ),
							);
						}
					}
					$datas['variation'] = $variations_data;
				}
				$products[] = $datas;
			}
		}

		return $products;
	}

	/**
	 * Get product link callback - Returns the first available product link based on assignment data.
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return \WP_REST_Response Response containing product link and success status.
	 */
	public function get_product_link_callback( \WP_REST_Request $request ) {
		$params        = $request->get_params();
		$assigned_data = isset( $params['assignedData'] ) && is_array( $params['assignedData'] ) ? product_addons()->sanitize_rest_params( $params['assignedData'] ) : array();
		$option_id     = isset( $params['optionId'] ) ? sanitize_text_field( $params['optionId'] ) : '';

		// Validate assigned data.
		if ( empty( $assigned_data ) || ! isset( $assigned_data['aType'] ) ) {
			return new WP_REST_Response(
				array(
					'success'   => false,
					'published' => false,
					'message'   => __( 'Invalid assigned data.', 'product-addons' ),
				),
				400
			);
		}
		$is_published = false;
		if ( $option_id && 'new' !== $option_id ) {
			$is_published = 'publish' === get_post_status( $option_id );
		}

		$assign_type = $assigned_data['aType'];

		$product_link = '';
		if ( 'specific_product' !== $assign_type && $is_published ) {
			$product_link = $this->get_first_product_link( $assigned_data );
		}

		return new WP_REST_Response(
			array(
				'success'     => true,
				'published'   => $is_published,
				'productLink' => $product_link,
			),
			200
		);
	}

	/**
	 * Get the first available product link based on assignment type.
	 *
	 * @param array $assigned_data Assignment data containing type and includes/excludes.
	 * @return string Product permalink or empty string if no product found.
	 */
	private function get_first_product_link( $assigned_data ) {
		$exclude_ids = $this->parse_exclude_ids( $assigned_data );

		switch ( $assigned_data['aType'] ) {
			case 'all_product':
				return $this->get_first_simple_product_link( $exclude_ids );
			default:
				return isset( product_addons()->get_assign_taxonomies()[ $assigned_data['aType'] ] )
					? $this->get_first_taxonomy_product_link( $assigned_data, $exclude_ids )
					: '';
		}
	}

	/**
	 * Parse exclude IDs from assigned data, handling both new object format and legacy format.
	 *
	 * @param array $assigned_data Assignment data.
	 * @return array Array of exclude product IDs.
	 */
	private function parse_exclude_ids( $assigned_data ) {
		$exclude_ids = array();

		if ( empty( $assigned_data['excludes'] ) || ! is_array( $assigned_data['excludes'] ) ) {
			return $exclude_ids;
		}

		foreach ( $assigned_data['excludes'] as $exclude_item ) {
			if ( is_array( $exclude_item ) && isset( $exclude_item['item_id'] ) ) {
				$exclude_ids[] = absint( $exclude_item['item_id'] );
			} elseif ( is_numeric( $exclude_item ) ) {
				// Fallback for legacy format.
				$exclude_ids[] = absint( $exclude_item );
			}
		}

		return array_filter( $exclude_ids );
	}

	/**
	 * Get the first simple product link, excluding specified products.
	 *
	 * @param array $exclude_ids Product IDs to exclude.
	 * @return string Product permalink or empty string.
	 */
	private function get_first_simple_product_link( $exclude_ids ) {
		$data_store  = WC_Data_Store::load( 'product' );
		$product_ids = $data_store->search_products( '', '', true, false, 99, array(), $exclude_ids );

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product && $product->get_type() !== 'variation' && $product->get_status() === 'publish' ) {
				return get_permalink( $product_id );
			}
		}

		return '';
	}

	/**
	 * Get the first product link from taxonomy terms.
	 *
	 * @param array $assigned_data Assignment data.
	 * @param array $exclude_ids   Product IDs to exclude.
	 * @return string Product permalink or empty string.
	 */
	private function get_first_taxonomy_product_link( $assigned_data, $exclude_ids ) {
		if ( empty( $assigned_data['includes'] ) || ! is_array( $assigned_data['includes'] ) ) {
			return '';
		}

		$taxonomy = product_addons()->get_assign_taxonomies()[ $assigned_data['aType'] ] ?? '';
		if ( ! $taxonomy ) {
			return '';
		}

		// Parse term IDs and get products in one optimized flow.
		$all_product_ids = array();

		foreach ( $assigned_data['includes'] as $include_item ) {
			$term_id = 0;
			if ( is_array( $include_item ) && isset( $include_item['item_id'] ) ) {
				$term_id = absint( $include_item['item_id'] );
			} elseif ( is_numeric( $include_item ) ) {
				$term_id = absint( $include_item );
			}

			if ( $term_id ) {
				$term_products = get_objects_in_term( $term_id, $taxonomy );
				if ( ! empty( $term_products ) ) {
					$all_product_ids = array_merge( $all_product_ids, $term_products );
				}
			}
		}

		// Process and find first valid product.
		if ( ! empty( $all_product_ids ) ) {
			$unique_products   = array_unique( array_map( 'absint', $all_product_ids ) );
			$filtered_products = ! empty( $exclude_ids ) ? array_diff( $unique_products, $exclude_ids ) : $unique_products;

			foreach ( $filtered_products as $product_id ) {
				$product = wc_get_product( $product_id );
				if ( $product && $product->get_type() !== 'variation' && $product->get_status() === 'publish' ) {
					return get_permalink( $product_id );
				}
			}
		}

		return '';
	}

	/**
	 * Searches for products or categories based on the provided keyword and trigger type.
	 *
	 * @since 1.0.3
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response containing the search results.
	 */
	public function get_products_details_callback( \WP_REST_Request $request ) {
		$params = $request->get_params();
		$items  = isset( $params['items'] ) && is_array( $params['items'] ) ? $params['items'] : array();
		$output = array();

		foreach ( $items as $item ) {
			$product_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;

			if ( ! $product_id ) {
				continue;
			}

			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			$formatted_variation = $product->is_type( 'variable' ) ? '' : wc_get_formatted_variation( $product, true, false, true );
			$product_value       = $product->get_name() . ( $formatted_variation ? ' - ' . $formatted_variation : '' );

			$data = array(
				'id'       => $product_id,
				'editLink' => html_entity_decode( get_edit_post_link( $product_id ) ),
				'url'      => get_permalink( $product_id ),
				'value'    => rawurldecode( wp_strip_all_tags( $product_value ) ),
				'img'      => wp_get_attachment_url( $product->get_image_id() ),
				'regular'  => $product->get_regular_price( 'edit' ),
				'sale'     => $product->get_sale_price( 'edit' ),
			);

			if ( $product->is_type( 'variable' ) ) {
				$variation_ids_input  = isset( $item['variation'] ) && is_array( $item['variation'] ) ? array_map( 'absint', $item['variation'] ) : array();
				$available_variations = $product->get_available_variations();
				$variations_data      = array();

				foreach ( $available_variations as $variation_data ) {
					$variation_id = $variation_data['variation_id'];
					$variation    = wc_get_product( $variation_id );

					if ( $variation && $variation->is_purchasable() && $variation->is_in_stock() ) {
						$variation_formatted = wc_get_formatted_variation( $variation, true, false, true );
						$variations_data[]   = array(
							'id'         => $variation_id,
							'url'        => get_permalink( $variation_id ),
							'value'      => rawurldecode( wp_strip_all_tags( $variation_formatted ? $variation->get_name() . ' - ' . $variation_formatted : $variation->get_name() ) ),
							'img'        => wp_get_attachment_url( $variation->get_image_id() ),
							'attributes' => wc_get_product_variation_attributes( $variation_id ),
							'regular'    => $variation->get_regular_price( 'edit' ),
							'sale'       => $variation->get_sale_price( 'edit' ),
							'enable'     => in_array( $variation_id, $variation_ids_input, true ),
						);
					}
				}
				$data['variation'] = $variations_data;
			}

			$output[] = $data;
		}

		return $output;
	}

	/**
	 * Retrieves assigned product data based on the provided option ID.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object containing the option ID.
	 *
	 * @return \WP_REST_Response The REST response containing the assigned data or an error message.
	 */
	public function get_assign_product_callback( \WP_REST_Request $request ) {
		$request_params = $request->get_params();
		$option_id      = ! empty( $request_params['option_id'] ) ? sanitize_text_field( $request_params['option_id'] ) : '';

		if ( $option_id ) {
			return new WP_REST_Response(
				array(
					'success'  => true,
					'assigned' => product_addons()->get_assigned_product_data( $option_id ),
				),
				200
			);
		}

		// If option_id is missing, return a message indicating the issue.
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => 'Option ID Missing',
			),
			400
		);
	}


	/**
	 * Set assigned product data for a given option.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST request object containing the option ID and assignment data.
	 *
	 * @return \WP_REST_Response The REST response containing the success message or an error response.
	 */
	public function set_assign_product_callback( \WP_REST_Request $request ) {
		$params        = $request->get_params();
		$option_id     = ! empty( $params['option_id'] ) ? sanitize_text_field( $params['option_id'] ) : '';
		$product_image = ! empty( $params['product_image'] ) ? product_addons()->sanitize_rest_params( $params['product_image'] ) : array();
		$nonce         = isset( $params['wpnonce'] ) ? sanitize_text_field( $params['wpnonce'] ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'prad-nonce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid or missing nonce.', 'product-addons' ),
				),
				403
			);
		}

		if ( empty( $option_id ) ) {
			return new WP_REST_Response(
				array(
					'success'  => false,
					'response' => array(
						'message' => __( 'No ID found', 'product-addons' ),
					),
				),
				400
			);
		}

		$raw_data = isset( $params['raw_data'] ) ? product_addons()->sanitize_rest_params( $params['raw_data'] ) : array();

		// Only the assignment types this install supports are accepted; more are added through prad_assign_taxonomies.
		$assign_taxonomies = product_addons()->get_assign_taxonomies();
		$assign_type       = isset( $raw_data['aType'] ) ? $raw_data['aType'] : '';
		if ( ! in_array( $assign_type, array( 'specific_product', 'all_product' ), true ) && ! isset( $assign_taxonomies[ $assign_type ] ) ) {
			return new WP_REST_Response(
				array(
					'success'  => false,
					'response' => array(
						'message' => __( 'Invalid assignment type.', 'product-addons' ),
					),
				),
				400
			);
		}

		$new_image_data               = get_option( 'prad_product_image_update_data', array() );
		$new_image_data[ $option_id ] = $product_image;
		update_option( 'prad_product_image_update_data', $new_image_data );

		/* First Remove existing assign include / excludes meta */
		$this->handle_existing_assign_meta( $option_id );

		// Update Option Data Both Option Data and Product/Term Data.
		if ( 'specific_product' === $raw_data['aType'] ) {  // Update meta for Specific Product.
			if ( is_array( $raw_data['includes'] ) && ! empty( $raw_data['includes'] ) ) {
				foreach ( $raw_data['includes'] as $include ) {
					$meta_inc = json_decode( product_addons()->safe_stripslashes( get_post_meta( $include, 'prad_product_assigned_meta_inc', true ) ), true );
					$meta_inc = is_array( $meta_inc ) ? $meta_inc : array();

					if ( ! in_array( $option_id, array_map( 'strval', $meta_inc ), true ) ) {
						$meta_inc[] = $option_id;
					}
					update_post_meta( $include, 'prad_product_assigned_meta_inc', wp_json_encode( $meta_inc ) );
				}
			}
		} elseif ( isset( $assign_taxonomies[ $raw_data['aType'] ] ) ) {  /* Update meta for Terms */
			if ( is_array( $raw_data['includes'] ) && ! empty( $raw_data['includes'] ) ) {
				foreach ( $raw_data['includes'] as $include ) {
					$meta_inc = json_decode( product_addons()->safe_stripslashes( get_term_meta( $include, 'prad_term_assigned_meta_inc', true ) ), true );
					$meta_inc = is_array( $meta_inc ) ? $meta_inc : array();

					if ( ! in_array( $option_id, array_map( 'strval', $meta_inc ), true ) ) {
						$meta_inc[] = $option_id;
					}
					update_term_meta( $include, 'prad_term_assigned_meta_inc', wp_json_encode( $meta_inc ) );
				}
			}
		} elseif ( 'all_product' === $raw_data['aType'] ) {        // Update meta for All Products.
			$option_settings = json_decode( product_addons()->safe_stripslashes( get_option( 'prad_option_assign_all', '[]' ) ), true );
			$option_settings = is_array( $option_settings ) ? $option_settings : array();

			if ( ! in_array( $option_id, array_map( 'strval', $option_settings ), true ) ) {
				$option_settings[] = $option_id;
			}
			update_option( 'prad_option_assign_all', wp_json_encode( $option_settings ) );
		}

		// Update Meta for Excludes Products.
		if ( is_array( $raw_data['excludes'] ) && count( $raw_data['excludes'] ) > 0 ) {
			foreach ( $raw_data['excludes'] as $exclude ) {
				$meta_exc = json_decode( product_addons()->safe_stripslashes( get_post_meta( $exclude, 'prad_product_assigned_meta_exc', true ) ), true );
				$meta_exc = is_array( $meta_exc ) ? $meta_exc : array();

				if ( ! in_array( $option_id, array_map( 'strval', $meta_exc ), true ) ) {
					$meta_exc[] = $option_id;
				}
				update_post_meta( $exclude, 'prad_product_assigned_meta_exc', wp_json_encode( $meta_exc ) );
			}
		}

		// Update the option meta with the assigned data.
		update_post_meta( $option_id, 'prad_base_assigned_data', wp_json_encode( $raw_data ) );

		/**
		 * Fires after an option set's assignment is saved, so extensions can store their own
		 * assignment settings.
		 *
		 * @since 1.8.3
		 *
		 * @param int   $option_id Option set ID.
		 * @param array $raw_data  The saved assignment data.
		 */
		do_action( 'prad_option_assignment_saved', $option_id, $raw_data );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'response' => array(
					'product_image' => $product_image,
					'option_id'     => $option_id,
					'message'       => __( 'Option Assigned Updated successfully', 'product-addons' ),
					'newData'       => json_decode( product_addons()->safe_stripslashes( get_post_meta( $option_id, 'prad_base_assigned_data', true ) ), true ),
				),
			),
			200
		);
	}

	/**
	 * Handle Existing Assign Data
	 *
	 * @since 1.0.0
	 * @param int $option_id The ID of the option to retrieve assigned data for.
	 *
	 * @return void
	 */
	public function handle_existing_assign_meta( $option_id ) {
		$assigned_data = json_decode( product_addons()->safe_stripslashes( get_post_meta( $option_id, 'prad_base_assigned_data', true ) ), true );
		if ( isset( $assigned_data['aType'] ) && 'all_product' === $assigned_data['aType'] ) {
			$option_settings = json_decode( product_addons()->safe_stripslashes( get_option( 'prad_option_assign_all', '[]' ) ), true );
			$option_settings = is_array( $option_settings ) ? $option_settings : array();

			if ( in_array( $option_id, array_map( 'strval', $option_settings ), true ) ) {
				$option_settings = array_diff( $option_settings, array( $option_id ) );
			}
			update_option( 'prad_option_assign_all', wp_json_encode( $option_settings ) );
		} elseif ( isset( $assigned_data['aType'] ) && 'specific_product' === $assigned_data['aType'] ) {
			if ( is_array( $assigned_data['includes'] ) && ! empty( $assigned_data['includes'] ) ) {
				foreach ( $assigned_data['includes'] as $include ) {
					$meta_inc = json_decode( product_addons()->safe_stripslashes( get_post_meta( $include, 'prad_product_assigned_meta_inc', true ) ), true );
					$meta_inc = is_array( $meta_inc ) ? $meta_inc : array();

					if ( in_array( $option_id, array_map( 'strval', $meta_inc ), true ) ) {
						$meta_inc = array_diff( $meta_inc, array( $option_id ) );
					}
					update_post_meta( $include, 'prad_product_assigned_meta_inc', wp_json_encode( $meta_inc ) );
				}
			}
		} elseif ( isset( $assigned_data['aType'] ) && 0 === strpos( (string) $assigned_data['aType'], 'specific_' ) ) {
			// Clean up every term type ("specific_*", including ones no longer assignable), so no stale meta is left behind.
			if ( is_array( $assigned_data['includes'] ) && ! empty( $assigned_data['includes'] ) ) {
				foreach ( $assigned_data['includes'] as $include ) {
					$meta_inc = json_decode( product_addons()->safe_stripslashes( get_term_meta( $include, 'prad_term_assigned_meta_inc', true ) ), true );
					$meta_inc = is_array( $meta_inc ) ? $meta_inc : array();

					if ( in_array( $option_id, array_map( 'strval', $meta_inc ), true ) ) {
						$meta_inc = array_diff( $meta_inc, array( $option_id ) );
					}
					update_term_meta( $include, 'prad_term_assigned_meta_inc', wp_json_encode( $meta_inc ) );
				}
			}
		}
		if ( isset( $assigned_data['excludes'] ) && is_array( $assigned_data['excludes'] ) && count( $assigned_data['excludes'] ) > 0 ) {
			foreach ( $assigned_data['excludes'] as $exclude ) {
				$meta_exc = json_decode( product_addons()->safe_stripslashes( get_post_meta( $exclude, 'prad_product_assigned_meta_exc', true ) ), true );
				$meta_exc = is_array( $meta_exc ) ? $meta_exc : array();

				if ( in_array( $option_id, array_map( 'strval', $meta_exc ), true ) ) {
					$meta_exc = array_diff( $meta_exc, array( $option_id ) );
				}
				update_post_meta( $exclude, 'prad_product_assigned_meta_exc', wp_json_encode( $meta_exc ) );
			}
		}

		if ( is_array( $assigned_data ) ) {
			/**
			 * Fires when an option set's assignment is removed (before it is saved again, or when the
			 * option set is deleted), so extensions can remove their own assignment settings.
			 *
			 * @since 1.8.3
			 *
			 * @param int   $option_id     Option set ID.
			 * @param array $assigned_data The assignment data being removed.
			 */
			do_action( 'prad_option_assignment_removed', $option_id, $assigned_data );
		}
	}

	/**
	 * Get global data.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_REST_Response The REST response containing the global data.
	 */
	public function get_global_callback() {
		return new WP_REST_Response(
			array(
				'success'  => true,
				'response' => array(
					'globalStyle'   => get_option( 'prad_global_style', '' ),
					'thematicStyle' => get_option( 'prad_global_style_thematic', '' ),
				),
			),
			200
		);
	}
	/**
	 * Set global data.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The request object containing the data.
	 *
	 * @return \WP_REST_Response The REST response with success or error message.
	 */
	public function set_global_callback( \WP_REST_Request $request ) {
		$request_params = $request->get_params();
		$style          = isset( $request_params['style'] ) ? product_addons()->sanitize_rest_params( $request_params['style'] ) : '';
		$css            = isset( $request_params['css'] ) ? sanitize_textarea_field( $request_params['css'] ) : '';
		$is_themetic    = isset( $request_params['isThemetic'] ) ? sanitize_textarea_field( $request_params['isThemetic'] ) : 'no';
		$nonce          = isset( $request_params['wpnonce'] ) ? sanitize_text_field( $request_params['wpnonce'] ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'prad-nonce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid or missing nonce.', 'product-addons' ),
				),
				403
			);
		}

		if ( 'yes' === $is_themetic ) {
			if ( $style ) {
				update_option( 'prad_global_style_thematic', $style );
			}
			if ( $css ) {
				update_option( 'prad_global_style_thematic_css', $css );
			}
		} else {
			if ( $style ) {
				update_option( 'prad_global_style', $style );
			}
			if ( $css ) {
				update_option( 'prad_global_style_css', $css );
			}
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Style saved successfully.', 'product-addons' ),
			),
			200
		);
	}

	/**
	 * Get global data.
	 *
	 * @since 1.0.0
	 *
	 * @return \WP_REST_Response The REST response containing the global data.
	 */
	public function get_settings_callback() {
		return new WP_REST_Response(
			array(
				'success'  => true,
				'response' => get_option( 'prad_settings', '' ),
			),
			200
		);
	}
	/**
	 * Set global data.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The request object containing the data.
	 *
	 * @return \WP_REST_Response The REST response with success or error message.
	 */
	public function set_settings_callback( \WP_REST_Request $request ) {
		$request_params = $request->get_params();
		$settings       = isset( $request_params['settings'] ) ? product_addons()->sanitize_rest_params( $request_params['settings'] ) : '';
		$nonce          = isset( $request_params['wpnonce'] ) ? sanitize_text_field( $request_params['wpnonce'] ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'prad-nonce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid or missing nonce.', 'product-addons' ),
				),
				403
			);
		}

		if ( $settings ) {
			update_option( 'prad_settings', $settings );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Settings saved successfully.', 'product-addons' ),
			),
			200
		);
	}

	/**
	 * Product Image Compability
	 *
	 * @since 1.0.5
	 *
	 * @param \WP_REST_Request $request The request object containing the data.
	 *
	 * @return \WP_REST_Response The REST response with success or error message.
	 */
	public function product_image_callback( \WP_REST_Request $request ) {
		$request_params = $request->get_params();
		$product_data   = isset( $request_params['productData'] ) && is_array( $request_params['productData'] ) ? product_addons()->sanitize_rest_params( $request_params['productData'] ) : array();

		$to_return = array();
		if ( ! empty( $product_data ) && is_array( $product_data ) ) {
			foreach ( $product_data as $key => $value ) {
				$id                = function_exists( 'attachment_url_to_postid' ) && $value['src'] ? attachment_url_to_postid( $value['src'] ) : '';
				$to_return[ $key ] = $id;
			}
		}

		return new WP_REST_Response(
			array(
				'success'   => true,
				'message'   => $product_data,
				'to_return' => $to_return,
			),
			200
		);
	}

	/**
	 * The free plugin's hardcoded allowed upload types — jpg/jpeg/png only.
	 *
	 * No filter, no extension points: this endpoint only ever needs to accept
	 * these three image types, so the list is a fixed constant rather than a
	 * pro-extensible one.
	 *
	 * @return array
	 */
	private function get_allowed_upload_mime_types(): array {
		return array(
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'png'  => 'image/png',
		);
	}

	/**
	 * Retrieve and validate uploaded file.
	 *
	 * @return array|\WP_Error
	 */
	protected function get_uploaded_file() {

		// Nonce verification before processing form data.
		$nonce = isset( $_POST['pradnonce'] ) ? sanitize_key( wp_unslash( $_POST['pradnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! wp_verify_nonce( $nonce, 'prad-nonce' ) ) {
			return new \WP_Error( 'invalid_nonce', __( 'Invalid nonce.', 'product-addons' ) );
		}

		if ( empty( $_FILES['prad_file'] ) ||
			empty( $_FILES['prad_file']['name'] )
		) {
			return new \WP_Error( 'no_file', __( 'No file found.', 'product-addons' ) );
		}

		// Sanitize the uploaded file entry field by field.
		$file = array(
			'name'     => isset( $_FILES['prad_file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['prad_file']['name'] ) ) : '',
			'type'     => isset( $_FILES['prad_file']['type'] ) ? sanitize_mime_type( wp_unslash( $_FILES['prad_file']['type'] ) ) : '',
			'tmp_name' => isset( $_FILES['prad_file']['tmp_name'] ) ? sanitize_text_field( $_FILES['prad_file']['tmp_name'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Server-generated temp path; unslashing would break Windows paths. Validated with is_uploaded_file() below.
			'error'    => isset( $_FILES['prad_file']['error'] ) ? absint( $_FILES['prad_file']['error'] ) : UPLOAD_ERR_NO_FILE,
			'size'     => isset( $_FILES['prad_file']['size'] ) ? absint( $_FILES['prad_file']['size'] ) : 0,
		);

		if ( UPLOAD_ERR_OK !== $file['error'] || '' === $file['name'] || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
			return new \WP_Error( 'upload_failed', __( 'File upload failed. Please try again.', 'product-addons' ) );
		}

		$max_file_size = 25 * 1024 * 1024; // 25MB

		if ( $file['size'] > $max_file_size || filesize( $file['tmp_name'] ) > $max_file_size ) {
			return new \WP_Error(
				'file_size',
				__( 'File size exceeds the maximum allowed limit (25MB).', 'product-addons' )
			);
		}

		$allowed_types = $this->get_allowed_upload_mime_types();

		// wp_check_filetype_and_ext() sniffs the file's real content (via the
		// fileinfo extension) against $allowed_types, rejecting anything whose
		// actual bytes don't match one of the three allowed image types —
		// this catches a disallowed file simply renamed with a .jpg/.png
		// extension.
		$filetype = wp_check_filetype_and_ext(
			$file['tmp_name'],
			$file['name'],
			$allowed_types
		);

		if ( empty( $filetype['ext'] ) || empty( $filetype['type'] ) || ! isset( $allowed_types[ $filetype['ext'] ] ) ) {
			return new \WP_Error(
				'invalid_type',
				__( 'Invalid file type. Only JPG, JPEG and PNG files are allowed.', 'product-addons' )
			);
		}

		// Defense in depth: confirm the file actually decodes as an image.
		// wp_check_filetype_and_ext() only checks the declared MIME signature;
		// a polyglot file can carry a valid image signature followed by
		// unrelated (potentially executable) content. getimagesize() parses
		// the real image structure and fails on anything that isn't one.
		if ( ! @getimagesize( $file['tmp_name'] ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
			return new \WP_Error(
				'invalid_image',
				__( 'The uploaded file is not a valid image.', 'product-addons' )
			);
		}

		return $file;
	}

	/**
	 * Handles file uploads via REST API.
	 *
	 * @since 1.0.0
	 *
	 * @return WP_REST_Response Response indicating success or failure of the upload.
	 */
	public function upload_files_callback() {

		$nonce = isset( $_POST['pradnonce'] ) ? sanitize_key( wp_unslash( $_POST['pradnonce'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! wp_verify_nonce( $nonce, 'prad-nonce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce.', 'product-addons' ),
				),
				403
			);
		}

		$file = $this->get_uploaded_file();

		if ( is_wp_error( $file ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $file->get_error_message(),
				),
				400
			);
		}

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		add_filter( 'upload_dir', array( $this, 'prad_handle_upload_dir' ) );

		// Passing 'mimes' directly restricts wp_handle_upload()'s own internal
		// filetype check to exactly these three types, without touching the
		// site-wide 'upload_mimes' filter.
		$uploaded = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'mimes'     => $this->get_allowed_upload_mime_types(),
			)
		);

		remove_filter( 'upload_dir', array( $this, 'prad_handle_upload_dir' ) );

		if ( isset( $uploaded['error'] ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => $uploaded['error'],
				),
				400
			);
		}

		$this->prad_ensure_upload_dir_security( dirname( $uploaded['file'] ) );

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'file' => $uploaded,
				),
			),
			200
		);
	}

	/**
	 * Write a .htaccess to the upload directory with baseline security hardening.
	 *
	 * Only creates the file if it does not already exist; safe to call on every
	 * upload. Sets X-Content-Type-Options to stop browsers from ever sniffing an
	 * uploaded file into executing as a different content type than declared, and
	 * denies execution of any PHP-family file that might end up in this directory —
	 * this is unconditional, hardcoded hardening (not tied to any filter), since
	 * jpg/jpeg/png uploads never legitimately need to run as PHP.
	 *
	 * @param string $dir_path Absolute filesystem path to the upload directory.
	 */
	private function prad_ensure_upload_dir_security( $dir_path ) {
		$htaccess = trailingslashit( $dir_path ) . '.htaccess';
		if ( file_exists( $htaccess ) ) {
			return;
		}

		$rules  = "<IfModule mod_headers.c>\n";
		$rules .= "  Header set X-Content-Type-Options \"nosniff\"\n";
		$rules .= "</IfModule>\n";
		$rules .= "<FilesMatch \"\\.(?:php|phtml|php\\d|pht|phar)$\">\n";
		$rules .= "  <IfModule mod_authz_core.c>\n";
		$rules .= "    Require all denied\n";
		$rules .= "  </IfModule>\n";
		$rules .= "  <IfModule !mod_authz_core.c>\n";
		$rules .= "    Order allow,deny\n";
		$rules .= "    Deny from all\n";
		$rules .= "  </IfModule>\n";
		$rules .= "</FilesMatch>\n";

		file_put_contents( $htaccess, $rules ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	}

	/**
	 * Customize the upload directory path for PRAD files.
	 *
	 * @param array $upload The existing upload directory data.
	 * @return array The modified upload directory data.
	 */
	public function prad_handle_upload_dir( $upload ) {
		$directory        = 'prad_option_files/temp';
		$upload['subdir'] = '/' . $directory;
		$upload['path']   = $upload['basedir'] . $upload['subdir'];
		$upload['url']    = $upload['baseurl'] . $upload['subdir'];
		return $upload;
	}

	/**
	 * Set analytics data callback.
	 *
	 * Handles updating analytics data for a given option ID and type.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response indicating success or failure.
	 */
	public function set_analytics_data_callback( \WP_REST_Request $request ) {
		$request_params = $request->get_params();
		$nonce          = isset( $request_params['nonce'] ) ? sanitize_key( $request_params['nonce'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'prad-nonce' ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Invalid nonce.', 'product-addons' ),
				),
				403
			);
		}
		$option_id = isset( $request_params['optionId'] ) ? sanitize_text_field( $request_params['optionId'] ) : '';
		$type      = isset( $request_params['type'] ) ? sanitize_text_field( $request_params['type'] ) : '';

		if ( $option_id && $type ) {
			do_action( 'prad_update_stats_table_data', $option_id, $type, '' );
			return new WP_REST_Response(
				array(
					'success'   => true,
					'option_id' => $option_id,
					'type'      => $type,
					'message'   => __( 'Analytics data updated.', 'product-addons' ),
				),
				200
			);
		}
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Invalid Analytics data.', 'product-addons' ),
			),
			400
		);
	}

	/**
	 * Retrieves a list of options with search and pagination functionality.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_REST_Request $request The REST API request object.
	 * @return WP_REST_Response Response containing the list of options and pagination info.
	 */
	public function get_analytics_data_callback( \WP_REST_Request $request ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- REST callback signature.
		global $wpdb;

		$table_name   = $wpdb->prefix . 'prad_stats_graph';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // phpcs:ignore
		if ( $table_exists ) {
			$stats_graph = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY id ASC', $table_name ) ); // phpcs:ignore
		} else {
			$wpdb->hide_errors();
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$analytics = new Analytics();
			$analytics->create_stats_graph_table();
			$stats_graph = array();
		}

		return new WP_REST_Response(
			array(
				'success'     => true,
				'stats_table' => $this->stats_table_data(),
				'stats_graph' => ! empty( $stats_graph ) ? $stats_graph : array(),
			),
			200
		);
	}

	/**
	 * Retrieves analytics stats table data for options.
	 *
	 * @since 1.0.0
	 *
	 * @return array Stats table data for options.
	 */
	public function stats_table_data() {
		global $wpdb;

		$table_name   = $wpdb->prefix . 'prad_stats_table';
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ); // phpcs:ignore
		if ( ! $table_exists ) {
			$wpdb->hide_errors();
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
			$analytics = new Analytics();
			$analytics->create_stats_table();
			return array();
		}

		$paged    = 1;
		$per_page = -1;
		$order    = 'DESC';

		$args = array(
			'post_type'      => 'prad_option',
			'posts_per_page' => $per_page,
			'order'          => $order,
			'orderby'        => 'ID',
			'post_status'    => array( 'publish', 'draft' ),
			'paged'          => $paged,
		);

		$query = new \WP_Query( $args );
		$data  = array();

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$id           = get_the_ID();
				$option_stats = $wpdb->get_results(//phpcs:ignore
					$wpdb->prepare(
						"SELECT * FROM {$wpdb->prefix}prad_stats_table WHERE option_id = %d",
						$id
					)
				);

				if ( ! empty( $option_stats ) ) {
					$option_stats = $option_stats[0];
				} else {
					$option_stats = (object) array();
				}

				$click_rate = ( isset( $option_stats->impression_count ) && isset( $option_stats->click_count ) && $option_stats->impression_count > 0 ) ? ( $option_stats->click_count / $option_stats->impression_count ) * 100 : 0;
				$cart_rate  = ( isset( $option_stats->impression_count ) && isset( $option_stats->add_to_cart_count ) && $option_stats->impression_count > 0 ) ? ( $option_stats->add_to_cart_count / $option_stats->impression_count ) * 100 : 0;

				$data[] = array(
					'id'           => $id,
					'title'        => get_the_title(),
					'click'        => round( $click_rate ),
					'cart'         => round( $cart_rate ),
					'sales'        => isset( $option_stats->sales ) ? $option_stats->sales : 0,
					'option_stats' => $option_stats,
					'assigned'     => product_addons()->get_assigned_product_data( $id ),
				);
			}

			wp_reset_postdata();
		}

		return $data;
	}

	/**
	 * Dismiss the builder onboarding tour.
	 *
	 * Sets the same flag that creating a first option list sets, so the site
	 * stops counting as a fresh install. Skipping the tour never creates an
	 * add-on, so without this the tour would reappear on every page load.
	 *
	 * @since v.1.6.17
	 *
	 * @return WP_REST_Response
	 */
	public function dismiss_tour_callback() {
		update_option( 'prad_first_option_created', 'yes' );

		return new WP_REST_Response(
			array(
				'success' => true,
			),
			200
		);
	}
}
