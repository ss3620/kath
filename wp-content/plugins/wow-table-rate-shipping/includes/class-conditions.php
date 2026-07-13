<?php // phpcs:ignore

namespace WTRS\Includes;

use WP_Error;
use WTRS\Includes\Utils\Flags;

defined( 'ABSPATH' ) || exit;

/**
 * Conditions class.
 */
class Conditions {

	/**
	 * Get condition data by type.
	 *
	 * @param string $type Data type to fetch.
	 * @param string $search Search term.
	 * @param int    $per_page Number of items per page.
	 * @return array|WP_Error Array of data or error.
	 */
	public static function get_condition_data_by_type( $type, $search = '', $per_page = 50 ) {
		switch ( $type ) {
			case 'products':
				return self::get_products_data( $search, $per_page );

			case 'categories':
				return self::get_categories_data( $search, $per_page );

			case 'tags':
				return self::get_tags_data( $search, $per_page );

			case 'cart_coupons':
			case 'coupons':
				return self::get_coupons_data( $search, $per_page );

			case 'users':
				return self::get_users_data( $search, $per_page );

			case 'user_roles':
				return self::get_user_roles_data( $search );

			case 'colors':
				return self::get_attribute_data( 'pa_color', $search, $per_page );

			case 'country':
			case 'state':
			case 'city':
				return self::get_locations( $type, $search, $per_page );

			case 'shipping_class':
			case 'shipping-class':
				return self::get_shipping_classes_data( $search, $per_page );

			case 'weekdays':
				return array(
					array(
						'value' => '0',
						'label' => __( 'Sunday', 'wow-table-rate-shipping' ),
					),
					array(
						'value' => '1',
						'label' => __( 'Monday', 'wow-table-rate-shipping' ),
					),
					array(
						'value' => '2',
						'label' => __( 'Tuesday', 'wow-table-rate-shipping' ),
					),
					array(
						'value' => '3',
						'label' => __( 'Wednesday', 'wow-table-rate-shipping' ),
					),
					array(
						'value' => '4',
						'label' => __( 'Thursday', 'wow-table-rate-shipping' ),
					),
					array(
						'value' => '5',
						'label' => __( 'Friday', 'wow-table-rate-shipping' ),
					),
					array(
						'value' => '6',
						'label' => __( 'Saturday', 'wow-table-rate-shipping' ),
					),
				);

			case 'attribute':
				return self::get_attribute_ids( $search, $per_page );

			default:
				// Check if it's a dynamic attribute (starts with 'pa_').
				if ( strpos( $type, 'pa_' ) === 0 ) {
					return self::get_attribute_data( $type, $search, $per_page );
				}
				return new \WP_Error( 'invalid_type', 'Invalid data type', array( 'status' => 400 ) );
		}
	}

