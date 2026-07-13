<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Revenue Ajax
 *
 * @package Revenue
 */

namespace Revenue;

use WC_AJAX;
use WC_Data_Store;
use WP_Query;

/**
 * Revenue Campaign
 *
 * @hooked on init
 */
class Revenue_Ajax {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_revenue_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wc_ajax_revenue_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_nopriv_revenue_add_to_cart', array( $this, 'add_to_cart' ) );
		add_action( 'wp_ajax_revenue_add_bundle_to_cart', array( $this, 'add_bundle_to_cart' ) );
		add_action( 'wp_ajax_nopriv_revenue_add_bundle_to_cart', array( $this, 'add_bundle_to_cart' ) );
		add_action( 'wp_ajax_revenue_close_popup', array( $this, 'close_popup' ) );
		add_action( 'wp_ajax_nopriv_revenue_close_popup', array( $this, 'close_popup' ) );
		add_action( 'wp_ajax_revenue_count_impression', array( $this, 'count_impression' ) );
		add_action( 'wp_ajax_nopriv_revenue_count_impression', array( $this, 'count_impression' ) );

		add_filter( 'revenue_rest_before_prepare_campaign', array( $this, 'modify_campaign_rest_response' ) );

		add_action( 'wp_ajax_revenue_get_product_price', array( $this, 'get_product_price' ) );

		add_action( 'wp_ajax_revx_get_next_campaign_id', array( $this, 'get_next_campaign_id' ) );

		add_action( 'wp_ajax_revx_get_campaign_limits', array( $this, 'get_campaign_limits' ) );

		add_action( 'wp_ajax_revx_activate_woocommerce', array( $this, 'activate_woocommerce' ) );

		add_action( 'wp_ajax_revx_install_woocommerce', array( $this, 'install_woocommerce' ) );

		add_action( 'wp_ajax_revenue_get_search_suggestion', array( $this, 'get_search_suggestion' ) );
		add_action( 'wp_ajax_revenue_get_cart_total', array( $this, 'get_cart_total' ) );
		add_action( 'wp_ajax_nopriv_revenue_get_cart_total', array( $this, 'get_cart_total' ) );

		add_action( 'wp_ajax_revenue_get_campaign_offer_items', array( $this, 'get_offer_items' ) );

		add_action( 'wp_ajax_revenue_get_trigger_items', array( $this, 'get_trigger_items' ) );

