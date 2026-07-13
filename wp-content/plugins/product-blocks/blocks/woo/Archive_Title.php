<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Archive_Title {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'layout'        => '1',
			'excerptShow'   => true,
			'prefixShow'    => false,
			'showImage'     => false,
			'titleTag'      => 'h1',
			'prefixText'    => 'Sample Prefix Text: ',
			'currentPostId' => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/archive-title',
			array(
				'editor_script'   => 'wopb-blocks-builder-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function get_data() {
		if ( is_admin() ) {
			// For Demonstration Purpose
			return array(
				'title' => 'Archive Title',
				'image' => WOPB_URL . 'assets/img/blocks/builder/builder-fallback.jpg',
				'desc'  => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam molestie aliquet molestie.',
			);
		} else {
			if ( is_archive() ) {
				if ( is_product_category() ) {
					$obj           = get_queried_object();
					$attachment_id = get_term_meta( $obj->term_id, 'thumbnail_id', true );
					return array(
						'title' => $obj->name,
						'image' => $attachment_id ? wp_get_attachment_url( $attachment_id ) : '',
						'desc'  => $obj->description,
					);
				} elseif ( is_product_tag() ) {
					$obj           = get_queried_object();
					$attachment_id = get_term_meta( $obj->term_id, 'thumbnail_id', true );
					return array(
						'title' => $obj->name,
						'image' => $attachment_id ? wp_get_attachment_url( $attachment_id ) : '',
						'desc'  => $obj->description,
					);
				} elseif ( is_product_category() || is_tag() ) {
					$obj           = get_queried_object();
					$attachment_id = get_term_meta( $obj->term_id, 'thumbnail_id', true );
					return array(
						'title' => $obj->name,
						'image' => '',
						'desc'  => $obj->description,
					);
				} elseif ( is_date() ) {
					$date = '';
					if ( is_year() ) {
						$date = get_the_date( 'Y' );
					} elseif ( is_month() ) {
						$date = get_the_date( 'F Y' );
					} elseif ( is_day() ) {
						$date = get_the_date( 'F j, Y' );
					}
					return array(
						'title' => $date,
						'image' => '',
						'desc'  => '',
					);
				} elseif ( is_author() ) {
					return array(
						'title' => get_the_author_meta( 'display_name' ),
						'image' => get_avatar_url( get_the_author_meta( 'ID' ) ),
						'desc'  => get_the_author_meta( 'user_email' ),
					);
				} elseif ( is_tax() ) {
					$obj           = get_queried_object();
					$attachment_id = get_term_meta( $obj->term_id, 'thumbnail_id', true );
					return array(
						'title' => $obj->name,
						'image' => $attachment_id ? wp_get_attachment_url( $attachment_id ) : '',
						'desc'  => $obj->description,
					);
				} elseif ( is_search() ) {
					return array(
						'title' => get_search_query(),
						'image' => '',
						'desc'  => '',
					);
				}
			} elseif ( is_search() ) {
				return array(
					'title' => get_search_query(),
					'image' => '',
					'desc'  => '',
				);
			}
			return array(
				'title' => '',
				'image' => '',
				'desc'  => '',
			);
		}
	}

	public function content( $attr, $noAjax ) {
		$data              = $this->get_data(); // Dummy
		$block_name        = 'archive-title';
		$wraper_before     = $wraper_after = $post_loop = '';
		$attr              = wp_parse_args( $attr, $this->get_attributes() );
		$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['align']     = ! empty( $attr['align'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';

		$wraper_before     .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . '' . $attr['align'] . '' . $attr['className'] . '">';
			$wraper_before .= '<div class="wopb-block-wrapper">';
			$wraper_before .= '<div class="wopb-block-archive-title wopb-archive-layout-' . esc_attr( $attr['layout'] ) . '">';

			$style  = ( $attr['layout'] == '2' && $data['image'] ) ? 'style="background-image: url(' . esc_url( $data['image'] ) . ')"' : '';
			$prefix = ( $attr['prefixShow'] && $attr['prefixText'] ) ? '<span class="wopb-archive-prefix">' . esc_html( $attr['prefixText'] ) . '</span> ' : '';

			$name = ( $data['title'] ) ? '<' . esc_attr( $attr['titleTag'] ) . ' class="wopb-archive-name">' . $prefix . esc_html( $data['title'] ) . '</' . esc_attr( $attr['titleTag'] ) . '>' : '';

			$excerpt = ( $attr['excerptShow'] && $data['desc'] ) ? '<div class="wopb-archive-desc">' . wp_kses_post( $data['desc'] ) . '</div>' : '';

				// Prefix
		switch ( $attr['layout'] ) {
			case 1:
				$img        = ( $attr['showImage'] && $data['image'] ) ? '<img class="wopb-archive-image" src="' . esc_url( $data['image'] ) . '" alt="' . esc_attr( $data['title'] ) . '"/>' : '';
				$post_loop .= $img . $name . $excerpt;
				break;
			case 2:
				$post_loop .= '<div class="wopb-archive-content" ' . wp_kses_post( $style ) . '><div class="wopb-archive-overlay"></div>' . wp_kses_post( $name . $excerpt ) . '</div>';
				break;
		}

			$wraper_after .= '</div>';
			$wraper_after .= '</div>';
		$wraper_after     .= '</div>';

		return $wraper_before . $post_loop . $wraper_after;
	}
}
