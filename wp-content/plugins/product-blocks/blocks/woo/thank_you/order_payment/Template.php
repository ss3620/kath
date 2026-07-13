<?php
defined( 'ABSPATH' ) || exit;

$order_id = absint( get_query_var( 'order-received' ) );
$order    = wc_get_order( $order_id );

if ( $order && ! ( $order->has_status( 'failed' ) ) ) {
	?>
	<div class="wopb-thankyou-order-payment-container">
		<ul class="woocommerce-order-overview woocommerce-thankyou-order-details order_details">

			<li class="woocommerce-order-overview__order order">
				<?php echo esc_html( $attr['orderText'] ); ?>
				<strong><?php echo esc_html( $order->get_order_number() ); //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
			</li>
			<div class="wopb-separator"></div>

			<li class="woocommerce-order-overview__date date">
				<?php echo esc_html( $attr['dateText'] ); ?>
				<strong><?php echo wc_format_datetime( $order->get_date_created() ); //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
			</li>
			<div class="wopb-separator"></div>

			<?php if ( is_user_logged_in() && $order->get_user_id() === get_current_user_id() && $order->get_billing_email() ) : ?>
				<li class="woocommerce-order-overview__email email">
					<?php echo esc_html( $attr['emailText'] ); ?>
					<strong><?php echo esc_html( $order->get_billing_email() ); //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
				</li>
				<div class="wopb-separator"></div>
			<?php endif; ?>

			<li class="woocommerce-order-overview__total total">
				<?php echo esc_html( $attr['totalText'] ); ?>
				<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); //phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped ?></strong>
			</li>
			<div class="wopb-separator"></div>
			<?php if ( $order->get_payment_method_title() ) : ?>
				<li class="woocommerce-order-overview__payment-method method">
					<?php echo esc_html( $attr['payMethodText'] ); ?>
					<strong><?php echo wp_kses_post( $order->get_payment_method_title() ); ?></strong>
				</li>
			<?php endif; ?>
		</ul>
	</div>
	<?php
}
