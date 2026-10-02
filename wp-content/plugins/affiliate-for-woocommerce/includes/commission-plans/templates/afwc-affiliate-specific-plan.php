<?php
/**
 * Commission Plan Template - Affiliate specific
 *
 * @package   affiliate-for-woocommerce/includes/commission-plans/templates/
 * @since     8.60.0
 * @version   1.2.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'slug'        => 'affiliate-specific-plan',
	'name'        => 'Affiliate-specific',
	'description' => 'Set custom commission rates for individual affiliates.',
	'plan-data'   => array(
		'name'                 => 'Affiliate-specific',
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
							'type'     => 'affiliate',
							'operator' => 'in',
							'value'    => array(),
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
