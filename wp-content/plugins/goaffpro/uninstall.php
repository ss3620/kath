<?php
# Silence is golden.
$goaffpro_token_key = 'goaffpro_public_token';
if(is_multisite()){
	$goaffpro_token_key .= "_".get_current_blog_id();
}
delete_option($goaffpro_token_key);