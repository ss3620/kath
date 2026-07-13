<?php
/**
 * Wishlist Addons Core.
 *
 * @package WOPB\Wishlist
 * @since v.1.1.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * Wishlist class.
 */
class Wishlist {

	/**
	 * Setup class.
	 *
	 * @since v.1.1.0
	 */
	private $button        = '';
	private $browse        = '';
	private $wishlist_page = '';
	private $action_added  = '';
	private $require_login = '';

	public function __construct() {
		$this->button        = wopb_function()->get_setting( 'wishlist_button' );
		$this->browse        = wopb_function()->get_setting( 'wishlist_browse' );
		$this->wishlist_page = wopb_function()->get_setting( 'wishlist_page' );
		$this->action_added  = wopb_function()->get_setting( 'wishlist_action_added' );
		$this->require_login = wopb_function()->get_setting( 'wishlist_require_login' );
		$position            = wopb_function()->get_setting( 'wishlist_position' );

		add_action( 'wp_ajax_wopb_wishlist', array( $this, 'wopb_wishlist_callback' ) );
		add_action( 'wp_ajax_nopriv_wopb_wishlist', array( $this, 'wopb_wishlist_callback' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'add_wishlist_scripts' ) );
		add_action( 'woocommerce_add_to_cart', array( $this, 'remove_wishlist_after_add_to_cart' ), 10, 2 ); // Remove wishlist item after add to cart.

		add_shortcode( 'wopb_wishlist', array( $this, 'wishlist_shortcode_callback' ) );

		if ( wopb_function()->get_setting( 'wishlist_single_enable' ) == 'yes' ) {
			if ( wopb_function()->get_setting( 'wopb_quickview' ) == 'true' ) {
				add_filter(
					'wopb_quick_view_bottom_cart',
					function ( $content, $product_id ) {
						return $content . $this->add_wishlist_html();
					},
					11,
					2
				);
			}
			add_action( 'woocommerce_before_single_product', array( $this, 'before_single_product' ) );
		}

		if ( wopb_function()->get_setting( 'wishlist_shop_enable' ) == 'yes' ) { // wishlist in default WooCommerce pages
			if ( $position == 'before_cart' ) {
				add_filter( 'wopb_top_add_to_cart_loop', array( $this, 'wopb_show_wishlist_in_shop' ), 40, 1 );
			} elseif ( $position == 'after_cart' ) {
				add_filter( 'wopb_bottom_add_to_cart_loop', array( $this, 'wopb_show_wishlist_in_shop' ), 40, 1 );
			}
		}

		if ( $this->action_added == 'popup' ) {
			add_filter(
				'wopb_active_modal',
				function ( array $loaders ) {
					if ( ! in_array( 'loader_1', $loaders ) ) {
						$loaders[] = 'loader_1';
					}
					return $loaders;
				}
			);
		}

		add_filter( 'wopb_grid_wishlist', array( $this, 'get_grid_wishlist_html' ), 10, 3 );

		if ( wopb_function()->get_setting( 'wishlist_nav_enable' ) == 'yes' ) {
			if ( wopb_function()->get_setting( 'wishlist_nav_shortcode' ) == 'yes' ) {
				add_shortcode( 'wopb_wishlist_nav', array( $this, 'wishlist_nav_menu' ) );
			} else {
				add_filter( 'wp_nav_menu_items', array( $this, 'nav_menu_item' ), 10, 2 );
			}
		}
		add_action( 'wopb_save_settings', array( $this, 'generate_css' ), 10, 1 ); // CSS Generator
		add_filter( 'wopb_menu_wishlist_data', array( $this, 'menu_wishlist_data_callback' ), 10, 1 );
	}

	/**
	 * Return wishlist data to block
	 *
	 * @since v.4.0.0
	 * @return array
	 */
	public function menu_wishlist_data_callback( $data = array() ) {
		$wishlist_page = wopb_function()->get_setting( 'wishlist_page' );
		$action_added  = wopb_function()->get_setting( 'wishlist_action_added' );
		$action        = 'add';
		$redirect      = $wishlist_page && $action_added == 'redirect' ? ( ' data-redirect="' . esc_url( get_permalink( $wishlist_page ) ) . '"' ) : '';
		if ( ! empty( $data['action'] ) && $data['action'] == 'menu_block' ) {
			$action = 'menu_block';
		}

		return array(
			'button_attr' => '
                data-action="' . esc_attr( $action ) . '"
                data-added-action="' . esc_attr( $action_added ) . '"' .
				$redirect . '
                data-postid="' . esc_attr( ! empty( $data['post_id'] ) ? $data['post_id'] : '' ) . '"
                data-modal-loader="loader_1"
                data-modal_content_class="wopb-wishlist-wrapper"
            ',
			'w_items'     => $this->get_wishlist_id(),
		);
	}

