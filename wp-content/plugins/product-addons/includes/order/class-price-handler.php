<?php
/**
 * PriceHandler.
 *
 * @package PRAD
 * @since 1.6.14
 */
namespace PRAD\Includes\Order;

use PRAD\Includes\Common\Formula\Array_Expression_Engine;
use PRAD\Includes\Common\SafeMathEvaluator;

defined( 'ABSPATH' ) || exit;

/**
 * Handles addon option price calculation for cart and checkout.
 *
 * Kept free of CartPage dependencies so external plugins can
 * instantiate it directly:
 *
 *   $handler = new \PRAD\Includes\Order\PriceHandler();
 *   $result  = $handler->calculate_option_price( $selection, $product_id, $option_ids );
 */
class PriceHandler {

	/**
	 * Calculate the total addon price for a cart-item selection.
	 *
	 * @param string     $prad_selection JSON-encoded selection data.
	 * @param int        $product_id     Product ID.
	 * @param array      $option_ids     Published addon option post IDs.
	 * @param int|string $variation_id   Variation ID, or empty string.
	 * @param int        $quantity       Cart item quantity.
	 * @return array|null Array with keys 'price', 'price_data', 'extra_data', 'option_ids',
	 *                    or null on exception.
	 */
	public function calculate_option_price( $prad_selection, $product_id, $option_ids, $variation_id = '', $quantity = 1 ) {
		return $this->calculate_option_price_v2( $prad_selection, $product_id, $option_ids, $variation_id, $quantity );

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
	 * Calculate the total addon price for a cart-item selection (v2).
	 *
	 * Refactored version of calculate_option_price. Delegates all sub-tasks
	 * to private helpers so each concern can be read and tested in isolation.
	 *
	 * @param string     $prad_selection JSON-encoded selection data.
	 * @param int        $product_id     Product ID.
	 * @param array      $option_ids     Published addon option post IDs.
	 * @param int|string $variation_id   Variation ID, or empty string.
	 * @param int        $quantity       Cart item quantity.
	 * @return array|null Array with keys 'price', 'price_data', 'extra_data', 'option_ids',
	 *                    or null on exception.
	 */
	public function calculate_option_price_v2( $prad_selection, $product_id, $option_ids, $variation_id = '', $quantity = 1 ) {
		$price_product = apply_filters(
			'prad_cart_checkout_page_percentage_price',
			! empty( $variation_id ) ? $variation_id : $product_id
		);

		try {
			if ( ! ( is_array( $option_ids ) && ! empty( $option_ids ) ) ) {
				$option_ids = product_addons()->get_product_option_ids( $product_id );
			}

			$ctx = array(
				'merged_content'       => $this->build_merged_content( $option_ids ),
				'price_product'        => $price_product,
				'product_dynamic_data' => $this->get_product_dynamic_data( $product_id, $variation_id, $quantity, $price_product ),
				'pro_active'           => product_addons()->is_pro_feature_available(),
				'product_id'           => $product_id,
				'allowed_tags'         => apply_filters( 'prad_allowed_html_tags', array() ), // phpcs:ignore
			);

			$prad_cart_item_selection = json_decode( product_addons()->safe_stripslashes( $prad_selection ), true );
			if ( ! is_array( $prad_cart_item_selection ) || empty( $prad_cart_item_selection ) ) {
				return array(
					'price'      => 0,
					'price_data' => array(),
					'extra_data' => array(),
					'option_ids' => $option_ids,
				);
			}
			$result = $this->process_fields( $prad_cart_item_selection, $ctx );
		} catch ( \Exception $e ) {
			return null;
		}

		return array(
			'price'      => $result['price'],
			'price_data' => $result['price_data'],
			'extra_data' => $result['extra_data'],
			'option_ids' => $option_ids,
		);
	}

	// -------------------------------------------------------------------------
	// Setup helpers
	// -------------------------------------------------------------------------

	/**
	 * Build the product dimension/quantity data used by advanced formula fields.
	 *
	 * @param int        $product_id    Product ID.
	 * @param int|string $variation_id  Variation ID, or empty string.
	 * @param int        $quantity      Cart item quantity.
	 * @param mixed      $price_product Effective price for percentage calculations.
	 * @return array
	 */
	private function get_product_dynamic_data( $product_id, $variation_id, $quantity, $price_product ) {
		$the_id      = ! empty( $variation_id ) ? $variation_id : $product_id;
		$the_product = wc_get_product( $the_id );
		return array(
			'product_price'    => $price_product,
			'product_quantity' => $quantity,
			'product_weight'   => $the_product->get_weight() ?: 0,
			'product_length'   => $the_product->get_length() ?: 0,
			'product_width'    => $the_product->get_width() ?: 0,
			'product_height'   => $the_product->get_height() ?: 0,
		);
	}

	/**
	 * Collect all addon block definitions from published option post IDs.
	 *
	 * @param array $option_ids Published addon option post IDs.
	 * @return array
	 */
	private function build_merged_content( $option_ids ) {
		$merged_content = array();
		if ( ! is_array( $option_ids ) || empty( $option_ids ) ) {
			return $merged_content;
		}
		foreach ( $option_ids as $opt_id ) {
			if ( 'publish' !== get_post_status( $opt_id ) ) {
				continue;
			}
			$content = json_decode( wp_json_encode( get_post_meta( $opt_id, 'prad_addons_blocks', true ) ) );
			if ( ! empty( $content ) ) {
				$merged_content = array_merge( $merged_content, $content );
			}
		}
		return $merged_content;
	}

	// -------------------------------------------------------------------------
	// Display value helpers
	// -------------------------------------------------------------------------

	/**
	 * Build the human-readable display value for a field selection.
	 *
	 * @param array $field                  Field data array.
	 * @param array $prad_allowed_html_tags Allowed HTML tags for wp_kses.
	 * @return string
	 */
	private function resolve_field_display_value( $field, $prad_allowed_html_tags ) {
		if ( $field['type'] === 'section' ) {
			return '';
		}
		if ( isset( $field['type'] ) && 'upload' === $field['type'] ) {
			return $this->resolve_upload_display_value( $field, $prad_allowed_html_tags );
		}
		if ( is_array( $field['value'] ) ) {
			return $this->resolve_array_value_display( $field['value'], $prad_allowed_html_tags );
		}
		return $field['value'];
	}

	/**
	 * Build display HTML for an upload field (list of file links).
	 *
	 * @param array $field                  Field data array.
	 * @param array $prad_allowed_html_tags Allowed HTML tags for wp_kses.
	 * @return string
	 */
	private function resolve_upload_display_value( $field, $prad_allowed_html_tags ) {
		if ( empty( $field['value'] ) ) {
			return '';
		}
		$res = '<span>';
		foreach ( $field['value'] as $item ) {
			$res .= wp_kses( '<a href="' . esc_url( $item['path'] ) . '">' . esc_html( $item['name'] ) . '</a>&nbsp;&nbsp;', $prad_allowed_html_tags );
		}
		return $res . '</span>';
	}

	/**
	 * Build display value when the field value is an array (image, date/time, labeled items, etc.).
	 *
	 * @param array $value                  Field value array.
	 * @param array $prad_allowed_html_tags Allowed HTML tags for wp_kses.
	 * @return string
	 */
	private function resolve_array_value_display( $value, $prad_allowed_html_tags ) {
		if ( isset( $value['path'] ) ) {
			return $this->is_image_url( $value['path'] )
				? wp_kses( '<a href="' . esc_url( $value['path'] ) . '"><img src="' . esc_url( $value['path'] ) . '" alt="' . esc_attr( $value['name'] ) . '" /></a>', $prad_allowed_html_tags )
				: wp_kses( '<span><strong>' . __( 'Name', 'product-addons' ) . ': </strong>' . wp_kses_post( $value['name'] ) . '</span><span> <strong>' . __( 'Path', 'product-addons' ) . ':</strong> ' . wp_kses_post( $value['path'] ) . '</span>', $prad_allowed_html_tags );
		}
		if ( isset( $value[0]['label'] ) ) {
			return $this->resolve_labeled_items_display( $value, $prad_allowed_html_tags );
		}
		if ( isset( $value['date'] ) || isset( $value['time'] ) ) {
			return ( $value['date'] ?? '' ) . ' ' . ( $value['time'] ?? '' );
		}
		return implode( ' | ', $value );
	}

	/**
	 * Build display string for labeled choice items (radio, checkbox, color_switch, etc.).
	 *
	 * @param array $value                  Array of labeled items.
	 * @param array $prad_allowed_html_tags Allowed HTML tags for wp_kses.
	 * @return string
	 */
	private function resolve_labeled_items_display( $value, $prad_allowed_html_tags ) {
		$labels = '';
		foreach ( $value as $item ) {
			$count_val = isset( $item['count'] ) && '' !== $item['count'] ? $item['count'] : 1;
			if ( isset( $item['label'] ) ) {
				$label   = wp_kses( '<span>' . $item['label'] . '</span>', $prad_allowed_html_tags );
				$count   = wp_kses( '<span> <strong>' . __( 'Count', 'product-addons' ) . ':</strong> ' . $count_val . '</span>', $prad_allowed_html_tags );
				$labels .= $label . $count . ', ';
			} else {
				$labels .= $item . ', ';
			}
		}
		return $labels;
	}

	// -------------------------------------------------------------------------
	// Price calculation helpers
	// -------------------------------------------------------------------------

	/**
	 * Process a section (repeater) field and return aggregated price/data for all instances.
	 *
	 * Each repeater instance produces a single extra_data entry whose name is the instance
	 * label and whose value is the joined "Field: Value" display string with the instance
	 * total price appended. Individual field entries do not include price HTML here — it
	 * is added to the section-level value instead.
	 *
	 * @param array  $section_field Section field array (must contain 'repeatedValues').
	 * @param string $key           Block ID of the section field.
	 * @param array  $ctx           Calculation context — see process_fields().
	 * @return array Array with keys 'price', 'price_data', and 'extra_data'.
	 */
	private function process_section_field( array $section_field, string $key, array $ctx ) {
		$price      = 0;
		$price_data = array();
		$extra_data = array();

		if ( empty( $section_field['repeatedValues'] ) || ! is_array( $section_field['repeatedValues'] ) ) {
			return compact( 'price', 'price_data', 'extra_data' );
		}

		$section_option_data = $this->get_options_by_blockid( $ctx['merged_content'], $key );
		$section_own_price   = $this->calculate_plain_option_price( $section_option_data, '', $ctx['price_product'], $ctx['pro_active'] );

		$ctx_no_price = array_merge( $ctx, array( 'showPrice' => false ) );

		foreach ( $section_field['repeatedValues'] as $repeater_instance ) {
			if ( empty( $repeater_instance['selectedFields'] ) || ! is_array( $repeater_instance['selectedFields'] ) ) {
				continue;
			}

			$result         = $this->process_fields( $repeater_instance['selectedFields'], $ctx_no_price );
			$instance_price = $result['price'] + $section_own_price;
			$price         += $instance_price;

			foreach ( $result['price_data'] as $opt_id => $opt_price_val ) {
				$price_data[ $opt_id ] = ( $price_data[ $opt_id ] ?? 0 ) + $opt_price_val;
			}

			// Build "Field Label: Value" parts from the child entries (no individual prices).
			$parts = array();
			foreach ( $result['extra_data'] as $entry ) {
				$prad_additional = is_array( $entry['prad_additional'] ?? null ) ? $entry['prad_additional'] : array();
				$entry_name      = $entry['name'] ?? '';
				$entry_type      = $prad_additional['type'] ?? '';
				if ( 'custom_formula' === $entry_type || 'advanced_formula' === $entry_type ) {
					$entry_value = $prad_additional['opt_price_with_html'] ?? '';
				} else {
					$entry_value = $entry['value'] ?? '';
				}
				if ( '' !== $entry_name && '' !== $entry_value ) {
					$parts[] = $entry_name . ': ' . $entry_value;
				}
			}
			$joined_value = implode( ', ', $parts );

			// Append the instance total price (fields + section own price) to the joined string.
			if ( $instance_price ) {
				$formatted_price = wc_price(
					apply_filters(
						'prad_raw_tax_currency_compitable_price',
						array(
							'product_id' => $ctx['product_id'],
							'price'      => $instance_price,
							'source'     => 'cart',
						)
					)
				);
				$joined_value .= ' <strong>  +<span class="prad-price">' . $formatted_price . '</span></strong>';
			}

			$instance_label = ! empty( $repeater_instance['label'] ) ? esc_html( $repeater_instance['label'] ) : __( 'Addons Field', 'product-addons' );

			$extra_data[] = array(
				'prad_additional' => array(
					'type'                => 'section',
					'field_raw'           => $result,
					'opt_price'           => $instance_price,
					'opt_price_with_html' => $instance_price ? '<strong>  +<span class="prad-price">' . wc_price(
						apply_filters(
							'prad_raw_tax_currency_compitable_price',
							array(
								'product_id' => $ctx['product_id'],
								'price'      => $instance_price,
								'source'     => 'cart',
							)
						)
					) . '</span></strong>' : '',
				),
				'name'  => $instance_label,
				'value' => $joined_value,
			);
		}

		$repeat_count = count( $section_field['repeatedValues'] );
		if ( $repeat_count > 1 && ! empty( $ctx['price_product'] ) ) {
			$price += floatval( $ctx['price_product'] ) * ( $repeat_count - 1 );
		}

		return compact( 'price', 'price_data', 'extra_data' );
	}

	/**
	 * Process a flat list of fields and return aggregated price, price_data, and extra_data.
	 *
	 * Single source of truth for field processing — called for both the top-level cart
	 * selection and each repeater instance's selectedFields.
	 *
	 * @param array $fields Keyed array of field entries (blockid => field data).
	 * @param array $ctx    Calculation context:
	 *                        merged_content       — addon block definitions
	 *                        price_product        — base price for percentage calculations
	 *                        product_dynamic_data — product dimensions/quantity for advanced formulas
	 *                        pro_active           — whether pro features are enabled
	 *                        product_id           — used when building price HTML
	 *                        allowed_tags         — wp_kses allowed HTML tags
	 * @return array Array with keys 'price', 'price_data', and 'extra_data'.
	 */
	private function process_fields( array $fields, array $ctx ) {
		$price      = 0;
		$price_data = array();
		$extra_data = array();

		foreach ( $fields as $key => $field ) {
			$type = $field['type'] ?? '';

			if ( 'products' === $type ) {
				continue;
			}

			if ( 'section' === $type && $ctx['pro_active'] ) {
				$result     = $this->process_section_field( $field, $key, $ctx );
				$price     += $result['price'];
				$extra_data = array_merge( $extra_data, $result['extra_data'] );
				foreach ( $result['price_data'] as $opt_id => $opt_price_val ) {
					$price_data[ $opt_id ] = ( $price_data[ $opt_id ] ?? 0 ) + $opt_price_val;
				}
				continue;
			}

			$option_data   = $this->get_options_by_blockid( $ctx['merged_content'], $key );
			$is_formula    = ( 'custom_formula' === $type || 'advanced_formula' === $type );
			if ( ! $option_data && ! $is_formula ) {
				continue;
			}

			$res       = $this->resolve_field_display_value( $field, $ctx['allowed_tags'] );
			$opt_price = $this->calculate_field_price( $field, $key, $option_data, $fields, $res, $ctx );

			if ( 'custom_formula' === $type || 'advanced_formula' === $type ) {
				$res = '';
			}

			if ( isset( $field['optionid'] ) ) {
				$price_data[ $field['optionid'] ] = ( $price_data[ $field['optionid'] ] ?? 0 ) + $opt_price;
			}

			$extra_data[] = $this->build_extra_data_entry( $field, $opt_price, $ctx['product_id'], $res, $ctx['showPrice'] ?? true );
			$price       += $opt_price;
		}

		return compact( 'price', 'price_data', 'extra_data' );
	}

	/**
	 * Dispatch to the right price calculator based on field type.
	 *
	 * Priority: _vDatas (variant selections) → custom_formula → adv_formula → plain option.
	 *
	 * @param array      $field        The field data array.
	 * @param string     $key          Block ID of the field.
	 * @param array|null $option_data  Options from the block definition (null for formula fields).
	 * @param array      $local_fields All fields in the same scope (used for custom_formula variables).
	 * @param mixed      $res          Pre-resolved display value.
	 * @param array      $ctx          Calculation context — see process_fields().
	 * @return float
	 */
	private function calculate_field_price( $field, $key, $option_data, $local_fields, $res, array $ctx ) {
		$type = $field['type'] ?? '';
		if ( isset( $field['_vDatas'] ) ) {
			$addon_field = $this->get_addon_field_by_blockid( $ctx['merged_content'], $key );
			return $this->calculate_vdata_price( $field, $addon_field, $option_data, $res, $ctx['price_product'], $ctx['pro_active'] );
		}
		if ( 'custom_formula' === $type ) {
			$addon_field = $this->get_addon_field_by_blockid( $ctx['merged_content'], $key );
			return $this->calculate_custom_formula_price( $addon_field, $local_fields, $ctx['merged_content'], $ctx['price_product'], $ctx['pro_active'] );
		}
		if ( 'advanced_formula' === $type ) {
			$addon_field = $this->get_addon_field_by_blockid( $ctx['merged_content'], $key );
			return $this->calculate_advanced_formula_price( $addon_field, $field, $ctx['product_dynamic_data'] );
		}
		return $this->calculate_plain_option_price( $option_data, $res, $ctx['price_product'], $ctx['pro_active'] );
	}

	/**
	 * Calculate price for a field that has variant selections (_vDatas).
	 *
	 * Handles both "same price for all variants" and individual per-variant pricing.
	 *
	 * @param array       $field         Field data array.
	 * @param object|null $addon_field   Full addon field object from the block definition.
	 * @param array|null  $option_data   Options array from the block definition.
	 * @param mixed       $res           Pre-resolved display value.
	 * @param mixed       $price_product Effective price for percentage calculations.
	 * @param bool        $pro_active    Whether pro features are enabled.
	 * @return float
	 */
	private function calculate_vdata_price( $field, $addon_field, $option_data, $res, $price_product, $pro_active ) {
		$same_price_data = ! empty( $addon_field->samePrice ) ? (array) $addon_field->samePrice : array(); // phpcs:ignore

		if ( ! empty( $same_price_data['enabled'] ) ) {
			return $this->calculate_same_price( $same_price_data, $price_product, $pro_active );
		}

		$opt_price = 0;
		foreach ( $field['_vDatas'] as $i => $index ) {
			if ( ! isset( $option_data[ $index ] ) ) {
				continue;
			}
			$cost       = $this->resolve_option_cost( $option_data[ $index ], $pro_active );
			$p_type     = $option_data[ $index ]->type;
			$value      = $this->resolve_per_variant_value( $field, $i, $p_type, $res );
			$opt_price += $this->apply_price_type( $cost, $p_type, $value, $price_product, $pro_active );
		}
		return $opt_price;
	}

	/**
	 * Calculate price when samePrice is enabled: one cost applies to all selected variants.
	 *
	 * @param array $same_price_data samePrice configuration array.
	 * @param mixed $price_product   Effective price for percentage calculations.
	 * @param bool  $pro_active      Whether pro features are enabled.
	 * @return float
	 */
	private function calculate_same_price( $same_price_data, $price_product, $pro_active ) {
		$cost   = ( isset( $same_price_data['sale'] ) && $same_price_data['sale'] && $pro_active )
			? $same_price_data['sale']
			: ( $same_price_data['regular'] ?? 0 ); // phpcs:ignore
		$p_type = $same_price_data['type'] ?? 'fixed'; // phpcs:ignore

		if ( 'no_cost' === $p_type ) {
			return 0;
		}
		if ( 'percentage' === $p_type ) {
			return ( floatval( $price_product ) * floatval( $cost ) ) / 100;
		}
		return floatval( $cost );
	}

	/**
	 * Resolve the effective value for one entry in the _vDatas loop.
	 *
	 * For per_unit on choice fields (radio, checkbox, switch…) the value is the
	 * item's count; for all other field types it is the display/text value.
	 *
	 * @param array  $field  Field data array.
	 * @param int    $i      Index into the _vDatas array.
	 * @param string $p_type Pricing type for the selected option.
	 * @param mixed  $res    Pre-resolved display value.
	 * @return mixed
	 */
	private function resolve_per_variant_value( $field, $i, $p_type, $res ) {
		$choice_types = array( 'radio', 'checkbox', 'switch', 'img_switch', 'color_switch' );
		if ( 'per_unit' === $p_type && isset( $field['type'] ) && in_array( $field['type'], $choice_types, true ) ) {
			return isset( $field['value'][ $i ]['count'] ) ? $field['value'][ $i ]['count'] : 1;
		}
		return $res ? $res : '';
	}

	/**
	 * Return the effective cost for one option (sale price when available and pro is active).
	 *
	 * @param object $option     Option object with 'regular' and optional 'sale' properties.
	 * @param bool   $pro_active Whether pro features are enabled.
	 * @return mixed
	 */
	private function resolve_option_cost( $option, $pro_active ) {
		return ( isset( $option->sale ) && $option->sale && $pro_active )
			? $option->sale
			: $option->regular;
	}

	/**
	 * Apply a pricing-type rule and return the resulting price contribution.
	 *
	 * Non-pro installs fall back to a flat cost for per_unit/per_word/per_char_no_space.
	 *
	 * @param mixed  $cost          Option cost (regular or sale).
	 * @param string $p_type        Pricing type (fixed, per_unit, per_char, etc.).
	 * @param mixed  $value         The field's display or count value.
	 * @param mixed  $price_product Effective price for percentage calculations.
	 * @param bool   $pro_active    Whether pro features are enabled.
	 * @return float
	 */
	private function apply_price_type( $cost, $p_type, $value, $price_product, $pro_active ) {
		if ( ! $pro_active && in_array( $p_type, array( 'per_unit', 'per_word', 'per_char_no_space' ), true ) ) {
			return floatval( $cost );
		}
		switch ( $p_type ) {
			case 'per_unit':
				return floatval( $value ) * floatval( $cost );
			case 'per_char':
				return mb_strlen( $value ) * floatval( $cost );
			case 'per_char_no_space':
				return mb_strlen( str_replace( ' ', '', $value ) ) * floatval( $cost );
			case 'per_word':
				return str_word_count( $value ) * floatval( $cost );
			case 'percentage':
				return ( floatval( $price_product ) * floatval( $cost ) ) / 100;
			case 'no_cost':
				return 0;
			default:
				return floatval( $cost );
		}
	}

	/**
	 * Calculate price for a plain (non-variant) single-option field.
	 *
	 * @param array|null $option_data   Options array from the block definition.
	 * @param mixed      $value         The field's display or count value.
	 * @param mixed      $price_product Effective price for percentage calculations.
	 * @param bool       $pro_active    Whether pro features are enabled.
	 * @return float
	 */
	private function calculate_plain_option_price( $option_data, $value, $price_product, $pro_active ) {
		if ( empty( $option_data[0] ) ) {
			return 0;
		}
		$cost   = $this->resolve_option_cost( $option_data[0], $pro_active );
		$p_type = $option_data[0]->type;
		return $this->apply_price_type( $cost, $p_type, $value, $price_product, $pro_active );
	}

	/**
	 * Calculate price for a custom_formula field by evaluating its expression
	 * with dynamic variables built from the current selection.
	 *
	 * @param object|null $addon_field              Full addon field object from the block definition.
	 * @param array       $prad_cart_item_selection All fields in the current scope.
	 * @param array       $merged_content           Addon block definitions.
	 * @param mixed       $price_product            Effective price for percentage calculations.
	 * @param bool        $pro_active               Whether pro features are enabled.
	 * @return float
	 */
	private function calculate_custom_formula_price( $addon_field, $prad_cart_item_selection, $merged_content, $price_product, $pro_active ) {
		$expression = ! empty( $addon_field->formulaData->expression ) ? $addon_field->formulaData->expression : '';
		$valid      = ! empty( $addon_field->formulaData->valid ) ? $addon_field->formulaData->valid : false;

		if ( ! $valid || ! $expression ) {
			return 0;
		}

		$dynamic_variables = array( 'product_price' => $price_product );

		foreach ( $prad_cart_item_selection as $f_key => $f_value ) {
			if ( ! isset( $f_value['type'] ) ) {
				continue;
			}
			if ( 'number' === $f_value['type'] || 'range' === $f_value['type'] ) {
				$dynamic_variables[ $f_key ] = isset( $f_value['value'] ) ? floatval( $f_value['value'] ) : 0;
			} elseif ( ( 'select' === $f_value['type'] || 'dropdown' === $f_value['type'] ) && isset( $f_value['_vDatas'][0] ) ) {
				$selected_index = $f_value['_vDatas'][0];
				if ( '' !== $selected_index ) {
					$dynamic_variables[ $f_key ] = $this->resolve_select_cost_for_formula( $f_key, $selected_index, $merged_content, $price_product, $pro_active );
				}
			}
		}

		return $this->evaluate_expression( $expression, $dynamic_variables );
	}

	/**
	 * Resolve a select/dropdown option's effective cost for use as a formula variable.
	 *
	 * @param string $f_key          Block ID of the select/dropdown field.
	 * @param mixed  $selected_index Selected option index within the block's options array.
	 * @param array  $merged_content Addon block definitions.
	 * @param mixed  $price_product  Effective price for percentage calculations.
	 * @param bool   $pro_active     Whether pro features are enabled.
	 * @return float
	 */
	private function resolve_select_cost_for_formula( $f_key, $selected_index, $merged_content, $price_product, $pro_active ) {
		$select_block_data = $this->get_options_by_blockid( $merged_content, $f_key );
		if ( empty( $select_block_data[ $selected_index ] ) ) {
			return 0;
		}
		$option = $select_block_data[ $selected_index ];
		$cost   = $this->resolve_option_cost( $option, $pro_active );
		$p_type = $option->type;

		if ( 'percentage' === $p_type ) {
			return ( floatval( $price_product ) * floatval( $cost ) ) / 100;
		}
		if ( 'no_cost' === $p_type ) {
			return 0;
		}
		return floatval( $cost );
	}

	/**
	 * Calculate price for an advanced_formula field using the Array_Expression_Engine.
	 *
	 * @param object|null $addon_field          Full addon field object from the block definition.
	 * @param array       $field                The field data array (used for 'dynamics').
	 * @param array       $product_dynamic_data Product dimension/quantity data.
	 * @return float
	 */
	private function calculate_advanced_formula_price( $addon_field, $field, $product_dynamic_data ) {
		$expression = ! empty( $addon_field->advancedFormulaData->expression ) ? $addon_field->advancedFormulaData->expression : '';
		$valid      = ! empty( $addon_field->advancedFormulaData->valid ) ? $addon_field->advancedFormulaData->valid : false;

		if ( ! $expression || ! $valid ) {
			return 0;
		}

		$dynamic_variables = array_merge(
			! empty( $field['dynamics'] ) ? $field['dynamics'] : array(),
			$product_dynamic_data
		);

		$result = Array_Expression_Engine::evaluate_expression_safe( $expression, $dynamic_variables );
		return is_numeric( $result ) ? floatval( $result ) : 0;
	}

	// -------------------------------------------------------------------------
	// Output helper
	// -------------------------------------------------------------------------

	/**
	 * Build the extra_data entry for a single processed field.
	 *
	 * @param array  $field       Field data.
	 * @param float  $opt_price   Calculated price for this field.
	 * @param int    $product_id  Product ID used for price formatting.
	 * @param string $res         Pre-resolved display value.
	 * @param bool   $show_price  Whether to append price HTML to the value string.
	 * @return array
	 */
	private function build_extra_data_entry( $field, $opt_price, $product_id, $res, $show_price = true ) {
		$formatted_price = wc_price(
			apply_filters(
				'prad_raw_tax_currency_compitable_price',
				array(
					'product_id' => $product_id,
					'price'      => $opt_price,
					'source'     => 'cart',
				)
			)
		);
		$price_html = '<strong>  +<span class="prad-price">' . $formatted_price . '</span></strong>';

		return array(
			'prad_additional' => array(
				'type'                => $field['type'] ? $field['type'] : '',
				'field_raw'           => $field,
				'opt_price'           => $opt_price,
				'opt_price_with_html' => $price_html,
			),
			'name'            => ! empty( $field['label'] ) ? esc_html( $field['label'] ) : __( 'Addons Field', 'product-addons' ),
			'value'           => ( $show_price && $opt_price ) ? $res . $price_html : $res,
		);
	}

	/**
	 * Get the options array for a given block ID from a blocks tree.
	 *
	 * @param array  $blocksarray The blocks tree (array of objects).
	 * @param string $blockid     The block ID to search for.
	 * @return array|null
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
	 * Get a full addon field object by block ID from a blocks tree.
	 *
	 * @param array  $blocksarray The blocks tree (array of objects).
	 * @param string $blockid     The block ID to search for.
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
	 * Evaluate a mathematical expression with optional dynamic variable substitution.
	 *
	 * @param string $expression        The expression string.
	 * @param array  $dynamic_variables Variables to substitute into the expression.
	 * @return float
	 */
	public function evaluate_expression( $expression, $dynamic_variables = array() ) {
		return SafeMathEvaluator::evaluate_expression( $expression, $dynamic_variables );
	}

	/**
	 * Check whether a URL points to an image file.
	 *
	 * @param string $url The URL to check.
	 * @return bool
	 */
	public function is_image_url( $url ) {
		$file_path = wp_parse_url( $url, PHP_URL_PATH );
		$file_name = basename( $file_path );
		$file_type = wp_check_filetype( $file_name );
		return isset( $file_type['type'] ) && strpos( $file_type['type'], 'image/' ) === 0;
	}
}
