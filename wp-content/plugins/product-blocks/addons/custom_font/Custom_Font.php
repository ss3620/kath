<?php
namespace WOPB;

defined( 'ABSPATH' ) || exit;

class Custom_Font {
	public function __construct() {
		$this->custom_font_post_type_callback();
		add_action( 'save_post', array( $this, 'metabox_save_data' ) );
		add_action( 'add_meta_boxes', array( $this, 'init_metabox_callback' ) );
		add_action( 'manage_wopb_custom_font_posts_custom_column', array( $this, 'templates_table_content' ), 10, 2 );
		add_filter( 'upload_mimes', array( $this, 'add_file_types_to_uploads' ) );
		add_filter( 'enter_title_here', array( $this, 'update_custom_font_title' ), 10, 2 );
		add_filter( 'wp_check_filetype_and_ext', array( $this, 'font_correct_filetypes' ), 10, 5 );
		add_filter( 'manage_wopb_custom_font_posts_columns', array( $this, 'templates_table_head' ) );
	}

	public function update_custom_font_title( $title, $post ) {
		if ( isset( $post->post_type ) && 'wopb_custom_font' === $post->post_type ) {
			return __( 'Add Font Family Name', 'product-blocks' );
		}
		return $title;
	}

	public function font_correct_filetypes( $data, $file, $filename, $mimes, $real_mime ) {
		if ( ! empty( $data['ext'] ) && ! empty( $data['type'] ) ) {
			return $data;
		}

		$wp_file_type = wp_check_filetype( $filename, $mimes );

		if ( 'ttf' === $wp_file_type['ext'] ) {
			$data['ext']  = 'ttf';
			$data['type'] = 'font/ttf';
		}
		return $data;
	}


	public function add_file_types_to_uploads( $file_types ) {
		$new_filetypes          = array();
		$new_filetypes['woff']  = 'font/woff';
		$new_filetypes['woff2'] = 'font/woff2';
		$new_filetypes['ttf']   = 'font/ttf';
		$new_filetypes['svg']   = 'image/svg+xml';
		$new_filetypes['eot']   = 'font/ttf';
		$file_types             = array_merge( $file_types, $new_filetypes );
		return $file_types;
	}


	// Template Heading Add
	public function templates_table_head( $defaults ) {
		$type_array        = array(
			'preview' => '<span class="wopb-custom-font-preview-th">' . __( 'Preview', 'product-blocks' ) . '</span>',
			'woff'    => __( 'WOFF', 'product-blocks' ),
			'woff2'   => __( 'WOFF2', 'product-blocks' ),
			'ttf'     => __( 'TTF', 'product-blocks' ),
			'svg'     => __( 'SVG', 'product-blocks' ),
			'eot'     => __( 'EOT', 'product-blocks' ),
		);
		$defaults['title'] = __( 'Font Family', 'product-blocks' );
		array_splice( $defaults, 2, 0, $type_array );

		return $defaults;
	}


	// Get Font Face
	public function get_font_face( $settings, $font_name ) {
		$font_src = array();
		if ( $settings['woff'] ) {
			array_push( $font_src, 'url(' . esc_url( $settings['woff'] ) . ') format("woff")' );
		}
		if ( $settings['woff2'] ) {
			array_push( $font_src, 'url(' . esc_url( $settings['woff2'] ) . ') format("woff2")' );
		}
		if ( $settings['ttf'] ) {
			array_push( $font_src, 'url(' . esc_url( $settings['ttf'] ) . ') format("TrueType")' );
		}
		if ( $settings['svg'] ) {
			array_push( $font_src, 'url(' . esc_url( $settings['svg'] ) . ') format("svg")' );
		}
		if ( $settings['eot'] ) {
			array_push( $font_src, 'url(' . esc_url( $settings['eot'] ) . ') format("eot")' );
		}
		$font_face = '@font-face {
            font-family: "' . esc_attr( $font_name ) . '";
            font-weight: ' . esc_attr( $settings['weight'] ) . ';
            src: ' . implode( ', ', $font_src ) . ';
        }';

