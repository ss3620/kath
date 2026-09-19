<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Free_Shipping_Progress_Bar {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'showProgress'    => true,
			'progressTop'     => false,
			'beforePriceText' => 'Add',
			'afterPriceText'  => 'to cart and get Free shipping!',
			'freeShipText'    => 'You have free shipping!',
			'currentPostId'   => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/free-shipping-progress-bar',
			array(
				'editor_script'   => 'wopb-blocks-builder-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}

	public function content( $attr, $noAjax = false ) {
		$attr                      = wp_parse_args( $attr, $this->get_attributes() );
		$free_shipping_instance_id = $this->check_free_shipping();

		if ( ! empty( $free_shipping_instance_id ) ) {
			$block_name    = 'free-shipping-progress-bar';
			$wraper_before = $wraper_after = $content = '';

			if ( function_exists( 'WC' ) ) {
				$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';

				$wraper_before     .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . $block_name . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . '">';
					$wraper_before .= '<div class="wopb-product-wrapper">';
				if ( ! is_admin() ) {
					if ( isset( WC()->customer ) ) {
						ob_start();
						// if cart and customer is set the block should render normally.
						require_once WOPB_PATH . 'blocks/woo/free_shipping_progress_bar/Template.php';
						$content .= ob_get_clean();
					}
				}
					$wraper_after .= '</div> ';
				$wraper_after     .= '</div> ';
			}

			return $wraper_before . $content . $wraper_after;
		}
	}

	public function check_free_shipping() {
		if ( WC()->cart ) {
			$instance_id       = '';
			$shipping_packages = WC()->cart->get_shipping_packages();
			$shipping_zone     = wc_get_shipping_zone( reset( $shipping_packages ) );
			$zone_id           = $shipping_zone->get_id();
			$available_methods = \WC_Data_Store::load( 'shipping-zone' )->get_methods( $zone_id, '' );

			foreach ( $available_methods as $method ) {
				if ( $method->method_id == 'free_shipping' && $method->is_enabled ) {
					$instance_id = $method->instance_id;
					break;
				}
			}

			return $instance_id;
		}
	}
}
