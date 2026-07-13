<?php
/**
 * Shortcodes.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<table class="form-table fgf_parameter_syntax widefat">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Syntax', 'free-gifts-for-woocommerce' ) ; ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?php esc_html_e( 'Syntax', 'free-gifts-for-woocommerce' ) ; ?></td>
			<td><?php esc_html_e( '[shortcode parameter1 = "value" parameter2 = "value" ]' ) ; ?></td>
		</tr>
	</tbody>
</table>

<h2><?php esc_html_e( 'Example', 'free-gifts-for-woocommerce' ) ; ?></h2>
<p><b>[fgf_gift_products type="carousel" mode="inline"]</b></p>
<p><b>[fgf_gift_products type="table" per_page="2"]</b></p>
<p><b>[fgf_cart_eligible_notices]</b></p>
<p><b>[fgf_progress_bar]</b></p>

<table class="form-table fgf_parameter_list widefat">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Parameters', 'free-gifts-for-woocommerce' ) ; ?></th>
			<th><?php esc_html_e( 'Value', 'free-gifts-for-woocommerce' ) ; ?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>type</td>
			<td>carousel, table, selectbox</td>
		</tr>
		<tr>
			<td>mode</td>
			<td>inline, popup</td>
		</tr>
		<tr>
			<td>per_page</td>
			<td>any number</td>
		</tr>
	</tbody>
</table>
<?php


