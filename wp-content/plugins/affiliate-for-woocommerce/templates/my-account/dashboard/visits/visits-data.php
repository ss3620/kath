<?php
/**
 * My Account > Affiliate > Reports
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/my-account/dashboard/visits/
 * @since    8.44.0
 * @version  1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Note: We do not recommend removing existing id & classes in HTML.

$allowed_html = afwc_get_allowed_html_with_svg();

foreach ( $visits_data['rows'] as $visit ) {
	echo '<tr>';
	foreach ( $visits_headers as $column_name => $column_label ) {
		?>
		<td class="<?php echo esc_attr( $column_name ); ?>" data-title="<?php echo esc_attr( $column_label ); ?>">
			<?php
			if ( 'referring_url' === $column_name ) {
				echo ! empty( $visit[ $column_name ] ) ? '<a href="' . esc_url( $visit[ $column_name ] ) . '">' . esc_html( $visit[ $column_name ] ) . '</a>' : '-';
			} elseif ( 'is_converted' === $column_name ) {
				$is_converted = ! empty( $visit[ $column_name ] ) ? 'yes' : 'no';
				echo wp_kses( AFWC_Visits::afwc_get_is_converted_svg( $is_converted ), $allowed_html );
			} elseif ( 'user_agent_info' === $column_name && is_array( $visit[ $column_name ] ) && ! empty( $visit[ $column_name ] ) ) {
				?>
				<div class="afwc-visits-device-wrapper">
					<span class="afwc-visits-info-label"><?php echo esc_html_x( 'Device:', 'Device type label in visits dashboard', 'affiliate-for-woocommerce' ); ?></span>
					<span class="afwc-visits-info-value afwc-visits-device">
						<?php
						$device_type = ! empty( $visit[ $column_name ]['device_type'] ) ? $visit[ $column_name ]['device_type'] : '';
						echo wp_kses( AFWC_Visits::afwc_get_device_type_svg( $device_type ), $allowed_html );
						?>
					</span>
				</div>
				<div class="afwc-visits-browser-wrapper">
					<span class="afwc-visits-info-label"><?php echo esc_html_x( 'Browser:', 'Browser type label in visits dashboard', 'affiliate-for-woocommerce' ); ?></span>
					<span class="afwc-visits-info-value afwc-visits-browser">
						<?php
						$browser = ! empty( $visit[ $column_name ]['browser'] ) ? $visit[ $column_name ]['browser'] : '';
						echo ( ! empty( $browser ) && 'Unknown' !== $browser ) ? esc_html( $browser ) : '-';
						?>
					</span>
				</div>
				<div class="afwc-visits-os-wrapper">
					<span class="afwc-visits-info-label"><?php echo esc_html_x( 'OS:', 'OS type label in visits dashboard', 'affiliate-for-woocommerce' ); ?></span>
					<span class="afwc-visits-info-value afwc-visits-os">
						<?php
						$os = ! empty( $visit[ $column_name ]['os'] ) ? $visit[ $column_name ]['os'] : '';
						echo ( ! empty( $os ) && 'Unknown' !== $os ) ? esc_html( $os ) : '-';
						?>
					</span>
				</div>
				<div class="afwc-visits-country-wrapper">  
					<span class="afwc-visits-info-label"><?php echo esc_html_x( 'Country:', 'Country type label in visits dashboard', 'affiliate-for-woocommerce' ); ?></span>
					<?php
					if ( is_array( $visit['country'] ) && ! empty( $visit['country']['code'] ) ) {
						$country_title = ! empty( $visit['country']['name'] ) ? $visit['country']['name'] : $visit['country']['code'];
						?>
						<span
							class="afwc-visits-info-value afwc-visits-country"
							title="<?php echo esc_attr( $country_title ); ?>"
							data-country_name="<?php echo esc_attr( $visit['country']['name'] ); ?>"
							data-country_code="<?php echo esc_attr( $visit['country']['code'] ); ?>"
						>
							<?php echo esc_attr( $visit['country']['code'] ); ?>
						</span>
						<?php
					} else {
						echo '<span class="afwc-visits-info-value">-</span>';
					}
					?>
				</div>
				<?php
			} else {
				echo ! empty( $visit[ $column_name ] ) ? wp_kses_post( $visit[ $column_name ] ) : '';
			}
			?>
		</td>
		<?php
	}
	echo '</tr>';
}
