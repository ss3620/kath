<?php
/**
 * Criteria brand total fields.
 * 
 * @since 9.4.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-field-wrapper'>
	<span class='fgf-field-title'>
		<label><?php esc_html_e('Select a Brand', 'free-gifts-for-woocommerce'); ?><span class="required">*</span></label>
	</span>
	<span class='fgf-field'>
		<select class="fgf_select2 fgf-rule-cart-total-type-fields fgf-rule-cart-total-type-brands" name="fgf_rule[fgf_cart_brands][]" multiple="multiple">
			<?php
			foreach ($brands as $brand_id => $brand_name) :
				$selected = ( in_array($brand_id, $brand_ids) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr($brand_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($brand_name); ?></option>
			<?php endforeach; ?>
		</select>
	</span>
</div>
<?php
