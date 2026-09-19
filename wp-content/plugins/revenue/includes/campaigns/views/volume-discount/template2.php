<?php
/**
 * Volume Discount Template with Attribute Selection
 *
 * @package Revenue
 */

namespace Revenue;

use Revenue\Services\Revenue_Product_Context;

defined( 'ABSPATH' ) || exit;

$product = Revenue_Product_Context::get_product_context();
if ( ! $product ) {
	return;
}
$product_id   = $product->get_id();
$product_type = $product->get_type();

$placement_settings = revenue()->get_placement_settings( $campaign['id'] );
$display_style      = isset( $placement_settings['display_style'] ) ? $placement_settings['display_style'] : 'inpage';

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

$extra_class = '';
if ( 'popup' === $display_style ) {
	$extra_class = 'revx-popup-init-size';
}
if ( 'floating' === $display_style ) {
	$extra_class = 'revx-floating-init-size';
}

$campaign_id       = $campaign['id'];
$campaign_type     = $campaign['campaign_type'];
$template_data     = revenue()->get_campaign_meta( $campaign_id, 'builder', true );
$offers            = revenue()->get_campaign_meta( $campaign_id, 'offers', true ) ?? array();
$product_attr_data = array(
	array(
		'label'  => 'Color',
		'option' => array(
			array(
				'label' => 'Black',
				'value' => 'black',
			),
			array(
				'label' => 'White',
				'value' => 'white',
			),
		),
	),
	array(
		'label'  => 'Storage',
		'option' => array(
			array(
				'label' => '256 GB Storage',
				'value' => '256gb',
			),
			array(
				'label' => '512 GB Storage',
				'value' => '512gb',
			),
		),
	),
	array(
		'label'  => 'RAM',
		'option' => array(
			array(
				'label' => '16GB Ram',
				'value' => '16gb',
			),
			array(
				'label' => '32GB Ram',
				'value' => '32gb',
			),
		),
	),
);

if ( ! function_exists( 'Revenue\get_product_attribute_values' ) ) {
	/**
	 * Get formatted product attribute values for variable products.
	 *
	 * @param WC_Product $product The WooCommerce product object.
	 * @return array Array of formatted attributes with their values.
	 */
	function get_product_attribute_values( $product ) {
		$formatted_attributes = array();

		if ( ! $product || ! $product->is_type( 'variable' ) ) {
			return $formatted_attributes;
		}

		$attributes = $product->get_attributes();

		foreach ( $attributes as $attr_slug => $attribute_obj ) {
			// Skip if not for variation.
			if ( ! $attribute_obj->get_variation() ) {
				continue;
			}

			$taxonomy      = $attribute_obj->get_name(); // e.g. 'pa_color'.
			$taxonomy_name = wc_attribute_label( $taxonomy ); // e.g. 'Color'.

			$terms = array();

			if ( taxonomy_exists( $taxonomy ) ) {
				$term_ids = $attribute_obj->get_options(); // IDs of terms.
				foreach ( $term_ids as $term_id ) {
					$term = get_term( $term_id );
					if ( ! is_wp_error( $term ) && $term ) {
						$terms[] = $term->name; // e.g. "Blue".
					}
				}
			} else {
				// For custom attributes (not taxonomy-based).
				$terms = $attribute_obj->get_options();
			}

			// Strip "pa_" from slug for output (optional).
			$clean_slug                          = str_replace( 'pa_', '', $attr_slug );
			$formatted_attributes[ $clean_slug ] = $terms;
		}

		return $formatted_attributes;
	}
}

