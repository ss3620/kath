<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For https://jetformbuilder.com/

use Jet_Form_Builder\Exceptions\Action_Exception;

if (!in_array('jetform_builder', wpae_get_blocked_integrations())): 
    
    add_action('jet-form-builder/form-handler/before-send', 'wpae_jetform_builder_extra_validation', 10, 1);

    function wpae_jetform_builder_extra_validation($form){  

        if (wpa_check_is_spam($_POST)){
            do_action('wpa_handle_spammers','jetform_builder', $_POST);
            throw ( new Action_Exception( $GLOBALS['wpa_error_message'] ) )->dynamic_error();
        }
    }

    // REMOVE LATER for version 1.35

    add_filter('jet-form-builder/before-end-form', 'wpae_jetform_builder_add_initiator_field', 10, 1); 

     function wpae_jetform_builder_add_initiator_field($form){
        return  '<input type="hidden" id="wpae_initiator" class="wpae_initiator" name="wpae_initiator" value="" />';
    }

    // EOF REMOVE LATER for version 1.35

endif;