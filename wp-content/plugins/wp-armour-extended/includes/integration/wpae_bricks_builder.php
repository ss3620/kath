<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For https://bricksbuilder.io/

if (!in_array('bricks_builder_form', wpae_get_blocked_integrations())): 
    add_filter('bricks/form/validate', 'wpae_bricks_builder_extra_validation', 10, 2 );
    
    function wpae_bricks_builder_extra_validation($errors, $form )
    {
        $form_fields   = $form->get_fields();
        if (wpa_check_is_spam($form_fields)){
            do_action('wpa_handle_spammers','bricks_form', $form_fields);
            $errors[] = esc_html__( $GLOBALS['wpa_error_message'], 'bricks' );
        }
        return $errors;
    }

endif;