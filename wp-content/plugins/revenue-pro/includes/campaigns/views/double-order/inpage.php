<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * The Template for displaying revenuex view
 *
 * @package RevenueX
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$offers               = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
$campaign_id          = $campaign['id'];
$checkbox_key         = "revenue_double_order_checkbox_{$campaign_id}";
$saved_index          = WC()->session->get( $checkbox_key );
$is_all_products      = 'all_products' === $campaign['campaign_trigger_type'];
$cart_items           = WC()->cart->get_cart();
$generated_styles     = revenue()->campaign_style_generator( 'inpage', $campaign, $placement );
$checkbox_items_key   = "revenue_double_order_checkbox_products_{$campaign_id}";
$selected_product_ids = WC()->session->get( $checkbox_items_key ) ?? array();

$double_order_session = WC()->session->get( 'revenue_double_order_session_data', array() );
$double_order_session = isset( $double_order_session[ $campaign_id ] ) ? $double_order_session[ $campaign_id ] : array();

$container_style = revenue()->get_style( $generated_styles, 'container' );


$animation_duration = isset( $campaign['double_order_animation_delay_between'] ) ? $campaign['double_order_animation_delay_between'] : 0;

if ( $is_all_products ) {
	$success_message = isset( $campaign['double_order_success_message'] ) ? $campaign['double_order_success_message'] : __(
		'Congrats! You saved {discount_value} on your order!',
		'revenue-pro'
	);

} else {
	$success_message = isset( $campaign['double_order_success_message'] ) ? $campaign['double_order_success_message'] : __(
		'Congrats! You saved {discount_value} on your order!',
		'revenue-pro'
	);
}


$animation_name = isset( $campaign['double_order_animation_enabled'], $campaign['double_order_animation_type'] ) && 'yes' === $campaign['double_order_animation_enabled']
? 'revx-double-order-animation revx-double-order-animation-' . $campaign['double_order_animation_type']
: '';

$cart_item_ids = array();
foreach ( $cart_items as $key => $value ) {
	$cart_item_ids[] = $value['variation_id'] ? $value['variation_id'] : $value['product_id'];
}


echo '<div class="revx-double-order-container" 
    style="--revx-double-order-animation-delay-between: ' . esc_attr( $animation_duration ) . 's; 
           --animation-active-time: 1s; ' . esc_attr( $container_style ) . '" 
    data-campaign-id="' . esc_attr( $campaign['id'] ) . '" 
    data-selected-index="' . esc_attr( $saved_index ) . '">';

echo wp_kses(
	revenue()->get_template_part(
		'double_order_countdown_timer',
		array(
			'generated_styles' => $generated_styles,
			'current_campaign' => $campaign,
		)
	),
	revenue()->get_allowed_tag()
);

