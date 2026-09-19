<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (!in_array('ninjaforms', wpae_get_blocked_integrations())): 

    add_filter( 'ninja_forms_submit_data', 'my_ninja_forms_submit_data' );

    function my_ninja_forms_submit_data( $form_data ) {
        $firstElement = 0;
        foreach( $form_data[ 'fields' ] as $field ) {
            $ninjaFormData[$field['key']] =  $field['value'];

            if ($firstElement == 0){
                $firstElement                 = $field['id'];  
            }
      }

      if (array_key_exists('wpa_field_name',$form_data['extra'])){
          $ninjaFormData[$form_data['extra']['wpa_field_name']] = $form_data['extra']['wpa_field_value'];   
      }

      if (array_key_exists('alt_s',$form_data['extra'])){
          $ninjaFormData['alt_s'] = $form_data['extra']['alt_s'];   
      }
        
      if (wpa_check_is_spam($ninjaFormData)){
          do_action('wpa_handle_spammers','ninjaForms',$ninjaFormData);
          $form_data['errors']['fields'][$firstElement] = $GLOBALS['wpa_error_message']; 
      }

      return $form_data;
    }
    
endif;