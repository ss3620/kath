<?php
/**
 * Some common functions for Affiliate For WooCommerce
 *
 * @package     affiliate-for-woocommerce/includes/
 * @since       1.0.0
 * @version     1.34.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encode affiliate id
 *
 * @param  integer $affiliate_id The affiliate id.
 * @return integer
 */
function afwc_encode_affiliate_id( $affiliate_id ) {
	return $affiliate_id;
}

/**
 * Get commission statuses
 *
 * @param string $status Commission Status.
 *
 * @return array|string
 */
function afwc_get_commission_statuses( $status = '' ) {
	$statuses = array(
		AFWC_REFERRAL_STATUS_DRAFT    => _x( 'Draft', 'Commission status title for draft', 'affiliate-for-woocommerce' ),
		AFWC_REFERRAL_STATUS_PAID     => _x( 'Paid', 'Commission status title for paid', 'affiliate-for-woocommerce' ),
		AFWC_REFERRAL_STATUS_UNPAID   => _x( 'Unpaid', 'Commission status title for unpaid', 'affiliate-for-woocommerce' ),
		AFWC_REFERRAL_STATUS_REJECTED => _x( 'Rejected', 'Commission status title for rejected', 'affiliate-for-woocommerce' ),
	);

	// Return array of statuses if the provided status is empty.
	if ( empty( $status ) ) {
		return $statuses;
	}

	return ( ! empty( $statuses[ $status ] ) ) ? $statuses[ $status ] : '';
}

/**
 * Get commission status colors.
 *
 * @param string $status Commission Status.
 *
 * @return array|string
 */
function afwc_get_commission_status_colors( $status = '' ) {

	/**
	 * Filter to modify the commission status colors.
	 *
	 * @param array $colors Array of commission status colors.
	 *
	 * @since 6.28.0
	 */
	$colors = apply_filters(
		'afwc_commission_status_colors',
		array(
			AFWC_REFERRAL_STATUS_PAID     => 'green',
			AFWC_REFERRAL_STATUS_UNPAID   => 'orange',
			AFWC_REFERRAL_STATUS_REJECTED => 'red',
			AFWC_REFERRAL_STATUS_DRAFT    => 'gray',
		)
	);

	// Return array of colors if the provided status is empty.
	if ( empty( $status ) ) {
		return $colors;
	}

	return ( ! empty( $colors[ $status ] ) ) ? $colors[ $status ] : '';
}

/**
 * Get payout methods.
 *
 * @param string $method Payout method.
 *
 * @return array|string
 */
function afwc_get_payout_methods( $method = '' ) {
	$payout_methods = array(
		'paypal'            => esc_html_x( 'PayPal', 'PayPal: Pay & record payout method title', 'affiliate-for-woocommerce' ),
		'paypal-manual'     => esc_html_x( 'PayPal Manual', 'PayPal: record only manual payout method title', 'affiliate-for-woocommerce' ),
		'stripe'            => esc_html_x( 'Stripe', 'Stripe payout method title', 'affiliate-for-woocommerce' ),
		'other'             => esc_html_x( 'Other', 'Other payout method title', 'affiliate-for-woocommerce' ),
		'coupon-fixed-cart' => esc_html_x( 'Fixed Cart Coupon', 'Coupon: Fixed Cart payout method title', 'affiliate-for-woocommerce' ),
		'wsc-store-credit'  => esc_html_x( 'Store Credit Coupon', 'Coupon: Store Credit payout method title', 'affiliate-for-woocommerce' ),
	);

	// Return array of payout methods if method is not provided.
	if ( empty( $method ) ) {
		return $payout_methods;
	}

	return ( ! empty( $payout_methods[ $method ] ) ) ? $payout_methods[ $method ] : $method;
}

/**
 * Get payout methods supported for automatic payouts.
 *
 * @return array
 */
function afwc_get_automatic_payout_methods() {
	$payout_methods = array( 'paypal', 'stripe', 'coupon-fixed-cart', 'wsc-store-credit' );

	return $payout_methods;
}


if ( ! function_exists( 'afwc_get_available_payout_methods' ) ) {
	/**
	 * Function to get the available/enabled payout methods.
	 *
	 * @return array Return the array of payout methods.
	 */
	function afwc_get_available_payout_methods() {
		$methods = array();

		if ( 'yes' === get_option( 'afwc_enable_stripe_payout', 'no' ) ) {
			$stripe = is_callable( array( 'AFWC_Stripe_API', 'get_instance' ) ) ? AFWC_Stripe_API::get_instance() : null;
			if ( is_callable( array( $stripe, 'is_enabled' ) ) && $stripe->is_enabled() ) {
				$methods['stripe'] = afwc_get_payout_methods( 'stripe' );
			}
		}

		$paypal = is_callable( array( 'AFWC_PayPal_API', 'get_instance' ) ) ? AFWC_PayPal_API::get_instance() : null;
		if ( is_callable( array( $paypal, 'is_enabled' ) ) && $paypal->is_enabled() ) {
			$methods['paypal'] = afwc_get_payout_methods( 'paypal' );
		}

		$coupon_fixed_cart = is_callable( array( 'AFWC_Coupon_API', 'get_instance' ) ) ? AFWC_Coupon_API::get_instance() : null;
		if ( is_callable( array( $coupon_fixed_cart, 'is_enabled' ) ) && $coupon_fixed_cart->is_enabled() ) {
			$methods['coupon-fixed-cart'] = afwc_get_payout_methods( 'coupon-fixed-cart' );
		}

		$coupon_store_credit = is_callable( array( 'WSC_AFWC_Store_Credit_API', 'get_instance' ) ) ? WSC_AFWC_Store_Credit_API::get_instance() : null;
		if ( is_callable( array( $coupon_store_credit, 'is_enabled' ) ) && $coupon_store_credit->is_enabled() ) {
			$methods['wsc-store-credit'] = afwc_get_payout_methods( 'wsc-store-credit' );
		}

		return $methods;
	}
}

if ( ! function_exists( 'afwc_get_available_payout_methods_for_affiliate' ) ) {
	/**
	 * Function to get the available payout methods for affiliate.
	 *
	 * @param int $affiliate_id The affiliate ID.
	 *
	 * @return array Return the array of payout methods.
	 */
	function afwc_get_available_payout_methods_for_affiliate( $affiliate_id = 0 ) {
		$methods = array();

		$affiliate_id = ( ! empty( $affiliate_id ) ? $affiliate_id : get_current_user_id() );

		$selected_method = get_user_meta( $affiliate_id, 'afwc_payout_method', true );

		$enabled_payout_methods = afwc_get_available_payout_methods();

		// PayPal will be shown in either of the below case.
		if ( 'yes' === get_option( 'afwc_allow_paypal_email', 'no' ) || 'paypal' === $selected_method ) {
			$methods['paypal'] = esc_html_x( 'PayPal', 'PayPal payout method title', 'affiliate-for-woocommerce' );
		}

		// Stripe.
		$set_stripe = false;
		if ( is_array( $enabled_payout_methods ) && in_array( 'stripe', array_keys( $enabled_payout_methods ), true ) ) {
			$set_stripe = true;

			if ( 'stripe' === $selected_method ) {
				$set_stripe = true;
			}

			if ( $set_stripe ) {
				$methods['stripe'] = esc_html_x( 'Stripe', 'Stripe payout method title', 'affiliate-for-woocommerce' );
			}
		}

		// Coupon: fixed cart.
		if ( is_array( $enabled_payout_methods ) && in_array( 'coupon-fixed-cart', array_keys( $enabled_payout_methods ), true ) ) {
			$methods['coupon-fixed-cart'] = afwc_get_payout_methods( 'coupon-fixed-cart' );
		}

		// Coupon: Store credit from Smart Coupons.
		if ( is_array( $enabled_payout_methods ) && in_array( 'wsc-store-credit', array_keys( $enabled_payout_methods ), true ) ) {
			$methods['wsc-store-credit'] = afwc_get_payout_methods( 'wsc-store-credit' );
		}

		return $methods;
	}
}

