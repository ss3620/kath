<?php
/**
 * Shortcode Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode Block Class
 */
class Shortcode_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'shortcode';
	}

	/**
	 * Render the shortcode block
	 *
	 * @return string
	 */
	public function render(): string {
		$attributes = $this->get_common_attributes();

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title();
		$html .= $this->render_shortcode_content();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render title section if not hidden
	 *
	 * @return string
	 */
	private function render_title(): string {
		if ( $this->get_property( 'hide', false ) ) {
			return '';
		}

		$html  = '<div class="prad-d-flex prad-item-center prad-gap-12 prad-mb-12">';
		$html .= '<div class="prad-relative prad-w-fit">';
		$html .= sprintf(
			'<div class="prad-block-title">%s</div>',
			wp_kses( $this->get_label(), $this->allowed_html_tags )
		);
		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Render shortcode content
	 *
	 * @return string
	 */
	private function render_shortcode_content(): string {
		$shortcode = $this->get_property( 'value', '' );
		return do_shortcode( $shortcode );
	}
}
