<?php //phpcs:ignore

namespace PRAD\Includes\Compatibility;

use PRAD\Includes\Xpo;

defined( 'ABSPATH' ) || exit;

/**
 * ShopCompatibilty class.
 */
class ShopCompatibilty {
	/**
	 * List of checked product options for compatibility.
	 *
	 * @var array $product_options_checked
	 * Stores the list of product options that have been checked for compatibility.
	 * This array is used to prevent redundant compatibility checks on the same product options.
	 */
	private $product_options_checked = array();

	/**
	 * Class constructor.
	 *
	 * Hooks into various WooCommerce filters to modify product behavior:
	 * - 'woocommerce_product_add_to_cart_text': Customizes the add to cart button text.
	 * - 'woocommerce_product_add_to_cart_url': Customizes the add to cart button URL.
	 * - 'woocommerce_product_supports': Modifies product support capabilities.
	 * - 'woocommerce_product_duplicate': Handles actions when a product is duplicated.
	 */
	public function __construct() {
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'handle_add_to_cart_text' ), 9999, 2 );
		add_filter( 'woocommerce_product_add_to_cart_url', array( $this, 'handle_add_to_cart_url' ), 9999, 2 );
		add_filter( 'woocommerce_product_supports', array( $this, 'handle_product_support' ), 9999, 3 );
		add_filter( 'woocommerce_product_duplicate', array( $this, 'on_product_duplicate' ), 9999, 2 );
	}

	/**
	 * Handles cleanup of specific product meta when a product is duplicated.
	 *
	 * This method is triggered during the product duplication process.
	 * It removes the 'prad_product_assigned_meta_inc' and 'prad_product_assigned_meta_exc'
	 * meta fields from the duplicated product to prevent carrying over these meta values.
	 *
	 * @param WC_Product $duplicate The duplicated product object.
	 * @param WC_Product $product   The original product object being duplicated.
	 *
	 * @return void
	 */
	public function on_product_duplicate( $duplicate, $product ) {
		delete_post_meta( $duplicate->get_id(), 'prad_product_assigned_meta_inc' );
		delete_post_meta( $duplicate->get_id(), 'prad_product_assigned_meta_exc' );
	}

	/**
	 * Handles product support for WooCommerce features.
	 *
	 * @param bool       $support  Current support value.
	 * @param string     $feature  Feature name.
	 * @param WC_Product $product  Product object.
	 *
	 * @return bool Modified support value.
	 */
	public function handle_product_support( $support, $feature, $product ) {
		if ( 'ajax_add_to_cart' === $feature && $this->product_has_options( $product ) && Xpo::get_prad_settings_item( 'enableSelectOptionInShop', true ) ) {
			$support = false;
		}
		return $support;
	}

	/**
	 * Handles the add to cart button text for products with options.
	 *
	 * @param string     $btn_text Button text.
	 * @param WC_Product $product  Product object.
	 *
	 * @return string Modified button text.
	 */
	public function handle_add_to_cart_text( $btn_text, $product ) {
		if ( $this->product_has_options( $product ) && Xpo::get_prad_settings_item( 'enableSelectOptionInShop', true ) ) {
			$btn_text = Xpo::get_prad_settings_item( 'shopAddToCartText', 'Select Options' );
		}
		return $btn_text;
	}
	/**
	 * Handles the add to cart button URL for products with options.
	 *
	 * @param string     $btn_url Button URL.
	 * @param WC_Product $product Product object.
	 *
	 * @return string Modified button URL.
	 */
	public function handle_add_to_cart_url( $btn_url, $product ) {
		if ( $this->product_has_options( $product ) && Xpo::get_prad_settings_item( 'enableSelectOptionInShop', true ) ) {
			$btn_url = $product->get_permalink();
		}
		return $btn_url;
	}

	/**
	 * Checks if a product has options assigned.
	 *
	 * @param WC_Product $product Product object.
	 *
	 * @return bool True if product has options, false otherwise.
	 */
	public function product_has_options( $product ) {
		if ( in_array( $product->get_type(), array( 'grouped', 'external' ) ) ) {
			return false;
		}

		$product_id = $product->get_id();
		$is_applied = isset( $this->product_options_checked[ $product_id ] ) ?
			$this->product_options_checked[ $product_id ]
			:
			$this->is_any_vaild_option_available( $product_id );

		$this->product_options_checked[ $product_id ] = $is_applied;

		return $is_applied;
	}

	/**
	 * Checks if any valid option is available for a product.
	 *
	 * @param int $product_id Product ID.
	 *
	 * @return bool True if any valid option is available, false otherwise.
	 */
	public function is_any_vaild_option_available( $product_id ) {

		$option_ids = product_addons()->get_product_option_ids( $product_id );

		if ( is_array( $option_ids ) && ! empty( $option_ids ) ) {
			foreach ( $option_ids as $k => $opt_id ) {
				$status = get_post_status( $opt_id );
				if ( 'publish' === $status ) {
					$content = get_post_meta( $opt_id, 'prad_addons_blocks', true );
					$content = wp_json_encode( $content );
					$content = json_decode( $content );

					if ( ! empty( $content ) ) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Checks if any required field exists in the blocks array.
	 *
	 * @param array $blocksarray Array of block objects.
	 *
	 * @return bool True if a required field exists, false otherwise.
	 */
	public function has_required( $blocksarray ) {
		try {
			foreach ( $blocksarray as $field ) {
				if (
					isset( $field->required ) &&
					true === $field->required
				) {
					return true;
				}
				if ( isset( $field->innerBlocks ) ) { //phpcs:ignore
					$result = $this->has_required( $field->innerBlocks ); //phpcs:ignore
					if ( null !== $result ) {
						return $result;
					}
				}
			}
		} catch ( \Exception $e ) {
			return false;
		}
		return false;
	}
}
