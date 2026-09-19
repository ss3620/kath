<?php
/**
 * Commission Plan Template - Affiliate Performance Plan
 *
 * @package   affiliate-for-woocommerce/includes/commission-plans/templates/
 * @since     8.58.0
 * @version   1.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'slug'        => 'affiliate-performance-plan',
	'name'        => 'Performance-based commissions',
	'description' => 'Reward affiliates based on customers referred, referral orders, revenue generated, and recent referral activity.',
	'plan-data'   => array(
		'name'                 => 'Performance-based commissions',
		'status'               => 'Draft',
		'amount'               => 15.00,
		'type'                 => 'Percentage',
		'rules'                => array(
			'condition' => 'AND',
			'rules'     => array(
				array(
					'condition' => 'OR',
					'rules'     => array(
						array(
							'type'     => 'affiliate_customers',
							'operator' => 'gt',
							'value'    => '100',
						),
						array(
							'type'     => 'affiliate_revenue',
							'operator' => 'gt',
							'value'    => '10000',
						),
						array(
							'type'     => 'valid_referral_orders',
							'operator' => 'gt',
							'value'    => '500',
						),
					),
				),
				array(
					'condition' => 'AND',
					'rules'     => array(
						array(
							'type'     => 'affiliate_last_referral',
							'operator' => 'lte',
							'value'    => '30',
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
