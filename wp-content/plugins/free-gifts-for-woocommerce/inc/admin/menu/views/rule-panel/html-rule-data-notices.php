<?php
/**
 * Panel- Notices.
 * 
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div id='fgf_rule_data_notices' class='fgf-rule-options-wrapper'>
	<div class='fgf-rule-eligible-notices-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Eligibility Notice Configuration', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-rule-fields-section'>
			<?php
			/**
			 * This hook is used to do extra action before rule notices settings.
			 *
			 * @since 1.0.0
			 */
			do_action('fgf_before_rule_notices_settings', $rule_data);
			?>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Display Free Gift Eligibility Notice in Cart for this Rule', 'free-gifts-for-woocommerce'); ?>
						<?php fgf_wc_help_tip(__('When set to "Show", a notice will be displayed to user in cart and checkout if they are not eligible for receiving free gifts from this rule.', 'free-gifts-for-woocommerce')); // phpcs:ignore WordPress.XSS.EscapeOutput.OutputNotEscaped. ?>
					</label>
				</span>
				<span class='fgf-field'>
					<select name='fgf_rule[fgf_show_notice]' class='fgf_rule_show_notice'>
						<option value='1' <?php selected($rule_data['fgf_show_notice'], '1'); ?>><?php esc_html_e('Hide', 'free-gifts-for-woocommerce'); ?></option>
						<option value='2' <?php selected($rule_data['fgf_show_notice'], '2'); ?>><?php esc_html_e('Show', 'free-gifts-for-woocommerce'); ?></option>
					</select>
				</span>
			</div>

			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Free Gifts Eligibility Notice', 'free-gifts-for-woocommerce'); ?></label>
				</span>
				<span class='fgf-field'>
					<textarea name="fgf_rule[fgf_notice]" cols="20" rows="5" class="fgf_rule_notice"><?php echo wp_kses_post($rule_data['fgf_notice']); ?></textarea>
				</span>
			</div>

			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Image/Icon for Eligibility Notice', 'free-gifts-for-woocommerce'); ?></label>
				</span>
				<span class='fgf-field'>
					<span class='fgf-upload-img-container fgf_rule_notice'>
						<input type='hidden' class='fgf-upload-img-url' name='fgf_rule[fgf_notice_image_id]' value="<?php echo esc_attr($rule_data['fgf_notice_image_id']); ?>"/>
						<?php $image_url = ( $rule_data['fgf_notice_image_id'] ) ? wp_get_attachment_image_url($rule_data['fgf_notice_image_id']) : ''; ?>
						<div class='fgf-uploaded-img-wrapper'>
							<img id='target' src="<?php echo esc_url($image_url); ?>" class='fgf-uploaded-img-preview'/>
						</div>
						<button type='button' class='fgf-upload-img'><?php esc_html_e('Select a Image', 'free-gifts-for-woocommerce'); ?></button>
						<button type='button' class='fgf-delete-uploaded-img<?php echo ( '' == $image_url ) ? esc_attr(' fgf-hide') : ''; ?>'><?php esc_html_e('Delete Image', 'free-gifts-for-woocommerce'); ?></button>
					</span>
				</span>
			</div>

			<?php
			/**
			 * This hook is used to do extra action after rule notices settings.
			 *
			 * @since 1.0
			 */
			do_action('fgf_after_rule_notices_settings', $rule_data);
			?>
		</div>
	</div>
	<div class='fgf-bogo-rule-notice-fields-wrapper fgf-rule-notice-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Eligibility Notice Configuration - Buy X Get Y', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-rule-fields-section'>
			<p><?php esc_html_e('Please make sure whether the Buy X Get Y Eligible Notice - Single Product Page and Buy X Get Y Eligible Notice - Product Archive Pages[Like Category, Shop] were enabled under the Display settings[Free Gifts -> Settings -> Display].', 'free-gifts-for-woocommerce'); ?></p>
		<?php
			/**
			 * This hook is used to do extra action before BOGO rule notices settings.
			 *
			 * @since 11.9.0
			 */
			do_action('fgf_before_bogo_rule_notices_settings', $rule_data);
		?>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Display Eligible Notices for Buy X Get Y', 'free-gifts-for-woocommerce'); ?>
						<?php fgf_wc_help_tip(__('When set to "Show", a notice will be displayed to users in the Single Product page and Product Archive pages if they are not eligible to receive free gifts from this rule.', 'free-gifts-for-woocommerce')); ?>
					</label>
				</span>
				<span class='fgf-field'>
					<select name='fgf_rule[fgf_show_bogo_eligible_notice]' class='fgf-rule-show-bogo-eligible-notice-field'>
						<option value='1' <?php selected($rule_data['fgf_show_bogo_eligible_notice'], '1'); ?>><?php esc_html_e('Hide', 'free-gifts-for-woocommerce'); ?></option>
						<option value='2' <?php selected($rule_data['fgf_show_bogo_eligible_notice'], '2'); ?>><?php esc_html_e('Show', 'free-gifts-for-woocommerce'); ?></option>
					</select>
				</span>
			</div>

		<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Eligibility Notice - Content', 'free-gifts-for-woocommerce'); ?></label>
				</span>
				<span class='fgf-field'>
					<textarea name="fgf_rule[fgf_bogo_eligible_notice]" cols="20" rows="5" class='fgf-rule-bogo-eligible-notice-field'><?php echo wp_kses_post($rule_data['fgf_bogo_eligible_notice']); ?></textarea>
				</span>
			</div>
			<?php
			/**
			 * This hook is used to do extra action after BOGO rule notices settings.
			 *
			 * @since 11.9.0
			 */
			do_action('fgf_after_bogo_rule_notices_settings', $rule_data);
			?>
