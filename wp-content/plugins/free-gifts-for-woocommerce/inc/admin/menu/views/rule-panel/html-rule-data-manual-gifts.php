<?php
/**
 * General - Manual Gifts
 * 
 * @since 11.4.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-rule-manual-gifts-fields-wrapper fgf-rule-general-fields-wrapper'>
	<?php
	/**
	 * This hook is used to do extra action before rule manual gifts settings.
	 *
	 * @since 11.4.0
	 */
	do_action('fgf_before_rule_manual_gifts_settings', $rule_data);
	?>
	<div class='fgf-rule-manual-gifts-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Gift Product(s) Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Gift Product Selection Type', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
			</span>
			<span class='fgf-field'>
				<select name="fgf_rule[fgf_gift_type]" class = 'fgf_gift_type fgf_rule_type fgf_manual_rule_type'>
					<?php foreach (fgf_get_gift_product_selection_types() as $type_id => $type_name) : ?>
						<option value='<?php echo esc_attr($type_id); ?>' <?php selected($rule_data['fgf_gift_type'], $type_id); ?>><?php echo esc_html($type_name); ?></option>
					<?php endforeach; ?> 
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select Product(s)', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span>
					<?php fgf_wc_help_tip(__('The selected products will be displayed to the user', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<?php
				fgf_select2_html(array(
					'class' => 'fgf_gift_products fgf_rule_type fgf-gift-selection-type-1 fgf-gift-selection-type-field',
					'name' => 'fgf_rule[fgf_gift_products]',
					'list_type' => 'products',
					'action' => 'fgf_json_search_products_and_variations',
					'display_stock' => 'yes',
					'placeholder' => __('Search a Product', 'free-gifts-for-woocommerce'),
					'options' => $rule_data['fgf_gift_products'],
				));
				?>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select Categories', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span>
					<?php fgf_wc_help_tip(__('The products from the selected categories will be displayed to the user', 'free-gifts-for-woocommerce')); ?>
				</label>
			</span>
			<span class='fgf-field'>
				<select class='fgf_gift_categories fgf_select2 fgf_rule_type fgf-gift-selection-type-field fgf-gift-selection-type-2' name='fgf_rule[fgf_gift_categories][]' multiple='multiple'>
					<?php
					foreach (fgf_get_wc_categories() as $category_id => $category_name) :
						$selected = ( in_array($category_id, $rule_data['fgf_gift_categories']) ) ? ' selected="selected"' : '';
						?>
						<option value="<?php echo esc_attr($category_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($category_name); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>
	</div>

	<div class='fgf-rule-manual-gifts-quantity-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Quantity Settings', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Quantity for Selected Free Gift Product(s)', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
			</span>
			<span class='fgf-field'>
				<input type="number" class='fgf_rule_type fgf_automatic_rule_type' name='fgf_rule[fgf_automatic_product_qty]' min='1' value="<?php echo esc_attr($rule_data['fgf_automatic_product_qty']); ?>"/>
			</span>
		</div>
	</div>
	<?php
	/**
	 * This hook is used to do extra action after rule manual gifts settings.
	 *
	 * @since 11.4.0
	 */
	do_action('fgf_after_rule_manual_gifts_settings', $rule_data);
	?>
</div>
<?php
