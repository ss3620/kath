<?php
/**
 * Commission Plan Template - Brand-associated affiliate groups
 *
 * @package   affiliate-for-woocommerce/includes/commission-plans/templates/
 * @since     9.14.0
 * @version   1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$product_group = taxonomy_exists( 'product_brand' ) ? 'product_brand' : 'product_category';

return array(
	'slug'        => 'brand-with-affiliate-group-plan',
	'name'        => 'Brand-specific affiliate groups',
	'description' => 'Set commissions for affiliate groups that drive sales of selected brands.',
	'plan-data'   => array(
		'name'                 => 'Brand-specific affiliate groups',
		'status'               => 'Draft',
		'amount'               => 25.00,
		'type'                 => 'Percentage',
		'rules'                => array(
			'condition' => 'AND',
			'rules'     => array(
				array(
					'condition' => 'AND',
					'rules'     => array(
						array(
							'type'     => $product_group,
							'operator' => 'in',
							'value'    => '',
						),
						array(
							'type'     => 'affiliate_tag',
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
