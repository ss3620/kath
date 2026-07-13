<?php
/**
 * HTML- Buy product brands fields.
 * 
 * @since 11.3.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-field-wrapper'>
	<span class='fgf-field-title'>
		<label><?php esc_html_e('Select Brands', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
		<?php fgf_wc_help_tip(__('The products from the selected brands will be displayed to the user', 'free-gifts-for-woocommerce')); ?>
	</span>
	<span class='fgf-field'>
		<select class='fgf-buy-brands fgf_select2 fgf_bogo_rule_type fgf_rule_type fgf-buy-product-selection-type-field fgf-buy-product-selection-type-field-brand' name='fgf_rule[fgf_buy_product_brands][]' multiple='multiple'>
			<?php
			foreach ($brands as $brand_id => $brand_name) :
				$selected = ( in_array($brand_id, $selected_brand_ids) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr($brand_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($brand_name); ?></option>
			<?php endforeach; ?>
		</select>
	</span>
</div>


<div class='fgf-field-wrapper'>
	<span class='fgf-field-title'>
		<label><?php esc_html_e('Buy Product condition applicable when', 'free-gifts-for-woocommerce'); ?><span class="required">*</span>
		</label>
	</span>
	<span class='fgf-field'>
		<select name="fgf_rule[fgf_buy_brand_consider_type]" class = "fgf_bogo_rule_type fgf_buy_brands fgf_rule_type fgf-buy-product-selection-type-field fgf-buy-product-selection-type-field-brand">
			<option value="1" <?php selected($brand_consider_type, '1'); ?>><?php esc_html_e('Any one of the product(s) from the selected category must be in the cart', 'free-gifts-for-woocommerce'); ?></option>
			<option value="2" <?php selected($brand_consider_type, '2'); ?>><?php esc_html_e('One Product from each selected category must be in the cart', 'free-gifts-for-woocommerce'); ?></option>
		</select>
	</span>
</div>

<div class='fgf-field-wrapper'>
	<span class='fgf-field-title'>
		<label><?php esc_html_e('Buy Quantity Calculation Based on', 'free-gifts-for-woocommerce'); ?><span class="required">*</span>
			<?php fgf_wc_help_tip(__("Same Product's Quantity: Quantity must match for each product to receive a free gift. Total Quantity of the Selected Brand's Products: Quantity must match either for each product or quantity of products that belong to the selected brand should match to receive a free gift. Product with Least quantity from the selected brand: The quantity of the product which is least from the products that belong to the selected brand will be considered for awarding the free gift.", 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
		</label>
	</span>
	<span class='fgf-field'>
		<select name="fgf_rule[fgf_buy_brand_quantity_consider_type]" class = "fgf_bogo_rule_type fgf_buy_brands fgf_rule_type fgf-buy-product-selection-type-field fgf-buy-product-selection-type-field-brand">
			<option value="1" <?php selected($brand_type, '1'); ?>><?php esc_html_e("Same Product's Quantity", 'free-gifts-for-woocommerce'); ?></option>
			<option value="2" <?php selected($brand_type, '2'); ?>><?php esc_html_e("Total Quantity of the Selected Brand's Products", 'free-gifts-for-woocommerce'); ?></option>
			<option value="3" <?php selected($brand_type, '3'); ?>><?php esc_html_e('Product with least quantity from the selected brand', 'free-gifts-for-woocommerce'); ?></option>
		</select>
	</span>
</div>
<?php
