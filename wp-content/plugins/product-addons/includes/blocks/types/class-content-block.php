<?php
/**
 * Content Block Implementation
 *
 * @package PRAD
 * @since 1.1.18
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Content Block Class
 */
class Content_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'content';
	}

	/**
	 * Render the content block
	 *
	 * @return string
	 */
	public function render(): string {
		$attributes = $this->get_common_attributes();

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= '<div class="prad-block-content-wrapper">';
		$html .= $this->render_content();
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render the content
	 *
	 * @return string
	 */
	private function render_content(): string {
		$content_text = $this->get_property( 'previewContent', '' );
		return do_shortcode( $content_text );
	}
}
