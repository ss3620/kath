<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (!in_array('formcraft_form', wpae_get_blocked_integrations())): 
    
    add_action('formcraft_before_save', 'wpae_formcraft_extra_validation', 10, 4);

    function wpae_formcraft_extra_validation($content, $meta, $raw_content, $integrations){
        if (wpa_check_is_spam($_POST)){
            global $fc_final_response;
            do_action('wpa_handle_spammers','formcraft_form', $_POST);
            $fc_final_response['failed'] = $GLOBALS['wpa_error_message'];
            echo json_encode($fc_final_response);
            wp_die();            
        }
    }
endif;