<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For https://www.wpbeaverbuilder.com/


if (!in_array('beaver_builder', wpae_get_blocked_integrations())): 

   add_action( 'fl_module_contact_form_before_send', 'wpae_beaver_builder_extra_validation', 10, 5 );

   function wpae_beaver_builder_extra_validation( $mailto, $subject, $template, $headers, $settings ) {
      if (wpa_check_is_spam($_POST)){
         do_action('wpa_handle_spammers','beaver_builder', $form_data);
         wp_send_json(array(
               'error' => true,
               'message' => _($GLOBALS['wpa_error_message'])           
         ));
         wp_die();
      }
   }

endif;