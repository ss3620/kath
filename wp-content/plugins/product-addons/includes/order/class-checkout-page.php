<?php	// phpcs:ignore
/**
 * CartPage.
 *
 * @package PRAD
 * @since v.1.0.0
 */
namespace PRAD\Includes\Order;

defined( 'ABSPATH' ) || exit;

/**
 * CheckoutPage class.
 */
class CheckoutPage {
	/**
	 * Constructor
	 */
	public function __construct() {

		add_action( 'woocommerce_checkout_create_order_line_item', array( $this, 'woocommerce_checkout_create_order_line_item' ), 10, 4 );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'woocommerce_checkout_create_order' ), 10 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'woocommerce_checkout_create_order' ), 10 );
		add_action( 'woocommerce_view_order', array( $this, 'prad_custom_view_order_fields' ), 10, 1 );
		add_action( 'woocommerce_thankyou', array( $this, 'prad_custom_view_order_fields' ), 10, 1 );
		add_action( 'woocommerce_order_status_completed', array( $this, 'prad_woocommerce_order_status_completed' ), 10, 1 );
	}

	/**
	 * Moved Files when order status is completed
	 *
	 * Retrieves the WooCommerce order by ID and checks for the custom meta field
	 *
	 * @param int $order_id The ID of the WooCommerce order being viewed.
	 *
	 * @return void
	 */
	public function prad_woocommerce_order_status_completed( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		// Get all items from the order.
		$items = $order->get_items();

		// Loop through each item in the order.
		foreach ( $items as $item ) {
			// Move the order's uploaded files out of the temporary folder.
			$cart_item_prad_selection = $item->get_meta( 'cart_item_prad_selection' );
			if ( ! empty( $cart_item_prad_selection['extra_data'] ) ) {

				foreach ( $cart_item_prad_selection['extra_data'] as $val ) {
					if ( ! isset( $val['prad_additional'] ) ) {
						continue;
					}
					if ( 'upload' === $val['prad_additional']['type'] ) {
						$result = $this->process_upload_field_on_checkout( $val, 'order_placed' );
						$item->update_meta_data( $val['name'], $result['value'] );
					} elseif ( 'section' === $val['prad_additional']['type'] ) {
						$result = $this->process_section_field_on_checkout( $val, 'order_placed' );
						$item->update_meta_data( $val['name'], $result['value'] );
					}
				}
			}

			$item->save();
		}
	}

	/**
	 * Display and enqueue custom assets for the "View Order" page in My Account.
	 *
	 * Retrieves the WooCommerce order by ID and checks for the custom meta field
	 * `_prad_option_ids`. If the meta exists and is not empty, enqueue the
	 * required CSS and JavaScript files for displaying custom order details.
	 *
	 * @param int $order_id The ID of the WooCommerce order being viewed.
	 *
	 * @return void
	 */
	public function prad_custom_view_order_fields( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$custom_note = $order->get_meta( '_prad_option_ids' );

		if ( ! empty( $custom_note ) ) {
			product_addons()->enqueue_style( 'prad-cart-style', 'wowcart' );

			$cart_asset = product_addons()->get_script_asset( 'assets/js/wowcart.js', array( 'jquery' ) );
			wp_enqueue_script( 'prad-cart-script', PRAD_URL . 'assets/js/wowcart.js', $cart_asset['dependencies'], $cart_asset['version'], true );
			wp_set_script_translations( 'prad-cart-script', 'product-addons', PRAD_PATH . 'languages/' );
		}
	}

	/**
	 * WooCommerce create order line item
	 *
	 * @param object    $item Item Data.
	 * @param string    $cart_item_key Cart Item Key.
	 * @param array     $cart_item Cart Item.
	 * @param \WC_Order $order Order.
	 * @return void
	 */
	public function woocommerce_checkout_create_order_line_item( $item, $cart_item_key, $cart_item, $order ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $order required by the woocommerce_checkout_create_order_line_item hook signature.

		// Add Option Ids applied.
		if ( ! empty( $cart_item['prad_option_published_ids'] ) ) {
			$item->add_meta_data( '_prad_option_ids', $cart_item['prad_option_published_ids'] );
		}

		// Add Option Selections.
		if ( isset( $cart_item['prad_selection'] ) ) {
			$item->add_meta_data( 'cart_item_prad_selection', $cart_item['prad_selection'] );
		}

		if ( ! empty( $cart_item['prad_selection']['extra_data'] ) ) {
			$prad_uploads_path = array();

			foreach ( $cart_item['prad_selection']['extra_data'] as $val ) {
				if ( ! isset( $val['prad_additional'] ) ) {
					$item->add_meta_data( $val['name'], $val['value'] );
					continue;
				}
				if ( 'upload' === $val['prad_additional']['type'] ) {
					$result            = $this->process_upload_field_on_checkout( $val, 'temp' );
					$prad_uploads_path = array_merge( $prad_uploads_path, $result['paths'] );
					$item->add_meta_data( $val['name'], $result['value'] );
				} elseif ( 'section' === $val['prad_additional']['type'] ) {
					$result            = $this->process_section_field_on_checkout( $val, 'temp' );
					$prad_uploads_path = array_merge( $prad_uploads_path, $result['paths'] );
					$item->add_meta_data( $val['name'], $result['value'] );
				} else {
					$item->add_meta_data( $val['name'], $val['value'] );
				}
			}

			// Add Upload files Path.
			if ( ! empty( $prad_uploads_path ) ) {
				$item->add_meta_data( '_prad_option_uploads_path', $prad_uploads_path );
			}
		}

		// Add Price Data.
		if ( isset( $cart_item['prad_selection']['price_data'] ) ) {
			$item->add_meta_data( '_prad_option_price_data', $cart_item['prad_selection']['price_data'] );
		}
	}

	/**
	 * Perform action after create order on WooCommerce
	 *
	 * @since 1.0.0
	 *
	 * @param W\C_Order $order Order.
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
			$option_ids             = $item->get_meta( '_prad_option_ids' );
			$prad_option_price_data = $item->get_meta( '_prad_option_price_data' );

			if ( $option_ids ) {
				$order->update_meta_data( '_prad_option_ids', $option_ids );
				$option_ids = (array) $option_ids;
				$data       = array_unique( array_merge( $data, $option_ids ) );
				if ( $prad_option_price_data ) {
					foreach ( $option_ids as $opt_id ) {
						if ( isset( $prad_option_price_data[ $opt_id ] ) ) {
							do_action( 'prad_update_stats_table_data', $opt_id, 'sales', $prad_option_price_data[ $opt_id ] );
						}
					}
				}
			}

			// Add Order Item Meta & Handle Upload paths.
			$cart_item_prad_selection = $item->get_meta( 'cart_item_prad_selection' );
			if ( ! empty( $cart_item_prad_selection['extra_data_Depricated'] ) ) {
				$prad_uploads_path = array();

				foreach ( $cart_item_prad_selection['extra_data'] as $val ) {
					if ( ! isset( $val['prad_additional'] ) ) {
						$item->add_meta_data( $val['name'], $val['value'] );
						continue;
					}
					if ( 'upload' === $val['prad_additional']['type'] ) {
						$result            = $this->process_upload_field_on_checkout( $val, 'temp' );
						$prad_uploads_path = array_merge( $prad_uploads_path, $result['paths'] );
						$item->add_meta_data( $val['name'], $result['value'] );
					} elseif ( 'section' === $val['prad_additional']['type'] ) {
						$result            = $this->process_section_field_on_checkout( $val, 'temp' );
						$prad_uploads_path = array_merge( $prad_uploads_path, $result['paths'] );
						$item->add_meta_data( $val['name'], $result['value'] );
					} else {
						$item->add_meta_data( $val['name'], $val['value'] );
					}
				}

				// Add Upload Paths to Item Meta.
				if ( ! empty( $prad_uploads_path ) ) {
					$item->add_meta_data( '_prad_option_uploads_path', $prad_uploads_path );
				}
			}

			$item->save();
		}

		if ( ! empty( $data ) ) {
			$order->save();
			foreach ( $data as $campaign_id ) {
				do_action( 'prad_update_stats_table_data', $campaign_id, 'order_count', '' );
			}
		}
	}

	/**
	 * Move upload field files and build their display HTML.
	 *
	 * @param array  $val        The extra_data entry for an upload field.
	 * @param string $move_stage File move stage: 'temp' or 'order_placed'.
	 * @param bool   $add_price  Whether to include this field's price contribution.
	 * @return array { value: string, paths: string[] }
	 */
	private function process_upload_field_on_checkout( array $val, string $move_stage, bool $add_price = true ): array {
		$is_order_placed = 'order_placed' === $move_stage;
		$changed_value   = $val['value'];
		$collected_paths = array();

		if ( isset( $val['prad_additional']['field_raw'] ) ) {
			$field = $val['prad_additional']['field_raw'];
			$res   = '';
			if ( ! empty( $field['value'] ) && is_array( $field['value'] ) ) {
				$res = '<span>';
				foreach ( $field['value'] as $prad_item ) {
					$changed_path = $prad_item['path'];
					$changed_name = $prad_item['name'];
					$moved_data   = product_addons()->prad_move_uploadblock_files( array( $prad_item['path'] ), $move_stage, $is_order_placed );
					if ( ! empty( $moved_data[0]['updated_src'] ) ) {
						$changed_path = $moved_data[0]['updated_src']['curr_src'];
						$changed_name = $moved_data[0]['updated_src']['curr_name'];
						if ( ! $is_order_placed ) {
							$collected_paths[] = $changed_path;
						}
					}
					$res .= \wp_kses( '<a href="' . \esc_url( $changed_path ) . '">' . \esc_html( $changed_name ) . '</a>&nbsp;&nbsp;', \apply_filters( 'prad_allowed_html_tags', array() ) );// phpcs:ignore
				}
				$res .= '</span>';
			}
			$price_html    = $add_price && isset( $val['prad_additional']['opt_price_with_html'] ) ? $val['prad_additional']['opt_price_with_html'] : '';
			$changed_value = $res . $price_html;
		}

		return array(
			'value' => $changed_value,
			'paths' => $collected_paths,
		);
	}

	/**
	 * Process upload fields nested inside a section/repeater entry.
	 *
	 * @param array  $val        The extra_data entry for a section field.
	 * @param string $move_stage File move stage: 'temp' or 'order_placed'.
	 * @return array { value: string, paths: string[] }
	 */
	private function process_section_field_on_checkout( array $val, string $move_stage ): array {
		$collected_paths = array();
		$changed_value   = $val['value'];

		$extra_data = isset( $val['prad_additional']['field_raw']['extra_data'] )
			? $val['prad_additional']['field_raw']['extra_data']
			: array();

		if ( empty( $extra_data ) ) {
			return array(
				'value' => $changed_value,
				'paths' => $collected_paths,
			);
		}

		$has_upload = false;
		foreach ( $extra_data as $entry ) {
			if ( isset( $entry['prad_additional']['type'] ) && 'upload' === $entry['prad_additional']['type'] ) {
				$has_upload = true;
				break;
			}
		}

		if ( ! $has_upload ) {
			return array(
				'value' => $changed_value,
				'paths' => $collected_paths,
			);
		}

		$parts = array();
		foreach ( $extra_data as $entry ) {
			$prad_additional = isset( $entry['prad_additional'] ) && is_array( $entry['prad_additional'] ) ? $entry['prad_additional'] : array();
			$entry_name      = isset( $entry['name'] ) ? $entry['name'] : '';
			$entry_type      = isset( $prad_additional['type'] ) ? $prad_additional['type'] : '';

			if ( 'upload' === $entry_type ) {
				$result          = $this->process_upload_field_on_checkout( $entry, $move_stage, false );
				$collected_paths = array_merge( $collected_paths, $result['paths'] );
				$entry_value     = $result['value'];
			} elseif ( 'custom_formula' === $entry_type || ! empty( $prad_additional['price_only'] ) ) {
				$entry_value = isset( $prad_additional['opt_price_with_html'] ) ? $prad_additional['opt_price_with_html'] : '';
			} else {
				$entry_value = isset( $entry['value'] ) ? $entry['value'] : '';
			}

			if ( '' !== $entry_name && '' !== $entry_value ) {
				$parts[] = $entry_name . ': ' . $entry_value;
			}
		}

		$price_html    = isset( $val['prad_additional']['opt_price_with_html'] ) ? $val['prad_additional']['opt_price_with_html'] : '';
		$changed_value = implode( ', ', $parts ) . ' ' . $price_html;

		return array(
			'value' => $changed_value,
			'paths' => $collected_paths,
		);
	}
}
