<?php
/**
 * PriceHandler.
 *
 * @package PRAD
 * @since 1.6.14
 */

namespace PRAD\Includes\Order;

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
	 * @param array|string $prad_selection Sanitized selection data, or a legacy JSON string.
	 * @param int          $product_id     Product ID.
	 * @param array        $option_ids     Published addon option post IDs.
	 * @param int|string   $variation_id   Variation ID, or empty string.
	 * @param int          $quantity       Cart item quantity.
	 * @return array|null Array with keys 'price', 'price_data', 'extra_data', 'option_ids',
	 *                    or null on exception.
	 */
	public function calculate_option_price( $prad_selection, $product_id, $option_ids, $variation_id = '', $quantity = 1 ) {
		return $this->calculate_option_price_v2( $prad_selection, $product_id, $option_ids, $variation_id, $quantity );
	}

	/**
	 * Calculate the total addon price for a cart-item selection (v2).
	 *
	 * Refactored version of calculate_option_price. Delegates all sub-tasks
	 * to private helpers so each concern can be read and tested in isolation.
	 *
	 * @param array|string $prad_selection Sanitized selection data, or a legacy JSON string.
	 * @param int          $product_id     Product ID.
	 * @param array        $option_ids     Published addon option post IDs.
	 * @param int|string   $variation_id   Variation ID, or empty string.
	 * @param int          $quantity       Cart item quantity.
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
				'merged_content' => $this->build_merged_content( $option_ids ),
				'price_product'  => $price_product,
				'product_id'     => $product_id,
				'variation_id'   => $variation_id,
				'quantity'       => $quantity,
				'allowed_tags'   => apply_filters( 'prad_allowed_html_tags', array() ), // phpcs:ignore
			);

			// Cart items saved by earlier versions hold the raw JSON string; decode and sanitize it.
			$prad_cart_item_selection = is_array( $prad_selection )
				? $prad_selection
				: product_addons()->sanitize_selection_data( json_decode( product_addons()->safe_stripslashes( $prad_selection ), true ) );
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
		if ( 'section' === $field['type'] ) {
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
	 * Process a flat list of fields and return aggregated price, price_data, and extra_data.
	 *
	 * Single source of truth for field processing — called for the top-level cart selection,
	 * and (via the prad_process_section_field filter) for each repeater instance's
	 * selectedFields in product-addons-pro's section-repeater feature. Public so that
	 * filter can recurse into it.
	 *
	 * @param array $fields Keyed array of field entries (blockid => field data).
	 * @param array $ctx    Calculation context.
	 *                        merged_content — addon block definitions
	 *                        price_product  — base price for percentage calculations
	 *                        product_id     — used when building price HTML
	 *                        variation_id   — the cart item's variation, or ''
	 *                        quantity       — the cart item's quantity
	 *                        allowed_tags   — wp_kses allowed HTML tags.
	 * @return array Array with keys 'price', 'price_data', and 'extra_data'.
	 */
	public function process_fields( array $fields, array $ctx ) {
		$price      = 0;
		$price_data = array();
		$extra_data = array();

		foreach ( $fields as $key => $field ) {
			$type = $field['type'] ?? '';

			if ( 'products' === $type ) {
				continue;
			}

			if ( 'section' === $type ) {
				// Section repeaters are a pro-only feature (product-addons-pro hooks this
				// filter). Without it, a section falls through and prices like a plain field.
				$result = apply_filters( 'prad_process_section_field', null, $field, $key, $ctx, $this );
				if ( is_array( $result ) ) {
					$price     += $result['price'];
					$extra_data = array_merge( $extra_data, $result['extra_data'] );
					foreach ( $result['price_data'] as $opt_id => $opt_price_val ) {
						$price_data[ $opt_id ] = ( $price_data[ $opt_id ] ?? 0 ) + $opt_price_val;
					}
					continue;
				}
			}

			$option_data = $this->get_options_by_blockid( $ctx['merged_content'], $key );
			$own_price   = $this->get_field_own_price( $field, $key, $fields, $ctx );
			if ( ! $option_data && null === $own_price ) {
				continue;
			}

			if ( null !== $own_price ) {
				// A field priced by its own rule (a formula) lists only its price.
				$res       = '';
				$opt_price = floatval( $own_price );
			} else {
				$res       = $this->resolve_field_display_value( $field, $ctx['allowed_tags'] );
				$opt_price = $this->calculate_field_price( $field, $key, $option_data, $res, $ctx );
			}

			if ( isset( $field['optionid'] ) ) {
				$price_data[ $field['optionid'] ] = ( $price_data[ $field['optionid'] ] ?? 0 ) + $opt_price;
			}

			$entry = $this->build_extra_data_entry( $field, $opt_price, $ctx['product_id'], $res, $ctx['showPrice'] ?? true );
			if ( null !== $own_price ) {
				$entry['prad_additional']['price_only'] = true;
			}

			$extra_data[] = $entry;
			$price       += $opt_price;
		}

		return compact( 'price', 'price_data', 'extra_data' );
	}

	/**
	 * The price of a field priced by its own rule rather than by its options, such as a
	 * Custom Formula field.
	 *
	 * @param array  $field        The field data array.
	 * @param string $key          Block ID of the field.
	 * @param array  $local_fields All fields in the same scope (used for custom_formula variables).
	 * @param array  $ctx          Calculation context — see process_fields().
	 * @return float|null Null when the field is priced by its options.
	 */
	private function get_field_own_price( $field, $key, $local_fields, array $ctx ) {
		$addon_field = $this->get_addon_field_by_blockid( $ctx['merged_content'], $key );

		if ( 'custom_formula' === ( $field['type'] ?? '' ) ) {
			return $this->calculate_custom_formula_price( $addon_field, $local_fields, $ctx['merged_content'], $ctx['price_product'] );
		}

		/**
		 * Filters the price of a field priced by its own rule rather than by its options,
		 * e.g. a field type another plugin adds. Such a field lists only its price in the cart.
		 *
		 * @since 1.8.3
		 *
		 * @param float|null  $price       Null to price the field by its options.
		 * @param array       $field       The field's cart data.
		 * @param object|null $addon_field The field's definition.
		 * @param array       $ctx         Calculation context: price_product, product_id,
		 *                                 variation_id and quantity, among others.
		 */
		$price = apply_filters( 'prad_field_own_price', null, $field, $addon_field, $ctx );

		return is_numeric( $price ) ? floatval( $price ) : null;
	}

	/**
	 * Dispatch to the right price calculator based on field type.
	 *
	 * Priority: _vDatas (variant selections) → plain option.
	 *
	 * @param array      $field       The field data array.
	 * @param string     $key         Block ID of the field.
	 * @param array|null $option_data Options from the block definition.
	 * @param mixed      $res         Pre-resolved display value.
	 * @param array      $ctx         Calculation context — see process_fields().
	 * @return float
	 */
	private function calculate_field_price( $field, $key, $option_data, $res, array $ctx ) {
		if ( isset( $field['_vDatas'] ) ) {
			$addon_field = $this->get_addon_field_by_blockid( $ctx['merged_content'], $key );
			return $this->calculate_vdata_price( $field, $addon_field, $option_data, $res, $ctx['price_product'] );
		}
		return $this->calculate_plain_option_price( $option_data, $res, $ctx['price_product'] );
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
	 * @return float
	 */
	private function calculate_vdata_price( $field, $addon_field, $option_data, $res, $price_product ) {
		// A price shared by every selected variant is supplied by product-addons-pro.
		$same_price = apply_filters( 'prad_variant_same_price', null, $addon_field, $price_product );
		if ( null !== $same_price ) {
			return $same_price;
		}

		$opt_price = 0;
		foreach ( $field['_vDatas'] as $i => $index ) {
			if ( ! isset( $option_data[ $index ] ) ) {
				continue;
			}
			$cost       = $this->resolve_option_cost( $option_data[ $index ] );
			$p_type     = $option_data[ $index ]->type;
			$value      = $this->resolve_per_variant_value( $field, $i );
			$opt_price += $this->apply_price_type( $cost, $p_type, $value, $price_product );
		}
		return $opt_price;
	}

	/**
	 * Resolve the effective value for one entry in the _vDatas loop.
	 *
	 * Only choice fields send _vDatas, so the value of a selected option is how many of
	 * it were chosen: the count saved with it (a quantity another plugin adds), or 1.
	 *
	 * @param array $field Field data array.
	 * @param int   $i     Index into the _vDatas array.
	 * @return mixed
	 */
	private function resolve_per_variant_value( $field, $i ) {
		$selected = $field['value'][ $i ] ?? null;
		return is_array( $selected ) && isset( $selected['count'] ) ? $selected['count'] : 1;
	}

	/**
	 * Return the effective cost for one option.
	 *
	 * Free always uses the regular price. product-addons-pro hooks prad_resolve_option_cost
	 * to return the sale price instead when one is set and licensing allows it — that
	 * decision lives entirely in the pro plugin.
	 *
	 * @param object $option Option object with 'regular' and optional 'sale' properties.
	 * @return mixed
	 */
	private function resolve_option_cost( $option ) {
		return apply_filters( 'prad_resolve_option_cost', $option->regular, $option );
	}

	/**
	 * Apply a pricing-type rule and return the resulting price contribution.
	 *
	 * Price types this plugin doesn't know (e.g. ones another plugin adds through
	 * prad_custom_price_type) fall back to a flat cost.
	 *
	 * @param mixed  $cost          Option cost (regular or sale).
	 * @param string $p_type        Pricing type (fixed, percentage, per_char, etc.).
	 * @param mixed  $value         The field's display or count value.
	 * @param mixed  $price_product Effective price for percentage calculations.
	 * @return float
	 */
	private function apply_price_type( $cost, $p_type, $value, $price_product ) {
		$custom_price_result = apply_filters( 'prad_custom_price_type', null, $p_type, $cost, $value );
		if ( null !== $custom_price_result ) {
			return $custom_price_result;
		}
		switch ( $p_type ) {
			case 'per_char':
				return mb_strlen( $value ) * floatval( $cost );
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
	 * Public so the pro-only section-repeater feature (which needs a section's own
	 * base price) can call it — see the prad_process_section_field filter.
	 *
	 * @param array|null $option_data   Options array from the block definition.
	 * @param mixed      $value         The field's display or count value.
	 * @param mixed      $price_product Effective price for percentage calculations.
	 * @return float
	 */
	public function calculate_plain_option_price( $option_data, $value, $price_product ) {
		if ( empty( $option_data[0] ) ) {
			return 0;
		}
		$cost   = $this->resolve_option_cost( $option_data[0] );
		$p_type = $option_data[0]->type;
		return $this->apply_price_type( $cost, $p_type, $value, $price_product );
	}

	/**
	 * Calculate price for a custom_formula field by evaluating its expression
	 * with dynamic variables built from the current selection.
	 *
	 * @param object|null $addon_field              Full addon field object from the block definition.
	 * @param array       $prad_cart_item_selection All fields in the current scope.
	 * @param array       $merged_content           Addon block definitions.
	 * @param mixed       $price_product            Effective price for percentage calculations.
	 * @return float
	 */
	private function calculate_custom_formula_price( $addon_field, $prad_cart_item_selection, $merged_content, $price_product ) {
		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- formulaData is the block schema's own property name (JS/JSON data contract), not a PHP naming choice.
		$expression = ! empty( $addon_field->formulaData->expression ) ? $addon_field->formulaData->expression : '';
		$valid      = ! empty( $addon_field->formulaData->valid ) ? $addon_field->formulaData->valid : false;
		// phpcs:enable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

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
					$dynamic_variables[ $f_key ] = $this->resolve_select_cost_for_formula( $f_key, $selected_index, $merged_content, $price_product );
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
	 * @return float
	 */
	private function resolve_select_cost_for_formula( $f_key, $selected_index, $merged_content, $price_product ) {
		$select_block_data = $this->get_options_by_blockid( $merged_content, $f_key );
		if ( empty( $select_block_data[ $selected_index ] ) ) {
			return 0;
		}
		$option = $select_block_data[ $selected_index ];
		$cost   = $this->resolve_option_cost( $option );
		$p_type = $option->type;

		if ( 'percentage' === $p_type ) {
			return ( floatval( $price_product ) * floatval( $cost ) ) / 100;
		}
		if ( 'no_cost' === $p_type ) {
			return 0;
		}
		return floatval( $cost );
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
		$price_html      = '<strong>  +<span class="prad-price">' . $formatted_price . '</span></strong>';

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