	/**
	 * Grid Wishlist HTML Template
	 *
	 * @since v.4.0.0
	 * @return STRING
	 */
	public function get_grid_wishlist_html( $output, $post_id, $tooltipPosition = '' ) {
		$login_redirect = '';
		$user_id        = get_current_user_id();
		$current_uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		if ( $this->require_login == 'yes' && ! $user_id ) {
			$login_redirect = 'data-login-redirect="' . wp_login_url( home_url( $current_uri ) ) . '"';
		}

		$wishlist_ids  = $this->get_wishlist_id( $user_id );
		$button_class  = 'wopb_meta_svg_con wopb-wishlist-icon wopb-wishlist-add ' . ( in_array( $post_id, $wishlist_ids ) ? 'wopb-wishlist-active' : '' ) . '';
		$wishlist_data = apply_filters( 'wopb_menu_wishlist_data', array( 'post_id' => get_the_ID() ) );

		$output          = '<div ';
			$output     .= 'class="' . esc_attr( $button_class ) . '"';
			$output     .= ( ! empty( $wishlist_data['button_attr'] ) ? $wishlist_data['button_attr'] : '' ) . $login_redirect;
		$output         .= '>';
			$output     .= '<span class="wopb-tooltip-text">';
				$output .= wopb_function()->svg_icon( 'wishlist' );
				$output .= wopb_function()->svg_icon( 'wishlistFill' );
				$output .= '<span class="wopb-tooltip-text-' . esc_attr( $tooltipPosition ) . '"><span>' . esc_html( $this->button ? $this->button : 'Add To Wishlist' ) . '</span><span>' . esc_html( $this->browse ? $this->browse : 'Browse Wishlist' ) . '</span></span>';
			$output     .= '</span>';
		$output         .= '</div>';

		return $output;
	}

	/**
	 * Wishlist ID
	 *
	 * @since v.1.1.0
	 * @return ARRAY
	 */
	public function get_wishlist_id( $user_id = '' ) {
		$wishlist_data = array();
		$user_id       = $user_id ? $user_id : get_current_user_id();
		if ( isset( $_COOKIE['wopb_wishlist'] ) ) {
			$cookie_value = isset( $_COOKIE['wopb_wishlist'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['wopb_wishlist'] ) ) : '';
			$data         = json_decode( $cookie_value, true );
			if ( is_array( $data ) ) {
				return $data;
			} else {
				setcookie( 'wopb_wishlist', '', time() - 3600, '/' );
				unset( $cookie_value );
			}
		} elseif ( $this->require_login == 'yes' && $user_id ) {
			$user_data     = get_user_meta( $user_id, 'wopb_wishlist', true );
			$wishlist_data = $user_data ? $user_data : array();
		}
		return $wishlist_data;
	}

	/**
	 * Wishlist JS Script Add
	 *
	 * @since v.1.1.0
	 * @return NULL
	 */
	public function add_wishlist_scripts() {
		wp_enqueue_style( 'wopb-modal-css', WOPB_URL . 'assets/css/modal.min.css', array(), WOPB_VER );
		wp_enqueue_style( 'wopb-animation-css', WOPB_URL . 'assets/css/animation.min.css', array(), WOPB_VER );
		wp_enqueue_style( 'wopb-wishlist-style', WOPB_URL . 'addons/wishlist/css/wishlist.css', array(), WOPB_VER );
		wp_enqueue_script( 'wopb-wishlist', WOPB_URL . 'addons/wishlist/js/wishlist.js', array( 'jquery' ), WOPB_VER, true );
		wp_localize_script(
			'wopb-wishlist',
			'wopb_wishlist',
			array(
				'ajax'          => admin_url( 'admin-ajax.php' ),
				'security'      => wp_create_nonce( 'wopb-nonce' ),
				'emptyWishlist' => wopb_function()->get_setting( 'wishlist_empty' ),
			)
		);
	}

	/**
	 * Wishlist Button In Single Product Page
	 *
	 * @since v.4.1.0
	 * @return null
	 */
	public function before_single_product() {
		$filter = wopb_function()->get_setting( 'wishlist_position_single' ) == 'before_cart' ? 'wopb_top_add_to_cart' : 'wopb_bottom_add_to_cart';
		add_filter( $filter, array( $this, 'add_wishlist_html' ), 100, 1 );
	}