/**
 * Get table name
 *
 * @param  string $name The table.
 * @return string
 */
function afwc_get_tablename( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'afwc_' . $name;
}

/**
 * Get referrer id.
 *
 * @deprecated 8.56.0 use Referral_Medium_Interface instead.
 *
 * @param string|int $customer The customer email address or customer's user ID.
 * @param WC_Order   $order The order object.
 *
 * @return integer Return the affiliate ID, either from customer's lifetime affiliate or cookie.
 */
function afwc_get_referrer_id( $customer = '', $order = null ) {

	_deprecated_function( __FUNCTION__, '8.56.0', 'AFWC_API::determine_affiliate_for_order' );

	// If the lifetime commission is enabled, check for a lifetime affiliate for the customer.
	if ( ! empty( $customer ) && 'yes' === get_option( 'afwc_enable_lifetime_commissions', 'no' ) ) {
		$ltc_affiliate = afwc_get_ltc_affiliate_by_customer( $customer );
		$affiliate_obj = ! empty( $ltc_affiliate ) ? new AFWC_Affiliate( $ltc_affiliate ) : null;

		if ( is_object( $affiliate_obj ) && is_callable( array( $affiliate_obj, 'is_ltc_enabled' ) ) && $affiliate_obj->is_ltc_enabled() ) {
			return intval( $ltc_affiliate );
		}
	}

	// if affiliate ID is not found from lifetime commission, check for the referral coupon.
	if ( $order instanceof WC_Order && is_callable( array( $order, 'get_coupon_codes' ) ) ) {
		$used_coupons = $order->get_coupon_codes();

		if ( ! empty( $used_coupons ) && is_array( $used_coupons ) ) {

			$credit_policy = get_option( 'afwc_credit_affiliate', 'last' );
			$afwc_coupon   = ( is_callable( array( 'AFWC_Coupon', 'get_instance' ) ) ) ? AFWC_Coupon::get_instance() : null;

			if ( ! empty( $afwc_coupon ) && is_callable( array( $afwc_coupon, 'get_affiliate' ) ) ) {
				$affiliate_id = 0;

				foreach ( $used_coupons as $coupon_code ) {
					// If the credit policy is set to 'first', we only need the first affiliate ID.
					if ( 'first' === $credit_policy && ! empty( $affiliate_id ) ) {
						return intval( $affiliate_id );
					}
					$affiliate_id = $afwc_coupon->get_affiliate( $coupon_code );
				}
				// If the affiliate ID is found, return it.
				if ( ! empty( $affiliate_id ) ) {
					return intval( $affiliate_id );
				}
			}
		}
	}

	/**
	 * Return the affiliate ID from the cookie.
	 */
	return ! empty( $_COOKIE[ AFWC_AFFILIATES_COOKIE_NAME ] ) // phpcs:ignore
		? intval( wc_clean( wp_unslash( $_COOKIE[ AFWC_AFFILIATES_COOKIE_NAME ] ) ) ) // phpcs:ignore
		: 0;
}

/**
 * Get campaign id from cookie.
 *
 * @return integer Return the campaign id if exists in the cookie otherwise 0.
 */
function afwc_get_campaign_id() {
	return ! empty( $_COOKIE[ AFWC_CAMPAIGN_COOKIE_NAME ] ) ? intval( wc_clean( wp_unslash( $_COOKIE[ AFWC_CAMPAIGN_COOKIE_NAME ] ) ) ) : 0; // phpcs:ignore
}

/**
 * Get hit id from cookie.
 *
 * @return integer Return the hit id if exists in the cookie otherwise 0.
 */
function afwc_get_hit_id() {
	return ! empty( $_COOKIE[ AFWC_HIT_COOKIE_NAME ] ) ? intval( wc_clean( wp_unslash( $_COOKIE[ AFWC_HIT_COOKIE_NAME ] ) ) ) : 0; // phpcs:ignore
}

/**
 * Get user id based on affiliate id.
 *
 * @param  integer $affiliate_id The affiliate id.
 * @return integer
 */
function afwc_get_user_id_based_on_affiliate_id( $affiliate_id ) {
	// Currently we don't have "affiliates_users" table. So return incoming ID.
	return $affiliate_id;

	// phpcs:disable
	global $wpdb;

	$afwc_affiliates_users = afwc_get_tablename( 'affiliates_users' );
	$is_table              = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $afwc_affiliates_users ) ); // phpcs:ignore

	if ( ! empty( $is_table ) ) {
		if ( is_numeric( $affiliate_id ) ) {
			$result = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$wpdb->prefix}afwc_affiliates_users WHERE affiliate_id = %d ", $affiliate_id ) ); // phpcs:ignore
		} else {
			$result            = 0;
			$results           = $wpdb->get_results( "SELECT user_id, MD5( affiliate_id ) AS affiliate_id_md5 FROM {$wpdb->prefix}afwc_affiliates_users", ARRAY_A ); // phpcs:ignore
			$user_to_affiliate = array();
			foreach ( $results as $result ) {
				$user_to_affiliate[ $result['user_id'] ] = $result['affiliate_id_md5'];
			}
			$user_id = array_search( $affiliate_id, $user_to_affiliate, true );
			if ( false !== $user_id ) {
				$result = $user_id;
			}
		}
	}

	if ( ! empty( $result ) ) {
		$affiliate_id = $result;
	}

	$user = get_user_by( 'id', $affiliate_id );

	return ( $user ? $affiliate_id : '' );
	// phpcs:enable
}

/**
 * Get affiliate id based on user id.
 *
 * @param  integer $user_id The user id.
 * @return integer
 */
function afwc_get_affiliate_id_based_on_user_id( $user_id ) {
	// Currently we don't have "affiliates_users" table. So return incoming user_ID.
	return $user_id;

	// phpcs:disable
	global $wpdb;

	$afwc_affiliates_users = afwc_get_tablename( 'affiliates_users' );
	$is_table              = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $afwc_affiliates_users ) ); // phpcs:ignore

	if ( ! empty( $is_table ) ) {
		$result = $wpdb->get_var( $wpdb->prepare( "SELECT affiliate_id FROM {$wpdb->prefix}afwc_affiliates_users WHERE user_id = %d ", $user_id ) ); // phpcs:ignore
		if ( ! empty( $result ) ) {
			$user_id = $result;
		}
	}

	return $user_id;
	// phpcs:enable
}

/**
 * Check if a provided plugin is active or not
 *
 * @param  string $plugin The plugin to check.
 * @return boolean
 */
