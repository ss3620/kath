<?php // phpcs:ignore
/**
 * Cleanup old files from prad_option_files folders.
 *
 * @package PRAD
 */
namespace PRAD\Includes\Cron;

use PRAD\Includes\Xpo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Cleanup
 *
 * Handles scheduled cleanup of old files in prad_option_files folders.
 *
 * @package PRAD
 */
class Cleanup {
	/**
	 * Constructor.
	 * Adds the cleanup action. The cron event itself is scheduled on plugin
	 * activation (see product-addons.php) rather than here, since this class
	 * is only reachable via the plugins_loaded-deferred init chain.
	 */
	public function __construct() {
		add_action( 'prad_cleanup_upload_files', array( $this, 'cleanup_old_files_callback' ) );
	}

	/**
	 * Delete files older than 5 days from prad_option_files subfolders.
	 *
	 * @return void
	 */
	public function cleanup_old_files_callback() {
		$upload_dir = wp_upload_dir();
		$base_dir   = trailingslashit( $upload_dir['basedir'] ) . 'prad_option_files/';
		$now        = time();

		$temp_days_raw            = Xpo::get_prad_settings_item( 'uploadTempRemove', '' );
		$order_placed_days_raw    = Xpo::get_prad_settings_item( 'uploadOrderPlacedRemove', '' );
		$order_completed_days_raw = Xpo::get_prad_settings_item( 'uploadOrderCompletedRemove', '' );

		$folders = array(
			'temp'            => $temp_days_raw,
			'order_placed'    => $order_placed_days_raw,
			'order_completed' => $order_completed_days_raw,
		);

		foreach ( $folders as $folder => $days_raw ) {
			if ( empty( $days_raw ) || absint( $days_raw ) <= 0 ) {
				continue;
			}
			$days = absint( $days_raw );
			$dir  = $base_dir . $folder;
			if ( is_dir( $dir ) ) {
				$files = glob( $dir . '/*' );
				if ( ! empty( $files ) ) {
					foreach ( $files as $file ) {
						if ( is_file( $file ) && ( $now - filemtime( $file ) ) > ( $days * DAY_IN_SECONDS ) ) {
							wp_delete_file( $file );
						}
					}
				}
			}
		}
	}
}