	/**
	 * Wishlist Action Button Shortcode
	 *
	 * @param $content
	 * @return STRING | HTML of the shortcode
	 * @since v.1.1.0
	 */
	public function add_wishlist_html( $content = '' ) {
		$wishlist_data = $this->get_wishlist_id();
		$is_product    = is_product();
		$button_class  = 'wopb-wishlist-add wopb-wishlist-addon-btn' . ( in_array( get_the_ID(), $wishlist_data ) ? ' wopb-wishlist-active' : '' );
		if ( $is_product ) {
			$this->button  = wopb_function()->get_setting( 'wishlist_button_single' );
			$this->browse  = wopb_function()->get_setting( 'wishlist_browse_single' );
			$button_class .= ' wopb-wishlist-single-btn';
		} else {
			$button_class .= ' wopb-wishlist-shop-btn';
		}

		$login_redirect = '';
		if ( $this->require_login == 'yes' && ! get_current_user_id() ) {
			$current_uri    = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			$login_redirect = 'data-login-redirect="' . esc_url( wp_login_url( home_url( $current_uri ) ) ) . '"';
		}
		$wishlist_data = apply_filters( 'wopb_menu_wishlist_data', array( 'post_id' => get_the_ID() ) );

		$html              = '<span class="wopb-wishlist-btn-wrap">';
			$html         .= '<a ';
				$html     .= 'class="' . $button_class . '"';
				$html     .= ( ! empty( $wishlist_data['button_attr'] ) ? $wishlist_data['button_attr'] : '' ) . $login_redirect;
			$html         .= '>';
				$html     .= '<span class="wopb-wishlist-text">';
					$html .= wopb_function()->svg_icon( 'wishlist' ) . esc_html( $this->button );
				$html     .= '</span>';
				$html     .= '<span class="wopb-wishlist-browse">';
					$html .= wopb_function()->svg_icon( 'wishlistFill' ) . esc_html( $this->browse );
				$html     .= '</span>';
			$html         .= '</a>';
		$html             .= '</span>';
		return $content . $html;
	}


