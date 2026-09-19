<?php
defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- template partial included directly into a method scope; these are local render-time variables/functions, not plugin globals.

$review_data         .= '<div class="wopb-star-rating">';
	$review_data     .= '<span style="width: ' . esc_attr( $rating_average ? ( ( $rating_average / 5 ) * 100 ) : 0 ) . '%">';
		$review_data .= '<strong itemprop="ratingValue" class="wopb-rating">' . esc_html( $rating_average ) . '</strong>';
		$review_data .= esc_html__( 'out of 5', 'product-blocks' );
	$review_data     .= '</span>';
$review_data         .= '</div>';
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals
