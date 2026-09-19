<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For forminator

if (!in_array('forminator', wpae_get_blocked_integrations())): 
    
    add_filter('forminator_custom_form_submit_errors', 'wpae_forminator_extra_validation', 99, 3 );
    
    function wpae_forminator_extra_validation($submit_errors, $form_id, $field_data_array)
    {
        if (wpa_check_is_spam($_POST)){
            do_action('wpa_handle_spammers','forminator_form', $_POST);
            $submit_errors[][$field_data_array[0]['name']] = $GLOBALS['wpa_error_message'];
        }
        return $submit_errors;
    }

endif;