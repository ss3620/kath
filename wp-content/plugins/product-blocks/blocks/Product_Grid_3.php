<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Product_Grid_3 {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'productView'             => 'grid',
			'contentLayout'           => '1',
			'columns'                 => array(
				'lg' => '3',
				'sm' => '2',
				'xs' => '1',
			),
			'slidesToShow'            => (object) array(
				'lg' => '3',
				'sm' => '2',
				'xs' => '1',
			),
			'autoPlay'                => true,
			'showDots'                => true,
			'showArrows'              => true,
			'slideSpeed'              => '3000',
			'showPrice'               => true,
			'showReview'              => true,
			'showCart'                => true,
			'showOutStock'            => true,
			'showInStock'             => false,
			'showShortDesc'           => false,
			'showSale'                => true,
			'showHot'                 => false,
			'showDeal'                => false,
			'filterShow'              => false,
			'headingShow'             => false,
			'paginationShow'          => false,
			'catShow'                 => true,
			'titleShow'               => true,
			'showImage'               => true,
			'disableFlip'             => false,
			'showVideo'               => true,
			'showVariationSwitch'     => true,
			'variationSwitchPosition' => 'before_title',
			'queryTax'                => 'product_cat',
			'arrowStyle'              => 'leftAngle2#rightAngle2',
			'headingText'             => 'Product Grid #3',
			'headingURL'              => '',
			'headingBtnText'          => 'View More',
			'headingStyle'            => 'style1',
			'headingTag'              => 'h2',
			'headingAlign'            => 'left',
			'subHeadingShow'          => false,
			'subHeadingText'          => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ut sem augue. Sed at felis ut enim dignissim sodales.',
			'saleText'                => 'Sale!',
			'saleDesign'              => 'text',
			'saleStyle'               => 'classic',
			'hotText'                 => 'Hot',
			'dealText'                => 'Days|Hours|Minutes|Seconds',
			'shortDescLimit'          => 7,
			'titleTag'                => 'h3',
			'titleLength'             => 0,
			'cartText'                => '',
			'cartActive'              => 'View Cart',
			'enableCatLink'           => true,
			'catPosition'             => 'none',
			'imgCrop'                 => 'full',
			'imgAnimation'            => 'none',
			'filterType'              => 'product_cat',
			'filterText'              => 'all',
			'filterCat'               => '[]',
			'filterTag'               => '["all"]',
			'filterAction'            => '[]',
			'filterActionText'        => 'Top Sale|Popular|On Sale|Most Rated|Top Rated|Featured|New Arrival',
			'filterMobile'            => true,
			'filterMobileText'        => 'More',
			'paginationType'          => 'pagination',
			'loadMoreText'            => 'Load More',
			'paginationText'          => 'Previous|Next',
			'paginationNav'           => 'textArrow',
			'paginationAjax'          => true,
			'queryNumber'             => 6,
			'overlayMetaList'         => '["_compare","_wishlist","_qview"]',
			'tooltipPosition'         => 'left',
			'ovrMetaInline'           => false,
			'currentPostId'           => '',
			'hoverMeta'               => true,
			'cartNoFollow'            => true,
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/product-grid-3',
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

		$wrapper_main_content  = '';
		$block_name            = 'product-grid-3';
		$wraper_before         = $wraper_after = $post_loop = '';
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
				$wrapper_main_content .= '<div class="wopb-block-items-wrap wopb-block-row wopb-block-column-' . ( ! empty( $attr['columns']['lg'] ) ? intval( $attr['columns']['lg'] ) : 3 ) . '">';
			}

							$idx = $noAjax ? 1 : 0;
			while ( $recent_posts->have_posts() ) :
				$recent_posts->the_post();

				$image_data = $category_data = $title_data = $price_data = $review_data = $cart_data = $title_review_data = $cat_price_data = $description_data = $variationSwitcher_data = '';

				include WOPB_PATH . 'blocks/template/data.php';
				include WOPB_PATH . 'blocks/template/category.php';

				if ( $product ) {
					$post_loop     .= '<div class="wopb-block-item ' . ( ! empty( $item_calss['wrapper'] ) ? $item_calss['wrapper'] : '' ) . '">';
						$post_loop .= '<div class="wopb-block-content-wrap">';

						// Variation Switcher
					if ( $attr['showVariationSwitch'] ) {
									$variationSwitcher_data = apply_filters( 'wopb_variation_switcher', '', $product );
					}

							// Image
					if ( $attr['showImage'] ) {
										$image_data .= '<div class="wopb-block-image wopb-block-image-' . esc_attr( $attr['imgAnimation'] ) . '">';

						if ( $attr['showSale'] || $attr['showHot'] ) {
							$image_data .= '<div class="wopb-onsale-hot">';
							if ( $attr['showSale'] && $product->is_on_sale() ) {
										$image_data .= '<span class="wopb-onsale wopb-onsale-' . esc_attr( $attr['saleStyle'] ) . '">';
								if ( $attr['saleDesign'] == 'digit' ) {
									$image_data .= '-' . esc_html( $_discount ); }
								if ( $attr['saleDesign'] == 'text' ) {
									$image_data .= isset( $attr['saleText'] ) ? esc_html( $attr['saleText'] ) : esc_html__( 'Sale!', 'product-blocks' ); }
								if ( $attr['saleDesign'] == 'textDigit' ) {
									$image_data .= '-' . esc_html( $_discount ) . esc_html__( ' Off', 'product-blocks' ); }
															$image_data .= '</span>';
							}
							if ( $attr['showHot'] && $product->is_featured() ) {
														$image_data     .= '<span class="wopb-hot">';
															$image_data .= isset( $attr['hotText'] ) ? esc_html( $attr['hotText'] ) : esc_html__( 'Hot', 'product-blocks' );
														$image_data     .= '</span>';
							}
													$image_data .= '</div>';
						}

						if ( ! empty( $parsedOverlayMetaList ) ) {
							$meta_class  = $attr['ovrMetaInline'] ? ' wopb_f_inline' : '';
							$meta_class .= ! $attr['hoverMeta'] ? ' wopb-is-visible' : '';
							$image_data .= '<div class="wopb-product-new-meta' . $meta_class . '">';
							foreach ( $parsedOverlayMetaList as $meta_val ) {
								if ( $meta_val == '_wishlist' ) {
									$image_data .= apply_filters( 'wopb_grid_wishlist', '', $post_id, $attr['tooltipPosition'] );
								}
								if ( $meta_val == '_qview' ) {
									$image_data .= apply_filters( 'wopb_grid_quickview', '', $recent_posts, $post_id, $attr['tooltipPosition'] );
								}
								if ( $meta_val == '_compare' ) {
									$image_data .= apply_filters( 'wopb_grid_compare', '', $post_id, $attr['tooltipPosition'] );
								}
								if ( $meta_val == '_cart' ) {
									$cart_params['isIcon'] = true;
									$image_data           .= wopb_function()->get_add_to_cart( $product, $cart_params );
								}
							}
							$image_data .= '</div>';
						}

						if ( $attr['showCart'] ) {
							$cart_params['isIcon'] = false;
							$image_data           .= '<div class="wopb-quick-cart">';
								$image_data       .= '<div class="wopb-product-btn">';
								$image_data       .= wopb_function()->get_add_to_cart( $product, $cart_params );
								$image_data       .= '</div>';
							$image_data           .= '</div>';
						}

						if ( $attr['showDeal'] ) {
							$image_data .= wopb_function()->get_deals( $product, $attr['dealText'] );
						}

						if ( $attr['catPosition'] != 'none' && $attr['catShow'] ) {
							$image_data .= wp_kses_post( $category );
						}

						if ( $product->get_stock_status() == 'outofstock' && $attr['showOutStock'] ) {
							$image_data     .= '<div class="wopb-product-outofstock">';
								$image_data .= '<span>' . esc_html__( 'Out of stock', 'product-blocks' ) . '</span>';
							$image_data     .= '</div>';
						} elseif ( $product->get_stock_status() == 'instock' && $attr['showInStock'] ) {
							$image_data .= '<div class="wopb-product-instock">';
							$image_data .= '<span>' . esc_html__( 'In Stock', 'product-blocks' ) . '</span>';
							$image_data .= '</div>';
						}

											// Image
											$empty_image = false;
						if ( has_post_thumbnail() ) {
							$image_url = wp_get_attachment_image_url( $post_thumb_id, ( $attr['imgCrop'] ? $attr['imgCrop'] : 'full' ) );
						} else {
							$empty_image = true;
							$image_url   = esc_url( wc_placeholder_img_src( ( $attr['imgCrop'] ? $attr['imgCrop'] : 'full' ) ) );
						}
											$image_data             .= $empty_image ? '<div class="empty-image">' : '';
												$image_data         .= '<a href="' . esc_url( $titlelink ) . '">';
													$image_data     .= '<img ';
														$image_data .= 'src="' . $image_url . '" ';
														$image_data .= 'alt="' . esc_attr( $title ) . '" ';
						if ( ! empty( $item_calss['image'] ) ) {
							$image_data .= 'class=" ' . $item_calss['image'] . '" ';
							$image_data .= 'srcset="' . wp_get_attachment_image_srcset( $post_thumb_id ) . '"';
						}
													$image_data .= '/>';
													$image_data .= apply_filters( 'wopb_after_loop_image', '', $product, $post_thumb_id );
						if ( $attr['showVideo'] && ! $empty_image ) {
							$image_data .= apply_filters( 'wopb_product_video', '', $product, $post_thumb_id );
						}
						if ( ! $attr['disableFlip'] && ! $empty_image ) {
							$image_data .= apply_filters( 'wopb_flip_image', '', $product, $attr['imgCrop'] );
						}
												$image_data .= '</a>';
											$image_data     .= $empty_image ? '</div>' : '';

										$image_data .= '</div>';
					}

													$cat_price_data .= '<div class="wopb-product-cat-price">';
													// Category
					if ( ( $attr['catPosition'] == 'none' ) && $attr['catShow'] ) {
						$cat_price_data .= wp_kses_post( $category );
					}
													// Price
					if ( $attr['showPrice'] ) {
						if ( $attr['variationSwitchPosition'] == 'before_price' ) {
							$cat_price_data .= $variationSwitcher_data;
						}
						$cat_price_data .= '<div class="wopb-product-price">' . $product->get_price_html() . '</div>';
						if ( $attr['variationSwitchPosition'] == 'after_price' ) {
							$cat_price_data .= $variationSwitcher_data;
						}
					}
													$cat_price_data .= '</div>';

													$title_review_data .= '<div class="wopb-product-title-review">';
													// Title
					if ( $attr['titleShow'] ) {
						include WOPB_PATH . 'blocks/template/title.php';
						if ( $attr['variationSwitchPosition'] == 'before_title' ) {
							$title_review_data .= $variationSwitcher_data;
						}

						$title_review_data .= $title_data;

						if ( $attr['variationSwitchPosition'] == 'after_title' ) {
							$title_review_data .= $variationSwitcher_data;
						}
					}
													// Review
					if ( $attr['showReview'] ) {
						include WOPB_PATH . 'blocks/template/review.php';
						$title_review_data .= $review_data;
					}
					if ( $attr['showShortDesc'] ) {
						$description_data .= '<div class="wopb-short-description">' . wopb_function()->excerpt( $post_id, $attr['shortDescLimit'] ) . '</div>';
					}
													$title_review_data .= '</div>';

													$post_loop .= $image_data . '<div class="wopb-pg-cl ' . ( $attr['contentLayout'] ? 'wopb-pg-cl' . esc_attr( $attr['contentLayout'] ) : '' ) . '">' . $cat_price_data . $title_review_data . $description_data . '</div>';

					if ( $after_loop = apply_filters( 'wopb_after_loop_item', $content = '' ) ) {
						$post_loop .= '<div class="wopb-after-loop-item">' . $after_loop . '</div>';
					}
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
