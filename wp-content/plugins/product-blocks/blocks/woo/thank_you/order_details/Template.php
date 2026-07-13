<?php
/**
 * @package WooCommerce\Templates
 * @version 3.8.0
 */
defined( 'ABSPATH' ) || exit;

$order_id = absint( get_query_var( 'order-received' ) );
$order    = wc_get_order( $order_id );

$order_items           = $order->get_items( apply_filters( 'woocommerce_purchase_order_item_types', 'line_item' ) );
$show_purchase_note    = $order->has_status( apply_filters( 'woocommerce_purchase_note_order_statuses', array( 'completed', 'processing' ) ) );
$show_customer_details = is_user_logged_in() && $order->get_user_id() === get_current_user_id();
$downloads             = $order->get_downloadable_items();
$show_downloads        = $order->has_downloadable_item() && $order->is_download_permitted();

if ( ! $order ) {
	return;
}
?>

<div class="wopb-thankyou-order-details-container">

<!-- order downloads -->
<?php if ( $show_downloads ) { ?>
	<section class="woocommerce-order-downloads">
		<h2 class="woocommerce-order-downloads__title">
			<?php
				// phpcs:ignore WordPress.CodeAnalysis.EscapedNotTranslated.Found
				echo esc_html( $attr['downloadText'] );
			?>
		</h2>
		<table class="woocommerce-table woocommerce-table--order-downloads shop_table shop_table_responsive order_details">
			<thead>
				<tr>
					<?php foreach ( wc_get_account_downloads_columns() as $column_id => $column_name ) : ?>
					<th class="<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
					<?php endforeach; ?>
				</tr>
			</thead>

			<?php foreach ( $downloads as $download ) : ?>
				<tr>
					<?php foreach ( wc_get_account_downloads_columns() as $column_id => $column_name ) : ?>
						<td class="<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
							<?php
							if ( has_action( 'woocommerce_account_downloads_column_' . $column_id ) ) {
								do_action( 'woocommerce_account_downloads_column_' . $column_id, $download );
							} else {
								switch ( $column_id ) {
									case 'download-product':
										if ( $download['product_url'] ) {
											echo '<a href="' . esc_url( $download['product_url'] ) . '">' . esc_html( $download['product_name'] ) . '</a>';
										} else {
											echo esc_html( $download['product_name'] );
										}
										break;
									case 'download-file':
										echo '<a href="' . esc_url( $download['download_url'] ) . '" class="woocommerce-MyAccount-downloads-file button alt">' . esc_html( $download['download_name'] ) . '</a>';
										break;
									case 'download-remaining':
										echo is_numeric( $download['downloads_remaining'] ) ? esc_html( $download['downloads_remaining'] ) : esc_html__( '&infin;', 'product-blocks' );
										break;
									case 'download-expires':
										if ( ! empty( $download['access_expires'] ) ) {
											echo '<time datetime="' . esc_attr( gmdate( 'Y-m-d', strtotime( $download['access_expires'] ) ) ) . '" title="' . esc_attr( strtotime( $download['access_expires'] ) ) . '">' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $download['access_expires'] ) ) ) . '</time>';
										} else {
											esc_html_e( 'Never', 'product-blocks' );
										}
										break;
								}
							}
							?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</table>
	</section>
<?php } ?> 

<!-- Order Details  -->
<section class="woocommerce-order-details">
	<?php do_action( 'woocommerce_order_details_before_order_table', $order ); ?>

	<h2 class="woocommerce-order-details__title"><?php echo esc_html( $attr['orderDetailsText'] ); ?></h2>

	<table class="woocommerce-table woocommerce-table--order-details shop_table order_details">

		<thead>
			<tr>
				<th class="woocommerce-table__product-name product-name"><?php echo esc_html( $attr['productText'] ); ?></th>
				<th class="woocommerce-table__product-table product-total"><?php echo esc_html( $attr['totalText'] ); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php
			do_action( 'woocommerce_order_details_before_order_table_items', $order );

			foreach ( $order_items as $item_id => $item ) {
				$product = $item->get_product();

				wc_get_template(
					'order/order-details-item.php',
					array(
						'order'              => $order,
						'item_id'            => $item_id,
						'item'               => $item,
						'show_purchase_note' => $show_purchase_note,
						'purchase_note'      => $product ? $product->get_purchase_note() : '',
						'product'            => $product,
					)
				);
			}

			do_action( 'woocommerce_order_details_after_order_table_items', $order );
			?>
		</tbody>

		<tfoot>
			<?php

			foreach ( $order->get_order_item_totals() as $key => $total ) {
				if ( $key == 'cart_subtotal' ) {
					$total['label'] = $attr['subTotalText'];
				} elseif ( $key == 'shipping' ) {
					$total['label'] = $attr['shippingText'];
				} elseif ( $key == 'payment_method' ) {
					$total['label'] = $attr['payMethodText'];
				} elseif ( $key == 'order_total' ) {
					$total['label'] = $attr['footTotalText'];
				}
				?>
					<tr>
						<th scope="row"><?php echo esc_html( $total['label'] ); ?></th>
						<td><?php echo ( 'payment_method' === $key ) ? esc_html( $total['value'] ) : wp_kses_post( $total['value'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
					</tr>
				<?php
			}
			?>
			<?php if ( $order->get_customer_note() ) : ?>
				<tr>
					<th><?php esc_html_e( 'Note:', 'product-blocks' ); ?></th>
					<td><?php echo wp_kses_post( nl2br( wptexturize( $order->get_customer_note() ) ) ); ?></td>
				</tr>
			<?php endif; ?>
		</tfoot>
	</table>
	<?php do_action( 'woocommerce_order_details_after_order_table', $order ); ?>
</section>

</div>
