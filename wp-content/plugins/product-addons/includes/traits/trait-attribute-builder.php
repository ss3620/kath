<?php
/**
 * Attribute Builder Trait
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Traits;

defined( 'ABSPATH' ) || exit;

/**
 * Trait for building HTML attributes
 */
trait Attribute_Builder {

	/**
	 * Build HTML attributes string from array
	 *
	 * @param array $attributes Associative array of attributes.
	 * @return string
	 */
	protected function build_attributes( array $attributes ): string {
		$attr_strings = array();

		foreach ( $attributes as $key => $value ) {
			if ( '' !== $value && null !== $value && false !== $value ) {
				if ( is_bool( $value ) && true === $value ) {
					// Boolean attributes like 'required', 'disabled'.
					$attr_strings[] = esc_attr( $key );
				} else {
					$attr_strings[] = sprintf( '%s="%s"', esc_attr( $key ), esc_attr( $value ) );
				}
			}
		}

		return implode( ' ', $attr_strings );
	}

	/**
	 * Build data attributes from array
	 *
	 * @param array $data_attributes Associative array of data attributes to be converted to HTML data-* attributes.
	 * @return array
	 */
	protected function build_data_attributes( array $data_attributes ): array {
		$attributes = array();

		foreach ( $data_attributes as $key => $value ) {
			$data_key = 'data-' . str_replace( '_', '-', $key );
			if ( ! $value ) {
				continue;
			}
			if ( is_array( $value ) || is_object( $value ) ) {
				$attributes[ $data_key ] = wp_json_encode( $value );
			} else {
				$attributes[ $data_key ] = $value;
			}
		}

		return $attributes;
	}

	/**
	 * Build CSS classes string from array
	 *
	 * @param array $classes Array of CSS class names.
	 * @return string
	 */
	protected function build_css_classes( array $classes ): string {
		$classes = array_filter(
			$classes,
			function ( $cls ) {
				return ! empty( trim( $cls ) );
			}
		);

		return implode( ' ', array_unique( $classes ) );
	}

	/**
	 * Sanitize CSS class name
	 *
	 * @param string $class_name The CSS class name to sanitize.
	 * @return string
	 */
	protected function sanitize_css_class( string $class_name ): string {
		return sanitize_html_class( $class_name );
	}
}
