<?php
/**
 * Free/Pro compatibility guard.
 *
 * Runs before any other pro code so an incompatible free version can never
 * reach a campaign, frontend, REST or asset class.
 *
 * @package Revenue Pro
 * @since   2.2.0
 */

namespace RevenuePro;

defined( 'ABSPATH' ) || exit;

class Revenue_Pro_Compat {

	/**
	 * Minimum free plugin version this pro version is compatible with.
	 */
	const REQUIRED_FREE_VERSION = '2.3.0';

	/**
	 * Fixed, trusted package source. Never taken from request input.
	 */
	const FREE_PACKAGE_URL = 'https://downloads.wordpress.org/plugin/revenue.zip';

	const FREE_PLUGIN_SLUG = 'revenue/revenue.php';

	/**
	 * Cross-request lock, held as an option so the insert is atomic.
	 */
	const LOCK_OPTION = 'revenue_free_version_updating';

	const LOCK_TIMEOUT = 300;

	/**
	 * Back-off after a failed attempt, so a broken host is not retried on every hit.
	 */
	const FAILURE_TRANSIENT = 'revenue_pro_free_update_failed';

	const FAILURE_BACKOFF = 900;

	/**
	 * Set once per request, so an update can never re-enter itself.
	 *
	 * @var bool
	 */
	private static $attempted = false;

	/**
	 * Decide whether pro may boot.
	 *
	 * @return bool True when the installed free version satisfies this pro version.
	 */
	public static function boot() {
		$state = self::free_state();

		if ( 'ok' === $state ) {
			return true;
		}

		// Incompatible from here on: stay installed, stay active, do nothing.
		if ( 'old' === $state ) {
			self::maybe_schedule_free_update();
		}

		self::register_notice( $state );
		self::load_helper_ajax();

		return false;
	}

	/**
	 * State of the installed free plugin: ok, old, inactive or missing.
	 *
	 * @return string
	 */
	private static function free_state() {
		$version = self::installed_free_version();

		if ( '' === $version ) {
			return file_exists( WP_PLUGIN_DIR . '/' . self::FREE_PLUGIN_SLUG ) ? 'inactive' : 'missing';
		}

		return version_compare( $version, self::REQUIRED_FREE_VERSION, '>=' ) ? 'ok' : 'old';
	}

	/**
	 * Version of the free plugin, or an empty string when it is not active.
	 *
	 * @return string
	 */
	private static function installed_free_version() {
		if ( defined( 'REVENUE_VER' ) ) {
			return (string) REVENUE_VER;
		}

		// Free normally loads before pro, so a missing constant means inactive.
		// Read the header anyway in case something reorders the plugin list.
		if ( ! self::free_is_active() ) {
			return '';
		}

		$file = WP_PLUGIN_DIR . '/' . self::FREE_PLUGIN_SLUG;

		if ( ! file_exists( $file ) ) {
			return '';
		}

		$data = get_file_data( $file, array( 'Version' => 'Version' ), 'plugin' );

		return isset( $data['Version'] ) ? (string) $data['Version'] : '';
	}

	/**
	 * Whether the free plugin is active on this site or network.
	 *
	 * @return bool
	 */
	private static function free_is_active() {
		$active = (array) get_option( 'active_plugins', array() );

		if ( in_array( self::FREE_PLUGIN_SLUG, $active, true ) ) {
			return true;
		}

		if ( is_multisite() ) {
			$network = (array) get_site_option( 'active_sitewide_plugins', array() );

			return isset( $network[ self::FREE_PLUGIN_SLUG ] );
		}

		return false;
	}

	/**
	 * Queue the forced free update for shutdown.
	 *
	 * Replacing the files now would swap them out from under the old free code
	 * that is still executing in this request, so it waits until the response
	 * has been sent and nothing else will run.
	 *
	 * @return void
	 */
	private static function maybe_schedule_free_update() {
		if ( self::$attempted || ! self::update_allowed() ) {
			return;
		}

		self::$attempted = true;

		add_action( 'shutdown', array( __CLASS__, 'run_free_update' ), 5 );
	}

	/**
	 * Guards that must hold before an update is even queued.
	 *
	 * @return bool
	 */
	private static function update_allowed() {
		if ( defined( 'REVENUE_PRO_SKIP_FREE_AUTOUPDATE' ) && REVENUE_PRO_SKIP_FREE_AUTOUPDATE ) {
			return false;
		}

		if ( self::is_developer_install() ) {
			return false;
		}

		if ( function_exists( 'wp_installing' ) && wp_installing() ) {
			return false;
		}

		if ( get_transient( self::FAILURE_TRANSIENT ) ) {
			return false;
		}

		return ! self::lock_is_held();
	}

