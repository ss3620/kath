<?php
/**
 * Product brand filters data.
 * 
 * @since 9.4.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-field-wrapper'>
	<span class='fgf-field-title'>
		<label><?php esc_html_e('Applicable when', 'free-gifts-for-woocommerce'); ?>
			<?php fgf_wc_help_tip(__('This option provides additional control on when to award the Free Gifts.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
		</label>
	</span>
	<span class='fgf-field'>
		<select name="fgf_rule[fgf_applicable_brands_type]" class="fgf_product_filter fgf_applicable_brands_type">
			<?php foreach (fgf_rule_applicable_brands_filter_options() as $filter_id => $filter_name) : ?>
				<option value="<?php echo esc_attr($filter_id); ?>" <?php selected($applicable_brands_type, $filter_id); ?>><?php echo esc_html($filter_name); ?></option>
			<?php endforeach; ?>
		</select>
	</span>
</div>

<div class='fgf-field-wrapper'>
	<span class='fgf-field-title'>
		<label><?php esc_html_e('Select Brands', 'free-gifts-for-woocommerce'); ?></label>
	</span>
	<span class='fgf-field'>
		<select class="fgf_include_brands fgf_product_filter fgf_select2" name="fgf_rule[fgf_include_brands][]" multiple="multiple">
			<?php
			foreach ($brands as $brand_id => $brand_name) :
				$selected = ( in_array($brand_id, $include_brands) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr($brand_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($brand_name); ?></option>
			<?php endforeach; ?>
		</select>
	</span>
</div>

<div class='fgf-field-wrapper'>
	<span class='fgf-field-title'>
		<label><?php esc_html_e('Minimum Quantity', 'free-gifts-for-woocommerce'); ?>
			<?php fgf_wc_help_tip(__("The user's cart must contain the minimum quantity mentioned in this option which is the sum of the product(s) quantity that belongs to the selected categories.", 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
		</label>
	</span>
	<span class='fgf-field'>
		<input type="number" class="fgf_product_filter fgf-brand-product-count" name="fgf_rule[fgf_brand_product_count]" min="1" value="<?php echo esc_attr($brand_product_count); ?>"/>
	</span>
</div>

<div class='fgf-field-wrapper'>
	<span class='fgf-field-title'>
		<label><?php esc_html_e('Select Brands', 'free-gifts-for-woocommerce'); ?></label>
	</span>
	<span class='fgf-field'>
		<select class="fgf_exclude_brands fgf_product_filter fgf_select2" name="fgf_rule[fgf_exclude_brands][]" multiple="multiple">
			<?php
			foreach ($brands as $brand_id => $brand_name) :
				$selected = ( in_array($brand_id, $exclude_brands) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr($brand_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($brand_name); ?></option>
			<?php endforeach; ?>
		</select>
	</span>
</div>
<?php
