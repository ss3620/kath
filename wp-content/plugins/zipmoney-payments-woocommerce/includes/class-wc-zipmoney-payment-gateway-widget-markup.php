<?php
/**
 * Builds the Zip widget markup.
 *
 * The single place the widget element is described. Before this class the same element
 * was spelled out twice — once in render_widget_product(), once in render_widget_cart() —
 * and the two copies had already drifted apart. Everything that renders a widget now goes
 * through build(): the hooked renderers, the [zip_widget] shortcode and the cart block's
 * server-side fallback.
 *
 * Pure: it takes values and returns a string, so it can be tested without WordPress.
 * esc_attr() is the only outside call, and it is injected for that reason.
 */
class WC_Zipmoney_Payment_Gateway_Widget_Markup {

	/**
	 * Attribute names per widget type.
	 *
	 * The product widget uses data-zm-widget while the cart widget uses zm-widget. That
	 * difference predates this class and is preserved verbatim, though it turns out not to
	 * matter: the bundle reads every one of these through getAttribute("data-" + name) ||
	 * getAttribute(name), so both spellings are honoured (zip-widget.min.js 1.7.0). The one
	 * place the prefix does matter is data-zm-price, which the bundle's MutationObserver
	 * watches by that exact name when it re-quotes a variation.
	 */
	private static $shapes = array(
		'product' => array(
			'class'       => 'widget-product',
			'asset'       => 'productwidget',
			'widget_attr' => 'data-zm-widget',
		),
		'cart'    => array(
			'class'       => 'widget-cart',
			'asset'       => 'cartwidget',
			'widget_attr' => 'zm-widget',
		),
	);

	/**
	 * Who printed this widget.
	 *
	 * The browser cannot tell a widget the render hook printed from one the merchant
	 * placed with the shortcode, and guessing is what let the placement script relocate —
	 * and then delete — a widget it never owned. So the server says it outright, and the
	 * script only ever touches ORIGIN_AUTO.
	 */
	const ORIGIN_AUTO   = 'auto';
	const ORIGIN_MANUAL = 'manual';

	/**
	 * @param string $type Widget type, product or cart.
	 * @param array  $args region, symbol, price, origin. Any of them may be empty; origin
	 *                     defaults to ORIGIN_AUTO.
	 * @return string Empty string for an unknown type.
	 */
	public static function build( $type, array $args = array() ) {
		if ( ! isset( self::$shapes[ $type ] ) ) {
			return '';
		}

		$symbol = isset( $args['symbol'] ) ? $args['symbol'] : '';
		$price  = isset( $args['price'] ) ? $args['price'] : '';

		$attributes = self::skeleton( $type, isset( $args['region'] ) ? $args['region'] : '' ) + array();

		$attributes['data-zm-price']   = $price;
		$attributes['data-zm-symbol']  = $symbol;
		$attributes['data-zip-origin'] = self::ORIGIN_MANUAL === ( isset( $args['origin'] ) ? $args['origin'] : '' )
			? self::ORIGIN_MANUAL
			: self::ORIGIN_AUTO;

		return self::element( $attributes );
	}


	/**
	 * The part of the element that does not depend on the cart: everything except price and
	 * symbol.
	 *
	 * The cart block cannot call build() — it renders in the browser, from a React tree —
	 * so it gets this skeleton through the inline configuration and spreads it into JSX.
	 * Without it the block spells the same six attributes a second time, and the two copies
	 * drift apart exactly the way the two PHP copies did before this class existed.
	 *
	 * @param string $type   Widget type, product or cart.
	 * @param string $region Merchant region.
	 * @return array attribute name => value; empty for an unknown type.
	 */
	public static function skeleton( $type, $region = '' ) {
		if ( ! isset( self::$shapes[ $type ] ) ) {
			return array();
		}

		$shape = self::$shapes[ $type ];

		return array(
			'class'               => $shape['class'],
			'zm-region'           => (string) $region,
			'data-zm-asset'       => $shape['asset'],
			$shape['widget_attr'] => 'popup',
			'data-zm-popup-asset' => 'termsdialog',
		);
	}


	/**
	 * The configuration root the Zip bundle reads before it draws anything.
	 *
	 * The bundle takes the first element in the document carrying zm-merchant, zm-region or
	 * zm-widget as its configuration, so this has to be printed before any widget.
	 *
	 * data-zm-merchant is how it knows whose assets to ask for. Without a merchant it sets
	 * the id to the literal "default" and reads the generic global.json instead of the
	 * merchant's own assets — the widget still appears, so the failure is silent, but none
	 * of the merchant's configuration reaches it.
	 *
	 * Attribute order is kept from the version that only ever ran on the checkout.
	 *
	 * @param array $args merchant, environment, region, price_min, price_max,
	 *                    display_inline, language. Any of them may be empty.
	 * @return string
	 */
	public static function build_root( array $args = array() ) {
		$value = function ( $key ) use ( $args ) {
			return isset( $args[ $key ] ) ? $args[ $key ] : '';
		};

		return self::element(
			array(
				'data-zm-merchant'       => $value( 'merchant' ),
				'data-env'               => $value( 'environment' ),
				// The bundle otherwise draws nothing outside a checkout page.
				'data-require-checkout'  => 'false',
				'data-zm-region'         => $value( 'region' ),
				'data-zm-price-max'      => $value( 'price_max' ),
				'data-zm-price-min'      => $value( 'price_min' ),
				'data-zm-display-inline' => $value( 'display_inline' ),
				'data-zm-language'       => $value( 'language' ),
			)
		);
	}


	/**
	 * The language part of a WordPress locale.
	 *
	 * The expression this replaces, substr( $locale, 0, strpos( $locale, '_' ) ), returned
	 * an empty string for every locale without a region — 'en' among them — because
	 * strpos() gives false there and substr() reads false as 0. A locale that carries no
	 * region is already the language.
	 *
	 * @param string $locale get_locale() value.
	 * @return string
	 */
	public static function language_from_locale( $locale ) {
		$locale    = is_string( $locale ) ? $locale : '';
		$separator = strpos( $locale, '_' );

		return false === $separator ? $locale : substr( $locale, 0, $separator );
	}


	/**
	 * One div with the given attributes, values escaped.
	 *
	 * @param array $attributes Attribute name => value.
	 * @return string
	 */
	private static function element( array $attributes ) {
		$rendered = '';

		foreach ( $attributes as $name => $value ) {
			$rendered .= ' ' . $name . '="' . self::escape( $value ) . '"';
		}

		return '<div' . $rendered . '></div>';
	}

	/**
	 * WordPress's esc_attr(), and only it.
	 *
	 * There used to be an htmlspecialchars() fallback here for the unit suite, which runs
	 * without WordPress — with the effect that the tests exercised the fallback while sites
	 * ran the other branch, and removing the esc_attr() call left the suite green. The
	 * suite now declares the function itself (tests/unit/bootstrap.php), so there is one
	 * branch and the tests are on it. Nothing on a live site reaches this class before
	 * WordPress is loaded: the plugin is loaded by WordPress.
	 *
	 * @param mixed $value
	 * @return string
	 */
	private static function escape( $value ) {
		$value = is_scalar( $value ) ? (string) $value : '';

		return esc_attr( $value );
	}
}
