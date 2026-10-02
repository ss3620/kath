<?php
namespace RevenuePro;

defined( 'ABSPATH' ) || exit;

/**
 * RevenueX Campaign
 *
 * @hooked on init
 */
class Revenue_Pro_Campaign {

	public function __construct() {

		add_filter( 'revenue_campaign_instance', array( $this, 'set_campaign_instance' ), 10, 2 );
		add_filter( 'revenue_campaign_file_path', array( $this, 'set_shortcode_pro_file_path' ), 10, 2 );
		add_filter( 'revenue_template_part_path', array( $this, 'set_template_part_path' ), 10, 2 );
		add_filter( 'revenue_campaign_template_path', array( $this, 'set_campaign_template_path' ), 10, 4 );
		add_filter( 'revenue_campaign_add_to_cart_dispatch', array( $this, 'add_pro_campaign_to_cart' ), 10, 3 );
		add_filter( 'revenue_campaign_hideable_on_cart_types', array( $this, 'add_hideable_on_cart_types' ) );
		add_filter( 'revenue_campaign_supports_product_title_link', array( $this, 'supports_product_title_link' ), 10, 2 );
		add_filter( 'revenue_campaign_wrapper_divider_types', array( $this, 'add_wrapper_divider_types' ) );
		add_filter( 'revenue_campaign_item_divider_types', array( $this, 'add_item_divider_types' ) );
		add_filter( 'revenue_campaign_hides_add_to_cart_types', array( $this, 'add_hides_add_to_cart_types' ) );
		add_filter( 'revenue_campaign_card_template_two_types', array( $this, 'add_card_template_two_types' ) );
		add_filter( 'revenue_campaign_offer_checkbox_types', array( $this, 'add_offer_checkbox_types' ) );
		add_filter( 'revenue_campaign_hides_offer_image_link_types', array( $this, 'add_hides_offer_image_link_types' ) );
		add_filter( 'revenue_campaign_unit_price_types', array( $this, 'add_unit_price_types' ) );
		add_filter( 'revenue_campaign_per_product_quantity_input_types', array( $this, 'add_per_product_quantity_input_types' ) );
		add_filter( 'revenue_campaign_adds_trigger_product_types', array( $this, 'add_adds_trigger_product_types' ) );
		add_filter( 'revenue_campaign_trigger_search_drops_children_types', array( $this, 'add_trigger_search_drops_children_types' ) );
		add_filter( 'revenue_campaign_trigger_search_skips_parent_types', array( $this, 'add_trigger_search_skips_parent_types' ) );
		add_filter( 'revenue_campaign_trigger_stock_types', array( $this, 'add_trigger_stock_types' ) );
		add_filter( 'revenue_campaign_localize_data', array( $this, 'add_localize_data' ) );
		add_filter( 'revenue_campaign_product_picker_types', array( $this, 'add_product_picker_types' ) );
		add_filter( 'revenue_campaign_add_to_cart_button_class', array( $this, 'add_to_cart_button_class' ), 10, 2 );
		add_filter( 'revenue_campaign_inpage_container_class', array( $this, 'inpage_container_class' ), 10, 2 );
		add_filter( 'revenue_campaign_site_wide_placement_types', array( $this, 'add_site_wide_placement_types' ) );
		add_filter( 'revenue_campaign_upsell_slider_types', array( $this, 'add_upsell_slider_types' ) );
		add_filter( 'revenue_campaign_upsell_products_key', array( $this, 'set_upsell_products_key' ), 10, 2 );
		add_filter( 'revenue_campaign_upsell_status_key', array( $this, 'set_upsell_status_key' ), 10, 2 );
		add_filter( 'revenue_campaign_progressbar_has_label', array( $this, 'progressbar_has_label' ), 10, 2 );
		add_action( 'revenue_render_progressbar_markers', array( $this, 'render_progressbar_markers' ), 10, 3 );
		add_filter( 'revenue_campaign_variation_trigger_search_types', array( $this, 'add_variation_trigger_search_types' ) );
		add_filter( 'revenue_campaign_expand_variable_trigger_search_types', array( $this, 'add_expand_variable_trigger_search_types' ) );
		add_filter( 'revenue_campaign_prepend_trigger_offer', array( $this, 'prepend_trigger_offer' ), 10, 3 );
	}

	/**
	 * Frequently bought together wraps its divider in an extra element.
	 *
	 * @param array $types Campaign types.
	 * @return array
	 */
	public function add_wrapper_divider_types( $types ) {
		return array_merge( (array) $types, array( 'frequently_bought_together' ) );
	}

	/**
	 * Frequently bought together and mix and match draw a divider between offer items.
	 *
	 * @param array $types Campaign types.
	 * @return array
	 */
	public function add_item_divider_types( $types ) {
		return array_merge( (array) $types, array( 'frequently_bought_together', 'mix_match' ) );
	}

	/**
	 * Frequently bought together adds the whole set to the cart, not single products.
	 *
	 * @param array $types Campaign types.
	 * @return array
	 */
	public function add_hides_add_to_cart_types( $types ) {
		return array_merge( (array) $types, array( 'frequently_bought_together' ) );
	}

	/**
	 * Pro campaign types that use the second product card layout.
	 *
	 * @param array $types Campaign types.
	 * @return array
	 */
	public function add_card_template_two_types( $types ) {
		return array_merge( (array) $types, array( 'mix_match', 'spending_goal', 'frequently_bought_together' ) );
	}

	/**
	 * Frequently bought together puts a selection checkbox on each offer item.
	 *
	 * @param array $types Campaign types.
	 * @return array
	 */
	public function add_offer_checkbox_types( $types ) {
		return array_merge( (array) $types, array( 'frequently_bought_together' ) );
	}

	/**
	 * Double order offers show the same product, so the image links nowhere.
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	public function add_hides_offer_image_link_types( $types ) {
		return array_merge( (array) $types, array( 'double_order' ) );
	}

	/**
	 * Mix and match is the one type where the shopper assembles their own selection.
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	public function add_product_picker_types( $types ) {
		return array_merge( (array) $types, array( 'mix_match' ) );
	}

	/**
	 * Both set-building types need every trigger product in stock before they run.
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	/**
	 * Mix and match prices each picked product on its own, never as "N x price".
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	public function add_unit_price_types( $types ) {
		return array_merge( (array) $types, array( 'mix_match' ) );
	}

	/**
	 * Frequently bought together shows one quantity field per product, not per offer row.
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	public function add_per_product_quantity_input_types( $types ) {
		return array_merge( (array) $types, array( 'frequently_bought_together' ) );
	}

	/**
	 * Frequently bought together adds the trigger product along with the offers.
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	public function add_adds_trigger_product_types( $types ) {
		return array_merge( (array) $types, array( 'frequently_bought_together' ) );
	}

	/**
	 * Both set-building types pick whole products as triggers, not variations.
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	public function add_trigger_search_drops_children_types( $types ) {
		return array_merge( (array) $types, array( 'mix_match', 'frequently_bought_together' ) );
	}

	/**
	 * Both set-building types list variations only, never the parent product.
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	public function add_trigger_search_skips_parent_types( $types ) {
		return array_merge( (array) $types, array( 'mix_match', 'frequently_bought_together' ) );
	}

	/**
	 * Both set-building types need every trigger product in stock before they run.
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	public function add_trigger_stock_types( $types ) {
		return array_merge( (array) $types, array( 'frequently_bought_together', 'mix_match' ) );
	}

	/**
	 * Mix and match runtime settings the storefront script reads.
	 *
	 * @param array $data Localized campaign data.
	 * @return array
	 */
	public function add_localize_data( $data ) {
		$data['mix_match_count_mode']                 = apply_filters( 'revenue_mix_match_count_mode', 'unique' );
		$data['mix_match_hide_footer_until_selected'] = (bool) apply_filters( 'revenue_mix_match_hide_footer_until_selected', false );

		return $data;
	}

