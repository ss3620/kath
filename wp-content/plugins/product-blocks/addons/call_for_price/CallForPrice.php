<?php
/**
 * Call for Price Addons Core.
 *
 * @package WOPB\Call for Price
 * @since v.1.0.8
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * Call for Price class.
 */
class CallForPrices {

	/**
	 * Setup class.
	 *
	 * @since v.1.0.8
	 */
	public function __construct() {
		add_action( 'wp_head', array( $this, 'single_loop_product_callback' ) );
		if ( wopb_function()->get_setting( 'call_on_shop' ) == 'yes' ) {
			add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'add_to_cart_button' ), 10, 3 );
		}
		add_filter( 'woocommerce_is_purchasable', array( $this, 'product_purchasable' ), 10, 2 ); // Purchasable false for Empty Price products

		// CSS Generator
		add_action( 'wopb_save_settings', array( $this, 'generate_css' ), 10, 1 );
	}

	/**
	 * Product Purchasable or Not Checking
	 *
	 * @since v.1.3.8
	 * @return NULL
	 */
	public function product_purchasable( $value, $product ) {
		if (
			( ! $product->get_price() ) ||
			( wopb_function()->get_setting( 'call_all_product' ) == 'yes' ) ||
			( wopb_function()->get_setting( 'call_out_stock' ) == 'yes' && ! $product->is_in_stock() )
		) {
			return false;
		}
		return $value;
	}

	/**
	 * Check Single and Looped Product
	 *
	 * @since v.1.3.8
	 * @return NULL
	 */
	public function single_loop_product_callback() {
		if ( is_product() ) {
			$btn_position = wopb_function()->get_setting( 'call_btn_position' );
			if ( $btn_position == 'description_bottom' ) {
				add_filter(
					'woocommerce_short_description',
					function ( $content ) {
						ob_start();
						$this->price_condition_callback();
						return $content . ob_get_clean();
					},
					10,
					1
				);
			} elseif ( $btn_position == 'meta_bottom' ) {
				add_filter( 'woocommerce_product_meta_end', array( $this, 'price_condition_callback' ) );
			} elseif ( $btn_position == 'title_bottom' ) {
				add_action( 'wopb_after_single_product_title', array( $this, 'price_condition_callback' ), 6 );
				add_action( 'woocommerce_single_product_summary', array( $this, 'price_condition_callback' ), 6 );
			}
		}
	}

	/**
	 * Call for Price Addons Initial Setup Action
	 *
	 * @since v.1.3.8
	 * @return NULL
	 */
	public function price_condition_callback( $price = '', $product = '' ) {
		if ( gettype( $product ) != 'object' ) {
			$product = wc_get_product( get_the_ID() );
		}
		if (
			! $product->get_price()
			|| wopb_function()->get_setting( 'call_all_product' ) == 'yes'
			|| ( wopb_function()->get_setting( 'call_out_stock' ) == 'yes' && ! $product->is_in_stock() )
		) {
			$html = $this->product_price_html( $product );
			if ( is_product() ) {
				echo $html;
			} else {
				return $html;
			}
		}
		return $price;
	}

	/**
	 * Call for Price Addons Initial Setup Action
	 *
	 * @since v.1.0.8
	 * @return NULL
	 */
	public function initial_setup() {
		$settings     = wopb_function()->get_setting();
		$initial_data = array(
			// Start Setting Attributes
			'call_type'               => '',
			'call_link'               => '',
			'call_for_price_text'     => __( 'Call for Price', 'product-blocks' ),
			'call_price_icon_enable'  => 'yes',
			'call_icon_position'      => 'before',
			'call_btn_position'       => 'title_bottom',
			'call_all_product'        => '',
			'call_out_stock'          => '',
			'call_on_shop'            => 'yes',
			// End Setting Attributes

			// Start Design Attributes
			// Typography
			'call_btn_typo_single'    => array(
				'size'        => 16,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => '#000000',
				'hover_color' => '',
			),
			'call_btn_bg_single'      => array(
				'bg'       => '#FFDC5E',
				'hover_bg' => '',
			),
			'call_icon_color_single'  => array(
				'color'       => '#000000',
				'hover_color' => '',
			),
			'call_btn_padding_single' => array(
				'top'    => 10,
				'bottom' => 10,
				'left'   => 16,
				'right'  => 16,
			),
			'call_btn_margin_single'  => array(
				'top'    => 5,
				'bottom' => 5,
				'left'   => 0,
				'right'  => 0,
			),
			'call_btn_border_single'  => array(
				'border' => 0,
				'color'  => '',
			),
			'call_btn_radius'         => 4,
			'call_icon_size_single'   => 16,

			'call_btn_typo_shop'      => array(
				'size'        => 16,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => '#000000',
				'hover_color' => '',
			),
			'call_btn_bg_shop'        => array(
				'bg'       => '#FFDC5E',
				'hover_bg' => '',
			),
			'call_icon_color_shop'    => array(
				'color'       => '#000000',
				'hover_color' => '',
			),
			'call_btn_padding_shop'   => array(
				'top'    => 10,
				'bottom' => 10,
				'left'   => 16,
				'right'  => 16,
			),
			'call_btn_margin_shop'    => array(
				'top'    => 0,
				'bottom' => 0,
				'left'   => 0,
				'right'  => 0,
			),
			'call_btn_border_shop'    => array(
				'border' => 0,
				'color'  => '',
			),
			'call_btn_radius_shop'    => 4,
			'call_icon_size_shop'     => 16,
			// End Design Attributes
		);
		foreach ( $initial_data as $key => $val ) {
			if ( ! isset( $settings[ $key ] ) ) {
				wopb_function()->set_setting( $key, $val );
			}
		}
		$this->generate_css( 'wopb_call_for_price' );
	}

	/**
	 * Call for Price Addons HTML Callback
	 *
	 * @since v.1.0.8
	 * @return STRING
	 */
	public function product_price_html( $product ) {
		$link      = '';
		$icon      = '';
		$target    = '_self';
		$call_link = wopb_function()->get_setting( 'call_link' );
		$call_type = wopb_function()->get_setting( 'call_type' );

		switch ( $call_type ) {
			case 'phone':
				$link = 'tel:' . $call_link;
				break;
			case 'skype':
				$link = 'skype:' . $call_link . '?call';
				break;
			case 'whatsapp':
                $link = 'https://api.whatsapp.com/send?phone=' . $call_link . '&text=' . esc_url( home_url( $_SERVER['REQUEST_URI'] ) ); //phpcs:ignore
				$target = '_blank';
				break;
			case 'email':
				$link = 'mailto:' . $call_link;
				break;
			case 'link':
				$link   = $call_link;
				$target = '_blank';
				break;
			default:
				break;
		}

		if ( wopb_function()->get_setting( 'call_type' ) && wopb_function()->get_setting( 'call_price_icon_enable' ) == 'yes' ) {
			$icon = $this->icons( wopb_function()->get_setting( 'call_type' ) );
		}

		if ( $link ) {
			$button_class = 'wopb-call-to-action';
			if ( is_product() ) {
				$button_class .= ' wopb-call-price-single-btn';
			} else {
				$button_class .= ' wopb-call-price-shop-btn';
			}
			$html = '<a target="' . $target . '" class="' . $button_class . '" href="' . $link . '">';
			if ( wopb_function()->get_setting( 'call_icon_position' ) == 'before' ) {
				$html .= $icon;
			}
			$html .= wopb_function()->get_setting( 'call_for_price_text' );
			if ( wopb_function()->get_setting( 'call_icon_position' ) == 'after' ) {
				$html .= $icon;
			}
			$html .= '</a>';
			return $html;
		}
	}

	/**
	 * Call for Price Button Replace to Add to Cart Button
	 *
	 * @param $add_to_cart_html
	 * @param $product
	 * @param $args
	 * @return STRING
	 * @since v.4.0.0
	 */
	public function add_to_cart_button( $add_to_cart_html, $product, $args ) {
		ob_start();
		echo $this->price_condition_callback( '', $product );
		$call_html = ob_get_clean();
		return $call_html ? $call_html : $add_to_cart_html;
	}

	/**
	 * Icon List
	 *
	 * @since v.1.3.8
	 * @return array
	 */
	public function icons( $key = '' ) {
		$icon = array(
			'phone'    => 'phone.svg',
			'skype'    => 'skype.svg',
			'whatsapp' => 'whatsapp.svg',
			'email'    => 'mail.svg',
			'link'     => 'link.svg',
		);
		if ( $key && isset( $icon[ $key ] ) ) {
			$svg_path = WOPB_PATH . 'assets/img/addons/call_for_price/' . $icon[ $key ];
			if ( file_exists( $svg_path ) ) {
				return file_get_contents( $svg_path );
			}
		}
		return '';
	}

	/**
	 * CSS Generator
	 *
	 * @since v.4.0.0
	 * @return void
	 */
	public function generate_css( $key ) {

		if ( $key == 'wopb_call_for_price' && method_exists( wopb_function(), 'convert_css' ) ) {
			$settings         = wopb_function()->get_setting();
			$single_btn_style = array_merge( $settings['call_btn_typo_single'], $settings['call_btn_bg_single'] );
			$shop_btn_style   = array_merge( $settings['call_btn_typo_shop'], $settings['call_btn_bg_shop'] );

			/* Single product page button style */
			$css      = '.wopb-call-to-action.wopb-call-price-single-btn{';
				$css .= wopb_function()->convert_css( 'general', $single_btn_style );
				$css .= wopb_function()->convert_css( 'border', $settings['call_btn_border_single'] );
				$css .= wopb_function()->convert_css( 'radius', $settings['call_btn_radius'] );
				$css .= 'padding: ' . wopb_function()->convert_css( 'dimension', $settings['call_btn_padding_single'] ) . ';';
				$css .= 'margin: ' . wopb_function()->convert_css( 'dimension', $settings['call_btn_margin_single'] ) . ';';
			$css     .= '}';
			$css     .= '.wopb-call-to-action.wopb-call-price-single-btn:hover{';
				$css .= wopb_function()->convert_css( 'hover', $single_btn_style );
			$css     .= '}';
			$css     .= '.wopb-call-to-action.wopb-call-price-single-btn svg{';
				$css .= 'height: ' . ( ! empty( $settings['call_icon_size_single'] ) ? $settings['call_icon_size_single'] : '16' ) . 'px;';
				$css .= 'width: ' . ( ! empty( $settings['call_icon_size_single'] ) ? $settings['call_icon_size_single'] : '16' ) . 'px;';
			$css     .= '}';
			$css     .= '.wopb-call-to-action.wopb-call-price-single-btn svg path{';
				$css .= 'fill: ' . ( ! empty( $settings['call_icon_color_single']['color'] ) ? $settings['call_icon_color_single']['color'] : '#5A5A5A' ) . ';';
			$css     .= '}';
			$css     .= '.wopb-call-to-action.wopb-call-price-single-btn:hover svg path{';
				$css .= 'fill: ' . ( ! empty( $settings['call_icon_color_single']['hover_color'] ) ? $settings['call_icon_color_single']['hover_color'] : '#5A5A5A' ) . ';';
			$css     .= '}';
			/* Single product page button style */

			/* Shop page button style */
			$css     .= '.wopb-call-to-action.wopb-call-price-shop-btn{';
				$css .= wopb_function()->convert_css( 'general', $shop_btn_style );
				$css .= wopb_function()->convert_css( 'border', $settings['call_btn_border_shop'] );
				$css .= wopb_function()->convert_css( 'radius', $settings['call_btn_radius'] );
				$css .= 'padding: ' . wopb_function()->convert_css( 'dimension', $settings['call_btn_padding_shop'] ) . ';';
				$css .= 'margin: ' . wopb_function()->convert_css( 'dimension', $settings['call_btn_margin_shop'] ) . ';';
			$css     .= '}';
				$css .= '.wopb-call-to-action.wopb-call-price-shop-btn:hover{';
				$css .= wopb_function()->convert_css( 'hover', $shop_btn_style );
			$css     .= '}';
				$css .= '.wopb-call-to-action.wopb-call-price-shop-btn svg{';
				$css .= 'height: ' . ( ! empty( $settings['call_icon_size_shop'] ) ? $settings['call_icon_size_shop'] : '16' ) . 'px;';
				$css .= 'width: ' . ( ! empty( $settings['call_icon_size_shop'] ) ? $settings['call_icon_size_shop'] : '16' ) . 'px;';
			$css     .= '}';
			$css     .= '.wopb-call-to-action.wopb-call-price-shop-btn svg path{';
				$css .= 'fill: ' . ( ! empty( $settings['call_icon_color_shop']['color'] ) ? $settings['call_icon_color_shop']['color'] : '#5A5A5A' ) . ';';
			$css     .= '}';
			$css     .= '.wopb-call-to-action.wopb-call-price-shop-btn:hover svg path{';
				$css .= 'fill: ' . ( ! empty( $settings['call_icon_color_shop']['hover_color'] ) ? $settings['call_icon_color_shop']['hover_color'] : '#5A5A5A' ) . ';';
			$css     .= '}';
			/* Shop page button style */

			wopb_function()->update_css( $key, 'add', $css );
		}
	}
}
