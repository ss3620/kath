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

		/*
		 * SECURITY: Promote orphaned 'double_order' duplicates to normal cart items.
		 *
		 * Each duplicate is bound to its parent via a unique `revx_double_order_parent_token`
		 * stamped on both lines in double_order_multiplier(). When the parent is removed from
		 * the cart the duplicate becomes an orphan: its parent token no longer resolves, so
		 * the discount that justified it no longer applies.
		 *
		 * Rather than deleting the orphan (poor UX), we strip all campaign metadata from it.
		 * It then looks like a regular cart item and is completely ignored by apply_discount(),
		 * which only considers items tagged with revx_campaign_type = 'double_order'.
		 *
		 * This runs on `template_redirect` — the safe place to mutate the cart before
		 * woocommerce_cart_calculate_fees fires — so promotion happens before any discount
		 * calculation.
		 */
		$needs_session_save = false;
		$campaigns_to_reset = array();

		foreach ( WC()->cart->get_cart() as $key => $item ) {
			if ( isset( $item['revx_campaign_type'] ) && 'double_order' === $item['revx_campaign_type'] ) {
				$parent_token = isset( $item['revx_double_order_parent_token'] ) ? $item['revx_double_order_parent_token'] : '';

				if ( ! $this->validate_source_product( $parent_token ) ) {
					// Capture the campaign ID before promoting strips it from the item.
					if ( isset( $item['revx_campaign_id'] ) ) {
						$campaigns_to_reset[] = $item['revx_campaign_id'];
					}
					$this->promote_orphaned_duplicate( $key );
					$needs_session_save = true;
				}
			}
		}

		if ( $needs_session_save ) {
			WC()->cart->set_session();
		}

		/*
		 * Reset session state for any campaign that just lost a parent product.
		 *
		 * After promotion the cart is clean, but the session would still mark the
		 * campaign as active (is_checked: yes). The template reads this session to
		 * decide whether to render the checkbox as checked — leaving it stale
		 * causes the checkbox to appear active while apply_discount() applies no
		 * discount (no revx_campaign_id items remain). The user would have to
		 * manually uncheck and re-check to restore a consistent state.
		 *
		 * Clearing the session entry here ensures the checkbox renders unchecked
		 * on the next page load. The user can re-enable the double order explicitly.
		 */
		if ( ! empty( $campaigns_to_reset ) ) {
			$session_data = WC()->session->get( $this->session_key, array() );
			foreach ( array_unique( $campaigns_to_reset ) as $reset_id ) {
				unset( $session_data[ $reset_id ] );
			}
			WC()->session->set( $this->session_key, $session_data );
		}
	}

	/**
	 * Promote an orphaned duplicate cart item to a regular (non-campaign) product.
	 *
	 * Strips all double-order campaign metadata from the cart line so that
	 * apply_discount() ignores it entirely. The item stays in the cart, giving
	 * the customer a graceful experience: they lose the campaign discount but
	 * keep the product they added.
	 *
	 * Caller is responsible for calling WC()->cart->set_session() once all
	 * promotions for a given request are done (batch the write).
	 *
	 * @param string $cart_item_key The key of the cart item to promote.
	 * @return void
	 */
	private function promote_orphaned_duplicate( $cart_item_key ) {
		if ( ! isset( WC()->cart->cart_contents[ $cart_item_key ] ) ) {
			return;
		}

		unset( WC()->cart->cart_contents[ $cart_item_key ]['revx_campaign_type'] );
		unset( WC()->cart->cart_contents[ $cart_item_key ]['revx_campaign_id'] );
		unset( WC()->cart->cart_contents[ $cart_item_key ]['revx_double_order_parent_token'] );
	}

	/**
	 * Generate a unique token used to bind a duplicate to the exact parent line
	 * it was created from.
	 *
	 * @return string
	 */
	private function generate_parent_token() {
		return uniqid( 'revx_dop_', true );
	}

	/**
	 * Validate that the original (source) product backing a double-order
	 * duplicate is still present in the cart.
	 *
	 * The double-order campaign stamps a unique parent token on BOTH the genuine
	 * (user-added) parent line and the duplicate it generates. The duplicate is
	 * tagged with `revx_campaign_type = 'double_order'`; the parent is not.
	 *
	 * Matching on the token rather than the product id is what makes this robust:
	 * if a customer buys 3 products, duplicates them, then removes 2 of the
	 * originals, each remaining duplicate is checked against ITS OWN parent. The
	 * 2 duplicates whose parents are gone fail validation, while the duplicate
	 * whose parent survives stays valid. A simple "does any source product / any
	 * non-duplicate exist?" check could not tell those cases apart.
	 *
	 * @param string $parent_token Token that links a duplicate to its parent.
	 * @return bool True only when a non-'double_order' line carrying this token
	 *              exists in the cart; false when the parent was removed.
	 */
	private function validate_source_product( $parent_token ) {
		if ( ! WC()->cart || empty( $parent_token ) ) {
			return false;
		}

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			// Skip duplicates created by the double-order campaign – we are
			// explicitly looking for the genuine, user-added parent line.
			$is_duplicate = isset( $cart_item['revx_campaign_type'] ) && 'double_order' === $cart_item['revx_campaign_type'];

			$item_token = isset( $cart_item['revx_double_order_parent_token'] ) ? $cart_item['revx_double_order_parent_token'] : '';

			if ( ! $is_duplicate && $item_token === $parent_token ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Enforce the double-order minimum quantity for a single product.
	 *
	 * When a customer accepts a double order the campaign adds duplicate lines so
	 * the product's total quantity reaches parent_qty × multiplier. Nothing stops
	 * the customer from returning to the cart afterwards and lowering the quantity
	 * of those added duplicates while keeping the discount. This check closes that
	 * gap: a product only remains eligible while its total *valid* quantity
	 * (genuine parent lines + duplicates whose parent token still resolves) is at
	 * or above the required minimum of parent_qty × multiplier.
	 *
	 * Example: multiplier 10, one product in the cart -> 9 duplicates are added
	 * for a total of 10. The customer must keep a total of at least 10 to earn the
	 * discount; anything above the minimum is fine, anything below forfeits it.
	 *
	 * @param int $product_id The product or variation id to evaluate.
	 * @param int $multiplier  The offer multiplier (offer quantity).
	 * @return bool True when the product still meets the minimum quantity.
	 */
	private function product_meets_minimum( $product_id, $multiplier ) {
		$multiplier = intval( $multiplier );

		if ( ! WC()->cart || $multiplier <= 0 ) {
			return false;
		}

		$parent_qty    = 0;
		$valid_dup_qty = 0;

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$item_pid = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( intval( $item_pid ) !== intval( $product_id ) ) {
				continue;
			}

			$is_duplicate = isset( $cart_item['revx_campaign_type'] ) && 'double_order' === $cart_item['revx_campaign_type'];

			if ( $is_duplicate ) {
				$parent_token = isset( $cart_item['revx_double_order_parent_token'] ) ? $cart_item['revx_double_order_parent_token'] : '';

				// Only intact duplicates (parent still present) count toward the minimum.
				if ( $this->validate_source_product( $parent_token ) ) {
					$valid_dup_qty += intval( $cart_item['quantity'] );
				}
			} else {
				$parent_qty += intval( $cart_item['quantity'] );
			}
		}

		// No genuine parent line -> nothing to double, so nothing qualifies.
		if ( $parent_qty <= 0 ) {
			return false;
		}

		$required_total = $parent_qty * $multiplier;

		return ( $parent_qty + $valid_dup_qty ) >= $required_total;
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

						/*
						 * SECURITY: Bind this duplicate to its exact parent line.
						 *
						 * Mint a unique token, stamp it on the parent cart line and
						 * pass the same token to the duplicate. validate_source_product()
						 * later confirms the parent carrying this token is still in the
						 * cart before any discount is honoured, so removing the original
						 * (even just some of several originals) invalidates its duplicate.
						 */
						$parent_token = $this->generate_parent_token();
						$cart->cart_contents[ $key ]['revx_double_order_parent_token'] = $parent_token;

						$cart->add_to_cart(
							$item['product_id'],
							intval( $item['quantity'] ) * ( $multiplier - 1 ),
							$item['variation_id'],
							$variation_attributes,
							array(
								'revx_campaign_type'             => 'double_order',
								'revx_campaign_id'               => $campaign_id,
								'revx_double_order_parent_token' => $parent_token,
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

						/*
						 * SECURITY: Bind this duplicate to its exact parent line via a
						 * unique token stamped on both. See validate_source_product().
						 */
						$parent_token = $this->generate_parent_token();
						$cart->cart_contents[ $key ]['revx_double_order_parent_token'] = $parent_token;

						$cart->add_to_cart(
							$pid,
							intval( $item['quantity'] ) * ( $multiplier - 1 ),
							$item['variation_id'],
							$variation_attributes,
							array(
								'revx_campaign_type'             => 'double_order',
								'revx_campaign_id'               => $campaign_id,
								'revx_double_order_parent_token' => $parent_token,
							)
						);
					}
				}
			}
		}

		// Persist the parent tokens we wrote directly onto the parent cart lines.
		$cart->set_session();

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

					/*
					 * SECURITY: Build the discount base from valid lines only.
					 *
					 * For an all-products campaign every cart product is duplicated.
					 * Instead of discounting the raw cart subtotal (which would still
					 * include orphaned duplicates), we sum the line subtotals and skip
					 * any duplicate whose parent token no longer resolves to a parent
					 * line in the cart. This handles the partial-removal case: buy 3,
					 * duplicate, then delete 2 originals -> only the 1 surviving
					 * parent + its duplicate count toward the discount; the 2 orphaned
					 * duplicates are excluded (and purged in check_cart_for_double_order).
					 */
					$cart_subtotal   = 0;
					$discount_amount = 0;

					foreach ( WC()->cart->get_cart() as $cart_item ) {
						$_id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

						/*
						 * SECURITY: enforce the per-product double-order minimum. If the
						 * customer trimmed the added duplicates so the product's total
						 * valid quantity dropped below parent × multiplier, exclude both
						 * its parent and duplicate lines from the discount base.
						 */
						if ( ! $this->product_meets_minimum( $_id, $quantity ) ) {
							continue;
						}

						$is_duplicate = isset( $cart_item['revx_campaign_type'] ) && 'double_order' === $cart_item['revx_campaign_type'];

						if ( $is_duplicate ) {
							$parent_token = isset( $cart_item['revx_double_order_parent_token'] ) ? $cart_item['revx_double_order_parent_token'] : '';

							// Orphaned duplicate (its parent was removed) -> not eligible.
							if ( ! $this->validate_source_product( $parent_token ) ) {
								continue;
							}
						}

						$cart_subtotal += $cart_item['line_subtotal'];
					}

					if ( $cart_subtotal <= 0 ) {
						continue;
					}

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

							/*
							 * SECURITY: enforce the double-order minimum quantity. If the
							 * customer lowered the added duplicate quantity in the cart, the
							 * product no longer reaches parent × multiplier and forfeits the
							 * discount entirely (both parent and duplicate lines).
							 */
							if ( ! $this->product_meets_minimum( $_id, $quantity ) ) {
								continue;
							}

							/*
							 * SECURITY: A discount line is only honoured when its parent
							 * is still present. A duplicate ('double_order') line is
							 * validated against the parent token it carries: if the
							 * matching parent line was removed, the duplicate is orphaned
							 * and skipped here (and purged in check_cart_for_double_order()).
							 * Genuine parent lines have no campaign type and are always
							 * valid while they remain in the cart. This stops a user from
							 * deleting the original while keeping the discounted duplicate.
							 */
							$is_duplicate = isset( $cart_item['revx_campaign_type'] ) && 'double_order' === $cart_item['revx_campaign_type'];

							if ( $is_duplicate ) {
								$parent_token = isset( $cart_item['revx_double_order_parent_token'] ) ? $cart_item['revx_double_order_parent_token'] : '';

								if ( ! $this->validate_source_product( $parent_token ) ) {
									continue;
								}
							}

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
