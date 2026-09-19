<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For https://bricksbuilder.io/

if (!in_array('strong_testimonials', wpae_get_blocked_integrations())): 
    
    add_filter('wpmtst_form_additional_checks', 'wpae_strong_testimonials_extra_validation', 10, 1 );
    
    function wpae_strong_testimonials_extra_validation($form_errors )
    {
        if (wpa_check_is_spam($_POST)){
            do_action('wpa_handle_spammers','strong_testimonials', $_POST);
            $form_errors['post_content']    = $GLOBALS['wpa_error_message'];
        }
        return $form_errors;
    }

endif;