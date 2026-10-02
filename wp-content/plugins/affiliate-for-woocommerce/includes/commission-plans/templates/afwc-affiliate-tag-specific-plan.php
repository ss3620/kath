<?php
/**
 * Commission Plan Template - Affiliate tag specific
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
	'slug'        => 'affiliate-tag-specific-plan',
	'name'        => 'Affiliate tags/groups',
	'description' => 'Set commission rates for affiliates based on their tags (e.g., Gold 25%, Influencers 35%).',
	'plan-data'   => array(
		'name'                 => 'Affiliate tags/groups',
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
							'type'     => 'affiliate_tag',
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
