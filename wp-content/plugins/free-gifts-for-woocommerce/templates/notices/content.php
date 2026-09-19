<?php
/**
 * This template displays the notice content.
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/notices/content.php
 *
 * To maintain compatibility, Free Gifts for WooCommerce will update the template files and you have to copy the updated files to your theme.
 *
 * @since 10.4.0
 * @var $icon_url contains the icon URL
 * @var $notice Notice
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!$notice) {
	return;
}
?>
<span class='fgf-notice-content-wrapper'>
	<?php if ($icon_url) : ?>
		<span class='fgf-notice-icon'>
			<img src='<?php echo esc_url($icon_url); ?>'/>
		</span>
	<?php endif; ?>

	<span class='fgf-notice-content'><?php echo wc_kses_notice($notice); ?></span>
</span>
<?php
