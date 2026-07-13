<?php
/**
 * This template displays contents inside carousel layout
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/carousel-layout.php
 *
 * To maintain compatibility, Free Gifts for WooCommerce will update the template files and you have to copy the updated files to your theme
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class="fgf_gift_products_wrapper" id='fgf_gift_products_wrapper'>
	<?php
	/**
	 * This hook is used to display the extra content before gift products content.
	 *
	 * @since 1.0
	 */
	do_action('fgf_before_gift_products_content');
	?>
	<h3><?php echo esc_html(get_option('fgf_settings_free_gift_heading_label')); ?></h3>
	<div class="fgf-gift-products-content">
		<div class="fgf-owl-carousel-items owl-carousel">

			<?php
			foreach ($gift_products as $key => $gift_product) :

				$link_classes = array( 'fgf_add_to_cart_link' );
				if ($gift_product['hide_add_to_cart']) {
					$link_classes[] = 'fgf_disable_links';
				}

				$_product = wc_get_product($gift_product['parent_id']);
				$buy_product_id = !empty($gift_product['buy_product_id']) ? $gift_product['buy_product_id'] : null;
				$coupon_id = !empty($gift_product['coupon_id']) ? $gift_product['coupon_id'] : null;
				?>

				<div class="fgf-owl-carousel-item fgf-gift-product-item fgf-owl-carousel-item<?php echo esc_attr($key); ?>">

					<span class='fgf-product-image'><?php fgf_render_gift_product_image($_product, $gift_product['variation_ids']); ?></span>
					<h5><?php fgf_render_product_name($_product); ?></h5>
					<div class='fgf-gift-product-add-to-cart-actions'>
						<?php
						if (fgf_is_valid_to_show_gift_product_quantity_field($gift_product)) :
							?>
							<span class='fgf-gift-product-qty-container'>
								<input class='fgf-gift-product-qty' type='number' size='5' min='1' max='<?php echo esc_attr($gift_product['qty']); ?>' value='1'/>
							</span>
						<?php endif; ?>

						<span class="<?php echo esc_attr(implode(' ', $link_classes)); ?>">

							<?php if (fgf_check_is_array($gift_product['variation_ids'])) : ?>
								<select class="fgf-product-variations"
										data-rule_id="<?php echo esc_attr($gift_product['rule_id']); ?>"
										data-buy_product_id="<?php echo esc_attr($buy_product_id); ?>"
										data-coupon_id="<?php echo esc_attr($coupon_id); ?>">
											<?php
											foreach ($gift_product['variation_ids'] as $variation_id) :
												$_variation = wc_get_product($variation_id);
												?>
										<option value="<?php echo esc_attr($_variation->get_id()); ?>" data-image='<?php echo esc_attr($_variation->get_image()); ?>'><?php echo esc_html(fgf_render_product_name($_variation, false, false)); ?></option>
									<?php endforeach; ?>
								</select>
							<?php endif; ?>

							<a class="<?php echo esc_attr(implode(' ', fgf_get_gift_product_add_to_cart_classes())); ?>"
							   data-product_id="<?php echo esc_attr($gift_product['product_id']); ?>"
							   data-rule_id="<?php echo esc_attr($gift_product['rule_id']); ?>"
							   data-buy_product_id="<?php echo esc_attr($buy_product_id); ?>"
							   data-coupon_id="<?php echo esc_attr($coupon_id); ?>"
							   href="<?php echo esc_url(fgf_get_gift_product_add_to_cart_url($gift_product)); ?>">
								   <?php echo esc_html(get_option('fgf_settings_free_gift_add_to_cart_button_label')); ?>
							</a>
						</span>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	/**
	 * This hook is used to display the extra content after gift products content.
	 *
	 * @since 1.0
	 */
	do_action('fgf_after_gift_products_content');
	?>
	<input type="hidden" id="fgf_gift_products_type" value='<?php echo esc_attr($mode); ?>'>
</div>
<?php

