<?php
/**
 * Stock Scarcity inpage Template
 *
 * This file handles the display of Stock Scarcity offers in a inpage container.
 *
 * @package    Revenue
 * @subpackage Templates
 * @version    2.0.0
 */

//phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar

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


// Fetch required data.
$display_type       = revenue()->get_placement_settings( $campaign['id'], $placement, 'display_style' ) ?? 'inpage';
$view_mode          = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
$template_data      = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
$placement_settings = revenue()->get_placement_settings( $campaign['id'] );

$product        = Revenue_Product_Context::get_product_context();
$stock_quantity = null;
$campaign_id    = $campaign['id'];
$product_id     = $product->get_id();

if ( $product && is_a( $product, 'WC_Product' ) ) {
	$stock_quantity = $product->get_stock_quantity();
}

$sold_stock = $product->get_total_sales();
$view_count = get_post_meta( $product_id, $campaign_id . '_views_counter', true );
if ( empty( $view_count ) ) {
	$view_count = 0;
}
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

// Save one time sold quantity.
$fixed_quantity_meta_key = $campaign_id . '_fixed_quantity';
$fixed_sold_meta_key     = $campaign_id . '_fixed_sold_quantity';
$fixed_view_meta_key     = $campaign_id . '_views_counter';
$fixed_user_meta_key     = $campaign_id . '_fixed_user';
$fixed_quantity          = (int) get_post_meta( $product_id, $fixed_quantity_meta_key, true );
$fixed_sold_quantity     = (int) get_post_meta( $product_id, $fixed_sold_meta_key, true );
$fixed_views             = (int) get_post_meta( $product_id, $fixed_view_meta_key, true );
$fixed_users             = (int) get_post_meta( $product_id, $fixed_user_meta_key, true );
// If the fixed sold quantity is not set, save it as the current sold quantity.

if ( empty( $fixed_sold_quantity ) ) {
	$fixed_sold_quantity = $sold_stock;
	update_post_meta( $product_id, $fixed_sold_meta_key, $fixed_sold_quantity );
}
	// If the fixed quantity is not set, save it as the current quantity.
if ( empty( $fixed_quantity ) ) {
	$fixed_quantity = $stock_quantity;
	update_post_meta( $product_id, $fixed_quantity_meta_key, $fixed_quantity );
}
	// If the fixed views is not set, save it as the current views.
if ( empty( $fixed_views ) ) {
	$fixed_views += $view_count;
	update_post_meta( $product_id, $fixed_view_meta_key, $fixed_views );
}
	// If the fixed users is not set, save it as the current users.
if ( empty( $fixed_users ) ) {
	$fixed_users = $user_count;
	update_post_meta( $product_id, $fixed_user_meta_key, $fixed_users );
}

$quantity_diff = (int) $stock_quantity - (int) $fixed_quantity;
$sold_diff     = (int) $sold_stock - (int) $fixed_sold_quantity;
$view_diff     = (int) $view_count - (int) $fixed_views;
$user_diff     = (int) $user_count - (int) $fixed_users;

$message_type     = $campaign['stock_scarcity_message_type'] ?? 'generalMessage';
$general_settings = $campaign['stock_scarcity_general_message_settings'] ?? array();
$flip_settings    = $campaign['stock_scarcity_flip_message_settings'] ?? array();

if ( 'generalMessage' === $message_type ) {
	$is_progress_bar = isset( $campaign['stock_scarcity_general_message_settings']['enable_stock_bar'] ) && 'yes' === $campaign['stock_scarcity_general_message_settings']['enable_stock_bar'];
} else {
	$is_progress_bar = isset( $campaign['stock_scarcity_flip_message_settings']['enable_stock_bar'] ) && 'yes' === $campaign['stock_scarcity_flip_message_settings']['enable_stock_bar'];
}

