<?php
/**
 * Separator Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Separator Block Class
 */
class Separator_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'separator';
	}

	/**
	 * Render the separator block
	 *
	 * @return string
	 */
	public function render(): string {
		$attributes = $this->get_common_attributes();

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_separator();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render separator element
	 *
	 * @return string
	 */
	private function render_separator(): string {
		$height = $this->get_property(
			'height',
			array(
				'val'  => '1',
				'unit' => 'px',
			)
		);

		$style = sprintf(
			'height: %s%s; width: %s;',
			esc_attr( ! empty( $height['val'] ) ? $height['val'] : 0 ),
			esc_attr( ! empty( $height['unit'] ) ? $height['unit'] : 'px' ),
			'100%'
		);

		return sprintf(
			'<div class="prad-separator-item" style="%s"></div>',
			$style
		);
	}
}
