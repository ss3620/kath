<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * @package Revenue
 */

namespace Revenue;

//phpcs:disable WordPress.PHP.StrictInArray.MissingTrueStrict, WordPress.PHP.StrictComparisons.LooseComparison


/**
 * WowRevenue Campaign: Normal Discount
 *
 * @hooked on init
 */
class Revenue_Normal_Discount {
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
	 * Stores the IDs of campaigns that have already been rendered to prevent duplicate rendering.
	 *
	 * @var array $rendered_campaign_ids
	 */
	private $rendered_campaign_ids = array();
	/**
	 * Keeps track of the current position for rendering in-page campaigns.
	 *
	 * @var string $current_position
	 *    The position within the page where in-page campaigns should be displayed.
	 *    Default is an empty string, indicating no position is set.
	 */
	public $current_position = '';

	/**
	 * Initializes the class.
	 */
	public function init() {

		// Set Discounted Price on Cart Before Calculate Totals.
		add_action( 'revenue_campaign_normal_discount_before_calculate_cart_totals', array( $this, 'set_price_on_cart' ), 10, 2 );
		add_filter( 'revenue_campaign_normal_discount_cart_item_price', array( $this, 'cart_item_price' ), 9999, 2 );
	}

	/**
	 * Retrieves the offer products for a given campaign.
	 *
	 * @param int $campaign_id The ID of the campaign.
	 * @return array An array of offer product data.
	 */
	public function get_offer_products( $campaign_id ) {
		$offer_data = array();

		$offers = revenue()->get_campaign_meta( $campaign_id, 'offers', true );

		foreach ( $offers as $offer ) {
			$offered_product_ids = $offer['products'] ?? array();
			$offer_qty           = $offer['quantity'] ?? '';
			$offer_value         = $offer['value'] ?? '';
			$offer_type          = $offer['type'] ?? '';
			$is_tag_enabled      = isset( $offer['isEnableTag'] ) && 'yes' === $offer['isEnableTag'];

			foreach ( $offered_product_ids as $offer_product_id ) {
				$offered_product = wc_get_product( $offer_product_id );
				if ( ! $offered_product || ! $offered_product->is_in_stock() ) {
					continue;
				}
				if ( revenue()->is_hide_product( $campaign_id, $offer_product_id ) ) {
					continue;
				}

				$image = wp_get_attachment_image_src( get_post_thumbnail_id( $offered_product->get_id() ), 'single-post-thumbnail' ) ?: array( wc_placeholder_img_src() );

				// Add the product data to the offer_data array.
				$offer_data[] = array(
					'title'          => $offered_product->get_title(),
					'thumbnail'      => $image[0],  // Corrected this line.
					'regular_price'  => $offered_product->get_regular_price(),
					'sale_price'     => $offered_product->get_sale_price(),
					'min_qty'        => $offer_qty,
					'discount_type'  => $offer_type,
					'discount_value' => $offer_value,
				);
			}
		}

		return $offer_data;
	}

	/**
	 * Outputs in-page views for a list of campaigns.
	 *
	 * This method processes and renders in-page views based on the provided campaigns.
	 * It adds each campaign to the `inpage` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function output_inpage_views( $campaigns, $data ) {
		foreach ( $campaigns as $campaign ) {
			$this->campaigns['inpage'][ $data['position'] ][] = $campaign;
		}
		$this->current_position = $data['position'];
		do_action( 'revenue_campaign_normal_discount_inpage_before_render_content' );
		$this->render_views( $data );
		do_action( 'revenue_campaign_normal_discount_inpage_after_render_content' );
	}

	/**
	 * Outputs popup views for a list of campaigns.
	 *
	 * This method processes and renders popup views based on the provided campaigns.
	 * It adds each campaign to the `popup` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data An array of data to be passed to the view.
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
	 * Outputs floating views for a list of campaigns.
	 *
	 * This method processes and renders floating views based on the provided campaigns.
	 * It adds each campaign to the `floating` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function output_floating_views( $campaigns, $data ) {
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
		// Initialize rendered campaign ID tracker if not already.
		if ( ! isset( $this->rendered_campaign_ids ) ) {
			$this->rendered_campaign_ids = array();
		}

		// In-page rendering.
		if ( ! empty( $this->campaigns['inpage'][ $this->current_position ] ) ) {
			$campaigns = $this->campaigns['inpage'][ $this->current_position ];

			foreach ( $campaigns as $campaign ) {
				if ( in_array( $campaign['id'], $this->rendered_campaign_ids, true ) ) {
					continue;
				}
				$this->rendered_campaign_ids[] = $campaign['id'];

				revenue()->update_campaign_impression( $campaign['id'] );
				$file_path = revenue()->get_campaign_path( $campaign, 'inpage', 'normal-discount' );

				$file_path = apply_filters(
					'revenue_campaign_view_path',
					$file_path,
					'normal_discount',
					'inpage',
					$campaign
				);

				if ( file_exists( $file_path ) ) {
					do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
					extract( $data ); //phpcs:ignore
					include $file_path;
				}
			}
		}

		// Popup rendering.
		if ( ! empty( $this->campaigns['popup'] ) ) {

			foreach ( $this->campaigns['popup'] as $campaign ) {
				if ( in_array( $campaign['id'], $this->rendered_campaign_ids, true ) ) {
					continue;
				}
				$this->rendered_campaign_ids[] = $campaign['id'];

				revenue()->load_popup_assets( $campaign );

				revenue()->update_campaign_impression( $campaign['id'] );
				$current_campaign = $campaign;

				$file_path = revenue()->get_campaign_path( $campaign, 'popup', 'normal-discount' );

				$file_path = apply_filters(
					'revenue_campaign_view_path',
					$file_path,
					'normal_discount',
					'popup',
					$campaign
				);

				if ( file_exists( $file_path ) ) {
					do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
					extract( $data ); //phpcs:ignore
					include $file_path;
				}
			}
		}

		// Floating rendering.
		if ( ! empty( $this->campaigns['floating'] ) ) {

			foreach ( $this->campaigns['floating'] as $campaign ) {
				if ( in_array( $campaign['id'], $this->rendered_campaign_ids, true ) ) {
					continue;
				}

				revenue()->load_floating_assets( $campaign );

				$this->rendered_campaign_ids[] = $campaign['id'];

				revenue()->update_campaign_impression( $campaign['id'] );
				$current_campaign = $campaign;

				$file_path = revenue()->get_campaign_path( $campaign, 'floating', 'normal-discount' );

				$file_path = apply_filters(
					'revenue_campaign_view_path',
					$file_path,
					'normal_discount',
					'floating',
					$campaign
				);

				if ( file_exists( $file_path ) ) {
					do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
					extract( $data ); //phpcs:ignore
					include $file_path;
				}
			}
		}
	}


	/**
	 * Set Price on Cart
	 *
	 * @param array $cart_item Cart Item.
	 * @param int   $campaign_id Campaign ID.
	 *
	 * @return void
	 */
	public function set_price_on_cart( $cart_item, $campaign_id ) {
		$campaign_id   = intval( $cart_item['revx_campaign_id'] );
		$offers        = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
		$product_id    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$variation_id  = $cart_item['variation_id'];
		$cart_quantity = $cart_item['quantity'];

		$regular_price = $cart_item['data']->get_regular_price( 'edit' );
		$sale_price    = $cart_item['data']->get_sale_price( 'edit' );

		// Extension Filter: Sale Price Addon.
		$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );
		// based on extension filter use sale price or regular price for calculation.
		$offered_price = $filtered_price;