		add_action( 'wp_ajax_nopriv_revenue_get_trigger_items', array( $this, 'get_trigger_items' ) );
	}

	public function get_cart_total() {
		if ( WC()->cart ) {
			// Recalculate totals before getting the cart total
			WC()->cart->calculate_totals();

			// unnecessary as this action is done in calculate_totals()
			// do_action( 'woocommerce_before_calculate_totals', WC()->cart );
		}

		$cart_total    = 0;
		$cart_subtotal = 0;

		if ( wc_prices_include_tax() ) {
			$cart_total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();
		} else {
			$cart_total = WC()->cart->get_cart_contents_total();
		}

		if ( WC()->cart->display_prices_including_tax() ) {
			$cart_subtotal = WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();

		} else {
			$cart_subtotal = WC()->cart->get_subtotal();
		}

		wp_send_json_success(
			array(
				'cart_total'  => $cart_total,
				'subtotal'    => $cart_subtotal,
				'items_count' => WC()->cart ? WC()->cart->get_cart_contents_count() : 0,
			)
		);
	}


	public function get_trigger_items() {

		$nonce = '';
		if ( isset( $_GET['security'] ) ) {
			$nonce = sanitize_key( $_GET['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}

		$type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';

		$trigger_type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';

		$search_keyword = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

		$data = array();

		$source = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : '';

		$campaign_type = isset( $_GET['campaign_type'] ) ? sanitize_text_field( wp_unslash( $_GET['campaign_type'] ) ) : '';

		$response_data = array();

		switch ( $trigger_type ) {
			case 'products':
				$response_data = $this->search_products( $search_keyword );
				break;
			case 'category':
				$response_data = $this->search_categories( $search_keyword );
				break;
			default:
				// code...
				break;
		}

		$response_data = apply_filters( 'revenue_campaign_trigger_items', $response_data, $search_keyword, $trigger_type, $campaign_type );

		wp_send_json( $response_data );
	}

	/**
	 * Search for WooCommerce products by term.
	 *
	 * @param string $term Search term.
	 * @param bool   $include_variations Whether to include product variations.
	 * @return array List of found products.
	 */
	public function search_products( $term, $include_variations = false ) {

		if ( isset( $_GET['limit'] ) && ! empty( wp_unslash( $_GET['limit'] ) ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$limit = absint( wp_unslash( $_GET['limit'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			$limit = absint( apply_filters( 'woocommerce_json_search_limit', 30 ) );
		}
		$source         = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$trigger_action = isset( $_GET['trigger_action'] ) ? sanitize_text_field( wp_unslash( $_GET['trigger_action'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$include_cats   = isset( $_GET['include_cats'] ) ? array_map( 'absint', wp_unslash( $_GET['include_cats'] ) ) : false; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$data_store = WC_Data_Store::load( 'product' );
		$ids        = $data_store->search_products( $term, '', (bool) $include_variations, false, $limit * 2 ); // Fetch more than the limit to account for exclusions.

		$products      = array();
		$campaign_type = isset( $_GET['campaign_type'] ) ? sanitize_text_field( wp_unslash( $_GET['campaign_type'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		foreach ( $ids as $product_id ) {
			$product = wc_get_product( $product_id );

			// Skip non-published products
			if ( ! $product || $product->get_status() !== 'publish' ) {
				continue;
			}

			if ( $product && $product->is_in_stock() ) {

				// Check if trigger_action is "exclude" and validate include_cats.
				if ( $trigger_action === 'exclude' && ! empty( $include_cats ) ) {
					$product_categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
					if ( empty( array_intersect( $product_categories, $include_cats ) ) ) {
						continue; // Skip products not in the included categories.
					}
				}

				$chilren      = $product->get_children();
				$child_data   = array();
				$product_link = get_permalink( $product_id );

				if ( is_array( $chilren ) ) {
					foreach ( $chilren as $child_id ) {
						$child = wc_get_product( $child_id );
						// Skip non-published child variations
						if ( $child && $child->is_in_stock() && $child->get_status() === 'publish' ) {
							$parent_id   = $child->get_parent_id();
							$parent      = wc_get_product( $parent_id );
							$parent_name = $parent ? $parent->get_name() : '';
							$attributes  = $child->get_attributes();

							$attribute_parts = array();

							foreach ( $attributes as $attr_key => $value ) {
								$taxonomy = str_replace( 'attribute_', '', $attr_key );
								// check if the product is an object
								// which means it is a simple product created from a variation.
								// skip it.
								if ( is_object( $value ) ) {
									continue;
								}
								if ( taxonomy_exists( $taxonomy ) ) {
									$taxonomy_obj      = get_taxonomy( $taxonomy );
									$label             = $taxonomy_obj ? $taxonomy_obj->labels->singular_name : ucfirst( $taxonomy );
									$term              = get_term_by( 'slug', $value, $taxonomy );
									$value_name        = $term ? $term->name : $value;
									$attribute_parts[] = "{$label}: {$value_name}";
								} else {
									$label             = ucfirst( str_replace( '_', ' ', $taxonomy ) );
									$attribute_parts[] = "{$label}: {$value}";
								}
							}

							$full_name = $parent_name . ' – ' . implode( ', ', $attribute_parts );

							$child_data[] = array(
								'item_id'       => $child_id,
								'item_name'     => rawurldecode( wp_strip_all_tags( $full_name ) ),
								'thumbnail'     => wp_get_attachment_url( $child->get_image_id() ),
								'regular_price' => $child->get_regular_price(),
								'sale_price'    => $child->get_sale_price(),
								'parent'        => $product_id,
								'parent_id'     => $parent_id,
								'url'           => $child->get_permalink(),
							);
						}
					}
				}

				$product_data = array(
					'item_id'       => $product_id,
					'url'           => get_permalink( $product_id ),
					'item_name'     => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
					'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ),
					'regular_price' => $product->get_regular_price(),
					'sale_price'    => $product->get_sale_price(),
					'children'      => $child_data,
				);

				if ( 'bundle_discount' === $product->get_type() ) {
					// $products = array_merge( $products, $child_data );
				} elseif ( 'trigger' == $source ) {
					switch ( $campaign_type ) {
						case 'normal_discount':
							$product_data['children'] = array();
							$products[]               = $product_data;
							break;
						case 'bundle_discount':
							$product_data['children'] = array();
							$products[]               = $product_data;
							break;
						case 'volume_discount':
							$product_data['children'] = array();
							$products[]               = $product_data;
							break;
						case 'buy_x_get_y':
							$product_data['children'] = array();
							$products[]               = $product_data;
							break;
						case 'mix_match':
							$product_data['children'] = array();
							$products[]               = $product_data;
							break;
						case 'frequently_bought_together':
							$product_data['children'] = array();
							$products[]               = $product_data;
							break;
						default:
							$products[] = $product_data;
							break;
					}
				} else {
					$products[] = $product_data;
				}

				// Break if we reach the limit.
				if ( count( $products ) >= $limit ) {
					break;
				}
			}
		}

		return array_slice( $products, 0, $limit ); // Ensure the final result respects the limit.
	}

	/**
	 * Get trigger and offer search suggestion.
	 *
	 * @return mixed
	 */
	public function get_search_suggestion() {

		$nonce = isset( $_GET['security'] ) ? sanitize_key( $_GET['security'] ) : '';
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}

		$type           = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$source         = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$campaign_type  = isset( $_GET['campaign_type'] ) ? sanitize_text_field( wp_unslash( $_GET['campaign_type'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$trigger_action = isset( $_GET['trigger_action'] ) ? sanitize_text_field( wp_unslash( $_GET['trigger_action'] ) ) : ''; //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$include_cats   = isset( $_GET['include_cats'] ) ? array_map( 'absint', wp_unslash( $_GET['include_cats'] ) ) : false; //phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$data = array();

		if ( 'products' === $type ) {
			$args = array(
				'limit'        => 10, // Fetch more than necessary to account for exclusions.
				'orderby'      => 'date',
				'order'        => 'ASC',
				'stock_status' => 'instock',
			);

			// Only fetch published products
			$args['status'] = 'publish';

			$products = wc_get_products( $args );

			foreach ( $products as $product ) {
				if ( $product && $product->is_in_stock() ) {
					// Handle trigger_action and include_cats filtering.
					if ( 'exclude' === $trigger_action && ! empty( $include_cats ) ) {
						$product_categories = wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'ids' ) );
						if ( empty( array_intersect( $product_categories, $include_cats ) ) ) {
							continue; // Skip products not in the included categories.
						}
					}

					$full_name    = $product ? $product->get_name() : '';
					$children     = $product->get_children();
					$child_data   = array();
					$product_link = get_permalink( $product->get_id() );

					if ( is_array( $children ) ) {
						foreach ( $children as $child_id ) {
							$child = wc_get_product( $child_id );
							if ( 'offer' === $source ) {
								if ( $child && $child->is_type( 'variation' ) ) {
									$parent_id   = $child->get_parent_id();
									$parent      = wc_get_product( $parent_id );
									$parent_name = $parent ? $parent->get_name() : '';
									$attributes  = $child->get_attributes();

									$attribute_parts = array();

									foreach ( $attributes as $attr_key => $value ) {
										$taxonomy = str_replace( 'attribute_', '', $attr_key );
										if ( taxonomy_exists( $taxonomy ) ) {
											$taxonomy_obj      = get_taxonomy( $taxonomy );
											$label             = $taxonomy_obj ? $taxonomy_obj->labels->singular_name : ucfirst( $taxonomy );
											$term              = get_term_by( 'slug', $value, $taxonomy );
											$value_name        = $term ? $term->name : $value;
											$attribute_parts[] = "{$label}: {$value_name}";
										} else {
											$label             = ucfirst( str_replace( '_', ' ', $taxonomy ) );
											$attribute_parts[] = "{$label}: {$value}";
										}
									}

									$child_name = $parent_name . ' – ' . implode( ', ', $attribute_parts );
								}
							}

							if ( $child && $child->is_in_stock() ) {
								// Skip non-published child variations
								if ( $child->get_status() !== 'publish' ) {
									continue;
								}
								$child_data[] = array(
									'item_id'       => $child_id,
									'item_name'     => $source === 'offer' ? $child_name : $full_name,
									'thumbnail'     => wp_get_attachment_url( $child->get_image_id() ),
									'regular_price' => $child->get_regular_price(),
									'parent_id'     => $product->get_id(),
									'url'           => $child->get_permalink(),
								);
							}
						}
					}

					$product_data = array(
						'item_id'        => $product->get_id(),
						'url'            => $product_link,
						// 'item_name'      => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
						'item_name'      => $full_name,
						'thumbnail'      => wp_get_attachment_url( $product->get_image_id() ),
						'regular_price'  => $product->get_regular_price(),
						'children'       => $child_data,
						'show_attribute' => 'variable' === $product->get_type(),
					);

					if ( 'trigger' == $source ) {
						switch ( $campaign_type ) {
							case 'normal_discount':
								$product_data['children'] = array();
								$data[]                   = $product_data;
								break;
							case 'bundle_discount':
								$product_data['children'] = array();
								$data[]                   = $product_data;
								break;
							case 'volume_discount':
								$product_data['children'] = array();
								$data[]                   = $product_data;
								break;
							case 'buy_x_get_y':
								$product_data['children'] = array();
								$data[]                   = $product_data;
								break;
							case 'mix_match':
								$product_data['children'] = array();
								$data[]                   = $product_data;
								break;
							case 'frequently_bought_together':
								$product_data['children'] = array();
								$data[]                   = $product_data;
								break;
							default:
								$data[] = $product_data;
								break;
						}
					} else {
						$data[] = $product_data;
					}
				}
			}
		} elseif ( 'category' === $type ) {
			$category_args = array(
				'taxonomy' => 'product_cat',
				'number'   => 5,
				'orderby'  => 'name',
				'order'    => 'ASC',
			);

			$categories = get_terms( $category_args );

			foreach ( $categories as $category ) {
				if ( ! is_wp_error( $category ) ) {
					$data[] = array(
						'item_id'   => $category->term_id,
						'item_name' => $category->name,
						'url'       => get_term_link( $category ),
						'thumbnail' => get_term_meta( $category->term_id, 'thumbnail_id', true )
							? wp_get_attachment_url( get_term_meta( $category->term_id, 'thumbnail_id', true ) )
							: wc_placeholder_img_src(),
					);
				}
			}
		}

		// Limit the final output to ensure it respects the requested number.
		$data = array_slice( $data, 0, 5 ); // Adjust to your desired limit.

		$data = apply_filters( 'revenue_campaign_search_suggestion_data', $data, $type, $campaign_type, $source );

		wp_send_json_success( $data );
	}

	public function search_categories( $term ) {

		$found_categories = array();
		$args             = array(
			'taxonomy'   => array( 'product_cat' ),
			'orderby'    => 'id',
			'order'      => 'ASC',
			'hide_empty' => false,
			'fields'     => 'all',
			'name__like' => $term,
		);

		$terms = get_terms( $args );

		$data = array();

		if ( $terms ) {
			foreach ( $terms as $term ) {
				$term->formatted_name = '';

				$ancestors = array();
				if ( $term->parent ) {
					$ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat' ) );
					foreach ( $ancestors as $ancestor ) {
						$ancestor_term = get_term( $ancestor, 'product_cat' );
						if ( $ancestor_term ) {
							$term->formatted_name .= $ancestor_term->name . ' > ';
						}
					}
				}

				$term->parents                      = $ancestors;
				$term->formatted_name              .= $term->name . ' (' . $term->count . ')';
				$found_categories[ $term->term_id ] = $term;

				$data[] = array(
					'item_id'   => $term->term_id,
					'item_name' => $term->name,
					'url'       => get_term_link( $term ),
					'thumbnail' => get_term_meta( $term->term_id, 'thumbnail_id', true )
						? wp_get_attachment_url( get_term_meta( $term->term_id, 'thumbnail_id', true ) )
						: wc_placeholder_img_src(),
				);
			}
		}

		return $data;
	}


	/**
	 * Get next campaign id.
	 *
	 * @return mixed
	 */
	public function get_next_campaign_id() {
		$nonce = '';
		if ( isset( $_POST['security'] ) ) {
			$nonce = sanitize_key( $_POST['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}

		global $wpdb;
		$res = $wpdb->get_row( "SELECT COALESCE(MAX(id), 0) + 1 AS next_campaign_id FROM {$wpdb->prefix}revenue_campaigns;" ); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return wp_send_json_success( array( 'next_campaign_id' => $res->next_campaign_id ) );
	}

	/**
	 * Get Product price
	 *
	 * @return mixed
	 */
	public function get_product_price() {
		check_ajax_referer( 'revenue-get-product-price', false );

		$product_id = isset( $_GET['product_id'] ) ? sanitize_text_field( wp_unslash( $_GET['product_id'] ) ) : '';

		$product = wc_get_product( $product_id );
		$data    = array();
		if ( $product ) {
			$data['sale_price']    = $product->get_sale_price();
			$data['regular_price'] = $product->get_regular_price();
		}

		return wp_send_json_success( $data );
	}

	/**
	 * Modify campaign rest response
	 *
	 * @param array $data Data.
	 * @return mixed
	 */
	public function modify_campaign_rest_response( $data ) {

		if ( empty( $data['is_show_free_shipping_bar'] ) ) {
			$data['is_show_free_shipping_bar'] = 'yes';
		}

		if ( empty( $data['all_goals_complete_message'] ) ) {
			$data['all_goals_complete_message'] = __( 'Awesome! 😊 You’ve unlocked the ultimate reward! 🏆', 'revenue' );
		}

		if ( empty( $data['campaign_display_style'] ) ) {
			$data['campaign_display_style'] = 'inpage';
		}
		if ( empty( $data['campaign_builder_view'] ) ) {
			$data['campaign_builder_view'] = 'list';
		}
		if ( is_null( $data['offers'] ) ) {
			$data['offers'] = array();
		}

		if ( ! isset( $data['add_to_cart_animation_type'] ) ) {
			$data['add_to_cart_animation_type'] = 'shake';
		}
		if ( isset( $data['offers'] ) ) {

			foreach ( $data['offers'] as $idx => $offer ) {

				$products_data = array();
				foreach ( $offer['products'] as $product_id ) {
					if ( ! $product_id ) {
						continue;
					}

					$parent_id = '';
					$parent    = '';
					$product   = wc_get_product( $product_id );

					if ( $product && $product->is_type( 'variation' ) ) {
						$parent_id   = $product->get_parent_id();
						$parent      = wc_get_product( $parent_id );
						$parent_name = $parent ? $parent->get_name() : '';
						$attributes  = $product->get_attributes();

						$attribute_parts = array();

						foreach ( $attributes as $key => $value ) {
							$taxonomy = str_replace( 'attribute_', '', $key );
							if ( taxonomy_exists( $taxonomy ) ) {
								$taxonomy_obj      = get_taxonomy( $taxonomy );
								$label             = $taxonomy_obj ? $taxonomy_obj->labels->singular_name : ucfirst( $taxonomy );
								$term              = get_term_by( 'slug', $value, $taxonomy );
								$value_name        = $term ? $term->name : $value;
								$attribute_parts[] = "{$label}: {$value_name}";
							} else {
								$label             = ucfirst( str_replace( '_', ' ', $taxonomy ) );
								$attribute_parts[] = "{$label}: {$value}";
							}
						}

						$full_name = $parent_name . ' – ' . implode( ', ', $attribute_parts );
					} else {
						$full_name = $product ? $product->get_name() : '';
					}

					if ( $product ) {
						$products_data[] = array(
							'item_id'       => $product_id,
							'item_name'     => rawurldecode( wp_strip_all_tags( $full_name ) ),
							'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ),
							'regular_price' => $product->get_regular_price(),
							'url'           => get_permalink( $product_id ),
							'parent_id'     => $parent_id,
						);
					}
				}
				$data['offers'][ $idx ]['products'] = $products_data;
			}
		}

		if ( is_null( $data['campaign_start_date_time'] ) ) {
			$data['campaign_start_date'] = gmdate( 'Y-m-d', time() );
			$data['campaign_start_time'] = gmdate( 'H:00', time() );
		} else {
			$timestamp                   = strtotime( $data['campaign_start_date_time'] );
			$data['campaign_start_date'] = gmdate( 'Y-m-d', $timestamp );
			$data['campaign_start_time'] = gmdate( 'H:i', $timestamp );
		}

		if ( isset( $data['schedule_end_time_enabled'] ) && 'yes' === $data['schedule_end_time_enabled'] ) {
			if ( is_null( $data['campaign_end_date_time'] ) ) {
				$data['campaign_end_date'] = gmdate( 'Y-m-d', time() );
				$data['campaign_end_time'] = gmdate( 'H:00', time() );
			} else {
				$timestamp                 = strtotime( $data['campaign_end_date_time'] );
				$data['campaign_end_date'] = gmdate( 'Y-m-d', $timestamp );
				$data['campaign_end_time'] = gmdate( 'H:i', $timestamp );
			}
		}

		if ( is_null( $data['builder'] ) ) {
			unset( $data['builder'] );
		}

		if ( is_null( $data['builderdata'] ) ) {
			unset( $data['builderdata'] );
		}

		if ( isset( $data['campaign_type'] ) && 'mix_match' === $data['campaign_type'] ) {
			$data['campaign_trigger_relation'] = 'and';
		} elseif ( empty( $data['campaign_trigger_relation'] ) ) {
				$data['campaign_trigger_relation'] = 'or';
		}

		if ( empty( $data['campaign_placement'] ) && 'next_order_coupon' == $data['campaign_type'] ) {
			$data['campaign_placement']       = 'thankyou_page';
			$data['campaign_inpage_position'] = 'before_thankyou';
		}

		if ( empty( $data['campaign_placement'] ) ) {
			$data['campaign_placement'] = 'multiple';
		}

		if ( empty( $data['campaign_trigger_type'] ) ) {
			$data['campaign_trigger_type'] = 'products';
		}
		if ( empty( $data['offered_product_click_action'] ) ) {
			$data['offered_product_click_action'] = 'go_to_product';
		}

		if ( empty( $data['add_to_cart_animation_trigger_type'] ) ) {
			$data['add_to_cart_animation_trigger_type'] = 'on_hover';
		}
		if ( empty( $data['countdown_start_time_status'] ) ) {
			$data['countdown_start_time_status'] = 'right_now';
		}

		if ( isset( $data['campaign_placement'] ) && 'multiple' != $data['campaign_placement'] ) {
			if ( 'double_order' == $data['campaign_type'] ) {
				$data['placement_settings'] = array(
					$data['campaign_placement'] => array(
						'page'                     => $data['campaign_placement'],
						'status'                   => 'yes',
						'display_style'            => $data['campaign_display_style'] ?? 'inpage',
						'builder_view'             => $data['campaign_builder_view'],
						'inpage_position'          => $data['campaign_inpage_position'] ? $data['campaign_inpage_position'] : 'review_order_before_payment',
						'popup_animation'          => $data['campaign_popup_animation'],
						'popup_animation_delay'    => $data['campaign_popup_animation_delay'],
						'floating_position'        => $data['campaign_floating_position'],
						'floating_animation_delay' => $data['campaign_floating_animation_delay'],
						'drawer_position'          => 'top-left',
					),
				);
			} elseif ( 'stock_scarcity' == $data['campaign_type'] ) {
				$data['placement_settings'] = array(
					$data['campaign_placement'] => array(
						'page'                     => $data['campaign_placement'],
						'status'                   => 'yes',
						'display_style'            => $data['campaign_display_style'] ?? 'inpage',
						'builder_view'             => $data['campaign_builder_view'],
						'inpage_position'          => 'rvex_below_the_product_title',
						'popup_animation'          => $data['campaign_popup_animation'],
						'popup_animation_delay'    => $data['campaign_popup_animation_delay'],
						'floating_position'        => $data['campaign_floating_position'],
						'floating_animation_delay' => $data['campaign_floating_animation_delay'],
						'drawer_position'          => 'top-left',
					),
				);
			} elseif ( 'next_order_coupon' == $data['campaign_type'] ) {
				$data['placement_settings'] = array(
					$data['campaign_placement'] => array(
						'page'                     => $data['campaign_placement'],
						'status'                   => 'yes',
						'display_style'            => $data['campaign_display_style'] ?? 'inpage',
						'builder_view'             => $data['campaign_builder_view'],
						'inpage_position'          => 'before_thankyou',
						'popup_animation'          => $data['campaign_popup_animation'],
						'popup_animation_delay'    => $data['campaign_popup_animation_delay'],
						'floating_position'        => $data['campaign_floating_position'],
						'floating_animation_delay' => $data['campaign_floating_animation_delay'],
						'drawer_position'          => 'top-left',
					),
				);
			} else {
				$data['placement_settings'] = array(
					$data['campaign_placement'] => array(
						'page'                     => $data['campaign_placement'],
						'status'                   => 'yes',
						'display_style'            => $data['campaign_display_style'] ?? 'inpage',
						'builder_view'             => $data['campaign_builder_view'],
						'inpage_position'          => $data['campaign_inpage_position'] ? $data['campaign_inpage_position'] : 'before_add_to_cart_form',
						'popup_animation'          => $data['campaign_popup_animation'],
						'popup_animation_delay'    => $data['campaign_popup_animation_delay'],
						'floating_position'        => $data['campaign_floating_position'],
						'floating_animation_delay' => $data['campaign_floating_animation_delay'],
						'drawer_position'          => 'top-left',
					),
				);
			}

			$data['placement_settings']       = $data['placement_settings'];
			$data['campaign_placement']       = 'multiple';
			$data['campaign_display_style']   = 'multiple';
			$data['campaign_inpage_position'] = 'multiple';
		}

		if ( empty( $data['offered_product_on_cart_action'] ) ) {
			$data['offered_product_on_cart_action'] = 'do_nothing';
		}
		if ( empty( $data['multiple_variation_selection_enabled'] ) ) {
			$data['multiple_variation_selection_enabled'] = 'no';
		}
		if ( empty( $data['active_page'] ) && ! empty( $data['placement_settings'] ) ) {
			$placement_setting   = (array) $data['placement_settings'];
			$data['active_page'] = ! empty( $placement_setting ) ? array_keys( $placement_setting )[0] : 'product_page';
		}

		if ( ! isset( $data['double_order_animation_type'] ) ) {
			$data['double_order_animation_type'] = 'shake';
		}

		if ( isset( $data['campaign_type'] ) && 'next_order_coupon' === $data['campaign_type'] ) {
			if ( isset( $data['revx_next_order_coupon'] ) ) {
				$coupon_id                                     = $data['revx_next_order_coupon']['choose_next_order_coupon'] ?? '';
				$coupon_code                                   = wc_get_coupon_code_by_id( $coupon_id );
				$data['revx_next_order_coupon']['coupon_code'] = $coupon_code ? $coupon_code : '';
			}
		}

		return $data;
	}


	/**
	 * Reveneux Add to cart
	 * Unified function supporting both old and new versions
	 *
	 * @return mixed
	 */
	public function add_to_cart() {

		check_ajax_referer( 'revenue-add-to-cart', false );

		$product_id   = isset( $_POST['productId'] ) ? sanitize_text_field( wp_unslash( $_POST['productId'] ) ) : '';
		$campaign_id  = isset( $_POST['campaignId'] ) ? sanitize_text_field( wp_unslash( $_POST['campaignId'] ) ) : '';
		$quantity     = isset( $_POST['quantity'] ) ? sanitize_text_field( wp_unslash( $_POST['quantity'] ) ) : '';
		$index        = isset( $_POST['index'] ) ? sanitize_text_field( wp_unslash( $_POST['index'] ) ) : '';
		$variation_id = isset( $_POST['variationId'] ) ? sanitize_text_field( wp_unslash( $_POST['variationId'] ) ) : 0;
		$attributes   = isset( $_POST['selectedAttr'] ) ? revenue()->sanitize_posted_attributes( $_POST['selectedAttr'] ) : array();

		$has_free_shipping_enabled = revenue()->get_campaign_meta( $campaign_id, 'free_shipping_enabled', true ) ?? 'no';

		$campaign = (array) revenue()->get_campaign_data( $campaign_id );

		$offers = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );

		$status = false;

		$cart_item_data = array(
			'rev_is_free_shipping' => $has_free_shipping_enabled,
			'revx_campaign_id'     => $campaign_id,
			'revx_campaign_type'   => $campaign['campaign_type'],
		);

		// Detect if it's new version (has 'products' data) or old version.
		// NEED TO CHECK WITH RELEASE DATE.
		$is_new_version = false;

		$campaign_version = revenue()->get_campaign_meta( $campaign_id, 'campaign_version', true ) ?? '1.0.0';

		if ( '2.0.0' === $campaign_version && version_compare( REVENUE_VER, '2.0.0', '>=' ) ) {
			$is_new_version = true;
		}

		// For backward compatibility, if the campaign was created before the new version release date, treat it as old version.
		$product_index = 0;
		if ( 'buy_x_get_y' === $campaign['campaign_type'] ) {

			$bxgy_data         = isset( $_POST['bxgy_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bxgy_data'] ) ) : array();
			$bxgy_trigger_data = isset( $_POST['bxgy_trigger_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bxgy_trigger_data'] ) ) : array();
			$bxgy_offer_data   = isset( $_POST['bxgy_offer_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['bxgy_offer_data'] ) ) : array();

			$trigger_product_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';

			if ( empty( $trigger_product_relation ) ) {
				$trigger_product_relation = 'or';
			}

			$is_category = ( 'category' === $campaign['campaign_trigger_type'] ) || ( 'all_products' === $campaign['campaign_trigger_type'] );

			$trigger_items       = revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $product_id, $is_category );
			$trigger_product_ids = array();
			$trigger_product_qty = array();
			foreach ( $trigger_items as $titem ) {
				$trigger_product_ids[ $titem['item_id'] ] = $bxgy_trigger_data[ $titem['item_id'] ] ?? 1;
				$trigger_product_qty[ $titem['item_id'] ] = $titem['quantity'];
			}

			$x_products = array();
			$y_products = array();

			// New version: Process products array for variable product support.
			if ( $is_new_version ) {
				$products = $_POST['products'];

				foreach ( $products as $p_data ) {
					if ( isset( $trigger_product_ids[ $p_data['product_id'] ] ) && 'yes' == $p_data['is_x_product'] ) {
						$trigger_product_ids[ $p_data['product_id'] ] = max( $trigger_product_ids[ $p_data['product_id'] ], $p_data['quantity'] );
						foreach ( $trigger_items as $titem ) {
							if ( $titem['item_id'] == $p_data['product_id'] ) {
								$bxgy_trigger_data[ $titem['item_id'] ] = max( $bxgy_trigger_data[ $titem['item_id'] ], $p_data['quantity'] );
								break;
							}
						}
						$x_products[ $p_data['product_id'] ] = $p_data;
					} else {
						$y_products[ $p_data['product_id'] ] = $p_data;
					}
				}
			}

			$parent_keys    = array();
			$cart_item_data = array_merge(
				$cart_item_data,
				array(
					'revx_bxgy_trigger_products' => $bxgy_trigger_data,
					'revx_bxgy_items'            => array(),
					'revx_offer_data'            => $offers,
					'revx_offer_products'        => $bxgy_offer_data,
					'revx_bxgy_all_triggers_key' => array(),
					'revx_required_qty'          => 1,
				)
			);

			// Add Y products data for new version.
			if ( $is_new_version ) {
				$cart_item_data['revx_y_products'] = $y_products;
			}

			$all_passed = true;
			$i          = 0;
			foreach ( $trigger_product_ids as $id => $qty ) {
				++$i;
				$cart_item_data['revx_required_qty'] = $trigger_product_qty[ $id ];

				// Initialize variation data.
				$current_variation_id = 0;
				$current_attributes   = array();

				// New version: Check for variable product support.
				if ( $is_new_version && isset( $x_products[ $id ] ) ) {
					$t_product = wc_get_product( $id );
					if ( $t_product && $t_product->is_type( 'variable' ) ) {
						$current_variation_id = $x_products[ $id ]['variation_id'];
						$current_attributes   = isset( $x_products[ $id ]['selected_attributes'] ) ? $x_products[ $id ]['selected_attributes'] : array();
					}
				}

				if ( count( $trigger_product_ids ) === $i ) {
					// Last Product.
					$cart_item_data['revx_bxgy_last_trigger']     = true;
					$cart_item_data['revx_bxgy_all_triggers_key'] = $parent_keys;

					$status = WC()->cart->add_to_cart( $id, $qty, $current_variation_id, $current_attributes, $cart_item_data );
				} else {
					$status = WC()->cart->add_to_cart( $id, $qty, $current_variation_id, $current_attributes, $cart_item_data );
				}

				if ( $status ) {
					$parent_keys[] = $status;
					do_action( 'revenue_item_added_to_cart', $status, $id, $campaign_id );
				} else {
					$all_passed = false;
				}
			}

			if ( $all_passed ) {
				do_action( 'revenue_campaign_buy_x_get_y_after_added_trigger_products', $parent_keys, $cart_item_data, $trigger_product_ids );
			} else {
				$status = false;
			}

			revenue()->increment_campaign_add_to_cart_count( $campaign_id );
		} elseif ( 'mix_match' === $campaign['campaign_type'] ) {

			$has_required_products      = isset( $campaign['mix_match_is_required_products'] ) && 'yes' == $campaign['mix_match_is_required_products'];
			$required_products          = $has_required_products ? revenue()->get_campaign_meta( $campaign['id'], 'mix_match_required_products', true ) : array();
			$mix_match_trigger_products = revenue()->get_item_ids_from_triggers( $campaign );
			$mix_match_data             = isset( $_POST['mix_match_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mix_match_data'] ) ) : array();

			$cart_item_data = array_merge(
				$cart_item_data,
				array(
					'revx_campaign_id'        => $campaign_id,
					'revx_campaign_type'      => $campaign['campaign_type'],
					'revx_required_products'  => $required_products,
					'revx_mix_match_products' => array_keys( $mix_match_data ),
					'revx_offer_data'         => $offers,
					'rev_is_free_shipping'    => $has_free_shipping_enabled,
				)
			);

			if ( $is_new_version ) {
				// New version: Use products array with variable support.
				$products = $_POST['products'];
				foreach ( $products as $p_data ) {
					$pid          = $p_data['product_id'];
					$qty          = $p_data['quantity'];
					$variation_id = isset( $p_data['variation_id'] ) ? $p_data['variation_id'] : 0;
					$attributes   = isset( $p_data['selected_attributes'] ) ? $p_data['selected_attributes'] : array();
					$status       = WC()->cart->add_to_cart(
						$pid,
						$qty,
						$variation_id,
						$attributes,
						$cart_item_data
					);
					revenue()->increment_campaign_add_to_cart_count( $campaign_id, $pid );

					if ( $status ) {
						do_action( 'revenue_item_added_to_cart', $status, $pid, $campaign_id );
					}
				}
			} else {
				// Old version: Use mix_match_data.
				foreach ( $mix_match_data as $pid => $qty ) {
					$status = WC()->cart->add_to_cart(
						$pid,
						$qty,
						0,
						array(),
						$cart_item_data
					);
					revenue()->increment_campaign_add_to_cart_count( $campaign_id, $pid );

					if ( $status ) {
						do_action( 'revenue_item_added_to_cart', $status, $pid, $campaign_id );
					}
				}
			}
		} elseif ( 'frequently_bought_together' === $campaign['campaign_type'] ) {
			$required_products = isset( $_POST['requiredProducts'] ) ? $_POST['requiredProducts'] : array();

			if ( ! is_array( $required_products ) ) {
				$required_products = array( $required_products );
			}
			$required_products = array_map( 'absint', $required_products );
			$required_products = array_filter( $required_products ); // remove invalid/empty

			$fbt_data = isset( $_POST['fbt_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['fbt_data'] ) ) : array();

			$is_required_trigger_product = revenue()->get_campaign_meta( $campaign_id, 'fbt_is_trigger_product_required', true );

			if ( 'yes' === $is_required_trigger_product ) {
				// check if required trigger product is set in fbt data
				foreach ( $required_products as $required_product ) {
					if ( ! isset( $fbt_data[ $required_product ] ) ) {
						return wp_send_json_error(
							array(
								'message' => 'Required trigger product not found.',
							),
							400
						);
					}
				}
			}

			$cart_item_data = array_merge(
				$cart_item_data,
				array(
					'revx_campaign_id'           => $campaign_id,
					'revx_campaign_type'         => $campaign['campaign_type'],
					'revx_fbt_required_products' => $required_products,
					'revx_fbt_data'              => $fbt_data,
					'revx_offer_data'            => $offers,
					'rev_is_free_shipping'       => $has_free_shipping_enabled,
				)
			);

			if ( $is_new_version ) {
				// New version: Use products array with variable support.
				$products                             = $_POST['products'];
				$cart_item_data['revx_products_data'] = $products;
				$revx_fbt_all_triggers_key            = array();
				$revx_fbt_all_items_key               = array();
				foreach ( $products as $_pd ) {
					$variation_id = isset( $_pd['variation_id'] ) ? $_pd['variation_id'] : 0;
					$attributes   = isset( $_pd['selected_attributes'] ) ? $_pd['selected_attributes'] : array();
					$status       = WC()->cart->add_to_cart(
						$_pd['product_id'],
						$_pd['quantity'],
						$variation_id,
						$attributes,
						$cart_item_data
					);
					if ( $status ) {
						if ( in_array( $_pd['product_id'], $required_products ) ) {
							$revx_fbt_all_triggers_key[] = $status;
						} else {
							$revx_fbt_all_items_key[] = $status;
						}
						do_action( 'revenue_item_added_to_cart', $status, $_pd['product_id'], $campaign_id );
					}
				}
				// only set trigger keys and items keys to trigger items on cart,
				// its easier to handle the removal of items when trigger items are removed.
				foreach ( $revx_fbt_all_triggers_key as $key ) {
					WC()->cart->cart_contents[ $key ]['revx_fbt_all_triggers_key'] = $revx_fbt_all_triggers_key;
					WC()->cart->cart_contents[ $key ]['revx_fbt_all_items_key']    = $revx_fbt_all_items_key;
				}
			} else {
				// Old version: Use fbt_data.
				foreach ( $fbt_data as $pid => $qty ) {
					$status = WC()->cart->add_to_cart(
						$pid,
						$qty,
						0,
						array(),
						$cart_item_data
					);
					if ( $status ) {
						do_action( 'revenue_item_added_to_cart', $status, $pid, $campaign_id );
					}
				}
			}

			revenue()->increment_campaign_add_to_cart_count( $campaign_id );

		} elseif ( 'spending_goal' === $campaign['campaign_type'] ) {
			$cart_item_data['revx_spending_goal_upsell'] = 'yes';
			$status                                      = WC()->cart->add_to_cart(
				$product_id,
				$quantity,
				$variation_id,
				$attributes,
				$cart_item_data
			);

			revenue()->increment_campaign_add_to_cart_count( $campaign_id );

			if ( $status ) {
				do_action( 'revenue_item_added_to_cart', $status, $product_id, $campaign_id );
			}
		} elseif ( 'free_shipping_bar' === $campaign['campaign_type'] ) {
			$cart_item_data['revx_free_shipping_bar_upsell'] = 'yes';
			$status = WC()->cart->add_to_cart(
				$product_id,
				$quantity,
				$variation_id,
				$attributes,
				$cart_item_data
			);

			revenue()->increment_campaign_add_to_cart_count( $campaign_id );

			if ( $status ) {
				do_action( 'revenue_item_added_to_cart', $status, $product_id, $campaign_id );
			}
		} elseif ( 'normal_discount' === $campaign['campaign_type'] ) {
			$offer_qty  = '';
			$flag_check = true;

			if ( is_array( $offers ) ) {
				foreach ( $offers as $offer_idx => $offer ) {

					$offered_product_ids = $offer['products'];
					$offer_qty           = $offer['quantity'];

					foreach ( $offered_product_ids as $offer_product_id ) {
						$offered_product = wc_get_product( $offer_product_id );
						if ( ! $offered_product ) {
							continue;
						}
						$parent_id = $offered_product->get_parent_id(); // If has parent id, that means it's a variation product.

						if ( $parent_id && $offer_product_id == $variation_id ) {
							if ( 'yes' === revenue()->get_campaign_meta( $campaign['id'], 'quantity_selector_enabled', true ) ) {
								$offer_qty = max( $quantity, $offer_qty );
							}

							$status = WC()->cart->add_to_cart(
								$product_id,
								$offer_qty,
								$variation_id,
								$attributes,
								$cart_item_data
							);
							revenue()->increment_campaign_add_to_cart_count( $campaign_id );

						} elseif ( $product_id === $offer_product_id && $flag_check ) {
							if ( 'yes' === revenue()->get_campaign_meta( $campaign['id'], 'quantity_selector_enabled', true ) ) {
								$offer_qty = max( $quantity, $offer_qty );
							}

							$status = WC()->cart->add_to_cart(
								$product_id,
								$offer_qty,
								0,
								array(),
								$cart_item_data
							);
							revenue()->increment_campaign_add_to_cart_count( $campaign_id );
						}

						++$product_index;
					}
				}
			}
			if ( $status ) {
				do_action( 'revenue_item_added_to_cart', $status, $product_id, $campaign_id );
			}
		} else {
			// Handle volume_discount and other campaign types.
			$offer_qty  = '';
			$flag_check = true;
			if ( is_array( $offers ) ) {
				foreach ( $offers as $offer_idx => $offer ) {

					$offered_product_ids = $offer['products'];
					$offer_qty           = $offer['quantity'];

					if ( 'volume_discount' === $campaign['campaign_type'] ) {
						$offered_product_ids   = array();
						$offered_product_ids[] = $product_id;
					}

					foreach ( $offered_product_ids as $offer_product_id ) {
						$offered_product = wc_get_product( $offer_product_id );
						if ( ! $offered_product ) {
							continue;
						}

						if ( 'volume_discount' === $campaign['campaign_type'] ) {
							$flag_check = (string) $offer_idx === (string) $index;
						}
						if ( $product_id === $offer_product_id && $flag_check ) {
							if ( 'yes' === revenue()->get_campaign_meta( $campaign['id'], 'quantity_selector_enabled', true ) ) {
								$offer_qty = max( $quantity, $offer_qty );
							}

							if ( 'volume_discount' === $campaign['campaign_type'] ) {
								$offer_qty = max( $quantity, $offer_qty );
							}

							// Old version compatibility: check product index for non-volume discount.
							if ( ! ( 'volume_discount' === $campaign['campaign_type'] ) && ( $is_new_version || $product_index == $index ) ) {
								$status = WC()->cart->add_to_cart(
									$product_id,
									$offer_qty,
									0,
									array(),
									$cart_item_data
								);
								revenue()->increment_campaign_add_to_cart_count( $campaign_id );
							}
						}
						++$product_index;
					}
				}
			}

			// Handle volume discount specifically.
			if ( 'volume_discount' === $campaign['campaign_type'] ) {
				// Add offer index to cart item data if provided.
				if ( isset( $_POST['offerIndex'] ) ) {
					$cart_item_data['revx_offer_index'] = absint( $_POST['offerIndex'] );
				}

				// Check if multiple variation.
				if ( isset( $_POST['products'] ) && is_array( $_POST['products'] ) && ! empty( $_POST['products'] ) ) {
					$cart_item_data['revx_multiple_variation'] = 'yes';

					$products = $_POST['products'];
					foreach ( $products as $p_data ) {
						$pid    = isset( $p_data['product_id'] ) ? sanitize_text_field( $p_data['product_id'] ) : 0;
						$qty    = isset( $p_data['quantity'] ) ? absint( $p_data['quantity'] ) : $quantity;
						$var_id = isset( $p_data['variation_id'] ) ? absint( $p_data['variation_id'] ) : 0;
						$attrs  = isset( $p_data['selected_attributes'] ) ? revenue()->sanitize_posted_attributes( $p_data['selected_attributes'] ) : array();

						$status = WC()->cart->add_to_cart(
							$pid,
							$qty,
							$var_id,
							$attrs,
							$cart_item_data
						);
					}
				} else {
					// Use single product data.
					$status = WC()->cart->add_to_cart(
						$product_id,
						$quantity,
						$variation_id,
						$attributes,
						$cart_item_data
					);
				}
				revenue()->increment_campaign_add_to_cart_count( $campaign_id );
			}

			if ( $status ) {
				do_action( 'revenue_item_added_to_cart', $status, $product_id, $campaign_id );
			}
		}

		$on_cart_action = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_on_cart_action', true );

		$campaign_source_page = isset( $_POST['campaignSourcePage'] ) ? sanitize_text_field( wp_unslash( $_POST['campaignSourcePage'] ) ) : '';

		$response_data = array(
			'add_to_cart'    => $status,
			'on_cart_action' => $on_cart_action,
		);
		switch ( $campaign_source_page ) {
			case 'cart_page':
				$response_data['is_reload'] = true;
				break;
			case 'checkout_page':
				$response_data['is_reload'] = true;
				break;

			default:
				// code...
				break;
		}

		WC()->cart->calculate_totals();

		$cart_total    = 0;
		$cart_subtotal = 0;

		if ( wc_prices_include_tax() ) {
			$cart_total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();
		} else {
			$cart_total = WC()->cart->get_cart_contents_total();
		}

		$shipping_total = 0;

		if ( WC()->cart->display_prices_including_tax() ) {
			$shipping_total = WC()->cart->shipping_total + WC()->cart->shipping_tax_total;
		} else {
			$shipping_total = WC()->cart->shipping_total;
		}

		$cart_total += $shipping_total;

		if ( WC()->cart->display_prices_including_tax() ) {
			$cart_subtotal = WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();

		} else {
			$cart_subtotal = WC()->cart->get_subtotal();
		}

		ob_start();

		woocommerce_mini_cart();

		$mini_cart = ob_get_clean();

		$data = array(
			'fragments' => apply_filters(
				'woocommerce_add_to_cart_fragments',
				array(
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
				)
			),
			'cart_hash' => WC()->cart->get_cart_hash(),
		);
		wp_send_json_success( array_merge( $response_data, $data ) );
	}

	/**
	 * Reveneux Add Bundle to cart
	 *
	 * @return mixed
	 */
	public function add_bundle_to_cart() {

		check_ajax_referer( 'revenue-add-to-cart', false );

		$campaign_id = isset( $_POST['campaignId'] ) ? sanitize_text_field( wp_unslash( $_POST['campaignId'] ) ) : '';
		$quantity    = isset( $_POST['quantity'] ) ? sanitize_text_field( wp_unslash( $_POST['quantity'] ) ) : '';

		$bundle_product_id = get_option( 'revenue_bundle_parent_product_id', false );

		if ( ! $bundle_product_id ) {
			wp_send_json_error();
		}

		$campaign                  = (array) revenue()->get_campaign_data( $campaign_id );
		$has_free_shipping_enabled = revenue()->get_campaign_meta( $campaign_id, 'free_shipping_enabled', true ) ?? 'no';

		$offers         = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
		$is_qty_enabled = revenue()->get_campaign_meta( $campaign['id'], 'quantity_selector_enabled', true );

		if ( 'yes' !== $is_qty_enabled ) {
			$quantity = 1;
		}

		// Detect version: new version has 'products' data, old version doesn't
		$is_new_version = isset( $_POST['products'] ) && ! empty( $_POST['products'] );
		$products       = $is_new_version ? $_POST['products'] : array();

		$tip                  = false;
		$trigger_product_data = array();

		// Process products data for new version
		if ( $is_new_version ) {
			foreach ( $products as $_pd ) {
				if ( isset( $_pd['is_trigger'] ) && 'yes' === $_pd['is_trigger'] ) {
					$tip                                        = $_pd['product_id'];
					$trigger_product_data[ $_pd['product_id'] ] = $_pd;
				}
			}
		}

		$bundle_id = $campaign['id'] . '_' . wp_rand( 1, 9999999 );

		// Base bundle data (common for both versions)
		$bundle_data = array(
			'revx_campaign_id'     => $campaign_id,
			'revx_bundle_id'       => $bundle_id,
			'revx_bundle_data'     => $offers,
			'revx_bundle_type'     => 'trigger',
			'revx_bundled_items'   => array(),
			'revx_campaign_type'   => $campaign['campaign_type'],
			'rev_is_free_shipping' => $has_free_shipping_enabled,
		);

		// Add new version specific data if available
		if ( $is_new_version ) {
			$bundle_data['revx_bundle_products']             = $products; // For new version 2.0
			$bundle_data['revx_bundle_trigger_product_data'] = $trigger_product_data;
		}

		// Handle trigger products for both versions
		if ( 'yes' === $campaign['bundle_with_trigger_products_enabled'] ) {
			$trigger_product_id = '';

			if ( $is_new_version ) {
				// New version: use trigger product from products array
				$trigger_product_id = $tip;
			} else {
				// Old version: use trigger_product_id from POST
				$trigger_product_id = isset( $_POST['trigger_product_id'] ) ? sanitize_text_field( wp_unslash( $_POST['trigger_product_id'] ) ) : '';
			}

			if ( $trigger_product_id ) {
				$trigger_product = wc_get_product( $trigger_product_id );

				// For old version, check if product is simple type; for new version, just check if product exists
				$is_valid_trigger = $is_new_version ?
					( $trigger_product && $trigger_product->exists() ) :
					( $trigger_product && $trigger_product->is_type( 'simple' ) );

				if ( $is_valid_trigger ) {
					$bundle_data['revx_bundle_with_trigger'] = 'yes';
					$bundle_data['revx_trigger_product_id']  = $trigger_product_id;
					$bundle_data['revx_min_qty']             = 1;
				}
			}
		}

		$status = WC()->cart->add_to_cart( $bundle_product_id, $quantity, 0, array(), $bundle_data );

		if ( $status ) {
			revenue()->increment_campaign_add_to_cart_count( $campaign_id );
		}

		$on_cart_action = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_on_cart_action', true );

		// Handle different source page parameter names for backward compatibility
		$campaign_source_page = isset( $_POST['campaignSrcPage'] ) ?
			sanitize_text_field( wp_unslash( $_POST['campaignSrcPage'] ) ) :
			( isset( $_POST['campaignSourcePage'] ) ? sanitize_text_field( wp_unslash( $_POST['campaignSourcePage'] ) ) : '' );

		$response_data = array(
			'add_to_cart'    => $status,
			'on_cart_action' => $on_cart_action,
		);
		switch ( $campaign_source_page ) {
			case 'cart_page':
				$response_data['is_reload'] = true;
				break;
			case 'checkout_page':
				$response_data['is_reload'] = true;
				break;

			default:
				// code...
				break;
		}
		WC()->cart->calculate_totals();

		ob_start();

		woocommerce_mini_cart();

		$mini_cart = ob_get_clean();

		$data = array(
			'fragments' => apply_filters(
				'woocommerce_add_to_cart_fragments',
				array(
					'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
				)
			),
			'cart_hash' => WC()->cart->get_cart_hash(),
		);
		wp_send_json_success( array_merge( $response_data, $data ) );
	}


	/**
	 * Close popup
	 *
	 * @return mixed
	 */
	public function close_popup() {
		check_ajax_referer( 'revenue-add-to-cart', false ); // Add this nonce on js and also localize this.

		$campaign_id = isset( $_POST['campaignId'] ) ? sanitize_text_field( wp_unslash( $_POST['campaignId'] ) ) : '';

		$cart_data = WC()->session->get( 'revenue_cart_data' );

		if ( ! ( is_array( $cart_data ) && isset( $cart_data[ $campaign_id ] ) ) ) {
			revenue()->increment_campaign_rejection_count( $campaign_id );
		}

		wp_send_json_success( array( 'rejection_updated' => true ) );
	}

	/**
	 * Count impression.
	 *
	 * @return mixed
	 */
	public function count_impression() {
		check_ajax_referer( 'revenue-add-to-cart', false ); // Add this nonce on js and also localize this.

		$campaign_id = isset( $_POST['campaignId'] ) ? sanitize_text_field( wp_unslash( $_POST['campaignId'] ) ) : '';

		revenue()->update_campaign_impression( $campaign_id );

		wp_send_json_success( array( 'impression_count_updated' => true ) );
	}


	/**
	 * Get campaign limits.
	 *
	 * @return array.
	 */
	public function get_campaign_limits() {
		$nonce = '';
		if ( isset( $_POST['security'] ) ) {
			$nonce = sanitize_key( $_POST['security'] );
		}
		$result = wp_verify_nonce( $nonce, 'revenue-dashboard' );
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$res = $wpdb->get_row(
			"SELECT
                COUNT(*) AS total_campaigns,
                SUM(CASE WHEN campaign_type = 'normal_discount' THEN 1 ELSE 0 END) AS normal_discount,
                SUM(CASE WHEN campaign_type = 'volume_discount' THEN 1 ELSE 0 END) AS volume_discount,
                SUM(CASE WHEN campaign_type = 'bundle_discount' THEN 1 ELSE 0 END) AS bundle_discount,
                SUM(CASE WHEN campaign_type = 'buy_x_get_y' THEN 1 ELSE 0 END) AS buy_x_get_y
            FROM {$wpdb->prefix}revenue_campaigns;"
		); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return wp_send_json_success( $res );
	}



	/**
	 * Activate WC
	 *
	 * @return mixed
	 */
	public function activate_woocommerce() {

		$nonce = '';
		if ( isset( $_POST['security'] ) ) {
			$nonce = sanitize_key( $_POST['security'] );
		}
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions to activate plugins.', 'revenue' ) );
		}
		$result = activate_plugin( 'woocommerce/woocommerce.php' );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		wp_send_json_success();
	}


	/**
	 * Install WC
	 *
	 * @return mixed
	 */
	public function install_woocommerce() {

		$nonce = '';
		if ( isset( $_POST['security'] ) ) {
			$nonce = sanitize_key( $_POST['security'] );
		}
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}
		if ( ! current_user_can( 'install_plugins' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions to install plugins.', 'revenue' ) );
		}

		if ( ! class_exists( 'WP_Upgrader' ) ) {
			include ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! function_exists( 'plugins_api' ) ) {
			include ABSPATH . 'wp-admin/includes/plugin-install.php';
		}
		$plugin_slug = 'woocommerce';
		$api         = plugins_api(
			'plugin_information',
			array(
				'slug'   => $plugin_slug,
				'fields' => array(
					'sections' => false,
				),
			)
		);

		if ( is_wp_error( $api ) ) {
			wp_send_json_error( $api->get_error_message() );
		}
		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );
		$result   = $upgrader->install( $api->download_link );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}
		if ( ! $result ) {
			wp_send_json_error( __( 'Plugin installation failed.', 'revenue' ) );
		}
		wp_send_json_success();
	}




	public function get_eventin_ticket_data_by_id( $variation_id ) {
		$id = explode( '_', $variation_id )[0];

		$data       = array();
		$event_logo = get_post_meta( $id, 'etn_event_logo', true );
		$variations = get_post_meta( $id, 'etn_ticket_variations', true );
		$child_data = array();

		if ( is_array( $variations ) && ! empty( $variations ) ) {

			foreach ( $variations as $variation ) {
				if ( $id . '_' . $variation['etn_ticket_slug'] == $variation_id ) {
					$data = array(
						'item_id'       => $id . '_' . $variation['etn_ticket_slug'],
						'item_name'     => $variation['etn_ticket_name'],
						'regular_price' => $variation['etn_ticket_price'],
						'thumbnail'     => wc_placeholder_img_src(),
						'_type'         => 'eventin_events',
					);
				}
			}
		}

		return $data;
	}


	public function get_offer_items() {
		$nonce = '';
		if ( isset( $_GET['security'] ) ) {
			$nonce = sanitize_key( $_GET['security'] );
		}
		if ( ! wp_verify_nonce( $nonce, 'revenue-dashboard' ) ) {
			die();
		}

		$type = isset( $_GET['type'] ) ? sanitize_text_field( wp_unslash( $_GET['type'] ) ) : '';

		$data = array();

		if ( 'products' == $type ) {

			$args = array(
				'limit'   => 5, // Limit to 5 products.
				'orderby' => 'date', // Order by date.
				'order'   => 'ASC', // Ascending order.
			);

			$products = wc_get_products( $args );

			$source = isset( $_GET['source'] ) ? sanitize_text_field( wp_unslash( $_GET['source'] ) ) : '';

			$campaign_type = isset( $_GET['campaign_type'] ) ? sanitize_text_field( wp_unslash( $_GET['campaign_type'] ) ) : '';
			foreach ( $products as $product ) {
				if ( $product ) {

					$children     = $product->get_children();
					$child_data   = array();
					$product_link = get_permalink( $product );
					if ( is_array( $children ) ) {
						foreach ( $children as $child_id ) {
							$child        = wc_get_product( $child_id );
							$child_data[] = array(
								'item_id'        => $child_id,
								'item_name'      => rawurldecode( wp_strip_all_tags( $child->get_name() ) ),
								'thumbnail'      => wp_get_attachment_url( $child->get_image_id() ),
								'regular_price'  => $child->get_regular_price(),
								'parent_id'      => $product->get_id(),
								'url'            => $product_link,
								'show_attribute' => 'variable' === $product->get_type(),
							);
						}
					}

					if ( 'trigger' === $source && 'mix_match' !== $campaign_type && 'buy_x_get_y' !== $campaign_type && 'frequently_bought_together' !== $campaign_type ) {
						$data[] = array(
							'item_id'        => $product->get_id(),
							'url'            => get_permalink( $product ),
							'item_name'      => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
							'thumbnail'      => wp_get_attachment_url( $product->get_image_id() ),
							'regular_price'  => $product->get_regular_price(),
							'children'       => array(),
							'show_attribute' => 'variable' === $product->get_type(),
						);
					} elseif ( ! empty( $child_data ) ) {
							$data = array_merge( $data, $child_data );
					} else {

						$data[] = array(
							'item_id'        => $product->get_id(),
							'url'            => get_permalink( $product ),
							'item_name'      => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
							'thumbnail'      => wp_get_attachment_url( $product->get_image_id() ),
							'regular_price'  => $product->get_regular_price(),
							'children'       => array(),
							'show_attribute' => 'variable' === $product->get_type(),
						);
					}
				}
			}
		} elseif ( 'category' === $type ) {
			$category_args = array(
				'taxonomy' => 'product_cat', // Taxonomy for WooCommerce product categories.
				'number'   => 5, // Limit to 5 categories.
				'orderby'  => 'name', // Order by name.
				'order'    => 'ASC', // Ascending order.
			);

			$categories = get_terms( $category_args );

			foreach ( $categories as $category ) {
				if ( ! is_wp_error( $category ) ) {
					$data[] = array(
						'item_id'   => $category->term_id,
						'item_name' => $category->name,
						'url'       => get_term_link( $category ), // Get the category link.
						'thumbnail' => get_term_meta( $category->term_id, 'thumbnail_id', true ) ? wp_get_attachment_url( get_term_meta( $category->term_id, 'thumbnail_id', true ) ) : wc_placeholder_img_src(), // Get category thumbnail.
					);
				}
			}
		}

		wp_send_json_success( $data );
	}
}