// for general message.
$general_message     = '';
$flip_first_message  = '';
$flip_second_message = '';
if ( 'generalMessage' === $message_type ) {
	// General Message Settings.
	$in_stock_message     = $general_settings['in_stock_message'] ?? __( '{stock_number} units available now!😊', 'revenue' );
	$low_stock_message    = $general_settings['low_stock_message'] ?? __( "Only {stock_number} left - don't miss out!🏃‍♀️", 'revenue' );
	$urgent_stock_message = $general_settings['urgent_stock_message'] ?? __( 'Only {stock_number} left - restock uncertain!❗', 'revenue' );
	$is_low_stock         = $general_settings['isLowStockChecked'] ?? 'no';
	$is_urgent_stock      = $general_settings['isUrgentStockChecked'] ?? 'no';
	$enable_stock_bar     = $general_settings['enable_stock_bar'] ?? 'no';
	$enable_fake_stock    = $general_settings['enable_fake_stock'] ?? 'no';
	$repeat_interval      = $general_settings['repeat_interval'] ?? 'no';
	$low_stock_amount     = $general_settings['low_stock_amount'] ?? 0;
	$urgent_stock_amount  = $general_settings['urgent_stock_amount'] ?? 0;
	$in_stock_fake_amount = $general_settings['in_stock_fake_amount'] ?? 0;
	$low_fake_amount      = $general_settings['low_fake_amount'] ?? 0;
	$urgent_fake_amount   = $general_settings['urgent_fake_amount'] ?? 0;


	// If Fake stock is not enabled.
	if ( null !== $stock_quantity && 'yes' !== $enable_fake_stock ) {
		if ( 'yes' === $is_low_stock && $stock_quantity <= $low_stock_amount && $stock_quantity > $urgent_stock_amount ) {
			$general_message = $low_stock_message;
		} elseif ( 'yes' === $is_urgent_stock && $stock_quantity <= $urgent_stock_amount && $stock_quantity <= $low_stock_amount ) {
			$general_message = $urgent_stock_message;
		} else {
			$general_message = $in_stock_message;
		}
		$general_message = str_replace( '{stock_number}', $stock_quantity, $general_message );
	}
	if ( null !== $stock_quantity && 'yes' === $enable_fake_stock ) {

		// If the fixed sold quantity is not set, save it as the current sold quantity.
		$fixed_general_quantity_meta_key = $campaign_id . '_fixed_gen_quantity';
		$fixed_gen_stock_quantity        = get_post_meta( $product_id, $fixed_general_quantity_meta_key, true );
		if ( empty( $fixed_gen_stock_quantity ) ) {
			$fixed_gen_stock_quantity = $stock_quantity;
			update_post_meta( $product_id, $fixed_general_quantity_meta_key, $fixed_gen_stock_quantity );
		}
		$quantity_gen_diff   = (int) $stock_quantity - (int) $fixed_gen_stock_quantity;
		$fake_stock_quantity = (int) $in_stock_fake_amount + (int) $quantity_gen_diff;

		// If Fake is enable and repeat interval.
		if ( 'yes' === $repeat_interval && $stock_quantity >= $in_stock_fake_amount && 0 === $fake_stock_quantity ) {
			$fake_stock_quantity = $in_stock_fake_amount;
			update_post_meta( $product_id, $fixed_general_quantity_meta_key, $stock_quantity );
		}

		if ( 'yes' === $is_low_stock && $fake_stock_quantity <= $low_fake_amount && $fake_stock_quantity > $urgent_fake_amount ) {
			$general_message = $low_stock_message;
		} elseif ( 'yes' === $is_urgent_stock && $fake_stock_quantity <= $urgent_fake_amount && $fake_stock_quantity <= $low_fake_amount ) {
			$general_message = $urgent_stock_message;
		} else {
			$general_message = $in_stock_message;
		}
		$general_message = str_replace( '{stock_number}', $fake_stock_quantity, $general_message );
	}
} elseif ( 'flipMessage' === $message_type ) {
	// Flip Message Settings.
	$enable_fake_stock_flip      = $flip_settings['enable_fake_stock'] ?? 'no';
	$first_repeat_interval_flip  = $flip_settings['first_repeat_interval'] ?? 'no';
	$second_repeat_interval_flip = $flip_settings['second_repeat_interval'] ?? 'no';
	$select_first_message_flip   = $flip_settings['select_first_message'] ?? 'stockNumber';
	$first_sale_message_flip     = $flip_settings['first_sale_message'] ?? '{sales_number} units SOLD already! Grab Yours👍';
	$select_second_message_flip  = $flip_settings['select_second_message'] ?? 'stockNumber';
	$second_view_message_flip    = $flip_settings['second_view_message'] ?? '{view_number} people are looking at this right now! Act Fast⚡';
	$first_stock_message_flip    = $flip_settings['first_stock_message'] ?? '{stock_number} units available now!😊';
	$first_view_message_flip     = $flip_settings['first_view_message'] ?? '{view_number} people are looking at this right now! Act Fast⚡';
	$first_user_message_flip     = $flip_settings['first_user_message'] ?? '{shopper_number} PEOPLE just bought this! Will you be next?🔥';
	$first_fake_amount_flip      = $flip_settings['first_fake_amount'] ?? 0;
	$second_fake_amount_flip     = $flip_settings['second_fake_amount'] ?? 0;
	$second_stock_message_flip   = $flip_settings['second_stock_message'] ?? '{stock_number} units available now!😊!';
	$second_sale_message_flip    = $flip_settings['second_sale_message'] ?? '{sales_number} units SOLD already! Grab Yours👍';
	$second_user_message_flip    = $flip_settings['second_user_message'] ?? '{shopper_number} PEOPLE just bought this! Will you be next?🔥';
	// If Fake stock is not enabled.

	if ( null !== $stock_quantity && 'yes' !== $enable_fake_stock_flip ) {
		switch ( $select_first_message_flip ) {
			case 'stockNumber':
				$flip_first_message = str_replace( '{stock_number}', $stock_quantity, $first_stock_message_flip );
				break;
			case 'saleNumber':
				$flip_first_message = str_replace( '{sales_number}', $sold_stock, $first_sale_message_flip );
				break;
			case 'viewNumber':
				$flip_first_message = str_replace( '{view_number}', $view_count, $first_view_message_flip );
				break;
			case 'userNumber':
				$flip_first_message = str_replace( '{shopper_number}', $user_count, $first_user_message_flip );
				break;
			default:
				$flip_first_message = str_replace( '{stock_number}', $stock_quantity, $first_stock_message_flip );
		}

		switch ( $select_second_message_flip ) {
			case 'stockNumber':
				$flip_second_message = str_replace( '{stock_number}', $stock_quantity, $second_stock_message_flip );
				break;
			case 'saleNumber':
				$flip_second_message = str_replace( '{sales_number}', $sold_stock, $second_sale_message_flip );
				break;
			case 'viewNumber':
				$flip_second_message = str_replace( '{view_number}', $view_count, $second_view_message_flip );
				break;
			case 'userNumber':
				$flip_second_message = str_replace( '{shopper_number}', $user_count, $second_user_message_flip );
				break;
			default:
				$flip_second_message = str_replace( '{stock_number}', $stock_quantity, $second_stock_message_flip );
		}
	}

	// If Fake stock is enabled.
	if ( null !== $stock_quantity && 'yes' === $enable_fake_stock_flip ) {

		$fake_stock_quantity_one = $first_fake_amount_flip + $quantity_diff;
		// If Fake is enable and first repeat interval.
		if ( 'yes' === $first_repeat_interval_flip && $stock_quantity >= $first_fake_amount_flip && 'stockNumber' === $select_first_message_flip && 0 === $fake_stock_quantity_one ) {
			$fake_stock_quantity_one = $first_fake_amount_flip;
			update_post_meta( $product_id, $fixed_quantity_meta_key, $stock_quantity );
		}
		$fake_sold_stock_one = $first_fake_amount_flip + $sold_diff;
		$fake_view_count_one = $first_fake_amount_flip + $view_diff;
		$fake_user_count_one = $first_fake_amount_flip + $user_diff;

		if ( empty( $fake_view_count_one ) ) {
			$fake_view_count_one = 0;
		}

		if ( empty( $fake_user_count_one ) ) {
			$fake_user_count_one = 0;
		}

		$fake_stock_quantity_two = $second_fake_amount_flip + $quantity_diff;
		// If Fake is enable and second repeat interval.
		if ( 'yes' === $second_repeat_interval_flip && $stock_quantity >= $second_fake_amount_flip && 'stockNumber' === $select_second_message_flip && 0 === $fake_stock_quantity_two ) {
			$fake_stock_quantity_two = $first_fake_amount_flip;
			update_post_meta( $product_id, $fixed_quantity_meta_key, $stock_quantity );
		}
		$fake_sold_stock_two = $second_fake_amount_flip + $sold_diff;
		$fake_view_count_two = $second_fake_amount_flip + $view_diff;
		$fake_user_count_two = $second_fake_amount_flip + $user_diff;

		if ( empty( $fake_view_count_two ) ) {
			$fake_view_count_two = 0;
		}

		if ( empty( $fake_user_count_two ) ) {
			$fake_user_count_two = 0;
		}

		switch ( $select_first_message_flip ) {
			case 'stockNumber':
				$flip_first_message = str_replace( '{stock_number}', $fake_stock_quantity_one, $first_stock_message_flip );
				break;
			case 'saleNumber':
				$flip_first_message = str_replace( '{sales_number}', $fake_sold_stock_one, $first_sale_message_flip );
				break;
			case 'viewNumber':
				$flip_first_message = str_replace( '{view_number}', $fake_view_count_one, $first_view_message_flip );
				break;
			case 'userNumber':
				$flip_first_message = str_replace( '{shopper_number}', $fake_user_count_one, $first_user_message_flip );
				break;
		}

		switch ( $select_second_message_flip ) {
			case 'stockNumber':
				$flip_second_message = str_replace( '{stock_number}', $fake_stock_quantity_two, $second_stock_message_flip );
				break;
			case 'saleNumber':
				$flip_second_message = str_replace( '{sales_number}', $fake_sold_stock_two, $second_sale_message_flip );
				break;
			case 'viewNumber':
				$flip_second_message = str_replace( '{view_number}', $fake_view_count_two, $second_view_message_flip );
				break;
			case 'userNumber':
				$flip_second_message = str_replace( '{shopper_number}', $fake_user_count_two, $second_user_message_flip );
				break;
		}
	}
}

ob_start();
?>
	<div
		class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'wrapper' ) ); ?> revx-stock-scarcity-wrapper revx-flex-column inpage <?php echo esc_attr( $device_manager_class ); ?>"
	>   

		<?php
		if ( 'flipMessage' === $message_type ) {
			?>
			<div class="revx-flip-wrapper">
			<?php
			echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'scarcityMessage', $flip_first_message, 'revx-flip-text' ) );
			echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'scarcityMessage', $flip_second_message, 'revx-flip-text' ) );
			?>
			</div>
			<?php
		} else {
			echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'scarcityMessage', $general_message ) );
		}
		if ( $is_progress_bar ) {
			echo wp_kses( Revenue_Template_Utils::render_progressbar( $template_data, 'CampaignProgressbar', $percentage, $campaign ), revenue()->get_allowed_tag() ); //phpcs:ignore
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
