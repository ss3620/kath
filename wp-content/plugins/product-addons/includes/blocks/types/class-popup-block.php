<?php
/**
 * Popup Block Implementation
 *
 * @package PRAD
 * @since 1.1.18
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Popup Block Class
 */
class Popup_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'popup';
	}

	/**
	 * Render the content block
	 *
	 * @return string
	 */
	public function render(): string {
		$attributes = $this->get_common_attributes();

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= '<div class="prad-block-popup-wrapper">';
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

		$content  = $this->render_header();
		$content .= $this->render_popup_content();

		return $content;
	}
	/**
	 * Render the content
	 *
	 * @return string
	 */
	private function render_header(): string {

		$header_part  = '<div class="prad-block-popup-header" data-popup-id="' . esc_attr( $this->get_block_id() ) . '">';
		$header_part .= $this->get_property( 'label', '' );
		$header_part .= '</div>';

		return $header_part;
	}
	/**
	 * Render the content
	 *
	 * @return string
	 */
	private function render_popup_content(): string {

		$close_icons = '<svg
			xmlns="http://www.w3.org/2000/svg"
			fill="none"
			viewBox="0 0 24 24"
		>
			<path
				fill="currentColor"
				fill-rule="evenodd"
				d="M12 1.25C6.063 1.25 1.25 6.063 1.25 12S6.063 22.75 12 22.75 22.75 17.937 22.75 12 17.937 1.25 12 1.25ZM8.707 7.293a1 1 0 0 0-1.414 1.414L10.586 12l-3.293 3.293a1 1 0 1 0 1.414 1.414L12 13.414l3.293 3.293a1 1 0 0 0 1.414-1.414L13.414 12l3.293-3.293a1 1 0 0 0-1.414-1.414L12 10.586 8.707 7.293Z"
				clip-rule="evenodd"
			/>
		</svg>';

		$content_part  = '<div class="prad-block-popup-content-wrapper " data-popup-id="' . esc_attr( $this->get_block_id() ) . '">';
		$content_part .= '<div class="prad-block-popup-content-close">' . $close_icons . '</div>';
		$content_part .= '<div class="prad-block-popup-content prad-scrollbar prad-overflow-y-auto prad-overflow-x-hidden">';
		$content_part .= do_shortcode( $this->get_property( 'popupContent', '' ) );
		$content_part .= '</div>';
		$content_part .= '</div>';

		return $content_part;
	}
}
