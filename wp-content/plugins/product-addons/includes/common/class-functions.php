<?php //phpcs:ignore
namespace PRAD\Includes\Common;

use PRAD\Includes\Compatibility\BaseCurrency;
use WC_Data_Store;

defined( 'ABSPATH' ) || exit;

/**
 * Functions class.
 */
class Functions {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Load style path
	 *
	 * @param string $style_name style name.
	 * @return string
	 */
	public function get_style_path( $style_name ) {
		return PRAD_URL . 'assets/css/' . $style_name . ( is_rtl() ? '-rtl' : '' ) . '.css';
	}

	/**
	 * Absolute file path of a bundled stylesheet: the same file get_style_path() gives the URL of.
	 *
	 * @param string $style_name style name.
	 * @return string
	 */
	public function get_style_file_path( $style_name ) {
		return PRAD_PATH . 'assets/css/' . $style_name . ( is_rtl() ? '-rtl' : '' ) . '.css';
	}

	/**
	 * Enqueue style
	 *
	 * @param string $handle handle.
	 * @param string $style_name style name.
	 * @param array  $deps dependencies.
	 * @return void
	 */
	public function enqueue_style( $handle, $style_name, $deps = array() ) {
		wp_enqueue_style(
			$handle,
			$this->get_style_path( $style_name ),
			$deps,
			PRAD_VER
		);
	}

	/**
	 * Enqueue style
	 *
	 * @param string $handle handle.
	 * @param string $script_name script name.
	 * @param array  $args args.
	 * @return void
	 */
	public function enqueue_script( $handle, $script_name, $args = true ) {
		$script_path = PRAD_URL . 'assets/js/' . $script_name . '.js';
		$assets      = $this->get_script_asset( $script_path );
		wp_enqueue_script( $handle, $script_path, $assets['dependencies'], $assets['version'], $args );
	}

	/**
	 * Read a wp-scripts generated asset file for a built script.
	 *
	 * WP Scripts (DependencyExtractionWebpackPlugin) generates a sibling
	 * `{entry}.asset.php` file containing `dependencies` and `version`.
	 *
	 * @since 1.5.8
	 *
	 * @param string $relative_js_path Relative JS path inside the plugin, e.g. 'assets/js/frontend-script.js'.
	 * @param array  $fallback_deps    Dependencies to use if the asset file does not exist.
	 *
	 * @return array { dependencies: string[], version: string }
	 */
	public function get_script_asset( $relative_js_path, $fallback_deps = array() ) {
		$relative_js_path = ltrim( (string) $relative_js_path, '/\\' );
		$asset_path       = preg_replace( '/\\.js$/', '.asset.php', $relative_js_path );
		$asset_file       = trailingslashit( PRAD_PATH ) . str_replace( array( '\\', '//' ), '/', $asset_path );

		$asset = null;
		if ( $asset_file && file_exists( $asset_file ) ) {
			$asset = include $asset_file;
		}

		if ( is_array( $asset ) && isset( $asset['dependencies'], $asset['version'] ) ) {
			return array(
				'dependencies' => is_array( $asset['dependencies'] ) ? $asset['dependencies'] : array(),
				'version'      => (string) $asset['version'],
			);
		}

		return array(
			'dependencies' => is_array( $fallback_deps ) ? $fallback_deps : array(),
			'version'      => defined( 'PRAD_VER' ) ? PRAD_VER : false,
		);
	}

	/**
	 * Get the taxonomies an option can be assigned to, keyed by assignment type.
	 *
	 * This plugin supports product categories. Other plugins can add more through
	 * the prad_assign_taxonomies filter.
	 *
	 * @since 1.8.2
	 *
	 * @return array Assignment type => taxonomy, e.g. 'specific_category' => 'product_cat'.
	 */
	public function get_assign_taxonomies(): array {
		$default    = array( 'specific_category' => 'product_cat' );
		$taxonomies = apply_filters( 'prad_assign_taxonomies', $default );

		return is_array( $taxonomies ) ? $taxonomies : $default;
	}

	/**
	 * Get the assignable taxonomies keyed by the trigger type the search endpoints use.
	 *
	 * @since 1.8.2
	 *
	 * @return array Trigger type => taxonomy, e.g. 'cat' => 'product_cat'.
	 */
	public function get_assign_trigger_taxonomies(): array {
		$triggers = array();

		foreach ( $this->get_assign_taxonomies() as $assign_type => $taxonomy ) {
			$trigger              = 'specific_category' === $assign_type ? 'cat' : preg_replace( '/^specific_/', '', $assign_type );
			$triggers[ $trigger ] = $taxonomy;
		}

		return $triggers;
	}

