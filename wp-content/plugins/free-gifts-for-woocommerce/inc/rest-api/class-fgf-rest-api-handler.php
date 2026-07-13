<?php

/**
 * Rest API Handler.
 * 
 * @since 9.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}

if (!class_exists('FGF_REST_API_Handler')) {

	/**
	 * Class.
	 * 
	 * @since 9.0.0
	 */
	class FGF_REST_API_Handler {

		/**
		 * Class Initialization.
		 * 
		 * @since 9.0.0
		 */
		public static function init() {
			add_action('rest_api_init', array( __CLASS__, 'register_rest_routes' ));
			add_action('woocommerce_rest_is_request_to_rest_api', array( __CLASS__, 'is_request_to_rest_api' ));
		}

		/**
		 * Is request to rest API?
		 * 
		 * @since 9.0.0
		 * @param boolean $bool
		 * @return bool
		 */
		public static function is_request_to_rest_api( $bool ) {
			$rest_prefix = trailingslashit(rest_get_url_prefix());
			$request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
			if (false === strpos($request_uri, $rest_prefix . 'fgf/')) {
				return $bool;
			}

			return true;
		}

		/**
		 * Register rest routes.
		 * 
		 * @since 9.0.0
		 */
		public static function register_rest_routes() {
			include_once FGF_ABSPATH . 'inc/rest-api/class-fgf-rest-posts-controller.php';

			$controllers = array(
				'rule' => 'FGF_Rule_Controller',
			);

			/**
			 * This hook is used to alter the REST API controllers. 
			 * 
			 * @since 9.0.0
			 */
			$controllers = apply_filters('fgf_rest_api_controllers', $controllers);

			foreach ($controllers as $key => $class_name) {

				$file_name = FGF_ABSPATH . 'inc/rest-api/class-fgf-rest-' . $key . '-controller.php';
				if (file_exists($file_name)) {
					include_once $file_name;
				}

				$controller = new $class_name();
				if (!is_object($controller)) {
					continue;
				}

				$controller->register_routes();
			}
		}
	}

	FGF_REST_API_Handler::init();
}
