<?php
/**
 * Quickview Addons Core.
 *
 * @package WOPB\Quickview
 * @since v.1.1.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * Quickview class.
 */
class Quickview {


	/**
	 * Setup class.
	 *
	 * @since v.1.1.0
	 */

	private $is_mobile;

	private $allowed_html_tag;

	public function __construct() {
		$position_filters       = $this->button_position_filters();
		$quick_view_position    = wopb_function()->get_setting( 'quick_view_position' );
		$this->is_mobile        = wp_is_mobile();
		$this->allowed_html_tag = wopb_function()->allowed_html_tags();

		add_action( 'wc_ajax_wopb_quickview', array( $this, 'wopb_quickview_callback' ) );
		add_action( 'wp_ajax_nopriv_wopb_quickview', array( $this, 'wopb_quickview_callback' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_quickview_scripts' ) );

		// Quick view in default woocommerce shop pages
		if ( isset( $position_filters[ $quick_view_position ] ) ) {
			add_filter( $position_filters[ $quick_view_position ], array( $this, 'quick_view_in_cart' ), 20, 2 );
		}
		if ( $quick_view_position == 'shortcode' ) {
			add_shortcode( 'wopb_quick_view_button', array( $this, 'quick_view_button' ) );
		}

		add_filter(
			'wopb_active_modal',
			function ( array $loaders ) {
				if ( ! in_array( $modal_loader = wopb_function()->get_setting( 'quick_view_loader' ), $loaders ) ) {
					$loaders[] = $modal_loader;
				}
				return $loaders;
			}
		);
		add_action( 'wopb_save_settings', array( $this, 'generate_css' ), 10, 1 ); // CSS Generator

		add_filter( 'wopb_grid_quickview', array( $this, 'get_grid_quick_view' ), 10, 4 );
	}


	/**
	 * QuickView HTML
	 *
	 * @since v.4.0.0
	 * @return STRING
	 */
	public function get_grid_quick_view( $output, $post, $post_id, $position = '' ) {
		$modal_wrapper_class  = 'wopb-quick-view-wrapper wopb-layout-' . wopb_function()->get_setting( 'quick_view_layout' );
		$click_action_setting = wopb_function()->get_setting( 'quick_view_click_action' );
		$modal_wrapper_class .= ( $click_action_setting == 'right_sidebar' || $click_action_setting == 'left_sidebar' ) ? ' wopb-sidebar-wrap wopb-' . $click_action_setting : '';
		$quick_view_text      = wopb_function()->get_setting( 'quick_view_text' );

		$output         .= '<div
            class="wopb-quickview-btn wopb_meta_svg_con"
            data-list="' . esc_attr( implode( ',', wopb_function()->get_ids( $post ) ) ) . '"
            data-postid="' . esc_attr( $post_id ) . '"
            data-modal_wrapper_class="' . esc_attr( $modal_wrapper_class ) . '"
            data-open-animation="wopb-' . esc_attr( wopb_function()->get_setting( 'quick_view_open_animation' ) ) . '"
            data-close-animation="wopb-' . esc_attr( wopb_function()->get_setting( 'quick_view_close_animation' ) ) . '"
            data-modal-loader="' . esc_attr( wopb_function()->get_setting( 'quick_view_loader' ) ) . '"
            defaultWooPage="">';
			$output     .= '<span class="wopb-tooltip-text">';
				$output .= wopb_function()->svg_icon( wopb_function()->get_setting( 'quick_view_button_icon' ) );
		if ( $quick_view_text ) {
			$tooltipPosition = $position ? sanitize_html_class( $position ) : 'left';
			$output         .= '<span class="wopb-tooltip-text-' . esc_attr( $tooltipPosition ) . '">';
				$output     .= esc_html( $quick_view_text );
			$output         .= '</span>';
		}
			$output .= '</span>';
		$output     .= '</div>';

		return $output;
	}


	/**
	 * Quickview Addons Initial Setup Action
	 *
	 * @return NULL
	 * @since v.2.1.8
	 */
	public function add_quickview_scripts() {
		wp_enqueue_script( 'wc-add-to-cart-variation' );
		wp_enqueue_script( 'wc-single-product' );
		wp_enqueue_script( 'flexslider' );

		wp_enqueue_style( 'wopb-modal-css', WOPB_URL . 'assets/css/modal.min.css', array(), WOPB_VER );
		wp_enqueue_style( 'wopb-animation-css', WOPB_URL . 'assets/css/animation.min.css', array(), WOPB_VER );
		wp_enqueue_style( 'wopb-quickview-style', WOPB_URL . 'addons/quick_view/css/quick_view.min.css', array(), WOPB_VER );
		wp_enqueue_style( 'wopb-slick-style', WOPB_URL . 'assets/css/slick.css', array(), WOPB_VER );
		wp_enqueue_style( 'wopb-slick-theme-style', WOPB_URL . 'assets/css/slick-theme.css', array(), WOPB_VER );
		wp_enqueue_script( 'wopb-slick-script', WOPB_URL . 'assets/js/slick.min.js', array( 'jquery' ), WOPB_VER, true );
		wp_enqueue_script( 'wopb-quickview', WOPB_URL . 'addons/quick_view/js/quickview.js', array( 'jquery', 'wp-api-fetch' ), WOPB_VER, true );

		wp_localize_script(
			'wopb-quickview',
			'wopb_quickview',
			array(
				'ajax'                    => admin_url( 'admin-ajax.php' ),
				'security'                => wp_create_nonce( 'wopb-nonce' ),
				'isVariationSwitchActive' => wopb_function()->get_setting( 'wopb_variation_swatches' ),
			)
		);
	}


	/**
	 * Quickview Addons Initial Setup Action
	 *
	 * @return NULL
	 * @since v.1.1.0
	 */
	public function initial_setup() {
		$settings = wopb_function()->get_setting();
		// Set Default Value
		$initial_data = array(
			'quick_view_mobile_enable'        => '',
			'quick_view_shop_enable'          => 'yes',
			'quick_view_archive_enable'       => 'yes',
			'quick_view_click_action'         => 'popup',
			'quick_view_loader'               => 'loader_1',
			'quick_view_open_animation'       => 'zoom_in',
			'quick_view_close_animation'      => 'zoom_out',
			'quick_view_product_navigation'   => 'yes',

			'quick_view_text'                 => __( 'Quick View', 'product-blocks' ),
			'quick_view_position'             => 'bottom_cart',
			'quick_view_button_icon_enable'   => 'yes',
			'quick_view_button_icon'          => 'quick_view_3',
			'quick_view_button_icon_position' => 'before_text',

			'quick_view_contents'             => $this->quick_view_contents( 'default' ),
			'quick_view_image_type'           => 'image_with_gallery',
			'quick_view_image_gallery'        => 'bottom',
			'quick_view_image_pagination'     => 'line',
			'quick_view_image_effect'         => 'yes',
			'quick_view_image_effect_type'    => 'zoom',
			'quick_view_image_hover_icon'     => 'zoom_1',

			'quick_view_buy_now'              => 'yes',
			'quick_view_thumbnail_freeze'     => 'yes',
			'quick_view_close_button'         => 'yes',
			'quick_view_close_add_to_cart'    => 'yes',

			'quick_view_layout'               => 1,
			'quick_view_preset'               => '1',

			'quick_view_btn_typo'             => array(
				'size'        => 14,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => 'rgba(7, 7, 7, 1)',
				'hover_color' => 'rgba(255, 23, 107, 1)',
			),
			'quick_view_btn_bg'               => array(
				'bg'       => '',
				'hover_bg' => '',
			),
			'quick_view_btn_padding'          => array(
				'top'    => 0,
				'bottom' => 0,
				'left'   => 0,
				'right'  => 0,
			),
			'quick_view_btn_border'           => array(
				'border' => 0,
				'color'  => '',
			),
			'quick_view_btn_radius'           => 0,
			'quick_view_icon_size'            => 16,
			'quick_view_btn_align'            => '',

			'quick_view_modal_bg'             => '#FFFFFF',
			'quick_view_title_typo'           => array(
				'size'        => 14,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => '#070707',
				'hover_color' => '',
			),
			'quick_view_content_inner_gap'    => 15,
			'quick_view_thumbnail_ratio'      => 'default',
			'quick_view_thumbnail_height'     => 350,
			'quick_view_thumbnail_width'      => 400,
			'quick_view_modal_btn_typo'       => array(
				'size'        => 14,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => '#ffffff',
				'hover_color' => '#ff176b',
			),
			'quick_view_modal_btn_border'     => array(
				'border' => 1,
				'color'  => '#ff176b',
			),
			'quick_view_modal_btn_bg'         => array(
				'bg'       => '#ff176b',
				'hover_bg' => '#ffffff',
			),
		);
		foreach ( $initial_data as $key => $val ) {
			if ( ! isset( $settings[ $key ] ) ) {
				wopb_function()->set_setting( $key, $val );
			}
		}
		$this->generate_css( 'wopb_quickview' );
	}

	/**
	 * Quickview Add Action Callback.
	 *
	 * @return null
	 * @since v.1.1.0
	 */
	public function wopb_quickview_callback() {
		if ( empty( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return;
		}
		$params            = array(
            'post_id'   => isset( $_POST['postid'] ) ? sanitize_text_field( $_POST['postid'] ) : '', // phpcs:ignore
            'post_list' => isset( $_POST['postList'] ) ? sanitize_text_field( $_POST['postList'] ) : '' // phpcs:ignore
		);
		$image_effect      = wopb_function()->get_setting( 'quick_view_image_effect' );
		$image_effect_type = wopb_function()->get_setting( 'quick_view_image_effect_type' );
		?>
		<div class="wopb-modal-header">
			<?php if ( wopb_function()->get_setting( 'quick_view_close_button' ) == 'yes' ) { ?>
				<a class="wopb-modal-close">
                    <?php echo wp_kses( wopb_function()->svg_icon( 'close' ), $this->allowed_html_tag ); // phpcs:ignore ?> 
				</a>
			<?php } ?>
		</div>
		<div
			class="wopb-modal-body"
			data-outside_click="yes"
			data-product_id="<?php echo esc_attr( $params['post_id'] ); ?>"
			data-modal_close_after_cart="<?php echo esc_attr( wopb_function()->get_setting( 'quick_view_close_add_to_cart' ) ); ?>"
		>
			<div class="woocommerce-message wopb-d-none" role="alert"></div>
			<?php $this->quick_view_content( $params ); ?>
		</div>
		<?php if ( $image_effect == 'yes' && $image_effect_type == 'popup' ) { ?>
			<div class="wopb-quick-view-zoom wopb-zoom-2 wopb-d-none">
				<a class="wopb-zoom-close wopb-modal-close-icon"></a>
				<img alt="Zoom Image" src="">
			</div>
			<?php
		}
		if ( wopb_function()->get_setting( 'quick_view_product_navigation' ) == 'yes' && $params['post_list'] ) {
			$this->quick_view_navigation( $params ); //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		die();
	}

	/**
	 * Quick View Navigation
	 *
	 * @since v.3.1.5
	 * @param $params
	 * @return null
	 */
	public function quick_view_navigation( $params ) {
		?>
		<div class="wopb-quick-view-navigation wopb-d-none">
			<?php
			$p_id     = explode( ',', $params['post_list'] );
			$search   = array_search( $params['post_id'], $p_id );
			$previous = isset( $p_id[ $search - 1 ] ) ? $p_id[ $search - 1 ] : '';
			$next     = isset( $p_id[ $search + 1 ] ) ? $p_id[ $search + 1 ] : '';

			foreach ( array( 'previous', 'next' ) as $key => $type ) {
				$thumbnail = get_post_thumbnail_id( ${$type} );
				if ( ${$type} ) {
					?>
					<div
						class="wopb-nav-arrow wopb-quick-view-<?php echo esc_attr( $type ); ?>"
						data-list="<?php echo esc_attr( $params['post_list'] ); ?>"
						data-postid="<?php echo esc_attr( ${$type} ); ?>"
						data-modal-loader="<?php echo esc_attr( wopb_function()->get_setting( 'quick_view_loader' ) ); ?>">
						<span class="wopb-nav-icon">
							<?php echo wp_kses( wopb_function()->svg_icon( $type == 'previous' ? 'leftAngle2' : 'rightAngle2' ), $this->allowed_html_tag ); ?>
						</span>
						<div class="wopb-quick-view-btn-image">
							<?php
							if ( $thumbnail ) {
								$t_img = wp_get_attachment_image_src( $thumbnail, 'thumbnail' );
								if ( isset( $t_img[0] ) ) {
									?>
									<img src="<?php echo esc_url( $t_img[0] ); ?>" />
								<?php } ?>
							<?php } ?>
							<span class="wopb-nav-title"><?php echo esc_html( get_the_title( ${$type} ) ); ?></span>
						</div>
					</div>
				<?php } ?>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Quick View Contents
	 *
	 * @since v.3.1.5
	 * @param $params
	 * @return null
	 */
	public function quick_view_content( $params = array() ) {
		global $post;
		$post_id = $params['post_id'];
		$post    = get_post( $post_id, OBJECT );
		setup_postdata( $post );
		$product = wc_get_product( $post_id );

		$view_contents     = wopb_function()->get_setting( 'quick_view_contents' );
		$content_keys      = array_column( $view_contents, 'key' );
		$quick_view_layout = wopb_function()->get_setting( 'quick_view_layout' );
		$content_class     = ' wopb-' . $product->get_type() . '-product';

		$rating_count = $product->get_rating_count();
		$review_count = $product->get_review_count();
		$average      = $product->get_average_rating();

		if ( $quick_view_layout == 2 ) {
			?>
			<div class="wopb-product-info">
				<?php
					woocommerce_template_single_title();
					echo '<div class="woocommerce-product-rating">';
						echo wc_get_rating_html( $average, $rating_count );
						echo '<span class="woocommerce-review-link">';
							echo '(<span class="count">' . esc_html( $review_count ) . '</span> ' . esc_html__( 'customer review', 'product-blocks' ) . ')';
						echo '</span>';
					echo '</div>';
				?>
			</div>
		<?php } ?>

		<div class="wopb-main-section">
			<?php
			if ( in_array( 'image', $content_keys ) ) {
				$this->quick_view_image( $product );
			}
			?>
			<div class="wopb-quick-view-content <?php echo esc_attr( $content_class ); ?>">
				<?php
				foreach ( $view_contents as $content ) {
					switch ( $content['key'] ) {
						case 'title':
							if ( $quick_view_layout != 2 ) {
								woocommerce_template_single_title();
							}
							break;
						case 'rating':
							if ( in_array( $quick_view_layout, array( 1, 4, 5 ) ) ) {
								woocommerce_template_single_rating();
							} elseif ( $quick_view_layout == 3 ) {
								?>
								<div class="wopb-rating-info">
									<?php
									woocommerce_template_single_rating();
									$this->stock_status( $product ); //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</div>
								<?php
							}
							break;
						case 'price':
							if ( $product->get_price() ) {
								$save_percentage = ( $product->get_sale_price() && $product->get_regular_price() ) ? round( ( $product->get_regular_price() - $product->get_sale_price() ) / $product->get_regular_price() * 100 ) . '%' : '';
								echo '<div>';
									woocommerce_template_single_price();
								if ( $save_percentage ) {
									echo '<span class="wopb-discount-percentage">' . esc_html( $save_percentage ) . '</span>';
								}
									echo '</div>';
							}
							break;
						case 'description':
							woocommerce_template_single_excerpt();
							break;
						case 'stock_status':
							if ( $quick_view_layout != 3 ) {
								$this->stock_status( $product );
							}
							break;
						case 'add_to_cart':
							echo '<div>';
								ob_start();
									add_action( 'woocommerce_before_quantity_input_field', array( $this, 'quick_view_before_add_to_cart_quantity' ) );
									add_action( 'woocommerce_after_quantity_input_field', array( $this, 'quick_view_after_add_to_cart_quantity' ) );
									woocommerce_template_single_add_to_cart();
								echo ob_get_clean(); // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
								echo '<div class="wopb-cart-bottom">' . apply_filters( 'wopb_quick_view_bottom_cart', '', $product->get_id() ) . '</div>';
							if ( wopb_function()->get_setting( 'quick_view_buy_now' ) && ! $product->is_type( 'external' ) ) {
								ob_start();
								$this->quick_buy_now_button();
								$quick_buy_now_safe = wopb_function()->wp_kses_safe( ob_get_clean() );
								echo $quick_buy_now_safe; // phpcs:disable
								}
                                echo '</div>';
                                break;
						case 'meta':
							woocommerce_template_single_meta();
							break;
						case 'view_details':
							echo '<a class="wopb-product-details" href="' . esc_url( $product->get_permalink() ) . '">' . esc_html__( "View Full Product Details", "product-blocks" ) . '</a>';
							break;
						case 'social_share':
							$this->social_share( $product );
							break;
						case 'campaign_count':
                            case 'delivery_info':
                            case 'payment_info':
							break;
						default;
							break;
                        }
                    }
                ?>
            </div>
        </div>
        <?php
        wp_reset_postdata();
    }

    /**
     * Product Stock Status
     *
     * @param $product
     * @return null
     * @since v.3.1.5
     */
    public function stock_status( $product ) { ?>
        <div class="wopb-product-stock-status">
            <?php
            if ( $product->get_stock_status() == 'instock' ) {
                echo '<span class="wopb-product-in-stock">' . esc_html__( "In Stock", "product-blocks" ) . '</span>';
            } elseif ( $product->get_stock_status() == 'outofstock' ) {
                echo '<span class="wopb-product-out-stock">' . esc_html__( "Out of stock", "product-blocks" ) . '</span>';
            }
            ?>
        </div>
    <?php
    }

    /**
     * Quick View Buy Now Button
     *
     * @since v.3.1.5
     * @return null
     */
    public function quick_buy_now_button() {
        global $product;
        ?>
        <div>
            <a
                class="wopb-quickview-buy-btn single_add_to_cart_button button alt"
                value="<?php echo esc_attr( $product->get_ID() ); ?>"
                data-cart_type="buy_now"
                data-redirect="<?php echo esc_url( wc_get_checkout_url() ); ?>"
            >
                <?php echo esc_html__( 'Buy Now', 'product-blocks' ); ?>
            </a>
        </div>
    <?php
    }

    /**
     * Quick View Image
     *
     * @since v.3.1.5
     * @param $params
     * @return null
     */
    public function quick_view_image( $product ) {
        $image_effect = wopb_function()->get_setting('quick_view_image_effect');
        $image_effect_type = wopb_function()->get_setting('quick_view_image_effect_type');
        $image_wrapper_class = wopb_function()->get_setting('quick_view_thumbnail_freeze') == 'yes' &&  ! $this->is_mobile ? ' wopb-image-sticky' : '';
?>
        <div class="wopb-quick-view-image <?php echo esc_attr($image_wrapper_class); ?>">
            <div class="<?php echo esc_attr($image_wrapper_class); ?>">
            <?php
                if (
                    $image_effect == 'yes' &&
                    $image_effect_type == 'zoom' &&
                    ! $this->is_mobile
                ) {
            ?>
                <div class="wopb-zoom-image-outer wopb-zoom-1">
                    <div class="wopb-zoom-image-inner">
                        <img alt="Zoom Image">
                    </div>
                </div>
            <?php
                }
                $slick_html = function() use( $product ) {
                    $image_type = wopb_function()->get_setting('quick_view_image_type');
                    $quick_image_gallery = 'wopb-' . wopb_function()->get_setting('quick_view_image_gallery');
                    $quick_image_pagination = wopb_function()->get_setting('quick_view_image_pagination');
                    $save_percentage = ($product->get_sale_price() && $product->get_regular_price()) ? round(($product->get_regular_price() - $product->get_sale_price()) / $product->get_regular_price() * 100) . '%' : '';
                    $attachment = $product->get_image_id();
                    $gallery    = $product->get_gallery_image_ids();
                    $all_id = [];
                    if ( !empty( $attachment ) ) {
                        $all_id[] = $attachment;
                    }
                    if ( !empty($gallery) ) {
                        $all_id = array_merge($all_id, $gallery);
                    }
                    $image_full = $image_thumb = '';
                    $gallery_thumbnail = wc_get_image_size( 'gallery_thumbnail' );
                    $thumbnail_size = apply_filters( 'woocommerce_gallery_thumbnail_size', array( $gallery_thumbnail['width'], $gallery_thumbnail['height'] ) );
                    $full_size = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
                    foreach ( $all_id as $key => $attachment_id ) {
                        $thumbnail_src = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );
                        $full_src = wp_get_attachment_image_src( $attachment_id, $full_size );
                        $alt_text = trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
                        $image_full .= '<div>';
                            $image_full .= '<a>';
                                $image_full .= '<img class="wopb-main-image" src="'.esc_url($full_src[0]).'" alt="'.esc_attr($alt_text).'" data-width="'.esc_attr($full_src[1]).'" data-height="'.esc_attr($full_src[2]).'"/>';
                            $image_full .= '</a>';
                        $image_full .= '</div>';
                        $image_thumb .= '<div>';
                            $image_thumb .= '<img src="'.esc_url($thumbnail_src[0]).'" alt="'.esc_attr($alt_text).'" />';
                        $image_thumb .= '</div>';
                    }
                    $slider_attr = '';
                    $image_class = $image_type;
                    $image_class .= ' wopb-' . wopb_function()->get_setting('quick_view_thumbnail_ratio');
                    if ( count( $all_id) > 1 ) {
                        $nav_html = '';
                        $nav_class = '';
                        $dot = '';
                        $position = '';
                        if( $image_type == 'image_with_gallery' ) {
                            $image_class .= $nav_class .= ' ' . $quick_image_gallery;
                            $position = wopb_function()->get_setting('quick_view_image_gallery');
                            if( $image_thumb ) {
                                $nav_html = wp_kses_post( $image_thumb );
                            }
                        }elseif( $image_type == 'image_with_pagination' ) {
                            $image_class .= $nav_class .= ' ' . $quick_image_pagination;
                            $dot = $quick_image_pagination == 'line' || $quick_image_pagination == 'dot' ? true : $dot;
                        }
                        $slider_attr = wc_implode_html_attributes(
                            array(
                                'data-arrow'  => true,
                                'data-dots'  => $dot,
                            )
                        );
                    }
            ?>
                <div class="wopb-quick-view-gallery <?php echo esc_attr( $image_class ) ?>">
                    <div class="wopb-thumbnail">
                        <div
                            class="<?php echo count( $all_id) > 1 ? 'wopb-quick-slider' : '' ?>"
                            <?php echo wp_kses_post( $slider_attr ) ?>
                        >
                            <?php if( $image_full) { echo wp_kses_post( $image_full );}  ?>
                        </div>
                        <?php if ( $save_percentage ) { ?>
                            <span class="wopb-quick-view-sale">-
                                <span><?php echo esc_html__('Sale!', 'product-blocks'); ?></span>
                            </span>
                        <?php } ?>
                    </div>
                    <?php
                        if ( isset($nav_html) && $nav_html ) {
                    ?>
                        <div
                            class="wopb-quick-slider-nav<?php echo esc_attr( $nav_class ) ?>"
                            data-arrow="true"
                            data-collg="4"
                            data-colmd="4"
                            data-colsm="3"
                            data-colxs="3"
                            data-position=<?php echo esc_attr($position); ?>
                        >
					<?php 
						$nav_html_safe = wopb_function()->wp_kses_safe( $nav_html );
						echo $nav_html_safe; // phpcs:ignore
						?>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php
        };
        $gallery_classes = function($classes) {
            return $classes;
        };
        add_filter( 'wc_get_template', function ( $template, $template_name, $args, $template_path, $default_path ) use( $slick_html ) {
            if ( 'single-product/product-image.php' == $template_name ) {
                return WC_ABSPATH . 'templates/single-product/product-image.php';
            }
            return $template;
        }, 20, 5 );
        add_action('woocommerce_product_thumbnails', $slick_html);
        add_filter('woocommerce_single_product_image_gallery_classes', $gallery_classes);
        add_filter('woocommerce_single_product_image_thumbnail_html', function () {
            return '';
        }, 10, 2);
        echo woocommerce_show_product_images(); //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        remove_filter('woocommerce_single_product_image_gallery_classes', $gallery_classes);
        remove_filter('woocommerce_single_product_image_thumbnail_html', function () {
            return '';
        }, 10, 2);
        remove_action('woocommerce_product_thumbnails', $slick_html);
        ?>
        </div>
    <?php
    }

    /**
     * Before Quantity
     *
     * @since v.3.1.5
     * @return null
     */
    public function quick_view_before_add_to_cart_quantity() {
        echo '<span class="wopb-add-to-cart-minus">' . wp_kses( wopb_function()->svg_icon( 'minus_2' ), wopb_function()->allowed_html_tags() ) . '</span>';
    }

    /**
     * After Quantity
     *
     * @since v.3.1.5
     * @param $params
     * @return null
     */
    public function quick_view_after_add_to_cart_quantity() {
        echo '<span class="wopb-add-to-cart-plus">' . wp_kses( wopb_function()->svg_icon( 'plus_3' ), wopb_function()->allowed_html_tags() ) . '</span>';
    }

    /**
     * Product social share icons
     *
     * @since v.3.1.5
     * @param $product
     * @return null
     *
     */
    public function social_share( $product ) {
        $link = $product->get_permalink();
        ?>
        <div class="wopb-product-social-share">
            <span><?php echo esc_html__( 'Social Share','product-blocks' ); ?></span>
            <a href="<?php echo esc_url( 'http://www.facebook.com/sharer.php?u=' . $link ); ?>" target="_blank" class="wopb-share-facebook">
                <?php echo wp_kses( wopb_function()->svg_icon( 'facebook' ), $this->allowed_html_tag ); ?>
            </a>
            <a href="<?php echo esc_url( 'https://www.linkedin.com/sharing/share-offsite/?url=' . $link ); ?>" target="_blank" class="wopb-share-linkedin">
                <?php echo wp_kses( wopb_function()->svg_icon( 'linkedin' ), $this->allowed_html_tag ); ?>
            </a>
            <a href="<?php echo esc_url( 'http://twitter.com/share?url=' . $link ); ?>" target="_blank" class="wopb-share-twitter">
                <?php echo wp_kses( wopb_function()->svg_icon( 'twitter' ), $this->allowed_html_tag ); ?>
            </a>
            <a href="<?php echo esc_url( 'http://pinterest.com/pin/create/link/?url=' . $link ); ?>" target="_blank" class="wopb-share-pinterest">
                <?php echo wp_kses( wopb_function()->svg_icon( 'pinterest' ), $this->allowed_html_tag ); ?>
            </a>
            <a href="<?php echo esc_url( 'https://web.skype.com/share?url=' . $link ); ?>" target="_blank" class="wopb-share-skype">
                <?php echo wp_kses( wopb_function()->svg_icon( 'skype' ), $this->allowed_html_tag ); ?>
            </a>
        </div>
    <?php
    }

    /**
     * Quick View Button
     *
     * @return null
     * @since v.3.1.5
     */
    public function quick_view_button() {
        global $wp_query;
        $params = array(
            'source'    => 'default',
            'post'      => new \WP_Query( $wp_query->query_vars ),
            'post_id'   => get_the_ID(),
        );
        $quick_view_safe = wopb_function()->wp_kses_safe( $this->get_quick_view($params));
        echo $quick_view_safe; // phpcs:ignore
    }

    /**
	 * QuickView HTML
     *
     * @since v.1.1.0
	 * @return STRING
	 */
    public function get_quick_view( $params = [] ) {
        if ( wopb_function()->get_setting( 'quickview_mobile_disable' ) == 'yes' && wp_is_mobile() ) {} else {
            $output = '';

            $modal_wrapper_class        = 'wopb-quick-view-wrapper wopb-layout-' . wopb_function()->get_setting( 'quick_view_layout' );
            $click_action_setting       = wopb_function()->get_setting( 'quick_view_click_action' );
            $quick_view_icon            = wopb_function()->svg_icon( wopb_function()->get_setting( 'quick_view_button_icon' ) );
            $quick_view_icon_position   = wopb_function()->get_setting( 'quick_view_button_icon_position' );
            $button_icon_enable         = isset( $params['icon'] ) ? $params['icon'] : wopb_function()->get_setting( 'quick_view_button_icon_enable' );
            $button_class               = 'wopb-quickview-btn wopb-quick-addon-btn';

            $default_woo            = ( isset( $params['source'] ) && $params['source'] == 'default' ) ? 'yes' : '';
            $before_text            = ( $button_icon_enable == 'yes' && $quick_view_icon_position == 'before_text' ) ? $quick_view_icon : '';
            $after_text             = ( $button_icon_enable == 'yes' && $quick_view_icon_position == 'after_text' ) ? $quick_view_icon : '';
            $quick_view_text        = wopb_function()->get_setting( 'quick_view_text' );
            $modal_wrapper_class    .= ( $click_action_setting == 'right_sidebar' ||  $click_action_setting == 'left_sidebar' ) ? ' wopb-sidebar-wrap wopb-' . $click_action_setting : '';

            $output .= '<span class="wopb-quick-btn-wrap">';
                $output .= '<span
                    class="' . esc_attr( $button_class ) . '"
                    data-list="' . esc_attr( implode( ',', wopb_function()->get_ids( $params['post'] ) ) ) . '"
                    data-postid="' . esc_attr( $params['post_id'] ) . '"
                    data-modal_wrapper_class="' . esc_attr( $modal_wrapper_class ) . '"
                    data-open-animation="wopb-' . esc_attr( wopb_function()->get_setting( 'quick_view_open_animation' ) ) . '"
                    data-close-animation="wopb-' . esc_attr( wopb_function()->get_setting( 'quick_view_close_animation' ) ) . '"
                    data-modal-loader="' . esc_attr( wopb_function()->get_setting( 'quick_view_loader' ) ) . '"
                    defaultWooPage ="' . esc_attr( $default_woo ) . '">';

                    if ( isset( $params['tooltip'] ) && $params['tooltip'] ) {
				$output .= '<span class="wopb-tooltip-text">';
			$output .= $quick_view_icon;
			if ( $quick_view_text ) {
							$output .= '<span class="' . ( in_array( $params['layout'] , $params['position'] ) ? 'wopb-tooltip-text-left' : 'wopb-tooltip-text-top' ) .'">';
								$output .= esc_html( $quick_view_text );
							$output .= '</span>';
					}
				$output .= '</span>';
                    } else {
				$output .= $before_text . $quick_view_text . $after_text;
                    }
                $output .= '</span>';
            $output .= '</span>';

            return $output;
        }
    }

    /**
     * Quick View Button in Default Shop Page
     *
     * @param $content
     * @param $args
     * @return null
     * @since v.3.1.5
     */
    public function quick_view_in_cart( $content, $args ) {
        if ( ! wopb_function()->is_builder() &&
            (
                ( wopb_function()->get_setting( 'quick_view_shop_enable' ) == 'yes' && is_shop() )  ||
                ( wopb_function()->get_setting( 'quick_view_archive_enable' ) == 'yes' && is_archive() )
            ) ) {
            global $product;
            ob_start();
            $this->quick_view_button();
            return $content . ob_get_clean();
        }
        return $content;
    }

    /**
     * Quick Button Position Filters
     *
     * @since v.3.1.5
     * @return array
     */
    public function button_position_filters() {
        return array(
           'top_cart'       => 'wopb_top_add_to_cart_loop',
           'before_cart'    => 'wopb_before_add_to_cart_loop',
           'after_cart'     => 'wopb_after_add_to_cart_loop',
           'bottom_cart'    => 'wopb_bottom_add_to_cart_loop',
           'above_image'    => 'wopb_before_shop_loop_title',
        );
    }

    /**
     * Quick View Content Item
     *
     * @since v.3.1.5
     * @param $default
     * @return array
     */
    public function quick_view_contents( $default = '' ) {
        $default_options = array(
            ['key' => 'image','label' => __( 'Image','product-blocks' )],
            ['key' => 'title','label' => __( 'Title','product-blocks' )],
            ['key' => 'rating','label' => __( 'Rating','product-blocks' )],
            ['key' => 'price','label' => __( 'Price','product-blocks' )],
            ['key' => 'description','label' => __( 'Description','product-blocks' )],
            ['key' => 'stock_status','label' => __( 'Stock Status','product-blocks' )],
            ['key' => 'add_to_cart','label' => __( 'Add To Cart','product-blocks' )],
            ['key' => 'meta','label' => __( 'Meta','product-blocks' )],
            ['key' => 'view_details','label' => __( 'View Details','product-blocks' )],
            ['key' => 'social_share','label' => __( 'Social Share','product-blocks' )],
        );
        $options = array(
            ['key' => '','label' => __( 'Select Column','product-blocks' )],
            ...$default_options,
        );
        return $default && $default == 'default' ? $default_options : $options;
    }

    /**
     * Dynamic CSS
     *
     * @since v.3.1.5
     * @return string
     */
    public function generate_css( $key ) {
        if ( $key == 'wopb_quickview' ) {
            $settings = wopb_function()->get_setting();
            $btn_style = array_merge( $settings['quick_view_btn_typo'], $settings['quick_view_btn_bg'] );
            $modal_btn_style = array_merge( $settings['quick_view_modal_btn_typo'], $settings['quick_view_modal_btn_bg'] );

            $css = '';
            if( ! empty( $settings['quick_view_btn_align'] ) ) {
                $css .= '.wopb-quick-btn-wrap{';
                    $css .= 'display: inline-flex;';
                    $css .= 'justify-content: ' . $settings['quick_view_btn_align'] . ';';
                $css .= '}';
            }
            $css .= '.wopb-quickview-btn.wopb-quick-addon-btn {';
                $css .= wopb_function()->convert_css('general', $btn_style);
                $css .= wopb_function()->convert_css('border', $settings['quick_view_btn_border']);
                $css .= wopb_function()->convert_css('radius', $settings['quick_view_btn_radius']);
                $css .= 'padding: ' . wopb_function()->convert_css('dimension', $settings['quick_view_btn_padding']) . ';';
            $css .= '}';
            $css .= '.wopb-quickview-btn.wopb-quick-addon-btn:hover {';
                $css .= wopb_function()->convert_css('hover', $btn_style);
            $css .= '}';
            $css .= '.wopb-quickview-btn.wopb-quick-addon-btn svg{';
                $css .= 'height: ' . ( ! empty( $settings['quick_view_icon_size'] ) ? $settings['quick_view_icon_size'] : '16' ) . 'px;';
                $css .= 'width: ' . ( ! empty( $settings['quick_view_icon_size'] ) ? $settings['quick_view_icon_size'] : '16' ) . 'px;';
            $css .= '}';

            $css .= 'body .wopb-quick-view-wrapper .wopb-modal-content,';
            $css .= 'body .wopb-popup-body:has(.wopb-quick-view-wrapper),';
            $css .= 'body .wopb-quick-view-wrapper .wopb-zoom-image-outer.wopb-zoom-1,';
            $css .= 'body .wopb-quick-view-image div.wopb-image-sticky,';
            $css .= 'body .wopb-quick-view-image.wopb-image-sticky {';
                $css .= ! empty( $settings['quick_view_modal_bg'] ) ? ( 'background-color: ' . $settings['quick_view_modal_bg'] . ';' ) : '';
            $css .= '}';

            $css .= '.wopb-quick-view-wrapper .product_title {';
                $css .= wopb_function()->convert_css('general', $settings['quick_view_title_typo']);
            $css .= '}';
            $css .= '.wopb-quick-view-wrapper .product_title:hover {';
                $css .= wopb_function()->convert_css('hover', $settings['quick_view_title_typo']);
            $css .= '}';

            if ( ! empty( $settings['quick_view_content_inner_gap'] ) ) {
                $css .= '.wopb-quick-view-wrapper .wopb-quick-view-content,';
                $css .= '.wopb-quick-view-wrapper .wopb-product-info {';
                    $css .= 'gap: ' . $settings['quick_view_content_inner_gap'] . 'px;';
                $css .= '}';

                $css .= '.wopb-quick-view-wrapper .single_add_to_cart_button.wopb-quickview-buy-btn {';
                    $css .= 'margin-top: ' . $settings['quick_view_content_inner_gap'] . 'px;';
                $css .= '}';
            }

            if ( ! empty( $settings['quick_view_thumbnail_ratio'] ) && $settings['quick_view_thumbnail_ratio'] == 'custom' ) {
                if( ! empty( $settings['quick_view_thumbnail_width'] ) ) {
                    $css .= '.wopb-quick-view-wrapper .wopb-quick-view-image{';
                        $css .= ! empty( $settings['quick_view_thumbnail_width'] ) ? ( 'max-width: ' . $settings['quick_view_thumbnail_width'] . 'px !important;' ) : '';
                    $css .= '}';
                }
                if( ! empty( $settings['quick_view_thumbnail_width'] ) ) {
                    $css .= '.wopb-quick-view-gallery .wopb-thumbnail .wopb-quick-slider img{';
                        $css .= ! empty( $settings['quick_view_thumbnail_height'] ) ? ( 'max-height: ' . $settings['quick_view_thumbnail_height'] . 'px !important;' ) : '';
                    $css .= '}';
                }
            }

            $css .= '.wopb-quick-view-wrapper form.cart button.single_add_to_cart_button,';
            $css .= '.wopb-quick-view-wrapper .single_add_to_cart_button.wopb-quickview-buy-btn,';
            $css .= '.wopb-quick-view-wrapper .wopb-compare-btn.wopb-compare-shop-btn,';
            $css .= '.wopb-quick-view-wrapper .wopb-wishlist-add.wopb-wishlist-shop-btn,';
            $css .= '.wopb-quick-view-wrapper .wopb-chunk-price-label{';
                $css .= wopb_function()->convert_css('general', $modal_btn_style);
                $css .= wopb_function()->convert_css('border', $settings['quick_view_modal_btn_border']);
            $css .= '}';
            $css .= '.wopb-quick-view-wrapper form.cart button.single_add_to_cart_button:hover,';
            $css .= '.wopb-quick-view-wrapper .single_add_to_cart_button.wopb-quickview-buy-btn:hover,';
            $css .= '.wopb-quick-view-wrapper .wopb-compare-btn.wopb-compare-shop-btn:hover,';
            $css .= '.wopb-quick-view-wrapper .wopb-wishlist-add.wopb-wishlist-shop-btn:hover,';
            $css .= '.wopb-quick-view-wrapper .wopb-chunk-price-label:hover{';
                $css .= wopb_function()->convert_css('hover', $modal_btn_style);
                $css .= wopb_function()->convert_css('border', $settings['quick_view_modal_btn_border']);
            $css .= '}';

            wopb_function()->update_css( $key, 'add', $css );
        }
    }
}
