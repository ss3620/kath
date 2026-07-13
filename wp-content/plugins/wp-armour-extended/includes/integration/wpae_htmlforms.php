<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For https://www.htmlformsplugin.com/

if (!in_array('htmlforms', wpae_get_blocked_integrations())): 

    add_filter( 'hf_form_html', 'wpae_htmlform_add_initiator_field', 10, 2);

    function wpae_htmlform_add_initiator_field($html, $form){
    	$dom = new DOMDocument();
        $dom->loadHTML( $html );    
        $domForm = $dom->getElementsByTagName('form')->item(0);
        $input = $dom->createElement('input');
        $input->setAttribute('type', 'hidden');
        $input->setAttribute('id', 'wpae_initiator');
        $input->setAttribute('name', 'wpae_initiator');
        $input->setAttribute('class', 'wpae_initiator');
        $domForm->appendChild($input);
        $html = $dom->saveHTML();
        return $html;
    }

    add_filter( 'hf_validate_form', function( $error_code, $form, $data ) {
    	if (wpa_check_is_spam($data)){
            do_action('wpa_handle_spammers','html_forms',$_POST);        
            $error_code = 'spam_detected';
        }
        return $error_code;
    }, 10, 3 );

    add_filter( 'hf_form_message_spam_detected', function( $message ) {
        return $GLOBALS['wpa_error_message'];
    });

endif;