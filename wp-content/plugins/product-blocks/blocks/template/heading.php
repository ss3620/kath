<?php
defined( 'ABSPATH' ) || exit;

if ( $attr['headingShow'] ) {
	$allowed_html_tags      = wopb_function()->allowed_html_tags();
	$attr['headingTag']     = in_array( $attr['headingTag'], wopb_function()->allowed_block_tags() ) ? $attr['headingTag'] : 'h2';
	$attr['headingText']    = wp_kses( $attr['headingText'], $allowed_html_tags );
	$attr['headingBtnText'] = wp_kses( $attr['headingBtnText'], $allowed_html_tags );
	$attr['subHeadingText'] = wp_kses( $attr['subHeadingText'], $allowed_html_tags );
	$wraper_before         .= '<div class="wopb-heading-wrap wopb-heading-' . sanitize_html_class( $attr['headingStyle'] ) . ' wopb-heading-' . sanitize_html_class( $attr['headingAlign'] ) . '">';
	if ( $attr['headingURL'] ) {
		$wraper_before .= '<' . $attr['headingTag'] . ' class="wopb-heading-inner"><a href="' . esc_url( $attr['headingURL'] ) . '"><span>' . $attr['headingText'] . '</span></a></' . $attr['headingTag'] . '>';
	} else {
		$wraper_before .= '<' . $attr['headingTag'] . ' class="wopb-heading-inner"><span>' . $attr['headingText'] . '</span></' . $attr['headingTag'] . '>';
	}
	if ( $attr['headingStyle'] == 'style11' && $attr['headingURL'] && $attr['headingBtnText'] ) {
		$wraper_before .= '<a class="wopb-heading-btn" href="' . esc_url( $attr['headingURL'] ) . '">' . $attr['headingBtnText'] . wopb_function()->svg_icon( 'rightArrowLg' ) . '</a>';
	}
	if ( $attr['subHeadingShow'] ) {
		$wraper_before .= '<div class="wopb-sub-heading"><div class="wopb-sub-heading-inner">' . $attr['subHeadingText'] . '</div></div>';
	}
	$wraper_before .= '</div>';
}
