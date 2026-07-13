<?php
if ( ! defined( 'ABSPATH' ) ) exit; 

if (!in_array('woocommerce', wpae_get_blocked_integrations())):

    add_action('wc_ajax_ppc-create-order', 'wpae_ppc_create_order_extra_validation', 1);
    add_action('wc_ajax_nopriv_ppc-create-order', 'wpae_ppc_create_order_extra_validation', 1); // For non-logged-in users

    function wpae_ppc_create_order_extra_validation() {
        
        $raw_data = file_get_contents('php://input');
        $decoded_data = json_decode($raw_data, true);
        $form_data  = array();
        
        if (isset($decoded_data['form_encoded'])) {
            parse_str($decoded_data['form_encoded'], $form_data);
        }
        
        if (wpa_check_is_spam($form_data)){
            do_action('wpa_handle_spammers','woocommerce_checkout',$form_data);
            wc_add_notice($GLOBALS['wpa_error_message'], 'error');
            wp_die($GLOBALS['wpa_error_message']);
        }
    }

    add_action('wc_ajax_wc_ppcp_frontend_request', 'wpae_wc_ppcp_frontend_request_extra_validation', 1);
    add_action('wc_ajax_nopriv_wc_ppcp_frontend_request', 'wpae_wc_ppcp_frontend_request_extra_validation', 1); // For non-logged-in users

    function wpae_wc_ppcp_frontend_request_extra_validation() {
        $raw_data = file_get_contents('php://input');
        $data = json_decode($raw_data, true);
        if (wpa_check_is_spam($data)){
            do_action('wpa_handle_spammers','woocommerce_checkout',$data);
            wc_add_notice($GLOBALS['wpa_error_message'], 'error');
            wp_die($GLOBALS['wpa_error_message']);
        }
    }

    add_action('wc_ajax_checkout', 'wpae_wc_ajax_checkout_extra_validation', 1);
    add_action('wc_ajax_nopriv_checkout', 'wpae_wc_ajax_checkout_extra_validation', 1); // For non-logged-in users

    function wpae_wc_ajax_checkout_extra_validation() {
        if (wpa_check_is_spam($_POST)){
            do_action('wpa_handle_spammers','woocommerce_checkout',$_POST);
            wc_add_notice($GLOBALS['wpa_error_message'], 'error');
            wp_die($GLOBALS['wpa_error_message']);
        }
    }
    
    add_action( 'woocommerce_after_checkout_validation', 'wpae_woocommerce_extra_checkout_validation', 10, 2);

    function wpae_woocommerce_extra_checkout_validation( $fields, $errors) {
        if( empty( $errors->get_error_codes() ) ) {
            
            $wpa_checking = true;

            if (isset($_POST['payment_method']) && ($_POST['payment_method'] == 'ppec_paypal' )){
                $wpa_checking = false;
            }

            if ( isset($_POST['payment_request_type']) && ( ($_POST['payment_request_type'] == 'google_pay' ) || ($_POST['payment_request_type'] == 'apple_pay' ) ) ) {
                $wpa_checking = false;
            }

            if ($wpa_checking){
                if (wpa_check_is_spam($_POST)){
                    do_action('wpa_handle_spammers','woocommerce_checkout',$_POST);
                    $errors->add( 'validation',$GLOBALS['wpa_error_message'] );
                }
            }
            
        } 
        return $errors;
    }
    
    add_action( 'woocommerce_checkout_billing', 'wpae_woocommerce_add_initiator_field');
    function wpae_woocommerce_add_initiator_field( $checkout ) {
    	echo '<input type="hidden" id="wpae_initiator" class="wpae_initiator" name="wpae_initiator" value="" />';
    }

endif; // EOF BLOCK INTEGRATION CHECK


if (!in_array('woocommerce_register', wpae_get_blocked_integrations())):     
   
    add_filter( 'woocommerce_registration_errors', 'wpa_woocommerce_register_validation', 10, 3 );
      
    function wpa_woocommerce_register_validation( $errors, $username, $email ) {
        if (wpa_check_is_spam($_POST)){
            $errors->add( 'reg_email', $GLOBALS['wpa_error_message']);
        }    
        return $errors;
    }
    
    add_action( 'woocommerce_register_form', 'wpae_woocommerce_register_add_initiator_field');
    function wpae_woocommerce_register_add_initiator_field( $checkout ) {
        echo '<input type="hidden" id="wpae_initiator" class="wpae_initiator" name="wpae_initiator" value="" />';
    }

endif; // EOF BLOCK INTEGRATION CHECK