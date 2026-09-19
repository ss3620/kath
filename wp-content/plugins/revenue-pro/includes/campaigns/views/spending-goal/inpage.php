<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! isset( revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )[ $position ] ) ) {
	return;
}

$reward_type_options = array(
	'free_shipping' => __( 'Free Shipping', 'revenue-pro' ),
	'discount'      => __( 'Discount', 'revenue-pro' ),
	'gift'          => __( 'Gift Items', 'revenue-pro' ),
);

$buider_data = revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )[ $position ];

$generated_styles = revenue()->campaign_style_generator( $position, $campaign );

$circular_progress_container_style = revenue()->get_style( $generated_styles, 'circularProgressContainer' );
$circular_progress_bar_style       = revenue()->get_style( $generated_styles, 'circularProgressBar' );
$icon_style                        = revenue()->get_style( $generated_styles, 'icon' );
$reward_name_style                 = revenue()->get_style( $generated_styles, 'rewardName' );
$progress_bar_style                = revenue()->get_style( $generated_styles, 'progressBar' );
$wrapper_style                     = revenue()->get_style( $generated_styles, 'container' );

$cart_total = $this->get_eligible_cart_total();
$step1      = 120;
$step2      = 200;
$progress   = 40;

$circular_size = 56;
$stroke_width  = 8;
$radius        = ( $circular_size - $stroke_width ) / 2;
$circumference = 2 * M_PI * $radius;

$progress_offset = $circumference - ( $progress / 100 ) * $circumference;

$offers = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );

$step_width = 100 / ( count( $offers ) ); // Divide evenly across steps

$total_goal = 0;
foreach ( $offers as $offer ) {
	if ( isset( $offer['spending_goal'] ) ) {
		$total_goal += $offer['spending_goal'];
	}
}

// $progress = min( ( $cart_total / $total_goal ) * 100, 100 );

$img_style = revenue()->get_style( $generated_styles, 'productImage' );

$gift_item_wrapper_style      = revenue()->get_style( $generated_styles, 'giftItemsWrapper' );
$reward_message_wrapper_style = revenue()->get_style( $generated_styles, 'rewardMessageWrapper' );


$required_goal   = 0;
$current_message = '';
$reward_message  = '';

foreach ( $offers as $index => $offer ) {
	if ( ! isset( $offer['spending_goal'] ) ) {
		continue;
	}
	$required_goal += floatval( $offer['spending_goal'] );
	if ( $cart_total < $required_goal ) {
		// User hasn't reached this step yet.
		$current_message = isset( $offer['before_message'] ) ? $offer['before_message'] : '';
		// $reward_message  = isset( $offer['after_message'] ) ? $offer['after_message'] : '';

		$remaining_amount = $cart_total - $required_goal;

		$current_message = str_replace( '{remaining_amount}', wc_price( abs( $remaining_amount ) ), $current_message );
		if ( isset( $offer['reward_type'] ) ) {
			$current_message = str_replace( '{reward_type}', $reward_type_options[ $offer['reward_type'] ], $current_message );
		}
		if ( isset( $offer['discount_value'] ) ) {
			$current_message = str_replace( '{discount_value}', $offer['discount_value'] ?? '', $current_message );
		}

		break;
	} else {
		$reward_message = isset( $offer['after_message'] ) ? $offer['after_message'] : '';
		if ( ! isset( $offer['reward_type'] ) ) {
			continue;
		}

		switch ( $offer['reward_type'] ) {
			case 'discount':
				$discountType = $offer['discount_type'] ?? null;
				if ( $discountType === 'percentage' ) {
					$reward_message = str_replace(
						'{discount_value}',
						( $offer['discount_value'] ?? 0 ) . '%',
						$reward_message
					);
				} else {
					$reward_message = str_replace(
						'{discount_value}',
						wc_price( $offer['discount_value'] ?? 0 ),
						$reward_message
					);
				}
				break;

			default:
				break;
		}
	}
}

if ( $cart_total >= $required_goal && isset( $campaign['all_goals_complete_message'] ) ) {
	$is_all_completed = true;
	$current_message  = $campaign['all_goals_complete_message'];
}

$upsell_products = array();