	/**
	 * Mix and match add-product buttons carry their own class.
	 *
	 * @param string $class         Button classes.
	 * @param string $campaign_type Campaign type slug.
	 * @return string
	 */
	public function add_to_cart_button_class( $class, $campaign_type ) {
		return 'mix_match' === $campaign_type ? $class . ' revx-mix-match-product-btn' : $class;
	}

	/**
	 * Mix and match fills the width of its inpage container.
	 *
	 * @param string $class         Container classes.
	 * @param string $campaign_type Campaign type slug.
	 * @return string
	 */
	public function inpage_container_class( $class, $campaign_type ) {
		return 'mix_match' === $campaign_type ? trim( $class . ' revx-w-full' ) : $class;
	}

	/**
	 * Spending goal can sit at the top or bottom of every page, so it needs that placement CSS.
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	public function add_site_wide_placement_types( $types ) {
		return array_merge( (array) $types, array( 'spending_goal' ) );
	}

	/**
	 * Spending goal renders its offers as an upsell slider, same as the free shipping bar.
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	public function add_upsell_slider_types( $types ) {
		return array_merge( (array) $types, array( 'spending_goal' ) );
	}

	/**
	 * Spending goal keeps its upsell groups under its own meta key.
	 *
	 * @param string $key           Meta key.
	 * @param string $campaign_type Campaign type slug.
	 * @return string
	 */
	public function set_upsell_products_key( $key, $campaign_type ) {
		return 'spending_goal' === $campaign_type ? 'spending_goal_upsell_products' : $key;
	}

	/**
	 * Spending goal keeps its upsell status under its own meta key.
	 *
	 * @param string $key           Meta key.
	 * @param string $campaign_type Campaign type slug.
	 * @return string
	 */
	public function set_upsell_status_key( $key, $campaign_type ) {
		return 'spending_goal' === $campaign_type ? 'spending_goal_upsell_product_status' : $key;
	}

	/**
	 * Spending goal step markers carry labels, so the bar needs room beneath it.
	 *
	 * @param bool  $has_label Whether the bar reserves label space.
	 * @param array $campaign  Campaign data.
	 * @return bool
	 */
	public function progressbar_has_label( $has_label, $campaign ) {
		if ( 'spending_goal' !== $campaign['campaign_type'] || empty( $campaign['offers'] ) || ! is_array( $campaign['offers'] ) ) {
			return $has_label;
		}

		foreach ( $campaign['offers'] as $step ) {
			if ( ! empty( $step['reward_name'] ) ) {
				return true;
			}
		}

		return $has_label;
	}

	/**
	 * Draw the spending goal step markers inside Free's shared progress bar.
	 *
	 * @param array     $campaign      Campaign data.
	 * @param float|int $progress      Progress percentage.
	 * @param array     $template_data Builder data, already carrying is_rtl.
	 * @return void
	 */
	public function render_progressbar_markers( $campaign, $progress, $template_data ) {
		if ( 'spending_goal' !== $campaign['campaign_type'] ) {
			return;
		}

		$steps = ( ! empty( $campaign['offers'] ) && is_array( $campaign['offers'] ) ) ? $campaign['offers'] : false;
		if ( ! $steps ) {
			return;
		}

		$total_steps   = count( $steps );
		$is_show_icon  = 'yes' === ( $campaign['spending_goal_progress_show_icon'] ?? '' );
		$required_goal = 0;

		foreach ( $steps as $idx => $step ) {
			if ( ! isset( $step['spending_goal'] ) ) {
				continue;
			}
			$required_goal += $step['spending_goal'];
			\Revenue\Revenue_Pro_Template_Utils::render_spg_step(
				$idx,
				$step,
				$progress,
				$total_steps,
				$template_data,
				$required_goal,
				$is_show_icon
			);
		}
	}

