<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (!in_array('mailin_brevo', wpae_get_blocked_integrations())): 

    add_action( 'init', 'handle_custom_form_submission', 5 ); // Set priority to 5
    function handle_custom_form_submission() {
        // Check if the form is being submitted.
        if ( isset( $_POST['sib_form_action'] ) && $_POST['sib_form_action'] === 'subscribe_form_submit' ) {
            if (wpa_check_is_spam($_POST)){
                do_action('wpa_handle_spammers','mailin_brevo', $_POST);                
                $error_message = $GLOBALS['wpa_error_message'];
                wp_send_json(
                    array(
                        'status' => 'failure',
                        'msg' => array("errorMsg" => $GLOBALS['wpa_error_message']),
                    )
                );
                wp_die( $error_message );
            }
        }
    }
    
    
endif;