$backup_message = $success_message;
foreach ( $offers as $idx => $offer ) {
	$quantity      = $offer['quantity'];
	$discount_type = $offer['type'];
	$value         = $offer['value'];
	$message       = '';
	$message       = isset( $offer['offer_text'] ) ? $offer['offer_text'] : '';

	$success_message = $backup_message;

	switch ( $discount_type ) {
		case 'percentage':
			$message         = str_replace( '{discount_value}', $value . '%', $message );
			$message         = str_replace( '{quantity}', $quantity, $message );
			$success_message = str_replace( '{discount_value}', $value . '%', $success_message );


			break;
		case 'fixed_discount':
			$message         = str_replace( '{discount_value}', wc_price( $value ), $message );
			$message         = str_replace( '{quantity}', $quantity, $message );
			$success_message = str_replace( '{discount_value}', wc_price( $value * $quantity ), $success_message );

			break;
		case 'no_discount':
			break;
		default:
			break;
	}

	$success_message = str_replace( '{quantity}', $quantity, $success_message );


	if ( $is_all_products ) {


		$wrapper_style = revenue()->get_style( $generated_styles, 'DoubleOrderAllProductWrapper' );

		$saved_index = -1;
		if ( isset( $double_order_session['index'] ) ) {
			$saved_index = $double_order_session['index'];
		}
		$is_selected = $saved_index === $idx;
		$is_show     = 0 === $idx || ( -1 !== $saved_index && $saved_index + 1 >= $idx );


		?>
		<div style="<?php echo esc_attr( $wrapper_style ); ?>" class="revx-products-lists 
							   <?php
								echo esc_attr( ! $is_show ? ' hidden ' : '' );
								echo esc_attr( $animation_name );
								?>
		" data-index="<?php echo esc_attr( $idx ); ?>">
			<div id="revx-double-order-checkbox-<?php echo esc_attr( $idx ); ?>" class="revx-double-order-checkbox-label">
				<?php

				$checkbox_selected_style = revenue()->get_style( $generated_styles, 'AllProductCheckboxSelected' );
				$checkbox_default_style  = revenue()->get_style( $generated_styles, 'AllProductCheckboxDefault' );
				$cur_style               = $is_selected ? $checkbox_selected_style : $checkbox_default_style;

				?>

				<div
					data-multiplier="<?php echo esc_attr( $quantity ); ?>"
					data-type="<?php echo esc_attr( $discount_type ); ?>"
					data-value="<?php echo esc_attr( $value ); ?>"
					data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>"
					data-index="<?php echo esc_attr( $idx ); ?>"
					data-is-checked="<?php echo esc_attr( $is_selected ? 'yes' : 'no' ); ?>"
					data-default-style="<?php echo esc_attr( $checkbox_default_style ); ?>" data-selected-style="<?php echo esc_attr( $checkbox_selected_style ); ?>" class="revx-builder-checkbox revx-justify-center revx-double-order-checkbox" style="<?php echo esc_attr( $cur_style ); ?>">
					<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 12 12">
						<path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9.75 3.75 5 8.5 2.625 6.125"></path>
					</svg>
				</div>


				<div class="revx-products-list-header" >
					<?php
					echo wp_kses(
						revenue()->tag_wrapper(
							$campaign,
							$generated_styles,
							'DoubleOrderMessage',
							$message,
							'',
							'div',
							array(
								'success-message' => $success_message,
								'default-message' => $message,
							)
						),
						revenue()->get_allowed_tag()
					);
					?>
				</div>
			</div>
		</div>
		<?php
	} else {


		$trigger_items   = $campaign['campaign_trigger_items'];
		$container_style = revenue()->get_style( $generated_styles, 'DoubleOrderProductsMessageContainer' );
		$wrapper_style   = revenue()->get_style( $generated_styles, 'DoubleOrderWrapper' );
		$saved_index     = -1;
		if ( isset( $double_order_session['index'] ) ) {
			$saved_index = $double_order_session['index'];
		}
		$is_show = 0 === $idx || ( -1 !== $saved_index && intval( $saved_index ) + 1 >= $idx );

		echo '<div class="revx-products-lists-specific ' . $animation_name . ' ' . ( ! $is_show ? ' hidden ' : '' ) . '" style="' . esc_attr( $wrapper_style ) . '" data-index="' . esc_attr( $idx ) . '">';
		?>
		<div class="revx-products-list-header-specific" style="<?php echo esc_attr( $container_style ); ?>">

			<?php
			echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'DoubleOrderMessage', $message, '', 'div' ), revenue()->get_allowed_tag() );
			?>
		</div>
		<?php

		$child_idx = -1;

		foreach ( $trigger_items as $trigger ) {
			$child_idx++;
			if ( 'products' === $trigger['trigger_type'] ) {
				$item_id = $trigger['item_id'];
				if ( ! in_array( $item_id, $cart_item_ids ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					continue;
				}
				$product = wc_get_product( $item_id );

				$success_message = str_replace( '{product_title}', $success_message, $product->get_title() );


				if ( $product ) {
					$product_image = wp_get_attachment_image_src( get_post_thumbnail_id( $item_id ), 'thumbnail' );
					$image_url     = $product_image ? $product_image[0] : wc_placeholder_img_src();


					$checkbox_selected_style = revenue()->get_style( $generated_styles, 'checkboxSelected' );
					$checkbox_default_style  = revenue()->get_style( $generated_styles, 'checkboxDefault' );
					$checkbox_required_style = revenue()->get_style( $generated_styles, 'checkboxRequired' );
					$is_selected             = $idx === $saved_index && in_array( $item_id, $double_order_session['products'] ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					$cur_style               = $is_selected ? $checkbox_selected_style : $checkbox_default_style;
					?>
					<div class="revx-product-item" data-parent-index="<?php echo esc_attr( $idx ); ?>" data-child-index="<?php echo esc_attr( $child_idx ); ?>" data-product-id="<?php echo esc_attr( $item_id ); ?>">

						<div
							data-multiplier="<?php echo esc_attr( $quantity ); ?>"
							data-type="<?php echo esc_attr( $discount_type ); ?>"
							data-value="<?php echo esc_attr( $value ); ?>"
							data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>"
							data-index="<?php echo esc_attr( $idx ); ?>"
							data-product-id="<?php echo esc_attr( $item_id ); ?>"
							data-is-checked="<?php echo esc_attr( $is_selected ? 'yes' : 'no' ); ?>"

							data-default-style="<?php echo esc_attr( $checkbox_default_style ); ?>" data-selected-style="<?php echo esc_attr( $checkbox_selected_style ); ?>" class="revx-builder-checkbox revx-justify-center revx-double-order-checkbox-specific" style="<?php echo esc_attr( $cur_style ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 12 12">
								<path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9.75 3.75 5 8.5 2.625 6.125"></path>
							</svg>
						</div>
						<?php
						echo wp_kses(
							revenue()->get_template_part(
								'image',
								array(
									'offered_product'  => $product,
									'generated_styles' => $generated_styles,
									'current_campaign' => $campaign,
								)
							),
							revenue()->get_allowed_tag()
						);
						?>
						<div class="revx-product-details">
							<?php
							echo wp_kses(
								revenue()->get_template_part(
									'product_title',
									array(
										'offered_product'  => $product,
										'generated_styles' => $generated_styles,
										'current_campaign' => $campaign,
									)
								),
								revenue()->get_allowed_tag()
							);

							echo wp_kses(
								revenue()->get_template_part(
									'price_container',
									array(
										'quantity'         => 1,
										'offered_product'  => $product,
										'generated_styles' => $generated_styles,
										'regular_price'    => $product->get_regular_price(),
										'offered_price'    => $product->get_price(),
										'current_campaign' => $campaign,
									)
								),
								revenue()->get_allowed_tag()
							);
							?>

						</div>
					</div>
					<?php
				}
			} elseif ( 'category' === $trigger['trigger_type'] ) {
				$item_id = $trigger['item_id'];

				$eligible_product_ids = array();


				foreach ( $cart_item_ids as $cart_item_id ) {
					$cat_ids = revenue()->get_product_category_ids( $cart_item_id );
					if ( in_array( $item_id, $cat_ids ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$eligible_product_ids[] = $cart_item_id;
					}
				}

				$is_already_visible = array();

				foreach ( $eligible_product_ids as $epid ) {
					$product = wc_get_product( $epid );
					if ( isset( $is_already_visible[ $epid ] ) ) {
						continue;
					}
					$is_already_visible[ $epid ] = true;

					if ( $product ) {
						$success_message = str_replace( '{product_title}', $success_message, $product->get_title() );

						$product_image = wp_get_attachment_image_src( get_post_thumbnail_id( $epid ), 'thumbnail' );
						$image_url     = $product_image ? $product_image[0] : wc_placeholder_img_src();


						$checkbox_selected_style = revenue()->get_style( $generated_styles, 'checkboxSelected' );
						$checkbox_default_style  = revenue()->get_style( $generated_styles, 'checkboxDefault' );
						$checkbox_required_style = revenue()->get_style( $generated_styles, 'checkboxRequired' );
						$is_selected             = $idx === $saved_index && in_array( $epid, $selected_product_ids ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
						$cur_style               = $is_selected ? $checkbox_selected_style : $checkbox_default_style;
						?>
					<div class="revx-product-item" data-parent-index="<?php echo esc_attr( $idx ); ?>" data-child-index="<?php echo esc_attr( $child_idx ); ?>" data-product-id="<?php echo esc_attr( $epid ); ?>">

						<div
							data-multiplier="<?php echo esc_attr( $quantity ); ?>"
							data-type="<?php echo esc_attr( $discount_type ); ?>"
							data-value="<?php echo esc_attr( $value ); ?>"
							data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>"
							data-index="<?php echo esc_attr( $idx ); ?>"
							data-product-id="<?php echo esc_attr( $epid ); ?>"
							data-is-checked="<?php echo esc_attr( $is_selected ? 'yes' : 'no' ); ?>"

							data-default-style="<?php echo esc_attr( $checkbox_default_style ); ?>" data-selected-style="<?php echo esc_attr( $checkbox_selected_style ); ?>" class="revx-builder-checkbox revx-justify-center revx-double-order-checkbox-specific" style="<?php echo esc_attr( $cur_style ); ?>">
							<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 12 12">
								<path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="1.5" d="M9.75 3.75 5 8.5 2.625 6.125"></path>
							</svg>
						</div>
						<?php
						echo wp_kses(
							revenue()->get_template_part(
								'image',
								array(
									'offered_product'  => $product,
									'generated_styles' => $generated_styles,
									'current_campaign' => $campaign,
								)
							),
							revenue()->get_allowed_tag()
						);
						?>
						<div class="revx-product-details">
							<?php
							echo wp_kses(
								revenue()->get_template_part(
									'product_title',
									array(
										'offered_product'  => $product,
										'generated_styles' => $generated_styles,
										'current_campaign' => $campaign,
									)
								),
								revenue()->get_allowed_tag()
							);

								echo wp_kses(
									revenue()->get_template_part(
										'price_container',
										array(
											'quantity' => 1,
											'offered_product' => $product,
											'generated_styles' => $generated_styles,
											'regular_price' => $product->get_regular_price(),
											'offered_price' => $product->get_price(),
											'current_campaign' => $campaign,
										)
									),
									revenue()->get_allowed_tag()
								);
							?>

						</div>
					</div>
						<?php
					}
				}
			}
		}
		echo '</div>';
	}
}
echo '</div>';
