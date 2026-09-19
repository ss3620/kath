<?php

/**
 * Handles the notices.
 * 
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

if (!class_exists('FGF_Notices_Handler')) {

	/**
	 * Class.
	 * 
	 * @since 1.0.0
	 */
	class FGF_Notices_Handler {

		/**
		 * Class Initialization.
		 * 
		 * @since 1.0.0
		 */
		public static function init() {
			// Define the BOGO eligible notice display hooks.
			add_action( 'wp_head', array( __CLASS__, 'define_hooks' ), 10, 3 ) ;
			// May be add BOGO eligible notices in the available variation.
			add_action('woocommerce_available_variation', array( __CLASS__, 'maybe_add_bogo_eligible_notices_in_available_variation' ), 10, 3);
			// May be show the gift products related notices in the cart.
			add_action('woocommerce_before_cart', array( __CLASS__, 'maybe_show_cart_notices' ), 5);
			// May be show the gift products related notices in the checkout.
			add_action('woocommerce_before_checkout_form', array( __CLASS__, 'maybe_show_checkout_notices' ), 5);
		}

		/**
		 * Define the eligible notice display hooks.
		 * 
		 * @since 11.9.0
		 * */
		public static function define_hooks() {
			$single_product_location = self::get_single_product_display_current_location();
			if (fgf_check_is_array($single_product_location)) {
				// Hook for the BOGO eligible notice on single product page.
				add_action($single_product_location['hook'], array( __CLASS__, 'render_bogo_eligible_notices_product_page' ), $single_product_location['priority']);
			}

			$loop_page_location = self::get_loop_page_display_current_location();
			if (fgf_check_is_array($loop_page_location)) {
				// Hook for the BOGO eligible notice on loop page.
				add_action($loop_page_location['hook'], array( __CLASS__, 'render_loop_pag_eligible_notices' ), $loop_page_location['priority']);
			}
		}

		/**
		 * Get the single product page display current location.
		 *
		 * @since 11.9.0
		 * @return array.
		 */
		public static function get_single_product_display_current_location() {
			$single_product_location = get_option('fgf_settings_bogo_eligible_notice_on_single_product_position');
			$location_details        = fgf_get_bogo_eligible_notice_on_single_product_display_positions();

			return isset($location_details[$single_product_location]) ? $location_details[$single_product_location] : reset($location_details);
		}

		/**
		 * Get the single product page display current location.
		 *
		 * @since 11.9.0
		 * @return array.
		 */
		public static function get_loop_page_display_current_location() {
			$loop_page_location = get_option('fgf_settings_bogo_eligible_notice_on_loop_page_position');
			$location_details   = fgf_get_bogo_eligible_notice_on_loop_page_display_positions();

			return isset($location_details[$loop_page_location]) ? $location_details[$loop_page_location] : reset($location_details);
		}

		/**
		 * May be add eligible notices in the available variation if exists.
		 * 
		 * @since 11.9.0
		 */
		public static function render_bogo_eligible_notices_product_page() {
			global $product ;
			if ( ! is_object( $product ) ) {
				return ;
			}

			self::render_single_product_bogo_eligible_notices($product);
		}

		/**
		 * May be add eligible notices in the available variation if exists.
		 * 
		 * @since 11.9.0
		 */
		public static function maybe_add_bogo_eligible_notices_in_available_variation( $array, $class, $variation ) {
			// Return if the array is empty.
			if (!fgf_check_is_array($array)) {
				return $array;
			}

			// Return if the variation is not object.
			if (!is_object($variation)) {
				return $array;
			}

			$array['fgf_free_gifts'] = self::render_single_product_bogo_eligible_notices($variation, false);

			return $array;
		}

		/**
		 * May be render the eligible notice on single product page.
		 * 
		 * @since 11.9.0
		 * @param object $product
		 * @param bool $echo 
		 * @return HTML/null
		 */
		public static function render_single_product_bogo_eligible_notices( $product, $echo = true ) {
			// If the BOGO eligible notice is not enabled on the single product page, return it.
			if ('1'!==get_option('fgf_settings_bogo_eligible_notice_on_single_product')) {
				return null;
			}

			if ( ! is_object( $product ) ) {
				return null;
			}

			$notices =FGF_BOGO_Rule_Notice_Handler::get_bogo_eligible_notices($product);
			
			if ($echo) {
				fgf_get_template('single-product/bogo-eligible-notices.php', array( 'notices'=>$notices ));
			} else {
				return fgf_get_template_html('single-product/bogo-eligible-notices.php', array( 'notices'=>$notices ));
			}
		}

		/**
		 * May be render the eligible notices on loop page.
		 * 
		 * @since 11.9.0
		 */
		public static function render_loop_pag_eligible_notices() {
			// If the BOGO eligible notice is not enabled on the loop page, return it.
			if ('1'!==get_option('fgf_settings_bogo_eligible_notice_on_loop_page')) {
				return;
			}

			global $product ;
			if ( ! is_object( $product ) ) {
				return ;
			}

			$notices =FGF_BOGO_Rule_Notice_Handler::get_bogo_eligible_notices($product);
			if (!fgf_check_is_array($notices)) {
				return;
			}

			fgf_get_template('loop/bogo-eligible-notices.php', array( 'notices'=>$notices ));
		}

		/**
		 * May be show the gift products related notices in the cart.
		 *
		 * @since 1.0.0
		 * @return void
		 * */
		public static function maybe_show_cart_notices() {
			// May be show the gift products notices in cart.
			self::maybe_show_cart_gift_notices();
			// May be display the eligible gift products notice in the cart.
			self::maybe_show_cart_gift_products_eligible_notice();
		}

		/**
		 * May be show the gift products related notices in checkout.
		 *
		 * @since 1.0.0
		 * @return void
		 * */
		public static function maybe_show_checkout_notices() {
			echo '<div id="fgf-checkout-gift-notices-wrapper">';
			// May be show the gift products notices in checkout.
			self::maybe_show_checkout_gift_notices();
			// May be display the eligible gift products notice in the checkout.
			self::maybe_show_checkout_gift_products_eligible_notice();
			echo '</div>';
		}

		/**
		 * Is valid to show the notice?.
		 *
		 * @since 1.0.0
		 * @return bool.
		 * */
		public static function is_valid_show_notice() {
			// Return if the cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return false;
			}

			// Return if the cart is empty.
			if (WC()->cart->get_cart_contents_count() == 0) {
				return false;
			}

			// Restrict the display of the gift products when the maximum gifts count reached.
			if (!FGF_Rule_Handler::manual_product_exists() || FGF_Rule_Handler::check_per_order_count_exists()) {
				return false;
			}

			$return        = true;
			$gift_products = FGF_Rule_Handler::get_overall_manual_gift_products();
			if (!fgf_check_is_array($gift_products)) {
				$return = false;
			}
			/**
			 * This hook is used to validate the notice.
			 *
			 * @since 1.0
			 */
			return apply_filters('fgf_is_valid_notice', $return);
		}

		/**
		 * Get the cart gift notice.
		 * 
		 * @since 11.0.0
		 * @return string
		 */
		public static function get_cart_gift_notice() {
			/**
			 * This hook is used to validate the notice to show in the cart.
			 *
			 * @since 1.0.0
			 */
			if (!apply_filters('fgf_is_valid_show_cart_notice', self::is_valid_show_notice())) {
				return '';
			}

			$notice = ( '2' == get_option('fgf_settings_gift_cart_page_display') ) ? fgf_get_gift_products_popup_notice() : fgf_get_manual_gift_products_notice();
			/**
			 * This hook is used to alter the cart gift notices.
			 * 
			 * @since 11.0.0
			 * @param string $notice
			 */
			return apply_filters('fgf_cart_gift_notices', $notice);
		}

		/**
		 * Get the checkout gift notices.
		 * 
		 * @since 11.0.0
		 * @return array
		 */
		public static function get_checkout_gift_notices() {
			/**
			 * This hook is used to validate the notice to show in the checkout.
			 *
			 * @since 1.0.0
			 */
			if (!apply_filters('fgf_is_valid_show_checkout_notice', self::is_valid_show_notice())) {
				return array();
			}

			$notices = array();
			// Cart forward notice.
			if ('yes' == get_option('fgf_settings_enable_checkout_free_gift_notice') && fgf_get_free_gift_products_count_in_cart() <= 0) {
				$cart_page_url                  = sprintf('<a class="fgf_forward_link" href="%s">%s</a>', wc_get_cart_url(), get_option('fgf_settings_checkout_free_gift_notice_shortcode_message'));
				$notices['cart_forward_notice'] = str_replace('[cart_page]', $cart_page_url, get_option('fgf_settings_checkout_free_gift_notice_message'));
			}

			// Gift notice.
			if ('2' === get_option('fgf_settings_gift_checkout_page_display')) {
				$notices['gift_notice'] = ( '1' === get_option('fgf_settings_checkout_gift_products_display_type') ) ? fgf_get_manual_gift_products_notice() : fgf_get_gift_products_popup_notice();
			}

			/**
			 * This hook is used to alter the checkout gift notices.
			 * 
			 * @param array $notices
			 * @since 11.0.0
			 */
			return apply_filters('fgf_checkout_gift_notices', $notices);
		}

		/**
		 * May be show the gift products notices in the cart.
		 *
		 * @since 1.0.0
		 * @return void
		 * */
		public static function maybe_show_cart_gift_notices() {
			$notice = self::get_cart_gift_notice();
			if (!$notice) {
				return;
			}

			// Notice.
			self::show_notice($notice);
		}

		/**
		 * May be show the gift products notices in checkout.
		 *
		 * @since 1.0.0
		 * @return void.
		 * */
		public static function maybe_show_checkout_gift_notices() {
			$notices = self::get_checkout_gift_notices();
			if (!fgf_check_is_array($notices)) {
				return;
			}

			foreach ($notices as $notice) {
				self::show_notice($notice, 'success', true);
			}
		}

		/**
		 * Is valid to show the eligible notice?.
		 *
		 * @return bool.
		 * */
		public static function is_valid_show_eligible_notice() {
			// Return if the cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return false;
			}

			// Return if the cart is empty.
			if (WC()->cart->get_cart_contents_count() == 0) {
				return false;
			}

			$cart_notices = FGF_Rule_Handler::get_cart_notices();
			if (!fgf_check_is_array($cart_notices)) {
				return false;
			}
			/**
			 * This hook is used to validate the notices.
			 *
			 * @since 1.0
			 */
			return apply_filters('fgf_is_valid_eligible_notice', true);
		}

		/**
		 * Get the cart gift eligible notices.
		 * 
		 * @since 11.0.0
		 * @return array
		 */
		public static function get_cart_gift_eligible_notices() {
			$display_type = get_option('fgf_settings_display_cart_notices_type');
			if (in_array($display_type, array( '3', '4' ))) {
				return array();
			}

			/**
			 * This hook is used to validate the eligible notices to show in the cart.
			 *
			 * @since 1.0.0
			 */
			if (!apply_filters('fgf_is_valid_show_cart_eligible_notice', self::is_valid_show_eligible_notice())) {
				return array();
			}

			/**
			 * This hook is used to alter the cart gift eligible notices.
			 * 
			 * @since 11.0.0
			 */
			return apply_filters('fgf_cart_gift_eligible_notices', self::get_formatted_eligible_notices());
		}

		/**
		 * Get the checkout gift eligible notices.
		 * 
		 * @since 11.0.0
		 * @return array
		 */
		public static function get_checkout_gift_eligible_notices() {
			$display_type = get_option('fgf_settings_display_cart_notices_type');
			if (in_array($display_type, array( '2', '4' ))) {
				return array();
			}

			/**
			 * This hook is used to validate the eligible notices to show in the checkout.
			 *
			 * @since 1.0.0
			 */
			if (!apply_filters('fgf_is_valid_show_checkout_eligible_notice', self::is_valid_show_eligible_notice())) {
				return array();
			}

			/**
			 * This hook is used to alter the checkout gift eligible notices.
			 * 
			 * @since 11.0.0
			 */
			return apply_filters('fgf_checkout_gift_eligible_notices', self::get_formatted_eligible_notices());
		}

		/**
		 * Get the formatted eligible notices.
		 * 
		 * @since 11.0.0
		 * @return array
		 */
		public static function get_formatted_eligible_notices() {
			$eligible_notices = array();
			$cart_notices     = FGF_Rule_Handler::get_cart_notices();
			foreach ($cart_notices as $notice_data) {
				// Display the eligible gift product notice.
				$eligible_notices[] = self::format_eligible_notice($notice_data);

				if ('2' == get_option('fgf_settings_display_eligibility_notices_type')) {
					break;
				}
			}

			return $eligible_notices;
		}

		/**
		 * Maybe display the eligible gift products notice in the cart.
		 *
		 * @since 1.0.0
		 * @return void
		 * */
		public static function maybe_show_cart_gift_products_eligible_notice() {
			$display_type = get_option('fgf_settings_display_cart_notices_type');
			if (in_array($display_type, array( '3', '4' ))) {
				return;
			}

			/**
			 * This hook is used to validate the eligible notices to show in the cart.
			 *
			 * @since 1.0
			 */
			if (!apply_filters('fgf_is_valid_show_cart_eligible_notice', self::is_valid_show_eligible_notice())) {
				return;
			}

			self::show_eligible_notices();
		}

		/**
		 * Maybe display the eligible gift products notice in the checkout.
		 *
		 * @return void
		 * */
		public static function maybe_show_checkout_gift_products_eligible_notice() {
			$display_type = get_option('fgf_settings_display_cart_notices_type');
			if (in_array($display_type, array( '2', '4' ))) {
				return;
			}

			/**
			 * This hook is used to validate the eligible notices to show in the checkout.
			 *
			 * @since 1.0
			 */
			if (!apply_filters('fgf_is_valid_show_checkout_eligible_notice', self::is_valid_show_eligible_notice())) {
				return;
			}

			self::show_eligible_notices(true);
		}

		/**
		 * Show the eligible notices.
		 *
		 * @return void
		 * */
		public static function show_eligible_notices( $own_notice = false ) {
			$cart_notices = FGF_Rule_Handler::get_cart_notices();
			foreach ($cart_notices as $notice_data) {
				$plugin_notice = !empty($notice_data['icon_url']) ? true : $own_notice;
				// Display the eligible gift product notice.
				self::show_notice(self::format_eligible_notice($notice_data), 'notice', $plugin_notice);

				if ('2' == get_option('fgf_settings_display_eligibility_notices_type')) {
					break;
				}
			}
		}

		/**
		 * Format the eligible notice.
		 *
		 * @since 10.4.0
		 * @param array $notice_data
		 */
		public static function format_eligible_notice( $notice_data ) {
			return fgf_get_template_html('notices/content.php', $notice_data);
		}

		/**
		 * Add or render the notice.
		 *
		 * @since 1.0.0
		 * @param string $notice
		 * @param string $type
		 * @param boolean $plugin_notice
		 */
		public static function show_notice( $notice, $type = 'success', $plugin_notice = false ) {
			if ($plugin_notice || '2' == get_option('fgf_settings_display_notice_mode')) {
				$notices = array(
					'notice' =>
					array(
						'notice' => $notice,
						'data' => array(),
					),
				);

				/**
				 * This hook is used to alter the notice arguments.
				 * 
				 * @since 11.7.0
				 */
				$notices =apply_filters('fgf_notice_arguments', $notices, $notice, $type);

				fgf_get_template('notices/' . $type . '.php', $notices);
			} elseif (!wc_has_notice($notice, $type)) {
				fgf_add_wc_notice($notice, $type);
			}
		}
	}

	FGF_Notices_Handler::init();
}
