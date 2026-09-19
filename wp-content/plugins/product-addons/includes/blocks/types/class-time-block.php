<?php
/**
 * Time Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Time Block Class
 */
class Time_Block extends Abstract_Block {

	/**
	 * SVG icon for date picker
	 */
	const DATE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 20 20">
		<g stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" clip-path="url(#clock_20)">
			<path d="M10 18.333a8.333 8.333 0 1 0 0-16.666 8.333 8.333 0 0 0 0 16.666Z" />
			<path d="M10 5v5l3.333 1.667" />
		</g>
		<defs>
			<clipPath id="clock_20">
				<path fill="#fff" d="M0 0h20v20H0z" />
			</clipPath>
		</defs>
	</svg>';

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'time';
	}

	/**
	 * Render the time block
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
			$this->get_time_attributes( $price_info )
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_price_with_position( $price_info );
		$html .= $this->render_time_picker( $price_info );
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get date specific attributes
	 *
	 * @return array
	 */
	private function get_time_attributes( $price_info ): array {
		$css_classes = array(
			'prad-parent',
			'prad-block-time',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);

		$attributes['class']      = $this->build_css_classes( $css_classes );
		$attributes['data-val']   = $price_info['price'];
		$attributes['data-ptype'] = $price_info['type'];

		return $attributes;
	}

	/**
	 * Render time picker section
	 *
	 * @param object $item Time item
	 * @param array  $price_info Price information
	 * @return string
	 */
	private function render_time_picker( array $price_info ): string {

		$html = '<div class="prad-custom-datetime-picker-container prad-d-flex prad-item-center prad-gap-12 prad-w-full">';

		$html .= $this->render_time_input_container( $price_info );

		if ( $this->should_show_price_beside_field( $price_info ) ) {
			$html .= $this->render_price_html( $price_info, 'beside' );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render date input container
	 *
	 * @return string
	 */
	private function render_time_input_container( $price_info ): string {
		$html  = '<div class="prad-time-picker-container prad-block-input prad-w-full prad-d-flex prad-item-center prad-gap-8">';
		$html .= $this->render_time_icon();
		$html .= $this->render_time_input( $price_info );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render date icon
	 *
	 * @return string
	 */
	private function render_time_icon(): string {
		return sprintf(
			'<div class="prad-lh-0 prad-input-time-icon prad-cursor-pointer">%s</div>',
			self::DATE_ICON
		);
	}

	/**
	 * Render date input
	 *
	 * @return string
	 */
	private function render_time_input( $price_info ): string {
		$format = $this->get_property( 'dateFormat', '' );

		$attributes = array(
			'type'          => 'text',
			'readonly'      => true,
			'class'         => 'prad-time-input prad-custom-time-input prad-w-95 prad-cursor-pointer prad-input',
			'placeholder'   => 'HH:MM AM',
			'data-val'      => $price_info['price'],
			'data-max-time' => $this->get_property( 'maxTime', '' ),
			'data-min-time' => $this->get_property( 'minTime', '' ),
		);

		return sprintf( '<input %s />', $this->build_attributes( $attributes ) );
	}
}
