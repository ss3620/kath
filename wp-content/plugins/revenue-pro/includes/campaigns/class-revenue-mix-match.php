<?php
namespace RevenuePro;

defined( 'ABSPATH' ) || exit;

use Revenue;

/**
 * WowRevenue Campaign: Mix and Match
 *
 * Handles the functionality for the "Mix and Match" product campaigns.
 * This campaign allows customers to apply discounts on products when certain conditions are met
 * such as purchasing a required quantity of specific products. It also manages various output views
 * like in-page, popup, and floating views for the campaign.
 *
 * @package RevenuePro
 * @since 1.0.0
 *
 * @hooked on init
 */
class Revenue_Mix_Match {
	use Revenue\SingletonTrait;

	/**
	 * Stores the campaigns.
	 *
	 * @var array
	 */
	public $campaigns = array();

	/**
	 * Stores the current position for in-page views.
	 *
	 * @var string
	 */
	public $current_position = '';

	/**
	 * Campaign type.
	 *
	 * @var string
	 */
	public $campaign_type = 'mix_match';

	/**
	 * Initialize the campaign and register necessary actions and filters.
	 *
	 * @return void
	 */
	public function init() {
		add_action( "revenue_campaign_{$this->campaign_type}_before_calculate_cart_totals", array( $this, 'set_price_on_cart' ), 10, 2 );
		add_filter( "revenue_campaign_{$this->campaign_type}_cart_item_price", array( $this, 'cart_item_price' ), 9999, 2 );
	}

	/**
	 * Set the price of a cart item based on the applicable offers for the campaign.
	 *
	 * @param array $cart_item The cart item being processed.
	 * @param int   $campaign_id The campaign ID.
	 *
	 * @return void
	 */
	public function set_price_on_cart( $cart_item, $campaign_id ) {
		$campaign_id  = intval( $cart_item['revx_campaign_id'] );
		$offers       = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
		$product_id   = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$eligible_qty = $this->get_eligible_quantity( $cart_item );

		if ( $eligible_qty ) {
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
					if ( ! isset( $offer['quantity'] ) ) {
						continue;
					}
					if ( $offer['quantity'] <= $eligible_qty ) {
						$offer_type  = $offer['type'];
						$offer_value = $offer['value'];
					}
				}

				if ( $offer_type && $offer_value ) {
					$offered_price = revenue()->calculate_campaign_offered_price(
						$offer_type,
						$offer_value,
						$filtered_price
					);
				}
			}

