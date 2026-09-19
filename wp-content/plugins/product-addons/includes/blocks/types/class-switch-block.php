<?php
/**
 * Switch Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Switch Block Class
 */
class Switch_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'switch';
	}

	/**
	 * Render the switch block
	 *
	 * @return string
	 */
	public function render(): string {
		$options = $this->get_field_options();

		if ( empty( $options ) ) {
			return '';
		}

		$item         = $options[0];
		$price_info   = $this->get_price_info( $item );
		$enable_count = $this->get_property( 'enableCount', false );

		$attributes = array_merge(
			$this->get_common_attributes(),
			$this->get_switch_attributes()
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_price_with_position( $price_info );
		$html .= $this->render_switch_content( $item, $price_info, $enable_count );
		$html .= $this->render_description_below_field();
		$html .= $this->render_tooltip();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get checkbox specific attributes
	 *
	 * @return array
	 */
	private function get_switch_attributes(): array {
		$attributes  = array();
		$css_classes = array(
			'prad-parent',
			'prad-block-switch',
			'prad-switcher-count',
			'_switchCount',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
			'prad-block-item-img-parent prad-block-img-' . $this->get_property( 'imgStyle', 'normal' ),
		);

		$attributes['class'] = $this->build_css_classes( $css_classes );

		return $attributes;
	}

	/**
	 * Render switch content including checkbox, label and counter
	 *
	 * @param object $item Switch item
	 * @param array  $price_info Price information
	 * @param bool   $enable_count Whether counting is enabled
	 * @return string
	 */
	private function render_switch_content( $item, array $price_info, bool $enable_count ): string {
		$price_position = $this->get_property( 'pricePosition', 'with_option' );
		$justify_class  = $price_position === 'with_option' ? 'left' : 'between';

		$html = sprintf(
			'<div class="prad-switch-item-wrapper prad-d-flex prad-item-center prad-justify-%s prad-gap-12 prad-w-full">',
			esc_attr( $justify_class )
		);

		// Switch item
		$html .= $this->render_switch_item( $item, $price_info );

		// Price and counter section
		if ( $enable_count || ( $price_position !== 'with_title' && $item['type'] !== 'no_cost' ) ) {
			$html .= '<div class="prad-d-flex prad-item-center prad-gap-12">';

			if ( $price_position !== 'with_title' && $item['type'] !== 'no_cost' ) {
				$html .= sprintf(
					'<div class="prad-block-price prad-text-upper">%s</div>',
					wp_kses( $price_info['html'], $this->allowed_html_tags )
				);
			}

			if ( $enable_count && product_addons()->is_pro_feature_available() ) {
				$html .= $this->render_counter_input();
			}

			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render switch item with checkbox and label
	 *
	 * @param object $item Switch item
	 * @param array  $price_info Price information
	 * @return string
	 */
	private function render_switch_item( $item, array $price_info ): string {
		$block_id           = $this->get_block_id();
		$enable_count       = $this->get_property( 'enableCount', false );
		$item_formula_value = $this->is_formula_value_enabled() && ! empty( $item['formulaValue'] ) ? $item['formulaValue'] : '';

		$input_attributes = array(
			'class'              => 'prad-input-hidden',
			'type'               => 'checkbox',
			'id'                 => $block_id,
			'name'               => 'prad-checkbox-' . $block_id,
			'value'              => $price_info['price'],
			'data-ptype'         => $item['type'],
			'data-index'         => '0',
			'data-formula-value' => $item_formula_value,
			'data-label'         => $item['value'],
			'data-count'         => $enable_count ? 'yes' : 'no',
			'data-counter'       => $block_id . '-switcher-count',
		);

		$html  = '<div class="prad-switch-item prad-d-flex prad-item-center prad-gap-10">';
		$html .= sprintf( '<input %s />', $this->build_attributes( $input_attributes ) );
		$html .= sprintf( '<label for="%s" class="prad-d-flex prad-item-center prad-gap-10">', esc_attr( $block_id ) );
		$html .= '<div class="prad-switch-body prad-shrink-0 prad-selection-none"><div class="prad-switch-thumb"></div></div>';
		$html .= '<div class="prad-block-content prad-d-flex prad-item-center">';

		if ( isset( $item['img'] ) && $item['img'] && product_addons()->is_pro_feature_available() ) {
			$html .= sprintf(
				'<img class="prad-block-item-img" src="%s" alt="Item" data-tooltip-label="%s" />',
				esc_url( $item['img'] ),
				esc_attr( $item['value'] )
			);
		}

		$html .= sprintf(
			'<div class="prad-ellipsis-2" title="%1$s">%2$s</div>',
			esc_attr( $item['value'] ),
			wp_kses( $item['value'], $this->allowed_html_tags )
		);

		$html .= '</div></label></div>';

		return $html;
	}

	/**
	 * Render counter input
	 *
	 * @return string
	 */
	private function render_counter_input(): string {
		$block_id = $this->get_block_id();
		$min      = $this->get_property( 'min', 1 );
		$max      = $this->get_property( 'max', 100 );

		$input_attributes = array(
			'id'           => 'prad_quantity_' . $block_id,
			'name'         => 'prad_quantity_' . $block_id,
			'type'         => 'number',
			'class'        => 'prad-block-input prad-quantity-input switcher-count prad-input',
			'placeholder'  => $min,
			'value'        => $min,
			'min'          => $min,
			'data-counter' => $block_id . '-switcher-count',
		);

		if ( $max ) {
			$input_attributes['max'] = $max;
		}

		return sprintf( '<input %s />', $this->build_attributes( $input_attributes ) );
	}

	/**
	 * Render tooltip for image preview
	 *
	 * @return string
	 */
	private function render_tooltip(): string {
		$enable_preview = $this->get_property( 'enableImagePreview', false );
		if ( ! $enable_preview ) {
			return '';
		}
		return '
		<div class="prad-img-tooltip" role="tooltip" aria-hidden="true">
			<img src="" alt="" />
			<span class="prad-img-tooltip-label"></span>
		</div>';
	}
}
