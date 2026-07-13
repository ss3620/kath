<?php
/**
 * Panel - Criteria
 * 
 * @since 1.0.0
 * 
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div id='fgf_rule_data_criteria' class='fgf-rule-options-wrapper'>
	<?php
	/**
	 * This hook is used to display extra content before rule criteria settings.
	 * 
	 * @since 1.0.0
	 */
	do_action('fgf_before_rule_criteria_settings', $rule_data);
	?>
	<div class='fgf-rule-criteria-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Criteria Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Criteria Type', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__('AND – The user will be eligible only when they satisfy both the criteria. OR – The user will be eligible if they satisfy any one of the criteria', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<select name="fgf_rule[fgf_condition_type]">
					<option value="1" <?php selected($rule_data['fgf_condition_type'], '1'); ?>><?php esc_html_e('AND', 'free-gifts-for-woocommerce'); ?></option>
					<option value="2" <?php selected($rule_data['fgf_condition_type'], '2'); ?>><?php esc_html_e('OR', 'free-gifts-for-woocommerce'); ?></option>
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Criteria Calculated based on', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__('Cart Subtotal - Sum of all Product Prices and Taxes if applicable. Order Total - Sum of all Product Prices, Shipping and Taxes. Category Total - Sum of all Product Prices plus applicable Taxes that belong to a particular category.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<select name="fgf_rule[fgf_total_type]" class="fgf-rule-total-type">
					<?php
					foreach (fgf_get_rule_criteria_total_type_options() as $key => $type_label) :
						?>
						<option value="<?php echo esc_attr($key); ?>" <?php selected($rule_data['fgf_total_type'], $key); ?>><?php echo esc_html($type_label); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select a Category', 'free-gifts-for-woocommerce'); ?><span class="required">*</span></label>
			</span>
			<span class='fgf-field'>
				<select class="fgf_select2 fgf-rule-cart-total-type-fields fgf-rule-cart-total-type-3" name="fgf_rule[fgf_cart_categories][]">
					<?php
					foreach (fgf_get_wc_categories() as $category_id => $category_name) :
						$selected = ( in_array($category_id, $rule_data['fgf_cart_categories']) ) ? ' selected="selected"' : '';
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
				<select class="fgf-rule-cart-total-type-fields fgf-rule-cart-total-type-3" name="fgf_rule[fgf_consider_cart_subcategories_total]">
					<option value="1"<?php selected(false, $rule_data['fgf_consider_cart_subcategories_total']); ?>><?php esc_html_e('No', 'free-gifts-for-woocommerce'); ?></option>
					<option value="2"<?php selected(true, $rule_data['fgf_consider_cart_subcategories_total']); ?>><?php esc_html_e('Yes', 'free-gifts-for-woocommerce'); ?></option>
				</select>
			</span>
		</div>

		<?php
		/**
		 * This hook is used to display extra content after criteria total type settings.
		 * 
		 * @since 8.6
		 */
		do_action('fgf_after_rule_criteria_total_type_settings', $rule_data);
		?>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Calculate Category Total Criteria after WC Coupon Discount is applied', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__('When enabled and if WC coupon is applied, then the discount amount will be considered for calculation of "Category Total Criteria".', 'free-gifts-for-woocommerce')); ?>
				</label>
			</span>
			<span class='fgf-field'>
				<input type="checkbox" name="fgf_rule[fgf_exclude_category_subtotal_discount_amount]" class="fgf-rule-cart-total-type-fields fgf-rule-cart-total-type-3 fgf-rule-cart-total-type-brands" value="2" <?php checked('2', $rule_data['fgf_exclude_category_subtotal_discount_amount']); ?>/>
			</span>
		</div>

		<div class='fgf-field-wrapper fgf-field-second-wrapper'>
			<span class='fgf-field'>
				<?php esc_html_e('Min', 'free-gifts-for-woocommerce'); ?>
				<input type="text" name="fgf_rule[fgf_cart_subtotal_min_value]" class='wc_input_price' min="0" value="<?php echo esc_attr(wc_format_localized_price($rule_data['fgf_cart_subtotal_min_value'])); ?>"/>
			</span>
			<span class='fgf-field'>
				<?php esc_html_e('Max', 'free-gifts-for-woocommerce'); ?>
				<input type="text" name="fgf_rule[fgf_cart_subtotal_max_value]" class='wc_input_price' min="0" value="<?php echo esc_attr(wc_format_localized_price($rule_data['fgf_cart_subtotal_max_value'])); ?>"/>
			</span>
		</div>

		<div class='fgf-field-wrapper fgf-rule-cart-quantity-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Cart Quantity', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__("The user's cart quantity(sum of all product quantities) should be within the specified range", 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<?php esc_html_e('Min', 'free-gifts-for-woocommerce'); ?>
				<input type="number" name="fgf_rule[fgf_quantity_min_value]" min="0" value="<?php echo esc_attr($rule_data['fgf_quantity_min_value']); ?>"/>
			</span>
			<span class='fgf-field'>
				<?php esc_html_e('Max', 'free-gifts-for-woocommerce'); ?>
				<input type="number" name="fgf_rule[fgf_quantity_max_value]" min="0" value="<?php echo esc_attr($rule_data['fgf_quantity_max_value']); ?>"/>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Number of Products in the Cart', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__("Total number of products added in the user's cart should be within the specified range", 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<?php esc_html_e('Min', 'free-gifts-for-woocommerce'); ?>
				<input type="number" name="fgf_rule[fgf_product_count_min_value]" min="0" value="<?php echo esc_attr($rule_data['fgf_product_count_min_value']); ?>"/>
			</span>
			<span class='fgf-field'>
				<?php esc_html_e('Max', 'free-gifts-for-woocommerce'); ?>
				<input type="number" name="fgf_rule[fgf_product_count_max_value]" min="0" value="<?php echo esc_attr($rule_data['fgf_product_count_max_value']); ?>"/>
			</span>
		</div>

		<?php
		/**
		 * This hook is used to display extra content after rule criteria settings.
		 * 
		 * @since 1.0.0
		 */
		do_action('fgf_after_rule_criteria_settings', $rule_data);
		?>
	</div>
</div>
<?php
