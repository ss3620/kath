<?php
/**
 * Cart Reserved Timer Addons Core.
 *
 * @package WOPB\CartReserved
 * @since v.3.2.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * CartReserved class.
 */
class CartReserved {

	/**
	 * Setup class.
	 *
	 * @since v.3.2.0
	 */
	public function __construct() {
		add_action( 'wp', array( $this, 'cart_reserved_action' ) ); // Cart Reserved Timer Action
		add_action( 'woocommerce_add_to_cart', array( $this, 'set_cookie_after_add_cart' ), 10, 6 ); // Set The Cookie For Every Add to Cart Button Trigger
		add_action( 'template_redirect', array( $this, 'custom_clear_cart' ) ); // Reload The Same Page For Clear the Cart Page
		add_action( 'wopb_save_settings', array( $this, 'generate_css' ), 10, 1 ); // CSS Generator

		// Set Cookie If By Default Cookie Are Not Set
		if ( ! isset( $_COOKIE['wopb_cart_reserved_timer'] ) && wopb_function()->get_setting( 'cart_reserved_time' ) ) {
			$this->set_cookie_after_add_cart();
		}
	}

	/**
	 * Each Add To Cart Set the Cookies
	 *
	 * @return void
	 * @since v.3.2.0
	 */
	public function set_cookie_after_add_cart() {
		if ( ! headers_sent() ) {
			setcookie( 'wopb_cart_reserved_timer', round( microtime( true ) * 1000 ), time() + ( (int) ( wopb_function()->get_setting( 'cart_reserved_time' ) ) * 60 ), '/' );
		} else {
			trigger_error( 'Reserved Timer Cookies not set for headers request sending issue', E_USER_WARNING );
		}
	}

	/**
	 * Cart Reserved Timer Action
	 *
	 * @return void
	 * @since v.3.2.0
	 */
	public function cart_reserved_action() {
		if ( wc_get_page_id( 'cart' ) == get_the_ID() ) {
			if ( ! WC()->cart->is_empty() && wopb_function()->get_setting( 'wopb_cart_reserved' ) == 'true' ) {
				add_filter( 'productx_common_script', '__return_true' );
			}
			if ( wc_post_content_has_shortcode( 'woocommerce_cart' ) || wopb_function()->is_builder() ) {
				add_action( 'woocommerce_before_cart_contents', array( $this, 'cart_content_hook' ) );
			} else {
				add_filter( 'the_content', array( $this, 'the_content_hook' ) );
			}
		}
	}

	/**
	 * Cart Reserved Timer Content Hook
	 *
	 * @return string
	 * @since v.3.2.0
	 */
	public function the_content_hook( $content ) {
		return $this->content( $content );
	}

