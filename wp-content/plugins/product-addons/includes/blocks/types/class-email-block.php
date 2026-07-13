<?php
/**
 * Email Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Email Block Class
 */
class Email_Block extends Abstract_Block {

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'email';
	}

	/**
	 * Render the email block
	 *
	 * @return string
	 */
	public function render(): string {
		$options = $this->get_field_options();
		if ( empty( $options ) ) {
			return '';
		}

		$price_info = $this->get_price_info( $options[0] );

		$css_classes = array(
			'prad-parent',
			'prad-block-email',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);

		$attributes = array_merge(
			$this->get_common_attributes(),
			array(
				'class'      => $this->build_css_classes( $css_classes ),
				'data-ptype' => $price_info['type'],
				'data-val'   => $price_info['price'],
			)
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_price_with_position( $price_info );
		$html .= $this->render_email_input( $price_info );
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render email input section
	 *
	 * @return string
	 */
	private function render_email_input( array $price_info ): string {
		$block_id    = $this->get_block_id();
		$placeholder = $this->get_property( 'placeholder', '' );
		$value       = $this->get_property( 'value', '' );

		$html = '<div class="prad-d-flex prad-item-center prad-gap-12 prad-mb-12">';

		$input_attributes = array(
			'class'       => 'prad-w-full prad-block-input prad-input',
			'type'        => 'email',
			'placeholder' => $placeholder,
			'id'          => $block_id . '-prad-email-field',
			'value'       => $value,
			'data-val'    => $price_info['price'],
		);

		$html .= sprintf( '<input %s />', $this->build_attributes( $input_attributes ) );

		if ( $this->should_show_price_beside_field( $price_info ) ) {
			$html .= $this->render_price_html( $price_info, 'beside' );
		}

		$html .= '</div>';

		return $html;
	}
}
