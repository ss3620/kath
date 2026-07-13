<?php
/**
 * Volume Discount Template with Attribute Selection
 *
 * @package Revenue
 */

namespace Revenue;

use Revenue\Services\Revenue_Product_Context;

defined( 'ABSPATH' ) || exit;

$product = Revenue_Product_Context::get_product_context();
// important: as we dont want volume discount to popup without a product.
if ( ! $product ) {
	return;
}
$product_id          = $product->get_id();
$product_type        = $product->get_type();
$is_skip_add_to_cart = 'yes' === $campaign['skip_add_to_cart'];


$placement_settings = revenue()->get_placement_settings( $campaign['id'] );
$display_style      = isset( $placement_settings['display_style'] ) ? $placement_settings['display_style'] : 'inpage';

$device_manager       = $template_data['campaign_visibility_enabled'] ?? array();
$device_manager_class = '';

$is_multiple_variation_enabled = revenue()->get_campaign_meta( $campaign['id'], 'multiple_variation_selection_enabled', true );

if ( is_array( $device_manager ) && ! empty( $device_manager ) ) {
	if ( isset( $device_manager['desktop'] ) && 'no' === $device_manager['desktop'] ) {
		$device_manager_class .= ' revx-hide-desktop';
	}
	if ( isset( $device_manager['tablet'] ) && 'no' === $device_manager['tablet'] ) {
		$device_manager_class .= ' revx-hide-tablet';
	}
	if ( isset( $device_manager['mobile'] ) && 'no' === $device_manager['mobile'] ) {
		$device_manager_class .= ' revx-hide-mobile';
	}
}

$extra_class = '';
if ( 'popup' === $display_style ) {
	$extra_class = 'revx-popup-init-size';
}
if ( 'floating' === $display_style ) {
	$extra_class = 'revx-floating-init-size';
}

$campaign_id       = $campaign['id'];
$campaign_type     = $campaign['campaign_type'];
$template_data     = revenue()->get_campaign_meta( $campaign_id, 'builder', true );
$offers            = revenue()->get_campaign_meta( $campaign_id, 'offers', true ) ?? array();
$product_attr_data = array(
	array(
		'label'  => 'Color',
		'option' => array(
			array(
				'label' => 'Black',
				'value' => 'black',
			),
			array(
				'label' => 'White',
				'value' => 'white',
			),
		),
	),
	array(
		'label'  => 'Storage',
		'option' => array(
			array(
				'label' => '256 GB Storage',
				'value' => '256gb',
			),
			array(
				'label' => '512 GB Storage',
				'value' => '512gb',
			),
		),
	),
	array(
		'label'  => 'RAM',
		'option' => array(
			array(
				'label' => '16GB Ram',
				'value' => '16gb',
			),
			array(
				'label' => '32GB Ram',
				'value' => '32gb',
			),
		),
	),
);

if ( ! function_exists( 'Revenue\get_product_attribute_values' ) ) {
	/**
	 * Get formatted product attribute values for variable products.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @return array Formatted array of product attributes with their values.
	 */
	function get_product_attribute_values( $product ) {
		$formatted_attributes = array();

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return $formatted_attributes;
		}

		$attributes = $product->get_attributes();

		foreach ( $attributes as $attr_slug => $attribute_obj ) {
			// Skip if not for variation.
			if ( ! $attribute_obj->get_variation() ) {
				continue;
			}

			$taxonomy      = $attribute_obj->get_name(); // e.g. 'pa_color'.
			$taxonomy_name = wc_attribute_label( $taxonomy ); // e.g. 'Color'.

			$terms = array();

			// Get term names (for global attributes).
			if ( taxonomy_exists( $taxonomy ) ) {
				$term_ids = $attribute_obj->get_options(); // IDs of terms.
				foreach ( $term_ids as $term_id ) {
					$term = get_term( $term_id );
					if ( ! is_wp_error( $term ) && $term ) {
						$terms[] = $term->name; // e.g. "Blue".
					}
				}
			} else {
				// For custom attributes (not taxonomy-based).
				$terms = $attribute_obj->get_options();
			}

			// Strip "pa_" from slug for output (optional).
			$clean_slug                          = str_replace( 'pa_', '', $attr_slug );
			$formatted_attributes[ $clean_slug ] = $terms;
		}

		return $formatted_attributes;
	}
}