		return $font_face;
	}


	// Column Content
	public function templates_table_content( $column_id, $post_id ) {
		$woff     = $woff2 = $ttf = $svg = $eot = false;
		$settings = get_post_meta( $post_id, '__font_settings', true );

		if ( $settings ) {
			foreach ( $settings as $key => $value ) {
				if ( $value['woff'] ) {
					$woff = true; }
				if ( $value['woff2'] ) {
					$woff2 = true; }
				if ( $value['ttf'] ) {
					$ttf = true; }
				if ( $value['svg'] ) {
					$svg = true; }
				if ( $value['eot'] ) {
					$eot = true; }
			}
			$font_face = $this->get_font_face( $settings[0], get_the_title( $post_id ) );
			echo wopb_function()->esc_inline( $font_face ); //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped

			switch ( $column_id ) {
				case 0:
					echo '<span class="wopb-custom-font-preview" style="font-family: ' . esc_attr( get_the_title( $post_id ) ) . '">' . esc_html__( 'The quick brown fox jumps over the lazy dog.', 'product-blocks' ) . '</span>';
					break;
				case 1:
					echo '<span class="dashicons ' . ( $woff ? 'dashicons-yes' : 'dashicons-no-alt' ) . '"></span>';
					break;
				case 2:
					echo '<span class="dashicons ' . ( $woff2 ? 'dashicons-yes' : 'dashicons-no-alt' ) . '"></span>';
					break;
				case 3:
					echo '<span class="dashicons ' . ( $ttf ? 'dashicons-yes' : 'dashicons-no-alt' ) . '"></span>';
					break;
				case 4:
					echo '<span class="dashicons ' . ( $svg ? 'dashicons-yes' : 'dashicons-no-alt' ) . '"></span>';
					break;
				case 5:
					echo '<span class="dashicons ' . ( $eot ? 'dashicons-yes' : 'dashicons-no-alt' ) . '"></span>';
					break;
				default:
					break;
			}
		}
	}


	function init_metabox_callback() {
		add_meta_box(
			'wopb-custom-font-id',
			esc_html__( 'Font Vaiations', 'product-blocks' ),
			array( $this, 'custom_font_callback' ),
			'wopb_custom_font',
			'advanced'
		);
	}


	function set_data( $arr = array(), $font_name = '' ) {
		?>
		<div class="wopb-custom-font-container wopb-custom-font<?php echo esc_attr( empty( $arr ) ) ? '-copy' : ''; ?>">
			<div class="wopb-custom-font-heading">
				<div>
					<label class="font-label"><?php echo esc_html__( 'Weight:  ', 'product-blocks' ); ?> <span class="wopb-custom-font-weight"> <?php echo esc_html( isset( $arr['weight'] ) ? $arr['weight'] : '' ); ?> </span></label>
					<select name="weight[]">
						<?php $weight = isset( $arr['weight'] ) ? $arr['weight'] : ''; ?>
						<option <?php selected( $weight, 'normal' ); ?> value="normal"><?php echo esc_html__( 'Normal', 'product-blocks' ); ?></option>
						<option <?php selected( $weight, '100' ); ?> value="100"><?php echo esc_html__( '100', 'product-blocks' ); ?></option>
						<option <?php selected( $weight, '200' ); ?> value="200"><?php echo esc_html__( '200', 'product-blocks' ); ?></option>
						<option <?php selected( $weight, '300' ); ?> value="300"><?php echo esc_html__( '300', 'product-blocks' ); ?></option>
						<option <?php selected( $weight, '400' ); ?> value="400"><?php echo esc_html__( '400', 'product-blocks' ); ?></option>
						<option <?php selected( $weight, '500' ); ?> value="500"><?php echo esc_html__( '500', 'product-blocks' ); ?></option>
						<option <?php selected( $weight, '600' ); ?> value="600"><?php echo esc_html__( '600', 'product-blocks' ); ?></option>
						<option <?php selected( $weight, '700' ); ?> value="700"><?php echo esc_html__( '700', 'product-blocks' ); ?></option>
						<option <?php selected( $weight, '800' ); ?> value="800"><?php echo esc_html__( '800', 'product-blocks' ); ?></option>
						<option <?php selected( $weight, '900' ); ?> value="900"><?php echo esc_html__( '900', 'product-blocks' ); ?></option>
					</select>
				</div>
				<?php
					$styles = '';
				if ( ! empty( $arr ) ) {
					$font_face = $this->get_font_face( $arr, $font_name );
					echo wopb_function()->esc_inline( $font_face ); //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
					$styles = 'style="font-family: ' . esc_attr( $font_name ) . '; font-weight: ' . esc_attr( $arr['weight'] ) . ' "';
				}
				?>
				<span 
					<?php
						$styles_safe = wopb_function()->wp_kses_safe( $styles );
						echo $styles_safe;
					?>
										class="wopb-custom-font-preview"><?php echo esc_html__( 'The quick brown fox jumps over the lazy dog', 'product-blocks' ); ?>
				</span>
				<div class="wopb-custom-font-actions">
					<span class="wopb-custom-font-edit"><span class="dashicons dashicons-edit"></span><?php echo esc_html__( 'Edit', 'product-blocks' ); ?></span>
					<span class="wopb-custom-font-close"><span class="dashicons dashicons-no-alt"></span><?php echo esc_html__( 'Close', 'product-blocks' ); ?></span>
					<span class="wopb-custom-font-delete"><span class="dashicons dashicons-trash"></span><?php echo esc_html__( 'Delete', 'product-blocks' ); ?></span>
				</div>
			</div>
			<div class="wopb-custom-font-content">
				<div class="wopb-font-file-list">
					<label><?php echo esc_html__( 'WOFF File', 'product-blocks' ); ?></label>
					<input type="text" name="woff[]" value="<?php echo isset( $arr['woff'] ) ? esc_attr( $arr['woff'] ) : ''; ?>" placeholder="<?php echo esc_html__( 'The web open Font Format, Used by Modern Browsers', 'product-blocks' ); ?>"/>
					<span class="button wopb-font-upload" type="font/woff" extension="woff"><span class="dashicons dashicons-upload"></span><?php echo esc_html__( 'Upload', 'product-blocks' ); ?></span>
				</div>
				<div class="wopb-font-file-list">
					<label><?php echo esc_html__( 'WOFF2 File', 'product-blocks' ); ?></label>
					<input type="text" name="woff2[]" value="<?php echo isset( $arr['woff2'] ) ? esc_attr( $arr['woff2'] ) : ''; ?>" placeholder="<?php echo esc_html__( 'The web open Font Format 2, Used by Super Modern Browsers', 'product-blocks' ); ?>"/>
					<span class="button wopb-font-upload" type="font/woff2" extension="woff2"><span class="dashicons dashicons-upload"></span><?php echo esc_html__( 'Upload', 'product-blocks' ); ?></span>
				</div>
				<div class="wopb-font-file-list">
					<label><?php echo esc_html__( 'TTF File', 'product-blocks' ); ?></label>
					<input type="text" name="ttf[]" value="<?php echo isset( $arr['ttf'] ) ? esc_attr( $arr['ttf'] ) : ''; ?>" placeholder="<?php echo esc_html__( 'TrueType Fonts, Used for better supporting Safari, Android, iOS', 'product-blocks' ); ?>"/>
					<span class="button wopb-font-upload" type="font/ttf" extension="ttf"><span class="dashicons dashicons-upload"></span><?php echo esc_html__( 'Upload', 'product-blocks' ); ?></span>
				</div>
				<div class="wopb-font-file-list">
					<label><?php echo esc_html__( 'SVG File', 'product-blocks' ); ?></label>
					<input type="text" name="svg[]" value="<?php echo isset( $arr['svg'] ) ? esc_attr( $arr['svg'] ) : ''; ?>" placeholder="<?php echo esc_html__( 'SVG font allow SVG to be used as glyphs when displaying text, Used by Legacy iOS', 'product-blocks' ); ?>"/>
					<span class="button wopb-font-upload" type="image/svg+xml" extension="svg"><span class="dashicons dashicons-upload"></span><?php echo esc_html__( 'Upload', 'product-blocks' ); ?></span>
				</div>
				<div class="wopb-font-file-list">
					<label><?php echo esc_html__( 'EOT File', 'product-blocks' ); ?></label>
					<input type="text" name="eot[]" value="<?php echo isset( $arr['eot'] ) ? esc_attr( $arr['eot'] ) : ''; ?>" placeholder="<?php echo esc_html__( 'Embedded OpenType, Used by IE6-IE9 Browsers', 'product-blocks' ); ?>"/>
					<span class="button wopb-font-upload" type="application/vnd.ms-fontobject" extension="eot"><span class="dashicons dashicons-upload"></span><?php echo esc_html__( 'Upload', 'product-blocks' ); ?></span>
				</div>
			</div>
		</div>
		<?php
	}


	function custom_font_callback( $post ) {
		wp_nonce_field( 'font_meta_box', 'custom_font_nonce' );
		$settings = get_post_meta( $post->ID, '__font_settings', true );

		$this->set_data(); // Set Empty Data

		if ( is_array( $settings ) && ! empty( $settings ) ) {
			foreach ( $settings as $key => $val ) {
				$this->set_data( $val, $post->post_title );
			}
		}
		echo '<span class="button wopb-font-variation-action">' . esc_html__( 'Add Variation', 'product-blocks' ) . '</span>';
	}


	function metabox_save_data( $post_id ) {
		if ( ! isset( $_POST['custom_font_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['custom_font_nonce'] ) ), 'font_meta_box' ) ) {
			return;
		}

		$arr         = array();
		$font_weight = isset( $_POST['weight'] ) ? wopb_function()->rest_sanitize_params( $_POST['weight'] ) : array();
		if ( ! empty( $font_weight ) ) {
			foreach ( $font_weight as $i => $value ) {
				if ( isset( $font_weight[ $i ] ) &&
					( ! empty( $_POST['woff'][ $i ] ) ||
					! empty( $_POST['woff2'][ $i ] ) ||
					! empty( $_POST['ttf'][ $i ] ) ||
					! empty( $_POST['svg'][ $i ] ) ||
                    !empty($_POST['eot'][$i])) ) { //phpcs:ignore
							$temp           = array();
                            $temp['weight'] = isset($_POST['weight'][$i]) ? sanitize_text_field($_POST['weight'][$i]) : ''; // phpcs:ignore
                            $temp['woff'] = isset($_POST['woff'][$i]) ? sanitize_text_field($_POST['woff'][$i]) : ''; // phpcs:ignore
                            $temp['woff2'] = isset($_POST['woff2'][$i]) ? sanitize_text_field($_POST['woff2'][$i]) : ''; // phpcs:ignore
                            $temp['ttf'] = isset($_POST['ttf'][$i]) ? sanitize_text_field($_POST['ttf'][$i]) : ''; // phpcs:ignore
                            $temp['svg'] = isset($_POST['svg'][$i]) ? sanitize_text_field($_POST['svg'][$i]) : ''; // phpcs:ignore
                            $temp['eot'] = isset($_POST['eot'][$i]) ? sanitize_text_field($_POST['eot'][$i]) : ''; // phpcs:ignore
							$arr[]          = $temp;
				}
			}
			update_post_meta( $post_id, '__font_settings', $arr );
		}
	}

	// Templates Post Type Register
	public function custom_font_post_type_callback() {
		$labels = array(
			'name'               => _x( 'Custom Fonts', 'Custom Font', 'product-blocks' ),
			'singular_name'      => _x( 'Saved Custom Font', 'Custom Font', 'product-blocks' ),
			'menu_name'          => __( 'Saved Custom Font', 'product-blocks' ),
			'parent_item_colon'  => __( 'Parent Custom Font', 'product-blocks' ),
			'all_items'          => __( 'Saved Custom Font', 'product-blocks' ),
			'view_item'          => __( 'View Custom Font', 'product-blocks' ),
			'add_new_item'       => __( 'Add New', 'product-blocks' ),
			'add_new'            => __( 'Add New', 'product-blocks' ),
			'edit_item'          => __( 'Edit Custom Font', 'product-blocks' ),
			'update_item'        => __( 'Update Custom Font', 'product-blocks' ),
			'search_items'       => __( 'Search Custom Font', 'product-blocks' ),
			'not_found'          => __( 'No Custom Font Found', 'product-blocks' ),
			'not_found_in_trash' => __( 'Not Custom Font found in Trash', 'product-blocks' ),
		);
		$args   = array(
			'labels'              => $labels,
			'show_in_rest'        => false,
			'supports'            => array( 'title' ),
			'hierarchical'        => false,
			'public'              => false,
			'rewrite'             => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'capability_type'     => 'page',
		);
		register_post_type( 'wopb_custom_font', $args );
	}
}
