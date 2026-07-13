<?php

/**
 * The Template for displaying revenuex view
 *
 * @package RevenueX
 * @version 1.0.0
 */

use Revenue\Services\Revenue_Product_Context;

defined('ABSPATH') || exit;

$product = Revenue_Product_Context::get_product_context();

$offered_product = false;
$regular_price   = false;
$offered_price   = false;
$output_content = '';

$view_mode = revenue()->get_placement_settings($campaign['id'],$placement,'builder_view') ?? 'list';
$buider_data = revenue()->get_campaign_meta($campaign['id'], 'builderdata', true)['inpage'][$view_mode];
// $bundle_product_id = $this->get_bundle_product_id();
$generated_styles         = revenue()->campaign_style_generator('inpage',$campaign, $placement);
$offers                   = revenue()->get_campaign_meta($campaign['id'], 'offers', true);
$on_cart_action           = revenue()->get_campaign_meta($campaign['id'], 'offered_product_on_cart_action', true);
$on_offered_product_click = revenue()->get_campaign_meta($campaign['id'], 'offered_product_click_action', true);
$is_qty_selector_enabled = revenue()->get_campaign_meta($campaign['id'], 'quantity_selector_enabled', true);
// styles
$product_style     = revenue()->get_style($generated_styles, 'product');
$taggedProductStyle = revenue()->get_style($generated_styles, 'taggedProduct');
$builder_Separator = revenue()->get_style($generated_styles, 'bundleSeparator');
$product_id = $product->get_id();


$offer_products = revenue()->getOfferProductsData($offers);

$trigger_product_relation = isset($campaign['campaign_trigger_relation'])?$campaign['campaign_trigger_relation']:'or';
if(empty($trigger_product_relation)) {
	$trigger_product_relation = 'or';
}
$is_category = ('category'==$campaign['campaign_trigger_type']) || ('all_products' == $campaign['campaign_trigger_type']);

$trigger_items = revenue()->getTriggerProductsData($campaign['campaign_trigger_items'], $trigger_product_relation, $product->get_id(), $is_category);


$fbt_items = array_merge($trigger_items, $offer_products);


$items_content = '';
$product_lists = [];
$cookie_name =  sprintf("revx_campaign_%s", $campaign['id']);

$total_offer_products = 1;

$offer_data = array();


$selected_items = isset($_COOKIE[$cookie_name]) ? sanitize_text_field(urldecode($_COOKIE[$cookie_name])) : '';

$selected_items = json_decode(wp_unslash($selected_items), true) ??  [];

if(!is_array($selected_items)) {
    $selected_items = [];
}

$total_regular_price = 0;
$total_sale_price = 0;

$is_trigger_product_required = isset($campaign['fbt_is_trigger_product_required'])? 'yes' == $campaign['fbt_is_trigger_product_required']: false;

ob_start();

