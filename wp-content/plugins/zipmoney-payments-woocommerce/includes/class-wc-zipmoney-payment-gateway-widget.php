<?php
class WC_Zipmoney_Payment_Gateway_Widget {

	private $WC_Zipmoney_Payment_Gateway;

	/**
	 * Owns "the configuration root is printed once, and before anything else of ours".
	 *
	 * @var WC_Zipmoney_Payment_Gateway_Widget_Root
	 */
	private $root;

	public function __construct( WC_Zipmoney_Payment_Gateway $WC_Zipmoney_Payment_Gateway ) {
		$this->WC_Zipmoney_Payment_Gateway = $WC_Zipmoney_Payment_Gateway;
		$this->root                        = new WC_Zipmoney_Payment_Gateway_Widget_Root();
	}

	public function init_hooks() {
		// Before any widget markup. The bundle takes the first element in the document
		// carrying zm-merchant, zm-region or zm-widget as its configuration root, so a
		// widget printed higher up the page is taken for one and the merchant key is never
		// read.
		add_action( 'wp_body_open', array( $this, 'render_root_el' ), 0 );

		// A theme that does not call wp_body_open() — required of themes only since WP 5.2
		// — falls back to where the root used to be printed. That is too late for a page
		// whose widget is rendered server-side, but no worse than before.
		add_action( 'wp_footer', array( $this, 'render_root_el' ) );

		add_filter( 'woocommerce_gateway_description', array( $this, 'updateMethodDescription' ), 10, 2 );

		$WC_Zipmoney_Payment_Gateway_Config = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config;

		// inject the order button
		if ( $WC_Zipmoney_Payment_Gateway_Config->is_it_iframe_flow() ) {
			add_filter( 'woocommerce_order_button_html', array( $this, 'order_button' ), 10, 2 );
		}

		// use this hook to convert customer address
		add_action( 'woocommerce_checkout_update_order_review', array( 'WC_Zipmoney_Payment_Gateway_Util', 'update_customer_details' ) );

		// add banner hook
		$this->_add_banner_hook( $WC_Zipmoney_Payment_Gateway_Config );

		// Widget
		$this->_add_widget_hook();

		// Placement the merchant does by hand, for a spot no hook covers.
		add_shortcode( 'zip_widget', array( $this, 'render_widget_shortcode' ) );

		// Add the express button
		// TODO: Express checkout is not completed at this state
		// $this->_add_express_button_hook($WC_Zipmoney_Payment_Gateway_Config);

		// Init the widget scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'backend_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'frontend_scripts' ) );

		// Add the capture charge button and cancel charge button
		add_action( 'woocommerce_order_item_add_action_buttons', array( $this, 'action_add_charge_buttons' ) );

		// Add the authorised status for payment complete
		add_filter( 'woocommerce_valid_order_statuses_for_payment_complete', array( $this, 'filter_add_authorize_order_status_for_payment_complete' ) );

		// add the payment gateway hook to order total
		add_filter( 'woocommerce_available_payment_gateways', array( $this, 'process_available_payment_gateways_with_order_threshold' ) );

		// add the notification section on checkout page
		add_action( 'woocommerce_before_checkout_form', array( $this, 'add_zip_notification_section_on_checkout' ) );

		// add async to zip-widget js file
        add_filter( 'script_loader_tag', array( $this, 'add_async_to_script' ), 799, 3 );
    }

	public function add_zip_notification_section_on_checkout( $wccm_autocreate_account ) {
		include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/view/frontend/checkout_notification_section.php';
	}


	public function process_available_payment_gateways_with_order_threshold( $gateways ) {
		if ( isset( $gateways[ $this->WC_Zipmoney_Payment_Gateway->id ] ) == false ) {
			// if the zipmoney payment is not active, then we won't process anything
			return $gateways;
		}

		$minLimit = $this->WC_Zipmoney_Payment_Gateway->get_option( WC_Zipmoney_Payment_Gateway_Config::CONFIG_ORDER_THRESHOLD_MIN_TOTAL );
		if ( ! empty( $minLimit ) && is_numeric( $minLimit ) ) {
			if ( WC()->cart && WC()->cart->total < $minLimit ) {
				// if the cart total has less than min threshold, then we will hide the payment option
				unset( $gateways[ $this->WC_Zipmoney_Payment_Gateway->id ] );
			}
		}
		$maxLimit = $this->WC_Zipmoney_Payment_Gateway->get_option( WC_Zipmoney_Payment_Gateway_Config::CONFIG_ORDER_THRESHOLD_MAX_TOTAL );
		if ( ! empty( $maxLimit ) && is_numeric( $maxLimit ) ) {
			if ( WC()->cart && WC()->cart->total > $maxLimit ) {
				// if the cart total has exceeded the max threshold, then we will hide the payment option
				unset( $gateways[ $this->WC_Zipmoney_Payment_Gateway->id ] );
			}
		}

		return $gateways;
	}

