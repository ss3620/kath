<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Product_Category_1 {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'queryType'                 => 'regular',
			'queryCat'                  => '[]',
			'queryNumber'               => 8,
			'readMore'                  => false,
			'productView'               => 'grid',
			'columns'                   => (object) array(
				'lg' => '3',
				'sm' => '2',
				'xs' => '1',
			),
			'slidesToShow'              => (object) array(
				'lg' => '4',
				'sm' => '2',
				'xs' => '1',
			),
			'autoPlay'                  => true,
			'showDots'                  => true,
			'showArrows'                => true,
			'slideSpeed'                => '3000',
			'headingShow'               => false,
			'showImage'                 => true,
			'titleShow'                 => true,
			'descShow'                  => true,
			'countShow'                 => true,
			'arrowStyle'                => 'leftAngle2#rightAngle2',
			'headingText'               => 'Product Category #1',
			'headingURL'                => '',
			'headingBtnText'            => 'View More',
			'headingStyle'              => 'style1',
			'headingTag'                => 'h2',
			'headingAlign'              => 'left',
			'subHeadingShow'            => false,
			'subHeadingText'            => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ut sem augue. Sed at felis ut enim dignissim sodales.',
			'titleTag'                  => 'h3',
			'contentVerticalPosition'   => 'middlePosition',
			'contentHorizontalPosition' => 'centerPosition',
			'categoryrCountText'        => 'products',
			'imgAnimation'              => 'zoomIn',
			'readMoreText'              => '',
			'descLimit'                 => 5,
			'currentPostId'             => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/product-category-1',
			array(
				'editor_script'   => 'wopb-blocks-editor-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr, $noAjax = false ) {

		$attr = wp_parse_args( $attr, $this->get_attributes() );

		if ( ! $noAjax ) {
			$paged         = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );
			$attr['paged'] = $paged ? $paged : 1;
		}

		$block_name           = 'product-category-1';
		$wraper_before        = $wraper_after = $post_loop = '';
		$wrapper_main_content = '';
		$image_size           = isset( $attr['imgCrop'] ) && $attr['imgCrop'] ? $attr['imgCrop'] : 'full';

		$slider_attr = wc_implode_html_attributes(
			array(
				'data-slidestoshow' => wopb_function()->slider_responsive_split( $attr['slidesToShow'] ),
				'data-autoplay'     => esc_attr( $attr['autoPlay'] ),
				'data-slidespeed'   => esc_attr( $attr['slideSpeed'] ),
				'data-showdots'     => esc_attr( $attr['showDots'] ),
				'data-showarrows'   => esc_attr( $attr['showArrows'] ),
			)
		);

		$recent_posts = wopb_function()->get_category_data( json_decode( $attr['queryCat'] ), $attr['queryNumber'], $attr['queryType'] );

		if ( ! empty( $recent_posts ) ) {
			$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
			$attr['align']     = ! empty( $attr['align'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
			$attr['titleTag']  = in_array( $attr['titleTag'], wopb_function()->allowed_block_tags() ) ? $attr['titleTag'] : 'h3';

			$wraper_before     .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . ( isset( $attr['align'] ) ? ' align' . $attr['align'] : '' ) . '">';
				$wraper_before .= '<div class="wopb-block-wrapper">';

			if ( $attr['headingShow'] ) {
				$wraper_before     .= '<div class="wopb-heading-filter">';
					$wraper_before .= '<div class="wopb-heading-filter-in">';
						include WOPB_PATH . 'blocks/template/heading.php';
					$wraper_before .= '</div>';
				$wraper_before     .= '</div>';
			}

					$wraper_before .= '<div class="wopb-wrapper-main-content">';
			if ( $attr['productView'] == 'slide' ) {
				$wrapper_main_content .= '<div class="wopb-product-blocks-slide" ' . wp_kses_post( $slider_attr ) . '>';
			} else {
				$wrapper_main_content .= '<div class="wopb-block-items-wrap wopb-block-row wopb-block-column-' . sanitize_html_class( json_decode( wp_json_encode( $attr['columns'] ), true )['lg'] ) . '">';
			}

			foreach ( $recent_posts as $value ) {
				$post_loop     .= '<div class="wopb-block-item">';
					$post_loop .= '<div class="wopb-block-content-wrap wopb-category-wrap">';
				if ( $attr['showImage'] ) {
							$post_loop .= '<div class="wopb-block-image wopb-block-image-' . sanitize_html_class( $attr['imgAnimation'] ) . '"><a href="' . esc_url( $value['url'] ) . '" class="wopb-product-cat-img"><img src="' . esc_url( $value['image'] ? $value['image'][ $image_size ] : WOPB_URL . 'assets/img/wopb_fallback.jpg' ) . '" alt="' . esc_attr( $value['name'] ) . '"/></a></div>';
				}
				if ( $attr['titleShow'] || $attr['countShow'] || $attr['descShow'] || $attr['readMore'] ) {
									$post_loop     .= '<div class="wopb-category-content-items wopb-category-content-' . sanitize_html_class( $attr['contentVerticalPosition'] ) . ' wopb-category-content-' . sanitize_html_class( $attr['contentHorizontalPosition'] ) . '">';
										$post_loop .= '<div class="wopb-category-content-item">';
					if ( $attr['titleShow'] ) {
						$post_loop .= '<' . $attr['titleTag'] . ' class="wopb-product-cat-title"><a href=' . esc_url( $value['url'] ) . '>' . esc_html( $value['name'] ) . '</a></' . $attr['titleTag'] . '>';
					}
					if ( $attr['countShow'] ) {
						$post_loop .= '<div class="wopb-product-cat-count">' . esc_html( $value['count'] ) . ' ' . ( isset( $attr['categoryrCountText'] ) ? esc_html( $attr['categoryrCountText'] ) : esc_html__( 'products', 'product-blocks' ) ) . '</div>';
					}
					if ( $attr['descShow'] ) {
						$post_loop .= '<div class="wopb-product-cat-desc">' . wc_format_content( wp_kses_post( wp_trim_words( $value['desc'], $attr['descLimit'] ) ) ) . '</div>';
					}
					if ( $attr['readMore'] ) {
						$post_loop .= '<div class="wopb-product-readmore"><a href=' . esc_url( $value['url'] ) . '>' . ( $attr['readMoreText'] ? esc_html( $attr['readMoreText'] ) : esc_html__( 'Read More', 'product-blocks' ) ) . '</a></div>';
					}
										$post_loop .= '</div>';
									$post_loop     .= '</div>';
				}
												$post_loop .= '</div>';
												$post_loop .= '</div>';
			}

							$wrapper_main_content .= $post_loop;

						$wrapper_main_content .= '</div>';// wopb-block-items-wrap

			if ( $attr['productView'] == 'slide' && $attr['showArrows'] ) {
				include WOPB_PATH . 'blocks/template/arrow.php';
			}

					$wraper_after .= '</div>';// wopb-wrapper-main-content
				$wraper_after     .= '</div>';
			$wraper_after         .= '</div>';

			wp_reset_postdata();
		}

		return $noAjax ? $post_loop : $wraper_before . $wrapper_main_content . $wraper_after;
	}
}
