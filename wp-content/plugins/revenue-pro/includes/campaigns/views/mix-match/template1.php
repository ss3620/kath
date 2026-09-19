<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Normal Discount inpage Template
 *
 * This file handles the display of normal discount offers in a inpage container.
 *
 * @package    Revenue
 * @subpackage Templates
 * @version    1.0.0
 */

namespace Revenue;

use Revenue;
use Revenue\Services\Revenue_Product_Context;

/**
 * The Template for displaying revenue view
 *
 * @package Revenue
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$product = Revenue_Product_Context::get_product_context();

if ( ! $product ) {
	return;
}

/**
 * Special Note for custom work:
 * using filter for regular price modification, handle with care in future.
 */

// Fetch required data.
$display_type           = revenue()->get_placement_settings( $campaign['id'], $placement, 'display_style' ) ?? 'inpage';
$view_mode              = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
$template_data          = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
$offers                 = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
$placement_settings     = revenue()->get_placement_settings( $campaign['id'] );
$display_style          = isset( $placement_settings['display_style'] ) ? $placement_settings['display_style'] : 'inpage';
$slider_columns         = json_encode( Revenue_Template_Utils::get_slider_data( $template_data ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
$products_wrapper_class = 'grid' == $view_mode ? 'revx-slider-wrapper' : '';
$is_grid_view           = 'grid' === $view_mode;
$tier_items             = $campaign['offers'];
$is_selected            = false;
$extra_class            = '';
if ( 'popup' === $display_style ) {
	$extra_class = 'revx-popup-init-size';
}
if ( 'floating' === $display_style ) {
	$extra_class = 'revx-floating-init-size';
}

$device_manager       = $template_data['campaign_visibility_enabled'] ?? array();
$device_manager_class = '';

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

$has_required_products     = isset( $campaign['mix_match_is_required_products'] ) && 'yes' == $campaign['mix_match_is_required_products'];
$required_products         = isset( $campaign['mix_match_required_products'] ) && $has_required_products ? $campaign['mix_match_required_products'] : array();
$initial_product_selection = isset( $campaign['mix_match_initial_product_selection'] ) ? $campaign['mix_match_initial_product_selection'] : '';
$trigger_product_relation  = 'and';

$trigger_items = revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $product->get_id() );

$cookie_name            = sprintf( 'revx_mix_match_%s', $campaign['id'] );
$selected_items         = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( urldecode( $_COOKIE[ $cookie_name ] ) ) : '';
$selected_items         = json_decode( wp_unslash( $selected_items ), true ) ?? array();
$selected_product       = array();
$offer                  = array();
$selected_regular_price = 0;

$filtered_items = array();

foreach ( $selected_items as $item ) {
	$product_selected_items = wc_get_product( $item['id'] );
	if ( $product_selected_items && $product_selected_items->is_type( 'variable' ) ) {
		continue;
	}
	$filtered_items[ $item['id'] ] = $item;
}

$selected_items = $filtered_items;
$selected_item_ids      = array_keys( $selected_items );

$required_product_arr = array();
$mix_match_quantity   = revenue()->getMixMatchQuantities( $campaign );

$is_all_product_selection = 'all_products' === $initial_product_selection;

$pre_selected_ids = array(); 

$offer_data = array();
foreach ( $trigger_items as $offer ) {
	$offered_product  = wc_get_product( $offer['item_id'] );
	$offer_product_id = $offer['item_id'];

	if ( ! $offered_product || ! $offered_product->is_in_stock() ) {
		continue;
	}

	// If grouped product → merge all its children.
	if ( $offered_product->is_type( 'grouped' ) ) {
		$child_ids = $offered_product->get_children();

		foreach ( $child_ids as $child_id ) {
			$child_product = wc_get_product( $child_id );

			if ( ! $child_product || ! $child_product->is_in_stock() || $child_product->is_type( 'variable' ) ) {
				continue;
			}

			$tax_display = get_option( 'woocommerce_tax_display_shop', 'incl' );

			$regular_price = $child_product->get_regular_price();
			$sale_price    = $child_product->get_sale_price();
			// Extension Filter: Sale Price Addon.
			$filtered_price = apply_filters( 'revenue_base_price_for_mix_match', $regular_price, $sale_price );

			if ( 'incl' === $tax_display ) {
				$regular_price = wc_get_price_including_tax( $child_product, array( 'price' => $filtered_price ) );
			} else {
				$regular_price = wc_get_price_excluding_tax( $child_product, array( 'price' => $filtered_price ) );
			}

			$offer_data[ $child_id ]                  = $offer;
			$offer_data[ $child_id ]['item_id']       = $child_id;
			$offer_data[ $child_id ]['item_name']     = $child_product->get_name();
			$offer_data[ $child_id ]['thumbnail']     = wp_get_attachment_url( $child_product->get_image_id() );
			$offer_data[ $child_id ]['regular_price'] = $regular_price;
			$offer_data[ $child_id ]['parent_id']     = $offer_product_id;
		}

		continue; // skip main grouped parent product.
	}

	// For normal (non-grouped) products.
	if ( $offered_product->is_type( 'variable' ) ) {
		continue;
	}

	$tax_display = get_option( 'woocommerce_tax_display_shop', 'incl' );

	$regular_price = $offered_product->get_regular_price();
	$sale_price    = $offered_product->get_sale_price();
	// Extension Filter: Sale Price Addon.
	$filtered_price = apply_filters( 'revenue_base_price_for_mix_match', $regular_price, $sale_price );

	if ( 'incl' === $tax_display ) {
		$regular_price = wc_get_price_including_tax( $offered_product, array( 'price' => $filtered_price ) );
	} else {
		$regular_price = wc_get_price_excluding_tax( $offered_product, array( 'price' => $filtered_price ) );
	}

	if ( ! isset( $offer_data[ $offer_product_id ] ) ) {
		$offer_data[ $offer_product_id ] = $offer;
	}
	$offer_data[ $offer_product_id ]['regular_price'] = $regular_price;
}
$offer_data_ids = array_keys( $offer_data );

if ( $is_all_product_selection ) {
	$pre_selected_ids = $offer_data_ids;
} elseif ( $has_required_products ) {
	$pre_selected_ids = $required_products;
}

foreach ( $pre_selected_ids as $id ) {
	if ( ! in_array( $id, $selected_item_ids ) ) {
		$pre_product = wc_get_product( $id );

		if ( $pre_product && $pre_product->is_type( 'variable' ) ) {
			continue;
		}
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $pre_product->get_id() ), 'single-post-thumbnail' );
		$thumbnail = isset( $thumbnail[0] ) ? $thumbnail[0] : wc_placeholder_img_src();

		if ( $pre_product->is_in_stock() ) {
			$regular_price = $pre_product->get_regular_price();
			$sale_price    = $pre_product->get_sale_price();
			// Extension Filter: Sale Price Addon.
			$filtered_price = apply_filters( 'revenue_base_price_for_mix_match', $regular_price, $sale_price );

			$selected_items[ $id ] = array(
				'id'           => $id,
				'productName'  => $pre_product->get_name(),
				'regularPrice' => $filtered_price,
				'thumbnail'    => $thumbnail,
				'quantity'     => 1,
				'is_required'  => in_array( $id, $required_products ) ? 'yes' : 'no', // check if its one of the required product.
			);
		} else {
			unset( $selected_items[ $id ] );
		}
	} else {
		$pre_product = wc_get_product( $id );

		// Handle grouped products present in cookie/selection by normalizing to children.
		if ( $pre_product && $pre_product->is_type( 'grouped' ) ) {
			$child_ids = $pre_product->get_children();
			if ( is_array( $child_ids ) ) {
				foreach ( $child_ids as $child_id ) {
					$child_product = wc_get_product( $child_id );
					if ( ! $child_product || $child_product->is_type( 'variable' ) ) {
						continue;
					}
					if ( $child_product->is_in_stock() ) {
						if ( ! isset( $selected_items[ $child_id ] ) ) {
							$child_thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $child_product->get_id() ), 'single-post-thumbnail' );
							$child_thumbnail = isset( $child_thumbnail[0] ) ? $child_thumbnail[0] : wc_placeholder_img_src();

							$regular_price = $child_product->get_regular_price();
							$sale_price    = $child_product->get_sale_price();
							// Extension Filter: Sale Price Addon.
							$filtered_price = apply_filters( 'revenue_base_price_for_mix_match', $regular_price, $sale_price );

							$selected_items[ $child_id ] = array(
								'id'           => $child_id,
								'productName'  => $child_product->get_name(),
								'regularPrice' => $filtered_price,
								'thumbnail'    => $child_thumbnail,
								'quantity'     => 1,
								'is_required'  => ( in_array( $child_id, $required_products ) || in_array( $id, $required_products ) ) ? 'yes' : 'no',
							);
						} else {
							$selected_items[ $child_id ]['is_required'] = ( in_array( $child_id, $required_products ) || in_array( $id, $required_products ) ) ? 'yes' : 'no';
						}
					} else {
						unset( $selected_items[ $child_id ] );
					}
				}
			}
			// Remove grouped parent if it exists in the selection.
			unset( $selected_items[ $id ] );
			continue;
		}

		if ( $pre_product->is_type( 'variable' ) ) {
			continue;
		}
		// stock and requirement might be change before cookie is expierd..
		if ( $pre_product->is_in_stock() ) {
			$selected_items[ $id ]['is_required'] = in_array(
				$id,
				$required_products
			) ? 'yes' : 'no'; // check if its one of the required product.
		} else {
			unset( $selected_items[ $id ] );
		}
	}
}
// Eligibility basis for the first server-rendered tier highlight. Mirrors the frontend JS:
// 'total' sums the selected offered-product quantities, otherwise counts distinct products.
$revx_mix_match_count_mode = apply_filters( 'revenue_mix_match_count_mode', 'unique' );
if ( 'total' === $revx_mix_match_count_mode ) {
	$selected_product_count = array_sum( wp_list_pluck( $selected_items, 'quantity' ) );
} else {
	$selected_product_count = count( $selected_items );
}

