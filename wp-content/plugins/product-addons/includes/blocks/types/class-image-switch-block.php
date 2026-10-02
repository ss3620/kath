<?php
/**
 * Image Switch Block Implementation
 *
 * Deprecated, kept only as legacy support: the Image Swatches field can no longer be
 * added in the builder. This class stays so Image Swatches fields that existing users
 * already saved on their products keep working, and it will be removed in a future release.
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Image Switch Block Class
 */
class Image_Switch_Block extends Abstract_Block {

	/**
	 * SVG icon for checkmark
	 */
	const CHECKMARK_ICON = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" width="16" height="16" viewBox="0 0 16 16">
        <rect width="16" height="16" fill="currentColor" rx="2" />
        <path stroke="#fff" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m12.125 5.375-5.25 5.25L4.25 8" />
    </svg>';

	/**
	 * Get block type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'img_switch';
	}

	/**
	 * Render the image switch block
	 *
	 * @return string
	 */
	public function render(): string {
		// Fields made with WowAddons Pro's "Advanced Image Swatches" are Pro fields, rendered by Pro only.
		if ( $this->get_property( 'isAdvanced', false ) ) {
			return '';
		}

		$options = $this->get_field_options();
		if ( empty( $options ) ) {
			return '';
		}

		$attributes = array_merge(
			$this->get_common_attributes(),
			$this->get_switch_attributes()
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_noprice();
		$html .= $this->render_swatch_wrapper( $options );
		$html .= $this->render_description_below_field();
		$html .= $this->render_tooltip();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get switch specific attributes
	 *
	 * @return array
	 */
	private function get_switch_attributes(): array {
		$multiple   = $this->get_property( 'multiple', false );
		$input_type = $multiple ? 'checkbox' : 'radio';
		$layout     = $this->get_property( 'layout', '_default' );

		$css_classes = array(
			'prad-parent',
			'prad-block-img-swatches',
			'prad-type_swatches-input',
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
	 * Render swatch wrapper with all image options
	 *
	 * @param array $options Options to render.
	 * @return string
	 */
	private function render_swatch_wrapper( $options ) {
		$html = '<div class="prad-swatch-wrapper">';

		foreach ( $options as $index => $item ) {
			$html .= $this->render_single_swatch( $item, $index );
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Get image swatch wrapper attributes
	 *
	 * @param object $item Option data.
	 * @return array
	 */
	private function get_swatch_wrapper_attributes( $item ): array {
		$update_product_image = $this->get_property( 'updateProductImage', false );
		$thumbnail            = '';
		$thumbnail_prop       = '';

		if ( $update_product_image && isset( $item['imgid'] ) ) {
			$thumbnail                              = wp_get_attachment_image_src( $item['imgid'], 'thumbnail' ) ? wp_get_attachment_image_src( $item['imgid'], 'thumbnail' )[0] : '';
			$woocommerce_gallery_thumbnail          = wp_get_attachment_image_src( $item['imgid'], 'woocommerce_gallery_thumbnail' ) ? wp_get_attachment_image_src( $item['imgid'], 'woocommerce_gallery_thumbnail' )[0] : '';
			$thumbnail_prop                         = wc_get_product_attachment_props( $item['imgid'] );
			$thumbnail_prop['pradGalleryThumbnail'] = $woocommerce_gallery_thumbnail;
		}

		return array(
			'class'                   => 'prad-swatch-item-wrapper prad-relative prad-d-flex prad-flex-column prad-h-full',
			'data-product-image'      => wp_json_encode( $thumbnail ),
			'data-product-image-prop' => wp_json_encode( $thumbnail_prop ),
			'data-product-imageid'    => isset( $item['imgid'] ) ? $item['imgid'] : '',
		);
	}

	/**
	 * Render a single image swatch
	 *
	 * @param object  $item Option data.
	 * @param integer $index Option index.
	 * @return string
	 */
	private function render_single_swatch( $item, int $index ): string {
		$price_info         = $this->get_price_info( $item );
		$wrapper_attributes = $this->get_swatch_wrapper_attributes( $item );
		$layout             = $this->get_property( 'layout', '_default' );

		$html  = sprintf( '<div %s>', $this->build_attributes( $wrapper_attributes ) );
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
		$hover_class = $this->get_hover_class();
		$layout      = $this->get_property( 'layout', '_default' );

		$html  = sprintf(
			'<div class="prad-swatch-container prad-p-2 prad-w-fit prad-relative prad-hover-%s-bottom">',
			esc_attr( $hover_class )
		);
		$html .= $this->render_swatch_input( $item, $index, $price_info );
		$html .= $this->render_swatch_label( $item, $index, $price_info );
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
			'data-ptype' => $price_info['type'],
			'data-label' => $item['value'],
		);

		return sprintf( '<input %s />', $this->build_attributes( $attributes ) );
	}

	/**
	 * Render the swatch label with image
	 *
	 * @param object  $item Option data.
	 * @param integer $index Option index.
	 * @param array   $price_info Price data for the option.
	 * @return string
	 */
	private function render_swatch_label( $item, int $index, array $price_info ): string {
		$blockid = $this->get_block_id();
		$img_url = ! empty( $item['img'] ) ? $item['img'] : PRAD_URL . 'assets/img/default-product.svg';

		$html  = sprintf( '<label class="prad-lh-0 prad-mb-0" for="%s">', esc_attr( $blockid . $index ) );
		$html .= sprintf(
			'<img class="prad-swatch-item" title="%s" src="%s" alt="swatch item" data-tooltip-label="%s" />',
			esc_attr( $price_info['price'] ),
			esc_url( $img_url ),
			esc_attr( $item['value'] ?? '' )
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
		return sprintf( '<div class="prad-swatch-mark-image">%s</div>', self::CHECKMARK_ICON );
	}

	/**
	 * Render the hidden image preview tooltip container.
	 *
	 * Deprecated, legacy support: "Enable Image Preview" has always been a free setting of
	 * the Image Swatches field, so Image Swatches fields existing users already saved keep
	 * their image preview. It goes away with this field type in a future release.
	 *
	 * @return string
	 */
	private function render_tooltip(): string {
		$enable_preview = $this->get_property( 'enableImagePreview', false );
		if ( ! $enable_preview ) {
			return '';
		}
		return '
		<div class="prad-img-tooltip" role="tooltip" aria-hidden="true">
			<img src="" alt="" />
			<span class="prad-img-tooltip-label"></span>
		</div>';
	}
}
