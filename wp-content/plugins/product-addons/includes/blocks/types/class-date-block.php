<?php
/**
 * Date Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Date Block Class
 */
class Date_Block extends Abstract_Block {

	/**
	 * SVG icon for date picker
	 */
	const DATE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 20 20">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
            d="M2.577 7.837H17.43m-3.728 3.254h.008m-3.706 0h.008m-3.714 0h.008m7.396 3.239h.008m-3.706 0h.008m-3.714 0h.008M13.37 1.667v2.742M6.638 1.667v2.742" />
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" 
            d="M13.532 2.983H6.476C4.029 2.983 2.5 4.346 2.5 6.852v7.541c0 2.546 1.529 3.94 3.976 3.94h7.048c2.455 0 3.976-1.37 3.976-3.877V6.852c.008-2.506-1.513-3.87-3.968-3.87Z" 
            clip-rule="evenodd" />
    </svg>';

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'date';
	}

	/**
	 * Render the date block
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
			$this->get_date_attributes( $price_info )
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_price_with_position( $price_info );
		$html .= $this->render_date_picker( $price_info );
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get date specific attributes
	 *
	 * @return array
	 */
	private function get_date_attributes( $price_info ): array {
		$css_classes = array(
			'prad-parent',
			'prad-block-date',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);

		$attributes['class']      = $this->build_css_classes( $css_classes );
		$attributes['data-val']   = $price_info['price'];
		$attributes['data-ptype'] = $price_info['type'];

		return $attributes;
	}


	/**
	 * Render date picker
	 *
	 * @return string
	 */
	private function render_date_picker( $price_info ): string {

		$html  = '<div class="prad-custom-datetime-picker-container prad-d-flex prad-item-center prad-gap-12 prad-w-full">';
		$html .= $this->render_date_input_container( $price_info );

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
	private function render_date_input_container( $price_info ): string {
		$html  = '<div class="prad-date-picker-container prad-block-input prad-w-full prad-d-flex prad-item-center prad-gap-8">';
		$html .= $this->render_date_icon();
		$html .= $this->render_date_input( $price_info );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render date icon
	 *
	 * @return string
	 */
	private function render_date_icon(): string {
		return sprintf(
			'<div class="prad-lh-0 prad-input-date-icon prad-cursor-pointer">%s</div>',
			self::DATE_ICON
		);
	}

	/**
	 * Render date input
	 *
	 * @return string
	 */
	private function render_date_input( $price_info ): string {
		$format = $this->get_property( 'dateFormat', '' );

		$attributes = array(
			'type'                   => 'text',
			'class'                  => 'prad-date-input prad-custom-date-input prad-w-95 prad-cursor-pointer prad-input',
			'data-max-date'          => $this->get_property( 'maxDate', '' ),
			'data-min-date'          => $this->get_property( 'minDate', '' ),
			'placeholder'            => $format,
			'data-format'            => $format,
			'data-disabled-weekdays' => wp_json_encode( $this->get_property( 'disableDays', '[]' ) ),
			'data-disabled-date'     => wp_json_encode( $this->get_property( 'disableDates', '[]' ) ),
			'data-val'               => $price_info['price'],
		);

		return sprintf( '<input %s />', $this->build_attributes( $attributes ) );
	}
}
