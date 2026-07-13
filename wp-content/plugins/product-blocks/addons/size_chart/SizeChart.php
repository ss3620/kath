<?php
/**
 * Size Chart Addons Core.
 *
 * @package WOPB\SizeChart
 * @since v.3.2.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * SizeChart class.
 */
class SizeChart {

	private $size_chart = 'wopb-size-chart';

	/**
	 * Setup class.
	 *
	 * @since v.3.2.0
	 */
	public function __construct() {
		$this->custom_post_register();
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_size_chart_assets' ), 999 );
		add_filter( 'gutenberg_can_edit_post_type', array( $this, 'gutenberg_editor_disable' ), 10, 2 );
		add_filter( 'use_block_editor_for_post_type', array( $this, 'gutenberg_editor_disable' ), 10, 2 );
		add_action( 'add_meta_boxes', array( $this, 'size_chart_meta_boxes' ) );
		add_action( 'save_post_wopb-size-chart', array( $this, 'save_meta_fields' ), 10, 2 );

		// Size Chart tab in product edit page of admin panel
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'size_chart_admin_product_tab' ), 5, 1 );
		add_action( 'woocommerce_product_data_panels', array( $this, 'size_chart_admin_product_tab_fields' ) );
		add_action( 'woocommerce_process_product_meta', array( $this, 'size_chart_admin_fields_save' ), 10, 2 );

		if ( wopb_function()->get_setting( 'size_chart_display' ) == 'popup' ) {
			$chart_btn_position = wopb_function()->get_setting( 'size_chart_position' );
			if ( $chart_btn_position == 'before_add_cart' || $chart_btn_position == 'after_add_cart' ) {
				$wc_cart_position = array(
					'before_add_cart' => 'wopb_top_add_to_cart',
					'after_add_cart'  => 'wopb_bottom_add_to_cart',
				);
				add_action( $wc_cart_position[ $chart_btn_position ], array( $this, 'size_chart_position' ) );
			} else {
				$meta_position = array(
					'before_meta' => 'woocommerce_product_meta_start',
					'after_meta'  => 'woocommerce_product_meta_end',
				);
				add_action( $meta_position[ $chart_btn_position ], array( $this, 'size_chart_meta_position' ) );
			}
		} else {
			add_filter( 'woocommerce_product_tabs', array( $this, 'size_chart_product_tab' ) );
		}
		// CSS Generator
		add_action( 'wopb_save_settings', array( $this, 'generate_css' ), 10, 1 );
	}

	/**
	 * Enqueue Style & Script
	 *
	 * @return void
	 * @since v.3.2.0
	 */
	public function enqueue_size_chart_assets() {
		if ( wopb_function()->get_setting( 'is_wc_ready' ) ) {
			wp_enqueue_style( 'woocommerce_admin_styles' );
		}
	}

	/**
	 * Register Custom Size Chart Post Type
	 *
	 * @return void
	 * @since v.3.2.0
	 */
	public function custom_post_register() {
		$labels = array(
			'name'                  => esc_html_x( 'Size Chart', 'Post Type General Name', 'product-blocks' ),
			'singular_name'         => esc_html_x( 'Size Chart', 'Post Type Singular Name', 'product-blocks' ),
			'menu_name'             => esc_html__( 'Size Chart', 'product-blocks' ),
			'name_admin_bar'        => esc_html__( 'Size Chart', 'product-blocks' ),
			'archives'              => esc_html__( 'Size Chart Archives', 'product-blocks' ),
			'all_items'             => esc_html__( 'Size Charts', 'product-blocks' ),
			'add_new'               => esc_html__( 'Add New Chart', 'product-blocks' ),
			'add_new_item'          => esc_html__( 'Add New Chart', 'product-blocks' ),
			'edit_item'             => esc_html__( 'Edit Chart', 'product-blocks' ),
			'update_item'           => esc_html__( 'Update Chart', 'product-blocks' ),
			'view_item'             => esc_html__( 'View Chart', 'product-blocks' ),
			'search_items'          => esc_html__( 'Search Size Charts', 'product-blocks' ),
			'not_found'             => esc_html__( 'Not found', 'product-blocks' ),
			'not_found_in_trash'    => esc_html__( 'Not found in Trash', 'product-blocks' ),
			'insert_into_item'      => esc_html__( 'Insert into Size Chart', 'product-blocks' ),
			'uploaded_to_this_item' => esc_html__( 'Uploaded to this Size Chart', 'product-blocks' ),
			'items_list'            => esc_html__( 'Size Charts', 'product-blocks' ),
			'items_list_navigation' => esc_html__( 'Size Charts navigation', 'product-blocks' ),
			'filter_items_list'     => esc_html__( 'Filter from charts', 'product-blocks' ),
		);

		$is_active = wopb_function()->get_setting( 'is_lc_active' );
		$supports  = array( 'title', 'revisions' );
		if ( $is_active ) {
			$supports[]                      = 'thumbnail';
			$labels['featured_image']        = esc_html__( 'Size chart Image', 'product-blocks' );
			$labels['set_featured_image']    = esc_html__( 'Set size chart image', 'product-blocks' );
			$labels['remove_featured_image'] = esc_html__( 'Remove image', 'product-blocks' );
			$labels['use_featured_image']    = esc_html__( 'Use as size chart image', 'product-blocks' );
		}

		$chart_arg = array(
			'label'               => esc_html__( 'Size Chart', 'product-blocks' ),
			'description'         => esc_html__( 'Size Chart', 'product-blocks' ),
			'labels'              => $labels,
			'supports'            => $supports,
			'show_in_menu'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'can_export'          => true,
			'has_archive'         => false,
			'rewrite'             => array(
				'slug'       => $this->size_chart,
				'with_front' => true,
			),
			'query_var'           => true,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'capability_type'     => 'page',
			'show_in_rest'        => false,
			'rest_base'           => $this->size_chart,
		);

		register_post_type( $this->size_chart, $chart_arg );

		if ( ! $is_active ) {
			add_action(
				'add_meta_boxes',
				function () {
					add_meta_box(
						'wopb-size-chart-image-upgrade',
						esc_html__( 'Size chart Image', 'product-blocks' ),
						function () {
							echo '<a style="padding:10px 0;color: #ff176b;" target="_blank" href="' . esc_url( wopb_function()->get_premium_link( 'https://www.wpxpo.com/wowstore' ) ) . '" class="wopb-pro-feature-note">' . esc_html__( 'Upgrade to Pro to set a Size Chart Image.', 'product-blocks' ) . '</a>';
						},
						$this->size_chart,
						'side',
						'low'
					);
				}
			);
		}
	}

	/**
	 * Disable Gutenberg Editor as By Default
	 *
	 * @param bool   $can_edit
	 * @param string $post_type
	 * @return bool
	 * @since v.3.2.0
	 */
	public function gutenberg_editor_disable( bool $can_edit, string $post_type ) {
		return $this->size_chart == $post_type ? false : $can_edit;
	}

	/**
	 * Custom Meta Box Added
	 *
	 * @return void
	 * @since v.3.2.0
	 */
	public function size_chart_meta_boxes() {
		add_meta_box(
			'wopb-size-chart-table',
			__( 'Chart Table', 'product-blocks' ),
			array( $this, 'table_field_callback' ),
			$this->size_chart,
			'advanced',
			'default'
		);
		add_meta_box(
			'wopb-size-chart-assign',
			__( 'Assigning Chart', 'product-blocks' ),
			array( $this, 'assign_fields_callback' ),
			$this->size_chart,
			'side',
			'default'
		);
	}

	/**
	 * Dynamic Editable Table into the Meta box
	 *
	 * @return void
	 * @since v.3.2.0
	 */
	public function table_field_callback() {
		global $post;
		$chart_columns    = get_post_meta( $post->ID, 'wopb_sc_column', true );
		$heading_position = metadata_exists( 'post', $post->ID, 'wopb_sc_heading_position' )
			? get_post_meta( $post->ID, 'wopb_sc_heading_position', true )
			: 'on';
		$hide_name        = metadata_exists( 'post', $post->ID, 'wopb_sc_hide_title' )
			? get_post_meta( $post->ID, 'wopb_sc_hide_title', true )
			: '';

		$head_default    = __( 'Title', 'product-blocks' );
		$content_default = __( 'Content', 'product-blocks' );
		if ( empty( $chart_columns ) ) {
			$chart_columns = array_merge(
				array( array_fill( 0, 3, $head_default ) ),
				array_fill( 0, 3, array_fill( 0, 3, $content_default ) )
			);
		}
		?>
		<p class="wopb-toogle-field-wrap">
			<span class="wopb-label">
				<?php echo esc_html__( 'Table Heading Position', 'product-blocks' ); ?>
			</span>
			<span class="wopb-toggle-control">
				<span><?php echo esc_html__( 'Column', 'product-blocks' ); ?></span>
				<span class="wopb-position-input">
					<input
						type="checkbox"
						name="wopb_sc_heading_position"
						id="wopb-sc-heading-position"
						data-head-default="<?php echo esc_attr( $head_default ); ?>"
						data-content-default="<?php echo esc_attr( $content_default ); ?>"
						<?php echo $heading_position !== '' ? 'checked' : ''; ?>
					>
					<label for="wopb-sc-heading-position" class="wopb-toggle"></label>
				</span>
				<span><?php echo esc_html__( 'Row', 'product-blocks' ); ?></span>
			</span>
		</p>
		<p class="wopb-toogle-field-wrap">
			<span class="wopb-label">
				<?php echo esc_html__( 'Hide Chart Name', 'product-blocks' ); ?>
			</span>
			<span class="wopb-toggle-control">
				<span>
					<input
						type="checkbox"
						name="wopb_sc_hide_title"
						id="wopb-sc-hide-title"
						<?php echo $hide_name !== '' ? 'checked' : ''; ?>
					>
					<label for="wopb-sc-hide-title" class="wopb-toggle"></label>
				</span>
			</span>
		</p>
		<input type="hidden" name="wopb_sc_column_array" id="wopb-sc-column-array">
		<table class="wopb-sc-table">
			<tbody>
				<tr class="wopb-sc-control-col">
					<?php
					if ( isset( $chart_columns[0] ) ) {
						foreach ( $chart_columns[0] as $row_index => $column ) {
							?>
							<td>
								<a class="wopb-sc-control-btn wopb-sc-add-item">
									<span class="dashicons dashicons-plus-alt2"></span>
									<span class="wopb-sc-tooltip"><?php echo esc_html__( 'Add Column', 'product-blocks' ); ?></span>
								</a>
								<a class="wopb-sc-control-btn wopb-sc-del-item">
									<span class="dashicons dashicons-minus"></span>
									<span class="wopb-sc-tooltip"><?php echo esc_html__( 'Remove Column', 'product-blocks' ); ?></span>
								</a>
							</td>
							<?php
						}
					}
					?>
				</tr>
				<?php
				if ( is_array( $chart_columns ) ) {
					foreach ( $chart_columns as $row_index => $columns ) {
						$row_control_class = $row_index == 0 ? ' wopb-d-none' : '';
						?>
						<tr class="wopb-sc-input-row">
							<?php
							foreach ( $columns as $col_index => $column ) {
								$col_class = '';
								if ( $heading_position != '' && $row_index == 0 ) {
									$col_class = ' wopb-sc-head-col';
								} elseif ( $heading_position == '' && $col_index == 0 ) {
									$col_class = ' wopb-sc-head-col';
								}
								?>
								<td class="wopb-sc-input-col<?php echo esc_attr( $col_class ); ?>">
									<input
										type="text"
										value="<?php echo esc_attr( $column ); ?>"
										class="wopb-sc-solumn" placeholder='Enter text..'
										autocomplete="off"
									>
								</td>
							<?php } ?>
							<td class="wopb-sc-control-row">
								<?php if ( $row_index > 0 ) { ?>
									<a class="wopb-sc-control-btn wopb-sc-add-item<?php echo esc_attr( $row_control_class ); ?>">
										<span class="dashicons dashicons-plus-alt2"></span>
										<span class="wopb-sc-tooltip"><?php echo esc_html__( 'Add Row', 'product-blocks' ); ?></span>
									</a>
									<a class="wopb-sc-control-btn wopb-sc-del-item<?php echo esc_html( $row_control_class ); ?>">
										<span class="dashicons dashicons-minus"></span>
										<span class="wopb-sc-tooltip"><?php echo esc_html__( 'Remove Row', 'product-blocks' ); ?></span>
									</a>
								<?php } ?>
							</td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Assign All Meta Field Markup
	 *
	 * @return void
	 * @since v.3.2.0
	 */
	public function assign_fields_callback() {
		global $post;
		$all_product_selected = get_post_meta( $post->ID, 'wopb_sc_all_product', true );
		$get_category         = get_post_meta( $post->ID, 'wopb_sc_category', true );
		$include_products     = get_post_meta( $post->ID, 'wopb_sc_include_products', true );
		$exclude_products     = get_post_meta( $post->ID, 'wopb_sc_exclude_products', true );
		?>
		<p>
			<input
				type="checkbox"
				id="wopb-sc-all-product"
				name="wopb_sc_all_product"
				value="1"
				<?php checked( $all_product_selected, true, true ); ?>
			/>
			<label for="wopb-sc-all-product">
				<?php echo esc_html__( 'Apply On All Products ?', 'product-blocks' ); ?>
			</label>
		</p>
		<div class="wopb-sc-category<?php echo $all_product_selected ? ' wopb-d-none' : ''; ?>">
			<label><?php echo esc_html__( 'Assign Category', 'product-blocks' ); ?></label>
			<div>
				<select name="wopb_sc_category[]" class="wopb-select2" multiple>
					<?php
						$categories = get_terms(
							array(
								'taxonomy' => 'product_cat',
								'fields'   => 'all',
								'orderby'  => 'id',
								'order'    => 'ASC',
							)
						);
					foreach ( $categories as $category ) {
						$selected = '';
						if ( ! empty( $get_category ) && in_array( $category->term_id, $get_category ) ) {
							$selected = 'selected';
						}
						echo '<option value="' . esc_attr( $category->term_id ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $category->name ) . '</option>';
					}
					?>
				</select>
			</div>
		</div>

		<?php
		$this->render_product_select_field(
			'wopb_sc_include_products',
			! empty( $include_products ) ? $include_products : array(),
			__( 'Include Products', 'product-blocks' ),
			'wopb-sc-include-products' . ( $all_product_selected ? ' wopb-d-none' : '' ),
			true
		);
		$this->render_product_select_field(
			'wopb_sc_exclude_products',
			! empty( $exclude_products ) ? $exclude_products : array(),
			__( 'Exclude Products', 'product-blocks' ),
			'wopb-sc-exclude-products',
			false
		);
	}

	/**
	 * Product Selected Fields
	 *
	 * @param string $key
	 * @param array  $selected_products
	 * @param string $label
	 * @param string $class
	 * @return void
	 * @since v.3.2.0
	 */
	private function render_product_select_field( string $key, array $selected_products, string $label, string $class = '', $pro = false ) {
		$is_active = wopb_function()->get_setting( 'is_lc_active' );
		?>
		<p class="wopb-sc-products <?php echo esc_attr( $class ); ?>">
			<label for="<?php echo esc_attr( $key ); ?>">
				<?php
					echo esc_html( $label ) . wc_help_tip( __( 'Select Products List To Show Specific Product.', 'product-blocks' ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</label>
			<select
				<?php echo ( $pro && ! $is_active ) ? 'disabled' : ''; ?>
				id="<?php echo esc_attr( $key ); ?>"
				class="wc-product-search"
				name="<?php echo esc_attr( $key ) . '[]'; ?>"
				multiple="multiple"
				data-placeholder="<?php echo esc_attr__( 'Search for a product', 'product-blocks' ); ?>"
				data-action="woocommerce_json_search_products" data-minimum_input_length="3"
				data-exclude_type="variation"
				style="min-width: 250px;">
				<?php
				foreach ( $selected_products as $product_id ) {
					$product = wc_get_product( $product_id );
					if ( is_object( $product ) ) {
						$selected = '';
						if ( in_array( $product_id, $selected_products ) ) {
							$selected = 'selected';
						}
						?>
							<option
								value="<?php echo esc_attr( $product_id ); ?>"
								<?php echo esc_attr( $selected ); ?>
							>
								<?php echo esc_html( wp_strip_all_tags( $product->get_formatted_name() ) ); ?>
							</option>
						<?php
					}
				}
				?>
			</select>
			<?php

			if ( $pro && ! $is_active ) {
				printf(
					'<a style="padding:10px 0;color: #ff176b;" target="_blank" href="%s" class="wopb-pro-feature-note">%s</a>',
					esc_url( wopb_function()->get_premium_link( 'https://www.wpxpo.com/wowstore' ) ),
					esc_html__( 'Upgrade to Pro to use this feature.', 'product-blocks' )
				);
			}
			?>
		</p>
		<?php
	}

	/**
	 * Save Size Chart Meta box Fields
	 *
	 * @param int    $post_id
	 * @param object $post
	 * @return void
	 * @since v.3.2.0
	 */
	public function save_meta_fields( int $post_id, object $post ) {
		$post_id = absint( $post_id );
		if (
			empty( $post_id ) ||
			empty( $post ) ||
			empty( $_POST['post_ID'] ) || //phpcs:ignore WordPress.Security.NonceVerification.Missing
			absint( $_POST['post_ID'] ) !== $post_id || //phpcs:ignore WordPress.Security.NonceVerification.Missing
			defined( 'DOING_AUTOSAVE' ) &&
			! current_user_can( 'edit_post', $post_id )
		) {
			return;
		}
		if ( isset( $_POST['wopb_sc_column_array'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$column_array = json_decode( str_replace( '\"', '"', $_POST['wopb_sc_column_array'] ) ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
			update_post_meta( $post_id, 'wopb_sc_column', wopb_function()->rest_sanitize_params( $column_array ) ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
		update_post_meta( $post_id, 'wopb_sc_heading_position', isset( $_POST['wopb_sc_heading_position'] ) ? wopb_function()->rest_sanitize_params( $_POST['wopb_sc_heading_position'] ) : '' ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $post_id, 'wopb_sc_hide_title', isset( $_POST['wopb_sc_hide_title'] ) ? wopb_function()->rest_sanitize_params( $_POST['wopb_sc_hide_title'] ) : '' ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $post_id, 'wopb_sc_all_product', isset( $_POST['wopb_sc_all_product'] ) ? wopb_function()->rest_sanitize_params( $_POST['wopb_sc_all_product'] ) : '' ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $post_id, 'wopb_sc_category', isset( $_POST['wopb_sc_category'] ) ? wopb_function()->rest_sanitize_params( $_POST['wopb_sc_category'] ) : '' ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $post_id, 'wopb_sc_include_products', isset( $_POST['wopb_sc_include_products'] ) ? wopb_function()->rest_sanitize_params( $_POST['wopb_sc_include_products'] ) : '' ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
		update_post_meta( $post_id, 'wopb_sc_exclude_products', isset( $_POST['wopb_sc_exclude_products'] ) ? wopb_function()->rest_sanitize_params( $_POST['wopb_sc_exclude_products'] ) : '' ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/**
	 * Size Chart tab in product edit page in admin panel
	 *
	 * @return array
	 * @since v.4.0.0
	 */
	public function size_chart_admin_product_tab( $product_data_tabs ) {
		$product_data_tabs['wopb_size_chart'] = array(
			'label'    => __( 'Size Chart', 'product-blocks' ),
			'class'    => array( 'wopb_product_tab' ),
			'target'   => 'wopb_size_chart_tab_data',
			'priority' => 15.3,
		);
		return $product_data_tabs;
	}

	/**
	 * Size Chart Custom Field
	 *
	 * @return void
	 * @since v.4.0.0
	 */
	public function size_chart_admin_product_tab_fields() {
		global $post;
		$args_1 = array( // Query for get all charts for dropdown
			'post_type'      => $this->size_chart,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
		);
		$charts = new \WP_Query( $args_1 );

		$get_charts = get_post_meta( $post->ID, 'wopb_size_chart_ids', true );
		?>
		<div class="panel woocommerce_options_panel hidden" id="wopb_size_chart_tab_data">
			<div class="wopb-field-group">
				<?php if ( $charts->have_posts() ) { ?>
					<label for="wopb_size_chart_ids">
						<?php echo esc_html__( 'Select Size Chart', 'product-blocks' ); ?>
					</label>
					<select
							id="wopb_size_chart_ids"
							class="wopb-select2"
							name="wopb_size_chart_ids[]" 
						>
						<option value=""><?php echo esc_html__( 'Select Size Chart', 'product-blocks' ); ?></option>
						<?php
						foreach ( $charts->posts as $chart ) {
							$chart_id     = $chart->ID;
								$selected = '';
							if ( ! empty( $get_charts ) && in_array( $chart_id, $get_charts ) ) {
								$selected = 'selected';
							}
							?>
									<option
										value="<?php echo esc_attr( $chart_id ); ?>"
									<?php echo esc_attr( $selected ); ?>
									>
									<?php echo esc_html( $chart->post_title ); ?>
									</option>
						<?php } ?>
					</select>
				<?php } else { ?>
					<div class="wopb-chart-create-desc">
						<?php echo esc_html__( 'There is no chart available', 'product-blocks' ); ?>
						<a
							href="<?php echo esc_url( admin_url( 'post-new.php?post_type=wopb-size-chart' ) ); ?>"
							class="wopb-add-chart-btn"
						>
							<?php echo esc_html__( 'Create Chart', 'product-blocks' ); ?>
						</a>
					</div>
				<?php } ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save input field when product save in admin panel
	 *
	 * @param INT $post_id
	 * @return void
	 * @since v.1.0.4
	 */
	public function size_chart_admin_fields_save( $post_id ) {
		$args      = array(
			'post_type'      => $this->size_chart,
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'meta_query'     => array(
				array(
					'key'     => 'wopb_sc_include_products',
					'value'   => $post_id,
					'compare' => 'LIKE',
				),
			),
		);
		$results   = new \WP_Query( $args );
		$chart_ids = wopb_function()->rest_sanitize_params( $_POST['wopb_size_chart_ids'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $results->posts ) {
			foreach ( $results->posts as $chart ) {
				$include_products = get_post_meta( $chart->ID, 'wopb_sc_include_products', true );
				if ( ! empty( $include_products ) ) {
					if (
						! empty( $chart_ids ) &&
						in_array( $chart->ID, $chart_ids ) &&
						in_array( $post_id, $include_products )
					) {
						// Check selected chart and product id exist or not in existing chart.
						// If already exist skip then chart id save in database
						$chart_ids = array_diff( $chart_ids, array( $chart->ID ) );
					} elseif ( in_array( $post_id, $include_products ) ) {
						// If product id exist in existing chart then product remove from the chart
						$include_products = array_diff( $include_products, array( $post_id ) );
						update_post_meta( $chart->ID, 'wopb_sc_include_products', $include_products );
					}
				}
			}
		}
		// Product save to selected chart
		if ( ! empty( $chart_ids ) ) {
			foreach ( $chart_ids as $chart_id ) {
				$include_products = get_post_meta( $chart_id, 'wopb_sc_include_products', true );
				if ( empty( $include_products ) ) {
					$include_products = array();
				}
				$include_products[] = $post_id;
				update_post_meta( $chart_id, 'wopb_sc_include_products', $include_products );
			}
		}
		update_post_meta( $post_id, 'wopb_size_chart_ids', $_POST['wopb_size_chart_ids'] ); //phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/**
	 * Get Products by Product IDS
	 *
	 * @param array $product_ids
	 * @return string|void
	 * @since v.3.2.0
	 */
	public function product_names_by_ids( array $product_ids ) {
		$products      = wc_get_products(
			array(
				'limit'   => -1,
				'include' => $product_ids,
			)
		);
		$product_names = array();

		foreach ( $products as $product ) {
			$product_names[] = $product->get_name();
		}

		if ( ! empty( $product_names ) ) {
			return implode( ', ', $product_names );
		}
	}

	/**
	 * Display Button Position Base On The Hooks
	 *
	 * @return string
	 * @since v.3.2.0
	 */
	public function size_chart_position() {
		$table_content = $this->table_content();
		if ( $table_content ) {
			$html          = '';
			$html         .= '<div class="wopb-chart-btn-wrapper">';
				$html     .= '<span id="wopb-chart-modal-btn">';
					$html .= wopb_function()->svg_icon( wopb_function()->get_setting( 'size_chart_btn_icon' ) );
					$html .= esc_html( wopb_function()->get_setting( 'size_chart_btn_text' ) );
				$html     .= '</span>';
			$html         .= '</div>';
			$html         .= $table_content;
			return $html;
		}
	}

	/**
	 * Display Button Position Base On The Hooks
	 *
	 * @return void
	 * @since v.3.2.0
	 */
	public function size_chart_meta_position() {
		echo $this->size_chart_position();
	}

	/**
	 * Size Chart Tab In Single Product
	 *
	 * @param array $tabs
	 * @return array
	 * @since v.3.2.0
	 */
	public function size_chart_product_tab( array $tabs ) {
		$table_content = $this->table_content();
		if ( $table_content ) {
			$tabs['wopb_size_chart_tab'] = array(
				'title'    => esc_html( wopb_function()->get_setting( 'size_chart_tab_text' ) ),
				'callback' => function () use ( $table_content ) {
					echo $table_content;
				},
				'priority' => 60,
			);
		}
		return $tabs;
	}

	/**
	 * Check If Product Assigned In Size Chart
	 *
	 * @param array $chart_ids
	 * @return bool
	 * @since v.3.2.0
	 */
	public function is_assigned_on_products( array $chart_ids ) {
		global $product;
		$exclude_products = get_post_meta( $chart_ids[0], 'wopb_sc_exclude_products', true );
		$include_products = get_post_meta( $chart_ids[0], 'wopb_sc_include_products', true );
		$categories       = get_post_meta( $chart_ids[0], 'wopb_sc_category', true );

		if ( ! empty( $exclude_products ) && in_array( $product->get_id(), $exclude_products ) ) {
			return false;
		} elseif ( get_post_meta( $chart_ids[0], 'wopb_sc_all_product', true ) ) {
			return true;
		} elseif ( ! empty( $include_products ) && in_array( $product->get_id(), $include_products ) ) {
			return true;
		} elseif ( ! empty( $categories ) && array_intersect( $product->get_category_ids(), $categories ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Chart and Product Details For Reuse
	 *
	 * @return array
	 * @since v.3.2.0
	 */
	public function chart_list() {
		global $product;
		$args = array(
			'post_type'      => $this->size_chart,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'desc',
			'meta_query'     => array(
				'relation' => 'OR',
				array(
					'key'     => 'wopb_sc_all_product',
					'value'   => 1,
					'compare' => '=',
				),
				array(
					'key'     => 'wopb_sc_include_products',
					'value'   => '"' . $product->get_id() . '"',
					'compare' => 'LIKE',
				),
				array(
					'key'     => 'wopb_sc_exclude_products',
					'value'   => '"' . $product->get_id() . '"',
					'compare' => 'LIKE',
				),
			),
		);
		if ( ! empty( $product->get_category_ids() ) ) {
			foreach ( $product->get_category_ids() as $category_id ) {
				$args['meta_query'][] = array(
					'key'     => 'wopb_sc_category',
					'value'   => '"' . $category_id . '"',
					'compare' => 'LIKE',
				);
			}
		}

		$query  = new \WP_Query( $args );
		$charts = $query->posts;
		return wp_list_pluck( $charts, 'ID' );
	}

	/**
	 * Table Content Preview in Front-end
	 *
	 * @return void|null|boolean
	 * @since v.3.2.0
	 */
	public function table_content() {
		if ( ! is_product() ) {
			return;
		}
		global $product;
		$chart_ids             = get_post_meta( $product->get_id(), 'wopb_size_chart_ids', true );
		$chart_id_from_product = '';
		$chart_id_from_post    = '';
		$chart_id              = '';
		if ( ! empty( $chart_ids[0] ) && get_post_status( $chart_ids[0] ) == 'publish' ) {
			$chart_id = $chart_id_from_product = $chart_ids[0];
		} elseif ( $chart_ids = $this->chart_list() ) {
			$chart_id = $chart_id_from_post = $chart_ids[0];
		}
		if (
			$chart_id &&
			(
				$chart_id_from_product ||
				( $chart_id_from_post && $this->is_assigned_on_products( $chart_ids ) )
			)
		) {
			$table_rows       = get_post_meta( $chart_id, 'wopb_sc_column', true );
			$heading_position = get_post_meta( $chart_id, 'wopb_sc_heading_position', true );
			$hide_title       = get_post_meta( $chart_id, 'wopb_sc_hide_title', true );
			$title            = get_the_title( $chart_id );
			$is_tab           = wopb_function()->get_setting( 'size_chart_display' ) == 'additional_tab';
			$modal_class      = 'wopb-size-chart-wrapper';
			$modal_class     .= ! $is_tab ? ' wopb-size-chart-modal' : '';
			ob_start();
			?>
			<div class="<?php echo esc_attr( $modal_class ); ?>">
				<div class="wopb-chart-<?php echo $is_tab ? 'tab' : 'modal'; ?>-content">
					<?php if ( ( $is_tab && $title && ! $hide_title ) || ! $is_tab ) { ?>
						<div class="wopb-sc-title">
							<?php echo ! $hide_title ? esc_html( $title ) : ''; ?>
							<?php if ( ! $is_tab ) { ?>
								<span class="wopb-chart-modal-close">
									<svg width="24" height="24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6 6 18M6 6l12 12" stroke="#070707" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
								</span>
							<?php } ?>
						</div>
					<?php } ?>
					<div class="wopb-chart-body">
						<?php
						if ( has_post_thumbnail( $chart_id ) ) {
							echo '<div class="wopb-size-chart-image">' . get_the_post_thumbnail( $chart_id ) . '</div>';
						} elseif ( ! empty( $table_rows ) ) {
							$table_class  = ' wopb-scrollbar';
							$table_class .= $heading_position != '' ? ' wopb-heading-row' : ' wopb-heading-col';
							?>
							<div class="wopb-sc-table<?php echo $table_class; ?>">
								<table >
								<?php
								foreach ( $table_rows as $key => $columns ) {
									?>
										<tr class="wopb-sc-table-row">
									<?php
									foreach ( $columns as $index => $column ) {
										$col_class = '';
										if ( $heading_position != '' && $key == 0 ) {
											$col_class = 'wopb-sc-head-col';
										} elseif ( $heading_position == '' && $index == 0 ) {
											$col_class = 'wopb-sc-head-col';
										}
										?>
											<td class="<?php echo esc_attr( $col_class ); ?>">
											<?php echo esc_html( $column ); ?>
											</td>
											<?php } ?>
										</tr>
									<?php } ?>
								</table>
							</div>
						<?php } ?>
					</div>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
	}

	/**
	 * CSS Generator
	 *
	 * @return void
	 * @since v.3.2.0
	 */
	public function generate_css( $key ) {
		if ( $key == 'wopb_size_chart' && method_exists( wopb_function(), 'convert_css' ) ) {
			$settings  = wopb_function()->get_setting();
			$btn_style = array_merge( $settings['size_chart_btn_typo'], $settings['size_chart_btn_bg'] );

			$css      = '#wopb-chart-modal-btn {';
				$css .= wopb_function()->convert_css( 'general', $btn_style );
				$css .= 'padding: ' . wopb_function()->convert_css( 'dimension', $settings['size_chart_btn_padding'] ) . ';';
				$css .= 'border: ' .
					( ! empty( $settings['size_chart_btn_border']['border'] )
						? $settings['size_chart_btn_border']['border']
						: 0
					) . 'px solid ' .
					( ! empty( $settings['size_chart_btn_border']['color'] )
						? $settings['size_chart_btn_border']['color']
						: ''
					) . ';';
				$css .= ! empty( $settings['size_chart_btn_radius'] ) ? 'border-radius: ' . $settings['size_chart_btn_radius'] . 'px;' : '';
			$css     .= '}';
			$css     .= '#wopb-chart-modal-btn:hover {';
				$css .= wopb_function()->convert_css( 'hover', $btn_style );
			$css     .= '}';
			$css     .= '#wopb-chart-modal-btn svg{';
				$css .= isset( $settings['size_chart_btn_icon_size']['color'] ) ? 'fill: ' . $settings['size_chart_btn_icon_size']['color'] . ';' : '';
				$css .= 'width : ' . ( ! empty( $settings['size_chart_btn_icon_size'] ) ? $settings['size_chart_btn_icon_size'] : 20 ) . 'px;';
				$css .= 'height : ' . ( ! empty( $settings['size_chart_btn_icon_size'] ) ? $settings['size_chart_btn_icon_size'] : 20 ) . 'px;';
			$css     .= '}';

			$css     .= '.wopb-size-chart-wrapper .wopb-sc-table th, .wopb-size-chart-wrapper .wopb-sc-table td {';
				$css .= 'border: ' .
					( ! empty( $settings['size_chart_table_border']['border'] )
						? $settings['size_chart_table_border']['border']
						: 1
					) . 'px solid ' .
					( ! empty( $settings['size_chart_table_border']['color'] )
						? $settings['size_chart_table_border']['color']
						: '#646464'
					) . ';';
			$css     .= '}';

			$css     .= '.wopb-size-chart-wrapper .wopb-sc-table .wopb-sc-head-col {';
				$css .= isset( $settings['size_chart_heading_bg']['bg'] ) ? 'background-color: ' . $settings['size_chart_heading_bg']['bg'] . ';' : '';
				$css .= isset( $settings['size_chart_heading_color']['color'] ) ? 'color: ' . $settings['size_chart_heading_color']['color'] . ';' : '';
			$css     .= '}';
			$css     .= '.wopb-size-chart-wrapper .wopb-sc-table .wopb-sc-head-col:hover {';
				$css .= isset( $settings['size_chart_heading_bg']['hover_bg'] ) ? 'background-color: ' . $settings['size_chart_heading_bg']['hover_bg'] . ';' : '';
				$css .= isset( $settings['size_chart_heading_color']['hover_color'] ) ? 'color: ' . $settings['size_chart_heading_color']['hover_color'] . ';' : '';
			$css     .= '}';

			$css     .= '.wopb-sc-table-row:nth-child(even) {';
				$css .= isset( $settings['size_chart_even_row_bg']['bg'] ) ? 'background-color: ' . $settings['size_chart_even_row_bg']['bg'] . ';' : '';
				$css .= isset( $settings['size_chart_even_row_color']['color'] ) ? 'color: ' . $settings['size_chart_even_row_color']['color'] . ';' : '';
			$css     .= '}';
			$css     .= '.wopb-sc-table-row:nth-child(even):hover {';
				$css .= isset( $settings['size_chart_even_row_bg']['hover_bg'] ) ? 'background-color: ' . $settings['size_chart_even_row_bg']['hover_bg'] . ';' : '';
				$css .= isset( $settings['size_chart_even_row_color']['hover_color'] ) ? 'color: ' . $settings['size_chart_even_row_color']['hover_color'] . ';' : '';
			$css     .= '}';

			$css     .= '.wopb-sc-table-row:nth-child(odd) {';
				$css .= isset( $settings['size_chart_odd_row_bg']['bg'] ) ? 'background-color: ' . $settings['size_chart_odd_row_bg']['bg'] . ';' : '';
				$css .= isset( $settings['size_chart_odd_row_color']['color'] ) ? 'color: ' . $settings['size_chart_odd_row_color']['color'] . ';' : '';
			$css     .= '}';
			$css     .= '.wopb-sc-table-row:nth-child(odd):hover {';
				$css .= isset( $settings['size_chart_odd_row_bg']['hover_bg'] ) ? 'background-color: ' . $settings['size_chart_odd_row_bg']['hover_bg'] . ';' : '';
				$css .= isset( $settings['size_chart_odd_row_color']['hover_color'] ) ? 'color: ' . $settings['size_chart_odd_row_color']['hover_color'] . ';' : '';
			$css     .= '}';

			wopb_function()->update_css( $key, 'add', $css );
		}
	}
}
