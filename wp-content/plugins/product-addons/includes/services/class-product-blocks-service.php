<?php
/**
 * Product Blocks Service
 *
 * @package PRAD
 * @since 1.0.0
 */

namespace PRAD\Includes\Services;

defined( 'ABSPATH' ) || exit;

/**
 * Product Blocks Service Class
 */
class Product_Blocks_Service {

	/**
	 * Cache for blocks data
	 *
	 * @var array
	 */
	private array $blocks_cache = array();

	/**
	 * Cache for price data
	 *
	 * @var array
	 */
	private array $price_cache = array();

	/**
	 * Get blocks data for a product
	 *
	 * @param int $product_id Product ID to retrieve blocks for.
	 * @return array
	 */
	public function get_product_blocks_data( int $product_id ): array {
		// Check cache first.
		if ( isset( $this->blocks_cache[ $product_id ] ) ) {
			return $this->blocks_cache[ $product_id ];
		}

		$result = array(
			'blocks'        => array(),
			'published_ids' => array(),
			'total_addons'  => 0,
		);

		// Get option IDs for this product.
		$option_ids = product_addons()->get_product_option_ids( $product_id );

		if ( empty( $option_ids ) ) {
			return $result;
		}

		foreach ( $option_ids as $option_id ) {
			$status = get_post_status( $option_id );

			if ( 'publish' === $status ) {
				$blocks_content = $this->get_addon_blocks_content( $option_id );

				if ( ! empty( $blocks_content ) ) {
					// Render addon CSS if needed.
					$this->maybe_render_addon_css( $option_id );

					$result['blocks'][ $option_id ] = $blocks_content;
					$result['published_ids'][]      = $option_id;
					++$result['total_addons'];
				}
			} elseif ( ! $status ) {
				// Clean up deleted options.
				do_action( 'prad_delete_option_product_meta', $option_id );
			}
		}

		// Cache the result.
		$this->blocks_cache[ $product_id ] = $result;

		return $result;
	}

	/**
	 * Get blocks data for a product without css
	 *
	 * @param int $product_id Product ID to retrieve blocks for.
	 * @return array
	 */
	public function get_product_blocks( int $product_id ): array {
		$cache_key = 'raw_' . $product_id;

		if ( isset( $this->blocks_cache[ $cache_key ] ) ) {
			return $this->blocks_cache[ $cache_key ];
		}

		$result = array(
			'blocks'        => array(),
			'published_ids' => array(),
		);

		$option_ids = product_addons()->get_product_option_ids( $product_id );

		if ( empty( $option_ids ) ) {
			return $result;
		}

		foreach ( $option_ids as $option_id ) {
			$status = get_post_status( $option_id );

			if ( 'publish' === $status ) {
				$blocks_content = $this->get_addon_blocks_content( $option_id );

				if ( ! empty( $blocks_content ) ) {
					$result['blocks'][ $option_id ] = $blocks_content;
					$result['published_ids'][]      = $option_id;
				}
			} elseif ( ! $status ) {
				// Clean up deleted options.
				do_action( 'prad_delete_option_product_meta', $option_id );
			}
		}

		// Cache the result.
		$this->blocks_cache[ $cache_key ] = $result;

		return $result;
	}

	/**
	 * Get addon blocks content
	 *
	 * @param int $addon_id Addon post ID to retrieve blocks content for.
	 * @return array
	 */
	private function get_addon_blocks_content( int $addon_id ): array {
		$content = get_post_meta( $addon_id, 'prad_addons_blocks', true );

		if ( empty( $content ) ) {
			return array();
		}

		// Ensure proper JSON encoding/decoding.
		if ( is_string( $content ) ) {
			$content = json_decode( $content, true );
		}

		if ( ! is_array( $content ) ) {
			return array();
		}

		return apply_filters( 'prad_addon_blocks_content', $content, $addon_id );
	}

