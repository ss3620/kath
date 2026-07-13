<?php
defined( 'ABSPATH' ) || exit;

$title            = ( isset( $attr['titleLength'] ) && $attr['titleLength'] != 0 ) ? wp_trim_words( $title, $attr['titleLength'], '...' ) : $title;
$attr['titleTag'] = in_array( $attr['titleTag'], wopb_function()->allowed_block_tags() ) ? $attr['titleTag'] : 'h3';
$title_data      .= '<' . esc_attr( $attr['titleTag'] ) . ' class="wopb-block-title"><a title="' . esc_attr( $title ) . '"  href="' . esc_url( $titlelink ) . '">' . wp_kses_post( $title ) . '</a></' . esc_attr( $attr['titleTag'] ) . '>';
