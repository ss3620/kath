<?php

namespace RevenuePro;
/**
 * Contains Common Functions
 */
class Revenue_Pro_Functions {

	public function get_name() {
		return __('WowRevenue Pro','revenue-pro');
	}

    public function get_license_key() {
        return get_option( 'revenue_pro_license_key', '' );
    }

    public function has_valid_key() {
        
    }

    public function get_admin_menu_slug() {
        return apply_filters( 'revenue_menu_slug', 'revenue' );
    }

}