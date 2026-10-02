<?php
/**
 * VIP Shopper member discounts from annual spend (plan.pdf Moon/Star/Goddess).
 * Requires the vip_shopper role; wholesale roles use wholesale pricing instead.
 */

defined( 'ABSPATH' ) || exit;

class EG_VIP_Discounts {

	/**
	 * Annual spend tiers => cart percent discount.
	 *
	 * @var array<int,float>
	 */
	const TIERS = array(
		1000 => 15.0, // Goddess
		500  => 12.0, // Star
		0    => 10.0, // Moon
	);

	/**
	 * Bootstrap.
	 */
	public static function init() {
		add_action( 'woocommerce_cart_calculate_fees', array( __CLASS__, 'apply_member_discount' ), 20 );
	}

	/**
	 * Apply negative fee for VIP member discount.
	 *
	 * @param WC_Cart $cart Cart.
	 */
	public static function apply_member_discount( $cart ) {
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}
		if ( ! is_user_logged_in() || EG_Roles::user_is_wholesale() ) {
			return;
		}

		$user_id = get_current_user_id();
		$roles   = (array) wp_get_current_user()->roles;

		// VIP Shopper members only. Customers, affiliates and ambassadors pay full price.
		if ( ! in_array( 'vip_shopper', $roles, true ) ) {
			return;
		}

		$percent = self::get_discount_percent( $user_id );
		if ( $percent <= 0 ) {
			return;
		}

		$subtotal = (float) $cart->get_subtotal();
		if ( $subtotal <= 0 ) {
			return;
		}

		$amount = round( $subtotal * ( $percent / 100 ), wc_get_price_decimals() );
		if ( $amount <= 0 ) {
			return;
		}

		$cart->add_fee(
			sprintf(
				/* translators: %s: percent */
				__( 'VIP Member Discount (%s%%)', 'earth-goddess' ),
				wc_format_decimal( $percent, 0 )
			),
			-1 * $amount,
			false
		);
	}

	/**
	 * @param int $user_id User ID.
	 * @return float
	 */
	public static function get_discount_percent( $user_id ) {
		$spend = self::get_annual_spend( $user_id );
		foreach ( self::TIERS as $min => $percent ) {
			if ( $spend >= $min ) {
				return (float) $percent;
			}
		}
		return 10.0;
	}

	/**
	 * Sum of completed/processing orders in the last 365 days.
	 *
	 * @param int $user_id User ID.
	 * @return float
	 */
	public static function get_annual_spend( $user_id ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return 0.0;
		}
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status'      => array( 'wc-completed', 'wc-processing' ),
				'date_created' => '>=' . gmdate( 'Y-m-d', strtotime( '-365 days' ) ),
				'limit'       => -1,
				'return'      => 'objects',
			)
		);
		$total = 0.0;
		foreach ( $orders as $order ) {
			if ( $order instanceof WC_Order ) {
				$total += (float) $order->get_total();
			}
		}
		return $total;
	}

	/**
	 * Tier label for SOP / dashboards.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	public static function get_tier_label( $user_id ) {
		$spend = self::get_annual_spend( $user_id );
		if ( $spend >= 1000 ) {
			return 'Goddess';
		}
		if ( $spend >= 500 ) {
			return 'Star';
		}
		return 'Moon';
	}
}
