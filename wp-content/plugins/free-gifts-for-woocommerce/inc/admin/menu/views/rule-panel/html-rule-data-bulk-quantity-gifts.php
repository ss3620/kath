<?php
/**
 * General - Bulk Quantity Gifts
 * 
 * @since 12.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-rule-bulk-quantity-gifts-fields-wrapper fgf-rule-general-fields-wrapper'>
	<?php
	/**
	 * This hook is used to do extra action before rule bulk quantity gifts settings.
	 *
	 * @since 12.0.0
	 */
	do_action('fgf_before_rule_bulk_quantity_gifts_settings', $rule_data);
	?>
	<div class='fgf-rule-fields-section'>
		<h2><?php esc_html_e('Bulk Quantity Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Rule Consider Type', 'free-gifts-for-woocommerce'); ?></label>
			</span>
			<span class='fgf-field'>
				<select class='fgf-bulk-quantity-rules-consider-type' name='fgf_rule[fgf_bulk_quantity_rules_consider_type]'>
					<option value='1'<?php selected(1, $rule_data['fgf_bulk_quantity_rules_consider_type']); ?>><?php esc_html_e('First Matched Rule', 'free-gifts-for-woocommerce'); ?></option>
					<option value='2'<?php selected(2, $rule_data['fgf_bulk_quantity_rules_consider_type']); ?>><?php esc_html_e('All Valid Rule', 'free-gifts-for-woocommerce'); ?></option>
				</select>
			</span>
		</div>
	</div>
	<h2><?php esc_html_e('Bulk Quantity Rules', 'free-gifts-for-woocommerce'); ?></h2>
	<div class='fgf-rule-bulk-quantity-rules-wrapper fgf-bulk-rules-wrapper'>
		<button type='button' class='fgf-add-bulk-quantity-rule fgf-add-bulk-rule' data-template_name='fgf-bulk-quantity-rule'><?php esc_html_e('Add Rule', 'free-gifts-for-woocommerce'); ?></button>
		<?php
		if (fgf_check_is_array($rule_data['fgf_bulk_quantity_rules'])) :
			foreach ($rule_data['fgf_bulk_quantity_rules'] as $bulk_quantity_rule_id => $bulk_quantity_rule) :
				$bulk_quantity_rule = fgf_parsed_bulk_quantity_rules($bulk_quantity_rule);
				$rule_name = "fgf_rule[fgf_bulk_quantity_rules][{$bulk_quantity_rule_id}]";
				?>
				<div class='fgf-rule-bulk-quantity-rule-wrapper fgf-bulk-rule-wrapper' data-rule_id='<?php echo esc_attr($bulk_quantity_rule_id); ?>'>
					<div class='fgf-rule-bulk-field'>
						<input type='number' name='<?php echo esc_attr($rule_name); ?>[minimum]' class='fgf-rule-bulk-min-qunaity-field' min='1' step='1' 
						placeholder="<?php esc_attr_e('From', 'free-gifts-for-woocommerce'); ?>" value='<?php echo esc_attr($bulk_quantity_rule['minimum']); ?>'/>
					</div>
					<div class='fgf-rule-bulk-field'>
						<input type='number' name='<?php echo esc_attr($rule_name); ?>[maximum]' class='fgf-rule-bulk-max-qunaity-field' min='1' step='1' 
						placeholder="<?php esc_attr_e('To', 'free-gifts-for-woocommerce'); ?>" value='<?php echo esc_attr($bulk_quantity_rule['maximum']); ?>'/>
					</div>
					<div class='fgf-rule-bulk-field'>
						<input type='number' name='<?php echo esc_attr($rule_name); ?>[gift_quantity]' class='fgf-rule-bulk-gift-quantity-field' min='1' step='1' 
						placeholder="<?php esc_attr_e('Get', 'free-gifts-for-woocommerce'); ?>" value='<?php echo esc_attr($bulk_quantity_rule['gift_quantity']); ?>'/>
					</div>
					<div class='fgf-rule-bulk-field'>
						<select name=<?php echo esc_attr($rule_name); ?>[gift_type]' class = 'fgf-rule-bulk-gift-selection-type-field'>
							<?php foreach (fgf_get_gift_product_selection_types() as $type_id => $type_name) : ?>
								<option value='<?php echo esc_attr($type_id); ?>' <?php selected($bulk_quantity_rule['gift_type'], $type_id); ?>><?php echo esc_html($type_name); ?></option>
							<?php endforeach; ?> 
						</select>
					</div>
					<div class='fgf-rule-bulk-field'>
						<?php
						fgf_select2_html(array(
							'class' => 'fgf-rule-bulk-gift-products-field fgf-bulk-gift-type-field fgf-bulk-gift-type-1',
							'name' => "{$rule_name}[gift_products]",
							'list_type' => 'products',
							'action' => 'fgf_json_search_products_and_variations',
							'display_stock' => 'yes',
							'placeholder' => __('Search a Product', 'free-gifts-for-woocommerce'),
							'options' => $bulk_quantity_rule['gift_products'],
						));
						?>
					</div>
					<div class='fgf-rule-bulk-field'>
						<select class='fgf_select2 fgf-rule-bulk-gift-category-field fgf-bulk-gift-type-field fgf-bulk-gift-type-2' name=<?php echo esc_attr($rule_name); ?>[gift_categories][]' multiple='multiple'>
							<?php
							foreach (fgf_get_wc_categories() as $category_id => $category_name) :
								$selected = ( in_array($category_id, $bulk_quantity_rule['gift_categories']) ) ? ' selected="selected"' : '';
								?>
								<option value="<?php echo esc_attr($category_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($category_name); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php
					/**
					 * This hook is used to display extra content after bulk pricing rule.
					 * 
					 * @since 12.0.0
					 */
					do_action('fgf_bulk_rule_settings', 'fgf_bulk_quantity_rules', $bulk_quantity_rule_id, $bulk_quantity_rule);
					?>
					<div class='fgf-rule-bulk-field'>
						<button type='button' class='fgf-remove-bulk-quantity-rule fgf-remove-bulk-rule'><?php esc_html_e('Remove Rule', 'free-gifts-for-woocommerce'); ?></button>
					</div>
				</div>
				<?php
			endforeach;
		endif;
		?>
	</div>
	<?php
	/**
	 * This hook is used to do extra action after rule bulk quantity gifts settings.
	 *
	 * @since 12.0.0
	 */
	do_action('fgf_after_rule_bulk_quantity_gifts_settings', $rule_data);
	?>
	<script type='text/html' id='tmpl-fgf-bulk-quantity-rule'>
<?php include_once 'html-rule-data-bulk-quantity-rule.php'; ?>
	</script>
</div>

<?php
