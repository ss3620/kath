<?php
/**
 * Where each Zip widget is rendered, and whether it is rendered at all.
 *
 * Pure by design: it takes the raw gateway settings array and returns a decision.
 * No WordPress or WooCommerce calls, so the placement rules can be tested without
 * bootstrapping either.
 */
class WC_Zipmoney_Payment_Gateway_Widget_Placement {

	const TYPE_PRODUCT = 'product';
	const TYPE_CART    = 'cart';

	/**
	 * The hook and priority each widget is rendered on.
	 *
	 * These are the two places the widget has always appeared, and the priorities are what
	 * keeps it there. `woocommerce_single_product_summary` carries the core callbacks
	 * title@5, rating@10, price@10, excerpt@20, add-to-cart@30, meta@40 and sharing@50;
	 * ours registers after core at an equal priority of 10, so it lands right after the
	 * price. On the cart, core puts wc_get_pay_buttons@10 and
	 * woocommerce_button_proceed_to_checkout@20 on `woocommerce_proceed_to_checkout`, and
	 * ours registers later at the same priority 20 — after the Proceed to checkout button.
	 *
	 * A merchant who needs the widget somewhere else has two ways that do not involve a
	 * list of positions: the selector setting, which moves this widget in the browser, and
	 * the [zip_widget] shortcode, which renders one wherever it is placed.
	 *
	 * @var array type => array( hook, priority )
	 */
	private static $hooks = array(
		self::TYPE_PRODUCT => array( 'woocommerce_single_product_summary', 10 ),
		self::TYPE_CART    => array( 'woocommerce_proceed_to_checkout', 20 ),
	);

	/**
	 * Settings keys, kept here so the resolver stays readable on its own.
	 *
	 * The settings form takes its keys from get_setting_key() rather than spelling them a
	 * second time: a form that writes one option while the resolver reads another means the
	 * widget quietly ignores the setting.
	 */
	private static $keys = array(
		self::TYPE_PRODUCT => array(
			'enabled'  => 'display_widget_product_page',
			'selector' => 'display_widget_product_selector',
		),
		self::TYPE_CART    => array(
			'enabled'  => 'display_widget_cart',
			'selector' => 'display_widget_cart_selector',
		),
	);

	/**
	 * @param string $type Widget type, product or cart.
	 * @param string $name One of enabled, selector.
	 * @return string
	 */
	public static function get_setting_key( $type, $name ) {
		return isset( self::$keys[ $type ][ $name ] ) ? self::$keys[ $type ][ $name ] : '';
	}

	/**
	 * Where the widget should be rendered for this type, or null when it is switched off.
	 *
	 * @param string $type     Widget type, product or cart.
	 * @param array  $settings Raw gateway settings.
	 * @return array|null array( hook, priority )
	 */
	public static function resolve( $type, array $settings ) {
		if ( ! isset( self::$hooks[ $type ] ) || ! self::is_enabled( $type, $settings ) ) {
			return null;
		}

		return self::$hooks[ $type ];
	}

	/**
	 * The widget checkbox. Absent means enabled, because that is the form default and
	 * installs saved before this field existed have no value for it.
	 *
	 * @param string $type     Widget type, product or cart.
	 * @param array  $settings Raw gateway settings.
	 * @return bool
	 */
	public static function is_enabled( $type, array $settings ) {
		if ( ! isset( self::$keys[ $type ] ) ) {
			return false;
		}

		$key = self::$keys[ $type ]['enabled'];

		return ! isset( $settings[ $key ] ) || 'no' !== $settings[ $key ];
	}

	/**
	 * The optional CSS selector the widget is moved in front of. Empty means the widget
	 * stays where the hook put it.
	 *
	 * @param string $type     Widget type, product or cart.
	 * @param array  $settings Raw gateway settings.
	 * @return string
	 */
	public static function get_selector( $type, array $settings ) {
		if ( ! isset( self::$keys[ $type ] ) ) {
			return '';
		}

		$value = isset( $settings[ self::$keys[ $type ]['selector'] ] )
			? $settings[ self::$keys[ $type ]['selector'] ]
			: '';

		return is_string( $value ) ? trim( $value ) : '';
	}
}
