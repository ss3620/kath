<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Product_List_1 {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'sortSection'      => '["title","description","price","review","cart"]',
			'productView'      => 'grid',
			'columns'          => array(
				'lg' => '2',
				'sm' => '1',
			),
			'slidesToShow'     => (object) array(
				'lg' => '4',
				'sm' => '2',
				'xs' => '1',
			),
			'autoPlay'         => true,
			'showDots'         => true,
			'showArrows'       => true,
			'slideSpeed'       => '3000',
			'showPrice'        => true,
			'showReview'       => true,
			'showCart'         => false,
			'filterShow'       => false,
			'paginationShow'   => false,
			'headingShow'      => false,
			'titleShow'        => true,
			'showShortDesc'    => false,
			'showImage'        => true,
			'disableFlip'      => false,
			'queryTax'         => 'product_cat',
			'arrowStyle'       => 'leftAngle2#rightAngle2',
			'headingText'      => 'Product List #1',
			'headingURL'       => '',
			'headingBtnText'   => 'View More',
			'headingStyle'     => 'style1',
			'headingTag'       => 'h2',
			'headingAlign'     => 'left',
			'subHeadingShow'   => false,
			'subHeadingText'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ut sem augue. Sed at felis ut enim dignissim sodales.',
			'shortDescLimit'   => 7,
			'titleTag'         => 'h3',
			'titleLength'      => 0,
			'cartText'         => '',
			'cartActive'       => 'View Cart',
			'imgFlip'          => false,
			'imgCrop'          => 'full',
			'imgAnimation'     => 'none',
			'filterType'       => 'product_cat',
			'filterText'       => 'all',
			'filterCat'        => '[]',
			'filterTag'        => '["all"]',
			'filterAction'     => '[]',
			'filterActionText' => 'Top Sale|Popular|On Sale|Most Rated|Top Rated|Featured|New Arrival',
			'filterMobile'     => true,
			'filterMobileText' => 'More',
			'paginationType'   => 'pagination',
			'loadMoreText'     => 'Load More',
			'paginationText'   => 'Previous|Next',
			'paginationNav'    => 'textArrow',
			'paginationAjax'   => true,
			'queryNumber'      => 6,
			'overlayMetaList'  => '["_cart"]',
			'tooltipPosition'  => 'left',
			'ovrMetaInline'    => true,
			'currentPostId'    => '',
			'hoverMeta'        => true,
			'cartNoFollow'     => true,
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/product-list-1',
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

			if ( isset( $_COOKIE['productFilters'] ) ) {
				// Remove extra backslashes.
				$raw = sanitize_text_field( wp_unslash( $_COOKIE['productFilters'] ?? '' ) );

				// Then decode JSON.
				$product_filters = json_decode( $raw, true );

				if ( is_array( $product_filters ) ) {
					$attr['product_filters'] = $product_filters;
				}
			}
		}

		$block_name            = 'product-list-1';
		$wraper_before         = $wraper_after = $post_loop = '';
		$wrapper_main_content  = '';
		$recent_posts          = new \WP_Query( wopb_function()->get_query( $attr ) );
		$pageNum               = wopb_function()->get_page_number( $attr, $recent_posts->found_posts );
		$parsedOverlayMetaList = json_decode( $attr['overlayMetaList'], true );
		$item_calss            = wopb_function()->loop_item_classes();

		$slider_attr = wc_implode_html_attributes(
			array(
				'data-slidestoshow' => wopb_function()->slider_responsive_split( $attr['slidesToShow'] ),
				'data-autoplay'     => esc_attr( $attr['autoPlay'] ),
				'data-slidespeed'   => esc_attr( $attr['slideSpeed'] ),
				'data-showdots'     => esc_attr( $attr['showDots'] ),
				'data-showarrows'   => esc_attr( $attr['showArrows'] ),
			)
		);

		if ( $recent_posts->have_posts() ) {
			$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
			$attr['align']     = ! empty( $attr['align'] ) ? 'align' . preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
			$cart_params       = array(
				'cartText'        => $attr['cartText'],
				'cartActive'      => $attr['cartActive'],
				'tooltipPosition' => $attr['tooltipPosition'],
				'cartNoFollow'    => $attr['cartNoFollow'],
			);

			$wraper_before     .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . $attr['align'] . '">';
				$wraper_before .= '<div class="wopb-block-wrapper' . ( isset( $attr['layout'] ) ? ' wopb-has-gridlay' : '' ) . '">';

			if ( $attr['headingShow'] || $attr['filterShow'] ) {
				$wraper_before     .= '<div class="wopb-heading-filter">';
					$wraper_before .= '<div class="wopb-heading-filter-in">';

						// Heading
						include WOPB_PATH . 'blocks/template/heading.php';

				if ( $attr['filterShow'] && $attr['productView'] == 'grid' ) {
					$wraper_before .= '<div class="wopb-filter-navigation">';

						// Filter
					if ( $attr['filterShow'] ) {
						include WOPB_PATH . 'blocks/template/filter.php';
					}
							$wraper_before .= '</div>';
				}
					$wraper_before     .= '</div>';
						$wraper_before .= '</div>';
			}

					$wraper_before .= '<div class="wopb-wrapper-main-content">';
			if ( $attr['productView'] == 'slide' ) {
				$wrapper_main_content .= '<div class="wopb-product-blocks-slide" ' . wp_kses_post( $slider_attr ) . '>';
			} else {
				$wrapper_main_content .= '<div class="wopb-block-items-wrap wopb-block-row wopb-block-column-' . ( ! empty( $attr['columns']['lg'] ) ? intval( $attr['columns']['lg'] ) : 4 ) . ' wopb-block-content-' . esc_attr( $attr['imgFlip'] ) . '">';
			}
							$is_show = json_decode( $attr['sortSection'] );
							$idx     = $noAjax ? 1 : 0;
			while ( $recent_posts->have_posts() ) :
				$recent_posts->the_post();

				$title_data = $price_data = $review_data = $cart_data = $description_data = '';

				include WOPB_PATH . 'blocks/template/data.php';
				if ( $product ) {
					$post_loop     .= '<div class="wopb-block-item wopb-block-media ' . ( ! empty( $item_calss['wrapper'] ) ? $item_calss['wrapper'] : '' ) . '">';
						$post_loop .= '<div class="wopb-block-content-wrap">';

					if ( $attr['showImage'] ) {
								$post_loop .= '<div class="wopb-block-image wopb-block-image-' . esc_attr( $attr['imgAnimation'] ) . '">';

						if ( has_post_thumbnail() ) {
							$post_loop .= '<a href="' . esc_url( $titlelink ) . '">';
							$post_loop .= '<img ';
							$post_loop .= 'src="' . esc_url( wp_get_attachment_image_url( $post_thumb_id, ( $attr['imgCrop'] ? $attr['imgCrop'] : 'full' ) ) ) . '" ';
							$post_loop .= 'alt="' . esc_attr( $title ) . '" ';
							if ( ! empty( $item_calss['image'] ) ) {
												$post_loop .= 'class=" ' . $item_calss['image'] . '" ';
												$post_loop .= 'srcset="' . wp_get_attachment_image_srcset( $post_thumb_id ) . '"';
							}
										$post_loop .= '/>';
							if ( ! $attr['disableFlip'] ) {
													$post_loop .= apply_filters( 'wopb_flip_image', '', $product, $attr['imgCrop'] );
							}
											$post_loop .= '</a>';
						} else {
							$post_loop         .= '<div class="empty-image">';
								$post_loop     .= '<a href="' . esc_url( $titlelink ) . '">';
									$post_loop .= '<img alt=' . esc_attr( $title ) . ' src="' . esc_url( wc_placeholder_img_src( ( $attr['imgCrop'] ? $attr['imgCrop'] : 'full' ) ) ) . '"/>';
								$post_loop     .= '</a>';
							$post_loop         .= '</div>';
						}

						if ( ! empty( $parsedOverlayMetaList ) ) {
							$meta_class  = $attr['ovrMetaInline'] ? ' wopb_f_inline' : '';
							$meta_class .= ! $attr['hoverMeta'] ? ' wopb-is-visible' : '';
							$post_loop  .= '<div class="wopb-product-new-meta' . $meta_class . '">';
							foreach ( $parsedOverlayMetaList as $meta_val ) {
								if ( $meta_val == '_wishlist' ) {
									$post_loop .= apply_filters( 'wopb_grid_wishlist', '', $post_id, $attr['tooltipPosition'] );
								}
								if ( $meta_val == '_qview' ) {
													$post_loop .= apply_filters( 'wopb_grid_quickview', '', $recent_posts, $post_id, $attr['tooltipPosition'] );
								}
								if ( $meta_val == '_compare' ) {
									$post_loop .= apply_filters( 'wopb_grid_compare', '', $post_id, $attr['tooltipPosition'] );
								}
								if ( $meta_val == '_cart' ) {
												$cart_params['isIcon'] = true;
												$post_loop            .= wopb_function()->get_add_to_cart( $product, $cart_params );
								}
							}
							$post_loop .= '</div>';
						}

										$post_loop .= '</div>';
					}

							$post_loop .= '<div class="wopb-block-content">';

								// Title
					if ( $attr['titleShow'] && in_array( 'title', $is_show ) ) {
										include WOPB_PATH . 'blocks/template/title.php';
					}

					if ( $attr['showShortDesc'] ) {
						$description_data .= '<div class="wopb-short-description">' . wopb_function()->excerpt( $post_id, $attr['shortDescLimit'] ) . '</div>';
					}

													// Review
					if ( $attr['showReview'] && in_array( 'review', $is_show ) ) {
						include WOPB_PATH . 'blocks/template/review.php';
					}

													// Price
					if ( $attr['showPrice'] && in_array( 'price', $is_show ) ) {
						$price_data .= '<div class="wopb-product-price">' . $product->get_price_html() . '</div>';
					}

													// Add to Cart URL
					if ( $attr['showCart'] && in_array( 'cart', $is_show ) ) {
						$cart_params['isIcon'] = false;
						$cart_data            .= wopb_function()->get_add_to_cart( $product, $cart_params );
					}

					foreach ( $is_show as $value ) {
						$post_loop .= ${$value . '_data'};
					}

													$post_loop .= '</div>';
													$post_loop .= '</div>';
													$post_loop .= '</div>';
				}
				++$idx;
							endwhile;

							$wrapper_main_content .= $post_loop;

			if ( $attr['paginationShow'] && $attr['productView'] == 'grid' && $attr['paginationType'] == 'loadMore' ) {
				$wrapper_main_content .= '<span class="wopb-loadmore-insert-before"></span>';
			}

						$wrapper_main_content .= '</div>';// wopb-block-items-wrap

						// Load More
			if ( $attr['paginationShow'] && $attr['productView'] == 'grid' && $attr['paginationType'] == 'loadMore' ) {
				include WOPB_PATH . 'blocks/template/loadmore.php';
			}

						// Pagination
			if ( $attr['paginationShow'] && $attr['productView'] == 'grid' && $attr['paginationType'] == 'pagination' ) {
				include WOPB_PATH . 'blocks/template/pagination.php';
			}

			if ( $attr['productView'] == 'slide' && $attr['showArrows'] ) {
				include WOPB_PATH . 'blocks/template/arrow.php';
			}

					$wraper_after .= '</div>';// wopb-wrapper-main-content
				$wraper_after     .= '</div>';
			$wraper_after         .= '</div>';

			wp_reset_postdata();
		}

		if ( $noAjax && $attr['ajax_source'] == 'filter' || ! ( $recent_posts->have_posts() ) ) {
			if ( $post_loop === '' ) {
				// note: dont remove without proper check. - Shihab.
				// these before after are necessary for jquery use in filter.js .
				$wraper_before .= '<div '
									. ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ?? '' ) . '" ' : '' )
									. ' class="wp-block-product-blocks-' . esc_attr( $block_name ?? '' )
									. ' wopb-block-' . sanitize_html_class( $attr['blockId'] ?? '' )
									. ' ' . ( $attr['className'] ?? '' )
									. ' ' . ( $attr['align'] ?? '' )
									. '">';
				$wraper_before .= '<div class="wopb-block-wrapper'
									. ( isset( $attr['layout'] ) ? ' wopb-has-gridlay' : '' )
									. '">';
				$wraper_before .= '<div class="wopb-wrapper-main-content">';

				$wrapper_main_content .= '<span class="wopb-no-product-found">' . esc_html__( 'No products were found of your matching selection', 'product-blocks' ) . '</span>';

				$wraper_after .= '</div>'; // wopb-wrapper-main-content.
				$wraper_after .= '</div>'; // wopb-block-wrapper.
				$wraper_after .= '</div>'; // wopb-block.
				// before after needed when no products found when it is rendering without ajax/api.
				if ( ! $noAjax ) {
					return $wraper_before . $wrapper_main_content . $wraper_after;
				}
			}

			return $wrapper_main_content;
		}

		return $noAjax ? $post_loop : $wraper_before . $wrapper_main_content . $wraper_after;
	}
}