$radio_class = Revenue_Template_Utils::get_element_class( $template_data, 'volumeDiscountRadio' );
ob_start();
?>
<div class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'wrapper' ) ); ?> <?php echo esc_attr( $display_style ); ?> <?php echo esc_attr( $device_manager_class ); ?> <?php echo esc_attr( $extra_class ); ?>">
	<?php Revenue_Template_Utils::render_wrapper_header( $campaign, $template_data ); ?>
	<div
		class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'productsWrapper' ) ); ?> <?php echo 'revx-' . esc_attr( $campaign_type ) . '-add-to-cart'; ?> revx-items-wrapper revx-scrollbar-common revx-flex-column"
		data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>"
		data-product-id="<?php echo esc_attr( $product_id ); ?>"
		data-product-type="<?php echo esc_attr( $product_type ); ?>"
		
	>
		<?php
		if ( is_array( $offers ) && ! empty( $offers ) ) {
			$is_variable = $product->get_type() === 'variable';
			if ( $is_variable ) {
				$variations_main = $product->get_available_variations();
				$variations      = array();
				$attributes      = get_product_attribute_values( $product );
				$parent_id       = $product->get_id();
				$product_name    = $product->get_name();

				if ( $variations_main ) {
					foreach ( $variations_main as $index => $variation ) {
						$variations[ $index ]['item_id']       = $variation['variation_id'];
						$variations[ $index ]['item_name']     = $product_name;
						$variations[ $index ]['thumbnail']     = wp_get_attachment_url( isset( $variation['image_id'] ) ? $variation['image_id'] : 0 );
						$variations[ $index ]['regular_price'] = isset( $variation['display_regular_price'] ) ? $variation['display_regular_price'] : 0;
						$variations[ $index ]['sale_price']    = isset( $variation['display_price'] ) ? $variation['display_price'] : 0;
						$variations[ $index ]['is_in_stock']   = ! empty( $variation['is_in_stock'] ) ? true : false;
						$variations[ $index ]['parent_id']     = $parent_id;

						$combination_key = array();

						if ( ! empty( $variation['attributes'] ) ) {
							foreach ( $variation['attributes'] as $key => $value ) {
								// $clean_key                     = str_replace( 'attribute_pa_', '', strtolower( $key ) );
								// $clean_value                   = strtolower( $value );
								$combination_key[ $key ] = $value;
							}
						}

						$combination_str                          = json_encode( $combination_key );
						$variations[ $index ]['combination_keys'] = $combination_str;
					}
				}
				$variations['attributes'] = $attributes;
			}
			// take poduct regular price or first variation product price as default value.
			$regular_price = $is_variable && $variations
								? $variations[0]['regular_price']
								: $product->get_regular_price();

			$sale_price = $is_variable && $variations
								? $variations[0]['sale_price']
								: $product->get_sale_price();

			// Extension Filter: Sale Price Addon.
			$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );

			foreach ( $offers as $idx => $offer ) {
				$is_enable_tag = isset( $offer['isEnableTag'] ) && $offer['isEnableTag'] === 'yes';
				$is_selected   = ( count( $offers ) - 1 ) == $idx;
				$offer_type    = $offer['type'];
				$offer_value   = $offer['value'];
				$offer_qty     = $offer['quantity'];
				$save_data     = $template_data['saveBadgeWrapper']['text'] ?? '';
				$quantities    = $template_data['quantityMessage']['text'] ?? '';
				$quantities    = str_replace( '{qty}', intval( $offer_qty ), $quantities );

				// single item offered price after calculation.
				// based on extension filter use sale price or regular price for calculation.
				$offered_price = revenue()->calculate_campaign_offered_price(
					$offer_type,
					$offer_value,
					$filtered_price,
					false,
					$offer_qty,
					$campaign_type
				);

				// TAX SUPPORTED PRICE CALCULATION.
				$regular_price_taxed = wc_get_price_to_display(
					$product,
					array(
						'price' => floatval( $regular_price ),
						'qty'   => 1,
					)
				);
				$offered_price_taxed = wc_get_price_to_display(
					$product,
					array(
						'price' => floatval( $offered_price ),
						'qty'   => 1, // Per-unit price.
					)
				);

				// below prices are to show on frontend.
				// requires multiplication with real offer quantity.
				$offer['regular_price'] = $regular_price_taxed * $offer_qty;
				$offer['offered_price'] = $offered_price_taxed * $offer_qty;

				/**
				 * CAUTION: DO NOT MODIFY BELOW VARIABLE.
				 * Used for radio button data attribute.
				 * Change or modify saved_amount variable with caution.
				 */
				$saved_amount     = floatval( $offer['regular_price'] ) - floatval( $offer['offered_price'] );
				$percentage_value = false;
				if ( 'percentage' === $offer_type ) {
					if ( floatval( $filtered_price ) !== floatval( $regular_price ) ) {
						$perc = ( $saved_amount / $offer['regular_price'] ) * 100;
						// use calculated percentage value for save badge when filter chnaged discount base price.
						$percentage_value = wc_format_decimal( $perc, 2 ) . '%';
					} else {
						$percentage_value = wc_format_decimal( $offer_value, 2 ) . '%';
					}
				}
				// replace smart tag if available with appropriate value.
				// NOTE: saved_amount is used for data attribute, so it should be in number format.
				// use wc_price() only here to keep the actual amount format correct for data attribute.
				$save_data = str_replace(
					'{save_amount}',
					$percentage_value ? $percentage_value : wc_price( $saved_amount ),
					$save_data,
				);

				$inline_style = $is_selected
				? 'border-width: var(--revx-active-border-width); border-color: var(--revx-active-border-color); border-style: var(--revx-active-border-style);'
				: '';
				/**
				 * Determine the price factor for variations:
				 * - If multiple variation selection is enabled and the product is variable, set factor to 1
				 *   because each variation has its own base price and the frontend JS (variation-product-selection.js)
				 *   will sum them.
				 * - If it's a single variation, use the offer quantity as the factor to calculate the total price.
				 */
				$price_factor = ( $is_multiple_variation_enabled === 'yes' && $is_variable ) ? 1 : floatval( $offer_qty );
				// if variations of the product are avaialable , assign them.
				$offer['variations'] = $variations ?? array();
				if ( $offer['variations'] ) {
					// using referencing to directly modify values in each obbject, handle with care.
					foreach ( $offer['variations'] as $key => &$variation ) {
						if ( is_string( $key ) ) {
							continue;
						}

						// Extension Filter: Sale Price Addon.
						$variation_filtered_price = apply_filters(
							'revenue_base_price_for_discount_filter',
							$variation['regular_price'],
							$variation['sale_price']
						);
						// based on extension filter use sale price or regular price for calculation.
						$variation['offered_price'] = revenue()->calculate_campaign_offered_price(
							$offer_type,
							$offer_value,
							$variation_filtered_price,
							false,
							$offer_qty,
							$campaign_type
						) * $price_factor;
						$variation['regular_price'] = $variation['regular_price'] * $price_factor;
						$variation['sale_price']    = $variation['sale_price'] * $price_factor;
						$variation['saved_amount']  = $variation['regular_price'] - $variation['offered_price'];
					}
				}
				?>
					<div 
						data-campaign-type="volume_discount"
						data-offer-item="item"
						class="revx-volume-discount-item revx-relative revx-d-flex <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'product' ) ); ?> <?php echo $is_enable_tag ? esc_attr( 'revx-tag-bg revx-tag-border' ) : ''; ?>"
					>
						<?php
						if ( $is_enable_tag ) {
							echo wp_kses_post( Revenue_Template_Utils::render_tag( $template_data ) );
						}
						?>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_radio( $template_data, $saved_amount, $offer_type, $offer_value, $is_selected, 'volumeDiscountRadio', $is_enable_tag ? 'revx-tag-radio' : '', 'default', $offer_qty, $idx ) ); ?>
						<div class="revx-w-full">
							<div class="revx-d-flex revx-item-center revx-w-full">
								<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'quantityMessage', $quantities, $is_enable_tag ? 'revx-tag-text-color' : '' ) ); ?>
								<?php Revenue_Template_Utils::render_save_badge( $template_data, $save_data, $is_enable_tag ? 'revx-tag-bg revx-tag-border revx-tag-text-color' : '', 'display: block; flex-shrink: 0', 'saveBadgeWrapper' ); ?>
								<?php Revenue_Template_Utils::revenue_render_product_price( $offer, 'list', $template_data, false, true, true, 'revx-vqd-price' ); ?>
							</div>
							
							<div class="revx-volume-attributes revx-<?php echo esc_attr( $is_selected ? 'active' : 'inactive' ); ?>">
								<?php
								$limits = $is_multiple_variation_enabled === 'yes' ? $offer_qty : 1;
								for ( $i = 0; $i < $limits; $i++ ) {
									Revenue_Template_Utils::revenue_render_product_variation( $offer, $template_data, $campaign_type );
								}
								?>
							</div>
						</div>
					</div>
				<?php
			}
		}
		?>
	</div>
	<!-- Do not delete this div neither add another div parent is used in jquery -->
	<div class="revx-d-flex revx-item-center revx-justify-center revx-w-full" data-atc-button-text="<?php echo esc_attr( $template_data['addToCartWrapper']['text'] ?? '' ); ?>">
		<?php
		echo wp_kses_post(
			Revenue_Template_Utils::render_volume_discount_add_to_cart_button(
				$template_data,
				false,
				'addToCartWrapper',
				$campaign_id,
				$campaign_type,
				$offer_qty,
				$saved_amount,
				$is_skip_add_to_cart
			)
		);
		?>
	</div>
</div>

<?php
$output = ob_get_clean();
switch ( $display_style ) {
	case 'inpage':
		Revenue_Template_Utils::inpage_container( $campaign, $output );
		break;
	case 'popup':
		Revenue_Template_Utils::popup_container( $campaign, $output );
		break;
	case 'floating':
		Revenue_Template_Utils::floating_container( $campaign, $output );
		break;
}
