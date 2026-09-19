<?php
/**
 * Text Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Text Block Class (for textfield and textarea)
 */
class Textfield_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return $this->get_property( 'inputType', 'textfield' );
	}

	/**
	 * Render the text block
	 *
	 * @return string
	 */
	public function render(): string {
		$options = $this->get_field_options();

		if ( empty( $options ) ) {
			return '';
		}

		$item       = $options[0];
		$price_info = $this->get_price_info( $options[0] );

		$attributes = array_merge(
			$this->get_common_attributes(),
			array(
				'data-ptype'     => $price_info['type'] ?? 'no_cost',
				'data-val'       => $price_info['price'],
				'data-transform' => $this->get_property( 'textTransform', '' ),
			)
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_price_with_position( $price_info );
		$html .= $this->render_input_section( $item, $price_info );
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render input section
	 *
	 * @param object $item
	 * @param array  $price_info
	 * @return string
	 */
	private function render_input_section( $item, array $price_info ): string {
		$html = '<div class="prad-d-flex prad-item-center prad-gap-12 prad-mb-12">';

		// Input field
		$html .= $this->render_text_input( $price_info );

		// Price beside input
		if ( $this->should_show_price_beside_field( $price_info ) ) {
			$html .= $this->render_price_html( $price_info, 'beside' );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render text input or textarea
	 *
	 * @param array $price_info
	 * @return string
	 */
	private function render_text_input( array $price_info ): string {
		$input_type   = $this->get_type();
		$base_classes = array( 'prad-w-full', 'prad-block-input', 'prad-input' );

		// Build dynamic inline styles
		$styles = $this->build_input_styles();

		$base_attributes = array(
			'class'       => $this->build_css_classes( $base_classes ),
			'placeholder' => $this->get_property( 'placeholder', '' ),
			'id'          => $this->get_block_id() . '-prad-' . $input_type . '-field',
			'name'        => 'prad_field_' . $this->get_block_id(),
			'data-val'    => $price_info['price'],
			'minlength'   => $this->get_property( 'minChar', '' ),
			'maxlength'   => $this->get_property( 'maxChar', '' ),
		);

		// Add style attribute if styles exist
		if ( ! empty( $styles ) ) {
			$base_attributes['style'] = $styles;
		}

		// Add default value if set
		$default_value = $this->get_property( 'value', '' );

		return $this->render_textfield( $base_attributes, $default_value, $input_type );
	}

	/**
	 * Build dynamic input styles
	 *
	 * @return string
	 */
	private function build_input_styles(): string {
		$style_properties = array();

		// Text transform
		$text_transform = $this->get_property( 'textTransform', 'none' );
		if ( $text_transform && $text_transform !== 'none' ) {
			$style_properties[] = sprintf( 'text-transform: %s', esc_attr( $text_transform ) );
		}

		// Add other style properties as needed
		// Example: font-weight, font-size, color, etc.

		return implode( '; ', $style_properties );
	}

	/**
	 * Render text input field
	 *
	 * @param array  $attributes
	 * @param string $default_value
	 * @param string $input_type
	 * @return string
	 */
	private function render_textfield( array $attributes, string $default_value, string $input_type ): string {
		$attributes['type'] = 'text';

		// Add value
		if ( $default_value !== '' ) {
			$attributes['value'] = $default_value;
		}

		// Add input-specific attributes
		$this->add_input_specific_attributes( $attributes, $input_type );

		return sprintf( '<input %s />', $this->build_attributes( $attributes ) );
	}

	/**
	 * Add input-specific attributes
	 *
	 * @param array  &$attributes
	 * @param string $input_type
	 */
	private function add_input_specific_attributes( array &$attributes, string $input_type ): void {
		switch ( $input_type ) {
			case 'number':
				$min  = $this->get_property( 'min' );
				$max  = $this->get_property( 'max' );
				$step = $this->get_property( 'step' );

				if ( $min !== '' && $min !== null ) {
					$attributes['min'] = $min;
				}
				if ( $max !== '' && $max !== null ) {
					$attributes['max'] = $max;
				}
				if ( $step !== '' && $step !== null ) {
					$attributes['step'] = $step;
				}
				break;

			case 'telephone':
				// Add pattern for phone validation if needed
				$pattern = $this->get_property( 'pattern' );
				if ( $pattern ) {
					$attributes['pattern'] = $pattern;
				}
				break;

			case 'email':
				// Email inputs have built-in validation
				$attributes['autocomplete'] = 'email';
				break;

			case 'url':
				// URL inputs have built-in validation
				$attributes['autocomplete'] = 'url';
				break;
		}

		// Add maxlength for all text inputs
		$max_length = $this->get_property( 'maxLength' );
		if ( $max_length && $input_type !== 'number' ) {
			$attributes['maxlength'] = $max_length;
		}

		// Add minlength if specified
		$min_length = $this->get_property( 'minLength' );
		if ( $min_length && $input_type !== 'number' ) {
			$attributes['minlength'] = $min_length;
		}
	}
}
