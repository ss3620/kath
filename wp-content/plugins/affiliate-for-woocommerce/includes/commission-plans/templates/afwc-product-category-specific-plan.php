<?php
/**
 * Commission Plan Template - Product category specific
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
	'slug'        => 'product-category-specific-plan',
	'name'        => 'Product categories',
	'description' => 'Set commission rates for specific product categories.',
	'plan-data'   => array(
		'name'                 => 'Product categories',
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
							'type'     => 'product_category',
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
		'distribution'         => array(),
	),
);
