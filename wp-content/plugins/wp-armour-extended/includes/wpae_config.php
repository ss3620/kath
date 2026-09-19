<?php
$GLOBALS['wpae_version'] 						= '1.41';

$GLOBALS['wpae_db_version'] 					= get_option('wpae_db_version');
$GLOBALS['wpae_data_per_page'] 					= '50';

$GLOBALS['wpae_log_spammer_ip'] 				= get_option('wpae_log_spammer_ip');
$GLOBALS['wpae_log_spam_data'] 					= get_option('wpae_log_spam_data');
$GLOBALS['wpae_auto_block_ip']					= get_option('wpae_auto_block_ip');
$GLOBALS['wpae_auto_delete_submission']			= get_option('wpae_auto_delete_submission');
$GLOBALS['wpae_enable_2level_check']			= get_option('wpae_enable_2level_check');
$GLOBALS['wpae_blocked_words']					= get_option('wpae_blocked_words');


$GLOBALS['wpae_integrations']					= array(
														
														'woocommerce' => array(
																				'name' => 'WooCommerce Checkout',
																				'path' => 'woocommerce/woocommerce.php',
																				'type' => 'plugin'	
																			 ),

														'woocommerce_register' => array(
																				'name' => 'WooCommerce Registration',
																				'path' => 'woocommerce/woocommerce.php',
																				'type' => 'plugin'	
																			 ),
														'avia_enfold' => array(
																				'name' => 'Avia Enfold',
																				'path' => 'woocommerce/woocommerce.php',
																				'type' => 'theme'
																			 ),
														'edd' => array(
																				'name' => 'Easy Digital Downloads',
																				'path' => 'easy-digital-downloads/easy-digital-downloads.php',
																				'type' => 'plugin'	
																			 ),
														'happyforms' => array(
																				'name' => 'Happy Forms',
																				'path' => 'woocommerce/woocommerce.php',
																				'type' => 'plugin'	
																			 ),
														'htmlforms' => array(
																				'name' => 'HTML Forms',
																				'path' => 'woocommerce/woocommerce.php',
																				'type' => 'plugin'	
																			 ),
														'ninjaforms' => array(
																				'name' => 'Ninja Forms',
																				'path' => 'woocommerce/woocommerce.php',
																				'type' => 'plugin'	
																			 ),
														'quform' => array(
																				'name' => 'QU Form',
																				'path' => 'woocommerce/woocommerce.php',
																				'type' => 'plugin'	
																			 ),
														'buddypress' => array(
																				'name' => 'BuddyPress + Youzify',
																				'path' => 'buddypress/bp-loader.php',
																				'type' => 'plugin'	
																			 ),
														'mc4wp' => array(
																				'name' => 'Mailchimp For Wordpress',
																				'path' => 'mc4wp/mc4wp.php',
																				'type' => 'plugin'	
																			 ),
														'ultimate_member' => array(
																				'name' => 'Ultimate Member',
																				'path' => 'ultimate-member/ultimate-member.php',
																				'type' => 'plugin'	
																			 ),
														'bricks_builder_form' => array(
																				'name' => 'Bricks Builder Form',
																				'path' => 'bricks/style.css',
																				'type' => 'theme'	
																			 ),
														'bricksforge_form' => array(
																				'name' => 'Bricksforge Pro Form',
																				'path' => 'bricksforge/bricksforge.php',
																				'type' => 'plugin'	
																			 ),
														'forminator' => array(
																				'name' => 'Forminator Form',
																				'path' => 'forminator/forminator.php',
																				'type' => 'plugin'	
																			 ),
														'strong_testimonials' => array(
																				'name' => 'Strong Testimonials',
																				'path' => 'strong-testimonials/strong-testimonials.php',
																				'type' => 'plugin'	
																			 ),
														'formcraft_form' => array(
																				'name' => 'Formcraft Form',
																				'path' => 'formcraft3/formcraft-main.php',
																				'type' => 'plugin'	
																			 ),
														'jetform_builder' => array(
																				'name' => 'JetForm Builder',
																				'path' => 'jetformbuilder/jet-form-builder.php',
																				'type' => 'plugin'	
																			 ),
														'wp_login_form' => array(
																				'name' => 'WP Login Form',
																				'path' => 'wordpress/login',
																				'type' => 'wordpress'	
																			 ),
														'mailpoet' => array(
																				'name' => 'Mailpoet',
																				'path' => 'mailpoet/mailpoet.php',
																				'type' => 'plugin'	
																			 ),
														'ws_form' => array(
																				'name' => 'WS Form',
																				'path' => 'ws-form/ws-form.php',
																				'type' => 'plugin'	
																			 ),
														'bbpress' => array(
																				'name' => 'bbPress',
																				'path' => '',
																				'type' => 'plugin'	
																			 ),
														'calderaforms' => array(
																				'name' => 'Caldera Forms',
																				'path' => '',
																				'type' => 'plugin'	
																			 ),
														'contactform7' => array(
																				'name' => 'Contact Form 7',
																				'path' => '',
																				'type' => 'plugin'	
																			 ),
														'diviengineform' => array(
																				'name' => 'Divi Engine Form',
																				'path' => '',
																				'type' => 'plugin'	
																			 ),
														'diviform' => array(
																				'name' => 'Divi Form',
																				'path' => '',
																				'type' => 'Theme'	
																			 ),
														'elementor' => array(
																				'name' => 'Elementor Form',
																				'path' => '',
																				'type' => 'Theme'	
																			 ),
														'fluentform' => array(
																				'name' => 'Fluent Form',
																				'path' => '',
																				'type' => 'Plugin'	
																			 ),
														'formidable' => array(
																				'name' => 'Formidable Form',
																				'path' => '',
																				'type' => 'Plugin'	
																			 ),
														'gravityforms' => array(
																				'name' => 'Gravity Forms',
																				'path' => '',
																				'type' => 'Plugin'	
																			 ),
														'toolsetform' => array(
																				'name' => 'Tool Set Form',
																				'path' => '',
																				'type' => 'Plugin'	
																			 ),
														'wpcomment' => array(
																				'name' => 'WP Comment',
																				'path' => '',
																				'type' => 'wordpress'	
																			 ),
														'wpforms' => array(
																				'name' => 'WP Forms',
																				'path' => '',
																				'type' => 'plugin'	
																			 ),
														'wpregistration' => array(
																				'name' => 'WP Registration',
																				'path' => '',
																				'type' => 'wordpress'	
																			 ),
														'userswp' => array(
																				'name' => 'UsersWP',
																				'path' => '',
																				'type' => 'plugin'	
																			 ),
														'mailin_brevo' => array(
																				'name' => 'Brevo (SendInBlue)',
																				'path' => '',
																				'type' => 'plugin'	
																			 ),
														'beaver_builder' => array(
																				'name' => 'Beaver Builder',
																				'path' => '',
																				'type' => 'plugin'	
																			 ),
														'sure_forms' => array(
																				'name' => 'Sure Forms',
																				'path' => '',
																				'type' => 'plugin'	
																			 ),
														'everest_forms' => array(
																				'name' => 'Everest Forms',
																				'path' => '',
																				'type' => 'plugin'	
																			 ),
														'bit_forms' => array(
																				'name' => 'Bit Forms',
																				'path' => '',
																				'type' => 'plugin'	
																			 )
														);