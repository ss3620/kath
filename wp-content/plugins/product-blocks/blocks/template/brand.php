<?php
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- template partial included directly into a method scope; these are local render-time variables/functions, not plugin globals.

$brand = '';
if ( ! empty( $attr['brandShow'] ) && taxonomy_exists( 'product_brand' ) ) {
	$brand_terms = get_the_terms( $post_id, 'product_brand' );
	if ( ! empty( $brand_terms ) && ! is_wp_error( $brand_terms ) ) {
		$brand .= '<div class="wopb-brand-grid ' . 'wopb-cat-' . esc_attr( $attr['brandPosition'] ) . '" >';
		foreach ( $brand_terms as $val ) {
			$brand_link_href = ' href="' . esc_url( get_term_link( $val->term_id ) ) . '"';
			if ( isset( $attr['enableBrandLink'] ) && ! $attr['enableBrandLink'] ) {
				$brand_link_href = '';
			}
			$brand .= '<a' . $brand_link_href . '>' . esc_html( $val->name ) . '</a>';
		}
		$brand .= '</div>';
	}
}
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
