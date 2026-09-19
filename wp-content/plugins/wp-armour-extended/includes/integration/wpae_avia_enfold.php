<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For https://www.htmlformsplugin.com/

if (!in_array('avia_enfold', wpae_get_blocked_integrations())): 
    add_filter('avf_form_send', 'my_avf_form_send', 10, 4 );
    function my_avf_form_send( $send, $post, $form_params, $avia_form )
    {
        if (wpa_check_is_spam($_POST)){
            do_action('wpa_handle_spammers','avia_builder', $_POST);
        } else {
            return $send;
        }
    }
endif;