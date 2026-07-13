<?php
/**
 * Coupon Discount inpage Template
 *
 * This file handles the display of Coupon discount offers in a inpage container.
 *
 * @package    Revenue
 * @subpackage Templates
 * @version    1.0.0
 */

//phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar

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
$display_type  = 'inpage';
$template_data = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
$campaign      = revenue()->get_campaign_data( $campaign['id'] );

// Coupon Settings .
$coupon_settings = $campaign['revx_next_order_coupon'];

// Coupon Code.
if ( is_user_logged_in() ) {
	$user_id   = get_current_user_id();
	$coupon_id = get_user_meta( $user_id, '_revx_next_order_coupon_id', true );
} else {
	$coupon_id = $this->get_guest_meta( '_revx_next_order_coupon_id' );
}
$coupon          = new \WC_Coupon( $coupon_id );
$button_link     = ! empty( $coupon_settings['coupon_button_link'] ) ? $coupon_settings['coupon_button_link'] : wc_get_page_permalink( 'shop' );
$discount_type   = $coupon->get_discount_type();
$dicsount_amount = $coupon->get_amount();
$expiry_date     = $coupon->get_date_expires(); // might need to handle time cases.


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
$save_data = '';
if ( 'percent' === $discount_type ) {
	/* translators: %s: discount percentage value without the percent sign. */
	$save_data = sprintf( __( '%s%%', 'revenue' ), $dicsount_amount );
} else {
	$save_data = wc_price( $dicsount_amount );
}
$offer_text = $template_data['heading']['text'] ?? '';
$message    = str_replace( '{discount_value}', $save_data, $offer_text );

ob_start();
?>
<div class="revx-coupon-template-body <?php echo esc_attr( $device_manager_class ); ?>">
	<div class="revx-coupon-template-outer <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'CouponContainer' ) ); ?>" style="display: none;">
		<div
			class="revx-coupon-shape1 revx-coupon-shape1-left"
			style="border: 1px dashed var(--revx-border-color, #6c5ce7)"
		></div>
		<div
			class="revx-coupon-shape1 revx-coupon-shape1-right"
			style="border: 1px dashed var(--revx-border-color, #6c5ce7)"
		></div>
		<div
			class="revx-coupon-template-wrapper1"
			style="
				max-width: var(--revx-coupon-width, unset);
				border: 1px dashed var(--revx-border-color, #6c5ce7);
			"
		>
			<div
				class="revx-coupon-template-container1"
				style="background-color: var(--revx-coupon-bg-color, #6c5ce7)"
			>
				<div
					class="revx-coupon-template-tag <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'couponTag' ) ); ?>"
					style="
						font-size: var(--revx-coupon-font-size, 25px);
						color: var(--revx-coupon-bg-color, #6c5ce7);
						text-shadow: 0px 0px 2px var(--revx-coupon-color, #cac2ff);
					"
				>
					COUPON
				</div>
				<div
					class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'couponContentContainer' ) ); ?> revx-coupon-template1-content"
				>
					<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'heading', $message ) ); ?> 
					<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'subHeading' ) ); ?>
					<div
						class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'couponButtons' ) ); ?> revx-flex-wrap"
					>
						<div
							class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'couponCodeContainer' ) ); ?> revx-coupon-template-code revx-coupon-button"
						>
							<div class="revx-coupon-value"><?php echo esc_html( $coupon->get_code() ); ?></div>
							<div class="revx-coupon-copy-btn revx-lh-0">
								<svg
									width="1rem"
									height="1rem"
									viewBox="0 0 16 16"
									fill="none"
									xmlns="http://www.w3.org/2000/svg"
								>
									<g clip-path="url(#clip0_1180_32578)">
										<path
											d="M13.3333 6H7.33333C6.59695 6 6 6.59695 6 7.33333V13.3333C6 14.0697 6.59695 14.6667 7.33333 14.6667H13.3333C14.0697 14.6667 14.6667 14.0697 14.6667 13.3333V7.33333C14.6667 6.59695 14.0697 6 13.3333 6Z"
											stroke="currentColor"
											stroke-width="1.2"
											stroke-linecap="round"
											stroke-linejoin="round"
										></path>
										<path
											d="M3.33301 9.99967H2.66634C2.31272 9.99967 1.97358 9.8592 1.72353 9.60915C1.47348 9.3591 1.33301 9.01996 1.33301 8.66634V2.66634C1.33301 2.31272 1.47348 1.97358 1.72353 1.72353C1.97358 1.47348 2.31272 1.33301 2.66634 1.33301H8.66634C9.01996 1.33301 9.3591 1.47348 9.60915 1.72353C9.8592 1.97358 9.99967 2.31272 9.99967 2.66634V3.33301"
											stroke="currentColor"
											stroke-width="1.2"
											stroke-linecap="round"
											stroke-linejoin="round"
										></path>
									</g>
									<defs>
										<clipPath id="clip0_1180_32578">
											<rect width="16" height="16" fill="currentColor"></rect>
										</clipPath>
									</defs>
								</svg>
							</div>
						</div>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_link( $template_data, 'shopNowButton', $button_link, '_blank' ) ); ?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<?php
$output = ob_get_clean();

// Output content based on display style.
switch ( $display_type ) {
	case 'inpage':
		Revenue_Template_Utils::inpage_container( $campaign, $output );
		break;
}
