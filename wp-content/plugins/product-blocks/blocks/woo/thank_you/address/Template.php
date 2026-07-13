<?php
/**
 * @package WooCommerce\Templates
 * @version 3.8.0
 */

defined( 'ABSPATH' ) || exit;

$order_id = absint( get_query_var( 'order-received' ) );
$order    = wc_get_order( $order_id );

$show_shipping = ! wc_ship_to_billing_address_only() && $order->needs_shipping_address();

if ( ! $order ) {
	return;
}
?>
<div class="wopb-thankyou-address-container">
	<section class="woocommerce-customer-details">
		<div class="woocommerce-column--billing-address">
			<div class="wopb-billing-shipping-address">
				<h2 class="woocommerce-column__title wopb-address-title "><?php echo esc_html( $attr['billingText'] ); ?></h2>
				<address>
					<?php echo wp_kses_post( $order->get_formatted_billing_address( esc_html__( 'N/A', 'product-blocks' ) ) ); ?>

					<?php if ( $order->get_billing_phone() ) : ?>
						<p class="woocommerce-customer-details--phone"><?php echo esc_html( $order->get_billing_phone() ); ?></p>
					<?php endif; ?>

					<?php if ( $order->get_billing_email() ) : ?>
						<p class="woocommerce-customer-details--email"><?php echo esc_html( $order->get_billing_email() ); ?></p>
					<?php endif; ?>
				</address>
			</div>
			
		</div>

		<?php if ( $show_shipping ) { ?>
		<div class="woocommerce-column--shipping-address">
			<div class="wopb-billing-shipping-address">
				<h2 class="woocommerce-column__title wopb-address-title "><?php echo esc_html( $attr['shippingText'] ); ?></h2>
				<address>
					<?php echo wp_kses_post( $order->get_formatted_shipping_address( esc_html__( 'N/A', 'product-blocks' ) ) ); ?>

					<?php if ( $order->get_shipping_phone() ) : ?>
						<p class="woocommerce-customer-details--phone"><?php echo esc_html( $order->get_shipping_phone() ); ?></p>
					<?php endif; ?>
				</address>
			</div>
			
		</div>
		<?php } ?>
	</section>
	<?php do_action( 'woocommerce_order_details_after_customer_details', $order ); ?>
</div>
<?php
	remove_action( 'woocommerce_thankyou', 'woocommerce_order_details_table' );
	do_action( 'woocommerce_thankyou', $order->get_id() );
?>
