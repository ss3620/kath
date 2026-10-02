<?php //phpcs:ignore
/**
 * Class Hooks
 *
 * @package WowAddons
 */

namespace PRAD\Includes\Common;

use PRAD\Includes\Compatibility\BaseCurrency;
use PRAD\Includes\Xpo;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Hooks class.
 */
class Hooks {

	/**
	 * Stores the current product ID.
	 *
	 * @var int|string
	 */
	private $p_id = '';
	/**
	 * Stores the current product object.
	 *
	 * @var WC_Product|null
	 */
	private $prad_product;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'prad_blocks_price_both_show', array( $this, 'handle_prad_blocks_price_both_show' ), 10, 4 );
		add_action( 'prad_delete_option_product_meta', array( $this, 'delete_option_product_meta_callback' ), 10, 1 );

		add_action( 'prad_enqueue_block_css', array( $this, 'enqueue_block_css_callback' ), 10 );
		add_action( 'prad_enqueue_block_js', array( $this, 'enqueue_block_js_callback' ), 10 );

		add_filter( 'prad_allowed_html_tags', array( $this, 'handle_prad_allowed_html_tags' ), 10, 1 );

		add_filter( 'prad_raw_tax_currency_compitable_price', array( $this, 'handle_prad_raw_tax_currency_compitable_price' ), 10, 1 );
		add_filter( 'prad_raw_tax_compitable_price', array( $this, 'prad_get_price_including_tax' ), 10, 1 );

		add_action( 'prad_load_script_on_ajax', array( $this, 'handle_prad_load_script_on_ajax' ), 99 );
	}

	/**
	 * Loads required scripts and styles via AJAX for the frontend.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function handle_prad_load_script_on_ajax() {

		// Load Needed JS. An AJAX/REST response never reaches wp_footer(), so the scripts are
		// printed here with core's tag printers, which escape their own attributes.
		// wp_print_scripts() is avoided on purpose: it would also re-print the jquery/wp-*
		// dependencies the page already has.
		//
		// JSON_HEX_TAG turns every "<" in the JSON into a unicode escape, which the browser reads
		// back as the same value, so "<!--" and "<script" can never appear in the inline script
		// text. That keeps the script element intact (an option value like "<!--<script>" would
		// otherwise swallow the rest of the page) whatever WordPress version prints it.
		wp_print_inline_script_tag(
			'var prad_option_front = ' . wp_json_encode( $this->get_prad_option_front_data(), JSON_HEX_TAG ) . ';',
			array( 'id' => 'prad-option-front-data' )
		);

		$scripts = array(
			'prad-front-script-js-2' => 'frontend-script.js',
			'prad-front-date-js-2'   => 'wowdate-min.js',
			'prad-flag-script-js-2'  => 'wowflag.js',
		);
		foreach ( $scripts as $id => $file ) {
			$asset = product_addons()->get_script_asset( 'assets/js/' . $file );
			wp_print_script_tag(
				array(
					'src' => add_query_arg( 'ver', $asset['version'], PRAD_URL . 'assets/js/' . $file ),
					'id'  => $id,
				)
			);
		}

		// Load Needed CSS. These are the same two stylesheets enqueue_block_css_callback() queues as
		// "prad-frontend-css" and "prad-blocks-css". They are printed inline, read from disk: the
		// old way of fetching the file through its own public URL printed nothing, silently, on
		// any server where that request fails.
		$this->print_inline_style( $this->get_style_css( 'wowaddons-frontend' ) );
		$this->print_inline_style( $this->get_style_css( 'wowaddons-blocks' ) );

		// enqueue_block_css_callback() has already queued the global CSS on the "prad-global-css"
		// handle (the thematic CSS when it is set, the regular one otherwise), so print that
		// handle rather than deciding again here which option to use. Nothing is printed when
		// no global CSS is saved.
		wp_print_styles( 'prad-global-css' );
	}

	/**
	 * Reads a stylesheet bundled with the plugin.
	 *
	 * @param string $style_name Style name, as used by get_style_path().
	 *
	 * @return string The CSS, or an empty string when the file cannot be read.
	 */
	private function get_style_css( $style_name ) {
		$file = product_addons()->get_style_file_path( $style_name );
		if ( ! is_readable( $file ) ) {
			return '';
		}

		return (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reads a stylesheet bundled with the plugin, not a remote URL.
	}

	/**
	 * Prints a block of CSS inline, right where it is called.
	 *
	 * An AJAX/REST response never reaches wp_footer(), so the style is printed
	 * immediately through core's style printer instead of a hand-written <style> tag.
	 * Each call gets its own handle, so every call prints, like the echo it replaces.
	 *
	 * @param string $css CSS to print.
	 *
	 * @return void
	 */
	private function print_inline_style( $css ) {
		$css = wp_strip_all_tags( (string) $css );
		if ( '' === trim( $css ) ) {
			return;
		}

		$handle = wp_unique_id( 'prad-ajax-css-' );
		wp_register_style( $handle, false, array(), PRAD_VER );
		wp_add_inline_style( $handle, $css );
		wp_print_styles( $handle );
	}

	/**
	 * Enqueues the front-end script for PRAD and localizes necessary data.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_block_js_callback() {
		$front_asset = product_addons()->get_script_asset( 'assets/js/frontend-script.js', array( 'wp-api-fetch', 'jquery', 'wp-i18n' ) );
		wp_enqueue_script( 'prad-front-script', PRAD_URL . 'assets/js/frontend-script.js', $front_asset['dependencies'], $front_asset['version'], true );

		$date_asset = product_addons()->get_script_asset( 'assets/js/wowdate-min.js', array( 'jquery' ) );
		wp_enqueue_script( 'prad-date-script', PRAD_URL . 'assets/js/wowdate-min.js', $date_asset['dependencies'], $date_asset['version'], true );

		$flag_asset = product_addons()->get_script_asset( 'assets/js/wowflag.js', array( 'jquery' ) );
		wp_enqueue_script( 'prad-flag-script', PRAD_URL . 'assets/js/wowflag.js', $flag_asset['dependencies'], $flag_asset['version'], true );
		wp_localize_script(
			'prad-front-script',
			'prad_option_front',
			$this->get_prad_option_front_data()
		);
		wp_set_script_translations( 'prad-front-script', 'product-addons', PRAD_PATH . 'languages/' );
	}
	/**
	 * Enqueues the front-end styles.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function enqueue_block_css_callback() {

		product_addons()->enqueue_style( 'prad-frontend-css', 'wowaddons-frontend' );
		product_addons()->enqueue_style( 'prad-blocks-css', 'wowaddons-blocks' );

		$css     = get_option( 'prad_global_style_css', '' );
		$new_css = get_option( 'prad_global_style_thematic_css', '' );

		// Tags are stripped here, where the CSS is handed to the style printer, so the AJAX
		// path (which prints this same handle) gets the sanitising it had before.
		if ( $new_css ) {
			wp_register_style( 'prad-global-css', false ); // phpcs:ignore
			wp_enqueue_style( 'prad-global-css' );
			wp_add_inline_style( 'prad-global-css', wp_strip_all_tags( $new_css ) );
		} elseif ( $css ) {
			wp_register_style( 'prad-global-css', false ); // phpcs:ignore
			wp_enqueue_style( 'prad-global-css' );
			wp_add_inline_style( 'prad-global-css', wp_strip_all_tags( $css ) );
		}
	}

	/**
	 * Builds the regular price html for PRAD blocks.
	 *
	 * Sale prices are not handled here: product-addons-pro replaces the result through
	 * the prad_blocks_price_both_show filter when an option has a sale price.
	 *
	 * @param string $type       The type of price display logic.
	 * @param float  $regular    The regular price of the product.
	 * @param int    $product_id The ID of the product.
	 *
	 * @return string price html.
	 */
	public function handle_prad_blocks_price_html_return( $type, $regular, $product_id ) {
		$regular = floatval( $regular );
		$type    = $type ? $type : 'fixed';

		$unit_labels = $this->get_unit_price_labels();
		if ( isset( $unit_labels[ $type ] ) ) {
			return $this->get_per_unit_price_html( $regular, $product_id, $unit_labels[ $type ] );
		}

		switch ( $type ) {
			case 'percentage':
				$price_product = apply_filters(
					'prad_percentage_based_price_raw',
					$product_id,
					'revert'
				);
				$regular_c     = $regular ? ( ( $price_product * $regular ) / 100 ) : null;
				return $this->get_price_html( $regular_c, $product_id );
			case 'no_cost':
				return '<span class="pricex prad-d-none">10</span>';
			default:
				return $this->get_price_html( $regular, $product_id );
		}
	}

	/**
	 * Price types priced per counted unit, with the unit's label.
	 *
	 * @return array Price type => unit label.
	 */
	private function get_unit_price_labels() {
		/**
		 * Filters the price types priced per counted unit (e.g. per character), whose price
		 * is shown as "rate/unit * count = total".
		 *
		 * @since 1.8.3
		 *
		 * @param array $labels Price type => unit label.
		 */
		return (array) apply_filters(
			'prad_unit_price_labels',
			array(
				'per_char' => Xpo::get_prad_settings_item( 'characterText', 'Character' ),
			)
		);
	}

	/**
	 * Builds the price object (type, price and html) for PRAD blocks.
	 *
	 * The prad_blocks_price_both_show filter also passes the sale price, which this
	 * callback ignores: product-addons-pro applies sale prices on top of this result.
	 *
	 * @param string $type       The type of price display logic.
	 * @param float  $regular    The regular price of the product.
	 * @param float  $sale       The sale price of the product (ignored here).
	 * @param int    $product_id The ID of the product.
	 *
	 * @return array Price type, price and html.
	 */
	public function handle_prad_blocks_price_both_show( $type, $regular, $sale, $product_id ) {
		$type = $type ? $type : 'fixed';
		return array(
			'type'  => $type,
			'price' => $this->handle_prad_blocks_price_return( $type, $regular, $product_id ),
			'html'  => $this->handle_prad_blocks_price_html_return( $type, $regular, $product_id ),
		);
	}

	/**
	 * Handles the price calculation for PRAD blocks.
	 *
	 * Sale prices are applied by product-addons-pro through the prad_blocks_price_both_show filter.
	 *
	 * @param string $type       The type of price calculation.
	 * @param float  $regular    The regular price of the product.
	 * @param int    $product_id The ID of the product.
	 *
	 * @return float The calculated price based on the type.
	 */
	public function handle_prad_blocks_price_return( $type, $regular, $product_id ) {
		$type      = $type ? $type : 'fixed';
		$to_return = 'no_cost' === $type ? 0 : floatval( $regular );

		return 'percentage' === $type ? $to_return : $this->handle_prad_raw_tax_currency_compitable_price(
			array(
				'price'      => $to_return,
				'product_id' => $product_id,
				'source'     => 'product_page',
			)
		);
	}

	/**
	 * Generate HTML for Pricing
	 *
	 * @param float $regular    Regular price.
	 * @param int   $product_id Product ID.
	 *
	 * @return string HTML representation of the pricing.
	 */
	private function get_price_html( $regular, $product_id ) {
		$regular = $regular ? $this->handle_prad_raw_tax_currency_compitable_price(
			array(
				'price'      => $regular,
				'product_id' => $product_id,
				'source'     => 'product_page',
			)
		) : 0;
		$html    = '<span class="pricex">' . wc_price( $regular ) . '</span>';

		return wp_kses(
			$html,
			$this->handle_prad_allowed_html_tags()
		);
	}

	/**
	 * Generate HTML for a count-based price type (e.g. per_char), rendered
	 * as "rate/unit * count = total". Count starts at 0 on initial page load;
	 * the frontend script updates this live as the customer types.
	 *
	 * @param float  $regular    Regular price (rate).
	 * @param int    $product_id Product ID.
	 * @param string $unit       Unit label (e.g. 'Character', 'Word').
	 *
	 * @return string HTML representation of the pricing breakdown.
	 */
	private function get_per_unit_price_html( $regular, $product_id, $unit ) {
		$regular = $regular ? $this->handle_prad_raw_tax_currency_compitable_price(
			array(
				'price'      => $regular,
				'product_id' => $product_id,
				'source'     => 'product_page',
			)
		) : 0;

		$rate = $regular;

		$count = 0;
		$total = $rate * $count;

		$rate_html = wc_price( $regular );

		$breakdown_hidden = $count > 0 ? '' : ' prad-d-none';

		$html = '<span class="pricex prad-per-unit-price" data-unit-rate="' . esc_attr( $rate ) . '">'
			. $rate_html . '/' . esc_html( $unit )
			. '<span class="prad-per-unit-breakdown' . esc_attr( $breakdown_hidden ) . '"> * <span class="prad-per-unit-count">' . esc_html( $count ) . '</span> = <span class="prad-per-unit-total">' . wc_price( $total ) . '</span></span>'
			. '</span>';

		return wp_kses(
			$html,
			$this->handle_prad_allowed_html_tags()
		);
	}

	/**
	 * Set ALlowed Html
	 *
	 * This is the single wp_kses() allow-list for everything the plugin prints:
	 * WordPress's own "post" list plus the form/SVG markup the blocks render. Tag
	 * names must be lowercase (wp_kses lowercases the markup before matching) and
	 * the entries below extend the "post" definition of a tag rather than replace
	 * it, so a tag keeps WordPress's global attributes (class, id, style, data and
	 * aria attributes).
	 *
	 * @since 1.0.0
	 *
	 * @param array $extras Allowed htmls.
	 *
	 * @return array
	 */
	public function handle_prad_allowed_html_tags( $extras = array() ) {
		// Inline text tags are listed here, not just inherited from the "post" list, so they
		// always survive wp_kses(). The attributes are the ones the blocks and WooCommerce's
		// price markup (e.g. <del aria-hidden="true">) put on them.
		$text_tag_attrs = array(
			'class'       => true,
			'id'          => true,
			'style'       => true,
			'title'       => true,
			'aria-hidden' => true,
			'aria-label'  => true,
			'data-*'      => true,
		);

		$allowed = array(
			'div'      => array(
				'readonly' => true,
			),
			'bdi'      => array(
				'dir' => true,
			),
			'del'      => $text_tag_attrs + array( 'datetime' => true ),
			'ins'      => $text_tag_attrs + array(
				'datetime' => true,
				'cite'     => true,
			),
			'strong'   => $text_tag_attrs,
			'b'        => $text_tag_attrs,
			'select'   => array(
				'multiple' => true,
				'data-*'   => true,
			),
			'option'   => array(
				'value'  => true,
				'data-*' => true,
			),
			'input'    => array(
				'data-*'       => true,
				'type'         => true,
				'value'        => true,
				'placeholder'  => true,
				'name'         => true,
				'id'           => true,
				'min'          => true,
				'max'          => true,
				'format'       => true,
				'class'        => true,
				'step'         => true,
				'disabled'     => true,
				'readonly'     => true,
				'required'     => true,
				'maxlength'    => true,
				'minlength'    => true,
				'pattern'      => true,
				'autocomplete' => true,
				'accept'       => true,
				'multiple'     => true,
				'hidden'       => true,
			),
			'textarea' => array(
				'data-*'       => true,
				'type'         => true,
				'value'        => true,
				'placeholder'  => true,
				'name'         => true,
				'id'           => true,
				'min'          => true,
				'max'          => true,
				'rows'         => true,
				'format'       => true,
				'class'        => true,
				'disabled'     => true,
				'readonly'     => true,
				'required'     => true,
				'maxlength'    => true,
				'minlength'    => true,
				'pattern'      => true,
				'autocomplete' => true,
				'accept'       => true,
			),
			'svg'      => array(
				'xmlns'        => true,
				'width'        => true,
				'height'       => true,
				'viewbox'      => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
				'class'        => true,
				'style'        => true,
			),
			'g'        => array(
				'fill'            => true,
				'stroke'          => true,
				'opacity'         => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-width'    => true,
				'clip-path'       => true,
			),
			'path'     => array(
				'd'               => true,
				'fill'            => true,
				'fill-rule'       => true,
				'stroke'          => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'stroke-width'    => true,
				'clip-rule'       => true,
			),
			'rect'     => array(
				'rx'           => true,
				'width'        => true,
				'height'       => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
			),
			'defs'     => array(),
			'clippath' => array(
				'id' => true,
			),
			'style'    => array(
				'id'     => true,
				'class'  => true,
				'type'   => true,
				'media'  => true,
				'title'  => true,
				'scoped' => true,
				'data-*' => true,
			),
		);

		return array_replace_recursive( wp_kses_allowed_html( 'post' ), $allowed, $extras );
	}

	/**
	 * Delete Product Meta
	 *
	 * @since 1.0.0
	 *
	 * @param string $option_id Option id.
	 *
	 * @return void
	 */
	public function delete_option_product_meta_callback( $option_id ) {
		if ( ! $option_id ) {
			return;
		}
		$assigned_data = json_decode( product_addons()->safe_stripslashes( get_post_meta( $option_id, 'prad_base_assigned_data', true ) ), true );
		if ( $assigned_data ) {
			if ( 'all_product' === $assigned_data['aType'] ) {       /* Remove options for All Products */
				$option_settings = json_decode( product_addons()->safe_stripslashes( get_option( 'prad_option_assign_all', '[]' ) ), true );

				if ( is_array( $option_settings ) ) {
					if ( in_array( $option_id, $option_settings, false ) ) { //phpcs:ignore
						$option_settings = array_values( array_diff( $option_settings, array( $option_id ) ) );
					}
				} else {
					$option_settings = array();
				}
				update_option( 'prad_option_assign_all', wp_json_encode( $option_settings ) );
			} elseif ( is_array( $assigned_data['includes'] ) && count( $assigned_data['includes'] ) > 0 ) {
				foreach ( $assigned_data['includes'] as $include ) {
					$meta_inc = array();
					if ( 'specific_product' === $assigned_data['aType'] ) {
						$meta_inc = json_decode( product_addons()->safe_stripslashes( get_post_meta( $include, 'prad_product_assigned_meta_inc', true ) ), true );
					} elseif ( $this->is_term_assign_type( $assigned_data['aType'] ) ) {
						$meta_inc = json_decode( product_addons()->safe_stripslashes( get_term_meta( $include, 'prad_term_assigned_meta_inc', true ) ), true );
					}
					if ( is_array( $meta_inc ) ) {
						if ( in_array( $option_id, $meta_inc, false ) ) { //phpcs:ignore
							$meta_inc = array_values( array_diff( $meta_inc, array( $option_id ) ) );
						}
					} else {
						$meta_inc = array();
					}
					if ( 'specific_product' === $assigned_data['aType'] ) {
						update_post_meta( $include, 'prad_product_assigned_meta_inc', wp_json_encode( $meta_inc ) );
					} elseif ( $this->is_term_assign_type( $assigned_data['aType'] ) ) {
						update_term_meta( $include, 'prad_term_assigned_meta_inc', wp_json_encode( $meta_inc ) );
					}
				}
			}
			/* Handle excludes */
			if ( is_array( $assigned_data['excludes'] ) && count( $assigned_data['excludes'] ) > 0 ) {
				foreach ( $assigned_data['excludes'] as $exclude ) {
					$meta_exc = json_decode( product_addons()->safe_stripslashes( get_post_meta( $exclude, 'prad_product_assigned_meta_exc', true ) ), true );
					if ( is_array( $meta_exc ) ) {
						if ( in_array( $option_id, $meta_exc, false ) ) { //phpcs:ignore
							$meta_exc = array_values( array_diff( $meta_exc, array( $option_id ) ) );
						}
					} else {
						$meta_exc = array();
					}
					update_post_meta( $exclude, 'prad_product_assigned_meta_exc', wp_json_encode( $meta_exc ) );
				}
			}

			if ( is_array( $assigned_data ) ) {
				/** This action is documented in includes/restapi/class-request-api.php */
				do_action( 'prad_option_assignment_removed', $option_id, $assigned_data );
			}
		}
	}

	/**
	 * Whether an assignment type assigns to taxonomy terms (every "specific_*" type except
	 * specific_product, including ones other plugins add), whose assignments are kept in term meta.
	 *
	 * @param string $assign_type Assignment type.
	 * @return bool
	 */
	private function is_term_assign_type( $assign_type ) {
		return 'specific_product' !== $assign_type && 0 === strpos( (string) $assign_type, 'specific_' );
	}

	/**
	 * Converts the price to a compatible value with tax and currency.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments for price conversion.
	 *
	 * @return float Converted price.
	 */
	public function handle_prad_raw_tax_currency_compitable_price( $args ) {
		$price = $this->prad_get_price_including_tax( $args );
		return $price ? BaseCurrency::convert( floatval( $price ) ) : 0;
	}

	/**
	 * Get price including tax.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Arguments for price calculation.
	 *
	 * @return float Price including tax.
	 */
	public function prad_get_price_including_tax( $args ) {
		$price      = isset( $args['price'] ) ? $args['price'] : 0;
		$product_id = isset( $args['product_id'] ) ? $args['product_id'] : 0;
		$source     = isset( $args['source'] ) ? $args['source'] : '';

		if ( ! $price ) {
			return $price;
		}

		if ( ! $product_id ) {
			return $price ? $price : 0;
		}

		if ( (int) $this->p_id !== (int) $product_id ) {
			$this->prad_product = wc_get_product( $product_id );
			$this->p_id         = $product_id;
		}

		if ( ! $this->prad_product ) {
			return $price ? $price : 0;
		}

		return wc_get_price_to_display(
			$this->prad_product,
			array(
				'qty'             => 1,
				'price'           => $price,
				'display_context' => $source,
			)
		);
	}

	/**
	 * Retrieves front-end option data for PRAD scripts.
	 *
	 * @since 1.0.0
	 *
	 * @return array Front-end option data.
	 */
	public function get_prad_option_front_data() {
		return apply_filters(
			'prad_option_front_data',
			array_merge(
				array(
					'url'            => PRAD_URL,
					'nonce'          => wp_create_nonce( 'prad-nonce' ),
					'thousand_sep'   => get_option( 'woocommerce_price_thousand_sep', ',' ),
					'decimal_sep'    => get_option( 'woocommerce_price_decimal_sep', '.' ),
					'num_decimals'   => get_option( 'woocommerce_price_num_decimals', '2' ),
					'currency_pos'   => get_option( 'woocommerce_currency_pos', 'left' ),
					'currencySymbol' => function_exists( 'get_woocommerce_currency_symbol' ) ? get_woocommerce_currency_symbol() : '$',
					'characterText'  => \PRAD\Includes\Xpo::get_prad_settings_item( 'characterText', 'Character' ),
					'date_format'    => get_option( 'date_format' ),
				),
				product_addons()->get_currency_converted_data(),
			)
		);
	}
}
