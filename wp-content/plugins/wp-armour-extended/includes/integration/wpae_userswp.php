<?php
if ( ! defined( 'ABSPATH' ) ) exit;
// For BuddyPress And BuddyBoss

if (!in_array('userswp', wpae_get_blocked_integrations())): 

    add_filter( 'uwp_validate_result', 'wpae_userswp_extra_validation', 10, 3 );

    function wpae_userswp_extra_validation( $result, $context, $data ) {
        $contexts = array('login', 'forgot', 'register');
        if (in_array($context, $contexts)) {
            if (wpa_check_is_spam($_POST)){
                do_action('wpa_handle_spammers','usersWP_'.$context,$_POST);        
                $result = new WP_Error('spam_detected', $GLOBALS['wpa_error_message']);
            }                        
        }
        return $result;
    }

    add_action('uwp_template_fields', 'wpae_userswp_add_initiator_field', 10, 1);

    function wpae_userswp_add_initiator_field($context) {
        $contexts = array('forgot', 'register'); // login uses jQuery('form.uwp-login-form').append(wpa_hidden_field);
        if (in_array($context, $contexts)) {
            echo '<input type="hidden" class="wpae_initiator" name="wpae_initiator" value="" />';
        }
    }

endif;