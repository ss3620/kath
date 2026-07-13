<?php
/**
 * Commission Plan Template - Product specific
 *
 * @package   product-for-woocommerce/includes/commission-plans/templates/
 * @since     8.60.0
 * @version   1.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'slug'        => 'product-specific-plan',
	'name'        => 'Custom rates for products',
	'description' => 'Set commission rates for specific products.',
	'plan-data'   => array(
		'name'                 => 'Custom rates for products',
		'status'               => 'Draft',
		'amount'               => 15.00,
		'type'                 => 'Percentage',
		'rules'                => array(
			'condition' => 'AND',
			'rules'     => array(
				array(
					'condition' => 'AND',
					'rules'     => array(
						array(
							'type'     => 'product',
							'operator' => 'in',
							'value'    => '',
						),
					),
				),
			),
		),
		'apply_to'             => 'all',
		'action_for_remaining' => 'continue',
		'no_of_tiers'          => 1,
		'distribution'         => '',
	),
);
