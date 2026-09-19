<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * @package Revenue
 */

namespace Revenue;

//phpcs:disable WordPress.PHP.StrictInArray.MissingTrueStrict, WordPress.PHP.StrictComparisons.LooseComparison

/**
 * WowRevenue Campaign: Volume Discount
 *
 * This class handles the volume discount campaigns, including setting discount prices on the cart,
 * rendering views for different campaign types (in-page, popup, floating), and processing shortcodes.
 *
 * @hooked on init
 */
class Revenue_Volume_Discount {
	use SingletonTrait;

	/**
	 * Stores the campaigns to be rendered on the page.
	 *
	 * @var array|null $campaigns
	 *    An array of campaign data organized by view types (e.g., in-page, popup, floating),
	 *    or null if no campaigns are set.
	 */
	public $campaigns = array();

	/**
	 * Keeps track of the current position for rendering in-page campaigns.
	 *
	 * @var string $current_position
	 *    The position within the page where in-page campaigns should be displayed.
	 *    Default is an empty string, indicating no position is set.
	 */
	public $current_position = '';

	/**
	 * Defines the type of campaign being handled.
	 *
	 * @var string $campaign_type
	 *    The type of campaign, typically used to categorize or filter campaigns.
	 *    Default value is 'volume_discount'.
	 */
	public $campaign_type = 'volume_discount';


	/**
	 * Initializes the class by setting up necessary hooks.
	 *
	 * This method adds actions related to the volume discount campaign type, such as setting the discounted
	 * price on the cart before calculating totals.
	 *
	 * @return void
	 */
	public function init() {

		// Set Discounted Price on Cart Before Calculate Totals.
		add_action( "revenue_campaign_{$this->campaign_type}_before_calculate_cart_totals", array( $this, 'set_price_on_cart' ), 10, 2 );
		add_filter( "revenue_campaign_{$this->campaign_type}_cart_item_price", array( $this, 'cart_item_price' ), 9999, 2 );
	}


	/**
	 * Sets the discounted price for a cart item based on the active campaign.
	 *
	 * This method calculates the offered price for a cart item based on the campaign's offer type and value.
	 * It updates the item's price in the cart accordingly.
	 *
	 * @param array $cart_item    The cart item data.
	 * @param int   $campaign_id  The ID of the campaign applied to the cart item.
	 *
	 * @return void
	 */
	public function set_price_on_cart( $cart_item, $campaign_id ) {

		$campaign_id   = intval( $cart_item['revx_campaign_id'] );
		$offers        = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
		$product_id    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$parent_id     = $cart_item['product_id'];
		$variation_id  = $cart_item['variation_id'];
		$cart_quantity = $cart_item['quantity'];

		$regular_price = $cart_item['data']->get_regular_price( 'edit' );
		$sale_price    = $cart_item['data']->get_sale_price( 'edit' );

		// Extension Filter: Sale Price Addon.
		$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );
		// based on extension filter use sale price or regular price for calculation.
		$offered_price = $filtered_price;

		$is_multiple_variation = 'yes' === ( $cart_item['revx_multiple_variation'] ?? 'no' );
		if ( is_array( $offers ) ) {
			$offer_type         = '';
			$offer_value        = '';
			$offer_qty          = '';
			$offered_products[] = $product_id;

			if ( $is_multiple_variation ) {
				$cart_quantity = $this->get_cart_quantity_of_product( $campaign_id, $parent_id );
			}
			foreach ( $offers as $offer ) {

				if ( in_array( $product_id, $offered_products ) && $offer['quantity'] <= $cart_quantity ) {
					$offer_type  = $offer['type'];
					$offer_value = isset( $offer['value'] ) ? $offer['value'] : null;
					$offer_qty   = intval( $offer['quantity'] );
				}
			}
		}

			// @todo if 'free' is never possible remove this codition.
		if ( $offer_type && ( 'free' === $offer_type || $offer_value ) ) {

			if ( 'fixed_total_price' === $offer_type ) {
				if ( $offer_qty <= $cart_quantity ) {
					$offered_price = revenue()->calculate_campaign_offered_price(
						$offer_type,
						$offer_value,
						$filtered_price,
						false,
						1,
						'volume_discount'
					);
					$offered_price = $offered_price / $offer_qty;
				}
			} else {
				$offered_price = revenue()->calculate_campaign_offered_price(
					$offer_type,
					$offer_value,
					$filtered_price
				);
			}
		}

		$offered_price = apply_filters( 'revenue_campaign_volume_discount_price', $offered_price, $product_id );

