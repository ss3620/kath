<?php
/**
 * Button Block Implementation
 *
 * Deprecated, kept only as legacy support: the Button field can no longer be added in
 * the builder. This class stays so Button fields that existing users already saved on
 * their products keep working, and it will be removed in a future release.
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
		// Fields made with WowAddons Pro's "Advanced Button" are Pro fields, rendered by Pro only.
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

		$html = sprintf( '<div %s>', $this->build_attributes( $attributes ) );

		$html .= $this->render_title_description_noprice();
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

		$multiple           = $this->get_property( 'multiple', false );
		$enable_min_max_res = $this->get_property( 'enableMinMaxRes', true );
		if ( $multiple && $enable_min_max_res ) {
			$attributes['data-minselect'] = $this->get_property( 'minSelect', '' );
			$attributes['data-maxselect'] = $this->get_property( 'maxSelect', '' );
		}

		return $attributes;
	}

	/**
	 * Render the group of buttons
	 *
	 * @param array $options Options to render.
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
	 * @param array $options Options to render.
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
	 * @param object $item Option data.
	 * @param int    $index Option index.
	 * @return string
	 */
	private function render_single_button( $item, int $index ): string {
		$price_obj              = $this->get_price_info( $item );
		$multiple               = $this->get_property( 'multiple', false );
		$input_type             = $multiple ? 'checkbox' : 'radio';
		$prad_allowed_html_tags = $this->allowed_html_tags;

		$html = '<div class="prad-button-container">';

		// Input.
		$html .= $this->render_button_input( $item, $index, $price_obj, $input_type );

		// Label.
		$html .= $this->render_button_label( $item, $index, $price_obj, $prad_allowed_html_tags );

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render the hidden input for a button
	 *
	 * @param object $item Option data.
	 * @param int    $index Option index.
	 * @param array  $price_obj Price data for the option.
	 * @param string $input_type Input type (radio or checkbox).
	 * @return string
	 */
	private function render_button_input( $item, int $index, array $price_obj, string $input_type ): string {
		$blockid = $this->get_block_id();

		$input_attributes = array(
			'class'      => 'prad-input-hidden',
			'type'       => $input_type,
			'data-index' => $index,
			'data-uid'   => $item['uid'] ?? '',
			'id'         => $blockid . $index,
			'name'       => $blockid,
			'value'      => $price_obj['price'],
			'data-ptype' => $price_obj['type'],
			'data-label' => $item['value'],
		);

		return sprintf( '<input %s />', $this->build_attributes( $input_attributes ) );
	}

	/**
	 * Render the button label
	 *
	 * @param object $item Option data.
	 * @param int    $index Option index.
	 * @param array  $price_obj Price data for the option.
	 * @param array  $allowed_tags Allowed HTML tags for output escaping.
	 * @return string
	 */
	private function render_button_label( $item, int $index, array $price_obj, array $allowed_tags ): string {
		$blockid = $this->get_block_id();

		$html  = sprintf( '<label class="prad-mb-0" for="%s">', esc_attr( $blockid . $index ) );
		$html .= '<div class="prad-button-item prad-w-fit prad-d-flex prad-item-center prad-gap-8">';

		// Value.
		$html .= sprintf(
			'<div title="%s" class="prad-ellipsis-2 prad-text-%s" style="min-width: %s">%s</div>',
			wp_kses( $item['value'], $allowed_tags ),
			'no_cost' !== $item['type'] ? 'start' : 'center',
			'no_cost' !== $item['type'] ? 'unset' : '2rem',
			wp_kses( $item['value'], $allowed_tags )
		);

		// Price.
		if ( 'no_cost' !== $item['type'] ) {
			$html .= sprintf(
				'<div class="prad-block-price prad-text-upper">%s</div>',
				wp_kses( $price_obj['html'], $allowed_tags )
			);
		}

		$html .= '</div></label>';

		return $html;
	}
}
