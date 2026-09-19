<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Normal Discount inpage Template
 *
 * This file handles the display of normal discount offers in a inpage container.
 *
 * @package    Revenue
 * @subpackage Templates
 * @version    1.0.0
 */

namespace Revenue;

use Revenue;

/**
 * The Template for displaying revenue view
 *
 * @package Revenue
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Fetch required data.
$template_data        = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
$offers               = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
$timer_prefix         = $campaign['countdown_timer_prefix'] ?? __( 'Hurry! Offer Ends in ', 'revenue-pro' );
$trigger_type         = $campaign['campaign_trigger_type'];
$trigger_items        = revenue()->get_campaign_meta( $campaign['id'], 'campaign_trigger_items', true );
$cart_items           = WC()->cart->get_cart();
$cart_item_ids        = array();
$campaign_id          = $campaign['id'];
$double_order_session = WC()->session->get( 'revenue_double_order_session_data', array() );
$double_order_session = isset( $double_order_session[ $campaign_id ] ) ? $double_order_session[ $campaign_id ] : array();

$device_manager       = $template_data['campaign_visibility_enabled'] ?? array();
$device_manager_class = '';

if ( is_array( $device_manager ) && ! empty( $device_manager ) ) {
	if ( isset( $device_manager['desktop'] ) && 'no' === $device_manager['desktop'] ) {
		$device_manager_class .= ' revx-hide-desktop';
	}
	if ( isset( $device_manager['tablet'] ) && 'no' === $device_manager['tablet'] ) {
		$device_manager_class .= ' revx-hide-tablet';
	}
	if ( isset( $device_manager['mobile'] ) && 'no' === $device_manager['mobile'] ) {
		$device_manager_class .= ' revx-hide-mobile';
	}
}

foreach ( $cart_items as $key => $value ) {
	$cart_item_ids[] = $value['variation_id'] ? $value['variation_id'] : $value['product_id'];
}
$is_all_product = 'all_products' === $trigger_type;
if ( $is_all_product ) {
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

$is_timer_enable = 'yes' === $campaign['countdown_timer_enabled'];
$timer           = '0';
if ( $is_timer_enable ) {
	$timer = $campaign['double_order_countdown_duration'];
}

$placement_settings = $campaign['placement_settings'];
$countdown          = isset( $campaign['double_order_countdown_duration'] ) ? $campaign['double_order_countdown_duration'] : 100;

// used anonymous function to avoid polluting global namespace
// TODO: remove this when we move to OOP
$render_product_item = function ( $template_data, $trigger_product, $offer, $is_selected, $campaign_id ) {
	ob_start();
	?>
	<div class="revx-d-flex revx-item-center">
		<div class="revx-checkbox-left revx-checkbox-wrapper <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'productPriceContainer' ) ); ?>">
			<div
				data-product-id="<?php echo esc_attr( $trigger_product['item_id'] ); ?>"
				data-multiplier = "<?php echo esc_attr( $offer['quantity'] ); ?>"
				data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>"
				data-type="<?php echo esc_attr( $offer['type'] ); ?>"
				data-value="<?php echo esc_attr( $offer['value'] ); ?>"
				data-index="<?php echo esc_attr( $offer['index'] ); ?>"
				data-is-checked="<?php echo esc_attr( $is_selected ? 'yes' : 'no' ); ?>"

				class="revx-checkbox-container revx-double-order-checkbox-specific revx-<?php echo ( $is_selected ) ? 'active' : 'inactive'; ?>" 
				style="font-size: 24px"
			>
				<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 16 16">
					<rect width="11.8" height="11.8" x="2.102" y="2.1"
						stroke="var(--revx-checkbox-bg-color,#000000)"
						rx="2.7"></rect>
					<rect width="11.4" height="11.4" x="2.502" y="2.5"
						fill="var(--revx-checkbox-bg-color,#000000)"
						class="revx-checkbox-inactive"
						rx="2"></rect>
					<path stroke="var(--revx-checkbox-icon-color,#fff)"
						stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"
						d="m11.4 5.9-4.2 4.2-2-2"
						class="revx-checkbox-inactive"></path>
				</svg>
			</div>
		</div>
		<?php
		echo Revenue_Template_Utils::render_image(
			array(
				'src'   => $trigger_product['thumbnail'],
				'alt'   => $trigger_product['item_name'],
				'class' => Revenue_Template_Utils::get_element_class( $template_data, 'productImage' ) . ' revx-lh-0 revx-shrink-0',
			)
		);
		?>
		<div class="revx-d-flex revs-item-center revx-justify-between revx-w-full">
			<?php
			echo Revenue_Template_Utils::render_rich_text(
				$template_data,
				'productTitle',
				$trigger_product['item_name'],
				'revx-ellipsis-1',
				'max-width: 8rem;',
				'text',
				'',
				false,
				'',
				true
			);
			?>
			<?php echo Revenue_Template_Utils::revenue_render_product_price( $trigger_product, 'list', $template_data ); ?>
		</div>
	</div>
	<?php
	return ob_get_clean();
};

$animation_delay_between = $campaign['double_order_animation_delay_between'] . 's';

?>
<?php // data-campaign-type and data-is-all-product is used in jquery double_order.js ?>
<div
	data-campaign-type="double_order"
	data-is-all-product="<?php echo esc_attr($is_all_product); ?>"
 	class="
		<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'wrapper' ) ); ?> 
		revx-d-flex revx-flex-column <?php echo esc_attr( $device_manager_class ); ?>
	"
	style="--revx-double-order-animation-delay-between: <?php echo esc_attr( $animation_delay_between ) ?>;"
	
>
	<?php if ( $is_timer_enable ) { ?>
		<div
			class=" revx-countdown-timer-container <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'doubleOrderCountdown' ) ); ?> revx-d-flex revx-item-center revx-double-order-countdown-timer-container"
			data-countdown-duration="<?php echo esc_attr( $countdown ); ?>"
			>
			<?php echo Revenue_Template_Utils::render_rich_text( $template_data, 'doubleOrderCountdown', $timer_prefix ); ?>
			<div
				class="revx-d-flex revx-item-center revx-gap-4 revx-double-order-timer revx-countdown-timer"
				style="color: var(--revx-timer-color)"
			>
				<div class="revx-countdown-timer" >
					<span class="revx-days">--</span> <span class="revx-days-label">D :</span>
					<span class="revx-hours">--</span> <span class="revx-hours-label">H :</span>
					<span class="revx-minutes">--</span> <span class="revx-minutes-label">M :</span>
					<span class="revx-seconds">--</span><span>S</span>
				</div>
			</div>
		</div>
		<?php
	}
	foreach ( $offers as $offer_index => $offer ) {
		$quantity    		 = $offer['quantity'];
		$offer_text  		 = $offer['offer_text'];
		$offer_type  		 = $offer['type'];
		$offer_value 		 = $offer['value'];
		$offer['index'] 	 = $offer_index;
		$cur_success_message = $success_message;
		// only 2 types of offer is possible in double order
		// percentage and fixed
		if ('percentage' === $offer_type) {
			$offer_text = str_replace(
				array('{discount_value}', '{qty}'), 
				array($offer_value . '%', $quantity), 
				$offer_text
			);
			$cur_success_message = str_replace( 
				'{discount_value}', 
				$offer_value . '%', 
				$success_message 
			);
		} else {
			$offer_text = str_replace(
				array( '{discount_value}', '{qty}' ),
				array( strip_tags( wc_price( $offer_value ) ), $quantity ),
				$offer_text
			);
			$cur_success_message = str_replace( 
				'{discount_value}', 
				strip_tags(wc_price( $offer_value )), 
				$success_message 
			);
		}
		$animation_class = 'yes' === $campaign['double_order_animation_enabled'] 
			? ' revx-double-order-animation revx-double-order-animation-' .  esc_attr( $campaign['double_order_animation_type'] )
			: '';
		?>
		<div 
			class="revx-double-order-items <?php echo esc_attr($animation_class) ?>"
			data-index="<?php echo esc_attr( $offer_index ); ?>" 
			<?php // data-double-order-item is used in jqeury to find double order items ?>
			data-double-order-item="Item" 
			data-success-message="<?php echo esc_attr($cur_success_message); ?>" 
			data-default-message="<?php echo esc_attr($offer_text); ?>" 
		>
			<div
				class="
					<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'doubleOrderHeading' ) ); ?> 
					revx-double-order-header<?php echo $is_all_product ? '' : '2'; ?> 
					revx-d-flex revx-item-center revx-gap-8
				"
			>
			<?php
			if ( $is_all_product ) {
				$saved_index = -1;
				if ( isset( $double_order_session['index'] ) ) {
					$saved_index = $double_order_session['index'];
				}
				$is_selected = $offer_index === $saved_index;
				?>
				<div 
					id="revx-double-order-checkbox-<?php echo esc_attr( $offer_index ); ?>" 
					class="revx-checkbox-wrappers revx-products-lists-specific" 
					data-index="<?php echo esc_attr( $offer_index ); ?>"
				>
					<div
						data-multiplier = "<?php echo esc_attr( $quantity ); ?>"
						data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>"
						data-type="<?php echo esc_attr( $offer_type ); ?>"
						data-value="<?php echo esc_attr( $offer_value ); ?>"
						data-index="<?php echo esc_attr( $offer_index ); ?>"
						data-is-checked="<?php echo esc_attr( $is_selected ? 'yes' : 'no' ); ?>"

						class="revx-checkbox-container revx-double-order-checkbox-specific revx-<?php echo ( $is_selected ) ? 'active' : 'inactive'; ?>" 
						style="font-size: 24px"
					>
						<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 16 16">
							<rect width="11.8" height="11.8" x="2.102" y="2.1"
								stroke="var(--revx-checkbox-bg-color,#000000)"
								rx="2.7"></rect>
							<rect width="11.4" height="11.4" x="2.502" y="2.5"
								fill="var(--revx-checkbox-bg-color,#000000)"
								class="revx-checkbox-inactive"
								rx="2"></rect>
							<path stroke="var(--revx-checkbox-icon-color,#fff)"
								stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"
								d="m11.4 5.9-4.2 4.2-2-2"
								class="revx-checkbox-inactive"></path>
						</svg>
					</div>
				</div>
				<?php
			}
			?>
			<?php echo Revenue_Template_Utils::render_rich_text( $template_data, 'DoubleOrderMessage', $offer_text ); ?>
			</div>
			<?php if ( ! $is_all_product ) { ?>
				<div
					class="revx-products-lists-specific <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'doubleOrderProducts' ) ); ?> revx-d-flex revx-flex-column"
					data-index="<?php echo esc_attr( $offer_index ); ?>"
					data-offer-group-index="<?php echo esc_attr( $offer_index ); ?>"
				>
				
					<?php
					$saved_index = -1;
					if ( isset( $double_order_session['index'] ) ) {
						$saved_index = $double_order_session['index'];
					}
					
					foreach ( $trigger_items as $index => $trigger_product ) {
						$item_id = $trigger_product['item_id'];
						
						
						if ( $trigger_type === 'category' ) {
							$eligible_product_ids = array();
							foreach ( $cart_items as $key => $value ) {
								// get categories of product in cart with product_id which is parent id in variable product case
								$cat_ids = revenue()->get_product_category_ids( $value['product_id'] );
								// if any of the category of product in cart matches with trigger category then add that product to eligible products
								if ( in_array( $item_id, $cat_ids ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
									// if variable product then add variation id else product id
									$eligible_product_ids[] = $value['variation_id'] ? $value['variation_id'] : $value['product_id'];
								}
							}
							
							$eligible_product_ids = array_unique( $eligible_product_ids );
							// show all products which are in cart and belongs to trigger category
							foreach ( $eligible_product_ids as $product_id ) {
								$cart_product   = wc_get_product( $product_id );
								$product_image = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'thumbnail' );
								$image         = $product_image ? $product_image[0] : wc_placeholder_img_src();
								$product_title = $cart_product->get_title();
								$regular_price = $cart_product->get_price(); 
								$sale_price    = $cart_product->get_price();
								
								$product_array = array(
									'item_id'       => $product_id,
									'item_name'     => $product_title,
									'thumbnail'     => $image,
									'regular_price' => $regular_price,
									'sale_price'    => $sale_price,
								);
								$is_selected = 
									$offer_index === $saved_index && 
									in_array( $product_id, $double_order_session['products'] ?? array() ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict	
								
								echo $render_product_item( $template_data, $product_array, $offer, $is_selected, $campaign_id );
							}
						} else {
							// only show products that are in cart
							if ( in_array( $item_id, $cart_item_ids ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
								// updating regular price with cart price to show frontend
								$trigger_product['regular_price'] = wc_get_product( $item_id )->get_price(); 
								$is_selected = 
									$offer_index === $saved_index && 
									in_array( $item_id, $double_order_session['products'] ?? array() ); //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict	
								
								echo $render_product_item( $template_data, $trigger_product, $offer, $is_selected, $campaign_id);
							}
						}
					}
					?>
				</div>
			<?php } ?>
		</div>
		<?php
	}
	?>
</div>


<?php
