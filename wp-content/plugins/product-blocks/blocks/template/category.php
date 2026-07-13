<?php
defined( 'ABSPATH' ) || exit;

$category = '';
if ( $attr['catShow'] ) {
	$category .= '<div class="wopb-category-grid ' . 'wopb-cat-' . esc_attr( $attr['catPosition'] ) . '" >';
	$cat       = get_the_terms( $post_id, 'product_cat' );
	if ( ! empty( $cat ) ) {
		foreach ( $cat as $val ) {
			$cat_link_href = ' href="' . esc_url( get_term_link( $val->term_id ) ) . '"';
			if ( isset( $attr['enableCatLink'] ) && ! $attr['enableCatLink'] ) {
				$cat_link_href = '';
			}
			$category .= '<a' . $cat_link_href . '>' . esc_html( $val->name ) . '</a>';
		}
	}
	$category .= '</div>';
}
