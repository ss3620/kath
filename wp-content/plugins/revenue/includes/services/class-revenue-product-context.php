<?php

namespace Revenue\Services;


class Revenue_Product_Context {
	/**
	 * WC_Product|null|false
	 */
	protected static $product_context = null;

	/**
	 * Set the product context.
	 *
	 * @param int $product_id The WooCommerce product ID.
	 */
	public static function set_product_context( $product_id ) {
		self::$product_context = wc_get_product( $product_id );
	}

	/**
	 * Get the product context object.
	 *
	 * @return \WC_Product|null|false 	The WooCommerce product object, 
	 * 									or null if not set 
	 * 									or false if no product found with previously given id.
	 */
	public static function get_product_context() {
		return self::$product_context;
	}

	/**
	 * Get the product context ID.
	 *
	 * @return int|null The ID of the WooCommerce product, or null if not set.
	 */
	public static function get_product_context_id() {
		if ( self::$product_context instanceof \WC_Product ) {
			return self::$product_context->get_id();
		}
		return null;
	}

	/**
	 * Clear the product context.
	 */
	public static function clear_product_context() {
		self::$product_context = null;
	}
		
}