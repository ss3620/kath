<?php
/**
 * HTML- Subtotal Gift brands search fields.
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
		<select class='fgf-subtotal-gift-brands fgf_select2 fgf_rule_type fgf-subtotal-rule-type fgf-subtotal-manual-rule-type fgf-subtotal-gift-selection-type-brand fgf-subtotal-gift-selection-type-field' name='fgf_rule[fgf_subtotal_gift_brands][]' multiple='multiple'>
			<?php
			foreach ($brands as $brand_id => $brand_name) :
				$selected = ( in_array($brand_id, $selected_brand_ids) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr($brand_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($brand_name); ?></option>
			<?php endforeach; ?>
		</select>
	</span>
</div>
<?php
