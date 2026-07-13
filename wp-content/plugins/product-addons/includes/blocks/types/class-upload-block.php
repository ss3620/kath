<?php
/**
 * Upload Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Upload Block Class
 */
class Upload_Block extends Abstract_Block {

	private $allowed_file_types = array();

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'upload';
	}

	/**
	 * Render the upload block
	 *
	 * @return string
	 */
	public function render(): string {
		$options = $this->get_field_options();

		if ( empty( $options ) ) {
			return '';
		}

		$item       = $options[0];
		$price_info = $this->get_price_info( $item );

		$attributes = array_merge(
			$this->get_common_attributes(),
			$this->get_upload_attributes( $price_info )
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_price_with_position( $price_info );
		$html .= $this->render_upload_section( $item, $price_info );
		$html .= $this->render_description_below_field();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get checkbox specific attributes
	 *
	 * @return array
	 */
	private function get_upload_attributes( $price_info ): array {
		$attributes  = array();
		$css_classes = array(
			'prad-parent',
			'prad-block-upload',
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);

		$allowed = $this->get_property( 'allowedFileTypes', array() );
		if ( ! product_addons()->is_pro_feature_available() ) {
			$allowed = array_values(
				array_filter(
					$allowed,
					function ( $ext ) {
						return in_array( $ext, array( 'jpg', 'jpeg', 'png' ), true );
					}
				)
			);
		}

		$this->allowed_file_types = $allowed;

		$attributes['data-ptype']         = $price_info['type'];
		$attributes['data-max_size']      = $this->get_property( 'maxSize', '' );
		$attributes['data-size_prefix']   = $this->get_property( 'sizePrefix', '' );
		$attributes['data-size_error']    = $this->get_property( 'sizeError', '' );
		$attributes['data-number_prefix'] = $this->get_property( 'numberPrefix', '' );
		$attributes['data-max_number']    = $this->get_property( 'maxNumber', '' );
		$attributes['data-min_number']    = $this->get_property( 'minNumber', '' );
		$attributes['data-number_error']  = $this->get_property( 'numberError', '' );
		$attributes['data-allowed']       = wp_json_encode( $allowed );
		$attributes['data-val']           = $price_info['price'];

		$attributes['class'] = $this->build_css_classes( $css_classes );

		return $attributes;
	}


	/**
	 * Render upload section
	 *
	 * @param object $item Upload item
	 * @param array  $price_info Price information
	 * @return string
	 */
	private function render_upload_section( $item, array $price_info ): string {
		$block_id      = $this->get_block_id();
		$allowed_types = $this->allowed_file_types;
		$accept_types  = $this->get_accept_types( $allowed_types );

		$html  = '<div class="prad-upload-wrapper">';
		$html .= '<div class="">';
		$html .= '<div class="prad-block-upload-title prad-d-flex prad-item-center prad-gap-4 prad-w-full">';
		$html .= $this->render_upload_label( $block_id, $price_info, $accept_types );
		$html .= ' </div>';
		$html .= ' </div>';
		$html .= '<div class="prad-upload-result"></div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render upload label and dropzone
	 *
	 * @param string $block_id Block ID
	 * @param array  $price_info Price information
	 * @param string $accept_types Accepted file types
	 * @return string
	 */
	private function render_upload_label( string $block_id, array $price_info, string $accept_types ): string {
		$drag_drop_text = $this->get_property( 'dragDropText', 'Click or drag and drop' );
		$html           = sprintf(
			'<label for="prad_block_upload_%s" class="prad-upload-container prad-drop-zone prad-border-none prad-bg-transparent prad-m-0 prad-w-full">',
			esc_attr( $block_id )
		);

		$html .= '<div class="prad-d-flex prad-item-center prad-gap-12">';

			// Upload icon
			$html         .= $this->render_upload_icon();
			$html         .= '<div class="prad-block-upload-text prad-block-upload-title">';
				$html     .= $this->render_file_input( $block_id, $price_info, $accept_types );
				$html     .= '<div class="prad-cursor-pointer">';
					$html .= esc_html( $drag_drop_text );
				$html     .= '</div>';
			$html         .= '</div>';

		$html .= '</div>';

		$html .= '</label>';

		return $html;
	}

	/**
	 * Render file input
	 *
	 * @param string $block_id Block ID
	 * @param array  $price_info Price information
	 * @param string $accept_types Accepted file types
	 * @return string
	 */
	private function render_file_input( string $block_id, array $price_info, string $accept_types ): string {
		$input_attributes = array(
			'id'       => 'prad_block_upload_' . $block_id,
			'class'    => 'prad-input-hidden prad-upload-input',
			'type'     => 'file',
			'hidden'   => 'hidden',
			'data-val' => $price_info['price'],
			'multiple' => 'multiple',
		);

		if ( $accept_types ) {
			$input_attributes['accept'] = $accept_types;
		}

		return sprintf( '<input %s />', $this->build_attributes( $input_attributes ) );
	}

	/**
	 * Render upload icon
	 *
	 * @return string
	 */
	private function render_upload_icon(): string {
		$upload_text = $this->get_property( 'uploadText', 'Upload' );
		return '<div class="prad-block-upload-icon prad-d-flex prad-item-center prad-gap-6">
		
		<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
			<path d="M11.5556 2.45333C11.5556 2.82152 11.2571 3.12 10.8889 3.12C10.5207 3.12 10.2222 2.82152 10.2222 2.45333V2C10.2222 1.63181 9.92375 1.33333 9.55556 1.33333L2 1.33333C1.63181 1.33333 1.33333 1.63181 1.33333 2V2.45333C1.33333 2.82152 1.03486 3.12 0.666667 3.12C0.298477 3.12 0 2.82152 0 2.45333V2C0 0.895431 0.895431 0 2 0H9.55556C10.6601 0 11.5556 0.895431 11.5556 2V2.45333Z" fill="#1A1A1A"/>
			<path d="M6.44445 11.12C6.44445 11.4882 6.14597 11.7867 5.77778 11.7867C5.40959 11.7867 5.11111 11.4882 5.11111 11.12V5.17392L3.58252 6.70252C3.32217 6.96287 2.90006 6.96287 2.63971 6.70252C2.37936 6.44217 2.37936 6.02006 2.63971 5.75971L5.30637 3.09304C5.56672 2.83269 5.98883 2.83269 6.24918 3.09304L8.91585 5.75971C9.1762 6.02006 9.1762 6.44217 8.91585 6.70252C8.6555 6.96287 8.23339 6.96287 7.97304 6.70252L6.44445 5.17392V11.12Z" fill="#1A1A1A"/>
		</svg>

			<div>' . esc_html( $upload_text ) . '</div>
		</div>';
	}


	/**
	 * Get accept types string for file input
	 *
	 * @param array $allowed_types Array of allowed file extensions
	 * @return string
	 */
	private function get_accept_types( array $allowed_types ): string {
		if ( empty( $allowed_types ) || ! is_array( $allowed_types ) ) {
			return '';
		}

		return implode( ',', array_map( fn( $ext ) => '.' . trim( $ext ), $allowed_types ) );
	}
}
