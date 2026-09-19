<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Bundle Discount inpage Template
 *
 * This file handles the display of bundle discount offers in an inpage container.
 *
 * @package    Revenue
 * @subpackage Templates
 * @version    1.0.0
 */

namespace Revenue;

use Revenue\Services\Revenue_Product_Context;

/**
 * The Template for displaying bundle discount view
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
$display_type           = revenue()->get_placement_settings( $campaign['id'], $placement, 'display_style' ) ?? 'inpage';
$view_mode              = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
$template_data          = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
$offers                 = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
$placement_settings     = revenue()->get_placement_settings( $campaign['id'] );
$display_style          = isset( $placement_settings['display_style'] ) ? $placement_settings['display_style'] : 'inpage';
$slider_columns         = json_encode( Revenue_Template_Utils::get_slider_data( $template_data ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
$products_wrapper_class = 'revx-slider-wrapper';


ob_start();
Revenue_Template_Utils::render_products_wrapper(
	$campaign,
	$placement,
	true,
	'bundleFooterPrice',
	'wrapper',
	$product
);

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
