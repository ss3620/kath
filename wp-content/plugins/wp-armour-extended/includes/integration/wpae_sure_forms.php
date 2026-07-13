<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For https://sureforms.com/ & https://wordpress.org/plugins/sureforms/


if (!in_array('sure_forms', wpae_get_blocked_integrations())): 

   add_action('srfm_before_submission', 'wpae_sure_forms_extra_validation', 10, 1);

   function wpae_sure_forms_extra_validation($submission_data) {
      //print_r($submission_data);
      if (wpa_check_is_spam($_POST)){
         do_action('wpa_handle_spammers','sure_forms', $form_data);  

         $response = [
            'success' => false,
            'message' => _($GLOBALS['wpa_error_message'])
         ];

         echo json_encode($response);
         die();
      }
   }

endif;