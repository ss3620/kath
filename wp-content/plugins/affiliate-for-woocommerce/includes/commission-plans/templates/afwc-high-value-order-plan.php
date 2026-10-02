<?php
/**
 * Commission Plan Template - High-value orders
 *
 * @package   affiliate-for-woocommerce/includes/commission-plans/templates/
 * @since     9.14.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'slug'        => 'high-value-order-plan',
	'name'        => 'High-value orders',
	'description' => 'Reward affiliates with higher commissions for orders above a certain amount.',
	'plan-data'   => array(
		'name'                 => 'High-value orders',
		'status'               => 'Draft',
		'amount'               => 20.00,
		'type'                 => 'Percentage',
		'rules'                => array(
			'condition' => 'AND',
			'rules'     => array(
				array(
					'condition' => 'OR',
					'rules'     => array(
						array(
							'type'     => 'order_subtotal',
							'operator' => 'gt',
							'value'    => '1000',
						),
					),
				),
			),
		),
		'apply_to'             => 'all',
		'action_for_remaining' => 'continue',
		'no_of_tiers'          => 1,
		'distribution'         => array(),
	),
);
