<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For https://wsform.com/

if (!in_array('ws_form', wpae_get_blocked_integrations())): 

	add_filter( 'wsf_submit_validate', 'wpae_ws_form_extra_validation', 10, 3 );

    function wpae_ws_form_extra_validation( $field_error_action_array, $post_mode, $submit ) {
    	// Only process validation if the form is submitted and not saved
	    if ( $post_mode !== 'submit' ) {
	        return $field_error_action_array;
	    }

		if (wpa_check_is_spam($_POST)){
            do_action('wpa_handle_spammers','ws_form', $_POST);
            $field_error_action_array[] = array(
	            'action' => 'message',
	            'message' => $GLOBALS['wpa_error_message'],
        	);
        }
        return $field_error_action_array;
    }
endif;
