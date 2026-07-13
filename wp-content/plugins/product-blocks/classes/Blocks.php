<?php
/**
 * Compatibility Action.
 *
 * @package WOPB\Notice
 * @since v.1.1.0
 */
namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * Blocks class.
 */
class Blocks {

	/**
	 * Setup class.
	 *
	 * @since v.1.1.0
	 */
	private $all_blocks;

	/**
	 * Setup class.
	 *
	 * @since v.1.1.0
	 */
	public function __construct() {
		$this->blocks();
		add_action( 'wc_ajax_wopb_load_more', array( $this, 'wopb_load_more_callback' ) );
		add_action( 'wp_ajax_nopriv_wopb_load_more', array( $this, 'wopb_load_more_callback' ) );
		add_action( 'wc_ajax_wopb_filter', array( $this, 'wopb_filter_callback' ) );
		add_action( 'wp_ajax_nopriv_wopb_filter', array( $this, 'wopb_filter_callback' ) );
		add_action( 'wc_ajax_wopb_pagination', array( $this, 'wopb_pagination_callback' ) );
		add_action( 'wp_ajax_nopriv_wopb_pagination', array( $this, 'wopb_pagination_callback' ) );
		add_action( 'wp_ajax_wopb_addcart', array( $this, 'wopb_addcart_callback' ) );
		add_action( 'wp_ajax_nopriv_wopb_addcart', array( $this, 'wopb_addcart_callback' ) );
		add_action( 'wp_ajax_wopb_checkout_login', array( $this, 'wopb_checkout_login_callback' ) );
		add_action( 'wp_ajax_nopriv_wopb_checkout_login', array( $this, 'wopb_checkout_login_callback' ) );
		add_action( 'wp_ajax_wopb_share_count', array( $this, 'wopb_share_count_callback' ) );
		add_action( 'wp_ajax_nopriv_wopb_share_count', array( $this, 'wopb_share_count_callback' ) );
		// Nonce Generating Callback
		add_action( 'wp_ajax_wopb_get_nonce', array( $this, 'wopb_get_nonce_callback' ) ); // Nonce Generating Callback
		add_action( 'wp_ajax_nopriv_wopb_get_nonce', array( $this, 'wopb_get_nonce_callback' ) ); // Nonce Generating Callback

		// When a WOPB builder template is active (shop/archive), override posts_per_page
		// with the queryNumber configured on the grid/list block in that template.
		// On pages where no WOPB template is active, leave WooCommerce defaults untouched.
		add_action( 'woocommerce_product_query', array( $this, 'set_global_posts_per_page' ) );
	}

	public function set_global_posts_per_page( $q ) {
		$builder_post_id = wopb_function()->is_builder();
		if ( ! $builder_post_id ) {
			return;
		}
		$query_number = $this->get_builder_query_number( (int) $builder_post_id );

		// if the block in this page is one of grid or list by our plugin and query number is available, set it to query.
		if ( $query_number > 0 ) {
			$q->set( 'posts_per_page', $query_number );
		}
	}