	/**
	 * Maybe render addon CSS
	 *
	 * @param int $addon_id Addon post ID to render CSS for.
	 */
	private function maybe_render_addon_css( int $addon_id ): void {
		$print_styles = wp_doing_ajax() || wp_is_serving_rest_request() ? 'print' : '';

		if ( function_exists( 'product_addons' ) ) {
			product_addons()->render_addon_css( $addon_id, $print_styles );
		}

		do_action( 'prad_render_addon_css', $addon_id, $print_styles );
	}

	/**
	 * Get product price data
	 *
	 * @param \WC_Product $product Product object to retrieve price data for.
	 * @return array
	 */
	public function get_product_price_data( \WC_Product $product ): array {
		$product_id = $product->get_id();

		// Check cache.
		if ( isset( $this->price_cache[ $product_id ] ) ) {
			return $this->price_cache[ $product_id ];
		}

		$price_data = array(
			'base_price'            => $this->get_product_base_price( $product ),
			'base_price_percentage' => $this->get_product_base_price_percentage( $product ),
			'variations'            => array(),
			'variations_percentage' => array(),
		);

		// Get variation prices for variable products.
		if ( $product->is_type( 'variable' ) ) {
			$variation_ids = $product->get_children();

			foreach ( $variation_ids as $variation_id ) {
				$price_data['variations'][ $variation_id ] = apply_filters(
					'prad_single_product_page_price',
					$variation_id
				);

				$price_data['variations_percentage'][ $variation_id ] = apply_filters(
					'prad_percentage_based_price_raw',
					$variation_id,
					'converts'
				);
			}
		}

		// Cache the result.
		$this->price_cache[ $product_id ] = $price_data;

		return $price_data;
	}

	/**
	 * Get product base price
	 *
	 * @param \WC_Product $product Product object to retrieve base price for.
	 * @return float
	 */
	public function get_product_base_price( \WC_Product $product ): float {
		$price = apply_filters(
			'prad_single_product_page_price',
			$product->get_id()
		);

		return (float) $price;
	}

	/**
	 * Get product base price percentage
	 *
	 * @param \WC_Product $product Product object to retrieve base price percentage for.
	 * @return float
	 */
	private function get_product_base_price_percentage( \WC_Product $product ): float {
		$price = apply_filters(
			'prad_percentage_based_price_raw',
			$product->get_id(),
			'converts'
		);

		return (float) $price;
	}
	/**
	 * Get and decode JSON option
	 *
	 * @param string $option_name Name of the option to retrieve.
	 * @param array  $def Default value to return if option is not found or invalid.
	 * @return array Decoded option value as array.
	 */
	private function get_json_option( string $option_name, array $def = array() ): array {
		$value   = get_option( $option_name, '[]' );
		$decoded = json_decode( product_addons()->safe_stripslashes( $value ), true );

		return is_array( $decoded ) ? $decoded : $def;
	}

	/**
	 * Get and decode JSON meta
	 *
	 * @param int    $post_id Product ID to retrieve meta for.
	 * @param string $meta_key Meta key to retrieve.
	 * @param array  $def Default value to return if meta is not found or invalid.
	 * @return array Decoded meta value as array.
	 */
	private function get_json_meta( int $post_id, string $meta_key, array $def = array() ): array {
		$value = get_post_meta( $post_id, $meta_key, true );

		if ( empty( $value ) ) {
			return $def;
		}

		$decoded = json_decode( product_addons()->safe_stripslashes( $value ), true );

		return is_array( $decoded ) ? $decoded : $def;
	}

	/**
	 * Get and decode JSON term meta
	 *
	 * @param int    $term_id   Term ID to retrieve meta for.
	 * @param string $meta_key  Meta key to retrieve.
	 * @param array  $def   Default value to return if meta is not found or invalid.
	 * @return array
	 */
	private function get_json_term_meta( int $term_id, string $meta_key, array $def = array() ): array {
		$value = get_term_meta( $term_id, $meta_key, true );

		if ( empty( $value ) ) {
			return $def;
		}

		$decoded = json_decode( product_addons()->safe_stripslashes( $value ), true );

		return is_array( $decoded ) ? $decoded : $def;
	}
}
