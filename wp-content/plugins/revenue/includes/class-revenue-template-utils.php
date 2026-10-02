<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * Revenue Ajax
 *
 * @package Revenue
 */

namespace Revenue;

defined( 'ABSPATH' ) || exit;

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
	 * campaign is a wrapper type it renders an extra wrapper element; otherwise a simple divider.
	 *
	 * @param bool       $is_grid_view  Whether the layout is grid view.
	 * @param string     $element_id    Optional element id to lookup classes in template data.
	 * @param array|null $template_data Template builder data or null.
	 * @param bool       $is_buy_x_get_y True when rendering buy_x_get_y campaign divider.
	 * @param bool       $is_bundle     True when rendering bundle campaign divider.
	 * @param bool       $is_wrapper    True when the campaign type wraps its divider.
	 * @return void
	 */
	public static function render_campaign_divider( $is_grid_view = false, $element_id = '', $template_data = null, $is_buy_x_get_y = false, $is_bundle = false, $is_wrapper = false ) {
		$class_name = '' !== $element_id ? self::get_element_class( $template_data, $element_id ) : '';
		$is_wrapper = $is_buy_x_get_y || $is_bundle || $is_wrapper;

		if ( $is_wrapper ) {
			?>
			<div
				class="<?php echo $is_grid_view ? 'vertical' : 'horizontal'; ?> <?php echo $is_grid_view ? '' : esc_attr( $class_name ); ?> revx-campaign-divider-wrapper"
			>
				<div
					class="revx-campaign-divider <?php echo $is_grid_view ? 'vertical' : 'horizontal';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>"
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
	 * Set total prices for the current render context.
	 *
	 * Renderers that live outside this class use this instead of touching the properties.
	 *
	 * @param float $after_price  Total price after discounts/offers.
	 * @param float $before_price Total price before discounts/offers.
	 * @return void
	 */
	public static function set_total_price( $after_price, $before_price ) {
		self::$after_price  = (float) $after_price;
		self::$before_price = (float) $before_price;
	}

	/**
	 * Campaign types whose divider is wrapped in an extra element.
	 *
	 * @param string $campaign_type Campaign type slug.
	 * @return bool
	 */
	public static function uses_wrapper_divider( $campaign_type ) {
		$types = apply_filters( 'revenue_campaign_wrapper_divider_types', array() );

		return in_array( $campaign_type, (array) $types, true );
	}

	/**
	 * Campaign types that draw a divider between offer items.
	 *
	 * @param string $campaign_type Campaign type slug.
	 * @return bool
	 */
	public static function uses_item_divider( $campaign_type ) {
		$types = apply_filters( 'revenue_campaign_item_divider_types', array( 'bundle_discount' ) );

		return in_array( $campaign_type, (array) $types, true );
	}

	/**
	 * Campaign types that never show a per-product add to cart button.
	 *
	 * @param string $campaign_type Campaign type slug.
	 * @return bool
	 */
	public static function hides_add_to_cart( $campaign_type ) {
		$types = apply_filters( 'revenue_campaign_hides_add_to_cart_types', array( 'buy_x_get_y' ) );

		return in_array( $campaign_type, (array) $types, true );
	}

	/**
	 * Campaign types that render product cards with the second card layout.
	 *
	 * @param string $campaign_type Campaign type slug.
	 * @return bool
	 */
	public static function uses_card_template_two( $campaign_type ) {
		$types = apply_filters( 'revenue_campaign_card_template_two_types', array( 'free_shipping_bar' ) );

		return in_array( $campaign_type, (array) $types, true );
	}

	/**
	 * Campaign types that render a selection checkbox on each offer item.
	 *
	 * @param string $campaign_type Campaign type slug.
	 * @return bool
	 */
	public static function uses_offer_checkbox( $campaign_type ) {
		$types = apply_filters( 'revenue_campaign_offer_checkbox_types', array() );

		return in_array( $campaign_type, (array) $types, true );
	}









	/**
	 * Campaign types that let the shopper build their own selection.
	 *
	 * These pick products with an add-product button instead of add to cart, list the
	 * campaign trigger products alongside the offers, and carry the selection in the footer.
	 *
	 * @param string $campaign_type Campaign type slug.
	 * @return bool
	 */
	public static function uses_product_picker( $campaign_type ) {
		$types = apply_filters( 'revenue_campaign_product_picker_types', array() );

		return in_array( $campaign_type, (array) $types, true );
	}

	/**
	 * Campaign types that render their offers as an upsell slider.
	 *
	 * @param string $campaign_type Campaign type slug.
	 * @return bool
	 */
	public static function uses_upsell_slider( $campaign_type ) {
		$types = apply_filters( 'revenue_campaign_upsell_slider_types', array( 'free_shipping_bar' ) );

		return in_array( $campaign_type, (array) $types, true );
	}

	/**
	 * Campaign meta key holding the upsell product groups.
	 *
	 * @param string $campaign_type Campaign type slug.
	 * @return string
	 */
	public static function get_upsell_products_key( $campaign_type ) {
		return (string) apply_filters( 'revenue_campaign_upsell_products_key', 'upsell_products', $campaign_type );
	}

	/**
	 * Campaign meta key holding the upsell on/off status.
	 *
	 * @param string $campaign_type Campaign type slug.
	 * @return string
	 */
	public static function get_upsell_status_key( $campaign_type ) {
		return (string) apply_filters( 'revenue_campaign_upsell_status_key', 'upsell_products_status', $campaign_type );
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
		$is_product_picker       = self::uses_product_picker( $campaign_type );
		$is_buy_x_get_y          = 'buy_x_get_y' === $campaign_type;
		$is_wrapper_divider      = self::uses_wrapper_divider( $campaign_type );
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

			$image         = ( wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' ) ) ? wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' ) : array( wc_placeholder_img_src() );
			$product_title = ( $is_bundle || $is_variable_product ) ? $product_data['item_name'] : $offered_product->get_title();
			$regular_price = $default_variation['regular_price'];
			$sale_price    = ( isset( $default_variation['sale_price'] ) && $default_variation['sale_price'] > 0 )
								? $default_variation['sale_price']
								: $regular_price;
			$product_price = ( isset( $default_variation['sale_price'] ) && $default_variation['sale_price'] > 0 ) ? $sale_price : $regular_price;
			// $price_data    = revenue()->calculate_campaign_offered_price( $offer['type'], $offer['value'], $is_product_picker ? $regular_price : $product_price, true );
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
				'sale_price'    => $is_product_picker ? '' : $sale_price,
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
				$is_product_picker ? 'addProductWrapper' : 'addToCartWrapper',
				$product_index,
				$is_x_product,
				$is_trigger
			);

			if ( $is_buy_x_get_y && $total_rendered_products < $render_product_length - 1 ) {
				echo '<div class="revx-item-separator"></div>';
			}

			++$total_rendered_products;

			if ( ! ( $offer_length === $offer_index && $render_product_length === $total_rendered_products ) && $is_divider ) {
				self::render_campaign_divider( $is_grid_view, '', null, $is_buy_x_get_y, $is_bundle, $is_wrapper_divider );
			}
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

			$image         = ( wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' ) ) ? wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), 'single-post-thumbnail' ) : array( wc_placeholder_img_src() );
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

			// Campaign divider rendering is handled by the caller when needed.
		}
		self::$after_price  = $after_price;
		self::$before_price = $before_price;
	}

	/**
	 * Render the products item container for a campaign.
	 *
	 * Builds and outputs the products container markup for different campaign types
	 * handling slider wrappers, trigger items and prepending trigger/upsell
	 * products when necessary.
	 *
	 * @param array                     $campaign         Campaign data array.
	 * @param array|string              $template_data    Template builder data (array) or empty string.
	 * @param array                     $offers           Offers array for the campaign.
	 * @param bool                      $is_product_picker     True when the shopper builds their own selection.
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
		$is_product_picker,
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
		$is_upsell_slider    = self::uses_upsell_slider( $campaign['campaign_type'] );
		$is_buy_x_get_y      = 'buy_x_get_y' === $campaign['campaign_type'];

		$slider_controller_class = $is_upsell_slider ? 'revx-slider2-controller' : '';

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
		if ( $is_grid_view || $is_upsell_slider ) {
			echo '<div class="' . esc_attr( $class_name ) . '">';
			echo wp_kses( $prev_button, revenue()->get_allowed_tag() );
			echo '<div class="revx-slider-content ' . esc_attr( $is_upsell_slider ? 'revx-slider2-content' : 'revx-slider-style' ) . '">';
		}

		if ( $is_upsell_slider ) {
			$product_ids    = array();
			$discount_type  = '';
			$discount_value = 0;

			foreach ( $campaign[ self::get_upsell_products_key( $campaign['campaign_type'] ) ] as $upsell_products ) {
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

		// Some campaign types show the trigger products as the first offer. Their owner supplies it.
		$offers = apply_filters( 'revenue_campaign_prepend_trigger_offer', $offers, $campaign, $current_product );
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

		if ( $is_product_picker || $is_x_product ) {
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

		if ( $is_grid_view || $is_upsell_slider ) {
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
		$slider_columns         = wp_json_encode( self::get_slider_data( $template_data ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
		$products_wrapper_class = 'grid' === $view_mode ? 'revx-slider-wrapper' : '';
		$is_grid_view           = 'grid' === $view_mode;

		$is_bundle         = 'bundle_discount' === $campaign['campaign_type'];
		$is_product_picker = self::uses_product_picker( $campaign['campaign_type'] );
		$is_buy_x_get_y    = 'buy_x_get_y' === $campaign['campaign_type'];
		$is_upsell_slider  = self::uses_upsell_slider( $campaign['campaign_type'] );
		$is_divider        = self::uses_item_divider( $campaign['campaign_type'] );

		$element_id = 'productsWrapper';
		if ( $is_upsell_slider ) {
			$status_key = self::get_upsell_status_key( $campaign['campaign_type'] );
			if ( 'yes' !== ( $campaign[ $status_key ] ?? '' ) ) {
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
						echo $is_upsell_slider ? '' : esc_attr( $view_mode );
						echo ' ' . esc_attr( $products_wrapper_class );
						echo ' ' . ( $is_grid_view || $is_upsell_slider ? 'revx-slider-wrapper' : 'revx-flex-column' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings.
						echo ' ' . ( $is_divider ? 'revx-revx-product-body-scroll' : '' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings.
						echo ' revx-items-wrapper revx-scrollbar-common';
					}
					?>
				"
				data-slider-columns='<?php echo esc_attr( $slider_columns ); ?>'
				data-layout='<?php echo esc_attr( $display_style ); ?>'
			>
			<?php
			if ( $is_grid_view || $is_upsell_slider ) {
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
						$is_product_picker,
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
						$is_product_picker,
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
					$is_product_picker,
					false,
					false,
					$is_divider,
					$is_bundle,
					$view_mode,
					$is_grid_view,
					$product
				);
			}
			if ( $is_grid_view || $is_upsell_slider || $is_buy_x_get_y ) {
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
								$is_product_picker,
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
		$slider_columns         = wp_json_encode( self::get_slider_data( $template_data ), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP );
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
						echo ' ' . ( $is_grid_view ? 'revx-slider-wrapper' : 'revx-flex-column' ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings.
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
	 * It handles different campaign types such as bundle discounts, and calculates
	 * total prices based on the campaign's offer details.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $campaign         The campaign data array. Must include 'id' and 'campaign_type'.
	 * @param array  $template_data    The campaign template data used for rendering.
	 * @param string $footer_id       The identifier for the footer element in the template.
	 *
	 * @return void Outputs HTML directly.
	 */
	public static function render_products_footer( $campaign, $template_data, $footer_id ) {
		$campaign_type       = $campaign['campaign_type'];
		$campaign_id         = $campaign['id'];
		$is_bundle           = 'bundle_discount' === $campaign['campaign_type'];
		$outer_footer        = 'volume_discount' === $campaign_type;
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

		$offer = array();

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
						revx-justify-<?php echo $is_center ? 'between' : 'center';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>"
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
					<?php
						echo wp_kses_post( self::render_rich_text( $template_data, 'totalText' ) );
						self::revenue_render_product_price(
							$price_array,
							'list',
							$template_data
						);
					?>
					</div>
				</div>
					<?php if ( ! $outer_footer ) { ?>
					<div
						class="<?php echo esc_attr( self::get_element_class( $template_data, 'bundleCartWrapper' ) ); ?> 
								revx-d-flex revx-parent-btn
								revx-item-center 
								revx-justify-<?php echo $is_center ? 'between' : 'center';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>"
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
							border-<?php echo $is_rtl ? 'left' : 'right';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>: inherit;
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
							border-<?php echo $is_rtl ? 'right' : 'left';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>: inherit;
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
		$hide_cart           = self::hides_add_to_cart( $campaign['campaign_type'] ) || $hide_add_to_cart;
		?>
		<div
			class="revx-product-cart-box <?php echo esc_attr( self::get_element_class( $template_data, 'productCartBox' ) ); ?> revx-d-flex revx-item-center <?php echo $no_wrap ? '' : 'revx-flex-wrap'; ?> <?php echo 'grid' === $layout ? 'revx-slider-center' : ''; ?>"
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
				<?php echo $no_wrap ? '' : 'revx-flex-wrap';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?> 
				<?php echo $is_list_layout ? '' : 'revx-slider-center';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?> 
				<?php echo $is_enable_tag ? 'revx-tag-text-color' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>
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

		// Map every way an attribute can reach this function to the data we
		// actually need: the canonical key WooCommerce expects to be posted,
		// the real taxonomy name and the human readable label.
		//
		// The taxonomy name cannot be rebuilt from the posted key: WooCommerce
		// keeps taxonomy names in raw UTF-8 (wc_sanitize_taxonomy_name() url
		// decodes), while variation meta keys are sanitize_title()'d. For a
		// Hebrew attribute the taxonomy is "pa_צבע" but the posted key is
		// "attribute_pa_%d7%a6%d7%91%d7%a2", so taxonomy_exists() on anything
		// derived from the key always fails. Ask the product instead.
		$attribute_info = array();
		if ( $parent && $parent->is_type( 'variable' ) ) {
			foreach ( $parent->get_attributes() as $attribute ) {
				if ( ! $attribute->get_variation() ) {
					continue;
				}

				$attribute_taxonomy = $attribute->get_name();
				$sanitized_name     = sanitize_title( $attribute_taxonomy );

				$info = array(
					'key'      => 'attribute_' . $sanitized_name,
					'taxonomy' => $attribute->is_taxonomy() ? $attribute_taxonomy : '',
					'label'    => wc_attribute_label( $attribute_taxonomy, $parent ),
				);

				// Every alias the option list can be keyed by upstream.
				$aliases = array(
					'attribute_' . $sanitized_name,
					$sanitized_name,
					$attribute_taxonomy,
					str_replace( 'pa_', '', $sanitized_name ),
				);
				foreach ( $aliases as $alias ) {
					if ( '' !== $alias && ! isset( $attribute_info[ $alias ] ) ) {
						$attribute_info[ $alias ] = $info;
					}
				}
			}
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
				// The real taxonomy from the product comes first so non latin
				// attribute names resolve; the guesses stay as a fallback.
				$candidates = array();
				if ( isset( $attribute_info[ $attr_key ]['taxonomy'] ) && '' !== $attribute_info[ $attr_key ]['taxonomy'] ) {
					$candidates[] = $attribute_info[ $attr_key ]['taxonomy'];
				}
				if ( 0 === strpos( $raw, 'attribute_' ) ) {
					$candidates[] = substr( $raw, 10 ); // strip 'attribute_'.
				} else {
					$candidates[] = $raw;
				}
				$candidates[] = 'pa_' . $clean;
				$candidates[] = $clean;

				foreach ( $candidates as $tax ) {
					if ( taxonomy_exists( $tax ) ) {
						$terms = wc_get_product_terms( $product_id, $tax, array( 'fields' => 'all' ) );
						if ( ! empty( $terms ) ) {
							foreach ( $terms as $term ) {
								foreach ( $opts as $opt ) {
									// The option can be either the slug or the
									// name depending on where it came from.
									if ( 0 === strcasecmp( $opt, $term->slug ) || 0 === strcasecmp( $opt, $term->name ) ) {
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
		// The same attribute can reach this point under two key shapes at once
		// (the variation meta key and the bare slug the templates build), which
		// would render the same dropdown twice. Collapse them onto the key
		// WooCommerce expects, keeping the first non empty option list.
		$normalized_attributes = array();
		foreach ( $attributes as $attr => $options ) {
			$canonical = isset( $attribute_info[ $attr ] ) ? $attribute_info[ $attr ]['key'] : $attr;

			if ( empty( $normalized_attributes[ $canonical ] ) ) {
				$normalized_attributes[ $canonical ] = $options;
			}
		}
		$attributes = $normalized_attributes;

		foreach ( $attributes as $attr => $options ) :
			$info = isset( $attribute_info[ $attr ] ) ? $attribute_info[ $attr ] : null;

			// Always post the key WooCommerce expects. Option lists do not all
			// arrive with the same key shape (some come from the variation meta,
			// some are built by the templates), and anything that does not start
			// with "attribute_" is skipped by the front-end script.
			$attribute_name = $info ? $info['key'] : strtolower( $attr );
			$attr_key       = str_replace( 'attribute_', '', $attribute_name );
			$attr_taxonomy  = $info ? $info['taxonomy'] : '';

			$default_option = null;
			if ( isset( $default_attributes ) && isset( $default_attributes[ $attr_key ] ) ) {
				$default_option = $default_attributes[ $attr_key ];
			}

			if ( $info && '' !== $info['label'] ) {
				$label = $info['label'];
			} else {
				// remove any kind of prefixes before the actual attribute name. Add more if any case found.
				$prefixes = array( 'attribute_', 'pa_' );
				// use label either with default option name or seperate label tag. Convert to lower for consistency. Can be capitalized.
				$label = str_replace( $prefixes, '', strtolower( $attr ) );
			}
			?>
				<div class="<?php echo esc_attr( self::get_element_class( $template_data, 'productAttributeField' ) ); ?> revx-relative revx-w-<?php echo esc_attr( 'grid' === $layout ? 'full' : 'fit' ); ?> revx-d-flex revx-item-center">
					<select class="revx-product-Attr-wrapper <?php echo esc_attr( $layout ); ?> <?php echo esc_attr( $is_enable_tag ? 'revx-tag-border revx-tag-bg revx-tag-text-color' : '' ); ?>" id="productAttributeSelect_<?php echo esc_attr( $product_id . '_' . $attr ); ?>"
						name="<?php echo esc_attr( $attribute_name ); ?>" 
						data-attribute_name="<?php echo esc_attr( $attribute_name ); ?>" 
						data-show_option_none="yes"
						data-options="<?php echo esc_attr( wp_json_encode( $options ) ); ?>" 
					>
						<option value="" ><?php echo esc_html__( 'Select', 'revenue' ); ?> <?php echo esc_html( $label ); ?></option>
						<?php
						foreach ( $options as $option ) :
							$option_value = $option;
							$option_label = $option;

							// Map the option to a taxonomy term so the value is the
							// term slug (what WooCommerce validates against) and
							// the visible label is the term name. The taxonomy
							// comes from the product itself, because it cannot be
							// rebuilt from the sanitized attribute key when the
							// attribute name is not latin.
							$tax_candidates = array( $attr_taxonomy, 'pa_' . $attr_key, $attr_key );
							foreach ( $tax_candidates as $tax ) {
								if ( '' !== $tax && taxonomy_exists( $tax ) ) {
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
							if ( null !== $default_option && ( $default_option == $option_value || $default_option == $option_label ) ) {
								$is_selected = 'selected';
							}
							?>
							<option 
								value="<?php echo esc_attr( $option_value ); ?>" 
								<?php echo esc_attr( $is_selected ); ?>
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
	 * @param bool         $is_required     Whether the offer item is a required product. Default false.
	 * @param bool         $width_image    Whether to reserve width for an image. Default false.
	 * @param bool         $is_checked     Whether the checkbox should be rendered in checked/active state. Default false.
	 * @return string HTML for the checkbox wrapper.
	 */
	public static function render_checkbox( $template_data, $is_left = true, $is_enable_tag = false, $is_required = false, $width_image = false, $is_checked = false ) {
		ob_start();
		?>
		<div class="<?php echo $is_enable_tag ? 'revx-tag-text-color' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?> 
				<?php echo $is_left ? 'revx-checkbox-left' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?> 
				<?php echo $width_image ? 'revx-with-image' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?> 
				<?php echo $is_required ? 'revx-required-product' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?> revx-checkbox-wrapper">
			<div class="revx-checkbox-container revx-<?php echo ( $is_required || $is_checked ) ? 'active' : 'inactive';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>" style="font-size: 24px">
				<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" fill="none" viewBox="0 0 16 16">
					<rect width="11.8" height="11.8" x="2.102" y="2.1"
						stroke="var(--revx<?php echo $is_enable_tag ? '-tag' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>-checkbox-bg-color,#000000)"
						rx="2.7"></rect>
					<rect width="11.4" height="11.4" x="2.502" y="2.5"
						fill="var(--revx<?php echo $is_enable_tag ? '-tag' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>-checkbox-bg-color,#000000)"
						class="revx-checkbox-inactive"
						rx="2"></rect>
					<path stroke="var(--revx<?php echo $is_enable_tag ? '-tag' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>-checkbox-icon-color,#fff)"
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
		$is_product_picker   = self::uses_product_picker( $campaign['campaign_type'] );
		$is_bundle           = 'bundle_discount' === $campaign['campaign_type'];
		$is_enable_tag       = isset( $pd['isEnableTag'] ) && 'yes' === $pd['isEnableTag'];
		$is_list_layout      = 'list' === $layout;
		$is_skip_add_to_cart = 'yes' === $campaign['skip_add_to_cart'];
		$is_go_to_product    = 'go_to_product' === $campaign['offered_product_click_action'];

		$template_two = self::uses_card_template_two( $campaign['campaign_type'] );
		$is_up_sell   = self::uses_upsell_slider( $campaign['campaign_type'] );

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
				<?php echo $data_price_attributes; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each attribute value is esc_attr()'d when built in get_data_price_attributes(). ?>
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
				<div class="revx-product-image <?php echo ( $is_up_sell ) ? 'revx-h-full' : '';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>">
					<?php
					if ( self::uses_offer_checkbox( $campaign['campaign_type'] ) ) {
						$is_required = isset( $offer['is_required_product'] ) ? $offer['is_required_product'] : false;
						echo wp_kses( self::render_checkbox( $template_data, $is_list_layout, $is_enable_tag, $is_required, ! $is_list_layout ), revenue()->get_allowed_tag() );
					}
					if ( $is_go_to_product ) {
						echo '<a target="_blank" href="' . esc_url( get_permalink( $product_id ) ) . '">';
						echo wp_kses_post( $image_html );
						echo '</a>';
					} else {
						echo wp_kses_post( $image_html );
					}
					if ( $is_product_picker && 'grid' === $layout ) {
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
							$is_bundle || $is_product_picker || $is_up_sell,
							false,
							'',
							$is_product_picker
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
					if ( $template_two && ! ( 'grid' === $layout && $is_product_picker ) ) {
						echo '<div class="' . esc_attr( $class_layout ) . '">';
					}
					self::revenue_render_product_cart(
						$pd,
						$campaign,
						$template_data,
						$add_cart_id,
						false,
						$is_product_picker,
						$layout
					);
					if ( $template_two && ! ( 'grid' === $layout && $is_product_picker ) ) {
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

		$template_two = self::uses_card_template_two( $campaign['campaign_type'] );
		$is_up_sell   = self::uses_upsell_slider( $campaign['campaign_type'] );

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
			<?php echo $data_price_attributes; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each attribute value is esc_attr()'d when built in get_data_price_attributes(). ?>
			data-product-qty="<?php echo esc_attr( $product_quantity ); ?>"
			class="
				<?php echo 'revx-' . esc_attr( $campaign_type ) . '-add-to-cart'; ?> 
				<?php echo esc_attr( $class ); ?> 
				revx-relative revx-product-layout 
				<?php echo esc_attr( $extra_class ); ?> 
				<?php echo $is_enable_tag ? 'revx-tag-bg revx-tag-border' : 'revx-campaign-product-card';  //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hardcoded two-branch ternary, both branches literal strings. ?>
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
			$class = (string) apply_filters( 'revenue_campaign_add_to_cart_button_class', $class, $campaign_type, $element_id );

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

		return '';
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
		if ( ! is_array( $data ) || ! $element_id ) {
			return;
		}

		$buffer_level = ob_get_level();
		try {
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

			$button_class = $args['class'] . $animation_class;

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

					$element      = isset( $data[ $element_id ] ) ? $data[ $element_id ] : array();
					$text_element = isset( $data[ $element_id . 'RichText' ] ) ? $data[ $element_id . 'RichText' ] : array();

					$class = self::get_element_data( $element, 'className' );

					$text = $args['text'] ? $args['text'] : self::get_element_data( $text_element, 'text' );

					$text = apply_filters( 'revenue_apply_rich_text_smart_tags', $text, $element );
					echo wp_kses_post( self::as_string_or_empty( $text ) );
					?>
				</div>
				<?php
				if ( $url ) {
					echo '</a>';
				}
				return ob_get_clean();
		} finally {
			while ( ob_get_level() > $buffer_level ) {
				ob_end_clean();
			}
		}
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
				<?php echo wp_kses_post( self::as_string_or_empty( $text ) ); ?>
			</div>
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
	 * Guard against non-string input before it is passed to wp_kses_post().
	 *
	 * @param string $text Text to check.
	 * @return string The original input, or an empty string if it isn't a string.
	 */
	private static function as_string_or_empty( $text = 'This is dummy text' ) {
		if ( ! is_string( $text ) ) {
			return '';
		}
		return $text;
	}

	/**
	 * Sanitize a value before it is interpolated into a CSS declaration.
	 *
	 * Settings are stored without a schema, so this is the last line of defense
	 * against stored CSS injection (e.g. a value like "red; } body { display:none; }").
	 * Allows only characters real color/dimension/gradient values use.
	 *
	 * @param mixed $value Raw setting value.
	 * @return string Safe CSS value, or empty string if not scalar.
	 */
	private static function sanitize_css_value( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		return preg_replace( '/[^a-zA-Z0-9#.,%\-() ]/', '', (string) $value );
	}

	/**
	 * Sanitize a value before it is used as (part of) a CSS custom property name.
	 *
	 * @param mixed $value Raw setting key/value.
	 * @return string Safe CSS identifier fragment.
	 */
	private static function sanitize_css_identifier( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		return preg_replace( '/[^a-zA-Z0-9_-]/', '', (string) $value );
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
						$kebab_property = self::sanitize_css_identifier( strtolower( preg_replace( '/([a-z0-9])([A-Z])/', '$1-$2', $property ) ) );
						$safe_value     = self::sanitize_css_value( $value );
						$css_value      = 'fontSize' === $property ? "{$safe_value}px" : $safe_value;

						$output .= '    --revx-' . self::sanitize_css_identifier( $style_name ) . "-{$kebab_property}: {$css_value};\n";
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
		$is_stock_scarcity = 'stock_scarcity' === $campaign['campaign_type'];
		$template_data     = revenue()->get_campaign_meta( $campaign['id'], 'builder', true );

		// Campaign types that draw step markers ask for room under the bar.
		$has_label = (bool) apply_filters( 'revenue_campaign_progressbar_has_label', false, $campaign );

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
			$template_data['is_rtl'] = $is_rtl;
			do_action( 'revenue_render_progressbar_markers', $campaign, $progress, $template_data );
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
				$theme_vars .= '--revx-theme-base-primary: ' . self::sanitize_css_value( $themes_data['base']['primary'] ) . ";\n";
			}
			if ( isset( $themes_data['base']['secondary'] ) ) {
				$theme_vars .= '--revx-theme-base-secondary: ' . self::sanitize_css_value( $themes_data['base']['secondary'] ) . ";\n";
			}

			if ( isset( $themes_data['base']['shades'] ) && is_array( $themes_data['base']['shades'] ) ) {
				foreach ( $themes_data['base']['shades'] as $index => $shade ) {
					$theme_vars .= '--revx-theme-shade-' . ( (int) $index + 1 ) . ': ' . self::sanitize_css_value( $shade ) . ";\n";
				}
			}
		}

		if ( isset( $themes_data['accent'] ) && is_array( $themes_data['accent'] ) ) {
			foreach ( $themes_data['accent'] as $key => $value ) {
				$theme_vars .= '--revx-theme-accent-' . self::sanitize_css_identifier( $key ) . ': ' . self::sanitize_css_value( $value ) . ";\n";
			}
		}

		return $theme_vars;
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
					<?php
						echo wp_kses( $output_content, revenue()->get_allowed_tag() );
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
				<?php echo wp_kses( self::render_campaign_close( $template_data, 'revx-close-right' ), revenue()->get_allowed_tag() ); ?>
					<div id="revx-floating" class="revx-floating revx-campaign-container <?php echo esc_attr( $class ); ?> revx-campaign-<?php echo esc_attr( $view_mode ); ?>" data-campaign-id="<?php echo esc_attr( $current_campaign['id'] ); ?>" >
					<?php
					echo wp_kses( $output_content, revenue()->get_allowed_tag() );
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
				<?php echo esc_attr( apply_filters( 'revenue_campaign_inpage_container_class', '', $campaign_type ) ); ?>" 
		>
			<?php
			echo wp_kses( $output_content, revenue()->get_allowed_tag() );
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
}
