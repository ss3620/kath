<?php
namespace WOPB;

defined( 'ABSPATH' ) || exit;

class Builder {
	public function __construct() {
		$this->builder_post_type_callback();
		add_action( 'add_meta_boxes', array( $this, 'init_metabox_callback' ) );
		add_action( 'save_post', array( $this, 'metabox_save_data' ) );
		add_action( 'load-post-new.php', array( $this, 'disable_new_post_templates' ) );
	}

	public function disable_new_post_templates() {
		if ( get_current_screen()->post_type == 'wopb_builder' && ( ! defined( 'WOPB_PRO_VER' ) ) ) {
			$post_count = wp_count_posts( 'wopb_builder' );
			$post_count = $post_count->publish + $post_count->draft;
			if ( $post_count > 0 ) {
				wp_die( 'You are not allowed to do that! Please <a target="_blank" href="' . esc_url( wopb_function()->get_premium_link( '', 'menu_WB_go_pro' ) ) . '">Upgrade Pro.</a>' );
			}
		}
	}


	function init_metabox_callback() {
		add_meta_box(
			'container-width-id',
			esc_html__( 'WowStore Builder', 'product-blocks' ),
			array( $this, 'container_width_callback' ),
			'wopb_builder',
			'side'
		);
	}

	// Meta Box Data HTML Callback
	function container_width_callback( $post ) {
		wp_nonce_field( 'container_meta_box', 'container_meta_box_nonce' );

		$width   = get_post_meta( $post->ID, '__wopb_container_width', true );
		$sidebar = get_post_meta( $post->ID, 'wopb-builder-sidebar', true );
		$widget  = get_post_meta( $post->ID, 'wopb-builder-widget-area', true );
		$p_type  = get_post_meta( $post->ID, '_wopb_builder_type', true );
		$p_type  = $p_type ? $p_type : 'archive';

		$widget_area = wp_get_sidebars_widgets();
		if ( isset( $widget_area['wp_inactive_widgets'] ) ) {
			unset( $widget_area['wp_inactive_widgets'] ); }
		if ( isset( $widget_area['array_version'] ) ) {
			unset( $widget_area['array_version'] ); }
		?>
		<p>
			<label style="margin-bottom:5px;display:block;"><?php esc_html_e( 'Builder Page Container Width', 'product-blocks' ); ?></label>
			<input type="number" name="container-width" style="width: 100%" value="<?php echo esc_attr( $width ? $width : 1140 ); ?>"/>
		</p>
		<p class="productx-meta-sidebar-position">
			<label><?php esc_html_e( 'Sidebar', 'product-blocks' ); ?></label>
			<select name="wopb-builder-sidebar" style="width:88%">
				<option <?php selected( $sidebar, '' ); ?> value=""><?php esc_html_e( '- None -', 'product-blocks' ); ?></option>
				<option <?php selected( $sidebar, 'left' ); ?> value="left"><?php esc_html_e( 'Left Sidebar', 'product-blocks' ); ?></option>
				<option <?php selected( $sidebar, 'right' ); ?> value="right"><?php esc_html_e( 'Right Sidebar', 'product-blocks' ); ?></option>
			</select>
		</p>
		<p class="productx-meta-sidebar-widget">
			<label><?php esc_html_e( 'Select Sidebar(Widget Area)', 'product-blocks' ); ?></label>
			<select name="wopb-builder-widget-area" style="width:88%">
				<option <?php selected( $sidebar, '' ); ?> value=""><?php esc_html_e( '- None -', 'product-blocks' ); ?></option>
				<?php foreach ( $widget_area as $key => $val ) { ?>
					<option <?php selected( $widget, $key ); ?> value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( ucwords( str_replace( '-', ' ', $key ) ) ); ?></option>
				<?php } ?>
			</select>
		</p>
		<?php
	}

	// Save Meta Box Data
	function metabox_save_data( $post_id ) {
		if ( ! isset( $_POST['container_meta_box_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['container_meta_box_nonce'] ) ), 'container_meta_box' ) ) {
			return;
		}
        if ( ! isset( $_POST['container-width'] ) ) { //phpcs:ignore
			return;
		}

        $width  = isset( $_POST['container-width'] ) ? sanitize_text_field( $_POST['container-width'] ) : '1140'; //phpcs:ignore
        $sidebar= isset( $_POST['wopb-builder-sidebar'] ) ? sanitize_text_field( $_POST['wopb-builder-sidebar'] ) : ''; //phpcs:ignore
        $widget = isset( $_POST['wopb-builder-widget-area'] ) ? sanitize_text_field( $_POST['wopb-builder-widget-area'] ) : ''; //phpcs:ignore

		update_post_meta( $post_id, '__wopb_container_width', $width );
		update_post_meta( $post_id, 'wopb-builder-sidebar', $sidebar );
		update_post_meta( $post_id, 'wopb-builder-widget-area', $widget );
	}

	// Builder Post Type Register
	public function builder_post_type_callback() {
		$labels = array(
			'name'               => _x( 'Builder', 'Builder', 'product-blocks' ),
			'singular_name'      => _x( 'Builder', 'Builder', 'product-blocks' ),
			'menu_name'          => __( 'Builder', 'product-blocks' ),
			'parent_item_colon'  => __( 'Parent Builder', 'product-blocks' ),
			'all_items'          => __( 'Builder', 'product-blocks' ),
			'view_item'          => __( 'View Builder', 'product-blocks' ),
			'add_new_item'       => __( 'Add New', 'product-blocks' ),
			'add_new'            => __( 'Add New', 'product-blocks' ),
			'edit_item'          => __( 'Edit Builder', 'product-blocks' ),
			'update_item'        => __( 'Update Builder', 'product-blocks' ),
			'search_items'       => __( 'Search Builder', 'product-blocks' ),
			'not_found'          => __( 'No Builder Found', 'product-blocks' ),
			'not_found_in_trash' => __( 'Not Builder found in Trash', 'product-blocks' ),
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
		register_post_type( 'wopb_builder', $args );
	}
}