	/**
	 * Added the authorize status for payment complete
	 *
	 * @param $statuses
	 * @param $instance
	 * @return array
	 */
	public function filter_add_authorize_order_status_for_payment_complete( $statuses ) {
		$statuses[] = str_replace( 'wc-', '', WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY );

		return $statuses;
	}

	/**
	 * Add the capture charge button to admin order page
	 *
	 * @param WC_Order $order
	 */
	public function action_add_charge_buttons( WC_Order $order ) {
		include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/view/backend/charge_buttons.php';
	}


	/**
	 * Add the widget hook
	 *
	 * @param WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config
	 */
	private function _add_widget_hook() {
		$settings = $this->_get_settings();

		foreach ( array(
			WC_Zipmoney_Payment_Gateway_Widget_Placement::TYPE_PRODUCT,
			WC_Zipmoney_Payment_Gateway_Widget_Placement::TYPE_CART,
		) as $type ) {
			$placement = WC_Zipmoney_Payment_Gateway_Widget_Placement::resolve( $type, $settings );

			if ( null === $placement ) {
				continue;
			}

			list( $hook, $priority ) = $placement;

			add_action( $hook, array( $this, 'render_widget_' . $type ), $priority );
		}
	}



	/**
	 * The saved gateway settings as a plain array.
	 *
	 * init_settings() runs in the gateway constructor, so this is populated by the time
	 * hooks are registered. The fallback covers a gateway instantiated some other way.
	 *
	 * Deliberately the raw array rather than Config::is_bool_config_by_key(): the resolver
	 * that reads it is pure by design, so that the placement rules can be tested without
	 * WordPress, and a pure function cannot ask WooCommerce for a form default. The cost is
	 * that "an absent checkbox counts as enabled" is stated twice — here through the form
	 * default, and in Widget_Placement::is_enabled() — and tests/unit pin the second.
	 *
	 * @return array
	 */
	private function _get_settings() {
		$settings = $this->WC_Zipmoney_Payment_Gateway->settings;

		if ( is_array( $settings ) && array() !== $settings ) {
			return $settings;
		}

		$settings = get_option( 'woocommerce_' . $this->WC_Zipmoney_Payment_Gateway->id . '_settings', array() );

		return is_array( $settings ) ? $settings : array();
	}


	/**
	 * TODO: Express checkout is disabled at this state. It will be implemented in the future
	 *
	 * Add express button hook
	 *
	 * @param WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config
	 */
	// private function _add_express_button_hook(WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config)
	// {
	// $config_is_express = $WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_IS_EXPRESS);
	// $config_display_widget = $WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET);
	//
	// Express in Customise template
	// if ($config_is_express) {
	// add_action('zipmoney_wc_render_widget_general', array($this, 'render_express_payment_button'), 12);
	// } else if ($config_display_widget) {
	// add_action('zipmoney_wc_render_widget_general', array($this, 'render_widget_general'), 10);
	// }
	// Express in product page
	// if ($config_is_express && $WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_IS_EXPRESS_PRODUCT_PAGE)) {
	// Express checkout on product page
	// add_action('woocommerce_after_add_to_cart_button', array($this, 'render_express_payment_button'));
	// } else if ($config_display_widget && $WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET_PRODUCT_PAGE)) {
	// The widget on product page
	// add_action('woocommerce_after_add_to_cart_button', array($this, 'render_widget_product'));
	// }
	// Express in cart page
	// if ($config_is_express && $WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_IS_EXPRESS_CART)) {
	// Express checkout on cart page
	// add_action('woocommerce_after_add_to_cart_button', array($this, 'render_widget_product'));
	// } else if ($config_display_widget && $WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key(WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET_CART)) {
	// The widget on cart page
	// add_action('woocommerce_proceed_to_checkout', array($this, 'render_widget_cart'), 20);
	// }
	// }

