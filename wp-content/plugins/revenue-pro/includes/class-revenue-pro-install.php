<?php

/**
 * Installation related functions and actions.
 *
 * @package RevenueX
 * @version 1.0.0
 */

namespace RevenuePro;

class Revenue_Pro_Install {


	/**
	 * Perform Installation
	 *
	 * @return void
	 */
	public function install() {
		// Set installation time if user install it first time.
		$this->set_installed_time();
	}
	/**
	 * Set installed time
	 *
	 * @return void
	 */
	public function set_installed_time() {
		if ( empty( get_option( 'revenue_pro_installed_time' ) ) ) {
			update_option( 'revenue_pro_installed_time', time() );
		}
	}
}
