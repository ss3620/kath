<?php
namespace WOPB\blocks;

use WOPB\Includes\Durbin\Xpo;

defined( 'ABSPATH' ) || exit;

class Filter {

	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
		add_action( 'wc_ajax_wopb_show_more_filter_item', array( $this, 'show_more_callback' ) );
		add_action( 'wp_ajax_nopriv_wopb_show_more_filter_item', array( $this, 'show_more_callback' ) );
	}

	public function get_attributes() {
		return array(
			'repeatableFilter'           => array(
				// 1 => array('type' => 'price','label' => 'Filter By Price'),
				0 => array(
					'type'  => 'search',
					'label' => __( 'Filter By Search', 'product-blocks' ),
				),
				1 => array(
					'type'  => 'product_cat',
					'label' => __( 'Filter By Category', 'product-blocks' ),
				),
				2 => array(
					'type'  => 'product_tag',
					'label' => __( 'Filter By Tag', 'product-blocks' ),
				),
				3 => array(
					'type'  => 'status',
					'label' => __( 'Filter By Status', 'product-blocks' ),
				),
				4 => array(
					'type'  => 'rating',
					'label' => __( 'Filter By Rating', 'product-blocks' ),
				),
				5 => array(
					'type'  => 'pa_color',
					'label' => __( 'Filter By Color', 'product-blocks' ),
				),
			),
			'sortingItems'               => array(
				0 => (object) array(
					'label' => __( 'Select Sort By', 'product-blocks' ),
					'value' => '',
				),
				1 => (object) array(
					'label' => __( 'Default Sorting', 'product-blocks' ),
					'value' => 'default',
				),
				2 => (object) array(
					'label' => __( 'Sort by popularity', 'product-blocks' ),
					'value' => 'popular',
				),
				3 => (object) array(
					'label' => __( 'Sort by latest', 'product-blocks' ),
					'value' => 'latest',
				),
				4 => (object) array(
					'label' => __( 'Sort by average rating', 'product-blocks' ),
					'value' => 'rating',
				),
				5 => (object) array(
					'label' => __( 'Sort by price: low to high', 'product-blocks' ),
					'value' => 'price_low',
				),
				6 => (object) array(
					'label' => __( 'Sort by price: high to low', 'product-blocks' ),
					'value' => 'price_high',
				),
			),
			'blockTarget'                => '',
			'clearFilter'                => true,
			'filterHeading'              => true,
			'productCount'               => true,
			'expandTaxonomy'             => false,
			'enableTaxonomyRelation'     => false,
			'viewTaxonomyLimit'          => 10,
			'togglePlusMinus'            => true,
			'togglePlusMinusInitialOpen' => true,
			'toggleInitialMobile'        => false,
			'filterHeadText'             => 'Filter',
			'currentPostId'              => '',
			'searchPlaceHolder'          => 'Search Products...',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/filter',
			array(
				'editor_script'   => 'wopb-blocks-editor-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	/**
	 * This
	 *
	 * @return string
	 */
	public function content( $attr ) {
		$attr = wp_parse_args( $attr, $this->get_attributes() );

		$is_visible = true;
		if ( isset( $attr['blockPubDate'] ) && $attr['blockPubDate'] != 'empty' ) {
			$is_visible = Xpo::is_pro_feature_visible( $attr['blockPubDate'] );
		}
		$is_active = Xpo::is_lc_active();

		$page_post_id = ! empty( $attr['currentPostId'] ) ? $attr['currentPostId'] : wopb_function()->get_ID();
		if ( ( $is_active || $is_visible ) && $post = get_post( $page_post_id ) ) {
			$is_mobile           = wp_is_mobile();
			$html                = $wraper_before = '';
			$block_name          = 'filter';
			$attr['headingShow'] = true;
			$wrapper_class       = '';
			$wrapper_class      .= 'wopb-filter-block-front-end ';
			if ( $attr['togglePlusMinus'] ) {
				if ( ! $attr['togglePlusMinusInitialOpen'] || ( $is_mobile && $attr['toggleInitialMobile'] ) ) {
					$wrapper_class .= ' wopb-filter-toggle-initial-close';
				} elseif ( $attr['togglePlusMinusInitialOpen'] ) {
					$wrapper_class .= ' wopb-filter-toggle-initial-open';
				}
			}

			$attr['className']   = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
			$attr['blockTarget'] = ! empty( $attr['blockTarget'] ) ? sanitize_html_class( $attr['blockTarget'] ) : '';

			$wraper_before     .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . '">';
				$wraper_before .= '<div class="wopb-product-wrapper wopb-filter-block ' . $wrapper_class . '" data-postid = "' . $page_post_id . '" data-block-target = "' . $attr['blockTarget'] . '" data-current-url="' . get_pagenum_link() . '">';

			if ( $attr['filterHeading'] || $is_mobile ) {
				$html     .= '<div class="wopb-filter-title-section">';
					$html .= '<span class="wopb-filter-title">' . wp_kses( $attr['filterHeadText'], wopb_function()->allowed_html_tags() ) . '</span>';
					$html .= '<span class="dashicons dashicons-filter wopb-filter-icon"></span>';
				$html     .= '</div>';
			}
			if ( $attr['clearFilter'] ) {
				ob_start();
				$this->removeFilterItem();
				$html .= ob_get_clean();
			}
				$html        .= $this->filter_content( $attr, $post );
				$wraper_after = '</div>';
			$wraper_after    .= '</div>';

			$content = $wraper_before . $html . $wraper_after;
			add_action(
				'wopb_footer',
				function () use ( $content ) {
					$in_footer_safe = wopb_function()->wp_kses_safe( $this->filter_in_footer( $content ) );
                echo $in_footer_safe; // phpcs:ignore
				}
			);

			return $content;
		}
	}

	/**
	 * Filter in Footer Hook
	 *
	 * @param $filter_content
	 * @return string
	 * @since v.4.0.6
	 */
	public function filter_in_footer( $filter_content ) {
		$filter_modal                  = '';
		$filter_modal                 .= '<div class="wopb-filter-modal">';
			$filter_modal             .= '<div class="wopb-modal-overlay"></div>';
			$filter_modal             .= '<div class="wopb-filter-content">';
				$filter_modal         .= '<div class="wopb-modal-header">';
					$filter_modal     .= '<a class="wopb-modal-close">';
						$filter_modal .= wopb_function()->svg_icon( 'close' );
					$filter_modal     .= '</a>';
					$filter_modal     .= '<span class="wopb-modal-title">';
						$filter_modal .= esc_html__( 'Filter', 'product-blocks' );
					$filter_modal     .= '</span>';
				$filter_modal         .= '</div>';
			$filter_modal             .= $filter_content;
			$filter_modal             .= '</div>';
		$filter_modal                 .= '</div>';
		return $filter_modal;
	}

	/**
	 * Filter Content
	 *
	 * @since v.4.0.6
	 * @return string
	 */
	public function filter_content( $attr, $post ) {
		$counter           = 0;
		$blocks            = parse_blocks( $post->post_content );
		$target_block_attr = array();
		$target_block_attr = $this->getTargetBlockAttributes( $attr, $blocks, $target_block_attr );
		$queried_object    = is_product_taxonomy() ? get_queried_object() : '';
		$query_term        = array_merge(
			array(
				'query_tax'  => $queried_object && $queried_object->taxonomy ? $queried_object->taxonomy : '',
				'query_term' => $queried_object && $queried_object->term_id ? $queried_object->term_id : '',
			),
			$target_block_attr
		);

		$html = '<form autocomplete="off" action="javascript:">';
		foreach ( $attr['repeatableFilter'] as $active_filter ) {
			$section_class = 'wopb-filter-' . $active_filter['type'];
			$body_class    = '';
			$content       = '';

			ob_start();
			switch ( $active_filter['type'] ) {
				case 'search':
					$this->search_filter( $attr['searchPlaceHolder'] );
					break;
				case 'price':
					$body_class = ' wopb-price-range-slider';
					$this->price_filter();
					break;
				case 'status':
					$this->status_filter( $attr, $query_term );
					break;
				case 'rating':
					$this->rating_filter();
					break;
				case 'sort_by':
					$this->sorting_filter( $attr );
					break;

				default:
					$attr['viewTaxonomyLimit'] = ! empty( $attr['viewTaxonomyLimit'] ) ? intval( $attr['viewTaxonomyLimit'] ) : 10;
					$object_taxonomies         = array_diff( get_object_taxonomies( 'product' ), array( 'product_type', 'product_visibility', 'product_shipping_class' ) );

					foreach ( $object_taxonomies as $key ) {
						if ( $key === $active_filter['type'] ) {
							$term_query = array_merge(
								array(
									'taxonomy' => $key,
									'limit'    => $attr['viewTaxonomyLimit'],
								),
								$query_term
							);
							if ( $queried_object && $key == $queried_object->taxonomy && $queried_object->term_id ) {
								$term_query['parent'] = $queried_object->term_id;
							}
							if ( is_search() ) {
								$term_query['search_query'] = get_search_query();
							}
							$params = $this->get_term_data( $term_query );
							if ( ! empty( $params['terms'] ) ) {
								++$counter;
								$section_class        = isset( $key ) ? ' wopb-filter-' . esc_attr( $key ) : '';
								$params['attributes'] = array(
									'productCount'      => $attr['productCount'],
									'expandTaxonomy'    => $attr['expandTaxonomy'],
									'viewTaxonomyLimit' => $attr['viewTaxonomyLimit'],
								);
								$this->product_taxonomy_filter( $params );
							}
						}
					}
			}
			$content .= ob_get_clean();

			if ( $content ) {
				$html .= '<div class="wopb-filter-section ' . $section_class . '">';
				if ( $attr['enableTaxonomyRelation'] && $counter == 1 ) {
					ob_start();
						$this->taxonomy_relation();
					$html .= ob_get_clean();
				}
					// Header content
					$html         .= '<div class="wopb-filter-header">';
						$html     .= '<span class="wopb-filter-label">';
							$html .= ! empty( $active_filter['label'] ) ? wp_kses_post( $active_filter['label'] ) : '';
						$html     .= '</span>';
				if ( $attr['togglePlusMinus'] ) {
					$html .= '<div class="wopb-filter-toggle">';
					$html .= '<span class="dashicons dashicons-plus-alt2 wopb-filter-plus"></span>';
					$html .= '<span class="dashicons dashicons-minus wopb-filter-minus"></span>';
					$html .= '</div>';
				}
						$html .= '</div>';

						// Body content
						$html .= '<div class="wopb-filter-body' . $body_class . '">';
						$html .= $content;
						$html .= '</div>';

						$html .= '</div>';
			}
		}

			ob_start();
				$this->reset_filter();
			$html .= ob_get_clean();
		$html     .= '</form>';
		return $html;
	}

	/**
	 * Get Term by Taxonomy
	 *
	 * @param $args
	 * @return array
	 * @since v.4.1.1
	 */
	public function get_term_data( $args ) {
		global $wp_query;
		$query_vars = $wp_query->query_vars;

		if ( ! str_starts_with( $args['taxonomy'], 'pa_' ) && empty( $args['parent'] ) ) {
			$args['parent'] = 0;
		}

		$term_query = array(
			'taxonomy'   => $args['taxonomy'],
			'hide_empty' => true,
		);

		$product_ids = $this->get_product_ids( $args );
		$taxonomy    = $args['taxonomy'];

		// Object id check for parent term
		$term_query['object_ids'] = empty( $product_ids ) ? 'wopb_empty' : $product_ids;

		// Object id check also for child term
		$filter = function ( $clauses, $taxonomies, $args ) use ( $product_ids, $taxonomy ) {
			return $this->term_query_modify( $clauses, $product_ids, $taxonomy );
		};
		add_filter( 'terms_clauses', $filter, 10, 3 );

		// exclude category from wholesalex
		if ( $args['taxonomy'] == 'product_cat' ) {
			if ( ! empty( $query_vars['__wholesalex_exclude_cat'] ) ) {
				$term_query['exclude'] = $query_vars['__wholesalex_exclude_cat']; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			}
			if ( ! empty( $query_vars['__wholesalex_include_cat'] ) ) {
				$term_query['include'] = $query_vars['__wholesalex_include_cat']; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostIn_include
			}
		}

		// check taxonomy from grid and sync with query
		if ( ! empty( $args['queryTaxValue'] ) && is_array( $args['queryTaxValue'] ) ) {
			foreach ( $args['queryTaxValue'] as $tax ) {
				if ( ! is_array( $tax ) ) {
					$tax = (array) $tax;
				}
				if (
					empty( $args['query_term'] ) &&
					empty( $args['child_check'] ) &&
					! empty( $tax['value'] )
				) {
					if ( $args['taxonomy'] == $args['query_tax'] ) {
						$term_query['slug'][] = $tax['value'];
						unset( $args['parent'] );
					}
					$args['tax_slug'][] = $tax['value'];
				}
			}
		}

		// term fetch by parent
		if ( isset( $args['parent'] ) && $args['parent'] !== '' ) {
			$term_query['parent'] = $args['parent'];
		}
		$total_terms = 0;
		$terms       = array();
		if ( $term_query['object_ids'] != 'wopb_empty' ) {
			$total_terms = count( get_terms( array_merge( array( 'fields' => 'ids' ), $term_query ) ) );
			$terms       = get_terms( $term_query );
		}
		$term_query['number'] = $args['limit'];
		$term_query['offset'] = ! empty( $args['offset'] ) ? $args['offset'] : 0;
		remove_filter( 'terms_clauses', $filter, 10 );
		return array(
			'query_params' => array(
				'taxonomy'      => $args['taxonomy'],
				'query_tax'     => $args['query_tax'],
				'query_term'    => $args['query_term'],
				'queryTaxValue' => ! empty( $args['queryTaxValue'] ) ? $args['queryTaxValue'] : '',
				'parent'        => ! empty( $args['parent'] ) ? $args['parent'] : '',
				'limit'         => $args['limit'],
				'search_query'  => ! empty( $term_query['search_query'] ) ? $term_query['search_query'] : '',
				'tax_slug'      => ! empty( $args['tax_slug'] ) ? $args['tax_slug'] : '',
			),
			'total_terms'  => $total_terms,
			'terms'        => $terms,
		);
	}

	/**
	 * Modify deafult term query for product count by parent and child
	 *
	 * @param $clauses
	 * @param $product_ids
	 * @param $taxonomy
	 * @return array
	 * @since v.4.1.7
	 */
	public function term_query_modify( $clauses, $product_ids, $taxonomy ) {
		global $wpdb;
		$product_ids      = implode( ',', array_map( 'intval', $product_ids ) );
		$clauses['join']  = preg_replace(
			'/INNER JOIN wp_term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id/',
			'LEFT JOIN wp_term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id',
			$clauses['join']
		);
		$clauses['where'] = preg_replace(
			'/AND tr\.object_id IN \([^\)]+\)/',
			"AND ( tr.object_id IN ($product_ids) 
                                OR tt.term_id IN (
                                    SELECT parent FROM {$wpdb->term_taxonomy} as term_tax_2
                                    WHERE taxonomy = '$taxonomy' 
                                    AND term_id IN (
                                        SELECT term_tax_3.term_id 
                                        FROM {$wpdb->term_taxonomy} AS term_tax_3
                                        INNER JOIN {$wpdb->term_relationships} AS term_relation_2 
                                        ON term_tax_3.term_taxonomy_id = term_relation_2.term_taxonomy_id 
                                        WHERE term_relation_2.object_id IN ($product_ids)
                                    )
                                ) )",
			$clauses['where']
		);
		return $clauses;
	}

	public function get_product_ids( $args ) {
		global $wp_query;
		$query_vars = $wp_query->query_vars;

		$query_args = array(
			'posts_per_page' => -1,
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);
		if ( ! empty( $args['search_query'] ) ) {
			$query_args['s'] = $args['search_query'];
		}
		$tax_query   = array();
		$tax_query[] = array(
			'taxonomy' => $args['taxonomy'],
			'field'    => 'term_id',
			'terms'    => '',
			'operator' => 'EXISTS',
		);
		$tax_query[] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'name',
			'terms'    => 'exclude-from-catalog',
			'operator' => 'NOT IN',
		);

		// check taxonomy from grid and sync with query
		if ( ! empty( $args['query_tax'] ) && ! empty( $args['tax_slug'] ) ) {
			$tax_query[] = array(
				'taxonomy' => $args['query_tax'],
				'field'    => 'slug',
				'terms'    => $args['tax_slug'],
				'operator' => 'IN',
			);
		}

		// check taxonomy in taxonomy / archive page
		if ( ! empty( $args['query_term'] ) ) {
			$tax_query[] = array(
				'taxonomy' => $args['query_tax'],
				'field'    => 'id',
				'terms'    => $args['query_term'],
				'operator' => 'IN',
			);
		}

		// check taxonomy and term when product count
		if ( ! empty( $args['taxonomy'] ) && ! empty( $args['query_count_term'] ) ) {
			$tax_query[] = array(
				'taxonomy' => $args['taxonomy'],
				'field'    => 'id',
				'terms'    => $args['query_count_term'],
				'operator' => 'IN',
			);
		}

		// post not in query from $query_vars
		if ( ! empty( $query_vars['post__not_in'] ) ) {
            $query_args['post__not_in'] = $query_vars['post__not_in']; // phpcs:ignore
		}
        $query_args['tax_query'] = $tax_query; //phpcs:ignore
		return get_posts( $query_args );
	}


	public function removeFilterItem() {
		?>
		<div class="wopb-filter-remove-section">
			<div class="wopb-active-items"></div>
			<span class="wopb-filter-remove-all">
				<?php esc_html_e( 'Clear All', 'product-blocks' ); ?> <span class="dashicons dashicons-no-alt wopb-filter-remove-icon">
			</span>
		</div>
		<?php
	}

	public function search_filter( $searchPlaceholder = 'Search Products...' ) {
		?>
	<input type="hidden" class="wopb-filter-slug" value="search">
	<div class="wopb-search-filter-body">
		<input type="text" class="wopb-filter-search-input" placeholder="<?php echo esc_attr( $searchPlaceholder ); ?>" />
        <span class="wopb-search-icon"><?php echo wp_kses( wopb_function()->svg_icon('search'), wopb_function()->allowed_html_tags() ); // phpcs:ignore ?></span>
	</div>
		<?php
	}

	public function price_filter() {
		$max_price = $this->max_price();
		?>
	<input type="hidden" class="wopb-filter-slug" value="price">
	<div class="wopb-price-range">
		<span class="wopb-price-range-bar"></span>
		<input type="range" class="wopb-price-range-input wopb-min-range" min="0" max="<?php echo esc_attr( $max_price ); ?>" value="0" step="1">
		<input type="range" class="wopb-price-range-input wopb-max-range" min="0" max="<?php echo esc_attr( $max_price ); ?>" value="<?php echo esc_attr( $max_price ); ?>" step="1">
	</div>
	<span class="wopb-input-group">
		<input type="number" class="wopb-filter-price-input wopb-min-price" value="0" min="0">
		<input type="number" class="wopb-filter-price-input wopb-max-price" value="<?php echo esc_attr( $max_price ); ?>" min="0" max="<?php echo esc_attr( $max_price ); ?>">
	</span>
		<?php
	}

	public function status_filter( $attr, $term_query ) {
		?>
	<input type="hidden" class="wopb-filter-slug" value="status">
	<div class="wopb-filter-check-list">
		<?php
		foreach ( wc_get_product_stock_status_options() as $key => $status ) {
			$count = wopb_function()->generate_stock_status_count_query( $key, $term_query );
			?>
			<div class="wopb-filter-item">
				<div class="wopb-item-content">
					<?php //removed the for from label, because with it, mobile version click misbehaves - shihab. ?>
					<?php // when label wraps input, no need for 'for' attribute - shihab. ?>
					<label>
						<input type="checkbox" class="wopb-status-input" id="status_<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $key ); ?>"/>
					<?php echo esc_html( $status ); ?>
					<?php
					if ( $attr['productCount'] ) {
						echo esc_html( '(' . $count . ')' ); }
					?>
					</label>
				</div>
			</div>
		<?php } ?>
	</div>
		<?php
	}

	public function rating_filter() {
		?>
	<input type="hidden" class="wopb-filter-slug" value="rating">
	<div class="wopb-filter-check-list wopb-filter-ratings">
		<?php for ( $row = 5; $row > 0; $row-- ) { ?>
			<div class="wopb-filter-item">
				<div class="wopb-item-content">
					<?php //removed the 'for' from label, because with it, mobile version click misbehaves - shihab. ?>
					<?php // when label wraps input, no need for 'for' attribute - shihab. ?>
					<label>
						<input type="checkbox" class="wopb-rating-input" value="<?php echo esc_attr( $row ); ?>" id="filter-rating-<?php echo esc_attr( $row ); ?>">
						<?php for ( $filledStar = $row; $filledStar > 0; $filledStar-- ) { ?>
							<span class="dashicons dashicons-star-filled"></span>
						<?php } ?>
						<?php for ( $emptyStar = 0; $emptyStar < 5 - $row; $emptyStar++ ) { ?>
							<span class="dashicons dashicons-star-empty"></span>
						<?php } ?>
					</label>
				</div>
			</div>
		<?php } ?>
	</div>
		<?php
	}

	public function taxonomy_relation() {
		?>
		<div class="wopb-taxonomy-relation">
			<div class="wopb-relation-heading"><?php echo esc_html__( 'Taxonomy Relation', 'product-blocks' ); ?></div>
			<div class="wopb-filter-body">
				<input type="hidden" class="wopb-filter-slug" value="tax_relation">
				<?php //removed the 'for' from label, because with it, mobile version click misbehaves - shihab. ?>
				<?php // when label wraps input, no need for 'for' attribute - shihab. ?>
				<label>
					<input name="tax_relation" type="radio" class="wopb-filter-tax-relation" id="wopb_tax_relation_and" value="AND" checked>
					<span>AND</span>
				</label>
				<label>
					<input name="tax_relation" type="radio" class="wopb-filter-tax-relation" id="wopb_tax_relation_or" value="OR">
					<span>OR</span>
				</label>
			</div>
		</div>
		<?php
	}

	public function product_taxonomy_filter( $params ) {
		$attr = $params['attributes'];
		?>
		<input
			type="hidden"
			class="wopb-filter-slug"
			value="product_taxonomy"
			data-taxonomy="<?php echo esc_attr( $params['query_params']['taxonomy'] ); ?>"
			data-query="<?php echo esc_attr( json_encode( $params['query_params'] ) ); ?>"
			data-attributes="<?php echo esc_attr( json_encode( $attr ) ); ?>"
		/>
		<div class="wopb-filter-check-list">
			<?php
				! empty( $params['query_params']['taxonomy'] ) ? $this->product_taxonomy_terms( $attr, $params ) : '';
			?>
		</div>
		<?php
		if ( ! empty( $params['total_terms'] ) && $params['total_terms'] > $attr['viewTaxonomyLimit'] ) {
			$total_page = $params['total_terms'] / $attr['viewTaxonomyLimit'];
			$total_page = ceil( (float) $total_page );

			?>
				<a
					href="javascript:"
					class="wopb-filter-extend-control wopb-filter-show-more"
					data-current-page="1"
					data-total-page="<?php echo esc_attr( $total_page ); ?>"
				>
				<?php esc_html_e( 'Show More', 'product-blocks' ); ?>
				</a>
				<a
					href="javascript:"
					class="wopb-filter-extend-control wopb-filter-show-less"
					data-current-page="1"
				>
				<?php esc_html_e( 'Show Less', 'product-blocks' ); ?>
				</a>
		<?php } ?>
		<?php
	}


	/**
	 * Show Term by Taxonomy
	 *
	 * @param $attr
	 * @param $params
	 * @return null
	 * @since v.2.5.3
	 */
	public function product_taxonomy_terms( $attr, $params ) {
		$query_params = $params['query_params'];
		// do not make stict checking. loose comparison is intentional.
		$is_expand_taxonomy = isset( $attr['expandTaxonomy'] ) && 'true' == $attr['expandTaxonomy'];
		?>
		<?php
		foreach ( $params['terms'] as $term ) {
			$extended_item_class              = isset( $params['show_more'] ) ? 'wopb-filter-extended-item' : '';
			$query_params['query_count_term'] = $term->term_id;
			?>
			<div class="wopb-filter-item <?php echo esc_attr( $extended_item_class ); ?>">
				<div class="wopb-item-content">
					<?php //removed the for from label, because with it, mobile version click misbehaves - shihab. ?>
					<?php // when label wraps input, no need for 'for' attribute - shihab. ?>
					<label>
						<input
							type="checkbox"
							class="wopb-filter-tax-term-input"
							id="tax_term_<?php echo esc_attr( $term->name . '_' . $term->term_id ); ?>"
							value="<?php echo esc_attr( $term->term_id ); ?>"
							data-label="<?php echo esc_attr( $term->name ); ?>"
						/>
					<?php
					if (
						strpos( $query_params['taxonomy'], 'pa_' ) === 0 &&
						$tax_attribute = wopb_function()->get_attribute_by_taxonomy( $query_params['taxonomy'] )
					) {
						$attr_value = get_term_meta( $term->term_id, $tax_attribute->attribute_type, true );
						if ( $tax_attribute->attribute_type === 'color' ) {
							$color_html      = $attr_value ? '<span class="wopb-filter-tax-color" style="background-color: ' . esc_attr( $attr_value ) . ';"></span>' : '';
							$color_html_safe = wopb_function()->wp_kses_safe( $color_html );
							echo $color_html_safe; // phpcs:ignore
						} elseif ( $tax_attribute->attribute_type === 'image' ) {
							if ( $attr_value ) {
								echo wp_get_attachment_image( $attr_value );
							} else {
								echo '<img src="' . esc_url( WOPB_URL . '/assets/img/fallback.svg' ) . '" />';
							}
						}
					}
					?>
						<span>
							<?php
							echo esc_html( $term->name );
							if ( $attr['productCount'] ) {
								echo esc_html( ' (' . count( $this->get_product_ids( $query_params ) ) . ')' );
							}
							?>
						</span>
					</label>
					<?php
					if ( ! str_starts_with( $query_params['taxonomy'], 'pa_' ) ) {
						$query_params['parent']      = $term->term_id;
						$query_params['child_check'] = true;
						$child_data                  = $this->get_term_data( $query_params );
						if ( ! empty( $child_data['terms'] ) ) {
							$params = array_merge( $child_data, array( 'taxonomy' => $query_params['taxonomy'] ) );
							?>
						<div class="wopb-filter-check-list
							<?php
								echo $is_expand_taxonomy
									? ''
									: esc_attr( ' wopb-d-none' )
							?>
							"
						>
							<?php $this->product_taxonomy_terms( $attr, $params ); ?>
						</div>
							<?php
						}
					}
					?>
				</div>
				<?php if ( ! empty( $child_data['terms'] ) ) { ?>
					<div class="wopb-filter-child-toggle">
						<span class="dashicons dashicons-arrow-right-alt2 wopb-filter-right-toggle<?php echo esc_attr( $is_expand_taxonomy ? ' wopb-d-none' : '' ); ?>"></span>
						<span class="dashicons dashicons-arrow-down-alt2 wopb-filter-down-toggle<?php echo esc_attr( $is_expand_taxonomy ? '' : ' wopb-d-none' ); ?>"></span>
					</div>
				<?php } ?>
			</div>
		<?php } ?>
		<?php
	}

	public function sorting_filter( $attr ) {
		?>
		<input type="hidden" class="wopb-filter-slug" value="sorting">
		<select name="sortBy" class="select wopb-filter-sorting-input">
			<?php 
			foreach ( $attr['sortingItems'] as $item ) { ?>
				<option value="<?php echo esc_attr( $item->value ); ?>">
					<?php echo esc_html( $item->label ); ?>
				</option>
			<?php } ?>
		</select>
		<?php
	}

	public function reset_filter() {
		$queried_object     = get_queried_object();
		$slug               = '';
		$current_page_value = '';
		$taxonomy           = '';
		if ( is_product_taxonomy() ) {
			$slug               = 'product_taxonomy';
			$current_page_value = $queried_object->term_id;
			$taxonomy           = $queried_object->taxonomy;
		} elseif ( is_search() ) {
			$slug               = 'product_search';
			$current_page_value = get_search_query();
		}
		?>
		<div class="wopb-filter-section wopb-filter-reset-section">
			<div class="wopb-filter-body">
				<input type="hidden" class="wopb-filter-slug wopb-filter-slug-reset wopb-d-none" value="reset">
				<?php if ( isset( $slug ) ) { ?>
					<input type="hidden" class="wopb-filter-current-page wopb-d-none" value="<?php echo esc_attr( $current_page_value ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>" data-taxonomy="<?php echo esc_attr( $taxonomy ); ?>">
				<?php } ?>
			</div>
		</div>
		<?php
	}

	public function max_price() {
		global $wpdb;
        // phpcs:ignore
        $max_price = $wpdb->get_var("
            SELECT MAX(CAST(meta_value AS DECIMAL(10,2)))
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_price'
        "
		);
		return ceil( (float) $max_price );
	}


	/**
	 * Show more filter item by ajax
	 *
	 * @since v.2.5.3
	 * @return null
	 */
	public function show_more_callback() {
		if (
			! isset( $_POST['wpnonce'] ) ||
			! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['wpnonce'] ) ), 'wopb-nonce' )
		) {
			return;
		}
        $query = wopb_function()->rest_sanitize_params( wp_unslash( $_POST['query'] ) ); // phpcs:ignore
        $attr = wopb_function()->rest_sanitize_params( $_POST['attributes'] ); // phpcs:ignore
        $query['offset'] = ( sanitize_text_field( wp_unslash( $_POST['current_page'] ) ) - 1) * $query['limit']; // phpcs:ignore
		$params          = $this->get_term_data( $query );
		if ( ! empty( $params['terms'] ) ) {
			$params['show_more'] = true;
			ob_start();
			$this->product_taxonomy_terms( $attr, $params );
			$product_taxonomy_terms_safe = wopb_function()->wp_kses_safe( ob_get_clean() );
            echo $product_taxonomy_terms_safe; // phpcs:ignore
		}
		wp_die();
	}

	/**
	 * Get targeted filter block attribute
	 *
	 * @param $attr
	 * @param $blocks
	 * @param $target_block_attr
	 * @return array
	 * @since v.2.5.4
	 */
	public function getTargetBlockAttributes( $attr, $blocks, &$target_block_attr ) {
		foreach ( $blocks as $block ) {
			if ( $block['blockName'] == 'product-blocks/' . $attr['blockTarget'] ) {
				if ( ! empty( $block['attrs']['queryTaxValue'] ) ) {
					$target_block_attr = array_merge(
						$target_block_attr,
						array(
							'query_tax'     => ! empty( $block['attrs']['queryTax'] ) ? $block['attrs']['queryTax'] : 'product_cat',
							'queryTaxValue' => json_decode( $block['attrs']['queryTaxValue'] ),
						)
					);
				}
			} elseif ( count( $block['innerBlocks'] ) > 0 ) {
				$this->getTargetBlockAttributes( $attr, $block['innerBlocks'], $target_block_attr );
			}
		}
		return $target_block_attr;
	}
}