		if ( is_array( $offers ) ) {
			$offer_type  = '';
			$offer_value = '';

			foreach ( $offers as $offer ) {

				$offered_products = $offer['products'];

				if ( in_array( $product_id, $offered_products ) && $offer['quantity'] <= $cart_quantity ) {
					$offer_type  = isset( $offer['type'] ) ? $offer['type'] : '';
					$offer_value = isset( $offer['value'] ) ? $offer['value'] : '';
				}
			}

			if ( $offer_type && ( 'free' == $offer_type || $offer_value ) ) {
				$offered_price = revenue()->calculate_campaign_offered_price(
					$offer_type,
					$offer_value,
					$filtered_price
				);
			}
		}

		$offered_price = apply_filters( 'revenue_campaign_normal_discount_price', $offered_price, $product_id );
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
		$variation_id  = $cart_item['variation_id'];
		$cart_quantity = $cart_item['quantity'];

		$regular_price = $cart_item['data']->get_regular_price( 'edit' );
		$sale_price    = $cart_item['data']->get_sale_price( 'edit' );

		// Extension Filter: Sale Price Addon.
		$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );
		// based on extension filter use sale price or regular price for calculation.
		$offered_price = $filtered_price;

		$product = $cart_item['data'];

		if ( is_array( $offers ) ) {
			$offer_type  = '';
			$offer_value = '';

			foreach ( $offers as $offer ) {
				$offered_products = $offer['products'];

				if ( in_array( $product_id, $offered_products ) && $offer['quantity'] <= $cart_quantity ) {
					$offer_type  = isset( $offer['type'] ) ? $offer['type'] : '';
					$offer_value = isset( $offer['value'] ) ? $offer['value'] : '';
				}
			}

			if ( $offer_type && ( 'free' == $offer_type || $offer_value ) ) {
				$offered_price = revenue()->calculate_campaign_offered_price(
					$offer_type,
					$offer_value,
					$filtered_price
				);
			}
		}

		// Apply WooCommerce tax display setting AFTER discount calculation.
		$tax_display = get_option( 'woocommerce_tax_display_cart', 'incl' );

		if ( 'incl' === $tax_display ) {
			$offered_price = wc_get_price_including_tax( $product, array( 'price' => $offered_price ) );
		} else {
			$offered_price = wc_get_price_excluding_tax( $product, array( 'price' => $offered_price ) );
		}

		$offered_price = apply_filters( 'revenue_campaign_normal_discount_price', $offered_price, $product_id );

		return $offered_price;
	}


	/**
	 * Cart Item Price
	 *
	 * @param string $subtotal Subtotal.
	 * @param array  $cart_item Cart Item.
	 *
	 * @return string
	 */
	public function cart_item_price( $subtotal, $cart_item ) {
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$subtotal      = $this->get_discounted_price( $cart_item );
			$tax_display   = get_option( 'woocommerce_tax_display_cart', 'incl' );
			$regular_price = 'incl' === $tax_display ? wc_get_price_including_tax( $cart_item['data'], array( 'price' => $cart_item['data']->get_regular_price() ) ) : $cart_item['data']->get_regular_price();

			if ( $cart_item['data']->get_regular_price() != $subtotal ) {
				return '<del>' . wc_price( $regular_price ) . '</del> ' . wc_price( $subtotal );
			}
			$subtotal = wc_price( $subtotal );
		}

		return $subtotal;
	}
}
