<?php
/**
 * REST API Action.
 *
 * @package WOPB\REST_API
 * @since v.1.0.0
 */
namespace WOPB;

use WOPB\blocks\Product_Search;

defined( 'ABSPATH' ) || exit;

/**
 * Styles class.
 */
class REST_API {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'wopb_register_route' ) );
	}


	/**
	 * REST API Action
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function wopb_register_route() {
		register_rest_route(
			'wopb',
			'posts',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'args'                => array(),
				'callback'            => array( $this, 'wopb_route_post_data' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'wopb',
			'category',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'args'                => array(),
				'callback'            => array( $this, 'wopb_route_category_data' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'wopb',
			'common',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'args'                => array(),
				'callback'            => array( $this, 'wopb_route_common_data' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'wopb',
			'preview',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'args'                => array(),
				'callback'            => array( $this, 'wopb_route_preview_data' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'wopb/v2',
			'/template_page_insert/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'template_page_insert' ),
					'permission_callback' => function () {
						return apply_filters( 'wopb_rest_api_capability', current_user_can( 'manage_options' ) );
					},
					'args'                => array(),
				),
			)
		);
		register_rest_route(
			'wopb/v1',
			'/search/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'search_settings_action' ),
					'permission_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
					'args'                => array(),
				),
			)
		);
		register_rest_route(
			'wopb',
			'/product-filter/',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'product_filter' ),
				'permission_callback' => '__return_true',
				'args'                => array(),
			),
		);
		register_rest_route(
			'wopb',
			'product-search',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'product_search' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'wopb',
			'views',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'popular_posts_tracker_callback' ),
				'permission_callback' => '__return_true',
			)
		);
		register_rest_route(
			'wopb/v1', // Add version
			'/get-pd-taxonomy-data', // Remove trailing slash
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_product_taxonomy_data' ),
				'permission_callback' => '__return_true',
			)
		);
	}


	/**
	 * Post View Counter for Every Post
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function popular_posts_tracker_callback( $server ) {
		$params = $server->get_params();
		if ( ! isset( $params['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $params['wpnonce'] ) ), 'wopb-nonce' ) ) {
			die();
		}
		$post_id = sanitize_text_field( $params['postID'] );
		if ( $post_id ) {
			// View Product Count
			$count = (int) get_post_meta( $post_id, '__product_views_count', true );
			update_post_meta( $post_id, '__product_views_count', $count ? (int) $count + 1 : 1 );

			// Recently View Products
			$viewed_products = empty( $_COOKIE['__wopb_recently_viewed'] ) ? array() : (array) explode( '|', sanitize_text_field( wp_unslash( $_COOKIE['__wopb_recently_viewed'] ) ) );
			if ( ! in_array( $post_id, $viewed_products ) ) {
				$viewed_products[] = $post_id;
			}
			if ( sizeof( $viewed_products ) > 30 ) {
				array_shift( $viewed_products );
			}
			wc_setcookie( '__wopb_recently_viewed', implode( '|', $viewed_products ) );

			return rest_ensure_response( array() );
		}
	}


	/**
	 * Taxonomy Data Response of REST API
	 *
	 * @since v.2.3.7
	 * @param ARRAY | Parameter (ARRAY)
	 * @return ARRAY | \WP_REST_Response Taxonomy List as Array
	 */
	public function wopb_route_common_data( $prams ) {
		if ( ! isset( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return rest_ensure_response( array() );
		}

		$action_type = isset( $prams['action_type'] ) ? esc_attr( $prams['action_type'] ) : '';

		// Stock Status
		$stock_status = array();
		foreach ( wc_get_product_stock_status_options() as $key => $status ) {
			$temp = array(
				'value' => $key,
				'label' => $status,
			);
			if ( $action_type == 'status' ) {
				$temp['count'] = wopb_function()->generate_stock_status_count_query( $key, $prams );
			}
			$stock_status[] = $temp;
		}
		if ( $action_type == 'status' ) {
			return $stock_status;
		}

		// Global Customizer
		$global = get_option( 'productx_global', array() );
		// Image Size
		$image_sizes = wopb_function()->get_image_size();

		$post_type_data = array();
		$post_types     = wopb_function()->get_post_type();
		foreach ( $post_types as $post_type_slug => $post_type ) {
			$taxonomies     = $post_type_slug == 'product' ? array_diff( get_object_taxonomies( $post_type_slug ), array( 'product_type', 'product_visibility', 'product_shipping_class' ) ) : get_object_taxonomies( $post_type_slug );
			$taxonomy_array = array();
			foreach ( $taxonomies as $key => $taxonomy_slug ) {
				$taxonomy   = get_taxonomy( $taxonomy_slug );
				$terms      = get_terms(
					array(
						'taxonomy'   => $taxonomy_slug,
						'hide_empty' => false,
					)
				);
				$term_array = array();
				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $k => $term ) {
						$term_array[ urldecode_deep( $term->slug ) ] = array(
							'id'   => $term->term_id,
							'name' => $term->name,
						);
					}
				}
				$taxonomy_array[] = array(
					'name'  => $taxonomy->name,
					'label' => $taxonomy->label,
					'terms' => $term_array,
				);
			}
			$post_type_data[ $post_type_slug ] = $taxonomy_array;
		}

		return rest_ensure_response(
			array(
				'tag'                => wopb_function()->taxonomy( 'product_tag' ),
				'cat'                => wopb_function()->taxonomy( 'product_cat' ),
				'global'             => $global,
				'image'              => $image_sizes,
				'post_type_taxonomy' => $post_type_data,
				'post_type'          => wp_json_encode( $post_types ),
				'stock_status'       => $stock_status,
			)
		);
	}

	/**
	 * Builder Preview Data
	 *
	 * @since v.2.3.7
	 * @param ARRAY | Parameters of the REST API
	 * @return ARRAY | Response as Array
	 */
	public function wopb_route_preview_data( $prams ) {
		if ( ! isset( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return rest_ensure_response( array() );
		}

		global $product;
		$post_id = isset( $prams['previews'] ) ? $prams['previews'] : '';
		if ( $post_id ) {
			$post_data = array();
			$products  = wc_get_product( $post_id );
			switch ( $prams['type'] ) {
				case 'title':
					$post_data['title'] = $products->get_title();
					break;
				case 'description':
					$post_data['description'] = $products->get_description();
					break;
				case 'short':
					$post_data['short'] = $products->get_short_description();
					break;
				case 'image':
					$images        = array();
					$feature_image = $products->get_image_id();
					if ( ! empty( $feature_image ) ) {
						$images[] = esc_url( wp_get_attachment_image_url( $feature_image, 'full' ) );
					}
					$galleries = $products->get_gallery_image_ids();
					if ( ! empty( $galleries ) ) {
						foreach ( $galleries as $key => $val ) {
							$images[] = esc_url( wp_get_attachment_image_url( $val, 'full' ) );
						}
					}
					$sales                     = $products->get_sale_price();
					$regular                   = $products->get_regular_price();
					$post_data['images']       = $images;
					$post_data['images_thumb'] = $images;
					$post_data['percentage']   = ( $regular && $sales ) ? round( ( ( $regular - $sales ) / $regular ) * 100 ) : 0;
					break;
				case 'meta':
					$post_data['sku']      = $products->get_sku();
					$post_data['category'] = '<div className="meta-block__cat">' . wp_kses_post( $this->list_items( $products->get_category_ids(), 'product_cat' ) ) . '</div>';
					$post_data['tag']      = '<div className="meta-block__tag">' . wp_kses_post( $this->list_items( $products->get_tag_ids(), 'product_tag' ) ) . '</div>';
					break;
				case 'price':
					$sales                      = $products->get_sale_price();
					$regular                    = $products->get_regular_price();
					$post_data['sales_price']   = $sales;
					$post_data['regular_price'] = $regular;
					$post_data['range_price']   = ! $regular && ! $sales ? $products->get_price_html() : '';
					$post_data['percentage']    = ( $regular && $sales ) ? round( ( ( $regular - $sales ) / $regular ) * 100 ) : 0;
					$post_data['symbol']        = get_woocommerce_currency_symbol();
					break;
				case 'rating':
					$post_data['sales']   = $products->get_total_sales();
					$post_data['rating']  = $products->get_review_count();
					$post_data['average'] = ( ( $products->get_average_rating() / 5 ) * 100 ) . '%';
					break;
			}
			return array(
				'type' => 'data',
				'data' => $post_data,
			);
		}

		$data     = array(
			array(
				'value' => '',
				'label' => '-- Select Product --',
			),
		);
		$products = wc_get_products(
			array(
				'posts_per_page' => 10,
				'post_status'    => 'publish',
			)
		);
		foreach ( $products as $key => $val ) {
			$data[] = array(
				'value' => $val->get_id(),
				'label' => $val->get_title(),
			);
		}
		return array(
			'type' => 'list',
			'list' => $data,
		);
	}


	public function list_items( $terms, $type ) {
		$inc     = 1;
		$content = '';
		foreach ( $terms as $term_id ) {
			$term = get_term_by( 'id', $term_id, $type );
			if ( $inc > 1 ) {
				$content .= ', ';
			}
			$content .= '<a class="meta-block__value" href="#">' . esc_html( $term->name ) . '</a>';
			++$inc;
		}
		return $content;
	}


	/**
	 * REST API Action
	 *
	 * @since v.1.0.0
	 * @param ARRAY | Parameters of the REST API
	 * @return ARRAY | Response as Array
	 */
	public function wopb_route_category_data( $prams ) {
		if ( ! isset( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return rest_ensure_response( array() );
		}

		$queryCat    = isset( $prams['queryCat'] ) ? wopb_function()->rest_sanitize_params( $prams['queryCat'] ) : '';
		$queryNumber = isset( $prams['queryNumber'] ) ? wopb_function()->rest_sanitize_params( $prams['queryNumber'] ) : '';
		$queryType   = isset( $prams['queryType'] ) ? wopb_function()->rest_sanitize_params( $prams['queryType'] ) : '';
		$data        = wopb_function()->get_category_data( json_decode( $queryCat ), $queryNumber, $queryType );
		return rest_ensure_response( $data );
	}


	/**
	 * Post Data Response of REST API
	 *
	 * @since v.1.0.0
	 * @param MIXED | Pram (ARRAY), Local (BOOLEAN)
	 * @return ARRAY | Response Image Size as Array
	 */
	public function wopb_route_post_data( $prams ) {
		if ( ! isset( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return;
		}

		$data = array();
		$loop = new \WP_Query( wopb_function()->get_query( wopb_function()->rest_sanitize_params( $prams ) ) );

		if ( $loop->have_posts() ) {
			while ( $loop->have_posts() ) {
				$loop->the_post();
				$var                   = array();
				$post_id               = get_the_ID();
				$product               = wc_get_product( $post_id );
				$user_id               = get_the_author_meta( 'ID' );
				$var['post_id']        = $post_id;
				$var['title']          = get_the_title();
				$var['permalink']      = get_permalink();
				$var['excerpt']        = $product->get_short_description();
				$var['time']           = get_the_date();
				$var['price_sale']     = $product->get_sale_price();
				$var['price_regular']  = $product->get_regular_price();
				$var['discount']       = ( $var['price_sale'] && $var['price_regular'] ) ? round( ( $var['price_regular'] - $var['price_sale'] ) / $var['price_regular'] * 100 ) . '%' : '';
				$var['sale']           = $product->is_on_sale();
				$var['price_html']     = $product->get_price_html();
				$var['stock']          = $product->get_stock_status();
				$var['featured']       = $product->is_featured();
				$var['rating_count']   = $product->get_rating_count();
				$var['rating_average'] = $product->get_average_rating();
				$var['type']           = $product->get_type();

				$time_to     = $product->get_date_on_sale_to() ? strtotime( $product->get_date_on_sale_to() ) : '';
				$var['deal'] = ( $var['price_sale'] && $time_to > current_time( 'timestamp' ) ) ? gmdate( 'Y/m/d', $time_to ) : '';

				// image
				if ( has_post_thumbnail() ) {
					$thumb_id    = get_post_thumbnail_id( $post_id );
					$image_sizes = wopb_function()->get_image_size();
					$image_src   = array();
					foreach ( $image_sizes as $key => $value ) {
						$image_src[ $key ] = esc_url( wp_get_attachment_image_src( $thumb_id, $key, false )[0] );
					}
					$var['image'] = $image_src;
				} else {
					$var['image']['full'] = esc_url( wc_placeholder_img_src( 'full' ) );
				}

				// tag
				$tag = get_the_terms( $post_id, ( isset( $prams['tag'] ) ? esc_attr( $prams['tag'] ) : 'product_tag' ) );
				if ( ! empty( $tag ) ) {
					$v = array();
					foreach ( $tag as $val ) {
						$v[] = array(
							'slug' => $val->slug,
							'name' => $val->name,
							'url'  => esc_url( get_term_link( $val->term_id ) ),
						);
					}
					$var['tag'] = $v;
				}

				// cat
				$cat = get_the_terms( $post_id, ( isset( $prams['cat'] ) ? esc_attr( $prams['cat'] ) : 'product_cat' ) );
				if ( ! empty( $cat ) ) {
					$v = array();
					foreach ( $cat as $val ) {
						$v[] = array(
							'slug' => $val->slug,
							'name' => $val->name,
							'url'  => esc_url( get_term_link( $val->term_id ) ),
						);
					}
					$var['category'] = $v;
				}
				$data[] = $var;
			}
			wp_reset_postdata();
		}
		return rest_ensure_response( $data );
	}

	/**
	 * Insert Post For Imported Template
	 *
	 * @since v.2.3.8
	 * @param STRING
	 * @return ARRAY | Inserted Post Url
	 */
	public function template_page_insert( $server ) {
		$post     = $server->get_params();
		$new_page = array(
			'post_title'   => sanitize_text_field( $post['title'] ),
			'post_type'    => 'page',
			'post_content' => wp_slash( $post['blockCode'] ),
			'post_status'  => 'draft',
		);
		$post_id  = wp_insert_post( $new_page );
		return array(
			'success' => true,
			'link'    => get_edit_post_link( $post_id ),
		);
	}

	/**
	 * Search from editor settings
	 *
	 * @since v.2.6.0
	 * @param STRING
	 * @return ARRAY | Inserted Post Url
	 */
	public function search_settings_action( $server ) {
		global $wpdb;
		$post           = $server->get_params();
		$type           = isset( $post['type'] ) ? wopb_function()->rest_sanitize_params( $post['type'] ) : '';
		$condition_type = isset( $post['condition'] ) ? wopb_function()->rest_sanitize_params( $post['condition'] ) : '';
		$term_type      = isset( $post['term'] ) ? wopb_function()->rest_sanitize_params( $post['term'] ) : '';
		switch ( $type ) {
			case 'posts':
			case 'allpost':
			case 'postExclude':
				$post_type = array( 'post' );
				if ( $type == 'allpost' ) {
					$post_type = array_keys( wopb_function()->get_post_type() );
				} elseif ( $type == 'postExclude' ) {
					$post_type = array( $condition_type );
				}
				$args = array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					'posts_per_page' => 10,
				);
				if ( is_numeric( $term_type ) ) {
					$args['p'] = $term_type;
				} else {
					$args['s'] = $term_type;
				}

				$post_results = new \WP_Query( $args );
				$data         = array();
				if ( ! empty( $post_results ) ) {
					while ( $post_results->have_posts() ) {
						$post_results->the_post();
						$id     = get_the_ID();
						$title  = html_entity_decode( get_the_title() );
						$data[] = array(
							'value' => $id,
							'title' => ( $title ? '[ID: ' . $id . '] ' . $title : ( '[ID: ' . $id . ']' ) ),
						);
					}
					wp_reset_postdata();
				}
				return array(
					'success' => true,
					'data'    => $data,
				);
				break;

			case 'product':
			case 'allproduct':
			case 'productInclude':
			case 'productExclude':
				$post_type = array( 'product' );
				if ( $type == 'allproduct' ) {
					$post_type = array_keys( wopb_function()->get_post_type() );
				} elseif ( $type == 'productInclude' || $type == 'productExclude' ) {
					$post_type = array( $condition_type );
				}
				$args = array(
					'post_type'      => $post_type,
					'post_status'    => 'publish',
					'posts_per_page' => 10,
				);
				if ( is_numeric( $term_type ) ) {
					$args['p'] = $term_type;
				} else {
					$args['s'] = $term_type;
				}
				if ( $term_type ) {
					$args['search_key'] = $term_type;
					add_filter( 'posts_search', array( $this, 'searchProducts' ), 100, 2 );
					add_filter( 'posts_distinct', array( $this, 'searchProductsDistinct' ), 100, 2 );
					add_filter( 'posts_join', array( wopb_function(), 'custom_post_join' ), 100, 2 );
				}

				$post_results = new \WP_Query( $args );

				remove_filter( 'posts_search', array( $this, 'searchProducts' ), 100, 2 );
				remove_filter( 'posts_distinct', array( $this, 'searchProductsDistinct' ), 100, 2 );
				remove_filter( 'posts_join', array( wopb_function(), 'custom_post_join' ), 100, 2 );

				$data = array();
				if ( ! empty( $post_results ) ) {
					while ( $post_results->have_posts() ) {
						$post_results->the_post();
						$id     = get_the_ID();
						$title  = wp_strip_all_tags( html_entity_decode( get_the_title() ) );
						$data[] = array(
							'value' => $id,
							'title' => ( $title ? '[ID: ' . $id . '] ' . $title : ( '[ID: ' . $id . ']' ) ),
						);
					}
					wp_reset_postdata();
				}
				return array(
					'success' => true,
					'data'    => $data,
				);
				break;

			case 'author':
				$term = '%' . $wpdb->esc_like( $term_type ) . '%';
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$post_results = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT ID, display_name 
                        FROM $wpdb->users 
                        WHERE user_login LIKE %s OR ID LIKE %s OR user_nicename LIKE %s OR user_email LIKE %s OR display_name LIKE %s LIMIT 10",
						$term,
						$term,
						$term,
						$term,
						$term
					)
				);
				$data         = array();
				if ( ! empty( $post_results ) ) {
					foreach ( $post_results as $key => $val ) {
						$data[] = array(
							'value' => $val->ID,
							'title' => '[ID: ' . $val->ID . '] ' . $val->display_name,
						);
					}
				}
				return array(
					'success' => true,
					'data'    => $data,
				);
				break;

			case 'taxValue':
				$split     = explode( '###', $condition_type );
				$condition = $split && isset( $split[1] ) && $split[1] != 'multiTaxonomy' ? array( $split[1] ) : get_object_taxonomies( $split[0] );
				$args      = array(
					'taxonomy'   => $condition,
					'fields'     => 'all',
					'orderby'    => 'id',
					'order'      => 'ASC',
					'name__like' => $term_type,
				);
				if ( is_numeric( $term_type ) ) {
					unset( $args['name__like'] );
					$args['include'] = array( $term_type );
				}

				$post_results = get_terms( $args );
				$data         = array();
				if ( ! empty( $post_results ) ) {
					foreach ( $post_results as $key => $val ) {
						$taxonomy = get_taxonomy( $val->taxonomy );
						if ( $split && isset( $split[1] ) && $split[1] == 'multiTaxonomy' ) {
							$data[] = array(
								'value' => $val->taxonomy . '###' . $val->slug,
								'title' => '[ID: ' . $val->term_id . '] ' . $taxonomy->label . ': ' . $val->name,
							);
						} else {
							$data[] = array(
								'value' => $val->slug,
								'title' => '[ID: ' . $val->term_id . '] ' . $val->name,
							);
						}
					}
				}
				return array(
					'success' => true,
					'data'    => $data,
				);
				break;

			case 'taxExclude':
				$condition = get_object_taxonomies( $condition_type );
				$args      = array(
					'taxonomy'   => $condition,
					'fields'     => 'all',
					'orderby'    => 'id',
					'order'      => 'ASC',
					'name__like' => $term_type,
				);
				if ( is_numeric( $term_type ) ) {
					unset( $args['name__like'] );
					$args['include'] = array( $term_type );
				}
				$post_results = get_terms( $args );
				$data         = array();
				if ( ! empty( $post_results ) ) {
					foreach ( $post_results as $key => $val ) {
						$data[] = array(
							'value' => $val->taxonomy . '###' . $val->slug,
							'title' => '[ID: ' . $val->term_id . '] ' . $val->taxonomy . ': ' . $val->name,
						);
					}
				}
				return array(
					'success' => true,
					'data'    => $data,
				);
				break;

			case 'product_cat':
			case 'product_tag':
				$split     = explode( '###', $condition_type );
				$condition = $split && isset( $split[1] ) && $split[1] != 'multiTaxonomy' ? array( $split[1] ) : get_object_taxonomies( $split[0] );
				$args      = array(
					'taxonomy'   => $type,
					'fields'     => 'all',
					'orderby'    => 'id',
					'order'      => 'ASC',
					'name__like' => $term_type,
				);
				if ( is_numeric( $term_type ) ) {
					unset( $args['name__like'] );
					$args['include'] = array( $term_type );
				}

				$post_results = get_terms( $args );
				$data         = array();
				if ( ! empty( $post_results ) ) {
					foreach ( $post_results as $key => $val ) {
						$taxonomy = get_taxonomy( $val->taxonomy );
						if ( $split && isset( $split[1] ) && $split[1] == 'multiTaxonomy' ) {
							$data[] = array(
								'value' => $val->taxonomy . '###' . $val->slug,
								'title' => '[ID: ' . $val->term_id . '] ' . $taxonomy->label . ': ' . $val->name,
							);
						} else {
							$data[] = array(
								'value' => $val->slug,
								'title' => '[ID: ' . $val->term_id . '] ' . $val->name,
							);
						}
					}
				}
				return array(
					'success' => true,
					'data'    => $data,
				);
				break;

			default:
				return array(
					'success' => true,
					'data'    => array(
						array(
							'value' => '',
							'title' => '- Select -',
						),
					),
				);
				break;
		}
	}

	/**
	 * Product Search Query
	 *
	 * @param $search
	 * @param $query
	 * @return STRING
	 * @since v.3.1.0
	 */
	public function searchProducts( $search, $query ) {
		global $wpdb;

		if ( ! empty( $query->query_vars['s'] ) ) {
			$search_term = $query->query_vars['s'];
			// Properly escape the search term for LIKE queries.
			$search_term_like = '%' . $wpdb->esc_like( $search_term ) . '%';

			// Use parameterized query with $wpdb->prepare().
			$search = $wpdb->prepare(
				" AND (
                {$wpdb->prefix}posts.post_title LIKE %s 
                OR {$wpdb->prefix}posts.post_content LIKE %s 
                OR {$wpdb->prefix}posts.post_excerpt LIKE %s 
                OR (post_meta.meta_key='_sku' AND post_meta.meta_value LIKE %s)
            ) ",
				$search_term_like,
				$search_term_like,
				$search_term_like,
				$search_term_like
			);
		}

		return $search;
	}

	/**
	 * Product Distinct Query
	 *
	 * @param $where
	 * @return STRING
	 * @since v.3.1.0
	 */
	public function searchProductsDistinct( $where ) {
		return 'DISTINCT';
	}

	/**
	 * Get block list by product filtering
	 *
	 * @since v.2.6.5
	 * @param STRING
	 * @return array
	 */
	public function product_filter( $server ) {
		$params = $server->get_params();

		if ( ! isset( $params['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $params['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return array(
				'blockList' => array(),
			);
		}

		$product_filters = $params['product_filters'];
		$post_id         = sanitize_text_field( $params['post_id'] );
		$post            = get_post( $post_id );
		$blockRaw        = sanitize_text_field( $params['block_name'] );
		$blockName       = str_replace( '_', '/', $blockRaw );
		$blocks          = parse_blocks( $post->post_content );
		$params          = array(
			'page_post_id'    => $post_id,
			'current_url'     => sanitize_url( $params['current_url'] ),
			'product_filters' => $product_filters,
			'ajax_source'     => 'filter',
		);

		$block_list = array();
		$block_list = $this->product_filter_block_target( $blocks, $blockName, $blockRaw, $params, $block_list );
		return array(
			'blockList' => $block_list,
		);
	}

	/**
	 * Filter Callback of the Blocks
	 *
	 * @param $blocks
	 * @param $blockName
	 * @param $blockRaw
	 * @param $params
	 * @param $block_list
	 * @return STRING
	 * @since v.2.1.4
	 */
	public function product_filter_block_target( $blocks, $blockName, $blockRaw, $params, &$block_list ) {
		foreach ( $blocks as $key => $value ) {
			if ( $blockName == $value['blockName'] ) {
				$objName = str_replace( ' ', '_', ucwords( join( ' ', explode( '-', explode( '/', $blockName )[1] ) ) ) );
				$obj     = '\WOPB\blocks\\' . $objName;
				$newObj  = new $obj();
				$attr    = $newObj->get_attributes( true );

				$attr         = array_merge( $attr, $params );
				$attr         = array_merge( $attr, $value['attrs'] );
				$block_list[] = array(
					'blockId' => $value['attrs']['blockId'],
					'content' => $newObj->content( $attr, true ),
				);
				remove_filter( 'posts_where', 'title_filter', 1000 );
				remove_filter( 'posts_join', 'custom_join_product_filter', 1000 );
			}
			if ( ! empty( $value['innerBlocks'] ) ) {
				$this->product_filter_block_target( $value['innerBlocks'], $blockName, $blockRaw, $params, $block_list );
			}
		}
		return $block_list;
	}

	/**
	 * Get Product Search Block Search Content
	 *
	 * @since v.2.6.8
	 * @param $server
	 * @return HTML
	 */
	public function product_search( $server ) {
		$data = $server->get_params();
		if ( ! isset( $data['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $data['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return rest_ensure_response( array() );
		}
		$blockId       = isset( $data['blockId'] ) ? sanitize_text_field( $data['blockId'] ) : '';
		$blockRaw      = isset( $data['blockName'] ) ? sanitize_text_field( $data['blockName'] ) : '';
		$blockName     = str_replace( '_', '/', $blockRaw );
		$widgetBlockId = isset( $data['widgetBlockId'] ) ? sanitize_key( $data['widgetBlockId'] ) : '';
		$postId        = isset( $data['postId'] ) ? sanitize_text_field( $data['postId'] ) : '';
		$params        = array(
			'search'   => isset( $data['search'] ) ? sanitize_text_field( $data['search'] ) : '',
			'category' => isset( $data['category'] ) ? sanitize_text_field( $data['category'] ) : '',
		);

		$post_data = get_post( $postId );
		if ( $widgetBlockId ) {
			$blocks = parse_blocks( get_option( 'widget_block' )[ $widgetBlockId ]['content'] );
		} elseif ( has_blocks( $post_data->post_content ) ) {
			$blocks = parse_blocks( $post_data->post_content );
		}

		$this->search_block_attr( $blocks, $blockId, $blockRaw, $blockName, $params );
		if ( isset( $data['attr'] ) && $data['attr'] ) {
			$params['attr'] = $data['attr'];
		}
		$search_param = $this->search_block_param( $params );
		if ( isset( $data['source'] ) && $data['source'] == 'block_editor' ) {
			$search_param['products'] = wopb_function()->product_format( $search_param );
			return $search_param;
		}
		$product_search = new Product_Search();
		ob_start();
			$product_search->search_item_content( $search_param );
		return ob_get_clean();
	}

	/**
	 * Get Product Search Block Search Data
	 *
	 * @since v.2.6.8
	 * @param $blocks
	 * @param $blockId
	 * @param $blockRaw
	 * @param $blockName
	 * @return array
	 */
	public function search_block_attr( $blocks, $blockId, $blockRaw, $blockName, &$params ) {
		foreach ( $blocks as $key => $value ) {
			if ( $blockName == $value['blockName'] && $value['attrs']['blockId'] == $blockId ) {
				$objName        = str_replace( ' ', '_', ucwords( join( ' ', explode( '-', explode( '/', $blockName )[1] ) ) ) );
				$obj            = '\WOPB\blocks\\' . $objName;
				$newObj         = new $obj();
				$attr           = $newObj->get_attributes( true );
				$params['attr'] = array_merge( $attr, $value['attrs'] );
				break;
			}
			if ( ! empty( $value['innerBlocks'] ) ) {
				$this->search_block_attr( $value['innerBlocks'], $blockId, $blockRaw, $blockName, $params );
			}
		}

		return $params;
	}


	/**
	 * Get Product Search Param
	 *
	 * @since v.2.6.8
	 * @param $params
	 * @return array
	 */
	public function search_block_param( $params = array() ) {
		$query_args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		);
		$tax_args   = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
		);
		$tax_terms  = '';

		if ( isset( $params['search'] ) && $params['search'] ) {
			$query_args['filter_search_key'] = $params['search'];
			add_filter( 'posts_join', array( wopb_function(), 'custom_post_join' ), 100, 2 );
			add_filter( 'posts_where', array( wopb_function(), 'custom_post_query' ), 1000, 2 );
			add_filter(
				'posts_distinct',
				function () {
					return 'DISTINCT'; // duplicate data remove
				}
			);

			$tax_args['search'] = $params['search'];
		}
		if ( isset( $params['category'] ) && $params['category'] ) {
			$query_args['tax_query'][] = array(
				'taxonomy' => 'product_cat',
				'field'    => 'slug',
				'terms'    => $params['category'],
				'operator' => 'IN',
			);
		} else {
			$tax_terms = get_terms( $tax_args );
		}

		$products = new \WP_Query( $query_args );
		$params   = array(
			'search'        => $params['search'],
			'attr'          => isset( $params['attr'] ) ? $params['attr'] : '',
			'products'      => $products,
			'total_product' => count( $products->posts ),
			'tax_terms'     => $tax_terms,
		);
		return $params;
	}
	/**
	 * Get Product Search Param
	 *
	 * @since v.2.6.8
	 * @param $params
	 * @return array
	 */
	public function get_product_taxonomy_data() {
		$product_taxonomies = array();
		$object_taxonomies  = array_diff( get_object_taxonomies( 'product' ), array( 'product_type', 'product_visibility', 'product_shipping_class' ) );
		foreach ( $object_taxonomies as $key ) {
			$params          = array(
				'taxonomy'           => $key,
				'product_visibility' => true,
				'term_limit'         => 3,
			);
			$taxonomy        = get_taxonomy( $key );
			$taxonomy->terms = wopb_function()->taxonomy_terms_tree( $params );
			if ( wopb_function()->get_attribute_by_taxonomy( $key ) ) {
				$taxonomy->attribute = wopb_function()->get_attribute_by_taxonomy( $key );
			}
			$product_taxonomies[] = $taxonomy;
		}
		wp_send_json_success( $product_taxonomies );
	}
}
