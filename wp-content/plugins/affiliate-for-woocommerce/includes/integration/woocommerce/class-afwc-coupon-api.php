<?php
/**
 * Main class for WooCommerce coupons to process payouts
 *
 * @package   affiliate-for-woocommerce/includes/integration/woocommerce/
 * @since     8.21.0
 * @version   1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AFWC_Coupon_API' ) ) {

	/**
	 * Fixed Cart Coupon API
	 */
	class AFWC_Coupon_API {

		/**
		 * Enabled
		 *
		 * @var bool $enabled
		 */
		public $enabled = false;

		/**
		 * Variable to hold instance of this class
		 *
		 * @var $instance
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return AFWC_Coupon_API Singleton object of this class
		 */
		public static function get_instance() {

			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		private function __construct() {
		}

		/**
		 * Function to handle WC compatibility related function call from appropriate class
		 *
		 * @param string $function_name Function to call.
		 * @param array  $arguments     Array of arguments passed while calling $function_name.
		 * @return mixed Result of function call.
		 */
		public function __call( $function_name = '', $arguments = array() ) {
			if ( ! is_callable( 'SA_WC_AFW_Compatibility', $function_name ) ) {
				return;
			}

			if ( ! empty( $arguments ) ) {
				return call_user_func_array( 'SA_WC_AFW_Compatibility::' . $function_name, $arguments );
			} else {
				return call_user_func( 'SA_WC_AFW_Compatibility::' . $function_name );
			}
		}

		/**
		 * Method to check whether the Fixed Cart Coupon is enabled for payout.
		 * It detects based on whether the plugin is active and setting is selected.
		 *
		 * @return bool Return true if enabled otherwise false.
		 */
		public function is_enabled() {
			if ( 'yes' !== get_option( 'woocommerce_enable_coupons' ) ) {
				return false;
			}

			// check in settings if coupon type is selected.
			$allowed_coupon_types_for_payout = get_option( 'afwc_enabled_for_coupon_payout', array() );
			if ( empty( $allowed_coupon_types_for_payout ) || ! is_array( $allowed_coupon_types_for_payout ) ) {
				return false;
			}

			return in_array( 'fixed_cart', $allowed_coupon_types_for_payout, true );
		}

		/**
		 * Process Fixed Cart payout via WooCommerce coupon - create a coupon.
		 *
		 * @param array  $payout_records Array of payout records.
		 * @param string $currency       Currency for payout.
		 *
		 * @return array|WP_Error|null  $response
		 */
		public function process_coupon_payout( $payout_records = array(), $currency = '' ) {
			// Check if setting is enabled/allowed.
			if ( false === $this->is_enabled() ) {
				Affiliate_For_WooCommerce::log(
					'error',
					_x( 'Payout method: Fixed Cart Coupon is disabled.', 'Logger for Fixed Cart Coupon payout', 'affiliate-for-woocommerce' )
				);
				return;
			}

			if ( empty( $currency ) ) {
				Affiliate_For_WooCommerce::log(
					'error',
					_x( 'Currency missing for Fixed Cart Coupon payout.', 'Logger for Fixed Cart Coupon payout', 'affiliate-for-woocommerce' )
				);
				return;
			}

			if ( empty( $payout_records ) || ! is_array( $payout_records ) ) {
				Affiliate_For_WooCommerce::log(
					'error',
					_x( 'Payout records are not available for Fixed Cart Coupon payout.', 'Logger for Fixed Cart Coupon payout', 'affiliate-for-woocommerce' )
				);
				return;
			}

			// check if we find affiliate.
			if ( empty( $payout_records['id'] ) ) {
				Affiliate_For_WooCommerce::log(
					'error',
					_x( 'Missing affiliate ID for Fixed Cart Coupon payout.', 'Logger for Fixed Cart Coupon payout', 'affiliate-for-woocommerce' )
				);
				return;
			}

			$user = get_user_by( 'id', intval( $payout_records['id'] ) );
			if ( ! ( $user instanceof WP_User ) || empty( $user->user_email ) ) {
				Affiliate_For_WooCommerce::log(
					'error',
					_x( 'Invalid affiliate id.', 'Logger for Fixed Cart Coupon payout', 'affiliate-for-woocommerce' )
				);
				return;
			}

			$args        = array(
				'return'               => 'code',
				'discount_type'        => 'fixed_cart',
				'amount'               => ( ( ! empty( $payout_records['amount'] ) ) ? floatval( $payout_records['amount'] ) : 0.00 ),
				'description'          => _x( 'Coupon created as affiliate commission payout', 'coupon description', 'affiliate-for-woocommerce' ),
				'filter'               => 'afwc_coupon_payouts_custom_meta',
				'email_restrictions'   => array( sanitize_email( $user->user_email ) ),
				'usage_limit'          => 1,
				'usage_limit_per_user' => 1,
			);
			$coupon_code = $this->generate_coupon_code( $args );

			$result = array();
			if ( empty( $coupon_code ) ) {
				$result = array(
					'ACK' => 'Failed',
					'msg' => _x( 'Unable to create a coupon', 'coupon creation failed', 'affiliate-for-woocommerce' ),
				);
			} else {
				$result = array(
					'ACK'         => 'Success',
					'coupon_code' => $coupon_code,
				);

				// Calculate total amount if 'amount' is missing in the result.
				// amount addition will be here in amount received.
				if ( empty( $args['amount'] ) ) {
					$amounts          = ! empty( $payout_records ) && is_array( $payout_records ) ? array_column( $payout_records, 'amount' ) : array();
					$result['amount'] = ! empty( $amounts ) && is_array( $amounts ) ? floatval( array_sum( $amounts ) ) : 0.00;
				} else {
					$result['amount'] = $args['amount'];
				}
			}

			/**
			 * Fires immediately after Fixed Cart Coupon commission payout.
			 *
			 * @param array $result         The results
			 * @param array $payout_records  The Payout records.
			 * @param array
			 *
			 * @since 8.21.0
			 */
			do_action( 'afwc_after_coupon_commission_payout', $result, $payout_records, array( 'source' => $this ) );

			return $result;
		}

		/**
		 * Method to generate a coupon programmatically.
		 *
		 * @param array $args Arguments to configure coupon creation.
		 *
		 * @return string|int Coupon Code (string) or Coupon ID (int) on success. Empty string on failure.
		 */
		public function generate_coupon_code( $args = array() ) {
			if ( empty( $args ) || ! is_array( $args ) ) {
				return '';
			}

			$discount_type = ! empty( $args['discount_type'] ) ? $args['discount_type'] : '';
			if ( empty( $discount_type ) ) {
				return '';
			}

			$coupon_amount = ! empty( $args['amount'] ) ? floatval( $args['amount'] ) : 0;
			if ( $coupon_amount < 0 ) {
				return '';
			}

			$coupon_code = ! empty( $args['code'] )
				? sanitize_text_field( $args['code'] )
				: uniqid();

			if ( wc_get_coupon_id_by_code( $coupon_code ) ) {
				return '';
			}

			$coupon = new WC_Coupon();

			$args['amount']        = $coupon_amount;
			$args['code']          = $coupon_code;
			$args['discount_type'] = $discount_type;

			foreach ( $args as $key => $value ) {
				if ( in_array( $key, array( 'return', 'filter' ), true ) ) {
					continue;
				}
				$data[ $key ] = wc_clean( $value );
			}

			if ( ! empty( $args['filter'] ) ) {
				$filter = sanitize_text_field( $args['filter'] );

				/**
				 * Filter to modify coupon data before saving.
				 *
				 * @param array $data   Coupon data.
				 * @param array $args   Arguments passed for coupon creation.
				 *
				 * @since 8.21.0
				 */
				$data = apply_filters( $filter, $data, $args );
			}

			foreach ( $data as $key => $value ) {
				if ( in_array( $key, array( 'return', 'filter' ), true ) ) {
					continue;
				}
				$value = wc_clean( $value );
				if ( is_callable( array( $coupon, "set_{$key}" ) ) ) {
					$coupon->{"set_{$key}"}( $value );
				} else {
					$coupon->update_meta_data( $key, $value );
				}
			}

			$coupon_id = $coupon->save();
			if ( empty( $coupon_id ) ) {
				return '';
			}

			$return_type = ! empty( $args['return'] ) ? $args['return'] : 'code';

			// Based on return type, return either coupon code or ID.
			return ( ( 'code' === $return_type ) ? ( is_callable( array( $coupon, 'get_code' ) ) ? $coupon->get_code() : get_the_title( $coupon_id ) ) : $coupon_id );
		}
	}
}

AFWC_Coupon_API::get_instance();
