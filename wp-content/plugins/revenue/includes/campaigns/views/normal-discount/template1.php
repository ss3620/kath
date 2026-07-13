<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * The Template for displaying revenue view
 *
 * This file handles the display of normal discount offers in a inpage container.
 *
 * @package    Revenue
 * @subpackage Templates
 * @version    1.0.0
 */

namespace Revenue;

use Revenue;

defined( 'ABSPATH' ) || exit;
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

ob_start();
Revenue_Template_Utils::render_products_wrapper( $campaign, $placement, true );

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
