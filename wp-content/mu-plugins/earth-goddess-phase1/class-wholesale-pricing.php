<?php
/**
 * Role-based wholesale RRP discounts + Starter opening-order minimum.
 */

defined( 'ABSPATH' ) || exit;

class EG_Wholesale_Pricing {

	/**
	 * Discount off RRP by wholesale role.
	 *
	 * @var array<string,float>
	 */
	const DISCOUNTS = array(
		'wholesale_starter'    => 0.35,
		'wholesale_preferred'  => 0.40,
		'wholesale_elite'      => 0.45,
	);

	const STARTER_MIN_OPENING = 250;

	/**
	 * Prevent recursive filter calls while reading raw product prices.
	 *
	 * @var bool
	 */
	private static $filtering = false;

	/**
	 * Bootstrap.
	 */
	public static function init() {
		add_filter( 'woocommerce_product_get_price', array( __CLASS__, 'filter_price' ), 20, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( __CLASS__, 'filter_price' ), 20, 2 );
		add_filter( 'woocommerce_product_get_sale_price', array( __CLASS__, 'filter_sale_price' ), 20, 2 );
		add_filter( 'woocommerce_product_variation_get_sale_price', array( __CLASS__, 'filter_sale_price' ), 20, 2 );
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'filter_price_html' ), 20, 2 );
		add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'enforce_starter_opening_minimum' ) );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'enforce_starter_opening_minimum' ) );
	}

	/**
	 * @param mixed      $price   Price.
	 * @param WC_Product $product Product.
	 * @return mixed
	 */
	public static function filter_price( $price, $product ) {
		$discounted = self::maybe_discount( $price, $product );
		return ( null !== $discounted ) ? $discounted : $price;
	}

	/**
	 * Keep sale price aligned when wholesale pricing applies.
	 *
	 * @param mixed      $price   Sale price.
	 * @param WC_Product $product Product.
	 * @return mixed
	 */
	public static function filter_sale_price( $price, $product ) {
		$discounted = self::maybe_discount( $price, $product );
		if ( null === $discounted ) {
			return $price;
		}
		// Always expose a sale price so catalogue shows RRP → wholesale.
		return $discounted;
	}

	/**
	 * @param string     $html    HTML.
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public static function filter_price_html( $html, $product ) {
		if ( ! EG_Roles::user_is_wholesale() ) {
			return $html;
		}
		$regular = (float) $product->get_regular_price( 'edit' );
		if ( $regular <= 0 ) {
			return $html;
		}
		$discounted = self::resolve_wholesale_price( $product );
		if ( null === $discounted ) {
			return $html;
		}
		return wc_format_sale_price(
			wc_get_price_to_display( $product, array( 'price' => $regular ) ),
			wc_get_price_to_display( $product, array( 'price' => $discounted ) )
		) . $product->get_price_suffix();
	}

	/**
	 * @param mixed      $price   Price.
	 * @param WC_Product $product Product.
	 * @return float|null Null when no change.
	 */
	private static function maybe_discount( $price, $product ) {
		if ( self::$filtering || ! is_user_logged_in() || ! EG_Roles::user_is_wholesale() ) {
			return null;
		}
		if ( ! ( $product instanceof WC_Product ) ) {
			return null;
		}

		self::$filtering = true;
		try {
			return self::resolve_wholesale_price( $product );
		} finally {
			self::$filtering = false;
		}
	}

	/**
	 * On sale: role % off the sale price. Not on sale: role % off regular RRP.
	 *
	 * @param WC_Product $product Product.
	 * @return float|null
	 */
	private static function resolve_wholesale_price( $product ) {
		$regular = (float) $product->get_regular_price( 'edit' );
		$sale    = $product->get_sale_price( 'edit' );
		$on_sale = ( '' !== $sale && null !== $sale && (float) $sale > 0 );

		$base = $on_sale ? (float) $sale : $regular;
		if ( $base <= 0 ) {
			return null;
		}

		return self::apply_role_discount( $base );
	}

	/**
	 * @param float $base Base price.
	 * @return float|null
	 */
	public static function apply_role_discount( $base ) {
		$role = EG_Roles::get_wholesale_role();
		if ( ! $role || ! isset( self::DISCOUNTS[ $role ] ) ) {
			return null;
		}
		$rate = self::DISCOUNTS[ $role ];
		return round( (float) $base * ( 1 - $rate ), wc_get_price_decimals() );
	}

	/**
	 * Starter accounts: first completed order must be >= $250.
	 */
	public static function enforce_starter_opening_minimum() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		$user = wp_get_current_user();
		if ( ! $user || ! in_array( 'wholesale_starter', (array) $user->roles, true ) ) {
			return;
		}
		if ( self::user_has_completed_order( $user->ID ) ) {
			return;
		}
		$total = (float) WC()->cart->get_subtotal();
		if ( $total < self::STARTER_MIN_OPENING ) {
			wc_add_notice(
				sprintf(
					/* translators: %s: minimum opening order amount */
					__( 'Wholesale Starter opening order must be at least %s.', 'earth-goddess' ),
					wc_price( self::STARTER_MIN_OPENING )
				),
				'error'
			);
		}
	}

	/**
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function user_has_completed_order( $user_id ) {
		$orders = wc_get_orders(
			array(
				'customer_id' => $user_id,
				'status'      => array( 'wc-completed', 'wc-processing' ),
				'limit'       => 1,
				'return'      => 'ids',
			)
		);
		return ! empty( $orders );
	}
}
