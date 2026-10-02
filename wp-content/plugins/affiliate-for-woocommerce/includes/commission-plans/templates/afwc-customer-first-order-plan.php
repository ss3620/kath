<?php
/**
 * Commission Plan Template - Customer First Order Plan
 *
 * @package   affiliate-for-woocommerce//includes/commission-plans/templates/
 * @since     8.58.0
 * @version   1.3.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'slug'        => 'customer-first-order-plan',
	'name'        => 'First-order',
	'description' => "Give commission only on a customer's first purchase.",
	'plan-data'   => array(
		'name'                 => 'First-order',
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
							'type'     => 'user_first_order',
							'operator' => 'eq',
							'value'    => 'yes',
						),
					),
				),
			),
		),
		'apply_to'             => 'all',
		'action_for_remaining' => 'zero',
		'no_of_tiers'          => 1,
		'distribution'         => array(),
	),
);
