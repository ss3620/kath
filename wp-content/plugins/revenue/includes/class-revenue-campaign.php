<?php //phpcs:ignore Generic.Files.LineEndings.InvalidEOLChar
namespace Revenue;

use WC_Shipping_Free_Shipping;
use DateTime;
use Revenue\Services\Revenue_Product_Context;

/**
 * Revenue Campaign
 *
 * @hooked on init
 */
class Revenue_Campaign {

	/**
	 * Contains campaigns
	 *
	 * @var array
	 */
	public $campaigns = array();

	/**
	 * Contain stock scarcities
	 *
	 * @var array
	 */
	public $stock_scacities = array();

	/**
	 * Contain campaign additional data
	 *
	 * @var array
	 */
	public $campaign_additional_data = array();

	/**
	 * Contain countdown data
	 *
	 * @var array
	 */
	public $countdown_data = array();

	/**
	 * Contain animated button data.
	 *
	 * @var array
	 */
	public $animated_button_data = array();

	/**
	 * Contain free shipping status
	 *
	 * @var boolean
	 */
	public $is_free_shipping = false;

	/**
	 * Contain spending goals
	 *
	 * @var array
	 */
	public $spending_goals = array();

	/**
	 * Contain status of is script already enqueued or not.
	 *
	 * @var boolean
	 */
	public $is_enqueue_data_already = false;

	/**
	 * Contain is already render or not
	 *
	 * @var integer
	 */
	public $is_already_render = 0;

	/**
	 * Contain rendered campaings
	 *
	 * @var array
	 */
	public $renderd_campaigns = array();
	/**
	 * Flag to avoid infinite loops when removing a bundle parent via a child.
	 *
	 * @var string
	 */
	protected $removing_container_key = null;


	/**
	 * Contain revenue price
	 *
	 * @var array
	 */
	public $revenue_price = array();

	/**
	 * Checkout filters
	 *
	 * @var array
	 */
	private $checkout_filters = array(
		'revenue_block_checkout_before_order_review',
		'revenue_block_add_text_after_billing_form',
		'revenue_block_before_checkout_billing_form',
		'revenue_block_before_checkout_form',
		'revenue_block_review_order_before_payment',
		'revenue_block_review_order_after_payment',
		'revenue_block_after_checkout_form',
	);

	/**
	 * Cart filters
	 *
	 * @var array
	 */
	private $cart_filters = array(
		'revenue_block_before_cart_table',
		'revenue_block_after_cart_table',
		'revenue_block_before_cart_form',
		'revenue_block_after_cart_form',
		'revenue_block_before_cart_totals',
		'revenue_block_after_cart_totals',
		'revenue_block_proceed_to_checkout',
	);

	/**
	 * Registry for block injection (blockName => array of ['filter' => ..., 'position' => 'before'|'after'])
	 *
	 * @var array
	 */
	private $block_injection_registry = array();