		$cart_item['data']->set_price( $offered_price );
	}

	/**
	 * Get Discounted Price
	 *
	 * @param array $cart_item Cart Item.
	 *
	 * @return float
	 */
	public function get_discounted_price( $cart_item ) {
		$campaign_id   = intval( $cart_item['revx_campaign_id'] );
		$offers        = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
		$product_id    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$parent_id     = $cart_item['product_id'];
		$variation_id  = $cart_item['variation_id'];
		$cart_quantity = $cart_item['quantity'];
		$offer_qty     = '';

		$regular_price = $cart_item['data']->get_regular_price( 'edit' );
		$sale_price    = $cart_item['data']->get_sale_price( 'edit' );

		// Extension Filter: Sale Price Addon.
		$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );
		// based on extension filter use sale price or regular price for calculation.
		$offered_price = $filtered_price;

		$product = $cart_item['data'];

		// Get WooCommerce tax display setting.
		$tax_display = get_option( 'woocommerce_tax_display_cart', 'incl' );

		// Adjust base offered price based on tax setting.
		if ( 'incl' === $tax_display ) {
			$offered_price = wc_get_price_including_tax( $product, array( 'price' => $offered_price ) );
		} else {
			$offered_price = wc_get_price_excluding_tax( $product, array( 'price' => $offered_price ) );
		}

		if ( is_array( $offers ) ) {
			$offer_type         = '';
			$offer_value        = '';
			$offered_products[] = $product_id;

			$is_multiple_variation = 'yes' === ( $cart_item['revx_multiple_variation'] ?? 'no' );
			if ( $is_multiple_variation ) {
				$cart_quantity = $this->get_cart_quantity_of_product( $campaign_id, $parent_id );
			}
			foreach ( $offers as $offer ) {
				if ( in_array( $product_id, $offered_products ) && $offer['quantity'] <= $cart_quantity ) {
					$offer_type  = $offer['type'];
					$offer_value = $offer['value'];
					$offer_qty   = intval( $offer['quantity'] );
				}
			}

			if ( $offer_type && ( 'free' === $offer_type || $offer_value ) ) {

				if ( 'fixed_total_price' === $offer_type ) {
					if ( $offer_qty <= $cart_quantity ) {
						$offered_price = revenue()->calculate_campaign_offered_price(
							$offer_type,
							$offer_value,
							$filtered_price,
							false,
							$offer_qty,
							'volume_discount'
						);
						// Ignoring particular case which results in round up for corner case like 17.999 = 18.00
						// where quantity is 10 and total price is 179.99 . If modification needed, handle with care.
						$offered_price = floor( $offered_price * pow( 10, wc_get_price_decimals() ) ) / pow( 10, wc_get_price_decimals() );
					}
				} else {
					$offered_price = revenue()->calculate_campaign_offered_price(
						$offer_type,
						$offer_value,
						$filtered_price
					);
				}

				// Apply tax to final offered price based on WooCommerce setting.
				if ( 'incl' === $tax_display ) {
					$offered_price = wc_get_price_including_tax( $product, array( 'price' => $offered_price ) );
				} else {
					$offered_price = wc_get_price_excluding_tax( $product, array( 'price' => $offered_price ) );
				}
			}

			$offered_price = apply_filters( 'revenue_campaign_volume_discount_price', $offered_price, $product_id );
			return $offered_price;
		}
	}

	/**
	 * Filters the cart item price to display the discounted price.
	 *
	 * This method filters the cart item price to display the discounted price if the cart item has a campaign ID.
	 *
	 * @param string $subtotal   The cart item price.
	 * @param array  $cart_item  The cart item data.
	 *
	 * @return string
	 */
	public function cart_item_price( $subtotal, $cart_item ) {
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$subtotal      = $this->get_discounted_price( $cart_item );
			$tax_display   = get_option( 'woocommerce_tax_display_cart', 'incl' );
			$regular_price = 'incl' === $tax_display ? wc_get_price_including_tax( $cart_item['data'], array( 'price' => $cart_item['data']->get_regular_price() ) ) : $cart_item['data']->get_regular_price();

			if ( $regular_price != $subtotal ) {
				return '<del>' . wc_price( $regular_price ) . '</del> ' . wc_price( $subtotal );
			}
			$subtotal = wc_price( $subtotal );
		}

		return $subtotal;
	}

	/**
	 * Get the total quantity of a specific product(including different variations) in the cart
	 * that is associated with the given campaign.
	 *
	 * Iterates through the WooCommerce cart items and sums the quantities
	 * of items where the product ID matches the provided parent product ID
	 * and the item is tagged with the specified campaign ID.
	 *
	 * @param int $campaign_id The campaign ID to match against the cart item meta.
	 * @param int $parent_id   The parent product ID to match against the cart item product ID.
	 *
	 * @return int The total quantity of the matched product for the given campaign in the cart.
	 */
	private function get_cart_quantity_of_product( $campaign_id, $parent_id ) {
		$cart_quantity = 0;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$is_same_product  = absint( $cart_item['product_id'] ) === absint( $parent_id );
			$is_same_campaign = isset( $cart_item['revx_campaign_id'] ) &&
				absint( $cart_item['revx_campaign_id'] ) === absint( $campaign_id );

			if ( $is_same_product && $is_same_campaign ) {
				$cart_quantity += $cart_item['quantity'];
			}
		}
		return $cart_quantity;
	}

	/**
	 * Outputs in-page views for the provided campaigns.
	 *
	 * This method processes and renders in-page views based on the provided campaigns.
	 * It adds each campaign to the `inpage` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data      An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function output_inpage_views( $campaigns, $data = array() ) {
		foreach ( $campaigns as $campaign ) {
			$this->campaigns['inpage'][ $data['position'] ][] = $campaign;
		}
		$this->current_position = $data['position'];
		$this->render_views( $data );
	}

	/**
	 * Outputs popup views for the provided campaigns.
	 *
	 * This method processes and renders popup views based on the provided campaigns.
	 * It adds each campaign to the `popup` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data      An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function output_popup_views( $campaigns, $data = array() ) {
		foreach ( $campaigns as $campaign ) {
			$this->campaigns['popup'][] = $campaign;
		}
		$this->render_views( $data );
	}

	/**
	 * Outputs floating views for the provided campaigns.
	 *
	 * This method processes and renders floating views based on the provided campaigns.
	 * It adds each campaign to the `floating` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data      An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function output_floating_views( $campaigns, $data = array() ) {
		foreach ( $campaigns as $campaign ) {
			$this->campaigns['floating'][] = $campaign;
		}
		$this->render_views( $data );
	}

	/**
	 * Renders and outputs views for the campaigns.
	 *
	 * This method generates HTML output for different types of campaign views:
	 * - In-page views
	 * - Popup views
	 * - Floating views
	 *
	 * It includes the respective PHP files for each view type and processes them.
	 * The method also enqueues necessary scripts and styles for popup and floating views.
	 *
	 * @param array $data An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function render_views( $data = array() ) {
		if ( ! empty( $this->campaigns['inpage'][ $this->current_position ] ) ) {

			$campaigns = $this->campaigns['inpage'][ $this->current_position ];
			foreach ( $campaigns as $campaign ) {

				$output = '';
				revenue()->update_campaign_impression( $campaign['id'] );

				// $file_path = REVENUE_PATH . 'includes/campaigns/views/volume-discount/template1.php';
				$file_path = revenue()->get_campaign_path( $campaign, 'inpage', 'volume-discount' );

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'volume_discount', 'inpage', $campaign );

				if ( file_exists( $file_path ) ) {
					do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );

					extract( $data ); //phpcs:ignore
					include $file_path;
				}
			}
		}

		if ( ! empty( $this->campaigns['popup'] ) ) {

			// wp_enqueue_script( 'revenue-popup' );
			// wp_enqueue_style( 'revenue-popup' );

			$output_popups = '';
			$campaigns     = $this->campaigns['popup'];
			foreach ( $campaigns as $campaign ) {
				$current_campaign = $campaign;
				$output           = '';

				revenue()->update_campaign_impression( $campaign['id'] );

				revenue()->load_popup_assets( $campaign );

				$file_path = revenue()->get_campaign_path( $campaign, 'popup', 'volume-discount' );

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'volume_discount', 'popup', $campaign );
				do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
				ob_start();
				if ( file_exists( $file_path ) ) {
					extract($data); //phpcs:ignore
					include $file_path;
				}

				$output_popups .= ob_get_clean();
			}

			if ( $output_popups ) {
				echo wp_kses( $output_popups, revenue()->get_allowed_tag() );
				$output_popups = '';
			}
		}
		if ( ! empty( $this->campaigns['floating'] ) ) {

			$output_floatings = '';
			$campaigns        = $this->campaigns['floating'];
			foreach ( $campaigns as $campaign ) {

				revenue()->load_floating_assets( $campaign );

				revenue()->update_campaign_impression( $campaign['id'] );

				$file_path = revenue()->get_campaign_path( $campaign, 'floating', 'volume-discount' );

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'volume_discount', 'floating', $campaign );

				do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
				ob_start();
				if ( file_exists( $file_path ) ) {
					extract($data); //phpcs:ignore
					include $file_path;
				}

				$output_floatings .= ob_get_clean();
			}

			if ( $output ) {
				echo wp_kses( $output_floatings, revenue()->get_allowed_tag() );
				$output_floatings = '';
			}
		}
	}
}
