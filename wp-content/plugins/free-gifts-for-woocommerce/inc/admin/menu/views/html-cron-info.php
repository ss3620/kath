<?php
/**
 * Cron information.
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
?>
<table class="form-table fgf-cron-info widefat striped">
	<thead>
		<tr>
			<th><?php esc_html_e('Cron Name', 'free-gifts-for-woocommerce'); ?></th>
			<th><?php esc_html_e('Last Updated', 'free-gifts-for-woocommerce'); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		if (fgf_check_is_array($server_cron_info)) {
			foreach ($server_cron_info as $key => $values) {
				?>
				<tr>
					<td><?php echo esc_html($values['cron']); ?></td>
					<td><?php echo esc_html($values['last_updated_date']); ?></td>
				</tr>
				<?php
			}
		}
		?>
	</tbody>
</table>
<?php