	/**
	 * Whether the render_block_data filter has been registered.
	 *
	 * @var bool
	 */
	private $render_block_data_registered = false;

	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'revenue_item_added_to_cart', array( $this, 'after_add_to_cart' ), 10, 3 );

		add_action( 'woocommerce_remove_cart_item', array( $this, 'after_remove_cart_item' ), 10, 2 );

		add_action( 'woocommerce_cart_item_restored', array( $this, 'after_cart_item_restored' ), 10, 2 );

		add_action( 'woocommerce_cart_emptied', array( $this, 'after_cart_emptied' ) );

		add_action( 'woocommerce_add_to_cart', array( $this, 'woocommerce_after_add_to_cart' ), 10, 6 );

		add_action( 'woocommerce_before_calculate_totals', array( $this, 'woocommerce_before_calculate_totals' ) );

		add_action( 'woocommerce_check_cart_items', array( $this, 'woocommerce_check_cart_items' ) );

		add_action( 'woocommerce_cart_item_remove_link', array( $this, 'woocommerce_cart_item_remove_link' ), 10, 2 );

		add_action( 'woocommerce_cart_item_quantity', array( $this, 'woocommerce_cart_item_quantity' ), 10, 2 );

		add_action( 'woocommerce_cart_item_class', array( $this, 'woocommerce_cart_item_class' ), 10, 2 );

		add_action( 'woocommerce_cart_item_subtotal', array( $this, 'woocommerce_cart_item_subtotal' ), 10, 2 );

		add_action( 'woocommerce_cart_item_price', array( $this, 'woocommerce_cart_item_price' ), 10, 2 );

		add_action( 'woocommerce_get_item_data', array( $this, 'woocommerce_get_item_data' ), 10, 2 );

		add_action( 'woocommerce_cart_item_name', array( $this, 'woocommerce_cart_item_name' ), 10, 2 );

		add_action( 'woocommerce_after_cart_item_quantity_update', array( $this, 'woocommerce_after_cart_item_quantity_update' ), 10, 2 );

		add_action( 'woocommerce_store_api_product_quantity_minimum', array( $this, 'woocommerce_store_api_product_quantity_minimum' ), 10, 3 );

		add_action( 'woocommerce_store_api_product_quantity_maximum', array( $this, 'woocommerce_store_api_product_quantity_maximum' ), 10, 3 );

		add_action( 'woocommerce_checkout_order_processed', array( $this, 'woocommerce_checkout_create_order' ), 10 );

		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'woocommerce_checkout_create_order' ), 10 );

		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'woocommerce_checkout_create_order_line_item' ), 10, 4 );

		add_action( 'woocommerce_hidden_order_itemmeta', array( $this, 'woocommerce_hidden_order_itemmeta' ) );

		add_shortcode( revenue()->get_campaign_shortcode_tag(), array( $this, 'render_campaign_view_shortcode' ) );

		add_filter( 'woocommerce_package_rates', array( $this, 'handle_free_shipping' ), 10, 2 );

		add_action( 'wp', array( $this, 'run_all_page_campaigns' ) );

		add_action( 'wp_print_scripts', array( $this, 'localize_script' ) );

		add_action( 'revenue_campaign_before_header', array( $this, 'add_edit_campaign_link' ) );

		/**
		 * Commenting this hook to compatibility with woocommerce subscription plugin. without this hook, works fine.
		 */
		// add_filter( 'woocommerce_cart_get_subtotal', array( $this, 'get_cart_contents_total' ) );

		// add_filter( 'woocommerce_cart_get_cart_contents_total', array( $this, 'get_cart_contents_total' ) );

		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'recalculate_fees' ), 99 );

		$this->subscriptions_plugin_integrations();

		// add_action( 'woocommerce_calculate_totals', array( $this, 'remove_price_filter' ) );
		// add_action( 'woocommerce_after_calculate_totals', array( $this, 'remove_price_filter' ) );
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'cart_fragment' ) );

		add_action( 'revenue_before_campaign_render', array( $this, 'add_campaign_css' ) );

		// add_action('revenue_rest_update_campaign', array($this, 'set_campaign_version'), 10);

		add_filter( 'woocommerce_blocks_checkout_block_registration', array( $this, 'custom_checkout_text_injection' ) );
		// add_filter( 'woocommerce_blocks_cart_block_registration', array( $this, 'custom_cart_text_injection' ) );

		// Register all revenue block filters dynamically.
		foreach ( $this->checkout_filters as $filter ) {
			add_filter( $filter, array( $this, 'revenue_block_handler' ) );
		}
		// Register all revenue block filters dynamically.
		foreach ( $this->cart_filters as $filter ) {
			add_filter( $filter, array( $this, 'revenue_cart_block_handler' ) );
		}
		add_filter(
			'revenue_block_before_cart_form',
			function ( $content ) {
				ob_start();
				$this->before_cart();
				return ob_get_clean();
			}
		);

		add_filter( 'render_block', array( $this, 'render_block' ), 9999, 3 );
		// doesn't work from ajax file, because of function dependency.
		add_action( 'wp_ajax_revenue_get_campaign_html', array( $this, 'get_campaign_html' ) );
		add_action( 'wp_ajax_nopriv_revenue_get_campaign_html', array( $this, 'get_campaign_html' ) );
		add_filter( 'woocommerce_available_variation', array( $this, 'set_variation_stock_scarity' ), 10, 1 );
	}

	/**
	 * Get campaign html for the given position through ajax.
	 *
	 * @return void|\WP_REST_Response
	 */
	public function get_campaign_html() {
		$position = sanitize_text_field( wp_unslash( $_GET['position'] ?? '' ) );
		if ( ! $position ) {
			return;
		}
		if ( ! method_exists( $this, $position ) ) {
			return;
		}
		// call the method dynamically and return the output.
		ob_start();
		$this->{$position}();
		$html = ob_get_clean();

		wp_send_json_success(
			array(
				'innerHtml' => $html,
			)
		);
	}

	/**
	 * Reference blocks, do not delete, add more if found, might be needed in future.
	 *
	 * @var array $cart_blocks
	 */
	protected $cart_blocks = array(
		'woocommerce/cart',
		'woocommerce/filled-cart-block',
		'woocommerce/cart-items-block',
		'woocommerce/cart-line-items-block',
		'woocommerce/cart-cross-sells-block',
		'woocommerce/cart-cross-sells-products-block',
		'woocommerce/cart-totals-block',
		'woocommerce/cart-order-summary-block',
		'woocommerce/cart-order-summary-heading-block',
		'woocommerce/cart-order-summary-coupon-form-block',
		'woocommerce/cart-order-summary-subtotal-block',
		'woocommerce/cart-order-summary-fee-block',
		'woocommerce/cart-order-summary-discount-block',
		'woocommerce/cart-order-summary-shipping-block',
		'woocommerce/cart-order-summary-taxes-block',
		'woocommerce/cart-express-payment-block',
		'woocommerce/proceed-to-checkout-block',
		'woocommerce/cart-accepted-payment-methods-block',
	);

	/**
	 * Reference blocks, do not delete, add more if found, might be needed in future.
	 *
	 * @var array $checkout_blocks
	 */
	protected $checkout_blocks = array(
		'woocommerce/checkout',
		'woocommerce/checkout-fields-block',
		'woocommerce/checkout-contact-information-block',
		'woocommerce/checkout-billing-address-block',
		'woocommerce/checkout-shipping-address-block',
		'woocommerce/checkout-methods-block',
		'woocommerce/checkout-payment-block',
		'woocommerce/checkout-order-summary-block',
		'woocommerce/checkout-order-summary-heading-block',
		'woocommerce/checkout-order-summary-coupon-form-block',
		'woocommerce/checkout-order-summary-subtotal-block',
		'woocommerce/checkout-order-summary-fee-block',
		'woocommerce/checkout-order-summary-discount-block',
		'woocommerce/checkout-order-summary-shipping-block',
		'woocommerce/checkout-order-summary-taxes-block',
		'woocommerce/checkout-order-summary-total-block',
		'woocommerce/checkout-terms-and-conditions-block',
		'woocommerce/checkout-actions-block',
	);

	/**
	 * Modify WooCommerce Cart blocks to insert revenue campaign placeholders.
	 *
	 * This function is hooked to the `render_block` filter and is responsible for:
	 * 1. Checking if the current request is on a WooCommerce cart page.
	 * 2. Mapping specific WooCommerce cart-related blocks to semantic positions
	 *    (cart, cart_table, cart_totals, proceed_to_checkout).
	 * 3. Enqueueing the frontend JavaScript for campaign rendering.
	 * 4. Outputting empty placeholder divs before and after the block content
	 *    which will later be populated via JavaScript.
	 *
	 * The placeholders are added as sibling elements to avoid breaking
	 * React-based WooCommerce blocks, ensuring safe frontend rendering.
	 *
	 * @param string $block_content The original block HTML content.
	 * @param array  $block         The full block array including blockName, attrs, etc.
	 * @param array  $attrs         Optional block attributes (from block parser).
	 *
	 * @return string Modified block content with revenue campaign placeholders.
	 */
	public function render_block( $block_content, $block, $attrs ) {
		if ( ! WC()->cart ) {
			return $block_content;
		}
		// add more positions when needed.
		$blocks_map = array(
			// cart positions.
			'woocommerce/cart'                         => '{pos}_cart',
			'woocommerce/cart-line-items-block'        => '{pos}_cart_table',
			'woocommerce/cart-order-summary-block'     => '{pos}_cart_totals',
			'woocommerce/proceed-to-checkout-block'    => 'proceed_to_checkout',
			// checkout positions.
			'woocommerce/checkout'                     => '{pos}_checkout_form',
			'woocommerce/checkout-contact-information-block' => 'before_checkout_billing_form',
			'woocommerce/checkout-actions-block'       => 'after_checkout_billing_form',
			'woocommerce/checkout-order-summary-block' => 'checkout_{pos}_order_review',
			'woocommerce/checkout-payment-block'       => 'review_order_{pos}_payment',
			'woocommerce/checkout-order-summary-shipping-block' => 'review_order_{pos}_shipping',
		);

		if ( ! isset( $block['blockName'] ) || ! isset( $blocks_map[ $block['blockName'] ] ) ) {
			return $block_content;
		}

		// enqueue the script that will later render the campaign on frontend.
		wp_enqueue_style( 'revx-animation', REVENUE_URL . 'assets/css/common/revenue-animation.css', array(), REVENUE_VER );
		wp_enqueue_style( 'revx-campaign', REVENUE_URL . 'assets/css/common/revenue-campaign.css', array(), REVENUE_VER );
		wp_enqueue_style( 'revenue-campaign' );
		wp_enqueue_style( 'revenue-campaign-buyx_gety' );
		wp_enqueue_style( 'revenue-campaign-volume' );
		wp_enqueue_style( 'revenue-campaign-double_order' );
		wp_enqueue_style( 'revenue-campaign-fbt' );
		wp_enqueue_style( 'revenue-campaign-mix_match' );
		wp_enqueue_style( 'revenue-utility' );
		wp_enqueue_style( 'revenue-responsive' );
		wp_enqueue_style( 'revenue-animated-add-to-cart' );
		wp_enqueue_style( 'revenue-campaign-spending_goal' );
		wp_enqueue_script( 'revenue-block-integration' );
		wp_enqueue_script( 'revenue-campaign' );
		wp_enqueue_script( 'revenue-slider' );
		wp_enqueue_script( 'revenue-add-to-cart' );
		wp_enqueue_script( 'revenue-variation-product-selection' );
		wp_enqueue_script( 'revenue-checkbox-handler' );
		wp_enqueue_script( 'revenue-double-order' );
		wp_enqueue_script( 'revenue-campaign-total' );
		wp_enqueue_script( 'revenue-countdown' );
		wp_enqueue_script( 'revenue-animated-add-to-cart' );
		wp_enqueue_script( 'revenue-spending-goal' );
		$this->localize_script();

		$position = $blocks_map[ $block['blockName'] ];

		// forcefully matching positon with classic cart hook position the before billing form.
		if ( 'before_checkout_billing_form' === $position ) {
			return (
				'<div class="revenue-block-slot revenue-' . esc_attr( $position ) . '" data-position="' . esc_attr( $position ) . '"></div>' .
				$block_content
			);
		}
		// forcefully matching positon with classic cart hook position the after billing form.
		if ( 'after_checkout_billing_form' === $position ) {
			return (
				$block_content .
				'<div class="revenue-block-slot revenue-' . esc_attr( $position ) . '" data-position="' . esc_attr( $position ) . '"></div>'
			);
		}
		// proceed to checkout doesn't have before after in our architecture.
		if ( 'proceed_to_checkout' === $position ) {
			return (
				$block_content .
				'<div class="revenue-block-slot revenue-' . esc_attr( $position ) . '" data-position="' . esc_attr( $position ) . '"></div>'
			);
		}

		$before_position = str_replace( '{pos}', 'before', $position );
		$after_position  = str_replace( '{pos}', 'after', $position );

		return (
			'<div class="revenue-block-slot revenue-' . esc_attr( $before_position ) . '" data-position="' . esc_attr( $before_position ) . '"></div>' .
			$block_content .
			'<div class="revenue-block-slot revenue-' . esc_attr( $after_position ) . '" data-position="' . esc_attr( $after_position ) . '"></div>'
		);
	}

	/**
	 * Set variation stock scarity
	 *
	 * @param array $variation_data Variation data woocommerce filter.
	 * @return array
	 */
	public function set_variation_stock_scarity( $variation_data ) {
		// Example: override availability HTML.
		$output = '';
		ob_start();
		$this->fetch_and_run_campaigns(
			$variation_data['variation_id'],
			'product_page',
			'inpage',
			'before_add_to_cart_quantity',
			false,
			false,
			'stock_scarcity'
		);
		$output = ob_get_clean();

		if ( $output ) {
			$variation_data['availability_html'] = $output;
		}

		// Custom flag for JS.
		$variation_data['revx_campaign'] = true;

		return $variation_data;
	}

	/**
	 * Set campaign version
	 *
	 * @param array $campaign Campaign data.
	 * @return void
	 */
	public function set_campaign_version( $campaign ) {
		revenue()->update_campaign_meta( $campaign['id'], 'campaign_version', '2.0.0' );
	}

	/**
	 * Add campaign CSS styles
	 *
	 * @param int $campaign_id Campaign ID.
	 * @return void
	 */
	public function add_campaign_css( $campaign_id ) {
		$campaign_version = revenue()->get_campaign_meta( $campaign_id, 'campaign_version', true );

		if ( '2.0.0' === $campaign_version ) {
			wp_enqueue_style( 'revx-animation', REVENUE_URL . 'assets/css/common/revenue-animation.css', array(), REVENUE_VER );
			wp_enqueue_style( 'revx-campaign', REVENUE_URL . 'assets/css/common/revenue-campaign.css', array(), REVENUE_VER );
		}
		?>
			<style>
				:root{
					<?php echo wp_kses_post( Revenue_Template_Utils::get_global_themes() ); ?>
				}
				<?php echo wp_kses_post( Revenue_Template_Utils::get_global_typography() ); ?>
			</style>
		<?php
		$placement_settings = revenue()->get_placement_settings( $campaign_id );
		$display_style      = isset( $placement_settings['display_style'] ) ? $placement_settings['display_style'] : 'inpage';
		$campaign           = revenue()->get_campaign_data( $campaign_id );
		$campaign_type      = $campaign['campaign_type'];

		$css = '';
		switch ( $display_style ) {
			case 'inpage':
				$css = revenue()->get_campaign_meta( $campaign_id, 'inpage_css', true );
				break;
			case 'popup':
				$css = revenue()->get_campaign_meta( $campaign_id, 'popup_css', true );
				break;
			case 'floating':
				$css = revenue()->get_campaign_meta( $campaign_id, 'floating_css', true );
				break;
			case 'top':
				$css = revenue()->get_campaign_meta( $campaign_id, 'top_css', true );
				break;
			case 'bottom':
				$css = revenue()->get_campaign_meta( $campaign_id, 'bottom_css', true );
				break;
			case 'drawer':
				$css = revenue()->get_campaign_meta( $campaign_id, 'drawer_css', true );
				break;

			default:
				// code...
				break;
		}

		// conditionally add css for all page top and bottom placement.
		if ( 'free_shipping_bar' === $campaign_type || 'spending_goal' === $campaign_type || 'countdown_timer' === $campaign_type ) {
			// $css .= revenue()->get_campaign_meta( $campaign_id, 'top_css', true );
			$entire_site_placement = revenue()->get_placement_settings( $campaign_id, 'all_page' );
			if (
				isset( $entire_site_placement['page'] ) &&
				'all_page' === $entire_site_placement['page'] &&
				'yes' === $entire_site_placement['status']
			) {
				if ( 'top' === $entire_site_placement['display_style'] ) {
					$css .= revenue()->get_campaign_meta( $campaign_id, 'top_css', true );
				} elseif ( 'bottom' === $entire_site_placement['display_style'] ) {
					$css .= revenue()->get_campaign_meta( $campaign_id, 'bottom_css', true );
				}
			}
		}

		?>
			<style>
				<?php echo html_entity_decode( $css ); //phpcs:ignore ?>
			</style>

		<?php

		// add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'cart_fragment' ) );
	}

	/**
	 * Custom cart text injection for WooCommerce Cart blocks.
	 *
	 * @param array $block_registry Block registry.
	 * @return array
	 */
	public function custom_cart_text_injection( $block_registry ) {
		// Map WooCommerce blocks to their corresponding revenue filters.
		$block_mappings = array(
			'woocommerce/cart-line-items-block'     => array(
				'filters' => array(
					'revenue_block_before_cart_table' => 'before',
					'revenue_block_after_cart_table'  => 'after',
				),
			),
			'woocommerce/filled-cart-block'         => array(
				'filters' => array(
					'revenue_block_before_cart_form' => 'before',
					'revenue_block_after_cart_form'  => 'after',
				),
			),
			'woocommerce/cart-order-summary-block'  => array(
				'filters' => array(
					'revenue_block_before_cart_totals'  => 'before',
					'revenue_block_proceed_to_checkout' => 'after',
				),
			),
			'woocommerce/proceed-to-checkout-block' => array(
				'filter'   => 'revenue_block_after_cart_totals',
				'position' => 'after',
			),
		);
		// Instead of directly filtering rendered HTML (which can conflict with React's
		// children vs dangerouslySetInnerHTML rule in block-based blocks), register
		// the block names into a registry and use `render_block_data` to mutate parsed
		// block data safely before React renders it.
		foreach ( $block_mappings as $block_name => $config ) {
			if ( isset( $config['filters'] ) ) {
				foreach ( $config['filters'] as $filter => $position ) {
					$this->block_injection_registry[ $block_name ][] = array(
						'filter'   => $filter,
						'position' => $position,
					);
				}
			} else {
				$this->block_injection_registry[ $block_name ][] = array(
					'filter'   => $config['filter'],
					'position' => $config['position'],
				);
			}
		}

		// Register the render_block_data filter only once.
		if ( ! $this->render_block_data_registered ) {
			add_filter( 'render_block_data', array( $this, 'filter_render_block_data' ), 10, 2 );
			$this->render_block_data_registered = true;
		}
		return $block_registry;
	}

	/**
	 * Custom checkout text injection for WooCommerce Checkout blocks.
	 *
	 * @param array $block_registry Block registry.
	 * @return array
	 */
	public function custom_checkout_text_injection( $block_registry ) {
		// Map WooCommerce blocks to their corresponding revenue filters.
		$block_mappings = array(
			'woocommerce/checkout-order-summary-cart-items-block' => array(
				'filter'   => 'revenue_block_checkout_before_order_review',
				'position' => 'before',
			),
			'woocommerce/checkout-billing-address-block' => array(
				'filter'   => 'revenue_block_add_text_after_billing_form',
				'position' => 'before',
			),
			'woocommerce/checkout-contact-information-block' => array(
				'filter'   => 'revenue_block_before_checkout_billing_form',
				'position' => 'before',
			),
			'woocommerce/checkout'                       => array(
				'filters' => array(
					'revenue_block_before_checkout_form' => 'before',
					'revenue_block_after_checkout_form'  => 'after',
				),
			),
			'woocommerce/checkout-payment-block'         => array(
				'filters' => array(
					'revenue_block_review_order_before_payment' => 'before',
					'revenue_block_review_order_after_payment' => 'after',
				),
			),
		);

		// Build registry for checkout blocks and register render_block_data handler.
		foreach ( $block_mappings as $block_name => $config ) {
			if ( isset( $config['filters'] ) ) {
				foreach ( $config['filters'] as $filter => $position ) {
					$this->block_injection_registry[ $block_name ][] = array(
						'filter'   => $filter,
						'position' => $position,
					);
				}
			} else {
				$this->block_injection_registry[ $block_name ][] = array(
					'filter'   => $config['filter'],
					'position' => $config['position'],
				);
			}
		}

		if ( ! $this->render_block_data_registered ) {
			add_filter( 'render_block_data', array( $this, 'filter_render_block_data' ), 10, 2 );
			$this->render_block_data_registered = true;
		}

		return $block_registry;
	}


	/**
	 * Filter to safely inject content into parsed block data before rendering.
	 * This avoids manipulating final HTML which can cause React errors about
	 * children vs dangerouslySetInnerHTML.
	 *
	 * @param array $parsed_block Parsed block data.
	 * @param array $source_block Source block (unused).
	 * @return array
	 */
	public function filter_render_block_data( $parsed_block, $source_block ) {
		// Avoid mutating blocks in admin/editor or REST render contexts to prevent
		// React errors in the block editor. Only mutate blocks on the front-end.
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return $parsed_block;
		}

		if ( empty( $parsed_block ) || ! isset( $parsed_block['blockName'] ) ) {
			return $parsed_block;
		}

		$block_name = $parsed_block['blockName'];

		if ( empty( $this->block_injection_registry[ $block_name ] ) ) {
			return $parsed_block;
		}

		// Get the original innerHTML or content for this block. Prefer innerHTML
		// in parsed_block if present, otherwise fallback to render() output.
		$original_inner = '';
		if ( isset( $parsed_block['innerHTML'] ) ) {
			$original_inner = $parsed_block['innerHTML'];
		} elseif ( isset( $parsed_block['innerBlocks'] ) && is_array( $parsed_block['innerBlocks'] ) ) {
			// If innerBlocks exist, concatenate their innerHTML when available.
			$parts = array();
			foreach ( $parsed_block['innerBlocks'] as $inner ) {
				if ( isset( $inner['innerHTML'] ) ) {
					$parts[] = $inner['innerHTML'];
				}
			}
			$original_inner = implode( '', $parts );
		}

		// For each configured injection for this block, run the filter and inject
		// before or after the inner content.
		foreach ( $this->block_injection_registry[ $block_name ] as $injection ) {
			$filter   = $injection['filter'];
			$position = $injection['position'];

			// Apply the existing filter to get the HTML we want to inject.
			$injected_html = apply_filters( $filter, '' );

			if ( $injected_html ) {
				if ( 'before' === $position ) {
					$original_inner = $injected_html . $original_inner;
				} else {
					$original_inner = $original_inner . $injected_html;
				}
			}
		}

		// Assign modified innerHTML back to parsed block so React sees a single
		// dangerouslySetInnerHTML source for the block (avoids children conflict).
		$parsed_block['innerHTML'] = $original_inner;

		return $parsed_block;
	}

	/**
	 * Inject content into the block content based on the filter name and position.
	 *
	 * @param string $block_content The original block content.
	 * @param string $filter_name   The filter name to apply.
	 * @param string $position      The position to inject content ('before' or 'after').
	 * @return string Modified block content with injected content.
	 */
	private function inject_content_checkout_block( $block_content, $filter_name, $position ) {
		$revx_checkout_content = apply_filters( $filter_name, '' );
		if ( 'before' === $position ) {
			return $revx_checkout_content . $block_content;
		} else {
			return $block_content . $revx_checkout_content;
		}
	}

	/**
	 * Handle revenue Checkout block filters.
	 *
	 * @return string
	 */
	public function revenue_block_handler() {
		$filter_name = current_filter();

		// Map filter names to position names.
		$position_map = array(
			'revenue_block_checkout_before_order_review' => 'checkout_before_order_review',
			'revenue_block_add_text_after_billing_form'  => 'after_checkout_billing_form',
			'revenue_block_before_checkout_billing_form' => 'before_checkout_billing_form',
			'revenue_block_before_checkout_form'         => 'before_checkout_form',
			'revenue_block_review_order_before_payment'  => 'review_order_before_payment',
			'revenue_block_review_order_after_payment'   => 'review_order_after_payment',
			'revenue_block_after_checkout_form'          => 'after_checkout_form',
		);

		$position = $position_map[ $filter_name ] ?? '';
		ob_start();
		$this->checkout_common_position_callback( $position, 'checkout_page' );
		return ob_get_clean();
	}

	/**
	 * Handle revenue cart block filters.
	 *
	 * @return string
	 */
	public function revenue_cart_block_handler() {
		$filter_name = current_filter();

		// Map filter names to position names.
		$position_map = array(
			'revenue_block_before_cart_table'   => 'before_cart_table',
			'revenue_block_after_cart_table'    => 'after_cart_table',
			'revenue_block_before_cart_form'    => 'before_cart',
			'revenue_block_after_cart_form'     => 'after_cart',
			'revenue_block_before_cart_totals'  => 'before_cart_totals',
			'revenue_block_after_cart_totals'   => 'after_cart_totals',
			'revenue_block_proceed_to_checkout' => 'proceed_to_checkout',
		);

		$position = $position_map[ $filter_name ] ?? '';
		ob_start();
		$this->checkout_common_position_callback( $position, 'cart_page' );
		return ob_get_clean();
	}

	/**
	 * Callback for checkout common position.
	 *
	 * @param string $position Position.
	 * @param string $which_page Which page (cart_page or checkout_page).
	 */
	public function checkout_common_position_callback( $position = '', $which_page = 'checkout_page' ) {
		if ( WC()->cart ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product_id = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

				Revenue_Product_Context::set_product_context( $product_id );

				$campaigns = revenue()->get_available_campaigns( $product_id, $which_page, 'inpage', $position );
				if ( ! empty( $campaigns ) ) {
					// Run campaigns for the product in the checkout page.
					$this->run_campaigns( $campaigns, 'inpage', $which_page, $position );
					break; // Break after running campaigns for the first product.
				}

				Revenue_Product_Context::clear_product_context();
			}
		}
	}

	/**
	 * Cart fragment
	 *
	 * @param array $fragment Fragment.
	 * @return array
	 */
	public function cart_fragment( $fragment ) {

		WC()->cart->calculate_totals();

		$cart_total    = 0;
		$cart_subtotal = 0;

		if ( wc_prices_include_tax() ) {
			$cart_total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();
		} else {
			$cart_total = WC()->cart->get_cart_contents_total();
		}

		if ( WC()->cart->display_prices_including_tax() ) {
			$cart_subtotal = WC()->cart->get_subtotal() + WC()->cart->get_subtotal_tax();

		} else {
			$cart_subtotal = WC()->cart->get_subtotal();
		}

		$fragment['subtotal'] = $cart_subtotal;
		$fragment['total']    = $cart_total;
		return $fragment;
	}


	/**
	 * Recalculate Fees
	 *
	 * @return void
	 */
	public function recalculate_fees() {
		// Avoid infinite loop by checking if totals are already calculated.
		static $recalculating = false;

		// Prevent further recalculation if it's already happening.
		if ( $recalculating ) {
			return;
		}

		// Set flag to indicate we're recalculating.
		$recalculating = true;

		// Ensure the cart is updated, and check if it's in the front-end or during AJAX request.
		if ( ! is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Check if the cart contains the specific product.
		$contains_specific_item = false;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
				$contains_specific_item = true;
				break;
			}
		}

		// Only recalculate if the specific item is found in the cart.
		if ( $contains_specific_item ) {
			// Recalculate taxes (force the cart to recalculate totals).
			WC()->cart->calculate_totals();  // This triggers recalculation of taxes.
			WC()->cart->calculate_shipping(); // This triggers recalculation of shipping costs.
		}

		// Reset the flag after the recalculation.
		$recalculating = false;
	}


	/**
	 * Get cart contents total
	 *
	 * @param string|float $sub_total Subtotal.
	 * @return mixed
	 */
	public function get_cart_contents_total( $sub_total ) {
		$total               = 0;
		$has_revenue_product = false;
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			// Revenue Cart Item: Added through revenue (checking for campaign data).
			if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
				$campaign_type = $cart_item['revx_campaign_type'];
				$campaign_id   = $cart_item['revx_campaign_id'];

				/**
				 * Hook for updating the price on the cart from several campaigns
				 * Valid Campaign Types:
				 * - normal_discount
				 * - bundle_discount
				 * - volume_discount
				 * - buy_x_get_y
				 * - mix_match
				 * - frequently_bought_together
				 * - spending_goal
				 */

				// Fire the appropriate action based on the campaign type.
				do_action( "revenue_campaign_{$campaign_type}_before_calculate_cart_totals", $cart_item, $campaign_id, WC()->cart );
				$has_revenue_product = true;
			}
			$total += floatval( $cart_item['data']->get_price() ) * $cart_item['quantity'];
		}
		if ( $total && $has_revenue_product ) {
			if ( WC()->cart->display_prices_including_tax() ) {
				return $total - WC()->cart->get_subtotal_tax();
			} else {
				return $total;
			}
		}
		return $sub_total;
	}


	/**
	 * Add Edit Campaign link
	 *
	 * @param string $campaign_id Campaign Id.
	 * @return void
	 */
	public function add_edit_campaign_link( $campaign_id ) {
		$has_permission = current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
		if ( ! $has_permission ) {
			return;
		}
		?>
		<a class="revx-admin-edit" target="_blank" href="<?php echo esc_url( revenue()->get_edit_campaign_url( $campaign_id ) ); ?>"><?php echo esc_html__( 'Edit Campaign', 'revenue' ); ?></a>
		<?php
	}

	/**
	 * Force recalculate shipping charge
	 *
	 * @return void
	 */
	public function force_recalculate_shipping() {
		WC()->cart->calculate_shipping();
	}


	/**
	 * Localize script
	 *
	 * @return void
	 */
	public function localize_script() {

		$campaign_localize_data = array(
			'ajax'                         => admin_url( 'admin-ajax.php' ),
			'nonce'                        => wp_create_nonce( 'revenue-add-to-cart' ),
			'user'                         => get_current_user_id(),
			'data'                         => $this->campaign_additional_data,
			'currency_format_num_decimals' => wc_get_price_decimals(),
			'currency_format_symbol'       => get_woocommerce_currency_symbol(),
			'currency_format_decimal_sep'  => wc_get_price_decimal_separator(),
			'currency_format_thousand_sep' => wc_get_price_thousand_separator(),
			'currency_format'              => get_woocommerce_price_format(),
			'checkout_page_url'            => wc_get_checkout_url(),
			'added_to_cart'                => __( 'Added to Cart', 'revenue' ),
			'adding'                       => __( 'Adding...', 'revenue' ),
			'free_gift_added_to_cart'      => __( 'Free gift added to cart', 'revenue' ),
			'free_gifts_added_to_cart'     => __( 'Free gifts added to cart', 'revenue' ),
			'select_all_attributes'        => __( 'Please select all required attributes', 'revenue' ),
			'free_gift_removed'            => __( 'Free gift removed', 'revenue' ),
			'required_product_missing'     => __( 'Error adding to cart, A required product is missing!', 'revenue' ),
			'required_products_missing'    => __( 'Error adding to cart, Some required products are missing!', 'revenue' ),
			'select_items_first'           => __( 'Please select the item(s) first', 'revenue' ),
			'error_adding_to_cart'         => __( 'Error adding to cart', 'revenue' ),
			'select_at_least_one_product'  => __( 'Please select at least one product to add', 'revenue' ),
		);

		wp_localize_script( 'revenue-campaign', 'revenue_campaign', $campaign_localize_data );
		wp_localize_script( 'revenue-v1-campaign', 'revenue_campaign', $campaign_localize_data );
	}


	/**
	 * Run all page campaigns.
	 *
	 * @return void
	 */
	public function run_all_page_campaigns() {
		$which_page = '';
		if ( is_product() ) {
			$which_page = 'product_page';
		} elseif ( is_cart() ) {
			$which_page = 'cart_page';
		} elseif ( is_checkout() ) {
			$which_page = 'checkout_page';
		} elseif ( is_shop() ) {
			$which_page = 'shop_page';
		} elseif ( is_account_page() ) {
			$which_page = 'my_account';
		}
		// NOTE: this can be helpful when doing hardcoded shortcodes. Example: shortcode on mini cart of woodmart.
		// wp_enqueue_style( 'revenue-campaign' );
		// wp_enqueue_style( 'revenue-campaign-buyx_gety' );
		// wp_enqueue_style( 'revenue-campaign-volume' );
		// wp_enqueue_style( 'revenue-campaign-double_order' );
		// wp_enqueue_style( 'revenue-campaign-fbt' );
		// wp_enqueue_style( 'revenue-campaign-mix_match' );
		// wp_enqueue_style( 'revenue-utility' );
		// wp_enqueue_style( 'revenue-responsive' );
		// wp_enqueue_script( 'revenue-campaign' );
		// wp_enqueue_script( 'revenue-slider' );
		// wp_enqueue_script( 'revenue-add-to-cart' );
		// wp_enqueue_script( 'revenue-variation-product-selection' );
		// wp_enqueue_script( 'revenue-checkbox-handler' );
		// wp_enqueue_script( 'revenue-double-order' );
		// wp_enqueue_script( 'revenue-campaign-total' );
		// wp_enqueue_script( 'revenue-countdown' );
		// wp_enqueue_script( 'revenue-animated-add-to-cart' );
		// wp_enqueue_style( 'revenue-animated-add-to-cart' );
		// wp_enqueue_script( 'revenue-spending-goal' );
		// wp_enqueue_style( 'revenue-campaign-spending_goal' );
		// wp_enqueue_style( 'revx-animation', REVENUE_URL . 'assets/css/common/revenue-animation.css', array(), REVENUE_VER );
		// wp_enqueue_style( 'revx-campaign', REVENUE_URL . 'assets/css/common/revenue-campaign.css', array(), REVENUE_VER );

		if ( ! empty( $which_page ) ) {

			$positions = revenue()->get_campaign_inpage_positions();

			$inpage_positions = isset( $positions[ $which_page ] ) ? array_keys( $positions[ $which_page ] ) : array();
			foreach ( $inpage_positions as $position ) {
				if ( strpos( $position, 'rvex_' ) === 0 ) {
					// Handle positions with the 'rvex_' prefix.
					add_action( $position, array( $this, $position ) );
				} elseif ( method_exists( $this, $position ) ) {
					// Handle other positions.
					add_action( 'woocommerce_' . $position, array( $this, $position ), 1 );
				}
			}

			if ( 'checkout_page' === $which_page ) {
				add_action( 'woocommerce_before_thankyou', array( $this, 'before_thankyou' ) );
				add_action( 'woocommerce_thankyou', array( $this, 'thankyou' ) );
				wp_enqueue_script( 'revenue-campaign' );
			}
			if ( 'cart_page' === $which_page ) {
				add_action( 'woocommerce_after_cart_item_name', array( $this, 'cart_item_name' ), 10, 2 );
				add_filter( 'woocommerce_cart_item_price', array( $this, 'rvex_add_text_after_cart_item_price' ), 10, 3 );
			}
			if ( 'product_page' === $which_page ) {
				// This Hook is used to add the campaign Stock Scarcity in the product page.
				add_action( 'rvex_below_the_product_title', array( $this, 'rvex_below_the_product_title' ) );
				add_action( 'rvex_below_the_product_price', array( $this, 'rvex_below_the_product_price' ) );
				add_action( 'woocommerce_before_add_to_cart_quantity', array( $this, 'before_add_to_cart_quantity' ) );
			}

			if ( 'my_account' === $which_page ) {
				// Enqueue scripts for My Account page campaigns.
				wp_enqueue_script( 'revenue-campaign' );
			}
		}

		add_action( 'woocommerce_review_order_before_submit', array( $this, 'review_order_before_submit' ) );
		add_action( 'woocommerce_review_order_before_payment', array( $this, 'review_order_before_payment' ) );
		add_action( 'woocommerce_review_order_before_shipping', array( $this, 'review_order_before_shipping' ) );

		/**
		 * Filters the post content.
		 *
		 * @param string $content Content of the current post.
		 * @return string Content of the current post.
		 */
		add_filter( 'the_content', array( $this, 'run_cart_checkout_block_campaigns' ) );

		add_action( 'wp_head', array( $this, 'run_hellobar_campaings' ) );
		add_action( 'wp_footer', array( $this, 'run_global_campaigns' ) );

		do_action( 'revenue_run_campaign' );
	}

	/**
	 * Run hellobar campaigns.
	 *
	 * @return void
	 */
	public function run_hellobar_campaings() {
		$this->fetch_and_run_campaigns( 0, 'all_page', 'top' );
	}

	/**
	 * Fetch available campaigns for a product and execute them.
	 * with_ids, is_cart and campaign_type are optional parameters,
	 * variation stock scarcity special case. So that it only fetches the stock scarcity campaign.
	 *
	 * @param int|string $pid       Product ID.
	 * @param string     $page      The placement/page context. Example: 'product_page', 'cart_page'.
	 * @param string     $dp_mode   Display mode. Example: 'popup', 'floating', 'inpage'.
	 * @param string     $position  Optional. Position within the placement.
	 *                              Example: 'top_left', 'before_cart_table','before_add_to_cart_button'.
	 *                              Default ''.
	 * @param bool       $with_ids  Optional. Whether to include campaign IDs.
	 *                              Default false.
	 * @param bool       $is_cart   Optional. Whether the campaign is for a cart item.
	 *                              Default false.
	 * @param string     $campaign_type Optional. Campaign type. Example: 'popup', 'floating', 'inpage'.
	 *                              Default ''.
	 *
	 * @return void
	 */
	private function fetch_and_run_campaigns(
		$pid,
		$page,
		$dp_mode,
		$position = '',
		$with_ids = false,
		$is_cart = false,
		$campaign_type = ''
	) {
		Revenue_Product_Context::set_product_context( $pid );

		$campaigns = revenue()->get_available_campaigns(
			$pid,
			$page,
			$dp_mode,
			$position,
			$with_ids,
			$is_cart,
			$campaign_type
		);
		$this->run_campaigns( $campaigns, $dp_mode, $page, $position );

		Revenue_Product_Context::clear_product_context();
	}

	/**
	 * Run popup and floating campaigns.
	 *
	 * @param string|int $pid Product ID.
	 * @param string     $page Which page is current page
	 *                   Example: 'product_page', 'cart_page', 'thankyou_page'.
	 *
	 * @return void
	 */
	private function run_popup_and_floating_campaigns( $pid, $page ) {
		$display_modes = array( 'popup', 'floating' );

		foreach ( $display_modes as $dp_mode ) {
			$this->fetch_and_run_campaigns( $pid, $page, $dp_mode );
		}
	}

	/**
	 * Run campaigns on cart and checkout page based on cart items.
	 *
	 * @param string $page      The placement/page context. Example: 'product_page', 'cart_page'.
	 * @param string $dp_mode   Display mode. Example: 'popup', 'floating', 'inpage'.
	 * @param string $position  Optional. Position within the placement/page.
	 *                          Example: 'top_left', 'before_cart_table','before_add_to_cart_button'.
	 *                          Default ''.
	 *
	 * @return void
	 */
	private function run_campaigns_for_cart_items( $page, $dp_mode, $position = '' ) {
		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
			// Run campaigns for variations.
			// Needed for double order campaign and if any campaign has variation trigger.
			if ( isset( $cart_item['variation_id'] ) ) {
				$this->fetch_and_run_campaigns( $cart_item['variation_id'], $page, $dp_mode, $position );
			}
			// Run campaigns for main products.
			// Most campaigns use main product as trigger.
			$this->fetch_and_run_campaigns( $cart_item['product_id'], $page, $dp_mode, $position );
		}
	}

	/**
	 * Run campaigns on thankyou page based on order items.
	 *
	 * @param \WC_Order $_order                    Order object.
	 * @param string    $position                  Optional. Position within the placement/page.
	 *                                             Example: 'top_left', 'before_cart_table','before_add_to_cart_button'.
	 *                                             Default ''.
	 * @param bool      $is_run_popup_and_floating Optional. Whether to run popup and floating campaigns.
	 *                                             Default false.
	 *
	 * @return void
	 */
	private function run_campaigns_for_order_items( $_order, $position, $is_run_popup_and_floating = false ) {
		foreach ( $_order->get_items() as $item ) {
			/**
			 * This comment is for the linters.
			 *
			 * @var \WC_Order_Item_Product $item
			 */

			$pid = $item->get_product_id();

			$this->fetch_and_run_campaigns( $pid, 'thankyou_page', 'inpage', $position );

			if ( $is_run_popup_and_floating ) {
				$this->run_popup_and_floating_campaigns( $pid, 'thankyou_page' );
			}
		}
	}

	/**
	 * Run global campaigns.
	 *
	 * @return void
	 */
	public function run_global_campaigns() {
		global $post;
		$which_page = 'all_page';
		if ( is_product() ) {
			$which_page = 'product_page';
		} elseif ( is_cart() ) {
			$which_page = 'cart_page';
		} elseif ( is_checkout() ) {
			$which_page = 'checkout_page';
		}

		if ( $post ) {
			$this->fetch_and_run_campaigns( $post->ID, $which_page, 'drawer', 'top_left' );
			$this->fetch_and_run_campaigns( $post->ID, 'all_page', 'bottom' );
		}
		if ( is_product() ) {
			$this->run_popup_and_floating_campaigns( get_the_ID(), $which_page );

		} elseif ( ! is_wc_endpoint_url( 'order-received' ) && ( is_cart() || is_checkout() ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$this->run_popup_and_floating_campaigns( $cart_item['product_id'], $which_page );
			}
		}
	}


	/**
	 * Run cart and checkout block campaigns
	 *
	 * @param string $content Content.
	 * @return string
	 */
	public function run_cart_checkout_block_campaigns( $content ) {
		$before_extra      = '';
		$after_extra       = '';
		$showed_product_id = array();
		ob_start();
		if ( is_cart() && has_block( 'woocommerce/cart', get_the_ID() ) ) {

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product_id = $cart_item['product_id'];

				Revenue_Product_Context::set_product_context( $product_id );

				if ( ! isset( $showed_product_id[ $product_id ] ) ) {
					$showed_product_id[ $product_id ] = true;
					$this->fetch_and_run_campaigns( $product_id, 'cart_page', 'inpage', 'before_content' );
				}
				Revenue_Product_Context::clear_product_context();
			}
		}
		if ( is_checkout() && has_block( 'woocommerce/checkout', get_the_ID() ) ) {
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product_id = $cart_item['product_id'];

				Revenue_Product_Context::set_product_context( $product_id );

				if ( ! isset( $showed_product_id[ $product_id ] ) ) {
					$showed_product_id[ $product_id ] = true;
					$campaigns                        = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', 'before_content' );

					$this->run_campaigns( $campaigns, 'inpage', 'cart_page', 'before_content' );
				}
				Revenue_Product_Context::clear_product_context();
			}
		}
		$before_extra = ob_get_clean();
		ob_start();
		if ( is_cart() && has_block( 'woocommerce/cart', get_the_ID() ) ) {

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product_id = $cart_item['product_id'];

				Revenue_Product_Context::set_product_context( $product_id );

				if ( ! isset( $showed_product_id[ $product_id ] ) ) {
					$showed_product_id[ $product_id ] = true;
					$campaigns                        = revenue()->get_available_campaigns( $product_id, 'cart_page', 'inpage', 'after_content' );

					$this->run_campaigns( $campaigns, 'inpage', 'cart_page', 'after_content' );
				}
				Revenue_Product_Context::clear_product_context();
			}
		}
		if ( is_checkout() && has_block( 'woocommerce/checkout', get_the_ID() ) ) {

			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$product_id = $cart_item['product_id'];

				Revenue_Product_Context::set_product_context( $product_id );

				if ( ! isset( $showed_product_id[ $product_id ] ) ) {
					$showed_product_id[ $product_id ] = true;
					$campaigns                        = revenue()->get_available_campaigns( $product_id, 'checkout_page', 'inpage', 'after_content' );

					$this->run_campaigns( $campaigns, 'inpage', 'cart_page', 'after_content' );
				}
				Revenue_Product_Context::clear_product_context();
			}
		}

		$after_extra = ob_get_clean();

		return $before_extra . $content . $after_extra;
	}

	/**
	 * Is campaign valid
	 *
	 * @param array $campaign Campaign.
	 * @return boolean
	 */
	private function is_campaign_valid( $campaign ) {
		$valid = false;
		if ( isset( $campaign['campaign_type'], $campaign['campaign_status'], $campaign['id'] ) && 'publish' === $campaign['campaign_status'] ) {
			$valid = true;
		}

		if ( ! $valid || revenue()->is_hide_campaign( $campaign['id'], $campaign['campaign_type'] ) ) {
			return false;
		}

		switch ( $campaign['campaign_type'] ) {
			case 'bundle_discount':
				return $this->is_bundle_discount_campaign_stock_valid( $campaign );

			case 'buy_x_get_y':
				return $this->is_buy_x_get_y_campaign_stock_valid( $campaign );

			case 'frequently_bought_together':
				return $this->is_frequently_bought_together_campaign_stock_valid( $campaign );

			case 'mix_match':
				return $this->is_mix_match_campaign_stock_valid( $campaign );

			default:
				return true;
		}
	}

	/**
	 * Check bundle discount campaign stock validity.
	 *
	 * @param array $campaign Campaign data.
	 * @return bool
	 */
	private function is_bundle_discount_campaign_stock_valid( $campaign ) {
		$product_ids = array();

		$offers = $campaign['offers'] ?? revenue()->get_campaign_meta( $campaign['id'], 'offers', true );
		if ( is_array( $offers ) ) {
			foreach ( $offers as $offer ) {
				if ( ! empty( $offer['products'] ) && is_array( $offer['products'] ) ) {
					$product_ids = array_merge( $product_ids, $offer['products'] );
				}
			}
		}

		$include_required_products = isset( $campaign['bundle_with_trigger_products_enabled'] ) && 'yes' === $campaign['bundle_with_trigger_products_enabled'];
		if ( $include_required_products && ! empty( $campaign['campaign_trigger_items'] ) && is_array( $campaign['campaign_trigger_items'] ) ) {
			foreach ( $campaign['campaign_trigger_items'] as $trigger_item ) {
				if ( isset( $trigger_item['item_id'] ) && $trigger_item['item_id'] ) {
					$product_ids[] = $trigger_item['item_id'];
				}
			}
		}

		return $this->are_all_products_in_stock( $product_ids );
	}

	/**
	 * Check buy x get y campaign stock validity.
	 *
	 * @param array $campaign Campaign data.
	 * @return bool
	 */
	private function is_buy_x_get_y_campaign_stock_valid( $campaign ) {
		$required_product_ids = $this->get_required_product_ids_from_campaign( $campaign );
		return $this->are_all_products_in_stock( $required_product_ids );
	}

	/**
	 * Check frequently bought together campaign stock validity.
	 *
	 * @param array $campaign Campaign data.
	 * @return bool
	 */
	private function is_frequently_bought_together_campaign_stock_valid( $campaign ) {
		$required_product_ids = $this->get_required_product_ids_from_campaign( $campaign );
		return $this->are_all_products_in_stock( $required_product_ids );
	}

	/**
	 * Check mix and match campaign stock validity.
	 *
	 * @param array $campaign Campaign data.
	 * @return bool
	 */
	private function is_mix_match_campaign_stock_valid( $campaign ) {
		$required_product_ids = $this->get_required_product_ids_from_campaign( $campaign );
		return $this->are_all_products_in_stock( $required_product_ids );
	}

	/**
	 * Get required product IDs from campaign trigger items.
	 *
	 * @param array $campaign Campaign data.
	 * @return array
	 */
	private function get_required_product_ids_from_campaign( $campaign ) {
		$required_product_ids = array();

		if ( empty( $campaign['campaign_trigger_items'] ) || ! is_array( $campaign['campaign_trigger_items'] ) ) {
			return $required_product_ids;
		}

		$trigger_relation = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';
		$trigger_relation = strtolower( (string) $trigger_relation );

		if ( 'and' !== $trigger_relation ) {
			$trigger_product_id = $this->get_triggering_product_id_for_or_relation( $campaign );

			if ( $trigger_product_id ) {
				return array( $trigger_product_id );
			}

			return $required_product_ids;
		}

		foreach ( $campaign['campaign_trigger_items'] as $trigger_item ) {
			if ( isset( $trigger_item['item_id'] ) && $trigger_item['item_id'] ) {
				$required_product_ids[] = $trigger_item['item_id'];
			}
		}

		return $required_product_ids;
	}

	/**
	 * Get currently triggering product id for OR trigger relation.
	 *
	 * @param array $campaign Campaign data.
	 * @return int
	 */
	private function get_triggering_product_id_for_or_relation( $campaign ) {
		$current_product_id = Revenue_Product_Context::get_product_context_id();

		foreach ( $campaign['campaign_trigger_items'] as $trigger_item ) {
			if (
				isset( $trigger_item['item_id'] ) &&
				$trigger_item['item_id'] &&
				(int) $trigger_item['item_id'] === $current_product_id
			) {
				return $current_product_id;
			}
		}

		return 0;
	}

	/**
	 * Check if all given products are in stock.
	 *
	 * @param array $product_ids Product IDs.
	 * @return bool
	 */
	private function are_all_products_in_stock( $product_ids ) {
		if ( empty( $product_ids ) || ! \function_exists( 'wc_get_product' ) ) {
			return true;
		}

		$product_ids = array_unique( array_map( 'intval', $product_ids ) );

		foreach ( $product_ids as $product_id ) {
			if ( ! $product_id ) {
				continue;
			}

			$product = \wc_get_product( $product_id );
			if ( ! $product || ! $product->is_in_stock() ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Run campaigns.
	 *
	 * @param array  $campaigns Campaigns.
	 * @param string $display_type Display type.
	 * @param string $placement Placement.
	 * @param string $position Position.
	 * @return void
	 */
	public function run_campaigns( $campaigns, $display_type = 'inpage', $placement = '', $position = '' ) {
		$typewise_campaigns = array();
		$campaigns          = $this->filter_running_campaigns( $campaigns );

		$campaign_id = '';
		foreach ( $campaigns as $campaign ) {
			$campaign = (array) $campaign;

			$campaign_id = $campaign['id'];

			if ( ! $this->is_campaign_valid( $campaign ) ) {
				continue;
			}

			$typewise_campaigns[ $campaign['campaign_type'] ][] = $campaign;

			if ( revenue()->is_for_new_builder( $campaign ) ) {
				wp_enqueue_style( 'revenue-campaign' );
				wp_enqueue_style( 'revenue-campaign-buyx_gety' );
				wp_enqueue_style( 'revenue-campaign-volume' );
				wp_enqueue_style( 'revenue-campaign-double_order' );
				wp_enqueue_style( 'revenue-campaign-fbt' );
				wp_enqueue_style( 'revenue-campaign-mix_match' );
				wp_enqueue_style( 'revenue-utility' );
				wp_enqueue_style( 'revenue-responsive' );
				wp_enqueue_script( 'revenue-campaign' );
				wp_enqueue_script( 'revenue-slider' );
				wp_enqueue_script( 'revenue-add-to-cart' );
				wp_enqueue_script( 'revenue-variation-product-selection' );
				wp_enqueue_script( 'revenue-checkbox-handler' );
				wp_enqueue_script( 'revenue-double-order' );
				wp_enqueue_script( 'revenue-campaign-total' );
				wp_enqueue_script( 'revenue-countdown' );
				wp_enqueue_script( 'revenue-animated-add-to-cart' );
				wp_enqueue_style( 'revenue-animated-add-to-cart' );

			} else {

				wp_enqueue_style( 'revenue-v1-campaign' );
				wp_enqueue_style( 'revenue-v1-campaign-buyx_gety' );
				wp_enqueue_style( 'revenue-v1-campaign-double_order' );
				wp_enqueue_style( 'revenue-v1-campaign-fbt' );
				wp_enqueue_style( 'revenue-v1-campaign-mix_match' );
				wp_enqueue_style( 'revenue-v1-utility' );
				wp_enqueue_style( 'revenue-v1-responsive' );
				wp_enqueue_script( 'revenue-v1-campaign' );
				wp_enqueue_script( 'revenue-v1-add-to-cart' );
				wp_enqueue_script( 'revenue-v1-animated-add-to-cart' );
				wp_enqueue_style( 'revenue-v1-animated-add-to-cart' );

			}

			$which_page = 'all_page';
			if ( is_product() ) {
				$which_page = 'product_page';
			} elseif ( is_cart() ) {
				$which_page = 'cart_page';
			} elseif ( is_shop() ) {
				$which_page = 'shop_page';
			} elseif ( is_product_category() ) {
				$which_page = 'product_category_page';
			} elseif ( is_product_tag() ) {
				$which_page = 'product_tag_page';
			} elseif ( is_account_page() ) {
				$which_page = 'my_account';
			} elseif ( is_wc_endpoint_url( 'order-received' ) ) {
				$which_page = 'thankyou_page';
			} elseif ( is_checkout() ) {
				$which_page = 'checkout_page';
			}

			// If the campaign is not set to display on this page, skip it. Might be done in better way.
			if ( ! isset( $campaign['placement_settings'][ $which_page ] ) ) {
				continue;
			}

			$campaign_view = $campaign['placement_settings'][ $which_page ]['builder_view'];
			$is_grid       = 'grid' === $campaign_view;

			$is_divider         = in_array( $campaign['campaign_type'], array( 'mix_match', 'frequently_bought_together', 'bundle_discount' ), true );
			$is_buy_x_get_y     = in_array( $campaign['campaign_type'], array( 'buy_x_get_y' ), true );
			$is_volume_discount = in_array( $campaign['campaign_type'], array( 'volume_discount' ), true );

			$this->campaign_additional_data[ $campaign['id'] ]['revenue_campaign_type']              = $campaign['campaign_type'];
			$this->campaign_additional_data[ $campaign['id'] ]['revenue_campaign_view']              = $campaign_view;
			$this->campaign_additional_data[ $campaign['id'] ]['offered_product_click_action']       = $campaign['offered_product_click_action'];
			$this->campaign_additional_data[ $campaign['id'] ]['offered_product_on_cart_action']     = $campaign['offered_product_on_cart_action'];
			$this->campaign_additional_data[ $campaign['id'] ]['animated_add_to_cart_enabled']       = $campaign['animated_add_to_cart_enabled'];
			$this->campaign_additional_data[ $campaign['id'] ]['add_to_cart_animation_trigger_type'] = $campaign['add_to_cart_animation_trigger_type'];
			$this->campaign_additional_data[ $campaign['id'] ]['add_to_cart_animation_type']         = $campaign['add_to_cart_animation_type'];
			$this->campaign_additional_data[ $campaign['id'] ]['add_to_cart_animation_start_delay']  = $campaign['add_to_cart_animation_start_delay'];
			$this->campaign_additional_data[ $campaign['id'] ]['free_shipping_enabled']              = $campaign['free_shipping_enabled'];
			$this->campaign_additional_data[ $campaign['id'] ]['is_campaign_divider']                = $is_divider;
			if ( $is_divider ) {
				$this->campaign_additional_data[ $campaign['id'] ]['revenue_divider_size'] = $campaign['builder']['sliderParent']['dividerSize'][ $display_type ] ?? null;
			}
			if ( $is_buy_x_get_y ) {
				if ( $is_grid ) {
					$this->campaign_additional_data[ $campaign['id'] ]['revenue_separator_width']  = $campaign['builder']['sliderParent']['separatorWidth'][ $display_type ] ?? null;
					$this->campaign_additional_data[ $campaign['id'] ]['revenue_separator_height'] = null;
				} else {
					$this->campaign_additional_data[ $campaign['id'] ]['revenue_separator_width']  = null;
					$this->campaign_additional_data[ $campaign['id'] ]['revenue_separator_height'] = $campaign['builder']['productsContainerWrapper']['separatorHeight'][ $display_type ] ?? null;
				}
			}
			if ( $is_volume_discount ) {
				$this->campaign_additional_data[ $campaign['id'] ]['multiple_variation_selection_enabled'] = $campaign['multiple_variation_selection_enabled'] ?? 'no';
			}
			$this->campaign_additional_data[ $campaign['id'] ]['revenue_slider_gap'] =
				isset(
					$campaign['builder']['sliderParent']['gap'][ $display_type ]
				)
					? $campaign['builder']['sliderParent']['gap'][ $display_type ]
					: '';
			if ( isset( $campaign['countdown_timer_enabled'] ) && 'yes' === $campaign['countdown_timer_enabled'] ) {

				$countdown_data = array();

				$end_date = revenue()->get_campaign_meta( $campaign['id'], 'countdown_end_date', true );
				$end_time = revenue()->get_campaign_meta( $campaign['id'], 'countdown_end_time', true );

				$end_date_time = $end_date . ' ' . $end_time;

				$have_start_date_time = ( 'schedule_to_later' === revenue()->get_campaign_meta( $campaign['id'], 'countdown_start_time_status', true ) );

				$start_date_time = '';
				if ( $have_start_date_time ) {
					$start_date = revenue()->get_campaign_meta( $campaign['id'], 'countdown_start_date', true );
					$start_time = revenue()->get_campaign_meta( $campaign['id'], 'countdown_start_time', true );

					$start_date_time = $start_date . ' ' . $start_time;
				}

				// If start_date_time is empty, set it to current date and time.
				if ( empty( $start_date_time ) ) {
					$start_date_time = current_time( 'mysql' );
				}

				$current_date_time = new DateTime( current_time( 'mysql' ) );

				$end_date_time_obj = new DateTime( $end_date_time );
				if ( $end_date_time_obj >= $current_date_time ) {
					$countdown_data = array(
						'end_time'   => $end_date_time,
						'start_time' => $start_date_time,
					);
				}

				$this->campaign_additional_data[ $campaign['id'] ]['countdown_data'] = $countdown_data;
			}

			if ( isset( $campaign['campaign_placement'] ) && 'multiple' !== $campaign['campaign_placement'] ) {
				$campaign['placement_settings'] = array(
					$campaign['campaign_placement'] => array(
						'page'                     => $campaign['campaign_placement'],
						'status'                   => 'yes',
						'display_style'            => $campaign['campaign_display_style'],
						'builder_view'             => $campaign['campaign_builder_view'],
						'inpage_position'          => $campaign['campaign_inpage_position'],
						'popup_animation'          => $campaign['campaign_popup_animation'],
						'popup_animation_delay'    => $campaign['campaign_popup_animation_delay'],
						'floating_position'        => $campaign['campaign_floating_position'],
						'floating_animation_delay' => $campaign['campaign_floating_animation_delay'],
					),
				);

				$campaign['placement_settings'] = $campaign['placement_settings'];
			}

			$placement_settings = isset( $campaign['placement_settings'][ $placement ] ) ? $campaign['placement_settings'][ $placement ] : '';

			if ( is_array( $placement_settings ) && isset( $placement_settings['display_style'] ) && 'popup' === $placement_settings['display_style'] ) {
				$this->campaign_additional_data[ $campaign['id'] ]['campaign_popup_animation']       = $placement_settings['popup_animation'] ?? '';
				$this->campaign_additional_data[ $campaign['id'] ]['campaign_popup_animation_delay'] = $placement_settings['popup_animation_delay'] ?? '';
			}

			if ( isset( $campaign['animated_add_to_cart_enabled'] ) && 'yes' === $campaign['animated_add_to_cart_enabled'] ) {

				$trigger_when    = revenue()->get_campaign_meta( $campaign['id'], 'add_to_cart_animation_trigger_type', true );
				$animation_type  = revenue()->get_campaign_meta( $campaign['id'], 'add_to_cart_animation_type', true );
				$animation_delay = 0;

				if ( 'loop' === $trigger_when && empty( $loop_animation ) ) {
					$animation_delay = revenue()->get_campaign_meta( $campaign['id'], 'add_to_cart_animation_start_delay', true ) ?? 0;
					$loop_animation  = array(
						'type'  => $animation_type,
						'delay' => $animation_delay,
					);
				} elseif ( 'on_hover' === $trigger_when && empty( $hover_animation ) ) {
					$hover_animation = $animation_type;
				}
				$this->animated_button_data[ $campaign['id'] ] = array(
					'loop_animation'  => $animation_type,
					'delay'           => $animation_delay,
					'hover_animation' => $animation_type,
				);
			}
		}

		$this->localize_script();

		$display_type_methods = array(
			'inpage'   => 'output_inpage_views',
			'floating' => 'output_floating_views',
			'popup'    => 'output_popup_views',
			'drawer'   => 'output_drawer_views',
			'top'      => 'output_top_views',
			'bottom'   => 'output_bottom_views',
		);

		$class = false;

		foreach ( $typewise_campaigns as $type => $_campaigns ) {
			// Check if the display type and campaign type are valid.
			if ( isset( $display_type_methods[ $display_type ] ) ) {
				$method = $display_type_methods[ $display_type ];

				// Determine the appropriate class based on the campaign type.
				switch ( $type ) {
					case 'normal_discount':
						$class = Revenue_Normal_Discount::instance();
						break;
					case 'bundle_discount':
						$class = Revenue_Bundle_Discount::instance();
						break;
					case 'volume_discount':
						$class = Revenue_Volume_Discount::instance();
						break;
					case 'buy_x_get_y':
						$class = Revenue_Buy_X_Get_Y::instance();
						break;
					case 'free_shipping_bar':
						$class = Revenue_Free_Shipping_Bar::instance();
						break;
					case 'countdown_timer':
						$class = Revenue_Countdown_Timer::instance();
						break;
					case 'stock_scarcity':
						$class = Revenue_Stock_Scarcity::instance();
						break;
					case 'next_order_coupon':
						$class = Revenue_Next_Order_Coupon::instance();
						break;
					default:
						$class = false;
						break;
				}
				$class = apply_filters( 'revenue_campaign_instance', $class, $type );

				if ( $class ) {
					do_action( "revenue_campaign_{$type}_{$display_type}_before_render_content" );
					// @TODO Backward Compatibility should be added for pro.

					if ( method_exists( $class, $method ) ) {
						?>
							<style>
								:root{
									<?php echo wp_kses_post( Revenue_Template_Utils::get_global_themes() ); ?>
								}
								<?php echo wp_kses_post( Revenue_Template_Utils::get_global_typography() ); ?>
							</style>
						<?php
						// keep revx-template as wrapper for every campaign.
						echo '<div class="revx-template">';
						// Start output buffering to capture all inner output to prevent printing before revx-template.
						ob_start();

						$class->$method(
							$_campaigns,
							array(
								'display_type' => $display_type,
								'position'     => $position,
								'placement'    => $placement,
							)
						);
						$method_content = ob_get_clean();
						// Echo the buffered content inside the wrapper div.
						echo $method_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						// Close the wrapper revx-template after all inner content has been printed.
						echo '</div>';
					}
					do_action( "revenue_campaign_{$type}_{$display_type}_after_render_content" );
				}
			}
		}
	}

	/**
	 * Check if campaign is already running.
	 *
	 * @param int $id Campaign ID.
	 * @return bool
	 */
	private function is_campaign_running( $id ) {
		static $tracked_campaign_ids = array();

		if ( in_array( $id, $tracked_campaign_ids, true ) ) {
			return true;
		}

		$tracked_campaign_ids[] = $id;
		return false;
	}

	/**
	 * Filter campaign based on running id and allowed multi render.
	 *
	 * @param array $campaign Campaign.
	 * @return bool
	 */
	private function filter_campaign( $campaign ) {
		$allowed_multi_render = array( 'stock_scarcity', 'countdown_timer' );
		if (
			! in_array( $campaign['campaign_type'], $allowed_multi_render, true )
			&& $this->is_campaign_running( $campaign['id'] )
		) {
			return false;
		}
		return true;
	}

	/**
	 * Filter running campaigns array based on filter campaign function.
	 *
	 * @param array $campaigns Campaigns.
	 * @return array
	 */
	public function filter_running_campaigns( $campaigns ) {
		return array_filter(
			$campaigns,
			array( $this, 'filter_campaign' )
		);
	}

	/**
	 * Run campaign on before add to cart button.
	 *
	 * @return void
	 */
	public function before_add_to_cart_button() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'product_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on after add to cart button.
	 *
	 * @return void
	 */
	public function after_add_to_cart_button() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'product_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on after add to cart quantity.
	 *
	 * @return void
	 */
	public function after_add_to_cart_quantity() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'product_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on before add to cart quantity.
	 *
	 * @return void
	 */
	public function before_add_to_cart_quantity() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'product_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on below the product title.
	 *
	 * @return void
	 */
	public function rvex_below_the_product_title() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'product_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on below the product price.
	 *
	 * @return void
	 */
	public function rvex_below_the_product_price() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'product_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on before add to cart form.
	 *
	 * @return void
	 */
	public function before_add_to_cart_form() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'product_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on after add to cart form.
	 *
	 * @return void
	 */
	public function after_add_to_cart_form() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'product_page', 'inpage', __FUNCTION__ );
	}
	/**
	 * Run campaign on after shop loop item title.
	 *
	 * @return void
	 */
	public function after_shop_loop_item_title() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'shop_page', 'inpage', __FUNCTION__ );
	}
	/**
	 * Run campaign on before shop loop item title.
	 *
	 * @return void
	 */
	public function shop_loop_item_title() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'shop_page', 'inpage', __FUNCTION__ );
	}
	/**
	 * Run campaign on Cart Item Name
	 *
	 * @param array $cart_item Cart Item.
	 * @return void
	 */
	public function cart_item_name( $cart_item ) {
		// usually during cart
		// necessary for using inside stock scarcity and countdown timer: render views function.
		$this->fetch_and_run_campaigns( $cart_item['product_id'], 'cart_page', 'inpage', __FUNCTION__ );
	}
	/**
	 * Run campaign on Cart Item Name
	 *
	 * @param string $price Price.
	 * @param array  $cart_item Cart Item.
	 * @return void
	 */
	public function rvex_add_text_after_cart_item_price( $price, $cart_item ) {
		ob_start();
		$this->fetch_and_run_campaigns( $cart_item['product_id'], 'cart_page', 'inpage', 'cart_item_price' );

		$cart_item_content = ob_get_clean();
		return $price . $cart_item_content;
	}

	/**
	 * Run campaign on before single product summary
	 *
	 * @return void
	 */
	public function before_single_product_summary() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'product_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on after single product summary
	 *
	 * @return void
	 */
	public function after_single_product_summary() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'product_page', 'inpage', __FUNCTION__ );
	}


	/**
	 * Run campaign on after single product
	 *
	 * @return void
	 */
	public function after_single_product() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'product_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on before single product
	 *
	 * @return void
	 */
	public function before_single_product() {
		$this->fetch_and_run_campaigns( get_the_ID(), 'product_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * NOTE: could not find any hook for cart contents. 20 November, 2025.
	 * Run campaign on before cart contents
	 *
	 * @return void
	 */
	public function before_cart_contents() {
		$this->run_campaigns_for_cart_items( 'cart_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on before cart table
	 *
	 * @return void
	 */
	public function before_cart_table() {
		$this->run_campaigns_for_cart_items( 'cart_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on before cart
	 *
	 * @return void
	 */
	public function before_cart() {
		$this->run_campaigns_for_cart_items( 'cart_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on after cart table
	 *
	 * @return void
	 */
	public function after_cart_table() {
		$this->run_campaigns_for_cart_items( 'cart_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on after cart
	 *
	 * @return void
	 */
	public function after_cart() {
		$this->run_campaigns_for_cart_items( 'cart_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on before cart totals
	 *
	 * @return void
	 */
	public function before_cart_totals() {
		$this->run_campaigns_for_cart_items( 'cart_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on after cart totals
	 *
	 * @return void
	 */
	public function after_cart_totals() {
		$this->run_campaigns_for_cart_items( 'cart_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on after proceed to checkout
	 *
	 * @return void
	 */
	public function proceed_to_checkout() {
		$this->run_campaigns_for_cart_items( 'cart_page', 'inpage', __FUNCTION__ );
	}

	// Checkout.
	/**
	 * Run campaign on before checkout form
	 *
	 * @return void
	 */
	public function before_checkout_form() {
		$this->run_campaigns_for_cart_items( 'checkout_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on before checkout billing form
	 *
	 * @return void
	 */
	public function before_checkout_billing_form() {
		$this->run_campaigns_for_cart_items( 'checkout_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on review order before submit
	 *
	 * @return void
	 */
	public function review_order_before_submit() {
		$this->run_campaigns_for_cart_items( 'checkout_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on after checkout billing form
	 *
	 * @return void
	 */
	public function after_checkout_billing_form() {
		$this->run_campaigns_for_cart_items( 'checkout_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on checkout after order review
	 *
	 * @return void
	 */
	public function checkout_after_order_review() {
		$this->run_campaigns_for_cart_items( 'checkout_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on checkout before order review
	 *
	 * @return void
	 */
	public function checkout_before_order_review() {
		$this->run_campaigns_for_cart_items( 'checkout_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on review order before order total
	 *
	 * @return void
	 */
	public function review_order_before_order_total() {
		$this->run_campaigns_for_cart_items( 'checkout_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on review order after order total
	 *
	 * @return void
	 */
	public function review_order_after_order_total() {
		$this->run_campaigns_for_cart_items( 'checkout_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on review order before payment
	 *
	 * @return void
	 */
	public function review_order_before_payment() {
		$this->run_campaigns_for_cart_items( 'checkout_page', 'inpage', __FUNCTION__ );
	}
	/**
	 * Run campaign on review order before shipping.
	 *
	 * @return void
	 */
	public function review_order_before_shipping() {
		$this->run_campaigns_for_cart_items( 'checkout_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on review order after payment
	 *
	 * @return void
	 */
	public function review_order_after_payment() {
		$this->run_campaigns_for_cart_items( 'checkout_page', 'inpage', __FUNCTION__ );
	}

	/**
	 * Run campaign on after checkout form
	 *
	 * @return void
	 */
	public function after_checkout_form() {
		$this->run_campaigns_for_cart_items( 'checkout_page', 'inpage', __FUNCTION__ );
	}

	// Thankyou page.
	/**
	 * Run campaign on before thankyou
	 *
	 * @param int $order_id Order Id.
	 * @return void
	 */
	public function before_thankyou( $order_id ) {
		$_order = wc_get_order( $order_id );

		$this->run_campaigns_for_order_items( $_order, __FUNCTION__, true );
	}

	/**
	 * Run campaign on thankyou page after thankyou hook
	 *
	 * @param string|int $order_id Order ID.
	 * @return void
	 */
	public function thankyou( $order_id ) {
		$_order = wc_get_order( $order_id );

		$this->run_campaigns_for_order_items( $_order, __FUNCTION__ );
	}


	/**
	 * Handle Free Shipping
	 *
	 * @param Object $package_rates Shipping Package Rate.
	 * @param array  $package Package Data.
	 * @return mixed
	 */
	public function handle_free_shipping( $package_rates, $package ) {

		$is_free_shipping = true;
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			if ( ! ( isset( $cart_item['rev_is_free_shipping'] ) && 'yes' === $cart_item['rev_is_free_shipping'] ) ) {
				$is_free_shipping = false;
				break;
			}

			if ( isset( $cart_item['revx_campaign_type'] ) && 'buy_x_get_y' === $cart_item['revx_campaign_type'] ) {
				if ( ! Revenue_Buy_X_Get_Y::instance()->is_eligible_for_discount( $cart_item ) ) {
					$is_free_shipping = false;
					break;
				}
			}
		}

		// Check if the campaign products remove from the cart and still free shipping is applied.

		foreach ( WC()->cart->get_cart() as  $cart_item ) {

			$campaign_id = $cart_item['revx_campaign_id'] ?? 0;
			$product_id  = $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

			if ( ! $campaign_id ) {
				continue;
			}

			$campaign_data = revenue()->get_campaign_data( $campaign_id );
			$offers        = $campaign_data['offers'] ?? array();
			$offer_product = array();
			if ( is_array( $offers ) ) {
				foreach ( $offers as $offer ) {
					if ( isset( $offer['products'] ) && is_array( $offer['products'] ) ) {
						$offer_product = array_merge( $offer_product, $offer['products'] );
					}
				}
				$offer_product = array_unique( array_map( 'intval', $offer_product ) );
			}

			if ( ! in_array( (int) $product_id, $offer_product, true ) ) {
				$is_free_shipping = false;
				break;
			}
		}

		$is_free_shipping = apply_filters( 'revenue_free_shipping', $is_free_shipping );

		if ( $is_free_shipping ) {
			$free_shipping        = new WC_Shipping_Free_Shipping( 'revenue_free_shipping' );
			$free_shipping->title = apply_filters( 'revenue_free_shipping_title', __( 'WowRevenue Free Shipping', 'revenue' ) ); // Add Global Settings.
			$free_shipping->calculate_shipping( $package );
			return $free_shipping->rates;
		}

		return $package_rates;
	}

	/**
	 * Handle Animated Add to cart
	 *
	 * @return void
	 */
	public function handle_animated_add_to_cart() {
		wp_enqueue_script( 'revenue-animated-add-to-cart' );
		wp_enqueue_style( 'revenue-animated-add-to-cart' );
		wp_localize_script( 'revenue-animated-add-to-cart', 'revenue_animated_atc', array( 'data' => $this->animated_button_data ) );
	}

	/**
	 * WooCommerce check cart items
	 *
	 * @return void
	 */
	public function woocommerce_check_cart_items() {
		$cart_hash = isset( $_COOKIE['woocommerce_cart_hash'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['woocommerce_cart_hash'] ) ) : '';
		$revx_hash = isset( $_COOKIE['revenue_cart_checked_hash'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['revenue_cart_checked_hash'] ) ) : '';

		if ( $cart_hash !== $revx_hash ) {
			// Not Checked.
			$cart = WC()->cart;

			do_action( 'revenue_check_cart_items', $cart );

			if ( ! headers_sent() ) {
				setcookie( 'revenue_cart_checked_hash', $cart_hash, time() + ( 86400 * 30 ), '/' );
			}
		}
	}

	/**
	 * After Add to cart from revenue campaign
	 *
	 * Set Campaign and Product Id on session to check if the product already on cart or not
	 *
	 * @since 1.0.0
	 *
	 * @param string     $key Cart item hash.
	 * @param int|string $product_id Product Id.
	 * @param int|string $campaign_id Campaign id.
	 * @return void
	 */
	public function after_add_to_cart( $key, $product_id, $campaign_id ) {
		$cart_data = WC()->session->get( 'revenue_cart_data' );
		if ( ! is_array( $cart_data ) ) {
			$cart_data = array();
		}
		if ( ! isset( $cart_data[ $campaign_id ][ $product_id ] ) ) {
			$cart_data[ $campaign_id ][ $product_id ] = $key;
			WC()->session->set( 'revenue_cart_data', $cart_data );
		}
	}

	/**
	 * Actions After remove cart item
	 *
	 * Perform campaign wise action after remove a item from cart
	 *
	 * @param string  $key Cart Item Hash Key.
	 * @param WC_Cart $cart Cart.
	 * @return void
	 */
	public function after_remove_cart_item( $key, $cart ) {
		$cart_item = $cart->removed_cart_contents[ $key ];
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];
			$item          = WC()->cart->get_cart()[ $key ];
			$cart_data     = WC()->session->get( 'revenue_cart_data' );
			if ( ! is_array( $cart_data ) ) {
				$cart_data = array();
			}
			if ( isset( $cart_data[ $item['revx_campaign_id'] ][ $item['product_id'] ] ) ) {
				unset( $cart_data[ $item['revx_campaign_id'] ][ $item['product_id'] ] );
				if ( empty( $cart_data[ $item['revx_campaign_id'] ] ) ) {
					unset( $cart_data[ $item['revx_campaign_id'] ] );
				}
				WC()->session->set( 'revenue_cart_data', $cart_data );
			}

			/**
			 * Hook for update price on cart from several campaigns
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action( "revenue_campaign_{$campaign_type}_remove_cart_item", $key, $cart_item, $campaign_id );
		}
	}

	/**
	 * After cart item restored
	 *
	 * Set Campaign and Product Id on session to check if the product already on cart or not
	 *
	 * @param string  $key Restored item cart hash key.
	 * @param WC_Cart $cart Cart.
	 * @return void
	 */
	public function after_cart_item_restored( $key, $cart ) {
		$cart_item = $cart->removed_cart_contents[ $key ];

		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$cart_data = WC()->session->get( 'revenue_cart_data' );
			if ( ! is_array( $cart_data ) ) {
				$cart_data = array();
			}
			if ( ! isset( $cart_data[ $campaign_id ][ $cart_item['product_id'] ] ) ) {
				$cart_data[ $campaign_id ][ $cart_item['product_id'] ] = $key;
				WC()->session->set( 'revenue_cart_data', $cart_data );
			}

			/**
			 * Hook for update price on cart from several campaigns
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action( "revenue_campaign_{$campaign_type}_restore_cart_item", $key, $cart_item, $campaign_id );
		}
	}

	/**
	 * Set Revenue cart data Null after cart empty
	 *
	 * @return void
	 */
	public function after_cart_emptied() {
		WC()->session->set( 'revenue_cart_data', null );
	}



	/**
	 * WooCommerce After Add to cart an item.
	 *
	 * @since 1.0.0
	 *
	 * @param string     $cart_item_key Cart Item Key.
	 * @param int|string $product_id Product Id.
	 * @param int        $quantity Added Quantity.
	 * @param int        $variation_id Variation Id.
	 * @param array      $variation Variation Data.
	 * @param array      $cart_item_data Cart Item Data.
	 * @return void
	 */
	public function woocommerce_after_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {

		if ( ! did_action( 'woocommerce_cart_loaded_from_session' ) ) {
			return;
		}

		if ( isset( $cart_item_data['revx_campaign_id'], $cart_item_data['revx_campaign_type'] ) ) {
			$campaign_type = $cart_item_data['revx_campaign_type'];
			/**
			 * Hook for update price on cart from several campaigns
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action( "revenue_campaign_{$campaign_type}_added_to_cart", $cart_item_key, $cart_item_data, $product_id, $quantity );
		}
	}

	/**
	 * WooCommerce Before Calculate Total
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Cart $cart Cart.
	 * @return void
	 */
	public function woocommerce_before_calculate_totals( $cart ) {
		// if ( is_admin() ) {
		// return;
		// }

		wp_cache_delete( 'revx_cart_product_ids' );
		$has_revenue_price = false;
		foreach ( $cart->get_cart() as $cart_item ) {
			$is_from_revenue = false;

			if ( $cart_item['data']->is_type( 'etn' ) ) {
				continue;
			}

			// Revenue Cart Item : Added through revenue.
			if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
				$campaign_type = $cart_item['revx_campaign_type'];
				$campaign_id   = $cart_item['revx_campaign_id'];

				/**
				 * Hook for update price on cart from several campaigns
				 * Valid Campaign Type:
				 * normal_discount
				 * bundle_discount
				 * volume_discount
				 * buy_x_get_y
				 * mix_match
				 * frequently_bought_together
				 * spending_goal
				 */
				do_action( "revenue_campaign_{$campaign_type}_before_calculate_cart_totals", $cart_item, $campaign_id, $cart );

				$has_revenue_price = true;

				$pid = isset( $cart_item['variation_id'] ) && $cart_item['variation_id'] ? $cart_item['variation_id'] : $cart_item['product_id'];

				$this->revenue_price[ $pid ] = $cart_item['data']->get_price();

			}
		}

		// if ( $has_revenue_price ) {
		// $this->add_price_filter();
		// }.
	}

	/**
	 * Adds price filter hooks for WooCommerce products and variations.
	 *
	 * This method attaches filters to modify the price of both regular products
	 * and product variations using the 'set_product_price' callback.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function add_price_filter() {
		add_filter( 'woocommerce_product_get_price', array( $this, 'set_product_price' ), 10, 2 );
		add_filter( 'woocommerce_product_variation_get_price', array( $this, 'set_product_price' ), 10, 2 );
	}

	/**
	 * Removes previously added price filter hooks.
	 *
	 * This method removes the price modification filters that were added by add_price_filter().
	 * Use this method when you need to restore original product pricing.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return void
	 */
	public function remove_price_filter() {
		remove_filter( 'woocommerce_product_get_price', array( $this, 'set_product_price' ), 10, 2 );
		remove_filter( 'woocommerce_product_variation_get_price', array( $this, 'set_product_price' ), 10, 2 );
	}

	/**
	 * Sets the product price based on stored revenue prices.
	 *
	 * Checks if a custom revenue price exists for the given product ID in the
	 * revenue_price array and returns that price if found. Otherwise, returns
	 * the original price unchanged.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param float           $price   The original product price.
	 * @param WC_Product|null $product The product object.
	 *
	 * @return float The modified or original price.
	 */
	public function set_product_price( $price, $product ) {
		if ( isset( $this->revenue_price[ $product->get_id() ] ) ) {
			$price = $this->revenue_price[ $product->get_id() ];
		}

		return $price;
	}
	/**
	 * WooCommerce Before Calculate Total
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Cart $cart Cart.
	 * @return void
	 */
	public function woocommerce_cart_calculate_fees( $cart ) {
		if ( is_admin() ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item ) {
			// Revenue Cart Item : Added through revenue.
			if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
				$campaign_type = $cart_item['revx_campaign_type'];
				$campaign_id   = $cart_item['revx_campaign_id'];

				/**
				 * Hook for update price on cart from several campaigns
				 * Valid Campaign Type:
				 * normal_discount
				 * bundle_discount
				 * volume_discount
				 * buy_x_get_y
				 * mix_match
				 * frequently_bought_together
				 * spending_goal
				 */
				do_action( "revenue_campaign_{$campaign_type}_cart_calculate_fees", $cart_item, $campaign_id, $cart );
			}
		}
	}


	/**
	 * WooCommerce Cart Item Remove Link
	 *
	 * Used for any modification on remove link based on campaign type
	 *
	 * @since 1.0.0
	 *
	 * @param string $link Remove Link.
	 * @param string $cart_item_key Cart Hash Key.
	 * @return string
	 */
	public function woocommerce_cart_item_remove_link( $link, $cart_item_key ) {
		$cart_item = WC()->cart->cart_contents[ $cart_item_key ];
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$link = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_remove_link", $link, $cart_item, $campaign_id );
		}

		return $link;
	}


	/**
	 * WooCommerce Cart Item Quantity
	 *
	 * Used for any modification on cart item quantity based on campaign type
	 *
	 * @since 1.0.0
	 *
	 * @param string $quantity Item Quantity.
	 * @param string $cart_item_key Cart Hash Key.
	 * @return string
	 */
	public function woocommerce_cart_item_quantity( $quantity, $cart_item_key ) {
		$cart_item = WC()->cart->cart_contents[ $cart_item_key ];
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$quantity = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_quantity", $quantity, $cart_item, $campaign_id );
		}
		return $quantity;
	}

	/**
	 * Add Custom class name on cart item based on campaign type
	 *
	 * @since 1.0.0
	 *
	 * @param string $classname Class Name.
	 * @param array  $cart_item Cart Item.
	 * @return string
	 */
	public function woocommerce_cart_item_class( $classname, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];
			$classname     = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_class", $classname, $cart_item, $campaign_id );
		}
		return $classname;
	}

	/**
	 * Change Cart Item Subtotal based on campaign
	 *
	 * @param string $subtotal Item Subtotal.
	 * @param array  $cart_item Cart Item.
	 * @return string|float
	 */
	public function woocommerce_cart_item_subtotal( $subtotal, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$subtotal = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_subtotal", $subtotal, $cart_item, $campaign_id );
		}
		return $subtotal;
	}

	/**
	 * Change Cart Item Name based on campaign
	 *
	 * @param string $item_name Item Name.
	 * @param array  $cart_item Cart Item.
	 * @return string|float
	 */
	public function woocommerce_cart_item_name( $item_name, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$item_name = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_name", $item_name, $cart_item, $campaign_id );
		}
		return $item_name;
	}

	/**
	 * Change Minimum Product quantity on Cart Block based on campaign
	 *
	 * @param string     $value Minimum Quantity Value.
	 * @param WC_Product $product Product Object.
	 * @param array      $cart_item Cart Item.
	 * @return string|float
	 */
	public function woocommerce_store_api_product_quantity_minimum( $value, $product, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$value = apply_filters( "revenue_campaign_{$campaign_type}_store_api_product_quantity_minimum", $value, $cart_item, $campaign_id );
		}
		return $value;
	}
	/**
	 * Change maximum Product quantity on Cart Block based on campaign
	 *
	 * @param string     $value Maximum Quantity Value.
	 * @param WC_Product $product Product Object.
	 * @param array      $cart_item Cart Item.
	 * @return string|float
	 */
	public function woocommerce_store_api_product_quantity_maximum( $value, $product, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$value = apply_filters( "revenue_campaign_{$campaign_type}_store_api_product_quantity_maximum", $value, $cart_item, $campaign_id );
		}
		return $value;
	}



	/**
	 * Change Cart Item Price based on campaign
	 *
	 * @since 1.0.0
	 *
	 * @param string $price Item Price.
	 * @param array  $cart_item Cart Item.
	 * @return string|float
	 */
	public function woocommerce_cart_item_price( $price, $cart_item ) {

		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$price = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_price", $price, $cart_item, $campaign_id );
		}
		return $price;
	}

	/**
	 * Get Cart item Data
	 *
	 * @since 1.0.0
	 *
	 * @param array $data Item Data.
	 * @param array $cart_item Cart Item.
	 * @return array
	 */
	public function woocommerce_get_item_data( $data, $cart_item ) {
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$data = apply_filters( "revenue_campaign_{$campaign_type}_cart_item_data", $data, $cart_item, $campaign_id );
		}
		return $data;
	}


	/**
	 * WooCommerce create order line item
	 *
	 * @param object   $item Item Data.
	 * @param string   $cart_item_key Cart Item Key.
	 * @param array    $cart_item Cart Item.
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function woocommerce_checkout_create_order_line_item( $item, $cart_item_key, $cart_item, $order ) {

		// Item order through revenue.
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			$item->add_meta_data( '_revx_campaign_id', $campaign_id, true );
			$item->add_meta_data( '_revx_campaign_type', $campaign_type, true );
			/**
			 * Hook for add item meta data
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action( "revenue_campaign_{$campaign_type}_create_order_line_item", $item, $cart_item_key, $cart_item, $campaign_id, $order );
		}
	}


	/**
	 * Add Hidden Order Item Meta
	 *
	 * @param array $hidden_meta Hidden Meta.
	 * @return array
	 */
	public function woocommerce_hidden_order_itemmeta( $hidden_meta ) {
		$hidden_meta[] = '_revx_campaign_id';
		$hidden_meta[] = '_revx_campaign_type';

		$hidden_meta = apply_filters( 'revenue_hidden_order_item_meta', $hidden_meta );

		return $hidden_meta;
	}


	/**
	 * Perform action after create order on WooCommerce
	 *
	 * @since 1.0.0
	 *
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function woocommerce_checkout_create_order( $order ) {
		$order = wc_get_order( $order );

		if ( ! $order ) {
			return;
		}

		// Get all items from the order.
		$items = $order->get_items();

		$data = array();

		// Loop through each item in the order.
		foreach ( $items as $item ) {
			// Get the campaign ID from the item's meta data.
			$campaign_id = $item->get_meta( '_revx_campaign_id' );

			if ( $campaign_id ) {
				// Get the product associated with the item.
				$order->update_meta_data( '_revx_campaign_id', $campaign_id );
				$product = $item->get_product();
				// Add the product ID and campaign ID to the data array.
				$data[] = array(
					'item_id'     => $product->get_id(),
					'campaign_id' => $campaign_id,
				);
			}
		}

		// If data array is not empty, trigger the custom action.
		if ( ! empty( $data ) ) {
			$order->save();
			do_action( 'revenue_campaign_order_created', $data, $order );
		}
	}

	/**
	 * Get appropriate product id for the current page.
	 * If global post is populated with product use that,
	 * otherwise for shop page get first product id
	 * for thankyou page get first product of the order
	 * for cart and checkout use first cart item.
	 *
	 * @return false|int if porduct not found return false otherwise return the appropirate product id.
	 */
	private function get_product_id_for_context() {
		global $post;

		$product_id = false;

		if ( isset( $post->post_type ) && 'product' === $post->post_type ) {
			// Single product page.
			$product_id = $post->ID;

		} elseif ( is_shop() || is_product_category() || is_product_tag() ) {
			// Shop / Product Archive page.
			$products = wc_get_products(
				array(
					'limit'   => 1, // Only the first product.
					'orderby' => 'date',
					'order'   => 'ASC',
				)
			);
			if ( ! empty( $products ) ) {
				$product_id = $products[0]->get_id();
			}
		} elseif ( is_order_received_page() ) {
			// Thank You page.
			$order_id = isset( $_GET['order'] ) ? absint( $_GET['order'] ) : 0;
			if ( $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order ) {
					$items = $order->get_items();
					if ( ! empty( $items ) ) {
						$first_item = reset( $items );
						$product_id = $first_item->get_product_id();
					}
				}
			}
		} elseif ( is_cart() || is_checkout() ) {
			// Cart or Checkout page.
			$cart_items = WC()->cart->get_cart();
			if ( ! empty( $cart_items ) ) {
				$first_item = reset( $cart_items );
				$product_id = $first_item['product_id'];
			}
		}

		// Now $product_id has the appropriate product ID.
		return $product_id;
	}

	/**
	 * Render campaign view shortcode
	 *
	 * @param array $atts Attributes.
	 * @return mixed
	 */
	public function render_campaign_view_shortcode( $atts ) {
		if ( ! isset( $atts['id'] ) ) {
			return false;
		}

		$id = (int) $atts['id'];

		if ( $this->is_campaign_running( $id ) ) {
			return;
		}

		$campaign = revenue()->get_campaign_data( $id );

		if ( ! $this->is_campaign_valid( $campaign ) ) {
			return;
		}

		$class = false;

		switch ( $campaign['campaign_type'] ) {
			case 'normal_discount':
				$class = Revenue_Normal_Discount::instance();
				break;
			case 'bundle_discount':
				$class = Revenue_Bundle_Discount::instance();
				break;
			case 'volume_discount':
				$class = Revenue_Volume_Discount::instance();
				break;
			case 'buy_x_get_y':
				$class = Revenue_Buy_X_Get_Y::instance();
				break;
			case 'free_shipping_bar':
				$class = Revenue_Free_Shipping_Bar::instance();
				break;
			case 'countdown_timer':
				$class = Revenue_Countdown_Timer::instance();
				break;
			case 'stock_scarcity':
				$class = Revenue_Stock_Scarcity::instance();
				break;
			default:
				$class = false;
				break;
		}

		$product_id = $this->get_product_id_for_context();

		Revenue_Product_Context::set_product_context( $product_id );

		$class = apply_filters( 'revenue_campaign_instance', $class, $campaign['campaign_type'] );

		do_action( 'revenue_campaign_before_render_shortcode', $campaign );
		ob_start();
		switch ( $campaign['campaign_type'] ) {
			case 'free_shipping_bar':
			case 'stock_scarcity':
				$class->render_shortcode( $campaign );
				break;

			default:
				$this->render_shortcode( $campaign );
				break;
		}
		do_action( 'revenue_campaign_after_render_shortcode', $campaign );

		Revenue_Product_Context::clear_product_context();

		// shortcode expects raw html.
		return ob_get_clean();
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
		if ( ! is_array( $campaign ) ) {
			return;
		}

		revenue()->update_campaign_impression( $campaign['id'] );

		$this->run_shortcode(
			$campaign,
			array(
				'display_type' => 'inpage',
				'position'     => '',
				'placement'    => 'product_page',
			),
			true,
		);
	}

	/**
	 * Run shortcode
	 *
	 * @param array $campaign Campaign.
	 * @param array $data Data.
	 * @param bool  $should_echo Should echo.
	 *
	 * @return mixed
	 */
	public function run_shortcode( $campaign, $data = array(), $should_echo = false ) {
		$display_type = $data['display_type'];
		$which_page   = $data['placement'];
		$campaign_id  = $campaign['id'];

		if ( revenue()->is_for_new_builder( $campaign ) ) {
			wp_enqueue_style( 'revenue-campaign' );
			wp_enqueue_style( 'revenue-campaign-buyx_gety' );
			wp_enqueue_style( 'revenue-campaign-volume' );
			wp_enqueue_style( 'revenue-campaign-double_order' );
			wp_enqueue_style( 'revenue-campaign-fbt' );
			wp_enqueue_style( 'revenue-campaign-mix_match' );
			wp_enqueue_style( 'revenue-utility' );
			wp_enqueue_style( 'revenue-responsive' );
			wp_enqueue_script( 'revenue-campaign' );
			wp_enqueue_script( 'revenue-slider' );
			wp_enqueue_script( 'revenue-add-to-cart' );
			wp_enqueue_script( 'revenue-variation-product-selection' );
			wp_enqueue_script( 'revenue-checkbox-handler' );
			wp_enqueue_script( 'revenue-double-order' );
			wp_enqueue_script( 'revenue-campaign-total' );
			wp_enqueue_script( 'revenue-countdown' );
			wp_enqueue_script( 'revenue-animated-add-to-cart' );
			wp_enqueue_style( 'revenue-animated-add-to-cart' );
			wp_enqueue_script( 'revenue-campaign-countdown' );
			wp_enqueue_style( 'revenue-campaign-countdown' );

		} else {

			wp_enqueue_style( 'revenue-v1-campaign' );
			wp_enqueue_style( 'revenue-v1-campaign-buyx_gety' );
			wp_enqueue_style( 'revenue-v1-campaign-double_order' );
			wp_enqueue_style( 'revenue-v1-campaign-fbt' );
			wp_enqueue_style( 'revenue-v1-campaign-mix_match' );
			wp_enqueue_style( 'revenue-v1-utility' );
			wp_enqueue_style( 'revenue-v1-responsive' );
			wp_enqueue_script( 'revenue-v1-campaign' );
			wp_enqueue_script( 'revenue-v1-add-to-cart' );
			wp_enqueue_script( 'revenue-v1-animated-add-to-cart' );
			wp_enqueue_style( 'revenue-v1-animated-add-to-cart' );

		}

		$campaign_view = $campaign['placement_settings'][ $which_page ]['builder_view'];
		$is_grid       = 'grid' === $campaign_view;

		$is_divider     = in_array( $campaign['campaign_type'], array( 'mix_match', 'frequently_bought_together', 'bundle_discount' ), true );
		$is_buy_x_get_y = in_array( $campaign['campaign_type'], array( 'buy_x_get_y' ), true );

		$this->campaign_additional_data[ $campaign['id'] ]['revenue_campaign_type']              = $campaign['campaign_type'];
		$this->campaign_additional_data[ $campaign['id'] ]['revenue_campaign_view']              = $campaign_view;
		$this->campaign_additional_data[ $campaign['id'] ]['offered_product_click_action']       = $campaign['offered_product_click_action'];
		$this->campaign_additional_data[ $campaign['id'] ]['offered_product_on_cart_action']     = $campaign['offered_product_on_cart_action'];
		$this->campaign_additional_data[ $campaign['id'] ]['animated_add_to_cart_enabled']       = $campaign['animated_add_to_cart_enabled'];
		$this->campaign_additional_data[ $campaign['id'] ]['add_to_cart_animation_trigger_type'] = $campaign['add_to_cart_animation_trigger_type'];
		$this->campaign_additional_data[ $campaign['id'] ]['add_to_cart_animation_type']         = $campaign['add_to_cart_animation_type'];
		$this->campaign_additional_data[ $campaign['id'] ]['add_to_cart_animation_start_delay']  = $campaign['add_to_cart_animation_start_delay'];
		$this->campaign_additional_data[ $campaign['id'] ]['free_shipping_enabled']              = $campaign['free_shipping_enabled'];
		$this->campaign_additional_data[ $campaign['id'] ]['is_campaign_divider']                = $is_divider;
		if ( $is_divider ) {
			$this->campaign_additional_data[ $campaign['id'] ]['revenue_divider_size'] = $campaign['builder']['sliderParent']['dividerSize'][ $display_type ] ?? null;
		}
		if ( $is_buy_x_get_y ) {
			if ( $is_grid ) {
				$this->campaign_additional_data[ $campaign['id'] ]['revenue_separator_width']  = $campaign['builder']['sliderParent']['separatorWidth'][ $display_type ] ?? null;
				$this->campaign_additional_data[ $campaign['id'] ]['revenue_separator_height'] = null;
			} else {
				$this->campaign_additional_data[ $campaign['id'] ]['revenue_separator_width']  = null;
				$this->campaign_additional_data[ $campaign['id'] ]['revenue_separator_height'] = $campaign['builder']['productsContainerWrapper']['separatorHeight'][ $display_type ] ?? null;
			}
		}
		$this->campaign_additional_data[ $campaign['id'] ]['revenue_slider_gap'] =
			isset(
				$campaign['builder']['sliderParent']['gap'][ $display_type ]
			)
				? $campaign['builder']['sliderParent']['gap'][ $display_type ]
				: '';
		if ( isset( $campaign['countdown_timer_enabled'] ) && 'yes' === $campaign['countdown_timer_enabled'] ) {

			$countdown_data = array();

			$end_date = revenue()->get_campaign_meta( $campaign['id'], 'countdown_end_date', true );
			$end_time = revenue()->get_campaign_meta( $campaign['id'], 'countdown_end_time', true );

			$end_date_time = $end_date . ' ' . $end_time;

			$have_start_date_time = ( 'schedule_to_later' === revenue()->get_campaign_meta( $campaign['id'], 'countdown_start_time_status', true ) );

			$start_date_time = '';
			if ( $have_start_date_time ) {
				$start_date = revenue()->get_campaign_meta( $campaign['id'], 'countdown_start_date', true );
				$start_time = revenue()->get_campaign_meta( $campaign['id'], 'countdown_start_time', true );

				$start_date_time = $start_date . ' ' . $start_time;
			}

			// If start_date_time is empty, set it to current date and time.
			if ( empty( $start_date_time ) ) {
				$start_date_time = current_time( 'mysql' );
			}

			$current_date_time = new DateTime( current_time( 'mysql' ) );

			$end_date_time_obj = new DateTime( $end_date_time );
			if ( $end_date_time_obj >= $current_date_time ) {
				$countdown_data = array(
					'end_time'   => $end_date_time,
					'start_time' => $start_date_time,
				);
			}

			$this->campaign_additional_data[ $campaign['id'] ]['countdown_data'] = $countdown_data;
		}

		if ( isset( $campaign['campaign_placement'] ) && 'multiple' !== $campaign['campaign_placement'] ) {
			$campaign['placement_settings'] = array(
				$campaign['campaign_placement'] => array(
					'page'                     => $campaign['campaign_placement'],
					'status'                   => 'yes',
					'display_style'            => $campaign['campaign_display_style'],
					'builder_view'             => $campaign['campaign_builder_view'],
					'inpage_position'          => $campaign['campaign_inpage_position'],
					'popup_animation'          => $campaign['campaign_popup_animation'],
					'popup_animation_delay'    => $campaign['campaign_popup_animation_delay'],
					'floating_position'        => $campaign['campaign_floating_position'],
					'floating_animation_delay' => $campaign['campaign_floating_animation_delay'],
				),
			);

			$campaign['placement_settings'] = $campaign['placement_settings'];
		}

		$placement_settings = isset( $campaign['placement_settings'][ $which_page ] ) ? $campaign['placement_settings'][ $which_page ] : '';

		if ( is_array( $placement_settings ) && isset( $placement_settings['display_style'] ) && 'popup' === $placement_settings['display_style'] ) {
			$this->campaign_additional_data[ $campaign['id'] ]['campaign_popup_animation']       = $placement_settings['popup_animation'] ?? '';
			$this->campaign_additional_data[ $campaign['id'] ]['campaign_popup_animation_delay'] = $placement_settings['popup_animation_delay'] ?? '';
		}

		if ( isset( $campaign['animated_add_to_cart_enabled'] ) && 'yes' === $campaign['animated_add_to_cart_enabled'] ) {

			$trigger_when    = revenue()->get_campaign_meta( $campaign['id'], 'add_to_cart_animation_trigger_type', true );
			$animation_type  = revenue()->get_campaign_meta( $campaign['id'], 'add_to_cart_animation_type', true );
			$animation_delay = 0;

			if ( 'loop' === $trigger_when && empty( $loop_animation ) ) {
				$animation_delay = revenue()->get_campaign_meta( $campaign['id'], 'add_to_cart_animation_start_delay', true ) ?? 0;
				$loop_animation  = array(
					'type'  => $animation_type,
					'delay' => $animation_delay,
				);
			} elseif ( 'on_hover' === $trigger_when && empty( $hover_animation ) ) {
				$hover_animation = $animation_type;
			}
			$this->animated_button_data[ $campaign['id'] ] = array(
				'loop_animation'  => $animation_type,
				'delay'           => $animation_delay,
				'hover_animation' => $animation_type,
			);
		}

		$this->localize_script();

		// Replace underscores with hyphens in the campaign type.
		$campaign_type = isset( $campaign['campaign_type'] ) ? str_replace( '_', '-', $campaign['campaign_type'] ) : 'normal-discount';

		$file_path = false;
		if ( isset( $campaign['campaign_display_style'] ) ) {
			$file_path = revenue()->get_campaign_path( $campaign, 'inpage', $campaign_type );
		}

		$file_path = apply_filters( 'revenue_campaign_shortcode_file_path', $file_path, $campaign );

		ob_start();
		if ( file_exists( $file_path ) ) {
			extract($data); //phpcs:ignore
			?>
			<div class="revx-template">
				<div class="revenue-campaign-shortcode">
					<?php
						do_action( 'revenue_before_campaign_render', $campaign['id'], $campaign );
						include $file_path;
					?>
				</div>
			</div>
			<?php
		}

		$output = ob_get_clean();
		if ( ! $should_echo ) {
			return $output;
		}
		// echo wp_kses( $output, revenue()->get_allowed_tag() ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * This is needed for countdown timer when it is used with shortcode.
	 * Localize data for countdown timer using Hiding File.
	 *
	 * @param array $campaign The campaign data.
	 *
	 * @return array
	 */
	public function count_down_localize_data( $campaign = array() ) {

		$countdown_timer_type = $campaign['countdown_timer_type'] ?? 'static';
		$evergreen_settings   = $campaign['countdown_timer_evergreen_settings'] ?? array();

		$evergreen_days        = $evergreen_settings['evergreen_days'] ?? 0;
		$evergreen_hours       = $evergreen_settings['evergreen_hours'] ?? 0;
		$evergreen_minutes     = $evergreen_settings['evergreen_minutes'] ?? 0;
		$evergreen_seconds     = $evergreen_settings['evergreen_seconds'] ?? 0;
		$repeat_after_finished = $evergreen_settings['repeatAfterFinished'] ?? 'no';

		$daily_recurring_settings = $campaign['countdown_timer_daily_recurring_settings'] ?? array();
		$set_select_mode          = $daily_recurring_settings['setSelectMode'] ?? 'dailyMode'; // weekdaysMode.
		$daily_time_slots         = $daily_recurring_settings['dailySlotsTimes'] ?? array();
		$weekly_time_slots        = $daily_recurring_settings['weeklyTimeSlot'] ?? array();
		$is_saturday              = $daily_recurring_settings['issaturdaySlot'] ?? 'no';
		$is_sunday                = $daily_recurring_settings['issundaySlot'] ?? 'no';
		$is_monday                = $daily_recurring_settings['ismondaySlot'] ?? 'no';
		$is_tuesday               = $daily_recurring_settings['istuesdaySlot'] ?? 'no';
		$is_wednesday             = $daily_recurring_settings['iswednesdaySlot'] ?? 'no';
		$is_thursday              = $daily_recurring_settings['isthursdaySlot'] ?? 'no';
		$is_friday                = $daily_recurring_settings['isfridaySlot'] ?? 'no';

		$action_type   = $campaign['countdown_timer_entire_site_action_type'] ?? 'makeFullAction';
		$action_link   = $campaign['countdown_timer_entire_site_action_link'] ?? '#';
		$action_enable = $campaign['countdown_timer_entire_site_action_enable'] ?? 'yes';

		$static_settings     = $campaign['countdown_timer_static_settings'] ?? array();
		$current_active_page = is_shop() ? 'shop_page' : ( is_product() ? 'product_page' : ( is_cart() ? 'cart_page' : 'others' ) );

		$static_time_frame_mode    = $static_settings['setTimeFrame'] ?? 'startNow';
		$static_end_date_time      = $static_settings['setEndDateTime'] ?? '2025-03-03';
		$static_end_select_time    = $static_settings['setEndSelectTime'] ?? '00:30';
		$static_timer_end_behavior = $static_settings['timerEndBehavior'] ?? 'hideTimer';
		$static_start_date_time    = $static_settings['setStartDateTime'] ?? '2025-02-27';
		$static_start_select_time  = $static_settings['setStartSelectTime'] ?? '01:00';
		$is_product_page_enable    = $campaign['placement_settings']['product_page']['status'] ?? 'no';
		$is_shop_page_enable       = $campaign['placement_settings']['shop_page']['status'] ?? 'no';
		$is_cart_page_enable       = $campaign['placement_settings']['cart_page']['status'] ?? 'no';

		// Format the dates for JavaScript.
		$end_date_time   = gmdate( 'Y-m-d H:i:s', strtotime( "$static_end_date_time $static_end_select_time" ) );
		$start_date_time = gmdate( 'Y-m-d H:i:s', strtotime( "$static_start_date_time $static_start_select_time" ) );

		$is_close_button_enable = $campaign['countdown_timer_enable_close_button'] ?? 'no';
		$is_all_page_enable     = 'no';
		if ( isset( $campaign['placement_settings']['all_page'] ) && ! empty( $campaign['placement_settings']['all_page'] ) && 'yes' === $campaign['placement_settings']['all_page']['status'] ) {
			$is_all_page_enable = 'yes';
		}

		$data = array(
			'timeFrameMode'       => esc_js( $static_time_frame_mode ),
			'endDateTime'         => esc_js( $end_date_time ),
			'startDateTime'       => esc_js( $start_date_time ),
			'timerEndBehavior'    => esc_js( $static_timer_end_behavior ),
			'countdownTimerType'  => esc_js( $countdown_timer_type ),
			'currentPage'         => esc_js( $current_active_page ),
			'actionType'          => esc_js( $action_type ),
			'actionLink'          => esc_js( $action_link ),
			'actionEnable'        => esc_js( $action_enable ),
			'isProductPageEnable' => esc_js( $is_product_page_enable ),
			'isShopPageEnable'    => esc_js( $is_shop_page_enable ),
			'isCartPageEnable'    => esc_js( $is_cart_page_enable ),
			'isAllPageEnable'     => esc_js( $is_all_page_enable ),
			'evergreenDays'       => esc_js( $evergreen_days ),
			'evergreenHours'      => esc_js( $evergreen_hours ),
			'evergreenMinutes'    => esc_js( $evergreen_minutes ),
			'evergreenSeconds'    => esc_js( $evergreen_seconds ),
			'repeatAfterFinished' => esc_js( $repeat_after_finished ),
			'setSelectMode'       => esc_js( $set_select_mode ),
			'dailyTimeSlots'      => $daily_time_slots,
			'weeklyTimeSlots'     => $weekly_time_slots,
			'isSaturdaySlot'      => esc_js( $is_saturday ),
			'isSundaySlot'        => esc_js( $is_sunday ),
			'isMondaySlot'        => esc_js( $is_monday ),
			'isTuesdaySlot'       => esc_js( $is_tuesday ),
			'isWednesdaySlot'     => esc_js( $is_wednesday ),
			'isThursdaySlot'      => esc_js( $is_thursday ),
			'isFridaySlot'        => esc_js( $is_friday ),
		);

		return $data;
	}


	/**
	 * WooCommerce after item quantity update.
	 *
	 * @param string  $cart_item_key Cart item key.
	 * @param integer $quantity quantity.
	 * @return void
	 */
	public function woocommerce_after_cart_item_quantity_update( $cart_item_key, $quantity = 0 ) {
		$cart_item = WC()->cart->cart_contents[ $cart_item_key ];
		if ( isset( $cart_item['revx_campaign_id'], $cart_item['revx_campaign_type'] ) ) {
			$campaign_id   = $cart_item['revx_campaign_id'];
			$campaign_type = $cart_item['revx_campaign_type'];

			/**
			 * Hook for add item meta data
			 * Valid Campaign Type:
			 * normal_discount
			 * bundle_discount
			 * volume_discount
			 * buy_x_get_y
			 * mix_match
			 * frequently_bought_together
			 * spending_goal
			 */
			do_action( "revenue_campaign_{$campaign_type}_after_item_quantity_updated", $cart_item, $cart_item_key, $quantity );
		}
	}




	/**
	 * Integrates subscription plugin functionality by adding filters for recurring price calculations.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function subscriptions_plugin_integrations() {
		add_filter( 'ywsbs_subscription_recurring_price', array( $this, 'yith_subscription_recurring_price' ), 10, 3 );
	}

	/**
	 * Filters the recurring price for YITH WooCommerce Subscription.
	 *
	 * @since 1.0.0
	 * @param float      $recurring_price The recurring price.
	 * @param WC_Product $product The product object.
	 * @param array      $subscription_info Subscription information.
	 * @return float Returns the product's price.
	 */
	public function yith_subscription_recurring_price( $recurring_price, $product, $subscription_info ) {

		if ( 0 == did_action( 'woocommerce_before_calculate_totals' ) ) {
			WC()->cart->calculate_totals();
		}
		return $product->get_price();
	}
}