$quantity_style        = revenue()->get_style( $generated_styles, 'quantitySelector' );
$quantity_input_style  = revenue()->get_style( $generated_styles, 'quantitySelector', 'input' );
$quantity_button_style = revenue()->get_style( $generated_styles, 'quantitySelector', 'child' );
$classes               = revenue()->get_style( $generated_styles, 'quantitySelector', 'classes' );

if ( 'yes' === $campaign['spending_goal_upsell_product_status'] ) {


	$data            = $campaign['spending_goal_upsell_products'];
	$upsell_products = array();

	if ( ! is_array( $data ) ) {
		$data = array();
	}

	foreach ( $data as $order ) {
		// Ensure 'products' is an array of product IDs.
		if ( ! isset( $order['products'] ) || ! is_array( $order['products'] ) ) {
			continue;
		}

		// Process each product ID.
		foreach ( $order['products'] as $item_data ) {

			$regular_price    = (float) $item_data['regular_price'];
			$quantity         = isset( $order['quantity'] ) ? (int) $order['quantity'] : 0;
			$discounted_price = $regular_price;
			$discount_amount  = isset( $order['value'] ) ? (float) $order['value'] : 0;



			// Calculate discounted price based on order type.
			if ( isset( $order['type'] ) ) {
				switch ( $order['type'] ) {
					case 'percentage':
						$discount_value   = $discount_amount;
						$discount_amount  = number_format( ( $regular_price * $discount_value ) / 100, 2 );
						$discounted_price = number_format( $regular_price - $discount_amount, 2 );
						break;

					case 'fixed_discount':
						$discount_amount  = number_format( $discount_amount, 2 );
						$discounted_price = number_format( $regular_price - $discount_amount, 2 );
						break;

					case 'free':
						$discounted_price = '0.00';
						$discount_amount  = '100%';
						break;

					case 'no_discount':
						$discount_amount  = '0';
						$discounted_price = number_format( $regular_price, 2 );
						break;
				}
			}

			// Prepare product data.
			$upsell_products[] = array(
				'item_id'       => $item_data['item_id'],
				'item_name'     => $item_data['item_name'],
				'thumbnail'     => $item_data['thumbnail'], // Get the product thumbnail URL.
				'regular_price' => number_format( $regular_price, 2 ),
				'url'           => get_permalink( $item_data['item_id'] ),
				'sale_price'    => $discounted_price,
				'quantity'      => $quantity,
				'type'          => $order['type'],
				'value'         => isset( $order['value'] ) ? $order['value'] : '',
			);
		}
	}
}

$icon_size = ( 'top' === $position || 'bottom' === $position ) ? 'S' : '';

$left_slider_style  = revenue()->get_style( $generated_styles, 'leftSliderIcon' );
$right_slider_style = revenue()->get_style( $generated_styles, 'rightSliderIcon' );

$checked_icon_style = revenue()->get_style( $generated_styles, 'checkedIcon' );

$icon_style        = revenue()->get_style( $generated_styles, 'icon' );
$icon_size_class   = revenue()->get_style( $generated_styles, 'icon', 'classes' );
$add_to_cart_class = revenue()->get_style( $generated_styles, 'addToCartButton' );

$separator_style = revenue()->get_style( $generated_styles, 'separator' );

$bk_total = $cart_total;
?>