	public function wopb_addcart_callback() {
		if ( ! isset( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return;
		}
		$product_id  = isset( $_POST['postid'] ) ? sanitize_text_field( wp_unslash( $_POST['postid'] ) ) : '';
		$quantity    = isset( $_POST['quantity'] ) ? sanitize_text_field( wp_unslash( $_POST['quantity'] ) ) : '';
		$variationId = isset( $_POST['variationId'] ) ? sanitize_text_field( wp_unslash( $_POST['variationId'] ) ) : '';

		$variation = isset( $_POST['variation'] ) ? array_map( 'sanitize_text_field', wp_unslash( $_POST['variation'] ) ) : array();

		$cart_type = isset( $_POST['cartType'] ) ? sanitize_text_field( wp_unslash( $_POST['cartType'] ) ) : '';

		if ( $product_id ) {
			global $woocommerce;
			if ( $cart_type == 'buy_now' ) {
				WC()->cart->empty_cart();
			}
			$passed_validation = apply_filters( 'woocommerce_add_to_cart_validation', true, $product_id, $quantity );
			if ( $passed_validation ) {
				WC()->cart->add_to_cart( $product_id, $quantity, $variationId, $variation );
			}

			ob_start();
			woocommerce_mini_cart();
			$mini_cart = ob_get_clean();

			$data = array(
				'message'   => wc_add_to_cart_message( $product_id, $quantity, true ),
				'fragments' => apply_filters(
					'woocommerce_add_to_cart_fragments',
					array( 'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>' )
				),
				'cart_hash' => WC()->cart->get_cart_hash(),
				'success'   => true,
			);

			wp_send_json( $data );
		}
	}

	/**
	 * Require Blocks.
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function blocks() {
        $request = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : ''; // phpcs:ignore
		$host        = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';
        $host_url = isset($_SERVER['REQUEST_URI']) ? sanitize_url( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore
		$request_url = ( isset( $_SERVER['HTTPS'] ) && sanitize_text_field( wp_unslash( $_SERVER['HTTPS'] ) ) === 'on' ? 'https' : 'http' ) . '://' . $host . $host_url;
		if (
			(
				is_admin() &&
				$request != 'et_fb_ajax_render_shortcode' && // Divi Module Check
				wopb_function()->get_screen( 'action' ) != 'elementor' && // Elementor Widget Check
				$request != 'elementor_ajax' // Elementor Widget Check
			) || strpos( $request_url, '/wopb_builder/' ) !== false
		) {
			return;
		}

		$settings = wopb_function()->get_setting();
		$blocks   = array(
			'image'                                   => 'Image',
			'heading'                                 => 'Heading',
			'product_grid_1'                          => 'Product_Grid_1',
			'product_grid_2'                          => 'Product_Grid_2',
			'product_grid_3'                          => 'Product_Grid_3',
			'product_list_1'                          => 'Product_List_1',
			'product_category_1'                      => 'Product_Category_1',
			'product_category_2'                      => 'Product_Category_2',
			'product_category_3'                      => 'Product_Category_3',
			'product_filter'                          => 'Filter',
			'currency_switcher'                       => 'Currency_Switcher',
			'product_search'                          => 'Product_Search',
			'product_slider'                          => 'Product_Slider',
			'menu_wishlist'                           => 'Menu_Wishlist',
			'menu_compare'                            => 'Menu_Compare',
			'builder_cart_table'                      => 'Cart_Table',
			'builder_cart_total'                      => 'Cart_Total',
			'builder_free_shipping_progress_bar'      => 'Free_Shipping_Progress_Bar',
			'builder_checkout_login'                  => 'Checkout_Login',
			'builder_checkout_billing'                => 'Checkout_Billing',
			'builder_checkout_shipping'               => 'Checkout_Shipping',
			'builder_checkout_additional_information' => 'Checkout_Additional_Information',
			'builder_checkout_coupon'                 => 'Checkout_Coupon',
			'builder_checkout_payment_method'         => 'Checkout_Payment_Method',
			'builder_checkout_order_review'           => 'Checkout_Order_Review',
			'builder_my_account'                      => 'My_Account',
			'builder_archive_title'                   => 'Archive_Title',
			'builder_product_title'                   => 'Product_Title',
			'builder_product_short_description'       => 'Product_Short',
			'builder_product_price'                   => 'Product_Price',
			'builder_product_description'             => 'Product_Description',
			'builder_product_stock'                   => 'Product_Stock',
			'builder_product_image'                   => 'Product_Image',
			'builder_product_meta'                    => 'Product_Meta',
			'builder_product_additional_info'         => 'Product_Additional_Info',
			'builder_product_cart'                    => 'Product_Cart',
			'builder_product_review'                  => 'Product_Review',
			'builder_product_breadcrumb'              => 'Product_Breadcrumb',
			'builder_product_rating'                  => 'Product_Rating',
			'builder_product_tab'                     => 'Product_Tab',
			'builder_thankyou_order_conformation'     => 'Order_Conformation',
			'builder_thankyou_address'                => 'Thankyou_Address',
			'builder_thankyou_order_details'          => 'Thankyou_Order_Details',
			'builder_thankyou_order_payment'          => 'Order_Payment',
			'builder_social_share'                    => 'Social_Share',
		);

		spl_autoload_register(
			function ( $class ) {
				if ( strpos( $class, 'WOPB\blocks' ) === 0 ) {
						$source = WOPB_PATH . 'blocks/' . explode( '\\', $class )[2] . '.php';
					if ( file_exists( $source ) ) {
						include_once $source;
					} else {
						$source = WOPB_PATH . 'blocks/woo/' . explode( '\\', $class )[2] . '.php';
						if ( file_exists( $source ) ) {
							include_once $source;
						}
					}
				}
			}
		);

		foreach ( $blocks as $id => $block ) {
			if ( isset( $settings[ $id ] ) && $settings[ $id ] != 'yes' ) {
			} else {
				$obj = '\WOPB\blocks\\' . $block;
				new $obj();
			}
		}
	}

	/**
	 * Load More Action.
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function wopb_load_more_callback() {
		if ( ! isset( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return;
		}
		$paged         = isset( $_POST['paged'] ) ? sanitize_text_field( wp_unslash( $_POST['paged'] ) ) : '';
		$blockId       = isset( $_POST['blockId'] ) ? sanitize_text_field( wp_unslash( $_POST['blockId'] ) ) : '';
		$postId        = isset( $_POST['postId'] ) ? sanitize_text_field( wp_unslash( $_POST['postId'] ) ) : '';
		$blockRaw      = isset( $_POST['blockName'] ) ? sanitize_text_field( wp_unslash( $_POST['blockName'] ) ) : '';
		$builder       = isset( $_POST['builder'] ) ? sanitize_text_field( wp_unslash( $_POST['builder'] ) ) : '';
		$blockName     = str_replace( '_', '/', $blockRaw );
		$widgetBlockId = isset( $_POST['widgetBlockId'] ) ? sanitize_text_field( wp_unslash( $_POST['widgetBlockId'] ) ) : '';
		if ( $paged && $blockId && $postId && $blockName ) {
			$post             = get_post( $postId );
			$filterAttributes = wopb_function()->rest_sanitize_params( wp_unslash( ! empty( $_POST['filterAttributes'] ) && is_array( $_POST['filterAttributes'] ) ? $_POST['filterAttributes'] : array() ) );
			$params           = array(
				'filterAttributes' => $filterAttributes,
				'ajax_source'      => 'pagination',
			);
			if ( $widgetBlockId ) {
				$blocks = parse_blocks( get_option( 'widget_block' )[ $widgetBlockId ]['content'] );
				$this->block_return( $blocks, $paged, $blockId, $blockRaw, $blockName, $builder, $params );
			} elseif ( has_blocks( $post->post_content ) ) {
				$blocks = parse_blocks( $post->post_content );
				$this->block_return( $blocks, $paged, $blockId, $blockRaw, $blockName, $builder, $params );
			}
		}
	}

	/**
	 * Filter Callback of the Blocks
	 *
	 * @param $blocks, $paged, $blockId, $blockRaw, $blockName, $builder, $params
	 * @return STRING
	 * @since v.2.1.4
	 */
	public function block_return( $blocks, $paged, $blockId, $blockRaw, $blockName, $builder, $params = array(), $search = '', $archive_builder = null ) {
		foreach ( $blocks as $key => $value ) {
			if ( $blockName == $value['blockName'] ) {
				if ( $value['attrs']['blockId'] == $blockId ) {
					$objName                 = str_replace( ' ', '_', ucwords( join( ' ', explode( '-', explode( '/', $blockName )[1] ) ) ) );
					$obj                     = '\WOPB\blocks\\' . $objName;
					$newObj                  = new $obj();
					$attr                    = $newObj->get_attributes( true );
					$value['attrs']['paged'] = $paged;
					if ( $builder ) {
						$value['attrs']['builder'] = $builder;
					}
					if ( $search && strlen( $search ) ) {
						$value['attrs']['is_search'] = $search;
					}
					if ( ! empty( $archive_builder ) ) {
						$value['attrs']['is_archive'] = $archive_builder;
					}

					if ( isset( $params['filterAttributes'] ) && $params['filterAttributes'] ) {
						$attr = array_merge( $attr, $params['filterAttributes'] );
					}
					$attr = array_merge( $attr, $value['attrs'] );
					if ( $params['ajax_source'] ) {
						$attr['ajax_source'] = $params['ajax_source'];
					}
					$new_content_safe = wopb_function()->wp_kses_safe( $newObj->content( $attr, true ) );
                    echo $new_content_safe; // phpcs:ignore
					die();
				}
			}
			if ( ! empty( $value['innerBlocks'] ) ) {
				$this->block_return( $value['innerBlocks'], $paged, $blockId, $blockRaw, $blockName, $builder, $params, $search );
			}
		}
	}


	public function filter_block_return( $blocks, $blockId, $blockRaw, $blockName, $params = array() ) {
		foreach ( $blocks as $key => $value ) {
			if ( $blockName == $value['blockName'] ) {
				if ( $value['attrs']['blockId'] == $blockId ) {
					$objName      = str_replace( ' ', '_', ucwords( join( ' ', explode( '-', explode( '/', $blockName )[1] ) ) ) );
					$obj          = '\WOPB\blocks\\' . $objName;
					$newObj       = new $obj();
					$attr         = $newObj->get_attributes( true );
					$attr         = array_merge( $attr, $params );
					$attr         = array_merge( $attr, $value['attrs'] );
					$content_safe = wopb_function()->wp_kses_safe( $newObj->content( $attr, true ) );
					echo $content_safe; // phpcs:ignore
					die();
				}
			}
			if ( ! empty( $value['innerBlocks'] ) ) {
				$this->filter_block_return( $value['innerBlocks'], $blockId, $blockRaw, $blockName, $params );
			}
		}
	}

	/**
	 * Filter Callback.
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function wopb_filter_callback() {
		if ( ! isset( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return;
		}
		$taxtype = isset( $_POST['taxtype'] ) ? sanitize_text_field( wp_unslash( $_POST['taxtype'] ) ) : '';

		$blockId  = isset( $_POST['blockId'] ) ? sanitize_text_field( wp_unslash( $_POST['blockId'] ) ) : '';
		$postId   = isset( $_POST['postId'] ) ? sanitize_text_field( wp_unslash( $_POST['postId'] ) ) : '';
		$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : '';
		$blockRaw = isset( $_POST['blockName'] ) ? sanitize_text_field( wp_unslash( $_POST['blockName'] ) ) : '';

		$blockName     = str_replace( '_', '/', $blockRaw );
		$widgetBlockId = isset( $_POST['widgetBlockId'] ) ? sanitize_text_field( wp_unslash( $_POST['widgetBlockId'] ) ) : '';

		$params = array(
			'page_post_id' => $postId,
			'current_url'  => isset( $_POST['currentUrl'] ) ? esc_url_raw( wp_unslash( $_POST['currentUrl'] ) ) : '',
			'queryTax'     => $taxtype,
			'ajax_source'  => 'filter',
		);

		if ( $taxtype == 'product_cat' && $taxonomy ) {
			$params['queryCatAction'] = array( $taxonomy );
		}
		if ( $taxtype == 'product_tag' && $taxonomy ) {
			$params['queryTagAction'] = array( $taxonomy );
		}
		if ( $taxonomy ) {
			if ( strpos( $taxonomy, 'custom_action#' ) !== false ) {
				$params['custom_action'] = $taxonomy;
			}
		}
		if ( $taxtype ) {
			$post = get_post( $postId );
			if ( $widgetBlockId ) {
				$blocks = parse_blocks( get_option( 'widget_block' )[ $widgetBlockId ]['content'] );
				$this->filter_block_return( $blocks, $blockId, $blockRaw, $blockName, $params );
			} elseif ( has_blocks( $post->post_content ) ) {
				$blocks = parse_blocks( $post->post_content );
				$this->filter_block_return( $blocks, $blockId, $blockRaw, $blockName, $params );
			}
		}
	}

	/**
	 * Pagination Callback.
	 *
	 * @since v.1.0.0
	 * @return NULL
	 */
	public function wopb_pagination_callback() {
		if ( ! isset( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return;
		}

		$isSearch = isset( $_POST['isSearch'] ) ? sanitize_text_field( wp_unslash( $_POST['isSearch'] ) ) : '';

		$paged           = isset( $_POST['paged'] ) ? sanitize_text_field( wp_unslash( $_POST['paged'] ) ) : '';
		$blockId         = isset( $_POST['blockId'] ) ? sanitize_text_field( wp_unslash( $_POST['blockId'] ) ) : '';
		$postId          = isset( $_POST['postId'] ) ? sanitize_text_field( wp_unslash( $_POST['postId'] ) ) : '';
		$blockRaw        = isset( $_POST['blockName'] ) ? sanitize_text_field( wp_unslash( $_POST['blockName'] ) ) : '';
		$builder         = isset( $_POST['builder'] ) ? sanitize_text_field( wp_unslash( $_POST['builder'] ) ) : '';
		$archive_builder = isset( $_POST['is_archive'] ) ? sanitize_text_field( wp_unslash( $_POST['is_archive'] ) ) : '';

		$blockName = str_replace( '_', '/', $blockRaw );

		$widgetBlockId = isset( $_POST['widgetBlockId'] ) ? sanitize_text_field( wp_unslash( $_POST['widgetBlockId'] ) ) : '';
		$params        = array(
			'ajax_source' => 'pagination',
		);

		if ( isset( $_POST['filterAttributes'] ) ) {
			$params['filterAttributes'] = map_deep( wp_unslash( $_POST['filterAttributes'] ), 'sanitize_text_field' );
		}

		if ( $paged ) {
			$post = get_post( $postId );
			if ( $widgetBlockId ) {
				$blocks = parse_blocks( get_option( 'widget_block' )[ $widgetBlockId ]['content'] );
				$this->block_return( $blocks, $paged, $blockId, $blockRaw, $blockName, $builder, $params, $isSearch, $archive_builder );
			} elseif ( has_blocks( $post->post_content ) ) {
				$blocks = parse_blocks( $post->post_content );
				$this->block_return( $blocks, $paged, $blockId, $blockRaw, $blockName, $builder, $params, $isSearch, $archive_builder );
			}
		}
	}

	/**
	 * Checkout Login
	 *
	 * @since v.4.0.0
	 * @return NULL
	 */
	public function wopb_checkout_login_callback() {
		if ( ! isset( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return;
		}

		$username = isset( $_POST['username'] ) ? sanitize_text_field( wp_unslash( $_POST['username'] ) ) : '';
		$password = isset( $_POST['password'] ) ? sanitize_text_field( wp_unslash( $_POST['password'] ) ) : '';
		$remember = isset( $_POST['rememberme'] ) ? sanitize_text_field( wp_unslash( $_POST['rememberme'] ) ) : '';

		$errors = array();

		if ( $username && $password ) {
			try {
				$creds = array(
					'user_login'    => trim( wp_unslash( $username ) ),
					'user_password' => $password,
					'remember'      => $remember,
				);

				$validation_error = new \WP_Error();
				$validation_error = apply_filters( 'woocommerce_process_login_errors', $validation_error, $creds['user_login'], $creds['user_password'] );
				if ( $validation_error->get_error_code() ) {
					return wp_send_json( $validation_error->get_error_message(), 422 );
				}
				if ( empty( $creds['user_login'] ) ) {
					$errors['username'] = __( 'Username or Email is required.', 'product-blocks' );
					return wp_send_json( $errors, 422 );
				}
				if ( empty( $creds['user_password'] ) ) {
					$errors['password'] = __( 'Password is required.', 'product-blocks' );
					return wp_send_json( $errors, 422 );
				}

				// On multisite, ensure user exists on current site, if not add them before allowing login.
				if ( is_multisite() ) {
					$blog_id   = get_current_blog_id();
					$user_data = get_user_by( is_email( $creds['user_login'] ) ? 'email' : 'login', $creds['user_login'] );
					if ( $user_data && ! is_user_member_of_blog( $user_data->ID, $blog_id ) ) {
						add_user_to_blog( $blog_id, $user_data->ID, 'customer' );
					}
				}

				// Perform the login.
				$user = wp_signon( apply_filters( 'woocommerce_login_credentials', $creds ), is_ssl() );

				if ( is_wp_error( $user ) ) {
					$errors['default'] = $user->get_error_message();
					return wp_send_json( $errors, 422 );
				} else {
					return wp_send_json_success( array( 'success' => true ) );
					exit;
				}
			} catch ( \Exception $e ) {
				return wp_send_json( $e->getMessage(), 500 );
				do_action( 'woocommerce_login_failed' );
			}
		}
	}

	/**
	 * share Count callback
	 *
	 * @since v.1.0.0
	 * @return STRING
	 */
	public function wopb_share_count_callback() {
		if ( ! isset( $_REQUEST['wpnonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_REQUEST['wpnonce'] ) ), 'wopb-nonce' ) ) {
			return;
		}

		$post_id = isset( $_POST['postId'] ) ? sanitize_text_field( wp_unslash( $_POST['postId'] ) ) : '';
		$count   = isset( $_POST['shareCount'] ) ? sanitize_text_field( wp_unslash( $_POST['shareCount'] ) ) : '';
		$count   = (int) $count + 1;
		update_post_meta( $post_id, 'wopb_share_count', $count );
		return wp_send_json_success(
			array(
				'success' => true,
				'count'   => $count,
			)
		);
	}

	/**
	 * Nonce Generation Callback
	 *
	 * @since v.4.4.1
	 *
	 * @return STRING The AJAX response.
	 */
	public function wopb_get_nonce_callback() {
		nocache_headers();
		wp_send_json_success(
			array(
				'nonce' => wp_create_nonce( 'wopb-nonce' ),
			)
		);
	}

	/**
	 * Retrieve the queryNumber from the first product grid/list block
	 * found in a builder post's block tree.
	 *
	 * Since we are using first block, if a user is using multiple blocks
	 * will behave abnormally for 2nd block.
	 *
	 * @param int $post_id Builder post ID.
	 * @return int queryNumber, or 0 when not found.
	 */
	private function get_builder_query_number( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || ! has_blocks( $post->post_content ) ) {
			return 0;
		}
		return $this->find_query_number_in_blocks( parse_blocks( $post->post_content ) );
	}

	/**
	 * Recursively search a block tree for the first product grid/list block
	 * and return its queryNumber attribute.
	 *
	 * @param array $blocks Parsed blocks array.
	 * @return int queryNumber, or 0 when not found.
	 */
	private function find_query_number_in_blocks( $blocks ) {
		$product_blocks = array(
			'product-blocks/product-grid-1',
			'product-blocks/product-grid-2',
			'product-blocks/product-grid-3',
			'product-blocks/product-list-1',
		);
		foreach ( $blocks as $block ) {

			if ( in_array( $block['blockName'], $product_blocks, true ) ) {

				$should_use_query_number = ( $block['attrs']['queryNumber'] ?? 0 ) > 0 &&
											! ( $block['attrs']['paginationAjax'] ?? false ) &&
											( $block['attrs']['paginationShow'] ?? false );

				return $should_use_query_number ? (int) $block['attrs']['queryNumber'] : 0;
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = $this->find_query_number_in_blocks( $block['innerBlocks'] );
				if ( $found > 0 ) {
					return $found;
				}
			}
		}
		return 0;
	}
}
