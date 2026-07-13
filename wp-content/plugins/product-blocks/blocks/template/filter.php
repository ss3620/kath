<?php
defined( 'ABSPATH' ) || exit;
global $wpdb;

$page_post_id   = ! empty( $attr['currentPostId'] )
	? sanitize_html_class( $attr['currentPostId'] )
	: wopb_function()->get_page_post_id( wopb_function()->get_ID(), $attr['blockId'] );
$page_post_id   = $page_post_id ? $page_post_id : ( ! empty( $attr['page_post_id'] ) ? $attr['page_post_id'] : '' );
$wraper_before .= '<div class="wopb-filter-wrap" data-taxtype=' . esc_attr( $attr['filterType'] ) . ' data-blockid="' . esc_attr( $attr['blockId'] ) . '" data-blockname="product-blocks_' . esc_attr( $block_name ) . '" data-postid="' . esc_attr( $page_post_id ) . '" data-current-url="' . get_pagenum_link() . '">';
$wraper_before .= wopb_function()->filter( $attr['filterText'], $attr['filterType'], $attr['filterCat'], $attr['filterTag'], $attr['filterAction'], $attr['filterActionText'], $noAjax, $attr['filterMobileText'], $attr['filterMobile'] );
$wraper_before .= '</div>';
