<?php
/**
 * The Template for displaying revenuex view
 *
 * @package RevenueX
 * @version 1.0.0
 */

use Revenue\Services\Revenue_Product_Context;

 defined( 'ABSPATH' ) || exit;
 
	$product = Revenue_Product_Context::get_product_context();

	$offered_product = false;
	$regular_price   = false;
	$offered_price   = false;

	$output_content          = '';
	$product_content_output  = '';
	$product_quantity_output = '';

	$view_mode        = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
	$buider_data      = revenue()->get_campaign_meta( $campaign['id'], 'builderdata', true )['popup'][ $view_mode ];
	$generated_styles = revenue()->campaign_style_generator( 'popup', $campaign, $placement );

	$offers                   = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
	$on_cart_action           = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_on_cart_action', true );
	$on_offered_product_click = revenue()->get_campaign_meta( $campaign['id'], 'offered_product_click_action', true );
	$offer_ids                = array();
	$trigger_product_data     = array(
		'title'         => $product->get_title(),
		'regular_price' => $product->get_regular_price(),
		'thumbnail'     => wp_get_attachment_image_src( get_post_thumbnail_id( $product->get_id() ), 'single-post-thumbnail' ),
	);
	$total_offer_products     = 0;

	$offer_data = array();


	$has_required_products     = isset( $campaign['mix_match_is_required_products'] ) && 'yes' == $campaign['mix_match_is_required_products'];
	$required_products         = isset( $campaign['mix_match_required_products'] ) ? $campaign['mix_match_required_products'] : array();
	$initial_product_selection = isset( $campaign['mix_match_initial_product_selection'] ) ? $campaign['mix_match_initial_product_selection'] : '';

	$offer_product_data = array();

	$trigger_product_relation = 'and';

	$trigger_items = revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $product->get_id() );

	$mix_match_products = array();

	$mix_match_quantity    = revenue()->getMixMatchQuantities( $campaign );
	$quantity_items_style  = revenue()->get_style( $generated_styles, 'selectedQuantityItems' );
	$regular_items_style   = revenue()->get_style( $generated_styles, 'regularQuantityItems' );
	$checkbox_style        = revenue()->get_style( $generated_styles, 'selectIcon' );
	$tagged_products_style = revenue()->get_style( $generated_styles, 'taggedProduct' );

	$cookie_name       = sprintf( 'revx_mix_match_%s', $campaign['id'] );
	$selected_items    = isset( $_COOKIE[ $cookie_name ] ) ? sanitize_text_field( urldecode( $_COOKIE[ $cookie_name ] ) ) : '';
	$selected_items    = json_decode( wp_unslash( $selected_items ), true ) ?? array();
	$selected_item_ids = array_keys( $selected_items );

	foreach ( $selected_items as $key => $value ) {
		$pr = wc_get_product( $key );
		if ( $pr ) {
			if ( $pr->is_in_stock() ) {
				$selected_items[ $key ]['regularPrice'] = $pr->get_regular_price();
			} else {
				unset( $selected_items[ $key ] );
			}
		}
	}

	if ( ! empty( $required_products ) && $has_required_products ) {
		foreach ( $required_products as $rpid ) {
			if ( ! in_array( $rpid, $selected_item_ids ) ) {
				$required_product = wc_get_product( $rpid );
				$thumnail         = wp_get_attachment_image_src( get_post_thumbnail_id( $required_product->get_id() ), 'single-post-thumbnail' );
				$thumnail         = isset( $thumnail[0] ) ? $thumnail[0] : wc_placeholder_img_src();
	
				if ( $required_product->is_in_stock() ) {
					$selected_items[ $rpid ] = array(
						'id'           => $rpid,
						'productName'  => $required_product->get_name(),
						'regularPrice' => $required_product->get_regular_price(),
						'thumbnail'    => $thumnail,
						'quantity'     => 1,
					);
				} else {
					unset( $selected_items[ $rpid ] );
				}
			}
		}
	}

	$is_all_product_selection = 'all_products' === $initial_product_selection;

	if ( $is_all_product_selection ) {
		foreach ( $trigger_items as $offer ) {
			$offer_product_id = $offer['item_id'];
			if ( ! in_array( $offer_product_id, $selected_item_ids ) ) {
				$required_product = wc_get_product( $offer_product_id );
				$thumnail         = wp_get_attachment_image_src( get_post_thumbnail_id( $required_product->get_id() ), 'single-post-thumbnail' );
				$thumnail         = isset( $thumnail[0] ) ? $thumnail[0] : wc_placeholder_img_src();
				if ( $required_product->is_in_stock() ) {
					$selected_items[ $offer_product_id ] = array(
						'id'           => $offer_product_id,
						'productName'  => $required_product->get_name(),
						'regularPrice' => $required_product->get_regular_price(),
						'thumbnail'    => $thumnail,
						'quantity'     => 1,
					);
				} else {
					unset( $selected_items[ $offer_product_id ] );
				}
			}
		}
	}



	$total_regular_price = 0;
	$total_sale_price    = 0;
	$selected_index      = -1;


	foreach ( $selected_items as $key => $item ) {
		$total_regular_price += floatval( $item['regularPrice'] ) * intval( $item['quantity'] );
	}

	foreach ( $mix_match_quantity as $idx => $quantity ) {
		if ( count( $selected_items ) >= $quantity['quantity'] ) {
			$selected_index = $idx;
		}
	}

	$selected_product_header_desc = sprintf(
		__( 'For <span class="revx-selected-product-count"> %s</span> Items', 'revenue-pro' ),
		count( $selected_items )
	);



	ob_start();
	?>
	<div class="revx-mixmatch-quantity revx-mix-match-offer-grid">
		<?php
		foreach ( $mix_match_quantity as $idx => $quantity ) {

			$default  = $regular_items_style;
			$tagged   = $tagged_products_style;
			$selected = $quantity_items_style;

			$items_style = $quantity['isEnableTag'] == 'yes' ? $tagged : $default;

			if ( ( $idx == $selected_index ) ) {
				$items_style = $selected;
			}

			$offer_items_style = ( $idx == $selected_index ) ? 'selectedQuantityOffer' : 'regularQuantityOffer';
			$isTagEnabled      = isset( $quantity['isEnableTag'] ) && 'yes' == $quantity['isEnableTag'];

			?>
			   <div data-index="<?php echo esc_attr( $idx ); ?>" data-selected-style="<?php echo esc_attr( $selected ); ?>" data-default-style="<?php echo esc_attr( $default ); ?>" class="revx-mixmatch-regular-quantity" style="<?php echo esc_attr( $items_style ); ?>">
				<?php
				if ( $isTagEnabled ) {
					echo wp_kses(
						revenue()->get_template_part(
							'badge_tag',
							array(
								'current_campaign' => $campaign,
								'generated_styles' => $generated_styles,
							)
						),
						revenue()->get_allowed_tag()
					);
				}
				?>
				   <div class="revx-justify-space">
					   <div>
					   <?php
						   printf( esc_html__( '%s Items', 'revenue-pro' ), esc_html( $quantity['quantity'] ) );
						   echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'regularQuantityOffer', $quantity['saveData']['content'], 'revx-builder-savings-tag' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					   </div>
					   <div class="revx-builder-checkbox revx-justify-center <?php echo $idx == $selected_index ? '' : esc_attr( 'revx-d-none' ); ?>" style="<?php echo esc_attr( $checkbox_style ); ?>">
						   <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="none" viewBox="0 0 12 12"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 3.75 5 8.5 2.625 6.125"></path></svg>
					   </div>
				   </div>
			   </div>
			<?php
		}
		?>
	</div>
	<?php
	$product_quantity_output .= ob_get_clean();

	if ( $selected_index != -1 ) {
		$selected_offer   = $mix_match_quantity[ $selected_index ];
		$total_sale_price = $total_regular_price * ( 1 - ( floatval( $selected_offer['value'] ) / 100 ) );
	}


	ob_start();
	foreach ( $trigger_items as $offer ) {
		$offered_product  = wc_get_product( $offer['item_id'] );
		$offer_product_id = $offer['item_id'];
		if ( ! $offered_product ) {
			continue;
		}
		if(!$offered_product->is_in_stock()) {
			continue;
		}
		$regular_price = $offered_product->get_regular_price();
		$offered_price = $offered_product->get_regular_price();

		if ( ! isset( $offer_data[ $offer_product_id ] ) ) {
			$offer_data[ $offer_product_id ] = $offer;
		}

		$offer_data[ $offer_product_id ]['regular_price'] = $regular_price;
		$product_style                                    = revenue()->get_style( $generated_styles, 'product' );

		if ( 'list' == $view_mode ) :
			?>
				<div class="revx-campaign-item revx-mix-match-item" style="<?php echo $product_style; ?>">
					<?php
						echo wp_kses(
							revenue()->get_template_part(
								'image',
								array(
									'offered_product'  => $offered_product,
									'generated_styles' => $generated_styles,
									'current_campaign' => $campaign,
								)
							),
							revenue()->get_allowed_tag()
						);
					?>
					<div class="revx-campaign-text-content revx-align-center revx-full-width" style="gap: 8px">
						<div class="revx-full-width">
							<?php
								echo wp_kses(
									revenue()->get_template_part(
										'product_title',
										array(
											'offered_product'  => $offered_product,
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
											'offered_product'  => $offered_product,
											'generated_styles' => $generated_styles,
											'regular_price'    => $regular_price,
											'offered_price'    => $offered_price,
											'current_campaign' => $campaign,
											'campaign_type' => 'mix_match',
										)
									),
									revenue()->get_allowed_tag()
								);
								echo wp_kses(
									revenue()->get_template_part(
										'quantity_selector',
										array(
											'generated_styles' => $generated_styles,
											'current_campaign' => $campaign,
											'offered_product' => $offered_product,
										)
									),
									revenue()->get_allowed_tag()
								);
							?>
						</div>
						<?php
							echo wp_kses(
								revenue()->tag_wrapper(
									$campaign,
									$generated_styles,
									'selectButton',
									__( 'Add', 'revenue-pro' ),
									'revx-builder-add-btn revx-cursor-pointer',
									'button',
									array(
										'campaign-id' => $campaign['id'],
										'product-id'  => $offer['item_id'],
									)
								),
								revenue()->get_allowed_tag()
							); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						?>
					</div>
				</div>
				<?php else : ?>
				<div class="revx-campaign-item revx-campaign-text-content">
					<?php
						echo wp_kses(
							revenue()->get_template_part(
								'image',
								array(
									'offered_product'  => $offered_product,
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
									'offered_product'  => $offered_product,
									'generated_styles' => $generated_styles,
									'regular_price'    => $regular_price,
									'offered_price'    => $offered_price,
									'current_campaign' => $campaign,
									'campaign_type'    => 'mix_match',
								)
							),
							revenue()->get_allowed_tag()
						);
						echo wp_kses(
							revenue()->get_template_part(
								'product_title',
								array(
									'offered_product'  => $offered_product,
									'generated_styles' => $generated_styles,
									'current_campaign' => $campaign,
								)
							),
							revenue()->get_allowed_tag()
						);
						echo wp_kses(
							revenue()->get_template_part(
								'quantity_selector',
								array(
									'generated_styles' => $generated_styles,
									'current_campaign' => $campaign,
									'offered_product'  => $offered_product,
								)
							),
							revenue()->get_allowed_tag()
						);
						echo wp_kses(
							revenue()->tag_wrapper(
								$campaign,
								$generated_styles,
								'selectButton',
								__( 'Add', 'revenue-pro' ),
								'revx-builder-add-btn revx-cursor-pointer',
								'button',
								array(
									'campaign-id' => $campaign['id'],
									'product-id'  => $offer['item_id'],
								)
							),
							revenue()->get_allowed_tag()
						); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
		<?php endif; ?>
		<?php
	}

	$product_content_output .= ob_get_clean();

	if ( ! $product_content_output || ! $product_quantity_output ) {
		return;
	}
	// settings
	$product_title = $offered_product->get_title();
	$image         = wp_get_attachment_image_src( get_post_thumbnail_id( $offered_product->get_id() ), 'single-post-thumbnail' );
	// style
	$container_class               = ' revx-mix-match revx-popup-container ';
	$wrapper_style                 = revenue()->get_style( $generated_styles, 'wrapper' );
	$selected_product_container    = revenue()->get_style( $generated_styles, 'triggerProductWrapper' );
	$regular_product_container     = revenue()->get_style( $generated_styles, 'regularProductWrapper' );
	$selected_product_header       = revenue()->get_style( $generated_styles, 'selectedPd' );
	$img_style                     = revenue()->get_style( $generated_styles, 'selectedPdImg' );
	$product_remove_icon           = revenue()->get_style( $generated_styles, 'selectProductClose' );
	$product_divider_style         = revenue()->get_style( $generated_styles, 'selectedProdDivider' );
	$selected_product_header_style = revenue()->get_style( $generated_styles, 'selectedProductHeader' );
	// let name = `${isLastItem ?'selectedQuantityItems' : 'regularQuantityItems'}`;

	$is_slider_enabled            = true;
	$gridClass                    = 'revx-campaign-view__items ' . ( $is_slider_enabled ? 'revx-slider-container' : '' );
	$empty_selected_product_class = count( $selected_items ) == 0 ? 'revx-d-none' : '';

	ob_start();
	if ( 'list' == $view_mode ) {
		$container_class .= ' revx-mix-match-list';
		?>
		<div class="revx-campaign-container__wrapper" style="<?php echo esc_attr( $wrapper_style ); ?>">
			<div>
				<?php echo wp_kses( $product_quantity_output, revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div class="revx-product-container"  style="<?php echo esc_attr( $regular_product_container ); ?> ">
							<?php echo wp_kses( $product_content_output, revenue()->get_allowed_tag() ); ?>
					</div>

					<div  class="revx-selected-product-container <?php echo count( $selected_items ) > 0 ? '' : 'revx-empty-selected-items'; ?> " style="<?php echo esc_attr( $selected_product_container ); ?>">
						<div class="revx-selected-product-header <?php echo esc_attr( $empty_selected_product_class ); ?>" style="<?php echo esc_attr( $selected_product_header ); ?>">
							<div class="revx-campaign-text-content">
								<?php
								$regular_price = $total_regular_price;
								$offered_price = $total_sale_price;
								echo wp_kses(
									revenue()->get_template_part(
										'price_container',
										array(
											'generated_styles' => $generated_styles,
											'regular_price' => $total_regular_price,
											'offered_price' => $total_sale_price,
											'current_campaign' => $campaign,
											'campaign_type' => 'mix_match',
											'force_show' => true,
										)
									),
									revenue()->get_allowed_tag()
								);
								?>
								<div style="<?php echo esc_attr( $selected_product_header_style ); ?>"> <?php echo $selected_product_header_desc; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
							</div>
						</div>
						<div class="revx-selected-items">
							<div class="revx-empty-mix-match <?php echo count( $selected_items ) > 0 ? 'revx-d-none' : ''; ?>">
								<div class="revx-empty-mix-match-icon">
									<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M18 24C18 25.5913 18.6321 27.1174 19.7574 28.2426C20.8826 29.3679 22.4087 30 24 30C25.5913 30 27.1174 29.3679 28.2426 28.2426C29.3679 27.1174 30 25.5913 30 24" stroke="#868C98" stroke-width="2" stroke-linecap="round"></path>
										<path d="M9 16.2111C9 15.61 9 15.3094 9.08582 15.026C9.17163 14.7426 9.33835 14.4925 9.6718 13.9923L11.8125 10.7812C12.3938 9.90927 12.6845 9.4733 13.1267 9.23665C13.5688 9 14.0928 9 15.1407 9H32.8593C33.9072 9 34.4312 9 34.8733 9.23665C35.3155 9.4733 35.6062 9.90927 36.1875 10.7812L38.3282 13.9923C38.6616 14.4925 38.8284 14.7426 38.9142 15.026C39 15.3094 39 15.61 39 16.2111V35C39 36.8856 39 37.8284 38.4142 38.4142C37.8284 39 36.8856 39 35 39H13C11.1144 39 10.1716 39 9.58579 38.4142C9 37.8284 9 36.8856 9 35V16.2111Z" stroke="#868C98" stroke-width="2"></path>
										<path d="M9 19H39" stroke="#868C98" stroke-width="2" stroke-linecap="round"></path>
									</svg>
								</div>
								<div class="revx-empty-mix-match-message">
									<?php echo esc_html__( 'Please add product to proceed', 'revenue-pro' ); ?>
								</div>
							</div>
							<?php
							foreach ( $selected_items as $idx => $item ) {
								?>
									<div class='revx-selected-item' data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>" data-product-id="<?php echo esc_attr( $item['id'] ); ?>" >
										<div class="revx-builder-divider" style="<?php echo esc_attr( $product_divider_style ); ?>"></div>

										<div class="revx-flex" style="<?php echo esc_attr( $selected_product_header ); ?>">
											<div class="revx-campaign-item__image revx" style="<?php echo esc_attr( $img_style ); ?>">
												<img src="<?php echo esc_url_raw( $item['thumbnail'] ); ?>" alt="<?php echo esc_attr( $item['productName'] ); ?>" />
											</div>
											<div class="revx-justify-space revx-full-width">
												<div>
												<?php
													echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'selectedProdTitle', $item['productName'], 'revx-selected-item__product-title revx-product-title', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'selectedProductPrice', $item['quantity'] . ' x ' . wc_price( $item['regularPrice'] ), 'revx-selected-item__product-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												?>
												</div>

												<?php
													if ( ! in_array( $item['id'], $required_products ) ) {
														?>
														<div class="revx-remove-selected-item revx-cursor-pointer revx-align-center" style="<?php echo esc_attr( $product_remove_icon ); ?>">
															<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="currentColor" viewBox="0 0 20 20"><rect width="20" height="20" rx="10"></rect><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="m14 6-8 8M6 6l8 8"></path></svg>
														</div>
														<?php
													 }
												?>

												
											</div>
										</div>
									</div>
								<?php
							}
							?>
							<!-- For Cloned Item - Start -->
								<div class='revx-selected-item revx-d-none' data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>" >
										<div class="revx-builder-divider" style="<?php echo esc_attr( $product_divider_style ); ?>"></div>

										<div class="revx-flex" style="<?php echo esc_attr( $selected_product_header ); ?>">
											<div class="revx-campaign-item__image" style="<?php echo esc_attr( $img_style ); ?>">
												<img src="" alt="" />
											</div>
											<div class="revx-justify-space revx-full-width">
												<div>
												<?php
													echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'selectedProdTitle', '', 'revx-selected-item__product-title revx-product-title', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
													echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'selectedProductPrice', '', 'revx-selected-item__product-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												?>
												</div>
												<div class="revx-remove-selected-item revx-cursor-pointer revx-align-center" style="<?php echo esc_attr( $product_remove_icon ); ?>">
													<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="currentColor" viewBox="0 0 20 20"><rect width="20" height="20" rx="10"></rect><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="m14 6-8 8M6 6l8 8"></path></svg>
												</div>
											</div>
										</div>
								</div>
							<!-- For Cloned Item - End -->
							</div>
							<div class="revx-builder-divider" style="<?php echo esc_attr( $product_divider_style ); ?>"></div>
							<div class="revx-campaign-text-content revx-full-width">
								<?php
								echo wp_kses(
									revenue()->get_template_part(
										'add_to_cart',
										array(
											'class' => count( $selected_items ) > 0 ? '' : 'revx-empty',
											'generated_styles' => $generated_styles,
											'offered_product' => $offered_product,
											'current_campaign' => $campaign,
										)
									),
									revenue()->get_allowed_tag()
								); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
								?>
							</div>
					</div>
			</div>
		</div>
		<?php
	} else {
		$container_class .= ' revx-mix-match-grid';
		?>
		<div class="revx-campaign-container__wrapper-main" style="position: relative;">
			<div class="revx-campaign-container__wrapper" style="<?php echo esc_attr( $wrapper_style ); ?>">
				<div>
				<?php echo wp_kses( $product_quantity_output, revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<div class="revx-flex revx-sm-f-col" style=" gap: 16px">
						<div class="revx-mix-match-products <?php echo $is_slider_enabled ? 'revx-slider' : ''; ?> revx-flex">
							<?php echo wp_kses( revenue()->get_slider_icon( $generated_styles, 'left' ), revenue()->get_allowed_tag() ); ?>
						
							<div class="<?php echo esc_attr( $gridClass ); ?>" style="<?php echo esc_attr( $regular_product_container ); ?> ">
								<?php echo wp_kses( $product_content_output, revenue()->get_allowed_tag() ); ?>
							</div>
							<?php echo wp_kses( revenue()->get_slider_icon( $generated_styles, 'right' ), revenue()->get_allowed_tag() ); ?>
							
						</div>

						<div class="revx-selected-product-container <?php echo count( $selected_items ) > 0 ? '' : 'revx-empty-selected-items'; ?> " style="<?php echo esc_attr( $selected_product_container ); ?>">
							<div class="revx-selected-product-header <?php echo esc_attr( $empty_selected_product_class ); ?>" style="<?php echo esc_attr( $selected_product_header ); ?>">
								<div class="revx-campaign-text-content">
									<?php
									$regular_price = $total_regular_price;
									$offered_price = $total_sale_price;
									echo wp_kses(
										revenue()->get_template_part(
											'price_container',
											array(
												'generated_styles' => $generated_styles,
												'regular_price'    => $total_regular_price,
												'offered_price'    => $total_sale_price,
												'current_campaign' => $campaign,
												'campaign_type' => 'mix_match',
												'force_show' => true,
											)
										),
										revenue()->get_allowed_tag()
									);
									?>
									<div style="<?php echo esc_attr( $selected_product_header_style ); ?>"> 
														   <?php
															echo $selected_product_header_desc; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
															?>
																											</div>
								</div>
							</div>
							<div class="revx-builder-divider" style="<?php echo esc_attr( $product_divider_style ); ?>"></div>
							<div class="revx-selected-items revx-scrollbar-md">
								<div class="revx-empty-mix-match <?php echo count( $selected_items ) > 0 ? 'revx-d-none' : ''; ?>">
									<div class="revx-empty-mix-match-icon">
										<svg width="48" height="48" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
											<path d="M18 24C18 25.5913 18.6321 27.1174 19.7574 28.2426C20.8826 29.3679 22.4087 30 24 30C25.5913 30 27.1174 29.3679 28.2426 28.2426C29.3679 27.1174 30 25.5913 30 24" stroke="#868C98" stroke-width="2" stroke-linecap="round" />
											<path d="M9 16.2111C9 15.61 9 15.3094 9.08582 15.026C9.17163 14.7426 9.33835 14.4925 9.6718 13.9923L11.8125 10.7812C12.3938 9.90927 12.6845 9.4733 13.1267 9.23665C13.5688 9 14.0928 9 15.1407 9H32.8593C33.9072 9 34.4312 9 34.8733 9.23665C35.3155 9.4733 35.6062 9.90927 36.1875 10.7812L38.3282 13.9923C38.6616 14.4925 38.8284 14.7426 38.9142 15.026C39 15.3094 39 15.61 39 16.2111V35C39 36.8856 39 37.8284 38.4142 38.4142C37.8284 39 36.8856 39 35 39H13C11.1144 39 10.1716 39 9.58579 38.4142C9 37.8284 9 36.8856 9 35V16.2111Z" stroke="#868C98" stroke-width="2" />
											<path d="M9 19H39" stroke="#868C98" stroke-width="2" stroke-linecap="round" />
										</svg>
									</div>
									<div class="revx-empty-mix-match-message">
									<?php echo esc_html__( 'Please add product to proceed', 'revenue-pro' ); ?>
									</div>
								</div>
								<?php
								$count_number = 0;
								foreach ( $selected_items as $i => $item ) {
									?>
									<div class='revx-selected-item' data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>" data-product-id="<?php echo esc_attr( $item['id'] ); ?>">
										<?php
										if ( $count_number != 0 ) {
											?>
												<div class="revx-builder-divider" style="<?php echo esc_attr( $product_divider_style ); ?>"></div>
											<?php
										}
										?>
										
										<div class="revx-flex" style="<?php echo esc_attr( $selected_product_header ); ?>">
											<div class="revx-campaign-item__image revx" style="<?php echo esc_attr( $img_style ); ?>">
												<img src="<?php echo esc_url_raw( $item['thumbnail'] ); ?>" alt="<?php echo esc_attr( $item['productName'] ); ?>" />
											</div>
											<div class="revx-justify-space revx-full-width">
												<div>
												<?php
														echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'selectedProdTitle', $item['productName'], 'revx-selected-item__product-title revx-product-title', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
														echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'selectedProductPrice', $item['quantity'] . ' x ' . wc_price( $item['regularPrice'] ), 'revx-selected-item__product-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												?>
												</div>
												<?php
													if ( ! in_array( $item['id'], $required_products ) ) { 
														?>
														<div class="revx-remove-selected-item revx-align-center revx-cursor-pointer" style="<?php echo esc_attr( $product_remove_icon ); ?>">
															<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="currentColor" viewBox="0 0 20 20">
																<rect width="20" height="20" rx="10"></rect>
																<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="m14 6-8 8M6 6l8 8"></path>
															</svg>
														</div>

														<?php
													}
												?>
												
											</div>
										</div>
									</div>
									<?php
									$count_number++;
								}
								?>
								<!-- For Cloned Item - Start -->
								<div class='revx-selected-item revx-d-none' data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>">
									<div class="revx-builder-divider" style="<?php echo esc_attr( $product_divider_style ); ?>"></div>
									<div class="revx-flex" style="<?php echo esc_attr( $selected_product_header ); ?>">
										<div class="revx-campaign-item__image" style="<?php echo esc_attr( $img_style ); ?>">
											<img src="" alt="" />
										</div>
										<div class="revx-justify-space revx-full-width">
											<div>
											<?php
												echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'selectedProdTitle', '', 'revx-selected-item__product-title revx-product-title', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
												echo wp_kses( revenue()->tag_wrapper( $campaign, $generated_styles, 'selectedProductPrice', '', 'revx-selected-item__product-price', 'div' ), revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											?>
											</div>
											<div class="revx-remove-selected-item revx-cursor-pointer revx-align-center" style="<?php echo esc_attr( $product_remove_icon ); ?>">
												<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="currentColor" viewBox="0 0 20 20">
													<rect width="20" height="20" rx="10"></rect>
													<path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="m14 6-8 8M6 6l8 8"></path>
												</svg>
											</div>
										</div>
									</div>
								</div>
								<!-- For Cloned Item - End -->
							</div>
							<div class="revx-builder-divider" style="<?php echo esc_attr( $product_divider_style ); ?>"></div>
							<div class="revx-campaign-text-content revx-full-width">
								<?php
								echo wp_kses(
									revenue()->get_template_part(
										'add_to_cart',
										array(
											'class' => count( $selected_items ) > 0 ? '' : 'revx-empty',
											'generated_styles' => $generated_styles,
											'offered_product' => $offered_product,
											'current_campaign' => $campaign,
										)
									),
									revenue()->get_allowed_tag()
								); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
								?>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
	?>
<input type="hidden" name="<?php echo 'revx-offer-data-' . esc_attr( $campaign['id'] ); ?>" value="<?php echo htmlspecialchars( wp_json_encode( $offer_data ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" />
<input type="hidden" name="<?php echo 'revx-selected-items-' . esc_attr( $campaign['id'] ); ?>" value="<?php echo htmlspecialchars( wp_json_encode( $selected_items ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" />
<input type="hidden" name="<?php echo 'revx-qty-data-' . esc_attr( $campaign['id'] ); ?>" value="<?php echo htmlspecialchars( wp_json_encode( $mix_match_quantity ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>" />
<input type="hidden" name="<?php echo 'revx-required-products-' . esc_attr( $campaign['id'] ); ?>" value="
									  <?php
										echo htmlspecialchars( wp_json_encode( $required_products ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped 
										?>
																									" />
<?php

$output_content = ob_get_clean();
revenue()->popup_container( $campaign, $generated_styles, $output_content, $container_class, false, $placement ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