function afwc_is_plugin_active( $plugin = '' ) {
	if ( ! empty( $plugin ) ) {
		if ( function_exists( 'is_plugin_active' ) ) {
			return is_plugin_active( $plugin );
		} else {
			$active_plugins = (array) get_option( 'active_plugins', array() );
			if ( is_multisite() ) {
				$active_plugins = array_merge( $active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
			}
			if ( ( in_array( $plugin, $active_plugins, true ) || array_key_exists( $plugin, $active_plugins ) ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Format a price with the appropriate currency symbol and formatting rules.
 *
 * @param float  $price    The numeric price value.
 * @param string $currency Optional. The currency code (e.g., 'USD'). If null, uses the store default.
 *
 * @return string The formatted price string with the currency symbol.
 */
function afwc_format_price( $price = 0, $currency = null ) {
	// Format the number based on WooCommerce settings.
	$formatted_number = afwc_format_number( $price );

	if ( empty( $currency ) ) {
		$currency = AFWC_CURRENCY_CODE;
	}
	$currency_symbol = get_woocommerce_currency_symbol( $currency );

	$price_format = get_woocommerce_price_format();

	$formatted_price = sprintf( $price_format, $currency_symbol, $formatted_number );

	return $formatted_price;
}

/**
 * Format a number using WooCommerce's decimal and thousand separator settings.
 *
 * @param float  $numeric_value      The numeric value.
 * @param int    $decimals           Number of decimal places.
 * @param string $decimal_separator  Decimal separator.
 * @param string $thousand_separator Thousand separator.
 *
 * @return string The formatted number string.
 */
function afwc_format_number( $numeric_value = 0, $decimals = null, $decimal_separator = null, $thousand_separator = null ) {
	// For $decimals, 'null' was checked rather than 'empty' because 0 (zero) is an acceptable value for this.
	if ( is_null( $decimals ) ) {
		$decimals = afwc_get_price_decimals();
	}

	if ( empty( $decimal_separator ) ) {
		$decimal_separator = afwc_get_price_decimal_separator();
	}

	if ( empty( $thousand_separator ) ) {
		$thousand_separator = afwc_get_price_thousand_separator();
	}
	return number_format( $numeric_value, $decimals, $decimal_separator, $thousand_separator );
}

/**
 * Return the number of decimals after the decimal point.
 *
 * @return integer
 */
function afwc_get_price_decimals() {
	return absint( get_option( 'woocommerce_price_num_decimals', 2 ) );
}

/**
 * Return the thousand separator for prices
 *
 * @return string
 */
function afwc_get_price_thousand_separator() {
	$separator = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_thousand_sep' ) ), ENT_QUOTES );
	return $separator;
}

/**
 * Return the decimal separator for prices
 *
 * @return string
 */
function afwc_get_price_decimal_separator() {
	$separator = wp_specialchars_decode( stripslashes( get_option( 'woocommerce_price_decimal_sep' ) ), ENT_QUOTES );
	return $separator ? $separator : '.';
}

/**
 * Check if the user is affiliate or not.
 *
 * @param  WP_User|int $user The user object/ID.
 *
 * @return string Return affiliate status(yes/no/pending/not_registered).
 */
function afwc_is_user_affiliate( $user = null ) {
	$is_affiliate = 'not_registered';

	// Create User object if user id is provided.
	$user = is_int( $user ) ? new WP_User( $user ) : $user;

	if ( ! $user instanceof WP_User || empty( $user->ID ) ) {
		return $is_affiliate;
	}

	// Get affiliate status from meta.
	$have_meta = get_user_meta( $user->ID, 'afwc_is_affiliate', true );

	if ( empty( $have_meta ) ) {
		// Check if the affiliate exists in the affiliate user roles.
		$user_roles   = ! empty( $user->roles ) ? $user->roles : array();
		$is_affiliate = ( true === afwc_is_affiliate_user_role( $user_roles ) ) ? 'yes' : $is_affiliate;
	} else {
		// Assign the affiliate meta.
		$is_affiliate = $have_meta;
	}

	return $is_affiliate;
}

/**
 * Function to create page for registration
 *
 * @return int
 */
function afwc_create_reg_form_page() {
	$slug    = 'affiliates';
	$page_id = '';
	if ( ! get_page_by_path( $slug ) || ! get_page_by_path( 'afwc_registration_form' ) ) {
		$reg_page = array(
			'post_type'    => 'page',
			'post_name'    => $slug,
			'post_title'   => __( 'Join our affiliate program', 'affiliate-for-woocommerce' ),
			'post_status'  => 'draft',
			'post_content' => '[afwc_registration_form]',
		);
		$page_id  = wp_insert_post( $reg_page );
	}
	return $page_id;
}

/**
 * Function to get campaign id from slug
 *
 * @param string $slug campaign slug to get campaign id.
 * @return int $campaign_id campaign id.
 */
function afwc_get_campaign_id_by_slug( $slug ) {
	global $wpdb;
	$campaign_id = $wpdb->get_var( // phpcs:ignore
		$wpdb->prepare( // phpcs:ignore
			"SELECT id FROM {$wpdb->prefix}afwc_campaigns WHERE slug = %s AND status = %s",
			array( $slug, 'Active' )
		)
	);
	$campaign_id = ! empty( $campaign_id ) ? $campaign_id : 0;
	return $campaign_id;
}

/**
 * Function to check if we have any active campaigns.
 *
 * @param bool $check_rules Whether to validate the rules.
 * @param int  $campaign_id The campaign ID to check for a single campaign.
 *
 * @return bool if we find active campaigns for the current users otherwise false.
 */
function afwc_is_campaign_active( $check_rules = true, $campaign_id = 0 ) {
	if ( ! class_exists( 'AFWC_Campaign_Dashboard' ) ) {
		include_once AFWC_PLUGIN_DIRPATH . '/includes/admin/class-afwc-campaign-dashboard.php';
	}
	$campaign_dashboard = AFWC_Campaign_Dashboard::get_instance();

	/**
	 * Filter to modify the campaign active status.
	 *
	 * @param bool Whether the campaign is active or not.
	 *
	 * @since 8.6.0
	 */
	return apply_filters(
		'afwc_is_campaign_active',
		is_callable( array( $campaign_dashboard, 'fetch_campaigns' ) ) ? ! empty(
			$campaign_dashboard->fetch_campaigns(
				array(
					'campaign_status' => 'Active',
					'affiliate_id'    => get_current_user_id(),
					'check_rules'     => $check_rules,
					'campaign_id'     => $campaign_id,
				)
			)
		) : false
	);
}

/**
 * Add prefix to WC order statuses
 *
 * @return $prefixed_statuses
 */
function afwc_get_prefixed_order_statuses() {
	$statuses = wc_get_is_paid_statuses();

	$prefixed_statuses = array();
	foreach ( $statuses as $key => $value ) {
		$prefixed_statuses[ $key ] = 'wc-' . $value;
	}

	return $prefixed_statuses;
}

/**
 * Get id name map for affiliate tags
 *
 * @return array $result
 */
function afwc_get_user_tags_id_name_map() {
	$result = array();
	$terms  = get_terms(
		array(
			'taxonomy'   => 'afwc_user_tags',
			'hide_empty' => false,
		)
	);
	if ( ! is_wp_error( $terms ) ) {
		foreach ( $terms as $key => $value ) {
			$result[ $value->term_id ] = $value->name;
		}
	}
	return $result;
}

/**
 * Get WC paid status.
 *
 * @return array $wc_paid_statuses
 */
function afwc_get_paid_order_status() {
	$wc_paid_statuses = array();
	$wc_paid_statuses = wc_get_is_paid_statuses();

	/**
	 * Filter to modify the paid order statuses.
	 *
	 * @param array $wc_paid_statuses Array of paid order statuses.
	 *
	 * @since 1.8.0
	 */
	$wc_paid_statuses = apply_filters( 'afwc_paid_order_statuses', $wc_paid_statuses );
	foreach ( $wc_paid_statuses as $key => $value ) {
		$wc_paid_statuses[ $key ] = afwc_prefix_wc_to_order_status( $value );
	}
	return $wc_paid_statuses;
}

/**
 * Get WC unpaid status.
 *
 * @return array $wc_reject_statuses
 */
function afwc_get_reject_order_status() {
	$wc_reject_statuses = array();

	/**
	 * Filter to modify the rejected order statuses.
	 *
	 * @param array Array of rejected order statuses.
	 *
	 * @since 1.8.0
	 */
	$wc_reject_statuses = apply_filters( 'afwc_rejected_order_statuses', array( 'refunded', 'cancelled', 'failed', 'draft' ) );
	foreach ( $wc_reject_statuses as $key => $value ) {
		$wc_reject_statuses[ $key ] = afwc_prefix_wc_to_order_status( $value );
	}
	return $wc_reject_statuses;
}

/**
 * Function to prefix order status if not present.
 *
 * @param string $order_status The order status.
 * @return string The prefixed order status.
 */
function afwc_prefix_wc_to_order_status( $order_status = '' ) {
	if ( empty( $order_status ) ) {
		return;
	}

	$prefixed_order_status = ( strpos( $order_status, 'wc-' ) === false ) ? 'wc-' . $order_status : $order_status;

	return $prefixed_order_status;
}

/**
 * Get default plan details.
 *
 * @return array Return default plan details.
 */
function afwc_get_default_plan_details() {
	global $wpdb;

	$default_plan_id = afwc_get_default_commission_plan_id();

	if ( empty( $default_plan_id ) ) {
		return array();
	}

	$afwc_commission      = is_callable( array( 'AFWC_Commission_Plans', 'get_instance' ) ) ? AFWC_Commission_Plans::get_instance() : null;
	$default_plan_details = $afwc_commission instanceof AFWC_Commission_Plans && is_callable( array( $afwc_commission, 'get_plans' ) )
		? $afwc_commission->get_plans( array( 'id' => $default_plan_id ) )
		: array();

	return ( ! empty( $default_plan_details ) && is_array( $default_plan_details ) ) ? reset( $default_plan_details ) : array();
}

/**
 * Get default commission plan id.
 *
 * @return int
 */
function afwc_get_default_commission_plan_id() {
	/**
	 * Filter to modify the default commission plan ID.
	 *
	 * @param int The default commission plan ID.
	 *
	 * @since 1.27.0
	 */
	return apply_filters(
		'afwc_default_commission_plan_id',
		intval( get_option( 'afwc_default_commission_plan_id', 0 ) )
	);
}

/**
 * Check if self-refer is allowed by the affiliate settings.
 *
 * @return bool Return true if affiliates are allowed to self-refer otherwise false.
 */
function afwc_allow_self_refer() {
	return boolval( 'yes' === get_option( 'afwc_allow_self_refer', 'yes' ) );
}

/**
 * Get regex pattern for affiliate identifier.
 *
 * @return string Return the regex pattern for affiliate identifier. Allows only the alphabets and numbers and the pattern should start from the alphabets.
 */
function afwc_affiliate_identifier_regex_pattern() {
	/**
	 * Filter to modify the affiliate identifier regex pattern.
	 *
	 * @param string $pattern The regex pattern.
	 *
	 * @since 6.27.0
	 */
	return apply_filters( 'afwc_affiliate_identifier_regex_pattern', '^[a-zA-Z]\w*$' );
}

/**
 * Get the allowed character set for the tracking param name.
 *
 * @return string Return the allowed-character regex.
 */
function afwc_pname_allowed_chars_regex() {
	/**
	 * Filter to modify the tracking param name allowed-character regex.
	 *
	 * @param string The regex pattern.
	 *
	 * @since 9.10.0
	 */
	return apply_filters( 'afwc_pname_allowed_chars_regex', '^[a-zA-Z0-9_-]+$' );
}

/**
 * Get affiliate tracking param name.
 *
 * @return string Affiliate tracking param name.
 */
function afwc_get_pname() {
	$pname = get_option( 'afwc_pname', 'ref' );
	$pname = ( ! empty( $pname ) ) ? $pname : 'ref';
	return $pname;
}

/**
 * Function to get affiliate URL based on provided URL.
 *
 * @param string $url                   The existing URL.
 * @param string $pname                 Affiliate tracking param name.
 * @param string $affiliate_identifier  Affiliate's unique ID.
 *
 * @return string Updated affiliate URL
 */
function afwc_get_affiliate_url( $url = '', $pname = '', $affiliate_identifier = '' ) {
	if ( empty( $url ) ) {
		return '';
	}

	if ( empty( $pname ) ) {
		$pname = afwc_get_pname();
	}

	if ( 'yes' === get_option( 'afwc_use_pretty_referral_links', 'no' ) ) {
		$url_path = wp_parse_url( $url, PHP_URL_PATH );
		// Update the path by appending referral tracking param.
		$update_path   = trailingslashit( ( ! empty( $url_path ) ? $url_path : '' ) ) . $pname . '/' . $affiliate_identifier;
		$affiliate_url = afwc_process_url( $url, array( 'path' => trailingslashit( $update_path ) ) );
	} else {
		$affiliate_url = add_query_arg( $pname, $affiliate_identifier, $url );
	}

	return $affiliate_url;
}

/**
 * Function to check if HPOS is enabled.
 *
 * @return boolean
 */
function afwc_is_hpos_enabled() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) && is_callable( array( '\Automattic\WooCommerce\Utilities\OrderUtil', 'custom_orders_table_usage_is_enabled' ) ) ) {
		return \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	return false;
}

/**
 * Get the lifetime affiliate by customer.
 *
 * @param string|int $customer The customer email address or customer ID.
 *
 * @return int Return the affiliate Id if the customer is linked with any affiliate otherwise 0.
 */
function afwc_get_ltc_affiliate_by_customer( $customer = '' ) {
	if ( 'no' === get_option( 'afwc_enable_lifetime_commissions', 'no' ) ) {
		return 0;
	}

	global $wpdb;

	$affiliate_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"SELECT DISTINCT um.user_id
		   FROM {$wpdb->prefix}usermeta as um
		   WHERE ( um.meta_key = %s AND FIND_IN_SET(%s, um.meta_value) > 0 )",
			esc_sql( 'afwc_ltc_customers' ),
			esc_sql( $customer )
		)
	);

	if ( ! empty( $affiliate_id ) ) {
		return intval( $affiliate_id );
	}

	/**
	 * Filter to check whether to get lifetime affiliate from guest email or not.
	 *
	 * If the customer is not linked with any affiliate, check if the customer is a guest user.
	 * If the customer is a guest user, check if the email is linked with any affiliate.
	 * This is to ensure that the guest user can be linked with an affiliate if the email is already registered with an affiliate.
	 *
	 * @param bool Whether to allow checking lifetime affiliate from guest email or not.
	 *
	 * @since 8.2.0
	 */
	if ( true !== apply_filters( 'afwc_is_ltc_customer_from_guest_email', true ) ) {
		return 0;
	}

	$alternative = '';

	if ( is_numeric( $customer ) ) {
		$user        = get_userdata( $customer );
		$alternative = $user instanceof WP_User && ! empty( $user->user_email ) ? $user->user_email : '';
	}

	if ( is_email( $customer ) ) {
		$user        = get_user_by( 'email', $customer );
		$alternative = $user instanceof WP_User && ! empty( $user->ID ) ? $user->ID : '';
	}

	if ( empty( $alternative ) ) {
		return 0;
	}

	$affiliate_id = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"SELECT DISTINCT um.user_id
		   FROM {$wpdb->prefix}usermeta as um
		   WHERE ( um.meta_key = %s AND FIND_IN_SET(%s, um.meta_value) > 0 )",
			esc_sql( 'afwc_ltc_customers' ),
			esc_sql( $alternative )
		)
	);

	return ! empty( $affiliate_id ) ? intval( $affiliate_id ) : 0;
}