	/**
	 * Hooked renderer for the cart widget. Which hook and which priority is decided by
	 * WC_Zipmoney_Payment_Gateway_Widget_Placement::resolve() from the merchant's setting.
	 *
	 * @access public
	 */
	public function render_widget_cart() {
		echo $this->get_widget_html( WC_Zipmoney_Payment_Gateway_Widget_Placement::TYPE_CART );
	}


	/**
	 * The widget markup for the current request, or an empty string when there is nothing
	 * to render.
	 *
	 * Shared by the hooked renderers, the [zip_widget] shortcode and the cart block's
	 * server-side render, so all three agree on the element and, more importantly, on
	 * where the price comes from.
	 *
	 * @param string $type   Widget type, product or cart.
	 * @param string $origin  Who is printing it — Markup::ORIGIN_AUTO for the render
	 *                        hook, ORIGIN_MANUAL for the shortcode. The placement script
	 *                        moves and deduplicates only the automatic one.
	 * @return string
	 */
	public function get_widget_html( $type, $origin = WC_Zipmoney_Payment_Gateway_Widget_Markup::ORIGIN_AUTO ) {
		$price = $this->_get_widget_price( $type );

		if ( null === $price ) {
			return '';
		}

		$this->_enqueue_widget_js();

		return $this->root->ahead_of(
			$this->_root_markup(),
			WC_Zipmoney_Payment_Gateway_Widget_Markup::build(
				$type,
				array(
					'region' => $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config->get_widget_region(),
					'symbol' => get_woocommerce_currency_symbol(),
					'price'  => $price,
					'origin' => $origin,
				)
			)
		);
	}


	/**
	 * The price the widget quotes, taken from the store rather than from the page.
	 *
	 * Null means there is nothing to render: no product in context, or no cart.
	 *
	 * @param string $type Widget type, product or cart.
	 * @return string|float|null
	 */
	private function _get_widget_price( $type ) {
		if ( WC_Zipmoney_Payment_Gateway_Widget_Placement::TYPE_PRODUCT === $type ) {
			$product = wc_get_product();

			if ( ! $product ) {
				return null;
			}

			$raw = $product->get_price();

			// A variable product whose price meta is stale — a bulk import that wrote the
			// variations without re-syncing the parent — answers '' here while its
			// variations are priced. Without this the page renders no widget at all, and
			// no widget is also nothing for the variation script to re-price: it updates
			// the element the server printed, it never creates one. The cheapest variation
			// is what the page itself shows as "from", so it is also what to quote.
			if ( ( null === $raw || '' === $raw ) && $product->is_type( 'variable' ) ) {
				$prices = $product->get_variation_prices();
				$raw    = self::cheapest_variation_price( isset( $prices['price'] ) ? $prices['price'] : array() );

				if ( null === $raw ) {
					return null;
				}

				return wc_get_price_to_display( $product, array( 'price' => $raw ) );
			}

			// wc_get_price_to_display(), not get_price(), because the variation payload
			// hands the browser display_price — which is this same function applied to
			// the variation. Using the raw price here would mean the widget quotes one
			// basis on load and another after the shopper picks a variation, on any store
			// that displays prices tax-inclusive. On a store without tax the two agree.
			return self::quotable_price( $raw, wc_get_price_to_display( $product ) );
		}

		if ( WC_Zipmoney_Payment_Gateway_Widget_Placement::TYPE_CART === $type ) {
			$cart = WC()->cart;

			if ( ! $cart ) {
				return null;
			}

			// The cart's own total, not a sum assembled here. The sum this replaces —
			// contents plus shipping plus tax — left out fees, so a store charging a
			// surcharge quoted the shopper less than it was about to take. It also
			// disagreed with two things that were already canonical: the order threshold
			// that decides whether Zip is offered at all reads $cart->total, and the cart
			// block reads the Store API's grand total, which includes fees. Three bases
			// for one number, and the widget had the only one that was wrong.
			return is_numeric( $cart->get_total( 'edit' ) )
				? (float) $cart->get_total( 'edit' )
				: null;
		}

		return null;
	}

