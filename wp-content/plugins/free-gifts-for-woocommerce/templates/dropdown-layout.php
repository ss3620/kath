<?php
/**
 * This template displays gift products dropdown layout.
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/dropdown-layout.php
 *
 * To maintain compatibility, Free Gifts for WooCommerce will update the template files and you have to copy the updated files to your theme
 * 
 * @modified 10.6.0
 * @var $gift_products
 * @var $mode
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
	<h3><?php echo esc_html(fgf_get_gift_product_heading_label()); ?></h3>

	<div class="fgf-gift-product-wrapper fgf-gift-products-content">
		<?php if (fgf_is_dropdown_gift_product_with_image()) : ?>
			<div class='fgf-dropdown-wrapper'>
				<div class='fgf-dropdown-default-option-wrapper'>
					<input type='hidden' class='fgf-gift-product-selection' selected='selected'/>
					<a class='fgf-dropdown-option-selected'>
						<span class='fgf-dropdown-product-image'><img src='<?php echo esc_url(wc_placeholder_img_src()); ?>'></span>
						<label class='fgf-dropdown-product-name'><?php echo esc_html(fgf_get_gift_product_dropdown_default_value_label()); ?></label>
					</a>
					<span class='fgf-pointer fgf-pointer-down'></span>
				</div>
				<ul class='fgf-dropdown-options-wrapper'>

					<li class='fgf-dropdown-option-content'>
						<a class='fgf-dropdown-option'> 
							<span class='fgf-dropdown-product-image'><img src='<?php echo esc_url(wc_placeholder_img_src()); ?>'></span>
							<label class='fgf-dropdown-product-name'><?php echo esc_html(fgf_get_gift_product_dropdown_default_value_label()); ?></label>
						</a>
					</li>
					<?php
					foreach ($gift_products as $gift_product) :
						if ($gift_product['hide_add_to_cart']) {
							continue;
						}

						$product_ids = ( fgf_check_is_array($gift_product['variation_ids']) ) ? $gift_product['variation_ids'] : array( $gift_product['parent_id'] );

						foreach ($product_ids as $product_id) :
							$_product = wc_get_product($product_id);
							$buy_product_id = !empty($gift_product['buy_product_id']) ? $gift_product['buy_product_id'] : null;
							$coupon_id = !empty($gift_product['coupon_id']) ? $gift_product['coupon_id'] : null;
							?>
							<li class='fgf-dropdown-option-content' value="<?php echo esc_attr($product_id); ?>" 
								data-rule-id="<?php echo esc_attr($gift_product['rule_id']); ?>"
								data-buy-product-id="<?php echo esc_attr($buy_product_id); ?>"
								data-coupon-id="<?php echo esc_attr($coupon_id); ?>">
								<a class='fgf-dropdown-option'> 
									<span class='fgf-dropdown-product-image'><?php fgf_render_gift_product_image($_product, $product_id, 'woocommerce_thumbnail'); ?></span> 
									<label class='fgf-dropdown-product-name'><?php echo wp_kses_post(fgf_get_dropdown_gift_product_name($product_id)); ?></label>
								</a>
							</li>
							<?php
						endforeach;
					endforeach;
					?>
				</ul>
			</div>

		<?php else : ?>

			<select class="fgf-gift-product-selection">
				<option value=""><?php echo esc_html(fgf_get_gift_product_dropdown_default_value_label()); ?></option>
				<?php
				foreach ($gift_products as $gift_product) :

					if ($gift_product['hide_add_to_cart']) {
						continue;
					}

					$buy_product_id = !empty($gift_product['buy_product_id']) ? $gift_product['buy_product_id'] : null;
					$coupon_id = !empty($gift_product['coupon_id']) ? $gift_product['coupon_id'] : null;
					?>
					<?php if (fgf_check_is_array($gift_product['variation_ids'])) : ?>
						<optgroup label="<?php echo esc_attr(fgf_get_dropdown_gift_product_name($gift_product['parent_id'])); ?>">
							<?php foreach ($gift_product['variation_ids'] as $variation_id) : ?>
								<option value="<?php echo esc_attr($variation_id); ?>" 
										data-rule-id="<?php echo esc_attr($gift_product['rule_id']); ?>"
										data-buy-product-id="<?php echo esc_attr($buy_product_id); ?>"
										data-coupon-id="<?php echo esc_attr($coupon_id); ?>"><?php echo wp_kses_post(fgf_get_dropdown_gift_product_name($variation_id)); ?></option>
									<?php endforeach; ?>
						</optgroup>
					<?php else : ?>
						<option value="<?php echo esc_attr($gift_product['product_id']); ?>" 
								data-rule-id="<?php echo esc_attr($gift_product['rule_id']); ?>"
								data-buy-product-id="<?php echo esc_attr($buy_product_id); ?>"
								data-coupon-id="<?php echo esc_attr($coupon_id); ?>"><?php echo wp_kses_post(fgf_get_dropdown_gift_product_name($gift_product['product_id'])); ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
			</select>
		<?php endif; ?>

		<?php if (fgf_show_dropdown_add_to_cart_button()) : ?>
			<button class="button fgf-add-gift-product"><?php echo esc_html(fgf_get_gift_product_add_to_cart_button_label()); ?></button>
		<?php endif; ?>
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
