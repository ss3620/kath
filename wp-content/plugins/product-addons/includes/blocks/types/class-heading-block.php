<?php
/**
 * Heading Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Heading Block Class
 */
class Heading_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'heading';
	}

	/**
	 * Render the heading block
	 *
	 * @return string
	 */
	public function render(): string {
		$attributes = $this->get_common_attributes();

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_heading();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render the heading
	 *
	 * @return string
	 */
	private function render_heading(): string {
		$heading_tag  = esc_html( $this->get_property( 'tag', 'h1' ) );
		$heading_text = $this->get_property( 'value', '' );

		return wp_kses(
			sprintf( '<%1$s class="prad-block-heading">%2$s</%1$s>', $heading_tag, $heading_text ),
			$this->allowed_html_tags
		);
	}
}
