<?php
/**
 * WooCommerce Cost of Goods
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Cost of Goods to newer
 * versions in the future. If you wish to customize WooCommerce Cost of Goods for your
 * needs please refer to http://docs.woocommerce.com/document/cost-of-goods/ for more information.
 *
 * @author      SkyVerge
 * @copyright   Copyright (c) 2013-2025, SkyVerge, Inc. (info@skyverge.com)
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\COG\Admin\Analytics;

use SkyVerge\WooCommerce\PluginFramework\v5_15_11 as Framework;

/**
 * Integrates with the Analytics > Revenue page.
 *
 * @since 2.14.0
 */
class Revenue extends AbstractAnalyticsReport
{
	protected string $statsTableName;

	protected array $customReportFields = [
		'cost_of_goods' => 'floatval',
		'cogs_profit' => 'floatval',
		'cogs_avg_profit_per_order' => 'floatval',
	];

	public function __construct()
	{
		global $wpdb;
		$this->statsTableName = $wpdb->prefix.'wc_order_stats';
	}

	public function addHooks() : void
	{
		parent::addHooks();

		// add our field to query args
		add_filter('woocommerce_analytics_revenue_query_args', [$this, 'addCogsFields']);

		// allow ordering by CoGs
		add_filter('woocommerce_analytics_orderby_enum_reports/revenue/stats', [$this, 'addCogsToOrderBy']);

		// add a JOIN on the meta table
		add_filter('woocommerce_analytics_clauses_join_orders_stats_total', [$this, 'addMetaJoin']);
		add_filter('woocommerce_analytics_clauses_join_orders_stats_interval', [$this, 'addMetaJoin']);

		// REST API schema
		add_filter('woocommerce_rest_report_revenue_stats_schema', [$this, 'modifySchema']);
	}

	public function addSelect($reportColumns, $context, $table_name)
	{
		if ($context !== 'orders_stats') {
			return $reportColumns;
		}

		$reportColumns['cost_of_goods'] = 'COALESCE(SUM(cost_of_goods), 0) AS cost_of_goods';
		$reportColumns['cogs_profit'] = "SUM({$this->statsTableName}.net_total) - COALESCE(SUM(cost_of_goods), 0) AS cogs_profit";
		$reportColumns['cogs_avg_profit_per_order'] = "(SUM({$this->statsTableName}.net_total) - COALESCE(SUM(cost_of_goods), 0)) / SUM(CASE WHEN {$this->statsTableName}.parent_id = 0 THEN 1 ELSE 0 END) AS cogs_avg_profit_per_order";

		return $reportColumns;
	}

	public function addMetaJoin($clauses)
	{
		$orderMetaTableName = Framework\SV_WC_Order_Compatibility::get_orders_meta_table();

		if (Framework\SV_WC_Plugin_Compatibility::is_hpos_enabled()) {
			$clauses[] = "LEFT JOIN (
				SELECT
					order_id,
					SUM(meta_value) AS cost_of_goods
				FROM
					{$orderMetaTableName}
					WHERE meta_key = '_wc_cog_order_total_cost'
				GROUP BY order_id
			) cost_of_goods_lookup
			ON cost_of_goods_lookup.order_id = {$this->statsTableName}.order_id";
		} else {
			$clauses[] = "LEFT JOIN (
				SELECT
					post_id as order_id,
					SUM(meta_value) AS cost_of_goods
				FROM
					{$orderMetaTableName}
					WHERE meta_key = '_wc_cog_order_total_cost'
				GROUP BY post_id
			) cost_of_goods_lookup
			ON cost_of_goods_lookup.order_id = {$this->statsTableName}.order_id";
		}

		return $clauses;
	}

	public function modifySchema($schema)
	{
		$cogsProperties = [
			'cost_of_goods' => [
				'description' => __('Cost of goods', 'wc-cogs'),
				'type'        => 'number',
				'context'     => ['view', 'edit'],
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			],
			'cogs_profit' => [
				'description' => __('Profit (cost deducted from net revenue)', 'wc-cogs'),
				'type'        => 'number',
				'context'     => ['view', 'edit'],
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			],
			'cogs_avg_profit_per_order' => [
				'description' => __('Average profit per order', 'wc-cogs'),
				'type'        => 'number',
				'context'     => ['view', 'edit'],
				'readonly'    => true,
				'indicator'   => true,
				'format'      => 'currency',
			],
		];

		if (isset($schema['totals']['properties']) && is_array($schema['totals']['properties'])) {
			$schema['totals']['properties'] = array_merge($schema['totals']['properties'], $cogsProperties);
		}

		if (isset($schema['intervals']['items']['properties']) && is_array($schema['intervals']['items']['properties'])) {
			$schema['intervals']['items']['properties'] = array_merge($schema['intervals']['items']['properties'], $cogsProperties);
		}

		return $schema;
	}
}
