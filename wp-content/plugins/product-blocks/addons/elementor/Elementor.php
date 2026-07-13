<?php
defined( 'ABSPATH' ) || exit;

final class Elementor_WOPB_Extension {

	private static $_instance = null;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'widget_scripts' ) );
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'widget_styles' ) );
		add_action( 'elementor/frontend/before_enqueue_scripts', array( $this, 'wopb_gutenberg_block_support' ) );
	}

	public function widget_styles() {
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'wopb-slick-style', WOPB_URL . 'assets/css/slick.css', array(), WOPB_VER );
		wp_enqueue_style( 'wopb-slick-theme-style', WOPB_URL . 'assets/css/slick-theme.css', array(), WOPB_VER );
		if ( is_rtl() ) {
			wp_enqueue_style( 'wopb-blocks-rtl-css', WOPB_URL . 'assets/css/rtl.css', array(), WOPB_VER );
		}
		wp_enqueue_style( 'wopb-style', WOPB_URL . 'assets/css/blocks.style.css', array(), WOPB_VER );
		wp_enqueue_style( 'wopb-css', WOPB_URL . 'assets/css/wopb.css', array(), WOPB_VER );
	}

	public function widget_scripts() {
		global $post;
		wp_enqueue_script( 'wopb-slick-script', WOPB_URL . 'assets/js/slick.min.js', array( 'jquery' ), WOPB_VER, true );
		wp_enqueue_script( 'wopb-flexmenu-script', WOPB_URL . 'assets/js/flexmenu.min.js', array( 'jquery' ), WOPB_VER, true );
		if ( has_block( 'product-blocks/cart-total', $post ) ) {
			wp_enqueue_script( 'wc-cart' );
		}
		if ( has_block( 'product-blocks/checkout-order-review', $post ) ) {
			wp_enqueue_script( 'wc-checkout' );
		}
		wp_enqueue_script( 'wopb-script', WOPB_URL . 'assets/js/wopb.js', array( 'jquery', 'wopb-flexmenu-script', 'wp-api-fetch' ), WOPB_VER, true );
		$wopb_core_localize = array(
			'url'               => WOPB_URL,
			'ajax'              => admin_url( 'admin-ajax.php' ),
			'security'          => wp_create_nonce( 'wopb-nonce' ),
			'currency_symbol'   => get_woocommerce_currency_symbol(),
			'currency_position' => get_option( 'woocommerce_currency_pos' ),
			'errorElementGroup' => array(
				'errorElement' => '<div class="wopb-error-element"></div>',
			),
			'taxonomyCatUrl'    => admin_url( 'edit-tags.php?taxonomy=category' ),
		);
		$wopb_core_localize = array_merge( $wopb_core_localize, wopb_function()->get_endpoint_urls() );
		wp_localize_script( 'wopb-script', 'wopb_core', $wopb_core_localize );
	}

	public function includes() {
		require_once WOPB_PATH . 'addons/elementor/Elementor_Widget.php';
	}

	public function register_widgets() {
		$this->includes();
		\Elementor\Plugin::instance()->widgets_manager->register( new \ProductX_Widget() );
	}

	/**
	 * WowStore Builder and elementor  gutenberg  block compatibility
	 *
	 * @since v.4.2.4
	 */
	public function wopb_gutenberg_block_support() {
		$is_build_with_elementor  = ( \Elementor\Plugin::$instance->db->is_built_with_elementor( get_the_id() ) );
		$is_header_footer_builder = wopb_function()->conditions( 'header' ) || wopb_function()->conditions( 'footer' );
		if ( $is_build_with_elementor && $is_header_footer_builder ) {
			wp_enqueue_style( 'wp-block-library' );
		}
	}
}
