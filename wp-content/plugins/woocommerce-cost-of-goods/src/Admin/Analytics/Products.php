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

use SkyVerge\WooCommerce\PluginFramework\v5_12_1 as Framework;

/**
 * Integrates with the Analytics > Products page.
 *
 * @see \Automattic\WooCommerce\Admin\API\Reports\Products\DataStore
 * @see \Automattic\WooCommerce\Admin\API\Reports\Products\Stats\DataStore
 */
class Products extends AbstractAnalyticsReport
{
	protected string $orderProductLookupTable;

	protected array $customReportFields = [
		'cost_of_goods' => 'floatval',
		'cogs_profit' => 'floatval',
	];

	public function __construct()
	{
		global $wpdb;
		$this->orderProductLookupTable = $wpdb->prefix.'wc_order_product_lookup';
	}

	public function addHooks() : void
	{
		parent::addHooks();

		// add our field to query args
		add_filter('woocommerce_analytics_products_query_args', [$this, 'addCogsFields']);
		add_filter('woocommerce_analytics_products_stats_query_args', [$this, 'addCogsFields']);

		// allow ordering by CoGs
		add_filter('woocommerce_analytics_orderby_enum_reports/products/stats', [$this, 'addCogsToOrderBy']);
		add_filter('woocommerce_analytics_orderby_enum_reports/products', [$this, 'addCogsToOrderBy']);

		// add a JOIN on the meta table
		add_filter('woocommerce_analytics_clauses_join_products_stats_total', [$this, 'addMetaJoin']);
		add_filter('woocommerce_analytics_clauses_join_products_stats_interval', [$this, 'addMetaJoin']); // `products_stats` context -- handles the filters

		add_filter('woocommerce_analytics_clauses_join_products_subquery', [$this, 'addMetaJoin']); // `products` context -- handles the "Products" table
	}

	/**
	 * @inheritDoc
	 */
	public function addSelect($reportColumns, $context, $table_name)
	{
		if  (! is_array($reportColumns) || ! in_array($context, ['products_stats', 'products'], true)) {
			return $reportColumns;
		}

		$reportColumns['cost_of_goods'] = 'COALESCE(SUM(cost_of_goods), 0) AS cost_of_goods';
		$reportColumns['cogs_profit'] = "SUM({$this->orderProductLookupTable}.product_net_revenue) - COALESCE(SUM(cost_of_goods), 0) AS cogs_profit";

		return $reportColumns;
	}

	/**
	 * @inheritDoc
	 */
	public function addMetaJoin($clauses)
	{
		global $wpdb;
		$orderItemMetaTableName = $wpdb->prefix.'woocommerce_order_itemmeta';

		$clauses[] = "LEFT JOIN (
				SELECT
					order_item_id,
					SUM(meta_value) AS cost_of_goods
				FROM
					{$orderItemMetaTableName}
					WHERE meta_key = '_wc_cog_item_total_cost'
				GROUP BY order_item_id
			) cost_of_goods_lookup
			ON cost_of_goods_lookup.order_item_id = {$this->orderProductLookupTable}.order_item_id";

		return $clauses;
	}
}
