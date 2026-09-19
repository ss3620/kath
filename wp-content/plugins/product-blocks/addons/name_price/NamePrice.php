<?php
/**
 * Name Your Price Addons Core.
 *
 * @package WOPB\NamePrice
 * @since v.4.0.0
 */

namespace WOPB;

defined( 'ABSPATH' ) || exit;

/**
 * NamePrice class.
 */
class NamePrice {

	/**
	 * Setup class.
	 *
	 * @since v.4.0.0
	 */
	private $def_value = array(
		'enable'       => 'no',
		'suggested'    => '',
		'chunk_enable' => 'no',
		'chunk'        => '',
		'min'          => '',
		'max'          => '',
	);

	public function __construct() {

		// Show Suggested Price for Simple Products.
		add_action( 'woocommerce_product_options_pricing', array( $this, 'simple_product_fields' ) );

		// Variation options Show Suggested Prices.
		add_action( 'woocommerce_variation_options_pricing', array( $this, 'variable_product_fields' ), 10, 3 );

		// Save Custom Fields of Simple Products
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_simple_product_fields' ), 10, 1 );

		// Save Custom Fields of Variable Products
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variable_product_fields' ), 10, 2 );

		// Add Custom Fields for Simple Products
		add_action( 'woocommerce_before_add_to_cart_button', array( $this, 'product_suggested_name_price' ) );

		// Add to cart link change for redirect
		add_filter( 'woocommerce_loop_add_to_cart_link', array( $this, 'loop_add_to_cart_link' ), 100, 3 );

		// Get the add to cart button text for the single page.
		add_filter( 'woocommerce_product_single_add_to_cart_text', array( $this, 'single_add_cart_label_text' ), 9999 );

		// Get the add to cart button text.
		add_filter( 'woocommerce_product_add_to_cart_text', array( $this, 'shop_add_cart_label_text' ), 9999, 2 );

		// Check If Price is Between Min and Max Price
		add_filter( 'woocommerce_add_to_cart_validation', array( $this, 'add_to_cart_validation' ), 10, 3 );

		// Filter cart item data for add to cart requests.
		add_filter( 'woocommerce_add_cart_item_data', array( $this, 'set_custom_prices' ), 10, 2 );

		// Set cart item price
		add_filter( 'woocommerce_get_cart_contents', array( $this, 'get_cart_contents' ), 10, 1 );

		// See if prices should be shown for each variation after selection.
		add_filter( 'woocommerce_available_variation', array( $this, 'get_all_variations' ), 10, 3 );

		add_filter( 'woocommerce_get_price_html', array( $this, 'price_html' ), 10, 2 );

		// CSS Generator
		add_action( 'wopb_save_settings', array( $this, 'generate_css' ), 10, 1 );
	}

	/**
	 * Display Simple Product
	 *
	 * @return void
	 */
	public function simple_product_fields() {
		global $product_object;
		$this->generate_fields( $product_object );
	}

	/**
	 * Show Custom Field for Variable
	 *
	 * @param $loop
	 * @param $variation_data
	 * @param $variation
	 * @return void
	 * @since v.4.0.0
	 */
	public function variable_product_fields( $loop, $variation_data, $variation ) {
		$product = wc_get_product( $variation->ID );
		$this->generate_fields( $product, $loop );
	}