if (is_array($fbt_items)) {
	$offer_length = count($fbt_items);


	foreach ($fbt_items as $offer_index => $offer) {

		$offer_type          = $offer['type'];
		$offer_value         = $offer['value'];
		$offer_qty           = $offer['quantity'];


		$offer_product_id = $offer['item_id'];


		$total_offer_products++;

		// settings
		$offered_product         = wc_get_product($offer_product_id);
		if(!$offered_product) {
			continue;
		}

		$product_title           = $offered_product->get_name();
		$regular_price           = $offered_product->get_regular_price();
		$in_percentage           = revenue()->calculate_discount_percentage($regular_price, $offered_price);
		$is_last_product         = $offer_index == ($offer_length - 1);
		$image                   = wp_get_attachment_image_src(get_post_thumbnail_id($offer_product_id), 'single-post-thumbnail');
		$price_data              = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price, true );
		$offered_price           = $price_data['price'];
		$product_lists[$offer_product_id] = ['product_id' => $offer_product_id, 'product_title' => $product_title, 'required' => false, 'selected' => false, 'quantity' => 1];
        if(isset($offer['trigger']) && $is_trigger_product_required) {
            $product_lists[$offer_product_id]['required']=true;
            $product_lists[$offer_product_id]['selected']=true;
        }
        if(isset($product_lists[$offer_product_id]['selected']) && $product_lists[$offer_product_id]['selected']) {
            $selected_items[$offer_product_id] = $product_lists[$offer_product_id]['quantity'];
        }
		$quantity = max($offer_qty, isset($selected_items[$offer_product_id]) ? $selected_items[$offer_product_id] : 1);
        $product_lists[$offer_product_id]['quantity']=$quantity;

        if (!isset($offer_data[$offer_product_id])) {
			$offer_data[$offer_product_id] = $offer;
		}



		$offer_data[$offer_product_id]['regular_price'] = $regular_price;
		if (isset($selected_items[$offer_product_id])) {
			$total_regular_price += (floatval($selected_items[$offer_product_id]) * floatval($regular_price));
			$total_sale_price +=  (floatval($selected_items[$offer_product_id]) * ($offered_price ? floatval($offered_price) : floatval($regular_price)));
		}
		$isTagEnabled = isset($offer['isEnableTag']) && 'yes' == $offer['isEnableTag'];



		?>
		<div id="revenuex-campaign-item-<?php echo esc_attr($product_id) . '-' . esc_attr($campaign['id']); ?>" class="revx-campaign-item"
			data-product-id="<?php echo esc_attr($product_id); ?>" data-campaign-id="<?php echo esc_attr($campaign['id']); ?>" style="<?php echo $isTagEnabled?esc_attr($taggedProductStyle): esc_attr($product_style); ?>">

			<?php
			if ('list' == $view_mode) {
                echo wp_kses(
					revenue()->get_template_part(
						'image',
						array(
							'offered_product'  => $offered_product,
							'generated_styles' => $generated_styles,
                            'current_campaign' => $campaign
						)
					),
					revenue()->get_allowed_tag()
				);
			?>
				<div class="revx-campaign-text-content">
					<?php
                    echo wp_kses(
                        revenue()->get_template_part(
                            'product_title',
                            array(
                                'offered_product'  => $offered_product,
                                'generated_styles' => $generated_styles,
                                'current_campaign' => $campaign
                            )
                        ),
                        revenue()->get_allowed_tag()
                    );
					?>
					<div class="revx-pricing-wrapper">
						<?php
                            echo wp_kses(
                                revenue()->get_template_part(
                                    'price_container',
                                    array(
                                        'quantity'         => $quantity,
                                        'offered_product'  => $offered_product,
                                        'generated_styles' => $generated_styles,
                                        'regular_price'    => $regular_price,
                                        'offered_price'    => $offered_price,
                                        'current_campaign' => $campaign
                                    )
                                ),
                                revenue()->get_allowed_tag()
                            );
                            echo wp_kses(
                                revenue()->get_template_part(
                                    'save',
                                    array(
                                        'generated_styles' => $generated_styles,
                                        'regular_price' => $regular_price,
                                        'offered_price' => $offered_price,
                                        'current_campaign' => $campaign,
                                        'quantity' => $quantity,
                                        'message'  => $price_data['message'],
                                    )
                                ),
                                revenue()->get_allowed_tag()
                            );						?>
					</div>
					<?php
                    echo wp_kses(
                        revenue()->get_template_part(
                            'quantity_selector',
                            array(
                                'quantity'     => $quantity,
                                'min_quantity' => 'free' == $offer_type ? 1 : $offer_qty,
                                'max_quantity' => 'free' == $offer_type ? $offer_qty : '',
                                'value'        => $quantity,
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
			} else {
                echo wp_kses(
					revenue()->get_template_part(
						'image',
						array(
							'offered_product'  => $offered_product,
							'generated_styles' => $generated_styles,
                            'current_campaign' => $campaign
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
                            'current_campaign' => $campaign
                        )
                    ),
                    revenue()->get_allowed_tag()
                );
                echo wp_kses(
                    revenue()->get_template_part(
                        'price_container',
                        array(
                            'quantity'         => $quantity,
                            'offered_product'  => $offered_product,
                            'generated_styles' => $generated_styles,
                            'regular_price'    => $regular_price,
                            'offered_price'    => $offered_price,
                            'current_campaign' => $campaign
                        )
                    ),
                    revenue()->get_allowed_tag()
                );
                echo wp_kses(
                    revenue()->get_template_part(
                        'save',
                        array(
                            'generated_styles' => $generated_styles,
                            'regular_price' => $regular_price,
                            'offered_price' => $offered_price,
                            'current_campaign' => $campaign,
                            'quantity' => $quantity,
                            'message'  => $price_data['message'],
                        )
                    ),
                    revenue()->get_allowed_tag()
                );	
                echo wp_kses(
                    revenue()->get_template_part(
                        'quantity_selector',
                        array(
                            'quantity'     => $quantity,
                            'min_quantity' => 'free' == $offer_type ? 1 : $offer_qty,
                            'max_quantity' => 'free' == $offer_type ? $offer_qty : '',
                            'value'        => $quantity,
                            'generated_styles' => $generated_styles,
                            'current_campaign' => $campaign,
                            'offered_product' => $offered_product,
                        )
                    ),
                    revenue()->get_allowed_tag()
                );
			}
			?>
			<?php if ($isTagEnabled) {
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
			} ?>
		</div>
        <?php
        if(!$is_last_product) {
            echo wp_kses(
                revenue()->get_template_part(
                    'add_campaign',
                    array(
                        'current_campaign' => $campaign,
                        'generated_styles' => $generated_styles,
                    )
                ),
                revenue()->get_allowed_tag()
            );
        }
	}
}

$items_content .= ob_get_clean();

if (!$items_content) {
	return;
}

$container_class       = 'list' == $view_mode ? 'revx-campaign-list revx-frequently-bought-together revx-frequently-bought-together-list' : 'revx-frequently-bought-together-grid revx-frequently-bought-together revx-campaign-grid';

if ($view_mode == 'list') {

	$wrapper_style         = revenue()->get_style($generated_styles, 'wrapper');
	$total_price_style     = revenue()->get_style($generated_styles, 'totalPrice');
	$trigger_product_style = revenue()->get_style($generated_styles, 'triggerProductWrapper');
	$product_gap_style     = revenue()->get_style($generated_styles, 'productGap');

    $selected_product_header_desc = sprintf(
		__( 'For <span class="revx-selected-product-count"> %s</span> Items', 'revenue-pro' ),
		count( $selected_items )
		);
	ob_start();
?>
	<div  data-campaign-id="<?php echo esc_attr($campaign['id']); ?>" class="revx-campaign-container__wrapper" style="<?php echo esc_attr($wrapper_style); ?> width:100%;">

		<?php
            echo wp_kses( $items_content,revenue()->get_allowed_tag() ); 
		?>

		<div class="revx-triggerProduct" style="<?php echo esc_attr($trigger_product_style); ?>">
			<div class="revx-justify-space">
				<div class="revx-item-options">
					<?php

					foreach ($product_lists as $id => $item) {
						$is_checked = isset($selected_items[$id]) ? true : false;
						$is_required = isset($item['required']) && $item['required'] ? true : false;
					?>
						<div data-min-quantity="<?php echo $is_required ? 1 : esc_attr($item['quantity']); ?>" data-product-id="<?php echo esc_attr($id); ?>" class="revx-item-option revx-flex <?php echo $is_required ? esc_attr('revx-item-required') : ''; ?>" style="<?php echo esc_attr($product_gap_style); ?>">
							<?php
							echo wp_kses( revenue()->get_template_part('checkbox', ['generated_styles' => $generated_styles, 'required' => $is_required, 'selected' => $is_checked]), revenue()->get_allowed_tag()); 
							echo wp_kses(revenue()->tag_wrapper($campaign, $generated_styles, 'selectedProdTitle', $item['product_title'], 'revx-product-title', 'h5'),revenue()->get_allowed_tag()); 
							?>
						</div>
					<?php
					}

					?>


				</div>
				<div>
					<?php
					echo wp_kses(revenue()->get_template_part('total_price_container', ['current_campaign'=>$campaign, 'generated_styles'=>$generated_styles,'regular_price' => $total_regular_price, 'sale_price' => $total_sale_price]),revenue()->get_allowed_tag()); 
					echo wp_kses(revenue()->tag_wrapper($campaign,$generated_styles, 'productCountTitle', $selected_product_header_desc, 'revx-space-nowrap', 'div'),revenue()->get_allowed_tag()); 
					?>
				</div>
			</div>
		</div>
	</div>

	<?php
        echo wp_kses(
            revenue()->get_template_part(
                'add_to_cart',
                array(
                    'generated_styles' => $generated_styles,
                    'current_campaign' => $campaign,
                    'offered_product' => $offered_product,
                )
            ),
            revenue()->get_allowed_tag()
        );
	?>

	<input type="hidden" name="<?php echo 'revx-offer-data-' . esc_attr($campaign['id']); ?>" value="<?php echo htmlspecialchars(wp_json_encode($offer_data)); 
																																	?>" />

	<input type="hidden" name="<?php echo 'revx-required-product-' . esc_attr($campaign['id']); ?>" value="<?php echo  esc_attr($product_id); 
																																		?>" />
    <input type="hidden" name="<?php echo 'revx-fbt-selected-items-' . esc_attr($campaign['id']) ?>" value="<?php echo  esc_attr(wp_json_encode($selected_items)); 
																																		?>" />

<?php
	$output_content .= ob_get_clean();
} else {

	// style
	$heading_text             = isset($campaign['banner_heading']) ? $campaign['banner_heading'] : __('Frequently Bought Together Heading', 'revenue-pro');
	$container_style          = revenue()->get_style($generated_styles, 'container');
	$wrapper_style            = revenue()->get_style($generated_styles, 'wrapper');
	$product_gap_style        = revenue()->get_style($generated_styles, 'productGap');
	$product_wrapper_style    = revenue()->get_style($generated_styles, 'regularProductWrapper');
	$total_price_style        = revenue()->get_style($generated_styles, 'totalPrice');
	$addToCartQuantity__style = revenue()->get_style($generated_styles, 'addToCartQuantity');
	$trigger_product_style    = revenue()->get_style($generated_styles, 'triggerProductWrapper');

    $selected_product_header_desc = sprintf(
		__( 'For <span class="revx-selected-product-count"> %s</span> Items', 'revenue-pro' ),
		count( $selected_items )
		);
	ob_start();

?>

	<div class="revx-campaign-container__wrapper" data-campaign-id="<?php echo esc_attr($campaign['id']); ?>" style="<?php echo esc_attr($wrapper_style); ?>">
		<div class="revx-campaign-container__product">
			<div class="revx-regular-product revx-slider revx-align-center revx-slider-items-wrapper" style="<?php echo esc_attr($product_wrapper_style); ?>">
            <?php echo wp_kses( revenue()->get_slider_icon( $generated_styles, 'left' ), revenue()->get_allowed_tag() );  ?>
				<div class="revx-slider-container revx-campaign-text-content"> <?php echo revenue()->kses_campaign_view($items_content); ?> </div>
			<?php echo wp_kses( revenue()->get_slider_icon( $generated_styles, 'right' ), revenue()->get_allowed_tag() );  ?>

			</div>
			<div class="revx-triggerProduct revx-fbt-options revx-campaign-text-content" style="<?php echo esc_attr($trigger_product_style); ?>">

				<div class="revx-item-options">
					<?php

					foreach ($product_lists as $id => $item) {
						$is_checked = isset($selected_items[$id]) ? true : false;

						$is_required = isset($item['required']) && $item['required'] ? true : false;

					?>
						<div data-min-quantity="<?php echo $is_required ? 1 : esc_attr($offer_qty); ?>"  data-product-id="<?php echo esc_attr($id); ?>" class="revx-item-option revx-flex <?php echo $is_required ? 'revx-item-required' : ''; ?>" style="<?php echo esc_attr($product_gap_style); ?>">
							<?php
							echo wp_kses( revenue()->get_template_part('checkbox', ['generated_styles' => $generated_styles, 'required' => $is_required, 'selected' => $is_checked]), revenue()->get_allowed_tag()); 
							echo wp_kses(revenue()->tag_wrapper($campaign, $generated_styles, 'selectedProdTitle', $item['product_title'], 'revx-product-title', 'h5'),revenue()->get_allowed_tag()); 
							?>
						</div>
					<?php
					}
					?>
				</div>
				<?php
				echo wp_kses(revenue()->get_template_part('total_price_container', ['current_campaign'=>$campaign, 'generated_styles'=>$generated_styles,'regular_price' => $total_regular_price, 'sale_price' => $total_sale_price]),revenue()->get_allowed_tag()); 
                echo wp_kses(revenue()->tag_wrapper($campaign,$generated_styles, 'productCountTitle', $selected_product_header_desc, 'revx-space-nowrap', 'div'),revenue()->get_allowed_tag()); 
                echo wp_kses(
                    revenue()->get_template_part(
                        'add_to_cart',
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
		</div>

		<?php
		?>
	</div>
	<input type="hidden" name="<?php echo 'revx-offer-data-' . esc_attr($campaign['id']) ?>" value="<?php echo htmlspecialchars(wp_json_encode($offer_data)); 
																																	?>" />

	<input type="hidden" name="<?php echo 'revx-required-product-' . esc_attr($campaign['id']) ?>" value="<?php echo  esc_attr($product_id); 
																																		?>" />
	<input type="hidden" name="<?php echo 'revx-fbt-selected-items-' . esc_attr($campaign['id']) ?>" value="<?php echo  esc_attr(wp_json_encode($selected_items)); 
																																		?>" />

<?php
	$output_content .= ob_get_clean();
}
revenue()->inpage_container($campaign, $generated_styles,$output_content, $container_class, $placement); 