<?php

/**
 * Coupon.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly
}

if ( ! class_exists( 'FGF_Coupon' ) ) {

	/**
	 * Class
	 */
	class FGF_Coupon {

		/**
		 * Class Initialization.
		 */
		public static function init() {
			// Add the custom free gift coupon type.
			add_filter( 'woocommerce_coupon_discount_types' , array( __CLASS__, 'coupon_discount_types' ) ) ;
		}

		/**
		 * Add the custom free gift coupon type
		 */
		public static function coupon_discount_types( $types ) {
			$types[ 'fgf_free_gift' ] = __( 'Free Gift' , 'free-gifts-for-woocommerce' ) ;

			return $types ;
		}
	}

	FGF_Coupon::init() ;
}
