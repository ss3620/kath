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

abstract class AbstractAnalyticsReport
{
	/**
	 * Array of custom reports we've created. These should correspond to the `SELECT` column names that we add.
	 * Key should be the column name and value should be a function to cast the result to.
	 *
	 * @var array<string, string>
	 */
	protected array $customReportFields = [];

	public function addHooks() : void
	{
		// add our SELECT statement
		add_filter( 'woocommerce_admin_report_columns', [$this, 'addSelect'], 10, 3 );

		// cast the values we SELECT
		add_filter('woocommerce_rest_reports_column_types', [$this, 'addColumnTypes'], 10, 2);
	}

	/**
	 * Adds our custom fields as supported query args. This is necessary to get COGs to appear in the table "Summary"
	 * response.
	 *
	 * @param mixed $queryVars
	 * @return mixed
	 */
	public function addCogsFields($queryVars)
	{
		if (is_array($queryVars) && isset($queryVars['fields']) && ! empty($this->customReportFields)) {
			$queryVars['fields'] = array_unique(array_merge($queryVars['fields'], array_keys($this->customReportFields)));
		}

		return $queryVars;
	}

	/**
	 * Adds our custom fields as supported "orderby" columns.
	 *
	 * @param array|mixed $orderByValues
	 * @return array|mixed
	 */
	public function addCogsToOrderBy($orderByValues)
	{
		if (is_array($orderByValues) && ! empty($this->customReportFields)) {
			$orderByValues = array_unique(array_merge($orderByValues, array_keys($this->customReportFields)));
		}

		return $orderByValues;
	}

	/**
	 * Adds our `SELECT` statements to the MySQL query.
	 *
	 * @param array|mixed $reportColumns
	 * @param string|mixed $context
	 * @param string|mixed $table_name
	 * @return mixed
	 */
	abstract public function addSelect($reportColumns, $context, $table_name);

	/**
	 * Adds a custom `JOIN` clause to the query to join on metadata.
	 *
	 * @param mixed|array $clauses
	 * @return mixed|array
	 */
	abstract public function addMetaJoin($clauses);

	/**
	 * Specify the values of each of our {@see static::$customReportFields} to instruct WooCommerce on how to cast them.
	 *
	 * @param mixed $columnTypes
	 * @param mixed $data
	 * @return mixed
	 */
	public function addColumnTypes($columnTypes, $data)
	{
		if (is_array($columnTypes)) {
			$columnTypes = array_merge($columnTypes, $this->customReportFields);
		}

		return $columnTypes;
	}
}
