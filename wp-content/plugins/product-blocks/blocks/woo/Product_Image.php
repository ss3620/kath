<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Product_Image {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'showGallery'       => true,
			'showSale'          => true,
			'showlightBox'      => true,
			'arrowLargeImage'   => true,
			'arrowGalleryImage' => true,
			'imageView'         => 'onclick',
			'galleryPosition'   => 'bottom',
			'galleryColumns'    => (object) array(
				'lg' => '4',
				'sm' => '2',
				'xs' => '2',
			),
			'saleText'          => 'Sale!',
			'saleDesign'        => 'text',
			'currentPostId'     => '',
			'hoverZoom'         => true,
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/product-image',
			array(
				'editor_script'   => 'wopb-blocks-builder-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr ) {
		global $product;
		$product       = wc_get_product();
		$block_name    = 'product-image';
		$wraper_before = $wraper_after = $content = '';
		$attr          = wp_parse_args( $attr, $this->get_attributes() );

		if ( ! empty( $product ) ) {
			global $productx_settings;
			$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';

			$wraper_before .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . '">';
			$wraper_before .= '<div class="wopb-product-wrapper wopb-product-gallery-' . sanitize_html_class( $attr['galleryPosition'] ) . '">';

			global $productx_sales;
			global $productx_sales_text;
			$sales      = $product->get_sale_price();
			$regular    = $product->get_regular_price();
			$percentage = ( $regular && $sales ) ? round( ( ( $regular - $sales ) / $regular ) * 100 ) : 0;
			if ( $attr['showSale'] && $percentage ) {
				$productx_sales_text = $attr['saleDesign'] == 'textDigit' ? '-' . esc_attr( $percentage ) . '% ' . esc_attr( $attr['saleText'] ) : ( $attr['saleDesign'] == 'digit' ? '-' . esc_attr( $percentage ) . '%' : esc_attr( $attr['saleText'] ) );
				$flash_sale          = function () {
					global $productx_sales_text;
					return '<div class="wopb-product-gallery-sale-tag">' . esc_html( $productx_sales_text ) . '</div>';
				};
				add_filter( 'woocommerce_sale_flash', $flash_sale );
				ob_start();
				woocommerce_show_product_sale_flash();
				$productx_sales = ob_get_clean();
				remove_filter( 'woocommerce_sale_flash', $flash_sale );
			}

			$gallery_classes = function ( $classes ) {
				if ( in_array( 'woocommerce-product-gallery', $classes ) ) {
					$classes[]                                    = 'woocommerce-product-gallery';
					$classes[ array_search( 'green', $classes ) ] = ' woocommerce-product-gallery-off';
				}
				return $classes;
			};

			$image_html = function ( $gallery, $post_id ) {
				return '';
			};

			$productx_settings['onview']           = $attr['imageView'];
			$productx_settings['showlight']        = $attr['showlightBox'];
			$productx_settings['position']         = $attr['galleryPosition'];
			$productx_settings['showArrow']        = $attr['arrowLargeImage'];
			$productx_settings['showGalleryArrow'] = $attr['arrowGalleryImage'];
			$productx_settings['column']           = (array) $attr['galleryColumns'];
			$productx_settings['showGallery']      = $attr['showGallery'];
			$productx_settings['hoverZoom']        = $attr['hoverZoom'];

			$slick_html = function () {
				global $product;
				global $productx_settings;
				global $productx_sales;
				$attachment = $product->get_image_id();
				$gallery    = $product->get_gallery_image_ids();

				$all_id = array();
				if ( ! empty( $attachment ) ) {
					$all_id[] = $attachment;
				}
				if ( ! empty( $gallery ) ) {
					$all_id = array_merge( $all_id, $gallery );
				}

				$image_full        = $image_thumb = '';
				$full_size         = apply_filters( 'woocommerce_gallery_full_size', apply_filters( 'woocommerce_product_thumbnails_large_size', 'full' ) );
				$thumbnail_size    = $full_size;
				$video_image_full  = '';
				$video_image_thumb = '';
				$video_meta        = get_post_meta( $product->get_id(), '__wopb_product_video', true );
				$video_position    = ! empty( $video_meta['single_position'] ) ? $video_meta['single_position'] : 'first';
				if ( ! empty( $all_id ) ) {
					ob_start();
                    echo apply_filters('wopb_product_video', '', $product, $all_id[0]); // phpcs:ignore
					$video_thumb = ob_get_clean();
					if ( $video_thumb ) {
						$fallback_url  = ! empty( $video_meta['img'] ) ? $video_meta['img'] : '';
						$thumbnail_src = wp_get_attachment_image_src( $all_id[0], $thumbnail_size );
						if ( ! empty( $video_meta['img'] ) && ! empty( $video_meta['img_id'] ) ) {
							$thumbnail_src = wp_get_attachment_image_src( $video_meta['img_id'], $thumbnail_size );
						}
						$fallback_thumb_url = ! empty( $thumbnail_src ) ? $thumbnail_src[0] : WOPB_URL . 'assets/img/fallback.svg';

						$full_src     = ! empty( $all_id ) ? wp_get_attachment_image_src( $all_id[0], $full_size ) : '';
						$fallback_url = ! $fallback_url ? ( ! empty( $full_src ) ? $full_src[0] : WOPB_URL . 'assets/img/fallback.svg' ) : $fallback_url;

						$video_image_full      .= '<div class="wopb-main-image wopb-product-video-section">';
							$video_image_full  .= '<img src="' . esc_url( $fallback_url ) . '" data-width="100" data-height="100"/>';
							$video_image_full  .= $video_thumb;
						$video_image_full      .= '</div>';
						$video_image_thumb     .= '<div class="wopb-nav-slide wopb-video-nav">';
							$video_image_thumb .= '<img src="' . esc_url( $fallback_thumb_url ) . '"/>';
						$video_image_thumb     .= '</div>';
					}
					$total_attachment = count( $all_id );
					foreach ( $all_id as $key => $attachment_id ) {
						$thumbnail_src = wp_get_attachment_image_src( $attachment_id, $thumbnail_size );
						$full_src      = wp_get_attachment_image_src( $attachment_id, $full_size );
						$alt_text      = trim( wp_strip_all_tags( get_post_meta( $attachment_id, '_wp_attachment_image_alt', true ) ) );
						if ( ! empty( $video_image_full ) && $video_position == 'first' && $key == 0 ) {
							$image_full  .= $video_image_full;
							$image_thumb .= $video_image_thumb;
						}
						$image_full     .= '<div class="wopb-main-image">';
							$image_full .= '<img src="' . esc_url( $full_src[0] ) . '" alt="' . esc_attr( $alt_text ) . '" data-width="' . esc_attr( $full_src[1] ) . '" data-height="' . esc_attr( $full_src[2] ) . '"/>';
						$image_full     .= '</div>';
						$image_thumb    .= '<div class="wopb-nav-slide"><img src="' . esc_url( $thumbnail_src[0] ) . '" alt="' . esc_attr( $alt_text ) . '" /></div>';
						if (
							! empty( $video_image_full ) &&
							( $video_position == 'after_first_image' && ( $key == 0 || $total_attachment == 1 ) ) ||
							( $video_position == 'last' && $key == $total_attachment - 1 )
						) {
							$image_full  .= $video_image_full;
							$image_thumb .= $video_image_thumb;
						}
					}
				} else {
					$image_full     .= '<div class="wopb-main-image">';
						$image_full .= '<img src="' . esc_url( wc_placeholder_img_src( 'woocommerce_single' ) ) . '" alt="' . esc_html__( 'Awaiting product image', 'product-blocks' ) . '"/>';
					$image_full     .= '</div>';
				}

				echo '<div class="wopb-product-gallery-wrapper' . esc_attr( $productx_settings['showlight'] ? ' wopb-product-zoom-wrapper' : '' ) . '" data-hover-zoom="' . esc_attr( $productx_settings['hoverZoom'] ) . '">';
				if ( $productx_settings['showlight'] ) {
					echo '<a href="#" class="wopb-product-zoom"><svg enable-background="new 0 0 612 612" version="1.1" viewBox="0 0 612 612" xml:space="preserve" xmlns="http://www.w3.org/2000/svg"><path d="m243.96 340.18-206.3 206.32 0.593-125.75c0-10.557-8.568-19.125-19.125-19.125s-19.125 8.568-19.125 19.125v172.12c0 5.661 2.333 10.232 6.043 13.368 3.462 3.538 8.282 5.757 13.637 5.757h171.57c10.557 0 19.125-8.567 19.125-19.125 0-10.557-8.568-19.125-19.125-19.125h-126.78l206.53-206.51-27.043-27.061zm362-334.42c-3.461-3.538-8.28-5.757-13.616-5.757h-171.59c-10.557 0-19.125 8.568-19.125 19.125s8.568 19.125 19.125 19.125h126.76l-206.51 206.53 27.042 27.042 206.32-206.32-0.612 125.75c0 10.557 8.568 19.125 19.125 19.125s19.125-8.568 19.125-19.125v-172.12c0-5.661-2.333-10.231-6.044-13.368z"/></svg></a>';
				}
				if ( $productx_sales ) {
					echo wp_kses_post( $productx_sales );
				}
					echo '<div class="wopb-builder-slider-for" data-arrow="' . esc_attr( $productx_settings['showArrow'] ) . '">';
				if ( $image_full ) {
					$image_full_safe = wopb_function()->wp_kses_safe( $image_full );
					echo $image_full_safe; // phpcs:ignore
				}
					echo '</div>';
				echo '</div>';
				if ( count( $all_id ) > 1 || ( ! empty( $video_thumb ) && count( $all_id ) > 0 ) ) {
					$lg = isset( $productx_settings['column']['lg'] ) ? $productx_settings['column']['lg'] : 4;
					$md = isset( $productx_settings['column']['md'] ) ? $productx_settings['column']['md'] : 4;
					$sm = isset( $productx_settings['column']['sm'] ) ? $productx_settings['column']['sm'] : 2;
					$xs = isset( $productx_settings['column']['xs'] ) ? $productx_settings['column']['xs'] : 2;
					if ( $productx_settings['showGallery'] ) {
						echo '<div class="wopb-builder-slider-nav thumb-image" data-arrow="' . esc_attr( $productx_settings['showGalleryArrow'] ) . '" data-view="' . esc_attr( $productx_settings['onview'] ) . '" data-position="' . esc_attr( $productx_settings['position'] ) . '" data-collg="' . esc_attr( $lg ) . '" data-colmd="' . esc_attr( $md ) . '" data-colsm="' . esc_attr( $sm ) . '" data-colxs="' . esc_attr( $xs ) . '">';
						if ( $image_thumb ) {
							echo wp_kses_post( $image_thumb );
						}
						echo '</div>';
					}
				}
			};

			add_filter(
				'wc_get_template',
				function ( $template, $template_name, $args, $template_path, $default_path ) use ( $slick_html ) {
					if ( 'single-product/product-image.php' == $template_name ) {
						return WC_ABSPATH . 'templates/single-product/product-image.php';
					}
					return $template;
				},
				20,
				5
			);
			add_action( 'woocommerce_product_thumbnails', $slick_html );

			add_filter( 'woocommerce_single_product_image_gallery_classes', $gallery_classes );
			add_filter( 'woocommerce_single_product_image_thumbnail_html', $image_html, 10, 2 );

			ob_start();
			woocommerce_show_product_images();
			$content .= ob_get_clean();

			remove_filter( 'woocommerce_single_product_image_gallery_classes', $gallery_classes );
			remove_filter( 'woocommerce_single_product_image_thumbnail_html', $image_html, 10, 2 );

			$wraper_after .= '</div>';
			$wraper_after .= '</div>';

			remove_action( 'woocommerce_product_thumbnails', $slick_html );
		}

		return $wraper_before . $content . $wraper_after;
	}
}
