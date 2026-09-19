<?php
/*
Plugin Name: WP Armour Extended - Honeypot Anti Spam
Plugin URI: http://wordpress.org/plugins/honeypot/
Description: Extra tools and feature for WP Armour
Author: Dnesscarkey
Version: 1.41
Author URI: https://dineshkarki.com.np
*/

include ('wpae_updater.php');
include ('includes/wpae_config.php');
include ('includes/wpae_functions.php');

add_action( 'init', function(){
	if( !is_admin() ){ // ONLY BLOCK SPAM IF IT IS NOT ADMIN PANEL
		include 'includes/integration/wpae_mc4wp.php';
		include 'includes/integration/wpae_s2member.php';
		
	}
});

add_action( 'plugins_loaded', function(){
	if( !is_admin() ){ 		
		include 'includes/integration/wpae_buddypress.php';
		include 'includes/integration/wpae_quform.php';	
		//include 'includes/integration/wpae_happyforms.php';	NOT COMPLETE
		include 'includes/integration/wpae_ultimate_member.php';				

	}
	include 'includes/integration/wpae_edd.php';
	include 'includes/integration/wpae_woocommerce.php';	
	include 'includes/integration/wpae_htmlforms.php';
	include 'includes/integration/wpae_avia_enfold.php';	
	include 'includes/integration/wpae_bricks_builder.php';	
	include 'includes/integration/wpae_bricksforge_form.php';	
	include 'includes/integration/wpae_forminator.php';	
	include 'includes/integration/wpae_strong_testimonials.php';	
	include 'includes/integration/wpae_formcraft.php';
	include 'includes/integration/wpae_jetform_builder.php';
	include 'includes/integration/wpae_mailpoet.php';
	include 'includes/integration/wpae_ws_form.php';
	include 'includes/integration/wpae_userswp.php';
	include 'includes/integration/wpae_mailin_brevo.php';
	include 'includes/integration/wpae_beaver_builder.php';
	include 'includes/integration/wpae_sure_forms.php';
	include 'includes/integration/wpae_everest_forms.php';
	include 'includes/integration/wpae_bit_forms.php';
	
});

include 'includes/integration/wpae_ninjaforms.php';	

add_action( 'admin_enqueue_scripts', 'wpae_admin_assets');
add_action( 'wp_enqueue_scripts','wpae_load_scripts');
add_filter( 'wpa_tabs_filter', 'wpae_modify_tabs', 10, 1 );
add_action(	'wpa_handle_spammers','wpae_record_spammers',10,2);
add_action( 'plugins_loaded','wpae_is_ip_blocked');
add_action( 'plugins_loaded','wpae_plugin_update');
add_action( 'admin_notices', 'wpae_admin_notice');
add_filter( 'wpa_widget_content', 'wpae_widget_content', 10, 1 );

register_activation_hook( __FILE__, 'wpae_create_db_table' );