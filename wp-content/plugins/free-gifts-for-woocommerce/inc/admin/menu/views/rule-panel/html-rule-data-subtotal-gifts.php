<?php
/**
 * General - Subtotal Gifts
 * 
 * @since 11.4.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-rule-subtotal-gifts-fields-wrapper fgf-rule-general-fields-wrapper'>
	<?php
	/**
	 * This hook is used to do extra action before rule subtotal gifts settings.
	 *
	 * @since 11.4.0
	 */
	do_action('fgf_before_rule_subtotal_gifts_settings', $rule_data);
	?>
	<div class='fgf-rule-subtotal-price-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Total Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Criteria Calculated based on', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__('Cart Subtotal - Sum of all Product Prices and Taxes if applicable. Order Total - Sum of all Product Prices, Shipping and Taxes. Category Total - Sum of all Product Prices plus applicable Taxes that belong to a particular category.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<select name="fgf_rule[fgf_subtotal_price_type]" class='fgf-rule-subtotal-type'>
					<?php
					foreach (fgf_get_rule_criteria_total_type_options() as $key => $type_label) :
						?>
						<option value="<?php echo esc_attr($key); ?>" <?php selected($rule_data['fgf_subtotal_price_type'], $key); ?>><?php echo esc_html($type_label); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select a Category', 'free-gifts-for-woocommerce'); ?><span class="required">*</span></label>
			</span>
			<span class='fgf-field'>
				<select class="fgf_select2 fgf-rule-total-type-fields fgf-rule-total-type-3" name="fgf_rule[fgf_total_categories][]">
					<?php
					foreach (fgf_get_wc_categories() as $category_id => $category_name) :
						$selected = ( in_array($category_id, $rule_data['fgf_total_categories']) ) ? ' selected="selected"' : '';
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
				<select class='fgf-rule-total-type-fields fgf-rule-total-type-3' name='fgf_rule[fgf_consider_subcategories_total]'>
					<option value="1"<?php selected(1, $rule_data['fgf_consider_subcategories_total']); ?>><?php esc_html_e('No', 'free-gifts-for-woocommerce'); ?></option>
					<option value="2"<?php selected(2, $rule_data['fgf_consider_subcategories_total']); ?>><?php esc_html_e('Yes', 'free-gifts-for-woocommerce'); ?></option>
				</select>
			</span>
		</div>

		<?php
		/**
		 * This hook is used to display extra content after total type settings.
		 * 
		 * @since 11.5.0
		 */
		do_action('fgf_after_rule_subtotal_gifts_total_type_settings', $rule_data);
		?>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Calculate Category Total Criteria after WC Coupon Discount is applied', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__('When enabled and if WC coupon is applied, then the discount amount will be considered for calculation of "Category Total Criteria".', 'free-gifts-for-woocommerce')); ?>
				</label>
			</span>
			<span class='fgf-field'>
				<input type='checkbox' name='fgf_rule[fgf_calculate_total_discounted_category_total]' class='fgf-rule-total-type-fields fgf-rule-total-type-3' value='2' <?php checked('2', $rule_data['fgf_calculate_total_discounted_category_total']); ?>/>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Price', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
			</span>
			<span class='fgf-field'>
				<input type='text' class='fgf_rule_type fgf-subtotal-price-field fgf-subtotal-rule-type wc_input_price' name='fgf_rule[fgf_subtotal_price]' min='0' value="<?php echo esc_attr(wc_format_localized_price($rule_data['fgf_subtotal_price'])); ?>"/>
			</span>
		</div>
	</div>

	<div class='fgf-rule-subtotal-gifts-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Gift Product(s) Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Gift Product Selection Type', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
			</span>
			<span class='fgf-field'>
				<select name='fgf_rule[fgf_subtotal_gift_type]' class = 'fgf-subtotal-gift-type fgf_rule_type fgf-subtotal-rule-type fgf-subtotal-manual-rule-type'>
					<?php foreach (fgf_get_subtotal_gift_product_selection_types() as $type_id => $type_name) : ?>
						<option value='<?php echo esc_attr($type_id); ?>' <?php selected($rule_data['fgf_subtotal_gift_type'], $type_id); ?>><?php echo esc_html($type_name); ?></option>
					<?php endforeach; ?> 
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select Product(s)', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span>
					<?php fgf_wc_help_tip(__("The selected Product(s) will be added to the user's cart once the subtoal reached", 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<?php
				fgf_select2_html(array(
					'class' => 'fgf-subtotal-gift-products fgf_rule_type fgf-subtotal-rule-type fgf-subtotal-gift-selection-type-1 fgf-subtotal-gift-selection-type-field',
					'name' => 'fgf_rule[fgf_subtotal_gift_products]',
					'list_type' => 'products',
					'action' => 'fgf_json_search_products_and_variations',
					'exclude_global_variable' => 'yes',
					'display_stock' => 'yes',
					'placeholder' => __('Search a Product', 'free-gifts-for-woocommerce'),
					'options' => $rule_data['fgf_subtotal_gift_products'],
				));
				?>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select Categories', 'free-gifts-for-woocommerce'); ?><span class="required">*</span>
					<?php fgf_wc_help_tip(__('The products from the selected categories will be displayed to the user', 'free-gifts-for-woocommerce')); ?>
				</label>
			</span>
			<span class='fgf-field'>
				<select class='fgf-subtotal-gift-categories fgf_select2 fgf_rule_type fgf-subtotal-rule-type fgf-subtotal-manual-rule-type fgf-subtotal-gift-selection-type-2 fgf-subtotal-gift-selection-type-field' name="fgf_rule[fgf_subtotal_gift_categories][]" multiple="multiple">
					<?php
					foreach (fgf_get_wc_categories() as $category_id => $category_name) :
						$selected = ( in_array($category_id, $rule_data['fgf_subtotal_gift_categories']) ) ? ' selected="selected"' : '';
						?>
						<option value="<?php echo esc_attr($category_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($category_name); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>

		<?php
		/**
		 * This hook is used to do extra action after rule subtotal gift products settings.
		 *
		 * @since 11.3.0
		 */
		do_action('fgf_after_rule_subtotal_gift_products_settings', $rule_data);
		?>
	</div>

	<div class='fgf-rule-subtotal-gifts-quantity-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Gift Quantity Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Quantity for Selected Free Gift Product(s)', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
			</span>
			<span class='fgf-field'>
				<input type='number' class='fgf_rule_type fgf-subtotal-products-qty fgf-subtotal-rule-type' name='fgf_rule[fgf_subtotal_gift_products_qty]' min='1' value="<?php echo esc_attr($rule_data['fgf_subtotal_gift_products_qty']); ?>"/>
			</span>
		</div>
	</div>
	<div class='fgf-rule-subtotal-repeat-gifts-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Gift Repeating Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Repeat Gift', 'free-gifts-for-woocommerce'); ?><span class="required">*</span>
					<?php fgf_wc_help_tip(__('When enabled, the user will keep receiving free gifts every time they add the multiples of the required quantity to the cart.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<input type='checkbox' name='fgf_rule[fgf_subtotal_repeat_gift]' class = 'fgf-subtotal-repeat-gift fgf_rule_type fgf-subtotal-rule-type' value='2' <?php checked('2', $rule_data['fgf_subtotal_repeat_gift']); ?>/>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Repeat Gift Mode', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span>
					<?php fgf_wc_help_tip(__('Unlimited: No restriction on receiving Free Gifts. Limited: Free Gift can be received till the Repeat Limit is reached.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<select name='fgf_rule[fgf_subtotal_repeat_gift_mode]' class = 'fgf-subtotal-repeat-gift-mode fgf_rule_type fgf-subtotal-rule-type fgf-subtotal-repeat-gift-field'>
					<option value="1" <?php selected($rule_data['fgf_subtotal_repeat_gift_mode'], '1'); ?>><?php esc_html_e('Unlimited', 'free-gifts-for-woocommerce'); ?></option>
					<option value="2" <?php selected($rule_data['fgf_subtotal_repeat_gift_mode'], '2'); ?>><?php esc_html_e('Limited', 'free-gifts-for-woocommerce'); ?></option>
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Repeat Limit', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
			</span>
			<span class='fgf-field'>
				<input type='number' class='fgf-subtotal-gift-repeat-limit fgf_rule_type fgf-subtotal-rule-type fgf-subtotal-repeat-gift-field' name='fgf_rule[fgf_subtotal_repeat_gift_limit]' min='1' value="<?php echo esc_attr($rule_data['fgf_subtotal_repeat_gift_limit']); ?>"/>
			</span>
		</div>
	</div>
	<?php
	/**
	 * This hook is used to do extra action after rule subtotal gifts settings.
	 *
	 * @since 11.4.0
	 */
	do_action('fgf_after_rule_subtotal_gifts_settings', $rule_data);
	?>
</div>
<?php