	/**
	 * Cart Reserved Timer Before Cart Content Hook
	 *
	 * @return void
	 * @since v.3.2.0
	 */
	public function cart_content_hook() {
		echo $this->content();  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Reload The Same Page For Clear the Cart Page
	 *
	 * @return void
	 * @since v.3.2.0
	 */
	public function custom_clear_cart() {
		if ( is_cart() && isset( $_GET['wopb-cart-clear'] ) ) {  // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			WC()->cart->empty_cart();
			wp_redirect( get_permalink() );
			exit;
		}
	}

	/**
	 * Cart Reserved Timer Custom Message
	 *
	 * @param $content
	 * @return string
	 * @since v.3.2.0
	 */
	public function content( $content = '' ) {
		$reserved_time = wopb_function()->get_setting( 'cart_reserved_time' );
		if (
			WC()->cart->is_empty() ||
			! $reserved_time ||
			( isset( $_COOKIE['wopb_cart_reserved_timer'] ) && $_COOKIE['wopb_cart_reserved_timer'] == '0' )
		) {
			return $content;
		}
		if ( $reserved_time ) {
			$countTime = ( isset( $_COOKIE['wopb_cart_reserved_timer'] ) ? (int) sanitize_text_field( $_COOKIE['wopb_cart_reserved_timer'] ) : 0 ) + ( $reserved_time * 60000 );
			$distance  = $countTime - ( time() * 1000 );
			$hours     = floor( ( $distance % ( 1000 * 60 * 60 * 24 ) ) / ( 1000 * 60 * 60 ) );
			$minutes   = floor( ( $distance % ( 1000 * 60 * 60 ) ) / ( 1000 * 60 ) );
			$seconds   = floor( ( $distance % ( 1000 * 60 ) ) / 1000 );

			$timer_message       = explode( '{time}', wopb_function()->get_setting( 'cart_reserved_time_msg' ) );
			$cart_reversed_icons = wopb_function()->get_setting( 'cart_reserved_icon' );
			$cart_icon           = array(
				'fire'   => '🔥',
				'watch'  => '⏱️',
				'bell'   => '🔔',
				'timer'  => '⏳',
				'rocket' => '🚀',
				'bomb'   => '🧨',
				'blast'  => '💥',
			);

			$html          = '<div class="wopb-cart-reserved" data-expire="' . $reserved_time . '" data-hides="' . wopb_function()->get_setting( 'cart_reserved_expire' ) . '">';
				$html     .= '<div class="wopb-cart-reserved-icon"> ' . $cart_icon[ $cart_reversed_icons ] . ' </div>';
				$html     .= '<div class="wopb-cart-reserved-content">';
					$html .= '<div class="wopb-cart-reserved-message">' . wopb_function()->get_setting( 'cart_reserved_msg' ) . '</div>';
					$html .= '<div class="wopb-cart-reserved-timer"> ' . ( isset( $timer_message[0] ) ? $timer_message[0] : '' ) . ' <div class="wopb-cart-time">' . ( $hours > 0 ? ( $hours > 9 ? $hours . ':' : '0' . $hours . ':' ) : '' ) . ( $minutes > 9 ? $minutes : '0' . $minutes ) . ':' . ( $seconds > 9 ? $seconds : '0' . $seconds ) . '</div> ' . ( isset( $timer_message[1] ) ? $timer_message[1] : '' ) . ' </div>';
				$html     .= '</div>';
			$html         .= '</div>';
			return $html . $content;
		}
	}

	/**
	 * CSS Generator
	 *
	 * @param $key
	 * @return void
	 * @since v.3.2.0
	 */
	public function generate_css( $key ) {
		if ( $key == 'wopb_cart_reserved' && method_exists( wopb_function(), 'convert_css' ) ) {
			$settings = wopb_function()->get_setting();
			$css      = '.wopb-cart-reserved {';
				$css .= 'background-color: ' . ( ! empty( $settings['cart_reserved_bg']['bg'] ) ? $settings['cart_reserved_bg']['bg'] : '#e2e2e2' ) . ';';
				$css .= isset( $settings['cart_reserved_padding'] ) ? 'padding: ' . $settings['cart_reserved_padding'] . 'px;' : '';
				$css .= 'border: ' .
					( ! empty( $settings['cart_reserved_border']['border'] )
						? $settings['cart_reserved_border']['border']
						: 0
					) . 'px dashed ' .
					( ! empty( $settings['cart_reserved_border']['color'] )
						? $settings['cart_reserved_border']['color']
						: ''
					) . ';';
				$css .= ! empty( $settings['cart_reserved_radius'] ) ? 'border-radius: ' . $settings['cart_reserved_radius'] . 'px;' : '';
			$css     .= '}';
			$css     .= '.wopb-cart-reserved:hover {';
				$css .= ! empty( $settings['cart_reserved_bg']['hover_bg'] ) ? 'background-color: ' . $settings['cart_reserved_bg']['hover_bg'] . ';' : '';
			$css     .= '}';

			$css     .= '.wopb-cart-reserved-message {';
				$css .= wopb_function()->convert_css( 'general', $settings['cart_reserved_msg_typo'] );
			$css     .= '}';
			$css     .= '.wopb-cart-reserved-message:hover {';
				$css .= wopb_function()->convert_css( 'hover', $settings['cart_reserved_msg_typo'] );
			$css     .= '}';

			$css     .= '.wopb-cart-reserved-timer {';
				$css .= wopb_function()->convert_css( 'general', $settings['cart_reserved_typo'] );
			$css     .= '}';
			$css     .= '.wopb-cart-reserved-timer:hover {';
				$css .= wopb_function()->convert_css( 'hover', $settings['cart_reserved_typo'] );
			$css     .= '}';
			$css     .= '.wopb-cart-time {';
				$css .= isset( $settings['cart_reserved_timer_color']['color'] ) ? 'color: ' . $settings['cart_reserved_timer_color']['color'] . ';' : '';
			$css     .= '}';
			$css     .= '.wopb-cart-time:hover {';
				$css .= isset( $settings['cart_reserved_timer_color']['hover_color'] ) ? 'color: ' . $settings['cart_reserved_timer_color']['hover_color'] . ';' : '';
			$css     .= '}';
			$css     .= '.wopb-cart-reserved-icon {';
				$css .= 'font-size: ' . ( ! empty( $settings['cart_reserved_icon_size'] ) ? $settings['cart_reserved_icon_size'] : 25 ) . 'px;';
			$css     .= '}';

			wopb_function()->update_css( $key, 'add', $css );
		}
	}
}
