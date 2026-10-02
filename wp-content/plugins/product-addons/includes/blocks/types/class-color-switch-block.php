<?php
/**
 * Color Switch Block Implementation
 *
 * Deprecated, kept only as legacy support: the Color Swatches field can no longer be
 * added in the builder. This class stays so Color Swatches fields that existing users
 * already saved on their products keep working, and it will be removed in a future release.
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Color Switch Block Class
 */
class Color_Switch_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'color_switch';
	}

	/**
	 * Render the color switch block
	 *
	 * @return string
	 */
	public function render(): string {
		// Fields made with WowAddons Pro's "Advanced Color Swatches" are Pro fields, rendered by Pro only.
		if ( $this->get_property( 'isAdvanced', false ) ) {
			return '';
		}

		$options = $this->get_field_options();
		if ( empty( $options ) ) {
			return '';
		}

		$attributes = array_merge(
			$this->get_common_attributes(),
			$this->get_selection_attributes()
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_noprice();
		$html .= $this->render_swatch_wrapper( $options );
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get selection specific attributes
	 *
	 * @return array
	 */
	private function get_selection_attributes(): array {
		$multiple   = $this->get_property( 'multiple', false );
		$input_type = $multiple ? 'checkbox' : 'radio';
		$layout     = $this->get_property( 'layout', '_default' );

		$css_classes = array(
			'prad-parent',
			'prad-block-color-switcher',
			'prad-switcher-count',
			'prad-switcher-count-' . $input_type,
			'prad-swatch-layout' . $layout,
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);

		$attributes['class'] = $this->build_css_classes( $css_classes );
		$enable_min_max_res  = $this->get_property( 'enableMinMaxRes', true );

		if ( $multiple && $enable_min_max_res ) {
			$attributes['data-minselect'] = $this->get_property( 'minSelect', '' );
			$attributes['data-maxselect'] = $this->get_property( 'maxSelect', '' );
		}

		return $attributes;
	}


	/**
	 * Get hover class based on layout visibility
	 *
	 * @return string
	 */
	private function get_hover_class(): string {
		$visibility = $this->get_property( 'layoutVisibility', 'always_show' );

		switch ( $visibility ) {
			case 'hover_show':
				return 'show';
			case 'hover_hide':
				return 'hide';
			default:
				return 'always';
		}
	}

	/**
	 * Render swatch wrapper with all color options
	 *
	 * @param array $options Options to render.
	 * @return string
	 */
	private function render_swatch_wrapper( $options ): string {
		$html = '<div class="prad-swatch-wrapper">';

		foreach ( $options as $index => $item ) {
			$html .= $this->render_single_swatch( $item, $index );
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Render a single color swatch
	 *
	 * @param object  $item Option data.
	 * @param integer $index Option index.
	 * @return string
	 */
	private function render_single_swatch( $item, int $index ): string {
		$price_info = $this->get_price_info( $item );
		$layout     = $this->get_property( 'layout', '_default' );

		$html  = '<div class="prad-swatch-item-wrapper prad-relative prad-d-flex prad-flex-column prad-h-full">';
		$html .= $this->render_swatch_container( $item, $index, $price_info );

		if ( '_default' === $layout ) {
			$html .= $this->render_block_content( (object) $item, $index, $price_info );
		}

		if ( '_img' === $layout ) {
			$html .= sprintf(
				'<div class="prad-text-center">%s</div>',
				$this->render_price_html( $price_info, 'beside' )
			);
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Render the swatch container
	 *
	 * @param object  $item Option data.
	 * @param integer $index Option index.
	 * @param array   $price_info Price data for the option.
	 * @return string
	 */
	private function render_swatch_container( $item, int $index, array $price_info ): string {
		$layout      = $this->get_property( 'layout', '_default' );
		$hover_class = $this->get_hover_class();

		$html  = sprintf(
			'<div class="prad-swatch-container prad-p-2 prad-w-fit prad-relative prad-hover-%s-bottom">',
			esc_attr( $hover_class )
		);
		$html .= $this->render_swatch_input( $item, $index, $price_info );
		$html .= $this->render_swatch_label( $item, $index );
		$html .= $this->render_swatch_mark();

		if ( '_overlay' === $layout ) {
			$html .= $this->render_block_content( (object) $item, $index, $price_info );
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Render the swatch input
	 *
	 * @param object  $item Option data.
	 * @param integer $index Option index.
	 * @param array   $price_info Price data for the option.
	 * @return string
	 */
	private function render_swatch_input( $item, int $index, array $price_info ): string {
		$multiple = $this->get_property( 'multiple', false );
		$blockid  = $this->get_block_id();

		$attributes = array(
			'class'      => 'prad-input-hidden',
			'type'       => $multiple ? 'checkbox' : 'radio',
			'data-index' => $index,
			'data-uid'   => $item['uid'] ?? '',
			'id'         => $blockid . $index,
			'name'       => $blockid,
			'value'      => $price_info['price'],
			'data-ptype' => $item['type'],
			'data-label' => $item['value'],
		);

		return sprintf( '<input %s />', $this->build_attributes( $attributes ) );
	}

	/**
	 * Render the swatch label
	 *
	 * @param object  $item Option data.
	 * @param integer $index Option index.
	 * @return string
	 */
	private function render_swatch_label( $item, int $index ): string {
		$blockid = $this->get_block_id();
		$layout  = $this->get_property( 'layout', '_default' );

		$html  = sprintf( '<label class="prad-lh-0 prad-mb-4" for="%s" title="%s">', esc_attr( $blockid . $index ), esc_attr( '_img' === $layout ? $item['value'] : '' ) );
		$html .= sprintf(
			'<div class="prad-swatch-item" aria-label="Color swatch for %s" style="background-color: %s;"></div>',
			esc_attr( $item['value'] ),
			esc_attr( $item['color'] )
		);
		$html .= '</label>';

		return $html;
	}

	/**
	 * Render the swatch checkmark
	 *
	 * @return string
	 */
	private function render_swatch_mark(): string {
		return '
        <div class="prad-swatch-mark-image" style="border: 1px solid #ffffff;padding: 1px !important;border-radius: 2px;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" width="16" height="16" viewBox="0 0 16 16">
                <rect width="16" height="16" fill="currentColor" rx="2" />
                <path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m12.125 5.375-5.25 5.25L4.25 8" />
            </svg>
        </div>';
	}
}
