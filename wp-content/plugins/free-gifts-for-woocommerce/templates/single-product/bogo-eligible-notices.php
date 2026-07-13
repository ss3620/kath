<?php
/**
 * This template displays the BOGO eligible notices content on the single product page.
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/single-product/bogo-eligible-notices.php
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
<div class='fgf-bogo-eligible-notices-wrapper fgf-bogo-single-product-eligible-notices-wrapper'>
	<?php
	if (fgf_check_is_array($notices)) :
		foreach ($notices as $notice) :
			?>
		<div class='fgf-bogo-eligible-notice'><?php echo wc_kses_notice($notice); ?></div>
			<?php
	endforeach; 
	endif;
	?>
</div>
<?php
