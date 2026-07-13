<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Image {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'imageUpload'    => (object) array(
				'id'  => '999999',
				'url' => WOPB_URL . 'assets/img/wopb-placeholder.jpg',
			),
			'linkType'       => 'link',
			'imgLink'        => '',
			'linkTarget'     => '_blank',
			'imgAlt'         => 'Image',
			'imgAnimation'   => 'none',
			'imgOverlay'     => false,
			'imgOverlayType' => 'default',
			'headingText'    => 'This is a Heading Example',
			'headingEnable'  => false,
			'btnText'        => 'Free Download',
			'btnLink'        => '#',
			'btnTarget'      => '_blank',
			'btnPosition'    => 'centerCenter',
			'currentPostId'  => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/image',
			array(
				'editor_script'   => 'wopb-blocks-editor-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr, $noAjax = false ) {
		$attr          = wp_parse_args( $attr, $this->get_attributes() );
		$wraper_before = '';
		$block_name    = 'image';

		$allowed_html_tags   = wopb_function()->allowed_html_tags();
		$attr['className']   = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['align']       = ! empty( $attr['align'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';
		$attr['btnText']     = wp_kses( $attr['btnText'], $allowed_html_tags );
		$attr['headingText'] = wp_kses( $attr['headingText'], $allowed_html_tags );

		$wraper_before             .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . ( isset( $attr['align'] ) ? ' align' . $attr['align'] : '' ) . '">';
			$wraper_before         .= '<div class="wopb-block-wrapper">';
				$wraper_before     .= '<figure class="wopb-image-block-wrapper">';
					$wraper_before .= '<div class="wopb-image-block wopb-image-block-' . sanitize_html_class( $attr['imgAnimation'] ) . ( $attr['imgOverlay'] ? ' wopb-image-block-overlay wopb-image-block-' . sanitize_html_class( $attr['imgOverlayType'] ) : '' ) . '">';
						// Single Image
						$img_arr = (array) $attr['imageUpload'];
		if ( ! empty( $img_arr ) ) {
			if ( $attr['linkType'] == 'link' && $attr['imgLink'] ) {
				$wraper_before .= '<a href="' . esc_url( $attr['imgLink'] ) . '" target="' . esc_attr( $attr['linkTarget'] ) . '"><img class="wopb-image" src="' . esc_url( $img_arr['url'] ) . '" alt="' . esc_attr( $attr['imgAlt'] ) . '" /></a>';
			} else {
				$wraper_before .= '<img class="wopb-image" src="' . esc_url( $img_arr['url'] ) . '" alt="' . esc_attr( $attr['imgAlt'] ) . '" />';
			}
		}
		if ( $attr['btnLink'] && ( $attr['linkType'] == 'button' ) ) {
			$wraper_before .= '<div class="wopb-image-button wopb-image-button-' . sanitize_html_class( $attr['btnPosition'] ) . '"><a href="' . esc_url( $attr['btnLink'] ) . '" target="' . esc_attr( $attr['btnTarget'] ) . '">' . $attr['btnText'] . '</a></div>';
		}
					$wraper_before .= '</div>';
		if ( $attr['headingEnable'] == 1 ) {
			$wraper_before .= '<figcaption class="wopb-image-caption">' . $attr['headingText'] . '</figcaption>';
		}
				$wraper_before .= '</figure>';
			$wraper_before     .= '</div>';
		$wraper_before         .= '</div>';

		return $wraper_before;
	}
}
