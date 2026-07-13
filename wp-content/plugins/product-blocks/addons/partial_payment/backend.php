<?php
defined( 'ABSPATH' ) || exit;

/**
 * Partial Payment Addons Initial Configuration
 *
 * @since v.1.0.8
 */
if ( ! function_exists( 'wopb_partial_payment_init' ) ) {
	add_filter( 'wopb_addons_config', 'wopb_partial_payment_init' );
	/**
	 * Initialize partial payment configuration
	 *
	 * @param array $config Configuration array.
	 * @return array Modified configuration array.
	 */
	function wopb_partial_payment_init( $config ) {
		$configuration                  = array(
			'name'     => __( 'Partial Payment', 'product-blocks' ),
			'desc'     => __( 'Split product prices into parts and let the customers place orders by paying only a deposit amount.', 'product-blocks' ),
			'live'     => 'https://www.wpxpo.com/product/wowstore/features/woocommerce-partial-payment/',
			'docs'     => 'https://wpxpo.com/docs/wowstore/add-ons/partial-payment/',
			'is_pro'   => true,
			'type'     => 'sales',
			'priority' => 70,
		);
		$config['wopb_partial_payment'] = $configuration;
		return $config;
	}
}

/**
 * Partial Payment Addons Default Settings  Parameters
 *
 * @param ARRAY | Default Settings Configuration
 * @return ARRAY
 * @since v.1.0.8
 */
add_filter( 'wopb_settings', 'get_partial_payment_opt', 10, 1 );
function get_partial_payment_opt( $config ) {
	$is_active = wopb_function()->get_setting( 'is_lc_active' );
	$arr       = array(
		'wopb_partial_payment' => array(
			'label' => __( 'Partial Payment', 'product-blocks' ),
			'attr'  => array(
				'partial_payment_heading' => array(
					'type'  => 'heading',
					'label' => __( 'Partial Payment Settings', 'product-blocks' ),
				),
				'wopb_partial_payment'    => array(
					'type'  => 'toggle',
					'value' => $is_active ? 'true' : 'false',
					'label' => __( 'Enable Partial Payment', 'product-blocks' ),
					'desc'  => __( 'Enable partial payment on your website', 'product-blocks' ),
					'pro'   => 'true',
				),
				'container_1'             => array(
					'type' => 'container',
					'attr' => array(
						'partial_payment_label_text' => array(
							'type'    => 'text',
							'label'   => __( 'Partial Payment Label/Text', 'product-blocks' ),
							'default' => __( 'Partial Payment', 'product-blocks' ),
						),
						'deposit_payment_text'       => array(
							'type'    => 'text',
							'label'   => __( 'Deposit/First Payment Text', 'product-blocks' ),
							'default' => __( 'First Payment', 'product-blocks' ),
						),
						'full_payment_text'          => array(
							'type'    => 'text',
							'label'   => __( 'Full Payment Text', 'product-blocks' ),
							'default' => __( 'Full Payment', 'product-blocks' ),
						),
						'deposit_to_pay_text'        => array(
							'type'    => 'text',
							'label'   => __( 'To Pay', 'product-blocks' ),
							'default' => __( 'To Pay', 'product-blocks' ),
						),
						'deposit_paid_text'          => array(
							'type'    => 'text',
							'label'   => __( 'Paid', 'product-blocks' ),
							'default' => __( 'Paid', 'product-blocks' ),
						),
						'deposit_amount_text'        => array(
							'type'    => 'text',
							'label'   => __( 'Deposit Amount Text', 'product-blocks' ),
							'default' => __( 'Deposit', 'product-blocks' ),
						),
						'due_payment_text'           => array(
							'type'    => 'text',
							'label'   => __( 'Due Payment', 'product-blocks' ),
							'default' => __( 'Due Payment', 'product-blocks' ),
						),
						'deposit_paid_status'        => array(
							'type'    => 'select',
							'default' => 'wc-processing',
							'options' => wc_get_order_statuses(),
							'label'   => __( 'Deposit Paid Status', 'product-blocks' ),
							'desc'    => __( 'Set order status when deposits are paid', 'product-blocks' ),
						),
						'disable_payment_method'     => array(
							'type'    => 'multiselect',
							'label'   => __( 'Disable Payment Methods	', 'product-blocks' ),
							'options' => wopb_function()->payment_gateway_list(),
							'desc'    => 'Test',
						),
					),
				),
			),
		),
	);
	return array_merge( $config, $arr );
}