</div>
</div>
<div class='fgf-bulk-rule-notice-fields-wrapper fgf-rule-notice-fields-wrapper fgf-rule-fields-section'>
		<h2><?php esc_html_e('Eligibility Notice Configuration - Bulk', 'free-gifts-for-woocommerce'); ?></h2>
		<div class='fgf-rule-fields-section'>
			<p><?php esc_html_e('The notices are displayed based on next eligible notices by valid rules.', 'free-gifts-for-woocommerce'); ?></p>
		<?php
			/**
			 * This hook is used to do extra action before bulk rule notices settings.
			 *
			 * @since 12.0.0
			 */
			do_action('fgf_before_bulk_rule_notices_settings', $rule_data);
		?>
			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Display Eligible Notices for Bulk', 'free-gifts-for-woocommerce'); ?>
						<?php fgf_wc_help_tip(__('When set to "Show", a notice will be displayed to users in the cart page and checkout page if they are not eligible to receive free gifts from this rule.', 'free-gifts-for-woocommerce')); ?>
					</label>
				</span>
				<span class='fgf-field'>
					<select name='fgf_rule[fgf_show_bulk_eligible_notice]' class='fgf-rule-show-bulk-eligible-notice-field'>
						<option value='1' <?php selected($rule_data['fgf_show_bulk_eligible_notice'], '1'); ?>><?php esc_html_e('Hide', 'free-gifts-for-woocommerce'); ?></option>
						<option value='2' <?php selected($rule_data['fgf_show_bulk_eligible_notice'], '2'); ?>><?php esc_html_e('Show', 'free-gifts-for-woocommerce'); ?></option>
					</select>
				</span>
			</div>
			
		<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Eligibility Notice - Content', 'free-gifts-for-woocommerce'); ?></label>
				</span>
				<span class='fgf-field'>
					<textarea name="fgf_rule[fgf_bulk_eligible_notice]" cols="20" rows="5" class='fgf-rule-bulk-eligible-notice-field'><?php echo wp_kses_post($rule_data['fgf_bulk_eligible_notice']); ?></textarea>
				</span>
			</div>

			<div class='fgf-field-wrapper'>
				<span class='fgf-field-title'>
					<label><?php esc_html_e('Image/Icon for Eligibility Notice', 'free-gifts-for-woocommerce'); ?></label>
				</span>
				<span class='fgf-field'>
					<span class='fgf-upload-img-container fgf-rule-bulk-eligible-notice-field'>
						<input type='hidden' class='fgf-upload-img-url' name='fgf_rule[fgf_bulk_notice_image_id]' value="<?php echo esc_attr($rule_data['fgf_bulk_notice_image_id']); ?>"/>
						<?php $image_url = ( $rule_data['fgf_bulk_notice_image_id'] ) ? wp_get_attachment_image_url($rule_data['fgf_bulk_notice_image_id']) : ''; ?>
						<div class='fgf-uploaded-img-wrapper'>
							<img id='target' src="<?php echo esc_url($image_url); ?>" class='fgf-uploaded-img-preview'/>
						</div>
						<button type='button' class='fgf-upload-img'><?php esc_html_e('Select a Image', 'free-gifts-for-woocommerce'); ?></button>
						<button type='button' class='fgf-delete-uploaded-img<?php echo ( '' == $image_url ) ? esc_attr(' fgf-hide') : ''; ?>'><?php esc_html_e('Delete Image', 'free-gifts-for-woocommerce'); ?></button>
					</span>
				</span>
			</div>
			<?php
			/**
			 * This hook is used to do extra action after bulk rule notices settings.
			 *
			 * @since 12.0.0
			 */
			do_action('fgf_after_bulk_rule_notices_settings', $rule_data);
			?>