	/**
	 * The lowest usable price among a variable product's variations, or null when none of
	 * them carries one.
	 *
	 * Pure, and kept apart from the WooCommerce calls above for the same reason
	 * quotable_price() is: the rule is what is worth pinning. Variations with no price are
	 * skipped rather than read as zero — WooCommerce leaves the key in place with an empty
	 * value, and treating that as free would quote nothing to the shopper as if it were
	 * something.
	 *
	 * @param array $prices Variation id => price, as WC_Product_Variable::get_variation_prices() returns.
	 * @return float|null
	 */
	public static function cheapest_variation_price( $prices ) {
		$usable = array();

		foreach ( (array) $prices as $price ) {
			if ( is_scalar( $price ) && '' !== $price && is_numeric( $price ) ) {
				$usable[] = (float) $price;
			}
		}

		return $usable ? min( $usable ) : null;
	}


	/**
	 * The price to quote for a product, or null when there is nothing to quote.
	 *
	 * Pure, and kept apart from the WooCommerce calls above so the rule itself can be
	 * tested: a product with no price at all — "price on request", a half-finished import —
	 * must not be quoted. wc_get_price_to_display() answers 0.0 for it, because the empty
	 * string is cast on the way in, and the shopper would be offered four instalments of
	 * nothing. A real zero is a price and is quoted.
	 *
	 * @param string|float|null $raw     What the product stores, WC_Product::get_price().
	 * @param string|float      $display What wc_get_price_to_display() made of it.
	 * @return string|float|null
	 */
	public static function quotable_price( $raw, $display ) {
		if ( null === $raw || '' === $raw ) {
			return null;
		}

		return $display;
	}


	/**
	 * Add the banner hook
	 *
	 * @param WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config
	 */
	private function _add_banner_hook( WC_Zipmoney_Payment_Gateway_Config $WC_Zipmoney_Payment_Gateway_Config ) {
		// Banners
		if ( $WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key( WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_BANNERS ) ) {
			// if the display banner is enabled
			if ( $WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key( WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_BANNER_SHOP ) ) {
				add_action( 'woocommerce_before_main_content', array( $this, 'render_banner_shop' ) );
			}

			if ( $WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key( WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_BANNER_PRODUCT_PAGE ) ) {
				add_action( 'woocommerce_before_main_content', array( $this, 'render_banner_product_page' ) );
			}

			if ( $WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key( WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_BANNER_CATEGORY ) ) {
				add_action( 'woocommerce_before_main_content', array( $this, 'render_banner_category' ) );
			}

			if ( $WC_Zipmoney_Payment_Gateway_Config->is_bool_config_by_key( WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_BANNER_CART ) ) {
				add_action( 'woocommerce_before_main_content', array( $this, 'render_banner_cart' ) );
			}
		}
	}


	/**
	 * Outputs style used for ZipMoney Payment admin section
	 */
	public function backend_scripts() {
		wp_register_style(
			'wc-zipmoney-style-admin',
			esc_url( plugins_url( 'assets/css/woocommerce-zipmoney-payment-admin.css', dirname( __FILE__ ) ) ),
			array(),
			WC_Zipmoney_Payment_Gateway::getAdminCSSVersion(),
			'all'
		);
		wp_enqueue_style( 'wc-zipmoney-style-admin' );
		$time = date( 'YmdHi' );
		wp_enqueue_script( 'zipmoney_admin_js', plugin_dir_url( __dir__ ) . 'assets/js/admin_options.js?v=' . $time, __FILE__ );
	}

	/**
	 * add defer to zip-widget.min.js
	 */
	public function add_defer_to_script( $tag, $handle, $src ) {
		// You can use this to make it work as below for a specific script
		if ( 'wc-zipmoney-widget-js' === $handle ) {
			$tag = '<script type="text/javascript" defer src="' . esc_url( $src ) . '"></script>';
		}
		return $tag;
	}

    /**
     * add async to zip-widget.min.js
     */
    public function add_async_to_script( $tag, $handle, $src ) {
        // You can use this to make it work as below for a specific script
        if ( 'wc-zipmoney-widget-js' === $handle ) {
            $tag = '<script type="text/javascript" async src="' . esc_url( $src ) . '"></script>';
        }
        return $tag;
    }

