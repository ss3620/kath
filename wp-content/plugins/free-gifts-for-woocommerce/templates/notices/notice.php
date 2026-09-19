<?php
/**
 * This template displays the notice.
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/notices/notice.php
 *
 * To maintain compatibility, Free Gifts for WooCommerce will update the template files and you have to copy the updated files to your theme
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!$notice) {
	return;
}
?>
<div class="fgf-success-info fgf-notice woocommerce-info" role="alert"><?php echo wc_kses_notice($notice['notice']); ?></div>
<?php
