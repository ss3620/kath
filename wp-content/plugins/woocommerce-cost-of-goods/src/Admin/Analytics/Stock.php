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

use Exception;
use WC_COG_Product;
use WC_Product;
use WP_Post;
use WP_Query;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Integrates with the Analytics > Stock page.
 * @see \Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\DataStore
 * @see \Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\Controller for table summary
 * @see \Automattic\WooCommerce\Admin\API\Reports\Stock\Controller for data within the table rows
 */
class Stock
{
	protected int $maxProductsToQuery = 10000;

	public function addHooks() : void
	{
		$this->maxProductsToQuery = (int) apply_filters('woocommerce_cogs_max_products_for_stock_valuation_query', $this->maxProductsToQuery);

		add_filter('woocommerce_rest_prepare_report_stock', [$this, 'filterProductItem'], 10, 3);
		add_filter('woocommerce_analytics_stock_stats_query', [$this, 'addToStockTotals'], 10);
	}

	/**
	 * Adds data to each individual item within a table row.
	 *
	 * @see \Automattic\WooCommerce\Admin\API\Reports\Stock\Controller::prepare_item_for_response()
	 *
	 * @param WP_REST_Response|mixed $response The response object.
	 * @param WC_Product|mixed $product The original product object.
	 * @param WP_REST_Request|mixed $request Request used to generate the response.
	 * @return WP_REST_Response|mixed
	 */
	public function filterProductItem($response, $product, $request)
	{
		if (! $response instanceof WP_REST_Response || ! $product instanceof WC_Product) {
			return $response;
		}

		$data = $response->get_data();
		if (is_array($data)) {
			$data['cogs_retail_value'] = (float) $product->get_price() * (float) $product->get_stock_quantity();
			$data['cogs_cost_value'] = (float) WC_COG_Product::get_cost($product) * (float) $product->get_stock_quantity();
		}

		$response->set_data($data);

		return $response;
	}

	/**
	 * Adds our "value" totals to the array, for use in the table summary.
	 *
	 * This seems to work different to other report queries. Rather than doing one big query with a ton of selects,
	 * we have a bunch of individually-queried values ({@see \Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\DataStore::get_data()})
	 *
	 * @see \Automattic\WooCommerce\Admin\API\Reports\Stock\Stats\Query::get_data()
	 */
	public function addToStockTotals($results)
	{
		if (is_array($results)) {
			return array_merge($results, $this->queryTotalValues());
		}

		return $results;
	}

	protected function queryTotalValues() : array
	{
		$results = [
			'cogs_total_retail_value' => 0,
			'cogs_total_cost_value' => 0,
		];

		try {
			$productIds = $this->getProductIdsWithStock();
			if (empty($productIds)) {
				return $results;
			}

			foreach($this->queryProductObjects($productIds) as $product) {
				$stockQuantity = (int) $product->get_stock_quantity();
				$cost      = (float) \WC_COG_Product::get_cost( $product );
				$price     = (float) $product->get_price();

				$results['cogs_total_retail_value'] += $price * $stockQuantity;
				$results['cogs_total_cost_value'] += $cost * $stockQuantity;
			}
		} catch(Exception $e) {
			// set to null to convey we have no results at all
			$results['cogs_total_retail_value'] = null;
			$results['cogs_total_cost_value'] = null;
		}

		return $results;
	}

	/**
	 * Gets an array of product IDs that have manage stock enabled and at least 1 item in stock.
	 * We do this instead of a meta query because it's more performant. There are indexes on the `stock_status`
	 * and `stock_quantity` columns, whereas there's no index on `meta_value` were we to join on that instead.
	 *
	 * @return int[]
	 * @throws Exception
	 */
	protected function getProductIdsWithStock() : array
	{
		global $wpdb;

		// do a count first so we can see how many we're working with
		$count = (int) $wpdb->get_var($this->getProductIdsWithStockSql('COUNT(*)'));

		if ($count > $this->maxProductsToQuery) {
			throw new Exception(sprintf('Too many products to count total valuation (%s)', number_format($count)));
		}

		return array_map(
			'intval',
			$wpdb->get_col($this->getProductIdsWithStockSql('product_id'))
		);
	}

	protected function getProductIdsWithStockSql(string $select) : string
	{
		global $wpdb;
		$productMetaLookup = $wpdb->prefix.'wc_product_meta_lookup';

		return "SELECT {$select} FROM {$productMetaLookup}
		WHERE stock_status = 'instock' AND stock_quantity IS NOT NULL";
	}

	/**
	 * @param int[] $idsToInclude
	 * @return WC_Product[]
	 */
	protected function queryProductObjects(array $idsToInclude) : array
	{
		$query = new WP_Query([
			'post__in'       => $idsToInclude,
			'post_type'      => ['product', 'product_variation'],
			'posts_per_page' => $this->maxProductsToQuery,
			'post_status'    => ['publish', 'private'],
			'tax_query'      => [
				[
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => ['variable'],
					'operator' => 'NOT IN',
				],
			],
			'orderby' => 'ID',
			'no_found_rows' => true,
		]);

		return array_filter(
			array_map(
				fn($post) => $post instanceof WP_Post ? wc_get_product($post) : null,
				$query->posts
			)
		);
	}
}
