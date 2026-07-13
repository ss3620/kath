<?php
defined( 'ABSPATH' ) || exit;

$wrapper_main_content     .= '<div class="wopb-slick-nav" style="display:none">';
	$nav                   = explode( '#', $attr['arrowStyle'] );
	$wrapper_main_content .= '<div class="wopb-slick-prev"><div class="slick-arrow slick-prev">' . wopb_function()->svg_icon( $nav[0] ) . '</div></div>';
	$wrapper_main_content .= '<div class="wopb-slick-next"><div class="slick-arrow slick-next">' . wopb_function()->svg_icon( $nav[1] ) . '</div></div>';
$wrapper_main_content     .= '</div>';
