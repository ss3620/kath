<?php
/**
 * Content - Common short codes. 
 * 
 * @since 11.4.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div id='fgf-common-shortcode-content' class='fgf-shortcode-tab-content'>
	<div class='fgf-shortcode-description'><p><?php esc_html_e('You can use the shortcodes on any page.', 'free-gifts-for-woocommerce'); ?></p></div>
	
	<table class='form-table fgf-form-table widefat striped fgf-common-shortcode-table'>
		<thead>
			<tr>
				<th><?php esc_html_e('Shortcode', 'free-gifts-for-woocommerce'); ?></th>
				<th><?php esc_html_e('Parameter Support', 'free-gifts-for-woocommerce'); ?></th>
				<th><?php esc_html_e('Description', 'free-gifts-for-woocommerce'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$shortcodes = FGF_Shortcode_Tab::get_common_shortcodes();
			if (fgf_check_is_array($shortcodes)) :
				foreach ($shortcodes as $shortcode => $shortcode_details) :
					?>
					<tr>
						<td><b><?php echo esc_html($shortcode); ?></b></td>
						<td><?php echo esc_html($shortcode_details['supported_parameters']); ?></td>
						<td><?php echo esc_html($shortcode_details['usage']); ?></td>
					</tr>
					<?php
				endforeach;
			endif;
			?>
		</tbody>
	</table>
</div>
<?php
