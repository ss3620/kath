<?php
/**
 * Progress bar block preview template
 * 
 * @since 11.0.0
 * */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-progress-bar-block-preview_wrapper'>
	<h3 class='fgf-progress-bar-heading-label'><?php echo wp_kses_post(fgf_get_progress_bar_heading_label()); ?></h3>
	<div class='fgf-progress-bar-above-details-wrapper'>
		<span class='fgf-progress-bar-start'>0</span>
		<span class='fgf-progress-bar-end'>5</span>
	</div>

	<div class='fgf-progress-bar-outer-wrapper'>
		<span style="width:0%;clear: both;">
			<span class='fgf-progress-bar-fill'></span>
		</span>
	</div>

	<div class='fgf-progress-bar-below-details-wrapper'>
		<span class='fgf-progress-bar-start'><?php echo wp_kses_post(str_replace('[added_gift_count]', 0, get_option('fgf_settings_progress_bar_added_gift_count_label'))); ?></span>
		<span class='fgf-progress-bar-end'><?php echo wp_kses_post(str_replace('[remaining_gift_count]', 5, get_option('fgf_settings_progress_bar_remaining_gift_count_label'))); ?></span>
	</div>
</div>
<?php
