<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Revenue Ajax
 *
 * @package Revenue
 */

namespace Revenue;

use WC_AJAX;
use WC_Data_Store;
use WP_Query;

/**
 * Revenue Templates
 *
 * @hooked on init
 */
class Revenue_Template_Utils {
	use SingletonTrait;

	/**
	 * Constructor
	 */
	public function init() {
	}

	/**
	 * Render the wrapper header for a campaign.
	 *
	 * Outputs heading and subheading markup when present in the template data
	 * and renders the free shipping / countdown block for the campaign.
	 *
	 * @param array|null   $campaign      Campaign data array or null.
	 * @param array|string $template_data Template builder data (array) or empty string.
	 * @return void
	 */
	public static function render_wrapper_header( $campaign = null, $template_data = '' ) {
		// only show heading when heading is enabled.
		if (
			self::get_element_data( $template_data['heading'] ?? array(), 'enableHeading' ) === 'yes' &&
			(
				! empty( self::get_element_data( $template_data['heading'] ?? array(), 'text' ) ) ||
				! empty( self::get_element_data( $template_data['subHeading'] ?? array(), 'text' ) )
			)
		) {
			?>
			<div class="<?php echo esc_attr( self::get_element_class( $template_data, 'class' ) ); ?>">
			<?php echo wp_kses_post( self::render_rich_text( $template_data, 'heading' ) ); ?>
			<?php echo wp_kses_post( self::render_rich_text( $template_data, 'subHeading' ) ); ?>
			</div>
			<?php
		}

		self::render_free_shipping_countdown( $campaign['id'], $template_data );
	}
	/**
	 * Output the campaign divider icon HTML.
	 *
	 * Renders a small SVG used as a divider icon between campaign items.
	 *
	 * @return void Echoes the divider icon markup.
	 */
	public static function get_campaign_divider_icon() {
		?>
			<div class="revx-divider-icon">
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
						d="M8 3.334v9.333M3.333 8h9.333"
					></path>
				</svg>
			</div>
		<?php
	}
	/**
	 * Render campaign divider markup.
	 *
	 * Outputs a wrapper and the divider SVG icon between campaign items. When the
	 * campaign is a wrapper type (buy_x_get_y, bundle, or frequently_bought_together)
	 * it renders an extra wrapper element; otherwise it renders a simple divider.
	 *
	 * @param bool       $is_grid_view  Whether the layout is grid view.
	 * @param string     $element_id    Optional element id to lookup classes in template data.
	 * @param array|null $template_data Template builder data or null.
	 * @param bool       $is_buy_x_get_y True when rendering buy_x_get_y campaign divider.
	 * @param bool       $is_bundle     True when rendering bundle campaign divider.
	 * @param bool       $is_fbt        True when rendering frequently bought together divider.
	 * @return void
	 */
	public static function render_campaign_divider( $is_grid_view = false, $element_id = '', $template_data = null, $is_buy_x_get_y = false, $is_bundle = false, $is_fbt = false ) {
		$class_name = '' !== $element_id ? self::get_element_class( $template_data, $element_id ) : '';
		$is_wrapper = $is_buy_x_get_y || $is_bundle || $is_fbt;

		if ( $is_wrapper ) {
			?>
			<div
				class="<?php echo $is_grid_view ? 'vertical' : 'horizontal'; ?> <?php echo $is_grid_view ? '' : esc_attr( $class_name ); ?> revx-campaign-divider-wrapper"
			>
				<div
					class="revx-campaign-divider <?php echo $is_grid_view ? 'vertical' : 'horizontal'; ?>"
				>
				<?php self::get_campaign_divider_icon(); ?>
				</div>
			</div>
			<?php
		} else {
			?>
			
			<div class="revx-campaign-divider <?php echo $is_grid_view ? 'vertical' : 'horizontal'; ?> <?php echo esc_attr( $class_name ); ?>">
			<?php self::get_campaign_divider_icon(); ?>
			</div>
			<?php
		}
	}

	/**
	 * Total price after discounts/offers for the current render context.
	 *
	 * @var float
	 */
	private static float $after_price = 0;

	/**
	 * Total price before discounts/offers for the current render context.
	 *
	 * @var float
	 */
	private static float $before_price = 0;

