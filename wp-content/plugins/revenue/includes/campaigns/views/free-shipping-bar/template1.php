<?php
/**
 * Normal Discount inpage Template
 *
 * This file handles the display of normal discount offers in a inpage container.
 *
 * @package    Revenue
 * @subpackage Templates
 * @version    1.0.0
 */

//phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar

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
$view_mode              = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
$template_data          = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
$offers                 = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
$is_grid_view           = 'grid' === $view_mode;
$current_page           = revenue()->get_current_page();
$placement_settings     = $campaign['placement_settings'];
$slider_columns         = json_encode( Revenue_Template_Utils::get_slider_data( $template_data ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
$products_wrapper_class = $is_grid_view ? 'revx-slider-wrapper' : '';
$is_all_page_active     = isset( $placement_settings['all_page'] ) ? 'yes' === $placement_settings['all_page']['status'] : false;
$is_other_page_active   = isset( $placement_settings[ $current_page ] ) ? 'yes' === $placement_settings[ $current_page ]['status'] : false;
$page_key               = ( $is_all_page_active && ! $is_other_page_active ) ? 'all_page' : $current_page;
$display_style          = $placement_settings[ $page_key ]['display_style'] ?? 'inpage';
$is_upsell_on           = 'yes' === $campaign['upsell_products_status'];
$is_progress_bar        = 'yes' === $campaign['is_show_free_shipping_bar'];
$is_cta_btn             = 'yes' === $campaign['enable_cta_button'];
$is_campaign_close      = 'yes' === $campaign['show_close_icon'];

$button_link = ! empty( $offers[0]['cta_link'] ) ? $offers[0]['cta_link'] : wc_get_page_permalink( 'shop' );

$size         = $is_upsell_on ? 92 : 60;
$stroke_width = $is_upsell_on ? 12 : 8;
$label_size   = $is_upsell_on ? 18 : 16;
$cart_total   = $this->get_eligible_cart_total( $offers[0]['free_shipping_based_on'], true );
$total_goal   = 0;

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

foreach ( $offers as $offer ) {
	if ( isset( $offer['required_goal'] ) ) {
		$total_goal += floatval( $offer['required_goal'] );
	}
}

$progress = $total_goal > 0 ? min( ( $cart_total / $total_goal ) * 100, 100 ) : 0;


$required_goal   = 0;
$current_message = '';
$reward_message  = '';

foreach ( $offers as $index => $offer ) {
	if ( ! isset( $offer['required_goal'] ) ) {
		continue;
	}
	$required_goal += floatval( $offer['required_goal'] );
	if ( 0 == $cart_total ) {
		$current_message  = isset( $offer['promo_message'] ) ? $offer['promo_message'] : '';
		$remaining_amount = $cart_total - $required_goal;

		$current_message = str_replace( '{remaining_amount}', wc_price( abs( $remaining_amount ) ), $current_message );

	} elseif ( $cart_total < $required_goal ) {
		$current_message = isset( $offer['before_message'] ) ? $offer['before_message'] : '';

		$remaining_amount = $cart_total - $required_goal;

		$current_message = str_replace( '{remaining_amount}', wc_price( abs( $remaining_amount ) ), $current_message );

		break;
	} else {
		$reward_message  = isset( $offer['after_message'] ) ? $offer['after_message'] : '';
		$current_message = $reward_message;
	}
}

// handle Upsale products.
$upsell_products = array();

if ( 'yes' === $campaign['upsell_products_status'] ) {

	$data            = $campaign['upsell_products'];
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
				'sale_price'    => 0 == $discount_amount ? false : $discounted_price,
				'quantity'      => $quantity,
				'type'          => $order['type'],
				'value'         => isset( $order['value'] ) ? $order['value'] : '',
			);
		}
	}
}
$is_drawer = 'drawer' === $display_style;
if ( $is_drawer ) {
	$wrapper_id = 'drawerWrapper';

	$drawer_position = $placement_settings[ $page_key ]['drawer_position'] ?? 'top-right';

	$radius          = ( $size - $stroke_width ) / 2;
	$circumference   = 2 * pi() * $radius;
	$progress_offset = $circumference - ( $progress / 100 ) * $circumference;
} elseif ( $is_all_page_active && ! $is_other_page_active ) {
	$wrapper_id = 'allSideProgressWrapper';
} else {
	$wrapper_id = 'wrapper';
}

ob_start();

?>

<div 
	class="
		<?php
		echo esc_attr(
			Revenue_Template_Utils::get_element_class(
				$template_data,
				$wrapper_id
			)
		);
		?>
		<?php echo esc_attr( $display_style ); ?>
		<?php echo $is_drawer ? esc_attr( $drawer_position ) : ''; ?>  
		revx-d-flex 
		<?php echo $is_drawer ? esc_attr( 'revx-drawer-container' ) : esc_attr( 'revx-w-full' ); ?> 
		<?php echo ( $is_drawer || ( $is_all_page_active && ! $is_other_page_active ) ) ? '' : esc_attr( 'revx-flex-column' ); ?> 
		<?php echo esc_attr( $device_manager_class ); ?>
	" 
	style="box-sizing: border-box;" <?php // handle overflow-x on some themes ( ex: 2025). ?>
	id="revx-progress-<?php echo esc_attr( $display_style ); ?>"
	data-position="<?php echo esc_attr( $display_style ); ?>"
	data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>"
	data-campaign-type="free_shipping_bar"
	data-container-level="top"
	data-cart-total="<?php echo esc_attr( $cart_total ); ?>"
	data-based-on="<?php echo esc_attr( $offers[0]['free_shipping_based_on'] ); ?>"
	data-progress="<?php echo esc_attr( $progress ); ?>"
	data-show-confetti="<?php echo esc_attr( $campaign['show_confetti'] ); ?>"
	data-final-message="<?php echo esc_attr( $offers[0]['after_message'] ?? '' ); ?>"
	data-radius="<?php echo esc_attr( $is_drawer ? $radius : '' ); ?>"