ob_start();

?>

<div 
	class="
		<?php echo esc_attr( Revenue_Template_Utils::get_element_class(
			$template_data,
			'productBodyWrapper'
		) ); ?> 
		<?php echo esc_attr( $display_style ); ?> 
		<?php echo esc_attr( $device_manager_class ); ?> 
		<?php echo esc_attr( $extra_class ); ?>
	"
	data-container-level="mix_match_file"
>
	<div class="revx-product-body-wrapper">
		
		<?php Revenue_Template_Utils::render_wrapper_header( $campaign, $template_data ); ?>

		<div class="revx-tiers-container revx-gap-8 revx-mixmatch-quantity">
			<?php
			foreach ( $tier_items as $tier_item_idx => $tier_item ) {
				$is_enable_tag  = (
					isset( $tier_item['isEnableTag'] ) &&
					'yes' === $tier_item['isEnableTag']
				);
				$quantity       = $tier_item['quantity'];
				$discount_value = $tier_item['value'] ?? '';
				$discount_type  = $tier_item[ 'type' ];
				$next_quantity  = $tier_items[ $tier_item_idx + 1 ]['quantity'] ?? null;
				$is_selected    = $next_quantity
					? ( $selected_product_count >= $quantity && $selected_product_count < $next_quantity )
					: ( $selected_product_count >= $quantity );

				$tier_class     = 'revx-tier-regular';
				if ( $is_selected && $is_enable_tag ) {
					$tier_class = 'revx-tier-selected revx-tier-enable';
				} elseif ( $is_selected ) {
					$tier_class = 'revx-tier-selected';
				} elseif ( $is_enable_tag ) {
					$tier_class = 'revx-tier-enable';
				}
				$save_data = '';
				if ( 'percentage' === $discount_type ) {
					// translators: %s is discount value.
					$save_data = sprintf( __( '%s%%', 'revenue-pro' ), $discount_value );
				} elseif ( 'fixed_discount' === $discount_type ) {
					$save_data = wc_price( floatval( $discount_value ) );
				}

				$title_to_show = str_replace( '{qty}', $quantity, $template_data['mixMatchTitle']['text'] );
				$badge_text    = str_replace( '{discount_value}', $save_data, $template_data['mixMatchBadge']['text'] );
				$badge_to_show = in_array(
					$discount_type,
					array( 'no_discount', 'fixed_price' ),
					true
				) ? '' : $badge_text;
				?>
				<div
				data-index="<?php echo esc_attr( $tier_item_idx ); ?>"
					class="<?php echo esc_attr( $tier_class ); ?> <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'tierButton' ) ); ?> revx-tier-button revx-mixmatch-regular-quantity"
				>
					<?php
					if ( $is_enable_tag ) {
							echo Revenue_Template_Utils::render_tag( $template_data );
					}
					?>
					<div
						class="revx-d-flex revx-flex-wrap revx-item-center revx-justify-between revx-relative revx-w-full"
					>
						<div class="revx-mix-title-badge">
							<?php echo Revenue_Template_Utils::render_rich_text( $template_data, 'mixMatchTitle', $title_to_show, 'revx-mix-match-title' ); ?>
							<?php Revenue_Template_Utils::render_save_badge( $template_data, $badge_to_show ?? '', 'revx-mix-match-badge', 'mixMatchBadge' ); ?>
						</div>
						<div class="<?php echo $is_enable_tag ? 'revx-tag-text-color' : ''; ?> revx-checkbox-wrapper">
							<div 
								class="revx-checkbox-container <?php echo $is_selected ? 'revx-active' : 'revx-inactive revx-d-none' ?>" 
								style="font-size: 24px; pointer-events: none;" 
								data-is-checked="<?php echo esc_attr( $is_selected ? 'yes' : 'no' ); ?>"
							>
								<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 16 16">
									<rect width="11.8" height="11.8" x="2.102" y="2.1"
										<?php #old code: echo $is_enable_tag ? '-tag' : ''; ?>
										stroke="var(--revx-checkbox-bg-color,#000000)"
										rx="2.7"></rect>
									<rect width="11.4" height="11.4" x="2.502" y="2.5"
										<?php #old code - color was unxpected: echo $is_enable_tag ? '-tag' : ''; ?>										
										fill="var(--revx-checkbox-bg-color,#000000)"
										class="revx-checkbox-inactive"
										rx="2"></rect>
									<path 
										<?php #old code: echo $is_enable_tag ? '-tag' : ''; ?>
										stroke="var(--revx-checkbox-icon-color,#fff)"
										stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"
										d="m11.4 5.9-4.2 4.2-2-2"
										class="revx-checkbox-inactive"></path>
								</svg>
							</div>
						</div>
					</div>
				</div>
			<?php } ?>
		</div>
		<?php echo esc_attr( Revenue_Template_Utils::render_mix_match_products_container( $campaign, $template_data, $placement, true ) ); ?>
	</div>
	<?php Revenue_Template_Utils::render_mix_match_products_footer( $campaign, $template_data, 'mixMatchFooter', $selected_items, $campaign['id'], $campaign['campaign_type'] ); ?>
</div>

<input type="hidden" name="<?php echo 'revx-offer-data-' . esc_attr( $campaign['id'] ); ?>" value="<?php echo htmlspecialchars( wp_json_encode( $offer_data ) ); ?>" />
<input type="hidden" name="<?php echo 'revx-selected-items-' . esc_attr( $campaign['id'] ); ?>" value="<?php echo htmlspecialchars( wp_json_encode( $selected_items ) ); ?>" />
<input type="hidden" name="<?php echo 'revx-qty-data-' . esc_attr( $campaign['id'] ); ?>" value="<?php echo htmlspecialchars( wp_json_encode( $mix_match_quantity ) ); ?>" />
<input type="hidden" name="<?php echo 'revx-required-products-' . esc_attr( $campaign['id'] ); ?>" value="<?php echo htmlspecialchars( wp_json_encode( $required_products ) ); ?>" />


<?php


$output = ob_get_clean();

// Output content based on display style.
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
