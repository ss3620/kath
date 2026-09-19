<?php
/**
 * HTML- Gift brands search fields.
 * 
 * @since 10.8.0
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
		<select class='fgf-gift-selection-type-field fgf_rule_type fgf-gift-brands fgf-gift-selection-type-brand fgf_select2' name='fgf_rule[fgf_gift_brands][]' multiple='multiple'>
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
