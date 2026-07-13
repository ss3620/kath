<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For Bit Forms

if (!in_array('bit_forms', wpae_get_blocked_integrations())): 
    
    add_action('bitform_save_entry', 'wpae_bit_forms_extra_validation', 10, 3);

    function wpae_bit_forms_extra_validation($formManagerInstance, $submittedData, $formId) {
        if (wpa_check_is_spam($_POST)){
            do_action('wpa_handle_spammers','bit_forms', $_POST);
            wp_send_json_error([
                'message' => $GLOBALS['wpa_error_message']
            ], 400);
           exit();
        }
    }

endif;