/**
 * Function to check whether the given roles come under the affiliate user role.
 *
 * @param array|string $user_roles The user role.
 *
 * @return boolean Return true if the user roles are selected for the affiliate role in affiliate setting.
 */
function afwc_is_affiliate_user_role( $user_roles = array() ) {
	if ( empty( $user_roles ) ) {
		return false;
	}

	if ( ! is_array( $user_roles ) ) {
		$user_roles = (array) $user_roles;
	}

	$affiliate_roles = get_option( 'affiliate_users_roles', array() );

	return ( ! empty( $affiliate_roles ) && is_array( $affiliate_roles ) ) && count( array_intersect( $affiliate_roles, $user_roles ) ) > 0;
}

if ( ! function_exists( 'afwc_get_current_url' ) ) {
	/**
	 * Function to get the current url.
	 *
	 * @return string.
	 */
	function afwc_get_current_url() {
		$server_scheme      = ( ! empty( $_SERVER['HTTPS'] ) ) ? wc_clean( wp_unslash( $_SERVER['HTTPS'] ) ) : ''; // phpcs:ignore
		$server_http_host   = ( ! empty( $_SERVER['HTTP_HOST'] ) ) ? wc_clean( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : ''; // phpcs:ignore
		$server_request_uri = ( ! empty( $_SERVER['REQUEST_URI'] ) ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; // phpcs:ignore
		$base_url           = ( ( ! empty( $server_scheme ) && 'on' === $server_scheme ) ? 'https' : 'http' ) . '://' . $server_http_host;
		return $base_url . $server_request_uri;
	}
}

if ( ! function_exists( 'afwc_myaccount_dashboard_url' ) ) {
	/**
	 * Function to get affiliate dashboard URL.
	 *
	 * @return string.
	 */
	function afwc_myaccount_dashboard_url() {

		$affiliate_dashboard_page_id = get_option( 'afwc_custom_affiliate_dashboard_page_id' );
		$affiliate_dashboard_url     = '';

		if ( ! empty( $affiliate_dashboard_page_id ) && 'publish' === get_post_status( $affiliate_dashboard_page_id ) ) {
			$affiliate_dashboard_url = get_permalink( $affiliate_dashboard_page_id );
		} else {
			$endpoint                = get_option( 'woocommerce_myaccount_afwc_dashboard_endpoint', 'afwc-dashboard' );
			$my_account_page_id      = wc_get_page_id( 'myaccount' );
			$my_account_page         = ( ! empty( $my_account_page_id ) && ( intval( $my_account_page_id ) > 0 ) ) ? wc_get_page_permalink( 'myaccount' ) : '';
			$affiliate_dashboard_url = ! empty( $my_account_page ) && ! empty( $endpoint ) ? wc_get_endpoint_url( $endpoint, '', $my_account_page ) : get_home_url();
		}

		/**
		 * Filter to modify the affiliate dashboard URL.
		 *
		 * @param string $affiliate_dashboard_url The affiliate dashboard URL.
		 *
		 * @since 6.21.0
		 */
		return apply_filters( 'afwc_myaccount_dashboard_url', $affiliate_dashboard_url );
	}
}

if ( ! function_exists( 'afwc_get_product_affiliate_url' ) ) {
	/**
	 * Function to get affiliate URL for the provided product.
	 *
	 * @param int  $product_id     The product ID.
	 * @param int  $affiliate_id   The affiliate ID.
	 * @param bool $force_generate Whether to generate forcefully without checking the exclude products.
	 *
	 * @return string Return the product affiliate URL.
	 */
	function afwc_get_product_affiliate_url( $product_id = 0, $affiliate_id = 0, $force_generate = false ) {

		$product_id   = absint( $product_id );
		$affiliate_id = absint( $affiliate_id );
		if ( empty( $product_id ) || empty( $affiliate_id ) ) {
			return '';
		}

		if ( 'no' === get_option( 'afwc_show_product_referral_url', 'no' ) ) {
			return '';
		}

		$affiliate = new AFWC_Affiliate( $affiliate_id );

		// Return if the affiliate ID is not existing for the affiliate(Not a valid affiliate).
		if ( empty( $affiliate->affiliate_id ) ) {
			return '';
		}

		if ( ! $force_generate ) {
			$excluded_products = afwc_get_storewide_excluded_products();

			// Return if the product is listed under excluded products for commission.
			if ( ! empty( $excluded_products ) && is_array( $excluded_products ) ) {
				$excluded_products = array_map( 'absint', $excluded_products );

				if ( in_array( $product_id, $excluded_products, true ) ) {
					return '';
				}
			}

			// Return if the product page assigned to another affiliate for landing page.
			if ( is_callable( 'AFWC_Landing_Page', 'is_enabled' ) && AFWC_Landing_Page::is_enabled() ) {
				$landing_page          = AFWC_Landing_Page::get_instance();
				$lp_assigned_affiliate = ( $affiliate instanceof AFWC_Affiliate && is_callable( array( $landing_page, 'get_affiliate_id' ) ) ) ? absint( $landing_page->get_affiliate_id( $product_id ) ) : 0;

				if ( ! empty( $lp_assigned_affiliate ) && ( absint( $affiliate->affiliate_id ) !== $lp_assigned_affiliate ) ) {
					return '';
				}
			}
		}

		$product = wc_get_product( $product_id );
		// Get product link.
		$product_link = ( $product instanceof WC_Product && is_callable( array( $product, 'get_permalink' ) ) ) ? $product->get_permalink( $product_id ) : '';

		if ( empty( $product_link ) ) {
			return '';
		}

		$identifier = ( $affiliate instanceof AFWC_Affiliate && is_callable( array( $affiliate, 'get_identifier' ) ) ) ? $affiliate->get_identifier() : '';

		return afwc_get_affiliate_url( $product_link, '', $identifier );
	}
}

if ( ! function_exists( 'afwc_get_storewide_excluded_products' ) ) {
	/**
	 * Function to get storewide excluded products.
	 *
	 * @return array Returns the array of product IDs otherwise empty array.
	 */
	function afwc_get_storewide_excluded_products() {
		$excluded_products = get_option( 'afwc_storewide_excluded_products', array() );

		if ( empty( $excluded_products ) || ! is_array( $excluded_products ) ) {
			return array();
		}

		return afwc_get_variable_variation_product_ids( $excluded_products );
	}
}

if ( ! function_exists( 'afwc_get_variable_variation_product_ids' ) ) {
	/**
	 * Function to get variable product IDs.
	 * This will return the parent product's ID as well as all its variation IDs for variable products.
	 *
	 * @param array $product_ids Array of product IDs.
	 *
	 * @return array The updated array of product IDs with variable + variations.
	 */
	function afwc_get_variable_variation_product_ids( $product_ids = array() ) {
		if ( empty( $product_ids ) || ! is_array( $product_ids ) ) {
			return array();
		}

		$ids = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( intval( $product_id ) );

			// Continue the loop, if the instance is not a WooCommerce product.
			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			// Push the product ID.
			$ids[] = intval( $product_id );

			if ( is_callable( array( $product, 'is_type' ) ) && $product->is_type( 'variable' ) ) {
				// Push respective variation IDs if the product type is variable.
				$ids = array_merge(
					$ids,
					is_callable( array( $product, 'get_children' ) ) ? array_map( 'intval', $product->get_children() ) : array()
				);
			}
		}

		return array_unique( $ids );
	}
}

if ( ! function_exists( 'afwc_current_user_can_manage_affiliate' ) ) {
	/**
	 * Function to check whether current user can manage affiliate.
	 *
	 * @return bool Returns true if current user can manage affiliate otherwise false.
	 */
	function afwc_current_user_can_manage_affiliate() {
		return current_user_can( 'manage_woocommerce' ); // phpcs:ignore WordPress.WP.Capabilities.Unknown
	}
}

if ( ! function_exists( 'afwc_get_affiliates_by_user_roles' ) ) {
	/**
	 * Function to get the affiliate IDs by affiliate user roles.
	 *
	 * @return array Return the array of affiliate IDs otherwise empty array if there is no user roles for affiliates.
	 */
	function afwc_get_affiliates_by_user_roles() {

		$affiliate_roles = get_option( 'affiliate_users_roles', array() );

		if ( empty( $affiliate_roles ) ) {
			return array();
		}

		$user_ids = get_users(
			array(
				'role__in' => $affiliate_roles,
				'fields'   => 'ID',
			)
		);

		return ! empty( $user_ids ) && is_array( $user_ids ) ? array_map( 'intval', $user_ids ) : array();
	}
}

if ( ! function_exists( 'afwc_process_url' ) ) {
	/**
	 * Function to process and return a valid URL.
	 *
	 * @link https://github.com/woocommerce/woocommerce/blob/6.5.0/plugins/woocommerce/src/Internal/Utilities/URL.php#L329
	 *
	 * @param string $url               The URL to process.
	 * @param array  $component_overrides An array of URL components to override the parsed ones.
	 *
	 * @return string Return the processed URL or an empty string if the URL is empty.
	 */
	function afwc_process_url( $url = '', $component_overrides = array() ) {

		if ( empty( $url ) ) {
			return '';
		}

		// Parse the URL components.
		$parsed_url = wp_parse_url( $url );

		// Return if parsing failed.
		if ( empty( $parsed_url ) ) {
			return $url;
		}

		// Merge parsed components with overrides.
		$components = wp_parse_args( $component_overrides, $parsed_url );

		// Extract components.
		$scheme = ! empty( $components['scheme'] ) ? $components['scheme'] . '://' : '//';
		$host   = ! empty( $components['host'] ) ? $components['host'] : '';
		$port   = ! empty( $components['port'] ) ? ':' . $components['port'] : '';
		$path   = ! empty( $components['path'] ) ? $components['path'] : '';

		// Special handling for hostless URLs (typically, filepaths) referencing the current working directory.
		if ( '' === $host && ( '' === $path || '.' === $path ) ) {
			$path = './';
		}

		$user      = ! empty( $components['user'] ) ? $components['user'] : '';
		$pass      = ! empty( $components['pass'] ) ? ':' . $components['pass'] : '';
		$user_pass = ( ! empty( $user ) || ! empty( $pass ) ) ? $user . $pass . '@' : '';

		$query    = ! empty( $components['query'] ) ? '?' . $components['query'] : '';
		$fragment = ! empty( $components['fragment'] ) ? '#' . $components['fragment'] : '';

		// Return the processed URL.
		return $scheme . $user_pass . $host . $port . $path . $query . $fragment;
	}
}

/**
 * Get the current date.
 *
 * @param bool   $is_gmt Whether to get the GMT date or not.
 * @param string $format The format for the date.
 *
 * @return string Return current date.
 */
function afwc_get_current_date( $is_gmt = false, $format = 'Y-m-d' ) {
	$current_site_time = current_time( 'Y-m-d H:i:s' );
	return $is_gmt ? get_gmt_from_date( $current_site_time, $format ) : gmdate( $format, strtotime( $current_site_time ) );
}

if ( ! function_exists( 'afwc_get_smart_date_filters' ) ) {
	/**
	 * Function to get available smart date filters for reporting.
	 *
	 * @return array Return the grouped date filters.
	 */
	function afwc_get_smart_date_filters() {
		return array(
			array(
				'today'     => esc_html_x( 'Today', 'smart dates option for today', 'affiliate-for-woocommerce' ),
				'yesterday' => esc_html_x( 'Yesterday', 'smart dates option for yesterday', 'affiliate-for-woocommerce' ),
			),
			array(
				'this_week'    => esc_html_x( 'This Week', 'smart dates option for this week', 'affiliate-for-woocommerce' ),
				'last_week'    => esc_html_x( 'Last Week', 'smart dates option for last week', 'affiliate-for-woocommerce' ),
				'last_4_weeks' => esc_html_x( 'Last 4 Weeks', 'smart dates option for last 4 weeks', 'affiliate-for-woocommerce' ),
			),
			array(
				'this_month' => esc_html_x( 'This Month', 'smart dates option for this month', 'affiliate-for-woocommerce' ),
				'last_month' => esc_html_x( 'Last Month', 'smart dates option for last month', 'affiliate-for-woocommerce' ),
				'3_months'   => esc_html_x( '3 Months', 'smart dates option for 3 months', 'affiliate-for-woocommerce' ),
				'6_months'   => esc_html_x( '6 Months', 'smart dates option for 6 months', 'affiliate-for-woocommerce' ),
			),
			array(
				'this_year' => esc_html_x( 'This Year', 'smart dates option for this year', 'affiliate-for-woocommerce' ),
				'last_year' => esc_html_x( 'Last Year', 'smart dates option for last year', 'affiliate-for-woocommerce' ),
			),
		);
	}
}

if ( ! function_exists( 'afwc_is_valid_date_range' ) ) {
	/**
	 * Function to verify whether the date range are valid.
	 *
	 * @param array $date_range Array of to date and from date.
	 *
	 * @return bool Return true if valid otherwise false.
	 */
	function afwc_is_valid_date_range( $date_range = array() ) {
		if ( ! empty( $date_range['from'] ) && ! empty( $date_range['to'] ) && ( strtotime( $date_range['from'] ) > strtotime( $date_range['to'] ) ) ) {
			return false;
		}
		return true;
	}
}

if ( ! function_exists( 'afwc_get_campaign_title' ) ) {
	/**
	 * Function to get campaign title based on campaign ID.
	 *
	 * @param int $id The campaign ID.
	 *
	 * @return string Return the campaign title if campaign is exists otherwise empty.
	 */
	function afwc_get_campaign_title( $id = 0 ) {
		if ( empty( $id ) ) {
			return '';
		}

		global $wpdb;

		try {
			$campaign_title = $wpdb->get_var( // phpcs:ignore
				$wpdb->prepare(
					"SELECT title
					   FROM {$wpdb->prefix}afwc_campaigns
					   WHERE id = %d",
					intval( $id )
				)
			);
		} catch ( Exception $e ) {
			Affiliate_For_WooCommerce::log_error( __FUNCTION__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			$campaign_title = '';
		}

		return ! empty( $campaign_title ) ? $campaign_title : '';
	}
}

if ( ! function_exists( 'afwc_get_click_to_copy_html' ) ) {
	/**
	 * Function to get the click-to-copy HTML element.
	 *
	 * @param string $content The text to copy.
	 * @param array  $attrs The attributes for the click-to-copy element.
	 *
	 * @return string The generated HTML for the click-to-copy element.
	 */
	function afwc_get_click_to_copy_html( $content = '', $attrs = array() ) {
		if ( empty( $content ) ) {
			return '';
		}

		$id    = ! empty( $attrs['id'] ) ? $attrs['id'] : '';
		$class = 'afwc-click-to-copy' . ( ! empty( $attrs['class'] ) ? ( ' ' . $attrs['class'] ) : '' );
		$title = ! empty( $attrs['title'] ) ? $attrs['title'] : _x( 'Click to copy', 'click to copy title', 'affiliate-for-woocommerce' );

		wp_enqueue_script( 'afwc-click-to-copy' );

		ob_start();
		?>
		<span title="<?php echo esc_attr( $title ); ?>">
			<code id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $class ); ?>" data-ctp="<?php echo esc_attr( $content ); ?>">
				<?php echo wp_kses_post( $content ); ?>
			</code>
		</span>
		<?php
		return ob_get_clean();
	}
}

/**
 * Generate a semi-random string
 *
 * @param int   $length Length of the random string.
 * @param array $params Array of additional params.
 *
 * @return string
 */
function afwc_generate_random_string( $length = 24, $params = array() ) {
	if ( isset( $params['metadata']['order_id'] ) ) {
		$random_string = md5( $params['metadata']['order_id'] );
	} else {
		$characters        = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTU';
		$characters_length = strlen( $characters );
		$random_string     = '';
		for ( $i = 0; $i < $length; $i++ ) {
			$random_string .= $characters[ wp_rand( 0, $characters_length - 1 ) ];
		}
	}

	return $random_string;
}

if ( ! function_exists( 'afwc_get_affiliate_status_with_meta_value' ) ) {
	/**
	 * Function to get all affiliate statuses.
	 *
	 * @return array 'status_name' => 'meta_value'.
	 */
	function afwc_get_affiliate_status_with_meta_value() {
		return array(
			'active'   => 'yes',
			'pending'  => 'pending',
			'rejected' => 'no',
		);
	}
}

if ( ! function_exists( 'afwc_get_affiliate_status_label_by_meta_value' ) ) {
	/**
	 * Function to get affiliate's status label.
	 *
	 * @return string Status label for the given meta value.
	 * @param string $status The affiliate status label.
	 */
	function afwc_get_affiliate_status_label_by_meta_value( $status = '' ) {
		switch ( $status ) {
			case 'yes':
				return esc_html_x( 'Active', 'Title for active affiliate status', 'affiliate-for-woocommerce' );
			case 'pending':
				return esc_html_x( 'Pending', 'Title for pending affiliate status', 'affiliate-for-woocommerce' );
			case 'no':
				return esc_html_x( 'Rejected', 'Title for rejected affiliate status', 'affiliate-for-woocommerce' );
			default:
				// Affiliate status will be considered as Not Registered when the meta value is empty or not matching with any of the existing status meta values.
				return esc_html_x( 'Not Registered', 'Title for not registered affiliate status', 'affiliate-for-woocommerce' );
		}
	}
}

if ( ! function_exists( 'afwc_get_affiliate_id_by_identifier' ) ) {
	/**
	 * Retrieve affiliate ID by its identifier.
	 *
	 * @param string|int $identifier The affiliate identifier.
	 *
	 * @throws Exception If any error during query execution.
	 * @return int Affiliate ID if found, otherwise zero.
	 */
	function afwc_get_affiliate_id_by_identifier( $identifier = '' ) {
		if ( empty( $identifier ) ) {
				return 0;
		}

		$affiliate_id = afwc_get_affiliate_id_by_assigned_identifier( $identifier );

		if ( ! empty( $affiliate_id ) && is_numeric( $affiliate_id ) ) {
			return intval( $affiliate_id );
		}

		return is_numeric( $identifier ) ? intval( $identifier ) : 0;
	}
}

if ( ! function_exists( 'afwc_get_affiliate_id_by_assigned_identifier' ) ) {
	/**
	 * Retrieve affiliate ID where the given identifier is assigned.
	 *
	 * @param string|int $identifier The affiliate identifier.
	 *
	 * @throws Exception If any error during query execution.
	 * @return int Affiliate ID if found, otherwise zero.
	 */
	function afwc_get_affiliate_id_by_assigned_identifier( $identifier = '' ) {
		if ( empty( $identifier ) ) {
				return 0;
		}

		global $wpdb;

		$results = array();

		try {
			$results = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT 
						u.ID 		AS user_id,
						um.meta_key AS meta_key
					FROM {$wpdb->users} AS u
					INNER JOIN {$wpdb->usermeta} AS um
						ON u.ID = um.user_id
					WHERE um.meta_key IN ('afwc_ref_url_id', 'afwc_default_identifier')
					  AND um.meta_value = %s
					LIMIT 2",
					$identifier
				)
			);
			if ( ! empty( $wpdb->last_error ) ) {
				throw new Exception( $wpdb->last_error );
			}
		} catch ( Exception $e ) {
			Affiliate_For_WooCommerce::log_error( __FUNCTION__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
		}

		if ( ! empty( $results ) && is_array( $results ) ) {
			foreach ( $results as $result ) {
				if ( ! empty( $result->meta_key ) && 'afwc_ref_url_id' === $result->meta_key ) {
					return intval( $result->user_id );
				}
			}

			if ( ! empty( $results[0]->user_id ) ) {
				return intval( $results[0]->user_id );
			}
		}

		return 0;
	}
}

