<?php
// this is the URL our updater / license checker pings. This should be the URL of the site with EDD installed
define( 'WPAE_STORE_URL', 'https://dineshkarki.com.np' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

// the download ID for the product in Easy Digital Downloads
define( 'WPAE_ITEM_ID', 375752 ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

// the name of the product in Easy Digital Downloads
define( 'WPAE_ITEM_NAME', 'WP Armour Extended' ); // you should use your own CONSTANT name, and be sure to replace it throughout this file

// the name of the settings page for the license input to be displayed
define( 'WPAE_PLUGIN_LICENSE_PAGE', 'wp-armour&tab=license' );

if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	// load our custom updater
	include dirname( __FILE__ ) . '/EDD_SL_Plugin_Updater.php';
}

/**
 * Initialize the updater. Hooked into `init` to work with the
 * wp_version_check cron job, which allows auto-updates.
 */
function wpae_plugin_updater() {

	// To support auto-updates, this needs to run during the wp_version_check cron job for privileged users.
	$doing_cron = defined( 'DOING_CRON' ) && DOING_CRON;
	if ( ! current_user_can( 'manage_options' ) && ! $doing_cron ) {
		return;
	}

	// retrieve our license key from the DB
	$license_key = trim( get_option( 'wpae_license_key' ) );

	// setup the updater
	$wpae_updater = new EDD_SL_Plugin_Updater(
		WPAE_STORE_URL,
		'wp-armour-extended/wp-armour-extended.php',
		array(
			'version' => $GLOBALS['wpae_version'],                    // current version number
			'license' => $license_key,             // license key (used get_option above to retrieve from DB)
			'item_id' => WPAE_ITEM_ID,       // ID of the product
			'author'  => 'Easy Digital Downloads', // author of this plugin
			'beta'    => false,
		)
	);

}
add_action( 'init', 'wpae_plugin_updater' );

/**
 * Registers the license key setting in the options table.
 *
 * @return void
 */
function wpae_register_option() {
	register_setting( 'wpae_license', 'wpae_license_key', 'wpae_sanitize_license' );
}
add_action( 'admin_init', 'wpae_register_option' );

/**
 * Sanitizes the license key.
 *
 * @param string  $new The license key.
 * @return string
 */
function wpae_sanitize_license( $new ) {
	$old = get_option( 'wpae_license_key' );
	if ( $old && $old !== $new ) {
		delete_option( 'wpae_license_status' ); // new license has been entered, so must reactivate
	}

	return sanitize_text_field( $new );
}

/**
 * Activates the license key.
 *
 * @return void
 */
function wpae_activate_license() {

	// listen for our activate button to be clicked
	if ( ! isset( $_POST['wpae_license_activate'] ) ) {
		return;
	}

	// run a quick security check
	if ( ! check_admin_referer( 'wpae_nonce', 'wpae_nonce' ) ) {
		return; // get out if we didn't click the Activate button
	}

	// retrieve the license from the database
	$license = trim( get_option( 'wpae_license_key' ) );
	if ( ! $license ) {
		$license = ! empty( $_POST['wpae_license_key'] ) ? sanitize_text_field( $_POST['wpae_license_key'] ) : '';
	}
	if ( ! $license ) {
		return;
	}

	// data to send in our API request
	$api_params = array(
		'edd_action'  => 'activate_license',
		'license'     => $license,
		'item_id'     => WPAE_ITEM_ID,
		'item_name'   => rawurlencode( WPAE_ITEM_NAME ), // the name of our product in EDD
		'url'         => home_url(),
		'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
	);

	// Call the custom API.
	$response = wp_remote_post(
		WPAE_STORE_URL,
		array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params,
		)
	);

		// make sure the response came back okay
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
		} else {
			$message = __( 'An error occurred, please try again.' );
		}
	} else {

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( false === $license_data->success ) {

			switch ( $license_data->error ) {

				case 'expired':
					$message = sprintf(
						/* translators: the license key expiration date */
						__( 'Your license key expired on %s.', 'edd-sample-plugin' ),
						date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
					);
					break;

				case 'disabled':
				case 'revoked':
					$message = __( 'Your license key has been disabled.', 'wp-armour-extended' );
					break;

				case 'missing':
					$message = __( 'Invalid license.', 'wp-armour-extended' );
					break;

				case 'invalid':
				case 'site_inactive':
					$message = __( 'Your license is not active for this URL.', 'wp-armour-extended' );
					break;

				case 'item_name_mismatch':
					/* translators: the plugin name */
					$message = sprintf( __( 'This appears to be an invalid license key for %s.', 'wp-armour-extended' ), WPAE_ITEM_NAME );
					break;

				case 'no_activations_left':
					$message = __( 'Your license key has reached its activation limit.', 'wp-armour-extended' );
					break;

				default:
					$message = __( 'An error occurred, please try again.', 'wp-armour-extended' );
					break;
			}
		}
	}

		// Check if anything passed on a message constituting a failure
	if ( ! empty( $message ) ) {
		$redirect = add_query_arg(
			array(
				'page'          => WPAE_PLUGIN_LICENSE_PAGE,
				'sl_activation' => 'false',
				'message'       => rawurlencode( $message ),
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect );
		exit();
	}

	// $license_data->license will be either "valid" or "invalid"
	if ( 'valid' === $license_data->license ) {
		update_option( 'wpae_license_key', $license );
	}
	update_option( 'wpae_license_status', $license_data->license );
	wp_safe_redirect( admin_url( 'admin.php?page=' . WPAE_PLUGIN_LICENSE_PAGE ) );
	exit();
}
add_action( 'admin_init', 'wpae_activate_license' );

