<?php
/**
 * Plugin Name: WowShipping - Weight Based Table Rate Shipping with Live Rates for UPS, USPS, DHL
 * Description: Flexible table rate shipping plugin with live rates to solve any complex shipping scenario using weight, cart, location, and 30+ conditions.
 * Version:     1.1.33
 * Author:      WPXPO
 * Author URI:  https://www.wpxpo.com/about
 * Requires Plugins: woocommerce
 * Text Domain: wow-table-rate-shipping
 * Domain Path: /languages
 * License:     GPLv3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package wow-table-rate-shipping
 */

use WTRS\Includes\Init;

defined( 'ABSPATH' ) || exit;

// Define Vars.
define( 'WTRS_VER', '1.1.33' );
define( 'WTRS_URL', plugin_dir_url( __FILE__ ) );
define( 'WTRS_BASE', plugin_basename( __FILE__ ) );
define( 'WTRS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WTRS_RULE_VER', '3' );
define( 'WTRS_WOO_MARKETPLACE', false );

if ( ! function_exists( 'wtrs_autoloader' ) ) {
	/**
	 * Autoloader function
	 *
	 * @param string $class_name class name.
	 * @return void
	 */
	function wtrs_autoloader( $class_name ) {
		$namespace = 'WTRS\\';
		$base_dir  = WTRS_PATH;

		$len = strlen( $namespace );
		if ( strncmp( $namespace, $class_name, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class_name, $len );

		$segments  = explode( '\\', $relative_class );
		$file_name = array_pop( $segments );
		$subfolder = strtolower( implode( '/', $segments ) );

		$prefix = ( strpos( $subfolder, 'traits' ) !== false ) ? 'trait-' : 'class-';

		$file_name = strtolower(
			preg_replace( '/([a-z])([A-Z])/', '$1-$2', $file_name )
		);

		$file = rtrim( $base_dir . $subfolder, '/' ) . '/' . $prefix . $file_name . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
			return;
		}
	}

}

spl_autoload_register( 'wtrs_autoloader' );

// Composer dependencies autoloading.
require_once WTRS_PATH . 'vendor_prefixed/vendor/autoload.php';

if ( ! function_exists( 'wtrs_init' ) ) {
	/**
	 * Init plugin
	 *
	 * @return void
	 */
	function wtrs_init() {
		new Init();
	}
}

wtrs_init();
