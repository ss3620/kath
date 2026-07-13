<?php
defined( 'ABSPATH' ) || exit;
global $wpdb;
global $wp_query;
$query_vars        = $wp_query->query_vars;
$queried_object    = get_queried_object();
$page_post_id      = ! empty( $attr['currentPostId'] )
	? sanitize_html_class( $attr['currentPostId'] )
	: wopb_function()->get_page_post_id( wopb_function()->get_ID(), $attr['blockId'] );
$page_post_id      = $page_post_id ? $page_post_id : ( ! empty( $attr['page_post_id'] ) ? $attr['page_post_id'] : '' );
$filter_attributes = array();
if ( isset( $attr['product_filters'] ) ) {
	$filter_attributes['product_filters'] = $attr['product_filters'];
} elseif ( isset( $attr['queryTax'] ) ) {
	$filter_attributes['queryTax'] = $attr['queryTax'];
	if ( isset( $attr['queryCatAction'] ) ) {
		$filter_attributes['queryCatAction'] = $attr['queryCatAction'];
	} elseif ( isset( $attr['queryTagAction'] ) ) {
		$filter_attributes['queryTagAction'] = $attr['queryTagAction'];
	}
}
if ( is_product_taxonomy() && ! isset( $attr['product_filters'] ) ) {
	$filter_attributes['productTaxonomy'] = array(
		'taxonomy' => $queried_object->taxonomy,
		'term_ids' => array( $queried_object->term_id ),
	);
}
if ( isset( $query_vars['post__not_in'] ) ) {
	$filter_attributes['post__not_in'] = $query_vars['post__not_in']; // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
}

$data_filter_attributes = ' data-filter-attributes=' . wp_json_encode( $filter_attributes );
$wrapper_main_content  .= '<div data-archive-builder="'
							. ( ( is_archive() || is_search() || is_product_taxonomy() || is_product_tag() ) && ! is_shop() ) . '"
							 data-search-block="' . is_search() . '"
							 class="wopb-pagination-wrap' . ( $attr['paginationAjax'] ? ' wopb-pagination-ajax-action' : '' ) . '"
							 data-paged="1"
							 data-blockid="' . esc_attr( $attr['blockId'] ) . '"
							 data-postid="' . esc_attr( $page_post_id ) . '"
							 data-pages="' . esc_attr( $pageNum ) . '"
							 data-blockname="product-blocks_' . esc_attr( $block_name ) . '"
							 ' . wopb_function()->get_builder_attr() . $data_filter_attributes . '>';
$wrapper_main_content  .= wopb_function()->pagination( $pageNum, $attr['paginationNav'], $attr['paginationText'], $attr );
$wrapper_main_content  .= '</div>';