			$offered_price = apply_filters( 'revenue_campaign_mix_match_price', $offered_price, $product_id );
			$cart_item['data']->set_price( $offered_price );
		}
	}

	/**
	 * Get the discounted price for a cart item based on the campaign's offers.
	 *
	 * @param array $cart_item The cart item being processed.
	 *
	 * @return float The discounted price.
	 */
	public function get_discounted_price( $cart_item ) {
		$campaign_id  = intval( $cart_item['revx_campaign_id'] );
		$offers       = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
		$product_id   = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$eligible_qty = $this->get_eligible_quantity( $cart_item );

		$regular_price = $cart_item['data']->get_regular_price( 'edit' );
		$sale_price    = $cart_item['data']->get_sale_price( 'edit' );

		// Extension Filter: Sale Price Addon.
		$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );
		// based on extension filter use sale price or regular price for calculation.
		$offered_price = $filtered_price;

		if ( $eligible_qty ) {

			if ( is_array( $offers ) ) {
				$offer_type  = '';
				$offer_value = '';

				foreach ( $offers as $offer ) {
					if ( ! isset( $offer['quantity'] ) ) {
						continue;
					}
					if ( $offer['quantity'] <= $eligible_qty ) {
						$offer_type  = $offer['type'];
						$offer_value = $offer['value'];
					}
				}

				if ( $offer_type && $offer_value ) {
					$offered_price = revenue()->calculate_campaign_offered_price(
						$offer_type,
						$offer_value,
						$filtered_price
					);
				}
			}

			// NEW: ADDED TAX SUPPORT.
			$display_mode = get_option( 'woocommerce_tax_display_cart', 'excl' );

			// $offered_price should be the raw base you computed (usually tax-exclusive).
			if ( 'incl' === $display_mode ) {
				// Show including tax (adds all applicable taxes, handles compound/rounding).
				$offered_price = wc_get_price_including_tax(
					$cart_item['data'],
					array( 'price' => $offered_price )
				);
			} else {
				// Show excluding tax (removes taxes if the base happened to include them).
				$offered_price = wc_get_price_excluding_tax(
					$cart_item['data'],
					array( 'price' => $offered_price )
				);
			}

			$offered_price = apply_filters( 'revenue_campaign_mix_match_price', $offered_price, $product_id );
		}

		return $offered_price;
	}

	/**
	 * Adjust the cart item price displayed to the customer, including the original price and the discounted price.
	 *
	 * @param string $subtotal The subtotal of the cart item.
	 * @param array  $cart_item The cart item being processed.
	 *
	 * @return string The formatted subtotal with the discount applied.
	 */
	public function cart_item_price( $subtotal, $cart_item ) {
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$subtotal = $this->get_discounted_price( $cart_item );

			if ( $cart_item['data']->get_regular_price() != $subtotal ) {

				// NEW: ADDED TAX SUPPORT.
				$price        = $cart_item['data']->get_regular_price();
				$display_mode = get_option( 'woocommerce_tax_display_cart', 'excl' );

				if ( 'incl' === $display_mode ) {
					// Show including tax (adds all applicable taxes, handles compound/rounding).
					$price = wc_get_price_including_tax(
						$cart_item['data'],
						array( 'price' => $price )
					);
				} else {
					// Show excluding tax (removes taxes if the base happened to include them).
					$price = wc_get_price_excluding_tax(
						$cart_item['data'],
						array( 'price' => $price )
					);
				}

				return '<del>' . wc_price( $price ) . '</del> ' . wc_price( $subtotal );
			}
			$subtotal = wc_price( $subtotal );
		}

		return $subtotal;
	}

	/**
	 * Calculate the eligible quantity of products for the campaign.
	 *
	 * @param array $cart_item The cart item being processed.
	 *
	 * @return int The number of eligible products.
	 */
	public function get_eligible_quantity( $cart_item ) {
		$count              = 0;
		$campaign_id        = isset( $cart_item['revx_campaign_id'] ) ? absint( $cart_item['revx_campaign_id'] ) : 0;
		$required_products  = revenue()->get_var( $cart_item['revx_required_products'] ) ?? array();
		$mix_match_products = revenue()->get_var( $cart_item['revx_mix_match_products'] ) ?? array();

		// Full cart quantities (any source) — used only for the required-products presence gate.
		$cart_qty_map     = revenue()->get_cart_product_quantities();
		$cart_product_ids = array_keys( $cart_qty_map );

		// Campaign-scoped quantities — only products added FROM this campaign count toward the
		// discount. This ensures the same product added manually or by another campaign does not
		// inflate the eligible quantity used to pick the offer tier.
		$campaign_qty_map     = $campaign_id ? revenue()->get_cart_product_quantities( false, $campaign_id ) : $cart_qty_map;
		$campaign_product_ids = array_keys( $campaign_qty_map );

		$contains_all_required = ! array_diff( $required_products, $cart_product_ids );

		// Number of distinct mix-match products added by this campaign (legacy default behavior).
		$unique_count = count( array_intersect( $mix_match_products, $campaign_product_ids ) );
		// Summed quantity of mix-match products added by this campaign.
		$total_qty = array_sum( array_intersect_key( $campaign_qty_map, array_flip( $mix_match_products ) ) );

		if ( $contains_all_required && $unique_count ) {
			$count = $unique_count;
		}

		/**
		 * Filter the eligible quantity used to select a Mix & Match offer tier.
		 *
		 * By default this is the number of distinct mix-match products in the cart
		 * (gated by the required-products check). Returning the 'total' value switches
		 * eligibility to the summed quantity of the offered products instead.
		 *
		 * @param int   $count     The eligible quantity (default: distinct product count, or 0 if required products missing).
		 * @param array $cart_item The cart item being processed.
		 * @param array $context   ['unique' => int, 'total' => int, 'qty_map' => array, 'contains_all_required' => bool].
		 */
		return apply_filters(
			'revenue_mix_match_eligible_quantity',
			$count,
			$cart_item,
			array(
				'unique'                => $unique_count,
				'total'                 => $total_qty,
				'qty_map'               => $campaign_qty_map,
				'contains_all_required' => $contains_all_required,
			)
		);
	}

	/**
	 * Outputs the in-page views for the campaign.
	 *
	 * @param array $campaigns The list of campaigns.
	 * @param array $data The data to pass to the view.
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
	 * Outputs the popup views for the campaign.
	 *
	 * @param array $campaigns The list of campaigns.
	 * @param array $data The data to pass to the view.
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
	 * Outputs the floating views for the campaign.
	 *
	 * @param array $campaigns The list of campaigns.
	 * @param array $data The data to pass to the view.
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
	 * Renders the views for the campaign (in-page, popup, or floating).
	 *
	 * @param array $data The data to pass to the view.
	 *
	 * @return void
	 */
	public function render_views( $data = array() ) {
		global $current_campaign;
		global $post;

		if ( ! defined( 'REVENUE_VER' ) || version_compare( REVENUE_VER, '2.0.0', '<' ) ) {
			return;
		}

		$data = wp_parse_args(
			$data,
			array(
				'placement' => 'product_page',
			)
		);

		// Rendering for inpage views.
		if ( ! empty( $this->campaigns['inpage'][ $this->current_position ] ) ) {
			$output    = '';
			$campaigns = $this->campaigns['inpage'][ $this->current_position ];
			foreach ( $campaigns as $campaign ) {
				$current_campaign = $campaign;
				revenue()->update_campaign_impression( $campaign['id'], $post->ID );

				$file_path = revenue()->get_campaign_path( $campaign, 'inpage', 'mix-match' );
				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'mix_match', 'inpage', $campaign );

				if ( file_exists( $file_path ) ) {
					do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
					extract( $data ); //phpcs:ignore
					include $file_path;
				}
			}
		}

		// Rendering for popup views.
		if ( ! empty( $this->campaigns['popup'] ) ) {

			$output    = '';
			$campaigns = $this->campaigns['popup'];
			foreach ( $campaigns as $campaign ) {
				$current_campaign = $campaign;
				revenue()->update_campaign_impression( $campaign['id'], $post->ID );

				revenue()->load_popup_assets();

				$file_path = revenue_pro()->get_campaign_path( $campaign, 'popup', 'mix-match' );
				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'mix_match', 'popup', $campaign );

				if ( file_exists( $file_path ) ) {
					do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
					extract( $data ); //phpcs:ignore
					include $file_path;
				}
			}
		}

		// Rendering for floating views.
		if ( ! empty( $this->campaigns['floating'] ) ) {

			$output    = '';
			$campaigns = $this->campaigns['floating'];
			foreach ( $campaigns as $campaign ) {
				$current_campaign = $campaign;

				revenue()->load_floating_assets();
				revenue()->update_campaign_impression( $campaign['id'], $post->ID );

				$file_path = revenue_pro()->get_campaign_path( $campaign, 'floating', 'mix-match' );
				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'mix_match', 'floating', $campaign );

				if ( file_exists( $file_path ) ) {
					do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
					extract( $data ); //phpcs:ignore
					include $file_path;
				}
			}
		}
	}
}
