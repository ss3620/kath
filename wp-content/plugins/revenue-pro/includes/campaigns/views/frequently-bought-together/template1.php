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

// use Revenue\Revenue_Template_Utils;

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

// Fetch required data.
$view_mode          = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
$template_data      = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
$offers             = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
$placement_settings = revenue()->get_placement_settings( $campaign['id'] );
$display_style      = isset( $placement_settings['display_style'] ) ? $placement_settings['display_style'] : 'inpage';
$is_grid_view       = 'grid' === $view_mode;
$extra_class        = '';
$total_offer_items  = is_array( $offers ) ? count( $offers ) : 0;

$is_skip_add_to_cart  = 'yes' === $campaign['skip_add_to_cart'];
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

if ( 'popup' === $display_style ) {
	$extra_class = 'revx-popup-init-size';
}
if ( 'floating' === $display_style ) {
	$extra_class = 'revx-floating-init-size';
}

$offer_products = revenue()->getOfferProductsData( $offers );

$trigger_product_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';
if ( empty( $trigger_product_relation ) ) {
	$trigger_product_relation = 'or';
}
$is_category = ( 'category' === $campaign['campaign_trigger_type'] ) || ( 'all_products' === $campaign['campaign_trigger_type'] );

$trigger_items = revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $product->get_id(), $is_category );

$fbt_items = array_merge( $trigger_items, $offer_products );
ob_start();
?>
	<div 
		class="
			<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'productBodyWrapper' ) ); ?>
			<?php echo esc_attr( $display_style ); ?> <?php echo esc_attr( $device_manager_class ); ?>
			<?php echo esc_attr( $extra_class ); ?>
		"
	>
		<div class="revx-product-body-wrapper">
			<?php Revenue_Template_Utils::render_wrapper_header( $campaign, $template_data ); ?>
			<?php echo esc_attr( Revenue_Template_Utils::render_fbt_products_container( $campaign, $template_data, $placement, true, false, $product ) ); ?>
		</div>
		<div
			class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'fbtFooter' ) ); ?> revx-d-flex revx-justify-between revx-item-center revx-gap-10"
		>
			<div class="revx-d-flex revx-item-center">
				<div data-fbt-trigger-items="trigger">
					<?php echo Revenue_Template_Utils::render_rich_text( $template_data, 'selectedTriggerTitle', '', '', '', 'text', 'selectedTitle', false, 'selectedTriggerTitle' ); ?>
					<?php echo Revenue_Template_Utils::render_rich_text( $template_data, 'selectedPrice', '', '', '', 'text', '', false, '' ); ?>
				</div>
				<div
					class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'fbtFooterIcon' ) ); ?> revx-icon"
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="1em"
						height="1em"
						fill="none"
						viewBox="0 0 16 16"
					>
						<path
							stroke="currentColor"
							stroke-linecap="round"
							stroke-linejoin="round"
							stroke-width="1.2"
							d="M8 3.334v9.333M3.333 8h9.333"
						></path>
					</svg>
				</div>
				<div data-fbt-offer-items="offer">
					<?php echo Revenue_Template_Utils::render_rich_text( $template_data, 'selectedOfferTitle', '', '', '', 'text', 'selectedTitle', false, 'selectedOfferTitle' ); ?>
					<?php echo Revenue_Template_Utils::render_rich_text( $template_data, 'selectedPrice', '', '', '', 'text', '', false, '' ); ?>
				</div>
				<div
					class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'fbtFooterIcon' ) ); ?> revx-icon"
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="1em"
						height="1em"
						fill="none"
						viewBox="0 0 16 16"
					>
						<path
							stroke="currentColor"
							stroke-linecap="round"
							stroke-width="1.5"
							d="M2.664 5.334h10.667M2.664 10.667h10.667"
						></path>
					</svg>
				</div>
				<div data-fbt-total="total">
					<?php echo Revenue_Template_Utils::render_rich_text( $template_data, 'selectedTitle', '', '', '', 'text', '', false, 'selectedTotalTitle' ); ?>
					<!-- selectedTotalTitle is used in jsx -->
					<?php echo Revenue_Template_Utils::render_rich_text( $template_data, 'selectedTotalTitle', '', '', '', 'text', 'selectedPrice', false, '' ); ?>
				</div>
			</div>
			<?php 
			echo Revenue_Template_Utils::render_add_to_cart_button(
				$template_data,
				false,
				'addToCartWrapper',
				$campaign['id'],
				$campaign['campaign_type'],
				$view_mode,
				$is_skip_add_to_cart
			);
			?>
		</div>
	</div>

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
