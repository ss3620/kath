<?php
/**
 * This template displays shortcode content for the BOGO eligible notices.
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/shortcodes/bogo-eligible-notices.php
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
<div class='fgf-bogo-eligible-notices-wrapper fgf-bogo-single-product-eligible-notices-wrapper fgf-bogo-shortcode-eligible-notices-wrapper'>
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
