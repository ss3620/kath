<?php
/**
 * Normal Discount inpage Template
 *
 * This file handles the display of normal discount offers in a inpage container.
 *
 * @package    Revenue
 * @subpackage Templates
 * @version    1.0.0
 */

//phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar

namespace Revenue;

use Revenue;
use Revenue\Services\Revenue_Product_Context;

defined( 'ABSPATH' ) || exit;
$product = Revenue_Product_Context::get_product_context();
if ( ! $product ) {
	return;
}

// Fetch required data.
$view_mode            = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
$template_data        = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
$offers               = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
$placement_settings   = revenue()->get_placement_settings( $campaign['id'] );
$display_style        = isset( $placement_settings['display_style'] ) ? $placement_settings['display_style'] : 'inpage';
$is_grid_view         = 'grid' === $view_mode;
$device_manager       = $template_data['campaign_visibility_enabled'] ?? array();
$device_manager_class = '';
$extra_class          = '';
$is_skip_add_to_cart  = 'yes' === $campaign['skip_add_to_cart'];

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

if ( 'popup' === $display_style ) {
	$extra_class = 'revx-popup-init-size';
}
if ( 'floating' === $display_style ) {
	$extra_class = 'revx-floating-init-size';
}

$campaign_id   = $campaign['id'];
$campaign_type = $campaign['campaign_type'];

$is_category              = ( 'category' === $campaign['campaign_trigger_type'] ) || ( 'all_products' === $campaign['campaign_trigger_type'] );
$trigger_product_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';
$trigger_products         = revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $product->get_id(), $is_category );
$total_regular_price      = 0;
$total_sale_price         = 0;

$is_set_individual_product_quantity = revenue()->get_campaign_meta( $campaign['id'], 'buy_x_get_y_trigger_qty_status', true ) === 'yes';
$offer_data                         = array();
$trigger_data                       = array();

$trigger_product_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';

if ( empty( $trigger_product_relation ) ) {
	$trigger_product_relation = 'or';
}

$offer_products = revenue()->getOfferProductsData( $offers );
$is_category    = ( 'category' === $campaign['campaign_trigger_type'] ) || ( 'all_products' === $campaign['campaign_trigger_type'] );
$trigger_items  = revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $product->get_id(), $is_category );

if ( is_array( $trigger_items ) ) {
	foreach ( $trigger_items as $idx => $trigger ) {
		$trigger_product_id            = $trigger['item_id'];
		$trigger_product_name          = $trigger['item_name'];
		$trigger_product_thumbnail     = $trigger['thumbnail'];
		$trigger_product_regular_price = $trigger['regular_price'];
		$trigger_qty                   = isset( $trigger['quantity'] ) ? $trigger['quantity'] : 1;


		$offered_product = wc_get_product( $trigger_product_id );
		if ( ! $offered_product ) {
			continue;
		}

		$product_title                 = $offered_product->get_title();
		$regular_price                 = $offered_product->get_price();
		$trigger_product_regular_price = $regular_price;

		$offered_price = $regular_price;


		$total_regular_price += floatval( $trigger_product_regular_price ) * $trigger_qty;
		$total_sale_price    += floatval( $trigger_product_regular_price ) * $trigger_qty;

		if ( ! isset( $offer_data[ $trigger_product_id ]['regular_price'] ) ) {
			$offer_data[ $trigger_product_id ]['regular_price'] = $regular_price;
		}
		if ( ! isset( $offer_data[ $trigger_product_id ]['offer'] ) ) {
			$offer_data[ $trigger_product_id ]['offer'] = array();
		}
		$offer_data[ $trigger_product_id ]['offer'][] = array(
			'qty'   => $trigger_qty,
			'type'  => '',
			'value' => '',
		);
	}
}

$trigger_data = $offer_data;

$offer_data = array();

