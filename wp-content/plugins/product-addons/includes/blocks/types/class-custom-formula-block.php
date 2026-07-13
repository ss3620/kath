<?php
/**
 * Custom Formula Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Custom Formula Block Class
 */
class Custom_Formula_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'custom_formula';
	}

	/**
	 * Render the custom formula block
	 *
	 * @return string
	 */
	public function render(): string {
		$formula_data = $this->get_property( 'formulaData', array() );

		if ( ! $this->is_valid_formula( $formula_data ) ) {
			return '';
		}

		$attributes = array_merge(
			$this->get_common_attributes(),
			$this->get_formula_attributes( $formula_data )
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= '<div class="prad-d-flex prad-item-center prad-gap-16 prad-mb-12">';
		$html .= $this->render_title_section_with_price();
		$html .= '</div>';
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Check if formula data is valid
	 *
	 * @param object|null $formula_data
	 * @return boolean
	 */
	private function is_valid_formula( $formula_data ): bool {
		return isset( $formula_data['valid'] )
			&& $formula_data['valid']
			&& isset( $formula_data['expression'] )
			&& ! empty( $formula_data['expression'] );
	}

	/**
	 * Get formula specific attributes
	 *
	 * @param object $formula_data
	 * @return array
	 */
	private function get_formula_attributes( $formula_data ): array {
		$attributes                      = array();
		$css_classes                     = array(
			'prad-parent',
			'prad-block-custom-formula',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);
		$attributes['class']             = $this->build_css_classes( $css_classes );
		$attributes['data-formula-data'] = wp_json_encode(
			! empty( $formula_data['expression'] ) ? $formula_data['expression'] : ''
		);

		return $attributes;
	}

	/**
	 * Render title section with price
	 *
	 * @return string
	 */
	private function render_title_section_with_price(): string {
		$label        = $this->get_property( 'label' );
		$allowed_tags = $this->allowed_html_tags;

		$html  = '<div class="prad-d-flex prad-item-center prad-gap-16 prad-mb-12">';
		$html .= sprintf(
			'<div class="prad-block-title">%s</div>',
			wp_kses( $label, $allowed_tags )
		);
		$html .= $this->render_description_tooltip();
		$html .= $this->render_price_container();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render price container
	 *
	 * @return string
	 */
	private function render_price_container(): string {
		$allowed_tags = $this->allowed_html_tags;

		return sprintf(
			'<div class="prad-block-price prad-text-upper prad-formula-price-container">%s</div>',
			wp_kses( \wc_price( 0 ), $allowed_tags )
		);
	}
}
