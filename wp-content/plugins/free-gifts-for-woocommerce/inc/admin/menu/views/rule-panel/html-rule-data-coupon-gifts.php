<?php
/**
 * General - Coupon Gifts
 * 
 * @since 11.4.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-rule-coupon-gifts-fields-wrapper fgf-rule-general-fields-wrapper'>
	<?php
	/**
	 * This hook is used to do extra action before rule coupon gifts settings.
	 *
	 * @since 11.4.0
	 */
	do_action('fgf_before_rule_coupon_gifts_settings', $rule_data);
	?>
	<div class='fgf-rule-coupon-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Coupon Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select the Coupon', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
			</span>
			<span class='fgf-field'>
				<?php
				fgf_select2_html(array(
					'class' => 'fgf_apply_coupon fgf_rule_type fgf_coupon_rule_type',
					'name' => 'fgf_rule[fgf_apply_coupon]',
					'list_type' => 'coupons',
					'action' => 'fgf_json_search_coupons',
					'multiple' => false,
					'placeholder' => __('Search a Coupon', 'free-gifts-for-woocommerce'),
					'options' => $rule_data['fgf_apply_coupon'],
				));
				?>
			</span>
		</div>
	</div>

	<div class='fgf-rule-coupon-gifts-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Gift Product(s) Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Select Product(s)', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span>
					<?php fgf_wc_help_tip(__("The selected Product(s) will be added to the user's cart once the coupon applied", 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
				</label>
			</span>
			<span class='fgf-field'>
				<?php
				fgf_select2_html(array(
					'class' => 'fgf_coupon_gift_products fgf_rule_type fgf_coupon_rule_type',
					'name' => 'fgf_rule[fgf_coupon_gift_products]',
					'list_type' => 'products',
					'action' => 'fgf_json_search_products_and_variations',
					'exclude_global_variable' => 'yes',
					'display_stock' => 'yes',
					'placeholder' => __('Search a Product', 'free-gifts-for-woocommerce'),
					'options' => $rule_data['fgf_coupon_gift_products'],
				));
				?>
			</span>
		</div>
	</div>

	<div class='fgf-rule-coupon-gifts-quantity-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Gift Quantity Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Quantity for Selected Free Gift Product(s)', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
			</span>
			<span class='fgf-field'>
				<input type='number' class='fgf_rule_type fgf_coupon_gift_products_qty fgf_coupon_rule_type' name='fgf_rule[fgf_coupon_gift_products_qty]' min='1' value="<?php echo esc_attr($rule_data['fgf_coupon_gift_products_qty']); ?>"/>
			</span>
		</div>
	</div>
	<?php
	/**
	 * This hook is used to do extra action after rule coupon gifts settings.
	 *
	 * @since 11.4.0
	 */
	do_action('fgf_after_rule_coupon_gifts_settings', $rule_data);
	?>
</div>
<?php
