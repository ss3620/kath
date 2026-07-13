<?php
/**
 * Number Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Number Block Class
 */
class Number_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'number';
	}

	/**
	 * Render the number block
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
			'prad-block-number',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);

		$attributes = array_merge(
			$this->get_common_attributes(),
			array(
				'class'       => $this->build_css_classes( $css_classes ),
				'data-ptype'  => $price_info['type'],
				'data-val'    => $price_info['price'],
				'data-defval' => $this->get_property( 'value', '' ),
			)
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_price_with_position( $price_info );
		$html .= $this->render_number_input( $price_info );
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}


	/**
	 * Render number input section
	 *
	 * @return string
	 */
	private function render_number_input( array $price_info ): string {

		$input_attributes = array(
			'class'       => 'prad-w-full prad-block-input prad-input',
			'type'        => 'number',
			'placeholder' => $this->get_property( 'placeholder', '' ),
			'id'          => $this->get_block_id() . '-prad-number-field',
			'step'        => $this->get_property( 'step', 1 ),
			'data-val'    => $price_info['price'],
		);

		$enableMinMaxRes = $this->get_property( 'enableMinMaxRes', true );
		if ( $enableMinMaxRes ) {
			$input_attributes['min'] = $this->get_property( 'min', 0 );
			$input_attributes['max'] = $this->get_property( 'max', 100 );
		}

		$html  = '<div class="prad-d-flex prad-item-center prad-gap-12 prad-mb-12">';
		$html .= sprintf( '<input %s />', $this->build_attributes( $input_attributes ) );

		if ( $this->should_show_price_beside_field( $price_info ) ) {
			$html .= $this->render_price_html( $price_info, 'beside' );
		}

		$html .= '</div>';

		return $html;
	}
}
