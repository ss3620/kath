<?php
/**
 *  Rule filters data.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div id="fgf_rule_data_filters" class="fgf-rule-options-wrapper">

	<?php
	/**
	 * This hook is used to do extra action before rule filter settings.
	 * 
	 * @since 1.0.0
	 */
	do_action('fgf_before_rule_filters_settings', $rule_data);
	?>
	<div class='fgf-rule-user-filter-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('User Filter', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('User Filter', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__('The selected users will be eligible for free gifts', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<select class="fgf_user_filter_type" name="fgf_rule[fgf_user_filter_type]">
					<?php foreach (fgf_rule_user_filter_options() as $filter_id => $filter_name) : ?>
						<option value="<?php echo esc_attr($filter_id); ?>" <?php selected($rule_data['fgf_user_filter_type'], $filter_id); ?>><?php echo esc_html($filter_name); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>
		<?php
		/**
		 * This hook is used to do extra action before rule user filter settings.
		 * 
		 * @since 9.2
		 */
		do_action('fgf_before_rule_user_filters_settings', $rule_data);
		?>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select User(s)', 'free-gifts-for-woocommerce'); ?></label>
			</span>
			<span class='fgf-field'>
				<?php
				fgf_select2_html(array(
					'class' => 'fgf_include_users fgf_user_filter fgf_user_filter-2',
					'name' => 'fgf_rule[fgf_include_users]',
					'list_type' => 'customers',
					'action' => 'fgf_json_search_customers',
					'placeholder' => __('Search a User', 'free-gifts-for-woocommerce'),
					'options' => $rule_data['fgf_include_users'],
				));
				?>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select User(s)', 'free-gifts-for-woocommerce'); ?></label>
			</span>
			<span class='fgf-field'>
				<?php
				fgf_select2_html(array(
					'class' => 'fgf_exclude_users fgf_user_filter fgf_user_filter-3',
					'name' => 'fgf_rule[fgf_exclude_users]',
					'list_type' => 'customers',
					'action' => 'fgf_json_search_customers',
					'placeholder' => __('Search a User', 'free-gifts-for-woocommerce'),
					'options' => $rule_data['fgf_exclude_users'],
				));
				?>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select User Role(s)', 'free-gifts-for-woocommerce'); ?> </label>
			</span>
			<span class='fgf-field'>
				<select class="fgf_include_user_roles fgf_user_filter fgf_select2 fgf_user_filter-4" name="fgf_rule[fgf_include_user_roles][]" multiple="multiple">
					<?php
					foreach (fgf_get_user_roles() as $user_role_id => $user_role_name) :
						$selected = ( in_array($user_role_id, $rule_data['fgf_include_user_roles']) ) ? ' selected="selected"' : '';
						?>
						<option value="<?php echo esc_attr($user_role_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($user_role_name); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select User Role(s)', 'free-gifts-for-woocommerce'); ?></label>
			</span>
			<span class='fgf-field'>
				<select class="fgf_exclude_user_roles fgf_user_filter fgf_select2 fgf_user_filter-5" name="fgf_rule[fgf_exclude_user_roles][]" multiple="multiple">
					<?php
					foreach (fgf_get_user_roles() as $user_role_id => $user_role_name) :
						$selected = ( in_array($user_role_id, $rule_data['fgf_exclude_user_roles']) ) ? ' selected="selected"' : '';
						?>
						<option value="<?php echo esc_attr($user_role_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($user_role_name); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>
		<?php
		/**
		 * This hook is used to do extra action after rule user filter settings.
		 * 
		 * @since 9.2.0
		 */
		do_action('fgf_after_rule_user_filters_settings', $rule_data);
		?>
	</div>
	<div class='fgf-rule-product-filter-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Product Filter', 'free-gifts-for-woocommerce'); ?></h2>
		<?php
		/**
		 * This hook is used to do extra action before rule product filter settings.
		 * 
		 * @since 9.2
		 */
		do_action('fgf_before_rule_product_filters_settings', $rule_data);
		?>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Product Filter', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__('The users will be eligible for free products when they purchase any of the products selected in this option.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<select class="fgf_product_filter_type" name="fgf_rule[fgf_product_filter_type]">
					<?php foreach (fgf_rule_product_filter_options() as $filter_id => $filter_name) : ?>
						<option value="<?php echo esc_attr($filter_id); ?>" <?php selected($rule_data['fgf_product_filter_type'], $filter_id); ?>><?php echo esc_html($filter_name); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Applicable when', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__(' This option provides additional control on when to award the Free Gifts.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<select name="fgf_rule[fgf_applicable_products_type]" class="fgf_product_filter fgf_applicable_products_type">
					<?php foreach (fgf_rule_product_applicable_filter_options() as $filter_id => $filter_name) : ?>
						<option value="<?php echo esc_attr($filter_id); ?>" <?php selected($rule_data['fgf_applicable_products_type'], $filter_id); ?>><?php echo esc_html($filter_name); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select Product(s)', 'free-gifts-for-woocommerce'); ?></label>
			</span>
			<span class='fgf-field'>
				<?php
				fgf_select2_html(array(
					'class' => 'fgf_include_products fgf_product_filter',
					'name' => 'fgf_rule[fgf_include_products]',
					'list_type' => 'products',
					'action' => 'fgf_json_search_products_and_variations',
					'display_stock' => 'yes',
					'placeholder' => __('Search a Product', 'free-gifts-for-woocommerce'),
					'options' => $rule_data['fgf_include_products'],
				));
				?>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select Product(s)', 'free-gifts-for-woocommerce'); ?></label>
			</span>
			<span class='fgf-field'>
				<?php
				fgf_select2_html(array(
					'class' => 'fgf_exclude_products fgf_product_filter',
					'name' => 'fgf_rule[fgf_exclude_products]',
					'list_type' => 'products',
					'action' => 'fgf_json_search_products_and_variations',
					'display_stock' => 'yes',
					'placeholder' => __('Search a Product', 'free-gifts-for-woocommerce'),
					'options' => $rule_data['fgf_exclude_products'],
				));
				?>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Product Count', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__('The user must add the number of products mentioned in this option to their cart in order for them to be eligibile for a Free Gift.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<input type="number" class="fgf_product_filter fgf_include_product_count" name="fgf_rule[fgf_include_product_count]" min="1" value="<?php echo esc_attr($rule_data['fgf_include_product_count']); ?>"/>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Applicable when', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__('This option provides additional control on when to award the Free Gifts.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<select name="fgf_rule[fgf_applicable_categories_type]" class="fgf_product_filter fgf_applicable_categories_type">
					<?php foreach (fgf_rule_category_applicable_filter_options() as $filter_id => $filter_name) : ?>
						<option value="<?php echo esc_attr($filter_id); ?>" <?php selected($rule_data['fgf_applicable_categories_type'], $filter_id); ?>><?php echo esc_html($filter_name); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select Categories', 'free-gifts-for-woocommerce'); ?></label>
			</span>
			<span class='fgf-field'>
				<select class="fgf_include_categories fgf_product_filter fgf_select2" name="fgf_rule[fgf_include_categories][]" multiple="multiple">
					<?php
					foreach (fgf_get_wc_categories() as $category_id => $category_name) :
						$selected = ( in_array($category_id, $rule_data['fgf_include_categories']) ) ? ' selected="selected"' : '';
						?>
						<option value="<?php echo esc_attr($category_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($category_name); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Minimum Quantity', 'free-gifts-for-woocommerce'); ?>
					<?php fgf_wc_help_tip(__("The user's cart must contain the minimum quantity mentioned in this option which is the sum of the product(s) quantity that belongs to the selected categories.", 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<input type="number" class="fgf_product_filter fgf_include_category_product_count" name="fgf_rule[fgf_include_category_product_count]" min="1" value="<?php echo esc_attr($rule_data['fgf_include_category_product_count']); ?>"/>
			</span>
		</div>

		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select Categories', 'free-gifts-for-woocommerce'); ?></label>
			</span>
			<span class='fgf-field'>
				<select class="fgf_exclude_categories fgf_product_filter fgf_select2" name="fgf_rule[fgf_exclude_categories][]" multiple="multiple">
					<?php
					foreach (fgf_get_wc_categories() as $category_id => $category_name) :
						$selected = ( in_array($category_id, $rule_data['fgf_exclude_categories']) ) ? ' selected="selected"' : '';
						?>
						<option value="<?php echo esc_attr($category_id); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($category_name); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>

		<?php
		/**
		 * This hook is used to do extra action after rule product filter settings.
		 * 
		 * @since 9.2
		 */
		do_action('fgf_after_rule_product_filters_settings', $rule_data);
		?>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Restrict Free Gift(s) if cart contains', 'free-gifts-for-woocommerce'); ?></label>
			</span>
			<span class='fgf-field'>
				<select class="fgf_virtual_product_restriction" name="fgf_rule[fgf_virtual_product_restriction]">
					<?php foreach (fgf_rule_virtual_product_restriction_options() as $option_id => $option_name) : ?>
						<option value="<?php echo esc_attr($option_id); ?>" <?php selected($rule_data['fgf_virtual_product_restriction'], $option_id); ?>><?php echo esc_html($option_name); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>
	</div>
	<?php
	/**
	 * This hook is used to do extra action after rule filter settings.
	 * 
	 * @since 1.0
	 */
	do_action('fgf_after_rule_filters_settings', $rule_data);
	?>
</tbody>
</table>
</div>
<?php