if ( is_array( $offer_products ) ) {
	$offer_length = count( $offer_products );
	foreach ( $offer_products as $offer_index => $offer ) {
		$offer_qty      = $offer['quantity'];
		$offer_value    = $offer['value'];
		$offer_type     = $offer['type'];
		$items_content  = '';
		$is_tag_enabled = isset( $offer['isEnableTag'] ) ? 'yes' == $offer['isEnableTag'] : false;

		$save_data = '';

		$offer_product_id = $offer['item_id'];

		$product_count = count( $offer_products );

		$is_last_product = ( $offer_length - 1 ) == $offer_index;

		$offered_product = wc_get_product( $offer_product_id );

		if ( ! $offered_product ) {
			continue;
		}

		$_data = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price, true, $offer_qty );

		$regular_price = $offered_product->get_regular_price();
		$sale_price    = $offered_product->get_sale_price();

		// Extension Filter: Sale Price Addon.
		$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );
		// based on extension filter use sale price or regular price for calculation.
		$offered_price = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $filtered_price );

		$total_regular_price += floatval( $regular_price ) * intval( $offer_qty );
		$total_sale_price    += floatval( $offered_price ) * intval( $offer_qty );


		if ( ! isset( $offer_data[ $offer_product_id ]['regular_price'] ) ) {
			$offer_data[ $offer_product_id ]['regular_price'] = $regular_price;
		}
		if ( ! isset( $offer_data[ $offer_product_id ]['offer'] ) ) {
			$offer_data[ $offer_product_id ]['offer'] = array();
		}
		$offer_data[ $offer_product_id ]['offer'][] = array(
			'qty'   => $offer_qty,
			'type'  => $offer_type,
			'value' => $offer_value,
		);

	}
}


// calculate trigger product total.
$total_trigger_regular_price = 0;
$total_trigger_sale_price    = 0;

foreach ( $trigger_products as $t_products ) {
	$product_id   = $t_products['item_id'];
	$tax_display  = get_option( 'woocommerce_tax_display_shop', 'incl' );
	$_product     = wc_get_product( $product_id );
	$product_type = $_product->get_type();
	if ( ! $_product ) {
		continue;
	}
	if ( 'variable' === $product_type ) {
		$children                = $_product->get_children();
		$variation_first_regular = null;
		$variation_first_offer   = null;
		foreach ( $children as $child_id ) {
			$variation = wc_get_product( $child_id );
			if ( $variation ) {
				$variation_first_regular = 'incl' === $tax_display ? wc_get_price_including_tax( $_product, array( 'price' => $variation->get_regular_price() ) ) : $variation->get_regular_price();
				$variation_first_offer   = 'incl' === $tax_display ? wc_get_price_including_tax( $_product, array( 'price' => $variation->get_sale_price() ) ) : $variation->get_sale_price();
				$variation_first_regular = floatval( $variation_first_regular );
				$variation_first_offer   = floatval( $variation_first_offer );
				break;
			}
		}
	}

	$regular_price = 'incl' === $tax_display ? wc_get_price_including_tax( $_product, array( 'price' => $_product->get_regular_price() ) ) : floatval( $_product->get_regular_price() );
	$sale_price    = 'incl' === $tax_display ? wc_get_price_including_tax( $_product, array( 'price' => $_product->get_sale_price() ) ) : floatval( $_product->get_sale_price() );
	$regular_price = floatval( $regular_price );
	$sale_price    = floatval( $sale_price );
	if ( $is_set_individual_product_quantity || 'or' === $trigger_product_relation ) {
		$total_trigger_regular_price += 'variable' === $product_type ? $variation_first_regular * $t_products['quantity'] : $regular_price * $t_products['quantity'];
		$total_trigger_sale_price    += 'variable' === $product_type ? $variation_first_offer * $t_products['quantity'] : $sale_price * $t_products['quantity'];
	} else {
		$total_trigger_regular_price += 'variable' === $product_type ? $variation_first_regular * $t_products['quantity'] : $regular_price * $t_products['quantity'];
		$total_trigger_sale_price    += 'variable' === $product_type ? $variation_first_offer * $t_products['quantity'] : $sale_price * $t_products['quantity'];
	}
}

$discount_value            = 0;
$total_offer_regular_price = 0;
$total_offer_sale_price    = 0;

