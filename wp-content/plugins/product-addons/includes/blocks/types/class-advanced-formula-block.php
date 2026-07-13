<?php
/**
 * Advanced Formula Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;
use PRAD\Includes\Common\Formula\Array_Expression_Engine;

defined( 'ABSPATH' ) || exit;

/**
 * Advanced Formula Block Class
 */
class Advanced_Formula_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'advanced_formula';
	}

	/**
	 * Render the advanced formula block
	 *
	 * @return string
	 */
	public function render(): string {

		if ( ! product_addons()->is_pro_feature_available() ) {
			return '';
		}

		$advanced_formula_data = $this->get_property( 'advancedFormulaData', array() );

		if ( ! $this->is_valid_formula( $advanced_formula_data ) ) {
			return '';
		}

		$attributes = array_merge(
			$this->get_common_attributes(),
			$this->get_formula_attributes( $advanced_formula_data )
		);

		$evaluated_expression = Array_Expression_Engine::evaluate_expression_safe(
			$advanced_formula_data['expression'],
			array()
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_section_with_price();
		// $html .= '<span> Evaluated without dynamic: ' . $evaluated_expression . '</span>';
		$html .= $this->render_description_below_title();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Check if formula data is valid
	 *
	 * @param object|null $advanced_formula_data
	 * @return boolean
	 */
	private function is_valid_formula( $advanced_formula_data ): bool {
		return isset( $advanced_formula_data['valid'] )
			&& $advanced_formula_data['valid']
			&& ! empty( $advanced_formula_data['expression'] );
	}

	/**
	 * Get formula specific attributes
	 *
	 * @param object $advanced_formula_data
	 * @return array
	 */
	private function get_formula_attributes( $advanced_formula_data ): array {
		$attributes                            = array();
		$attributes['data-formula-expression'] = wp_json_encode(
			! empty( $advanced_formula_data['expression'] ) ? $advanced_formula_data['expression'] : ''
		);
		$attributes['data-formula-bidmap']     = wp_json_encode(
			! empty( $advanced_formula_data['dynamicOptionBidMap'] ) ? $advanced_formula_data['dynamicOptionBidMap'] : ''
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

		$html  = '<div class="prad-d-flex prad-item-center prad-gap-12">';
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
			'<div class="prad-block-price prad-text-upper prad-adv-formula-price-container">%s</div>',
			wp_kses( \wc_price( 0 ), $allowed_tags )
		);
	}
}
