<?php
namespace WOPB;

defined( 'ABSPATH' ) || exit;

class RequestAPI {

	private $api_endpoint = 'https://wopb.wpxpo.com/wp-json/restapi/v2/';

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'get_template_data' ) );
		add_action( 'draft_to_publish', array( $this, 'builder_draft_to_publish' ), 100, 1 );
	}

	/**
	 * Create Builder Post Type
	 *
	 * @since v.2.3.9
	 * @return NULL
	 */
	public function get_template_data() {
		register_rest_route(
			'wopb/v2',
			'/get_single_premade/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'get_single_premade_callback' ),
					'permission_callback' => function () {
						return apply_filters( 'wopb_rest_api_capability', current_user_can( 'manage_options' ) );
					},
					'args'                => array(),
				),
			)
		);
		register_rest_route(
			'wopb/v2',
			'/condition/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'condition_settings_action' ),
					'permission_callback' => function () {
						return apply_filters( 'wopb_rest_api_capability', current_user_can( 'manage_options' ) );
					},
					'args'                => array(),
				),
			)
		);
		register_rest_route(
			'wopb/v2',
			'/condition_save/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'condition_save_action' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(),
				),
			)
		);
		register_rest_route(
			'wopb/v2',
			'/data_builder/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'data_builder_action' ),
					'permission_callback' => function () {
						return apply_filters( 'wopb_rest_api_capability', current_user_can( 'manage_options' ) );
					},
					'args'                => array(),
				),
			)
		);
		register_rest_route(
			'wopb/v2',
			'/template_action/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'template_page_action' ),
					'permission_callback' => function () {
						return current_user_can( 'manage_options' );
					},
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Single Premade Data and Create Builder Posts
	 *
	 * @since v.2.3.9
	 * @param ARRAY
	 * @return ARRAY | Data of the Premade
	 */
	public function get_single_premade_callback( $server ) {
		$obj_count = wp_count_posts( 'wopb_builder' );

		/*
			Builder is unrestricted for all users
		*/
		// if ( ! wopb_function()->get_setting( 'is_lc_active' )  ) {
		// $p_count = isset( $obj_count->publish ) ? $obj_count->publish : 0;
		// $d_count = isset( $obj_count->draft ) ? $obj_count->draft : 0;
		// if ( ( $p_count + $d_count ) > 0 ) {
		// return array( 'success' => false );
		// }
		// }

		$post = $server->get_params();
		$id   = isset( $post['ID'] ) ? sanitize_text_field( $post['ID'] ) : '';
		$type = isset( $post['type'] ) ? sanitize_text_field( $post['type'] ) : '';

		if ( $id ) {
			$response = wp_remote_post(
				$this->api_endpoint . 'single-premade',
				array(
					'method'  => 'POST',
					'timeout' => 120,
					'body'    => array(
						'section_id' => $id,
						'license'    => get_option( 'edd_wopb_license_key' ),
					),
				)
			);
			if ( is_wp_error( $response ) ) {
				return array(
					'success' => false,
					'data'    => 'Something went wrong:' . $response->get_error_message(),
				);
			} elseif ( isset( $response['body'] ) && $type ) {
					$body = json_decode( $response['body'] );
				if ( isset( $body->rawData ) && isset( $body->success ) && $body->success ) {
					$post_id = $this->set_post_content( $type, wp_slash( $body->rawData ) );
					return array(
						'success' => true,
						'link'    => get_edit_post_link( $post_id ),
					);
				} else {
					return array( 'success' => false );
				}
			}
		} else {
			$post_id = $this->set_post_content( $type, '' );
			return array(
				'success' => true,
				'link'    => get_edit_post_link( $post_id ),
			);
		}
	}


	/**
	 * Condition Settings Actions
	 *
	 * @since v.2.3.9
	 * @param ARRAY
	 * @return ARRAY | Data of the Condition
	 */
	public function condition_settings_action( $server ) {
		global $wpdb;
		$post         = $server->get_params();
		$search_type  = explode( '###', sanitize_text_field( $post['type'] ) );
		$search_term  = isset( $post['term'] ) ? sanitize_text_field( $post['term'] ) : '';
		$title_return = isset( $post['title_return'] ) ? rest_sanitize_boolean( $post['title_return'] ) : '';
		switch ( $search_type[0] ) {
			case 'type':
				$args = array(
					'post_type'      => $search_type[1],
					'post_status'    => 'publish',
					'orderby'        => 'title',
					'order'          => 'ASC',
					's'              => $search_term,
					'posts_per_page' => 10,
				);
				if ( $title_return ) {
					unset( $args['s'] );
					$args['p'] = $search_term;
				}
				$post_results = new \WP_Query( $args );
				$title        = '';
				$data         = array();
				if ( ! empty( $post_results ) ) {
					while ( $post_results->have_posts() ) {
						$post_results->the_post();
						$id     = get_the_ID();
						$title  = get_the_title();
						$data[] = array(
							'value' => $id,
							'title' => ( $title ? $title : ( '##' . $id ) ),
						);
					}
					wp_reset_postdata();
				}
				return array(
					'success' => true,
					'data'    => ( $title_return ? $title : $data ),
				);
			break;

			case 'term':
				$args = array(
					'taxonomy'   => $search_type[1],
					'fields'     => 'all',
					'orderby'    => 'id',
					'order'      => 'ASC',
					'name__like' => $search_term,
				);
				if ( $title_return ) {
					$args['term_taxonomy_id'] = array( $search_term );
					unset( $args['name__like'] );
				}
				$post_results = get_terms( $args );
				$title        = '';
				$data         = array();
				if ( ! empty( $post_results ) ) {
					foreach ( $post_results as $key => $val ) {
						$title  = $val->name;
						$data[] = array(
							'value' => $val->term_id,
							'title' => $title,
						);
					}
				}
				return array(
					'success' => true,
					'data'    => ( $title_return ? $title : $data ),
				);
			break;

			case 'author':
				$term         = $title_return ? $wpdb->esc_like( $search_term ) : '%' . $wpdb->esc_like( $search_term ) . '%';
				$post_results = get_users(
					array(
						'search'         => '*' . $term . '*',
						'search_columns' => array(
							'user_login',
							'user_nicename',
							'user_email',
							'display_name',
						),
						'number'         => 10,
					)
				);
				$title        = '';
				$data         = array();
				if ( ! empty( $post_results ) ) {
					foreach ( $post_results as $key => $val ) {
						$title  = $val->display_name;
						$data[] = array(
							'value' => $val->ID,
							'title' => $val->display_name,
						);
					}
				}
				return array(
					'success' => true,
					'data'    => ( $title_return ? $title : $data ),
				);
			break;
			default:
				return array( 'success' => false );
			break;
		}
		return array(
			'success' => true,
			'data'    => 'This is Testing !!!',
		);
	}

	/**
	 * Save Conditions Data
	 *
	 * @since v.2.3.9
	 * @param ARRAY
	 * @return ARRAY | Message of the Condition Success
	 */
	public function condition_save_action( $server ) {
		$post = $server->get_params();
		if ( isset( $post['settings'] ) ) {
			$settings = wopb_function()->recursive_sanitize_text_field( $post['settings'] );
			update_option( 'wopb_builder_conditions', $settings );
			return array(
				'success' => true,
				'data'    => 'Settings Saved!!!',
			);
		}
	}

	/**
	 * Get Add Default Condition Data
	 *
	 * @since v.2.3.9
	 * @return ARRAY | Default Data
	 */
	public function builder_data() {
		$single_data  = array();
		$archive_data = array(
			array(
				'label' => 'All Archive',
				'value' => '',
			),
		);
		$header_data  = array(
			array(
				'label' => 'Entire Site',
				'value' => 'entire_site',
			),
			array(
				'label' => 'Archive',
				'value' => 'archive',
			),
			array(
				'label' => 'Singular',
				'value' => 'single_product',
			),
		);

		$post_type = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_type as $key => $type ) {
			// Post Type
			if ( $type->name == 'product' || $type->name == 'page' ) {
				$single_temp = array(
					'label'  => $type->label,
					'value'  => $type->name,
					'search' => 'type###' . $type->name,
				);

				$archive_temp = array();

				// Taxonomy
				$taxonomy = get_object_taxonomies( $type->name, 'objects' );
				if ( ! empty( $taxonomy ) ) {
					$single_tax   = $archive_tax = array();
					$single_tax[] = $single_temp;

					$archive_temp     = array(
						'label' => $type->label . ' Archive',
						'value' => $type->name . '_archive',
					);
					$exclude_taxonomy = array( 'product_shipping_class' );
					foreach ( $taxonomy as $key => $val ) {
						if ( in_array( $key, $exclude_taxonomy ) ) {
							continue;
						}
						if ( $val->public ) {
							$single_tax[]  = array(
								'label'  => 'In ' . $val->label,
								'value'  => 'in_' . $val->name,
								'search' => 'term###' . $val->name,
							);
							$archive_tax[] = array(
								'label'  => $val->label,
								'value'  => $val->name,
								'search' => 'term###' . $val->name,
							);

							if ( $val->hierarchical ) {
								// Hierarchical
								$single_tax[]  = array(
									'label'  => 'In Child ' . $val->label,
									'value'  => 'in_' . $val->name . '_children',
									'search' => 'term###' . $val->name,
								);
								$archive_tax[] = array(
									'label'  => 'Direct Child ' . $val->label . ' Of',
									'value'  => 'child_of_' . $val->name,
									'search' => 'term###' . $val->name,
								);
								$archive_tax[] = array(
									'label'  => 'Any Child ' . $val->label . ' Of',
									'value'  => 'any_child_of_' . $val->name,
									'search' => 'term###' . $val->name,
								);
							}
						}
					}
					$single_temp['attr']  = $single_tax;
					$archive_temp['attr'] = $archive_tax;
				}
				$single_data[] = $single_temp;
				if ( ! empty( $archive_temp ) ) {
					$archive_data[] = $archive_temp;
				}
			}
		}

		return array(
			'single_product' => $single_data,
			'archive'        => $archive_data,
			'header'         => $header_data,
			'footer'         => $header_data,
		);
	}

	/**
	 * Builder Post Type Data
	 *
	 * @since v.2.3.9
	 * @param ARRAY
	 * @return ARRAY | Information of Builder Post
	 */
	public function data_builder_action( $server ) {
		$post         = $server->get_params();
		$args         = array(
			'post_type'      => 'wopb_builder',
			'post_status'    => array( 'publish', 'draft' ),
			'orderby'        => 'title',
			'order'          => 'ASC',
			'posts_per_page' => -1,
		);
		$post_results = new \WP_Query( $args );
		$post_list    = array();
		$settings     = get_option( 'wopb_builder_conditions', array() );
		if ( ! empty( $post_results ) ) {
			while ( $post_results->have_posts() ) {
				$post_results->the_post();
				$id        = get_the_ID();
				$meta_type = get_post_meta( $id, '_wopb_builder_type', true );

				// condition check for existing builder version before 2.3.9
				if ( $meta_type === 'single-product' ) {
					$type = 'single_product';
					if ( $settings['archive'][ $id ][0] == 'filter/single-product' ) {
						$settings[ $type ][ $id ] = $settings['archive'][ $id ];
						if ( $settings[ $type ][ $id ][1] == 'include/allsingle' ) {
							$settings[ $type ][ $id ][1] = 'include/single_product/product';
						}
						unset( $settings[ $type ][ $id ][0] );
						$settings[ $type ][ $id ] = array_values( $settings[ $type ][ $id ] );
					}
				} elseif ( $meta_type === 'shop' ) {
					$type = 'shop';
				} elseif ( $meta_type === 'cart' ) {
					$type = 'cart';
				} else {
					$type = $meta_type ? $meta_type : 'archive';
					if ( $type == 'archive' && isset( $settings[ $type ][ $id ][0] ) && $settings[ $type ][ $id ][0] == 'filter/archive' ) {
						unset( $settings[ $type ][ $id ][0] );
						$settings[ $type ][ $id ] = array_values( $settings[ $type ][ $id ] );
					}
				}

				$post_list[] = array(
					'id'     => $id,
					'title'  => get_the_title(),
					'author' => get_the_author_meta( 'display_name' ),
					'date'   => get_the_date( get_option( 'date_format' ) ),
					'edit'   => get_edit_post_link( $id ),
					'type'   => $type,
					'label'  => str_replace( '_', ' ', $type ),
					'status' => get_post_status(),
				);
			}
			wp_reset_postdata();
		}

		$arg = array(
			'success'  => true,
			'postlist' => $post_list,
			'settings' => $settings,
			'defaults' => $this->builder_data(),
		);
		if ( isset( $post['pid'] ) && $post['pid'] ) {
			$post_meta = get_post_meta( wopb_function()->rest_sanitize_params( $post['pid'] ), '_wopb_builder_type', true );
			if ( $post_meta == 'single-product' ) {
				$arg['type'] = 'single_product';
			} else {
				$arg['type'] = $post_meta ? $post_meta : 'archive';
			}
		}
		return $arg;
	}

	/**
	 * Delete Template Page
	 *
	 * @since v.2.3.9
	 * @param STRING
	 * @return ARRAY | Success Message
	 */
	public function template_page_action( $server ) {
		$post    = $server->get_params();
		$message = '';
		if ( isset( $post['type'] ) && isset( $post['id'] ) && $post['id'] ) {
			$s_type  = wopb_function()->rest_sanitize_params( $post['type'] );
			$post_id = wopb_function()->rest_sanitize_params( $post['id'] );
			if ( $s_type == 'delete' ) {
				if ( isset( $post['section'] ) && $post['section'] == 'builder' ) { // phpcs:ignore WordPress.Security
					$conditions   = get_option( 'wopb_builder_conditions', array() );
					$builder_type = get_post_meta( $post_id, '_wopb_builder_type', true );
					if ( isset( $conditions[ $builder_type ][ $post_id ] ) ) {
						unset( $conditions[ $builder_type ][ $post_id ] );
						update_option( 'wopb_builder_conditions', $conditions );
					}
				}
				wp_delete_post( $post_id, true );
				$message = __( 'Template has been deleted.', 'product-blocks' );
			} elseif ( $s_type == 'duplicate' ) {
				$post = get_post( $post_id );

				if ( isset( $post ) && $post != null ) {
					$args        = array(
						'post_author'  => get_current_user_id(),
						'post_content' => str_replace( 'u0022', '\u0022', $post->post_content ),
						'post_excerpt' => $post->post_excerpt,
						'post_name'    => $post->post_name,
						'post_status'  => 'draft',
						'post_title'   => $post->post_title,
						'post_type'    => $post->post_type,
					);
					$new_post_id = wp_insert_post( $args );
					$type        = get_post_meta( $post_id, '_wopb_builder_type', true );
					$css         = get_post_meta( $post_id, '_wopb_css', true );
					$width       = get_post_meta( $post_id, '__container_width', true );
					$sidebar     = get_post_meta( $post_id, '__builder_sidebar', true );
					$widget_area = get_post_meta( $post_id, '__builder_widget_area', true );

					update_post_meta( $new_post_id, '_wopb_builder_type', $type );
					update_post_meta( $new_post_id, '_wopb_css', $css );
					update_post_meta( $new_post_id, '__container_width', $width );
					update_post_meta( $new_post_id, '__builder_sidebar', $sidebar );
					update_post_meta( $new_post_id, '__builder_widget_area', $widget_area );
					update_post_meta( $new_post_id, '_wopb_active', 'yes' );
				}

				$conditions = get_option( 'wopb_builder_conditions', array() );
				if ( $conditions && $type ) {
					if ( isset( $conditions[ $type ][ $post_id ] ) ) {
						$conditions[ $type ][ $new_post_id ] = $conditions[ $type ][ $post_id ];
						update_option( 'wopb_builder_conditions', $conditions );
					}
				}
				$message = __( 'Template has been duplicated.', 'product-blocks' );
			} elseif ( $s_type == 'status' ) {
				if ( $post['status'] ) {
					wp_update_post(
						array(
							'ID'          => $post_id,
							'post_status' => wopb_function()->rest_sanitize_params( $post['status'] ),
						)
					);
					$message = __( 'Status has been changed.', 'product-blocks' );
				}
			}
		}
		return array(
			'success' => true,
			'message' => $message,
		);
	}

	/**
	 * Single Premade Data and Insert Builder Posts
	 *
	 * @since v.2.3.9
	 * @param ARRAY
	 * @return INT | Post ID
	 */
	public function set_post_content( $type, $body = '' ) {
		$post_id  = wp_insert_post(
			array(
				'post_title'   => ucwords( str_replace( '_', ' ', $type ) ) . ' Template',
				'post_content' => $body,
				'post_type'    => 'wopb_builder',
				'post_status'  => 'draft',
			)
		);
		$settings = get_option( 'wopb_builder_conditions', array() );
		switch ( $type ) {
			case 'home_page':
				update_post_meta( $post_id, '_wopb_builder_type', 'home_page' );
				$settings['home_page'][ $post_id ] = array( 'filter/home_page' );
				update_option( 'wopb_builder_conditions', $settings );
				break;
			case 'single_product':
				update_post_meta( $post_id, '_wopb_builder_type', 'single_product' );
				$settings['single_product'][ $post_id ] = array( 'include/single_product/product' );
				update_option( 'wopb_builder_conditions', $settings );
				break;
			case 'shop':
				update_post_meta( $post_id, '_wopb_builder_type', 'shop' );
				$settings['shop'][ $post_id ] = array( 'filter/shop' );
				update_option( 'wopb_builder_conditions', $settings );
				break;
			case 'cart':
				update_post_meta( $post_id, '_wopb_builder_type', 'cart' );
				$settings['cart'][ $post_id ] = array( 'filter/cart' );
				update_option( 'wopb_builder_conditions', $settings );
				break;
			case 'checkout':
				update_post_meta( $post_id, '_wopb_builder_type', 'checkout' );
				$settings['checkout'][ $post_id ] = array( 'filter/checkout' );
				update_option( 'wopb_builder_conditions', $settings );
				break;
			case 'my_account':
				update_post_meta( $post_id, '_wopb_builder_type', 'my_account' );
				$settings['my_account'][ $post_id ] = array( 'filter/my_account' );
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => 'My Account Template',
					)
				);
				update_option( 'wopb_builder_conditions', $settings );
				break;
			case 'thank_you':
				update_post_meta( $post_id, '_wopb_builder_type', 'thank_you' );
				$settings['thank_you'][ $post_id ] = array( 'filter/thank_you' );
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => 'Thank You Template',
					)
				);
				update_option( 'wopb_builder_conditions', $settings );
				break;
			case 'product_search':
				update_post_meta( $post_id, '_wopb_builder_type', 'product_search' );
				$settings['product_search'][ $post_id ] = array( 'filter/product_search' );
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => 'Product Search Result Template',
					)
				);
				update_option( 'wopb_builder_conditions', $settings );
				break;
			case 'archive':
			case 'product_cat':
			case 'product_tag':
				update_post_meta( $post_id, '_wopb_builder_type', 'archive' );
				$extra                           = $type != 'archive' ? '/' . $type : '';
				$settings['archive'][ $post_id ] = array( 'include/archive' . $extra );
				if ( $type == 'product_cat' ) {
					wp_update_post(
						array(
							'ID'         => $post_id,
							'post_title' => 'Product Category Template',
						)
					);
				} elseif ( $type == 'product_tag' ) {
					wp_update_post(
						array(
							'ID'         => $post_id,
							'post_title' => 'Product Tag Template',
						)
					);
				}
				update_option( 'wopb_builder_conditions', $settings );
				break;
			case 'header':
				update_post_meta( $post_id, '_wopb_builder_type', 'header' );
				$settings['header'][ $post_id ] = array( 'include/header/entire_site' );
				update_option( 'wopb_builder_conditions', $settings );
				break;
			case 'footer':
				update_post_meta( $post_id, '_wopb_builder_type', 'footer' );
				$settings['footer'][ $post_id ] = array( 'include/footer/entire_site' );
				update_option( 'wopb_builder_conditions', $settings );
				break;
			case '404':
				update_post_meta( $post_id, '_wopb_builder_type', '404' );
				$settings['404'][ $post_id ] = array( 'filter/404' );
				update_option( 'wopb_builder_conditions', $settings );
				break;
			default:
				break;
		}
		return $post_id;
	}

	public function builder_draft_to_publish( $post ) {
		if ( get_post_meta( $post->ID, '_wopb_builder_type', true ) == 'home_page' && get_option( 'show_on_front' ) == 'page' ) {
			update_option( 'show_on_front', 'posts' );
		}
	}
}