	/**
	 * Register style and scripts required
	 */
	public function frontend_scripts() {
		wp_register_style(
			'wc-zipmoney-style',
			esc_url( plugins_url( 'assets/css/woocommerce-zipmoney-payment-front.css', dirname( __FILE__ ) ) ),
			array(),
			WC_Zipmoney_Payment_Gateway::getFrontendCSSVersion(),
			'all'
		);
		wp_enqueue_style( 'wc-zipmoney-style' );

		wp_register_script( 'wc-zipmoney-script', esc_url( plugins_url( 'assets/js/woocommerce-zipmoney-payment-front.js', dirname( __FILE__ ) ) ), array( 'thickbox' ), '2.0.4', true );
		wp_enqueue_script( 'wc-zipmoney-script' );

		wp_register_script( 'wc-zipmoney-script-order-button', esc_url( plugins_url( 'assets/js/zip_order_button.js', dirname( __FILE__ ) ) ), array( 'thickbox' ), '2.0.4', true );
		wp_enqueue_script( 'wc-zipmoney-script-order-button' );

		$WC_Zipmoney_Payment_Gateway_Config = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config;

		// Registered on every front-end page, enqueued only by the code that
		// actually prints zm-* markup. Keeping the two together means a new
		// widget location cannot forget to ask for the script.
		//
		// Not in the footer: the bundle is loaded with async, so the block checkout
		// script and the inline _collectWidgetsEl call in the gateway description
		// would both run before the Zip global exists. Enqueued from a render hook
		// it lands in the footer anyway, which is harmless there — the markup is
		// already in the document and the bundle collects it on load.
		wp_register_script( 'wc-zipmoney-widget-js', 'https://static.zip.co/lib/js/zm-widget-js/dist/zip-widget.min.js', array(), '2.0.5', false );

		if ( is_checkout() ) {
			// Checkout renders the merchant root element and the gateway description
			// unconditionally, and the block checkout receives that description over
			// the Store API, where none of the render hooks below run.
			$this->_enqueue_widget_js();
		}

		if ( $WC_Zipmoney_Payment_Gateway_Config->is_it_iframe_flow() ) {
			// The third argument is $deps, so the version used to be passed as a
			// dependency and `true` as the version.
			wp_register_script( 'wc-zipmoney-checkout-js', 'https://static.zip.co/checkout/checkout-v1.min.js', array(), '1.0.0', true );
			wp_enqueue_script( 'wc-zipmoney-checkout-js' );
		}
		wp_enqueue_script( 'wc-zipmoney-js' );

		$this->_enqueue_placement_script();
		$this->_enqueue_cart_block_widget();
		$this->_lead_block_pages_with_the_root();
	}


	/**
	 * Whether this request is the cart page rendered by the Cart Block.
	 *
	 * Asked from both directions — the block path needs it true, the classic placement
	 * script needs it false — so it lives in one place rather than as two spellings of the
	 * same condition sixty lines apart.
	 *
	 * @return bool
	 */
	private function _is_block_cart() {
		return function_exists( 'has_block' ) && has_block( 'woocommerce/cart' );
	}


	/**
	 * Whether this request is the checkout page rendered by the Checkout Block.
	 *
	 * @return bool
	 */
	private function _is_block_checkout() {
		return function_exists( 'has_block' ) && has_block( 'woocommerce/checkout' );
	}


	/**
	 * On a page whose Zip markup is built in the browser, print the root inside the content.
	 *
	 * Those pages put their zm-* elements in the middle of the document, and the root
	 * printed at wp_footer on a theme that does not call wp_body_open() lands below them.
	 * The bundle takes the first element carrying zm-merchant, zm-region or zm-widget as its
	 * configuration, and a selector list matches in document order, so it reads one of those
	 * elements instead — none of which has the merchant key — and serves the generic asset.
	 *
	 * Measured on both. A block cart asks Zip for global.json rather than
	 * assets?merchantid. A block checkout goes further and reads the literal "default"
	 * merchant, because its first element is the Learn More link from the payment method
	 * description: the block fetches that description through the Store API, a separate
	 * request, so the copy that reaches the page never passed the root in front of it.
	 *
	 * The content is above anything either block mounts, so printing the root there settles
	 * the order on the server rather than inside a React tree. Where wp_body_open did fire
	 * the root is already out and this adds nothing. Not tied to the marketing widget being
	 * switched on: the checkout markup appears either way.
	 */
	private function _lead_block_pages_with_the_root() {
		$block_page = ( is_cart() && $this->_is_block_cart() )
			|| ( is_checkout() && $this->_is_block_checkout() );

		if ( ! $block_page ) {
			return;
		}

		add_filter( 'the_content', array( $this, 'prepend_root_to_content' ), 0 );
	}


