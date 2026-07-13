<?php	// phpcs:ignore
/**
 * CartPage.
 *
 * @package PRAD
 * @since v.1.0.0
 */
namespace PRAD\Includes\Order;

use PRAD\Includes\Common\Formula\Array_Expression_Engine;
use PRAD\Includes\Common\SafeMathEvaluator;
use PRAD\Includes\Compatibility\BaseCurrency;
use PRAD\Includes\Services\Product_Blocks_Service;
use PRAD\Includes\Xpo;

defined( 'ABSPATH' ) || exit;

/**
 * CartPage class.
 */
class CartPage {
	/**
	 * Product blocks service
	 *
	 * @var Product_Blocks_Service
	 */
	private Product_Blocks_Service $blocks_service;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->blocks_service = new Product_Blocks_Service();
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'save_custom_meta_to_cart' ), 10, 4 );
		add_filter( 'woocommerce_get_item_data', array( $this, 'display_custom_meta_in_cart' ), 10, 2 );
		// add_action( 'woocommerce_add_order_item_meta', array( $this, 'save_custom_meta_to_order' ), 10, 2 );.
		add_action( 'woocommerce_before_calculate_totals', array( $this, 'woocommerce_before_calculate_totals' ), 999999, 1 );
		add_action( 'woocommerce_add_to_cart', array( $this, 'prad_add_option_product_to_cart' ), 10, 6 );

		add_action( 'woocommerce_before_mini_cart', array( $this, 'prad_mini_cart_calculation' ), 1 );
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'prad_validate_before_add_to_cart' ), 10, 2 );
		add_action(
			'woocommerce_before_mini_cart_contents',
			array( $this, 'prad_before_mini_cart_contents' ),
			99999,
			3
		);
	}

	/**
	 * Recalculates the WooCommerce cart totals for AJAX requests
	 * when not on the Cart or Checkout pages.
	 *
	 * This ensures that mini cart totals are accurate after
	 * cart updates made via AJAX (e.g., quantity changes or item removal).
	 *
	 * @since 1.0.6
	 * @return void
	 */
	public function prad_mini_cart_calculation() {
		if ( is_cart() || is_checkout() || ! wp_doing_ajax() ) {
			return;
		}
		WC()->cart->calculate_totals();
	}

	public function prad_before_mini_cart_contents() {
		if ( is_cart() || is_checkout() || ! wp_doing_ajax() ) {
			return;
		}

		WC()->cart->calculate_totals();
	}

	/**
	 * Handles additional logic when a product is added to the WooCommerce cart.
	 *
	 * This function is triggered after a product is successfully added to the cart. It can be used to
	 * perform follow-up actions such as logging, adding related products, modifying session data,
	 * or handling custom cart behavior based on metadata passed during the add-to-cart process.
	 *
	 * @param string $cart_item_key   Unique key for the added cart item.
	 * @param int    $product_id      ID of the product being added to the cart.
	 * @param int    $quantity        Quantity of the product being added.
	 * @param int    $variation_id    ID of the variation (if applicable).
	 * @param array  $variation       Array of variation attributes for the product.
	 * @param array  $cart_item_data  Additional data passed during the add-to-cart process.
	 */
	public function prad_add_option_product_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data ) {
		$prad_products_selection = isset( $cart_item_data['prad_products_selection'] ) ? $cart_item_data['prad_products_selection'] : array();
		if ( is_array( $prad_products_selection ) ) {
			foreach ( $prad_products_selection as $item ) {
				$_id    = isset( $item['id'] ) ? (int) $item['id'] : '';
				$_count = isset( $item['count'] ) ? (int) $item['count'] : 1;
				if ( $_id ) {
					WC()->cart->add_to_cart( $_id, $_count );
				}
			}
		}
	}

	/**
	 * Update Cart Item Price
	 *
	 *  @param object $cart cart Object.
	 * @return void
	 */
	public function woocommerce_before_calculate_totals( $cart ) {

		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		// NOTE: Compatibilit issue with 'Tiered Pricing Table for WooCommerce' plugin. As this hook recalculating cart totals in mini cart, this condition is restricting recalculating the price.
		// if ( did_action( 'woocommerce_before_calculate_totals' ) > 1 ) {
		// 	return;
		// }

		foreach ( $cart->get_cart() as $cart_item ) {
			if ( ! empty( $cart_item['prad_selection']['price'] ) ) {

				$product       = $cart_item['data'];
				$option_price  = floatval( $cart_item['prad_selection']['price'] );
				$product_price = apply_filters(
					'prad_cart_checkout_page_price',
					! empty( $cart_item['variation_id'] ) ? $cart_item['variation_id'] : $cart_item['product_id']
				);

				$data = $this->calculate_option_price( $cart_item['prad_selection_raw'], $cart_item['product_id'], $cart_item['prad_option_published_ids'], $cart_item['variation_id'], $cart_item['quantity'] );
				if ( isset( $data['price'] ) ) {
					$option_price = floatval( $data['price'] );
				}

				// Support for Bookings and Appointments For WooCommerce Premium by PluginHive.
				if ( ! empty( $cart_item['phive_booked_price'] ) ) {
					$product_price = $cart_item['phive_booked_price'];
				}

				// Support for Fancy Product Designer plugin.
				if ( class_exists( 'Fancy_Product_Designer' ) && isset( $cart_item['fpd_data']['fpd_product_price'] ) ) {
					$product_price = $cart_item['fpd_data']['fpd_product_price'];
				}

				// Support for YayPricing Dynamic Pricing & Discounts.
				if ( function_exists( 'YAYDP\\load_plugin' ) && ! empty( $cart_item['yaydp_custom_data']['price'] ) && ! empty( $cart_item['modifiers'] ) ) {
					$product_price = $cart_item['yaydp_custom_data']['price'];
				}

				if ( function_exists( 'tpt_initFreemius' ) ) {
					$pricing_rule = \TierPricingTable\PriceManager::getPricingRule( $cart_item['data']->get_id() );
					if ( $pricing_rule ) {
						$new_price = $pricing_rule->getTierPrice( $cart_item['quantity'], false, 'cart' );
						if ( false !== $new_price ) {
							$product_price = $new_price;
						}
					}
				}

				$option_price = $option_price + floatval( $product_price );

				if ( ( function_exists( 'wcml_is_multi_currency_on' ) && wcml_is_multi_currency_on() ) || class_exists( 'WC_Product_Price_Based_Country' ) || class_exists( 'WC_Aelia_CurrencySwitcher' ) ) {
					$option_price = BaseCurrency::convert( $option_price );
				}

				$product->set_price( $option_price );
			}
		}
	}

	/**
	 * Save Option Meta to cart Data
	 *
	 *  @param object $cart_item_data cart Object.
	 *  @param string $product_id ID of product.
	 *  @param string $variation_id variation ID of product.
	 *
	 * @return object
	 */
	public function save_custom_meta_to_cart( $cart_item_data, $product_id, $variation_id ) {
		$prad_selection = isset( $_POST['prad_selection'] ) ? product_addons()->sanitize_rest_params( $_POST['prad_selection'] ) : ''; //phpcs:ignore
		$option_ids     = isset( $_POST['prad_option_published_ids'] ) ? product_addons()->sanitize_rest_params( json_decode( wp_unslash( $_POST['prad_option_published_ids'] ), true ) ) : array();//phpcs:ignore
		$prad_products_selection = isset( $_POST['prad_products_selection'] ) ? product_addons()->sanitize_rest_params( $_POST['prad_products_selection'] ) : ''; // phpcs:ignore
		$prad_products_selection = json_decode( product_addons()->safe_stripslashes( $prad_products_selection ), true );

		$_POST['prad_selection']            = '';
		$_POST['prad_option_published_ids'] = '';
		$_POST['prad_products_selection']   = '';

		if ( ! empty( $option_ids ) ) {
			foreach ( $option_ids as $id ) {
				do_action( 'prad_update_stats_table_data', $id, 'add_to_cart_count', '' );
			}
		}
		if ( ! empty( $prad_selection ) ) {
			$data = $this->calculate_option_price( $prad_selection, $product_id, $option_ids, ! empty( $variation_id ) ? $variation_id : '', isset( $_POST['quantity'] ) ? absint( wp_unslash( $_POST['quantity'] ) ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification

			$cart_item_data['prad_selection']            = $data; // This will be used in checkout order create and others order area
			$cart_item_data['prad_products_selection']   = $prad_products_selection;
			$cart_item_data['prad_selection_base_price'] = apply_filters(
				'prad_cart_checkout_page_price',
				! empty( $variation_id ) ? $variation_id : $product_id
			);
			$cart_item_data['prad_selection_raw']        = $prad_selection;
			$cart_item_data['prad_option_published_ids'] = $option_ids;
		}
		return $cart_item_data;
	}

	/**
	 * Display Option Meta in cart Data
	 *
	 *  @param array  $item_data Item Object.
	 *  @param object $cart_item Cart Object .
	 *
	 * @return object
	 */
	public function display_custom_meta_in_cart( $item_data, $cart_item ) {
		if ( ( is_cart() && Xpo::get_prad_settings_item( 'hideFieldsInCart', false ) ) ||
		( is_checkout() && Xpo::get_prad_settings_item( 'hideFieldsInCheckout', false ) ) ) {
			return $item_data;
		}

		// approach 1.
		if ( isset( $cart_item['prad_selection_raw'] ) ) {
			$data = $this->calculate_option_price( $cart_item['prad_selection_raw'], $cart_item['product_id'], $cart_item['prad_option_published_ids'], $cart_item['variation_id'], $cart_item['quantity'] );
			if ( isset( $data['extra_data'] ) ) {
				product_addons()->enqueue_style( 'prad-cart-style', 'wowcart' );

				$cart_asset = product_addons()->get_script_asset( 'assets/js/wowcart.js' );
				wp_enqueue_script( 'prad-cart-script', PRAD_URL . 'assets/js/wowcart.js', $cart_asset['dependencies'], $cart_asset['version'], true );
				wp_set_script_translations( 'prad-cart-script', 'product-addons', PRAD_PATH . 'languages/' );

				$addon_data = $data['extra_data'] ?? array();

				foreach ( $addon_data as $key => $item ) {
					if ( is_array( $item ) && isset( $item['prad_additional'] ) ) {
						unset( $addon_data[ $key ]['prad_additional'] );
					}
				}

				$item_data = array_merge( $item_data, $addon_data );
			}
		}

		/*
		$prad_products_selection = $cart_item['prad_products_selection'];
		if ( is_array( $prad_products_selection ) ) {
		foreach ( $prad_products_selection as $item ) {
			$_id    = isset( $item['id'] ) ? (int) $item['id'] : '';
			$_count = isset( $item['count'] ) ? (int) $item['count'] : 1;
			if ( $_id ) {
				WC()->cart->add_to_cart( $_id, $_count );
			}
		}
		}

		if ( isset( $cart_item['prad_selection']['extra_data'] ) ) {
		wp_enqueue_style( 'prad-cart-style', PRAD_URL . 'assets/css/wowcart.css', array(), PRAD_VER );
		wp_enqueue_script( 'prad-cart-script', PRAD_URL . 'assets/js/wowcart.js', array( 'jquery' ), PRAD_VER, true );
		$item_data = array_merge( $item_data, $cart_item['prad_selection']['extra_data'] );
		}
		*/
		return $item_data;
	}

	/**
	 * Display Option Meta in cart Data
	 *
	 *  @param string $prad_selection selection Object.
	 *  @param string $product_id ID of product.
	 *  @param array  $option_ids option ids of product.
	 *  @param array  $variation_id variation ID of product.
	 *  @param int    $quantity Quantity of product.
	 *
	 * @return array
	 */
	public function calculate_option_price( $prad_selection, $product_id, $option_ids, $variation_id = '', $quantity = 1 ) {
		$pro_active    = product_addons()->is_pro_feature_available();
		$extra_data    = array();
		$price         = 0;
		$price_data    = array();
		$price_product = apply_filters(
			'prad_cart_checkout_page_percentage_price',
			! empty( $variation_id ) ? $variation_id : $product_id
		);

		$the_id               = ! empty( $variation_id ) ? $variation_id : $product_id;
		$the_product          = wc_get_product( $the_id );
		$product_dynamic_data = array(
			'product_price'    => $price_product,
			'product_quantity' => $quantity,
			'product_weight'   => $the_product->get_weight() ? $the_product->get_weight() : 0,
			'product_length'   => $the_product->get_length() ? $the_product->get_length() : 0,
			'product_width'    => $the_product->get_width() ? $the_product->get_width() : 0,
			'product_height'   => $the_product->get_height() ? $the_product->get_height() : 0,
		);

		try {
			/* Fallback for option_ids if it is not set from cart  object   */
			if ( ! ( is_array( $option_ids ) && ! empty( $option_ids ) ) ) {
				$option_ids = product_addons()->get_product_option_ids( $product_id );
			}

			$merged_content = array();
			if ( is_array( $option_ids ) && ! empty( $option_ids ) ) {
				foreach ( $option_ids as $k => $opt_id ) {
					if ( 'publish' === get_post_status( $opt_id ) ) {
						$content = get_post_meta( $opt_id, 'prad_addons_blocks', true );
						$content = wp_json_encode( $content );
						$content = json_decode( $content );
						if ( ! empty( $content ) ) {
							$merged_content = array_merge( $merged_content, $content );
						}
					}
				}
			}

			$prad_cart_item_selection = json_decode( product_addons()->safe_stripslashes( $prad_selection ), true );
			if ( is_array( $prad_cart_item_selection ) && ! empty( $prad_cart_item_selection ) ) {
				$prad_allowed_html_tags = apply_filters( 'prad_allowed_html_tags', array() );// phpcs:ignore
				foreach ( $prad_cart_item_selection as $key => $field ) {
					$option_data    = $this->get_options_by_blockid( $merged_content, $key );
					$custom_formula = ( isset( $field['type'] ) && 'custom_formula' === $field['type'] );
					$adv_formula    = ( isset( $field['type'] ) && 'advanced_formula' === $field['type'] );
					if ( $option_data || $custom_formula || $adv_formula ) {
						if ( isset( $field['type'] ) && 'products' === $field['type'] ) {
							continue;
						}
						if ( isset( $field['type'] ) && 'upload' === $field['type'] ) {
							if ( ! empty( $field['value'] ) ) {
								$res = '<span>';
								foreach ( $field['value'] as $item ) {
									$res .= wp_kses( '<a href="' . esc_url( $item['path'] ) . '">' . esc_html( $item['name'] ) . '</a>&nbsp;&nbsp;', $prad_allowed_html_tags );
								}
								$res .= '</span>';
							}
						} elseif ( is_array( $field['value'] ) ) {
							if ( isset( $field['value']['path'] ) ) {
								if ( $this->is_image_url( $field['value']['path'] ) ) {
									$res = wp_kses( '<a href="' . esc_url( $field['value']['path'] ) . '"><img src="' . esc_url( $field['value']['path'] ) . '" alt="' . esc_attr( $field['value']['name'] ) . '" /></a>', $prad_allowed_html_tags );
								} else {
									$res = wp_kses( '<span><strong>' . __( 'Name', 'product-addons' ) . ': </strong>' . wp_kses_post( $field['value']['name'] ) . '</span><span> <strong>' . __( 'Path', 'product-addons' ) . ':</strong> ' . wp_kses_post( $field['value']['path'] ) . '</span>', $prad_allowed_html_tags );
								}
							} elseif ( isset( $field['value'][0]['label'] ) ) {
								$labels = '';
								foreach ( $field['value'] as $item ) {
									$count_val = isset( $item['count'] ) && '' !== $item['count'] ? $item['count'] : 1;
									if ( isset( $item['label'] ) ) {
										$label  = wp_kses( '<span>' . $item['label'] . '</span>', $prad_allowed_html_tags );
										$count  = wp_kses( '<span> <strong>' . __( 'Count', 'product-addons' ) . ':</strong> ' . $count_val . '</span>', $prad_allowed_html_tags );
										$labels = $labels . $label . $count . ', ';
									} else {
										$labels = $labels . $item . ', ';
									}
								}
								$res = $labels;
							} elseif ( isset( $field['value']['date'] ) || isset( $field['value']['time'] ) ) {
								$date = isset( $field['value']['date'] ) ? $field['value']['date'] : '';
								$time = isset( $field['value']['time'] ) ? $field['value']['time'] : '';
								$res  = $date . ' ' . $time;
							} else {
								$res = implode( ' | ', $field['value'] );
							}
						} else {
							$res = $field['value'];
						}

						$opt_price = 0;

						if ( isset( $field['_vDatas'] ) ) {
							$addon_field     = $this->get_addon_field_by_blockid( $merged_content, $key );
							$same_price_data = ! empty( $addon_field->samePrice ) ? (array) $addon_field->samePrice : array();  //phpcs:ignore
							if ( ! empty( $same_price_data['enabled'] ) && ! empty( $same_price_data ) ) {
								$sp_cost   = ( isset( $same_price_data['sale'] ) && $same_price_data['sale'] && $pro_active ) ? $same_price_data['sale'] : ( $same_price_data['regular'] ?? 0 );  //phpcs:ignore
								$sp_p_type = $same_price_data['type'] ?? 'fixed';  //phpcs:ignore

								if ( 'no_cost' === $sp_p_type ) {
										$opt_price = 0;
								} elseif ( 'percentage' === $sp_p_type ) {
									$price_product = apply_filters(
										'prad_cart_checkout_page_percentage_price',
										! empty( $variation_id ) ? $variation_id : $product_id
									);
									$opt_price     = ( ( floatval( $price_product ) * floatval( $sp_cost ) ) / 100 );
								} else {
									$opt_price = floatval( $sp_cost );
								}
							} else {
								foreach ( $field['_vDatas'] as $i => $index ) {
									if ( isset( $option_data[ $index ] ) ) {
										$cost   = ( isset( $option_data[ $index ]->sale ) && $option_data[ $index ]->sale && $pro_active ) ? $option_data[ $index ]->sale : $option_data[ $index ]->regular;
										$p_type = $option_data[ $index ]->type;
										$value  = $res ? $res : '';
										if (
										! $pro_active &&
										( 'per_unit' === $p_type || 'per_word' === $p_type || 'per_char_no_space' === $p_type )
										) {
											$opt_price = $opt_price + floatval( $cost );
										} elseif ( 'per_unit' === $p_type ) {
											if ( isset( $field['type'] ) && in_array( $field['type'], array( 'radio', 'checkbox', 'switch', 'img_switch', 'color_switch' ), true ) ) {
												$value = 1;
												if ( isset( $field['value'] ) && isset( $field['value'][ $i ] ) ) {
													if ( isset( $field['value'][ $i ]['count'] ) ) {
														$value = $field['value'][ $i ]['count'];
													}
												}
												$opt_price = $opt_price + floatval( $value ) * floatval( $cost );
											} else {
												$opt_price = $opt_price + floatval( $value ) * floatval( $cost );
											}
										} elseif ( 'per_char' === $p_type ) {
											$char_count = mb_strlen( $value ); // Get the number of characters in the string.
											$opt_price  = $opt_price + ( $char_count * floatval( $cost ) );
										} elseif ( 'per_char_no_space' === $p_type ) {
											$char_count = mb_strlen( str_replace( ' ', '', $value ) ); // Get the number of characters in the string except space.
											$opt_price  = $opt_price + ( $char_count * floatval( $cost ) );
										} elseif ( 'per_word' === $p_type ) {
											$word_count = str_word_count( $value ); // Get the number of words in the string.
											$opt_price  = $opt_price + ( $word_count * floatval( $cost ) );
										} elseif ( 'percentage' === $p_type ) {
											$price_product = apply_filters(
												'prad_cart_checkout_page_percentage_price',
												! empty( $variation_id ) ? $variation_id : $product_id
											);
											$opt_price     = $opt_price + ( ( floatval( $price_product ) * floatval( $cost ) ) / 100 );
										} elseif ( 'no_cost' === $p_type ) {
											$opt_price = $opt_price + 0;
										} else {
											$opt_price = $opt_price + floatval( $cost );
										}
									}
								}
							}
						} elseif ( $custom_formula ) {
							$addon_field = $this->get_addon_field_by_blockid( $merged_content, $key );
							$expression  = ! empty( $addon_field->formulaData->expression ) ? $addon_field->formulaData->expression : '';
							$valid       = ! empty( $addon_field->formulaData->valid ) ? $addon_field->formulaData->valid : false;
							if ( $valid && $expression ) {
								$dynamic_variables = array(
									'product_price' => $price_product,
								);
								foreach ( $prad_cart_item_selection as $f_key => $f_value ) {
									if ( isset( $f_value['type'] ) ) {
										if ( 'number' === $f_value['type'] || 'range' === $f_value['type'] ) {
											$dynamic_variables[ $f_key ] = isset( $f_value['value'] ) ? floatval( $f_value['value'] ) : 0;
										} elseif ( ( 'select' === $f_value['type'] || 'dropdown' === $f_value['type'] ) && isset( $f_value['_vDatas'][0] ) ) {
											// $dynamic_variables[ $f_key ] = isset( $f_value['cost'][0] ) ? floatval( $f_value['cost'][0] ) : 0;
											$selected_option_index = $f_value['_vDatas'][0];
											if ( '' !== $selected_option_index ) {
												$select_block_data   = $this->get_options_by_blockid( $merged_content, $f_key );
												$select_block_cost   = ( isset( $select_block_data[ $selected_option_index ]->sale ) && $select_block_data[ $selected_option_index ]->sale && $pro_active ) ? $select_block_data[ $selected_option_index ]->sale : $select_block_data[ $selected_option_index ]->regular;
												$select_block_p_type = $select_block_data[ $selected_option_index ]->type;

												$calculated_cost = floatval( $select_block_cost );
												if ( 'percentage' === $select_block_p_type ) {
													$calculated_cost = ( floatval( $price_product ) * floatval( $select_block_cost ) ) / 100;
												} elseif ( 'no_cost' === $select_block_p_type ) {
													$calculated_cost = 0;
												}
												$dynamic_variables[ $f_key ] = $calculated_cost;
											}
										}
									}
								}
								$formula_price = $this->evaluate_expression( $expression, $dynamic_variables );
								$opt_price     = $formula_price;
								$res           = '';
							}
						} elseif ( $adv_formula ) {
							$addon_field = $this->get_addon_field_by_blockid( $merged_content, $key );
							$expression  = ! empty( $addon_field->advancedFormulaData->expression ) ? $addon_field->advancedFormulaData->expression : '';
							$valid       = ! empty( $addon_field->advancedFormulaData->valid ) ? $addon_field->advancedFormulaData->valid : false;

							if ( $expression && $valid ) {
								$dynamic_variables = ! empty( $field['dynamics'] ) ? $field['dynamics'] : array();
								$dynamic_variables = array_merge( $dynamic_variables, $product_dynamic_data );

								// Have to Update Dynamic for Products options later.
								$result    = Array_Expression_Engine::evaluate_expression_safe(
									$expression,
									$dynamic_variables
								);
								$opt_price = is_numeric( $result ) ? floatval( $result ) : 0;
								$res       = '';
							}
						} else {
							$cost   = ( isset( $option_data[0]->sale ) && $option_data[0]->sale && $pro_active ) ? $option_data[0]->sale : $option_data[0]->regular;
							$p_type = $option_data[0]->type;
							$value  = $res;
							if (
							! $pro_active &&
							( 'per_unit' === $p_type || 'per_word' === $p_type || 'per_char_no_space' === $p_type )
							) {
								$opt_price = floatval( $cost );
							} elseif ( 'per_unit' === $p_type ) {
								$opt_price = floatval( $value ) * floatval( $cost );
							} elseif ( 'per_char' === $p_type ) {
								$char_count = mb_strlen( $value ); // Get the number of characters in the string.
								$opt_price  = $char_count * floatval( $cost );
							} elseif ( 'per_char_no_space' === $p_type ) {
								$char_count = mb_strlen( mb_strlen( str_replace( ' ', '', $value ) ) ); // Get the number of characters in the string.
								$opt_price  = ( $char_count * floatval( $cost ) );
							} elseif ( 'per_word' === $p_type ) {
								$word_count = str_word_count( $value ); // Get the number of words in the string.
								$opt_price  = ( $word_count * floatval( $cost ) );
							} elseif ( 'percentage' === $p_type ) {
								$price_product = apply_filters(
									'prad_cart_checkout_page_percentage_price',
									! empty( $variation_id ) ? $variation_id : $product_id
								);
								$opt_price     = ( floatval( $price_product ) * floatval( $cost ) ) / 100;
							} elseif ( 'no_cost' === $p_type ) {
								$opt_price = 0;
							} else {
								$opt_price = floatval( $cost );
							}
						}

						if ( isset( $field['optionid'] ) ) {
							if ( ! isset( $price_data[ $field['optionid'] ] ) ) {
								$price_data[ $field['optionid'] ] = 0;
							}
							$price_data[ $field['optionid'] ] += $opt_price;
						}

						$extra_data[] = array(
							'prad_additional' => array(
								'type'                => $field['type'] ? $field['type'] : '',
								'field_raw'           => $field,
								'opt_price'           => $opt_price,
								'opt_price_with_html' => '<strong>  +<span class="prad-price">' . wc_price(
									apply_filters(
										'prad_raw_tax_currency_compitable_price',
										array(
											'product_id' => $product_id,
											'price'      => $opt_price,
											'source'     => 'cart',
										)
									)
								) . '</span></strong>',
							),
							'name'            => ! empty( $field['label'] ) ? esc_html( $field['label'] ) : __( 'Addons Field', 'product-addons' ),
							'value'           => $opt_price ? $res . '<strong>  +<span class="prad-price">' . wc_price(
								apply_filters(
									'prad_raw_tax_currency_compitable_price',
									array(
										'product_id' => $product_id,
										'price'      => $opt_price,
										'source'     => 'cart',
									)
								)
							) . '</span></strong>' : $res,
						);

						$price = $price + $opt_price;
					}
				}
			}
		} catch ( \Exception $e ) {
			return null;
		}
		return array(
			'price'      => $price,
			'price_data' => $price_data,
			'extra_data' => $extra_data,
			'option_ids' => $option_ids,
		);
	}

	/**
	 * Display Option Meta in cart Data
	 *
	 *  @param string $url file url.
	 *
	 * @return bool
	 */
	public function is_image_url( $url ) {
		$file_path = wp_parse_url( $url, PHP_URL_PATH );
		$file_name = basename( $file_path );
		$file_type = wp_check_filetype( $file_name );
		return isset( $file_type['type'] ) && strpos( $file_type['type'], 'image/' ) === 0;
	}

	/**
	 * Display Option Meta in cart Data
	 *
	 *  @param string $item_id ID of item.
	 *  @param array  $values selected.
	 *
	 * @return void
	 */
	public function save_custom_meta_to_order( $item_id, $values ) {

		if ( isset( $values['prad_selection']['extra_data'] ) ) {
			foreach ( $values['prad_selection']['extra_data'] as $val ) {
				wc_add_order_item_meta( $item_id, $val['name'], $val['value'] );
			}
		}
	}

	/**
	 * Display Option Meta in cart Data
	 *
	 *  @param array  $blocksarray blocks array.
	 *  @param string $blockid blockid .
	 *
	 * @return array
	 */
	public function get_options_by_blockid( $blocksarray, $blockid ) {
		try {
			foreach ( $blocksarray as $field ) {
				if (
				isset( $field->blockid ) &&
				$field->blockid === $blockid
				) {
					return $field->_options ?? null;
				}
				if ( isset( $field->innerBlocks ) ) {	//phpcs:ignore
					$result = $this->get_options_by_blockid( $field->innerBlocks, $blockid );	//phpcs:ignore
					if ( null !== $result ) {
						return $result;
					}
				}
			}
		} catch ( \Exception $e ) {
			return null;
		}
		return null;
	}

	/**
	 * Get Addon Field by Block ID
	 *
	 *  @param array  $blocksarray blocks array.
	 *  @param string $blockid blockid .
	 *
	 * @return object|null
	 */
	public function get_addon_field_by_blockid( $blocksarray, $blockid ) {
		try {
			foreach ( $blocksarray as $field ) {
				if (
				isset( $field->blockid ) &&
				$field->blockid === $blockid
				) {
					return $field ?? null;
				}
				if ( isset( $field->innerBlocks ) ) {	//phpcs:ignore
					$result = $this->get_addon_field_by_blockid( $field->innerBlocks, $blockid );	//phpcs:ignore
					if ( null !== $result ) {
						return $result;
					}
				}
			}
		} catch ( \Exception $e ) {
			return null;
		}
		return null;
	}

	/**
	 * PHP function to evaluate a mathematical expression string,
	 * including dynamic variables and percentages.
	 *
	 * @param string $expression The mathematical expression string.
	 * @param array  $dynamic_variables An associative array of dynamic variables to replace in the expression.
	 * @return float The calculated result.
	 */
	public function evaluate_expression( $expression, $dynamic_variables = array() ) {
		return SafeMathEvaluator::evaluate_expression( $expression, $dynamic_variables );
	}

	/**
	 * Validates required fields before adding product to cart.
	 *
	 * @param bool $passed Whether validation passed.
	 * @param int  $product_id The product ID.
	 * @return bool The validation result.
	 */
	public function prad_validate_before_add_to_cart( $passed, $product_id ) {

		$blocks_data = $this->blocks_service->get_product_blocks( $product_id );
		if ( empty( $blocks_data['published_ids'] ) ) {
			return $passed;
		}

		$required_fields = $this->collect_required_fields( $blocks_data );

		if ( empty( $required_fields ) ) {
			return $passed;
		}

		$prad_selection = $this->parse_prad_selection();

		$validate = $this->validate_required_fields( $required_fields, $prad_selection, $passed );

		if ( ! $validate ) {
			wc_add_notice( __( 'Please fill in all required fields.', 'product-addons' ), 'error' );
		}

		return $validate;
	}

	/**
	 * Collects required fields from blocks data.
	 *
	 * @param array $blocks_data The blocks data array.
	 * @return array An array of required fields.
	 */
	private function collect_required_fields( $blocks_data ) {
		$published_ids = $blocks_data['published_ids'] ?? array();

		$db_ids    = array();
		$block_ids = array();

		foreach ( $published_ids as $option_id ) {
			$meta = get_post_meta( $option_id, 'prad_required_options', true );
			if ( ! is_array( $meta ) ) {
				$block_ids[] = $option_id;
			} elseif ( ! empty( $meta ) ) {
				$db_ids[ $option_id ] = $meta;
			}
		}

		$required = array();

		if ( ! empty( $db_ids ) ) {
			$required = array_merge(
				$required,
				$this->collect_required_fields_from_db_meta( $db_ids )
			);
		}

		if ( ! empty( $block_ids ) ) {
			$filtered_blocks = $this->filter_blocks_by_ids( $blocks_data['blocks'] ?? array(), $block_ids );
			$required        = array_merge(
				$required,
				$this->collect_required_fields_from_blocks( $filtered_blocks )
			);
		}

		return $required;
	}

	/**
	 * Collects required fields from database.
	 *
	 * @param array $db_ids_with_meta The database IDs with their associated meta.
	 * @return array An array of required fields.
	 */
	private function collect_required_fields_from_db_meta( $db_ids_with_meta ) {
		$required = array();

		foreach ( $db_ids_with_meta as $option_id => $meta ) {
			foreach ( $meta as $field ) {
				$block_id   = array_key_first( $field );
				$required[] = array(
					'block_id' => $block_id,
					'type'     => $field[ $block_id ]['type'] ?? 'field',
				);
			}
		}
		return $required;
	}

	/**
	 * Filters a blocks array to only include entries whose keys are in the given ID list.
	 *
	 * @param array $all_blocks Associative array of blocks keyed by option/addon ID.
	 * @param array $block_ids  List of option IDs to keep.
	 * @return array Filtered blocks array.
	 */
	private function filter_blocks_by_ids( $all_blocks, $block_ids ) {
		$id_set = array_flip( $block_ids );

		return array_filter(
			$all_blocks,
			function ( $key ) use ( $id_set ) {
				return isset( $id_set[ $key ] );
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Collects required fields from blocks.
	 *
	 * @param array $blocks The blocks array.
	 * @return array An array of required fields.
	 */
	private function collect_required_fields_from_blocks( $blocks ) {
		$required = array();

		foreach ( $blocks as $blocklist ) {
			foreach ( $blocklist as $block ) {
				$required = array_merge( $required, $this->get_required_blocks( $block ) );
			}
		}

		return $required;
	}

	/**
	 * Parses the prad_selection field from the POST request.
	 *
	 * Decodes the JSON string sent by the frontend into an associative array.
	 * Returns an empty array when the field is absent or the JSON is invalid.
	 *
	 * @return array Decoded selection data, or an empty array on failure.
	 */
	private function parse_prad_selection() {
		$raw = isset( $_POST['prad_selection'] ) ? product_addons()->sanitize_rest_params( $_POST['prad_selection'] ) : ''; //phpcs:ignore
		$decoded = json_decode( product_addons()->safe_stripslashes( $raw ), true );
		return is_array( $decoded ) && ! empty( $decoded ) ? $decoded : array();
	}

	/**
	 * Validates required fields.
	 *
	 * @param array $required_fields The array of required fields.
	 * @param array $prad_selection The selected product addons.
	 * @param bool  $passed Whether validation has passed so far.
	 * @return bool True if all required fields are filled, false otherwise.
	 */
	private function validate_required_fields( $required_fields, $prad_selection, $passed ) {
		if ( empty( $prad_selection ) ) {
			return false;
		}

		$submitted_ids = array_keys( $prad_selection );

		foreach ( $required_fields as $field ) {
			if ( ! in_array( $field['block_id'], $submitted_ids, true ) ) {
				return false;
			}
		}

		return $passed;
	}

	/**
	 * Gets required blocks from a block configuration.
	 *
	 * @param array $block The block configuration array.
	 * @return array An array of required blocks.
	 */
	private function get_required_blocks( $block ) {
		$required = array();

		if ( $block['en_logic'] ?? false ) {
			return $required;
		} elseif ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			foreach ( $block['innerBlocks'] as $inner_block ) {
				$required = array_merge(
					$required,
					$this->get_required_blocks( $inner_block )
				);
			}
		} elseif ( isset( $block['required'] ) && true === $block['required'] ) {
			$required[] = array(
				'block_id' => $block['blockid'] ?? '',
				'type'     => $block['type'] ?? 'field',
			);
		}

		return $required;
	}
}
