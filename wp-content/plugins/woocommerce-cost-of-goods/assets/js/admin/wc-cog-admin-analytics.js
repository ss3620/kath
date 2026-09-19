/** @global wc_cog_admin_analytics */

const { addFilter } = wp.hooks;

// @link https://github.com/woocommerce/woocommerce/blob/trunk/packages/js/currency/src/utils.tsx
const wcCogsGetCurrency = () => {
	const currencySettings = window.wc.wcSettings.getSetting('currency');
	return window.wc.currency.CurrencyFactory(currencySettings);
}

/**
 * Adds custom headers to the table.
 *
 * @param {string} endpoint
 */
function wcCogsGetTableHeadersForEndpoint(endpoint) {
	let newEndpointHeaders = [];

	if (['revenue', 'products', 'categories'].includes(endpoint)) {
		newEndpointHeaders = newEndpointHeaders.concat([
			{
				label: wc_cog_admin_analytics.i18n.cost_of_goods,
				key: "cost_of_goods",
				isSortable: true,
				isNumeric: true
			},
			{
				label: wc_cog_admin_analytics.i18n.profit,
				key: "cogs_profit",
				isSortable: true,
				isNumeric: true
			}
		])
	}

	if (endpoint === 'revenue') {
		newEndpointHeaders.push(
			{
				label: wc_cog_admin_analytics.i18n.average_profit_per_order,
				key: "cogs_avg_profit_per_order",
				isSortable: true,
				isNumeric: true
			}
		)
	}

	// @link https://github.com/woocommerce/woocommerce/blob/0ff266356830457f01f996d49e9647f7c846c5f4/plugins/woocommerce/client/admin/client/analytics/report/stock/table.js
	if (endpoint === 'stock') {
		newEndpointHeaders = newEndpointHeaders.concat([
			{
				label: wc_cog_admin_analytics.i18n.value_at_retail,
				key: "cogs_retail_value",
				isSortable: false,
				isNumeric: true
			},
			{
				label: wc_cog_admin_analytics.i18n.value_at_cost,
				key: "cogs_cost_value",
				isSortable: false,
				isNumeric: true
			}
		])
	}

	return newEndpointHeaders;
}

/**
 * Populates the row data for each item.
 *
 * @param {string} endpoint
 * @param {object} item
 */
function wcCogsGetTableItemsForEndpoint(endpoint, item) {
	let newEndpointItems = [];

	if (endpoint === 'revenue') {
		newEndpointItems = newEndpointItems.concat([
			{
				display: wcCogsGetCurrency().render(item.subtotals.cost_of_goods), // @link https://github.com/woocommerce/woocommerce/blob/0ff266356830457f01f996d49e9647f7c846c5f4/plugins/woocommerce/client/admin/client/analytics/report/revenue/table.js#L180-L181
				value: wcCogsGetCurrency().formatDecimal(item.subtotals.cost_of_goods)
			},
			{
				display: wcCogsGetCurrency().render(item.subtotals.cogs_profit),
				value: wcCogsGetCurrency().formatDecimal(item.subtotals.cogs_profit)
			},
			{
				display: wcCogsGetCurrency().render(item.subtotals.cogs_avg_profit_per_order),
				value: wcCogsGetCurrency().formatDecimal(item.subtotals.cogs_avg_profit_per_order)
			}
		])
	}

	if (['products', 'categories'].includes(endpoint)) {
		// NOTE: the item structure is different here compared to the revenue report (which has a `subtotals` property)
		newEndpointItems = newEndpointItems.concat([
			{
				display: wcCogsGetCurrency().render(item.cost_of_goods),
				value: wcCogsGetCurrency().formatDecimal(item.cost_of_goods)
			},
			{
				display: wcCogsGetCurrency().render(item.cogs_profit),
				value: wcCogsGetCurrency().formatDecimal(item.cogs_profit)
			}
		])
	}

	if  (endpoint === 'stock') {
		newEndpointItems = newEndpointItems.concat([
			{
				display: item.manage_stock ? wcCogsGetCurrency().render(item.cogs_retail_value) : wc_cog_admin_analytics.i18n.na,
				value: item.manage_stock ? wcCogsGetCurrency().formatDecimal(item.cogs_retail_value) : null
			},
			{
				display: item.manage_stock ?  wcCogsGetCurrency().render(item.cogs_cost_value) : wc_cog_admin_analytics.i18n.na,
				value: item.manage_stock ? wcCogsGetCurrency().formatDecimal(item.cogs_cost_value) : null
			}
		])
	}

	return newEndpointItems;
}

/**
 * Adds custom fields to the "Summary" at the bottom of the table. These are all the total numbers added up.
 *
 * @param {object} reportTableData
 */
