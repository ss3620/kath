<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Stock Scarcity inpage Template
 *
 * This file handles the display of Stock Scarcity offers in a inpage container.
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




// Fetch required data
$display_type       = revenue()->get_placement_settings( $campaign['id'], $placement, 'display_style' ) ?? 'inpage';
$view_mode          = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
$template_data      = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
$placement_settings = revenue()->get_placement_settings( $campaign['id'] );

$is_progress_bar = $campaign['stock_scarcity_general_message_settings']['enable_stock_bar'] === 'yes';

$product = Revenue_Product_Context::get_product_context();
$stock_quantity = null;
$campaign_id    = $campaign['id'];
$product_id     = $product->get_id();

if ( $product && is_a( $product, 'WC_Product' ) ) {
	$stock_quantity = $product->get_stock_quantity();
}

$sold_stock = $product->get_total_sales();
$view_count = get_post_meta( $product_id, $campaign_id . '_views_counter', true );
$user_count = (int) $product->get_meta( $product->get_id() . '_user_purchase_count', true );
$user_count = (int) $this->get_distinct_user_count_by_product( $product->get_id() );

$total_stock = $sold_stock + $stock_quantity;
$percentage  = round( $total_stock > 0 ? ( $stock_quantity / $total_stock ) * 100 : 0 );
if ( ! $product->managing_stock() || 0 === $stock_quantity ) {
	return;
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

ob_start();
?>
	<div
		class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'wrapper' ) ); ?> revx-stock-scarcity-wrapper revx-flex-column inpage <?php echo esc_attr( $device_manager_class ); ?>"
	>   
		<?php
		echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'scarcityMessage' ) );
		if ( $is_progress_bar ) {
			echo wp_kses( Revenue_Template_Utils::render_progressbar( $template_data, 'CampaignProgressbar', $percentage, $campaign, 'two' ), revenue()->get_allowed_tag() ); //phpcs:ignore
		}
		?>
	</div>

<?php
$output = ob_get_clean();

// Output content based on display style.
switch ( $display_type ) {
	case 'inpage':
		Revenue_Template_Utils::inpage_container( $campaign, $output );
		break;
}
