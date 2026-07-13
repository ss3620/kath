<?php
/**
 * Registry for referral mediums.
 *
 * @package     affiliate-for-woocommerce/includes/referral-mediums/
 * @since       8.52.0
 * @version     1.1.3
 */

namespace AFWC\Referral_Mediums;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use AFWC\Registry;

if ( ! class_exists( Registry::class, false ) ) {
	include_once AFWC_PLUGIN_DIRPATH . '/includes/abstracts/class-registry.php';
}

if ( ! class_exists( Referral_Medium_Registry::class ) && class_exists( Registry::class ) ) {

	/**
	 * Class for Referral medium registry.
	 */
	class Referral_Medium_Registry extends Registry {

		/**
		 * Variable to hold instance of this class
		 *
		 * @var Referral_Medium_Registry
		 */
		private static $instance = null;

		/**
		 * Get single instance of this class
		 *
		 * @return Referral_Medium_Registry Singleton object of this class
		 */
		public static function get_instance() {
			// Check if instance is already exists.
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Constructor
		 */
		public function __construct() {
			$this->include_files();
			$this->register_mediums();
		}


		/**
		 * Include referral medium files and dependencies.
		 *
		 * @return void
		 */
		public function include_files() {
			include_once 'class-referral-medium-interface.php';
			include_once AFWC_PLUGIN_DIRPATH . '/includes/abstracts/class-abstract-cookie-medium.php';

			$mediums = glob( AFWC_PLUGIN_DIRPATH . '/includes/referral-mediums/*.php' );
			if ( ! empty( $mediums ) && is_array( $mediums ) ) {
				foreach ( $mediums as $file ) {
					if ( is_file( $file ) ) {
						include_once $file;
					}
				}
			}
		}

		/**
		 * Register referral mediums.
		 *
		 * @return void
		 */
		public function register_mediums() {

			/**
			 * Filter for registering referral mediums.
			 *
			 * @since 8.52.0
			 * @param array $mediums Array of referral mediums.
			 */
			$mediums = apply_filters(
				'afwc_referral_mediums',
				array(
					'manual'              => Manual_Assign::class,
					'lifetime_commission' => Lifetime_Commission::class,
					'coupon'              => Referral_Coupon::class,
					'landing_page'        => Landing_Page::class,
					'direct_link'         => Direct_Link::class,
					'link'                => Referral_Link::class,
				),
				array( 'registry' => $this )
			);

			if ( empty( $mediums ) || ! is_array( $mediums ) ) {
				return;
			}

			foreach ( $mediums as $key => $class ) {
				if ( ! class_exists( $class ) ) {
					continue;
				}

				$medium_instance = new $class();

				if ( ! $medium_instance instanceof Referral_Medium_Interface ) {
					continue;
				}

				$this->register( $key, $medium_instance );
			}
		}
	}
}
