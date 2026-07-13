<?php
/**
 * SaveTemplate Addons Core.
 *
 * @package WOPB\SaveTemplate
 * @since v.1.1.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

class Saved_Templates {
	public function __construct() {
		$this->templates_post_type_callback();
		add_action( 'admin_head', array( $this, 'custom_head_templates' ) );
		add_action( 'load-post-new.php', array( $this, 'disable_new_post_templates' ) );
		add_filter( 'manage_wopb_templates_posts_columns', array( $this, 'templates_table_head' ) );
		add_action( 'manage_wopb_templates_posts_custom_column', array( $this, 'templates_table_content' ), 10, 2 );
	}

	/**
	 * SaveTemplate Head Pro Link HTML
	 *
	 * @since v.1.1.0
	 * @return NULL
	 */
	public function custom_head_templates() {
		if ( 'wopb_templates' == get_current_screen()->post_type && ( ! defined( 'WOPB_PRO_VER' ) ) ) {
			$post_count = wp_count_posts( 'wopb_templates' );
			$post_count = $post_count->publish + $post_count->draft;
			if ( $post_count > 0 ) { ?>
				<span class="wopb-saved-templates-action" data-link="<?php echo esc_url( wopb_function()->get_premium_link( '', 'menu_saved_template_go_pro' ) ); ?>" data-text="Go Pro for Unlimited Templates" data-count="<?php echo esc_attr( $post_count ); ?>" style="display:none;"></span>
				<?php
			}
		}
	}

	/**
	 * Disable New Post for Free Users
	 *
	 * @since v.1.1.0
	 * @return NULL
	 */
	public function disable_new_post_templates() {
		if ( get_current_screen()->post_type == 'wopb_templates' && ( ! defined( 'WOPB_PRO_VER' ) ) ) {
			$post_count = wp_count_posts( 'wopb_templates' );
			$post_count = $post_count->publish + $post_count->draft;
			if ( $post_count > 0 ) {
				wp_die( 'You are not allowed to do that! Please <a target="_blank" href="' . esc_url( wopb_function()->get_premium_link( '', 'menu_saved_template_go_pro' ) ) . '">Upgrade Pro.</a>' );
			}
		}
	}

	/**
	 * Template Heading Add
	 *
	 * @since v.1.1.0
	 * @return NULL
	 */
	public function templates_table_head( $defaults ) {
		$type_array = array( 'type' => __( 'Shortcode', 'product-blocks' ) );
		array_splice( $defaults, 2, 0, $type_array );
		return $defaults;
	}

	/**
	 * Column Content
	 *
	 * @since v.1.1.0
	 * @return NULL
	 */
	public function templates_table_content( $column_name, $post_id ) {
		echo '<code class="wopb-shortcode-copy">[product_blocks id="' . esc_attr( $post_id ) . '"]</code>';
	}

	/**
	 * Templates Post Type Register
	 *
	 * @since v.1.1.0
	 * @return NULL
	 */
	public function templates_post_type_callback() {
		$labels = array(
			'name'               => _x( 'Saved Templates', 'Templates', 'product-blocks' ),
			'singular_name'      => _x( 'Saved Template', 'Templates', 'product-blocks' ),
			'menu_name'          => __( 'Saved Templates', 'product-blocks' ),
			'parent_item_colon'  => __( 'Parent Template', 'product-blocks' ),
			'all_items'          => __( 'Saved Templates', 'product-blocks' ),
			'view_item'          => __( 'View Template', 'product-blocks' ),
			'add_new_item'       => __( 'Add New Template', 'product-blocks' ),
			'add_new'            => __( 'Add New Template', 'product-blocks' ),
			'edit_item'          => __( 'Edit Template', 'product-blocks' ),
			'update_item'        => __( 'Update Template', 'product-blocks' ),
			'search_items'       => __( 'Search Template', 'product-blocks' ),
			'not_found'          => __( 'No Template Found', 'product-blocks' ),
			'not_found_in_trash' => __( 'Not Template found in Trash', 'product-blocks' ),
		);
		$args   = array(
			'labels'              => $labels,
			'show_in_rest'        => true,
			'supports'            => array( 'title', 'editor' ),
			'hierarchical'        => false,
			'public'              => false,
			'rewrite'             => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'capability_type'     => 'page',
		);
		register_post_type( 'wopb_templates', $args );
	}
}
