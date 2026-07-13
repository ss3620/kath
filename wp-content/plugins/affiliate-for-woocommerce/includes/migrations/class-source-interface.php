<?php
/**
 * Interface for data migration sources/plugins.
 *
 * @package     affiliate-for-woocommerce/includes/migration
 * @since       8.48.0
 * @version     1.0.0
 */

namespace AFWC\Migrations;

if ( ! interface_exists( Source_Interface::class ) ) {

	/**
	 * Defines the contract for affiliate source plugins
	 * used in migration to AFWC.
	 */
	interface Source_Interface {

		/**
		 * Get affiliate ID from the source plugin's cookie, if available.
		 *
		 * @return int Return affiliate ID.
		 */
		public function get_affiliate_from_cookie();

		/**
		 * Get affiliate ID for an order, if available.
		 *
		 * @param int $order_id Order ID.
		 *
		 * @return int Return affiliate ID.
		 */
		public function get_affiliate_from_order( $order_id );

		/**
		 * Stop tracking in the source plugin.
		 *
		 * @return void.
		 */
		public function stop_tracking();

		/**
		 * Check if migration data is complete for this source.
		 *
		 * @return bool True if migration is complete.
		 */
		public function is_completed();

		/**
		 * Fetch affiliates to migrate.
		 *
		 * @return array Affiliates data.
		 */
		public function get_affiliates();

		/**
		 * Fetch setups to migrate.
		 *
		 * @return array Setups data.
		 */
		public function get_setups();

		/**
		 * Map the both plugin's user meta.
		 *
		 * @return array User meta mapping.
		 */
		public function get_user_meta_map();
	}
}
