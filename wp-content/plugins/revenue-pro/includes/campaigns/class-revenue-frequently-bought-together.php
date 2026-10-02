<?php

namespace RevenuePro;

defined( 'ABSPATH' ) || exit;

use Revenue;

/**
 * WowRevenue Campaign: Frequently Bought Together
 *
 * This class implements the logic for the "Frequently Bought Together" campaign, where products are
 * displayed together if the user buys one product and is eligible for discounts or special offers.
 *
 * @hooked on init
 */
class Revenue_Frequently_Bought_Together {
	use Revenue\SingletonTrait;

	/**
	 * Array of campaigns.
	 *
	 * @var array
	 */
	public $campaigns = array();

	/**
	 * The current position of the campaign for display.
	 *
	 * @var string
	 */
	public $current_position = '';

	/**
	 * Type of campaign - "frequently_bought_together".
	 *
	 * @var string
	 */
	public $campaign_type = 'frequently_bought_together';

	/**
	 * Tracks whether cart items are being removed to prevent infinite recursion.
	 *
	 * @var bool $is_removing_cart_items
	 *    Set to true when removing cart items to prevent infinite recursion.
	 *    Default value is false.
	 */
	protected $is_removing_cart_items = false;

	/**
	 * Initialize the campaign by hooking into WooCommerce actions and filters.
	 *
	 * @return void
	 */
	public function init() {
		add_action( "revenue_campaign_{$this->campaign_type}_before_calculate_cart_totals", array( $this, 'set_price_on_cart' ), 10, 2 );
		add_action( "revenue_campaign_{$this->campaign_type}_remove_cart_item", array( $this, 'remove_cart_item' ), 10, 3 );

		add_filter( "revenue_campaign_{$this->campaign_type}_cart_item_price", array( $this, 'cart_item_price' ), 9999, 2 );
	}

