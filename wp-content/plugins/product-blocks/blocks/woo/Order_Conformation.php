<?php
namespace WOPB\blocks;

defined( 'ABSPATH' ) || exit;

class Order_Conformation {
	public function __construct() {
		add_action( 'init', array( $this, 'register' ) );
	}

	public function get_attributes() {
		return array(
			'showHead'      => true,
			'showMessage'   => true,
			'orderHeadText' => 'Order#',
			'messageText'   => 'Thank you. Your order has been received.',
			'currentPostId' => '',
		);
	}

	public function register() {
		register_block_type(
			'product-blocks/thankyou-order-conformation',
			array(
				'editor_script'   => 'wopb-blocks-builder-script',
				'editor_style'    => 'wopb-blocks-editor-css',
				'render_callback' => array( $this, 'content' ),
			)
		);
	}


	public function content( $attr, $noAjax = false ) {
		if ( function_exists( 'WC' ) && is_checkout() && is_wc_endpoint_url( 'order-received' ) ) {
			$block_name        = 'thankyou-order-conformation';
			$wraper_before     = $wraper_after = $content = '';
			$attr              = wp_parse_args( $attr, $this->get_attributes() );
			$attr['className'] = ! empty( $attr['className'] ) ? preg_replace( '/[^A-Za-z0-9_ -]/', '', $attr['className'] ) : '';

			$wraper_before     .= '<div ' . ( isset( $attr['advanceId'] ) ? 'id="' . sanitize_html_class( $attr['advanceId'] ) . '" ' : '' ) . ' class="wp-block-product-blocks-' . $block_name . ' wopb-block-' . sanitize_html_class( $attr['blockId'] ) . ' ' . $attr['className'] . '">';
				$wraper_before .= '<div class="wopb-product-wrapper">';
			if ( ! is_admin() ) {
				if ( isset( WC()->customer ) ) {
					ob_start();
					require_once WOPB_PATH . 'blocks/woo/thank_you/order_conformation/Template.php';
					$content .= ob_get_clean();
				}
			}
				$wraper_after .= '</div>';
			$wraper_after     .= '</div>';

			return $wraper_before . $content . $wraper_after;
		}
	}
}