	/**
	 * Get products data.
	 *
	 * @param string $search Search term.
	 * @param int    $per_page Number of items per page.
	 * @return array Array of products.
	 */
	private static function get_products_data( $search = '', $per_page = 50 ) {
		$args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'relation' => 'AND',
				array(
					'relation' => 'OR',
					array(
						'key'     => '_virtual',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_virtual',
						'value'   => 'yes',
						'compare' => '!=',
					),
				),
				array(
					'relation' => 'OR',
					array(
						'key'     => '_downloadable',
						'compare' => 'NOT EXISTS',
					),
					array(
						'key'     => '_downloadable',
						'value'   => 'yes',
						'compare' => '!=',
					),
				),
			),
		);

		if ( ! empty( $search ) ) {
			$args['s'] = sanitize_text_field( $search );
		}

		$products = get_posts( $args );

		$data = array();
		foreach ( $products as $product ) {
			$data[] = array(
				'value' => $product->ID,
				'label' => $product->post_title,
				'image' => wp_get_attachment_image_url( get_post_thumbnail_id( $product->ID ), 'thumbnail' ),
			);
		}

		return $data;
	}

	/**
	 * Get categories data.
	 *
	 * @param string $search Search term.
	 * @param int    $per_page Number of items per page.
	 * @return array Array of categories.
	 */
	private static function get_categories_data( $search = '', $per_page = 50 ) {
		$args = array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'number'     => $per_page,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'fields'     => 'all',
		);

		if ( ! empty( $search ) ) {
			$args['search'] = sanitize_text_field( $search );
		}

		$categories = get_terms( $args );
		$data       = array();

		if ( ! is_wp_error( $categories ) ) {
			foreach ( $categories as $category ) {
				$thumbnail_id = get_term_meta( $category->term_id, 'thumbnail_id', true );
				$data[]       = array(
					'value' => $category->term_id,
					'label' => $category->name,
					'image' => $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ) : '',
				);
			}
		}

		return $data;
	}


	/**
	 * Get tags data.
	 *
	 * @param string $search Search term.
	 * @param int    $per_page Number of items per page.
	 * @return array Array of tags.
	 */
	private static function get_tags_data( $search = '', $per_page = 50 ) {
		$args = array(
			'taxonomy'   => 'product_tag',
			'hide_empty' => false,
			'number'     => $per_page,
			'orderby'    => 'name',
			'order'      => 'ASC',
			'fields'     => 'all',
		);

		if ( ! empty( $search ) ) {
			$args['search'] = sanitize_text_field( $search );
		}

		$tags = get_terms( $args );
		$data = array();

		if ( ! is_wp_error( $tags ) ) {
			foreach ( $tags as $tag ) {
				$data[] = array(
					'value' => $tag->term_id,
					'label' => $tag->name,
				);
			}
		}

		return $data;
	}

	/**
	 * Get coupons data.
	 *
	 * @param string $search Search term (matches coupon code/title).
	 * @param int    $per_page Number of items per page.
	 * @return array Array of coupons in shape: [ [ 'value' => code, 'label' => code, 'id' => post_id ], ... ]
	 */
	private static function get_coupons_data( $search = '', $per_page = 50 ) {
		if ( ! post_type_exists( 'shop_coupon' ) ) {
			return array();
		}

		$args = array(
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $search ) ) {
			$args['s'] = sanitize_text_field( $search );
		}

		$coupons = get_posts( $args );
		$data    = array();

		// Virtual option that represents all free-shipping coupons.
		$data[] = array(
			'value' => 'wtrs:coupon:free_shipping',
			'label' => __( 'Any Free Shipping Coupons', 'wow-table-rate-shipping' ),
		);

		foreach ( $coupons as $coupon_post ) {
			$code   = $coupon_post->post_title;
			$data[] = array(
				'value' => $code,
				'label' => $code,
			);
		}

		return $data;
	}


	/**
	 * Get users data.
	 *
	 * @param string $search Search term.
	 * @param int    $per_page Number of items per page.
	 * @return array Array of users.
	 */
	private static function get_users_data( $search = '', $per_page = 50 ) {
		$args = array(
			'number' => $per_page,
		);

		if ( ! empty( $search ) ) {
			$args['search'] = '*' . sanitize_text_field( $search ) . '*';
		}

		$users = get_users( $args );
		$data  = array();

		foreach ( $users as $user ) {
			$data[] = array(
				'value'    => $user->ID,
				'label'    => $user->display_name,
				'username' => $user->user_login,
				'avatar'   => get_avatar_url( $user->ID, array( 'size' => 32 ) ),
			);
		}

		return $data;
	}

	/**
	 * Get user roles data.
	 *
	 * @param string $search Search term.
	 * @return array Array of user roles.
	 */
	private static function get_user_roles_data( $search = '' ) {
		global $wp_roles;

		$roles = $wp_roles->get_names();
		$data  = array();

		foreach ( $roles as $role_key => $role_name ) {
			if ( empty( $search ) || stripos( $role_name, $search ) !== false ) {
				$data[] = array(
					'value' => $role_key,
					'label' => $role_name,
				);
			}
		}

		return $data;
	}

	/**
	 * Get attribute data (colors, sizes, etc.).
	 *
	 * @param string $attribute_name Attribute taxonomy name.
	 * @param string $search Search term.
	 * @param int    $per_page Number of items per page.
	 * @return array Array of attribute terms.
	 */
	private static function get_attribute_data( $attribute_name, $search = '', $per_page = 50 ) {
		$args = array(
			'taxonomy'   => $attribute_name,
			'hide_empty' => false,
			'number'     => $per_page,
		);

		if ( ! empty( $search ) ) {
			$args['search'] = sanitize_text_field( $search );
		}

		$terms = get_terms( $args );
		$data  = array();

		if ( ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$data[] = array(
					'value' => $term->term_id,
					'label' => $term->name,
					'pro'   => Flags::PRODUCT_ATTRIBUTES_PRO,
				);
			}
		}

		// For brands, we add support for multiple popular brand taxonomies used by different plugins, so we need to check the attribute name and fetch brand data accordingly.
		if ( 'pa_brand' === $attribute_name ) {
			$brand_data = self::get_brand_data( $search, $per_page );
			$data       = array_merge( $brand_data, $data );
		}

		return $data;
	}

	/**
	 * Get supported brand taxonomies.
	 *
	 * @return array
	 */
	private static function get_brand_taxonomies() {
		return array(
			'brand',
			'product_brand',
			'product_brands',
			'pwb-brand',
			'yith_product_brand',
			'berocket_brand',
		);
	}

	/**
	 * Get brand terms from available brand taxonomy.
	 *
	 * @param string $search Search term.
	 * @param int    $per_page Number of items per page.
	 * @return array
	 */
	private static function get_brand_data( $search = '', $per_page = 50 ) {
		$data = array();

		foreach ( self::get_brand_taxonomies() as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				continue;
			}

			$args = array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'number'     => $per_page,
			);

			if ( ! empty( $search ) ) {
				$args['search'] = sanitize_text_field( $search );
			}

			$terms = get_terms( $args );

			if ( is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$data[] = array(
					'value' => $term->term_id,
					'label' => $term->name,
					'pro'   => Flags::PRODUCT_ATTRIBUTES_PRO,
				);
			}
		}

		return $data;
	}

	/**
	 * Get location data
	 *
	 * @param string  $type location type.
	 * @param string  $search search term.
	 * @param integer $per_page number of items per page.
	 * @return array
	 */
	private static function get_locations( $type, $search = '', $per_page = 50 ) {
		if ( ! class_exists( 'WC_Countries' ) ) {
			if ( function_exists( 'WC' ) ) {
				include_once WC()->plugin_path() . '/includes/class-wc-countries.php';
			} else {
				return new \WP_Error( 'woocommerce_not_loaded', 'WooCommerce is not active or loaded', array( 'status' => 500 ) );
			}
		}

		$countries_obj = new \WC_Countries();

		$items = array();
		$count = 0;

		if ( 'country' === $type ) {
			$countries = $countries_obj->get_countries();
			foreach ( $countries as $country_code => $country_name ) {
				if ( ! empty( $search ) ) {
					if ( false === strpos( strtolower( $country_name ), strtolower( $search ) ) ) {
						continue;
					}
				}
				$items[] = array(
					'value' => $country_code,
					'label' => $country_name,
				);
				++$count;

				if ( $count >= $per_page ) {
					break;
				}
			}
		} elseif ( 'state' === $type ) {
			$states = $countries_obj->get_states();
			foreach ( $states as $country_code => $_states ) {
				foreach ( $_states as $state_code => $state_name ) {
					if ( ! empty( $search ) ) {
						if ( false === strpos( strtolower( $state_name ), strtolower( $search ) ) ) {
							continue;
						}
					}

					$items[] = array(
						'value' => $country_code . '#' . $state_code,
						'label' => $state_name,
					);
					++$count;

					if ( $count >= $per_page ) {
						break;
					}
				}

				if ( $count >= $per_page ) {
					break;
				}
			}
		}

		return $items;
	}

	/**
	 * Get shipping classes data.
	 *
	 * @param string $search search term.
	 * @param int    $per_page Number of items per page.
	 * @return array Array of shipping classes.
	 */
	public static function get_shipping_classes_data( $search = '', $per_page = 50 ) {
		if ( ! class_exists( 'WC_Shipping' ) ) {
			return array();
		}

		$shipping_classes = get_terms(
			array(
				'taxonomy'   => 'product_shipping_class',
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			)
		);

		$data  = array();
		$count = 0;

		if ( ! is_wp_error( $shipping_classes ) ) {
			foreach ( $shipping_classes as $shipping_class ) {

				if ( ! empty( $search ) && stripos( $shipping_class->name, $search ) === false ) {
					continue;
				}

				$data[] = array(
					'value' => $shipping_class->slug,
					'label' => $shipping_class->name,
				);

				++$count;

				if ( $count >= $per_page ) {
					break;
				}
			}
		}

		return $data;
	}

	/**
	 * Get all product attributes.
	 *
	 * @return array Array of product attributes.
	 */
	public static function get_attribute_ids() {
		if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
			return array();
		}

		$attributes = wc_get_attribute_taxonomies();
		$data       = array();

		foreach ( $attributes as $attribute ) {
			$data[] = array(
				'value' => 'pa_' . $attribute->attribute_name,
				'label' => $attribute->attribute_label,
				'pro'   => Flags::PRODUCT_ATTRIBUTES_PRO,
			);
		}

		return $data;
	}
}