	/**
	 * Retrieves the assigned product data for a given option.
	 *
	 * @since 1.0.0
	 *
	 * @param int $option_id The ID of the option to retrieve assigned data for.
	 *
	 * @return object An object containing the assigned data, including 'aType', 'includes', and 'excludes'.
	 */
	public function get_assigned_product_data( $option_id ) {
		$assigned_data = json_decode( product_addons()->safe_stripslashes( get_post_meta( $option_id, 'prad_base_assigned_data', true ) ), true );
		$assigned_data = is_array( $assigned_data ) ? $assigned_data : array();

		/**
		 * Filters the assignment data of an option set sent to the builder, so extensions can
		 * add their own assignment settings.
		 *
		 * @since 1.8.3
		 *
		 * @param array $data          'aType', 'includes' and 'excludes', with the includes and
		 *                             excludes resolved to the items the builder shows.
		 * @param array $assigned_data The saved assignment data.
		 * @param int   $option_id     Option set ID.
		 */
		return (object) apply_filters( 'prad_assigned_product_data', $this->resolve_assigned_product_data( $assigned_data ), $assigned_data, $option_id );
	}

	/**
	 * Resolves saved assignment data to what the builder shows.
	 *
	 * @param array $assigned_data The saved assignment data.
	 * @return array
	 */
	private function resolve_assigned_product_data( $assigned_data ) {
		if ( empty( $assigned_data ) ) {
			return array(
				'aType'    => 'specific_product',
				'includes' => array(),
				'excludes' => array(),
			);
		} else {
			if ( isset( $assigned_data['includes'] ) && count( $assigned_data['includes'] ) > 0 ) {
				if ( 'specific_product' === $assigned_data['aType'] ) {
					$includes = $this->get_searched_products( '', false, '', $assigned_data['includes'] );
				} elseif ( isset( $this->get_assign_taxonomies()[ $assigned_data['aType'] ] ) ) {
					$term_type = 'specific_category' === $assigned_data['aType'] ? 'cat' : preg_replace( '/^specific_/', '', $assigned_data['aType'] );
					$includes  = $this->get_searched_categories(
						array(
							'term'         => '',
							'limit'        => '',
							'includes'     => $assigned_data['includes'],
							'trigger_type' => $term_type,
						)
					);
				} else {
					$includes = array();
				}
			} else {
				$includes = array();
			}

			return array(
				'aType'    => $assigned_data['aType'],
				'includes' => $includes,
				'excludes' => isset( $assigned_data['excludes'] ) && count( $assigned_data['excludes'] ) > 0
					? $this->get_searched_products( '', false, '', $assigned_data['excludes'] )
					: array(),
			);
		}
	}

	/**
	 * Retrieves a list of searched products based on the provided term, including optional product variations.
	 *
	 * @since 1.0.0
	 *
	 * @param string $term               The search term.
	 * @param bool   $include_variations Whether to include product variations in the search. Default is false.
	 * @param int    $limit              The number of products to return. Defaults to all.
	 * @param array  $include_ids        Array of product IDs to include in the search.
	 * @param array  $tax_filter         Optional. Restrict results to products in the given taxonomy terms,
	 *                                   e.g. array( 'taxonomy' => 'product_cat', 'term_ids' => array( 1, 2 ) ).
	 *
	 * @return array An array of product details, including item ID, URL, name, and thumbnail URL.
	 */
	public function get_searched_products( $term, $include_variations = false, $limit = '', $include_ids = array(), $tax_filter = array() ) {
		$taxonomy = isset( $tax_filter['taxonomy'] ) ? $tax_filter['taxonomy'] : '';
		$term_ids = isset( $tax_filter['term_ids'] ) && is_array( $tax_filter['term_ids'] ) ? array_map( 'absint', $tax_filter['term_ids'] ) : array();

		if ( $taxonomy && $term_ids ) {
			$ids = $this->get_searched_product_ids_by_taxonomy( $term, $taxonomy, $term_ids, $limit );
		} else {
			// Load the product data store.
			$data_store  = WC_Data_Store::load( 'product' );
			$exclude_ids = array();
			$ids         = $data_store->search_products( $term, '', (bool) $include_variations, false, $limit, $include_ids, $exclude_ids );
		}

		$products = array();

		foreach ( $ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				$products[] = array(
					'item_id'   => $product_id,
					'url'       => get_permalink( $product_id ),
					'item_name' => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
					'thumbnail' => wp_get_attachment_url( $product->get_image_id() ),
					'regular'   => $product->get_regular_price(),
					'sale'      => $product->get_sale_price(),
				);
			}
		}

