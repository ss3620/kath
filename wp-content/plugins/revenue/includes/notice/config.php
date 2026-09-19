<?php
/**
 * Per-plugin identity for all three promo surfaces — the ONE file to edit when
 * copying this folder into a sibling WPXPO plugin.
 *
 * Read it via Notice::config(), never a bare require: it returns an array, so
 * require_once would give a second caller `true`.
 *
 * Still manual when porting (see README): the namespace in class-notice.php
 * and the text domain in __() — both compile-time, so neither can live here.
 */

defined( 'ABSPATH' ) || exit;

return array(

	// Builds promo keys, the nonce action, and every transient/query-arg name,
	// so two WPXPO plugins on one site never collide.
	'prefix'                 => 'revx',

	// Everything up to and including the image directory, trailing slash
	// included, so promos/ append only the campaign path. A sibling plugin
	// keeping its artwork in assets/img/ (or photos/, or a CDN) changes it
	// here once and every surface follows.
	'asset_url'              => REVENUE_URL . 'assets/images/',

	// Cross-plugin notice priority (xpo_active_notice_lists filter).
	'priority_key'           => 'wowrevenue',
	'priority'               => 4,

	'brand_name'             => 'WowRevenue',
	'brand_color'            => '#00a464',

	// `source` records where the click came from, so it differs per surface —
	// one key per promos/ file. `campaign` is shared; `medium` is set per promo.
	'utm_source'             => 'db-revenue-notice',
	'utm_source_hellobar'    => 'db-revenue-hellobar',
	'utm_source_plugin_meta' => 'db-revenue-plugin-meta',
	'utm_campaign'           => 'revenue-dashboard',

);