/**
 * Deactivates the license key.
 * This will decrease the site count.
 *
 * @return void
 */
function wpae_deactivate_license() {

	// listen for our activate button to be clicked
	if ( isset( $_POST['wpae_license_deactivate'] ) ) {

		// run a quick security check
		if ( ! check_admin_referer( 'wpae_nonce', 'wpae_nonce' ) ) {
			return; // get out if we didn't click the Activate button
		}

		// retrieve the license from the database
		$license = trim( get_option( 'wpae_license_key' ) );

		// data to send in our API request
		$api_params = array(
			'edd_action'  => 'deactivate_license',
			'license'     => $license,
			'item_id'     => WPAE_ITEM_ID,
			'item_name'   => rawurlencode( WPAE_ITEM_NAME ), // the name of our product in EDD
			'url'         => home_url(),
			'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
		);

		// Call the custom API.
		$response = wp_remote_post(
			WPAE_STORE_URL,
			array(
				'timeout'   => 15,
				'sslverify' => false,
				'body'      => $api_params,
			)
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

			if ( is_wp_error( $response ) ) {
				$message = $response->get_error_message();
			} else {
				$message = __( 'An error occurred, please try again.' );
			}

			$redirect = add_query_arg(
				array(
					'page'          => WPAE_PLUGIN_LICENSE_PAGE,
					'sl_activation' => 'false',
					'message'       => rawurlencode( $message ),
				),
				admin_url( 'admin.php' )
			);

			wp_safe_redirect( $redirect );
			exit();
		}

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		/*// $license_data->license will be either "deactivated" or "failed"
		if ( 'deactivated' === $license_data->license ) {
			delete_option( 'wpae_license_status' );
			update_option('wpae_license_key','');
		}*/	

		/* DEACTIVATE THE KEY EVEN IF IT FAILS */
		if ( 'deactivated' === $license_data->license || 'failed' === $license_data->license ) {
		    delete_option( 'wpae_license_status' );
		    update_option('wpae_license_key', '');
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . WPAE_PLUGIN_LICENSE_PAGE ) );
		exit();

	}
}
add_action( 'admin_init', 'wpae_deactivate_license' );

/**
 * Checks if a license key is still valid.
 * The updater does this for you, so this is only needed if you want
 * to do something custom.
 *
 * @return void
 */
function wpae_check_license() {

	$license = trim( get_option( 'wpae_license_key' ) );

	$api_params = array(
		'edd_action'  => 'check_license',
		'license'     => $license,
		'item_id'     => WPAE_ITEM_ID,
		'item_name'   => rawurlencode( WPAE_ITEM_NAME ),
		'url'         => home_url(),
		'environment' => function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production',
	);

	// Call the custom API.
	$response = wp_remote_post(
		WPAE_STORE_URL,
		array(
			'timeout'   => 15,
			'sslverify' => false,
			'body'      => $api_params,
		)
	);

	if ( is_wp_error( $response ) ) {
		return false;
	}

	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	if ( 'valid' === $license_data->license ) {
		echo 'valid';
		exit;
		// this license is still valid
	} else {
		echo 'invalid';
		exit;
		// this license is no longer valid
	}
}

/**
 * This is a means of catching errors from the activation method above and displaying it to the customer
 */
function wpae_admin_notices() {
	if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {

		switch ( $_GET['sl_activation'] ) {

			case 'false':
				$message = urldecode( $_GET['message'] );
				?>
				<div class="error">
					<p><?php echo wp_kses_post( $message ); ?></p>
				</div>
				<?php
				break;

			case 'true':
			default:
				// Developers can put a custom success message here for when activation is successful if they way.
				break;

		}
	}
}
add_action( 'admin_notices', 'wpae_admin_notices' );