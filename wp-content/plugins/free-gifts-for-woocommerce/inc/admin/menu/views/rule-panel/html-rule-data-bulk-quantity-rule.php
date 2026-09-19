<?php
/**
 * General - Bulk Quantity Rule
 * 
 * @since 11.4.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-rule-bulk-quantity-rule-wrapper fgf-bulk-rule-wrapper' data-rule_id='{{data.rule_id}}'>
	<div class='fgf-rule-bulk-field'>
		<input type='number' name='fgf_rule[fgf_bulk_quantity_rules][{{data.rule_id}}][minimum]' class='fgf-rule-bulk-min-qunaity-field' min='1' step='1' value='1' placeholder="<?php esc_attr_e('From', 'free-gifts-for-woocommerce'); ?>"/>
	</div>
	<div class='fgf-rule-bulk-field'>
		<input type='number'name='fgf_rule[fgf_bulk_quantity_rules][{{data.rule_id}}][maximum]' class='fgf-rule-bulk-max-qunaity-field' min='1' step='1' value='' placeholder="<?php esc_attr_e('To', 'free-gifts-for-woocommerce'); ?>"/>
	</div>
	<div class='fgf-rule-bulk-field'>
		<input type='number' name='fgf_rule[fgf_bulk_quantity_rules][{{data.rule_id}}][gift_quantity]' class='fgf-rule-bulk-gift-quantity-field' min='1' step='1' value='1' placeholder="<?php esc_attr_e('Get', 'free-gifts-for-woocommerce'); ?>"/>
	</div>
	<div class='fgf-rule-bulk-field'>
		<select name='fgf_rule[fgf_bulk_quantity_rules][{{data.rule_id}}][gift_type]' class = 'fgf-rule-bulk-gift-selection-type-field'>
			<?php foreach (fgf_get_gift_product_selection_types() as $type_id => $type_name) : ?>
				<option value='<?php echo esc_attr($type_id); ?>'><?php echo esc_html($type_name); ?></option>
			<?php endforeach; ?> 
		</select>
	</div>
	<div class='fgf-rule-bulk-field'>
		<?php
		fgf_select2_html(array(
			'class' => 'fgf-rule-bulk-gift-products-field fgf-bulk-gift-type-field fgf-bulk-gift-type-1',
			'name' => 'fgf_rule[fgf_bulk_quantity_rules][{{data.rule_id}}][gift_products]',
			'list_type' => 'products',
			'action' => 'fgf_json_search_products_and_variations',
			'display_stock' => 'yes',
			'placeholder' => __('Search a Product', 'free-gifts-for-woocommerce'),
			'options' => array(),
		));
		?>
	</div>
	<div class='fgf-rule-bulk-field'>
		<select class='fgf_select2 fgf-rule-bulk-gift-category-field fgf-bulk-gift-type-field fgf-bulk-gift-type-2' name='fgf_rule[fgf_bulk_quantity_rules][{{data.rule_id}}][gift_categories][]' multiple='multiple'>
			<?php
			foreach (fgf_get_wc_categories() as $category_id => $category_name) :
				?>
				<option value="<?php echo esc_attr($category_id); ?>"><?php echo esc_html($category_name); ?></option>
			<?php endforeach; ?>
		</select>
	</div>
	<?php
	/**
	 * This hook is used to display extra content after bulk pricing rule.
	 * 
	 * @since 11.4.0
	 */
	do_action('fgf_bulk_rule_settings', 'fgf_bulk_quantity_rules', '{{data.rule_id}}');
	?>
	<div class='fgf-rule-bulk-field'>
		<button type='button' class='fgf-remove-bulk-quantity-rule fgf-remove-bulk-rule''><?php esc_html_e('Remove Rule', 'free-gifts-for-woocommerce'); ?></button>
	</div>
</div>
<?php
