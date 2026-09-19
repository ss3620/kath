<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (!in_array('mc4wp', wpae_get_blocked_integrations())): 

    add_filter('mc4wp_form_errors', 'wpae_mc4wp_extra_validation', 10, 2);
    add_filter('mc4wp_form_messages', 'wpae_mc4wp_extra_validation_message');

    function wpae_mc4wp_extra_validation( $errors, $form) {
        if (wpa_check_is_spam($_POST)){
        	do_action('wpa_handle_spammers','mailchimp',$_POST);
            $errors[] = 'wpae_spam_detected';
        }
        return $errors;
    }

    function wpae_mc4wp_extra_validation_message($messages) {
      $messages['wpae_spam_detected'] = $GLOBALS['wpa_error_message'];
      return $messages;
    }

endif;