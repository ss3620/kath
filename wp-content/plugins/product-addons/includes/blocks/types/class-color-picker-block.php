<?php
/**
 * Color Picker Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Color Picker Block Class
 */
class Color_Picker_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'color_picker';
	}

	/**
	 * Render the color picker block
	 *
	 * @return string
	 */
	public function render(): string {
		$options = $this->get_field_options();
		if ( empty( $options ) ) {
			return '';
		}

		$price_info = $this->get_price_info( $options[0] );
		$attributes = array_merge(
			$this->get_common_attributes(),
			$this->get_field_specific_attributes( $price_info )
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_price_with_position( $price_info );
		$html .= $this->render_color_picker( $price_info );
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get specific attributes
	 *
	 * @return array
	 */
	private function get_field_specific_attributes( $price_info ) {
		$css_classes = array(
			'prad-parent',
			'prad-block-color-picker',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);

		$attributes['class']       = $this->build_css_classes( $css_classes );
		$attributes['data-val']    = $price_info['price'];
		$attributes['data-ptype']  = $price_info['type'];
		$attributes['data-defval'] = $this->get_property( 'defaultColor', '' );

		return $attributes;
	}

	/**
	 * Render color picker input
	 *
	 * @return string
	 */
	private function render_color_picker( $price_info ): string {
		$html = '<div class="prad-d-flex prad-item-center prad-gap-12 prad-w-full">';

		// Input field
		$html .= $this->render_color_input( $price_info );

		// Price beside input
		if ( $this->should_show_price_beside_field( $price_info ) ) {
			$html .= $this->render_price_html( $price_info, 'beside' );
		}

		$html .= '</div>';

		return $html;

		return $html;
	}

	/**
	 * Render color picker input
	 *
	 * @return string
	 */
	private function render_color_input( $price_info ): string {
		$default_color = $this->get_property( 'defaultColor', '' );
		$block_id      = $this->get_block_id();

		$html  = '<div class="prad-block-input prad-color-picker-container prad-d-flex prad-item-center prad-gap-8 prad-w-full">';
		$html .= $this->render_input_type_color( $price_info, $default_color, $block_id );
		$html .= $this->render_input_type_text( $default_color );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render color picker input
	 *
	 * @return string
	 */
	private function render_input_type_color( $price_info, $default_color, $block_id ): string {
		$picker_attributes = array(
			'type'     => 'color',
			'class'    => 'prad-front-end prad-input prad-w-90',
			'id'       => $block_id . '-color-picker',
			'name'     => 'prad_field_' . $block_id,
			'value'    => trim( $default_color, '"' ),
			'data-val' => $price_info['price'],
		);

		$html = sprintf( '<input %s />', $this->build_attributes( $picker_attributes ) );

		return $html;
	}

	/**
	 * Render color picker input
	 *
	 * @return string
	 */
	private function render_input_type_text( $default_color ): string {
		$picker_attributes = array(
			'type'  => 'text',
			'class' => 'prad-input-color-text prad-input',
			'value' => trim( $default_color, '"' ),
		);

		$html              = '<div class="prad-input-wrapper prad-d-flex prad-item-center prad-justify-between prad-gap-24 prad-w-full">';
			$html         .= '<div class="prad-input-wrapper prad-d-flex prad-item-center prad-justify-between prad-gap-24 prad-w-full">';
				$html     .= sprintf( '<input %s />', $this->build_attributes( $picker_attributes ) );
				$html     .= '<div class="prad-color-picker-resetter prad-lh-0">';
					$html .= '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 5 5 15M5 5l10 10" /></svg>';
				$html     .= '</div>';
			$html         .= '</div>';
		$html             .= '</div>';

		return $html;
	}
}