	/**
	 * The cart widget for the Cart Block.
	 *
	 * The classic cart hook never fires on a block cart page, so without this there is no
	 * widget there at all. The block fills WooCommerce's ExperimentalOrderMeta slot and
	 * takes its price from the wc/store/cart data store.
	 */
	private function _enqueue_cart_block_widget() {
		if ( ! is_cart() || ! $this->_is_block_cart() ) {
			return;
		}

		$settings = $this->_get_settings();
		$type     = WC_Zipmoney_Payment_Gateway_Widget_Placement::TYPE_CART;

		if ( ! WC_Zipmoney_Payment_Gateway_Widget_Placement::is_enabled( $type, $settings ) ) {
			return;
		}

		$asset_path   = WOOCOMMERCE_GATEWAY_ZIPMONEY_PATH . '/build/cart-widget.asset.php';
		$version      = WOOCOMMERCE_GATEWAY_ZIPMONEY_VERSION;
		$dependencies = array();

		if ( file_exists( $asset_path ) ) {
			$asset        = require $asset_path;
			$version      = is_array( $asset ) && isset( $asset['version'] ) ? $asset['version'] : $version;
			$dependencies = is_array( $asset ) && isset( $asset['dependencies'] ) ? $asset['dependencies'] : $dependencies;
		}

		wp_register_script(
			'wc-zipmoney-cart-widget-block',
			WOOCOMMERCE_GATEWAY_ZIPMONEY_URL . '/build/cart-widget.js',
			$dependencies,
			$version,
			true
		);
		wp_add_inline_script(
			'wc-zipmoney-cart-widget-block',
			'window.zipCartWidget = ' . wp_json_encode(
				array(
					// The element's shape comes from the same builder the classic pages use,
					// so the block does not describe it a second time.
					'attributes' => WC_Zipmoney_Payment_Gateway_Widget_Markup::skeleton(
						$type,
						(string) $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config->get_widget_region()
					),
					// The block owns its node, so the selector cannot be honoured by moving
					// it afterwards the way the classic pages do. The block renders itself
					// into the merchant's element instead, and falls back to the order
					// summary slot when the selector matches nothing.
					'selector'   => WC_Zipmoney_Payment_Gateway_Widget_Placement::get_selector( $type, $settings ),
				)
			) . ';',
			'before'
		);
		wp_enqueue_script( 'wc-zipmoney-cart-widget-block' );

		// No render hook runs on this page, so the bundle has to be asked for here.
		$this->_enqueue_widget_js();
	}


	/**
	 * The page content with the configuration root in front of it, when the root is owed.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function prepend_root_to_content( $content ) {
		return $this->root->ahead_of( $this->_root_markup(), $content );
	}


	/**
	 * The placement helper: moves the rendered widget in front of a configured selector
	 * and follows variation price changes. Only enqueued when it has something to do.
	 */
	private function _enqueue_placement_script() {
		if ( is_product() ) {
			$type = WC_Zipmoney_Payment_Gateway_Widget_Placement::TYPE_PRODUCT;
		} elseif ( is_cart() ) {
			// On a block cart the widget is a React node owned by the block; moving it
			// would fight the renderer. Placement there is the block's job.
			if ( $this->_is_block_cart() ) {
				return;
			}

			$type = WC_Zipmoney_Payment_Gateway_Widget_Placement::TYPE_CART;
		} else {
			return;
		}

		$settings = $this->_get_settings();

		if ( ! WC_Zipmoney_Payment_Gateway_Widget_Placement::is_enabled( $type, $settings ) ) {
			return;
		}

		$selector = WC_Zipmoney_Payment_Gateway_Widget_Placement::get_selector( $type, $settings );
		$product  = WC_Zipmoney_Payment_Gateway_Widget_Placement::TYPE_PRODUCT === $type;

		// Nothing to move and no variations to follow.
		if ( '' === $selector && ! $product ) {
			return;
		}

		// Same source as the rendered attribute: reset_data restores this value when the
		// shopper clears the variation, and a different basis here would be a silent
		// mismatch with what the page loaded with.
		$price = $product ? $this->_get_widget_price( $type ) : null;

		wp_register_script(
			'wc-zipmoney-widget-placement',
			esc_url( plugins_url( 'assets/js/woocommerce-zipmoney-widget-placement.js', dirname( __FILE__ ) ) ),
			array( 'jquery' ),
			WOOCOMMERCE_GATEWAY_ZIPMONEY_VERSION,
			true
		);
		wp_add_inline_script(
			'wc-zipmoney-widget-placement',
			'window.zipWidgetPlacement = ' . wp_json_encode(
				array(
					'type'     => $type,
					'selector' => $selector,
					'price'    => $price,
				)
			) . ';',
			'before'
		);
		wp_enqueue_script( 'wc-zipmoney-widget-placement' );
	}


