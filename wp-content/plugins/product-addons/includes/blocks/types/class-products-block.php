<?php
/**
 * Products Block Implementation
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Blocks\Types;

use PRAD\Includes\Blocks\Abstracts\Abstract_Block;

defined( 'ABSPATH' ) || exit;

/**
 * Products Block Class
 */
class Products_Block extends Abstract_Block {

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
		return 'products';
	}

	/**
	 * Render the products block
	 *
	 * @return string
	 */
	public function render(): string {
		$options = $this->process_product_options();
		if ( empty( $options ) ) {
			return '';
		}

		$attributes = array_merge(
			$this->get_common_attributes(),
			$this->get_products_attributes()
		);

		$html  = sprintf( '<div %s>', $this->build_attributes( $attributes ) );
		$html .= $this->render_title_description_noprice();
		$html .= $this->render_products_wrapper( $options );
		$html .= $this->render_description_below_field();
		$html .= $this->render_tooltip();
		$html .= '</div>';

		return $html;
	}

	/**
	 * Process product options
	 *
	 * @return array
	 */
	private function process_product_options(): array {
		global $product;
		$product_id = $product ? $product->get_id() : 0;

		$manual_products = $this->get_property( 'manualProducts', array() );
		$merge_variation = $this->get_property( 'mergeVariation', false );
		$options         = array();

		foreach ( $manual_products as $item ) {

			if ( isset( $item['variation'] ) ) {
				if ( $merge_variation ) {
					$product_data = $this->get_product_data( $item['id'], true );
					if ( $product_data && $product_data->is_in_stock && $product_data->is_purchasable ) {
						$options[] = $product_data;
					}
				} elseif ( is_array( $item['variation'] ) ) {
					foreach ( $item['variation'] as $v_id ) {
						$product_data = $this->get_product_data( $v_id, false );
						if ( $product_data && $product_data->is_in_stock && $product_data->is_purchasable ) {
							$options[] = $product_data;
						}
					}
				}
			} else {
				$product_data = $product_id == $item['id'] ? '' : $this->get_product_data( $item['id'], false );
				if ( $product_data && $product_data->is_in_stock && $product_data->is_purchasable ) {
					$options[] = $product_data;
				}
			}
		}

		if ( ! product_addons()->is_pro_feature_available() && is_array( $options ) && count( $options ) > 2 ) {
			$options = array_slice( $options, 0, 2 );
		}

		return $options;
	}

	/**
	 * Get product data
	 *
	 * @param integer $product_id
	 * @param boolean $with_variations
	 * @return object|null
	 */
	private function get_product_data( int $product_id, bool $with_variations ) {
		return \product_addons()->get_product_block_product_attr( $product_id, $with_variations );
	}

	/**
	 * Get products specific attributes
	 *
	 * @return array
	 */
	private function get_products_attributes(): array {
		$block_type = $this->get_property( 'blockType', '' );
		$input_type = $this->get_input_type();
		$layout     = $this->get_property( 'layout', '_default' );

		$attributes  = array();
		$css_classes = array(
			'prad-parent',
			'prad-block-products',
			'prad-type' . ( '_swatches' === $block_type ? $block_type : '-' . $input_type ) . '-input',
			'prad-switcher-count',
			'prad-swatch-layout' . $layout,
			'prad-block-' . $this->get_block_id(),
			$this->get_css_class(),
		);
		if ( '_swatches' !== $block_type ) {
			$css_classes[] = 'prad-block-item-img-parent prad-block-img-' . $this->get_property( 'imgStyle', 'normal' );
		}
		$attributes['data-input-type'] = $input_type;
		$attributes['class']           = $this->build_css_classes( $css_classes );

		$enableMinMaxRes = $this->get_property( 'enableMinMaxRes', true );
		if ( 'checkbox' === $input_type && $enableMinMaxRes ) {
			$attributes['data-minselect'] = $this->get_property( 'minSelect', '' );
			$attributes['data-maxselect'] = $this->get_property( 'maxSelect', '' );
		}

		return $attributes;
	}

	/**
	 * Get input type based on block type
	 *
	 * @return string
	 */
	private function get_input_type(): string {
		$block_type = $this->get_property( 'blockType', '' );
		$multiple   = $this->get_property( 'multiple', false );

		if ( '_swatches' === $block_type ) {
			return $multiple ? 'checkbox' : 'radio';
		}

		return '_radios' === $block_type ? 'radio' : 'checkbox';
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
	 * Get column class
	 *
	 * @return string
	 */
	private function get_column_class(): string {
		$columns = $this->get_property( 'columns', 1 );
		return (string) min( max( (int) $columns, 1 ), 3 );
	}

	/**
	 * Render products wrapper
	 *
	 * @return string
	 */
	private function render_products_wrapper( $options ) {
		$block_type    = $this->get_property( 'blockType', '' );
		$wrapper_class = '_swatches' === $block_type ?
			'prad-swatch-wrapper' :
			'prad-input-container prad-column-' . $this->get_column_class();

		$html  = sprintf( '<div class="%s">', esc_attr( $wrapper_class ) );
		$html .= $this->render_product_items( $options );
		$html .= '</div>';

		return $html;
	}

	/**
	 * Render product items
	 *
	 * @return string
	 */
	private function render_product_items( $options ) {
		$html = '';

		foreach ( $options as $index => $item ) {
			$html .= $this->render_product_item( $item, $index );
		}

		return $html;
	}

	/**
	 * Render single product item
	 *
	 * @param object  $item
	 * @param integer $index
	 * @return string
	 */
	private function render_product_item( $item, int $index ): string {
		$block_type     = $this->get_property( 'blockType', '' );
		$variation_html = $this->get_variation_html( $item, $index );

		return '_swatches' === $block_type ?
			$this->render_swatch_item( $item, $index, $variation_html ) :
			$this->render_input_item( $item, $index, $variation_html );
	}

	/**
	 * Get variation HTML
	 *
	 * @param object  $item
	 * @param integer $index
	 * @return string
	 */
	private function get_variation_html( $item, int $index ): string {
		return \product_addons()->generate_products_block_variation_section_html(
			array(
				'item'  => $item,
				'index' => $index,
			)
		);
	}

	/**
	 * Render swatch item
	 *
	 * @param object  $item
	 * @param integer $index
	 * @param string  $variation_html
	 * @return string
	 */
	private function render_swatch_item( $item, int $index, string $variation_html ): string {
		$price_info  = product_addons()->get_price_object( $item->regular, $item->sale );
		$layout      = $this->get_property( 'layout', '_default' );
		$hover_class = $this->get_hover_class();

		$html  = '<div class="prad-products-item-wrapper prad-swatch-item-wrapper prad-relative prad-d-flex prad-flex-column prad-h-full">';
		$html .= $this->render_swatch_container( $item, $index, $price_info, $hover_class, $layout );

		if ( $layout === '_default' ) {
			$html .= $this->render_block_content( $item, $index, $price_info, $variation_html );
		}

		if ( $this->should_render_quantity_input( $layout ) && product_addons()->is_pro_feature_available() ) {
			$html .= $this->render_quantity_input( $index );
		}

		if ( '_overlay' === $layout || '_img' === $layout ) {
			$html .= $variation_html;
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Render swatch container
	 *
	 * @param object  $item
	 * @param integer $index
	 * @param array   $price_info
	 * @param string  $hover_class
	 * @param string  $layout
	 * @return string
	 */
	private function render_swatch_container( $item, int $index, array $price_info, string $hover_class, string $layout ): string {
		$html  = sprintf(
			'<div class="prad-swatch-container prad-p-2 prad-w-fit prad-relative prad-hover-%s-bottom">',
			esc_attr( $hover_class )
		);
		$html .= $this->render_swatch_input( $item, $index, $price_info );
		$html .= $this->render_swatch_label( $item, $index );
		$html .= $this->render_swatch_mark();

		if ( $layout === '_overlay' ) {
			$html .= $this->render_block_content( $item, $index, $price_info );
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Render swatch input
	 *
	 * @param object  $item
	 * @param integer $index
	 * @param array   $price_info
	 * @return string
	 */
	private function render_swatch_input( $item, int $index, array $price_info ): string {
		$enable_count = $this->get_property( 'enableCount', false );
		$input_type   = $this->get_input_type();
		$blockid      = $this->get_block_id();
		$item_id      = $blockid . $index;

		$attributes = array(
			'class'           => 'prad-input-hidden',
			'type'            => $input_type,
			'data-index'      => $index,
			'id'              => $item_id,
			'name'            => $blockid,
			'value'           => $price_info['price'],
			'data-ptype'      => $item->type,
			'data-product-id' => $item->id,
			'data-label'      => $item->value,
			'data-count'      => $enable_count ? 'yes' : 'no',
			'data-counter'    => $item_id . '-switcher-count',
		);

		return sprintf( '<input %s />', $this->build_attributes( $attributes ) );
	}

	/**
	 * Render swatch label
	 *
	 * @param object  $item
	 * @param integer $index
	 * @return string
	 */
	private function render_swatch_label( $item, int $index ): string {
		$blockid = $this->get_block_id();
		$img_url = isset( $item->img ) ? $item->img : PRAD_URL . 'assets/img/default-product.svg';

		return sprintf(
			'<label class="prad-lh-0 prad-mb-0" for="%s"><img class="prad-swatch-item" title="%s" src="%s" alt="swatch item" data-tooltip-label="%s" /></label>',
			esc_attr( $blockid . $index ),
			esc_attr( $item->value ),
			esc_url( $img_url ),
			esc_attr( $item->value )
		);
	}

	/**
	 * Render swatch mark
	 *
	 * @return string
	 */
	private function render_swatch_mark(): string {
		return sprintf(
			'<div class="prad-swatch-mark-image" style="border: 1px solid #fff; padding: 1px !important; border-radius: 2px;">%s</div>',
			self::CHECKMARK_ICON
		);
	}

	/**
	 * Render input item
	 *
	 * @param object  $item
	 * @param integer $index
	 * @param string  $variation_html
	 * @return string
	 */
	private function render_input_item( $item, int $index, string $variation_html ): string {
		$price_info   = product_addons()->get_price_object( $item->regular, $item->sale );
		$input_type   = $this->get_input_type();
		$enable_count = $this->get_property( 'enableCount', false );
		$column_class = $this->get_column_class();

		$wrapper_class = 'prad-products-item-wrapper prad-d-flex prad-item-center prad-gap-8 prad-column-' . $column_class;

		$html  = sprintf( '<div class="%s">', esc_attr( $wrapper_class ) );
		$html .= '<div class="prad-d-flex ' . ( $variation_html ? 'prad-item-start' : 'prad-item-center' ) . ' prad-gap-8">';
		$html .= $this->render_input_group( $item, $index, $input_type, $variation_html );

		if ( $item->type != 'no_cost' || $enable_count ) {
			$html .= $this->render_price_and_quantity( $item, $index, $price_info );
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Render input group with label
	 *
	 * @param object  $item
	 * @param integer $index
	 * @param string  $input_type
	 * @param string  $variation_html
	 * @return string
	 */
	private function render_input_group( $item, int $index, string $input_type, string $variation_html ): string {
		$price_info   = product_addons()->get_price_object( $item->regular, $item->sale );
		$enable_count = $this->get_property( 'enableCount', false );
		$blockid      = $this->get_block_id();
		$allowed_tags = $this->allowed_html_tags;

		$html = sprintf( '<div class="prad-%s-item prad-d-flex prad-item-center prad-gap-10">', esc_attr( $input_type ) );

		// Input
		$input_attributes = array(
			'class'           => 'prad-input-hidden',
			'type'            => $input_type,
			'id'              => $blockid . $index,
			'name'            => 'prad-' . $input_type . '-' . $blockid,
			'value'           => $price_info['price'],
			'data-ptype'      => $item->type,
			'data-product-id' => $item->id,
			'data-index'      => $index,
			'data-label'      => $item->value,
			'data-count'      => $enable_count ? 'yes' : 'no',
			'data-counter'    => $blockid . $index . '-switcher-count',
		);

		$html .= sprintf( '<input %s />', $this->build_attributes( $input_attributes ) );

		// Label
		$html .= sprintf( '<label for="%s" class="prad-d-flex prad-item-center prad-gap-10">', esc_attr( $blockid . $index ) );
		$html .= $this->render_input_mark( $input_type );
		$html .= $this->render_input_content( $item, $variation_html, $allowed_tags );
		$html .= '</label>';

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render input mark (radio/checkbox)
	 *
	 * @param string $input_type
	 * @return string
	 */
	private function render_input_mark( string $input_type ): string {
		if ( $input_type === 'radio' ) {
			return '<div class="prad-radio-mark prad-br-round prad-realtive prad-selection-none"></div>';
		}

		return '<div class="prad-checkbox-mark prad-selection-none">
            <svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="m10.125 3.375-5.25 5.25L2.25 6" stroke="currentColor" stroke-width="1.5" 
                    stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </div>';
	}

	/**
	 * Render input content
	 *
	 * @param object $item
	 * @param string $variation_html
	 * @param array  $allowed_tags
	 * @return string
	 */
	private function render_input_content( $item, string $variation_html, array $allowed_tags ): string {
		$p_url = isset( $item->url ) ? $item->url : '';
		$html  = '<div class="prad-block-content prad-d-flex prad-item-center">';

		if ( isset( $item->img ) && $item->img && product_addons()->is_pro_feature_available() ) {
			$html .= sprintf( '<img class="prad-block-item-img" src="%s" alt="Item" data-tooltip-label="%s" />', esc_url( $item->img ), esc_attr( $item->value ) );
		}

		$class      = 'prad-ellipsis-2';
		$attributes = array(
			'title' => $item->value,
			'class' => $class,
		);

		if ( $p_url ) {
			$attributes['class']     .= ' prad-cursor-pointer prad-product-link';
			$attributes['data-phref'] = $p_url;
		}

		if ( $variation_html ) {
			$html .= '<div>';
			$html .= sprintf(
				'<div %1$s>%2$s</div>',
				$this->build_attributes( $attributes ),
				wp_kses( $item->value, $allowed_tags )
			);
			$html .= $variation_html;
			$html .= '</div>';
		} else {
			$html .= '<div ' . $this->build_attributes( $attributes ) . '>';
			$html .= wp_kses( $item->value, $allowed_tags );
			$html .= '</div>';
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Render price and quantity section
	 *
	 * @param object  $item
	 * @param integer $index
	 * @param array   $price_info
	 * @return string
	 */
	private function render_price_and_quantity( $item, int $index, array $price_info ): string {
		$enable_count = $this->get_property( 'enableCount', false );
		$allowed_tags = $this->allowed_html_tags;

		$html = '<div class="prad-d-flex prad-item-center prad-gap-12">';

		if ( $item->type != 'no_cost' ) {
			$html .= sprintf(
				'<div class="prad-block-price prad-text-upper ssss">%s</div>',
				wp_kses( $price_info['html'], $allowed_tags )
			);
		}

		if ( $enable_count && product_addons()->is_pro_feature_available() ) {
			$html .= $this->render_quantity_input( $index );
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * Check if quantity input should be rendered
	 *
	 * @param string $layout
	 * @return boolean
	 */
	private function should_render_quantity_input( string $layout ): bool {
		return $this->get_property( 'enableCount', false ) && $layout === '_img';
	}

	/**
	 * Get quantity input attributes
	 *
	 * @param integer $index
	 * @return array
	 */
	private function get_quantity_input_attributes( int $index ): array {
		$blockid = $this->get_block_id();
		$min     = $this->get_property( 'min', 1 );
		$max     = $this->get_property( 'max', 100 );
		$item_id = $blockid . $index;

		return array(
			'id'           => 'prad_quantity_' . $item_id,
			'name'         => 'prad_quantity_' . $item_id,
			'type'         => 'number',
			'placeholder'  => $min,
			'value'        => $min,
			'min'          => $min,
			'max'          => $max,
			'class'        => 'prad-block-input prad-quantity-input switcher-count prad-input prad-w-full prad-mt-6',
			'data-counter' => $item_id . '-switcher-count',
		);
	}

	/**
	 * Render quantity input
	 *
	 * @param integer $index
	 * @return string
	 */
	private function render_quantity_input( int $index ): string {
		return sprintf( '<input %s />', $this->build_attributes( $this->get_quantity_input_attributes( $index ) ) );
	}

	/**
	 * Render block content using parent method
	 *
	 * @param object  $item
	 * @param integer $index
	 * @param array   $price_info
	 * @param string  $variation_html
	 * @return string
	 */
	private function render_block_contents( $item, int $index, array $price_info, string $variation_html = '' ): string {
		return parent::render_block_content( $item, $index, $price_info, $variation_html );
	}

	/**
	 * Render tooltip
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
