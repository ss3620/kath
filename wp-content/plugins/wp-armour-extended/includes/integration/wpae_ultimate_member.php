<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For Ultimate Member

if (!in_array('ultimate_member', wpae_get_blocked_integrations())): 

    add_action('um_after_register_fields', 'wpae_ultimate_member_add_initiator_field');
   
    function wpae_ultimate_member_add_initiator_field($args ){
    	echo '<input type="hidden" id="wpae_initiator" class="wpae_initiator" name="wpae_initiator" value="" />';
    }

    add_action('um_submit_form_errors_hook_','wpae_ultimate_member_extra_validation', 999, 1);
    function wpae_ultimate_member_extra_validation( $args ) {
        
        if (wpa_check_is_spam($args)){
            do_action('wpa_handle_spammers','ultimate_member',$args);        
            UM()->form()->add_error( 'user_login', $GLOBALS['wpa_error_message']);
        }
    }

endif;