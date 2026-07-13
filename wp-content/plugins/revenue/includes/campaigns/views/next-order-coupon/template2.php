<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Coupon Discount inpage Template
 *
 * This file handles the display of Coupon discount offers in a inpage container.
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
$display_type       = revenue()->get_placement_settings( $campaign['id'], $placement, 'display_style' ) ?? 'inpage';
$view_mode          = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
$template_data      = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
$placement_settings = revenue()->get_placement_settings( $campaign['id'] );
// Coupon Code.
if ( is_user_logged_in() ) {
	$user_id   = get_current_user_id();
	$coupon_id = get_user_meta( $user_id, '_revx_next_order_coupon_id', true );
} else {
	$coupon_id = $this->get_guest_meta( '_revx_next_order_coupon_id' );
}
$coupon      = new \WC_Coupon( $coupon_id );
$button_link = ! empty( $coupon_settings['coupon_button_link'] ) ? $coupon_settings['coupon_button_link'] : wc_get_page_permalink( 'shop' );

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
<div class="revx-coupon-template-body <?php echo esc_attr( $device_manager_class ); ?>">
	<div class="revx-coupon-template-outer <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'CouponContainer' ) ); ?>" style="display: none;">
		<div
			class="revx-coupon-shape2 revx-coupon-shape2-left"
			style="border: 1px dashed var(--revx-border-color, #6c5ce7)"
		></div>
		<div
			class="revx-coupon-shape2 revx-coupon-shape2-right"
			style="border: 1px dashed var(--revx-border-color, #6c5ce7)"
		></div>
		<div
			class="revx-coupon-template-wrapper2"
			style="
				max-width: var(--revx-coupon-width, unset);
				border: 1px dashed var(--revx-border-color, #6c5ce7);
			"
		>
			<div
				class="revx-coupon-template-container2"
				style="background-color: var(--revx-coupon-bg-color, #6c5ce7)"
			>
				<div
					class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'couponContentContainer' ) ); ?> revx-coupon-template2-content"
					style="
						border-left: 2px dashed
							var(--revx-coupon-separator, #ffffff) !important;
					"
				>
					<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in Revenue_Template_Utils::render_rich_text()
					echo Revenue_Template_Utils::render_rich_text( $template_data, 'heading' );
					?>
										<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in Revenue_Template_Utils::render_rich_text()
										echo Revenue_Template_Utils::render_rich_text( $template_data, 'subHeading' );
										?>
					<div
						class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'couponButtons' ) ); ?> revx-flex-wrap"
					>
						<div
							class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'couponCodeContainer' ) ); ?> revx-coupon-template-code  revx-coupon-button"
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
						<?php
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped in Revenue_Template_Utils::render_link()
						echo Revenue_Template_Utils::render_link( $template_data, 'shopNowButton', $button_link, '_blank' );
						?>
					</div>
				</div>
				<div
					class="revx-coupon-template-svg <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'couponSVGContainer' ) ); ?>"
					data-toolbar-id="container-47"
					data-element-id="couponSVGContainer"
					aria-expanded="false"
					aria-haspopup="true"
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="100%"
						height="100%"
						fill="none"
						viewBox="0 0 94 94"
					>
						<path
							stroke="#fff"
							stroke-width="2.37"
							d="M48.957 18.604a.98.98 0 0 0-.98-.979H26.24c-4.387 0-6.58 0-8.256.854a7.83 7.83 0 0 0-3.423 3.423c-.854 1.676-.854 3.87-.854 8.256v6.146c0 .5.405.904.904.904h1.054c5.408 0 9.792 4.384 9.792 9.792s-4.384 9.792-9.792 9.792h-1.054c-.5 0-.904.404-.904.904v6.146c0 4.387 0 6.58.854 8.256a7.83 7.83 0 0 0 3.423 3.423c1.676.854 3.87.854 8.256.854h21.738a.98.98 0 0 0 .979-.98v-.978a3.917 3.917 0 0 1 7.833 0v.979c0 .54.439.979.98.979h9.987c4.387 0 6.58 0 8.256-.854a7.83 7.83 0 0 0 3.424-3.423c.853-1.676.853-3.87.853-8.256v-6.49a.88.88 0 0 0-.904-.879l-.915.027c-5.437.16-9.93-4.204-9.93-9.644 0-5.328 4.319-9.648 9.647-9.648h1.198c.5 0 .904-.404.904-.904v-6.146c0-4.387 0-6.58-.853-8.256a7.83 7.83 0 0 0-3.424-3.423c-1.675-.854-3.869-.854-8.256-.854h-9.988a.98.98 0 0 0-.979.98v.978a3.917 3.917 0 0 1-7.833 0zM56.79 35.25a3.917 3.917 0 0 0-7.833 0v3.917a3.917 3.917 0 0 0 7.833 0zm0 19.583a3.917 3.917 0 0 0-7.833 0v3.917a3.917 3.917 0 0 0 7.833 0z"
							clip-rule="evenodd"
						></path>
					</svg>
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
