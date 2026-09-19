<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (!in_array('edd', wpae_get_blocked_integrations())): 

    add_action( 'edd_purchase_form_user_info' , 'wpae_edd_add_initiator_field');
    add_action( 'edd_register_form_fields_bottom', 'wpae_edd_add_initiator_field' );
    add_action( 'edd_purchase_form_user_info_fields' , 'wpae_edd_add_initiator_field');

    function wpae_edd_add_initiator_field(){
    	echo '<input type="hidden" id="wpae_initiator" class="wpae_initiator" name="wpae_initiator" value="" />';
    }

    function wpae_edd_extra_validation($data) {
    	if (wpa_check_is_spam($_POST)){
            do_action('wpa_handle_spammers','edd', $_POST);
            edd_set_error( 'spam_detected',$GLOBALS['wpa_error_message']);
        }
    }
    add_action('edd_checkout_error_checks', 'wpae_edd_extra_validation');
    add_action( 'edd_pre_process_register_form', 'wpae_edd_extra_validation' );   
    
endif;