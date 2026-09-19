<?php // phpcs:ignore

namespace WTRS\Includes\Utils;

use WTRS\Includes\Xpo;

defined( 'ABSPATH' ) || exit;

/**
 * Flags
 */
final class Flags {

	public const MAX_SHIPPING_RULES           = 9999999;
	public const MAX_CONDITION_GROUPS         = 9999999;
	public const MAX_NESTED_CONDITIONS        = 9999999;
	public const MAX_SHIPPING_METHODS         = 9999999;
	public const MAX_FLEXIBLE_SEGMENTS        = 9999999;
	public const HANDLING_FEE_PRO             = true;
	public const PRODUCT_ATTRIBUTES_PRO       = true;
	public const SPECIAL_ACTIONS_PRO          = true;
	public const MAX_RATE_TIER_CONDITION      = 9999999;
	public const RATE_TIER_CONDITION_LIMIT    = 3;
	public const RULE_VISIBILITY_FREE_OPTIONS = array(
		'both',
	);
	public const CONDITION_FREE_OPTIONS       = array(
		'always',
		'cart_quantity',
		'cart_total',
		'cart_subtotal',
		'cart_weight',
		'cart_weight',
		'cart_coupons',
		'coupons',
		'cart_length',
		'cart_width',
		'cart_height',
		'cart_volume',
	);
	public const CRITERIA_FREE_OPTIONS        = array(
		'always',
		'cart_quantity',
		'cart_total',
		'cart_subtotal',
		'cart_weight',
		'cart_weight',
		'cart_coupons',
		'coupons',
		'cart_length',
		'cart_width',
		'cart_height',
		'cart_volume',
	);

	public const RATE_CALCULATION_FREE_OPTIONS = array(
		'sum',
	);

	/**
	 * Get flags
	 *
	 * @return array
	 */
	public static function get_flags() {
		return array(
			'MAX_SHIPPING_RULES'            => self::MAX_SHIPPING_RULES,
			'MAX_CONDITION_GROUPS'          => self::MAX_CONDITION_GROUPS,
			'MAX_NESTED_CONDITIONS'         => self::MAX_NESTED_CONDITIONS,
			'MAX_SHIPPING_METHODS'          => self::MAX_SHIPPING_METHODS,
			'MAX_FLEXIBLE_SEGMENTS'         => self::MAX_FLEXIBLE_SEGMENTS,
			'HANDLING_FEE_PRO'              => self::HANDLING_FEE_PRO,
			'PRODUCT_ATTRIBUTES_PRO'        => self::PRODUCT_ATTRIBUTES_PRO,
			'RULE_VISIBILITY_FREE_OPTIONS'  => self::RULE_VISIBILITY_FREE_OPTIONS,
			'CONDITION_FREE_OPTIONS'        => self::CONDITION_FREE_OPTIONS,
			'CRITERIA_FREE_OPTIONS'         => self::CRITERIA_FREE_OPTIONS,
			'RATE_CALCULATION_FREE_OPTIONS' => self::RATE_CALCULATION_FREE_OPTIONS,
			'MAX_RATE_TIER_CONDITION'       => self::MAX_RATE_TIER_CONDITION,
			'RATE_TIER_CONDITION_LIMIT'     => self::RATE_TIER_CONDITION_LIMIT,
			'SPECIAL_ACTIONS_PRO'           => self::SPECIAL_ACTIONS_PRO,
		);
	}

	/**
	 * Checks if user is admin or shop manager.
	 *
	 * @return boolean
	 */
	public static function is_user_admin() {
		return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' ); // phpcs:ignore
	}

	/**
	 * Check if current user is free user.
	 *
	 * @return boolean
	 */
	public static function is_pro_active() {
		return Xpo::is_lc_active();
	}

	/**
	 * Check if current user is free user.
	 *
	 * @return boolean
	 */
	public static function is_free_user() {
		return ! self::is_pro_active();
	}

	/**
	 * Check if current user can preview pro features.
	 *
	 * @return boolean
	 */
	public static function show_pro_preview() {
		return self::is_free_user() && self::is_user_admin();
	}

	/**
	 * Check if current user can preview pro features.
	 *
	 * @return boolean
	 */
	public static function is_pro_or_can_preview() {
		return self::is_pro_active() || self::show_pro_preview();
	}
}
