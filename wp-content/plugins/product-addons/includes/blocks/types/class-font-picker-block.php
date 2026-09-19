<?php
/**
 * Font Picker Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Font Picker Block Class
 */
class Font_Picker_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'font_picker';
	}

	/**
	 * Render the font_picker block
	 *
	 * @return string
	 */
	public function render(): string {
		$options = $this->get_field_options();

		if ( empty( $options ) || ! product_addons()->is_pro_feature_available() || ! self::has_valid_options( $options ) ) {
			return '';
		}

		$attributes = array_merge(
			$this->get_common_attributes(),
			$this->get_font_picker_attributes(),
			$this->get_same_price_attributes()
		);

		$html = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		if ( ! empty( $this->same_price_info['enabled'] ) && product_addons()->is_pro_feature_available() ) {
			$html .= $this->render_title_description_price_with_position( $this->same_price_info );
		} else {
			$html .= $this->render_title_description_noprice();
		}
		$html .= $this->render_font_picker_container( $options );
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}

	public static function has_valid_options( array $options ): bool {
		$fonts = get_option( 'prad_custom_fonts', array() );
		foreach ( $options as $index => $item ) {
			$font = null;
			if ( isset( $item['fontFamily'] ) ) {
				foreach ( $fonts as $font_item ) {
					if ( isset( $font_item['id'] ) && $font_item['id'] == $item['fontFamily'] ) {
						$font = $font_item;
						break;
					}
				}
			}

			if ( $font ) {
				return true;
				break;
			}
		}
		return false;
	}

	/**
	 * Get checkbox specific attributes
	 *
	 * @return array
	 */
	private function get_font_picker_attributes(): array {
		$attributes                     = array();
		$css_classes                    = array(
			'prad-parent',
			'prad-block-font_picker',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
			'prad-block-item-img-parent prad-block-img-' . $this->get_property( 'imgStyle', 'normal' ),
		);
		$attributes['class']            = $this->build_css_classes( $css_classes );
		$to_apply_fields                = $this->get_property( 'toApplyFields', array() );
		$attributes['data-apply-fonts'] = wp_json_encode( $to_apply_fields );

		return $attributes;
	}


	/**
	 * Render font_picker container with options
	 *
	 * @param array $options Select options
	 * @return string
	 */
	private function render_font_picker_container( array $options ): string {
		$html  = '<div class="prad-custom-select prad-w-full">';
		$html .= $this->render_select_box();
		$html .= $this->render_options_list( $options );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render select box (trigger)
	 *
	 * @return string
	 */
	private function render_select_box(): string {
		$html  = '<div class="prad-select-box prad-block-input prad-block-content" readonly="readonly">';
		$html .= '<div class="prad-select-box-item">' . esc_html__( 'Select an option', 'product-addons' ) . '</div>';
		$html .= '<div class="prad-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="8" fill="none">';
		$html .= '<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m1 1 6 6 6-6"></path>';
		$html .= '</svg></div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Generate font-face CSS styles
	 *
	 * @param array $fonts Array of font objects with family, src, and file_type
	 * @return string Generated CSS font-face rules
	 */
	private function generate_font_face_styles( array $fonts ): string {
		if ( empty( $fonts ) ) {
			return '';
		}

		$css = '<style class="prad-custom-fonts">';

		foreach ( $fonts as $font ) {
			if ( empty( $font['family'] ) || empty( $font['src'] ) ) {
				continue;
			}

			$font_family = esc_attr( $font['family'] );
			$font_src    = esc_url( $font['src'] );
			$file_type   = isset( $font['file_type'] ) ? $font['file_type'] : 'woff2';

			// Convert file type to proper format value
			$format = $file_type === 'ttf' ? 'truetype' : $file_type;

			$css .= sprintf(
				"\n@font-face {\n    font-family: '%s';\n    src: url('%s') format('%s');\n    font-weight: normal;\n    font-style: normal;\n    font-display: swap;\n}\n",
				$font_family,
				$font_src,
				esc_attr( $format )
			);
		}

		$css .= '</style>';

		return $css;
	}

	/**
	 * Render options list
	 *
	 * @param array $options Select options
	 * @return string
	 */
	private function render_options_list( array $options ): string {
		$html = '<div class="prad-select-options">';

		$fonts = get_option( 'prad_custom_fonts', array() );
		$html .= $this->generate_font_face_styles( $fonts );

		foreach ( $options as $index => $item ) {
			$font = null;
			if ( isset( $item['fontFamily'] ) ) {
				foreach ( $fonts as $font_item ) {
					if ( isset( $font_item['id'] ) && $font_item['id'] == $item['fontFamily'] ) {
						$font = $font_item;
						break;
					}
				}
			}

			if ( ! $font ) {
				return '';
			}

			$price_info = $this->get_price_info( $item );
			$label      = $font['title'];

			$option_attributes = array(
				'class'            => 'prad-select-option',
				'data-value'       => $price_info['price'],
				'data-label'       => $label,
				'data-index'       => $index,
				'data-font-family' => $font['family'],
				'data-ptype'       => $item['type'] ?? 'no_cost',
				'data-uid'         => $item['fontFamily'] ?? '',
			);

			$html .= sprintf( '<div %s  >', $this->build_attributes( $option_attributes ) );
			$html .= '<div class="prad-d-flex prad-item-center prad-gap-8">';

			// Option content
			$html .= '<div class="prad-block-content prad-d-flex prad-item-center">';
			$html .= sprintf(
				'<div class="prad-ellipsis-2" title="%1$s" style="font-family: &quot;%2$s&quot;">%3$s</div>',
				esc_attr( $label ),
				esc_attr( $font['family'] ),
				wp_kses( $label, $this->allowed_html_tags )
			);
			$html .= '</div>';

			// Price if not free
			if ( isset( $item['type'] ) && $item['type'] !== 'no_cost' && ! ( ! empty( $this->same_price_info['enabled'] ) && product_addons()->is_pro_feature_available() ) ) {
				$html .= '<div class="prad-block-price prad-text-upper">';
				$html .= wp_kses( $price_info['html'], $this->allowed_html_tags );
				$html .= '</div>';
			}

			$html .= '</div></div>';
		}

		$html .= '</div>';

		return $html;
	}
}
