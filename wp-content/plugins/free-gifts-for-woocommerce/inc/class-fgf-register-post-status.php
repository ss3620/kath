<?php

/**
 * Register Custom Post Status.
 *
 * @package
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit ; // Exit if accessed directly.
}

if ( ! class_exists( 'FGF_Register_Post_Status' ) ) {

	/**
	 * Class.
	 */
	class FGF_Register_Post_Status {

		/**
		 * Class initialization.
		 */
		public static function init() {
			add_action( 'init', array( __CLASS__, 'register_custom_post_status' ) ) ;
		}

		/**
		 * Register Custom Post Status.
		 */
		public static function register_custom_post_status() {
			$custom_post_statuses = array(
				'fgf_active'    => array( 'FGF_Register_Post_Status', 'active_post_status_args' ),
				'fgf_inactive'  => array( 'FGF_Register_Post_Status', 'inactive_post_status_args' ),
				'fgf_manual'    => array( 'FGF_Register_Post_Status', 'manual_post_status_args' ),
				'fgf_automatic' => array( 'FGF_Register_Post_Status', 'automatic_post_status_args' ),
					) ;
			/**
			 * This hook is used to alter the custom post statuses.
			 * 
			 * @since 1.0
			 */
			$custom_post_statuses = apply_filters( 'fgf_add_custom_post_status', $custom_post_statuses ) ;

			// return if no post status to register.
			if ( ! fgf_check_is_array( $custom_post_statuses ) ) {
				return ;
			}

			foreach ( $custom_post_statuses as $post_status => $args_function ) {

				$args = array() ;
				if ( $args_function ) {
					$args = call_user_func_array( $args_function, array() ) ;
				}

				// Register post status.
				register_post_status( $post_status, $args ) ;
			}
		}

		/**
		 * Active Custom Post Status arguments.
		 * 
		 * @return array
		 */
		public static function active_post_status_args() {
			/**
			 * This hook is used to alter the active post status arguments.
			 * 
			 * @since 1.0
			 */
			$args = apply_filters(
					'fgf_active_post_status_args',
					array(
						'label'                     => esc_html_x( 'Active', 'free-gifts-for-woocommerce' ),
						'public'                    => true,
						'exclude_from_search'       => false,
						'show_in_admin_all_list'    => true,
						'show_in_admin_status_list' => true,
						/* translators: %s: count */
						'label_count'               => _n_noop( 'Active <span class="count">(%s)</span>', 'Active <span class="count">(%s)</span>', 'free-gifts-for-woocommerce' ),
					)
					) ;

			return $args ;
		}

		/**
		 * Inactive Custom Post Status arguments.
		 * 
		 * @return array
		 */
		public static function inactive_post_status_args() {
			/**
			 * This hook is used to alter the In-Active post status arguments.
			 * 
			 * @since 1.0
			 */
			$args = apply_filters(
					'fgf_inactive_post_status_args',
					array(
						'label'                     => esc_html_x( 'Inactive', 'free-gifts-for-woocommerce' ),
						'public'                    => true,
						'exclude_from_search'       => false,
						'show_in_admin_all_list'    => true,
						'show_in_admin_status_list' => true,
						/* translators: %s: count */
						'label_count'               => _n_noop( 'Inactive <span class="count">(%s)</span>', 'Inactive <span class="count">(%s)</span>', 'free-gifts-for-woocommerce' ),
					)
					) ;

			return $args ;
		}

		/**
		 * Manual Custom Post Status arguments.
		 * 
		 * @return array
		 */
		public static function manual_post_status_args() {
			/**
			 * This hook is used to alter the manual post status arguments.
			 * 
			 * @since 1.0
			 */
			$args = apply_filters(
					'fgf_manual_post_status_args',
					array(
						'label'                     => esc_html_x( 'Manual', 'free-gifts-for-woocommerce' ),
						'public'                    => true,
						'exclude_from_search'       => false,
						'show_in_admin_all_list'    => true,
						'show_in_admin_status_list' => true,
						/* translators: %s: count */
						'label_count'               => _n_noop( 'Manual <span class="count">(%s)</span>', 'Manual <span class="count">(%s)</span>', 'free-gifts-for-woocommerce' ),
					)
					) ;

			return $args ;
		}

		/**
		 * Automatic Custom Post Status arguments.
		 * 
		 * @return array
		 */
		public static function automatic_post_status_args() {
			/**
			 * This hook is used to alter the automatic post status arguments.
			 * 
			 * @since 1.0
			 */
			$args = apply_filters(
					'fgf_automatic_post_status_args',
					array(
						'label'                     => esc_html_x( 'Automatic', 'free-gifts-for-woocommerce' ),
						'public'                    => true,
						'exclude_from_search'       => false,
						'show_in_admin_all_list'    => true,
						'show_in_admin_status_list' => true,
						/* translators: %s: count */
						'label_count'               => _n_noop( 'Automatic <span class="count">(%s)</span>', 'Automatic <span class="count">(%s)</span>', 'free-gifts-for-woocommerce' ),
					)
					) ;

			return $args ;
		}
	}

	FGF_Register_Post_Status::init() ;
}
