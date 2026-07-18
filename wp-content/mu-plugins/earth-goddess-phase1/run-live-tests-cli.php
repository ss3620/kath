<?php
/**
 * CLI entry: php run-live-tests-cli.php (from this directory) or via:
 * php -d display_errors=1 run-live-tests-cli.php
 *
 * Bootstraps WordPress from ABSPATH three levels up from mu-plugins/.../
 */

$wp_load = dirname( __DIR__, 3 ) . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
	fwrite( STDERR, "wp-load.php not found at {$wp_load}\n" );
	exit( 1 );
}

require_once $wp_load;

if ( ! class_exists( 'EG_Live_Pathway_Tester' ) ) {
	fwrite( STDERR, "EG_Live_Pathway_Tester not loaded. Is the MU-plugin active?\n" );
	exit( 1 );
}

// Elevate to admin capability context for WP cron-less CLI.
$admins = get_users(
	array(
		'role'   => 'administrator',
		'number' => 1,
		'fields' => array( 'ID' ),
	)
);
if ( ! empty( $admins[0] ) ) {
	wp_set_current_user( (int) $admins[0]->ID );
}

$results = EG_Live_Pathway_Tester::run_all();
update_option( EG_Live_Pathway_Tester::OPTION_RESULTS, $results, false );

$out_dir  = dirname( __DIR__, 3 );
$log_path = $out_dir . '/eg-live-pathway-test-results.json';
file_put_contents( $log_path, wp_json_encode( $results, JSON_PRETTY_PRINT ) );

$md   = array();
$md[] = '# Earth Goddess AU – Live Pathway Test Evidence';
$md[] = '';
$md[] = '- Started: ' . $results['started'];
$md[] = '- Finished: ' . $results['finished'];
$md[] = '- Pass: ' . $results['pass'];
$md[] = '- Fail: ' . $results['fail'];
$md[] = '';
$md[] = '| ID | Result | Check | Actual |';
$md[] = '|----|--------|-------|--------|';
foreach ( $results['cases'] as $case ) {
	$md[] = '| `' . $case['id'] . '` | ' . ( ! empty( $case['pass'] ) ? 'PASS' : 'FAIL' ) . ' | ' . str_replace( '|', '\\|', $case['title'] ) . ' | ' . str_replace( '|', '\\|', $case['actual'] ) . ' |';
}
$md[] = '';
$md[] = 'QA user password: `' . EG_Live_Pathway_Tester::PASSWORD . '`';
$md_path = $out_dir . '/eg-live-pathway-test-results.md';
file_put_contents( $md_path, implode( "\n", $md ) );

echo "PASS={$results['pass']} FAIL={$results['fail']}\n";
echo "Wrote {$log_path}\n";
echo "Wrote {$md_path}\n";
exit( $results['fail'] > 0 ? 2 : 0 );
