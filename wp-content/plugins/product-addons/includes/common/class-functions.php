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
	 * Checks if the Product Addons Pro plugin is active and its license is valid.
	 *
	 * Verifies whether the plugin located at 'product-addons-pro/product-addons-pro.php'
	 * is currently active. If active, it then checks the license status stored in the
	 * 'edd_prad_license_data' option to ensure it is marked as 'valid'.
	 *
	 * @return bool True if the plugin is active and the license is valid, false otherwise.
	 */
	public function is_lc_active() {
		if ( defined( 'PRAD_PRO_VER' ) ) {
			$license_data = get_option( 'edd_prad_license_data', array() );
			return isset( $license_data['license'] ) && 'valid' === $license_data['license'] ? true : false;
		}
		return false;
	}

	/**
	 * Checks if the license has expired.
	 *
	 * This method checks the stored license data in the WordPress options table
	 * and determines if the license status is set to 'expired'. It returns `true`
	 * if the license is expired, otherwise `false`.
	 *
	 * @return bool True if the license is expired, otherwise false.
	 */
	public function is_lc_expired() {
		$license_data = get_option( 'edd_prad_license_data', array() );
		return isset( $license_data['license'] ) && 'expired' === $license_data['license'] ? true : false;
	}

	/**
	 * Checks if the license has expired.
	 *
	 * This method checks the stored license data in the WordPress options table
	 * and determines if the license status is set to 'expired'. It returns `true`
	 * if the license is expired, otherwise `false`.
	 *
	 * @return bool True if the license is expired, otherwise false.
	 */
	public function handle_all_pro_block() {
		if ( defined( 'PRAD_PRO_VER' ) ) {
			$license_data = get_option( 'edd_prad_license_data', array() );
			return isset( $license_data['license'] ) && ( 'valid' === $license_data['license'] || 'expired' === $license_data['license'] ) ? true : false;
		}
		return false;
	}

	/**
	 * Checks if the pro feature is available based on plugin activation and license status.
	 *
	 * Returns true if the Product Addons Pro plugin is active and the license is either valid or expired.
	 *
	 * @return bool True if pro features are available, false otherwise.
	 */
	public function is_pro_feature_available() {
		if ( defined( 'PRAD_PRO_VER' ) ) {
			$license_data = get_option( 'edd_prad_license_data', array() );
			return isset( $license_data['license'] ) && ( 'valid' === $license_data['license'] || 'expired' === $license_data['license'] ) ? true : false;
		}
		return false;
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
		if ( empty( $assigned_data ) ) {
			return (object) array(
				'aType'             => 'specific_product',
				'includes'          => array(),
				'excludes'          => array(),
				'excludeCategories' => array(),
			);
		} else {
			if ( isset( $assigned_data['includes'] ) && count( $assigned_data['includes'] ) > 0 ) {
				if ( 'specific_product' === $assigned_data['aType'] ) {
					$includes = $this->get_searched_products( '', false, '', $assigned_data['includes'] );
				} elseif ( 'specific_category' === $assigned_data['aType'] || 'specific_tag' === $assigned_data['aType'] || 'specific_brand' === $assigned_data['aType'] ) {
					$term_type = 'specific_category' === $assigned_data['aType'] ? 'cat' : ( 'specific_tag' === $assigned_data['aType'] ? 'tag' : 'brand' );
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

			return (object) array(
				'aType'             => $assigned_data['aType'],
				'includes'          => $includes,
				'excludes'          => isset( $assigned_data['excludes'] ) && count( $assigned_data['excludes'] ) > 0
					? $this->get_searched_products( '', false, '', $assigned_data['excludes'] )
					: array(),
				'excludeCategories' => isset( $assigned_data['excludeCategories'] ) && count( $assigned_data['excludeCategories'] ) > 0
					? $this->get_searched_categories(
						array(
							'term'         => '',
							'limit'        => '',
							'includes'     => $assigned_data['excludeCategories'],
							'trigger_type' => 'cat',
						)
					)
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
	 *
	 * @return array An array of product details, including item ID, URL, name, and thumbnail URL.
	 */
	public function get_searched_products( $term, $include_variations = false, $limit = '', $include_ids = array() ) {
		// Load the product data store.
		$data_store = WC_Data_Store::load( 'product' );

		$exclude_ids = array();
		$ids         = $data_store->search_products( $term, '', (bool) $include_variations, false, $limit, $include_ids, $exclude_ids );
		$products    = array();

		foreach ( $ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( $product ) {
				$products[] = array(
					'item_id'   => $product_id,
					'url'       => get_permalink( $product_id ),
					'item_name' => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
					'thumbnail' => wp_get_attachment_url( $product->get_image_id() ),
				);
			}
		}

		return $products;
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

		$found_categories = array();
		$args             = array(
			'taxonomy'   => array( 'product_' . $trigger_type ),
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
			wp_register_style( 'prad-addons-css-'. $id, false ); // phpcs:ignore
			wp_enqueue_style( 'prad-addons-css-' . $id );
			wp_add_inline_style( 'prad-addons-css-' . $id, $prad_addons_css );
		}
		if ( 'print' === $type ) {
			echo '<style id="prad-addons-css-' . esc_attr( $id ) . '-inline">' . esc_html( $prad_addons_css ) . '</style>';
		}
	}

	/**
	 * Sanitize Params
	 *
	 * Recursively sanitizes the given parameters to ensure safe data handling.
	 *
	 * @param mixed $params The data to be sanitized. Can be an array, boolean, object, or string.
	 *
	 * @return mixed The sanitized data.
	 */
	public function sanitize_rest_params( $params ) {
		if ( is_array( $params ) ) {
			return array_map( array( $this, 'sanitize_rest_params' ), $params );
		} elseif ( is_bool( $params ) ) {
			return rest_sanitize_boolean( $params );
		} elseif ( is_object( $params ) ) {
			return $params;
		} elseif ( is_string( $params ) ) {
			return $params;
		} else {
			return $params;
		}
	}

	/**
	 * Returns a structured price object with numeric and formatted HTML price.
	 *
	 * If a sale price is provided, the returned price is the sale price, and the
	 * HTML includes both the regular and sale prices. Otherwise, it returns only
	 * the regular price.
	 *
	 * @since 1.0.3
	 *
	 * @param float|string $regular The regular price.
	 * @param float|string $sale    The sale price. If empty or false, regular price is used.
	 *
	 * @return array {
	 *     @type float  $price The numeric value of the applicable price.
	 *     @type string $html  The formatted HTML price string.
	 * }
	 */
	public function get_price_object( $regular, $sale ) {
		return array(
			'price' => $sale ? floatval( $sale ) : floatval( $regular ),
			'html'  => $sale ? '<span class="pricex"><del>' . wc_price( $regular ) . '</del> <ins>' . wc_price( $sale ) . '</ins></span>' : '<span class="pricex">' . wc_price( $regular ) . '</span>',
		);
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
	 * Revert the given price to the base price.
	 *
	 * Price is returned.
	 *
	 * @since 1.0.4
	 *
	 * @param float $price The original price to be reverted.
	 *
	 * @return float The reverted price.
	 */
	public function get_currency_reverted_price( $price ) {
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
				return $price / $total_rate;
			}
		}

		// WooCommerce Currency Switcher by WPExperts.
		if ( defined( 'WCCS_VERSION' ) ) {
			// Dont have any filter to revert the price.
			$price = $this->manual_currency_reverted_price( $price );
			return $price;
		}

		if ( function_exists( 'wmc_revert_price' ) ) {

			if ( defined( 'WOOMULTI_CURRENCY_VERSION' ) && class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
				$curcy = \WOOMULTI_CURRENCY_Data::get_ins();
			} elseif ( defined( 'WOOMULTI_CURRENCY_F_VERSION' ) && class_exists( 'WOOMULTI_CURRENCY_F_Data' ) ) {
				$curcy = \WOOMULTI_CURRENCY_F_Data::get_ins();
			}

			if ( isset( $curcy ) && $curcy->get_enable() ) {
				$price = wmc_revert_price( $price );
				return $price;
			}
		}

		// Yay_Currency Switcher.
		if ( defined( 'YAY_CURRENCY_VERSION' ) ) {
			$price = apply_filters( 'yay_currency_revert_price', $price, '' );// phpcs:ignore
			return $price;
		}

		// FOX - Currency Switcher.
		if ( defined( 'WOOCS_VERSION' ) ) {
			$price = apply_filters( 'woocs_back_convert_price', $price, '' );// phpcs:ignore
			return $price;
		}

		// Currency Switcher for WooCommerce by wpwham.
		if ( function_exists( 'alg_get_current_currency_code' ) && function_exists( 'alg_convert_price' ) ) {
			// Dont have any filter to revert the price.
			$price = $this->manual_currency_reverted_price( $price );
			return $price;
		}

		// Yith Currency Switcher.
		if ( function_exists( 'yith_wcmcs_convert_price' ) ) {
			// Dont have any filter to revert the price.
			$price = $this->manual_currency_reverted_price( $price );
			return $price;
		}

		// Aelia Currency Switcher.
		if ( class_exists( 'WC_Aelia_CurrencySwitcher' ) ) {
			$base_currency   = apply_filters( 'wc_aelia_cs_base_currency', '' );// phpcs:ignore
			$active_currency = get_woocommerce_currency();
			$price           = apply_filters( 'wc_aelia_cs_convert', $price, $active_currency, $base_currency );// phpcs:ignore
			return $price;
		}

		if ( class_exists( '\WCPay\MultiCurrency\MultiCurrency' ) ) {
			$multi_currency  = null;
			$converted_price = $price;

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
				$currency = $multi_currency->get_selected_currency();
				$rate     = $currency->get_rate();
				if ( $currency->get_is_default() || $rate <= 0 ) {
					return $converted_price;
				}

				// Reverse conversion.
				$base_price = (float) $converted_price / $rate;

				return (float) $base_price;

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

			if ( floatval( $cr_rate ) == floatval( 0 ) ) {
				return 0;
			}

			return ( floatval( $price ) - floatval( $cr_extra ) ) / floatval( $cr_rate );
		}

		return floatval( $price );
	}

	/**
	 * Sanitizes the given content using wp_kses with allowed HTML tags.
	 *
	 * This method applies the 'prad_allowed_html_tags' filter to determine
	 * which HTML tags are permitted, and then sanitizes the $prad_blocks content
	 * accordingly using wp_kses.
	 *
	 * @since 1.0.5
	 *
	 * @param mixed $prad_blocks The content to be sanitized.
	 * @return mixed The sanitized content with only allowed HTML tags.
	 */
	public function get_wp_kses_content( $prad_blocks ) {
		return $prad_blocks;
	}

	/**
	 * Get Option Value bypassing cache
	 * Inspired By WordPress Core get_option
	 *
	 * @since v.1.0.7
	 * @param string  $option Option Name.
	 * @param boolean $default_value option default value.
	 * @return mixed
	 */
	public function get_option_without_cache( $option, $default_value = false ) {
		global $wpdb;

		if ( is_scalar( $option ) ) {
			$option = trim( $option );
		}

		if ( empty( $option ) ) {
			return false;
		}

		$value = $default_value;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s LIMIT 1", $option ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		if ( is_object( $row ) ) {
			$value = $row->option_value;
		} else {
			return apply_filters( "prad_default_option_{$option}", $default_value, $option );
		}

		return apply_filters( "prad_option_{$option}", maybe_unserialize( $value ), $option );
	}

	/**
	 * Add option without adding to the cache
	 * Inspired By WordPress Core set_transient
	 *
	 * @since v.1.0.7
	 * @param string $option option name.
	 * @param string $value option value.
	 * @param string $autoload whether to load WordPress startup.
	 * @return bool
	 */
	public function add_option_without_cache( $option, $value = '', $autoload = 'yes' ) {
		global $wpdb;

		if ( is_scalar( $option ) ) {
			$option = trim( $option );
		}

		if ( empty( $option ) ) {
			return false;
		}

		wp_protect_special_option( $option );

		if ( is_object( $value ) ) {
			$value = clone $value;
		}

		$value = sanitize_option( $option, $value );

		/*
		 * Make sure the option doesn't already exist.
		 */

		if ( apply_filters( "prad_default_option_{$option}", false, $option, false ) !== $this->get_option_without_cache( $option ) ) {
			return false;
		}

		$serialized_value = maybe_serialize( $value );
		$autoload         = ( 'no' === $autoload || false === $autoload ) ? 'no' : 'yes';

		$result = $wpdb->query( $wpdb->prepare( "INSERT INTO `$wpdb->options` (`option_name`, `option_value`, `autoload`) VALUES (%s, %s, %s) ON DUPLICATE KEY UPDATE `option_name` = VALUES(`option_name`), `option_value` = VALUES(`option_value`), `autoload` = VALUES(`autoload`)", $option, $serialized_value, $autoload ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! $result ) {
			return false;
		}

		return true;
	}

	/**
	 * Get Transient Value bypassing cache
	 * Inspired By WordPress Core get_transient
	 *
	 * @since v.1.0.7
	 * @param string $transient Transient Name.
	 * @return mixed
	 */
	public function get_transient_without_cache( $transient ) {
		$transient_option  = '_transient_' . $transient;
		$transient_timeout = '_transient_timeout_' . $transient;
		$timeout           = $this->get_option_without_cache( $transient_timeout );

		if ( false !== $timeout && $timeout < time() ) {
			delete_option( $transient_option );
			delete_option( $transient_timeout );
			$value = false;
		}

		if ( ! isset( $value ) ) {
			$value = $this->get_option_without_cache( $transient_option );
		}

		return apply_filters( "prad_transient_{$transient}", $value, $transient );
	}

	/**
	 * Set transient without adding to the cache
	 * Inspired By WordPress Core set_transient
	 *
	 * @since v.1.0.7
	 * @param string  $transient Transient Name.
	 * @param mixed   $value Transient Value.
	 * @param integer $expiration Time until expiration in seconds.
	 * @return bool
	 */
	public function set_transient_without_cache( $transient, $value, $expiration = 0 ) {
		$expiration = (int) $expiration;

		$transient_timeout = '_transient_timeout_' . $transient;
		$transient_option  = '_transient_' . $transient;

		$result = false;

		if ( false === $this->get_option_without_cache( $transient_option ) ) {
			$autoload = 'yes';
			if ( $expiration ) {
				$autoload = 'no';
				$this->add_option_without_cache( $transient_timeout, time() + $expiration, 'no' );
			}
			$result = $this->add_option_without_cache( $transient_option, $value, $autoload );
		} else {
			/*
			 * If expiration is requested, but the transient has no timeout option,
			 * delete, then re-create transient rather than update.
			 */
			$update = true;

			if ( $expiration ) {
				if ( false === $this->get_option_without_cache( $transient_timeout ) ) {
					delete_option( $transient_option );
					$this->add_option_without_cache( $transient_timeout, time() + $expiration, 'no' );
					$result = $this->add_option_without_cache( $transient_option, $value, 'no' );
					$update = false;
				} else {
					update_option( $transient_timeout, time() + $expiration );
				}
			}

			if ( $update ) {
				$result = update_option( $transient_option, $value );
			}
		}

		return $result;
	}

	/**
	 * Generates the HTML for the product block variation section.
	 *
	 * This function outputs the variation selection UI for a given product block item.
	 *
	 * @param array $args Arguments containing the product item.
	 * @return string The generated HTML for the variation section.
	 */
	public function generate_products_block_variation_section_html( $args ) {
		$item              = $args['item'];
		$allowed_html_tags = apply_filters( 'prad_allowed_html_tags', array() );
		ob_start();
		if ( isset( $item->variation ) && $item->variation ) {
			$select_options       = '';
			$product              = wc_get_product( $item->id );
			$available_variations = $product->get_available_variations();
			foreach ( $available_variations as $variation_data ) {
				$variation_id = $variation_data['variation_id'];
				$variation    = wc_get_product( $variation_id );

				if ( $variation && $variation->is_purchasable() && $variation->is_in_stock() ) {
					$variation_attributes = $variation->get_attributes();
					$option_label         = '';
					$valid_variation      = true;
					$i                    = 0;
					$regular_price        = $variation->get_regular_price( '' );
					$sale_price           = $variation->get_sale_price( '' );

					$regular_price = apply_filters(
						'prad_raw_tax_compitable_price',
						array(
							'product_id' => $variation_id,
							'price'      => $variation->get_regular_price(),
							'source'     => 'product_page',
						)
					);
					$sale_price    = apply_filters(
						'prad_raw_tax_compitable_price',
						array(
							'product_id' => $variation_id,
							'price'      => $variation->get_sale_price(),
							'source'     => 'product_page',
						)
					);
					$price_obj     = product_addons()->get_price_object( $regular_price, $sale_price );
					foreach ( $variation_attributes as $key => $value ) {
						$label = str_replace( '_', ' ', str_replace( 'pa_', '', $key ) );
						if ( ! empty( $value ) ) {
							$option_label .= ( $i > 0 ? ' , ' : '' ) . ucfirst( $label ) . ' - ' . ucfirst( $value );
							++$i;
						} else {
							$valid_variation = false;
						}
					}
					if ( $valid_variation ) {
						$option_label    = rawurldecode( wp_strip_all_tags( $option_label ) );
						$select_options .= '<div class="prad-select-option" title="' . esc_attr( $option_label ) . '" value="' . esc_attr( $price_obj['price'] ) . '" data-variation-id="' . esc_attr( $variation_id ) . '"  data-pricehtml="' . esc_attr( $price_obj['html'] ) . '">' . esc_html( $option_label ) . '</div>';
					}
				}
			}

			if ( $select_options ) {
				?>
				<div class="prad-product-block-variation-select prad-mt-10">
					<div class="prad-custom-select prad-w-full prad-product-variation-select-comp">
						<div class="prad-select-box prad-block-input prad-block-content" readonly="readonly"><div style="max-width: 120px" class="prad-select-box-item prad-mr-12 prad-ellipsis"><?php esc_html_e( 'Select an option', 'product-addons' ); ?></div> <div class="prad-icon"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="8" fill="none"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="m1 1 6 6 6-6"></path></svg></div></div>
						<div class="prad-select-options">
							<?php echo wp_kses( $select_options, $allowed_html_tags ); ?>
						</div>
					</div>
				</div>
				
				<?php
			}
		}
		return ob_get_clean();
	}

	/**
	 * Retrieves product block attributes for a given product ID.
	 *
	 * Returns an object containing product details such as ID, type, variation status,
	 * URL, name, image, regular and sale prices, stock status, and purchasable status.
	 *
	 * @param int  $p_id   Product ID.
	 * @param bool $var_p  Whether the product is a variation.
	 * @return object|null Product attributes object or null if not found.
	 */
	public function get_product_block_product_attr( $p_id, $var_p = false ) {
		$product = wc_get_product( $p_id );
		if ( $product ) {
			$data = array(
				'id'             => $p_id,
				'type'           => 'per_unit',
				'variation'      => $var_p,
				'url'            => get_permalink( $p_id ),
				'value'          => rawurldecode( wp_strip_all_tags( $product->get_name() ) ),
				'img'            => wp_get_attachment_url( $product->get_image_id() ),
				'regular'        => apply_filters(
					'prad_raw_tax_compitable_price',
					array(
						'product_id' => $p_id,
						'ppType'     => 'reg',
						'price'      => $product->get_regular_price(),
						'source'     => 'product_page',
					)
				),
				'sale'           => apply_filters(
					'prad_raw_tax_compitable_price',
					array(
						'product_id' => $p_id,
						'ppType'     => 'sale',
						'price'      => $product->get_sale_price(),
						'source'     => 'product_page',
					)
				),
				'is_in_stock'    => $product->is_in_stock(),
				'is_purchasable' => $product->is_purchasable(),
			);
			return (object) $data;
		}

		return null;
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
	 * Get WOW Products Details
	 *
	 * @return array
	 */
	public function get_wow_products_details() {
		return array(
			'products'        => array(
				'wow_shipping' => file_exists( WP_PLUGIN_DIR . '/wow-table-rate-shipping/wow-table-rate-shipping.php' ),
				'post_x'       => file_exists( WP_PLUGIN_DIR . '/ultimate-post/ultimate-post.php' ),
				'wow_store'    => file_exists( WP_PLUGIN_DIR . '/product-blocks/product-blocks.php' ),
				'wow_optin'    => file_exists( WP_PLUGIN_DIR . '/optin/optin.php' ),
				'wow_revenue'  => file_exists( WP_PLUGIN_DIR . '/revenue/revenue.php' ),
				'wholesale_x'  => file_exists( WP_PLUGIN_DIR . '/wholesalex/wholesalex.php' ),
			),
			'products_active' => array(
				'wow_shipping' => defined( 'WTRS_VER' ),
				'post_x'       => defined( 'ULTP_VER' ),
				'wow_store'    => defined( 'WOPB_VER' ),
				'wow_optin'    => defined( 'OPTN_VERSION' ),
				'wow_revenue'  => defined( 'REVENUE_VER' ),
				'wholesale_x'  => defined( 'WHOLESALEX_VER' ),
			),
		);
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

		// ✅ Make sure destination folder exists
		if ( ! file_exists( $to_dir ) ) {
			wp_mkdir_p( $to_dir );
		}

		// ✅ Make sure temp folder exists
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
	 * Retrieves the list of allowed file types for uploads.
	 *
	 * This function returns an array of file types that are permitted to be uploaded
	 * through the product addons functionality.
	 *
	 * @return array List of allowed file types for uploads.
	 */
	public function prad_get_upload_allowed_file_types() {

		$allowed_types = array(
			'png'  => 'image/png',
			'jpg'  => 'image/jpeg',
			'jpeg' => 'image/jpeg',
			'pdf'  => 'application/pdf',
			'csv'  => 'text/csv',
			'doc'  => 'application/msword',
			'txt'  => 'text/plain',
			'ppt'  => 'application/vnd.ms-powerpoint',
			'heic' => 'image/heic',
			'svg'  => 'image/svg+xml',
			'ai'   => 'application/pdf',
			'psd'  => 'image/vnd.adobe.photoshop',
			'eps'  => 'application/postscript',
			'cdr'  => 'application/vnd.corel-draw',
			'gpx'  => 'text/xml',
		);

		return apply_filters( 'prad_upload_field_allowed_file_types', $allowed_types );
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

		// Get options from product terms (categories, tags, brands).
		$option_terms_inc = $this->get_product_term_options( $product_id, 'prad_term_assigned_meta_inc' );
		$option_terms_exc = $this->get_product_term_options( $product_id, 'prad_term_assigned_meta_exc' );

		// Merge and filter.
		$merged     = array_unique( array_merge( $option_all, $option_terms_inc, $option_product ) );
		$option_ids = array_diff( $merged, $option_exclude, $option_terms_exc );

		// Sort for consistency.
		sort( $option_ids );

		return apply_filters( 'prad_product_option_ids', $option_ids, $product_id );
	}

	/**
	 * Retrieves product options from terms (categories, tags, brands).
	 *
	 * @param int    $product_id The product ID.
	 * @param string $meta_key   The meta key to retrieve ('prad_term_assigned_meta_inc' or 'prad_term_assigned_meta_exc').
	 *
	 * @return array An array of option IDs from product terms.
	 */
	private function get_product_term_options( int $product_id, string $meta_key ) {
		$option_terms = array();
		if ( 'prad_term_assigned_meta_exc' === $meta_key && ! $this->is_lc_active() ) {
			return $option_terms;
		}

		$taxonomies = array( 'product_cat', 'product_tag', 'product_brand' );
		if ( 'prad_term_assigned_meta_exc' === $meta_key ) {
			$taxonomies = array( 'product_cat' );
		}

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $product_id, $taxonomy );
			if ( $terms && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {
					$term_options = $this->get_json_term_meta( $term->term_id, $meta_key, array() );

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
