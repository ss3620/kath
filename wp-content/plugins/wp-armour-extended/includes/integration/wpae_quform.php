<?php
if ( ! defined( 'ABSPATH' ) ) exit;

if (!in_array('quform', wpae_get_blocked_integrations())): 

	add_filter('quform_pre_validate', function (array $result, Quform_Form $form) {
	    if (wpa_check_is_spam($_POST)){
	        do_action('wpa_handle_spammers','quForm',$_POST);
	        $result = array(
			    'type' => 'error',
			    'error' => array(
			        'enabled' => true,
			        'title' => '',
			        'content' => $GLOBALS['wpa_error_message']
			    )
			);
	    }
	    return $result;
	}, 10, 2);

endif;