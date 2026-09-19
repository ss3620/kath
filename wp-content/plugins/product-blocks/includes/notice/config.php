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
	'prefix'                 => 'wopb',

	// Base for every image in promos/ — include the image folder, because
	// WPXPO plugins do not agree on its name (img/, images/, photos/…).
	// Promo entries then name only what is below it, and a port changes the
	// folder here instead of in every entry.
	'asset_url'              => WOPB_URL . 'assets/img/',

	// Cross-plugin notice priority (xpo_active_notice_lists filter).
	'priority_key'           => 'wow_store',
	'priority'               => 5,

	'brand_name'             => 'WowStore',
	'brand_color'            => '#DD106C',

	// `source` records where the click came from, so it differs per surface —
	// one key per promos/ file. `campaign` is shared; `medium` is set per promo.
	'utm_source'             => 'db-wowstore-notice',
	'utm_source_hellobar'    => 'db-wowstore-hellobar',
	'utm_source_plugin_meta' => 'db-wowstore-plugin-meta',
	'utm_campaign'           => 'wowstore-dashboard',

);
