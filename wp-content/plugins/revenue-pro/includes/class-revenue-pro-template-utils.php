<?php
/**
 * Frequently Bought Together rendering.
 *
 * @package RevenuePro
 */

namespace Revenue;

defined( 'ABSPATH' ) || exit;

/**
 * Render helpers owned by Pro campaign types.
 *
 * Shared markup still comes from Revenue_Template_Utils in Free.
 */
class Revenue_Pro_Template_Utils {

	/**
	 * Render a single Frequently Bought Together (FBT) product item.
	 *
	 * This function handles rendering of individual product items within an FBT campaign.
	 * It resolves products (including variations), calculates pricing (regular, sale, offered),
	 * manages offer data tracking, and outputs the product card via `self::revenue_render_fbt__product_card()`.
	 *
	 * It also updates campaign-level pricing totals in Revenue_Template_Utils
	 * and optionally outputs campaign dividers and separators between items.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $offered_product_ids Array of offered product IDs (IDs of products included in the campaign).
	 * @param array  $offer               Optional. Offer data for the current iteration. Default empty array.
	 * @param array  $campaign            The campaign data array. Must include 'id' and 'campaign_type'.
	 * @param array  $template_data       Campaign template data used for rendering product cards.
	 * @param bool   $is_variation        Whether the current context is rendering a product variation.
	 * @param bool   $is_divider          Whether to show a divider between items.
	 * @param bool   $is_bundle           Whether the products are part of a bundle.
	 * @param string $view_mode           The display mode. Accepts 'grid' or 'list'.
	 * @param int    $offer_length        Total number of offers in the campaign.
	 * @param int    $offer_index         Index (1-based) of the current offer in iteration.
	 * @param int    &$render_index        Reference. Tracks the global render index across products.
	 * @param int    &$total_offer_products Reference. Tracks the total number of offered products rendered.
	 * @param array  &$offer_data          Reference. Aggregated offer data (prices, variations, etc.).
	 * @param bool   $is_grid_view        Whether the current layout is grid view.
	 * @param bool   $is_trigger          Optional. Whether the current product is a trigger product. Default false.
	 *
	 * @return void Outputs HTML directly.
	 *
	 * @see Revenue_Template_Utils::get_product_group()          Retrieves grouped products when rendering variations.
	 * @see Revenue_Template_Utils::get_product_list()           Retrieves a list of products when not variations.
	 * @see self::revenue_render_fbt__product_card() Handles rendering of the product card markup.
	 * @see Revenue_Template_Utils::render_campaign_divider()    Optionally outputs a divider between campaign sections.
	 */
	public static function render_fbt_product_item( $offered_product_ids, $offer, $campaign, $template_data, $is_variation, $is_divider, $is_bundle, $view_mode, $offer_length, $offer_index, &$render_index, &$total_offer_products, &$offer_data, $is_grid_view, $is_trigger = false ) {
		$render_products = $is_variation ? Revenue_Template_Utils::get_product_group( $offered_product_ids ) : Revenue_Template_Utils::get_product_list( $offered_product_ids );

		$render_product_length   = count( $render_products );
		$total_rendered_products = 0;
		$campaign_type           = $campaign['campaign_type'];
		$is_fbt                  = 'frequently_bought_together' === $campaign_type;
		$hide_card               = $is_bundle;

		$after_price  = 0;
		$before_price = 0;

		foreach ( $render_products as $product_index => $product_data ) {
			$is_variable_product = ! empty( $product_data['parent_id'] );

			if ( $is_variable_product ) {
				$product_id     = $product_data['parent_id'];
				$parent_product = wc_get_product( $product_id );
				if ( ! $parent_product ) {
					continue;
				}

				$in_stock_variations = array_filter( $product_data['variations'], fn( $v ) => $v['is_in_stock'] );
				if ( empty( $in_stock_variations ) ) {
					continue;
				}

				$default_variation = reset( $in_stock_variations );
				$offer_product_id  = $default_variation['item_id'];

				foreach ( $in_stock_variations as $variation_data ) {
					$var_id                                   = $variation_data['item_id'];
					$offer_data[ $var_id ]['regular_price'] ??= $variation_data['regular_price'];
					$offer_data[ $var_id ]['sale_price']    ??= $variation_data['sale_price'];
					$offer_data[ $var_id ]['offer'][]         = array(
						'qty'   => $offer['quantity'] ?? '',
						'type'  => $offer['type'] ?? '',
						'value' => $offer['value'] ?? '',
					);
				}
			} else {
				if ( ! $product_data['is_in_stock'] ) {
					continue;
				}
				$offer_product_id  = $product_data['item_id'];
				$product_id        = $offer_product_id;
				$default_variation = $product_data;
			}

			$offered_product = wc_get_product( $offer_product_id );
			if ( ! $offered_product || revenue()->is_hide_product( $campaign['id'], $offer_product_id ) ) {
				continue;
			}

			++$render_index;
			++$total_offer_products;

			$image         = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' ) ?: array( wc_placeholder_img_src() );
			$product_title = ( $is_bundle || $is_variable_product ) ? $product_data['item_name'] : $offered_product->get_title();
			$regular_price = $default_variation['regular_price'];
			$sale_price    = ( isset( $default_variation['sale_price'] ) && $default_variation['sale_price'] > 0 )
								? $default_variation['sale_price']
								: $regular_price;

			$type  = isset( $offer['type'] ) ? sanitize_text_field( $offer['type'] ) : '';
			$value = isset( $offer['value'] ) ? floatval( $offer['value'] ) : 0;

			$save_text = $template_data['saveBadgeWrapper']['text'] ?? '';

			// Extension Filter: Sale Price Addon.
			$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );
			// based on extension filter, use sale price or regular price for calculation.
			$offered_price = $filtered_price;

			$price_data = revenue()->calculate_campaign_offered_price(
				$type,
				$value,
				$filtered_price,
				true,
				1,
				$campaign_type,
				$save_text,
				$regular_price
			);

			// $save_tag = Revenue_Template_Utils::get_element_data( $template_data[], 'text' );
			if ( is_array( $price_data ) && isset( $price_data['price'] ) ) {
				$offered_price = floatval( $price_data['price'] );
			} else {
				$offered_price = floatval( $price_data );
			}

			$product_array = array(
				'id'            => $product_id,
				'title'         => $product_title,
				'image'         => $image,
				'regular_price' => $regular_price,
				'sale_price'    => $sale_price,
				'offered_price' => $offered_price,
				'quantity'      => $offer['quantity'] ?? '',
				'price_data'    => $price_data,
				'isEnableTag'   => $offer['isEnableTag'] ?? 'no',
			);

			if ( $is_variable_product ) {
				$product_array['variations'] = $product_data['variations'];
			}

			$offered_price = (float) $product_array['offered_price'];
			$sale_price    = (float) $product_array['sale_price'];
			$regular_price = (float) $product_array['regular_price'];

			if ( $offered_price > 0 ) {
				$after_price  += $offered_price;
				$before_price += $regular_price;
			} elseif ( $sale_price > 0 ) {
				$after_price  += $sale_price;
				$before_price += $sale_price;
			} else {
				$after_price  += $regular_price;
				$before_price += $regular_price;
			}

			self::revenue_render_fbt__product_card(
				$offer,
				$product_array,
				$view_mode,
				$campaign,
				$template_data,
				$hide_card,
				'addToCartWrapper',
				$product_index,
				$is_trigger
			);

			++$total_rendered_products;

			if ( ! ( $offer_length === $offer_index && $render_product_length === $total_rendered_products ) && $is_divider ) {
				Revenue_Template_Utils::render_campaign_divider( $is_grid_view, '', null, false, $is_bundle, $is_fbt );
			}
		}
		Revenue_Template_Utils::set_total_price( $after_price, $before_price );
	}


	/**
	 * Render the container for Frequently Bought Together (FBT) products.
	 *
	 * This method generates the HTML container for displaying FBT products
	 * based on campaign rules, template configuration, and product settings.
	 *
	 * @param array       $campaign         Campaign data containing configuration and rules.
	 * @param array       $template_data    Template data for rendering product layout and styles.
	 * @param array       $offers           Array of product offers to be displayed.
	 * @param bool        $is_variation     Whether the current product is a variation.
	 * @param bool        $is_divider       Whether to show a divider between items.
	 * @param bool        $is_bundle        Whether the products are part of a bundle.
	 * @param string      $view_mode        The current view mode (e.g., 'list', 'grid').
	 * @param bool        $is_grid_view     Whether products should be displayed in grid view.
	 * @param \WC_Product $current_product  The WooCommerce product object for the main product.
	 *
	 * @return void The rendered HTML for the FBT product container.
	 */
	public static function render_fbt_products_item_container(
		$campaign,
		$template_data,
		$offers,
		$is_variation,
		$is_divider,
		$is_bundle,
		$view_mode,
		$is_grid_view,
		$current_product
	) {
		$offered_product_ids = array();
		$class_name          = 'revx-slider-container revx-slider-x';

		$slider_controller_class = '';

		$prev_button = '
			<div class="revx-slider-controller prev ' . $slider_controller_class . '">
				<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" transform="rotate(180)" viewBox="0 0 24 24">
					<path stroke="currentColor" d="m9 18 6-6-6-6"></path>
				</svg>
			</div>
		';
		$next_button = '
		<div class="revx-slider-controller next ' . $slider_controller_class . '">
			<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24">
				<path stroke="currentColor" d="m9 18 6-6-6-6"></path>
			</svg>
		</div>';

		if ( $is_grid_view ) {
			echo '<div class="' . esc_attr( $class_name ) . '">';
			echo wp_kses( $prev_button, revenue()->get_allowed_tag() );
			echo '<div class="revx-slider-content revx-slider-style">';
		}

		$trigger_product_relation = 'or';

		$is_required_product      = isset( $campaign['fbt_is_trigger_product_required'] ) ? 'yes' === $campaign['fbt_is_trigger_product_required'] : false;
		$trigger_product_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';
		if ( empty( $trigger_product_relation ) ) {
			$trigger_product_relation = 'or';
		}
		$is_category   = ( 'category' === $campaign['campaign_trigger_type'] ) || ( 'all_products' === $campaign['campaign_trigger_type'] );
		$trigger_items = revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $current_product->get_id(), $is_category );

		$new_products = array();
		foreach ( $trigger_items as $trigger ) {
			$item_id = $trigger['item_id'] ?? null;
			if ( null !== $item_id ) {
				$pro = wc_get_product( $item_id );
				if ( $pro ) {
					$child = $pro->get_children();
					if ( empty( $child ) ) {
						$new_products[] = $item_id;
					} else {
						$new_products = array_merge( $new_products, $child );
					}
				}
			}
		}
		$new_array = array(
			'products'            => $new_products,
			'is_required_product' => $is_required_product,
			'source'              => 'trigger',
		);
		array_unshift( $offers, $new_array );

		if ( is_array( $offers ) ) {
			$offer_length = count( $offers );
			$offer_index  = 0;

			foreach ( $offers as $offer_index => $offer ) {
				$offered_product_ids = isset( $offer['products'] ) ? $offer['products'] : array();
				++$offer_index;
				$is_trigger = isset( $offer['source'] ) && 'trigger' === $offer['source'];

				self::render_fbt_product_item(
					$offered_product_ids,
					$offer,
					$campaign,
					$template_data,
					$is_variation,
					$is_divider,
					$is_bundle,
					$view_mode,
					$offer_length,
					$offer_index,
					$render_index,
					$total_offer_products,
					$offer_data,
					$is_grid_view,
					$is_trigger
				);
			}
		}

		if ( $is_grid_view ) {
			echo '</div>';
			echo wp_kses( $next_button, revenue()->get_allowed_tag() );
			echo '</div>';
		}
	}


	/**
	 * Render the main products container for a frequently bought together campaign.
	 *
	 * Outputs the HTML wrapper and product items for various campaign types,
	 * including handling for grid/list views, slider functionality, and special
	 *
	 * @since 1.0.0
	 *
	 * @param array             $campaign      The campaign data array. Must include 'id', 'campaign_type', and trigger information.
	 * @param array             $template_data The campaign template data used for rendering.
	 * @param string            $placement     The placement identifier for the campaign.
	 * @param bool              $is_variation  Whether the current product is a variation. Default false.
	 * @param bool              $is_x_product  Whether the container is rendering qualifying (X) products. Default false.
	 * @param \WC_Product|false $product       The current WooCommerce product object, if available. False if not applicable.
	 *
	 * @return void Outputs HTML directly.
	 */
	public static function render_fbt_products_container( $campaign, $template_data, $placement, $is_variation = false, $is_x_product = false, $product = false ) {
		$view_mode              = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
		$template_data          = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
		$offers                 = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
		$placement_settings     = revenue()->get_placement_settings( $campaign['id'] );
		$display_style          = isset( $placement_settings['display_style'] ) ? $placement_settings['display_style'] : 'inpage';
		$slider_columns         = wp_json_encode( Revenue_Template_Utils::get_slider_data( $template_data ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
		$products_wrapper_class = 'grid' === $view_mode ? 'revx-slider-wrapper' : '';
		$is_grid_view           = 'grid' === $view_mode;
		$is_fbt                 = 'frequently_bought_together' === $campaign['campaign_type'];
		$is_divider             = $is_fbt;

		$element_id = 'productsWrapper';

		if ( $is_grid_view ) {
			$element_id = 'sliderParent';
		}

		?>
			<div class="
					<?php
						echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, $element_id ) ) . ' ';
						echo esc_attr( $view_mode );
						echo ' ' . esc_attr( $products_wrapper_class );
						echo ' ' . ( $is_grid_view ? 'revx-slider-wrapper' : 'revx-flex-column' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings.
						echo ' ' . ( $is_divider ? 'revx-revx-product-body-scroll' : '' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings.
						echo ' revx-items-wrapper revx-scrollbar-common';

					?>
				"
				data-slider-columns='<?php echo esc_attr( $slider_columns ); ?>'
				data-layout='<?php echo esc_attr( $display_style ); ?>'
			>
			<?php
			if ( $is_grid_view ) {
				echo '<div class="revx-slider-parent">';
			}

			self::render_fbt_products_item_container(
				$campaign,
				$template_data,
				$offers,
				$is_variation,
				$is_divider,
				false,
				$view_mode,
				$is_grid_view,
				$product
			);

			if ( $is_grid_view ) {
				echo '</div>';
			}
			?>
			</div>
			<?php
	}


	/**
	 * Render a Frequently Bought Together (FBT) product card.
	 *
	 * This method generates the HTML markup for displaying a single FBT product card.
	 * It supports different layouts, campaign-specific data, and customization options
	 * such as hiding the add-to-cart button or handling X-product campaigns.
	 *
	 * @param array      $offer          The offer data containing pricing and discount information.
	 * @param array      $pd             Product data array (usually contains product ID, title, price, etc.).
	 * @param string     $layout         Layout type for rendering the card. Defaults to 'grid'.
	 * @param array|null $campaign       The campaign configuration data. Required for rendering.
	 * @param array|null $template_data  Template-specific data used for styling and rendering.
	 * @param bool       $hide_cart      Whether to hide the add-to-cart button. Default false.
	 * @param string     $add_cart_id    HTML wrapper ID for the add-to-cart button. Default 'addToCartWrapper'.
	 * @param int        $product_index  The index/position of the product in the FBT list.
	 * @param bool       $is_trigger     Optional. Whether this product is a trigger product. Default false.
	 *
	 * @return string|null Rendered HTML for the product card, or null if product/campaign is invalid.
	 */
	public static function revenue_render_fbt__product_card(
		$offer = array(),
		$pd = array(),
		$layout = 'grid',
		$campaign = null,
		$template_data = null,
		$hide_cart = false,
		$add_cart_id = 'addToCartWrapper',
		$product_index = '',
		$is_trigger = false
	) {
		if ( ! $pd || ! $campaign ) {
			return;
		}

		$is_fbt           = 'frequently_bought_together' === $campaign['campaign_type'];
		$is_enable_tag    = isset( $pd['isEnableTag'] ) && 'yes' === $pd['isEnableTag'];
		$is_list_layout   = 'list' === $layout;
		$is_go_to_product = 'go_to_product' === $campaign['offered_product_click_action'];

		$template_two = $is_fbt;

		$class       = Revenue_Template_Utils::get_element_class( $template_data, 'productLayout' );
		$extra_class = 'revx-d-flex';
		if ( ! $is_list_layout ) {
			$extra_class = 'revx-d-flex revx-flex-column revx-justify-between revx-slider-title-align revx-slider-product';
		}
		$title_class = '';
		if ( $is_enable_tag ) {
			$title_class .= 'revx-tag-text-color';
		}
		if ( ! $is_list_layout ) {
			$title_class .= 'revx-ellipsis-2 revx-width-11rem';
		}

		$product_title = $pd['title'] ?? '';
		$image_src     = $pd['image'][0] ?? '';

		// Render Image.
		$image_html = Revenue_Template_Utils::render_image(
			array(
				'src'   => $image_src,
				'alt'   => $product_title,
				'class' => Revenue_Template_Utils::get_element_class( $template_data, 'productImage' ),
			)
		);

		$product_id   = isset( $pd['id'] ) ? $pd['id'] : '';
		$product_type = 'none';
		if ( $product_id ) {
			$product_ = wc_get_product( $product_id );
			if ( $product_ ) {
				$product_type = $product_->get_type();
			}
		}

		// -------------------------------------------------------//
		// Prepare data attributes for price and variations.
		$data_price_attributes = Revenue_Template_Utils::get_data_price_attributes( $product_id, $offer );
		// Do not remove this.
		// Asif
		// -------------------------------------------------------//.

		$product_quantity = ! empty( $pd['quantity'] ) ? $pd['quantity'] : 1;
		$campaign_id      = $campaign['id'];
		$campaign_type    = $campaign['campaign_type'];
		$is_only_regular  = floatval( $pd['regular_price'] ) === floatval( $pd['offered_price'] );
		// corner case of mis match tax settings.
		$display_offered_price = 'incl' === get_option( 'woocommerce_tax_display_shop', 'incl' )
			? wc_get_price_including_tax( $product_, array( 'price' => $pd['offered_price'] ) )
			: wc_get_price_excluding_tax( $product_, array( 'price' => $pd['offered_price'] ) );
		?>
			<div data-product-id="<?php echo esc_attr( $product_id ); ?>"
				data-variation-id="0"
				data-is-trigger="<?php echo esc_attr( $is_trigger ? 'yes' : 'no' ); ?>"
				data-product-index="<?php echo esc_attr( $product_index ); ?>"
				data-product-offered-price="<?php echo esc_attr( $display_offered_price ); ?>"
				campaign_id="<?php echo esc_attr( $campaign_id ); ?>"
				campaign_type="<?php echo esc_attr( $campaign_type ); ?>"
				product_type="<?php echo esc_attr( $product_type ); ?>"
				<?php echo $data_price_attributes; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each attribute value is esc_attr()'d in get_data_price_attributes(). ?>
				data-product-qty="<?php echo esc_attr( $product_quantity ); ?>"
				class="<?php echo 'revx-' . esc_attr( $campaign_type ) . '-add-to-cart'; ?> <?php echo esc_attr( $class ); ?> revx-relative revx-product-layout <?php echo esc_attr( $extra_class ); ?> <?php echo $is_enable_tag ? 'revx-tag-bg revx-tag-border' : ''; ?> revx-campaign-product-card"
			>
				<?php
				if ( $is_enable_tag ) {
					echo wp_kses_post( Revenue_Template_Utils::render_tag( $template_data ) );
				}
				?>

				<?php
				if ( 'grid' === $layout ) {
					echo '<div>';
				}
				?>
				<div class="revx-product-image">
					<?php
					if ( $is_fbt ) {
						$is_required_product = isset( $offer['is_required_product'] ) ? $offer['is_required_product'] : false;
						echo wp_kses( Revenue_Template_Utils::render_checkbox( $template_data, $is_list_layout, $is_enable_tag, $is_required_product, ! $is_list_layout ), revenue()->get_allowed_tag() );
					}
					if ( $is_go_to_product ) {
						echo '<a target="_blank" href="' . esc_url( get_permalink( $product_id ) ) . '">';
						echo wp_kses_post( $image_html );
						echo '</a>';
					} else {
						echo wp_kses_post( $image_html );
					}
					?>
				</div>
				<div class="revx-w-full">
					<?php
					if ( $is_go_to_product ) {
						echo '<a href="' . esc_url( get_permalink( $product_id ) ) . '" target="_blank">';
						echo wp_kses_post(
							Revenue_Template_Utils::render_rich_text(
								$template_data,
								'productTitle',
								$product_title,
								$title_class
							)
						);
						echo '</a>';
					} else {
						echo wp_kses_post(
							Revenue_Template_Utils::render_rich_text(
								$template_data,
								'productTitle',
								$product_title,
								$title_class
							)
						);
					}
					?>
					<?php
						Revenue_Template_Utils::revenue_render_product_price(
							$pd,
							$layout,
							$template_data,
							false,
							false,
							false,
							'',
							$is_only_regular
						);
					if ( $is_list_layout ) {
						?>
								<div class="revx-d-flex revx-flex-wrap revx-w-full revx-justify-between">
							<?php
								Revenue_Template_Utils::revenue_render_product_variation(
									$pd,
									$template_data,
									$layout
								);
							if ( ! $hide_cart && ! $template_two ) {
								Revenue_Template_Utils::revenue_render_product_cart(
									$pd,
									$campaign,
									$template_data,
									$add_cart_id,
								);
							}
							?>
								</div>
							<?php
					} else {
						Revenue_Template_Utils::revenue_render_product_variation(
							$pd,
							$template_data,
							$layout
						);
						if ( ! $hide_cart && ! $template_two ) {
							Revenue_Template_Utils::revenue_render_product_cart(
								$pd,
								$campaign,
								$template_data,
								$add_cart_id,
							);
						}
					}
					?>
				</div>
				<?php
				if ( 'grid' === $layout ) {
					echo '</div>';
				}
				?>
				<?php
				if ( ! $hide_cart && ( 'grid' === $layout || $template_two ) ) {
					$class_layout  = 'revx-layout-secondary';
					$class_layout .= '';
					if ( $template_two ) {
						echo '<div class="' . esc_attr( $class_layout ) . '">';
					}
					Revenue_Template_Utils::revenue_render_product_cart(
						$pd,
						$campaign,
						$template_data,
						$add_cart_id,
						false,
						false,
					);
					if ( $template_two ) {
						echo '</div>';
					}
				}
				?>
			</div>
		<?php
	}

	/**
	 * Return the SVG markup for a spending-goal reward type.
	 *
	 * Supported reward types:
	 *  - 'free_shipping' : free shipping icon SVG
	 *  - 'gift'          : gift icon SVG
	 *  - 'discount'      : discount icon SVG
	 *  - 'check'         : check icon SVG
	 *  - 'add'           : add icon SVG
	 *  - 'minus'         : minus icon SVG
	 *
	 * @param string $reward_type Reward type identifier.
	 * @return string SVG markup for the given reward type or empty string when unsupported.
	 */
	public static function get_spg_icon( $reward_type ) {
		switch ( $reward_type ) {
			case 'free_shipping':
				return '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 20 20"><circle cx="13.332" cy="14.167" r="1.667" stroke="currentColor" stroke-width="1.25"/><circle cx="6.668" cy="14.167" r="1.667" stroke="currentColor" stroke-width="1.25"/><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.25" d="M2.497 9.997h6.667V4.164m0 0v1.667H2.497v6.666c0 .92.746 1.667 1.667 1.667h.833m4.167-10h4.166l3.854 3.083a.83.83 0 0 1 .313.65v1.267m-2.5-3.333H13.33v3.333h4.167m0 0v3.333c0 .92-.746 1.667-1.667 1.667h-.833m-3.333 0H8.33"/></svg>';

			case 'gift':
				return '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-linecap="round" stroke-width="1.25" d="M2.083 9.168c0-.69.56-1.25 1.25-1.25h13.333c.69 0 1.25.56 1.25 1.25v0c0 .69-.56 1.25-1.25 1.25H3.333c-.69 0-1.25-.56-1.25-1.25z"/><path stroke="currentColor" stroke-linecap="round" stroke-width="1.25" d="M3.751 10.414v6c0 .233 0 .35.045.44.04.078.104.141.182.181.09.046.206.046.44.046h11.166c.234 0 .35 0 .44-.046a.4.4 0 0 0 .182-.182c.045-.089.045-.206.045-.439v-6M11.248 5.83v2.084H7.915a2.5 2.5 0 0 1 0-5h.417a2.917 2.917 0 0 1 2.916 2.917Z"/><path stroke="currentColor" stroke-linecap="round" stroke-width="1.25" d="M11.248 5.833v2.084h2.084a2.083 2.083 0 1 0-2.084-2.084Zm-.001 4.581v6.667"/></svg>';

			case 'discount':
				return '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 20 20"><path stroke="currentColor" stroke-width="1.25" d="M8.265 2.649c.786-1.313 2.688-1.313 3.475 0a2.03 2.03 0 0 0 2.23.923c1.484-.372 2.83.973 2.457 2.458a2.03 2.03 0 0 0 .924 2.23c1.313.786 1.313 2.688 0 3.475a2.03 2.03 0 0 0-.924 2.23c.372 1.485-.973 2.83-2.457 2.457a2.03 2.03 0 0 0-2.23.924c-.787 1.313-2.689 1.313-3.475 0a2.03 2.03 0 0 0-2.23-.924c-1.485.372-2.83-.973-2.458-2.457a2.03 2.03 0 0 0-.924-2.23c-1.312-.787-1.312-2.689 0-3.475a2.03 2.03 0 0 0 .924-2.23c-.372-1.485.973-2.83 2.458-2.458a2.03 2.03 0 0 0 2.23-.923Z"/><path stroke="currentColor" stroke-linecap="round" stroke-width="1.25" d="m6.665 13.33 6.667-6.666"/><path stroke="currentColor" stroke-width="1.25" d="M8.799 6.198A1.25 1.25 0 1 1 7.03 7.966 1.25 1.25 0 0 1 8.8 6.198Zm4.168 5.832a1.25 1.25 0 1 1-1.768 1.768 1.25 1.25 0 0 1 1.768-1.768Z"/></svg>';

			case 'check':
				return '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" d="M20 6 9 17l-5-5"/></svg>';

			case 'add':
				return '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 16 16"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M8 3.334v9.333M3.333 8h9.333"/></svg>';

			case 'minus':
				return '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 16 16"><path stroke="currentColor" d="M3.333 8h9.333"/></svg>';

			default:
				return '';
		}
	}

	/**
	 * Render a single spending-goal step and optional preview content.
	 *
	 * This method outputs the HTML for a step marker used in the Spending Goal
	 * progress bar. It displays an icon for the reward type and, when in preview
	 * mode, renders a small gift preview panel for 'gift' type rewards.
	 *
	 * @param int       $index         Zero-based index of the step.
	 * @param array     $step          Step configuration array (may contain reward_name, reward_type, gift_products, etc.).
	 * @param float|int $progress      Current progress percentage (0-100) used to position the step marker.
	 * @param int       $total_steps   Total number of steps in the progress bar.
	 * @param array     $template_data Template builder data used for rendering labels and classes.
	 * @param float|int $required_goal Accumulated required goal amount up to this step.
	 * @param bool      $is_show_icon  Whether to show the reward type icon instead of the default check icon.
	 * @return void                      Outputs HTML directly.
	 */
	public static function render_spg_step( $index = 0, $step = array(), $progress = 0, $total_steps = 0, $template_data = array(), $required_goal = 0, $is_show_icon = false ) {

		$icon_padding    = '8px';
		$is_rtl          = $template_data['is_rtl'];
		$step_percentage = ( ( $index + 1 ) / $total_steps ) * 100;
		$accomplished    = $progress >= $step_percentage;

		$is_last         = ( $index + 1 ) === $total_steps;
		$label           = isset( $step['reward_name'] ) ? $step['reward_name'] : '';
		$type            = isset( $step['reward_type'] ) ? $step['reward_type'] : '';
		$is_preview      = ( ( $step['gift_product_preview'] ?? '' ) === 'preview' );
		$success_message = isset( $step['after_message'] ) ? $step['after_message'] : '';

		$gift_products = array();
		$gift_quantity = '0';
		$spending_goal = isset( $step['spending_goal'] ) ? $step['spending_goal'] : '0';
		$gift_heading  = '';

		$selected_gift_products = array();
		$selected_product_ids   = array();

		if ( $is_preview ) {
			$gift_products          = ! empty( $step['gift_products'] ) ? $step['gift_products'] : array();
			$gift_quantity          = ! empty( $step['gift_quantity'] ) ? $step['gift_quantity'] : '';
			$item_text              = $gift_quantity > 1 ? 'items' : 'item';
			$selected_gift_products = array_slice( $gift_products, 1 );  // currently fixed selected product for testing. $selected_gift_products will be selected gift products array.
			$selected_product_ids   = array_column( $selected_gift_products, 'item_id' );

			if ( strtolower( $gift_quantity ) === 'all' ) {
				$gift_heading = 'Spend ' . $spending_goal . ' more to claim your gift!';
			} elseif ( '0' != $spending_goal ) {
				$gift_heading = 'Spend ' . $spending_goal . ' more to get any ' . $gift_quantity . ' ' . $item_text . ' as a gift!';
			}
		}

		?>
			<div 
				data-success-message="<?php echo esc_attr( $success_message ); ?>" 
				class="revx-progress-step <?php echo ! $is_last ? 'middle' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?> <?php echo $accomplished ? 'revx-progress-active' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>" 
				style="
					<?php if ( $is_rtl ) : ?>
						right: calc( <?php echo esc_attr( $step_percentage ); ?>% - (calc( (var(--revx-progress-height, 8px) * 3 + (<?php echo esc_attr( $icon_padding ); ?> * 2)) * 2 )) );
					<?php else : ?>
						left: <?php echo esc_attr( $step_percentage ); ?>%;
					<?php endif; ?>
					position: absolute;
					z-index: 999;
					<?php // wp_kses()/safecss_filter_attr() allow-lists "transform" but not the standalone "translate" property, which it strips from the style attribute. ?>
					transform: translate(-100%, <?php echo empty( $label ) ? '-4%' : '0%';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>)
				"
			>
				<div 
					class="revx-progress-step-icon-container <?php echo $is_last ? 'last' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?> <?php echo $accomplished ? 'completed' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>"
					style="
						background-color: <?php echo $accomplished ? 'var(--revx-active-color, #F6F8FA)' : 'var(--revx-inactive-color, #F6F8FA)';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>;
						width: calc( var(--revx-progress-height, 8px) * 3 );
						height: calc( var(--revx-progress-height, 8px) * 3 );
						border-radius: 50%;
						padding: <?php echo esc_attr( $icon_padding ); ?>;
						box-sizing: content-box;
					"
				>
					<div
						style="
							line-height: 0;
							color: <?php echo $accomplished ? 'var(--revx-inactive-color, #F6F8FA)' : 'var(--revx-icon-color, #1827DD)';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>;
							font-size: calc( var(--revx-progress-height, 8px) * 3 );
						"
					>
					<?php echo wp_kses( self::get_spg_icon( $is_show_icon ? $type : 'check' ), revenue()->get_allowed_tag() ); ?>
					</div>
				<?php
				if ( $is_preview && $gift_products && 'gift' === $type ) {

					?>
					
						<div class="revx-gift-container revx-d-none">
							<div class="revx-spending-gift">
								<div class="revx-spending-gift-heading">
									<?php echo esc_html( $gift_heading ); ?>
								</div>
								<div class="revx-spending-gift-container revx-scrollbar-common">

									<?php
									foreach ( $gift_products as $idx => $product ) {
										$is_on_cart = self::is_gift_on_cart( $product['item_id'], $required_goal );

										?>
										
										<div class="revx-product-item revx-spending-gift-item">
											<div class="revx-spending-gift-image">
													<img
														src="<?php echo esc_url( $product['thumbnail'] ); ?>"
														alt="<?php echo esc_attr( $product['item_name'] ); ?>"
													/>
												</div>
												<div class="revx-d-flex revx-item-center revx-justify-between revx-w-full">
													<div class="revx-spending-gift-content">
														<div
															class="revx-spending-gift-title revx-ellipsis-1"
															title="<?php echo esc_attr( $product['item_name'] ); ?>"
														>
															<?php echo esc_html( $product['item_name'] ); ?>
														</div>
														<div class="revx-spending-gift-price">
															<del><?php echo esc_html( $product['regular_price'] ); ?></del>
															<div>Free</div>
														</div>
													</div>
													<div class="revx-spending-gift-action <?php echo in_array( $product['item_id'], $selected_product_ids ) ? 'revx-active' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>">
														<!-- this part will render from jQuery -->
														<div class="revx-icon revx-gift-item-checked <?php echo esc_attr( $is_on_cart ? 'on-cart' : '' ); ?>">
															<?php
															echo wp_kses(
																self::get_spg_icon( 'check' ),
																revenue()->get_allowed_tag()
															);
															?>
														</div>
														<div class="revx-icon revx-gift-item-remove <?php echo esc_attr( $is_on_cart ? 'on-cart' : '' ); ?>"
														data-product-id="<?php echo esc_attr( $product['item_id'] ); ?>">
															<?php echo wp_kses( self::get_spg_icon( 'minus' ), revenue()->get_allowed_tag() ); ?>
														</div>
														<div class="revx-icon revx-gift-item-add <?php echo esc_attr( $is_on_cart ? 'on-cart' : '' ); ?>" 
														data-product-id="<?php echo esc_attr( $product['item_id'] ); ?>"
														>
															<?php echo wp_kses( self::get_spg_icon( 'add' ), revenue()->get_allowed_tag() ); ?>
														</div>
														<!-- jQuery End -->
													</div>
											</div>
										</div>
									<?php } ?>
								</div>
							</div>
						</div>
					<?php } ?>
				</div>
			<?php
			if ( ! empty( $label ) ) {
				echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'spendingGoalLabel', $label, 'revx-absolute revx-bellow-8 revx-spg-goal-label', 'font-size: var(--revx-step-label-size, 14px); font-weight: 500; color: var(--revx-step-label-color); width: max-content; max-width: 9rem;' ) );
			}
			?>
			</div>
			<?php
	}

	/**
	 * Check if a specific item qualifies as a gift in the cart based on the goal.
	 *
	 * @param int   $item_id The product or variation ID to check.
	 * @param float $goal The spending goal required for the gift.
	 * @return bool True if the item is a qualifying gift in the cart, false otherwise.
	 */
	public static function is_gift_on_cart( $item_id, $goal ) {
		$cart_total = self::get_eligible_cart_total();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			if ( $product_id == $item_id && self::is_gift( $cart_item, $cart_total ) && $cart_total >= $goal ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Calculate and return the eligible cart total, excluding gift products.
	 *
	 * @return float
	 */
	private static function get_eligible_cart_total() {
		$cart_total     = 0;
		$total_line_tax = 0;
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return $cart_total;
		}

		// Remove gift products from calculation.
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! self::is_gift( $cart_item, $cart_total ) ) {
				if ( isset( $cart_item['line_total'] ) ) {
					$cart_total     += $cart_item['line_total'];
					$total_line_tax += isset( $cart_item['line_tax'] ) ? (float) $cart_item['line_tax'] : 0;
				}
			}
		}
		// to handle tax.
		if ( WC()->cart->display_prices_including_tax() ) {
			$cart_total += $total_line_tax;
		}
		// wc_format_decimal to round up few points after decimal and properly format the price.
		return (float) wc_format_decimal( $cart_total, 2 );
	}


	/**
	 * Determine if the given cart item qualifies as a gift based on cart total.
	 *
	 * @param array $cart_item The cart item to check.
	 * @param float $cart_total The current cart total.
	 * @return bool True if the item is a gift, false otherwise.
	 */
	private static function is_gift( $cart_item, $cart_total ) {
		if ( isset( $cart_item['revx_is_reward_gift'], $cart_item['revx_cart_required'] ) && $cart_item['revx_is_reward_gift'] && $cart_item['revx_cart_required'] <= $cart_total ) {
			return true;
		}
		return false;
	}

	/**
	 * Render mix & match (or volume-based) product items for a campaign offer.
	 *
	 * Builds product cards for simple and variable products, calculates offered
	 * pricing, aggregates before/after prices, and updates shared render state
	 * counters and offer metadata.
	 *
	 * Side effects:
	 * - Mutates `$render_index`, `$total_offer_products`, and `$offer_data` by reference.
	 * - Updates static price totals: `self::$after_price` and `self::$before_price`.
	 * - Outputs product card markup via `revenue_render_mix_match_product_card()`.
	 *
	 * @param int[]  $offered_product_ids     List of product or variation IDs included in the offer.
	 * @param array  $offer                   Offer configuration (quantity, type, value, isEnableTag).
	 * @param array  $campaign                Campaign data (must include `id` and `campaign_type`).
	 * @param array  $template_data            Template-related data used during rendering.
	 * @param bool   $is_variation             Whether products should be treated as variation groups.
	 * @param string $view_mode                Rendering mode (e.g., grid, list, modal).
	 * @param int    &$render_index            Running index of rendered items (passed by reference).
	 * @param int    &$total_offer_products    Total number of offer products rendered (by reference).
	 * @param array  &$offer_data              Offer metadata indexed by product/variation ID (by reference).
	 *
	 * @return void
	 */
	public static function render_mix_match_product_item(
		$offered_product_ids,
		$offer,
		$campaign,
		$template_data,
		$is_variation,
		$view_mode,
		&$render_index,
		&$total_offer_products,
		&$offer_data
	) {
		$render_products         = $is_variation ? Revenue_Template_Utils::get_product_group( $offered_product_ids ) : Revenue_Template_Utils::get_product_list( $offered_product_ids );
		$total_rendered_products = 0;
		$campaign_type           = $campaign['campaign_type'];
		$is_mix_match            = 'mix_match' === $campaign_type;

		$after_price  = 0;
		$before_price = 0;

		// Addon: optionally split single-attribute variable products into separate cards
		// (one per in-stock variation) instead of one grouped card with a dropdown.
		$split_variations = apply_filters( 'revenue_mix_match_split_variations', false );

		foreach ( $render_products as $product_index => $product_data ) {
			$is_variable_product = ! empty( $product_data['parent_id'] );

			if ( $is_variable_product && $split_variations && $is_mix_match ) {
				$parent_product = wc_get_product( $product_data['parent_id'] );
				// Only split when the variable product uses exactly one attribute.
				if ( $parent_product && 1 === count( $parent_product->get_variation_attributes() ) ) {
					$in_stock_variations = array_filter(
						$product_data['variations'],
						fn( $v ) => $v['is_in_stock']
					);

					$tax_display = get_option( 'woocommerce_tax_display_shop', 'incl' );

					foreach ( $in_stock_variations as $variation_data ) {
						$var_id  = $variation_data['item_id'];
						$var_obj = wc_get_product( $var_id );
						if ( ! $var_obj || revenue()->is_hide_product( $campaign['id'], $var_id ) ) {
							continue;
						}

						$var_regular = $variation_data['regular_price'];
						// Match the dropdown paths: skip variations without a price.
						if ( '' === $var_regular || null === $var_regular ) {
							continue;
						}

						$var_sale = ( isset( $variation_data['sale_price'] ) && $variation_data['sale_price'] > 0 )
							? $variation_data['sale_price']
							: $var_regular;

						// NOTE on tax: keep card-display prices RAW — revenue_render_product_price()
						// applies the woocommerce_tax_display_shop conversion itself, exactly like the
						// non-split grouped card path. Taxed copies go ONLY into the split payload below,
						// because the JS cookie/footer path does no conversion and must sum consistently
						// with the taxed offer_data built in template1.php.

						// Extension Filter: Sale Price Addon.
						$var_filtered   = apply_filters( 'revenue_base_price_for_discount_filter', $var_regular, $var_sale );
						$var_price_data = revenue()->calculate_campaign_offered_price( '', 0, $var_filtered, true );
						$var_offered    = ( is_array( $var_price_data ) && isset( $var_price_data['price'] ) )
							? floatval( $var_price_data['price'] )
							: floatval( $var_price_data );

						$var_image = wp_get_attachment_image_src( get_post_thumbnail_id( $var_id ), 'single-post-thumbnail' )
							?: ( wp_get_attachment_image_src( get_post_thumbnail_id( $product_data['parent_id'] ), 'single-post-thumbnail' )
								?: array( wc_placeholder_img_src() ) );
						$var_thumb = $var_image[0] ?? wc_placeholder_img_src();

						$var_regular_display = apply_filters( 'revenue_base_price_for_mix_match', $var_regular, $var_sale );
						$var_sale_display    = apply_filters( 'revenue_sale_price_for_mix_match', '', $var_sale );

						// Taxed copies for the payload only (cookie/footer JS path), mirroring
						// how template1.php taxes offer_data for simple products.
						if ( 'incl' === $tax_display ) {
							$payload_regular = wc_get_price_including_tax( $var_obj, array( 'price' => $var_regular_display ) );
							$payload_sale    = '' !== $var_sale_display
								? wc_get_price_including_tax( $var_obj, array( 'price' => $var_sale_display ) )
								: '';
						} else {
							$payload_regular = wc_get_price_excluding_tax( $var_obj, array( 'price' => $var_regular_display ) );
							$payload_sale    = '' !== $var_sale_display
								? wc_get_price_excluding_tax( $var_obj, array( 'price' => $var_sale_display ) )
								: '';
						}

						// Everything the frontend needs to add this fixed variation without a dropdown.
						$split_payload = array(
							'parent_id'     => (int) $product_data['parent_id'],
							'variation_id'  => (int) $var_id,
							'attributes'    => $var_obj->get_variation_attributes(), // keys like attribute_pa_size.
							'item_name'     => $var_obj->get_name(),
							'regular_price' => $payload_regular,
							'sale_price'    => $payload_sale,
							'thumbnail'     => $var_thumb,
						);

						$product_array = array(
							'id'              => $var_id,
							'title'           => $var_obj->get_name(),
							'image'           => $var_image,
							'regular_price'   => $var_regular_display,
							'sale_price'      => $var_sale_display,
							'offered_price'   => $var_offered,
							'quantity'        => $offer['quantity'] ?? '',
							'price_data'      => $var_price_data,
							'isEnableTag'     => 'no',
							'split_variation' => $split_payload,
						);

						++$render_index;
						++$total_offer_products;
						self::revenue_render_mix_match_product_card(
							$product_array,
							$view_mode,
							$campaign,
							$template_data,
							false,
							'addProductWrapper',
							$var_id,
						);
						++$total_rendered_products;
					}

					continue; // Skip the default grouped card for this variable product.
				}
			}

			if ( $is_variable_product ) {
				$product_id     = $product_data['parent_id'];
				$parent_product = wc_get_product( $product_id );
				if ( ! $parent_product ) {
					continue;
				}

				$in_stock_variations = array_filter( $product_data['variations'], fn( $v ) => $v['is_in_stock'] );
				if ( empty( $in_stock_variations ) ) {
					continue;
				}

				$default_variation = reset( $in_stock_variations );
				$offer_product_id  = $default_variation['item_id'];

				foreach ( $in_stock_variations as $variation_data ) {
					$var_id                                   = $variation_data['item_id'];
					$offer_data[ $var_id ]['regular_price'] ??= $variation_data['regular_price'];
					$offer_data[ $var_id ]['sale_price']    ??= $variation_data['sale_price'];
					$offer_data[ $var_id ]['offer'][]         = array(
						'qty'   => $offer['quantity'] ?? '',
						'type'  => $offer['type'] ?? '',
						'value' => $offer['value'] ?? '',
					);
				}
			} else {
				if ( ! $product_data['is_in_stock'] ) {
					continue;
				}
				$offer_product_id  = $product_data['item_id'];
				$product_id        = $offer_product_id;
				$default_variation = $product_data;
			}

			$offered_product = wc_get_product( $offer_product_id );
			if ( ! $offered_product || revenue()->is_hide_product( $campaign['id'], $offer_product_id ) ) {
				continue;
			}

			++$render_index;
			++$total_offer_products;

			$image         = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' ) ?: array( wc_placeholder_img_src() );
			$product_title = ( $is_variable_product ) ? $product_data['item_name'] : $offered_product->get_title();
			$regular_price = $default_variation['regular_price'];
			$sale_price    = ( isset( $default_variation['sale_price'] ) && $default_variation['sale_price'] > 0 )
								? $default_variation['sale_price']
								: $regular_price;

			// Extension Filter: Sale Price Addon.
			$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );

			$type  = isset( $offer['type'] ) ? sanitize_text_field( $offer['type'] ) : '';
			$value = isset( $offer['value'] ) ? floatval( $offer['value'] ) : 0;
			// based on extension filter use sale price or regular price for calculation.
			$price_data = revenue()->calculate_campaign_offered_price(
				$type,
				$value,
				$filtered_price,
				true
			);
			if ( is_array( $price_data ) && isset( $price_data['price'] ) ) {
				$offered_price = floatval( $price_data['price'] );
			} else {
				$offered_price = floatval( $price_data );
			}

			// Extension Filter: Sale Price Addon.
			$filtered_mix_match_regular_price = apply_filters( 'revenue_base_price_for_mix_match', $regular_price, $sale_price );

			// Extension Filter: Sale Price Addon.
			// for non extension state sale price will be empty.
			$filtered_mix_match_sale_price = apply_filters( 'revenue_sale_price_for_mix_match', '', $sale_price );

			// directly modified regular price with sale price when extension active
			// for easy visual price display.
			$product_array = array(
				'id'            => $product_id,
				'title'         => $product_title,
				'image'         => $image,
				'regular_price' => $filtered_mix_match_regular_price,
				'sale_price'    => $is_mix_match ? $filtered_mix_match_sale_price : $sale_price,
				'offered_price' => $offered_price,
				'quantity'      => $offer['quantity'] ?? '',
				'price_data'    => $price_data,
				'isEnableTag'   => $offer['isEnableTag'] ?? 'no',
			);

			if ( $is_variable_product ) {
				$product_array['variations'] = $product_data['variations'];
			}

			$offered_price = (float) $product_array['offered_price'];
			$sale_price    = (float) $product_array['sale_price'];
			$regular_price = (float) $product_array['regular_price'];

			if ( $offered_price > 0 ) {
				$after_price  += $offered_price;
				$before_price += $regular_price;
			} elseif ( $sale_price > 0 ) {
				$after_price  += $sale_price;
				$before_price += $sale_price;
			} else {
				$after_price  += $regular_price;
				$before_price += $regular_price;
			}

			self::revenue_render_mix_match_product_card(
				$product_array,
				$view_mode,
				$campaign,
				$template_data,
				false,
				$is_mix_match ? 'addProductWrapper' : 'addToCartWrapper',
				$product_index,
			);
			++$total_rendered_products;
		}
		Revenue_Template_Utils::set_total_price( $after_price, $before_price );
	}

	/**
	 * Render the container for Mix Match products.
	 *
	 * This method generates the HTML container for displaying FBT products
	 * based on campaign rules, template configuration, and product settings.
	 *
	 * @param array  $campaign         Campaign data containing configuration and rules.
	 * @param array  $template_data    Template data for rendering product layout and styles.
	 * @param bool   $is_variation     Whether the campaign supports mix-and-match products.
	 * @param bool   $is_mix_match     Whether the campaign supports mix-and-match products.
	 * @param string $view_mode        The current view mode (e.g., 'list', 'grid').
	 * @param bool   $is_grid_view     Whether products should be displayed in grid view.
	 *
	 * @return void The rendered HTML for the Mix and Match product container.
	 */
	public static function render_mix_match_products_item_container(
		$campaign,
		$template_data,
		$is_variation,
		$is_mix_match,
		$view_mode,
		$is_grid_view
	) {
		$offered_product_ids = array();
		$class_name          = 'revx-slider-container revx-slider-x';

		$slider_controller_class = '';

		$prev_button = '
			<div class="revx-slider-controller prev ' . $slider_controller_class . '">
				<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" transform="rotate(180)" viewBox="0 0 24 24">
					<path stroke="currentColor" d="m9 18 6-6-6-6"></path>
				</svg>
			</div>
		';
		$next_button = '
		<div class="revx-slider-controller next ' . $slider_controller_class . '">
			<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24">
				<path stroke="currentColor" d="m9 18 6-6-6-6"></path>
			</svg>
		</div>';

		if ( $is_grid_view ) {
			echo '<div class="' . esc_attr( $class_name ) . '">';
			echo wp_kses( $prev_button, revenue()->get_allowed_tag() );
			echo '<div class="revx-slider-content revx-slider-style">';
		}

		if ( $is_mix_match ) {
			$products = revenue()->get_campaign_meta( $campaign['id'], 'campaign_trigger_items', true );
			foreach ( (array) $products as $product ) {
				$pro     = wc_get_product( $product['item_id'] );
				$item_id = $product['item_id'] ?? null;
				if ( $pro ) {
					$child = $pro->get_children();
					if ( empty( $child ) ) {
						$offered_product_ids[] = $item_id;
					} else {
						$offered_product_ids = array_merge( $offered_product_ids, $child );
					}
				}
			}

			self::render_mix_match_product_item(
				$offered_product_ids,
				array(),
				$campaign,
				$template_data,
				$is_variation,
				$view_mode,
				$render_index,
				$total_offer_products,
				$offer_data,
			);
		}

		if ( $is_grid_view ) {
			echo '</div>';
			echo wp_kses( $next_button, revenue()->get_allowed_tag() );
			echo '</div>';
		}
	}

	/**
	 * Render the Mix and Match products container markup.
	 *
	 * This function generates the HTML structure for displaying the products
	 * within a Mix and Match campaign, including handling grid view, slider layout,
	 * and campaign divider rendering. It outputs dynamic wrappers and product items
	 * based on campaign settings, placement configuration, and template data.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $campaign       The campaign data array. Must include 'id' and 'campaign_type'.
	 * @param array  $template_data  The campaign template data.
	 * @param string $placement      The placement identifier (e.g., sidebar, inpage).
	 * @param bool   $is_variation   Optional. Whether the product is a variation. Default false.
	 *
	 * @return void Outputs HTML directly.
	 */
	public static function render_mix_match_products_container( $campaign, $template_data, $placement, $is_variation ) {
		$view_mode              = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
		$template_data          = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
		$is_grid_view           = ( 'grid' === $view_mode );
		$is_mix_match           = ( 'mix_match' === $campaign['campaign_type'] );
		$products_wrapper_class = ( 'grid' === $view_mode ? 'revx-slider-wrapper' : '' );

		$element_id = $is_grid_view ? 'sliderParent' : 'productsWrapper';

		?>
		<div class="
			<?php
			echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, $element_id ) ) . ' ';
			echo esc_attr( $view_mode ) . ' ';
			echo esc_attr( $products_wrapper_class ) . ' ';
			echo ( $is_grid_view ? 'revx-slider-wrapper' : 'revx-flex-column' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings.
			echo ' revx-items-wrapper revx-scrollbar-common';
			?>
	">
		<?php
			self::render_mix_match_products_item_container(
				$campaign,
				$template_data,
				$is_variation,
				$is_mix_match,
				$view_mode,
				$is_grid_view,
			);
		?>
	</div>

		<?php
	}

	/**
	 * Render the footer section specifically for Mix & Match product offers in a campaign.
	 *
	 * This function generates the HTML markup for the footer area of Mix & Match product offers,
	 * including total price, savings badge, and quantity selector if enabled.
	 * It calculates total prices based on selected products and offer details.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $campaign         The campaign data array. Must include 'id' and 'campaign_type'.
	 * @param array  $template_data    The campaign template data used for rendering.
	 * @param string $footer_id       The identifier for the footer element in the template.
	 * @param array  $selected_product Optional. The list of selected products for mix & match campaigns. Default empty array.
	 * @param string $campaign_id     The ID of the campaign.
	 * @param string $campaign_type   The type of the campaign.
	 *
	 * @return void Outputs HTML directly.
	 */
	public static function render_mix_match_products_footer( $campaign, $template_data, $footer_id, $selected_product = array(), $campaign_id = '', $campaign_type = '' ) {
		$is_mix_match = 'mix_match' === $campaign['campaign_type'];
		$outer_footer = $is_mix_match;

		$offer                  = array();
		$selected_regular_price = 0;
		$is_skip_add_to_cart    = 'yes' === $campaign['skip_add_to_cart'];

		$json_string      = wp_json_encode( $selected_product, JSON_PRETTY_PRINT );
		$selected_product = json_decode( $json_string, true );

		$selected_product_count = count( $selected_product );
		$offers                 = $campaign['offers'];
		$applied_offer          = array();
		foreach ( $offers as $offer ) {
			if ( $offer['quantity'] <= $selected_product_count ) {
				$applied_offer = $offer;
			}
		}

		$template_data = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );

		// Addon: optionally keep the footer (selected list + total + add to cart) hidden until
		// the shopper adds at least one product. Default off, so core behavior is unchanged.
		$hide_footer_until_selected = apply_filters( 'revenue_mix_match_hide_footer_until_selected', false );
		$footer_hidden_class        = ( $hide_footer_until_selected && $selected_product_count < 1 ) ? ' revx-d-none' : '';

		?>
	<div
		class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, $footer_id ) ); ?> revx-d-flex revx-flex-column revx-mixmatch-footer<?php echo esc_attr( $footer_hidden_class ); ?>"
		revx-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>"
	>
		<div class="revx-d-flex revx-item-center revx-gap-10">
			<div class="revx-selected-container revx-scrollbar-common">
				<?php
				foreach ( $selected_product as $product ) {
					$is_required             = ( isset( $product['is_required'] ) && 'yes' === $product['is_required'] );
					$selected_regular_price += (float) $product['regularPrice'] * $product['quantity'];
					?>
					<div class="revx-d-flex revx-item-center revx-gap-10 revx-selected-item"
						data-product-id="<?php echo esc_attr( $product['id'] ); ?>"
						data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>"
					>
						<div
							class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'selectedProductCloseIcon' ) ); ?> revx-relative revx-lh-0"
						>
							<?php
							if ( ! $is_required ) {
								?>
								<div
									class="revx-selected-remove revx-absolute"
									role="button"
									tabindex="0"
									aria-label="Remove item"
								>
									<svg
										xmlns="http://www.w3.org/2000/svg"
										width="1em"
										height="1em"
										fill="none"
										viewBox="0 0 16 16"
									>
										<path
											stroke="currentColor"
											stroke-linecap="round"
											stroke-linejoin="round"
											stroke-width="1.2"
											d="m12 4-8 8m0-8 8 8"
										></path>
									</svg>
								</div>
								<?php
							}
							echo wp_kses_post(
								Revenue_Template_Utils::render_image(
									array(
										'src' => $product['thumbnail'],
										'alt' => $product['productName'],
									)
								)
							);
					?>
						</div>
						<div
							class="revx-d-flex revx-item-start revx-gap-4 revx-flex-column"
						>
							<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'selectedProductTitle', $product['productName'], 'revx-selected-title revx-ellipsis-1' ) ); ?>
							<div
								class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'selectedProductPrice' ) ); ?> revx-d-flex revx-item-center revx-selected-item__product-price"
							>
								<?php echo wp_kses_post( wc_price( $product['regularPrice'] ) ); ?>
								<div class="revx-qty">(x <?php echo esc_attr( $product['quantity'] ); ?>)</div>
							</div>
						</div>
					</div>
					<?php
				}
				?>
				<!-- Clone items start -->
				<div class="revx-d-flex revx-item-center revx-gap-10 revx-selected-item revx-d-none"
					data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>"
				>
					<div
						class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'selectedProductCloseIcon' ) ); ?> revx-relative revx-lh-0"
					>
						<div
							class="revx-selected-remove revx-absolute"
							role="button"
							tabindex="0"
							aria-label="Remove item"
						>
							<svg
								xmlns="http://www.w3.org/2000/svg"
								width="1em"
								height="1em"
								fill="none"
								viewBox="0 0 16 16"
							>
								<path
									stroke="currentColor"
									stroke-linecap="round"
									stroke-linejoin="round"
									stroke-width="1.2"
									d="m12 4-8 8m0-8 8 8"
								></path>
							</svg>
						</div>
						<?php
						echo wp_kses_post(
							Revenue_Template_Utils::render_image(
								array(
									'src' => '',
									'alt' => '',
								)
							)
						);
						?>
					</div>
					<div
						class="revx-d-flex revx-item-start revx-gap-4 revx-flex-column"
					>
						<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'selectedProductTitle', '', 'revx-selected-title revx-ellipsis-1' ) ); ?>
						<div
							class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'selectedProductPrice' ) ); ?> revx-d-flex revx-item-center revx-selected-item__product-price"
						>
							<div class="revx-price-placeholder"></div>
							<div class="revx-qty"></div>
						</div>
					</div>
				</div>
				<!-- Clone items end -->
			</div>
		</div>
		<div
			class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'mixMatchFooterPricing' ) ); ?> revx-d-flex revx-item-center revx-justify-between revx-w-full"
		>
			<div class="revx-w-full revx-price-container">
				<?php echo wp_kses_post( Revenue_Template_Utils::render_rich_text( $template_data, 'totalPriceTitle' ) ); ?>
				<div
					class="<?php echo esc_attr( Revenue_Template_Utils::get_element_class( $template_data, 'mixMatchTotalPrice' ) ); ?> revx-d-flex revx-item-center revx-flex-wrap"
				>
					<?php
						$is_discount      = isset( $applied_offer['type'] ) && 'no_discount' !== $applied_offer['type'];
						$calculated_price = ! $is_discount
							? $selected_regular_price
							: Revenue_Template_Utils::calculated_offer_price( $selected_regular_price, $applied_offer['value'], $applied_offer['type'], $applied_offer['quantity'] );
					?>
					<del 
						class="revx-product-old-price <?php echo ! $is_discount ? 'revx-d-none' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?> "
					>
						<?php echo wp_kses_post( wc_price( $selected_regular_price ) ); ?>
					</del>
					<div class="revx-campaign-item__sale-price"><?php echo wp_kses_post( wc_price( $calculated_price ) ); ?></div>
				</div>
			</div>
			<div
				class="revx-mix-match-reset-btn <?php echo $selected_product_count < 1 ? 'revx-d-none' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>"
				data-mix-match-reset-btn
			>
				Reset
			</div>
			<?php echo wp_kses_post( Revenue_Template_Utils::render_add_to_cart_button( $template_data, false, 'addToCartWrapper', $campaign_id, $campaign_type, '', $is_skip_add_to_cart ) ); ?>
		</div>
	</div>
		<?php
	}

	/**
	 * Get data-price attributes for a product and optional offer.
	 * used in fbt, buy x get y and some other campaign.
	 *
	 * Returns a string of HTML data attributes (such as data-regular-price,
	 * data-sale-price, data-offered-price and data-variations) for the
	 * provided product id and offer. This is used in FBT, Buy X Get Y and
	 * other campaign templates to embed price/variation metadata on product cards.
	 *
	 * @param int $product_id Product ID.
	 * @return string Escaped HTML attributes string.
	 */
	public static function get_data_price_attributes_for_mix_match( $product_id ) {
		$data_price_attributes = '';
		$product_id            = absint( $product_id );
		// if product id is empty return empty string.
		if ( empty( $product_id ) ) {
			return $data_price_attributes;
		}
		$product_ = wc_get_product( $product_id );
		// if product is not instance of \WC_Product return empty string.
		if ( ! $product_ instanceof \WC_Product ) {
			return $data_price_attributes;
		}
		$tax_display  = get_option( 'woocommerce_tax_display_shop', 'incl' );
		$product_type = $product_->get_type();
		if ( 'variable' === $product_type ) {
			$children                = $product_->get_children();
			$variation_data          = array();
			$variation_regular_price = null;
			$variation_sale_price    = null;
			$variation_first_regular = null;
			foreach ( $children as $child_id ) {
				$variation = wc_get_product( $child_id );
				// if variation is not instance of \WC_Product continue, ignore the variation.
				if ( ! $variation instanceof \WC_Product ) {
					continue;
				}
				$price = $variation->get_regular_price();
				// if variation is not in stock or price is empty or null continue, ignore the variation.
				if ( ! $variation->is_in_stock() || '' === $price || null === $price ) {
					continue;
				}
				$attributes = $variation->get_attributes();
				$image_id   = $variation->get_image_id();
				$image_url  = '';
				if ( $image_id ) {
					$image_url = wp_get_attachment_url( $image_id );
				}

				$variation_regular_price = 'incl' === $tax_display
												? wc_get_price_including_tax( $product_, array( 'price' => $price ) )
												: $price;
				$variation_sale_price    = 'incl' === $tax_display
												? wc_get_price_including_tax( $product_, array( 'price' => $variation->get_sale_price() ) )
												: $variation->get_sale_price();
				// Extension Filter: Sale Price Addon.
				$filtered_mix_match_regular_price = apply_filters(
					'revenue_base_price_for_mix_match',
					$variation_regular_price,
					$variation_sale_price
				);
				// using filter for extensibility of sale price addon.
				$variation_index_data = array(
					'id'            => $child_id,
					'regular_price' => $filtered_mix_match_regular_price,
					'sale_price'    => $variation_sale_price,
					'attributes'    => $attributes,
					'image_url'     => $image_url,
				);

				$variation_data[] = $variation_index_data;
				if ( ! $variation_first_regular ) {
					$variation_first_regular = $filtered_mix_match_regular_price;
				}
			}
			$data_price_attributes = 'data-variations=\'' . esc_attr( wp_json_encode( $variation_data ) ) . '\'';
		} else {
			$regular_price = 'incl' === $tax_display
								? wc_get_price_including_tax( $product_, array( 'price' => $product_->get_regular_price() ) )
								: $product_->get_regular_price();
			$sale_price    = 'incl' === $tax_display
								? wc_get_price_including_tax( $product_, array( 'price' => $product_->get_sale_price() ) )
								: $product_->get_sale_price();

			// Extension Filter: Sale Price Addon.
			$filtered_mix_match_regular_price = apply_filters( 'revenue_base_price_for_mix_match', $regular_price, $sale_price );
			// using filter for extensibility of sale price addon.
			$data_price_attributes = 'data-regular-price="' . esc_attr( $filtered_mix_match_regular_price ) . '"';
		}
		return $data_price_attributes;
	}

	/**
	 * Render a Mix & Match product card.
	 *
	 * Outputs the HTML markup for a single Mix & Match product entry including image,
	 * price, variations and add-to-cart controls.
	 *
	 * @param array      $pd            Product data array.
	 * @param string     $layout        Layout mode ('grid'|'list').
	 * @param array|null $campaign      Campaign data array or null.
	 * @param array|null $template_data Template builder data or null.
	 * @param bool       $hide_cart     Whether to hide cart controls.
	 * @param string     $add_cart_id   Wrapper id for add to cart.
	 * @param int|string $product_index Index of the product in the loop.
	 * @return void
	 */
	public static function revenue_render_mix_match_product_card( $pd = array(), $layout = 'grid', $campaign = null, $template_data = null, $hide_cart = false, $add_cart_id = 'addToCartWrapper', $product_index = '' ) {
		if ( ! $pd || ! $campaign ) {
			return;
		}
		$is_mix_match        = 'mix_match' === $campaign['campaign_type'];
		$is_enable_tag       = isset( $pd['isEnableTag'] ) && 'yes' === $pd['isEnableTag'];
		$is_list_layout      = 'list' === $layout;
		$is_skip_add_to_cart = 'yes' === $campaign['skip_add_to_cart'];
		$is_go_to_product    = 'go_to_product' === $campaign['offered_product_click_action'];

		$template_two = $is_mix_match;

		$class       = Revenue_Template_Utils::get_element_class( $template_data, 'productLayout' );
		$extra_class = 'revx-d-flex';
		if ( ! $is_list_layout ) {
			$extra_class = 'revx-d-flex revx-flex-column revx-justify-between revx-slider-title-align revx-slider-product';
		}
		$title_class = '';
		if ( $is_enable_tag ) {
			$title_class .= 'revx-tag-text-color';
		}
		if ( ! $is_list_layout ) {
			$title_class .= 'revx-ellipsis-2 revx-width-11rem';
		}

		$product_title = $pd['title'] ?? '';
		$image_src     = $pd['image'][0] ?? '';

		// Render Image.
		$image_html = Revenue_Template_Utils::render_image(
			array(
				'src'   => $image_src,
				'alt'   => $product_title,
				'class' => Revenue_Template_Utils::get_element_class( $template_data, 'productImage' ),
			)
		);

		$product_id   = isset( $pd['id'] ) ? $pd['id'] : '';
		$product_type = 'none';
		if ( $product_id ) {
			$product_ = wc_get_product( $product_id );
			if ( $product_ ) {
				$product_type = $product_->get_type();
			}
		}

		// -------------------------------------------------------//
		// Prepare data attributes for price and variations.
		// introducing new function for custom work extension.
		$data_price_attributes = self::get_data_price_attributes_for_mix_match( $product_id );
		// Do not remove this.
		// Asif
		// -------------------------------------------------------//.
		$product_quantity = isset( $pd['quantity'] ) ? $pd['quantity'] : '';
		$campaign_id      = $campaign['id'];
		$campaign_type    = $campaign['campaign_type'];

		// Addon: split single-attribute variation cards carry the full variation context so the
		// frontend can add the fixed variation directly (no dropdown). See render_mix_match_product_item.
		$split_variation_attr = ( ! empty( $pd['split_variation'] ) && is_array( $pd['split_variation'] ) )
			? wp_json_encode( $pd['split_variation'] )
			: '';
		// @todo refactor lines and remove extra sapces
		?>
			<div data-product-id="<?php echo esc_attr( $product_id ); ?>"
				date-variation-id="0"
				<?php if ( $split_variation_attr ) : ?>
				data-split-variation='<?php echo esc_attr( $split_variation_attr ); ?>'
				<?php endif; ?>
				<?php echo $data_price_attributes; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each attribute value is esc_attr()'d in get_data_price_attributes_for_mix_match(). ?>
				data-product-index="<?php echo esc_attr( $product_index ); ?>"
				campaign_id="<?php echo esc_attr( $campaign_id ); ?>"   
				campaign_type="<?php echo esc_attr( $campaign_type ); ?>"   
				product_type="<?php echo esc_attr( $product_type ); ?>"   
				data-product-qty="<?php echo esc_attr( $product_quantity ); ?>" class="<?php echo 'revx-' . esc_attr( $campaign_type ) . '-add-to-cart'; ?> <?php echo esc_attr( $class ); ?> revx-relative revx-product-layout <?php echo esc_attr( $extra_class ); ?> <?php echo $is_enable_tag ? 'revx-tag-bg revx-tag-border' : ''; ?> revx-campaign-product-card"
			>
				<?php
				if ( $is_enable_tag ) {
					echo wp_kses_post( Revenue_Template_Utils::render_tag( $template_data ) );
				}
				?>

				<?php
				if ( 'grid' === $layout ) {
					echo '<div>';
				}
				?>
				<div class="revx-product-image">
					<?php
					if ( $is_go_to_product ) {
						echo '<a target="_blank" href="' . esc_url( get_permalink( $product_id ) ) . '">';
						echo wp_kses_post( $image_html );
						echo '</a>';
					} else {
						echo wp_kses_post( $image_html );
					}
					if ( $is_mix_match && 'grid' === $layout ) {
						echo wp_kses_post( Revenue_Template_Utils::render_add_to_cart_button( $template_data, $is_enable_tag, 'addProductWrapper', $campaign_id, $campaign_type, $layout, $is_skip_add_to_cart ) );
					}
					?>
				</div>
				<div class="revx-w-full">
					
					<?php
					if ( $is_go_to_product ) {
						echo '<a href="' . esc_url( get_permalink( $product_id ) ) . '" target="_blank">';
						echo wp_kses_post(
							Revenue_Template_Utils::render_rich_text(
								$template_data,
								'productTitle',
								$product_title,
								$title_class
							),
						);
						echo '</a>';
					} else {
						echo wp_kses_post(
							Revenue_Template_Utils::render_rich_text(
								$template_data,
								'productTitle',
								$product_title,
								$title_class
							)
						);
					}
					?>
					<?php
						Revenue_Template_Utils::revenue_render_product_price(
							$pd,
							$layout,
							$template_data,
							false,
							false,
							false,
							'',
							true,
							'productPriceContainer'
						);
						Revenue_Template_Utils::revenue_render_product_variation(
							$pd,
							$template_data,
							$layout
						);
					?>
				</div>
				<?php
				if ( 'grid' === $layout ) {
					echo '</div>';
				}
				?>
				<?php
				if ( ! $hide_cart ) {
					Revenue_Template_Utils::revenue_render_product_cart(
						$pd,
						$campaign,
						$template_data,
						$add_cart_id,
						false,
						'grid' === $layout,
					);
				}
				?>
			</div>
		<?php
	}
}
