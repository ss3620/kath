<?php

namespace RevenuePro;

use Revenue;

/**
 * WowRevenue Campaign: Double Order Plus
 *
 * @hooked on init
 */
class Revenue_Double_Order {
	use Revenue\SingletonTrait;

	/**
	 * Store all campaigns
	 *
	 * @var array
	 */
	public $campaigns = array();

	/**
	 * Store current position
	 *
	 * @var string
	 */
	public $current_position = '';

	/**
	 * Store campaign type
	 *
	 * @var string
	 */
	public $campaign_type = 'double_order';

	/**
	 * Store session key
	 *
	 * @var string
	 */
	private $session_key = 'revenue_double_order_session_data';

	/**
	 * Track rendered campaigns to prevent rendering them multiple times
	 *
	 * @var array
	 */
	private static $rendered_campaigns = array();


	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_ajax_revenue_double_order_multiplier', array( $this, 'double_order_multiplier' ) );
		add_action( 'wp_ajax_nopriv_revenue_double_order_multiplier', array( $this, 'double_order_multiplier' ) );
		// add_action('woocommerce_cart_calculate_fees', [$this, 'apply_discount']);

		add_action( 'template_redirect', array( $this, 'check_cart_for_double_order' ) );

		add_action( 'woocommerce_new_order', array( $this, 'reset_session_on_order_complete' ) );

		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_discount' ) );

