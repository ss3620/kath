<?php
/**
 * Products Block Implementation
 *
 * Deprecated, kept only as legacy support: the Products field can no longer be added in
 * the builder. This class stays so Products fields that existing users already saved on
 * their products keep working, and it will be removed in a future release.
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
		// Fields made with WowAddons Pro's "Advanced Products" are Pro fields, rendered by Pro only.
		if ( $this->get_property( 'isAdvanced', false ) ) {
			return '';
		}

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

			if ( $this->is_option_hidden( $item ) ) {
				continue;
			}

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
				$product_data = (int) $product_id === (int) $item['id'] ? '' : $this->get_product_data( $item['id'], false );
				if ( $product_data && $product_data->is_in_stock && $product_data->is_purchasable ) {
					$options[] = $product_data;
				}
			}
		}

		return $options;
	}

	/**
	 * Get product data
	 *
	 * @param integer $product_id Product ID.
	 * @param boolean $with_variations Whether to include variation data.
	 * @return object|null
	 */
	private function get_product_data( int $product_id, bool $with_variations ) {
		return $this->get_product_block_product_attr( $product_id, $with_variations );
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

		$enable_min_max_res = $this->get_property( 'enableMinMaxRes', true );
		if ( 'checkbox' === $input_type && $enable_min_max_res ) {
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
	 * @param array $options Options to render.
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
	 * @param array $options Options to render.
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
	 * @param object  $item Product data.
	 * @param integer $index Product index.
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
	 * @param object  $item Product data.
	 * @param integer $index Product index.
	 * @return string
	 */
	private function get_variation_html( $item, int $index ): string {
		return $this->generate_products_block_variation_section_html(
			array(
				'item'  => $item,
				'index' => $index,
			)
		);
	}

	/**
	 * Render swatch item
	 *
	 * @param object  $item Product data.
	 * @param integer $index Product index.
	 * @param string  $variation_html Pre-rendered variation section HTML.
	 * @return string
	 */
	private function render_swatch_item( $item, int $index, string $variation_html ): string {
		$price_info  = $this->get_price_object( $item->regular, $item->sale );
		$layout      = $this->get_property( 'layout', '_default' );
		$hover_class = $this->get_hover_class();

		$html  = '<div class="prad-products-item-wrapper prad-swatch-item-wrapper prad-relative prad-d-flex prad-flex-column prad-h-full">';
		$html .= $this->render_swatch_container( $item, $index, $price_info, $hover_class, $layout );

		if ( '_default' === $layout ) {
			$html .= $this->render_block_content( $item, $index, $price_info, $variation_html );
		}

		if ( '_img' === $layout && ! empty( $price_info['html'] ) ) {
			$html .= sprintf(
				'<div class="prad-block-price prad-text-upper prad-text-center">%s</div>',
				wp_kses( $price_info['html'], $this->allowed_html_tags )
			);
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
	 * @param object  $item Product data.
	 * @param integer $index Product index.
	 * @param array   $price_info Price data for the product.
	 * @param string  $hover_class Hover effect CSS class.
	 * @param string  $layout Swatch layout key.
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

		if ( '_overlay' === $layout ) {
			$html .= $this->render_block_content( $item, $index, $price_info );
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Render swatch input
	 *
	 * @param object  $item Product data.
	 * @param integer $index Product index.
	 * @param array   $price_info Price data for the product.
	 * @return string
	 */
	private function render_swatch_input( $item, int $index, array $price_info ): string {
		$input_type = $this->get_input_type();
		$blockid    = $this->get_block_id();
		$item_id    = $blockid . $index;

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
		);

		return sprintf( '<input %s />', $this->build_attributes( $attributes ) );
	}

	/**
	 * Render swatch label
	 *
	 * @param object  $item Product data.
	 * @param integer $index Product index.
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
	 * @param object  $item Product data.
	 * @param integer $index Product index.
	 * @param string  $variation_html Pre-rendered variation section HTML.
	 * @return string
	 */
	private function render_input_item( $item, int $index, string $variation_html ): string {
		$price_info   = $this->get_price_object( $item->regular, $item->sale );
		$input_type   = $this->get_input_type();
		$column_class = $this->get_column_class();

		$wrapper_class = 'prad-products-item-wrapper prad-d-flex prad-item-center prad-gap-8 prad-column-' . $column_class;

		$html  = sprintf( '<div class="%s">', esc_attr( $wrapper_class ) );
		$html .= '<div class="prad-d-flex ' . ( $variation_html ? 'prad-item-start' : 'prad-item-center' ) . ' prad-gap-8">';
		$html .= $this->render_input_group( $item, $index, $input_type, $variation_html );

		if ( 'no_cost' !== $item->type ) {
			$html .= $this->render_price( $price_info );
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * Render input group with label
	 *
	 * @param object  $item Product data.
	 * @param integer $index Product index.
	 * @param string  $input_type Input type (radio or checkbox).
	 * @param string  $variation_html Pre-rendered variation section HTML.
	 * @return string
	 */
	private function render_input_group( $item, int $index, string $input_type, string $variation_html ): string {
		$price_info   = $this->get_price_object( $item->regular, $item->sale );
		$blockid      = $this->get_block_id();
		$allowed_tags = $this->allowed_html_tags;

		$html = sprintf( '<div class="prad-%s-item prad-d-flex prad-item-center prad-gap-10">', esc_attr( $input_type ) );

		// Input.
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
		);

		$html .= sprintf( '<input %s />', $this->build_attributes( $input_attributes ) );

		// Label.
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
	 * @param string $input_type Input type (radio or checkbox).
	 * @return string
	 */
	private function render_input_mark( string $input_type ): string {
		if ( 'radio' === $input_type ) {
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
	 * @param object $item Product data.
	 * @param string $variation_html Pre-rendered variation section HTML.
	 * @param array  $allowed_tags Allowed HTML tags for output escaping.
	 * @return string
	 */
	private function render_input_content( $item, string $variation_html, array $allowed_tags ): string {
		$p_url = isset( $item->url ) ? $item->url : '';
		$html  = '<div class="prad-block-content prad-d-flex prad-item-center">';

		$html .= apply_filters( 'prad_option_image_html', '', $item, $this );

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
	 * Render the product's price
	 *
	 * @param array $price_info Price data for the product.
	 * @return string
	 */
	private function render_price( array $price_info ): string {
		return sprintf(
			'<div class="prad-d-flex prad-item-center prad-gap-12"><div class="prad-block-price prad-text-upper">%s</div></div>',
			wp_kses( $price_info['html'], $this->allowed_html_tags )
		);
	}

	/**
	 * Render the hidden image preview tooltip container.
	 *
	 * Deprecated, legacy support: "Enable Image Preview" has always been a free setting of
	 * the Products field, so Products fields existing users already saved keep their image
	 * preview. It goes away with this field type in a future release.
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

	/**
	 * Generates the HTML for the product block variation section.
	 *
	 * This function outputs the variation selection UI for a given product block item.
	 *
	 * @param array $args Arguments containing the product item.
	 * @return string The generated HTML for the variation section.
	 */
	private function generate_products_block_variation_section_html( $args ) {
		$item              = $args['item'];
		$allowed_html_tags = apply_filters( 'prad_allowed_html_tags', array() );
		ob_start();
		if ( isset( $item->variation ) && $item->variation ) {
			$select_options       = '';
			$product              = wc_get_product( $item->id );
			$available_variations = $product->get_available_variations();
			foreach ( $available_variations as $variation_data ) {
				$variation_id = $variation_data['variation_id'];
				$variation    = wc_get_product( $variation_id );

				if ( $variation && $variation->is_purchasable() && $variation->is_in_stock() ) {
					$variation_attributes = $variation->get_attributes();
					$option_label         = '';
					$valid_variation      = true;
					$i                    = 0;
					$regular_price        = $variation->get_regular_price( '' );
					$sale_price           = $variation->get_sale_price( '' );

					$regular_price = apply_filters(
						'prad_raw_tax_compitable_price',
						array(
							'product_id' => $variation_id,
							'price'      => $variation->get_regular_price(),
							'source'     => 'product_page',
						)
					);
					$sale_price    = apply_filters(
						'prad_raw_tax_compitable_price',
						array(
							'product_id' => $variation_id,
							'price'      => $variation->get_sale_price(),
							'source'     => 'product_page',
						)
					);
					$price_obj     = $this->get_price_object( $regular_price, $sale_price );
					foreach ( $variation_attributes as $key => $value ) {
						$label = str_replace( '_', ' ', str_replace( 'pa_', '', $key ) );
						if ( ! empty( $value ) ) {
							$option_label .= ( $i > 0 ? ' , ' : '' ) . ucfirst( $label ) . ' - ' . ucfirst( $value );
							++$i;
						} else {
							$valid_variation = false;
						}
					}
					if ( $valid_variation ) {
						$option_label    = rawurldecode( wp_strip_all_tags( $option_label ) );
						$select_options .= '<div class="prad-select-option" title="' . esc_attr( $option_label ) . '" value="' . esc_attr( $price_obj['price'] ) . '" data-variation-id="' . esc_attr( $variation_id ) . '"  data-pricehtml="' . esc_attr( $price_obj['html'] ) . '">' . esc_html( $option_label ) . '</div>';
					}
				}
			}

			if ( $select_options ) {
				?>
				<div class="prad-product-block-variation-select prad-mt-10">
					<div class="prad-custom-select prad-w-full prad-product-variation-select-comp">
						<div class="prad-select-box prad-block-input prad-block-content" readonly="readonly"><div style="max-width: 120px" class="prad-select-box-item prad-mr-12 prad-ellipsis"><?php esc_html_e( 'Select an option', 'product-addons' ); ?></div> <div class="prad-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="8" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m1 1 6 6 6-6"></path></svg></div></div>
						<div class="prad-select-options">
							<?php echo wp_kses( $select_options, $allowed_html_tags ); ?>
						</div>
					</div>
				</div>

				<?php
			}
		}
		return ob_get_clean();
	}

	/**
	 * Retrieves product block attributes for a given product ID.
	 *
	 * Returns an object containing product details such as ID, type, variation status,
	 * URL, name, image, regular and sale prices, stock status, and purchasable status.
	 *
	 * @param int  $p_id   Product ID.
	 * @param bool $var_p  Whether the product is a variation.
	 * @return object|null Product attributes object or null if not found.
	 */
	private function get_product_block_product_attr( $p_id, $var_p = false ) {
		$product = wc_get_product( $p_id );
		if ( $product ) {
			$formatted_variation = wc_get_formatted_variation( $product, true, false, true );
			$product_value       = $product->get_name() . ( $formatted_variation ? ' - ' . $formatted_variation : '' );
			$data                = array(
				'id'             => $p_id,
				'type'           => 'fixed',
				'variation'      => $var_p,
				'url'            => get_permalink( $p_id ),
				'value'          => rawurldecode( wp_strip_all_tags( $product_value ) ),
				'img'            => wp_get_attachment_url( $product->get_image_id() ),
				'regular'        => apply_filters(
					'prad_raw_tax_compitable_price',
					array(
						'product_id' => $p_id,
						'ppType'     => 'reg',
						'price'      => $product->get_regular_price(),
						'source'     => 'product_page',
					)
				),
				'sale'           => apply_filters(
					'prad_raw_tax_compitable_price',
					array(
						'product_id' => $p_id,
						'ppType'     => 'sale',
						'price'      => $product->get_sale_price(),
						'source'     => 'product_page',
					)
				),
				'is_in_stock'    => $product->is_in_stock(),
				'is_purchasable' => $product->is_purchasable(),
			);
			return (object) $data;
		}

		return null;
	}

	/**
	 * Returns a structured price object with numeric and formatted HTML price.
	 *
	 * If a sale price is provided, the returned price is the sale price, and the
	 * HTML includes both the regular and sale prices. Otherwise, it returns only
	 * the regular price.
	 *
	 * @param float|string $regular The regular price.
	 * @param float|string $sale    The sale price. If empty or false, regular price is used.
	 * @return array {
	 *     @type float  $price The numeric value of the applicable price.
	 *     @type string $html  The formatted HTML price string.
	 * }
	 */
	private function get_price_object( $regular, $sale ) {
		return array(
			'price' => $sale ? floatval( $sale ) : floatval( $regular ),
			'html'  => $sale ? '<span class="pricex"><del>' . wc_price( $regular ) . '</del> <ins>' . wc_price( $sale ) . '</ins></span>' : '<span class="pricex">' . wc_price( $regular ) . '</span>',
		);
	}
}
