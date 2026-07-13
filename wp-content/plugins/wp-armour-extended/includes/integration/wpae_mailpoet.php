<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For https://www.mailpoet.com/

if (!in_array('mailpoet', wpae_get_blocked_integrations())): 
    
    add_action('mailpoet_subscription_before_subscribe', 'wpae_mailpoet_extra_validation', 10, 3 );

    function wpae_mailpoet_extra_validation($data, $segmentIds, $form){
        if (wpa_check_is_spam($_POST)){
            do_action('wpa_handle_spammers','mailpoet', $_POST);
            throw new \MailPoet\UnexpectedValueException($GLOBALS['wpa_error_message']);
        }
    }
endif;