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
		// if ( ! has_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'woocommerce_checkout_create_order_line_item' ] ) ) {
		// add_action( 'woocommerce_checkout_create_order_line_item', [ $this, 'woocommerce_checkout_create_order_line_item' ], 10, 4 );
		// }.
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
			// Get the campaign ID from the item's meta data.
			// $prad_option_uploads_path = $item->get_meta( '_prad_option_uploads_path' );
			// if ( ! empty( $prad_option_uploads_path ) ) {
			// $moved_data = product_addons()->prad_move_uploadblock_files( $prad_option_uploads_path, 'order_placed' );
			// }.

			// starts.
			$cart_item_prad_selection = $item->get_meta( 'cart_item_prad_selection' );
			if ( ! empty( $cart_item_prad_selection['extra_data'] ) ) {

				foreach ( $cart_item_prad_selection['extra_data'] as $val ) {
					if ( isset( $val['prad_additional'] ) && 'upload' === $val['prad_additional']['type'] ) {
						$changed_value = $val['value'];
						if ( isset( $val['prad_additional']['field_raw'] ) ) {
							$field = $val['prad_additional']['field_raw'];
							if ( ! empty( $field['value'] ) && is_array( $field['value'] ) ) {
								$res = '<span>';
								foreach ( $field['value'] as $prad_item ) {
									$changed_path = $prad_item['path'];
									$changed_name = $prad_item['name'];
									$moved_data   = product_addons()->prad_move_uploadblock_files( array( $prad_item['path'] ), 'order_placed', true );
									if ( ! empty( $moved_data[0]['updated_src'] ) ) {
										$changed_path = $moved_data[0]['updated_src']['curr_src'];
										$changed_name = $moved_data[0]['updated_src']['curr_name'];
									}
									$res .= wp_kses( '<a href="' . esc_url( $changed_path ) . '">' . esc_html( $changed_name ) . '</a>&nbsp;&nbsp;', apply_filters( 'prad_allowed_html_tags', array() ) );// phpcs:ignore

								}
								$res .= '</span>';
							}
							$changed_value = isset( $val['prad_additional']['opt_price_with_html'] ) ? $res . $val['prad_additional']['opt_price_with_html'] : $res;
						}
						$item->update_meta_data( $val['name'], $changed_value );
					} else {
						// $item->add_meta_data( $val['name'], $val['value'] );
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
	public function woocommerce_checkout_create_order_line_item( $item, $cart_item_key, $cart_item, $order ) {

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
				if ( isset( $val['prad_additional'] ) && 'upload' === $val['prad_additional']['type'] ) {
					$changed_value = $val['value'];
					if ( isset( $val['prad_additional']['field_raw'] ) ) {
						$field = $val['prad_additional']['field_raw'];
						if ( ! empty( $field['value'] ) && is_array( $field['value'] ) ) {
							$res = '<span>';
							foreach ( $field['value'] as $prad_item ) {
								$changed_path = $prad_item['path'];
								$changed_name = $prad_item['name'];
								$moved_data   = product_addons()->prad_move_uploadblock_files( array( $prad_item['path'] ), 'temp' );
								if ( ! empty( $moved_data[0]['updated_src'] ) ) {
									$changed_path        = $moved_data[0]['updated_src']['curr_src'];
									$changed_name        = $moved_data[0]['updated_src']['curr_name'];
									$prad_uploads_path[] = $changed_path;
								}
								$res .= wp_kses( '<a href="' . esc_url( $changed_path ) . '">' . esc_html( $changed_name ) . '</a>&nbsp;&nbsp;', apply_filters( 'prad_allowed_html_tags', array() ) );// phpcs:ignore
							}
							$res .= '</span>';
						}
						$changed_value = isset( $val['prad_additional']['opt_price_with_html'] ) ? $res . $val['prad_additional']['opt_price_with_html'] : $res;
					}
					$item->add_meta_data( $val['name'], $changed_value );
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
					if ( isset( $val['prad_additional'] ) && 'upload' === $val['prad_additional']['type'] ) {
						$changed_value = $val['value'];
						if ( isset( $val['prad_additional']['field_raw'] ) ) {
							$field = $val['prad_additional']['field_raw'];
							if ( ! empty( $field['value'] ) && is_array( $field['value'] ) ) {
								$res = '<span>';
								foreach ( $field['value'] as $prad_item ) {
									$changed_path = $prad_item['path'];
									$changed_name = $prad_item['name'];
									$moved_data   = product_addons()->prad_move_uploadblock_files( array( $prad_item['path'] ), 'temp' );
									if ( ! empty( $moved_data[0]['updated_src'] ) ) {
										$changed_path        = $moved_data[0]['updated_src']['curr_src'];
										$changed_name        = $moved_data[0]['updated_src']['curr_name'];
										$prad_uploads_path[] = $changed_path;
									}
									$res .= wp_kses( '<a href="' . esc_url( $changed_path ) . '">' . esc_html( $changed_name ) . '</a>&nbsp;&nbsp;', apply_filters( 'prad_allowed_html_tags', array() ) );// phpcs:ignore

								}
								$res .= '</span>';
							}
							$changed_value = isset( $val['prad_additional']['opt_price_with_html'] ) ? $res . $val['prad_additional']['opt_price_with_html'] : $res;
						}
						$item->add_meta_data( $val['name'], $changed_value );
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
				// do_action( 'prad_update_stats_table_data', $campaign_id, 'sales', $order->get_total() );.
			}
		}
	}
}