		// add_action( 'woocommerce_cart_updated', array( $this, 'update_cart_action' ) );
	}

	public function update_cart_action() {

		if ( WC()->session ) {
			WC()->session->__unset( $this->session_key );
		}
	}

	// Method to check the cart and remove custom items based on session
	public function check_cart_for_double_order() {
		// Check if WooCommerce session is available
		if ( ! WC()->session ) {
			return;
		}

		$session_data = WC()->session->get( $this->session_key );

		// Get the campaign ID and saved index from session
		$campaign_ids = array(); // Array to hold all campaign IDs that may be involved
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['revx_campaign_id'] ) ) {
				$campaign_ids[] = $cart_item['revx_campaign_id'];
			}
		}

		// Iterate over each campaign ID, check session, and remove items if needed
		foreach ( $campaign_ids as $campaign_id ) {

			if ( ! isset( $session_data[ $campaign_id ] ) ) {
				foreach ( WC()->cart->get_cart() as $key => $item ) {
					// Check if the item is from the current campaign and is a custom-added item
					if ( isset( $item['revx_campaign_type'] ) && $item['revx_campaign_type'] === 'double_order' &&
						isset( $item['revx_campaign_id'] ) && $item['revx_campaign_id'] === $campaign_id ) {
						WC()->cart->remove_cart_item( $key );
					}
				}
			}
		}
	}

	/**
	 * Double Order Multiplier
	 *
	 * @return void
	 */
	public function double_order_multiplier() {
		if ( ! WC()->session ) {
			wp_send_json_error( array( 'message' => 'Session not initialized' ) );
		}

		$multiplier  = isset( $_POST['multiplier'] ) ? absint( $_POST['multiplier'] ) : 0;
		$campaign_id = isset( $_POST['campaign_id'] ) ? absint( $_POST['campaign_id'] ) : 0;
		$index       = isset( $_POST['index'] ) ? absint( $_POST['index'] ) : 0;
		$is_checked  = isset( $_POST['is_checked'] ) ? sanitize_text_field( $_POST['is_checked'] ) : 'no';
		$product_ids = isset( $_POST['product_ids'] ) ? $_POST['product_ids'] : array();

		$campaign = revenue()->get_campaign_data( $campaign_id );

		if ( ! $campaign || empty( $campaign['campaign_trigger_type'] ) ) {
			wp_send_json_error( array( 'message' => 'Invalid campaign data' ) );
		}

		if ( ! isset( $campaign['offers'][ $index ] ) ) {
			wp_send_json_error( array( 'message' => 'Offer not found for selected index' ) );
		}

		$offer    = $campaign['offers'][ $index ];
		$quantity = isset( $offer['quantity'] ) ? intval( $offer['quantity'] ) : 0;

		$checkbox_key       = "revenue_double_order_checkbox_{$campaign_id}";
		$checkbox_items_key = "revenue_double_order_checkbox_products_{$campaign_id}";

		$session_data = WC()->session->get( $this->session_key, array() );

		// Determine request type based on product IDs
		$request_type = empty( $product_ids ) ? 'all_products' : 'specific';
		// Store data in session
		$data = array(
			'type'       => $request_type,
			'products'   => $product_ids,
			'is_checked' => $is_checked,
			'index'      => $index,
			'multiplier' => $multiplier,
		);

		$session_data[ $campaign_id ] = $data;

		if ( 'no' === $is_checked ) {
			unset( $session_data[ $campaign_id ] );
		}

		$is_all_products  = 'all_products' === $campaign['campaign_trigger_type'];
		$cart             = WC()->cart;
		$cart_product_ids = array();

		foreach ( $cart->get_cart() as $key => $item ) {
			if ( isset( $item['revx_campaign_type'], $item['revx_campaign_id'] ) && 'double_order' === $item['revx_campaign_type'] && intval( $item['revx_campaign_id'] ) === $campaign_id ) {
				$cart->remove_cart_item( $key );
			}
		}

		$cart = WC()->cart;

		if ( $is_all_products ) {

			if ( $quantity === $multiplier ) {
				foreach ( $cart->get_cart() as $key => $item ) {
					$cart_product_ids[] = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];

					if ( 'yes' == $is_checked ) {
						// For variation products, WooCommerce already stores attributes in $item['variation']
						$variation_attributes = ! empty( $item['variation'] ) ? $item['variation'] : array();

						$cart->add_to_cart(
							$item['product_id'],
							intval( $item['quantity'] ) * ( $multiplier - 1 ),
							$item['variation_id'],
							$variation_attributes,
							array(
								'revx_campaign_type' => 'double_order',
								'revx_campaign_id'   => $campaign_id,
							)
						);
					}
				}
			}
		} elseif ( $quantity === $multiplier ) {
			foreach ( $cart->get_cart() as $key => $item ) {
				$cart_product_ids[] = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];

				$pid = $item['variation_id'] ? $item['variation_id'] : $item['product_id'];

				if ( in_array( $pid, $product_ids ) ) { //phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
					if ( 'yes' == $is_checked ) {
						// For variation products, WooCommerce already stores attributes in $item['variation']
						$variation_attributes = ! empty( $item['variation'] ) ? $item['variation'] : array();

						$cart->add_to_cart(
							$pid,
							intval( $item['quantity'] ) * ( $multiplier - 1 ),
							$item['variation_id'],
							$variation_attributes,
							array(
								'revx_campaign_type' => 'double_order',
								'revx_campaign_id'   => $campaign_id,
							)
						);
					}
				}
			}
		}

		$session_data['cart_hash'] = WC()->cart->get_cart_hash();

		WC()->session->set( $this->session_key, $session_data );

		wp_send_json_success();
	}

	// Update cart based on multiplier.
	private function update_cart_multiplier( $multiplier ) {
		$cart = WC()->cart;
	}

	// Restore the original cart state.
	private function restore_cart() {
		$cart          = WC()->cart;
		$original_cart = WC()->session->get( $this->original_cart_session_key );

		if ( $original_cart ) {
			$cart->empty_cart();
			foreach ( $original_cart as $cart_item ) {
				$cart->add_to_cart( $cart_item['product_id'], $cart_item['quantity'] );
			}
			WC()->session->__unset( $this->original_cart_session_key );
		}
	}

	public function output_inpage_views( $campaigns, $data = array() ) {
		foreach ( $campaigns as $campaign ) {
			// Check if the campaign has already been rendered.
			if ( isset( self::$rendered_campaigns[ $campaign['id'] ] ) ) {
				continue;
			}

			// Mark the campaign as rendered.
			self::$rendered_campaigns[ $campaign['id'] ] = true;

			// Store the campaign for rendering.
			$this->campaigns['inpage'][ $data['position'] ][] = $campaign;
		}
		// Update the current position and render views.
		$this->current_position = $data['position'];
		$this->render_views( $data );
	}

	/**
	 * Render Views
	 *
	 * @param array $data Optional. Data to pass to the view.
	 * @return void
	 */
	public function render_views( $data = array() ) {
		if ( ! defined( 'REVENUE_VER' ) || version_compare( REVENUE_VER, '2.0.0', '<' ) ) {
			return;
		}

		$data = wp_parse_args(
			$data,
			array(
				'placement' => 'checkout_page',
			)
		);

		if ( ! empty( $this->campaigns['inpage'][ $this->current_position ] ) ) {
			$output    = '';
			$campaigns = $this->campaigns['inpage'][ $this->current_position ];
			foreach ( $campaigns as $campaign ) {
				revenue()->update_campaign_impression( $campaign['id'] );

				$file_path = revenue()->get_campaign_path( $campaign, 'inpage', 'double-order' );
				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'double_order', 'inpage', $campaign );

				if ( file_exists( $file_path ) ) {
					do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
					extract( $data );
					include $file_path;
				}
			}
		}
	}


	/**
	 * Reset session data when an order is completed.
	 *
	 * @return void
	 */
	public function reset_session_on_order_complete() {
		if ( is_admin() || ! ( WC()->session ) ) {
			return;
		}
		WC()->session->__unset( $this->session_key );
	}

	/**
	 * Apply discount to the cart based on double order campaign.
	 *
	 * @return void
	 */
	public function apply_discount() {
		if ( ! WC()->session ) {
			return;
		}

		$campaign_ids = array();
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['revx_campaign_id'] ) ) {
				$campaign_ids[] = $cart_item['revx_campaign_id'];
			}
		}

		$session_data = WC()->session->get( $this->session_key );

		foreach ( $campaign_ids as $campaign_id ) {

			if ( ! isset( $session_data[ $campaign_id ] ) ) {
				continue;
			}

			$saved_index = $session_data[ $campaign_id ]['index'];

			if ( $saved_index !== -1 && is_numeric( $saved_index ) ) {
				$offers = revenue()->get_campaign_meta( $campaign_id, 'offers', true );
				if ( ! isset( $offers[ $saved_index ] ) ) {
					continue;
				}

				$offer         = $offers[ $saved_index ];
				$quantity      = isset( $offer['quantity'] ) ? $offer['quantity'] : 0;
				$discount_type = isset( $offer['type'] ) ? $offer['type'] : '';
				$value         = isset( $offer['value'] ) ? $offer['value'] : 0;
				$campaign      = revenue()->get_campaign_data( $campaign_id );

				$trigger_type = $campaign['campaign_trigger_type'];
				if ( 'all_products' == $trigger_type ) {
					$cart_subtotal   = WC()->cart->get_subtotal();
					$discount_amount = 0;

					if ( 'percentage' === $discount_type ) {
						$discount_amount = ( $cart_subtotal * $value ) / 100;
					} else {
						$discount_amount = $value;
					}

					$discount_amount = max( 0, $discount_amount );

					if ( $discount_amount > 0 ) {
						// translators: %s is the quantity of the product.
						$fee_name = sprintf( __( 'Savings Applied for %sx Cart', 'revenue-pro' ), $quantity );
						WC()->cart->add_fee( $fee_name, -$discount_amount, true, '' );
					}
				} else {

					// Get selected products from session.
					$product_ids     = $session_data[ $campaign_id ]['products'];
					$discount_amount = 0;

					// Track products that will get discounts.
					$discounted_products = array();

					// Loop through cart items.
					foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
						$_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

						// Check if this product should get discount.
						if ( in_array( $_id, $product_ids ) ) {
							$product          = wc_get_product( $_id );
							$line_subtotal    = $cart_item['line_subtotal']; // Get line subtotal before discount.
							$product_quantity = $cart_item['quantity'];
							$item_discount    = 0;

							// Calculate discount for this specific product.
							if ( 'percentage' === $discount_type ) {
								$item_discount = ( ( $line_subtotal * $value ) / 100 ) * $quantity;
							} else {
								$item_discount = $value;
							}

							$item_discount = max( 0, $item_discount );
							$item_discount = $item_discount;

							if ( $item_discount > 0 ) {
								$discount_amount += $item_discount;

								// Store product info for fee name.
								$discounted_products[] = array(
									'name'     => $product->get_name(),
									'quantity' => $quantity, // Multiplier from the campaign.
									'discount' => $item_discount,
								);
							}
						}
					}

					// Apply discounts as separate fees for each product.
					foreach ( $discounted_products as $product_info ) {

						$fee_name = sprintf(
							// translators: %1$s is the quantity and %2$s is the product name.
							__( 'Savings Applied for %1$dx %2$s', 'revenue-pro' ),
							$product_info['quantity'],
							$product_info['name']
						);

						WC()->cart->add_fee( $fee_name, -$product_info['discount'], true, '' );
					}
				}
			}
		}
	}
}