	/**
	 * Whether the free plugin looks like a developer checkout that must never be
	 * overwritten: a git/svn/hg working copy, or a non-production environment.
	 *
	 * @return bool
	 */
	private static function is_developer_install() {
		$free_path = WP_PLUGIN_DIR . '/' . dirname( self::FREE_PLUGIN_SLUG ) . '/';

		// A .git entry is a directory for a clone and a file for a worktree/submodule.
		foreach ( array( '.git', '.svn', '.hg' ) as $vcs ) {
			if ( file_exists( $free_path . $vcs ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Whether another request currently owns the update lock.
	 *
	 * @return bool
	 */
	private static function lock_is_held() {
		$lock = get_option( self::LOCK_OPTION );

		if ( ! $lock ) {
			return false;
		}

		// A request that died mid-update must not wedge the lock forever.
		if ( ( time() - (int) $lock ) > self::LOCK_TIMEOUT ) {
			delete_option( self::LOCK_OPTION );

			return false;
		}

		return true;
	}

	/**
	 * Take the lock. add_option() fails on a duplicate row, so only one request wins.
	 *
	 * @return bool
	 */
	private static function acquire_lock() {
		return (bool) add_option( self::LOCK_OPTION, time(), '', 'no' );
	}

	/**
	 * Force the free plugin to the required version. Runs on shutdown only.
	 *
	 * @return void
	 */
	public static function run_free_update() {
		// The request that just ran the pro upgrader must not chain into a second install.
		if ( did_action( 'upgrader_process_complete' ) ) {
			return;
		}

		if ( self::lock_is_held() || ! self::acquire_lock() ) {
			return;
		}

		$result = self::install_free();

		delete_option( self::LOCK_OPTION );

		if ( is_wp_error( $result ) ) {
			set_transient( self::FAILURE_TRANSIENT, $result->get_error_message(), self::FAILURE_BACKOFF );

			return;
		}

		delete_transient( self::FAILURE_TRANSIENT );
	}

	/**
	 * Download and overwrite-install the free plugin, then verify the result.
	 *
	 * @return true|\WP_Error
	 */
	private static function install_free() {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-ajax-upgrader-skin.php';

		$tmp_file = download_url( self::FREE_PACKAGE_URL );

		if ( is_wp_error( $tmp_file ) ) {
			return $tmp_file;
		}

		$upgrader = new \Plugin_Upgrader( new \WP_Ajax_Upgrader_Skin() );
		$result   = $upgrader->install( $tmp_file, array( 'overwrite_package' => true ) );

		if ( file_exists( $tmp_file ) ) {
			wp_delete_file( $tmp_file );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $result ) {
			return new \WP_Error( 'revenue_free_update_failed', __( 'The WowRevenue update could not be installed.', 'revenue-pro' ) );
		}

		wp_clean_plugins_cache();

		if ( ! is_plugin_active( self::FREE_PLUGIN_SLUG ) ) {
			$activated = activate_plugin( self::FREE_PLUGIN_SLUG );

			if ( is_wp_error( $activated ) ) {
				return $activated;
			}
		}

		$installed = get_file_data(
			WP_PLUGIN_DIR . '/' . self::FREE_PLUGIN_SLUG,
			array( 'Version' => 'Version' ),
			'plugin'
		);

		if ( empty( $installed['Version'] ) || version_compare( $installed['Version'], self::REQUIRED_FREE_VERSION, '<' ) ) {
			return new \WP_Error( 'revenue_free_version_unmet', __( 'The installed WowRevenue version does not meet the required version after updating.', 'revenue-pro' ) );
		}

		return true;
	}

	/**
	 * Tell the merchant what happened. Nothing is shown on the storefront.
	 *
	 * @param string $state Free plugin state.
	 * @return void
	 */
	private static function register_notice( $state ) {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! class_exists( '\RevenuePro\Revenue_Pro_Notice', false ) ) {
			include_once REVENUE_PRO_PATH . 'includes/class-revenue-pro-notice.php';
		}

		if ( 'missing' === $state ) {
			$callback = 'get_wowrevenue_not_installed_notice';
		} elseif ( 'inactive' === $state ) {
			$callback = 'get_wowrevenue_not_active_notice';
		} else {
			$callback = 'get_wowrevenue_incompatible_version_notice';
		}

		add_action( 'admin_notices', array( Revenue_Pro_Notice::class, $callback ) );
	}

	/**
	 * The install/activate buttons in those notices need their ajax handlers.
	 *
	 * @return void
	 */
	private static function load_helper_ajax() {
		if ( ! is_admin() ) {
			return;
		}

		if ( ! class_exists( '\RevenuePro\Revenue_Pro_Ajax', false ) ) {
			include_once REVENUE_PRO_PATH . 'includes/class-revenue-pro-ajax.php';
			new Revenue_Pro_Ajax();
		}
	}
}
