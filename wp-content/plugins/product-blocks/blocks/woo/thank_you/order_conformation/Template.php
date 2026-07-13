<?php
defined( 'ABSPATH' ) || exit;

$order_id = absint( get_query_var( 'order-received' ) );
$order    = wc_get_order( $order_id );

if ( $order ) {
	do_action( 'woocommerce_before_thankyou', $order->get_id() );
	?>
	<div class="wopb-thankyou-order-conformation-container">
		<?php if ( $attr['showHead'] ) { ?>
			<div class="wopb-order-heading-section" >
				<div class="wopb-order-heading"><?php echo esc_html( $attr['orderHeadText'] . $order->get_order_number() ); ?> </div>
			</div>
			<?php
		}
		if ( $order->has_status( 'failed' ) ) {
			?>
			<div class="wopb-thankyou-order-fail">
				<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed"><?php esc_html_e( 'Unfortunately your order cannot be processed as the originating bank/merchant has declined your transaction. Please attempt your purchase again.', 'product-blocks' ); ?></p>

				<p class="woocommerce-notice woocommerce-notice--error woocommerce-thankyou-order-failed-actions">
					<a href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>" class="button pay"><?php esc_html_e( 'Pay', 'product-blocks' ); ?></a>
					<?php if ( is_user_logged_in() ) : ?>
						<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="button pay"><?php esc_html_e( 'My account', 'product-blocks' ); ?></a>
					<?php endif; ?>
				</p>
			</div>

			<?php
		} elseif ( $attr['showMessage'] ) {
			?>
				<div class="wopb-order-message-section" >
					<div class="wopb-order-message"> <?php echo esc_html( $attr['messageText'] ); ?></div>
				</div>
				<?php

		}
		?>
	</div>
	<?php
} else {
	?>
	<p class="woocommerce-notice woocommerce-notice--success woocommerce-thankyou-order-received"><?php echo apply_filters( 'woocommerce_thankyou_order_received_text', esc_html__( 'Thank you. Your order has been received.', 'product-blocks' ), null );//phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
	<?php
}