	/**
	 * Custom Fields and Settings.
	 *
	 * @param $product
	 * @param $loop
	 * @return void
	 * @since v.4.0.0
	 */
	public function generate_fields( $product, $loop = '' ) {
		$data            = $this->get_data( $product->get_id() );
		$loop            = $loop !== '' ? '[' . $loop . ']' : '';
		$currency_symbol = ' (' . get_woocommerce_currency_symbol() . ')'; ?>
		<div class="wopb-name-price-option">
			<div>
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'          => '_wopb_name_price' . $loop,
						'class'       => 'checkbox wopb-name-price-enable',
						'value'       => $data['enable'],
						'label'       => __( 'Name Your Price', 'product-blocks' ),
						'description' => __( 'Turn on custom price / Name Your Price for this Product', 'product-blocks' ),
						'default'     => 'no',
					)
				);
				?>
			</div>
			<div class="wopb-name-price-wrap">
				<?php
				woocommerce_wp_checkbox(
					array(
						'id'          => '_wopb_name_hide_price' . $loop,
						'class'       => 'checkbox',
						'value'       => isset( $data['hide_price'] ) ? $data['hide_price'] : '',
						'label'       => __( 'Hide Main Price', 'product-blocks' ),
						'description' => __( 'Turn on if you want to hide main price in single product', 'product-blocks' ),
						'default'     => 'no',
					)
				);
				?>
				<?php
				woocommerce_wp_text_input(
					array(
						'id'        => '_wopb_name_price_suggested' . $loop,
						'type'      => 'number',
						'label'     => __( 'Suggested Price', 'product-blocks' ) . $currency_symbol,
						'data_type' => 'price',
						'value'     => $data['suggested'],
					)
				);
				woocommerce_wp_checkbox(
					array(
						'id'          => '_wopb_name_price_chunk_enable' . $loop,
						'value'       => $data['chunk_enable'],
						'label'       => __( 'Price Chunk', 'product-blocks' ),
						'description' => __( 'Show Chunked Price Instead of Input Field', 'product-blocks' ),
						'default'     => 'no',
					)
				);
				woocommerce_wp_text_input(
					array(
						'id'          => '_wopb_name_price_chunk' . $loop,
						'type'        => 'text',
						'label'       => __( 'Suggested Chunk', 'product-blocks' ),
						'value'       => $data['chunk'],
						'description' => __( 'Suggested Chunk eg: 10, 15, 20', 'product-blocks' ),
					)
				);
				woocommerce_wp_text_input(
					array(
						'id'                => '_wopb_name_price_min' . $loop,
						'type'              => 'number',
						'label'             => __( 'Minimum Price', 'product-blocks' ) . $currency_symbol,
						'data_type'         => 'price',
						'value'             => $data['min'],
						'custom_attributes' => array( 'min' => 0 ),
					)
				);
				woocommerce_wp_text_input(
					array(
						'id'                => '_wopb_name_price_max' . $loop,
						'type'              => 'number',
						'label'             => __( 'Maximum Price', 'product-blocks' ) . $currency_symbol,
						'data_type'         => 'price',
						'value'             => $data['max'],
						'custom_attributes' => array( 'min' => 0 ),
					)
				);
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save Custom Field for Simple Product
	 *
	 * @param $post_id
	 * @param string $loop it's loop number for variable product
	 * @return void
	 * @since v.4.0.0
	 */
	public function save_simple_product_fields( $post_id, $loop = '' ) {
		$this->save_product_fields( $post_id );
	}

	/**
	 * Save Custom Field for Variable Product
	 *
	 * @return void
	 * @since v.4.0.0
	 */
	public function save_variable_product_fields( $variation_id, $loop ) {
		$this->save_product_fields( $variation_id, $loop );
	}

	/**
	 * Get Video Meta Data
	 *
	 * @return array
	 * @since v.4.0.0
	 */
	public function get_data( $post_id ) {
		$data_value = get_post_meta( $post_id, '_wopb_name_price', true );
		return $data_value ? $data_value : $this->def_value;
	}

	/**
	 * Save Custom Field for Variable or Simple Product
	 *
	 * @return void
	 * @since v.4.0.0
	 */
	public function save_product_fields( $product_id, $loop = '' ) {
		$data     = $this->def_value;
		$key_list = array(
			'enable'       => '_wopb_name_price',
			'hide_price'   => '_wopb_name_hide_price',
			'suggested'    => '_wopb_name_price_suggested',
			'chunk_enable' => '_wopb_name_price_chunk_enable',
			'chunk'        => '_wopb_name_price_chunk',
			'min'          => '_wopb_name_price_min',
			'max'          => '_wopb_name_price_max',
		);
		$input    = array();
		foreach ( $key_list as $key => $val ) {
            if ( isset( $_POST[$val] ) ) { // phpcs:ignore
				// loop is coming from woocommcerce and starts from 0.
				if ( '' !== $loop && 0 <= $loop ) {
                    $input[$key] = sanitize_text_field( $_POST[$val][$loop] ); // phpcs:ignore
				} else {
                    $input[$key] = sanitize_text_field( $_POST[$val] ); // phpcs:ignore
				}
			}
		}
		update_post_meta( $product_id, '_wopb_name_price', array_merge( $data, $input ) );
	}

	/**
	 * Custom Name Price Field for Simple Product Name Price Addon
	 *
	 * @return void
	 * @since v.4.0.0
	 */
	public function product_suggested_name_price() {
		global $product;
		$type = $product->get_type();
		if ( $type != 'variable' && $type != 'grouped' && $type != 'external' ) {
			ob_start();
			$this->common_data( $product->get_id() );
			$name_price_data_safe = wopb_function()->wp_kses_safe( ob_get_clean() );
            echo $name_price_data_safe; // phpcs:ignore
		}
	}

	/**
	 * Generate HTML for the Frontend
	 *
	 * @return void | null
	 * @since v.4.0.0
	 */
	public function common_data( $product_id ) {
		$data = $this->get_data( $product_id );
		if ( $data['enable'] != 'yes' ) {
			return;
		}
		$label_min          = wopb_function()->get_setting( 'name_price_min_show' );
		$label_max          = wopb_function()->get_setting( 'name_price_max_show' );
		$price_title        = wopb_function()->get_setting( 'name_price_label' );
		$chunk_price_enable = $data['chunk_enable'];
		$price_min          = $data['min'];
		$price_max          = $data['max'];
		$price_chunk        = $data['chunk'];
		$price_chunk        = $price_chunk ? explode( ',', $price_chunk ) : array();
		$price_suggest      = $data['suggested'];

		if ( $price_suggest || $price_min || $price_max || ! empty( $price_chunk ) ) {
			?>
			<div class="wopb-name-price-main-wrapper">
				<div
					class="wopb-name-price-wrapper"
					data-min="<?php echo esc_attr( wopb_function()->currency_switcher_data( $price_min )['value'] ); ?>"
					data-max="<?php echo esc_attr( wopb_function()->currency_switcher_data( $price_max )['value'] ); ?>"
				>
					<div class="wopb-input-section<?php echo esc_attr( $chunk_price_enable == 'yes' ? ' wopb-d-none' : '' ); ?>">
						<label class="wopb-name-price-label" for="wopb-name-price-box">
							<?php echo esc_html( $price_title ); ?>
						</label>
						<input
							type="number"
							id="wopb-name-price-box"
							class="wopb-name-price-field"
							name="wopb_custom_prices"
							min="<?php echo esc_attr( $price_min ? wopb_function()->currency_switcher_data( $price_min )['value'] : 1 ); ?>"
							<?php echo esc_attr( $price_max ? 'max="' . wopb_function()->currency_switcher_data( $price_max )['value'] . '"' : '' ); ?>
							value="<?php echo esc_attr( $price_suggest ? wopb_function()->currency_switcher_data( $price_suggest )['value'] : ( $price_min ? wopb_function()->currency_switcher_data( $price_min )['value'] : 1 ) ); ?>"
						/>
					</div>
					<?php if ( count( $price_chunk ) > 0 ) { ?>
						<div class="wopb-chunk-price-wrapper">
							<?php foreach ( $price_chunk as $key => $chunk ) { ?>
								<span>
									<input
										type="radio"
										id="wopb-chunk-<?php echo esc_attr( $key ); ?>"
										name="wopb_custom_prices_suggestion"
										value="<?php echo esc_attr( wopb_function()->currency_switcher_data( $chunk )['value'] ); ?>"
										class="wopb-custom-chunk-prices"
									/>
									<label class="wopb-chunk-price-label" for="wopb-chunk-<?php echo esc_attr( $key ); ?>"><?php echo wc_price( wopb_function()->currency_switcher_data( $chunk )['value'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></label>
								</span>
							<?php } ?>
						</div>
					<?php } ?>
				</div>

				<div id="wopb-price-warning"></div>
				<div class="wopb-min-max-price">
					<?php
					if ( $price_min && $label_min == 'yes' ) {
						$min_safe = wopb_function()->wp_kses_safe( $this->min_max_generate( 'minimum', wopb_function()->get_setting( 'name_price_min' ), $price_min ) );
						echo $min_safe; // phpcs:ignore
					}
					if ( $price_max && $label_max == 'yes' ) {
						$max_safe = wopb_function()->wp_kses_safe( $this->min_max_generate( 'maximum', wopb_function()->get_setting( 'name_price_max' ), $price_max ) );
						echo $max_safe; // phpcs:ignore
					}
					?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Generate Min and max Price for Name Your Price Addon.
	 *
	 * @param $class_min_max
	 * @param $prices_titles
	 * @param $price_min_max
	 * @return string
	 * @since v.4.0.0
	 */
	public function min_max_generate( $class_min_max, $prices_titles, $price_min_max ) {
		$html      = '<div id="wopb-' . $class_min_max . '-price">';
			$html .= '<span class="wopb-range-label">' . $prices_titles . '</span> <span class="wopb-range-price">' . wc_price( wopb_function()->currency_switcher_data( $price_min_max )['value'] ) . '</span>';
		$html     .= '</div>';
		return $html;
	}

	/**
	 * Update the total price in Checkout Page
	 *
	 * @param $cart
	 * @return void
	 * @since v.4.0.0
	 */
	public function display_price_in_cart( $cart ) {
		if ( is_object( $cart ) ) {
			foreach ( $cart->get_cart() as $cart_item ) {
				if ( ! isset( $cart_item['wopb_custom_prices'] ) ) {
					continue;
				}
				$wopb_custom_price = $cart_item['wopb_custom_prices'];
				$currency          = wopb_function()->currency_switcher_data();
				if ( ! empty( $currency['current_currency_rate'] ) ) {
					$wopb_custom_price = $wopb_custom_price / $currency['current_currency_rate'];
				}
				$cart_item['data']->set_price( max( 0, $wopb_custom_price ) );
			}
		}
	}

	/**
	 * Show Partial Payment Subtotal
	 *
	 * @param $subtotal
	 * @param $cart_item
	 * @param $cart_item_key
	 * @return string
	 * @since v.2.0.1
	 */
	public function cart_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {
		if (
			$cart_item['wopb_custom_prices'] &&
			! empty( $cart_item['quantity'] )
		) {
			$subtotal = wc_price( $cart_item['wopb_custom_prices'] * $cart_item['quantity'] );
		}

		return $subtotal;
	}

	/**
	 * Add To Cart Link
	 *
	 * @param $link
	 * @param $product
	 * @param $args
	 * @return string
	 * @since v.4.0.0
	 */
	public function loop_add_to_cart_link( $link, $product, $args ) {
		$product_id    = $product->get_id();
		$data          = $this->get_data( $product_id );
		$name_price    = $data['enable'];
		$price_min     = $data['min'];
		$price_max     = $data['max'];
		$price_chunk   = $data['chunk'];
		$price_chunk   = $price_chunk ? explode( ',', $price_chunk ) : array();
		$price_suggest = $data['suggested'];

		if ( $name_price && ( $price_suggest || $price_min || $price_max || ! empty( $price_chunk ) ) ) {
			return str_replace(
				array( 'href="?add-to-cart=' . $product_id . '"', 'add_to_cart_button', 'ajax_add_to_cart' ),
				array( 'href="' . get_permalink( $product_id ) . '"', '', '' ),
				$link
			);
		}
		return $link;
	}

	/**
	 * Rename Single Product Page Add to Cart
	 *
	 * @param $label
	 * @return string
	 * @since v.4.0.0
	 */
	public function single_add_cart_label_text( $label ) {
		$product_id = get_the_ID();
		$data       = $this->get_data( $product_id );
		if ( $product_id && $data['enable'] == 'yes' && $text = wopb_function()->get_setting( 'name_price_single' ) ) {
			$label = $text;
		}
		return $label;
	}

	/**
	 * Rename the button on the Shop page
	 *
	 * @param $label
	 * @param $product
	 * @return string
	 * @since v.4.0.0
	 */
	public function shop_add_cart_label_text( $label, $product ) {
		$data = $this->get_data( $product->get_id() );
		if ( $product && isset( $data['enable'] ) && $data['enable'] == 'yes' && $text = wopb_function()->get_setting( 'name_price_archive' ) ) {
			$label = $text;
		}
		return $label;
	}

	/**
	 * Set Custom Price for Individual Product
	 *
	 * @param $cart
	 * @param $product_id
	 * @return array
	 * @since v.4.0.0
	 */
	public function set_custom_prices( $cart, $product_id ) {
        if ( ! empty( $_POST['wopb_custom_prices'] ) ) {  // phpcs:ignore
            $cart['wopb_custom_prices'] = wopb_function()->rest_sanitize_params( $_POST['wopb_custom_prices'] );  // phpcs:ignore
		}
		return $cart;
	}

	/**
	 * Set Cart Item Price
	 *
	 * @param $cart_contents
	 * @return array
	 * @since v.4.0.4
	 */
	public function get_cart_contents( $cart_contents ) {
		foreach ( $cart_contents as $cart_item ) {
			if ( ! empty( $cart_item['wopb_custom_prices'] ) ) {
				$cart_item['data']->set_price( $cart_item['wopb_custom_prices'] );
			}
		}
		return $cart_contents;
	}

	/**
	 * Change Cart item price
	 *
	 * @param $price
	 * @param $cart_item
	 * @param $cart_item_key
	 * @return string
	 * @since v.4.0.0
	 */
	public function cart_item_price( $price, $cart_item, $cart_item_key ) {
		if ( isset( $cart_item['wopb_custom_prices'] ) ) {
			return wc_price( $cart_item['wopb_custom_prices'] );
		}
		return $price;
	}

	/**
	 * Show Each of Variation Products for Variable Name Price Addon
	 *
	 * @param $data
	 * @param $product
	 * @param $variation
	 * @return object
	 * @since v.4.0.0
	 */
	public function get_all_variations( $data, $product, $variation ) {
		ob_start();
		$this->common_data( $variation->get_id(), $product->get_type() );
		$data['variation_description'] .= ob_get_clean();
		return $data;
	}

	/**
	 * Hide Main Price
	 *
	 * @param $price
	 * @param $product
	 * @return string
	 * @since v.4.0.5
	 */
	public function price_html( $price, $product ) {
		$name_price = $product->get_meta( '_wopb_name_price' );
		if (
			! empty( $name_price['enable'] ) && $name_price['enable'] == 'yes' &&
			! empty( $name_price['hide_price'] ) && $name_price['hide_price'] == 'yes'
		) {
			return '';
		}
		return $price;
	}

	/**
	 * Check If Price is Between Min and Max Price
	 *
	 * @param $passed
	 * @param $product_id
	 * @param $quantity
	 * @return boolean
	 * @since v.4.0.0
	 */
	public function add_to_cart_validation( $passed, $product_id, $quantity ) {
		$data = $this->get_data( $product_id );
		if ( $data['enable'] == 'yes' ) {
			$product   = wc_get_product( $product_id );
			$price_min = $data['min'];
			$price_min = wopb_function()->currency_switcher_data( $price_min ? $price_min : 1 )['value'];
			$price_max = $data['max'];
			$price_max = wopb_function()->currency_switcher_data( $price_max ? $price_max : $product->get_price() )['value'];
            if ( sanitize_text_field( $_SERVER['REQUEST_METHOD'] ) == 'POST' ) { // phpcs:ignore
                $custom_price = isset( $_POST['wopb_custom_prices'] ) ? sanitize_text_field( $_POST['wopb_custom_prices'] ) : ''; // phpcs:ignore
				if ( ! $custom_price || $custom_price && ( $custom_price < $price_min || $custom_price > $price_max ) ) {
					wc_add_notice(
						__( 'Product price must be between ', 'product-blocks' ) . $price_min .
						__( ' to ', 'product-blocks' ) . $price_max .
						__( ' to add to cart.', 'product-blocks' ),
						'error'
					);
					$passed = false;
				}
			}
		}
		return $passed;
	}

	/**
	 * CSS Generator
	 *
	 * @since v.4.0.0
	 * @return void
	 */
	public function generate_css( $key ) {
		if ( $key == 'wopb_name_price' ) {
			$settings          = wopb_function()->get_setting();
			$price_split_style = array_merge( $settings['name_price_split_typo'], $settings['name_price_split_bg'] );

			$css      = '.wopb-name-price-wrapper .wopb-name-price-label{';
				$css .= wopb_function()->convert_css( 'general', $settings['name_price_lvl_typo'] );
			$css     .= '}';
			$css     .= '.wopb-name-price-wrapper .wopb-name-price-label:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['name_price_lvl_typo'] );
			$css     .= '}';

			$css     .= '.wopb-name-price-wrapper .wopb-chunk-price-label{';
				$css .= wopb_function()->convert_css( 'general', $price_split_style );
				$css .= wopb_function()->convert_css( 'border', $settings['name_price_split_border'] );
				$css .= wopb_function()->convert_css( 'radius', $settings['name_price_split_radius'] );
				$css .= 'padding: ' . wopb_function()->convert_css( 'dimension', $settings['name_price_split_padding'] ) . ';';
			$css     .= '}';
			$css     .= '.wopb-name-price-wrapper .wopb-chunk-price-label:hover{';
				$css .= wopb_function()->convert_css( 'hover', $price_split_style );
			$css     .= '}';

			$css     .= '.wopb-min-max-price .wopb-range-label{';
				$css .= wopb_function()->convert_css( 'general', $settings['name_price_range_lvl_typo'] );
			$css     .= '}';
			$css     .= '.wopb-min-max-price .wopb-range-label:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['name_price_range_lvl_typo'] );
			$css     .= '}';

			$css     .= '.wopb-min-max-price .wopb-range-price{';
				$css .= wopb_function()->convert_css( 'general', $settings['name_price_range_typo'] );
			$css     .= '}';
			$css     .= '.wopb-min-max-price .wopb-range-price:hover{';
				$css .= wopb_function()->convert_css( 'hover', $settings['name_price_range_typo'] );
			$css     .= '}';

			wopb_function()->update_css( $key, 'add', $css );
		}
	}
}
