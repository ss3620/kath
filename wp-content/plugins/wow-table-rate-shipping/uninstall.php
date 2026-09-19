<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package wow-table-rate-shipping
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Determine whether this site has enabled cleanup on uninstall.
 *
 * @return bool
 */
function wtrs_should_cleanup_on_uninstall() {
	$settings = get_option( 'wtrs-settings', array() );

	if ( ! is_array( $settings ) ) {
		return false;
	}

	$settings = wp_parse_args(
		$settings,
		array(
			'cleanUpOnUninstall' => false,
		)
	);

	return (bool) $settings['cleanUpOnUninstall'];
}

/**
 * Cleanup plugin data for the current blog.
 *
 * @return bool True if cleanup ran.
 */
function wtrs_cleanup_current_blog() {
	if ( ! wtrs_should_cleanup_on_uninstall() ) {
		return false;
	}

	global $wpdb;

	// Core plugin options.
	delete_option( 'wtrs-settings' );
	delete_option( 'wtrs-shipping-rules' );
	delete_option( 'wtrs_rule_import_job_status' );

	// EDD licensing remnants used by WowShipping.
	delete_option( 'edd_wtrs_license_key' );
	delete_option( 'edd_wtrs_license_data' );

	// Carrier credentials stored by CarrierCredentialManager.
	$carrier_secret_like = $wpdb->esc_like( 'wtrs_carrier_secret_' ) . '%';
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $carrier_secret_like )
	);

	// Remove any cached transients created by the plugin (direct DB to support wildcard).
	$patterns = array(
		'_transient_wtrs_%',
		'_transient_timeout_wtrs_%',
		'_site_transient_wtrs_%',
		'_site_transient_timeout_wtrs_%',
	);

	foreach ( $patterns as $pattern ) {
		$like = $wpdb->esc_like( rtrim( $pattern, '%' ) ) . '%';
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like )
		);
	}

	// Remove WooCommerce method instance settings and zone method rows for this plugin.
	$zone_methods_table = $wpdb->prefix . 'woocommerce_shipping_zone_methods';
	$has_zone_table     = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $zone_methods_table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( $has_zone_table === $zone_methods_table ) {
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare( "DELETE FROM {$zone_methods_table} WHERE method_id = %s", 'wtrs_wc_method' ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
	}

	// Delete any WooCommerce options related to our shipping method instances.
	$woo_like = $wpdb->esc_like( 'woocommerce_wtrs_wc_method' ) . '%';
	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $woo_like )
	);

	// Clear scheduled events that belong to this plugin (best-effort).
	$crons = _get_cron_array();
	if ( is_array( $crons ) ) {
		foreach ( $crons as $timestamp => $hooks ) {
			if ( ! is_array( $hooks ) ) {
				continue;
			}
			foreach ( $hooks as $hook => $events ) {
				if ( 0 !== strpos( (string) $hook, 'wtrs_' ) ) {
					continue;
				}
				wp_clear_scheduled_hook( $hook );
			}
		}
	}

	return true;
}

$did_any_cleanup = false;

if ( is_multisite() && function_exists( 'get_sites' ) ) {
	$sites = get_sites( array( 'fields' => 'ids' ) );

	if ( is_array( $sites ) ) {
		foreach ( $sites as $site_id ) {
			switch_to_blog( (int) $site_id );
			$did_any_cleanup = wtrs_cleanup_current_blog() || $did_any_cleanup;
			restore_current_blog();
		}
	}

	// If we cleaned any site, also remove network-level transients for the same prefix.
	if ( $did_any_cleanup ) {
		global $wpdb;
		if ( ! empty( $wpdb->sitemeta ) ) {
			$site_patterns = array(
				'_site_transient_wtrs_%',
				'_site_transient_timeout_wtrs_%',
			);

			foreach ( $site_patterns as $pattern ) {
				$like = $wpdb->esc_like( rtrim( $pattern, '%' ) ) . '%';
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare( "DELETE FROM {$wpdb->sitemeta} WHERE meta_key LIKE %s", $like )
				);
			}
		}
	}
} else {
	$did_any_cleanup = wtrs_cleanup_current_blog();
}
