<?php
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- template partial included directly into a method scope; these are local render-time variables/functions, not plugin globals.
global $wpdb;

$page_post_id   = ! empty( $attr['currentPostId'] )
	? absint( $attr['currentPostId'] )
	: absint( wopb_function()->get_page_post_id( wopb_function()->get_ID(), $attr['blockId'] ) );
$page_post_id   = $page_post_id ? $page_post_id : ( ! empty( $attr['page_post_id'] ) ? absint( $attr['page_post_id'] ) : '' );
$wraper_before .= '<div class="wopb-filter-wrap" data-taxtype="' . esc_attr( $attr['filterType'] ) . '" data-blockid="' . esc_attr( $attr['blockId'] ) . '" data-blockname="product-blocks_' . esc_attr( $block_name ) . '" data-postid="' . esc_attr( $page_post_id ) . '" data-current-url="' . esc_url( get_pagenum_link() ) . '">';
$wraper_before .= wopb_function()->filter( $attr['filterText'], $attr['filterType'], $attr['filterCat'], $attr['filterTag'], $attr['filterAction'], $attr['filterActionText'], $noAjax, $attr['filterMobileText'], $attr['filterMobile'] );
$wraper_before .= '</div>';
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
