<?php
/**
 *  Rule filters data.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div class='fgf-field-wrapper'>
	<span class='fgf-field-title'>
		<label><?php esc_html_e('Select Groups', 'free-gifts-for-woocommerce'); ?></label>
	</span>
	<span class='fgf-field'>
		<select class='fgf_user_filter fgf_include_group_filter fgf_select2 fgf_user_filter-b2b_inculde_groups' name='fgf_rule[fgf_b2b_include_groups][]' multiple='multiple'>
			<?php
			foreach ($groups as $group_id => $group) :
				$selected = ( in_array($group->ID, $rule_data['fgf_b2b_include_groups']) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr($group->ID); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($group->post_title); ?></option>
			<?php endforeach; ?>
		</select>
	</span>
</div>

<div class='fgf-field-wrapper'>
	<span class='fgf-field-title'>
		<label><?php esc_html_e('Select Groups', 'free-gifts-for-woocommerce'); ?></label>
	</span>
	<span class='fgf-field'>
		<select class='fgf_user_filter fgf_exclude_group_filter fgf_select2 fgf_user_filter-b2b_exculde_groups' name='fgf_rule[fgf_b2b_exclude_groups][]' multiple='multiple'>
			<?php
			foreach ($groups as $group_id => $group) :
				$selected = ( in_array($group->ID, $rule_data['fgf_b2b_exclude_groups']) ) ? ' selected="selected"' : '';
				?>
				<option value="<?php echo esc_attr($group->ID); ?>"<?php echo esc_attr($selected); ?>><?php echo esc_html($group->post_title); ?></option>
			<?php endforeach; ?>
		</select>
	</span>
</div>
<?php
