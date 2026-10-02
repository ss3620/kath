<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar

namespace Revenue;

defined( 'ABSPATH' ) || exit;

use WC_DateTime;
use DateTimeZone;
use Exception;
use DateTime;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Contains Common Functions
 */
class Revenue_Functions {

	/**
	 * Store the cart product IDs.
	 *
	 * @var array|null
	 */
	private static $cart_product_ids = null;

	/**
	 * Store the cart product IDs.
	 *
	 * @var array|null
	 */
	public function __construct() {
		$this->register_hooks();
	}
	/**
	 * Get campaign shortcode tag
	 *
	 * @return string
	 */
	public function get_campaign_shortcode_tag() {
		return apply_filters( 'revenue_get_campaign_shortcode_tag', 'revenue_campaign' );
	}
	/**
	 * Get revenue admin menu position
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_admin_menu_position() {
		return apply_filters( 'revenue_menu_position', '58' );
	}



	/**
	 * Is user can see revenue admin menu
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function is_user_allowed_to_revenue_dashboard() {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		return apply_filters( 'revenue_show_dashboard', $has_permission );
	}

	/**
	 * Get revenue admin menu slug
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_admin_menu_slug() {
		return apply_filters( 'revenue_menu_slug', 'revenue' );
	}

	/**
	 * Get revenue admin menu slug
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_admin_menu_title() {
		return apply_filters( 'revenue_menu_title', __( 'WowRevenue', 'revenue' ) );
	}

	/**
	 * Get revenue admin menu slug
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function is_whitelabel_enabled() {
		return 'yes' === apply_filters( 'revenue_whitelabel_status', 'no' );
	}
	/**
	 * Get revenue campaign types
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_types() {
		$types = array(
			'normal_discount'            => _x( 'Normal Discount', 'Campaign Types', 'revenue' ),
			'bundle_discount'            => _x( 'Bundle Discount', 'Campaign Types', 'revenue' ),
			'volume_discount'            => _x( 'Volume Discount', 'Campaign Types', 'revenue' ),
			'buy_x_get_y'                => _x( 'Buy X Get Y Discount', 'Campaign Types', 'revenue' ),
			'free_shipping_bar'          => _x( 'Free Shipping', 'Campaign Types', 'revenue' ),
			'stock_scarcity'             => _x( 'Stock Scarcity', 'Campaign Types', 'revenue' ),
			'countdown_timer'            => _x( 'Countdown Timer', 'Campaign Types', 'revenue' ),
			'next_order_coupon'          => _x( 'Next Order Coupon', 'Campaign Types', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_types', $types );
	}

	/**
	 * Get campaign types implemented by the Free plugin.
	 *
	 * This list is the REST write boundary and must not be filterable.
	 *
	 * @return string[]
	 */
	public function get_free_campaign_types() {
		return array(
			'normal_discount',
			'bundle_discount',
			'volume_discount',
			'buy_x_get_y',
			'free_shipping_bar',
			'stock_scarcity',
			'countdown_timer',
			'next_order_coupon',
		);
	}

	/**
	 * Check whether a campaign type is implemented by the Free plugin.
	 *
	 * @param string $type Campaign type.
	 * @return bool
	 */
	public function is_free_campaign_type( $type ) {
		return in_array( $type, $this->get_free_campaign_types(), true );
	}
	/**
	 * Get campaign type name by key
	 *
	 * @since 1.0.0
	 * @param string $campaign_key The campaign type key (e.g., 'normal_discount')
	 * @return string|null Campaign type name or null if not found
	 */
	public function get_campaign_type_name( $campaign_key ) {

		if ( 'buy_x_get_y' === $campaign_key ) {
			return _x( 'Buy X Get Y', 'Campaign Types', 'revenue' );
		}

		$types = $this->get_campaign_types();

		return isset( $types[ $campaign_key ] ) ? $types[ $campaign_key ] : null;
	}
	/**
	 * Get revenue campaign statuses
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_statuses() {
		$types = array(
			'draft'   => _x( 'Draft', 'Campaign Statuses', 'revenue' ),
			'publish' => _x( 'Publish', 'Campaign Statuses', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_statuses', $types );
	}
	/**
	 * Get revenue trigger types
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_trigger_types() {
		$types = array(
			'all_products' => _x( 'All Products', 'Trigger Types', 'revenue' ),
			'products'     => _x( 'Specific Products', 'Trigger Types', 'revenue' ),
			'category'     => _x( 'Specific Category', 'Trigger Types', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_trigger_types', $types );
	}
	/**
	 * Get revenue display types
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_display_types() {
		$types = array(
			'inpage'   => _x( 'In Page', 'Display Types', 'revenue' ),
			'popup'    => _x( 'Pop-up', 'Display Types', 'revenue' ),
			'floating' => _x( 'Floating', 'Display Types', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_display_types', $types );
	}
	/**
	 * Get revenue display types
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_floating_positions() {
		$types = array(
			'top-left'     => _x( 'Top Left', 'Floating positions', 'revenue' ),
			'top-right'    => _x( 'Top Right', 'Floating positions', 'revenue' ),
			'bottom-left'  => _x( 'Bottom Left', 'Floating positions', 'revenue' ),
			'bottom-right' => _x( 'Bottom Right', 'Floating positions', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_display_types', $types );
	}


	/**
	 * Get revenue campaign placements
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_placements() {

		$placement_options = array(
			'normal_discount'            => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
			),
			'bundle_discount'            => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
			),
			'volume_discount'            => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
			),

			'buy_x_get_y'                => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
			),

			'mix_match'                  => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
			),
			'frequently_bought_together' => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
			),
			'double_order'               => array(
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
			),
			'spending_goal'              => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'all_page'      => _x( 'Entire Site', 'Page Type', 'revenue' ),
			),
			'free_shipping_bar'          => array(
				'product_page'  => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'     => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'checkout_page' => _x( 'Checkout Page', 'Page Type', 'revenue' ),
				'all_page'      => _x( 'Entire Site', 'Page Type', 'revenue' ),
			),
			'stock_scarcity'             => array(
				'product_page' => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'    => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'shop_page'    => _x( 'Shop Page', 'Page Type', 'revenue' ),
			),
			'countdown_timer'            => array(
				'product_page' => _x( 'Product Page', 'Page Type', 'revenue' ),
				'cart_page'    => _x( 'Cart Page', 'Page Type', 'revenue' ),
				'all_page'     => _x( 'Entire Site', 'Page Type', 'revenue' ),
				'shop_page'    => _x( 'Shop Page', 'Page Type', 'revenue' ),
			),
			'next_order_coupon'          => array(
				'thankyou_page' => _x( 'Thank You Page', 'Page Type', 'revenue' ),
				'to_email'      => _x( 'To Email', 'Page Type', 'revenue' ),
				'my_account'    => _x( 'My Account', 'Page Type', 'revenue' ),
			),
		);
		return apply_filters( 'revenue_campaign_placements', $placement_options );
	}
	/**
	 * Get revenuex display in page campaignions
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_inpage_positions() {
		// Normal Discounts.
		$product_page_positions = array(
			'none'                         => __( 'None (Select if using shortcode)', 'revenue' ),
			'before_add_to_cart_form'      => _x( 'Before add to cart Button', 'Display Positions', 'revenue' ),
			'after_add_to_cart_form'       => _x( 'After add to cart Button', 'Display Positions', 'revenue' ),
			'after_single_product_summary' => _x( 'After single product summary', 'Display Positions', 'revenue' ),
			'before_single_product'        => _x( 'Before single product', 'Stock Scarcity Positions', 'revenue' ),
			'after_single_product'         => _x( 'After single product', 'Stock Scarcity Positions', 'revenue' ),
		);

		$theme = get_option( 'template' );

		if ( 'astra' === $theme ) {
			unset( $product_page_positions['after_single_product_summary'] );
		}

		$cart_page_positions = array(
			'none'                => __( 'None (Select if using shortcode)', 'revenue' ),
			'before_cart_table'   => _x( 'Before cart table', 'Display Positions', 'revenue' ),
			'before_cart'         => _x( 'Before cart', 'Display Positions', 'revenue' ),
			'after_cart_table'    => _x( 'After cart table', 'Display Positions', 'revenue' ),
			'after_cart'          => _x( 'After cart', 'Display Positions', 'revenue' ),
			'before_cart_totals'  => _x( 'Before Cart Total', 'Display Positions', 'revenue' ),
			'after_cart_totals'   => _x( 'After Cart Total', 'Display Positions', 'revenue' ),
			'proceed_to_checkout' => _x( 'Proceed to Checkout', 'Display Positions', 'revenue' ),

		);
		$checkout_page_positions = array(
			'none'                         => __( 'None (Select if using shortcode)', 'revenue' ),
			'before_checkout_form'         => _x( 'Before checkout form', 'Display Positions', 'revenue' ),
			'before_checkout_billing_form' => _x( 'Before checkout billing form', 'Display Positions', 'revenue' ),
			'after_checkout_billing_form'  => _x( 'After checkout billing form', 'Display Positions', 'revenue' ),
			'checkout_before_order_review' => _x( 'Before order review', 'Display Positions', 'revenue' ),
			'review_order_before_payment'  => _x( 'Before payment', 'Display Positions', 'revenue' ),
			'review_order_after_payment'   => _x( 'After payment', 'Display Positions', 'revenue' ),
			'after_checkout_form'          => _x( 'After checkout form', 'Display Positions', 'revenue' ),

		);

		$thankyou_page_positions = array(
			'none'            => __( 'None (Select if using shortcode)', 'revenue' ),
			'before_thankyou' => _x( 'Before Thank You Message', 'Display Positions', 'revenue' ),
			'thankyou'        => _x( 'After Thank You Message', 'Display Positions', 'revenue' ),
		);
		$shop_page_positions     = array(
			'none'                       => __( 'None (Select if using shortcode)', 'revenue' ),
			'after_shop_loop_item_title' => _x( 'Below the Product Title', 'Display Positions', 'revenue' ),
			'shop_loop_item_title'       => _x( 'Before the Product Title', 'Display Positions', 'revenue' ),
		);

		// Email positions.
		$email_positions = array(
			'email_before_order_table' => _x( 'Before Order Table', 'Display Positions', 'revenue' ),
			'email_after_order_table'  => _x( 'After Order Table', 'Display Positions', 'revenue' ),
			'email_after_order_meta'   => _x( 'After Order Meta', 'Display Positions', 'revenue' ),
			'email_after_order_items'  => _x( 'After Order Items', 'Display Positions', 'revenue' ),
		);

		// my account positions.
		$my_account_positions = array(
			'my_account_before_order_table' => _x( 'Bottom of the Page', 'Display Positions', 'revenue' ),
		);
		$types                = array(
			'product_page'  => $product_page_positions,
			'cart_page'     => $cart_page_positions,
			'checkout_page' => $checkout_page_positions,
			'thankyou_page' => $thankyou_page_positions,
			'shop_page'     => $shop_page_positions,
			'to_email'      => $email_positions,
			'my_account'    => $my_account_positions,
		);

		return apply_filters( 'revenue_campaign_in_page_display_positions', $types );
	}

	/**
	 * Register hooks
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_hooks() {
		// Registered once here rather than inside get_allowed_tag(), which is called on
		// every render; add_filter dedupes the same callback anyway, but the widened
		// safe_style_css list still applies to every wp_kses call in the request, ours
		// and everyone else's, for as long as the process lives.
		add_filter( 'safe_style_css', array( $this, 'allow_display_in_kses' ) );

		if ( 'astra' === get_option( 'template' ) ) {
			add_action(
				'astra_woo_single_title_after',
				function () {
					do_action( 'rvex_below_the_product_title' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'rvex_below_the_product_title' uses the plugin's 'rvex' prefix.
				}
			);
		} else {
			add_action(
				'woocommerce_single_product_summary',
				function () {
					do_action( 'rvex_below_the_product_title' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'rvex_below_the_product_title' uses the plugin's 'rvex' prefix.
				},
				10
			);
		}
		if ( 'astra' === get_option( 'template' ) ) {
			add_action(
				'astra_woo_single_price_after',
				function () {
					do_action( 'rvex_below_the_product_price' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'rvex_below_the_product_price' uses the plugin's 'rvex' prefix.
				},
				11
			);
		} else {
			add_action(
				'woocommerce_single_product_summary',
				function () {
					do_action( 'rvex_below_the_product_price' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'rvex_below_the_product_price' uses the plugin's 'rvex' prefix.
				},
				11
			);
		}

		add_action(
			'woocommerce_account_content',
			function () {
				do_action( 'rvex_above_my_account' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'rvex_above_my_account' uses the plugin's 'rvex' prefix.
			},
			5
		);
		add_action(
			'woocommerce_account_content',
			function () {
				do_action( 'rvex_below_my_account' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- 'rvex_below_my_account' uses the plugin's 'rvex' prefix.
			},
			25
		);
	}



	/**
	 * Get revenue animated add to cart animation types
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_popup_animation_types() {
		$types = array(
			// ''        => __( 'None', 'revenue' ),
			'fade'    => __( 'Fade', 'revenue' ),
			'slide'   => __( 'Slide', 'revenue' ),
			'zoom'    => __( 'Zoom', 'revenue' ),
			'bounce'  => __( 'Bounce', 'revenue' ),
			'shake'   => __( 'Shake', 'revenue' ),
			'swing'   => __( 'Swing', 'revenue' ),
			'wobble'  => __( 'Wobble', 'revenue' ),
			'vibrate' => __( 'Flash', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_popup_animation_types', $types );
	}
	/**
	 * Get revenue animated add to cart animation types
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_animated_add_to_cart_animation_types() {
		$types = array(

			'wobble' => __( 'Wobble', 'revenue' ),
			'shake'  => __( 'Shake', 'revenue' ),
			'zoom'   => __( 'Zoom', 'revenue' ),
			'pulse'  => __( 'Pulse', 'revenue' ),
		);

		return apply_filters( 'revenue_campaign_animated_add_to_cart_animation_types', $types );
	}

	/**
	 * Get campaign data
	 *
	 * @param string $id ID.
	 * @param string $context Context.
	 * @since 1.0.0
	 * @return array
	 */
	public function get_campaign_data( $id, $context = 'raw' ) {
		if ( 'raw' === $context ) {
			$GLOBALS['revenue_campaign_data'][ $id ] = $this->read_campaign_data( $id, false, $context );
		}
		if ( ! isset( $GLOBALS['revenue_campaign_data'][ $id ] ) ) {
			if ( ! isset( $GLOBALS['revenue_campaign_data'] ) ) {
				$GLOBALS['revenue_campaign_data'] = array();
			}
			if ( ! is_array( $GLOBALS['revenue_campaign_data'] ) ) {
				$GLOBALS['revenue_campaign_data'] = array();
			}

			$GLOBALS['revenue_campaign_data'][ $id ] = $this->read_campaign_data( $id, false, $context );

			if ( ! $GLOBALS['revenue_campaign_data'][ $id ] ) {
				unset( $GLOBALS['revenue_campaign_data'][ $id ] );
			}
		}

		return $this->get_var( $GLOBALS['revenue_campaign_data'][ $id ] ) ?? false;
	}

