<?php
/**
 * This template is used to displaying the progress bar of manual gift products.
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/progress-bar.php
 *
 * To maintain compatibility, Free Gifts for WooCommerce will update the template files and you have to copy the updated files to your theme
 * 
 * @since 9.8.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-progress-bar-wrapper'>
	<p class='fgf-progress-bar-heading-label'><?php echo wp_kses_post(fgf_get_progress_bar_heading_label()); ?></p>
	<div class='fgf-progress-bar-above-details-wrapper'>
		<span class='fgf-progress-bar-start'>0</span>
		<span class='fgf-progress-bar-end'><?php echo wp_kses_post(fgf_get_progress_bar_maximum_gift_count_label()); ?></span>
	</div>

	<div class='fgf-progress-bar-outer-wrapper'>
		<span style="width:<?php echo esc_attr(fgf_get_manual_gift_products_progress_bar_width()); ?>%;clear: both;">
			<span class='fgf-progress-bar-fill'></span>
		</span>
	</div>

	<div class='fgf-progress-bar-below-details-wrapper'>
		<span class='fgf-progress-bar-start'><?php echo wp_kses_post(fgf_get_progress_bar_added_gift_count_label()); ?></span>
		<span class='fgf-progress-bar-end'><?php echo wp_kses_post(fgf_get_progress_bar_remaining_gift_count_label()); ?></span>
	</div>
</div>
<?php
