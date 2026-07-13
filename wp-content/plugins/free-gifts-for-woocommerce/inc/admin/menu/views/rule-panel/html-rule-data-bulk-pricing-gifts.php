<?php
/**
 * General - Bulk Pricing Gifts
 * 
 * @since 12.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-rule-bulk-pricing-gifts-fields-wrapper fgf-rule-general-fields-wrapper'>
	<?php
	/**
	 * This hook is used to do extra action before rule bulk pricing gifts settings.
	 *
	 * @since 11.4.0
	 */
	do_action('fgf_before_rule_bulk_pricing_gifts_settings', $rule_data);
	?>
	<div class='fgf-rule-fields-section'>
		<h2><?php esc_html_e('Bulk Pricing Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Bulk Pricing Calculated based on', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__('Cart Subtotal - Sum of all Product Prices and Taxes if applicable. Order Total - Sum of all Product Prices, Shipping and Taxes. Category Total - Sum of all Product Prices plus applicable Taxes that belong to a particular category.', 'free-gifts-for-woocommerce')); ?>
				</label>
			</span>
			<span class='fgf-field'>
				<select name='fgf_rule[fgf_bulk_pricing_total_type]' class='fgf-bulk-pricing-total-type'>
					<?php
					foreach (fgf_get_rule_criteria_total_type_options() as $key => $type_label) :
						?>
						<option value="<?php echo esc_attr($key); ?>" <?php selected($rule_data['fgf_bulk_pricing_total_type'], $key); ?>><?php echo esc_html($type_label); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select a Category', 'free-gifts-for-woocommerce'); ?><span class="required">*</span></label>
			</span>
			<span class='fgf-field'>
				<select class='fgf_select2 fgf-bulk-pricing-total-type-field fgf-bulk-pricing-total-type-3' multiple='multiple' name='fgf_rule[fgf_bulk_pricing_categories][]'>
					<?php
					foreach (fgf_get_wc_categories() as $category_id => $category_name) :
						$selected = ( in_array($category_id, $rule_data['fgf_bulk_pricing_categories']) ) ? ' selected="selected"' : '';
						?>
						<option value="<?php echo esc_attr($category_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($category_name); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Including Sub-category Product(s)', 'free-gifts-for-woocommerce'); ?></label>
			</span>
			<span class='fgf-field'>
				<select class='fgf-bulk-pricing-total-type-field fgf-bulk-pricing-total-type-3' name='fgf_rule[fgf_consider_bulk_pricing_subcategories_total]'>
					<option value='1'<?php selected(1, $rule_data['fgf_consider_bulk_pricing_subcategories_total']); ?>><?php esc_html_e('No', 'free-gifts-for-woocommerce'); ?></option>
					<option value='2'<?php selected(2, $rule_data['fgf_consider_bulk_pricing_subcategories_total']); ?>><?php esc_html_e('Yes', 'free-gifts-for-woocommerce'); ?></option>
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Calculate Category Total Criteria after WC Coupon Discount is applied', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__('When enabled and if WC coupon is applied, then the discount amount will be considered for calculation of "Category Total Criteria".', 'free-gifts-for-woocommerce')); ?>
				</label>
			</span>
			<span class='fgf-field'>
				<input type="checkbox" name="fgf_rule[fgf_calculate_bulk_pricing_discounted_category_total]" class='fgf-bulk-pricing-total-type-field fgf-bulk-pricing-total-type-3' value='2' <?php checked('2', $rule_data['fgf_calculate_bulk_pricing_discounted_category_total']); ?>/>
			</span>
		</div>
		<?php
		/**
		 * This hook is used to display extra content after bulk pricing rule settings.
		 * 
		 * @since 11.4.0
		 */
		do_action('fgf_bulk_pricing_rule_settings', $rule_data);
		?>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Rule Consider Type', 'free-gifts-for-woocommerce'); ?></label>
			</span>
			<span class='fgf-field'>
				<select class='fgf-bulk-pricing-rules-consider-type' name='fgf_rule[fgf_bulk_pricing_rules_consider_type]'>
					<option value='1'<?php selected(1, $rule_data['fgf_bulk_pricing_rules_consider_type']); ?>><?php esc_html_e('First Matched Rule', 'free-gifts-for-woocommerce'); ?></option>
					<option value='2'<?php selected(2, $rule_data['fgf_bulk_pricing_rules_consider_type']); ?>><?php esc_html_e('All Valid Rule', 'free-gifts-for-woocommerce'); ?></option>
				</select>
			</span>
		</div>
	</div>

	<h2><?php esc_html_e('Bulk Pricing Rules', 'free-gifts-for-woocommerce'); ?></h2>
	<div class='fgf-rule-bulk-pricing-rules-wrapper fgf-bulk-rules-wrapper'>
		<button type='button' class='fgf-add-bulk-pricing-rule fgf-add-bulk-rule' data-template_name='fgf-bulk-pricing-rule'><?php esc_html_e('Add Rule', 'free-gifts-for-woocommerce'); ?></button>
		<?php
		if (fgf_check_is_array($rule_data['fgf_bulk_pricing_rules'])) :
			foreach ($rule_data['fgf_bulk_pricing_rules'] as $bulk_pricing_rule_id => $bulk_pricing_rule) :
				$bulk_pricing_rule = fgf_parsed_bulk_pricing_rules($bulk_pricing_rule);
				$rule_name = "fgf_rule[fgf_bulk_pricing_rules][{$bulk_pricing_rule_id}]";
				?>
				<div class='fgf-rule-bulk-pricing-rule-wrapper fgf-bulk-rule-wrapper' data-rule_id='<?php echo esc_attr($bulk_pricing_rule_id); ?>'>
					<div class='fgf-rule-bulk-field'>
						<input type='text' name='<?php echo esc_attr($rule_name); ?>[minimum]' class='fgf-rule-bulk-pricing-min-field wc_input_price' value='<?php echo esc_attr($bulk_pricing_rule['minimum']); ?>' placeholder="<?php esc_attr_e('From', 'free-gifts-for-woocommerce'); ?>"/>
					</div>
					<div class='fgf-rule-bulk-field'>
						<input type='text' name='<?php echo esc_attr($rule_name); ?>[maximum]' class='fgf-rule-bulk-pricing-max-field wc_input_price' value='<?php echo esc_attr(wc_format_localized_price($bulk_pricing_rule['maximum'])); ?>' placeholder="<?php esc_attr_e('To', 'free-gifts-for-woocommerce'); ?>"/>
					</div>
					<div class='fgf-rule-bulk-field'>
						<input type='number' name='<?php echo esc_attr($rule_name); ?>[gift_quantity]' class='fgf-rule-bulk-gift-quantity-field' min='1' step='1' value='<?php echo esc_attr(wc_format_localized_price($bulk_pricing_rule['gift_quantity'])); ?>' placeholder="<?php esc_attr_e('Get', 'free-gifts-for-woocommerce'); ?>"/>
					</div>
					<div class='fgf-rule-bulk-field'>
						<select name=<?php echo esc_attr($rule_name); ?>[gift_type]' class = 'fgf-rule-bulk-gift-selection-type-field'>
							<?php foreach (fgf_get_gift_product_selection_types() as $type_id => $type_name) : ?>
								<option value='<?php echo esc_attr($type_id); ?>' <?php selected($bulk_pricing_rule['gift_type'], $type_id); ?>><?php echo esc_html($type_name); ?></option>
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
							'options' => $bulk_pricing_rule['gift_products'],
						));
						?>
					</div>
					<div class='fgf-rule-bulk-field'>
						<select class='fgf_select2 fgf-rule-bulk-gift-category-field fgf-bulk-gift-type-field fgf-bulk-gift-type-2' name=<?php echo esc_attr($rule_name); ?>[gift_categories][]' multiple='multiple'>
							<?php
							foreach (fgf_get_wc_categories() as $category_id => $category_name) :
								$selected = ( in_array($category_id, $bulk_pricing_rule['gift_categories']) ) ? ' selected="selected"' : '';
								?>
								<option value="<?php echo esc_attr($category_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($category_name); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php
					/**
					 * This hook is used to display extra content after bulk pricing rule.
					 * 
					 * @since 11.4.0
					 */
					do_action('fgf_bulk_rule_settings', 'fgf_bulk_pricing_rules', $bulk_pricing_rule_id, $bulk_pricing_rule);
					?>
					<div class='fgf-rule-bulk-field'>
						<button type='button' class='fgf-remove-bulk-pricing-rule fgf-remove-bulk-rule'><?php esc_html_e('Remove Rule', 'free-gifts-for-woocommerce'); ?></button>
					</div>
				</div>
				<?php
			endforeach;
		endif;
		?>
	</div>
	<?php
	/**
	 * This hook is used to do extra action after rule bulk pricing gifts settings.
	 *
	 * @since 11.4.0
	 */
	do_action('fgf_before_rule_bulk_pricing_gifts_settings', $rule_data);
	?>
	<script type='text/html' id='tmpl-fgf-bulk-pricing-rule'>
<?php include_once 'html-rule-data-bulk-pricing-rule.php'; ?>
	</script>
</div>
<?php
