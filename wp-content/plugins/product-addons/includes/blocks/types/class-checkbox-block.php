<?php
/**
 * Checkbox Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Checkbox Block Class
 */
class Checkbox_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'checkbox';
	}

	/**
	 * Render the checkbox block
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
			$this->get_checkbox_attributes(),
			$this->get_same_price_attributes()
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_block_heading();
		$html .= $this->render_checkbox_group( $options );
		$html .= $this->render_description_below_field();
		$html .= $this->render_image_preview();

		$html .= '</div>';

		return $html;
	}

	/**
	 * Get checkbox specific attributes
	 *
	 * @return array
	 */
	private function get_checkbox_attributes(): array {
		$attributes          = array();
		$css_classes         = array(
			'prad-parent',
			'prad-block-checkbox',
			'prad-type-checkbox-input',
			'prad-switcher-count',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
			'prad-block-item-img-parent prad-block-img-' . $this->get_property( 'imgStyle', 'normal' ),
		);
		$attributes['class'] = $this->build_css_classes( $css_classes );
		$enable_min_max_res  = $this->get_property( 'enableMinMaxRes', true );

		if ( $enable_min_max_res ) {
			$attributes['data-minselect'] = $this->get_property( 'minSelect', 1 );
			$attributes['data-maxselect'] = $this->get_property( 'maxSelect', 100 );
		}

		return $attributes;
	}

	/**
	 * Get column class based on columns count
	 *
	 * @return string
	 */
	private function get_column_class(): string {
		$columns = (int) $this->get_property( 'columns', 1 );
		return (string) min( max( $columns, 1 ), 3 );
	}

	/**
	 * Get Fixed Height Attributes
	 *
	 * @return string
	 */
	private function get_fixed_height_attributes(): array {
		$fixed_height = $this->get_property( 'fixedHeight', '' );

		if ( empty( $fixed_height ) ) {
			return array(
				'class' => '',
				'style' => '',
			);
		}

		return array(
			'class' => ' prad-overflow-x-hidden prad-overflow-y-auto prad-scrollbar prad-pb-8 prad-pt-8 prad-p-8 prad-fixed-height',
			'style' => sprintf( 'style="max-height: %spx;"', esc_attr( $fixed_height ) ),
		);
	}

	/**
	 * Render checkbox group
	 *
	 * @param array $options Field options.
	 * @return string
	 */
	private function render_checkbox_group( $options ): string {
		$column_class = $this->get_column_class();
		$fixed_height = $this->get_fixed_height_attributes();

		$html = sprintf(
			'<div class="prad-input-container prad-column-%s%s" %s >%s</div>',
			esc_attr( $column_class ),
			esc_attr( $fixed_height['class'] ),
			$fixed_height['style'],
			$this->render_checkbox_options( $options )
		);

		return $html;
	}

	/**
	 * Render all checkbox options
	 *
	 * @param array $options Field options.
	 * @return string
	 */
	private function render_checkbox_options( $options ): string {
		$html = '';

		foreach ( $options as $index => $item ) {
			$html .= $this->render_checkbox_item( $item, $index );
		}

		return $html;
	}

	/**
	 * Get checkbox item wrapper class
	 *
	 * @return string
	 */
	private function get_checkbox_item_wrapper_class(): string {
		$justify = 'left';

		return sprintf(
			'prad-checkbox-item-wrapper prad-d-flex prad-item-start prad-gap-8 prad-justify-%s',
			esc_attr( $justify )
		);
	}

	/**
	 * Get checkbox input attributes
	 *
	 * @param array   $item       Option item.
	 * @param integer $index      Option index.
	 * @param array   $price_info Price information.
	 * @return array
	 */
	private function get_checkbox_input_attributes( $item, int $index, array $price_info ): array {
		$blockid = $this->get_block_id();

		return array_merge(
			array(
				'class'      => 'prad-input-hidden',
				'type'       => 'checkbox',
				'id'         => $blockid . $index,
				'name'       => 'prad-checkbox-' . $blockid,
				'value'      => $price_info['price'],
				'data-ptype' => $item['type'],
				'data-index' => $index,
				'data-uid'   => $item['uid'] ?? '',
				'data-label' => $item['value'],
			),
			$this->get_option_extra_attributes( $item, $index )
		);
	}

	/**
	 * Render single checkbox
	 *
	 * @param array   $item  Option item.
	 * @param integer $index Option index.
	 * @return string
	 */
	private function render_checkbox_item( array $item, int $index ): string {
		$price_info    = $this->get_price_info( $item );
		$wrapper_class = $this->get_checkbox_item_wrapper_class();

		$html  = sprintf( '<div class="%s">', esc_attr( $wrapper_class ) );
		$html .= $this->render_checkbox_input_group( $item, $index, $price_info );

		if ( 'no_cost' !== $item['type'] || $this->has_option_input() ) {
			$html .= $this->render_price_and_input( $item, $index, $price_info );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render checkbox input with label
	 *
	 * @param object  $item       Option item.
	 * @param integer $index      Option index.
	 * @param array   $price_info Price information.
	 * @return string
	 */
	private function render_checkbox_input_group( $item, int $index, array $price_info ): string {
		$blockid      = $this->get_block_id();
		$allowed_tags = $this->allowed_html_tags;

		$html  = '<div class="prad-checkbox-item prad-d-flex prad-item-center prad-gap-10">';
		$html .= sprintf( '<input %s />', $this->build_attributes( $this->get_checkbox_input_attributes( $item, $index, $price_info ) ) );
		$html .= sprintf( '<label for="%s" class="prad-d-flex prad-item-start prad-gap-10">', esc_attr( $blockid . $index ) );
		$html .= '<div class="prad-checkbox-mark  prad-selection-none"><svg
								width="12"
								height="12"
								viewBox="0 0 12 12"
								fill="none"
								xmlns="http://www.w3.org/2000/svg"
							>
								<path
									d="m10.125 3.375-5.25 5.25L2.25 6"
									stroke="currentColor"
									stroke-width="1.5"
									stroke-linecap="round"
									stroke-linejoin="round"
								/>
							</svg></div>';
		$html .= $this->render_checkbox_content( $item, $allowed_tags );
		$html .= '</label>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render checkbox content
	 *
	 * @param object $item         Option item.
	 * @param array  $allowed_tags Allowed HTML tags.
	 * @return string
	 */
	private function render_checkbox_content( $item, array $allowed_tags ): string {
		$html = '<div class="prad-block-content prad-d-flex prad-item-center">';

		$html .= apply_filters( 'prad_option_image_html', '', $item, $this );

		$html .= '<div class="prad-option-content">';
		$html .= sprintf(
			'<div title="%1$s" class="prad-ellipsis-2">%1$s</div>',
			wp_kses( $item['value'], $allowed_tags )
		);
		$html .= $this->render_option_description( $item );
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render the option's price and input
	 *
	 * @param object  $item       Option item.
	 * @param integer $index      Option index.
	 * @param array   $price_info Price information.
	 * @return string
	 */
	private function render_price_and_input( $item, int $index, array $price_info ): string {
		$columns      = $this->get_property( 'columns', 1 );
		$allowed_tags = $this->allowed_html_tags;

		$html = '<div class="prad-d-flex prad-item-start prad-gap-12">';

		if ( 'no_cost' !== $item['type'] && ! $this->has_shared_price() ) {
			$html .= sprintf(
				'<div class="prad-block-price prad-text-upper">%s</div>',
				wp_kses( $price_info['html'], $allowed_tags )
			);
		}

		if ( 1 === (int) $columns ) {
			$html .= $this->render_option_input( $index );
		}

		$html .= '</div>';

		return $html;
	}
}
