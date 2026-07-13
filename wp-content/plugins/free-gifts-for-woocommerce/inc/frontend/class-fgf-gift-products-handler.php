<?php

/**
 * Handles the free gift products.
 * 
 * @since 1.0.0
 */
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}
if (!class_exists('FGF_Gift_Products_Handler')) {

	/**
	 * Class.
	 * 
	 * @since 1.0.0
	 */
	class FGF_Gift_Products_Handler {

		/**
		 * Automatic gifts added.
		 * 
		 * @since 9.7.0
		 * @var boolean
		 */
		public static $automatic_gifts_added = false;

		/**
		 * Automatic gifts removed.
		 * 
		 * @since 9.7.0
		 * @var type
		 */
		public static $automatic_gifts_removed = false;

		/**
		 * Free gift automatic removed cart item.
		 * 
		 * @since 10.1.0
		 * @var bool
		 */
		public static $free_gift_automatic_removed_cart_item = false;

		/**
		 * Notices.
		 * 
		 * @since 11.0.0
		 * @var array
		 */
		public static $notices = array(
			'success' => array(),
			'error' => array(),
			'info' => array(),
		);

		/**
		 * Class.
		 * 
		 * @since 1.0.0
		 */
		public static function init() {
			// Add automatic gift products to the cart.
			add_action('wp', array( __CLASS__, 'add_to_cart_automatic_gift_product' ));
			// Add manual gift products to the cart.
			add_action('wp', array( __CLASS__, 'add_to_cart_manual_gift_product' ));
			// Remove the gift products from the cart.
			add_action('wp', array( __CLASS__, 'remove_gift_product_from_cart' ));
			// Add automatic gift products to the cart via cart ajax.
			add_action('woocommerce_before_cart', array( __CLASS__, 'add_to_cart_automatic_gift_product_ajax' ));
			// Remove the gift products from the cart via cart ajax.
			add_action('woocommerce_before_cart', array( __CLASS__, 'remove_gift_product_from_cart_ajax' ));
			// Add to automatic gift product in the mini cart.
			add_action('woocommerce_before_mini_cart', array( __CLASS__, 'add_to_cart_automatic_gift_product_mini_cart' ));
			// Remove the free gift products from the mini cart.
			add_action('woocommerce_before_mini_cart', array( __CLASS__, 'remove_gift_product_from_mini_cart' ));
			// Add automatic gift products to the cart via checkout ajax.
			add_action('woocommerce_review_order_before_cart_contents', array( __CLASS__, 'add_to_cart_automatic_gift_product_ajax' ));
			// Remove the gift products from the cart via checkout ajax.
			add_action('woocommerce_review_order_before_cart_contents', array( __CLASS__, 'remove_gift_product_from_cart_ajax' ));

			// Define the gift products hooks.
			add_action('wp_head', array( __CLASS__, 'define_gift_products_hooks' ));

			// Add the gifts details in the order review fragments.
			add_filter('woocommerce_update_order_review_fragments', array( __CLASS__, 'maybe_add_gift_details_in_order_review_fragments' ), 10, 1);

			// Render the progress bar in the cart page.
			add_action('woocommerce_before_cart_table', array( __CLASS__, 'render_progress_bar_cart_page' ), 10);
			// Render the progress bar in the checkout page.
			add_action('woocommerce_before_checkout_form', array( __CLASS__, 'render_progress_bar_checkout_page' ), 20);
		}

		/**
		 * Render the progress bar for manual gift products in the cart page.
		 * 
		 * @since 9.8.0
		 */
		public static function render_progress_bar_cart_page() {
			if ('yes' !== get_option('fgf_settings_cart_page_progress_bar_enabled')) {
				return;
			}

			self::render_progress_bar();
		}

		/**
		 * Render the progress bar for manual gift products in the checkout page.
		 * 
		 * @since 9.8.0
		 */
		public static function render_progress_bar_checkout_page() {
			if ('yes' !== get_option('fgf_settings_checkout_page_progress_bar_enabled')) {
				return;
			}

			echo '<div id="fgf-checkout-progress-bar-wrapper">';
			self::render_progress_bar();
			echo '</div>';
		}

		/**
		 * Render the progress bar for manual gift products.
		 * 
		 * @since 11.2.0
		 */
		public static function render_progress_bar() {
			// Return if the gift products do not exist.
			if (!FGF_Rule_Handler::get_total_gift_products_count()) {
				return;
			}

			fgf_get_template('progress-bar.php');
		}

		/**
		 * Define the gift products hooks.
		 * 
		 * @since 8.7
		 * */
		public static function define_gift_products_hooks() {
			$customize_hook = self::get_gift_display_cart_page_current_location();
			if (fgf_check_is_array($customize_hook)) {
				// Hook for the gift display in the cart page.
				add_action($customize_hook['hook'], array( __CLASS__, 'render_gift_products_cart_page' ), $customize_hook['priority']);
			}

			$customize_hook = self::get_gift_display_checkout_page_current_location();
			if (fgf_check_is_array($customize_hook)) {
				// Hook for the gift display in the checkout page.
				add_action($customize_hook['hook'], array( __CLASS__, 'render_gift_products_checkout_page' ), $customize_hook['priority']);
			}
		}

		/**
		 * Get the gift display cart page current location.
		 *
		 * @return array.
		 */
		public static function get_gift_display_cart_page_current_location() {
			$cart_location = get_option('fgf_settings_gift_cart_page_display_position');
			/**
			 * This hook is used to alter the gift products display positions in the cart.
			 * 
			 * @since 1.0
			 */
			$location_details = apply_filters('fgf_gift_display_cart_page_position', array(
				'1' => array(
					'hook' => 'woocommerce_after_cart_table',
					'priority' => 10,
				),
				'2' => array(
					'hook' => 'woocommerce_before_cart_table',
					'priority' => 10,
				),
			));

			$location_detail = isset($location_details[$cart_location]) ? $location_details[$cart_location] : reset($location_details);

			return $location_detail;
		}

		/**
		 * Get the gift display checkout page current location.
		 *
		 * @return array.
		 */
		public static function get_gift_display_checkout_page_current_location() {
			if ('1' === get_option('fgf_settings_checkout_gift_products_display_type')) {
				$location_details = array(
					'1' => array(
						'hook' => 'woocommerce_before_checkout_form',
						'priority' => 20,
					),
					'2' => array(
						'hook' => 'woocommerce_after_checkout_billing_form',
						'priority' => 10,
					),
					'3' => array(
						'hook' => get_option('fgf_settings_checkout_gift_products_custom_hook_name'),
						'priority' => get_option('gtw_settings_order_gift_wrapper_custom_hook_priority'),
					),
				);
			} else {
				$location_details = array(
					'1' => array(
						'hook' => 'woocommerce_checkout_order_review',
						'priority' => 10,
					),
				);
			}

			$checkout_location = get_option('fgf_settings_checkout_gift_products_hook_name');
			/**
			 * This hook is used to alter the gift products display positions in the checkout.
			 * 
			 * @since 1.0
			 */
			$location_details = apply_filters('fgf_gift_display_checkout_page_position', $location_details);

			return isset($location_details[$checkout_location]) ? $location_details[$checkout_location] : reset($location_details);
		}

		/**
		 * May be add gift details in the order review fragments.
		 * 
		 * @since 11.2.0
		 * @param array $fragments
		 * @return array
		 */
		public static function maybe_add_gift_details_in_order_review_fragments( $fragments ) {
			$fragments = fgf_check_is_array($fragments) ? $fragments : array();

			$fragments['fgf_notices_html'] = fgf_get_checkout_free_gifts_notices_html();
			$fragments['fgf_gift_details_html'] = fgf_get_checkout_free_gifts_html();
			$fragments['fgf_progress_bar_html'] = fgf_get_checkout_progress_bar_html();

			return $fragments;
		}

		/**
		 * Remove Gift products from cart.
		 * 
		 * @return mixed
		 * */
		public static function remove_gift_product_from_cart() {
			if (isset($_REQUEST['payment_method']) || isset($_REQUEST['woocommerce-cart-nonce'])) {
				return;
			}

			self::remove_gift_products();
		}

		/**
		 * Remove Gift products from cart via ajax.
		 * 
		 * @return mixed
		 * */
		public static function remove_gift_product_from_cart_ajax() {
			if (!isset($_REQUEST['payment_method']) && !isset($_REQUEST['woocommerce-cart-nonce'])) {
				return;
			}

			self::remove_gift_products();
		}

		/**
		 * Remove Gift products from the mini cart.
		 * 
		 * @return mixed
		 * */
		public static function remove_gift_product_from_mini_cart() {
			if (isset($_REQUEST['payment_method']) || isset($_REQUEST['woocommerce-cart-nonce'])) {
				return;
			}

			self::remove_gift_products();
		}

		/**
		 * Add to automatic gift product in cart via ajax.
		 * 
		 * @return mixed
		 * */
		public static function add_to_cart_automatic_gift_product_ajax() {
			if (!isset($_REQUEST['payment_method']) && !isset($_REQUEST['woocommerce-cart-nonce'])) {
				return;
			}

			// Don't add automatic gift products when it is already executed.
			if (self::$automatic_gifts_added) {
				return;
			}

			self::automatic_gift_product(false);
			self::bogo_gift_product(false);
			self::coupon_gift_product(false);
			self::subtotal_gift_product(false);
			self::bulk_pricing_gift_product(false);
			self::bulk_quantity_gift_product(false);
			self::cheapest_gift_product(false);

			self::$automatic_gifts_added = true;
		}

		/**
		 * Add to automatic gift product in cart.
		 * 
		 * @return mixed
		 * */
		public static function add_to_cart_automatic_gift_product() {

			if (isset($_REQUEST['payment_method']) || isset($_REQUEST['woocommerce-cart-nonce'])) {
				return;
			}

			// Don't add automatic gift products when it is already executed.
			if (self::$automatic_gifts_added) {
				return;
			}

			$redirect = ( '2' == get_option('fgf_settings_restrict_redirection_after_gifts_added') ) ? false : true;

			self::automatic_gift_product($redirect);
			self::bogo_gift_product($redirect);
			self::coupon_gift_product($redirect);
			self::subtotal_gift_product($redirect);
			self::bulk_pricing_gift_product($redirect);
			self::bulk_quantity_gift_product($redirect);
			self::cheapest_gift_product($redirect);

			self::$automatic_gifts_added = true;
		}

		/**
		 * Add to automatic gift product in the mini cart.
		 * 
		 * @return mixed
		 * */
		public static function add_to_cart_automatic_gift_product_mini_cart() {

			if (isset($_REQUEST['payment_method']) || isset($_REQUEST['woocommerce-cart-nonce'])) {
				return;
			}

			// Don't add automatic gift products when it is already executed.
			if (self::$automatic_gifts_added) {
				return;
			}

			self::automatic_gift_product(false);
			self::bogo_gift_product(false);
			self::coupon_gift_product(false);
			self::subtotal_gift_product(false);
			self::bulk_pricing_gift_product(false);
			self::bulk_quantity_gift_product(false);
			self::cheapest_gift_product(false);

			self::$automatic_gifts_added = true;
		}

		/**
		 * Render the gift products in the cart page.
		 * 
		 * @since 9.3.0
		 */
		public static function render_gift_products_cart_page() {
			$mode = ( '2' == get_option('fgf_settings_gift_cart_page_display') ) ? 'popup' : 'inline';

			self::display_gift_products($mode);
		}

		/**
		 * Render the gift products in the checkout page.
		 * 
		 * @since 9.3.0
		 */
		public static function render_gift_products_checkout_page() {
			// Return if the checkout page gift products display option is disabled. 
			if ('2' !== get_option('fgf_settings_gift_checkout_page_display')) {
				return;
			}

			$mode = ( '2' == get_option('fgf_settings_checkout_gift_products_display_type') ) ? 'popup' : 'inline';

			echo '<div id="fgf-checkout-gift-details-wrapper">';
			self::display_gift_products($mode, 'checkout');
			echo '</div>';
		}

		/**
		 * Render the gift products.
		 */
		public static function display_gift_products( $mode = 'inline', $page = 'cart' ) {
			/**
			 * This hook is used to do extra action before manual gift products summary.
			 * 
			 * @since 1.0
			 */
			do_action('fgf_before_manual_gift_products_summary');

			// Restrict the display of the gift products when the maximum gifts count reached.
			if (!FGF_Rule_Handler::manual_product_exists() || FGF_Rule_Handler::check_per_order_count_exists()) {
				return;
			}

			// Return if data args does not exists.
			$data_args = self::get_gift_product_data();
			if (!$data_args) {
				return;
			}

			$data_args['permalink'] = ( 'checkout' === $page ) ? wc_get_page_permalink('checkout') : get_permalink();

			if ('popup' === $mode) {
				$data_args['mode'] = 'popup';
				// Display Gift Products popup layout.
				fgf_get_template('popup-layout.php', array( 'data_args' => $data_args ));
			} else {
				$data_args['mode'] = 'inline';
				// Display Gift Products layout
				fgf_get_template($data_args['template'], $data_args);
			}

			/**
			 * This hook is used to do extra action after manual gift products summary.
			 * 
			 * @since 1.0
			 */
			do_action('fgf_after_manual_gift_products_summary');
		}

		/**
		 *  Get Gift Product Data
		 */
		public static function get_gift_product_data() {
			$gift_products = FGF_Rule_Handler::get_overall_manual_gift_products();
			if (!fgf_check_is_array($gift_products)) {
				return false;
			}

			$display_type = get_option('fgf_settings_gift_display_type');

			switch ($display_type) {
				case '3':
					$data_args = array(
						'template' => 'dropdown-layout.php',
						'gift_products' => $gift_products,
					);
					break;

				case '2':
					$data_args = array(
						'template' => 'carousel-layout.php',
						'gift_products' => $gift_products,
					);
					break;

				default:
					$per_page = fgf_get_free_gifts_per_page_column_count();
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

			return $data_args;
		}

		/**
		 * Add to gift product in cart.
		 */
		public static function add_to_cart_manual_gift_product() {

			if (!isset($_GET['fgf_gift_product']) || !isset($_GET['fgf_rule_id'])) {
				return;
			}

			// Return if cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return;
			}

			// return if cart is empty
			if (WC()->cart->get_cart_contents_count() == 0) {
				return;
			}

			// Restrict Adding gift product if gift products per order count exists
			if (FGF_Rule_Handler::check_per_order_count_exists()) {
				return;
			}

			$product_id = absint($_GET['fgf_gift_product']);
			$rule_id = absint($_GET['fgf_rule_id']);
			$buy_product_id = !empty($_GET['fgf_buy_product_id']) ? absint($_GET['fgf_buy_product_id']) : 0;
			$coupon_id = !empty($_GET['fgf_coupon_id']) ? absint($_GET['fgf_coupon_id']) : 0;

			$rule = fgf_get_rule($rule_id);
			$product = wc_get_product($product_id);

			// Return if product id is not proper product
			if (!$product) {
				return;
			}

			// Return if rule id is not proper rule
			if (!$rule->exists()) {
				return;
			}

			// Return if the gift products do not exist.
			$gift_products = FGF_Rule_Handler::get_overall_manual_gift_products();
			if (!fgf_check_is_array($gift_products)) {
				return;
			}

			// Return if the rule is not valid.
			if (!fgf_rule_product_exists($rule, $product_id, $buy_product_id, $coupon_id)) {
				return;
			}

			$cart_item_data = array(
				'fgf_gift_product' => array(
					'mode' => $rule->get_rule_mode(),
					'rule_id' => $rule_id,
					'product_id' => $product_id,
					'buy_product_id' => $buy_product_id,
					'coupon_id' => $coupon_id,
					/**
					 * This hook is used to alter the manual gift product price.
					 * 
					 * @since 1.0
					 */
					'price' => apply_filters('fgf_manual_gift_product_price', fgf_get_free_gift_product_price(), $rule_id, $product_id),
				),
			);

			// Add to Gift product in cart
			WC()->cart->add_to_cart($product_id, '1', 0, array(), $cart_item_data);

			// Success Notice
			fgf_add_wc_notice(get_option('fgf_settings_free_gift_success_message'));

			self::add_notice(get_option('fgf_settings_free_gift_success_message'));

			// Safe Redirect
			wp_safe_redirect(get_permalink());
			exit();
		}

		/**
		 * Add to automatic gift product in cart.
		 * 
		 * @return mixed
		 * */
		public static function automatic_gift_product( $redirect = true ) {
			// Return if cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return;
			}

			// Return if cart is empty.
			if (WC()->cart->get_cart_contents_count() == 0) {
				return;
			}

			// Restrict Adding gift product if gift products per order count exists.
			if (FGF_Rule_Handler::check_per_order_count_exists()) {
				return;
			}

			$automatic_gift_products = FGF_Rule_Handler::get_automatic_gift_products();
			if (!fgf_check_is_array($automatic_gift_products)) {
				return;
			}

			/**
			 * This hook is used to validate the automatic gift products.
			 * 
			 * @since 1.0
			 */
			if (apply_filters('fgf_validate_automatic_gift_products', false)) {
				return;
			}

			$products_added = false;
			$free_products_cart_count = fgf_get_free_gift_products_count_in_cart(true);
			$free_gifts_products_order_count = floatval(get_option('fgf_settings_gifts_count_per_order'));

			foreach ($automatic_gift_products as $automatic_gift_product) {

				// Return if order count exists.
				if ($free_gifts_products_order_count && $free_products_cart_count >= $free_gifts_products_order_count) {
					break;
				}

				// Check is valid rule.
				if (!FGF_Rule_Handler::rule_product_exists($automatic_gift_product['rule_id'], $automatic_gift_product['product_id'], true)) {
					continue;
				}

				// Return If already added this product in cart.
				if ($automatic_gift_product['hide_add_to_cart']) {
					continue;
				}

				/**
				 * This hook is used to validate the automatic gift product before add to the cart.
				 * 
				 * @since 8.8
				 */
				if (apply_filters('fgf_validate_automatic_gift_product_add_to_cart', false, $automatic_gift_product)) {
					continue;
				}

				// Validate the automatic gift product before add to the cart.
				if (self::validate_automatic_gift_product_before_add_to_cart($automatic_gift_product)) {
					continue;
				}

				$rule = fgf_get_rule($automatic_gift_product['rule_id']);
				$product = wc_get_product($automatic_gift_product['product_id']);

				// Return if product id is not proper product.
				if (!$product) {
					return;
				}

				// Return if rule id is not proper rule.
				if (!$rule->exists()) {
					return;
				}

				$cart_item_data = array(
					'fgf_gift_product' => array(
						'mode' => 'automatic',
						'rule_id' => $automatic_gift_product['rule_id'],
						'product_id' => $automatic_gift_product['product_id'],
						/**
						 * This hook is used to alter the automatic gift product price.
						 * 
						 * @since 1.0
						 */
						'price' => apply_filters('fgf_automatic_gift_product_price', fgf_get_free_gift_product_price(), $automatic_gift_product),
						'qty' => $automatic_gift_product['qty'],
					),
				);

				$products_added = true;

				$free_products_cart_count++;

				// Add to Gift product in cart
				WC()->cart->add_to_cart($automatic_gift_product['product_id'], $automatic_gift_product['qty'], 0, array(), $cart_item_data);
			}

			if ($products_added) {
				// Success Notice.
				fgf_add_wc_notice(get_option('fgf_settings_free_gift_automatic_success_message'));

				self::add_notice(get_option('fgf_settings_free_gift_automatic_success_message'));

				if (self::is_valid_redirection('automatic', $redirect)) {
					// Safe redirect to current page.
					wp_safe_redirect(get_permalink());
					exit();
				}
			}
		}

		/**
		 * Add to BOGO gift product in cart.
		 * 
		 * @return mixed
		 * */
		public static function bogo_gift_product( $redirect = true ) {

			// Return if cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return;
			}

			// Return if cart is empty.
			if (WC()->cart->get_cart_contents_count() == 0) {
				return;
			}

			$bogo_gift_products = FGF_Rule_Handler::get_bogo_gift_products();
			if (!fgf_check_is_array($bogo_gift_products)) {
				return;
			}
			/**
			 * This hook is used to validate the BOGO gift products.
			 * 
			 * @since 1.0
			 */
			if (apply_filters('fgf_validate_bogo_gift_products', false)) {
				return;
			}

			$products_added = false;

			foreach ($bogo_gift_products as $key => $bogo_gift_product) {

				// Return if already added this product in the cart.
				if ($bogo_gift_product['hide_add_to_cart']) {
					continue;
				}

				/**
				 * This hook is used to validate the BOGO gift product before add to the cart.
				 * 
				 * @since 8.8
				 */
				if (apply_filters('fgf_validate_bogo_gift_product_add_to_cart', false, $bogo_gift_product)) {
					continue;
				}

				// Validate the BOGO gift product before add to the cart.
				if (self::validate_automatic_gift_product_before_add_to_cart($bogo_gift_product)) {
					continue;
				}

				$rule = fgf_get_rule($bogo_gift_product['rule_id']);
				$product = wc_get_product($bogo_gift_product['product_id']);

				// Return if product id is not a proper product.
				if (!$product) {
					continue;
				}

				// Return if rule id is not proper rule.
				if (!$rule->exists()) {
					continue;
				}

				$cart_item_data = array(
					'fgf_gift_product' => array(
						'mode' => 'bogo',
						'rule_id' => $bogo_gift_product['rule_id'],
						'product_id' => $bogo_gift_product['product_id'],
						'buy_product_id' => $bogo_gift_product['buy_product_id'],
						/**
						 * This hook is used to alter the BOGO gift product price.
						 * 
						 * @since 1.0
						 */
						'price' => apply_filters('fgf_bogo_gift_product_price', fgf_get_free_gift_product_price(), $bogo_gift_product),
					),
				);

				$products_added = true;

				// Add to Gift product in cart.
				WC()->cart->add_to_cart($bogo_gift_product['product_id'], $bogo_gift_product['qty'], 0, array(), $cart_item_data);
			}

			if ($products_added) {
				// Success Notice.
				fgf_add_wc_notice(get_option('fgf_settings_free_gift_bogo_success_message'));

				self::add_notice(get_option('fgf_settings_free_gift_bogo_success_message'));

				if (self::is_valid_redirection('bogo', $redirect)) {
					// Safe redirect to current page.
					wp_safe_redirect(get_permalink());
					exit();
				}
			}
		}

		/**
		 * Add to coupon gift product in the cart.
		 * 
		 * @return mixed
		 * */
		public static function coupon_gift_product( $redirect = true ) {
			// Return if the cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return;
			}

			// Return if the cart is empty.
			if (WC()->cart->get_cart_contents_count() == 0) {
				return;
			}

			// Restrict Adding gift product if gift products per order count exists
			if (FGF_Rule_Handler::check_per_order_count_exists()) {
				return;
			}

			$coupon_gift_products = FGF_Rule_Handler::get_coupon_gift_products();
			if (!fgf_check_is_array($coupon_gift_products)) {
				return;
			}
			/**
			 * This hook is used to validate the Coupon gift products.
			 * 
			 * @since 1.0
			 */
			if (apply_filters('fgf_validate_coupon_gift_products', false)) {
				return;
			}

			$products_added = false;
			$free_products_cart_count = fgf_get_free_gift_products_count_in_cart(true);
			$free_gifts_products_order_count = floatval(get_option('fgf_settings_gifts_count_per_order'));

			foreach ($coupon_gift_products as $coupon_gift_product) {

				// Return if already added this product in the cart.
				if ($coupon_gift_product['hide_add_to_cart']) {
					continue;
				}

				// Return if order count exists.
				if ($free_gifts_products_order_count && $free_products_cart_count >= $free_gifts_products_order_count) {
					break;
				}

				/**
				 * This hook is used to validate the coupon gift product before add to the cart.
				 * 
				 * @since 8.8
				 */
				if (apply_filters('fgf_validate_coupon_gift_product_add_to_cart', false, $coupon_gift_product)) {
					continue;
				}

				// Validate the coupon gift product before add to the cart.
				if (self::validate_automatic_gift_product_before_add_to_cart($coupon_gift_product)) {
					continue;
				}

				$rule = fgf_get_rule($coupon_gift_product['rule_id']);
				$product = wc_get_product($coupon_gift_product['product_id']);

				// Return if the product id is not a proper product.
				if (!$product) {
					continue;
				}

				// Return if the rule id is not a proper rule.
				if (!$rule->exists()) {
					continue;
				}

				$cart_item_data = array(
					'fgf_gift_product' => array(
						'mode' => 'coupon',
						'rule_id' => $coupon_gift_product['rule_id'],
						'product_id' => $coupon_gift_product['product_id'],
						'coupon_id' => $coupon_gift_product['coupon_id'],
						/**
						 * This hook is used to alter the coupon gift product price.
						 * 
						 * @since 1.0
						 */
						'price' => apply_filters('fgf_coupon_gift_product_price', fgf_get_free_gift_product_price(), $coupon_gift_product),
					),
				);

				$products_added = true;

				$free_products_cart_count++;

				// Add to gift product in the cart.
				WC()->cart->add_to_cart($coupon_gift_product['product_id'], $coupon_gift_product['qty'], 0, array(), $cart_item_data);
			}

			if ($products_added) {
				// Success Notice.
				fgf_add_wc_notice(get_option('fgf_settings_free_gift_coupon_success_message'));

				self::add_notice(get_option('fgf_settings_free_gift_coupon_success_message'));

				if (self::is_valid_redirection('coupon', $redirect)) {
					// Safe redirect to current page.
					wp_safe_redirect(get_permalink());
					exit();
				}
			}
		}

		/**
		 * Add to subtotal gift product in the cart.
		 * 
		 * @since 11.3.0
		 * */
		public static function subtotal_gift_product( $redirect = true ) {
			// Return if the cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return;
			}

			// Return if the cart is empty.
			if (WC()->cart->get_cart_contents_count() == 0) {
				return;
			}

			$subtotal_gift_products = FGF_Rule_Handler::get_subtotal_gift_products();
			if (!fgf_check_is_array($subtotal_gift_products)) {
				return;
			}
			/**
			 * This hook is used to validate the Coupon gift products.
			 * 
			 * @since 11.3.0
			 */
			if (apply_filters('fgf_validate_subtotal_gift_products', false)) {
				return;
			}

			$products_added = false;
			$free_products_cart_count = fgf_get_free_gift_products_count_in_cart(true);
			$free_gifts_products_order_count = floatval(get_option('fgf_settings_gifts_count_per_order'));

			foreach ($subtotal_gift_products as $subtotal_gift_product) {

				// Return if already added this product in the cart.
				if ($subtotal_gift_product['hide_add_to_cart']) {
					continue;
				}

				/**
				 * This hook is used to validate the subtotal gift product before add to the cart.
				 * 
				 * @since 11.3.0
				 */
				if (apply_filters('fgf_validate_subtotal_gift_product_add_to_cart', false, $subtotal_gift_product)) {
					continue;
				}

				// Validate the subtotal gift product before add to the cart.
				if (self::validate_automatic_gift_product_before_add_to_cart($subtotal_gift_product)) {
					continue;
				}

				$rule = fgf_get_rule($subtotal_gift_product['rule_id']);
				$product = wc_get_product($subtotal_gift_product['product_id']);

				// Return if the product id is not a proper product.
				if (!$product) {
					continue;
				}

				// Return if the rule id is not a proper rule.
				if (!$rule->exists()) {
					continue;
				}

				$cart_item_data = array(
					'fgf_gift_product' => array(
						'mode' => 'subtotal',
						'rule_id' => $subtotal_gift_product['rule_id'],
						'product_id' => $subtotal_gift_product['product_id'],
						/**
						 * This hook is used to alter the coupon gift product price.
						 * 
						 * @since 11.3.0
						 */
						'price' => apply_filters('fgf_subtotal_gift_product_price', fgf_get_free_gift_product_price(), $subtotal_gift_product),
					),
				);

				$products_added = true;
				$free_products_cart_count++;

				// Add to gift product in the cart.
				WC()->cart->add_to_cart($subtotal_gift_product['product_id'], $subtotal_gift_product['qty'], 0, array(), $cart_item_data);
			}

			if ($products_added) {
				// Success Notice.
				fgf_add_wc_notice(get_option('fgf_settings_subtotal_based_free_gifts_success_message'));

				self::add_notice(get_option('fgf_settings_subtotal_based_free_gifts_success_message'));

				if (self::is_valid_redirection('subtotal', $redirect)) {
					// Safe redirect to current page.
					wp_safe_redirect(get_permalink());
					exit();
				}
			}
		}

		/**
		 * Add to bulk pricing gift product in the cart.
		 * 
		 * @since 12.0.0
		 * */
		public static function bulk_pricing_gift_product( $redirect = true ) {
			// Return if the cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return;
			}

			// Return if the cart is empty.
			if (WC()->cart->get_cart_contents_count() == 0) {
				return;
			}

			$bulk_pricing_gift_products = FGF_Rule_Handler::get_bulk_pricing_gift_products();
			if (!fgf_check_is_array($bulk_pricing_gift_products)) {
				return;
			}
			/**
			 * This hook is used to validate the bulk pricing gift products.
			 * 
			 * @since 12.0.0
			 */
			if (apply_filters('fgf_validate_bulk_pricing_gift_products', false)) {
				return;
			}

			$products_added = false;
			$free_products_cart_count = fgf_get_free_gift_products_count_in_cart(true);
			$free_gifts_products_order_count = floatval(get_option('fgf_settings_gifts_count_per_order'));
			foreach ($bulk_pricing_gift_products as $bulk_pricing_gift_product) {

				// Return if already added this product in the cart.
				if ($bulk_pricing_gift_product['hide_add_to_cart']) {
					continue;
				}

				/**
				 * This hook is used to validate the bulk pricing gift product before add to the cart.
				 * 
				 * @since 12.0.0
				 */
				if (apply_filters('fgf_validate_bulk_pricing_gift_product_add_to_cart', false, $bulk_pricing_gift_product)) {
					continue;
				}

				// Validate the bulk pricing gift product before add to the cart.
				if (self::validate_automatic_gift_product_before_add_to_cart($bulk_pricing_gift_product)) {
					continue;
				}

				$rule = fgf_get_rule($bulk_pricing_gift_product['rule_id']);
				$product = wc_get_product($bulk_pricing_gift_product['product_id']);

				// Return if the product id is not a proper product.
				if (!$product) {
					continue;
				}

				// Return if the rule id is not a proper rule.
				if (!$rule->exists()) {
					continue;
				}

				$cart_item_data = array(
					'fgf_gift_product' => array(
						'mode' => 'bulk_pricing',
						'rule_id' => $bulk_pricing_gift_product['rule_id'],
						'product_id' => $bulk_pricing_gift_product['product_id'],
						/**
						 * This hook is used to alter the bulk pricing gift product price.
						 * 
						 * @since 12.0.0
						 */
						'price' => apply_filters('fgf_bulk_pricing_gift_product_price', fgf_get_free_gift_product_price(), $bulk_pricing_gift_product),
					),
				);

				$products_added = true;
				$free_products_cart_count++;

				// Add to gift product in the cart.
				WC()->cart->add_to_cart($bulk_pricing_gift_product['product_id'], $bulk_pricing_gift_product['qty'], 0, array(), $cart_item_data);
			}

			if ($products_added) {
				// Success Notice.
				fgf_add_wc_notice(get_option('fgf_settings_bulk_pricing_based_free_gifts_success_message'));

				self::add_notice(get_option('fgf_settings_bulk_pricing_based_free_gifts_success_message'));

				if (self::is_valid_redirection('bulk_pricing', $redirect)) {
					// Safe redirect to current page.
					wp_safe_redirect(get_permalink());
					exit();
				}
			}
		}

		/**
		 * Add to bulk quantity gift product in the cart.
		 * 
		 * @since 12.0.0
		 * */
		public static function bulk_quantity_gift_product( $redirect = true ) {
			// Return if the cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return;
			}

			// Return if the cart is empty.
			if (WC()->cart->get_cart_contents_count() == 0) {
				return;
			}

			$bulk_quantity_gift_products = FGF_Rule_Handler::get_bulk_quantity_gift_products();
			if (!fgf_check_is_array($bulk_quantity_gift_products)) {
				return;
			}
			/**
			 * This hook is used to validate the bulk quantity gift products.
			 * 
			 * @since 12.0.0
			 */
			if (apply_filters('fgf_validate_bulk_quantity_gift_products', false)) {
				return;
			}

			$products_added = false;
			$free_products_cart_count = fgf_get_free_gift_products_count_in_cart(true);
			$free_gifts_products_order_count = floatval(get_option('fgf_settings_gifts_count_per_order'));
			foreach ($bulk_quantity_gift_products as $bulk_quantity_gift_product) {

				// Return if already added this product in the cart.
				if ($bulk_quantity_gift_product['hide_add_to_cart']) {
					continue;
				}

				/**
				 * This hook is used to validate the bulk quantity gift product before add to the cart.
				 * 
				 * @since 12.0.0
				 */
				if (apply_filters('fgf_validate_bulk_quantity_gift_product_add_to_cart', false, $bulk_quantity_gift_product)) {
					continue;
				}

				// Validate the bulk quantity gift product before add to the cart.
				if (self::validate_automatic_gift_product_before_add_to_cart($bulk_quantity_gift_product)) {
					continue;
				}

				$rule = fgf_get_rule($bulk_quantity_gift_product['rule_id']);
				$product = wc_get_product($bulk_quantity_gift_product['product_id']);

				// Return if the product id is not a proper product.
				if (!$product) {
					continue;
				}

				// Return if the rule id is not a proper rule.
				if (!$rule->exists()) {
					continue;
				}

				$cart_item_data = array(
					'fgf_gift_product' => array(
						'mode' => 'bulk_quantity',
						'rule_id' => $bulk_quantity_gift_product['rule_id'],
						'product_id' => $bulk_quantity_gift_product['product_id'],
						/**
						 * This hook is used to alter the bulk quantity gift product price.
						 * 
						 * @since 12.0.0
						 */
						'price' => apply_filters('fgf_bulk_quantity_gift_product_price', fgf_get_free_gift_product_price(), $bulk_quantity_gift_product),
					),
				);

				$products_added = true;
				$free_products_cart_count++;

				// Add to gift product in the cart.
				WC()->cart->add_to_cart($bulk_quantity_gift_product['product_id'], $bulk_quantity_gift_product['qty'], 0, array(), $cart_item_data);
			}

			if ($products_added) {
				// Success Notice.
				fgf_add_wc_notice(get_option('fgf_settings_bulk_quantity_based_free_gifts_success_message'));

				self::add_notice(get_option('fgf_settings_bulk_quantity_based_free_gifts_success_message'));

				if (self::is_valid_redirection('bulk_quantity', $redirect)) {
					// Safe redirect to current page.
					wp_safe_redirect(get_permalink());
					exit();
				}
			}
		}

		/**
		 * Add to cheapest gift product in the cart.
		 * 
		 * @since 12.0.0
		 * */
		public static function cheapest_gift_product( $redirect = true ) {
			// Return if the cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return;
			}

			// Return if the cart is empty.
			if (WC()->cart->get_cart_contents_count() == 0) {
				return;
			}

			$cheapest_gift_products = FGF_Rule_Handler::get_cheapest_gift_products();
			if (!fgf_check_is_array($cheapest_gift_products)) {
				return;
			}
			/**
			 * This hook is used to validate the cheapest gift products.
			 * 
			 * @since 12.0.0
			 */
			if (apply_filters('fgf_validate_cheapest_gift_products', false)) {
				return;
			}

			$products_added = false;
			$free_products_cart_count = fgf_get_free_gift_products_count_in_cart(true);
			$free_gifts_products_order_count = floatval(get_option('fgf_settings_gifts_count_per_order'));
			foreach ($cheapest_gift_products as $cheapest_gift_product) {

				// Return if already added this product in the cart.
				if ($cheapest_gift_product['hide_add_to_cart']) {
					continue;
				}

				/**
				 * This hook is used to validate the cheapest gift product before add to the cart.
				 * 
				 * @since 12.0.0
				 */
				if (apply_filters('fgf_validate_cheapest_gift_product_add_to_cart', false, $cheapest_gift_product)) {
					continue;
				}

				// Validate the cheapest gift product before add to the cart.
				if (self::validate_automatic_gift_product_before_add_to_cart($cheapest_gift_product)) {
					continue;
				}

				$rule = fgf_get_rule($cheapest_gift_product['rule_id']);
				$product = wc_get_product($cheapest_gift_product['product_id']);

				// Return if the product id is not a proper product.
				if (!$product) {
					continue;
				}

				// Return if the rule id is not a proper rule.
				if (!$rule->exists()) {
					continue;
				}

				$cart_item_data = array(
					'fgf_gift_product' => array(
						'mode' => 'cheapest_gift',
						'rule_id' => $cheapest_gift_product['rule_id'],
						'product_id' => $cheapest_gift_product['product_id'],
						/**
						 * This hook is used to alter the cheapest gift product price.
						 * 
						 * @since 12.0.0
						 */
						'price' => apply_filters('fgf_cheapest_gift_product_price', fgf_get_free_gift_product_price(), $cheapest_gift_product),
					),
				);

				$products_added = true;
				$free_products_cart_count++;

				// Add to gift product in the cart.
				WC()->cart->add_to_cart($cheapest_gift_product['product_id'], $cheapest_gift_product['qty'], 0, array(), $cart_item_data);
			}

			if ($products_added) {
				// Success Notice.
				fgf_add_wc_notice(get_option('fgf_settings_cheapest_gift_based_free_gifts_success_message'));

				self::add_notice(get_option('fgf_settings_cheapest_gift_based_free_gifts_success_message'));

				if (self::is_valid_redirection('cheapest_gift', $redirect)) {
					// Safe redirect to current page.
					wp_safe_redirect(get_permalink());
					exit();
				}
			}
		}

		/**
		 * Is a valid redirection?
		 * 
		 * @since 9.9.0
		 * @param string $mode
		 * @param string $redirect
		 * @return boolean
		 */
		public static function is_valid_redirection( $mode, $redirect ) {
			/**
			 * This hook is used to validate the redirection after gift products added.
			 * 
			 * @since 1.0.0
			 */
			if (!apply_filters('fgf_is_valid_redirection', $redirect, $mode) || fgf_doing_ajax() || is_shop()) {
				return false;
			}

			return true;
		}

		/**
		 * Remove Gift products from cart.
		 * */
		public static function remove_gift_products() {
			// Don't remove automatic gift products when it is already executed.
			if (self::$automatic_gifts_removed) {
				return;
			}

			// Return if cart object is not initialized.
			if (!is_object(WC()->cart)) {
				return;
			}

			$products_removed = false;

			foreach (WC()->cart->get_cart() as $key => $value) {

				if (!isset($value['fgf_gift_product'])) {
					continue;
				}

				switch ($value['fgf_gift_product']['mode']) {
					case 'manual':
						$rule_qty = FGF_Rule_Handler::rule_product_exists($value['fgf_gift_product']['rule_id'], $value['fgf_gift_product']['product_id']);
						break;

					case 'automatic':
						$rule_qty = FGF_Rule_Handler::rule_product_exists($value['fgf_gift_product']['rule_id'], $value['fgf_gift_product']['product_id'], true);
						break;

					case 'bogo':
					case 'manual_bogo':
						$rule_qty = FGF_Rule_Handler::get_bogo_rule_product_qty($value['fgf_gift_product']['rule_id'], $value['fgf_gift_product']['product_id'], $value['fgf_gift_product']['buy_product_id']);
						break;

					case 'coupon':
					case 'manual_coupon':
						$rule_qty = FGF_Rule_Handler::get_coupon_rule_product_qty($value['fgf_gift_product']['rule_id'], $value['fgf_gift_product']['product_id'], $value['fgf_gift_product']['coupon_id']);
						break;

					case 'subtotal':
					case 'manual_subtotal':
						$rule_qty = FGF_Rule_Handler::get_subtotal_rule_product_qty($value['fgf_gift_product']['rule_id'], $value['fgf_gift_product']['product_id']);
						break;

					case 'bulk_pricing':
					case 'manual_bulk_pricing':
						$rule_qty = FGF_Rule_Handler::get_bulk_pricing_rule_product_qty($value['fgf_gift_product']['rule_id'], $value['fgf_gift_product']['product_id']);
						break;

					case 'bulk_quantity':
					case 'manual_bulk_quantity':
						$rule_qty = FGF_Rule_Handler::get_bulk_quantity_rule_product_qty($value['fgf_gift_product']['rule_id'], $value['fgf_gift_product']['product_id']);
						break;

					case 'cheapest_gift':
					case 'manual_cheapest_gift':
						$rule_qty = FGF_Rule_Handler::get_cheapest_gift_rule_product_qty($value['fgf_gift_product']['rule_id'], $value['fgf_gift_product']['product_id']);
						break;
				}

				if (!$rule_qty) {
					$products_removed = true;

					// Remove gift products if not matched.
					self::remove_automatic_free_gift_product_cart_item($key);
				} elseif ($rule_qty < $value['quantity']) {
					$products_removed = true;

					// Update gift products quantity.
					WC()->cart->set_quantity($key, $rule_qty);
				}
			}

			// Error Notice
			if ($products_removed) {
				fgf_add_wc_notice(get_option('fgf_settings_free_gift_error_message'), 'notice');

				self::add_notice(get_option('fgf_settings_free_gift_error_message'), 'notice');

				// Reset the rule data after the gifts are removed.
				FGF_Rule_Handler::reset();
			}

			self::$automatic_gifts_removed = true;
		}

		/**
		 * Remove automatic free gift product cart item
		 * 
		 * @since 10.1.0
		 * @param string $key
		 * @return void
		 */
		public static function remove_automatic_free_gift_product_cart_item( $key ) {
			self::$free_gift_automatic_removed_cart_item = true;
			WC()->cart->remove_cart_item($key);
			self::$free_gift_automatic_removed_cart_item = false;
		}

		/**
		 * Validate automatic gift product before add to cart.
		 * 
		 * @since 10.1.0
		 * @param array $gift_product
		 * @return bool
		 */
		public static function validate_automatic_gift_product_before_add_to_cart( $gift_product ) {
			if (!fgf_show_automatic_free_gift_product_cart_item_remove_link()) {
				return false;
			}

			$session_gift_products = fgf_get_removed_automatic_free_gift_products_from_session();
			$rule_id = $gift_product['rule_id'];

			if (!fgf_check_is_array($session_gift_products) || !isset($session_gift_products[$rule_id]) || !fgf_check_is_array($session_gift_products[$rule_id])) {
				return false;
			}

			if (in_array($gift_product['product_id'], $session_gift_products[$rule_id])) {
				return true;
			}

			return false;
		}

		/**
		 * Add a notice
		 * 
		 * @since 11.0.0
		 * @param string $notice
		 * @param string $type
		 */
		public static function add_notice( $notice, $type = 'success' ) {
			self::$notices[$type][] = $notice;
		}

		/**
		 * Get notices.
		 * 
		 * @since 11.0.0
		 * @return array
		 */
		public static function add_notices() {
			return self::$notices;
		}
	}

	FGF_Gift_Products_Handler::init();
}