if ( ! function_exists( 'afwc_generate_default_identifier' ) ) {
	/**
	 * Method to generate and assign default identifier to affiliate.
	 *
	 * @param int    $affiliate_id The affiliate ID.
	 * @param string $user_login The username of the affiliate.
	 *
	 * @throws Exception If any error during the process.
	 * @return bool True if assigned otherwise false.
	 */
	function afwc_generate_default_identifier( $affiliate_id = 0, $user_login = '' ) {
		global $wpdb;

		if ( empty( $user_login ) ) {
			try {
				$user_login = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"SELECT user_login FROM {$wpdb->users} WHERE ID = %d",
						$affiliate_id
					)
				);
			} catch ( Exception $e ) {
				$user_login = '';
				Affiliate_For_WooCommerce::log_error( __FUNCTION__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			}
		}

		return ! empty( $user_login ) && update_user_meta( $affiliate_id, 'afwc_default_identifier', $user_login . '-' . $affiliate_id );
	}
}

if ( ! function_exists( 'afwc_get_allowed_html_with_svg' ) ) {
	/**
	 * Returns the allowed HTML tags including SVG elements.
	 *
	 * @return array Allowed HTML tags and attributes.
	 */
	function afwc_get_allowed_html_with_svg() {
		$allowed_html  = wp_kses_allowed_html( 'post' );
		$allowed_html += array(
			'svg'   => array(
				'xmlns'        => true,
				'fill'         => true,
				'viewbox'      => true,
				'stroke-width' => true,
				'stroke'       => true,
				'class'        => true,
			),
			'path'  => array(
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'd'               => true,
			),
			// Accessible name for the icon it sits in.
			'title' => array(),
		);
		return $allowed_html;
	}
}