function wcCogsGetTableSummaryForTable(reportTableData) {
	let newEndpointSummaries = [];

	if (reportTableData.endpoint === 'revenue') {
		newEndpointSummaries = newEndpointSummaries.concat([
			{
				label: wc_cog_admin_analytics.i18n.total_cost_of_goods,
				value: wcCogsGetCurrency().render(reportTableData.totals.cost_of_goods)
			},
			{
				label: wc_cog_admin_analytics.i18n.total_profit,
				value: wcCogsGetCurrency().render(reportTableData.totals.cogs_profit)
			},
			{
				label: wc_cog_admin_analytics.i18n.average_profit_per_order,
				value: wcCogsGetCurrency().render(reportTableData.totals.cogs_avg_profit_per_order)
			}
		]);
	}

	if (['products', 'categories'].includes(reportTableData.endpoint)) {
		newEndpointSummaries = newEndpointSummaries.concat([
			{
				label: wc_cog_admin_analytics.i18n.total_cost_of_goods,
				value: wcCogsGetCurrency().render(reportTableData.totals.cost_of_goods)
			},
			{
				label: wc_cog_admin_analytics.i18n.total_profit,
				value: wcCogsGetCurrency().render(reportTableData.totals.cogs_profit)
			}
		]);
	}

	if (reportTableData.endpoint === 'stock') {
		newEndpointSummaries = newEndpointSummaries.concat([
			{
				label: wc_cog_admin_analytics.i18n.total_value_at_retail,
				value: reportTableData.totals.cogs_total_retail_value === null
					? wc_cog_admin_analytics.i18n.na
					: wcCogsGetCurrency().render(reportTableData.totals.cogs_total_retail_value)
			},
			{
				label: wc_cog_admin_analytics.i18n.total_value_at_cost,
				value: reportTableData.totals.cogs_total_retail_value === null
					? wc_cog_admin_analytics.i18n.na
					: wcCogsGetCurrency().render(reportTableData.totals.cogs_total_cost_value)
			}
		]);
	}

	return newEndpointSummaries;
}

/**
 * Add custom headers and item data to the table.
 */
const wcCogsAddFieldsToTables = (reportTableData) => {
	// @link https://github.com/woocommerce/woocommerce/blob/0ff266356830457f01f996d49e9647f7c846c5f4/plugins/woocommerce/client/admin/client/analytics/report/revenue/table.js#L55
	const newHeaders = [
		...reportTableData.headers,
		...wcCogsGetTableHeadersForEndpoint(reportTableData.endpoint),
	];

	const newRows = reportTableData.rows.map((row, index) => {
		const item = reportTableData.items.data[index];
		return [
			...row,
			...wcCogsGetTableItemsForEndpoint(reportTableData.endpoint, item)
		];
	});

	const newSummary = [
		...reportTableData.summary,
		...wcCogsGetTableSummaryForTable(reportTableData)
	];

	reportTableData.headers = newHeaders;
	reportTableData.rows = newRows;
	reportTableData.summary = newSummary;

	return reportTableData;
};

addFilter("woocommerce_admin_report_table", "wc-cog", wcCogsAddFieldsToTables);

/***************************** Filters */

const cogsFilter = {
	key: 'cost_of_goods',
	label: wc_cog_admin_analytics.i18n.cost_of_goods,
	order: 'desc',
	orderby: 'cost_of_goods',
	type: 'currency',
	isReverseTrend: false
};

const cogsProfitFilter = {
	key: 'cogs_profit',
	label: wc_cog_admin_analytics.i18n.profit,
	order: 'desc',
	orderby: 'cogs_profit',
	type: 'currency',
	isReverseTrend: false,
	labelTooltipText: wc_cog_admin_analytics.i18n.profit_tooltip
};

/**
 * Revenue Chart Filters
 * @link https://github.com/woocommerce/woocommerce/blob/0ff266356830457f01f996d49e9647f7c846c5f4/plugins/woocommerce/client/admin/client/analytics/report/revenue/config.js#L23
 */
const wcCogsRevenueChartFilters = (filters) => {
	return [
		...filters,
		cogsFilter,
		cogsProfitFilter,
		{
			key: 'cogs_avg_profit_per_order',
			label: wc_cog_admin_analytics.i18n.average_profit_per_order,
			order: 'desc',
			orderby: 'cogs_avg_profit_per_order',
			type: 'currency',
			isReverseTrend: false
		}
	]
};

addFilter('woocommerce_admin_revenue_report_charts', 'wc-cog', wcCogsRevenueChartFilters);

/**
 * Products Chart Filters
 */
const wcCogsProductsChartFilters = (filters) => {
	return [
		...filters,
		cogsFilter,
		cogsProfitFilter
	]
};

addFilter('woocommerce_admin_products_report_charts', 'wc-cog', wcCogsProductsChartFilters);

/**
 * Categories Chart Filters
 * @link https://github.com/woocommerce/woocommerce/blob/0ff266356830457f01f996d49e9647f7c846c5f4/plugins/woocommerce/client/admin/client/analytics/report/categories/config.js#L33-L55
 */
const wcCogsCategoriesChartFilters = (filters) => {
	return [
		...filters,
		cogsFilter,
		cogsProfitFilter
	]
};

addFilter('woocommerce_admin_categories_report_charts', 'wc-cog', wcCogsCategoriesChartFilters);