	/**
	 * Get total prices for the current render context.
	 *
	 * Returns an associative array with the total price after discounts/offers
	 * and the total price before discounts/offers.
	 *
	 * @return array{after_price:float,before_price:float}
	 */
	public static function get_total_price(): array {
		return array(
			'after_price'  => self::$after_price,
			'before_price' => self::$before_price,
		);
	}
	/**
	 * Render product items for a campaign.
	 *
	 * Processes the provided offered product IDs (variations or simple products),
	 * computes pricing and campaign totals, updates render counters and offer data,
	 * and outputs individual product cards for the campaign.
	 *
	 * @param array        $offered_product_ids   Array of offered product IDs.
	 * @param array        $offer                 Offer data for the current iteration.
	 * @param array        $campaign              Campaign data array.
	 * @param array|string $template_data         Template builder data (array) or empty string.
	 * @param bool         $is_variation          Whether the current context is rendering product variations.
	 * @param bool         $is_divider            Whether to show a divider between items.
	 * @param bool         $is_bundle             Whether the products are part of a bundle.
	 * @param string       $view_mode             Display mode ('grid' or 'list').
	 * @param int          $offer_length          Total number of offers in the campaign.
	 * @param int          $offer_index           Index (1-based) of the current offer in iteration.
	 * @param int          &$render_index         Reference to the global render index counter.
	 * @param int          &$total_offer_products Reference to total offered products rendered.
	 * @param array        &$offer_data           Reference to aggregated offer data (prices, variations, etc.).
	 * @param bool         $is_grid_view          Whether the current layout is grid view.
	 * @param bool         $is_x_product          Optional. Whether the current product is an X (qualifying) product. Default false.
	 * @param bool         $is_trigger            Optional. Whether the current product is a trigger product. Default false.
	 *
	 * @return void Outputs HTML directly.
	 */
	public static function render_product_item( $offered_product_ids, $offer, $campaign, $template_data, $is_variation, $is_divider, $is_bundle, $view_mode, $offer_length, $offer_index, &$render_index, &$total_offer_products, &$offer_data, $is_grid_view, $is_x_product = false, $is_trigger = false ) {
		// @todo refactor long line
		$render_products = $is_variation ? self::get_product_group( $offered_product_ids ) : self::get_product_list( $offered_product_ids );

		$render_product_length   = count( $render_products );
		$total_rendered_products = 0;
		$campaign_type           = $campaign['campaign_type'];
		$is_mix_match            = 'mix_match' === $campaign_type;
		$is_buy_x_get_y          = 'buy_x_get_y' === $campaign_type;
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
			$product_price = ( isset( $default_variation['sale_price'] ) && $default_variation['sale_price'] > 0 ) ? $sale_price : $regular_price;
			// $price_data    = revenue()->calculate_campaign_offered_price( $offer['type'], $offer['value'], $is_mix_match ? $regular_price : $product_price, true );
			$type  = isset( $offer['type'] ) ? sanitize_text_field( $offer['type'] ) : '';
			$value = isset( $offer['value'] ) ? floatval( $offer['value'] ) : 0;

			$save_text = $template_data['saveBadgeWrapper']['text'] ?? '';

			// Extension Filter: Sale Price Addon.
			// filtered price by sale price addon to get sale price otherwise regular price.
			$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );

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
				'sale_price'    => $is_mix_match ? '' : $sale_price,
				'offered_price' => $offered_price,
				'quantity'      => $offer['quantity'] ?? '',
				'price_data'    => $price_data,
				'isEnableTag'   => $offer['isEnableTag'] ?? 'no',
			);
			// product variations array can be made from here, later on.
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

			self::revenue_render_product_card(
				$offer,
				$product_array,
				$view_mode,
				$campaign,
				$template_data,
				$hide_card,
				$is_mix_match ? 'addProductWrapper' : 'addToCartWrapper',
				$product_index,
				$is_x_product,
				$is_trigger
			);

			if ( $is_buy_x_get_y && $total_rendered_products < $render_product_length - 1 ) {
				echo '<div class="revx-item-separator"></div>';
			}

			++$total_rendered_products;

			if ( ! ( $offer_length === $offer_index && $render_product_length === $total_rendered_products ) && $is_divider ) {
				self::render_campaign_divider( $is_grid_view, '', null, $is_buy_x_get_y, $is_bundle, $is_fbt );
			}
		}
		self::$after_price  = $after_price;
		self::$before_price = $before_price;
	}

	/**
	 * Render a single Frequently Bought Together (FBT) product item.
	 *
	 * This function handles rendering of individual product items within an FBT campaign.
	 * It resolves products (including variations), calculates pricing (regular, sale, offered),
	 * manages offer data tracking, and outputs the product card via `self::revenue_render_fbt__product_card()`.
	 *
	 * It also updates campaign-level pricing totals (`self::$after_price` and `self::$before_price`)
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
	 * @see self::get_product_group()          Retrieves grouped products when rendering variations.
	 * @see self::get_product_list()           Retrieves a list of products when not variations.
	 * @see self::revenue_render_fbt__product_card() Handles rendering of the product card markup.
	 * @see self::render_campaign_divider()    Optionally outputs a divider between campaign sections.
	 */
	public static function render_fbt_product_item( $offered_product_ids, $offer, $campaign, $template_data, $is_variation, $is_divider, $is_bundle, $view_mode, $offer_length, $offer_index, &$render_index, &$total_offer_products, &$offer_data, $is_grid_view, $is_trigger = false ) {
		$render_products = $is_variation ? self::get_product_group( $offered_product_ids ) : self::get_product_list( $offered_product_ids );

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

			// $save_tag = self::get_element_data( $template_data[], 'text' );
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
				self::render_campaign_divider( $is_grid_view, '', null, false, $is_bundle, $is_fbt );
			}
		}
		self::$after_price  = $after_price;
		self::$before_price = $before_price;
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
		$render_products         = $is_variation ? self::get_product_group( $offered_product_ids ) : self::get_product_list( $offered_product_ids );
		$total_rendered_products = 0;
		$campaign_type           = $campaign['campaign_type'];
		$is_mix_match            = 'mix_match' === $campaign_type;

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
		self::$after_price  = $after_price;
		self::$before_price = $before_price;
	}

	/**
	 * Render a single Buy X Get Y product item.
	 *
	 * This function handles rendering of individual product items within a Buy X Get Y campaign.
	 * It resolves products (including variations), calculates pricing (regular, sale, offered),
	 * manages offer data tracking, and outputs the product card via `self::revenue_render_product_card()`.
	 *
	 * It also updates campaign-level pricing totals (`self::$after_price` and `self::$before_price`)
	 * and optionally outputs campaign dividers and separators between items.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $offered_product_ids Array of offered product IDs (IDs of products included in the campaign).
	 * @param array  $offer               Optional. Offer data for the current iteration. Default empty array.
	 * @param array  $campaign            The campaign data array. Must include 'id' and 'campaign_type'.
	 * @param array  $template_data       Campaign template data used for rendering product cards.
	 * @param bool   $is_variation        Whether the current context is rendering a product variation.
	 * @param string $view_mode           The display mode. Accepts 'grid' or 'list'.
	 * @param int    $offer_length        Total number of offers in the campaign.
	 * @param int    $offer_index         Index (1-based) of the current offer in iteration.
	 * @param int    &$render_index        Reference. Tracks the global render index across products.
	 * @param int    &$total_offer_products Reference. Tracks the total number of offered products rendered.
	 * @param array  &$offer_data          Reference. Aggregated offer data (prices, variations, etc.).
	 * @param bool   $is_grid_view        Whether the current layout is grid view.
	 * @param bool   $is_x_product        Optional. Whether the current product is an "X" (qualifying) product. Default false.
	 *
	 * @return void Outputs HTML directly.
	 *
	 * @see self::get_product_group()          Retrieves grouped products when rendering variations.
	 * @see self::get_product_list()           Retrieves a list of products when not variations.
	 * @see self::revenue_render_product_card() Handles rendering of the product card markup.
	 * @see self::render_campaign_divider()    Optionally outputs a divider between campaign sections.
	 */
	public static function render_buy_x_get_y_product_item( $offered_product_ids, $offer, $campaign, $template_data, $is_variation, $view_mode, $offer_length, $offer_index, &$render_index, &$total_offer_products, &$offer_data, $is_grid_view, $is_x_product = false ) {
		$render_products = $is_variation ? self::get_product_group( $offered_product_ids ) : self::get_product_list( $offered_product_ids );

		$render_product_length   = count( $render_products );
		$total_rendered_products = 0;
		$campaign_type           = $campaign['campaign_type'];
		$is_buy_x_get_y          = 'buy_x_get_y' === $campaign_type;

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
			$product_title = ( $is_variable_product ) ? $product_data['item_name'] : $offered_product->get_title();
			$regular_price = $default_variation['regular_price'];
			$sale_price    = ( isset( $default_variation['sale_price'] ) && $default_variation['sale_price'] > 0 )
								? $default_variation['sale_price']
								: $regular_price;
			$type          = isset( $offer['type'] ) ? sanitize_text_field( $offer['type'] ) : '';
			$value         = isset( $offer['value'] ) ? floatval( $offer['value'] ) : 0;

			// Extension Filter: Sale Price Addon.
			$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );
			// based on extension filter use sale price or regular price for calculation.
			$price_data = revenue()->calculate_campaign_offered_price( $type, $value, $filtered_price, true );

			if ( is_array( $price_data ) && isset( $price_data['price'] ) ) {
				$offered_price = floatval( $price_data['price'] );
			} else {
				$offered_price = floatval( $price_data );
			}
			$quantity = '';

			// check only for x product.
			$is_set_individual_product_quantity = revenue()->get_campaign_meta( $campaign['id'], 'buy_x_get_y_trigger_qty_status', true ) === 'yes';
			$trigger_product_relation           = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';

			if ( is_array( $offer ) ) {
				foreach ( $offer as $off ) {
					if ( is_array( $off ) && isset( $off['item_id'] ) ) {
						if ( $product_id == $off['item_id'] ) {
							if ( $is_set_individual_product_quantity ) {
								$quantity = $off['quantity'];
							} else {
								$quantity = 1;
							}

							break;
						}
					}
				}
			}
			// @todo refactor this with ternary operator
			if ( $is_x_product ) {
				$product_array = array(
					'id'            => $product_id,
					'title'         => $product_title,
					'image'         => $image,
					'regular_price' => $regular_price,
					'sale_price'    => $sale_price,
					'offered_price' => $sale_price,
					'quantity'      => $offer['quantity'] ?? $quantity,
					'price_data'    => $price_data,
					'isEnableTag'   => $offer['isEnableTag'] ?? 'no',
				);
			} else {
				$product_array = array(
					'id'            => $product_id,
					'title'         => $product_title,
					'image'         => $image,
					'regular_price' => $regular_price,
					'sale_price'    => $sale_price,
					'offered_price' => $offered_price,
					'quantity'      => $offer['quantity'] ?? $quantity,
					'price_data'    => $price_data,
					'isEnableTag'   => $offer['isEnableTag'] ?? 'no',
				);
			}

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

			self::revenue_render_buy_x_get_y_product_card(
				$offer,
				$product_array,
				$view_mode,
				$campaign,
				$template_data,
				false,
				'addToCartWrapper',
				$product_index,
				$is_x_product
			);

			if ( $is_buy_x_get_y && $total_rendered_products < $render_product_length - 1 ) {
				echo '<div class="revx-item-separator"></div>';
			}

			++$total_rendered_products;

			if ( ! ( $offer_length === $offer_index && $render_product_length === $total_rendered_products ) ) {
				// self::render_campaign_divider( $is_grid_view, '', null, $is_buy_x_get_y, false );
			}
		}
		self::$after_price  = $after_price;
		self::$before_price = $before_price;
	}

	/**
	 * Render the products item container for a campaign.
	 *
	 * Builds and outputs the products container markup for different campaign types
	 * (mix & match, buy_x_get_y, bundle, frequently_bought_together, free_shipping_bar,
	 * spending_goal), handling slider wrappers, trigger items and prepending trigger/upsell
	 * products when necessary.
	 *
	 * @param array                     $campaign         Campaign data array.
	 * @param array|string              $template_data    Template builder data (array) or empty string.
	 * @param array                     $offers           Offers array for the campaign.
	 * @param bool                      $is_mix_match     True if this is a mix & match campaign.
	 * @param bool                      $is_x_product     True when rendering qualifying (X) products for buy_x_get_y.
	 * @param bool                      $is_variation     Whether products are variations.
	 * @param bool                      $is_divider       Whether to show dividers between items.
	 * @param bool                      $is_bundle        Whether the campaign is a bundle.
	 * @param string                    $view_mode        Display mode ('grid' or 'list').
	 * @param bool                      $is_grid_view     Whether products are displayed in grid view.
	 * @param \WC_Product|WP_Post|false $current_product Current product object or false.
	 * @return void
	 */
	public static function render_products_item_container(
		$campaign,
		$template_data,
		$offers,
		$is_mix_match,
		$is_x_product,
		$is_variation,
		$is_divider,
		$is_bundle,
		$view_mode,
		$is_grid_view,
		$current_product
	) {
		$offered_product_ids = array();
		$class_name          = 'revx-slider-container revx-slider-x';
		$is_fbt              = 'frequently_bought_together' === $campaign['campaign_type'];
		$is_fsb              = 'free_shipping_bar' === $campaign['campaign_type'];
		$is_spg              = 'spending_goal' === $campaign['campaign_type'];
		$is_buy_x_get_y      = 'buy_x_get_y' === $campaign['campaign_type'];

		$slider_controller_class = ( $is_fsb || $is_spg ) ? 'revx-slider2-controller' : '';

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

		if ( $is_buy_x_get_y ) {
			$class_name = 'revx-slider-container revx-multiple-slider ' . ( $is_x_product ? 'revx-slider-x' : 'revx-slider-y' );
		}
		if ( $is_grid_view || $is_fsb || $is_spg ) {
			echo '<div class="' . esc_attr( $class_name ) . '">';
			echo wp_kses( $prev_button, revenue()->get_allowed_tag() );
			echo '<div class="revx-slider-content ' . esc_attr( ( $is_fsb || $is_spg ) ? 'revx-slider2-content' : 'revx-slider-style' ) . '">';
		}

		if ( $is_fsb || $is_spg ) {
			$product_ids    = array();
			$discount_type  = '';
			$discount_value = 0;

			foreach ( $campaign[ $is_spg ? 'spending_goal_upsell_products' : 'upsell_products' ] as $upsell_products ) {
				$discount_type  = $upsell_products['type'] ?? '';
				$discount_value = $upsell_products['value'] ?? 0;

				foreach ( $upsell_products['products'] as $upsell ) {
					$item_id = $upsell['item_id'] ?? null;
					if ( null !== $item_id ) {
						$product_ids[] = $item_id;
					}
				}
			}

			// Final normalized structure.
			$new_array = array(
				'products' => $product_ids,
				'value'    => $discount_value,
				'type'     => $discount_type,
			);
			array_unshift( $offers, $new_array );
		}

		$trigger_product_relation = 'or';
		if ( $is_fbt ) {
			$is_fbt_required          = isset( $campaign['fbt_is_trigger_product_required'] ) ? $campaign['fbt_is_trigger_product_required'] === 'yes' : false;
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
					$new_products[] = $item_id;
				}
			}
			$new_array = array(
				'products'        => $new_products,
				'is_fbt_required' => $is_fbt_required,
			);
			array_unshift( $offers, $new_array );
		}
		if ( $is_bundle ) {
			$is_with_trigger_product = isset( $campaign['bundle_with_trigger_products_enabled'] ) ? 'yes' === $campaign['bundle_with_trigger_products_enabled'] : false;

			if ( $is_with_trigger_product ) {
				// Collect all existing product IDs in $offers.
				$existing_ids = array();
				foreach ( $offers as $offer_entry ) {
					if ( isset( $offer_entry['products'] ) && is_array( $offer_entry['products'] ) ) {
						$existing_ids = array_merge( $existing_ids, $offer_entry['products'] );
					}
				}
				$existing_ids = array_unique( $existing_ids );

				$trigger_product_relation = 'or';
				$is_category              = ( 'category' === $campaign['campaign_trigger_type'] ) || ( 'all_products' === $campaign['campaign_trigger_type'] );
				$trigger_items            = revenue()->getTriggerProductsData(
					$campaign['campaign_trigger_items'],
					$trigger_product_relation,
					$current_product->get_id(),
					$is_category
				);

				$new_products = array();
				foreach ( $trigger_items as $trigger ) {
					$item_id = $trigger['item_id'] ?? null;
					if ( null !== $item_id && ! in_array( $item_id, $existing_ids ) ) {
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

				if ( ! empty( $new_products ) ) {
					$new_array = array(
						'products' => $new_products,
						'source'   => 'trigger',
					);
					array_unshift( $offers, $new_array );
				}
			}
		}

		if ( $is_mix_match || $is_x_product ) {
			$is_category              = ( 'category' === $campaign['campaign_trigger_type'] ) || ( 'all_products' === $campaign['campaign_trigger_type'] );
			$trigger_product_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';
			$products                 = $is_x_product ? revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $current_product->get_id(), $is_category ) : revenue()->get_campaign_meta( $campaign['id'], 'campaign_trigger_items', true );
			foreach ( $products as $product ) {
				$offered_product_ids[] = $product['item_id'];
			}

			self::render_product_item(
				$offered_product_ids,
				array(),
				$campaign,
				$template_data,
				$is_variation,
				$is_divider,
				$is_bundle,
				$view_mode,
				0,
				0,
				$render_index,
				$total_offer_products,
				$offer_data,
				$is_grid_view,
				$is_x_product
			);
		} elseif ( is_array( $offers ) ) {
			$offer_length = count( $offers );
			$offer_index  = 0;

			foreach ( $offers as $offer_index => $offer ) {
				$offered_product_ids = isset( $offer['products'] ) ? $offer['products'] : array();
				++$offer_index;
				$is_trigger = isset( $offer['source'] ) && 'trigger' === $offer['source'];

				self::render_product_item(
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
					false,
					$is_trigger
				);
			}
		}

		if ( $is_grid_view || $is_fsb || $is_spg ) {
			echo '</div>';
			echo wp_kses( $next_button, revenue()->get_allowed_tag() );
			echo '</div>';
		}
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

		$is_fbt_required          = isset( $campaign['fbt_is_trigger_product_required'] ) ? 'yes' === $campaign['fbt_is_trigger_product_required'] : false;
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
			'products'        => $new_products,
			'is_fbt_required' => $is_fbt_required,
			'source'          => 'trigger',
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
			foreach ( $products as $product ) {
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
	 * Render the Buy X Get Y product items container.
	 *
	 * Outputs the HTML wrapper and product items for a Buy X Get Y campaign.
	 * Handles rendering for both "X" (trigger/qualifying) products and "Y" (offered) products,
	 * depending on campaign configuration, view mode (grid or list), and variation state.
	 * Adds slider navigation controls when in grid view.
	 *
	 * @since 1.0.0
	 *
	 * @param array            $campaign        The campaign data array. Must include 'id', 'campaign_type', and trigger information.
	 * @param array            $template_data   The campaign template data used for rendering.
	 * @param array|false      $offers          List of offers for the campaign, or false if none.
	 * @param bool             $is_x_product    Whether the container is rendering qualifying (X) products. Default false.
	 * @param bool             $is_variation    Whether the current product is a variation. Default false.
	 * @param string           $view_mode       The display mode. Accepts 'grid' or 'list'.
	 * @param bool             $is_grid_view    Whether the layout is grid view. Default false.
	 * @param \WC_Product|null $current_product The current WooCommerce product object, if available. Null if not applicable.
	 *
	 * @return void Outputs HTML directly.
	 */
	public static function render_buy_x_get_y_products_item_container(
		$campaign,
		$template_data,
		$offers,
		$is_x_product,
		$is_variation,
		$view_mode,
		$is_grid_view,
		$current_product
	) {

		$offered_product_ids = array();
		$class_name          = 'revx-slider-container revx-slider-x';

		$is_buy_x_get_y = 'buy_x_get_y' === $campaign['campaign_type'];

		$prev_button = '
			<div class="revx-slider-controller prev">
				<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" transform="rotate(180)" viewBox="0 0 24 24">
					<path stroke="currentColor" d="m9 18 6-6-6-6"></path>
				</svg>
			</div>
		';
		$next_button = '
		<div class="revx-slider-controller next">
			<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24">
				<path stroke="currentColor" d="m9 18 6-6-6-6"></path>
			</svg>
		</div>';

		if ( $is_buy_x_get_y ) {
			$class_name = 'revx-slider-container revx-multiple-slider ' . ( $is_x_product ? 'revx-slider-x' : 'revx-slider-y' );
		}
		if ( $is_grid_view ) {
			echo '<div class="' . esc_attr( $class_name ) . '">';
			echo wp_kses( $prev_button, revenue()->get_allowed_tag() );
			echo '<div class="revx-slider-content revx-slider-style">';
		}

		$trigger_product_relation = 'or';

		if ( $is_x_product ) {
			$is_category              = ( 'category' === $campaign['campaign_trigger_type'] ) || ( 'all_products' === $campaign['campaign_trigger_type'] );
			$trigger_product_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';
			$products                 = $is_x_product ? revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $current_product->get_id(), $is_category ) : revenue()->get_campaign_meta( $campaign['id'], 'campaign_trigger_items', true );
			foreach ( $products as $product ) {
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
			// x product is rendered here.
			self::render_buy_x_get_y_product_item(
				$offered_product_ids,
				$products,
				$campaign,
				$template_data,
				$is_variation,
				$view_mode,
				0,
				0,
				$render_index,
				$total_offer_products,
				$offer_data,
				$is_grid_view,
				$is_x_product
			);
		} elseif ( is_array( $offers ) ) {
			$offer_length = count( $offers );
			$offer_index  = 0;

			foreach ( $offers as $offer_index => $offer ) {
				$offered_product_ids = isset( $offer['products'] ) ? $offer['products'] : array();
				++$offer_index;

				self::render_buy_x_get_y_product_item(
					$offered_product_ids,
					$offer,
					$campaign,
					$template_data,
					$is_variation,
					$view_mode,
					$offer_length,
					$offer_index,
					$render_index,
					$total_offer_products,
					$offer_data,
					$is_grid_view,
				);
				// handles case: last product of offer but has more offer afterwards.
				if ( $offer_index <= $offer_length - 1 ) {
					// add seperator inbetween offers.
					echo '<div class="revx-item-separator"></div>';
				}
			}
		}

		if ( $is_grid_view ) {
			echo '</div>';
			echo wp_kses( $next_button, revenue()->get_allowed_tag() );
			echo '</div>';
		}
	}

	/**
	 * Render the products container wrapper and its inner content for a campaign.
	 *
	 * Builds and outputs the products container (grid/list) including slider metadata
	 * and delegates rendering of inner product items based on campaign type.
	 *
	 * @param array             $campaign      Campaign data array.
	 * @param array|string      $template_data Template builder data (array) or empty string.
	 * @param string            $placement     Placement identifier.
	 * @param bool              $is_variation  Whether products are variations.
	 * @param bool              $is_x_product  Whether rendering qualifying X products for buy_x_get_y.
	 * @param \WC_Product|false $product       Current product object or false.
	 * @return void
	 */
	public static function render_products_container( $campaign, $template_data, $placement, $is_variation = false, $is_x_product = false, $product = false ) {
		$view_mode              = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
		$template_data          = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
		$offers                 = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
		$placement_settings     = revenue()->get_placement_settings( $campaign['id'] );
		$display_style          = isset( $placement_settings['display_style'] ) ? $placement_settings['display_style'] : 'inpage';
		$slider_columns         = json_encode( self::get_slider_data( $template_data ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
		$products_wrapper_class = 'grid' === $view_mode ? 'revx-slider-wrapper' : '';
		$is_grid_view           = 'grid' === $view_mode;

		$is_bundle      = 'bundle_discount' === $campaign['campaign_type'];
		$is_mix_match   = 'mix_match' === $campaign['campaign_type'];
		$is_buy_x_get_y = 'buy_x_get_y' === $campaign['campaign_type'];
		$is_fsb         = 'free_shipping_bar' === $campaign['campaign_type'];
		$is_spg         = 'spending_goal' === $campaign['campaign_type'];
		$is_fbt         = 'frequently_bought_together' === $campaign['campaign_type'];
		$is_divider     = $is_bundle || $is_fbt;

		$is_upsell_on     = 'yes' === $campaign['upsell_products_status'];
		$is_spg_upsell_on = 'yes' === $campaign['spending_goal_upsell_product_status'];

		$element_id = 'productsWrapper';
		if ( $is_fsb ) {
			if ( ! $is_upsell_on ) {
				return;
			}
			$element_id   = 'sliderParent2';
			$is_grid_view = false;
		}
		if ( $is_spg ) {
			if ( ! $is_spg_upsell_on ) {
				return;
			}
			$element_id   = 'sliderParent2';
			$is_grid_view = false;
		}

		if ( $is_buy_x_get_y ) {
			$element_id = 'productsContainerWrapper';
		}
		if ( $is_grid_view ) {
			$element_id = 'sliderParent';
		}

		?>
			<div class="
					<?php
						echo esc_attr( self::get_element_class( $template_data, $element_id ) ) . ' ';

					if ( $is_buy_x_get_y && ! $is_grid_view ) {
						echo 'revx-buy-x-get-y-container';
					} else {
						echo ( $is_fsb || $is_spg ) ? '' : esc_attr( $view_mode );
						echo ' ' . esc_attr( $products_wrapper_class );
						echo ' ' . ( $is_grid_view || $is_fsb || $is_spg ? 'revx-slider-wrapper' : 'revx-flex-column' );
						echo ' ' . ( $is_divider ? 'revx-revx-product-body-scroll' : '' );
						echo ' revx-items-wrapper revx-scrollbar-common';
					}
					?>
				"
				data-slider-columns='<?php echo esc_attr( $slider_columns ); ?>'
				data-layout='<?php echo esc_attr( $display_style ); ?>'
			>
			<?php
			if ( $is_grid_view || $is_fsb || $is_spg ) {
				echo '<div class="revx-slider-parent">';
			} elseif ( $is_buy_x_get_y ) {
				echo '<div class="revx-buy-x-get-y-wrapper list revx-d-flex revx-flex-column revx-items-wrapper revx-scrollbar-common">';
			}

			switch ( $campaign['campaign_type'] ) {
				case 'buy_x_get_y':
					$trigger_product_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';
					$is_category              = ( 'category' === $campaign['campaign_trigger_type'] ) || ( 'all_products' === $campaign['campaign_trigger_type'] );

					if ( empty( $trigger_product_relation ) ) {
						$trigger_product_relation = 'or';
					}
					$product_id    = $product->get_id();
					$trigger_items = revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_product_relation, $product_id, $is_category );
					// Here x product to render.
					self::render_products_item_container(
						$campaign,
						$template_data,
						$trigger_items,
						$is_mix_match,
						$is_x_product,
						$is_variation,
						$is_divider,
						$is_bundle,
						$view_mode,
						$is_grid_view,
						$product
					);
					break;

				default:
					self::render_products_item_container(
						$campaign,
						$template_data,
						$offers,
						$is_mix_match,
						$is_x_product,
						$is_variation,
						$is_divider,
						$is_bundle,
						$view_mode,
						$is_grid_view,
						$product
					);
					break;

			}

			if ( $is_buy_x_get_y && $is_grid_view ) {
				self::render_campaign_divider( $is_grid_view, 'CampaignDivider', $template_data, $is_buy_x_get_y );
				self::render_products_item_container(
					$campaign,
					$template_data,
					$offers,
					$is_mix_match,
					false,
					false,
					$is_divider,
					$is_bundle,
					$view_mode,
					$is_grid_view,
					$product
				);
			}
			if ( $is_grid_view || $is_fsb || $is_spg || $is_buy_x_get_y ) {
					echo '</div>';
			}
			?>
			</div>
			<?php
			if ( $is_buy_x_get_y && ! $is_grid_view ) {
				self::render_campaign_divider( $is_grid_view, 'CampaignDivider', $template_data, $is_buy_x_get_y );
				?>
				<div class="<?php echo esc_attr( self::get_element_class( $template_data, $element_id ) ) . ' revx-buy-x-get-y-container'; ?>"
					data-slider-columns='<?php echo esc_attr( $slider_columns ); ?>'
					data-layout='<?php echo esc_attr( $display_style ); ?>'
				>
					<div class="revx-buy-x-get-y-wrapper list revx-d-flex revx-flex-column revx-items-wrapper revx-scrollbar-common">
						<?php
							self::render_products_item_container(
								$campaign,
								$template_data,
								$offers,
								$is_mix_match,
								false,
								false,
								$is_divider,
								$is_bundle,
								$view_mode,
								$is_grid_view,
								$product
							);
						?>
					</div>
				</div>

			<?php } ?>
			<?php
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
		$slider_columns         = json_encode( self::get_slider_data( $template_data ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
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
						echo esc_attr( self::get_element_class( $template_data, $element_id ) ) . ' ';
						echo esc_attr( $view_mode );
						echo ' ' . esc_attr( $products_wrapper_class );
						echo ' ' . ( $is_grid_view ? 'revx-slider-wrapper' : 'revx-flex-column' );
						echo ' ' . ( $is_divider ? 'revx-revx-product-body-scroll' : '' );
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
			echo esc_attr( self::get_element_class( $template_data, $element_id ) ) . ' ';
			echo esc_attr( $view_mode ) . ' ';
			echo esc_attr( $products_wrapper_class ) . ' ';
			echo ( $is_grid_view ? 'revx-slider-wrapper' : 'revx-flex-column' );
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
	 * Render the Buy X Get Y products container markup.
	 *
	 * This function generates the HTML structure for displaying the products
	 * within a Buy X Get Y campaign, including handling grid view, slider layout,
	 * and campaign divider rendering. It outputs dynamic wrappers and product items
	 * based on campaign settings, placement configuration, and template data.
	 *
	 * @since 1.0.0
	 *
	 * @param array             $campaign       The campaign data array. Must include 'id' and 'campaign_type'.
	 * @param array             $template_data  The campaign template data.
	 * @param string            $placement      The placement identifier (e.g., sidebar, inpage).
	 * @param bool              $is_variation   Optional. Whether the product is a variation. Default false.
	 * @param bool              $is_x_product   Optional. Whether the product is an X (qualifying) product. Default false.
	 * @param \WC_Product|false $product Optional. The WooCommerce product object if available, or false. Default false.
	 *
	 * @return void Outputs HTML directly.
	 */
	public static function render_buy_x_get_y_products_container(
		$campaign,
		$template_data,
		$placement,
		$is_variation = false,
		$is_x_product = false,
		$product = false
	) {
		$view_mode              = revenue()->get_placement_settings( $campaign['id'], $placement, 'builder_view' ) ?? 'list';
		$template_data          = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
		$offers                 = revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
		$placement_settings     = revenue()->get_placement_settings( $campaign['id'] );
		$display_style          = isset( $placement_settings['display_style'] ) ? $placement_settings['display_style'] : 'inpage';
		$slider_columns         = json_encode( self::get_slider_data( $template_data ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
		$products_wrapper_class = 'grid' === $view_mode ? 'revx-slider-wrapper' : '';
		$is_grid_view           = 'grid' === $view_mode;
		$is_buy_x_get_y         = 'buy_x_get_y' === $campaign['campaign_type'];

		$element_id = 'productsContainerWrapper';

		if ( $is_grid_view ) {
			$element_id = 'sliderParent';
		}

		?>
			<div 
				class="
					<?php
						echo esc_attr( self::get_element_class( $template_data, $element_id ) ) . ' ';

					if ( ! $is_grid_view ) {
						echo 'revx-buy-x-get-y-container';
					} else {
						echo esc_attr( $view_mode );
						echo ' ' . esc_attr( $products_wrapper_class );
						echo ' ' . ( $is_grid_view ? 'revx-slider-wrapper' : 'revx-flex-column' );
						echo ' revx-items-wrapper revx-scrollbar-common';
					}
					?>
				"
				data-slider-columns='<?php echo esc_attr( $slider_columns ); ?>'
				data-layout='<?php echo esc_attr( $display_style ); ?>'
			>
				<?php
				if ( $is_grid_view ) {
					echo '<div class="revx-slider-parent">';
				} else {
					echo '<div class="revx-buy-x-get-y-wrapper list revx-d-flex revx-flex-column revx-items-wrapper revx-scrollbar-common">';
				}
				self::render_buy_x_get_y_products_item_container(
					$campaign,
					$template_data,
					$offers,
					$is_x_product,
					$is_variation,
					$view_mode,
					$is_grid_view,
					$product
				);

				if ( $is_grid_view ) {
					self::render_campaign_divider( $is_grid_view, 'CampaignDivider', $template_data, $is_buy_x_get_y );
					self::render_buy_x_get_y_products_item_container(
						$campaign,
						$template_data,
						$offers,
						false,
						true,
						$view_mode,
						$is_grid_view,
						$product
					);
				}
				echo '</div>'
				?>
			</div>
			<?php
			if ( ! $is_grid_view ) {
				self::render_campaign_divider( $is_grid_view, 'CampaignDivider', $template_data, $is_buy_x_get_y );
				?>
				<div class="<?php echo esc_attr( self::get_element_class( $template_data, $element_id ) ) . ' revx-buy-x-get-y-container'; ?>"
					data-slider-columns='<?php echo esc_attr( $slider_columns ); ?>'
					data-layout='<?php echo esc_attr( $display_style ); ?>'
				>
					<div class="revx-buy-x-get-y-wrapper list revx-d-flex revx-flex-column revx-items-wrapper revx-scrollbar-common">
						<?php
							self::render_buy_x_get_y_products_item_container(
								$campaign,
								$template_data,
								$offers,
								false,
								true,
								$view_mode,
								$is_grid_view,
								$product
							);
						?>
					</div>
				</div>
				<?php
			}
	}

	/**
	 * Calculate the offer-adjusted price based on discount type.
	 *
	 * @param float  $price       Original product price.
	 * @param float  $offer_value Discount value (percentage or fixed amount).
	 * @param string $offer_type  Type of discount. Accepts:
	 *                            - 'percentage'     → percentage discount.
	 *                            - 'fixed_discount' → fixed price discount.
	 * @param int    $offer_qty   Quantity of the product.
	 * @return float Adjusted price after applying discount.
	 */
	public static function calculated_offer_price( $price, $offer_value, $offer_type, $offer_qty = 1 ) {
		$price       = (float) $price;
		$offer_value = (float) $offer_value;

		if ( 'percentage' === $offer_type ) {
			return (float) ( $price - ( $price * ( $offer_value / 100 ) ) );

		} elseif ( 'fixed_discount' === $offer_type ) {
			return (float) ( $price - ( $offer_value * $offer_qty ) );
		} elseif ( 'fixed_price' === $offer_type ) {
			return (float) ( $offer_value * $offer_qty );
		}

		return $price;
	}

	/**
	 * Render the footer section for product offers in a campaign.
	 *
	 * This function generates the HTML markup for the footer area of product offers,
	 * including total price, savings badge, and quantity selector if enabled.
	 * It handles different campaign types such as bundle discounts and mix & match,
	 * and calculates total prices based on selected products and offer details.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $campaign         The campaign data array. Must include 'id' and 'campaign_type'.
	 * @param array  $template_data    The campaign template data used for rendering.
	 * @param string $footer_id       The identifier for the footer element in the template.
	 * @param array  $selected_product Optional. The list of selected products for mix & match campaigns. Default empty array.
	 *
	 * @return void Outputs HTML directly.
	 */
	public static function render_products_footer( $campaign, $template_data, $footer_id, $selected_product = array() ) {
		$campaign_type       = $campaign['campaign_type'];
		$campaign_id         = $campaign['id'];
		$is_bundle           = 'bundle_discount' === $campaign['campaign_type'];
		$is_mix_match        = 'mix_match' === $campaign['campaign_type'];
		$outer_footer        = $is_mix_match || 'volume_discount' === $campaign_type;
		$is_center           = 'yes' === $campaign['quantity_selector_enabled'];
		$is_skip_add_to_cart = 'yes' === $campaign['skip_add_to_cart'];

		$is_bundle_with_trigger_enabled = 'yes' === $campaign['bundle_with_trigger_products_enabled'];
		$is_bundle_with_trigger_item    = $campaign['campaign_trigger_items'] ?? array();
		$campaign_trigger_type          = 'all_products' === $campaign['campaign_trigger_type'];

		$trigger_item_data = false;
		if ( is_array( $is_bundle_with_trigger_item ) && ! empty( $is_bundle_with_trigger_item ) ) {
			$trigger_item_data = reset( $is_bundle_with_trigger_item );
		}
		$trigger_item_id = isset( $trigger_item_data['item_id'] ) ? $trigger_item_data['item_id'] : 0;

		if ( $campaign_trigger_type ) {
			// use get_the_ID() if you only need  the id of the product that triggered the campaign.
			$trigger_item_id = get_the_ID();
		}

		$offer                  = array();
		$selected_regular_price = 0;

		if ( $is_mix_match ) {
			$json_string      = wp_json_encode( $selected_product, JSON_PRETTY_PRINT );
			$selected_product = json_decode( $json_string, true );

			$selected_product_count = count( $selected_product );
			$offers                 = $campaign['offers'];
			$applied_offer          = array();
			foreach ( $offers as $offer ) {
				if ( $offer['quantity'] == $selected_product_count ) {
					$applied_offer = $offer;
				}
			}
		}

		$offer_product        = $campaign['offers'][0] ?? array();
		$offers               = revenue()->get_campaign_meta( $campaign['id'], 'offers', true ) ?? array();
		$offer_qty            = 0;
		$total_regular_price  = 0;
		$total_discount_value = 0;

		// only for bundle with trigger enabled, add trigger product id to offers products array.
		if ( $is_bundle_with_trigger_enabled && ! empty( $trigger_item_id ) ) {
			$offers[0]['products']   = $offers[0]['products'] ?? array();
			$offers[0]['products'][] = $trigger_item_id;
		}

		foreach ( $offers as $offer ) {
			$offered_product_ids = $offer['products'];
			$quantity            = $offer['quantity'];
			$offer_qty          += $quantity;
			$discount_type       = $offer['type'];
			$discount_value      = $offer['value'];

			$processed_parents = array(); // ✅ Track already processed parent IDs for each offer
			foreach ( $offered_product_ids as $product_id ) {
				$product = wc_get_product( $product_id );

				unset( $first_child_id );

				if ( ! $product ) {
					continue;
				}

				$product_type  = $product->get_type();
				$has_parent_id = 0;

				// ✅ Identify parent for variable/variation products
				if ( 'variation' === $product_type ) {
					$has_parent_id = $product->get_parent_id();
				} elseif ( 'variable' === $product_type ) {
					$has_parent_id  = $product->get_id(); // Treat variable itself as parent.
					$child_ids      = $product->get_children();
					$first_child_id = $child_ids[0];
					$product        = wc_get_product( $first_child_id );
				}

				// ✅ Skip duplicate variations (process parent once)
				if ( $has_parent_id && in_array( $has_parent_id, $processed_parents, true ) ) {
					continue;
				}

				// ✅ Mark parent as processed
				if ( $has_parent_id ) {
					$processed_parents[] = $has_parent_id;
				}

				$tax_display   = get_option( 'woocommerce_tax_display_shop', 'incl' );
				$regular_price = 'incl' === $tax_display
					? wc_get_price_including_tax( $product, array( 'price' => $product->get_regular_price() ) )
					: floatval( $product->get_regular_price() );

				$current_product_price = $regular_price * $quantity;
				$total_regular_price  += $regular_price * $quantity;

				// ✅ Skip trigger product discount when bundle trigger is enabled
				$is_trigger_product_id = isset( $first_child_id ) || $trigger_item_id == $product_id;
				if ( $is_trigger_product_id && $is_bundle_with_trigger_enabled ) {
					continue;
				}

				// ✅ Apply discount logic
				if ( 'percentage' === $discount_type ) {
					$discount_amount       = ( $regular_price * floatval( $discount_value ) / 100 ) * $quantity;
					$total_discount_value += $discount_amount;
				} elseif ( 'amount' === $discount_type ) {
					$total_discount_value += $current_product_price - ( floatval( $discount_value ) * $quantity );
				} elseif ( 'fixed_discount' === $discount_type ) {
					$total_discount_value += floatval( $discount_value ) * $quantity;
				} elseif ( 'free' === $discount_type ) {
					$total_discount_value += $current_product_price;
				}
			}
		}

		$template_data = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
		$offer_text    = $template_data['saveBadgeWrapper']['text'] ?? '';
		// Calculate total discount percentage.
		$total_discount = number_format( ( $total_discount_value * 100 ) / $total_regular_price, 2 );
		/* translators: %s: discount percentage value without the percent sign (e.g. "15" for 15%). The percent sign is added in the translation string. */
		$save_data = sprintf( __( '%s%%', 'revenue' ), $total_discount );
		$message   = str_replace( '{discount_value}', $save_data, $offer_text );

		if ( $is_bundle ) {
			$message = str_replace( '{save_amount}', $save_data, $offer_text );
			if ( 0 === $total_discount_value ) {
				$message = '';
			}
		}

		$price_data = array(
			'message' => $message,
			'type'    => $discount_type,
			'value'   => 10,
			'price'   => 10.8,
		);

		$total_offered_price = $total_regular_price - $total_discount_value;
		// showing total regular and offer price in footer.
		$price_array = array(
			'regular_price' => $total_regular_price,
			'offered_price' => $total_offered_price,
			// 'quantity'      => $offer_qty,
			'price_data'    => $price_data,
		);
		if ( $is_bundle ) {
			?>
			<div
				class="<?php echo esc_attr( self::get_element_class( $template_data, $footer_id ) ); ?> revx-flex-wrap revx-d-flex revx-item-center <?php echo 'revx-w-full revx-justify-between'; ?>"
			>
			<?php
			self::render_save_badge( $template_data, 'empty', '', 'display: block; flex-shrink: 0', 'bundleLabel' );
			?>
				<div class="revx-d-flex revx-item-center revx-justify-end revx-gap-10">
				<?php
					echo wp_kses_post( self::render_rich_text( $template_data, 'totalText' ) );
					self::revenue_render_product_price(
						$price_array,
						'list',
						$template_data,
						false,
						false,
						false,
						'',
						false,
						'bundleTotalPrice'
					);
				?>
				</div>
			</div>
			<div
				class="<?php echo esc_attr( self::get_element_class( $template_data, 'bundleCartWrapper' ) ); ?> 
						revx-d-flex revx-parent-btn
						revx-item-center 
						revx-justify-<?php echo $is_center ? 'between' : 'center'; ?>"
						data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>"
						data-quantity="<?php echo esc_attr( $offer_product['quantity'] ?? 1 ); ?>"
			>
				<?php self::revenue_render_product_quantity( $campaign, $template_data, $campaign['id'] ); ?>
				<?php echo wp_kses_post( self::render_add_to_cart_button( $template_data, false, 'addToCartWrapper', $campaign_id, $campaign_type, '', $is_skip_add_to_cart ) ); ?>
			</div>

				<?php
		} else {
			?>
			<div>
				<div
					class="<?php echo esc_attr( self::get_element_class( $template_data, $footer_id ) ); ?> <?php echo $outer_footer ? 'revx-d-flex revx-flex-column' : ''; ?> <?php echo $is_bundle ? 'revx-w-full revx-justify-between' : ''; ?> revx-flex-wrap revx-d-flex"
				>
					<div class="revx-d-flex revx-item-center revx-gap-10">
					<?php if ( $is_mix_match ) { ?>
							<div class="revx-selected-container revx-scrollbar-common">
								<div class="revx-d-flex revx-item-center revx-gap-10">
									<?php
									foreach ( $selected_product as $product ) {
										$is_required             = 'yes' === $product['is_required'];
										$selected_regular_price += (float) $product['regularPrice'] * $product['quantity'];
										?>
										<div
											class="<?php echo esc_attr( self::get_element_class( $template_data, 'selectedProductCloseIcon' ) ); ?> revx-relative revx-lh-0"
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
												self::render_image(
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
											<?php echo wp_kses_post( self::render_rich_text( $template_data, 'selectedProductTitle', $product['productName'], 'revx-selected-title revx-ellipsis-1' ) ); ?>
											<div
												class="<?php echo esc_attr( self::get_element_class( $template_data, 'selectedProductPrice' ) ); ?> revx-d-flex revx-item-center"
											>
												<?php echo esc_html( $product['regularPrice'] ) . '$'; ?>
												<div class="revx-qty">(x <?php echo esc_html( $product['quantity'] ); ?>)</div>
											</div>
										</div>
									<?php } ?>
								</div>
							</div>
							<?php
					} else {
						echo wp_kses_post( self::render_rich_text( $template_data, 'totalText' ) );
						self::revenue_render_product_price(
							$price_array,
							'list',
							$template_data
						);
					}
					?>
					</div>
					<?php if ( $is_mix_match ) { ?>
						<div
							class="<?php echo esc_attr( self::get_element_class( $template_data, 'mixMatchFooterPricing' ) ); ?> revx-d-flex revx-item-center revx-justify-between revx-w-full"
						>
							<div class="revx-w-full">
								<?php echo wp_kses_post( self::render_rich_text( $template_data, 'totalPriceTitle' ) ); ?>
								<div
									class="<?php echo esc_attr( self::get_element_class( $template_data, 'mixMatchTotalPrice' ) ); ?> revx-d-flex revx-item-center revx-flex-wrap"
								>
									<?php if ( 'no_discount' !== $applied_offer['type'] ) { ?> 
										<del class="revx-product-old-price"><?php echo wp_kses_post( wc_price( $selected_regular_price ) ); ?></del><!-- Total Regular Price of selected product  -->
										<?php
										if ( 'no_discount' !== $applied_offer['type'] ) {
											?>
											<div><?php echo wp_kses_post( wc_price( self::calculated_offer_price( $selected_regular_price, $applied_offer['discount_value'], $applied_offer['type'], $applied_offer['quantity'] ) ) ); ?></div> 
										<?php } ?><!-- After applying discount un the total regular price  -->
									<?php } else { ?>
										<div><?php echo wp_kses_post( wc_price( $selected_regular_price ) ); ?></div>
									<?php } ?>
								</div>
							</div>
							<?php echo wp_kses_post( self::render_add_to_cart_button( $template_data, false, 'addToCartWrapper', '', '', '', $is_skip_add_to_cart ) ); ?>
						</div>
					<?php } ?>
				</div>
					<?php if ( ! $outer_footer ) { ?>
					<div
						class="<?php echo esc_attr( self::get_element_class( $template_data, 'bundleCartWrapper' ) ); ?> 
								revx-d-flex revx-parent-btn
								revx-item-center 
								revx-justify-<?php echo $is_center ? 'between' : 'center'; ?>"
								data-campaign-id="<?php echo esc_attr( $campaign['id'] ); ?>"
								data-quantity="<?php echo esc_attr( $offer_product['quantity'] ?? 1 ); ?>"
					>
						<?php self::revenue_render_product_quantity( $campaign, $template_data, $campaign['id'] ); ?>
						<?php echo wp_kses_post( self::render_add_to_cart_button( $template_data, false, 'addToCartWrapper', $campaign_id, $campaign_type, '', $is_skip_add_to_cart ) ); ?>
					</div>
				<?php } ?>
			</div>
				<?php
		}
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

		?>
	<div
		class="<?php echo esc_attr( self::get_element_class( $template_data, $footer_id ) ); ?> revx-d-flex revx-flex-column"
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
							class="<?php echo esc_attr( self::get_element_class( $template_data, 'selectedProductCloseIcon' ) ); ?> revx-relative revx-lh-0"
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
								self::render_image(
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
							<?php echo wp_kses_post( self::render_rich_text( $template_data, 'selectedProductTitle', $product['productName'], 'revx-selected-title revx-ellipsis-1' ) ); ?>
							<div
								class="<?php echo esc_attr( self::get_element_class( $template_data, 'selectedProductPrice' ) ); ?> revx-d-flex revx-item-center revx-selected-item__product-price"
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
						class="<?php echo esc_attr( self::get_element_class( $template_data, 'selectedProductCloseIcon' ) ); ?> revx-relative revx-lh-0"
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
							self::render_image(
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
						<?php echo wp_kses_post( self::render_rich_text( $template_data, 'selectedProductTitle', '', 'revx-selected-title revx-ellipsis-1' ) ); ?>
						<div
							class="<?php echo esc_attr( self::get_element_class( $template_data, 'selectedProductPrice' ) ); ?> revx-d-flex revx-item-center revx-selected-item__product-price"
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
			class="<?php echo esc_attr( self::get_element_class( $template_data, 'mixMatchFooterPricing' ) ); ?> revx-d-flex revx-item-center revx-justify-between revx-w-full"
		>
			<div class="revx-w-full revx-price-container">
				<?php echo wp_kses_post( self::render_rich_text( $template_data, 'totalPriceTitle' ) ); ?>
				<div
					class="<?php echo esc_attr( self::get_element_class( $template_data, 'mixMatchTotalPrice' ) ); ?> revx-d-flex revx-item-center revx-flex-wrap"
				>
					<?php
						$is_discount      = isset( $applied_offer['type'] ) && 'no_discount' !== $applied_offer['type'];
						$calculated_price = ! $is_discount
							? $selected_regular_price
							: self::calculated_offer_price( $selected_regular_price, $applied_offer['value'], $applied_offer['type'], $applied_offer['quantity'] );
					?>
					<del 
						class="revx-product-old-price <?php echo ! $is_discount ? 'revx-d-none' : ''; ?> "
					>
						<?php echo wp_kses_post( wc_price( $selected_regular_price ) ); ?>
					</del>
					<div class="revx-campaign-item__sale-price"><?php echo wp_kses_post( wc_price( $calculated_price ) ); ?></div>
				</div>
			</div>
			<div 
				class="<?php echo $selected_product_count < 1 ? 'revx-d-none' : ''; ?>"
				data-mix-match-reset-btn 
				style="text-wrap: nowrap; cursor:pointer; text-decoration:underline; color:#0073aa; font-size:14px; margin:0 8px;"
			>	
				Reset
			</div>
			<?php echo wp_kses_post( self::render_add_to_cart_button( $template_data, false, 'addToCartWrapper', $campaign_id, $campaign_type, '', $is_skip_add_to_cart ) ); ?>
		</div>
	</div>
		<?php
	}
	/**
	 * Render the main wrapper for product offers in a campaign.
	 *
	 * This function generates the HTML structure for the main wrapper that contains
	 * the header, product container, and footer sections of a campaign. It handles
	 * different display styles such as popup and floating, and applies appropriate
	 * CSS classes based on the campaign's placement settings.
	 *
	 * @since 2.0.0
	 *
	 * @param array             $campaign     The campaign data array. Must include 'id' and 'campaign_type'.
	 * @param string            $placement    The placement identifier (e.g., sidebar, inpage).
	 * @param bool              $is_variation Optional. Whether the product is a variation. Default false.
	 * @param string            $footer_id    Optional. The identifier for the footer element in the template. Default empty string.
	 * @param string            $element_id   Optional. The identifier for the wrapper element in the template. Default 'wrapper'.
	 * @param \WC_Product|false $product      Optional. The WooCommerce product object if available, or false. Default false.
	 *
	 * @return void Outputs HTML directly.
	 */
	public static function render_products_wrapper( $campaign, $placement, $is_variation = false, $footer_id = '', $element_id = 'wrapper', $product = false ) {

		$template_data        = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );
		$placement_settings   = revenue()->get_placement_settings( $campaign['id'] );
		$display_style        = isset( $placement_settings['display_style'] ) ? $placement_settings['display_style'] : 'inpage';
		$device_manager       = $template_data['campaign_visibility_enabled'] ?? array();
		$device_manager_class = '';
		$extra_class          = '';

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

		if ( 'popup' === $display_style ) {
			$extra_class = 'revx-popup-init-size';
		}
		if ( 'floating' === $display_style ) {
			$extra_class = 'revx-floating-init-size';
		}
		?>
		
		<div class="<?php echo esc_attr( self::get_element_class( $template_data, $element_id ) ); ?> <?php echo esc_attr( $display_style ); ?> <?php echo esc_attr( $device_manager_class ); ?> <?php echo esc_attr( $extra_class ); ?>">
	
			<?php self::render_wrapper_header( $campaign, $template_data ); ?>

			<?php self::render_products_container( $campaign, $template_data, $placement, $is_variation, false, $product ); ?>
				
			<?php if ( ! empty( $footer_id ) ) : ?>
				<?php self::render_products_footer( $campaign, $template_data, $footer_id ); ?>
			<?php endif; ?>
			
		</div>
		<?php
	}

	/**
	 * Render the product quantity.
	 *
	 * This function generates the HTML markup for a quantity selector input,
	 * including plus and minus buttons, if the quantity selector is enabled
	 * in the campaign settings. It applies appropriate CSS classes based on
	 * the template data and whether tags are enabled for the product.
	 *
	 * @since 1.0.0
	 *
	 * @param array $campaign      The campaign data array. Must include 'quantity_selector_enabled'.
	 * @param array $template_data The campaign template data used for rendering.
	 * @param int   $id            The unique identifier for the product or campaign.
	 * @param bool  $is_enable_tag Optional. Whether tags are enabled for the product. Default false.
	 * @param int   $min_quantity  Optional. The minimum quantity allowed. Default 1.
	 *
	 * @return void Outputs HTML directly if quantity selector is enabled.
	 */
	public static function revenue_render_product_quantity( $campaign = null, $template_data = null, $id = 0, $is_enable_tag = false, $min_quantity = 1 ) {
		$min_quantity = ! empty( $min_quantity ) && is_numeric( $min_quantity ) && $min_quantity > 0
		? (int) $min_quantity
		: 1;

		$is_rtl = revenue()->is_rtl();

		if ( 'yes' === $campaign['quantity_selector_enabled'] ) {
			?>
				<div
					class="<?php echo esc_attr( self::get_element_class( $template_data, 'quantitySelector' ) ); ?> <?php echo esc_attr( $is_enable_tag ? 'revx-tag-bg revx-tag-border revx-tag-text-color' : '' ); ?> revx-d-flex"
				>
					<div
						class="revx-campaign-icon revx-icon-left revx-quantity-minus"
						tabindex="0"
						role="button"
						style="
							display: flex;
							align-items: center;
							height: 100%;
							width: 100%;
							justify-content: center;
							cursor: pointer;
							transition: 0.3s;
							line-height: 0;
							border-<?php echo $is_rtl ? 'left' : 'right'; ?>: inherit;
						"
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
								d="M3.333 8h9.333"
							></path>
						</svg>
					</div>
					<input
						class="revx-product-input"
						type="number"
						value="<?php echo esc_attr( $min_quantity ); ?>"
						min="<?php echo esc_attr( $min_quantity ); ?>"
						id="product-qty-<?php echo esc_attr( $id ); ?>"
						data-product-id="<?php echo esc_attr( $id ); ?>"
						data-name="revx_quantity"
						style="
							width: 100%;
							height: auto;
							border: none;
							outline: none;
							padding: 0px 4px;
							margin: 0px;
							text-align: center;
							background-color: inherit;
							color: inherit;
							font-size: inherit;
							font-weight: inherit;
						"
					/>
					<div
						class="revx-campaign-icon revx-icon-right revx-quantity-plus"
						tabindex="0"
						role="button"
						style="
							display: flex;
							align-items: center;
							height: 100%;
							width: 100%;
							justify-content: center;
							cursor: pointer;
							transition: 0.3s;
							border-<?php echo $is_rtl ? 'right' : 'left'; ?>: inherit;
						"
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
								d="M8 3.334v9.333M3.333 8h9.333"
							></path>
						</svg>
					</div>
				</div>

			<?php
		}
	}
	/**
	 * Render the product cart area for a campaign product card.
	 *
	 * Outputs the quantity selector and the Add to Cart button (when applicable)
	 * for a given product within a campaign card. The button may be hidden for
	 * specific campaign types or when explicitly requested.
	 *
	 * @param array      $pd               Product data array.
	 * @param array|null $campaign         Campaign data array or null.
	 * @param array|null $template_data    Template data array or null.
	 * @param string     $element_id       Optional element id for the add-to-cart wrapper.
	 * @param bool       $no_wrap          If true, disables flex-wrap on the container.
	 * @param bool       $hide_add_to_cart If true, suppress rendering of the add-to-cart button.
	 * @param string     $layout           Layout mode ('list'|'grid').
	 * @return void
	 */
	public static function revenue_render_product_cart( $pd, $campaign = null, $template_data = null, $element_id = '', $no_wrap = false, $hide_add_to_cart = false, $layout = 'list' ) {
		if ( ! $pd || ! $campaign ) {
			return;
		}
		$campaign_type       = $campaign['campaign_type'];
		$campaign_id         = $campaign['id'];
		$is_skip_add_to_cart = 'yes' === $campaign['skip_add_to_cart'];
		$is_enable_tag       = isset( $pd['isEnableTag'] ) && 'yes' === $pd['isEnableTag'];
		$hide_cart           = 'buy_x_get_y' === $campaign['campaign_type'] || 'frequently_bought_together' === $campaign['campaign_type'] || $hide_add_to_cart;
		?>
		<div
			class="revx-product-cart-box <?php echo esc_attr( self::get_element_class( $template_data, 'productCartBox' ) ); ?> revx-d-flex revx-item-center <?php echo $no_wrap ? '' : 'revx-flex-wrap'; ?> <?php echo $layout === 'grid' ? 'revx-slider-center' : ''; ?>"
		>
			<?php self::revenue_render_product_quantity( $campaign, $template_data, $pd['id'], $is_enable_tag, $pd['quantity'] ); ?>
			<?php
			if ( ! $hide_cart ) {
				echo wp_kses_post( self::render_add_to_cart_button( $template_data, $is_enable_tag, $element_id, $campaign_id, $campaign_type, '', $is_skip_add_to_cart ) ); }
			?>
		</div>

		<?php
	}
	/**
	 * Render the Mix & Match product cart UI for a single product.
	 *
	 * Outputs the HTML markup for the product quantity selector and (optionally) the "Add to Cart" button
	 * within a Mix & Match campaign context. Handles display logic for campaign types that should not
	 * show the cart button (e.g., Buy X Get Y, Frequently Bought Together).
	 *
	 * @since 1.0.0
	 *
	 * @param array      $pd               Product data array. Must include 'id', 'quantity', and optionally 'isEnableTag'.
	 * @param array|null $campaign         The campaign data array. Must include 'id' and 'campaign_type'.
	 * @param array|null $template_data    The template data array for rendering classes and styles.
	 * @param string     $element_id       Optional. The element ID for the cart button. Default empty string.
	 * @param bool       $no_wrap          Optional. If true, disables flex-wrap on the container. Default false.
	 * @param bool       $hide_add_to_cart Optional. If true, hides the add to cart button. Default false.
	 *
	 * @return void Outputs HTML directly.
	 */
	public static function revenue_render_mix_match_product_cart( $pd, $campaign = null, $template_data = null, $element_id = '', $no_wrap = false, $hide_add_to_cart = false ) {
		if ( ! $pd || ! $campaign ) {
			return;
		}
		$campaign_type = $campaign['campaign_type'];
		$campaign_id   = $campaign['id'];
		$is_enable_tag = isset( $pd['isEnableTag'] ) && 'yes' === $pd['isEnableTag'];
		$hide_cart     = 'buy_x_get_y' === $campaign['campaign_type'] || 'frequently_bought_together' === $campaign['campaign_type'] || $hide_add_to_cart;

		$is_skip_add_to_cart = 'yes' === $campaign['skip_add_to_cart'];
		?>
		<div
			class="<?php echo esc_attr( self::get_element_class( $template_data, 'productCartBox' ) ); ?> revx-d-flex revx-item-center <?php echo $no_wrap ? '' : 'revx-flex-wrap'; ?>"
		>
		<?php self::revenue_render_product_quantity( $campaign, $template_data, $pd['id'], $is_enable_tag, $pd['quantity'] ); ?>
		<?php
		if ( ! $hide_cart ) {
			echo wp_kses_post( self::render_add_to_cart_button( $template_data, $is_enable_tag, $element_id, $campaign_id, $campaign_type, '', $is_skip_add_to_cart ) ); }
		?>
		</div>

		<?php
	}

	/**
	 * Render product price block.
	 *
	 * Outputs the regular, sale and offered price HTML for a product within a campaign card,
	 * converts prices based on WooCommerce tax display settings and optionally shows quantity multipliers.
	 *
	 * @param array|object $pd            Product data array containing price fields (regular_price, offered_price, sale_price, quantity, price_data, id).
	 * @param string       $layout        Layout mode ('grid'|'list').
	 * @param array|null   $template_data Template builder data or null.
	 * @param bool         $no_wrap       If true, disables flex-wrap on the container.
	 * @param bool         $no_badge      If true, suppresses rendering of the save badge.
	 * @param bool         $no_quantity   If true, hides the quantity multiplier when quantity > 1.
	 * @param string       $custom_class  Optional custom class for the outer container.
	 * @param bool         $only_regular  When true, only the regular price is displayed.
	 * @param string       $element_id    Element id key in template data to lookup classes (default 'productPriceContainer').
	 * @return void
	 */
	public static function revenue_render_product_price(
		$pd,
		$layout = 'grid',
		$template_data = null,
		$no_wrap = false,
		$no_badge = false,
		$no_quantity = false,
		$custom_class = '',
		$only_regular = false,
		$element_id = 'productPriceContainer'
	) {
		if ( ! $pd ) {
			return;
		}

		$product_id     = $pd['id'] ?? '';
		$is_enable_tag  = isset( $pd['isEnableTag'] ) && 'yes' === $pd['isEnableTag'];
		$is_list_layout = 'list' === $layout;
		// corner case of more decimal numbers in actual price than the display settings.
		// example: regular price 10.64583 and display decimal number up to 2 decimal.
		// in this case, regular price is still 10.64583 but displayed offer price is 10.65.
		// so, we need to compare the prices after rounding both prices to the display decimal number settings.
		$is_offer_price = isset( $pd['offered_price'] ) &&
			wc_format_decimal( $pd['regular_price'], '' ) !== wc_format_decimal( $pd['offered_price'], '' );
		$quantity       = (int) ( $pd['quantity'] ?? 1 );
		$regular_price  = $pd['regular_price'];
		$is_sale_price  = false;
		if ( isset( $pd['sale_price'] ) ) {
			$is_sale_price = '' !== $pd['sale_price'];
		}

		// GIVEN THE DISPLAYED PRICE WITH TAX OR NOT BASED ON WOOCOMMERCE SETTINGS.
		$_product    = ! empty( $product_id ) ? wc_get_product( $product_id ) : null;
		$tax_display = get_option( 'woocommerce_tax_display_shop', 'incl' );

		// normalize numeric prices.
		$regular_price = (float) $regular_price;
		$offered_price = null;
		$sale_price    = null;
		if ( isset( $pd['offered_price'] ) ) {
			$offered_price = (float) $pd['offered_price'];
		}
		if ( isset( $pd['sale_price'] ) ) {
			$sale_price = '' !== $pd['sale_price'] ? (float) $pd['sale_price'] : null;
		}

		// helper to convert price according to tax display mode when product is available.
		$convert_price = function ( $price ) use ( $_product, $tax_display ) {
			if ( null === $price ) {
				return null;
			}
			if ( $_product ) {
				if ( 'incl' === $tax_display ) {
					return wc_get_price_including_tax( $_product, array( 'price' => $price ) );
				}
				return wc_get_price_excluding_tax( $_product, array( 'price' => $price ) );
			}
			return $price;
		};

		$display_regular = $convert_price( $regular_price );
		$display_offered = $convert_price( $offered_price );
		$display_sale    = $convert_price( $sale_price );
		// echo '<pre>'; print_r($display_sale); echo '</pre>';
		// TODO: removed sale price. Only used regular and offer price.
		// focusing on only regular and offer price, breaks multiple place,
		// need careful checking to find if sale price is creating issue anywhere.
		// Render price HTML.
		// revx-product-regular-price, revx-product-sale-price, revx-product-offered-price are used by frontend scripts.
		$price_html = '<div class="revx-product-regular-price">' . wc_price( $display_regular ) . '</div>';
		if ( ! $only_regular ) {
			if ( $is_offer_price ) {
				// Compare underlying numeric prices to decide if offer equals regular.
				if ( isset( $pd['offered_price'] ) && (float) $pd['offered_price'] == (float) $pd['regular_price'] ) {
					$price_html = '<div class="revx-product-offered-price">' . wc_price( $display_offered ) . '</div>';
				} else {
					$price_html = '<del class="revx-product-old-price">' . wc_price( $display_regular ) . '</del>';
					// revx-product-offered-price is being used in jquery, do not remove.
					$price_html .= '<div class="revx-product-offered-price">' . wc_price( $display_offered ) . '</div>';
				}
			}
		}
		// else {
		// $price_html .= '<div>' . wc_price( $pd['offered_price'] ) . '</div>';
		// }
		if ( ! $no_quantity && $quantity > 1 ) {
			$price_html .= '<div class="revx-quantity-multiplier-' . esc_attr( $product_id ) . '">(x' . esc_html( $quantity ) . ')</div>';
		}
		?>
		<div class="revx-d-flex revx-item-center revx-gap-10 <?php echo $no_wrap ? '' : 'revx-flex-wrap'; ?> <?php echo $is_list_layout ? '' : 'revx-slider-center'; ?> <?php echo esc_attr( $custom_class ); ?>">
			<div class="
				<?php echo esc_attr( self::get_element_class( $template_data, $element_id ) ); ?> 
				revx-d-flex revx-item-center 
				<?php echo $no_wrap ? '' : 'revx-flex-wrap'; ?> 
				<?php echo $is_list_layout ? '' : 'revx-slider-center'; ?> 
				<?php echo $is_enable_tag ? 'revx-tag-text-color' : ''; ?>
			"
			>
				<?php echo wp_kses_post( $price_html ); ?>
			</div>
		<?php
		if ( ! $no_badge ) {
			self::render_save_badge( $template_data, $pd['price_data']['message'] ?? '', $is_enable_tag ? 'revx-tag-bg revx-tag-border revx-tag-text-color' : '', 'display: block; flex-shrink: 0' ); }
		?>
		</div>

		<?php
	}

	/**
	 * Render product variation selector/options for a product.
	 *
	 * Outputs variation select elements and embeds variation mapping data for use
	 * by front-end scripts. This function accepts a product data array ($pd),
	 * optional template data ($template_data) and layout mode ($layout).
	 *
	 * @param array      $pd            Product data array containing 'variations'.
	 * @param array|null $template_data Template data used for classes and styles.
	 * @param string     $layout        Layout mode, either 'list' or 'grid'. Default 'list'.
	 * @return void
	 */
	public static function revenue_render_product_variation( $pd, $template_data = null, $layout = 'list' ) {
		if ( ! $pd ) {
			return;
		}

		$product_id    = isset( $pd['variations'][0]['parent_id'] ) ? $pd['variations'][0]['parent_id'] : 0;
		$is_enable_tag = isset( $pd['isEnableTag'] ) && 'yes' === $pd['isEnableTag'];

		if ( empty( $pd['variations'] ) ) {
			return;
		}
		$variation_map = array();
		$attributes    = array();

		foreach ( $pd['variations'] as $variation ) {
			// Skip invalid variation entries without an item_id to avoid PHP warnings.
			if ( ! isset( $variation['item_id'] ) ) {
				continue;
			}
			/**
			 * A variation of the product.
			 *
			 * @var WC_Product_Variation $variation_product
			 */
			$variation_product = wc_get_product( $variation['item_id'] ?? 0 );
			if ( empty( $variation_product ) ) {
				continue;
			}
			$price = $variation_product->get_regular_price();
			if ( ! $variation_product->is_in_stock() || '' === $price || null === $price ) {
				continue;
			}
			$variation_attributes = array();
			if ( $variation_product && $variation_product->is_type( 'variation' ) ) {
				$variation_attributes = $variation_product->get_variation_attributes();
			}
			$variation_map[] = array(
				'regular_price' => $variation['regular_price'],
				'offered_price' => isset( $variation['offered_price'] ) ? $variation['offered_price'] : $variation['regular_price'],
				'saved_amount'  => isset( $variation['saved_amount'] ) ? $variation['saved_amount'] : 0,
				'sale_price'    => $variation['sale_price'],
				'variation_id'  => $variation['item_id'],
				'parent_id'     => $variation['parent_id'],
				'attributes'    => $variation_attributes,
			);
			if ( $variation_product && $variation_product->is_type( 'variation' ) ) {
				$variation_attributes = $variation_product->get_variation_attributes();
				// Merge variation attributes into the main attributes array.
				foreach ( $variation_attributes as $key => $value ) {
					if ( ! isset( $attributes[ $key ] ) ) {
						$attributes[ $key ] = array();
					}
					if ( ! empty( $value ) && ! in_array( $value, $attributes[ $key ], true ) ) {
						$attributes[ $key ][] = $value;
					}
				}
			}
		}

		if ( empty( $attributes ) ) {
			$attributes = $pd['variations']['attributes'];
		} else {
			foreach ( $attributes as &$values ) {
				$values = array_unique( $values );
			}
		}
		unset( $values );

		$clean_map = array();

		foreach ( $variation_map as $attr_json => $data ) {
			$attrs = json_decode( $attr_json, true );

			// Ensure both are arrays before merging.
			if ( ! is_array( $attrs ) ) {
				$attrs = array();
			}
			if ( ! is_array( $data ) ) {
				$data = array();
			}

			$clean_map[] = array_merge( $attrs, $data );
		}

		/**
		 * The parent product.
		 *
		 * @var WC_Product $parent
		 */
		$parent               = wc_get_product( $product_id );
		$variation_attributes = array();
		if ( $parent && $parent->is_type( 'variable' ) ) {

			$variation_attributes = $parent->get_variation_attributes();
		}

		// For Any Options Support.
		foreach ( $variation_attributes as $key => $value ) {
			// Normalize the key to lowercase.
			$attribute_key = 'attribute_' . sanitize_title( $key );

			// Add the attribute if it doesn't exist or if it exists but is empty.
			if ( ! isset( $attributes[ $attribute_key ] ) || empty( $attributes[ $attribute_key ] ) ) {
				$attributes[ $attribute_key ] = $value;
			}
		}
		?>
		<div
			class="<?php echo esc_attr( self::get_element_class( $template_data, 'productAttributeWrapper' ) ); ?> revx-d-flex revx-item-center revx-flex-wrap"
			data-product-id="<?php echo esc_attr( $product_id ); ?>"
			data-variation-map="<?php echo esc_attr( wp_json_encode( $clean_map ) ); ?>"
		>
		<?php
		// Reorder attribute options to match how WooCommerce stores/displays them.
		// This will try taxonomy order (via wc_get_product_terms) first, then
		// fallback to the product attribute string (pipe separated) if available.
		if ( ! empty( $attributes ) && ! empty( $parent ) ) {
			foreach ( $attributes as $attr_key => $opts ) {
				$raw   = strtolower( $attr_key );
				$clean = str_replace( array( 'attribute_', 'pa_' ), '', $raw );

				$ordered = array();
				$matched = false;

				// Candidate taxonomy names to try (e.g. 'pa_color', 'color').
				$candidates = array();
				if ( 0 === strpos( $raw, 'attribute_' ) ) {
					$candidates[] = substr( $raw, 10 ); // strip 'attribute_'.
				} else {
					$candidates[] = $raw;
				}
				$candidates[] = 'pa_' . $clean;
				$candidates[] = $clean;

				foreach ( $candidates as $tax ) {
					if ( taxonomy_exists( $tax ) ) {
						$terms = wc_get_product_terms( $product_id, $tax, array( 'fields' => 'names' ) );
						if ( ! empty( $terms ) ) {
							foreach ( $terms as $tname ) {
								foreach ( $opts as $opt ) {
									if ( 0 === strcasecmp( $opt, $tname ) ) {
										$ordered[] = $opt;
										break;
									}
								}
							}
							// append any options not matched in terms (preserve original order).
							foreach ( $opts as $opt ) {
								if ( ! in_array( $opt, $ordered, true ) ) {
									$ordered[] = $opt;
								}
							}
							$attributes[ $attr_key ] = array_values( array_unique( $ordered ) );
							$matched                 = true;
							break;
						}
					}
				}

				if ( ! $matched ) {
					// Fallback: use product's attribute string (e.g. "Blue | Green | Red").
					$attr_string = $parent->get_attribute( $clean );
					if ( $attr_string ) {
						$parts   = array_map( 'trim', explode( '|', $attr_string ) );
						$ordered = array();
						foreach ( $parts as $part ) {
							foreach ( $opts as $opt ) {
								if ( 0 === strcasecmp( $opt, $part ) ) {
									$ordered[] = $opt;
									break;
								}
							}
						}
						foreach ( $opts as $opt ) {
							if ( ! in_array( $opt, $ordered, true ) ) {
								$ordered[] = $opt;
							}
						}
						$attributes[ $attr_key ] = array_values( array_unique( $ordered ) );
					}
				}
			}
		}

		$product = wc_get_product( $product_id );

		if ( $product && $product->is_type( 'variable' ) ) {
			$default_attributes = $product->get_default_attributes();
		}

		if ( $product && $product->is_type( 'variable' ) ) {
			$default_attributes = $product->get_default_attributes();
		}
		foreach ( $attributes as $attr => $options ) :
			$attribute_name = strtolower( $attr );
			$attr_key       = str_replace( 'attribute_', '', $attr );
			if ( isset( $default_attributes ) && isset( $default_attributes[ $attr_key ] ) ) {
				$default_option = $default_attributes[ $attr_key ];
			}

			// remove any kind of prefixes before the actual attribute name. Add more if any case found.
			$prefixes = array( 'attribute_', 'pa_' );
			// use label either with default option name or seperate label tag. Convert to lower for consistency. Can be capitalized.
			$label = str_replace( $prefixes, '', strtolower( $attr ) );
			?>
				<div class="<?php echo esc_attr( self::get_element_class( $template_data, 'productAttributeField' ) ); ?> revx-relative revx-w-<?php echo esc_attr( 'grid' === $layout ? 'full' : 'fit' ); ?> revx-d-flex revx-item-center">
					<select class="revx-product-Attr-wrapper <?php echo esc_attr( $layout ); ?> <?php echo esc_attr( $is_enable_tag ? 'revx-tag-border revx-tag-bg revx-tag-text-color' : '' ); ?>" id="productAttributeSelect_<?php echo esc_attr( $product_id . '_' . $attr ); ?>"
						name="<?php echo esc_attr( $attribute_name ); ?>" 
						data-attribute_name="<?php echo esc_attr( $attribute_name ); ?>" 
						data-show_option_none="yes"
						data-options="<?php echo esc_attr( wp_json_encode( $options ) ); ?>" 
					>
						<option value="" ><?php echo esc_html__( 'Select', 'revenue' ); ?> <?php echo esc_html( $label ); ?></option>
						<?php foreach ( $options as $option ) :
							$option_value = $option;
							$option_label = $option;

							// Try to map option to a taxonomy term so we can
							// use the term slug as the value and the term name
							// as the visible label. Check common candidates
							// (pa_{attribute} and the raw attribute name).
							$tax_candidates = array( 'pa_' . $attr_key, $attr_key );
							foreach ( $tax_candidates as $tax ) {
								if ( taxonomy_exists( $tax ) ) {
									// Try by slug first (option might already be a slug).
									$term = get_term_by( 'slug', sanitize_title( $option ), $tax );
									if ( ! $term ) {
										// Fall back to matching by name.
										$term = get_term_by( 'name', $option, $tax );
									}
									if ( $term && ! is_wp_error( $term ) ) {
										$option_value = $term->slug;
										$option_label = $term->name;
										break;
									}
								}
							}

							// Ensure the selected check compares against the
							// actual option value that will be submitted.
							$is_selected = '';
							if ( isset( $default_option ) && ( $default_option == $option_value || $default_option == $option_label ) ) {
								$is_selected = 'selected';
							}
						?>
							<option 
								value="<?php echo esc_attr( $option_value ); ?>" 
								<?php echo $is_selected; ?>
							>
								<?php echo esc_html( $option_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<div class="revx-lh-0 revx-select-icon revx-absolute">
						<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 24 24">
							<path stroke="currentColor" d="m6 9 6 6 6-6" />
						</svg>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Render a checkbox element used in campaign product cards.
	 *
	 * Outputs a checkbox HTML fragment and returns it as a string. The checkbox
	 * can be rendered on the left, with optional tag styling, width for image,
	 * and required/checked states used by FBT (Frequently Bought Together) UI.
	 *
	 * @param array|string $template_data  Template data or configuration used for classes/text.
	 * @param bool         $is_left        Whether the checkbox appears on the left side. Default true.
	 * @param bool         $is_enable_tag  Whether tag styling is enabled. Default false.
	 * @param bool         $is_fbt_required Whether the FBT product is required. Default false.
	 * @param bool         $width_image    Whether to reserve width for an image. Default false.
	 * @param bool         $is_checked     Whether the checkbox should be rendered in checked/active state. Default false.
	 * @return string HTML for the checkbox wrapper.
	 */
	public static function render_checkbox( $template_data, $is_left = true, $is_enable_tag = false, $is_fbt_required = false, $width_image = false, $is_checked = false ) {
		ob_start();
		?>
		<div class="<?php echo $is_enable_tag ? 'revx-tag-text-color' : ''; ?> 
				<?php echo $is_left ? 'revx-checkbox-left' : ''; ?> 
				<?php echo $width_image ? 'revx-with-image' : ''; ?> 
				<?php echo $is_fbt_required ? 'revx-required-product' : ''; ?> revx-checkbox-wrapper">
			<div class="revx-checkbox-container revx-<?php echo ( $is_fbt_required || $is_checked ) ? 'active' : 'inactive'; ?>" style="font-size: 24px">
				<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 16 16">
					<rect width="11.8" height="11.8" x="2.102" y="2.1"
						stroke="var(--revx<?php echo $is_enable_tag ? '-tag' : ''; ?>-checkbox-bg-color,#000000)"
						rx="2.7"></rect>
					<rect width="11.4" height="11.4" x="2.502" y="2.5"
						fill="var(--revx<?php echo $is_enable_tag ? '-tag' : ''; ?>-checkbox-bg-color,#000000)"
						class="revx-checkbox-inactive"
						rx="2"></rect>
					<path stroke="var(--revx<?php echo $is_enable_tag ? '-tag' : ''; ?>-checkbox-icon-color,#fff)"
						stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"
						d="m11.4 5.9-4.2 4.2-2-2"
						class="revx-checkbox-inactive"></path>
				</svg>
			</div>
		</div>
		<?php
		return ob_get_clean();
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
	 * @param int        $product_id Product ID.
	 * @param array|null $offer      Optional offer array with 'type' and 'value'.
	 * @return string Escaped HTML attributes string.
	 */
	public static function get_data_price_attributes( $product_id, $offer ) {
		// @todo move function to different file.
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
		$tax_display = get_option( 'woocommerce_tax_display_shop', 'incl' );
		// @todo refactor to simpler variable checking code.
		$product_type = $product_->get_type();
		if ( 'variable' === $product_type ) {
			$children                = $product_->get_children();
			$variation_data          = array();
			$variation_regular_price = null;
			$variation_sale_price    = null;
			$variation_offered_price = null;
			$variation_first_regular = null;
			$variation_first_offer   = null;
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
				$variation_index_data    = array(
					'id'            => $child_id,
					'regular_price' => $variation_regular_price,
					'sale_price'    => $variation_sale_price,
					'attributes'    => $attributes,
					'image_url'     => $image_url,
				);
				// Extension Filter: Sale Price Addon.
				$filtered_price = apply_filters(
					'revenue_base_price_for_discount_filter',
					$variation_regular_price,
					$variation_sale_price
				);

				if ( $offer && isset( $offer['type'] ) && isset( $offer['value'] ) ) {
					$variation_index_data['offered_price'] = revenue()->calculate_campaign_offered_price(
						$offer['type'],
						$offer['value'],
						$filtered_price
					);
					$variation_index_data['saved_amount']  =
						floatval( $variation_regular_price ) - floatval( $variation_index_data['offered_price'] );
				}
				$variation_data[] = $variation_index_data;
				if ( ! $variation_first_regular ) {
					$variation_first_regular = $variation_regular_price;

					// use the filtered price as offered price if no offer is provided.
					// based on extension filter it can be sale price or regular price.
					$variation_first_offer = $variation_index_data['offered_price'] ?? $filtered_price;
				}
			}
			$data_price_attributes  = 'data-variations=\'' . esc_attr( wp_json_encode( $variation_data ) ) . '\'';
			$data_price_attributes .= ' data-regular-price="' . esc_attr( $variation_first_regular ) . '"';
			$data_price_attributes .= ' data-offered-price="' . esc_attr( $variation_first_offer ) . '"';
		} else {
			$regular_price = 'incl' === $tax_display
								? wc_get_price_including_tax( $product_, array( 'price' => $product_->get_regular_price() ) )
								: $product_->get_regular_price();
			$sale_price    = 'incl' === $tax_display
								? wc_get_price_including_tax( $product_, array( 'price' => $product_->get_sale_price() ) )
								: $product_->get_sale_price();

			// Extension Filter: Sale Price Addon.
			$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );
			// keeping both filtered price and offered price for clarity. and future use.
			$offered_price = $filtered_price;
			// Calculate offered price if offer is provided.
			if ( $offer && isset( $offer['type'] ) && isset( $offer['value'] ) ) {
				$offered_price = revenue()->calculate_campaign_offered_price(
					$offer['type'],
					$offer['value'],
					$filtered_price
				);
			}
			$data_price_attributes  = 'data-regular-price="' . esc_attr( $regular_price ) . '"';
			$data_price_attributes .= ' data-sale-price="' . esc_attr( $sale_price ) . '"';
			$data_price_attributes .= ' data-offered-price="' . esc_attr( $offered_price ) . '"';
		}
		return $data_price_attributes;
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
	 * Render a product card for an offer.
	 *
	 * Outputs or returns the HTML for a product card used by the Revenue plugin.
	 * The card can be rendered in different layouts (e.g. 'grid' or 'list'), and may
	 * be associated with a campaign or additional template data. Options are provided
	 * to hide the add-to-cart controls, customize the add-to-cart wrapper id, and
	 * to mark the product as a special "X" product or a trigger product.
	 *
	 * @param array      $offer         Offer data (e.g. pricing, offer-specific metadata).
	 * @param array      $pd            Product data array (e.g. post/product fields, title, image).
	 * @param string     $layout        Layout style for rendering the card. Expected values: 'grid', 'list'.
	 * @param mixed|null $campaign      Campaign identifier or object associated with the offer.
	 * @param mixed|null $template_data Optional template data used to influence rendering.
	 * @param bool       $hide_cart     When true, suppress rendering of cart/add-to-cart controls.
	 * @param string     $add_cart_id   HTML id attribute used for the add-to-cart wrapper element.
	 * @param int|string $product_index Optional index (numeric or string) when rendering multiple products.
	 * @param bool       $is_x_product  When true, marks the product as an "X" product (special handling).
	 * @param bool       $is_trigger    When true, marks the product as a trigger product (special handling).
	 *
	 * @return string|void HTML string of the rendered product card, or void if the function echoes output directly.
	 */
	public static function revenue_render_product_card( $offer = array(), $pd = array(), $layout = 'grid', $campaign = null, $template_data = null, $hide_cart = false, $add_cart_id = 'addToCartWrapper', $product_index = '', $is_x_product = false, $is_trigger = false ) {
		if ( ! $pd || ! $campaign ) {
			return;
		}

		$is_buy_x_get_y      = 'buy_x_get_y' === $campaign['campaign_type'];
		$is_mix_match        = 'mix_match' === $campaign['campaign_type'];
		$is_fbt              = 'frequently_bought_together' === $campaign['campaign_type'];
		$is_fsb              = 'free_shipping_bar' === $campaign['campaign_type'];
		$is_spg              = 'spending_goal' === $campaign['campaign_type'];
		$is_bundle           = 'bundle_discount' === $campaign['campaign_type'];
		$is_enable_tag       = isset( $pd['isEnableTag'] ) && 'yes' === $pd['isEnableTag'];
		$is_list_layout      = 'list' === $layout;
		$is_skip_add_to_cart = 'yes' === $campaign['skip_add_to_cart'];
		$is_go_to_product    = 'go_to_product' === $campaign['offered_product_click_action'];

		$template_two = $is_mix_match || $is_fsb || $is_spg || $is_fbt;
		$is_up_sell   = $is_fsb || $is_spg;

		$class       = self::get_element_class( $template_data, 'productLayout' );
		$extra_class = 'revx-d-flex';
		if ( ! $is_list_layout ) {
			$extra_class = 'revx-d-flex revx-flex-column revx-justify-between revx-slider-title-align revx-slider-product';
		} elseif ( $is_up_sell ) {
			$extra_class = 'revx-d-flex revx-item-center revx-slider-product revx-slider2-style';
		}
		$title_class = '';
		if ( $is_enable_tag ) {
			$title_class .= 'revx-tag-text-color';
		}
		if ( ! $is_list_layout ) {
			$title_class .= 'revx-ellipsis-2 revx-width-11rem';
		}
		if ( $is_up_sell ) {
			$title_class .= 'revx-ellipsis-1';
		}

		$product_title = $pd['title'] ?? '';
		$image_src     = $pd['image'][0] ?? '';

		// Render Image.
		$image_html = self::render_image(
			array(
				'src'   => $image_src,
				'alt'   => $product_title,
				'class' => self::get_element_class( $template_data, 'productImage' ),
			)
		);

		$product_id   = isset( $pd['id'] ) ? $pd['id'] : '';
		$product      = wc_get_product( $product_id );
		$product_type = $product->get_type();
		// -------------------------------------------------------//
		// Prepare data attributes for price and variations.
		$data_price_attributes = self::get_data_price_attributes( $product_id, $offer );
		// Do not remove this.
		// Asif
		// -------------------------------------------------------//.
		$product_quantity = $pd['quantity'] ?? 1;
		$campaign_id      = $campaign['id'];
		$campaign_type    = $campaign['campaign_type'];

		?>
			<div 
				data-product-id="<?php echo esc_attr( $product_id ); ?>"
				data-is-trigger="<?php echo esc_attr( $is_trigger ? 'yes' : 'no' ); ?>"
				data-variation-id="0"
				data-product-index="<?php echo esc_attr( $product_index ); ?>"  
				campaign_id="<?php echo esc_attr( $campaign_id ); ?>"   
				campaign_type="<?php echo esc_attr( $campaign_type ); ?>"   
				product_type="<?php echo esc_attr( $product_type ); ?>"   
				data-offer-item="item"
				data-product-qty="<?php echo esc_attr( $product_quantity ); ?>" 
				<?php echo wp_kses( $data_price_attributes, array() ); ?>
				class="<?php echo 'revx-' . esc_attr( $campaign_type ) . '-add-to-cart'; ?> <?php echo esc_attr( $class ); ?> revx-relative revx-product-layout <?php echo esc_attr( $extra_class ); ?> <?php echo $is_enable_tag ? 'revx-tag-bg revx-tag-border' : ''; ?> revx-campaign-product-card"
			>
				<?php
				if ( $is_enable_tag ) {
					echo wp_kses_post( self::render_tag( $template_data ) );
				}
				if ( $is_buy_x_get_y ) {
					echo wp_kses_post( self::render_tag( $template_data, 'saveTagWrapper', self::get_element_data( $template_data[ $is_x_product ? 'xProductTag' : 'yProductTag' ], 'text' ) ) );
				}
				?>

				<?php
				if ( 'grid' === $layout ) {
					echo '<div>';
				}
				?>
				<div class="revx-product-image <?php echo ( $is_up_sell ) ? 'revx-h-full' : ''; ?>">
					<?php
					if ( $is_fbt ) {
						$is_fbt_required = isset( $offer['is_fbt_required'] ) ? $offer['is_fbt_required'] : false;
						echo wp_kses_post( self::render_checkbox( $template_data, $is_list_layout, $is_enable_tag, $is_fbt_required, ! $is_list_layout ) );
					}
					if ( $is_go_to_product ) {
						echo '<a target="_blank" href="' . esc_url( get_permalink( $product_id ) ) . '">';
						echo wp_kses_post( $image_html );
						echo '</a>';
					} else {
						echo wp_kses_post( $image_html );
					}
					if ( $is_mix_match && 'grid' === $layout ) {
						echo wp_kses_post( self::render_add_to_cart_button( $template_data, $is_enable_tag, 'addProductWrapper', $campaign_id, $campaign_type, '', $is_skip_add_to_add_to_cart ?? $is_skip_add_to_cart ) );
					}
					?>
				</div>
				<?php
				if ( $is_up_sell ) {
					echo '<div class="revx-d-flex revx-justify-between revx-w-full revx-product-alignment">';
				}
				?>
				<div class="revx-w-full">
					<?php
					if ( $is_go_to_product ) {
						echo '<a target="_blank" href="' . esc_url( get_permalink( $product_id ) ) . '">';
						echo wp_kses_post(
							self::render_rich_text(
								$template_data,
								'productTitle',
								$product_title,
								$title_class,
								$is_up_sell ? 'max-width: 9rem' : '',
								'text',
								'',
								false,
								'',
								true,
							)
						);
						echo '</a>';
					} else {
						echo wp_kses_post(
							self::render_rich_text(
								$template_data,
								'productTitle',
								$product_title,
								$title_class,
								$is_up_sell ? 'max-width: 10rem' : ''
							)
						);
					}
					?>
					<?php
						self::revenue_render_product_price(
							$pd,
							$layout,
							$template_data,
							$is_bundle,
							$is_bundle || $is_mix_match || $is_up_sell,
							false,
							'',
							$is_mix_match
						);
					if ( $is_list_layout ) {
						?>
							<?php
								self::revenue_render_product_variation(
									$pd,
									$template_data,
									$layout
								);
							if ( ! $hide_cart && ! $template_two ) {
								self::revenue_render_product_cart(
									$pd,
									$campaign,
									$template_data,
									$add_cart_id,
								);
							}
							?>
						<?php
					} else {
						self::revenue_render_product_variation(
							$pd,
							$template_data,
							$layout,
						);
					}
					?>
				</div>
				<?php
				if ( 'grid' === $layout ) {
					echo '</div>';
				}

				if ( ! $hide_cart && ( 'grid' === $layout || $template_two ) ) {
					$class_layout  = 'revx-layout-secondary';
					$class_layout .= $is_up_sell ? ' revx-w-full' : '';
					if ( $template_two && ! ( 'grid' === $layout && $is_mix_match ) ) {
						echo '<div class="' . esc_attr( $class_layout ) . '">';
					}
					self::revenue_render_product_cart(
						$pd,
						$campaign,
						$template_data,
						$add_cart_id,
						false,
						$is_mix_match,
						$layout
					);
					if ( $template_two && ! ( 'grid' === $layout && $is_mix_match ) ) {
						echo '</div>';
					}
				}
				if ( $is_up_sell ) {
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

		$class       = self::get_element_class( $template_data, 'productLayout' );
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
		$image_html = self::render_image(
			array(
				'src'   => $image_src,
				'alt'   => $product_title,
				'class' => self::get_element_class( $template_data, 'productImage' ),
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
		$data_price_attributes = self::get_data_price_attributes( $product_id, $offer );
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
				<?php echo wp_kses( $data_price_attributes, array() ); ?>
				data-product-qty="<?php echo esc_attr( $product_quantity ); ?>" 
				class="<?php echo 'revx-' . esc_attr( $campaign_type ) . '-add-to-cart'; ?> <?php echo esc_attr( $class ); ?> revx-relative revx-product-layout <?php echo esc_attr( $extra_class ); ?> <?php echo $is_enable_tag ? 'revx-tag-bg revx-tag-border' : ''; ?> revx-campaign-product-card"
			>
				<?php
				if ( $is_enable_tag ) {
					echo wp_kses_post( self::render_tag( $template_data ) );
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
						$is_fbt_required = isset( $offer['is_fbt_required'] ) ? $offer['is_fbt_required'] : false;
						echo wp_kses( self::render_checkbox( $template_data, $is_list_layout, $is_enable_tag, $is_fbt_required, ! $is_list_layout ), revenue()->get_allowed_tag() );
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
							self::render_rich_text(
								$template_data,
								'productTitle',
								$product_title,
								$title_class
							)
						);
						echo '</a>';
					} else {
						echo wp_kses_post(
							self::render_rich_text(
								$template_data,
								'productTitle',
								$product_title,
								$title_class
							)
						);
					}
					?>
					<?php
						self::revenue_render_product_price(
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
								self::revenue_render_product_variation(
									$pd,
									$template_data,
									$layout
								);
							if ( ! $hide_cart && ! $template_two ) {
								self::revenue_render_product_cart(
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
						self::revenue_render_product_variation(
							$pd,
							$template_data,
							$layout
						);
						if ( ! $hide_cart && ! $template_two ) {
							self::revenue_render_product_cart(
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
					self::revenue_render_product_cart(
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

		$class       = self::get_element_class( $template_data, 'productLayout' );
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
		$image_html = self::render_image(
			array(
				'src'   => $image_src,
				'alt'   => $product_title,
				'class' => self::get_element_class( $template_data, 'productImage' ),
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
		// @todo refactor lines and remove extra sapces
		?>
			<div data-product-id="<?php echo esc_attr( $product_id ); ?>"
				date-variation-id="0"
				<?php echo wp_kses( $data_price_attributes, array() ); ?>
				data-product-index="<?php echo esc_attr( $product_index ); ?>"  
				campaign_id="<?php echo esc_attr( $campaign_id ); ?>"   
				campaign_type="<?php echo esc_attr( $campaign_type ); ?>"   
				product_type="<?php echo esc_attr( $product_type ); ?>"   
				data-product-qty="<?php echo esc_attr( $product_quantity ); ?>" class="<?php echo 'revx-' . esc_attr( $campaign_type ) . '-add-to-cart'; ?> <?php echo esc_attr( $class ); ?> revx-relative revx-product-layout <?php echo esc_attr( $extra_class ); ?> <?php echo $is_enable_tag ? 'revx-tag-bg revx-tag-border' : ''; ?> revx-campaign-product-card"
			>
				<?php
				if ( $is_enable_tag ) {
					echo wp_kses_post( self::render_tag( $template_data ) );
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
						echo wp_kses_post( self::render_add_to_cart_button( $template_data, $is_enable_tag, 'addProductWrapper', $campaign_id, $campaign_type, $layout, $is_skip_add_to_cart ) );
					}
					?>
				</div>
				<div class="revx-w-full">
					
					<?php
					if ( $is_go_to_product ) {
						echo '<a href="' . esc_url( get_permalink( $product_id ) ) . '" target="_blank">';
						echo wp_kses_post(
							self::render_rich_text(
								$template_data,
								'productTitle',
								$product_title,
								$title_class
							),
						);
						echo '</a>';
					} else {
						echo wp_kses_post(
							self::render_rich_text(
								$template_data,
								'productTitle',
								$product_title,
								$title_class
							)
						);
					}
					?>
					<?php
						self::revenue_render_product_price(
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
						self::revenue_render_product_variation(
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
					self::revenue_render_product_cart(
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

	/**
	 * Render a single "Buy X Get Y" product card within a campaign layout.
	 *
	 * This function outputs the HTML markup for a product card used in
	 * "Buy X Get Y" promotional campaigns. It supports both grid and list
	 * layouts, conditional rendering of product tags, images, titles, prices,
	 * variations, and add-to-cart buttons. Rendering behavior depends on
	 * the provided campaign type, template data, and product details.
	 *
	 * @since 1.0.0
	 *
	 * @param array      $offer          Optional. Offer-specific data for the product card. Default empty array.
	 * @param array      $pd             Product data array. Must include keys like 'id', 'title', 'image', and 'quantity'.
	 * @param string     $layout         Layout style. Accepts 'grid' or 'list'. Default 'grid'.
	 * @param array|null $campaign       Campaign configuration array. Must include 'id' and 'campaign_type'.
	 * @param array|null $template_data  Template customization data (CSS classes, labels, etc.). Default null.
	 * @param bool       $hide_cart      Whether to hide the Add to Cart button. Default false.
	 * @param string     $add_cart_id    Identifier for the Add to Cart wrapper. Default 'addToCartWrapper'.
	 * @param int|string $product_index  Index of the product in the current loop (used for DOM data attributes).
	 * @param bool       $is_x_product   Whether the product is an "X" product (qualifying item) in Buy X Get Y logic. Default false.
	 *
	 * @return void Outputs HTML directly. Returns nothing.
	 *
	 * @throws InvalidArgumentException If required $pd or $campaign data is missing.
	 *
	 * @see self::render_image()
	 * @see self::render_tag()
	 * @see self::revenue_render_product_price()
	 * @see self::revenue_render_product_variation()
	 * @see self::revenue_render_product_cart()
	 */
	public static function revenue_render_buy_x_get_y_product_card( $offer = array(), $pd = array(), $layout = 'grid', $campaign = null, $template_data = null, $hide_cart = false, $add_cart_id = 'addToCartWrapper', $product_index = '', $is_x_product = false ) {
		// @todo refactor this long line.
		if ( ! $pd || ! $campaign ) {
			return;
		}

		$template_two = 'free_shipping_bar' === $campaign['campaign_type'] || 'spending_goal' === $campaign['campaign_type'] || 'mix_match' === $campaign['campaign_type'];
		$is_up_sell   = 'free_shipping_bar' === $campaign['campaign_type'] || 'spending_goal' === $campaign['campaign_type'];

		$is_buy_x_get_y   = 'buy_x_get_y' === $campaign['campaign_type'];
		$is_enable_tag    = isset( $pd['isEnableTag'] ) && 'yes' === $pd['isEnableTag'];
		$is_list_layout   = 'list' === $layout;
		$is_go_to_product = 'go_to_product' === $campaign['offered_product_click_action'];

		$class       = self::get_element_class( $template_data, 'productLayout' );
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
		$image_html = self::render_image(
			array(
				'src'   => $image_src,
				'alt'   => $product_title,
				'class' => self::get_element_class( $template_data, 'productImage' ),
			)
		);

		$product_id   = isset( $pd['id'] ) ? $pd['id'] : '';
		$product_type = 'none';
		if ( $product_id ) {
			$product = wc_get_product( $product_id );
			if ( $product ) {
				$product_type = $product->get_type();
			}
		}
		$offer_value      = $offer['value'] ?? '';
		$offer_type       = $offer['type'] ?? '';
		$product_quantity = ! empty( $pd['quantity'] ) ? $pd['quantity'] : 1;
		$campaign_id      = $campaign['id'];
		$campaign_type    = $campaign['campaign_type'];
		$x_product_label  = $template_data['xProductTag']['text'];
		$x_product_label  = str_replace( '{qty}', $product_quantity, $x_product_label );
		$y_product_label  = $template_data['yProductTag']['text'];

		// -------------------------------------------------------//
		// Prepare data attributes for price and variations.
		$data_price_attributes = self::get_data_price_attributes( $product_id, $offer );
		// Do not remove this.
		// Asif
		// -------------------------------------------------------//.

		// Prefer regular/offered price values from the product data (`$pd`) when available.
		$pd_regular = isset( $pd['regular_price'] ) ? floatval( $pd['regular_price'] ) : null;
		$pd_offered = isset( $pd['offered_price'] ) ? floatval( $pd['offered_price'] ) : null;
		// @todo refactor later by changing the smart tag, but keep support for discount amount smart tag for backward compatibility.
		switch ( $offer_type ) {
			case 'percentage':
				if ( $pd_regular && $pd_offered && $pd_regular > 0 ) {
					$percent       = round( ( ( $pd_regular - $pd_offered ) / $pd_regular ) * 100 );
					$display_value = $percent . '%';
				} else {
					$display_value = $offer_value . '%';
				}
				break;

			case 'fixed_discount':
				// should be the difference between regular and offered price.
				// since based on filter offered price can be on sale price, so can not rely on offer value.
				if ( null !== $pd_regular && null !== $pd_offered ) {
					$discount      = $pd_regular - $pd_offered;
					$display_value = wc_price( $discount );
				} else {
					$display_value = wc_price( $offer_value );
				}
				break;

			case 'fixed_price':
				// should be the difference between regular and offered price.
				if ( null !== $pd_offered && null !== $pd_regular ) {
					$display_value = wc_price( $pd_regular - $pd_offered );
				} else {
					$display_value = wc_price( $offer_value );
				}
				break;

			case 'no_discount':
				$display_value   = __( 'No Discount', 'revenue' );
				$y_product_label = '';
				break;

			case 'free':
				$display_value   = __( 'Free', 'revenue' );
				$y_product_label = __( 'Free', 'revenue' );
				break;

			default:
				$display_value = $offer_value;
				break;
		}

		$y_product_label = str_replace( '{discount_amount}', $display_value, $y_product_label );

		$product_tag_to_render = '';
		if ( $is_buy_x_get_y ) {
			ob_start();
			if ( $is_x_product ) {
				echo wp_kses_post( self::render_tag( $template_data, 'saveTagWrapper', $x_product_label ) );
			} elseif ( $y_product_label ) {
				echo wp_kses_post( self::render_tag( $template_data, 'saveTagWrapper', $y_product_label ) );
			}
			$product_tag_to_render = ob_get_clean();
		}

		// @todo remove extra spaces from below.
		?>

		<div 
			data-is-x-product="<?php echo esc_attr( $is_x_product ? 'yes' : 'no' ); ?>"
			data-product-id="<?php echo esc_attr( $product_id ); ?>"
			data-variation-id="0"
			data-product-index="<?php echo esc_attr( $product_index ); ?>"  
			campaign_id="<?php echo esc_attr( $campaign_id ); ?>"   
			campaign_type="<?php echo esc_attr( $campaign_type ); ?>"   
			product_type="<?php echo esc_attr( $product_type ); ?>"   
			<?php echo wp_kses( $data_price_attributes, array() ); ?>
			data-product-qty="<?php echo esc_attr( $product_quantity ); ?>" 
			class="
				<?php echo 'revx-' . esc_attr( $campaign_type ) . '-add-to-cart'; ?> 
				<?php echo esc_attr( $class ); ?> 
				revx-relative revx-product-layout 
				<?php echo esc_attr( $extra_class ); ?> 
				<?php echo $is_enable_tag ? 'revx-tag-bg revx-tag-border' : 'revx-campaign-product-card'; ?>
			"
		>
		<?php
			// Escape buffered HTML output to satisfy WP Security: EscapeOutput rule.
			echo wp_kses_post( $product_tag_to_render );
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
				?>
			</div>
		<div class="revx-w-full">
		<?php
		if ( $is_go_to_product ) {
			echo '<a target="_blank" href="' . esc_url( get_permalink( $product_id ) ) . '">';
			echo wp_kses_post( self::render_rich_text( $template_data, 'productTitle', $product_title, $title_class ) );
			echo '</a>';
		} else {
			echo wp_kses_post( self::render_rich_text( $template_data, 'productTitle', $product_title, $title_class ) );
		}
		?>
		<?php
				self::revenue_render_product_price(
					$pd,
					$layout,
					$template_data,
					false,
					$is_buy_x_get_y,
					false,
					'',
					false
				);
		?>
				<?php
				if ( $is_buy_x_get_y && $is_list_layout ) {
					?>
					<div class="revx-d-flex revx-flex-wrap revx-w-full revx-justify-between">
						<?php
							self::revenue_render_product_variation(
								$pd,
								$template_data,
								$layout
							);
						if ( ! $hide_cart && ! $template_two ) {
							self::revenue_render_product_cart(
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
					self::revenue_render_product_variation(
						$pd,
						$template_data,
						$layout
					);
					if ( ! $hide_cart && ! $template_two && 'list' === $layout ) {
						self::revenue_render_product_cart(
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
				$class_layout .= $is_up_sell ? ' revx-w-full' : '';
				if ( $template_two ) {
					echo '<div class="' . esc_attr( $class_layout ) . '">';
				}
				self::revenue_render_product_cart(
					$pd,
					$campaign,
					$template_data,
					$add_cart_id,
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
	 * Retrieve a value from an element array by key.
	 *
	 * @param array|mixed $element Element array or value to read from.
	 * @param string      $key     Key to fetch from the element.
	 * @return mixed|string The value if set; otherwise an empty string.
	 */
	public static function get_element_data( $element, $key ) {
		if ( isset( $element[ $key ] ) ) {
			return $element[ $key ];
		}
		return '';
	}
	/**
	 * Get the CSS class name for a template element.
	 *
	 * Looks up the provided template data array for the given element identifier
	 * and returns its 'className' value if present.
	 *
	 * @param array|string|null $data       Template data array (or null/other types).
	 * @param string            $element_id Element identifier to look up in the template data.
	 * @return string The element className when found, otherwise an empty string.
	 */
	public static function get_element_class( $data, $element_id ) {

		if ( is_array( $data ) && isset( $data[ $element_id ], $data[ $element_id ]['className'] ) ) {
			return $data[ $element_id ]['className'];
		}
		return '';
	}

	/**
	 * Render shipping/free-shipping label and countdown wrapper.
	 *
	 * Accepts an optional args array to override default classes and inner
	 * elements and returns the rendered HTML string for the free-shipping label
	 * and countdown timer.
	 *
	 * @param array $args Optional. Configuration for wrapper, inner free_shipping and countdown blocks.
	 * @return string Rendered HTML for shipping/countdown wrapper.
	 */
	public static function render_shipping_countdown_wrapper( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'class' => 'revx-uuid-8571623',
				'inner' => array(
					'free_shipping' => array(
						'inner' => array(
							'free_shipping_icon'  => array(
								'type'  => 'icon',
								'svg'   => '',
								'class' => '',
							),
							'free_shipping_label' => array(
								'text'  => '',
								'class' => '',
								'type'  => 'rich_text',
							),
						),
						'class' => '',
					),
					'countdown'     => array(
						'inner' => array(
							'countdown_prefix' => array(
								'type'  => 'rich_text',
								'text'  => '',
								'class' => '',
							),
							'countdown_timer'  => array(
								'data'  => '',
								'type'  => 'countdown',
								'class' => '',
							),
						),
						'class' => '',
					),
				),
			)
		);

		ob_start();
		?>
			<div class="<?php echo esc_attr( $args['class'] ); ?>">
			<?php if ( ! empty( $args['inner']['free_shipping']['inner']['free_shipping_label']['text'] ) ) : ?>
					<div class="<?php echo esc_attr( $args['inner']['free_shipping']['class'] ); ?>">
						<?php if ( ! empty( $args['inner']['free_shipping']['inner']['free_shipping_icon']['svg'] ) ) : ?>
							<div class="<?php echo esc_attr( $args['inner']['free_shipping']['inner']['free_shipping_icon']['class'] ); ?>">
								<?php echo wp_kses_post( $args['inner']['free_shipping']['inner']['free_shipping_icon']['svg'] ); ?>
							</div>
						<?php endif; ?>

						<?php echo wp_kses_post( self::render_rich_text( $args['inner']['free_shipping']['inner']['free_shipping_label'], '' ) ); ?>
					</div>
				<?php endif; ?>

			<?php if ( ! empty( $args['inner']['countdown']['inner']['countdown_timer']['data'] ) ) : ?>
					<div class="<?php echo esc_attr( $args['inner']['countdown']['class'] ); ?>">
						<?php if ( ! empty( $args['inner']['countdown']['inner']['countdown_prefix']['text'] ) ) : ?>
							<?php echo wp_kses_post( self::render_rich_text( $args['inner']['countdown']['inner']['countdown_prefix'], '' ) ); ?>
						<?php endif; ?>

						<div class="<?php echo esc_attr( $args['inner']['countdown']['inner']['countdown_timer']['class'] ); ?>"
							data-countdown="<?php echo esc_attr( $args['inner']['countdown']['inner']['countdown_timer']['data'] ); ?>">
						</div>
					</div>
				<?php endif; ?>
			</div>
			<?php
			return ob_get_clean();
	}

	/**
	 * Render a heading wrapper.
	 *
	 * Outputs a simple heading block using the provided arguments.
	 *
	 * @param array $args {
	 *     Optional. Arguments for rendering the heading.
	 *
	 *     @type string $class         CSS class applied to the wrapper. Default 'revx-uuid-390579'.
	 *     @type string $text          Heading text to display. Default empty.
	 *     @type string $heading_class CSS class applied to the h2 element. Default 'revx-uuid-4744355'.
	 * }
	 * @return string Rendered HTML for the heading wrapper (empty string when no text).
	 */
	public static function render_heading_wrapper( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'class'         => 'revx-uuid-390579',
				'text'          => '',
				'heading_class' => 'revx-uuid-4744355',
			)
		);

		if ( empty( $args['text'] ) ) {
			return '';
		}

		ob_start();
		?>
			<div class="<?php echo esc_attr( $args['class'] ); ?>">
				<h2 class="<?php echo esc_attr( $args['heading_class'] ); ?>">
				<?php echo wp_kses_post( self::esc_invalid_markup( $args['text'] ) ); ?>
				</h2>
			</div>
			<?php
			return ob_get_clean();
	}

	/**
	 * Render a radio option element used in offers.
	 *
	 * @param array      $template      Template data array.
	 * @param float|int  $saved_amount  Saved amount value.
	 * @param string     $offer_type    Offer type identifier.
	 * @param mixed      $offer_value   Offer value (percentage, amount, etc.).
	 * @param bool       $selected      Whether the radio option is selected.
	 * @param string     $element_id    Element id in the template.
	 * @param string     $custom_class  Additional custom class(es) for the wrapper.
	 * @param string     $group         Radio group name.
	 * @param int|string $offer_qty     Offer quantity (optional).
	 *
	 * @return string Rendered HTML for the radio option.
	 */
	public static function render_radio( $template, $saved_amount, $offer_type, $offer_value, $selected = false, $element_id = 'radio', $custom_class = '', $group = 'default', $offer_qty = '', $offer_index = '' ) {
		ob_start();
		?>
				<div class="revx-radio-wrapper <?php echo esc_attr( self::get_element_class( $template, $element_id ) ); ?> <?php echo esc_attr( $custom_class ); ?> revx-<?php echo $selected ? 'active' : 'inactive'; ?>"
				data-radio-group="<?php echo esc_attr( $group ); ?>" data-offer-type="<?php echo esc_attr( $offer_type ); ?>" data-offer-value="<?php echo esc_attr( $offer_value ); ?>" data-saved-amount="<?php echo esc_attr( $saved_amount ); ?>" data-quantity="<?php echo esc_attr( $offer_qty ); ?>" data-offer-index="<?php echo esc_attr( $offer_index ); ?>"></div>
			<?php
			return ob_get_clean();
	}

	/**
	 * Render an image wrapper for a campaign/product.
	 *
	 * @param array $args {
	 *     Optional. Arguments to render the image.
	 *
	 *     @type string $src   Image source URL.
	 *     @type string $alt   Image alt text.
	 *     @type string $class CSS class to apply to the image wrapper.
	 * }
	 * @return string HTML markup for the image wrapper.
	 */
	public static function render_image( $args = array() ) {
		$args  = wp_parse_args(
			$args,
			array(
				'src'   => '',
				'alt'   => '',
				'class' => '',
			)
		);
		$class = $args['class'];
		$src   = $args['src'];
		$alt   = $args['alt'];

		ob_start();
		?>
			<div class="<?php echo esc_attr( $class ); ?> revx-campaign-item__image">
				<img src="<?php echo esc_url( $src ); ?>" alt="<?php echo esc_attr( $alt ); ?>" loading="lazy" style="border-radius: inherit; position: relative; max-width: 100%; max-height: 100%;object-fit: cover;width: 100%;height: 100%;aspect-ratio: 1 / 1;" />
			</div>
			<?php
			return ob_get_clean();
	}

	/**
	 * Render the "Add to Cart" button element from template data.
	 *
	 * Looks up the provided element in the template array, builds the appropriate
	 * class names (including tag and layout adjustments) and delegates actual
	 * button rendering to self::render_button().
	 *
	 * @param array|string $template            Template data array or string containing element definitions.
	 * @param bool         $is_enable_tag       Whether tag styling should be applied to the button.
	 * @param string       $element_id          Element id in the template to fetch classes/text from.
	 * @param string       $campaign_id         Optional campaign id used for data attributes.
	 * @param string       $campaign_type       Optional campaign type used for class names.
	 * @param string       $layoput             Layout (note: original parameter name preserved).
	 * @param bool         $is_skip_add_to_cart Whether to add skip-add-to-cart class when true.
	 * @return string|void Rendered HTML for the button or void when element missing.
	 */
	public static function render_add_to_cart_button( $template, $is_enable_tag = false, $element_id = 'addToCartWrapper', $campaign_id = '', $campaign_type = '', $layoput = 'list', $is_skip_add_to_cart = false ) {
		if ( is_array( $template ) && isset( $template[ $element_id ] ) ) {

			$class = self::get_element_class( $template, $element_id );
			if ( $is_enable_tag ) {
				$class .= ' revx-tag-btn-style';
			}
			if ( 'addProductWrapper' === $element_id && 'grid' === $layoput ) {
				$class .= ' revx-add-product-btn';
			}
			if ( 'mix_match' === $campaign_type ) {
				$class .= ' revx-mix-match-product-btn';
			}

			if ( $is_skip_add_to_cart ) {
				$class .= ' revx-skip-add-to-cart';
			}

			$text = $template[ $element_id ]['text'];

			return self::render_button(
				$template,
				$element_id,
				array(
					'class' => $class,
					'text'  => $text,
				),
				'',
				'',
				$campaign_id,
				$campaign_type
			);
		}
	}


	/**
	 * Render the "Add to Cart" button for volume discount offers.
	 *
	 * This helper looks up the provided element in the template data, applies
	 * tag styling when requested, replaces placeholder tokens in the button
	 * text (like {qty} and {save_amount}) and delegates to render_button().
	 *
	 * @param array  $template       Template data array.
	 * @param bool   $is_enable_tag  Whether tag styling should be applied to the button.
	 * @param string $element_id     Element id in the template to fetch classes/text from.
	 * @param string $campaign_id    Optional campaign id used for data attributes.
	 * @param string $campaign_type  Optional campaign type used for class names.
	 * @param mixed  $quantities     Quantity placeholder replacement value.
	 * @param mixed  $save_data      Save amount placeholder replacement value.
	 * @param bool   $is_skip_add_to_cart Whether to add skip-add-to-cart class when true for direct checkout.
	 *
	 * @return string|void Rendered HTML for the button or void when element missing.
	 */
	public static function render_volume_discount_add_to_cart_button(
		$template,
		$is_enable_tag = false,
		$element_id = 'addToCartWrapper',
		$campaign_id = '',
		$campaign_type = '',
		$quantities = '',
		$save_data = '',
		$is_skip_add_to_cart = false
	) {
		if ( is_array( $template ) && isset( $template[ $element_id ] ) ) {

			$class = self::get_element_class( $template, $element_id );
			if ( $is_enable_tag ) {
				$class .= ' revx-tag-btn-style';
			}

			if ( $is_skip_add_to_cart ) {
				$class .= ' revx-skip-add-to-cart';
			}

			$text       = $template[ $element_id ]['text'];
			$final_text = str_replace(
				array( '{qty}', '{save_amount}' ),
				array( $quantities, $save_data ),
				$text
			);

			return self::render_button(
				$template,
				$element_id,
				array(
					'class' => $class,
					'text'  => $final_text,
				),
				'',
				'',
				$campaign_id,
				$campaign_type
			);
		}
	}

	/**
	 * Render a link using a template element as a button.
	 *
	 * This method looks up the provided element in the template data,
	 * extracts its class and text, and delegates rendering to render_button()
	 * wrapping the output in an anchor pointing to the provided URL.
	 *
	 * @param array  $template   Template data array.
	 * @param string $element_id Element id in the template to fetch classes/text from.
	 * @param string $link       URL the link should point to.
	 * @param string $target     Link target attribute. Default '_self'.
	 *
	 * @return string|void Returns rendered HTML string when element exists, otherwise void.
	 */
	public static function render_link( $template, $element_id, $link, $target = '_self' ) {
		if ( is_array( $template ) && isset( $template[ $element_id ] ) ) {
			$class = self::get_element_class( $template, $element_id );
			$text  = self::get_element_data( $template[ $element_id ], 'text' ) ?? '';

			return self::render_button(
				$template,
				$element_id,
				array(
					'class' => $class,
					'text'  => $text,
				),
				$link,
				$target,
				'',
				''
			);
		}
	}

	/**
	 * Render a button element from template data.
	 *
	 * Generates the button HTML using provided template data and arguments.
	 *
	 * @param array  $data          Template data array containing element definitions.
	 * @param string $element_id    Element identifier in the template data.
	 * @param array  $args          Arguments for rendering the button (class, text, animation, etc.).
	 * @param string $url           Optional URL to wrap the button (defaults to empty).
	 * @param string $target        Link target attribute (defaults to '_self').
	 * @param string $campaign_id   Optional campaign id used for data attributes.
	 * @param string $campaign_type Optional campaign type used for class names.
	 * @return string Rendered HTML for the button.
	 */
	public static function render_button( $data, $element_id, $args = array(), $url = '', $target = '_self', $campaign_id = '', $campaign_type = '' ) {
		$defaults = array(
			'class'                     => '',
			'text'                      => '',
			'animation_type'            => '',
			'animate_on_hover'          => false,
			'animation_iteration_delay' => 0,
		);

		$args = wp_parse_args( $args, $defaults );

		$current_campaign          = revenue()->get_campaign_data( $campaign_id );
		$is_animated_atc_enabled   = isset( $current_campaign['animated_add_to_cart_enabled'] ) && 'yes' == $current_campaign['animated_add_to_cart_enabled'];
		$animated_atc_trigger_type = isset( $current_campaign['add_to_cart_animation_trigger_type'] ) ? sanitize_text_field( $current_campaign['add_to_cart_animation_trigger_type'] ) : '';
		$animated_type             = isset( $current_campaign['add_to_cart_animation_type'] ) ? sanitize_text_field( $current_campaign['add_to_cart_animation_type'] ) : '';
		$animation_delay           = isset( $current_campaign['add_to_cart_animation_start_delay'] ) ? sanitize_text_field( $current_campaign['add_to_cart_animation_start_delay'] ) : '0.8s';
		$animation_base_class      = $is_animated_atc_enabled ? 'revx-btn-animation ' : '';
		$animation_class           = '';

		// Build animation classes.
		$animation_class = '';
		if ( $is_animated_atc_enabled ) {
			$animation_class .= ' revx-btn-animation';

			switch ( $animated_type ) {
				case 'wobble':
					$animation_class .= ' revx-btn-wobble';
					break;
				case 'shake':
					$animation_class .= ' revx-btn-shake';
					break;
				case 'pulse':
					$animation_class .= ' revx-btn-pulse';
					break;
				case 'zoom':
					$animation_class .= ' revx-btn-zoomIn';
					break;
			}
		}

		$animation_base_class = 'loop' === $animated_atc_trigger_type ? "$animation_base_class $animation_class" : $animation_base_class;

		// Add hover class if animation should only trigger on hover.
		if ( 'on_hover' === $animated_atc_trigger_type ) {
			$animation_class .= ' revx-animate-on-hover';
		}

		$button_class = esc_attr( $args['class'] ) . $animation_class;

		// Add inline style for animation iteration delay if needed.
		$animation_style = '';

		ob_start();
		if ( $url ) {
			echo '<a href="' . esc_url( $url ) . '" class="revx-default-link" target="' . esc_attr( $target ) . '" rel="noopener noreferrer">';
		}
		?>
			<div class="
			<?php
			echo 'addToCartWrapper' === $element_id
			? 'revx-' . esc_attr( $campaign_type ) . '-btn'
			: '';
			?>
			<?php echo esc_attr( $button_class ); ?>" <?php echo ! empty( $animation_style ) ? 'style="' . esc_attr( $animation_style ) . '"' : ''; ?>
				data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>"
				data-campaign-type="<?php echo esc_attr( $campaign_type ); ?>"
				data-animation-delay="<?php echo esc_attr( $animation_delay ); ?>"
				data-animated-btn-enabled ="<?php echo esc_attr( $is_animated_atc_enabled ? 'yes' : 'no' ); ?>"
				data-animated-triggered-type = "<?php echo esc_attr( $animated_atc_trigger_type ); ?>"
				>
				<?php

				if ( ! is_array( $data ) || ! $element_id ) {
					return;
				}

				$element      = isset( $data[ $element_id ] ) ? $data[ $element_id ] : array();
				$text_element = isset( $data[ $element_id . 'RichText' ] ) ? $data[ $element_id . 'RichText' ] : array();

				$class = self::get_element_data( $element, 'className' );

				$text = $args['text'] ? $args['text'] : self::get_element_data( $text_element, 'text' );

				$text = apply_filters( 'revenue_apply_rich_text_smart_tags', $text, $element );
				echo wp_kses_post( self::esc_invalid_markup( $text ) );
				?>
			</div>
			<?php
			if ( $url ) {
				echo '</a>';
			}
			return ob_get_clean();
	}

	/**
	 * Render the close button HTML used in campaign containers.
	 *
	 * @param array  $data       Template data array containing element definitions.
	 * @param string $element_id Element id in the template to fetch classes from.
	 * @return string HTML markup for the close button.
	 */
	public static function render_button_close( $data, $element_id ) {
		$class = esc_attr( self::get_element_class( $data, $element_id ) );

		return '
			<div
				class="' . $class . ' revx-close-icon"
			>
				<svg
					xmlns="http://www.w3.org/2000/svg"
					width="100%"
					height="100%"
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
			</div>';
	}

	/**
	 * Render a rich text element from template data.
	 *
	 * Generates a div containing rich text pulled from the template data or
	 * from the provided $text parameter, with optional classes, inline styles,
	 * and an optional tooltip/title attribute.
	 *
	 * @param array  $data           Template data array.
	 * @param string $element_id     Element identifier in the template data.
	 * @param string $text           Optional text to render; falls back to template element text.
	 * @param string $custom_class    Optional additional CSS class(es) to apply.
	 * @param string $custom_style    Optional inline style to apply.
	 * @param string $key_text       Key name used to fetch text from the element (default 'text').
	 * @param string $element_class  Optional element class key in $data to inherit className from.
	 * @param bool   $inherit_parent Whether to inherit parent class (default false).
	 * @param string $element_text   Optional override element text key.
	 * @param bool   $tooltip        Whether to add a title attribute for tooltip (default false).
	 *
	 * @return string|null Rendered HTML string or null on invalid input.
	 */
	public static function render_rich_text(
		$data,
		$element_id = '',
		$text = '',
		$custom_class = '',
		$custom_style = '',
		$key_text = 'text',
		$element_class = '',
		$inherit_parent = false,
		$element_text = '',
		$tooltip = false
	) {
		if ( ! is_array( $data ) || ! $element_id ) {
			return;
		}

		$element = $data[ $element_id ] ?? array();

		// Determine text content.
		if ( ! $text ) {
			$text = self::get_element_data( $element, $key_text );
		}

		if ( ! empty( $element_text ) && isset( $data[ $element_text ][ $key_text ] ) ) {
			$text = $data[ $element_text ][ $key_text ];
		}

		$text = apply_filters( 'revenue_apply_rich_text_smart_tags', $text, $element );

		// Determine class name.
		$class = '';
		if ( ! $inherit_parent ) {
			if ( ! empty( $element_class ) && isset( $data[ $element_class ] ) ) {
				$class = self::get_element_data( $data[ $element_class ], 'className' );
			} else {
				$class = self::get_element_data( $element, 'className' );
			}
		}

		ob_start();
		?>
			<div 
				data-smart-tag="<?php echo esc_attr( $element_id ); ?>"
				data-smart-tag-text="<?php echo esc_attr( self::get_element_data( $element, $key_text ) ); ?>"
				class="<?php echo esc_attr( trim( $class . ' ' . $custom_class ) ); ?>" 
				style="<?php echo esc_attr( $custom_style ); ?>" 
				<?php echo $tooltip ? 'title="' . esc_attr( $text ) . '"' : ''; ?>
			>
				<?php echo wp_kses_post( self::esc_invalid_markup( $text ) ); ?>
			</div>
			<?php
			return ob_get_clean();
	}

	/**
	 * Render a normal text element from template data.
	 *
	 * Generates a simple div containing text pulled from the provided template
	 * data or from the optional $text parameter, and applies an optional CSS class.
	 *
	 * @param array  $data         Template data array.
	 * @param string $element_id   Element identifier in the template data.
	 * @param string $text         Optional text to render; falls back to template element text.
	 * @param string $custom_class Optional additional CSS class(es) to apply.
	 * @return string|null Rendered HTML markup as a string, or null on invalid input.
	 */
	public static function render_normal_text( $data, $element_id, $text = '', $custom_class = '' ) {

		if ( ! is_array( $data ) || ! $element_id ) {
			return;
		}

		$element = isset( $data[ $element_id ] ) ? $data[ $element_id ] : array();

		$class = self::get_element_data( $element, 'className' );

		$text = $text ? $text : self::get_element_data( $element, 'text' );

		ob_start();
		?>
			<div class="<?php echo esc_attr( $class ); ?> <?php echo esc_attr( $custom_class ); ?>"><?php echo wp_kses_post( self::esc_invalid_markup( $text ) ); ?></div>
			<?php
			return ob_get_clean();
	}

	/**
	 * Render the save badge element.
	 *
	 * Outputs the save badge rich text for the provided template element when a message is present.
	 *
	 * @param array  $template_data Template builder data array.
	 * @param string $message       Message to display; if set to 'empty' an empty badge will be rendered.
	 * @param string $custom_class  Optional additional CSS class for the badge.
	 * @param string $custom_style  Optional inline style for the badge.
	 * @param string $element_id    Template element id to use (default 'saveBadgeWrapper').
	 * @return void
	 */
	public static function render_save_badge( $template_data, $message, $custom_class = '', $custom_style = '', $element_id = 'saveBadgeWrapper' ) {
		if ( ! $message ) {
			return;
		}
		// Escape the output using wp_kses_post to allow safe HTML while satisfying WP coding standards.
		echo wp_kses_post( self::render_rich_text( $template_data, $element_id, 'empty' === $message ? '' : $message, $custom_class, $custom_style ) );
	}

		/**
		 * Renders a quantity selector input with increment/decrement buttons
		 *
		 * @param array $args Options for the quantity selector.
		 * @return string HTML markup for the quantity selector
		 */
	public static function render_quantity_selector( $args = array() ) {
		$defaults = array(
			'class'       => '',
			'value'       => 1,
			'min'         => 1,
			'max'         => 100,
			'product_id'  => 0,
			'campaign_id' => 0,
			'enabled'     => true,
		);

		$args = wp_parse_args( $args, $defaults );

		$input_name = 'revx-quantity-' . $args['campaign_id'] . '-' . $args['product_id'];

		// Start output buffering.
		ob_start();
		?>
			<div class="revx-builder__quantity <?php echo esc_attr( $args['class'] ); ?> ">
				<div class="revx-quantity-minus" style="display: flex; align-items: center; height: 100%; width: 100%; justify-content:center; border-right: inherit; cursor: pointer; transition: 0.3s;">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M3.33301 8H12.6663" stroke="currentColor" strokeWidth="1.2" strokeLinecap="round" strokeLinejoin="round"/>
					</svg>
				</div>
				<input
					data-name="revx_quantity"
					max="<?php echo esc_attr( $args['max'] ); ?>"
					type="number"
					min="<?php echo esc_attr( $args['min'] ); ?>"
					data-product-id="<?php echo esc_attr( $args['product_id'] ); ?>"
					data-campaign-id="<?php echo esc_attr( $args['campaign_id'] ); ?>"
					name="<?php echo esc_attr( $input_name ); ?>"
					style="width: 100%; border: none; outline: none; padding: 0px 4px 0px 4px; margin: 0; text-align: center;"
					value="<?php echo esc_attr( $args['value'] ); ?>"
				/>
				<div class="revx-quantity-plus" style="display: flex; align-items: center; height: 100%; width: 100%; justify-content:center; border-left: inherit; cursor: pointer; transition: 0.3s;">
					<svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
						<path d="M8 3.33398V12.6673" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
						<path d="M3.33301 8H12.6663" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
					</svg>
				</div>
			</div>
			<?php

			return ob_get_clean();
	}

	/**
	 * Render a simple container wrapper.
	 *
	 * Accepts an array of optional arguments to render a DIV wrapper and returns the
	 * rendered HTML string.
	 *
	 * @param array $args {
	 *     Optional. Arguments for the container.
	 *
	 *     @type string $class    CSS class(es) to apply to the container. Default ''.
	 *     @type string $children Inner HTML content to place inside the container. Default ''.
	 *     @type string $style    Optional inline style attribute. Default ''.
	 * }
	 * @return string Rendered HTML for the container.
	 */
	public static function render_container( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'class'    => '',
				'children' => '',
				'style'    => '',
			)
		);

		ob_start();
		?>
			<div class="<?php echo esc_attr( $args['class'] ); ?>" <?php echo ! empty( $args['style'] ) ? 'style="' . esc_attr( $args['style'] ) . '"' : ''; ?>>
			<?php echo wp_kses_post( $args['children'] ); ?>
			</div>
			<?php
			return ob_get_clean();
	}

	/**
	 * Render a tag element from template data.
	 *
	 * This is a small helper that delegates to render_rich_text() to output
	 * a tag wrapper from the template builder data.
	 *
	 * @param array|string $template_data Template data array or string.
	 * @param string       $element_id    Element id in the template (default 'tagWrapper').
	 * @param string       $text          Optional override text to render.
	 * @return string|null Rendered HTML string or null when input is invalid.
	 */
	public static function render_tag( $template_data, $element_id = 'tagWrapper', $text = '' ) {
		return self::render_rich_text( $template_data, $element_id, $text );
	}

	/**
	 * Basic sanitization for output that may contain markup.
	 *
	 * NOTE: This implementation currently returns the input unchanged to preserve
	 * existing behaviour; replace with a proper sanitization routine (for example
	 * wp_kses_post() or a configured wp_kses() call) when you need to allow safe HTML.
	 *
	 * @param string $text Text to sanitize.
	 * @return string Sanitized text (currently the original input).
	 */
	private static function esc_invalid_markup( $text = 'This is dummy text' ) {
		// Keep current behaviour: return text as-is; switch to proper escaping (wp_kses_post or similar) when required.
		if ( ! is_string( $text ) ) {
			return '';
		}
		return $text;
	}

	/**
	 * Build and return global typography CSS variables.
	 *
	 * Iterates over the stored typography settings and generates CSS custom
	 * properties for each style and device breakpoint. Uses container queries
	 * for non-mobile breakpoints and returns the resulting CSS string.
	 *
	 * @return string CSS variables and container-query blocks.
	 */
	public static function get_global_typography() {

		$typography_data = revenue()->get_setting( 'typography' );

		$output = '';

		// Define breakpoints for container queries (in px).
		$breakpoints = array(
			'sm' => 0,     // Mobile: < 390px.
			'md' => 390,   // Tablet: >= 390px and < 508px.
			'lg' => 508,    // Desktop: >= 508px.
		);

		foreach ( $typography_data as $style_name => $style ) {

			foreach ( $breakpoints as $device => $min_width ) {

				$device_styles = $style[ $device ] ?? $style['desktop'];

				if ( $device_styles ) {

					if ( 0 === $min_width ) {
						$output .= ":root {\n";
					} else {
						$output .= "@container revenue-campaign (min-width: {$min_width}px) {\n";
						$output .= "  :root {\n";
					}

					foreach ( $device_styles as $property => $value ) {
						$kebab_property = strtolower( preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', $property ) );
						$css_value      = 'fontSize' === $property ? "{$value}px" : $value;

						$output .= "    --revx-{$style_name}-{$kebab_property}: {$css_value};\n";
					}

					$output .= 0 === $min_width ? "}\n" : "  }\n}\n";
				}
			}
		}

		return $output;
	}

	/**
	 * Render free shipping and countdown wrapper for a campaign.
	 *
	 * Outputs the free shipping label and countdown timer markup when enabled
	 * via campaign meta flags 'free_shipping_enabled' or 'countdown_timer_enabled'.
	 *
	 * @param int|string $campaign_id   Campaign identifier.
	 * @param array      $template_data Template builder data used when rendering.
	 * @return void
	 */
	public static function render_free_shipping_countdown( $campaign_id, $template_data ) {

		$is_free_shipping_enable    = revenue()->get_campaign_meta( $campaign_id, 'free_shipping_enabled', 'no' ) === 'yes';
		$is_countdown_timer_enable  = revenue()->get_campaign_meta( $campaign_id, 'countdown_timer_enabled', 'no' ) === 'yes';
		$is_countdown_timer_visible = $is_countdown_timer_enable;

		if ( $is_countdown_timer_enable ) {
			// Fetch meta values.
			$end_date             = revenue()->get_campaign_meta( $campaign_id, 'countdown_end_date', true );
			$end_time             = revenue()->get_campaign_meta( $campaign_id, 'countdown_end_time', true );
			$start_timestamp      = null;
			$have_start_date_time = ( 'schedule_to_later' === revenue()->get_campaign_meta( $campaign_id, 'countdown_start_time_status', true ) );
			if ( $have_start_date_time ) {
				$start_date = revenue()->get_campaign_meta( $campaign_id, 'countdown_start_date', true );
				$start_time = revenue()->get_campaign_meta( $campaign_id, 'countdown_start_time', true );

				if ( ! empty( $start_date ) && ! empty( $start_time ) ) {
					$start_datetime  = $start_date . ' ' . $start_time;
					$start_timestamp = strtotime( $start_datetime ) * 1000; // in milliseconds.
				}
			}

			$end_datetime            = $end_date . ' ' . $end_time;
			$end_timestamp           = strtotime( $end_datetime ) * 1000; // in milliseconds.
			$cur_timestamp_universal = time() * 1000; // in milliseconds, universal time.
			$cur_timestamp           = current_time( 'timestamp' ) * 1000; // in milliseconds, site local time.

			if ( $cur_timestamp > $end_timestamp || ( $start_timestamp && $start_timestamp > $cur_timestamp ) ) {
				$is_countdown_timer_visible = false;
			}
		}

		$is_any_enable = $is_free_shipping_enable || ( $is_countdown_timer_enable && $is_countdown_timer_visible );

		if ( $is_any_enable ) {
			echo '<div class="revx-free-shipping-countdown-wrapper">';
		}

		if ( $is_free_shipping_enable ) {
			self::render_free_shipping( $template_data );
		}

		if ( $is_countdown_timer_enable && $is_countdown_timer_visible ) {
			self::render_countdown_timer( $campaign_id, $template_data, $start_timestamp, $end_timestamp );
		}

		if ( $is_any_enable ) {
			echo '</div>';
		}
	}

	/**
	 * Render the countdown timer for a campaign.
	 *
	 * Outputs the countdown timer markup and data attributes used by front-end
	 * scripts to initialise and update the timer for the given campaign.
	 *
	 * @param int   $campaign_id   Campaign ID.
	 * @param array $template_data Template builder data (array) used for classes/text.
	 * @return void Echoes HTML directly.
	 */
	public static function render_countdown_timer( $campaign_id, $template_data, $start_timestamp, $end_timestamp ) {
		// start  time and end time was sent for safety purpose.

		// IMPORTANT NOTE: for devs
		// the id="revx-countdown-timer-$campaign_id",
		// divs with classes revx-days, revx-hours, revx-minutes, revx-seconds
		// are necessary for the jquery to dynamically update the timers.
		?>
	   
			<div
				class="<?php echo esc_attr( self::get_element_class( $template_data, 'CountdownTimerContainer' ) ); ?> 
				revx-d-flex revx-item-center revx-flex-wrap"
				data-countdown-timer-container="containerDiv"
				data-campaign-id="<?php echo esc_attr( $campaign_id ); ?>"
			>
			<?php echo wp_kses_post( self::render_rich_text( $template_data, 'countdownTimerPrefix', '', '', '', 'text', '', true ) ); ?>
				<div
					id="revx-countdown-timer-<?php echo esc_attr( $campaign_id ); ?>"
					class="revx-countdown-timer revx-d-flex revx-item-center"
					style="gap: 4px; color: var(--revx-timer-color)"
					data-end-time="<?php echo esc_attr( $end_timestamp ); ?>"
				<?php if ( $start_timestamp ) : ?>
						data-start-time="<?php echo esc_attr( $start_timestamp ); ?>"
					<?php endif; ?>
				>
					<div class="revx-days">00</div>
					<span class="revx-days-colon"> : </span> 
					<div class="revx-hours">00</div>
					<span class="revx-hours-colon"> : </span> 
					<div class="revx-minutes">00</div>
					<span class="revx-minutes-colon"> : </span> 
					<div class="revx-seconds">00</div>
				</div>
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
			} elseif ( $spending_goal != '0' ) {
				$gift_heading = 'Spend ' . $spending_goal . ' more to get any ' . $gift_quantity . ' ' . $item_text . ' as a gift!';
			}
		}

		?>
			<div 
				data-success-message="<?php echo esc_attr( $success_message ); ?>" 
				class="revx-progress-step <?php echo ! $is_last ? 'middle' : ''; ?> <?php echo $accomplished ? 'revx-progress-active' : ''; ?>" 
				style="
					<?php if ( $is_rtl ) : ?>
						right: calc( <?php echo esc_attr( $step_percentage ); ?>% - (calc( (var(--revx-progress-height, 8px) * 3 + (<?php echo esc_attr( $icon_padding ); ?> * 2)) * 2 )) );
					<?php else : ?>
						left: <?php echo esc_attr( $step_percentage ); ?>%;
					<?php endif; ?>
					position: absolute;
					z-index: 999;
					translate: -100% <?php echo empty( $label ) ? '-4%' : '0%'; ?>
				"
			>
				<div 
					class="revx-progress-step-icon-container <?php echo $is_last ? 'last' : ''; ?> <?php echo $accomplished ? 'completed' : ''; ?>"
					style="
						background-color: <?php echo $accomplished ? 'var(--revx-active-color, #F6F8FA)' : 'var(--revx-inactive-color, #F6F8FA)'; ?>;
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
							color: <?php echo $accomplished ? 'var(--revx-inactive-color, #F6F8FA)' : 'var(--revx-icon-color, #1827DD)'; ?>;
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
													<div class="revx-spending-gift-action <?php echo in_array( $product['item_id'], $selected_product_ids ) ? 'revx-active' : ''; ?>">
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
				echo wp_kses_post( self::render_rich_text( $template_data, 'spendingGoalLabel', $label, 'revx-absolute revx-bellow-8 revx-spg-goal-label', 'font-size: var(--revx-step-label-size, 14px); font-weight: 500; color: var(--revx-step-label-color); width: max-content; max-width: 9rem;' ) );
			}
			?>
			</div>
			<?php
	}
	/**
	 * Render the progress bar used in various campaign types.
	 *
	 * Generates the HTML for a progress bar and optional markers/icons for
	 * countdown, spending-goal and stock-scarcity campaign types.
	 *
	 * @param array       $data       Template/element data array.
	 * @param string      $element_id Element identifier used to fetch classes.
	 * @param int|float   $progress   Progress percentage (0-100).
	 * @param array|false $campaign   Campaign data array or false when not available.
	 * @param string      $template   Template variant identifier (default 'one').
	 * @return string                Rendered HTML for the progress bar.
	 */
	public static function render_progressbar( $data, $element_id, $progress = 0, $campaign = false, $template = 'one' ) {
		$class = esc_attr( self::get_element_class( $data, $element_id ) );
		if ( ! $campaign ) {
			return;
		}

		$is_rtl = revenue()->is_rtl();

		$is_countdown      = 'countdown_timer' === $campaign['campaign_type'];
		$is_spg            = 'spending_goal' === $campaign['campaign_type'];
		$is_stock_scarcity = 'stock_scarcity' === $campaign['campaign_type'];
		$template_data     = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );

		// Check if any step has a label/reward_name.
		$has_label = false;
		if ( $is_spg && ! empty( $campaign['offers'] ) && is_array( $campaign['offers'] ) ) {
			foreach ( $campaign['offers'] as $step ) {
				if ( ! empty( $step['reward_name'] ) ) {
					$has_label = true;
					break;
				}
			}
		}

		ob_start();

		// Build inline styles.
		$inline_styles = array(
			'height: var(--revx-progress-height, 8px)',
			'background: var(--revx-inactive-color, #f6f8fa)',
			'display: flex',
			'align-items: center',
			'border-radius: 6px',
			'position: relative',
		);
		if ( $is_countdown ) {
			$inline_styles[] = 'margin-top: calc(var(--revx-progress-height, 8px) - calc(var(--revx-progress-height, 8px) / 5))';
			$inline_styles[] = 'margin-bottom: calc(var(--revx-progress-height, 8px) - calc(var(--revx-progress-height, 8px) / 5)) !important';
		}

		$style_string = implode( '; ', $inline_styles ) . ';';
		?>
			<div
				class="<?php echo esc_attr( $class ); ?> <?php echo $is_countdown ? 'revx-countdown-progress-container' : ''; ?> revx-progress-container <?php echo $has_label ? 'revx-label-spacing' : ''; ?>"
				style="<?php echo esc_attr( $style_string ); ?>"
			>
				<div
					class="revx-stock-bar revx-progress-bar"
					style="
						background: var(--revx-active-color, #6e3ff3);
						width: <?php echo esc_attr( floatval( $progress ) ); ?>%;
						height: inherit;
						border-radius: inherit;
					"
				></div>
			<?php if ( $is_countdown ) { ?>
				<div
					class="revx-progress-bar-icon"
					style="
						position: absolute;
						z-index: 9999;
						line-height: 0;
						<?php if ( $is_rtl ) : ?>
							right: calc(<?php echo esc_attr( floatval( $progress ) ); ?>% - (var(--revx-progress-height, 8px) * 2.7));
						<?php else : ?>
							left: calc(<?php echo esc_attr( floatval( $progress ) ); ?>% - (var(--revx-progress-height, 8px) * 2.7));
						<?php endif; ?>
						width: calc(var(--revx-progress-height, 8px) * 3);
						height: calc(var(--revx-progress-height, 8px) * 3);
						color: var(--revx-icon-color, #ffffff);
						fill: var(--revx-active-color, #6e3ff3);
					"
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="100%"
						height="100%"
						fill="none"
						viewBox="0 0 16 16"
					>
						<path
							fill="var(--revx-active-color, #6E3FF3)"
							d="M8 14.667A6.667 6.667 0 1 0 8 1.334a6.667 6.667 0 0 0 0 13.333"
						></path>
						<path stroke="currentColor" d="M8 4v4l2.667 1.333"></path>
					</svg>
				</div>
			<?php } ?>
			<?php
			if ( $is_spg ) {
				$template_data['is_rtl'] = $is_rtl;

				$steps        = ( ! empty( $campaign['offers'] ) && is_array( $campaign['offers'] ) )
									? $campaign['offers'] : false;
				$is_show_icon = 'yes' === ( $campaign['spending_goal_progress_show_icon'] ?? '' );
				if ( $steps ) {
					$total_steps = count( $steps );
					if ( $total_steps <= 0 ) {
						return;
					}
					$required_goal = 0;
					foreach ( $steps as $idx => $step ) {
						if ( ! isset( $step['spending_goal'] ) ) {
							continue;
						}
						$required_goal += $step['spending_goal'];
						self::render_spg_step(
							$idx,
							$step,
							$progress,
							$total_steps,
							$template_data,
							$required_goal,
							$is_show_icon
						);
					}
				}
			}
			?>
			<?php if ( $is_stock_scarcity && 'two' === $template ) { ?>
				<div
					style="
						position: absolute;
						z-index: 1;
						transform: translate(16%, -12%);
						line-height: 0;
						left: calc(<?php echo esc_attr( floatval( $progress ) ); ?>% - (var(--revx-progress-height, 8px) * 3));
						width: calc(var(--revx-progress-height, 8px) * 3);
						height: calc(var(--revx-progress-height, 8px) * 3);
						color: var(--revx-icon-color, #1827dd);
					"
				>
					<svg
						xmlns="http://www.w3.org/2000/svg"
						width="100%"
						height="100%"
						fill="none"
						viewBox="0 0 24 24"
					>
						<path
							fill="currentColor"
							d="M19.8 11.49a8.1 8.1 0 0 0-1.944-2.7l-.682-.626a.19.19 0 0 0-.305.077l-.304.875c-.19.548-.54 1.108-1.034 1.659a.15.15 0 0 1-.096.047.13.13 0 0 1-.1-.035.14.14 0 0 1-.048-.113c.087-1.41-.335-3.002-1.258-4.734-.764-1.44-1.826-2.562-3.152-3.345l-.968-.57a.188.188 0 0 0-.282.172l.052 1.125c.035.769-.054 1.448-.265 2.013a6.7 6.7 0 0 1-1.101 1.91q-.496.603-1.114 1.08a8.3 8.3 0 0 0-2.35 2.848 8.15 8.15 0 0 0-.2 6.804 8.234 8.234 0 0 0 4.392 4.35 8.25 8.25 0 0 0 3.209.642 8.3 8.3 0 0 0 3.209-.64 8.2 8.2 0 0 0 2.622-1.75 8.12 8.12 0 0 0 2.419-5.787 8.1 8.1 0 0 0-.7-3.302"
						></path>
					</svg>
				</div>
			<?php } ?>

			</div>
			<?php
			return ob_get_clean();
	}

	/**
	 * Return the SVG markup for a divider icon wrapped in a container element.
	 *
	 * Supported divider types: 'clone', 'bar', 'hyphen'. If 'none' is passed an
	 * empty string is returned. The $isTransform flag controls whether a translateY
	 * transform is included in the inline style for vertical positioning.
	 *
	 * @param string $divider_icon Divider icon identifier.
	 * @param bool   $is_transform  Optional. Whether to include translateY transform. Default true.
	 * @return string HTML string containing a div wrapper with the requested SVG, or an empty string.
	 */
	public static function get_divider_icon( $divider_icon, $is_transform = true ) {
		if ( 'none' === $divider_icon ) {
			return '';
		}

		// Determine inline style.
		$style                   = '';
		$is_transform && $style .= 'transform: translateY(var(--revx-icon-position, 0)); ';
		$style                  .= 'hyphen' === $divider_icon
		? 'height: var(--revx-icon-size);'
		: 'width: var(--revx-icon-size);';

		// SVG content based on icon type.
		switch ( $divider_icon ) {
			case 'clone':
				$svg = '
				<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="none" viewBox="0 0 4 12">
					<circle cx="2" cy="2" r="2" fill="currentColor"></circle>
					<circle cx="2" cy="10" r="2" fill="currentColor"></circle>
				</svg>
			';
				break;

			case 'bar':
				$svg = '
				<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="none" viewBox="0 0 2 12">
					<rect width="2" height="12" fill="currentColor" rx="1"></rect>
				</svg>
			';
				break;

			case 'hyphen':
				$svg = '
				<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="none" viewBox="0 0 12 2">
					<rect width="2" height="12" x="12" fill="currentColor" rx="1" transform="rotate(90 12 0)"></rect>
				</svg>
			';
				break;

			default:
				return '';
		}

		// Return wrapped SVG.
		return '<div class="revx-countdown-divider" style="' . $style . '">' . $svg . '</div>';
	}

	/**
	 * Render the campaign close button.
	 *
	 * Outputs the HTML markup for the campaign close icon used in campaign containers.
	 *
	 * @param array|string|null $template_data Template data array or identifier used to fetch classes.
	 * @param string            $custom_class  Additional CSS class to append to the close wrapper.
	 * @param string            $element_id    Element identifier in the template (default 'campaignClose').
	 * @return void
	 */
	public static function render_campaign_close( $template_data, $custom_class = '', $element_id = 'campaignClose' ) {
		ob_start();
		?>
			<div
				class="<?php echo esc_attr( self::get_element_class( $template_data, $element_id ) ); ?> revx-campaign-close <?php echo esc_attr( $custom_class ); ?>"
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
		return ob_get_clean();
	}

	/**
	 * Render the free shipping label.
	 *
	 * Outputs the free shipping markup using the provided template data. This
	 * is used when free shipping is enabled for a campaign to display the
	 * configured label and icon.
	 *
	 * @param array $template_data Template builder data used for rendering.
	 * @return void
	 */
	public static function render_free_shipping( $template_data ) {
		?>

			<div
				class="<?php echo esc_attr( self::get_element_class( $template_data, 'FreeShippingContainer' ) ); ?>"
			>
				<div class="revx-d-flex revx-item-center revx-gap-10">
					<div class="revx-fsc-check">
						<svg
							xmlns="http://www.w3.org/2000/svg"
							fill="none"
							viewBox="0 0 20 20"
							width="1em"
							height="1em"
						>
							<path
								stroke="currentColor"
								d="M10 17.5a7.5 7.5 0 1 0-5.303-2.197"
							></path>
							<path
								stroke="currentColor"
								d="m13.334 8.333-2.765 3.318c-.655.786-.983 1.18-1.424 1.2s-.802-.343-1.526-1.067l-.952-.951"
							></path>
						</svg>
					</div>
				<?php echo wp_kses( self::render_rich_text( $template_data, 'freeShippingLabel', '', '', '', 'text', '', true ), revenue()->get_allowed_tag() ); ?>
				</div>
			</div>
			<?php
	}

	/**
	 * Build and return CSS custom properties for global theme settings.
	 *
	 * Reads theme configuration from the plugin settings and constructs a
	 * string containing CSS variable declarations (for base colors, shades and
	 * accents) which can be injected into stylesheets.
	 *
	 * @return string CSS variables for global theme (each declaration ends with a newline).
	 */
	public static function get_global_themes() {
		$themes_data = revenue()->get_setting( 'themes' );

		$theme_vars = '';

		if ( isset( $themes_data['base'] ) ) {
			if ( isset( $themes_data['base']['primary'] ) ) {
				$theme_vars .= "--revx-theme-base-primary: {$themes_data['base']['primary']};\n";
			}
			if ( isset( $themes_data['base']['secondary'] ) ) {
				$theme_vars .= "--revx-theme-base-secondary: {$themes_data['base']['secondary']};\n";
			}

			if ( isset( $themes_data['base']['shades'] ) && is_array( $themes_data['base']['shades'] ) ) {
				foreach ( $themes_data['base']['shades'] as $index => $shade ) {
					$theme_vars .= '--revx-theme-shade-' . ( $index + 1 ) . ": $shade;\n";
				}
			}
		}

		if ( isset( $themes_data['accent'] ) ) {
			foreach ( $themes_data['accent'] as $key => $value ) {
				$theme_vars .= "--revx-theme-accent-$key: $value;\n";
			}
		}

		return $theme_vars;
	}

	/**
	 * Render slider navigation icons for a template.
	 *
	 * Generates HTML markup for previous and next slider arrows if the view mode is 'grid'.
	 * Uses `self::get_element_class()` to get CSS classes and outputs SVG arrows.
	 *
	 * @param array  $template   Template configuration array (used to get CSS classes).
	 * @param string $view_mode  Current view mode (e.g., 'grid', 'list').
	 *
	 * @return string HTML markup for slider icons or empty string if not applicable.
	 */
	public static function render_slider_icons( $template, $view_mode ) {

		if ( 'grid' !== $view_mode ) {
			return '';
		}
		$slider_class = self::get_element_class( $template, 'sliderIcon' );

		ob_start();
		?>
			<div class="revx-slider-icons"> 
				<div class="<?php echo esc_attr( $slider_class ); ?> prev" style="display: flex; box-sizing: content-box; position: absolute; left: 0; top: 50%;">
					<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="none" transform="rotate(180)" viewBox="0 0 24 24"><path stroke="currentColor" d="m9 18 6-6-6-6"></path></svg>
				</div>
				<div class="<?php echo esc_attr( $slider_class ); ?> next" style="display: flex; box-sizing: content-box; position: absolute; right: 0; top: 50%;">
					<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" d="m9 18 6-6-6-6"></path></svg>
				</div>
			</div>
			<?php
			return ob_get_clean();
	}

	/**
	 * Get slider column counts for different layouts and breakpoints.
	 *
	 * Reads the 'productsWrapper.column' structure from the provided template and
	 * returns an associative array of column counts keyed by layout and breakpoint.
	 *
	 * Example return:
	 *   array(
	 *     'inpage'   => array( 'lg' => 4, 'md' => 2, 'sm' => 1 ),
	 *     'floating' => array( 'lg' => 3, 'md' => 2, 'sm' => 1 ),
	 *     'popup'    => array( 'lg' => 5, 'md' => 3, 'sm' => 2 ),
	 *   )
	 *
	 * @param array $template Template builder data containing productsWrapper.column configuration.
	 * @return array|null Associative array of columns per layout and breakpoint, or null when input is invalid.
	 */
	public static function get_slider_data( $template ) {
		if ( ! isset( $template['productsWrapper']['column'] ) ) {
			return;
		}
		$layouts     = array( 'inpage', 'floating', 'popup' );
		$breakpoints = array( 'lg', 'md', 'sm' );
		$columns     = $template['productsWrapper']['column'];

		$result = array();

		foreach ( $layouts as $layout ) {
			foreach ( $breakpoints as $bp ) {
				$value                    = $columns[ $layout ][ $bp ]['grid']['value'] ?? null;
				$result[ $layout ][ $bp ] = (int) $value;
			}
		}

		return $result;
	}

	/**
	 * Render a popup campaign container.
	 *
	 * Builds and outputs the HTML wrapper used for popup campaign views.
	 *
	 * @param array  $current_campaign Campaign data array (must include 'id' and other meta).
	 * @param string $output_content   The inner HTML content to display inside the popup.
	 * @param string $class            Optional additional CSS class(es) for the popup container.
	 * @param bool   $without_heading  Optional flag to suppress heading output (unused in current implementation).
	 * @param string $placement        Optional placement identifier.
	 * @return void Outputs the popup container HTML.
	 */
	public static function popup_container( $current_campaign, $output_content, $class = '', $without_heading = false, $placement = '' ) {

		$placement_settings = revenue()->get_placement_settings( $current_campaign['id'], $placement );
		$view_mode          = $placement_settings['builder_view'] ?? 'list';

		$heading_text    = isset( $current_campaign['banner_heading'] ) ? $current_campaign['banner_heading'] : '';
		$subheading_text = isset( $current_campaign['banner_subheading'] ) ? $current_campaign['banner_subheading'] : '';
		$campaign_type   = $current_campaign['campaign_type'];
		$view_id         = revenue()->get_campaign_meta( $current_campaign['id'], 'campaign_view_id', true ) ?? '';
		$view_class      = revenue()->get_campaign_meta( $current_campaign['id'], 'campaign_view_class', true ) ?? '';
		$template_data   = revenue()->get_campaign_meta( $current_campaign['id'], 'builder', true );
		$class          .= " $view_class ";

		$animation_delay = 0;
		$animation_name  = isset( $placement_settings['popup_animation'] ) ? esc_attr( $placement_settings['popup_animation'] ) : '';
		$animation_class = "revx-animation-$animation_name";

		$animation_delay = isset( $placement_settings['popup_animation_delay'] ) ? $placement_settings['popup_animation_delay'] : 0;

		do_action( "revenue_campaign_{$campaign_type}_inpage_before_rendered_content", $current_campaign );
		?>
			<div 
				id="<?php echo esc_attr( $view_id ); ?>" 
				class="revx-campaign-popup-wrapper revx-popup revx-all-center revx-campaign-<?php echo esc_attr( $current_campaign['id'] ); ?> revx-campaign-view-<?php echo esc_attr( $current_campaign['id'] ); ?> <?php echo esc_attr( $animation_class ); ?>"
			>
				<div id="revx-popup-overlay" class="revx-popup__overlay"></div>
				<div 
					class="revx-popup__container" 
					data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>"
					data-animation-name="<?php echo esc_attr( $animation_name ); ?>"
					data-animation-delay="<?php echo esc_attr( $animation_delay ); ?>"
					id="revx-popup"
				>
				<?php
					echo wp_kses(
						self::render_campaign_close( $template_data, 'revx-close-right' ),
						revenue()->get_allowed_tag()
					);
				?>
					<div
						data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>"
						data-animation-name="<?php echo esc_attr( $animation_name ); ?>"
						data-animation-delay="<?php echo esc_attr( $animation_delay ); ?>"
						class="revx-popup__content revx-campaign-container <?php echo esc_attr( $class ); ?> revx-campaign-<?php echo esc_attr( $view_mode ); ?>"
					>
					<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $output_content;
					?>
					</div>
				</div>
			</div>
			<?php
	}
	/**
	 * Render the floating campaign container.
	 *
	 * Builds and outputs the HTML wrapper used for floating campaign views.
	 *
	 * @param array  $current_campaign Campaign data array (must include 'id' and other meta).
	 * @param string $output_content   The inner HTML content to display inside the floating container.
	 * @param string $class            Optional additional CSS class(es) for the floating container.
	 * @param bool   $without_heading  Optional flag to suppress heading output.
	 * @param string $placement        Optional placement identifier.
	 * @return void
	 */
	public static function floating_container( $current_campaign, $output_content, $class = '', $without_heading = false, $placement = '' ) {
		$animation_delay = 0;

		$placement_settings = revenue()->get_placement_settings( $current_campaign['id'], $placement );
		$view_mode          = $placement_settings['builder_view'] ?? 'list';

		$heading_text    = isset( $current_campaign['banner_heading'] ) ? $current_campaign['banner_heading'] : '';
		$subheading_text = isset( $current_campaign['banner_subheading'] ) ? $current_campaign['banner_subheading'] : '';
		$campaign_type   = $current_campaign['campaign_type'];

		$view_id    = revenue()->get_campaign_meta( $current_campaign['id'], 'campaign_view_id', true ) ?? '';
		$view_class = revenue()->get_campaign_meta( $current_campaign['id'], 'campaign_view_class', true ) ?? '';
		$class     .= " $view_class ";

		$template_data = revenue()->get_campaign_meta( $current_campaign['id'], 'builder', true );

		$animation_delay = isset( $placement_settings['floating_animation_delay'] ) ? $placement_settings['floating_animation_delay'] : 0;

		$position = isset( $placement_settings['floating_position'] ) ? $placement_settings['floating_position'] : '';
		// Determine position class based on $position variable.
		switch ( $position ) {
			case 'top-left':
			case 'top-right':
			case 'bottom-left':
			case 'bottom-right':
				$position_class = 'revx-floating-' . esc_attr( $position );
				break;
			default:
				$position_class = 'revx-floating-bottom-right'; // Default to bottom-right if position is not specified.
				break;
		}
		?>
			<div 
				class="revx-floating-main revx-all-center revx-campaign-<?php echo esc_attr( $current_campaign['id'] ); ?> revx-campaign-view-<?php echo esc_attr( $current_campaign['id'] ); ?> "  
				style="visibility: hidden;"
				id="<?php echo esc_attr( $view_id ); ?>" 
				data-position-class="<?php echo esc_attr( $position_class ); ?>" 
				data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" 
				data-animation-delay="<?php echo esc_attr( $animation_delay ); ?>" 
			>
				<div class="revx-floating-container">
				<?php echo wp_kses_post( self::render_campaign_close( $template_data, 'revx-close-right' ) ); ?>
					<div id="revx-floating" class="revx-floating revx-campaign-container <?php echo esc_attr( $class ); ?> revx-campaign-<?php echo esc_attr( $view_mode ); ?>" data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" >
					<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $output_content;
					?>
					</div>
				</div>
			</div>
			<?php
	}

	/**
	 * Render the inpage campaign container.
	 *
	 * Builds and outputs the HTML wrapper used for inpage campaign views.
	 *
	 * @param array  $current_campaign Campaign data array (must include 'id' and 'campaign_type').
	 * @param string $output_content   The inner HTML content to display inside the container.
	 * @param string $class            Optional additional CSS class(es) for the container.
	 * @param string $placement        Optional placement identifier.
	 * @return void
	 */
	public static function inpage_container( $current_campaign, $output_content, $class = '', $placement = '' ) {
		$placement_settings = revenue()->get_placement_settings( $current_campaign['id'], $placement );
		$view_mode          = $placement_settings['builder_view'] ?? 'list';

		$view_id    = revenue()->get_campaign_meta( $current_campaign['id'], 'campaign_view_id', true ) ?? '';
		$view_class = revenue()->get_campaign_meta( $current_campaign['id'], 'campaign_view_class', true ) ?? '';
		$class     .= " $view_class ";

		$heading_text    = isset( $current_campaign['banner_heading'] ) ? $current_campaign['banner_heading'] : '';
		$subheading_text = isset( $current_campaign['banner_subheading'] ) ? $current_campaign['banner_subheading'] : '';
		$campaign_type   = $current_campaign['campaign_type'];

		$theme      = wp_get_theme();
		$theme_name = get_stylesheet();

		$class                .= " revx-theme-$theme_name ";
		$is_stock_or_countdown = in_array( $campaign_type, array( 'stock_scarcity', 'countdown_timer' ), true );

		// $position is not used, so we remove it to avoid unused variable warnings.
		// If you need to use 'inpage_position' later, use:
		// $position = isset($placement_settings['inpage_position']) ? $placement_settings['inpage_position'] : '';

		do_action( 'revenue_campaign_before_container', $current_campaign['id'], $campaign_type, 'inpage', $current_campaign );
		?>
		<div 
			id="<?php echo esc_attr( $view_id ); ?>" 
			data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" 
			class="revx-template revx-inpage-container 
				<?php echo esc_attr( $is_stock_or_countdown ? '' : 'revx-campaign-container' ); ?>
				<?php echo esc_attr( $current_campaign['campaign_type'] ); ?> 
				<?php echo esc_attr( $placement_settings['page'] ?? '' ); ?> 
				<?php echo esc_attr( $class ); ?> revx-campaign-<?php echo esc_attr( $view_mode ); ?> 
				revx-campaign-<?php echo esc_attr( $current_campaign['id'] ); ?> 
				<?php echo esc_attr( 'mix_match' === $campaign_type ? 'revx-w-full' : '' ); ?>" 
		>
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $output_content;
			?>
		</div>
			<?php
			do_action( 'revenue_campaign_after_container', $current_campaign['id'], $campaign_type, 'inpage', $current_campaign );
	}


	/**
	 * Group products by parent if they are variations.
	 *
	 * Builds a grouped array where variation products are nested under their parent
	 * product entry, while simple products are returned as standalone items.
	 *
	 * @param array $product_ids Array of product IDs.
	 * @return array Array of grouped product data.
	 */
	public static function get_product_group( $product_ids ) {
		$grouped = array();
		$result  = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$is_variation = $product->is_type( 'variation' );
			$parent_id    = $is_variation ? $product->get_parent_id() : '';
			$parent_name  = $is_variation ? get_the_title( $parent_id ) : '';
			$thumbnail_id = $product->get_image_id();
			$thumbnail    = wp_get_attachment_url( $thumbnail_id );

			// Format variation name: "Product Name – Size: X, Color: Y".
			$item_name = $product->get_name();
			if ( $is_variation ) {
				$attributes = $product->get_attributes();
				$attr_parts = array();
				foreach ( $attributes as $key => $value ) {
					$taxonomy     = wc_attribute_label( str_replace( 'attribute_pa_', '', $key ) );
					$attr_parts[] = "{$taxonomy}: {$value}";
				}
				$item_name = $parent_name . ' – ' . implode( ', ', $attr_parts );
			}

			// Common item array.
			$item = array(
				'item_id'       => (string) $product_id,
				'item_name'     => $item_name,
				'thumbnail'     => $thumbnail,
				'regular_price' => $product->get_regular_price(),
				'sale_price'    => $product->get_sale_price(),
				'is_in_stock'   => $product->is_in_stock(),
				'quantity'      => 2,
				'parent_id'     => $parent_id ? (int) $parent_id : '',
				'attributes'    => $is_variation ? $attributes : '',
			);

			if ( $is_variation ) {
				if ( ! isset( $grouped[ $parent_id ] ) ) {
					$grouped[ $parent_id ] = array(
						'parent_id'  => $parent_id,
						'item_name'  => $parent_name,
						'thumbnail'  => $thumbnail,
						'quantity'   => 2,
						'variations' => array(),
					);
					$result[]              = &$grouped[ $parent_id ]; // Reference for later push.
				}
				$grouped[ $parent_id ]['variations'][] = $item;
			} else {
				$result[] = $item;
			}
		}

		return $result;
	}
	/**
	 * Build a simplified product list for the given product IDs.
	 *
	 * Each returned item contains:
	 *  - item_id:        string product id
	 *  - item_name:      product name
	 *  - thumbnail:      product image URL
	 *  - regular_price:  regular price as returned by WC_Product
	 *  - sale_price:     sale price as returned by WC_Product
	 *  - is_in_stock:    boolean stock status
	 *  - quantity:       hardcoded default quantity (2)
	 *
	 * @param array $product_ids Array of product IDs.
	 * @return array Array of product data arrays.
	 */
	public static function get_product_list( $product_ids ) {
		$result = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			$thumbnail_id = $product->get_image_id();
			$thumbnail    = wp_get_attachment_url( $thumbnail_id );

			$item_name = $product->get_name();

			$item = array(
				'item_id'       => (string) $product_id,
				'item_name'     => $item_name,
				'thumbnail'     => $thumbnail,
				'regular_price' => $product->get_regular_price(),
				'sale_price'    => $product->get_sale_price(),
				'is_in_stock'   => $product->is_in_stock(),
				'quantity'      => 2,
			);

			$result[] = $item;
		}

		return $result;
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
}