</div>
</div>
	<div class='fgf-rule-fields-section fgf-shortcode-wrapper'>
		<h3><?php esc_html_e('Shortcodes', 'free-gifts-for-woocommerce'); ?></h3>
		<div class='fgf-shortcode-tabs-wrapper'>
		<?php foreach (fgf_get_rule_notice_shortcode_tabs() as $tab_key => $tab_name) : ?>
			<button class='fgf-shortcode-tab active' href='#fgf-<?php echo esc_attr($tab_key); ?>-shortcode-content'><?php echo esc_html($tab_name); ?></button>
		<?php endforeach; ?>
		</div>

		<div id='fgf-eligible-notice-shortcode-content' class='fgf-shortcode-tab-content'>
		<div class='fgf-shortcode-description'><p><?php esc_html_e('You can use the shortcodes on eligible notices.', 'free-gifts-for-woocommerce'); ?></p></div>
		<table class='fgf-shortcode-table'>
			<?php
			if (fgf_check_is_array(fgf_get_rule_notice_shortcode_details())) :
				foreach (fgf_get_rule_notice_shortcode_details() as $short_detail) :
					?>
					<tr>
						<th><?php echo esc_html($short_detail['shortcode']); ?></th>
						<td><?php echo esc_html($short_detail['desc']); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</table>
		</div>

		<div id='fgf-bogo-eligible-notice-shortcode-content' class='fgf-shortcode-tab-content'>
		<div class='fgf-shortcode-description'><p><?php esc_html_e('You can use the shortcodes on BOGO eligible notices.', 'free-gifts-for-woocommerce'); ?></p></div>
		<table class='fgf-shortcode-table'>
			<?php
			if (fgf_check_is_array(fgf_get_bogo_rule_notice_shortcode_details())) :
				foreach (fgf_get_bogo_rule_notice_shortcode_details() as $short_detail) :
					?>
					<tr>
						<th><?php echo esc_html($short_detail['shortcode']); ?></th>
						<td><?php echo esc_html($short_detail['desc']); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</table>
		</div>

		<div id='fgf-bulk-eligible-notice-shortcode-content' class='fgf-shortcode-tab-content'>
		<div class='fgf-shortcode-description'><p><?php esc_html_e('You can use the shortcodes on Bulk eligible notices.', 'free-gifts-for-woocommerce'); ?></p></div>
		<table class='fgf-shortcode-table'>
			<?php
			if (fgf_check_is_array(fgf_get_bulk_rule_notice_shortcode_details())) :
				foreach (fgf_get_bulk_rule_notice_shortcode_details() as $short_detail) :
					?>
					<tr>
						<th><?php echo esc_html($short_detail['shortcode']); ?></th>
						<td><?php echo esc_html($short_detail['desc']); ?></td>
					</tr>
				<?php endforeach; ?>
			<?php endif; ?>
		</table>
		</div>
	</div>
</div>
<?php
