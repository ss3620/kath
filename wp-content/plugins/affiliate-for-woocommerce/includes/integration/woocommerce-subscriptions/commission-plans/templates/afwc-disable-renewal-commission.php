<?php
/**
 * Commission Plan Template - Zero commission on subscription renewal
 *
 * @package   affiliate-for-woocommerce/includes/integration/woocommerce-subscriptions/commission-plans/templates/
 * @since     9.9.0
 * @version   1.2.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return array(
	'slug'        => 'disable-renewal-commission',
	'name'        => 'Zero commission on subscription renewals',
	'description' => "Give commission only on the subscription's first/parent order, by giving zero commission on renewal/recurring orders.",
	'plan-data'   => array(
		'name'                 => 'Zero commission on subscription renewals',
		'status'               => 'Draft',
		'amount'               => 0,
		'type'                 => 'Percentage',
		'rules'                => array(
			'condition' => 'AND',
			'rules'     => array(
				array(
					'condition' => 'AND',
					'rules'     => array(
						array(
							'type'     => 'subscription_renewal',
							'operator' => 'gte',
							'value'    => '0',
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
	'priority'    => 0,
);