	/**
	 * Wishlist Shortcode, Where Wishlist Shown
	 *
	 * @since v.1.1.0
	 * @return STRING | HTML of the shortcode
	 */
	public function wishlist_shortcode_callback( $message = '', $ids = array() ) {
		$html          = '';
		$wishlist_data = empty( $ids ) ? $this->get_wishlist_id() : $ids;

		if ( count( $wishlist_data ) > 0 ) {
			$wishlist_page = $this->wishlist_page ? get_permalink( $this->wishlist_page ) : '#';
			$redirect_cart = wopb_function()->get_setting( 'wishlist_redirect_cart' );

			$html         .= '<div class="wopb-modal-body">';
				$html     .= '<div class="wopb-wishlist-modal-content' . ( empty( $post_id ) ? ' wopb-wishlist-shortcode' : '' ) . '" data-modal_content_class="wopb-wishlist-wrapper" data-modal-loader="loader_1">';
					$html .= '<div class="wopb-wishlist-modal">';
			if ( $message ) {
				$html .= esc_html( $message );
			} else {
				$html             .= '<div class="wopb-wishlist-table-body">';
				$html             .= '<table>';
					$html         .= '<thead>';
						$html     .= '<tr>';
							$html .= '<th class="wopb-wishlist-product-remove"></th>';
							$html .= '<th class="wopb-wishlist-product-image">' . esc_html__( 'Image', 'product-blocks' ) . '</th>';
							$html .= '<th class="wopb-wishlist-product-name">' . esc_html__( 'Name', 'product-blocks' ) . '</th>';
							$html .= '<th class="wopb-wishlist-product-price">' . esc_html__( 'Price', 'product-blocks' ) . '</th>';
							$html .= '<th class="wopb-wishlist-product-action">' . esc_html__( 'Action', 'product-blocks' ) . '</th>';
						$html     .= '</tr>';
					$html         .= '</thead>';
					$html         .= '<tbody>';
				foreach ( $wishlist_data as $val ) {
							$product = wc_get_product( $val );
					if ( $product ) {
						$link      = get_permalink( $val );
						$html     .= '<tr>';
						$html     .= '<td><a class="wopb-wishlist-remove" data-action="remove" href="#" data-postid="' . esc_attr( $product->get_id() ) . '">×</a></td>';
						$html     .= '<td class="wopb-wishlist-product-image">';
						$thumbnail = apply_filters( 'single_product_archive_thumbnail_size', 'woocommerce_thumbnail' );
						if ( $thumbnail ) {
								$html .= sprintf( '<a href="%s">%s</a>', esc_url( $link ), $product->get_image( 'thumbnail' ) );
						}
						$html .= '</td>';
						$html .= '<td class="wopb-wishlist-product-name"><a href="' . esc_url( $link ) . '">' . esc_html( $product->get_title() ) . '</a></td>';
						$html .= '<td class="wopb-wishlist-product-price">' . wp_kses_post( $product->get_price_html() ) . '</td>';
						if ( $product->is_in_stock() ) {
										$html .= '<td class="wopb-wishlist-product-action"><div class="wopb-wishlist-product-stock">' . ( $product->is_in_stock() ? esc_html__( 'In Stock', 'product-blocks' ) : esc_html__( 'Stock', 'product-blocks' ) ) . '</div><span class="wopb-wishlist-cart-added" data-action="cart_remove" ' . ( $redirect_cart ? 'data-redirect="' . esc_url( wc_get_cart_url() ) . '"' : '' ) . ' data-postid="' . esc_attr( $product->get_id() ) . '">' . do_shortcode( '[add_to_cart id="' . esc_attr( $val ) . '" show_price="false"]' ) . '</span></td>';
						} else {
														$html .= '<td class="wopb-wishlist-product-action"><div class="wopb-wishlist-product-stock">' . ( $product->is_in_stock() ? esc_html__( 'In Stock', 'product-blocks' ) : esc_html__( 'Stock', 'product-blocks' ) ) . '</div>' . do_shortcode( '[add_to_cart id="' . esc_attr( $val ) . '" show_price="false"]' ) . '</td>';
						}
											$html .= '</tr>';
					}
				}
					$html         .= '</tbody>';
							$html .= '</table>';
							$html .= '</div>';
			}
						$html         .= '<div class="wopb-wishlist-product-footer">';
							$html     .= '<span>';
								$html .= '<a href="' . esc_url( $wishlist_page ) . '">' . esc_html__( 'Open Wishlist Page', 'product-blocks' ) . '</a>';
							$html     .= '</span>';
							$html     .= '<span>';
								$html .= '<a href="#" class="wopb-wishlist-cart-added" data-action="cart_remove_all" ' . ( $redirect_cart ? 'data-redirect="' . esc_url( wc_get_cart_url() ) . '"' : '' ) . ' data-postid="' . implode( ',', $wishlist_data ) . '">' . esc_html__( 'Add All To Cart', 'product-blocks' ) . '</a>';
							$html     .= '</span>';
							$html     .= '<span>';
								$html .= '<a class="wopb-modal-continue" data-redirect="' . get_permalink( wc_get_page_id( 'shop' ) ) . '">' . esc_html__( 'Continue Shopping', 'product-blocks' ) . '</a>';
							$html     .= '</span>';
						$html         .= '</div>';
					$html             .= '</div>';// wopb-modal-content
				$html                 .= '</div>';// wopb-modal-content
			$html                     .= '</div>';// wopb-modal-body
		} else {
			$html     .= '<div class="wopb-empty-wishlist-wrap">';
				$html .= '<h3>' . esc_html__( 'Your Wishlist is empty.', 'product-blocks' ) . '</h3>';
				$html .= '<span><a class="wopb-modal-continue" data-redirect="' . get_permalink( wc_get_page_id( 'shop' ) ) . '">' . esc_html__( 'Continue Shopping', 'product-blocks' ) . '</span>';
			$html     .= '</div>';
		}
		return $html;
	}


