<?php
/**
 * Sale Notification Addons Core.
 *
 * @package WOPB\SalesNotification
 * @since v.4.0.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * SaleNotification class.
 */
class SalesNotification {

	/**
	 * Setup class.
	 *
	 * @since v.4.0.0
	 */
	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'sales_notification_route' ) );
		add_filter( 'body_class', array( $this, 'sales_notification_body_class' ), 9999, 1 );
		add_action( 'wopb_save_settings', array( $this, 'generate_css' ), 10, 1 ); // CSS Generator
	}

	/**
	 * CSS Generator
	 *
	 * @param $key
	 * @return null
	 * @since v.4.0.0
	 */
	public function generate_css( $key ) {
		if ( $key == 'wopb_sales_notification' ) {
			$settings = wopb_function()->get_setting();
			$css      = '.wopb-notification-wrap { ' . $settings['sales_push_display_position'] . ': 20px; }';
			$css     .= '.wopb-notification .wopb-notification-wrap {';
				$css .= isset( $settings['sales_bg_color']['bg'] ) ? ( 'background-color: ' . $settings['sales_bg_color']['bg'] . ';' ) : '';
			$css     .= '}';
			$css     .= '.wopb-notification .wopb-notification-wrap:hover {';
				$css .= isset( $settings['sales_bg_color']['hover_bg'] ) ? ( 'background-color: ' . $settings['sales_bg_color']['hover_bg'] . ';' ) : '';
			$css     .= '}';

			$css .= '.wopb-notification-name {' . wopb_function()->convert_css( 'general', $settings['sales_name_typo'] ) . '}';
			$css .= '.wopb-notification-name:hover {' . wopb_function()->convert_css( 'hover', $settings['sales_name_typo'] ) . '}';
			$css .= '.wopb-notification-name span{' . wopb_function()->convert_css( 'general', $settings['sales_label_typo'] ) . '}';
			$css .= '.wopb-notification-name span:hover{' . wopb_function()->convert_css( 'hover', $settings['sales_label_typo'] ) . '}';

			$css .= '.wopb-notification-product {' . wopb_function()->convert_css( 'general', $settings['sales_title_typo'] ) . '}';
			$css .= '.wopb-notification-product:hover {' . wopb_function()->convert_css( 'hover', $settings['sales_title_typo'] ) . '}';

			$css .= '.wopb-notification-time {' . wopb_function()->convert_css( 'general', $settings['sales_time_typo'] ) . '}';
			$css .= '.wopb-notification-time:hover {' . wopb_function()->convert_css( 'hover', $settings['sales_time_typo'] ) . '}';

			$css .= '.wopb-notification-wrap { border-radius: ' . $settings['sales_radius'] . 'px; }';
			$css .= '.wopb-notification-wrap .wopb-img-wrap img { border-radius: ' . $settings['sales_image_radius'] . 'px; }';

			$css .= '.wopb-notification-close {' . ( isset( $settings['sales_close_bg']['bg'] ) ? ( 'background-color: ' . $settings['sales_close_bg']['bg'] . ';' ) : '' ) . '}';
			$css .= '.wopb-notification-close:hover {' . ( isset( $settings['sales_close_bg']['hover_bg'] ) ? ( 'background-color: ' . $settings['sales_close_bg']['hover_bg'] . ';' ) : '' ) . '}';
			$css .= '.wopb-notification-close::after {' . ( isset( $settings['sales_close_color']['color'] ) ? ( 'color: ' . $settings['sales_close_color']['color'] . ';' ) : '' ) . '}';
			$css .= '.wopb-notification-close:hover::after {' . ( isset( $settings['sales_close_color']['hover_color'] ) ? ( 'color: ' . $settings['sales_close_color']['hover_color'] . ';' ) : '' ) . '}';

			wopb_function()->update_css( $key, 'add', $css );
		}
	}

	/**
	 * Add Body Class for Checking
	 *
	 * @param $classes
	 * @return array
	 * @since v.4.0.0
	 */
	public function sales_notification_body_class( $classes ) {
		$classes[] = 'wopb-notification-body';
		$classes[] = 'wopb-gap-' . wopb_function()->get_setting( 'sales_gap_time' );
		$classes[] = 'wopb-stay-' . wopb_function()->get_setting( 'sales_stay_time' );
		return $classes;
	}

	/**
	 * Sale Notification
	 *
	 * @return null
	 * @since v.4.0.0
	 */
	public function sales_notification_route() {
		register_rest_route(
			'sales/v1',
			'/notification/',
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'sales_notification_action' ),
					'permission_callback' => '__return_true',
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Sale Notification
	 *
	 * @return array
	 * @since v.4.0.0
	 */
	public function sales_notification_action() {
		$page_id = isset( $_POST['page_id'] ) ? $_POST['page_id'] : '';

		// Check if notification should display on this page
		if ( ! $this->should_display_notification( $page_id ) ) {
			return array( 'html' => array() );
		}
		$output      = array();
		$number      = wopb_function()->get_setting( 'sales_number' );
		$order_types = wc_get_order_types();
		if ( ( $key = array_search( 'shop_order_refund', $order_types ) ) !== false ) {
			unset( $order_types[ $key ] );
		}

		$args   = array(
			'limit'   => $number ? $number : 10,
			'orderby' => 'date',
			'order'   => 'DESC',
			'status'  => array( 'wc-completed', 'wc-pending', 'wc-processing', 'wc-on-hold' ),
			'type'    => $order_types,
		);
		$orders = wc_get_orders( $args );

		if ( ! empty( $orders ) ) {
			$name    = wopb_function()->get_setting( 'sales_name' );
			$label   = wopb_function()->get_setting( 'sales_label' );
			$image   = wopb_function()->get_setting( 'sales_image' );
			$time    = wopb_function()->get_setting( 'sales_time' );
			$prefix  = wopb_function()->get_setting( 'sales_time_prefix' );
			$postfix = wopb_function()->get_setting( 'sales_time_postfix' );

			foreach ( $orders as $order ) {
				$product_name = $product_url = $image_url = '';
				$date         = (array) $order->get_date_created();
				$user_info    = get_userdata( $order->get_user_id() );
				$time_deff    = isset( $date['date'] ) ? human_time_diff( strtotime( $date['date'] ), time() ) : '0';
				if ( $user_info ) {
					if (
						$name == 'display' ||
						( $user_info->first_name == '' && $user_info->last_name == '' )
					) {
						$customer_name = $user_info->display_name;
					} elseif ( $name == 'user_name' && $user_info->user_login ) {
						$customer_name = $user_info->user_login;
					} elseif ( $name == 'nick_name' && $user_info->nickname ) {
						$customer_name = $user_info->nickname;
					} elseif ( $name == 'first_name' && $user_info->first_name ) {
						$customer_name = $user_info->first_name;
					} elseif ( $name == 'last_name' && $user_info->last_name ) {
						$customer_name = $user_info->last_name;
					} else {
						$customer_name = $user_info->first_name . ' ' . $user_info->last_name;
					}
				} else {
					// use billing first and last name for guest user.
					// first name and last name can be used easily based on user settings.
					// for any other case like display_name, nick_name, username, full_name show "Full Billing Name".
					$billing_first_name = $order->get_billing_first_name();
					$billing_last_name  = $order->get_billing_last_name();
					if ( 'first_name' === $name && $billing_first_name ) {
						$customer_name = $billing_first_name;
					} elseif ( 'last_name' === $name && $billing_last_name ) {
						$customer_name = $billing_last_name;
					} else {
						$customer_name = $billing_first_name . ' ' . $billing_last_name;
					}
				}

				foreach ( $order->get_items() as $item ) {
					$id           = $item->get_product_id();
					$product_name = $item->get_name();
					$product_url  = get_permalink( $id );
					$temp         = wp_get_attachment_image_src( get_post_thumbnail_id( $id ), 'thumbnail' );
					$image_url    = isset( $temp[0] ) ? $temp[0] : wc_placeholder_img_src( 'thumbnail' );
					break;
				}
				$wrap_style = 'padding: 10px';
				if ( $image != 'yes' || ! $image_url ) {
					$wrap_style = 'padding: 12px 25px';
				}

				$html          = '<div class="wopb-notification wopb-animation-notification active">';
					$html     .= '<div class="wopb-notification-wrap" style="' . wopb_function()->core_esc_wp( $wrap_style ) . '">';
						$html .= '<a href="' . esc_url( $product_url ) . '">';
				if ( $image == 'yes' && $image_url ) {
					$html     .= '<div class="wopb-img-wrap">';
						$html .= '<img src="' . esc_url( $image_url ) . '"/>';
					$html     .= '</div>';
				}
							$html     .= '<div class="wopb-col">';
								$html .= '<div class="wopb-notification-name">' . esc_html( $customer_name ) . ' ' . ( $label ? '<span>' . esc_html( $label ) . '</span>' : '' ) . '</div>';
								$html .= '<div class="wopb-notification-product">' . esc_html( $product_name ) . '</div>';
				if ( $time == 'yes' ) {
					$html .= '<div class="wopb-notification-time">' . esc_html( $prefix . ' ' . $time_deff . ' ' . $postfix ) . '</div>';
				}
							$html .= '</div>';
						$html     .= '</a>';
						$html     .= '<span class="wopb-notification-close"></span>';
					$html         .= '</div>';
				$html             .= '</div>';

				$output[] = $html;
			}
		}

		return array(
			'html' => $output,
		);
	}

	/**
	 * Check if notification should display on specific page
	 *
	 * @param int $page_id
	 * @return bool
	 * @since v.4.0.0
	 */
	public function should_display_notification( $page_id = 0 ) {
		$action = wopb_function()->get_setting( 'select_page_visibility' );

		if ( $action === 'all' ) {
			return true;
		}

		$selected_page_list = wopb_function()->get_setting( 'custom_page_selection' );

		$is_page_in_list    = is_array( $selected_page_list ) && in_array( $page_id, $selected_page_list );

		return $action === 'include' ? $is_page_in_list : ! $is_page_in_list;
	}
}