$processed_parents = array();
foreach ( $offers as $offer ) {
	if ( is_array( $offer ) ) {
		$quantity           = $offer['quantity'];
		$all_offer_products = $offer['products'];
		$discount_type      = $offer['type'];
		$discount_value     = $offer['value'];

		foreach ( $all_offer_products as $product_id ) {
			$_product = wc_get_product( $product_id );

			if ( ! $_product ) {
				continue;
			}

			$product_type      = $_product->get_type();
			$has_parent_id     = 'variation' === $product_type ? $_product->get_parent_id() : ( 'variable' === $product_type ? $_product->get_id() : 0 );
			$is_same_parent_id = false;

			// Skip if parent ID already processed.
			if ( $has_parent_id && in_array( $has_parent_id, $processed_parents, true ) ) {
				continue;
			}

			// Mark this parent ID as processed.
			if ( $has_parent_id ) {
				$processed_parents[] = $has_parent_id;
			}

			if ( 'variable' === $product_type ) {
				$children                = $_product->get_children();
				$variation_first_regular = null;
				$variation_first_offer   = null;
				foreach ( $children as $child_id ) {
					$variation         = wc_get_product( $child_id );
					$is_same_parent_id = true;
					if ( $variation ) {
						$variation_first_regular = 'incl' === $tax_display
							? wc_get_price_including_tax( $_product, array( 'price' => $variation->get_regular_price() ) )
							: $variation->get_regular_price();

						$variation_first_offer = 'incl' === $tax_display
							? wc_get_price_including_tax( $_product, array( 'price' => $variation->get_sale_price() ) )
							: $variation->get_sale_price();
						break;
					}
				}
			}

			$tax_display = get_option( 'woocommerce_tax_display_shop', 'incl' );

			if ( 'variable' === $product_type ) {
				$regular_price = $variation_first_regular;
				$sale_price    = $variation_first_offer;
			} else {
				$regular_price = 'incl' === $tax_display
					? wc_get_price_including_tax( $_product, array( 'price' => $_product->get_regular_price() ) )
					: floatval( $_product->get_regular_price() );

				$sale_price = 'incl' === $tax_display
					? wc_get_price_including_tax( $_product, array( 'price' => $_product->get_sale_price() ) )
					: floatval( $_product->get_sale_price() );
			}

			// If no sale price exists, fallback to regular.
			if ( empty( $sale_price ) ) {
				$sale_price = $regular_price;
			}

			// Extension Filter: Sale Price Addon.
			$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );
			// based on extension filter use sale price or regular price for calculation.

			$offered_price = revenue()->calculate_campaign_offered_price(
				$discount_type,
				$discount_value,
				$filtered_price
			);

			$total_offer_regular_price += $regular_price * $quantity;
			$total_offer_sale_price    += $offered_price * $quantity;
		}
	}
}

// translators: %s: the discount percentage value (without the percent sign), e.g. "20".
$save_data  = sprintf( __( '%s%%', 'revenue' ), $discount_value );
$offer_text = $template_data['saveBadgeWrapper']['text'] ?? '';
$message    = str_replace( '{save_amount}', $save_data, $offer_text );

ob_start();
?>
	<div 
		class="
			<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'wrapper' ) ); ?>
			<?php echo esc_attr( $display_style ); ?> <?php echo esc_attr( $device_manager_class ); ?>
			<?php echo esc_attr( $extra_class ); ?>
		"
	>
		<?php Revenue_Template_Utils::render_wrapper_header( $campaign, $template_data ); ?>
		<?php
			Revenue_Template_Utils::render_buy_x_get_y_products_container(
				$campaign,
				$template_data,
				$placement,
				true,
				true,
				$product
			);
			?>
		<div>
			<div
				class="
					<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'buyXFooterPrice' ) ); ?>
					revx-d-flex revx-item-center revx-justify-between
				"
			>
				<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'totalText' ) ); ?>
				<div class="revx-d-flex revx-item-center revx-gap-10">
					<?php
						$summary     = Revenue_Template_Utils::get_total_price();
						$price_data  = array(
							'message' => $message,
							'type'    => 'percentage',
							'value'   => $discount_value,
						);
						$price_array = array(
							'regular_price' => $total_offer_regular_price + $total_trigger_regular_price,
							'offered_price' => $total_offer_sale_price + $total_trigger_sale_price,
							'quantity'      => 3,
							'price_data'    => $price_data,
						);
						Revenue_Template_Utils::revenue_render_product_price(
							$price_array,
							'list',
							$template_data,
							false,
							true,
							true,
							'',
							false,
							'buyXGetYTotalPrice'
						);
						?>
				</div>
			</div>
			<div class="revx-d-flex revx-item-center revx-justify-center">
				<?php
					echo wp_kses_post(
						Revenue_Template_Utils::render_add_to_cart_button(
							$template_data,
							false,
							'addToCartWrapper',
							$campaign_id,
							$campaign_type,
							$view_mode,
							$is_skip_add_to_cart,
						)
					);
					?>
			</div>
		</div>
	</div>

<input 
	type="hidden"
	name="<?php echo esc_attr( 'revx-offer-data-' . $campaign['id'] ); ?>"
	value=" <?php echo esc_html( htmlspecialchars( wp_json_encode( $offer_data ) ) ); ?>"
/>
<input 
	type="hidden"
	name="<?php echo esc_attr( 'revx-trigger-data-' . $campaign['id'] ); ?>"
	value=" <?php echo esc_html( htmlspecialchars( wp_json_encode( $trigger_data ) ) ); ?>"
/>
<input 
	type="hidden"
	name="<?php echo esc_attr( 'revx-trigger-product-id-' . $campaign['id'] ); ?>"
	value=" <?php echo esc_attr( $product->get_id() ); ?>"
/>
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