$radio_class = Revenue_Template_Utils::get_element_class( $template_data, 'volumeDiscountRadio' );
ob_start();
?>
<div class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'wrapper' ) ); ?> <?php echo esc_attr( $display_style ); ?> <?php echo esc_attr( $device_manager_class ); ?> <?php echo esc_attr( $extra_class ); ?>">
	<?php Revenue_Template_Utils::render_wrapper_header( $campaign, $template_data ); ?>
	<div
		class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'productsWrapper' ) ); ?> <?php echo 'revx-' . esc_attr( $campaign_type ) . '-add-to-cart'; ?> revx-items-wrapper revx-scrollbar-common revx-flex-column"
		data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>"
		data-product-id="<?php echo esc_attr( $product_id ); ?>"
		data-product-type="<?php echo esc_attr( $product_type ); ?>"
		
	>
		<?php
		if ( is_array( $offers ) && ! empty( $offers ) ) {
			$is_variable   = $product->get_type() === 'variable';
			$regular_price = $product->get_regular_price();
			if ( $is_variable ) {
				$variations_main = $product->get_available_variations();
				$variations      = array();
				$attributes      = get_product_attribute_values( $product );
				$parent_id       = $product->get_id();
				$product_name    = $product->get_name();

				if ( $variations_main ) {
					foreach ( $variations_main as $index => $variation ) {
						$variations[ $index ]['item_id']       = $variation['variation_id'];
						$variations[ $index ]['item_name']     = $product_name;
						$variations[ $index ]['thumbnail']     = wp_get_attachment_url( isset( $variation['image_id'] ) ? $variation['image_id'] : 0 );
						$variations[ $index ]['regular_price'] = isset( $variation['display_regular_price'] ) ? $variation['display_regular_price'] : 0;
						$variations[ $index ]['sale_price']    = isset( $variation['display_price'] ) ? $variation['display_price'] : 0;
						$variations[ $index ]['is_in_stock']   = ! empty( $variation['is_in_stock'] ) ? true : false;
						$variations[ $index ]['parent_id']     = $parent_id;

						$combination_key = array();

						if ( ! empty( $variation['attributes'] ) ) {
							foreach ( $variation['attributes'] as $key => $value ) {
								// $clean_key                     = str_replace( 'attribute_pa_', '', strtolower( $key ) );
								// $clean_value                   = strtolower( $value );
								$combination_key[ $key ] = $value;
							}
						}

						$combination_str                          = json_encode( $combination_key );
						$variations[ $index ]['combination_keys'] = $combination_str;
					}
				}
				$variations['attributes'] = $attributes;
			}

			foreach ( $offers as $idx => $offer ) {
				$is_enable_tag = isset( $offer['isEnableTag'] ) && 'yes' === $offer['isEnableTag'];
				$is_selected   = ( count( $offers ) - 1 ) == $idx;
				$offer_type    = $offer['type'];
				$offer_value   = $offer['value'];
				$offer_qty     = $offer['quantity'];
				$save_data     = '';

				if ( 'percentage' === $offer_type ) {
					/* translators: %s: discount percentage value */
					$save_data = sprintf( __( '%s%%', 'revenue' ), $offer_value );
				} elseif ( 'amount' === $offer_type ) {
					$save_data = wc_price( intval( $offer_qty ) * floatval( $offer_value ) );
				}

				$price_data             = revenue()->calculate_campaign_offered_price( $offer_type, $offer_value, $regular_price, true );
				$offered_price          = $price_data['price'];
				$offer['regular_price'] = $regular_price;
				$offer['offered_price'] = $price_data['price'];
				if ( $is_variable && $variations ) {
					$offer['variations']    = $variations;
					$offer['regular_price'] = $variations[0]['regular_price'];
					$offer['offered_price'] = $variations[0]['sale_price'];
				}

				$inline_style = $is_selected
					? 'border-width: var(--revx-active-border-width); border-color: var(--revx-active-border-color); border-style: var(--revx-active-border-style);'
					: '';
				?>
					<div
						class="revx-volume-discount-item <?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'product' ) ); ?> <?php echo $is_enable_tag ? esc_attr( 'revx-tag-bg revx-tag-border' ) : ''; ?>"
						style="display: flex;"
					>
						<?php
						if ( $is_enable_tag ) {
							echo wp_kses_post( Revenue_Template_Utils::render_tag( $template_data ) );
						}
						?>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_radio( $template_data, $is_selected, 'volumeDiscountRadio', $is_enable_tag ? 'revx-tag-radio' : '', 'default', $offer_qty ) ); ?>
						<div class="revx-w-full">
							<div class="revx-d-flex revx-item-center revx-w-full">
								<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'quantityMessage', Revenue_Template_Utils::get_element_data( $template_data['quantityMessage'], 'text' ), $is_enable_tag ? 'revx-tag-text-color' : '' ) ); ?>
								<?php Revenue_Template_Utils::render_save_badge( $template_data, $save_data ?? '', $is_enable_tag ? 'revx-tag-bg revx-tag-border revx-tag-text-color' : '', 'display: block; flex-shrink: 0', 'saveBadgeWrapper' ); ?>
								<?php Revenue_Template_Utils::revenue_render_product_price( $offer, 'list', $template_data, false, true, true, 'revx-vqd-price' ); ?>
							</div>
							<div class="revx-volume-attributes revx-<?php echo esc_attr( $is_selected ? 'active' : 'inactive' ); ?>">
								<?php
								for ( $i = 0; $i < $offer_qty; $i++ ) {
									Revenue_Template_Utils::revenue_render_product_variation( $offer, $template_data, );
								}
								?>
							</div>
						</div>
					</div>
				<?php
			}
		}
		?>
	</div>
	<?php echo wp_kses_post( Revenue_Template_Utils::render_add_to_cart_button( $template_data, true, 'addToCartWrapper', $campaign_id, $campaign_type ) ); ?>
</div>

<style>
.revx-attr-container {
	padding: 1rem;
	border-radius: 10px;
	background: #f9f9f9;
	margin-top: 10px;
	border: 1px solid #eee;
}
.revx-attr-heading {
	font-weight: 500;
	margin-bottom: 0.75rem;
}
.revx-attr-row {
	display: flex;
	align-items: center;
	gap: 1rem;
	margin-bottom: 0.75rem;
}
.revx-attr-label {
	width: 2rem;
	font-weight: bold;
}
.revx-attr-select {
	padding: 0.4rem;
	border: 1px solid #ccc;
	border-radius: 6px;
	flex: 1;
}
</style>

<script>
	jQuery(function ($) {
		$(document).on('click', '.revx-volume-discount-item', function () {
			let $radio = $(this).find('.revx-radio-wrapper');
			let $attribute = $(this).find('.revx-volume-attributes');

			// deactivate every other option
			$('.revx-radio-wrapper, .revx-volume-attributes')
				.removeClass('revx-active')
				.addClass('revx-inactive');

			// activate the clicked one
			$radio.add($attribute)
				.addClass('revx-active')
				.removeClass('revx-inactive');
		});
	});
</script>

<?php
$output = ob_get_clean();
switch ( $display_style ) {
	case 'inpage':
		Revenue_Template_Utils::inpage_container( $campaign, $output );
		break;
	case 'popup':
		Revenue_Template_Utils::popup_container( $campaign, $output );
		break;
	case 'floating':
		Revenue_Template_Utils::floating_container( $campaign, $output );
		break;
}
