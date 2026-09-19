<?php
/**
 * My Account > Affiliate > Reports
 *
 * @see      This template can be overridden by: https://woocommerce.com/document/affiliate-for-woocommerce/how-to-override-templates/
 * @package  affiliate-for-woocommerce/templates/my-account/dashboard/products/
 * @since    8.44.0
 * @version  1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Note: We do not recommend removing existing id & classes in HTML.

foreach ( $products['rows'] as $product ) {
	echo '<tr>';
	foreach ( $product_headers as $column_name => $column_label ) {
		?>
		<td class="<?php echo esc_attr( $column_name ); ?>" data-title="<?php echo esc_attr( $column_label ); ?>">
			<?php echo ! empty( $product[ $column_name ] ) ? wp_kses_post( $product[ $column_name ] ) : ''; ?>
		</td>
		<?php
	}
	echo '</tr>';
}
