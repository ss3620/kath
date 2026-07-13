<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Heading {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'headingText'    => 'This is a Heading Example',
			'headingURL'     => '',
			'headingBtnText' => 'View More',
			'headingStyle'   => 'style1',
			'headingTag'     => 'h2',
			'headingAlign'   => 'left',
			'subHeadingShow' => false,
			'subHeadingText' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer ut sem augue. Sed at felis ut enim dignissim sodales.',
			'currentPostId'  => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/heading',
			array(
				'editor_script'   => 'wopb-blocks-editor-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	/**
	 * This
	 *
	 * @return string
	 */
	public function content( $attr, $noAjax = false ) {
		$attr                = wp_parse_args( $attr, $this->get_attributes() );
		$wraper_before       = '';
		$block_name          = 'heading';
		$attr['headingShow'] = true;
		$attr['className']   = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';
		$attr['align']       = ! empty( $attr['align'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['align'] ) : '';

		$wraper_before     .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . esc_attr( $block_name ) . ' wopb-block-' . esc_attr( $attr['blockId'] ) . ' ' . ( isset( $attr['className'] ) ? $attr['className'] : '' ) . ( isset( $attr['align'] ) ? ' align' . $attr['align'] : '' ) . '">';
			$wraper_before .= '<div class="wopb-block-wrapper">';
				include WOPB_PATH . 'blocks/template/heading.php';
			$wraper_before .= '</div>';
		$wraper_before     .= '</div>';

		return $wraper_before;
	}
}
