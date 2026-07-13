<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For Everest Forms


if (!in_array('everest_forms', wpae_get_blocked_integrations())):   
   // add_action('everest_forms_process_before', 'wpae_everest_forms_extra_validation', 10, 1);  
   
   add_filter( 'everest_forms_process_initial_errors', 'wpae_everest_forms_extra_validation', 10, 2 );

   function wpae_everest_forms_extra_validation( $errors, $form_data ) {
      if (wpa_check_is_spam($_POST)){
           do_action('wpa_handle_spammers','everest_forms', $form_data['entry']['form_fields']);  
           $errors[ $form_data['id'] ]['header'] = $GLOBALS['wpa_error_message'];
       }
       return $errors;
   }

endif;