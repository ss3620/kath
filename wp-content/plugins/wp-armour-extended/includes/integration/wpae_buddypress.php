<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For BuddyPress And BuddyBoss

if (!in_array('buddypress', wpae_get_blocked_integrations())): 

    add_action( 'bp_after_signup_profile_fields' , 'wpae_buddypress_add_initiator_field'); // REGISTRATION
    add_action( 'bp_after_login_widget_loggedout' , 'wpae_buddypress_add_initiator_field'); // LOGIN

    function wpae_buddypress_add_initiator_field(){
    	echo '<input type="hidden" id="wpae_initiator" class="wpae_initiator" name="wpae_initiator" value="" />';
    }

    function wpae_buddypress_extra_validation($result) {
    	if (wpa_check_is_spam($_POST)){
            do_action('wpa_handle_spammers','buddyPress',$_POST);        
            $result['errors']->add( 'user_name', $GLOBALS['wpa_error_message']);
        }
        return $result;
    }
    add_action('bp_core_validate_user_signup', 'wpae_buddypress_extra_validation');

endif;