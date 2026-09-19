<?php
/**
 * Button Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Button Block Class
 */
class Button_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'button';
	}

	/**
	 * Render the button block
	 *
	 * @return string
	 */
	public function render(): string {
		$options = $this->get_field_options( true );
		if ( empty( $options ) ) {
			return '';
		}

		$attributes = array_merge(
			$this->get_common_attributes(),
			$this->get_selection_attributes(),
			$this->get_same_price_attributes()
		);

		$html = sprintf( '<div %s>', $this->build_attributes( $attributes ) );

		if ( ! empty( $this->same_price_info['enabled'] ) && product_addons()->is_pro_feature_available() ) {
			$html .= $this->render_title_description_price_with_position( $this->same_price_info );
		} else {
			$html .= $this->render_title_description_noprice();
		}
		$html .= $this->render_buttons_group( $options );
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get attributes specific to selection functionality
	 *
	 * @return array
	 */
	private function get_selection_attributes(): array {
		$attributes  = array();
		$css_classes = array(
			'prad-parent',
			'prad-block-button',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);

		$attributes['class'] = $this->build_css_classes( $css_classes );

		$multiple        = $this->get_property( 'multiple', false );
		$enableMinMaxRes = $this->get_property( 'enableMinMaxRes', true );
		if ( $multiple && $enableMinMaxRes ) {
			$attributes['data-minselect'] = $this->get_property( 'minSelect', '' );
			$attributes['data-maxselect'] = $this->get_property( 'maxSelect', '' );
		}

		return $attributes;
	}

	/**
	 * Render the group of buttons
	 *
	 * @return string
	 */
	private function render_buttons_group( $options ): string {
		$vertical = $this->get_property( 'vertical', false );
		$html     = sprintf(
			'<div class="prad-d-flex prad-flex-wrap prad-gap-%s prad-flex-%s">',
			esc_attr( $vertical ? '8' : '12' ),
			esc_attr( $vertical ? 'column' : 'row' )
		);

		$html .= $this->render_button_options( $options );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render all button options
	 *
	 * @return string
	 */
	private function render_button_options( $options ): string {
		$html = '';

		foreach ( $options as $index => $item ) {
			$html .= $this->render_single_button( $item, $index );
		}

		return $html;
	}

	/**
	 * Render a single button option
	 *
	 * @param object $item
	 * @param int    $index
	 * @return string
	 */
	private function render_single_button( $item, int $index ): string {
		$price_obj              = $this->get_price_info( $item );
		$multiple               = $this->get_property( 'multiple', false );
		$input_type             = $multiple ? 'checkbox' : 'radio';
		$prad_allowed_html_tags = $this->allowed_html_tags;

		$html = '<div class="prad-button-container">';

		// Input
		$html .= $this->render_button_input( $item, $index, $price_obj, $input_type );

		// Label
		$html .= $this->render_button_label( $item, $index, $price_obj, $prad_allowed_html_tags );

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render the hidden input for a button
	 *
	 * @param object $item
	 * @param int    $index
	 * @param array  $price_obj
	 * @param string $input_type
	 * @return string
	 */
	private function render_button_input( $item, int $index, array $price_obj, string $input_type ): string {
		$blockid            = $this->get_block_id();
		$item_formula_value = $this->is_formula_value_enabled() && ! empty( $item['formulaValue'] ) ? $item['formulaValue'] : '';

		$input_attributes = array(
			'class'              => 'prad-input-hidden',
			'type'               => $input_type,
			'data-index'         => $index,
			'data-uid'           => $item['uid'] ?? '',
			'data-formula-value' => $item_formula_value,
			'id'                 => $blockid . $index,
			'name'               => $blockid,
			'value'              => $price_obj['price'],
			'data-ptype'         => $price_obj['type'],
			'data-label'         => $item['value'],
		);

		return sprintf( '<input %s />', $this->build_attributes( $input_attributes ) );
	}

	/**
	 * Render the button label
	 *
	 * @param object $item
	 * @param int    $index
	 * @param array  $price_obj
	 * @param array  $allowed_tags
	 * @return string
	 */
	private function render_button_label( $item, int $index, array $price_obj, array $allowed_tags ): string {
		$blockid = $this->get_block_id();

		$html  = sprintf( '<label class="prad-mb-0" for="%s">', esc_attr( $blockid . $index ) );
		$html .= '<div class="prad-button-item prad-w-fit prad-d-flex prad-item-center prad-gap-8">';

		// Value
		$html .= sprintf(
			'<div title="%s" class="prad-ellipsis-2 prad-text-%s" style="min-width: %s">%s</div>',
			wp_kses( $item['value'], $allowed_tags ),
			$item['type'] != 'no_cost' ? 'start' : 'center',
			$item['type'] != 'no_cost' ? 'unset' : '2rem',
			wp_kses( $item['value'], $allowed_tags )
		);

		// Price
		if ( $item['type'] != 'no_cost' && ! ( ! empty( $this->same_price_info['enabled'] ) && product_addons()->is_pro_feature_available() ) ) {
			$html .= sprintf(
				'<div class="prad-block-price prad-text-upper">%s</div>',
				wp_kses( $price_obj['html'], $allowed_tags )
			);
		}

		$html .= '</div></label>';

		return $html;
	}
}