if ( ! function_exists( 'afwc_get_affiliate_from_cookie' ) ) {
	/**
	 * Get the affiliate ID from cookie.
	 *
	 * @return int Return affiliate ID if found, zero otherwise.
	 */
	function afwc_get_affiliate_from_cookie() {
		return ! empty( $_COOKIE[ AFWC_AFFILIATES_COOKIE_NAME ] ) // phpcs:ignore
			? intval( wc_clean( wp_unslash( $_COOKIE[ AFWC_AFFILIATES_COOKIE_NAME ] ) ) ) // phpcs:ignore
			: 0;
	}
}

if ( ! function_exists( 'afwc_get_current_hit_details' ) ) {
	/**
	 * Get the current hit details from cookie.
	 *
	 * @return array Return hit details if found, empty array otherwise.
	 */
	function afwc_get_current_hit_details() {

		$hit_id = afwc_get_hit_id();

		if ( empty( $hit_id ) ) {
			return array();
		}

		try {
			global $wpdb;

			$hit_details = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}afwc_hits WHERE id = %d",
					intval( $hit_id )
				),
				ARRAY_A
			);

			return ( ! empty( $hit_details ) && is_array( $hit_details ) ) ? $hit_details : array();
		} catch ( Throwable $e ) {
			Affiliate_For_WooCommerce::log_error( __FUNCTION__, ( is_callable( array( $e, 'getMessage' ) ) ) ? $e->getMessage() : '' );
			return array();
		}
	}
}