	/**
	 * Enqueue the Zip widget bundle for the current request.
	 *
	 * Called from the code paths that print zm-* markup, so "is the script
	 * needed here" is answered by the rendering itself rather than by a second
	 * copy of the display rules.
	 */
	private function _enqueue_widget_js() {
		wp_enqueue_script( 'wc-zipmoney-widget-js' );
	}


	/**
	 * Hooked renderer for the product widget. Which hook and which priority is decided by
	 * WC_Zipmoney_Payment_Gateway_Widget_Placement::resolve() from the merchant's setting.
	 *
	 * @access public
	 */
	public function render_widget_product() {
		echo $this->get_widget_html( WC_Zipmoney_Payment_Gateway_Widget_Placement::TYPE_PRODUCT );
	}


	/**
	 * [zip_widget] — lets the merchant place the widget anywhere a shortcode is rendered:
	 * an overridden theme template, the product description, or a page builder.
	 *
	 * @param array $atts type=product|cart. Defaults to the current page.
	 * @return string
	 */
	public function render_widget_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'type' => '' ), $atts, 'zip_widget' );
		$type = is_string( $atts['type'] ) ? strtolower( trim( $atts['type'] ) ) : '';

		if ( '' === $type ) {
			$type = is_cart()
				? WC_Zipmoney_Payment_Gateway_Widget_Placement::TYPE_CART
				: WC_Zipmoney_Payment_Gateway_Widget_Placement::TYPE_PRODUCT;
		}

		// The display checkbox stays the authority: a shortcode must not bring back a
		// widget the merchant switched off.
		if ( ! WC_Zipmoney_Payment_Gateway_Widget_Placement::is_enabled( $type, $this->_get_settings() ) ) {
			return '';
		}

		return $this->get_widget_html( $type, WC_Zipmoney_Payment_Gateway_Widget_Markup::ORIGIN_MANUAL );
	}


	/**
	 * Renders the widget below add to cart / proceed to checkout button in product or cart pages.
	 *
	 * @access public
	 */
	public function render_widget_general() {
        $this->_enqueue_widget_js();
        $region    = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config->get_widget_region();
		echo $this->root->ahead_of(
			$this->_root_markup(),
			'<div class="widget-product-cart" zm-region="' . esc_attr( $region ) . '"  data-zm-asset="productwidget" data-zm-widget="popup"  data-zm-popup-asset="termsdialog"></div>'
		);
	}

	/**
	 * Renders the express payment button.
	 *
	 * @access public
	 */
	public function render_express_payment_button() {
		include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/view/frontend/express_payment_button.php';
	}

	/**
	 * Renders the banner in the shop page.
	 *
	 * @access public
	 */
	public function render_banner_shop() {
		if ( is_shop() ) {
			$this->_render_banner();
		}
	}

	/**
	 * Renders the banner in the cart page.
	 *
	 * @access public
	 */
	public function render_banner_cart() {
		if ( is_cart() ) {
			$this->_render_banner();
		}
	}

	/**
	 * Renders the banner in the product page.
	 *
	 * @access public
	 */
	public function render_banner_product_page() {
		if ( is_product() ) {
			$this->_render_banner();
		}
	}

	/**
	 * Renders the banner in the category page.
	 *
	 * @access public
	 */
	public function render_banner_category() {
		if ( is_product_category() ) {
			$this->_render_banner();
		}
	}

	/**
	 * Renders the widget below add to cart / proceed to checkout button in product or cart pages.
	 */
	public function render_tagline() {
        $this->_enqueue_widget_js();
        echo $this->root->ahead_of(
            $this->_root_markup(),
            '<div id="zip-tagline" data-zm-widget="tagline"  data-zm-info="true"></div>'
        );
	}


	/**
	 * Renders the banner across the shop, cart, product, category pages.
	 *
	 * @access private
	 */
	private function _render_banner() {
        $this->_enqueue_widget_js();
        $region    = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config->get_widget_region();
        echo $this->root->ahead_of(
            $this->_root_markup(),
            '<div class="zipmoney-strip-banner" zm-region="' . esc_attr( $region ) . '" zm-asset="stripbanner"   zm-widget="popup"  zm-popup-asset="termsdialog" ></div>'
        );
    }

	/**
	 * The configuration root: the element the Zip bundle reads the merchant key, the
	 * environment, the region and the price limits from.
	 *
	 * Two things about it are not visible from the code. It has to come before any widget
	 * markup, because the bundle takes the first element in the document carrying
	 * zm-merchant, zm-region or zm-widget as its configuration — printed later, a widget is
	 * read as one, and the bundle then asks Zip for assets with an undefined merchant and
	 * quietly falls back to a generic one. And it is printed on every front-end page rather
	 * than only where a widget renders, because at wp_body_open nothing yet knows whether
	 * Zip will appear here; without the bundle the element is inert.
	 */
	public function render_root_el() {
		echo $this->root->claim( $this->_root_markup() );
	}


	/**
	 * The root element for this request, or an empty string when there is no use for one.
	 *
	 * A shop with Zip switched off has none, and printing it there is how "on every
	 * front-end page" turns into "on every page of every shop".
	 *
	 * @return string
	 */
	private function _root_markup() {
		if ( 'yes' !== $this->WC_Zipmoney_Payment_Gateway->enabled ) {
			return '';
		}

		$config = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config;

		return WC_Zipmoney_Payment_Gateway_Widget_Markup::build_root(
			array(
				'merchant'       => $config->get_merchant_public_key(),
				'environment'    => $config->get_environment(),
				'region'         => $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config->get_widget_region(),
				'price_min'      => $this->WC_Zipmoney_Payment_Gateway->get_option( WC_Zipmoney_Payment_Gateway_Config::CONFIG_ORDER_THRESHOLD_MIN_TOTAL ),
				'price_max'      => $this->WC_Zipmoney_Payment_Gateway->get_option( WC_Zipmoney_Payment_Gateway_Config::CONFIG_ORDER_THRESHOLD_MAX_TOTAL ),
				'display_inline' => $this->isDisplayInlineWidget(),
				'language'       => WC_Zipmoney_Payment_Gateway_Widget_Markup::language_from_locale( get_locale() ),
			)
		);
	}

    /**
	 * display product widget in line
	 */
	public function isDisplayInlineWidget() {
		$displayMode   = $this->WC_Zipmoney_Payment_Gateway->get_option( WC_Zipmoney_Payment_Gateway_Config::CONFIG_DISPLAY_WIDGET_MODE );
		$displayInline = 'false';
		if ( $displayMode == WC_Zipmoney_Payment_Gateway_Config::DISPLAY_INLINE ) {
			$displayInline = 'true';
		}
		return $displayInline;
	}
	/**
	 * Updated the method description text to include the Learn More link.
	 *
	 * @access public
	 * @param string $description , string $id
	 * @return string $description
	 */
	public function updateMethodDescription( $description, $id ) {
		if ( $id != $this->WC_Zipmoney_Payment_Gateway->id ) {
			return $description;
		}
		$this->_enqueue_widget_js();
		$description = $this->root->ahead_of(
			$this->_root_markup(),
			'<span zm-widget=\'inline\' zm-asset=\'checkoutdescription\'></span> <a  id="zipmoney-learn-more" class="zip-hover"  zm-widget="popup"  zm-popup-asset="checkoutdialog">Learn More</a>'
		);
		// show Save Zip account option in checkout when tokenisation is enable and customer logged in
		if ( $this->WC_Zipmoney_Payment_Gateway->showSaveAccountInCheckout() ) {
			$checked = ( $this->WC_Zipmoney_Payment_Gateway->customerHasToken() ) ? 'checked' : '';
			session_status() === PHP_SESSION_ACTIVE ?: session_start();
			$_SESSION['saveZipAccount'] = $this->WC_Zipmoney_Payment_Gateway->customerHasToken();
			$description               .= '<label><input type="checkbox" id="saveZipAccount" ' . $checked . ' onchange="Check(this)" /><span>Save Zip Account for future purchases</span></label>';
		}
		$description .= '<script>if(window.$zmJs!==undefined) window.$zmJs._collectWidgetsEl(window.$zmJs);</script>';
		return $description;
	}


	/**
	 * Renders the place order button in the checkout page by using the checkout.js
	 *
	 * @access public
	 */
	public function order_button( $text ) {
		$is_iframe_checkout = $this->WC_Zipmoney_Payment_Gateway->WC_Zipmoney_Payment_Gateway_Config->is_it_iframe_flow();

		include plugin_dir_path( dirname( __FILE__ ) ) . 'includes/view/frontend/order_button.php';

		return $text;
	}
}
