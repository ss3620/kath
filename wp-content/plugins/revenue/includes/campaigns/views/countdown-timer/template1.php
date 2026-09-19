<?php
/**
 * Countdown Timer inpage Template
 *
 * This file handles the display of Countdown Timer offers in a inpage container.
 *
 * @package    Revenue
 * @subpackage Templates
 * @version    1.0.0
 */

//phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar

namespace Revenue;

use Revenue;

/**
 * The Template for displaying revenue view.
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

$stock_quantity = null;
$campaign_id    = $campaign['id'];

$progressbar_enable_shop = $campaign['countdown_timer_shop_progress_bar'] ?? 'no';
$progressbar_enable_cart = $campaign['countdown_timer_cart_progress_bar'] ?? 'no';
$action_type             = $campaign['countdown_timer_entire_site_action_type'] ?? 'makeFullAction';
$action_link             = $campaign['countdown_timer_entire_site_action_link'] ?? '#';
$action_enable           = $campaign['countdown_timer_entire_site_action_enable'] ?? 'yes';

$is_close_button_enable = $campaign['countdown_timer_enable_close_button'] ?? 'no';
$is_all_page_enable     = 'no';
if ( isset( $campaign['placement_settings']['all_page'] ) && ! empty( $campaign['placement_settings']['all_page'] ) && 'yes' === $campaign['placement_settings']['all_page']['status'] ) {
	$is_all_page_enable = 'yes';
}

$product_divider_icon = $template_data['productDigitContainer']['style']['inpage']['lg']['value'];
$cart_divider_icon    = $template_data['cartDigitContainer']['style']['inpage']['lg']['value'];
$shop_divider_icon    = $template_data['shopDigitContainer']['style']['inpage']['lg']['value'];
$entire_divider_icon  = isset( $template_data['entireDigitContainer']['style'][ $display_type ] ) ? $template_data['entireDigitContainer']['style'][ $display_type ]['lg']['value'] : '';

$cta_link = ! empty( $campaign['countdown_timer_entire_site_action_link'] ) ? $campaign['countdown_timer_entire_site_action_link'] : wc_get_page_permalink( 'shop' );

$animation_enable   = $campaign['animation_settings_enable'] ?? 'yes';
$animation_type     = $campaign['animation_type'] ?? 'none';
$animation_duration = $campaign['animation_duration'] ?? 1;
$animation_delay    = $campaign['delay_between_loop'] ?? 1;
$animation_style    = '';

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

$progress = 0;
// Use the same localized data that the frontend timer uses.
$countdown_config = $this->count_down_localize_data( $campaign );
$current_page     = $countdown_config['currentPage'];
$timer_type       = $countdown_config['countdownTimerType'];
$time_frame       = $countdown_config['timeFrameMode'];
$is_modified_date = ( $timer_type === 'evergreen' ) ||
					( $timer_type === 'static' && $time_frame === 'startNow' );

if ( 'cart_page' === $current_page || 'shop_page' === $current_page ) {
	$start = $is_modified_date
	? ( isset( $countdown_config['modifiedDateTime'] )
		? strtotime( $countdown_config['modifiedDateTime'] )
		: false
		)
	: ( isset( $countdown_config['startDateTime'] )
		? strtotime( $countdown_config['startDateTime'] )
		: false
		);
	$end   = isset( $countdown_config['endDateTime'] ) ? strtotime( $countdown_config['endDateTime'] ) : false;
	if ( $start && $end && $end > $start ) {
		// $now       = time(); // Universal time
		$now       = current_time( 'timestamp' ); // Local time
		$remaining = $end - $now;
		$total     = $end - $start;
		$progress  = ( $total > 0 ) ? min( 100, max( 0, $remaining / $total * 100 ) ) : 0;
	}
}

$animation_class = '';
if ( 'yes' === $animation_enable ) {
	$animation_class = 'revx-btn-animation revx-btn-' . esc_attr( $animation_type );
	$animation_style = sprintf(
		'animation-duration: %dms; animation-iteration-count: infinite; animation-fill-mode: both; animation-timing-function: ease-in-out; animation-delay: %dms; cursor: pointer;',
		esc_attr( $animation_duration * 1000 ),
		esc_attr( 0 ) // set from jquery, animated-atc.js, keep.
	);
}

ob_start();
?>
<div 
	data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>" 
	class="revx-countdown-timer-p-wrapper <?php echo esc_attr( $device_manager_class ); ?>"
>
	<?php
	if ( is_cart() && isset( $campaign['placement_settings']['cart_page'] ) && ! empty( $campaign['placement_settings']['cart_page'] ) && 'yes' === $campaign['placement_settings']['cart_page']['status'] ) {
		$campaign_subheading = $campaign['banner_subheading'] ?? $campaign['builder']['cartSubheading']['text'];
		?>
		<div
			class="revx-cart-cdt-container <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'CartCountdownContainer' ) ); ?> revx-countdown-timer-wrapper revx-frontend revx-left-align revx-flex-column inpage"
		>
			<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'cartSubheading', $campaign_subheading ) ); ?>
			<div
				class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'cartDigitContainer' ) ); ?> revx-countdown-digit-wrapper revx-left-align"
			>
				<div class="revx-d-flex revx-item-center">
						<div class="revx-cart-days">00</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'cartCountdownLabel', '', 'revx-countdown-digit-label', '', 'dayLabel', 'cartDigitContainer' ) ); ?>
					</div>
					<?php echo wp_kses_post( Revenue_Template_Utils::get_divider_icon( $cart_divider_icon, false ) ); ?>

					<div class="revx-d-flex revx-item-center">
						<div class="revx-cart-hours">00</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'cartCountdownLabel', '', 'revx-countdown-digit-label', '', 'hourLabel', 'cartDigitContainer' ) ); ?>
					</div>
					<?php echo wp_kses_post( Revenue_Template_Utils::get_divider_icon( $cart_divider_icon, false ) ); ?>

					<div class="revx-d-flex revx-item-center">
						<div class="revx-cart-minutes">00</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'cartCountdownLabel', '', 'revx-countdown-digit-label', '', 'minuteLabel', 'cartDigitContainer' ) ); ?>
					</div>
					<?php echo wp_kses_post( Revenue_Template_Utils::get_divider_icon( $cart_divider_icon, false ) ); ?>

					<div class="revx-d-flex revx-item-center">
						<div class="revx-cart-seconds">00</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'cartCountdownLabel', '', 'revx-countdown-digit-label', '', 'secondLabel', 'cartDigitContainer' ) ); ?>
					</div>
			</div>
			<?php
			if ( 'yes' === $progressbar_enable_cart ) {
				echo wp_kses( Revenue_Template_Utils::render_progressbar( $template_data, 'cartCampaignProgressbar', $progress, $campaign ), revenue()->get_allowed_tag() ); //phpcs:ignore
			}
			?>
		</div>

		<?php
	} elseif ( is_product() && isset( $campaign['placement_settings']['product_page'] ) && ! empty( $campaign['placement_settings']['product_page'] ) && 'yes' === $campaign['placement_settings']['product_page']['status'] ) {
		$campaign_heading    = $campaign['banner_heading'] ?? $campaign['builder']['productHeading']['text'];
		$campaign_subheading = $campaign['banner_subheading'] ?? $campaign['builder']['productSubheading']['text'];
		?>
			<div
				data-animation-duration="<?php echo esc_attr( $animation_duration ); ?>"
				data-animation-delay="<?php echo esc_attr( $animation_delay ); ?>"
				data-animate-campaign="countdown_timer" <?php // for use in jquery ?>
				class="revx-product-cdt-container 
				<?php echo esc_attr( $animation_class ); ?> 
				<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'ProductCountdownContainer' ) ); ?> 
				revx-countdown-timer-wrapper revx-frontend inpage
				"
				style="<?php echo esc_attr( $animation_style ); ?>"
			>
				<div
					class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'productCountdownContent' ) ); ?> revx-flex-column"
				>
					<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'productHeading', $campaign_heading ) ); ?>
					<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'productSubheading', $campaign_subheading ) ); ?>
				</div>
				<div
					class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'productDigitContainer' ) ); ?> revx-countdown-digit-wrapper"
				>
					<div class="revx-d-flex revx-item-center revx-justify-center revx-flex-column">
						<div
							class="revx-product-days <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'productCountdownDigit' ) ); ?> revx-countdown-digit-container"
						>
							00
						</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'productCountdownLabel', '', 'revx-countdown-digit-label', '', 'dayLabel', '' ) ); ?>
					</div>
					<?php echo wp_kses_post( Revenue_Template_Utils::get_divider_icon( $product_divider_icon, true ) ); ?>

					<div class="revx-d-flex revx-item-center revx-justify-center revx-flex-column">
						<div
							class="revx-product-hours <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'productCountdownDigit' ) ); ?> revx-countdown-digit-container"
						>
							00
						</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'productCountdownLabel', '', 'revx-countdown-digit-label', '', 'hourLabel', '' ) ); ?>
					</div>
					<?php echo wp_kses_post( Revenue_Template_Utils::get_divider_icon( $product_divider_icon, true ) ); ?>

					<div class="revx-d-flex revx-item-center revx-justify-center revx-flex-column">
						<div
							class="revx-product-minutes <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'productCountdownDigit' ) ); ?> revx-countdown-digit-container"
						>
							00
						</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'productCountdownLabel', '', 'revx-countdown-digit-label', '', 'minuteLabel', '' ) ); ?>
					</div>
					<?php echo wp_kses_post( Revenue_Template_Utils::get_divider_icon( $product_divider_icon, true ) ); ?>

					<div class="revx-d-flex revx-item-center revx-justify-center revx-flex-column">
						<div
							class="revx-product-seconds <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'productCountdownDigit' ) ); ?> revx-countdown-digit-container"
						>
							00
						</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'productCountdownLabel', '', 'revx-countdown-digit-label', '', 'secondLabel', '' ) ); ?>
					</div>
				</div>
			</div>
		<?php
	} elseif ( is_shop() && isset( $campaign['placement_settings']['shop_page'] ) && ! empty( $campaign['placement_settings']['shop_page'] ) && 'yes' === $campaign['placement_settings']['shop_page']['status'] ) {
		$campaign_subheading = $campaign['banner_subheading'] ?? $campaign['builder']['shopSubheading']['text'];
		?>
				<div
					class="revx-shop-cdt-container <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'ShopCountdownContainer' ) ); ?> revx-countdown-timer-wrapper revx-frontend revx-left-align revx-flex-column inpage"
				>
					<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'shopSubheading', $campaign_subheading ) ); ?>
					<div
						class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'shopDigitContainer' ) ); ?> revx-countdown-digit-wrapper revx-left-align"
					>
						<div class="revx-d-flex revx-item-center">
								<div class="revx-shop-days">00</div>
								<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'shopCountdownLabel', '', 'revx-countdown-digit-label', '', 'dayLabel', 'shopDigitContainer' ) ); ?>
							</div>
							<?php echo wp_kses_post( Revenue_Template_Utils::get_divider_icon( $shop_divider_icon, false ) ); ?>

							<div class="revx-d-flex revx-item-center">
								<div class="revx-shop-hours">00</div>
								<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'shopCountdownLabel', '', 'revx-countdown-digit-label', '', 'hourLabel', 'shopDigitContainer' ) ); ?>
							</div>
							<?php echo wp_kses_post( Revenue_Template_Utils::get_divider_icon( $shop_divider_icon, false ) ); ?>

							<div class="revx-d-flex revx-item-center">
								<div class="revx-shop-minutes">00</div>
								<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'shopCountdownLabel', '', 'revx-countdown-digit-label', '', 'minuteLabel', 'shopDigitContainer' ) ); ?>
							</div>
							<?php echo wp_kses_post( Revenue_Template_Utils::get_divider_icon( $shop_divider_icon, false ) ); ?>

							<div class="revx-d-flex revx-item-center">
								<div class="revx-shop-seconds">00</div>
								<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'shopCountdownLabel', '', 'revx-countdown-digit-label', '', 'secondLabel', 'shopDigitContainer' ) ); ?>
							</div>
					</div>
					<?php
					if ( 'yes' === $progressbar_enable_shop ) {
						echo wp_kses( Revenue_Template_Utils::render_progressbar( $template_data, 'shopCampaignProgressbar', $progress, $campaign ), revenue()->get_allowed_tag() ); //phpcs:ignore
					}
					?>
				</div>

		<?php


	}
	?>
	<input
		type="hidden"
		name="<?php echo esc_attr( 'revx-countdown-data-' . $campaign['id'] ); ?>"
		value="<?php echo esc_html(
			htmlspecialchars(
				wp_json_encode( $this->count_down_localize_data( $campaign ) )
			)
		); ?>"
	/>
</div>
<?php
$output = ob_get_clean();

// Output content based on display style.
switch ( $display_type ) {
	case 'inpage':
		Revenue_Template_Utils::inpage_container( $campaign, $output );
		if ( $output ) {
			return;
		}
		break;


	default:
		break;
}

$output = '';

if ( $is_all_page_enable ) {
	ob_start();

	$campaign_entire_heading    = $campaign['banner_heading'] ?? $campaign['builder']['entireHeading']['text'];
	$campaign_entire_subheading = $campaign['banner_subheading'] ?? $campaign['builder']['entireSubheading']['text'];
	?>
	<div 
		data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>" 
		class="revx-countdown-timer-p-wrapper <?php echo esc_attr( $device_manager_class ); ?>"
	>
		<div class="revx-banner-cdt-container <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'EntireCountdownContainer' ) ); ?> revx-campaign-<?php echo esc_attr( $display_type ); ?> <?php echo esc_attr( $display_type ); ?> revx-countdown-timer-wrapper all_page revx-frontend">
			<div class="revx-countdown-timer-container">
				<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'entireHeading', $campaign_entire_heading, '', 'max-width: 20rem;' ) ); ?>  
				<div
					data-animation-duration="<?php echo esc_attr( $animation_duration ); ?>"
					data-animation-delay="<?php echo esc_attr( $animation_delay ); ?>"
					data-animate-campaign="countdown_timer" <?php // for use in jquery ?>
					class="
						<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'entireDigitContainer' ) ); ?> 
						revx-countdown-digit-wrapper <?php echo esc_attr( $animation_class ); ?>
					"
					style="<?php echo esc_attr( $animation_style ); ?>"
				>
					<div class="revx-d-flex revx-item-center revx-justify-center revx-flex-column">
						<div
							class="revx-banner-days <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'entireCountdownDigit' ) ); ?> revx-countdown-digit-container"
						>
							00
						</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'entireCountdownLabel', '', 'revx-countdown-digit-label', '', 'dayLabel', '' ) ); ?>
					</div>
					<?php echo wp_kses_post( Revenue_Template_Utils::get_divider_icon( $entire_divider_icon, true ) ); ?>

					<div class="revx-d-flex revx-item-center revx-justify-center revx-flex-column">
						<div
							class="revx-banner-hours <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'entireCountdownDigit' ) ); ?> revx-countdown-digit-container"
						>
							00
						</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'entireCountdownLabel', '', 'revx-countdown-digit-label', '', 'hourLabel', '' ) ); ?>
					</div>
					<?php echo wp_kses_post( Revenue_Template_Utils::get_divider_icon( $entire_divider_icon, true ) ); ?>

					<div class="revx-d-flex revx-item-center revx-justify-center revx-flex-column">
						<div
							class="revx-banner-minutes <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'entireCountdownDigit' ) ); ?> revx-countdown-digit-container"
						>
							00
						</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'entireCountdownLabel', '', 'revx-countdown-digit-label', '', 'minuteLabel', '' ) ); ?>
					</div>
					<?php echo wp_kses_post( Revenue_Template_Utils::get_divider_icon( $entire_divider_icon, true ) ); ?>

					<div class="revx-d-flex revx-item-center revx-justify-center revx-flex-column">
						<div
							class="revx-banner-seconds <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'entireCountdownDigit' ) ); ?> revx-countdown-digit-container"
						>
							00
						</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'entireCountdownLabel', '', 'revx-countdown-digit-label', '', 'secondLabel', '' ) ); ?>
					</div>
				</div>
				<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'entireSubheading', $campaign_entire_subheading, '', 'max-width: 24rem;' ) ); ?>
				<?php
				if ( 'yes' === $action_enable && 'addCtaAction' === $action_type ) {
					echo wp_kses_post( Revenue_Template_Utils::render_link( $template_data, 'shopNowButton', $cta_link, '_blank' ) );
				}
				if ( 'yes' === $is_close_button_enable ) {
					echo wp_kses_post( Revenue_Template_Utils::render_button_close( $template_data, 'closeIcon' ) );
				}
				?>
			</div>
			<?php if ( 'yes' === $action_enable && 'makeFullAction' === $action_type ) { ?>
				<a class="revx-full-bar-link" class="revx-full-bar-link" href="<?php echo esc_url( $action_link ); ?>">Shop Now</a>
			<?php } ?>
		</div>
	</div>
	<?php
}
?>
</div>
<input type="hidden" name="<?php echo esc_attr( 'revx-countdown-data-' . $campaign['id'] ); ?>" value="<?php echo esc_html( htmlspecialchars( wp_json_encode( $this->count_down_localize_data( $campaign ) ) ) ); ?>" />
<?php
$output = ob_get_clean();

// Output content based on display style.
switch ( $display_type ) {
	case 'top':
		Revenue_Template_Utils::inpage_container( $campaign, $output );
		break;
	case 'bottom':
		Revenue_Template_Utils::inpage_container( $campaign, $output );
		break;

	default:
		break;
}