>
<?php
if ( $is_drawer ) {
	if ( $is_campaign_close ) {
		echo wp_kses( Revenue_Template_Utils::render_campaign_close( $template_data, 'revx-drawer-closer' ), revenue()->get_allowed_tag() );
	}
	?>
	<div
		class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'circularProgressContainer' ) ); ?> revx-circular-progress-container revx-d-flex revx-item-center revx-justify-between revx-flex-column revx-drawer-opener"
	>
		<div
			class="revx-relative revx-d-flex revx-item-center revx-justify-center"
		>
			<svg width="<?php echo esc_attr( $size ); ?>" height="<?php echo esc_attr( $size ); ?>">
				<circle
					class="revx-progress-empty"
					r="<?php echo esc_attr( $radius ); ?>"
					cx="<?php echo esc_attr( $size / 2 ); ?>"
					cy="<?php echo esc_attr( $size / 2 ); ?>"
					style="
						stroke: var(
							--revx-circlular-progress-bar-inactive,
							#31353f
						);
						stroke-width: <?php echo esc_attr( $stroke_width ); ?>;
						fill: none;
					"
				></circle>
				<circle
					class="revx-progress-active"
					r="<?php echo esc_attr( $radius ); ?>"
					cx="<?php echo esc_attr( $size / 2 ); ?>"
					cy="<?php echo esc_attr( $size / 2 ); ?>"
					style="
						stroke: var(
							--revx-circlular-progress-bar-active,
							#f2ae40
						);
						stroke-width: <?php echo esc_attr( $stroke_width ); ?>;
						stroke-dasharray: <?php echo esc_attr( $circumference ); ?>;
						stroke-dashoffset: <?php echo esc_attr( $progress_offset ); ?>;
						stroke-linecap: round;
						fill: none;
						transition: stroke-dashoffset 500ms ease-in-out;
					"
				></circle>
			</svg>
			<div class="revx-circular-text revx-absolute" style="font-size: <?php echo esc_attr( ( floor( $progress ) !== $progress ) ? $label_size - 3 : $label_size ); ?>px"><?php echo esc_html( round( $progress, 2 ) ); ?>%</div>
		</div>
		<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'drawerCompleteMessage' ) ); ?>
	</div>
	<div
		class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'campaignDrawerContent' ) ); ?> revx-d-flex revx-item-center revx-drawer-content revx-w-full"
	>
		<div
			class="revx-d-flex revx-flex-column revx-w-full"
			style="gap: var(--revx-drawer-content-gap)"
		>
			<div class="revx-d-flex revx-item-center revx-justify-center revx-flex-wrap revx-gap-10">
				<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'fsbHeading', $current_message, 'revx-text-center', $is_cta_btn ? 'max-width: 24rem' : '' ) ); ?>
				<?php
				if ( $is_cta_btn ) {
					echo wp_kses_post( Revenue_Template_Utils::render_link( $template_data, 'shopNowButton', $button_link, '_blank' ) );
				}
				?>
			</div>
			<?php Revenue_Template_Utils::render_products_container( $campaign, $template_data, $placement, true ); ?>
		</div>
	</div>
	<?php
} else {
	if ( $is_all_page_active && ! $is_other_page_active ) {
		echo '<div
			class="revx-d-flex revx-flex-column"
			style="
				max-width: var(--revx-container-max-width);
				max-height: var(--revx-container-max-height);
				width: 100%;
				gap: var(--revx-container-gap);
				margin: 0px auto;
			"
		>';
	}
	?>
		<div class="revx-d-flex revx-item-center revx-justify-center revx-flex-wrap revx-gap-10">
			<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'fsbHeading', $current_message, 'revx-text-center', $is_cta_btn ? 'max-width: 24rem' : '' ) ); ?>
			<?php
			if ( $is_cta_btn ) {
				echo wp_kses_post( Revenue_Template_Utils::render_link( $template_data, 'shopNowButton', $button_link, '_blank' ) );
			}
			?>
		</div>
		<?php
		if ( $is_progress_bar ) {
			echo wp_kses( Revenue_Template_Utils::render_progressbar( $template_data, 'CampaignProgressbar', $progress, $campaign ), revenue()->get_allowed_tag() ); //phpcs:ignore
		}

		Revenue_Template_Utils::render_products_container( $campaign, $template_data, $placement, true );

		if ( $is_all_page_active && ! $is_other_page_active ) {
			echo '</div>';
		}
}
?>
	<input type="hidden" name="revenue_free_shipping_offer" value="<?php echo esc_html( htmlspecialchars( wp_json_encode( $offers ) ) ); ?>" />
	<input type="hidden" name="revenue_upsell_products" value="<?php echo esc_html( htmlspecialchars( wp_json_encode( $upsell_products ) ) ); ?>" />
</div>

<?php
$output = ob_get_clean();
