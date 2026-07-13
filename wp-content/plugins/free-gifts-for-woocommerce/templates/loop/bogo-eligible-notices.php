<?php
/**
 * This template displays the BOGO eligible notices content on the archives page.
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/loop/bogo-eligible-notices.php
 *
 * To maintain compatibility, Free Gifts for WooCommerce will update the template files and you have to copy the updated files to your theme.
 *
 * @since 11.9.0
 * @var array $notices
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-bogo-eligible-notices-wrapper fgf-bogo-loop-page-eligible-notices-wrapper'>
	<?php foreach ($notices as $notice) : ?>
		<div class='fgf-bogo-eligible-notice'><?php echo wc_kses_notice($notice); ?></div>
	<?php endforeach; ?>
</div>
<?php
