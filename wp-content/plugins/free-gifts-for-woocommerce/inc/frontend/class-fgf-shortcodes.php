<?php

/**
 * Shortcodes.
 * */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
if (!class_exists('FGF_Shortcodes')) {

	/**
	 * Class.
	 */
	class FGF_Shortcodes {

		/**
		 * Plugin slug.
		 * 
		 * @var string
		 * */
		private static $plugin_slug = 'fgf';

		/**
		 * Class Initialization.
		 * */
		public static function init() {
			/**
			 * This hook is used to alter the short codes.
			 * 
			 * @since 1.0
			 */
			$shortcodes = apply_filters('fgf_load_shortcodes', array(
				'fgf_gift_products',
				'fgf_cart_eligible_notices',
				'fgf_bogo_eligible_notices',
				'fgf_progress_bar',
			));

			foreach ($shortcodes as $shortcode_name) {

				add_shortcode($shortcode_name, array( __CLASS__, 'process_shortcode' ));
			}
		}

		/**
		 * Process Shortcode.
		 * */
		public static function process_shortcode( $atts, $content, $tag ) {
			$shortcode_name = str_replace('fgf_', '', $tag);
			$function = 'shortcode_' . $shortcode_name;

			switch ($shortcode_name) {
				case 'gift_products':
				case 'cart_eligible_notices':
				case 'bogo_eligible_notices':
				case 'fgf_progress_bar':
					ob_start();
					self::$function($atts, $content); // output for shortcode.
					$content = ob_get_contents();
					ob_end_clean();
					break;

				default:
					ob_start();
					/**
					 * This hook is used to display the short code content.
					 * 
					 * @since 1.0
					 */
					do_action("fgf_shortcode_{$shortcode_name}_content");
					$content = ob_get_contents();
					ob_end_clean();
					break;
			}

			return $content;
		}

		/**
		 * Shortcode for the gift products.
		 * */
		public static function shortcode_gift_products( $atts, $content ) {

			$atts = shortcode_atts(array(
				'per_page' => fgf_get_free_gifts_per_page_column_count(),
				'mode' => 'inline',
				'type' => 'table',
					), $atts, 'fgf_gift_products');

			$atts['data_args'] = self::get_gift_product_data($atts);

			$atts['popup_message'] = fgf_get_gift_products_popup_notice();

			// Display the Gift Products shortcode layout.
			fgf_get_template('shortcode-layout.php', $atts);
		}

		/**
		 * Get Gift Product Data
		 */
		public static function get_gift_product_data( $atts ) {
			// Return if cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return false;
			}

			// return if cart is empty
			if (WC()->cart->get_cart_contents_count() == 0) {
				return false;
			}

			// Restrict the display of the gift products when the maximum gifts count reached.
			if (!FGF_Rule_Handler::manual_product_exists() || FGF_Rule_Handler::check_per_order_count_exists()) {
				return;
			}

			$gift_products = FGF_Rule_Handler::get_overall_manual_gift_products();
			if (!fgf_check_is_array($gift_products)) {
				return false;
			}

			switch ($atts['type']) {
				case 'selectbox':
					$data_args = array(
						'template' => 'dropdown-layout.php',
						'gift_products' => $gift_products,
					);
					break;

				case 'carousel':
					$data_args = array(
						'template' => 'carousel-layout.php',
						'gift_products' => $gift_products,
					);
					break;

				default:
					$per_page = $atts['per_page'];
					$current_page = 1;

					/* Calculate Page Count */
					$default_args['posts_per_page'] = $per_page;
					$default_args['offset'] = ( $current_page - 1 ) * $per_page;
					$page_count = ceil(count($gift_products) / $per_page);

					$data_args = array(
						'template' => 'gift-products-layout.php',
						'gift_products' => array_slice($gift_products, $default_args['offset'], $per_page),
						'pagination' => array(
							'page_count' => $page_count,
							'current_page' => $current_page,
							'next_page_count' => ( ( $current_page + 1 ) > ( $page_count - 1 ) ) ? ( $current_page ) : ( $current_page + 1 ),
						),
					);
					break;
			}

			$data_args['mode'] = $atts['mode'];

			return $data_args;
		}

		/**
		 * Shortcode for the cart eligible notices.
		 * */
		public static function shortcode_cart_eligible_notices( $atts, $content ) {

			/**
			 * This hook is used to validate the eligible notice to show.
			 * 
			 * @since 1.0
			 */
			if (!apply_filters('fgf_is_valid_show_cart_eligible_notice', FGF_Notices_Handler::is_valid_show_eligible_notice())) {
				return '';
			}

			$cart_notices = FGF_Rule_Handler::get_cart_notices();
			foreach ($cart_notices as $cart_notice) {
				// Display the eligible gift product notice.
				$notices = array(
					'notice' =>
					array(
						'notice' => FGF_Notices_Handler::format_eligible_notice($cart_notice),
						'data' => array(),
					),
				);
								
				fgf_get_template('notices/notice.php', $notices);
			}
		}

		/**
		 * Shortcode for the BOGO eligible notices for a product.
		 * 
		 * @since 11.9.0
		 * @param array $atts
		 * @param string $content
		 * */
		public static function shortcode_bogo_eligible_notices( $atts, $content ) {
			$atts = shortcode_atts(array( 'product_id' => '' ), $atts, 'fgf_bogo_eligible_notices');
			$product=wc_get_product($atts['product_id']);
			if (!$product) {
				global $product;
			}

			if (!is_object($product)) {
				return;
			}

			if ( ! is_object( $product ) ) {
				return;
			}

			$notices =FGF_BOGO_Rule_Notice_Handler::get_bogo_eligible_notices($product);
			
			fgf_get_template('shortcodes/bogo-eligible-notices.php', array( 'notices'=>$notices ));
		}

		/**
		 * Shortcode for the progress bar to the manual gift products.
		 * 
		 * @since 9.8.0
		 * */
		public static function shortcode_progress_bar( $atts, $content ) {
			// Return if the gift products do not exist.
			$gift_products = FGF_Rule_Handler::get_overall_manual_gift_products();
			if (!fgf_check_is_array($gift_products)) {
				return;
			}

			fgf_get_template('progress-bar.php');
		}
	}

	FGF_Shortcodes::init();
}
