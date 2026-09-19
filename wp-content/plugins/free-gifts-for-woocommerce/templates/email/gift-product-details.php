<?php
/**
 * Gift product details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/free-gifts-for-woocommerce/emails/gift-product-details.php
 *
 * To maintain compatibility, Free Gifts for WooCommerce will update the template files and you have to copy the updated files to your theme
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
?>
<table class="fgf_gift_products_table">
	<tr>
		<th><?php esc_html_e( 'Product Name' , 'free-gifts-for-woocommerce' ) ; ?></th>
		<th><?php esc_html_e( 'Product Image' , 'free-gifts-for-woocommerce' ) ; ?></th>
		<th><?php esc_html_e( 'Quantity' , 'free-gifts-for-woocommerce' ) ; ?></th>
		<th><?php esc_html_e( 'Original Price' , 'free-gifts-for-woocommerce' ) ; ?></th>
		<th><?php esc_html_e( 'Your Price' , 'free-gifts-for-woocommerce' ) ; ?></th>
	</tr>
	<?php
	foreach ( $product_details as $product_detail ) :
		$product = wc_get_product( $product_detail[ 'product_id' ] ) ;
		?>
		<tr>
			<td><?php echo esc_html( $product_detail[ 'product_name' ] ) ; ?></td>
			<td><?php fgf_render_product_image( $product , array( 32, 32 ) ) ; ?></td>
			<td><?php echo esc_html( $product_detail[ 'quantity' ] ) ; ?></td>
			<td><?php fgf_price( $product_detail[ 'product_price' ] ) ; ?></td>
			<td><?php esc_html_e( 'Free' , 'free-gifts-for-woocommerce' ) ; ?></td>
		</tr>
	<?php endforeach ; ?>
</table>

