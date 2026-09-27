<?php
/**
 * CLI entry: lists (and optionally deletes) the all-digit bot accounts created by the
 * September 2026 signup flood.
 *
 * Dry run, writes eg-spam-users.csv to the WordPress root:
 *   php purge-spam-users-cli.php
 *
 * Same list, then delete:
 *   php purge-spam-users-cli.php --delete
 *
 * Options:
 *   --since=YYYY-MM-DD  Earliest registration date to consider. Default 2026-09-01.
 */

$wp_load = dirname( __DIR__, 3 ) . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
	fwrite( STDERR, "wp-load.php not found at {$wp_load}\n" );
	exit( 1 );
}

require_once $wp_load;
require_once ABSPATH . 'wp-admin/includes/user.php';

$delete = in_array( '--delete', $argv, true );
$since  = '2026-09-01';

foreach ( $argv as $arg ) {
	if ( 0 === strpos( $arg, '--since=' ) ) {
		$since = substr( $arg, 8 );
	}
}

if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $since ) ) {
	fwrite( STDERR, "--since must be YYYY-MM-DD\n" );
	exit( 1 );
}

/**
 * Roles that mark an account as worth keeping regardless of its username.
 *
 * @var string[]
 */
$protected_roles = array(
	'administrator',
	'editor',
	'author',
	'contributor',
	'shop_manager',
	'vip_shopper',
	'affiliate_business_builder',
	'ambassador',
	'wholesale_starter',
	'wholesale_preferred',
	'wholesale_elite',
);

global $wpdb;

$rows = $wpdb->get_results(
	$wpdb->prepare(
		"SELECT ID, user_login, user_email, user_registered
		 FROM {$wpdb->users}
		 WHERE user_login REGEXP '^[0-9]+$'
		   AND user_registered >= %s
		 ORDER BY user_registered ASC",
		$since . ' 00:00:00'
	)
);

$matched = array();
$skipped = array();

foreach ( $rows as $row ) {
	$user  = get_user_by( 'id', (int) $row->ID );
	$local = current( explode( '@', (string) $row->user_email ) );

	if ( ! $user ) {
		continue;
	}

	if ( array_intersect( $protected_roles, (array) $user->roles ) ) {
		$skipped[] = array( $row, 'protected role: ' . implode( '/', (array) $user->roles ) );
		continue;
	}

	if ( $local !== $row->user_login ) {
		$skipped[] = array( $row, 'email local part does not match username' );
		continue;
	}

	if ( count_user_posts( (int) $row->ID ) > 0 ) {
		$skipped[] = array( $row, 'has authored content' );
		continue;
	}

	if ( function_exists( 'wc_get_orders' ) ) {
		$orders = wc_get_orders(
			array(
				'customer_id' => (int) $row->ID,
				'limit'       => 1,
				'return'      => 'ids',
			)
		);

		if ( ! empty( $orders ) ) {
			$skipped[] = array( $row, 'has WooCommerce orders' );
			continue;
		}
	}

	$matched[] = $row;
}

$csv_path = dirname( __DIR__, 3 ) . '/eg-spam-users.csv';
$handle   = fopen( $csv_path, 'w' );
fputcsv( $handle, array( 'ID', 'user_login', 'user_email', 'user_registered', 'action' ) );

foreach ( $matched as $row ) {
	fputcsv(
		$handle,
		array( $row->ID, $row->user_login, $row->user_email, $row->user_registered, $delete ? 'deleted' : 'would delete' )
	);
}

foreach ( $skipped as $entry ) {
	list( $row, $reason ) = $entry;
	fputcsv( $handle, array( $row->ID, $row->user_login, $row->user_email, $row->user_registered, 'kept - ' . $reason ) );
}

fclose( $handle );

$deleted = 0;

if ( $delete ) {
	foreach ( $matched as $row ) {
		if ( wp_delete_user( (int) $row->ID ) ) {
			++$deleted;
		}
	}
}

echo 'Scanned since ' . $since . "\n";
echo 'Matched: ' . count( $matched ) . "\n";
echo 'Kept: ' . count( $skipped ) . "\n";
echo $delete ? 'Deleted: ' . $deleted . "\n" : "Dry run - nothing deleted. Re-run with --delete to remove them.\n";
echo 'Wrote ' . $csv_path . "\n";
