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

/**
 * The Template for displaying revenue view
 *
 * @package Revenue
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

// Fetch required data.

$template_data = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
$offers        = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );

$current_page       = revenue()->get_current_page();
$placement_settings = $campaign['placement_settings'];

$is_all_page_active   = isset( $placement_settings['all_page'] ) ? 'yes' === $placement_settings['all_page']['status'] : false;
$is_other_page_active = isset( $placement_settings[ $current_page ] ) ? 'yes' === $placement_settings[ $current_page ]['status'] : false;
$page_key             = ( $is_all_page_active && ! $is_other_page_active ) ? 'all_page' : $current_page;
$display_style        = isset( $placement_settings[ $page_key ]['display_style'] ) ? $placement_settings[ $page_key ]['display_style'] : 'inpage';
$is_upsell_on         = 'yes' === $campaign['spending_goal_upsell_product_status'];
$is_campaign_close    = 'yes' === $campaign['show_close_icon'];

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

$size         = $is_upsell_on ? 115 : 60;
$stroke_width = $is_upsell_on ? 12 : 8;
$label_size   = $is_upsell_on ? 18 : 16;

// use subtotal to ignore coupons, include taxes if needed.
$cart_total  = WC()->cart ? WC()->cart->get_subtotal() : 0;
$cart_total += WC()->cart->display_prices_including_tax() ? WC()->cart->get_subtotal_tax() : 0;
// Divide evenly across steps.
$step_width      = 100 / ( count( $offers ) );
$progress        = 0;
$remaining_total = $cart_total;

$total_goal = 0;
foreach ( $offers as $offer ) {
	if ( isset( $offer['spending_goal'] ) ) {
		$spending_goal = floatval( $offer['spending_goal'] );

		// JS: Math.min( (stepWidth / spendingGoal) * Math.min(spendingGoal, remainingTotal), stepWidth ).
		$contribution_to_progress = min(
			( $step_width / $spending_goal ) * min( $spending_goal, $remaining_total ),
			$step_width
		);

		$progress += $contribution_to_progress;

		// Reduce remaining total for next step.
		$remaining_total -= min( $spending_goal, $remaining_total );

		$total_goal += $spending_goal;
	}
}

$progress = min( $progress, 100 );

$reward_type_options = array(
	'free_shipping' => __( 'Free Shipping', 'revenue-pro' ),
	'discount'      => __( 'Discount', 'revenue-pro' ),
	'gift'          => __( 'Gift Items', 'revenue-pro' ),
);


$required_goal   = 0;
$current_message = '';
$reward_message  = '';

foreach ( $offers as $index => $offer ) {
	if ( ! isset( $offer['spending_goal'] ) ) {
		continue;
	}
	$required_goal += floatval( $offer['spending_goal'] );
	if ( $cart_total < $required_goal ) {
		// User hasn't reached this step yet.
		$current_message = isset( $offer['before_message'] ) ? $offer['before_message'] : '';

		$remaining_amount = $cart_total - $required_goal;

		$current_message = str_replace( '{remaining_amount}', wc_price( abs( $remaining_amount ) ), $current_message );
		if ( isset( $offer['reward_type'] ) ) {
			$current_message = str_replace( '{reward_type}', $reward_type_options[ $offer['reward_type'] ], $current_message );
		}
		if ( isset( $offer['discount_value'] ) ) {
			$current_message = str_replace( '{discount_value}', $offer['discount_value'] ?? '', $current_message );
		}

		break;
	} else {
		$reward_message = isset( $offer['after_message'] ) ? $offer['after_message'] : '';
		if ( ! isset( $offer['reward_type'] ) ) {
			continue;
		}

		switch ( $offer['reward_type'] ) {
			case 'discount':
				$discount_type = $offer['discount_type'] ?? null;
				if ( 'percentage' === $discount_type ) {
					$reward_message = str_replace(
						'{discount_value}',
						( $offer['discount_value'] ?? 0 ) . '%',
						$reward_message
					);
				} else {
					$reward_message = str_replace(
						'{discount_value}',
						wc_price( $offer['discount_value'] ?? 0 ),
						$reward_message
					);
				}
				break;

			default:
				break;
		}
	}
}

if ( $cart_total >= $required_goal && isset( $campaign['all_goals_complete_message'] ) ) {
	$is_all_completed = true;
	$current_message  = $campaign['all_goals_complete_message'];
}

$upsell_products = array();


if ( 'yes' === $campaign['spending_goal_upsell_product_status'] ) {


	$data            = $campaign['spending_goal_upsell_products'];
	$upsell_products = array();

	if ( ! is_array( $data ) ) {
		$data = array();
	}

	foreach ( $data as $order ) {
		// Ensure 'products' is an array of product IDs.
		if ( ! isset( $order['products'] ) || ! is_array( $order['products'] ) ) {
			continue;
		}

		// Process each product ID.
		foreach ( $order['products'] as $item_data ) {

			$regular_price    = (float) $item_data['regular_price'];
			$quantity         = isset( $order['quantity'] ) ? (int) $order['quantity'] : 0;
			$discounted_price = $regular_price;
			$discount_amount  = isset( $order['value'] ) ? (float) $order['value'] : 0;



			// Calculate discounted price based on order type.
			if ( isset( $order['type'] ) ) {
				switch ( $order['type'] ) {
					case 'percentage':
						$discount_value   = $discount_amount;
						$discount_amount  = number_format( ( $regular_price * $discount_value ) / 100, 2 );
						$discounted_price = number_format( $regular_price - $discount_amount, 2 );
						break;

					case 'fixed_discount':
						$discount_amount  = number_format( $discount_amount, 2 );
						$discounted_price = number_format( $regular_price - $discount_amount, 2 );
						break;

					case 'free':
						$discounted_price = '0.00';
						$discount_amount  = '100%';
						break;

					case 'no_discount':
						$discount_amount  = '0';
						$discounted_price = number_format( $regular_price, 2 );
						break;
				}
			}

			// Prepare product data.
			$upsell_products[] = array(
				'item_id'       => $item_data['item_id'],
				'item_name'     => $item_data['item_name'],
				'thumbnail'     => $item_data['thumbnail'], // Get the product thumbnail URL.
				'regular_price' => number_format( $regular_price, 2 ),
				'url'           => get_permalink( $item_data['item_id'] ),
				'sale_price'    => $discounted_price,
				'quantity'      => $quantity,
				'type'          => $order['type'],
				'value'         => isset( $order['value'] ) ? $order['value'] : '',
			);
		}
	}
}

$is_drawer = 'drawer' === $display_style;
if ( $is_drawer ) {
	$wrapper_id = 'drawerWrapper';

	$drawer_position = $placement_settings[ $page_key ]['drawer_position'] ?? 'top-right';

	$radius          = ( $size - $stroke_width ) / 2;
	$circumference   = 2 * pi() * $radius;
	$progress_offset = $circumference - ( $progress / 100 ) * $circumference;
} elseif ( $is_all_page_active && ! $is_other_page_active ) {
	$wrapper_id = 'allSideProgressWrapper';
} else {
	$wrapper_id = 'wrapper';
}

?>

<div
	id="revx-progress-<?php echo esc_attr( $display_style ); ?>"
	<?php # the revx-relative is for keeping the success message within the container. ?>
	class="
		revx-relative
		<?php
		echo esc_attr(
			Revenue_Template_Utils::get_element_class(
				$template_data,
				$wrapper_id
			)
		);
		?>
				<?php echo esc_attr( $display_style ); ?>
		<?php echo $is_drawer ? esc_attr( $drawer_position ) : ''; ?>
		revx-d-flex
		<?php echo $is_drawer ? 'revx-drawer-container' : 'revx-w-full';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>
		<?php echo ( $is_drawer || ( $is_all_page_active && ! $is_other_page_active ) ) ? '' : 'revx-flex-column';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>
		<?php echo esc_attr( $device_manager_class ); ?>
	"
	data-position="<?php echo esc_attr( $display_style ); ?>"
	data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>"
	data-campaign-type="spending_goal"
	data-container-level="top"
	data-cart-total="<?php echo esc_attr( $cart_total ); ?>"
	data-progress="<?php echo esc_attr( $progress ); ?>"
	data-final-message="<?php echo esc_attr( $campaign['all_goals_complete_message'] ); ?>"
	data-show-confetti="<?php echo esc_attr( $campaign['show_confetti'] ); ?>"
	data-radius="<?php echo esc_attr( $is_drawer ? $radius : '' ); ?>"
>

<?php

if ( 'drawer' === $display_style ) {
	if ( $is_campaign_close ) {
		echo wp_kses( Revenue_Template_Utils::render_campaign_close( $template_data, 'revx-drawer-closer' ), revenue()->get_allowed_tag() );
	}

	?>
	<div
		class="
			<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'circularProgressContainer' ) ); ?>
			revx-circular-progress-container revx-d-flex revx-item-center revx-justify-center revx-flex-column revx-drawer-opener
		"
	>
		<div
			class="revx-relative revx-d-flex revx-item-center revx-justify-center"
		>
			<svg width="<?php echo esc_attr( $size ); ?>" height="<?php echo esc_attr( $size ); ?>">
				<circle
					class="revx-progress-empty"
					r="<?php echo esc_attr( $radius ); ?>"
					cx="<?php echo esc_attr( $size / 2 ); ?>"
					cy="<?php echo esc_attr( $size / 2 ); ?>"
					style="
						stroke: var(
							--revx-circlular-progress-bar-inactive,
							#31353f
						);
						stroke-width: <?php echo esc_attr( $stroke_width ); ?>;
						fill: none;
					"
				></circle>
				<circle
					class="revx-progress-active"
					r="<?php echo esc_attr( $radius ); ?>"
					cx="<?php echo esc_attr( $size / 2 ); ?>"
					cy="<?php echo esc_attr( $size / 2 ); ?>"
					style="
						stroke: var(
							--revx-circlular-progress-bar-active,
							#f2ae40
						);
						stroke-width: <?php echo esc_attr( $stroke_width ); ?>;
						stroke-dasharray: <?php echo esc_attr( $circumference ); ?>;
						stroke-dashoffset: <?php echo esc_attr( $progress_offset ); ?>;
						stroke-linecap: round;
						fill: none;
						transition: stroke-dashoffset 500ms ease-in-out;
					"
				></circle>
			</svg>
			<div
				class="revx-circular-text revx-absolute"
				style="font-size: <?php echo esc_attr( ( '100' === $progress ) ? $label_size - 3 : $label_size ); ?>px"
			>
				<?php echo number_format( $progress, 2 ); ?>%
			</div>
		</div>
		<?php echo wp_kses( Revenue_Template_Utils::render_rich_text( $template_data, 'drawerCompleteMessage' ), revenue()->get_allowed_tag() ); ?>
	</div>
	<div
		class="
			<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'campaignDrawerContent' ) ); ?>
			revx-d-flex revx-item-center revx-drawer-content revx-w-full
		"
	>
		<div
			class="revx-d-flex revx-flex-column revx-w-full"
			style="gap: var(--revx-drawer-content-gap)"
		>
			<div class="revx-d-flex revx-item-center revx-justify-center revx-flex-wrap revx-gap-10">
				<?php echo wp_kses( Revenue_Template_Utils::render_rich_text( $template_data, 'spgHeading', $current_message, 'revx-text-center' ), revenue()->get_allowed_tag() ); ?>
				<?php echo wp_kses( Revenue_Template_Utils::render_add_to_cart_button( $template_data, false, 'shopNowButton' ), revenue()->get_allowed_tag() ); ?>
			</div>
			<?php echo wp_kses( Revenue_Template_Utils::render_progressbar( $template_data, 'CampaignProgressbar', $progress, $campaign ), revenue()->get_allowed_tag() ); ?>
			<?php Revenue_Template_Utils::render_products_container( $campaign, $template_data, $placement, true ); ?>
		</div>
	</div>
	<div class="revx-spending-goal-success">
		<span> <?php echo esc_attr( $reward_message ); ?> </span>
	</div>
	<?php
} else {
	if ( $is_all_page_active && ! $is_other_page_active ) {
		echo '<div
			class="revx-d-flex revx-flex-column revx-w-full"
			style="
				max-width: var(--revx-container-max-width);
				max-height: var(--revx-container-max-height);
				gap: var(--revx-container-gap);
				margin: 0px auto;
			"
		>';
	}
	?>
		<div class="revx-d-flex revx-item-center revx-justify-center revx-flex-wrap revx-gap-10">
			<?php echo wp_kses( Revenue_Template_Utils::render_rich_text( $template_data, 'spgHeading', $current_message, 'revx-text-center' ), revenue()->get_allowed_tag() ); ?>
			<?php echo wp_kses( Revenue_Template_Utils::render_add_to_cart_button( $template_data, false, 'shopNowButton' ), revenue()->get_allowed_tag() ); ?>
		</div>
		<?php echo wp_kses( Revenue_Template_Utils::render_progressbar( $template_data, 'CampaignProgressbar', $progress, $campaign ), revenue()->get_allowed_tag() ); ?>
		<?php Revenue_Template_Utils::render_products_container( $campaign, $template_data, $placement, true ); ?>
	<?php
	if ( $is_all_page_active && ! $is_other_page_active ) {
		echo '</div>';
	}
	?>
	<div class="revx-spending-goal-success">
		<svg
			style="
				background-color: #00A464;
				color: #ffffff;
				width: calc( var(--revx-progress-height, 4px) * 3 );
				height: calc( var(--revx-progress-height, 4px) * 3 );
				border-radius: 50%;
				padding: 4px;
				box-sizing: content-box;
			"
			xmlns="http://www.w3.org/2000/svg"
			width="1em"
			height="1em"
			fill="none"
			viewBox="0 0 24 24"
		>
			<path stroke="currentColor" d="M20 6 9 17l-5-5"/>
		</svg>
		<span> <?php echo esc_attr( $reward_message ); ?> </span>
	</div>
	<?php
}
?>
	<input type="hidden" name="revenue_spending_goal_offer" value="<?php echo esc_attr( wp_json_encode( $offers ) ); ?>" />
	<input type="hidden" name="revenue_upsell_products" value="<?php echo esc_attr( wp_json_encode( $upsell_products ) ); ?>" />
</div>
<?php

