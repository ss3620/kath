<?php
/**
 * General - Cheapest Gifts
 * 
 * @since 11.4.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-rule-cheapest-gifts-fields-wrapper fgf-rule-general-fields-wrapper'>
	<?php
	/**
	 * This hook is used to do extra action before rule cheapest gifts settings.
	 *
	 * @since 11.4.0
	 */
	do_action('fgf_before_rule_cheapest_gifts_settings', $rule_data);
	?>
	<div class='fgf-rule-cheapest-gifts-price-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Price Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Cheapest Price is considered based on', 'free-gifts-for-woocommerce'); ?></label>
			</span>
			<span class='fgf-field'>
				<select name='fgf_rule[fgf_cheapest_gift_price_type]' class='fgf-cheapest-gifts-price-type-field'>
					<?php
					foreach (fgf_get_rule_cheapest_gift_price_type_options() as $type_key => $type_label) :
						?>
						<option value="<?php echo esc_attr($type_key); ?>" <?php selected($rule_data['fgf_cheapest_gift_price_type'], $type_key); ?>><?php echo esc_html($type_label); ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>
	</div>
	<div class='fgf-rule-cheapest-gifts-quantity-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Quantity Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-field-wrapper'>
			<span class='fgf-field-title'>
				<label><?php esc_html_e('Quantity for Gift Product(s)', 'free-gifts-for-woocommerce'); ?><span class='required'>*</span></label>
			</span>
			<span class='fgf-field'>
				<input type="number" class='fgf-cheapest-gift-quantity-field' name='fgf_rule[fgf_cheapest_gift_quantity]' min='1' step='1' value="<?php echo esc_attr($rule_data['fgf_cheapest_gift_quantity']); ?>"/>
			</span>
		</div>
	</div>
	<?php
	/**
	 * This hook is used to do extra action after rule cheapest gifts settings.
	 *
	 * @since 11.4.0
	 */
	do_action('fgf_after_rule_cheapest_gifts_settings', $rule_data);
	?>
</div>
<?php