	/**
	 * Wishlist Addons Intitial Setup Action
	 *
	 * @since v.1.1.0
	 * @return NULL
	 */
	public function initial_setup() {
		$settings = wopb_function()->get_setting();
		// Set Default Value
		$initial_data = array(
			'wishlist_heading'            => 'yes',
			'wishlist_page'               => '',
			'wishlist_require_login'      => '',
			'wishlist_empty'              => '',
			'wishlist_redirect_cart'      => 'yes',
			'wishlist_action_added'       => 'popup',

			'wishlist_shop_enable'        => 'no',
			'wishlist_position'           => 'after_cart',
			'wishlist_button'             => '',
			'wishlist_browse'             => '',

			'wishlist_single_enable'      => 'yes',
			'wishlist_position_single'    => 'after_cart',
			'wishlist_button_single'      => __( 'Add to Wishlist', 'product-blocks' ),
			'wishlist_browse_single'      => __( 'Browse Wishlist', 'product-blocks' ),

			'wishlist_nav_enable'         => '',
			'wishlist_nav_location'       => '',
			'wishlist_nav_text'           => '',
			'wishlist_nav_icon_position'  => '',
			'wishlist_nav_click_action'   => '',
			'wishlist_nav_shortcode'      => '',

			'wishlist_btn_typo_shop'      => array(
				'size'        => 14,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => 'rgba(7, 7, 7, 1)',
				'hover_color' => 'rgba(255, 23, 107, 1)',
			),
			'wishlist_btn_bg_shop'        => array(
				'bg'       => '',
				'hover_bg' => '',
			),
			'wishlist_btn_padding_shop'   => array(
				'top'    => 0,
				'bottom' => 0,
				'left'   => 0,
				'right'  => 0,
			),
			'wishlist_btn_border_shop'    => array(
				'border' => 0,
				'color'  => '',
			),
			'wishlist_btn_radius_shop'    => 0,
			'wishlist_icon_size_shop'     => 16,
			'wishlist_align_shop'         => '',

			'wishlist_btn_typo_single'    => array(
				'size'        => 14,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => 'rgba(7, 7, 7, 1)',
				'hover_color' => 'rgba(255, 23, 107, 1)',
			),
			'wishlist_btn_bg_single'      => array(
				'bg'       => '',
				'hover_bg' => '',
			),
			'wishlist_btn_padding_single' => array(
				'top'    => 0,
				'bottom' => 0,
				'left'   => 0,
				'right'  => 0,
			),
			'wishlist_btn_border_single'  => array(
				'border' => 0,
				'color'  => '',
			),
			'wishlist_btn_radius_single'  => 0,
			'wishlist_icon_size_single'   => 16,

			'wishlist_btn_typo_nav'       => array(
				'size'        => 16,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => '',
				'hover_color' => '',
			),
			'wishlist_btn_bg_nav'         => array(
				'bg'       => '',
				'hover_bg' => '',
			),
			'wishlist_btn_padding_nav'    => array(
				'top'    => 0,
				'bottom' => 0,
				'left'   => 0,
				'right'  => 0,
			),
			'wishlist_btn_border_nav'     => array(
				'border' => 0,
				'color'  => '',
			),
			'wishlist_btn_radius_nav'     => 0,
			'wishlist_icon_size_nav'      => 18,

			'wishlist_heading_typo'       => array(
				'size'        => 16,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => '#000000',
				'hover_color' => '',
			),
			'wishlist_heading_bg'         => array(
				'bg'       => '#ededed',
				'hover_bg' => '',
			),
			'wishlist_body_text_typo'     => array(
				'size'        => 14,
				'bold'        => false,
				'italic'      => false,
				'underline'   => false,
				'color'       => '#000000',
				'hover_color' => '',
			),
			'wishlist_column_space'       => 0,
			'wishlist_column_border'      => array(
				'border' => 1,
				'color'  => 'rgba(0, 0, 0, .08)',
			),
		);
		foreach ( $initial_data as $key => $val ) {
			if ( ! isset( $settings[ $key ] ) ) {
				wopb_function()->set_setting( $key, $val );
			}
		}

		if ( ! isset( $settings['wishlist_page'] ) ) {
			// Insert Wishlist Page
			$wishlist_arr = array(
				'post_title'     => 'Wishlist',
				'post_type'      => 'page',
				'post_content'   => '<!-- wp:shortcode -->[wopb_wishlist]<!-- /wp:shortcode -->',
				'post_status'    => 'publish',
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
				'post_author'    => get_current_user_id(),
				'menu_order'     => 0,
			);
			$wishlist_id  = wp_insert_post( $wishlist_arr, false );
			if ( $wishlist_id ) {
				wopb_function()->set_setting( 'wishlist_page', $wishlist_id );
			}
		}
		$this->generate_css( 'wopb_wishlist' );
	}


