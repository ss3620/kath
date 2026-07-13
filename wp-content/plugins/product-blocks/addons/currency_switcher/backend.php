<?php
defined( 'ABSPATH' ) || exit;

/**
 * Currency Switcher Addons Initial Configuration
 *
 * @since v.1.1.9
 */
if ( ! function_exists( 'wopb_currency_switcher_init' ) ) {
	add_filter( 'wopb_addons_config', 'wopb_currency_switcher_init' );
	/**
	 * Initialize currency switcher configuration
	 *
	 * @param array $config The configuration array.
	 * @return array Modified configuration array.
	 */
	function wopb_currency_switcher_init( $config ) {
		$configuration                    = array(
			'name'     => __( 'Currency Switcher', 'product-blocks' ),
			'desc'     => __( 'It allows customers to switch product prices and make payments in their local currencies.', 'product-blocks' ),
			'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-currency-switcher/',
			'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/currency-switcher-addon/',
			'is_pro'   => true,
			'type'     => 'sales',
			'priority' => 60,
		);
		$config['wopb_currency_switcher'] = $configuration;
		return $config;
	}
}

/**
 * Currency Switcher Addons Default Settings Param
 *
 * @since v.1.1.9
 * @param ARRAY | Default Filter Congiguration
 * @return ARRAY
 */
add_filter( 'wopb_settings', 'get_currency_switcher_opt', 10, 1 );
function get_currency_switcher_opt( $config ) {
	$wc_default_currency   = get_woocommerce_currency();
	$wopb_default_currency = wopb_function()->get_setting( 'wopb_default_currency' ) ? wopb_function()->get_setting( 'wopb_default_currency' ) : $wc_default_currency;
	$currencies            = get_woocommerce_currencies();
	foreach ( $currencies as $code => $name ) {
		$currencies[ $code ] = $name . ' (' . get_woocommerce_currency_symbol( $code ) . ')';
	}
	$is_active = wopb_function()->get_setting( 'is_lc_active' );
	$arr       = array(
		'wopb_currency_switcher' => array(
			'label' => __( 'Currency Switcher', 'product-blocks' ),
			'attr'  => array(
				'currency_switcher_heading' => array(
					'type'  => 'heading',
					'label' => __( 'Currency Switcher Settings', 'product-blocks' ),
					'value' => $is_active ? 'true' : 'false',
				),
				'wopb_currency_switcher'    => array(
					'type'  => 'hidden',
					'value' => 'true',
				),
				'wopb_default_currency'     => array(
					'type'    => 'hidden',
					'value'   => $wopb_default_currency,
					'default' => $wc_default_currency,
				),
				'wopb_currencies'           => array(
					'type'                => 'repeatable',
					'label'               => __( 'Currency List', 'product-blocks' ),
					'header_text'         => $currencies[ $wc_default_currency ],
					'switch'              => true,
					'switch_name'         => 'wopb_is_default_currency',
					'depend_on_field'     => 'wopb_currency',
					'depend_target_field' => 'wopb_default_currency',
					'depend_options'      => $currencies,
					'fields'              => array(
						'wopb_is_default_currency'       => array(
							'type'                => 'toggle',
							'label'               => __( 'Set as Default', 'product-blocks' ),
							'depend_on_field'     => 'wopb_currency',
							'depend_target_field' => 'wopb_default_currency',
						),
						'wopb_currency'                  => array(
							'type'    => 'select',
							'label'   => __( 'Currency', 'product-blocks' ),
							'options' => $currencies,
							'default' => $wc_default_currency,
						),
						'wopb_currency_decimals'         => array(
							'type'    => 'number',
							'label'   => __( 'Decimal', 'product-blocks' ),
							'default' => 2,
						),
						'wopb_currency_symbol_position'  => array(
							'type'    => 'select',
							'label'   => __( 'Symbol Position', 'product-blocks' ),
							'options' => array(
								'left'        => esc_html__( 'Left', 'product-blocks' ),
								'right'       => esc_html__( 'Right', 'product-blocks' ),
								'left_space'  => esc_html__( 'Left Space', 'product-blocks' ),
								'right_space' => esc_html__( 'Right Space', 'product-blocks' ),
							),
							'default' => get_option( 'woocommerce_currency_pos' ),
						),
						'wopb_currency_rate'             => array(
							'type'                    => 'number',
							'label'                   => __( 'Rate', 'product-blocks' ),
							'default'                 => 1,
							'disable_depend_on_field' => 'wopb_is_default_currency',
							'disable_value'           => 'yes',
						),
						'wopb_currency_exchange_fee'     => array(
							'type'                    => 'number',
							'label'                   => __( 'Exchange Fee', 'product-blocks' ),
							'default'                 => 0,
							'disable_depend_on_field' => 'wopb_is_default_currency',
							'disable_value'           => 'yes',
						),
						'wopb_currency_exclude_gateways' => array(
							'type'    => 'switch',
							'label'   => __( 'Payment Method Disables	', 'product-blocks' ),
							'options' => wopb_function()->payment_gateway_list(),
						),
					),
				),
			),
		),
	);

	return array_merge( $config, $arr );
}