	/**
	 * Mix and match picks variations directly, never the parent product.
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	public function add_variation_trigger_search_types( $types ) {
		return array_merge( (array) $types, array( 'mix_match' ) );
	}

	/**
	 * Double order doubles a single variation, so a variable trigger expands to its children.
	 *
	 * @param array $types Campaign type slugs.
	 * @return array
	 */
	public function add_expand_variable_trigger_search_types( $types ) {
		return array_merge( (array) $types, array( 'double_order' ) );
	}

	/**
	 * Frequently bought together shows its trigger products as the first offer.
	 *
	 * @param array       $offers          Offers for the campaign.
	 * @param array       $campaign        Campaign data.
	 * @param \WC_Product $current_product Product in context.
	 * @return array
	 */
	public function prepend_trigger_offer( $offers, $campaign, $current_product ) {
		if ( 'frequently_bought_together' !== $campaign['campaign_type'] || ! $current_product ) {
			return $offers;
		}

		$is_required_product = isset( $campaign['fbt_is_trigger_product_required'] ) ? 'yes' === $campaign['fbt_is_trigger_product_required'] : false;
		$trigger_relation    = isset( $campaign['campaign_trigger_relation'] ) ? $campaign['campaign_trigger_relation'] : 'or';

		if ( empty( $trigger_relation ) ) {
			$trigger_relation = 'or';
		}

		$is_category   = ( 'category' === $campaign['campaign_trigger_type'] ) || ( 'all_products' === $campaign['campaign_trigger_type'] );
		$trigger_items = revenue()->getTriggerProductsData( $campaign['campaign_trigger_items'], $trigger_relation, $current_product->get_id(), $is_category );

		$new_products = array();
		foreach ( $trigger_items as $trigger ) {
			$item_id = $trigger['item_id'] ?? null;
			if ( null !== $item_id ) {
				$new_products[] = $item_id;
			}
		}

		array_unshift(
			$offers,
			array(
				'products'            => $new_products,
				'is_required_product' => $is_required_product,
			)
		);

		return $offers;
	}

	/**
	 * Pro campaign types that honour the "hide campaign" cart action.
	 *
	 * @param array $types Campaign types.
	 * @return array
	 */
	public function add_hideable_on_cart_types( $types ) {
		return array_merge( (array) $types, array( 'mix_match', 'frequently_bought_together' ) );
	}

	/**
	 * Double order links the product title to the order, not the product page.
	 *
	 * @param bool  $supports Whether the title is wrapped in a product link.
	 * @param array $campaign Campaign data.
	 * @return bool
	 */
	public function supports_product_title_link( $supports, $campaign ) {
		if ( isset( $campaign['campaign_type'] ) && 'double_order' === $campaign['campaign_type'] ) {
			return false;
		}

		return $supports;
	}

	/**
	 * Handle the shared add-to-cart endpoint for Pro campaign types.
	 *
	 * @param mixed $status   Previous provider result.
	 * @param array $campaign Campaign data.
	 * @param array $context  Sanitized shared request context.
	 * @return mixed
	 */
	// phpcs:disable WordPress.Security.NonceVerification.Missing -- Verified by the Free AJAX controller before dispatch.
	public function add_pro_campaign_to_cart( $status, $campaign, $context ) {
		if ( null !== $status || empty( $campaign['campaign_type'] ) ) {
			return $status;
		}

		switch ( $campaign['campaign_type'] ) {
			case 'mix_match':
				return $this->add_mix_match_to_cart( $campaign, $context );
			case 'frequently_bought_together':
				return $this->add_frequently_bought_together_to_cart( $campaign, $context );
			case 'spending_goal':
				return $this->add_spending_goal_to_cart( $context );
			default:
				return $status;
		}
	}