if ( ! function_exists( 'afwc_get_current_url_without_tracking_parameter' ) ) {
	/**
	 * Get the current URL without the affiliate tracking parameter.
	 *
	 * @return string The current URL without the affiliate tracking parameter.
	 */
	function afwc_get_current_url_without_tracking_parameter() {
		$current_url = afwc_get_current_url();
		$pname       = afwc_get_pname();

		if ( empty( $current_url ) || empty( $pname ) ) {
			return $current_url;
		}

		// Remove the affiliate tracking parameter from the URL.
		$cleaned_url = remove_query_arg( $pname, $current_url );

		// Also remove pretty referral links if present.
		$parsed_url = wp_parse_url( $cleaned_url );

		if ( ! empty( $parsed_url['path'] ) ) {
			$path    = $parsed_url['path'];
			$pattern = '/\/' . preg_quote( $pname, '/' ) . '\/[^\/?#]+/';

			if ( preg_match( $pattern, $path ) ) {
				$cleaned_path = preg_replace( $pattern, '', $path );
				$cleaned_url  = afwc_process_url( $cleaned_url, array( 'path' => $cleaned_path ) );
			}
		}

		return $cleaned_url;
	}
}

if ( ! function_exists( 'afwc_get_self_made_coupon_create_limit' ) ) {
	/**
	 * Get the maximum allowed self-created coupons by an affiliate.
	 *
	 * @param int $user_id The affiliate user ID.
	 *
	 * @return int The maximum allowed self-created coupons.
	 */
	function afwc_get_self_made_coupon_create_limit( $user_id = 0 ) {
		if ( empty( $user_id ) ) {
			return 1;
		}
		/**
		 * Filter maximum allowed coupon for an affiliate.
		 *
		 * @since 8.56.0
		 *
		 * @param int    max allowed coupon count.
		 * @param array  $user_id  The affiliate user ID.
		 */
		return intval( apply_filters( 'afwc_maximum_self_created_coupons', 1, $user_id ) );
	}
}