<div id="revx-progress-inpage"
	data-cart-total="<?php echo esc_attr( $cart_total ); ?>"
	data-progress="<?php echo esc_attr( $progress ); ?>"
	style="<?php echo esc_attr( $wrapper_style ); ?>"
	data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>"
	data-position=<?php echo esc_attr( $position ); ?>
	class="revx-campaign-container revx-campaign-spending-goal <?php echo esc_attr( $cart_total <= 0 ? 'hide' : '' ); ?> revx-campaign-<?php echo $campaign['id']; ?> revx-spending-goal-<?php echo esc_attr( $position ); ?>"
	data-final-message="<?php echo esc_attr( $campaign['all_goals_complete_message'] ); ?>"
	data-show-confetti="<?php echo esc_attr( $campaign['show_confetti'] ); ?>"

	> <!-- Add data-progress here -->

	<div class="revx-drawer-content open">
		<!-- Expanded Content -->
		<div class="revx-drawer-details">
			<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'paragraph', $current_message, 'revx-message', 'div' ), revenue()->get_allowed_tag() ); ?>
			<div class="revx-progress-container <?php echo esc_attr( $icon_size_class ); ?>">
				<div class="revx-progress-bar" style="<?php echo esc_attr( $progress_bar_style ); ?>">
					<?php
					$required_goal = 0;
					$progress      = 0;

					foreach ( $offers as $index => $offer ) {
						if ( ! isset( $offer['spending_goal'] ) ) {
							continue;
						}
						$required_goal += $offer['spending_goal'];

						$offer_type   = isset( $offer['reward_type'] ) ? $offer['reward_type'] : '';
						$sp_position  = ( $offer['spending_goal'] / $total_goal ) * 100;
						$is_completed = $cart_total >= floatval( $required_goal );
						$class        = $is_completed ? 'completed' : '';
						$sp_position  = ( ( $index + 1 ) * $step_width );

						$progress += min( ( ( $step_width / $offer['spending_goal'] ) * min( $offer['spending_goal'], $bk_total ) ), $step_width );
						$bk_total -= min( $offer['spending_goal'], $bk_total );


						?>
						<div class="revx-progress-step <?php echo esc_attr( $icon_size_class ); ?>" style="left: <?php echo esc_attr( $sp_position ); ?>%" >
							<div data-offer-type="<?php echo esc_attr( $offer_type ); ?>">
								<div style="<?php echo esc_attr( $progress_bar_style ); ?>" class='revx-step-icon-container <?php echo esc_attr( $class ); ?>  <?php echo esc_attr( $icon_size_class ); ?>'>
									<span class="revx-step-icon <?php echo esc_attr( $class ); ?> <?php echo esc_attr( $icon_size_class ); ?> " 
										style="<?php echo esc_attr( $icon_style ); ?>"
										data-offer-type="<?php echo esc_attr( $offer_type ); ?>">
										<?php
										if ( isset( $offer['reward_type'] ) && 'yes' === $campaign['spending_goal_progress_show_icon'] ) {
											echo revenue()->get_svg_from_assets( $offer['reward_type'] );
										} else {
											echo revenue()->get_svg_from_assets( 'check' );
										}
										?>
									</span>

									<?php
									if ( $offer_type === 'gift' && ! empty( $offer['gift_products'] ) ) :
										?>
										<div class="revx-gift-tooltip revx-spending-goal-gift" data-position="top">
										<div class="revx-spending-goal-gift__heading">
											<?php
											$is_all = false;
											if ( 'all' == $offer['gift_quantity'] ) {
												$is_all = true;
											}
											$message = $this->get_gift_heading_message( $required_goal, $offer );
											echo $message;
											?>
										</div>
											<div class="revx-gift-tooltip-content">
												<?php
												foreach ( $offer['gift_products'] as $gift_product ) :
													if ( empty( $gift_product['thumbnail'] ) ) {
														$gift_product['thumbnail'] = wc_placeholder_img_src();
													}
													$is_on_cart = $this->is_gift_on_cart( $gift_product['item_id'], $required_goal );


													?>
													<div
														class="revx-product-item revx-spending-goal-gift__item"
													>
														<div class="revx-spending-goal-gift__image-container">
															<img
																class="revx-spending-goal-gift__image"
																src="<?php echo esc_url( $gift_product['thumbnail'] ); ?>"
																alt="<?php echo esc_attr( $gift_product['item_name'] ); ?>"
															/>
														</div>
														<div class="revx-spending-goal-gift__content">
															<span class="revx-spending-goal-gift__title">
															<?php echo esc_attr( $gift_product['item_name'] ); ?>
															</span>
															<div class="revx-spending-goal-gift__price">
																<?php echo wc_price( $gift_product['regular_price'] ); ?>
															</div>
														</div>
														
														<div class="revx-spending-goal-gift__actions">
														<?php
														if ( $is_all ) {
															?>
																<div class="revx-spending-goal-gift__action checked <?php echo esc_attr( $is_on_cart ? 'on-cart' : '' ); ?>" data-product-id="<?php echo esc_attr( $gift_product['item_id'] ); ?>"> <?php echo revenue()->get_svg_from_assets( 'check' ); ?> </div>
																<div class="revx-spending-goal-gift__action add_to_cart <?php echo esc_attr( $is_on_cart ? 'on-cart' : '' ); ?> " data-product-id="<?php echo esc_attr( $gift_product['item_id'] ); ?>"> <?php echo revenue()->get_svg_from_assets( 'plus' ); ?> </div>
																<?php
														} else {
															?>
																<div class="revx-spending-goal-gift__action minus <?php echo esc_attr( $is_on_cart ? 'on-cart' : '' ); ?>" data-product-id="<?php echo esc_attr( $gift_product['item_id'] ); ?>"> <?php echo revenue()->get_svg_from_assets( 'minus' ); ?> </div>
																<div class="revx-spending-goal-gift__action checked <?php echo esc_attr( $is_on_cart ? 'on-cart' : '' ); ?>" data-product-id="<?php echo esc_attr( $gift_product['item_id'] ); ?>"> <?php echo revenue()->get_svg_from_assets( 'check' ); ?> </div>
																<div class="revx-spending-goal-gift__action add_to_cart <?php echo esc_attr( $is_on_cart ? 'on-cart' : '' ); ?> " data-product-id="<?php echo esc_attr( $gift_product['item_id'] ); ?>"> <?php echo revenue()->get_svg_from_assets( 'plus' ); ?> </div>
																<?php
														}

														?>
														</div>
															
													</div>
												<?php endforeach; ?>
											</div>
										</div>
									<?php endif; ?>
								</div>

								
								<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'rewardName', $offer['reward_name'], 'revx-step-label', 'div' ), revenue()->get_allowed_tag() ); ?>

							</div>
						</div>
						<?php
					}

					?>
					<div class="revx-progress-fill" style="width: <?php echo esc_attr( $progress ); ?>%;"></div>

				</div>
			</div>

		</div>

		<?php
		if ( 'inpage' === $position && ! empty( $upsell_products ) ) {
			?>
				<div
				className="revx-spg-separator"
				style="
				<?php
				echo esc_attr( $separator_style );
				echo 'height: 1px';
				?>
				"
				></div>
			<?php
		}
		?>

		<?php
		if ( ! empty( $upsell_products ) ) {
			?>
			<div class="revx-spending-goal-slider">
				<button class="revx-spending-goal-nav-button revx-spending-goal-prev" aria-label="Previous" style="<?php echo esc_attr( $left_slider_style ); ?>">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" version="1.1" xmlns:xlink="http://www.w3.org/1999/xlink" transform="matrix(-1,1.2246467991473532e-16,-1.2246467991473532e-16,-1,0,0)">
						<path d="M9 18L15 12L9 6" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"></path>
					</svg>
				</button>

				<div class="revx-spending-goal-slider-content">
					<div class="revx-spending-goal-slider-track">
						<?php

						foreach ( $upsell_products as $upsell_product ) {
							?>
								<div class="revx-spending-goal-product-card">
									<div class="revx-spending-goal-product-image" style="<?php echo esc_attr( $img_style ); ?>">
										<img src="<?php echo esc_attr( $upsell_product['thumbnail'] ); ?>" alt="<?php echo esc_attr( $upsell_product['item_name'] ); ?>" />
									</div>
									<div class="revx-spending-goal-product-details">
										<div class="revx-spending-goal-product-meta">
										<?php echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'productTitle', $upsell_product['item_name'], 'revx-spending-goal-product-title', 'div', array( 'product_url' => $upsell_product['url'] ) ), revenue()->get_allowed_tag() ); ?>
											
											<div class="revx-campaign-item__prices revx-flex" style="<?php echo esc_attr( revenue()->get_style( $generated_styles, 'priceContainer' ) ); ?>"> 
											<?php
											if ( isset( $upsell_product['sale_price'] ) ) {
												echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'regularPrice', wc_price( max( 0, $upsell_product['regular_price'] ) ), 'revx-campaign-item__regular-price', 'div' ), revenue()->get_allowed_tag() );
												echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'salePrice', wc_price( max( 0, $upsell_product['sale_price'] ) ), 'revx-campaign-item__sale-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											} else {
												echo wp_kses( revenue()->tag_wrapper( $current_campaign, $generated_styles, 'salePrice', wc_price( max( 0, $upsell_product['regular_price'] ) ), 'revx-campaign-item__sale-price', 'div' ), revenue()->get_allowed_tag() );
											}
											?>
											</div>
										</div>
										<div class="revx-spending-goal-actions revx-flex">
										<div class="revx-builder__quantity revx-align-center revx-width-full  <?php echo esc_attr( $classes ); ?>" style="<?php echo esc_attr( $quantity_style ); ?>">
											<div class="revx-quantity-minus revx-justify-center" style="<?php echo esc_attr( $quantity_button_style ); ?>">
											<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
											<path d="M3.33333 8H12.6667" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"/>
											</svg>
											</div>
											
												<input  data-name="revx_quantity"  type="number"  data-product-id="<?php echo esc_attr( $upsell_product['item_id'] ); ?>" data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" name="<?php echo esc_attr( 'revx-quantity-' . $current_campaign['id'] . '-' . $upsell_product['item_id'] ); ?>" style="<?php echo esc_attr( $quantity_input_style ); ?>" value="1"/>
												
											
											<div class="revx-quantity-plus revx-justify-center" style="<?php echo esc_attr( $quantity_button_style ); ?>">
											<svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
										<path d="M8 3.33301V12.6663" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"/>
										<path d="M3.33334 8H12.6667" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"/>
										</svg>
											</div>
										</div>

										<?php
										echo wp_kses(
											revenue()->tag_wrapper(
												$current_campaign,
												$generated_styles,
												'addToCartButton',
												__( 'Add to Cart', 'revenue-pro' ),
												'revx-cursor-pointer revx-spending-goal-add-cart ' . $add_to_cart_class,
												'button',
												array(
													'product-id'             => $upsell_product['item_id'],
													'campaign-id'            => $current_campaign['id'],
													'campaign-type'          => $current_campaign['campaign_type'],
												)
											),
											revenue()->get_allowed_tag()
										);

										?>
										</div>
										
									</div>
								</div>
							   <?php
						}
						?>
					</div>
				</div>

				<button class="revx-spending-goal-nav-button revx-spending-goal-next" aria-label="Next" style="<?php echo esc_attr( $right_slider_style ); ?>">
					<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M9 18L15 12L9 6" stroke="currentColor" strokeWidth="1.5" strokeLinecap="round" strokeLinejoin="round"/>
					</svg>
				</button>
			</div>

			<?php
		}


		?>
			<div class="revx-spending-goal-reward-message-wrapper <?php echo $reward_message ? 'has-message' : 'no-message'; ?>" style="<?php echo esc_attr( $reward_message_wrapper_style ); ?>">
			<div class="revx-spending-goal-checked-icon" style="<?php echo esc_attr( $checked_icon_style ); ?>">
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path fill-rule="evenodd" clip-rule="evenodd" d="M12 21C16.9706 21 21 16.9706 21 12C21 7.02944 16.9706 3 12 3C7.02944 3 3 7.02944 3 12C3 16.9706 7.02944 21 12 21ZM16.7682 9.64018C17.1218 9.21591 17.0645 8.58534 16.6402 8.23178C16.2159 7.87821 15.5853 7.93554 15.2318 8.35982L10.9328 13.5186L8.70711 11.2929C8.31658 10.9024 7.68342 10.9024 7.29289 11.2929C6.90237 11.6834 6.90237 12.3166 7.29289 12.7071L10.2852 15.6994C10.7051 16.1193 11.395 16.088 11.7752 15.6318L16.7682 9.64018Z" fill="currentColor"/>
				</svg>
			</div>
			<?php echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'rewardedMessage', $reward_message ?? 'Congratulations!', 'revx-spending-goal-reward-message', 'div' ), revenue()->get_allowed_tag() ); ?>
		</div>

	</div>
	<input type="hidden" name="revenue_spending_goal_offer" value="<?php echo htmlspecialchars( wp_json_encode( $offers ) ); ?>" />
	<input type="hidden" name="revenue_upsell_products" value="<?php echo htmlspecialchars( wp_json_encode( $upsell_products ) ); ?>" />
</div>
