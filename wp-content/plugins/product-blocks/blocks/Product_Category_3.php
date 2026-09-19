<?php
namespace WOPB\blocks;

use WOPB\Includes\Durbin\Xpo;

defined( 'ABSPATH' ) || exit;

class Product_Category_3 {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'layout'                 => 'layout1',
			'queryType'              => 'regular',
			'queryCat'               => '[]',
			'queryNumber'            => 8,
			'readMore'               => false,
			'productView'            => 'grid',
			'columns'                => array(
				'lg' => '4',
				'sm' => '2',
				'xs' => '2',
			),
			'columnGridGap'          => array(
				'lg'   => '0',
				'unit' => 'px',
			),
			'rowGap'                 => array(
				'lg'   => '0',
				'unit' => 'px',
			),
			'slidesToShow'           => (object) array(
				'lg' => '4',
				'sm' => '2',
				'xs' => '1',
			),
			'autoPlay'               => true,
			'showDots'               => true,
			'showArrows'             => true,
			'slideSpeed'             => '3000',
			'hideEmptyImageCategory' => false,
			'headingShow'            => false,
			'showImage'              => true,
			'titleShow'              => true,
			'descShow'               => false,
			'countShow'              => false,
			'arrowStyle'             => 'leftAngle2#rightAngle2',
			'headingText'            => 'Product Category #3',
			'headingURL'             => '',
			'headingBtnText'         => 'View More',
			'headingStyle'           => 'style1',
			'headingTag'             => 'h2',
			'headingAlign'           => 'left',
			'subHeadingShow'         => false,
			'subHeadingText'         => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ut sem augue. Sed at felis ut enim dignissim sodales.',
			'titleTag'               => 'p',
			'categoryCountPosition'  => 'afterTitle',
			'categoryrCountText'     => '',
			'imgCrop'                => 'full',
			'imgAnimation'           => 'none',
			'fallbackImg'            => '',
			'readMoreText'           => 'Read More',
			'descLimit'              => 5,
			'currentPostId'          => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/product-category-3',
			array(
				'editor_script'   => 'wopb-blocks-editor-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr, $noAjax = false ) {

		$attr = wp_parse_args( $attr, $this->get_attributes() );

		$is_visible = true;
		if ( isset( $attr['blockPubDate'] ) && $attr['blockPubDate'] != 'empty' ) {
			$is_visible = Xpo::is_pro_feature_visible( $attr['blockPubDate'] );
		}
		$is_active = Xpo::is_lc_active();

		if ( $is_active || $is_visible ) {
			if ( ! $noAjax ) {
				$paged         = is_front_page() ? get_query_var( 'page' ) : get_query_var( 'paged' );
				$attr['paged'] = $paged ? $paged : 1;
			}

			$wrapper_main_content = '';
			$block_name           = 'product-category-3';
			$wraper_before        = $wraper_after = $post_loop = '';
			$image_size           = $attr['imgCrop'] ? $attr['imgCrop'] : 'full';

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
				$attr['align']     = ! empty( $attr['align'] ) ? 'align' . preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
				$columns           = ! empty( $attr['columns']['lg'] ) ? intval( $attr['columns']['lg'] ) : 4;
				$row_gap           = ! empty( $attr['rowGap']['lg'] ) ? intval( $attr['rowGap']['lg'] ) : 0;
				$column_grid_gap   = ! empty( $attr['columnGridGap']['lg'] ) ? intval( $attr['columnGridGap']['lg'] ) : 0;
				$attr['titleTag']  = in_array( $attr['titleTag'], wopb_function()->allowed_block_tags() ) ? $attr['titleTag'] : 'div';

				$wraper_before .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . $attr['align'] . '">';
				$wraper_before .= '<div class="wopb-block-wrapper wopb-product-category-3-wrapper">';

				if ( $attr['headingShow'] ) {
					$wraper_before .= '<div class="wopb-heading-filter">';
					$wraper_before .= '<div class="wopb-heading-filter-in">';
					include WOPB_PATH . 'blocks/template/heading.php';
					$wraper_before .= '</div>';
					$wraper_before .= '</div>';
				}

				$wraper_before .= '<div class="wopb-wrapper-main-content">';
				if ( $attr['productView'] == 'slide' ) {
					$wrapper_main_content .= '<div class="wopb-product-blocks-slide" ' . wp_kses_post( $slider_attr ) . '>';
				} else {
					$wrapper_main_content .= '<div class="wopb-block-items-wrap wopb-block-row wopb-block-column-' . $columns . '">';
				}

				$key        = 0;
				$count_post = count( $recent_posts );
				foreach ( $recent_posts as $value ) {
					if ( $attr['hideEmptyImageCategory'] && ! $value['image'] ) {
						$count_post = $count_post - 1;
						continue;
					}
					++$key;
					$block_item_class = '';
					if ( $row_gap == 0 ) {
						$block_item_class .= ' wopb-row-gap-' . $row_gap;
					}
					if ( $column_grid_gap == 0 ) {
						$block_item_class .= ' wopb-column-gap-' . $column_grid_gap;
					}
					if ( $key > $columns ) {
						$block_item_class .= ' wopb-last-row-item';
						if ( $count_post % $columns != 0 && $key == $count_post ) {
							$block_item_class .= ' wopb-last-rest-item';
						}
					}
					if ( $key % $columns != 0 && $key != $count_post ) {
						$block_item_class .= ' wopb-center-column-item';
					}
					$category_count = '';
					if ( $attr['countShow'] ) {
						$category_count = '<span class="wopb-product-cat-count">' . ( $attr['categoryCountPosition'] == 'withTitle' ? '(' : '' ) .
							esc_html( $value['count'] ) .
							( isset( $attr['categoryrCountText'] ) && $attr['categoryrCountText']
								? ' ' . esc_html( $attr['categoryrCountText'] )
								: '' ) .
							( $attr['categoryCountPosition'] == 'withTitle' ? ')' : '' ) .
							'</span>';
					}
					$post_loop .= '<div class="wopb-block-item' . $block_item_class . '">';
					$post_loop .= '<div class="wopb-block-content-wrap wopb-category-wrap' . ( $attr['layout'] ? ' wopb-product-cat-' . sanitize_html_class( $attr['layout'] ) : '' ) . '">';
					if ( $attr['showImage'] ) {
						$post_loop .= '<div class="wopb-block-image wopb-block-image-' . esc_attr( $attr['imgAnimation'] ) . '">';
						$post_loop .= '<a href="' . esc_url( $value['url'] ) . '" class="wopb-product-cat-img' . ( $attr['countShow'] && $attr['categoryCountPosition'] == 'imageTop' ? ' imageTop' : '' ) . '">';
						if ( $attr['categoryCountPosition'] == 'imageTop' ) {
							$post_loop .= $category_count;
						}
						$post_loop .= '<img src=' . esc_url(
							$value['image']
								? $value['image'][ $image_size ]
								: ( $attr['fallbackImg'] && $attr['fallbackImg']['url']
									? $attr['fallbackImg']['url']
									: WOPB_URL . 'assets/img/wopb_fallback.jpg' )
						) . ' alt=' . esc_attr( $value['name'] ) . '/>';
						$post_loop .= '</a>';
						$post_loop .= '</div>';
					}
					if ( $attr['titleShow'] || $attr['countShow'] || $attr['descShow'] || $attr['readMore'] ) {
						$post_loop .= '<div class="wopb-category-content-items">';
						$post_loop .= '<div class="wopb-category-content-item">';
						if ( $attr['titleShow'] ) {
							$post_loop .= '<' . $attr['titleTag'] . ' class="wopb-product-cat-title">';
							$post_loop .= '<a href=' . esc_url( $value['url'] ) . '>' . esc_html( $value['name'] ) . '</a>';
							if ( $attr['categoryCountPosition'] == 'withTitle' ) {
								$post_loop .= $category_count;
							}
							$post_loop .= '</' . $attr['titleTag'] . '>';
						}
						if ( $attr['categoryCountPosition'] == 'afterTitle' ) {
							$post_loop .= $category_count;
						}
						if ( $attr['descShow'] ) {
							$post_loop .= '<div class="wopb-product-cat-desc">' . wc_format_content( wp_kses_post( wp_trim_words( $value['desc'], $attr['descLimit'] ) ) ) . '</div>';
						}
						if ( $attr['readMore'] ) {
							$post_loop .= '<div class="wopb-product-readmore"><a href=' . esc_url( $value['url'] ) . '>' . ( $attr['readMoreText'] ? wp_kses( $attr['readMoreText'], wopb_function()->allowed_html_tags() ) : esc_html__( 'Read More', 'product-blocks' ) ) . '</a></div>';
						}
						$post_loop .= '</div>';
						$post_loop .= '</div>';
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
				$wraper_after .= '</div>';
				$wraper_after .= '</div>';

				wp_reset_postdata();
			}

			return $noAjax ? $post_loop : $wraper_before . $wrapper_main_content . $wraper_after;
		}
	}
}
