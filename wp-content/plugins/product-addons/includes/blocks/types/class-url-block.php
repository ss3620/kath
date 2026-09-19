<?php
/**
 * URL Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * URL Block Class
 */
class Url_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'url';
	}

	/**
	 * Render the URL block
	 *
	 * @return string
	 */
	public function render(): string {
		$options = $this->get_field_options();

		if ( empty( $options ) ) {
			return '';
		}

		$price_info  = $this->get_price_info( $options[0] );
		$css_classes = array(
			'prad-parent',
			'prad-block-url',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);

		$attributes = array_merge(
			$this->get_common_attributes(),
			array(
				'class'      => $this->build_css_classes( $css_classes ),
				'data-ptype' => $price_info['type'],
				'data-val'   => $price_info['price'],
			)
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_price_with_position( $price_info );
		$html .= $this->render_url_input( $price_info );
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render URL input section
	 *
	 * @param object $item URL item
	 * @param array  $price_info Price information
	 * @return string
	 */
	private function render_url_input( array $price_info ): string {
		$block_id    = $this->get_block_id();
		$placeholder = $this->get_property( 'placeholder', '' );
		$value       = $this->get_property( 'value', '' );

		$html = '<div class="prad-d-flex prad-item-center prad-gap-12 prad-mb-12">';

		// URL input
		$input_attributes = array(
			'class'       => 'prad-w-full prad-block-input prad-input',
			'type'        => 'url',
			'placeholder' => $placeholder,
			'id'          => $block_id . '-prad-url-field',
			'value'       => $value,
			'data-val'    => $price_info['price'],
		);

		$html .= sprintf( '<input %s />', $this->build_attributes( $input_attributes ) );

		if ( $this->should_show_price_beside_field( $price_info ) ) {
			$html .= $this->render_price_html( $price_info, 'beside' );
		}

		$html .= '</div>';

		return $html;
	}
}