if ( ! function_exists( 'afwc_get_base_currency' ) ) {
	/**
	 * Get the store's base currency.
	 *
	 * @return string Base currency code.
	 */
	function afwc_get_base_currency() {
		return get_option( 'woocommerce_currency', 'USD' );
	}
}

if ( ! function_exists( 'afwc_convert_to_base_currency' ) ) {
	/**
	 * Convert amount from provided currency to base currency.
	 *
	 * @param float  $amount         Amount to convert.
	 * @param string $from_currency  Source currency code to convert from.
	 * @return float Converted amount.
	 */
	function afwc_convert_to_base_currency( $amount = 0, $from_currency = '' ) {

		if ( empty( $amount ) || empty( $from_currency ) ) {
			return floatval( $amount );
		}

		if ( ! afwc_can_convert_currency() ) {
			return floatval( $amount );
		}

		/**
		 * Get the store's base currency directly from WooCommerce settings to avoid any issues with currency switching plugins.
		 * As Aelia Currency Switcher plugin override the woocommerce_get_currency filter and return the customer's currency instead of the base currency, which cause issue in conversion.
		 */
		$to_currency = afwc_get_base_currency();

		// No conversion needed if same currency.
		if ( $to_currency === $from_currency ) {
			return floatval( $amount );
		}

		/**
		 * Filter to convert amount from one currency to another
		 * Multi-currency compatibility classes hook into this filter
		 *
		 * @since 8.61.0
		 *
		 * @param float  $amount         Amount to convert.
		 * @param string $from_currency  Source currency code.
		 * @param string $to_currency    Target currency code.
		 * @return float Converted amount.
		 */
		$converted_amount = apply_filters( 'afwc_convert_to_base_currency', $amount, $from_currency, $to_currency );

		return floatval( $converted_amount );
	}
}

if ( ! function_exists( 'afwc_can_convert_currency' ) ) {
	/**
	 * Function to check if currency conversion is possible.
	 *
	 * @return bool True if conversion is possible, false otherwise.
	 */
	function afwc_can_convert_currency() {
		return has_filter( 'afwc_convert_to_base_currency' );
	}
}

if ( ! function_exists( 'afwc_can_generate_coupon_url' ) ) {
	/**
	 * Function to check whether coupon URL can be generated or not.
	 *
	 * @return bool True if any 3rd party developer extends `afwc_coupon_url` filter to generate the coupon URL, otherwise false.
	 */
	function afwc_can_generate_coupon_url() {
		return has_filter( 'afwc_coupon_url' );
	}
}

if ( ! function_exists( 'afwc_get_settings_sections' ) ) {
	/**
	 * Method to get setting sections.
	 *
	 * @param string $section Optional. Section key to get specific title.
	 *
	 * @return array|string Array of all sections if no section provided, or specific section title.
	 */
	function afwc_get_settings_sections( $section = '' ) {
		$sections = array(
			''                             => _x( 'General', 'general setting section title', 'affiliate-for-woocommerce' ),
			'registration_form'            => _x( 'Registration Form', 'registration form setting section title', 'affiliate-for-woocommerce' ),
			'referrals'                    => _x( 'Referrals', 'referrals setting section title', 'affiliate-for-woocommerce' ),
			'commissions'                  => _x( 'Commissions', 'commissions setting section title', 'affiliate-for-woocommerce' ),
			'payouts'                      => _x( 'Payouts', 'payouts setting section title', 'affiliate-for-woocommerce' ),
			'affiliate_account'            => _x( "Affiliate's Account", 'affiliate account setting section title', 'affiliate-for-woocommerce' ),
			'email_marketing_integrations' => _x( 'Email Marketing Integrations', 'email marketing integrations setting section title', 'affiliate-for-woocommerce' ),
		);

		// Return whole array.
		if ( empty( $section ) ) {
			return $sections;
		}

		// Determine key for array lookup.
		$key = ( 'general' === $section ) ? '' : $section;

		// Return section title or empty string if not found.
		return $sections[ $key ] ?? '';
	}
}

if ( ! function_exists( 'afwc_get_order_refund_period_in_days' ) ) {
	/**
	 * Get the order refund period.
	 *
	 * @return int Refund period in days.
	 */
	function afwc_get_order_refund_period_in_days() {
		$default = 30;
		$value   = get_option( 'afwc_order_refund_period_in_days', $default );

		if ( '' === $value || null === $value || false === $value ) {
			$value = $default;
		}

		return absint( $value );
	}
}