	/**
	 * Wishlist Add Action Callback.
	 *
	 * @since v.1.1.0
	 * @return ARRAY | With Custom Message
	 */
	public function wopb_wishlist_callback() {
		if ( ! isset( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return;
		}

		$user_id        = get_current_user_id();
		$simple_Product = isset( $_POST['simpleProduct'] ) ? sanitize_text_field( wp_unslash( $_POST['simpleProduct'] ) ) : '';
		$type           = isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '';
		$post_id        = isset( $_POST['post_id'] ) ? sanitize_text_field( wp_unslash( $_POST['post_id'] ) ) : '';
		$product        = wc_get_product( $post_id );

		$user_data = $this->get_wishlist_id();

		if ( $post_id ) {
			if ( $type == 'add' ) {
				if ( ! in_array( $post_id, $user_data ) ) {
					$user_data[] = $post_id;
				}
				if ( $this->require_login == 'yes' && $user_id ) {
					update_user_meta( $user_id, 'wopb_wishlist', $user_data );
				} elseif ( $this->require_login == 'yes' && ! $user_id ) {
					wp_send_json_error(
						array(
							'message'  => __( 'You must be logged in.', 'product-blocks' ),
							'redirect' => wp_login_url(),
						)
					);
				}
				setcookie( 'wopb_wishlist', wp_json_encode( $user_data ), time() + 604800, '/' );
				$data = $this->wishlist_shortcode_callback( '', $user_data );
				wp_send_json_success(
					array(
						'html'           => $data,
						'wishlist_count' => count( $user_data ),
						'message'        => __( 'Wishlist Added.', 'product-blocks' ),
					)
				);
			} elseif ( $type == 'cart_remove' ) {
				if ( wopb_function()->get_setting( 'wishlist_empty' ) ) {
					$this->remove_wishlist_product( $post_id, $simple_Product );
				}
				wp_send_json_success(
					array(
						'wishlist_count'   => count( $this->get_wishlist_id() ),
						'product_redirect' => strpos( $product->add_to_cart_url(), 'add-to-cart=' ) === false ? $product->get_permalink() : '',
						'message'          => __( 'Wishlist Item Added To Cart.', 'product-blocks' ),
					)
				);
			} elseif ( $type == 'cart_remove_all' ) {
				foreach ( $user_data as $key => $val ) {
					WC()->cart->add_to_cart( $val );
				}
				if ( wopb_function()->get_setting( 'wishlist_empty' ) == 'yes' ) {
					if ( $this->require_login == 'yes' && $user_id ) {
						update_user_meta( $user_id, 'wopb_wishlist', array() );
					}
					setcookie( 'wopb_wishlist', wp_json_encode( array() ), time() + 604800, '/' );
				}

				wp_send_json_success(
					array(
						'wishlist_count' => count( $this->get_wishlist_id() ),
						'message'        => __( 'Wishlist All Item Removed.', 'product-blocks' ),
					)
				);
			} else {
				if ( $this->remove_wishlist_product( $post_id ) ) {
					wp_send_json_success(
						array(
							'wishlist_count' => count( $user_data ) - 1,
							'message'        => __( 'Wishlist Item Removed.', 'product-blocks' ),
						)
					);
				}
				wp_send_json_success( __( 'Wishlist Already Removed.', 'product-blocks' ) );
			}
		} elseif ( $type == 'menu_block' ) {
			$data = $this->wishlist_shortcode_callback( '', $user_data );
			wp_send_json_success(
				array(
					'html'           => $data,
					'wishlist_count' => count( $user_data ),
				)
			);
		} else {
			wp_send_json_error( __( 'Wishlist Not Added.', 'product-blocks' ) );
		}
		die();
	}

	/**
	 * Remove from Wishlist
	 *
	 * @param $post_id
	 * @param string $simple_product
	 * @return boolean
	 * @since v.3.1.14
	 */
	public function remove_wishlist_product( $post_id, $simple_product = '' ) {
		$user_data = $this->get_wishlist_id();
		if ( in_array( $post_id, $user_data ) ) {
			$user_id = get_current_user_id();
			$key     = array_search( $post_id, $user_data );
			if ( $simple_product !== 'false' ) {
				unset( $user_data[ $key ] );
			}
			if ( $this->require_login == 'yes' && $user_id ) {
				update_user_meta( $user_id, 'wopb_wishlist', $user_data );
			}
			setcookie( 'wopb_wishlist', wp_json_encode( $user_data ), time() + 604800, '/' );
			return true;
		}
		return false;
	}

	/**
	 * Wishlist show in default Shop page
	 *
	 * @return string
	 * @since v.3.1.14
	 */
	public function wopb_show_wishlist_in_shop( $content ) {
		if ( ( is_shop() || is_archive() ) && ! wopb_function()->is_builder() ) {
			return $content . $this->add_wishlist_html();
		}
	}

	/**
	 * Remove from Wishlist after add to cart
	 *
	 * @param $cart_item_key
	 * @param $product_id
	 * @return null
	 * @since v.3.1.14
	 */
	public function remove_wishlist_after_add_to_cart( $cart_item_key, $product_id ) {
		return $this->remove_wishlist_product( $product_id );
	}

	/**
	 * Wishlist Nav Menu To Specific Location.
	 *
	 * @since v.4.0.0
	 * @return string
	 */
	public function nav_menu_item( $items, $args ) {
		$nav_location = wopb_function()->get_setting( 'wishlist_nav_location' );
		if ( $nav_location ) {
			$nav_menu = '<li class="menu-item">' . $this->wishlist_nav_menu() . '</li>';
			if ( $args->theme_location ) {
				if ( $args->theme_location == $nav_location ) {
					$items .= $nav_menu;
				}
			} else {
				$items .= $nav_menu;
			}
		}
		return $items;
	}

	/**
	 * Wishlist Nav Menu.
	 *
	 * @since v.4.0.0
	 * @return string
	 */
	public function wishlist_nav_menu() {
		$icon_position    = wopb_function()->get_setting( 'wishlist_nav_icon_position' );
		$wishlist_text    = wopb_function()->get_setting( 'wishlist_nav_text' );
		$nav_class        = $icon_position == 'top_text' ? ' wopb-flex-column-dir' : '';
		$nav_click_action = wopb_function()->get_setting( 'wishlist_nav_click_action' );
		$html             = '<a class="wopb-wishlist-nav-item' . $nav_class . '"';
			$html        .= 'href="' . esc_url( get_permalink( wopb_function()->get_setting( 'wishlist_page' ) ) ) . '"';
			$html        .= 'data-action="' . $nav_click_action == 'popup' ? 'nav_popup' : 'redirect' . '"';
			$html        .= 'data-added-action="' . $nav_click_action == 'popup' ? 'nav_popup' : '' . '"';
			$html        .= 'data-postid="' . esc_attr( get_the_ID() ) . '"';
			$html        .= 'data-open-animation="wopb-' . esc_attr( wopb_function()->get_setting( 'compare_modal_open_animation' ) ) . '"';
			$html        .= 'data-close-animation="wopb-' . esc_attr( wopb_function()->get_setting( 'compare_modal_close_animation' ) ) . '"';
			$html        .= 'data-modal-loader="' . esc_attr( wopb_function()->get_setting( 'compare_modal_loading' ) ) . '"';
			$html        .= $nav_click_action == 'redirect' ? 'data-redirect="' . esc_url( get_permalink( wopb_function()->get_setting( 'wishlist_page' ) ) ) : '';
		$html            .= '>';
		if ( $icon_position == 'after_text' ) {
			$html .= wopb_function()->core_esc_wp( $wishlist_text );
		}
			$html         .= '<span class="wopb-wishlist-icon">';
				$html     .= wopb_function()->svg_icon( 'wishlist' );
				$html     .= '<span class="wopb-wishlist-count">';
					$html .= esc_html( count( $this->get_wishlist_id() ) );
				$html     .= '</span>';
			$html         .= '</span>';
		if ( $icon_position == 'before_text' || $icon_position == 'top_text' ) {
			$html .= wopb_function()->core_esc_wp( $wishlist_text );
		}
		$html .= '</a>';

		return $html;
	}

	/**
	 * Dynamic CSS
	 *
	 * @param $key
	 * @return void
	 * @since v.4.0.0
	 */
	public function generate_css( $key ) {
		if ( $key == 'wopb_wishlist' ) {
			$settings         = wopb_function()->get_setting();
			$shop_btn_style   = array_merge( $settings['wishlist_btn_typo_shop'], $settings['wishlist_btn_bg_shop'] );
			$single_btn_style = array_merge( $settings['wishlist_btn_typo_single'], $settings['wishlist_btn_bg_single'] );
			$nav_btn_style    = array_merge( $settings['wishlist_btn_typo_nav'], $settings['wishlist_btn_bg_nav'] );
			$css              = '';

			/* Shop page button style */
			if ( ! empty( $settings['wishlist_align_shop'] ) ) {
				$css     .= '.wopb-wishlist-btn-wrap{';
					$css .= 'display: inline-flex;';
					$css .= 'justify-content: ' . $settings['wishlist_align_shop'] . ';';
				$css     .= '}';
			}
			$css     .= '.wopb-wishlist-add.wopb-wishlist-shop-btn{';
				$css .= wopb_function()->convert_css( 'general', $shop_btn_style );
				$css .= wopb_function()->convert_css( 'border', $settings['wishlist_btn_border_shop'] );
				$css .= wopb_function()->convert_css( 'radius', $settings['wishlist_btn_radius_shop'] );
				$css .= 'padding: ' . wopb_function()->convert_css( 'dimension', $settings['wishlist_btn_padding_shop'] ) . ';';
			$css     .= '}';
			$css     .= '.wopb-wishlist-add.wopb-wishlist-shop-btn:hover{';
				$css .= wopb_function()->convert_css( 'hover', $shop_btn_style );
			$css     .= '}';
			$css     .= '.wopb-wishlist-add.wopb-wishlist-shop-btn svg{';
				$css .= 'height: ' . ( ! empty( $settings['wishlist_icon_size_shop'] ) ? $settings['wishlist_icon_size_shop'] : '16' ) . 'px;';
				$css .= 'width: ' . ( ! empty( $settings['wishlist_icon_size_shop'] ) ? $settings['wishlist_icon_size_shop'] : '16' ) . 'px;';
			$css     .= '}';
			/* Shop page button style */

			/* Single product page button style */
			$css     .= '.wopb-wishlist-add.wopb-wishlist-single-btn{';
				$css .= wopb_function()->convert_css( 'general', $single_btn_style );
				$css .= wopb_function()->convert_css( 'border', $settings['wishlist_btn_border_single'] );
				$css .= 'padding: ' . wopb_function()->convert_css( 'dimension', $settings['wishlist_btn_padding_single'] ) . ';';
				$css .= wopb_function()->convert_css( 'radius', $settings['wishlist_btn_radius_single'] );
			$css     .= '}';
			$css     .= '.wopb-wishlist-add.wopb-wishlist-single-btn:hover{';
				$css .= wopb_function()->convert_css( 'hover', $single_btn_style );
			$css     .= '}';
			$css     .= '.wopb-wishlist-add.wopb-wishlist-single-btn svg{';
				$css .= 'height: ' . ( ! empty( $settings['wishlist_icon_size_single'] ) ? $settings['wishlist_icon_size_single'] : '16' ) . 'px;';
				$css .= 'width: ' . ( ! empty( $settings['wishlist_icon_size_single'] ) ? $settings['wishlist_icon_size_single'] : '16' ) . 'px;';
			$css     .= '}';
			/* Single product page button style */

			/* Navbar style */
			$css     .= '.wopb-wishlist-nav-item{';
				$css .= wopb_function()->convert_css( 'general', $nav_btn_style );
				$css .= wopb_function()->convert_css( 'border', $settings['wishlist_btn_border_nav'] );
				$css .= wopb_function()->convert_css( 'radius', $settings['wishlist_btn_radius_nav'] );
				$css .= 'padding: ' . wopb_function()->convert_css( 'dimension', $settings['wishlist_btn_padding_nav'] ) . ';';
			$css     .= '}';
			$css     .= '.wopb-wishlist-nav-item:hover{';
				$css .= wopb_function()->convert_css( 'hover', $nav_btn_style );
			$css     .= '}';
			$css     .= '.wopb-wishlist-nav-item .wopb-wishlist-icon svg{';
				$css .= 'height: ' . ( ! empty( $settings['wishlist_icon_size_nav'] ) ? $settings['wishlist_icon_size_nav'] : '18' ) . 'px;';
				$css .= 'width: ' . ( ! empty( $settings['wishlist_icon_size_nav'] ) ? $settings['wishlist_icon_size_nav'] : '18' ) . 'px;';
			$css     .= '}';
			/* Navbar style */

			/* Table style */
			$css     .= '.wopb-wishlist-modal .wopb-wishlist-table-body table thead{';
				$css .= wopb_function()->convert_css( 'general', $settings['wishlist_heading_bg'] );
			$css     .= '}';
			$css     .= '.wopb-wishlist-modal .wopb-wishlist-table-body table thead:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['wishlist_heading_bg'] );
			$css     .= '}';
			$css     .= '.wopb-wishlist-table-body thead th{';
				$css .= wopb_function()->convert_css( 'general', $settings['wishlist_heading_typo'] );
			$css     .= '}';
			$css     .= '.wopb-wishlist-table-body thead th:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['wishlist_heading_typo'] );
			$css     .= '}';
			$css     .= '.wopb-wishlist-table-body tbody td{';
				$css .= wopb_function()->convert_css( 'general', $settings['wishlist_body_text_typo'] );
			$css     .= '}';
			$css     .= '.wopb-wishlist-table-body tbody td:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['wishlist_body_text_typo'] );
			$css     .= '}';
			$css     .= '.wopb-wishlist-modal table{';
			if ( ! empty( $settings['wishlist_column_space'] ) ) {
				$css .= 'border-collapse: separate;';
				$css .= 'border-spacing: ' . $settings['wishlist_column_space'] . 'px;';
			} else {
				$css .= 'border-collapse: collapse;';
			}
			$css     .= '}';
			$css     .= '.wopb-wishlist-table-body th, .wopb-wishlist-table-body td{';
				$css .= wopb_function()->convert_css( 'border', $settings['wishlist_column_border'] );
			$css     .= '}';
			/* Table style */

			wopb_function()->update_css( $key, 'add', $css );
		}
	}
}
