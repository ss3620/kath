<?php
/**
 * Content - Parameter short codes.  
 * 
 * @since 11.4.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<div id='fgf-parameters-shortcode-content' class='fgf-shortcode-tab-content'>
	<div class='fgf-shortcode-description'><p><?php esc_html_e('You can use the below-listed parameters for the shortcodes which support the parameters.', 'free-gifts-for-woocommerce'); ?></p></div>
	<table class='form-table widefat striped fgf-form-table fgf-shortcode-parameter-table'>
		<thead>
			<tr>
				<th><?php esc_html_e('Parameters', 'free-gifts-for-woocommerce'); ?></th>
				<th><?php esc_html_e('Value', 'free-gifts-for-woocommerce'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			$shortcodes = FGF_Shortcode_Tab::get_shortcode_parameter_values();
			if (fgf_check_is_array($shortcodes)) :
				foreach ($shortcodes as $parameter => $parameter_value) :
					?>
					<tr>
						<td><b><?php echo esc_html($parameter); ?></b></td>
						<td><?php echo esc_html($parameter_value); ?></td>
					</tr>
					<?php
				endforeach;
			endif;
			?>
		</tbody>
	</table>
</div>   
<?php
