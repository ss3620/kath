<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For https://bricksforge.io //

if (!in_array('bricksforge_form', wpae_get_blocked_integrations())): 
    
    add_filter('bricksforge/pro_forms/before_submit', 'wpae_bricksforge_extra_validation', 10, 1 );
    
    function wpae_bricksforge_extra_validation($form_data)
    {
        if (wpa_check_is_spam($form_data)){
            do_action('wpa_handle_spammers','bricksforge_form', $form_data);
            wp_send_json_error(array(
                'message' => __($GLOBALS['wpa_error_message'], 'bricksforge'),
            ));
        }        
    }
endif;