		return $products;
	}

	/**
	 * Finds product IDs matching a keyword and belonging to the given taxonomy terms.
	 *
	 * WC_Data_Store::search_products() has no taxonomy support, so a scoped
	 * product-picker search (e.g. excluding products from a chosen category)
	 * is run through WP_Query with a tax_query instead.
	 *
	 * @since 1.6.17
	 *
	 * @param string $term     The search term.
	 * @param string $taxonomy The taxonomy to filter by (product_cat, product_tag, product_brand).
	 * @param array  $term_ids Term IDs within that taxonomy to restrict results to.
	 * @param int    $limit    The number of products to return.
	 *
	 * @return array Product IDs.
	 */
	private function get_searched_product_ids_by_taxonomy( $term, $taxonomy, $term_ids, $limit = '' ) {
		$query_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'posts_per_page' => $limit ? (int) $limit : -1,
			's'              => $term,
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $term_ids,
				),
			),
		);

		$query = new \WP_Query( $query_args );

		return $query->posts;
	}

	/**
	 * Retrieves a list of searched product categories based on the provided term.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $params The search parameters.
	 *
	 * @return array An array of category details, including item ID, name, URL, and thumbnail URL.
	 */
	public function get_searched_categories( $params ) {
		$term         = isset( $params['term'] ) ? $params['term'] : '';
		$limit        = isset( $params['limit'] ) ? $params['limit'] : '';
		$includes     = isset( $params['includes'] ) && is_array( $params['includes'] ) ? $params['includes'] : array();
		$trigger_type = isset( $params['trigger_type'] ) ? $params['trigger_type'] : 'cat';

		$taxonomies = $this->get_assign_trigger_taxonomies();
		if ( ! isset( $taxonomies[ $trigger_type ] ) ) {
			return array();
		}

		$found_categories = array();
		$args             = array(
			'taxonomy'   => array( $taxonomies[ $trigger_type ] ),
			'orderby'    => 'id',
			'number'     => $limit,
			'order'      => 'ASC',
			'hide_empty' => false,
			'fields'     => 'all',
			'name__like' => $term,
			'include'    => $includes,
		);

		$categories = get_terms( $args );
		foreach ( $categories as $category ) {
			if ( ! is_wp_error( $category ) ) {
				$found_categories[] = array(
					'item_id'   => $category->term_id,
					'item_name' => $category->name,
					'url'       => get_term_link( $category ),
					'thumbnail' => get_term_meta( $category->term_id, 'thumbnail_id', true )
						? wp_get_attachment_url( get_term_meta( $category->term_id, 'thumbnail_id', true ) )
						: wc_placeholder_img_src(),
				);
			}
		}

		return $found_categories;
	}

	/**
	 * Renders and enqueues inline CSS for a specific addon post.
	 *
	 * Retrieves the custom CSS stored in the post meta with key 'prad_addons_css'
	 * for the given post ID. If CSS is found, it registers a dummy style handle,
	 * enqueues it, and adds the CSS inline using `wp_add_inline_style`.
	 *
	 * @param int   $id   The post ID from which to retrieve and render the addon CSS.
	 * @param mixed $type The context in which to render the CSS (e.g., 'print' for printing).
	 */
	public function render_addon_css( $id, $type = '' ) {
		$prad_addons_css = get_post_meta( $id, 'prad_addons_css', true );
		if ( $prad_addons_css ) {
			$handle = 'prad-addons-css-' . $id;
			wp_register_style( $handle, false ); // phpcs:ignore
			wp_enqueue_style( $handle );
			wp_add_inline_style( $handle, wp_strip_all_tags( $prad_addons_css ) );

			// AJAX/REST responses never reach wp_footer(), so print the style straight away.
			// esc_html() is not usable here: <style> is raw text, so &gt; and &quot; would reach the CSS parser as-is.
			if ( 'print' === $type ) {
				wp_print_styles( $handle );
			}
		}
	}

	/**
	 * Sanitize Params
	 *
	 * Recursively sanitizes REST request data (add-on blocks, styles, settings,
	 * assignment data). Each string is sanitized according to its key:
	 * rich-content fields allow post HTML, image fields must be URLs, formula
	 * expressions keep their comparison operators, and every other string is
	 * sanitized as plain text with line breaks kept. Numbers, booleans and
	 * nulls are kept; any other type is dropped.
	 *
	 * @param mixed      $params The data to be sanitized.
	 * @param int|string $key    Key of the current value in its parent array.
	 *
	 * @return mixed The sanitized data.
	 */
	public function sanitize_rest_params( $params, $key = '' ) {
		if ( is_array( $params ) ) {
			$clean = array();
			foreach ( $params as $item_key => $item ) {
				// List items inherit the parent key so they get the same rule.
				$clean[ $item_key ] = $this->sanitize_rest_params( $item, is_int( $item_key ) ? $key : $item_key );
			}
			return $clean;
		}

		if ( is_string( $params ) ) {
			switch ( $key ) {
				case 'previewContent':
				case 'popupContent':
					return wp_kses_post( $params );
				case 'img':
				case 'src':
				case 'url':
					return esc_url_raw( $params );
				case 'expression':
					return $this->sanitize_formula_expression( $params );
				default:
					return sanitize_textarea_field( $params );
			}
		}

		if ( is_bool( $params ) || is_int( $params ) || is_float( $params ) || is_null( $params ) ) {
			return $params;
		}

		return null;
	}

	/**
	 * Sanitize a formula expression.
	 *
	 * Formulas use the <, <=, > and >= operators, so HTML text sanitizers would
	 * corrupt them. Instead, invalid UTF-8 and control characters are removed
	 * and a space is added after any "<" that could open an HTML tag. The formula
	 * lexer ignores whitespace, so the expression evaluates the same.
	 *
	 * @param string $expression Raw formula expression.
	 *
	 * @return string The sanitized expression.
	 */
	public function sanitize_formula_expression( $expression ) {
		$expression = wp_check_invalid_utf8( (string) $expression );
		$expression = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $expression );
		$expression = preg_replace( '/<(?=[a-zA-Z\/!?])/', '< ', $expression );

		return trim( $expression );
	}

	/**
	 * Recursively sanitize add-on selection data submitted from the product page.
	 *
	 * Keys are sanitized as text, `path` values (uploaded file URLs) as URLs and
	 * every other string as textarea text so line breaks in customer input are kept.
	 * Numbers, booleans and nulls are returned unchanged.
	 *
	 * @param mixed      $data Decoded selection data.
	 * @param int|string $key  Key of the current value in its parent array.
	 *
	 * @return mixed The sanitized data.
	 */
	public function sanitize_selection_data( $data, $key = '' ) {
		if ( is_array( $data ) ) {
			$clean = array();
			foreach ( $data as $item_key => $item ) {
				$clean_key           = is_int( $item_key ) ? $item_key : sanitize_text_field( $item_key );
				$clean[ $clean_key ] = $this->sanitize_selection_data( $item, $item_key );
			}
			return $clean;
		}

		if ( is_string( $data ) ) {
			return 'path' === $key ? esc_url_raw( $data ) : sanitize_textarea_field( $data );
		}

		if ( is_int( $data ) || is_float( $data ) || is_bool( $data ) ) {
			return $data;
		}

		return null;
	}

	/**
	 * Converts the given price to the current currency based on active currency switchers.
	 *
	 * This function checks for various currency switcher plugins (e.g., WowStore Switcher,
	 * WooCommerce Currency Switcher, CURCY, Yay_Currency, etc.) and applies the appropriate
	 * conversion logic to the provided price. If no currency switcher is active, the original
	 * price is returned.
	 *
	 * @since 1.0.4
	 *
	 * @param float $price The original price to be converted.
	 *
	 * @return float The converted price based on the active currency switcher, or the original price if no switcher is active.
	 */
	public function get_currency_converted_price( $price ) {
		$price = floatval( $price );

		// WowStore Switcher.
		if ( defined( 'WOPB_VER' ) && defined( 'WOPB_PRO_VER' ) && class_exists( 'WOPB_PRO\Currency_Switcher_Action' ) ) {
			$current_currency_code = wopb_function()->get_setting( 'wopb_current_currency' );
			$default_currency      = wopb_function()->get_setting( 'wopb_default_currency' );
			$current_currency      = \WOPB_PRO\Currency_Switcher_Action::get_currency( $current_currency_code );
			if ( ! $current_currency ) {
				$current_currency = $default_currency;
			}

			if ( $current_currency_code !== $default_currency ) {
				$wopb_current_currency_rate = floatval( ( isset( $current_currency['wopb_currency_rate'] ) && $current_currency['wopb_currency_rate'] > 0 && ! ( '' === $current_currency['wopb_currency_rate'] ) ) ? $current_currency['wopb_currency_rate'] : 1 );
				$wopb_current_exchange_fee  = floatval( ( isset( $current_currency['wopb_currency_exchange_fee'] ) && $current_currency['wopb_currency_exchange_fee'] >= 0 && ! ( '' === $current_currency['wopb_currency_exchange_fee'] ) ) ? $current_currency['wopb_currency_exchange_fee'] : 0 );
				$total_rate                 = ( $wopb_current_currency_rate + $wopb_current_exchange_fee );
				return $price * $total_rate;
			}
		}

		// WooCommerce Currency Switcher by WPExperts.
		if ( defined( 'WCCS_VERSION' ) ) {
			$price = apply_filters( 'woocommerce_product_addons_option_price_raw', $price, '' ); //phpcs:ignore
			return $price;
		}

		if ( function_exists( 'wmc_get_price' ) ) {
			if ( defined( 'WOOMULTI_CURRENCY_VERSION' ) && class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
				$curcy = \WOOMULTI_CURRENCY_Data::get_ins();
			} elseif ( defined( 'WOOMULTI_CURRENCY_F_VERSION' ) && class_exists( 'WOOMULTI_CURRENCY_F_Data' ) ) {
				$curcy = \WOOMULTI_CURRENCY_F_Data::get_ins();
			}

			if ( isset( $curcy ) && $curcy->get_enable() ) {
				$price = wmc_get_price( $price );
				return $price;
			}
		}

		// Yay_Currency Switcher.
		if ( defined( 'YAY_CURRENCY_VERSION' ) ) {
			$price = apply_filters( 'yay_currency_convert_price', $price, '' ); // phpcs:ignore
			return $price;
		}

		// FOX - Currency Switcher.
		if ( defined( 'WOOCS_VERSION' ) ) {
			$price = apply_filters( 'woocs_convert_price', $price, '' );// phpcs:ignore
			return $price;
		}

		// Currency Switcher for WooCommerce by wpwham.
		if ( function_exists( 'alg_get_current_currency_code' ) && function_exists( 'alg_convert_price' ) ) {
			$default_currency      = get_option( 'woocommerce_currency' );
			$current_currency_code = alg_get_current_currency_code();
			if ( $current_currency_code !== $default_currency ) {
				$price = alg_convert_price(
					array(
						'price'         => $price,
						'currency'      => $current_currency_code,
						'currency_from' => $default_currency,
						'format_price'  => 'no',
					)
				);
				return $price;
			}
		}

		// Yith Currency Switcher.
		if ( function_exists( 'yith_wcmcs_convert_price' ) ) {
			$price = apply_filters( 'yith_wcmcs_convert_price', $price, '' );// phpcs:ignore
			return $price;
		}

		// Aelia Currency Switcher.
		if ( class_exists( 'WC_Aelia_CurrencySwitcher' ) ) {
			$base_currency   = apply_filters( 'wc_aelia_cs_base_currency', '' );// phpcs:ignore
			$active_currency = get_woocommerce_currency();
			$price           = apply_filters( 'wc_aelia_cs_convert', $price, $base_currency, $active_currency );// phpcs:ignore
			return $price;
		}

		if ( class_exists( '\WCPay\MultiCurrency\MultiCurrency' ) ) {
			$multi_currency = null;

			if ( class_exists( 'WC_Payments' ) && method_exists( '\WC_Payments', 'get_gateway' ) ) {
				$gateway = \WC_Payments::get_gateway();

				if ( class_exists( '\WCPay\WC_Payments_Currency_Manager' ) ) {
					$currency_manager = new \WCPay\WC_Payments_Currency_Manager( $gateway );

					if ( method_exists( $currency_manager, 'get_multi_currency_instance' ) ) {
						$multi_currency = $currency_manager->get_multi_currency_instance();
					}
				}
			}

			// Convert price if instance exists.
			if ( $multi_currency instanceof \WCPay\MultiCurrency\MultiCurrency && method_exists( $multi_currency, 'get_price' ) ) {
				$price = $multi_currency->get_price( $price, 'product' );
				return $price;
			}
		}

		return $price;
	}
	/**
	 * Retrieves currency conversion data including rate and extra value.
	 *
	 * This method calculates the converted currency rate by comparing
	 * two values from `get_currency_converted_price()`. It returns an array
	 * with the active status, rate difference, and extra amount.
	 *
	 * @since 1.0.4
	 * @return array {
	 *     @type bool   $cr_active Whether currency conversion is active (rate ≠ 1).
	 *     @type float  $cr_rate   The currency conversion rate difference.
	 *     @type float  $cr_extra  The extra value included in the conversion.
	 * }
	 */
	public function get_currency_converted_data() {
		$custom_array              = array();
		$_extra                    = BaseCurrency::convert( 0 );
		$_rate                     = BaseCurrency::convert( 1 ) - $_extra;
		$custom_array['cr_active'] = floatval( 1 ) !== floatval( $_rate ) ? true : false;
		$custom_array['cr_rate']   = $_rate;
		$custom_array['cr_extra']  = $_extra;

		return $custom_array;
	}

	/**
	 * Manually revert a converted currency price to its base value.
	 *
	 * This function uses the conversion rate and extra value from get_currency_converted_data()
	 * to calculate the original price before conversion.
	 *
	 * @since 1.0.4
	 * @param float $price The converted price to revert.
	 * @return float The reverted base price.
	 */
	public function manual_currency_reverted_price( $price ) {
		$currency_data = $this->get_currency_converted_data();
		if ( $currency_data['cr_active'] ) {
			$cr_rate  = isset( $currency_data['cr_rate'] ) ? $currency_data['cr_rate'] : 1;
			$cr_extra = isset( $currency_data['cr_extra'] ) ? $currency_data['cr_extra'] : 0;

			if ( floatval( $cr_rate ) === floatval( 0 ) ) {
				return 0;
			}

			return ( floatval( $price ) - floatval( $cr_extra ) ) / floatval( $cr_rate );
		}

		return floatval( $price );
	}
	/**
	 * Generates a UTM link with specified parameters and configuration.
	 *
	 * This method constructs a URL with UTM parameters for tracking purposes,
	 * optionally including affiliate and hash values, and supports custom UTM configurations.
	 *
	 * @param array $params {
	 *     Array of parameters for generating the UTM link.
	 *     @type string $url        Base URL to append UTM parameters to.
	 *     @type string $utmKey     Key to select default UTM configuration.
	 *     @type string $affiliate  Affiliate ID to append as 'ref'.
	 *     @type string $hash       Hash fragment to append to the URL.
	 *     @type array  $config     Custom UTM configuration array.
	 * }
	 * @return string The generated URL with UTM parameters.
	 */
	public function generate_utm_link( $params ) {
		// Default UTM configurations.
		$default_config = array(
			'example'         => array(
				'source'   => 'db-wowaddons-featurename',
				'medium'   => 'block-feature',
				'campaign' => 'wowaddons-dashboard',
			),
			'summer_db'       => array(
				'source'   => 'db-wowaddons-notice',
				'medium'   => 'black-friday-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'final_hour'      => array(
				'source'   => 'db-wowaddons-text',
				'medium'   => 'final-hour-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'massive_sale'    => array(
				'source'   => 'db-wowaddons-notice-logo',
				'medium'   => 'massive-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'flash_sale'      => array(
				'source'   => 'db-wowaddons-notice-text',
				'medium'   => 'flash-sale',
				'campaign' => 'wowaddons-dashboard',
			),
			'exclusive_deals' => array(
				'source'   => 'db-wowaddons-notice-logo',
				'medium'   => 'exclusive-deals',
				'campaign' => 'wowaddons-dashboard',
			),
		);

		// Step 1: Get parameters.
		$base_url      = $params['url'] ?? 'https://www.wpxpo.com/product/wowaddons/';
		$utm_key       = $params['utmKey'] ?? null;
		$affiliate     = $params['affiliate'] ?? apply_filters( 'prad_affiliate_id', '' );
		$hash          = $params['hash'] ?? '';
		$custom_config = $params['config'] ?? null;

		$parsed_url = wp_parse_url( $base_url );
		$scheme     = $parsed_url['scheme'] ?? 'https';
		$host       = $parsed_url['host'] ?? '';
		$path       = $parsed_url['path'] ?? '';
		$query      = array();

		// Step 3: Extract existing query params if present.
		if ( isset( $parsed_url['query'] ) ) {
			parse_str( $parsed_url['query'], $query );
		}

		// Step 4: Determine config.
		$utm_config = $custom_config ?? ( $utm_key && isset( $default_config[ $utm_key ] ) ? $default_config[ $utm_key ] : array() );

		// Step 5: Add UTM parameters.
		if ( ! empty( $utm_config ) ) {
			$query = array_merge(
				$query,
				array(
					'utm_source'   => $utm_config['source'],
					'utm_medium'   => $utm_config['medium'],
					'utm_campaign' => $utm_config['campaign'],
				)
			);
		}

		// Step 6: Add affiliate if present.
		if ( $affiliate ) {
			$query['ref'] = $affiliate;
		}

		// Step 7: Reconstruct URL.
		$final_url = $scheme . '://' . $host . $path;

		if ( ! empty( $query ) ) {
			$final_url .= '?' . http_build_query( $query );
		}

		if ( $hash ) {
			$final_url .= '#' . $hash;
		}

		return $final_url;
	}

	/**
	 * Move or copy files from temp folder to on_cart folder.
	 *
	 * @param array  $src_files Absolute file paths (from temp folder).
	 * @param string $_from     Source folder name ('temp' or 'order_placed').
	 * @param bool   $delete    Whether to delete original files (true = move, false = copy).
	 *
	 * @return array List of processed files with new paths & URLs.
	 */
	public function prad_move_uploadblock_files( array $src_files, string $_from = 'temp', bool $delete = false ) {
		$upload_dir = wp_upload_dir();

		$_to = 'order_placed';
		if ( 'order_placed' === $_from ) {
			$_to = 'order_completed';
		}

		$from_dir = $upload_dir['basedir'] . '/prad_option_files/' . $_from;
		$to_dir   = $upload_dir['basedir'] . '/prad_option_files/' . $_to;

		// Make sure destination folder exists.
		if ( ! file_exists( $to_dir ) ) {
			wp_mkdir_p( $to_dir );
		}

		// Make sure temp folder exists.
		if ( ! file_exists( $from_dir ) ) {
			wp_mkdir_p( $from_dir );
		}

		$processed = array();

		foreach ( $src_files as $src ) {
			// Extract filename with extension.
			$filename = basename( $src );

			$source_path      = $from_dir . '/' . $filename;
			$destination_path = $to_dir . '/' . $filename;

			// Skip if source file doesn't exist.
			if ( ! file_exists( $source_path ) ) {
				continue;
			}

			if ( 'on_cart' !== $_to && file_exists( $destination_path ) ) {
				$file_info = pathinfo( $filename );
				$name      = $file_info['filename'];
				$ext       = isset( $file_info['extension'] ) ? '.' . $file_info['extension'] : '';
				$counter   = 1;

				while ( file_exists( $destination_path ) ) {
					$destination_path = $to_dir . '/' . $name . '-' . $counter . $ext;
					++$counter;
				}
			}

			if ( copy( $source_path, $destination_path ) ) {
				if ( $delete ) {
					wp_delete_file( $source_path );
				}

				$processed[] = array(
					'updated_src' => array(
						'prev_src'  => $upload_dir['baseurl'] . '/prad_option_files/' . $_from . '/' . basename( $source_path ),
						'curr_src'  => $upload_dir['baseurl'] . '/prad_option_files/' . $_to . '/' . basename( $destination_path ),
						'curr_name' => basename( $destination_path ),
					),
					'file'        => $destination_path,
					'url'         => $upload_dir['baseurl'] . '/prad_option_files/' . $_to . '/' . basename( $destination_path ),
				);
			}
		}

		return $processed;
	}

	/**
	 * Safe stripslashes that handles both strings and arrays.
	 *
	 * This function safely applies stripslashes to a value, whether it's a string or an array.
	 * If the value is already an array, it returns the array as-is.
	 * If the value is a string, it applies stripslashes and returns the result.
	 *
	 * @since 1.0.0
	 * @param mixed $value The value to process (string or array).
	 * @return mixed The processed value.
	 */
	public function safe_stripslashes( $value ) {
		if ( is_array( $value ) ) {
			return $value;
		}

		if ( is_string( $value ) ) {
			return stripslashes( $value );
		}

		return $value;
	}

	/**
	 * Retrieves global WooCommerce product attributes and their terms.
	 *
	 * This function fetches all global attributes defined in WooCommerce
	 * and their associated terms, returning them in a structured array.
	 *
	 * @return array An associative array of attributes and their terms.
	 */
	public function prad_get_attributes() {
		$global_attrs = wc_get_attribute_taxonomies();

		$attrs = array();
		foreach ( $global_attrs as $attribute ) {
			$attr_name = $attribute->attribute_name;
			$terms     = get_terms(
				array(
					'taxonomy'   => wc_attribute_taxonomy_name( $attr_name ),
					'hide_empty' => false,
				)
			);

			foreach ( $terms as $term ) {
				$attrs_ops[ $attr_name ][] = array(
					'value' => (int) $term->term_id,
					'slug'  => $term->slug,
					'label' => $term->name,
				);
			}

			$attrs[ 'pa_' . $attr_name ] = array(
				'label'   => $attribute->attribute_label,
				'options' => isset( $attrs_ops[ $attr_name ] ) ? $attrs_ops[ $attr_name ] : array(),
			);
		}

		return $attrs;
	}

	/**
	 * Retrieves product option IDs based on global, product-specific, and term assignments.
	 *
	 * This function merges options assigned globally, per product, and by product terms,
	 * then excludes any options marked as excluded.
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return array An array of option IDs assigned to the product.
	 */
	public function get_product_option_ids( int $product_id ): array {
		$option_all = $this->get_json_option( 'prad_option_assign_all', array() );

		// Get options assigned directly to this product.
		$option_product = $this->get_json_meta( $product_id, 'prad_product_assigned_meta_inc', array() );

		// Get options excluded from this product.
		$option_exclude = $this->get_json_meta( $product_id, 'prad_product_assigned_meta_exc', array() );

		// Get options from product terms (the assignable taxonomies).
		$option_terms_inc = $this->get_product_term_options( $product_id );
		// Options excluded through the product's terms are supplied by product-addons-pro.
		$option_terms_exc = (array) apply_filters( 'prad_term_excluded_option_ids', array(), $product_id );

		// Merge and filter.
		$merged     = array_unique( array_merge( $option_all, $option_terms_inc, $option_product ) );
		$option_ids = array_diff( $merged, $option_exclude, $option_terms_exc );

		// Sort for consistency.
		sort( $option_ids );

		return apply_filters( 'prad_product_option_ids', $option_ids, $product_id );
	}

	/**
	 * Retrieves the options assigned through product terms (the assignable taxonomies).
	 *
	 * @param int $product_id The product ID.
	 *
	 * @return array An array of option IDs from product terms.
	 */
	private function get_product_term_options( int $product_id ) {
		$option_terms = array();

		foreach ( array_values( $this->get_assign_taxonomies() ) as $taxonomy ) {
			$terms = get_the_terms( $product_id, $taxonomy );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_options = $this->get_json_term_meta( $term->term_id, 'prad_term_assigned_meta_inc', array() );

					if ( is_array( $term_options ) ) {
						$option_terms = array_unique( array_merge( $option_terms, $term_options ) );
					}
				}
			}
		}

		return $option_terms;
	}

	/**
	 * Retrieves a JSON-encoded option and decodes it.
	 *
	 * @param string $option_name The option name.
	 * @param array  $def         The default value if option is not found.
	 *
	 * @return array The decoded option value or default.
	 */
	private function get_json_option( string $option_name, array $def = array() ): array {
		$value   = get_option( $option_name, '[]' );
		$decoded = json_decode( product_addons()->safe_stripslashes( $value ), true );

		return is_array( $decoded ) ? $decoded : $def;
	}

	/**
	 * Retrieves a JSON-encoded post meta and decodes it.
	 *
	 * @param int    $post_id  The post ID.
	 * @param string $meta_key The meta key.
	 * @param array  $def      The default value if meta is not found.
	 *
	 * @return array The decoded meta value or default.
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
	 * Retrieves a JSON-encoded term meta and decodes it.
	 *
	 * @param int    $term_id  The term ID.
	 * @param string $meta_key The meta key.
	 * @param array  $def      The default value if meta is not found.
	 *
	 * @return array The decoded meta value or default.
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
