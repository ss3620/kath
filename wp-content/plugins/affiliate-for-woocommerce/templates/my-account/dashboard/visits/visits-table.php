<?php
/**
 * My Account > Affiliate > Reports
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/my-account/dashboard/visits/
 * @since    8.37.0
 * @version  2.0.3
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Note: We do not recommend removing existing id & classes in HTML.

$visits_colspan = ( true === $is_show_user_agent_column ) ? 5 : 4;
?>
<table class="afwc_visits afwc-reports afwc-visits-table" aria-label="<?php echo esc_attr_x( 'Affiliate Visits', 'affiliate visitor table', 'affiliate-for-woocommerce' ); ?>">
	<thead>
		<?php
		if ( ! empty( $visits_headers ) && is_array( $visits_headers ) ) {
			echo '<tr>';
			foreach ( $visits_headers as $column_name => $column_label ) {
				?>
				<th class="<?php echo esc_attr( $column_name ); ?>"><?php echo esc_html( $column_label ); ?></th>
				<?php
			}
			echo '</tr>';
		}
		?>
	</thead>
	<tbody>
		<?php
		if ( ! empty( $visits_data ) && is_array( $visits_data ) && ! empty( $visits_data['rows'] ) && is_array( $visits_data['rows'] ) && ! empty( $visits_headers ) && is_array( $visits_headers ) ) {

			/**
			 * Action to output visitor  rows.
			 *
			 * @param array Array of visitor data.
			 *
			 * @since 8.5.0
			 */
			do_action(
				'afwc_visits_data',
				array(
					'visits_data'    => $visits_data,
					'visits_headers' => $visits_headers,
				)
			);
		} else {
			?>
			<tr>
				<td class="empty-table" colspan="<?php echo esc_attr( $visits_colspan ); ?>">
					<?php echo esc_html_x( 'No data to display', 'message to show when no visits data available', 'affiliate-for-woocommerce' ); ?>
				</td>
			</tr>
			<?php
		}
		?>
	</tbody>
	<?php if ( ! empty( $table_footer ) && true === $table_footer ) { ?>
	<tfoot>
		<tr>
			<td colspan="<?php echo esc_attr( $visits_colspan ); ?>">
				<div class="afwc-table-footer-container">
					<a class="afwc-back-button-wrapper" href="<?php echo esc_attr( esc_url( $dashboard_link ) ); ?>">
						<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
							<path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
						</svg>
						<span><?php echo esc_html_x( 'back to dashboard', 'Link to affiliate my account dashboard', 'affiliate-for-woocommerce' ); ?></span>
					</a>
					<?php if ( ! empty( $visits_data['total_count'] ) && ! empty( $visits_data ) && ( intval( $visits_data['total_count'] ) > count( $visits_data['rows'] ) ) ) { ?>
						<span class="afwc-visits-load-more-wrapper">
							<span class="afwc-loader" style="display: none;">
								<img src="<?php echo esc_url( WC()->plugin_url() ) . '/assets/images/wpspin-2x.gif'; ?>" class="afwc-table-loader" />
								<span><?php echo esc_html_x( 'Loading...', 'Visits table load more loading text', 'affiliate-for-woocommerce' ); ?></span>
							</span>
							<a id="afwc_load_more_visits" class="afwc-load-more-text" role="button" data-max_record="<?php echo esc_attr( $visits_data['total_count'] ); ?>">
								<span><?php echo esc_html_x( 'Load more', 'Visits table load more link text in my account', 'affiliate-for-woocommerce' ); ?></span>
							</a>
							<span class="afwc-no-load-more-text" style="display: none;"><?php echo esc_html_x( 'No more data to load', 'Text for no data to load', 'affiliate-for-woocommerce' ); ?></span>
						</span>
					<?php } ?>
				</div>
			</td>
		</tr>
	</tfoot>
	<?php } ?>
</table>
<?php
