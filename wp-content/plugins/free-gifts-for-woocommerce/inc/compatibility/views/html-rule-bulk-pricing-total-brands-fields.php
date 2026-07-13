<?php
/**
 * HTML- Bulk pricing brands search fields.
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
		<select class='fgf_select2 fgf-bulk-pricing-total-type-field fgf-bulk-pricing-total-type-brands' name='fgf_rule[fgf_bulk_pricng_total_brands][]' multiple='multiple'>
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
