<?php
/**
 * General - BOGO Gifts
 * 
 * @since 11.4.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-rule-bogo-gifts-fields-wrapper fgf-rule-general-fields-wrapper'>
	<?php
	/**
	 * This hook is used to do extra action before rule BOGO gifts settings.
	 *
	 * @since 11.4.0
	 */
	do_action('fgf_before_rule_bogo_gifts_settings', $rule_data);
	?>
	<div class='fgf-rule-bogo-buy-product-fields-wrapper fgf-rule-fields-section'>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Gift Product Type', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span>
					<?php fgf_wc_help_tip(__('When set to Same Product, the user will receive the specified quantities of the same product for free. When set to Different products, the user will receive the specified quantities of another product  for free.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<select name='fgf_rule[fgf_bogo_gift_type]' class = 'fgf_bogo_gift_type fgf_rule_type fgf_automatic_bogo_rule_type'>
					<option value='1' <?php selected($rule_data['fgf_bogo_gift_type'], '1'); ?>><?php esc_html_e('Same Product', 'free-gifts-for-woocommerce'); ?></option>
					<option value='2' <?php selected($rule_data['fgf_bogo_gift_type'], '2'); ?>><?php esc_html_e('Different Products', 'free-gifts-for-woocommerce'); ?></option>
				</select>
			</span>
		</div>

		<div class='fgf-rule-bogo-buy-product-fields-wrapper fgf-rule-fields-section'>
			<h2><?php esc_html_e('Buy Product(s) Configuration', 'free-gifts-for-woocommerce'); ?></h2>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Buy Product Type', 'free-gifts-for-woocommerce'); ?><span class="required">*</span>
						<?php fgf_wc_help_tip(__('Products: The user will receive a Free Gift if they purchase the selected product. Categories: The user will receive a Free Gift if they purchase any one product from the selected category.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
					</label>
				</span>
				<span class='fgf-field'>
					<select name='fgf_rule[fgf_buy_product_type]' class = 'fgf_buy_product_type fgf_bogo_rule_type fgf_rule_type'>
						<?php foreach (fgf_get_buy_product_selection_types() as $type_id => $type_name) : ?>
							<option value='<?php echo esc_attr($type_id); ?>' <?php selected($rule_data['fgf_buy_product_type'], $type_id); ?>><?php echo esc_html($type_name); ?></option>
						<?php endforeach; ?> 
					</select>
				</span>
			</div>

			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Buy Product', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
				</span>
				<span class='fgf-field'>
					<?php
					fgf_select2_html(array(
						'class' => 'fgf_buy_product fgf_rule_type fgf_bogo_rule_type fgf-buy-product-selection-type-field fgf-buy-product-selection-type-field-1',
						'name' => 'fgf_rule[fgf_buy_product]',
						'list_type' => 'products',
						'action' => 'fgf_json_search_products_and_variations',
						'multiple' => true,
						'display_stock' => 'yes',
						'placeholder' => __('Search a Product', 'free-gifts-for-woocommerce'),
						'options' => $rule_data['fgf_buy_product'],
					));
					?>
				</span>
			</div>

			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Buy Category', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
				</span>
				<span class='fgf-field'>
					<select class='fgf_buy_categories fgf_select2 fgf_bogo_rule_type fgf_rule_type fgf-buy-product-selection-type-field fgf-buy-product-selection-type-field-2' multiple='multiple' name='fgf_rule[fgf_buy_categories][]'>
						<?php
						foreach (fgf_get_wc_categories() as $category_id => $category_name) :
							$selected = ( in_array($category_id, $rule_data['fgf_buy_categories']) ) ? ' selected="selected"' : '';
							?>
							<option value="<?php echo esc_attr($category_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($category_name); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
			</div>

			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Buy Product condition applicable when', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
				</span>
				<span class='fgf-field'>
					<select name='fgf_rule[fgf_buy_product_consider_type]' class = 'fgf_bogo_rule_type fgf_buy_product fgf_rule_type fgf-buy-product-selection-type-field fgf-buy-product-selection-type-field-1'>
						<option value='1' <?php selected($rule_data['fgf_buy_product_consider_type'], '1'); ?>><?php esc_html_e('Any one of the selected Product(s) must be in the cart', 'free-gifts-for-woocommerce'); ?></option>
						<option value='2' <?php selected($rule_data['fgf_buy_product_consider_type'], '2'); ?>><?php esc_html_e('All the selected Product(s) must be in the cart', 'free-gifts-for-woocommerce'); ?></option>
					</select>
				</span>
			</div>

			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Buy Product condition applicable when', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
				</span>
				<span class='fgf-field'>
					<select name='fgf_rule[fgf_buy_category_consider_type]' class = 'fgf_bogo_rule_type fgf_buy_categories fgf_rule_type fgf-buy-product-selection-type-field fgf-buy-product-selection-type-field-2'>
						<option value='1' <?php selected($rule_data['fgf_buy_category_consider_type'], '1'); ?>><?php esc_html_e('Any one of the product(s) from the selected category must be in the cart', 'free-gifts-for-woocommerce'); ?></option>
						<option value='2' <?php selected($rule_data['fgf_buy_category_consider_type'], '2'); ?>><?php esc_html_e('One Product from each selected category must be in the cart', 'free-gifts-for-woocommerce'); ?></option>
					</select>
				</span>
			</div>

			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Buy Quantity Calculation Based on', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span>
						<?php fgf_wc_help_tip(__("Same Product's Quantity: Quantity must match for each product to receive a free gift. Total Quantity of the Selected Category's Products: Quantity must match either for each product or quantity of products which belong to the selected category should match to receive a free gift. Product with Least quantity from the selected category: The quantity of the product which is least from the products that belongs to the selected category will be considered for awarding the free gift.", 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
					</label>
				</span>
				<span class='fgf-field'>
					<select name='fgf_rule[fgf_buy_category_type]' class = 'fgf_bogo_rule_type fgf_buy_categories fgf_rule_type fgf-buy-product-selection-type-field fgf-buy-product-selection-type-field-2'>
						<option value='1' <?php selected($rule_data['fgf_buy_category_type'], '1'); ?>><?php esc_html_e("Same Product's Quantity", 'free-gifts-for-woocommerce'); ?></option>
						<option value='2' <?php selected($rule_data['fgf_buy_category_type'], '2'); ?>><?php esc_html_e("Total Quantity of the Selected Category's Products", 'free-gifts-for-woocommerce'); ?></option>
						<option value='3' <?php selected($rule_data['fgf_buy_category_type'], '3'); ?>><?php esc_html_e('Product with least quantity from the selected category', 'free-gifts-for-woocommerce'); ?></option>
					</select>
				</span>
			</div>

			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Buy Quantity Calculation Based on', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span>
						<?php fgf_wc_help_tip(__("Same Product's Quantity: Quantity must match for each product to receive a free gift. Total Quantity of the Selected Products: Quantity must match either for each product quantity or combined quantity of the selected products should match to receive a free gift. Product with Least quantity from the selected products: The product's quantity which is least from the selected products will be considered for awarding the free gift.", 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
					</label>
				</span>
				<span class='fgf-field'>
					<select name='fgf_rule[fgf_buy_product_quantity_consider_type]' class = 'fgf_bogo_rule_type fgf_buy_product fgf_rule_type fgf-buy-product-selection-type-field fgf-buy-product-selection-type-field-1'>
						<option value='1' <?php selected($rule_data['fgf_buy_product_quantity_consider_type'], '1'); ?>><?php esc_html_e("Same Product's Quantity", 'free-gifts-for-woocommerce'); ?></option>
						<option value='2' <?php selected($rule_data['fgf_buy_product_quantity_consider_type'], '2'); ?>><?php esc_html_e('Total Quantity of the Selected Products', 'free-gifts-for-woocommerce'); ?></option>
						<option value='3' <?php selected($rule_data['fgf_buy_product_quantity_consider_type'], '3'); ?>><?php esc_html_e('Product with least quantity from the selected products', 'free-gifts-for-woocommerce'); ?></option>
					</select>
				</span>
			</div>
			<?php
			/**
			 * This hook is used to do extra fields after buy product settings.
			 *
			 * @since 11.2.0
			 */
			do_action('fgf_after_rule_buy_product_settings', $rule_data);
			?>
		</div>
		<div class='fgf-rule-bogo-get-product-fields-wrapper fgf-rule-fields-section'>
			<h2><?php esc_html_e('Get Product(s) Configuration', 'free-gifts-for-woocommerce'); ?></h2>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Gift Product Selection Type', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
				</span>
				<span class='fgf-field'>
					<select name='fgf_rule[fgf_get_product_type]' class = 'fgf_get_product_type fgf_manual_bogo_rule_type fgf_rule_type'>
						<?php foreach (fgf_get_product_selection_types() as $type_id => $type_name) : ?>
							<option value='<?php echo esc_attr($type_id); ?>' <?php selected($rule_data['fgf_get_product_type'], $type_id); ?>><?php echo esc_html($type_name); ?></option>
						<?php endforeach; ?> 
					</select>
				</span>
			</div>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Get Product(s)', 'free-gifts-for-woocommerce'); ?><span class="required">*</span></label>
				</span>
				<span class='fgf-field'>
					<?php
					fgf_select2_html(array(
						'class' => 'fgf_get_products fgf_rule_type fgf-get-product-selection-type-field fgf-get-product-selection-type-field-1',
						'name' => 'fgf_rule[fgf_get_products]',
						'list_type' => 'products',
						'action' => 'fgf_json_search_products_and_variations',
						'display_stock' => 'yes',
						'placeholder' => __('Search a Product', 'free-gifts-for-woocommerce'),
						'options' => $rule_data['fgf_get_products'],
					));
					?>
				</span>
			</div>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Get Category', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
				</span>
				<span class='fgf-field'>
					<select multiple='multiple' class='fgf_get_categories fgf_select2 fgf_manual_bogo_rule_type fgf_rule_type fgf-get-product-selection-type-field fgf-get-product-selection-type-field-2' name='fgf_rule[fgf_get_categories][]'>
						<?php
						foreach (fgf_get_wc_categories() as $category_id => $category_name) :
							$selected = ( in_array($category_id, $rule_data['fgf_get_categories']) ) ? ' selected="selected"' : '';
							?>
							<option value="<?php echo esc_attr($category_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($category_name); ?></option>
						<?php endforeach; ?>
					</select>
				</span>
			</div>
			<?php
			/**
			 * This hook is used to do extra fields after get product settings.
			 *
			 * @since 11.2.0
			 */
			do_action('fgf_after_rule_get_product_settings', $rule_data);
			?>
		</div>
		<div class='fgf-rule-bogo-quantity-fields-wrapper fgf-rule-fields-section'>
			<h2><?php esc_html_e('Quantity Configuration', 'free-gifts-for-woocommerce'); ?></h2>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Get Product Quantity Restriction is Applicable', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span>
						<?php fgf_wc_help_tip(__('On All the Eligible Products Separately: Restriction is applicable separately for each product selected in Get Products. Across the Eligible Products: Restriction is applicable on the Total Quantity of Get Products added in the cart.', 'free-gifts-for-woocommerce')); ?>
					</label>
				</span>
				<span class='fgf-field'>
					<select name='fgf_rule[fgf_buy_quantity_type]' class = 'fgf_buy_quantity_type fgf_manual_bogo_rule_type fgf_rule_type'>
						<option value='1' <?php selected($rule_data['fgf_buy_quantity_type'], '1'); ?>><?php esc_html_e('On All the Eligible Products Separately', 'free-gifts-for-woocommerce'); ?></option>
						<option value='2' <?php selected($rule_data['fgf_buy_quantity_type'], '2'); ?>><?php esc_html_e('Across the Eligible Products', 'free-gifts-for-woocommerce'); ?></option>
					</select>
				</span>
			</div>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Get Product Awarding method', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span>
						<?php fgf_wc_help_tip(__("Display all the selected products: All the selected products will be displayed for the customer's selection. Display only the same product: The same bought product(s) only will be displayed for the customer's section. ", 'free-gifts-for-woocommerce')); ?>
					</label>
				</span>
				<span class='fgf-field'>
					<select name='fgf_rule[fgf_bogo_get_gift_type]' class = 'fgf_buy_quantity_type fgf_manual_bogo_rule_type fgf_rule_type'>
						<option value='1' <?php selected($rule_data['fgf_bogo_get_gift_type'], '1'); ?>><?php esc_html_e('Display all the selected products', 'free-gifts-for-woocommerce'); ?></option>
						<option value='2' <?php selected($rule_data['fgf_bogo_get_gift_type'], '2'); ?>><?php esc_html_e('Display only the same product', 'free-gifts-for-woocommerce'); ?></option>
					</select>
				</span>
			</div>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Buy Quantity', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
				</span>
				<span class='fgf-field'>
					<input type='number' class='fgf_rule_type fgf_bogo_rule_type' name='fgf_rule[fgf_buy_product_count]' min='1' value="<?php echo esc_attr($rule_data['fgf_buy_product_count']); ?>"/>
				</span>
			</div>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Get Quantity', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
				</span>
				<span class='fgf-field'>
					<input type='number' class='fgf_rule_type fgf_bogo_rule_type' name='fgf_rule[fgf_get_product_count]' min='1' value="<?php echo esc_attr($rule_data['fgf_get_product_count']); ?>"/>
				</span>
			</div>
			<?php
			/**
			 * This hook is used to do extra fields after quantity settings.
			 *
			 * @since 11.4.0
			 */
			do_action('fgf_after_rule_bogo_quantity_settings', $rule_data);
			?>
		</div>
		<div class='fgf-rule-bogo-repeat-gift-fields-wrapper fgf-rule-fields-section'>
			<h2><?php esc_html_e('Gift Repeating Configuration', 'free-gifts-for-woocommerce'); ?></h2>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Repeat Gift', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span>
						<?php fgf_wc_help_tip(__('When enabled, the user will keep receiving free gifts every time they add the multiples of the required quantity to the cart.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
					</label>
				</span>
				<span class='fgf-field'>
					<input type='checkbox' name='fgf_rule[fgf_bogo_gift_repeat]' class = 'fgf_bogo_gift_repeat fgf_rule_type fgf_bogo_rule_type' value='2' <?php checked('2', $rule_data['fgf_bogo_gift_repeat']); ?>/>
				</span>
			</div>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Repeat Gift Mode', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span>
						<?php fgf_wc_help_tip(__('Unlimited: No restriction on receiving Free Gifts. Limited: Free Gift can be received till the Repeat Limit is reached.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
					</label>
				</span>
				<span class='fgf-field'>
					<select name='fgf_rule[fgf_bogo_gift_repeat_mode]' class = 'fgf_bogo_gift_repeat_mode fgf_bogo_gift_repeat_field fgf_rule_type fgf_bogo_rule_type'>
						<option value='1' <?php selected($rule_data['fgf_bogo_gift_repeat_mode'], '1'); ?>><?php esc_html_e('Unlimited', 'free-gifts-for-woocommerce'); ?></option>
						<option value='2' <?php selected($rule_data['fgf_bogo_gift_repeat_mode'], '2'); ?>><?php esc_html_e('Limited', 'free-gifts-for-woocommerce'); ?></option>
					</select>
				</span>
			</div>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Repeat Limit', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
				</span>
				<span class='fgf-field'>
					<input type='number' class='fgf_bogo_gift_repeat_limit fgf_bogo_gift_repeat_field fgf_rule_type fgf_bogo_rule_type' name='fgf_rule[fgf_bogo_gift_repeat_limit]' min='1' value="<?php echo esc_attr($rule_data['fgf_bogo_gift_repeat_limit']); ?>"/>
				</span>
			</div>
			<?php
			/**
			 * This hook is used to do extra fields after repeat gift settings.
			 *
			 * @since 11.4.0
			 */
			do_action('fgf_after_rule_bogo_repeat_gift_settings', $rule_data);
			?>
		</div>
		<?php
		/**
		 * This hook is used to do extra action after rule BOGO gifts settings.
		 *
		 * @since 11.4.0
		 */
		do_action('fgf_after_rule_bogo_gifts_settings', $rule_data);
		?>
	</div>
</div>
<?php
