<?php

namespace RevenuePro;

use Revenue;
use Exception;
use WC_Shipping_Free_Shipping;

/**
 * WowRevenue Campaign: Spending Goal
 *
 * @hooked on init
 */
class Revenue_Spending_Goal {
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
	public $campaign_type = 'spending_goal';

	/**
	 * Track rendered campaigns to prevent rendering them multiple times
	 *
	 * @var array
	 */
	private static $rendered_campaigns = array();

	/**
	 * Store all campaigns data
	 *
	 * @var array
	 */
	public $all_campaigns = array();

	/**
	 * Discount percentage for the campaign.
	 *
	 * @var int
	 */
	public $discount_percentage = 0;

	/**
	 * Tracks whether cart items are being removed to prevent infinite recursion.
	 *
	 * @var bool $is_removing_cart_items
	 *    Set to true when removing cart items to prevent infinite recursion.
	 *    Default value is false.
	 */
	protected $is_removing_cart_items = false;

	/**
	 * Initialize the class
	 *
	 * @return void
	 */
	public function init() {

		// $this->fetch_campaigns();

		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'apply_campaign_discounts' ) );

		add_action( 'woocommerce_before_calculate_totals', array( $this, 'process_shipping_rewards' ), 9999 );

		/**
		 * Validate cart gifts after calculate totals and when a cart item is removed.
		 * - after_calculate_totals works when quantity is changed.
		 * - cart_item_removed works when a cart item is removed.
		 * Need both because of their timing to update mini cart fragment.
		 */
		add_action( 'woocommerce_after_calculate_totals', array( $this, 'validate_cart_gifts' ), 10 );
		add_action( 'woocommerce_cart_item_removed', array( $this, 'validate_cart_gifts' ), 10 );

		add_filter( 'woocommerce_package_rates', array( $this, 'modify_shipping_rates' ), 100, 2 );

		add_action( 'wp_ajax_revenue_auto_add_free_gifts', array( $this, 'auto_add_free_gifts' ) );
		add_action( 'wp_ajax_nopriv_revenue_auto_add_free_gifts', array( $this, 'auto_add_free_gifts' ) );

		add_action( 'wp_ajax_revenue_add_free_gift', array( $this, 'add_free_gift' ) );
		add_action( 'wp_ajax_nopriv_revenue_add_free_gift', array( $this, 'add_free_gift' ) );

		add_action( 'wp_ajax_revenue_remove_free_gift', array( $this, 'remove_free_gift' ) );
		add_action( 'wp_ajax_nopriv_revenue_remove_free_gift', array( $this, 'remove_free_gift' ) );

		// add_action( 'woocommerce_check_cart_items', array( $this, 'validate_cart_gifts' ) );

		// add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'check_and_add_gift_products' ), 20 );

		// add_action( 'woocommerce_cart_updated', array( $this, 'check_and_add_gift_products' ), 20 );

		// add_filter('woocommerce_cart_item_quantity', array($this, 'modify_gift_quantity_input'), 10, 3);

		add_filter( 'revenue_campaign_spending_goal_cart_item_quantity', array( $this, 'modify_gift_quantity_input' ), 10, 2 );
		add_filter( 'revenue_campaign_spending_goal_store_api_product_quantity_minimum', array( $this, 'modify_gift_quantity_input' ), 10, 2 );
		add_filter( 'revenue_campaign_spending_goal_store_api_product_quantity_maximum', array( $this, 'modify_gift_quantity_input' ), 10, 2 );

		add_filter( 'revenue_campaign_spending_goal_cart_item_price', array( $this, 'cart_item_price' ), 99999, 2 );

		add_action( 'revenue_campaign_spending_goal_before_calculate_cart_totals', array( $this, 'apply_upsell_product_discount' ), 10, 3 );

		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * Fetch all campaigns of type 'spending_goal'.
	 *
	 * @return array Filtered campaigns.
	 */
	public function fetch_campaigns() {
		global $post;
		$id            = $post ? $post->ID : 0;
		$all_campaigns = revenue()->get_available_campaigns( $id, '', '', '', false, true );

		$filted_data = array();

		foreach ( $all_campaigns as $cmp ) {
			if ( isset( $cmp['campaign_type'] ) && 'spending_goal' === $cmp['campaign_type'] ) {
				$filted_data[] = $cmp;
			}
		}

		return $filted_data;
	}

	public function register_scripts() {
		// wp_register_style( 'revenue-spending-goal', REVENUE_PRO_FILE . '/assets/css/spending-goal.css', array(), REVENUE_PRO_VER );
		// wp_enqueue_style( 'revenue-spending-goal' );
	}

	/**
	 * Get the heading message for a gift based on the goal and offer.
	 *
	 * @param float $goal The spending goal required for the gift.
	 * @param array $offer The offer data containing gift details.
	 * @return string The heading message for the gift.
	 */
	public function get_gift_heading_message( $goal, $offer ) {
		$cart_total       = $this->get_eligible_cart_total();
		$remaining_amount = $cart_total - $goal;
		$quantity         = isset( $offer['gift_quantity'] ) ? $offer['gift_quantity'] : false;
		$is_all           = 'all' === $quantity;

		$message = '';
		if ( $goal > $cart_total ) {
			$remaining_amount = wc_price( abs( $remaining_amount ) );
			if ( $is_all ) {
				// translators: %s is the remaining amount.
				$message = sprintf( __( 'Spend %s more to claim your gift!', 'revenue-pro' ), $remaining_amount );

			} elseif ( 1 == $quantity ) {
				// translators: %1$s is the remaining amount and %2$d is the quantity.
				$message = sprintf( __( 'Spend %1$s more to get any %2$d item as a gift!', 'revenue-pro' ), $remaining_amount, $quantity );

			} else {
				// translators: %1$s is the remaining amount and %2$d is the quantity.
				$message = sprintf( __( 'Spend %1$s more to get any %2$d items as a gift!', 'revenue-pro' ), $remaining_amount, $quantity );

			}
		} elseif ( $is_all ) {
				$message = __( 'Congrats! Your gift is here!', 'revenue-pro' );

		} elseif ( 1 == $quantity ) {
			// translators: %d is the quantity.
			$message = sprintf( __( 'Congrats! Choose any %d item.', 'revenue-pro' ), $quantity );

		} else {
			// translators: %d is the quantity.
			$message = sprintf( __( 'Congrats! Choose any %d items.', 'revenue-pro' ), $quantity );
		}

		return $message;
	}

	/**
	 * Apply upsell product discount to a cart item based on campaign settings.
	 *
	 * @param array $cart_item The cart item to apply the discount to.
	 * @param int   $campaign_id The campaign ID.
	 * @return void
	 */
	public function apply_upsell_product_discount( $cart_item, $campaign_id ) {
		$total = $this->get_eligible_cart_total();

		// if current cart item is gift then set price to 0 and return.
		if ( $this->is_gift( $cart_item, $total ) ) {
			$cart_item['data']->set_price( 0 );
			return;
		}

		$discounted_price = $this->get_discounted_price( $cart_item );
		$cart_item['data']->set_price( max( 0, $discounted_price ) );
	}

	/**
	 * Determine if the given cart item qualifies as a gift based on cart total.
	 *
	 * @param array $cart_item The cart item to check.
	 * @param float $cart_total The current cart total.
	 * @return bool True if the item is a gift, false otherwise.
	 */
	private function is_gift( $cart_item, $cart_total ) {
		if (
			isset( $cart_item['revx_is_reward_gift'], $cart_item['revx_cart_required'] ) &&
			$cart_item['revx_is_reward_gift'] &&
			$cart_item['revx_cart_required'] <= $cart_total
		) {
			return true;
		}
		return false;
	}

	/**
	 * Check if a specific item qualifies as a gift in the cart based on the goal.
	 *
	 * @param int   $item_id The product or variation ID to check.
	 * @param float $goal The spending goal required for the gift.
	 * @return bool True if the item is a qualifying gift in the cart, false otherwise.
	 */
	public function is_gift_on_cart( $item_id, $goal ) {
		$cart_total = $this->get_eligible_cart_total();

		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			if ( $product_id == $item_id && $this->is_gift( $cart_item, $cart_total ) && $cart_total >= $goal ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Get Discounted Price
	 *
	 * @param array $cart_item Cart Item.
	 *
	 * @return float
	 */
	public function get_discounted_price( $cart_item ) {
		$campaign_id = intval( $cart_item['revx_campaign_id'] );
		$data        = revenue()->get_campaign_meta( $campaign_id, 'spending_goal_upsell_products', true );

		$offered_product_id = isset( $cart_item['variation_id'] ) &&
								$cart_item['variation_id']
									? $cart_item['variation_id']
									: $cart_item['product_id'];

		if ( ! is_array( $data ) ) {
			$data = array();
		}

		$is_found = false;

		$regular_price = $cart_item['data']->get_regular_price( 'edit' );
		$sale_price    = $cart_item['data']->get_sale_price( 'edit' );

		// Extension Filter: Sale Price Addon.
		$filtered_price = apply_filters( 'revenue_base_price_for_discount_filter', $regular_price, $sale_price );
		// based on extension filter use sale price or regular price for calculation.
		$offered_price = $filtered_price;

		foreach ( $data as $pd ) {
			// Ensure 'products' is an array of product IDs.
			if ( ! isset( $pd['products'] ) || ! is_array( $pd['products'] ) ) {
				continue;
			}

			// Process each product ID.
			foreach ( $pd['products'] as $item_data ) {
				if ( intval( $offered_product_id ) === intval( $item_data['item_id'] ) ) {
					// Calculate discounted price based on pd type.
					$offered_price = revenue()->calculate_campaign_offered_price(
						$pd['type'],
						isset( $pd['value'] ) ? (float) $pd['value'] : 0,
						$filtered_price,
					);

					$is_found = true;
				}
			}
			if ( $is_found ) {
				break;
			}
		}
		// in case of cart items removed and gift item is stil on the cart
		// and lost its free eligibility, set normal price of the gift item.
		return apply_filters(
			'revenue_campaign_spending_goal_discount_price',
			$offered_price,
			$offered_product_id
		);
	}

	/**
	 * Cart Item Price
	 *
	 * @param string $subtotal Subtotal.
	 * @param array  $cart_item Cart Item.
	 * @return string
	 */
	public function cart_item_price( $subtotal, $cart_item ) {

		$total = $this->get_eligible_cart_total();

		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) && 'spending_goal' === $cart_item['revx_campaign_type'] ) {

			if ( $this->is_gift( $cart_item, $total ) ) {

				return '<del>' . wc_price( $cart_item['data']->get_regular_price() ) . '</del>  <span class="revx-free-gift"> Free! </span>';

			}
			$subtotal = $this->get_discounted_price( $cart_item );
			if ( $cart_item['data']->get_regular_price() != $subtotal ) {
				return '<del>' . wc_price( $cart_item['data']->get_regular_price() ) . '</del> ' . wc_price( $subtotal );
			}

			$subtotal = wc_price( $subtotal );
		}

		return $subtotal;
	}

	/**
	 * Modify quantity input for gift items to be readonly
	 *
	 * @param int   $product_quantity The quantity of the product.
	 * @param array $cart_item        The cart item data.
	 */
	public function modify_gift_quantity_input( $product_quantity, $cart_item ) {
		if ( isset( $cart_item['revx_is_reward_gift'] ) && $cart_item['revx_is_reward_gift'] ) {
			$product_quantity = $cart_item['quantity'];
			return $product_quantity;

		}
		return $product_quantity;
	}

	/**
	 * Handles AJAX request to automatically add free gifts to the cart based on campaign eligibility.
	 *
	 * @return void
	 */
	public function auto_add_free_gifts() {
		$campaign_id = isset( $_POST['campaign_id'] ) ? sanitize_text_field( $_POST['campaign_id'] ) : '';

		if ( empty( $campaign_id ) ) {
			wp_send_json_error(
				array(
					'status'  => false,
					'message' => 'Invalid request. Missing campaign or product information.',
				)
			);
			return;
		}

		$offers = revenue()->get_campaign_meta( $campaign_id, 'offers', true );

		if ( empty( $offers ) ) {
			wp_send_json_error(
				array(
					'status'  => false,
					'message' => 'No offers found for this campaign.',
				)
			);
			return;
		}

		$cart_total    = $this->get_eligible_cart_total();
		$current_gifts = $this->get_current_gift_items();
		$goal          = 0;

		$eligible_gifts   = array();
		$response_message = '';

		foreach ( $offers as $offer ) {
			if ( ! isset( $offer['spending_goal'] ) ) {
				continue;
			}
			$goal += $offer['spending_goal'];

			$gift_quantity = isset( $offer['gift_quantity'] ) ? $offer['gift_quantity'] : 1;

			if ( isset( $offer['reward_type'] ) && 'gift' === $offer['reward_type'] && $cart_total >= $goal ) {
				// Count how many gifts from this offer are already in cart.
				$current_offer_gifts_count = 0;
				foreach ( $current_gifts as $gift_item ) {
					if ( $gift_item['revx_campaign_id'] === $campaign_id ) {
						++$current_offer_gifts_count;
					}
				}

				foreach ( $offer['gift_products'] as $gift ) {
					foreach ( $current_gifts as $gift_item ) {
						if ( intval( $gift_item['product_id'] ) === intval( $gift['item_id'] ) ) {
							continue 2;
						}
					}
					if ( isset( $gift['parent_id'] ) ||
						( isset( $gift['children'] ) && ! empty( $gift['children'] ) )
					) {
						continue;
					}

					// Check gift quantity limits.
					if ( 'all' === $gift_quantity ) {
						$eligible_gifts[ $gift['item_id'] ] = array(
							'quantity'            => 1,
							'revx_campaign_id'    => $campaign_id,
							'revx_campaign_type'  => 'spending_goal',
							'revx_gift_items'     => $gift,
							'required_cart_value' => $goal,
						);
					}
				}
			}
		}

		foreach ( $current_gifts as $cart_item_key => $gift_item ) {
			if ( ! isset( $eligible_gifts[ $gift_item['product_id'] ] ) ) {
				WC()->cart->remove_cart_item( $cart_item_key );
			}
		}

		$status         = false;
		$added_items_id = array();
		// Add new eligible gift.
		foreach ( $eligible_gifts as $product_id => $gift_data ) {
			$status = $this->add_gift_to_cart(
				$product_id,
				1,
				$gift_data
			);

			if ( $status ) {
				$added_items_id[] = $product_id;
				$response_message = 'Gift added successfully to your cart!';
			}
		}

		if ( empty( $eligible_gifts ) && empty( $response_message ) ) {
			$response_message = 'Gifts not available to add atuomatically.';
		}

		wp_send_json_success(
			array(
				'status'         => $status,
				'added_items_id' => $added_items_id,
				'message'        => $response_message,
			)
		);
	}


	/**
	 * Handles AJAX request to add a free gift to the cart based on campaign eligibility.
	 *
	 * @return void
	 */
	public function add_free_gift() {
		$campaign_id = isset( $_POST['campaign_id'] ) ? sanitize_text_field( $_POST['campaign_id'] ) : '';
		$product_id  = isset( $_POST['product_id'] ) ? sanitize_text_field( $_POST['product_id'] ) : '';

		if ( empty( $campaign_id ) || empty( $product_id ) ) {
			wp_send_json_error(
				array(
					'status'  => false,
					'message' => 'Invalid request. Missing campaign or product information.',
				)
			);
			return;
		}

		$offers = revenue()->get_campaign_meta( $campaign_id, 'offers', true );

		if ( empty( $offers ) ) {
			wp_send_json_error(
				array(
					'status'  => false,
					'message' => 'No offers found for this campaign.',
				)
			);
			return;
		}

		$cart_total    = $this->get_eligible_cart_total();
		$current_gifts = $this->get_current_gift_items();
		$goal          = 0;

		$eligible_gifts   = array();
		$response_message = '';

		foreach ( $offers as $offer ) {
			if ( ! isset( $offer['spending_goal'] ) ) {
				continue;
			}
			$goal += $offer['spending_goal'];

			$gift_quantity = isset( $offer['gift_quantity'] ) ? $offer['gift_quantity'] : 1;

			if ( isset( $offer['reward_type'] ) && 'gift' === $offer['reward_type'] && $cart_total >= $goal ) {
				// Count how many gifts from this offer are already in cart.
				$current_offer_gifts_count = 0;
				// Extract all item_ids from current offer gift products
				$offer_gift_ids = wp_list_pluck( $offer['gift_products'], 'item_id' );
				foreach ( $current_gifts as $gift_item ) {
					if (
						intval( $gift_item['revx_campaign_id'] ) === intval( $campaign_id ) &&
						// strict checking not done intentionally.
						in_array( intval( $gift_item['product_id'] ), $offer_gift_ids )
					) {
						++$current_offer_gifts_count;
					}
				}

				foreach ( $offer['gift_products'] as $gift ) {
					if ( $product_id === $gift['item_id'] ) {

						// Check if this product is already in cart.
						$product_in_cart = false;
						foreach ( $current_gifts as $gift_item ) {
							if ( $gift_item['product_id'] === $product_id ) {
								$product_in_cart  = true;
								$response_message = 'This gift is already in your cart.';
								break;
							}
						}

						if ( $product_in_cart ) {
							continue;
						}

						// Check gift quantity limits.
						if ( 'all' === $gift_quantity ) {
							$eligible_gifts[ $gift['item_id'] ] = array(
								'quantity'            => 1,
								'revx_campaign_id'    => $campaign_id,
								'revx_campaign_type'  => 'spending_goal',
								'revx_gift_items'     => $gift,
								'required_cart_value' => $goal,
							);
						} elseif ( $current_offer_gifts_count < intval( $gift_quantity ) ) {
							$eligible_gifts[ $gift['item_id'] ] = array(
								'quantity'            => 1,
								'revx_campaign_id'    => $campaign_id,
								'revx_campaign_type'  => 'spending_goal',
								'revx_gift_items'     => $gift,
								'required_cart_value' => $goal,
							);
						} else {
							$response_message = sprintf(
								'Choose only %s! Remove one to select a new one.',
								$gift_quantity
							);
						}
					}
				}
			} elseif ( 'gift' === $offer['reward_type'] ) {
				$remaining        = abs( $goal - $cart_total );
				$response_message = sprintf(
					'Add %.2f more to your cart to qualify for this gift.',
					$remaining
				);
			}
		}

		// Remove gifts that are no longer eligible.
		$removed_items = false;
		foreach ( $current_gifts as $cart_item_key => $gift_item ) {
			if ( ! isset( $eligible_gifts[ $gift_item['product_id'] ] ) ) {
				WC()->cart->remove_cart_item( $cart_item_key );
				$removed_items = true;
			}
		}

		if ( $removed_items && empty( $response_message ) ) {
			$response_message = 'Some gifts were removed as they are no longer eligible.';
		}

		$status = false;

		// Add new eligible gift.
		foreach ( $eligible_gifts as $product_id => $gift_data ) {
			$status = $this->add_gift_to_cart(
				$product_id,
				1,
				$gift_data
			);

			if ( $status && empty( $response_message ) ) {
				$response_message = 'Gift added successfully to your cart!';
			} elseif ( ! $status && empty( $response_message ) ) {
				$response_message = 'Unable to add gift to cart. Please try again.';
			}
		}

		if ( empty( $eligible_gifts ) && empty( $response_message ) ) {
			$response_message = 'This gift is not available for selection.';
		}

		wp_send_json_success(
			array(
				'status'  => $status,
				'message' => $response_message,
			)
		);
	}
	/**
	 * Handles AJAX request to remove a free gift from the cart.
	 *
	 * @return void
	 */
	public function remove_free_gift() {
		$campaign_id = isset( $_POST['campaign_id'] ) ? sanitize_text_field( $_POST['campaign_id'] ) : '';
		$product_id  = isset( $_POST['product_id'] ) ? sanitize_text_field( $_POST['product_id'] ) : '';

		$removed_key = false;
		if ( WC()->cart && ! WC()->cart->is_empty() ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				if ( isset( $cart_item['revx_is_reward_gift'] ) && $cart_item['revx_is_reward_gift'] ) {
					$gift_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
					if ( $gift_id == $product_id ) {
						$removed_key = $cart_item_key;
						break;
					}
				}
			}
		}

		if ( $removed_key ) {
			$status = WC()->cart->remove_cart_item( $cart_item_key );
		}

		wp_send_json_success( array( 'status' => $status ) );

		// $this->add_gift_to_cart($product_id,$quantity,$gift_data);
	}



	/**
	 * Check and add gift products to cart
	 *
	 * @return void
	 */
	public function check_and_add_gift_products() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		$cart_total     = $this->get_eligible_cart_total();
		$current_gifts  = $this->get_current_gift_items();
		$eligible_gifts = array();

		if ( empty( $this->all_campaigns ) ) {
			$this->all_campaigns = $this->fetch_campaigns();
		}

		// Collect all eligible gifts from active campaigns.
		foreach ( $this->all_campaigns as $campaign ) {

			$goal = 0;

			foreach ( $campaign['offers'] as $offer ) {
				if ( ! isset( $offer['spending_goal'] ) ) {
					continue;
				}
				$goal += $offer['spending_goal'];

				if ( 'gift' === $offer['reward_type'] && $cart_total >= $goal ) {

					foreach ( $offer['gift_products'] as $gift ) {
						$eligible_gifts[ $gift['item_id'] ] = array(
							'quantity'           => $offer['gift_quantity'],
							'revx_campaign_id'   => $campaign['id'],
							'revx_campaign_type' => 'spending_goal',
							'revx_gift_items'    => $gift,
						);
					}
				}
			}
		}

		// Remove gifts that are no longer eligible.
		foreach ( $current_gifts as $cart_item_key => $gift_item ) {
			if (
				! isset( $eligible_gifts[ $gift_item['product_id'] ] ) ||
				$gift_item['quantity'] > $eligible_gifts[ $gift_item['product_id'] ]['quantity']
			) {
				WC()->cart->remove_cart_item( $cart_item_key );
			}
		}

		// Add new eligible gifts.
		foreach ( $eligible_gifts as $product_id => $gift_data ) {
			$current_quantity = isset( $current_gifts[ $product_id ] ) ?
				$current_gifts[ $product_id ]['quantity'] : 0;

			if ( intval( $current_quantity ) < intval( $gift_data['quantity'] ) ) {
				$this->add_gift_to_cart(
					$product_id,
					$gift_data['quantity'] - $current_quantity,
					$gift_data
				);
			}
		}
	}

	/**
	 * Add gift item to cart
	 *
	 * @param int   $product_id Product ID.
	 * @param int   $quantity Gift item quantity.
	 * @param array $gift_data Gift Data.
	 * @return mixed
	 */
	private function add_gift_to_cart( $product_id, $quantity, $gift_data ) {
		// Prepare gift item data.
		$cart_item_key  = false;
		$gift_cart_data = array(
			'revx_is_reward_gift' => true,
			'revx_campaign_id'    => $gift_data['revx_campaign_id'],
			'revx_campaign_type'  => 'spending_goal',
			'revx_cart_required'  => $gift_data['required_cart_value'],
		);

		// Check if product exists and is purchasable.
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_purchasable() ) {
			return false;
		}

		// Add the gift to cart with custom price.
		try {
			$cart_item_key = WC()->cart->add_to_cart(
				$product_id,
				$quantity,
				0,
				array(),
				$gift_cart_data
			);
		} catch ( Exception $e ) {

			wc_add_notice(
				sprintf(
					/* translators: %s: error message */
					__( 'Error adding gift product: %s', 'revenue-pro' ),
					$e->getMessage()
				),
				'error'
			);
			return false;
		}

		return $cart_item_key;
	}

	/**
	 * Get current gift items which added on cart
	 *
	 * @return array
	 */
	private function get_current_gift_items() {
		$gift_items = array();

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( isset( $cart_item['revx_is_reward_gift'] ) && $cart_item['revx_is_reward_gift'] ) {
				$item_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

				$gift_items[ $item_id ] = array(
					'product_id'         => $item_id,
					'key'                => $cart_item_key,
					'quantity'           => $cart_item['quantity'],
					'revx_campaign_id'   => $cart_item['revx_campaign_id'],
					'revx_campaign_type' => 'spending_goal',
				);
			}
		}

		return $gift_items;
	}

	/**
	 * Modify shipping rates based on campaign eligibility for free shipping.
	 *
	 * @param array $rates   The available shipping rates.
	 * @param array $package The shipping package data.
	 * @return array Modified shipping rates.
	 */
	public function modify_shipping_rates( $rates, $package ) {
		$cart_total = $this->get_eligible_cart_total();

		$is_free_shipping = false;

		if ( empty( $this->all_campaigns ) ) {
			$this->all_campaigns = $this->fetch_campaigns();
		}

		if ( ! empty( $this->all_campaigns ) ) {
			$first_index = array_keys( $this->all_campaigns )[0];

			$campaign = $this->all_campaigns[ $first_index ];

			$goal = 0;

			foreach ( $campaign['offers'] as $offer ) {
				if ( ! isset( $offer['spending_goal'] ) ) {
					continue;
				}
				$goal += isset( $offer['spending_goal'] ) ? $offer['spending_goal'] : 0;

				if ( isset( $offer['reward_type'] ) &&
					'free_shipping' === $offer['reward_type'] &&
					$cart_total >= $goal
				) {
					$is_free_shipping = true;
				}
			}
		}

		if ( $is_free_shipping ) {
			$free_shipping        = new WC_Shipping_Free_Shipping( 'revenue_free_shipping' );
			$free_shipping->title = apply_filters( 'revenue_free_shipping_title', __( 'WoW Revenue Free Shipping', 'revenue-pro' ) ); // Add Global Settings.
			$free_shipping->calculate_shipping( $package );
			return $free_shipping->rates;
		}

		return $rates;
	}



	/**
	 * Process shipping rewards for eligible cart items.
	 *
	 * Sets the price of gift items to zero if they qualify as a reward.
	 *
	 * @param WC_Cart $cart The WooCommerce cart object.
	 * @return void
	 */
	public function process_shipping_rewards( $cart ) {
		if ( ( is_admin() && ! defined( 'DOING_AJAX' ) ) || wp_doing_ajax() ) {
			return;
		}

		$cart_total = $this->get_eligible_cart_total();

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( $this->is_gift( $cart_item, $cart_total ) ) {
				$cart_item['data']->set_price( 0 );
			}
		}
	}

	/**
	 * Apply campaign discounts to the cart if eligible.
	 *
	 * @return void
	 */
	public function apply_campaign_discounts() {
		if ( is_admin() ) {
			return;
		}

		$cart_total        = WC()->cart->get_cart_contents_total();
		$cart_total       += WC()->cart->display_prices_including_tax() ? WC()->cart->get_cart_contents_tax() : 0;
		$applied_discounts = array();

		$goal = 0;

		if ( empty( $this->all_campaigns ) ) {
			$this->all_campaigns = $this->fetch_campaigns();
		}
		$applied_offer = array();

		foreach ( $this->all_campaigns as $id => $campaign ) {
			foreach ( $campaign['offers'] as $offer ) {

				if ( ! isset( $offer['spending_goal'] ) ) {
					continue;
				}
				$goal += $offer['spending_goal'];

				$key = 'Spending Goal Reward - ' . $id;

				if ( isset( $offer['reward_type'] ) && 'discount' === $offer['reward_type'] && $cart_total >= $goal ) {
					$applied_offer       = $offer;
					$applied_discounts[] = 'Spending Goal Reward - ' . $id;
				}
			}
		}

		if ( ! empty( $applied_offer ) && is_array( $applied_offer ) ) {
			$this->apply_offer_discount( $applied_offer, $cart_total );
		}
	}


	/**
	 * Validate cart gifts based on current cart total and active campaigns.
	 *
	 * Removes invalid gifts from the cart if they no longer qualify.
	 *
	 * @return void
	 */
	public function validate_cart_gifts() {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}
		if ( $this->is_removing_cart_items ) {
			return;
		}

		$cart_total = $this->get_eligible_cart_total();

		$this->is_removing_cart_items = true;

		$removed = false;

		// Remove invalid gifts from cart.
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( ! isset( $cart_item['revx_is_reward_gift'], $cart_item['revx_cart_required'] ) ) {
				continue;
			}
			if ( $cart_item['revx_cart_required'] && $cart_item['revx_cart_required'] > $cart_total ) {
				// remove the item if it does not fullfill the required amount.
				WC()->cart->remove_cart_item( $cart_item_key );
				$removed = true;
			}
		}

		$this->is_removing_cart_items = false;

		if ( $removed ) {
			wc_add_notice(
				__( 'Free gift was removed because your cart no longer meets the required amount.', 'revenue-pro' ),
				'notice'
			);
		}
	}

	/**
	 * Determine if a discount can be applied based on the offer and cart total.
	 *
	 * @param array $offer The offer data.
	 * @param float $cart_total The current cart total.
	 * @param array $applied_discounts List of already applied discounts.
	 * @return bool True if discount can be applied, false otherwise.
	 */
	private function can_apply_discount( $offer, $cart_total, $applied_discounts ) {
		return isset( $offer['reward_type'] ) && 'discount' === $offer['reward_type'] &&
			$cart_total >= $offer['spending_goal'] &&
			! in_array( $offer['key'], $applied_discounts );
	}

	/**
	 * Apply discount to the cart based on the offer.
	 *
	 * @param array $offer The offer data containing discount details.
	 * @param float $cart_total The current cart total.
	 * @return void
	 */
	private function apply_offer_discount( $offer, $cart_total ) {
		$discount_amount = 0;

		if ( ( isset( $offer['discount_type'] ) && 'percentage' === $offer['discount_type'] ) || ( isset( $offer['type'] ) && 'percentage' === $offer['type'] ) ) {
			$discount_value  = isset( $offer['discount_value'] ) ? $offer['discount_value'] : ( isset( $offer['value'] ) ? $offer['value'] : 0 );
			$discount_amount = $cart_total * ( $discount_value / 100 );
		} elseif ( ( isset( $offer['discount_type'] ) && 'amount' === $offer['discount_type'] ) || ( isset( $offer['type'] ) && 'amount' === $offer['type'] ) ) {
			$discount_amount = isset( $offer['discount_value'] ) ? $offer['discount_value'] : ( isset( $offer['value'] ) ? $offer['value'] : 0 );
		}

		// Apply max discount if set.

		if ( $discount_amount > 0 ) {
			WC()->cart->add_fee(
				__( 'Spending Goal Discounts', 'revenue-pro' ),
				-$discount_amount,
				true,
				'standard'
			);
		}
	}

	/**
	 * Calculate and return the eligible cart total, excluding gift products.
	 *
	 * @return float
	 */
	private function get_eligible_cart_total() {
		$cart_total     = 0;
		$total_line_tax = 0;
		if ( ! WC()->cart || WC()->cart->is_empty() ) {
			return $cart_total;
		}

		// Remove gift products from calculation.
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! $this->is_gift( $cart_item, $cart_total ) ) {
				if ( isset( $cart_item['line_total'] ) ) {
					$cart_total     += $cart_item['line_total'];
					$total_line_tax += isset( $cart_item['line_tax'] ) ? (float) $cart_item['line_tax'] : 0;
				}
			}
		}
		// to handle tax issue, Need to check with differnt tax setting. Checked with price including tax setting.
		if ( WC()->cart->display_prices_including_tax() ) {
			$cart_total += $total_line_tax;
		}
		// wc_format_decimal to round up few points after decimal and properly format the price.
		return (float) wc_format_decimal( $cart_total, 2 );
	}



	/**
	 * Output inpage views for campaigns.
	 *
	 * @param array $campaigns List of campaigns.
	 * @param array $data      Additional data for rendering.
	 */
	public function output_inpage_views( $campaigns, $data = array() ) {

		foreach ( $campaigns as $campaign ) {
			$this->campaigns['inpage'][ $data['position'] ][] = $campaign;
			$this->all_campaigns[ $campaign['id'] ]           = $campaign;
		}
		$this->current_position = $data['position'];
		$this->render_views( $data, 'inpage' );
	}
	/**
	 * Output drawer views for campaigns.
	 *
	 * @param array $campaigns List of campaigns.
	 * @param array $data      Additional data for rendering.
	 */
	public function output_drawer_views( $campaigns, $data = array() ) {

		foreach ( $campaigns as $campaign ) {
			$this->campaigns['drawer'][ $data['position'] ][] = $campaign;
			$this->all_campaigns[ $campaign['id'] ]           = $campaign;
		}
		$this->current_position = $data['position'];
		$this->render_views( $data, 'drawer' );
	}
	/**
	 * Output top views for campaigns.
	 *
	 * @param array $campaigns List of campaigns.
	 * @param array $data      Additional data for rendering.
	 */
	public function output_top_views( $campaigns, $data = array() ) {

		foreach ( $campaigns as $campaign ) {
			$this->campaigns['hellobar']['top'][]   = $campaign;
			$this->all_campaigns[ $campaign['id'] ] = $campaign;
		}
		$data['position']       = 'top';
		$this->current_position = $data['position'];
		$this->render_views( $data, 'top' );
	}
	/**
	 * Output bottom views for campaigns.
	 *
	 * @param array $campaigns List of campaigns.
	 * @param array $data      Additional data for rendering.
	 */
	public function output_bottom_views( $campaigns, $data = array() ) {

		foreach ( $campaigns as $campaign ) {
			$this->campaigns['hellobar']['bottom'][] = $campaign;
			$this->all_campaigns[ $campaign['id'] ]  = $campaign;
		}
		$data['position']       = 'bottom';
		$this->current_position = $data['position'];
		$this->render_views( $data, 'bottom' );
	}

	/**
	 * Render Views
	 *
	 * @param array  $data Data for rendering views.
	 * @param string $render_for What type of view to render.
	 * @return void
	 */
	public function render_views( $data = array(), $render_for = '' ) {
		global $current_campaign;

		if ( ! defined( 'REVENUE_VER' ) || version_compare( REVENUE_VER, '2.0.0', '<' ) ) {
			return;
		}

		$data = wp_parse_args(
			$data,
			array(
				'placement' => 'product_page',
			)
		);

		if ( ! empty( $this->campaigns['inpage'][ $this->current_position ] ) && 'inpage' === $render_for ) {
			$output    = '';
			$campaigns = $this->campaigns['inpage'][ $this->current_position ];

			foreach ( $campaigns as $campaign ) {
				// Prevent duplicate rendering of the same campaign in the same position.
				$campaign_key = 'inpage_' . $this->current_position . '_' . $campaign['id'];
				if ( isset( self::$rendered_campaigns[ $campaign_key ] ) ) {
					continue;
				}
				self::$rendered_campaigns[ $campaign_key ] = true;

				$current_campaign = $campaign;

				if ( revenue()->is_for_new_builder( $campaign ) ) {
					wp_enqueue_script( 'revenue-spending-goal' );
					wp_enqueue_style( 'revenue-campaign-spending_goal' );
				} else {
					wp_enqueue_script( 'revenue-v1-spending-goal' );
					wp_enqueue_style( 'revenue-v1-campaign-spending_goal' );
				}

				revenue()->update_campaign_impression( $campaign['id'] );

				$file_path = revenue()->get_campaign_path( $campaign, 'inpage', 'spending-goal' );

				$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'spending_goal', 'inpage', $campaign );

				$data['position'] = 'inpage';

				ob_start();
				if ( file_exists( $file_path ) ) {
					do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
					extract( $data ); //phpcs:ignore
					include $file_path;
				}

				$output .= ob_get_clean();
			}

			if ( $output ) {
				echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		if ( ! empty( $this->campaigns['drawer'] ) && 'drawer' === $render_for ) {

			$output    = '';
			$campaigns = $this->campaigns['drawer']['top_left'];

			foreach ( $campaigns as $campaign ) {
				// Prevent duplicate rendering of the same drawer campaign.
				$campaign_key = 'drawer_' . $campaign['id'];
				if ( isset( self::$rendered_campaigns[ $campaign_key ] ) ) {
					continue;
				}
				self::$rendered_campaigns[ $campaign_key ] = true;

				$current_campaign = $campaign;

				if ( revenue()->is_for_new_builder( $campaign ) ) {
					wp_enqueue_script( 'revenue-spending-goal' );
					wp_enqueue_style( 'revenue-campaign-spending_goal' );
				} else {
					wp_enqueue_script( 'revenue-v1-spending-goal' );
					wp_enqueue_style( 'revenue-v1-campaign-spending_goal' );
				}

				$placement_settings = revenue()->get_placement_settings( $campaign['id'] );

				$current_page = revenue()->get_current_page();

				if ( $current_page && ! empty( $placement_settings ) ) {

					$data['position'] = $placement_settings['drawer_position'];

					$file_path = revenue()->get_campaign_path( $campaign, 'drawer', 'spending-goal' );

					$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'spending_goal', 'drawer', $campaign );

					ob_start();
					if ( file_exists( $file_path ) ) {
						do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
						extract( $data ); //phpcs:ignore
						include $file_path;
					}

					$output .= ob_get_clean();
				} else {
					foreach ( $placement_settings as $page => $value ) {

						if ( 'yes' === $value['status'] && ( $page === $current_page || 'all_page' === $page ) ) {
							$data['position'] = $value['drawer_position'];

							$file_path = revenue()->get_campaign_path( $campaign, 'drawer', 'spending-goal' );

							$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'spending_goal', 'drawer', $campaign );

							ob_start();
							if ( file_exists( $file_path ) ) {
								do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
								extract( $data ); //phpcs:ignore
								include $file_path;
							}

							$output .= ob_get_clean();
						}
					}
				}

				revenue()->update_campaign_impression( $campaign['id'] );

			}

			if ( $output ) {
				echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		}

		if ( ! empty( $this->campaigns['hellobar'] ) ) {

			if ( isset( $this->campaigns['hellobar']['top'] ) && ! empty( $this->campaigns['hellobar']['top'] ) && 'top' === $render_for ) {

				$output    = '';
				$campaigns = $this->campaigns['hellobar']['top'];

				foreach ( $campaigns as $campaign ) {
					// Prevent duplicate rendering of the same top hellobar campaign.
					$campaign_key = 'hellobar_top_' . $campaign['id'];
					if ( isset( self::$rendered_campaigns[ $campaign_key ] ) ) {
						continue;
					}
					self::$rendered_campaigns[ $campaign_key ] = true;

					$current_campaign = $campaign;

					if ( revenue()->is_for_new_builder( $campaign ) ) {
						wp_enqueue_script( 'revenue-spending-goal' );
						wp_enqueue_style( 'revenue-campaign-spending_goal' );
					} else {
						wp_enqueue_script( 'revenue-v1-spending-goal' );
						wp_enqueue_style( 'revenue-v1-campaign-spending_goal' );
					}

					$placement_settings = revenue()->get_placement_settings( $campaign['id'] );

					$file_path = revenue()->get_campaign_path( $campaign, 'hellobar', 'spending-goal' );

					$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'spending_goal', 'hellobar', $campaign );

					ob_start();
					if ( file_exists( $file_path ) ) {
						do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
						extract( $data ); //phpcs:ignore
						include $file_path;
					}

					$output .= ob_get_clean();

					revenue()->update_campaign_impression( $campaign['id'] );
				}

				if ( $output ) {
					echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}

			if ( isset( $this->campaigns['hellobar']['bottom'] ) && ! empty( $this->campaigns['hellobar']['bottom'] ) && 'bottom' === $render_for ) {

				$output    = '';
				$campaigns = $this->campaigns['hellobar']['bottom'];

				foreach ( $campaigns as $campaign ) {
					// Prevent duplicate rendering of the same bottom hellobar campaign.
					$campaign_key = 'hellobar_bottom_' . $campaign['id'];
					if ( isset( self::$rendered_campaigns[ $campaign_key ] ) ) {
						continue;
					}
					self::$rendered_campaigns[ $campaign_key ] = true;

					$current_campaign = $campaign;

					if ( revenue()->is_for_new_builder( $campaign ) ) {
						wp_enqueue_script( 'revenue-spending-goal' );
						wp_enqueue_style( 'revenue-campaign-spending_goal' );
					} else {
						wp_enqueue_script( 'revenue-v1-spending-goal' );
						wp_enqueue_style( 'revenue-v1-campaign-spending_goal' );
					}

					$placement_settings = revenue()->get_placement_settings( $campaign['id'] );

					$file_path = revenue()->get_campaign_path( $campaign, 'hellobar', 'spending-goal' );

					$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'spending_goal', 'hellobar', $campaign );

					ob_start();
					if ( file_exists( $file_path ) ) {
						do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
						extract( $data ); //phpcs:ignore
						include $file_path;
					}

					$output .= ob_get_clean();

					revenue()->update_campaign_impression( $campaign['id'] );

				}

				if ( $output ) {
					echo $output; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
		}
	}
}