	/**
	 * Set the price of an item in the cart based on the "Frequently Bought Together" offers.
	 *
	 * @param array $cart_item The cart item data.
	 * @param int   $campaign_id The campaign ID.
	 *
	 * @return void
	 */
	public function set_price_on_cart( $cart_item, $campaign_id ) {
		$campaign_id   = intval( $cart_item['revx_campaign_id'] );
		$offers        = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
		$product_id    = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
		$cart_quantity = $cart_item['quantity'];

		if ( $this->is_eligible_for_discount( $cart_item, $campaign_id ) ) {

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

				if ( $offer_type && ( $offer_value || 'free' === $offer_type ) ) {
					$offered_price = revenue()->calculate_campaign_offered_price(
						$offer_type,
						$offer_value,
						$filtered_price
					);
				}
			}

			$offered_price = apply_filters( 'revenue_campaign_frequently_bought_together_price', $offered_price, $product_id );
			$cart_item['data']->set_price( $offered_price );
		}
	}

	/**
	 * Remove a cart item from the cart when the "Frequently Bought Together" trigger item is removed.
	 *
	 * @param string $key The cart item key.
	 *
	 * @return void
	 */
	public function remove_cart_item( $cart_item_key, $cart_item, $campaign_id ) {

		if ( ! isset( $cart_item['revx_fbt_all_triggers_key'], $cart_item['revx_fbt_all_items_key'] ) ) {
			return;
		}

		$trigger_keys = (array) $cart_item['revx_fbt_all_triggers_key'];
		$item_keys    = (array) $cart_item['revx_fbt_all_items_key'];

		if ( empty( $trigger_keys ) && empty( $item_keys ) ) {
			return;
		}

		// Prevent recursion
		if ( $this->is_removing_cart_items ) {
			return;
		}
		$this->is_removing_cart_items = true;

		remove_action( 'revenue_campaign_' . $this->campaign_type . '_remove_cart_item', array( $this, 'remove_cart_item' ), 10 );

		$cart_contents  = WC()->cart->cart_contents;
		$keys_to_remove = array_merge( $trigger_keys, $item_keys );

		// Only remove keys that exist in the cart
		foreach ( $keys_to_remove as $key ) {
			if ( isset( $cart_contents[ $key ] ) ) {
				// unset over remove_cart_item function
				// to prevent infinite recursion
				// action calls on removed items.
				unset( WC()->cart->cart_contents[ $key ] );
			}
		}

		// Recalculate totals and refresh cart once
		// manually set session as we used unset to remove items from cart,
		WC()->cart->set_session();
		WC()->cart->calculate_totals();

		add_action( 'revenue_campaign_' . $this->campaign_type . '_remove_cart_item', array( $this, 'remove_cart_item' ), 10 );

		$this->is_removing_cart_items = false;
	}

	/**
	 * Get the discounted price for a cart item based on the "Frequently Bought Together" offers.
	 *
	 * @param array $cart_item The cart item data.
	 *
	 * @return float The discounted price.
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
		$product     = $cart_item['data'];

		if ( 'incl' === $tax_display ) {
			$offered_price = wc_get_price_including_tax( $product, array( 'price' => $offered_price ) );
		} else {
			$offered_price = wc_get_price_excluding_tax( $product, array( 'price' => $offered_price ) );
		}

		$offered_price = apply_filters( 'revenue_campaign_normal_discount_price', $offered_price, $product_id );

		return $offered_price;
	}

	/**
	 * Modify the cart item price display with discounts applied through "Frequently Bought Together".
	 *
	 * @param string $subtotal The original subtotal.
	 * @param array  $cart_item The cart item data.
	 *
	 * @return string The modified subtotal with discounts.
	 */
	public function cart_item_price( $subtotal, $cart_item ) {
		if (
			isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) &&
			$this->is_eligible_for_discount( $cart_item, $cart_item['revx_campaign_id'] )
		) {
			$subtotal      = $this->get_discounted_price( $cart_item );
			$tax_display   = get_option( 'woocommerce_tax_display_cart', 'incl' );
			$regular_price =
				'incl' === $tax_display
					? wc_get_price_including_tax( $cart_item['data'], array( 'price' => $cart_item['data']->get_regular_price() ) )
					: $cart_item['data']->get_regular_price();

			if ( $cart_item['data']->get_regular_price() != $subtotal ) {
				return '<del>' . wc_price( $regular_price ) . '</del> ' . wc_price( $subtotal );
			}
			$subtotal = wc_price( $subtotal );
		}

		return $subtotal;
	}

	/**
	 * Check if the cart item is eligible for a discount based on the "Frequently Bought Together" campaign.
	 *
	 * @param array $cart_item The cart item data.
	 * @param int   $campaign_id The campaign ID.
	 *
	 * @return bool True if eligible, false otherwise.
	 */
	public function is_eligible_for_discount( $cart_item, $campaign_id ) {

		$is_required_trigger_product = revenue()->get_campaign_meta( $campaign_id, 'fbt_is_trigger_product_required', true );

		if ( 'yes' != $is_required_trigger_product ) {
			return true;
		}

		$required_products = revenue()->get_var( $cart_item['revx_fbt_required_products'] ) ?? 0;

		$cart_product_ids = revenue()->get_cart_product_ids( true ); // pass true to include parent product IDs for v2.0

		$contains_all_required = ! array_diff( $required_products, $cart_product_ids );

		return $contains_all_required;
	}

	/**
	 * Display the campaigns in the page view.
	 *
	 * @param array $campaigns The campaigns to be displayed.
	 * @param array $data Optional data for customizing the display.
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
	 * Display the campaigns in the popup view.
	 *
	 * @param array $campaigns The campaigns to be displayed.
	 * @param array $data Optional data for customizing the display.
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
	 * Display the campaigns in the floating view.
	 *
	 * @param array $campaigns The campaigns to be displayed.
	 * @param array $data Optional data for customizing the display.
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
	 * Render the views for displaying the campaigns.
	 *
	 * @param array $data Optional data for customizing the view rendering.
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

		if ( ! empty( $this->campaigns['inpage'][ $this->current_position ] ) ) {
			$output    = '';
			$campaigns = $this->campaigns['inpage'][ $this->current_position ];
			foreach ( $campaigns as $campaign ) {
				$current_campaign = $campaign;

				revenue()->update_campaign_impression( $campaign['id'], $post->ID );

				$file_path = revenue()->get_campaign_path( $campaign, 'inpage', 'frequently-bought-together' );

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'frequently_bought_together', 'inpage', $campaign );

				if ( file_exists( $file_path ) ) {
					do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
					extract( $data );
					include $file_path;
				}
			}
		}

		if ( ! empty( $this->campaigns['popup'] ) ) {

			$output    = '';
			$campaigns = $this->campaigns['popup'];
			foreach ( $campaigns as $campaign ) {
				$current_campaign = $campaign;

				revenue()->update_campaign_impression( $campaign['id'], $post->ID );

				revenue()->load_popup_assets();

				$file_path = revenue_pro()->get_campaign_path( $campaign, 'popup', 'frequently-bought-together' );

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'frequently_bought_together', 'popup', $campaign );

				if ( file_exists( $file_path ) ) {
					do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
					extract( $data );
					include $file_path;
				}
			}
		}

		if ( ! empty( $this->campaigns['floating'] ) ) {

			$output    = '';
			$campaigns = $this->campaigns['floating'];
			foreach ( $campaigns as $campaign ) {
				$current_campaign = $campaign;

				revenue()->load_floating_assets();

				revenue()->update_campaign_impression( $campaign['id'], $post->ID );

				$file_path = revenue_pro()->get_campaign_path( $campaign, 'floating', 'frequently-bought-together' );

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'frequently_bought_together', 'floating', $campaign );

				if ( file_exists( $file_path ) ) {
					do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
					extract( $data );
					include $file_path;
				}
			}
		}
	}
}
