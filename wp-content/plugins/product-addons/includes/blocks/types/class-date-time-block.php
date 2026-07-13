<?php
/**
 * DateTime Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * DateTime Block Class
 */
class Date_Time_Block extends Abstract_Block {

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
	 * SVG icon for time picker
	 */
	const TIME_ICON = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="20" height="20" viewBox="0 0 20 20">
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
		return 'datetime';
	}

	/**
	 * Render the datetime block
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
			$this->get_datetime_attributes( $price_info )
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_price_with_position( $price_info );
		$html .= $this->render_datetime_picker( $price_info );
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get datetime specific attributes
	 *
	 * @param array $price_info Price information
	 * @return array
	 */
	private function get_datetime_attributes( $price_info ): array {
		$css_classes = array(
			'prad-parent',
			'prad-block-datetime',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);

		$attributes['class']      = $this->build_css_classes( $css_classes );
		$attributes['data-val']   = $price_info['price'];
		$attributes['data-ptype'] = $price_info['type'];
		$attributes['data-field-variant'] = $this->get_property( 'blockType', 'datetime' );

		return $attributes;
	}

	/**
	 * Render datetime picker
	 *
	 * @param array $price_info Price information
	 * @return string
	 */
	private function render_datetime_picker( $price_info ): string {
		$html  = '<div class="prad-custom-datetime-picker-container prad-d-flex prad-item-center prad-gap-12 prad-w-full">';
		$html .= '<div class="prad-datetime-container prad-w-full prad-d-flex prad-item-center prad-gap-12">';
		$html .= $this->render_date_input_container( $price_info );
		$html .= $this->render_time_input_container( $price_info );
		$html .= '</div>';

		if ( $this->should_show_price_beside_field( $price_info ) ) {
			$html .= $this->render_price_html( $price_info, 'beside' );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render date input container
	 *
	 * @param array $price_info Price information
	 * @return string
	 */
	private function render_date_input_container( $price_info ): string {
		if ( 'time' === $this->get_property( 'blockType', 'datetime' ) ) {
			return '';
		}
		$html  = '<div class="prad-date-picker-container prad-block-input prad-w-50 prad-d-flex prad-item-center prad-gap-8 prad-w-full">';
		$html .= $this->render_date_icon();
		$html .= $this->render_date_input( $price_info );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render time input container
	 *
	 * @param array $price_info Price information
	 * @return string
	 */
	private function render_time_input_container( $price_info ): string {
		if ( 'date' === $this->get_property( 'blockType', 'datetime' ) ) {
			return '';
		}
		$html  = '<div class="prad-time-picker-container prad-block-input prad-w-50 prad-d-flex prad-item-center prad-gap-8 prad-w-full">';
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
	private function render_date_icon(): string {
		return sprintf(
			'<div class="prad-lh-0 prad-input-date-icon prad-cursor-pointer">%s</div>',
			self::DATE_ICON
		);
	}

	/**
	 * Render time icon
	 *
	 * @return string
	 */
	private function render_time_icon(): string {
		return sprintf(
			'<div class="prad-lh-0 prad-input-time-icon prad-cursor-pointer">%s</div>',
			self::TIME_ICON
		);
	}

	/**
	 * Render date input
	 *
	 * @param array $price_info Price information
	 * @return string
	 */
	private function render_date_input( $price_info ): string {
		$format        = $this->get_property( 'dateFormat', 'DD/MM/YYYY' );
		$min_date_type = $this->get_property( 'minDateType', 'none' );
		$max_date_type = $this->get_property( 'maxDateType', 'none' );

		$attributes = array(
			'type'                    => 'text',
			'readonly'                => true,
			'class'                   => 'prad-date-input prad-custom-date-input prad-w-95 prad-cursor-pointer prad-input',
			'data-min-date'           => 'custom' == $min_date_type ? $this->get_property( 'minDate', '' ) : $min_date_type,
			'data-max-date'           => 'custom' == $max_date_type ? $this->get_property( 'maxDate', '' ) : $max_date_type,
			'placeholder'             => $format,
			'data-format'             => $format,
			'data-disabled-weekdays'  => wp_json_encode( $this->get_property( 'disableDays', array() ) ),
			'data-disabled-date'      => wp_json_encode( $this->get_property( 'disableDates', array() ) ),
			'data-disabled-specdates' => wp_json_encode( $this->get_property( 'disableSpecificDates', '[]' ) ),
			'data-disable-today'      => wp_json_encode( $this->get_property( 'disableToday', false ) ),
			'data-disable-next-days'  => product_addons()->is_pro_feature_available() ? intval( $this->get_property( 'disableNextDays', 0 ) ) : 0,
			'data-val'                => $price_info['price'],
			'data-initdate'           => 'no',
		);

		return sprintf( '<input %s />', $this->build_attributes( $attributes ) );
	}

	/**
	 * Render time input
	 *
	 * @param array $price_info Price information
	 * @return string
	 */
	private function render_time_input( $price_info ): string {
		$format_type = $this->get_property( 'timeFormat', '12_hours' );
		$attributes  = array(
			'type'             => 'text',
			'readonly'         => true,
			'class'            => 'prad-time-input prad-custom-time-input prad-w-95 prad-cursor-pointer prad-input',
			'placeholder'      => '12_hours' == $format_type ? 'hh:mm A' : 'HH:mm',
			'data-val'         => $price_info['price'],
			'data-max-time'    => $this->get_property( 'maxTime', '' ),
			'data-min-time'    => $this->get_property( 'minTime', '' ),
			'data-inittime'    => 'no',
			'data-time-format' => $format_type,
		);

		return sprintf( '<input %s />', $this->build_attributes( $attributes ) );
	}
}
