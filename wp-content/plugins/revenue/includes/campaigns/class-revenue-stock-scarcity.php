<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
/**
 * @package Revenue
 */

namespace Revenue;

defined( 'ABSPATH' ) || exit;

use Revenue\Services\Revenue_Product_Context;

/**
 * WowRevenue Campaign: Stock Scarcity
 *
 * @hooked on init
 */
class Revenue_Stock_Scarcity {
	use SingletonTrait;

	/**
	 * Stores the campaigns to be rendered on the page.
	 *
	 * @var array|null $campaigns
	 *    An array of campaign data organized by view types (e.g., in-page, popup, floating),
	 *    or null if no campaigns are set.
	 */
	public $campaigns = array();

	/**
	 * Keeps track of the current position for rendering in-page campaigns.
	 *
	 * @var string $current_position
	 *    The position within the page where in-page campaigns should be displayed.
	 *    Default is an empty string, indicating no position is set.
	 */
	public $current_position = '';

	/**
	 * Initializes the class.
	 */
	public function init() {
		add_action( 'wp', array( $this, 'wsx_get_product_id_after_everything_loaded' ) );
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'wsx_store_campaign_data_for_cart' ), 10, 2 );
		add_action( 'wp_footer', array( $this, 'rvex_add_hidden_page_type_field' ) );
		add_action( 'wp_ajax_revenue_update_product_views', array( $this, 'update_product_views_ajax' ) );
		add_action( 'wp_ajax_nopriv_revenue_update_product_views', array( $this, 'update_product_views_ajax' ) );
	}

	/**
	 * Update product views count via AJAX.
	 *
	 * @return void
	 */
	public function update_product_views_ajax() {
		check_ajax_referer( 'revenue-add-to-cart', 'security' );
		if ( ! isset( $_POST['product_id'], $_POST['campaign_id'] ) ) {
			wp_send_json_error( 'Missing parameters', 400 );
			return;
		}

		$validation  = array( 'options' => array( 'min_range' => 1 ) );
		$product_id  = filter_var( wp_unslash( $_POST['product_id'] ), FILTER_VALIDATE_INT, $validation );
		$campaign_id = filter_var( wp_unslash( $_POST['campaign_id'] ), FILTER_VALIDATE_INT, $validation );

		if ( ! $product_id || empty( $campaign_id ) ) {
			wp_send_json_error( 'Invalid data', 400 );
			return;
		}

		$campaign = revenue()->get_raw_campaign( $campaign_id );
		if ( ! $campaign || 'publish' !== $campaign->campaign_status || 'stock_scarcity' !== $campaign->campaign_type ) {
			wp_send_json_error( 'Campaign not found', 404 );
			return;
		}

		// Public tracking must not mutate private product metadata.
		// Load the product object.
		$product = wc_get_product( $product_id );
		if ( ! $product || 'publish' !== $product->get_status() ) {
			wp_send_json_error( 'Product not found', 404 );
			return;
		}

		// Update view count.
		$count = (int) $product->get_meta( $campaign_id . '_views_counter', true );
		update_post_meta( $product_id, $campaign_id . '_views_counter', $count + 1 );
		wp_send_json_success( array( 'new_count' => $count + 1 ) );
	}
	/**
	 * Add hidden field to store current page type.
	 *
	 * @return void
	 */
	public function rvex_add_hidden_page_type_field() {
		$current_page = 'unknown';

		if ( is_shop() ) {
			$current_page = 'shop_page';
		} elseif ( is_product() ) {
			$current_page = 'product_page';
		} elseif ( is_cart() ) {
			$current_page = 'cart_page';
		}

		echo '<input type="hidden" id="wsx_current_page" value="' . esc_attr( $current_page ) . '">';
	}
	/**
	 * Store campaign data for cart.
	 *
	 * @param array $cart_item_data The cart item data.
	 * @param int   $product_id The product ID.
	 *
	 * @return array
	 */
	public function wsx_store_campaign_data_for_cart( $cart_item_data, $product_id ) {
		// stock scarcity.
		$positions = array(
			'rvex_below_the_product_title',
			'rvex_below_the_product_price',
			'before_add_to_cart_quantity',
			'cart_item_price',
			'after_cart_item_name',
			'after_shop_loop_item_title',
			'shop_loop_item_title',
		);
		// check current page is shop page or product page and cart page.
		// Public cart context hint; campaign eligibility is computed server-side below.
		$posted_page  = isset( $_POST['wsx_current_page'] ) ? sanitize_key( wp_unslash( $_POST['wsx_current_page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce public add-to-cart context, not privileged form data.
		$current_page = '';
		if ( is_product() ) {
			$current_page = 'product_page';
		} elseif ( is_shop() ) {
			$current_page = 'shop_page';
		} elseif ( is_cart() ) {
			$current_page = 'cart_page';
		} elseif ( in_array( $posted_page, array( 'product_page', 'shop_page', 'cart_page' ), true ) ) {
			$current_page = $posted_page;
		} else {
			$current_page = 'shop_page';
		}
		// Loop through each position and fetch campaigns.
		foreach ( $positions as $position ) {
			$campaigns = revenue()->get_available_campaigns( $product_id, $current_page, 'inpage', $position, false, false, 'stock_scarcity' );

			if ( ! empty( $campaigns ) ) {
				foreach ( $campaigns as $key => $campaign ) {
					$cart_item_data['revx_campaign_id_stock_scarcity']   = $campaign['id'];
					$cart_item_data['revx_campaign_type_stock_scarcity'] = $campaign['campaign_type'];
					revenue()->increment_campaign_add_to_cart_count( $campaign['id'] );
					break; // Stop after setting campaign data for one position.
				}
			}
		}
		return $cart_item_data;
	}


	/**
	 * Get product ID after everything is loaded.
	 *
	 * @return void
	 */
	public function wsx_get_product_id_after_everything_loaded() {
		if ( is_product() ) {
			global $wp_query;

			if ( ! empty( $wp_query->post ) ) {
				$product_id = $wp_query->post->ID;

				// List of positions.
				$positions = array(
					'rvex_below_the_product_title',
					'rvex_below_the_product_price',
					'before_add_to_cart_quantity',
					'cart_item_price',
					'after_cart_item_name',
					'after_shop_loop_item_title',
					'shop_loop_item_title',
				);

				// Loop through each position and fetch campaigns.
				foreach ( $positions as $position ) {
					$campaigns = revenue()->get_available_campaigns(
						$product_id,
						'product_page',
						'inpage',
						$position,
						false,
						false,
						'stock_scarcity'
					);
					$product   = wc_get_product( $product_id );
					if ( ! empty( $campaigns ) && $product->get_stock_quantity() > 0 ) {
						add_filter( 'woocommerce_get_stock_html', fn( $html, $product ) => '', 10, 2 );
					}
				}
			}
		}
	}

	/**
	 * Outputs in-page views for a list of campaigns.
	 *
	 * This method processes and renders in-page views based on the provided campaigns.
	 * It adds each campaign to the `inpage` section of the `campaigns` array and then
	 * calls `render_views` to output the HTML.
	 *
	 * @param array $campaigns An array of campaigns to be displayed.
	 * @param array $data An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function output_inpage_views( $campaigns, $data ) {
		foreach ( $campaigns as $campaign ) {
			$this->campaigns['inpage'][ $data['position'] ][] = $campaign;
		}
		$this->current_position = $data['position'];
		do_action( 'revenue_campaign_stock_scarcity_inpage_before_render_content' );
		$this->render_views( $data );
		do_action( 'revenue_campaign_stock_scarcity_inpage_after_render_content' );
	}

	/**
	 * Renders and outputs views for the campaigns.
	 *
	 * This method generates HTML output for different types of campaign views:
	 * - In-page views
	 * - Popup views
	 * - Floating views
	 *
	 * It includes the respective PHP files for each view type and processes them.
	 * The method also enqueues necessary scripts and styles for popup and floating views.
	 *
	 * @param array $data An array of data to be passed to the view.
	 *
	 * @return void
	 */
	public function render_views( $data = array() ) {
		if ( ! empty( $this->campaigns['inpage'][ $this->current_position ] ) ) {
			$campaigns = $this->campaigns['inpage'][ $this->current_position ];

			$campaign = $campaigns[0];

			wp_enqueue_script( 'revenue-campaign-stock-scarcity' );
			wp_enqueue_style( 'revenue-campaign-stock-scarcity' );

			revenue()->update_campaign_impression( $campaign['id'] );
			$output = '';

			$file_path = revenue()->get_campaign_path( $campaign, 'inpage', 'stock-scarcity' );

			// Existing Filepath:  REVENUE_PATH . 'includes/campaigns/views/stock-scarcity/template1.php'.
			$file_path = apply_filters( 'revenue_campaign_view_path', $file_path, 'stock_scarcity', 'inpage', $campaign );
			if ( file_exists( $file_path ) ) {
				do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
				// Template vars supplied by the caller (no extract()).
				$display_type = $data['display_type'] ?? '';
				$placement    = $data['placement'] ?? '';
				$position     = $data['position'] ?? '';
				include $file_path;
			}
		}
	}



	/**
	 * Get distinct user count by product ID.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return int The distinct user count for the specified product.
	 */
	public function get_distinct_user_count_by_product( $product_id ) {
		global $wpdb;

		$product_id = intval( $product_id );

		$query = $wpdb->prepare(
			"
			SELECT COUNT(DISTINCT customer_id)
			FROM {$wpdb->prefix}wc_order_product_lookup
			WHERE product_id = %d
			  AND customer_id IS NOT NULL
		",
			$product_id
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		// The query is already prepared via $wpdb->prepare() above.
		// Direct database call is necessary for accessing WooCommerce analytics table.
		// Caching is not needed as this is real-time data.
		return (int) $wpdb->get_var( $query );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	}


	/**
	 * Renders and outputs a shortcode view for a single campaign.
	 *
	 * This method generates HTML output for a campaign view by including the
	 * in-page view PHP file. It also updates the campaign impression count based on
	 * whether a product is available.
	 *
	 * @param array $campaign The campaign data to be rendered.
	 * @param array $data Data.
	 *
	 * @return void
	 */
	public function render_shortcode( $campaign, $data = array() ) {
		if ( is_array( $campaign ) ) {
			revenue()->update_campaign_impression( $campaign['id'] );
		} else {
			return;
		}
		if ( is_product() ) {

			$this->run_shortcode(
				$campaign,
				array(
					'display_type' => 'inpage',
					'position'     => '',
					'placement'    => 'product_page',
				)
			);
		} elseif ( is_cart() ) {
			$which_page = 'cart_page';

			if ( WC()->cart && ! WC()->cart->is_empty() ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					Revenue_Product_Context::set_product_context( $cart_item['product_id'] );
					$this->run_shortcode(
						$campaign,
						array(
							'display_type' => 'inpage',
							'position'     => '',
							'placement'    => 'cart_page',
						)
					);
					Revenue_Product_Context::clear_product_context();
				}
			}
		} elseif ( is_shop() ) {
			$which_page = 'shop_page';
		} elseif ( is_checkout() ) {
			if ( WC()->cart && ! WC()->cart->is_empty() ) {
				foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
					Revenue_Product_Context::set_product_context( $cart_item['product_id'] );
					$this->run_shortcode(
						$campaign,
						array(
							'display_type' => 'inpage',
							'position'     => '',
							'placement'    => 'checkout_page',
						)
					);
					Revenue_Product_Context::clear_product_context();
				}
			}
		}
	}

	/**
	 * Run shortcode
	 *
	 * @param array $campaign Campaign.
	 * @param array $data Data.
	 * @return mixed
	 */
	public function run_shortcode( $campaign, $data = array() ) {
		wp_enqueue_style( 'revenue-campaign' );
		wp_enqueue_style( 'revenue-campaign-buyx_gety' );
		wp_enqueue_style( 'revenue-campaign-volume' );
		do_action( 'revenue_enqueue_campaign_assets', $campaign['campaign_type'] );
		wp_enqueue_style( 'revenue-utility' );
		wp_enqueue_style( 'revenue-responsive' );
		wp_enqueue_script( 'revenue-campaign' );
		wp_enqueue_script( 'revenue-slider' );
		wp_enqueue_script( 'revenue-add-to-cart' );
		wp_enqueue_script( 'revenue-variation-product-selection' );
		wp_enqueue_script( 'revenue-checkbox-handler' );
		wp_enqueue_script( 'revenue-campaign-total' );
		wp_enqueue_script( 'revenue-countdown' );
		wp_enqueue_script( 'revenue-animated-add-to-cart' );
		wp_enqueue_style( 'revenue-animated-add-to-cart' );

		$file_path_prefix = apply_filters( 'revenue_campaign_file_path', REVENUE_PATH, $campaign['campaign_type'], $campaign );

		// Replace underscores with hyphens in the campaign type.
		$campaign_type = isset( $campaign['campaign_type'] ) ? str_replace( '_', '-', $campaign['campaign_type'] ) : 'normal-discount';

		$file_path = false;
		if ( isset( $campaign['campaign_display_style'] ) ) {

			switch ( $campaign['campaign_display_style'] ) {
				case 'inpage':
				case 'multiple':
					$file_path = revenue()->get_campaign_path( $campaign, 'inpage', $campaign_type );
					break;
				case 'popup':
					$file_path = revenue()->get_campaign_path( $campaign, 'popup', $campaign_type );
					break;
				case 'floating':
					$file_path = revenue()->get_campaign_path( $campaign, 'floating', $campaign_type );
					break;
				default:
					$file_path = revenue()->get_campaign_path( $campaign, 'inpage', $campaign_type );
					break;
			}
		}

		$file_path = apply_filters( 'revenue_campaign_shortcode_file_path', $file_path, $campaign );

		ob_start();
		if ( file_exists( $file_path ) ) {
			do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
			// Template vars supplied by the caller (no extract()).
			$display_type = $data['display_type'] ?? '';
			$placement    = $data['placement'] ?? '';
			$position     = $data['position'] ?? '';
			?>
				<div class="revenue-campaign-shortcode">
					<?php
						$position = 'inpage';
						include $file_path;
					?>
				</div>

			<?php
		}

		$output = ob_get_clean();

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		$rest_prefix         = trailingslashit( rest_get_url_prefix() );
		$is_rest_api_request = ( false !== strpos( $request_uri, $rest_prefix ) );

		if ( $is_rest_api_request ) {
			return $output;
		} else {
			echo wp_kses( $output, revenue()->get_allowed_tag() );
		}
	}
}