	/**
	 * Add mix-and-match selections to the cart.
	 *
	 * @param array $campaign Campaign data.
	 * @param array $context  Shared request context.
	 * @return mixed
	 */
	private function add_mix_match_to_cart( $campaign, $context ) {
		$required_products = isset( $campaign['mix_match_is_required_products'] ) && 'yes' === $campaign['mix_match_is_required_products']
			? revenue()->get_campaign_meta( $campaign['id'], 'mix_match_required_products', true )
			: array();
		$mix_match_data    = isset( $_POST['mix_match_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['mix_match_data'] ) ) : array();
		$cart_item_data    = array_merge(
			$context['cart_item_data'],
			array(
				'revx_required_products'  => $required_products,
				'revx_mix_match_products' => array_keys( $mix_match_data ),
				'revx_offer_data'         => $context['offers'],
			)
		);
		$status            = false;

		if ( isset( $_POST['products'] ) && is_array( $_POST['products'] ) ) {
			foreach ( wp_unslash( $_POST['products'] ) as $product_data ) {
				$product_id   = isset( $product_data['product_id'] ) ? absint( $product_data['product_id'] ) : 0;
				$quantity     = isset( $product_data['quantity'] ) ? wc_stock_amount( $product_data['quantity'] ) : 0;
				$variation_id = isset( $product_data['variation_id'] ) ? absint( $product_data['variation_id'] ) : 0;
				$attributes   = isset( $product_data['selected_attributes'] ) ? revenue()->sanitize_posted_attributes( $product_data['selected_attributes'] ) : array();
				$status       = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes, $cart_item_data );
				revenue()->increment_campaign_add_to_cart_count( $context['campaign_id'], $product_id );
				if ( $status ) {
					do_action( 'revenue_item_added_to_cart', $status, $product_id, $context['campaign_id'] );
				}
			}
		} else {
			foreach ( $mix_match_data as $product_id => $quantity ) {
				$status = WC()->cart->add_to_cart( absint( $product_id ), wc_stock_amount( $quantity ), 0, array(), $cart_item_data );
				revenue()->increment_campaign_add_to_cart_count( $context['campaign_id'], $product_id );
				if ( $status ) {
					do_action( 'revenue_item_added_to_cart', $status, $product_id, $context['campaign_id'] );
				}
			}
		}

		return $status;
	}

	/**
	 * Add frequently-bought-together selections to the cart.
	 *
	 * @param array $campaign Campaign data.
	 * @param array $context  Shared request context.
	 * @return mixed
	 */
	private function add_frequently_bought_together_to_cart( $campaign, $context ) {
		$required_products = isset( $_POST['requiredProducts'] ) ? wp_unslash( $_POST['requiredProducts'] ) : array();
		$required_products = is_array( $required_products ) ? $required_products : array( $required_products );
		$required_products = array_filter( array_map( 'absint', $required_products ) );
		$fbt_data          = isset( $_POST['fbt_data'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['fbt_data'] ) ) : array();

		if ( 'yes' === revenue()->get_campaign_meta( $context['campaign_id'], 'fbt_is_trigger_product_required', true ) ) {
			foreach ( $required_products as $required_product ) {
				if ( ! isset( $fbt_data[ $required_product ] ) ) {
					wp_send_json_error( array( 'message' => __( 'Required trigger product not found.', 'revenue-pro' ) ), 400 );
				}
			}
		}

		$cart_item_data = array_merge(
			$context['cart_item_data'],
			array(
				'revx_fbt_required_products' => $required_products,
				'revx_fbt_data'              => $fbt_data,
				'revx_offer_data'            => $context['offers'],
			)
		);
		$status         = false;

		if ( isset( $_POST['products'] ) && is_array( $_POST['products'] ) ) {
			$products                             = wp_unslash( $_POST['products'] );
			$cart_item_data['revx_products_data'] = $products;
			$trigger_keys                         = array();
			$item_keys                            = array();

			foreach ( $products as $product_data ) {
				$product_id   = isset( $product_data['product_id'] ) ? absint( $product_data['product_id'] ) : 0;
				$quantity     = isset( $product_data['quantity'] ) ? wc_stock_amount( $product_data['quantity'] ) : 0;
				$variation_id = isset( $product_data['variation_id'] ) ? absint( $product_data['variation_id'] ) : 0;
				$attributes   = isset( $product_data['selected_attributes'] ) ? revenue()->sanitize_posted_attributes( $product_data['selected_attributes'] ) : array();
				$status       = WC()->cart->add_to_cart( $product_id, $quantity, $variation_id, $attributes, $cart_item_data );
				if ( $status ) {
					if ( in_array( $product_id, $required_products, true ) ) {
						$trigger_keys[] = $status;
					} else {
						$item_keys[] = $status;
					}
					do_action( 'revenue_item_added_to_cart', $status, $product_id, $context['campaign_id'] );
				}
			}

			foreach ( $trigger_keys as $key ) {
				WC()->cart->cart_contents[ $key ]['revx_fbt_all_triggers_key'] = $trigger_keys;
				WC()->cart->cart_contents[ $key ]['revx_fbt_all_items_key']    = $item_keys;
			}
		} else {
			foreach ( $fbt_data as $product_id => $quantity ) {
				$status = WC()->cart->add_to_cart( absint( $product_id ), wc_stock_amount( $quantity ), 0, array(), $cart_item_data );
				if ( $status ) {
					do_action( 'revenue_item_added_to_cart', $status, $product_id, $context['campaign_id'] );
				}
			}
		}

		revenue()->increment_campaign_add_to_cart_count( $context['campaign_id'] );

		return $status;
	}

	/**
	 * Add a spending-goal upsell product to the cart.
	 *
	 * @param array $context Shared request context.
	 * @return mixed
	 */
	private function add_spending_goal_to_cart( $context ) {
		$cart_item_data                              = $context['cart_item_data'];
		$cart_item_data['revx_spending_goal_upsell'] = 'yes';
		$status                                      = WC()->cart->add_to_cart(
			$context['product_id'],
			$context['quantity'],
			$context['variation_id'],
			$context['attributes'],
			$cart_item_data
		);

		revenue()->increment_campaign_add_to_cart_count( $context['campaign_id'] );
		if ( $status ) {
			do_action( 'revenue_item_added_to_cart', $status, $context['product_id'], $context['campaign_id'] );
		}

		return $status;
	}
	// phpcs:enable WordPress.Security.NonceVerification.Missing

	public function set_template_part_path( $file_path, $name ) {
		if ( 'double_order_countdown_timer' === $name ) {
			return REVENUE_PRO_PATH . 'includes/campaigns/views/parts/double_order_countdown_timer.php';
		}

		return $file_path;
	}

	public function set_campaign_template_path( $file_path, $campaign, $view_type, $folder_path ) {
		$pro_folders = array( 'double-order', 'mix-match', 'frequently-bought-together', 'spending-goal' );
		if ( ! in_array( $folder_path, $pro_folders, true ) ) {
			return $file_path;
		}

		return REVENUE_PRO_PATH . 'includes/campaigns/views/' . $folder_path . '/template1.php';
	}

	public function set_shortcode_pro_file_path( $file_path, $campaign_type ) {
		switch ( $campaign_type ) {
			case 'mix_match':
			case 'frequently_bought_together':
			case 'double_order':
			case 'spending_goal':
				$file_path = REVENUE_PRO_PATH;
				break;

			default:
				// code...
				break;
		}

		return $file_path;
	}


	public function set_campaign_instance( $class, $type ) {

		// A never-activated or removed key means nothing goes; an expired one still
		// renders so an existing storefront doesn't break for a lapsed subscriber.
		if ( ! License::is_valid() && ! License::is_expired() ) {
			return $class;
		}

		switch ( $type ) {
			case 'mix_match':
				$class = Revenue_Mix_Match::instance();
				break;
			case 'frequently_bought_together':
				$class = Revenue_Frequently_Bought_Together::instance();
				break;
			case 'double_order':
				$class = Revenue_Double_Order::instance();
				break;
			case 'spending_goal':
				$class = Revenue_Spending_Goal::instance();
				break;
		}

		return $class;
	}
}
