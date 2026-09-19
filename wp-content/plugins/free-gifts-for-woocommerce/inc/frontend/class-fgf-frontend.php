<?php

/**
 *  Handles the frontend.
 * */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}
if ( ! class_exists( 'FGF_Frontend' ) ) {

	/**
	 * Class.
	 * */
	class FGF_Frontend {

		/**
		 * Class Initialization.
		 * */
		public static function init() {

			// Return if free gifts product is not hide in frontend.
			if ( 'yes' != get_option( 'fgf_settings_restrict_gift_product_display' ) ) {
				return ;
			}

			// Alter WooCommerce Product query.
			add_action( 'woocommerce_product_query' , array( __CLASS__, 'pre_get_posts' ) , 999 , 1 ) ;

			// Alter product is visible.
			add_action( 'woocommerce_product_is_visible' , array( __CLASS__, 'alter_product_is_visible' ) , 999 , 2 ) ;

			// Alter Shortcode product query.
			add_filter( 'woocommerce_shortcode_products_query' , array( __CLASS__, 'alter_shortcode_products_query' ) , 999 , 3 ) ;
		}

		/**
		 * Alter shortcode product query.
		 * 
		 * @return array
		 * */
		public static function alter_shortcode_products_query( $args, $attributes, $type ) {

			// Return if the admin page. 
			if ( is_admin() ) {
				return $args ;
			}

			// Return if free gifts does not exists.
			$free_products = fgf_get_rule_valid_gift_products() ;
			if ( ! fgf_check_is_array( $free_products ) ) {
				return $args ;
			}

			$post__not_in = $free_products ;
			if ( isset( $args[ 'post__not_in' ] ) && fgf_check_is_array( $args[ 'post__not_in' ] ) ) {
				$post__not_in = array_merge( $args[ 'post__not_in' ] , $post__not_in ) ;
			}

			$args[ 'post__not_in' ] = $post__not_in ;

			return $args ;
		}

		/**
		 * Alter Product is visible.
		 * 
		 * @return bool
		 * */
		public static function alter_product_is_visible( $visible, $product_id ) {
			// Return if the admin page. 
			if ( is_admin() ) {
				return $visible ;
			}

			// Return if free gifts does not exists.
			$free_products = fgf_get_rule_valid_gift_products() ;
			if ( ! fgf_check_is_array( $free_products ) ) {
				return $visible ;
			}

			if ( in_array( $product_id , $free_products ) ) {
				return false ;
			}

			return $visible ;
		}

		/**
		 * Alter WooCommerce Product query.
		 * 
		 * @return mixed
		 * */
		public static function pre_get_posts( $query ) {

			// Return if a query is not the main query. 
			if ( ! $query->is_main_query() ) {
				return ;
			}

			// Return if the admin page. 
			if ( is_admin() ) {
				return ;
			}

			// Return if the post type is product post type.
			if ( ! isset( $query->query_vars[ 'post_type' ] ) || 'product' != $query->query_vars[ 'post_type' ] ) {
				return ;
			}

			// Return if free gifts does not exists.
			$free_products = fgf_get_rule_valid_gift_products() ;
			if ( ! fgf_check_is_array( $free_products ) ) {
				return ;
			}

			$post_not_in = $query->get( 'post__not_in' ) ;
			$post_not_in = ( fgf_check_is_array( $post_not_in ) ) ? array_merge( $post_not_in , $free_products ) : $free_products ;

			// Set post is not in gift product ids.
			$query->set( 'post__not_in' , $post_not_in ) ;
		}
	}

	FGF_Frontend::init() ;
}