	/**
	 * Retrieve a list of campaigns based on provided arguments, with caching.
	 *
	 * This method queries the `revenue_campaigns` table to fetch campaign IDs based on the
	 * specified display type, position, page, and exclusion list. It uses caching to store
	 * the results and improve performance by reducing the number of database queries.
	 *
	 * @param array $args {
	 *     Optional. Arguments to filter the campaigns.
	 *
	 *     @type string $page          The page where the campaign is displayed. Default is 'product_page'.
	 *     @type string $display_type  The display type of the campaign. Default is 'inpage'.
	 *     @type string $position      The position of the campaign on the page. Default is 'before_add_to_cart_button'.
	 *     @type string $product_id    The product ID associated with the campaign. Default is an empty string.
	 *     @type array  $exclude       An array of campaign IDs to exclude from the results. Default is an empty array.
	 * }
	 *
	 * @return array An array of campaign IDs that match the specified criteria. Each item in the array is an associative array with the 'id' key.
	 */
	public function get_campaigns( $args ) {
		global $wpdb;

		// Parse arguments
		$args = wp_parse_args(
			$args,
			array(
				'page'          => 'product_page',
				// 'display_type' => 'inpage',
				// 'position'     => 'before_add_to_cart_button',
				'product_id'    => '',
				'exclude'       => array(), //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
				'trigger_type'  => 'all_products',
				'campaign_type' => '',
			)
		);

		if ( ! is_array( $args['exclude'] ) ) { //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			$args['exclude'] = array(); //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		}

		// Arguments as local variables (no extract()).
		$page          = $args['page'];
		$product_id    = $args['product_id'];
		$exclude       = $args['exclude'];
		$trigger_type  = $args['trigger_type'];
		$campaign_type = $args['campaign_type'];
		// Generate cache key.
		$cache_key = 'revenue_campaigns_' . md5( serialize( $args ) );

		// Attempt to get cached results.
		$res = wp_cache_get( $cache_key, 'revenue_campaigns' );

		if ( false === $res ) {
			// Prepare SQL query
			// Execute the query
			//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery

			if ( $args['campaign_type'] ) {
				$res = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}revenue_campaigns WHERE campaign_trigger_type= %s AND campaign_type=%s AND campaign_status='publish' AND id NOT IN (%s);",
						$trigger_type,
						$args['campaign_type'],
						implode( ',', $exclude )
					),
					ARRAY_A
				);
			} else {
				$res = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id FROM {$wpdb->prefix}revenue_campaigns WHERE campaign_trigger_type= %s AND campaign_status='publish' AND id NOT IN (%s);",
						$trigger_type,
						implode( ',', $exclude )
					),
					ARRAY_A
				);
			}

			// Cache the results
			wp_cache_set( $cache_key, $res, 'revenue_campaigns', 3600 ); // Cache for 1 hour
		}

		return $res;
	}
	public function get_countdown_timer_campaigns() {
		global $wpdb;

		// Define cache key and group
		// $cache_key   = 'revenue_countdown_campaigns';
		// $cache_group = 'revenue_countdown_campaigns';

		// Attempt to retrieve from cache
		// $res = wp_cache_get( 'revenue_countdown_campaigns', $cache_group );

		// if ( false === $res ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
			$query = $wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}revenue_campaigns WHERE campaign_type = %s AND campaign_status = %s",
				'countdown_timer',
				'publish'
			);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$res = $wpdb->get_results( $query, ARRAY_A );

			// Cache for 1 hour (3600 seconds)
			// wp_cache_set( 'revenue_countdown_campaigns', $res, $cache_group, 3600 );
		// }

		return $res;
	}



	/**
	 * Retrieves available campaigns for a given product based on various criteria.
	 *
	 * This function checks both product-level and category-level campaign
	 * inclusions and exclusions, then returns a list of eligible campaigns.
	 *
	 * @param int    $product_id    The ID of the product for which to retrieve campaigns.
	 * @param string $placement      The placement context for the campaigns.
	 * @param string $display_type   The type of display (e.g., inpage).
	 * @param string $position       The position of the display within the placement.
	 * @param bool   $with_ids      Whether to return an array with campaign IDs as well.
	 * @param bool   $is_cart       Whether to return campaigns for the cart page.
	 * @param string $campaign_type The type of campaign to retrieve.
	 *
	 * @return array An array of available campaigns. If $with_ids is true,
	 *               an array containing 'campaigns' and 'ids' is returned.
	 */
	public function get_available_campaigns(
		$product_id,
		$placement,
		$display_type = '',
		$position = '',
		$with_ids = false,
		$is_cart = false,
		$campaign_type = ''
	) {
		$revenue_bundle_id = get_option( 'revenue_bundle_parent_product_id', false );
		if ( $revenue_bundle_id && intval( $revenue_bundle_id ) === intval( $product_id ) ) {
			// avoid triggering product for Revenue Bundle dummy product.
			return array();
		}

		if ( 'inpage' === $display_type ) {
			$include_meta_key = "revx_camp_{$placement}_{$display_type}_{$position}_in";
			$exclude_meta_key = "revx_camp_{$placement}_{$display_type}_{$position}_ex";
		} else {
			$include_meta_key = "revx_camp_{$placement}_{$display_type}_in";
			$exclude_meta_key = "revx_camp_{$placement}_{$display_type}_ex";
		}

		$included = get_post_meta( $product_id, $include_meta_key );
		$excluded = get_post_meta( $product_id, $exclude_meta_key );

		if ( ! is_array( $included ) ) {
			$included = array();
		}
		if ( ! is_array( $excluded ) ) {
			$excluded = array();
		}

		$categories = revenue()->get_product_category_ids( $product_id );

		foreach ( $categories as $cat_id ) {
			$exclude_cat = get_term_meta( $cat_id, $exclude_meta_key );
			$include_cat = get_term_meta( $cat_id, $include_meta_key );
			$excluded    = array_merge( $excluded, $exclude_cat );
			$included    = array_merge( $included, $include_cat );
		}

		$args = array(
			'page'    => $placement,
			'exclude' => $excluded, //phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
		);

		if ( $campaign_type ) {
			$args['campaign_type'] = $campaign_type;
		}

		$all_product_campaigns = $this->get_campaigns( $args );

		foreach ( $all_product_campaigns as $camp ) {
			$camp_id = false;
			if ( is_object( $camp ) ) {
				$camp_id = $camp->id;
			} elseif ( is_array( $camp ) && isset( $camp['id'] ) ) {
				$camp_id = $camp['id'];
			}
			$included[] = $camp_id;
		}

		$included = array_diff( $included, $excluded );

		if ( 'all_page' == $placement ) {
			$published_countdown_timer = $this->get_countdown_timer_campaigns();

			foreach ( $published_countdown_timer as $c_id ) {
				$placement_settings = $this->get_placement_settings( $c_id['id'], 'all_page' );
				if ( isset( $placement_settings['status'] ) && 'yes' === $placement_settings['status'] ) {
					$included[] = $c_id['id'];
				}
			}
		}

		$campaigns = array();

		foreach ( $included as $camp_id ) {
			if ( ! isset( $campaigns[ $camp_id ] ) ) {
				$campaigns[ $camp_id ] = $this->read_campaign_data( $camp_id );
			}

			if ( ! $campaigns[ $camp_id ] ) {
				unset( $campaigns[ $camp_id ] );
				continue;
			}

			if ( isset( $campaigns[ $camp_id ]['campaign_status'] ) && 'publish' !== $campaigns[ $camp_id ]['campaign_status'] ) {
				unset( $campaigns[ $camp_id ] );
			}
			if ( isset( $campaigns[ $camp_id ] ) && ! $this->is_campaign_eligible( $campaigns[ $camp_id ] ) ) {
				unset( $campaigns[ $camp_id ] );
			}

			if ( $campaign_type && isset( $campaigns[ $camp_id ]['campaign_type'] ) && $campaigns[ $camp_id ]['campaign_type'] !== $campaign_type ) {
				unset( $campaigns[ $camp_id ] );
			}

			$placement_settings = $this->get_placement_settings( $camp_id, $placement );

			if ( ! $is_cart ) {
				if ( isset( $placement_settings['status'], $placement_settings['display_style'] ) ) {
					$status = $placement_settings['status'];
					// Campaigns saved without a display style are in-page; anything else never matches.
					$dis_style = '' !== $placement_settings['display_style'] ? $placement_settings['display_style'] : 'inpage';

					if ( 'drawer' === $dis_style ) {
						$inpage_position = isset( $placement_settings['drawer_position'] ) ? $placement_settings['drawer_position'] : '';

					} elseif ( 'inpage' === $dis_style ) {

						$inpage_position = isset( $placement_settings['inpage_position'] ) ? $placement_settings['inpage_position'] : '';

					}

					if ( 'yes' === $status && $display_type === $dis_style ) {
						if ( 'inpage' === $display_type && $position !== $inpage_position ) {
							unset( $campaigns[ $camp_id ] );
						}
					} else {
						unset( $campaigns[ $camp_id ] );
					}
				} else {
					unset( $campaigns[ $camp_id ] );
				}
			}
		}

		if ( $with_ids ) {
			return array(
				'campaigns' => $campaigns,
				'ids'       => $included,
			);
		}

		return $campaigns;
	}

	/**
	 * Checks if a campaign is eligible based on the current date-time and campaign date-time range.
	 *
	 * @param array $campaign The campaign data array with optional 'campaign_start_date_time' and 'campaign_end_date_time'.
	 * @return bool True if the campaign is eligible, otherwise false.
	 */
	public function is_campaign_eligible( array $campaign ) {
		$current_date_time = new DateTime( current_time( 'mysql' ) );

		$start_date_time = isset( $campaign['campaign_start_date_time'] )
			? new DateTime( $campaign['campaign_start_date_time'] )
			: null;
		$end_date_time   = isset( $campaign['campaign_end_date_time'] )
			? new DateTime( $campaign['campaign_end_date_time'] )
			: null;

		// Both start and end date-times are null, campaign is always available.
		if ( is_null( $start_date_time ) && is_null( $end_date_time ) ) {
			return true;
		}

		// Only end date-time is provided.
		if ( is_null( $start_date_time ) && isset( $campaign['schedule_end_time_enabled'] ) && 'yes' === $campaign['schedule_end_time_enabled'] ) {
			return $current_date_time <= $end_date_time;
		}

		// Only start date-time is provided.
		if ( is_null( $end_date_time ) || ! isset( $campaign['schedule_end_time_enabled'] ) || 'no' === $campaign['schedule_end_time_enabled'] ) {
			return $current_date_time >= $start_date_time;
		}
		// Both start and end date-times are provided.
		return $current_date_time >= $start_date_time && $current_date_time <= $end_date_time;
	}
	/**
	 * Read campaign data.
	 *
	 * @param int    $id Campaign id.
	 * @param mixed  $campaign Campaign.
	 * @param string $context Context.
	 * @since 1.0.0
	 */
	protected function read_campaign_data( $id, $campaign = false, $context = '' ) {
		if ( ! $campaign ) {
			$campaign = (object) $this->get_raw_campaign( $id );
		}

		if ( ! is_object( $campaign ) ) {
			return false;
		}

		$set_props = array();

		foreach ( array_keys( get_object_vars( $campaign ) ) as $field ) {
			$set_props[ $field ] = $campaign->$field;
		}

		foreach ( $this->get_campaign_keys( 'meta' ) as $meta_key ) {
			$set_props[ $meta_key ] = $this->get_campaign_meta( $id, $meta_key, true );
		}

		$set_props['campaign_trigger_items']         = $this->get_raw_campaign_triggers( $id, $context );
		$set_props['campaign_trigger_exclude_items'] = $this->get_raw_campaign_triggers_exclude_items( $id, $context );

		return $set_props;
	}

	/**
	 * Clear campaign runtime cache
	 *
	 * @param string $id Campaign ID.
	 * @return void
	 */
	public function clear_campaign_runtime_cache( $id ) {
		if ( isset( $GLOBALS['revenue_campaign_data'][ $id ] ) ) {
			unset( $GLOBALS['revenue_campaign_data'][ $id ] );
		}

		wp_cache_delete( $id, 'revenue_campaign_triggers' );
		wp_cache_delete( $id, 'revenue_campaign_triggers_exclude_items' );
		wp_cache_delete( $id, 'revenue_campaign_meta' );
		wp_cache_delete( $id, 'revenue_campaigns' );
		wp_cache_delete( $id, 'revenue_campaign_data' );
	}







	/**
	 * Get Campaign Keys
	 *
	 * @param string $type Type.
	 * @return array
	 */
	public function get_campaign_keys( $type = '' ) {
		$keys = array();
		switch ( $type ) {
			case 'campaign_table':
				$keys = array(
					'campaign_name',
					'campaign_author',
					'date_created',
					'date_created_gmt',
					'date_modified',
					'campaign_status',
					'campaign_placement',
					'campaign_behavior',
					'campaign_recommendation',
					'campaign_start_date_time',
					'campaign_end_date_time',
					'campaign_trigger_type',
					'campaign_trigger_relation',
				);
				break;
			case 'meta':
				$keys = array(
					'campaign_popup_animation',
					// 'is_campaign_popup_animation_trigger_immediate',
					'campaign_popup_animation_delay',
					'campaign_floating_position',
					// 'is_campaign_floating_animation_trigger_immediate',
					'campaign_floating_animation_delay',
					'offers',
					'bundle_with_trigger_products_enabled',
					'allow_more_than_required_quantity',
					'banner_heading',
					'banner_subheading',
					'stock_scarcity_enabled',
					'stock_scarcity_actions',
					'countdown_timer_enabled',
					'countdown_start_time_status',
					'countdown_start_date',
					'countdown_start_time',
					'countdown_end_date',
					'countdown_end_time',
					'animated_add_to_cart_enabled',
					'add_to_cart_animation_trigger_type',
					'add_to_cart_animation_type',
					'add_to_cart_animation_start_delay',
					'free_shipping_enabled',
					'schedule_end_time_enabled',
					'skip_add_to_cart',
					'quantity_selector_enabled',
					'multiple_variation_selection_enabled',
					'multiple_variation_selection_enabled',
					'offered_product_on_cart_action',
					'offered_product_click_action',
					'builder',
					'buildeMobileData',
					'campaign_builder_view',
					'builderdata',
					'css',
					'drawer_css',
					'inpage_css',
					'floating_css',
					'popup_css',
					'top_css',
					'bottom_css',
					// 'builderdata',
					'product_tag_text',
					'save_discount_ext',
					'bundle_label_badge',
					'add_to_cart_btn_text',
					'checkout_btn_text',
					'no_thanks_button_text',
					'total_price_text',
					'campaign_view_id',
					'campaign_view_class',
					'campaign_trigger_exclude_items',
					'campaign_trigger_items',
					'buy_x_get_y_trigger_qty_status',
					'placement_settings',
					'countdown_timer_prefix',
					'free_shipping_label',
					'countdown_timer_type',
					'countdown_timer_static_settings',
					'countdown_timer_evergreen_settings',
					'countdown_timer_daily_recurring_settings',
					'countdown_timer_shop_progress_bar',
					'countdown_timer_cart_progress_bar',
					'countdown_timer_entire_site_action_type',
					'countdown_timer_entire_site_action_link',
					'countdown_timer_entire_site_action_enable',
					'countdown_timer_enable_close_button',
					'stock_scarcity_general_message_settings',
					'stock_scarcity_flip_message_settings',
					'stock_scarcity_message_type',
					'stock_scarcity_enable_fake_stock',
					'stock_scarcity_animation_settings',
					'animation_settings_enable',
					'animation_type',
					'animation_duration',
					'delay_between_loop',
					'builderdata',
					'activeTemplate',
					'revx_next_order_coupon',
					// free_shipping_bar keys. Free reads these, so Free must register them.
					'upsell_products',
					'upsell_products_status',
					'is_show_free_shipping_bar',
					'enable_cta_button',
					'cta_button_text',
					'show_close_icon',
					'show_confetti',
					'all_goals_complete_message',
				);
				// code...
				break;
			case 'triggers':
				$keys = array(
					'id',
					'trigger_id',
					'item_id',
					'item_name',
				);
				break;
			case 'trigger_items':
				$keys = array(
					'id',
					'trigger_id',
					'item_id',
					'item_name',
				);

				break;
			case 'analytics':
				$keys = array(
					// 'total_order',
					'daywise_order_stats',
					// 'total_revenue',
					'daywise_revenue_stats',
					// 'total_impression',
					'daywise_impression_stats',
					// 'total_atc',
					'daywise_atc_stats',
					// 'total_checkout',
					'daywise_checkout_stats',
					// 'total_rejection',
					'daywise_rejection_stats',
					'animated_add_to_cart_enabled',
					'free_shipping_enabled',
					'countdown_timer_enabled',
					'stock_scarcity_enabled',
				);
				break;

			default:
				// code...
				break;
		}

		if ( 'meta' === $type ) {
			$keys = apply_filters( 'revenue_campaign_meta_keys', $keys );
		}

		return $keys;
	}


	/**
	 * Trashes or deletes a campaign or page.
	 *
	 * When the campaign and page is permanently deleted, everything that is tied to
	 * it is deleted also. This includes comments, campaign meta fields, and terms
	 * associated with the campaign.
	 *
	 * The campaign or page is moved to Trash instead of permanently deleted unless
	 * Trash is disabled, item is already in the Trash, or $force_delete is true.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int $campaign_id   campaign ID. Default 0.
	 * @return WP_campaign|false|null campaign data on success, false or null on failure.
	 */
	public function delete_campaign( $campaign_id ) {
		global $wpdb;

		//phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$campaign = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}revenue_campaigns WHERE ID = %d",
				$campaign_id
			)
		);
		if ( ! $campaign ) {
			return $campaign;
		}

		$campaign = $this->get_campaign_data( $campaign_id );

		/**
		 * Fires before a campaign is deleted.
		 *
		 * @since 1.0.0
		 *
		 * @param int     $campaign_id campaign ID.
		 * @param object  $campaign    campaign object.
		 */
		do_action( 'revenue_before_delete_campaign', $campaign_id, $campaign );

		// Delete campaign meta.
		$campaign_meta_ids  = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->prefix}revenue_campaign_meta WHERE campaign_id = %d ", $campaign_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$meta_delete_result = false;
		foreach ( $campaign_meta_ids as $mid ) {
			$meta_delete_result = (bool) $wpdb->delete( $wpdb->prefix . 'revenue_campaign_meta', array( 'meta_id' => $mid ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		if ( $meta_delete_result ) {
			wp_cache_delete( $campaign_id, 'revenue_campaign_meta' );
		}

		// Delete campaign triggers.
		$campaign_trigger_ids  = $wpdb->get_col( $wpdb->prepare( "SELECT trigger_id FROM {$wpdb->prefix}revenue_campaign_triggers WHERE campaign_id = %d ", $campaign_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$trigger_delete_result = false;
		foreach ( $campaign_trigger_ids as $tid ) {
			$trigger_delete_result = $this->delete_campaign_trigger( $campaign_id, $tid );
		}
		if ( $trigger_delete_result ) {
			wp_cache_delete( $campaign_id, 'revenue_campaign_triggers' );
		}

		// Delete campaign analytics
		$analytics_data_delete = $wpdb->delete( $wpdb->prefix . 'revenue_campaign_analytics', array( 'campaign_id' => $campaign_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		$result = $wpdb->delete( $wpdb->prefix . 'revenue_campaigns', array( 'id' => $campaign_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! $result ) {
			return false;
		}

		wp_cache_delete( $campaign_id, 'revenue_campaigns' );

		do_action( 'revenue_after_delete_campaign', $campaign_id, $campaign );

		return $campaign;
	}

	/**
	 * Delete campaign trigger
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign id.
	 * @param int $trigger_id Trigger id.
	 * @return int|false
	 */
	public function delete_campaign_trigger( $campaign_id, $trigger_id ) {
		global $wpdb;

		if ( ! $campaign_id || ! is_numeric( $campaign_id ) ) {
			return 0;
		}

		if ( ! $trigger_id || ! is_numeric( $trigger_id ) ) {
			return 0;
		}

		$result = (bool) $wpdb->delete( $wpdb->prefix . 'revenue_campaign_triggers', array( 'trigger_id' => $trigger_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $result;
	}

	/**
	 * Deletes a campaign meta field for the given campaign ID.
	 *
	 * You can match based on the key, or key and value. Removing based on key and
	 * value, will keep from removing duplicate metadata with the same key. It also
	 * allows removing all metadata matching the key, if needed.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id    campaign ID.
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value. If provided,
	 *                           rows will only be removed that match the value.
	 *                           Must be serializable if non-scalar. Default empty.
	 * @param bool   $delete_all Is delete all.
	 * @return bool True on success, false on failure.
	 */
	public function delete_campaign_meta( $campaign_id, $meta_key, $meta_value = '', $delete_all = false ) {
		global $wpdb;
		if ( ! $meta_key || ( ! is_numeric( $campaign_id ) && ! $delete_all ) ) {
			return false;
		}
		$meta_key   = wp_unslash( $meta_key );
		$meta_value = wp_unslash( $meta_value );
		$meta_value = maybe_serialize( $meta_value );
		$params     = array( $meta_key );

		$query = "SELECT meta_id FROM {$wpdb->prefix}revenue_campaign_meta WHERE meta_key = %s";

		if ( ! $delete_all ) {
			$query   .= ' AND campaign_id = %d';
			$params[] = $campaign_id;
		}

		if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value ) {
			$query   .= ' AND meta_value = %s';
			$params[] = $meta_value;
		}

		$meta_ids = $wpdb->get_col( $wpdb->prepare( $query, ...$params ) ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- $query is fully parameterised via $wpdb->prepare() above.
		if ( ! count( $meta_ids ) ) {
			return false;
		}

		if ( $delete_all ) {
			if ( '' !== $meta_value && null !== $meta_value && false !== $meta_value ) {
				$campaign_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->prefix}revenue_campaign_meta WHERE meta_key = %s AND meta_value = %s", $meta_key, $meta_value ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			} else {
				$campaign_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->prefix}revenue_campaign_meta WHERE meta_key = %s", $meta_key ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			}
		}

		$meta_ids = array_map( 'absint', $meta_ids );

		$count = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->prefix}revenue_campaign_meta WHERE meta_id IN(" . implode( ',', array_fill( 0, count( $meta_ids ), '%d' ) ) . ')',
				...$meta_ids
			)
		); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( ! $count ) {
			return false;
		}

		if ( $delete_all ) {
			$data = (array) $campaign_ids;
		} else {
			$data = array( $campaign_id );
		}
		wp_cache_delete_multiple( $data, 'revenue_campaign_meta' );

		return true;
	}


	/**
	 * Retrieves a campaign meta field for the given campaign ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id campaign ID.
	 * @param string $meta_key     Optional. The meta key to retrieve. By default,
	 *                        returns data for all keys. Default empty.
	 * @param bool   $single  Optional. Whether to return a single value.
	 *                        This parameter has no effect if `$key` is not specified.
	 *                        Default false.
	 * @return mixed An array of values if `$single` is false.
	 *               The value of the meta field if `$single` is true.
	 *               False for an invalid `$campaign_id` (non-numeric, zero, or negative value).
	 *               An empty string if a valid but non-existing campaign ID is passed.
	 */
	public function get_campaign_meta( $campaign_id, $meta_key = '', $single = false ) {
		if ( ! $campaign_id || is_wp_error( $campaign_id ) || ! is_numeric( $campaign_id ) ) {
			return null;
		}
		$meta_cache = wp_cache_get( $campaign_id, 'revenue_campaign_meta' );

		if ( ! $meta_cache ) {
			$meta_cache = $this->update_campaign_meta_cache( array( $campaign_id ) );
			if ( isset( $meta_cache[ $campaign_id ] ) ) {
				$meta_cache = $meta_cache[ $campaign_id ];
			} else {
				$meta_cache = null;
			}
		}

		if ( ! $meta_key ) {
			return $meta_cache;
		}

		if ( isset( $meta_cache[ $meta_key ] ) ) {
			if ( $single ) {
				return maybe_unserialize( $meta_cache[ $meta_key ][0] );
			} else {
				return array_map( 'maybe_unserialize', $meta_cache[ $meta_key ] );
			}
		}

		return null;
	}


	/**
	 * Updates the campaign meta cache for the specified campaign ID.
	 *
	 * This function retrieves metadata for campaigns that are not currently
	 * cached and updates the cache accordingly. It fetches campaign metadata
	 * from the database and organizes it into an associative array.
	 *
	 * @param int $campaign_id The ID of the campaign whose metadata needs to be updated.
	 *
	 * @return array An associative array of cached campaign metadata, where the keys
	 *               are campaign IDs and the values are arrays of meta data indexed
	 *               by meta keys.
	 */
	public function update_campaign_meta_cache( $campaign_id ) {
		global $wpdb;

		$cache_key      = 'revenue_campaign_meta';
		$non_cached_ids = array();
		$cache          = array();

		$cache_values = wp_cache_get_multiple( $campaign_id, $cache_key );

		foreach ( $cache_values as $id => $cached_object ) {
			if ( false === $cached_object ) {
				$non_cached_ids[] = $id;
			} else {
				$cache[ $id ] = $cached_object;
			}
		}

		if ( empty( $non_cached_ids ) ) {
			return $cache;
		}

		$non_cached_ids = array_map( 'absint', $non_cached_ids );
		$placeholders   = implode( ',', array_fill( 0, count( $non_cached_ids ), '%d' ) );

		$meta_list = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT campaign_id, meta_key, meta_value FROM {$wpdb->prefix}revenue_campaign_meta WHERE campaign_id IN ($placeholders) ORDER BY meta_id ASC", //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- %d placeholders built to match ID count.
				$non_cached_ids
			),
			ARRAY_A
		); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cached via wp_cache_* above.

		if ( ! empty( $meta_list ) ) {
			foreach ( $meta_list as $metarow ) {
				$mpid = (int) $metarow['campaign_id'];
				$mkey = $metarow['meta_key'];
				$mval = $metarow['meta_value'];

				// Force subkeys to be array type.
				if ( ! isset( $cache[ $mpid ] ) || ! is_array( $cache[ $mpid ] ) ) {
					$cache[ $mpid ] = array();
				}
				if ( ! isset( $cache[ $mpid ][ $mkey ] ) || ! is_array( $cache[ $mpid ][ $mkey ] ) ) {
					$cache[ $mpid ][ $mkey ] = array();
				}

				// Add a value to the current pid/key.
				$cache[ $mpid ][ $mkey ][] = $mval;
			}
		}

		$data = array();
		foreach ( $non_cached_ids as $id ) {
			if ( ! isset( $cache[ $id ] ) ) {
				$cache[ $id ] = array();
			}
			$data[ $id ] = $cache[ $id ];
		}

		wp_cache_add_multiple( $data, $cache_key );

		return $cache;
	}


	/**
	 * Updates a campaign meta field based on the given campaign ID.
	 *
	 * Use the `$prev_value` parameter to differentiate between meta fields with the
	 * same key and campaign ID.
	 *
	 * If the meta field for the campaign does not exist, it will be added and its ID returned.
	 *
	 * Can be used in place of add_campaign_meta().
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id    campaign ID.
	 * @param string $meta_key   Metadata key.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param mixed  $prev_value Optional. Previous value to check before updating.
	 *                           If specified, only update existing metadata entries with
	 *                           this value. Otherwise, update all entries. Default empty.
	 * @return int|bool Meta ID if the key didn't exist, true on successful update,
	 *                  false on failure or if the value passed to the function
	 *                  is the same as the one that is already in the database.
	 */
	public function update_campaign_meta( $campaign_id, $meta_key, $meta_value, $prev_value = '' ) {
		global $wpdb;

		$meta_key   = wp_unslash( $meta_key );
		$meta_value = wc_clean( wp_unslash( $meta_value ) );

		if ( 'stock_scarcity_actions' === $meta_key ) {

			$charset = $wpdb->get_col_charset( $wpdb->prefix . 'revenue_campaign_meta', 'meta_value' );

			if ( is_array( $meta_value ) ) {
				foreach ( $meta_value as $key => $mv ) {
					if ( 'utf8' === $charset ) {
						$meta_value[ $key ]['stock_message'] = wp_encode_emoji( $mv );
					}
				}
			}
		}

		if ( 'offers' === $meta_key ) {
			$campaign = $this->get_campaign_data( $campaign_id );

			$campaign_type = $campaign['campaign_type'];
			switch ( $campaign_type ) {
				case 'normal_discount':
					break;
				case 'bundle_discount':
				case 'buy_x_get_y':
					$valid_meta_value = array();

					foreach ( $meta_value as $data ) {
						if ( isset( $data['products'], $data['quantity'], $data['type'] ) && ! empty( $data['products'] ) && ! empty( $data['quantity'] ) && ! empty( $data['type'] ) ) {
							if ( 'free' == $data['type'] || 'no_discount' === $data['type'] ) {
								$data['value'] = '';
							}
							$valid_meta_value[] = $data;
						}
					}

					$meta_value = $valid_meta_value;

					break;
				case 'volume_discount':
					break;

				default:
					// code...
					break;
			}
		}

		// Compare existing value to new value if no prev value given and the key exists only once.
		if ( empty( $prev_value ) ) {
			$old_value = $this->get_campaign_meta( $campaign_id, $meta_key, false );
			if ( is_countable( $old_value ) && count( $old_value ) === 1 ) {
				if ( $old_value[0] === $meta_value ) {
					return false;
				}
			}
		}

		$meta_ids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM {$wpdb->prefix}revenue_campaign_meta WHERE meta_key = %s AND campaign_id = %d", $meta_key, $campaign_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( empty( $meta_ids ) ) {
			return $this->add_campaign_meta( $campaign_id, $meta_key, $meta_value );
		}

		$meta_value = maybe_serialize( $meta_value );

		$data  = compact( 'meta_value' );
		$where = array(
			'campaign_id' => $campaign_id,
			'meta_key'    => $meta_key, //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		);

		if ( ! empty( $prev_value ) ) {
			$prev_value          = maybe_serialize( $prev_value );
			$where['meta_value'] = $prev_value; //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		}

		$result = $wpdb->update( $wpdb->prefix . 'revenue_campaign_meta', $data, $where ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! $result ) {
			return false;
		}

		wp_cache_delete( $campaign_id, 'revenue_campaign_meta' );

		return update_metadata( 'revenue_campaign', $campaign_id, $meta_key, $meta_value, $prev_value );
	}

	/**
	 * Adds metadata for the specified object.
	 *
	 * @since 1.0.0
	 *
	 * @global wpdb $wpdb WordPress database abstraction object.
	 *
	 * @param int    $campaign_id   ID of the object metadata is for.
	 * @param string $meta_key      Metadata key.
	 * @param mixed  $meta_value    Metadata value. Must be serializable if non-scalar.
	 * @return int|false The meta ID on success, false on failure.
	 */
	public function add_campaign_meta( $campaign_id, $meta_key, $meta_value ) {
		global $wpdb;
		$meta_key = sanitize_key( $meta_key );

		$meta_value = wc_clean( wp_unslash( $meta_value ) );
		$meta_value = maybe_serialize( $meta_value );

		$result = $wpdb->insert( //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'revenue_campaign_meta',
			array(
				'campaign_id' => $campaign_id,
				'meta_key'    => $meta_key, //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'  => $meta_value, //phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		if ( ! $result ) {
			return false;
		}

		$mid = (int) $wpdb->insert_id;

		wp_cache_delete( $campaign_id, 'revenue_campaign_meta' );

		return $mid;
	}


	/**
	 * Get raw campaign
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign id.
	 * @return object
	 */
	public function get_raw_campaign( $campaign_id ) {
		global $wpdb;

		$campaign_id = (int) $campaign_id;
		if ( ! $campaign_id ) {
			return false;
		}

		$campaign = wp_cache_get( $campaign_id, 'revenue_campaigns' );

		if ( ! $campaign ) {
			$campaign = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}revenue_campaigns WHERE ID = %d LIMIT 1", $campaign_id ) ); //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery

			if ( ! $campaign ) {
				return false;
			}

			$campaign = $this->sanitize_campaign( $campaign );

			wp_cache_add( $campaign->id, $campaign, 'revenue_campaigns' );
		}

		return $campaign;
	}

	/**
	 * Get raw campaign triggers
	 *
	 * @since 1.0.0
	 *
	 * @param int    $campaign_id Campaign id.
	 * @param string $context Optional context.
	 * @param bool   $exclude Optional flag to exclude items.
	 * @return array|false
	 */
	private function get_raw_campaign_triggers( $campaign_id, $context = '', $exclude = false ) {
		global $wpdb;

		$campaign_id = (int) $campaign_id;

		if ( ! $campaign_id ) {
			return false;
		}

		// Use cache.
		$cache_key = $exclude ? 'revenue_campaign_triggers_exclude_items' : 'revenue_campaign_trigger_items';
		$triggers  = wp_cache_get( $campaign_id, $cache_key );

		if ( ! $triggers ) {
			$action_condition = $exclude ? "AND trigger_action = 'exclude'" : '';
			$triggers         = $wpdb->get_results( //phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter -- $action_condition is a hardcoded string literal.
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}revenue_campaign_triggers WHERE campaign_id = %d {$action_condition}", //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$campaign_id
				)
			);

			if ( empty( $triggers ) ) {
				return false;
			}

			$data = array();

			foreach ( $triggers as $trigger ) {
				$trigger = (array) $trigger;

				$data[ $trigger['trigger_id'] ]                   = $trigger;
				$data[ $trigger['trigger_id'] ]['trigger_action'] = $trigger['trigger_action'];
				$data[ $trigger['trigger_id'] ]['trigger_type']   = $trigger['trigger_type'];
				$data[ $trigger['trigger_id'] ]['item_quantity']  = $trigger['item_quantity'];
				$data[ $trigger['trigger_id'] ]['item_id']        = $trigger['item_id'];
				if ( 'category' == $trigger['trigger_type'] ) {
					$term                                  = get_term( $trigger['item_id'], 'product_cat' );
					$data[ $trigger['trigger_id'] ]['url'] = get_term_link( $term );
				} else {
					$data[ $trigger['trigger_id'] ]['url'] = get_permalink( $trigger['item_id'] );
				}
			}

			$triggers = $data;

			// Add to cache.
			wp_cache_set( $campaign_id, $data, $cache_key );
		}

		return $triggers;
	}


	/**
	 * Get raw campaign triggers exclude items
	 *
	 * @since 1.0.0
	 *
	 * @param int $campaign_id Campaign id.
	 * @return array|false
	 */
	public function get_raw_campaign_triggers_exclude_items( $campaign_id, $context = '' ) {
		return $this->get_raw_campaign_triggers( $campaign_id, $context, true );
	}



	/**
	 * Sanitize campaign
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $campaign Campaign.
	 * @return mixed
	 */
	public function sanitize_campaign( $campaign ) {
		if ( is_object( $campaign ) ) {

			if ( ! isset( $campaign->id ) ) {
				$campaign->id = 0;
			}
			foreach ( array_keys( get_object_vars( $campaign ) ) as $field ) {
				$campaign->$field = $this->sanitize_campaign_field( $field, $campaign->$field, $campaign->id );
			}
		} elseif ( is_array( $campaign ) ) {

			if ( ! isset( $campaign['id'] ) ) {
				$campaign['id'] = 0;
			}
			foreach ( array_keys( $campaign ) as $field ) {
				$campaign[ $field ] = $this->sanitize_campaign_field( $field, $campaign[ $field ], $campaign['id'] );
			}
		}

		return $campaign;
	}


	/**
	 * Sanitize campaign field
	 *
	 * @since 1.0.0
	 *
	 * @param string $field Field name.
	 * @param string $value Field value.
	 * @param string $context Context.
	 * @return string
	 */
	public function sanitize_campaign_field( $field, $value, $context = 'display' ) {
		switch ( $field ) {
			case 'campaign_name':
				$value = sanitize_text_field( $value );
				break;
			case 'campaign_author':
			case 'id':
				$value = (int) sanitize_text_field( $value );
				// code...
				break;
			case 'campaign_status':
				$value = sanitize_text_field( $value );
				break;
			case 'campaign_type':
				$value = sanitize_text_field( $value );
				break;
			case 'campaign_placement':
				$value = sanitize_text_field( $value );
				break;
			case 'campaign_behavior':
				$value = sanitize_text_field( $value );
				break;
			case 'campaign_recommendation':
			case 'campaign_inpage_position':
			case 'campaign_display_style':
				$value = sanitize_text_field( $value );
				break;
			case 'campaign_apply_on':
				$value = sanitize_text_field( $value );
				break;

			default:
				break;
		}

		return $value;
	}

	/**
	 * Replaces discount placeholders in a given text with the actual discount value.
	 *
	 * This function dynamically replaces the `{discount_value}` placeholder in a text string
	 * with either a formatted percentage or price value, depending on the given parameters.
	 * It ensures proper HTML escaping and WooCommerce-compatible price formatting.
	 *
	 * @param string $text          The text containing the `{discount_value}` placeholder.
	 * @param float  $value         The discount value to insert into the text.
	 * @param bool   $is_percentage Optional. Whether the discount is a percentage value. Default false.
	 *
	 * @return string The modified text with the discount value inserted.
	 */
	public static function get_modified_text( $text, $value, $is_percentage = false ) {
		if ( $is_percentage ) {
			$display_value = esc_html( $value ) . '%';
		} else {
			$display_value = wc_price( $value );
		}
		$text = str_replace( '{discount_value}', $display_value, $text );
		return $text;
	}

	/**
	 * Calculates the offered price based on the specified offer type and value.
	 *
	 * This function computes the price offered to the customer based on different
	 * types of discounts or offers (percentage, fixed discount, fixed price, etc.).
	 * It can also return a message indicating the savings or offer details.
	 *
	 * @param string $offer_type        The type of offer (e.g., 'percentage', 'fixed_discount', 'fixed_price', 'no_discount', 'free').
	 * @param float  $offer_value            The value associated with the offer, which can be a percentage or fixed amount.
	 * @param float  $discount_base_price    The base price on which discounts are calculated (required).
	 * @param bool   $with_save_data         Whether to return additional save data (message and offer details).
	 * @param int    $offer_qty              The quantity associated with the offer (default is 1).
	 * @param string $campaign_type          Campaign type (e.g., 'volume_discount').
	 * @param string $save_text              Save message template.
	 * @param float  $regular_price          Optional actual regular price (used for total save calculations).
	 *
	 * @return array|float The calculated offered price. If $with_save_data is true,
	 *                     an array containing offer details (type, value, message, price) is returned.
	 *                     Otherwise, the final offered price is returned as a float.
	 */
	public function calculate_campaign_offered_price(
		$offer_type,
		$offer_value,
		$discount_base_price,
		$with_save_data = false,
		$offer_qty = 1,
		$campaign_type = '',
		$save_text = 'Save {discount_value}',
		$regular_price = 0.0
	) {
		$offered_price = 0.0;

		// incoming $discount_base_price is required (base price for discount calcs)
		// incoming $regular_price is optional (actual regular price used for savings display).
		$discount_base_price = floatval( $discount_base_price );
		$regular_price       = floatval( $regular_price );
		// ensure regular_price defaults to at least the discount base price.
		$regular_price = $regular_price > $discount_base_price ? $regular_price : $discount_base_price;
		$offer_value   = floatval( $offer_value );
		$offer_qty     = floatval( $offer_qty );
		$save_data     = array();

		$save_data['message'] = '';

		if ( ! $offer_type ) {
			return $discount_base_price;
		}

		switch ( $offer_type ) {
			case 'percentage':
				// calculate discount based on the discount base price.
				$offered_price     = $discount_base_price - ( $discount_base_price * ( ( $offer_value * 1.0 ) / 100 ) );
				$save_data['type'] = 'percentage';
				// default percentage value is the offer value, but if regular price
				// is greater than base price, recompute percentage relative to regular price.
				if ( $regular_price > $discount_base_price && $regular_price > 0 ) {
					$perc = ( ( $regular_price - $offered_price ) / $regular_price ) * 100;

					$save_data['value'] = round( $perc, 2 );
					if ( $save_data['value'] ) {
						$save_data['message'] = self::get_modified_text( $save_text, $save_data['value'], true );
					}
				} else {
					$save_data['value'] = $offer_value;
					if ( $offer_value ) {
						$save_data['message'] = self::get_modified_text( $save_text, $offer_value, true );
					}
				}
				break;
			case 'fixed_discount':
			case 'amount':
				// apply fixed discount on the discount base price.
				$offered_price     = max( floatval( 0 ), ( $discount_base_price - $offer_value ) );
				$save_data['type'] = 'amount';
				// total save should be computed against the regular price.
				$total_save           = ( $regular_price - $offered_price ) * $offer_qty;
				$save_data['message'] = self::get_modified_text( $save_text, $total_save );
				break;
			case 'fixed_price':
				$offered_price      = $offer_value;
				$save_data['type']  = 'amount';
				$save_data['value'] = $offer_value;
				// total save computed against regular price (actual).
				$total_save = $regular_price - $offer_value;
				if ( 'volume_discount' === $campaign_type ) {
					$total_save = $total_save * $offer_qty;
				}
				$save_data['message'] = self::get_modified_text( $save_text, $total_save );
				break;
			case 'fixed_total_price':
				$offered_price = $offer_value;

				if ( 'volume_discount' === $campaign_type ) {
					// regular_price is the per-item regular price; compute totals.
					$offered_price = $offer_value / $offer_qty;
				}

				$save_data['type']  = 'fixed_total_price';
				$save_data['value'] = $offer_value;
				// total save computed using regular price totals.
				$total_save           = ( $regular_price * ( 'volume_discount' === $campaign_type ? $offer_qty : 1 ) ) - $offer_value;
				$save_data['message'] = self::get_modified_text( $save_text, $total_save );

				break;

			case 'no_discount':
				$offered_price        = $regular_price - $offer_value;
				$save_data['type']    = 'amount';
				$save_data['value']   = 0;
				$save_data['message'] = '';
				break;

			case 'free':
				$offered_price        = 0.0;
				$save_data['type']    = 'percentage';
				$save_data['value']   = '100';
				$save_data['message'] = __( 'Get Free', 'revenue' );
				break;

			default:
				// code...
				break;
		}
		// Ignoring particular case which results in round up for corner case like 17.999 = 18.00
		// where quantity is 10 and total price is 179.99 . If modification needed, handle with care.
		$volume_and_fixed_total = 'volume_discount' === $campaign_type && 'fixed_total_price' === $offer_type;
		if ( ! $volume_and_fixed_total ) {
			// 2nd parameter empty string for woocommerce settings, integer for specific decimal places.
			$offered_price = wc_format_decimal( $offered_price, '' );
		}

		if ( $with_save_data ) {
			$save_data['price'] = max( 0.0, $offered_price );

			return $save_data;
		}

		return max( 0.0, $offered_price );
	}


	/**
	 * Get data if set, otherwise return a default value or null. Prevents notices when data is not set.
	 *
	 * @since  1.0.0
	 * @param  mixed  $var     Variable.
	 * @param  string $default Default value.
	 * @return mixed
	 */
	public function get_var( &$var, $default = null ) {
		return isset( $var ) ? $var : $default;
	}

	/**
	 * Increment Campaign Add to cart count
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $product_id Product id.
	 * @return void
	 */
	public function increment_campaign_add_to_cart_count( $campaign_id, $product_id = false ) {
		$user_id = get_current_user_id();

		$campaign = $this->get_campaign_data( $campaign_id );

		if ( ! $campaign ) {
			return;
		}

		Revenue_Analytics::instance()->update_campaign_stat( $campaign_id, 'add_to_cart_count' );
	}
	/**
	 * Increment Campaign checkout page count
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $product_id Product id.
	 * @return void
	 */
	public function increment_campaign_checkout_count( $campaign_id, $product_id = false ) {
		$user_id = get_current_user_id();

		$campaign = $this->get_campaign_data( $campaign_id );

		if ( ! $campaign ) {
			return;
		}
		Revenue_Analytics::instance()->update_campaign_stat( $campaign_id, 'add_to_cart_count' );
	}
	/**
	 * Increment Campaign order page count
	 *
	 * @param int $campaign_id Campaign.
	 * @param int $product_id Product id.
	 * @param int $order Order id.
	 * @return void
	 */
	public function increment_campaign_order_count( $campaign_id, $product_id = false, $order_id = false ) {
		$user_id = get_current_user_id();

		$campaign = $this->get_campaign_data( $campaign_id );
		if ( ! $campaign ) {
			return;
		}

		Revenue_Analytics::instance()->update_campaign_stat( $campaign_id, 'order_count' );
	}

	/**
	 * Increment Campaign popup rejection count
	 *
	 * @param int $campaign_id Campaign.
	 * @return void
	 */
	public function increment_campaign_rejection_count( $campaign_id ) {
		$user_id  = get_current_user_id();
		$campaign = $this->get_campaign_data( $campaign_id );

		if ( ! $campaign ) {
			return;
		}

		Revenue_Analytics::instance()->update_campaign_stat( $campaign_id, 'rejection_count' );

		WC()->session->set( 'revx_should_check_order_for_campaign', true );
	}


	/**
	 * Get a specific setting by key or return all settings merged with defaults if no key is provided.
	 *
	 * @param string $key Optional. The key of the setting to retrieve. If not provided,
	 *                    all settings merged with default settings are returned.
	 * @return mixed The value of the specific setting if a key is provided, or all settings
	 *               merged with default settings if no key is provided.
	 */
	public function get_setting( $key = '' ) {
		// Get the saved settings from the database with a default empty array.
		$settings = get_option( 'revenue_settings', array() );

		// If a specific key is provided, return its value or the default setting if not found.
		if ( $key ) {
			return isset( $settings[ $key ] ) ? $settings[ $key ] : $this->get_default_settings( $key );
		}

		// If no key is provided, return all settings merged with default settings.
		return array_merge( $this->get_default_settings(), $settings );
	}

	/**
	 * Get the default settings or a specific default setting value.
	 *
	 * This function retrieves default settings, either as a whole or for a specific key.
	 * It applies the 'revenue_get_default_settings' filter, allowing other developers
	 * to override or extend the default settings.
	 *
	 * @param string $key Optional. The key of the specific default setting to retrieve.
	 *                    If empty, all default settings are returned.
	 * @return mixed The value of the specific default setting if a key is provided,
	 *               all default settings if no key is provided, or false if the key is not found.
	 */
	public function get_default_settings( $key = '' ) {
		// Define default settings and allow filtering.

		$defaults = apply_filters(
			'revenue_get_default_settings',
			array(
				'campaign_list_columns_visible' => array(
					'check_column',
					'campaign_name',
					'id',
					'campaign_status',
					'triggers',
					'total_add_to_cart',
					'conversion_rate',
					'total_sales',
					'campaign_progress',
					'actions',
				),
				'themes'                        => array(
					'base'   => array(
						'primary'   => '#0A0D14',
						'secondary' => '#FFFFFF',
						'shades'    => array( '#31353F', '#868C98', '#E2E4E9', '#F6F8FA' ),
					),
					'accent' => array(
						'primary'    => '#6E3FF3',
						'secondary'  => '#00A464',
						'tertiary'   => '#FBDFB1',
						'quaternary' => '#FEF7EC',
					),
				),
				'typography'                    => array(
					'style1' => array(
						'mobile'  => array(
							'fontSize'   => 16,
							'fontWeight' => 500,
						),
						'tablet'  => array(
							'fontSize'   => 24,
							'fontWeight' => 500,
						),
						'desktop' => array(
							'fontSize'   => 28,
							'fontWeight' => 500,
						),
					),
					'style2' => array(
						'mobile'  => array(
							'fontSize'   => 16,
							'fontWeight' => 500,
						),
						'tablet'  => array(
							'fontSize'   => 16,
							'fontWeight' => 500,
						),
						'desktop' => array(
							'fontSize'   => 24,
							'fontWeight' => 500,
						),
					),
					'style3' => array(
						'mobile'  => array(
							'fontSize'   => 12,
							'fontWeight' => 500,
						),
						'tablet'  => array(
							'fontSize'   => 14,
							'fontWeight' => 500,
						),
						'desktop' => array(
							'fontSize'   => 16,
							'fontWeight' => 500,
						),
					),
					'style4' => array(
						'mobile'  => array(
							'fontSize'   => 12,
							'fontWeight' => 500,
						),
						'tablet'  => array(
							'fontSize'   => 12,
							'fontWeight' => 500,
						),
						'desktop' => array(
							'fontSize'   => 14,
							'fontWeight' => 500,
						),
					),
					'style5' => array(
						'mobile'  => array(
							'fontSize'   => 12,
							'fontWeight' => 500,
						),
						'tablet'  => array(
							'fontSize'   => 12,
							'fontWeight' => 500,
						),
						'desktop' => array(
							'fontSize'   => 12,
							'fontWeight' => 500,
						),
					),
				),
				'theme'                         => 'default',
			),
			$key
		);

		// If a key is provided, return its value from the defaults. If not found, return false.
		return $key ? ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : false ) : $defaults;
	}



	/**
	 * Set a specific setting by key and update the database.
	 *
	 * @param string $key The key of the setting to update.
	 * @param mixed  $val The value to set for the specified key.
	 * @return bool True if the option value has changed, false if not or if update failed.
	 */
	public function set_setting( $key, $val ) {
		// Get the saved settings from the database or initialize as an empty array.
		$settings = get_option( 'revenue_settings', array() );

		// Ensure $settings is an array.
		if ( ! is_array( $settings ) ) {
			$settings = array();
		}

		// Check if the new value is different from the existing value.
		if ( isset( $settings[ $key ] ) && $settings[ $key ] === $val ) {
			// No need to update if the value hasn't changed.
			return true;
		}

		// Update the specific key with the new value.
		$settings[ $key ] = $val;

		// Save the updated settings array back to the database.
		return update_option( 'revenue_settings', $settings );
	}



	/**
	 * True if a cart item appears to be a bundle container item.
	 *
	 * @since  1.0.0
	 *
	 * @param  array $cart_item Cart item.
	 * @return boolean
	 */
	public function is_bundle_container_cart_item( $cart_item ) {
		$is_bundle = false;

		if ( isset( $cart_item['revx_bundled_items'] ) ) {
			$is_bundle = true;
		}

		return $is_bundle;
	}




	/**
	 * Given a bundle container cart item, find and return its child cart items - or their cart ids when the $return_ids arg is true.
	 *
	 * @since  1.0.0
	 *
	 * @param  array   $container_cart_item Container Cart item.
	 * @param  array   $cart_contents Cart contents.
	 * @param  boolean $return_ids Return ids.
	 * @return mixed
	 */
	public function get_bundled_cart_items( $container_cart_item, $cart_contents = false, $return_ids = false ) {
		if ( ! $cart_contents ) {
			$cart_contents = isset( WC()->cart ) ? WC()->cart->cart_contents : array();
		}

		$bundled_cart_items = array();

		if ( $this->is_bundle_container_cart_item( $container_cart_item ) ) {

			$bundled_items = $container_cart_item['revx_bundled_items'];

			if ( ! empty( $bundled_items ) && is_array( $bundled_items ) ) {
				foreach ( $bundled_items as $bundled_cart_item_key ) {
					if ( isset( $cart_contents[ $bundled_cart_item_key ] ) ) {
						$bundled_cart_items[ $bundled_cart_item_key ] = $cart_contents[ $bundled_cart_item_key ];
					}
				}
			}
		}

		return $return_ids ? array_keys( $bundled_cart_items ) : $bundled_cart_items;
	}













	public function set_product_image_trigger_item_response( $data, $is_clone = false ) {
		$keys = array( 'campaign_trigger_items', 'campaign_trigger_exclude_items' );

		foreach ( $keys as $key ) {
			if ( ! isset( $data[ $key ] ) || ! is_array( $data[ $key ] ) ) {
				continue;
			}

			$updated_data = array();
			foreach ( $data[ $key ] as $item ) {
				$item = (array) $item;
				if ( 'campaign_trigger_items' === $key && 'include' !== $item['trigger_action'] ) {
					continue;
				}
				$image_url = '';

				$updated_item = $item; // No need to use array_merge, direct assignment works

				if ( 'products' === $item['trigger_type'] ) {
					$product = wc_get_product( $item['item_id'] );
					if ( ! $product ) {
						continue;
					}
					// error_log( print_r( $product, true ) );
					if ( $product && $product->is_type( 'variation' ) ) {
						$parent_id   = $product->get_parent_id();
						$parent      = wc_get_product( $parent_id );
						$parent_name = $parent ? $parent->get_name() : '';
						$attributes  = $product->get_attributes();

						$attribute_parts = array();

						foreach ( $attributes as $attr_key => $value ) {
							$taxonomy = str_replace( 'attribute_', '', $attr_key );
							if ( taxonomy_exists( $taxonomy ) ) {
								$taxonomy_obj      = get_taxonomy( $taxonomy );
								$label             = $taxonomy_obj ? $taxonomy_obj->labels->singular_name : ucfirst( $taxonomy );
								$term              = get_term_by( 'slug', $value, $taxonomy );
								$value_name        = $term ? $term->name : $value;
								$attribute_parts[] = "{$label}: {$value_name}";
							} else {
								$label             = ucfirst( str_replace( '_', ' ', $taxonomy ) );
								$attribute_parts[] = "{$label}: {$value}";
							}
						}

						$full_name = $parent_name . ' – ' . implode( ', ', $attribute_parts );
					} else {
						$full_name = $product ? $product->get_name() : '';
					}
					$image_url                      = wp_get_attachment_url( $product->get_image_id() ) ? wp_get_attachment_url( $product->get_image_id() ) : wc_placeholder_img_src();
					$updated_item['item_name']      = $full_name;
					$updated_item['regular_price']  = $product->get_regular_price();
					$updated_item['sale_price']     = $product->get_sale_price();
					$updated_item['parent_id']      = $product->is_type( 'variation' ) ? $product->get_parent_id() : '';
					$updated_item['show_attribute'] = 'variable' == $product->get_type();
				} elseif ( 'category' === $item['trigger_type'] ) {
					$category                  = get_term( $item['item_id'] );
					$thumbnail_id              = get_term_meta( $category->term_id, 'thumbnail_id', true );
					$image_url                 = wp_get_attachment_url( $thumbnail_id ) ? wp_get_attachment_url( $thumbnail_id ) : wc_placeholder_img_src();
					$updated_item['item_name'] = rawurldecode( wp_strip_all_tags( $category->name ) );
				}

				$updated_item = apply_filters( 'revenue_trigger_item_response', $updated_item, $item['trigger_type'], $data['campaign_type'] );

				$updated_item['thumbnail'] = $image_url;

				if ( $is_clone ) {
					if ( isset( $updated_item['campaign_id'] ) ) {
						unset( $updated_item['campaign_id'] );
					}
					if ( isset( $updated_item['trigger_id'] ) ) {
						unset( $updated_item['trigger_id'] );
					}
				}

				$updated_data[] = $updated_item;
			}

			$data[ $key ] = $updated_data;
		}
		return $data;
	}




	public function update_campaign_impression( $campaign_id, $product_id = false ) {
		$campaign = $this->get_campaign_data( $campaign_id );
		if ( ! $campaign || empty( $campaign ) ) {
			return;
		}

		Revenue_Analytics::instance()->update_campaign_stat( $campaign_id, 'impression_count' );
	}


	/**
	 * Update campaign table by campaign id and specific column and value.
	 *
	 * @since 1.0.0
	 */
	public function update_campaign( $campaign_id, $key, $value ) {
		global $wpdb;
		$data = array( sanitize_key( $key ) => sanitize_text_field( $value ) );

		$data = wp_unslash( $data );

		$where = array( 'id' => $campaign_id );

		if ( false === $wpdb->update( $wpdb->prefix . 'revenue_campaigns', $data, $where ) ) { //phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			return false;
		}

		$this->clear_campaign_runtime_cache( $campaign_id );
	}





	public function get_cart_product_ids( $contain_parent_id = false ) {
		if ( ! isset( WC()->cart ) ) {
			return array();
		}
		// Check if the product IDs are already stored in the cache
		// $cached_product_ids = wp_cache_get( 'revx_cart_product_ids' );
		// if ( $cached_product_ids !== false ) {

		// return $cached_product_ids;
		// }

		// If not cached, get the cart items and their product IDs
		$product_ids = array();
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			if ( $contain_parent_id ) {
				$product_ids[] = $cart_item['product_id'];
			} else {
				$product_ids[] = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			}
		}

		// Cache the product IDs for future use
		// wp_cache_set( 'revx_cart_product_ids', $product_ids );

		return $product_ids;
	}

	/**
	 * Get a map of cart product IDs to their summed quantities.
	 *
	 * Unlike get_cart_product_ids(), this preserves the quantity of each cart line so
	 * callers can reason about the total quantity of a product in the cart rather than
	 * just its presence. Multiple cart lines for the same product/variation are summed.
	 *
	 * @param bool $contain_parent_id Whether to key by parent product ID instead of variation ID.
	 *
	 * @return array<int,int> Map of product/variation ID => total quantity in cart.
	 */
	public function get_cart_product_quantities( $contain_parent_id = false, $campaign_id = 0 ) {
		if ( ! isset( WC()->cart ) ) {
			return array();
		}

		$campaign_id = absint( $campaign_id );

		$quantities = array();
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			// When a campaign is specified, only count items that were actually added by that
			// campaign (identified by the revx_campaign_id tag). This prevents the same product
			// added manually or by another campaign from being counted toward this campaign.
			if ( $campaign_id ) {
				if ( ! isset( $cart_item['revx_campaign_id'] ) || absint( $cart_item['revx_campaign_id'] ) !== $campaign_id ) {
					continue;
				}
			}

			if ( $contain_parent_id ) {
				$id = $cart_item['product_id'];
			} else {
				$id = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];
			}

			$qty = isset( $cart_item['quantity'] ) ? intval( $cart_item['quantity'] ) : 0;

			if ( isset( $quantities[ $id ] ) ) {
				$quantities[ $id ] += $qty;
			} else {
				$quantities[ $id ] = $qty;
			}
		}

		return $quantities;
	}



	public function convert_to_inline( $styles ) {
		$css = '';
		foreach ( $styles as $property => $value ) {
			if ( is_array( $value ) ) {
				$css .= $this->convert_to_inline( $value );
			} else {
				$css .= "$property: $value;";
			}
		}
		return $css;
	}











	public function get_style( $styles, $name, $type = '' ) {
		$style = isset( $styles[ $name ] ) ? $styles[ $name ] : array();
		$css   = isset( $style['css'] ) ? $style['css'] : '';

		if ( $type ) {
			switch ( $type ) {
				case 'input':
					if ( isset( $style['input'] ) ) {
						return $style['input'];
					}
					// no break
				case 'child':
					if ( isset( $style['child'] ) ) {
						return $style['child'];
					}
					// no break
				case 'classes':
					$classes = isset( $style['classes'] ) ? $style['classes'] : array();
					$classes = implode( ' ', $classes );
					return $classes;

				default:
					// code...
					break;
			}
		}

		return $css;
	}






	/**
	 * Retrive from cache.
	 *
	 * @param string $cache_key Cache key.
	 * @return mixed
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- public method name retained for direct callers.


	/**
	 * Store in cache
	 *
	 * @param string $cache_key Cache key.
	 * @param array  $data Data.
	 * @return void
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- public method name retained for direct callers.









	/**
	 * Calculate sale price based on offer price
	 *
	 * @param string  $type Type.
	 * @param string  $value Value.
	 * @param string  $regular_price Regular Price.
	 * @param integer $quantity Quantity.
	 * @return array
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- public method name retained for direct callers.
	public function calculateSalePrice( $type, $value, $regular_price, $quantity = 1 ) {
		$value           = floatval( $value );
		$save            = array(
			'type'    => '',
			'value'   => 0,
			'content' => '',
		);
		$currency_symbol = '$';

		switch ( $type ) {
			case 'percentage':
				$save = array(
					'type'    => 'percentage',
					'value'   => $value,
					/* translators: %s: Discount percentage value */
					'content' => sprintf( __( 'Save %s%%', 'revenue' ), $value ),
				);
				break;
			case 'fixed_discount':
				$save = array(
					'type'    => 'amount',
					'value'   => $value,
					/* translators: %1s: Currency Symbol, %2s: Value */
					'content' => sprintf( __( 'Save %1$s%2$s', 'revenue' ), $currency_symbol, number_format( $value * $quantity, 2 ) ),
				);
				break;
			case 'no_discount':
				$price = $regular_price;
				break;
			case 'free':
				$price = 0;
				$save  = array(
					'type'    => 'free',
					'value'   => 100,
					'content' => __( 'Free', 'revenue' ),
				);
				break;
			case 'fixed_price':
				$price = $value;
				$save  = array(
					'type'    => 'amount',
					'value'   => abs( $regular_price - $value ),
					/* translators: 1: Currency symbol, 2: Discount amount. */
					'content' => sprintf( __( 'Save %1$s%2$s', 'revenue' ), $currency_symbol, number_format( abs( $regular_price - $value ) * $quantity, 2 ) ),
				);
				break;
			default:
				break;
		}

		return $save;
	}


	/**
	 * Function to get volume discount builder items data based on campaign triggers and offers.
	 *
	 * @param array $campaign Campaign data containing triggers and offers.
	 * @return array Array of volume discount builder items data.
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- public method name retained for direct callers.
	public function getMixMatchQuantities( $campaign ) {
		$offers = $campaign['offers'];

		$regular_price = 100;

		$data = array();

		// Process each offer and calculate sale price.
		foreach ( $offers as $offer ) {
			if ( isset( $offer['type'], $offer['value'] ) ) {

				$sale_data = revenue()->calculateSalePrice( $offer['type'], $offer['value'], $regular_price, $offer['quantity'] );
				$data[]    = array(
					'saveData'    => $sale_data,
					'type'        => $offer['type'],
					'value'       => $offer['value'],
					'quantity'    => $offer['quantity'],
					'isEnableTag' => isset( $offer['isEnableTag'] ) ? $offer['isEnableTag'] : '',
				);
			}
		}

		return $data;
	}

	/**
	 * Function to get buy x get y trigger products based on campaign triggers.
	 *
	 * @param array $campaign Campaign data containing triggers.
	 * @return array Array of products matching the campaign triggers.
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- public method name retained for direct callers.



	/**
	 * Calculate growth
	 *
	 * @param array $current_totals Current totals.
	 * @param array $previous_totals Previous totals.
	 * @param array $data_keys Data keys.
	 * @return array
	 */
	public function calculate_growth( $current_totals, $previous_totals, $data_keys ) {
		$growth_data = array();
		foreach ( $data_keys as $key ) {
			$current_value  = $current_totals[ $key ] ?? 0;
			$previous_value = $previous_totals[ $key ] ?? 0;

			if ( 0 != $previous_value ) {
				$growth_data[ $key ] = ( ( $current_value - $previous_value ) / $previous_value ) * 100;
			} else {
				$growth_data[ $key ] = 0 == $current_value ? 0 : 100; //phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison
			}
		}
		return $growth_data;
	}

	/**
	 * Generte campaigns stats chart data
	 *
	 * @param string $start Dtart Date.
	 * @param string $end End Date.
	 * @param array  $data Data.
	 * @param array  $keys Keys.
	 * @return array
	 */
	public function generate_campaigns_stats_chart_data( $start, $end, $data, $keys ) {
		$date_array = array();
		$cur_date   = new DateTime( $start );
		$end_date   = new DateTime( $end );

		$key_data = array();
		foreach ( $keys as $key ) {
			$key_data[ $key ] = 0;
		}
		while ( $cur_date <= $end_date ) {
			$formated_date                = $cur_date->format( 'Y-m-d' ); // Format date as YYYY-MM-DD.
			$key_data['date']             = $formated_date;
			$date_array[ $formated_date ] = $key_data;

			$cur_date->modify( '+1 day' ); // Increment current date by 1 day.
		}

		return $date_array;
	}

	/**
	 * Get allowed tag
	 *
	 * @return array
	 */
	public function get_allowed_tag() {
		// safe_style_css widening is registered once in register_hooks(), not per call.

		// @todo input is repeated - check back properly
		$allowed_tags = array_merge(
			wp_kses_allowed_html( 'post' ),
			array(
				'style'    => array(),
				'div'      => array(
					'id'               => true,
					'class'            => true,
					'style'            => true,
					'title'            => true,
					'role'             => true,
					'tabindex'         => true,
					'aria-*'           => true,
					'data-*'           => true,
					// Product cards carry these unprefixed attributes; frontend JS
					// (add-to-cart, campaign-total, variation selection, mix & match) reads them.
					'campaign_id'      => true,
					'campaign_type'    => true,
					'product_type'     => true,
					'revx-campaign-id' => true,
				),
				'svg'      => array(
					'xmlns'               => true,
					'xmlns:xlink'         => true,
					'width'               => true,
					'height'              => true,
					'fill'                => true,
					'stroke'              => true,
					'viewbox'             => true,
					'viewBox'             => true,
					'preserveaspectratio' => true,
					'transform'           => true,
					'class'               => true,
					'style'               => true,
					'role'                => true,
					'aria-*'              => true,
				),
				'path'     => array(
					'stroke'          => true,
					'stroke-width'    => true,
					'stroke-linecap'  => true,
					'stroke-linejoin' => true,
					'strokeLinecap'   => true,
					'strokeLinejoin'  => true,
					'strokeWidth'     => true,
					'd'               => true,
					'fill'            => true,
					'fill-rule'       => true,
					'clip-rule'       => true,
					'opacity'         => true,
					'transform'       => true,
				),
				'g'        => array(
					'clip-path' => true,
					'fill'      => true,
					'opacity'   => true,
					'transform' => true,
				),
				'defs'     => array(),
				'clippath' => array(
					'id' => true,
				),
				'circle'   => array(
					'cx'           => true,
					'cy'           => true,
					'r'            => true,
					'stroke'       => true,
					'stroke-width' => true,
					'fill'         => true,
					'opacity'      => true,
					'transform'    => true,
				),
				'rect'     => array(
					'class'     => true,
					'stroke'    => true,
					'width'     => true,
					'height'    => true,
					'x'         => true,
					'y'         => true,
					'rx'        => true,
					'ry'        => true,
					'fill'      => true,
					'opacity'   => true,
					'transform' => true,
				),
				'select'   => array(
					'id'     => true,
					'name'   => true,
					'class'  => true,
					'style'  => true,
					'data-*' => true,
				),
				'option'   => array(
					'value'    => true,
					'selected' => true,
					'disabled' => true,
					'class'    => true,
					'data-*'   => true,
				),
				'input'    => array(
					'id'          => true,
					'name'        => true,
					'checked'     => true,
					'disabled'    => true,
					'readonly'    => true,
					'required'    => true,
					'type'        => true,
					'class'       => true,
					'data-*'      => true,
					'aria-*'      => true,
					'style'       => true,
					'value'       => true,
					'placeholder' => true,
					'step'        => true,
					'min'         => true,
					'max'         => true,
				),
				'label'    => array(
					'for'    => true,
					'id'     => true,
					'class'  => true,
					'style'  => true,
					'data-*' => true,
				),
			)
		);
		return apply_filters( 'revenue_kses_notice_allowed_tags', $allowed_tags );
	}

	/**
	 * Allow display and other CSS properties in wp_kses
	 *
	 * @param array $styles Allowed CSS properties.
	 * @return array Modified array with additional properties.
	 */
	public function allow_display_in_kses( $styles ) {
		$extra_styles = array(
			// Layout / flexbox.
			'display',
			'align-items',
			'justify-content',
			'flex-direction',
			'flex-wrap',
			'gap',
			'column-gap',
			'row-gap',
			'box-sizing',
			// Positioning (used by slider arrows and badge overlays).
			'position',
			'top',
			'right',
			'bottom',
			'left',
			'z-index',
			// Visibility / interaction (used by floating and popup containers).
			'visibility',
			'opacity',
			'cursor',
			'pointer-events',
			'outline',
			'transition',
			'transform',
			// Media sizing (used by product images).
			'object-fit',
			'aspect-ratio',
			// Tables (used by the coupon email template).
			'border-collapse',
			'border-spacing',
			// SVG presentation properties (used by the circular progress ring).
			'fill',
			'stroke',
			'stroke-width',
			'stroke-dasharray',
			'stroke-dashoffset',
			'stroke-linecap',
			'stroke-linejoin',
			// Typography extras.
			'text-shadow',
			'white-space',
			'word-break',
			'text-overflow',
		);

		foreach ( $extra_styles as $extra_style ) {
			if ( ! in_array( $extra_style, $styles, true ) ) {
				$styles[] = $extra_style;
			}
		}

		return $styles;
	}






	/**
	 * Get Product Category ids
	 *
	 * @param string $product_id Product id.
	 * @return array
	 */
	public function get_product_category_ids( $product_id ) {
		$terms = get_the_terms( $product_id, 'product_cat' );
		if ( $terms && ! is_wp_error( $terms ) ) {
			$category_ids = array();
			foreach ( $terms as $term ) {
				$category_ids[] = $term->term_id;
			}
			return $category_ids;
		}
		return array();
	}

	/**
	 * Get Offer Products Data.
	 *
	 * @param array $data Data.
	 * @return array
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- public method name retained for direct callers.
	public function getOfferProductsData( $data ) {
		$result = array();

		foreach ( $data as $order ) {
			// Ensure 'products' is an array of product IDs.
			if ( ! isset( $order['products'] ) || ! is_array( $order['products'] ) ) {
				continue;
			}

			// Process each product ID.
			foreach ( $order['products'] as $product_id ) {
				if ( ! $product_id ) {
					continue;
				}

				// Fetch the product using WC function.
				$product = wc_get_product( $product_id );

				// Ensure product was retrieved successfully.
				if ( ! $product ) {
					continue;
				}

				$regular_price    = (float) $product->get_regular_price( 'edit' );
				$quantity         = isset( $order['quantity'] ) ? (int) $order['quantity'] : 0;
				$discounted_price = $regular_price;
				$discount_amount  = isset( $order['value'] ) ? (float) $order['value'] : 0;

				// Calculate discounted price based on order type.
				if ( isset( $order['type'] ) ) {
					switch ( $order['type'] ) {
						case 'percentage':
							$discount_value   = $discount_amount;
							$discount_amount  = number_format( ( floatval( $regular_price ) * floatval( $discount_value ) ) / 100, 2 );
							$discounted_price = number_format( floatval( $regular_price ) - floatval( $discount_amount ), 2 );

							break;

						case 'fixed_discount':
							$discount_amount  = number_format( $discount_amount, 2 );
							$discounted_price = number_format( floatval( $regular_price ) - floatval( $discount_amount ), 2 );
							break;

						case 'free':
							$discounted_price = '0.00';
							$discount_amount  = '100%';
							break;

						case 'no_discount':
							$discount_amount  = '0';
							$discounted_price = number_format( $regular_price, 2 );
							break;
					}
				}

				// Prepare product data.
				$result[] = array(
					'item_id'       => $product_id,
					'item_name'     => $product->get_name(),
					'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ), // Get the product thumbnail URL.
					'regular_price' => number_format( $regular_price, 2 ),
					'sale_price'    => $discounted_price,
					'save'          => ( 'percentage' === $order['type'] ? ( isset( $order['value'] ) ? $order['value'] : '' ) . '%' : $discount_amount ),
					'quantity'      => $quantity,
					'type'          => $order['type'],
					'value'         => isset( $order['value'] ) ? $order['value'] : '',
					'isEnableTag'   => isset( $order['isEnableTag'] ) ? $order['isEnableTag'] : 'no',
				);
			}
		}

		return $result;
	}

	/**
	 * Get trigger products data.
	 *
	 * @param array  $triggers Triggers Data.
	 * @param string $relation Relation.
	 * @param string $trigger_product_id Trigger product id.
	 * @param string $is_current_product Is current product.
	 * @return array
	 */
	// phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- public method name retained for direct callers.
	public function getTriggerProductsData( $triggers, $relation = 'or', $trigger_product_id = '', $is_current_product = '' ) {
		$result = array();
		if ( $is_current_product ) {
			$_product         = wc_get_product( $trigger_product_id );
			$regular_price    = (float) $_product->get_regular_price();
			$discounted_price = $regular_price;
			$discount_amount  = '0';
			$quantity         = 1;
			$result[]         = array(
				'item_id'       => $trigger_product_id,
				'item_name'     => $_product->get_name(),
				'thumbnail'     => wp_get_attachment_url( $_product->get_image_id() ), // Get the product thumbnail URL.
				'regular_price' => number_format( $regular_price, 2 ),
				'sale_price'    => number_format( $discounted_price, 2 ),
				'save'          => $discount_amount,
				'quantity'      => $quantity,
				'type'          => 'percentage',
				'parent_id'     => $_product->get_parent_id(),
				'value'         => '0',
				'isEnableTag'   => 'no',
				'trigger'       => true,
			);

			return $result;
		}
		if ( ! is_array( $triggers ) ) {
			return $result;
		}

		foreach ( $triggers as $trigger ) {
			if ( 'or' === $relation ) {
				if ( $trigger_product_id && $trigger['item_id'] == $trigger_product_id ) { //phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison

					$product_id = isset( $trigger['item_id'] ) ? (int) $trigger['item_id'] : 0;
					$quantity   = isset( $trigger['item_quantity'] ) ? (int) $trigger['item_quantity'] : 1;

					$product = wc_get_product( $product_id );

					if ( ! $product ) {
						continue;
					}

					$regular_price    = (float) $product->get_regular_price();
					$sale_price       = (float) $product->get_sale_price();
					$discounted_price = 0.0 === $sale_price ? $regular_price : $sale_price;
					$discount_amount  = '0';

					$result = array();

					$result[] = array(
						'item_id'       => $product_id,
						'parent_id'     => $product->get_parent_id(),
						'item_name'     => $product->get_name(),
						'thumbnail'     => wp_get_attachment_url( $product->get_image_id() ), // Get the product thumbnail URL.
						'regular_price' => number_format( $regular_price, 2 ),
						'sale_price'    => number_format( $discounted_price, 2 ),
						'save'          => $discount_amount,
						'quantity'      => $quantity,
						'type'          => 'percentage',
						'value'         => '0',
						'isEnableTag'   => 'no',
						'trigger'       => true,
					);

					return $result;
				}
			}
			$product_id = isset( $trigger['item_id'] ) ? (int) $trigger['item_id'] : 0;
			$quantity   = isset( $trigger['item_quantity'] ) ? (int) $trigger['item_quantity'] : 1;

			$_product = wc_get_product( $product_id );

			if ( ! $_product ) {
				continue;
			}

			$regular_price    = (float) $_product->get_regular_price();
			$sale_price       = (float) $_product->get_sale_price();
			$discounted_price = 0.0 === $sale_price ? $regular_price : $sale_price;
			$discount_amount  = '0';

			// Prepare product data.
			$result[] = array(
				'item_id'       => $product_id,
				'item_name'     => $_product->get_name(),
				'thumbnail'     => wp_get_attachment_url( $_product->get_image_id() ), // Get the product thumbnail URL.
				'regular_price' => number_format( $regular_price, 2 ),
				'sale_price'    => number_format( $discounted_price, 2 ),
				'save'          => $discount_amount,
				'quantity'      => $quantity,
				'type'          => 'percentage',
				'value'         => '0',
				'isEnableTag'   => 'no',
				'parent_id'     => $_product->get_parent_id(),
				'trigger'       => true,
			);
		}

		return $result;
	}





	/**
	 * Get edit campaign url
	 *
	 * @param string $campaign_id campaign id.
	 * @return string
	 */
	public function get_edit_campaign_url( $campaign_id = '' ) {
		$slug = revenue()->get_admin_menu_slug();

		return esc_url( admin_url( 'admin.php?page=' . $slug . '#/campaigns/' . $campaign_id ) );
	}








	/**
	 * Get campaign position default values.
	 *
	 * @param string $page Page.
	 * @return array
	 */
	public function get_campaign_position_default_values( $page = '' ) {
		$is_cart_page_use_block = has_block( 'woocommerce/cart', intval( get_option( 'woocommerce_cart_page_id' ) ) );

		$is_checkout_page_use_block = has_block( 'woocommerce/checkout', intval( get_option( 'woocommerce_checkout_page_id' ) ) );

		$data = array(
			'product_page'  => array(
				'campaign_inpage_position' => 'before_add_to_cart_form',
			),
			'cart_page'     => array(
				'campaign_inpage_position' => $is_cart_page_use_block ? 'before_content' : 'before_cart',
			),
			'checkout_page' => array(
				'campaign_inpage_position' => $is_checkout_page_use_block ? 'before_content' : 'before_checkout_form',
			),
			'thankyou_page' => array(
				'campaign_inpage_position' => 'before_thankyou',
			),
		);

		return apply_filters( 'revenue_campaign_positions_default_value', $data );
	}


	/**
	 * Check if a specific product ID exists in the WooCommerce cart.
	 *
	 * @param int $campaign_id The campaign id.
	 * @param int $product_id The product ID to check.
	 * @return bool True if the product is in the cart, false otherwise.
	 */
	public function is_product_in_cart( $campaign_id, $product_id ) {
		// Load cart product IDs only once.
		if ( is_null( self::$cart_product_ids ) ) {
			self::load_cart_product_ids();
		}

		// Check if the given product ID exists in the cart.
		return isset( self::$cart_product_ids[ $campaign_id ][ $product_id ] );
	}

	/**
	 * Load product IDs from the cart.
	 */
	private static function load_cart_product_ids() {
		$cart_items             = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart() : array();
		self::$cart_product_ids = array();

		foreach ( $cart_items as $cart_item ) {
			if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
				self::$cart_product_ids[ $cart_item['revx_campaign_id'] ][ $cart_item['product_id'] ] = true;
			}
		}
	}

	/**
	 * Is hide campaign
	 *
	 * @param int|string $campaign_id Campaign id.
	 * @return boolean
	 */
	public function is_hide_campaign( $campaign_id, $campaign_type = '' ) {
		$is_hide = 'hide_campaign' === revenue()->get_campaign_meta( $campaign_id, 'offered_product_on_cart_action', true );

		$hideable_types = apply_filters(
			'revenue_campaign_hideable_on_cart_types',
			array( 'normal_discount', 'bundle_discount', 'buy_x_get_y', 'volume_discount' )
		);

		$is_hidden_appicable = in_array( $campaign_type, $hideable_types, true );

		return $is_hide && $this->is_campaign_on_cart( $campaign_id ) && $is_hidden_appicable;
	}

	/**
	 * Is Hide product
	 *
	 * @param int|string $campaign_id Campaign ID.
	 * @param int|string $product_id Product ID.
	 * @return boolean
	 */
	public function is_hide_product( $campaign_id, $product_id ) {
		$is_hide = 'hide_products' === revenue()->get_campaign_meta( $campaign_id, 'offered_product_on_cart_action', true );
		return $is_hide && $this->is_product_in_cart( $campaign_id, $product_id );
	}

	/**
	 * Is campaign or cart.
	 *
	 * @param string|int $campaign_id Campaign id.
	 * @return boolean
	 */
	public function is_campaign_on_cart( $campaign_id ) {
		// Load cart product IDs only once.
		if ( is_null( self::$cart_product_ids ) ) {
			self::load_cart_product_ids();
		}
		return isset( self::$cart_product_ids[ $campaign_id ] ) && ! empty( self::$cart_product_ids[ $campaign_id ] );
	}

	/**
	 * Get Placement Settings
	 *
	 * @param int|string $campaign_id Campaign ID.
	 * @param string     $placement Placement.
	 * @param string     $key Key.
	 * @return array
	 */
	public function get_placement_settings( $campaign_id, $placement = '', $key = '' ) {

		if ( empty( $placement ) ) {
			$placement = $this->get_current_page();
		}

		$campaign = revenue()->get_campaign_data( $campaign_id );
		if ( isset( $campaign['campaign_placement'] ) && 'multiple' != $campaign['campaign_placement'] ) {
			$campaign['placement_settings'] = array(
				$campaign['campaign_placement'] => array(
					'page'                     => $campaign['campaign_placement'],
					'status'                   => 'yes',
					'display_style'            => $campaign['campaign_display_style'],
					'builder_view'             => $campaign['campaign_builder_view'],
					'inpage_position'          => $campaign['campaign_inpage_position'],
					'popup_animation'          => $campaign['campaign_popup_animation'],
					'popup_animation_delay'    => $campaign['campaign_popup_animation_delay'],
					'floating_position'        => $campaign['campaign_floating_position'] ?? 'top_left',
					'floating_animation_delay' => $campaign['campaign_floating_animation_delay'],
				),
			);

			$campaign['placement_settings'] = $campaign['placement_settings'];
		}

		$placement_settings = $campaign['placement_settings'];

		if ( ! empty( $placement ) ) {
			$placement_settings = $placement_settings[ $placement ] ?? array();

			if ( empty( $placement_settings ) && isset( $campaign['placement_settings']['all_page'] ) && 'yes' === $campaign['placement_settings']['all_page']['status'] ) {
				$placement_settings = $campaign['placement_settings']['all_page'];
			}

			if ( ! empty( $key ) ) {
				$placement_settings = $placement_settings[ $key ] ?? '';
			}
		}

		return $placement_settings;
	}


	/**
	 * Get current page is what (product,cart or checkout page)
	 *
	 * @return string
	 */
	public function get_current_page() {
		$which_page = '';
		if ( is_product() ) {
			$which_page = 'product_page';
		} elseif ( is_cart() ) {
			$which_page = 'cart_page';
		} elseif ( is_shop() ) {
			$which_page = 'shop_page';
		} elseif ( is_order_received_page() ) {
			$which_page = 'thankyou_page';
		} elseif ( is_account_page() ) {
			$which_page = 'my_account';
		} elseif ( is_checkout() ) {
			$which_page = 'checkout_page';
		}

		return $which_page;
	}

	/**
	 * Is Custom order table usages enabled or not
	 *
	 * @return boolean
	 */
	public function is_custom_orders_table_usages_enabled() {
		return OrderUtil::custom_orders_table_usage_is_enabled();
	}



	public function get_buyx_gety_individual_product_quantity_trigger_types() {

		$trigger_types = array( 'products' );

		return apply_filters( 'revenue_get_buy_x_get_y_individual_product_quantity_trigger_types', $trigger_types );
	}

	public function get_mix_match_required_product_trigger_types() {

		$trigger_types = array( 'products' );

		return apply_filters( 'revenue_get_mix_match_required_product_trigger_types', $trigger_types );
	}


	public function get_trigger_placeholder_message() {
		$trigger_messages = array(
			'products' => __( 'Search and select products...', 'revenue' ),
			'category' => __( 'Search and select categories...', 'revenue' ),
		);

		return apply_filters( 'revenue_trigger_items_placeholder_messages', $trigger_messages );
	}

	public function get_offered_items_type() {

		$types = array(
			'products'     => 'products',
			'all_products' => 'products',
			'category'     => 'products',
		);

		return apply_filters( 'revenue_offered_items_type', $types );
	}

	public function get_selected_trigger_item_suffix() {

		// type => suffix
		$suffix = array(
			'products' => __( 'Product', 'revenue' ),
			'category' => __( 'Category', 'revenue' ),
		);

		return apply_filters( 'revenue_trigger_selected_item_suffix', $suffix );
	}

	public function get_selected_offer_item_suffix() {

		// type => suffix
		$suffix = array(
			'products' => __( 'Product', 'revenue' ),
			'category' => __( 'Category', 'revenue' ),
		);

		return apply_filters( 'revenue_offer_selected_item_suffix', $suffix );
	}

	public function get_item_not_found_messages() {

		$messages = array(
			'products' => __( 'Oops, Product not found!', 'revenue' ),
			'category' => __( 'Oops, Category not found', 'revenue' ),
		);

		return apply_filters( 'revenue_get_item_not_found_messages', $messages );
	}



	public function is_show_bundle_with_trigger_product() {
		$data = array(
			'products',
			'category',
			'all_products',
		);

		return apply_filters( 'revenue_is_show_bundle_with_trigger_product', $data );
	}

	public function get_campaign_list_trigger_row() {
		$data = array(
			'products' => array(
				'singular' => 'Product',
				'plural'   => 'Products',
			),
			'category' => array(
				'singular' => 'Category',
				'plural'   => 'Categories',
			),
		);

		return apply_filters( 'revenue_get_campaign_list_trigger_row_message', $data );
	}


	public function show_quantity_selector_on_campaigns() {

		$allowed_campaign_types = array(
			'normal_discount',
			'mix_match',
			'bundle_discount',
			'frequently_bought_together',
			'buy_x_get_y',
			'free_shipping_bar',
			'spending_goal',
		);

		return apply_filters( 'revenue_get_show_quantity_selector_on_campaigns', $allowed_campaign_types );
	}

	/**
	 * Campaign types whose trigger search returns variations in place of the parent product.
	 *
	 * @param string $campaign_type Campaign type slug.
	 * @return bool
	 */
	public function trigger_search_returns_variations( $campaign_type ) {
		$types = apply_filters( 'revenue_campaign_variation_trigger_search_types', array() );

		return in_array( $campaign_type, (array) $types, true );
	}

	/**
	 * Campaign types whose trigger search expands a variable product into its variations.
	 *
	 * @param string $campaign_type Campaign type slug.
	 * @return bool
	 */
	public function trigger_search_expands_variable( $campaign_type ) {
		$types = apply_filters( 'revenue_campaign_expand_variable_trigger_search_types', array() );

		return in_array( $campaign_type, (array) $types, true );
	}

	/**
	 * Get campaign default placement page
	 *
	 * @param string $campaign_type Campaign Type.
	 */
	public function get_campaign_default_placement( $campaign_type = false ) {
		$data = array(
			'normal_discount'            => 'product_page',
			'buy_x_get_y'                => 'product_page',
			'volume_discount'            => 'product_page',
			'frequently_bought_together' => 'product_page',
			'mix_match'                  => 'product_page',
			'bundle_discount'            => 'product_page',
			'double_order'               => 'checkout_page',
			'spending_goal'              => 'product_page',
			'free_shipping_bar'          => 'product_page',
			'stock_scarcity'             => 'product_page',
			'countdown_timer'            => 'product_page',
			'next_order_coupon'          => 'thankyou_page',
		);

		$data = apply_filters( 'revenue_get_campaign_default_placement', $data );

		return $campaign_type ? $data[ $campaign_type ] : $data;
	}




	/**
	 * Get campaign counts
	 *
	 * @return mixed
	 */
	public function get_campaign_counts() {
		global $wpdb;
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$res = $wpdb->get_row(
			"SELECT
                COUNT(*) AS total_campaigns,
                SUM(CASE WHEN campaign_type = 'normal_discount' THEN 1 ELSE 0 END) AS normal_discount,
                SUM(CASE WHEN campaign_type = 'volume_discount' THEN 1 ELSE 0 END) AS volume_discount,
                SUM(CASE WHEN campaign_type = 'bundle_discount' THEN 1 ELSE 0 END) AS bundle_discount,
                SUM(CASE WHEN campaign_type = 'buy_x_get_y' THEN 1 ELSE 0 END) AS buy_x_get_y,
				SUM(CASE WHEN campaign_type = 'stock_scarcity' THEN 1 ELSE 0 END) AS stock_scarcity,
                SUM(CASE WHEN campaign_type = 'free_shipping_bar' THEN 1 ELSE 0 END) AS free_shipping_bar,
                SUM(CASE WHEN campaign_type = 'next_order_coupon' THEN 1 ELSE 0 END) AS next_order_coupon,
                SUM(CASE WHEN campaign_type = 'countdown_timer' THEN 1 ELSE 0 END) AS countdown_timer
            FROM {$wpdb->prefix}revenue_campaigns;"
		); //phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

		return $res;
	}


	/**
	 * Sanitize a posted attribute array or JSON string
	 *
	 * @param mixed $data Array or JSON string from $_POST.
	 * @return array Sanitized associative array
	 */
	public function sanitize_posted_attributes( $data ) {
		// Remove slashes added by WordPress.
		$data = wp_unslash( $data );

		// Decode JSON if needed.
		if ( is_string( $data ) ) {
			$data = json_decode( $data, true );
		}

		// Ensure it's an array.
		if ( ! is_array( $data ) ) {
			return array();
		}

		// Sanitize each key and value.
		//
		// Do NOT use sanitize_text_field()/wc_clean() here: it strips percent
		// encoded octets, and WooCommerce stores attribute term slugs for any
		// non latin language (Hebrew, Arabic, Russian, ...) percent encoded,
		// e.g. "1 ליטר" is saved as "1-%d7%9c%d7%99%d7%98%d7%a8". Stripping the
		// octets leaves "1-", which WC_Cart::add_to_cart() then rejects with
		// "Invalid value posted for <attribute>". See the same warning in
		// WooCommerce: includes/class-wc-cart.php ("Don't use wc_clean as it
		// destroys sanitized characters.").
		//
		// The values are handed straight to WC_Cart::add_to_cart(), which
		// sanitizes each one against its own attribute (sanitize_title() for
		// taxonomy attributes, wc_clean() for custom ones) and rejects anything
		// that is not a valid value, so keeping them intact here is safe.
		$sanitized = array();
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				continue;
			}

			// sanitize_title() keeps percent encoded octets intact.
			$key = sanitize_title( (string) $key );

			if ( '' === $key ) {
				continue;
			}

			$sanitized[ $key ] = trim( wp_strip_all_tags( (string) $value ) );
		}

		return $sanitized;
	}

	/**
	 * Get the template file path for a campaign view.
	 *
	 * @param array  $campaign Campaign data array.
	 * @param string $view_type The view type (e.g., 'inpage', 'popup', 'floating').
	 * @param string $folder_path Optional. The folder path for the template.
	 * @return string File path to the template.
	 */
	public function get_campaign_path( $campaign, $view_type = 'inpage', $folder_path = '' ) {
		$file_path = REVENUE_PATH . 'includes/campaigns/views/' . $folder_path . '/template1.php';

		$resolved_path = apply_filters( 'revenue_campaign_template_path', $file_path, $campaign, $view_type, $folder_path );

		return is_string( $resolved_path ) && is_readable( $resolved_path ) ? $resolved_path : $file_path;
	}

	public function load_popup_assets() {
		wp_enqueue_script( 'revenue-popup' );
		wp_enqueue_style( 'revenue-popup' );
	}

	public function load_floating_assets() {
		wp_enqueue_script( 'revenue-floating' );
		wp_enqueue_style( 'revenue-floating' );
	}

	/**
	 * Check RTL direction
	 *
	 * @since 1.0.0
	 * @return bool True if RTL, false otherwise
	 */
	public function is_rtl() {
		// Detect RTL direction (WordPress helper) and expose to template data
		return function_exists( 'is_rtl' ) ? is_rtl() : false;
	}
}
