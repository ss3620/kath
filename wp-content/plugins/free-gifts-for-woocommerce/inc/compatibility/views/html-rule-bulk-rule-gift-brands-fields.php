<?php
/**
 * HTML- Bulk rule gift brands search fields.
 * 
 * @since 12.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-rule-bulk-field'>
		<select class='fgf-bulk-gift-type-field fgf-bulk-gift-type-brand fgf_select2' name="fgf_rule[<?php echo esc_attr($rule_key); ?>][<?php echo esc_attr($rule_id); ?>][gift_brands][]" multiple='multiple'>
			<?php
			foreach ($brands as $brand_id => $brand_name) :
				$selected = ( in_array($brand_id, $selected_brand_ids) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr($brand_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($brand_name); ?></option>
			<?php endforeach; ?>
		</select>
</div>
<?php
