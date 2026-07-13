<?php
if ( ! defined( 'ABSPATH' ) ) exit; 

if (!in_array('happyforms', wpae_get_blocked_integrations())): 

    add_action( 'happyforms_form_submit_after', 'wpaesss_woocommerce_add_initiator_field',10, 2);
    function wpaesss_woocommerce_add_initiator_field($form) {
        echo '<input type="hidden" id="wpae_initiator" class="wpae_initiator" name="wpae_initiator" value="" />';
    }

    add_filter( 'happyforms_validate_submission', 'wpa_happyforms_extra_validation', 10, 3 );  
    function wpa_happyforms_extra_validation( $is_valid, $request, $form ) {
        if (wpa_check_is_spam($_POST)){
            do_action('wpa_handle_spammers','happyforms', $_POST);
            $is_valid = false;
        }    
        return $is_valid;
    }

endif;