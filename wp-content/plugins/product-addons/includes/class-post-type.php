<?php // phpcs:ignore
/**
 * PostType Action.
 *
 * @package PRAD\Options
 * @since v.1.0.0
 */
namespace PRAD\Includes;

defined( 'ABSPATH' ) || exit;

/**
 * PostType class.
 */
class PostType {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'post_type_callback' ) );
	}


	/**
	 * Option PostType
	 *
	 * @since v.1.0.0
	 */
	public function post_type_callback() {
		$labels = array(
			'name'               => _x( 'Option', 'Option', 'product-addons' ),
			'singular_name'      => _x( 'Option', 'Option', 'product-addons' ),
			'menu_name'          => __( 'Option', 'product-addons' ),
			'parent_item_colon'  => __( 'Parent Option', 'product-addons' ),
			'all_items'          => __( 'Option', 'product-addons' ),
			'view_item'          => __( 'View Option', 'product-addons' ),
			'add_new_item'       => __( 'Add New', 'product-addons' ),
			'add_new'            => __( 'Add New', 'product-addons' ),
			'edit_item'          => __( 'Edit Option', 'product-addons' ),
			'update_item'        => __( 'Update Option', 'product-addons' ),
			'search_items'       => __( 'Search Option', 'product-addons' ),
			'not_found'          => __( 'No Option Found', 'product-addons' ),
			'not_found_in_trash' => __( 'Not Option found in Trash', 'product-addons' ),
		);
		$args   = array(
			'labels'              => $labels,
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor' ),
			'hierarchical'        => false,
			'public'              => false,
			'rewrite'             => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'capability_type'     => 'page',
		);
		register_post_type( 'prad_option', $args );
	}
}
